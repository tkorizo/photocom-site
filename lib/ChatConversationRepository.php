<?php

declare(strict_types=1);

class ChatConversationRepository
{
    public static function normalizeSessionId(string $sessionId): string
    {
        $sessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $sessionId) ?? '';
        if (strlen($sessionId) < 8) {
            $sessionId = bin2hex(random_bytes(16));
        }

        return substr($sessionId, 0, 64);
    }

    /** @param array<string, mixed> $visitor */
    public static function findOrCreate(string $sessionId, string $pageUrl = '', array $visitor = []): array
    {
        $sessionId = self::normalizeSessionId($sessionId);
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM chat_conversations WHERE session_id = :sid LIMIT 1');
        $stmt->execute(['sid' => $sessionId]);
        $row = $stmt->fetch();
        if ($row) {
            if ($pageUrl !== '') {
                $upd = $db->prepare('UPDATE chat_conversations SET page_url = :url WHERE id = :id');
                $upd->execute(['url' => mb_substr($pageUrl, 0, 500), 'id' => (int) $row['id']]);
                $row['page_url'] = mb_substr($pageUrl, 0, 500);
            }

            return self::mergeVisitor((array) $row, self::needsGeo((array) $row) ? VisitorInfo::enrichGeo($visitor) : $visitor);
        }

        $visitor = VisitorInfo::enrichGeo($visitor);
        $meta = $visitor['meta'] ?? [];
        $ins = $db->prepare(
            'INSERT INTO chat_conversations (
                session_id, page_url, last_message_at,
                visitor_ip, visitor_country, visitor_city, visitor_user_agent, visitor_meta
             ) VALUES (:sid, :url, datetime(\'now\'), :ip, :country, :city, :ua, :meta)'
        );
        $ins->execute([
            'sid' => $sessionId,
            'url' => mb_substr($pageUrl, 0, 500),
            'ip' => mb_substr(trim((string) ($visitor['ip'] ?? '')), 0, 64),
            'country' => mb_substr(trim((string) ($visitor['country'] ?? '')), 0, 80),
            'city' => mb_substr(trim((string) ($visitor['city'] ?? '')), 0, 80),
            'ua' => mb_substr(trim((string) ($visitor['user_agent'] ?? '')), 0, 400),
            'meta' => is_array($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
        ]);

        $stmt->execute(['sid' => $sessionId]);
        $created = $stmt->fetch();

        return $created ?: ['id' => (int) $db->lastInsertId(), 'session_id' => $sessionId, 'page_url' => $pageUrl];
    }

    /** @param array<string, mixed> $row */
    private static function needsGeo(array $row): bool
    {
        return trim((string) ($row['visitor_country'] ?? '')) === ''
            && trim((string) ($row['visitor_ip'] ?? '')) === '';
    }

    /** @param array<string, mixed> $row @param array<string, mixed> $visitor */
    private static function mergeVisitor(array $row, array $visitor): array
    {
        if ($visitor === []) {
            return $row;
        }

        $updates = [];
        $fields = [
            'visitor_ip' => mb_substr(trim((string) ($visitor['ip'] ?? '')), 0, 64),
            'visitor_country' => mb_substr(trim((string) ($visitor['country'] ?? '')), 0, 80),
            'visitor_city' => mb_substr(trim((string) ($visitor['city'] ?? '')), 0, 80),
            'visitor_user_agent' => mb_substr(trim((string) ($visitor['user_agent'] ?? '')), 0, 400),
        ];
        foreach ($fields as $column => $value) {
            if ($value !== '' && trim((string) ($row[$column] ?? '')) === '') {
                $updates[$column] = $value;
                $row[$column] = $value;
            }
        }

        $existingMeta = VisitorInfo::decodeMeta(isset($row['visitor_meta']) ? (string) $row['visitor_meta'] : null);
        $incomingMeta = is_array($visitor['meta'] ?? null) ? $visitor['meta'] : [];
        $mergedMeta = $existingMeta;
        foreach ($incomingMeta as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $value = trim((string) $value);
            if ($value !== '' && trim((string) ($mergedMeta[$key] ?? '')) === '') {
                $mergedMeta[$key] = mb_substr($value, 0, 400);
            }
        }
        if ($mergedMeta !== $existingMeta) {
            $encoded = json_encode($mergedMeta, JSON_UNESCAPED_UNICODE);
            $updates['visitor_meta'] = $encoded;
            $row['visitor_meta'] = $encoded;
        }

        if ($updates === []) {
            return $row;
        }

        $sets = [];
        $params = ['id' => (int) $row['id']];
        foreach ($updates as $column => $value) {
            $sets[] = $column . ' = :' . $column;
            $params[$column] = $value;
        }
        Database::getInstance()->prepare(
            'UPDATE chat_conversations SET ' . implode(', ', $sets) . ' WHERE id = :id'
        )->execute($params);

        return $row;
    }

    public static function addMessage(int $conversationId, string $role, string $content, ?array $products = null): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO chat_messages (conversation_id, role, content, products_json)
             VALUES (:cid, :role, :content, :products)'
        );
        $stmt->execute([
            'cid' => $conversationId,
            'role' => $role,
            'content' => $content,
            'products' => $products !== null ? json_encode($products, JSON_UNESCAPED_UNICODE) : null,
        ]);
        $db->prepare(
            'UPDATE chat_conversations
             SET message_count = message_count + 1, last_message_at = datetime(\'now\')
             WHERE id = :id'
        )->execute(['id' => $conversationId]);
    }

    /** @return list<array{role:string,content:string}> */
    public static function recentMessages(int $conversationId, int $limit = 10): array
    {
        $limit = max(2, min(20, $limit));
        $stmt = Database::getInstance()->prepare(
            "SELECT role, content FROM chat_messages
             WHERE conversation_id = :cid AND role IN ('user', 'assistant')
             ORDER BY id DESC
             LIMIT {$limit}"
        );
        $stmt->execute(['cid' => $conversationId]);
        $rows = $stmt->fetchAll() ?: [];

        return array_reverse($rows);
    }

    public static function count(?string $search = null): int
    {
        if ($search !== null && trim($search) !== '') {
            $like = '%' . trim($search) . '%';
            $stmt = Database::getInstance()->prepare(
                'SELECT COUNT(DISTINCT c.id) FROM chat_conversations c
                 LEFT JOIN chat_messages m ON m.conversation_id = c.id
                 WHERE c.session_id LIKE :q OR c.page_url LIKE :q2 OR m.content LIKE :q3
                    OR c.visitor_ip LIKE :q4 OR c.visitor_country LIKE :q5 OR c.visitor_city LIKE :q6'
            );
            $stmt->execute(['q' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like, 'q5' => $like, 'q6' => $like]);

            return (int) $stmt->fetchColumn();
        }

        return (int) Database::getInstance()->query('SELECT COUNT(*) FROM chat_conversations')->fetchColumn();
    }

    /** @return list<array<string, mixed>> */
    public static function paginate(int $page = 1, int $perPage = 20, ?string $search = null): array
    {
        $page = max(1, $page);
        $perPage = max(5, min(50, $perPage));
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = '';
        if ($search !== null && trim($search) !== '') {
            $like = '%' . trim($search) . '%';
            $where = 'WHERE c.id IN (
                SELECT conversation_id FROM chat_messages WHERE content LIKE :q3
                UNION
                SELECT id FROM chat_conversations
                WHERE session_id LIKE :q OR page_url LIKE :q2
                   OR visitor_ip LIKE :q4 OR visitor_country LIKE :q5 OR visitor_city LIKE :q6
            )';
            $params = ['q' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like, 'q5' => $like, 'q6' => $like];
        }

        $sql = "SELECT c.* FROM chat_conversations c
                {$where}
                ORDER BY COALESCE(c.last_message_at, c.created_at) DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public static function listForExport(?string $search = null, ?int $id = null): array
    {
        if ($id !== null && $id > 0) {
            $row = self::find($id);

            return $row ? [$row] : [];
        }

        $params = [];
        $where = '';
        if ($search !== null && trim($search) !== '') {
            $like = '%' . trim($search) . '%';
            $where = 'WHERE c.id IN (
                SELECT conversation_id FROM chat_messages WHERE content LIKE :q3
                UNION
                SELECT id FROM chat_conversations
                WHERE session_id LIKE :q OR page_url LIKE :q2
                   OR visitor_ip LIKE :q4 OR visitor_country LIKE :q5 OR visitor_city LIKE :q6
            )';
            $params = ['q' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like, 'q5' => $like, 'q6' => $like];
        }

        $stmt = Database::getInstance()->prepare(
            "SELECT c.* FROM chat_conversations c
             {$where}
             ORDER BY COALESCE(c.last_message_at, c.created_at) DESC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM chat_conversations WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public static function messages(int $conversationId): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM chat_messages WHERE conversation_id = :cid ORDER BY id ASC'
        );
        $stmt->execute(['cid' => $conversationId]);

        return $stmt->fetchAll() ?: [];
    }

    public static function setBlocked(string $sessionId, int $until, string $reason = 'abuse'): void
    {
        $sessionId = self::normalizeSessionId($sessionId);
        Database::getInstance()->prepare(
            'UPDATE chat_conversations
             SET blocked_until = :until, blocked_reason = :reason
             WHERE session_id = :sid'
        )->execute([
            'until' => max(0, $until),
            'reason' => mb_substr($reason, 0, 40),
            'sid' => $sessionId,
        ]);
    }

    public static function clearBlocked(string $sessionId): void
    {
        $sessionId = self::normalizeSessionId($sessionId);
        Database::getInstance()->prepare(
            'UPDATE chat_conversations
             SET blocked_until = 0, blocked_reason = NULL
             WHERE session_id = :sid'
        )->execute(['sid' => $sessionId]);
    }

    public static function clearAllBlocked(): void
    {
        Database::getInstance()->exec(
            'UPDATE chat_conversations SET blocked_until = 0, blocked_reason = NULL WHERE blocked_until > 0'
        );
    }

    public static function isSessionBlocked(string $sessionId): bool
    {
        $sessionId = self::normalizeSessionId($sessionId);
        $stmt = Database::getInstance()->prepare(
            'SELECT blocked_until FROM chat_conversations WHERE session_id = :sid LIMIT 1'
        );
        $stmt->execute(['sid' => $sessionId]);
        $until = (int) ($stmt->fetchColumn() ?: 0);

        return $until > time();
    }

    /** @return list<array{session_id:string,visitor_ip:?string}> */
    public static function blockTargets(): array
    {
        $stmt = Database::getInstance()->query(
            'SELECT session_id, visitor_ip FROM chat_conversations'
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public static function delete(int $id): void
    {
        Database::getInstance()->prepare('DELETE FROM chat_conversations WHERE id = :id')->execute(['id' => $id]);
    }

    public static function firstUserMessage(int $conversationId): string
    {
        $stmt = Database::getInstance()->prepare(
            "SELECT content FROM chat_messages WHERE conversation_id = :cid AND role = 'user' ORDER BY id ASC LIMIT 1"
        );
        $stmt->execute(['cid' => $conversationId]);
        $text = (string) ($stmt->fetchColumn() ?: '');

        return mb_strlen($text) > 80 ? rtrim(mb_substr($text, 0, 79)) . '…' : $text;
    }
}
