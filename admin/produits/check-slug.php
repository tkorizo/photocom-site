<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireCatalog();

header('Content-Type: application/json; charset=utf-8');

$slug = trim((string) ($_GET['slug'] ?? ''));
$excludeId = isset($_GET['exclude_id']) ? (int) $_GET['exclude_id'] : null;
$name = trim((string) ($_GET['name'] ?? ''));

if ($slug === '' && $name !== '') {
    $slug = Helpers::slugify($name);
}

if ($slug === '') {
    echo json_encode(['ok' => true, 'slug' => '', 'exists' => false]);
    exit;
}

$normalized = Helpers::slugify($slug);
$exists = Helpers::slugExists('products', $normalized, $excludeId > 0 ? $excludeId : null);

echo json_encode([
    'ok' => true,
    'slug' => $normalized,
    'exists' => $exists,
    'message' => $exists
        ? 'Ce slug existe déjà. Modifiez le nom ou le slug.'
        : 'Slug disponible.',
], JSON_UNESCAPED_UNICODE);
