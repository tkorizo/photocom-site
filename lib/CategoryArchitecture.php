<?php

declare(strict_types=1);

class CategoryArchitecture
{
    /** @return array<int, array{name: string, slug: string, menu_order: int, children: array<int, array{name: string, slug: string}>}> */
    public static function tree(): array
    {
        return [
            [
                'name' => 'Appareils photo & caméras',
                'slug' => 'appareils-photo',
                'menu_order' => 1,
                'children' => [
                    ['name' => 'Hybrides, reflex & compacts', 'slug' => 'hybrides-reflex-compacts'],
                    ['name' => 'Caméscopes & cinéma', 'slug' => 'camescopes-cinema'],
                    ['name' => 'Action, 360° & drones', 'slug' => 'action-360-drones'],
                ],
            ],
            [
                'name' => 'Objectifs & optique',
                'slug' => 'objectifs',
                'menu_order' => 2,
                'children' => [
                    ['name' => 'Objectifs Canon', 'slug' => 'objectifs-canon'],
                    ['name' => 'Objectifs Nikon', 'slug' => 'objectifs-nikon'],
                    ['name' => 'Objectifs Sony', 'slug' => 'objectifs-sony'],
                    ['name' => 'Autres montures & accessoires', 'slug' => 'objectifs-autres'],
                ],
            ],
            [
                'name' => 'Flash & éclairage',
                'slug' => 'flash-eclairage',
                'menu_order' => 3,
                'children' => [
                    ['name' => 'Flash cobra & studio', 'slug' => 'flash-cobra-studio'],
                    ['name' => 'LED continue & COB', 'slug' => 'led-continue-cob'],
                    ['name' => 'Modificateurs de lumière', 'slug' => 'modificateurs-lumiere'],
                ],
            ],
            [
                'name' => 'Fonds & studio',
                'slug' => 'fonds-studio',
                'menu_order' => 4,
                'children' => [
                    ['name' => 'Fonds papier', 'slug' => 'fonds-papier'],
                    ['name' => 'Fonds tissu & stretch', 'slug' => 'fonds-tissu-stretch'],
                    ['name' => 'Tentes & mini studio', 'slug' => 'tentes-mini-studio'],
                ],
            ],
            [
                'name' => 'Trépieds & supports',
                'slug' => 'trepieds-supports',
                'menu_order' => 5,
                'children' => [
                    ['name' => 'Trépieds & monopodes', 'slug' => 'trepieds-monopodes'],
                    ['name' => 'Bras, C-stand & grues', 'slug' => 'bras-cstand-grues'],
                ],
            ],
            [
                'name' => 'Stabilisation & vidéo',
                'slug' => 'stabilisation-video',
                'menu_order' => 6,
                'children' => [
                    ['name' => 'Gimbals & stabilisateurs', 'slug' => 'gimbals-stabilisateurs'],
                    ['name' => 'Sliders & dollies', 'slug' => 'sliders-dollies'],
                ],
            ],
            [
                'name' => 'Audio & micros',
                'slug' => 'audio-micros',
                'menu_order' => 7,
                'children' => [
                    ['name' => 'Micros sans fil', 'slug' => 'micros-sans-fil'],
                    ['name' => 'Micros fil & cravate', 'slug' => 'micros-fil'],
                    ['name' => 'Perche & casques', 'slug' => 'perche-casques'],
                ],
            ],
            [
                'name' => 'Impression & labo',
                'slug' => 'impression-labo',
                'menu_order' => 8,
                'children' => [
                    ['name' => 'Imprimantes photo', 'slug' => 'imprimantes-photo'],
                    ['name' => 'Papier & cartouches', 'slug' => 'papier-cartouches'],
                    ['name' => 'Chimie & pellicule', 'slug' => 'chimie-pellicule'],
                    ['name' => 'Photobooth, borne & sublimation', 'slug' => 'photobooth-sublimation'],
                ],
            ],
            [
                'name' => 'Accessoires',
                'slug' => 'accessoires',
                'menu_order' => 9,
                'children' => [
                    ['name' => 'Sacs & transport', 'slug' => 'sacs-transport'],
                    ['name' => 'Batteries & chargeurs', 'slug' => 'batteries-chargeurs'],
                    ['name' => 'Cartes mémoire & lecteurs', 'slug' => 'cartes-memoire'],
                    ['name' => 'Moniteurs & téléprompteur', 'slug' => 'moniteurs-teleprompteur'],
                    ['name' => 'Adaptateurs & divers', 'slug' => 'adaptateurs-divers'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function classificationRules(): array
    {
        return [
            'hybrides-reflex-compacts' => [
                'powershot', 'hybride', 'reflex', 'eos r', 'eos m', 'eos 5', 'eos 6', 'eos 7', 'eos 8', 'eos 9',
                'eos 1', 'eos 200', 'eos 250', 'eos 400', 'eos 850', 'eos 90', 'appareil photo', 'appareils photo',
                'boîtier nu', 'boitier nu', ' nikon d', 'z fc', 'z5', 'z6', 'z7', 'z8', 'z9', 'z30', 'z50', ' z f',
                'gfx', 'alpha 7', 'alpha 9', 'ilme-fx', 'camera line',
            ],
            'camescopes-cinema' => ['caméscope', 'camescope', 'xa60', 'xa65', 'xa70', 'cinéma', 'cinema'],
            'action-360-drones' => ['insta360', 'osmo pocket', 'osmo action', 'action 5', 'action camera', 'drone', ' mavic'],

            'objectifs-canon' => ['objectif canon', 'canon ef', 'canon rf', ' ef ', ' rf ', 'ef-s', 'ef-m'],
            'objectifs-nikon' => ['objectif nikon', 'nikon z', ' z mount', 'nikkor'],
            'objectifs-sony' => ['objectif sony', 'sony fe', ' fe ', 'sony e'],
            'objectifs-autres' => ['objectif', 'objectifs', 'mm f/', ' macro ', 'teleconverter', 'convertisseur', 'bague d\'adaptation', 'bague d\'adaptation'],

            'flash-cobra-studio' => [
                'speedlight', 'speedlite', 'flash canon', 'flash nikon', 'tête flash', 'kit flash', 'flash cobra',
                'fj80', 'fj400', '430ex', 'flash tolifo', 'mactop', 'tornado t-', 'lampe flash', 'flash ac slave',
                'déclencheur radio', 'support de lampe flash', ' mt-1000', ' mt-200', ' t-200b',
            ],
            'led-continue-cob' => [
                ' cob ', 'led tolifo', 'tolifo led', 'led video', 'led fan', 'panneau led', 'projecteur led',
                'projecteur', 'fresnel', 'lumière continue', 'ring light', 'mandarine', 'barn door', 'ventilateur de studio',
                'halogène', 'tolifo konway', 'hf1201', 'hf0601', 'hf0901', 'hf0301', 'hf0802', 'sk-d1200', 'gk-1024',
                'pt-176', 'pt-24b', 'pt-15b', 'hf-96b', 'gk-60b', 'selfie light', 'mini lampe led', 'ps-00',
            ],
            'modificateurs-lumiere' => [
                'softbox', 'parapluie', 'réflecteur', 'reflecteur', 'dôme diffuseur', 'rapid box', 'tente diffusante',
                'pro light mods', ' grid', 'lanterne', 'boîte à lumière', 'boite a lumiere', 'boule sphérique',
                'diffuseur', 'snoot', 'bol de beauté', 'bol de beaute', 'creative pack', 'switch insert',
                'adaptateur monture', 'adaptateur type', 'monture bowens', 'monture elinchrom', 'feuille de filtres', 'gélatines',
            ],

            'fonds-papier' => [
                'fond du studio en papier', 'fond en papier', 'background sable', 'background ',
                'ond du studio en papier', ' en vinyle', 'vinyle 2.72',
            ],
            'fonds-tissu-stretch' => [
                'fond stretch', 'fond en tissu', 'fond du studio', 'x-drop', 'backdrop', 'arrière-plan pliable',
                'kit 3 fonds stretch', 'kit support en forme de t', 'chromakey', 'chroma-green', 'chroma key',
                'dérouleur fond', 'support du fond', 'fond pliable réversible',
            ],
            'tentes-mini-studio' => ['tente photo', 'mini studio light kit', 'pt-22', 'pt-03', 'pt-02', 'pt-05'],

            'trepieds-monopodes' => ['trépied', 'trepied', 'monopod', 'perche à selfie', 'perche a selfie', 'selfie stick'],
            'bras-cstand-grues' => [
                'c-stand', 'bras magique', 'bras d', 'mini bras', 'support mobile', 'support mural',
                'support de lumière', 'support pliable', 'grue de caméra', 'grue de camera', 'ventouse', 'auto-pole', 'sky rail', 'tabouret',
                'support de perche mural', 'support du fond mobile',
            ],

            'gimbals-stabilisateurs' => [
                'gimbal', 'stabilisateur', 'rs 3', 'rs 4', 'rs 5', 'rs3', 'rs4', 'rs5', 'osmo mobile',
                'mechanical stabilizer', 'yelangu', 'camera cage', 'cage kit', 'dslr camera cage',
            ],
            'sliders-dollies' => ['slider', 'dolly', 'chariot support', 'slider motorisé', 'slider en fibre'],

            'micros-sans-fil' => ['micro sans fil', 'microphone sans fil', 'dji mic', 'sans fil', 'boyalink', 'wireless microphone'],
            'micros-fil' => [
                'micro avec fil', 'microphone fil', 'videomic', 'boya', 'boyamic', 'micro-cravate', 'by-m1', 'by-dm10',
                'kit pour créateurs vidéo',
            ],
            'perche-casques' => ['perche de microphone', 'perche micro', 'casque', 'mdr-7506', 'rod '],

            'imprimantes-photo' => [
                'imprimante', 'frontier', 'citizen cx', 'citizen cy', ' cx-', ' cy-', ' cz-', ' cx2w', 'digipro', 'idp smart', 'minilab', 'op-900',
            ],
            'papier-cartouches' => [
                'papier photo', 'papier brillant', 'fujicolor', 'cartouche', 'encre', 'consommable', 'de100', 'dx100', 'ruban ymcko',
                'cartes vierges en pvc', 'longue carte pour nettoyage', 'lot de 200 cartes',
            ],
            'chimie-pellicule' => ['chimie', 'pellicule', 'argentique', 'ra-4', ' cp-49', ' cp-48', 'developer', 'bleach'],
            'photobooth-sublimation' => [
                'borne photo', 'photo booth', 'miroir magique', '360° photo', 'présentoir rotatif', 'presentoir rotatif',
                'presse ', 'découpe', 'pince à découper', 'photobook', 'sublimation', 'trophée', 'kiosque photo', 'unisheen', 'sks215', 'machine photobook',
            ],

            'sacs-transport' => ['sac à dos', 'sac a dos', ' sac ', 'étui', 'etui', 'gilet de photographie', 'sacs de transport', 'flight case'],
            'batteries-chargeurs' => ['batterie', 'chargeur', 'lp-e', 'en-el', 'np-f', 'nb-', 'v-lock', 'alimentation'],
            'cartes-memoire' => ['carte mémoire', 'carte sd', 'cfexpress', 'lecteur sony', 'lecteur de carte', 'memory card', 'sdxc', 'sdhc', 'mrw-', 'carte vierge pvc'],
            'moniteurs-teleprompteur' => ['monitor', 'portkeys', 'teleprompter', 'téléprompteur', 'desview', 'enregistreur vidéo', 'unisheen ur'],
            'adaptateurs-divers' => [
                'clap icb', 'clap ', 'icb-', 'ruban gaffer', 'stylo de nettoyage', 'mini adaptateur de montage sur bras',
            ],
        ];
    }

    /** Ordre de priorité pour éviter les mauvais classements */
    public static function classificationPriority(): array
    {
        return [
            'hybrides-reflex-compacts', 'camescopes-cinema', 'action-360-drones',
            'objectifs-canon', 'objectifs-nikon', 'objectifs-sony', 'objectifs-autres',
            'imprimantes-photo', 'papier-cartouches', 'chimie-pellicule', 'photobooth-sublimation',
            'flash-cobra-studio', 'led-continue-cob', 'modificateurs-lumiere',
            'fonds-papier', 'fonds-tissu-stretch', 'tentes-mini-studio',
            'gimbals-stabilisateurs', 'sliders-dollies',
            'micros-sans-fil', 'micros-fil', 'perche-casques',
            'trepieds-monopodes', 'bras-cstand-grues',
            'sacs-transport', 'batteries-chargeurs', 'cartes-memoire', 'moniteurs-teleprompteur',
            'adaptateurs-divers',
        ];
    }

    public static function fallbackSlug(): string
    {
        return 'adaptateurs-divers';
    }
}
