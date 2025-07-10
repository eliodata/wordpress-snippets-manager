<?php
/**
 * Snippet ID: 126
 * Name: Grille tarifaire caces produits categories liste produits variation et creation commande
 * Description: 
 * @active true
 */

/**
 * Renvoie un tableau complet de tarifs individuels (pour R482, R485, R486, R489, R490,
 * Habilitation Electrique Non Electricien, Habilitation Electrique Electricien)
 * selon la combinaison de catégories (et le niveau initial/recyclage).
 * 
 * Pour chaque niveau ('initial' ou 'recyclage'), on a deux clés :
 *   - 'message_if_invalid': message d'erreur si la combinaison n'existe pas.
 *   - 'combos': liste de combos valides (categories[] + price).
 */
function get_full_price_table() {
    $default_prices = [

        // -- R489 --
        'caces_r489' => [
            'recyclage' => [
                'message_if_invalid' => "Pour R489 Recyclage, vous devez choisir l'une des combinaisons suivantes : 
                    1A, 1B, 3, 5 (seules ou en combinaisons jusqu'à 3) – voir tarif correspondant.",
                'combos' => [
                    ['categories' => ['1A'],                 'price' => 350],
                    ['categories' => ['1B'],                 'price' => 350],
                    ['categories' => ['3'],                  'price' => 350],
                    ['categories' => ['5'],                  'price' => 350],
                    ['categories' => ['1A','1B'],            'price' => 470],
                    ['categories' => ['1A','3'],             'price' => 470],
                    ['categories' => ['1A','5'],             'price' => 470],
                    ['categories' => ['1B','3'],             'price' => 470],
                    ['categories' => ['1B','5'],             'price' => 470],
                    ['categories' => ['3','5'],              'price' => 470],
                    ['categories' => ['1A','1B','3'],        'price' => 590],
                    ['categories' => ['1A','1B','5'],        'price' => 590],
                    ['categories' => ['1B','3','5'],         'price' => 590],
                    ['categories' => ['1A','3','5'],         'price' => 590],
                ],
            ],
            'initial' => [
                'message_if_invalid' => "Pour R489 Initial, choisissez parmi : 1A, 1B, 3, 5 (simples ou en combinaisons).",
                'combos' => [
                    ['categories' => ['1A'],                 'price' => 420],
                    ['categories' => ['1B'],                 'price' => 420],
                    ['categories' => ['3'],                  'price' => 420],
                    ['categories' => ['5'],                  'price' => 420],
                    ['categories' => ['1A','1B'],            'price' => 580],
                    ['categories' => ['1A','3'],             'price' => 580],
                    ['categories' => ['1A','5'],             'price' => 580],
                    ['categories' => ['1B','3'],             'price' => 580],
                    ['categories' => ['1B','5'],             'price' => 580],
                    ['categories' => ['3','5'],              'price' => 580],
                    ['categories' => ['1A','1B','3'],        'price' => 690],
                    ['categories' => ['1A','1B','5'],        'price' => 690],
                    ['categories' => ['1B','3','5'],         'price' => 690],
                    ['categories' => ['1A','3','5'],         'price' => 690],
                ],
            ],
        ],
		
		        'aces_r489' => [
            'recyclage' => [
                'message_if_invalid' => "Pour R489 Recyclage, vous devez choisir l'une des combinaisons suivantes : 
                    1A, 1B, 3, 5 (seules ou en combinaisons jusqu'à 3) – voir tarif correspondant.",
                'combos' => [
                    ['categories' => ['1A'],                 'price' => 0],
                    ['categories' => ['1B'],                 'price' => 0],
                    ['categories' => ['3'],                  'price' => 0],
                    ['categories' => ['5'],                  'price' => 0],
                    ['categories' => ['1A','1B'],            'price' => 0],
                    ['categories' => ['1A','3'],             'price' => 0],
                    ['categories' => ['1A','5'],             'price' => 0],
                    ['categories' => ['1B','3'],             'price' => 0],
                    ['categories' => ['1B','5'],             'price' => 0],
                    ['categories' => ['3','5'],              'price' => 0],
                    ['categories' => ['1A','1B','3'],        'price' => 0],
                    ['categories' => ['1A','1B','5'],        'price' => 0],
                    ['categories' => ['1B','3','5'],         'price' => 0],
                    ['categories' => ['1A','3','5'],         'price' => 0],
                ],
            ],
            'initial' => [
                'message_if_invalid' => "Pour R489 Initial, choisissez parmi : 1A, 1B, 3, 5 (simples ou en combinaisons).",
                'combos' => [
                    ['categories' => ['1A'],                 'price' => 0],
                    ['categories' => ['1B'],                 'price' => 0],
                    ['categories' => ['3'],                  'price' => 0],
                    ['categories' => ['5'],                  'price' => 0],
                    ['categories' => ['1A','1B'],            'price' => 0],
                    ['categories' => ['1A','3'],             'price' => 0],
                    ['categories' => ['1A','5'],             'price' => 0],
                    ['categories' => ['1B','3'],             'price' => 0],
                    ['categories' => ['1B','5'],             'price' => 0],
                    ['categories' => ['3','5'],              'price' => 0],
                    ['categories' => ['1A','1B','3'],        'price' => 0],
                    ['categories' => ['1A','1B','5'],        'price' => 0],
                    ['categories' => ['1B','3','5'],         'price' => 0],
                    ['categories' => ['1A','3','5'],         'price' => 0],
                ],
            ],
        ],

        // -- R486 --
        'caces_r486' => [
            'recyclage' => [
                'message_if_invalid' => "Pour R486 Recyclage, seules les combos A, B, A+B et C sont autorisées.",
                'combos' => [
                    ['categories' => ['A'],                  'price' => 540],
                    ['categories' => ['B'],                  'price' => 540],
                    ['categories' => ['A','B'],              'price' => 750],
                    ['categories' => ['C'],                  'price' => 560], // CSV => 560
                ],
            ],
            'initial' => [
                'message_if_invalid' => "Pour R486 Initial, seules les combos A, B, A+B et C sont autorisées.",
                'combos' => [
                    ['categories' => ['A'],                  'price' => 720],
                    ['categories' => ['B'],                  'price' => 720],
                    ['categories' => ['A','B'],              'price' => 910],
                    ['categories' => ['C'],                  'price' => 660], // CSV => 660
                ],
            ],
        ],
		
		        'aces_r486' => [
            'recyclage' => [
                'message_if_invalid' => "Pour R486 Recyclage, seules les combos A, B, A+B et C sont autorisées.",
                'combos' => [
                    ['categories' => ['A'],                  'price' => 0],
                    ['categories' => ['B'],                  'price' => 0],
                    ['categories' => ['A','B'],              'price' => 0],
                    ['categories' => ['C'],                  'price' => 0], // CSV => 560
                ],
            ],
            'initial' => [
                'message_if_invalid' => "Pour R486 Initial, seules les combos A, B, A+B et C sont autorisées.",
                'combos' => [
                    ['categories' => ['A'],                  'price' => 0],
                    ['categories' => ['B'],                  'price' => 0],
                    ['categories' => ['A','B'],              'price' => 0],
                    ['categories' => ['C'],                  'price' => 0], // CSV => 660
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
		
		
		        'aces_r482' => [
            'initial' => [
                'message_if_invalid' => "Pour R482 Initial, seules les combos A, B1, C1, F, G ou leurs combinaisons sont valables.",
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
                'message_if_invalid' => "Pour R482 Recyclage, choisissez parmi A, B1, C1, F, G ou leurs combinaisons listées.",
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
                'message_if_invalid' => "Pour R485 Initial, la seule catégorie autorisée est 2 (tarif 0 en exemple).",
                'combos' => [
                    ['categories' => ['2'],                  'price' => 420],
                ],
            ],
        ],
		
		
		        'aces_r485' => [
            'recyclage' => [
                'message_if_invalid' => "Pour R485 Recyclage, la seule catégorie autorisée est 2.",
                'combos' => [
                    ['categories' => ['2'],                  'price' => 0],
                ],
            ],
            'initial' => [
                'message_if_invalid' => "Pour R485 Initial, la seule catégorie autorisée est 2 (tarif 0 en exemple).",
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
		
		
		        'aces_r490' => [
            'recyclage' => [
                'message_if_invalid' => "Pour R490 Recyclage, c'est uniquement 'Avec télécommande'.",
                'combos' => [
                    ['categories' => ['Avec télécommande'],  'price' => 0],
                ],
            ],
            'initial' => [
                'message_if_invalid' => "Pour R490 Initial, c'est uniquement 'Avec télécommande'.",
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
            [ // Nouvelle option BT seule
                'categories' => [
                    'BT (B1V-B2V-BR-BC-H0)'
                ],
                'price' => 430 // Prix exemple, ajustez si besoin
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
                'price' => 690 // Note: Ce combo BT+HT n'inclut pas BP par défaut
            ],
            // Si vous voulez un combo BT+BP+HT pour l'intra, il faudrait l'ajouter ici explicitement
            // Exemple :
            // [
            // 'categories' => [
            // 'BT (B1V-B2V-BR-BC-H0)',
            // 'BP photovoltaïque',
            // 'HT (H1-H2-HC)'
            // ],
            // 'price' => 720 // Prix exemple
            // ],
        ],
    ],
    'recyclage' => [
        'message_if_invalid' => "Choisissez BT (B1V-B2V-BR-BC-H0), BT avec BP photovoltaïque (pour Intra), HT (H1-H2-HC), ou les deux (recyclage).",
        'combos' => [
            [ // Nouvelle option BT seule
                'categories' => [
                    'BT (B1V-B2V-BR-BC-H0)'
                ],
                'price' => 300 // Prix exemple, ajustez si besoin
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
                'price' => 520 // Note: Ce combo BT+HT n'inclut pas BP par défaut
            ],
             // Si vous voulez un combo BT+BP+HT pour l'intra, il faudrait l'ajouter ici explicitement
        ],
    ],
],

    ];
    
    // Appliquer le hook pour les prix personnalisés
    return apply_filters('grille_tarifaire_get_prices', $default_prices);
}

/**
 * Tente de détecter un code caces ou habilitation à partir du nom du produit.
 * Retourne '' si non détecté.
 */
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


/**
 * Convertit certaines valeurs d'options en valeurs "simplifiées"
 * (ex : "Groupe A (élévation verticale)" => "A")
 * pour qu'on puisse matcher exactement les combinaisons du tableau.
 */
function normalize_category_label( $label ) {
    $map = [
        'Groupe A (élévation verticale)'           => 'A',
        'Groupe B (élévation multidirectionnelle)' => 'B',
        'Groupe C (conduite hors production)'      => 'C',
    ];
    return isset($map[$label]) ? $map[$label] : $label;
}

// ========================================
// INTERFACE D'ADMINISTRATION
// ========================================

/**
 * Ajouter le menu d'administration
 */
add_action('admin_menu', 'grille_tarifaire_admin_menu');

function grille_tarifaire_admin_menu() {
    add_submenu_page(
        'woocommerce',
        'Grille Tarifaire et UT',
        'Grille Tarifaire et UT',
        'manage_options',
        'grille-tarifaire',
        'grille_tarifaire_admin_page'
    );
}

/**
 * Page d'administration
 */
function grille_tarifaire_admin_page() {
    // Traitement des formulaires
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'save_prices' && wp_verify_nonce($_POST['_wpnonce'], 'save_prices')) {
            grille_tarifaire_save_prices();
            echo '<div class="notice notice-success"><p>Tarifs sauvegardés avec succès !</p></div>';
        } elseif ($_POST['action'] === 'reset_prices' && wp_verify_nonce($_POST['_wpnonce'], 'reset_prices')) {
            grille_tarifaire_reset_prices();
            echo '<div class="notice notice-success"><p>Tarifs réinitialisés aux valeurs par défaut !</p></div>';
        } elseif ($_POST['action'] === 'save_ut' && wp_verify_nonce($_POST['_wpnonce'], 'save_ut')) {
            grille_tarifaire_save_ut();
            echo '<div class="notice notice-success"><p>Unités de Temps sauvegardées avec succès !</p></div>';
        } elseif ($_POST['action'] === 'reset_ut' && wp_verify_nonce($_POST['_wpnonce'], 'reset_ut')) {
            grille_tarifaire_reset_ut();
            echo '<div class="notice notice-success"><p>Unités de Temps réinitialisées aux valeurs par défaut !</p></div>';
        }
    }

    $price_table = get_full_price_table();
    // Appliquer les prix personnalisés pour l'affichage admin
    $custom_prices = get_option('grille_tarifaire_custom_prices', []);
    if (!empty($custom_prices)) {
        $price_table = get_full_price_table_with_custom_prices_from_default($price_table);
    }
    ?>
    <div class="wrap">
        <h1>Gestion de la Grille Tarifaire et UT</h1>
        
        <div class="grille-admin-container">
            <div class="grille-admin-tabs">
                <button class="grille-tab-button active" onclick="showTab('r489')">R489</button>
                <button class="grille-tab-button" onclick="showTab('r482')">R482</button>
                <button class="grille-tab-button" onclick="showTab('r485')">R485</button>
                <button class="grille-tab-button" onclick="showTab('r486')">R486</button>
                <button class="grille-tab-button" onclick="showTab('r490')">R490</button>
                <button class="grille-tab-button" onclick="showTab('habilitation')">Habilitations</button>
                <button class="grille-tab-button" onclick="showTab('ut')">Unités de Temps (UT)</button>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('save_prices'); ?>
                <input type="hidden" name="action" value="save_prices">

                <!-- Onglet R489 -->
                <div id="tab-r489" class="grille-tab-content active">
                    <h2>CACES R489 - Chariots Élévateurs</h2>
                    <?php grille_tarifaire_render_formation_section('caces_r489', $price_table); ?>
                </div>

                <!-- Onglet R482 -->
                <div id="tab-r482" class="grille-tab-content">
                    <h2>CACES R482 - Engins de Chantier</h2>
                    <?php grille_tarifaire_render_formation_section('caces_r482', $price_table); ?>
                </div>

                <!-- Onglet R485 -->
                <div id="tab-r485" class="grille-tab-content">
                    <h2>CACES R485 - Chariots de Manutention Automoteurs</h2>
                    <?php grille_tarifaire_render_formation_section('caces_r485', $price_table); ?>
                </div>

                <!-- Onglet R486 -->
                <div id="tab-r486" class="grille-tab-content">
                    <h2>CACES R486 - Plates-formes Élévatrices</h2>
                    <?php grille_tarifaire_render_formation_section('caces_r486', $price_table); ?>
                </div>

                <!-- Onglet R490 -->
                <div id="tab-r490" class="grille-tab-content">
                    <h2>CACES R490 - Grues Auxiliaires</h2>
                    <?php grille_tarifaire_render_formation_section('caces_r490', $price_table); ?>
                </div>

                <!-- Onglet Habilitations -->
                <div id="tab-habilitation" class="grille-tab-content">
                    <h2>Habilitations Électriques</h2>
                    <?php 
                    grille_tarifaire_render_formation_section('habilitation_electrique_non_electricien', $price_table);
                    grille_tarifaire_render_formation_section('habilitation_electrique_electricien', $price_table);
                    ?>
                </div>

                <div class="grille-admin-actions">
                    <button type="submit" class="button button-primary button-large">Sauvegarder les modifications</button>
                </div>
            </form>

            <!-- Onglet UT -->
            <div id="tab-ut" class="grille-tab-content">
                <h2>Gestion des Unités de Temps (UT)</h2>
                <p><strong>Information :</strong> Les UT (Unités de Temps) définissent la durée des épreuves pratiques et théoriques pour chaque catégorie de formation. Ces données sont utilisées pour la génération des documents de sortie.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('save_ut'); ?>
                    <input type="hidden" name="action" value="save_ut">
                    <?php grille_tarifaire_render_ut_section(); ?>
                    
                    <div class="grille-admin-actions">
                        <button type="submit" class="button button-primary button-large">Sauvegarder les UT</button>
                    </div>
                </form>
                
                <form method="post" action="" style="margin-top: 20px;">
                    <?php wp_nonce_field('reset_ut'); ?>
                    <input type="hidden" name="action" value="reset_ut">
                    <button type="submit" class="button button-secondary" onclick="return confirm('Êtes-vous sûr de vouloir réinitialiser toutes les UT aux valeurs par défaut ?')">Réinitialiser les UT aux valeurs par défaut</button>
                </form>
            </div>

            <form method="post" action="" style="margin-top: 20px;">
                <?php wp_nonce_field('reset_prices'); ?>
                <input type="hidden" name="action" value="reset_prices">
                <button type="submit" class="button button-secondary" onclick="return confirm('Êtes-vous sûr de vouloir réinitialiser tous les tarifs aux valeurs par défaut ?')">Réinitialiser aux valeurs par défaut</button>
            </form>
        </div>
    </div>

    <style>
    .grille-admin-container {
        max-width: 1200px;
        margin: 20px 0;
    }
    
    .grille-admin-tabs {
        border-bottom: 1px solid #ccc;
        margin-bottom: 20px;
    }
    
    .grille-tab-button {
        background: #f1f1f1;
        border: 1px solid #ccc;
        border-bottom: none;
        padding: 10px 20px;
        cursor: pointer;
        margin-right: 5px;
        border-radius: 4px 4px 0 0;
    }
    
    .grille-tab-button.active {
        background: #fff;
        border-bottom: 1px solid #fff;
        margin-bottom: -1px;
    }
    
    .grille-tab-content {
        display: none;
        padding: 20px;
        border: 1px solid #ccc;
        border-top: none;
        background: #fff;
    }
    
    .grille-tab-content.active {
        display: block;
    }
    
    .grille-formation-section {
        margin-bottom: 30px;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: #f9f9f9;
    }
    
    .grille-level-section {
        margin-bottom: 20px;
    }
    
    .grille-level-title {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 10px;
        color: #0073aa;
    }
    
    .grille-combos-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    
    .grille-combos-table th,
    .grille-combos-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    
    .grille-combos-table th {
        background: #f1f1f1;
        font-weight: bold;
    }
    
    .grille-price-input {
        width: 80px;
        text-align: right;
    }
    
    .grille-message-textarea {
        width: 100%;
        height: 60px;
        margin-top: 10px;
    }
    
    .grille-admin-actions {
        margin-top: 30px;
        padding: 20px;
        background: #f1f1f1;
        border-radius: 5px;
        text-align: center;
    }
    </style>

    <script>
    function showTab(tabName) {
        // Masquer tous les onglets
        var tabs = document.querySelectorAll('.grille-tab-content');
        tabs.forEach(function(tab) {
            tab.classList.remove('active');
        });
        
        // Désactiver tous les boutons
        var buttons = document.querySelectorAll('.grille-tab-button');
        buttons.forEach(function(button) {
            button.classList.remove('active');
        });
        
        // Activer l'onglet sélectionné
        document.getElementById('tab-' + tabName).classList.add('active');
        event.target.classList.add('active');
    }
    </script>
    <?php
}

/**
 * Rendu d'une section de formation
 */
function grille_tarifaire_render_formation_section($formation_key, $price_table) {
    if (!isset($price_table[$formation_key])) {
        return;
    }
    
    $formation = $price_table[$formation_key];
    $formation_name = grille_tarifaire_get_formation_name($formation_key);
    
    echo '<div class="grille-formation-section">';
    echo '<h3>' . esc_html($formation_name) . '</h3>';
    
    foreach ($formation as $level => $level_data) {
        if (!is_array($level_data) || !isset($level_data['combos'])) {
            continue;
        }
        
        echo '<div class="grille-level-section">';
        echo '<div class="grille-level-title">' . ucfirst($level) . '</div>';
        
        // Tableau des combinaisons
        echo '<table class="grille-combos-table">';
        echo '<thead><tr><th>Catégories</th><th>Prix (€)</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($level_data['combos'] as $index => $combo) {
            $categories_str = implode(', ', $combo['categories']);
            $field_name = "prices[{$formation_key}][{$level}][combos][{$index}][price]";
            
            echo '<tr>';
            echo '<td>' . esc_html($categories_str) . '</td>';
            echo '<td><input type="number" name="' . esc_attr($field_name) . '" value="' . esc_attr($combo['price']) . '" class="grille-price-input" step="0.01" min="0"> €</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        
        // Message d'erreur
        $message_field_name = "prices[{$formation_key}][{$level}][message_if_invalid]";
        echo '<label><strong>Message si combinaison invalide :</strong></label>';
        echo '<textarea name="' . esc_attr($message_field_name) . '" class="grille-message-textarea">' . esc_textarea($level_data['message_if_invalid']) . '</textarea>';
        
        echo '</div>';
    }
    
    echo '</div>';
}

/**
 * Obtenir les UT par défaut pour toutes les formations
 */
function get_default_ut_table() {
    return [
        'caces_r489' => [
            'categories' => [
                '1A' => ['ut_pratique' => 0.5, 'ut_theorique' => 1],
                '1B' => ['ut_pratique' => 0.75, 'ut_theorique' => 1],
                '2A' => ['ut_pratique' => 0.5, 'ut_theorique' => 1],
                '2B' => ['ut_pratique' => 0.5, 'ut_theorique' => 1],
                '3' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                '4' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                '5' => ['ut_pratique' => 0.75, 'ut_theorique' => 1],
                '6' => ['ut_pratique' => 0.75, 'ut_theorique' => 1],
                '7' => ['ut_pratique' => 0.75, 'ut_theorique' => 1]
            ]
        ],
        'caces_r482' => [
            'categories' => [
                'A' => ['ut_pratique' => 1.5, 'ut_theorique' => 1],
                'B1' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'B2' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'B3' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'C1' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'C2' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'C3' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'D' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'E' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'F' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'G' => ['ut_pratique' => 1.2, 'ut_theorique' => 1]
            ]
        ],
        'caces_r485' => [
            'categories' => [
                'A' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'B' => ['ut_pratique' => 0.75, 'ut_theorique' => 1],
                'C' => ['ut_pratique' => 1, 'ut_theorique' => 1]
            ]
        ],
        'caces_r486' => [
            'categories' => [
                'A' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'B' => ['ut_pratique' => 1, 'ut_theorique' => 1]
            ]
        ],
        'caces_r490' => [
            'categories' => [
                'Avec télécommande' => ['ut_pratique' => 1.5, 'ut_theorique' => 1]
            ]
        ],
        'habilitation_electrique_non_electricien' => [
            'categories' => [
                'B0' => ['ut_pratique' => 0.5, 'ut_theorique' => 1],
                'H0' => ['ut_pratique' => 0.5, 'ut_theorique' => 1],
                'BS' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'BE' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'HE' => ['ut_pratique' => 1, 'ut_theorique' => 1]
            ]
        ],
        'habilitation_electrique_electricien' => [
            'categories' => [
                'B1' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'B2' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'BR' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'BC' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'H1' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'H2' => ['ut_pratique' => 1, 'ut_theorique' => 1],
                'HC' => ['ut_pratique' => 1, 'ut_theorique' => 1]
            ]
        ]
    ];
}

/**
 * Obtenir les UT avec les personnalisations appliquées
 */
function get_ut_table_with_custom() {
    $default_ut = get_default_ut_table();
    $custom_ut = get_option('grille_tarifaire_custom_ut', []);
    
    if (empty($custom_ut)) {
        return $default_ut;
    }
    
    // Fusionner les UT personnalisées avec les UT par défaut
    foreach ($custom_ut as $formation_key => $formation_data) {
        if (!isset($default_ut[$formation_key])) {
            continue;
        }
        
        if (isset($formation_data['categories']) && is_array($formation_data['categories'])) {
            foreach ($formation_data['categories'] as $category => $ut_data) {
                if (isset($default_ut[$formation_key]['categories'][$category])) {
                    if (isset($ut_data['ut_pratique'])) {
                        $default_ut[$formation_key]['categories'][$category]['ut_pratique'] = floatval($ut_data['ut_pratique']);
                    }
                    if (isset($ut_data['ut_theorique'])) {
                        $default_ut[$formation_key]['categories'][$category]['ut_theorique'] = floatval($ut_data['ut_theorique']);
                    }
                }
            }
        }
    }
    
    return $default_ut;
}

/**
 * Obtenir le nom d'affichage d'une formation
 */
function grille_tarifaire_get_formation_name($formation_key) {
    $names = [
        'caces_r489' => 'CACES R489 - Chariots Élévateurs',
        'caces_r482' => 'CACES R482 - Engins de Chantier',
        'caces_r485' => 'CACES R485 - Chariots de Manutention Automoteurs',
        'caces_r486' => 'CACES R486 - Plates-formes Élévatrices',
        'caces_r490' => 'CACES R490 - Grues Auxiliaires',
        'habilitation_electrique_non_electricien' => 'Habilitation Électrique Non Électricien',
        'habilitation_electrique_electricien' => 'Habilitation Électrique Électricien',
    ];
    
    return isset($names[$formation_key]) ? $names[$formation_key] : $formation_key;
}

/**
 * Sauvegarder les prix modifiés
 */
function grille_tarifaire_save_prices() {
    if (!isset($_POST['prices']) || !is_array($_POST['prices'])) {
        return;
    }
    
    // Sauvegarder dans les options WordPress
    update_option('grille_tarifaire_custom_prices', $_POST['prices']);
}

/**
 * Réinitialiser les prix aux valeurs par défaut
 */
function grille_tarifaire_reset_prices() {
    delete_option('grille_tarifaire_custom_prices');
}

/**
 * Rendu de la section UT
 */
function grille_tarifaire_render_ut_section() {
    $ut_table = get_ut_table_with_custom();
    
    foreach ($ut_table as $formation_key => $formation_data) {
        $formation_name = grille_tarifaire_get_formation_name($formation_key);
        
        echo '<div class="grille-formation-section">';
        echo '<h3>' . esc_html($formation_name) . '</h3>';
        
        if (isset($formation_data['categories']) && is_array($formation_data['categories'])) {
            echo '<table class="grille-combos-table">';
            echo '<thead><tr><th>Catégorie</th><th>UT Pratique</th><th>UT Théorique</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($formation_data['categories'] as $category => $ut_data) {
                $ut_pratique_field = "ut[{$formation_key}][categories][{$category}][ut_pratique]";
                $ut_theorique_field = "ut[{$formation_key}][categories][{$category}][ut_theorique]";
                
                echo '<tr>';
                echo '<td><strong>' . esc_html($category) . '</strong></td>';
                echo '<td><input type="number" name="' . esc_attr($ut_pratique_field) . '" value="' . esc_attr($ut_data['ut_pratique']) . '" class="grille-price-input" step="0.25" min="0"> UT</td>';
                echo '<td><input type="number" name="' . esc_attr($ut_theorique_field) . '" value="' . esc_attr($ut_data['ut_theorique']) . '" class="grille-price-input" step="0.25" min="0"> UT</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        }
        
        echo '</div>';
    }
}

/**
 * Sauvegarder les UT modifiées
 */
function grille_tarifaire_save_ut() {
    if (!isset($_POST['ut']) || !is_array($_POST['ut'])) {
        return;
    }
    
    // Sauvegarder dans les options WordPress
    update_option('grille_tarifaire_custom_ut', $_POST['ut']);
}

/**
 * Réinitialiser les UT aux valeurs par défaut
 */
function grille_tarifaire_reset_ut() {
    delete_option('grille_tarifaire_custom_ut');
}

/**
 * Obtenir les UT pour une formation et catégorie spécifique
 * 
 * @param string $formation_key Clé de la formation (ex: 'caces_r489')
 * @param string $category Catégorie (ex: '1A', 'B', etc.)
 * @return array|null Array avec 'ut_pratique' et 'ut_theorique' ou null si non trouvé
 */
function get_ut_for_category($formation_key, $category) {
    $ut_table = get_ut_table_with_custom();
    
    if (isset($ut_table[$formation_key]['categories'][$category])) {
        return $ut_table[$formation_key]['categories'][$category];
    }
    
    return null;
}

/**
 * Obtenir toutes les UT pour une formation
 * 
 * @param string $formation_key Clé de la formation (ex: 'caces_r489')
 * @return array|null Array des catégories avec leurs UT ou null si non trouvé
 */
function get_ut_for_formation($formation_key) {
    $ut_table = get_ut_table_with_custom();
    
    if (isset($ut_table[$formation_key]['categories'])) {
        return $ut_table[$formation_key]['categories'];
    }
    
    return null;
}



// Hook pour utiliser les prix personnalisés dans le frontend
add_filter('grille_tarifaire_get_prices', 'grille_tarifaire_apply_custom_prices');

function grille_tarifaire_apply_custom_prices($default_prices) {
    return get_full_price_table_with_custom_prices_from_default($default_prices);
}

function get_full_price_table_with_custom_prices_from_default($default_prices) {
    $custom_prices = get_option('grille_tarifaire_custom_prices', []);
    
    if (empty($custom_prices)) {
        return $default_prices;
    }
    
    // Fusionner les prix personnalisés avec les prix par défaut
    foreach ($custom_prices as $formation_key => $formation_data) {
        if (!isset($default_prices[$formation_key])) {
            continue;
        }
        
        foreach ($formation_data as $level => $level_data) {
            if (!isset($default_prices[$formation_key][$level])) {
                continue;
            }
            
            // Mettre à jour le message d'erreur
            if (isset($level_data['message_if_invalid'])) {
                $default_prices[$formation_key][$level]['message_if_invalid'] = $level_data['message_if_invalid'];
            }
            
            // Mettre à jour les prix des combinaisons
            if (isset($level_data['combos']) && is_array($level_data['combos'])) {
                foreach ($level_data['combos'] as $combo_index => $combo_data) {
                    if (isset($default_prices[$formation_key][$level]['combos'][$combo_index]) && isset($combo_data['price'])) {
                        $default_prices[$formation_key][$level]['combos'][$combo_index]['price'] = floatval($combo_data['price']);
                    }
                }
            }
        }
    }
    
    return $default_prices;
}