<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireLogin();

$pageTitle = 'Nouveau produit';
$currentPage = 'products';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        try {
            $data = ProductForm::dataFromPost();
            if ($data['name'] === '') {
                $errors[] = 'Le nom est obligatoire.';
            }
            if (empty($errors)) {
                ProductRepository::create($data);
                Helpers::flash('success', 'Produit créé.');
                Helpers::redirect('/admin/produits/index.php');
            }
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

require dirname(__DIR__) . '/includes/header.php';
require __DIR__ . '/_form.php';
require dirname(__DIR__) . '/includes/footer.php';
