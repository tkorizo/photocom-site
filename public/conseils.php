<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/includes/init.php';

Database::initialize();
ArticleRepository::seedDefaults();

$page = max(1, (int) ($_GET['page'] ?? 1));
$result = ArticleRepository::paginatePublished($page, 9);
$articles = $result['data'];
$pageTitle = 'Conseils';
$activeNav = 'conseils';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $seoTitle = $pageTitle;
    $seoDescription = 'Conseils photo et vidéo par PHOTOCOM : éclairage, objectifs, studio et bonnes pratiques pour réussir vos projets.';
    require __DIR__ . '/includes/site-seo.php';
    ?>
    <?php require __DIR__ . '/includes/site-favicon.php'; ?>
    <link rel="stylesheet" href="/assets/css/public.css">
    <link rel="stylesheet" href="/assets/css/chat.css">
</head>
<body class="page-blog">
    <?php require __DIR__ . '/includes/site-header.php'; ?>

    <main>
        <section class="blog-hero">
            <div class="container blog-hero-inner">
                <p class="section-label">Blog</p>
                <h1>Conseils photo &amp; vidéo</h1>
                <p class="blog-hero-lead">Guides pratiques et retours d’expérience pour choisir, utiliser et faire évoluer votre matériel.</p>
            </div>
        </section>

        <section class="home-section section-light">
            <div class="container">
                <?php if ($articles === []): ?>
                    <p class="blog-empty">Aucun article pour le moment. Revenez bientôt.</p>
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
    </main>

    <?php require __DIR__ . '/includes/site-footer.php'; ?>
    <?php require __DIR__ . '/includes/site-chat.php'; ?>
    <?php require __DIR__ . '/includes/site-cookies.php'; ?>
    <script src="/assets/js/mega-menu.js"></script>
    <script src="/assets/js/chat.js"></script>
</body>
</html>
