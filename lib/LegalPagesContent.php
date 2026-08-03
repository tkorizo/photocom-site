<?php

declare(strict_types=1);

class LegalPagesContent
{
    /**
     * @return array<string, array{title: string, content: string}>
     */
    public static function all(): array
    {
        $config = Helpers::config();
        $name = $config['name'] ?? 'PHOTOCOM';
        $address = $config['address'] ?? 'Casablanca, Maroc';
        $phone = $config['phone'] ?? '';
        $email = $config['email'] ?? 'contact@photocom.ma';
        $url = rtrim((string) ($config['url'] ?? 'https://photocom.ma'), '/');

        return [
            'mentions-legales' => [
                'title' => 'Mentions légales',
                'content' => self::mentions($name, $address, $phone, $email, $url),
            ],
            'confidentialite' => [
                'title' => 'Politique de confidentialité',
                'content' => self::confidentialite($name, $address, $email, $url),
            ],
            'conditions-generales' => [
                'title' => 'Conditions générales',
                'content' => self::cgv($name, $address, $email, $url),
            ],
            'politique-livraison' => [
                'title' => 'Politique de livraison',
                'content' => self::livraison($name, $address, $phone, $email),
            ],
            'politique-retour' => [
                'title' => 'Politique de retour',
                'content' => self::retour($name, $email, $phone),
            ],
            'comment-commander' => [
                'title' => 'Comment passer votre commande',
                'content' => self::commander($name, $phone, $email),
            ],
            'politique-cookies' => [
                'title' => 'Politique de cookies',
                'content' => self::cookies($name, $email, $url),
            ],
        ];
    }

    public static function seedIntoDatabase(bool $overwriteEmptyOnly = true): void
    {
        $db = Database::getInstance();
        foreach (self::all() as $slug => $page) {
            $stmt = $db->prepare('SELECT id, content FROM pages WHERE slug = :slug');
            $stmt->execute(['slug' => $slug]);
            $row = $stmt->fetch();

            if (!$row) {
                $insert = $db->prepare(
                    'INSERT INTO pages (slug, title, content, is_published) VALUES (:slug, :title, :content, 1)'
                );
                $insert->execute([
                    'slug' => $slug,
                    'title' => $page['title'],
                    'content' => $page['content'],
                ]);
                continue;
            }

            $content = trim((string) ($row['content'] ?? ''));
            if ($overwriteEmptyOnly && $content !== '') {
                continue;
            }

            $update = $db->prepare(
                'UPDATE pages SET title = :title, content = :content, is_published = 1, updated_at = datetime(\'now\') WHERE id = :id'
            );
            $update->execute([
                'id' => (int) $row['id'],
                'title' => $page['title'],
                'content' => $page['content'],
            ]);
        }
    }

    private static function mentions(string $name, string $address, string $phone, string $email, string $url): string
    {
        return <<<HTML
<p>Les présentes mentions légales s’appliquent au site internet <a href="{$url}">{$url}</a> édité par <strong>{$name}</strong>.</p>
<h2>Éditeur du site</h2>
<p><strong>{$name}</strong><br>Adresse : {$address}<br>Téléphone : {$phone}<br>E-mail : <a href="mailto:{$email}">{$email}</a></p>
<p>{$name} est une société spécialisée dans la distribution de matériel photo, vidéo et studio professionnel au Maroc.</p>
<h2>Hébergement</h2>
<p>Le site est hébergé sur l’infrastructure choisie par {$name}. Pour toute question relative à l’hébergement, vous pouvez nous contacter à l’adresse <a href="mailto:{$email}">{$email}</a>.</p>
<h2>Propriété intellectuelle</h2>
<p>L’ensemble des éléments du site (textes, photographies, logos, structure, charte graphique, catalogues) est protégé. Toute reproduction, représentation ou diffusion, totale ou partielle, sans autorisation écrite préalable de {$name} est interdite.</p>
<h2>Responsabilité</h2>
<p>Les informations publiées sur le site sont fournies à titre indicatif. {$name} s’efforce d’assurer l’exactitude des contenus et des disponibilités produits, sans garantir l’absence d’erreurs ou d’omissions. Les photographies sont non contractuelles.</p>
<h2>Contact</h2>
<p>Pour toute demande relative aux mentions légales : <a href="mailto:{$email}">{$email}</a> — {$phone}.</p>
HTML;
    }

