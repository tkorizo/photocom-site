<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireSuperAdmin();

$pageTitle = 'Journal d\'activité';
$currentPage = 'audit';

$entries = AuditLog::recent(400);
$logPath = AuditLog::path();
$logExists = is_file($logPath);
$logSize = $logExists ? filesize($logPath) : 0;

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Journal d'activité</h2>
            <p class="text-muted">
                Historique des modifications : qui a fait quoi, et quand.
                Fichier&nbsp;: <code><?= Helpers::e(str_replace(ROOT_PATH . '/', '', $logPath)) ?></code>
                <?php if ($logExists): ?>
                    (<?= number_format($logSize / 1024, 1, ',', ' ') ?> Ko)
                <?php endif; ?>
            </p>
        </div>
    </div>

    <?php if (empty($entries)): ?>
        <p class="empty-state">Aucune activité enregistrée pour le moment.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data-table data-table-compact">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Utilisateur</th>
                        <th>Rôle</th>
                        <th>Action</th>
                        <th>Cible</th>
                        <th>Détails</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <?php
                        $at = isset($entry['at']) ? strtotime((string) $entry['at']) : false;
                        $meta = $entry['meta'] ?? null;
                        $metaText = '';
                        if (is_array($meta) && $meta !== []) {
                            $parts = [];
                            foreach ($meta as $k => $v) {
                                if (is_scalar($v) || $v === null) {
                                    $parts[] = $k . '=' . (string) $v;
                                }
                            }
                            $metaText = implode(' · ', $parts);
                        }
                        $target = trim(((string) ($entry['entity'] ?? '')) . (($entry['entity_id'] ?? null) ? ' #' . $entry['entity_id'] : ''));
                        ?>
                        <tr>
                            <td><small><?= $at ? Helpers::e(date('d/m/Y H:i:s', $at)) : '—' ?></small></td>
                            <td>
                                <strong><?= Helpers::e((string) ($entry['user_name'] ?? '—')) ?></strong>
                                <div><small class="text-muted"><?= Helpers::e((string) ($entry['user_email'] ?? '')) ?></small></div>
                            </td>
                            <td><small><?= Helpers::e(UserRepository::roleLabel((string) ($entry['user_role'] ?? ''))) ?></small></td>
                            <td><?= Helpers::e(AuditLog::actionLabel((string) ($entry['action'] ?? ''))) ?></td>
                            <td><small><?= Helpers::e($target !== '' ? $target : '—') ?></small></td>
                            <td><small class="text-muted"><?= Helpers::e($metaText !== '' ? $metaText : '—') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
