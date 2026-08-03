<?php

declare(strict_types=1);

/**
 * Icône bouton « Ajouter au panier » : + et panier séparés (pas de chevauchement).
 *
 * @var int $iconSize  Taille du panier en px (défaut 18)
 */
$iconSize = isset($iconSize) ? (int) $iconSize : 18;
$plusSize = max(10, (int) round($iconSize * 0.7));
?>
<span class="btn-cart-add-icon" aria-hidden="true">
    <svg class="btn-cart-ico-plus" width="<?= $plusSize ?>" height="<?= $plusSize ?>" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
        <path d="M6 1.5v9M1.5 6h9"/>
    </svg>
    <svg class="btn-cart-ico-bag" width="<?= $iconSize ?>" height="<?= $iconSize ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6.5 7h11l-1.2 10.2a1.5 1.5 0 0 1-1.5 1.3H9.2a1.5 1.5 0 0 1-1.5-1.3L6.5 7Z"/>
        <path d="M9 7V5.8A3 3 0 0 1 12 2.8 3 3 0 0 1 15 5.8V7"/>
    </svg>
</span>
<?php unset($iconSize, $plusSize); ?>
