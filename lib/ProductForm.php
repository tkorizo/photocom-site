<?php

declare(strict_types=1);

class ProductForm
{
    public static function dataFromPost(?array $existing = null): array
    {
        $categoryIds = [];
        if (!empty($_POST['category_ids']) && is_array($_POST['category_ids'])) {
            $categoryIds = array_map('intval', $_POST['category_ids']);
        }

        $existingSecondary = $existing ? ProductRepository::getSecondaryImages($existing) : [];
        $secondary = [];

        for ($i = 1; $i <= 5; $i++) {
            $fieldName = 'image_secondary_' . $i;
            $existingPath = $existingSecondary[$i - 1] ?? null;
            $path = FileUploader::processField($fieldName, 'products', $existingPath);
            if ($path) {
                $secondary[] = $path;
            }
        }

        $mainImage = FileUploader::processField('image', 'products', $existing['image'] ?? null);

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'slug' => trim($_POST['slug'] ?? '') ?: null,
            'description' => (($_POST['description_html'] ?? '') === '1')
                ? trim((string) ($_POST['description'] ?? ''))
                : Helpers::plainTextFromHtml((string) ($_POST['description'] ?? ''), true),
            'short_description' => (($_POST['short_description_html'] ?? '') === '1')
                ? trim((string) ($_POST['short_description'] ?? ''))
                : Helpers::plainTextFromHtml((string) ($_POST['short_description'] ?? ''), true),
            'price' => (float) ($_POST['price'] ?? 0),
            'regular_price' => $_POST['regular_price'] !== '' ? (float) $_POST['regular_price'] : null,
            'sale_price' => $_POST['sale_price'] !== '' ? (float) $_POST['sale_price'] : null,
            'sku' => trim($_POST['sku'] ?? ''),
            'brand' => trim($_POST['brand'] ?? ''),
            'stock_quantity' => $_POST['stock_quantity'] !== '' ? (int) $_POST['stock_quantity'] : null,
            'manage_stock' => isset($_POST['manage_stock']),
            'stock_status' => $_POST['stock_status'] ?? 'instock',
            'is_out_of_stock' => isset($_POST['is_out_of_stock']),
            'is_coming_soon' => isset($_POST['is_coming_soon']),
            'catalog_visibility' => $_POST['catalog_visibility'] ?? 'visible',
            'hide_add_to_cart' => isset($_POST['hide_add_to_cart']),
            'featured' => isset($_POST['featured']),
            // La valeur est recalculée uniquement à partir du prix promo TTC.
            'on_sale' => (string) ($_POST['on_sale'] ?? '0') === '1',
            'category_id' => $categoryIds[0] ?? null,
            'category_ids' => $categoryIds,
            'image' => $mainImage,
            'images_secondary_json' => json_encode($secondary, JSON_UNESCAPED_UNICODE),
            'images_json' => json_encode(array_values(array_filter(array_merge($mainImage ? [$mainImage] : [], $secondary))), JSON_UNESCAPED_UNICODE),
            'is_active' => isset($_POST['is_active']),
        ];

        return Helpers::normalizeProductPricingFlags($data);
    }

    /** @return list<string> */
    public static function validate(array $data): array
    {
        $errors = [];
        if (($data['name'] ?? '') === '') {
            $errors[] = 'Le nom est obligatoire.';
        }
        $pricingError = Helpers::validateProductPricing($data);
        if ($pricingError !== null) {
            $errors[] = $pricingError;
        }

        return $errors;
    }
}
