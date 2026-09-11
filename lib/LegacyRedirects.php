<?php

declare(strict_types=1);

class LegacyRedirects
{
    /**
     * @param array{exact?: array<string,string>, prefixes?: array<string,string>} $map
     */
    public static function resolve(string $uri, array $map): ?string
    {
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/';
        }
        $path = rawurldecode($path);
        $path = '/' . strtolower(trim($path, '/'));
        if ($path === '/') {
            return null;
        }

        $exact = $map['exact'] ?? [];
        $target = $exact[$path] ?? null;
        if (!is_string($target) || $target === '') {
            $target = null;
            foreach ($map['prefixes'] ?? [] as $prefix => $to) {
                if (!is_string($prefix) || $prefix === '' || !is_string($to) || $to === '') {
                    continue;
                }
                $prefix = '/' . strtolower(trim($prefix, '/'));
                if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                    $target = $to;
                    break;
                }
            }
        }

        if ($target === null) {
            return null;
        }

        if (
            !str_starts_with($target, '/')
            || str_starts_with($target, '//')
            || str_contains($target, '\\')
            || preg_match('#[\x00-\x1F\x7F]#', $target) === 1
        ) {
            return null;
        }

        $targetPath = parse_url($target, PHP_URL_PATH);
        $normalizedTarget = is_string($targetPath) && $targetPath !== ''
            ? '/' . trim($targetPath, '/')
            : '/';
        if ($normalizedTarget === '') {
            $normalizedTarget = '/';
        }
        if ($normalizedTarget === $path) {
            return null;
        }

        return $target;
    }

    /**
     * @param array{exact?: array<string,string>, prefixes?: array<string,string>} $map
     */
    public static function apply(string $uri, array $map): void
    {
        $target = self::resolve($uri, $map);
        if ($target === null) {
            return;
        }

        header('Location: ' . $target, true, 301);
        header('X-Robots-Tag: noindex, follow');
        exit;
    }
}
