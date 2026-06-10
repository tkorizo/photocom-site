<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
$message = trim((string) ($payload['message'] ?? ''));

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

echo json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE);
