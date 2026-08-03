<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/includes/init.php';

$pageTitle = 'Qui sommes-nous';
$activeNav = 'about';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $seoTitle = $pageTitle;
    $seoDescription = 'PHOTOCOM, spécialiste du matériel photo et vidéo professionnel à Casablanca. Distribution officielle, conseil expert et service après-vente.';
    require __DIR__ . '/includes/site-seo.php';
    ?>
    <?php require __DIR__ . '/includes/site-favicon.php'; ?>
    <link rel="stylesheet" href="/assets/css/public.css">
    <link rel="stylesheet" href="/assets/css/chat.css">
</head>
<body class="page-about">
    <?php require __DIR__ . '/includes/site-header.php'; ?>

    <main>
        <section class="about-hero">
            <div class="container about-hero-inner">
                <p class="section-label">Notre maison</p>
                <h1>PHOTOCOM, partenaire photo &amp; vidéo au Maroc</h1>
                <p class="about-hero-lead">Depuis Casablanca, nous accompagnons photographes, créateurs et entreprises avec du matériel authentique, un conseil technique précis et un service de proximité.</p>
            </div>
        </section>

        <section class="home-section section-light">
            <div class="container about-intro">
                <div class="about-intro-text">
                    <p class="section-label">Notre histoire</p>
                    <h2>Une expertise construite sur le terrain</h2>
                    <p>PHOTOCOM est née d’une conviction simple : le matériel professionnel mérite un interlocuteur qui comprend les enjeux du plateau, du studio et de la production.</p>
                    <p>Nous sélectionnons des marques reconnues, testons les produits, et guidons chaque client — du premier appareil au setup studio complet — vers la solution la plus juste pour son usage.</p>
                    <p>Notre équipe, basée à Casablanca, reste disponible pour le conseil avant-vente, la démonstration et le suivi après livraison.</p>
                </div>
                <aside class="about-facts">
                    <div class="about-fact">
                        <strong>Casablanca</strong>
                        <span>Showroom &amp; retrait magasin</span>
                    </div>
                    <div class="about-fact">
                        <strong>100&nbsp;%</strong>
                        <span>Matériel authentique</span>
                    </div>
                    <div class="about-fact">
                        <strong>National</strong>
                        <span>Livraison partout au Maroc</span>
                    </div>
                    <div class="about-fact">
                        <strong>WhatsApp</strong>
                        <span>Devis sous 24&nbsp;h</span>
                    </div>
                </aside>
            </div>
        </section>

        <section class="home-section section-muted">
            <div class="container">
                <header class="section-head section-align-center">
                    <span class="section-label">Ce qui nous définit</span>
                    <h2>Quatre engagements concrets</h2>
                </header>
                <div class="about-pillars">
                    <article class="about-pillar">
                        <h3>Sélection exigeante</h3>
                        <p>Nous ne listons que des références fiables : boîtiers, objectifs, éclairage, studio et accessoires issus de circuits officiels.</p>
                    </article>
                    <article class="about-pillar">
                        <h3>Conseil technique</h3>
                        <p>Besoin d’un hybride, d’un flash studio ou d’un kit vidéo ? Nous vous aidons à choisir selon votre niveau, votre budget et vos projets.</p>
                    </article>
                    <article class="about-pillar">
                        <h3>Disponibilité locale</h3>
                        <p>Retrait en magasin, questions techniques, SAV : une relation directe, sans barrière entre vous et l’équipe PHOTOCOM.</p>
                    </article>
                    <article class="about-pillar">
                        <h3>Accompagnement</h3>
                        <p>Devis WhatsApp, suivi de commande, conseils d’usage : nous restons présents avant et après l’achat.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="home-section section-light">
            <div class="container about-audience">
                <header class="section-head">
                    <span class="section-label">Pour qui</span>
                    <h2>Nous travaillons avec</h2>
                </header>
                <ul class="about-audience-list">
                    <li>
                        <strong>Photographes &amp; vidéastes</strong>
                        <span>Matériel de prise de vue, optique et lumière pour la production quotidienne.</span>
                    </li>
                    <li>
                        <strong>Studios &amp; agences</strong>
                        <span>Éclairage, fonds, grip et solutions durables pour les plateaux.</span>
                    </li>
                    <li>
                        <strong>Entreprises &amp; écoles</strong>
                        <span>Équipements pour la communication, la formation et les projets audiovisuels.</span>
                    </li>
                    <li>
                        <strong>Passionnés exigeants</strong>
                        <span>Conseils clairs pour monter en gamme sans se tromper d’investissement.</span>
                    </li>
                </ul>
            </div>
        </section>

        <section class="home-section section-dark about-cta-band">
            <div class="container about-cta-inner">
                <div>
                    <p class="section-label section-label-dark">Parlons de votre projet</p>
                    <h2>Besoin d’un devis ou d’un conseil ?</h2>
                    <p>Expliquez-nous votre usage : nous vous orientons vers le bon matériel et un devis personnalisé.</p>
                </div>
                <div class="about-cta-actions">
                    <a href="/catalogue.php" class="btn btn-primary">Voir le catalogue</a>
                    <a href="https://wa.me/<?= preg_replace('/\D+/', '', $whatsapp) ?>" class="btn btn-outline-light" target="_blank" rel="noopener">WhatsApp</a>
                    <a href="/#contact" class="btn btn-outline-light">Contact</a>
                </div>
            </div>
        </section>

        <section class="home-section section-light">
            <div class="container about-contact-grid">
                <div>
                    <p class="section-label">Nous trouver</p>
                    <h2>PHOTOCOM à Casablanca</h2>
                    <ul class="about-contact-list">
                        <li><strong>Adresse</strong><span><?= Helpers::e($address) ?></span></li>
                        <li><strong>Téléphone</strong><a href="tel:<?= preg_replace('/\s+/', '', $phone) ?>"><?= Helpers::e($phone) ?></a></li>
                        <li><strong>Email</strong><a href="mailto:<?= Helpers::e($email) ?>"><?= Helpers::e($email) ?></a></li>
                        <li><strong>Horaires</strong><span><?= Helpers::e($hours) ?></span></li>
                    </ul>
                </div>
                <div class="about-links">
                    <p class="section-label">Ressources</p>
                    <h3>Aller plus loin</h3>
                    <ul>
                        <li><a href="/conseils">Conseils &amp; articles</a></li>
                        <li><a href="/page/comment-commander">Comment commander</a></li>
                        <li><a href="/page/politique-livraison">Livraison</a></li>
                        <li><a href="/page/politique-retour">Retours</a></li>
                        <li><a href="/page/mentions-legales">Mentions légales</a></li>
                    </ul>
                </div>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/includes/site-footer.php'; ?>
    <?php require __DIR__ . '/includes/site-chat.php'; ?>
    <?php require __DIR__ . '/includes/site-cookies.php'; ?>
    <script src="/assets/js/mega-menu.js"></script>
    <script src="/assets/js/chat.js"></script>
</body>
</html>
