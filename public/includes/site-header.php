<?php

declare(strict_types=1);

/** @var array $menuTree */
/** @var string $siteName */
/** @var string $whatsapp */
/** @var array $socialLinks */
$searchQuery = $searchQuery ?? trim($_GET['q'] ?? '');
$activeCatSlug = $activeCatSlug ?? trim($_GET['cat'] ?? '');
?>
<header class="site-header">
    <div class="top-bar">
        <div class="container top-bar-inner">
            <p class="top-bar-promo"><?= Helpers::e($siteName) ?> — Expert photo & vidéo au Maroc</p>
            <div class="top-bar-actions">
                <div class="lang-switch" aria-label="Langue">
                    <span class="lang-active">FR</span>
                    <span class="lang-sep">|</span>
                    <a href="#" lang="ar">AR</a>
                    <span class="lang-sep">|</span>
                    <a href="#" lang="en">EN</a>
                </div>
                <a href="/admin/login.php" class="top-bar-link">Compte</a>
                <?php if (!empty($socialLinks)): ?>
                    <div class="top-bar-social">
                        <?php
                        $socialLinkClass = 'top-bar-social-icon';
                        require __DIR__ . '/social-icons.php';
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<div class="header-sticky">
        <div class="header-main">
            <div class="container header-main-inner">
                <a href="/" class="logo" aria-label="<?= Helpers::e($siteName) ?>">
                    <?php $logoClass = 'header-logo-img'; $logoWidth = 148; require __DIR__ . '/site-logo.php'; ?>
                </a>

                <nav class="header-nav" aria-label="Navigation">
                    <a href="/">Accueil</a>
                    <a href="/catalogue.php">Catalogue</a>
                    <a href="/page/mentions-legales">Qui sommes-nous</a>
                    <a href="/#conseils">Conseils</a>
                    <a href="/#contact">Contact</a>
                </nav>

                <form class="header-search" action="/catalogue.php" method="get" role="search">
                    <input type="search" name="q" value="<?= Helpers::e($searchQuery) ?>" placeholder="Rechercher un produit, une marque…" aria-label="Rechercher">
                    <button type="submit" aria-label="Rechercher">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                    </button>
                </form>

                <div class="header-actions">
                    <button type="button" class="header-icon-btn mega-menu-toggle" aria-label="Ouvrir le menu catégories" aria-expanded="false">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <?php if (!empty($menuTree)): ?>
        <nav class="mega-nav" data-mega-nav aria-label="Catégories produits">
            <ul class="mega-nav-list">
                <?php foreach ($menuTree as $index => $root): ?>
                    <?php $hasChildren = !empty($root['children']); ?>
                    <li
                        class="mega-nav-item<?= $activeCatSlug === ($root['slug'] ?? '') ? ' is-active' : '' ?>"
                        data-mega-item
                        data-mega-index="<?= (int) $index ?>"
                    >
                        <?php if ($hasChildren): ?>
                            <button
                                type="button"
                                class="mega-nav-trigger"
                                data-mega-trigger="<?= (int) $index ?>"
                                aria-expanded="false"
                            >
                                <span><?= Helpers::e($root['name']) ?></span>
                            </button>
                        <?php else: ?>
                            <a href="/catalogue.php?cat=<?= Helpers::e($root['slug']) ?>" class="mega-nav-link">
                                <?= Helpers::e($root['name']) ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="mega-stage" data-mega-stage hidden>
                <div class="container">
                    <?php foreach ($menuTree as $index => $root): ?>
                        <?php if (empty($root['children'])) continue; ?>
                        <div class="mega-panel" data-mega-panel="<?= (int) $index ?>" hidden>
                            <div class="mega-panel-head">
                                <div class="mega-panel-title">
                                    <span class="mega-panel-kicker">Univers</span>
                                    <h2><?= Helpers::e($root['name']) ?></h2>
                                </div>
                                <a href="/catalogue.php?cat=<?= Helpers::e($root['slug']) ?>" class="btn btn-sm btn-outline-light">Tout voir</a>
                            </div>
                            <ul class="mega-subgrid">
                                <?php foreach ($root['children'] as $child): ?>
                                    <li>
                                        <a
                                            href="/catalogue.php?cat=<?= Helpers::e($child['slug']) ?>"
                                            class="mega-sub-link<?= $activeCatSlug === ($child['slug'] ?? '') ? ' is-active' : '' ?>"
                                        >
                                            <span><?= Helpers::e($child['name']) ?></span>
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </nav>
        <?php endif; ?>
</div>

<div class="mega-backdrop" data-mega-backdrop hidden aria-hidden="true"></div>
