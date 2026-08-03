<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();

$pageTitle = 'Fichiers';
$currentPage = 'files';
$errors = [];

$folder = trim((string) ($_GET['folder'] ?? ''));
if ($folder !== '' && !in_array($folder, MediaLibrary::folders(), true)) {
    $folder = '';
}
$search = trim((string) ($_GET['search'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));

$queryParams = static function () use ($folder, $search, $page): array {
    return array_filter([
        'folder' => $folder ?: null,
        'search' => $search ?: null,
        'page' => $page > 1 ? $page : null,
    ]);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        try {
            if ($action === 'upload') {
                $targetFolder = (string) ($_POST['folder'] ?? 'home');
                if (empty($_FILES['media_file']['name'])) {
                    throw new RuntimeException('Choisissez un fichier à envoyer.');
                }
                $url = MediaLibrary::upload($_FILES['media_file'], $targetFolder);
                AuditLog::record('settings.files', 'file', null, ['action' => 'upload', 'url' => $url]);
                Helpers::flash('success', 'Fichier ajouté : ' . basename($url));
            } elseif ($action === 'delete') {
                $url = (string) ($_POST['file_url'] ?? '');
                if (!MediaLibrary::deleteByUrl($url)) {
                    throw new RuntimeException('Impossible de supprimer ce fichier.');
                }
                AuditLog::record('settings.files', 'file', null, ['action' => 'delete', 'url' => $url]);
                Helpers::flash('success', 'Fichier supprimé.');
            } elseif ($action === 'delete_selected') {
                $urls = (array) ($_POST['file_urls'] ?? []);
                $deleted = 0;
                foreach ($urls as $url) {
                    if (MediaLibrary::deleteByUrl((string) $url)) {
                        $deleted++;
                    }
                }
                AuditLog::record('settings.files', 'file', null, ['action' => 'delete_selected', 'count' => $deleted]);
                Helpers::flash('success', $deleted . ' fichier' . ($deleted > 1 ? 's' : '') . ' supprimé' . ($deleted > 1 ? 's' : '') . '.');
            }
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }

        if ($errors === []) {
            $qs = http_build_query(array_filter([
                'folder' => $folder ?: null,
                'search' => $search ?: null,
            ]));
            Helpers::redirect('/admin/settings/files.php' . ($qs !== '' ? '?' . $qs : ''));
        }
    }
}

$result = MediaLibrary::paginate($folder !== '' ? $folder : null, $search !== '' ? $search : null, $page, 40);
$files = $result['data'];

require dirname(__DIR__) . '/includes/header.php';
?>

<?php if ($message = Helpers::flash('success')): ?>
    <div class="alert alert-success"><?= Helpers::e($message) ?></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $error): ?>
            <p><?= Helpers::e($error) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header files-toolbar">
        <div>
            <p class="text-muted" style="margin:0">
                <?= (int) $result['total'] ?> fichier<?= $result['total'] > 1 ? 's' : '' ?>
                · <?= Helpers::e(MediaLibrary::formatSize((int) $result['total_size'])) ?>
            </p>
        </div>
        <form method="get" class="search-form files-filter-form">
            <select name="folder">
                <option value="">Tous les dossiers</option>
                <?php foreach (MediaLibrary::folders() as $dir): ?>
                    <option value="<?= Helpers::e($dir) ?>" <?= $folder === $dir ? 'selected' : '' ?>>
                        <?= Helpers::e(MediaLibrary::folderLabel($dir)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="search" name="search" value="<?= Helpers::e($search) ?>" placeholder="Rechercher un fichier…">
            <button type="submit" class="btn">Filtrer</button>
        </form>
    </div>

    <form method="post" enctype="multipart/form-data" class="files-upload-box">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="action" value="upload">
        <div class="files-upload-row">
            <label class="files-upload-folder">Dossier
                <select name="folder" required>
                    <?php foreach (MediaLibrary::folders() as $dir): ?>
                        <option value="<?= Helpers::e($dir) ?>" <?= ($folder ?: 'home') === $dir ? 'selected' : '' ?>>
                            <?= Helpers::e(MediaLibrary::folderLabel($dir)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="files-upload-input">
                <span class="btn btn-primary">Ajouter un fichier</span>
                <input type="file" name="media_file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml,image/x-icon,.ico,video/mp4,video/webm" required onchange="this.form.submit()">
            </label>
        </div>
        <p class="field-hint">Images (JPG, PNG, WebP, SVG, GIF, ICO) jusqu’à 5 Mo · Vidéos (MP4, WebM) jusqu’à 50 Mo</p>
    </form>
</div>

<div class="panel">
    <?php if ($files === []): ?>
        <p class="empty-state">Aucun fichier dans ce dossier.</p>
    <?php else: ?>
        <form method="post" id="files-bulk-form">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="action" value="delete_selected">
            <div class="files-bulk-bar">
                <label class="checkbox-label compact">
                    <input type="checkbox" id="files-select-all"> Tout sélectionner
                </label>
                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer les fichiers sélectionnés ?');">Supprimer la sélection</button>
            </div>
        </form>

        <div class="table-scroll">
            <table class="data-table files-table">
                <thead>
                    <tr>
                        <th style="width:36px"></th>
                        <th style="width:64px">Aperçu</th>
                        <th>Nom</th>
                        <th>Dossier</th>
                        <th>Taille</th>
                        <th>Modifié</th>
                        <th style="width:140px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $file): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="file_urls[]" value="<?= Helpers::e($file['url']) ?>" class="files-row-check" form="files-bulk-form">
                            </td>
                            <td>
                                <?php if ($file['is_image']): ?>
                                    <a href="<?= Helpers::e($file['url']) ?>" target="_blank" rel="noopener">
                                        <img src="<?= Helpers::e($file['url']) ?>" alt="" class="files-thumb">
                                    </a>
                                <?php else: ?>
                                    <span class="files-thumb files-thumb--file"><?= Helpers::e(strtoupper($file['ext'] !== '' ? $file['ext'] : 'FILE')) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= Helpers::e($file['url']) ?>" target="_blank" rel="noopener" class="files-name">
                                    <?= Helpers::e($file['name']) ?>
                                </a>
                                <div class="files-url text-muted"><?= Helpers::e($file['url']) ?></div>
                            </td>
                            <td><?= Helpers::e(MediaLibrary::folderLabel($file['folder'])) ?></td>
                            <td><?= Helpers::e(MediaLibrary::formatSize($file['size'])) ?></td>
                            <td><small><?= Helpers::e(date('d/m/Y H:i', $file['modified'])) ?></small></td>
                            <td class="actions">
                                <a href="<?= Helpers::e($file['url']) ?>" class="btn btn-sm" target="_blank" rel="noopener">Voir</a>
                                <form method="post" class="inline-form" onsubmit="return confirm('Supprimer ce fichier ?');">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="file_url" value="<?= Helpers::e($file['url']) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Suppr.</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($result['total_pages'] > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $result['total_pages']; $i++): ?>
                    <a href="?<?= Helpers::e(http_build_query(array_filter([
                        'page' => $i,
                        'folder' => $folder ?: null,
                        'search' => $search ?: null,
                    ]))) ?>" class="<?= $i === $result['page'] ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
(function () {
    var selectAll = document.getElementById('files-select-all');
    if (!selectAll) return;
    selectAll.addEventListener('change', function () {
        document.querySelectorAll('.files-row-check').forEach(function (cb) {
            cb.checked = selectAll.checked;
        });
    });
})();
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
