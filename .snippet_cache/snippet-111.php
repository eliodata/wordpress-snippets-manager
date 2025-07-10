<?php
/**
 * Snippet ID: 111
 * Name: mise a jour numeros actions depuis titres
 * Description: 
 * @active false
 */


function remplir_fsbdd_inter_numero() {
    // Vérifie si le script a déjà été exécuté
    if ( get_option( 'fsbdd_inter_numero_executed' ) ) {
        return; // Le script a déjà été exécuté, sortir de la fonction
    }

    // Arguments pour récupérer tous les posts 'action-de-formation'
    $args = array(
        'post_type'      => 'action-de-formation',
        'posts_per_page' => -1, // Récupère tous les posts
        'post_status'    => 'any', // Inclut tous les statuts
        'fields'         => 'ids', // Récupère uniquement les IDs pour optimiser
    );

    $post_ids = get_posts( $args );

    if ( empty( $post_ids ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-warning is-dismissible">
                    <p>Aucun post trouvé pour le type "action-de-formation".</p>
                  </div>';
        });
        return; // Aucun post trouvé, sortir de la fonction
    }

    $updated_count = 0; // Compteur des mises à jour

    foreach ( $post_ids as $post_id ) {
        // Récupère la valeur actuelle du champ personnalisé
        $inter_numero = get_post_meta( $post_id, 'fsbdd_inter_numero', true );

        // Vérifie si le champ est vide
        if ( empty( $inter_numero ) ) {
            // Récupère le titre du post
            $post_title = get_the_title( $post_id );

            // Met à jour le champ personnalisé avec le titre du post
            update_post_meta( $post_id, 'fsbdd_inter_numero', $post_title );

            $updated_count++;
        }
    }

    // Enregistre que le script a été exécuté
    update_option( 'fsbdd_inter_numero_executed', true );

    // Affiche une notice dans l'admin
    add_action( 'admin_notices', function() use ( $updated_count ) {
        if ( $updated_count > 0 ) {
            echo '<div class="notice notice-success is-dismissible">
                    <p>' . esc_html( $updated_count ) . ' champ(s) "fsbdd_inter_numero" mis à jour avec le titre du post.</p>
                  </div>';
        } else {
            echo '<div class="notice notice-info is-dismissible">
                    <p>Aucun champ "fsbdd_inter_numero" n\'a été mis à jour.</p>
                  </div>';
        }
    });

    // Optionnel : Enregistrer dans le journal de débogage
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( 'Script rempli_fsbdd_inter_numero exécuté. ' . $updated_count . ' champs mis à jour.' );
    }
}

// Ajoute une action pour exécuter la fonction une fois au chargement de l'admin
add_action( 'admin_init', 'remplir_fsbdd_inter_numero' );
