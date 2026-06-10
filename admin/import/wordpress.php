<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();

$config = Helpers::config();

if (!($config['import_enabled'] ?? false)) {
    $pageTitle = 'Import WordPress';
    $currentPage = 'import';
    require dirname(__DIR__) . '/includes/header.php';
    ?>
    <div class="panel import-disabled-panel">
        <div class="import-disabled-icon">✓</div>
        <h2>Import WordPress terminé</h2>
        <p class="text-muted">L'import initial depuis WordPress a été effectué avec succès. Tous les produits et catégories sont désormais gérés directement depuis PHOTOCOM.</p>
        <p class="text-muted">Cette fonctionnalité est désactivée pour éviter les doublons.</p>
        <div class="actions-row" style="margin-top: 1.5rem;">
            <a href="/admin/produits/index.php" class="btn btn-primary">Gérer les produits</a>
            <a href="/admin/categories/index.php" class="btn">Gérer les catégories</a>
            <a href="/admin/index.php" class="btn">Retour au tableau de bord</a>
        </div>
    </div>
    <?php
    require dirname(__DIR__) . '/includes/footer.php';
    exit;
}

// Code original conservé si import_enabled est réactivé manuellement
$pageTitle = 'Import WordPress';
$currentPage = 'import';
Helpers::redirect('/admin/index.php');
