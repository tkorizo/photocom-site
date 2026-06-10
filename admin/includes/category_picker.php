<?php

declare(strict_types=1);

/**
 * @param array<int, array<string, mixed>> $tree
 * @param array<int, int> $selectedIds
 * @param array<int, array{id: int, name: string, parent_name: ?string}> $selectedLabels
 */
function adminCategoryPicker(array $tree, array $selectedIds, array $selectedLabels): void
{
    $hasSelection = !empty($selectedLabels);
    ?>
    <div class="cat-picker" data-cat-picker>
        <div class="cat-picker-field">
            <span class="cat-picker-field-label">Catégories sélectionnées</span>
            <div class="cat-picker-chips <?= $hasSelection ? '' : 'is-empty' ?>" data-cat-chips>
                <?php if ($hasSelection): ?>
                    <?php foreach ($selectedLabels as $item): ?>
                        <span class="cat-chip" data-cat-id="<?= (int) $item['id'] ?>">
                            <?php if (!empty($item['parent_name'])): ?>
                                <small><?= Helpers::e($item['parent_name']) ?> ›</small>
                            <?php endif; ?>
                            <?= Helpers::e($item['name']) ?>
                        </span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="cat-chip-placeholder">Aucune catégorie — ouvrez une section ci-dessous</span>
                <?php endif; ?>
            </div>
        </div>

        <input type="search" class="cat-picker-search" placeholder="Rechercher une catégorie…" data-cat-search autocomplete="off">

        <div class="cat-accordion">
            <?php foreach ($tree as $root): ?>
                <?php
                $rootId = (int) $root['id'];
                $children = $root['children'] ?? [];
                $hasChildren = !empty($children);
                $rootSelected = in_array($rootId, $selectedIds, true);
                $childSelected = false;
                foreach ($children as $child) {
                    if (in_array((int) $child['id'], $selectedIds, true)) {
                        $childSelected = true;
                        break;
                    }
                }
                $openByDefault = $rootSelected || $childSelected;
                ?>

                <?php if ($hasChildren): ?>
                    <details class="cat-accordion-item" data-cat-group="<?= Helpers::e(mb_strtolower($root['name'])) ?>" <?= $openByDefault ? 'open' : '' ?>>
                        <summary class="cat-accordion-header">
                            <span class="cat-accordion-chevron">›</span>
                            <span class="cat-accordion-title"><?= Helpers::e($root['name']) ?></span>
                            <span class="cat-accordion-count"><?= count($children) ?> sous-catégorie<?= count($children) > 1 ? 's' : '' ?></span>
                        </summary>
                        <div class="cat-accordion-body">
                            <label class="cat-option cat-option-parent">
                                <input type="checkbox" name="category_ids[]" value="<?= $rootId ?>"
                                    data-cat-name="<?= Helpers::e($root['name']) ?>"
                                    data-cat-parent=""
                                    <?= $rootSelected ? 'checked' : '' ?>>
                                <span>Toute la catégorie « <?= Helpers::e($root['name']) ?> »</span>
                            </label>
                            <?php foreach ($children as $child): ?>
                                <?php $childId = (int) $child['id']; ?>
                                <label class="cat-option" data-cat-name="<?= Helpers::e(mb_strtolower($child['name'])) ?>">
                                    <input type="checkbox" name="category_ids[]" value="<?= $childId ?>"
                                        data-cat-name="<?= Helpers::e($child['name']) ?>"
                                        data-cat-parent="<?= Helpers::e($root['name']) ?>"
                                        <?= in_array($childId, $selectedIds, true) ? 'checked' : '' ?>>
                                    <span><?= Helpers::e($child['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php else: ?>
                    <div class="cat-accordion-leaf" data-cat-group="<?= Helpers::e(mb_strtolower($root['name'])) ?>">
                        <label class="cat-option">
                            <input type="checkbox" name="category_ids[]" value="<?= $rootId ?>"
                                data-cat-name="<?= Helpers::e($root['name']) ?>"
                                data-cat-parent=""
                                <?= $rootSelected ? 'checked' : '' ?>>
                            <span><?= Helpers::e($root['name']) ?></span>
                        </label>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
