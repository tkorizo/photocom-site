<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireCatalog();

$isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

$respond = static function (bool $ok, string $message = '', string $redirect = '/admin/produits/index.php') use ($isAjax): void {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($message !== '') {
        Helpers::flash($ok ? 'success' : 'error', $message);
    }
    Helpers::redirect($redirect);
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
    $respond(false, 'Action non autorisée.');
}

$id = (int) ($_POST['id'] ?? 0);
$redirect = $_POST['redirect'] ?? '/admin/produits/index.php';

if ($id <= 0) {
    $respond(false, 'Produit invalide.', $redirect);
}

$data = [];

$floatFields = ['price', 'regular_price', 'sale_price'];
$stringFields = ['name', 'sku', 'brand', 'stock_status', 'catalog_visibility', 'admin_label'];
$boolToggleFields = [
    'is_out_of_stock',
    'is_coming_soon',
    'is_active',
    'featured',
    'on_sale',
    'manage_stock',
    'hide_add_to_cart',
];

foreach ($floatFields as $field) {
    if (array_key_exists($field, $_POST)) {
        $raw = trim((string) $_POST[$field]);
        $data[$field] = $raw === '' ? null : (float) $raw;
    }
}

foreach ($stringFields as $field) {
    if (array_key_exists($field, $_POST)) {
        $data[$field] = trim((string) $_POST[$field]);
    }
}

if (array_key_exists('stock_quantity', $_POST)) {
    $data['stock_quantity'] = $_POST['stock_quantity'] !== '' ? (int) $_POST['stock_quantity'] : null;
    $data['manage_stock'] = 1;
}

if (isset($_POST['field'], $_POST['value'])) {
    $field = (string) $_POST['field'];
    if (in_array($field, $boolToggleFields, true)) {
        $data[$field] = $_POST['value'] === '1';
    }
}

if (isset($_POST['category_id']) && !isset($_POST['category_ids'])) {
    $catId = (int) $_POST['category_id'];
    $data['category_ids'] = $catId > 0 ? [$catId] : [];
    $data['category_id'] = $catId > 0 ? $catId : null;
}

if (isset($_POST['category_ids']) && is_array($_POST['category_ids'])) {
    $ids = array_values(array_filter(array_map('intval', $_POST['category_ids'])));
    $data['category_ids'] = $ids;
    $data['category_id'] = $ids[0] ?? null;
}

if ($data !== []) {
    ProductRepository::quickUpdate($id, $data);
    AuditLog::record('product.quick_update', 'product', $id, ['fields' => array_keys($data)]);
    $respond(true, 'Produit mis à jour.', $redirect);
}

$respond(true, '', $redirect);
