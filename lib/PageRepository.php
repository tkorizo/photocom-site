<?php

declare(strict_types=1);

class PageRepository
{
    public static function all(): array
    {
        return Database::getInstance()
            ->query('SELECT * FROM pages ORDER BY title ASC')
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM pages WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $page = $stmt->fetch();

        return $page ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM pages WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        $page = $stmt->fetch();

        return $page ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public static function published(): array
    {
        return Database::getInstance()
            ->query('SELECT * FROM pages WHERE is_published = 1 ORDER BY title ASC')
            ->fetchAll();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::getInstance()->prepare(
            'UPDATE pages SET title = :title, content = :content, is_published = :is_published,
             updated_at = datetime(\'now\') WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'content' => $data['content'] ?? '',
            'is_published' => !empty($data['is_published']) ? 1 : 0,
        ]);
    }
}
