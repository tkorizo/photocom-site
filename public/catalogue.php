<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/includes/init.php';

$catSlug = trim($_GET['cat'] ?? '');
$searchQuery = trim($_GET['q'] ?? '');
$brandFilter = trim($_GET['brand'] ?? '');
$sort = $_GET['sort'] ?? 'name';
$minPrice = trim($_GET['min_price'] ?? '');
$maxPrice = trim($_GET['max_price'] ?? '');
$onSaleOnly = isset($_GET['on_sale']);
$inStockOnly = isset($_GET['in_stock']);
$page = max(1, (int) ($_GET['page'] ?? 1));

$allowedSort = ['name', 'price_asc', 'price_desc', 'newest'];
if (!in_array($sort, $allowedSort, true)) {
    $sort = 'name';
}

$category = null;
$categoryIds = null;
$pageTitle = 'Catalogue';

if ($catSlug !== '') {
    $category = CategoryRepository::findBySlug($catSlug);
    if ($category) {
        $pageTitle = $category['name'];
        $categoryIds = CategoryRepository::descendantIds((int) $category['id']);
    }
}

$filterMeta = ProductRepository::publicFilterMeta($categoryIds);

$result = ProductRepository::paginatePublic(
    $page,
    24,
    $categoryIds,
    $searchQuery ?: null,
    $brandFilter ?: null,
    $minPrice !== '' ? (float) $minPrice : null,
    $maxPrice !== '' ? (float) $maxPrice : null,
    $sort,
    $onSaleOnly,
    $inStockOnly
);

$menuTree = CategoryRepository::menuTree();
$activeCatSlug = $catSlug;

$queryParams = static function () use (
    $catSlug, $searchQuery, $brandFilter, $sort, $minPrice, $maxPrice, $onSaleOnly, $inStockOnly, $page
): array {
    return array_filter([
        'cat' => $catSlug ?: null,
        'q' => $searchQuery ?: null,
        'brand' => $brandFilter ?: null,
        'min_price' => $minPrice !== '' ? $minPrice : null,
        'max_price' => $maxPrice !== '' ? $maxPrice : null,
        'sort' => $sort !== 'name' ? $sort : null,
        'on_sale' => $onSaleOnly ? '1' : null,
        'in_stock' => $inStockOnly ? '1' : null,
        'page' => $page > 1 ? $page : null,
    ]);
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::e($pageTitle) ?> — <?= Helpers::e($siteName) ?></title>
    <link rel="stylesheet" href="/assets/css/public.css">
    <link rel="stylesheet" href="/assets/css/sidebar.css">
    <link rel="stylesheet" href="/assets/css/chat.css">
</head>
<body class="page-catalogue has-sidebar<?= $category ? ' has-category-hero' : '' ?>">
    <?php require __DIR__ . '/includes/site-sidebar.php'; ?>

    <div class="site-main">
    <?php if ($category):
        $categoryHeroImage = CategoryRepository::heroImage($category);
    ?>
    <section
        class="category-hero <?= $categoryHeroImage ? 'has-image' : 'is-fallback' ?>"
        aria-label="<?= Helpers::e($category['name']) ?>"
        <?php if ($categoryHeroImage): ?>style="background-image: url('<?= Helpers::e($categoryHeroImage) ?>')"<?php endif; ?>
    >
        <div class="category-hero-inner container">
            <span class="category-hero-label">Catalogue</span>
            <h1><?= Helpers::e($category['name']) ?></h1>
            <?php if (!empty($category['description'])): ?>
                <p class="category-hero-desc"><?= Helpers::e($category['description']) ?></p>
            <?php endif; ?>
            <p class="category-hero-meta"><?= $result['total'] ?> produit<?= $result['total'] > 1 ? 's' : '' ?></p>
        </div>
    </section>
    <?php endif; ?>
    <main class="catalogue-main">
        <div class="container catalogue-layout">
            <?php require __DIR__ . '/includes/catalogue-filters.php'; ?>

            <div class="catalogue-results">
                <header class="catalogue-header">
                    <?php if (!$category): ?>
                        <h1><?= Helpers::e($pageTitle) ?></h1>
                    <?php endif; ?>
                    <?php if (!$category): ?>
                        <p class="catalogue-meta"><?= $result['total'] ?> produit<?= $result['total'] > 1 ? 's' : '' ?></p>
                    <?php endif; ?>
                </header>

                <?php if (empty($result['data'])): ?>
                    <p class="catalogue-empty">Aucun produit trouvé. <a href="/catalogue.php<?= $catSlug ? '?cat=' . urlencode($catSlug) : '' ?>">Réinitialiser les filtres</a></p>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($result['data'] as $product): ?>
                            <article class="product-card">
                                <div class="product-card-image">
                                    <?php if ($product['image']): ?>
                                        <img src="<?= Helpers::e($product['image']) ?>" alt="<?= Helpers::e($product['name']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <span class="product-placeholder">▣</span>
                                    <?php endif; ?>
                                    <?php if ($product['on_sale']): ?>
                                        <span class="product-badge-sale">Promo</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-card-body">
                                    <?php if ($product['brand']): ?>
                                        <span class="product-brand"><?= Helpers::e($product['brand']) ?></span>
                                    <?php endif; ?>
                                    <h2><?= Helpers::e($product['name']) ?></h2>
                                    <?php if ($product['category_name']): ?>
                                        <p class="product-cat-label"><?= Helpers::e($product['category_name']) ?></p>
                                    <?php endif; ?>
                                    <p class="product-price"><?= Helpers::formatPrice((float) $product['price']) ?></p>
                                    <a href="https://wa.me/<?= Helpers::e($whatsapp) ?>?text=<?= urlencode('Bonjour, je souhaite des infos sur : ' . $product['name']) ?>" class="btn btn-card" target="_blank" rel="noopener">Commander</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($result['total_pages'] > 1): ?>
                        <nav class="pagination" aria-label="Pagination">
                            <?php for ($i = 1; $i <= $result['total_pages']; $i++): ?>
                                <?php $params = $queryParams(); $params['page'] = $i > 1 ? $i : null; ?>
                                <a href="?<?= http_build_query(array_filter($params)) ?>" class="<?= $i === $result['page'] ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/includes/site-footer.php'; ?>
    </div>
    <?php require __DIR__ . '/includes/site-chat.php'; ?>
    <script src="/assets/js/sidebar.js"></script>
    <script src="/assets/js/chat.js"></script>
</body>
</html>
