<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();

$pageTitle = 'Cookies & consentement';
$currentPage = 'cookies';
$errors = [];
$cookies = SettingRepository::cookies();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        SettingRepository::setMany([
            'cookies_enabled' => isset($_POST['cookies_enabled']) ? '1' : '0',
            'cookies_title' => trim((string) ($_POST['cookies_title'] ?? '')),
            'cookies_message' => trim((string) ($_POST['cookies_message'] ?? '')),
            'cookies_accept_label' => trim((string) ($_POST['cookies_accept_label'] ?? '')),
            'cookies_refuse_label' => trim((string) ($_POST['cookies_refuse_label'] ?? '')),
            'cookies_more_label' => trim((string) ($_POST['cookies_more_label'] ?? '')),
            'cookies_policy_url' => trim((string) ($_POST['cookies_policy_url'] ?? '/page/politique-cookies')),
        ]);
        AuditLog::record('settings.cookies', 'settings', null);
        Helpers::flash('success', 'Paramètres cookies enregistrés.');
        Helpers::redirect('/admin/settings/cookies.php');
    }
}

$cookies = SettingRepository::cookies();

require dirname(__DIR__) . '/includes/header.php';
?>

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success"><?= Helpers::e($message) ?></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-error"><?= Helpers::e($errors[0]) ?></div>
<?php endif; ?>

<div class="panel">
    <p class="text-muted">Configurez la bannière cookies affichée au démarrage du site public. La page légale associée se gère dans <a href="/admin/pages/index.php">Pages légales</a>.</p>

    <form method="post" class="form-grid">
        <?= Auth::csrfField() ?>

        <label class="checkbox-label">
            <input type="checkbox" name="cookies_enabled" value="1" <?= ($cookies['cookies_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
            Afficher la popup cookies sur le site
        </label>

        <label>Titre de la bannière
            <input type="text" name="cookies_title" value="<?= Helpers::e($cookies['cookies_title']) ?>">
        </label>
        <label>Message
            <textarea name="cookies_message" rows="4"><?= Helpers::e($cookies['cookies_message']) ?></textarea>
        </label>

        <div class="form-row">
            <label>Bouton accepter
                <input type="text" name="cookies_accept_label" value="<?= Helpers::e($cookies['cookies_accept_label']) ?>">
            </label>
            <label>Bouton refuser
                <input type="text" name="cookies_refuse_label" value="<?= Helpers::e($cookies['cookies_refuse_label']) ?>">
            </label>
            <label>Lien « en savoir plus »
                <input type="text" name="cookies_more_label" value="<?= Helpers::e($cookies['cookies_more_label']) ?>">
            </label>
        </div>

        <label>URL de la politique cookies
            <input type="text" name="cookies_policy_url" value="<?= Helpers::e($cookies['cookies_policy_url']) ?>" placeholder="/page/politique-cookies">
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="/page/politique-cookies" class="btn" target="_blank" rel="noopener">Voir la page cookies</a>
        </div>
    </form>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
