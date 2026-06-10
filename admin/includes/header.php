<?php

declare(strict_types=1);

$config = Helpers::config();
$user = Auth::user();
$currentPage = $currentPage ?? '';
$isAdmin = Auth::isAdmin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::e($pageTitle ?? 'Dashboard') ?> — <?= Helpers::e($config['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <span class="brand-logo">PC</span>
                <div>
                    <strong><?= Helpers::e($config['name']) ?></strong>
                    <small>Administration</small>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="/admin/index.php" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon">◉</span> Tableau de bord
                </a>

                <span class="nav-section">Catalogue</span>
                <a href="/admin/produits/index.php" class="nav-item <?= $currentPage === 'products' ? 'active' : '' ?>">
                    <span class="nav-icon">▣</span> Produits
                </a>
                <a href="/admin/categories/index.php" class="nav-item <?= $currentPage === 'categories' ? 'active' : '' ?>">
                    <span class="nav-icon">▤</span> Catégories
                </a>

                <?php if ($isAdmin): ?>
                    <span class="nav-section">Contenu</span>
                    <a href="/admin/articles/index.php" class="nav-item <?= $currentPage === 'articles' ? 'active' : '' ?>">
                        <span class="nav-icon">✎</span> Articles
                    </a>
                    <a href="/admin/pages/index.php" class="nav-item <?= $currentPage === 'pages' ? 'active' : '' ?>">
                        <span class="nav-icon">📄</span> Pages légales
                    </a>

                    <span class="nav-section">Configuration</span>
                    <a href="/admin/settings/site.php" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
                        <span class="nav-icon">⚙</span> Infos du site
                    </a>
                    <a href="/admin/chat/index.php" class="nav-item <?= $currentPage === 'chat' ? 'active' : '' ?>">
                        <span class="nav-icon">💬</span> Chat (n8n)
                    </a>
                    <a href="/admin/users/index.php" class="nav-item <?= $currentPage === 'users' ? 'active' : '' ?>">
                        <span class="nav-icon">👤</span> Utilisateurs
                    </a>
                <?php endif; ?>

                <span class="nav-section">Site</span>
                <a href="/" target="_blank" class="nav-item">
                    <span class="nav-icon">↗</span> Voir le site
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-badge">
                    <strong><?= Helpers::e($user['name'] ?? '') ?></strong>
                    <small><?= Helpers::e(UserRepository::roleLabel($user['role'] ?? 'editor')) ?></small>
                </div>
                <a href="/admin/logout.php" class="logout-link">Déconnexion</a>
            </div>
        </aside>
        <main class="admin-main">
            <header class="admin-topbar">
                <h1><?= Helpers::e($pageTitle ?? 'Dashboard') ?></h1>
            </header>
            <div class="admin-content">
