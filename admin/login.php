<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

Auth::startSession();

if (Auth::check()) {
    Helpers::redirect('/admin/index.php');
}

$config = Helpers::config();
$error = null;
$info = null;
$lockout = Auth::lockoutRemaining();
$homeSettings = SettingRepository::home();
$adminLogo = trim((string) ($homeSettings['site_logo_mark'] ?? ''));
if ($adminLogo === '') {
    $adminLogo = '/assets/images/logo-admin.webp';
}
$adminFavicon = trim((string) ($homeSettings['site_favicon'] ?? ''));

$pending = $_SESSION['admin_otp_pending'] ?? null;
$step = is_array($pending) && !empty($pending['email']) ? 'otp' : 'credentials';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'login');

    if ($lockout > 0 && $action !== 'cancel_otp') {
        $error = 'Trop de tentatives. Réessayez dans ' . ceil($lockout / 60) . ' minute(s).';
    } elseif (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Session expirée. Veuillez réessayer.';
    } elseif ($action === 'cancel_otp') {
        unset($_SESSION['admin_otp_pending']);
        $step = 'credentials';
        $pending = null;
    } elseif ($action === 'resend_otp') {
        $pending = $_SESSION['admin_otp_pending'] ?? null;
        if (!is_array($pending) || empty($pending['email'])) {
            $error = 'Session OTP expirée. Reconnectez-vous.';
            $step = 'credentials';
        } elseif (!OtpService::canResend(OtpService::PURPOSE_ADMIN_LOGIN, (string) $pending['email'])) {
            $error = 'Patientez une minute avant de renvoyer un code.';
            $step = 'otp';
        } else {
            $issued = OtpService::issue(
                OtpService::PURPOSE_ADMIN_LOGIN,
                (string) $pending['email'],
                (int) ($pending['id'] ?? 0),
                'Code de connexion PHOTOCOM Admin',
                'Voici votre code de vérification pour accéder à l’administration PHOTOCOM :'
            );
            if ($issued['ok']) {
                $info = $issued['message'];
                $_SESSION['admin_otp_pending']['sent_at'] = time();
            } else {
                $error = $issued['message'];
            }
            $step = 'otp';
        }
    } elseif ($action === 'verify_otp') {
        $pending = $_SESSION['admin_otp_pending'] ?? null;
        if (!is_array($pending) || empty($pending['email']) || empty($pending['id'])) {
            $error = 'Session OTP expirée. Reconnectez-vous.';
            unset($_SESSION['admin_otp_pending']);
            $step = 'credentials';
        } else {
            $code = (string) ($_POST['otp_code'] ?? '');
            $result = OtpService::verify(OtpService::PURPOSE_ADMIN_LOGIN, (string) $pending['email'], $code);
            if ($result['ok']) {
                $user = UserRepository::find((int) $pending['id']);
                if (!$user) {
                    $error = 'Compte introuvable.';
                    unset($_SESSION['admin_otp_pending']);
                    $step = 'credentials';
                } else {
                    Auth::completeLogin($user);
                    Helpers::redirect('/admin/index.php');
                }
            } else {
                $error = $result['message'];
                $step = 'otp';
            }
        }
    } elseif (Captcha::isHoneypotFilled() || !Captcha::verify($_POST['captcha'] ?? null)) {
        $error = 'Vérification incorrecte. Réessayez.';
        $step = 'credentials';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $user = Auth::validateCredentials($email, $password);
        if (!$user) {
            $error = 'Identifiants incorrects.';
            $lockout = Auth::lockoutRemaining();
            $step = 'credentials';
        } else {
            $issued = OtpService::issue(
                OtpService::PURPOSE_ADMIN_LOGIN,
                (string) $user['email'],
                (int) $user['id'],
                'Code de connexion PHOTOCOM Admin',
                'Voici votre code de vérification pour accéder à l’administration PHOTOCOM :'
            );
            if (!$issued['ok']) {
                $error = $issued['message'];
                $step = 'credentials';
            } else {
                $_SESSION['admin_otp_pending'] = [
                    'id' => (int) $user['id'],
                    'email' => (string) $user['email'],
                    'name' => (string) $user['name'],
                    'sent_at' => time(),
                ];
                $pending = $_SESSION['admin_otp_pending'];
                $info = $issued['message'];
                $step = 'otp';
            }
        }
    }
}

if ($step === 'credentials') {
    Captcha::issue();
}
$captchaUrl = Captcha::imageUrl();
$pendingEmail = is_array($pending) ? (string) ($pending['email'] ?? '') : '';
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
        <?php if ($info): ?>
            <div class="alert"><?= Helpers::e($info) ?></div>
        <?php endif; ?>

        <?php if ($step === 'otp'): ?>
            <form method="post" class="login-form">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="action" value="verify_otp">
                <p style="margin:0 0 1rem;font-size:.95rem;opacity:.85">
                    Code envoyé à <strong><?= Helpers::e($pendingEmail) ?></strong>
                </p>
                <label>
                    Code à 6 chiffres
                    <input type="text" name="otp_code" required autofocus inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code">
                </label>
                <button type="submit" class="btn btn-primary">Valider le code</button>
            </form>
            <form method="post" style="margin-top:.75rem">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="action" value="resend_otp">
                <button type="submit" class="btn btn-outline" style="width:100%">Renvoyer le code</button>
            </form>
            <form method="post" style="margin-top:.5rem">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="action" value="cancel_otp">
                <button type="submit" class="btn btn-outline" style="width:100%">Retour</button>
            </form>
        <?php else: ?>
            <form method="post" class="login-form">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="action" value="login">
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
        <?php endif; ?>
    </div>
</body>
</html>
