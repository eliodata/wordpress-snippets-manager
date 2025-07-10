<?php
/**
 * Snippet ID: 106
 * Name: NUMEROTATION AUTO TITRES ACTIONS DE FORMATION
 * Description: 
 * @active true
 */

// Fonction principale pour renommer automatiquement le post et remplir fsbdd_inter_numero
add_action('save_post', 'af_rename_post_automatiquement', 20, 3);
function af_rename_post_automatiquement($post_id, $post, $update) {

    // Éviter les auto-saves, révisions, etc.
    if ( wp_is_post_autosave($post_id) 
         || wp_is_post_revision($post_id)
         || $post->post_type !== 'action-de-formation' ) {
        return;
    }

    // Récupérer le champ personnalisé 'fsbdd_inter_numero'
    $fsbdd_inter_numero = get_post_meta($post_id, 'fsbdd_inter_numero', true);

    // Si 'fsbdd_inter_numero' est déjà défini, ne pas modifier le titre et le slug
    if ( !empty($fsbdd_inter_numero) ) {
        error_log("af_rename_post_automatiquement: 'fsbdd_inter_numero' déjà défini pour le post ID $post_id. Aucune modification effectuée.");
        return;
    }

    // Récupérer la date du champ personnalisé 'we_startdate'
    $start_timestamp = get_post_meta($post_id, 'we_startdate', true);

    // Si on n’a pas de timestamp, on ne fait rien
    if ( empty($start_timestamp) || !is_numeric($start_timestamp) ) {
        error_log("af_rename_post_automatiquement: 'we_startdate' manquant ou invalide pour le post ID $post_id");
        return;
    }

    // Convertir le timestamp en date
    $jour  = date('d', $start_timestamp);
    $mois  = date('m', $start_timestamp);
    $annee = date('y', $start_timestamp); // Deux derniers chiffres de l'année

    // Construire la base du slug : yymmdd
    $base_slug = $annee . $mois . $jour; // Exemple: "250122" pour le 22.01.2025

    // Initialiser le compteur
    $counter = 1;
    $new_slug = '';

    // Boucler jusqu'à trouver un slug unique
    do {
        $suffix   = sprintf('%02d', $counter); // Formate le compteur sur 2 chiffres (01, 02, ...)
        $new_slug = $base_slug . '-' . $suffix; // Ajout du tiret entre la date et le compteur, par exemple: "250122-01"
        $counter++;
    } while ( af_slug_exists($new_slug, $post_id) );

    error_log("af_rename_post_automatiquement: Nouveau slug généré pour le post ID $post_id est $new_slug");

    // Mettre à jour le titre, le slug et 'fsbdd_inter_numero' uniquement si nécessaire
    if ( $post->post_title !== $new_slug || $post->post_name !== $new_slug || $fsbdd_inter_numero !== $new_slug ) {

        // Désactiver temporairement l'action pour éviter les boucles
        remove_action('save_post', 'af_rename_post_automatiquement', 20);

        // Mettre à jour le post avec le nouveau titre et slug
        $update_result = wp_update_post([
            'ID'         => $post_id,
            'post_title' => $new_slug,  // Nouveau Titre
            'post_name'  => $new_slug,  // Nouveau Slug
        ], true);

        if ( is_wp_error($update_result) ) {
            error_log("af_rename_post_automatiquement: Erreur lors de la mise à jour du post ID $post_id: " . $update_result->get_error_message());
        } else {
            error_log("af_rename_post_automatiquement: Post ID $post_id mis à jour avec le slug $new_slug");
        }

        // Mettre à jour le champ personnalisé 'fsbdd_inter_numero'
        update_post_meta($post_id, 'fsbdd_inter_numero', $new_slug);
        error_log("af_rename_post_automatiquement: 'fsbdd_inter_numero' mis à jour pour le post ID $post_id avec la valeur $new_slug");

        // Réactiver l'action
        add_action('save_post', 'af_rename_post_automatiquement', 20, 3);
    }
}

// Fonction pour vérifier l'existence d'un slug
function af_slug_exists($slug, $current_post_id = 0) {
    // Vérifier l'existence via le titre
    $post_id_title = post_exists($slug);
    if ( $post_id_title && $post_id_title != $current_post_id ) {
        return true;
    }

    // Vérifier l'existence via le slug
    global $wpdb;
    $post_id_slug = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND ID != %d AND post_status NOT IN ('trash', 'auto-draft')",
        $slug,
        $current_post_id
    ));
    if ( $post_id_slug ) {
        return true;
    }

    return false;
}
