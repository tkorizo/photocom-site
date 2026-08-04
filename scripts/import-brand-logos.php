<?php

declare(strict_types=1);

/**
 * Télécharge / génère des logos monochromes haute résolution pour toutes les marques.
 *
 * Usage: php scripts/import-brand-logos.php
 */

require_once dirname(__DIR__) . '/bootstrap.php';

Database::initialize();

const LOGO_SIZE = 1200;
const GRAY = [75, 85, 99]; // #4B5563

$simpleIcons = static fn (string $slug): string => "https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/{$slug}.svg";
$wiki = static fn (string $file): string => 'https://commons.wikimedia.org/wiki/Special:FilePath/' . rawurlencode($file);

/** Sources prioritaires par slug de marque. */
$sources = [
    'canon' => [$wiki('Canon_wordmark.svg')],
    'sony' => [$simpleIcons('sony')],
    'nikon' => [$simpleIcons('nikon'), $wiki('Nikon_Logo.svg')],
    'dji' => [$simpleIcons('dji')],
    'fujifilm' => [$simpleIcons('fujifilm')],
    'kodak' => [$simpleIcons('kodak')],
    'insta360' => [$simpleIcons('insta360')],
    'rode' => [$wiki('RØDE_logo.svg')],
    // Citizen / PortKeys / Yelangu : wordmarks générés (pas de SVG public fiable en HR)
];

$outDir = ImageDownloader::brandsPath();
if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$tmpDir = sys_get_temp_dir() . '/photocom-brand-logos-' . getmypid();
if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0755, true);
}

$brands = BrandRepository::all();
$ok = 0;
$fail = 0;

foreach ($brands as $brand) {
    $slug = (string) $brand['slug'];
    $name = (string) $brand['name'];
    $id = (int) $brand['id'];
    echo "→ {$name} ({$slug}) … ";

    try {
        $pngPath = "{$tmpDir}/{$slug}.png";
        $rawPath = null;

        $candidates = $sources[$slug] ?? [];
        foreach ($candidates as $url) {
            $downloaded = downloadBinary($url);
            if ($downloaded === null) {
                continue;
            }
            $rawPath = "{$tmpDir}/{$slug}-src";
            file_put_contents($rawPath, $downloaded);
            if (isSvg($downloaded)) {
                $svgGray = recolorSvgToGray($downloaded);
                $svgFile = "{$tmpDir}/{$slug}.svg";
                file_put_contents($svgFile, $svgGray);
                if (rasterizeSvg($svgFile, $pngPath)) {
                    break;
                }
            } elseif (rasterFromRasterImage($rawPath, $pngPath)) {
                break;
            }
            $rawPath = null;
        }

        if (!is_file($pngPath)) {
            // Wordmark monochrome haute résolution
            $svgFile = "{$tmpDir}/{$slug}-wordmark.svg";
            file_put_contents($svgFile, buildWordmarkSvg($name));
            if (!rasterizeSvg($svgFile, $pngPath)) {
                throw new RuntimeException('Impossible de rasteriser le wordmark.');
            }
            $sourceLabel = 'wordmark';
        } else {
            $sourceLabel = 'remote';
        }

        $finalPng = monochromeAndTrim($pngPath);
        $filename = $slug . '.png';
        $dest = $outDir . '/' . $filename;
        if (!imagepng($finalPng, $dest, 6)) {
            throw new RuntimeException('Écriture PNG échouée.');
        }

        $publicPath = '/uploads/brands/' . $filename;
        if (!empty($brand['logo']) && $brand['logo'] !== $publicPath) {
            FileUploader::deleteFile($brand['logo']);
        }
        BrandRepository::updateLogo($id, $publicPath);

        $bytes = filesize($dest);
        echo "OK [{$sourceLabel}] ({$bytes} o)\n";
        $ok++;
    } catch (Throwable $e) {
        echo 'ERREUR: ' . $e->getMessage() . "\n";
        $fail++;
    }
}

// Nettoyage
array_map('unlink', glob($tmpDir . '/*') ?: []);
@rmdir($tmpDir);

echo "\nTerminé: {$ok} OK, {$fail} échec(s).\n";
exit($fail > 0 ? 1 : 0);

// ── helpers ──────────────────────────────────────────────────────────────

function downloadBinary(string $url): ?string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'PhotocomBrandImporter/1.0',
        CURLOPT_HTTPHEADER => ['Accept: image/*,*/*'],
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ctype = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    if ($body === false || $code >= 400 || strlen($body) < 80) {
        return null;
    }
    // Évite de traiter une page HTML d'erreur
    if (str_contains($ctype, 'text/html') && !str_starts_with(ltrim($body), '<svg') && !str_contains($body, '<svg')) {
        return null;
    }

    return $body;
}

function isSvg(string $data): bool
{
    $trim = ltrim($data);
    return str_starts_with($trim, '<svg')
        || str_starts_with($trim, '<?xml')
        || str_contains(substr($trim, 0, 500), '<svg');
}

