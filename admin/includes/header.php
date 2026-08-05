<?php

declare(strict_types=1);

$config = Helpers::config();
$user = Auth::user();
$currentPage = $currentPage ?? '';
$canCatalog = Auth::canManageCatalog();
$canContent = Auth::canManageContent();
$isAdmin = Auth::isAdmin();
$isSuperAdmin = Auth::isSuperAdmin();
$homeSettings = SettingRepository::home();
$adminLogo = trim((string) ($homeSettings['site_logo_mark'] ?? ''));
if ($adminLogo === '') {
    $adminLogo = '/assets/images/logo-admin.webp';
}
$adminFavicon = trim((string) ($homeSettings['site_favicon'] ?? ''));
require __DIR__ . '/icons.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::e($pageTitle ?? 'Dashboard') ?> — <?= Helpers::e($config['name']) ?></title>
    <?php if ($adminFavicon !== ''): ?>
        <link rel="icon" href="<?= Helpers::e($adminFavicon) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <script src="/assets/js/image-upload.js" defer></script>
</head>
<body class="admin-body<?= !empty($builderMode) ? ' admin-body-builder' : '' ?>">
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <a href="/admin/index.php" class="brand-logo-link" title="<?= Helpers::e($config['name']) ?>">
                    <img src="<?= Helpers::e($adminLogo) ?>" alt="<?= Helpers::e($config['name']) ?>" class="brand-logo-img">
                </a>
                <small class="sidebar-brand-label">Administration</small>
            </div>
            <nav class="sidebar-nav">
                <a href="/admin/index.php" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon"><?= adminIcon('dashboard') ?></span> Tableau de bord
                </a>

                <?php if ($canCatalog): ?>
                    <span class="nav-section">Catalogue</span>
                    <a href="/admin/produits/index.php" class="nav-item <?= $currentPage === 'products' ? 'active' : '' ?>">
                        <span class="nav-icon"><?= adminIcon('box') ?></span> Produits
                    </a>
                    <a href="/admin/categories/index.php" class="nav-item <?= $currentPage === 'categories' ? 'active' : '' ?>">
                        <span class="nav-icon"><?= adminIcon('layers') ?></span> Catégories
                    </a>
                    <a href="/admin/marques/index.php" class="nav-item <?= $currentPage === 'brands' ? 'active' : '' ?>">
                        <span class="nav-icon"><?= adminIcon('tag') ?></span> Marques
                    </a>
                <?php endif; ?>

                <?php if ($canContent): ?>
                    <span class="nav-section">Contenu</span>
                    <?php if ($isAdmin): ?>
                        <a href="/admin/home/index.php" class="nav-item <?= $currentPage === 'home' ? 'active' : '' ?>">
                            <span class="nav-icon"><?= adminIcon('home') ?></span> Page d'accueil
                        </a>
                    <?php endif; ?>
                    <a href="/admin/about/index.php" class="nav-item <?= $currentPage === 'about' ? 'active' : '' ?>">
                        <span class="nav-icon"><?= adminIcon('doc') ?></span> Qui sommes-nous
                    </a>
                    <a href="/admin/contact/index.php" class="nav-item <?= $currentPage === 'contact-page' ? 'active' : '' ?>">
                        <span class="nav-icon"><?= adminIcon('chat') ?></span> Contact
                    </a>
                    <a href="/admin/conseils-page/index.php" class="nav-item <?= $currentPage === 'conseils-page' ? 'active' : '' ?>">
                        <span class="nav-icon"><?= adminIcon('pen') ?></span> Page Conseils
                    </a>
                    <a href="/admin/articles/index.php" class="nav-item <?= $currentPage === 'articles' ? 'active' : '' ?>">
                        <span class="nav-icon"><?= adminIcon('pen') ?></span> Articles
                    </a>
                    <?php if ($isAdmin): ?>
                        <a href="/admin/pages/index.php" class="nav-item <?= $currentPage === 'pages' ? 'active' : '' ?>">
                            <span class="nav-icon"><?= adminIcon('doc') ?></span> Pages légales
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($isAdmin): ?>
                    <span class="nav-section">Configuration</span>
                    <a href="/admin/settings/site.php" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
                        <span class="nav-icon"><?= adminIcon('settings') ?></span> Infos du site
                    </a>
                    <a href="/admin/settings/files.php" class="nav-item <?= $currentPage === 'files' ? 'active' : '' ?>">
                        <span class="nav-icon"><?= adminIcon('folder') ?></span> Fichiers
                    </a>
                    <a href="/admin/settings/cookies.php" class="nav-item <?= $currentPage === 'cookies' ? 'active' : '' ?>">
                        <span class="nav-icon"><?= adminIcon('cookie') ?></span> Cookies
                    </a>
                    <a href="/admin/chat/index.php" class="nav-item <?= $currentPage === 'chat' ? 'active' : '' ?>">
                        <span class="nav-icon"><?= adminIcon('chat') ?></span> Chat (n8n)
                    </a>
                    <a href="/admin/users/index.php" class="nav-item <?= $currentPage === 'users' ? 'active' : '' ?>">
                        <span class="nav-icon"><?= adminIcon('user') ?></span> Utilisateurs
                    </a>
                    <?php if ($isSuperAdmin): ?>
                        <a href="/admin/settings/audit-log.php" class="nav-item <?= $currentPage === 'audit' ? 'active' : '' ?>">
                            <span class="nav-icon"><?= adminIcon('log') ?></span> Journal d'activité
                        </a>
                        <a href="/admin/settings/errors.php" class="nav-item <?= $currentPage === 'errors' ? 'active' : '' ?>">
                            <span class="nav-icon"><?= adminIcon('log') ?></span> Journal des erreurs
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <span class="nav-section">Site</span>
                <a href="/" target="_blank" class="nav-item">
                    <span class="nav-icon"><?= adminIcon('external') ?></span> Voir le site
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-badge">
                    <strong><?= Helpers::e($user['name'] ?? '') ?></strong>
                    <small><?= Helpers::e(UserRepository::roleLabel($user['role'] ?? Auth::ROLE_CATALOG)) ?></small>
                </div>
                <a href="/admin/logout.php" class="logout-link">Déconnexion</a>
            </div>
        </aside>
        <main class="admin-main">
            <header class="admin-topbar">
                <h1><?= Helpers::e($pageTitle ?? 'Dashboard') ?></h1>
            </header>
            <div class="admin-content">
