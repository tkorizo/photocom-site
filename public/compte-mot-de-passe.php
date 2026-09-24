<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/includes/init.php';

Database::migrate();
CustomerAuth::startSession();

if (CustomerAuth::check()) {
    Helpers::redirect('/mon-compte');
}

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$mode = $token !== '' ? 'reset' : 'request';
$errors = [];
$success = Helpers::flash('success');
$emailOld = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée. Veuillez réessayer.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'request') {
            $mode = 'request';
            $emailOld = trim((string) ($_POST['email'] ?? ''));
            $result = PasswordReset::request($emailOld);
            if ($result['ok']) {
                Helpers::flash('success', $result['message']);
                Helpers::redirect('/compte/mot-de-passe');
            }
            $errors[] = $result['message'];
        } elseif ($action === 'reset') {
            $mode = 'reset';
            $token = trim((string) ($_POST['token'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
            $result = PasswordReset::reset($token, $password, $passwordConfirm);
            if ($result['ok']) {
                Helpers::flash('success', $result['message']);
                Helpers::redirect('/compte?tab=login');
            }
            $errors[] = $result['message'];
            if (PasswordReset::findValidCustomerId($token) === null) {
                $mode = 'request';
                $token = '';
            }
        }
    }
}

if ($mode === 'reset' && PasswordReset::findValidCustomerId($token) === null) {
    $errors[] = 'Ce lien est invalide ou a expiré. Demandez une nouvelle réinitialisation.';
    $mode = 'request';
    $token = '';
}

$pageTitle = $mode === 'reset' ? 'Nouveau mot de passe' : 'Mot de passe oublié';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $seoTitle = $pageTitle;
    $seoDescription = 'Réinitialisez le mot de passe de votre compte client PHOTOCOM.';
    $seoRobots = 'noindex,nofollow';
    require __DIR__ . '/includes/site-seo.php';
    ?>
    <?php require __DIR__ . '/includes/site-favicon.php'; ?>
    <?php require __DIR__ . '/includes/site-assets.php'; ?>
</head>
<body class="page-account page-editorial page-contact">
    <?php require __DIR__ . '/includes/site-header.php'; ?>

    <main>
        <section class="editorial-hero">
            <div class="container">
                <div class="editorial-hero-copy">
                    <p class="section-label">Espace client</p>
                    <h1><?= Helpers::e($pageTitle) ?></h1>
                    <p class="editorial-lead">
                        <?= $mode === 'reset'
                            ? 'Choisissez un nouveau mot de passe pour votre compte.'
                            : 'Indiquez l’e-mail de votre compte : nous vous enverrons un lien de réinitialisation.' ?>
                    </p>
                </div>
            </div>
        </section>

        <section class="editorial-section section-light">
            <div class="container contact-layout">
                <div class="contact-main">
                    <div class="contact-panel contact-panel-accent">
                        <?php if ($success): ?>
                            <div class="account-alert" role="status">
                                <p><?= Helpers::e($success) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($errors !== []): ?>
                            <div class="account-alert account-alert-error" role="alert">
                                <?php foreach ($errors as $error): ?>
                                    <p><?= Helpers::e($error) ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($mode === 'reset'): ?>
                            <form method="post" class="account-form contact-wa-form" novalidate>
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="action" value="reset">
                                <input type="hidden" name="token" value="<?= Helpers::e($token) ?>">

                                <div class="account-field">
                                    <label for="reset-password">Nouveau mot de passe</label>
                                    <input type="password" id="reset-password" name="password" required minlength="8" autocomplete="new-password">
                                </div>
                                <div class="account-field">
                                    <label for="reset-password-confirm">Confirmation</label>
                                    <input type="password" id="reset-password-confirm" name="password_confirm" required minlength="8" autocomplete="new-password">
                                </div>

                                <div class="contact-wa-actions">
                                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                                    <a href="/compte?tab=login" class="btn btn-outline">Retour connexion</a>
                                </div>
                            </form>
                        <?php else: ?>
                            <form method="post" class="account-form contact-wa-form" novalidate>
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="action" value="request">

                                <div class="account-field">
                                    <label for="reset-email">E-mail du compte</label>
                                    <input type="email" id="reset-email" name="email" value="<?= Helpers::e($emailOld) ?>" required autocomplete="email">
                                </div>

                                <div class="contact-wa-actions">
                                    <button type="submit" class="btn btn-primary">Envoyer le lien</button>
                                    <a href="/compte?tab=login" class="btn btn-outline">Retour connexion</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <aside class="contact-aside">
                    <div class="contact-panel">
                        <h3>Sécurité</h3>
                        <p>Le lien est valable 1 heure et ne peut être utilisé qu’une seule fois.</p>
                    </div>
                </aside>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/includes/site-footer.php'; ?>
    <?php require __DIR__ . '/includes/site-chat.php'; ?>
    <?php require __DIR__ . '/includes/site-cookies.php'; ?>
    <?php require __DIR__ . '/includes/site-end-scripts.php'; ?>
</body>
</html>
