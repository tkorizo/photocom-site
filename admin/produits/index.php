<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireLogin();

$pageTitle = 'Produits';
$currentPage = 'products';

$page = max(1, (int) ($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$filter = $_GET['filter'] ?? '';
$categoryFilter = (int) ($_GET['category_id'] ?? 0);

$queryParams = static function () use ($search, $status, $filter, $categoryFilter, $page): array {
    return array_filter([
        'search' => $search ?: null,
        'status' => $status ?: null,
        'filter' => $filter ?: null,
        'category_id' => $categoryFilter ?: null,
        'page' => $page > 1 ? $page : null,
    ]);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        ProductRepository::delete((int) $_POST['delete_id']);
        Helpers::flash('success', 'Produit supprimé.');
    }
    Helpers::redirect('/admin/produits/index.php?' . http_build_query($queryParams()));
}

$result = ProductRepository::paginate($page, 25, $search ?: null, $status ?: null, $categoryFilter ?: null, $filter ?: null);
$filterLabel = ProductRepository::filterLabel($status ?: null, $filter ?: null);
$categories = CategoryRepository::allForSelect();
$kpis = AdminStats::products();

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
    ])), $result['total']); ?>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <form method="get" class="search-form">
            <input type="text" name="search" value="<?= Helpers::e($search) ?>" placeholder="Rechercher...">
            <select name="category_id">
                <option value="">Toutes catégories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= $categoryFilter === (int) $cat['id'] ? 'selected' : '' ?>><?= Helpers::e($cat['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">Tous statuts</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Actifs</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactifs</option>
            </select>
            <?php if ($filter): ?>
                <input type="hidden" name="filter" value="<?= Helpers::e($filter) ?>">
            <?php endif; ?>
            <button type="submit" class="btn">Filtrer</button>
        </form>
        <a href="/admin/produits/create.php" class="btn btn-primary">+ Nouveau produit</a>
    </div>

    <?php if (empty($result['data'])): ?>
        <p class="empty-state">Aucun produit. <a href="/admin/produits/create.php">Créer un produit</a></p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data-table products-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Nom</th>
                        <th>Catégories</th>
                        <th>Prix TTC</th>
                        <th>Stock</th>
                        <th>Rupture</th>
                        <th>Bientôt</th>
                        <th>Actif</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['data'] as $product): ?>
                        <tr>
                            <td>
                                <?php if ($product['image']): ?>
                                    <img src="<?= Helpers::e($product['image']) ?>" alt="" class="thumb">
                                <?php else: ?>
                                    <span class="thumb-placeholder">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= Helpers::e($product['name']) ?></strong>
                                <?php if ($product['sku']): ?><br><small>SKU: <?= Helpers::e($product['sku']) ?></small><?php endif; ?>
                            </td>
                            <td class="cell-categories"><?= Helpers::e(ProductCategoryRepository::getCategoriesLabel((int) $product['id'])) ?></td>
                            <td><?= Helpers::formatPrice((float) $product['price']) ?></td>
                            <?php $redirectQs = Helpers::e(http_build_query($queryParams())); ?>
                            <td>
                                <form method="post" action="/admin/produits/quick-update.php" class="inline-stock-form">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                    <input type="hidden" name="redirect" value="/admin/produits/index.php?<?= $redirectQs ?>">
                                    <input type="number" name="stock_quantity" value="<?= Helpers::e((string) ($product['stock_quantity'] ?? '')) ?>" class="input-sm" placeholder="—">
                                </form>
                            </td>
                            <td>
                                <form method="post" action="/admin/produits/quick-update.php" class="inline-toggle-form">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                    <input type="hidden" name="redirect" value="/admin/produits/index.php?<?= $redirectQs ?>">
                                    <input type="hidden" name="field" value="is_out_of_stock">
                                    <input type="hidden" name="value" value="<?= $product['is_out_of_stock'] ? '0' : '1' ?>">
                                    <label class="checkbox-label compact">
                                        <input type="checkbox" <?= $product['is_out_of_stock'] ? 'checked' : '' ?> onchange="this.form.querySelector('[name=value]').value=this.checked?'1':'0';this.form.submit()">
                                    </label>
                                </form>
                            </td>
                            <td>
                                <form method="post" action="/admin/produits/quick-update.php" class="inline-toggle-form">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                    <input type="hidden" name="redirect" value="/admin/produits/index.php?<?= $redirectQs ?>">
                                    <input type="hidden" name="field" value="is_coming_soon">
                                    <input type="hidden" name="value" value="<?= $product['is_coming_soon'] ? '0' : '1' ?>">
                                    <label class="checkbox-label compact">
                                        <input type="checkbox" <?= $product['is_coming_soon'] ? 'checked' : '' ?> onchange="this.form.querySelector('[name=value]').value=this.checked?'1':'0';this.form.submit()">
                                    </label>
                                </form>
                            </td>
                            <td>
                                <form method="post" action="/admin/produits/quick-update.php" class="inline-toggle-form">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                    <input type="hidden" name="redirect" value="/admin/produits/index.php?<?= $redirectQs ?>">
                                    <input type="hidden" name="field" value="is_active">
                                    <input type="hidden" name="value" value="<?= $product['is_active'] ? '0' : '1' ?>">
                                    <label class="checkbox-label compact">
                                        <input type="checkbox" <?= $product['is_active'] ? 'checked' : '' ?> onchange="this.form.querySelector('[name=value]').value=this.checked?'1':'0';this.form.submit()">
                                    </label>
                                </form>
                            </td>
                            <td class="actions">
                                <a href="/admin/produits/edit.php?id=<?= (int) $product['id'] ?>" class="btn btn-sm btn-primary">Modifier</a>
                                <form method="post" class="inline-form" onsubmit="return confirm('Supprimer ?');">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="delete_id" value="<?= (int) $product['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Suppr.</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($result['total_pages'] > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $result['total_pages']; $i++): ?>
                    <a href="?<?= http_build_query(array_filter(['page' => $i, 'search' => $search ?: null, 'status' => $status ?: null, 'filter' => $filter ?: null, 'category_id' => $categoryFilter ?: null])) ?>" class="<?= $i === $result['page'] ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.inline-stock-form input[name="stock_quantity"]').forEach(function(input) {
    input.addEventListener('change', function() { this.form.submit(); });
});
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
