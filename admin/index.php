<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

Auth::requireLogin();

$pageTitle = 'Tableau de bord';
$currentPage = 'dashboard';

$stats = AdminStats::dashboard();
$p = $stats['products'];
$c = $stats['categories'];
$recentProducts = ProductRepository::recent(5);
$user = Auth::user();
$isAdmin = Auth::isAdmin();
$articleCount = $isAdmin ? ArticleRepository::countPublished() : 0;
$siteSettings = $isAdmin ? SettingRepository::site() : [];
$chatSettings = $isAdmin ? SettingRepository::chat() : [];

require __DIR__ . '/includes/header.php';
?>

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success"><?= Helpers::e($message) ?></div>
<?php endif; ?>
<?php if ($message = Helpers::flash('error')): ?>
    <div class="alert alert-error"><?= Helpers::e($message) ?></div>
<?php endif; ?>

<section class="dash-hero">
    <div class="dash-hero-content">
        <p class="dash-hero-greeting">Bonjour, <?= Helpers::e($user['name'] ?? '') ?> 👋</p>
        <h2 class="dash-hero-title">Bienvenue sur votre espace PHOTOCOM</h2>
        <p class="dash-hero-sub">Gérez votre catalogue, votre contenu et la configuration du site depuis un seul endroit.</p>
    </div>
    <div class="dash-hero-date">
        <?php
        $jours = ['Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi', 'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche'];
        $mois = ['January' => 'janvier', 'February' => 'février', 'March' => 'mars', 'April' => 'avril', 'May' => 'mai', 'June' => 'juin', 'July' => 'juillet', 'August' => 'août', 'September' => 'septembre', 'October' => 'octobre', 'November' => 'novembre', 'December' => 'décembre'];
        $dateStr = $jours[date('l')] . ' ' . date('j') . ' ' . $mois[date('F')] . ' ' . date('Y');
        ?>
        <span><?= $dateStr ?></span>
    </div>
</section>

<div class="dash-stats">
    <a href="/admin/produits/index.php" class="dash-stat-card dash-stat-blue">
        <span class="dash-stat-icon">▣</span>
        <div>
            <strong><?= $p['total'] ?></strong>
            <span>Produits</span>
        </div>
        <small><?= $p['active'] ?> actifs</small>
    </a>
    <a href="/admin/categories/index.php" class="dash-stat-card dash-stat-purple">
        <span class="dash-stat-icon">▤</span>
        <div>
            <strong><?= $c['total'] ?></strong>
            <span>Catégories</span>
        </div>
        <small><?= $c['with_products'] ?> avec produits</small>
    </a>
    <a href="/admin/produits/index.php?filter=out_of_stock" class="dash-stat-card dash-stat-red">
        <span class="dash-stat-icon">!</span>
        <div>
            <strong><?= $p['out_of_stock'] ?></strong>
            <span>En rupture</span>
        </div>
        <small>À réapprovisionner</small>
    </a>
    <a href="/admin/produits/index.php?filter=on_sale" class="dash-stat-card dash-stat-amber">
        <span class="dash-stat-icon">%</span>
        <div>
            <strong><?= $p['on_sale'] ?></strong>
            <span>Promotions</span>
        </div>
        <small>Produits en promo</small>
    </a>
</div>

<?php if ($isAdmin): ?>
<section class="dash-section">
    <h3 class="dash-section-title">Modules</h3>
    <div class="module-grid">
        <a href="/admin/produits/index.php" class="module-card">
            <span class="module-icon module-icon-blue">▣</span>
            <h4>Produits</h4>
            <p>Gérer le catalogue, les stocks et les prix.</p>
        </a>
        <a href="/admin/categories/index.php" class="module-card">
            <span class="module-icon module-icon-purple">▤</span>
            <h4>Catégories</h4>
            <p>Organiser la hiérarchie et les visuels.</p>
        </a>
        <a href="/admin/articles/index.php" class="module-card">
            <span class="module-icon module-icon-green">✎</span>
            <h4>Articles</h4>
            <p><?= $articleCount ?> publié<?= $articleCount > 1 ? 's' : '' ?> — blog et actualités.</p>
        </a>
        <a href="/admin/pages/index.php" class="module-card">
            <span class="module-icon module-icon-slate">📄</span>
            <h4>Pages légales</h4>
            <p>Confidentialité et conditions générales.</p>
        </a>
        <a href="/admin/settings/site.php" class="module-card">
            <span class="module-icon module-icon-cyan">⚙</span>
            <h4>Infos du site</h4>
            <p>Contact, adresse, horaires et identité.</p>
        </a>
        <a href="/admin/chat/index.php" class="module-card">
            <span class="module-icon module-icon-pink">💬</span>
            <h4>Chat n8n</h4>
            <p><?= $chatSettings['chat_enabled'] === '1' ? 'Activé' : 'Désactivé' ?> — webhook n8n.</p>
        </a>
        <a href="/admin/users/index.php" class="module-card">
            <span class="module-icon module-icon-orange">👤</span>
            <h4>Utilisateurs</h4>
            <p>Équipe admin et éditeurs du catalogue.</p>
        </a>
        <a href="/" target="_blank" class="module-card">
            <span class="module-icon module-icon-muted">↗</span>
            <h4>Voir le site</h4>
            <p>Aperçu public de <?= Helpers::e($siteSettings['site_name'] ?? Helpers::config()['name']) ?>.</p>
        </a>
    </div>
