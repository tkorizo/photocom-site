<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();
Database::migrate();

$pageTitle = 'Agent de chat';
$currentPage = 'chat';
$tab = (string) ($_GET['tab'] ?? 'settings');
$allowedTabs = ['settings', 'instructions', 'products', 'chats'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'settings';
}
$errors = [];
$settings = SettingRepository::chat();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        $action = (string) ($_POST['action'] ?? 'save');
        if ($action === 'delete_chat') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                ChatConversationRepository::delete($id);
                AuditLog::record('chat.delete', 'chat', $id);
                Helpers::flash('success', 'Conversation supprimée.');
            }
            Helpers::redirect('/admin/chat/index.php?tab=chats');
        }

        $postedTab = (string) ($_POST['tab'] ?? $tab);
        if ($postedTab === 'instructions') {
            SettingRepository::setMany([
                'chat_system_prompt' => trim((string) ($_POST['chat_system_prompt'] ?? '')),
                'chat_instruction_no_answer' => trim((string) ($_POST['chat_instruction_no_answer'] ?? '')),
                'chat_instruction_insult' => trim((string) ($_POST['chat_instruction_insult'] ?? '')),
                'chat_instruction_language' => trim((string) ($_POST['chat_instruction_language'] ?? '')),
                'chat_instruction_handoff' => trim((string) ($_POST['chat_instruction_handoff'] ?? '')),
                'chat_instruction_extra' => trim((string) ($_POST['chat_instruction_extra'] ?? '')),
                'chat_multilingual' => isset($_POST['chat_multilingual']) ? '1' : '0',
            ]);
            AuditLog::record('settings.chat_instructions', 'settings', null);
            Helpers::flash('success', 'Instructions de l’agent enregistrées.');
            Helpers::redirect('/admin/chat/index.php?tab=instructions');
        }

        $key = trim((string) ($_POST['chat_openai_api_key'] ?? ''));
        if ($key !== '' && !ChatAgent::looksLikeOpenAiKey($key)) {
            $errors[] = 'La clé API OpenAI est invalide (elle doit commencer par sk-).';
        }
        if ($errors === []) {
            $data = [
                'chat_enabled' => isset($_POST['chat_enabled']) ? '1' : '0',
                'chat_agent_name' => trim((string) ($_POST['chat_agent_name'] ?? 'Conseiller PHOTOCOM')),
                'chat_openai_model' => trim((string) ($_POST['chat_openai_model'] ?? 'gpt-4o-mini')),
                'chat_temperature' => trim((string) ($_POST['chat_temperature'] ?? '0.3')),
                'chat_max_tokens' => (string) max(120, min(1200, (int) ($_POST['chat_max_tokens'] ?? 500))),
                'chat_history_limit' => (string) max(2, min(20, (int) ($_POST['chat_history_limit'] ?? 10))),
                'chat_product_limit' => (string) max(1, min(8, (int) ($_POST['chat_product_limit'] ?? 6))),
                'chat_include_prices' => isset($_POST['chat_include_prices']) ? '1' : '0',
                'chat_include_stock' => isset($_POST['chat_include_stock']) ? '1' : '0',
                'chat_welcome_message' => trim((string) ($_POST['chat_welcome_message'] ?? '')),
                'chat_offline_message' => trim((string) ($_POST['chat_offline_message'] ?? '')),
            ];
            if ($key !== '') {
                $data['chat_openai_api_key'] = $key;
            }
            if (isset($_POST['chat_clear_api_key'])) {
                $data['chat_openai_api_key'] = '';
            }
            SettingRepository::setMany($data);
            AuditLog::record('settings.chat', 'settings', null);
            Helpers::flash('success', 'Paramètres de l’agent enregistrés.');
            Helpers::redirect('/admin/chat/index.php?tab=settings');
        }
    }
}

$settings = SettingRepository::chat();
$hasKey = ChatAgent::hasApiKey($settings);
$maskedKey = ChatAgent::maskedApiKey($settings);
$models = ['gpt-4o-mini', 'gpt-4.1-mini', 'gpt-4o', 'gpt-4.1'];

$productSearch = trim((string) ($_GET['q'] ?? ''));
$productPage = max(1, (int) ($_GET['p'] ?? 1));
$productsResult = ['items' => [], 'total' => 0, 'pages' => 1];
if ($tab === 'products') {
    $productsResult = ProductRepository::paginate($productPage, 20, $productSearch !== '' ? $productSearch : null, 'active');
    $productsResult['items'] = $productsResult['data'] ?? [];
    $productsResult['pages'] = (int) ($productsResult['total_pages'] ?? 1);
}

