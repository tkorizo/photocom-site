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

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

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

$user = CustomerAuth::user();
$customerId = (int) ($user['id'] ?? 0);
$items = is_array($payload['items'] ?? null) ? $payload['items'] : [];

try {
    if (!RateLimiter::allow('order', 8, 120)) {
        http_response_code(429);
        echo json_encode(['ok' => false, 'error' => 'rate_limited', 'message' => 'Trop de commandes. Réessayez dans un instant.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $order = OrderRepository::createFromCart($customerId, $items);
    CustomerCartRepository::saveItems($customerId, []);

    $site = SettingRepository::site();
    $config = Helpers::config();
    $whatsapp = (string) ($site['whatsapp'] ?? $config['whatsapp'] ?? '');

    echo json_encode([
        'ok' => true,
        'reference' => $order['reference'],
        'total' => $order['total'],
        'whatsapp_url' => OrderRepository::whatsappUrl($order, $whatsapp),
        'account_url' => '/mon-compte/commande/' . rawurlencode((string) $order['reference']),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (RuntimeException $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'order_failed', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'order_failed', 'message' => 'Impossible d’enregistrer la commande.'], JSON_UNESCAPED_UNICODE);
}
