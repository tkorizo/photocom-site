<?php

declare(strict_types=1);

class Helpers
{
    public static function slugify(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');

        return $text !== '' ? $text : 'item-' . uniqid();
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    public static function formatPrice(?float $price): string
    {
        if ($price === null) {
            return '0,00 MAD';
        }

        return number_format($price, 2, ',', ' ') . ' MAD';
    }

    public static function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    public static function flash(string $key, ?string $message = null): ?string
    {
        Auth::startSession();

        if ($message !== null) {
            $_SESSION['flash'][$key] = $message;
            return null;
        }

        $value = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);

        return $value;
    }

    private static ?array $config = null;

    public static function config(): array
    {
        if (self::$config === null) {
            self::$config = require ROOT_PATH . '/config/app.php';
        }

        return self::$config;
    }

    public static function uniqueSlug(string $table, string $name, ?int $excludeId = null): string
    {
        $base = self::slugify($name);
        $slug = $base;
        $counter = 1;
        $db = Database::getInstance();

        while (true) {
            $sql = "SELECT id FROM {$table} WHERE slug = :slug";
            $params = ['slug' => $slug];

            if ($excludeId !== null) {
                $sql .= ' AND id != :id';
                $params['id'] = $excludeId;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            if (!$stmt->fetch()) {
                return $slug;
            }

            $slug = $base . '-' . $counter;
            $counter++;
        }
    }
}
