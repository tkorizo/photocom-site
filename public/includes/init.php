<?php

declare(strict_types=1);

$config = Helpers::config();
$site = SettingRepository::site();
$home = SettingRepository::home();
$menuItems = SettingRepository::resolveMenuItems($home['home_menu_items'] ?? '');
$menuTree = CategoryRepository::menuFromConfig($menuItems);
$footerPages = PageRepository::published();

$siteName = $site['site_name'] ?: $config['name'];
$whatsapp = $site['whatsapp'] ?: $config['whatsapp'];
$phone = $site['phone'] ?: $config['phone'];
$email = $site['email'] ?: $config['email'];
$address = $site['address'] ?: $config['address'];
$hours = $site['hours'] ?: $config['hours'];
$founded = $site['founded'] ?: $config['founded'];
$tagline = $site['site_tagline'] ?: ($home['hero_subtitle'] ?: 'Boutique photo & vidéo professionnelle à Casablanca');
$siteLogo = trim((string) ($home['site_logo'] ?? ''));
$siteLogoMark = trim((string) ($home['site_logo_mark'] ?? ''));
$siteFavicon = trim((string) ($home['site_favicon'] ?? ''));
$promoText = $home['promo_text'];
$heroVideoUrl = trim((string) ($home['hero_video_url'] ?? ''));
$heroVideoUrlMobile = trim((string) ($home['hero_video_url_mobile'] ?? ''));
$heroImageUrl = trim((string) ($home['hero_image_url'] ?? ''));
$heroImageUrlMobile = trim((string) ($home['hero_image_url_mobile'] ?? ''));
$heroVideoPoster = trim((string) ($home['hero_video_poster'] ?? ''));
$blogVideos = [
    [
        'url' => $home['home_blog_video_1'],
        'title' => $home['home_blog_title_1'],
        'excerpt' => $home['home_blog_excerpt_1'],
    ],
    [
        'url' => $home['home_blog_video_2'],
        'title' => $home['home_blog_title_2'],
        'excerpt' => $home['home_blog_excerpt_2'],
    ],
];
$socialLinks = array_filter([
    'facebook' => $home['social_facebook'],
    'instagram' => $home['social_instagram'],
    'youtube' => $home['social_youtube'],
    'linkedin' => $home['social_linkedin'],
]);
$chatSettings = SettingRepository::chat();
