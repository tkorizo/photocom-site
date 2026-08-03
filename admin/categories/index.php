<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireCatalog();

$pageTitle = 'Catégories';
$currentPage = 'categories';

$filter = $_GET['filter'] ?? '';
$redirectQs = http_build_query(array_filter(['filter' => $filter ?: null]));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $deleteId = (int) $_POST['delete_id'];
        CategoryRepository::delete($deleteId);
        AuditLog::record('category.delete', 'category', $deleteId);
        Helpers::flash('success', 'Catégorie supprimée.');
    }
    Helpers::redirect('/admin/categories/index.php' . ($redirectQs !== '' ? '?' . $redirectQs : ''));
}

$categories = CategoryRepository::all($filter ?: null);
$kpis = AdminStats::categories();
$filterLabel = CategoryRepository::filterLabel($filter ?: null);
$parentOptions = CategoryRepository::allForSelect();

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/kpi.php';
?>

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success"><?= Helpers::e($message) ?></div>
<?php endif; ?>
<?php if ($message = Helpers::flash('error')): ?>
    <div class="alert alert-error"><?= Helpers::e($message) ?></div>
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

    <p class="list-hint">Modifiez le parent dans le tableau, puis cliquez sur <strong>Enregistrer</strong> en haut.</p>

    <div class="products-save-bar" id="categories-save-bar" hidden>
        <div class="products-save-bar-inner">
            <span class="products-save-bar-text">
                <span id="categories-save-count">0</span> modification(s) en attente
            </span>
            <div class="products-save-bar-actions">
                <button type="button" class="btn" id="categories-save-discard">Annuler</button>
                <button type="button" class="btn btn-primary" id="categories-save-btn">Enregistrer les modifications</button>
            </div>
        </div>
    </div>

    <?php if (empty($categories)): ?>
        <p class="empty-state">Aucune catégorie. <a href="/admin/categories/create.php">Créer une catégorie</a></p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data-table products-table-editable">
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
                        <?php
                        $cid = (int) $category['id'];
                        $currentParent = $category['parent_id'] !== null ? (int) $category['parent_id'] : 0;
                        ?>
                        <tr>
                            <td>
                                <?php if ($category['thumbnail']): ?>
                                    <img src="<?= Helpers::e($category['thumbnail']) ?>" alt="" class="thumb">
                                <?php else: ?>
                                    <span class="thumb-placeholder">—</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= Helpers::e($category['name']) ?></strong></td>
                            <td class="cell-parent">
                                <form method="post" action="/admin/categories/quick-update.php" class="inline-field-form" data-defer-save>
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $cid ?>">
                                    <input type="hidden" name="redirect" value="/admin/categories/index.php<?= $redirectQs !== '' ? '?' . Helpers::e($redirectQs) : '' ?>">
                                    <select name="parent_id" class="input-inline input-parent">
                                        <option value="">— Aucun (racine) —</option>
                                        <?php foreach ($parentOptions as $opt): ?>
                                            <?php if ((int) $opt['id'] === $cid) {
                                                continue;
                                            } ?>
                                            <option value="<?= (int) $opt['id'] ?>" <?= $currentParent === (int) $opt['id'] ? 'selected' : '' ?>>
                                                <?= Helpers::e($opt['label']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td><?= Helpers::e($category['slug']) ?></td>
                            <td><?= Helpers::e($category['display_type'] ?? 'default') ?></td>
                            <td><?= (int) $category['products_count'] ?></td>
                            <td class="actions">
                                <a href="/admin/categories/edit.php?id=<?= $cid ?>" class="btn btn-sm">Modifier</a>
                                <form method="post" class="inline-form" onsubmit="return confirm('Supprimer cette catégorie ?');">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="delete_id" value="<?= $cid ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Suppr.</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var bar = document.getElementById('categories-save-bar');
    var countEl = document.getElementById('categories-save-count');
    var saveBtn = document.getElementById('categories-save-btn');
    var discardBtn = document.getElementById('categories-save-discard');
    if (!bar || !saveBtn) return;

    var dirty = new Set();

    function refreshBar() {
        var n = dirty.size;
        countEl.textContent = String(n);
        bar.hidden = n === 0;
    }

    function markDirty(form) {
        form.classList.add('is-dirty');
        dirty.add(form);
        refreshBar();
    }

    function clearDirty(form) {
        form.classList.remove('is-dirty');
        dirty.delete(form);
        refreshBar();
    }

    document.querySelectorAll('form[data-defer-save]').forEach(function (form) {
        form.querySelectorAll('select').forEach(function (el) {
            el.addEventListener('change', function () { markDirty(form); });
        });
    });

    discardBtn.addEventListener('click', function () { window.location.reload(); });

    saveBtn.addEventListener('click', async function () {
        var forms = Array.from(dirty);
        if (!forms.length) return;
        saveBtn.disabled = true;
        discardBtn.disabled = true;
        saveBtn.textContent = 'Enregistrement…';
        try {
            for (var i = 0; i < forms.length; i++) {
                var form = forms[i];
                var res = await fetch(form.getAttribute('action') || '/admin/categories/quick-update.php', {
                    method: 'POST',
                    body: new FormData(form),
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
            alert(err.message || 'Impossible d’enregistrer toutes les modifications.');
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
