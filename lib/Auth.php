<?php

declare(strict_types=1);

class Auth
{
    private const SESSION_KEY = 'photocom_admin';
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 900;

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();
        }
    }

    public static function csrfToken(): string
    {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        self::startSession();
        return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::csrfToken()) . '">';
    }

    public static function attempt(string $email, string $password): bool
    {
        self::startSession();

        if (self::isLockedOut()) {
            return false;
        }

        $stmt = Database::getInstance()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email))]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            self::recordFailedAttempt();
            return false;
        }

        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'] ?? 'editor',
        ];
        unset($_SESSION['login_attempts'], $_SESSION['lockout_until']);

        return true;
    }

    public static function check(): bool
    {
        self::startSession();
        return isset($_SESSION[self::SESSION_KEY]['id']);
    }

    public static function user(): ?array
    {
        self::startSession();
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    public static function role(): string
    {
        return self::user()['role'] ?? 'editor';
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function canManageCatalog(): bool
    {
        return in_array(self::role(), ['admin', 'editor'], true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /admin/login.php');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            Helpers::flash('error', 'Accès réservé aux administrateurs.');
            Helpers::redirect('/admin/index.php');
        }
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function lockoutRemaining(): int
    {
        self::startSession();
        if (!isset($_SESSION['lockout_until'])) {
            return 0;
        }

        return max(0, (int) $_SESSION['lockout_until'] - time());
    }

    private static function isLockedOut(): bool
    {
        return self::lockoutRemaining() > 0;
    }

    private static function recordFailedAttempt(): void
    {
        self::startSession();
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;

        if ($_SESSION['login_attempts'] >= self::MAX_ATTEMPTS) {
            $_SESSION['lockout_until'] = time() + self::LOCKOUT_SECONDS;
            $_SESSION['login_attempts'] = 0;
        }
    }

    public static function createAdmin(string $email, string $password, string $name): void
    {
        $stmt = Database::getInstance()->prepare(
            'INSERT INTO users (email, password_hash, name, role) VALUES (:email, :password_hash, :name, :role)'
        );
        $stmt->execute([
            'email' => strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'name' => $name,
            'role' => 'admin',
        ]);
    }
}
