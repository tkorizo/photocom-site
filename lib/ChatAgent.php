<?php

declare(strict_types=1);

class ChatAgent
{
    /**
     * @param array<string, mixed> $clientHints
     * @return array{reply: string, session_id: string, offline?: bool, blocked?: bool}
     */
    public static function reply(string $message, string $sessionId, string $pageUrl = '', array $clientHints = []): array
    {
        $settings = SettingRepository::chat();
        $offline = (string) ($settings['chat_offline_message'] ?? 'Le conseiller est momentanément indisponible.');
        $sessionId = ChatConversationRepository::normalizeSessionId($sessionId);

        if (($settings['chat_enabled'] ?? '0') !== '1') {
            return ['reply' => $offline, 'session_id' => $sessionId, 'offline' => true];
        }

        $verdict = ChatGuard::inspect($message, $sessionId);
        if ($verdict['action'] === 'block') {
            self::recordWithoutModel($sessionId, $pageUrl, $clientHints, $message, $verdict['reply']);
            ChatGuard::block($sessionId);

            return ['reply' => $verdict['reply'], 'session_id' => $sessionId, 'blocked' => true];
        }
        if ($verdict['action'] === 'canned') {
            self::recordWithoutModel($sessionId, $pageUrl, $clientHints, $message, $verdict['reply']);

            return ['reply' => $verdict['reply'], 'session_id' => $sessionId];
        }

        $apiKey = self::apiKey($settings);
        if ($apiKey === '') {
            return ['reply' => $offline, 'session_id' => $sessionId, 'offline' => true];
        }

        $visitor = VisitorInfo::collect($clientHints);
        $conversation = ChatConversationRepository::findOrCreate($sessionId, $pageUrl, $visitor);
        $conversationId = (int) $conversation['id'];
        ChatConversationRepository::addMessage($conversationId, 'user', $message);

        $limit = (int) ($settings['chat_product_limit'] ?? 6);
        $products = self::findProducts($message, $pageUrl, $limit);
        $historyLimit = (int) ($settings['chat_history_limit'] ?? 10);
        $history = ChatConversationRepository::recentMessages($conversationId, $historyLimit);

        $language = ($settings['chat_multilingual'] ?? '1') === '1'
            ? self::detectLanguage($message)
            : 'fr';

        $messages = [
            ['role' => 'system', 'content' => self::buildSystemPrompt($settings, $products, $pageUrl, $language)],
        ];
        foreach ($history as $row) {
            $role = ($row['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $content = trim((string) ($row['content'] ?? ''));
            if ($content === '') {
                continue;
            }
            $messages[] = ['role' => $role, 'content' => $content];
        }
        $messages[] = ['role' => 'system', 'content' => self::languageLock($language)];

        try {
            $reply = self::complete($apiKey, $settings, $messages);
        } catch (Throwable $e) {
            ErrorLogger::error('Chat agent OpenAI: ' . $e->getMessage(), ['model' => $settings['chat_openai_model'] ?? '']);
            $reply = $offline;
        }

        $reply = mb_substr(trim(strip_tags($reply)), 0, 4000);
        if ($reply === '') {
            $reply = $offline;
        }

        $productSnapshot = [];
        foreach ($products as $product) {
            $productSnapshot[] = [
                'id' => (int) ($product['id'] ?? 0),
                'name' => (string) ($product['name'] ?? ''),
                'url' => Helpers::productUrl($product),
            ];
        }
        ChatConversationRepository::addMessage($conversationId, 'assistant', $reply, $productSnapshot !== [] ? $productSnapshot : null);

        return ['reply' => $reply, 'session_id' => $sessionId];
    }

    /**
     * @param array<string, mixed> $clientHints
     */
    private static function recordWithoutModel(
        string $sessionId,
        string $pageUrl,
        array $clientHints,
        string $message,
        string $reply
    ): void {
        $visitor = VisitorInfo::collect($clientHints);
        $conversation = ChatConversationRepository::findOrCreate($sessionId, $pageUrl, $visitor);
        $conversationId = (int) $conversation['id'];
        ChatConversationRepository::addMessage($conversationId, 'user', $message);
        if (trim($reply) !== '') {
            ChatConversationRepository::addMessage($conversationId, 'assistant', $reply);
        }
    }

    /** @param array<string, string> $settings */
    public static function apiKey(array $settings): string
    {
        $fromSettings = trim((string) ($settings['chat_openai_api_key'] ?? ''));
        if (self::looksLikeOpenAiKey($fromSettings)) {
            return $fromSettings;
        }
        $config = Helpers::config();
        $fromEnv = trim((string) ($config['openai_api_key'] ?? ''));

        return self::looksLikeOpenAiKey($fromEnv) ? $fromEnv : '';
    }

    public static function looksLikeOpenAiKey(string $key): bool
    {
        return str_starts_with($key, 'sk-') && strlen($key) >= 20;
    }

    public static function hasApiKey(?array $settings = null): bool
    {
        return self::apiKey($settings ?? SettingRepository::chat()) !== '';
    }

    public static function maskedApiKey(?array $settings = null): string
    {
        $key = self::apiKey($settings ?? SettingRepository::chat());
        if ($key === '') {
            return '';
        }
        $tail = substr($key, -4);

        return str_repeat('•', max(0, strlen($key) - 4)) . $tail;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function findProducts(string $message, string $pageUrl, int $limit): array
    {
        $limit = max(1, min(8, $limit));
        $found = [];
        $ids = [];

        if (preg_match('#/produit/([a-z0-9-]+)#i', $pageUrl, $m)) {
            $current = ProductRepository::findBySlug($m[1]);
            if ($current && (int) ($current['is_active'] ?? 0) === 1) {
                $found[] = $current;
                $ids[(int) $current['id']] = true;
            }
        }

        if (self::isGreetingOnly($message) && $found === []) {
            return [];
        }

        $queries = self::searchQueries($message);
        foreach ($queries as $query) {
            foreach (ProductRepository::searchForChat($query, $limit) as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id < 1 || isset($ids[$id])) {
                    continue;
                }
                $ids[$id] = true;
                $found[] = $row;
                if (count($found) >= $limit) {
                    return $found;
                }
            }
        }

        return $found;
    }

    /** @return list<string> */
    private static function greetingWords(): array
    {
        return [
            'bonjour', 'bonsoir', 'salut', 'hello', 'hola', 'hi', 'hey', 'coucou',
            'salam', 'salaam', 'salamalikoum', 'salamalaikum', 'azul', 'azoul',
            'labas', 'ahlan', 'marhba', 'mrhba', 'slm', 'sbah', 'msalkhir',
            'merci', 'thanks', 'thank', 'ok', 'okay', 'oui', 'non', 'cv', 'slt', 'bjr', 'bsr',
            'wach', 'wash',
        ];
    }

    private static function isGreetingOnly(string $message): bool
    {
        $normalized = mb_strtolower(trim($message));
        $normalized = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $normalized) ?? $normalized;
        $tokens = preg_split('/\s+/u', trim($normalized)) ?: [];
        $tokens = array_values(array_filter($tokens, static fn ($t) => mb_strlen($t) >= 2));
        if ($tokens === []) {
            return true;
        }
        $greet = self::greetingWords();
        $filler = ['je', 'tu', 'vous', 'nous', 'les', 'des', 'une', 'est', 'chez', 'vous', 'svp', 'sil'];
        foreach ($tokens as $token) {
            if (!in_array($token, $greet, true) && !in_array($token, $filler, true)) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> */
    private static function searchQueries(string $message): array
    {
        $message = self::normalizeSearchMessage(trim($message));
        if (self::isGreetingOnly($message)) {
            return [];
        }
        $stop = array_merge(self::greetingWords(), [
            's’il', 'sil', 'vous', 'nous', 'pour', 'avec', 'dans', 'une', 'des', 'les',
            'the', 'and', 'pourrais', 'voudrais', 'cherche', 'besoin', 'info', 'prix',
            'devis', 'svp', 'avez', 'avoir', 'quel', 'quelle', 'est', 'disponible',
            'dispo', 'chez', 'votre', 'vos', 'sur', 'pas',
            'bghit', 'bghiti', 'nbghi', 'kayn', 'kaynin', 'kain', '3afak', '3afakum',
            '3ndkom', '3andkom', '3andna', '3ndna', '3ndi', '3andi', '3andek',
            'kifach', 'kifash', 'chno', 'chnou', 'ghir', 'daba', 'bzzaf', 'bezzaf',
            'mzyan', 'wakha', 'walo', 'hadchi', 'smahli', 't9der', 'nchri',
        ]);
        $parts = preg_split('/[\s,;:!?]+/u', mb_strtolower($message)) ?: [];
        $specific = [];
        $generic = [];
        foreach ($parts as $part) {
            $part = trim($part, "\"'«».-");
            if ($part === '' || mb_strlen($part) < 3 || in_array($part, $stop, true)) {
                continue;
            }
            if (preg_match('/[0-9]/', $part) === 1 || str_contains($part, '-') || mb_strlen($part) >= 5) {
                $specific[] = $part;
            } else {
                $generic[] = $part;
            }
        }

        return array_values(array_unique(array_merge($specific, $generic)));
    }

    /**
     * @param array<string, string> $settings
     * @param list<array<string, mixed>> $products
     */
    public static function buildSystemPrompt(array $settings, array $products, string $pageUrl = '', string $language = 'fr'): string
    {
        $site = SettingRepository::site();
        $agent = trim((string) ($settings['chat_agent_name'] ?? 'Conseiller PHOTOCOM'));
        $whatsapp = Helpers::formatPhoneDisplay((string) ($site['whatsapp'] ?? ''));
        $phone = (string) ($site['phone'] ?? '');
        $parts = [];
        $parts[] = self::languageLock($language);
        $parts[] = trim((string) ($settings['chat_system_prompt'] ?? ''));
        $parts[] = 'Identité affichée : ' . $agent;
        $parts[] = 'Société : ' . ($site['site_name'] ?? 'PHOTOCOM');
        $parts[] = 'Ville : ' . trim(($site['address_city'] ?? 'Casablanca') . ', ' . ($site['address_country'] ?? 'Maroc'));
        if (trim((string) ($site['address'] ?? '')) !== '') {
            $parts[] = 'Adresse : ' . $site['address'];
        }
        if ($phone !== '') {
            $parts[] = 'Téléphone : ' . $phone;
        }
        if ($whatsapp !== '') {
            $parts[] = 'WhatsApp : ' . $whatsapp . ' (lien wa.me avec les chiffres du numéro)';
        }
        if (trim((string) ($site['email'] ?? '')) !== '') {
            $parts[] = 'Email : ' . $site['email'];
        }
        if (trim((string) ($site['hours'] ?? '')) !== '') {
            $parts[] = "Horaires :\n" . $site['hours'];
        }
        $parts[] = 'Page Contact : /contact — Catalogue : /catalogue.php';
        if ($pageUrl !== '') {
            $parts[] = 'Page actuelle du visiteur : ' . $pageUrl;
        }

        $noAnswer = trim((string) ($settings['chat_instruction_no_answer'] ?? ''));
        $insult = trim((string) ($settings['chat_instruction_insult'] ?? ''));
        $handoff = trim((string) ($settings['chat_instruction_handoff'] ?? ''));
        $extra = trim((string) ($settings['chat_instruction_extra'] ?? ''));
        if ($noAnswer !== '') {
            $parts[] = $noAnswer;
        }
        if ($insult !== '') {
            $parts[] = $insult;
        }
        if ($handoff !== '') {
            $parts[] = $handoff;
        }
        if (($settings['chat_multilingual'] ?? '1') === '1') {
            $lang = trim((string) ($settings['chat_instruction_language'] ?? ''));
            $parts[] = $lang !== '' ? $lang : 'Réponds dans la langue du visiteur.';
        }
        $parts[] = self::languageLock($language);
        if ($extra !== '') {
            $parts[] = $extra;
        }

        $parts[] = 'Règles : 1) catalogue uniquement 2) réponses courtes en puces 3) TOUJOURS le prix du contexte + le lien complet de la fiche demandée 4) salutations rendues 5) un produit en rupture RESTE affiché, avec sa dispo 6) WhatsApp du contexte pour commande. Aucun autre numéro.';

        if ($products === []) {
            $parts[] = 'Aucun produit du catalogue ne correspond clairement à ce message. Si c’est un salut ou une prise de contact, réponds chaleureusement DANS LA LANGUE VERROUILLÉE, demande ce que la personne cherche, et propose de créer un compte ou de se connecter (https://photocom.ma/compte). N’invente pas de fiche. Si c’est une demande produit précise : oriente vers le catalogue ou WhatsApp.';
        } else {
            $lines = ['Produits du catalogue à utiliser (uniquement ceux-ci, y compris ceux en rupture) :'];
            foreach ($products as $product) {
                $lines[] = self::formatProductLine($product, true, true);
            }
            $parts[] = implode("\n", $lines);
            $parts[] = "Pour chaque produit demandé, même s’il est en rupture : le nom sur une ligne, puis\n- Prix : …\n- Dispo : recopie EXACTEMENT le libellé Dispo du contexte\n- Fiche : l’URL complète\nNe cache jamais un produit du contexte parce qu’il est en rupture. Tu peux ajouter 1 alternative en stock EN PLUS, pas à la place.";
        }

        return implode("\n\n", array_filter($parts));
    }

    public static function detectLanguage(string $message): string
    {
        $raw = trim($message);
        if ($raw === '') {
            return 'fr';
        }
        if (preg_match('/\p{Arabic}/u', $raw) === 1) {
            return 'ar';
        }

        $text = mb_strtolower($raw);
        $darija = 0;
        $french = 0;
        $english = 0;
        $spanish = 0;

        $darijaWords = [
            'wach', 'wash', 'bghit', 'bghiti', 'bghina', 'nbghi', 'kayn', 'kaynin', 'kain',
            '3and', '3andi', '3ndi', '3andna', '3ndna', '3ndkom', '3andkom', '3andek', '3ndek',
            '3afak', '3afakum', 'kifach', 'kifash', 'chno', 'chnou', 'ch7al', 'chhal',
            'bzzaf', 'bezzaf', 'mzyan', 'mazyan', 'daba', 'ghir', 'labas', 'smahli', 'smhli',
            'wakha', 'walo', 'zwin', 'zwina', 'mazal', 'safi', '3lach', 'fin', 'hadchi',
            'hadak', 'dakchi', 'nchri', 'nchrih', '3tini', 'chof', 'chouf', 't9der', 'n9der',
            'm3ak', 'm3aya', 'dyal', 'dyali', 'makaynch', 'makaynach', 'salam', 'salaam',
            'slm', 'marhba', 'mrhba', 'ahlan', 'rah', 'raha', 'khoya', 'khti', 'walakin',
            'liya', 'lik', 'lina', '3la', '7ta', 'hta', 'b7al', 'bhal',
        ];
        foreach ($darijaWords as $word) {
            if (preg_match('/(^|[^\p{L}\p{N}])' . preg_quote($word, '/') . '($|[^\p{L}\p{N}])/u', $text) === 1) {
                $darija++;
            }
        }
        if (preg_match('/\b[35789][a-z]{2,}\b/u', $text) === 1) {
            $darija += 2;
        }

        $frenchWords = [
            'bonjour', 'bonsoir', 'salut', 's\'il', 'vous', 'avez', 'cherche', 'cherchez',
            'recherche', 'voudrais', 'besoin', 'combien', 'disponible', 'est-ce',
            'pouvez', 'pourriez', 'j\'ai', 'notre', 'votre',
        ];
        foreach ($frenchWords as $word) {
            if (preg_match('/(^|[^\p{L}\p{N}])' . preg_quote($word, '/') . '($|[^\p{L}\p{N}])/u', $text) === 1) {
                $french++;
            }
        }
        if (preg_match('/\b(je|tu|nous|vous|bonjour|est-ce)\b/u', $text) === 1) {
            $french++;
        }

        $englishWords = ['hello', 'hi', 'please', 'looking', 'need', 'have', 'price', 'available', 'thanks', 'thank', 'what'];
        foreach ($englishWords as $word) {
            if (preg_match('/(^|[^\p{L}\p{N}])' . preg_quote($word, '/') . '($|[^\p{L}\p{N}])/u', $text) === 1) {
                $english++;
            }
        }
        if (preg_match('/\bdo you\b/u', $text) === 1) {
            $english += 2;
        }

        $spanishWords = ['hola', 'tienes', 'busco', 'necesito', 'precio', 'gracias', 'buenos'];
        foreach ($spanishWords as $word) {
            if (preg_match('/(^|[^\p{L}\p{N}])' . preg_quote($word, '/') . '($|[^\p{L}\p{N}])/u', $text) === 1) {
                $spanish++;
            }
        }

        if ($darija >= 1 && $darija >= $french && $darija >= $english && $darija >= $spanish) {
            return 'darija_latin';
        }
        if ($english >= 2 && $english > $french && $english > $darija) {
            return 'en';
        }
        if ($spanish >= 2 && $spanish > $french && $spanish > $darija) {
            return 'es';
        }

        return 'fr';
    }

    public static function languageLock(string $language): string
    {
        return match ($language) {
            'darija_latin' => "=== VERROU LANGUE (PRIORITÉ MAXIMALE) ===\n"
                . "Le visiteur écrit en DARIJA MAROCAIN (alphabet latin / arabizi).\n"
                . "Tu DOIS répondre 100 % en darija latin, comme un vendeur de Casa : wach, kayn, bghit, 3ndna, labas, wakha, chno.\n"
                . "INTERDIT : une seule phrase en français. INTERDIT : « Oui, nous avons », « Que recherchez-vous », « Voici », « Je peux vous proposer », « Bonjour et bienvenue ».\n"
                . "Ignore la langue des messages assistant précédents s’ils étaient en français : suis UNIQUEMENT ce verrou.\n"
                . "Garde inchangés : noms de produits, marques, prix MAD, URLs, et les libellés Prix / Dispo / Fiche.\n"
                . "Exemple :\nWakha, kayn 3ndna.\n**BOYA BY-M1**\n- Prix : 150,00 MAD TTC\n- Dispo : en stock / à confirmer en magasin\n- Fiche : https://photocom.ma/produit/micro-cravate-by-m1\nBghitih filaire wla sans fil ?\nT9der tcréé un compte wla tconnecta : https://photocom.ma/compte",
            'ar' => "=== قفل اللغة (أولوية قصوى) ===\n"
                . "الزبون كتب بالعربية. جاوب فقط بالعربية الدارجة أو العربية البسيطة. ممنوع الفرنسية تماماً.\n"
                . "أسماء المنتجات والأسعار والروابط تبقى كما هي. العناوين Prix / Dispo / Fiche تبقى كما هي.\n"
                . "تجاهل الردود السابقة بالفرنسية.",
            'en' => 'LANGUAGE LOCK: Reply entirely in English, like a shop assistant. Keep product names, MAD prices, URLs, and the labels Prix / Dispo / Fiche. Ignore previous French assistant replies.',
            'es' => 'BLOQUEO DE IDIOMA: Responde solo en español. Nombres de productos, precios MAD y URLs sin cambiar. Etiquetas Prix / Dispo / Fiche iguales. Ignora respuestas anteriores en francés.',
            default => 'VERROU LANGUE : réponds en français. Marques, prix MAD, URLs et libellés Prix / Dispo / Fiche inchangés.',
        };
    }

    private static function normalizeSearchMessage(string $message): string
    {
        $replacements = [
            'ميكرو' => 'micro',
            'مايكرو' => 'micro',
            'مايك' => 'micro',
            'كاميرا' => 'camera',
            'mikro' => 'micro',
            'mikrou' => 'micro',
            'microu' => 'micro',
            'camira' => 'camera',
            'kamera' => 'camera',
            'kamira' => 'camera',
        ];
        $out = $message;
        foreach ($replacements as $from => $to) {
            $out = preg_replace('/' . preg_quote($from, '/') . '/iu', $to, $out) ?? $out;
        }

        return $out;
    }

    /** @param array<string, mixed> $product */
    private static function formatProductLine(array $product, bool $includePrices, bool $includeStock): string
    {
        $pricing = Helpers::productPricing($product);
        $url = Helpers::absoluteUrl(Helpers::productUrl($product));
        $bits = [
            '- ' . (string) ($product['name'] ?? 'Produit'),
        ];
        if (trim((string) ($product['brand'] ?? '')) !== '') {
            $bits[] = 'Marque : ' . $product['brand'];
        }
        if (trim((string) ($product['sku'] ?? '')) !== '') {
            $bits[] = 'SKU : ' . $product['sku'];
        }
        if ($includePrices) {
            $bits[] = 'Prix : ' . Helpers::formatPrice((float) $pricing['current']);
        }
        if ($includeStock) {
            $bits[] = 'Dispo : ' . self::stockLabel($product);
        }
        $bits[] = 'Lien : ' . $url;
        $excerpt = Helpers::productExcerpt((string) ($product['short_description'] ?? ''), 140);
        if ($excerpt !== '') {
            $bits[] = $excerpt;
        }

        return implode(' | ', $bits);
    }

    /** @param array<string, mixed> $product */
    public static function stockLabel(array $product): string
    {
        $out = !empty($product['is_out_of_stock']) || (($product['stock_status'] ?? '') === 'outofstock');
        $soon = !empty($product['is_coming_soon']);
        if ($soon) {
            return 'bientôt disponible / à confirmer en magasin';
        }

        return $out ? 'en rupture / à confirmer en magasin' : 'en stock / à confirmer en magasin';
    }

    /**
     * @param array<string, string> $settings
     * @param list<array{role:string,content:string}> $messages
     */
    private static function complete(string $apiKey, array $settings, array $messages): string
    {
        $model = trim((string) ($settings['chat_openai_model'] ?? ''));
        if ($model === '') {
            $model = (string) (Helpers::config()['openai_model'] ?? 'gpt-4o-mini');
        }
        $temperature = (float) ($settings['chat_temperature'] ?? 0.3);
        $temperature = max(0.0, min(1.2, $temperature));
        $maxTokens = (int) ($settings['chat_max_tokens'] ?? 500);
        $maxTokens = max(120, min(1200, $maxTokens));

        $payload = json_encode([
            'model' => $model,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
            'messages' => $messages,
        ], JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            throw new RuntimeException('Impossible de préparer la requête OpenAI.');
        }

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 40,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
        ]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException($curlError !== '' ? $curlError : 'Erreur réseau OpenAI.');
        }
        $decoded = json_decode($response, true);
        if ($status < 200 || $status >= 300) {
            $apiMessage = is_array($decoded) ? (string) ($decoded['error']['message'] ?? 'Erreur OpenAI') : 'Erreur OpenAI';
            throw new RuntimeException($apiMessage);
        }
        $text = is_array($decoded) ? (string) ($decoded['choices'][0]['message']['content'] ?? '') : '';
        if (trim($text) === '') {
            throw new RuntimeException('Réponse OpenAI vide.');
        }

        return $text;
    }
}
