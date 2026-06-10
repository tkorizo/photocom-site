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

        <label>Nom complet
            <input type="text" name="name" value="<?= Helpers::e($data['name'] ?? '') ?>" required>
        </label>

        <label>Email
            <input type="email" name="email" value="<?= Helpers::e($data['email'] ?? '') ?>" required>
        </label>

        <label>Mot de passe <?= $isEdit ? '(laisser vide pour ne pas changer)' : '' ?>
            <input type="password" name="password" <?= $isEdit ? '' : 'required' ?> minlength="8" autocomplete="new-password">
        </label>

        <label>Rôle
            <select name="role">
                <option value="editor" <?= ($data['role'] ?? '') === 'editor' ? 'selected' : '' ?>>Éditeur — produits et catégories</option>
                <option value="admin" <?= ($data['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrateur — accès complet</option>
            </select>
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Enregistrer' : 'Créer l\'utilisateur' ?></button>
            <a href="/admin/users/index.php" class="btn">Annuler</a>
        </div>
    </form>
</div>