    private static function confidentialite(string $name, string $address, string $email, string $url): string
    {
        return <<<HTML
<p>La présente politique décrit la manière dont <strong>{$name}</strong> collecte, utilise et protège vos données personnelles lorsque vous utilisez le site <a href="{$url}">{$url}</a>.</p>
<h2>Responsable du traitement</h2>
<p>{$name}<br>{$address}<br>Contact : <a href="mailto:{$email}">{$email}</a></p>
<h2>Données collectées</h2>
<p>Selon votre utilisation du site, nous pouvons collecter :</p>
<p>• Identité et coordonnées (nom, téléphone, e-mail, adresse) lorsque vous nous contactez ou demandez un devis<br>• Données de navigation techniques (adresse IP, type de navigateur, pages consultées) via cookies et journaux serveur<br>• Contenu des messages envoyés via formulaires, WhatsApp ou chat</p>
<h2>Finalités</h2>
<p>Vos données sont utilisées pour : répondre à vos demandes, établir des devis, traiter les commandes, assurer le service après-vente, améliorer le site et respecter nos obligations légales.</p>
<h2>Base légale</h2>
<p>Le traitement repose sur votre consentement, l’exécution de mesures précontractuelles ou contractuelles, et l’intérêt légitime de {$name} à sécuriser et améliorer ses services.</p>
<h2>Conservation</h2>
<p>Les données sont conservées pendant la durée nécessaire aux finalités poursuivies, puis archivées ou supprimées conformément à la réglementation applicable au Maroc.</p>
<h2>Destinataires</h2>
<p>Les données sont destinées aux équipes autorisées de {$name} et, le cas échéant, à des prestataires techniques (hébergement, messagerie) agissant selon nos instructions.</p>
<h2>Vos droits</h2>
<p>Vous pouvez demander l’accès, la rectification ou la suppression de vos données, dans les limites prévues par la loi, en écrivant à <a href="mailto:{$email}">{$email}</a>.</p>
<h2>Cookies</h2>
<p>Le site utilise des cookies. Pour en savoir plus, consultez notre <a href="/page/politique-cookies">politique de cookies</a>.</p>
HTML;
    }

    private static function cgv(string $name, string $address, string $email, string $url): string
    {
        return <<<HTML
<p>Les présentes conditions générales encadrent les ventes de produits proposés par <strong>{$name}</strong> via le site <a href="{$url}">{$url}</a> et en magasin.</p>
<h2>Produits et prix</h2>
<p>Les produits sont décrits avec le plus grand soin. Les prix sont indiqués en dirhams marocains (MAD), TTC sauf mention contraire. {$name} se réserve le droit de modifier ses tarifs à tout moment ; le prix applicable est celui confirmé lors de la validation de la commande.</p>
<h2>Commande</h2>
<p>Toute commande implique l’acceptation des présentes conditions. {$name} confirme la commande après vérification de la disponibilité et validation du devis ou du paiement selon le mode choisi.</p>
<h2>Paiement</h2>
<p>Les modes de paiement acceptés sont communiqués lors de la commande (virement, espèces en magasin, ou autres moyens précisés par {$name}). Aucune livraison n’est effectuée avant validation du paiement, sauf accord contraire écrit.</p>
<h2>Disponibilité</h2>
<p>En cas d’indisponibilité après commande, {$name} informe le client dans les meilleurs délais et propose un report, un produit équivalent ou un remboursement.</p>
<h2>Garantie</h2>
<p>Les produits neufs bénéficient de la garantie constructeur et/ou des garanties légales applicables. Les conditions précises sont indiquées sur la fiche produit ou le bon de livraison.</p>
<h2>Droit applicable</h2>
<p>Les présentes conditions sont soumises au droit marocain. En cas de litige, une solution amiable sera recherchée avant toute action judiciaire. Siège : {$address}. Contact : <a href="mailto:{$email}">{$email}</a>.</p>
HTML;
    }

    private static function livraison(string $name, string $address, string $phone, string $email): string
    {
        return <<<HTML
<p><strong>{$name}</strong> assure la livraison de matériel photo et vidéo sur l’ensemble du territoire marocain, ainsi que le retrait en magasin à Casablanca.</p>
<h2>Zones de livraison</h2>
<p>Livraison nationale au Maroc. Les délais et frais varient selon la ville, le volume et le mode d’expédition choisi.</p>
<h2>Délais</h2>
<p>Les délais indicatifs sont communiqués lors de la confirmation de commande. Ils démarrent après validation du paiement et confirmation de disponibilité. Des retards indépendants de {$name} (transporteur, douane interne, force majeure) peuvent survenir.</p>
<h2>Frais de livraison</h2>
<p>Les frais sont calculés selon le poids, le volume et la destination. Ils sont indiqués avant validation de la commande ou sur le devis.</p>
<h2>Retrait en magasin</h2>
<p>Vous pouvez retirer votre commande à notre adresse : {$address}. Un avis de disponibilité vous est envoyé avant le retrait.</p>
<h2>Réception</h2>
<p>À la livraison, vérifiez l’état du colis. En cas d’anomalie visible, signalez-la sur le bon de transport et contactez-nous sous 48 h à <a href="mailto:{$email}">{$email}</a> ou au {$phone}.</p>
HTML;
    }

