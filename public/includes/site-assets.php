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

$publicCss = is_file(dirname(__DIR__) . '/assets/css/public.min.css')
    ? '/assets/css/public.min.css'
    : '/assets/css/public.css';
?>
<link rel="preload" href="<?= Helpers::e($assetVer('/assets/fonts/roboto-400-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= Helpers::e($assetVer('/assets/css/fonts.css')) ?>">
<link rel="stylesheet" href="<?= Helpers::e($assetVer($publicCss)) ?>">
<link rel="stylesheet" href="<?= Helpers::e($assetVer('/assets/css/chat.css')) ?>" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="<?= Helpers::e($assetVer('/assets/css/chat.css')) ?>"></noscript>
