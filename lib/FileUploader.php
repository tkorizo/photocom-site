<?php

declare(strict_types=1);

class FileUploader
{
    private const MAX_SIZE_IMAGE = 5_242_880; // 5 Mo
    private const MAX_SIZE_VIDEO = 52_428_800; // 50 Mo
    private const ALLOWED_IMAGES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const ALLOWED_FAVICONS = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/x-icon',
        'image/vnd.microsoft.icon',
        'image/ico',
    ];
    private const ALLOWED_VIDEOS = ['video/mp4', 'video/webm', 'video/quicktime'];
    private const ALLOWED_FOLDERS = ['products', 'categories', 'home', 'brands', 'articles'];

    public static function processField(string $fieldName, string $folder, ?string $existingPath = null): ?string
    {
        return self::processUpload($fieldName, $folder, $existingPath, 'image');
    }

    public static function processMediaField(string $fieldName, string $folder, ?string $existingPath = null, string $kind = 'image'): ?string
    {
        return self::processUpload($fieldName, $folder, $existingPath, $kind);
    }

    private static function processUpload(string $fieldName, string $folder, ?string $existingPath, string $kind): ?string
    {
        $deleteKey = $fieldName . '_delete';
        $currentKey = $fieldName . '_current';
        $fileKey = $fieldName . '_file';

        if (!empty($_POST[$deleteKey])) {
            self::deleteFile($existingPath);
            return null;
        }

        if (!empty($_FILES[$fileKey]['name']) && ($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $newPath = self::upload($_FILES[$fileKey], $folder, $kind);
            if ($newPath && $existingPath && $existingPath !== $newPath) {
                self::deleteFile($existingPath);
            }
            return $newPath;
        }

        $current = trim($_POST[$currentKey] ?? '');
        if ($current !== '' && self::isLocalPath($current)) {
            return $current;
        }

        // Keep external URL if provided as current (hero remote video)
        if ($current !== '' && (str_starts_with($current, 'http://') || str_starts_with($current, 'https://'))) {
            return $current;
        }

        return $existingPath;
    }

    public static function upload(array $file, string $folder, string $kind = 'image'): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Erreur lors du téléversement du fichier.');
        }

        $maxSize = $kind === 'video' ? self::MAX_SIZE_VIDEO : self::MAX_SIZE_IMAGE;
        if (($file['size'] ?? 0) > $maxSize) {
            $limit = $kind === 'video' ? '50 Mo' : '5 Mo';
            throw new RuntimeException("Fichier trop volumineux (max {$limit}).");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowed = match ($kind) {
            'video' => self::ALLOWED_VIDEOS,
            'favicon' => self::ALLOWED_FAVICONS,
            default => self::ALLOWED_IMAGES,
        };
        if (!in_array($mime, $allowed, true)) {
            $msg = match ($kind) {
                'video' => 'Format vidéo non autorisé. Utilisez MP4 ou WebM.',
                'favicon' => 'Format favicon non autorisé. Utilisez PNG, ICO ou WebP.',
                default => 'Format non autorisé. Utilisez JPG, PNG, GIF ou WebP.',
            };
            throw new RuntimeException($msg);
        }

        if (!in_array($folder, self::ALLOWED_FOLDERS, true)) {
            throw new RuntimeException('Dossier d’upload non autorisé.');
        }

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'image/ico' => 'ico',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/quicktime' => 'mov',
        ];
        $ext = $extensions[$mime] ?? ($kind === 'video' ? 'mp4' : 'png');

        $baseDir = match ($folder) {
            'categories' => ImageDownloader::categoriesPath(),
            'home' => ImageDownloader::homePath(),
            'brands' => ImageDownloader::brandsPath(),
            'articles' => ImageDownloader::articlesPath(),
            default => ImageDownloader::productsPath(),
        };

        $filename = $folder . '-' . date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destination = $baseDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Impossible d\'enregistrer le fichier.');
        }

        return '/uploads/' . $folder . '/' . $filename;
    }

    public static function deleteFile(?string $path): void
    {
        if (!self::isLocalPath($path)) {
            return;
        }

        $fullPath = ROOT_PATH . $path;
        if (is_file($fullPath)) {
            unlink($fullPath);
        }
    }

    public static function isLocalPath(?string $path): bool
    {
        return is_string($path) && str_starts_with($path, '/uploads/');
    }
}
