<?php

declare(strict_types=1);

/**
 * Génère le sitemap XML (Search Console) et le contenu robots.txt.
 * Inclut les pages, catégories, images produits et vidéos (hero + YouTube).
 */
class SitemapBuilder
{
    public static function baseUrl(): string
    {
        $site = SettingRepository::site();
        $config = Helpers::config();
        $url = rtrim((string) ($site['site_url'] ?: $config['url']), '/');

        return $url !== '' ? $url : '';
    }

    /**
     * @return list<array{
     *   loc:string,
     *   lastmod?:string,
     *   changefreq?:string,
     *   priority?:string,
     *   images?: list<array{loc:string,title?:string}>,
     *   videos?: list<array{thumbnail_loc:string,title:string,description:string,content_loc?:string,player_loc?:string}>
     * }>
     */
    public static function urls(): array
    {
        $base = self::baseUrl();
        $urls = [];

        $add = static function (
            string $path,
            ?string $lastmod = null,
            string $changefreq = 'weekly',
            string $priority = '0.7',
            array $images = [],
            array $videos = []
        ) use (&$urls, $base): void {
            $loc = str_starts_with($path, 'http') ? $path : $base . $path;
            $entry = [
                'loc' => $loc,
                'changefreq' => $changefreq,
                'priority' => $priority,
            ];
            if ($lastmod) {
                $entry['lastmod'] = substr($lastmod, 0, 10);
            }
            if ($images !== []) {
                $entry['images'] = $images;
            }
            if ($videos !== []) {
                $entry['videos'] = $videos;
            }
            $urls[] = $entry;
        };

        $home = [];
        try {
            $home = SettingRepository::home();
        } catch (Throwable) {
            $home = [];
        }

        $homeImages = [];
        $homeVideos = [];
        if (($home['hero_enabled'] ?? '1') === '1') {
            foreach (['hero_image_url', 'hero_image_url_mobile', 'hero_video_poster', 'site_logo'] as $key) {
                $img = self::imageEntry((string) ($home[$key] ?? ''), $base, 'PHOTOCOM');
                if ($img !== null) {
                    $homeImages[] = $img;
                }
            }
            $poster = trim((string) ($home['hero_video_poster'] ?? ''));
            if ($poster === '') {
                $poster = trim((string) ($home['hero_image_url'] ?? ''));
            }
            $site = SettingRepository::site();
            $heroTitle = trim((string) ($home['hero_title'] ?? '')) ?: 'PHOTOCOM';
            $heroDesc = trim((string) ($site['seo_description'] ?? $home['hero_subtitle'] ?? $site['site_tagline'] ?? 'PHOTOCOM'));
            foreach (['hero_video_url', 'hero_video_url_mobile'] as $key) {
                $vid = self::fileVideoEntry((string) ($home[$key] ?? ''), $poster, $heroTitle, $heroDesc, $base);
                if ($vid !== null) {
                    $homeVideos[] = $vid;
                }
            }
        }
        if (($home['conseils_enabled'] ?? '1') === '1') {
            for ($i = 1; $i <= 2; $i++) {
                $yt = self::youtubeVideoEntry(
                    (string) ($home['home_blog_video_' . $i] ?? ''),
                    (string) ($home['home_blog_title_' . $i] ?? 'Vidéo PHOTOCOM'),
                    (string) ($home['home_blog_excerpt_' . $i] ?? '')
                );
                if ($yt !== null) {
                    $homeVideos[] = $yt;
                }
            }
        }
        $homeImages = self::uniqueImages($homeImages);
        $homeVideos = self::uniqueVideos($homeVideos);

        $add('/', date('Y-m-d'), 'daily', '1.0', $homeImages, $homeVideos);
        $add('/catalogue.php', date('Y-m-d'), 'daily', '0.9');
        $add('/a-propos', null, 'monthly', '0.7');
        $add('/contact', null, 'monthly', '0.7');
        $add('/conseils', date('Y-m-d'), 'weekly', '0.8');

        try {
            foreach (CategoryRepository::allFlat() as $cat) {
                if (empty($cat['slug'])) {
                    continue;
                }
                $catImages = [];
                $hero = CategoryRepository::heroImage($cat);
                $img = self::imageEntry((string) ($hero ?? ''), $base, (string) ($cat['name'] ?? ''));
                if ($img !== null) {
                    $catImages[] = $img;
                }
                $add(
                    '/catalogue.php?cat=' . rawurlencode((string) $cat['slug']),
                    (string) ($cat['updated_at'] ?? $cat['created_at'] ?? ''),
                    'weekly',
                    '0.8',
                    $catImages
                );
            }
        } catch (Throwable) {
        }

        try {
            foreach (ArticleRepository::all() as $article) {
                if (!(int) ($article['is_published'] ?? 0) || empty($article['slug'])) {
                    continue;
                }
                $artImages = [];
                $img = self::imageEntry((string) ($article['image'] ?? ''), $base, (string) ($article['title'] ?? ''));
                if ($img !== null) {
                    $artImages[] = $img;
                }
                $add(
                    '/article/' . rawurlencode((string) $article['slug']),
                    (string) ($article['updated_at'] ?? $article['created_at'] ?? ''),
                    'monthly',
                    '0.7',
                    $artImages
                );
            }
        } catch (Throwable) {
        }

        try {
            $page = 1;
            do {
                $result = ProductRepository::paginatePublic($page, 200);
                foreach ($result['data'] as $product) {
                    if (empty($product['slug'])) {
                        continue;
                    }
                    $add(
                        '/produit/' . rawurlencode((string) $product['slug']),
                        (string) ($product['updated_at'] ?? $product['created_at'] ?? ''),
                        'weekly',
                        '0.8',
                        self::productImages($product, $base)
                    );
                }
                $totalPages = (int) ($result['total_pages'] ?? 1);
                $page++;
            } while ($page <= $totalPages);
        } catch (Throwable) {
        }

        try {
            foreach (PageRepository::published() as $page) {
                if (empty($page['slug'])) {
                    continue;
                }
                $add(
                    '/page/' . rawurlencode((string) $page['slug']),
                    (string) ($page['updated_at'] ?? ''),
                    'yearly',
                    '0.4'
                );
            }
        } catch (Throwable) {
        }

        return $urls;
    }

