<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

Auth::startSession();
$payload = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Requête invalide']);
    exit;
}

$sessionId = ChatConversationRepository::normalizeSessionId((string) ($payload['session_id'] ?? ''));
if (ChatGuard::isBlocked($sessionId) || ChatGuard::honeypotFilled($payload)) {
    if (ChatGuard::honeypotFilled($payload)) {
        ChatGuard::block($sessionId);
    }
    http_response_code(423);
    echo json_encode(['blocked' => true, 'session_id' => $sessionId, 'reply' => '']);
    exit;
}

$csrf = (string) ($payload['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
if (!Auth::verifyCsrf($csrf !== '' ? $csrf : null)) {
    http_response_code(403);
    echo json_encode(['error' => 'Session expirée. Rechargez la page.']);
    exit;
}

$now = time();
$window = 60;
$maxHits = 8;
$hits = $_SESSION['chat_hits'] ?? ['start' => $now, 'count' => 0];
if (($now - (int) $hits['start']) > $window) {
    $hits = ['start' => $now, 'count' => 0];
}
$hits['count'] = (int) $hits['count'] + 1;
$_SESSION['chat_hits'] = $hits;
if ($hits['count'] > $maxHits || !RateLimiter::allow('chat', 8, 60)) {
    http_response_code(429);
    echo json_encode(['error' => 'Trop de requêtes. Réessayez dans une minute.']);
    exit;
}

$message = trim((string) ($payload['message'] ?? ''));
$message = mb_substr(strip_tags($message), 0, 2000);
if ($message === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Message vide']);
    exit;
}

$pageUrl = trim((string) ($payload['page_url'] ?? ''));
if ($pageUrl !== '' && !str_starts_with($pageUrl, '/')) {
    $path = parse_url($pageUrl, PHP_URL_PATH);
    $pageUrl = is_string($path) && str_starts_with($path, '/') ? $path : '';
}
$pageUrl = mb_substr($pageUrl, 0, 500);

$clientHints = is_array($payload['visitor'] ?? null) ? $payload['visitor'] : [];
$result = ChatAgent::reply($message, $sessionId, $pageUrl, $clientHints);

echo json_encode([
    'reply' => $result['reply'] ?? '',
    'session_id' => $result['session_id'] ?? $sessionId,
    'blocked' => !empty($result['blocked']),
], JSON_UNESCAPED_UNICODE);
