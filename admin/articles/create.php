<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();

$pageTitle = 'Nouvel article';
$currentPage = 'articles';
$errors = [];
$data = ['title' => '', 'slug' => '', 'excerpt' => '', 'content' => '', 'image' => '', 'is_published' => 0];
$isEdit = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'excerpt' => trim($_POST['excerpt'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'image' => trim($_POST['image'] ?? ''),
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
        ];
        if ($data['title'] === '') {
            $errors[] = 'Le titre est obligatoire.';
        }
        if (empty($errors)) {
            ArticleRepository::create($data);
            Helpers::flash('success', 'Article créé.');
            Helpers::redirect('/admin/articles/index.php');
        }
    }
}

require dirname(__DIR__) . '/includes/header.php';
require __DIR__ . '/_form.php';
require dirname(__DIR__) . '/includes/footer.php';
