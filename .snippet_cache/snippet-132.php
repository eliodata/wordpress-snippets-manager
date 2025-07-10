<?php
/**
 * Snippet ID: 132
 * Name: Masquer champs meta categories actions etc... cote client devis mails commandes etc...
 * Description: 
 * @active true
 */

/**
 * Masquer certaines métas dans les commandes WooCommerce (front, e-mails, PDF, etc.)
 */
function my_hide_order_item_meta_data( $formatted_meta ) {
    // Liste des méta-clés à masquer
    $meta_keys_to_hide = array(
        'fsbdd_relsessproduit_cpt',
        'fsbdd_relsessaction_cpt_produit',
        'nombre_categories',
    );

    // On parcourt toutes les métas formatées et on supprime celles qui correspondent
    foreach ( $formatted_meta as $key => $meta ) {
        if ( in_array( $meta->key, $meta_keys_to_hide ) ) {
            unset( $formatted_meta[$key] );
        }
    }

    return $formatted_meta;
}
add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'my_hide_order_item_meta_data', 10, 1 );
