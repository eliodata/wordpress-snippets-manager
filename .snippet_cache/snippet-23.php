<?php
/**
 * Snippet ID: 23
 * Name: Renommer et classer documents formateur importés depuis espace frontend page outils formateurs
 * Description: 
 * @active false
 */

function my_unique_filename_callback_frontend( $dir, $name, $ext ) {
    // Récupérer l'ID du produit sélectionné dans le champ 'fsbdd_selsessionformatr' (ID stocké dans le select)
    $selected_product_id = isset($_POST['fsbdd_selsessionformatr']) ? sanitize_text_field($_POST['fsbdd_selsessionformatr']) : 'inconnu';

    // Récupérer l'ID du formateur lié à l'utilisateur connecté via la relation 'formateur-bdd-formateur-wp'
    $current_user_id = get_current_user_id();
    global $wpdb;
    $formateur_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT `from` FROM {$wpdb->prefix}mb_relationships WHERE `to` = %d AND `type` = 'formateur-bdd-formateur-wp'",
            $current_user_id
        )
    );

    // Si un formateur est trouvé, obtenir son nom (premier mot du titre), sinon 'inconnu'
    if ( $formateur_id ) {
        $formateur_title = get_the_title( $formateur_id );
        $formateur_name = strtok( $formateur_title, ' ' ); // Prend le premier mot du titre
    } else {
        $formateur_name = 'inconnu';
    }

    // Créer un sous-dossier pour le produit sélectionné
    $product_dir = $dir . '/' . $selected_product_id;
    if ( ! file_exists( $product_dir ) ) {
        mkdir( $product_dir, 0755, true ); // Créer le dossier avec les permissions appropriées
    }

    // Compter le nombre de fichiers existants pour ce produit et ce formateur dans le sous-dossier
    $files = glob( $product_dir . '/emargements-*-' . $formateur_name . '-' . $selected_product_id . $ext );
    $count = count( $files ) + 1;

    // Générer le nouveau nom de fichier dans le sous-dossier au format souhaité
    $new_name = $selected_product_id . '/emargements-' . $count . '-' . $formateur_name . '-' . $selected_product_id . $ext;
    return $new_name;
}

// Appliquer cette fonction de renommage au champ 'file_docformateursemarge'
add_filter('wp_handle_upload_prefilter', 'my_unique_filename_callback_frontend');
