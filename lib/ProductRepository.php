<?php

declare(strict_types=1);

class ProductRepository
{
    public static function paginate(
        int $page = 1,
        int $perPage = 20,
        ?string $search = null,
        ?string $status = null,
        ?int $categoryId = null,
        ?string $filter = null,
        ?string $brand = null,
        ?string $adminLabel = null
    ): array {
        $offset = max(0, ($page - 1) * $perPage);
        $where = [];
        $params = [];

        if ($search) {
            $where[] = '(p.name LIKE :search OR p.sku LIKE :search OR p.brand LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        if ($status === 'active') {
            $where[] = 'p.is_active = 1';
        } elseif ($status === 'inactive') {
            $where[] = 'p.is_active = 0';
        }
        if ($categoryId) {
            $where[] = 'EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.id AND pc.category_id = :category_id)';
            $params['category_id'] = $categoryId;
        }
        if ($brand) {
            $where[] = 'p.brand = :brand';
            $params['brand'] = $brand;
        }
        if ($adminLabel === 'none') {
            $where[] = '(p.admin_label IS NULL OR p.admin_label = \'\')';
        } elseif (ProductLabel::isValid($adminLabel)) {
            $where[] = 'p.admin_label = :admin_label';
            $params['admin_label'] = $adminLabel;
        }

        match ($filter) {
            'out_of_stock' => $where[] = '(p.is_out_of_stock = 1 OR p.stock_status = \'outofstock\')',
            'coming_soon' => $where[] = 'p.is_coming_soon = 1',
            'in_stock' => $where[] = '(p.is_out_of_stock = 0 AND p.stock_status = \'instock\' AND p.is_active = 1)',
            'on_sale' => $where[] = 'p.on_sale = 1 AND p.sale_price IS NOT NULL AND p.price IS NOT NULL AND p.sale_price > 0 AND p.sale_price < p.price',
            'featured' => $where[] = 'p.featured = 1',
            default => null,
        };

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = Database::getInstance()->prepare("SELECT COUNT(*) FROM products p {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT p.*, c.name as category_name
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                {$whereSql}
                ORDER BY p.updated_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = Database::getInstance()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / max(1, $perPage)),
        ];
    }

    public static function paginatePublic(
        int $page = 1,
        int $perPage = 24,
        ?array $categoryIds = null,
        ?string $search = null,
        ?string $brand = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        ?string $sort = 'name',
        bool $onSaleOnly = false,
        bool $inStockOnly = false
    ): array {
        $offset = max(0, ($page - 1) * $perPage);
        $where = ['p.is_active = 1'];
        $params = [];

        if ($search) {
            $where[] = '(p.name LIKE :search OR p.sku LIKE :search OR p.brand LIKE :search OR p.short_description LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($brand) {
            $where[] = 'p.brand = :brand';
            $params['brand'] = $brand;
        }

        if ($minPrice !== null) {
            $where[] = 'p.price >= :min_price';
            $params['min_price'] = $minPrice;
        }

        if ($maxPrice !== null) {
            $where[] = 'p.price <= :max_price';
            $params['max_price'] = $maxPrice;
        }

        if ($onSaleOnly) {
            $where[] = 'p.on_sale = 1 AND p.sale_price IS NOT NULL AND p.price IS NOT NULL AND p.sale_price > 0 AND p.sale_price < p.price';
        }

        if ($inStockOnly) {
            $where[] = '(p.is_out_of_stock = 0 AND p.stock_status = \'instock\')';
        }

        if ($categoryIds) {
            $placeholders = [];
            foreach (array_values($categoryIds) as $i => $id) {
                $key = 'cat' . $i;
                $placeholders[] = ':' . $key;
                $params[$key] = $id;
            }
            $where[] = 'EXISTS (
                SELECT 1 FROM product_categories pc
                WHERE pc.product_id = p.id AND pc.category_id IN (' . implode(',', $placeholders) . ')
            )';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $orderBy = match ($sort) {
            'price_asc' => 'p.price ASC, p.name ASC',
            'price_desc' => 'p.price DESC, p.name ASC',
            'newest' => 'p.created_at DESC',
            default => 'p.name ASC',
        };

        $countStmt = Database::getInstance()->prepare("SELECT COUNT(*) FROM products p {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT p.*, c.name as category_name
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                {$whereSql}
                ORDER BY {$orderBy}
                LIMIT :limit OFFSET :offset";

        $stmt = Database::getInstance()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / max(1, $perPage)),
        ];
    }

    /**
     * @return array{brands: array<int, string>, price_min: float, price_max: float, total: int}
     */
    public static function publicFilterMeta(?array $categoryIds = null): array
    {
        $where = ['p.is_active = 1'];
        $params = [];

        if ($categoryIds) {
            $placeholders = [];
            foreach (array_values($categoryIds) as $i => $id) {
                $key = 'cat' . $i;
                $placeholders[] = ':' . $key;
                $params[$key] = $id;
            }
            $where[] = 'EXISTS (
                SELECT 1 FROM product_categories pc
                WHERE pc.product_id = p.id AND pc.category_id IN (' . implode(',', $placeholders) . ')
            )';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $db = Database::getInstance();

        $brandStmt = $db->prepare(
            "SELECT DISTINCT p.brand
             FROM products p
             {$whereSql}
               AND p.brand IS NOT NULL
               AND trim(p.brand) != ''
               AND COALESCE((
                   SELECT b.show_in_filter
                   FROM brands b
                   WHERE lower(b.name) = lower(p.brand)
                   LIMIT 1
               ), 1) = 1
             ORDER BY p.brand ASC"
        );
        $brandStmt->execute($params);
        $brands = array_column($brandStmt->fetchAll(), 'brand');

        $rangeStmt = $db->prepare(
            "SELECT MIN(p.price) as price_min, MAX(p.price) as price_max, COUNT(*) as total FROM products p {$whereSql}"
        );
        $rangeStmt->execute($params);
        $range = $rangeStmt->fetch() ?: ['price_min' => 0, 'price_max' => 0, 'total' => 0];

        return [
            'brands' => $brands,
            'price_min' => (float) ($range['price_min'] ?? 0),
            'price_max' => (float) ($range['price_max'] ?? 0),
            'total' => (int) ($range['total'] ?? 0),
        ];
    }

    /**
     * Suggestions de recherche (autosuggest header).
     *
     * @return list<array{id:int,name:string,slug:string,brand:string,image:string,price:float,url:string}>
     */
    public static function suggest(string $query, int $limit = 8): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        $limit = max(1, min(12, $limit));
        $stmt = Database::getInstance()->prepare(
            "SELECT p.id, p.name, p.slug, p.brand, p.image, p.price, p.sale_price, p.on_sale
             FROM products p
             WHERE p.is_active = 1
               AND (p.catalog_visibility IS NULL OR p.catalog_visibility = '' OR p.catalog_visibility IN ('visible', 'catalog', 'search'))
               AND (p.name LIKE :q1 OR p.sku LIKE :q2 OR IFNULL(p.brand, '') LIKE :q3)
             ORDER BY
               CASE WHEN lower(p.name) LIKE lower(:q_start) THEN 0 ELSE 1 END,
               p.name ASC
             LIMIT {$limit}"
        );
        $like = '%' . $query . '%';
        $stmt->execute([
            'q1' => $like,
            'q2' => $like,
            'q3' => $like,
            'q_start' => $query . '%',
        ]);

        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $pricing = Helpers::productPricing($row);
            $out[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'slug' => (string) ($row['slug'] ?? ''),
                'brand' => (string) ($row['brand'] ?? ''),
                'image' => (string) ($row['image'] ?? ''),
                'price' => (float) $pricing['current'],
                'url' => Helpers::productUrl($row),
            ];
        }

        return $out;
    }

    /**
     * Recherche catalogue pour l’agent de chat (produits visibles uniquement).
     *
     * @return list<array<string, mixed>>
     */
    public static function searchForChat(string $query, int $limit = 6, bool $includeCategory = true): array
    {
        $query = trim($query);
        $limit = max(1, min(8, $limit));
        if (mb_strlen($query) < 2) {
            return [];
        }

        $compact = preg_replace('/[\s\-_]+/u', '', mb_strtolower($query)) ?? mb_strtolower($query);
        $categorySql = $includeCategory
            ? "OR EXISTS (
                    SELECT 1 FROM product_categories pc
                    JOIN categories c ON c.id = pc.category_id
                    WHERE pc.product_id = p.id AND c.name LIKE :q4
               )"
            : '';

        $stmt = Database::getInstance()->prepare(
            "SELECT p.id, p.name, p.slug, p.brand, p.sku, p.image, p.price, p.sale_price, p.on_sale,
                    p.short_description, p.is_out_of_stock, p.is_coming_soon, p.stock_status, p.is_active,
                    p.catalog_visibility
             FROM products p
             WHERE p.is_active = 1
               AND (p.catalog_visibility IS NULL OR p.catalog_visibility = '' OR p.catalog_visibility IN ('visible', 'catalog', 'search'))
               AND (
                    p.name LIKE :q1 OR p.sku LIKE :q2 OR IFNULL(p.brand, '') LIKE :q3
                    OR p.slug LIKE :q5
                    OR replace(replace(lower(p.name), ' ', ''), '-', '') LIKE :q_compact
                    OR replace(replace(lower(IFNULL(p.slug, '')), ' ', ''), '-', '') LIKE :q_compact
                    {$categorySql}
               )
             ORDER BY
               CASE
                 WHEN lower(p.name) = lower(:q_exact) THEN 0
                 WHEN lower(p.slug) = lower(:q_exact) THEN 1
                 WHEN lower(p.name) LIKE lower(:q_start) THEN 2
                 ELSE 3
               END,
               p.name ASC
             LIMIT {$limit}"
        );
        $like = '%' . $query . '%';
        $params = [
            'q1' => $like,
            'q2' => $like,
            'q3' => $like,
            'q5' => $like,
            'q_compact' => '%' . $compact . '%',
            'q_exact' => $query,
            'q_start' => $query . '%',
        ];
        if ($includeCategory) {
            $params['q4'] = $like;
        }
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Suggestions de recherche admin (tous les produits, lien édition).
     *
     * @return list<array{id:int,name:string,slug:string,brand:string,sku:string,image:string,price:float,url:string}>
     */
    public static function suggestAdmin(string $query, int $limit = 8): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        $limit = max(1, min(12, $limit));
        $stmt = Database::getInstance()->prepare(
            "SELECT p.id, p.name, p.slug, p.brand, p.sku, p.image, p.price, p.sale_price, p.on_sale
             FROM products p
             WHERE (p.name LIKE :q1 OR p.sku LIKE :q2 OR IFNULL(p.brand, '') LIKE :q3)
             ORDER BY
               CASE WHEN lower(p.name) LIKE lower(:q_start) THEN 0 ELSE 1 END,
               p.name ASC
             LIMIT {$limit}"
        );
        $like = '%' . $query . '%';
        $stmt->execute([
            'q1' => $like,
            'q2' => $like,
            'q3' => $like,
            'q_start' => $query . '%',
        ]);

        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $pricing = Helpers::productPricing($row);
            $out[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'slug' => (string) ($row['slug'] ?? ''),
                'brand' => (string) ($row['brand'] ?? ''),
                'sku' => (string) ($row['sku'] ?? ''),
                'image' => (string) ($row['image'] ?? ''),
                'price' => (float) $pricing['current'],
                'url' => '/admin/produits/edit.php?id=' . (int) $row['id'],
            ];
        }

        return $out;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row) {
            $row['category_ids'] = ProductCategoryRepository::getCategoryIds($id);
        }
        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $slug = Helpers::slugify($slug);
        if ($slug === '') {
            return null;
        }

        $stmt = Database::getInstance()->prepare(
            'SELECT p.*, c.name as category_name, c.slug as category_slug
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.slug = :slug
             LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $row['category_ids'] = ProductCategoryRepository::getCategoryIds((int) $row['id']);

        return $row;
    }

    /** @return list<array> */
    public static function related(int $excludeId, ?int $categoryId, int $limit = 4): array
    {
        $limit = max(1, min(12, $limit));
        $params = ['exclude_id' => $excludeId];
        $where = ['p.is_active = 1', 'p.id != :exclude_id'];

        if ($categoryId !== null && $categoryId > 0) {
            $where[] = '(p.category_id = :category_id OR EXISTS (
                SELECT 1 FROM product_categories pc
                WHERE pc.product_id = p.id AND pc.category_id = :category_id
            ))';
            $params['category_id'] = $categoryId;
        }

        $sql = 'SELECT p.*, c.name as category_name FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY p.featured DESC, p.updated_at DESC
                LIMIT ' . $limit;

        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function findByWordpressId(int $wordpressId): ?array
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM products WHERE wordpress_id = :wordpress_id');
        $stmt->execute(['wordpress_id' => $wordpressId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $slug = trim((string) ($data['slug'] ?? ''));
        $slug = $slug !== '' ? Helpers::slugify($slug) : Helpers::slugify((string) $data['name']);
        $stmt = Database::getInstance()->prepare(
            'INSERT INTO products (
                name, slug, description, short_description, price, regular_price, sale_price,
                sku, brand, stock_quantity, manage_stock, stock_status, is_out_of_stock, is_coming_soon,
                catalog_visibility, hide_add_to_cart, featured, on_sale, category_id, image,
                images_secondary_json, images_json, permalink, wordpress_id, is_active
            ) VALUES (
                :name, :slug, :description, :short_description, :price, :regular_price, :sale_price,
                :sku, :brand, :stock_quantity, :manage_stock, :stock_status, :is_out_of_stock, :is_coming_soon,
                :catalog_visibility, :hide_add_to_cart, :featured, :on_sale, :category_id, :image,
                :images_secondary_json, :images_json, :permalink, :wordpress_id, :is_active
            )'
        );
        $stmt->execute(self::bindParams($data, $slug));
        $id = (int) Database::getInstance()->lastInsertId();

        if (!empty($data['category_ids'])) {
            ProductCategoryRepository::syncForProduct($id, $data['category_ids']);
        }

        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        $slug = trim((string) ($data['slug'] ?? ''));
        $slug = $slug !== '' ? Helpers::slugify($slug) : Helpers::slugify((string) $data['name']);
        $params = self::bindParams($data, $slug);
        $params['id'] = $id;
        unset($params['wordpress_id']);

        $stmt = Database::getInstance()->prepare(
            'UPDATE products SET
                name = :name, slug = :slug, description = :description, short_description = :short_description,
                price = :price, regular_price = :regular_price, sale_price = :sale_price,
                sku = :sku, brand = :brand, stock_quantity = :stock_quantity, manage_stock = :manage_stock,
                stock_status = :stock_status, is_out_of_stock = :is_out_of_stock, is_coming_soon = :is_coming_soon,
                catalog_visibility = :catalog_visibility, hide_add_to_cart = :hide_add_to_cart,
                featured = :featured, on_sale = :on_sale, category_id = :category_id, image = :image,
                images_secondary_json = :images_secondary_json, images_json = :images_json,
                permalink = :permalink, is_active = :is_active, updated_at = datetime(\'now\')
             WHERE id = :id'
        );
        $result = $stmt->execute($params);

        if ($result && array_key_exists('category_ids', $data)) {
            ProductCategoryRepository::syncForProduct($id, $data['category_ids'] ?? []);
        }

        return $result;
    }

    public static function quickUpdate(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        $allowed = [
            'stock_quantity' => 'int',
            'is_out_of_stock' => 'bool',
            'is_coming_soon' => 'bool',
            'is_active' => 'bool',
            'manage_stock' => 'bool',
            'featured' => 'bool',
            'on_sale' => 'bool',
            'hide_add_to_cart' => 'bool',
            'price' => 'float',
            'regular_price' => 'float',
            'sale_price' => 'float',
            'name' => 'string',
            'sku' => 'string',
            'brand' => 'string',
            'stock_status' => 'enum:instock,outofstock,onbackorder',
            'catalog_visibility' => 'enum:visible,catalog,search,hidden',
            'category_id' => 'int',
            'admin_label' => 'label',
        ];

        foreach ($allowed as $field => $type) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $value = $data[$field];
            $fields[] = "{$field} = :{$field}";

            if ($type === 'bool') {
                $params[$field] = !empty($value) ? 1 : 0;
            } elseif ($type === 'int') {
                $params[$field] = ($value === '' || $value === null) ? null : (int) $value;
            } elseif ($type === 'float') {
                $params[$field] = ($value === '' || $value === null) ? null : (float) $value;
            } elseif (str_starts_with($type, 'enum:')) {
                $options = explode(',', substr($type, 5));
                $params[$field] = in_array((string) $value, $options, true) ? (string) $value : $options[0];
            } elseif ($type === 'label') {
                $params[$field] = ProductLabel::normalize(is_string($value) ? $value : (string) ($value ?? ''));
            } else {
                $params[$field] = trim((string) ($value ?? ''));
            }
        }

        if (isset($data['is_out_of_stock']) && !isset($data['stock_status'])) {
            $fields[] = 'stock_status = :stock_status';
            $params['stock_status'] = !empty($data['is_out_of_stock']) ? 'outofstock' : 'instock';
        }

        if (empty($fields) && !array_key_exists('category_ids', $data)) {
            return false;
        }

        $ok = true;
        if (!empty($fields)) {
            $sql = 'UPDATE products SET ' . implode(', ', $fields) . ", updated_at = datetime('now') WHERE id = :id";
            $stmt = Database::getInstance()->prepare($sql);
            $ok = $stmt->execute($params);
        }

        if (array_key_exists('category_ids', $data)) {
            $categoryIds = is_array($data['category_ids']) ? $data['category_ids'] : [];
            ProductCategoryRepository::syncForProduct($id, $categoryIds);
            if ($categoryIds !== []) {
                $primary = (int) $categoryIds[0];
                Database::getInstance()
                    ->prepare('UPDATE products SET category_id = :category_id, updated_at = datetime(\'now\') WHERE id = :id')
                    ->execute(['category_id' => $primary, 'id' => $id]);
            } else {
                Database::getInstance()
                    ->prepare('UPDATE products SET category_id = NULL, updated_at = datetime(\'now\') WHERE id = :id')
                    ->execute(['id' => $id]);
            }
        }

        return $ok;
    }

    public static function updateOrCreateByWordpressId(int $wordpressId, array $data): int
    {
        $existing = self::findByWordpressId($wordpressId);
        if ($existing) {
            self::update((int) $existing['id'], $data);
            return (int) $existing['id'];
        }
        $data['wordpress_id'] = $wordpressId;
        return self::create($data);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::getInstance()->prepare('DELETE FROM products WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public static function count(): int
    {
        return (int) Database::getInstance()->query('SELECT COUNT(*) FROM products')->fetchColumn();
    }

    public static function countActive(): int
    {
        return (int) Database::getInstance()->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();
    }

    public static function recent(int $limit = 5): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.created_at DESC LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function featured(int $limit = 5): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT p.*, c.name as category_name FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.featured = 1 AND p.is_active = 1
             ORDER BY p.updated_at DESC LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** @param list<int> $ids */
    public static function findByIdsOrdered(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($ids as $i => $id) {
            $key = 'id' . $i;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }

        $in = implode(', ', $placeholders);
        $stmt = Database::getInstance()->prepare(
            "SELECT p.*, c.name as category_name FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id IN ({$in})"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['id']] = $row;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }

    public static function listActiveForPicker(int $limit = 300): array
    {
        $limit = max(1, min(1000, $limit));
        $stmt = Database::getInstance()->prepare(
            'SELECT p.id, p.name, p.brand, p.image, p.price, p.sku, p.featured
             FROM products p
             WHERE p.is_active = 1
             ORDER BY p.name ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function bestsellers(int $limit = 8): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT p.*, c.name as category_name FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.is_active = 1
             ORDER BY p.on_sale DESC, p.featured DESC, p.updated_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function filterLabel(?string $status, ?string $filter): ?string
    {
        if ($filter) {
            return match ($filter) {
                'out_of_stock' => 'Produits en rupture',
                'coming_soon' => 'Produits bientôt disponibles',
                'in_stock' => 'Produits en stock',
                'on_sale' => 'Produits en promotion',
                'featured' => 'Produits vedettes',
                default => null,
            };
        }
        if ($status === 'active') {
            return 'Produits actifs';
        }
        if ($status === 'inactive') {
            return 'Produits inactifs';
        }
        return null;
    }

    public static function getSecondaryImages(array $product): array
    {
        $json = $product['images_secondary_json'] ?? '[]';
        $images = json_decode($json, true);
        return is_array($images) ? $images : [];
    }

    private static function bindParams(array $data, string $slug): array
    {
        return [
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'price' => (float) ($data['price'] ?? 0),
            'regular_price' => isset($data['regular_price']) ? (float) $data['regular_price'] : null,
            'sale_price' => isset($data['sale_price']) ? (float) $data['sale_price'] : null,
            'sku' => $data['sku'] ?? null,
            'brand' => $data['brand'] ?? null,
            'stock_quantity' => isset($data['stock_quantity']) && $data['stock_quantity'] !== '' ? (int) $data['stock_quantity'] : null,
            'manage_stock' => !empty($data['manage_stock']) ? 1 : 0,
            'stock_status' => $data['stock_status'] ?? 'instock',
            'is_out_of_stock' => !empty($data['is_out_of_stock']) ? 1 : 0,
            'is_coming_soon' => !empty($data['is_coming_soon']) ? 1 : 0,
            'catalog_visibility' => $data['catalog_visibility'] ?? 'visible',
            'hide_add_to_cart' => !empty($data['hide_add_to_cart']) ? 1 : 0,
            'featured' => !empty($data['featured']) ? 1 : 0,
            'on_sale' => !empty($data['on_sale']) ? 1 : 0,
            'category_id' => $data['category_id'] ?? null,
            'image' => $data['image'] ?? null,
            'images_secondary_json' => $data['images_secondary_json'] ?? null,
            'images_json' => $data['images_json'] ?? null,
            'permalink' => $data['permalink'] ?? null,
            'wordpress_id' => $data['wordpress_id'] ?? null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ];
    }
}
