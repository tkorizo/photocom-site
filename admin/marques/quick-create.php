<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireCatalog();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Non autorisé']);
    exit;
}

$name = trim((string) ($_POST['name'] ?? ''));
if ($name === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Nom obligatoire']);
    exit;
}

try {
    Database::initialize();
    $id = BrandRepository::create($name);
    $brand = BrandRepository::find($id);
    echo json_encode([
        'ok' => true,
        'brand' => [
            'id' => (int) $brand['id'],
            'name' => $brand['name'],
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
