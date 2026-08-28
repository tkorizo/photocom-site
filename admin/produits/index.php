<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireCatalog();

$pageTitle = 'Produits';
$currentPage = 'products';

$allColumns = [
    'image' => ['label' => 'Image', 'editable' => false],
    'name' => ['label' => 'Nom', 'editable' => true],
    'sku' => ['label' => 'SKU', 'editable' => true],
    'brand' => ['label' => 'Marque', 'editable' => true],
    'categories' => ['label' => 'Catégories', 'editable' => true],
    'price' => ['label' => 'Prix TTC', 'editable' => true],
    'regular_price' => ['label' => 'Avant promo (archiv.)', 'editable' => true],
    'sale_price' => ['label' => 'Prix promo TTC', 'editable' => true],
    'on_sale' => ['label' => 'Promo', 'editable' => false],
    'stock' => ['label' => 'Stock', 'editable' => true],
    'stock_status' => ['label' => 'Statut stock', 'editable' => true],
    'is_out_of_stock' => ['label' => 'Rupture', 'editable' => true],
    'is_coming_soon' => ['label' => 'Bientôt', 'editable' => true],
    'featured' => ['label' => 'Vedette', 'editable' => true],
    'is_active' => ['label' => 'Actif', 'editable' => true],
    'admin_label' => ['label' => 'Étiquette', 'editable' => true],
    'catalog_visibility' => ['label' => 'Visibilité', 'editable' => true],
    'hide_add_to_cart' => ['label' => 'Masquer panier', 'editable' => true],
    'updated_at' => ['label' => 'Modifié', 'editable' => false],
    'actions' => ['label' => 'Actions', 'editable' => false],
];

$defaultColumns = [
    'image', 'name', 'admin_label', 'categories', 'price', 'regular_price', 'sale_price', 'on_sale',
    'stock', 'is_out_of_stock', 'featured', 'is_active', 'actions',
];

