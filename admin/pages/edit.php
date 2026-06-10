<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
$page = PageRepository::find($id);

if (!$page) {
    Helpers::redirect('/admin/pages/index.php');
}

$pageTitle = 'Modifier : ' . $page['title'];
$currentPage = 'pages';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
        ];
        if ($data['title'] === '') {
            $errors[] = 'Le titre est obligatoire.';
        }
        if (empty($errors)) {
            PageRepository::update($id, $data);
            Helpers::flash('success', 'Page enregistrée.');
            Helpers::redirect('/admin/pages/index.php');
        }
    }
}

require dirname(__DIR__) . '/includes/header.php';
?>

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success"><?= Helpers::e($message) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error"><?= Helpers::e($errors[0]) ?></div>
<?php endif; ?>

<div class="panel">
    <form method="post" class="form-grid">
        <?= Auth::csrfField() ?>

        <label>Titre
            <input type="text" name="title" value="<?= Helpers::e($page['title']) ?>" required>
        </label>

        <label>Contenu
            <textarea name="content" rows="16"><?= Helpers::e($page['content']) ?></textarea>
        </label>

        <label class="checkbox-label">
            <input type="checkbox" name="is_published" value="1" <?= $page['is_published'] ? 'checked' : '' ?>>
            Publier la page
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="/admin/pages/index.php" class="btn">Retour</a>
        </div>
    </form>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
