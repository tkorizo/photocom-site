<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Anti-abus simple (session)
Auth::startSession();
$now = time();
$window = 60;
$maxHits = 20;
$hits = $_SESSION['chat_hits'] ?? ['start' => $now, 'count' => 0];
if (($now - (int) $hits['start']) > $window) {
    $hits = ['start' => $now, 'count' => 0];
}
$hits['count'] = (int) $hits['count'] + 1;
$_SESSION['chat_hits'] = $hits;
if ($hits['count'] > $maxHits) {
    http_response_code(429);
    echo json_encode(['error' => 'Trop de requêtes. Réessayez dans une minute.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Requête invalide']);
    exit;
}

$message = trim((string) ($payload['message'] ?? ''));
$message = mb_substr(strip_tags($message), 0, 2000);

if ($message === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Message vide']);
    exit;
}

$chat = SettingRepository::chat();

if ($chat['chat_enabled'] !== '1' || trim($chat['chat_n8n_webhook']) === '') {
    echo json_encode([
        'reply' => $chat['chat_offline_message'] ?: 'Merci pour votre message. Contactez-nous sur WhatsApp pour une réponse rapide.',
    ]);
    exit;
}

$webhook = trim($chat['chat_n8n_webhook']);
if (!preg_match('#^https://#i', $webhook)) {
    http_response_code(500);
    echo json_encode(['error' => 'Webhook chat mal configuré.']);
    exit;
}

$body = json_encode([
    'message' => $message,
    'source' => 'photocom-site',
    'sent_at' => date('c'),
], JSON_UNESCAPED_UNICODE);

$ch = curl_init($webhook);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
    CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
]);

$response = curl_exec($ch);
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$reply = $chat['chat_offline_message'] ?: 'Message reçu. Nous vous répondrons très bientôt.';

if ($response !== false && $status >= 200 && $status < 300) {
    $decoded = json_decode($response, true);
    if (is_array($decoded)) {
        $reply = (string) ($decoded['reply'] ?? $decoded['message'] ?? $decoded['output'] ?? $reply);
    } elseif (trim($response) !== '') {
        $reply = trim($response);
    }
}

$reply = mb_substr(strip_tags($reply), 0, 4000);
echo json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE);
