<?php

declare(strict_types=1);

class WordPressImporter
{
    private string $baseUrl;
    private string $consumerKey;
    private string $consumerSecret;
    private array $config;
    private array $errors = [];
    private int $categoriesImported = 0;
    private int $productsImported = 0;

    public function __construct()
    {
        $this->config = Helpers::config();
        $this->baseUrl = rtrim((string) $this->config['wordpress_url'], '/');
        $this->consumerKey = (string) $this->config['woocommerce_key'];
        $this->consumerSecret = (string) $this->config['woocommerce_secret'];
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->consumerKey !== '' && $this->consumerSecret !== '';
    }

    public function import(): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Configuration WordPress/WooCommerce incomplète. Vérifiez le fichier .env');
        }

        Database::initialize();

        if (!is_dir($this->config['uploads_path'])) {
            mkdir($this->config['uploads_path'], 0755, true);
        }
        ImageDownloader::categoriesPath();

        $this->importCategories();
        $this->importProducts();

        $status = empty($this->errors) ? 'success' : ($this->productsImported > 0 ? 'partial' : 'error');
        $message = sprintf(
            'Import terminé : %d catégories, %d produits (images locales).%s',
            $this->categoriesImported,
            $this->productsImported,
            empty($this->errors) ? '' : ' ' . count($this->errors) . ' erreur(s).'
        );

        $this->logImport($status, $message);

        return [
            'status' => $status,
            'message' => $message,
            'categories_count' => $this->categoriesImported,
            'products_count' => $this->productsImported,
            'errors' => $this->errors,
        ];
    }

    private function importCategories(): void
    {
        $allCategories = [];
        $page = 1;

        do {
            $categories = $this->fetch('/products/categories', ['page' => $page, 'per_page' => 100]);
            $allCategories = array_merge($allCategories, $categories);
            $page++;
        } while (count($categories) === 100);

        foreach ($allCategories as $category) {
            try {
                $thumbnail = null;
                if (!empty($category['image']['src'])) {
                    $thumbnail = ImageDownloader::download(
                        $category['image']['src'],
                        'categories',
                        'wp-cat-' . (int) $category['id'] . '-thumb'
                    );
                }

                CategoryRepository::updateOrCreateByWordpressId((int) $category['id'], [
                    'name' => $category['name'],
                    'slug' => $category['slug'] ?? Helpers::slugify($category['name']),
                    'description' => $category['description'] ?? null,
                    'display_type' => $category['display'] ?? 'default',
                    'thumbnail' => $thumbnail,
                    'menu_order' => (int) ($category['menu_order'] ?? 0),
                    'parent_id' => null,
                ]);
                $this->categoriesImported++;
            } catch (Throwable $e) {
                $this->errors[] = 'Catégorie ' . ($category['name'] ?? '?') . ' : ' . $e->getMessage();
            }
        }

        foreach ($allCategories as $category) {
            try {
                $parentWpId = (int) ($category['parent'] ?? 0);
                if ($parentWpId > 0) {
                    CategoryRepository::setParentByWordpressIds((int) $category['id'], $parentWpId);
                }
            } catch (Throwable $e) {
                $this->errors[] = 'Hiérarchie ' . ($category['name'] ?? '?') . ' : ' . $e->getMessage();
            }
        }
    }

    private function importProducts(): void
    {
        $page = 1;

        do {
            $products = $this->fetch('/products', [
                'page' => $page,
                'per_page' => 100,
                'status' => 'publish',
            ]);

            foreach ($products as $product) {
                try {
                    $this->importProduct($product);
                    $this->productsImported++;
                } catch (Throwable $e) {
                    $this->errors[] = 'Produit ' . ($product['name'] ?? '?') . ' : ' . $e->getMessage();
                }
            }

            $page++;
        } while (count($products) === 100);
    }

    private function importProduct(array $product): void
    {
        $categoryIds = [];
        if (!empty($product['categories']) && is_array($product['categories'])) {
            foreach ($product['categories'] as $wpCategory) {
                if (empty($wpCategory['id'])) {
                    continue;
                }
                $category = CategoryRepository::findByWordpressId((int) $wpCategory['id']);
                if ($category) {
                    $categoryIds[] = (int) $category['id'];
                }
            }
        }

        $meta = $this->parseMeta($product['meta_data'] ?? []);
        $stockStatus = $product['stock_status'] ?? 'instock';
        $manageStock = !empty($product['manage_stock']);
        $stockQty = $manageStock ? ($product['stock_quantity'] ?? null) : ($meta['woodmart_total_stock_quantity'] ?? null);

        $wpId = (int) $product['id'];
        $allImages = [];

        if (!empty($product['images']) && is_array($product['images'])) {
            $seen = [];
            $imageIndex = 0;
            foreach ($product['images'] as $image) {
                if (empty($image['src']) || isset($seen[$image['src']])) {
                    continue;
                }
                $seen[$image['src']] = true;

                if ($imageIndex >= 6) {
                    break;
                }

                $suffix = $imageIndex === 0 ? 'main' : 'sec-' . $imageIndex;
                $local = ImageDownloader::download($image['src'], 'products', 'wp-' . $wpId . '-' . $suffix);

                if ($local) {
                    $allImages[] = $local;
                } else {
                    $this->errors[] = 'Image non téléchargée pour ' . ($product['name'] ?? '?') . ' (' . $suffix . ')';
                }

                $imageIndex++;
            }
        }

        $mainImage = $allImages[0] ?? null;
        $secondaryImages = array_slice($allImages, 1, 5);

        $productId = ProductRepository::updateOrCreateByWordpressId($wpId, [
            'name' => $product['name'],
            'slug' => $product['slug'] ?? Helpers::slugify($product['name']),
            'description' => $product['description'] ?? null,
            'short_description' => $product['short_description'] ?? null,
            'price' => (float) ($product['price'] ?? 0),
            'regular_price' => isset($product['regular_price']) && $product['regular_price'] !== '' ? (float) $product['regular_price'] : null,
            'sale_price' => isset($product['sale_price']) && $product['sale_price'] !== '' ? (float) $product['sale_price'] : null,
            'sku' => $product['sku'] ?? null,
            'brand' => $this->extractBrand($product),
            'stock_quantity' => $stockQty !== null ? (int) $stockQty : null,
            'manage_stock' => $manageStock ? 1 : 0,
            'stock_status' => $stockStatus,
            'is_out_of_stock' => $stockStatus === 'outofstock' ? 1 : 0,
            'is_coming_soon' => $stockStatus === 'onbackorder' ? 1 : 0,
            'catalog_visibility' => $product['catalog_visibility'] ?? 'visible',
            'hide_add_to_cart' => ($meta['_hide_atc_button'] ?? 'no') === 'yes' ? 1 : 0,
            'featured' => !empty($product['featured']) ? 1 : 0,
            'on_sale' => !empty($product['on_sale']) ? 1 : 0,
            'category_id' => $categoryIds[0] ?? null,
            'category_ids' => $categoryIds,
            'image' => $mainImage,
            'images_secondary_json' => json_encode($secondaryImages, JSON_UNESCAPED_UNICODE),
            'images_json' => json_encode($allImages, JSON_UNESCAPED_UNICODE),
            'permalink' => $product['permalink'] ?? null,
            'is_active' => ($product['status'] ?? 'publish') === 'publish' ? 1 : 0,
        ]);

        ProductCategoryRepository::syncForProduct($productId, $categoryIds);
    }

    private function parseMeta(array $metaData): array
    {
        $meta = [];
        foreach ($metaData as $item) {
            if (isset($item['key'])) {
                $meta[$item['key']] = $item['value'];
            }
        }
        return $meta;
    }

    private function extractBrand(array $product): ?string
    {
        if (!empty($product['brands']) && is_array($product['brands'])) {
            return $product['brands'][0]['name'] ?? null;
        }
        if (!empty($product['attributes']) && is_array($product['attributes'])) {
            foreach ($product['attributes'] as $attribute) {
                $name = strtolower($attribute['name'] ?? '');
                if ($name === 'brand' || $name === 'marque') {
                    return $attribute['options'][0] ?? null;
                }
            }
        }
        return null;
    }

    private function fetch(string $endpoint, array $params = []): array
    {
        $query = $params ? '?' . http_build_query($params) : '';
        $url = $this->baseUrl . '/wp-json/wc/v3' . $endpoint . $query;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $this->consumerKey . ':' . $this->consumerSecret,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if ($response === false) {
            throw new RuntimeException('Erreur cURL : ' . $error);
        }

        if ($httpCode !== 200) {
            throw new RuntimeException('API WooCommerce erreur HTTP ' . $httpCode . ' pour ' . $endpoint);
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new RuntimeException('Réponse API invalide pour ' . $endpoint);
        }

        return $data;
    }

    private function logImport(string $status, string $message): void
    {
        $stmt = Database::getInstance()->prepare(
            'INSERT INTO import_logs (type, status, message, products_count, categories_count) VALUES (:type, :status, :message, :products_count, :categories_count)'
        );
        $stmt->execute([
            'type' => 'wordpress',
            'status' => $status,
            'message' => $message,
            'products_count' => $this->productsImported,
            'categories_count' => $this->categoriesImported,
        ]);
    }

    public static function recentLogs(int $limit = 10): array
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM import_logs ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
