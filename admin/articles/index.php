<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();

$pageTitle = 'Articles';
$currentPage = 'articles';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        ArticleRepository::delete((int) $_POST['delete_id']);
        Helpers::flash('success', 'Article supprimé.');
    }
    Helpers::redirect('/admin/articles/index.php');
}

$articles = ArticleRepository::all();

require dirname(__DIR__) . '/includes/header.php';
?>

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success"><?= Helpers::e($message) ?></div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h2>Articles du blog</h2>
        <a href="/admin/articles/create.php" class="btn btn-primary">+ Nouvel article</a>
    </div>

    <?php if (empty($articles)): ?>
        <p class="empty-state">Aucun article pour le moment.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Slug</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $article): ?>
                    <tr>
                        <td><strong><?= Helpers::e($article['title']) ?></strong></td>
                        <td><?= Helpers::e($article['slug']) ?></td>
                        <td><span class="badge <?= $article['is_published'] ? 'badge-success' : 'badge-muted' ?>"><?= $article['is_published'] ? 'Publié' : 'Brouillon' ?></span></td>
                        <td><?= Helpers::e(date('d/m/Y', strtotime($article['created_at']))) ?></td>
                        <td class="actions">
                            <a href="/admin/articles/edit.php?id=<?= (int) $article['id'] ?>" class="btn btn-sm">Modifier</a>
                            <form method="post" class="inline-form" onsubmit="return confirm('Supprimer ?');">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="delete_id" value="<?= (int) $article['id'] ?>">
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
