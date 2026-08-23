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

$activeFilterCount = 0;
if ($searchQuery !== '') {
    $activeFilterCount++;
}
if ($brandFilter !== '') {
    $activeFilterCount++;
}
if ($minPrice !== '') {
    $activeFilterCount++;
}
if ($maxPrice !== '') {
    $activeFilterCount++;
}
if ($onSaleOnly) {
    $activeFilterCount++;
}
if ($inStockOnly) {
    $activeFilterCount++;
}
if ($sort !== '' && $sort !== 'newest') {
    $activeFilterCount++;
}
?>
<?php if (($filtersPartial ?? 'all') !== 'drawer'): ?>
<div class="filters-toolbar" data-filters-toolbar>
    <button type="button" class="filters-open-btn" data-filters-open aria-controls="catalogue-filters-drawer" aria-expanded="false">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M4 6h16M7 12h10M10 18h4"/>
        </svg>
        <span>Filtres</span>
        <?php if ($activeFilterCount > 0): ?>
            <span class="filters-open-badge"><?= (int) $activeFilterCount ?></span>
        <?php endif; ?>
    </button>
</div>
<?php endif; ?>

<?php if (($filtersPartial ?? 'all') === 'toolbar') {
    return;
} ?>

<div class="filters-drawer-backdrop" data-filters-backdrop hidden aria-hidden="true"></div>

<aside
    class="catalogue-filters"
    id="catalogue-filters-drawer"
    data-filters-drawer
    aria-label="Filtres produits"
>
    <div class="filters-panel">
        <header class="filters-panel-head">
            <div class="filters-panel-head-text">
                <h2>Filtres</h2>
                <?php if ($category): ?>
                    <p class="filters-panel-context"><?= Helpers::e($category['name']) ?></p>
                <?php endif; ?>
            </div>
            <button type="button" class="filters-close-btn" data-filters-close aria-label="Fermer les filtres">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </header>

        <form method="get" class="filters-form" data-filters-form>
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
                            <span class="filter-option-mark" aria-hidden="true"></span>
                            <span class="filter-option-text">Toutes les marques</span>
                        </label>
                        <?php foreach ($filterMeta['brands'] as $brand): ?>
                            <label class="filter-option">
                                <input type="radio" name="brand" value="<?= Helpers::e($brand) ?>" <?= $brandFilter === $brand ? 'checked' : '' ?>>
                                <span class="filter-option-mark" aria-hidden="true"></span>
                                <span class="filter-option-text"><?= Helpers::e($brand) ?></span>
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
                            <span class="filter-option-mark" aria-hidden="true"></span>
                            <span class="filter-option-text"><?= Helpers::e($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <fieldset class="filter-block">
                <legend class="filter-label">Options</legend>
                <div class="filter-options">
                    <label class="filter-option">
                        <input type="checkbox" name="on_sale" value="1" <?= $onSaleOnly ? 'checked' : '' ?>>
                        <span class="filter-option-mark" aria-hidden="true"></span>
                        <span class="filter-option-text">En promotion</span>
                    </label>
                    <label class="filter-option">
                        <input type="checkbox" name="in_stock" value="1" <?= $inStockOnly ? 'checked' : '' ?>>
                        <span class="filter-option-mark" aria-hidden="true"></span>
                        <span class="filter-option-text">En stock</span>
                    </label>
                </div>
            </fieldset>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-filter">Appliquer</button>
                <a href="/catalogue.php<?= $catSlug ? '?cat=' . urlencode($catSlug) : '' ?>" class="btn-outline-filter">Réinitialiser</a>
            </div>
        </form>
    </div>
</aside>
