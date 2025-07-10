<?php
/**
 * Snippet ID: 191
 * Name: INTERFACE grille tarifaire
 * Description: * Active: false
 * @active false
 */

/**
 * Interface d'administration pour la gestion de la grille tarifaire
 * Permet de modifier facilement les prix et combinaisons des formations CACES/ACES et Habilitations Electriques
 */

// Securite : empecher l'acces direct
if (!defined('ABSPATH')) {
    exit;
}

// Fonction de base pour la grille tarifaire (remplace grille-tarifs.txt)
if (!function_exists('get_default_price_table')) {
    function get_default_price_table() {
        return [
            // -- R489 --
            'caces_r489' => [
                'recyclage' => [
                    'message_if_invalid' => "Pour R489 Recyclage, vous devez choisir l'une des combinaisons suivantes : 1A, 1B, 3, 5 (seules ou en combinaisons jusqu'à 3) – voir tarif correspondant.",
                    'combos' => [
                        // 1 Catégorie
                        ['categories' => ['1A'],                 'price' => 380],
                        ['categories' => ['1B'],                 'price' => 380],
                        ['categories' => ['3'],                  'price' => 380],
                        ['categories' => ['5'],                  'price' => 380],

                        // 2 Catégories
                        ['categories' => ['1A','1B'],            'price' => 450],
                        ['categories' => ['1A','3'],             'price' => 450],
                        ['categories' => ['1A','5'],             'price' => 450],
                        ['categories' => ['1B','3'],             'price' => 450],
                        ['categories' => ['1B','5'],             'price' => 450],
                        ['categories' => ['3','5'],              'price' => 450],

                        // 3 Catégories
                        ['categories' => ['1A','1B','3'],        'price' => 520],
                        ['categories' => ['1A','1B','5'],        'price' => 520],
                        ['categories' => ['1A','3','5'],         'price' => 520],
                        ['categories' => ['1B','3','5'],         'price' => 520],
                    ],
                ],
                'initial' => [
                    'message_if_invalid' => "Pour R489 Initial, vous devez choisir l'une des combinaisons suivantes : 1A, 1B, 3, 5 (seules ou en combinaisons jusqu'à 3) – voir tarif correspondant.",
                    'combos' => [
                        // 1 Catégorie
                        ['categories' => ['1A'],                 'price' => 450],
                        ['categories' => ['1B'],                 'price' => 450],
                        ['categories' => ['3'],                  'price' => 450],
                        ['categories' => ['5'],                  'price' => 450],

                        // 2 Catégories
                        ['categories' => ['1A','1B'],            'price' => 520],
                        ['categories' => ['1A','3'],             'price' => 520],
                        ['categories' => ['1A','5'],             'price' => 520],
                        ['categories' => ['1B','3'],             'price' => 520],
                        ['categories' => ['1B','5'],             'price' => 520],
                        ['categories' => ['3','5'],              'price' => 520],

                        // 3 Catégories
                        ['categories' => ['1A','1B','3'],        'price' => 590],
                        ['categories' => ['1A','1B','5'],        'price' => 590],
                        ['categories' => ['1A','3','5'],         'price' => 590],
                        ['categories' => ['1B','3','5'],         'price' => 590],
                    ],
                ],
            ],

            // -- ACES R489 --
            'aces_r489' => [
                'recyclage' => [
                    'message_if_invalid' => "Pour ACES R489 Recyclage, vous devez choisir l'une des combinaisons suivantes : 1A, 1B, 3, 5 (seules ou en combinaisons jusqu'à 3) – voir tarif correspondant.",
                    'combos' => [
                        // 1 Catégorie
                        ['categories' => ['1A'],                 'price' => 0],
                        ['categories' => ['1B'],                 'price' => 0],
                        ['categories' => ['3'],                  'price' => 0],
                        ['categories' => ['5'],                  'price' => 0],

                        // 2 Catégories
                        ['categories' => ['1A','1B'],            'price' => 0],
                        ['categories' => ['1A','3'],             'price' => 0],
                        ['categories' => ['1A','5'],             'price' => 0],
                        ['categories' => ['1B','3'],             'price' => 0],
                        ['categories' => ['1B','5'],             'price' => 0],
                        ['categories' => ['3','5'],              'price' => 0],

                        // 3 Catégories
                        ['categories' => ['1A','1B','3'],        'price' => 0],
                        ['categories' => ['1A','1B','5'],        'price' => 0],
                        ['categories' => ['1A','3','5'],         'price' => 0],
                        ['categories' => ['1B','3','5'],         'price' => 0],
                    ],
                ],
                'initial' => [
                    'message_if_invalid' => "Pour ACES R489 Initial, vous devez choisir l'une des combinaisons suivantes : 1A, 1B, 3, 5 (seules ou en combinaisons jusqu'à 3) – voir tarif correspondant.",
                    'combos' => [
                        // 1 Catégorie
                        ['categories' => ['1A'],                 'price' => 0],
                        ['categories' => ['1B'],                 'price' => 0],
                        ['categories' => ['3'],                  'price' => 0],
                        ['categories' => ['5'],                  'price' => 0],

                        // 2 Catégories
                        ['categories' => ['1A','1B'],            'price' => 0],
                        ['categories' => ['1A','3'],             'price' => 0],
                        ['categories' => ['1A','5'],             'price' => 0],
                        ['categories' => ['1B','3'],             'price' => 0],
                        ['categories' => ['1B','5'],             'price' => 0],
                        ['categories' => ['3','5'],              'price' => 0],

                        // 3 Catégories
                        ['categories' => ['1A','1B','3'],        'price' => 0],
                        ['categories' => ['1A','1B','5'],        'price' => 0],
                        ['categories' => ['1A','3','5'],         'price' => 0],
                        ['categories' => ['1B','3','5'],         'price' => 0],
                    ],
                ],
            ],

            // -- R486 --
            'caces_r486' => [
                'initial' => [
                    'message_if_invalid' => "Pour R486 Initial, vous devez choisir l'une des combinaisons suivantes : A, B, C (seules ou en combinaisons) – voir tarif correspondant.",
                    'combos' => [
                        // 1 Catégorie
                        ['categories' => ['A'],                  'price' => 450],
                        ['categories' => ['B'],                  'price' => 450],
                        ['categories' => ['C'],                  'price' => 450],

                        // 2 Catégories
                        ['categories' => ['A','B'],              'price' => 520],
                        ['categories' => ['A','C'],              'price' => 520],
                        ['categories' => ['B','C'],              'price' => 520],

                        // 3 Catégories
                        ['categories' => ['A','B','C'],          'price' => 590],
                    ],
                ],
                'recyclage' => [
                    'message_if_invalid' => "Pour R486 Recyclage, vous devez choisir l'une des combinaisons suivantes : A, B, C (seules ou en combinaisons) – voir tarif correspondant.",
                    'combos' => [
                        // 1 Catégorie
                        ['categories' => ['A'],                  'price' => 380],
                        ['categories' => ['B'],                  'price' => 380],
                        ['categories' => ['C'],                  'price' => 380],

                        // 2 Catégories
                        ['categories' => ['A','B'],              'price' => 450],
                        ['categories' => ['A','C'],              'price' => 450],
                        ['categories' => ['B','C'],              'price' => 450],

                        // 3 Catégories
                        ['categories' => ['A','B','C'],          'price' => 520],
                    ],
                ],
            ],

            // -- ACES R486 --
            'aces_r486' => [
                'initial' => [
                    'message_if_invalid' => "Pour ACES R486 Initial, vous devez choisir l'une des combinaisons suivantes : A, B, C (seules ou en combinaisons) – voir tarif correspondant.",
                    'combos' => [
                        // 1 Catégorie
                        ['categories' => ['A'],                  'price' => 0],
                        ['categories' => ['B'],                  'price' => 0],
                        ['categories' => ['C'],                  'price' => 0],

                        // 2 Catégories
                        ['categories' => ['A','B'],              'price' => 0],
                        ['categories' => ['A','C'],              'price' => 0],
                        ['categories' => ['B','C'],              'price' => 0],

                        // 3 Catégories
                        ['categories' => ['A','B','C'],          'price' => 0],
                    ],
                ],
                'recyclage' => [
                    'message_if_invalid' => "Pour ACES R486 Recyclage, vous devez choisir l'une des combinaisons suivantes : A, B, C (seules ou en combinaisons) – voir tarif correspondant.",
                    'combos' => [
                        // 1 Catégorie
                        ['categories' => ['A'],                  'price' => 0],
                        ['categories' => ['B'],                  'price' => 0],
                        ['categories' => ['C'],                  'price' => 0],

                        // 2 Catégories
                        ['categories' => ['A','B'],              'price' => 0],
                        ['categories' => ['A','C'],              'price' => 0],
                        ['categories' => ['B','C'],              'price' => 0],

                        // 3 Catégories
                        ['categories' => ['A','B','C'],          'price' => 0],
                    ],
                ],
            ],

            // -- R482 --
            'caces_r482' => [
                'initial' => [
                    'message_if_invalid' => "Pour R482 Initial, seules les combos A, B1, C1, F, G ou leurs combinaisons sont valables.",
                    'combos' => [
                        // 1 Catégorie
                        ['categories' => ['A'],                  'price' => 690],
                        ['categories' => ['B1'],                 'price' => 990],
                        ['categories' => ['C1'],                 'price' => 790],
                        ['categories' => ['F'],                  'price' => 790],
                        ['categories' => ['G'],                  'price' => 990],

                        // 2 Catégories
                        ['categories' => ['A','C1'],             'price' => 1190],
                        ['categories' => ['A','F'],              'price' => 1190],
                        ['categories' => ['C1','F'],             'price' => 1190],
                        ['categories' => ['B1','C1'],            'price' => 1290],
                        ['categories' => ['A','B1'],             'price' => 1290],
                        ['categories' => ['A','G'],              'price' => 1290],
                        ['categories' => ['B1','F'],             'price' => 1290],
                        ['categories' => ['B1','G'],             'price' => 1290],
                        ['categories' => ['C1','G'],             'price' => 1290],
                        ['categories' => ['F','G'],              'price' => 1290],

                        // 3 Catégories
                        ['categories' => ['A','C1','F'],         'price' => 1310],
                        ['categories' => ['A','B1','G'],         'price' => 1490],
                        ['categories' => ['A','B1','C1'],        'price' => 1490],
                        ['categories' => ['A','B1','F'],         'price' => 1490],
                        ['categories' => ['A','C1','G'],         'price' => 1490],
                        ['categories' => ['A','F','G'],          'price' => 1490],
                        ['categories' => ['B1','C1','F'],        'price' => 1490],
                        ['categories' => ['B1','C1','G'],        'price' => 1490],
                        ['categories' => ['B1','F','G'],         'price' => 1490],

                        // Catégories à 4
                        ['categories' => ['A', 'B1', 'C1', 'F'],  'price' => 0],
                        ['categories' => ['A', 'B1', 'C1', 'G'],  'price' => 0],
                        ['categories' => ['A', 'C1', 'F', 'G'],   'price' => 0],
                        ['categories' => ['B1', 'C1', 'F', 'G'],  'price' => 0],

                        // Catégories à 5
                        ['categories' => ['A', 'B1', 'C1', 'F', 'G'],  'price' => 0],
                    ],
                ],
                'recyclage' => [
                    'message_if_invalid' => "Pour R482 Recyclage, choisissez parmi A, B1, C1, F, G ou leurs combinaisons listées.",
                    'combos' => [
                        // 1 Catégorie
                        ['categories' => ['A'],                  'price' => 590],
                        ['categories' => ['B1'],                 'price' => 890],
                        ['categories' => ['C1'],                 'price' => 690],
                        ['categories' => ['F'],                  'price' => 690],
                        ['categories' => ['G'],                  'price' => 890],

                        // 2 Catégories
                        ['categories' => ['A','C1'],             'price' => 1050],
                        ['categories' => ['A','F'],              'price' => 1050],
                        ['categories' => ['C1','F'],             'price' => 1050],
                        ['categories' => ['B1','C1'],            'price' => 1190],
                        ['categories' => ['A','B1'],             'price' => 1190],
                        ['categories' => ['A','G'],              'price' => 1190],
                        ['categories' => ['B1','F'],             'price' => 1190],
                        ['categories' => ['B1','G'],             'price' => 1190],
                        ['categories' => ['C1','G'],             'price' => 1190],
                        ['categories' => ['F','G'],              'price' => 1190],

                        // 3 Catégories
                        ['categories' => ['A','C1','F'],         'price' => 1210],
                        ['categories' => ['A','B1','G'],         'price' => 1350],
                        ['categories' => ['A','B1','C1'],        'price' => 1350],
                        ['categories' => ['A','B1','F'],         'price' => 1350],
                        ['categories' => ['A','C1','G'],         'price' => 1350],
                        ['categories' => ['A','F','G'],          'price' => 1350],
                        ['categories' => ['B1','C1','F'],        'price' => 1350],
                        ['categories' => ['B1','C1','G'],        'price' => 1350],
                        ['categories' => ['B1','F','G'],         'price' => 1350],

                        // Catégories à 4
                        ['categories' => ['A', 'B1', 'C1', 'F'],  'price' => 0],
                        ['categories' => ['A', 'B1', 'C1', 'G'],  'price' => 0],
                        ['categories' => ['A', 'C1', 'F', 'G'],   'price' => 0],
                        ['categories' => ['B1', 'C1', 'F', 'G'],  'price' => 0],

                        // Catégories à 5
                        ['categories' => ['A', 'B1', 'C1', 'F', 'G'],  'price' => 0],
                    ],
                ],
            ],

            // -- ACES R482 --
            'aces_r482' => [
                'initial' => [
                    'message_if_invalid' => "Pour ACES R482 Initial, seules les combos A, B1, C1, F, G ou leurs combinaisons sont valables.",
                    'combos' => [
                        // 1 Catégorie
                        ['categories' => ['A'],                  'price' => 0],
                        ['categories' => ['B1'],                 'price' => 0],
                        ['categories' => ['C1'],                 'price' => 0],
                        ['categories' => ['F'],                  'price' => 0],
                        ['categories' => ['G'],                  'price' => 0],

                        // 2 Catégories
                        ['categories' => ['A','C1'],             'price' => 0],
                        ['categories' => ['A','F'],              'price' => 0],
                        ['categories' => ['C1','F'],             'price' => 0],
                        ['categories' => ['B1','C1'],            'price' => 0],
                        ['categories' => ['A','B1'],             'price' => 0],
                        ['categories' => ['A','G'],              'price' => 0],
                        ['categories' => ['B1','F'],             'price' => 0],
                        ['categories' => ['B1','G'],             'price' => 0],
                        ['categories' => ['C1','G'],             'price' => 0],
                        ['categories' => ['F','G'],              'price' => 0],

                        // 3 Catégories
                        ['categories' => ['A','C1','F'],         'price' => 0],
                        ['categories' => ['A','B1','G'],         'price' => 0],
                        ['categories' => ['A','B1','C1'],        'price' => 0],
                        ['categories' => ['A','B1','F'],         'price' => 0],
                        ['categories' => ['A','C1','G'],         'price' => 0],
                        ['categories' => ['A','F','G'],          'price' => 0],
                        ['categories' => ['B1','C1','F'],        'price' => 0],
                        ['categories' => ['B1','C1','G'],        'price' => 0],
                        ['categories' => ['B1','F','G'],         'price' => 0],

                        // Catégories à 4
                        ['categories' => ['A', 'B1', 'C1', 'F'],  'price' => 0],
                        ['categories' => ['A', 'B1', 'C1', 'G'],  'price' => 0],
                        ['categories' => ['A', 'C1', 'F', 'G'],   'price' => 0],
                        ['categories' => ['B1', 'C1', 'F', 'G'],  'price' => 0],

                        // Catégories à 5
                        ['categories' => ['A', 'B1', 'C1', 'F', 'G'],  'price' => 0],
                    ],
                ],
                'recyclage' => [
                    'message_if_invalid' => "Pour ACES R482 Recyclage, choisissez parmi A, B1, C1, F, G ou leurs combinaisons listées.",
                    'combos' => [
                        // 1 Catégorie
                        ['categories' => ['A'],                  'price' => 0],
                        ['categories' => ['B1'],                 'price' => 0],
                        ['categories' => ['C1'],                 'price' => 0],
                        ['categories' => ['F'],                  'price' => 0],
                        ['categories' => ['G'],                  'price' => 0],

                        // 2 Catégories
                        ['categories' => ['A','C1'],             'price' => 0],
                        ['categories' => ['A','F'],              'price' => 0],
                        ['categories' => ['C1','F'],             'price' => 0],
                        ['categories' => ['B1','C1'],            'price' => 0],
                        ['categories' => ['A','B1'],             'price' => 0],
                        ['categories' => ['A','G'],              'price' => 0],
                        ['categories' => ['B1','F'],             'price' => 0],
                        ['categories' => ['B1','G'],             'price' => 0],
                        ['categories' => ['C1','G'],             'price' => 0],
                        ['categories' => ['F','G'],              'price' => 0],

                        // 3 Catégories
                        ['categories' => ['A','C1','F'],         'price' => 0],
                        ['categories' => ['A','B1','G'],         'price' => 0],
                        ['categories' => ['A','B1','C1'],        'price' => 0],
                        ['categories' => ['A','B1','F'],         'price' => 0],
                        ['categories' => ['A','C1','G'],         'price' => 0],
                        ['categories' => ['A','F','G'],          'price' => 0],
                        ['categories' => ['B1','C1','F'],        'price' => 0],
                        ['categories' => ['B1','C1','G'],        'price' => 0],
                        ['categories' => ['B1','F','G'],         'price' => 0],

                        // Catégories à 4
                        ['categories' => ['A', 'B1', 'C1', 'F'],  'price' => 0],
                        ['categories' => ['A', 'B1', 'C1', 'G'],  'price' => 0],
                        ['categories' => ['A', 'C1', 'F', 'G'],   'price' => 0],
                        ['categories' => ['B1', 'C1', 'F', 'G'],  'price' => 0],

                        // Catégories à 5
                        ['categories' => ['A', 'B1', 'C1', 'F', 'G'],  'price' => 0],
                    ],
                ],
            ],

            // -- R485 --
            'caces_r485' => [
                'recyclage' => [
                    'message_if_invalid' => "Pour R485 Recyclage, la seule catégorie autorisée est 2.",
                    'combos' => [
                        ['categories' => ['2'],                  'price' => 350],
                    ],
                ],
                'initial' => [
                    'message_if_invalid' => "Pour R485 Initial, la seule catégorie autorisée est 2.",
                    'combos' => [
                        ['categories' => ['2'],                  'price' => 420],
                    ],
                ],
            ],

            // -- ACES R485 --
            'aces_r485' => [
                'recyclage' => [
                    'message_if_invalid' => "Pour ACES R485 Recyclage, la seule catégorie autorisée est 2.",
                    'combos' => [
                        ['categories' => ['2'],                  'price' => 0],
                    ],
                ],
                'initial' => [
                    'message_if_invalid' => "Pour ACES R485 Initial, la seule catégorie autorisée est 2.",
                    'combos' => [
                        ['categories' => ['2'],                  'price' => 0],
                    ],
                ],
            ],

            // -- R490 --
            'caces_r490' => [
                'recyclage' => [
                    'message_if_invalid' => "Pour R490 Recyclage, c'est uniquement 'Avec télécommande'.",
                    'combos' => [
                        ['categories' => ['Avec télécommande'],  'price' => 720],
                    ],
                ],
                'initial' => [
                    'message_if_invalid' => "Pour R490 Initial, c'est uniquement 'Avec télécommande'.",
                    'combos' => [
                        ['categories' => ['Avec télécommande'],  'price' => 820],
                    ],
                ],
            ],

            // -- ACES R490 --
            'aces_r490' => [
                'recyclage' => [
                    'message_if_invalid' => "Pour ACES R490 Recyclage, c'est uniquement 'Avec télécommande'.",
                    'combos' => [
                        ['categories' => ['Avec télécommande'],  'price' => 0],
                    ],
                ],
                'initial' => [
                    'message_if_invalid' => "Pour ACES R490 Initial, c'est uniquement 'Avec télécommande'.",
                    'combos' => [
                        ['categories' => ['Avec télécommande'],  'price' => 0],
                    ],
                ],
            ],

            // -- Habilitation Electrique Non Electricien --
            'habilitation_electrique_non_electricien' => [
                'initial' => [
                    'message_if_invalid' => "Choisissez H0(V) B0 ou BE / BE-BS / HE H0V Manœuvre (initial).",
                    'combos' => [
                        ['categories' => ['H0(V) B0'],                         'price' => 220],
                        ['categories' => ['BE', 'BE-BS', 'HE H0V Manœuvre'],   'price' => 310],
                    ],
                ],
                'recyclage' => [
                    'message_if_invalid' => "Choisissez H0(V) B0 ou BE / BE-BS / HE H0V Manœuvre (recyclage).",
                    'combos' => [
                        ['categories' => ['H0(V) B0'],                         'price' => 220],
                        ['categories' => ['BE', 'BE-BS', 'HE H0V Manœuvre'],   'price' => 270],
                    ],
                ],
            ],

            // -- Habilitation Electrique Electricien --
            'habilitation_electrique_electricien' => [
                'initial' => [
                    'message_if_invalid' => "Choisissez BT (B1V-B2V-BR-BC-H0), BT avec BP photovoltaïque (pour Intra), HT (H1-H2-HC), ou les deux (initial).",
                    'combos' => [
                        [
                            'categories' => [
                                'BT (B1V-B2V-BR-BC-H0)'
                            ],
                            'price' => 430
                        ],
                        [
                            'categories' => [
                                'BT (B1V-B2V-BR-BC-H0)', 
                                'BP photovoltaïque (INTRA seulement)'
                            ],
                            'price' => 460
                        ],
                        [
                            'categories' => [
                                'HT (H1-H2-HC)'
                            ],
                            'price' => 450
                        ],
                        [
                            'categories' => [
                                'BT (B1V-B2V-BR-BC-H0)',
                                'HT (H1-H2-HC)'
                            ],
                            'price' => 690
                        ],
                    ],
                ],
                'recyclage' => [
                    'message_if_invalid' => "Choisissez BT (B1V-B2V-BR-BC-H0), BT avec BP photovoltaïque (pour Intra), HT (H1-H2-HC), ou les deux (recyclage).",
                    'combos' => [
                        [
                            'categories' => [
                                'BT (B1V-B2V-BR-BC-H0)'
                            ],
                            'price' => 300
                        ],
                        [
                            'categories' => [
                                'BT (B1V-B2V-BR-BC-H0)', 
                                'BP photovoltaïque (INTRA seulement)'
                            ],
                            'price' => 320
                        ],
                        [
                            'categories' => [
                                'HT (H1-H2-HC)'
                            ],
                            'price' => 450
                        ],
                        [
                            'categories' => [
                                'BT (B1V-B2V-BR-BC-H0)',
                                'HT (H1-H2-HC)'
                            ],
                            'price' => 520
                        ],
                    ],
                ],
            ],
        ];
    }
}

