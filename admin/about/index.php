<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireContent();

$pageTitle = 'Page Qui sommes-nous';
$currentPage = 'about';
$builderMode = true;
$errors = [];
$defaults = SettingRepository::aboutDefaults();
$toggles = SettingRepository::aboutToggleKeys();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        SettingRepository::setMany(ContentPageAdmin::collectPost($defaults, $toggles));
        AuditLog::record('about.update', 'about', null);
        Helpers::flash('success', 'Page Qui sommes-nous enregistrée.');
        Helpers::redirect('/admin/about/index.php');
    }
}

$about = SettingRepository::about();
$isOn = static fn (string $key): bool => ($about[$key] ?? '0') === '1';

$pageMeta = [
    'seo' => ['title' => 'SEO', 'desc' => 'Titre et description pour Google'],
    'hero' => ['title' => 'Hero', 'desc' => 'En-tête de la page'],
    'story' => ['title' => 'Histoire', 'desc' => 'Texte + chiffres clés'],
    'pillars' => ['title' => 'Engagements', 'desc' => '4 piliers'],
    'audience' => ['title' => 'Publics', 'desc' => 'À qui s’adresse PHOTOCOM'],
    'cta' => ['title' => 'Bandeau CTA', 'desc' => 'Appel à l’action'],
    'find' => ['title' => 'Coordonnées', 'desc' => 'Bloc « Nous trouver »'],
    'links' => ['title' => 'Ressources', 'desc' => 'Liens utiles'],
];

require dirname(__DIR__) . '/includes/header.php';
?>
<link rel="stylesheet" href="/assets/css/home-editor.css">

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success he-flash"><?= Helpers::e($message) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
    <div class="alert alert-error he-flash"><?= Helpers::e($errors[0]) ?></div>
<?php endif; ?>

