<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();
Database::migrate();

$id = (int) ($_GET['id'] ?? 0);
$conversation = $id > 0 ? ChatConversationRepository::find($id) : null;
if (!$conversation) {
    Helpers::flash('error', 'Conversation introuvable.');
    Helpers::redirect('/admin/chat/index.php?tab=chats');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        Helpers::flash('error', 'Session expirée.');
        Helpers::redirect('/admin/chat/conversation.php?id=' . $id);
    }
    if (($_POST['action'] ?? '') === 'delete') {
        ChatConversationRepository::delete($id);
        AuditLog::record('chat.delete', 'chat', $id);
        Helpers::flash('success', 'Conversation supprimée.');
        Helpers::redirect('/admin/chat/index.php?tab=chats');
    }
    if (($_POST['action'] ?? '') === 'unblock') {
        ChatGuard::unblockVisitor(
            (string) ($conversation['session_id'] ?? ''),
            (string) ($conversation['visitor_ip'] ?? '')
        );
        AuditLog::record('chat.unblock', 'chat', $id);
        Helpers::flash('success', 'Le chat a été réactivé pour ce visiteur.');
        Helpers::redirect('/admin/chat/conversation.php?id=' . $id);
    }
}

$messages = ChatConversationRepository::messages($id);
$visitorRows = VisitorInfo::displayRows($conversation);
$blocked = ChatGuard::isConversationBlocked($conversation);
$pageTitle = 'Conversation chat';
$currentPage = 'chat';

require dirname(__DIR__) . '/includes/header.php';
?>

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success"><?= Helpers::e($message) ?></div>
<?php endif; ?>
<?php if ($message = Helpers::flash('error')): ?>
    <div class="alert alert-error"><?= Helpers::e($message) ?></div>
<?php endif; ?>

<p><a href="/admin/chat/index.php?tab=chats">← Conversations</a></p>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2>
                <?php if ($blocked): ?>
                    <span class="chat-block-icon" title="Visiteur bloqué" aria-label="Visiteur bloqué">
                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 8l8 8M16 8l-8 8" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                    </span>
                <?php endif; ?>
                Conversation #<?= (int) $conversation['id'] ?>
            </h2>
            <p class="text-muted">
                Session <?= Helpers::e((string) $conversation['session_id']) ?>
                — <?= Helpers::e((string) ($conversation['created_at'] ?? '')) ?>
                <?php if (!empty($conversation['page_url'])): ?>
                    — <a href="<?= Helpers::e((string) $conversation['page_url']) ?>" target="_blank" rel="noopener">page</a>
                <?php endif; ?>
            </p>
        </div>
        <div class="panel-actions">
            <?php if ($blocked): ?>
                <form method="post">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="action" value="unblock">
                    <button type="submit" class="btn">Réactiver le chat</button>
                </form>
            <?php endif; ?>
            <a class="btn btn-outline" href="/admin/chat/export.php?id=<?= (int) $conversation['id'] ?>&amp;format=json">Exporter JSON</a>
            <a class="btn btn-outline" href="/admin/chat/export.php?id=<?= (int) $conversation['id'] ?>&amp;format=csv">Exporter CSV</a>
            <form method="post" onsubmit="return confirm('Supprimer cette conversation ?');">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-outline">Supprimer</button>
            </form>
        </div>
    </div>

    <?php if ($blocked): ?>
        <div class="alert alert-error">
            Ce visiteur est bloqué<?= trim((string) ($conversation['visitor_ip'] ?? '')) !== '' ? ' (IP ' . Helpers::e((string) $conversation['visitor_ip']) . ')' : '' ?>.
            Le widget chat est masqué sur le site. Cliquez sur « Réactiver le chat » pour le rétablir.
        </div>
    <?php endif; ?>

    <dl class="chat-visitor-card">
        <?php foreach ($visitorRows as $row): ?>
            <div>
                <dt><?= Helpers::e($row['label']) ?></dt>
                <dd><?= Helpers::e($row['value']) ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>

    <div class="chat-admin-thread">
        <?php foreach ($messages as $msg): ?>
            <?php $role = (string) ($msg['role'] ?? 'user'); ?>
            <article class="chat-admin-bubble chat-admin-bubble--<?= Helpers::e($role) ?>" dir="auto">
                <strong><?= $role === 'assistant' ? 'Agent' : 'Visiteur' ?></strong>
                <span class="text-muted"><?= Helpers::e((string) ($msg['created_at'] ?? '')) ?></span>
                <p><?= nl2br(Helpers::e((string) ($msg['content'] ?? '')), false) ?></p>
            </article>
        <?php endforeach; ?>
        <?php if ($messages === []): ?>
            <p class="text-muted">Aucun message.</p>
        <?php endif; ?>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
