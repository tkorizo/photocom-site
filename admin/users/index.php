<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();

$pageTitle = 'Utilisateurs';
$currentPage = 'users';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        try {
            $deleteId = (int) $_POST['delete_id'];
            if ($deleteId === (int) Auth::user()['id']) {
                Helpers::flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            } else {
                UserRepository::delete($deleteId);
                Helpers::flash('success', 'Utilisateur supprimé.');
            }
        } catch (RuntimeException $e) {
            Helpers::flash('error', $e->getMessage());
        }
    }
    Helpers::redirect('/admin/users/index.php');
}

$users = UserRepository::all();

require dirname(__DIR__) . '/includes/header.php';
?>

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success"><?= Helpers::e($message) ?></div>
<?php endif; ?>
<?php if ($message = Helpers::flash('error')): ?>
    <div class="alert alert-error"><?= Helpers::e($message) ?></div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Équipe</h2>
            <p class="text-muted">Deux rôles : <strong>Administrateur</strong> (accès complet) et <strong>Éditeur</strong> (produits et catégories).</p>
        </div>
        <a href="/admin/users/create.php" class="btn btn-primary">+ Nouvel utilisateur</a>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Créé le</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><strong><?= Helpers::e($u['name']) ?></strong></td>
                    <td><?= Helpers::e($u['email']) ?></td>
                    <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-success' : 'badge-muted' ?>"><?= Helpers::e(UserRepository::roleLabel($u['role'])) ?></span></td>
                    <td><?= Helpers::e(date('d/m/Y', strtotime($u['created_at']))) ?></td>
                    <td class="actions">
                        <a href="/admin/users/edit.php?id=<?= (int) $u['id'] ?>" class="btn btn-sm">Modifier</a>
                        <?php if ((int) $u['id'] !== (int) Auth::user()['id']): ?>
                            <form method="post" class="inline-form" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="delete_id" value="<?= (int) $u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Suppr.</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