/**
 * Ajouter une page d'administration dans le menu WordPress
 */
add_action('admin_menu', 'grille_tarifaire_admin_menu');
function grille_tarifaire_admin_menu() {
    add_submenu_page(
        'woocommerce',
        'Gestion Grille Tarifaire',
        'Grille Tarifaire',
        'manage_woocommerce',
        'grille-tarifaire-admin',
        'grille_tarifaire_admin_page'
    );
}

/**
 * Traitement des données du formulaire
 */
add_action('admin_init', 'grille_tarifaire_save_data');
function grille_tarifaire_save_data() {
    if (isset($_POST['save_grille_tarifaire']) && wp_verify_nonce($_POST['grille_tarifaire_nonce'], 'save_grille_tarifaire')) {
        $updated_table = [];
        $price_table = get_full_price_table();
        
        foreach ($price_table as $formation_key => $formation_data) {
            $updated_table[$formation_key] = $formation_data;
            
            foreach ($formation_data as $niveau_key => $niveau_data) {
                if (isset($niveau_data['combos'])) {
                    foreach ($niveau_data['combos'] as $combo_index => $combo) {
                        $field_name = $formation_key . '_' . $niveau_key . '_' . $combo_index;
                        if (isset($_POST[$field_name])) {
                            $new_price = floatval($_POST[$field_name]);
                            $updated_table[$formation_key][$niveau_key]['combos'][$combo_index]['price'] = $new_price;
                        }
                    }
                }
            }
        }
        
        // Sauvegarder dans la base de donnees WordPress
        update_option('grille_tarifaire_custom', $updated_table);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Grille tarifaire mise a jour avec succes!</p></div>';
        });
    }
}

