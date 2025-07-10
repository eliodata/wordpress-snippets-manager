<?php
/**
 * Snippet ID: 197
 * Name: Masquer metas unités UT pratique et théoriques sur line items facture pdf etc... meta privées
 * Description: 
 * @active true
 */

/**
 * Rendre les métadonnées UT "privées" pour qu'elles ne s'affichent pas
 * WooCommerce ne montre pas les metas cachées dans les factures
 */

// Déclarer les UT comme métadonnées cachées
add_filter('woocommerce_hidden_order_itemmeta', 'hide_ut_meta_from_display');

function hide_ut_meta_from_display($hidden_meta_keys) {
    // Ajouter les clés UT à la liste des métadonnées cachées
    $hidden_meta_keys[] = 'ut_pratique';
    $hidden_meta_keys[] = 'ut_theorique';
    
    return $hidden_meta_keys;
}

/**
 * Alternative : Modifier les clés pour les rendre privées (avec underscore)
 * Décommentez cette section si la méthode ci-dessus ne fonctionne pas
 */
/*
// Renommer les métadonnées existantes pour les rendre privées
add_action('woocommerce_checkout_order_processed', 'convert_ut_meta_to_private', 20, 1);

function convert_ut_meta_to_private($order_id) {
    $order = wc_get_order($order_id);
    
    if (!$order) return;
    
    foreach ($order->get_items() as $item_id => $item) {
        // Vérifier si les UT existent
        $ut_pratique = $item->get_meta('ut_pratique');
        $ut_theorique = $item->get_meta('ut_theorique');
        
        if ($ut_pratique !== '') {
            $item->update_meta_data('_ut_pratique', $ut_pratique); // Ajouter version privée
            $item->delete_meta_data('ut_pratique'); // Supprimer version publique
        }
        
        if ($ut_theorique !== '') {
            $item->update_meta_data('_ut_theorique', $ut_theorique); // Ajouter version privée
            $item->delete_meta_data('ut_theorique'); // Supprimer version publique
        }
        
        $item->save();
    }
}
*/