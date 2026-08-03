<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();

$pageTitle = 'Pages légales';
$currentPage = 'pages';

$pages = PageRepository::all();

require dirname(__DIR__) . '/includes/header.php';
?>

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success"><?= Helpers::e($message) ?></div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header" style="justify-content:space-between;gap:1rem;flex-wrap:wrap">
        <p class="text-muted" style="margin:0">Gérez le contenu des pages légales affichées sur le site public.</p>
        <form method="post" action="/admin/pages/seed.php" onsubmit="return confirm('Remplir les pages vides avec le contenu type PHOTOCOM ? Les pages déjà rédigées ne seront pas écrasées.');">
            <?= Auth::csrfField() ?>
            <button type="submit" class="btn btn-sm">Réinjecter le contenu type (pages vides)</button>
        </form>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Page</th>
                <th>Slug</th>
                <th>Statut</th>
                <th>Mise à jour</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pages as $page): ?>
                <tr>
                    <td><strong><?= Helpers::e($page['title']) ?></strong></td>
                    <td><?= Helpers::e($page['slug']) ?></td>
                    <td><span class="badge <?= $page['is_published'] ? 'badge-success' : 'badge-muted' ?>"><?= $page['is_published'] ? 'Publiée' : 'Masquée' ?></span></td>
                    <td><?= Helpers::e(date('d/m/Y H:i', strtotime($page['updated_at']))) ?></td>
                    <td>
                        <a href="/admin/pages/edit.php?id=<?= (int) $page['id'] ?>" class="btn btn-sm btn-primary">Modifier</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
