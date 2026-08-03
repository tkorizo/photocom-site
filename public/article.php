<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/includes/init.php';

Database::initialize();

$slug = trim($_GET['slug'] ?? '');
$article = $slug !== '' ? ArticleRepository::findBySlug($slug) : null;

if (!$article || !(int) ($article['is_published'] ?? 0)) {
    http_response_code(404);
    $pageTitle = 'Article introuvable';
    $notFound = true;
} else {
    $pageTitle = $article['title'];
    $notFound = false;
}

$related = ArticleRepository::publishedRecent(4);
$related = array_values(array_filter(
    $related,
    static fn (array $row): bool => (int) ($row['id'] ?? 0) !== (int) ($article['id'] ?? 0)
));
$related = array_slice($related, 0, 3);
$activeNav = 'conseils';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $seoTitle = !$notFound
        ? (trim((string) ($article['meta_title'] ?? '')) ?: $article['title'])
        : $pageTitle;
    $seoDescription = !$notFound
        ? (trim((string) ($article['meta_description'] ?? '')) ?: trim((string) ($article['excerpt'] ?? '')))
        : '';
    $seoKeywords = !$notFound ? trim((string) ($article['meta_keywords'] ?? '')) : '';
    $seoImage = !$notFound ? trim((string) ($article['image'] ?? '')) : '';
    $seoCanonical = !$notFound ? trim((string) ($article['canonical_url'] ?? '')) : '';
    $seoType = 'article';
    $seoRobots = $notFound ? 'noindex,follow' : null;
    require __DIR__ . '/includes/site-seo.php';
    ?>
    <?php require __DIR__ . '/includes/site-favicon.php'; ?>
    <link rel="stylesheet" href="/assets/css/public.css">
    <link rel="stylesheet" href="/assets/css/chat.css">
</head>
<body class="page-blog page-article">
    <?php require __DIR__ . '/includes/site-header.php'; ?>

    <main>
        <?php if ($notFound): ?>
            <section class="home-section section-light">
                <div class="container page-content-body">
                    <h1>Article introuvable</h1>
                    <p>Cet article n’existe pas ou n’est plus publié.</p>
                    <p><a href="/conseils" class="btn btn-primary">Retour aux conseils</a></p>
                </div>
            </section>
        <?php else: ?>
            <article>
                <header class="article-hero">
                    <div class="container article-hero-inner">
                        <p class="article-breadcrumb">
                            <a href="/conseils">Conseils</a>
                            <span>/</span>
                            <span><?= Helpers::e($article['title']) ?></span>
                        </p>
                        <h1><?= Helpers::e($article['title']) ?></h1>
                        <div class="article-meta">
                            <time datetime="<?= Helpers::e(substr((string) ($article['created_at'] ?? ''), 0, 10)) ?>">
                                <?= Helpers::e(date('d/m/Y', strtotime((string) ($article['created_at'] ?? 'now')))) ?>
                            </time>
                        </div>
                        <?php if (!empty($article['excerpt'])): ?>
                            <p class="article-lead"><?= Helpers::e($article['excerpt']) ?></p>
                        <?php endif; ?>
                    </div>
                </header>

                <?php if (!empty($article['image'])): ?>
                    <div class="container article-cover">
                        <img src="<?= Helpers::e($article['image']) ?>" alt="<?= Helpers::e($article['title']) ?>">
                    </div>
                <?php endif; ?>

                <div class="home-section section-light">
                    <div class="container article-body page-content-body">
                        <?= $article['content'] ?: '<p>Contenu en cours de rédaction.</p>' ?>
                    </div>
                </div>
            </article>

            <?php if ($related !== []): ?>
                <section class="home-section section-muted">
                    <div class="container">
                        <header class="section-head">
                            <span class="section-label">À lire aussi</span>
                            <h2>Autres conseils</h2>
                        </header>
                        <div class="blog-grid blog-grid--related">
                            <?php foreach ($related as $item): ?>
                                <article class="blog-card">
                                    <a href="/article/<?= Helpers::e($item['slug']) ?>" class="blog-card-media">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="<?= Helpers::e($item['image']) ?>" alt="" loading="lazy">
                                        <?php else: ?>
                                            <span class="blog-card-ph"><?= Helpers::e(mb_strtoupper(mb_substr((string) $item['title'], 0, 1))) ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <div class="blog-card-body">
                                        <h2><a href="/article/<?= Helpers::e($item['slug']) ?>"><?= Helpers::e($item['title']) ?></a></h2>
                                        <a href="/article/<?= Helpers::e($item['slug']) ?>" class="blog-card-link">Lire</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php require __DIR__ . '/includes/site-footer.php'; ?>
    <?php require __DIR__ . '/includes/site-chat.php'; ?>
    <?php require __DIR__ . '/includes/site-cookies.php'; ?>
    <script src="/assets/js/mega-menu.js"></script>
    <script src="/assets/js/chat.js"></script>
</body>
</html>
