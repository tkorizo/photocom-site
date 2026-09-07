<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

CustomerAuth::startSession();
Database::migrate();

if (!CustomerAuth::check()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'auth_required'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = CustomerAuth::user();
$customerId = (int) ($user['id'] ?? 0);
if ($customerId < 1) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'auth_required'], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

try {
    if ($method === 'GET') {
        $items = CustomerCartRepository::getItems($customerId);
        echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'invalid_json'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $csrf = (string) ($payload['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if (!Auth::verifyCsrf($csrf !== '' ? $csrf : null)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'csrf'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $local = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $mode = (string) ($payload['mode'] ?? 'merge');

        if ($mode === 'replace') {
            $merged = array_values(array_filter($local, [CustomerCartRepository::class, 'isValidItem']));
        } else {
            $remote = CustomerCartRepository::getItems($customerId);
            $merged = CustomerCartRepository::merge($local, $remote);
        }

        CustomerCartRepository::saveItems($customerId, $merged);
        echo json_encode(['ok' => true, 'items' => $merged], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'cart_sync_failed'], JSON_UNESCAPED_UNICODE);
}
