<?php
/**
 * Snippet ID: 35
 * Name: Mise à jour Relation cpt action de formation / produits / commandes - adresse session inter -
 * Description: 
 * @active true
 */

add_action('save_post_action-de-formation', 'mettre_a_jour_produit_lie', 999, 3);
function mettre_a_jour_produit_lie($post_id, $post, $update) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'action-de-formation') {
        return;
    }
    if ($post->post_type !== 'action-de-formation') {
        return;
    }

    /*
     * 1) Mettre à jour la meta du PRODUIT lié
     * (champ fsbdd_relsessproduit_cpt sur le produit, depuis le CPT)
     */
    $produit_id = get_post_meta($post_id, 'fsbdd_relsessproduit', true);
    if (!empty($produit_id)) {
        update_post_meta($produit_id, 'fsbdd_relsessproduit_cpt', $post_id);
    }

    /*
     * 2) Mettre à jour la meta du LINE ITEM (fsbdd_select_lieuforminter)
     *    dans les commandes existantes, si le CPT est lié (fsbdd_relsessaction_cpt_produit)
     */
    $lieu_complet = get_post_meta($post_id, 'fsbdd_select_lieusession', true);
    if (empty($lieu_complet)) {
        return; // On ne fait rien si pas d'adresse
    }

    global $wpdb;
    $table_itemmeta = $wpdb->prefix . 'woocommerce_order_itemmeta';

    // Récupérer la liste des line items liés à ce CPT
    $results = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT order_item_id
             FROM $table_itemmeta
             WHERE meta_key = 'fsbdd_relsessaction_cpt_produit'
               AND meta_value = %d",
            $post_id
        )
    );

    if (!empty($results)) {
        // Mettre à jour la meta fsbdd_select_lieuforminter avec la nouvelle adresse
        foreach ($results as $line_item_id) {
            wc_update_order_item_meta($line_item_id, 'fsbdd_select_lieuforminter', $lieu_complet);
        }
    }
}
