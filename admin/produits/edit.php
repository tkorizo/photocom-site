<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireCatalog();

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
            $errors = array_merge($errors, ProductForm::validate($data));
            $slug = trim((string) ($data['slug'] ?? '')) !== ''
                ? Helpers::slugify((string) $data['slug'])
                : Helpers::slugify($data['name']);
            $data['slug'] = $slug;
            if ($slug !== '' && Helpers::slugExists('products', $slug, $id)) {
                $errors[] = 'Ce slug existe déjà (« ' . $slug . ' »). Modifiez le nom ou le slug.';
            }
            if ($data['brand'] !== '' && !BrandRepository::findByName($data['brand'])) {
                BrandRepository::create($data['brand']);
            }
            if (empty($errors)) {
                ProductRepository::update($id, $data);
                AuditLog::record('product.update', 'product', $id, ['name' => $data['name'] ?? '']);
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
