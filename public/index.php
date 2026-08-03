<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/includes/init.php';

$featuredProducts = [];
$bestsellers = [];
$brandList = [];
$productsCount = 0;

try {
    Database::initialize();
    $productsCount = ProductRepository::countActive();

    $featuredIds = SettingRepository::parseIdList($home['featured_product_ids'] ?? '');
    $featuredLimit = max(1, min(8, (int) ($home['featured_count'] ?? 2)));
    if ($featuredIds !== []) {
        $featuredProducts = array_slice(ProductRepository::findByIdsOrdered($featuredIds), 0, $featuredLimit);
    } else {
        $featuredProducts = ProductRepository::featured($featuredLimit);
        if (count($featuredProducts) < $featuredLimit) {
            $extra = ProductRepository::recent($featuredLimit - count($featuredProducts));
            $featuredProducts = array_merge($featuredProducts, $extra);
        }
    }

    $bestsellerIds = SettingRepository::parseIdList($home['bestsellers_product_ids'] ?? '');
    $bestsellerLimit = max(1, min(16, (int) ($home['bestsellers_count'] ?? 8)));
    if ($bestsellerIds !== []) {
        $bestsellers = array_slice(ProductRepository::findByIdsOrdered($bestsellerIds), 0, $bestsellerLimit);
    } else {
        $bestsellers = ProductRepository::bestsellers($bestsellerLimit);
    }

    $brandList = BrandRepository::forHome();
} catch (Throwable) {
    $productsCount = 0;
    $menuTree = $menuTree ?? [];
    $brandList = [];
}

$isOn = static fn (string $key): bool => ($home[$key] ?? '0') === '1';

$trustItems = [];
for ($i = 1; $i <= 4; $i++) {
    $trustItems[] = [
        'title' => $home['trust_' . $i . '_title'],
        'text' => $home['trust_' . $i . '_text'],
        'link_label' => $home['trust_' . $i . '_link_label'],
        'link_url' => $home['trust_' . $i . '_link_url'],
    ];
}

