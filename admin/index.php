<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

Auth::requireLogin();

$pageTitle = 'Tableau de bord';
$currentPage = 'dashboard';

$canCatalog = Auth::canManageCatalog();
$canContent = Auth::canManageContent();
$canManageHome = Auth::canManageHome();
$isAdmin = Auth::isAdmin();
$isSuperAdmin = Auth::isSuperAdmin();

$stats = $canCatalog ? AdminStats::dashboard() : ['products' => ['total' => 0, 'active' => 0, 'out_of_stock' => 0, 'on_sale' => 0, 'coming_soon' => 0, 'in_stock' => 0, 'featured' => 0], 'categories' => ['total' => 0, 'with_products' => 0, 'empty' => 0]];
$p = $stats['products'];
$c = $stats['categories'];
$recentProducts = $canCatalog ? ProductRepository::recent(5) : [];
$user = Auth::user();
$articleCount = $canContent ? ArticleRepository::countPublished() : 0;
$siteSettings = $isAdmin ? SettingRepository::site() : [];
$chatSettings = $isAdmin ? SettingRepository::chat() : [];
$recentCarts = $canCatalog ? CustomerCartRepository::listNonEmpty(5) : [];
$cartCount = $canCatalog ? CustomerCartRepository::countNonEmpty() : 0;
$customerCount = $canCatalog ? CustomerRepository::count() : 0;

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
        <p class="dash-hero-greeting">Bonjour, <?= Helpers::e($user['name'] ?? '') ?></p>
        <h2 class="dash-hero-title">Espace administration</h2>
        <p class="dash-hero-sub">
            <?= Helpers::e(UserRepository::roleLabel((string) ($user['role'] ?? ''))) ?> —
            <?php if ($isAdmin): ?>
                catalogue, contenu et configuration du site.
            <?php elseif ($canCatalog && $canContent): ?>
                gestion du catalogue et du contenu.
            <?php elseif ($canContent): ?>
                gestion des articles du blog Conseils.
            <?php else: ?>
                gestion du catalogue, des comptes et des paniers clients.
            <?php endif; ?>
        </p>
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

<?php if ($canCatalog): ?>
<div class="dash-stats">
    <a href="/admin/produits/index.php" class="dash-stat-card dash-stat-accent">
        <span class="dash-stat-icon"><?= adminIcon('box', 20) ?></span>
        <div>
            <strong><?= $p['total'] ?></strong>
            <span>Produits</span>
        </div>
        <small><?= $p['active'] ?> actifs</small>
    </a>
    <a href="/admin/categories/index.php" class="dash-stat-card dash-stat-neutral">
        <span class="dash-stat-icon"><?= adminIcon('layers', 20) ?></span>
        <div>
            <strong><?= $c['total'] ?></strong>
            <span>Catégories</span>
        </div>
        <small><?= $c['with_products'] ?> avec produits</small>
    </a>
    <a href="/admin/produits/index.php?filter=out_of_stock" class="dash-stat-card dash-stat-accent">
        <span class="dash-stat-icon"><?= adminIcon('alert', 20) ?></span>
        <div>
            <strong><?= $p['out_of_stock'] ?></strong>
            <span>En rupture</span>
        </div>
        <small>À réapprovisionner</small>
    </a>
    <a href="/admin/produits/index.php?filter=on_sale" class="dash-stat-card dash-stat-soft">
        <span class="dash-stat-icon"><?= adminIcon('percent', 20) ?></span>
        <div>
            <strong><?= $p['on_sale'] ?></strong>
            <span>Promotions</span>
        </div>
        <small>Produits en promo</small>
    </a>
    <a href="/admin/clients/index.php" class="dash-stat-card dash-stat-neutral">
        <span class="dash-stat-icon"><?= adminIcon('user', 20) ?></span>
        <div>
            <strong><?= (int) $customerCount ?></strong>
            <span>Comptes clients</span>
        </div>
        <small><?= (int) $cartCount ?> panier<?= $cartCount > 1 ? 's' : '' ?> en cours</small>
    </a>
    <?php if ($canContent): ?>
    <a href="/admin/articles/index.php" class="dash-stat-card dash-stat-accent">
        <span class="dash-stat-icon"><?= adminIcon('pen', 20) ?></span>
        <div>
            <strong><?= $articleCount ?></strong>
            <span>Articles publiés</span>
        </div>
        <small>Blog Conseils</small>
    </a>
    <?php endif; ?>
