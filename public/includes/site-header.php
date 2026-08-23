<?php

declare(strict_types=1);

/** @var array $menuTree */
/** @var array $home */
/** @var string $siteName */
/** @var string $whatsapp */
/** @var array $socialLinks */
$searchQuery = $searchQuery ?? trim($_GET['q'] ?? '');
$activeCatSlug = $activeCatSlug ?? trim($_GET['cat'] ?? '');
$promoEnabled = ($home['promo_enabled'] ?? '1') === '1';
$searchVisible = ($home['search_visible'] ?? '1') === '1';
$logoVisible = ($home['logo_visible'] ?? '1') === '1';
$navVisible = ($home['nav_visible'] ?? '1') === '1';
$navAlign = $home['nav_links_align'] ?? 'left';
$megaAlign = $home['mega_nav_align'] ?? 'stretch';
$headerOrder = SettingRepository::parseHeaderOrder($home['header_order'] ?? 'logo,nav,search');
CustomerAuth::startSession();
$customerLoggedIn = CustomerAuth::check();
$compteHref = $customerLoggedIn ? '/mon-compte' : '/compte';
$compteLabel = $customerLoggedIn ? 'Mon compte' : 'Compte';
?>
<?php require __DIR__ . '/site-analytics-body.php'; ?>
<div class="header-sticky">
    <header class="site-header">
        <?php if ($promoEnabled): ?>
        <div class="top-bar">
            <div class="container top-bar-inner">
                <p class="top-bar-promo"><?= Helpers::e($promoText ?: ($siteName . ' — Expert photo & vidéo au Maroc')) ?></p>
                <?php if (!empty($socialLinks)): ?>
                    <div class="top-bar-actions">
                        <div class="top-bar-social">
                            <?php
                            $socialLinkClass = 'top-bar-social-icon';
                            require __DIR__ . '/social-icons.php';
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </header>

        <div class="header-main">
            <div class="container header-main-inner header-nav-align-<?= Helpers::e($navAlign) ?><?= $searchVisible ? '' : ' no-search' ?>">
                <button type="button" class="header-icon-btn mega-menu-toggle header-menu-btn" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="site-mega-nav">
                    <svg class="mega-menu-toggle-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                    <svg class="mega-menu-toggle-close" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>

                <?php foreach ($headerOrder as $part): ?>
                    <?php if ($part === 'logo' && $logoVisible): ?>
                        <a href="/" class="logo" aria-label="<?= Helpers::e($siteName) ?>">
                            <?php $logoClass = 'header-logo-img'; $logoWidth = 148; require __DIR__ . '/site-logo.php'; ?>
                        </a>
                    <?php elseif ($part === 'nav' && $navVisible): ?>
                        <nav class="header-nav" aria-label="Navigation">
                            <a href="/">Accueil</a>
                            <a href="/catalogue.php">Catalogue</a>
                            <a href="/a-propos">Qui sommes-nous</a>
                            <a href="/conseils">Conseils</a>
                            <a href="/contact">Contact</a>
                        </nav>
                    <?php elseif ($part === 'search' && $searchVisible): ?>
                        <div class="header-search-wrap" data-search-suggest>
                            <form class="header-search" action="/catalogue.php" method="get" role="search" data-search-form>
                                <input type="search" name="q" value="<?= Helpers::e($searchQuery) ?>" placeholder="Rechercher un produit, une marque…" aria-label="Rechercher" data-search-input autocomplete="off" aria-autocomplete="list" aria-controls="header-search-suggest" aria-expanded="false">
                                <button type="submit" aria-label="Rechercher">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                                </button>
                            </form>
                            <div class="header-search-suggest" id="header-search-suggest" data-search-results hidden role="listbox" aria-label="Suggestions de recherche"></div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <div class="header-actions">
                    <?php if ($searchVisible): ?>
                        <button type="button" class="header-icon-btn header-search-toggle" data-search-toggle aria-label="Rechercher" aria-expanded="false" aria-controls="header-mobile-search">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                        </button>
                    <?php endif; ?>
                    <a href="<?= Helpers::e($compteHref) ?>" class="header-icon-btn header-account-btn" aria-label="<?= Helpers::e($compteLabel) ?>" title="<?= Helpers::e($compteLabel) ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="8" r="3.5"/>
                            <path d="M5.5 19.5c1.5-3.2 3.9-4.8 6.5-4.8s5 1.6 6.5 4.8"/>
                        </svg>
                    </a>
                    <a href="/panier" class="header-icon-btn header-cart-btn" data-cart-link aria-label="Panier">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M6 6h15l-1.5 9h-12z"/>
                            <path d="M6 6L5 3H2"/>
                            <circle cx="9" cy="20" r="1.4"/>
                            <circle cx="18" cy="20" r="1.4"/>
                        </svg>
                        <span class="header-cart-badge" data-cart-badge hidden>0</span>
                    </a>
                </div>
            </div>

            <?php if ($searchVisible): ?>
                <div class="header-mobile-search" id="header-mobile-search" data-mobile-search hidden>
                    <div class="container">
                        <div class="header-search-wrap" data-search-suggest>
                            <form class="header-search" action="/catalogue.php" method="get" role="search" data-search-form>
                                <input type="search" name="q" value="<?= Helpers::e($searchQuery) ?>" placeholder="Rechercher un produit, une marque…" aria-label="Rechercher" data-search-input autocomplete="off">
                                <button type="submit" aria-label="Rechercher">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                                </button>
                            </form>
                            <div class="header-search-suggest" data-search-results hidden role="listbox" aria-label="Suggestions de recherche"></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($menuTree)): ?>
        <nav class="mega-nav mega-nav-align-<?= Helpers::e($megaAlign) ?>" id="site-mega-nav" data-mega-nav aria-label="Menu">
            <div class="mega-mobile-chrome">
                <div class="mega-mobile-chrome-top">
                    <strong>Menu</strong>
                    <button type="button" class="mega-mobile-close" data-mega-close aria-label="Fermer le menu">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </div>
                <form class="mega-mobile-search" action="/catalogue.php" method="get" role="search">
                    <input type="search" name="q" value="<?= Helpers::e($searchQuery) ?>" placeholder="Rechercher…" aria-label="Rechercher">
                    <button type="submit" aria-label="Rechercher">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                    </button>
                </form>
                <div class="mega-mobile-pages">
                    <a href="/">Accueil</a>
                    <a href="/catalogue.php">Catalogue</a>
                    <a href="/a-propos">Qui sommes-nous</a>
                    <a href="/conseils">Conseils</a>
                    <a href="/contact">Contact</a>
                    <a href="/panier">Panier</a>
                    <a href="<?= Helpers::e($compteHref) ?>"><?= Helpers::e($compteLabel) ?></a>
                </div>
                <p class="mega-mobile-label">Catégories</p>
            </div>
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
                                <svg class="mega-nav-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
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
