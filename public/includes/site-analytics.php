<?php

declare(strict_types=1);

/**
 * Scripts Google Analytics 4 + Google Tag Manager (head).
 * Chargement différé pour réduire le TBT mobile.
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
<script>
(function () {
    var gtmId = <?= $gtmOk ? json_encode($gtm, JSON_UNESCAPED_UNICODE) : 'null' ?>;
    var ga4Id = <?= $ga4Ok ? json_encode($ga4, JSON_UNESCAPED_UNICODE) : 'null' ?>;

    function bootAnalytics() {
        if (gtmId) {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
            var f = document.getElementsByTagName('script')[0];
            var j = document.createElement('script');
            j.async = true;
            j.src = 'https://www.googletagmanager.com/gtm.js?id=' + gtmId;
            f.parentNode.insertBefore(j, f);
        }
        if (ga4Id) {
            var s = document.createElement('script');
            s.async = true;
            s.src = 'https://www.googletagmanager.com/gtag/js?id=' + ga4Id;
            document.head.appendChild(s);
            window.dataLayer = window.dataLayer || [];
            function gtag(){ dataLayer.push(arguments); }
            window.gtag = gtag;
            gtag('js', new Date());
            gtag('config', ga4Id);
        }
    }

    if ('requestIdleCallback' in window) {
        requestIdleCallback(bootAnalytics, { timeout: 3500 });
    } else {
        window.addEventListener('load', function () {
            setTimeout(bootAnalytics, 1200);
        });
    }
})();
</script>
