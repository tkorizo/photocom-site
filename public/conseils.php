<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/includes/init.php';

Database::initialize();
ArticleRepository::seedDefaults();

$cp = SettingRepository::conseilsPage();
$isOn = static fn (string $key): bool => ($cp[$key] ?? '0') === '1';

$page = max(1, (int) ($_GET['page'] ?? 1));
$result = ArticleRepository::paginatePublished($page, 9);
$articles = $result['data'];
$pageTitle = $cp['conseils_page_seo_title'] !== '' ? $cp['conseils_page_seo_title'] : 'Conseils';
$activeNav = 'conseils';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $seoTitle = $pageTitle;
    $seoDescription = $cp['conseils_page_seo_description'];
    $seoRobots = 'index,follow';
    require __DIR__ . '/includes/site-seo.php';
    ?>
    <?php require __DIR__ . '/includes/site-favicon.php'; ?>
    <?php require __DIR__ . '/includes/site-assets.php'; ?>
</head>
<body class="page-blog page-editorial">
    <?php require __DIR__ . '/includes/site-header.php'; ?>

    <main>
        <section class="editorial-hero">
            <div class="container">
                <div class="editorial-hero-copy">
                <?php if ($cp['conseils_page_hero_label'] !== ''): ?>
                    <p class="section-label"><?= Helpers::e($cp['conseils_page_hero_label']) ?></p>
                <?php endif; ?>
                <h1><?= Helpers::e($cp['conseils_page_hero_title']) ?></h1>
                <?php if ($cp['conseils_page_hero_lead'] !== ''): ?>
                    <p class="editorial-lead"><?= Helpers::e($cp['conseils_page_hero_lead']) ?></p>
                <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="editorial-section section-light">
            <div class="container">
                <?php if ($articles === []): ?>
                    <p class="blog-empty editorial-empty"><?= Helpers::e($cp['conseils_page_empty_text']) ?></p>
                <?php else: ?>
                    <div class="blog-grid">
                        <?php foreach ($articles as $index => $article): ?>
                            <?php
                            $href = '/article/' . rawurlencode((string) $article['slug']);
                            $isFeatured = $index === 0 && $result['page'] === 1;
                            ?>
                            <article class="blog-card<?= $isFeatured ? ' blog-card--featured' : '' ?>">
                                <a href="<?= Helpers::e($href) ?>" class="blog-card-media">
                                    <?php if (!empty($article['image'])): ?>
                                        <img src="<?= Helpers::e($article['image']) ?>" alt="<?= Helpers::e($article['title']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <span class="blog-card-ph"><?= Helpers::e(mb_strtoupper(mb_substr((string) $article['title'], 0, 1))) ?></span>
                                    <?php endif; ?>
                                </a>
                                <div class="blog-card-body">
                                    <time datetime="<?= Helpers::e(substr((string) ($article['created_at'] ?? ''), 0, 10)) ?>">
                                        <?= Helpers::e(date('d/m/Y', strtotime((string) ($article['created_at'] ?? 'now')))) ?>
                                    </time>
                                    <h2><a href="<?= Helpers::e($href) ?>"><?= Helpers::e($article['title']) ?></a></h2>
                                    <?php if (!empty($article['excerpt'])): ?>
                                        <p><?= Helpers::e($article['excerpt']) ?></p>
                                    <?php endif; ?>
                                    <a href="<?= Helpers::e($href) ?>" class="blog-card-link">Lire l’article</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($result['total_pages'] > 1): ?>
                        <div class="pagination blog-pagination">
                            <?php for ($i = 1; $i <= $result['total_pages']; $i++): ?>
                                <a href="?page=<?= $i ?>" class="<?= $i === $result['page'] ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($isOn('conseils_page_cta_enabled')): ?>
        <section class="editorial-section section-dark about-cta-band">
            <div class="container about-cta-inner">
                <div>
                    <?php if ($cp['conseils_page_cta_label'] !== ''): ?>
                        <p class="section-label section-label-dark"><?= Helpers::e($cp['conseils_page_cta_label']) ?></p>
                    <?php endif; ?>
                    <h2><?= Helpers::e($cp['conseils_page_cta_title']) ?></h2>
                    <?php if ($cp['conseils_page_cta_text'] !== ''): ?>
                        <p><?= Helpers::e($cp['conseils_page_cta_text']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="about-cta-actions">
                    <?php if ($cp['conseils_page_cta_btn_label'] !== ''): ?>
                        <a href="<?= Helpers::e($cp['conseils_page_cta_btn_url'] ?: '/contact') ?>" class="btn btn-primary"><?= Helpers::e($cp['conseils_page_cta_btn_label']) ?></a>
                    <?php endif; ?>
                    <?php if ($cp['conseils_page_cta_secondary_label'] !== ''): ?>
                        <a href="<?= Helpers::e($cp['conseils_page_cta_secondary_url'] ?: '/catalogue.php') ?>" class="btn btn-outline-light"><?= Helpers::e($cp['conseils_page_cta_secondary_label']) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <?php require __DIR__ . '/includes/site-footer.php'; ?>
    <?php require __DIR__ . '/includes/site-chat.php'; ?>
    <?php require __DIR__ . '/includes/site-cookies.php'; ?>
    <?php require __DIR__ . '/includes/site-end-scripts.php'; ?>
</body>
</html>
