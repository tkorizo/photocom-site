<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireContent();
require_once dirname(__DIR__) . '/includes/image_field.php';

$id = (int) ($_GET['id'] ?? 0);
$article = ArticleRepository::find($id);

if (!$article) {
    Helpers::redirect('/admin/articles/index.php');
}

$pageTitle = 'Modifier article';
$currentPage = 'articles';
$errors = [];
$data = $article;
$isEdit = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        try {
            $image = FileUploader::processField('article_image', 'articles', $article['image'] ?? null);
            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'slug' => trim($_POST['slug'] ?? ''),
                'excerpt' => trim($_POST['excerpt'] ?? ''),
                'content' => trim($_POST['content'] ?? ''),
                'image' => $image ?? '',
                'meta_title' => trim($_POST['meta_title'] ?? ''),
                'meta_description' => trim($_POST['meta_description'] ?? ''),
                'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
                'focus_keyword' => trim($_POST['focus_keyword'] ?? ''),
                'canonical_url' => trim($_POST['canonical_url'] ?? ''),
                'is_published' => isset($_POST['is_published']) ? 1 : 0,
            ];
            if ($data['title'] === '') {
                $errors[] = 'Le titre est obligatoire.';
            }
            if (empty($errors)) {
                ArticleRepository::update($id, $data);
                AuditLog::record('article.update', 'article', $id, ['title' => $data['title']]);
                Helpers::flash('success', 'Article mis à jour.');
                Helpers::redirect('/admin/articles/index.php');
            }
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

require dirname(__DIR__) . '/includes/header.php';
require __DIR__ . '/_form.php';
require dirname(__DIR__) . '/includes/footer.php';
