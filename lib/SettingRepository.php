<?php

declare(strict_types=1);

class SettingRepository
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $stmt = Database::getInstance()->prepare('SELECT value FROM settings WHERE key = :key');
        $stmt->execute(['key' => $key]);
        $value = $stmt->fetchColumn();

        return $value !== false ? (string) $value : $default;
    }

    public static function set(string $key, string $value): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO settings (key, value, updated_at) VALUES (:key, :value, datetime(\'now\'))
             ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = datetime(\'now\')'
        );
        $stmt->execute(['key' => $key, 'value' => $value]);
    }

    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            self::set((string) $key, (string) $value);
        }
    }

    public static function site(): array
    {
        $config = Helpers::config();
        $defaults = [
            'site_name' => $config['name'],
            'site_url' => $config['url'],
            'site_tagline' => 'Votre partenaire photo à Casablanca',
            'site_locale' => 'fr-MA',
            'site_country' => 'Maroc',
            'site_city' => 'Casablanca',
            'phone' => $config['phone'],
            'email' => $config['email'],
            'whatsapp' => $config['whatsapp'],
            'address' => $config['address'],
            'address_city' => 'Casablanca',
            'address_postal' => '',
            'address_country' => 'Maroc',
            'hours' => $config['hours'],
            'founded' => $config['founded'],
            'geo_latitude' => '',
            'geo_longitude' => '',
            'seo_title' => $config['name'] . ' — Matériel photo & vidéo à Casablanca',
            'seo_description' => 'PHOTOCOM : matériel photo et vidéo professionnel à Casablanca. Conseils experts, stock local et service après-vente.',
            'seo_keywords' => 'photo, vidéo, Casablanca, appareil photo, éclairage, PHOTOCOM',
            'seo_robots' => 'index,follow',
            'seo_google_verification' => '',
            'seo_bing_verification' => '',
            'seo_organization_type' => 'ElectronicsStore',
            'analytics_enabled' => '1',
            'analytics_ga4_id' => '',
            'analytics_gtm_id' => '',
            'seo_robots_txt_extra' => '',
            'social_facebook' => '',
            'social_instagram' => '',
            'social_youtube' => '',
            'social_linkedin' => '',
            'social_tiktok' => '',
            'social_twitter' => '',
        ];

        $data = [];
        foreach ($defaults as $key => $fallback) {
            $data[$key] = self::get($key, (string) $fallback) ?? (string) $fallback;
        }

        return $data;
    }

    public static function chat(): array
    {
        return [
            'chat_enabled' => self::get('chat_enabled', '0'),
            'chat_n8n_webhook' => self::get('chat_n8n_webhook', ''),
            'chat_welcome_message' => self::get('chat_welcome_message', 'Bonjour ! Comment pouvons-nous vous aider ?'),
            'chat_offline_message' => self::get('chat_offline_message', 'Nous vous répondrons dès que possible.'),
        ];
    }

    /** @return array<string, string> */
    public static function cookieDefaults(): array
    {
        return [
            'cookies_enabled' => '1',
            'cookies_title' => 'Nous respectons votre vie privée',
            'cookies_message' => 'Ce site utilise des cookies nécessaires au fonctionnement et, avec votre accord, des cookies pour améliorer votre expérience. Vous pouvez accepter, refuser ou en savoir plus.',
            'cookies_accept_label' => 'Tout accepter',
            'cookies_refuse_label' => 'Refuser',
            'cookies_more_label' => 'En savoir plus',
            'cookies_policy_url' => '/page/politique-cookies',
        ];
    }

    public static function cookies(): array
    {
        $defaults = self::cookieDefaults();
        $data = [];
        foreach ($defaults as $key => $fallback) {
            $data[$key] = self::get($key, $fallback) ?? $fallback;
        }

        return $data;
    }

    public static function home(): array
    {
        $defaults = self::homeDefaults();
        $data = [];
        foreach ($defaults as $key => $fallback) {
            $data[$key] = self::get($key, $fallback) ?? $fallback;
        }

        return $data;
    }

    public static function homeDefaults(): array
    {
        return [
            // Promo
            'promo_enabled' => '1',
            'promo_text' => 'Livraison nationale — Matériel 100 % authentique — Devis WhatsApp sous 24 h',

            // Hero
            'hero_enabled' => '1',
            'hero_kicker' => 'Expert photo & vidéo',
            'hero_title' => 'Matériel professionnel au Maroc',
            'hero_subtitle' => 'Votre partenaire photo à Casablanca',
            'hero_cta_label' => 'Découvrir le catalogue',
            'hero_cta_url' => '/catalogue.php',
            'hero_media_type' => 'none',
            'hero_video_url' => '',
            'hero_image_url' => '',
            'hero_video_poster' => '',
            'hero_align' => 'left',
            'hero_vertical' => 'center',
            'hero_overlay' => '55',
            'hero_height' => 'medium',
            'hero_show_subtitle' => '1',

            // Featured
            'featured_enabled' => '1',
            'featured_label' => 'Sélection',
            'featured_title' => 'Produits en vedette',
            'featured_align' => 'left',
            'featured_product_ids' => '',
            'featured_count' => '2',

            // Conseils
            'conseils_enabled' => '1',
            'conseils_label' => 'Conseils',
            'conseils_title' => 'Nos experts vous guident',
            'conseils_subtitle' => 'Tutoriels et astuces pour tirer le meilleur de votre matériel.',
            'conseils_align' => 'left',
            'home_blog_video_1' => 'https://www.youtube.com/embed/6v2L2UGZJAM',
            'home_blog_title_1' => 'Conseils éclairage studio',
            'home_blog_excerpt_1' => '',
            'home_blog_video_2' => 'https://www.youtube.com/embed/BCtXzY6T1jw',
            'home_blog_title_2' => 'Choisir son objectif',
            'home_blog_excerpt_2' => '',

            // Marques
            'brands_enabled' => '1',
            'brands_label' => 'Marques',
            'brands_title' => 'Parcourir par marque',
            'brands_align' => 'left',

            // Bestsellers
            'bestsellers_enabled' => '1',
            'bestsellers_label' => 'Top ventes',
            'bestsellers_title' => 'Meilleures ventes',
            'bestsellers_align' => 'left',
            'bestsellers_product_ids' => '',
            'bestsellers_count' => '8',
            'catalogue_cta_label' => 'Voir tout le catalogue',

            // Trust
            'trust_enabled' => '1',
            'trust_1_title' => 'Livraison',
            'trust_1_text' => 'Expédition sécurisée partout au Maroc. Retrait possible en magasin à Casablanca.',
            'trust_1_link_label' => 'En savoir plus',
            'trust_1_link_url' => '/page/politique-livraison',
            'trust_2_title' => 'Retours',
            'trust_2_text' => 'Produits neufs sous emballage d\'origine. Conditions de retour transparentes.',
            'trust_2_link_label' => 'En savoir plus',
            'trust_2_link_url' => '/page/politique-retour',
            'trust_3_title' => 'Commander',
            'trust_3_text' => 'Parcourez le catalogue, contactez-nous sur WhatsApp et recevez votre devis.',
            'trust_3_link_label' => 'En savoir plus',
            'trust_3_link_url' => '/page/comment-commander',
            'trust_4_title' => 'Garantie',
            'trust_4_text' => 'Matériel 100 % authentique avec service après-vente et support technique.',
            'trust_4_link_label' => 'Nous contacter',
            'trust_4_link_url' => '/#contact',

            // Newsletter
            'newsletter_enabled' => '1',
            'newsletter_label' => 'Newsletter',
            'newsletter_title' => 'Restez informé',
            'newsletter_text' => 'Nouveautés, promotions et conseils photo directement dans votre boîte mail.',
            'newsletter_button' => 'S\'inscrire',
            'newsletter_align' => 'left',

            // Navigation / header layout
            'header_order' => 'logo,nav,search',
            'nav_links_align' => 'left',
            'mega_nav_align' => 'stretch',
            'search_visible' => '1',
            'logo_visible' => '1',
            'nav_visible' => '1',
            'site_logo' => '',
            'site_logo_mark' => '',
            'site_favicon' => '',
            'home_menu_items' => '',

            // Social
            'social_facebook' => 'https://www.facebook.com/',
            'social_instagram' => 'https://www.instagram.com/',
            'social_youtube' => 'https://www.youtube.com/',
            'social_linkedin' => '',
        ];
    }

    /** @return list<string> */
    public static function homeToggleKeys(): array
    {
        return [
            'promo_enabled',
            'hero_enabled',
            'hero_show_subtitle',
            'featured_enabled',
            'conseils_enabled',
            'brands_enabled',
            'bestsellers_enabled',
            'trust_enabled',
            'newsletter_enabled',
            'search_visible',
            'logo_visible',
            'nav_visible',
        ];
    }

    /** @return list<string> */
    public static function parseIdList(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', $value) ?: []), static fn (int $id): bool => $id > 0));
    }

    /** @return list<string> */
    public static function parseHeaderOrder(?string $value): array
    {
        $allowed = ['logo', 'nav', 'search'];
        $parts = array_values(array_filter(array_map('trim', explode(',', (string) $value))));
        $order = [];
        foreach ($parts as $part) {
            if (in_array($part, $allowed, true) && !in_array($part, $order, true)) {
                $order[] = $part;
            }
        }
        foreach ($allowed as $item) {
            if (!in_array($item, $order, true)) {
                $order[] = $item;
            }
        }

        return $order;
    }

    /**
     * @return list<array{id: string, label: string, category_id: int, visible: int}>
     */
    public static function parseMenuItems(?string $json): array
    {
        $decoded = json_decode((string) $json, true);
        if (!is_array($decoded) || $decoded === []) {
            return [];
        }

        $items = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }
            $categoryId = (int) ($row['category_id'] ?? 0);
            if ($categoryId <= 0) {
                continue;
            }
            $id = trim((string) ($row['id'] ?? ''));
            if ($id === '') {
                $id = 'item-' . $categoryId . '-' . count($items);
            }
            $items[] = [
                'id' => $id,
                'label' => trim((string) ($row['label'] ?? '')),
                'category_id' => $categoryId,
                'visible' => ((int) ($row['visible'] ?? 1)) === 1 ? 1 : 0,
            ];
        }

        return $items;
    }

    /**
     * @param list<array{id?: string, label?: string, category_id?: int, visible?: int}> $items
     */
    public static function encodeMenuItems(array $items): string
    {
        $normalized = self::parseMenuItems(json_encode(array_values($items), JSON_UNESCAPED_UNICODE) ?: '[]');

        return json_encode($normalized, JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    /**
     * @return list<array{id: string, label: string, category_id: int, visible: int}>
     */
    public static function resolveMenuItems(?string $json): array
    {
        $items = self::parseMenuItems($json);
        if ($items !== []) {
            return $items;
        }

        return CategoryRepository::defaultMenuItemsFromRoots();
    }
}
