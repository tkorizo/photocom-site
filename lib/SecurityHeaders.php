<?php

declare(strict_types=1);

/**
 * En-têtes HTTP de sécurité pour le site public et l’admin.
 */
class SecurityHeaders
{
    public static function apply(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('X-XSS-Protection: 0');

        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (str_starts_with($requestPath, '/admin')) {
            header('X-Robots-Tag: noindex, nofollow');
        }

        // CSP permissive pour le site actuel (inline styles admin + chat), à durcir ensuite.
        $csp = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "img-src 'self' data: https: blob:",
            "media-src 'self' https: blob:",
            "font-src 'self' data: https:",
            "style-src 'self' 'unsafe-inline' https:",
            "script-src 'self' 'unsafe-inline' https:",
            "connect-src 'self' https:",
            "object-src 'none'",
        ]);
        header('Content-Security-Policy: ' . $csp);

        $https = Auth::isHttps();
        if ($https) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
