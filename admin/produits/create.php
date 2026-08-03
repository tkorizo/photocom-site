<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireCatalog();

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
            $slug = trim((string) ($data['slug'] ?? '')) !== ''
                ? Helpers::slugify((string) $data['slug'])
                : Helpers::slugify($data['name']);
            $data['slug'] = $slug;
            if ($slug !== '' && Helpers::slugExists('products', $slug)) {
                $errors[] = 'Ce slug existe déjà (« ' . $slug . ' »). Modifiez le nom ou le slug.';
            }
            if ($data['brand'] !== '' && !BrandRepository::findByName($data['brand'])) {
                BrandRepository::create($data['brand']);
            }
            if (empty($errors)) {
                $newId = ProductRepository::create($data);
                AuditLog::record('product.create', 'product', $newId, ['name' => $data['name'] ?? '']);
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
