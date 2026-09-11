<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/includes/init.php';

$contact = SettingRepository::contactPage();
$isOn = static fn (string $key): bool => ($contact[$key] ?? '0') === '1';
$pageTitle = $contact['contact_seo_title'] !== '' ? $contact['contact_seo_title'] : 'Contact';
$activeNav = 'contact';
$waDigits = preg_replace('/\D+/', '', (string) $whatsapp) ?: (string) $whatsapp;
$waBase = 'https://wa.me/' . $waDigits;
$waDisplay = Helpers::formatPhoneDisplay((string) $whatsapp);
$waIcon = '<svg class="contact-wa-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $seoTitle = $pageTitle;
    $seoDescription = $contact['contact_seo_description'];
    $seoRobots = 'index,follow';
    require __DIR__ . '/includes/site-seo.php';
    ?>
    <?php require __DIR__ . '/includes/site-favicon.php'; ?>
    <?php require __DIR__ . '/includes/site-assets.php'; ?>
</head>
<body class="page-contact page-editorial">
    <?php require __DIR__ . '/includes/site-header.php'; ?>

    <main>
        <section class="editorial-hero">
            <div class="container">
                <div class="editorial-hero-copy">
                <?php if ($contact['contact_hero_label'] !== ''): ?>
                    <p class="section-label"><?= Helpers::e($contact['contact_hero_label']) ?></p>
                <?php endif; ?>
                <h1><?= Helpers::e($contact['contact_hero_title']) ?></h1>
                <?php if ($contact['contact_hero_lead'] !== ''): ?>
                    <p class="editorial-lead"><?= Helpers::e($contact['contact_hero_lead']) ?></p>
                <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="editorial-section section-light">
            <div class="container contact-layout">
                <div class="contact-main">
                    <?php if ($isOn('contact_intro_enabled')): ?>
                        <header class="section-head">
                            <?php if ($contact['contact_intro_label'] !== ''): ?>
                                <span class="section-label"><?= Helpers::e($contact['contact_intro_label']) ?></span>
                            <?php endif; ?>
                            <h2><?= Helpers::e($contact['contact_intro_title']) ?></h2>
                            <?php if ($contact['contact_intro_text'] !== ''): ?>
                                <p class="section-sub"><?= Helpers::e($contact['contact_intro_text']) ?></p>
                            <?php endif; ?>
                        </header>
                    <?php endif; ?>

                    <?php if ($isOn('contact_whatsapp_enabled')): ?>
                        <div class="contact-panel contact-panel-accent">
                            <h3 class="contact-wa-heading"><?= $waIcon ?><?= Helpers::e($contact['contact_whatsapp_title']) ?></h3>
                            <?php if ($waDisplay !== ''): ?>
                                <p class="contact-wa-number"><a href="<?= Helpers::e($waBase) ?>" target="_blank" rel="noopener"><?= Helpers::e($waDisplay) ?></a></p>
                            <?php endif; ?>
                            <?php if ($contact['contact_whatsapp_text'] !== ''): ?>
                                <p><?= Helpers::e($contact['contact_whatsapp_text']) ?></p>
                            <?php endif; ?>

                            <form class="contact-wa-form" id="contact-wa-form" data-wa="<?= Helpers::e($waBase) ?>" data-prefix="<?= Helpers::e($contact['contact_whatsapp_prefiller']) ?>">
                                <label for="contact-message"><?= Helpers::e($contact['contact_form_title']) ?></label>
                                <textarea id="contact-message" name="message" rows="5" placeholder="<?= Helpers::e($contact['contact_form_placeholder']) ?>" required></textarea>
                                <div class="contact-wa-actions">
                                    <button type="submit" class="btn btn-primary"><?= Helpers::e($contact['contact_form_button']) ?></button>
                                    <a href="<?= Helpers::e($waBase) ?>" class="btn btn-outline" target="_blank" rel="noopener"><?= Helpers::e($contact['contact_whatsapp_label']) ?></a>
                                    <?php if ($contact['contact_cta_catalogue_label'] !== ''): ?>
                                        <a href="<?= Helpers::e($contact['contact_cta_catalogue_url'] ?: '/catalogue.php') ?>" class="btn btn-outline"><?= Helpers::e($contact['contact_cta_catalogue_label']) ?></a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <aside class="contact-aside">
                    <?php if ($isOn('contact_info_enabled')): ?>
                        <div class="contact-panel">
                            <?php if ($contact['contact_info_label'] !== ''): ?>
                                <p class="section-label"><?= Helpers::e($contact['contact_info_label']) ?></p>
                            <?php endif; ?>
                            <h2><?= Helpers::e($contact['contact_info_title']) ?></h2>
                            <ul class="contact-info-list">
                                <?php if ($address !== ''): ?>
                                    <li>
                                        <strong>Adresse</strong>
                                        <span><?= Helpers::e($address) ?></span>
                                    </li>
                                <?php endif; ?>
                                <?php if ($phone !== ''): ?>
                                    <li>
                                        <strong>Téléphone</strong>
                                        <a href="tel:<?= preg_replace('/\s+/', '', $phone) ?>"><?= Helpers::e($phone) ?></a>
                                    </li>
                                <?php endif; ?>
                                <?php if ($email !== ''): ?>
                                    <li>
                                        <strong>Email</strong>
                                        <a href="mailto:<?= Helpers::e($email) ?>"><?= Helpers::e($email) ?></a>
                                    </li>
                                <?php endif; ?>
                                <?php if ($hours !== ''): ?>
                                    <li>
                                        <strong>Horaires</strong>
                                        <span class="hours-multiline"><?= Helpers::multiline($hours) ?></span>
                                    </li>
                                <?php endif; ?>
                                <?php if ($whatsapp !== ''): ?>
                                    <li>
                                        <strong class="contact-wa-label"><?= $waIcon ?>WhatsApp</strong>
                                        <a href="<?= Helpers::e($waBase) ?>" target="_blank" rel="noopener"><?= Helpers::e($waDisplay) ?></a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                            <?php if ($isOn('contact_hours_enabled') && $contact['contact_hours_note'] !== ''): ?>
                                <p class="contact-note"><?= Helpers::e($contact['contact_hours_note']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </aside>
            </div>
        </section>

        <?php if ($isOn('contact_map_enabled') && trim($contact['contact_map_embed']) !== ''): ?>
        <section class="editorial-section section-muted">
            <div class="container">
                <?php if ($contact['contact_map_title'] !== ''): ?>
                    <header class="section-head">
                        <h2><?= Helpers::e($contact['contact_map_title']) ?></h2>
                    </header>
                <?php endif; ?>
                <div class="contact-map">
                    <?php
                    $embed = (string) $contact['contact_map_embed'];
                    if (preg_match('/<iframe\b[^>]*>.*?<\/iframe>/is', $embed, $m)) {
                        echo $m[0];
                    }
                    ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <?php require __DIR__ . '/includes/site-footer.php'; ?>
    <?php require __DIR__ . '/includes/site-chat.php'; ?>
    <?php require __DIR__ . '/includes/site-cookies.php'; ?>
    <?php require __DIR__ . '/includes/site-end-scripts.php'; ?>
    <script>
    (function () {
        var form = document.getElementById('contact-wa-form');
        if (!form) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var base = form.getAttribute('data-wa') || '';
            var prefix = form.getAttribute('data-prefix') || '';
            var msg = (form.querySelector('textarea') || {}).value || '';
            var text = (prefix + ' ' + msg).trim();
            window.open(base + '?text=' + encodeURIComponent(text), '_blank', 'noopener');
        });
    })();
    </script>
</body>
</html>
