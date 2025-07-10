<?php
/**
 * Snippet ID: 9
 * Name: Fonctions désactivées
 * Description: 
 * @active false
 */


/**
 * RELATIONS POST TO USERS BIDIRECTIONNELLE METABOX CLIENTS EN LIGNE VS CLIENTS BDD
* add_action( 'mb_relationships_init', function () {
*    MB_Relationships_API::register( [
*        'id'   => 'users_to_posts',
*        'from' => [
*            'object_type' => 'user',
*            'meta_box'    => [
*                'title' => 'Correspondance Base de Données',
*            ],
*        ],
*        'to'   => [
*            'object_type' => 'post',
*            'post_type'   => 'client',
*            'meta_box'    => [
*                'title' => 'Correspondance client en ligne',
*            ],			
*       ],
*    ] );	
*} );
*/


// bloquer defilement vertical quand manipulation barre horizontale admin column pro



/**
 * METABOX PRODUITS DATES CHANGEMENT ETAT FORMATEURS 
// DESACTIVER CASE ENVOYER EMAIL AU NOUVEAU CLIENT APRES CREATION COMPTE EN ADMIN
add_filter( 'send_email_change_email', '__return_false' );
add_filter( 'send_password_change_email', '__return_false' );
*/



/**
 * METABOX PRODUITS DATES CHANGEMENT ETAT FORMATEURS 

// Ajouter la Meta Box date changement etats
function add_custom_meta_box() {
    add_meta_box(
        'etat_dates_meta_box',           // ID de la Meta Box
        'Dates de Changement d\'État',   // Titre de la Meta Box
        'display_etat_dates_meta_box',   // Fonction de callback pour afficher le contenu
        'product',                       // Type de post (ici, produit)
        'side',                          // Contexte (où la box doit apparaître)
        'high'                           // Priorité
    );
}
add_action('add_meta_boxes', 'add_custom_meta_box');

// Afficher le contenu de la Meta Box
function display_etat_dates_meta_box($post) {
    // Les états à vérifier
    $etats = ['Contrat envoyé', 'Contrat reçu', 'Emargement OK'];

    echo '<ul>';

    foreach ($etats as $etat) {
        $meta_key = 'date_' . sanitize_title($etat) . '_%';
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE %s", 
            $post->ID, 
            $meta_key
        ));

        foreach ($results as $result) {
            // Extrait l'ID du formateur depuis la clé de métadonnée
            preg_match('/date_[^_]+_(\d+)/', $result->meta_key, $matches);
            $formateur_id = $matches[1];
            $titre_formateur = get_the_title($formateur_id);
            echo '<li>' . esc_html($titre_formateur) . ' - ' . esc_html($etat) . ': ' . esc_html($result->meta_value) . '</li>';
        }
    }

    echo '</ul>';
}

 */


/**
 * // AFFICHER CONTENU TEXTE ET DEVIS CONVENTION pdf a telecharger SUR PAGE COMMANDES DETAILS WOOCOMMERCE

add_action('woocommerce_order_details_before_order_table', function ($order) {
    $status = $order->get_status();

    if (in_array($status, array('modifpreinscript', 'preinscription', 'inscription', 'confirme', 'confirmemail', 'devisproposition', 'facturefsc', 'facturesent', 'factureok'))) {
        echo '<div style="margin-bottom: 10px;">';
        echo '<h2 style="margin-bottom: 15px; margin-top: 25px;">Vos documents</h2>';
        echo '<h3>DEVIS: ' . do_shortcode('[e2pdf-download id="16" button-title="Télécharger" dataset="' . $order->get_id() . '"]</h3>');
        echo '</div>';
    }

    if (in_array($status, array('inscription', 'confirme', 'confirmemail', 'facturefsc', 'facturesent', 'factureok'))) {
        echo '<div style="margin-bottom: 10px;">';
        echo '<h3 style="margin-bottom: 15px;">CONVENTION: ' . do_shortcode('[e2pdf-download id="13" button-title="Télécharger" dataset="' . $order->get_id() . '"]</h3>');
        echo '</div>';
    }
	
	    if (in_array($status, array('confirme', 'confirmemail', 'facturefsc', 'facturesent', 'factureok'))) {
        echo '<div style="margin-bottom: 10px;">';
        echo '<h3 style="margin-bottom: 25px;">CONVOCATION: ' . do_shortcode('[e2pdf-download id="17" button-title="Télécharger" dataset="' . $order->get_id() . '"]</h3>');
        echo '</div>';
    }
}, 10, 1);

*/


/**
 * EXPORT WP ALL EXPORT RELATION CPT CLIENT VERS USER 

function get_user_linked_to_client($client_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mb_relationships';

    // Recherche de l'utilisateur lié à partir de l'ID du client
    $user_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT `from` FROM $table_name WHERE `to` = %d AND `type` = 'clients-wp-bdd'",
            $client_id
        )
    );

    // Retourne l'ID de l'utilisateur ou 'No user linked'
    return $user_id ? $user_id : 'No user linked';
}
*/

