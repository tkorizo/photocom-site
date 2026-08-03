<?php

declare(strict_types=1);

/**
 * Icônes SVG minimalistes (stroke) pour le dashboard.
 */
function adminIcon(string $name, int $size = 18): string
{
    $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>',
        'box' => '<path d="M3 7.5 12 3l9 4.5v9L12 21 3 16.5v-9z"/><path d="M3 7.5 12 12l9-4.5M12 12v9"/>',
        'layers' => '<path d="M12 3 3 8l9 5 9-5-9-5z"/><path d="M3 12l9 5 9-5"/><path d="M3 16l9 5 9-5"/>',
        'tag' => '<path d="M20 12.5 11.5 4H5v6.5L13.5 19 20 12.5z"/><circle cx="8" cy="8" r="1.2"/>',
        'home' => '<path d="M4 11.5 12 4l8 7.5"/><path d="M6.5 10.5V20h11V10.5"/>',
        'file' => '<path d="M7 3h7l5 5v13H7V3z"/><path d="M14 3v5h5"/>',
        'folder' => '<path d="M3 7.5h6l2 2H21v9.5H3V7.5z"/><path d="M3 7.5V6h5.5l1.5 1.5"/>',
        'doc' => '<path d="M6 3h9l4 4v14H6V3z"/><path d="M15 3v4h4"/><path d="M9 12h6M9 16h6"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 3v2.2M12 18.8V21M4.9 6.3l1.6 1.6M17.5 16.1l1.6 1.6M3 12h2.2M18.8 12H21M4.9 17.7l1.6-1.6M17.5 7.9l1.6-1.6"/>',
        'cookie' => '<circle cx="12" cy="12" r="8"/><circle cx="9" cy="10" r="0.9"/><circle cx="14" cy="9" r="0.9"/><circle cx="11" cy="14" r="0.9"/><circle cx="15" cy="13.5" r="0.9"/>',
        'chat' => '<path d="M5 5h14v10H9l-4 3.5V5z"/>',
        'user' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 19.5c1.6-3.2 4-4.8 7-4.8s5.4 1.6 7 4.8"/>',
        'external' => '<path d="M10 5H5v14h14v-5"/><path d="M13 5h6v6M19 5 10 14"/>',
        'alert' => '<path d="M12 4 3.5 19h17L12 4z"/><path d="M12 10v4"/><circle cx="12" cy="16.5" r="0.8"/>',
        'percent' => '<path d="M6 18 18 6"/><circle cx="8" cy="8" r="2"/><circle cx="16" cy="16" r="2"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'pen' => '<path d="M4 17.5V20h2.5L18 8.5 15.5 6 4 17.5z"/><path d="M13.8 7.7 16.3 10.2"/>',
        'log' => '<path d="M5 4h14v16H5z"/><path d="M8 8h8M8 12h8M8 16h5"/>',
    ];

    $paths = $icons[$name] ?? $icons['file'];

    return '<svg class="nav-icon-svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
}
