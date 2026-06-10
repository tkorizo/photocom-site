<?php

declare(strict_types=1);

function adminImageField(string $name, string $label, ?string $currentPath = null): void
{
    $hasImage = $currentPath && $currentPath !== '';
    $uid = preg_replace('/[^a-z0-9_]/', '_', $name);
    ?>
    <div class="image-upload-field" data-field="<?= Helpers::e($name) ?>">
        <span class="image-upload-label"><?= Helpers::e($label) ?></span>
        <div class="image-upload-preview <?= $hasImage ? '' : 'is-empty' ?>" id="preview_<?= $uid ?>">
            <?php if ($hasImage): ?>
                <img src="<?= Helpers::e($currentPath) ?>" alt="">
            <?php else: ?>
                <span class="image-placeholder">Aucune image</span>
            <?php endif; ?>
        </div>
        <input type="hidden" name="<?= Helpers::e($name) ?>_current" value="<?= Helpers::e($currentPath ?? '') ?>">
        <input type="hidden" name="<?= Helpers::e($name) ?>_delete" value="0" class="image-delete-flag">
        <input type="file" name="<?= Helpers::e($name) ?>_file" accept="image/jpeg,image/png,image/gif,image/webp" class="image-file-input" id="file_<?= $uid ?>">
        <div class="image-upload-actions">
            <button type="button" class="btn btn-sm btn-primary image-btn-upload" data-target="file_<?= $uid ?>">Charger</button>
            <button type="button" class="btn btn-sm image-btn-change" data-target="file_<?= $uid ?>" <?= $hasImage ? '' : 'style="display:none"' ?>>Changer</button>
            <button type="button" class="btn btn-sm btn-danger image-btn-delete" <?= $hasImage ? '' : 'style="display:none"' ?>>Supprimer</button>
        </div>
    </div>
    <?php
}
