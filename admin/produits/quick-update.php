<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
    Helpers::flash('success', 'Action non autorisée.');
    Helpers::redirect('/admin/produits/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$redirect = $_POST['redirect'] ?? '/admin/produits/index.php';

if ($id > 0) {
    $data = [];

    if (array_key_exists('stock_quantity', $_POST)) {
        $data['stock_quantity'] = $_POST['stock_quantity'] !== '' ? (int) $_POST['stock_quantity'] : null;
        $data['manage_stock'] = 1;
    }

    if (isset($_POST['field'], $_POST['value'])) {
        $field = $_POST['field'];
        if (in_array($field, ['is_out_of_stock', 'is_coming_soon', 'is_active'], true)) {
            $data[$field] = $_POST['value'] === '1';
        }
    }

    if (!empty($data)) {
        ProductRepository::quickUpdate($id, $data);
        Helpers::flash('success', 'Produit mis à jour.');
    }
}

Helpers::redirect($redirect);
