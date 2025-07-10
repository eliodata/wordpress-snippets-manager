<?php
/**
 * Snippet ID: 114
 * Name: appel hook syn saisie auto couts depuis planning global
 * Description: 
 * @active false
 */

/**
 * Synchronise les coûts de formation avec le planning lorsqu'une métadonnée 'fsbdd_planning' est mise à jour.
 *
 * @param int    $meta_id     ID of the meta data being updated.
 * @param int    $post_id     Post ID.
 * @param string $meta_key    Meta data key.
 * @param mixed  $meta_value  Meta data value. This will be a PHP-serialized string representation of the value
 *                            if the value is an array, an object, or itself a PHP-serialized string.
 */
add_action( 'updated_post_meta', 'sync_formation_planning_costs_on_meta_update', 10, 4 );

function sync_formation_planning_costs_on_meta_update( $meta_id, $post_id, $meta_key, $meta_value ) {
    // On vérifie si la métadonnée mise à jour est 'fsbdd_planning'
    if ( $meta_key === 'fsbdd_planning' ) {
        // On vérifie si le post est du type 'action-de-formation'
        if ( get_post_type( $post_id ) === 'action-de-formation' ) {
            // **Vérifier si la requête vient de la page de gestion des plannings**
            if ( defined('DOING_AJAX') && DOING_AJAX || isset($_POST['save_plannings']) || isset($_POST['delete_plannings']) || isset($_POST['add_planning']) ) {
                // On appelle la fonction de synchronisation en passant l'ID du post
                sync_formation_planning_costs( $post_id );
            }
        }
    }
}