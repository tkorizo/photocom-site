<?php

declare(strict_types=1);

/**
 * Étiquettes internes (admin) pour classer / filtrer les produits.
 * 7 couleurs prédéfinies — usage libre (ex. vert = à jour).
 */
class ProductLabel
{
    /** @return array<string, array{name: string, hex: string, hint: string}> */
    public static function all(): array
    {
        return [
            'green' => ['name' => 'Vert', 'hex' => '#22c55e', 'hint' => 'À jour'],
            'blue' => ['name' => 'Bleu', 'hex' => '#3b82f6', 'hint' => 'En cours'],
            'yellow' => ['name' => 'Jaune', 'hex' => '#eab308', 'hint' => 'À vérifier'],
            'orange' => ['name' => 'Orange', 'hex' => '#f97316', 'hint' => 'Prioritaire'],
            'red' => ['name' => 'Rouge', 'hex' => '#ef4444', 'hint' => 'À corriger'],
            'purple' => ['name' => 'Violet', 'hex' => '#a855f7', 'hint' => 'Spécial'],
            'gray' => ['name' => 'Gris', 'hex' => '#94a3b8', 'hint' => 'Archivé'],
        ];
    }

    public static function isValid(?string $key): bool
    {
        return $key !== null && $key !== '' && isset(self::all()[$key]);
    }

    public static function normalize(?string $key): ?string
    {
        $key = trim((string) $key);
        return self::isValid($key) ? $key : null;
    }

    /** @return array{name: string, hex: string, hint: string}|null */
    public static function get(?string $key): ?array
    {
        $key = self::normalize($key);
        return $key !== null ? self::all()[$key] : null;
    }

    public static function filterLabel(?string $key): ?string
    {
        if ($key === 'none') {
            return 'Sans étiquette';
        }
        $meta = self::get($key);
        if ($meta === null) {
            return null;
        }
        return 'Étiquette ' . $meta['name'] . ' (' . $meta['hint'] . ')';
    }
}
