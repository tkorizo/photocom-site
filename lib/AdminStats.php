<?php

declare(strict_types=1);

class AdminStats
{
    public static function products(): array
    {
        $db = Database::getInstance();

        return [
            'total' => (int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn(),
            'active' => (int) $db->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn(),
            'inactive' => (int) $db->query('SELECT COUNT(*) FROM products WHERE is_active = 0')->fetchColumn(),
            'out_of_stock' => (int) $db->query(
                'SELECT COUNT(*) FROM products WHERE is_out_of_stock = 1 OR stock_status = \'outofstock\''
            )->fetchColumn(),
            'coming_soon' => (int) $db->query('SELECT COUNT(*) FROM products WHERE is_coming_soon = 1')->fetchColumn(),
            'on_sale' => (int) $db->query('SELECT COUNT(*) FROM products WHERE on_sale = 1')->fetchColumn(),
            'featured' => (int) $db->query('SELECT COUNT(*) FROM products WHERE featured = 1')->fetchColumn(),
            'in_stock' => (int) $db->query(
                'SELECT COUNT(*) FROM products WHERE is_out_of_stock = 0 AND stock_status = \'instock\' AND is_active = 1'
            )->fetchColumn(),
        ];
    }

    public static function categories(): array
    {
        $db = Database::getInstance();

        return [
            'total' => (int) $db->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
            'root' => (int) $db->query('SELECT COUNT(*) FROM categories WHERE parent_id IS NULL')->fetchColumn(),
            'children' => (int) $db->query('SELECT COUNT(*) FROM categories WHERE parent_id IS NOT NULL')->fetchColumn(),
            'with_products' => (int) $db->query(
                'SELECT COUNT(DISTINCT category_id) FROM product_categories'
            )->fetchColumn(),
            'empty' => (int) $db->query(
                'SELECT COUNT(*) FROM categories c
                 WHERE NOT EXISTS (SELECT 1 FROM product_categories pc WHERE pc.category_id = c.id)'
            )->fetchColumn(),
            'with_image' => (int) $db->query(
                'SELECT COUNT(*) FROM categories WHERE thumbnail IS NOT NULL AND thumbnail != \'\''
            )->fetchColumn(),
            'total_products_linked' => (int) $db->query(
                'SELECT COUNT(*) FROM product_categories'
            )->fetchColumn(),
        ];
    }

    public static function import(): array
    {
        $db = Database::getInstance();
        $lastLog = WordPressImporter::recentLogs(1)[0] ?? null;
        $config = Helpers::config();
        $configured = $config['wordpress_url'] !== '' && $config['woocommerce_key'] !== '' && $config['woocommerce_secret'] !== '';

        return [
            'products' => (int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn(),
            'categories' => (int) $db->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
            'imports_count' => (int) $db->query('SELECT COUNT(*) FROM import_logs')->fetchColumn(),
            'last_import_date' => $lastLog['created_at'] ?? null,
            'last_import_status' => $lastLog['status'] ?? null,
            'last_import_products' => (int) ($lastLog['products_count'] ?? 0),
            'last_import_categories' => (int) ($lastLog['categories_count'] ?? 0),
            'configured' => $configured,
        ];
    }

    public static function dashboard(): array
    {
        $products = self::products();
        $categories = self::categories();
        $import = self::import();

        return [
            'products' => $products,
            'categories' => $categories,
            'import' => $import,
            'founded' => Helpers::config()['founded'],
        ];
    }
}
