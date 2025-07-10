<?php
/**
 * Snippet ID: 113
 * Name: SAISIE AUTO COUTS charges FORMATEURS FOURNISSEURS depuis planning metabox action de formation V2
 * Description: 
 * @active true
 */

/**
 * Synchronisation du tableau de charges / coûts de formation avec le planning
 * CORRIGÉ pour créer une ligne par combinaison fournisseur + produit
 *
 * @param int     $post_id   ID du post à synchroniser.
 * @param bool    $force_sync Force la synchronisation même si le post est un autosave ou une révision. Par défaut : false.
 */
function sync_formation_planning_costs( $post_id, $force_sync = false ) {
    // 1. Éviter la récursion ou l'auto-sauvegarde, sauf si $force_sync est vrai
    if ( ! $force_sync && ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) ) {
        return;
    }

    // 2. Exclure tout de suite le post_id 268081
    if ( $post_id == 268081 ) {
        return;
    }

    // 3. Vérifie si le post est du type 'action-de-formation'
    if ( get_post_type( $post_id ) !== 'action-de-formation' ) {
        return;
    }

    // -------------------------------------------------------------------------
    // À partir d'ici : on récupère les données et on fait la synchronisation
    // -------------------------------------------------------------------------

    // Récupère les données du planning
    $planning = rwmb_meta( 'fsbdd_planning', [ 'object_id' => $post_id ] );

    // Récupère les données existantes du groupe de coûts
    $existing_costs = rwmb_meta( 'fsbdd_grpctsformation', [ 'object_id' => $post_id ] ) ?: [];
    $updated_costs  = [];

    if ( ! empty( $planning ) ) {
        // Stockage des disponibilités par formateur/fournisseur
        // MODIFIÉ : Clé unique par combinaison entité + produit
        $availability_totals = [];

        foreach ( $planning as $day ) {
            // Gestion des formateurs dans le planning
            if ( ! empty( $day['fsbdd_gpformatr'] ) ) {
                foreach ( $day['fsbdd_gpformatr'] as $formateur ) {
                    $formateur_id = $formateur['fsbdd_user_formateurrel'] ?? null;
                    $dispo        = $formateur['fsbdd_dispjourform'] ?? null;

                    if ( $formateur_id && $dispo ) {
                        $type_journee = strtolower( $dispo );
                        if ( $type_journee === 'journ' ) {
                            $count = 1;
                        } elseif ( in_array( $type_journee, [ 'aprem', 'matin' ], true ) ) {
                            $count = 1;
                        } else {
                            $count = 0;
                        }

                        // Pour les formateurs, on garde la logique existante (pas de produit)
                        $unique_key = 'formateur_' . $formateur_id;

                        $availability_totals[ $unique_key ]['type'] = '1'; // Formateur
                        $availability_totals[ $unique_key ]['entity_id'] = $formateur_id;
                        $availability_totals[ $unique_key ]['counts'][] = [
                            'type_journee' => $dispo,
                            'count'        => $count,
                        ];
                    }
                }
            }

            // Gestion des fournisseurs/salles dans le planning
            if ( ! empty( $day['fournisseur_salle'] ) ) {
                foreach ( $day['fournisseur_salle'] as $fournisseur ) {
                    $fournisseur_id = $fournisseur['fsbdd_user_foursalle'] ?? null;
                    $dispo          = $fournisseur['fsbdd_dispjourform'] ?? null;

                    if ( $fournisseur_id && $dispo ) {
                        $type_journee = strtolower( $dispo );
                        if ( $type_journee === 'journ' ) {
                            $count = 1;
                        } elseif ( in_array( $type_journee, [ 'aprem', 'matin' ], true ) ) {
                            $count = 1;
                        } else {
                            $count = 0;
                        }

                        // Récupérer les données du produit sélectionné
                        $selected_product_id = sanitize_text_field( $fournisseur['fsbdd_selected_product_id'] ?? '' );
                        // Normaliser le prix : remplacer virgule par point avant conversion
                        $price_raw = $fournisseur['fsbdd_selected_product_price'] ?? 0;
                        $price_normalized = str_replace(',', '.', $price_raw);
                        $selected_product_price = floatval( $price_normalized );
                        $selected_product_name = sanitize_text_field( $fournisseur['fsbdd_selected_product_name'] ?? '' );

                        // NOUVEAU : Créer une clé unique par combinaison fournisseur + produit
                        $product_key = ! empty( $selected_product_id ) ? $selected_product_id : 'no_product';
                        $unique_key = 'fournisseur_' . $fournisseur_id . '_product_' . $product_key;

                        $availability_totals[ $unique_key ]['type'] = '2'; // Fournisseur
                        $availability_totals[ $unique_key ]['entity_id'] = $fournisseur_id;
                        $availability_totals[ $unique_key ]['product_id'] = $selected_product_id;
                        $availability_totals[ $unique_key ]['product_price'] = $selected_product_price;
                        $availability_totals[ $unique_key ]['product_name'] = $selected_product_name;
                        $availability_totals[ $unique_key ]['counts'][] = [
                            'type_journee' => $dispo,
                            'count'        => $count,
                        ];
                    }
                }
            }
        }

        // Calcul des coûts et mise à jour des lignes
        foreach ( $availability_totals as $unique_key => $data ) {
            $entity_id = $data['entity_id'];

            if ( $data['type'] === '1' ) {
                // FORMATEURS : Logique existante (2 coûts distincts)
                $meta_key_coutjour     = 'fsform_number_coutjour';
                $meta_key_coutdemijour = 'fsform_number_coutdemijour';
                $meta_key_deplacemts   = 'fsform_text_deplacemts';

                $cout_journalier    = floatval( get_post_meta( $entity_id, $meta_key_coutjour, true ) ) ?: 0;
                $cout_demijournalier = floatval( get_post_meta( $entity_id, $meta_key_coutdemijour, true ) ) ?: 0;
                $infos_deplacement   = sanitize_text_field( get_post_meta( $entity_id, $meta_key_deplacemts, true ) ) ?: '';
                
            } else {
                // FOURNISSEURS : Logique avec prix du produit sélectionné
                $product_price = $data['product_price'] ?? 0;
                $product_name = $data['product_name'] ?? '';
                
                // Pour les fournisseurs : 1 prix unique, divisé par 2 pour les demi-journées
                $cout_journalier = $product_price;           // Prix complet pour journée
                $cout_demijournalier = $product_price / 2;   // Prix ÷ 2 pour demi-journée
                
                // Récupérer les infos de déplacement du fournisseur
                $meta_key_deplacemts = 'fsform_text_deplacemts';
                $infos_deplacement = sanitize_text_field( get_post_meta( $entity_id, $meta_key_deplacemts, true ) ) ?: '';
                
                // Si aucun produit sélectionné, fallback sur les anciennes métas
                if ( $product_price <= 0 ) {
                    $cout_journalier = floatval( get_post_meta( $entity_id, 'fsform_number_coutjour', true ) ) ?: 0;
                    $cout_demijournalier = floatval( get_post_meta( $entity_id, 'fsform_number_coutdemijour', true ) ) ?: 0;
                }
            }

            // MODIFIÉ : Recherche d'une ligne existante pour cette combinaison spécifique
            $matching_existing_costs = array_filter( $existing_costs, function ( $cost ) use ( $entity_id, $data ) {
                if ( $data['type'] === '1' ) {
                    // Formateur : match par ID formateur
                    return $cost['fsbdd_typechargedue'] === '1' && $cost['fsbdd_selectcoutform'] == $entity_id;
                } else {
                    // Fournisseur : match par ID fournisseur ET nom du produit
                    $existing_product_info = $cost['fsbdd_infoschargedue'] ?? '';
                    $current_product_name = $data['product_name'] ?? '';
                    
                    return $cost['fsbdd_typechargedue'] === '2' 
                           && $cost['fsbdd_selectctfourn'] == $entity_id
                           && ( empty( $current_product_name ) || strpos( $existing_product_info, $current_product_name ) !== false );
                }
            } );

            $existing_cost = $matching_existing_costs ? reset( $matching_existing_costs ) : [];

            // Préserver la valeur manuelle si elle existe (priorité sur les valeurs automatiques)
            if ( ! empty( $existing_cost['fsbdd_coutjourf'] ) ) {
                $cout_journalier = floatval( $existing_cost['fsbdd_coutjourf'] );
            }
            if ( ! empty( $existing_cost['fsbdd_coutdemijourf'] ) ) {
                $cout_demijournalier = floatval( $existing_cost['fsbdd_coutdemijourf'] );
            }

            // Récupérer les frais de mission s'ils sont déjà définis
            $fraismission = floatval( $existing_cost['fsbdd_typechrgfrmiss'] ?? 0 );

            // Initialiser les totaux
            $total_cout_journalier      = 0;
            $total_cout_demijournalier  = 0;
            $total_quantite_coutjour    = 0;
            $total_quantite_coutdemijour = 0;
            $total_quantite_fraismission = 0;

            foreach ( $data['counts'] as $count_data ) {
                $journee = strtolower( $count_data['type_journee'] );
                if ( $journee === 'journ' ) {
                    $total_cout_journalier   += $cout_journalier * $count_data['count'];
                    $total_quantite_coutjour += $count_data['count'];
                } elseif ( in_array( $journee, [ 'aprem', 'matin' ], true ) ) {
                    $total_cout_demijournalier   += $cout_demijournalier * $count_data['count'];
                    $total_quantite_coutdemijour += $count_data['count'];
                }
                // Frais de mission = 1 occurrence par journée/demi-journée
                $total_quantite_fraismission += $count_data['count'];
            }

            // Calculer le total
            $total_cout = $total_cout_journalier
                          + $total_cout_demijournalier
                          + ( $fraismission * $total_quantite_fraismission );

            // NOUVEAU : Construire les infos avec le nom du produit pour les fournisseurs
            $info_charge = sanitize_text_field( $existing_cost['fsbdd_infoschargedue'] ?? '' );
            if ( $data['type'] === '2' && ! empty( $data['product_name'] ) ) {
                // Pour les fournisseurs, afficher : "Nom du produit"
                $info_charge = $data['product_name'];
            }

            // Ajouter/mettre à jour la ligne
            $updated_costs[] = [
                'fsbdd_typechargedue'        => $data['type'], // 1 = Formateur, 2 = Fournisseur
                'fsbdd_selectcoutform'       => $data['type'] === '1' ? $entity_id : '',
                'fsbdd_selectctfourn'        => $data['type'] === '2' ? $entity_id : '',
                'fsbdd_coutjourf'            => $cout_journalier,
                'fsbdd_qtitectjour'          => $total_quantite_coutjour,
                'fsbdd_coutdemijourf'        => $cout_demijournalier,
                'fsbdd_qtitectdemijour'      => $total_quantite_coutdemijour,
                'fsbdd_fraismission'         => $fraismission,
                'fsbdd_qtitefrannex'         => $total_quantite_fraismission,
                'fsbdd_typechrgfrmiss'       => $fraismission,
                'fsbdd_montrechrge'          => $total_cout,
                'fsbdd_ttcout_journalier'    => $total_cout_journalier,
                'fsbdd_ttcout_demijournalier' => $total_cout_demijournalier,
                'fsbdd_ttfraismission'       => $fraismission * $total_quantite_fraismission,
                'fsbdd_infoschargedue'       => $info_charge,
                'fsbdd_infosfraisannex'      => $infos_deplacement,
                'fsbdd_daterchrge'           => sanitize_text_field( $existing_cost['fsbdd_daterchrge'] ?? '' ),
            ];
        }

        // Enregistrer les coûts mis à jour
        rwmb_set_meta( $post_id, 'fsbdd_grpctsformation', $updated_costs );

        // ---------------------------------------------------------------------
        // Calcul des totaux globaux et enregistrement
        // ---------------------------------------------------------------------
        $sum_formateurs_cost   = 0.0;
        $sum_formateurs_frais  = 0.0;
        $sum_fournisseurs_cost = 0.0;
        $sum_fournisseurs_frais = 0.0;

        foreach ( $updated_costs as $uc ) {
            if ( $uc['fsbdd_typechargedue'] === '1' ) {
                $sum_formateurs_cost  += ( $uc['fsbdd_ttcout_journalier'] + $uc['fsbdd_ttcout_demijournalier'] );
                $sum_formateurs_frais += $uc['fsbdd_ttfraismission'];
            } elseif ( $uc['fsbdd_typechargedue'] === '2' ) {
                $sum_fournisseurs_cost  += ( $uc['fsbdd_ttcout_journalier'] + $uc['fsbdd_ttcout_demijournalier'] );
                $sum_fournisseurs_frais += $uc['fsbdd_ttfraismission'];
            }
        }

        $total_final = $sum_formateurs_cost
                       + $sum_formateurs_frais
                       + $sum_fournisseurs_cost
                       + $sum_fournisseurs_frais;

        update_post_meta( $post_id, 'fsbdd_coutsformrs',   $sum_formateurs_cost );
        update_post_meta( $post_id, 'fsbdd_fraismission',   $sum_formateurs_frais );
        update_post_meta( $post_id, 'fsbdd_ttchrglogisti', $sum_fournisseurs_cost );
        update_post_meta( $post_id, 'fsbdd_fraisfourni',   $sum_fournisseurs_frais );
        update_post_meta( $post_id, 'fsbdd_coutaction',    $total_final );
    }
}

/**
 * Hooks pour déclencher la synchronisation.
 */
add_action( 'rwmb_infos-sessions_after_save_post', 'sync_formation_planning_costs', 10, 1 );
add_action( 'save_post_action-de-formation', 'sync_formation_planning_costs', 10, 1 );

/**
 * Exemple d'appel de la fonction depuis une autre fonction ou page.
 */
function ma_fonction_perso( $post_id ) {
    sync_formation_planning_costs( $post_id, true );
}