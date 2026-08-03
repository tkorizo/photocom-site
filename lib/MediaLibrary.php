<?php

declare(strict_types=1);

class MediaLibrary
{
    private const FOLDERS = ['products', 'categories', 'home', 'brands', 'articles'];
    private const SKIP_NAMES = ['.gitkeep', '.DS_Store', 'Thumbs.db'];
    private const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'];

    /** @return list<string> */
    public static function folders(): array
    {
        return self::FOLDERS;
    }

    public static function folderPath(string $folder): ?string
    {
        if (!in_array($folder, self::FOLDERS, true)) {
            return null;
        }

        return match ($folder) {
            'categories' => ImageDownloader::categoriesPath(),
            'home' => ImageDownloader::homePath(),
            'brands' => ImageDownloader::brandsPath(),
            default => ImageDownloader::productsPath(),
        };
    }

    public static function folderLabel(string $folder): string
    {
        return match ($folder) {
            'products' => 'Produits',
            'categories' => 'Catégories',
            'home' => 'Accueil',
            'brands' => 'Marques',
            default => $folder,
        };
    }

    /**
     * @return array{data: list<array{url: string, folder: string, name: string, size: int, modified: int, ext: string, is_image: bool}>, total: int, page: int, per_page: int, total_pages: int, total_size: int}
     */
    public static function paginate(?string $folder = null, ?string $search = null, int $page = 1, int $perPage = 40): array
    {
        $folders = $folder && in_array($folder, self::FOLDERS, true) ? [$folder] : self::FOLDERS;
        $search = $search !== null ? mb_strtolower(trim($search)) : '';
        $items = [];
        $totalSize = 0;

        foreach ($folders as $dir) {
            $base = self::folderPath($dir);
            if ($base === null || !is_dir($base)) {
                continue;
            }

            $files = scandir($base) ?: [];
            foreach ($files as $name) {
                if ($name === '.' || $name === '..' || in_array($name, self::SKIP_NAMES, true)) {
                    continue;
                }
                $full = $base . '/' . $name;
                if (!is_file($full)) {
                    continue;
                }
                if ($search !== '' && !str_contains(mb_strtolower($name), $search)) {
                    continue;
                }

                $size = (int) @filesize($full);
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $totalSize += $size;
                $items[] = [
                    'url' => '/uploads/' . $dir . '/' . $name,
                    'folder' => $dir,
                    'name' => $name,
                    'size' => $size,
                    'modified' => (int) @filemtime($full),
                    'ext' => $ext,
                    'is_image' => in_array($ext, self::IMAGE_EXT, true),
                ];
            }
        }

        usort($items, static fn (array $a, array $b): int => $b['modified'] <=> $a['modified']);

        $total = count($items);
        $perPage = max(10, min(100, $perPage));
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'data' => array_slice($items, $offset, $perPage),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'total_size' => $totalSize,
        ];
    }

    public static function formatSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' o';
        }
        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1, ',', ' ') . ' Ko';
        }

        return number_format($bytes / (1024 * 1024), 1, ',', ' ') . ' Mo';
    }

    public static function deleteByUrl(string $url): bool
    {
        if (!FileUploader::isLocalPath($url)) {
            return false;
        }

        $parts = explode('/', trim($url, '/'));
        if (count($parts) < 3 || $parts[0] !== 'uploads') {
            return false;
        }

        $folder = $parts[1];
        $name = $parts[2];
        if (!in_array($folder, self::FOLDERS, true)) {
            return false;
        }
        if ($name === '' || str_contains($name, '..') || str_contains($name, '/') || str_contains($name, '\\')) {
            return false;
        }

        $base = self::folderPath($folder);
        if ($base === null) {
            return false;
        }

        $full = $base . '/' . $name;
        $realBase = realpath($base);
        $realFile = realpath($full);
        if ($realBase === false || $realFile === false || !str_starts_with($realFile, $realBase)) {
            return false;
        }
        if (!is_file($realFile)) {
            return false;
        }

        return unlink($realFile);
    }

    public static function upload(array $file, string $folder): string
    {
        if (!in_array($folder, self::FOLDERS, true)) {
            throw new RuntimeException('Dossier non autorisé.');
        }

        $mime = '';
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo && is_uploaded_file($file['tmp_name'] ?? '')) {
            $mime = (string) finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }

        $kind = 'image';
        if (str_starts_with($mime, 'video/')) {
            $kind = 'video';
        } elseif (in_array($mime, ['image/x-icon', 'image/vnd.microsoft.icon', 'image/ico'], true)) {
            $kind = 'favicon';
        }

        $path = FileUploader::upload($file, $folder, $kind);
        if (!$path) {
            throw new RuntimeException('Échec du téléversement.');
        }

        return $path;
    }
}
