<?php

declare(strict_types=1);

/** Noscript GTM — à placer juste après <body>. */
$site = $site ?? SettingRepository::site();
$gtm = trim((string) ($site['analytics_gtm_id'] ?? ''));
$analyticsOn = ($site['analytics_enabled'] ?? '1') === '1';
$gtmOk = $analyticsOn && $gtm !== '' && preg_match('/^GTM-[A-Z0-9]+$/i', $gtm);

if (!$gtmOk) {
    return;
}
?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= Helpers::e($gtm) ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
