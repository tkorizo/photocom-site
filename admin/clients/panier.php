<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();
Database::migrate();

$customerId = (int) ($_GET['id'] ?? 0);
$customer = $customerId > 0 ? CustomerRepository::find($customerId) : null;
if (!$customer) {
    Helpers::flash('error', 'Compte client introuvable.');
    Helpers::redirect('/admin/clients/paniers.php');
}

$items = CustomerCartRepository::getItems($customerId);
$count = CustomerCartRepository::itemsCount($items);
$total = CustomerCartRepository::itemsTotal($items);
$phone = (string) ($customer['phone'] ?? '');
$wa = CustomerCartRepository::whatsappDigits($phone);
$msg = CustomerCartRepository::contactMessage($customer, $items);
$waUrl = $wa !== '' ? 'https://wa.me/' . $wa . '?text=' . rawurlencode($msg) : '';
$mailUrl = 'mailto:' . rawurlencode((string) $customer['email'])
    . '?subject=' . rawurlencode('Votre panier PHOTOCOM')
    . '&body=' . rawurlencode($msg);
$telUrl = $phone !== '' ? 'tel:' . preg_replace('/[^\d+]/', '', $phone) : '';

$pageTitle = 'Panier — ' . (string) $customer['name'];
$currentPage = 'customer-carts';

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Panier de <?= Helpers::e((string) $customer['name']) ?></h2>
            <p class="text-muted">
                <?= Helpers::e((string) $customer['email']) ?>
                <?= $phone !== '' ? ' · ' . Helpers::e($phone) : '' ?>
                · <?= $count ?> article<?= $count > 1 ? 's' : '' ?>
                · <?= Helpers::e(Helpers::formatPrice($total)) ?>
            </p>
        </div>
        <div class="table-actions">
            <?php if ($waUrl !== ''): ?>
                <a href="<?= Helpers::e($waUrl) ?>" class="btn btn-primary" target="_blank" rel="noopener">Contacter sur WhatsApp</a>
            <?php endif; ?>
            <a href="<?= Helpers::e($mailUrl) ?>" class="btn">Contacter par e-mail</a>
            <?php if ($telUrl !== ''): ?>
                <a href="<?= Helpers::e($telUrl) ?>" class="btn">Appeler</a>
            <?php endif; ?>
            <a href="/admin/clients/paniers.php" class="btn btn-outline">Tous les paniers</a>
        </div>
    </div>

    <?php if ($items === []): ?>
        <p class="empty-state">Ce panier est vide.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Produit</th>
                    <th>Qté</th>
                    <th>Prix unit.</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <?php
                    $line = (float) $item['price'] * (int) $item['qty'];
                    $href = !empty($item['slug']) ? '/produit/' . rawurlencode((string) $item['slug']) : '/admin/produits/edit.php?id=' . (int) $item['id'];
                    ?>
                    <tr>
                        <td>
                            <?php if (!empty($item['image'])): ?>
                                <img src="<?= Helpers::e((string) $item['image']) ?>" alt="" class="thumb thumb-sm">
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= Helpers::e($href) ?>" target="_blank" rel="noopener" class="product-link">
                                <?= Helpers::e((string) $item['name']) ?>
                            </a>
                            <?php if (!empty($item['brand'])): ?>
                                <div><small class="text-muted"><?= Helpers::e((string) $item['brand']) ?></small></div>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) $item['qty'] ?></td>
                        <td><?= Helpers::e(Helpers::formatPrice((float) $item['price'])) ?></td>
                        <td><strong><?= Helpers::e(Helpers::formatPrice($line)) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p style="margin-top:1rem;text-align:right">
            <strong>Total TTC : <?= Helpers::e(Helpers::formatPrice($total)) ?></strong>
        </p>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
