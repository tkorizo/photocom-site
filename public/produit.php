<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/includes/init.php';

Database::initialize();

$slug = trim($_GET['slug'] ?? '');
$product = $slug !== '' ? ProductRepository::findBySlug($slug) : null;

if (!$product || !(int) ($product['is_active'] ?? 0)) {
    http_response_code(404);
    $pageTitle = 'Produit introuvable';
    $notFound = true;
    $pricing = null;
    $related = [];
    $waUrl = '#';
} else {
    $pageTitle = $product['name'];
    $notFound = false;
    $pricing = Helpers::productPricing($product);
    $related = ProductRepository::related(
        (int) $product['id'],
        isset($product['category_id']) ? (int) $product['category_id'] : null,
        4
    );
    $waDigits = preg_replace('/\D+/', '', (string) $whatsapp) ?: (string) $whatsapp;
    $waUrl = 'https://wa.me/' . $waDigits . '?text=' . rawurlencode('Bonjour, je souhaite des infos sur : ' . $product['name']);
}

$secondaryImages = !$notFound ? ProductRepository::getSecondaryImages($product) : [];
$galleryImages = [];
if (!$notFound && !empty($product['image'])) {
    $galleryImages[] = (string) $product['image'];
}
foreach ($secondaryImages as $img) {
    if (is_string($img) && trim($img) !== '' && !in_array($img, $galleryImages, true)) {
        $galleryImages[] = $img;
    }
}
$isOut = !$notFound && (!empty($product['is_out_of_stock']) || ($product['stock_status'] ?? '') === 'outofstock');
$isComing = !$notFound && !empty($product['is_coming_soon']);
$activeNav = 'catalogue';
$canAddToCart = !$notFound && !$isOut && !$isComing && empty($product['hide_add_to_cart']);
$canWhatsApp = !$notFound && !$isOut;

$productExcerpt = !$notFound
    ? Helpers::productExcerpt($product['short_description'] ?? '', 320)
    : '';
