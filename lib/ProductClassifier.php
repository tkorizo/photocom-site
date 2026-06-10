<?php

declare(strict_types=1);

class ProductClassifier
{
    /** @var array<string, array<string, int>>|null */
    private static ?array $scoreRules = null;

    public static function classify(array $product): string
    {
        $name = self::normalize((string) ($product['name'] ?? ''));
        $text = self::buildText($product);

        $definitive = self::matchDefinitive($name, $text);
        if ($definitive !== null) {
            return $definitive;
        }

        return self::scoreBest($name, $text);
    }

    public static function detectBrand(array $product): ?string
    {
        $text = self::buildText($product);
        $name = self::normalize((string) ($product['name'] ?? ''));

        $brands = [
            'Canon' => ['canon', 'eos r', 'eos m', 'eos 5', 'eos 6', 'eos 7', 'eos 8', 'eos 9', 'powershot', 'speedlite', ' ef ', ' rf ', 'ef-s', 'ef-m'],
            'Nikon' => ['nikon', 'nikkor', 'speedlight sb-', ' en-el', ' z mount', ' nikon z'],
            'Sony' => ['sony', 'alpha 7', 'alpha 9', ' ilme-fx', ' fe ', 'sel2470', 'sel', ' np-f', ' mrw-'],
            'Fujifilm' => ['fujifilm', 'fuji ', 'fujicolor', 'frontier', 'gfx', 'dx100', 'de100'],
            'DJI' => ['dji', 'osmo', 'ronin', 'rs 3', 'rs 4', 'rs 5', 'rs3', 'rs4', 'rs5'],
            'Insta360' => ['insta360', 'insta 360'],
            'Tolifo' => ['tolifo'],
            'Boya' => ['boya', 'boyamic', 'boyalink'],
            'Westcott' => ['westcott', 'fj80', 'fj400', 'fj-x', 'rapid box', 'x-drop'],
            'Citizen' => ['citizen', ' cx-', ' cy-', ' cx2w', 'op-900'],
            'Desview' => ['desview'],
            'Godox' => ['godox'],
            'Rode' => ['rode', 'videomic'],
            'Sigma' => ['sigma '],
            'Tamron' => ['tamron'],
            'PortKeys' => ['portkeys'],
            'DIGIPRO' => ['digipro'],
            'IDP Smart' => ['idp smart', 'smart-idp', 'smart 31', 'smart 51'],
            'Godspeed' => ['godspeed'],
            'Komers' => ['komers'],
        ];

        foreach ($brands as $brand => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword) || str_contains($name, trim($keyword))) {
                    return $brand;
                }
            }
        }

        return null;
    }

    public static function buildText(array $product): string
    {
        $parts = [
            (string) ($product['name'] ?? ''),
            (string) ($product['short_description'] ?? ''),
            self::stripHtml((string) ($product['description'] ?? '')),
        ];

        return self::normalize(implode(' ', $parts));
    }

    private static function normalize(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = mb_strtolower($text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return ' ' . trim($text) . ' ';
    }

    private static function stripHtml(string $html): string
    {
        return strip_tags($html);
    }

    private static function matchDefinitive(string $name, string $text): ?string
    {
        // Transport — priorité absolue (évite les faux positifs « reflex / appareil photo » dans la description)
        if (self::containsAny($text, [
            'sac à dos', 'sac a dos', 'sacoche', 'housse ', 'étui ', 'etui ', 'flight case',
            'sacs de transport', 'bag for camera', 'rolling bag', 'gilet de photographie',
            ' sac photo', 'sac bandoulière', 'sac bandouliere',
        ])) {
            return 'sacs-transport';
        }

        $rules = [
            'moniteurs-teleprompteur' => [
                'téléprompteur', 'teleprompteur', 'teleprompter', 'desview dt', 'desview tp', 'desview bg',
                'prompteur', 'portkeys', 'moniteur ', 'monitor ', 'enregistreur vidéo', 'unisheen ur',
            ],
            'gimbals-stabilisateurs' => [
                'gimbal', 'stabilisateur dji', 'osmo mobile', 'rs 3', 'rs 4', 'rs 5', 'rs3', 'rs4', 'rs5',
                'mechanical stabilizer', 'yelangu', 'camera cage', 'cage kit', 'dslr camera cage',
            ],
            'action-360-drones' => [
                'insta360', 'osmo action', 'osmo pocket', 'action 5 pro', 'action camera', ' drone', 'mavic',
            ],
            'micros-sans-fil' => [
                'micro sans fil', 'microphone sans fil', 'dji mic', 'boyalink', 'wireless microphone',
                'micro cravate sans fil', 'by-wm', 'sans fil 2.4',
            ],
            'micros-fil' => [
                'micro-cravate', 'micro cravate', 'microphone cravate', 'by-m1', 'by-m1000', 'by-dm',
                'videomic', 'micro studio boya', 'microphone fil', 'kit pour créateurs vidéo',
            ],
            'perche-casques' => [
                'perche de microphone', 'perche micro', 'perche à micro', 'casque', 'mdr-7506', 'headphone',
            ],
            'imprimantes-photo' => [
                'imprimante', 'frontier de', 'frontier d', 'citizen cx', 'citizen cy', 'citizen op',
                ' cx-02', ' cy-02', ' cz-01', ' cx2w', 'digipro', 'idp smart', 'minilab', 'op-900',
                'imprimante jet d\'encre fuji',
            ],
            'papier-cartouches' => [
                'papier photo', 'papier brillant', 'fujicolor', 'cartouche', 'cartouches', 'encre ',
                'consommable', 'ruban ymcko', 'cartes vierges en pvc', 'longue carte pour nettoyage',
                'lot de 200 cartes', 'stylo de nettoyage', 'tête thermique',
            ],
            'chimie-pellicule' => [
                'chimie', 'pellicule', 'argentique', 'ra-4', ' cp-49', ' cp-48', 'kodak gold', 'kodak ',
            ],
            'photobooth-sublimation' => [
                'photo booth', 'miroir magique', 'borne photo', '360° photo', 'présentoir rotatif',
                'presentoir rotatif', 'sublimation', 'photobook', 'trophée bxp', 'kiosque photo', 'sks215',
                'machine photobook', 'presse ', 'pince à découper', 'pince découpe', 'découpe photo',
            ],
            'modificateurs-lumiere' => [
                'softbox', 'parapluie', 'réflecteur', 'reflecteur', 'snoot', 'dôme diffuseur',
                'rapid box', 'tente diffusante', 'boîte à lumière', 'boite a lumiere', 'lanterne',
                'boule sphérique', ' grid', 'bol de beauté', 'switch insert', 'adaptateur monture',
                'monture bowens', 'monture elinchrom', 'feuille de filtres', 'gélatines', 'creative pack',
            ],
            'flash-cobra-studio' => [
                'speedlight', 'speedlite', 'flash cobra', 'tête flash', 'kit flash', 'flash tolifo',
                'mactop', 'tornado t-', 'lampe flash', 'flash ac slave', 'déclencheur radio', 'déclencheur de flash',
                'support de lampe flash', ' mt-1000', ' mt-200', ' t-200b', '430ex', 'sb-5000', 'sb-',
            ],
            'led-continue-cob' => [
                ' cob ', 'led tolifo', 'tolifo led', 'led video', 'panneau led', 'projecteur led',
                'projecteur fresnel', 'fresnel', 'ring light', 'mandarine', 'barn door', 'lumière continue',
                'tolifo konway', 'selfie light', 'mini lampe led', 'led pour smartphone', 'led light pour smartphone',
                'pt-176', 'pt-24b', 'pt-15b', 'hf-96b', 'gk-60b', 'sk-d1200', 'gk-1024', 'ventilateur de studio',
            ],
            'fonds-papier' => [
                'fond du studio en papier', 'fond en papier', 'background sable', ' en vinyle', 'vinyle 2.72',
                'ond du studio en papier',
            ],
            'fonds-tissu-stretch' => [
                'fond stretch', 'fond en tissu', 'x-drop', 'chromakey', 'chroma-green', 'chroma key',
                'dérouleur fond', 'support du fond', 'fond pliable réversible', 'fond du studio blanc en tissu',
                'fond du studio noir en tissu', 'fond du studio gris en tissu', 'fond du studio vert',
            ],
            'tentes-mini-studio' => [
                'tente photo', 'mini studio light kit', 'pt-22', 'pt-03', 'pt-02', 'pt-05',
            ],
            'trepieds-monopodes' => [
                'trépied', 'trepied', 'monopod', 'perche à selfie', 'perche a selfie', 'selfie stick',
                'perche invisible',
            ],
            'sliders-dollies' => [
                'slider', 'dolly', 'chariot support', 'track dolly', 'slider motorisé', 'slider en fibre',
            ],
            'bras-cstand-grues' => [
                'c-stand', 'bras magique', 'mini bras', 'support mobile', 'support mural', 'support de lumière',
                'grue de caméra', 'grue de camera', 'ventouse', 'auto-pole', 'sky rail', 'tabouret',
                'support de perche mural', 'support du fond mobile', 'barre télescopique',
            ],
            'batteries-chargeurs' => [
                'batterie', 'chargeur', 'lp-e', 'en-el', 'np-f', 'nb-', 'v-lock', 'alimentation',
            ],
            'cartes-memoire' => [
                'carte mémoire', 'carte sd', 'cfexpress', 'lecteur de carte', 'lecteur sony', 'memory card',
                'sdxc', 'sdhc', 'mrw-',
            ],
            'adaptateurs-divers' => [
                'clap icb', 'clap ', 'icb-', 'ruban gaffer',
            ],
        ];

        // Kits appareil + objectif → appareils (before objectif rules)
        if (self::isCameraKit($name, $text)) {
            if (self::containsAny($text, ['caméscope', 'camescope', 'xa60', 'xa65', 'xa70', 'zr caméra', 'ilme-fx'])) {
                return 'camescopes-cinema';
            }
            return 'hybrides-reflex-compacts';
        }

        if (self::containsAny($name, ['objectif ', 'objectifs ']) || self::isStandaloneLens($name, $text)) {
            return self::classifyLens($text);
        }

        foreach ($rules as $slug => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $slug;
                }
            }
        }

        // Appareils photo (after sac/micro/etc.)
        if (self::isCameraBody($name, $text)) {
            if (self::containsAny($text, ['caméscope', 'camescope', 'xa60', 'xa65', 'xa70', 'cinéma', 'ilme-fx', 'zr '])) {
                return 'camescopes-cinema';
            }
            return 'hybrides-reflex-compacts';
        }

        return null;
    }

    private static function isCameraKit(string $name, string $text): bool
    {
        if (str_contains($name, 'kit appareil') || str_contains($name, 'kit ') && str_contains($text, 'objectif')) {
            return true;
        }

        $kitPatterns = [
            'eos r', 'eos m', ' nikon z', 'canon r', ' avec objectif', ' + rf-', ' + rf ', ' + objectif',
            'boîtier nu', 'boitier nu', 'hybride', 'reflex', 'powershot',
        ];

        foreach ($kitPatterns as $pattern) {
            if (str_contains($text, $pattern) && !str_contains($name, 'objectif canon ef') && !self::startsWithObjectif($name)) {
                // Ne pas traiter les accessoires transport comme un kit appareil
                if (self::containsAny($name, ['sac ', 'sacoche', 'housse', 'étui', 'etui ', 'flight case'])) {
                    continue;
                }
                return true;
            }
        }

        return false;
    }

    private static function isStandaloneLens(string $name, string $text): bool
    {
        if (self::startsWithObjectif($name)) {
            return true;
        }

        return (bool) preg_match('/\d+\s*-\s*\d+\s*mm\s*f\//', $name)
            || (bool) preg_match('/\d+mm\s*f\//', $name)
            || str_contains($name, 'teleconverter')
            || str_contains($name, 'convertisseur');
    }

    private static function startsWithObjectif(string $name): bool
    {
        $trim = ltrim($name);
        return str_starts_with($trim, 'objectif ') || str_starts_with($trim, 'objectifs ');
    }

    private static function classifyLens(string $text): string
    {
        if (self::containsAny($text, ['canon ef', 'canon rf', ' ef ', ' rf ', 'ef-s', 'ef-m', 'objectif canon'])) {
            return 'objectifs-canon';
        }
        if (self::containsAny($text, ['nikon z', 'nikkor', ' z mount', 'objectif nikon'])) {
            return 'objectifs-nikon';
        }
        if (self::containsAny($text, ['sony fe', ' sony e', 'objectif sony', ' sel'])) {
            return 'objectifs-sony';
        }

        return 'objectifs-autres';
    }

    private static function isCameraBody(string $name, string $text): bool
    {
        if (self::startsWithObjectif($name) || self::isStandaloneLens($name, $text)) {
            return false;
        }

        $patterns = [
            'eos r', 'eos m', 'eos 5', 'eos 6', 'eos 7', 'eos 8', 'eos 9', 'eos 90', 'eos 200', 'eos 400',
            ' nikon d', ' nikon z', 'z fc', 'z5', 'z6', 'z7', 'z8', 'z9', 'z30', 'z50',
            'alpha 7', 'alpha 9', 'powershot', 'gfx', 'boîtier', 'boitier',
        ];

        if (self::containsAny($name, ['sac ', 'sacoche', 'housse', 'étui', 'etui '])) {
            return false;
        }

        return self::containsAny($text, $patterns);
    }

    private static function scoreBest(string $name, string $text): string
    {
        $scores = [];
        foreach (self::getScoreRules() as $slug => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword => $weight) {
                if (str_contains($name, $keyword)) {
                    $score += $weight * 3;
                } elseif (str_contains($text, $keyword)) {
                    $score += $weight;
                }
            }
            if ($score > 0) {
                $scores[$slug] = $score;
            }
        }

        if (empty($scores)) {
            return CategoryArchitecture::fallbackSlug();
        }

        arsort($scores);

        return (string) array_key_first($scores);
    }

    /** @return array<string, array<string, int>> */
    private static function getScoreRules(): array
    {
        if (self::$scoreRules !== null) {
            return self::$scoreRules;
        }

        $flat = [];
        foreach (CategoryArchitecture::classificationRules() as $slug => $keywords) {
            $flat[$slug] = [];
            foreach ($keywords as $keyword) {
                $flat[$slug][mb_strtolower(trim($keyword))] = 1;
            }
        }

        self::$scoreRules = $flat;

        return self::$scoreRules;
    }

    private static function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }
}
