<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/includes/init.php';

$about = SettingRepository::about();
$isOn = static fn (string $key): bool => ($about[$key] ?? '0') === '1';
$pageTitle = $about['about_seo_title'] !== '' ? $about['about_seo_title'] : 'Qui sommes-nous';
$activeNav = 'about';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $seoTitle = $pageTitle;
    $seoDescription = $about['about_seo_description'];
    require __DIR__ . '/includes/site-seo.php';
    ?>
    <?php require __DIR__ . '/includes/site-favicon.php'; ?>
    <link rel="stylesheet" href="/assets/css/public.css">
    <link rel="stylesheet" href="/assets/css/chat.css">
</head>
<body class="page-about page-editorial">
    <?php require __DIR__ . '/includes/site-header.php'; ?>

    <main>
        <section class="editorial-hero">
            <div class="container">
                <div class="editorial-hero-copy">
                <?php if ($about['about_hero_label'] !== ''): ?>
                    <p class="section-label"><?= Helpers::e($about['about_hero_label']) ?></p>
                <?php endif; ?>
                <h1><?= Helpers::e($about['about_hero_title']) ?></h1>
                <?php if ($about['about_hero_lead'] !== ''): ?>
                    <p class="editorial-lead"><?= Helpers::e($about['about_hero_lead']) ?></p>
                <?php endif; ?>
                </div>
            </div>
        </section>

        <?php if ($isOn('about_story_enabled')): ?>
        <section class="editorial-section section-light">
            <div class="container about-intro">
                <div class="about-intro-text">
                    <?php if ($about['about_story_label'] !== ''): ?>
                        <p class="section-label"><?= Helpers::e($about['about_story_label']) ?></p>
                    <?php endif; ?>
                    <h2><?= Helpers::e($about['about_story_title']) ?></h2>
                    <?php foreach (['about_story_p1', 'about_story_p2', 'about_story_p3'] as $pk): ?>
                        <?php if (trim($about[$pk]) !== ''): ?>
                            <p><?= Helpers::e($about[$pk]) ?></p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <aside class="about-facts" aria-label="Points clés">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <?php
                        $fv = trim($about["about_fact_{$i}_value"]);
                        $fl = trim($about["about_fact_{$i}_label"]);
                        if ($fv === '' && $fl === '') {
                            continue;
                        }
                        ?>
                        <div class="about-fact">
                            <?php if ($fv !== ''): ?><strong><?= Helpers::e($fv) ?></strong><?php endif; ?>
                            <?php if ($fl !== ''): ?><span><?= Helpers::e($fl) ?></span><?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </aside>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($isOn('about_pillars_enabled')): ?>
        <section class="editorial-section section-muted">
            <div class="container">
                <header class="section-head section-align-center">
                    <?php if ($about['about_pillars_label'] !== ''): ?>
                        <span class="section-label"><?= Helpers::e($about['about_pillars_label']) ?></span>
                    <?php endif; ?>
                    <h2><?= Helpers::e($about['about_pillars_title']) ?></h2>
                </header>
                <div class="about-pillars">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <?php
                        $pt = trim($about["about_pillar_{$i}_title"]);
                        $px = trim($about["about_pillar_{$i}_text"]);
                        if ($pt === '' && $px === '') {
                            continue;
                        }
                        ?>
                        <article class="about-pillar">
                            <?php if ($pt !== ''): ?><h3><?= Helpers::e($pt) ?></h3><?php endif; ?>
                            <?php if ($px !== ''): ?><p><?= Helpers::e($px) ?></p><?php endif; ?>
                        </article>
                    <?php endfor; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($isOn('about_audience_enabled')): ?>
        <section class="editorial-section section-light">
            <div class="container about-audience">
                <header class="section-head">
                    <?php if ($about['about_audience_label'] !== ''): ?>
                        <span class="section-label"><?= Helpers::e($about['about_audience_label']) ?></span>
                    <?php endif; ?>
                    <h2><?= Helpers::e($about['about_audience_title']) ?></h2>
                </header>
                <ul class="about-audience-list">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <?php
                        $at = trim($about["about_audience_{$i}_title"]);
                        $ax = trim($about["about_audience_{$i}_text"]);
                        if ($at === '' && $ax === '') {
                            continue;
                        }
                        ?>
                        <li>
                            <?php if ($at !== ''): ?><strong><?= Helpers::e($at) ?></strong><?php endif; ?>
                            <?php if ($ax !== ''): ?><span><?= Helpers::e($ax) ?></span><?php endif; ?>
                        </li>
                    <?php endfor; ?>
                </ul>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($isOn('about_cta_enabled')): ?>
        <section class="editorial-section section-dark about-cta-band">
            <div class="container about-cta-inner">
                <div>
                    <?php if ($about['about_cta_label'] !== ''): ?>
                        <p class="section-label section-label-dark"><?= Helpers::e($about['about_cta_label']) ?></p>
                    <?php endif; ?>
                    <h2><?= Helpers::e($about['about_cta_title']) ?></h2>
                    <?php if ($about['about_cta_text'] !== ''): ?>
                        <p><?= Helpers::e($about['about_cta_text']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="about-cta-actions">
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                        <?php
                        $label = trim($about["about_cta_{$i}_label"]);
                        if ($label === '') {
                            continue;
                        }
                        $url = ContentPageAdmin::resolveCtaUrl($about["about_cta_{$i}_url"], (string) $whatsapp);
                        $external = str_starts_with($url, 'http');
                        $btnClass = $i === 1 ? 'btn btn-primary' : 'btn btn-outline-light';
                        ?>
                        <a href="<?= Helpers::e($url) ?>" class="<?= $btnClass ?>"<?= $external ? ' target="_blank" rel="noopener"' : '' ?>><?= Helpers::e($label) ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($isOn('about_find_enabled') || $isOn('about_links_enabled')): ?>
        <section class="editorial-section section-light">
            <div class="container about-contact-grid">
                <?php if ($isOn('about_find_enabled')): ?>
                <div>
                    <?php if ($about['about_find_label'] !== ''): ?>
                        <p class="section-label"><?= Helpers::e($about['about_find_label']) ?></p>
                    <?php endif; ?>
                    <h2><?= Helpers::e($about['about_find_title']) ?></h2>
                    <ul class="about-contact-list">
                        <?php if ($address !== ''): ?>
                            <li><strong>Adresse</strong><span><?= Helpers::e($address) ?></span></li>
                        <?php endif; ?>
                        <?php if ($phone !== ''): ?>
                            <li><strong>Téléphone</strong><a href="tel:<?= preg_replace('/\s+/', '', $phone) ?>"><?= Helpers::e($phone) ?></a></li>
                        <?php endif; ?>
                        <?php if ($email !== ''): ?>
                            <li><strong>Email</strong><a href="mailto:<?= Helpers::e($email) ?>"><?= Helpers::e($email) ?></a></li>
                        <?php endif; ?>
                        <?php if ($hours !== ''): ?>
                            <li><strong>Horaires</strong><span><?= Helpers::e($hours) ?></span></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if ($isOn('about_links_enabled')): ?>
                <div class="about-links">
                    <?php if ($about['about_links_label'] !== ''): ?>
                        <p class="section-label"><?= Helpers::e($about['about_links_label']) ?></p>
                    <?php endif; ?>
                    <h3><?= Helpers::e($about['about_links_title']) ?></h3>
                    <ul>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php
                            $ll = trim($about["about_link_{$i}_label"]);
                            $lu = trim($about["about_link_{$i}_url"]);
                            if ($ll === '' || $lu === '') {
                                continue;
                            }
                            ?>
                            <li><a href="<?= Helpers::e($lu) ?>"><?= Helpers::e($ll) ?></a></li>
                        <?php endfor; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <?php require __DIR__ . '/includes/site-footer.php'; ?>
    <?php require __DIR__ . '/includes/site-chat.php'; ?>
    <?php require __DIR__ . '/includes/site-cookies.php'; ?>
    <script src="/assets/js/mega-menu.js"></script>
    <script src="/assets/js/chat.js"></script>
</body>
</html>
