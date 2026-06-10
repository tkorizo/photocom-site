<?php

declare(strict_types=1);

class FileUploader
{
    private const MAX_SIZE = 5_242_880; // 5 Mo
    private const ALLOWED = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public static function processField(string $fieldName, string $folder, ?string $existingPath = null): ?string
    {
        $deleteKey = $fieldName . '_delete';
        $currentKey = $fieldName . '_current';
        $fileKey = $fieldName . '_file';

        if (!empty($_POST[$deleteKey])) {
            self::deleteFile($existingPath);
            return null;
        }

        if (!empty($_FILES[$fileKey]['name']) && ($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $newPath = self::upload($_FILES[$fileKey], $folder);
            if ($newPath && $existingPath && $existingPath !== $newPath) {
                self::deleteFile($existingPath);
            }
            return $newPath;
        }

        $current = trim($_POST[$currentKey] ?? '');
        if ($current !== '' && self::isLocalPath($current)) {
            return $current;
        }

        return $existingPath;
    }

    public static function upload(array $file, string $folder): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Erreur lors du téléversement du fichier.');
        }

        if (($file['size'] ?? 0) > self::MAX_SIZE) {
            throw new RuntimeException('Fichier trop volumineux (max 5 Mo).');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
        if ($finfo) {
            finfo_close($finfo);
        }

        if (!in_array($mime, self::ALLOWED, true)) {
            throw new RuntimeException('Format non autorisé. Utilisez JPG, PNG, GIF ou WebP.');
        }

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        $ext = $extensions[$mime] ?? 'jpg';

        $baseDir = $folder === 'categories'
            ? ImageDownloader::categoriesPath()
            : ImageDownloader::productsPath();

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
