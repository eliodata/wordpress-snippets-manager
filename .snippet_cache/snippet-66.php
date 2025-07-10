<?php
/**
 * Snippet ID: 66
 * Name: CALCULS champs effectifs et inscrits depuis commandes vers actions et calcul stock et book
 * Description: 
 * @active true
 */

/**
 * Met à jour les champs `fsbdd_effectifstage`, `fsbdd_inscrits`, `fsbdd_placedispo` et `fsbdd_sessconfirm` pour un CPT `action-de-formation`
 * basé sur les commandes liées.
 */
function update_action_de_formation_fields($cpt_id) {
    global $wpdb;
    if (!$cpt_id) {
        return;
    }

    // Récupérer toutes les commandes liées à cette action de formation
    $query = "
        SELECT DISTINCT oi.order_id
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim
            ON oi.order_item_id = oim.order_item_id
        WHERE oim.meta_key = 'fsbdd_relsessaction_cpt_produit'
        AND oim.meta_value = %s
    ";
    $order_ids = $wpdb->get_col($wpdb->prepare($query, $cpt_id));
    $total_effectif = 0;
    $total_inscrits = 0;

    // STATUTS VALIDES POUR CALCULER LES DEMANDES (effectifstage)
    $valid_statuses = [
        'wc-completed', 'wc-factureok', 'wc-facturesent', 'wc-attestationform',
        'wc-certifreal', 'wc-avenantvalide', 'wc-avenantconv', 'wc-confirme',
        'wc-inscription', 'wc-modifpreinscript', 'wc-preinscription'
    ];

    foreach ($order_ids as $order_id) {
        $related_order = wc_get_order($order_id);
        if ($related_order) {
            // Récupération de l'effectif à partir de la quantité dans le panier (logique similaire à entete.txt)
            $effectif = 0;

            $items = $related_order->get_items();
            
            foreach ($items as $item) {
                // Vérifier si ce produit est lié à l'action de formation actuelle
                $item_action_id = wc_get_order_item_meta($item->get_id(), 'fsbdd_relsessaction_cpt_produit', true);
                
                // Si l'item est lié à notre action de formation ou si on n'a pas d'ID spécifique
                if ($item_action_id == $cpt_id || empty($item_action_id)) {
                    $effectif += $item->get_quantity();
                }
            }
            
            // Utiliser l'effectif du champ meta comme fallback si la quantité est 0
            if ($effectif == 0) {
                $effectif_fallback = get_post_meta($order_id, 'fsbdd_effectif', true);
                if (!empty($effectif_fallback)) {
                    $effectif = intval($effectif_fallback);
                }
            }

            // Vérification du statut pour les demandes (effectifstage)
            $order_status = $related_order->get_status();
            $order_status_with_prefix = 'wc-' . $order_status;

            if (in_array($order_status_with_prefix, $valid_statuses)) {
                $total_effectif += $effectif;
            }

            // Calcul des inscrits basé sur le champ fsbdd_affaireniveau = 4 (Confirmé)
            $affaire_niveau = get_post_meta($order_id, 'fsbdd_affaireniveau', true);

            // Si le niveau d'affaire est "Confirmé" (valeur 4), compter dans les inscrits
            if ($affaire_niveau == '4') {
                $total_inscrits += $effectif;
            }
        }
    }

    // Mettre à jour les champs sur le CPT `action-de-formation`
    update_post_meta($cpt_id, 'fsbdd_effectifstage', $total_effectif);
    update_post_meta($cpt_id, 'fsbdd_inscrits', $total_inscrits);

    // Récupérer fsbdd_stockmax
    $stockmax = intval(get_post_meta($cpt_id, 'fsbdd_stockmax', true));

    // Calculer fsbdd_placedispo basé sur les inscrits confirmés
    $placedispo = $stockmax - $total_inscrits;
    update_post_meta($cpt_id, 'fsbdd_placedispo', $placedispo);

    // Récupérer la valeur actuelle de fsbdd_sessconfirm
    $current_sessconfirm = get_post_meta($cpt_id, 'fsbdd_sessconfirm', true);

    // Si la valeur actuelle est "BOOKÉ", ne rien changer
    if ($current_sessconfirm !== '4') {
        if ($placedispo > 0) {
            $new_sessconfirm = '2'; // "NON"
        } elseif ($placedispo === 0 && $total_inscrits !== 0) {
            $new_sessconfirm = '3'; // "OUI"
        } else {
            $new_sessconfirm = '1'; // "TODO"
        }
        update_post_meta($cpt_id, 'fsbdd_sessconfirm', $new_sessconfirm);
    }
}


/**
 * Met à jour les champs pour toutes les actions de formation liées à une commande.
 */
function update_fields_for_related_action_de_formation($order_id) {
    $order = wc_get_order($order_id);

    if (!$order) {
        return;
    }

    // Vérifier chaque produit dans la commande
    $items = $order->get_items();

    foreach ($items as $item_id => $item) {
        // Récupérer l'ID du CPT `action-de-formation` lié
        $cpt_id = wc_get_order_item_meta($item_id, 'fsbdd_relsessaction_cpt_produit', true);

        if ($cpt_id) {
            // Mettre à jour les champs pour cette action de formation
            update_action_de_formation_fields($cpt_id);
        }
    }
}

/**
 * Hook : Met à jour les actions de formation liées lorsqu'une commande est sauvegardée.
 */
add_action('woocommerce_update_order', 'update_fields_for_related_action_de_formation');
add_action('save_post_shop_order', 'update_fields_for_related_action_de_formation');

/**
 * Hook : Met à jour les champs lorsqu'un CPT `action-de-formation` est mis à jour.
 */
add_action('save_post_action-de-formation', 'update_action_de_formation_on_save');
function update_action_de_formation_on_save($post_id) {
    if (get_post_type($post_id) === 'action-de-formation') {
        update_action_de_formation_fields($post_id);
    }
}

/**
 * Permet de définir manuellement `fsbdd_sessconfirm` sans être écrasé par les mises à jour automatiques,
 * uniquement si l'utilisateur choisit "BOOKÉ".
 */
function allow_manual_set_booke($post_id, $post, $update) {
    // Vérifier si c'est une sauvegarde de 'action-de-formation'
    if ($post->post_type !== 'action-de-formation') {
        return;
    }

    // Vérifier si une valeur manuelle a été soumise via l'interface (à adapter selon votre formulaire)
    if (isset($_POST['fsbdd_sessconfirm'])) {
        $manual_value = sanitize_text_field($_POST['fsbdd_sessconfirm']);

        // Vérifier si la valeur est "BOOKÉ" (valeur '4')
        if ($manual_value === '4') {
            // Mettre à jour la valeur sans appliquer les conditions automatiques
            update_post_meta($post_id, 'fsbdd_sessconfirm', $manual_value);
            return;
        }
    }

    // Sinon, appliquer les mises à jour automatiques
    update_action_de_formation_fields($post_id);
}
add_action('save_post_action-de-formation', 'allow_manual_set_booke', 20, 3);

/**
 * FONCTION UTILITAIRE : Recalcule les effectifs pour toutes les actions de formation
 * Utilise cette fonction une seule fois pour mettre à jour toutes les données
 */
function recalculate_all_action_de_formation_fields() {
    $actions = get_posts(array(
        'post_type' => 'action-de-formation',
        'post_status' => 'any',
        'numberposts' => -1
    ));
    
    $count = 0;
    foreach ($actions as $action) {
        update_action_de_formation_fields($action->ID);
        $count++;
    }
    
    return "Recalcul terminé pour $count actions de formation.";
}


