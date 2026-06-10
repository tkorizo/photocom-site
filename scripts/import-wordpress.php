<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

error_reporting(E_ALL & ~E_DEPRECATED);

echo "=== Import WordPress / WooCommerce ===\n\n";

Database::initialize();

$importer = new WordPressImporter();

if (!$importer->isConfigured()) {
    echo "ERREUR : Configuration incomplète.\n";
    echo "Renseignez dans .env :\n";
    echo "  WORDPRESS_URL=https://votre-site.com\n";
    echo "  WOOCOMMERCE_KEY=ck_...\n";
    echo "  WOOCOMMERCE_SECRET=cs_...\n";
    exit(1);
}

echo "Connexion à " . Helpers::config()['wordpress_url'] . "...\n";
echo "Import des catégories et produits (avec liaisons)...\n\n";

try {
    set_time_limit(0);
    $result = $importer->import();

    echo $result['message'] . "\n";
    echo "  → " . $result['categories_count'] . " catégories\n";
    echo "  → " . $result['products_count'] . " produits\n";

    if (!empty($result['errors'])) {
        echo "\nErreurs (" . count($result['errors']) . ") :\n";
        foreach (array_slice($result['errors'], 0, 10) as $error) {
            echo "  - {$error}\n";
        }
    }

    echo "\nImport terminé.\n";
    exit($result['status'] === 'error' ? 1 : 0);
} catch (Throwable $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
    exit(1);
}
