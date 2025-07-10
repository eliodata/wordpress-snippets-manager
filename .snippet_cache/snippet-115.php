<?php
/**
 * Snippet ID: 115
 * Name: meta cpt client
 * Description: 
 * @active false
 */


// Ajouter une metabox pour afficher toutes les métadonnées du CPT client 258001
add_action('add_meta_boxes', 'display_all_meta_for_specific_cpt');

function display_all_meta_for_specific_cpt() {
    $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;

    // Limiter à l'ID spécifique du CPT
    if ($post_id === 258001) {
        add_meta_box(
            'all_meta_display',
            'Toutes les métadonnées',
            'render_all_meta_display',
            'client', // Remplacez "client" par le type de post si différent
            'normal',
            'default'
        );
    }
}

function render_all_meta_display($post) {
    $meta_data = get_post_meta($post->ID);

    if (empty($meta_data)) {
        echo '<p>Aucune métadonnée trouvée pour ce CPT.</p>';
        return;
    }

    echo '<table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f9f9f9;">
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Clé</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Valeur</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($meta_data as $key => $values) {
        foreach ($values as $value) {
            echo '<tr>
                    <td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($key) . '</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($value) . '</td>
                  </tr>';
        }
    }

    echo '  </tbody>
          </table>';
}
