<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();

$pageTitle = 'Informations du site';
$currentPage = 'settings';
$errors = [];
$settings = SettingRepository::site();

$siteKeys = [
    'site_name', 'site_url', 'site_tagline', 'site_locale', 'site_country', 'site_city',
    'phone', 'email', 'whatsapp', 'address', 'address_city', 'address_postal', 'address_country',
    'hours', 'founded', 'geo_latitude', 'geo_longitude',
    'seo_title', 'seo_description', 'seo_keywords', 'seo_robots',
    'seo_google_verification', 'seo_bing_verification', 'seo_organization_type',
    'analytics_enabled', 'analytics_ga4_id', 'analytics_gtm_id', 'seo_robots_txt_extra',
    'social_facebook', 'social_instagram', 'social_youtube', 'social_linkedin', 'social_tiktok', 'social_twitter',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        $payload = [];
        foreach ($siteKeys as $key) {
            if ($key === 'analytics_enabled') {
                $payload[$key] = isset($_POST['analytics_enabled']) ? '1' : '0';
                continue;
            }
            $payload[$key] = trim((string) ($_POST[$key] ?? ''));
        }

        $ga4 = $payload['analytics_ga4_id'] ?? '';
        if ($ga4 !== '' && !preg_match('/^G-[A-Z0-9]+$/i', $ga4)) {
            $errors[] = 'ID Google Analytics 4 invalide (format attendu : G-XXXXXXXX).';
        }
        $gtm = $payload['analytics_gtm_id'] ?? '';
        if ($gtm !== '' && !preg_match('/^GTM-[A-Z0-9]+$/i', $gtm)) {
            $errors[] = 'ID Google Tag Manager invalide (format attendu : GTM-XXXXXXX).';
        }

        if ($errors === []) {
            SettingRepository::setMany($payload);
            AuditLog::record('settings.site', 'settings', null);
            Helpers::flash('success', 'Informations du site enregistrées.');
            Helpers::redirect('/admin/settings/site.php');
        }
    }
}

$settings = SettingRepository::site();
$publicBase = rtrim((string) ($settings['site_url'] ?: Helpers::config()['url']), '/');
$sitemapUrl = $publicBase . '/sitemap.xml';
$robotsUrl = $publicBase . '/robots.txt';

