<?php

declare(strict_types=1);

class CategoryForm
{
    public static function dataFromPost(?array $existing = null): array
    {
        return [
            'name' => trim($_POST['name'] ?? ''),
            'slug' => trim($_POST['slug'] ?? '') ?: null,
            'description' => trim($_POST['description'] ?? ''),
            'parent_id' => $_POST['parent_id'] !== '' ? (int) $_POST['parent_id'] : null,
            'tax_code' => trim($_POST['tax_code'] ?? ''),
            'display_type' => $_POST['display_type'] ?? 'default',
            'extra_description' => trim($_POST['extra_description'] ?? ''),
            'thumbnail' => FileUploader::processField('thumbnail', 'categories', $existing['thumbnail'] ?? null),
            'category_icon' => FileUploader::processField('category_icon', 'categories', $existing['category_icon'] ?? null),
            'large_category_icon' => FileUploader::processField('large_category_icon', 'categories', $existing['large_category_icon'] ?? null),
            'title_background' => FileUploader::processField('title_background', 'categories', $existing['title_background'] ?? null),
        ];
    }
}
