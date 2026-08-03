<?php

declare(strict_types=1);

/**
 * Meta SEO / Open Graph / JSON-LD pour les pages publiques.
 *
 * Variables attendues (optionnelles) :
 * @var string|null $seoTitle
 * @var string|null $seoDescription
 * @var string|null $seoKeywords
 * @var string|null $seoCanonical
 * @var string|null $seoImage
 * @var string|null $seoRobots
 * @var string|null $seoType  website|article
 */

$site = $site ?? SettingRepository::site();
$config = $config ?? Helpers::config();
$siteName = $siteName ?? ($site['site_name'] ?: $config['name']);
$siteUrl = rtrim((string) ($site['site_url'] ?: $config['url']), '/');

$pageTitleFull = trim((string) ($seoTitle ?? ($pageTitle ?? $siteName)));
if ($pageTitleFull !== '' && !str_contains($pageTitleFull, $siteName)) {
    $pageTitleFull .= ' — ' . $siteName;
}
$description = trim((string) ($seoDescription ?? ($site['seo_description'] ?? '')));
if ($description === '') {
    $description = trim((string) ($site['site_tagline'] ?? ''));
}
$keywords = trim((string) ($seoKeywords ?? ($site['seo_keywords'] ?? '')));
$robots = trim((string) ($seoRobots ?? ($site['seo_robots'] ?? 'index,follow')));
$canonical = trim((string) ($seoCanonical ?? ''));
if ($canonical === '') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $canonical = $siteUrl . $requestPath;
}
$image = trim((string) ($seoImage ?? ''));
if ($image !== '' && str_starts_with($image, '/')) {
    $image = $siteUrl . $image;
}
$type = ($seoType ?? 'website') === 'article' ? 'article' : 'website';

$orgType = $site['seo_organization_type'] ?: 'ElectronicsStore';
$sameAs = array_values(array_filter([
    $site['social_facebook'] ?? '',
    $site['social_instagram'] ?? '',
    $site['social_youtube'] ?? '',
    $site['social_linkedin'] ?? '',
    $site['social_tiktok'] ?? '',
    $site['social_twitter'] ?? '',
]));
?>
<title><?= Helpers::e($pageTitleFull) ?></title>
<meta name="description" content="<?= Helpers::e($description) ?>">
<?php if ($keywords !== ''): ?>
<meta name="keywords" content="<?= Helpers::e($keywords) ?>">
<?php endif; ?>
<meta name="robots" content="<?= Helpers::e($robots) ?>">
<link rel="canonical" href="<?= Helpers::e($canonical) ?>">
<?php if (!empty($site['seo_google_verification'])): ?>
<meta name="google-site-verification" content="<?= Helpers::e($site['seo_google_verification']) ?>">
<?php endif; ?>
<?php if (!empty($site['seo_bing_verification'])): ?>
<meta name="msvalidate.01" content="<?= Helpers::e($site['seo_bing_verification']) ?>">
<?php endif; ?>

<meta property="og:type" content="<?= Helpers::e($type) ?>">
<meta property="og:site_name" content="<?= Helpers::e($siteName) ?>">
<meta property="og:title" content="<?= Helpers::e($pageTitleFull) ?>">
<meta property="og:description" content="<?= Helpers::e($description) ?>">
<meta property="og:url" content="<?= Helpers::e($canonical) ?>">
<?php if ($image !== ''): ?>
<meta property="og:image" content="<?= Helpers::e($image) ?>">
<?php endif; ?>
<meta property="og:locale" content="<?= Helpers::e(str_replace('-', '_', $site['site_locale'] ?: 'fr_MA')) ?>">

<meta name="twitter:card" content="<?= $image !== '' ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= Helpers::e($pageTitleFull) ?>">
<meta name="twitter:description" content="<?= Helpers::e($description) ?>">
<?php if ($image !== ''): ?>
<meta name="twitter:image" content="<?= Helpers::e($image) ?>">
<?php endif; ?>

<?php
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => $orgType,
    'name' => $siteName,
    'url' => $siteUrl !== '' ? $siteUrl : null,
    'description' => $description !== '' ? $description : null,
    'telephone' => $site['phone'] ?: null,
    'email' => $site['email'] ?: null,
    'foundingDate' => $site['founded'] ?: null,
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $site['address'] ?: null,
        'addressLocality' => $site['address_city'] ?: ($site['site_city'] ?: null),
        'postalCode' => $site['address_postal'] ?: null,
        'addressCountry' => $site['address_country'] ?: ($site['site_country'] ?: null),
    ],
    'sameAs' => $sameAs !== [] ? $sameAs : null,
];
if (($site['geo_latitude'] ?? '') !== '' && ($site['geo_longitude'] ?? '') !== '') {
    $jsonLd['geo'] = [
        '@type' => 'GeoCoordinates',
        'latitude' => $site['geo_latitude'],
        'longitude' => $site['geo_longitude'],
    ];
}
$jsonLd = array_filter($jsonLd, static fn ($v) => $v !== null && $v !== []);
if (isset($jsonLd['address']) && is_array($jsonLd['address'])) {
    $jsonLd['address'] = array_filter($jsonLd['address'], static fn ($v) => $v !== null && $v !== '');
}
?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php require __DIR__ . '/site-analytics.php'; ?>