require dirname(__DIR__) . '/includes/header.php';
?>

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success"><?= Helpers::e($message) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $error): ?>
            <p><?= Helpers::e($error) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="panel">
    <p class="text-muted">Identité, contact, SEO Google et signaux pour les recherches IA (organisation, localisation, réseaux).</p>

    <form method="post" class="form-grid">
        <?= Auth::csrfField() ?>

        <h3 class="form-section">Identité</h3>
        <div class="form-row">
            <label>Nom du site
                <input type="text" name="site_name" value="<?= Helpers::e($settings['site_name']) ?>" required>
            </label>
            <label>URL du site
                <input type="url" name="site_url" value="<?= Helpers::e($settings['site_url']) ?>" placeholder="https://…">
            </label>
        </div>
        <label>Slogan / description courte
            <input type="text" name="site_tagline" value="<?= Helpers::e($settings['site_tagline']) ?>">
        </label>
        <div class="form-row">
            <label>Langue (locale)
                <input type="text" name="site_locale" value="<?= Helpers::e($settings['site_locale']) ?>" placeholder="fr-MA">
            </label>
            <label>Pays
                <input type="text" name="site_country" value="<?= Helpers::e($settings['site_country']) ?>" placeholder="Maroc">
            </label>
            <label>Ville principale
                <input type="text" name="site_city" value="<?= Helpers::e($settings['site_city']) ?>" placeholder="Casablanca">
            </label>
        </div>

        <h3 class="form-section">Contact &amp; adresse</h3>
        <div class="form-row">
            <label>Téléphone
                <input type="text" name="phone" value="<?= Helpers::e($settings['phone']) ?>">
            </label>
            <label>Email
                <input type="email" name="email" value="<?= Helpers::e($settings['email']) ?>">
            </label>
            <label>WhatsApp (international)
                <input type="text" name="whatsapp" value="<?= Helpers::e($settings['whatsapp']) ?>">
            </label>
        </div>
        <label>Adresse complète
            <input type="text" name="address" value="<?= Helpers::e($settings['address']) ?>">
        </label>
        <div class="form-row">
            <label>Ville
                <input type="text" name="address_city" value="<?= Helpers::e($settings['address_city']) ?>">
            </label>
            <label>Code postal
                <input type="text" name="address_postal" value="<?= Helpers::e($settings['address_postal']) ?>">
            </label>
            <label>Pays (adresse)
                <input type="text" name="address_country" value="<?= Helpers::e($settings['address_country']) ?>">
            </label>
        </div>
        <label>Horaires
            <textarea name="hours" rows="4" placeholder="Une plage par ligne"><?= Helpers::e($settings['hours']) ?></textarea>
            <small class="field-hint">Plusieurs lignes possibles (semaine, samedi, Ramadan…).</small>
        </label>
        <div class="form-row">
            <label>Année de création
                <input type="text" name="founded" value="<?= Helpers::e($settings['founded']) ?>">
            </label>
        </div>
        <div class="form-row">
            <label>Latitude (GEO)
                <input type="text" name="geo_latitude" value="<?= Helpers::e($settings['geo_latitude']) ?>" placeholder="33.5731">
            </label>
            <label>Longitude (GEO)
                <input type="text" name="geo_longitude" value="<?= Helpers::e($settings['geo_longitude']) ?>" placeholder="-7.5898">
            </label>
        </div>

        <h3 class="form-section">SEO Google &amp; recherches IA</h3>
        <label>Titre SEO par défaut
            <input type="text" name="seo_title" value="<?= Helpers::e($settings['seo_title']) ?>" maxlength="70" placeholder="PHOTOCOM — Matériel photo & vidéo à Casablanca">
        </label>
        <label>Meta description par défaut
            <textarea name="seo_description" rows="3" maxlength="170"><?= Helpers::e($settings['seo_description']) ?></textarea>
            <small class="field-hint">Utilisée sur l’accueil et les pages sans description dédiée.</small>
        </label>
        <label>Mots-clés (séparés par des virgules)
            <input type="text" name="seo_keywords" value="<?= Helpers::e($settings['seo_keywords']) ?>">
        </label>
        <div class="form-row">
            <label>Robots
                <select name="seo_robots">
                    <?php foreach (['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'] as $robots): ?>
                        <option value="<?= $robots ?>" <?= ($settings['seo_robots'] ?? '') === $robots ? 'selected' : '' ?>><?= $robots ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Type d’organisation (schema)
                <select name="seo_organization_type">
                    <?php foreach (['Store', 'LocalBusiness', 'Organization', 'ElectronicsStore'] as $type): ?>
                        <option value="<?= $type ?>" <?= ($settings['seo_organization_type'] ?? '') === $type ? 'selected' : '' ?>><?= $type ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="form-row">
            <label>Google Search Console (code vérification)
                <input type="text" name="seo_google_verification" value="<?= Helpers::e($settings['seo_google_verification']) ?>" placeholder="contenu de google-site-verification">
                <small class="field-hint">Search Console → Paramètres → Vérification de la propriété → Balise HTML.</small>
            </label>
            <label>Bing Webmaster (code vérification)
                <input type="text" name="seo_bing_verification" value="<?= Helpers::e($settings['seo_bing_verification']) ?>">
            </label>
        </div>

        <h3 class="form-section">Google Analytics &amp; Tag Manager</h3>
        <label class="checkbox-label">
            <input type="checkbox" name="analytics_enabled" value="1" <?= ($settings['analytics_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
            Activer le suivi analytics sur le site public
        </label>
        <div class="form-row">
            <label>Google Analytics 4 — Measurement ID
                <input type="text" name="analytics_ga4_id" value="<?= Helpers::e($settings['analytics_ga4_id']) ?>" placeholder="G-XXXXXXXXXX" pattern="G-[A-Za-z0-9]+">
                <small class="field-hint">Admin GA4 → Admin → Flux de données → ID de mesure.</small>
            </label>
            <label>Google Tag Manager — Conteneur
                <input type="text" name="analytics_gtm_id" value="<?= Helpers::e($settings['analytics_gtm_id']) ?>" placeholder="GTM-XXXXXXX" pattern="GTM-[A-Za-z0-9]+">
                <small class="field-hint">GTM → Admin → Installer Google Tag Manager.</small>
            </label>
        </div>

        <h3 class="form-section">Sitemap &amp; robots.txt (Search Console)</h3>
        <div class="seo-tools-box">
            <p class="text-muted">Soumettez ces URL dans Google Search Console → Indexation / Sitemaps.</p>
            <ul class="seo-tools-links">
                <li>
                    <strong>Sitemap</strong>
                    <a href="<?= Helpers::e($sitemapUrl) ?>" target="_blank" rel="noopener"><?= Helpers::e($sitemapUrl) ?></a>
                </li>
                <li>
                    <strong>robots.txt</strong>
                    <a href="<?= Helpers::e($robotsUrl) ?>" target="_blank" rel="noopener"><?= Helpers::e($robotsUrl) ?></a>
                </li>
            </ul>
            <p class="field-hint">
                Le sitemap inclut automatiquement : accueil, catalogue, catégories, articles publiés et pages légales.
                Vérifiez que l’URL du site ci-dessus est bien votre domaine de production (https://…).
            </p>
        </div>
        <label>Règles robots.txt supplémentaires (optionnel)
            <textarea name="seo_robots_txt_extra" rows="4" placeholder="Ex.&#10;Disallow: /tmp/"><?= Helpers::e($settings['seo_robots_txt_extra']) ?></textarea>
        </label>

        <h3 class="form-section">Réseaux sociaux</h3>
        <div class="form-row">
            <label>Facebook
                <input type="url" name="social_facebook" value="<?= Helpers::e($settings['social_facebook']) ?>">
            </label>
            <label>Instagram
                <input type="url" name="social_instagram" value="<?= Helpers::e($settings['social_instagram']) ?>">
            </label>
            <label>YouTube
                <input type="url" name="social_youtube" value="<?= Helpers::e($settings['social_youtube']) ?>">
            </label>
        </div>
        <div class="form-row">
            <label>LinkedIn
                <input type="url" name="social_linkedin" value="<?= Helpers::e($settings['social_linkedin']) ?>">
            </label>
            <label>TikTok
                <input type="url" name="social_tiktok" value="<?= Helpers::e($settings['social_tiktok']) ?>">
            </label>
            <label>X / Twitter
                <input type="url" name="social_twitter" value="<?= Helpers::e($settings['social_twitter']) ?>">
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </form>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
