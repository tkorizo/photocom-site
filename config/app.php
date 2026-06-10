<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

return [
    'name' => env('APP_NAME', 'PHOTOCOM'),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'db_path' => dirname(__DIR__) . '/database/photocom.sqlite',
    'uploads_path' => dirname(__DIR__) . '/uploads/products',
    'wordpress_url' => env('WORDPRESS_URL', ''),
    'woocommerce_key' => env('WOOCOMMERCE_KEY', ''),
    'woocommerce_secret' => env('WOOCOMMERCE_SECRET', ''),
    'whatsapp' => env('WHATSAPP_NUMBER', '212522865417'),
    'phone' => env('PHONE', '+212 522 865 417'),
    'email' => env('EMAIL', 'contact@photocom.ma'),
    'address' => env('ADDRESS', '7 Allée de Persée, Boulevard Abdelmoumen, Casablanca'),
    'hours' => env('HOURS', 'Lundi - Samedi : 8h30 - 19h00'),
    'founded' => env('FOUNDED', '2010'),
    'import_enabled' => false,
];