function recolorSvgToGray(string $svg): string
{
    $gray = sprintf('#%02X%02X%02X', GRAY[0], GRAY[1], GRAY[2]);

    // Remplace fills / strokes courants
    $svg = preg_replace('/fill="(?!none)[^"]*"/i', 'fill="' . $gray . '"', $svg) ?? $svg;
    $svg = preg_replace("/fill='(?!none)[^']*'/i", "fill='{$gray}'", $svg) ?? $svg;
    $svg = preg_replace('/stroke="(?!none)[^"]*"/i', 'stroke="' . $gray . '"', $svg) ?? $svg;
    $svg = preg_replace('/fill:\s*(?!none)[^;"\']+/i', 'fill:' . $gray, $svg) ?? $svg;
    $svg = preg_replace('/stroke:\s*(?!none)[^;"\']+/i', 'stroke:' . $gray, $svg) ?? $svg;

    // Paths sans fill héritent souvent du noir — force un style racine
    if (!preg_match('/<svg[^>]*\sfill=/i', $svg)) {
        $svg = preg_replace('/<svg\b/i', '<svg fill="' . $gray . '"', $svg, 1) ?? $svg;
    }

    return $svg;
}

function buildWordmarkSvg(string $name): string
{
    $label = htmlspecialchars(mb_strtoupper($name), ENT_XML1);
    $len = max(mb_strlen($name), 1);
    $width = max(900, (int) (120 * $len * 0.72));
    $fontSize = $len > 10 ? 78 : ($len > 7 ? 96 : 120);
    $gray = sprintf('#%02X%02X%02X', GRAY[0], GRAY[1], GRAY[2]);

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="280" viewBox="0 0 {$width} 280">
  <text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle"
        font-family="Helvetica Neue, Arial Black, Arial, sans-serif"
        font-weight="700" font-size="{$fontSize}" letter-spacing="0.08em"
        fill="{$gray}">{$label}</text>
</svg>
SVG;
}

function rasterizeSvg(string $svgPath, string $outPng): bool
{
    // 1) resvg via npx si dispo
    if (rasterizeWithResvg($svgPath, $outPng)) {
        return is_file($outPng);
    }

    // 2) qlmanage (macOS)
    $outDir = dirname($outPng);
    $cmd = sprintf(
        'qlmanage -t -s %d -o %s %s 2>/dev/null',
        LOGO_SIZE,
        escapeshellarg($outDir),
        escapeshellarg($svgPath)
    );
    exec($cmd, $output, $code);
    $qlOut = $outDir . '/' . basename($svgPath) . '.png';
    if (is_file($qlOut)) {
        rename($qlOut, $outPng);
        return true;
    }

    return false;
}

function rasterizeWithResvg(string $svgPath, string $outPng): bool
{
    static $resvgReady = null;
    static $resvgDir = null;

    if ($resvgReady === false) {
        return false;
    }

    if ($resvgReady === null) {
        $resvgDir = sys_get_temp_dir() . '/photocom-resvg';
        if (!is_dir($resvgDir)) {
            mkdir($resvgDir, 0755, true);
        }
        $pkg = $resvgDir . '/node_modules/@resvg/resvg-js';
        if (!is_dir($pkg)) {
            $install = sprintf(
                'cd %s && npm install --silent --no-fund --no-audit @resvg/resvg-js@2.6.2 2>/dev/null',
                escapeshellarg($resvgDir)
            );
            exec($install, $o, $c);
            $resvgReady = is_dir($pkg);
        } else {
            $resvgReady = true;
        }
        if (!$resvgReady) {
            return false;
        }
    }

    $script = <<<'JS'
const { Resvg } = require('@resvg/resvg-js');
const fs = require('fs');
const svg = fs.readFileSync(process.argv[2]);
const resvg = new Resvg(svg, { fitTo: { mode: 'width', value: Number(process.argv[4] || 1200) } });
const png = resvg.render().asPng();
fs.writeFileSync(process.argv[3], png);
JS;
    $jsFile = $resvgDir . '/render.js';
    file_put_contents($jsFile, $script);

    $cmd = sprintf(
        'cd %s && node render.js %s %s %d 2>/dev/null',
        escapeshellarg($resvgDir),
        escapeshellarg($svgPath),
        escapeshellarg($outPng),
        LOGO_SIZE
    );
    exec($cmd, $output, $code);

    return $code === 0 && is_file($outPng);
}

function rasterFromRasterImage(string $srcPath, string $outPng): bool
{
    $info = @getimagesize($srcPath);
    if (!$info) {
        return false;
    }
    // Favicons trop petits : on refuse (< 96px) pour forcer wordmark
    if (min($info[0], $info[1]) < 96) {
        return false;
    }

    $img = match ($info[2]) {
        IMAGETYPE_PNG => @imagecreatefrompng($srcPath),
        IMAGETYPE_JPEG => @imagecreatefromjpeg($srcPath),
        IMAGETYPE_GIF => @imagecreatefromgif($srcPath),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcPath) : false,
        default => false,
    };
    if (!$img) {
        return false;
    }

    // Convertir en truecolor pour éviter les palettes saturées
    $w = imagesx($img);
    $h = imagesy($img);
    $true = imagecreatetruecolor($w, $h);
    imagealphablending($true, false);
    imagesavealpha($true, true);
    $transparent = imagecolorallocatealpha($true, 0, 0, 0, 127);
    imagefilledrectangle($true, 0, 0, $w, $h, $transparent);
    imagealphablending($true, true);
    imagecopy($true, $img, 0, 0, 0, 0, $w, $h);
    imagealphablending($true, false);
    imagesavealpha($true, true);
    $ok = imagepng($true, $outPng, 6);

    return $ok;
}

