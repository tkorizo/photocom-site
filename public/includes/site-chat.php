<?php

declare(strict_types=1);

/** @var array $chatSettings */
/** @var string $whatsapp */
/** @var string $siteName */
?>
<div class="chat-widget" data-chat-widget>
    <div class="chat-panel" data-chat-panel hidden aria-hidden="true">
        <header class="chat-panel-head">
            <div class="chat-panel-brand">
                <?php $logoClass = 'chat-logo-img'; $logoWidth = 92; require __DIR__ . '/site-logo.php'; ?>
                <div>
                    <strong>Assistance en ligne</strong>
                    <span><span class="chat-status-dot" aria-hidden="true"></span> Réponse rapide</span>
                </div>
            </div>
            <button type="button" class="chat-panel-close" data-chat-close aria-label="Fermer le chat">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </header>
        <div class="chat-messages" data-chat-messages role="log" aria-live="polite"></div>
        <form class="chat-form" data-chat-form>
            <input type="text" name="message" placeholder="Écrivez votre message…" autocomplete="off" required maxlength="1000" aria-label="Votre message">
            <button type="submit" aria-label="Envoyer">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
            </button>
        </form>
    </div>

    <button type="button" class="chat-launcher" data-chat-toggle aria-label="Ouvrir le chat" aria-expanded="false">
        <span class="chat-launcher-waves" aria-hidden="true">
            <span></span><span></span><span></span>
        </span>
        <span class="chat-launcher-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a4 4 0 01-4 4H8l-5 3V7a4 4 0 014-4h10a4 4 0 014 4z"/></svg>
        </span>
    </button>
</div>

<script>
window.PHOTOCOM_CHAT = {
    enabled: <?= ($chatSettings['chat_enabled'] ?? '0') === '1' ? 'true' : 'false' ?>,
    welcome: <?= json_encode($chatSettings['chat_welcome_message'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
    offline: <?= json_encode($chatSettings['chat_offline_message'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
    whatsapp: <?= json_encode($whatsapp, JSON_UNESCAPED_UNICODE) ?>
};
</script>
