<?php
/**
 * Snippet ID: 22
 * Name: BACKUP DOSSIER DYNAMIQUE NOM FORMATEUR UPLOAD FILES
 * Description: 
 * @active false
 */


 
// CREER DOSSIER AVEC NOM UTILISATEUR POUR DOCUMENTS FORMATEUR METABOX FILE FIELD ET NOM DYNAMIQUE FICHIER

function my_unique_filename_callback( $dir, $name, $ext ) {
    $current_user = wp_get_current_user();
    $user_login = $current_user->user_login;
    $name =  '_' . $user_login . '_emargements_' . $name;
    return $name . $ext;
}


add_filter( 'rwmb_meta_boxes', 'metabox_file_upload_formateurs_emargements' );

function metabox_file_upload_formateurs_emargements( $meta_boxes ) {
    $prefix = '';

    $meta_boxes[] = [
        'title'      => __( 'Documents formateurs', 'your-text-domain' ),
        'id'         => 'documents-formateurs',
        'fields'     => [
            [
                'id'   => $prefix . 'file_docformateursemarge',
                'name' => __( 'Documents formateurs émargements', 'your-text-domain' ),
                'type' => 'file',
                'upload_dir' => ABSPATH . 'documents-internes/fichiers-formateurs/',
				'unique_filename_callback' => 'my_unique_filename_callback',
            ],
        ],
    ];

    return $meta_boxes;
}


add_filter( 'rwmb_meta_boxes', 'metabox_file_upload_formateurs_divers' );

function metabox_file_upload_formateurs_divers( $meta_boxes ) {
    $prefix = '';

    $meta_boxes[] = [
        'title'      => __( 'Documents formateurs', 'your-text-domain' ),
        'id'         => 'documents-formateurs',
        'fields'     => [
            [
                'id'   => $prefix . 'file_docformateursdivers',
                'name' => __( 'Documents formateurs divers', 'your-text-domain' ),
                'type' => 'file',
                'upload_dir' => ABSPATH . 'documents-internes/fichiers-formateurs/',
            ],
        ],
    ];

    return $meta_boxes;
}