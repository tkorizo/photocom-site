<?php

declare(strict_types=1);

/**
 * Coupe les appels OpenAI sans valeur commerciale : bots, spam, insultes.
 */
class ChatGuard
{
    private const SESSION_LAST = 'chat_guard_last';
    private const SESSION_REPEAT = 'chat_guard_repeat';
    private const IP_BUCKET = 'chat-abuse';
    private const BLOCK_SECONDS = 6 * 3600;
    public const RESET_VERSION = '2026-09-14-unblock-1';

    /**
     * @return array{action: 'ok'|'canned'|'block', reply: string, reason: string}
     */
    public static function inspect(string $message, string $sessionId = ''): array
    {
        if (self::isBlocked($sessionId)) {
            return [
                'action' => 'block',
                'reply' => '',
                'reason' => 'blocked',
            ];
        }

        if (self::looksLikeBotAgent()) {
            return [
                'action' => 'block',
                'reply' => '',
                'reason' => 'bot',
            ];
        }

        if (self::isInsult($message)) {
            return [
                'action' => 'block',
                'reply' => 'PHOTOCOM reste à votre disposition pour le matériel photo et vidéo. Bonne journée.',
                'reason' => 'insult',
            ];
        }

        if (self::isSpam($message)) {
            return [
                'action' => 'canned',
                'reply' => 'Je peux vous aider uniquement pour le matériel PHOTOCOM (photo, vidéo, studio). Que recherchez-vous ?',
                'reason' => 'spam',
            ];
        }

        if (self::isRepeat($message)) {
            return [
                'action' => 'canned',
                'reply' => 'J’ai bien reçu votre message. Dites-moi le modèle ou l’usage, je vous oriente dans le catalogue.',
                'reason' => 'repeat',
            ];
        }

        return ['action' => 'ok', 'reply' => '', 'reason' => ''];
    }

    public static function isBlocked(string $sessionId = ''): bool
    {
        if (RateLimiter::isLocked(self::IP_BUCKET)) {
            return true;
        }
        if ($sessionId !== '' && self::sessionFileBlocked($sessionId)) {
            return true;
        }
        if ($sessionId !== '' && ChatConversationRepository::isSessionBlocked($sessionId)) {
            return true;
        }

        return false;
    }

    public static function isConversationBlocked(array $conversation): bool
    {
        $until = (int) ($conversation['blocked_until'] ?? 0);
        if ($until > time()) {
            return true;
        }
        $sessionId = trim((string) ($conversation['session_id'] ?? ''));
        $ip = trim((string) ($conversation['visitor_ip'] ?? ''));
        if ($sessionId !== '' && self::sessionFileBlocked($sessionId)) {
            return true;
        }
        if ($ip !== '' && RateLimiter::isLockedForIp(self::IP_BUCKET, $ip)) {
            return true;
        }

        return false;
    }

    public static function block(string $sessionId = ''): void
    {
        RateLimiter::lock(self::IP_BUCKET, self::BLOCK_SECONDS);
        if ($sessionId !== '') {
            self::markSessionFile($sessionId);
            ChatConversationRepository::setBlocked($sessionId, time() + self::BLOCK_SECONDS, 'abuse');
        }
    }

    public static function unblockVisitor(string $sessionId = '', string $ip = ''): void
    {
        $sessionId = trim($sessionId);
        $ip = trim($ip);
        if ($sessionId !== '') {
            self::clearSessionFile($sessionId);
            ChatConversationRepository::clearBlocked($sessionId);
        }
        if ($ip !== '') {
            RateLimiter::resetForIp(self::IP_BUCKET, $ip);
        }
    }

    public static function clearAllBlocks(): void
    {
        $dir = ROOT_PATH . '/storage/rate-limit';
        if (is_dir($dir)) {
            foreach (glob($dir . '/chat-sid-*.json') ?: [] as $file) {
                @unlink($file);
            }
        }
        foreach (ChatConversationRepository::blockTargets() as $row) {
            self::unblockVisitor((string) ($row['session_id'] ?? ''), (string) ($row['visitor_ip'] ?? ''));
        }
        ChatConversationRepository::clearAllBlocked();
    }

    public static function applyResetOnce(): void
    {
        if (SettingRepository::get('chat_block_reset') === self::RESET_VERSION) {
            return;
        }
        self::clearAllBlocks();
        SettingRepository::set('chat_block_reset', self::RESET_VERSION);
    }