</section>
<?php else: ?>
<section class="dash-section">
    <h3 class="dash-section-title">Accès rapide</h3>
    <div class="module-grid">
        <a href="/admin/produits/create.php" class="module-card">
            <span class="module-icon module-icon-blue">+</span>
            <h4>Nouveau produit</h4>
            <p>Ajouter un produit au catalogue.</p>
        </a>
        <a href="/admin/categories/create.php" class="module-card">
            <span class="module-icon module-icon-purple">+</span>
            <h4>Nouvelle catégorie</h4>
            <p>Créer une catégorie ou sous-catégorie.</p>
        </a>
        <a href="/admin/produits/index.php" class="module-card">
            <span class="module-icon module-icon-blue">▣</span>
            <h4>Tous les produits</h4>
            <p><?= $p['total'] ?> produits dans le catalogue.</p>
        </a>
        <a href="/admin/categories/index.php" class="module-card">
            <span class="module-icon module-icon-purple">▤</span>
            <h4>Toutes les catégories</h4>
            <p><?= $c['total'] ?> catégories organisées.</p>
        </a>
    </div>
</section>
<?php endif; ?>

<div class="grid-2 dash-grid">
    <section class="panel panel-modern">
        <div class="panel-header">
            <h2>Derniers produits</h2>
            <a href="/admin/produits/index.php" class="btn btn-sm">Voir tout →</a>
        </div>
        <?php if (empty($recentProducts)): ?>
            <p class="empty-state">Aucun produit. <a href="/admin/produits/create.php">Créer un produit</a></p>
        <?php else: ?>
            <table class="data-table data-table-compact">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Prix</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentProducts as $product): ?>
                        <tr>
                            <td>
                                <a href="/admin/produits/edit.php?id=<?= (int) $product['id'] ?>" class="product-link">
                                    <?php if ($product['image']): ?>
                                        <img src="<?= Helpers::e($product['image']) ?>" alt="" class="thumb thumb-sm">
                                    <?php endif; ?>
                                    <?= Helpers::e($product['name']) ?>
                                </a>
                            </td>
                            <td><?= Helpers::formatPrice((float) $product['price']) ?></td>
                            <td><span class="badge <?= $product['is_active'] ? 'badge-success' : 'badge-muted' ?>"><?= $product['is_active'] ? 'Actif' : 'Inactif' ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="panel panel-modern">
        <div class="panel-header">
            <h2>Aperçu catalogue</h2>
        </div>
        <ul class="dash-overview-list">
            <li>
                <span>Produits actifs</span>
                <a href="/admin/produits/index.php?status=active"><strong><?= $p['active'] ?></strong></a>
            </li>
            <li>
                <span>Bientôt disponibles</span>
                <a href="/admin/produits/index.php?filter=coming_soon"><strong><?= $p['coming_soon'] ?></strong></a>
            </li>
            <li>
                <span>En stock</span>
                <a href="/admin/produits/index.php?filter=in_stock"><strong><?= $p['in_stock'] ?></strong></a>
            </li>
            <li>
                <span>Catégories vides</span>
                <a href="/admin/categories/index.php?filter=empty"><strong><?= $c['empty'] ?></strong></a>
            </li>
            <li>
                <span>Vedettes</span>
                <a href="/admin/produits/index.php?filter=featured"><strong><?= $p['featured'] ?></strong></a>
            </li>
        </ul>
        <div class="dash-quick-actions">
            <a href="/admin/produits/create.php" class="btn btn-primary">+ Produit</a>
            <a href="/admin/categories/create.php" class="btn">+ Catégorie</a>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
