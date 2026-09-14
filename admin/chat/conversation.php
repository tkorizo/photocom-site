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
}

$messages = ChatConversationRepository::messages($id);
$visitorRows = VisitorInfo::displayRows($conversation);
$pageTitle = 'Conversation chat';
$currentPage = 'chat';

require dirname(__DIR__) . '/includes/header.php';
?>

<p><a href="/admin/chat/index.php?tab=chats">← Conversations</a></p>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Conversation #<?= (int) $conversation['id'] ?></h2>
            <p class="text-muted">
                Session <?= Helpers::e((string) $conversation['session_id']) ?>
                — <?= Helpers::e((string) ($conversation['created_at'] ?? '')) ?>
                <?php if (!empty($conversation['page_url'])): ?>
                    — <a href="<?= Helpers::e((string) $conversation['page_url']) ?>" target="_blank" rel="noopener">page</a>
                <?php endif; ?>
            </p>
        </div>
        <div class="panel-actions">
            <a class="btn btn-outline" href="/admin/chat/export.php?id=<?= (int) $conversation['id'] ?>&amp;format=json">Exporter JSON</a>
            <a class="btn btn-outline" href="/admin/chat/export.php?id=<?= (int) $conversation['id'] ?>&amp;format=csv">Exporter CSV</a>
            <form method="post" onsubmit="return confirm('Supprimer cette conversation ?');">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-outline">Supprimer</button>
            </form>
        </div>
    </div>

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
