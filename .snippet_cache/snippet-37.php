<?php
/**
 * Snippet ID: 37
 * Name: SAISIE AUTO DATES CHAMPS PLANNING action de formation
 * Description: 
 * @active false
 */

add_action('save_post_action-de-formation', 'initialize_planning_for_cpt', 10, 3);

function initialize_planning_for_cpt($post_id, $post, $update) {
    // Vérifier que c'est bien un enregistrement ou une mise à jour valide
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Vérifier que c'est bien le bon type de post
    if ($post->post_type !== 'action-de-formation') {
        return;
    }

    // Récupérer les métadonnées existantes pour le planning
    $planning = get_post_meta($post_id, 'fsbdd_planning', true);

    // Vérifier si le groupe est vide
    if (empty($planning)) {
        // Récupérer les dates de début et de fin
        $we_startdate = get_post_meta($post_id, 'we_startdate', true); // Timestamp
        $we_enddate = get_post_meta($post_id, 'we_enddate', true);     // Timestamp

        if (!empty($we_startdate) && !empty($we_enddate)) {
            $planning = []; // Initialisation du groupe

            // Convertir les timestamps en objets DateTime
            $start_date = new DateTime("@$we_startdate");
            $end_date = new DateTime("@$we_enddate");

            // Ajouter le premier jour uniquement avec les horaires
            $planning[] = [
                'fsbdd_planjour'    => $start_date->format('d-m-Y'), // Format : dd-mm-yyyy
                'fsbdd_plannmatin'  => '08:30',
                'fsbdd_plannmatinfin' => '12:00',
                'fsbdd_plannam'     => '13:30',
                'fsbdd_plannamfin'  => '17:00',
            ];

            // Mettre à jour la métadonnée avec le premier jour du planning
            update_post_meta($post_id, 'fsbdd_planning', $planning);
        }
    }
}
