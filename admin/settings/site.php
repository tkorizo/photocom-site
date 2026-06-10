<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();

$pageTitle = 'Informations du site';
$currentPage = 'settings';
$errors = [];
$settings = SettingRepository::site();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        SettingRepository::setMany([
            'site_name' => trim($_POST['site_name'] ?? ''),
            'site_url' => trim($_POST['site_url'] ?? ''),
            'site_tagline' => trim($_POST['site_tagline'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'whatsapp' => trim($_POST['whatsapp'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'hours' => trim($_POST['hours'] ?? ''),
            'founded' => trim($_POST['founded'] ?? ''),
        ]);
        Helpers::flash('success', 'Informations du site enregistrées.');
        Helpers::redirect('/admin/settings/site.php');
    }
}

$settings = SettingRepository::site();

require dirname(__DIR__) . '/includes/header.php';
?>

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success"><?= Helpers::e($message) ?></div>
<?php endif; ?>

<div class="panel">
    <p class="text-muted">Ces informations sont affichées sur le site public (contact, pied de page, etc.).</p>

    <form method="post" class="form-grid">
        <?= Auth::csrfField() ?>

        <h3 class="form-section">Identité</h3>
        <div class="form-row">
            <label>Nom du site
                <input type="text" name="site_name" value="<?= Helpers::e($settings['site_name']) ?>" required>
            </label>
            <label>URL du site
                <input type="url" name="site_url" value="<?= Helpers::e($settings['site_url']) ?>">
            </label>
        </div>
        <label>Slogan / description courte
            <input type="text" name="site_tagline" value="<?= Helpers::e($settings['site_tagline']) ?>">
        </label>

        <h3 class="form-section">Contact</h3>
        <div class="form-row">
            <label>Téléphone
                <input type="text" name="phone" value="<?= Helpers::e($settings['phone']) ?>">
            </label>
            <label>Email
                <input type="email" name="email" value="<?= Helpers::e($settings['email']) ?>">
            </label>
            <label>WhatsApp (numéro international)
                <input type="text" name="whatsapp" value="<?= Helpers::e($settings['whatsapp']) ?>">
            </label>
        </div>
        <label>Adresse
            <input type="text" name="address" value="<?= Helpers::e($settings['address']) ?>">
        </label>
        <div class="form-row">
            <label>Horaires
                <input type="text" name="hours" value="<?= Helpers::e($settings['hours']) ?>">
            </label>
            <label>Année de création
                <input type="text" name="founded" value="<?= Helpers::e($settings['founded']) ?>">
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </form>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
