<?php
/**
 * Snippet ID: 17
 * Name: test metadata bdd formateur
 * Description: 
 * @active false
 */

function afficher_metadata_cpt() {
    $post_id = 505; // ID du CPT à inspecter
    $meta_data = get_post_meta($post_id);

    echo '<h2>Métadonnées pour le CPT avec ID ' . $post_id . ' :</h2>';
    echo '<table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse;width: 100%">';
    echo '<tr><th>Clé</th><th>Valeur</th></tr>';

    if (!empty($meta_data)) {
        foreach ($meta_data as $key =&gt; $values) {
            echo '<tr>';
            echo '<td>' . esc_html($key) . '</td>';
            echo '<td>';
            foreach ($values as $value) {
                echo '<pre>' . esc_html(print_r($value, true)) . '</pre>';
            }
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="2">Aucune métadonnée trouvée pour ce CPT.</td></tr>';
    }

    echo '</table>';
}

// Pour afficher ces métadonnées dans l'admin WordPress, vous pouvez utiliser cette action
add_action('admin_notices', 'afficher_metadata_cpt');
