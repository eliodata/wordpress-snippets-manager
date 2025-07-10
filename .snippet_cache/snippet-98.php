<?php
/**
 * Snippet ID: 98
 * Name: ENREGISTREMENTS METAS REGLEMENTS CHARGES SUR CPT
 * Description: 
 * @active true
 */

/**
 * Met à jour les informations de règlements et de charges dans les métas du CPT parent (formateur, formateur-passe, salle-de-formation).
 *
 * @param int $post_id ID du post (formateur, formateur-passe, salle-de-formation) en cours de sauvegarde.
 */
function maj_reglements_et_charges($post_id) {
    // Vérifier si c'est bien un des CPT concernés
    $cpt_list = ['formateur', 'formateur-passe', 'salle-de-formation'];
    if (!in_array(get_post_type($post_id), $cpt_list)) {
        return;
    }

    // 1) Récupérer tous les règlements de ce CPT (post formateur/salle…)
    $reglements = get_post_meta($post_id, 'fsbdd_reglements', true) ?: [];

    // 2) Construire un index des montants réglés par action : action_id => total réglé
    $reglements_totals = [];
    foreach ($reglements as $reglement) {
        if (
            isset($reglement['sessions'], $reglement['montants_par_session']) 
            && is_array($reglement['sessions']) 
            && is_array($reglement['montants_par_session'])
        ) {
            foreach ($reglement['sessions'] as $session_id) {
                $montant_session = isset($reglement['montants_par_session'][$session_id])
                                   ? floatval($reglement['montants_par_session'][$session_id])
                                   : 0;
                if (!isset($reglements_totals[$session_id])) {
                    $reglements_totals[$session_id] = 0;
                }
                $reglements_totals[$session_id] += $montant_session;
            }
        }
    }

    // 3) Récupérer toutes les actions liées au post parent (via fsbdd_selectcoutform ou fsbdd_selectctfourn)
    $actions = get_posts([
        'post_type'      => 'action-de-formation',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'post__not_in'   => [268081], // Exclusion éventuelle
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => 'fsbdd_selectcoutform',
                'value'   => $post_id,
                'compare' => '=',
            ],
            [
                'key'     => 'fsbdd_selectctfourn',
                'value'   => $post_id,
                'compare' => '=',
            ],
        ],
    ]);

    // Variables d’accumulateur
    $total_regle_global       = 0; // Pour le meta fsbdd_total_reglements
    $total_charges_global     = 0; // Pour le meta fsbdd_total_montants (total charges)
    $total_charges_validees   = 0; // Pour le meta fsbdd_total_charges_validees
    $actions_summary          = []; // Pour le meta fsbdd_actions_summary (suivi détaillé)

    // 4) Boucler sur chaque action pour agréger les infos
    foreach ($actions as $action) {
        $action_id    = $action->ID;
        $action_title = get_the_title($action_id);

        // Récupérer les charges de cette action (meta fsbdd_grpctsformation)
        $group_charges = get_post_meta($action_id, 'fsbdd_grpctsformation', true);
        $cout_global   = 0.0; // somme de fsbdd_montrechrge (ou d’autres champs si besoin)

        // Calcul du coût global de cette action (uniquement pour ce CPT)
        if (is_array($group_charges)) {
            foreach ($group_charges as $charge) {
                // Vérifier si la charge est bien liée à ce post parent
                if (
                    (isset($charge['fsbdd_selectcoutform']) && intval($charge['fsbdd_selectcoutform']) === $post_id)
                    || (isset($charge['fsbdd_selectctfourn']) && intval($charge['fsbdd_selectctfourn']) === $post_id)
                ) {
                    $cout_global += floatval($charge['fsbdd_montrechrge'] ?? 0);
                }
            }
        }

        // Montant total déjà réglé pour cette action
        $total_regle_action = isset($reglements_totals[$action_id]) ? $reglements_totals[$action_id] : 0;
        // Solde pour cette action = coût global - total réglé
        $solde_action = $cout_global - $total_regle_action;

        // Accumulateurs globaux
        $total_regle_global   += $total_regle_action;
        $total_charges_global += $cout_global;

        // Lister le détail des règlements pour cette action
        $action_reg_details = [];
        foreach ($reglements as $reglement) {
            // Si ce règlement impacte l’action en question
            if (isset($reglement['montants_par_session'][$action_id])) {
                $action_reg_details[] = [
                    'date'    => $reglement['date']    ?? '',
                    'montant' => floatval($reglement['montants_par_session'][$action_id]),
                    'details' => $reglement['details'] ?? '',
                ];
            }
        }

        // Calcul du « montant total des charges validées » pour cette action
        // => Toute charge ayant fsbdd_daterchrge non vide
        if (is_array($group_charges)) {
            foreach ($group_charges as $charge) {
                if (!empty($charge['fsbdd_daterchrge'])) {
                    // Charge validée, vérifier si elle appartient au même CPT
                    if (
                        (isset($charge['fsbdd_selectcoutform']) && intval($charge['fsbdd_selectcoutform']) === $post_id)
                        || (isset($charge['fsbdd_selectctfourn']) && intval($charge['fsbdd_selectctfourn']) === $post_id)
                    ) {
                        $montant_charge = floatval($charge['fsbdd_montrechrge'] ?? 0);
                        $total_charges_validees += $montant_charge;
                    }
                }
            }
        }

        // Construire un petit récap pour l’action courante
        $actions_summary[$action_id] = [
            'action_title' => $action_title,
            'total_regle'  => $total_regle_action,
            'cout_global'  => $cout_global,
            'solde'        => $solde_action,
            'reglements'   => $action_reg_details,
        ];
    }

    // 5) Le solde global = total des coûts - total déjà réglé
    //    (ex. dans votre screenshot 900 - 560 = 340)
	$solde_global = $total_charges_global - $total_regle_global;

    // 6) Enregistrer les différentes metas dans le CPT parent
    update_post_meta($post_id, 'fsbdd_total_montants',         $total_charges_global);   // ex. 900
    update_post_meta($post_id, 'fsbdd_total_charges_validees', $total_charges_validees); // ex. 400
    update_post_meta($post_id, 'fsbdd_total_reglements',       $total_regle_global);     // ex. 560
	update_post_meta($post_id, 'fsbdd_solde', $solde_global);
    update_post_meta($post_id, 'fsbdd_actions_summary',        $actions_summary);
}
