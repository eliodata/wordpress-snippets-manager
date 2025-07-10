<?php
/**
 * Snippet ID: 120
 * Name: Sous titres produits woocommerce
 * Description: 
 * @active true
 */

/**
 * Affiche le sous-titre d'un produit (champ 'fsbdd_thematprod')
 * uniquement pour les utilisateurs ayant le rôle "administrator" ou "referent"
 */

// Affichage sous le titre sur la page produit
add_action('woocommerce_single_product_summary', 'fsbdd_show_subtitle_single_product', 6);
function fsbdd_show_subtitle_single_product() {
    global $product;
    $subtitle = get_post_meta($product->get_id(), 'fsbdd_thematprod', true);

    if ( ! empty($subtitle) ) {
        echo '<div class="fsbdd-subtitle">' . esc_html($subtitle) . '</div>';
    }
}


// Affichage sous le titre sur les pages de listing (archives produits)
add_action('woocommerce_shop_loop_item_title', 'fsbdd_show_subtitle_loop', 11);
function fsbdd_show_subtitle_loop() {
    if ( ! current_user_can('administrator') && ! current_user_can('referent') ) {
        return; // Aucun affichage si l'utilisateur n'est ni admin ni referent
    }

    global $product;
    $subtitle = get_post_meta($product->get_id(), 'fsbdd_thematprod', true);

    if ( ! empty($subtitle) ) {
        echo '<div class="fsbdd-subtitle">' . esc_html($subtitle) . '</div>';
    }
}