<form method="post" class="he-builder" data-page-builder data-page-meta="<?= Helpers::e(json_encode($pageMeta, JSON_UNESCAPED_UNICODE) ?: '{}') ?>">
    <?= Auth::csrfField() ?>

    <div class="he-toolbar">
        <div class="he-toolbar-left">
            <strong>Qui sommes-nous</strong>
            <span class="he-hint">Cliquez une section pour l’éditer — aperçu en direct</span>
        </div>
        <div class="he-toolbar-right">
            <a href="/a-propos" class="btn btn-outline" target="_blank" rel="noopener">Voir la page</a>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </div>

    <div class="he-workspace">
        <aside class="he-structure">
            <p class="he-structure-title">Sections</p>
            <?php foreach ($pageMeta as $id => $info): ?>
                <button type="button" class="he-structure-item<?= $id === 'hero' ? ' is-active' : '' ?>" data-select="<?= Helpers::e($id) ?>"><?= Helpers::e($info['title']) ?></button>
            <?php endforeach; ?>
        </aside>

        <div class="he-canvas-wrap pe-light-wrap">
            <div class="he-canvas pe-light" id="he-canvas">
                <section class="he-block" data-block="seo" style="display:none" aria-hidden="true"></section>

                <section class="he-block is-active" data-block="hero">
                    <span class="he-block-label">Hero</span>
                    <div class="pe-hero">
                        <span class="pe-kicker" data-preview="about_hero_label"><?= Helpers::e($about['about_hero_label']) ?></span>
                        <h1 data-preview="about_hero_title"><?= Helpers::e($about['about_hero_title']) ?></h1>
                        <p class="pe-lead" data-preview="about_hero_lead"><?= Helpers::e($about['about_hero_lead']) ?></p>
                    </div>
                </section>

                <section class="he-block<?= !$isOn('about_story_enabled') ? ' is-hidden' : '' ?>" data-block="story">
                    <span class="he-block-label">Histoire</span>
                    <div class="pe-section">
                        <div class="pe-grid-2">
                            <div>
                                <span class="pe-kicker" data-preview="about_story_label"><?= Helpers::e($about['about_story_label']) ?></span>
                                <h2 class="pe-title" data-preview="about_story_title"><?= Helpers::e($about['about_story_title']) ?></h2>
                                <p class="pe-text" data-preview="about_story_p1"><?= Helpers::e($about['about_story_p1']) ?></p>
                                <p class="pe-text" style="margin-top:.55rem" data-preview="about_story_p2"><?= Helpers::e($about['about_story_p2']) ?></p>
                                <p class="pe-text" style="margin-top:.55rem" data-preview="about_story_p3"><?= Helpers::e($about['about_story_p3']) ?></p>
                            </div>
                            <div class="pe-facts">
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <div class="pe-card">
                                        <strong data-preview="about_fact_<?= $i ?>_value"><?= Helpers::e($about["about_fact_{$i}_value"]) ?></strong>
                                        <span data-preview="about_fact_<?= $i ?>_label"><?= Helpers::e($about["about_fact_{$i}_label"]) ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="he-block pe-muted<?= !$isOn('about_pillars_enabled') ? ' is-hidden' : '' ?>" data-block="pillars">
                    <span class="he-block-label">Engagements</span>
                    <div class="pe-section">
                        <span class="pe-kicker" data-preview="about_pillars_label"><?= Helpers::e($about['about_pillars_label']) ?></span>
                        <h2 class="pe-title" data-preview="about_pillars_title"><?= Helpers::e($about['about_pillars_title']) ?></h2>
                        <div class="pe-pillars">
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <div class="pe-card">
                                    <strong data-preview="about_pillar_<?= $i ?>_title"><?= Helpers::e($about["about_pillar_{$i}_title"]) ?></strong>
                                    <p data-preview="about_pillar_<?= $i ?>_text"><?= Helpers::e($about["about_pillar_{$i}_text"]) ?></p>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </section>

                <section class="he-block<?= !$isOn('about_audience_enabled') ? ' is-hidden' : '' ?>" data-block="audience">
                    <span class="he-block-label">Publics</span>
                    <div class="pe-section">
                        <span class="pe-kicker" data-preview="about_audience_label"><?= Helpers::e($about['about_audience_label']) ?></span>
                        <h2 class="pe-title" data-preview="about_audience_title"><?= Helpers::e($about['about_audience_title']) ?></h2>
                        <div class="pe-pillars">
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <div class="pe-card">
                                    <strong data-preview="about_audience_<?= $i ?>_title"><?= Helpers::e($about["about_audience_{$i}_title"]) ?></strong>
                                    <p data-preview="about_audience_<?= $i ?>_text"><?= Helpers::e($about["about_audience_{$i}_text"]) ?></p>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </section>

                <section class="he-block<?= !$isOn('about_cta_enabled') ? ' is-hidden' : '' ?>" data-block="cta">
                    <span class="he-block-label">CTA</span>
                    <div class="pe-section pe-dark">
                        <span class="pe-kicker" data-preview="about_cta_label"><?= Helpers::e($about['about_cta_label']) ?></span>
                        <h2 class="pe-title" data-preview="about_cta_title"><?= Helpers::e($about['about_cta_title']) ?></h2>
                        <p class="pe-text" data-preview="about_cta_text"><?= Helpers::e($about['about_cta_text']) ?></p>
                        <div class="pe-actions">
                            <?php for ($i = 1; $i <= 3; $i++): ?>
                                <span class="<?= $i === 1 ? 'pe-btn' : 'pe-btn pe-btn-ghost' ?>" data-preview="about_cta_<?= $i ?>_label"><?= Helpers::e($about["about_cta_{$i}_label"]) ?></span>
                            <?php endfor; ?>
                        </div>
                    </div>
                </section>

                <section class="he-block<?= !$isOn('about_find_enabled') ? ' is-hidden' : '' ?>" data-block="find">
                    <span class="he-block-label">Coordonnées</span>
                    <div class="pe-section">
                        <span class="pe-kicker" data-preview="about_find_label"><?= Helpers::e($about['about_find_label']) ?></span>
                        <h2 class="pe-title" data-preview="about_find_title"><?= Helpers::e($about['about_find_title']) ?></h2>
                        <p class="pe-text">Adresse, téléphone, email et horaires issus des Infos du site.</p>
                    </div>
                </section>

                <section class="he-block<?= !$isOn('about_links_enabled') ? ' is-hidden' : '' ?>" data-block="links">
                    <span class="he-block-label">Ressources</span>
                    <div class="pe-section">
                        <span class="pe-kicker" data-preview="about_links_label"><?= Helpers::e($about['about_links_label']) ?></span>
                        <h2 class="pe-title" data-preview="about_links_title"><?= Helpers::e($about['about_links_title']) ?></h2>
                        <ul class="pe-list">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <li data-preview="about_link_<?= $i ?>_label"><?= Helpers::e($about["about_link_{$i}_label"]) ?></li>
                            <?php endfor; ?>
                        </ul>
                    </div>
                </section>
            </div>
        </div>

        <aside class="he-panel">
            <div class="he-panel-head">
                <strong data-panel-title>Hero</strong>
                <p data-panel-desc>En-tête de la page</p>
            </div>

            <div class="he-panel-section" data-panel="seo">
                <label>Titre SEO<input type="text" name="about_seo_title" value="<?= Helpers::e($about['about_seo_title']) ?>"></label>
                <label>Description SEO<textarea name="about_seo_description" rows="3"><?= Helpers::e($about['about_seo_description']) ?></textarea></label>
            </div>

            <div class="he-panel-section is-active" data-panel="hero">
                <label>Sur-titre<input type="text" name="about_hero_label" value="<?= Helpers::e($about['about_hero_label']) ?>" data-bind="about_hero_label"></label>
                <label>Titre<input type="text" name="about_hero_title" value="<?= Helpers::e($about['about_hero_title']) ?>" data-bind="about_hero_title"></label>
                <label>Chapô<textarea name="about_hero_lead" rows="3" data-bind="about_hero_lead"><?= Helpers::e($about['about_hero_lead']) ?></textarea></label>
            </div>

            <div class="he-panel-section" data-panel="story">
                <label class="he-switch"><input type="checkbox" name="about_story_enabled" value="1" <?= $isOn('about_story_enabled') ? 'checked' : '' ?> data-toggle-block="story"> Afficher la section</label>
                <label>Sur-titre<input type="text" name="about_story_label" value="<?= Helpers::e($about['about_story_label']) ?>" data-bind="about_story_label"></label>
                <label>Titre<input type="text" name="about_story_title" value="<?= Helpers::e($about['about_story_title']) ?>" data-bind="about_story_title"></label>
                <label>Paragraphe 1<textarea name="about_story_p1" rows="2" data-bind="about_story_p1"><?= Helpers::e($about['about_story_p1']) ?></textarea></label>
                <label>Paragraphe 2<textarea name="about_story_p2" rows="2" data-bind="about_story_p2"><?= Helpers::e($about['about_story_p2']) ?></textarea></label>
                <label>Paragraphe 3<textarea name="about_story_p3" rows="2" data-bind="about_story_p3"><?= Helpers::e($about['about_story_p3']) ?></textarea></label>
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <p class="he-field-label">Chiffre <?= $i ?></p>
                    <label>Valeur<input type="text" name="about_fact_<?= $i ?>_value" value="<?= Helpers::e($about["about_fact_{$i}_value"]) ?>" data-bind="about_fact_<?= $i ?>_value"></label>
                    <label>Légende<input type="text" name="about_fact_<?= $i ?>_label" value="<?= Helpers::e($about["about_fact_{$i}_label"]) ?>" data-bind="about_fact_<?= $i ?>_label"></label>
                <?php endfor; ?>
            </div>

            <div class="he-panel-section" data-panel="pillars">
                <label class="he-switch"><input type="checkbox" name="about_pillars_enabled" value="1" <?= $isOn('about_pillars_enabled') ? 'checked' : '' ?> data-toggle-block="pillars"> Afficher la section</label>
                <label>Sur-titre<input type="text" name="about_pillars_label" value="<?= Helpers::e($about['about_pillars_label']) ?>" data-bind="about_pillars_label"></label>
                <label>Titre<input type="text" name="about_pillars_title" value="<?= Helpers::e($about['about_pillars_title']) ?>" data-bind="about_pillars_title"></label>
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <p class="he-field-label">Engagement <?= $i ?></p>
                    <label>Titre<input type="text" name="about_pillar_<?= $i ?>_title" value="<?= Helpers::e($about["about_pillar_{$i}_title"]) ?>" data-bind="about_pillar_<?= $i ?>_title"></label>
                    <label>Texte<textarea name="about_pillar_<?= $i ?>_text" rows="2" data-bind="about_pillar_<?= $i ?>_text"><?= Helpers::e($about["about_pillar_{$i}_text"]) ?></textarea></label>
                <?php endfor; ?>
            </div>

            <div class="he-panel-section" data-panel="audience">
                <label class="he-switch"><input type="checkbox" name="about_audience_enabled" value="1" <?= $isOn('about_audience_enabled') ? 'checked' : '' ?> data-toggle-block="audience"> Afficher la section</label>
                <label>Sur-titre<input type="text" name="about_audience_label" value="<?= Helpers::e($about['about_audience_label']) ?>" data-bind="about_audience_label"></label>
                <label>Titre<input type="text" name="about_audience_title" value="<?= Helpers::e($about['about_audience_title']) ?>" data-bind="about_audience_title"></label>
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <p class="he-field-label">Public <?= $i ?></p>
                    <label>Titre<input type="text" name="about_audience_<?= $i ?>_title" value="<?= Helpers::e($about["about_audience_{$i}_title"]) ?>" data-bind="about_audience_<?= $i ?>_title"></label>
                    <label>Texte<textarea name="about_audience_<?= $i ?>_text" rows="2" data-bind="about_audience_<?= $i ?>_text"><?= Helpers::e($about["about_audience_{$i}_text"]) ?></textarea></label>
                <?php endfor; ?>
            </div>

            <div class="he-panel-section" data-panel="cta">
                <label class="he-switch"><input type="checkbox" name="about_cta_enabled" value="1" <?= $isOn('about_cta_enabled') ? 'checked' : '' ?> data-toggle-block="cta"> Afficher le bandeau</label>
                <label>Sur-titre<input type="text" name="about_cta_label" value="<?= Helpers::e($about['about_cta_label']) ?>" data-bind="about_cta_label"></label>
                <label>Titre<input type="text" name="about_cta_title" value="<?= Helpers::e($about['about_cta_title']) ?>" data-bind="about_cta_title"></label>
                <label>Texte<textarea name="about_cta_text" rows="2" data-bind="about_cta_text"><?= Helpers::e($about['about_cta_text']) ?></textarea></label>
                <?php for ($i = 1; $i <= 3; $i++): ?>
                    <p class="he-field-label">Bouton <?= $i ?></p>
                    <label>Libellé<input type="text" name="about_cta_<?= $i ?>_label" value="<?= Helpers::e($about["about_cta_{$i}_label"]) ?>" data-bind="about_cta_<?= $i ?>_label"></label>
                    <label>URL (ou whatsapp)<input type="text" name="about_cta_<?= $i ?>_url" value="<?= Helpers::e($about["about_cta_{$i}_url"]) ?>"></label>
                <?php endfor; ?>
            </div>

            <div class="he-panel-section" data-panel="find">
                <label class="he-switch"><input type="checkbox" name="about_find_enabled" value="1" <?= $isOn('about_find_enabled') ? 'checked' : '' ?> data-toggle-block="find"> Afficher la section</label>
                <p class="he-note-inline">Coordonnées gérées dans Infos du site.</p>
                <label>Sur-titre<input type="text" name="about_find_label" value="<?= Helpers::e($about['about_find_label']) ?>" data-bind="about_find_label"></label>
                <label>Titre<input type="text" name="about_find_title" value="<?= Helpers::e($about['about_find_title']) ?>" data-bind="about_find_title"></label>
            </div>

            <div class="he-panel-section" data-panel="links">
                <label class="he-switch"><input type="checkbox" name="about_links_enabled" value="1" <?= $isOn('about_links_enabled') ? 'checked' : '' ?> data-toggle-block="links"> Afficher la section</label>
                <label>Sur-titre<input type="text" name="about_links_label" value="<?= Helpers::e($about['about_links_label']) ?>" data-bind="about_links_label"></label>
                <label>Titre<input type="text" name="about_links_title" value="<?= Helpers::e($about['about_links_title']) ?>" data-bind="about_links_title"></label>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <p class="he-field-label">Lien <?= $i ?></p>
                    <label>Libellé<input type="text" name="about_link_<?= $i ?>_label" value="<?= Helpers::e($about["about_link_{$i}_label"]) ?>" data-bind="about_link_<?= $i ?>_label"></label>
                    <label>URL<input type="text" name="about_link_<?= $i ?>_url" value="<?= Helpers::e($about["about_link_{$i}_url"]) ?>"></label>
                <?php endfor; ?>
            </div>
        </aside>
    </div>
</form>

<script src="/assets/js/page-editor.js"></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
