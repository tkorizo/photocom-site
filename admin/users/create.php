<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();

$pageTitle = 'Nouvel utilisateur';
$currentPage = 'users';
$errors = [];
$data = ['name' => '', 'email' => '', 'role' => Auth::ROLE_CATALOG];
$isEdit = false;
$isSuperTarget = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'role' => UserRepository::normalizeRole((string) ($_POST['role'] ?? Auth::ROLE_CATALOG)),
        ];
        $password = $_POST['password'] ?? '';

        if ($data['name'] === '') {
            $errors[] = 'Le nom est obligatoire.';
        }
        if ($data['email'] === '') {
            $errors[] = 'L\'email est obligatoire.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }
        if ($data['role'] === Auth::ROLE_SUPER_ADMIN || !isset(UserRepository::assignableRoles()[$data['role']])) {
            $errors[] = 'Rôle non autorisé.';
        }

        if (empty($errors)) {
            try {
                $id = UserRepository::create($data['name'], $data['email'], $password, $data['role']);
                AuditLog::record('user.create', 'user', $id, [
                    'email' => $data['email'],
                    'role' => $data['role'],
                ]);
                Helpers::flash('success', 'Utilisateur créé.');
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
