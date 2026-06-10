<?php

declare(strict_types=1);

/** @var string $siteName */
/** @var string $logoClass */
/** @var int $logoWidth */
$logoClass = $logoClass ?? 'site-logo-img';
$logoWidth = $logoWidth ?? 148;
?>
<img
    src="/assets/images/logo-photocom.png"
    alt="<?= Helpers::e($siteName) ?>"
    class="<?= Helpers::e($logoClass) ?>"
    width="<?= (int) $logoWidth ?>"
    height="auto"
    decoding="async"
>
