<?php

declare(strict_types=1);

/**
 * Limite de requêtes / tentatives par IP (fichiers dans storage/rate-limit).
 */
class RateLimiter
{
    public static function clientIp(): string
    {
        $candidates = [
            trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? '')),
            trim((string) ($_SERVER['HTTP_X_REAL_IP'] ?? '')),
            trim((string) ($_SERVER['REMOTE_ADDR'] ?? '')),
        ];
        foreach ($candidates as $ip) {
            if (self::isPublicIp($ip)) {
                return $ip;
            }
        }

        $forwarded = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        if ($forwarded !== '') {
            foreach (array_map('trim', explode(',', $forwarded)) as $ip) {
                if (self::isPublicIp($ip)) {
                    return $ip;
                }
            }
        }

        $fallback = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

        return $fallback !== '' ? $fallback : '0.0.0.0';
    }

    /** true = requête autorisée. */
    public static function allow(string $bucket, int $maxHits, int $windowSeconds): bool
    {
        $now = time();
        $data = self::read($bucket);
        $hits = [];
        foreach ($data['hits'] ?? [] as $ts) {
            $ts = (int) $ts;
            if ($ts > $now - $windowSeconds) {
                $hits[] = $ts;
            }
        }
        if (count($hits) >= $maxHits) {
            self::write($bucket, ['hits' => $hits]);
            return false;
        }
        $hits[] = $now;
        self::write($bucket, ['hits' => $hits]);

        return true;
    }

    public static function isLocked(string $bucket): bool
    {
        return self::lockRemaining($bucket) > 0;
    }

    public static function lockRemaining(string $bucket): int
    {
        $data = self::read($bucket);
        $until = (int) ($data['lock_until'] ?? 0);

        return max(0, $until - time());
    }

    public static function fail(string $bucket, int $maxFails, int $lockSeconds): void
    {
        $data = self::read($bucket);
        if ((int) ($data['lock_until'] ?? 0) > time()) {
            return;
        }
        $fails = (int) ($data['fails'] ?? 0) + 1;
        if ($fails >= $maxFails) {
            self::write($bucket, [
                'fails' => 0,
                'lock_until' => time() + $lockSeconds,
            ]);
            return;
        }
        self::write($bucket, ['fails' => $fails, 'lock_until' => 0]);
    }

    public static function reset(string $bucket): void
    {
        $file = self::file($bucket);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public static function lock(string $bucket, int $seconds): void
    {
        self::write($bucket, [
            'fails' => 0,
            'lock_until' => time() + max(60, $seconds),
        ]);
    }

    private static function isPublicIp(string $ip): bool
    {
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    /** @return array<string, mixed> */
    private static function read(string $bucket): array
    {
        $file = self::file($bucket);
        if (!is_file($file)) {
            return [];
        }
        $fh = fopen($file, 'c+');
        if ($fh === false) {
            return [];
        }
        flock($fh, LOCK_SH);
        $raw = stream_get_contents($fh);
        flock($fh, LOCK_UN);
        fclose($fh);
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string, mixed> $data */
    private static function write(string $bucket, array $data): void
    {
        $dir = self::directory();
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }
        $file = self::file($bucket);
        $fh = fopen($file, 'c+');
        if ($fh === false) {
            return;
        }
        flock($fh, LOCK_EX);
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($data, JSON_UNESCAPED_UNICODE) ?: '{}');
        fflush($fh);
        flock($fh, LOCK_UN);
        fclose($fh);
        @chmod($file, 0640);
    }

    private static function directory(): string
    {
        return ROOT_PATH . '/storage/rate-limit';
    }

    private static function file(string $bucket): string
    {
        $key = hash('sha256', $bucket . '|' . self::clientIp());

        return self::directory() . '/' . $key . '.json';
    }
}
