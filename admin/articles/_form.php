<?php

declare(strict_types=1);

/** @var array $errors */
/** @var array $data */
/** @var bool $isEdit */
require_once dirname(__DIR__) . '/includes/image_field.php';
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
    <form method="post" enctype="multipart/form-data" class="form-grid article-form">
        <?= Auth::csrfField() ?>

        <h3 class="form-section">Contenu</h3>

        <label>Titre
            <input type="text" name="title" id="article-title" value="<?= Helpers::e($data['title'] ?? '') ?>" required>
        </label>

        <label>Slug (URL)
            <input type="text" name="slug" id="article-slug" value="<?= Helpers::e($data['slug'] ?? '') ?>" placeholder="Généré automatiquement si vide" pattern="[a-z0-9\-]*">
            <small class="field-hint">Lettres minuscules, chiffres et tirets. Ex. : choisir-premier-hybride</small>
        </label>

        <label>Extrait (accroche)
            <textarea name="excerpt" rows="3" maxlength="320"><?= Helpers::e($data['excerpt'] ?? '') ?></textarea>
            <small class="field-hint">Résumé court affiché dans la liste Conseils (max. 320 caractères).</small>
        </label>

        <?php adminImageField('article_image', 'Image à la une', $data['image'] ?? null); ?>

        <label>Contenu de l'article
            <textarea name="content" rows="14"><?= Helpers::e($data['content'] ?? '') ?></textarea>
            <small class="field-hint">HTML simple autorisé (&lt;p&gt;, &lt;h2&gt;, &lt;ul&gt;, &lt;a&gt;…).</small>
        </label>

        <label class="checkbox-label">
            <input type="checkbox" name="is_published" value="1" <?= !empty($data['is_published']) ? 'checked' : '' ?>>
            Publier l'article
        </label>

        <h3 class="form-section">Référencement SEO &amp; IA</h3>
        <p class="text-muted">Ces champs aident Google et les moteurs de recherche IA à comprendre la page.</p>

        <label>Meta titre
            <input type="text" name="meta_title" value="<?= Helpers::e($data['meta_title'] ?? '') ?>" maxlength="70" placeholder="Titre pour Google (sinon le titre de l’article)">
            <small class="field-hint">Idéal : 50–60 caractères.</small>
        </label>

        <label>Meta description
            <textarea name="meta_description" rows="3" maxlength="170" placeholder="Résumé pour les résultats de recherche"><?= Helpers::e($data['meta_description'] ?? '') ?></textarea>
            <small class="field-hint">Idéal : 140–160 caractères. Utilisée aussi comme description Open Graph.</small>
        </label>

        <div class="form-row">
            <label>Mot-clé principal
                <input type="text" name="focus_keyword" value="<?= Helpers::e($data['focus_keyword'] ?? '') ?>" placeholder="Ex. éclairage studio Casablanca">
            </label>
            <label>Mots-clés (secondaires)
                <input type="text" name="meta_keywords" value="<?= Helpers::e($data['meta_keywords'] ?? '') ?>" placeholder="mot1, mot2, mot3">
            </label>
        </div>

        <label>URL canonique (optionnel)
            <input type="url" name="canonical_url" value="<?= Helpers::e($data['canonical_url'] ?? '') ?>" placeholder="https://… laisser vide pour l’URL de l’article">
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Enregistrer' : 'Créer l\'article' ?></button>
            <a href="/admin/articles/index.php" class="btn">Annuler</a>
        </div>
    </form>
</div>

<script>
(function () {
    var title = document.getElementById('article-title');
    var slug = document.getElementById('article-slug');
    if (!title || !slug) return;
    var slugTouched = slug.value.trim() !== '';
    slug.addEventListener('input', function () { slugTouched = true; });
    title.addEventListener('input', function () {
        if (slugTouched && slug.value.trim() !== '') return;
        slug.value = title.value
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    });
})();
</script>
