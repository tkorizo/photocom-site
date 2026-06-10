<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/image_field.php';

$category = $category ?? null;
$parents = CategoryRepository::allForSelect(isset($category['id']) ? (int) $category['id'] : null);
?>
<div class="panel">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?= Helpers::e($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="form-grid">
        <?= Auth::csrfField() ?>

        <div class="form-row">
            <label>
                Nom *
                <input type="text" name="name" value="<?= Helpers::e($category['name'] ?? $_POST['name'] ?? '') ?>" required>
            </label>
            <label>
                Slug
                <input type="text" name="slug" value="<?= Helpers::e($category['slug'] ?? $_POST['slug'] ?? '') ?>" placeholder="auto-généré si vide">
            </label>
        </div>

        <div class="form-row">
            <label>
                Catégorie parente
                <select name="parent_id">
                    <option value="">— Aucune (racine) —</option>
                    <?php foreach ($parents as $parent): ?>
                        <?php $sel = (string) ($category['parent_id'] ?? $_POST['parent_id'] ?? '') === (string) $parent['id']; ?>
                        <option value="<?= (int) $parent['id'] ?>" <?= $sel ? 'selected' : '' ?>><?= Helpers::e($parent['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Type d'affichage
                <select name="display_type">
                    <?php $dt = $category['display_type'] ?? $_POST['display_type'] ?? 'default'; ?>
                    <option value="default" <?= $dt === 'default' ? 'selected' : '' ?>>Valeur par défaut</option>
                    <option value="products" <?= $dt === 'products' ? 'selected' : '' ?>>Produits</option>
                    <option value="subcategories" <?= $dt === 'subcategories' ? 'selected' : '' ?>>Sous-catégories</option>
                    <option value="both" <?= $dt === 'both' ? 'selected' : '' ?>>Les deux</option>
                </select>
            </label>
            <label>
                Tax code
                <input type="text" name="tax_code" value="<?= Helpers::e($category['tax_code'] ?? $_POST['tax_code'] ?? '') ?>">
            </label>
        </div>

        <label>
            Description
            <textarea name="description" rows="4"><?= Helpers::e($category['description'] ?? $_POST['description'] ?? '') ?></textarea>
        </label>

        <label>
            Description supplémentaire (après les produits)
            <textarea name="extra_description" rows="4"><?= Helpers::e($category['extra_description'] ?? $_POST['extra_description'] ?? '') ?></textarea>
        </label>

        <h3 class="form-section">Images</h3>
        <p class="text-muted" style="margin-bottom:1rem">Formats acceptés : JPG, PNG, GIF, WebP — max 5 Mo</p>

        <div class="images-grid">
            <?php adminImageField('thumbnail', 'Miniature', $category['thumbnail'] ?? null); ?>
            <?php adminImageField('category_icon', 'Icône catégorie', $category['category_icon'] ?? null); ?>
            <?php adminImageField('large_category_icon', 'Grande icône', $category['large_category_icon'] ?? null); ?>
            <?php adminImageField('title_background', 'Fond titre page', $category['title_background'] ?? null); ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="/admin/categories/index.php" class="btn">Annuler</a>
        </div>
    </form>
</div>
<script src="/assets/js/image-upload.js"></script>