$heroAlign = $home['hero_align'] ?? 'left';
$heroVertical = $home['hero_vertical'] ?? 'center';
$heroHeight = $home['hero_height'] ?? 'medium';
$heroOverlay = max(0, min(90, (int) ($home['hero_overlay'] ?? 55)));
$heroMediaType = $home['hero_media_type'] ?? 'none';
$heroImageUrl = $home['hero_image_url'] ?? '';
if ($heroMediaType === 'none') {
    if ($heroVideoUrl !== '') {
        $heroMediaType = 'video';
    } elseif ($heroImageUrl !== '') {
        $heroMediaType = 'image';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $seoTitle = trim((string) ($site['seo_title'] ?? '')) ?: $siteName;
    $seoDescription = trim((string) ($site['seo_description'] ?? '')) ?: ($tagline ?? '');
    $seoKeywords = trim((string) ($site['seo_keywords'] ?? ''));
    $seoType = 'website';
    require __DIR__ . '/includes/site-seo.php';
    ?>
    <?php require __DIR__ . '/includes/site-favicon.php'; ?>
    <link rel="stylesheet" href="/assets/css/public.css">
    <link rel="stylesheet" href="/assets/css/chat.css">
</head>
<body class="page-home">
    <a href="#main" class="skip-link">Aller au contenu principal</a>

    <?php require __DIR__ . '/includes/site-header.php'; ?>

    <main id="main">
        <?php if ($isOn('hero_enabled')): ?>
        <section
            class="hero-video hero-align-<?= Helpers::e($heroAlign) ?> hero-vertical-<?= Helpers::e($heroVertical) ?> hero-height-<?= Helpers::e($heroHeight) ?><?= $heroMediaType === 'image' ? ' has-hero-image' : '' ?>"
            aria-label="Présentation"
            style="--hero-overlay: <?= $heroOverlay ?>;<?= $heroMediaType === 'image' && $heroImageUrl !== '' ? ' --hero-image: url(' . Helpers::e($heroImageUrl) . ');' : '' ?>"
        >
            <div class="hero-video-wrap">
                <?php if ($heroMediaType === 'video' && $heroVideoUrl): ?>
                    <video class="hero-video-el" autoplay muted loop playsinline poster="<?= Helpers::e($heroVideoPoster) ?>">
                        <source src="<?= Helpers::e($heroVideoUrl) ?>" type="video/mp4">
                    </video>
                    <div class="hero-video-overlay"></div>
                    <div class="hero-video-caption container">
                        <span class="hero-kicker"><?= Helpers::e($home['hero_kicker']) ?></span>
                        <h1><?= Helpers::e($home['hero_title']) ?></h1>
                        <?php if ($isOn('hero_show_subtitle')): ?>
                            <p><?= Helpers::e($home['hero_subtitle'] ?: $tagline) ?></p>
                        <?php endif; ?>
                        <a href="<?= Helpers::e($home['hero_cta_url'] ?: '/catalogue.php') ?>" class="btn btn-primary"><?= Helpers::e($home['hero_cta_label']) ?></a>
                    </div>
                <?php elseif ($heroMediaType === 'image' && $heroImageUrl !== ''): ?>
                    <div class="hero-image-el" aria-hidden="true"></div>
                    <div class="hero-video-overlay"></div>
                    <div class="hero-video-caption container">
                        <span class="hero-kicker"><?= Helpers::e($home['hero_kicker']) ?></span>
                        <h1><?= Helpers::e($home['hero_title']) ?></h1>
                        <?php if ($isOn('hero_show_subtitle')): ?>
                            <p><?= Helpers::e($home['hero_subtitle'] ?: $tagline) ?></p>
                        <?php endif; ?>
                        <a href="<?= Helpers::e($home['hero_cta_url'] ?: '/catalogue.php') ?>" class="btn btn-primary"><?= Helpers::e($home['hero_cta_label']) ?></a>
                    </div>
                <?php else: ?>
                    <div class="hero-video-fallback">
                        <div class="hero-video-fallback-inner">
                            <span class="hero-kicker"><?= Helpers::e($home['hero_kicker']) ?></span>
                            <h1><?= Helpers::e($home['hero_title']) ?></h1>
                            <?php if ($isOn('hero_show_subtitle')): ?>
                                <p><?= Helpers::e($home['hero_subtitle'] ?: $tagline) ?></p>
                            <?php endif; ?>
                            <a href="<?= Helpers::e($home['hero_cta_url'] ?: '/catalogue.php') ?>" class="btn btn-primary"><?= Helpers::e($home['hero_cta_label']) ?></a>
                        </div>
                    </div>
                    <div class="hero-video-overlay"></div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($isOn('featured_enabled') && !empty($featuredProducts)): ?>
        <section class="home-section section-light section-align-<?= Helpers::e($home['featured_align']) ?>" id="vedettes">
            <div class="container">
                <header class="section-head">
                    <span class="section-label"><?= Helpers::e($home['featured_label']) ?></span>
                    <h2><?= Helpers::e($home['featured_title']) ?></h2>
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

        <?php if ($isOn('conseils_enabled')): ?>
        <section class="home-section section-dark section-align-<?= Helpers::e($home['conseils_align']) ?>" id="conseils">
            <div class="container">
                <header class="section-head">
                    <span class="section-label"><?= Helpers::e($home['conseils_label']) ?></span>
                    <h2><?= Helpers::e($home['conseils_title']) ?></h2>
                    <?php if ($home['conseils_subtitle'] !== ''): ?>
                        <p class="section-sub"><?= Helpers::e($home['conseils_subtitle']) ?></p>
                    <?php endif; ?>
                </header>
                <div class="blog-videos-grid">
                    <?php foreach ($blogVideos as $video): ?>
                        <?php if ($video['url'] === '') continue; ?>
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
                <p style="margin-top:1.75rem">
                    <a href="/conseils" class="btn btn-outline-light">Tous les conseils</a>
                </p>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($isOn('brands_enabled') && !empty($brandList)): ?>
        <section class="home-section section-light section-align-<?= Helpers::e($home['brands_align']) ?>" id="marques">
            <div class="container">
                <header class="section-head">
                    <span class="section-label"><?= Helpers::e($home['brands_label']) ?></span>
                    <h2><?= Helpers::e($home['brands_title']) ?></h2>
                </header>
                <div class="brand-blocks">
                    <?php foreach ($brandList as $brand): ?>
                        <a href="/catalogue.php?brand=<?= urlencode($brand['name']) ?>" class="brand-block<?= !empty($brand['logo']) ? ' brand-block--logo' : '' ?>" title="<?= Helpers::e($brand['name']) ?>">
                            <?php if (!empty($brand['logo'])): ?>
                                <img src="<?= Helpers::e($brand['logo']) ?>" alt="<?= Helpers::e($brand['name']) ?>" class="brand-logo-img" loading="lazy">
                            <?php else: ?>
                                <h3><?= Helpers::e($brand['name']) ?></h3>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($isOn('bestsellers_enabled') && !empty($bestsellers)): ?>
        <section class="home-section section-muted section-align-<?= Helpers::e($home['bestsellers_align']) ?>" id="meilleures-ventes">
            <div class="container">
                <header class="section-head">
                    <span class="section-label"><?= Helpers::e($home['bestsellers_label']) ?></span>
                    <h2><?= Helpers::e($home['bestsellers_title']) ?></h2>
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
                    <a href="/catalogue.php" class="btn btn-outline"><?= Helpers::e($home['catalogue_cta_label']) ?><?= $productsCount ? ' (' . $productsCount . ' produits)' : '' ?></a>
                </p>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($isOn('trust_enabled')): ?>
        <section class="trust-band">
            <div class="container trust-grid">
                <?php foreach ($trustItems as $index => $item): ?>
                    <article class="trust-item">
                        <span class="trust-icon"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <h3><?= Helpers::e($item['title']) ?></h3>
                        <p><?= Helpers::e($item['text']) ?></p>
                        <?php if ($item['link_label'] !== '' && $item['link_url'] !== ''): ?>
                            <a href="<?= Helpers::e($item['link_url']) ?>"><?= Helpers::e($item['link_label']) ?></a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($isOn('newsletter_enabled')): ?>
        <section class="newsletter-section section-align-<?= Helpers::e($home['newsletter_align']) ?>" id="newsletter">
            <div class="container newsletter-inner">
                <div class="newsletter-copy">
                    <span class="section-label section-label-dark"><?= Helpers::e($home['newsletter_label']) ?></span>
                    <h2><?= Helpers::e($home['newsletter_title']) ?></h2>
                    <p><?= Helpers::e($home['newsletter_text']) ?></p>
                </div>
                <form class="newsletter-form" action="#" method="post">
                    <label class="sr-only" for="newsletter-email">Adresse e-mail</label>
                    <input type="email" id="newsletter-email" name="email" placeholder="Votre adresse e-mail" required>
                    <button type="submit" class="btn btn-primary"><?= Helpers::e($home['newsletter_button']) ?></button>
                </form>
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
