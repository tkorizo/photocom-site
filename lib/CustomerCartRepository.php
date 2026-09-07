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

    public static function itemsCount(array $items): int
    {
        $n = 0;
        foreach ($items as $item) {
            if (self::isValidItem($item)) {
                $n += (int) $item['qty'];
            }
        }

        return $n;
    }

    public static function itemsTotal(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            if (self::isValidItem($item)) {
                $total += (float) $item['price'] * (int) $item['qty'];
            }
        }

        return $total;
    }

    /**
     * Paniers non vides, avec infos client (dashboard admin).
     *
     * @return list<array{customer: array<string,mixed>, items: list<array<string,mixed>>, count: int, total: float, updated_at: string}>
     */
    public static function listNonEmpty(?int $limit = null): array
    {
        $sql = 'SELECT c.id, c.name, c.email, c.phone, c.is_active,
                       cc.items_json, cc.updated_at AS cart_updated_at
                FROM customer_carts cc
                INNER JOIN customers c ON c.id = cc.customer_id
                ORDER BY cc.updated_at DESC';

        $rows = Database::getInstance()->query($sql)->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $items = json_decode((string) ($row['items_json'] ?? '[]'), true);
            $items = is_array($items) ? array_values(array_filter($items, [self::class, 'isValidItem'])) : [];
            if ($items === []) {
                continue;
            }
            $out[] = [
                'customer' => [
                    'id' => (int) $row['id'],
                    'name' => (string) $row['name'],
                    'email' => (string) $row['email'],
                    'phone' => (string) ($row['phone'] ?? ''),
                    'is_active' => (int) ($row['is_active'] ?? 0),
                ],
                'items' => $items,
                'count' => self::itemsCount($items),
                'total' => self::itemsTotal($items),
                'updated_at' => (string) ($row['cart_updated_at'] ?? ''),
            ];
            if ($limit !== null && count($out) >= max(1, min(100, $limit))) {
                break;
            }
        }

        return $out;
    }

    /**
     * @return array<int, array{count: int, total: float, updated_at: string}>
     */
    public static function summariesByCustomerId(): array
    {
        $map = [];
        foreach (self::listNonEmpty() as $cart) {
            $id = (int) $cart['customer']['id'];
            $map[$id] = [
                'count' => (int) $cart['count'],
                'total' => (float) $cart['total'],
                'updated_at' => (string) $cart['updated_at'],
            ];
        }

        return $map;
    }

    public static function countNonEmpty(): int
    {
        return count(self::listNonEmpty());
    }

    public static function whatsappDigits(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?: '';
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '0')) {
            $digits = '212' . substr($digits, 1);
        }

        return $digits;
    }

    public static function contactMessage(array $customer, array $items): string
    {
        $name = trim((string) ($customer['name'] ?? ''));
        $lines = [
            'Bonjour' . ($name !== '' ? ' ' . $name : '') . ',',
            '',
            'Je vous contacte au sujet de votre panier PHOTOCOM :',
            '',
        ];
        foreach ($items as $i => $item) {
            if (!self::isValidItem($item)) {
                continue;
            }
            $line = (float) $item['price'] * (int) $item['qty'];
            $lines[] = ($i + 1) . '. ' . $item['name']
                . ' × ' . (int) $item['qty']
                . ' — ' . Helpers::formatPrice($line);
        }
        $lines[] = '';
        $lines[] = 'Total : ' . Helpers::formatPrice(self::itemsTotal($items));
        $lines[] = '';
        $lines[] = 'Nous restons à votre disposition pour finaliser la commande.';

        return implode("\n", $lines);
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
