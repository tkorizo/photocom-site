<?php

declare(strict_types=1);

/**
 * @param array<int, array{label: string, value: int|string, variant?: string, hint?: string, url?: string, key?: string}> $items
 * @param array{status?: string, filter?: string} $active
 */
function adminKpiBar(array $items, array $active = []): void
{
    if (empty($items)) {
        return;
    }

    $activeStatus = $active['status'] ?? '';
    $activeFilter = $active['filter'] ?? '';
    ?>
    <div class="kpi-bar">
        <?php foreach ($items as $item): ?>
            <?php
            $variant = $item['variant'] ?? 'default';
            $isActive = false;
            if (!empty($item['key'])) {
                if (str_starts_with($item['key'], 'status:')) {
                    $isActive = $activeStatus === substr($item['key'], 7);
                } elseif (str_starts_with($item['key'], 'filter:')) {
                    $isActive = $activeFilter === substr($item['key'], 7);
                } elseif ($item['key'] === 'all') {
                    $isActive = $activeStatus === '' && $activeFilter === '';
                }
            }
            $class = 'kpi-card kpi-' . Helpers::e($variant) . ($isActive ? ' is-active' : '');
            $hasUrl = !empty($item['url']);
            if ($hasUrl) {
                $class .= ' kpi-link';
            }
            ?>
            <?php if ($hasUrl): ?>
                <a href="<?= Helpers::e($item['url']) ?>" class="<?= $class ?>">
            <?php else: ?>
                <div class="<?= $class ?>">
            <?php endif; ?>
                <span class="kpi-label"><?= Helpers::e($item['label']) ?></span>
                <strong class="kpi-value"><?= Helpers::e((string) $item['value']) ?></strong>
                <?php if (!empty($item['hint'])): ?>
                    <small class="kpi-hint"><?= Helpers::e($item['hint']) ?></small>
                <?php endif; ?>
                <?php if ($hasUrl): ?>
                    <small class="kpi-action">Voir la liste →</small>
                <?php endif; ?>
            <?php if ($hasUrl): ?>
                </a>
            <?php else: ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php
}

function adminActiveFilterBar(string $label, string $resetUrl, int $count): void
{
    ?>
    <div class="filter-active-bar">
        <span>Filtre actif : <strong><?= Helpers::e($label) ?></strong> (<?= $count ?> résultat<?= $count > 1 ? 's' : '' ?>)</span>
        <a href="<?= Helpers::e($resetUrl) ?>" class="btn btn-sm">Réinitialiser</a>
    </div>
    <?php
}