/**
 * Recuperer la grille tarifaire (personnalisee ou par defaut)
 */
function get_grille_tarifaire_data() {
    $custom_table = get_option('grille_tarifaire_custom', false);
    return $custom_table ? $custom_table : get_full_price_table();
}

/**
 * Page d'administration principale
 */
function grille_tarifaire_admin_page() {
    $price_table = get_grille_tarifaire_data();
    
    // Definir les noms conviviaux pour les formations
    $formation_names = [
        'caces_r489' => 'CACES R489',
        'aces_r489' => 'ACES R489',
        'caces_r486' => 'CACES R486',
        'aces_r486' => 'ACES R486',
        'caces_r482' => 'CACES R482',
        'aces_r482' => 'ACES R482',
        'caces_r485' => 'CACES R485',
        'aces_r485' => 'ACES R485',
        'caces_r490' => 'CACES R490',
        'aces_r490' => 'ACES R490',
        'habilitation_electrique_non_electricien' => 'Habilitation Electrique Non Electricien',
        'habilitation_electrique_electricien' => 'Habilitation Electrique Electricien'
    ];
    
    ?>
    <div class="wrap">
        <h1>Gestion de la Grille Tarifaire</h1>
        <p>Modifiez les prix des formations CACES, ACES et Habilitations Electriques.</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('save_grille_tarifaire', 'grille_tarifaire_nonce'); ?>
            
            <div class="grille-tarifaire-container">
                <?php foreach ($price_table as $formation_key => $formation_data): ?>
                    <div class="formation-section">
                        <h2><?php echo esc_html($formation_names[$formation_key] ?? $formation_key); ?></h2>
                        
                        <?php foreach ($formation_data as $niveau_key => $niveau_data): ?>
                            <?php if (isset($niveau_data['combos'])): ?>
                                <div class="niveau-section">
                                    <h3><?php echo ucfirst($niveau_key); ?></h3>
                                    
                                    <?php if (isset($niveau_data['message_if_invalid'])): ?>
                                        <div class="message-info">
                                            <strong>Regles:</strong> <?php echo esc_html($niveau_data['message_if_invalid']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <table class="wp-list-table widefat fixed striped">
                                        <thead>
                                            <tr>
                                                <th>Categories</th>
                                                <th>Prix (EUR)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($niveau_data['combos'] as $combo_index => $combo): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo esc_html(implode(', ', $combo['categories'])); ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $field_name = $formation_key . '_' . $niveau_key . '_' . $combo_index;
                                                        $current_price = $combo['price'];
                                                        ?>
                                                        <input 
                                                            type="number" 
                                                            name="<?php echo esc_attr($field_name); ?>"
                                                            value="<?php echo esc_attr($current_price); ?>"
                                                            step="0.01"
                                                            min="0"
                                                            class="small-text price-input"
                                                        />
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="submit-section">
                <?php submit_button('Sauvegarder la grille tarifaire', 'primary', 'save_grille_tarifaire'); ?>
                <button type="button" class="button" onclick="resetToDefaults()">Restaurer les valeurs par defaut</button>
            </div>
        </form>
        
        <!-- Section d'export/import -->
        <div class="export-import-section">
            <h2>Export / Import</h2>
            <div class="export-import-buttons">
                <button type="button" class="button" onclick="exportGrille()">Exporter la grille</button>
                <button type="button" class="button" onclick="document.getElementById('import-file').click()">Importer une grille</button>
                <input type="file" id="import-file" style="display: none;" accept=".json" onchange="importGrille(this)">
            </div>
        </div>
    </div>
    
    <style>
    .grille-tarifaire-container {
        margin: 20px 0;
    }
    
    .formation-section {
        margin-bottom: 40px;
        border: 1px solid #ddd;
        padding: 20px;
        background: #fff;
        border-radius: 5px;
    }
    
    .formation-section h2 {
        margin-top: 0;
        color: #23282d;
        border-bottom: 2px solid #0073aa;
        padding-bottom: 10px;
    }
    
    .niveau-section {
        margin-bottom: 30px;
    }
    
    .niveau-section h3 {
        color: #0073aa;
        margin-bottom: 10px;
    }
    
    .message-info {
        background: #f0f8ff;
        border-left: 4px solid #0073aa;
        padding: 10px;
        margin-bottom: 15px;
        font-size: 14px;
    }
    
    .price-input {
        width: 80px;
    }
    
    .submit-section {
        margin: 30px 0;
        padding: 20px;
        background: #f9f9f9;
        border-radius: 5px;
    }
    
    .export-import-section {
        margin-top: 40px;
        padding: 20px;
        background: #f9f9f9;
        border-radius: 5px;
    }
    
    .export-import-buttons {
        margin-top: 10px;
    }
    
    .export-import-buttons button {
        margin-right: 10px;
    }
    </style>
    
    <script>
    function resetToDefaults() {
        if (confirm('Etes-vous sur de vouloir restaurer les valeurs par defaut ? Toutes vos modifications seront perdues.')) {
            // Envoyer une requete AJAX pour supprimer les donnees personnalisees
            jQuery.post(ajaxurl, {
                action: 'reset_grille_tarifaire',
                nonce: '<?php echo wp_create_nonce('reset_grille_tarifaire'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Erreur lors de la restauration.');
                }
            });
        }
    }
    
    function exportGrille() {
        // Recuperer toutes les donnees du formulaire
        var formData = new FormData(document.querySelector('form'));
        var exportData = {};
        
        for (var pair of formData.entries()) {
            if (pair[0] !== 'grille_tarifaire_nonce' && pair[0] !== 'save_grille_tarifaire') {
                exportData[pair[0]] = pair[1];
            }
        }
        
        // Creer et telecharger le fichier JSON
        var dataStr = JSON.stringify(exportData, null, 2);
        var dataBlob = new Blob([dataStr], {type: 'application/json'});
        var url = URL.createObjectURL(dataBlob);
        var link = document.createElement('a');
        link.href = url;
        link.download = 'grille-tarifaire-' + new Date().toISOString().split('T')[0] + '.json';
        link.click();
    }
    
    function importGrille(input) {
        var file = input.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                try {
                    var importData = JSON.parse(e.target.result);
                    
                    // Appliquer les donnees importees aux champs du formulaire
                    for (var fieldName in importData) {
                        var field = document.querySelector('[name="' + fieldName + '"]');
                        if (field) {
                            field.value = importData[fieldName];
                        }
                    }
                    
                    alert('Donnees importees avec succes. N\'oubliez pas de sauvegarder.');
                } catch (error) {
                    alert('Erreur lors de l\'import du fichier. Verifiez le format JSON.');
                }
            };
            reader.readAsText(file);
        }
    }
    </script>
    <?php
}

