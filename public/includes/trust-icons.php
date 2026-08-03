<?php

declare(strict_types=1);

/**
 * Icônes SVG du bandeau confiance (livraison, retours, commander, garantie).
 */
function trustIcon(int $index, int $size = 28): string
{
    $icons = [
        1 => '<path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7"/><circle cx="7.5" cy="17.5" r="1.5"/><circle cx="17.5" cy="17.5" r="1.5"/>', // livraison
        2 => '<path d="M4 12a8 8 0 0 1 13.5-5.5L20 9"/><path d="M20 4v5h-5"/><path d="M20 12a8 8 0 0 1-13.5 5.5L4 15"/><path d="M4 20v-5h5"/>', // retours
        3 => '<path d="M6 7h12l-1 12H7L6 7z"/><path d="M9 7a3 3 0 0 1 6 0"/><path d="M10 11v4M14 11v4"/>', // commander
        4 => '<path d="M12 3 5 6v5c0 4.5 2.8 7.8 7 9 4.2-1.2 7-4.5 7-9V6l-7-3z"/><path d="m9 12 2 2 4-4"/>', // garantie
    ];

    $paths = $icons[$index] ?? $icons[1];

    return '<svg class="trust-icon-svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
}
