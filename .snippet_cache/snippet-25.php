<?php
/**
 * Snippet ID: 25
 * Name: test pixtral
 * Description: 
 * @active false
 */

// Ajouter la metabox aux CPT 'formateur' et 'salle-de-formation'
function add_fsbdd_metabox() {
    add_meta_box(
        'fsbdd_metabox', // ID de la metabox
        'ACTIONS DE FORMATIONS RÉALISÉES', // Titre de la metabox
        'fsbdd_metabox_callback', // Fonction de callback pour afficher le contenu
        ['formateur', 'salle-de-formation'], // CPT auxquels ajouter la metabox
        'normal', // Contexte
        'high' // Priorité
    );
}
add_action('add_meta_boxes', 'add_fsbdd_metabox');

// Fonction de callback pour afficher le contenu de la metabox
function fsbdd_metabox_callback($post) {
    global $wpdb;

    // Récupérer l'ID du CPT en cours
    $post_id = $post->ID;

    // Récupérer tous les produits pour vérifier lesquels contiennent ce formateur ou cette salle dans 'fsbdd_user_formateurrel' ou 'fsbdd_salle_formationrel'
    $products = get_posts([
        'post_type' => 'product',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]);

    $linked_products = [];

    foreach ($products as $product_id) {
        $planning = get_post_meta($product_id, 'fsbdd_planning', true);

        if ($planning && is_array($planning)) {
            foreach ($planning as $day) {
                if (isset($day['fsbdd_gpformatr']) && is_array($day['fsbdd_gpformatr'])) {
                    foreach ($day['fsbdd_gpformatr'] as $formateur) {
                        if (!empty($formateur['fsbdd_user_formateurrel']) && $formateur['fsbdd_user_formateurrel'] == $post_id) {
                            $linked_products[$product_id] = $product_id; // Utiliser l'ID du produit dans la liste
                            break 2;
                        }
                    }
                }
                if (isset($day['fsbdd_salle_formationrel']) && $day['fsbdd_salle_formationrel'] == $post_id) {
                    $linked_products[$product_id] = $product_id; // Utiliser l'ID du produit dans la liste
                    break 2;
                }
            }
        }
    }

    if (!empty($linked_products)) {
        echo '<table class="widefat" style="width: 100%; border-collapse: collapse;">';
        echo '<thead><tr style="background-color: #f1f1f1;">';
        echo '<th style="width: 10%;"><strong>Type de session</strong></th>';
        echo '<th style="width: 10%;"><strong>Numéro</strong></th>';
        echo '<th style="width: 40%;"><strong>Nom</strong></th>';
        echo '<th style="width: 10%;"><strong>Début</strong></th>';
        echo '<th style="width: 10%;"><strong>Fin</strong></th>';
        echo '<th style="width: 10%;"><strong>Effectif</strong></th>';
        echo '</tr></thead>';
        echo '<tbody>';

        $row_count = 0;
        foreach ($linked_products as $product_id) {
            $type_session = get_post_meta($product_id, 'fsbdd_typesession', true);
            $start_date = get_post_meta($product_id, 'we_startdate', true);
            $end_date = get_post_meta($product_id, 'we_enddate', true);
            $effectif = get_post_meta($product_id, 'fsbdd_inscrits', true);
            $product_name = get_the_title($product_id);
            $product_name = strlen($product_name) > 40 ? substr($product_name, 0, 40) . '...' : $product_name;

            // Formatage des dates
            $start_date = date('d/m/Y', $start_date);
            $end_date = date('d/m/Y', $end_date);

            echo '<tr style="background-color: ' . ($row_count % 2 == 0 ? '#f9f9f9' : '#ffffff') . ';">';
            echo '<td>' . esc_html($type_session) . '</td>';
            echo '<td><a href="' . get_edit_post_link($product_id) . '">' . esc_html($product_id) . '</a></td>';
            echo '<td>' . esc_html($product_name) . '</td>';
            echo '<td>' . esc_html($start_date) . '</td>';
            echo '<td>' . esc_html($end_date) . '</td>';
            echo '<td>' . esc_html($effectif) . '</td>';
            echo '</tr>';
            $row_count++;
        }

        echo '</tbody></table>';
    } else {
        echo '<p>Aucune formation associée trouvée.</p>';
    }
}
