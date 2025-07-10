<?php
/**
 * Snippet ID: 38
 * Name: meta actions
 * Description: 
 * @active false
 */

add_action('admin_notices', function () {
    // ID du CPT à inspecter
    $post_id = 279738;

    // Vérifier si nous sommes sur la page d'édition du post en question
    if (!isset($_GET['post']) || intval($_GET['post']) !== $post_id) {
        return; // Quitter si ce n'est pas la bonne page
    }

    // Vérifier si le post existe
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'action-de-formation') {
        echo '<div class="notice notice-error"><p>Le post ID ' . esc_html($post_id) . ' n\'existe pas ou n\'est pas de type action-de-formation.</p></div>';
        return;
    }

    // Récupérer toutes les métadonnées
    $meta_data = get_post_meta($post_id);

    // Afficher les métadonnées
    if (!empty($meta_data)) {
        echo '<div class="notice notice-info">';
        echo '<p><strong>Métadonnées pour l\'action-de-formation ID ' . esc_html($post_id) . ' :</strong></p>';
        echo '<ul>';
        foreach ($meta_data as $key => $value) {
            echo '<li><strong>' . esc_html($key) . ':</strong> ' . esc_html(print_r($value, true)) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    } else {
        echo '<div class="notice notice-warning"><p>Aucune métadonnée trouvée pour l\'action-de-formation ID ' . esc_html($post_id) . '.</p></div>';
    }
});