    public static function toXml(): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
            . ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"'
            . ' xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">',
        ];

        $esc = static fn (string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        foreach (self::urls() as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>' . $esc($url['loc']) . '</loc>';
            if (!empty($url['lastmod'])) {
                $lines[] = '    <lastmod>' . $esc($url['lastmod']) . '</lastmod>';
            }
            if (!empty($url['changefreq'])) {
                $lines[] = '    <changefreq>' . $esc($url['changefreq']) . '</changefreq>';
            }
            if (!empty($url['priority'])) {
                $lines[] = '    <priority>' . $esc($url['priority']) . '</priority>';
            }
            foreach ($url['images'] ?? [] as $image) {
                $lines[] = '    <image:image>';
                $lines[] = '      <image:loc>' . $esc($image['loc']) . '</image:loc>';
                if (!empty($image['title'])) {
                    $lines[] = '      <image:title>' . $esc((string) $image['title']) . '</image:title>';
                }
                $lines[] = '    </image:image>';
            }
            foreach ($url['videos'] ?? [] as $video) {
                $lines[] = '    <video:video>';
                $lines[] = '      <video:thumbnail_loc>' . $esc($video['thumbnail_loc']) . '</video:thumbnail_loc>';
                $lines[] = '      <video:title>' . $esc($video['title']) . '</video:title>';
                $lines[] = '      <video:description>' . $esc($video['description']) . '</video:description>';
                if (!empty($video['content_loc'])) {
                    $lines[] = '      <video:content_loc>' . $esc((string) $video['content_loc']) . '</video:content_loc>';
                }
                if (!empty($video['player_loc'])) {
                    $lines[] = '      <video:player_loc>' . $esc((string) $video['player_loc']) . '</video:player_loc>';
                }
                $lines[] = '    </video:video>';
            }
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines) . "\n";
    }

    public static function robotsTxt(): string
    {
        $site = SettingRepository::site();
        $base = self::baseUrl();
        $extra = trim((string) ($site['seo_robots_txt_extra'] ?? ''));

        $lines = [
            'User-agent: *',
            'Allow: /',
            'Allow: /uploads/',
            'Disallow: /admin/',
            'Disallow: /admin',
            'Disallow: /lib/',
            'Disallow: /database/',
            'Disallow: /storage/',
            'Disallow: /scripts/',
            'Disallow: /config/',
            'Disallow: /chat-api.php',
            'Disallow: /api/',
            'Disallow: /panier',
            'Disallow: /compte',
            'Disallow: /mon-compte',
            'Disallow: /rapport/',
            'Disallow: /rapport',
            '',
        ];

        if ($base !== '') {
            $lines[] = 'Sitemap: ' . $base . '/sitemap.xml';
            $lines[] = '';
        }

        if ($extra !== '') {
            $lines[] = $extra;
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /** @return list<array{loc:string,title?:string}> */
    private static function productImages(array $product, string $base): array
    {
        $seen = [];
        $out = [];
        $name = (string) ($product['name'] ?? '');
        $candidates = [];
        if (!empty($product['image'])) {
            $candidates[] = (string) $product['image'];
        }
        foreach (ProductRepository::getSecondaryImages($product) as $img) {
            if (is_string($img) && trim($img) !== '') {
                $candidates[] = $img;
            }
        }
        $json = json_decode((string) ($product['images_json'] ?? '[]'), true);
        if (is_array($json)) {
            foreach ($json as $img) {
                if (is_string($img) && trim($img) !== '') {
                    $candidates[] = $img;
                }
            }
        }
        foreach ($candidates as $src) {
            $entry = self::imageEntry($src, $base, $name);
            if ($entry === null || isset($seen[$entry['loc']])) {
                continue;
            }
            $seen[$entry['loc']] = true;
            $out[] = $entry;
            if (count($out) >= 10) {
                break;
            }
        }

        return $out;
    }

    /** @return array{loc:string,title?:string}|null */
    private static function imageEntry(string $src, string $base, string $title): ?array
    {
        $abs = Helpers::absoluteUrl($src, $base);
        if ($abs === '' || !preg_match('#^https?://#i', $abs)) {
            return null;
        }
        $entry = ['loc' => $abs];
        $title = trim($title);
        if ($title !== '') {
            $entry['title'] = mb_substr($title, 0, 200);
        }

        return $entry;
    }

    /**
     * @return array{thumbnail_loc:string,title:string,description:string,content_loc?:string,player_loc?:string}|null
     */
    private static function fileVideoEntry(string $src, string $poster, string $title, string $description, string $base): ?array
    {
        $abs = Helpers::absoluteUrl($src, $base);
        if ($abs === '' || !preg_match('#\.(mp4|webm|mov)(\?|$)#i', $abs)) {
            return null;
        }
        $thumb = Helpers::absoluteUrl($poster, $base);
        if ($thumb === '') {
            $thumb = Helpers::absoluteUrl('/assets/images/logo-photocom.png', $base);
        }
        $title = trim($title) !== '' ? trim($title) : 'Vidéo PHOTOCOM';
        $description = trim($description) !== '' ? trim($description) : $title;

        return [
            'thumbnail_loc' => $thumb,
            'title' => mb_substr($title, 0, 100),
            'description' => mb_substr($description, 0, 2048),
            'content_loc' => $abs,
        ];
    }

    /**
     * @return array{thumbnail_loc:string,title:string,description:string,player_loc:string}|null
     */
    private static function youtubeVideoEntry(string $url, string $title, string $description): ?array
    {
        $id = self::youtubeId($url);
        if ($id === null) {
            return null;
        }
        $title = trim($title) !== '' ? trim($title) : 'Vidéo PHOTOCOM';
        $description = trim($description) !== '' ? trim($description) : $title;

        return [
            'thumbnail_loc' => 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg',
            'title' => mb_substr($title, 0, 100),
            'description' => mb_substr($description, 0, 2048),
            'player_loc' => 'https://www.youtube.com/embed/' . $id,
        ];
    }

    public static function youtubeId(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (preg_match('#(?:youtube\.com/(?:embed/|watch\?v=|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    /** @param list<array{loc:string,title?:string}> $images */
    private static function uniqueImages(array $images): array
    {
        $seen = [];
        $out = [];
        foreach ($images as $image) {
            $loc = $image['loc'] ?? '';
            if ($loc === '' || isset($seen[$loc])) {
                continue;
            }
            $seen[$loc] = true;
            $out[] = $image;
        }

        return $out;
    }

    /** @param list<array<string,string>> $videos */
    private static function uniqueVideos(array $videos): array
    {
        $seen = [];
        $out = [];
        foreach ($videos as $video) {
            $key = ($video['content_loc'] ?? '') . '|' . ($video['player_loc'] ?? '');
            if ($key === '|' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $video;
        }

        return $out;
    }
}
