<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireLogin();

$pageTitle = 'Catégories';
$currentPage = 'categories';

$filter = $_GET['filter'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        CategoryRepository::delete((int) $_POST['delete_id']);
        Helpers::flash('success', 'Catégorie supprimée.');
    }
    Helpers::redirect('/admin/categories/index.php');
}

$categories = CategoryRepository::all($filter ?: null);
$kpis = AdminStats::categories();
$filterLabel = CategoryRepository::filterLabel($filter ?: null);

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/kpi.php';
?>

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success"><?= Helpers::e($message) ?></div>
<?php endif; ?>

<?php adminKpiBar([
    ['label' => 'Total catégories', 'value' => $kpis['total'], 'variant' => 'default', 'url' => '/admin/categories/index.php', 'key' => 'all'],
    ['label' => 'Catégories racines', 'value' => $kpis['root'], 'variant' => 'default', 'url' => '/admin/categories/index.php?filter=root', 'key' => 'filter:root'],
    ['label' => 'Sous-catégories', 'value' => $kpis['children'], 'variant' => 'default', 'url' => '/admin/categories/index.php?filter=children', 'key' => 'filter:children'],
    ['label' => 'Avec produits', 'value' => $kpis['with_products'], 'variant' => 'success', 'url' => '/admin/categories/index.php?filter=with_products', 'key' => 'filter:with_products'],
    ['label' => 'Sans produit', 'value' => $kpis['empty'], 'variant' => 'warning', 'url' => '/admin/categories/index.php?filter=empty', 'key' => 'filter:empty'],
    ['label' => 'Avec miniature', 'value' => $kpis['with_image'], 'variant' => 'success', 'url' => '/admin/categories/index.php?filter=with_image', 'key' => 'filter:with_image'],
    ['label' => 'Liens produits', 'value' => $kpis['total_products_linked'], 'variant' => 'muted', 'hint' => 'produits liés'],
], ['filter' => $filter]); ?>

<?php if ($filterLabel): ?>
    <?php adminActiveFilterBar($filterLabel, '/admin/categories/index.php', count($categories)); ?>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h2>Liste des catégories</h2>
        <a href="/admin/categories/create.php" class="btn btn-primary">+ Nouvelle catégorie</a>
    </div>

    <?php if (empty($categories)): ?>
        <p class="empty-state">Aucune catégorie. <a href="/admin/categories/create.php">Créer une catégorie</a></p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Miniature</th>
                    <th>Nom</th>
                    <th>Parent</th>
                    <th>Slug</th>
                    <th>Affichage</th>
                    <th>Produits</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td>
                            <?php if ($category['thumbnail']): ?>
                                <img src="<?= Helpers::e($category['thumbnail']) ?>" alt="" class="thumb">
                            <?php else: ?>
                                <span class="thumb-placeholder">—</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= Helpers::e($category['name']) ?></strong></td>
                        <td><?= Helpers::e($category['parent_name'] ?? '—') ?></td>
                        <td><?= Helpers::e($category['slug']) ?></td>
                        <td><?= Helpers::e($category['display_type'] ?? 'default') ?></td>
                        <td><?= (int) $category['products_count'] ?></td>
                        <td class="actions">
                            <a href="/admin/categories/edit.php?id=<?= (int) $category['id'] ?>" class="btn btn-sm">Modifier</a>
                            <form method="post" class="inline-form" onsubmit="return confirm('Supprimer cette catégorie ?');">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="delete_id" value="<?= (int) $category['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Suppr.</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
