<?php

declare(strict_types=1);

/** @var array $errors */
/** @var array $data */
/** @var bool $isEdit */
?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= Helpers::e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="panel">
    <form method="post" class="form-grid">
        <?= Auth::csrfField() ?>

        <label>Titre
            <input type="text" name="title" value="<?= Helpers::e($data['title'] ?? '') ?>" required>
        </label>

        <label>Slug (URL)
            <input type="text" name="slug" value="<?= Helpers::e($data['slug'] ?? '') ?>" placeholder="Généré automatiquement si vide">
        </label>

        <label>Extrait
            <textarea name="excerpt" rows="2"><?= Helpers::e($data['excerpt'] ?? '') ?></textarea>
        </label>

        <label>Image (URL)
            <input type="text" name="image" value="<?= Helpers::e($data['image'] ?? '') ?>" placeholder="/uploads/...">
        </label>

        <label>Contenu
            <textarea name="content" rows="12"><?= Helpers::e($data['content'] ?? '') ?></textarea>
        </label>

        <label class="checkbox-label">
            <input type="checkbox" name="is_published" value="1" <?= !empty($data['is_published']) ? 'checked' : '' ?>>
            Publier l'article
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Enregistrer' : 'Créer l\'article' ?></button>
            <a href="/admin/articles/index.php" class="btn">Annuler</a>
        </div>
    </form>
</div>
