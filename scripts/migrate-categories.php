<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

echo "Migration / reclassement produits PHOTOCOM\n\n";

$dry = in_array('--dry-run', $argv ?? [], true);
$full = in_array('--full', $argv ?? [], true);

if ($dry) {
    echo "Mode simulation (--dry-run)\n\n";
}

$stats = $full
    ? CategoryMigrator::migrate($dry)
    : CategoryMigrator::reassignProducts($dry);

echo "Produits traités : {$stats['total_products']}\n";
if (!$dry && isset($stats['categories_created'])) {
    echo "Catégories créées : {$stats['categories_created']}\n";
}
echo "Classés automatiquement : {$stats['classified']}\n";
echo "Fallback (adaptateurs & divers) : {$stats['fallback']}\n\n";

echo "Répartition par sous-catégorie :\n";
arsort($stats['by_slug']);
foreach ($stats['by_slug'] as $slug => $count) {
    echo sprintf("  %-35s %4d\n", $slug, $count);
}

if ($dry) {
    echo "\nRelancez sans --dry-run pour appliquer.\n";
} else {
    echo "\n✓ Migration terminée.\n";
}