if ($productExcerpt === '' && !$notFound) {
    $productExcerpt = Helpers::productExcerpt($product['description'] ?? '', 320);
}
$productDescriptionHtml = '';
if (!$notFound) {
    $rawDescription = (string) ($product['description'] ?? '');
    if (Helpers::containsHtml($rawDescription)) {
        $productDescriptionHtml = Helpers::sanitizeProductHtml($rawDescription);
    } else {
        $plainLong = Helpers::plainTextFromHtml($rawDescription, true);
        if ($plainLong === '') {
            $plainLong = Helpers::plainTextFromHtml((string) ($product['short_description'] ?? ''), true);
        }
        $productDescriptionHtml = $plainLong !== '' ? '<p>' . Helpers::multiline($plainLong) . '</p>' : '';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $seoTitle = !$notFound ? $product['name'] : $pageTitle;
    $seoDescription = !$notFound
        ? ($productExcerpt !== '' ? $productExcerpt : Helpers::productExcerpt($product['name'] ?? '', 160))
        : '';
    $seoImage = !$notFound ? trim((string) ($product['image'] ?? '')) : '';
    $seoType = 'product';
    $seoRobots = $notFound ? 'noindex,follow' : 'index,follow';
    if (!$notFound) {
        $productAbsImages = [];
        foreach ($galleryImages as $gallerySrc) {
            $abs = Helpers::absoluteUrl((string) $gallerySrc);
            if ($abs !== '') {
                $productAbsImages[] = $abs;
            }
        }
        $offerAvailability = ($isOut || $isComing)
            ? 'https://schema.org/OutOfStock'
            : 'https://schema.org/InStock';
        $seoJsonLdExtra = [[
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product['name'],
            'description' => $productExcerpt !== '' ? $productExcerpt : (string) $product['name'],
            'sku' => trim((string) ($product['sku'] ?? '')) ?: null,
            'image' => $productAbsImages !== [] ? $productAbsImages : null,
            'brand' => trim((string) ($product['brand'] ?? '')) !== ''
                ? ['@type' => 'Brand', 'name' => trim((string) $product['brand'])]
                : null,
            'offers' => [
                '@type' => 'Offer',
                'url' => Helpers::absoluteUrl(Helpers::productUrl($product)),
                'priceCurrency' => 'MAD',
                'price' => number_format((float) ($pricing['current'] ?? $product['price'] ?? 0), 2, '.', ''),
                'availability' => $offerAvailability,
                'itemCondition' => 'https://schema.org/NewCondition',
            ],
        ]];
        $seoJsonLdExtra[0] = array_filter($seoJsonLdExtra[0], static fn ($v) => $v !== null && $v !== []);
    }
    require __DIR__ . '/includes/site-seo.php';
    ?>
    <?php require __DIR__ . '/includes/site-favicon.php'; ?>
    <?php require __DIR__ . '/includes/site-assets.php'; ?>
</head>
<body class="page-product">
    <?php require __DIR__ . '/includes/site-header.php'; ?>

    <main>
        <?php if ($notFound): ?>
            <section class="product-studio-section">
                <div class="container">
                    <div class="editorial-empty">
                        <h1>Produit introuvable</h1>
                        <p>Ce produit n’existe pas ou n’est plus disponible.</p>
                        <p><a href="/catalogue.php" class="btn btn-primary">Retour au catalogue</a></p>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <article class="product-studio">
                <section class="product-studio-section">
                    <div class="container">
                        <nav class="product-studio-breadcrumb" aria-label="Fil d’Ariane">
                            <a href="/catalogue.php">Catalogue</a>
                            <?php if (!empty($product['category_slug']) && !empty($product['category_name'])): ?>
                                <span aria-hidden="true">/</span>
                                <a href="/catalogue.php?cat=<?= Helpers::e($product['category_slug']) ?>"><?= Helpers::e($product['category_name']) ?></a>
                            <?php endif; ?>
                            <span aria-hidden="true">/</span>
                            <span><?= Helpers::e($product['name']) ?></span>
                        </nav>

                        <div
                            class="product-studio-grid"
                            data-product-gallery
                            data-gallery-images="<?= Helpers::e(json_encode(array_values($galleryImages), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>"
                            data-gallery-alt="<?= Helpers::e($product['name']) ?>"
                        >
                            <div class="product-studio-media">
                                <figure class="product-studio-stage<?= $isComing ? ' is-coming-soon' : '' ?><?= $isOut ? ' is-out-of-stock' : '' ?>">
                                    <?php if ($galleryImages !== []): ?>
                                        <img
                                            src="<?= Helpers::e($galleryImages[0]) ?>"
                                            alt="<?= Helpers::e($product['name']) ?>"
                                            data-gallery-main
                                        >
                                        <button
                                            type="button"
                                            class="product-studio-expand"
                                            data-gallery-open
                                            aria-label="Agrandir l’image"
                                            title="Agrandir l’image"
                                        >
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <circle cx="11" cy="11" r="6.5"/>
                                                <path d="M16.5 16.5L21 21" stroke-linecap="round"/>
                                                <path d="M11 8.5v5M8.5 11h5" stroke-linecap="round"/>
                                            </svg>
                                        </button>
                                    <?php else: ?>
                                        <span class="product-placeholder product-detail-placeholder">PC</span>
                                    <?php endif; ?>
                                    <?php if ($isComing): ?>
                                        <span class="product-badge-soon">Bientôt disponible</span>
                                    <?php elseif ($isOut): ?>
                                        <span class="product-badge-out">En rupture</span>
                                    <?php endif; ?>
                                    <?php if ($pricing['on_sale'] && $pricing['discount_percent'] !== null): ?>
                                        <span class="product-badge-discount<?= ($isComing || $isOut) ? ' is-with-status' : '' ?>">−<?= (int) $pricing['discount_percent'] ?>%</span>
                                    <?php endif; ?>
                                </figure>

                                <?php if (count($galleryImages) > 1): ?>
                                    <div class="product-studio-thumbs" role="list">
                                        <?php foreach ($galleryImages as $index => $img): ?>
                                            <button
                                                type="button"
                                                class="product-studio-thumb<?= $index === 0 ? ' is-active' : '' ?>"
                                                data-gallery-thumb
                                                data-src="<?= Helpers::e($img) ?>"
                                                aria-label="Voir l’image <?= $index + 1 ?>"
                                                aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"
                                            >
                                                <img src="<?= Helpers::e($img) ?>" alt="">
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="product-studio-info">
                                <?php if (!empty($product['brand'])): ?>
                                    <p class="product-studio-brand"><?= Helpers::e($product['brand']) ?></p>
                                <?php endif; ?>

                                <h1><?= Helpers::e($product['name']) ?></h1>

                                <?php if (!empty($product['sku'])): ?>
                                    <p class="product-studio-sku">Réf. <?= Helpers::e($product['sku']) ?></p>
                                <?php endif; ?>

                                <div class="product-studio-price-row">
                                    <div class="product-price-block product-detail-price">
                                        <span class="product-price"><?= Helpers::formatPrice($pricing['current']) ?></span>
                                        <?php if ($pricing['old'] !== null): ?>
                                            <span class="product-price-old"><?= Helpers::formatPrice($pricing['old']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="product-detail-availability<?= $isOut ? ' is-out' : ($isComing ? ' is-soon' : '') ?>">
                                        <?php if ($isComing): ?>
                                            Bientôt disponible
                                        <?php elseif ($isOut): ?>
                                            En rupture de stock
                                        <?php else: ?>
                                            En stock
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <?php if ($productExcerpt !== ''): ?>
                                    <div class="product-studio-excerpt">
                                        <p><?= Helpers::e($productExcerpt) ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="product-studio-actions">
                                    <?php if ($canAddToCart): ?>
                                        <button
                                            type="button"
                                            class="btn btn-cart-add btn-studio-cart"
                                            data-cart-add
                                            data-id="<?= (int) $product['id'] ?>"
                                            data-slug="<?= Helpers::e((string) ($product['slug'] ?? '')) ?>"
                                            data-name="<?= Helpers::e((string) $product['name']) ?>"
                                            data-price="<?= Helpers::e((string) $pricing['current']) ?>"
                                            data-image="<?= Helpers::e((string) ($product['image'] ?? '')) ?>"
                                            data-brand="<?= Helpers::e((string) ($product['brand'] ?? '')) ?>"
                                            aria-label="Ajouter au panier"
                                            title="Ajouter au panier"
                                        >
                                            <?php $iconSize = 18; require __DIR__ . '/includes/cart-add-icon.php'; ?>
                                            <span class="btn-studio-cart-label">Ajouter au panier</span>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($canWhatsApp): ?>
                                        <a href="<?= Helpers::e($waUrl) ?>" class="btn btn-primary btn-studio-order" target="_blank" rel="noopener">
                                            <?= $isComing ? 'Me prévenir' : 'Commander' ?>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <a
                                    href="/catalogue.php<?= !empty($product['category_slug']) ? '?cat=' . rawurlencode((string) $product['category_slug']) : '' ?>"
                                    class="product-studio-back"
                                >← Retour au catalogue</a>
                            </div>
                        </div>
                    </div>
                </section>

                <?php if ($productDescriptionHtml !== ''): ?>
                    <section class="product-studio-desc-section">
                        <div class="container">
                            <div class="product-studio-desc-card">
                                <header class="product-studio-desc-head">
                                    <span class="section-label">Détails</span>
                                    <h2>Description du produit</h2>
                                </header>
                                <div class="product-studio-desc-body">
                                    <?= $productDescriptionHtml ?>
                                </div>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
            </article>

            <?php if ($related !== []): ?>
                <section class="product-studio-related">
                    <div class="container">
                        <header class="section-head">
                            <span class="section-label">Catalogue</span>
                            <h2>Produits similaires</h2>
                        </header>
                        <div class="products-grid">
                            <?php foreach ($related as $product): ?>
                                <?php
                                $titleTag = 'h3';
                                $showCategory = true;
                                require __DIR__ . '/includes/product-card.php';
                                ?>
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
    <?php require __DIR__ . '/includes/site-end-scripts.php'; ?>
    <?php if (!$notFound && $galleryImages !== []): ?>
        <script src="/assets/js/product-gallery.js?v=<?= is_file(__DIR__ . '/assets/js/product-gallery.js') ? filemtime(__DIR__ . '/assets/js/product-gallery.js') : time() ?>" defer></script>
    <?php endif; ?>
</body>
</html>
