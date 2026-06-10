<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();

$pageTitle = 'Pages légales';
$currentPage = 'pages';

$pages = PageRepository::all();

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="panel">
    <p class="text-muted">Gérez le contenu des pages légales affichées sur le site public.</p>

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
