<?php
/**
 * Snippet ID: 112
 * Name: création date plannig auto pour les actions
 * Description: 
 * @active false
 */

function ajouter_journee_planning_vide() {
    $post_id = 267295;

    // Vérifie si le script a déjà été exécuté
    if ( get_option( 'ajouter_journee_planning_vide_executed_' . $post_id ) ) {
        return;
    }

    // Récupère le timestamp
    $timestamp = get_post_meta( $post_id, 'we_startdate', true );

    // Vérifie s'il est vide ou nul
    if ( empty( $timestamp ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible">
                    <p>Le champ "we_startdate" est vide ou invalide pour le post spécifié.</p>
                  </div>';
        });
        return;
    }

    // Convertit le timestamp en format 'd.m.Y'
    $date_formatee = date( 'd.m.Y', intval($timestamp) );

    // Crée une nouvelle journée de planning vide
    $nouvelle_journee = array(
        'fsbdd_planjour'       => $date_formatee,
        'fsbdd_plannmatin'     => '',
        'fsbdd_plannmatinfin'  => '',
        'fsbdd_plannam'        => '',
        'fsbdd_plannamfin'     => '',
        'fsbdd_gpformatr'      => array(),
        'fournisseur_salle'    => array(),
    );

    // Récupère le planning existant
    $fsbdd_planning = get_post_meta( $post_id, 'fsbdd_planning', true );

    // Initialise le tableau si c'est vide ou non un array
    if ( ! is_array( $fsbdd_planning ) ) {
        $fsbdd_planning = array();
    }

    // Ajoute la nouvelle journée
    $fsbdd_planning[] = $nouvelle_journee;

    // Met à jour la meta
    update_post_meta( $post_id, 'fsbdd_planning', $fsbdd_planning );

    // Marque le script comme exécuté
    update_option( 'ajouter_journee_planning_vide_executed_' . $post_id, true );

    // Affiche une notification
    add_action( 'admin_notices', function() use ( $date_formatee ) {
        echo '<div class="notice notice-success is-dismissible">
                <p>Une journée de planning vide a été ajoutée pour la date : ' . esc_html( $date_formatee ) . '.</p>
              </div>';
    });
}
add_action( 'admin_init', 'ajouter_journee_planning_vide' );
