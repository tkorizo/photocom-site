<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/includes/init.php';

$slug = trim($_GET['slug'] ?? '');
$page = $slug !== '' ? PageRepository::findBySlug($slug) : null;

if (!$page || !(int) ($page['is_published'] ?? 0)) {
    http_response_code(404);
    $pageTitle = 'Page introuvable';
    $pageContent = '<p>Cette page n\'existe pas ou n\'est plus disponible.</p>';
} else {
    $pageTitle = $page['title'];
    $pageContent = $page['content'] ?: '<p>Contenu en cours de rédaction.</p>';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::e($pageTitle) ?> — <?= Helpers::e($siteName) ?></title>
    <link rel="stylesheet" href="/assets/css/public.css">
    <link rel="stylesheet" href="/assets/css/chat.css">
</head>
<body>
    <?php require __DIR__ . '/includes/site-header.php'; ?>

    <main class="page-content">
        <div class="container">
            <header class="page-content-header">
                <h1><?= Helpers::e($pageTitle) ?></h1>
            </header>
            <div class="page-content-body">
                <?= $pageContent ?>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/includes/site-footer.php'; ?>
    <?php require __DIR__ . '/includes/site-chat.php'; ?>
    <script src="/assets/js/mega-menu.js"></script>
    <script src="/assets/js/chat.js"></script>
</body>
</html>
