<?php

declare(strict_types=1);

/**
 * Journal des erreurs PHP (fichier JSON Lines, une ligne par erreur).
 */
class ErrorLogger
{
    private static bool $booted = false;
    private static bool $handling = false;

    public static function directory(): string
    {
        return ROOT_PATH . '/storage/logs';
    }

    public static function path(): string
    {
        return self::directory() . '/php-errors.log';
    }

    public static function ensureReady(): void
    {
        $dir = self::directory();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $keep = $dir . '/.gitkeep';
        if (!is_file($keep)) {
            file_put_contents($keep, '');
        }
    }

    /** Active la capture des erreurs / exceptions (à appeler depuis bootstrap). */
    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        $isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
        $display = $isCli || env('APP_DEBUG', '0') === '1';

        ini_set('display_errors', $display ? '1' : '0');
        ini_set('display_startup_errors', $display ? '1' : '0');
        ini_set('log_errors', '1');
        error_reporting(E_ALL);

        try {
            self::ensureReady();
            ini_set('error_log', self::path() . '.native');
        } catch (Throwable) {
            // ignore
        }

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            // Respecte @
            if (!(error_reporting() & $severity)) {
                return false;
            }

            $level = match (true) {
                in_array($severity, [E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_CORE_ERROR, E_COMPILE_ERROR], true) => 'error',
                in_array($severity, [E_WARNING, E_USER_WARNING, E_CORE_WARNING, E_COMPILE_WARNING], true) => 'warning',
                in_array($severity, [E_NOTICE, E_USER_NOTICE], true) => 'notice',
                in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED], true) => 'deprecated',
                default => 'info',
            };

            self::write($level, $message, [
                'type' => 'php_error',
                'severity' => $severity,
                'file' => $file,
                'line' => $line,
            ]);

            // false = laisser PHP continuer son comportement par défaut si display_errors
            return false;
        });

        set_exception_handler(static function (Throwable $e): void {
            self::write('error', $e->getMessage(), [
                'type' => 'exception',
                'class' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => self::formatTrace($e),
            ]);

            if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
                fwrite(STDERR, $e::class . ': ' . $e->getMessage() . PHP_EOL);
                return;
            }

            if (env('APP_DEBUG', '0') === '1') {
                http_response_code(500);
                header('Content-Type: text/plain; charset=utf-8');
                echo $e::class . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine();
                return;
            }

            http_response_code(500);
            if (!headers_sent()) {
                header('Content-Type: text/html; charset=utf-8');
            }
            echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Erreur</title>';
            echo '<style>body{font-family:system-ui,sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center;background:#0a0a0c;color:#f5f5f5;margin:0}';
            echo '.box{max-width:28rem;padding:2rem;text-align:center}h1{font-size:1.25rem;margin:0 0 .75rem}p{opacity:.75;line-height:1.5}</style></head><body>';
            echo '<div class="box"><h1>Une erreur est survenue</h1>';
            echo '<p>Nous avons enregistré le problème. Réessayez dans un instant ou contactez-nous si cela persiste.</p></div></body></html>';
        });

        register_shutdown_function(static function (): void {
            $err = error_get_last();
            if ($err === null) {
                return;
            }
            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
            if (!in_array($err['type'], $fatalTypes, true)) {
                return;
            }
            self::write('error', (string) $err['message'], [
                'type' => 'fatal',
                'severity' => $err['type'],
                'file' => $err['file'] ?? null,
                'line' => $err['line'] ?? null,
            ]);
        });
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function write(string $level, string $message, array $context = []): void
    {
        if (self::$handling) {
            return;
        }
        self::$handling = true;

        try {
            self::ensureReady();

            $uri = $_SERVER['REQUEST_URI'] ?? null;
            $method = $_SERVER['REQUEST_METHOD'] ?? null;
            $userId = null;
            try {
                if (class_exists('Auth') && Auth::check()) {
                    $userId = Auth::user()['id'] ?? null;
                }
            } catch (Throwable) {
                $userId = null;
            }
            $entry = [
                'at' => date('c'),
                'level' => $level,
                'message' => mb_substr($message, 0, 2000),
                'url' => $uri,
                'method' => $method,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_id' => $userId,
                'context' => $context !== [] ? $context : null,
            ];

            file_put_contents(
                self::path(),
                json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        } catch (Throwable) {
            // Ne jamais propager une erreur de log
        } finally {
            self::$handling = false;
        }
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    /** @return list<array<string, mixed>> */
    public static function recent(int $limit = 300): array
    {
        $path = self::path();
        if (!is_file($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false || $lines === []) {
            return [];
        }

        $slice = array_slice($lines, -$limit);
        $entries = [];
        foreach (array_reverse($slice) as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $entries[] = $decoded;
            }
        }

        return $entries;
    }

    public static function clear(): bool
    {
        $path = self::path();
        if (!is_file($path)) {
            return true;
        }

        return file_put_contents($path, '') !== false;
    }

    public static function size(): int
    {
        $path = self::path();
        return is_file($path) ? (int) filesize($path) : 0;
    }

    public static function count(): int
    {
        $path = self::path();
        if (!is_file($path)) {
            return 0;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        return is_array($lines) ? count($lines) : 0;
    }

    public static function levelLabel(string $level): string
    {
        return match ($level) {
            'error' => 'Erreur',
            'warning' => 'Avertissement',
            'notice' => 'Notice',
            'deprecated' => 'Déprécié',
            'info' => 'Info',
            default => $level,
        };
    }

    private static function formatTrace(Throwable $e): string
    {
        $frames = [];
        foreach (array_slice($e->getTrace(), 0, 12) as $i => $frame) {
            $file = $frame['file'] ?? '?';
            $line = $frame['line'] ?? '?';
            $fn = ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '');
            $frames[] = "#{$i} {$file}:{$line} {$fn}";
        }

        return implode("\n", $frames);
    }
}
