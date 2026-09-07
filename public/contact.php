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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $seoTitle = $pageTitle;
    $seoDescription = $contact['contact_seo_description'];
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
                            <h3><?= Helpers::e($contact['contact_whatsapp_title']) ?></h3>
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
                                        <strong>WhatsApp</strong>
                                        <a href="<?= Helpers::e($waBase) ?>" target="_blank" rel="noopener"><?= Helpers::e($phone ?: $whatsapp) ?></a>
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
