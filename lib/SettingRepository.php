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
        $keys = ['site_name', 'site_url', 'site_tagline', 'phone', 'email', 'whatsapp', 'address', 'hours', 'founded'];

        $data = [];
        foreach ($keys as $key) {
            $fallback = match ($key) {
                'site_name' => $config['name'],
                'site_url' => $config['url'],
                'site_tagline' => '',
                'phone' => $config['phone'],
                'email' => $config['email'],
                'whatsapp' => $config['whatsapp'],
                'address' => $config['address'],
                'hours' => $config['hours'],
                'founded' => $config['founded'],
                default => '',
            };
            $data[$key] = self::get($key, $fallback) ?? $fallback;
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

    public static function home(): array
    {
        return [
            'promo_text' => self::get('promo_text', 'Livraison nationale — Matériel 100 % authentique — Devis WhatsApp sous 24 h'),
            'hero_video_url' => self::get('hero_video_url', ''),
            'hero_video_poster' => self::get('hero_video_poster', ''),
            'home_blog_video_1' => self::get('home_blog_video_1', 'https://www.youtube.com/embed/6v2L2UGZJAM'),
            'home_blog_video_2' => self::get('home_blog_video_2', 'https://www.youtube.com/embed/BCtXzY6T1jw'),
            'social_facebook' => self::get('social_facebook', 'https://www.facebook.com/'),
            'social_instagram' => self::get('social_instagram', 'https://www.instagram.com/'),
            'social_youtube' => self::get('social_youtube', 'https://www.youtube.com/'),
            'social_linkedin' => self::get('social_linkedin', ''),
        ];
    }
}