$page = max(1, (int) ($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$filter = $_GET['filter'] ?? '';
$categoryFilter = (int) ($_GET['category_id'] ?? 0);
$brandFilter = trim($_GET['brand'] ?? '');
$labelFilter = trim($_GET['admin_label'] ?? '');
if ($labelFilter !== 'none' && !ProductLabel::isValid($labelFilter)) {
    $labelFilter = '';
}

$queryParams = static function () use ($search, $status, $filter, $categoryFilter, $brandFilter, $labelFilter, $page): array {
    return array_filter([
        'search' => $search ?: null,
        'status' => $status ?: null,
        'filter' => $filter ?: null,
        'category_id' => $categoryFilter ?: null,
        'brand' => $brandFilter ?: null,
        'admin_label' => $labelFilter ?: null,
        'page' => $page > 1 ? $page : null,
    ]);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $deleteId = (int) $_POST['delete_id'];
        ProductRepository::delete($deleteId);
        AuditLog::record('product.delete', 'product', $deleteId);
        Helpers::flash('success', 'Produit supprimé.');
    }
    Helpers::redirect('/admin/produits/index.php?' . http_build_query($queryParams()));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_columns') {
    if (Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $chosen = array_values(array_filter(
            (array) ($_POST['columns'] ?? []),
            static fn ($key): bool => isset($allColumns[(string) $key])
        ));
        if (!in_array('actions', $chosen, true)) {
            $chosen[] = 'actions';
        }
        if (!in_array('name', $chosen, true)) {
            array_unshift($chosen, 'name');
        }
        SettingRepository::set('admin_products_visible_columns', json_encode($chosen, JSON_UNESCAPED_UNICODE) ?: '[]');
        Helpers::flash('success', 'Colonnes de la liste enregistrées.');
    }
    Helpers::redirect('/admin/produits/index.php?' . http_build_query($queryParams()));
}

$savedColumns = json_decode((string) SettingRepository::get('admin_products_visible_columns', ''), true);
$visibleColumns = is_array($savedColumns) && $savedColumns !== []
    ? array_values(array_filter($savedColumns, static fn ($key): bool => isset($allColumns[(string) $key])))
    : $defaultColumns;
if (!in_array('actions', $visibleColumns, true)) {
    $visibleColumns[] = 'actions';
}
if (!in_array('name', $visibleColumns, true)) {
    array_unshift($visibleColumns, 'name');
}
// Une fois : afficher la nouvelle colonne Étiquette pour les préférences déjà enregistrées
if (
    is_array($savedColumns) && $savedColumns !== []
    && !in_array('admin_label', $visibleColumns, true)
    && SettingRepository::get('admin_products_label_column_seeded') !== '1'
) {
    $namePos = array_search('name', $visibleColumns, true);
    array_splice($visibleColumns, is_int($namePos) ? $namePos + 1 : 1, 0, ['admin_label']);
    SettingRepository::set('admin_products_visible_columns', json_encode($visibleColumns, JSON_UNESCAPED_UNICODE) ?: '[]');
    SettingRepository::set('admin_products_label_column_seeded', '1');
}

$result = ProductRepository::paginate(
    $page,
    25,
    $search ?: null,
    $status ?: null,
    $categoryFilter ?: null,
    $filter ?: null,
    $brandFilter ?: null,
    $labelFilter ?: null
);

$filterLabel = ProductRepository::filterLabel($status ?: null, $filter ?: null)
    ?? ProductLabel::filterLabel($labelFilter ?: null);
$categories = CategoryRepository::allForSelect();
$productLabels = ProductLabel::all();
$kpis = AdminStats::products();
$productIds = array_map(static fn (array $p): int => (int) $p['id'], $result['data']);
$categoryMap = ProductCategoryRepository::mapCategoryIdsForProducts($productIds);
$brands = BrandRepository::names();
if ($brands === []) {
    $brands = ProductRepository::publicFilterMeta(null)['brands'] ?? [];
}
$redirectQs = Helpers::e(http_build_query($queryParams()));
$isCol = static function (string $key) use ($visibleColumns): bool {
    return in_array($key, $visibleColumns, true);
};

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/kpi.php';
?>

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success"><?= Helpers::e($message) ?></div>
<?php endif; ?>

<?php adminKpiBar([
    ['label' => 'Total produits', 'value' => $kpis['total'], 'variant' => 'default', 'url' => '/admin/produits/index.php', 'key' => 'all'],
    ['label' => 'Actifs', 'value' => $kpis['active'], 'variant' => 'success', 'url' => '/admin/produits/index.php?status=active', 'key' => 'status:active'],
    ['label' => 'Inactifs', 'value' => $kpis['inactive'], 'variant' => 'muted', 'url' => '/admin/produits/index.php?status=inactive', 'key' => 'status:inactive'],
    ['label' => 'En rupture', 'value' => $kpis['out_of_stock'], 'variant' => 'error', 'url' => '/admin/produits/index.php?filter=out_of_stock', 'key' => 'filter:out_of_stock'],
    ['label' => 'Bientôt disponible', 'value' => $kpis['coming_soon'], 'variant' => 'warning', 'url' => '/admin/produits/index.php?filter=coming_soon', 'key' => 'filter:coming_soon'],
    ['label' => 'En stock', 'value' => $kpis['in_stock'], 'variant' => 'success', 'url' => '/admin/produits/index.php?filter=in_stock', 'key' => 'filter:in_stock'],
    ['label' => 'En promotion', 'value' => $kpis['on_sale'], 'variant' => 'warning', 'url' => '/admin/produits/index.php?filter=on_sale', 'key' => 'filter:on_sale'],
    ['label' => 'Vedettes', 'value' => $kpis['featured'], 'variant' => 'default', 'url' => '/admin/produits/index.php?filter=featured', 'key' => 'filter:featured'],
], ['status' => $status, 'filter' => $filter]); ?>

<?php if ($filterLabel): ?>
    <?php adminActiveFilterBar($filterLabel, '/admin/produits/index.php?' . http_build_query(array_filter([
        'search' => $search ?: null,
        'category_id' => $categoryFilter ?: null,
        'brand' => $brandFilter ?: null,
    ])), $result['total']); ?>
<?php endif; ?>

<div class="panel">
    <div class="panel-header products-list-toolbar">
        <form method="get" class="search-form">
            <div
                class="admin-search-suggest-wrap"
                data-search-suggest
                data-suggest-api="/admin/produits/suggest.php"
                data-suggest-all-href="/admin/produits/index.php"
                data-suggest-all-param="search"
            >
                <input
                    type="search"
                    name="search"
                    value="<?= Helpers::e($search) ?>"
                    placeholder="Rechercher nom, SKU, marque…"
                    data-search-input
                    autocomplete="off"
                    aria-autocomplete="list"
                    aria-expanded="false"
                >
                <div class="header-search-suggest" data-search-results hidden role="listbox" aria-label="Suggestions produits"></div>
            </div>
            <select name="category_id">
                <option value="">Toutes catégories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= $categoryFilter === (int) $cat['id'] ? 'selected' : '' ?>><?= Helpers::e($cat['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="brand">
                <option value="">Toutes marques</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?= Helpers::e($brand) ?>" <?= $brandFilter === $brand ? 'selected' : '' ?>><?= Helpers::e($brand) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="admin_label">
                <option value="">Toutes étiquettes</option>
                <option value="none" <?= $labelFilter === 'none' ? 'selected' : '' ?>>Sans étiquette</option>
                <?php foreach ($productLabels as $key => $meta): ?>
                    <option value="<?= Helpers::e($key) ?>" <?= $labelFilter === $key ? 'selected' : '' ?>>
                        <?= Helpers::e($meta['name']) ?> — <?= Helpers::e($meta['hint']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">Tous statuts</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Actifs</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactifs</option>
            </select>
            <select name="filter">
                <option value="">Tous filtres</option>
                <option value="on_sale" <?= $filter === 'on_sale' ? 'selected' : '' ?>>En promotion</option>
                <option value="featured" <?= $filter === 'featured' ? 'selected' : '' ?>>Vedettes</option>
                <option value="out_of_stock" <?= $filter === 'out_of_stock' ? 'selected' : '' ?>>Rupture</option>
                <option value="coming_soon" <?= $filter === 'coming_soon' ? 'selected' : '' ?>>Bientôt</option>
                <option value="in_stock" <?= $filter === 'in_stock' ? 'selected' : '' ?>>En stock</option>
            </select>
            <button type="submit" class="btn">Filtrer</button>
        </form>
        <div class="products-list-actions">
            <details class="columns-picker">
                <summary class="btn">Colonnes</summary>
                <form method="post" class="columns-picker-panel">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="action" value="save_columns">
                    <p class="columns-picker-title">Afficher / masquer</p>
                    <div class="columns-picker-grid">
                        <?php foreach ($allColumns as $key => $meta): ?>
                            <label class="checkbox-label compact">
                                <input
                                    type="checkbox"
                                    name="columns[]"
                                    value="<?= Helpers::e($key) ?>"
                                    <?= $isCol($key) ? 'checked' : '' ?>
                                    <?= in_array($key, ['name', 'actions'], true) ? 'disabled' : '' ?>
                                >
                                <?= Helpers::e($meta['label']) ?>
                                <?php if (in_array($key, ['name', 'actions'], true)): ?>
                                    <input type="hidden" name="columns[]" value="<?= Helpers::e($key) ?>">
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Enregistrer les colonnes</button>
                </form>
            </details>
            <a href="/admin/produits/create.php" class="btn btn-primary">+ Nouveau produit</a>
        </div>
    </div>

    <p class="list-hint">Modifiez les champs dans le tableau, puis cliquez sur <strong>Enregistrer</strong> en haut pour valider.</p>

    <div class="products-save-bar" id="products-save-bar" hidden>
        <div class="products-save-bar-inner">
            <span class="products-save-bar-text">
                <span id="products-save-count">0</span> modification(s) en attente
            </span>
            <div class="products-save-bar-actions">
                <button type="button" class="btn" id="products-save-discard">Annuler</button>
                <button type="button" class="btn btn-primary" id="products-save-btn">Enregistrer les modifications</button>
            </div>
        </div>
    </div>

    <?php if (empty($result['data'])): ?>
        <p class="empty-state">Aucun produit. <a href="/admin/produits/create.php">Créer un produit</a></p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data-table products-table products-table-editable">
                <thead>
                    <tr>
                        <?php foreach ($visibleColumns as $col): ?>
                            <th><?= Helpers::e($allColumns[$col]['label']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['data'] as $product): ?>
                        <?php
                        $pid = (int) $product['id'];
                        $selectedCats = $categoryMap[$pid] ?? [];
                        ?>
                        <tr data-product-id="<?= $pid ?>">
                            <?php foreach ($visibleColumns as $col): ?>
                                <?php if ($col === 'image'): ?>
                                    <td>
                                        <?php if ($product['image']): ?>
                                            <img src="<?= Helpers::e($product['image']) ?>" alt="" class="thumb">
                                        <?php else: ?>
                                            <span class="thumb-placeholder">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php elseif ($col === 'name'): ?>
                                    <td>
                                        <form method="post" action="/admin/produits/quick-update.php" class="inline-field-form" data-defer-save>
                                            <?= Auth::csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $pid ?>">
                                            <input type="hidden" name="redirect" value="/admin/produits/index.php?<?= $redirectQs ?>">
                                            <input type="text" name="name" value="<?= Helpers::e($product['name']) ?>" class="input-inline input-name" required>
                                        </form>
                                    </td>
                                <?php elseif ($col === 'sku'): ?>
                                    <td>
                                        <form method="post" action="/admin/produits/quick-update.php" class="inline-field-form" data-defer-save>
                                            <?= Auth::csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $pid ?>">
                                            <input type="hidden" name="redirect" value="/admin/produits/index.php?<?= $redirectQs ?>">
                                            <input type="text" name="sku" value="<?= Helpers::e((string) ($product['sku'] ?? '')) ?>" class="input-inline" placeholder="SKU">
                                        </form>
                                    </td>
                                <?php elseif ($col === 'brand'): ?>
                                    <td>
                                        <form method="post" action="/admin/produits/quick-update.php" class="inline-field-form" data-defer-save>
                                            <?= Auth::csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $pid ?>">
                                            <input type="hidden" name="redirect" value="/admin/produits/index.php?<?= $redirectQs ?>">
                                            <input type="text" name="brand" value="<?= Helpers::e((string) ($product['brand'] ?? '')) ?>" class="input-inline" placeholder="Marque">
                                        </form>
                                    </td>
                                <?php elseif ($col === 'admin_label'): ?>
                                    <?php $currentLabel = ProductLabel::normalize($product['admin_label'] ?? null); ?>
                                    <td class="cell-label">
                                        <form method="post" action="/admin/produits/quick-update.php" class="inline-field-form label-picker-form" data-defer-save>
                                            <?= Auth::csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $pid ?>">
                                            <input type="hidden" name="redirect" value="/admin/produits/index.php?<?= $redirectQs ?>">
                                            <input type="hidden" name="admin_label" value="<?= Helpers::e((string) ($currentLabel ?? '')) ?>" data-label-value>
                                            <div class="label-swatches" role="group" aria-label="Étiquette">
                                                <button type="button" class="label-swatch is-none <?= $currentLabel === null ? 'is-active' : '' ?>" data-label="" title="Aucune étiquette" aria-label="Aucune"></button>
                                                <?php foreach ($productLabels as $key => $meta): ?>
                                                    <button
                                                        type="button"
                                                        class="label-swatch is-<?= Helpers::e($key) ?> <?= $currentLabel === $key ? 'is-active' : '' ?>"
                                                        data-label="<?= Helpers::e($key) ?>"
                                                        style="--label-color: <?= Helpers::e($meta['hex']) ?>"
                                                        title="<?= Helpers::e($meta['name'] . ' — ' . $meta['hint']) ?>"
                                                        aria-label="<?= Helpers::e($meta['name']) ?>"
                                                    ></button>
                                                <?php endforeach; ?>
                                            </div>
                                            <span class="label-hint" data-label-hint><?= $currentLabel !== null ? Helpers::e($productLabels[$currentLabel]['hint']) : '' ?></span>
                                        </form>
                                    </td>
                                <?php elseif ($col === 'categories'): ?>
                                    <td class="cell-categories">
                                        <form method="post" action="/admin/produits/quick-update.php" class="inline-field-form" data-defer-save>
                                            <?= Auth::csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $pid ?>">
                                            <input type="hidden" name="redirect" value="/admin/produits/index.php?<?= $redirectQs ?>">
                                            <select name="category_ids[]" multiple class="input-inline input-cats" size="1" data-expand-on-focus>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?= (int) $cat['id'] ?>" <?= in_array((int) $cat['id'], $selectedCats, true) ? 'selected' : '' ?>>
                                                        <?= Helpers::e($cat['label']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>
                                <?php elseif (in_array($col, ['price', 'regular_price', 'sale_price'], true)): ?>
                                    <td class="cell-price cell-price-<?= Helpers::e($col) ?>" data-product-id="<?= $pid ?>">
                                        <form method="post" action="/admin/produits/quick-update.php" class="inline-field-form" data-defer-save data-price-field="<?= Helpers::e($col) ?>" data-product-id="<?= $pid ?>">
                                            <?= Auth::csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $pid ?>">
                                            <input type="hidden" name="redirect" value="/admin/produits/index.php?<?= $redirectQs ?>">
                                            <input type="number" step="0.01" min="0" name="<?= Helpers::e($col) ?>" value="<?= Helpers::e((string) ($product[$col] ?? '')) ?>" class="input-sm input-price" data-price-role="<?= Helpers::e($col) ?>">
                                        </form>
                                        <?php if ($col === 'sale_price'): ?>
                                            <small class="field-error inline-price-error" data-sale-error-for="<?= $pid ?>" hidden>Le prix promo TTC ne peut pas être supérieur au prix normal TTC.</small>
                                        <?php endif; ?>
                                    </td>
                                <?php elseif ($col === 'stock'): ?>
                                    <td>
                                        <form method="post" action="/admin/produits/quick-update.php" class="inline-field-form" data-defer-save>
                                            <?= Auth::csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $pid ?>">
                                            <input type="hidden" name="redirect" value="/admin/produits/index.php?<?= $redirectQs ?>">
                                            <input type="number" name="stock_quantity" value="<?= Helpers::e((string) ($product['stock_quantity'] ?? '')) ?>" class="input-sm" placeholder="—">
                                        </form>
                                    </td>
                                <?php elseif ($col === 'stock_status'): ?>
                                    <td>
                                        <form method="post" action="/admin/produits/quick-update.php" class="inline-field-form" data-defer-save>
                                            <?= Auth::csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $pid ?>">
                                            <input type="hidden" name="redirect" value="/admin/produits/index.php?<?= $redirectQs ?>">
                                            <?php $stock = $product['stock_status'] ?? 'instock'; ?>
                                            <select name="stock_status" class="input-inline">
                                                <option value="instock" <?= $stock === 'instock' ? 'selected' : '' ?>>En stock</option>
                                                <option value="outofstock" <?= $stock === 'outofstock' ? 'selected' : '' ?>>Rupture</option>
                                                <option value="onbackorder" <?= $stock === 'onbackorder' ? 'selected' : '' ?>>Sur commande</option>
                                            </select>
                                        </form>
                                    </td>
                                <?php elseif ($col === 'catalog_visibility'): ?>
                                    <td>
                                        <form method="post" action="/admin/produits/quick-update.php" class="inline-field-form" data-defer-save>
                                            <?= Auth::csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $pid ?>">
                                            <input type="hidden" name="redirect" value="/admin/produits/index.php?<?= $redirectQs ?>">
                                            <?php $vis = $product['catalog_visibility'] ?? 'visible'; ?>
                                            <select name="catalog_visibility" class="input-inline">
                                                <option value="visible" <?= $vis === 'visible' ? 'selected' : '' ?>>Visible</option>
                                                <option value="catalog" <?= $vis === 'catalog' ? 'selected' : '' ?>>Catalogue</option>
                                                <option value="search" <?= $vis === 'search' ? 'selected' : '' ?>>Recherche</option>
                                                <option value="hidden" <?= $vis === 'hidden' ? 'selected' : '' ?>>Masqué</option>
                                            </select>
                                        </form>
                                    </td>
                                <?php elseif ($col === 'on_sale'): ?>
                                    <td class="cell-status-dot">
                                        <span
                                            class="status-dot <?= !empty($product['on_sale']) ? 'is-on' : 'is-off' ?>"
                                            data-on-sale-display="<?= $pid ?>"
                                            title="<?= !empty($product['on_sale']) ? 'En promotion' : 'Pas en promotion' ?>"
                                            aria-label="<?= !empty($product['on_sale']) ? 'En promotion' : 'Pas en promotion' ?>"
                                        ></span>
                                    </td>
                                <?php elseif (in_array($col, ['is_out_of_stock', 'is_coming_soon', 'featured', 'is_active', 'hide_add_to_cart'], true)): ?>
                                    <td>
                                        <form method="post" action="/admin/produits/quick-update.php" class="inline-toggle-form" data-defer-save>
                                            <?= Auth::csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $pid ?>">
                                            <input type="hidden" name="redirect" value="/admin/produits/index.php?<?= $redirectQs ?>">
                                            <input type="hidden" name="field" value="<?= Helpers::e($col) ?>">
                                            <input type="hidden" name="value" value="<?= !empty($product[$col]) ? '1' : '0' ?>" data-toggle-value>
                                            <label class="checkbox-label compact">
                                                <input type="checkbox" <?= !empty($product[$col]) ? 'checked' : '' ?> data-toggle-checkbox>
                                            </label>
                                        </form>
                                    </td>
                                <?php elseif ($col === 'updated_at'): ?>
                                    <td><small><?= Helpers::e((string) ($product['updated_at'] ?? '—')) ?></small></td>
                                <?php elseif ($col === 'actions'): ?>
                                    <td class="actions">
                                        <a href="/admin/produits/edit.php?id=<?= $pid ?>" class="btn btn-sm btn-primary">Fiche</a>
                                        <form method="post" class="inline-form" onsubmit="return confirm('Supprimer ?');">
                                            <?= Auth::csrfField() ?>
                                            <input type="hidden" name="delete_id" value="<?= $pid ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Suppr.</button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($result['total_pages'] > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $result['total_pages']; $i++): ?>
                    <a href="?<?= http_build_query(array_filter([
                        'page' => $i,
                        'search' => $search ?: null,
                        'status' => $status ?: null,
                        'filter' => $filter ?: null,
                        'category_id' => $categoryFilter ?: null,
                        'brand' => $brandFilter ?: null,
                        'admin_label' => $labelFilter ?: null,
                    ])) ?>" class="<?= $i === $result['page'] ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
(function () {
    var bar = document.getElementById('products-save-bar');
    var countEl = document.getElementById('products-save-count');
    var saveBtn = document.getElementById('products-save-btn');
    var discardBtn = document.getElementById('products-save-discard');
    if (!bar || !saveBtn) return;

    var dirty = new Set();
    var labelHints = <?= json_encode(array_map(static fn (array $m): string => $m['hint'], $productLabels), JSON_UNESCAPED_UNICODE) ?>;

    function refreshBar() {
        var n = dirty.size;
        countEl.textContent = String(n);
        bar.hidden = n === 0;
        document.body.classList.toggle('has-products-save-bar', n > 0);
    }

    function markDirty(form) {
        if (!form) return;
        form.classList.add('is-dirty');
        dirty.add(form);
        refreshBar();
    }

    function clearDirty(form) {
        if (!form) return;
        form.classList.remove('is-dirty');
        dirty.delete(form);
        refreshBar();
    }

    var pricingMsg = 'Le prix promo TTC ne peut pas être supérieur au Prix TTC.';

    function parseNum(v) {
        if (v === '' || v === null || v === undefined) return null;
        var n = parseFloat(v);
        return isNaN(n) ? null : n;
    }

    function readPriceRole(productId, role) {
        var input = document.querySelector('form[data-product-id="' + productId + '"][data-price-field="' + role + '"] input[data-price-role="' + role + '"]');
        return input ? parseNum(input.value) : null;
    }

    function validateProductPrices(productId) {
        var sale = readPriceRole(productId, 'sale_price');
        var price = readPriceRole(productId, 'price');
        var invalid = sale !== null && price !== null && sale > price + 0.009;
        var validPromo = sale !== null && sale > 0 && price !== null && sale < price - 0.009;
        var err = document.querySelector('[data-sale-error-for="' + productId + '"]');
        var saleInput = document.querySelector('form[data-product-id="' + productId + '"][data-price-field="sale_price"] input');
        var onSaleDisplay = document.querySelector('[data-on-sale-display="' + productId + '"]');
        if (err) {
            err.hidden = !invalid;
            if (invalid) err.textContent = pricingMsg;
        }
        if (saleInput) saleInput.classList.toggle('input-invalid', invalid);
        if (onSaleDisplay) {
            onSaleDisplay.classList.toggle('is-on', validPromo && !invalid);
            onSaleDisplay.classList.toggle('is-off', !(validPromo && !invalid));
            onSaleDisplay.title = (validPromo && !invalid) ? 'En promotion' : 'Pas en promotion';
        }
        return !invalid;
    }

    function validateAllDirtyPrices() {
        var ids = new Set();
        dirty.forEach(function (form) {
            var id = form.getAttribute('data-product-id');
            if (id) ids.add(id);
            var hiddenId = form.querySelector('input[name="id"]');
            if (hiddenId) ids.add(hiddenId.value);
        });
        var ok = true;
        ids.forEach(function (id) {
            if (!validateProductPrices(id)) ok = false;
        });
        return ok;
    }

    document.querySelectorAll('form[data-defer-save]').forEach(function (form) {
        form.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]), select, textarea').forEach(function (el) {
            el.addEventListener('input', function () {
                markDirty(form);
                var pid = form.getAttribute('data-product-id') || (form.querySelector('input[name="id"]') || {}).value;
                if (pid && form.getAttribute('data-price-field')) validateProductPrices(pid);
            });
            el.addEventListener('change', function () {
                markDirty(form);
                var pid = form.getAttribute('data-product-id') || (form.querySelector('input[name="id"]') || {}).value;
                if (pid && form.getAttribute('data-price-field')) validateProductPrices(pid);
            });
            if (el.tagName === 'INPUT' && (el.type === 'text' || el.type === 'number')) {
                el.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        saveBtn.click();
                    }
                });
            }
        });

        form.querySelectorAll('[data-toggle-checkbox]').forEach(function (cb) {
            cb.addEventListener('change', function () {
                var valueInput = form.querySelector('[data-toggle-value]');
                if (valueInput) valueInput.value = cb.checked ? '1' : '0';
                markDirty(form);
            });
        });

        form.querySelectorAll('.label-swatch[data-label]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var value = btn.getAttribute('data-label') || '';
                var hidden = form.querySelector('[data-label-value]');
                var hint = form.querySelector('[data-label-hint]');
                if (hidden) hidden.value = value;
                form.querySelectorAll('.label-swatch').forEach(function (sw) {
                    sw.classList.toggle('is-active', (sw.getAttribute('data-label') || '') === value);
                });
                if (hint) hint.textContent = value && labelHints[value] ? labelHints[value] : '';
                markDirty(form);
            });
        });
    });

    document.querySelectorAll('[data-expand-on-focus]').forEach(function (select) {
        select.addEventListener('focus', function () { select.size = Math.min(8, select.options.length); });
        select.addEventListener('blur', function () {
            setTimeout(function () { select.size = 1; }, 150);
        });
    });

    discardBtn.addEventListener('click', function () {
        window.location.reload();
    });

    saveBtn.addEventListener('click', async function () {
        var forms = Array.from(dirty);
        if (!forms.length) return;

        if (!validateAllDirtyPrices()) {
            alert(pricingMsg);
            return;
        }

        saveBtn.disabled = true;
        discardBtn.disabled = true;
        saveBtn.textContent = 'Enregistrement…';

        try {
            for (var i = 0; i < forms.length; i++) {
                var form = forms[i];
                var action = form.getAttribute('action') || '/admin/produits/quick-update.php';
                var body = new FormData(form);
                var res = await fetch(action, {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                var payload = null;
                try { payload = await res.json(); } catch (e) { payload = null; }
                if (!res.ok || !payload || !payload.ok) {
                    throw new Error((payload && payload.message) || ('Erreur HTTP ' + res.status));
                }
                clearDirty(form);
            }
            window.location.reload();
        } catch (err) {
            alert((err && err.message) || 'Impossible d’enregistrer toutes les modifications. Réessayez.');
            saveBtn.disabled = false;
            discardBtn.disabled = false;
            saveBtn.textContent = 'Enregistrer les modifications';
            refreshBar();
        }
    });

    window.addEventListener('beforeunload', function (e) {
        if (dirty.size > 0) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
})();
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
