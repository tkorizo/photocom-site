<?php

declare(strict_types=1);

class CustomerRepository
{
    /** @return list<array<string,mixed>> */
    public static function all(?string $search = null): array
    {
        $sql = 'SELECT id, email, name, phone, is_active, created_at, updated_at FROM customers';
        $params = [];
        if ($search !== null && trim($search) !== '') {
            $sql .= ' WHERE name LIKE :q OR email LIKE :q OR phone LIKE :q';
            $params['q'] = '%' . trim($search) . '%';
        }
        $sql .= ' ORDER BY created_at DESC';
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function count(): int
    {
        return (int) Database::getInstance()->query('SELECT COUNT(*) FROM customers')->fetchColumn();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM customers WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM customers WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email))]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(string $name, string $email, string $password, string $phone = ''): int
    {
        $email = strtolower(trim($email));
        $name = trim($name);
        $phone = trim($phone);

        if ($name === '' || strlen($name) < 2) {
            throw new RuntimeException('Indiquez un nom valide (2 caractères minimum).');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Adresse e-mail invalide.');
        }
        if (strlen($password) < 8) {
            throw new RuntimeException('Le mot de passe doit contenir au moins 8 caractères.');
        }
        if (self::findByEmail($email)) {
            throw new RuntimeException('Un compte existe déjà avec cet e-mail.');
        }

        $stmt = Database::getInstance()->prepare(
            'INSERT INTO customers (email, password_hash, name, phone, is_active)
             VALUES (:email, :password_hash, :name, :phone, 1)'
        );
        $stmt->execute([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'name' => $name,
            'phone' => $phone !== '' ? $phone : null,
        ]);

        return (int) Database::getInstance()->lastInsertId();
    }

    public static function setActive(int $id, bool $active): void
    {
        $stmt = Database::getInstance()->prepare(
            'UPDATE customers SET is_active = :active, updated_at = datetime(\'now\') WHERE id = :id'
        );
        $stmt->execute([
            'active' => $active ? 1 : 0,
            'id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::getInstance()->prepare('DELETE FROM customers WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
