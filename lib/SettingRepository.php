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
            'trust_4_link_url' => '/contact',

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

    /** @return array<string, string> */
    public static function about(): array
    {
        return self::mergeSettings(self::aboutDefaults());
    }

    /** @return array<string, string> */
    public static function contactPage(): array
    {
        return self::mergeSettings(self::contactDefaults());
    }

    /** @return array<string, string> */
    public static function conseilsPage(): array
    {
        return self::mergeSettings(self::conseilsPageDefaults());
    }

    /** @return list<string> */
    public static function aboutToggleKeys(): array
    {
        return [
            'about_story_enabled',
            'about_pillars_enabled',
            'about_audience_enabled',
            'about_cta_enabled',
            'about_find_enabled',
            'about_links_enabled',
        ];
    }

    /** @return list<string> */
    public static function contactToggleKeys(): array
    {
        return [
            'contact_intro_enabled',
            'contact_info_enabled',
            'contact_whatsapp_enabled',
            'contact_map_enabled',
            'contact_hours_enabled',
        ];
    }

    /** @return list<string> */
    public static function conseilsPageToggleKeys(): array
    {
        return [
            'conseils_page_cta_enabled',
        ];
    }

    /** @return array<string, string> */
    public static function aboutDefaults(): array
    {
        return [
            'about_seo_title' => 'Qui sommes-nous',
            'about_seo_description' => 'PHOTOCOM, spécialiste du matériel photo et vidéo professionnel à Casablanca. Distribution officielle, conseil expert et service après-vente.',
            'about_hero_label' => 'Notre maison',
            'about_hero_title' => 'PHOTOCOM, partenaire photo & vidéo au Maroc',
            'about_hero_lead' => 'Depuis Casablanca, nous accompagnons photographes, créateurs et entreprises avec du matériel authentique, un conseil technique précis et un service de proximité.',
            'about_story_enabled' => '1',
            'about_story_label' => 'Notre histoire',
            'about_story_title' => 'Une expertise construite sur le terrain',
            'about_story_p1' => 'PHOTOCOM est née d’une conviction simple : le matériel professionnel mérite un interlocuteur qui comprend les enjeux du plateau, du studio et de la production.',
            'about_story_p2' => 'Nous sélectionnons des marques reconnues, testons les produits, et guidons chaque client — du premier appareil au setup studio complet — vers la solution la plus juste pour son usage.',
            'about_story_p3' => 'Notre équipe, basée à Casablanca, reste disponible pour le conseil avant-vente, la démonstration et le suivi après livraison.',
            'about_fact_1_value' => 'Casablanca',
            'about_fact_1_label' => 'Showroom & retrait magasin',
            'about_fact_2_value' => '100 %',
            'about_fact_2_label' => 'Matériel authentique',
            'about_fact_3_value' => 'National',
            'about_fact_3_label' => 'Livraison partout au Maroc',
            'about_fact_4_value' => 'WhatsApp',
            'about_fact_4_label' => 'Devis sous 24 h',
            'about_pillars_enabled' => '1',
            'about_pillars_label' => 'Ce qui nous définit',
            'about_pillars_title' => 'Quatre engagements concrets',
            'about_pillar_1_title' => 'Sélection exigeante',
            'about_pillar_1_text' => 'Nous ne listons que des références fiables : boîtiers, objectifs, éclairage, studio et accessoires issus de circuits officiels.',
            'about_pillar_2_title' => 'Conseil technique',
            'about_pillar_2_text' => 'Besoin d’un hybride, d’un flash studio ou d’un kit vidéo ? Nous vous aidons à choisir selon votre niveau, votre budget et vos projets.',
            'about_pillar_3_title' => 'Disponibilité locale',
            'about_pillar_3_text' => 'Retrait en magasin, questions techniques, SAV : une relation directe, sans barrière entre vous et l’équipe PHOTOCOM.',
            'about_pillar_4_title' => 'Accompagnement',
            'about_pillar_4_text' => 'Devis WhatsApp, suivi de commande, conseils d’usage : nous restons présents avant et après l’achat.',
            'about_audience_enabled' => '1',
            'about_audience_label' => 'Pour qui',
            'about_audience_title' => 'Nous travaillons avec',
            'about_audience_1_title' => 'Photographes & vidéastes',
            'about_audience_1_text' => 'Matériel de prise de vue, optique et lumière pour la production quotidienne.',
            'about_audience_2_title' => 'Studios & agences',
            'about_audience_2_text' => 'Éclairage, fonds, grip et solutions durables pour les plateaux.',
            'about_audience_3_title' => 'Entreprises & écoles',
            'about_audience_3_text' => 'Équipements pour la communication, la formation et les projets audiovisuels.',
            'about_audience_4_title' => 'Passionnés exigeants',
            'about_audience_4_text' => 'Conseils clairs pour monter en gamme sans se tromper d’investissement.',
            'about_cta_enabled' => '1',
            'about_cta_label' => 'Parlons de votre projet',
            'about_cta_title' => 'Besoin d’un devis ou d’un conseil ?',
            'about_cta_text' => 'Expliquez-nous votre usage : nous vous orientons vers le bon matériel et un devis personnalisé.',
            'about_cta_1_label' => 'Voir le catalogue',
            'about_cta_1_url' => '/catalogue.php',
            'about_cta_2_label' => 'WhatsApp',
            'about_cta_2_url' => 'whatsapp',
            'about_cta_3_label' => 'Contact',
            'about_cta_3_url' => '/contact',
            'about_find_enabled' => '1',
            'about_find_label' => 'Nous trouver',
            'about_find_title' => 'PHOTOCOM à Casablanca',
            'about_links_enabled' => '1',
            'about_links_label' => 'Ressources',
            'about_links_title' => 'Aller plus loin',
            'about_link_1_label' => 'Conseils & articles',
            'about_link_1_url' => '/conseils',
            'about_link_2_label' => 'Comment commander',
            'about_link_2_url' => '/page/comment-commander',
            'about_link_3_label' => 'Livraison',
            'about_link_3_url' => '/page/politique-livraison',
            'about_link_4_label' => 'Retours',
            'about_link_4_url' => '/page/politique-retour',
            'about_link_5_label' => 'Mentions légales',
            'about_link_5_url' => '/page/mentions-legales',
        ];
    }

    /** @return array<string, string> */
    public static function contactDefaults(): array
    {
        return [
            'contact_seo_title' => 'Contact',
            'contact_seo_description' => 'Contactez PHOTOCOM à Casablanca : téléphone, WhatsApp, email et horaires. Devis matériel photo & vidéo sous 24 h.',
            'contact_hero_label' => 'Contact',
            'contact_hero_title' => 'Parlons de votre projet',
            'contact_hero_lead' => 'Une question technique, un devis ou un retrait en magasin : notre équipe vous répond rapidement.',
            'contact_intro_enabled' => '1',
            'contact_intro_label' => 'Écrire ou appeler',
            'contact_intro_title' => 'Nous sommes à votre écoute',
            'contact_intro_text' => 'Décrivez votre besoin (boîtier, optique, éclairage, studio…) : nous vous orientons vers les bonnes références et un devis personnalisé.',
            'contact_info_enabled' => '1',
            'contact_info_label' => 'Coordonnées',
            'contact_info_title' => 'PHOTOCOM Casablanca',
            'contact_whatsapp_enabled' => '1',
            'contact_whatsapp_title' => 'Réponse rapide sur WhatsApp',
            'contact_whatsapp_text' => 'Le moyen le plus simple pour obtenir un devis, vérifier un stock ou préparer un retrait magasin.',
            'contact_whatsapp_label' => 'Ouvrir WhatsApp',
            'contact_whatsapp_prefiller' => 'Bonjour PHOTOCOM, je souhaite des informations sur :',
            'contact_form_title' => 'Préparer votre message',
            'contact_form_placeholder' => 'Votre message…',
            'contact_form_button' => 'Envoyer via WhatsApp',
            'contact_hours_enabled' => '1',
            'contact_hours_note' => 'Horaires susceptibles d’évoluer les jours fériés. N’hésitez pas à nous écrire hors horaires : nous vous répondrons dès ouverture.',
            'contact_map_enabled' => '0',
            'contact_map_embed' => '',
            'contact_map_title' => 'Nous situer',
            'contact_cta_catalogue_label' => 'Voir le catalogue',
            'contact_cta_catalogue_url' => '/catalogue.php',
        ];
    }

    /** @return array<string, string> */
    public static function conseilsPageDefaults(): array
    {
        return [
            'conseils_page_seo_title' => 'Conseils',
            'conseils_page_seo_description' => 'Conseils photo et vidéo par PHOTOCOM : éclairage, objectifs, studio et bonnes pratiques pour réussir vos projets.',
            'conseils_page_hero_label' => 'Blog',
            'conseils_page_hero_title' => 'Conseils photo & vidéo',
            'conseils_page_hero_lead' => 'Guides pratiques et retours d’expérience pour choisir, utiliser et faire évoluer votre matériel.',
            'conseils_page_empty_text' => 'Aucun article pour le moment. Revenez bientôt.',
            'conseils_page_cta_enabled' => '1',
            'conseils_page_cta_label' => 'Besoin d’un conseil personnalisé ?',
            'conseils_page_cta_title' => 'Parlez-nous de votre projet',
            'conseils_page_cta_text' => 'Nos équipes vous aident à choisir le matériel adapté à votre usage, votre niveau et votre budget.',
            'conseils_page_cta_btn_label' => 'Nous contacter',
            'conseils_page_cta_btn_url' => '/contact',
            'conseils_page_cta_secondary_label' => 'Voir le catalogue',
            'conseils_page_cta_secondary_url' => '/catalogue.php',
        ];
    }

    /**
     * @param array<string, string> $defaults
     * @return array<string, string>
     */
    private static function mergeSettings(array $defaults): array
    {
        $data = [];
        foreach ($defaults as $key => $fallback) {
            $data[$key] = self::get($key, $fallback) ?? $fallback;
        }

        return $data;
    }
}
