<?php

declare(strict_types=1);

class ArticleRepository
{
    public static function all(): array
    {
        return Database::getInstance()
            ->query('SELECT * FROM articles ORDER BY created_at DESC')
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM articles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $article = $stmt->fetch();

        return $article ?: null;
    }

    public static function count(): int
    {
        return (int) Database::getInstance()->query('SELECT COUNT(*) FROM articles')->fetchColumn();
    }

    public static function countPublished(): int
    {
        return (int) Database::getInstance()->query('SELECT COUNT(*) FROM articles WHERE is_published = 1')->fetchColumn();
    }

    public static function publishedRecent(int $limit = 2): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM articles WHERE is_published = 1 ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $slug = $data['slug'] ?: Helpers::uniqueSlug('articles', $data['title']);
        $stmt = Database::getInstance()->prepare(
            'INSERT INTO articles (title, slug, excerpt, content, image, is_published)
             VALUES (:title, :slug, :excerpt, :content, :image, :is_published)'
        );
        $stmt->execute([
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? '',
            'content' => $data['content'] ?? '',
            'image' => $data['image'] ?? '',
            'is_published' => !empty($data['is_published']) ? 1 : 0,
        ]);

        return (int) Database::getInstance()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $slug = $data['slug'] ?: Helpers::uniqueSlug('articles', $data['title'], $id);
        $stmt = Database::getInstance()->prepare(
            'UPDATE articles SET title = :title, slug = :slug, excerpt = :excerpt, content = :content,
             image = :image, is_published = :is_published, updated_at = datetime(\'now\') WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? '',
            'content' => $data['content'] ?? '',
            'image' => $data['image'] ?? '',
            'is_published' => !empty($data['is_published']) ? 1 : 0,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::getInstance()->prepare('DELETE FROM articles WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
