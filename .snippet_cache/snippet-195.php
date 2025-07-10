<?php
/**
 * Snippet ID: 195
 * Name: Afficher choix categories caces habilitations dans infos produit article commande admin panier
 * Description: 
 * @active true
 */

/**
 * Affiche les catégories sélectionnées dans les line items des commandes (admin)
 */


/**
 * Version simplifiée : Affichage sous le nom du produit
 */
add_action('woocommerce_before_order_itemmeta', 'afficher_categories_sous_produit', 10, 3);
function afficher_categories_sous_produit($item_id, $item, $product) {
    // Vérifier qu'on est en admin
    if (!is_admin()) {
        return;
    }
    
    $choix_categorie = wc_get_order_item_meta($item_id, 'choix_categorie', true);
    
    if ($choix_categorie) {
        echo '<div style="margin: 5px 0; font-size: 12px;">';
        echo '<strong style="color: #666;">Catégories : </strong>';
        
        $categories = explode(',', $choix_categorie);
        $badges = array();
        
        foreach ($categories as $cat) {
            $cat = trim($cat);
            if (!empty($cat)) {
                $badges[] = '<span style="background: #e8f4f8; color: #0073aa; padding: 2px 6px; border-radius: 3px; font-size: 11px;">' . esc_html($cat) . '</span>';
            }
        }
        
        echo implode(' ', $badges);
        echo '</div>';
    }
}
