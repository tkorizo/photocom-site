<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$root = __DIR__;

// Fichiers statiques : assets
if (str_starts_with($uri, '/assets/')) {
    $file = $root . '/public' . $uri;
    if (is_file($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'ico' => 'image/x-icon',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
        ];
        $mtime = filemtime($file) ?: time();
        $etag = '"' . dechex($mtime) . '-' . dechex((int) filesize($file)) . '"';
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=604800');
        header('ETag: ' . $etag);
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim((string) $_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
            http_response_code(304);
            return true;
        }
        readfile($file);
        return true;
    }
}

// Images / médias uploadés
if (preg_match('#^/uploads/(products|categories|home|brands|articles)/#', $uri)) {
    $file = $root . $uri;
    if (is_file($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $types = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
        ];
        $mtime = filemtime($file) ?: time();
        $etag = '"' . dechex($mtime) . '-' . dechex((int) filesize($file)) . '"';
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=2592000');
        header('ETag: ' . $etag);
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim((string) $_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
            http_response_code(304);
            return true;
        }
        readfile($file);
        return true;
    }
}

// Admin
if (str_starts_with($uri, '/admin')) {
    $adminPath = $uri === '/admin' ? '/admin/index.php' : $uri;
    if (!str_ends_with($adminPath, '.php')) {
        $adminPath = rtrim($adminPath, '/') . '/index.php';
    }
    $file = $root . $adminPath;
    if (is_file($file)) {
        require $file;
        return true;
    }
}

// Catalogue
if ($uri === '/catalogue.php' || $uri === '/catalogue') {
    require $root . '/public/catalogue.php';
    return true;
}

// Qui sommes-nous
if ($uri === '/a-propos' || $uri === '/qui-sommes-nous') {
    require $root . '/public/a-propos.php';
    return true;
}

// Contact
if ($uri === '/contact') {
    require $root . '/public/contact.php';
    return true;
}

// Blog conseils
if ($uri === '/conseils' || $uri === '/blog') {
    require $root . '/public/conseils.php';
    return true;
}

if (preg_match('#^/article/([a-z0-9-]+)$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    require $root . '/public/article.php';
    return true;
}

// Fiche produit
if (preg_match('#^/produit/([a-z0-9-]+)$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    require $root . '/public/produit.php';
    return true;
}

// Compte client
if ($uri === '/compte' || $uri === '/compte.php') {
    require $root . '/public/compte.php';
    return true;
}

if ($uri === '/mon-compte' || $uri === '/mon-compte.php') {
    require $root . '/public/mon-compte.php';
    return true;
}

if ($uri === '/compte/deconnexion' || $uri === '/compte-logout.php') {
    require $root . '/public/compte-logout.php';
    return true;
}

// Panier
if ($uri === '/panier' || $uri === '/panier.php') {
    require $root . '/public/panier.php';
    return true;
}

// Chat API
if ($uri === '/chat-api.php' || $uri === '/api/chat') {
    require $root . '/public/chat-api.php';
    return true;
}

// Autosuggest recherche
if ($uri === '/api/search-suggest' || $uri === '/api/search-suggest.php') {
    require $root . '/public/api/search-suggest.php';
    return true;
}

// Panier client connecté
if ($uri === '/api/cart' || $uri === '/api/cart.php') {
    require $root . '/public/api/cart.php';
    return true;
}

// Sitemap & robots (Search Console)
if ($uri === '/sitemap.xml' || $uri === '/sitemap.php') {
    require $root . '/public/sitemap.php';
    return true;
}
if ($uri === '/robots.txt' || $uri === '/robots.php') {
    require $root . '/public/robots.php';
    return true;
}

// Pages légales / informatives
if (preg_match('#^/page/([a-z0-9-]+)$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    require $root . '/public/page.php';
    return true;
}

// Page d'accueil
if ($uri === '/' || $uri === '/index.php') {
    require $root . '/public/index.php';
    return true;
}

http_response_code(404);
echo '404 - Page non trouvée';
return true;
