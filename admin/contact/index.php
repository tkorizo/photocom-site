<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireContent();

$pageTitle = 'Page Contact';
$currentPage = 'contact-page';
$builderMode = true;
$errors = [];
$defaults = SettingRepository::contactDefaults();
$toggles = SettingRepository::contactToggleKeys();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        try {
            SettingRepository::setMany(ContentPageAdmin::collectPost($defaults, $toggles));
            AuditLog::record('contact.update', 'contact', null);
            Helpers::flash('success', 'Page Contact enregistrée.');
            Helpers::redirect('/admin/contact/index.php');
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$contact = SettingRepository::contactPage();
$isOn = static fn (string $key): bool => ($contact[$key] ?? '0') === '1';
$pageMeta = [
    'seo' => ['title' => 'SEO', 'desc' => 'Titre et description pour les moteurs de recherche'],
    'hero' => ['title' => 'Hero', 'desc' => 'En-tête principal de la page'],
    'intro' => ['title' => 'Introduction', 'desc' => 'Présentation du service de contact'],
    'info' => ['title' => 'Coordonnées', 'desc' => 'Informations pratiques et horaires'],
    'whatsapp' => ['title' => 'WhatsApp & message', 'desc' => 'Actions de contact et formulaire'],
    'map' => ['title' => 'Carte', 'desc' => 'Localisation intégrée à la page'],
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
            <strong>Éditeur de la page Contact</strong>
            <span class="he-hint">Cliquez une section pour l’éditer — aperçu en direct</span>
        </div>
        <div class="he-toolbar-right">
            <a href="/contact" class="btn btn-outline" target="_blank" rel="noopener">Voir la page</a>
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
                    <span data-preview="contact_seo_title"><?= Helpers::e($contact['contact_seo_title']) ?></span>
                    <span data-preview="contact_seo_description"><?= Helpers::e($contact['contact_seo_description']) ?></span>
                </section>

                <section class="he-block is-active" data-block="hero">
                    <span class="he-block-label">Hero</span>
                    <div class="pe-hero">
                        <span class="pe-kicker" data-preview="contact_hero_label"><?= Helpers::e($contact['contact_hero_label']) ?></span>
                        <h1 data-preview="contact_hero_title"><?= Helpers::e($contact['contact_hero_title']) ?></h1>
                        <p class="pe-lead" data-preview="contact_hero_lead"><?= Helpers::e($contact['contact_hero_lead']) ?></p>
                    </div>
                </section>

                <section class="he-block<?= !$isOn('contact_intro_enabled') ? ' is-hidden' : '' ?>" data-block="intro">
                    <span class="he-block-label">Introduction</span>
                    <div class="pe-section">
                        <span class="pe-kicker" data-preview="contact_intro_label"><?= Helpers::e($contact['contact_intro_label']) ?></span>
                        <h2 class="pe-title" data-preview="contact_intro_title"><?= Helpers::e($contact['contact_intro_title']) ?></h2>
                        <p class="pe-text" data-preview="contact_intro_text"><?= Helpers::e($contact['contact_intro_text']) ?></p>
                    </div>
                </section>

                <section class="he-block<?= !$isOn('contact_info_enabled') ? ' is-hidden' : '' ?> pe-muted" data-block="info">
                    <span class="he-block-label">Coordonnées</span>
                    <div class="pe-section pe-contact-layout">
                        <div>
                            <span class="pe-kicker" data-preview="contact_info_label"><?= Helpers::e($contact['contact_info_label']) ?></span>
                            <h2 class="pe-title" data-preview="contact_info_title"><?= Helpers::e($contact['contact_info_title']) ?></h2>
                            <ul class="pe-list">
                                <li>Adresse du showroom</li>
                                <li>Téléphone et WhatsApp</li>
                                <li>Adresse email</li>
                            </ul>
                        </div>
                        <div class="pe-card">
                            <strong>Horaires</strong>
                            <p class="pe-text" data-preview="contact_hours_note"><?= Helpers::e($contact['contact_hours_note']) ?></p>
                        </div>
                    </div>
                </section>

                <section class="he-block<?= !$isOn('contact_whatsapp_enabled') ? ' is-hidden' : '' ?>" data-block="whatsapp">
                    <span class="he-block-label">WhatsApp & message</span>
                    <div class="pe-section pe-dark pe-contact-layout">
                        <div>
                            <h2 class="pe-title" data-preview="contact_whatsapp_title"><?= Helpers::e($contact['contact_whatsapp_title']) ?></h2>
                            <p class="pe-text" data-preview="contact_whatsapp_text"><?= Helpers::e($contact['contact_whatsapp_text']) ?></p>
                            <p class="pe-text" data-preview="contact_whatsapp_prefiller"><?= Helpers::e($contact['contact_whatsapp_prefiller']) ?></p>
                            <div class="pe-actions">
                                <span class="pe-btn" data-preview="contact_whatsapp_label"><?= Helpers::e($contact['contact_whatsapp_label']) ?></span>
                                <span class="pe-btn pe-btn-ghost" data-preview="contact_cta_catalogue_label"><?= Helpers::e($contact['contact_cta_catalogue_label']) ?></span>
                            </div>
                        </div>
                        <div class="pe-card">
                            <strong data-preview="contact_form_title"><?= Helpers::e($contact['contact_form_title']) ?></strong>
                            <p class="pe-text" data-preview="contact_form_placeholder"><?= Helpers::e($contact['contact_form_placeholder']) ?></p>
                            <span class="pe-btn" data-preview="contact_form_button"><?= Helpers::e($contact['contact_form_button']) ?></span>
                        </div>
                    </div>
                </section>

                <section class="he-block<?= !$isOn('contact_map_enabled') ? ' is-hidden' : '' ?>" data-block="map">
                    <span class="he-block-label">Carte</span>
                    <div class="pe-section">
                        <h2 class="pe-title" data-preview="contact_map_title"><?= Helpers::e($contact['contact_map_title']) ?></h2>
                        <div class="pe-map">Carte (si activée)</div>
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
                <label>Titre SEO<input type="text" name="contact_seo_title" value="<?= Helpers::e($contact['contact_seo_title']) ?>" data-bind="contact_seo_title"></label>
                <label>Description SEO<textarea name="contact_seo_description" rows="3" data-bind="contact_seo_description"><?= Helpers::e($contact['contact_seo_description']) ?></textarea></label>
            </div>

            <div class="he-panel-section is-active" data-panel="hero">
                <label>Sur-titre<input type="text" name="contact_hero_label" value="<?= Helpers::e($contact['contact_hero_label']) ?>" data-bind="contact_hero_label"></label>
                <label>Titre<input type="text" name="contact_hero_title" value="<?= Helpers::e($contact['contact_hero_title']) ?>" data-bind="contact_hero_title"></label>
                <label>Chapô<textarea name="contact_hero_lead" rows="3" data-bind="contact_hero_lead"><?= Helpers::e($contact['contact_hero_lead']) ?></textarea></label>
            </div>

            <div class="he-panel-section" data-panel="intro">
                <label class="he-switch"><input type="checkbox" name="contact_intro_enabled" value="1" <?= $isOn('contact_intro_enabled') ? 'checked' : '' ?> data-toggle-block="intro"> Afficher la section</label>
                <label>Sur-titre<input type="text" name="contact_intro_label" value="<?= Helpers::e($contact['contact_intro_label']) ?>" data-bind="contact_intro_label"></label>
                <label>Titre<input type="text" name="contact_intro_title" value="<?= Helpers::e($contact['contact_intro_title']) ?>" data-bind="contact_intro_title"></label>
                <label>Texte<textarea name="contact_intro_text" rows="3" data-bind="contact_intro_text"><?= Helpers::e($contact['contact_intro_text']) ?></textarea></label>
            </div>

            <div class="he-panel-section" data-panel="info">
                <label class="he-switch"><input type="checkbox" name="contact_info_enabled" value="1" <?= $isOn('contact_info_enabled') ? 'checked' : '' ?> data-toggle-block="info"> Afficher la section</label>
                <label>Sur-titre<input type="text" name="contact_info_label" value="<?= Helpers::e($contact['contact_info_label']) ?>" data-bind="contact_info_label"></label>
                <label>Titre<input type="text" name="contact_info_title" value="<?= Helpers::e($contact['contact_info_title']) ?>" data-bind="contact_info_title"></label>
                <label class="he-switch"><input type="checkbox" name="contact_hours_enabled" value="1" <?= $isOn('contact_hours_enabled') ? 'checked' : '' ?> data-toggle-block="hours"> Afficher la note d’horaires</label>
                <label>Note horaires<textarea name="contact_hours_note" rows="3" data-bind="contact_hours_note"><?= Helpers::e($contact['contact_hours_note']) ?></textarea></label>
                <p class="he-note-inline">Adresse, téléphone, email et horaires principaux sont gérés dans Infos du site.</p>
            </div>

            <div class="he-panel-section" data-panel="whatsapp">
                <label class="he-switch"><input type="checkbox" name="contact_whatsapp_enabled" value="1" <?= $isOn('contact_whatsapp_enabled') ? 'checked' : '' ?> data-toggle-block="whatsapp"> Afficher la section</label>
                <label>Titre du bloc<input type="text" name="contact_whatsapp_title" value="<?= Helpers::e($contact['contact_whatsapp_title']) ?>" data-bind="contact_whatsapp_title"></label>
                <label>Texte<textarea name="contact_whatsapp_text" rows="2" data-bind="contact_whatsapp_text"><?= Helpers::e($contact['contact_whatsapp_text']) ?></textarea></label>
                <label>Bouton WhatsApp<input type="text" name="contact_whatsapp_label" value="<?= Helpers::e($contact['contact_whatsapp_label']) ?>" data-bind="contact_whatsapp_label"></label>
                <label>Préfixe du message<input type="text" name="contact_whatsapp_prefiller" value="<?= Helpers::e($contact['contact_whatsapp_prefiller']) ?>" data-bind="contact_whatsapp_prefiller"></label>
                <hr class="he-sep">
                <p class="he-field-label">Formulaire</p>
                <label>Titre<input type="text" name="contact_form_title" value="<?= Helpers::e($contact['contact_form_title']) ?>" data-bind="contact_form_title"></label>
                <label>Placeholder<textarea name="contact_form_placeholder" rows="2" data-bind="contact_form_placeholder"><?= Helpers::e($contact['contact_form_placeholder']) ?></textarea></label>
                <label>Bouton d’envoi<input type="text" name="contact_form_button" value="<?= Helpers::e($contact['contact_form_button']) ?>" data-bind="contact_form_button"></label>
                <hr class="he-sep">
                <p class="he-field-label">Accès au catalogue</p>
                <label>Libellé<input type="text" name="contact_cta_catalogue_label" value="<?= Helpers::e($contact['contact_cta_catalogue_label']) ?>" data-bind="contact_cta_catalogue_label"></label>
                <label>URL<input type="text" name="contact_cta_catalogue_url" value="<?= Helpers::e($contact['contact_cta_catalogue_url']) ?>" data-bind="contact_cta_catalogue_url"></label>
            </div>

            <div class="he-panel-section" data-panel="map">
                <label class="he-switch"><input type="checkbox" name="contact_map_enabled" value="1" <?= $isOn('contact_map_enabled') ? 'checked' : '' ?> data-toggle-block="map"> Afficher la carte</label>
                <label>Titre<input type="text" name="contact_map_title" value="<?= Helpers::e($contact['contact_map_title']) ?>" data-bind="contact_map_title"></label>
                <label>Code iframe Google Maps / OpenStreetMap<textarea name="contact_map_embed" rows="5" placeholder='&lt;iframe src="..."&gt;&lt;/iframe&gt;' data-bind="contact_map_embed"><?= Helpers::e($contact['contact_map_embed']) ?></textarea></label>
            </div>
        </aside>
    </div>
</form>

<script src="/assets/js/page-editor.js"></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
