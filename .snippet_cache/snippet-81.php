<?php
/**
 * Snippet ID: 81
 * Name: Enregistrer metas planning action vers cpt formateur et fournisseur
 * Description: 
 * @active false
 */

add_action('save_post', 'sync_planning_with_related_cpts', 20, 2);
function sync_planning_with_related_cpts($post_id, $post) {
    if ($post->post_type !== 'action-de-formation' || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
        return;
    }

    $planning_data = rwmb_meta('fsbdd_planning', [], $post_id);
    if (empty($planning_data) || !is_array($planning_data)) {
        return;
    }

    $formateurs_data = [];
    $salles_data = [];

    foreach ($planning_data as $day) {
        $date = $day['fsbdd_planjour'] ?? '';
        if (empty($date)) {
            continue;
        }

        // Formateurs
        if (!empty($day['fsbdd_gpformatr']) && is_array($day['fsbdd_gpformatr'])) {
            foreach ($day['fsbdd_gpformatr'] as $formateur) {
                $formateur_id = (int) ($formateur['fsbdd_user_formateurrel'] ?? 0);
                if ($formateur_id) {
                    $dispo = sanitize_text_field($formateur['fsbdd_dispjourform'] ?? '');
                    $etat = sanitize_text_field($formateur['fsbdd_okformatr'] ?? '');
                    $formateurs_data[$formateur_id][] = [
                        'date' => $date,
                        'dispo' => $dispo,
                        'etat' => $etat,
                        'action_id' => $post_id,
                    ];
                }
            }
        }

        // Salles
        if (!empty($day['fournisseur_salle']) && is_array($day['fournisseur_salle'])) {
            foreach ($day['fournisseur_salle'] as $salle) {
                $salle_id = (int) ($salle['fsbdd_user_foursalle'] ?? 0);
                if ($salle_id) {
                    $dispo = sanitize_text_field($salle['fsbdd_dispjourform'] ?? '');
                    $etat = sanitize_text_field($salle['fsbdd_okformatr'] ?? '');
                    $salles_data[$salle_id][] = [
                        'date' => $date,
                        'dispo' => $dispo,
                        'etat' => $etat,
                        'action_id' => $post_id,
                    ];
                }
            }
        }
    }

    // Maintenant, on récupère les métadonnées de l'action-de-formation
    $sessconfirm_arr = get_post_meta($post_id, 'fsbdd_sessconfirm', false);
    $sessconfirm_val = (is_array($sessconfirm_arr) && !empty($sessconfirm_arr[0])) ? $sessconfirm_arr[0] : '';

    $typesession_arr = get_post_meta($post_id, 'fsbdd_typesession', false);
    $typesession_val = (is_array($typesession_arr) && !empty($typesession_arr[0])) ? $typesession_arr[0] : '';

    $lieusession_arr = get_post_meta($post_id, 'fsbdd_select_lieusession', false);
    $lieusession_val = (is_array($lieusession_arr) && !empty($lieusession_arr[0])) ? $lieusession_arr[0] : '';

    $produits_arr = get_post_meta($post_id, 'fsbdd_relsessproduit', false);
    $produit_id_val = (is_array($produits_arr) && !empty($produits_arr[0])) ? (int)$produits_arr[0] : 0;

    // Injecter ces infos dans chaque entrée ayant un action_id
    foreach ($formateurs_data as $fid => &$entries) {
        foreach ($entries as &$e) {
            if (!empty($e['action_id'])) {
                $e['sessconfirm'] = $sessconfirm_val;
                $e['typesession'] = $typesession_val;
                $e['lieusession'] = $lieusession_val;
                $e['produit_id'] = $produit_id_val;
            }
        }
    }
    unset($entries);

    foreach ($salles_data as $sid => &$entries) {
        foreach ($entries as &$e) {
            if (!empty($e['action_id'])) {
                $e['sessconfirm'] = $sessconfirm_val;
                $e['typesession'] = $typesession_val;
                $e['lieusession'] = $lieusession_val;
                $e['produit_id'] = $produit_id_val;
            }
        }
    }
    unset($entries);

    if (!empty($formateurs_data)) {
        update_related_cpt('formateur', $formateurs_data);
    }

    if (!empty($salles_data)) {
        update_related_cpt('salle-de-formation', $salles_data);
    }
}

function update_related_cpt($cpt_type, $data) {
    foreach ($data as $cpt_id => $entries) {
        $existing_data = get_post_meta($cpt_id, 'fsbdd_planning_data', true) ?: [];

        // Récupérer les action_id présents dans les nouvelles entrées
        $new_action_ids = array_column($entries, 'action_id');

        // Filtrer les anciennes données pour supprimer toutes celles qui ont un action_id faisant partie des nouvelles
        $updated_data = array_filter($existing_data, function($entry) use ($new_action_ids) {
            if (!empty($entry['action_id']) && in_array($entry['action_id'], $new_action_ids)) {
                return false;
            }
            return true;
        });

        // Ajouter les nouvelles données
        $updated_data = array_merge($updated_data, $entries);

        // Récupérer la liste des dates ajoutées par l'action-de-formation
        $action_dates = [];
        foreach ($entries as $new_entry) {
            if (!empty($new_entry['action_id'])) {
                $action_dates[] = $new_entry['date'];
            }
        }
        $action_dates = array_unique($action_dates);

        // Supprimer toutes les stand-alone qui ont une date présente dans action_dates
        $updated_data = array_filter($updated_data, function($entry) use ($action_dates) {
            if (empty($entry['action_id']) && in_array($entry['date'], $action_dates)) {
                return false; 
            }
            return true;
        });

        // Nettoyer les doublons
        $unique_data = array_map('unserialize', array_unique(array_map('serialize', $updated_data)));

        update_post_meta($cpt_id, 'fsbdd_planning_data', $unique_data);
    }
}