</div>
<?php elseif ($canContent): ?>
<div class="dash-stats">
    <a href="/admin/articles/index.php" class="dash-stat-card dash-stat-accent">
        <span class="dash-stat-icon"><?= adminIcon('pen', 20) ?></span>
        <div>
            <strong><?= $articleCount ?></strong>
            <span>Articles publiés</span>
        </div>
        <small>Blog Conseils</small>
    </a>
</div>
<?php endif; ?>

<section class="dash-section">
    <h3 class="dash-section-title"><?= $isAdmin ? 'Modules' : 'Accès rapide' ?></h3>
    <div class="module-grid">
        <?php if ($canCatalog): ?>
            <a href="/admin/produits/index.php" class="module-card">
                <span class="module-icon module-icon-accent"><?= adminIcon('box') ?></span>
                <h4>Produits</h4>
                <p>Gérer le catalogue, les stocks et les prix.</p>
            </a>
            <a href="/admin/categories/index.php" class="module-card">
                <span class="module-icon"><?= adminIcon('layers') ?></span>
                <h4>Catégories</h4>
                <p>Organiser la hiérarchie et les visuels.</p>
            </a>
            <a href="/admin/clients/index.php" class="module-card">
                <span class="module-icon"><?= adminIcon('user') ?></span>
                <h4>Comptes clients</h4>
                <p><?= (int) $customerCount ?> compte<?= $customerCount > 1 ? 's' : '' ?> — consulter et contacter.</p>
            </a>
            <a href="/admin/clients/paniers.php" class="module-card">
                <span class="module-icon module-icon-accent"><?= adminIcon('bag') ?></span>
                <h4>Paniers clients</h4>
                <p><?= (int) $cartCount ?> panier<?= $cartCount > 1 ? 's' : '' ?> en cours — relancer par WhatsApp ou e-mail.</p>
            </a>
            <?php if (!$isAdmin): ?>
                <a href="/admin/produits/create.php" class="module-card">
                    <span class="module-icon module-icon-accent"><?= adminIcon('plus') ?></span>
                    <h4>Nouveau produit</h4>
                    <p>Ajouter un produit au catalogue.</p>
                </a>
                <a href="/admin/marques/index.php" class="module-card">
                    <span class="module-icon"><?= adminIcon('tag') ?></span>
                    <h4>Marques</h4>
                    <p>Gérer les marques du catalogue.</p>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($canContent): ?>
            <a href="/admin/articles/index.php" class="module-card">
                <span class="module-icon"><?= adminIcon('pen') ?></span>
                <h4>Articles</h4>
                <p><?= $articleCount ?> publié<?= $articleCount > 1 ? 's' : '' ?> — blog et actualités.</p>
            </a>
            <?php if (!$isAdmin): ?>
                <a href="/admin/articles/create.php" class="module-card">
                    <span class="module-icon module-icon-accent"><?= adminIcon('plus') ?></span>
                    <h4>Nouvel article</h4>
                    <p>Rédiger un article pour Conseils.</p>
                </a>
                <a href="/admin/about/index.php" class="module-card">
                    <span class="module-icon"><?= adminIcon('doc') ?></span>
                    <h4>Qui sommes-nous</h4>
                    <p>Textes de la page À propos.</p>
                </a>
                <a href="/admin/contact/index.php" class="module-card">
                    <span class="module-icon"><?= adminIcon('chat') ?></span>
                    <h4>Contact</h4>
                    <p>Textes de la page Contact.</p>
                </a>
                <a href="/admin/conseils-page/index.php" class="module-card">
                    <span class="module-icon"><?= adminIcon('pen') ?></span>
                    <h4>Page Conseils</h4>
                    <p>En-tête et contenus de la page blog.</p>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($canManageHome): ?>
            <a href="/admin/home/index.php" class="module-card">
                <span class="module-icon"><?= adminIcon('home') ?></span>
                <h4>Page d'accueil</h4>
                <p>Hero, sections, vidéos, confiance et newsletter.</p>
            </a>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <a href="/admin/pages/index.php" class="module-card">
                <span class="module-icon"><?= adminIcon('doc') ?></span>
                <h4>Pages légales</h4>
                <p>Confidentialité et conditions générales.</p>
            </a>
            <a href="/admin/settings/site.php" class="module-card">
                <span class="module-icon"><?= adminIcon('settings') ?></span>
                <h4>Infos du site</h4>
                <p>Contact, adresse, horaires et identité.</p>
            </a>
            <a href="/admin/settings/files.php" class="module-card">
                <span class="module-icon"><?= adminIcon('folder') ?></span>
                <h4>Fichiers</h4>
                <p>Médias uploadés — ajouter ou supprimer.</p>
            </a>
            <a href="/admin/chat/index.php" class="module-card">
                <span class="module-icon"><?= adminIcon('chat') ?></span>
                <h4>Agent de chat</h4>
                <p><?= ($chatSettings['chat_enabled'] ?? '0') === '1' ? 'Activé' : 'Désactivé' ?> — conseiller OpenAI + catalogue.</p>
            </a>
            <a href="/admin/users/index.php" class="module-card">
                <span class="module-icon"><?= adminIcon('user') ?></span>
                <h4>Utilisateurs</h4>
                <p>Rôles et accès de l’équipe.</p>
            </a>
            <?php if ($isSuperAdmin): ?>
                <a href="/admin/settings/audit-log.php" class="module-card">
                    <span class="module-icon"><?= adminIcon('log') ?></span>
                    <h4>Journal d'activité</h4>
                    <p>Qui a modifié quoi, et quand.</p>
                </a>
            <?php endif; ?>
            <a href="/" target="_blank" class="module-card">
                <span class="module-icon module-icon-muted"><?= adminIcon('external') ?></span>
                <h4>Voir le site</h4>
                <p>Aperçu public de <?= Helpers::e($siteSettings['site_name'] ?? Helpers::config()['name']) ?>.</p>
            </a>
        <?php endif; ?>
    </div>
</section>

<?php if ($canCatalog): ?>
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
<?php endif; ?>

<?php if ($canCatalog): ?>
<section class="panel panel-modern" style="margin-top:1.5rem">
    <div class="panel-header">
        <h2>Paniers clients</h2>
        <a href="/admin/clients/paniers.php" class="btn btn-sm">Voir tout →</a>
    </div>
    <?php if ($recentCarts === []): ?>
        <p class="empty-state">Aucun panier enregistré. Ils apparaissent dès qu’un client connecté ajoute un produit.</p>
    <?php else: ?>
        <table class="data-table data-table-compact">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Articles</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentCarts as $cart): ?>
                    <?php $cust = $cart['customer']; ?>
                    <tr>
                        <td>
                            <strong><?= Helpers::e((string) $cust['name']) ?></strong>
                            <div><small class="text-muted"><?= Helpers::e((string) $cust['email']) ?></small></div>
                        </td>
                        <td><?= (int) $cart['count'] ?></td>
                        <td><?= Helpers::e(Helpers::formatPrice((float) $cart['total'])) ?></td>
                        <td class="table-actions">
                            <a href="/admin/clients/panier.php?id=<?= (int) $cust['id'] ?>" class="btn btn-sm btn-primary">Contacter</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
