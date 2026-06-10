<?php

declare(strict_types=1);

$config = Helpers::config();
$site = SettingRepository::site();
$home = SettingRepository::home();
$menuTree = CategoryRepository::menuTree();
$footerPages = PageRepository::published();

$siteName = $site['site_name'] ?: $config['name'];
$whatsapp = $site['whatsapp'] ?: $config['whatsapp'];
$phone = $site['phone'] ?: $config['phone'];
$email = $site['email'] ?: $config['email'];
$address = $site['address'] ?: $config['address'];
$hours = $site['hours'] ?: $config['hours'];
$founded = $site['founded'] ?: $config['founded'];
$tagline = $site['site_tagline'] ?: 'Boutique photo & vidéo professionnelle à Casablanca';
$promoText = $home['promo_text'];
$heroVideoUrl = $home['hero_video_url'];
$heroVideoPoster = $home['hero_video_poster'];
$blogVideos = [
    ['url' => $home['home_blog_video_1'], 'title' => 'Conseils éclairage studio'],
    ['url' => $home['home_blog_video_2'], 'title' => 'Choisir son objectif'],
];
$socialLinks = array_filter([
    'facebook' => $home['social_facebook'],
    'instagram' => $home['social_instagram'],
    'youtube' => $home['social_youtube'],
    'linkedin' => $home['social_linkedin'],
]);
$chatSettings = SettingRepository::chat();
