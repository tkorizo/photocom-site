<?php

declare(strict_types=1);

class Auth
{
    private const SESSION_KEY = 'photocom_admin';
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 900;

    public const ROLE_CONTENT = 'content';
    public const ROLE_CATALOG = 'catalog';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPER_ADMIN = 'super_admin';

    /** @var list<string> */
    public const ROLES = [
        self::ROLE_CONTENT,
        self::ROLE_CATALOG,
        self::ROLE_EDITOR,
        self::ROLE_ADMIN,
        self::ROLE_SUPER_ADMIN,
    ];

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => self::isHttps(),
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

    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
            return true;
        }
        $proto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($proto === 'https') {
            return true;
        }

        return false;
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
            RateLimiter::fail('login', self::MAX_ATTEMPTS, self::LOCKOUT_SECONDS);
            return false;
        }

        RateLimiter::reset('login');

        $role = UserRepository::normalizeRole((string) ($user['role'] ?? self::ROLE_CATALOG));

        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $role,
        ];
        unset($_SESSION['login_attempts'], $_SESSION['lockout_until']);

        if (class_exists('AuditLog')) {
            AuditLog::record('auth.login', 'user', (int) $user['id'], [
                'email' => $user['email'],
            ], [
                'id' => (int) $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $role,
            ]);
        }

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
        return UserRepository::normalizeRole((string) (self::user()['role'] ?? self::ROLE_CATALOG));
    }

    public static function isSuperAdmin(): bool
    {
        return self::role() === self::ROLE_SUPER_ADMIN;
    }

    /** Accès complet (administrateur ou super admin). */
    public static function isAdmin(): bool
    {
        return in_array(self::role(), [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN], true);
    }

    public static function canManageCatalog(): bool
    {
        return in_array(self::role(), [self::ROLE_CATALOG, self::ROLE_EDITOR, self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN], true);
    }

    public static function canManageContent(): bool
    {
        return in_array(self::role(), [self::ROLE_CONTENT, self::ROLE_EDITOR, self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN], true);
    }

    /** Page d’accueil (éditeur, admin, super admin). */
    public static function canManageHome(): bool
    {
        return in_array(self::role(), [self::ROLE_EDITOR, self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN], true);
    }

    public static function canManageUsers(): bool
    {
        return self::isAdmin();
    }

    public static function canViewAuditLog(): bool
    {
        return self::isSuperAdmin();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /admin/login.php');
            exit;
        }
        self::refreshSessionFromDatabase();
    }

    /** Aligne le rôle en session avec la base (après migration des rôles). */
    private static function refreshSessionFromDatabase(): void
    {
        $sessionUser = self::user();
        if (!$sessionUser) {
            return;
        }

        $dbUser = UserRepository::find((int) $sessionUser['id']);
        if (!$dbUser) {
            self::logout();
            header('Location: /admin/login.php');
            exit;
        }

        $_SESSION[self::SESSION_KEY]['role'] = UserRepository::normalizeRole((string) $dbUser['role']);
        $_SESSION[self::SESSION_KEY]['name'] = $dbUser['name'];
        $_SESSION[self::SESSION_KEY]['email'] = $dbUser['email'];
    }

    public static function requireCatalog(): void
    {
        self::requireLogin();
        if (!self::canManageCatalog()) {
            Helpers::flash('error', 'Accès réservé à la gestion du catalogue.');
            Helpers::redirect('/admin/index.php');
        }
    }

    public static function requireContent(): void
    {
        self::requireLogin();
        if (!self::canManageContent()) {
            Helpers::flash('error', 'Accès réservé à la gestion du contenu.');
            Helpers::redirect('/admin/index.php');
        }
    }

    public static function requireHome(): void
    {
        self::requireLogin();
        if (!self::canManageHome()) {
            Helpers::flash('error', 'Accès réservé à la configuration de la page d’accueil.');
            Helpers::redirect('/admin/index.php');
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

    public static function requireSuperAdmin(): void
    {
        self::requireLogin();
        if (!self::isSuperAdmin()) {
            Helpers::flash('error', 'Accès réservé au super administrateur.');
            Helpers::redirect('/admin/index.php');
        }
    }

    public static function logout(): void
    {
        self::startSession();
        if (self::check() && class_exists('AuditLog')) {
            AuditLog::record('auth.logout', 'user', (int) (self::user()['id'] ?? 0));
        }
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
        $sessionLeft = 0;
        if (isset($_SESSION['lockout_until'])) {
            $sessionLeft = max(0, (int) $_SESSION['lockout_until'] - time());
        }

        return max($sessionLeft, RateLimiter::lockRemaining('login'));
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
            'role' => self::ROLE_SUPER_ADMIN,
        ]);
    }
}
