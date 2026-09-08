<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireHome();

$pageTitle = 'Page d\'accueil';
$currentPage = 'home';
$builderMode = true;
require_once dirname(__DIR__, 2) . '/public/includes/trust-icons.php';

$errors = [];
$home = SettingRepository::home();
$fields = array_keys(SettingRepository::homeDefaults());
$toggles = SettingRepository::homeToggleKeys();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        try {
            $skipKeys = [
                'featured_product_ids',
                'bestsellers_product_ids',
                'header_order',
                'hero_media_type',
                'hero_image_url',
                'hero_image_url_mobile',
                'hero_video_url',
                'hero_video_url_mobile',
                'hero_video_poster',
                'featured_count',
                'bestsellers_count',
                'site_logo',
                'site_logo_mark',
                'site_favicon',
                'home_menu_items',
            ];
            $payload = [];
            foreach ($fields as $key) {
                if (in_array($key, $skipKeys, true)) {
                    continue;
                }
                if (in_array($key, $toggles, true)) {
                    $payload[$key] = isset($_POST[$key]) ? '1' : '0';
                } else {
                    $payload[$key] = trim((string) ($_POST[$key] ?? ''));
                }
            }

            $payload['header_order'] = implode(',', SettingRepository::parseHeaderOrder($_POST['header_order'] ?? 'logo,nav,search'));

            $featuredIds = array_values(array_filter(array_map('intval', (array) ($_POST['featured_product_ids'] ?? []))));
            $bestsellerIds = array_values(array_filter(array_map('intval', (array) ($_POST['bestsellers_product_ids'] ?? []))));
            $payload['featured_product_ids'] = implode(',', $featuredIds);
            $payload['bestsellers_product_ids'] = implode(',', $bestsellerIds);
            $payload['featured_count'] = (string) max(1, min(8, (int) ($_POST['featured_count'] ?? 2)));
            $payload['bestsellers_count'] = (string) max(1, min(16, (int) ($_POST['bestsellers_count'] ?? 8)));

            $resolveHeroMedia = static function (string $field, string $kind, ?string $existing): string {
                $path = FileUploader::processMediaField($field, 'home', $existing ?: null, $kind);
                if ($path) {
                    return $path;
                }
                return trim((string) ($_POST[$field . '_url_manual'] ?? ''));
            };

            $payload['hero_image_url'] = $resolveHeroMedia('hero_image', 'image', $home['hero_image_url'] ?: null);
            $payload['hero_image_url_mobile'] = $resolveHeroMedia('hero_image_mobile', 'image', $home['hero_image_url_mobile'] ?: null);
            $payload['hero_video_url'] = $resolveHeroMedia('hero_video', 'video', $home['hero_video_url'] ?: null);
            $payload['hero_video_url_mobile'] = $resolveHeroMedia('hero_video_mobile', 'video', $home['hero_video_url_mobile'] ?: null);
            $payload['hero_video_poster'] = (string) (FileUploader::processMediaField('hero_poster', 'home', $home['hero_video_poster'] ?: null, 'image') ?? '');

            $hasHeroVideo = $payload['hero_video_url'] !== '' || $payload['hero_video_url_mobile'] !== '';
            $hasHeroImage = $payload['hero_image_url'] !== '' || $payload['hero_image_url_mobile'] !== '' || $payload['hero_video_poster'] !== '';
            $payload['hero_media_type'] = $hasHeroVideo ? 'video' : ($hasHeroImage ? 'image' : 'none');

            $siteLogo = FileUploader::processMediaField('site_logo', 'home', $home['site_logo'] ?: null, 'image');
            $siteLogoMark = FileUploader::processMediaField('site_logo_mark', 'home', $home['site_logo_mark'] ?: null, 'image');
            $siteFavicon = FileUploader::processMediaField('site_favicon', 'home', $home['site_favicon'] ?: null, 'favicon');
            $payload['site_logo'] = (string) ($siteLogo ?? '');
            $payload['site_logo_mark'] = (string) ($siteLogoMark ?? '');
            $payload['site_favicon'] = (string) ($siteFavicon ?? '');

            // Menu catégories configurable
            $menuLabels = (array) ($_POST['menu_item_label'] ?? []);
            $menuCats = (array) ($_POST['menu_item_category'] ?? []);
            $menuIds = (array) ($_POST['menu_item_id'] ?? []);
            $menuVisibleKeys = array_map('strval', (array) ($_POST['menu_item_visible'] ?? []));
            $menuDraft = [];
            $count = max(count($menuLabels), count($menuCats), count($menuIds));
            for ($i = 0; $i < $count; $i++) {
                $itemId = trim((string) ($menuIds[$i] ?? ''));
                if ($itemId === '') {
                    $itemId = 'item-' . bin2hex(random_bytes(4));
                }
                $menuDraft[] = [
                    'id' => $itemId,
                    'label' => trim((string) ($menuLabels[$i] ?? '')),
                    'category_id' => (int) ($menuCats[$i] ?? 0),
                    'visible' => in_array($itemId, $menuVisibleKeys, true) ? 1 : 0,
                ];
            }
            $payload['home_menu_items'] = SettingRepository::encodeMenuItems($menuDraft);

            foreach (['home_blog_video_1', 'home_blog_video_2'] as $videoKey) {
                $raw = trim((string) ($payload[$videoKey] ?? ''));
                $embed = SitemapBuilder::youtubeEmbedUrl($raw);
                $payload[$videoKey] = $embed ?? $raw;
            }

            // Logos des marques (section accueil)
            foreach (BrandRepository::all() as $brandRow) {
                $bid = (int) $brandRow['id'];
                $logo = FileUploader::processField('brand_home_' . $bid, 'brands', $brandRow['logo'] ?? null);
                if ($logo !== ($brandRow['logo'] ?? null)) {
                    BrandRepository::updateLogo($bid, $logo);
                } elseif (array_key_exists('brand_home_' . $bid . '_current', $_POST)) {
                    // keep current via processField already
                }
            }

            SettingRepository::setMany($payload);

            AuditLog::record('home.update', 'home', null);
            Helpers::flash('success', 'Page d\'accueil enregistrée.');
            Helpers::redirect('/admin/home/index.php');
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$home = SettingRepository::home();
$isOn = static fn (string $key): bool => ($home[$key] ?? '0') === '1';
$headerOrder = SettingRepository::parseHeaderOrder($home['header_order'] ?? 'logo,nav,search');
$featuredSelected = SettingRepository::parseIdList($home['featured_product_ids'] ?? '');
$bestsellersSelected = SettingRepository::parseIdList($home['bestsellers_product_ids'] ?? '');
$pickerProducts = ProductRepository::listActiveForPicker(300);
$featuredPreview = $featuredSelected !== []
    ? ProductRepository::findByIdsOrdered($featuredSelected)
    : ProductRepository::featured((int) ($home['featured_count'] ?? 2));
$menuItems = SettingRepository::resolveMenuItems($home['home_menu_items'] ?? '');
$categoriesForSelect = CategoryRepository::allForSelect();
Database::initialize();
BrandRepository::syncFromProducts();
$homeBrands = BrandRepository::forHome();
$editorLogo = trim((string) ($home['site_logo'] ?? ''));
if ($editorLogo === '') {
    $editorLogo = '/assets/images/logo-photocom.png';
}
$editorLogoMark = trim((string) ($home['site_logo_mark'] ?? ''));
if ($editorLogoMark === '') {
    $editorLogoMark = '/assets/images/logo-admin.webp';
}
$editorFavicon = trim((string) ($home['site_favicon'] ?? ''));

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

<form method="post" class="he-builder" id="home-builder" data-home-builder enctype="multipart/form-data">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="header_order" id="header_order" value="<?= Helpers::e(implode(',', $headerOrder)) ?>">

    <div class="he-toolbar">
        <div class="he-toolbar-left">
            <strong>Éditeur page d'accueil</strong>
            <span class="he-hint">Cliquez une section du squelette pour la modifier</span>
        </div>
        <div class="he-toolbar-right">
            <a href="/" class="btn btn-outline" target="_blank" rel="noopener">Prévisualiser ↗</a>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </div>

    <div class="he-workspace">
        <aside class="he-structure" aria-label="Structure de la page">
            <p class="he-structure-title">Structure</p>
            <button type="button" class="he-structure-item is-active" data-select="promo">1 · Bandeau promo</button>
            <button type="button" class="he-structure-item" data-select="nav">2 · Navigation</button>
            <button type="button" class="he-structure-item" data-select="menu">3 · Menu catégories</button>
            <button type="button" class="he-structure-item" data-select="hero">4 · Hero</button>
            <button type="button" class="he-structure-item" data-select="featured">5 · Produits vedette</button>
            <button type="button" class="he-structure-item" data-select="conseils">6 · Conseils / Vidéos</button>
            <button type="button" class="he-structure-item" data-select="brands">7 · Marques</button>
            <button type="button" class="he-structure-item" data-select="bestsellers">8 · Meilleures ventes</button>
            <button type="button" class="he-structure-item" data-select="trust">9 · Confiance</button>
            <button type="button" class="he-structure-item" data-select="newsletter">10 · Newsletter</button>
            <button type="button" class="he-structure-item" data-select="social">11 · Réseaux sociaux</button>
        </aside>

        <div class="he-canvas-wrap">
            <div class="he-canvas" id="he-canvas"
                 data-hero-align="<?= Helpers::e($home['hero_align']) ?>"
                 data-hero-vertical="<?= Helpers::e($home['hero_vertical']) ?>"
                 data-hero-height="<?= Helpers::e($home['hero_height']) ?>">

                <section class="he-block <?= $isOn('promo_enabled') ? '' : 'is-hidden' ?>" data-block="promo">
                    <div class="he-block-label">Bandeau promo</div>
                    <div class="he-promo" data-preview="promo_text"><?= Helpers::e($home['promo_text']) ?></div>
                </section>

                <section class="he-block" data-block="nav">
                    <div class="he-block-label">Navigation</div>
                    <div class="he-nav" data-nav-align="<?= Helpers::e($home['nav_links_align']) ?>" data-mega-align="<?= Helpers::e($home['mega_nav_align']) ?>">
                        <div class="he-nav-main" id="he-nav-order-preview" data-order="<?= Helpers::e(implode(',', $headerOrder)) ?>">
                            <?php
                            $navParts = [
                                'logo' => '<span class="he-nav-logo" data-nav-part="logo"><img src="' . Helpers::e($editorLogo) . '" alt="Logo" class="he-nav-logo-img" id="he-nav-logo-img"></span>',
                                'nav' => '<span class="he-nav-links" data-nav-part="nav">Accueil · Catalogue · Contact</span>',
                                'search' => '<span class="he-nav-search ' . ($isOn('search_visible') ? '' : 'is-off') . '" data-nav-part="search">🔎</span>',
                            ];
                            foreach ($headerOrder as $part) {
                                echo $navParts[$part] ?? '';
                            }
                            ?>
                        </div>
                        <div class="he-nav-mega" id="he-menu-preview">
                            <?php
                            $visibleMenuLabels = [];
                            foreach ($menuItems as $item) {
                                if ((int) ($item['visible'] ?? 1) !== 1) {
                                    continue;
                                }
                                $label = trim((string) ($item['label'] ?? ''));
                                if ($label === '') {
                                    foreach ($categoriesForSelect as $opt) {
                                        if ((int) $opt['id'] === (int) $item['category_id']) {
                                            $label = (string) $opt['name'];
                                            break;
                                        }
                                    }
                                }
                                if ($label !== '') {
                                    $visibleMenuLabels[] = $label;
                                }
                            }
                            echo $visibleMenuLabels
                                ? Helpers::e(implode(' · ', $visibleMenuLabels))
                                : 'Aucun élément visible';
                            ?>
                        </div>
                    </div>
                </section>

                <section class="he-block" data-block="menu">
                    <div class="he-block-label">Menu catégories</div>
                    <div class="he-menu-cats" id="he-menu-cats-preview">
                        <?php foreach ($menuItems as $item): ?>
                            <?php
                            $chipLabel = trim((string) ($item['label'] ?? ''));
                            if ($chipLabel === '') {
                                foreach ($categoriesForSelect as $opt) {
                                    if ((int) $opt['id'] === (int) $item['category_id']) {
                                        $chipLabel = (string) $opt['name'];
                                        break;
                                    }
                                }
                            }
                            ?>
                            <span class="he-menu-chip <?= (int) ($item['visible'] ?? 1) === 1 ? '' : 'is-off' ?>">
                                <?= Helpers::e($chipLabel !== '' ? $chipLabel : 'Sans titre') ?>
                            </span>
                        <?php endforeach; ?>
                        <?php if (!$menuItems): ?>
                            <span class="he-note">Aucun élément de menu</span>
                        <?php endif; ?>
                    </div>
                    <p class="he-note">Texte libre, catégorie liée, ordre et nombre d’éléments au choix.</p>
                </section>

                <section class="he-block <?= $isOn('hero_enabled') ? '' : 'is-hidden' ?>" data-block="hero">
                    <div class="he-block-label">Hero</div>
                    <?php
                    $previewImage = trim((string) ($home['hero_image_url'] ?? ''))
                        ?: trim((string) ($home['hero_image_url_mobile'] ?? ''))
                        ?: trim((string) ($home['hero_video_poster'] ?? ''));
                    $hasPreviewVideo = trim((string) ($home['hero_video_url'] ?? '')) !== ''
                        || trim((string) ($home['hero_video_url_mobile'] ?? '')) !== '';
                    $heroBgStyle = $previewImage !== ''
                        ? 'background-image:url(' . Helpers::e($previewImage) . ');background-size:cover;background-position:center;'
                        : '';
                    $mediaBadge = match (true) {
                        $hasPreviewVideo && $previewImage !== '' => 'Vidéo + image de secours',
                        $hasPreviewVideo => 'Vidéo arrière-plan',
                        $previewImage !== '' => 'Image de secours',
                        default => 'Fond sombre — ajoutez image et vidéo',
                    };
                    ?>
                    <div class="he-hero" style="--he-overlay: <?= (int) $home['hero_overlay'] ?>%; <?= $heroBgStyle ?>">
                        <div class="he-hero-bg">
                            <span class="he-hero-media-badge" data-preview-video><?= Helpers::e($mediaBadge) ?></span>
                        </div>
                        <div class="he-hero-content">
                            <span class="he-hero-kicker" data-preview="hero_kicker"><?= Helpers::e($home['hero_kicker']) ?></span>
                            <h2 data-preview="hero_title"><?= Helpers::e($home['hero_title']) ?></h2>
                            <p class="<?= $isOn('hero_show_subtitle') ? '' : 'is-off' ?>" data-preview="hero_subtitle"><?= Helpers::e($home['hero_subtitle']) ?></p>
                            <span class="he-hero-cta" data-preview="hero_cta_label"><?= Helpers::e($home['hero_cta_label']) ?></span>
                        </div>
                    </div>
                </section>

                <section class="he-block <?= $isOn('featured_enabled') ? '' : 'is-hidden' ?>" data-block="featured" data-align="<?= Helpers::e($home['featured_align']) ?>">
                    <div class="he-block-label">Produits en vedette</div>
                    <div class="he-section-head">
                        <span data-preview="featured_label"><?= Helpers::e($home['featured_label']) ?></span>
                        <strong data-preview="featured_title"><?= Helpers::e($home['featured_title']) ?></strong>
                    </div>
                    <div class="he-cards he-cards-2" id="he-featured-preview">
                        <?php if ($featuredPreview): ?>
                            <?php foreach (array_slice($featuredPreview, 0, 4) as $product): ?>
                                <div class="he-card">
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="<?= Helpers::e($product['image']) ?>" alt="" class="he-card-thumb">
                                    <?php endif; ?>
                                    <strong><?= Helpers::e($product['name']) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="he-card">Choisissez des produits dans le panneau →</div>
                            <div class="he-card">Produit vedette</div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="he-block <?= $isOn('conseils_enabled') ? '' : 'is-hidden' ?>" data-block="conseils" data-align="<?= Helpers::e($home['conseils_align']) ?>">
                    <div class="he-block-label">Conseils / Vidéos</div>
                    <div class="he-section-head">
                        <span data-preview="conseils_label"><?= Helpers::e($home['conseils_label']) ?></span>
                        <strong data-preview="conseils_title"><?= Helpers::e($home['conseils_title']) ?></strong>
                        <em data-preview="conseils_subtitle"><?= Helpers::e($home['conseils_subtitle']) ?></em>
                    </div>
                    <div class="he-cards he-cards-2">
                        <div class="he-card he-card-video">
                            <strong data-preview="home_blog_title_1"><?= Helpers::e($home['home_blog_title_1']) ?></strong>
                            <small data-preview="home_blog_excerpt_1"><?= Helpers::e($home['home_blog_excerpt_1'] ?: 'Vidéo 1') ?></small>
                        </div>
                        <div class="he-card he-card-video">
                            <strong data-preview="home_blog_title_2"><?= Helpers::e($home['home_blog_title_2']) ?></strong>
                            <small data-preview="home_blog_excerpt_2"><?= Helpers::e($home['home_blog_excerpt_2'] ?: 'Vidéo 2') ?></small>
                        </div>
                    </div>
                </section>

                <section class="he-block <?= $isOn('brands_enabled') ? '' : 'is-hidden' ?>" data-block="brands" data-align="<?= Helpers::e($home['brands_align']) ?>">
                    <div class="he-block-label">Marques</div>
                    <div class="he-section-head">
                        <span data-preview="brands_label"><?= Helpers::e($home['brands_label']) ?></span>
                        <strong data-preview="brands_title"><?= Helpers::e($home['brands_title']) ?></strong>
                    </div>
                    <div class="he-brands he-brands-logos">
                        <?php foreach (array_slice($homeBrands, 0, 8) as $b): ?>
                            <?php if (!empty($b['logo'])): ?>
                                <span class="he-brand-logo-chip"><img src="<?= Helpers::e($b['logo']) ?>" alt="<?= Helpers::e($b['name']) ?>"></span>
                            <?php else: ?>
                                <span class="he-brand-logo-chip he-brand-logo-chip--text"><?= Helpers::e($b['name']) ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (!$homeBrands): ?>
                            <span class="he-note">Ajoutez des logos dans le panneau →</span>
                        <?php endif; ?>
                    </div>
                    <p class="he-note">Liste générée automatiquement depuis le catalogue</p>
                </section>

                <section class="he-block <?= $isOn('bestsellers_enabled') ? '' : 'is-hidden' ?>" data-block="bestsellers" data-align="<?= Helpers::e($home['bestsellers_align']) ?>">
                    <div class="he-block-label">Meilleures ventes</div>
                    <div class="he-section-head">
                        <span data-preview="bestsellers_label"><?= Helpers::e($home['bestsellers_label']) ?></span>
                        <strong data-preview="bestsellers_title"><?= Helpers::e($home['bestsellers_title']) ?></strong>
                    </div>
                    <div class="he-cards he-cards-4">
                        <div class="he-card">Produit</div>
                        <div class="he-card">Produit</div>
                        <div class="he-card">Produit</div>
                        <div class="he-card">Produit</div>
                    </div>
                    <div class="he-cta-preview" data-preview="catalogue_cta_label"><?= Helpers::e($home['catalogue_cta_label']) ?></div>
                </section>

                <section class="he-block <?= $isOn('trust_enabled') ? '' : 'is-hidden' ?>" data-block="trust">
                    <div class="he-block-label">Bandeau confiance</div>
                    <div class="he-trust">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <div class="he-trust-item">
                                <span class="he-trust-icon"><?= trustIcon($i, 22) ?></span>
                                <strong data-preview="trust_<?= $i ?>_title"><?= Helpers::e($home['trust_' . $i . '_title']) ?></strong>
                                <p data-preview="trust_<?= $i ?>_text"><?= Helpers::e($home['trust_' . $i . '_text']) ?></p>
                            </div>
                        <?php endfor; ?>
                    </div>
                </section>

                <section class="he-block <?= $isOn('newsletter_enabled') ? '' : 'is-hidden' ?>" data-block="newsletter" data-align="<?= Helpers::e($home['newsletter_align']) ?>">
                    <div class="he-block-label">Newsletter</div>
                    <div class="he-section-head">
                        <span data-preview="newsletter_label"><?= Helpers::e($home['newsletter_label']) ?></span>
                        <strong data-preview="newsletter_title"><?= Helpers::e($home['newsletter_title']) ?></strong>
                        <em data-preview="newsletter_text"><?= Helpers::e($home['newsletter_text']) ?></em>
                    </div>
                    <div class="he-cta-preview" data-preview="newsletter_button"><?= Helpers::e($home['newsletter_button']) ?></div>
                </section>

                <section class="he-block" data-block="social">
                    <div class="he-block-label">Réseaux sociaux</div>
                    <div class="he-social">
                        Facebook · Instagram · YouTube · LinkedIn
                    </div>
                </section>
            </div>
        </div>

        <aside class="he-panel" aria-label="Réglages de section">
            <div class="he-panel-head">
                <h2 data-panel-title>Bandeau promo</h2>
                <p data-panel-desc>Texte affiché en haut du site</p>
            </div>

            <!-- PROMO -->
            <div class="he-panel-section is-active" data-panel="promo">
                <label class="he-switch"><input type="checkbox" name="promo_enabled" value="1" <?= $isOn('promo_enabled') ? 'checked' : '' ?>> Afficher le bandeau</label>
                <label>Texte du bandeau
                    <input type="text" name="promo_text" value="<?= Helpers::e($home['promo_text']) ?>" data-bind="promo_text">
                </label>
            </div>

            <!-- NAV -->
            <div class="he-panel-section" data-panel="nav">
                <p class="he-field-label">Identité visuelle</p>
                <p class="he-note-inline">Logo header (site), logo secondaire (pied de page &amp; dashboard), favicon</p>

                <div class="he-brand-upload">
                    <div class="he-brand-card">
                        <p class="he-field-label">Logo header</p>
                        <div class="he-brand-preview" data-brand-preview="logo">
                            <img src="<?= Helpers::e($editorLogo) ?>" alt="Logo header" id="he-brand-logo-preview">
                        </div>
                        <label class="btn btn-sm btn-primary he-file-btn">
                            Ajouter le logo
                            <input type="file" name="site_logo_file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" data-brand-file="logo" hidden>
                        </label>
                        <input type="hidden" name="site_logo_current" value="<?= Helpers::e($home['site_logo'] ?? '') ?>">
                        <input type="hidden" name="site_logo_delete" value="0" id="site_logo_delete">
                        <?php if (($home['site_logo'] ?? '') !== ''): ?>
                            <button type="button" class="btn btn-sm" data-clear-brand="logo">Retirer le logo</button>
                        <?php endif; ?>
                    </div>

                    <div class="he-brand-card">
                        <p class="he-field-label">Logo footer / admin</p>
                        <div class="he-brand-preview" data-brand-preview="logo_mark">
                            <img src="<?= Helpers::e($editorLogoMark) ?>" alt="Logo secondaire" id="he-brand-logo_mark-preview">
                        </div>
                        <label class="btn btn-sm btn-primary he-file-btn">
                            Ajouter le logo
                            <input type="file" name="site_logo_mark_file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" data-brand-file="logo_mark" hidden>
                        </label>
                        <input type="hidden" name="site_logo_mark_current" value="<?= Helpers::e($home['site_logo_mark'] ?? '') ?>">
                        <input type="hidden" name="site_logo_mark_delete" value="0" id="site_logo_mark_delete">
                        <?php if (($home['site_logo_mark'] ?? '') !== ''): ?>
                            <button type="button" class="btn btn-sm" data-clear-brand="logo_mark">Retirer le logo</button>
                        <?php endif; ?>
                    </div>

                    <div class="he-brand-card">
                        <p class="he-field-label">Favicon</p>
                        <div class="he-brand-preview he-brand-preview--favicon" data-brand-preview="favicon">
                            <?php if ($editorFavicon !== ''): ?>
                                <img src="<?= Helpers::e($editorFavicon) ?>" alt="Favicon" id="he-brand-favicon-preview">
                            <?php else: ?>
                                <span class="he-brand-empty" id="he-brand-favicon-empty">Aucune</span>
                                <img src="" alt="Favicon" id="he-brand-favicon-preview" hidden>
                            <?php endif; ?>
                        </div>
                        <label class="btn btn-sm btn-primary he-file-btn">
                            Ajouter la favicon
                            <input type="file" name="site_favicon_file" accept="image/png,image/x-icon,image/vnd.microsoft.icon,.ico,image/svg+xml,image/webp,image/jpeg" data-brand-file="favicon" hidden>
                        </label>
                        <input type="hidden" name="site_favicon_current" value="<?= Helpers::e($home['site_favicon'] ?? '') ?>">
                        <input type="hidden" name="site_favicon_delete" value="0" id="site_favicon_delete">
                        <?php if (($home['site_favicon'] ?? '') !== ''): ?>
                            <button type="button" class="btn btn-sm" data-clear-brand="favicon">Retirer la favicon</button>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="he-field-label">Ordre des composants</p>
                <p class="he-note-inline">Glissez pour réordonner logo, liens et recherche</p>
                <ul class="he-order-list" id="header-order-list">
                    <?php
                    $labels = ['logo' => 'Logo', 'nav' => 'Liens de navigation', 'search' => 'Barre de recherche'];
                    foreach ($headerOrder as $part):
                    ?>
                        <li class="he-order-item" draggable="true" data-part="<?= Helpers::e($part) ?>">
                            <span class="he-drag">⋮⋮</span>
                            <span><?= Helpers::e($labels[$part] ?? $part) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <label class="he-switch"><input type="checkbox" name="logo_visible" value="1" <?= $isOn('logo_visible') ? 'checked' : '' ?>> Afficher le logo</label>
                <label class="he-switch"><input type="checkbox" name="nav_visible" value="1" <?= $isOn('nav_visible') ? 'checked' : '' ?>> Afficher les liens</label>
                <label class="he-switch"><input type="checkbox" name="search_visible" value="1" <?= $isOn('search_visible') ? 'checked' : '' ?>> Afficher la recherche</label>

                <p class="he-field-label">Alignement des liens</p>
                <div class="he-seg" data-seg="nav_links_align">
                    <label><input type="radio" name="nav_links_align" value="left" <?= $home['nav_links_align'] === 'left' ? 'checked' : '' ?>> Gauche</label>
                    <label><input type="radio" name="nav_links_align" value="center" <?= $home['nav_links_align'] === 'center' ? 'checked' : '' ?>> Centre</label>
                    <label><input type="radio" name="nav_links_align" value="right" <?= $home['nav_links_align'] === 'right' ? 'checked' : '' ?>> Droite</label>
                </div>
                <p class="he-field-label">Bande catégories</p>
                <div class="he-seg" data-seg="mega_nav_align">
                    <label><input type="radio" name="mega_nav_align" value="start" <?= $home['mega_nav_align'] === 'start' ? 'checked' : '' ?>> Gauche</label>
                    <label><input type="radio" name="mega_nav_align" value="stretch" <?= $home['mega_nav_align'] === 'stretch' ? 'checked' : '' ?>> Pleine largeur</label>
                    <label><input type="radio" name="mega_nav_align" value="center" <?= $home['mega_nav_align'] === 'center' ? 'checked' : '' ?>> Centre</label>
                </div>
            </div>

            <!-- MENU CATEGORIES -->
            <div class="he-panel-section" data-panel="menu">
                <p class="he-field-label">Éléments du mega menu</p>
                <p class="he-note-inline">Ajoutez autant d’entrées que vous voulez. Chaque bouton a un texte libre et une catégorie liée.</p>
                <ul class="he-order-list he-menu-list" id="menu-category-list">
                    <?php foreach ($menuItems as $item): ?>
                        <li class="he-order-item he-menu-item" draggable="true" data-menu-item>
                            <span class="he-drag">⋮⋮</span>
                            <label class="he-menu-vis" title="Visible">
                                <input type="checkbox" name="menu_item_visible[]" value="<?= Helpers::e($item['id']) ?>" <?= (int) $item['visible'] === 1 ? 'checked' : '' ?> data-menu-visible>
                            </label>
                            <input type="hidden" name="menu_item_id[]" value="<?= Helpers::e($item['id']) ?>">
                            <div class="he-menu-fields">
                                <input
                                    type="text"
                                    class="he-menu-name"
                                    name="menu_item_label[]"
                                    value="<?= Helpers::e($item['label']) ?>"
                                    placeholder="Texte du bouton"
                                    data-menu-label
                                >
                                <select name="menu_item_category[]" class="he-menu-cat" data-menu-category>
                                    <option value="">— Choisir une catégorie —</option>
                                    <?php foreach ($categoriesForSelect as $opt): ?>
                                        <option value="<?= (int) $opt['id'] ?>" <?= (int) $opt['id'] === (int) $item['category_id'] ? 'selected' : '' ?>>
                                            <?= Helpers::e($opt['label']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="button" class="he-menu-remove" data-menu-remove title="Supprimer">×</button>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <template id="menu-item-template">
                    <li class="he-order-item he-menu-item" draggable="true" data-menu-item>
                        <span class="he-drag">⋮⋮</span>
                        <label class="he-menu-vis" title="Visible">
                            <input type="checkbox" name="menu_item_visible[]" value="__ID__" checked data-menu-visible>
                        </label>
                        <input type="hidden" name="menu_item_id[]" value="__ID__">
                        <div class="he-menu-fields">
                            <input type="text" class="he-menu-name" name="menu_item_label[]" value="" placeholder="Texte du bouton" data-menu-label>
                            <select name="menu_item_category[]" class="he-menu-cat" data-menu-category>
                                <option value="">— Choisir une catégorie —</option>
                                <?php foreach ($categoriesForSelect as $opt): ?>
                                    <option value="<?= (int) $opt['id'] ?>"><?= Helpers::e($opt['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="button" class="he-menu-remove" data-menu-remove title="Supprimer">×</button>
                    </li>
                </template>

                <button type="button" class="btn btn-sm btn-primary" id="menu-item-add">Ajouter un élément</button>
                <?php if (!$categoriesForSelect): ?>
                    <p class="he-note-inline">Créez d’abord des catégories dans Catalogue → Catégories.</p>
                <?php endif; ?>
                <a href="/admin/categories/index.php" class="btn btn-outline" target="_blank" rel="noopener">Gérer toutes les catégories</a>
            </div>

            <!-- HERO -->
            <div class="he-panel-section" data-panel="hero">
                <label class="he-switch"><input type="checkbox" name="hero_enabled" value="1" <?= $isOn('hero_enabled') ? 'checked' : '' ?>> Afficher le hero</label>

                <p class="he-field-label">Médias d’arrière-plan</p>
                <p class="he-note-inline">L’image s’affiche tout de suite. La vidéo se charge ensuite ; si la connexion est trop lente (ou après 10 s), le visiteur reste sur l’image.</p>
                <?php
                $heroMediaSlots = [
                    [
                        'title' => 'Desktop',
                        'hint' => 'Écrans larges (≥ 901 px)',
                        'video_field' => 'hero_video',
                        'video_url' => (string) ($home['hero_video_url'] ?? ''),
                        'image_field' => 'hero_image',
                        'image_url' => (string) ($home['hero_image_url'] ?? ''),
                    ],
                    [
                        'title' => 'Mobile',
                        'hint' => 'Téléphones et tablettes (≤ 900 px)',
                        'video_field' => 'hero_video_mobile',
                        'video_url' => (string) ($home['hero_video_url_mobile'] ?? ''),
                        'image_field' => 'hero_image_mobile',
                        'image_url' => (string) ($home['hero_image_url_mobile'] ?? ''),
                    ],
                ];
                ?>
                <div class="he-hero-slots">
                    <?php foreach ($heroMediaSlots as $slot): ?>
                        <div class="he-hero-slot">
                            <strong class="he-hero-slot-title"><?= Helpers::e($slot['title']) ?></strong>
                            <p class="he-note-inline"><?= Helpers::e($slot['hint']) ?></p>

                            <label>Vidéo MP4 / WebM (max 50 Mo)
                                <input type="file" name="<?= Helpers::e($slot['video_field']) ?>_file" accept="video/mp4,video/webm">
                            </label>
                            <input type="hidden" name="<?= Helpers::e($slot['video_field']) ?>_current" value="<?= Helpers::e($slot['video_url']) ?>">
                            <input type="hidden" name="<?= Helpers::e($slot['video_field']) ?>_delete" value="0" id="<?= Helpers::e($slot['video_field']) ?>_delete">
                            <?php if ($slot['video_url'] !== ''): ?>
                                <p class="he-note-inline" data-current-file="<?= Helpers::e($slot['video_field']) ?>">Fichier actuel : <?= Helpers::e(basename($slot['video_url'])) ?></p>
                                <button type="button" class="btn btn-sm" data-clear-media="<?= Helpers::e($slot['video_field']) ?>">Supprimer la vidéo</button>
                            <?php endif; ?>
                            <label>Ou URL vidéo
                                <input type="url" name="<?= Helpers::e($slot['video_field']) ?>_url_manual" value="<?= Helpers::e(str_starts_with($slot['video_url'], 'http') ? $slot['video_url'] : '') ?>" placeholder="https://…/video.mp4">
                            </label>

                            <label>Image de secours (JPG/PNG/WebP, max 5 Mo)
                                <input type="file" name="<?= Helpers::e($slot['image_field']) ?>_file" accept="image/jpeg,image/png,image/gif,image/webp" data-hero-image-preview>
                            </label>
                            <input type="hidden" name="<?= Helpers::e($slot['image_field']) ?>_current" value="<?= Helpers::e($slot['image_url']) ?>">
                            <input type="hidden" name="<?= Helpers::e($slot['image_field']) ?>_delete" value="0" id="<?= Helpers::e($slot['image_field']) ?>_delete">
                            <?php if ($slot['image_url'] !== ''): ?>
                                <div class="he-media-preview" data-preview-wrap="<?= Helpers::e($slot['image_field']) ?>">
                                    <img src="<?= Helpers::e($slot['image_url']) ?>" alt="">
                                </div>
                                <button type="button" class="btn btn-sm" data-clear-media="<?= Helpers::e($slot['image_field']) ?>">Supprimer l’image</button>
                            <?php endif; ?>
                            <label>Ou URL image
                                <input type="url" name="<?= Helpers::e($slot['image_field']) ?>_url_manual" value="<?= Helpers::e(str_starts_with($slot['image_url'], 'http') ? $slot['image_url'] : '') ?>" placeholder="https://…">
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <label class="he-switch"><input type="checkbox" name="hero_loop" value="1" <?= $isOn('hero_loop') ? 'checked' : '' ?>> Lire la vidéo en boucle (sans arrêt)</label>

                <label>Sur-titre
                    <input type="text" name="hero_kicker" value="<?= Helpers::e($home['hero_kicker']) ?>" data-bind="hero_kicker">
                </label>
                <label>Titre
                    <input type="text" name="hero_title" value="<?= Helpers::e($home['hero_title']) ?>" data-bind="hero_title">
                </label>
                <label class="he-switch"><input type="checkbox" name="hero_show_subtitle" value="1" <?= $isOn('hero_show_subtitle') ? 'checked' : '' ?>> Afficher le sous-titre</label>
                <label>Sous-titre
                    <input type="text" name="hero_subtitle" value="<?= Helpers::e($home['hero_subtitle']) ?>" data-bind="hero_subtitle">
                </label>
                <div class="he-row">
                    <label>Bouton
                        <input type="text" name="hero_cta_label" value="<?= Helpers::e($home['hero_cta_label']) ?>" data-bind="hero_cta_label">
                    </label>
                    <label>Lien
                        <input type="text" name="hero_cta_url" value="<?= Helpers::e($home['hero_cta_url']) ?>">
                    </label>
                </div>
                <p class="he-field-label">Alignement horizontal du texte</p>
                <div class="he-seg" data-seg="hero_align">
                    <label><input type="radio" name="hero_align" value="left" <?= $home['hero_align'] === 'left' ? 'checked' : '' ?>> Gauche</label>
                    <label><input type="radio" name="hero_align" value="center" <?= $home['hero_align'] === 'center' ? 'checked' : '' ?>> Centre</label>
                    <label><input type="radio" name="hero_align" value="right" <?= $home['hero_align'] === 'right' ? 'checked' : '' ?>> Droite</label>
                </div>
                <p class="he-field-label">Position verticale</p>
                <div class="he-seg" data-seg="hero_vertical">
                    <label><input type="radio" name="hero_vertical" value="top" <?= $home['hero_vertical'] === 'top' ? 'checked' : '' ?>> Haut</label>
                    <label><input type="radio" name="hero_vertical" value="center" <?= $home['hero_vertical'] === 'center' ? 'checked' : '' ?>> Milieu</label>
                    <label><input type="radio" name="hero_vertical" value="bottom" <?= $home['hero_vertical'] === 'bottom' ? 'checked' : '' ?>> Bas</label>
                </div>
                <p class="he-field-label">Hauteur</p>
                <div class="he-seg" data-seg="hero_height">
                    <label><input type="radio" name="hero_height" value="small" <?= $home['hero_height'] === 'small' ? 'checked' : '' ?>> S</label>
                    <label><input type="radio" name="hero_height" value="medium" <?= $home['hero_height'] === 'medium' ? 'checked' : '' ?>> M</label>
                    <label><input type="radio" name="hero_height" value="large" <?= $home['hero_height'] === 'large' ? 'checked' : '' ?>> L</label>
                </div>
                <label>Opacité overlay (0–90)
                    <input type="range" name="hero_overlay" min="0" max="90" value="<?= Helpers::e($home['hero_overlay']) ?>" data-bind-overlay>
                </label>
            </div>

            <!-- FEATURED -->
            <div class="he-panel-section" data-panel="featured">
                <label class="he-switch"><input type="checkbox" name="featured_enabled" value="1" <?= $isOn('featured_enabled') ? 'checked' : '' ?>> Afficher la section</label>
                <label>Label<input type="text" name="featured_label" value="<?= Helpers::e($home['featured_label']) ?>" data-bind="featured_label"></label>
                <label>Titre<input type="text" name="featured_title" value="<?= Helpers::e($home['featured_title']) ?>" data-bind="featured_title"></label>
                <label>Nombre de produits
                    <input type="number" name="featured_count" min="1" max="8" value="<?= Helpers::e($home['featured_count'] ?? '2') ?>">
                </label>
                <p class="he-field-label">Alignement du titre</p>
                <div class="he-seg">
                    <label><input type="radio" name="featured_align" value="left" <?= $home['featured_align'] === 'left' ? 'checked' : '' ?>> Gauche</label>
                    <label><input type="radio" name="featured_align" value="center" <?= $home['featured_align'] === 'center' ? 'checked' : '' ?>> Centre</label>
                    <label><input type="radio" name="featured_align" value="right" <?= $home['featured_align'] === 'right' ? 'checked' : '' ?>> Droite</label>
                </div>

                <p class="he-field-label">Choisir les produits</p>
                <div
                    class="admin-search-suggest-wrap he-product-search-wrap"
                    data-search-suggest
                    data-suggest-api="/admin/produits/suggest.php"
                    data-suggest-link="picker"
                    data-suggest-picker="featured"
                    data-suggest-all-href="/admin/produits/index.php"
                    data-suggest-all-param="search"
                >
                    <input
                        type="search"
                        class="he-product-search"
                        placeholder="Rechercher un produit…"
                        data-product-search="featured"
                        data-search-input
                        autocomplete="off"
                        aria-autocomplete="list"
                        aria-expanded="false"
                    >
                    <div class="header-search-suggest" data-search-results hidden role="listbox" aria-label="Suggestions produits"></div>
                </div>
                <div class="he-product-picker" data-product-picker="featured">
                    <?php foreach ($pickerProducts as $product): ?>
                        <?php $checked = in_array((int) $product['id'], $featuredSelected, true); ?>
                        <label class="he-product-option">
                            <input type="checkbox" name="featured_product_ids[]" value="<?= (int) $product['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                            <?php if (!empty($product['image'])): ?>
                                <img src="<?= Helpers::e($product['image']) ?>" alt="">
                            <?php else: ?>
                                <span class="he-product-ph">PC</span>
                            <?php endif; ?>
                            <span>
                                <strong><?= Helpers::e($product['name']) ?></strong>
                                <small><?= Helpers::e(($product['brand'] ?: '—') . ' · ' . Helpers::formatPrice((float) $product['price'])) ?></small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="he-note-inline">Si aucun produit n’est coché, les produits marqués « Vedette » dans le catalogue sont utilisés.</p>
            </div>

            <!-- CONSEILS -->
            <div class="he-panel-section" data-panel="conseils">
                <label class="he-switch"><input type="checkbox" name="conseils_enabled" value="1" <?= $isOn('conseils_enabled') ? 'checked' : '' ?>> Afficher la section</label>
                <label>Label<input type="text" name="conseils_label" value="<?= Helpers::e($home['conseils_label']) ?>" data-bind="conseils_label"></label>
                <label>Titre<input type="text" name="conseils_title" value="<?= Helpers::e($home['conseils_title']) ?>" data-bind="conseils_title"></label>
                <label>Sous-titre<input type="text" name="conseils_subtitle" value="<?= Helpers::e($home['conseils_subtitle']) ?>" data-bind="conseils_subtitle"></label>
                <p class="he-field-label">Alignement</p>
                <div class="he-seg">
                    <label><input type="radio" name="conseils_align" value="left" <?= $home['conseils_align'] === 'left' ? 'checked' : '' ?>> Gauche</label>
                    <label><input type="radio" name="conseils_align" value="center" <?= $home['conseils_align'] === 'center' ? 'checked' : '' ?>> Centre</label>
                    <label><input type="radio" name="conseils_align" value="right" <?= $home['conseils_align'] === 'right' ? 'checked' : '' ?>> Droite</label>
                </div>
                <hr class="he-sep">
                <p class="he-field-label">Vidéo 1</p>
                <label>Lien YouTube<input type="url" name="home_blog_video_1" value="<?= Helpers::e($home['home_blog_video_1']) ?>" placeholder="https://www.youtube.com/watch?v=…"></label>
                <p class="he-note-inline">Collez n’importe quel lien YouTube (watch, youtu.be, Shorts ou embed). L’aperçu s’affiche automatiquement.</p>
                <label>Titre<input type="text" name="home_blog_title_1" value="<?= Helpers::e($home['home_blog_title_1']) ?>" data-bind="home_blog_title_1"></label>
                <label>Description<input type="text" name="home_blog_excerpt_1" value="<?= Helpers::e($home['home_blog_excerpt_1']) ?>" data-bind="home_blog_excerpt_1"></label>
                <p class="he-field-label">Vidéo 2</p>
                <label>Lien YouTube<input type="url" name="home_blog_video_2" value="<?= Helpers::e($home['home_blog_video_2']) ?>" placeholder="https://www.youtube.com/watch?v=…"></label>
                <label>Titre<input type="text" name="home_blog_title_2" value="<?= Helpers::e($home['home_blog_title_2']) ?>" data-bind="home_blog_title_2"></label>
                <label>Description<input type="text" name="home_blog_excerpt_2" value="<?= Helpers::e($home['home_blog_excerpt_2']) ?>" data-bind="home_blog_excerpt_2"></label>
            </div>

            <!-- BRANDS -->
            <div class="he-panel-section" data-panel="brands">
                <label class="he-switch"><input type="checkbox" name="brands_enabled" value="1" <?= $isOn('brands_enabled') ? 'checked' : '' ?>> Afficher la section</label>
                <label>Label<input type="text" name="brands_label" value="<?= Helpers::e($home['brands_label']) ?>" data-bind="brands_label"></label>
                <label>Titre<input type="text" name="brands_title" value="<?= Helpers::e($home['brands_title']) ?>" data-bind="brands_title"></label>
                <p class="he-field-label">Alignement</p>
                <div class="he-seg">
                    <label><input type="radio" name="brands_align" value="left" <?= $home['brands_align'] === 'left' ? 'checked' : '' ?>> Gauche</label>
                    <label><input type="radio" name="brands_align" value="center" <?= $home['brands_align'] === 'center' ? 'checked' : '' ?>> Centre</label>
                    <label><input type="radio" name="brands_align" value="right" <?= $home['brands_align'] === 'right' ? 'checked' : '' ?>> Droite</label>
                </div>
                <p class="he-field-label">Logos des marques</p>
                <p class="he-note-inline">Uploadez le logo de chaque marque. Les blocs restent cliquables vers le catalogue filtré.</p>
                <div class="he-brand-logos-list">
                    <?php foreach ($homeBrands as $brandRow): ?>
                        <?php $bid = (int) $brandRow['id']; ?>
                        <div class="he-brand-logo-row">
                            <strong><?= Helpers::e($brandRow['name']) ?></strong>
                            <?php if (!empty($brandRow['logo'])): ?>
                                <img src="<?= Helpers::e($brandRow['logo']) ?>" alt="" class="he-brand-logo-thumb">
                            <?php endif; ?>
                            <input type="file" name="brand_home_<?= $bid ?>_file" accept="image/jpeg,image/png,image/webp,image/svg+xml,image/gif">
                            <input type="hidden" name="brand_home_<?= $bid ?>_current" value="<?= Helpers::e((string) ($brandRow['logo'] ?? '')) ?>">
                            <input type="hidden" name="brand_home_<?= $bid ?>_delete" value="0">
                            <?php if (!empty($brandRow['logo'])): ?>
                                <label class="he-switch compact"><input type="checkbox" onchange="this.form.querySelector('[name=brand_home_<?= $bid ?>_delete]').value=this.checked?'1':'0'"> Retirer</label>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (!$homeBrands): ?>
                    <p class="he-note-inline">Aucune marque. Créez-en depuis Catalogue → Marques ou depuis un produit.</p>
                <?php endif; ?>
                <a href="/admin/marques/index.php" class="btn btn-outline" target="_blank" rel="noopener">Gérer les marques</a>
            </div>

            <!-- BESTSELLERS -->
            <div class="he-panel-section" data-panel="bestsellers">
                <label class="he-switch"><input type="checkbox" name="bestsellers_enabled" value="1" <?= $isOn('bestsellers_enabled') ? 'checked' : '' ?>> Afficher la section</label>
                <label>Label<input type="text" name="bestsellers_label" value="<?= Helpers::e($home['bestsellers_label']) ?>" data-bind="bestsellers_label"></label>
                <label>Titre<input type="text" name="bestsellers_title" value="<?= Helpers::e($home['bestsellers_title']) ?>" data-bind="bestsellers_title"></label>
                <label>Bouton catalogue<input type="text" name="catalogue_cta_label" value="<?= Helpers::e($home['catalogue_cta_label']) ?>" data-bind="catalogue_cta_label"></label>
                <label>Nombre de produits
                    <input type="number" name="bestsellers_count" min="1" max="16" value="<?= Helpers::e($home['bestsellers_count'] ?? '8') ?>">
                </label>
                <p class="he-field-label">Alignement</p>
                <div class="he-seg">
                    <label><input type="radio" name="bestsellers_align" value="left" <?= $home['bestsellers_align'] === 'left' ? 'checked' : '' ?>> Gauche</label>
                    <label><input type="radio" name="bestsellers_align" value="center" <?= $home['bestsellers_align'] === 'center' ? 'checked' : '' ?>> Centre</label>
                    <label><input type="radio" name="bestsellers_align" value="right" <?= $home['bestsellers_align'] === 'right' ? 'checked' : '' ?>> Droite</label>
                </div>
                <p class="he-field-label">Choisir les produits (optionnel)</p>
                <div
                    class="admin-search-suggest-wrap he-product-search-wrap"
                    data-search-suggest
                    data-suggest-api="/admin/produits/suggest.php"
                    data-suggest-link="picker"
                    data-suggest-picker="bestsellers"
                    data-suggest-all-href="/admin/produits/index.php"
                    data-suggest-all-param="search"
                >
                    <input
                        type="search"
                        class="he-product-search"
                        placeholder="Rechercher un produit…"
                        data-product-search="bestsellers"
                        data-search-input
                        autocomplete="off"
                        aria-autocomplete="list"
                        aria-expanded="false"
                    >
                    <div class="header-search-suggest" data-search-results hidden role="listbox" aria-label="Suggestions produits"></div>
                </div>
                <div class="he-product-picker" data-product-picker="bestsellers">
                    <?php foreach ($pickerProducts as $product): ?>
                        <?php $checked = in_array((int) $product['id'], $bestsellersSelected, true); ?>
                        <label class="he-product-option">
                            <input type="checkbox" name="bestsellers_product_ids[]" value="<?= (int) $product['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                            <?php if (!empty($product['image'])): ?>
                                <img src="<?= Helpers::e($product['image']) ?>" alt="">
                            <?php else: ?>
                                <span class="he-product-ph">PC</span>
                            <?php endif; ?>
                            <span>
                                <strong><?= Helpers::e($product['name']) ?></strong>
                                <small><?= Helpers::e(($product['brand'] ?: '—') . ' · ' . Helpers::formatPrice((float) $product['price'])) ?></small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="he-note-inline">Si aucun produit n’est coché, la sélection automatique du catalogue est utilisée.</p>
            </div>

            <!-- TRUST -->
            <div class="he-panel-section" data-panel="trust">
                <label class="he-switch"><input type="checkbox" name="trust_enabled" value="1" <?= $isOn('trust_enabled') ? 'checked' : '' ?>> Afficher la section</label>
                <p class="he-note-inline">Icônes automatiques : Livraison, Retours, Commander, Garantie.</p>
                <?php
                $trustLabels = [1 => 'Livraison', 2 => 'Retours', 3 => 'Commander', 4 => 'Garantie'];
                for ($i = 1; $i <= 4; $i++):
                ?>
                    <p class="he-field-label">
                        <span class="he-trust-icon he-trust-icon--sm"><?= trustIcon($i, 16) ?></span>
                        Bloc <?= $i ?> — <?= Helpers::e($trustLabels[$i]) ?>
                    </p>
                    <label>Titre<input type="text" name="trust_<?= $i ?>_title" value="<?= Helpers::e($home['trust_' . $i . '_title']) ?>" data-bind="trust_<?= $i ?>_title"></label>
                    <label>Texte<textarea name="trust_<?= $i ?>_text" rows="2" data-bind="trust_<?= $i ?>_text"><?= Helpers::e($home['trust_' . $i . '_text']) ?></textarea></label>
                    <div class="he-row">
                        <label>Libellé du lien
                            <input type="text" name="trust_<?= $i ?>_link_label" value="<?= Helpers::e($home['trust_' . $i . '_link_label']) ?>" placeholder="En savoir plus">
                        </label>
                        <label>URL
                            <input type="text" name="trust_<?= $i ?>_link_url" value="<?= Helpers::e($home['trust_' . $i . '_link_url']) ?>" placeholder="/page/...">
                        </label>
                    </div>
                <?php endfor; ?>
            </div>

            <!-- NEWSLETTER -->
            <div class="he-panel-section" data-panel="newsletter">
                <label class="he-switch"><input type="checkbox" name="newsletter_enabled" value="1" <?= $isOn('newsletter_enabled') ? 'checked' : '' ?>> Afficher la section</label>
                <label>Label<input type="text" name="newsletter_label" value="<?= Helpers::e($home['newsletter_label']) ?>" data-bind="newsletter_label"></label>
                <label>Titre<input type="text" name="newsletter_title" value="<?= Helpers::e($home['newsletter_title']) ?>" data-bind="newsletter_title"></label>
                <label>Texte<input type="text" name="newsletter_text" value="<?= Helpers::e($home['newsletter_text']) ?>" data-bind="newsletter_text"></label>
                <label>Bouton<input type="text" name="newsletter_button" value="<?= Helpers::e($home['newsletter_button']) ?>" data-bind="newsletter_button"></label>
                <p class="he-field-label">Alignement</p>
                <div class="he-seg">
                    <label><input type="radio" name="newsletter_align" value="left" <?= $home['newsletter_align'] === 'left' ? 'checked' : '' ?>> Gauche</label>
                    <label><input type="radio" name="newsletter_align" value="center" <?= $home['newsletter_align'] === 'center' ? 'checked' : '' ?>> Centre</label>
                    <label><input type="radio" name="newsletter_align" value="right" <?= $home['newsletter_align'] === 'right' ? 'checked' : '' ?>> Droite</label>
                </div>
            </div>

            <!-- SOCIAL -->
            <div class="he-panel-section" data-panel="social">
                <label>Facebook<input type="url" name="social_facebook" value="<?= Helpers::e($home['social_facebook']) ?>"></label>
                <label>Instagram<input type="url" name="social_instagram" value="<?= Helpers::e($home['social_instagram']) ?>"></label>
                <label>YouTube<input type="url" name="social_youtube" value="<?= Helpers::e($home['social_youtube']) ?>"></label>
                <label>LinkedIn<input type="url" name="social_linkedin" value="<?= Helpers::e($home['social_linkedin']) ?>"></label>
            </div>
        </aside>
    </div>
</form>

<script src="/assets/js/home-editor.js?v=<?= is_file(dirname(__DIR__, 2) . '/public/assets/js/home-editor.js') ? filemtime(dirname(__DIR__, 2) . '/public/assets/js/home-editor.js') : time() ?>"></script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
