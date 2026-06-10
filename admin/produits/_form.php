<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/image_field.php';

$product = $product ?? null;
$categoryTree = CategoryRepository::tree();
if (!empty($_POST['category_ids']) && is_array($_POST['category_ids'])) {
    $selectedCategories = array_map('intval', $_POST['category_ids']);
} else {
    $selectedCategories = $product['category_ids'] ?? ProductCategoryRepository::getCategoryIds((int) ($product['id'] ?? 0));
}
$selectedCategoryLabels = [];
foreach (CategoryRepository::all() as $cat) {
    if (in_array((int) $cat['id'], $selectedCategories, true)) {
        $selectedCategoryLabels[] = [
            'id' => (int) $cat['id'],
            'name' => $cat['name'],
            'parent_name' => $cat['parent_name'] ?? null,
        ];
    }
}
$secondaryImages = $product ? ProductRepository::getSecondaryImages($product) : [];
require_once dirname(__DIR__) . '/includes/category_picker.php';
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

        <h3 class="form-section">Informations générales</h3>
        <div class="form-row">
            <label>
                Nom du produit *
                <input type="text" name="name" value="<?= Helpers::e($product['name'] ?? $_POST['name'] ?? '') ?>" required>
            </label>
            <label>
                Slug
                <input type="text" name="slug" value="<?= Helpers::e($product['slug'] ?? $_POST['slug'] ?? '') ?>">
            </label>
            <label>
                Marque
                <input type="text" name="brand" value="<?= Helpers::e($product['brand'] ?? $_POST['brand'] ?? '') ?>">
            </label>
        </div>

        <h3 class="form-section">Prix (TTC)</h3>
        <div class="form-row">
            <label>
                Prix TTC (MAD) *
                <input type="number" step="0.01" name="price" value="<?= Helpers::e((string) ($product['price'] ?? $_POST['price'] ?? '0')) ?>">
            </label>
            <label>
                Prix régulier TTC
                <input type="number" step="0.01" name="regular_price" value="<?= Helpers::e((string) ($product['regular_price'] ?? $_POST['regular_price'] ?? '')) ?>">
            </label>
            <label>
                Prix promo TTC
                <input type="number" step="0.01" name="sale_price" value="<?= Helpers::e((string) ($product['sale_price'] ?? $_POST['sale_price'] ?? '')) ?>">
            </label>
            <label class="checkbox-label" style="align-self:end">
                <input type="checkbox" name="on_sale" <?= ($product['on_sale'] ?? 0) ? 'checked' : '' ?>>
                En promotion
            </label>
        </div>

        <h3 class="form-section">Stock & visibilité</h3>
        <div class="form-row">
            <label>
                Quantité en stock
                <input type="number" name="stock_quantity" value="<?= Helpers::e((string) ($product['stock_quantity'] ?? $_POST['stock_quantity'] ?? '')) ?>">
            </label>
            <label class="checkbox-label" style="align-self:end">
                <input type="checkbox" name="manage_stock" <?= ($product['manage_stock'] ?? 0) ? 'checked' : '' ?>>
                Gérer le stock
            </label>
            <label>
                Statut stock
                <select name="stock_status">
                    <?php $stock = $product['stock_status'] ?? $_POST['stock_status'] ?? 'instock'; ?>
                    <option value="instock" <?= $stock === 'instock' ? 'selected' : '' ?>>En stock</option>
                    <option value="outofstock" <?= $stock === 'outofstock' ? 'selected' : '' ?>>Rupture</option>
                    <option value="onbackorder" <?= $stock === 'onbackorder' ? 'selected' : '' ?>>Sur commande</option>
                </select>
            </label>
            <label>
                Visibilité catalogue
                <select name="catalog_visibility">
                    <?php $vis = $product['catalog_visibility'] ?? 'visible'; ?>
                    <option value="visible" <?= $vis === 'visible' ? 'selected' : '' ?>>Visible</option>
                    <option value="catalog" <?= $vis === 'catalog' ? 'selected' : '' ?>>Catalogue uniquement</option>
                    <option value="search" <?= $vis === 'search' ? 'selected' : '' ?>>Recherche uniquement</option>
                    <option value="hidden" <?= $vis === 'hidden' ? 'selected' : '' ?>>Masqué</option>
                </select>
            </label>
        </div>

        <div class="checkbox-row">
            <label class="checkbox-label">
                <input type="checkbox" name="is_out_of_stock" <?= ($product['is_out_of_stock'] ?? 0) ? 'checked' : '' ?>>
                Bouton rupture de stock
            </label>
            <label class="checkbox-label">
                <input type="checkbox" name="is_coming_soon" <?= ($product['is_coming_soon'] ?? 0) ? 'checked' : '' ?>>
                Prochainement disponible
            </label>
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" <?= ($product['is_active'] ?? 1) ? 'checked' : '' ?>>
                Afficher sur le site
            </label>
            <label class="checkbox-label">
                <input type="checkbox" name="featured" <?= ($product['featured'] ?? 0) ? 'checked' : '' ?>>
                Produit vedette
            </label>
            <label class="checkbox-label">
                <input type="checkbox" name="hide_add_to_cart" <?= ($product['hide_add_to_cart'] ?? 0) ? 'checked' : '' ?>>
                Masquer bouton ajouter au panier
            </label>
        </div>

        <h3 class="form-section">Catégories</h3>
        <?php if (empty($categoryTree)): ?>
            <p class="empty-state">Aucune catégorie. <a href="/admin/categories/create.php">Créer une catégorie</a></p>
        <?php else: ?>
            <?php adminCategoryPicker($categoryTree, $selectedCategories, $selectedCategoryLabels); ?>
        <?php endif; ?>

        <div class="form-row">
            <label>
                SKU / Référence
                <input type="text" name="sku" value="<?= Helpers::e($product['sku'] ?? $_POST['sku'] ?? '') ?>">
            </label>
        </div>

        <h3 class="form-section">Images</h3>
        <p class="text-muted" style="margin-bottom:1rem">Formats acceptés : JPG, PNG, GIF, WebP — max 5 Mo</p>

        <div class="images-grid">
            <?php adminImageField('image', 'Image principale', $product['image'] ?? null); ?>
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <?php adminImageField('image_secondary_' . $i, 'Image secondaire ' . $i, $secondaryImages[$i - 1] ?? null); ?>
            <?php endfor; ?>
        </div>

        <h3 class="form-section">Descriptions</h3>
        <label>
            Description courte
            <textarea name="short_description" rows="4"><?= Helpers::e($product['short_description'] ?? $_POST['short_description'] ?? '') ?></textarea>
        </label>
        <label>
            Description complète (HTML accepté)
            <textarea name="description" rows="10"><?= Helpers::e($product['description'] ?? $_POST['description'] ?? '') ?></textarea>
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="/admin/produits/index.php" class="btn">Annuler</a>
        </div>
    </form>
</div>
<script src="/assets/js/image-upload.js"></script>
<script src="/assets/js/category-picker.js"></script>
