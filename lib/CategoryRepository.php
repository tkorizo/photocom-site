<?php

declare(strict_types=1);

class CategoryRepository
{
    public static function all(?string $filter = null): array
    {
        $where = match ($filter) {
            'root' => 'WHERE c.parent_id IS NULL',
            'children' => 'WHERE c.parent_id IS NOT NULL',
            'with_products' => 'WHERE EXISTS (SELECT 1 FROM product_categories pc WHERE pc.category_id = c.id)',
            'empty' => 'WHERE NOT EXISTS (SELECT 1 FROM product_categories pc WHERE pc.category_id = c.id)',
            'with_image' => "WHERE c.thumbnail IS NOT NULL AND c.thumbnail != ''",
            default => '',
        };

        return Database::getInstance()
            ->query(
                'SELECT c.*, p.name as parent_name,
                    (SELECT COUNT(DISTINCT pc.product_id) FROM product_categories pc WHERE pc.category_id = c.id) as products_count
                 FROM categories c
                 LEFT JOIN categories p ON p.id = c.parent_id
                 ' . $where . '
                 ORDER BY c.menu_order ASC, COALESCE(c.parent_id, c.id), c.parent_id IS NOT NULL, c.name ASC'
            )
            ->fetchAll();
    }

    public static function filterLabel(?string $filter): ?string
    {
        return match ($filter) {
            'root' => 'Catégories racines',
            'children' => 'Sous-catégories',
            'with_products' => 'Catégories avec produits',
            'empty' => 'Catégories sans produit',
            'with_image' => 'Catégories avec miniature',
            default => null,
        };
    }

    public static function tree(): array
    {
        return self::buildTree(self::all());
    }

    public static function menuTree(): array
    {
        return self::buildTree(self::all());
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM categories WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function heroImage(?array $category): ?string
    {
        if (!$category) {
            return null;
        }

        foreach (['title_background', 'large_category_icon', 'thumbnail'] as $field) {
            $value = trim((string) ($category[$field] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    public static function descendantIds(int $categoryId): array
    {
        $db = Database::getInstance();
        $ids = [$categoryId];
        $stmt = $db->prepare('SELECT id FROM categories WHERE parent_id = :parent_id');
        $queue = [$categoryId];

        while ($queue) {
            $parentId = array_shift($queue);
            $stmt->execute(['parent_id' => $parentId]);
            foreach ($stmt->fetchAll() as $row) {
                $id = (int) $row['id'];
                $ids[] = $id;
                $queue[] = $id;
            }
        }

        return $ids;
    }

    public static function allForSelect(?int $excludeId = null): array
    {
        $categories = self::buildTree(self::allFlat());
        $flat = [];
        self::flattenTree($categories, $flat, 0, $excludeId);
        return $flat;
    }

    public static function allFlat(): array
    {
        return Database::getInstance()
            ->query('SELECT * FROM categories ORDER BY menu_order ASC, name ASC')
            ->fetchAll();
    }

    private static function buildTree(array $categories, ?int $parentId = null): array
    {
        $tree = [];
        foreach ($categories as $category) {
            $catParent = $category['parent_id'] !== null ? (int) $category['parent_id'] : null;
            if ($catParent === $parentId) {
                $category['children'] = self::buildTree($categories, (int) $category['id']);
                $tree[] = $category;
            }
        }
        return $tree;
    }

    private static function flattenTree(array $tree, array &$flat, int $depth, ?int $excludeId): void
    {
        foreach ($tree as $node) {
            if ($excludeId !== null && (int) $node['id'] === $excludeId) {
                continue;
            }
            $node['depth'] = $depth;
            $node['label'] = str_repeat('— ', $depth) . $node['name'];
            $flat[] = $node;
            if (!empty($node['children'])) {
                self::flattenTree($node['children'], $flat, $depth + 1, $excludeId);
            }
        }
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByWordpressId(int $wordpressId): ?array
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM categories WHERE wordpress_id = :wordpress_id');
        $stmt->execute(['wordpress_id' => $wordpressId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $slug = $data['slug'] ?? Helpers::uniqueSlug('categories', $data['name']);
        $stmt = Database::getInstance()->prepare(
            'INSERT INTO categories (
                name, slug, description, parent_id, tax_code, display_type, thumbnail,
                category_icon, large_category_icon, title_background, extra_description,
                menu_order, wordpress_id
            ) VALUES (
                :name, :slug, :description, :parent_id, :tax_code, :display_type, :thumbnail,
                :category_icon, :large_category_icon, :title_background, :extra_description,
                :menu_order, :wordpress_id
            )'
        );
        $stmt->execute(self::bindParams($data, $slug));
        return (int) Database::getInstance()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $slug = $data['slug'] ?? Helpers::uniqueSlug('categories', $data['name'], $id);
        $params = self::bindParams($data, $slug);
        $params['id'] = $id;
        unset($params['wordpress_id']);

        $stmt = Database::getInstance()->prepare(
            'UPDATE categories SET
                name = :name, slug = :slug, description = :description, parent_id = :parent_id,
                tax_code = :tax_code, display_type = :display_type, thumbnail = :thumbnail,
                category_icon = :category_icon, large_category_icon = :large_category_icon,
                title_background = :title_background, extra_description = :extra_description,
                menu_order = :menu_order
             WHERE id = :id'
        );
        return $stmt->execute($params);
    }

    public static function updateOrCreateByWordpressId(int $wordpressId, array $data): int
    {
        $existing = self::findByWordpressId($wordpressId);
        if ($existing) {
            $data['wordpress_id'] = $wordpressId;
            self::update((int) $existing['id'], $data);
            return (int) $existing['id'];
        }
        $data['wordpress_id'] = $wordpressId;
        return self::create($data);
    }

    public static function setParentByWordpressIds(int $wordpressId, int $parentWordpressId): void
    {
        $category = self::findByWordpressId($wordpressId);
        if (!$category) {
            return;
        }
        $parentId = null;
        if ($parentWordpressId > 0) {
            $parent = self::findByWordpressId($parentWordpressId);
            $parentId = $parent ? (int) $parent['id'] : null;
        }
        $stmt = Database::getInstance()->prepare('UPDATE categories SET parent_id = :parent_id WHERE id = :id');
        $stmt->execute(['parent_id' => $parentId, 'id' => $category['id']]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::getInstance()->prepare('DELETE FROM categories WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public static function count(): int
    {
        return (int) Database::getInstance()->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    }

    private static function bindParams(array $data, string $slug): array
    {
        return [
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'parent_id' => !empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            'tax_code' => $data['tax_code'] ?? null,
            'display_type' => $data['display_type'] ?? 'default',
            'thumbnail' => $data['thumbnail'] ?? null,
            'category_icon' => $data['category_icon'] ?? null,
            'large_category_icon' => $data['large_category_icon'] ?? null,
            'title_background' => $data['title_background'] ?? null,
            'extra_description' => $data['extra_description'] ?? null,
            'menu_order' => (int) ($data['menu_order'] ?? 0),
            'wordpress_id' => $data['wordpress_id'] ?? null,
        ];
    }
}
