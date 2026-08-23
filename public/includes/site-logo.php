<?php

declare(strict_types=1);

/** @var string $siteName */
/** @var string $logoClass */
/** @var int $logoWidth */
/** @var string|null $siteLogo */
/** @var string|null $siteLogoMark */
/** @var string $logoVariant main|mark */

$logoClass = $logoClass ?? 'site-logo-img';
$logoWidth = $logoWidth ?? 148;
$logoVariant = $logoVariant ?? 'main';
$defaultMain = '/assets/images/logo-photocom.png';
$defaultMark = '/assets/images/logo-admin.webp';

if ($logoVariant === 'mark') {
    $logoSrc = trim((string) ($siteLogoMark ?? ''));
    if ($logoSrc === '') {
        $logoSrc = $defaultMark;
    }
} else {
    $logoSrc = trim((string) ($siteLogo ?? ''));
    if ($logoSrc === '') {
        $logoSrc = $defaultMain;
    }
}
?>
<img
    src="<?= Helpers::e($logoSrc) ?>"
    alt="<?= Helpers::e($siteName) ?>"
    class="<?= Helpers::e($logoClass) ?>"
    width="<?= (int) $logoWidth ?>"
    height="<?= (int) max(24, (int) round($logoWidth * 0.32)) ?>"
    decoding="async"
    <?= ($logoClass ?? '') !== 'header-logo-img' ? 'loading="lazy"' : '' ?>
>
<?php
$logoVariant = 'main';
?>
