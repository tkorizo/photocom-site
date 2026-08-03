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
                <input type="text" name="name" id="product-name" value="<?= Helpers::e($product['name'] ?? $_POST['name'] ?? '') ?>" required data-slug-source>
            </label>
            <label>
                Slug
                <input type="text" name="slug" id="product-slug" value="<?= Helpers::e($product['slug'] ?? $_POST['slug'] ?? '') ?>" data-slug-target>
                <small id="slug-feedback" class="field-hint" aria-live="polite"></small>
            </label>
        </div>
        <div class="form-row">
            <label class="brand-field">
                Marque
                <input type="search" id="brand-search" class="brand-search" placeholder="Rechercher une marque…" autocomplete="off">
                <select name="brand" id="brand-select" size="6" class="brand-select">
                    <option value="">— Aucune —</option>
                    <?php
                    $selectedBrand = $product['brand'] ?? $_POST['brand'] ?? '';
                    foreach (BrandRepository::all(true) as $brandOption):
                    ?>
                        <option value="<?= Helpers::e($brandOption['name']) ?>" <?= $selectedBrand === $brandOption['name'] ? 'selected' : '' ?>>
                            <?= Helpers::e($brandOption['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-sm" id="brand-add-btn" style="margin-top:0.5rem">+ Ajouter une marque</button>
                <a href="/admin/marques/index.php" class="btn btn-sm" target="_blank" rel="noopener" style="margin-top:0.5rem">Gérer les logos</a>
            </label>
        </div>

        <h3 class="form-section">Prix (TTC)</h3>
        <p class="text-muted" style="margin:-0.35rem 0 1rem">Tous les prix sont TTC. Une promo s’active <strong>uniquement</strong> si le prix promo est <strong>strictement inférieur</strong> au Prix TTC. Si les deux sont identiques, aucune promo n’est affichée.</p>
        <?php
        $postedPrice = $_POST['price'] ?? ($product['price'] ?? '0');
        $postedRegular = $_POST['regular_price'] ?? ($product['regular_price'] ?? '');
        $postedSale = $_POST['sale_price'] ?? ($product['sale_price'] ?? '');
        $pricingSnapshot = [
            'price' => $postedPrice,
            'regular_price' => $postedRegular,
            'sale_price' => $postedSale,
        ];
        $autoOnSale = Helpers::hasValidPromoPrice($pricingSnapshot);
        $pricingFieldError = null;
        foreach ($errors ?? [] as $err) {
            if (str_contains((string) $err, 'prix promo') || str_contains((string) $err, 'promotion') || str_contains((string) $err, 'Prix TTC')) {
                $pricingFieldError = (string) $err;
                break;
            }
        }
        ?>
        <div class="form-row" id="product-pricing-fields">
            <label>
                Prix TTC (MAD) *
                <input type="number" step="0.01" min="0" name="price" id="product-price" value="<?= Helpers::e((string) $postedPrice) ?>">
                <small class="field-hint">Prix de référence. En promo, c’est le prix barré.</small>
            </label>
            <label>
                Prix avant promo TTC (optionnel)
                <input type="number" step="0.01" min="0" name="regular_price" id="product-regular-price" value="<?= Helpers::e((string) $postedRegular) ?>">
                <small class="field-hint">Non utilisé pour l’affichage promo du site.</small>
            </label>
            <label>
                Prix promo TTC
                <input type="number" step="0.01" min="0" name="sale_price" id="product-sale-price" value="<?= Helpers::e((string) $postedSale) ?>" class="<?= $pricingFieldError ? 'input-invalid' : '' ?>">
                <small class="field-hint">Doit être &lt; Prix TTC pour activer la promo.</small>
                <small class="field-error" id="product-sale-price-error"<?= $pricingFieldError ? '' : ' hidden' ?>><?= Helpers::e($pricingFieldError ?? 'Le prix promo TTC ne peut pas être supérieur au Prix TTC.') ?></small>
            </label>
            <label class="checkbox-label promo-auto-label" style="align-self:end">
                <input type="hidden" name="on_sale" id="product-on-sale-value" value="<?= $autoOnSale ? '1' : '0' ?>">
                <input type="checkbox" id="product-on-sale" <?= $autoOnSale ? 'checked' : '' ?> disabled>
                En promotion
                <small class="field-hint">Activé auto si promo &lt; Prix TTC</small>
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
<script>
(function () {
    var nameInput = document.getElementById('product-name');
    var slugInput = document.getElementById('product-slug');
    var feedback = document.getElementById('slug-feedback');
    var excludeId = <?= (int) ($product['id'] ?? 0) ?>;
    var slugTouched = <?= json_encode(trim((string) ($product['slug'] ?? $_POST['slug'] ?? '')) !== '') ?>;
    var checkTimer = null;

    function slugify(text) {
        return String(text || '')
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '') || '';
    }

    function setFeedback(message, isError) {
        if (!feedback) return;
        feedback.textContent = message || '';
        feedback.className = 'field-hint' + (isError ? ' field-hint-error' : (message ? ' field-hint-ok' : ''));
    }

    function checkSlug(slug) {
        if (!slug) {
            setFeedback('');
            return;
        }
        var url = '/admin/produits/check-slug.php?slug=' + encodeURIComponent(slug);
        if (excludeId > 0) url += '&exclude_id=' + excludeId;
        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.ok) return;
                if (data.slug && slugInput && slugInput.value !== data.slug && !slugTouched) {
                    slugInput.value = data.slug;
                }
                setFeedback(data.message, !!data.exists);
            })
            .catch(function () {});
    }

    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function () {
            if (!slugTouched) {
                slugInput.value = slugify(nameInput.value);
            }
            clearTimeout(checkTimer);
            checkTimer = setTimeout(function () { checkSlug(slugInput.value || slugify(nameInput.value)); }, 280);
        });
        slugInput.addEventListener('input', function () {
            slugTouched = true;
            clearTimeout(checkTimer);
            checkTimer = setTimeout(function () { checkSlug(slugInput.value); }, 280);
        });
        if (slugInput.value) checkSlug(slugInput.value);
    }

    var brandSearch = document.getElementById('brand-search');
    var brandSelect = document.getElementById('brand-select');
    var brandAddBtn = document.getElementById('brand-add-btn');
    var csrf = document.querySelector('input[name="csrf_token"]');

    if (brandSearch && brandSelect) {
        brandSearch.addEventListener('input', function () {
            var q = brandSearch.value.trim().toLowerCase();
            Array.prototype.forEach.call(brandSelect.options, function (opt) {
                if (!opt.value) { opt.hidden = false; return; }
                opt.hidden = q !== '' && opt.text.toLowerCase().indexOf(q) === -1;
            });
        });
    }

    if (brandAddBtn && brandSelect && csrf) {
        brandAddBtn.addEventListener('click', function () {
            var name = window.prompt('Nom de la nouvelle marque :');
            if (!name) return;
            name = name.trim();
            if (!name) return;
            var body = new FormData();
            body.append('csrf_token', csrf.value);
            body.append('name', name);
            fetch('/admin/marques/quick-create.php', { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.ok) {
                        alert(data.error || 'Erreur');
                        return;
                    }
                    var opt = document.createElement('option');
                    opt.value = data.brand.name;
                    opt.textContent = data.brand.name;
                    opt.selected = true;
                    brandSelect.appendChild(opt);
                    brandSelect.value = data.brand.name;
                    if (brandSearch) brandSearch.value = '';
                    Array.prototype.forEach.call(brandSelect.options, function (o) { o.hidden = false; });
                })
                .catch(function () { alert('Impossible d\'ajouter la marque.'); });
        });
    }

    var priceInput = document.getElementById('product-price');
    var regularInput = document.getElementById('product-regular-price');
    var saleInput = document.getElementById('product-sale-price');
    var saleError = document.getElementById('product-sale-price-error');
    var onSaleBox = document.getElementById('product-on-sale');
    var onSaleValue = document.getElementById('product-on-sale-value');
    var productForm = saleInput ? saleInput.closest('form') : null;
    var pricingMsgHigh = 'Le prix promo TTC ne peut pas être supérieur au Prix TTC.';

    function parsePrice(el) {
        if (!el || el.value === '') return null;
        var n = parseFloat(el.value);
        return isNaN(n) ? null : n;
    }

    function hasValidPromo() {
        var sale = parsePrice(saleInput);
        var price = parsePrice(priceInput);
        return sale !== null && sale > 0 && price !== null && sale < price - 0.009;
    }

    function syncPromoFlag() {
        var valid = hasValidPromo();
        if (onSaleBox) onSaleBox.checked = valid;
        if (onSaleValue) onSaleValue.value = valid ? '1' : '0';
        return valid;
    }

    function validatePromoPrice() {
        if (!saleInput || !saleError) return true;
        var sale = parsePrice(saleInput);
        var price = parsePrice(priceInput);
        var tooHigh = sale !== null && price !== null && sale > price + 0.009;
        saleInput.classList.toggle('input-invalid', tooHigh);
        saleError.hidden = !tooHigh;
        if (tooHigh) saleError.textContent = pricingMsgHigh;
        syncPromoFlag();
        return !tooHigh;
    }

    [priceInput, regularInput, saleInput].forEach(function (el) {
        if (!el) return;
        el.addEventListener('input', validatePromoPrice);
        el.addEventListener('change', validatePromoPrice);
    });

    if (productForm) {
        productForm.addEventListener('submit', function (e) {
            syncPromoFlag();
            if (!validatePromoPrice()) {
                e.preventDefault();
                saleInput.focus();
            }
        });
    }
    validatePromoPrice();
})();
</script>
