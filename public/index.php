<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/includes/init.php';

$featuredProducts = [];
$bestsellers = [];
$blogArticles = [];
$brandList = [];

try {
    Database::initialize();
    $productsCount = ProductRepository::countActive();
    $featuredProducts = ProductRepository::featured(2);
    if (count($featuredProducts) < 2) {
        $extra = ProductRepository::recent(2 - count($featuredProducts));
        $featuredProducts = array_merge($featuredProducts, $extra);
    }
    $bestsellers = ProductRepository::bestsellers(8);
    $blogArticles = ArticleRepository::publishedRecent(2);
    $brandList = ProductRepository::publicFilterMeta(null)['brands'] ?? [];
} catch (Throwable) {
    $productsCount = 0;
    $menuTree = $menuTree ?? [];
    $brandList = [];
}

if (!empty($blogArticles)) {
    foreach ($blogArticles as $i => $article) {
        if (isset($blogVideos[$i])) {
            $blogVideos[$i]['title'] = $article['title'];
            if ($article['excerpt']) {
                $blogVideos[$i]['excerpt'] = $article['excerpt'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= Helpers::e($siteName) ?> — Boutique photo & vidéo professionnelle à Casablanca.">
    <title><?= Helpers::e($siteName) ?> — Matériel Photo & Vidéo</title>
    <link rel="stylesheet" href="/assets/css/public.css">
    <link rel="stylesheet" href="/assets/css/sidebar.css">
    <link rel="stylesheet" href="/assets/css/chat.css">
</head>
<body class="page-home has-sidebar">
    <a href="#main" class="skip-link">Aller au contenu principal</a>

    <?php require __DIR__ . '/includes/site-sidebar.php'; ?>

    <div class="site-main">
    <main id="main">
        <section class="hero-video" aria-label="Présentation">
            <div class="hero-video-wrap">
                <?php if ($heroVideoUrl): ?>
                    <video class="hero-video-el" autoplay muted loop playsinline poster="<?= Helpers::e($heroVideoPoster) ?>">
                        <source src="<?= Helpers::e($heroVideoUrl) ?>" type="video/mp4">
                    </video>
                <?php else: ?>
                    <div class="hero-video-fallback">
                        <div class="hero-video-fallback-inner">
                            <span class="hero-kicker">Expert photo & vidéo</span>
                            <h1>Matériel professionnel au Maroc</h1>
                            <p><?= Helpers::e($tagline) ?></p>
                            <a href="/catalogue.php" class="btn btn-primary">Découvrir le catalogue</a>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="hero-video-overlay"></div>
                <?php if ($heroVideoUrl): ?>
                    <div class="hero-video-caption container">
                        <span class="hero-kicker">Expert photo & vidéo</span>
                        <h1>Matériel professionnel au Maroc</h1>
                        <a href="/catalogue.php" class="btn btn-primary">Découvrir le catalogue</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if (!empty($featuredProducts)): ?>
        <section class="home-section section-light" id="vedettes">
            <div class="container">
                <header class="section-head">
                    <span class="section-label">Sélection</span>
                    <h2>Produits en vedette</h2>
                </header>
                <div class="featured-grid">
                    <?php foreach (array_slice($featuredProducts, 0, 2) as $product): ?>
                        <article class="featured-card">
                            <a href="/catalogue.php?q=<?= urlencode($product['name']) ?>" class="featured-card-media">
                                <?php if ($product['image']): ?>
                                    <img src="<?= Helpers::e($product['image']) ?>" alt="<?= Helpers::e($product['name']) ?>" loading="lazy">
                                <?php else: ?>
                                    <span class="product-placeholder">PC</span>
                                <?php endif; ?>
                            </a>
                            <div class="featured-card-body">
                                <?php if ($product['brand']): ?>
                                    <span class="product-brand"><?= Helpers::e($product['brand']) ?></span>
                                <?php endif; ?>
                                <h3><a href="/catalogue.php?q=<?= urlencode($product['name']) ?>"><?= Helpers::e($product['name']) ?></a></h3>
                                <?php if ($product['short_description']): ?>
                                    <p><?= Helpers::e(mb_strimwidth(strip_tags($product['short_description']), 0, 120, '…')) ?></p>
                                <?php endif; ?>
                                <div class="featured-card-foot">
                                    <span class="product-price"><?= Helpers::formatPrice((float) $product['price']) ?></span>
                                    <a href="https://wa.me/<?= Helpers::e($whatsapp) ?>?text=<?= urlencode('Bonjour, je souhaite des infos sur : ' . $product['name']) ?>" class="btn btn-primary btn-sm" target="_blank" rel="noopener">Commander</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <section class="home-section section-dark" id="conseils">
            <div class="container">
                <header class="section-head">
                    <span class="section-label">Conseils</span>
                    <h2>Nos experts vous guident</h2>
                    <p class="section-sub">Tutoriels et astuces pour tirer le meilleur de votre matériel.</p>
                </header>
                <div class="blog-videos-grid">
                    <?php foreach ($blogVideos as $video): ?>
                        <article class="blog-video-card">
                            <div class="blog-video-frame">
                                <iframe src="<?= Helpers::e($video['url']) ?>" title="<?= Helpers::e($video['title']) ?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                            <div class="blog-video-body">
                                <h3><?= Helpers::e($video['title']) ?></h3>
                                <?php if (!empty($video['excerpt'])): ?>
                                    <p><?= Helpers::e($video['excerpt']) ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php if (!empty($brandList)): ?>
        <section class="home-section section-light" id="marques">
            <div class="container">
                <header class="section-head">
                    <span class="section-label">Marques</span>
                    <h2>Parcourir par marque</h2>
                </header>
                <div class="brand-blocks">
                    <?php foreach ($brandList as $brand): ?>
                        <a href="/catalogue.php?brand=<?= urlencode($brand) ?>" class="brand-block">
                            <h3><?= Helpers::e($brand) ?></h3>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($bestsellers)): ?>
        <section class="home-section section-muted" id="meilleures-ventes">
            <div class="container">
                <header class="section-head">
                    <span class="section-label">Top ventes</span>
                    <h2>Meilleures ventes</h2>
                </header>
                <div class="products-grid products-grid-compact">
                    <?php foreach ($bestsellers as $product): ?>
                        <article class="product-card product-card-minimal">
                            <a href="/catalogue.php?q=<?= urlencode($product['name']) ?>" class="product-card-image">
                                <?php if ($product['image']): ?>
                                    <img src="<?= Helpers::e($product['image']) ?>" alt="<?= Helpers::e($product['name']) ?>" loading="lazy">
                                <?php else: ?>
                                    <span class="product-placeholder">PC</span>
                                <?php endif; ?>
                                <?php if ($product['on_sale']): ?>
                                    <span class="product-badge-sale">Promo</span>
                                <?php endif; ?>
                            </a>
                            <div class="product-card-body">
                                <?php if ($product['brand']): ?>
                                    <span class="product-brand"><?= Helpers::e($product['brand']) ?></span>
                                <?php endif; ?>
                                <h3><a href="/catalogue.php?q=<?= urlencode($product['name']) ?>"><?= Helpers::e($product['name']) ?></a></h3>
                                <p class="product-price"><?= Helpers::formatPrice((float) $product['price']) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="section-cta">
                    <a href="/catalogue.php" class="btn btn-outline">Voir tout le catalogue<?= $productsCount ? ' (' . $productsCount . ' produits)' : '' ?></a>
                </p>
            </div>
        </section>
        <?php endif; ?>

        <section class="trust-band">
            <div class="container trust-grid">
                <article class="trust-item">
                    <span class="trust-icon">01</span>
                    <h3>Livraison</h3>
                    <p>Expédition sécurisée partout au Maroc. Retrait possible en magasin à Casablanca.</p>
                    <a href="/page/politique-livraison">En savoir plus</a>
                </article>
                <article class="trust-item">
                    <span class="trust-icon">02</span>
                    <h3>Retours</h3>
                    <p>Produits neufs sous emballage d'origine. Conditions de retour transparentes.</p>
                    <a href="/page/politique-retour">En savoir plus</a>
                </article>
                <article class="trust-item">
                    <span class="trust-icon">03</span>
                    <h3>Commander</h3>
                    <p>Parcourez le catalogue, contactez-nous sur WhatsApp et recevez votre devis.</p>
                    <a href="/page/comment-commander">En savoir plus</a>
                </article>
                <article class="trust-item">
                    <span class="trust-icon">04</span>
                    <h3>Garantie</h3>
                    <p>Matériel 100 % authentique avec service après-vente et support technique.</p>
                    <a href="/#contact">Nous contacter</a>
                </article>
            </div>
        </section>

        <section class="newsletter-section" id="newsletter">
            <div class="container newsletter-inner">
                <div class="newsletter-copy">
                    <span class="section-label section-label-dark">Newsletter</span>
                    <h2>Restez informé</h2>
                    <p>Nouveautés, promotions et conseils photo directement dans votre boîte mail.</p>
                </div>
                <form class="newsletter-form" action="#" method="post">
                    <label class="sr-only" for="newsletter-email">Adresse e-mail</label>
                    <input type="email" id="newsletter-email" name="email" placeholder="Votre adresse e-mail" required>
                    <button type="submit" class="btn btn-primary">S'inscrire</button>
                </form>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/includes/site-footer.php'; ?>
    </div>

    <?php require __DIR__ . '/includes/site-chat.php'; ?>
    <script src="/assets/js/sidebar.js"></script>
    <script src="/assets/js/chat.js"></script>
</body>
</html>
