<?php

declare(strict_types=1);

/**
 * Redirections 301 d’anciennes URLs (WooCommerce / slugs disparus).
 * Les pages actuelles (/produit/… existants, catalogue, articles, pages légales)
 * ne sont PAS listées ici : le routeur les sert normalement.
 *
 * @return array{exact: array<string,string>, prefixes: array<string,string>}
 */
return [
    'exact' => [
        '/produit/adaptateur-monture-bowens-pour-flashs-elinchrom-2' => '/produit/adaptateur-monture-bowens-pour-flashs-elinchrom',
        '/produit/adaptateur-monture-elinchrom-pour-flashs-bowens-2' => '/produit/adaptateur-monture-elinchrom-pour-flashs-bowens',
        '/produit/canon-hybride-eos-r50-objectif-rf-s-18-45mm-f4-5-6-3-is-stm' => '/',
        '/produit/nikon-d3500' => '/',
        '/produit/nikon-d5600' => '/',
        '/produit/dji-osmo-mobile-8p-standard-combo' => '/',
        '/contactez-nous' => '/contact',
        '/cinq-regles-simples-pour-reussir-la-composition-de-vos-photos' => '/',
        '/wishlist' => '/',
        '/compare' => '/',
    ],
    'prefixes' => [
        '/shop' => '/',
        '/product-category' => '/',
        '/product-tag' => '/',
        '/product' => '/',
        '/brand' => '/',
    ],
];
