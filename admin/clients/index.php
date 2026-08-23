<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();
Database::migrate();

$pageTitle = 'Comptes clients';
$currentPage = 'customers';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        Helpers::flash('error', 'Session expirée.');
        Helpers::redirect('/admin/clients/index.php');
    }

    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    $customer = $id > 0 ? CustomerRepository::find($id) : null;

    try {
        if (!$customer) {
            throw new RuntimeException('Compte client introuvable.');
        }

        if ($action === 'delete') {
            CustomerRepository::delete($id);
            if (class_exists('AuditLog')) {
                AuditLog::record('customer.delete', 'customer', $id, [
                    'email' => $customer['email'] ?? null,
                ]);
            }
            Helpers::flash('success', 'Compte client supprimé.');
        } elseif ($action === 'toggle') {
            $next = ((int) ($customer['is_active'] ?? 0) === 1) ? false : true;
            CustomerRepository::setActive($id, $next);
            if (class_exists('AuditLog')) {
                AuditLog::record('customer.toggle', 'customer', $id, [
                    'email' => $customer['email'] ?? null,
                    'is_active' => $next,
                ]);
            }
            Helpers::flash('success', $next ? 'Compte réactivé.' : 'Compte désactivé.');
        }
    } catch (RuntimeException $e) {
        Helpers::flash('error', $e->getMessage());
    }

    Helpers::redirect('/admin/clients/index.php' . (!empty($_GET['q']) ? '?q=' . rawurlencode((string) $_GET['q']) : ''));
}

$search = trim((string) ($_GET['q'] ?? ''));
$customers = CustomerRepository::all($search !== '' ? $search : null);
$total = CustomerRepository::count();

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
            <h2>Comptes clients</h2>
            <p class="text-muted">
                Inscriptions créées depuis le bouton <strong>Compte</strong> du site.
                <?= (int) $total ?> compte<?= $total > 1 ? 's' : '' ?> au total.
            </p>
        </div>
        <a href="/compte" target="_blank" class="btn btn-outline">Voir le formulaire public</a>
    </div>

    <form method="get" class="search-form" style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap;">
        <input type="search" name="q" value="<?= Helpers::e($search) ?>" placeholder="Rechercher nom, e-mail, téléphone…" style="flex:1;min-width:220px;">
        <button type="submit" class="btn btn-primary">Rechercher</button>
        <?php if ($search !== ''): ?>
            <a href="/admin/clients/index.php" class="btn btn-outline">Réinitialiser</a>
        <?php endif; ?>
    </form>

    <?php if ($customers === []): ?>
        <p class="text-muted">Aucun compte client pour le moment.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>E-mail</th>
                    <th>Téléphone</th>
                    <th>Statut</th>
                    <th>Créé le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $c): ?>
                    <?php $active = (int) ($c['is_active'] ?? 0) === 1; ?>
                    <tr>
                        <td><strong><?= Helpers::e((string) $c['name']) ?></strong></td>
                        <td><?= Helpers::e((string) $c['email']) ?></td>
                        <td><?= Helpers::e((string) ($c['phone'] ?? '—')) ?></td>
                        <td>
                            <span class="badge <?= $active ? 'badge-success' : 'badge-muted' ?>">
                                <?= $active ? 'Actif' : 'Désactivé' ?>
                            </span>
                        </td>
                        <td><?= Helpers::e(substr((string) $c['created_at'], 0, 16)) ?></td>
                        <td class="table-actions">
                            <form method="post" style="display:inline;">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <input type="hidden" name="action" value="toggle">
                                <button type="submit" class="btn btn-sm"><?= $active ? 'Désactiver' : 'Réactiver' ?></button>
                            </form>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce compte client ?');">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
