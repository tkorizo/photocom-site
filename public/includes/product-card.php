<?php

declare(strict_types=1);

/**
 * Carte produit uniforme (hauteur égale, promo, Commander).
 *
 * Variables attendues :
 * @var array $product
 * @var string $whatsapp
 * @var string|false|null $cardHref  string = lien, false = pas de lien, null/absent = fiche produit
 * @var string $titleTag h2|h3
 * @var bool $showCategory
 * @var bool $showCommand
 * @var string $extraClass
 */

if (!isset($cardHref)) {
    $cardHref = Helpers::productUrl($product);
}
$hasLink = is_string($cardHref) && $cardHref !== '';
$titleTag = in_array($titleTag ?? 'h3', ['h2', 'h3'], true) ? ($titleTag ?? 'h3') : 'h3';
$showCategory = !empty($showCategory);
$showCommand = !isset($showCommand) || $showCommand;
$extraClass = trim((string) ($extraClass ?? ''));
$pricing = Helpers::productPricing($product);
$waDigits = preg_replace('/\D+/', '', (string) $whatsapp) ?: (string) $whatsapp;
$waUrl = 'https://wa.me/' . $waDigits . '?text=' . rawurlencode('Bonjour, je souhaite des infos sur : ' . $product['name']);
$canAddToCart = empty($product['hide_add_to_cart'])
    && empty($product['is_out_of_stock'])
    && (($product['stock_status'] ?? 'instock') !== 'outofstock');
?>
<article class="product-card product-card-framed<?= $extraClass !== '' ? ' ' . Helpers::e($extraClass) : '' ?>">
    <?php if ($hasLink): ?>
        <a href="<?= Helpers::e($cardHref) ?>" class="product-card-image" target="_blank" rel="noopener">
    <?php else: ?>
        <div class="product-card-image">
    <?php endif; ?>
            <?php if (!empty($product['image'])): ?>
                <img src="<?= Helpers::e($product['image']) ?>" alt="<?= Helpers::e($product['name']) ?>" loading="lazy">
            <?php else: ?>
                <span class="product-placeholder">PC</span>
            <?php endif; ?>
            <?php if ($pricing['on_sale'] && $pricing['discount_percent'] !== null): ?>
                <span class="product-badge-discount">−<?= (int) $pricing['discount_percent'] ?>%</span>
            <?php endif; ?>
    <?php if ($hasLink): ?>
        </a>
    <?php else: ?>
        </div>
    <?php endif; ?>

    <div class="product-card-body">
        <?php if (!empty($product['brand'])): ?>
            <span class="product-brand"><?= Helpers::e($product['brand']) ?></span>
        <?php endif; ?>

        <<?= $titleTag ?>>
            <?php if ($hasLink): ?>
                <a href="<?= Helpers::e($cardHref) ?>" target="_blank" rel="noopener"><?= Helpers::e($product['name']) ?></a>
            <?php else: ?>
                <?= Helpers::e($product['name']) ?>
            <?php endif; ?>
        </<?= $titleTag ?>>

        <?php if ($showCategory && !empty($product['category_name'])): ?>
            <p class="product-cat-label"><?= Helpers::e($product['category_name']) ?></p>
        <?php endif; ?>

        <div class="product-card-footer">
            <div class="product-price-block">
                <span class="product-price"><?= Helpers::formatPrice($pricing['current']) ?></span>
                <?php if ($pricing['old'] !== null): ?>
                    <span class="product-price-old"><?= Helpers::formatPrice($pricing['old']) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($showCommand): ?>
                <div class="product-card-actions">
                            <?php if ($canAddToCart): ?>
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
                            <?php $iconSize = 16; require __DIR__ . '/cart-add-icon.php'; ?>
                        </button>
                    <?php endif; ?>
                    <a href="<?= Helpers::e($waUrl) ?>" class="btn btn-card" target="_blank" rel="noopener">Commander</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</article>
<?php
unset($cardHref, $titleTag, $showCategory, $showCommand, $extraClass, $pricing, $waUrl, $waDigits, $hasLink, $canAddToCart);
?>
