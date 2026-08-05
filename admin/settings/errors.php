<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireSuperAdmin();

$pageTitle = 'Journal des erreurs';
$currentPage = 'errors';
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'clear') {
        ErrorLogger::clear();
        AuditLog::record('errors.clear', 'error_log', null);
        Helpers::flash('success', 'Journal des erreurs vidé.');
        Helpers::redirect('/admin/settings/errors.php');
    }
    if ($action === 'test') {
        ErrorLogger::warning('Test manuel du journal des erreurs depuis le dashboard.', [
            'type' => 'manual_test',
            'triggered_by' => Auth::user()['email'] ?? null,
        ]);
        Helpers::flash('success', 'Entrée de test ajoutée au journal.');
        Helpers::redirect('/admin/settings/errors.php');
    }
}

$entries = ErrorLogger::recent(400);
$logPath = ErrorLogger::path();
$logExists = is_file($logPath);
$logSize = ErrorLogger::size();
$logCount = ErrorLogger::count();

require dirname(__DIR__) . '/includes/header.php';
?>

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success"><?= Helpers::e($message) ?></div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header panel-header-split">
        <div>
            <h2>Journal des erreurs</h2>
            <p class="text-muted">
                Erreurs PHP capturées sur le site (warnings, exceptions, fatals).
                Fichier&nbsp;: <code><?= Helpers::e(str_replace(ROOT_PATH . '/', '', $logPath)) ?></code>
                — <?= (int) $logCount ?> entrée<?= $logCount > 1 ? 's' : '' ?>
                <?php if ($logExists): ?>
                    (<?= number_format($logSize / 1024, 1, ',', ' ') ?> Ko)
                <?php endif; ?>
            </p>
        </div>
        <div class="panel-actions">
            <form method="post" style="display:inline">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="action" value="test">
                <button type="submit" class="btn btn-outline btn-sm">Ajouter un test</button>
            </form>
            <?php if ($logCount > 0): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Vider tout le journal des erreurs&nbsp;?');">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="btn btn-danger btn-sm">Vider le journal</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($entries === []): ?>
        <p class="empty-state">Aucune erreur enregistrée pour le moment. Tant mieux.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table class="data-table data-table-compact">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Niveau</th>
                        <th>Message</th>
                        <th>URL</th>
                        <th>Fichier</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <?php
                        $at = isset($entry['at']) ? strtotime((string) $entry['at']) : false;
                        $level = (string) ($entry['level'] ?? 'info');
                        $ctx = is_array($entry['context'] ?? null) ? $entry['context'] : [];
                        $file = (string) ($ctx['file'] ?? '');
                        $line = $ctx['line'] ?? null;
                        $fileShort = $file !== '' ? str_replace(ROOT_PATH . '/', '', $file) : '';
                        if ($fileShort !== '' && $line) {
                            $fileShort .= ':' . $line;
                        }
                        $badgeClass = match ($level) {
                            'error' => 'badge-error',
                            'warning' => 'badge-warning',
                            'deprecated', 'notice' => 'badge-muted',
                            default => 'badge-info',
                        };
                        ?>
                        <tr>
                            <td><small><?= $at ? Helpers::e(date('d/m/Y H:i:s', $at)) : '—' ?></small></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= Helpers::e(ErrorLogger::levelLabel($level)) ?></span></td>
                            <td>
                                <strong><?= Helpers::e((string) ($entry['message'] ?? '')) ?></strong>
                                <?php if (!empty($ctx['class'])): ?>
                                    <div><small class="text-muted"><?= Helpers::e((string) $ctx['class']) ?></small></div>
                                <?php endif; ?>
                                <?php if (!empty($ctx['trace'])): ?>
                                    <details class="error-trace">
                                        <summary>Stack trace</summary>
                                        <pre><?= Helpers::e((string) $ctx['trace']) ?></pre>
                                    </details>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small>
                                    <?php if (!empty($entry['method'])): ?>
                                        <?= Helpers::e((string) $entry['method']) ?>
                                    <?php endif; ?>
                                    <?= Helpers::e((string) ($entry['url'] ?? '—')) ?>
                                </small>
                            </td>
                            <td><small class="text-muted"><?= Helpers::e($fileShort !== '' ? $fileShort : '—') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
