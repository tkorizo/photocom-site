<?php

declare(strict_types=1);

/** @var array $errors */
/** @var array $data */
/** @var bool $isEdit */
/** @var bool $isSuperTarget */
$isSuperTarget = !empty($isSuperTarget);
$assignable = UserRepository::assignableRoles();
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
            <input type="email" name="email" value="<?= Helpers::e($data['email'] ?? '') ?>" required <?= $isSuperTarget && !Auth::isSuperAdmin() ? 'readonly' : '' ?>>
        </label>

        <label>Mot de passe <?= $isEdit ? '(laisser vide pour ne pas changer)' : '' ?>
            <input type="password" name="password" <?= $isEdit ? '' : 'required' ?> minlength="8" autocomplete="new-password">
        </label>

        <label>Rôle
            <?php if ($isSuperTarget): ?>
                <input type="hidden" name="role" value="<?= Helpers::e(Auth::ROLE_SUPER_ADMIN) ?>">
                <input type="text" value="Super administrateur — compte unique" disabled>
                <small class="field-hint">Ce rôle ne peut pas être modifié. Un seul super administrateur existe.</small>
            <?php else: ?>
                <select name="role">
                    <?php foreach ($assignable as $value => $label): ?>
                        <option value="<?= Helpers::e($value) ?>" <?= ($data['role'] ?? '') === $value ? 'selected' : '' ?>>
                            <?= Helpers::e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Enregistrer' : 'Créer l\'utilisateur' ?></button>
            <a href="/admin/users/index.php" class="btn">Annuler</a>
        </div>
    </form>
</div>
