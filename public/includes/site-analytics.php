<?php

declare(strict_types=1);

/**
 * Scripts Google Analytics 4 + Google Tag Manager (head).
 * Variables : $site (optionnel, sinon chargé).
 */

$site = $site ?? SettingRepository::site();
$ga4 = trim((string) ($site['analytics_ga4_id'] ?? ''));
$gtm = trim((string) ($site['analytics_gtm_id'] ?? ''));
$analyticsOn = ($site['analytics_enabled'] ?? '1') === '1';

if (!$analyticsOn) {
    return;
}

// Validation basique des IDs
$ga4Ok = $ga4 !== '' && preg_match('/^G-[A-Z0-9]+$/i', $ga4);
$gtmOk = $gtm !== '' && preg_match('/^GTM-[A-Z0-9]+$/i', $gtm);

if (!$ga4Ok && !$gtmOk) {
    return;
}
?>
<?php if ($gtmOk): ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?= Helpers::e($gtm) ?>');</script>
<!-- End Google Tag Manager -->
<?php endif; ?>
<?php if ($ga4Ok): ?>
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= Helpers::e($ga4) ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '<?= Helpers::e($ga4) ?>');
</script>
<!-- End Google Analytics 4 -->
<?php endif; ?>
