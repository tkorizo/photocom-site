<?php

declare(strict_types=1);

class ImageDownloader
{
    public static function productsPath(): string
    {
        return rtrim(Helpers::config()['uploads_path'], '/');
    }

    public static function categoriesPath(): string
    {
        $path = dirname(self::productsPath()) . '/categories';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    public static function isRemoteUrl(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }

    public static function isLocalPath(?string $value): bool
    {
        return is_string($value) && str_starts_with($value, '/uploads/');
    }

    public static function download(string $url, string $folder, string $filename): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (self::isLocalPath($url)) {
            return $url;
        }

        $baseDir = $folder === 'categories' ? self::categoriesPath() : self::productsPath();
        $extension = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'jpg';
        $extension = preg_replace('/[^a-zA-Z0-9]/', '', $extension) ?: 'jpg';
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '-', $filename) ?: 'image';
        $file = $safeName . '.' . strtolower($extension);
        $destination = $baseDir . '/' . $file;

        if (is_file($destination) && filesize($destination) > 0) {
            return '/uploads/' . $folder . '/' . $file;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'PHOTOCOM-Importer/1.0',
        ]);

        $content = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($content === false || $httpCode !== 200 || strlen($content) < 100) {
            return null;
        }

        if (file_put_contents($destination, $content) === false) {
            return null;
        }

        return '/uploads/' . $folder . '/' . $file;
    }

    public static function localizeProductImages(array $product): array
    {
        $wpId = (int) ($product['wordpress_id'] ?? $product['id']);
        $updates = [];

        if (self::isRemoteUrl($product['image'] ?? null)) {
            $local = self::download((string) $product['image'], 'products', 'wp-' . $wpId . '-main');
            if ($local) {
                $updates['image'] = $local;
            }
        }

        $secondary = ProductRepository::getSecondaryImages($product);
        $newSecondary = [];
        $changedSecondary = false;

        foreach ($secondary as $i => $url) {
            if (self::isRemoteUrl($url)) {
                $local = self::download($url, 'products', 'wp-' . $wpId . '-sec-' . ($i + 1));
                $newSecondary[] = $local ?? $url;
                if ($local) {
                    $changedSecondary = true;
                }
            } else {
                $newSecondary[] = $url;
            }
        }

        if ($changedSecondary) {
            $updates['images_secondary_json'] = json_encode($newSecondary, JSON_UNESCAPED_UNICODE);
        }

        $allJson = $product['images_json'] ?? '[]';
        $allImages = json_decode($allJson, true);
        if (is_array($allImages)) {
            $newAll = [];
            $changedAll = false;
            foreach ($allImages as $i => $url) {
                if (self::isRemoteUrl($url)) {
                    $suffix = $i === 0 ? 'main' : 'sec-' . $i;
                    $local = self::download($url, 'products', 'wp-' . $wpId . '-' . $suffix);
                    $newAll[] = $local ?? $url;
                    if ($local) {
                        $changedAll = true;
                    }
                } else {
                    $newAll[] = $url;
                }
            }
            if ($changedAll) {
                $updates['images_json'] = json_encode($newAll, JSON_UNESCAPED_UNICODE);
                if (!isset($updates['image']) && !empty($newAll[0]) && self::isLocalPath($newAll[0])) {
                    $updates['image'] = $newAll[0];
                }
            }
        }

        return $updates;
    }

    public static function localizeCategoryImages(array $category): array
    {
        $wpId = (int) ($category['wordpress_id'] ?? $category['id']);
        $updates = [];
        $fields = [
            'thumbnail' => 'thumb',
            'category_icon' => 'icon',
            'large_category_icon' => 'icon-lg',
            'title_background' => 'bg',
        ];

        foreach ($fields as $field => $suffix) {
            $value = $category[$field] ?? null;
            if (self::isRemoteUrl($value)) {
                $local = self::download((string) $value, 'categories', 'wp-cat-' . $wpId . '-' . $suffix);
                if ($local) {
                    $updates[$field] = $local;
                }
            }
        }

        return $updates;
    }
}
