<?php

declare(strict_types=1);

/**
 * Génère le sitemap XML (Search Console) et le contenu robots.txt.
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

    /** @return list<array{loc:string,lastmod?:string,changefreq?:string,priority?:string}> */
    public static function urls(): array
    {
        $base = self::baseUrl();
        $urls = [];

        $add = static function (string $path, ?string $lastmod = null, string $changefreq = 'weekly', string $priority = '0.7') use (&$urls, $base): void {
            $loc = str_starts_with($path, 'http') ? $path : $base . $path;
            $entry = [
                'loc' => $loc,
                'changefreq' => $changefreq,
                'priority' => $priority,
            ];
            if ($lastmod) {
                $entry['lastmod'] = substr($lastmod, 0, 10);
            }
            $urls[] = $entry;
        };

        $add('/', date('Y-m-d'), 'daily', '1.0');
        $add('/catalogue.php', date('Y-m-d'), 'daily', '0.9');
        $add('/a-propos', null, 'monthly', '0.6');
        $add('/contact', null, 'monthly', '0.7');
        $add('/conseils', date('Y-m-d'), 'weekly', '0.8');

        try {
            foreach (CategoryRepository::allFlat() as $cat) {
                if (empty($cat['slug'])) {
                    continue;
                }
                $add('/catalogue.php?cat=' . rawurlencode((string) $cat['slug']), null, 'weekly', '0.7');
            }
        } catch (Throwable) {
        }

        try {
            foreach (ArticleRepository::all() as $article) {
                if (!(int) ($article['is_published'] ?? 0) || empty($article['slug'])) {
                    continue;
                }
                $add(
                    '/article/' . rawurlencode((string) $article['slug']),
                    (string) ($article['updated_at'] ?? $article['created_at'] ?? ''),
                    'monthly',
                    '0.7'
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
                        '0.8'
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
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach (self::urls() as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';
            if (!empty($url['lastmod'])) {
                $lines[] = '    <lastmod>' . htmlspecialchars($url['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</lastmod>';
            }
            if (!empty($url['changefreq'])) {
                $lines[] = '    <changefreq>' . htmlspecialchars($url['changefreq'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</changefreq>';
            }
            if (!empty($url['priority'])) {
                $lines[] = '    <priority>' . htmlspecialchars($url['priority'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</priority>';
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
            'Disallow: /admin/',
            'Disallow: /lib/',
            'Disallow: /database/',
            'Disallow: /storage/',
            'Disallow: /scripts/',
            'Disallow: /config/',
            'Disallow: /chat-api.php',
            'Disallow: /api/',
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
}
