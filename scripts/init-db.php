<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

echo "Initialisation de la base de données PHOTOCOM...\n";

Database::initialize();
echo "✓ Schéma créé.\n";

$uploadsPath = Helpers::config()['uploads_path'];
if (!is_dir($uploadsPath)) {
    mkdir($uploadsPath, 0755, true);
    echo "✓ Dossier uploads créé.\n";
}

$stmt = Database::getInstance()->query('SELECT COUNT(*) FROM users');
$userCount = (int) $stmt->fetchColumn();

if ($userCount === 0) {
    $email = env('ADMIN_EMAIL', 'admin@photocom.ma');
    $password = env('ADMIN_PASSWORD', 'Photocom2026!');
    $name = env('ADMIN_NAME', 'Administrateur');

    Auth::createAdmin($email, $password, $name);

    echo "✓ Compte admin créé.\n";
    echo "  Email    : {$email}\n";
    echo "  Password : {$password}\n";
    echo "  ⚠️  Changez ce mot de passe après la première connexion !\n";
} else {
    echo "✓ Compte admin déjà existant.\n";
}

echo "\nBase prête. Lancez le serveur avec :\n";
echo "  php -S localhost:8000 router.php\n\n";
echo "  Site   : http://localhost:8000\n";
echo "  Admin  : http://localhost:8000/admin/login.php\n";
