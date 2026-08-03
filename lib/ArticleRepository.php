<?php

declare(strict_types=1);

class ArticleRepository
{
    public static function all(): array
    {
        return Database::getInstance()
            ->query('SELECT * FROM articles ORDER BY created_at DESC')
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM articles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $article = $stmt->fetch();

        return $article ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM articles WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        $article = $stmt->fetch();

        return $article ?: null;
    }

    public static function count(): int
    {
        return (int) Database::getInstance()->query('SELECT COUNT(*) FROM articles')->fetchColumn();
    }

    public static function countPublished(): int
    {
        return (int) Database::getInstance()->query('SELECT COUNT(*) FROM articles WHERE is_published = 1')->fetchColumn();
    }

    public static function publishedRecent(int $limit = 2): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM articles WHERE is_published = 1 ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int}
     */
    public static function paginatePublished(int $page = 1, int $perPage = 9): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(24, $perPage));
        $offset = ($page - 1) * $perPage;
        $db = Database::getInstance();

        $total = (int) $db->query('SELECT COUNT(*) FROM articles WHERE is_published = 1')->fetchColumn();
        $stmt = $db->prepare(
            'SELECT * FROM articles WHERE is_published = 1 ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / max(1, $perPage)),
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function sampleArticles(): array
    {
        return [
            [
                'title' => 'Choisir son premier hybride au Maroc',
                'slug' => 'choisir-premier-hybride',
                'excerpt' => 'Capteur, monture, autofocus : les critères essentiels pour investir dans un boîtier polyvalent.',
                'meta_title' => 'Choisir son premier appareil hybride au Maroc | PHOTOCOM',
                'meta_description' => 'Guide PHOTOCOM pour choisir un hybride photo/vidéo à Casablanca : capteur, monture, budget et essais en magasin.',
                'meta_keywords' => 'hybride, appareil photo, Casablanca, PHOTOCOM, plein format, APS-C',
                'focus_keyword' => 'appareil hybride Maroc',
                'content' => <<<'HTML'
<p>Passer au numérique hybride, c’est souvent le premier grand investissement photo. Avant de comparer les prix, clarifiez votre usage : portraits, reportage, vidéo, voyage.</p>
<h2>1. Définir le besoin</h2>
<p>Un créateur de contenu vidéo n’a pas les mêmes priorités qu’un photographe événementiel. Listez vos trois usages principaux : cela évite d’acheter trop — ou trop peu.</p>
<h2>2. Capteur et monture</h2>
<p>APS-C ou plein format, l’écosystème d’objectifs compte autant que le boîtier. Chez PHOTOCOM, nous privilégions les systèmes pour lesquels l’optique et les accessoires restent disponibles localement.</p>
<h2>3. Essayer avant d’acheter</h2>
<p>Prenez le temps de tenir le boîtier, de tester l’autofocus et le viseur. Notre équipe à Casablanca peut vous présenter des configurations adaptées à votre budget.</p>
<p><a href="/catalogue.php">Parcourir le catalogue appareils</a> ou demandez un devis WhatsApp pour une sélection personnalisée.</p>
HTML,
            ],
            [
                'title' => 'Éclairage studio : flash ou LED continue ?',
                'slug' => 'eclairage-studio-flash-ou-led',
                'excerpt' => 'Deux philosophies d’éclairage, deux workflows. Voici comment trancher selon vos projets.',
                'meta_title' => 'Flash ou LED studio : que choisir ? | Conseils PHOTOCOM',
                'meta_description' => 'Flash vs LED continue pour le studio photo et vidéo. Conseils pratiques PHOTOCOM pour composer un kit d’éclairage à Casablanca.',
                'meta_keywords' => 'éclairage studio, flash, LED, photo produit, Casablanca',
                'focus_keyword' => 'éclairage studio flash LED',
                'content' => <<<'HTML'
<p>Le studio commence souvent par une question : flash cobra / générateur, ou LED continue ?</p>
<h2>Le flash</h2>
<p>Puissance, gel du mouvement, compatibilité haute vitesse… Le flash reste roi pour la photo produit et le portrait classique. Il demande un peu de méthode (mesures, modificateurs) mais offre un rendu très maîtrisé.</p>
<h2>La LED continue</h2>
<p>Idéale pour la vidéo et le live. Vous voyez le rendu en temps réel, ce qui accélère le cadrage et le travail d’équipe. Attention à la température de couleur et à la qualité du flicker.</p>
<h2>Notre conseil</h2>
<p>Beaucoup de studios mixtes combinent les deux : LED pour la vidéo et l’ambiance, flash pour les séries photo. Nous pouvons construire un kit progressif — un modificateur, une tête, puis une seconde source.</p>
HTML,
            ],
            [
                'title' => 'Objectifs : focale fixe ou zoom polyvalent ?',
                'slug' => 'objectifs-focale-fixe-ou-zoom',
                'excerpt' => 'Qualité optique, ouverture, polyvalence : comment composer un sac d’objectifs cohérent.',
                'meta_title' => 'Focale fixe ou zoom : quel objectif choisir ? | PHOTOCOM',
                'meta_description' => 'Comparer focale fixe et zoom pour la photo pro. Conseils PHOTOCOM pour construire un sac d’objectifs sans doublons inutiles.',
                'meta_keywords' => 'objectif photo, focale fixe, zoom, 24-70, 85mm',
                'focus_keyword' => 'focale fixe ou zoom',
                'content' => <<<'HTML'
<p>Le zoom 24–70 mm rassure. La focale fixe 50 mm ou 85 mm séduit par son ouverture et son rendu. Les deux ont leur place.</p>
<h2>Quand privilégier le zoom</h2>
<p>Reportage, événement, voyage : un bon zoom vous évite de changer d’optique en pleine action. Choisissez une optique de qualité plutôt qu’un kit trop lent en ouverture.</p>
<h2>Quand privilégier la focale fixe</h2>
<p>Portrait, low-light, esthétique bokeh : une focale fixe lumineuse transforme le rendu. Elle force aussi une meilleure composition.</p>
<h2>Construire progressivement</h2>
<p>Commencez par une optique polyvalente, puis ajoutez une focale signature (35, 50 ou 85). Notre équipe vous aide à éviter les doublons inutiles dans votre monture.</p>
HTML,
            ],
            [
                'title' => 'Préparer un tournage léger avec un kit mobile',
                'slug' => 'kit-tournage-mobile',
                'excerpt' => 'Boîtier, micro, lumière et stabilisation : l’essentiel pour filmer proprement hors studio.',
                'meta_title' => 'Kit tournage mobile : checklist PHOTOCOM',
                'meta_description' => 'Composez un kit vidéo mobile (image, son, lumière, stabilisation) pour filmer proprement hors studio avec PHOTOCOM Casablanca.',
                'meta_keywords' => 'kit vidéo, tournage mobile, micro, gimbal, LED',
                'focus_keyword' => 'kit tournage mobile',
                'content' => <<<'HTML'
<p>Un kit mobile efficace tient dans un sac, mais couvre image, son et lumière.</p>
<ul>
<li><strong>Image</strong> — hybride + optique polyvalente, batteries de rechange.</li>
<li><strong>Son</strong> — micro-canon ou HF selon le contexte ; toujours un casque pour contrôler.</li>
<li><strong>Lumière</strong> — une LED compacte et un panneau pliable changent un intérieur terne.</li>
<li><strong>Stabilisation</strong> — cage légère, trépied travel ou gimbal selon le style.</li>
</ul>
<p>Avant chaque sortie, vérifiez cartes, codecs et espace disque. Chez PHOTOCOM, nous montons des kits « content creator » et « corporate mobile » prêts à l’emploi.</p>
HTML,
            ],
            [
                'title' => 'Batteries et alimentation : éviter les pannes en shooting',
                'slug' => 'batteries-alimentation-shooting',
                'excerpt' => 'Combienies, grips, chargeurs et powerbanks : comment sécuriser l’énergie sur un shooting long.',
                'meta_title' => 'Batteries photo/vidéo : guide anti-panne | PHOTOCOM',
                'meta_description' => 'Astuces PHOTOCOM pour gérer batteries, grips et chargeurs pendant un shooting photo ou vidéo professionnel au Maroc.',
                'meta_keywords' => 'batterie appareil photo, grip, chargeur, shooting',
                'focus_keyword' => 'batteries appareil photo',
                'content' => <<<'HTML'
<p>Rien n’arrête un shooting aussi vite qu’une batterie à plat. Une bonne gestion d’énergie fait partie du métier.</p>
<h2>Calculez votre autonomie</h2>
<p>Comptez le nombre de prises / minutes de rushs, puis ajoutez 30&nbsp;% de marge. En hiver ou avec l’écran toujours allumé, l’autonomie chute vite.</p>
<h2>Doubler les points critiques</h2>
<p>Deux batteries minimum pour le boîtier, un chargeur rapide, et une solution secteur (prise / V-mount / powerbank compatible) pour les LED longues.</p>
<h2>Stockage et transport</h2>
<p>Rangez les accus dans des étuis isolés, évitez la chaleur du coffre. Chez PHOTOCOM, nous proposons batteries d’origine et alternatives fiables selon votre monture.</p>
HTML,
            ],
            [
                'title' => 'Entretenir son matériel photo : nettoyage et SAV',
                'slug' => 'entretenir-materiel-photo-sav',
                'excerpt' => 'Capteur, optiques, joints : les gestes simples qui prolongent la vie de votre équipement.',
                'meta_title' => 'Entretien matériel photo et SAV | PHOTOCOM Casablanca',
                'meta_description' => 'Nettoyage capteur, entretien des objectifs et service après-vente : les bonnes pratiques PHOTOCOM pour protéger votre matériel.',
                'meta_keywords' => 'nettoyage capteur, entretien objectif, SAV photo Casablanca',
                'focus_keyword' => 'entretien matériel photo',
                'content' => <<<'HTML'
<p>Un boîtier et des optiques bien entretenus gardent leur valeur et évitent les mauvaises surprises le jour J.</p>
<h2>Au quotidien</h2>
<p>Soufflette, chiffon microfibre, rangement dans un sac propre : trois gestes qui limitent poussière et rayures. Changez d’objectif à l’abri du vent.</p>
<h2>Le capteur</h2>
<p>Des taches récurrentes ? Un nettoyage humide professionnel est plus sûr qu’un kit improvisé. Nous pouvons vous orienter vers un diagnostic en magasin.</p>
<h2>Quand passer au SAV</h2>
<p>Autofocus capricieux, erreur électronique, choc : ne forcez pas. PHOTOCOM vous accompagne sur le suivi SAV et les pièces disponibles pour les marques que nous distribuons.</p>
HTML,
            ],
        ];
    }

    public static function seedDefaults(): void
    {
        if (self::count() > 0) {
            return;
        }
        self::ensureSampleArticles();
    }

    /** Insère les articles d’exemple manquants (par slug) et complète le SEO manquant. */
    public static function ensureSampleArticles(): void
    {
        foreach (self::sampleArticles() as $sample) {
            $existing = self::findBySlug($sample['slug']);
            if (!$existing) {
                self::create([
                    'title' => $sample['title'],
                    'slug' => $sample['slug'],
                    'excerpt' => $sample['excerpt'],
                    'content' => $sample['content'],
                    'image' => '',
                    'meta_title' => $sample['meta_title'] ?? '',
                    'meta_description' => $sample['meta_description'] ?? '',
                    'meta_keywords' => $sample['meta_keywords'] ?? '',
                    'focus_keyword' => $sample['focus_keyword'] ?? '',
                    'canonical_url' => '',
                    'is_published' => 1,
                ]);
                continue;
            }

            $needsSeo = trim((string) ($existing['meta_description'] ?? '')) === ''
                || trim((string) ($existing['meta_title'] ?? '')) === '';
            if ($needsSeo) {
                self::update((int) $existing['id'], [
                    'title' => $existing['title'],
                    'slug' => $existing['slug'],
                    'excerpt' => $existing['excerpt'] ?? $sample['excerpt'],
                    'content' => $existing['content'] ?? $sample['content'],
                    'image' => $existing['image'] ?? '',
                    'meta_title' => $sample['meta_title'] ?? '',
                    'meta_description' => $sample['meta_description'] ?? '',
                    'meta_keywords' => $sample['meta_keywords'] ?? '',
                    'focus_keyword' => $sample['focus_keyword'] ?? '',
                    'canonical_url' => $existing['canonical_url'] ?? '',
                    'is_published' => (int) ($existing['is_published'] ?? 1),
                ]);
            }
        }
    }

    public static function create(array $data): int
    {
        $slug = $data['slug'] ?: Helpers::uniqueSlug('articles', $data['title']);
        $stmt = Database::getInstance()->prepare(
            'INSERT INTO articles (
                title, slug, excerpt, content, image, is_published,
                meta_title, meta_description, meta_keywords, focus_keyword, canonical_url
             ) VALUES (
                :title, :slug, :excerpt, :content, :image, :is_published,
                :meta_title, :meta_description, :meta_keywords, :focus_keyword, :canonical_url
             )'
        );
        $stmt->execute(self::bindParams($data, $slug));

        return (int) Database::getInstance()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $slug = $data['slug'] ?: Helpers::uniqueSlug('articles', $data['title'], $id);
        $params = self::bindParams($data, $slug);
        $params['id'] = $id;
        $stmt = Database::getInstance()->prepare(
            'UPDATE articles SET
                title = :title, slug = :slug, excerpt = :excerpt, content = :content,
                image = :image, is_published = :is_published,
                meta_title = :meta_title, meta_description = :meta_description,
                meta_keywords = :meta_keywords, focus_keyword = :focus_keyword,
                canonical_url = :canonical_url,
                updated_at = datetime(\'now\')
             WHERE id = :id'
        );
        $stmt->execute($params);
    }

    /** @param array<string, mixed> $data */
    private static function bindParams(array $data, string $slug): array
    {
        return [
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? '',
            'content' => $data['content'] ?? '',
            'image' => $data['image'] ?? '',
            'is_published' => !empty($data['is_published']) ? 1 : 0,
            'meta_title' => $data['meta_title'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'meta_keywords' => $data['meta_keywords'] ?? '',
            'focus_keyword' => $data['focus_keyword'] ?? '',
            'canonical_url' => $data['canonical_url'] ?? '',
        ];
    }

    public static function delete(int $id): void
    {
        $stmt = Database::getInstance()->prepare('DELETE FROM articles WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
