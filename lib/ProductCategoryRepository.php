<?php

declare(strict_types=1);

class ProductCategoryRepository
{
    public static function syncForProduct(int $productId, array $categoryIds): void
    {
        $db = Database::getInstance();
        $db->prepare('DELETE FROM product_categories WHERE product_id = :product_id')
            ->execute(['product_id' => $productId]);

        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));

        if (empty($categoryIds)) {
            return;
        }

        $stmt = $db->prepare(
            'INSERT OR IGNORE INTO product_categories (product_id, category_id) VALUES (:product_id, :category_id)'
        );

        foreach ($categoryIds as $categoryId) {
            $stmt->execute([
                'product_id' => $productId,
                'category_id' => $categoryId,
            ]);
        }
    }

    public static function getCategoryIds(int $productId): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT category_id FROM product_categories WHERE product_id = :product_id ORDER BY category_id ASC'
        );
        $stmt->execute(['product_id' => $productId]);

        return array_map('intval', array_column($stmt->fetchAll(), 'category_id'));
    }

    public static function getCategoryNames(int $productId): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT c.name FROM product_categories pc
             JOIN categories c ON c.id = pc.category_id
             WHERE pc.product_id = :product_id
             ORDER BY c.name ASC'
        );
        $stmt->execute(['product_id' => $productId]);

        return array_column($stmt->fetchAll(), 'name');
    }

    public static function getCategoriesLabel(int $productId): string
    {
        $names = self::getCategoryNames($productId);
        return $names ? implode(', ', $names) : '—';
    }
}