$chatSearch = trim((string) ($_GET['cq'] ?? ''));
$chatPage = max(1, (int) ($_GET['cp'] ?? 1));
$chats = [];
$chatTotal = 0;
$chatPages = 1;
if ($tab === 'chats') {
    $chatTotal = ChatConversationRepository::count($chatSearch !== '' ? $chatSearch : null);
    $chatPages = max(1, (int) ceil($chatTotal / 20));
    $chats = ChatConversationRepository::paginate($chatPage, 20, $chatSearch !== '' ? $chatSearch : null);
}

require dirname(__DIR__) . '/includes/header.php';
?>

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success"><?= Helpers::e($message) ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-error"><?= Helpers::e($errors[0]) ?></div>
<?php endif; ?>

<nav class="chat-tabs" aria-label="Sections de l’agent">
    <a href="/admin/chat/index.php?tab=settings" class="<?= $tab === 'settings' ? 'is-active' : '' ?>">Paramètres</a>
    <a href="/admin/chat/index.php?tab=instructions" class="<?= $tab === 'instructions' ? 'is-active' : '' ?>">Instructions</a>
    <a href="/admin/chat/index.php?tab=products" class="<?= $tab === 'products' ? 'is-active' : '' ?>">Catalogue produits</a>
    <a href="/admin/chat/index.php?tab=chats" class="<?= $tab === 'chats' ? 'is-active' : '' ?>">Conversations</a>
</nav>

