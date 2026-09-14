<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();
Database::migrate();

$id = (int) ($_GET['id'] ?? 0);
$format = strtolower(trim((string) ($_GET['format'] ?? 'json')));
if ($format !== 'csv') {
    $format = 'json';
}
$search = trim((string) ($_GET['q'] ?? ''));

if ($id > 0 && ChatConversationRepository::find($id) === null) {
    Helpers::flash('error', 'Conversation introuvable.');
    Helpers::redirect('/admin/chat/index.php?tab=chats');
}

AuditLog::record('chat.export', 'chat', $id > 0 ? $id : null, [
    'format' => $format,
    'scope' => $id > 0 ? 'one' : 'all',
]);

ChatExport::download($format, $id > 0 ? $id : null, $search !== '' ? $search : null);
