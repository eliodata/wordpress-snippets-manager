<?php
/**
 * Snippet ID: 19
 * Name: Renommer documents formateurs importés depuis commandes
 * Description: 
 * @active false
 */

function my_unique_filename_callback_emargements( $dir, $name, $ext ) {
    $order_id = get_the_ID(); // ID de la commande en cours

    // Récupérer l'ID du formateur sélectionné dans le champ "fsbdd_formatdocs"
    $formateur_id = rwmb_meta( 'fsbdd_formatdocs', ['object_type' => 'post'], $order_id );

    // Si un formateur est sélectionné, obtenir le titre et extraire le premier mot
    if ( $formateur_id ) {
        $formateur_title = get_the_title( $formateur_id );
        $formateur_name = strtok( $formateur_title, ' ' ); // Prend le premier mot du titre
    } else {
        $formateur_name = 'inconnu'; // Valeur par défaut si aucun formateur n'est sélectionné
    }

    // Créer un sous-dossier avec le nom de l'ID de la commande dans le dossier des uploads
    $order_dir = $dir . '/' . $order_id;
    if ( ! file_exists( $order_dir ) ) {
        mkdir( $order_dir, 0755, true ); // Créer le dossier avec les permissions appropriées
    }

    // Compter le nombre de fichiers existants pour cette commande et le formateur dans le sous-dossier
    $files = glob( $order_dir . '/emargements-*-' . $formateur_name . '-' . $order_id . $ext );
    $count = count( $files ) + 1;

    // Générer le nouveau nom de fichier dans le sous-dossier au format souhaité
    $new_name = $order_id . '/emargements-' . $count . '-' . $formateur_name . '-' . $order_id . $ext;
    return $new_name;
}
