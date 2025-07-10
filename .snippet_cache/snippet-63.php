<?php
/**
 * Snippet ID: 63
 * Name: afficher metas produit
 * Description: 
 * @active false
 */

add_action('add_meta_boxes', function () {
    // Ajoute une metabox uniquement pour le produit 16438
    global $post;
    if ($post && $post->ID == 16438 && get_post_type($post) == 'product') {
        add_meta_box(
            'cpt_meta_from_product',
            __('CPT lié au produit', 'textdomain'),
            'afficher_cpt_lie_depuis_produit',
            'product',
            'normal',
            'default'
        );
    }
});

function afficher_cpt_lie_depuis_produit($post) {
    $product_id = $post->ID;

    // Rechercher les CPT 'action-de-formation' liés à ce produit
    $args = [
        'post_type'      => 'action-de-formation',
        'post_status'    => 'publish',
        'meta_query'     => [
            [
                'key'     => 'fsbdd_relsessproduit',
                'value'   => $product_id,
                'compare' => '='
            ]
        ],
        'posts_per_page' => -1, // Récupérer tous les CPT associés
    ];
    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        echo '<p>' . __('Aucun CPT lié trouvé pour ce produit.', 'textdomain') . '</p>';
        return;
    }

    echo '<h3>' . __('CPT(s) lié(s) au produit :', 'textdomain') . '</h3>';
    while ($query->have_posts()) {
        $query->the_post();

        $cpt_id = get_the_ID();
        echo '<p><strong>ID CPT :</strong> ' . esc_html($cpt_id) . '</p>';

        // Afficher les métadonnées du CPT
        $cpt_meta = get_post_meta($cpt_id);

        if (!empty($cpt_meta)) {
            echo '<ul>';
            foreach ($cpt_meta as $key => $values) {
                echo '<li><strong>' . esc_html($key) . ':</strong> ' . esc_html(implode(', ', $values)) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>' . __('Aucune métadonnée trouvée pour ce CPT.', 'textdomain') . '</p>';
        }
    }

    // Réinitialiser la requête
    wp_reset_postdata();
}
