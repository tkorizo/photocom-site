<?php

declare(strict_types=1);

/**
 * Journal d’audit réservé au super administrateur (fichier JSON Lines).
 */
class AuditLog
{
    public static function directory(): string
    {
        return ROOT_PATH . '/storage/logs';
    }

    public static function path(): string
    {
        return self::directory() . '/admin-audit.log';
    }

    public static function ensureReady(): void
    {
        $dir = self::directory();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $keep = $dir . '/.gitkeep';
        if (!is_file($keep)) {
            file_put_contents($keep, '');
        }
    }

    /**
     * @param array<string, mixed> $meta
     * @param array{id?:int,email?:string,name?:string,role?:string}|null $actorOverride
     */
    public static function record(
        string $action,
        string $entity = '',
        ?int $entityId = null,
        array $meta = [],
        ?array $actorOverride = null
    ): void {
        try {
            self::ensureReady();
            $actor = $actorOverride ?? Auth::user();
            $entry = [
                'at' => date('c'),
                'action' => $action,
                'entity' => $entity,
                'entity_id' => $entityId,
                'user_id' => $actor['id'] ?? null,
                'user_name' => $actor['name'] ?? null,
                'user_email' => $actor['email'] ?? null,
                'user_role' => isset($actor['role']) ? UserRepository::normalizeRole((string) $actor['role']) : null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'meta' => $meta !== [] ? $meta : null,
            ];
            file_put_contents(
                self::path(),
                json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        } catch (Throwable) {
            // Ne jamais bloquer l’action métier si le log échoue.
        }
    }

    /** @return list<array<string, mixed>> */
    public static function recent(int $limit = 300): array
    {
        $path = self::path();
        if (!is_file($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false || $lines === []) {
            return [];
        }

        $slice = array_slice($lines, -$limit);
        $entries = [];
        foreach (array_reverse($slice) as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $entries[] = $decoded;
            }
        }

        return $entries;
    }

    public static function actionLabel(string $action): string
    {
        return match ($action) {
            'auth.login' => 'Connexion',
            'auth.logout' => 'Déconnexion',
            'product.create' => 'Produit créé',
            'product.update' => 'Produit modifié',
            'product.delete' => 'Produit supprimé',
            'product.quick_update' => 'Produit (édition rapide)',
            'category.create' => 'Catégorie créée',
            'category.update' => 'Catégorie modifiée',
            'category.delete' => 'Catégorie supprimée',
            'brand.create' => 'Marque ajoutée',
            'brand.update' => 'Marque modifiée',
            'brand.delete' => 'Marque supprimée',
            'article.create' => 'Article créé',
            'article.update' => 'Article modifié',
            'article.delete' => 'Article supprimé',
            'page.update' => 'Page modifiée',
            'home.update' => 'Page d’accueil modifiée',
            'settings.site' => 'Infos du site',
            'settings.cookies' => 'Paramètres cookies',
            'settings.chat' => 'Paramètres chat',
            'settings.files' => 'Fichiers médias',
            'user.create' => 'Utilisateur créé',
            'user.update' => 'Utilisateur modifié',
            'user.delete' => 'Utilisateur supprimé',
            default => $action,
        };
    }
}
