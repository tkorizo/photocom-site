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

        $db->exec('CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT,
            updated_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
        )');

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
        ];

        foreach ($defaultPages as [$slug, $title]) {
            $stmt = $db->prepare('SELECT id FROM pages WHERE slug = :slug');
            $stmt->execute(['slug' => $slug]);
            if (!$stmt->fetch()) {
                $insert = $db->prepare('INSERT INTO pages (slug, title, content) VALUES (:slug, :title, :content)');
                $insert->execute(['slug' => $slug, 'title' => $title, 'content' => '']);
            }
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
            'chat_enabled' => '0',
            'chat_n8n_webhook' => '',
            'chat_welcome_message' => 'Bonjour ! Comment pouvons-nous vous aider ?',
            'chat_offline_message' => 'Nous vous répondrons dès que possible.',
            'promo_text' => 'Livraison nationale — Matériel 100 % authentique — Devis WhatsApp sous 24 h',
            'hero_video_url' => '',
            'hero_video_poster' => '',
            'home_blog_video_1' => 'https://www.youtube.com/embed/6v2L2UGZJAM',
            'home_blog_video_2' => 'https://www.youtube.com/embed/BCtXzY6T1jw',
            'social_facebook' => 'https://www.facebook.com/',
            'social_instagram' => 'https://www.instagram.com/',
            'social_youtube' => 'https://www.youtube.com/',
            'social_linkedin' => '',
        ];

        foreach ($defaultSettings as $key => $value) {
            $stmt = $db->prepare('SELECT key FROM settings WHERE key = :key');
            $stmt->execute(['key' => $key]);
            if (!$stmt->fetch()) {
                $insert = $db->prepare('INSERT INTO settings (key, value) VALUES (:key, :value)');
                $insert->execute(['key' => $key, 'value' => $value]);
            }
        }
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
