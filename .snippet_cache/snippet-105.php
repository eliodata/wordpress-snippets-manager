<?php
/**
 * Snippet ID: 105
 * Name: PAGE PLANNINGS ACTIONS ADMIN
 * Description: 
 * @active false
 */

/**
 * On crée d'abord une fonction réutilisable qui encapsule
 * tout le traitement de votre snippet initial.
 */
function my_update_fsbdd_grpctsformation( $post_id ) {
    // 1. Exclure tout de suite le post_id 268081
    if ($post_id == 268081) {
        return;
    }

    // Vérifie si le post est du type 'action-de-formation'
    if (get_post_type($post_id) !== 'action-de-formation') {
        return;
    }

    // Récupère les données du planning
    $planning = rwmb_meta('fsbdd_planning', ['object_id' => $post_id]);

    // Récupère les données existantes du groupe de coûts
    $existing_costs = rwmb_meta('fsbdd_grpctsformation', ['object_id' => $post_id]) ?: [];
    $updated_costs = [];

    if (!empty($planning)) {
        // Stockage des disponibilités par formateur/fournisseur
        $availability_totals = [];

        foreach ($planning as $day) {
            // -------------------------------------------------
            // GESTION DES FORMATEURS
            // -------------------------------------------------
            if (!empty($day['fsbdd_gpformatr'])) {
                foreach ($day['fsbdd_gpformatr'] as $formateur) {
                    $formateur_id = $formateur['fsbdd_user_formateurrel'] ?? null;
                    $dispo       = $formateur['fsbdd_dispjourform'] ?? null;

                    if ($formateur_id && $dispo) {
                        $type_journee = strtolower($dispo);
                        if ($type_journee === 'journée') {
                            $count = 1;
                        } elseif (in_array($type_journee, ['am', 'matin'])) {
                            $count = 1; // demi-journée => +1 (vous ajustez selon votre logique)
                        } else {
                            $count = 0; // Valeur par défaut si type inconnu
                        }

                        // On cumule pour ce formateur
                        $availability_totals[$formateur_id]['type']     = '1'; // 1 = Formateur
                        $availability_totals[$formateur_id]['counts'][] = [
                            'type_journee' => $dispo,
                            'count'        => $count,
                        ];
                    }
                }
            }

            // -------------------------------------------------
            // GESTION DES FOURNISSEURS / SALLES
            // -------------------------------------------------
            if (!empty($day['fournisseur_salle'])) {
                foreach ($day['fournisseur_salle'] as $fournisseur) {
                    $fournisseur_id = $fournisseur['fsbdd_user_foursalle'] ?? null;
                    $dispo          = $fournisseur['fsbdd_dispjourform']   ?? null;

                    if ($fournisseur_id && $dispo) {
                        $type_journee = strtolower($dispo);
                        if ($type_journee === 'journée') {
                            $count = 1;
                        } elseif (in_array($type_journee, ['am', 'matin'])) {
                            $count = 1; // demi-journée => +1
                        } else {
                            $count = 0;
                        }

                        // On cumule pour ce fournisseur/salle
                        $availability_totals[$fournisseur_id]['type']     = '2'; // 2 = Fournisseur/Salle
                        $availability_totals[$fournisseur_id]['counts'][] = [
                            'type_journee' => $dispo,
                            'count'        => $count,
                        ];
                    }
                }
            }
        }

        // -------------------------------------------------
        // CALCUL DES COÛTS ET MISE À JOUR DES LIGNES
        // -------------------------------------------------
        foreach ($availability_totals as $id => $data) {
            $meta_key_coutjour      = 'fsform_number_coutjour';      // Clé pour récupérer le coût journalier
            $meta_key_coutdemijour  = 'fsform_number_coutdemijour';  // Clé pour récupérer le coût demi-journée
            $meta_key_deplacemts    = 'fsform_text_deplacemts';      // Clé pour récupérer les infos de déplacement

            // Récupérer les valeurs depuis le CPT formateur/fournisseur
            $cout_journalier        = floatval(get_post_meta($id, $meta_key_coutjour, true)) ?: 0;
            $cout_demijournalier    = floatval(get_post_meta($id, $meta_key_coutdemijour, true)) ?: 0;
            $infos_deplacement      = sanitize_text_field(get_post_meta($id, $meta_key_deplacemts, true)) ?: '';

            // Trouver si une ligne existe déjà dans fsbdd_grpctsformation
            // (pour éviter d'écraser les valeurs manuelles)
            $matching_existing_costs = array_filter($existing_costs, function ($cost) use ($id, $data) {
                return $cost['fsbdd_typechargedue'] === $data['type'] && (
                    ($data['type'] === '1' && $cost['fsbdd_selectcoutform'] == $id) || // Formateur
                    ($data['type'] === '2' && $cost['fsbdd_selectctfourn'] == $id)    // Fournisseur
                );
            });
            $existing_cost = $matching_existing_costs ? reset($matching_existing_costs) : [];

            // Préserver la valeur manuelle si elle existe
            if (! empty($existing_cost['fsbdd_coutjourf'])) {
                $cout_journalier = floatval($existing_cost['fsbdd_coutjourf']);
            }
            if (! empty($existing_cost['fsbdd_coutdemijourf'])) {
                $cout_demijournalier = floatval($existing_cost['fsbdd_coutdemijourf']);
            }

            // Récupérer les frais de mission si déjà définis
            $fraismission = floatval($existing_cost['fsbdd_typechrgfrmiss'] ?? 0);

            // Init totaux
            $total_cout_journalier       = 0;
            $total_cout_demijournalier   = 0;
            $total_quantite_coutjour     = 0;
            $total_quantite_coutdemijour = 0;
            $total_quantite_fraismission = 0;

            // Parcourir les counts
            foreach ($data['counts'] as $count_data) {
                $journee_type = strtolower($count_data['type_journee']);
                if ($journee_type === 'journée') {
                    $total_cout_journalier += $cout_journalier * $count_data['count'];
                    $total_quantite_coutjour += $count_data['count'];
                } elseif (in_array($journee_type, ['am', 'matin'])) {
                    $total_cout_demijournalier += $cout_demijournalier * $count_data['count'];
                    $total_quantite_coutdemijour += $count_data['count'];
                }
                // Les frais de mission augmentent pour chaque occurrence (journée ou demi-journée)
                $total_quantite_fraismission += $count_data['count'];
            }

            // Calculer le total complet
            $total_cout = $total_cout_journalier
                        + $total_cout_demijournalier
                        + ($fraismission * $total_quantite_fraismission);

            // Alimenter le tableau final
            $updated_costs[] = [
                'fsbdd_typechargedue'         => $data['type'], // 1 = Formateur, 2 = Fournisseur
                'fsbdd_selectcoutform'        => $data['type'] === '1' ? $id : '',
                'fsbdd_selectctfourn'         => $data['type'] === '2' ? $id : '',
                'fsbdd_coutjourf'             => $cout_journalier,
                'fsbdd_qtitectjour'           => $total_quantite_coutjour,
                'fsbdd_coutdemijourf'         => $cout_demijournalier,
                'fsbdd_qtitectdemijour'       => $total_quantite_coutdemijour,
                'fsbdd_fraismission'          => $fraismission,
                'fsbdd_qtitefrannex'          => $total_quantite_fraismission,
                'fsbdd_typechrgfrmiss'        => $fraismission,
                'fsbdd_montrechrge'           => $total_cout, // Montant total calculé
                'fsbdd_ttcout_journalier'     => $total_cout_journalier,
                'fsbdd_ttcout_demijournalier' => $total_cout_demijournalier,
                'fsbdd_ttfraismission'        => $fraismission * $total_quantite_fraismission,
                'fsbdd_infoschargedue'        => sanitize_text_field($existing_cost['fsbdd_infoschargedue'] ?? ''),
                'fsbdd_infosfraisannex'       => $infos_deplacement, // Infos déplacements
                'fsbdd_daterchrge'            => sanitize_text_field($existing_cost['fsbdd_daterchrge'] ?? ''),
            ];
        }

        // Enregistrer les coûts mis à jour
        rwmb_set_meta($post_id, 'fsbdd_grpctsformation', $updated_costs);

        // ---------------------------------------------
        //   CALCUL DES TOTAUX GLOBAUX
        // ---------------------------------------------
        $sum_formateurs_cost    = 0.0;
        $sum_formateurs_frais   = 0.0;
        $sum_fournisseurs_cost  = 0.0;
        $sum_fournisseurs_frais = 0.0;

        foreach ($updated_costs as $uc) {
            if ($uc['fsbdd_typechargedue'] === '1') {
                // 1 = Formateur
                $sum_formateurs_cost  += ($uc['fsbdd_ttcout_journalier'] + $uc['fsbdd_ttcout_demijournalier']);
                $sum_formateurs_frais += $uc['fsbdd_ttfraismission'];
            } elseif ($uc['fsbdd_typechargedue'] === '2') {
                // 2 = Fournisseur/Salle
                $sum_fournisseurs_cost  += ($uc['fsbdd_ttcout_journalier'] + $uc['fsbdd_ttcout_demijournalier']);
                $sum_fournisseurs_frais += $uc['fsbdd_ttfraismission'];
            }
        }

        // Calcul du total global
        $total_final = $sum_formateurs_cost 
                     + $sum_formateurs_frais
                     + $sum_fournisseurs_cost
                     + $sum_fournisseurs_frais;

        // Enregistrer ces totaux
        update_post_meta($post_id, 'fsbdd_coutsformrs', $sum_formateurs_cost);
        update_post_meta($post_id, 'fsbdd_fraismission', $sum_formateurs_frais);
        update_post_meta($post_id, 'fsbdd_ttchrglogisti', $sum_fournisseurs_cost);
        update_post_meta($post_id, 'fsbdd_fraisfourni', $sum_fournisseurs_frais);
        update_post_meta($post_id, 'fsbdd_coutaction', $total_final);
    }
}

/**
 * Maintenant, on attache cette fonction à votre hook "rwmb_infos-sessions_after_save_post".
 * Ainsi, chaque fois qu'un post est sauvegardé depuis l'admin, si le post_type
 * correspond et que le post_id n'est pas 268081, on déclenche le calcul.
 */
add_action('rwmb_infos-sessions_after_save_post', function($post_id) {
    my_update_fsbdd_grpctsformation($post_id);
});
