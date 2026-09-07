<?php

declare(strict_types=1);

/**
 * Scripts de fin de page (après markup chat / cookies).
 * mega-menu + cart sont déjà en defer dans site-footer.php
 */
?>
<script src="/assets/js/chat.js?v=<?= is_file(__DIR__ . '/../assets/js/chat.js') ? filemtime(__DIR__ . '/../assets/js/chat.js') : time() ?>" defer></script>