/**
 * EXPORT WP ALL EXPORT RELATION USER VERS CPT CLIENTS 

function get_related_client_for_user($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mb_relationships';

    // Rechercher l'ID du client lié à cet utilisateur
    $client_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT `to` FROM $table_name WHERE `from` = %d AND `type` = 'clients-wp-bdd'",
            $user_id
        )
    );

    return $client_id ? $client_id : 'No client linked';
}
*/

/**
 * SNIPPET POUR WP ALL IMPORT RELATION METABOX IO CLIENTS BDD & USERS

add_action('pmxi_saved_post', 'update_meta_box_unique_user_relationship', 10, 3);
function update_meta_box_unique_user_relationship($post_id, $xml_data, $is_update) {
    // Vérifiez que c'est bien un CPT client
    if (get_post_type($post_id) !== 'client') {
        return;
    }

    // Récupérer l'ID utilisateur de l'import (assurez-vous que ce champ existe dans votre import)
    $user_id = get_post_meta($post_id, 'user_id', true);

    if ($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mb_relationships';

        // Supprimer toutes les relations existantes pour ce client (post_id)
        $wpdb->delete(
            $table_name,
            ['to' => $post_id, 'type' => 'clients-wp-bdd'],
            ['%d', '%s']
        );

        // Supprimer toutes les relations existantes pour cet utilisateur (user_id)
        $wpdb->delete(
            $table_name,
            ['from' => $user_id, 'type' => 'clients-wp-bdd'],
            ['%d', '%s']
        );

        // Insérer la nouvelle relation unique entre l'utilisateur et le client
        $wpdb->insert(
            $table_name,
            [
                'from' => $user_id,  // ID utilisateur
                'to'   => $post_id,  // ID du CPT client
                'type' => 'clients-wp-bdd'
            ],
            ['%d', '%d', '%s']
        );
    }
}
*/

/**
 *
// SNIPPET POUR WP ALL IMPORT RELATION METABOX IO USERs vers CLIENTS BDD

add_action('pmxi_after_post_import', 'update_user_client_relationship_after_import', 10, 1);
function update_user_client_relationship_after_import($user_id) {
    // Vérifiez que c'est bien un utilisateur qui est importé
    if (get_post_type($user_id) !== 'user' && !is_numeric($user_id)) {
        return;
    }

    // Récupérer l'ID du client lié après l'import pour s'assurer qu'il est mis à jour
    $client_id = get_user_meta($user_id, 'related_client_id', true);

    if ($client_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mb_relationships';

        // Supprimer toutes les relations existantes pour cet utilisateur (mais ne supprime pas celles du client)
        $wpdb->delete(
            $table_name,
            ['from' => $user_id, 'type' => 'clients-wp-bdd'],
            ['%d', '%s']
        );

        // Insérer la nouvelle relation sans toucher aux autres relations du client
        $wpdb->insert(
            $table_name,
            [
                'from' => $user_id,  // ID de l'utilisateur
                'to'   => $client_id,  // ID du CPT client
                'type' => 'clients-wp-bdd'
            ],
            ['%d', '%d', '%s']
        );
    }
}
*/

/* Afficher description de variation dans le panier

// Cart page (and mini cart)
add_filter( 'woocommerce_cart_item_name', 'cart_item_product_description', 20, 3);
function cart_item_product_description( $item_name, $cart_item, $cart_item_key ) {
    if ( ! is_checkout() ) {
        if( $cart_item['variation_id'] > 0 ) {
            $description = $cart_item['data']->get_description(); // variation description
        } else {
            $description = $cart_item['data']->get_short_description(); // product short description (for others)
        }

        if ( ! empty($description) ) {
            return $item_name . '<br><div class="description">
                <strong>' . __( 'Durée', 'woocommerce' ) . '</strong>: '. $description . '
            </div>';
        }
    }
    return $item_name;
}


// Checkout page
add_filter( 'woocommerce_checkout_cart_item_quantity', 'cart_item_checkout_product_description', 20, 3);
function cart_item_checkout_product_description( $item_quantity, $cart_item, $cart_item_key ) {
    if( $cart_item['variation_id'] > 0 ) {
        $description = $cart_item['data']->get_description(); // variation description
    } else {
        $description = $cart_item['data']->get_short_description(); // product short description (for others)
    }

    if ( ! empty($description) ) {
        return $item_quantity . '<br><div class="description">
            <strong>' . __( 'Durée', 'woocommerce' ) . '</strong>: '. $description . '
        </div>';
    }

    return $item_quantity;
}
 */