<?php if ($tab === 'settings'): ?>
<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Paramètres de l’agent</h2>
            <p class="text-muted">Conseiller OpenAI sur le site : il cherche dans le catalogue à chaque message, sans envoyer tout le tableau.</p>
        </div>
        <span class="status-pill <?= ($settings['chat_enabled'] === '1' && $hasKey) ? 'is-on' : 'is-off' ?>">
            <?= ($settings['chat_enabled'] === '1' && $hasKey) ? 'Actif' : 'Inactif' ?>
        </span>
    </div>

    <?php if (!$hasKey): ?>
        <div class="alert alert-error">Aucune clé OpenAI. Collez-la ci-dessous ou dans le fichier <code>.env</code> (<code>OPENAI_API_KEY</code>).</div>
    <?php endif; ?>

    <form method="post" class="form-grid">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="tab" value="settings">

        <label class="checkbox-label">
            <input type="checkbox" name="chat_enabled" value="1" <?= $settings['chat_enabled'] === '1' ? 'checked' : '' ?>>
            Activer le chat sur le site
        </label>

        <div class="form-row">
            <label>Nom de l’agent
                <input type="text" name="chat_agent_name" value="<?= Helpers::e($settings['chat_agent_name']) ?>">
            </label>
            <label>Modèle OpenAI
                <select name="chat_openai_model">
                    <?php foreach ($models as $model): ?>
                        <option value="<?= Helpers::e($model) ?>" <?= $settings['chat_openai_model'] === $model ? 'selected' : '' ?>><?= Helpers::e($model) ?></option>
                    <?php endforeach; ?>
                    <?php if ($settings['chat_openai_model'] !== '' && !in_array($settings['chat_openai_model'], $models, true)): ?>
                        <option value="<?= Helpers::e($settings['chat_openai_model']) ?>" selected><?= Helpers::e($settings['chat_openai_model']) ?></option>
                    <?php endif; ?>
                </select>
            </label>
        </div>

        <label>Clé API OpenAI
            <input type="password" name="chat_openai_api_key" value="" autocomplete="new-password" placeholder="<?= $hasKey ? Helpers::e('Enregistrée : ' . $maskedKey . ' — laisser vide pour conserver') : 'sk-…' ?>">
            <small class="field-hint">Jamais exposée sur le site public. Priorité : ce champ, sinon <code>.env</code>.</small>
        </label>
        <?php if ($hasKey && trim((string) $settings['chat_openai_api_key']) !== ''): ?>
            <label class="checkbox-label">
                <input type="checkbox" name="chat_clear_api_key" value="1">
                Supprimer la clé du dashboard (revenir au .env)
            </label>
        <?php endif; ?>

        <div class="form-row">
            <label>Température (0–1,2)
                <input type="number" name="chat_temperature" step="0.1" min="0" max="1.2" value="<?= Helpers::e($settings['chat_temperature']) ?>">
            </label>
            <label>Tokens max. par réponse
                <input type="number" name="chat_max_tokens" min="120" max="1200" value="<?= Helpers::e($settings['chat_max_tokens']) ?>">
            </label>
            <label>Messages d’historique
                <input type="number" name="chat_history_limit" min="2" max="20" value="<?= Helpers::e($settings['chat_history_limit']) ?>">
            </label>
            <label>Produits envoyés à l’IA
                <input type="number" name="chat_product_limit" min="1" max="8" value="<?= Helpers::e($settings['chat_product_limit']) ?>">
            </label>
        </div>

        <label class="checkbox-label">
            <input type="checkbox" name="chat_include_prices" value="1" <?= $settings['chat_include_prices'] === '1' ? 'checked' : '' ?>>
            Inclure les prix du catalogue dans le contexte
        </label>
        <label class="checkbox-label">
            <input type="checkbox" name="chat_include_stock" value="1" <?= $settings['chat_include_stock'] === '1' ? 'checked' : '' ?>>
            Inclure la disponibilité (stock / rupture)
        </label>

        <label>Message d’accueil
            <textarea name="chat_welcome_message" rows="3"><?= Helpers::e($settings['chat_welcome_message']) ?></textarea>
        </label>
        <label>Message hors ligne / erreur
            <textarea name="chat_offline_message" rows="2"><?= Helpers::e($settings['chat_offline_message']) ?></textarea>
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="/" class="btn btn-outline" target="_blank" rel="noopener">Tester sur le site</a>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if ($tab === 'instructions'): ?>
<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Instructions (system messages)</h2>
            <p class="text-muted">Ces textes sont envoyés à OpenAI à chaque message, avec les infos société (adresse, horaires, WhatsApp) et les produits trouvés.</p>
        </div>
    </div>
    <form method="post" class="form-grid">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="tab" value="instructions">

        <label class="checkbox-label">
            <input type="checkbox" name="chat_multilingual" value="1" <?= $settings['chat_multilingual'] === '1' ? 'checked' : '' ?>>
            Mode multilingue (sinon réponses en français uniquement)
        </label>

        <label>Rôle commercial (system principal)
            <textarea name="chat_system_prompt" rows="8"><?= Helpers::e($settings['chat_system_prompt']) ?></textarea>
        </label>
        <label>Si aucune réponse / info manquante
            <textarea name="chat_instruction_no_answer" rows="4"><?= Helpers::e($settings['chat_instruction_no_answer']) ?></textarea>
        </label>
        <label>En cas d’insulte ou d’agression
            <textarea name="chat_instruction_insult" rows="4"><?= Helpers::e($settings['chat_instruction_insult']) ?></textarea>
        </label>
        <label>Langue (si mode multilingue)
            <textarea name="chat_instruction_language" rows="3"><?= Helpers::e($settings['chat_instruction_language']) ?></textarea>
        </label>
        <label>Transfert humain (WhatsApp, contact)
            <textarea name="chat_instruction_handoff" rows="4"><?= Helpers::e($settings['chat_instruction_handoff']) ?></textarea>
        </label>
        <label>Consignes supplémentaires
            <textarea name="chat_instruction_extra" rows="4" placeholder="Ex. ne jamais parler des concurrents, privilégier le retrait magasin…"><?= Helpers::e($settings['chat_instruction_extra']) ?></textarea>
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer les instructions</button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if ($tab === 'products'): ?>
<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Catalogue visible par l’agent</h2>
            <p class="text-muted">
                <?= (int) ($productsResult['total'] ?? 0) ?> produit<?= ((int) ($productsResult['total'] ?? 0)) > 1 ? 's' : '' ?> actif<?= ((int) ($productsResult['total'] ?? 0)) > 1 ? 's' : '' ?>.
                L’agent ne reçoit que les fiches correspondant à la question du client (<?= Helpers::e($settings['chat_product_limit']) ?> max.).
            </p>
        </div>
        <form method="get" class="panel-actions">
            <input type="hidden" name="tab" value="products">
            <input type="search" name="q" value="<?= Helpers::e($productSearch) ?>" placeholder="Nom, SKU, marque…">
            <button type="submit" class="btn btn-outline btn-sm">Filtrer</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table data-table-compact">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Marque</th>
                    <th>SKU</th>
                    <th>Prix</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productsResult['items'] as $product): ?>
                    <?php $pricing = Helpers::productPricing($product); ?>
                    <tr>
                        <td><?= Helpers::e((string) $product['name']) ?></td>
                        <td><?= Helpers::e((string) ($product['brand'] ?? '')) ?></td>
                        <td><?= Helpers::e((string) ($product['sku'] ?? '')) ?></td>
                        <td><?= Helpers::e(Helpers::formatPrice((float) $pricing['current'])) ?></td>
                        <td><a href="<?= Helpers::e(Helpers::productUrl($product)) ?>" target="_blank" rel="noopener">Fiche</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (($productsResult['items'] ?? []) === []): ?>
                    <tr><td colspan="5" class="text-muted">Aucun produit actif.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ((int) ($productsResult['pages'] ?? 1) > 1): ?>
        <p class="text-muted" style="margin-top:1rem">
            Page <?= (int) $productPage ?> / <?= (int) $productsResult['pages'] ?>
            <?php if ($productPage > 1): ?>
                — <a href="/admin/chat/index.php?tab=products&amp;p=<?= $productPage - 1 ?>&amp;q=<?= Helpers::e($productSearch) ?>">Précédent</a>
            <?php endif; ?>
            <?php if ($productPage < (int) $productsResult['pages']): ?>
                — <a href="/admin/chat/index.php?tab=products&amp;p=<?= $productPage + 1 ?>&amp;q=<?= Helpers::e($productSearch) ?>">Suivant</a>
            <?php endif; ?>
        </p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($tab === 'chats'): ?>
