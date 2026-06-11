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

$sortOptions = [
    'name' => 'Nom A → Z',
    'price_asc' => 'Prix croissant',
    'price_desc' => 'Prix décroissant',
    'newest' => 'Nouveautés',
];
?>
<aside class="catalogue-filters" aria-label="Filtres produits">
    <div class="filters-panel">
        <header class="filters-panel-head">
            <h2>Filtres</h2>
            <?php if ($category): ?>
                <p class="filters-panel-context"><?= Helpers::e($category['name']) ?></p>
            <?php endif; ?>
        </header>

        <form method="get" class="filters-form">
            <?php if ($catSlug): ?>
                <input type="hidden" name="cat" value="<?= Helpers::e($catSlug) ?>">
            <?php endif; ?>

            <div class="filter-block">
                <label class="filter-label" for="filter-q">Recherche</label>
                <input type="search" id="filter-q" name="q" value="<?= Helpers::e($searchQuery) ?>" placeholder="Nom, marque…" class="filter-input">
            </div>

            <?php if (!empty($filterMeta['brands'])): ?>
                <fieldset class="filter-block">
                    <legend class="filter-label">Marque</legend>
                    <div class="filter-options">
                        <label class="filter-option">
                            <input type="radio" name="brand" value="" <?= $brandFilter === '' ? 'checked' : '' ?>>
                            <span class="filter-option-text">Toutes les marques</span>
                            <span class="filter-option-mark" aria-hidden="true"></span>
                        </label>
                        <?php foreach ($filterMeta['brands'] as $brand): ?>
                            <label class="filter-option">
                                <input type="radio" name="brand" value="<?= Helpers::e($brand) ?>" <?= $brandFilter === $brand ? 'checked' : '' ?>>
                                <span class="filter-option-text"><?= Helpers::e($brand) ?></span>
                                <span class="filter-option-mark" aria-hidden="true"></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
            <?php endif; ?>

            <div class="filter-block">
                <span class="filter-label">Prix (MAD)</span>
                <div class="filter-row">
                    <input type="number" name="min_price" value="<?= Helpers::e($minPrice) ?>" placeholder="Min" min="0" step="1" class="filter-input" aria-label="Prix minimum">
                    <input type="number" name="max_price" value="<?= Helpers::e($maxPrice) ?>" placeholder="Max" min="0" step="1" class="filter-input" aria-label="Prix maximum">
                </div>
                <?php if ($filterMeta['price_max'] > 0): ?>
                    <small class="filter-hint"><?= Helpers::formatPrice($filterMeta['price_min']) ?> — <?= Helpers::formatPrice($filterMeta['price_max']) ?></small>
                <?php endif; ?>
            </div>

            <fieldset class="filter-block">
                <legend class="filter-label">Trier par</legend>
                <div class="filter-options">
                    <?php foreach ($sortOptions as $value => $label): ?>
                        <label class="filter-option">
                            <input type="radio" name="sort" value="<?= Helpers::e($value) ?>" <?= $sort === $value ? 'checked' : '' ?>>
                            <span class="filter-option-text"><?= Helpers::e($label) ?></span>
                            <span class="filter-option-mark" aria-hidden="true"></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <fieldset class="filter-block">
                <legend class="filter-label">Options</legend>
                <div class="filter-options">
                    <label class="filter-option">
                        <input type="checkbox" name="on_sale" value="1" <?= $onSaleOnly ? 'checked' : '' ?>>
                        <span class="filter-option-text">En promotion</span>
                        <span class="filter-option-mark" aria-hidden="true"></span>
                    </label>
                    <label class="filter-option">
                        <input type="checkbox" name="in_stock" value="1" <?= $inStockOnly ? 'checked' : '' ?>>
                        <span class="filter-option-text">En stock</span>
                        <span class="filter-option-mark" aria-hidden="true"></span>
                    </label>
                </div>
            </fieldset>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-filter">Appliquer</button>
                <a href="/catalogue.php<?= $catSlug ? '?cat=' . urlencode($catSlug) : '' ?>" class="btn btn-outline-filter">Réinitialiser</a>
            </div>
        </form>
    </div>
</aside>
