<?php

declare(strict_types=1);

class CategoryMigrator
{
    public static function migrate(bool $dryRun = false): array
    {
        $db = Database::getInstance();
        $assignments = self::classifyAllProducts();
        $stats = self::buildStats($assignments);

        if ($dryRun) {
            return $stats;
        }

        $db->beginTransaction();
        try {
            $db->exec('DELETE FROM product_categories');
            $db->exec('DELETE FROM categories');

            $slugToId = self::insertCategoryTree($db);
            self::applyAssignments($db, $assignments, $slugToId);

            $db->commit();
            $stats['categories_created'] = count($slugToId);

            return $stats;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /** Reclasse les produits sans recréer l'arborescence catégories. */
    public static function reassignProducts(bool $dryRun = false): array
    {
        $db = Database::getInstance();
        $slugToId = self::loadSlugMap($db);

        if (empty($slugToId)) {
            return self::migrate($dryRun);
        }

        $assignments = self::classifyAllProducts();
        $stats = self::buildStats($assignments);

        if ($dryRun) {
            return $stats;
        }

        $db->beginTransaction();
        try {
            $db->exec('DELETE FROM product_categories');
            self::applyAssignments($db, $assignments, $slugToId);
            $db->commit();

            return $stats;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /** @return array<int, array{slug: string, brand: ?string}> */
    private static function classifyAllProducts(): array
    {
        $products = Database::getInstance()->query(
            'SELECT id, name, short_description, description, brand FROM products'
        )->fetchAll();

        $assignments = [];
        foreach ($products as $product) {
            $assignments[(int) $product['id']] = [
                'slug' => ProductClassifier::classify($product),
                'brand' => ProductClassifier::detectBrand($product),
            ];
        }

        return $assignments;
    }

    /** @param array<int, array{slug: string, brand: ?string}> $assignments */
    private static function buildStats(array $assignments): array
    {
        $bySlug = [];
        $fallback = CategoryArchitecture::fallbackSlug();
        $classified = 0;
        $fallbackCount = 0;

        foreach ($assignments as $item) {
            $slug = $item['slug'];
            $bySlug[$slug] = ($bySlug[$slug] ?? 0) + 1;
            if ($slug === $fallback) {
                $fallbackCount++;
            } else {
                $classified++;
            }
        }

        return [
            'total_products' => count($assignments),
            'classified' => $classified,
            'fallback' => $fallbackCount,
            'by_slug' => $bySlug,
        ];
    }

    /** @return array<string, int> */
    private static function loadSlugMap(PDO $db): array
    {
        $rows = $db->query('SELECT id, slug FROM categories')->fetchAll();
        $map = [];
        foreach ($rows as $row) {
            $map[$row['slug']] = (int) $row['id'];
        }

        return $map;
    }

    /** @return array<string, int> */
    private static function insertCategoryTree(PDO $db): array
    {
        $slugToId = [];
        foreach (CategoryArchitecture::tree() as $root) {
            $rootId = self::insertCategory($db, $root['name'], $root['slug'], null, (int) $root['menu_order']);
            $slugToId[$root['slug']] = $rootId;

            foreach ($root['children'] as $order => $child) {
                $childId = self::insertCategory($db, $child['name'], $child['slug'], $rootId, $order + 1);
                $slugToId[$child['slug']] = $childId;
            }
        }

        return $slugToId;
    }

    /**
     * @param array<int, array{slug: string, brand: ?string}> $assignments
     * @param array<string, int> $slugToId
     */
    private static function applyAssignments(PDO $db, array $assignments, array $slugToId): void
    {
        $fallbackId = $slugToId[CategoryArchitecture::fallbackSlug()] ?? null;

        $linkStmt = $db->prepare(
            'INSERT INTO product_categories (product_id, category_id) VALUES (:product_id, :category_id)'
        );
        $updateProductStmt = $db->prepare(
            'UPDATE products SET category_id = :category_id, brand = CASE WHEN :brand != \'\' THEN :brand ELSE brand END WHERE id = :id'
        );

        foreach ($assignments as $productId => $item) {
            $categoryId = $slugToId[$item['slug']] ?? $fallbackId;
            if (!$categoryId) {
                continue;
            }
            $linkStmt->execute(['product_id' => $productId, 'category_id' => $categoryId]);
            $updateProductStmt->execute([
                'id' => $productId,
                'category_id' => $categoryId,
                'brand' => $item['brand'] ?? '',
            ]);
        }
    }

    private static function insertCategory(PDO $db, string $name, string $slug, ?int $parentId, int $menuOrder): int
    {
        $stmt = $db->prepare(
            'INSERT INTO categories (name, slug, parent_id, menu_order, display_type)
             VALUES (:name, :slug, :parent_id, :menu_order, :display_type)'
        );
        $stmt->execute([
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $parentId,
            'menu_order' => $menuOrder,
            'display_type' => 'default',
        ]);

        return (int) $db->lastInsertId();
    }

    /** @deprecated Utiliser ProductClassifier::classify() */
    public static function classifyProduct(string $name, array $rules, array $priority): string
    {
        return ProductClassifier::classify(['name' => $name, 'short_description' => '', 'description' => '']);
    }
}
