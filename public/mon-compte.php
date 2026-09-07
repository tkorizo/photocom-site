<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/includes/init.php';

Database::migrate();
CustomerAuth::startSession();

if (!CustomerAuth::check()) {
    Helpers::redirect('/compte?tab=login');
}

$customer = CustomerAuth::user();
$customerId = (int) ($customer['id'] ?? 0);
$savedCart = $customerId > 0 ? CustomerCartRepository::getItems($customerId) : [];
$orders = $customerId > 0 ? OrderRepository::forCustomer($customerId) : [];
$highlight = strtoupper(trim((string) ($_GET['commande'] ?? '')));
$pageTitle = 'Mon espace boutique';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $seoTitle = $pageTitle;
    $seoDescription = 'Votre espace boutique PHOTOCOM — panier et historique de commandes.';
    $seoRobots = 'noindex,nofollow';
    require __DIR__ . '/includes/site-seo.php';
    ?>
    <?php require __DIR__ . '/includes/site-favicon.php'; ?>
    <?php require __DIR__ . '/includes/site-assets.php'; ?>
</head>
<body class="page-account page-shop-account page-editorial page-contact">
    <?php require __DIR__ . '/includes/site-header.php'; ?>

    <main>
        <section class="editorial-hero">
            <div class="container">
                <div class="editorial-hero-copy">
                    <p class="section-label">Espace boutique</p>
                    <h1>Bonjour <?= Helpers::e((string) ($customer['name'] ?? '')) ?></h1>
                    <p class="editorial-lead">Retrouvez votre panier, vos commandes WhatsApp et relancez un achat en un clic.</p>
                </div>
            </div>
        </section>

        <section class="editorial-section section-light">
            <div class="container">
                <nav class="shop-account-nav" aria-label="Espace boutique">
                    <a href="/catalogue.php">Catalogue</a>
                    <a href="/panier">Panier</a>
                    <a href="/mon-compte" class="is-active">Mes commandes</a>
                    <a href="/compte/deconnexion">Déconnexion</a>
                </nav>

                <?php if ($message = Helpers::flash('success')): ?>
                    <div class="account-alert account-alert-success"><?= Helpers::e($message) ?></div>
                <?php endif; ?>
                <?php if ($highlight !== ''): ?>
                    <div class="account-alert account-alert-success">
                        Commande <?= Helpers::e($highlight) ?> enregistrée. Finalisez-la sur WhatsApp si la fenêtre ne s’est pas ouverte.
                    </div>
                <?php endif; ?>

                <div class="shop-account-grid">
                    <div class="shop-account-main">
                        <div class="contact-panel">
                            <h2 class="account-panel-title">Historique des commandes</h2>
                            <?php if ($orders === []): ?>
                                <p class="account-cart-empty">Aucune commande pour le moment. Ajoutez des produits au panier, puis cliquez sur « Commander sur WhatsApp » — la commande sera enregistrée ici.</p>
                                <p style="margin-top:1rem"><a href="/catalogue.php" class="btn btn-primary">Voir le catalogue</a></p>
                            <?php else: ?>
                                <div class="shop-order-list">
                                    <?php foreach ($orders as $order): ?>
                                        <?php
                                        $ref = (string) ($order['reference'] ?? '');
                                        $isHl = $highlight !== '' && strtoupper($ref) === $highlight;
                                        $countItems = 0;
                                        foreach ($order['items'] as $it) {
                                            $countItems += (int) ($it['qty'] ?? 0);
                                        }
                                        ?>
                                        <a class="shop-order-card<?= $isHl ? ' is-highlight' : '' ?>" href="/mon-compte/commande/<?= Helpers::e(rawurlencode($ref)) ?>">
                                            <div>
                                                <strong><?= Helpers::e($ref) ?></strong>
                                                <span class="shop-order-meta">
                                                    <?= Helpers::e(substr((string) ($order['created_at'] ?? ''), 0, 16)) ?>
                                                    · <?= (int) $countItems ?> article<?= $countItems > 1 ? 's' : '' ?>
                                                </span>
                                            </div>
                                            <div class="shop-order-card-end">
                                                <span class="shop-order-status shop-order-status--<?= Helpers::e((string) $order['status']) ?>">
                                                    <?= Helpers::e(OrderRepository::statusLabel((string) $order['status'])) ?>
                                                </span>
                                                <strong><?= Helpers::e(Helpers::formatPrice((float) $order['total'])) ?></strong>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <aside class="shop-account-aside">
                        <div class="contact-panel contact-panel-accent" data-account-cart>
                            <h2 class="account-panel-title">Panier en cours</h2>
                            <p class="account-cart-meta">
                                <span data-account-cart-count><?= count($savedCart) ?> article<?= count($savedCart) > 1 ? 's' : '' ?></span>
                                ·
                                <strong data-account-cart-total>—</strong>
                            </p>
                            <p class="account-cart-empty" data-account-cart-empty<?= $savedCart !== [] ? ' hidden' : '' ?>>
                                Panier vide.
                            </p>
                            <div class="account-cart-list" data-account-cart-list<?= $savedCart === [] ? ' hidden' : '' ?>></div>
                            <div class="contact-wa-actions" style="margin-top:1rem">
                                <a href="/panier" class="btn btn-primary">Ouvrir le panier</a>
                                <a href="/catalogue.php" class="btn btn-outline">Continuer les achats</a>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/includes/site-footer.php'; ?>
    <?php require __DIR__ . '/includes/site-chat.php'; ?>
    <?php require __DIR__ . '/includes/site-cookies.php'; ?>
    <?php require __DIR__ . '/includes/site-end-scripts.php'; ?>
</body>
</html>
