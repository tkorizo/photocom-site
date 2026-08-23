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
$pageTitle = 'Mon compte';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $seoTitle = $pageTitle;
    $seoDescription = 'Votre espace client PHOTOCOM.';
    $seoRobots = 'noindex,nofollow';
    require __DIR__ . '/includes/site-seo.php';
    ?>
    <?php require __DIR__ . '/includes/site-favicon.php'; ?>
    <?php require __DIR__ . '/includes/site-assets.php'; ?>
</head>
<body class="page-account page-editorial page-contact">
    <?php require __DIR__ . '/includes/site-header.php'; ?>

    <main>
        <section class="editorial-hero">
            <div class="container">
                <div class="editorial-hero-copy">
                    <p class="section-label">Espace client</p>
                    <h1>Bonjour <?= Helpers::e((string) ($customer['name'] ?? '')) ?></h1>
                    <p class="editorial-lead">Voici votre espace compte PHOTOCOM.</p>
                </div>
            </div>
        </section>

        <section class="editorial-section section-light">
            <div class="container contact-layout">
                <div class="contact-main">
                    <?php if ($message = Helpers::flash('success')): ?>
                        <div class="account-alert account-alert-success"><?= Helpers::e($message) ?></div>
                    <?php endif; ?>

                    <div class="contact-panel contact-panel-accent">
                        <h2 class="account-panel-title">Informations</h2>
                        <dl class="account-meta">
                            <div>
                                <dt>Nom</dt>
                                <dd><?= Helpers::e((string) ($customer['name'] ?? '')) ?></dd>
                            </div>
                            <div>
                                <dt>E-mail</dt>
                                <dd><?= Helpers::e((string) ($customer['email'] ?? '')) ?></dd>
                            </div>
                        </dl>

                        <div class="account-actions contact-wa-actions">
                            <a href="/catalogue.php" class="btn btn-primary">Voir le catalogue</a>
                            <a href="/panier" class="btn btn-outline">Voir mon panier</a>
                            <a href="/contact" class="btn btn-outline">Nous contacter</a>
                            <a href="/compte/deconnexion" class="btn btn-outline">Se déconnecter</a>
                        </div>
                    </div>

                    <div class="contact-panel" data-account-cart>
                        <h2 class="account-panel-title">Mon panier</h2>
                        <p class="account-cart-meta">
                            <span data-account-cart-count><?= count($savedCart) ?> article<?= count($savedCart) > 1 ? 's' : '' ?></span>
                            ·
                            <strong data-account-cart-total>—</strong>
                        </p>
                        <p class="account-cart-empty" data-account-cart-empty<?= $savedCart !== [] ? ' hidden' : '' ?>>
                            Votre panier est vide pour le moment.
                        </p>
                        <div class="account-cart-list" data-account-cart-list<?= $savedCart === [] ? ' hidden' : '' ?>></div>
                        <div class="contact-wa-actions" style="margin-top:1rem">
                            <a href="/panier" class="btn btn-primary">Ouvrir le panier</a>
                        </div>
                    </div>
                </div>

                <aside class="contact-aside">
                    <div class="contact-panel">
                        <h3>Besoin d’un conseil ?</h3>
                        <p>Notre équipe vous accompagne pour le matériel photo, vidéo et studio.</p>
                        <div class="contact-wa-actions">
                            <a href="/contact" class="btn btn-outline">Contacter PHOTOCOM</a>
                        </div>
                    </div>
                </aside>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/includes/site-footer.php'; ?>
    <?php require __DIR__ . '/includes/site-chat.php'; ?>
    <?php require __DIR__ . '/includes/site-cookies.php'; ?>
    <?php require __DIR__ . '/includes/site-end-scripts.php'; ?>
</body>
</html>
