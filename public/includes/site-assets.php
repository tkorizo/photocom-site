<?php

declare(strict_types=1);

/**
 * CSS + polices pour les pages publiques (chemin critique mobile).
 */
$assetVer = static function (string $relativePath): string {
    $full = dirname(__DIR__) . $relativePath;
    $mtime = is_file($full) ? (string) filemtime($full) : (string) time();
    return $relativePath . '?v=' . $mtime;
};
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
<link rel="stylesheet" href="<?= Helpers::e($assetVer('/assets/css/public.css')) ?>">
<link rel="stylesheet" href="<?= Helpers::e($assetVer('/assets/css/chat.css')) ?>" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="<?= Helpers::e($assetVer('/assets/css/chat.css')) ?>"></noscript>
