<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$category = CategoryRepository::find($id);
if (!$category) {
    Helpers::redirect('/admin/categories/index.php');
}

$pageTitle = 'Modifier la catégorie';
$currentPage = 'categories';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        try {
            $data = CategoryForm::dataFromPost($category);
            if ($data['name'] === '') {
                $errors[] = 'Le nom est obligatoire.';
            }
            if (empty($errors)) {
                CategoryRepository::update($id, $data);
                Helpers::flash('success', 'Catégorie mise à jour.');
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
