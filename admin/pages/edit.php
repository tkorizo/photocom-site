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
            AuditLog::record('page.update', 'page', $id, ['title' => $data['title'] ?? '']);
            Helpers::flash('success', 'Page enregistrée.');
            Helpers::redirect('/admin/pages/index.php');
        }
        $page = array_merge($page, $data);
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

        <div class="rich-editor" data-rich-editor>
            <p class="text-muted" style="margin-bottom:0.5rem">Contenu — utilisez la barre d’outils pour paragraphes, titres, alignement et liens.</p>
            <div class="rich-toolbar" data-rich-toolbar>
                <button type="button" class="btn btn-sm" data-cmd="formatBlock" data-value="p" title="Paragraphe">¶</button>
                <button type="button" class="btn btn-sm" data-cmd="formatBlock" data-value="h2" title="Titre 2">H2</button>
                <button type="button" class="btn btn-sm" data-cmd="formatBlock" data-value="h3" title="Titre 3">H3</button>
                <span class="rich-sep"></span>
                <button type="button" class="btn btn-sm" data-cmd="bold" title="Gras"><strong>B</strong></button>
                <button type="button" class="btn btn-sm" data-cmd="italic" title="Italique"><em>I</em></button>
                <button type="button" class="btn btn-sm" data-cmd="underline" title="Souligné"><u>U</u></button>
                <span class="rich-sep"></span>
                <button type="button" class="btn btn-sm" data-cmd="justifyLeft" title="Aligner à gauche">⬅</button>
                <button type="button" class="btn btn-sm" data-cmd="justifyCenter" title="Centrer">⬌</button>
                <button type="button" class="btn btn-sm" data-cmd="justifyRight" title="Aligner à droite">➡</button>
                <span class="rich-sep"></span>
                <button type="button" class="btn btn-sm" data-cmd="insertUnorderedList" title="Liste à puces">• Liste</button>
                <button type="button" class="btn btn-sm" data-cmd="createLink" title="Lien cliquable">Lien</button>
                <button type="button" class="btn btn-sm" data-cmd="unlink" title="Retirer le lien">Unlink</button>
                <button type="button" class="btn btn-sm" data-cmd="removeFormat" title="Effacer le format">Clear</button>
            </div>
            <div
                class="rich-area"
                data-rich-area
                contenteditable="true"
                role="textbox"
                aria-multiline="true"
            ><?= $page['content'] ?></div>
            <textarea name="content" data-rich-input hidden><?= Helpers::e($page['content']) ?></textarea>
        </div>

        <label class="checkbox-label">
            <input type="checkbox" name="is_published" value="1" <?= $page['is_published'] ? 'checked' : '' ?>>
            Publier la page
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="/admin/pages/index.php" class="btn">Retour</a>
            <a href="/page/<?= Helpers::e($page['slug']) ?>" class="btn" target="_blank" rel="noopener">Voir sur le site</a>
        </div>
    </form>
</div>

<script src="/assets/js/rich-editor.js"></script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