/**
 * @return \GdImage
 */
function monochromeAndTrim(string $pngPath)
{
    $loaded = imagecreatefrompng($pngPath);
    if (!$loaded) {
        throw new RuntimeException('PNG source invalide.');
    }
    $w = imagesx($loaded);
    $h = imagesy($loaded);

    $src = imagecreatetruecolor($w, $h);
    imagealphablending($src, false);
    imagesavealpha($src, true);
    $transparent = imagecolorallocatealpha($src, 0, 0, 0, 127);
    imagefilledrectangle($src, 0, 0, $w, $h, $transparent);
    imagealphablending($src, true);
    imagecopy($src, $loaded, 0, 0, 0, 0, $w, $h);
    imagealphablending($src, false);
    imagesavealpha($src, true);

    /** @var array<int, int> $colorCache */
    $colorCache = [];
    $alloc = static function (int $a) use ($src, &$colorCache): int {
        if (!isset($colorCache[$a])) {
            $colorCache[$a] = imagecolorallocatealpha($src, GRAY[0], GRAY[1], GRAY[2], $a);
        }
        return $colorCache[$a];
    };
    $colorCache[127] = $transparent;

    // Silhouette monochrome (#4B5563) : fond clair → transparent, forme → gris
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgba = imagecolorat($src, $x, $y);
            $a = ($rgba & 0x7F000000) >> 24; // 0 opaque … 127 transparent
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;
            $lum = (int) round(0.299 * $r + 0.587 * $g + 0.114 * $b);

            // Fond blanc / très clair → transparent
            if ($a < 120 && $lum >= 235) {
                imagesetpixel($src, $x, $y, $transparent);
                continue;
            }

            if ($a >= 125) {
                continue; // déjà transparent
            }

            // Intensité de gris proportionnelle à l'obscurité du pixel
            $darkness = (255 - $lum) / 255;
            $opacity = (127 - $a) / 127; // 1 = opaque
            $strength = max(0.0, min(1.0, $darkness * $opacity * 1.15));
            $na = (int) round(127 * (1 - $strength));
            imagesetpixel($src, $x, $y, $alloc($na));
        }
    }

    $trimmed = trimTransparent($src);

    // Pad to target canvas with transparent margin
    $tw = imagesx($trimmed);
    $th = imagesy($trimmed);
    $maxDim = max($tw, $th);
    $canvasSize = max(LOGO_SIZE, $maxDim);
    $pad = (int) round($canvasSize * 0.08);
    $canvas = imagecreatetruecolor($canvasSize, $canvasSize);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $canvasTransparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefilledrectangle($canvas, 0, 0, $canvasSize, $canvasSize, $canvasTransparent);

    $scale = min(
        ($canvasSize - 2 * $pad) / max($tw, 1),
        ($canvasSize - 2 * $pad) / max($th, 1)
    );
    $nw = max(1, (int) round($tw * $scale));
    $nh = max(1, (int) round($th * $scale));
    $dx = (int) (($canvasSize - $nw) / 2);
    $dy = (int) (($canvasSize - $nh) / 2);

    imagealphablending($canvas, true);
    imagecopyresampled($canvas, $trimmed, $dx, $dy, 0, 0, $nw, $nh, $tw, $th);

    // Remettre blending off pour sauvegarde alpha
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);

    return $canvas;
}

/**
 * @param \GdImage $img
 * @return \GdImage
 */
function trimTransparent($img)
{
    $w = imagesx($img);
    $h = imagesy($img);
    $minX = $w;
    $minY = $h;
    $maxX = -1;
    $maxY = -1;

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgba = imagecolorat($img, $x, $y);
            $a = ($rgba & 0x7F000000) >> 24;
            if ($a < 120) {
                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }
    }

    if ($maxX < 0) {
        // Tout transparent — renvoyer une copie
        $copy = imagecreatetruecolor($w, $h);
        imagealphablending($copy, false);
        imagesavealpha($copy, true);
        imagecopy($copy, $img, 0, 0, 0, 0, $w, $h);
        return $copy;
    }

    $nw = $maxX - $minX + 1;
    $nh = $maxY - $minY + 1;
    $out = imagecreatetruecolor($nw, $nh);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    $transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
    imagefilledrectangle($out, 0, 0, $nw, $nh, $transparent);
    imagecopy($out, $img, 0, 0, $minX, $minY, $nw, $nh);

    return $out;
}
