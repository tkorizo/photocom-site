<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireCatalog();

$pageTitle = 'Marques';
$currentPage = 'brands';
$errors = [];

Database::initialize();
BrandRepository::syncFromProducts();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $logo = FileUploader::processField('brand_logo', 'brands', null);
            $brandId = BrandRepository::create($name, $logo);
            AuditLog::record('brand.create', 'brand', $brandId, ['name' => $name]);
            Helpers::flash('success', 'Marque ajoutée.');
        } elseif ($action === 'update' && isset($_POST['id'])) {
            $id = (int) $_POST['id'];
            $brand = BrandRepository::find($id);
            if ($brand) {
                $logo = FileUploader::processField('brand_logo_' . $id, 'brands', $brand['logo'] ?? null);
                BrandRepository::update($id, [
                    'name' => trim((string) ($_POST['name'] ?? $brand['name'])),
                    'slug' => trim((string) ($_POST['slug'] ?? $brand['slug'])),
                    'logo' => $logo,
                    'is_active' => isset($_POST['is_active']),
                    'show_in_filter' => isset($_POST['show_in_filter']),
                    'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                ]);
                AuditLog::record('brand.update', 'brand', $id, ['name' => trim((string) ($_POST['name'] ?? $brand['name']))]);
                Helpers::flash('success', 'Marque mise à jour.');
            }
        } elseif ($action === 'delete' && isset($_POST['id'])) {
            $deleteId = (int) $_POST['id'];
            BrandRepository::delete($deleteId);
            AuditLog::record('brand.delete', 'brand', $deleteId);
            Helpers::flash('success', 'Marque supprimée.');
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }

    if ($errors === []) {
        $qs = trim($_GET['q'] ?? '') !== '' ? '?q=' . urlencode(trim($_GET['q'])) : '';
        Helpers::redirect('/admin/marques/index.php' . $qs);
    }
}

$brands = BrandRepository::all();
$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $brands = array_values(array_filter(
        $brands,
        static fn (array $b): bool => str_contains(mb_strtolower($b['name']), mb_strtolower($search))
    ));
}

require dirname(__DIR__) . '/includes/header.php';
?>

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success"><?= Helpers::e($message) ?></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $error): ?>
            <p><?= Helpers::e($error) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="panel brands-panel">
    <div class="panel-header brands-toolbar-header">
        <div>
            <h2>Marques</h2>
            <p class="text-muted">Gérez les marques, logos, et leur affichage dans les filtres catalogue.</p>
        </div>
        <form method="get" class="search-form">
            <input type="search" name="q" value="<?= Helpers::e($search) ?>" placeholder="Rechercher une marque…">
            <button type="submit" class="btn">Rechercher</button>
        </form>
    </div>

    <form method="post" enctype="multipart/form-data" class="brand-create-box">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="action" value="create">
        <h3 class="form-section">Ajouter une marque</h3>
        <div class="brand-create-grid">
            <label>Nom
                <input type="text" name="name" required placeholder="Ex. Canon">
            </label>
            <label>Logo
                <input type="file" name="brand_logo_file" accept="image/jpeg,image/png,image/webp,image/gif">
            </label>
            <div class="brand-create-actions">
                <button type="submit" class="btn btn-primary">+ Ajouter</button>
            </div>
        </div>
        <input type="hidden" name="brand_logo_current" value="">
        <input type="hidden" name="brand_logo_delete" value="0">
    </form>
</div>

<div class="panel brands-panel">
    <div class="panel-header">
        <h2>Liste des marques</h2>
        <span class="text-muted"><?= count($brands) ?> marque<?= count($brands) > 1 ? 's' : '' ?></span>
    </div>

    <?php if (!$brands): ?>
        <p class="empty-state">Aucune marque.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="brands-table">
                <colgroup>
                    <col class="col-logo">
                    <col class="col-name">
                    <col class="col-slug">
                    <col class="col-active">
                    <col class="col-filter">
                    <col class="col-order">
                    <col class="col-file">
                    <col class="col-actions">
                </colgroup>
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Nom</th>
                        <th>Slug</th>
                        <th>Actif</th>
                        <th>Filtre</th>
                        <th>Ordre</th>
                        <th>Nouveau logo</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($brands as $brand): ?>
                        <?php $bid = (int) $brand['id']; ?>
                        <tr>
                            <td class="brands-td-logo">
                                <?php if (!empty($brand['logo'])): ?>
                                    <img src="<?= Helpers::e($brand['logo']) ?>" alt="" class="thumb brand-logo-thumb">
                                <?php else: ?>
                                    <span class="thumb-placeholder">—</span>
                                <?php endif; ?>
                                <form method="post" enctype="multipart/form-data" id="brand-form-<?= $bid ?>" class="brands-hidden-form">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?= $bid ?>">
                                    <input type="hidden" name="brand_logo_<?= $bid ?>_current" value="<?= Helpers::e((string) ($brand['logo'] ?? '')) ?>">
                                    <input type="hidden" name="brand_logo_<?= $bid ?>_delete" value="0">
                                </form>
                                <form method="post" id="brand-del-<?= $bid ?>" class="brands-hidden-form" onsubmit="return confirm('Supprimer cette marque ?');">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $bid ?>">
                                </form>
                            </td>
                            <td>
                                <input form="brand-form-<?= $bid ?>" type="text" name="name" value="<?= Helpers::e($brand['name']) ?>" required aria-label="Nom" class="brands-input">
                            </td>
                            <td>
                                <input form="brand-form-<?= $bid ?>" type="text" name="slug" value="<?= Helpers::e($brand['slug']) ?>" aria-label="Slug" class="brands-input">
                            </td>
                            <td class="brands-td-center">
                                <label class="checkbox-label compact">
                                    <input form="brand-form-<?= $bid ?>" type="checkbox" name="is_active" <?= (int) ($brand['is_active'] ?? 1) === 1 ? 'checked' : '' ?>> Actif
                                </label>
                            </td>
                            <td class="brands-td-center">
                                <label class="checkbox-label compact" title="Afficher cette marque dans les filtres du catalogue">
                                    <input form="brand-form-<?= $bid ?>" type="checkbox" name="show_in_filter" <?= (int) ($brand['show_in_filter'] ?? 1) === 1 ? 'checked' : '' ?>> Oui
                                </label>
                            </td>
                            <td>
                                <input form="brand-form-<?= $bid ?>" type="number" name="sort_order" value="<?= (int) $brand['sort_order'] ?>" aria-label="Ordre" class="brands-input brands-input-order">
                            </td>
                            <td>
                                <input form="brand-form-<?= $bid ?>" type="file" name="brand_logo_<?= $bid ?>_file" accept="image/jpeg,image/png,image/webp,image/gif" aria-label="Logo" class="brands-input brands-input-file">
                            </td>
                            <td class="brands-td-actions">
                                <button form="brand-form-<?= $bid ?>" type="submit" class="btn btn-sm btn-primary">Enregistrer</button>
                                <button form="brand-del-<?= $bid ?>" type="submit" class="btn btn-sm btn-danger">Suppr.</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
