<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/includes/init.php';

$pageTitle = 'Panier';
$activeNav = 'catalogue';
$waDigits = preg_replace('/\D+/', '', (string) $whatsapp) ?: (string) $whatsapp;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $seoTitle = 'Panier';
    $seoDescription = 'Votre sélection PHOTOCOM — validez votre commande WhatsApp.';
    $seoRobots = 'noindex,follow';
    require __DIR__ . '/includes/site-seo.php';
    ?>
    <?php require __DIR__ . '/includes/site-favicon.php'; ?>
    <link rel="stylesheet" href="/assets/css/public.css">
    <link rel="stylesheet" href="/assets/css/chat.css">
</head>
<body class="page-cart page-editorial">
    <?php require __DIR__ . '/includes/site-header.php'; ?>

    <main data-cart-page>
        <section class="editorial-hero product-page-hero">
            <div class="container">
                <div class="editorial-hero-copy">
                    <p class="section-label">Commande</p>
                    <h1>Votre panier</h1>
                    <p class="editorial-lead" data-cart-count-label>Sélectionnez des produits depuis le catalogue.</p>
                </div>
            </div>
        </section>

        <section class="editorial-section section-light">
            <div class="container">
                <div class="editorial-empty" data-cart-empty>
                    <h2>Panier vide</h2>
                    <p>Ajoutez des produits via le bouton « Ajouter au panier », puis revenez ici pour commander.</p>
                    <p><a href="/catalogue.php" class="btn btn-primary">Voir le catalogue</a></p>
                </div>

                <div class="cart-layout" data-cart-content hidden>
                    <div class="cart-list" data-cart-list></div>

                    <aside class="cart-summary">
                        <h2>Récapitulatif</h2>
                        <div class="cart-summary-row">
                            <span>Total TTC</span>
                            <strong data-cart-total>0,00 MAD TTC</strong>
                        </div>
                        <p class="cart-summary-note">La commande est envoyée via WhatsApp. Aucun paiement en ligne.</p>
                        <a
                            href="#"
                            class="btn btn-primary btn-cart-order"
                            data-cart-order
                            target="_blank"
                            rel="noopener"
                        >Commander sur WhatsApp</a>
                        <button type="button" class="btn btn-outline" data-cart-clear>Vider le panier</button>
                        <a href="/catalogue.php" class="cart-continue">Continuer vos achats</a>
                    </aside>
                </div>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/includes/site-footer.php'; ?>
    <script src="/assets/js/mega-menu.js"></script>
    <script src="/assets/js/chat.js"></script>
</body>
</html>
