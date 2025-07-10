<?php
/**
 * Snippet ID: 198
 * Name: Metabox side Suivi Rapprochement Commande WooCommerce
 * Description: <p>Ajoute une metabox de 'Suivi Rapprochement' sur les pages de commande WooCommerce. Cet outil permet de suivre l'avancement du traitement de chaque commande à l'aide d'une checklist d'étapes clés. Une barre de progression visuelle et un pourcentage indiquent le statut global, offrant une vue rapide et efficace de l'état de chaque commande.</p>
 * @active true
 */

/**
 * Metabox pour le suivi de rapprochement des commandes WooCommerce
 * À ajouter dans functions.php ou dans un plugin
 */

// Ajouter la metabox
add_action('add_meta_boxes', 'fsbdd_add_rapprochement_metabox');

function fsbdd_add_rapprochement_metabox() {
    add_meta_box(
        'fsbdd_rapprochement_metabox',
        'Suivi Rapprochement',
        'fsbdd_rapprochement_metabox_content',
        'shop_order',
        'side',
        'high'
    );
}

// Contenu de la metabox
function fsbdd_rapprochement_metabox_content($post) {
    // Sécurité avec nonce
    wp_nonce_field('fsbdd_rapprochement_metabox', 'fsbdd_rapprochement_nonce');
    
    // Liste des étapes de rapprochement
    $etapes = array(
        'session' => 'Session OK',
        'specificites' => 'Spécificités OK',
        'convocations' => 'Convocations OK',
        'quantites_couts' => 'Quantités, Coûts, Frais & TVA OK',
        'subro_reglements' => 'Subro & Règlements OK',
        'client_bdd_web' => 'Client BDD & Web OK'
    );
    
    // Récupérer les valeurs existantes
    $valeurs_actuelles = array();
    foreach ($etapes as $key => $label) {
        $valeurs_actuelles[$key] = get_post_meta($post->ID, 'fsbdd_rappro_' . $key, true);
    }
    
    echo '<div class="fsbdd-rapprochement-container">';
    echo '<style>
        .fsbdd-rapprochement-container {
            padding: 0;
        }
        .fsbdd-rapprochement-container .etape {
            display: flex;
            align-items: center;
            margin-bottom: 2px;
            padding: 3px 5px;
            border-radius: 3px;
            transition: background-color 0.2s;
        }
        .fsbdd-rapprochement-container .etape.etape-ok label {
            color: #3c8c42;
        }
        .fsbdd-rapprochement-container .etape input[type="checkbox"] {
            margin-right: 8px;
            width: 16px;
            height: 16px;
            accent-color: #4caf50;
        }
        .fsbdd-rapprochement-container .etape label {
            flex-grow: 1;
            margin: 0;
            font-size: 13px;
            font-weight: normal;
            cursor: pointer;
        }
        .fsbdd-rapprochement-progress-container {
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .fsbdd-rapprochement-progress-bar {
            background-color: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            height: 8px;
            flex-grow: 1;
        }
        .fsbdd-rapprochement-progress-fill {
            background-color: #4caf50;
            width: 0%;
            height: 100%;
            transition: width 0.4s ease-in-out;
        }
        .fsbdd-rapprochement-progress-text {
            font-size: 11px;
            font-weight: bold;
            white-space: nowrap;
        }
    </style>';

    // Afficher les cases à cocher
    foreach ($etapes as $key => $label) {
        $checked = checked($valeurs_actuelles[$key], '1', false);
        $class = $valeurs_actuelles[$key] ? ' etape-ok' : '';
        
        echo '<div class="etape' . $class . '">';
        echo '<input type="checkbox" id="fsbdd_rappro_' . $key . '" name="fsbdd_rappro_' . $key . '" value="1" ' . $checked . '>';
        echo '<label for="fsbdd_rappro_' . $key . '">' . esc_html($label) . '</label>';
        echo '</div>';
    }
    
    // Indicateur de progression
    $total_etapes = count($etapes);
    $etapes_completees = count(array_filter($valeurs_actuelles));
    $pourcentage = $total_etapes > 0 ? round(($etapes_completees / $total_etapes) * 100) : 0;
    
    echo '<div class="fsbdd-rapprochement-progress-container">';
    echo '  <div class="fsbdd-rapprochement-progress-bar">';
    echo '    <div class="fsbdd-rapprochement-progress-fill" style="width: ' . $pourcentage . '%;"></div>';
    echo '  </div>';
    echo '  <div class="fsbdd-rapprochement-progress-text">' . $etapes_completees . '/' . $total_etapes . ' (' . $pourcentage . '%)</div>';
    echo '</div>';
    
    echo '</div>';
}

// Sauvegarder les données
add_action('save_post', 'fsbdd_save_rapprochement_metabox');

function fsbdd_save_rapprochement_metabox($post_id) {
    // Vérifications de sécurité
    if (!isset($_POST['fsbdd_rapprochement_nonce']) || 
        !wp_verify_nonce($_POST['fsbdd_rapprochement_nonce'], 'fsbdd_rapprochement_metabox')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Vérifier que c'est bien une commande
    if (get_post_type($post_id) !== 'shop_order') {
        return;
    }
    
    // Liste des champs à sauvegarder
    $champs = array(
        'session',
        'specificites', 
        'convocations',
        'quantites_couts',
        'subro_reglements',
        'client_bdd_web'
    );
    
    // Sauvegarder chaque champ
    foreach ($champs as $champ) {
        $meta_key = 'fsbdd_rappro_' . $champ;
        $valeur = isset($_POST[$meta_key]) ? '1' : '0';
        update_post_meta($post_id, $meta_key, $valeur);
    }
}

// Ajouter une colonne dans la liste des commandes (optionnel)
add_filter('manage_edit-shop_order_columns', 'fsbdd_add_rapprochement_column');

function fsbdd_add_rapprochement_column($columns) {
    $columns['rapprochement'] = 'Rapprochement';
    return $columns;
}

add_action('manage_shop_order_posts_custom_column', 'fsbdd_display_rapprochement_column', 10, 2);

function fsbdd_display_rapprochement_column($column, $post_id) {
    if ($column === 'rapprochement') {
        $champs = array('session', 'specificites', 'convocations', 'quantites_couts', 'subro_reglements', 'client_bdd_web');
        $total = count($champs);
        $completes = 0;
        
        foreach ($champs as $champ) {
            if (get_post_meta($post_id, 'fsbdd_rappro_' . $champ, true) === '1') {
                $completes++;
            }
        }
        
        $pourcentage = round(($completes / $total) * 100);
        $couleur = $pourcentage == 100 ? '#46b450' : ($pourcentage >= 50 ? '#FE8E1B' : '#dc3232');
        
        echo '<span style="color: ' . $couleur . '; font-weight: bold;">';
        echo $completes . '/' . $total . ' (' . $pourcentage . '%)';
        echo '</span>';
    }
}

// Fonction helper pour récupérer l'état d'une étape
function fsbdd_get_rapprochement_status($order_id, $etape) {
    return get_post_meta($order_id, 'fsbdd_rappro_' . $etape, true) === '1';
}

// Fonction helper pour récupérer le pourcentage de completion
function fsbdd_get_rapprochement_percentage($order_id) {
    $champs = array('session', 'specificites', 'convocations', 'quantites_couts', 'subro_reglements', 'client_bdd_web');
    $total = count($champs);
    $completes = 0;
    
    foreach ($champs as $champ) {
        if (get_post_meta($order_id, 'fsbdd_rappro_' . $champ, true) === '1') {
            $completes++;
        }
    }
    
    return round(($completes / $total) * 100);
}


// Ajouter une metabox personnalisée sur la page d'accueil de l'admin
add_action('admin_init', 'fsbdd_add_dashboard_rapprochement_metabox');

function fsbdd_add_dashboard_rapprochement_metabox() {
    add_action('welcome_panel', 'fsbdd_dashboard_rapprochement_metabox_content');
}

function fsbdd_dashboard_rapprochement_metabox_content() {
    // Récupérer les commandes récentes
    $recent_orders = wc_get_orders(array(
        'limit' => 10,
        'orderby' => 'date',
        'order' => 'DESC',
    ));

    echo '<div class="fsbdd-dashboard-rapprochement-container">';
    echo '<h2>Résumé des Rapprochements</h2>';

    foreach ($recent_orders as $order) {
        $order_id = $order->get_id();
        $percentage = fsbdd_get_rapprochement_percentage($order_id);

        echo '<div class="fsbdd-dashboard-order-summary">';
        echo '<p><strong>Commande #' . $order_id . '</strong> - Progression : ' . $percentage . '%</p>';
        echo '</div>';
    }

    echo '</div>';
}