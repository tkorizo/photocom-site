<?php

declare(strict_types=1);

/** @var array $cookieSettings */
$cookieSettings = $cookieSettings ?? SettingRepository::cookies();
if (($cookieSettings['cookies_enabled'] ?? '0') !== '1') {
    return;
}
?>
<div
    class="cookie-banner"
    id="cookie-banner"
    hidden
    role="dialog"
    aria-modal="true"
    aria-labelledby="cookie-banner-title"
    data-cookie-banner
>
    <div class="cookie-banner-inner">
        <div class="cookie-banner-copy">
            <strong id="cookie-banner-title"><?= Helpers::e($cookieSettings['cookies_title']) ?></strong>
            <p><?= Helpers::e($cookieSettings['cookies_message']) ?></p>
        </div>
        <div class="cookie-banner-actions">
            <button type="button" class="btn btn-primary" data-cookie-accept><?= Helpers::e($cookieSettings['cookies_accept_label']) ?></button>
            <button type="button" class="btn btn-outline-light" data-cookie-refuse><?= Helpers::e($cookieSettings['cookies_refuse_label']) ?></button>
            <a class="cookie-banner-link" href="<?= Helpers::e($cookieSettings['cookies_policy_url']) ?>"><?= Helpers::e($cookieSettings['cookies_more_label']) ?></a>
        </div>
    </div>
</div>
<script>
window.__COOKIE_BANNER__ = {
    storageKey: 'photocom_cookie_consent'
};
</script>
<script src="/assets/js/cookies.js" defer></script>
