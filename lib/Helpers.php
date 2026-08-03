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

    /**
     * Nettoie le HTML produit (imports WP / traductions) pour un affichage sûr et lisible.
     */
    public static function sanitizeProductHtml(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $allowed = '<p><br><ul><ol><li><strong><b><em><i><a><h2><h3><h4>';
        $html = strip_tags($html, $allowed);
        $html = preg_replace('/\s(?:style|class|lang|dir|id|target|rel)="[^"]*"/i', '', $html) ?? $html;
        $html = preg_replace('/<(p|li|h2|h3|h4|ul|ol|strong|b|em|i|a)\s*>/i', '<$1>', $html) ?? $html;
        // Liens : ne garder que href http(s) ou relatif.
        $html = preg_replace_callback(
            '/<a\s+href="([^"]*)">/i',
            static function (array $m): string {
                $href = trim($m[1]);
                if ($href === '' || preg_match('#^(https?:)?//|^/#i', $href) !== 1) {
                    return '<a>';
                }

                return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">';
            },
            $html
        ) ?? $html;

        return trim($html);
    }

    /** Extrait texte brut pour résumé produit. */
    public static function productExcerpt(?string $text, int $maxLen = 280): string
    {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)) ?? '');
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

    /** URL publique de la fiche produit. */
    public static function productUrl(array $product): string
    {
        $slug = trim((string) ($product['slug'] ?? ''));
        if ($slug === '') {
            return '/catalogue.php?q=' . rawurlencode((string) ($product['name'] ?? ''));
        }

        return '/produit/' . rawurlencode($slug);
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
        header('Location: ' . $url);
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
