<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

Auth::startSession();

if (Auth::check()) {
    Helpers::redirect('/admin/index.php');
}

$config = Helpers::config();
$error = null;
$lockout = Auth::lockoutRemaining();
$homeSettings = SettingRepository::home();
$adminLogo = trim((string) ($homeSettings['site_logo_mark'] ?? ''));
if ($adminLogo === '') {
    $adminLogo = '/assets/images/logo-admin.webp';
}
$adminFavicon = trim((string) ($homeSettings['site_favicon'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($lockout > 0) {
        $error = 'Trop de tentatives. Réessayez dans ' . ceil($lockout / 60) . ' minute(s).';
    } elseif (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Session expirée. Veuillez réessayer.';
    } elseif (Captcha::isHoneypotFilled() || !Captcha::verify($_POST['captcha'] ?? null)) {
        $error = 'Vérification incorrecte. Réessayez.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (Auth::attempt($email, $password)) {
            Helpers::redirect('/admin/index.php');
        }

        $error = 'Identifiants incorrects.';
        $lockout = Auth::lockoutRemaining();
    }
}

Captcha::issue();
$captchaUrl = Captcha::imageUrl();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — <?= Helpers::e($config['name']) ?></title>
    <meta name="robots" content="noindex,nofollow">
    <?php if ($adminFavicon !== ''): ?>
        <link rel="icon" href="<?= Helpers::e($adminFavicon) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/admin.css?v=<?= is_file(dirname(__DIR__) . '/public/assets/css/admin.css') ? filemtime(dirname(__DIR__) . '/public/assets/css/admin.css') : time() ?>">
</head>
<body class="login-body">
    <div class="login-card">
        <div class="login-brand">
            <img src="<?= Helpers::e($adminLogo) ?>" alt="<?= Helpers::e($config['name']) ?>" class="brand-logo-img brand-logo-img--login">
            <h1><?= Helpers::e($config['name']) ?></h1>
            <p>Administration</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= Helpers::e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="login-form">
            <?= Auth::csrfField() ?>
            <label>
                Email
                <input type="email" name="email" required autofocus>
            </label>
            <label>
                Mot de passe
                <input type="password" name="password" required>
            </label>
            <div class="login-captcha" aria-label="Vérification anti-robot">
                <label class="login-honeypot" aria-hidden="true">
                    Site web
                    <input type="text" name="company_url" value="" tabindex="-1" autocomplete="off">
                </label>
                <div class="login-captcha-row">
                    <img src="<?= Helpers::e($captchaUrl) ?>" alt="Code de vérification" width="200" height="58" class="login-captcha-img">
                    <a href="/admin/login.php" class="login-captcha-reload">Autre code</a>
                </div>
                <label>
                    Recopiez le code
                    <input type="text" name="captcha" required autocomplete="off" autocapitalize="characters" spellcheck="false" inputmode="text" maxlength="8" <?= $lockout > 0 ? 'disabled' : '' ?>>
                </label>
            </div>
            <button type="submit" class="btn btn-primary" <?= $lockout > 0 ? 'disabled' : '' ?>>Se connecter</button>
        </form>
    </div>
</body>
</html>
