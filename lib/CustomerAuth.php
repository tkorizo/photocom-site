<?php

declare(strict_types=1);

class CustomerAuth
{
    private const SESSION_KEY = 'photocom_customer';
    private const MAX_ATTEMPTS = 8;
    private const LOCKOUT_SECONDS = 900;

    public static function startSession(): void
    {
        Auth::startSession();
    }

    public static function attempt(string $email, string $password): bool
    {
        self::startSession();

        if (self::isLockedOut()) {
            return false;
        }

        $customer = CustomerRepository::findByEmail($email);
        if (
            !$customer
            || (int) ($customer['is_active'] ?? 0) !== 1
            || !password_verify($password, (string) $customer['password_hash'])
        ) {
            self::recordFailedAttempt();
            RateLimiter::fail('customer_login', self::MAX_ATTEMPTS, self::LOCKOUT_SECONDS);
            return false;
        }

        RateLimiter::reset('customer_login');

        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = [
            'id' => (int) $customer['id'],
            'email' => $customer['email'],
            'name' => $customer['name'],
        ];
        unset($_SESSION['customer_login_attempts'], $_SESSION['customer_lockout_until']);

        return true;
    }

    public static function loginById(int $id): bool
    {
        $customer = CustomerRepository::find($id);
        if (!$customer || (int) ($customer['is_active'] ?? 0) !== 1) {
            return false;
        }

        self::startSession();
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = [
            'id' => (int) $customer['id'],
            'email' => $customer['email'],
            'name' => $customer['name'],
        ];

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

    public static function logout(): void
    {
        self::startSession();
        unset($_SESSION[self::SESSION_KEY]);
    }

    private static function isLockedOut(): bool
    {
        $until = (int) ($_SESSION['customer_lockout_until'] ?? 0);
        return $until > time() || RateLimiter::isLocked('customer_login');
    }

    private static function recordFailedAttempt(): void
    {
        $attempts = (int) ($_SESSION['customer_login_attempts'] ?? 0) + 1;
        $_SESSION['customer_login_attempts'] = $attempts;
        if ($attempts >= self::MAX_ATTEMPTS) {
            $_SESSION['customer_lockout_until'] = time() + self::LOCKOUT_SECONDS;
            unset($_SESSION['customer_login_attempts']);
        }
    }
}