<div class="panel">
    <div class="panel-header">
        <div>
            <h2>Conversations</h2>
            <p class="text-muted"><?= (int) $chatTotal ?> conversation<?= $chatTotal > 1 ? 's' : '' ?> enregistrée<?= $chatTotal > 1 ? 's' : '' ?>.</p>
        </div>
        <div class="panel-actions">
            <form method="get" class="panel-actions">
                <input type="hidden" name="tab" value="chats">
                <input type="search" name="cq" value="<?= Helpers::e($chatSearch) ?>" placeholder="Rechercher dans les messages…">
                <button type="submit" class="btn btn-outline btn-sm">Filtrer</button>
            </form>
            <a class="btn btn-sm" href="/admin/chat/export.php?format=json<?= $chatSearch !== '' ? '&amp;q=' . rawurlencode($chatSearch) : '' ?>">Tout exporter (JSON)</a>
            <a class="btn btn-sm btn-outline" href="/admin/chat/export.php?format=csv<?= $chatSearch !== '' ? '&amp;q=' . rawurlencode($chatSearch) : '' ?>">CSV</a>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table data-table-compact">
            <thead>
                <tr>
                    <th>Début</th>
                    <th>Visiteur</th>
                    <th>Dernier message</th>
                    <th>Échanges</th>
                    <th>Page</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($chats as $conv): ?>
                    <?php
                    $cid = (int) $conv['id'];
                    $preview = ChatConversationRepository::firstUserMessage($cid);
                    ?>
                    <tr>
                        <td><?= Helpers::e($preview !== '' ? $preview : '—') ?></td>
                        <td><?= Helpers::e(VisitorInfo::shortLabel($conv)) ?></td>
                        <td><?= Helpers::e((string) ($conv['last_message_at'] ?? $conv['created_at'] ?? '')) ?></td>
                        <td><?= (int) ($conv['message_count'] ?? 0) ?></td>
                        <td class="text-muted"><?= Helpers::e(mb_substr((string) ($conv['page_url'] ?? ''), 0, 48)) ?></td>
                        <td class="panel-actions">
                            <a class="btn btn-sm" href="/admin/chat/conversation.php?id=<?= $cid ?>">Ouvrir</a>
                            <a class="btn btn-sm btn-outline" href="/admin/chat/export.php?id=<?= $cid ?>&amp;format=json">Exporter</a>
                            <form method="post" onsubmit="return confirm('Supprimer cette conversation ?');">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="action" value="delete_chat">
                                <input type="hidden" name="id" value="<?= $cid ?>">
                                <button type="submit" class="btn btn-sm btn-outline">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($chats === []): ?>
                    <tr><td colspan="6" class="text-muted">Aucune conversation pour le moment.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($chatPages > 1): ?>
        <p class="text-muted" style="margin-top:1rem">
            Page <?= (int) $chatPage ?> / <?= (int) $chatPages ?>
            <?php if ($chatPage > 1): ?>
                — <a href="/admin/chat/index.php?tab=chats&amp;cp=<?= $chatPage - 1 ?>&amp;cq=<?= Helpers::e($chatSearch) ?>">Précédent</a>
            <?php endif; ?>
            <?php if ($chatPage < $chatPages): ?>
                — <a href="/admin/chat/index.php?tab=chats&amp;cp=<?= $chatPage + 1 ?>&amp;cq=<?= Helpers::e($chatSearch) ?>">Suivant</a>
            <?php endif; ?>
        </p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
