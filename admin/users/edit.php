<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
$user = UserRepository::find($id);

if (!$user) {
    Helpers::redirect('/admin/users/index.php');
}

$pageTitle = 'Modifier utilisateur';
$currentPage = 'users';
$errors = [];
$data = [
    'name' => $user['name'],
    'email' => $user['email'],
    'role' => $user['role'],
];
$isEdit = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'role' => $_POST['role'] ?? 'editor',
        ];
        $password = $_POST['password'] ?? '';

        if ($data['name'] === '') {
            $errors[] = 'Le nom est obligatoire.';
        }
        if ($data['email'] === '') {
            $errors[] = 'L\'email est obligatoire.';
        }
        if ($user['role'] === 'admin' && $data['role'] !== 'admin' && UserRepository::countAdmins() <= 1) {
            $errors[] = 'Impossible de retirer le rôle du dernier administrateur.';
        }

        if (empty($errors)) {
            try {
                UserRepository::update($id, $data['name'], $data['email'], $data['role'], $password ?: null);
                if ($id === (int) Auth::user()['id']) {
                    Auth::startSession();
                    $_SESSION['photocom_admin']['name'] = $data['name'];
                    $_SESSION['photocom_admin']['email'] = strtolower($data['email']);
                    $_SESSION['photocom_admin']['role'] = $data['role'];
                }
                Helpers::flash('success', 'Utilisateur mis à jour.');
                Helpers::redirect('/admin/users/index.php');
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}

require dirname(__DIR__) . '/includes/header.php';
require __DIR__ . '/_form.php';
require dirname(__DIR__) . '/includes/footer.php';
