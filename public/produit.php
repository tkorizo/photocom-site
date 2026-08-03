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
$canOrder = !$notFound && !$isOut && empty($product['hide_add_to_cart']);

$productExcerpt = !$notFound
    ? Helpers::productExcerpt($product['short_description'] ?? '', 320)
    : '';
if ($productExcerpt === '' && !$notFound) {
    $productExcerpt = Helpers::productExcerpt($product['description'] ?? '', 320);
}
$productDescriptionHtml = !$notFound
    ? Helpers::sanitizeProductHtml($product['description'] ?? '')
    : '';
if ($productDescriptionHtml === '' && !$notFound) {
    $plainShort = Helpers::productExcerpt($product['short_description'] ?? '', 2000);
    $productDescriptionHtml = $plainShort !== '' ? '<p>' . Helpers::e($plainShort) . '</p>' : '';
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
    $seoRobots = $notFound ? 'noindex,follow' : null;
    require __DIR__ . '/includes/site-seo.php';
    ?>
    <?php require __DIR__ . '/includes/site-favicon.php'; ?>
    <link rel="stylesheet" href="/assets/css/public.css">
    <link rel="stylesheet" href="/assets/css/chat.css">
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

                        <div class="product-studio-grid" data-product-gallery>
                            <div class="product-studio-media">
                                <figure class="product-studio-stage">
                                    <?php if ($galleryImages !== []): ?>
                                        <img
                                            src="<?= Helpers::e($galleryImages[0]) ?>"
                                            alt="<?= Helpers::e($product['name']) ?>"
                                            data-gallery-main
                                        >
                                    <?php else: ?>
                                        <span class="product-placeholder product-detail-placeholder">PC</span>
                                    <?php endif; ?>
                                    <?php if ($pricing['on_sale'] && $pricing['discount_percent'] !== null): ?>
                                        <span class="product-badge-discount">−<?= (int) $pricing['discount_percent'] ?>%</span>
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
                                    <?php if ($canOrder): ?>
                                        <button
                                            type="button"
                                            class="btn btn-cart-add"
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
                                        </button>
                                        <a href="<?= Helpers::e($waUrl) ?>" class="btn btn-primary btn-studio-order" target="_blank" rel="noopener">Commander</a>
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
    <script src="/assets/js/chat.js"></script>
    <script>
    (function () {
        var root = document.querySelector('[data-product-gallery]');
        if (!root) return;
        var main = root.querySelector('[data-gallery-main]');
        var thumbs = root.querySelectorAll('[data-gallery-thumb]');
        if (!main || !thumbs.length) return;

        thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                var src = thumb.getAttribute('data-src');
                if (!src) return;
                main.src = src;
                thumbs.forEach(function (t) {
                    var on = t === thumb;
                    t.classList.toggle('is-active', on);
                    t.setAttribute('aria-pressed', on ? 'true' : 'false');
                });
            });
        });
    })();
    </script>
</body>
</html>
