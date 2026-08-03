<?php

declare(strict_types=1);

/** @var string|null $siteFavicon */
$faviconSrc = trim((string) ($siteFavicon ?? ''));
if ($faviconSrc === '') {
    return;
}

$ext = strtolower(pathinfo($faviconSrc, PATHINFO_EXTENSION));
$type = match ($ext) {
    'svg' => 'image/svg+xml',
    'png' => 'image/png',
    'jpg', 'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'ico' => 'image/x-icon',
    default => null,
};
?>
<link rel="icon" href="<?= Helpers::e($faviconSrc) ?>"<?= $type ? ' type="' . Helpers::e($type) . '"' : '' ?>>
<link rel="apple-touch-icon" href="<?= Helpers::e($faviconSrc) ?>">
