<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireCatalog();
Database::migrate();

$pageTitle = 'Paniers clients';
$currentPage = 'customer-carts';

$carts = CustomerCartRepository::listNonEmpty();

require dirname(__DIR__) . '/includes/header.php';
?>

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success"><?= Helpers::e($message) ?></div>
<?php endif; ?>
<?php if ($message = Helpers::flash('error')): ?>
    <div class="alert alert-error"><?= Helpers::e($message) ?></div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Paniers clients</h2>
            <p class="text-muted">
                Paniers enregistrés sur les comptes clients.
                <?= count($carts) ?> panier<?= count($carts) > 1 ? 's' : '' ?> en cours.
                Contactez le client par WhatsApp ou e-mail pour relancer la commande.
            </p>
        </div>
        <a href="/admin/clients/index.php" class="btn btn-outline">Comptes clients</a>
    </div>

    <?php if ($carts === []): ?>
        <p class="empty-state">Aucun panier enregistré pour le moment. Les paniers apparaissent ici dès qu’un client connecté ajoute un produit.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Contact</th>
                    <th>Articles</th>
                    <th>Total</th>
                    <th>Mis à jour</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($carts as $cart): ?>
                    <?php
                    $cust = $cart['customer'];
                    $cid = (int) $cust['id'];
                    $phone = (string) ($cust['phone'] ?? '');
                    $wa = CustomerCartRepository::whatsappDigits($phone);
                    $msg = CustomerCartRepository::contactMessage($cust, $cart['items']);
                    $waUrl = $wa !== '' ? 'https://wa.me/' . $wa . '?text=' . rawurlencode($msg) : '';
                    $mailUrl = 'mailto:' . rawurlencode((string) $cust['email'])
                        . '?subject=' . rawurlencode('Votre panier PHOTOCOM')
                        . '&body=' . rawurlencode($msg);
                    ?>
                    <tr>
                        <td>
                            <strong><?= Helpers::e((string) $cust['name']) ?></strong>
                            <?php if ((int) ($cust['is_active'] ?? 0) !== 1): ?>
                                <span class="badge badge-muted">Désactivé</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?= Helpers::e((string) $cust['email']) ?></div>
                            <small class="text-muted"><?= $phone !== '' ? Helpers::e($phone) : 'Pas de téléphone' ?></small>
                        </td>
                        <td><?= (int) $cart['count'] ?></td>
                        <td><?= Helpers::e(Helpers::formatPrice((float) $cart['total'])) ?></td>
                        <td><?= Helpers::e(substr((string) $cart['updated_at'], 0, 16)) ?></td>
                        <td class="table-actions">
                            <a href="/admin/clients/panier.php?id=<?= $cid ?>" class="btn btn-sm btn-primary">Voir le panier</a>
                            <?php if ($waUrl !== ''): ?>
                                <a href="<?= Helpers::e($waUrl) ?>" class="btn btn-sm" target="_blank" rel="noopener">WhatsApp</a>
                            <?php endif; ?>
                            <a href="<?= Helpers::e($mailUrl) ?>" class="btn btn-sm">E-mail</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
