<?php
/**
 * Snippet ID: 11
 * Name: CALCUL AUTOMATIQUES FACTURATION et DATES COMMANDE V2 optimisée
 * Description: 
 * @active false
 */

// Hook principal pour la mise à jour des commandes WooCommerce
add_action('woocommerce_update_order', 'mettre_a_jour_commandes_woocommerce');

function mettre_a_jour_commandes_woocommerce($order_id) {
    if (!$order_id) return;

    // Frais client
    $total_frais_client = calculer_total_groupe_champs($order_id, 'fsbdd_gpfraisclient', 'fsbdd_montfraisclient');
    update_post_meta($order_id, 'fsbdd_totalfrais', $total_frais_client);

    // Frais TTC
    calculer_total_frais_ttc($order_id, $total_frais_client);

    // Charges
    mettre_a_jour_charges($order_id);

    // Marge
    mettre_a_jour_marge($order_id);

    // Règlements client
    $total_reglement_client = calculer_total_groupe_champs($order_id, 'fsbdd_reglmtclients', 'fsbdd_clientreglmt');
    update_post_meta($order_id, 'fsbdd_ttrglmtclient', $total_reglement_client);

    // Solde client
    calculer_solde($order_id, 'fsbdd_totalclient', 'fsbdd_ttrglmtclient', 'fsbdd_soldeclient');

    // Règlements OPCO
    $total_reglement_opco = calculer_total_groupe_champs($order_id, 'fsbdd_reglmtopco', 'fsbdd_opcorglmt');
    update_post_meta($order_id, 'fsbdd_ttrglmtopco', $total_reglement_opco);

    // Solde OPCO
    calculer_solde($order_id, 'fsbdd_totalopco', 'fsbdd_ttrglmtopco', 'fsbdd_soldopco');

    // Solde global
    $solde_client = (float)get_post_meta($order_id, 'fsbdd_soldeclient', true);
    $solde_opco = (float)get_post_meta($order_id, 'fsbdd_soldopco', true);
    update_post_meta($order_id, 'fsbdd_solde', $solde_client + $solde_opco);

    // Mise à jour de la date d'échéance de facturation
    calculer_date_fin_fact($order_id);
}

// Fonction utilitaire pour calculer le total d'un groupe de champs clonables avec une condition
function calculer_total_groupe_champs_conditionnel($order_id, $groupe_nom, $champ_nom, $condition_field = null, $condition_value = null) {
    $groups = rwmb_meta($groupe_nom, ['object_type' => 'post'], $order_id);
    $total = 0;
    foreach ($groups as $group) {
        if (!empty($group[$champ_nom])) {
            if ($condition_field && $condition_value !== null) {
                if (isset($group[$condition_field]) && $group[$condition_field] == $condition_value) {
                    $total += (float)$group[$champ_nom];
                }
            } else {
                $total += (float)$group[$champ_nom];
            }
        }
    }
    return $total;
}

// Fonction utilitaire pour calculer le total de champs individuels
function calculer_total_champs($order_id, $champs) {
    $total = 0;
    foreach ($champs as $champ) {
        $total += (float)get_post_meta($order_id, $champ, true);
    }
    return $total;
}

// Fonction pour calculer le total des frais TTC
function calculer_total_frais_ttc($order_id, $total_frais_ht) {
    $check_exotva = get_post_meta($order_id, 'fsbdd_check_exotva', true);
    $total_frais_ttc = ($check_exotva !== 'NON') ? $total_frais_ht * 1.20 : $total_frais_ht;
    update_post_meta($order_id, 'fsbdd_totalfraisttc', $total_frais_ttc);
}

// Fonction pour mettre à jour la marge
function mettre_a_jour_marge($order_id) {
    $order = wc_get_order($order_id);
    $order_total_ht = (float)$order->get_subtotal();
    $total_charge = (float)get_post_meta($order_id, 'fsbdd_totalcharge', true);
    $marge = $order_total_ht - $total_charge;
    update_post_meta($order_id, 'fsbdd_marge', $marge);
}

// Fonction pour calculer le solde
function calculer_solde($order_id, $meta_total, $meta_reglement, $meta_solde) {
    $total = (float)get_post_meta($order_id, $meta_total, true);
    $reglement = (float)get_post_meta($order_id, $meta_reglement, true);
    $solde = $total - $reglement;
    update_post_meta($order_id, $meta_solde, $solde);
}

// Fonction pour mettre à jour les charges
function mettre_a_jour_charges($order_id) {
    // Calcul des frais de mission
    $total_frais_mission = calculer_total_groupe_champs_conditionnel($order_id, 'fsbdd_grpctsformation', 'fsbdd_montrechrge', 'fsbdd_typechargedue', 2);
    update_post_meta($order_id, 'fsbdd_fraismission', $total_frais_mission);

    // Calcul des coûts de formation
    $total_couts_formation = calculer_total_groupe_champs_conditionnel($order_id, 'fsbdd_grpctsformation', 'fsbdd_montrechrge', 'fsbdd_typechargedue', 1);
    update_post_meta($order_id, 'fsbdd_coutsformrs', $total_couts_formation);

    // Calcul des charges logistiques
    $total_charges_logistiques = calculer_total_groupe_champs_conditionnel($order_id, 'fsbdd_grpctsformation', 'fsbdd_montrechrge', 'fsbdd_typechargedue', 3);
    update_post_meta($order_id, 'fsbdd_ttchrglogisti', $total_charges_logistiques);

    // Calcul du total des charges
    $total_charge = $total_frais_mission + $total_couts_formation + $total_charges_logistiques;
    update_post_meta($order_id, 'fsbdd_totalcharge', $total_charge);
}

// Fonction pour calculer la date de fin de facturation
function calculer_date_fin_fact($order_id) {
    $date_fact = get_post_meta($order_id, 'fsbdd_datefact', true);
    if (empty($date_fact)) return;

    $date_fact_obj = DateTime::createFromFormat('d-m-Y', $date_fact);
    if (!$date_fact_obj) return;

    $date_fact_obj->modify('+28 days');
    $date_fin_fact = $date_fact_obj->format('d-m-Y');
    update_post_meta($order_id, 'fsbdd_datefinfact', $date_fin_fact);
}