<?php

declare(strict_types=1);

class OrderRepository
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Envoyée (WhatsApp)',
            self::STATUS_PROCESSING => 'En traitement',
            self::STATUS_COMPLETED => 'Terminée',
            self::STATUS_CANCELLED => 'Annulée',
        ];
    }

    public static function statusLabel(string $status): string
    {
        $all = self::statuses();

        return $all[$status] ?? $status;
    }

    /**
     * @param list<array<string, mixed>> $rawItems
     * @return array{id:int,reference:string,status:string,items:list<array<string,mixed>>,total:float,created_at:string}
     */
    public static function createFromCart(int $customerId, array $rawItems): array
    {
        $customer = CustomerRepository::find($customerId);
        if (!$customer || (int) ($customer['is_active'] ?? 0) !== 1) {
            throw new RuntimeException('Compte client introuvable ou désactivé.');
        }

        $items = self::snapshotItems($rawItems);
        if ($items === []) {
            throw new RuntimeException('Votre panier ne contient aucun produit disponible.');
        }

        $total = 0.0;
        foreach ($items as $item) {
            $total += (float) $item['price'] * (int) $item['qty'];
        }

        $reference = self::newReference();
        $stmt = Database::getInstance()->prepare(
            'INSERT INTO customer_orders (
                customer_id, reference, status, items_json, total,
                customer_name, customer_email, customer_phone, created_at, updated_at
            ) VALUES (
                :customer_id, :reference, :status, :items_json, :total,
                :customer_name, :customer_email, :customer_phone, datetime(\'now\'), datetime(\'now\')
            )'
        );
        $stmt->execute([
            'customer_id' => $customerId,
            'reference' => $reference,
            'status' => self::STATUS_PENDING,
            'items_json' => json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'total' => $total,
            'customer_name' => (string) ($customer['name'] ?? ''),
            'customer_email' => (string) ($customer['email'] ?? ''),
            'customer_phone' => (string) ($customer['phone'] ?? ''),
        ]);

        $order = self::findByReference($reference);
        if (!$order) {
            throw new RuntimeException('Commande introuvable après enregistrement.');
        }

        return $order;
    }

    /** @return list<array<string, mixed>> */
    public static function forCustomer(int $customerId, int $limit = 50): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM customer_orders WHERE customer_id = :id ORDER BY created_at DESC, id DESC LIMIT :limit'
        );
        $stmt->bindValue(':id', $customerId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, min(100, $limit)), PDO::PARAM_INT);
        $stmt->execute();

        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function findByReference(string $reference, ?int $customerId = null): ?array
    {
        $sql = 'SELECT * FROM customer_orders WHERE reference = :reference';
        $params = ['reference' => strtoupper(trim($reference))];
        if ($customerId !== null) {
            $sql .= ' AND customer_id = :customer_id';
            $params['customer_id'] = $customerId;
        }
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ? self::hydrate($row) : null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM customer_orders WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::hydrate($row) : null;
    }

    /** @return list<array<string, mixed>> */
    public static function allAdmin(?string $status = null, ?string $search = null): array
    {
        $where = [];
        $params = [];
        if ($status !== null && $status !== '' && isset(self::statuses()[$status])) {
            $where[] = 'o.status = :status';
            $params['status'] = $status;
        }
        if ($search !== null && trim($search) !== '') {
            $where[] = '(o.reference LIKE :q OR o.customer_name LIKE :q OR o.customer_email LIKE :q)';
            $params['q'] = '%' . trim($search) . '%';
        }
        $sql = 'SELECT o.* FROM customer_orders o';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY o.created_at DESC, o.id DESC LIMIT 300';
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute($params);

        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function updateStatus(int $id, string $status): void
    {
        if (!isset(self::statuses()[$status])) {
            throw new RuntimeException('Statut de commande invalide.');
        }
        $stmt = Database::getInstance()->prepare(
            'UPDATE customer_orders SET status = :status, updated_at = datetime(\'now\') WHERE id = :id'
        );
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public static function whatsappUrl(array $order, string $whatsappDigits): string
    {
        $digits = preg_replace('/\D+/', '', $whatsappDigits) ?: $whatsappDigits;
        if ($digits === '') {
            return '#';
        }

        return 'https://wa.me/' . $digits . '?text=' . rawurlencode(self::whatsappText($order));
    }

    public static function whatsappText(array $order): string
    {
        $items = is_array($order['items'] ?? null) ? $order['items'] : [];
        $lines = [
            'Bonjour, je souhaite passer commande.',
            'Référence : ' . (string) ($order['reference'] ?? ''),
            '',
        ];
        foreach ($items as $i => $item) {
            $qty = (int) ($item['qty'] ?? 0);
            $lineTotal = (float) ($item['price'] ?? 0) * $qty;
            $lines[] = ($i + 1) . '. ' . (string) ($item['name'] ?? '')
                . ' × ' . $qty
                . ' — ' . Helpers::formatPrice($lineTotal);
        }
        $lines[] = '';
        $lines[] = 'Total : ' . Helpers::formatPrice((float) ($order['total'] ?? 0));
        $name = trim((string) ($order['customer_name'] ?? ''));
        if ($name !== '') {
            $lines[] = 'Client : ' . $name;
        }
        $lines[] = '';
        $lines[] = 'Merci !';

        return implode("\n", $lines);
    }

    /**
     * @param list<array<string, mixed>> $rawItems
     * @return list<array{id:int,slug:string,name:string,brand:string,image:string,qty:int,price:float}>
     */
    public static function snapshotItems(array $rawItems): array
    {
        $out = [];
        foreach ($rawItems as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $id = (int) ($raw['id'] ?? 0);
            $qty = max(1, min(99, (int) ($raw['qty'] ?? 0)));
            if ($id < 1 || $qty < 1) {
                continue;
            }
            $product = ProductRepository::find($id);
            if (!$product || (int) ($product['is_active'] ?? 0) !== 1) {
                continue;
            }
            $visibility = (string) ($product['catalog_visibility'] ?? 'visible');
            if ($visibility !== '' && !in_array($visibility, ['visible', 'catalog', 'search'], true)) {
                continue;
            }
            if (!empty($product['is_out_of_stock']) || ($product['stock_status'] ?? '') === 'outofstock') {
                continue;
            }
            if (!empty($product['is_coming_soon'])) {
                continue;
            }
            $pricing = Helpers::productPricing($product);
            $out[] = [
                'id' => $id,
                'slug' => (string) ($product['slug'] ?? ''),
                'name' => (string) ($product['name'] ?? ''),
                'brand' => (string) ($product['brand'] ?? ''),
                'image' => (string) ($product['image'] ?? ''),
                'qty' => $qty,
                'price' => (float) $pricing['current'],
            ];
        }

        return $out;
    }

    private static function newReference(): string
    {
        do {
            $ref = 'PC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while (self::findByReference($ref) !== null);

        return $ref;
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): array
    {
        $items = json_decode((string) ($row['items_json'] ?? '[]'), true);
        $row['items'] = is_array($items) ? $items : [];
        $row['total'] = (float) ($row['total'] ?? 0);
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['customer_id'] = (int) ($row['customer_id'] ?? 0);

        return $row;
    }
}
