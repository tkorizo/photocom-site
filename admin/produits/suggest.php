<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireCatalog();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$q = trim((string) ($_GET['q'] ?? ''));
$limit = (int) ($_GET['limit'] ?? 8);

try {
    Database::migrate();
    $items = ProductRepository::suggestAdmin($q, $limit);
    echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'items' => [],
        'error' => 'suggest_failed',
        'message' => (Helpers::config()['debug'] ?? false) ? $e->getMessage() : null,
    ], JSON_UNESCAPED_UNICODE);
}
