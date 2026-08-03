<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireCatalog();

$isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

$respond = static function (bool $ok, string $message = '', string $redirect = '/admin/categories/index.php') use ($isAjax): void {
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
$redirect = $_POST['redirect'] ?? '/admin/categories/index.php';

if ($id <= 0) {
    $respond(false, 'Catégorie invalide.', $redirect);
}

$data = [];

if (array_key_exists('parent_id', $_POST)) {
    $raw = trim((string) $_POST['parent_id']);
    $data['parent_id'] = $raw === '' ? null : (int) $raw;
}

if (array_key_exists('name', $_POST)) {
    $data['name'] = trim((string) $_POST['name']);
}

if ($data === []) {
    $respond(true, '', $redirect);
}

try {
    CategoryRepository::quickUpdate($id, $data);
    AuditLog::record('category.update', 'category', $id, ['fields' => array_keys($data)]);
    $respond(true, 'Catégorie mise à jour.', $redirect);
} catch (Throwable $e) {
    $respond(false, $e->getMessage(), $redirect);
}
