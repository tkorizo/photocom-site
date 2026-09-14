<?php

declare(strict_types=1);

class VisitorInfo
{
    /**
     * @param array<string, mixed> $clientHints
     * @return array{ip:string,country:string,city:string,user_agent:string,meta:array<string,string>}
     */
    public static function collect(array $clientHints = []): array
    {
        $ip = RateLimiter::clientIp();
        $ua = mb_substr(trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 400);
        $parsed = self::parseUserAgent($ua);
        $acceptLanguage = mb_substr(trim((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '')), 0, 120);
        $referer = mb_substr(trim((string) ($_SERVER['HTTP_REFERER'] ?? '')), 0, 400);
        $hints = self::sanitizeHints($clientHints);
        $device = self::normalizeDevice($hints['device'] !== '' ? $hints['device'] : $parsed['device']);

        $geo = self::geoFromHeaders();

        $language = $hints['language'] !== '' ? $hints['language'] : self::primaryLanguage($acceptLanguage);
        $timezone = $hints['timezone'] !== '' ? $hints['timezone'] : (string) ($geo['timezone'] ?? '');

        $meta = [];
        foreach ([
            'country_code' => (string) ($geo['country_code'] ?? ''),
            'region' => (string) ($geo['region'] ?? ''),
            'isp' => (string) ($geo['isp'] ?? ''),
            'org' => (string) ($geo['org'] ?? ''),
            'timezone' => $timezone,
            'browser' => $parsed['browser'],
            'os' => $parsed['os'],
            'device' => $device,
            'language' => $language,
            'languages' => $acceptLanguage,
            'referer' => $referer,
        ] as $key => $value) {
            $value = trim($value);
            if ($value !== '') {
                $meta[$key] = $value;
            }
        }

        return [
            'ip' => $ip,
            'country' => (string) ($geo['country'] ?? ''),
            'city' => (string) ($geo['city'] ?? ''),
            'user_agent' => $ua,
            'meta' => $meta,
        ];
    }

    /**
     * Complète le pays / la ville via un lookup IP, uniquement si encore manquant.
     *
     * @param array{ip?:string,country?:string,city?:string,user_agent?:string,meta?:array<string,string>} $visitor
     * @return array{ip:string,country:string,city:string,user_agent:string,meta:array<string,string>}
     */
    public static function enrichGeo(array $visitor): array
    {
        $ip = trim((string) ($visitor['ip'] ?? ''));
        $country = trim((string) ($visitor['country'] ?? ''));
        $meta = is_array($visitor['meta'] ?? null) ? $visitor['meta'] : [];
        if ($country !== '' || !self::isPublicIp($ip)) {
            $visitor['ip'] = $ip;
            $visitor['country'] = $country;
            $visitor['city'] = trim((string) ($visitor['city'] ?? ''));
            $visitor['user_agent'] = (string) ($visitor['user_agent'] ?? '');
            $visitor['meta'] = $meta;

            return $visitor;
        }

        $geo = self::lookupGeo($ip);
        if ($geo === []) {
            $visitor['ip'] = $ip;
            $visitor['country'] = '';
            $visitor['city'] = trim((string) ($visitor['city'] ?? ''));
            $visitor['user_agent'] = (string) ($visitor['user_agent'] ?? '');
            $visitor['meta'] = $meta;

            return $visitor;
        }

        $visitor['ip'] = $ip;
        $visitor['country'] = (string) ($geo['country'] ?? '');
        $visitor['city'] = trim((string) ($visitor['city'] ?? '')) !== ''
            ? (string) $visitor['city']
            : (string) ($geo['city'] ?? '');
        $visitor['user_agent'] = (string) ($visitor['user_agent'] ?? '');
        foreach (['country_code', 'region', 'isp', 'org', 'timezone'] as $key) {
            if (trim((string) ($meta[$key] ?? '')) === '' && trim((string) ($geo[$key] ?? '')) !== '') {
                $meta[$key] = (string) $geo[$key];
            }
        }
        $visitor['meta'] = $meta;

        return $visitor;
    }

