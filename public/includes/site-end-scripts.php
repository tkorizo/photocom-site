<?php

declare(strict_types=1);

/**
 * Scripts de fin de page (après markup chat / cookies).
 * mega-menu + cart sont déjà en defer dans site-footer.php
 *
 * chat.js : chargé au premier geste ou après idle (réduit JS inutilisé au chargement).
 */
$chatJs = '/assets/js/chat.js?v=' . (is_file(__DIR__ . '/../assets/js/chat.js') ? filemtime(__DIR__ . '/../assets/js/chat.js') : time());
$chatEnabled = (($chatSettings['chat_enabled'] ?? '0') === '1');
?>
<?php if ($chatEnabled): ?>
<script>
(function () {
  var loaded = false;
  function loadChat() {
    if (loaded) return;
    loaded = true;
    var s = document.createElement('script');
    s.src = <?= json_encode($chatJs, JSON_UNESCAPED_SLASHES) ?>;
    s.defer = true;
    document.body.appendChild(s);
  }
  ['pointerdown', 'keydown', 'touchstart'].forEach(function (evt) {
    window.addEventListener(evt, loadChat, { once: true, passive: true });
  });
  if ('requestIdleCallback' in window) {
    requestIdleCallback(loadChat, { timeout: 4000 });
  } else {
    window.addEventListener('load', function () { setTimeout(loadChat, 2500); });
  }
})();
</script>
<?php endif; ?>
