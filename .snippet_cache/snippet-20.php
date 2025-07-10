<?php
/**
 * Snippet ID: 20
 * Name: Ajouter tampon validé sur documents formateurs importés depuis les commandes
 * Description: 
 * @active false
 */

// Appliquer un tampon aux fichiers de la commande lors de la mise à jour
add_action( 'save_post_shop_order', 'apply_stamp_to_order_files', 10, 3 );

function apply_stamp_to_order_files( $post_id, $post, $update ) {
    // Vérifiez que le post est bien une commande WooCommerce
    if ( 'shop_order' !== $post->post_type ) {
        return;
    }

    $order_id = $post_id; // ID de la commande en cours
    $upload_dir = wp_upload_dir();
    $order_dir = $upload_dir['basedir'] . '/pdfclients/' . $order_id; // Chemin complet vers le dossier de la commande

    // Vérifiez si le sous-dossier de la commande existe
    if ( ! is_dir( $order_dir ) ) {
        error_log( "Le dossier de la commande n'existe pas : " . $order_dir );
        return;
    }

    // Parcourez tous les fichiers du sous-dossier de la commande
    $files = glob( $order_dir . '/*.*' );

    foreach ( $files as $file_path ) {
        $ext = pathinfo( $file_path, PATHINFO_EXTENSION );

        // Appliquer le tampon seulement aux formats PDF, JPG, et PNG
        if ( extension_loaded( 'imagick' ) && in_array( strtolower( $ext ), ['pdf', 'jpg', 'jpeg', 'png'] ) ) {
            try {
                $imagick = new \Imagick( $file_path );

                // Création du tampon
                $stamp = new \Imagick();
                $stamp->newImage( 200, 70, new \ImagickPixel( 'transparent' ) );
                $stamp->setImageFormat( 'png' );

                $draw = new \ImagickDraw();
                $draw->setFillColor( 'red' );
                $draw->setFontSize( 24 );
                $draw->annotation( 20, 40, 'VALIDÉ' );
                $stamp->drawImage( $draw );

                // Positionner le tampon sur l'image (en bas à gauche)
                $imagick->compositeImage( $stamp, \Imagick::COMPOSITE_OVER, 10, 10 );

                // Sauvegarder l'image avec le tampon appliqué
                $imagick->writeImage( $file_path );

                // Libérer les ressources
                $imagick->clear();
                $stamp->clear();

                error_log( "Tampon appliqué avec succès sur le fichier : " . $file_path );
            } catch ( Exception $e ) {
                error_log( 'Erreur lors de l’ajout du tampon : ' . $e->getMessage() );
            }
        } else {
            error_log( "Le fichier n'est pas au format attendu pour l'ajout du tampon : " . $file_path );
        }
    }
}