    /** @return array<string, string> */
    public static function decodeMeta(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }
        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $key => $value) {
            if (is_string($key) && (is_string($value) || is_numeric($value))) {
                $out[$key] = trim((string) $value);
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $conversation
     * @return list<array{label:string,value:string}>
     */
    public static function displayRows(array $conversation): array
    {
        $meta = self::decodeMeta(isset($conversation['visitor_meta']) ? (string) $conversation['visitor_meta'] : null);
        $ip = trim((string) ($conversation['visitor_ip'] ?? ''));
        $country = trim((string) ($conversation['visitor_country'] ?? ''));
        $city = trim((string) ($conversation['visitor_city'] ?? ''));
        $code = strtoupper(trim($meta['country_code'] ?? ''));
        $place = self::formatPlace($country, $city, $code);
        $browser = trim($meta['browser'] ?? '');
        $os = trim($meta['os'] ?? '');
        $device = self::normalizeDevice($meta['device'] ?? '');
        if ($device === '') {
            $uaForDevice = trim((string) ($conversation['visitor_user_agent'] ?? ''));
            if ($uaForDevice !== '') {
                $device = self::normalizeDevice(self::parseUserAgent($uaForDevice)['device']);
            }
        }
        $nav = trim(implode(' · ', array_filter([$browser, $os], static fn ($v) => $v !== '')));
        $isp = trim((string) (($meta['isp'] ?? '') !== '' ? $meta['isp'] : ($meta['org'] ?? '')));

        $rows = [
            ['label' => 'Adresse IP', 'value' => $ip !== '' ? $ip : '—'],
            ['label' => 'Pays / ville', 'value' => $place !== '' ? $place : '—'],
            ['label' => 'Appareil', 'value' => $device !== '' ? $device : '—'],
            ['label' => 'Navigateur', 'value' => $nav !== '' ? $nav : '—'],
            ['label' => 'Langue', 'value' => trim($meta['language'] ?? '') !== '' ? $meta['language'] : '—'],
            ['label' => 'Fuseau horaire', 'value' => trim($meta['timezone'] ?? '') !== '' ? $meta['timezone'] : '—'],
            ['label' => 'FAI', 'value' => $isp !== '' ? $isp : '—'],
            ['label' => 'Page', 'value' => trim((string) ($conversation['page_url'] ?? '')) !== '' ? (string) $conversation['page_url'] : '—'],
        ];

        $referer = trim($meta['referer'] ?? '');
        if ($referer !== '') {
            $rows[] = ['label' => 'Provenance', 'value' => $referer];
        }

        $ua = trim((string) ($conversation['visitor_user_agent'] ?? ''));
        if ($ua !== '') {
            $rows[] = ['label' => 'User-Agent', 'value' => $ua];
        }

        return $rows;
    }

    public static function shortLabel(array $conversation): string
    {
        $meta = self::decodeMeta(isset($conversation['visitor_meta']) ? (string) $conversation['visitor_meta'] : null);
        $country = trim((string) ($conversation['visitor_country'] ?? ''));
        $code = strtoupper(trim($meta['country_code'] ?? ''));
        $ip = trim((string) ($conversation['visitor_ip'] ?? ''));
        $flag = self::flagEmoji($code);
        $place = $country !== '' ? $country : ($code !== '' && $code !== 'XX' ? $code : '');
        $device = self::normalizeDevice($meta['device'] ?? '');
        if ($device === '') {
            $ua = trim((string) ($conversation['visitor_user_agent'] ?? ''));
            if ($ua !== '') {
                $device = self::normalizeDevice(self::parseUserAgent($ua)['device']);
            }
        }
        $parts = array_filter([$device, $flag, $place, $ip], static fn ($v) => $v !== '');

        return $parts !== [] ? implode(' · ', $parts) : '—';
    }

    public static function flagEmoji(string $code): string
    {
        $code = strtoupper(trim($code));
        if (!preg_match('/^[A-Z]{2}$/', $code) || $code === 'XX') {
            return '';
        }

        return mb_chr(0x1F1E6 + ord($code[0]) - 65) . mb_chr(0x1F1E6 + ord($code[1]) - 65);
    }

    /** @param array<string, mixed> $clientHints */
    private static function sanitizeHints(array $clientHints): array
    {
        $timezone = trim((string) ($clientHints['tz'] ?? $clientHints['timezone'] ?? ''));
        if ($timezone !== '' && !preg_match('/^[A-Za-z0-9_+\-\/]{1,60}$/', $timezone)) {
            $timezone = '';
        }

        $language = trim((string) ($clientHints['lang'] ?? $clientHints['language'] ?? ''));
        if ($language !== '' && !preg_match('/^[A-Za-z]{2,8}(-[A-Za-z0-9]{2,8})?$/', $language)) {
            $language = '';
        }

        $device = strtolower(trim((string) ($clientHints['device'] ?? '')));
        if (!in_array($device, ['desktop', 'mobile', 'tablet', 'ordinateur', 'portable', 'tablette'], true)) {
            $device = '';
        }

        return [
            'timezone' => $timezone,
            'language' => $language,
            'device' => $device,
        ];
    }

    /** @return array{country?:string,country_code?:string,city?:string,region?:string,timezone?:string} */
    private static function geoFromHeaders(): array
    {
        $code = strtoupper(trim((string) (
            $_SERVER['HTTP_CF_IPCOUNTRY']
            ?? $_SERVER['HTTP_X_COUNTRY_CODE']
            ?? $_SERVER['HTTP_X_APPENGINE_COUNTRY']
            ?? $_SERVER['GEOIP_COUNTRY_CODE']
            ?? ''
        )));
        if ($code === '' || $code === 'XX' || !preg_match('/^[A-Z]{2}$/', $code)) {
            return [];
        }

        return [
            'country_code' => $code,
            'country' => self::countryName($code),
        ];
    }

    /** @return array<string, string> */
    private static function lookupGeo(string $ip): array
    {
        $fromWho = self::fetchJson('https://ipwho.is/' . rawurlencode($ip), 1.6);
        if (is_array($fromWho) && ($fromWho['success'] ?? false) === true) {
            $tz = $fromWho['timezone']['id'] ?? $fromWho['timezone'] ?? '';
            $connection = is_array($fromWho['connection'] ?? null) ? $fromWho['connection'] : [];

            return self::cleanGeo([
                'country' => (string) ($fromWho['country'] ?? ''),
                'country_code' => strtoupper((string) ($fromWho['country_code'] ?? '')),
                'city' => (string) ($fromWho['city'] ?? ''),
                'region' => (string) ($fromWho['region'] ?? ''),
                'isp' => (string) ($connection['isp'] ?? ''),
                'org' => (string) ($connection['org'] ?? ''),
                'timezone' => is_string($tz) ? $tz : '',
            ]);
        }

        $fromApi = self::fetchJson('http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,country,countryCode,regionName,city,isp,org,timezone', 1.4);
        if (is_array($fromApi) && ($fromApi['status'] ?? '') === 'success') {
            return self::cleanGeo([
                'country' => (string) ($fromApi['country'] ?? ''),
                'country_code' => strtoupper((string) ($fromApi['countryCode'] ?? '')),
                'city' => (string) ($fromApi['city'] ?? ''),
                'region' => (string) ($fromApi['regionName'] ?? ''),
                'isp' => (string) ($fromApi['isp'] ?? ''),
                'org' => (string) ($fromApi['org'] ?? ''),
                'timezone' => (string) ($fromApi['timezone'] ?? ''),
            ]);
        }

        return [];
    }

    /** @param array<string, string> $geo */
    private static function cleanGeo(array $geo): array
    {
        $out = [];
        foreach ($geo as $key => $value) {
            $value = trim($value);
            if ($value === '' || $value === 'XX') {
                continue;
            }
            $out[$key] = mb_substr($value, 0, 120);
        }
        $code = strtoupper((string) ($out['country_code'] ?? ''));
        if ($code !== '' && $code !== 'XX') {
            $localized = self::countryName($code);
            if ($localized !== '' && $localized !== $code) {
                $out['country'] = $localized;
            }
        }

        return $out;
    }

    /** @return array<string, mixed>|null */
    private static function fetchJson(string $url, float $timeout): ?array
    {
        if (!function_exists('curl_init')) {
            return null;
        }
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT => max(1, (int) ceil($timeout)),
            CURLOPT_USERAGENT => 'PHOTOCOM-Chat/1.0',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (!is_string($body) || $status < 200 || $status >= 300) {
            return null;
        }
        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : null;
    }

    /** @return array{browser:string,os:string,device:string} */
    private static function parseUserAgent(string $ua): array
    {
        $browser = 'Navigateur inconnu';
        if (preg_match('/Edg(?:e|A|iOS)?\/(\d+)/i', $ua, $m)) {
            $browser = 'Edge ' . $m[1];
        } elseif (preg_match('/OPR\/(\d+)/i', $ua, $m) || preg_match('/Opera\/(\d+)/i', $ua, $m)) {
            $browser = 'Opera ' . $m[1];
        } elseif (preg_match('/Firefox\/(\d+)/i', $ua, $m)) {
            $browser = 'Firefox ' . $m[1];
        } elseif (preg_match('/Chrome\/(\d+)/i', $ua, $m) && !preg_match('/Chromium/i', $ua)) {
            $browser = 'Chrome ' . $m[1];
        } elseif (preg_match('/Version\/(\d+).+Safari/i', $ua, $m)) {
            $browser = 'Safari ' . $m[1];
        } elseif (preg_match('/Safari\/(\d+)/i', $ua)) {
            $browser = 'Safari';
        } elseif (preg_match('/SamsungBrowser\/(\d+)/i', $ua, $m)) {
            $browser = 'Samsung Internet ' . $m[1];
        }

        $os = 'OS inconnu';
        if (preg_match('/iPhone|iPad|iPod/i', $ua)) {
            $os = 'iOS';
        } elseif (preg_match('/Android/i', $ua)) {
            $os = 'Android';
        } elseif (preg_match('/Windows NT 10/i', $ua)) {
            $os = 'Windows 10/11';
        } elseif (preg_match('/Windows NT/i', $ua)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X|Macintosh/i', $ua)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/i', $ua)) {
            $os = 'Linux';
        } elseif (preg_match('/CrOS/i', $ua)) {
            $os = 'ChromeOS';
        }

        $device = 'Ordinateur';
        if (preg_match('/Tablet|iPad/i', $ua) || (stripos($ua, 'Android') !== false && stripos($ua, 'Mobile') === false)) {
            $device = 'Tablette';
        } elseif (preg_match('/Mobi|iPhone|iPod|Android.+Mobile/i', $ua)) {
            $device = 'Portable';
        }

        return ['browser' => $browser, 'os' => $os, 'device' => $device];
    }

    public static function normalizeDevice(string $device): string
    {
        $key = mb_strtolower(trim($device));
        if (in_array($key, ['mobile', 'portable', 'phone', 'smartphone'], true)) {
            return 'Portable';
        }
        if (in_array($key, ['tablet', 'tablette', 'ipad'], true)) {
            return 'Tablette';
        }
        if (in_array($key, ['desktop', 'ordinateur', 'pc', 'computer'], true)) {
            return 'Ordinateur';
        }

        return '';
    }

    public static function deviceFromUserAgent(string $ua): string
    {
        if (trim($ua) === '') {
            return '';
        }

        return self::normalizeDevice(self::parseUserAgent($ua)['device']);
    }

    private static function primaryLanguage(string $header): string
    {
        $first = trim(explode(',', $header)[0] ?? '');
        $first = trim(explode(';', $first)[0] ?? '');

        return mb_substr($first, 0, 20);
    }

    private static function countryName(string $code): string
    {
        if (class_exists('Locale')) {
            $name = Locale::getDisplayRegion('-' . $code, 'fr');
            if (is_string($name) && $name !== '' && strtoupper($name) !== $code) {
                return $name;
            }
        }

        return $code;
    }

    private static function formatPlace(string $country, string $city, string $code): string
    {
        $flag = self::flagEmoji($code);
        $parts = [];
        if ($flag !== '') {
            $parts[] = $flag;
        }
        if ($country !== '') {
            $parts[] = $country;
        } elseif ($code !== '' && $code !== 'XX') {
            $parts[] = $code;
        }
        if ($city !== '') {
            $parts[] = $city;
        }

        return implode(' · ', $parts);
    }

    private static function isPublicIp(string $ip): bool
    {
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
