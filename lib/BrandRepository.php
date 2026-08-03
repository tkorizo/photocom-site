<?php

declare(strict_types=1);

class BrandRepository
{
    /** @return list<array<string, mixed>> */
    public static function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM brands';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY name ASC';

        return Database::getInstance()->query($sql)->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public static function forHome(): array
    {
        return Database::getInstance()
            ->query('SELECT * FROM brands WHERE is_active = 1 ORDER BY sort_order ASC, name ASC')
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM brands WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findByName(string $name): ?array
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM brands WHERE lower(name) = lower(:name)');
        $stmt->execute(['name' => trim($name)]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(string $name, ?string $logo = null): int
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('Le nom de la marque est obligatoire.');
        }

        $existing = self::findByName($name);
        if ($existing) {
            return (int) $existing['id'];
        }

        $slug = Helpers::uniqueSlug('brands', $name);
        $stmt = Database::getInstance()->prepare(
            'INSERT INTO brands (name, slug, logo, is_active, sort_order)
             VALUES (:name, :slug, :logo, 1, 0)'
        );
        $stmt->execute([
            'name' => $name,
            'slug' => $slug,
            'logo' => $logo,
        ]);

        return (int) Database::getInstance()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = Database::getInstance()->prepare(
            'UPDATE brands SET
                name = :name,
                slug = :slug,
                logo = :logo,
                is_active = :is_active,
                sort_order = :sort_order,
                updated_at = datetime(\'now\')
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'name' => trim((string) ($data['name'] ?? '')),
            'slug' => trim((string) ($data['slug'] ?? '')) ?: Helpers::uniqueSlug('brands', (string) ($data['name'] ?? 'marque'), $id),
            'logo' => $data['logo'] ?? null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    public static function updateLogo(int $id, ?string $logo): void
    {
        $stmt = Database::getInstance()->prepare(
            'UPDATE brands SET logo = :logo, updated_at = datetime(\'now\') WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'logo' => $logo]);
    }

    public static function delete(int $id): void
    {
        $brand = self::find($id);
        if ($brand && !empty($brand['logo'])) {
            FileUploader::deleteFile($brand['logo']);
        }
        Database::getInstance()->prepare('DELETE FROM brands WHERE id = :id')->execute(['id' => $id]);
    }

    /** Importe les marques distinctes déjà présentes sur les produits. */
    public static function syncFromProducts(): void
    {
        $rows = Database::getInstance()
            ->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND trim(brand) != '' ORDER BY brand ASC")
            ->fetchAll();

        foreach ($rows as $row) {
            self::create((string) $row['brand']);
        }
    }

    /** @return list<string> */
    public static function names(): array
    {
        return array_map(
            static fn (array $b): string => (string) $b['name'],
            self::all(true)
        );
    }
}