/**
 * Action AJAX pour restaurer les valeurs par defaut
 */
add_action('wp_ajax_reset_grille_tarifaire', 'reset_grille_tarifaire_ajax');
function reset_grille_tarifaire_ajax() {
    if (!wp_verify_nonce($_POST['nonce'], 'reset_grille_tarifaire') || !current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => 'Permissions insuffisantes.']);
    }
    
    delete_option('grille_tarifaire_custom');
    wp_send_json_success(['message' => 'Grille tarifaire restauree aux valeurs par defaut.']);
}

/**
 * Fonction pour recuperer la grille tarifaire personnalisee (a utiliser dans le code existant)
 */
function get_full_price_table_custom() {
    // Fonction pour compatibilité avec l'ancien code
    return get_full_price_table();
}

// Remplace complètement l'ancienne fonction get_full_price_table
if (!function_exists('get_full_price_table')) {
function get_full_price_table() {
    $custom_table = get_option('grille_tarifaire_custom', false);
    return $custom_table ? $custom_table : get_default_price_table();
}
}

/**
 * Ajouter des styles CSS pour l'administration
 */
add_action('admin_head', 'grille_tarifaire_admin_styles');
function grille_tarifaire_admin_styles() {
    $screen = get_current_screen();
    if ($screen && strpos($screen->id, 'grille-tarifaire-admin') !== false) {
        ?>
        <style>
        .wrap h1 {
            color: #23282d;
        }
        
        .formation-section:nth-child(odd) {
            background: #fafafa;
        }
        
        .price-input:focus {
            border-color: #0073aa;
            box-shadow: 0 0 0 1px #0073aa;
        }
        
        .wp-list-table th {
            background: #f1f1f1;
        }
        
        .wp-list-table tr:hover {
            background: #f9f9f9;
        }
        </style>
        <?php
    }
}

