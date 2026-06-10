<?php

declare(strict_types=1);

class UserRepository
{
    public static function all(): array
    {
        return Database::getInstance()
            ->query('SELECT id, email, name, role, created_at FROM users ORDER BY created_at ASC')
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => strtolower(trim($email))]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function create(string $name, string $email, string $password, string $role = 'editor'): int
    {
        if (!in_array($role, ['admin', 'editor'], true)) {
            $role = 'editor';
        }

        if (self::findByEmail($email)) {
            throw new RuntimeException('Cet email est déjà utilisé.');
        }

        $stmt = Database::getInstance()->prepare(
            'INSERT INTO users (email, password_hash, name, role) VALUES (:email, :password_hash, :name, :role)'
        );
        $stmt->execute([
            'email' => strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'name' => trim($name),
            'role' => $role,
        ]);

        return (int) Database::getInstance()->lastInsertId();
    }

    public static function update(int $id, string $name, string $email, string $role, ?string $password = null): void
    {
        if (!in_array($role, ['admin', 'editor'], true)) {
            $role = 'editor';
        }

        $existing = self::findByEmail($email);
        if ($existing && (int) $existing['id'] !== $id) {
            throw new RuntimeException('Cet email est déjà utilisé.');
        }

        $params = [
            'id' => $id,
            'name' => trim($name),
            'email' => strtolower(trim($email)),
            'role' => $role,
        ];

        if ($password !== null && $password !== '') {
            $sql = 'UPDATE users SET name = :name, email = :email, role = :role, password_hash = :password_hash WHERE id = :id';
            $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        } else {
            $sql = 'UPDATE users SET name = :name, email = :email, role = :role WHERE id = :id';
        }

        Database::getInstance()->prepare($sql)->execute($params);
    }

    public static function countAdmins(): int
    {
        return (int) Database::getInstance()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    }

    public static function delete(int $id): void
    {
        $user = self::find($id);
        if (!$user) {
            return;
        }

        $count = (int) Database::getInstance()->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count <= 1) {
            throw new RuntimeException('Impossible de supprimer le dernier utilisateur.');
        }

        if ($user['role'] === 'admin' && self::countAdmins() <= 1) {
            throw new RuntimeException('Impossible de supprimer le dernier administrateur.');
        }

        $stmt = Database::getInstance()->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function roleLabel(string $role): string
    {
        return match ($role) {
            'admin' => 'Administrateur',
            'editor' => 'Éditeur',
            default => $role,
        };
    }
}
