<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireLogin();

$pageTitle = 'Nouvelle catégorie';
$currentPage = 'categories';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        try {
            $data = CategoryForm::dataFromPost();
            if ($data['name'] === '') {
                $errors[] = 'Le nom est obligatoire.';
            }
            if (empty($errors)) {
                CategoryRepository::create($data);
                Helpers::flash('success', 'Catégorie créée.');
                Helpers::redirect('/admin/categories/index.php');
            }
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

require dirname(__DIR__) . '/includes/header.php';
require __DIR__ . '/_form.php';
require dirname(__DIR__) . '/includes/footer.php';