/**
 * Tente de détecter un code caces ou habilitation à partir du nom du produit.
 * Retourne '' si non détecté.
 */
if (!function_exists('get_caces_code_from_product_name')) {
function get_caces_code_from_product_name( $product_name ) {
    $name_lower = mb_strtolower( $product_name, 'UTF-8' );

    // Détection des CACES
    if (stripos($name_lower, 'caces r489') !== false) return 'caces_r489';
    if (stripos($name_lower, 'caces r486') !== false) return 'caces_r486';
    if (stripos($name_lower, 'caces r482') !== false) return 'caces_r482';
    if (stripos($name_lower, 'caces r485') !== false) return 'caces_r485';
    if (stripos($name_lower, 'caces r490') !== false) return 'caces_r490';

    // Détection des ACES
    if (stripos($name_lower, 'aces r489') !== false) return 'aces_r489';
    if (stripos($name_lower, 'aces r486') !== false) return 'aces_r486';
    if (stripos($name_lower, 'aces r482') !== false) return 'aces_r482';
    if (stripos($name_lower, 'aces r485') !== false) return 'aces_r485';
    if (stripos($name_lower, 'aces r490') !== false) return 'aces_r490';

    // Ajout des habilitations électriques :
    if ( stripos($name_lower, 'habilitation electrique non electricien') !== false ) {
        return 'habilitation_electrique_non_electricien';
    }
    if ( stripos($name_lower, 'habilitation electrique electricien') !== false ) {
        return 'habilitation_electrique_electricien';
    }

    return '';
}
}

/**
 * Convertit certaines valeurs d'options en valeurs "simplifiées"
 * (ex : "Groupe A (élévation verticale)" => "A")
 * pour qu'on puisse matcher exactement les combinaisons du tableau.
 */
if (!function_exists('normalize_category_label')) {
function normalize_category_label( $label ) {
    $map = [
        'Groupe A (élévation verticale)'           => 'A',
        'Groupe B (élévation multidirectionnelle)' => 'B',
        'Groupe C (conduite hors production)'      => 'C',
    ];
    return isset($map[$label]) ? $map[$label] : $label;
}
}

/**
 * Ajouter un lien rapide dans la barre d'administration
 */
add_action('admin_bar_menu', 'grille_tarifaire_admin_bar_link', 999);
function grille_tarifaire_admin_bar_link($admin_bar) {
    if (current_user_can('manage_woocommerce')) {
        $admin_bar->add_node([
            'id'    => 'grille-tarifaire-admin',
            'title' => 'Grille Tarifaire',
            'href'  => admin_url('admin.php?page=grille-tarifaire-admin'),
            'meta'  => [
                'title' => 'Gerer la grille tarifaire'
            ]
        ]);
    }
}