    private static function retour(string $name, string $email, string $phone): string
    {
        return <<<HTML
<p>La présente politique précise les conditions de retour et d’échange applicables aux produits vendus par <strong>{$name}</strong>.</p>
<h2>Produits concernés</h2>
<p>Les retours concernent les produits neufs, non utilisés, dans leur emballage d’origine complet (accessoires, notice, scellés le cas échéant), sauf défaut de conformité ou panne sous garantie.</p>
<h2>Délai</h2>
<p>Sauf disposition particulière, toute demande de retour doit être formulée dans un délai raisonnable après réception (généralement 7 jours), via <a href="mailto:{$email}">{$email}</a> ou {$phone}, en précisant le numéro de commande.</p>
<h2>Procédure</h2>
<p>1. Contactez le service client pour obtenir un accord de retour<br>2. Emballez soigneusement le produit<br>3. Expédiez ou déposez le colis selon les instructions reçues<br>4. Après contrôle, {$name} procède à l’échange, au avoir ou au remboursement selon le cas</p>
<h2>Frais</h2>
<p>Les frais de retour sont à la charge du client, sauf erreur de {$name} ou produit défectueux à réception.</p>
<h2>Exclusions</h2>
<p>Ne sont pas repris : produits descellés lorsque le scellé est requis, logiciels activés, consommables entamés, produits endommagés par une mauvaise utilisation.</p>
HTML;
    }

    private static function commander(string $name, string $phone, string $email): string
    {
        return <<<HTML
<p>Commander chez <strong>{$name}</strong> est simple. Voici les étapes recommandées.</p>
<h2>1. Parcourir le catalogue</h2>
<p>Consultez notre <a href="/catalogue.php">catalogue en ligne</a>, filtrez par catégorie ou marque, et repérez les références qui vous intéressent.</p>
<h2>2. Demander un devis</h2>
<p>Contactez-nous via WhatsApp, téléphone ({$phone}) ou e-mail (<a href="mailto:{$email}">{$email}</a>) en indiquant les références et quantités souhaitées. Nous vous répondons rapidement avec un devis clair.</p>
<h2>3. Validation</h2>
<p>Après acceptation du devis, nous confirmons la disponibilité, le mode de paiement et les options de livraison ou de retrait en magasin.</p>
<h2>4. Paiement et expédition</h2>
<p>Dès validation du paiement, la commande est préparée puis expédiée ou mise à disposition en magasin selon votre choix.</p>
<h2>Besoin d’un conseil ?</h2>
<p>Notre équipe vous guide dans le choix du matériel (appareil, optique, éclairage, studio, audio). N’hésitez pas à nous écrire avant de commander.</p>
HTML;
    }

    private static function cookies(string $name, string $email, string $url): string
    {
        return <<<HTML
<p>Cette politique explique l’usage des cookies sur le site <a href="{$url}">{$url}</a> édité par <strong>{$name}</strong>.</p>
<h2>Qu’est-ce qu’un cookie ?</h2>
<p>Un cookie est un petit fichier déposé sur votre terminal lors de la visite d’un site. Il permet de mémoriser des informations relatives à votre navigation.</p>
<h2>Cookies utilisés</h2>
<p><strong>Cookies nécessaires</strong> : indispensables au fonctionnement du site (session, sécurité, mémorisation de votre choix cookies).<br><strong>Cookies de mesure d’audience</strong> (si activés) : aident à comprendre l’utilisation du site pour l’améliorer.<br><strong>Cookies de préférence</strong> : mémorisent certains choix d’affichage.</p>
<h2>Gestion de votre consentement</h2>
<p>Lors de votre première visite, une bannière vous permet d’accepter, de refuser ou de paramétrer les cookies non essentiels. Vous pouvez modifier votre choix à tout moment en effaçant les cookies de votre navigateur ou en nous contactant.</p>
<h2>Durée</h2>
<p>La durée de conservation des cookies varie selon leur finalité, dans la limite des durées raisonnables et réglementaires.</p>
<h2>Contact</h2>
<p>Pour toute question : <a href="mailto:{$email}">{$email}</a>. Voir aussi notre <a href="/page/confidentialite">politique de confidentialité</a>.</p>
HTML;
    }
}
