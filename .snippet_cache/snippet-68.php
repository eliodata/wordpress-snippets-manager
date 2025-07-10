<?php
/**
 * Snippet ID: 68
 * Name: Ajouter lien metabox.io relationship et select posts sous titres / noms clients BDD / action de formation / produits
 * Description: 
 * @active true
 */

// CPT CLIENTS VERS COMPTE WP

add_action( 'edit_form_after_title', 'add_info_below_cpt_title' );

function add_info_below_cpt_title( $post ) {
    // Vérifier si le post appartient aux CPT 'client' ou 'prospect'
    if ( ! in_array( $post->post_type, [ 'client', 'prospect' ] ) ) {
        return;
    }

    global $wpdb;

    // **Partie 1 : Société**
    $client_id = $post->ID;
    $user_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT `from` FROM {$wpdb->prefix}mb_relationships WHERE `to` = %d AND `type` = 'clients-wp-bdd'",
        $client_id
    ));
    $society_info = '';
if ( !empty( $user_id ) ) {
    $user = get_userdata( $user_id );
    if ( $user ) { // Vérifiez que l'utilisateur existe réellement
        $billing_company = get_user_meta( $user_id, 'billing_company', true );
        $edit_link = get_edit_user_link( $user_id );
        if ( !empty( $billing_company ) ) {
            $society_info = '<strong style="color: #ffffff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">Compte ecommerce :</strong> <a href="' . esc_url( $edit_link ) . '" style="color: #ffd700; text-decoration: none; font-weight: 600; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">' . esc_html( $billing_company ) . '</a>';
        } else {
            $society_info = '<strong style="color: #ffffff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">Utilisateur lié :</strong> <a href="' . esc_url( $edit_link ) . '" style="color: #87ceeb; text-decoration: none; font-weight: 500;">Voir le compte utilisateur WP</a>';
        }
    } else {
        $society_info = '<strong style="color: #ff6b6b; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">Utilisateur lié :</strong> <span style="color: #ffcccb;">Utilisateur introuvable.</span>';
    }
} else {
    $society_info = '<strong style="color: #ffffff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">Société :</strong> <span style="color: #ffcccb;">Aucun utilisateur lié trouvé</span>';
}

    // **Partie 2 : Relations**
    $related_posts = rwmb_meta( 'post_relations', [ 'object_type' => 'post' ], $post->ID );
    $relations_info = '';
    if ( !empty( $related_posts ) ) {
        $relations_info = '<strong style="color: #ffffff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">Relations :</strong> ';
        $links = [];
        foreach ( $related_posts as $related_post ) {
            $links[] = '<a href="' . get_edit_post_link( $related_post ) . '" style="color: #87ceeb; text-decoration: none; font-weight: 500; margin: 0 2px;">' . get_the_title( $related_post ) . '</a>';
        }
        $relations_info .= implode( ' <span style="color: #cccccc;">|</span> ', $links );
    } else {
        $relations_info = '<strong style="color: #ffffff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">Relations :</strong> <span style="color: #cccccc;">Aucun post relatif sélectionné</span>';
    }

    // **Affichage final**
    echo '<div style="margin-top: 0px; padding: 8px 12px; background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%); border-left: 4px solid #ffd700; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">';
    echo '<p style="margin: 0; font-size: 14px; line-height: 1.5;">' . $society_info . ' <span style="color: #cccccc; font-weight: bold;">|</span> ' . $relations_info . '</p>';
    echo '</div>';
}

    // CPT FORMATEURS VERS COMPTE WP

add_action( 'edit_form_after_title', 'add_formateur_info_below_title' );

function add_formateur_info_below_title( $post ) {
    // Vérifier si le post appartient au CPT 'formateur'
    if ( $post->post_type !== 'formateur' ) {
        return;
    }

    global $wpdb;

    // Récupérer l'utilisateur lié au formateur via la relation
    $formateur_id = $post->ID;
    $user_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT `to` FROM {$wpdb->prefix}mb_relationships WHERE `from` = %d AND `type` = 'formateur-bdd-formateur-wp'",
        $formateur_id
    ));

    // Préparer les informations pour l'affichage
    $relation_info = '';
    if ( !empty( $user_id ) ) {
        $user = get_userdata( $user_id );
        $user_name = $user->display_name;
        $edit_link = get_edit_user_link( $user_id );

        $relation_info = '<strong style="color: #ffffff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">Compte site FS :</strong> <a href="' . esc_url( $edit_link ) . '" style="color: #ffd700; text-decoration: none; font-weight: 600; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">' . esc_html( $user_name ) . '</a>';
    } else {
        $relation_info = '<strong style="color: #ffffff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">Compte site FS :</strong> <span style="color: #ffcccb;">Aucun utilisateur lié trouvé</span>';
    }

    // Afficher les informations sous le titre
    echo '<div style="margin-top: 0px; padding: 8px 12px; background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%); border-left: 4px solid #87ceeb; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">';
    echo '<p style="margin: 0; font-size: 14px; line-height: 1.5;">' . $relation_info . '</p>';
    echo '</div>';
}


    // CPT ACTION DE FORMATION VERS PRODUITS