    public static function honeypotFilled(array $payload): bool
    {
        foreach (['company_url', 'website', 'url'] as $key) {
            if (trim((string) ($payload[$key] ?? '')) !== '') {
                return true;
            }
        }

        return trim((string) ($_POST['company_url'] ?? '')) !== '';
    }

    public static function tooFast(?int $openedAt): bool
    {
        if ($openedAt === null || $openedAt < 1) {
            return false;
        }
        $elapsed = time() - $openedAt;

        return $elapsed >= 0 && $elapsed < 2;
    }

    private static function isInsult(string $message): bool
    {
        $text = self::normalize($message);
        if ($text === '') {
            return false;
        }

        $phrases = [
            'nique ta mere', 'nique ta mère', 'ntm', 'nique le bot',
            'ta gueule', 'ferme ta gueule', 'va te faire',
            'fuck you', 'stupid bot', 'kill yourself',
            'nik mok', 'n3al dik', 'khra 3lik', 'khra 3likom',
        ];
        foreach ($phrases as $phrase) {
            if (str_contains($text, self::normalize($phrase))) {
                return true;
            }
        }

        $words = [
            'connard', 'connasse', 'encule', 'enculee', 'salope', 'salopard',
            'fdp', 'ntm', 'enfoire', 'enfoiree',
            'zamel', 'zaml', '9ahba', 'qahba', 'kahba', 'zbi', 'zbii',
            'asshole', 'motherfucker',
            'قحبة', 'زامل', 'انعل',
        ];
        foreach ($words as $word) {
            $safe = preg_quote(self::normalize($word), '/');
            if ($safe !== '' && preg_match('/(^|[^\p{L}\p{N}])' . $safe . '([^\p{L}\p{N}]|$)/u', $text)) {
                return true;
            }
        }

        return false;
    }

    private static function isSpam(string $message): bool
    {
        if (preg_match_all('/https?:\/\//i', $message, $m) && count($m[0]) >= 2) {
            return true;
        }
        if (preg_match('/https?:\/\/(?!(www\.)?photocom\.ma)/i', $message)) {
            return true;
        }
        if (preg_match('/(.)\1{10,}/u', $message)) {
            return true;
        }
        $low = self::normalize($message);
        foreach (['seo backlink', 'buy now', 'crypto', 'viagra', 'casino', 'forex', 'onlyfans'] as $needle) {
            if (str_contains($low, $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function isRepeat(string $message): bool
    {
        Auth::startSession();
        $key = md5(self::normalize($message));
        $prev = (string) ($_SESSION[self::SESSION_REPEAT] ?? '');
        $count = (int) ($_SESSION['chat_guard_repeat_n'] ?? 0);
        if ($prev === $key) {
            $_SESSION['chat_guard_repeat_n'] = $count + 1;

            return $count >= 1;
        }
        $_SESSION[self::SESSION_REPEAT] = $key;
        $_SESSION['chat_guard_repeat_n'] = 1;

        return false;
    }

    private static function looksLikeBotAgent(): bool
    {
        $ua = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if ($ua === '') {
            return false;
        }

        return preg_match('/curl|wget|python-requests|scrapy|httpclient|libwww|go-http|crawler|spider|headlesschrome/i', $ua) === 1;
    }

    private static function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $map = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'œ' => 'oe',
        ];

        return strtr($value, $map);
    }

    private static function sessionFileBlocked(string $sessionId): bool
    {
        $file = self::sessionFile($sessionId);
        if (!is_file($file)) {
            return false;
        }
        $until = (int) (json_decode((string) file_get_contents($file), true)['until'] ?? 0);

        return $until > time();
    }

    private static function markSessionFile(string $sessionId): void
    {
        $dir = ROOT_PATH . '/storage/rate-limit';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }
        $payload = json_encode(['until' => time() + self::BLOCK_SECONDS], JSON_UNESCAPED_UNICODE) ?: '{}';
        @file_put_contents(self::sessionFile($sessionId), $payload, LOCK_EX);
    }

    private static function clearSessionFile(string $sessionId): void
    {
        $file = self::sessionFile($sessionId);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private static function sessionFile(string $sessionId): string
    {
        return ROOT_PATH . '/storage/rate-limit/chat-sid-' . hash('sha256', $sessionId) . '.json';
    }
}
