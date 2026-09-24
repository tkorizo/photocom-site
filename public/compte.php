<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/includes/init.php';

Database::migrate();
CustomerAuth::startSession();

if (CustomerAuth::check()) {
    Helpers::redirect('/mon-compte');
}

$tabParam = (string) ($_GET['tab'] ?? 'register');
$tab = in_array($tabParam, ['login', 'register', 'verify'], true) ? $tabParam : 'register';
$errors = [];
$info = Helpers::flash('success');
$old = [
    'name' => '',
    'email' => '',
    'phone' => '',
];

$pending = $_SESSION['customer_register_pending'] ?? null;
if ($tab === 'verify' && (!is_array($pending) || empty($pending['email']))) {
    $tab = 'register';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée. Veuillez réessayer.';
    } else {
        $action = (string) ($_POST['action'] ?? 'register');
        $old['name'] = trim((string) ($_POST['name'] ?? ''));
        $old['email'] = trim((string) ($_POST['email'] ?? ''));
        $old['phone'] = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

        try {
            if ($action === 'login') {
                $tab = 'login';
                if ($old['email'] === '' || $password === '') {
                    throw new RuntimeException('E-mail et mot de passe requis.');
                }
                $customer = CustomerRepository::findByEmail($old['email']);
                if (
                    $customer
                    && (int) ($customer['is_active'] ?? 0) === 1
                    && !CustomerRepository::isEmailVerified($customer)
                    && password_verify($password, (string) $customer['password_hash'])
                ) {
                    $issued = OtpService::issue(
                        OtpService::PURPOSE_CUSTOMER_REGISTER,
                        (string) $customer['email'],
                        (int) $customer['id'],
                        'Vérifiez votre compte PHOTOCOM',
                        'Bonjour ' . trim((string) $customer['name']) . ', voici le code pour activer votre compte PHOTOCOM :'
                    );
                    if (!$issued['ok']) {
                        throw new RuntimeException($issued['message']);
                    }
                    $_SESSION['customer_register_pending'] = [
                        'id' => (int) $customer['id'],
                        'email' => (string) $customer['email'],
                        'name' => (string) $customer['name'],
                    ];
                    Helpers::flash('success', $issued['message']);
                    Helpers::redirect('/compte?tab=verify');
                }
                if (!CustomerAuth::attempt($old['email'], $password)) {
                    throw new RuntimeException('Identifiants incorrects ou compte désactivé.');
                }
                Helpers::flash('success', 'Connexion réussie. Bienvenue !');
                Helpers::redirect('/mon-compte');
            }

            if ($action === 'verify_otp') {
                $tab = 'verify';
                $pending = $_SESSION['customer_register_pending'] ?? null;
                if (!is_array($pending) || empty($pending['email']) || empty($pending['id'])) {
                    throw new RuntimeException('Session de vérification expirée. Recréez un compte ou reconnectez-vous.');
                }
                $result = OtpService::verify(
                    OtpService::PURPOSE_CUSTOMER_REGISTER,
                    (string) $pending['email'],
                    (string) ($_POST['otp_code'] ?? '')
                );
                if (!$result['ok']) {
                    throw new RuntimeException($result['message']);
                }
                CustomerRepository::markEmailVerified((int) $pending['id']);
                unset($_SESSION['customer_register_pending']);
                if (!CustomerAuth::loginById((int) $pending['id'])) {
                    throw new RuntimeException('Compte activé. Connectez-vous avec vos identifiants.');
                }
                Helpers::flash('success', 'Compte vérifié. Bienvenue !');
                Helpers::redirect('/mon-compte');
            }

            if ($action === 'resend_otp') {
                $tab = 'verify';
                $pending = $_SESSION['customer_register_pending'] ?? null;
                if (!is_array($pending) || empty($pending['email'])) {
                    throw new RuntimeException('Session de vérification expirée.');
                }
                if (!OtpService::canResend(OtpService::PURPOSE_CUSTOMER_REGISTER, (string) $pending['email'])) {
                    throw new RuntimeException('Patientez une minute avant de renvoyer un code.');
                }
                $issued = OtpService::issue(
                    OtpService::PURPOSE_CUSTOMER_REGISTER,
                    (string) $pending['email'],
                    (int) ($pending['id'] ?? 0),
                    'Vérifiez votre compte PHOTOCOM',
                    'Bonjour, voici votre nouveau code pour activer votre compte PHOTOCOM :'
                );
                if (!$issued['ok']) {
                    throw new RuntimeException($issued['message']);
                }
                $info = $issued['message'];
            }

            if ($action === 'register') {
                $tab = 'register';
                if ($password !== $passwordConfirm) {
                    throw new RuntimeException('Les mots de passe ne correspondent pas.');
                }
                $id = CustomerRepository::create($old['name'], $old['email'], $password, $old['phone']);
                $issued = OtpService::issue(
                    OtpService::PURPOSE_CUSTOMER_REGISTER,
                    $old['email'],
                    $id,
                    'Vérifiez votre compte PHOTOCOM',
                    'Bonjour ' . $old['name'] . ', bienvenue chez PHOTOCOM. Voici votre code d’activation :'
                );
                if (!$issued['ok']) {
                    CustomerRepository::delete($id);
                    throw new RuntimeException($issued['message']);
                }
                $_SESSION['customer_register_pending'] = [
                    'id' => $id,
                    'email' => strtolower($old['email']),
                    'name' => $old['name'],
                ];
                Helpers::flash('success', $issued['message']);
                Helpers::redirect('/compte?tab=verify');
            }
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = match ($tab) {
    'login' => 'Connexion',
    'verify' => 'Vérification e-mail',
    default => 'Créer un compte',
};
$pendingEmail = is_array($pending) ? (string) ($pending['email'] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $seoTitle = $pageTitle;
    $seoDescription = 'Créez votre compte client PHOTOCOM pour suivre vos demandes et vos préférences.';
    $seoRobots = 'noindex,follow';
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
                        <?php if ($tab === 'login'): ?>
                            Accédez à votre espace client PHOTOCOM.
                        <?php elseif ($tab === 'verify'): ?>
                            Saisissez le code reçu par e-mail pour activer votre compte.
                        <?php else: ?>
                            Inscrivez-vous pour centraliser vos demandes et vos informations.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </section>

        <section class="editorial-section section-light">
            <div class="container contact-layout">
                <div class="contact-main">
                    <header class="section-head">
                        <span class="section-label">Compte</span>
                        <h2><?= Helpers::e($pageTitle) ?></h2>
                    </header>

                    <div class="contact-panel contact-panel-accent">
                        <?php if ($tab !== 'verify'): ?>
                            <nav class="account-switch" aria-label="Choisir une action">
                                <a
                                    href="/compte?tab=register"
                                    class="account-switch-link<?= $tab === 'register' ? ' is-active' : '' ?>"
                                >Créer un compte</a>
                                <a
                                    href="/compte?tab=login"
                                    class="account-switch-link<?= $tab === 'login' ? ' is-active' : '' ?>"
                                >Se connecter</a>
                            </nav>
                        <?php endif; ?>

                        <?php if ($info): ?>
                            <div class="account-alert" role="status">
                                <p><?= Helpers::e($info) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($errors !== []): ?>
                            <div class="account-alert account-alert-error" role="alert">
                                <?php foreach ($errors as $error): ?>
                                    <p><?= Helpers::e($error) ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($tab === 'verify'): ?>
                            <p class="section-sub">Code envoyé à <strong><?= Helpers::e($pendingEmail) ?></strong></p>
                            <form method="post" class="account-form contact-wa-form" novalidate>
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="action" value="verify_otp">
                                <div class="account-field">
                                    <label for="otp-code">Code à 6 chiffres</label>
                                    <input type="text" id="otp-code" name="otp_code" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" autofocus>
                                </div>
                                <div class="contact-wa-actions">
                                    <button type="submit" class="btn btn-primary">Activer mon compte</button>
                                </div>
                            </form>
                            <form method="post" style="margin-top:1rem">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="action" value="resend_otp">
                                <button type="submit" class="btn btn-outline">Renvoyer le code</button>
                            </form>
                        <?php elseif ($tab === 'register'): ?>
                            <form method="post" class="account-form contact-wa-form" novalidate>
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="action" value="register">

                                <div class="account-field">
                                    <label for="account-name">Nom complet</label>
                                    <input type="text" id="account-name" name="name" value="<?= Helpers::e($old['name']) ?>" required minlength="2" autocomplete="name">
                                </div>

                                <div class="account-field">
                                    <label for="account-email">E-mail</label>
                                    <input type="email" id="account-email" name="email" value="<?= Helpers::e($old['email']) ?>" required autocomplete="email">
                                </div>

                                <div class="account-field">
                                    <label for="account-phone">Téléphone <span class="account-optional">(optionnel)</span></label>
                                    <input type="tel" id="account-phone" name="phone" value="<?= Helpers::e($old['phone']) ?>" autocomplete="tel">
                                </div>

                                <div class="account-field-row">
                                    <div class="account-field">
                                        <label for="account-password">Mot de passe</label>
                                        <input type="password" id="account-password" name="password" required minlength="8" autocomplete="new-password">
                                    </div>
                                    <div class="account-field">
                                        <label for="account-password-confirm">Confirmation</label>
                                        <input type="password" id="account-password-confirm" name="password_confirm" required minlength="8" autocomplete="new-password">
                                    </div>
                                </div>

                                <div class="contact-wa-actions">
                                    <button type="submit" class="btn btn-primary">Créer mon compte</button>
                                    <a href="/catalogue.php" class="btn btn-outline">Voir le catalogue</a>
                                </div>
                            </form>
                        <?php else: ?>
                            <form method="post" class="account-form contact-wa-form" novalidate>
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="action" value="login">

                                <div class="account-field">
                                    <label for="login-email">E-mail</label>
                                    <input type="email" id="login-email" name="email" value="<?= Helpers::e($old['email']) ?>" required autocomplete="email">
                                </div>

                                <div class="account-field">
                                    <label for="login-password">Mot de passe</label>
                                    <input type="password" id="login-password" name="password" required autocomplete="current-password">
                                </div>

                                <p class="account-optional" style="margin:0 0 1rem">
                                    <a href="/compte/mot-de-passe">Mot de passe oublié ?</a>
                                </p>

                                <div class="contact-wa-actions">
                                    <button type="submit" class="btn btn-primary">Se connecter</button>
                                    <a href="/compte?tab=register" class="btn btn-outline">Créer un compte</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <aside class="contact-aside">
                    <div class="contact-panel">
                        <h3>Pourquoi un compte ?</h3>
                        <p>Centralisez vos coordonnées et simplifiez vos échanges avec l’équipe PHOTOCOM.</p>
                        <ul class="contact-info-list">
                            <li>
                                <strong>Suivi</strong>
                                <span>Retrouvez plus facilement vos demandes et vos préférences.</span>
                            </li>
                            <li>
                                <strong>Gain de temps</strong>
                                <span>Moins de ressaisie lors de vos prochaines prises de contact.</span>
                            </li>
                            <li>
                                <strong>Conseil</strong>
                                <span>Un interlocuteur mieux informé pour vous orienter en photo &amp; vidéo.</span>
                            </li>
                        </ul>
                    </div>
                    <div class="contact-panel">
                        <h3>Besoin d’aide ?</h3>
                        <p>Notre équipe reste disponible pour toute question sur un produit ou une commande.</p>
                        <div class="contact-wa-actions">
                            <a href="/contact" class="btn btn-outline">Nous contacter</a>
                        </div>
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
