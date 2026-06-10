<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();

$pageTitle = 'Chat (n8n)';
$currentPage = 'chat';
$errors = [];
$settings = SettingRepository::chat();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        SettingRepository::setMany([
            'chat_enabled' => isset($_POST['chat_enabled']) ? '1' : '0',
            'chat_n8n_webhook' => trim($_POST['chat_n8n_webhook'] ?? ''),
            'chat_welcome_message' => trim($_POST['chat_welcome_message'] ?? ''),
            'chat_offline_message' => trim($_POST['chat_offline_message'] ?? ''),
        ]);
        Helpers::flash('success', 'Paramètres du chat enregistrés.');
        Helpers::redirect('/admin/chat/index.php');
    }
}

$settings = SettingRepository::chat();

require dirname(__DIR__) . '/includes/header.php';
?>

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success"><?= Helpers::e($message) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error"><?= Helpers::e($errors[0]) ?></div>
<?php endif; ?>

<div class="panel">
    <p class="text-muted">Le chat du site sera connecté à votre workflow <strong>n8n</strong> via un webhook. Configurez l'URL de votre workflow n8n qui recevra et traitera les messages des visiteurs.</p>

    <form method="post" class="form-grid">
        <?= Auth::csrfField() ?>

        <label class="checkbox-label">
            <input type="checkbox" name="chat_enabled" value="1" <?= $settings['chat_enabled'] === '1' ? 'checked' : '' ?>>
            Activer le chat sur le site
        </label>

        <label>URL Webhook n8n
            <input type="url" name="chat_n8n_webhook" value="<?= Helpers::e($settings['chat_n8n_webhook']) ?>" placeholder="https://votre-n8n.com/webhook/...">
        </label>

        <label>Message d'accueil
            <textarea name="chat_welcome_message" rows="3"><?= Helpers::e($settings['chat_welcome_message']) ?></textarea>
        </label>

        <label>Message hors ligne
            <textarea name="chat_offline_message" rows="2"><?= Helpers::e($settings['chat_offline_message']) ?></textarea>
        </label>

        <div class="help-box">
            <strong>Intégration n8n</strong>
            <p>Créez un workflow n8n avec un nœud <em>Webhook</em> en déclencheur. Collez l'URL de production ici. Le site enverra les messages au format JSON :</p>
            <pre>{
  "message": "Texte du visiteur",
  "session_id": "identifiant-session",
  "page_url": "URL de la page",
  "timestamp": "2026-06-10T12:00:00"
}</pre>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </form>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