add_action( 'edit_form_after_title', 'add_product_links_below_title' );

function add_product_links_below_title( $post ) {
    // Vérifier si on est dans l'édition d'un post du CPT 'action-de-formation'
    if ( 'action-de-formation' !== $post->post_type ) {
        return;
    }

    // Récupérer l'ID du produit sélectionné dans le champ 'fsbdd_relsessproduit'
    $product_id = rwmb_get_value( 'fsbdd_relsessproduit', [], $post->ID );

    // Préparer l'affichage
    if ( !empty( $product_id ) ) {
        // Récupérer le nom (titre) du produit
        $product_title = get_the_title( $product_id );

        // Lien pour voir le produit (front-end)
        $view_link = get_permalink( $product_id );

        // Lien pour modifier le produit (back-end)
        $edit_link = get_edit_post_link( $product_id );

        if ( $view_link && $edit_link ) {
            $relation_info = '<strong style="color: #ffffff; font-size: 16px; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">Formation : <span style="color: #ffd700; font-weight: 700;">' . esc_html( $product_title ) . '</span></strong> '
                           . '<span style="color: #cccccc; margin: 0 8px;">-</span> '
                           . '<a href="' . esc_url( $view_link ) . '" target="_blank" rel="noopener noreferrer" style="color: #87ceeb; text-decoration: none; font-weight: 500; padding: 2px 8px; background: rgba(135,206,235,0.2); border-radius: 3px; margin-right: 8px;">Voir</a>'
                           . '<a href="' . esc_url( $edit_link ) . '" target="_blank" rel="noopener noreferrer" style="color: #98fb98; text-decoration: none; font-weight: 500; padding: 2px 8px; background: rgba(152,251,152,0.2); border-radius: 3px;">Modifier</a>';
        } else {
            $relation_info = '<strong style="color: #ffffff; font-size: 16px; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">Formation : <span style="color: #ffd700; font-weight: 700;">' . esc_html( $product_title ) . '</span></strong> '
                           . '<span style="color: #ff6b6b;">- Impossible de récupérer les liens du produit.</span>';
        }
    } else {
        $relation_info = '<strong style="color: #ffffff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">Formation :</strong> <span style="color: #ffcccb;">Aucun produit lié trouvé</span>';
    }

    echo '<div style="margin-top:0; padding: 10px 12px; background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0.08) 100%); border-left: 4px solid #ffd700; border-radius: 4px; box-shadow: 0 2px 6px rgba(0,0,0,0.3);">';
    echo '<p style="margin:0; font-size: 14px; line-height: 1.6;">' . $relation_info . '</p>';
    echo '</div>';
}


// Afficher le lien vers les CPT client/prospect sous le titre de la page utilisateur
function add_cpt_link_to_user_profile_header() {
    // Vérifier si nous sommes sur une page d'édition d'utilisateur
    $screen = get_current_screen();
    if (!$screen || ($screen->base !== 'user-edit' && $screen->base !== 'profile')) {
        return;
    }
    
    // Récupérer l'ID de l'utilisateur en cours d'édition
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : get_current_user_id();
    
    global $wpdb;
    
    // Récupérer les CPT liés à l'utilisateur via la relation 'clients-wp-bdd'
    $cpt_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT `to` FROM {$wpdb->prefix}mb_relationships WHERE `from` = %d AND `type` = 'clients-wp-bdd'",
        $user_id
    ));
    
    if (!empty($cpt_ids)) {
        echo '<div style="margin: 10px 0; padding: 12px; background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(240,248,255,0.95) 100%); border-left: 4px solid #314150; border-radius: 6px; box-shadow: 0 3px 8px rgba(0,0,0,0.15);">';
        echo '<strong style="color: #314150; font-size: 15px; text-shadow: 1px 1px 2px rgba(255,255,255,0.8);">Fiches liées : </strong>';
        
        $links = array();
        foreach ($cpt_ids as $cpt_id) {
            $post = get_post($cpt_id);
            if ($post) {
                $post_type_label = ($post->post_type === 'client') ? 'Client' : 'Prospect';
                $edit_link = get_edit_post_link($cpt_id);
                $links[] = '<span style="margin-right: 12px;"><strong style="color: #314150;">' . $post_type_label . ' : </strong>' .
                          '<a href="' . esc_url($edit_link) . '" style="color: #0073aa; text-decoration: none; font-weight: 600; padding: 2px 6px; background: rgba(0,115,170,0.1); border-radius: 3px;">' . esc_html(get_the_title($cpt_id)) . '</a></span>';
            }
        }
        
        echo implode(' <span style="color: #314150; font-weight: bold;">|</span> ', $links);
        echo '</div>';
    }
}

// Ajouter la fonction au hook de l'en-tête de l'administration
add_action('admin_notices', 'add_cpt_link_to_user_profile_header');