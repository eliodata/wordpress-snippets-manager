<?php
/**
 * Snippet ID: 91
 * Name: HOOK MISE A JOUR cpt action de formation si modif dans le planning
 * Description: 
 * @active false
 */

/**
 * Force la création d'une révision lorsque le champ 'fsbdd_planning' est mis à jour et que sa valeur change réellement.
 *
 * @param int    $meta_id    ID de la méta.
 * @param int    $post_id    ID du post.
 * @param string $meta_key   Clé de la méta.
 * @param mixed  $meta_value Valeur de la méta.
 */
function your_prefix_force_revision_on_fsbdd_planning_update( $meta_id, $post_id, $meta_key, $meta_value ) {
    if ( 'fsbdd_planning' !== $meta_key ) {
        return;
    }

    $post_type = get_post_type( $post_id );
    if ( 'action-de-formation' !== $post_type ) {
        return;
    }

    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    // Récupère l'ancienne valeur de la méta
    $old_value = get_metadata( 'post', $post_id, $meta_key, true );

    // Compare les anciennes et nouvelles valeurs
    // Utilise une comparaison stricte pour les tableaux
    if ( is_array( $old_value ) && is_array( $meta_value ) ) {
        if ( serialize( $old_value ) === serialize( $meta_value ) ) {
            // Si aucune modification réelle, ne crée pas de révision
            return;
        }
    } elseif ( $old_value === $meta_value ) {
        // Pour les autres types de données
        return;
    }

    // Log pour débogage
    error_log( "fsbdd_planning modifié pour post ID: $post_id. Création d'une révision." );

    // Crée une révision
    wp_save_post_revision( $post_id );
}
add_action( 'updated_post_meta', 'your_prefix_force_revision_on_fsbdd_planning_update', 10, 4 );
add_action( 'added_post_meta', 'your_prefix_force_revision_on_fsbdd_planning_update', 10, 4 );
