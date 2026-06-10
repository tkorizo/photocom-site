<?php

declare(strict_types=1);

/** @var array $menuTree */
/** @var string $siteName */
/** @var string $whatsapp */
/** @var string $promoText */
$searchQuery = $searchQuery ?? '';
$activeCatSlug = $activeCatSlug ?? trim($_GET['cat'] ?? '');
?>
<button type="button" class="sidebar-drawer-toggle" data-sidebar-drawer aria-label="Ouvrir le menu" aria-expanded="true" title="Menu">
    <svg class="icon-open" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
    <svg class="icon-closed" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
</button>

<div class="sidebar-backdrop" data-sidebar-backdrop hidden aria-hidden="true"></div>

<aside class="site-sidebar" data-sidebar aria-label="Navigation principale">
    <div class="sidebar-inner">
        <div class="sidebar-head">
            <a href="/" class="sidebar-logo">
                <?php $logoClass = 'sidebar-logo-img'; require __DIR__ . '/site-logo.php'; ?>
            </a>
            <button type="button" class="sidebar-close" data-sidebar-close aria-label="Fermer le menu">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="sidebar-promo">
            <p><?= Helpers::e($promoText) ?></p>
        </div>

        <form class="sidebar-search" action="/catalogue.php" method="get" role="search">
            <input type="search" name="q" value="<?= Helpers::e($searchQuery) ?>" placeholder="Rechercher un produit…" aria-label="Rechercher">
            <button type="submit" aria-label="Rechercher">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>
            </button>
        </form>

        <nav class="sidebar-nav" aria-label="Pages">
            <a href="/" class="sidebar-nav-link">Accueil</a>
            <a href="/catalogue.php" class="sidebar-nav-link">Catalogue</a>
            <a href="/page/mentions-legales" class="sidebar-nav-link">Qui sommes-nous</a>
            <a href="/#conseils" class="sidebar-nav-link">Conseils</a>
            <a href="/#contact" class="sidebar-nav-link">Contact</a>
        </nav>

        <div class="sidebar-section">
            <p class="sidebar-section-label">Univers</p>
            <div class="sidebar-accordion-list">
                <?php foreach ($menuTree as $index => $root): ?>
                    <?php
                    $hasChildren = !empty($root['children']);
                    $isActiveRoot = $activeCatSlug === ($root['slug'] ?? '');
                    $childActive = false;
                    if ($hasChildren && $activeCatSlug !== '') {
                        foreach ($root['children'] as $child) {
                            if (($child['slug'] ?? '') === $activeCatSlug) {
                                $childActive = true;
                                break;
                            }
                        }
                    }
                    $isOpen = $isActiveRoot || $childActive;
                    ?>
                    <div class="sidebar-accordion <?= $isOpen ? 'is-open' : '' ?>" data-accordion>
                        <?php if ($hasChildren): ?>
                            <button
                                type="button"
                                class="sidebar-accordion-trigger"
                                aria-expanded="<?= $isOpen ? 'true' : 'false' ?>"
                                aria-controls="sidebar-cat-<?= (int) $index ?>"
                            >
                                <span><?= Helpers::e($root['name']) ?></span>
                                <svg class="sidebar-chevron" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div id="sidebar-cat-<?= (int) $index ?>" class="sidebar-accordion-panel" <?= $isOpen ? '' : 'hidden' ?>>
                                <a href="/catalogue.php?cat=<?= Helpers::e($root['slug']) ?>" class="sidebar-cat-all">Tout voir</a>
                                <ul class="sidebar-sublist">
                                    <?php foreach ($root['children'] as $child): ?>
                                        <li>
                                            <a
                                                href="/catalogue.php?cat=<?= Helpers::e($child['slug']) ?>"
                                                class="sidebar-sub-link <?= $activeCatSlug === ($child['slug'] ?? '') ? 'is-active' : '' ?>"
                                            ><?= Helpers::e($child['name']) ?></a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="/catalogue.php?cat=<?= Helpers::e($root['slug']) ?>" class="sidebar-accordion-link <?= $isActiveRoot ? 'is-active' : '' ?>">
                                <?= Helpers::e($root['name']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="sidebar-foot">
            <div class="sidebar-actions">
                <div class="sidebar-lang" aria-label="Langue">
                    <span class="is-active">FR</span>
                    <a href="#" lang="ar">AR</a>
                    <a href="#" lang="en">EN</a>
                </div>
                <a href="/catalogue.php" class="sidebar-action-link">Panier</a>
                <a href="/admin/login.php" class="sidebar-action-link">Compte</a>
            </div>
            <?php require __DIR__ . '/social-icons.php'; ?>
            <a href="https://wa.me/<?= Helpers::e($whatsapp) ?>" class="btn btn-primary sidebar-whatsapp" target="_blank" rel="noopener">WhatsApp</a>
        </div>
    </div>
</aside>

<div class="mobile-bar">
    <button type="button" class="mobile-bar-btn" data-sidebar-toggle aria-label="Ouvrir le menu" aria-expanded="false">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
    </button>
    <a href="/" class="mobile-bar-logo" aria-label="<?= Helpers::e($siteName) ?>">
        <?php $logoClass = 'mobile-bar-logo-img'; $logoWidth = 110; require __DIR__ . '/site-logo.php'; ?>
    </a>
    <a href="/catalogue.php" class="mobile-bar-btn" aria-label="Catalogue">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>
    </a>
</div>
