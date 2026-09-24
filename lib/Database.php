<?php

declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require ROOT_PATH . '/config/app.php';
            $dbPath = $config['db_path'];

            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            self::$instance = new PDO('sqlite:' . $dbPath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            self::$instance->exec('PRAGMA foreign_keys = ON');
        }

        return self::$instance;
    }

    public static function initialize(): void
    {
        $schema = file_get_contents(ROOT_PATH . '/database/schema.sql');
        if ($schema === false) {
            throw new RuntimeException('Impossible de lire le schéma de base de données.');
        }

        $db = self::getInstance();
        self::migrate();
        $db->exec($schema);
    }

    public static function migrate(): void
    {
        $db = self::getInstance();

        self::addColumnIfMissing('categories', 'parent_id', 'INTEGER REFERENCES categories(id) ON DELETE SET NULL');
        self::addColumnIfMissing('categories', 'description', 'TEXT');
        self::addColumnIfMissing('categories', 'tax_code', 'TEXT');
        self::addColumnIfMissing('categories', 'display_type', "TEXT DEFAULT 'default'");
        self::addColumnIfMissing('categories', 'thumbnail', 'TEXT');
        self::addColumnIfMissing('categories', 'category_icon', 'TEXT');
        self::addColumnIfMissing('categories', 'large_category_icon', 'TEXT');
        self::addColumnIfMissing('categories', 'title_background', 'TEXT');
        self::addColumnIfMissing('categories', 'extra_description', 'TEXT');
        self::addColumnIfMissing('categories', 'menu_order', 'INTEGER DEFAULT 0');
        self::addColumnIfMissing('categories', 'show_in_menu', 'INTEGER DEFAULT 1');

        $db->exec('CREATE TABLE IF NOT EXISTS brands (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            slug TEXT NOT NULL UNIQUE,
            logo TEXT,
            is_active INTEGER NOT NULL DEFAULT 1,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\')),
            updated_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
        )');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_brands_name ON brands(name)');
        self::addColumnIfMissing('brands', 'show_in_filter', 'INTEGER NOT NULL DEFAULT 1');

        self::addColumnIfMissing('products', 'stock_quantity', 'INTEGER');
        self::addColumnIfMissing('products', 'manage_stock', 'INTEGER DEFAULT 0');
        self::addColumnIfMissing('products', 'is_out_of_stock', 'INTEGER DEFAULT 0');
        self::addColumnIfMissing('products', 'is_coming_soon', 'INTEGER DEFAULT 0');
        self::addColumnIfMissing('products', 'catalog_visibility', "TEXT DEFAULT 'visible'");
        self::addColumnIfMissing('products', 'hide_add_to_cart', 'INTEGER DEFAULT 0');
        self::addColumnIfMissing('products', 'featured', 'INTEGER DEFAULT 0');
        self::addColumnIfMissing('products', 'on_sale', 'INTEGER DEFAULT 0');
        self::addColumnIfMissing('products', 'images_secondary_json', 'TEXT');
        self::addColumnIfMissing('products', 'permalink', 'TEXT');
        self::addColumnIfMissing('products', 'admin_label', 'TEXT');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_products_admin_label ON products(admin_label)');

        $db->exec('CREATE TABLE IF NOT EXISTS product_categories (
            product_id INTEGER NOT NULL,
            category_id INTEGER NOT NULL,
            PRIMARY KEY (product_id, category_id),
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        )');

        $db->exec('CREATE INDEX IF NOT EXISTS idx_categories_parent ON categories(parent_id)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_product_categories_category ON product_categories(category_id)');

        self::addColumnIfMissing('users', 'role', "TEXT NOT NULL DEFAULT 'admin'");
        self::migrateUserRoles();

        $db->exec('CREATE TABLE IF NOT EXISTS customers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            name TEXT NOT NULL,
            phone TEXT,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\')),
            updated_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
        )');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_customers_email ON customers(email)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_customers_created ON customers(created_at)');

        $db->exec('CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_id INTEGER NOT NULL,
            token_hash TEXT NOT NULL UNIQUE,
            expires_at TEXT NOT NULL,
            used_at TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\')),
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        )');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_password_reset_customer ON password_reset_tokens(customer_id)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_password_reset_expires ON password_reset_tokens(expires_at)');

        $db->exec('CREATE TABLE IF NOT EXISTS email_otps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            purpose TEXT NOT NULL,
            email TEXT NOT NULL,
            subject_id INTEGER,
            code_hash TEXT NOT NULL,
            expires_at TEXT NOT NULL,
            attempts INTEGER NOT NULL DEFAULT 0,
            consumed_at TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
        )');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_email_otps_lookup ON email_otps(purpose, email, consumed_at)');

        self::addColumnIfMissing('customers', 'email_verified_at', 'TEXT');

        $db->exec('CREATE TABLE IF NOT EXISTS customer_carts (
            customer_id INTEGER PRIMARY KEY,
            items_json TEXT NOT NULL DEFAULT \'[]\',
            updated_at TEXT NOT NULL DEFAULT (datetime(\'now\')),
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        )');

        $db->exec('CREATE TABLE IF NOT EXISTS customer_orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_id INTEGER NOT NULL,
            reference TEXT NOT NULL UNIQUE,
            status TEXT NOT NULL DEFAULT \'pending\',
            items_json TEXT NOT NULL DEFAULT \'[]\',
            total REAL NOT NULL DEFAULT 0,
            customer_name TEXT,
            customer_email TEXT,
            customer_phone TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\')),
            updated_at TEXT NOT NULL DEFAULT (datetime(\'now\')),
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        )');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_customer_orders_customer ON customer_orders(customer_id, created_at)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_customer_orders_status ON customer_orders(status)');

        $db->exec('CREATE TABLE IF NOT EXISTS chat_conversations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT NOT NULL UNIQUE,
            page_url TEXT,
            language TEXT,
            message_count INTEGER NOT NULL DEFAULT 0,
            last_message_at TEXT,
            visitor_ip TEXT,
            visitor_country TEXT,
            visitor_city TEXT,
            visitor_user_agent TEXT,
            visitor_meta TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
        )');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_chat_conversations_last ON chat_conversations(last_message_at)');
        self::addColumnIfMissing('chat_conversations', 'visitor_ip', 'TEXT');
        self::addColumnIfMissing('chat_conversations', 'visitor_country', 'TEXT');
        self::addColumnIfMissing('chat_conversations', 'visitor_city', 'TEXT');
        self::addColumnIfMissing('chat_conversations', 'visitor_user_agent', 'TEXT');
        self::addColumnIfMissing('chat_conversations', 'visitor_meta', 'TEXT');
        self::addColumnIfMissing('chat_conversations', 'blocked_until', 'INTEGER NOT NULL DEFAULT 0');
        self::addColumnIfMissing('chat_conversations', 'blocked_reason', 'TEXT');
        $db->exec('CREATE TABLE IF NOT EXISTS chat_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            conversation_id INTEGER NOT NULL,
            role TEXT NOT NULL,
            content TEXT NOT NULL,
            products_json TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\')),
            FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE
        )');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_chat_messages_conversation ON chat_messages(conversation_id, id)');

        self::addColumnIfMissing('articles', 'meta_title', 'TEXT');
        self::addColumnIfMissing('articles', 'meta_description', 'TEXT');
        self::addColumnIfMissing('articles', 'meta_keywords', 'TEXT');
        self::addColumnIfMissing('articles', 'focus_keyword', 'TEXT');
        self::addColumnIfMissing('articles', 'canonical_url', 'TEXT');

        // Annule les flags promo sans vraie remise (prix promo >= Prix TTC).
        $db->exec(
            'UPDATE products SET on_sale = 0
             WHERE on_sale = 1 AND (
                sale_price IS NULL
                OR price IS NULL
                OR sale_price <= 0
                OR sale_price >= price
             )'
        );

        $db->exec('CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT,
            updated_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
        )');

        // Comptes clients déjà existants = e-mail considéré vérifié (une seule fois).
        $bf = $db->query("SELECT value FROM settings WHERE key = 'customers_email_verified_backfill'")->fetchColumn();
        if ($bf !== '1') {
            $db->exec('UPDATE customers SET email_verified_at = COALESCE(email_verified_at, created_at) WHERE email_verified_at IS NULL');
            $ins = $db->prepare(
                "INSERT INTO settings (key, value, updated_at) VALUES ('customers_email_verified_backfill', '1', datetime('now'))
                 ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = datetime('now')"
            );
            $ins->execute();
        }

        $db->exec('CREATE TABLE IF NOT EXISTS articles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            excerpt TEXT,
            content TEXT,
            image TEXT,
            is_published INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\')),
            updated_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
        )');

        $db->exec('CREATE TABLE IF NOT EXISTS pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT NOT NULL UNIQUE,
            title TEXT NOT NULL,
            content TEXT,
            is_published INTEGER NOT NULL DEFAULT 1,
            updated_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
        )');

        $db->exec('CREATE INDEX IF NOT EXISTS idx_articles_published ON articles(is_published)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_pages_slug ON pages(slug)');

        self::seedDefaults();

        try {
            ArticleRepository::ensureSampleArticles();
        } catch (Throwable) {
            // Articles optionnels
        }

        try {
            BrandRepository::syncFromProducts();
        } catch (Throwable) {
            // Table brands may not be ready yet on first boot edge cases.
        }
    }

    private static function migrateUserRoles(): void
    {
        $db = self::getInstance();

        try {
            $superCount = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin'")->fetchColumn();
        } catch (Throwable) {
            return;
        }

        if ($superCount > 0) {
            return;
        }

        $stmt = $db->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
        $row = $stmt ? $stmt->fetch() : false;
        if ($row) {
            $upd = $db->prepare("UPDATE users SET role = 'super_admin' WHERE id = :id");
            $upd->execute(['id' => (int) $row['id']]);
        }
    }

    private static function seedDefaults(): void
    {
        $db = self::getInstance();
        $config = require ROOT_PATH . '/config/app.php';

        $defaultPages = [
            ['confidentialite', 'Politique de confidentialité'],
            ['conditions-generales', 'Conditions générales'],
            ['mentions-legales', 'Mentions légales'],
            ['politique-livraison', 'Politique de livraison'],
            ['politique-retour', 'Politique de retour'],
            ['comment-commander', 'Comment passer votre commande'],
            ['politique-cookies', 'Politique de cookies'],
        ];

        foreach ($defaultPages as [$slug, $title]) {
            $stmt = $db->prepare('SELECT id FROM pages WHERE slug = :slug');
            $stmt->execute(['slug' => $slug]);
            if (!$stmt->fetch()) {
                $insert = $db->prepare('INSERT INTO pages (slug, title, content) VALUES (:slug, :title, :content)');
                $insert->execute(['slug' => $slug, 'title' => $title, 'content' => '']);
            }
        }

        try {
            LegalPagesContent::seedIntoDatabase(true);
        } catch (Throwable) {
            // Contenu légal optionnel au premier boot
        }

        $defaultSettings = [
            'site_name' => $config['name'],
            'site_url' => $config['url'],
            'site_tagline' => 'Votre partenaire photo à Casablanca',
            'phone' => $config['phone'],
            'email' => $config['email'],
            'whatsapp' => $config['whatsapp'],
            'address' => $config['address'],
            'hours' => $config['hours'],
            'founded' => $config['founded'],
            ...SettingRepository::chatDefaults(),
            ...SettingRepository::cookieDefaults(),
            ...SettingRepository::homeDefaults(),
        ];

        foreach ($defaultSettings as $key => $value) {
            $stmt = $db->prepare('SELECT key FROM settings WHERE key = :key');
            $stmt->execute(['key' => $key]);
            if (!$stmt->fetch()) {
                $insert = $db->prepare('INSERT INTO settings (key, value) VALUES (:key, :value)');
                $insert->execute(['key' => $key, 'value' => $value]);
            }
        }

        SettingRepository::applyChatPlaybook();
        ChatGuard::applyResetOnce();
    }

    private static function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        if (!self::columnExists($table, $column)) {
            self::getInstance()->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private static function columnExists(string $table, string $column): bool
    {
        $stmt = self::getInstance()->query('PRAGMA table_info(' . $table . ')');
        $columns = $stmt->fetchAll();

        foreach ($columns as $col) {
            if (($col['name'] ?? '') === $column) {
                return true;
            }
        }

        return false;
    }
}
