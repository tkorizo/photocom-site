<?php

declare(strict_types=1);

class Helpers
{
    public static function slugify(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');

        return $text !== '' ? $text : 'item-' . uniqid();
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /** Texte multiligne échappé (horaires, notes). */
    public static function multiline(?string $value): string
    {
        return nl2br(self::e($value), false);
    }

    /**
     * Décode les entités HTML importées (&nbsp; &#8217; &amp;…).
     */
    public static function decodeHtmlEntities(?string $text): string
    {
        $out = (string) $text;
        $prev = null;
        for ($i = 0; $i < 3 && $out !== $prev; $i++) {
            $prev = $out;
            $out = html_entity_decode($out, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return str_replace("\u{00A0}", ' ', $out);
    }

    /** True si le texte contient des balises de mise en forme. */
    public static function containsHtml(?string $html): bool
    {
        return (bool) preg_match('/<(p|br|ul|ol|li|strong|b|em|i|a|h[2-4]|div)(\s|\/|>)/i', (string) $html);
    }

    /** Texte lisible : sans codes HTML, apostrophes et espaces normaux. */
    public static function plainTextFromHtml(?string $html, bool $keepNewlines = false): string
    {
        $html = self::decodeHtmlEntities(trim((string) $html));
        if ($html === '') {
            return '';
        }

        $html = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<\/(p|div|h[1-6]|li|tr)>/i', "\n", $html) ?? $html;
        $html = strip_tags($html);
        $html = self::decodeHtmlEntities($html);

        if ($keepNewlines) {
            $html = preg_replace('/[ \t]+/u', ' ', $html) ?? $html;
            $html = preg_replace("/\n{3,}/", "\n\n", $html) ?? $html;

            return trim($html);
        }

        return trim(preg_replace('/\s+/u', ' ', $html) ?? '');
    }

    /**
     * Nettoie le HTML produit (imports WP / traductions) pour un affichage sûr et lisible.
     */
    public static function sanitizeProductHtml(?string $html): string
    {
        $html = self::decodeHtmlEntities(trim((string) $html));
        if ($html === '') {
            return '';
        }

        $allowed = '<p><br><ul><ol><li><strong><b><em><i><a><h2><h3><h4>';
        $html = strip_tags($html, $allowed);
        $html = preg_replace('/\s(?:style|class|lang|dir|id|target|rel)="[^"]*"/i', '', $html) ?? $html;
        $html = preg_replace('/<(p|li|h2|h3|h4|ul|ol|strong|b|em|i|a)\s*>/i', '<$1>', $html) ?? $html;
        // Liens : ne garder que href http(s) ou relatif.
        $html = preg_replace_callback(
            '/<a\s+href="([^"]*)">/i',
            static function (array $m): string {
                $href = trim($m[1]);
                if (!self::isSafePublicHref($href)) {
                    return '<a>';
                }

                return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" rel="noopener noreferrer">';
            },
            $html
        ) ?? $html;

        return trim($html);
    }

    /** HTML public (articles, pages légales) : mêmes balises + images locales/https. */
    public static function sanitizePublicHtml(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $allowed = '<p><br><ul><ol><li><strong><b><em><i><a><h2><h3><h4><blockquote><img>';
        $html = strip_tags($html, $allowed);
        $html = preg_replace('/\s(?:style|class|lang|dir|id|target|rel)="[^"]*"/i', '', $html) ?? $html;
        $html = preg_replace('/<(p|li|h2|h3|h4|ul|ol|strong|b|em|i|a|blockquote)\s*>/i', '<$1>', $html) ?? $html;
        $html = preg_replace_callback(
            '/<a\s+href="([^"]*)">/i',
            static function (array $m): string {
                $href = trim($m[1]);
                if (!self::isSafePublicHref($href)) {
                    return '<a>';
                }

                return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" rel="noopener noreferrer">';
            },
            $html
        ) ?? $html;
        $html = preg_replace_callback(
            '/<img\b([^>]*)>/i',
            static function (array $m): string {
                $attrs = $m[1];
                $src = '';
                $alt = '';
                if (preg_match('/\bsrc="([^"]*)"/i', $attrs, $sm)) {
                    $src = trim(html_entity_decode($sm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                }
                if (preg_match('/\balt="([^"]*)"/i', $attrs, $am)) {
                    $alt = trim($am[1]);
                }
                if ($src === '' || !self::isSafePublicSrc($src)) {
                    return '';
                }

                return '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '">';
            },
            $html
        ) ?? $html;

        return trim($html);
    }

    public static function isSafePublicHref(string $href): bool
    {
        $href = trim($href);
        if ($href === '' || str_contains($href, "\0") || str_contains($href, '\\')) {
            return false;
        }
        if (preg_match('#^(javascript|data|vbscript):#i', $href) === 1) {
            return false;
        }
        if (str_starts_with($href, '//')) {
            return false;
        }
        if (str_starts_with($href, '/') && !str_starts_with($href, '//')) {
            return !str_contains($href, '..');
        }

        return preg_match('#^https://#i', $href) === 1;
    }

    public static function isSafePublicSrc(string $src): bool
    {
        if (str_starts_with($src, '/uploads/') && !str_contains($src, '..')) {
            return true;
        }

        return preg_match('#^https://#i', $src) === 1 && !str_contains($src, '..');
    }

    /** Extrait texte brut pour résumé produit. */
    public static function productExcerpt(?string $text, int $maxLen = 280): string
    {
        $plain = self::plainTextFromHtml($text, false);
        if ($plain === '') {
            return '';
        }
        if (mb_strlen($plain) <= $maxLen) {
            return $plain;
        }

        return rtrim(mb_substr($plain, 0, $maxLen - 1)) . '…';
    }

    public static function formatPrice(?float $price): string
    {
        if ($price === null) {
            return '0,00 MAD TTC';
        }

        return number_format($price, 2, ',', ' ') . ' MAD TTC';
    }

    /** Affichage lisible d’un numéro WhatsApp ou téléphone. */
    public static function formatPhoneDisplay(?string $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }
        if (preg_match('/\s/', $raw) === 1) {
            return $raw;
        }
        $digits = preg_replace('/\D+/', '', $raw) ?: '';
        if ($digits === '') {
            return $raw;
        }
        if (str_starts_with($digits, '212') && strlen($digits) === 12) {
            $national = substr($digits, 3);
            $first = $national[0];
            if ($first === '6' || $first === '7') {
                return '+212 ' . $first . ' '
                    . substr($national, 1, 2) . ' '
                    . substr($national, 3, 2) . ' '
                    . substr($national, 5, 2) . ' '
                    . substr($national, 7, 2);
            }

            return '+212 ' . substr($national, 0, 3) . ' '
                . substr($national, 3, 3) . ' '
                . substr($national, 6, 3);
        }

        return '+' . ltrim($digits, '+');
    }

    /** URL publique de la fiche produit. */
    public static function productUrl(array $product): string
    {
        $slug = trim((string) ($product['slug'] ?? ''));
        if ($slug === '') {
            return '/catalogue.php?q=' . rawurlencode((string) ($product['name'] ?? ''));
        }

        return '/produit/' . rawurlencode($slug);
    }

    /** Transforme un chemin média (/uploads/…) en URL absolue https. */
    public static function absoluteUrl(?string $url, ?string $base = null): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }
        if ($base === null) {
            $site = SettingRepository::site();
            $config = self::config();
            $base = rtrim((string) ($site['site_url'] ?: ($config['url'] ?? '')), '/');
        } else {
            $base = rtrim($base, '/');
        }
        if ($base === '') {
            return $url;
        }
        if (!str_starts_with($url, '/')) {
            $url = '/' . $url;
        }

        return $base . $url;
    }

    /**
     * Prix public TTC.
     * Promo uniquement si Prix promo TTC < Prix TTC (même champs visibles en admin).
     *
     * @return array{current: float, old: ?float, discount_percent: ?int, on_sale: bool}
     */
    public static function productPricing(array $product): array
    {
        $price = self::nullableFloat($product['price'] ?? null);
        $sale = self::nullableFloat($product['sale_price'] ?? null);

        // Promo = prix promo strictement inférieur au Prix TTC.
        $hasPromo = self::hasValidPromoPrice($product);

        if ($hasPromo && $sale !== null && $price !== null) {
            $percent = (int) round((($price - $sale) / $price) * 100);

            return [
                'current' => $sale,
                'old' => $price,
                'discount_percent' => $percent >= 1 ? $percent : null,
                'on_sale' => $percent >= 1,
            ];
        }

        return [
            'current' => $price ?? 0.0,
            'old' => null,
            'discount_percent' => null,
            'on_sale' => false,
        ];
    }

    private static function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    /**
     * Promo valide = prix promo TTC renseigné et strictement inférieur au Prix TTC.
     */
    public static function hasValidPromoPrice(array $data): bool
    {
        $price = self::nullableFloat($data['price'] ?? null);
        $sale = self::nullableFloat($data['sale_price'] ?? null);

        if ($sale === null || $sale <= 0 || $price === null) {
            return false;
        }

        return $sale < ($price - 0.009);
    }

    /**
     * @return string|null Message d’erreur ou null si OK
     */
    public static function validateProductPricing(array $data): ?string
    {
        $price = self::nullableFloat($data['price'] ?? null);
        $sale = self::nullableFloat($data['sale_price'] ?? null);
        $wantsPromo = !empty($data['on_sale']);

        if ($sale !== null && $price !== null && $sale > ($price + 0.009)) {
            return 'Le prix promo TTC ne peut pas être supérieur au Prix TTC.';
        }

        if ($wantsPromo && !self::hasValidPromoPrice($data)) {
            return 'Renseignez un prix promo TTC strictement inférieur au Prix TTC pour activer la promotion.';
        }

        return null;
    }

    /**
     * Active « en promo » uniquement si Prix promo TTC < Prix TTC.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalizeProductPricingFlags(array $data): array
    {
        if (!array_key_exists('on_sale', $data)
            && !array_key_exists('sale_price', $data)
            && !array_key_exists('price', $data)
        ) {
            return $data;
        }

        $price = self::nullableFloat($data['price'] ?? null);
        $sale = self::nullableFloat($data['sale_price'] ?? null);

        // Prix promo trop élevé : laisser tel quel, la validation bloque l’enregistrement.
        if ($sale !== null && $price !== null && $sale > ($price + 0.009)) {
            return $data;
        }

        $data['on_sale'] = self::hasValidPromoPrice($data) ? 1 : 0;

        return $data;
    }

    public static function redirect(string $url): void
    {
        $target = '/';
        $url = trim($url);
        if (
            $url !== ''
            && str_starts_with($url, '/')
            && !str_starts_with($url, '//')
            && !str_contains($url, '\\')
            && preg_match('#[\x00-\x1F\x7F]#', $url) !== 1
        ) {
            $target = $url;
        }
        header('Location: ' . $target, true, 302);
        exit;
    }

    public static function flash(string $key, ?string $message = null): ?string
    {
        Auth::startSession();

        if ($message !== null) {
            $_SESSION['flash'][$key] = $message;
            return null;
        }

        $value = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);

        return $value;
    }

    private static ?array $config = null;

    public static function config(): array
    {
        if (self::$config === null) {
            self::$config = require ROOT_PATH . '/config/app.php';
        }

        return self::$config;
    }

    public static function uniqueSlug(string $table, string $name, ?int $excludeId = null): string
    {
        $base = self::slugify($name);
        $slug = $base;
        $counter = 1;
        $db = Database::getInstance();

        while (true) {
            $sql = "SELECT id FROM {$table} WHERE slug = :slug";
            $params = ['slug' => $slug];

            if ($excludeId !== null) {
                $sql .= ' AND id != :id';
                $params['id'] = $excludeId;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            if (!$stmt->fetch()) {
                return $slug;
            }

            $slug = $base . '-' . $counter;
            $counter++;
        }
    }

    public static function slugExists(string $table, string $slug, ?int $excludeId = null): bool
    {
        $slug = self::slugify($slug);
        $sql = "SELECT id FROM {$table} WHERE slug = :slug";
        $params = ['slug' => $slug];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetch();
    }
}
