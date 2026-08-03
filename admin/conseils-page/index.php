<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireContent();

$pageTitle = 'Page Conseils';
$currentPage = 'conseils-page';
$builderMode = true;
$errors = [];
$defaults = SettingRepository::conseilsPageDefaults();
$toggles = SettingRepository::conseilsPageToggleKeys();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        try {
            SettingRepository::setMany(ContentPageAdmin::collectPost($defaults, $toggles));
            AuditLog::record('conseils_page.update', 'conseils_page', null);
            Helpers::flash('success', 'Page Conseils enregistrée.');
            Helpers::redirect('/admin/conseils-page/index.php');
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$page = SettingRepository::conseilsPage();
$isOn = static fn (string $key): bool => ($page[$key] ?? '0') === '1';
$pageMeta = [
    'seo' => ['title' => 'SEO', 'desc' => 'Titre et description pour les moteurs de recherche'],
    'hero' => ['title' => 'Hero', 'desc' => 'En-tête principal de la page'],
    'listing' => ['title' => 'Liste des articles', 'desc' => 'Aperçu du contenu géré dans Articles'],
    'cta' => ['title' => 'Bandeau CTA', 'desc' => 'Appel à l’action en bas de page'],
];

require dirname(__DIR__) . '/includes/header.php';
?>
<link rel="stylesheet" href="/assets/css/home-editor.css">

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success he-flash"><?= Helpers::e($message) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
    <div class="alert alert-error he-flash">
        <?php foreach ($errors as $error): ?>
            <p><?= Helpers::e($error) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" class="he-builder" data-page-builder data-page-meta="<?= Helpers::e(json_encode($pageMeta, JSON_UNESCAPED_UNICODE) ?: '{}') ?>">
    <?= Auth::csrfField() ?>

    <div class="he-toolbar">
        <div class="he-toolbar-left">
            <strong>Éditeur de la page Conseils</strong>
            <span class="he-hint">Cliquez une section pour l’éditer — aperçu en direct</span>
        </div>
        <div class="he-toolbar-right">
            <a href="/conseils" class="btn btn-outline" target="_blank" rel="noopener">Voir la page</a>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </div>

    <div class="he-workspace">
        <aside class="he-structure" aria-label="Structure de la page">
            <p class="he-structure-title">Sections</p>
            <?php foreach ($pageMeta as $id => $info): ?>
                <button type="button" class="he-structure-item<?= $id === 'hero' ? ' is-active' : '' ?>" data-select="<?= Helpers::e($id) ?>"><?= Helpers::e($info['title']) ?></button>
            <?php endforeach; ?>
        </aside>

        <div class="he-canvas-wrap pe-light-wrap">
            <div class="he-canvas pe-light" id="he-canvas">
                <section class="he-block" data-block="seo" style="display:none" aria-hidden="true">
                    <span data-preview="conseils_page_seo_title"><?= Helpers::e($page['conseils_page_seo_title']) ?></span>
                    <span data-preview="conseils_page_seo_description"><?= Helpers::e($page['conseils_page_seo_description']) ?></span>
                </section>

                <section class="he-block is-active" data-block="hero">
                    <span class="he-block-label">Hero</span>
                    <div class="pe-hero">
                        <span class="pe-kicker" data-preview="conseils_page_hero_label"><?= Helpers::e($page['conseils_page_hero_label']) ?></span>
                        <h1 data-preview="conseils_page_hero_title"><?= Helpers::e($page['conseils_page_hero_title']) ?></h1>
                        <p class="pe-lead" data-preview="conseils_page_hero_lead"><?= Helpers::e($page['conseils_page_hero_lead']) ?></p>
                    </div>
                </section>

                <section class="he-block" data-block="listing">
                    <span class="he-block-label">Liste des articles</span>
                    <div class="pe-section">
                        <span class="pe-kicker">Conseils récents</span>
                        <h2 class="pe-title">Guides, techniques et inspirations</h2>
                        <div class="pe-blog-grid">
                            <?php for ($i = 1; $i <= 3; $i++): ?>
                                <article class="pe-blog-card">
                                    <div class="pe-card">
                                        <span class="pe-kicker">Article <?= $i ?></span>
                                        <strong>Contenu géré depuis Articles</strong>
                                        <p class="pe-text">L’image, le titre et l’extrait apparaîtront ici.</p>
                                    </div>
                                </article>
                            <?php endfor; ?>
                        </div>
                        <p class="pe-text">Les articles proviennent de l’administration Articles.</p>
                        <p class="pe-text"><strong>Liste vide :</strong> <span data-preview="conseils_page_empty_text"><?= Helpers::e($page['conseils_page_empty_text']) ?></span></p>
                    </div>
                </section>

                <section class="he-block<?= !$isOn('conseils_page_cta_enabled') ? ' is-hidden' : '' ?>" data-block="cta">
                    <span class="he-block-label">Bandeau CTA</span>
                    <div class="pe-section pe-dark">
                        <span class="pe-kicker" data-preview="conseils_page_cta_label"><?= Helpers::e($page['conseils_page_cta_label']) ?></span>
                        <h2 class="pe-title" data-preview="conseils_page_cta_title"><?= Helpers::e($page['conseils_page_cta_title']) ?></h2>
                        <p class="pe-text" data-preview="conseils_page_cta_text"><?= Helpers::e($page['conseils_page_cta_text']) ?></p>
                        <div class="pe-actions">
                            <span class="pe-btn" data-preview="conseils_page_cta_btn_label"><?= Helpers::e($page['conseils_page_cta_btn_label']) ?></span>
                            <span class="pe-btn pe-btn-ghost" data-preview="conseils_page_cta_secondary_label"><?= Helpers::e($page['conseils_page_cta_secondary_label']) ?></span>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <aside class="he-panel" aria-label="Réglages de section">
            <div class="he-panel-head">
                <strong data-panel-title>Hero</strong>
                <p data-panel-desc>En-tête principal de la page</p>
            </div>

            <div class="he-panel-section" data-panel="seo">
                <label>Titre SEO<input type="text" name="conseils_page_seo_title" value="<?= Helpers::e($page['conseils_page_seo_title']) ?>" data-bind="conseils_page_seo_title"></label>
                <label>Description SEO<textarea name="conseils_page_seo_description" rows="3" data-bind="conseils_page_seo_description"><?= Helpers::e($page['conseils_page_seo_description']) ?></textarea></label>
            </div>

            <div class="he-panel-section is-active" data-panel="hero">
                <label>Sur-titre<input type="text" name="conseils_page_hero_label" value="<?= Helpers::e($page['conseils_page_hero_label']) ?>" data-bind="conseils_page_hero_label"></label>
                <label>Titre<input type="text" name="conseils_page_hero_title" value="<?= Helpers::e($page['conseils_page_hero_title']) ?>" data-bind="conseils_page_hero_title"></label>
                <label>Chapô<textarea name="conseils_page_hero_lead" rows="3" data-bind="conseils_page_hero_lead"><?= Helpers::e($page['conseils_page_hero_lead']) ?></textarea></label>
            </div>

            <div class="he-panel-section" data-panel="listing">
                <p class="he-note-inline">Les cartes, images et textes des articles sont gérés dans l’administration Articles.</p>
                <label>Message si la liste est vide<textarea name="conseils_page_empty_text" rows="3" data-bind="conseils_page_empty_text"><?= Helpers::e($page['conseils_page_empty_text']) ?></textarea></label>
                <a href="/admin/articles/index.php" class="btn btn-outline" target="_blank" rel="noopener">Gérer les articles</a>
            </div>

            <div class="he-panel-section" data-panel="cta">
                <label class="he-switch"><input type="checkbox" name="conseils_page_cta_enabled" value="1" <?= $isOn('conseils_page_cta_enabled') ? 'checked' : '' ?> data-toggle-block="cta"> Afficher le bandeau</label>
                <label>Sur-titre<input type="text" name="conseils_page_cta_label" value="<?= Helpers::e($page['conseils_page_cta_label']) ?>" data-bind="conseils_page_cta_label"></label>
                <label>Titre<input type="text" name="conseils_page_cta_title" value="<?= Helpers::e($page['conseils_page_cta_title']) ?>" data-bind="conseils_page_cta_title"></label>
                <label>Texte<textarea name="conseils_page_cta_text" rows="3" data-bind="conseils_page_cta_text"><?= Helpers::e($page['conseils_page_cta_text']) ?></textarea></label>
                <hr class="he-sep">
                <p class="he-field-label">Bouton principal</p>
                <label>Libellé<input type="text" name="conseils_page_cta_btn_label" value="<?= Helpers::e($page['conseils_page_cta_btn_label']) ?>" data-bind="conseils_page_cta_btn_label"></label>
                <label>URL<input type="text" name="conseils_page_cta_btn_url" value="<?= Helpers::e($page['conseils_page_cta_btn_url']) ?>" data-bind="conseils_page_cta_btn_url"></label>
                <p class="he-field-label">Bouton secondaire</p>
                <label>Libellé<input type="text" name="conseils_page_cta_secondary_label" value="<?= Helpers::e($page['conseils_page_cta_secondary_label']) ?>" data-bind="conseils_page_cta_secondary_label"></label>
                <label>URL<input type="text" name="conseils_page_cta_secondary_url" value="<?= Helpers::e($page['conseils_page_cta_secondary_url']) ?>" data-bind="conseils_page_cta_secondary_url"></label>
            </div>
        </aside>
    </div>
</form>

<script src="/assets/js/page-editor.js"></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
