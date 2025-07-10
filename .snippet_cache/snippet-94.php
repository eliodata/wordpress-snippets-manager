<?php
/**
 * Snippet ID: 94
 * Name: test save post mise a jour planning action
 * Description: 
 * @active false
 */

add_action('save_post', 'update_group_costs_on_save', 10, 3);

function update_group_costs_on_save($post_id, $post, $update) {
    // Vérifiez le type de post
    if ($post->post_type !== 'action-de-formation') {
        return;
    }

    // Vérifiez les autorisations de l'utilisateur
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Empêche les boucles infinies
    remove_action('save_post', 'update_group_costs_on_save');

    // Récupère les données du planning
    $planning = rwmb_meta('fsbdd_planning', ['object_id' => $post_id]);

    // Récupère les données existantes du groupe de coûts
    $existing_costs = rwmb_meta('fsbdd_grpctsformation', ['object_id' => $post_id]) ?: [];
    $updated_costs = [];

    if (!empty($planning)) {
        // Stockage des disponibilités par formateur/fournisseur
        $availability_totals = [];

        foreach ($planning as $day) {
            // Gestion des formateurs dans le planning
            if (!empty($day['fsbdd_gpformatr'])) {
                foreach ($day['fsbdd_gpformatr'] as $formateur) {
                    $formateur_id = $formateur['fsbdd_user_formateurrel'] ?? null;
                    $dispo = $formateur['fsbdd_dispjourform'] ?? null;

                    if ($formateur_id && $dispo) {
                        // Déterminer le type de journée et ajuster le compteur
                        $type_journee = strtolower($dispo);
                        if ($type_journee === 'journée') {
                            $count = 1;
                        } elseif (in_array($type_journee, ['am', 'matin'])) {
                            $count = 1; // Utilisation directe du coût demi-journée
                        } else {
                            $count = 0; // Valeur par défaut si type inconnu
                        }

                        $availability_totals[$formateur_id]['type'] = '1'; // Formateur
                        $availability_totals[$formateur_id]['counts'][] = [
                            'type_journee' => $dispo,
                            'count' => $count,
                        ];
                    }
                }
            }

            // Gestion des fournisseurs/salles dans le planning
            if (!empty($day['fournisseur_salle'])) {
                foreach ($day['fournisseur_salle'] as $fournisseur) {
                    $fournisseur_id = $fournisseur['fsbdd_user_foursalle'] ?? null;
                    $dispo = $fournisseur['fsbdd_dispjourform'] ?? null;

                    if ($fournisseur_id && $dispo) {
                        // Déterminer le type de journée et ajuster le compteur
                        $type_journee = strtolower($dispo);
                        if ($type_journee === 'journée') {
                            $count = 1;
                        } elseif (in_array($type_journee, ['am', 'matin'])) {
                            $count = 1; // Utilisation directe du coût demi-journée
                        } else {
                            $count = 0; // Valeur par défaut si type inconnu
                        }

                        $availability_totals[$fournisseur_id]['type'] = '2'; // Fournisseur/Salle
                        $availability_totals[$fournisseur_id]['counts'][] = [
                            'type_journee' => $dispo,
                            'count' => $count,
                        ];
                    }
                }
            }
        }

        // Calcul des coûts et mise à jour des lignes
        foreach ($availability_totals as $id => $data) {
            $meta_key_coutjour = 'fsform_number_coutjour'; // Clé pour récupérer le coût journalier
            $meta_key_coutdemijour = 'fsform_number_coutdemijour'; // Clé pour récupérer le coût demi-journée
            $meta_key_deplacemts = 'fsform_text_deplacemts'; // Clé pour récupérer les informations de déplacement

            // Récupérer les valeurs depuis le CPT formateur ou fournisseur
            $cout_journalier = floatval(get_post_meta($id, $meta_key_coutjour, true)) ?: 0;
            $cout_demijournalier = floatval(get_post_meta($id, $meta_key_coutdemijour, true)) ?: 0;

            // Récupérer les informations de déplacement depuis le CPT formateur ou fournisseur
            $infos_deplacement = sanitize_text_field(get_post_meta($id, $meta_key_deplacemts, true)) ?: '';

            // Vérifier si une valeur existe déjà dans le champ correspondant
            $existing_cost = array_filter($existing_costs, function ($cost) use ($id, $data) {
                return $cost['fsbdd_typechargedue'] === $data['type'] && (
                    ($data['type'] === '1' && $cost['fsbdd_selectcoutform'] == $id) || // Formateur
                    ($data['type'] === '2' && $cost['fsbdd_selectctfourn'] == $id)    // Fournisseur
                );
            });

            $existing_cost = $existing_cost ? reset($existing_cost) : [];

            // Pré-remplir fsbdd_coutdemijourf si vide
            if (isset($existing_cost['fsbdd_coutdemijourf']) && $existing_cost['fsbdd_coutdemijourf'] !== '') {
                $cout_demijournalier = floatval($existing_cost['fsbdd_coutdemijourf']);
            } else {
                $cout_demijournalier = $cout_demijournalier ?: 0;
            }

            // Récupérer les frais de mission si déjà définis
            $fraismission = floatval($existing_cost['fsbdd_typechrgfrmiss'] ?? 0);

            // Initialiser les totaux
            $total_cout_journalier = 0;
            $total_cout_demijournalier = 0;
            $total_quantite_coutjour = 0;
            $total_quantite_coutdemijour = 0;
            $total_quantite_fraismission = 0;

            // Parcourir les counts pour calculer les totaux
            foreach ($data['counts'] as $count_data) {
                if (strtolower($count_data['type_journee']) === 'journée') {
                    $total_cout_journalier += $cout_journalier * $count_data['count'];
                    $total_quantite_coutjour += $count_data['count'];
                } elseif (in_array(strtolower($count_data['type_journee']), ['am', 'matin'])) {
                    $total_cout_demijournalier += $cout_demijournalier * $count_data['count'];
                    $total_quantite_coutdemijour += $count_data['count'];
                }
                // Frais de mission sont comptés par occurrence
                $total_quantite_fraismission += $count_data['count'];
            }

            // Calculer le total des coûts
            $total_cout = $total_cout_journalier + $total_cout_demijournalier + ($fraismission * $total_quantite_fraismission);

            // Ajouter ou mettre à jour la ligne
            $updated_costs[] = [
                'fsbdd_typechargedue' => $data['type'], // 1 = Formateur, 2 = Fournisseur
                'fsbdd_selectcoutform' => $data['type'] === '1' ? $id : '',
                'fsbdd_selectctfourn' => $data['type'] === '2' ? $id : '',
                'fsbdd_coutjourf' => $cout_journalier, // Préserve la valeur existante si déjà définie
                'fsbdd_qtitectjour' => $total_quantite_coutjour, // Quantité Journée
                'fsbdd_coutdemijourf' => $cout_demijournalier, // Pré-rempli
                'fsbdd_qtitectdemijour' => $total_quantite_coutdemijour, // Quantité Demi Journée
                'fsbdd_fraismission' => $fraismission,
                'fsbdd_qtitefrannex' => $total_quantite_fraismission, // Quantité Frais Annexes
                'fsbdd_typechrgfrmiss' => $fraismission,
                'fsbdd_montrechrge' => $total_cout, // Montant total calculé
                'fsbdd_ttcout_journalier' => $total_cout_journalier, // Enregistre le total coût journalier
                'fsbdd_ttcout_demijournalier' => $total_cout_demijournalier, // Enregistre le total coût demi-journalier
                'fsbdd_ttfraismission' => $fraismission * $total_quantite_fraismission, // Enregistre le total frais de mission
                'fsbdd_infoschargedue' => sanitize_text_field($existing_cost['fsbdd_infoschargedue'] ?? ''),
                'fsbdd_infosfraisannex' => $infos_deplacement, // Prérempli avec les métadonnées
                'fsbdd_daterchrge' => sanitize_text_field($existing_cost['fsbdd_daterchrge'] ?? ''),
            ];
        }

        // Enregistrer les coûts mis à jour dans le champ sérialisé
        rwmb_set_meta($post_id, 'fsbdd_grpctsformation', $updated_costs);
    }

    // Réattache le hook
    add_action('save_post', 'update_group_costs_on_save', 10, 3);
}
