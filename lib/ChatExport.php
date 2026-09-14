<?php

declare(strict_types=1);

class ChatExport
{
    /**
     * @param array<string, mixed> $conversation
     * @return array<string, mixed>
     */
    public static function conversationPayload(array $conversation): array
    {
        $meta = VisitorInfo::decodeMeta(isset($conversation['visitor_meta']) ? (string) $conversation['visitor_meta'] : null);
        $ua = trim((string) ($conversation['visitor_user_agent'] ?? ''));
        $device = VisitorInfo::normalizeDevice((string) ($meta['device'] ?? ''));
        if ($device === '') {
            $device = VisitorInfo::deviceFromUserAgent($ua);
        }

        $id = (int) ($conversation['id'] ?? 0);
        $messages = [];
        foreach (ChatConversationRepository::messages($id) as $msg) {
            $role = (string) ($msg['role'] ?? 'user');
            $messages[] = [
                'id' => (int) ($msg['id'] ?? 0),
                'role' => $role === 'assistant' ? 'agent' : 'visiteur',
                'content' => (string) ($msg['content'] ?? ''),
                'created_at' => (string) ($msg['created_at'] ?? ''),
            ];
        }

        return [
            'id' => $id,
            'session_id' => (string) ($conversation['session_id'] ?? ''),
            'created_at' => (string) ($conversation['created_at'] ?? ''),
            'last_message_at' => (string) ($conversation['last_message_at'] ?? ''),
            'page_url' => (string) ($conversation['page_url'] ?? ''),
            'message_count' => (int) ($conversation['message_count'] ?? count($messages)),
            'visitor' => [
                'ip' => (string) ($conversation['visitor_ip'] ?? ''),
                'country' => (string) ($conversation['visitor_country'] ?? ''),
                'city' => (string) ($conversation['visitor_city'] ?? ''),
                'device' => $device,
                'browser' => (string) ($meta['browser'] ?? ''),
                'os' => (string) ($meta['os'] ?? ''),
                'language' => (string) ($meta['language'] ?? ''),
                'timezone' => (string) ($meta['timezone'] ?? ''),
                'isp' => (string) (($meta['isp'] ?? '') !== '' ? $meta['isp'] : ($meta['org'] ?? '')),
            ],
            'messages' => $messages,
        ];
    }

    /** @return array{exported_at:string,count:int,conversations:list<array<string,mixed>>} */
    public static function payload(?int $id = null, ?string $search = null): array
    {
        $rows = ChatConversationRepository::listForExport($search, $id);
        $conversations = [];
        foreach ($rows as $row) {
            $conversations[] = self::conversationPayload($row);
        }

        return [
            'exported_at' => date('c'),
            'source' => 'photocom.ma',
            'count' => count($conversations),
            'conversations' => $conversations,
        ];
    }

    public static function download(string $format, ?int $id = null, ?string $search = null): void
    {
        $payload = self::payload($id, $search);
        $stamp = date('Y-m-d-His');
        $base = $id !== null && $id > 0
            ? 'photocom-chat-' . $id . '-' . $stamp
            : 'photocom-chats-' . $stamp;

        if ($format === 'csv') {
            self::sendCsv($payload, $base . '.csv');
            return;
        }

        self::sendJson($payload, $base . '.json');
    }

    /** @param array{exported_at:string,count:int,conversations:list<array<string,mixed>>} $payload */
    private static function sendJson(array $payload, string $filename): void
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) {
            $json = '{"error":"export impossible"}';
        }
        self::headers('application/json; charset=utf-8', $filename);
        echo $json;
        exit;
    }

    /** @param array{exported_at:string,count:int,conversations:list<array<string,mixed>>} $payload */
    private static function sendCsv(array $payload, string $filename): void
    {
        self::headers('text/csv; charset=utf-8', $filename);
        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'conversation_id',
            'session_id',
            'conversation_created_at',
            'last_message_at',
            'page_url',
            'visitor_ip',
            'visitor_country',
            'visitor_city',
            'device',
            'browser',
            'os',
            'language',
            'timezone',
            'isp',
            'message_id',
            'role',
            'message_created_at',
            'content',
        ], ';');

        foreach ($payload['conversations'] as $conv) {
            $visitor = is_array($conv['visitor'] ?? null) ? $conv['visitor'] : [];
            $messages = is_array($conv['messages'] ?? null) ? $conv['messages'] : [];
            if ($messages === []) {
                fputcsv($out, [
                    $conv['id'] ?? '',
                    $conv['session_id'] ?? '',
                    $conv['created_at'] ?? '',
                    $conv['last_message_at'] ?? '',
                    $conv['page_url'] ?? '',
                    $visitor['ip'] ?? '',
                    $visitor['country'] ?? '',
                    $visitor['city'] ?? '',
                    $visitor['device'] ?? '',
                    $visitor['browser'] ?? '',
                    $visitor['os'] ?? '',
                    $visitor['language'] ?? '',
                    $visitor['timezone'] ?? '',
                    $visitor['isp'] ?? '',
                    '',
                    '',
                    '',
                    '',
                ], ';');
                continue;
            }
            foreach ($messages as $msg) {
                fputcsv($out, [
                    $conv['id'] ?? '',
                    $conv['session_id'] ?? '',
                    $conv['created_at'] ?? '',
                    $conv['last_message_at'] ?? '',
                    $conv['page_url'] ?? '',
                    $visitor['ip'] ?? '',
                    $visitor['country'] ?? '',
                    $visitor['city'] ?? '',
                    $visitor['device'] ?? '',
                    $visitor['browser'] ?? '',
                    $visitor['os'] ?? '',
                    $visitor['language'] ?? '',
                    $visitor['timezone'] ?? '',
                    $visitor['isp'] ?? '',
                    $msg['id'] ?? '',
                    $msg['role'] ?? '',
                    $msg['created_at'] ?? '',
                    $msg['content'] ?? '',
                ], ';');
            }
        }
        fclose($out);
        exit;
    }

    private static function headers(string $contentType, string $filename): void
    {
        $safe = preg_replace('/[^a-zA-Z0-9._-]/', '-', $filename) ?: 'export.json';
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $safe . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('X-Robots-Tag: noindex, nofollow');
    }
}
