<?php

declare(strict_types=1);

/**
 * Coupe les appels OpenAI sans valeur commerciale : bots, spam, insultes.
 */
class ChatGuard
{
    private const SESSION_BLOCK = 'chat_guard_blocked';
    private const SESSION_LAST = 'chat_guard_last';
    private const SESSION_REPEAT = 'chat_guard_repeat';
    private const IP_BUCKET = 'chat-abuse';

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
        Auth::startSession();
        if (!empty($_SESSION[self::SESSION_BLOCK])) {
            return true;
        }
        if (RateLimiter::isLocked(self::IP_BUCKET)) {
            return true;
        }
        if ($sessionId !== '' && self::sessionFileBlocked($sessionId)) {
            return true;
        }

        return false;
    }

    public static function block(string $sessionId = ''): void
    {
        Auth::startSession();
        $_SESSION[self::SESSION_BLOCK] = 1;
        RateLimiter::lock(self::IP_BUCKET, 6 * 3600);
        if ($sessionId !== '') {
            self::markSessionFile($sessionId);
        }
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
        $payload = json_encode(['until' => time() + 6 * 3600], JSON_UNESCAPED_UNICODE) ?: '{}';
        @file_put_contents(self::sessionFile($sessionId), $payload, LOCK_EX);
    }

    private static function sessionFile(string $sessionId): string
    {
        return ROOT_PATH . '/storage/rate-limit/chat-sid-' . hash('sha256', $sessionId) . '.json';
    }
}
