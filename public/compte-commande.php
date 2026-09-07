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
$reference = strtoupper(trim((string) ($_GET['ref'] ?? '')));
$order = $reference !== '' && $customerId > 0
    ? OrderRepository::findByReference($reference, $customerId)
    : null;

if (!$order) {
    http_response_code(404);
}

$pageTitle = $order ? ('Commande ' . $order['reference']) : 'Commande introuvable';
$waUrl = $order ? OrderRepository::whatsappUrl($order, (string) $whatsapp) : '#';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $seoTitle = $pageTitle;
    $seoDescription = 'Détail de votre commande PHOTOCOM.';
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
                    <h1><?= $order ? Helpers::e((string) $order['reference']) : 'Commande introuvable' ?></h1>
                    <?php if ($order): ?>
                        <p class="editorial-lead">
                            <?= Helpers::e(substr((string) $order['created_at'], 0, 16)) ?>
                            · <?= Helpers::e(OrderRepository::statusLabel((string) $order['status'])) ?>
                        </p>
                    <?php endif; ?>
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

                <?php if (!$order): ?>
                    <div class="contact-panel">
                        <p>Cette commande n’existe pas ou n’est pas liée à votre compte.</p>
                        <p style="margin-top:1rem"><a href="/mon-compte" class="btn btn-primary">Retour à mes commandes</a></p>
                    </div>
                <?php else: ?>
                    <div class="shop-account-grid">
                        <div class="shop-account-main">
                            <div class="contact-panel">
                                <h2 class="account-panel-title">Articles</h2>
                                <div class="shop-order-lines">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <?php
                                        $href = !empty($item['slug']) ? '/produit/' . rawurlencode((string) $item['slug']) : '/catalogue.php';
                                        $line = (float) ($item['price'] ?? 0) * (int) ($item['qty'] ?? 0);
                                        ?>
                                        <article class="shop-order-line">
                                            <a href="<?= Helpers::e($href) ?>" class="shop-order-line-media">
                                                <?php if (!empty($item['image'])): ?>
                                                    <img src="<?= Helpers::e((string) $item['image']) ?>" alt="">
                                                <?php else: ?>
                                                    <span class="product-placeholder">PC</span>
                                                <?php endif; ?>
                                            </a>
                                            <div>
                                                <?php if (!empty($item['brand'])): ?>
                                                    <p class="product-brand"><?= Helpers::e((string) $item['brand']) ?></p>
                                                <?php endif; ?>
                                                <h3><a href="<?= Helpers::e($href) ?>"><?= Helpers::e((string) $item['name']) ?></a></h3>
                                                <p>× <?= (int) $item['qty'] ?> · <?= Helpers::e(Helpers::formatPrice((float) $item['price'])) ?> / unité</p>
                                            </div>
                                            <strong><?= Helpers::e(Helpers::formatPrice($line)) ?></strong>
                                            <button
                                                type="button"
                                                class="btn btn-outline btn-sm"
                                                data-cart-add
                                                data-id="<?= (int) $item['id'] ?>"
                                                data-slug="<?= Helpers::e((string) ($item['slug'] ?? '')) ?>"
                                                data-name="<?= Helpers::e((string) $item['name']) ?>"
                                                data-price="<?= Helpers::e((string) $item['price']) ?>"
                                                data-image="<?= Helpers::e((string) ($item['image'] ?? '')) ?>"
                                                data-brand="<?= Helpers::e((string) ($item['brand'] ?? '')) ?>"
                                                data-qty="<?= (int) $item['qty'] ?>"
                                            >Remettre au panier</button>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <aside class="shop-account-aside">
                            <div class="contact-panel contact-panel-accent">
                                <h2 class="account-panel-title">Récapitulatif</h2>
                                <dl class="account-meta">
                                    <div>
                                        <dt>Total TTC</dt>
                                        <dd><?= Helpers::e(Helpers::formatPrice((float) $order['total'])) ?></dd>
                                    </div>
                                    <div>
                                        <dt>Statut</dt>
                                        <dd><?= Helpers::e(OrderRepository::statusLabel((string) $order['status'])) ?></dd>
                                    </div>
                                </dl>
                                <p class="account-cart-empty">Aucun paiement en ligne : la commande se finalise sur WhatsApp.</p>
                                <div class="contact-wa-actions" style="margin-top:1rem">
                                    <?php if ((string) $order['status'] !== OrderRepository::STATUS_CANCELLED): ?>
                                        <a href="<?= Helpers::e($waUrl) ?>" class="btn btn-primary" target="_blank" rel="noopener">Renvoyer sur WhatsApp</a>
                                    <?php endif; ?>
                                    <a href="/mon-compte" class="btn btn-outline">Toutes les commandes</a>
                                </div>
                            </div>
                        </aside>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/includes/site-footer.php'; ?>
    <?php require __DIR__ . '/includes/site-chat.php'; ?>
    <?php require __DIR__ . '/includes/site-cookies.php'; ?>
    <?php require __DIR__ . '/includes/site-end-scripts.php'; ?>
</body>
</html>
