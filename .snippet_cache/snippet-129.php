<?php
/**
 * Snippet ID: 129
 * Name: Déclenchement statut certificat réalisation et attestations suivi documents automatique si certifiés sur action
 * Description: 
 * @active true
 */

/**
 * 1. Fonction pour récupérer les IDs de commandes liées à une action-de-formation.
 */
function get_order_ids_for_action_de_formation($action_id) {
    global $wpdb;
    $results = $wpdb->get_col($wpdb->prepare("
        SELECT order_id
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim 
            ON (oi.order_item_id = oim.order_item_id)
        WHERE oim.meta_key = 'fsbdd_relsessaction_cpt_produit'
          AND oim.meta_value = %d
    ", $action_id));
    return $results; 
}

/**
 * 2. Hook : Après la sauvegarde du CPT action-de-formation, vérifier si fsbdd_etatemargm == 4
 *    - Si oui, on passe la commande en statut wc-certifreal
 *    - Si en plus fsbdd_select_suivi_declenchmt == '1', on passe en statut wc-attestationform
 */
add_action('save_post_action-de-formation', 'check_and_update_order_status_on_certification', 99, 3);
function check_and_update_order_status_on_certification($post_id, $post, $update) {
    global $wpdb;
    
    // Éviter les autosaves ou les créations initiales non pertinentes pour ce workflow
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Vérifier la nouvelle valeur de fsbdd_etatemargm
    $etatemargm = get_post_meta($post_id, 'fsbdd_etatemargm', true);
    
    // Si l'état d'émargement est 4 (certifié)
    if ('4' === $etatemargm) {
        // Récupérer toutes les commandes liées à cette action-de-formation
        $order_ids = get_order_ids_for_action_de_formation($post_id);
        
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) {
                continue;
            }
            
            // NOUVELLE VÉRIFICATION : Vérifier que la commande est dans un statut autorisé
            $allowed_statuses = array('wc-confirme', 'wc-avenantconv', 'wc-avenantvalide', 'wc-facturesent', 'wc-factureok');
            $current_status = $order->get_status();
            
            if (!in_array('wc-' . $current_status, $allowed_statuses)) {
                // Statut non autorisé, ignorer cette commande
                continue;
            }
            
            // 1) Récupérer l'ID WordPress du client (user ID)
            $customer_user_id = $order->get_customer_id();
            if (!$customer_user_id) {
                // Pas de client associé, on passe quand même en certifreal
                if (!$order->has_status('certifreal') && !$order->has_status('attestationform')) {
                    $order->update_status(
                        'certifreal',
                        'Action de formation certifiée - Certificat de réalisation généré.'
                    );
                }
                continue;
            }
            
            // 2) Trouver l'ID du CPT client via la table mb_relationships (type 'clients-wp-bdd')
            $client_cpt_id = $wpdb->get_var($wpdb->prepare("
                SELECT `to`
                FROM {$wpdb->prefix}mb_relationships
                WHERE `from` = %d
                  AND `type` = 'clients-wp-bdd'
            ", $customer_user_id));
            
            if (!$client_cpt_id) {
                // Pas de CPT client trouvé, on passe quand même en certifreal
                if (!$order->has_status('certifreal') && !$order->has_status('attestationform')) {
                    $order->update_status(
                        'certifreal',
                        'Action de formation certifiée - Certificat de réalisation généré.'
                    );
                }
                continue;
            }
            
            // 3) Vérifier la valeur de fsbdd_select_suivi_declenchmt sur le CPT client
            $declenchement_value = get_post_meta($client_cpt_id, 'fsbdd_select_suivi_declenchmt', true);
            
            // 4) Déterminer le statut cible selon le suivi déclenchement
            if ($declenchement_value === '1') {
                // Si déclenchement = OUI, on veut le statut attestationform
                if (!$order->has_status('attestationform')) {
                    $order->update_status(
                        'attestationform',
                        'Action de formation certifiée - Attestation de formation générée (déclenchement = OUI).'
                    );
                }
            } else {
                // Si déclenchement != OUI, on veut le statut certifreal
                if (!$order->has_status('certifreal') && !$order->has_status('attestationform')) {
                    $order->update_status(
                        'certifreal',
                        'Action de formation certifiée - Certificat de réalisation généré.'
                    );
                }
            }
        }
    }
}