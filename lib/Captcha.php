<?php

declare(strict_types=1);

/**
 * Captcha session pour la connexion admin (image PNG).
 */
class Captcha
{
    private const SESSION_CODE = 'admin_captcha_code';
    private const SESSION_EXPIRES = 'admin_captcha_exp';
    private const TTL = 600;
    private const ALPHABET = '2346789ABCDEFGHJKMNPQRTUVWXY';

    public static function issue(): void
    {
        Auth::startSession();
        $alphabet = self::ALPHABET;
        $max = strlen($alphabet) - 1;
        $code = '';
        for ($i = 0; $i < 5; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }
        $_SESSION[self::SESSION_CODE] = $code;
        $_SESSION[self::SESSION_EXPIRES] = time() + self::TTL;
    }

    public static function verify(?string $input): bool
    {
        Auth::startSession();
        $expected = (string) ($_SESSION[self::SESSION_CODE] ?? '');
        $expires = (int) ($_SESSION[self::SESSION_EXPIRES] ?? 0);
        unset($_SESSION[self::SESSION_CODE], $_SESSION[self::SESSION_EXPIRES]);

        $input = strtoupper(preg_replace('/\s+/', '', trim((string) $input)) ?? '');
        if ($expected === '' || $expires < time() || $input === '') {
            return false;
        }

        return hash_equals($expected, $input);
    }

    public static function isHoneypotFilled(): bool
    {
        return trim((string) ($_POST['company_url'] ?? '')) !== '';
    }

    public static function imageUrl(): string
    {
        return '/admin/captcha.php?t=' . bin2hex(random_bytes(4));
    }

    public static function outputImage(): void
    {
        Auth::startSession();
        $code = (string) ($_SESSION[self::SESSION_CODE] ?? '');
        if ($code === '' || (int) ($_SESSION[self::SESSION_EXPIRES] ?? 0) < time()) {
            self::issue();
            $code = (string) ($_SESSION[self::SESSION_CODE] ?? '');
        }

        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('X-Robots-Tag: noindex, nofollow');

        if (!function_exists('imagecreatetruecolor') || $code === '') {
            echo base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
            );
            return;
        }

        $width = 200;
        $height = 58;
        $im = imagecreatetruecolor($width, $height);
        if ($im === false) {
            return;
        }

        $bg = imagecolorallocate($im, 12, 12, 12);
        $fg = imagecolorallocate($im, 242, 242, 242);
        $noise = imagecolorallocate($im, 55, 55, 55);
        $accent = imagecolorallocate($im, 232, 31, 5);
        imagefilledrectangle($im, 0, 0, $width, $height, $bg);

        for ($i = 0; $i < 14; $i++) {
            imageline(
                $im,
                random_int(0, $width),
                random_int(0, $height),
                random_int(0, $width),
                random_int(0, $height),
                $noise
            );
        }

        $len = strlen($code);
        for ($i = 0; $i < $len; $i++) {
            $glyph = imagecreatetruecolor(20, 28);
            if ($glyph === false) {
                continue;
            }
            imagefilledrectangle($glyph, 0, 0, 20, 28, $bg);
            $color = $i % 2 === 0 ? $fg : $accent;
            imagestring($glyph, 5, 4, 6, $code[$i], $color);
            $destW = random_int(28, 34);
            $destH = random_int(34, 42);
            $dx = 14 + ($i * 36);
            $dy = (int) (($height - $destH) / 2) + random_int(-4, 4);
            imagecopyresized($im, $glyph, $dx, $dy, 0, 0, $destW, $destH, 20, 28);
            imagedestroy($glyph);
        }

        imagepng($im);
        imagedestroy($im);
    }
}
