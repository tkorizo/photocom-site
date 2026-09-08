<?php

declare(strict_types=1);

class UserRepository
{
    /** @return list<array{id:int,email:string,name:string,role:string,created_at:string}> */
    public static function all(): array
    {
        $users = Database::getInstance()
            ->query('SELECT id, email, name, role, created_at FROM users ORDER BY created_at ASC')
            ->fetchAll();

        if (Auth::isSuperAdmin()) {
            return $users;
        }

        return array_values(array_filter(
            $users,
            static fn (array $u): bool => self::normalizeRole((string) ($u['role'] ?? '')) !== Auth::ROLE_SUPER_ADMIN
        ));
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

    public static function normalizeRole(string $role): string
    {
        $role = trim($role);

        return in_array($role, Auth::ROLES, true) ? $role : Auth::ROLE_CATALOG;
    }

    /** Rôles pouvant être attribués via le formulaire (jamais un 2e super admin). */
    public static function assignableRoles(): array
    {
        return [
            Auth::ROLE_CONTENT => 'Gestion de contenu — articles et pages',
            Auth::ROLE_CATALOG => 'Gestion de produits — catalogue, comptes et paniers clients',
            Auth::ROLE_EDITOR => 'Éditeur — catalogue, contenu et page d’accueil',
            Auth::ROLE_ADMIN => 'Administrateur — accès complet',
        ];
    }

    public static function create(string $name, string $email, string $password, string $role = Auth::ROLE_CATALOG): int
    {
        $role = self::normalizeRole($role);
        if ($role === Auth::ROLE_SUPER_ADMIN || !isset(self::assignableRoles()[$role])) {
            $role = Auth::ROLE_CATALOG;
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
        $existingUser = self::find($id);
        if (!$existingUser) {
            throw new RuntimeException('Utilisateur introuvable.');
        }

        $currentRole = self::normalizeRole((string) $existingUser['role']);
        $role = self::normalizeRole($role);

        // Le super admin unique ne peut pas être rétrogradé.
        if ($currentRole === Auth::ROLE_SUPER_ADMIN) {
            $role = Auth::ROLE_SUPER_ADMIN;
        } elseif ($role === Auth::ROLE_SUPER_ADMIN || !isset(self::assignableRoles()[$role])) {
            $role = Auth::ROLE_CATALOG;
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

    public static function countByRole(string $role): int
    {
        $stmt = Database::getInstance()->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
        $stmt->execute(['role' => $role]);

        return (int) $stmt->fetchColumn();
    }

    public static function countAdmins(): int
    {
        return self::countByRole(Auth::ROLE_ADMIN) + self::countByRole(Auth::ROLE_SUPER_ADMIN);
    }

    public static function countSuperAdmins(): int
    {
        return self::countByRole(Auth::ROLE_SUPER_ADMIN);
    }

    public static function delete(int $id): void
    {
        $user = self::find($id);
        if (!$user) {
            return;
        }

        $role = self::normalizeRole((string) $user['role']);

        $count = (int) Database::getInstance()->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count <= 1) {
            throw new RuntimeException('Impossible de supprimer le dernier utilisateur.');
        }

        if ($role === Auth::ROLE_SUPER_ADMIN) {
            throw new RuntimeException('Impossible de supprimer le compte super administrateur.');
        }

        if (in_array($role, [Auth::ROLE_ADMIN, Auth::ROLE_SUPER_ADMIN], true) && self::countAdmins() <= 1) {
            throw new RuntimeException('Impossible de supprimer le dernier administrateur.');
        }

        $stmt = Database::getInstance()->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function canActorManage(array $target, ?array $actor = null): bool
    {
        $actor = $actor ?? Auth::user();
        if (!$actor) {
            return false;
        }

        $actorRole = self::normalizeRole((string) ($actor['role'] ?? ''));
        $targetRole = self::normalizeRole((string) ($target['role'] ?? ''));

        if (!in_array($actorRole, [Auth::ROLE_ADMIN, Auth::ROLE_SUPER_ADMIN], true)) {
            return false;
        }

        if ($targetRole === Auth::ROLE_SUPER_ADMIN && $actorRole !== Auth::ROLE_SUPER_ADMIN) {
            return false;
        }

        return true;
    }

    public static function roleLabel(string $role): string
    {
        return match (self::normalizeRole($role)) {
            Auth::ROLE_SUPER_ADMIN => 'Super administrateur',
            Auth::ROLE_ADMIN => 'Administrateur',
            Auth::ROLE_EDITOR => 'Éditeur',
            Auth::ROLE_CATALOG => 'Gestion de produits',
            Auth::ROLE_CONTENT => 'Gestion de contenu',
            default => $role,
        };
    }

    public static function roleBadgeClass(string $role): string
    {
        return match (self::normalizeRole($role)) {
            Auth::ROLE_SUPER_ADMIN => 'badge-success',
            Auth::ROLE_ADMIN => 'badge-success',
            Auth::ROLE_EDITOR => 'badge-info',
            Auth::ROLE_CATALOG => 'badge-muted',
            Auth::ROLE_CONTENT => 'badge-muted',
            default => 'badge-muted',
        };
    }
}
