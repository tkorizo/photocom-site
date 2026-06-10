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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($lockout > 0) {
        $error = 'Trop de tentatives. Réessayez dans ' . ceil($lockout / 60) . ' minute(s).';
    } elseif (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Session expirée. Veuillez réessayer.';
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — <?= Helpers::e($config['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="login-body">
    <div class="login-card">
        <div class="login-brand">
            <span class="brand-logo">PC</span>
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
            <button type="submit" class="btn btn-primary" <?= $lockout > 0 ? 'disabled' : '' ?>>Se connecter</button>
        </form>
    </div>
</body>
</html>
