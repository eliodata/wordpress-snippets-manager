<?php
/**
 * Snippet ID: 90
 * Name: HOOK synchronisation planning et fichiers cpt action de formation avec actions externes et maj depuis page planning
 * Description: 
 * @active false
 */


/**
 * Fonction pour gérer les mises à jour de la métadonnée 'fsbdd_planning'.
 *
 * @param int    $meta_id      ID de la métadonnée.
 * @param int    $post_id      ID du post.
 * @param string $meta_key     Clé de la métadonnée.
 * @param mixed  $_meta_value  Nouvelle valeur de la métadonnée.
 */
function handle_fsbdd_planning_update($meta_id, $post_id, $meta_key, $_meta_value) {
    // Vérifie si la clé méta est 'fsbdd_planning'
    if ($meta_key !== 'fsbdd_planning') {
        return;
    }

    // Récupère le post pour vérifier son type
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'action-de-formation') {
        return;
    }

    // Assurez-vous que les fonctions nécessaires sont définies
    if (!function_exists('update_fsbdd_etatemargm') || !function_exists('process_file_validation_action_de_formation')) {
        return;
    }

    // Appelle la fonction pour mettre à jour l'état des émargements
    update_fsbdd_etatemargm($post_id);

    // Appelle la fonction pour traiter la validation des fichiers
    process_file_validation_action_de_formation($post_id, $post, true);
}

add_action('updated_post_meta', 'handle_fsbdd_planning_update', 10, 4);
add_action('added_post_meta', 'handle_fsbdd_planning_update', 10, 4);

/**
 * Fonction pour mettre à jour les frais en fonction des modifications du planning.
 */
function update_fsbdd_etatemargm($post_id) {
    // Votre logique existante pour mettre à jour l'état des émargements
    // (Assurez-vous qu'elle est correctement définie et fonctionne indépendamment de 'save_post')
}

/**
 * Fonction pour traiter la validation des fichiers.
 *
 * @param int    $post_id ID du post.
 * @param object $post    Objet du post.
 * @param bool   $update  Indique si le post est mis à jour.
 */
function process_file_validation_action_de_formation($post_id, $post, $update) {
    // Votre logique existante pour traiter la validation des fichiers
    // (Assurez-vous qu'elle peut être appelée indépendamment de 'save_post')
}

/**
 * Exemple de fonction personnalisée pour modifier le planning.
 *
 * @param int    $action_id       ID de l'action de formation.
 * @param string $original_date   Date originale du planning.
 * @param string $original_type   Type original ('formateur' ou 'fournisseur').
 * @param int    $original_nom    ID original du formateur/fournisseur.
 * @param int    $new_nom         Nouvel ID du formateur/fournisseur.
 * @param string $new_dispo       Nouvelle disponibilité.
 * @param string $new_etat        Nouvel état.
 * @param string $new_date        Nouvelle date (facultative).
 */
function update_fsbdd_planning_entry($action_id, $original_date, $original_type, $original_nom, $new_nom, $new_dispo, $new_etat, $new_date = null) {
    // Récupère les plannings existants
    $existing_plannings = get_post_meta($action_id, 'fsbdd_planning', true);
    if (empty($existing_plannings) || !is_array($existing_plannings)) {
        $existing_plannings = [];
    }

    $found = false;
    foreach ($existing_plannings as &$entry) {
        if ($entry['fsbdd_planjour'] === $original_date) {
            if ($original_type === 'formateur') {
                foreach ($entry['fsbdd_gpformatr'] as &$formateur) {
                    if ($formateur['fsbdd_user_formateurrel'] == $original_nom) {
                        $formateur['fsbdd_user_formateurrel'] = $new_nom;
                        $formateur['fsbdd_dispjourform']      = $new_dispo;
                        $formateur['fsbdd_okformatr']         = $new_etat;

                        if ($new_date !== null && $new_date !== $original_date) {
                            $entry['fsbdd_planjour'] = $new_date;
                        }

                        $found = true;

                        $action_title = get_the_title($action_id);
                        $confirmations[$action_title][] = __('Modification pour le formateur', 'your-text-domain') . ' ' . get_post_field('post_title', $new_nom) . ' ' . __('le', 'your-text-domain') . ' ' . $new_date;
                        break 2;
                    }
                }
            } elseif ($original_type === 'fournisseur') {
                foreach ($entry['fournisseur_salle'] as &$salle) {
                    if ($salle['fsbdd_user_foursalle'] == $original_nom) {
                        $salle['fsbdd_user_foursalle'] = $new_nom;
                        $salle['fsbdd_dispjourform']   = $new_dispo;
                        $salle['fsbdd_okformatr']      = $new_etat;

                        if ($new_date !== null && $new_date !== $original_date) {
                            $entry['fsbdd_planjour'] = $new_date;
                        }

                        $found = true;

                        $action_title = get_the_title($action_id);
                        $confirmations[$action_title][] = __('Modification pour le fournisseur/salle', 'your-text-domain') . ' ' . get_post_field('post_title', $new_nom) . ' ' . __('le', 'your-text-domain') . ' ' . $new_date;
                        break 2;
                    }
                }
            }
        }
    }

    if (!$found) {
        // Optionnel : gérer le cas où l'entrée n'a pas été trouvée
    }

    // Met à jour la métadonnée 'fsbdd_planning' avec les modifications
    update_post_meta($action_id, 'fsbdd_planning', $existing_plannings);
}
