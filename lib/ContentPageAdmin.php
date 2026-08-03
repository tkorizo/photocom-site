<?php

declare(strict_types=1);

/**
 * Helpers d’enregistrement des pages éditoriales (about / contact / conseils).
 */
class ContentPageAdmin
{
    /**
     * @param array<string, string> $defaults
     * @param list<string> $toggles
     * @return array<string, string>
     */
    public static function collectPost(array $defaults, array $toggles): array
    {
        $payload = [];
        foreach (array_keys($defaults) as $key) {
            if (in_array($key, $toggles, true)) {
                $payload[$key] = isset($_POST[$key]) ? '1' : '0';
            } else {
                $payload[$key] = trim((string) ($_POST[$key] ?? ''));
            }
        }

        return $payload;
    }

    public static function resolveCtaUrl(string $url, string $whatsapp): string
    {
        $url = trim($url);
        if ($url === '' || $url === 'whatsapp') {
            $digits = preg_replace('/\D+/', '', $whatsapp) ?: $whatsapp;

            return 'https://wa.me/' . $digits;
        }

        return $url;
    }
}
