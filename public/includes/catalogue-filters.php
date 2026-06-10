<?php

declare(strict_types=1);

/** @var string $catSlug */
/** @var string $searchQuery */
/** @var array $filterMeta */
/** @var string $brandFilter */
/** @var string $sort */
/** @var string $minPrice */
/** @var string $maxPrice */
/** @var bool $onSaleOnly */
/** @var bool $inStockOnly */
/** @var array|null $category */
?>
<aside class="catalogue-filters">
    <form method="get" class="filters-form">
        <?php if ($catSlug): ?>
            <input type="hidden" name="cat" value="<?= Helpers::e($catSlug) ?>">
        <?php endif; ?>

        <div class="filter-block">
            <label class="filter-label">Recherche</label>
            <input type="search" name="q" value="<?= Helpers::e($searchQuery) ?>" placeholder="Nom, marque…" class="filter-input">
        </div>

        <?php if (!empty($filterMeta['brands'])): ?>
            <div class="filter-block">
                <label class="filter-label" for="brand">Marque</label>
                <select name="brand" id="brand" class="filter-input">
                    <option value="">Toutes les marques</option>
                    <?php foreach ($filterMeta['brands'] as $brand): ?>
                        <option value="<?= Helpers::e($brand) ?>" <?= $brandFilter === $brand ? 'selected' : '' ?>><?= Helpers::e($brand) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="filter-block">
            <label class="filter-label">Prix (MAD)</label>
            <div class="filter-row">
                <input type="number" name="min_price" value="<?= Helpers::e($minPrice) ?>" placeholder="Min" min="0" step="1" class="filter-input">
                <input type="number" name="max_price" value="<?= Helpers::e($maxPrice) ?>" placeholder="Max" min="0" step="1" class="filter-input">
            </div>
            <?php if ($filterMeta['price_max'] > 0): ?>
                <small class="filter-hint"><?= Helpers::formatPrice($filterMeta['price_min']) ?> — <?= Helpers::formatPrice($filterMeta['price_max']) ?></small>
            <?php endif; ?>
        </div>

        <div class="filter-block">
            <label class="filter-label" for="sort">Trier par</label>
            <select name="sort" id="sort" class="filter-input">
                <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Nom A → Z</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Prix croissant</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Nouveautés</option>
            </select>
        </div>

        <div class="filter-block filter-checks">
            <label class="filter-check">
                <input type="checkbox" name="on_sale" value="1" <?= $onSaleOnly ? 'checked' : '' ?>>
                En promotion
            </label>
            <label class="filter-check">
                <input type="checkbox" name="in_stock" value="1" <?= $inStockOnly ? 'checked' : '' ?>>
                En stock
            </label>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary btn-filter">Appliquer</button>
            <a href="/catalogue.php<?= $catSlug ? '?cat=' . urlencode($catSlug) : '' ?>" class="btn btn-outline-filter">Réinitialiser</a>
        </div>
    </form>
</aside>
