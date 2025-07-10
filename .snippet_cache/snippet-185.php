<?php
/**
 * Snippet ID: 185
 * Name: afficher meta cpt salles fournisseur
 * Description: 
 * @active false
 */


// Ajouter une meta box pour afficher les métadonnées
add_action('add_meta_boxes', 'add_post_metadata_metabox');

function add_post_metadata_metabox() {
    // Pour un post spécifique (ID 279738)
    global $post;
    if ($post && $post->ID == 279738) {
        add_meta_box(
            'post-metadata-display',
            'Métadonnées du Post',
            'display_post_metadata_callback',
            null, // Tous les types de posts
            'normal',
            'default'
        );
    }
    
    // OU pour tous les posts (décommentez la ligne suivante et commentez le bloc ci-dessus)
    /*
    add_meta_box(
        'post-metadata-display',
        'Métadonnées du Post',
        'display_post_metadata_callback',
        null, // Tous les types de posts
        'normal',
        'default'
    );
    */
}

function display_post_metadata_callback($post) {
    // Récupérer toutes les métadonnées
    $metadata = get_post_meta($post->ID);
    
    if (empty($metadata)) {
        echo '<p>Aucune métadonnée trouvée pour ce post.</p>';
        return;
    }
    
    echo '<style>
        .metadata-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .metadata-table th,
        .metadata-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        .metadata-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .metadata-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .metadata-key {
            font-family: monospace;
            background-color: #f0f0f0;
            padding: 2px 4px;
            border-radius: 3px;
        }
        .metadata-value {
            max-width: 400px;
            word-wrap: break-word;
        }
        .metadata-serialized {
            background-color: #fff3cd;
            padding: 5px;
            border-radius: 3px;
            font-size: 12px;
        }
    </style>';
    
    echo '<div>';
    echo '<p><strong>Post ID:</strong> ' . $post->ID . '</p>';
    echo '<p><strong>Nombre de métadonnées:</strong> ' . count($metadata) . '</p>';
    
    echo '<table class="metadata-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Clé (Meta Key)</th>';
    echo '<th>Valeur (Meta Value)</th>';
    echo '<th>Type</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($metadata as $key => $values) {
        // Ignorer les clés internes WordPress si souhaité
        // if (substr($key, 0, 1) === '_') continue;
        
        foreach ($values as $value) {
            echo '<tr>';
            echo '<td class="metadata-key">' . esc_html($key) . '</td>';
            echo '<td class="metadata-value">';
            
            // Vérifier si la valeur est sérialisée
            if (is_serialized($value)) {
                $unserialized = maybe_unserialize($value);
                echo '<div class="metadata-serialized">';
                echo '<strong>Données sérialisées:</strong><br>';
                echo '<pre>' . esc_html(print_r($unserialized, true)) . '</pre>';
                echo '</div>';
                echo '<details>';
                echo '<summary>Voir la valeur brute</summary>';
                echo '<pre>' . esc_html($value) . '</pre>';
                echo '</details>';
            } else {
                // Limiter l'affichage pour les valeurs très longues
                if (strlen($value) > 500) {
                    echo esc_html(substr($value, 0, 500)) . '...';
                    echo '<details>';
                    echo '<summary>Voir la valeur complète</summary>';
                    echo '<pre>' . esc_html($value) . '</pre>';
                    echo '</details>';
                } else {
                    echo esc_html($value);
                }
            }
            
            echo '</td>';
            echo '<td>';
            
            // Déterminer le type
            if (is_serialized($value)) {
                echo 'Sérialisé';
            } elseif (is_numeric($value)) {
                echo 'Numérique';
            } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
                echo 'URL';
            } elseif (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                echo 'Email';
            } else {
                echo 'Texte';
            }
            
            echo '</td>';
            echo '</tr>';
        }
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// Alternative: Afficher dans une colonne existante
add_action('add_meta_boxes', 'add_metadata_info_metabox');

function add_metadata_info_metabox() {
    global $post;
    if ($post && $post->ID == 279786) {
        add_meta_box(
            'metadata-count-info',
            'Infos Métadonnées',
            'metadata_count_callback',
            null,
            'side',
            'default'
        );
    }
}

function metadata_count_callback($post) {
    $metadata = get_post_meta($post->ID);
    $count = count($metadata);
    $hidden_count = 0;
    
    foreach ($metadata as $key => $values) {
        if (substr($key, 0, 1) === '_') {
            $hidden_count++;
        }
    }
    
    echo '<p><strong>Total métadonnées:</strong> ' . $count . '</p>';
    echo '<p><strong>Métadonnées cachées:</strong> ' . $hidden_count . '</p>';
    echo '<p><strong>Métadonnées publiques:</strong> ' . ($count - $hidden_count) . '</p>';
}