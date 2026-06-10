<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$product = ProductRepository::find($id);
if (!$product) {
    Helpers::redirect('/admin/produits/index.php');
}

$pageTitle = 'Modifier le produit';
$currentPage = 'products';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        try {
            $data = ProductForm::dataFromPost($product);
            if ($data['name'] === '') {
                $errors[] = 'Le nom est obligatoire.';
            }
            if (empty($errors)) {
                ProductRepository::update($id, $data);
                Helpers::flash('success', 'Produit mis à jour.');
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
