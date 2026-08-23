<?php

declare(strict_types=1);

class CustomerCartRepository
{
    /** @return list<array<string, mixed>> */
    public static function getItems(int $customerId): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT items_json FROM customer_carts WHERE customer_id = :id'
        );
        $stmt->execute(['id' => $customerId]);
        $row = $stmt->fetch();
        if (!$row) {
            return [];
        }

        $decoded = json_decode((string) ($row['items_json'] ?? '[]'), true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, [self::class, 'isValidItem']));
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    public static function saveItems(int $customerId, array $items): void
    {
        $clean = array_values(array_filter($items, [self::class, 'isValidItem']));
        $json = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '[]';
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO customer_carts (customer_id, items_json, updated_at)
             VALUES (:id, :items, datetime(\'now\'))
             ON CONFLICT(customer_id) DO UPDATE SET
                items_json = excluded.items_json,
                updated_at = datetime(\'now\')'
        );
        $stmt->execute([
            'id' => $customerId,
            'items' => $json,
        ]);
    }

    /**
     * Fusionne le panier local avec le panier serveur (qty max par produit).
     *
     * @param list<array<string, mixed>> $local
     * @param list<array<string, mixed>> $remote
     * @return list<array<string, mixed>>
     */
    public static function merge(array $local, array $remote): array
    {
        $map = [];
        foreach (array_merge($remote, $local) as $item) {
            if (!self::isValidItem($item)) {
                continue;
            }
            $id = (int) $item['id'];
            if (!isset($map[$id])) {
                $map[$id] = [
                    'id' => $id,
                    'slug' => (string) ($item['slug'] ?? ''),
                    'name' => (string) $item['name'],
                    'price' => (float) ($item['price'] ?? 0),
                    'image' => (string) ($item['image'] ?? ''),
                    'brand' => (string) ($item['brand'] ?? ''),
                    'qty' => (int) $item['qty'],
                ];
                continue;
            }
            $map[$id]['qty'] = max((int) $map[$id]['qty'], (int) $item['qty']);
            if (!empty($item['image'])) {
                $map[$id]['image'] = (string) $item['image'];
            }
            if (!empty($item['slug'])) {
                $map[$id]['slug'] = (string) $item['slug'];
            }
            if (isset($item['price'])) {
                $map[$id]['price'] = (float) $item['price'];
            }
        }

        return array_values($map);
    }

    /** @param mixed $item */
    public static function isValidItem($item): bool
    {
        return is_array($item)
            && (int) ($item['id'] ?? 0) > 0
            && is_string($item['name'] ?? null)
            && trim((string) $item['name']) !== ''
            && (float) ($item['price'] ?? -1) >= 0
            && (int) ($item['qty'] ?? 0) > 0;
    }
}
