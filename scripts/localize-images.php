<?php

declare(strict_types=1);

/**
 * Télécharge toutes les images distantes (photocom.ma) et les enregistre en local.
 * Usage: php scripts/localize-images.php
 */

require_once dirname(__DIR__) . '/bootstrap.php';

error_reporting(E_ALL & ~E_DEPRECATED);

echo "=== Localisation des images (téléchargement en fichiers) ===\n\n";

Database::initialize();
ImageDownloader::categoriesPath();

$productCount = 0;
$categoryCount = 0;
$errors = 0;

$products = Database::getInstance()->query('SELECT * FROM products')->fetchAll();
echo 'Produits à traiter : ' . count($products) . "\n";

foreach ($products as $product) {
    $updates = ImageDownloader::localizeProductImages($product);
    if (!empty($updates)) {
        ProductRepository::update((int) $product['id'], array_merge($product, $updates));
        $productCount++;
        echo "  ✓ Produit #{$product['id']} : " . count($updates) . " champ(s) localisé(s)\n";
    }
}

$categories = Database::getInstance()->query('SELECT * FROM categories')->fetchAll();
echo "\nCatégories à traiter : " . count($categories) . "\n";

foreach ($categories as $category) {
    $hasRemote = false;
    foreach (['thumbnail', 'category_icon', 'large_category_icon', 'title_background'] as $field) {
        if (ImageDownloader::isRemoteUrl($category[$field] ?? null)) {
            $hasRemote = true;
            break;
        }
    }

    if (!$hasRemote && !empty($category['wordpress_id'])) {
        continue;
    }

    $updates = ImageDownloader::localizeCategoryImages($category);

    if (empty($updates) && !empty($category['wordpress_id']) && empty($category['thumbnail'])) {
        $updates = selfFetchCategoryThumbnail((int) $category['wordpress_id'], $category);
    }

    if (!empty($updates)) {
        CategoryRepository::update((int) $category['id'], array_merge($category, $updates));
        $categoryCount++;
        echo "  ✓ Catégorie #{$category['id']} ({$category['name']})\n";
    }
}

echo "\n=== Terminé ===\n";
echo "Produits mis à jour : {$productCount}\n";
echo "Catégories mises à jour : {$categoryCount}\n";

function selfFetchCategoryThumbnail(int $wpId, array $category): array
{
    $config = Helpers::config();
    $url = rtrim($config['wordpress_url'], '/') . '/wp-json/wc/v3/products/categories/' . $wpId;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => $config['woocommerce_key'] . ':' . $config['woocommerce_secret'],
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $data = json_decode($response ?: '', true);

    if (empty($data['image']['src'])) {
        return [];
    }

    $local = ImageDownloader::download($data['image']['src'], 'categories', 'wp-cat-' . $wpId . '-thumb');
    return $local ? ['thumbnail' => $local] : [];
}
