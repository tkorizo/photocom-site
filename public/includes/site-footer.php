<?php

declare(strict_types=1);

/** @var string $siteName */
/** @var string $address */
/** @var string $phone */
/** @var string $email */
/** @var string $hours */
/** @var array $footerPages */
/** @var array $socialLinks */
?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-col footer-brand">
            <a href="/" class="logo logo-footer">
                <?php $logoClass = 'footer-logo-img'; $logoWidth = 160; $logoVariant = 'mark'; require __DIR__ . '/site-logo.php'; ?>
            </a>
            <p class="footer-desc">Matériel photo, vidéo et studio professionnel. Distribution officielle des grandes marques au Maroc.</p>
            <?php if (!empty($socialLinks)): ?>
                <div class="footer-social">
                    <?php
                    $socialLinkClass = 'footer-social-icon';
                    require __DIR__ . '/social-icons.php';
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer-col">
            <h3>Navigation</h3>
            <ul>
                <li><a href="/">Accueil</a></li>
                <li><a href="/catalogue.php">Catalogue</a></li>
                <li><a href="/a-propos">Qui sommes-nous</a></li>
                <li><a href="/contact">Contact</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h3>Informations</h3>
            <ul>
                <?php foreach ($footerPages as $page): ?>
                    <li><a href="/page/<?= Helpers::e($page['slug']) ?>"><?= Helpers::e($page['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="footer-col" id="contact">
            <h3>Contact</h3>
            <ul class="footer-contact">
                <li><?= Helpers::e($address) ?></li>
                <li><a href="tel:<?= preg_replace('/\s+/', '', $phone) ?>"><?= Helpers::e($phone) ?></a></li>
                <li><a href="mailto:<?= Helpers::e($email) ?>"><?= Helpers::e($email) ?></a></li>
                <li class="hours-multiline"><?= Helpers::multiline($hours) ?></li>
            </ul>
        </div>
    </div>
</footer>
<div class="footer-bottom">
    <div class="container footer-bottom-inner">
        <p>&copy; <?= date('Y') ?> <?= Helpers::e($siteName) ?>. Tous droits réservés.</p>
        <div class="footer-bottom-links">
            <?php foreach (array_slice($footerPages, 0, 3) as $page): ?>
                <a href="/page/<?= Helpers::e($page['slug']) ?>"><?= Helpers::e($page['title']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php
$waDigitsFooter = preg_replace('/\D+/', '', (string) ($whatsapp ?? '')) ?: (string) ($whatsapp ?? '');
?>
<script>
window.PHOTOCOM = Object.assign({}, window.PHOTOCOM || {}, {
    whatsapp: <?= json_encode($waDigitsFooter, JSON_UNESCAPED_UNICODE) ?>,
    customerLoggedIn: <?= !empty($customerLoggedIn) ? 'true' : 'false' ?>,
    csrf: <?= json_encode(Auth::csrfToken(), JSON_UNESCAPED_UNICODE) ?>
});
</script>
<script src="/assets/js/cart.js?v=<?= is_file(__DIR__ . '/../assets/js/cart.js') ? filemtime(__DIR__ . '/../assets/js/cart.js') : time() ?>" defer></script>
<script src="/assets/js/mega-menu.js?v=<?= is_file(__DIR__ . '/../assets/js/mega-menu.js') ? filemtime(__DIR__ . '/../assets/js/mega-menu.js') : time() ?>" defer></script>
<script src="/assets/js/search-suggest.js?v=<?= is_file(__DIR__ . '/../assets/js/search-suggest.js') ? filemtime(__DIR__ . '/../assets/js/search-suggest.js') : time() ?>" defer></script>
<?php if (!empty($loadFiltersDrawer)): ?>
<script src="/assets/js/filters-drawer.js?v=<?= is_file(__DIR__ . '/../assets/js/filters-drawer.js') ? filemtime(__DIR__ . '/../assets/js/filters-drawer.js') : time() ?>" defer></script>
<?php endif; ?>

