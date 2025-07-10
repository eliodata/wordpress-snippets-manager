<?php
/**
 * Snippet ID: 32
 * Name: backup functions php 24 11 2024
 * Description: * Active: false  Active: false  Active: false
 * @active false
 */

/**
 * AFFICHER DATES DU JOUR PAR SHORTCODE 
 */

function displayTodaysDate( $atts )
{
return date(get_option('date_format'));
}
add_shortcode( 'datetoday', 'displayTodaysDate');


/**
 * DISABLE COMMENTS FEEDS RSS FLUX
*/
function remove_comment_feeds( $for_comments ){
    if( $for_comments ){
        remove_action( 'do_feed_rss2', 'do_feed_rss2', 10, 1 );
        remove_action( 'do_feed_atom', 'do_feed_atom', 10, 1 );
    }
}
add_action( 'do_feed_rss2', 'remove_comment_feeds', 9, 1 );
add_action( 'do_feed_atom', 'remove_comment_feeds', 9, 1 );


/**
 * MODIFIER MESSAGE ALERTE PRODUITS MAX DANS LE PANIER
 */
function isa_wc_max_qty_scripts() {


 
    /* Add JS to frontend wherever woocommerce.js is loaded. */
 
    wp_add_inline_script( 'woocommerce', 'window.onload = function(){
 
        function onBlurHandler( event ) {
            var max = this.getAttribute( "max" );
            if ( this.validity.rangeOverflow ) {
 
                message = + max + " stagiaires max. Contactez-nous pour des volumes supérieurs, ou précisez le dans votre commande svp.";
 
                this.setCustomValidity( message );
 
            } else {
                this.setCustomValidity("");
            }
             
        }
 
        var quantity = document.querySelector( "form.cart .qty" );
        var cartQuantity = document.querySelector( "form.woocommerce-cart-form .qty" );
         
        if ( quantity ) { // quantity input on single product page
            quantity.addEventListener( "blur", onBlurHandler, false );
        }
        if ( cartQuantity ) { // quantity input on cart page
            cartQuantity.addEventListener( "blur", onBlurHandler, false );
        }
 
        };'
    );
}
 
add_action( 'wp_enqueue_scripts', 'isa_wc_max_qty_scripts', 9999 );



/**
 * SUPPRIMER PRODUIT DANS LE PANIER SI AJOUT D'UN NOUVEAU PRODUIT 
*/
add_filter( 'woocommerce_add_cart_item_data', 'add_cart_item_data', 10, 3 );
function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
    global $woocommerce;
    $woocommerce->cart->empty_cart();
    return $cart_item_data;
}


    // AJOUTER TEXTE AVANT PRIX VARIATIONS

add_filter( 'woocommerce_get_price_html', 'cw_change_product_price_display' );
add_filter( 'woocommerce_cart_item_price', 'cw_change_product_price_display' );
function cw_change_product_price_display( $price ) {
    // Your additional text in a translatable string
    $text = __('Prix public, selon options et prise en charge: ');

    // returning the text before the price
    return $text . ' ' . $price;
}


// Change WooCommerce "Related products" text

add_filter('gettext', 'change_rp_text', 10, 3);
add_filter('ngettext', 'change_rp_text', 10, 3);

function change_rp_text($translated, $text, $domain)
{
     if ($text === 'Related products' && $domain === 'woocommerce') {
         $translated = esc_html__('Formations en relation', $domain);
     }
     return $translated;
}


/** MASQUER AFFICHAGE PRIX SOUS TITRE PRODUIT POUR VARIABLES. */

add_filter( 'woocommerce_variable_sale_price_html', 'bbloomer_variation_price_format', 10, 2 );

add_filter( 'woocommerce_variable_price_html', 'bbloomer_variation_price_format', 10, 2 );

function bbloomer_variation_price_format( $price, $product ) {

 if (is_product()) {
    return $product->get_price();
 } else {
        // Main Price
        $prices = array( $product->get_variation_price( 'min', true ), $product->get_variation_price( 'max', true ) );
        $price = $prices[0] !== $prices[1] ? sprintf( __( '%1$s', 'woocommerce' ), wc_price( $prices[0] ) ) : wc_price( $prices[0] );

        // Sale Price
        $prices = array( $product->get_variation_regular_price( 'min', true ), $product->get_variation_regular_price( 'max', true ) );
        sort( $prices );
        $saleprice = $prices[0] !== $prices[1] ? sprintf( __( '%1$s', 'woocommerce' ), wc_price( $prices[0] ) ) : wc_price( $prices[0] );

        if ( $price !== $saleprice ) {
        $price = '<del>' . $saleprice . '</del> <ins>' . $price . '</ins>';
        }
        return $price;
         }

}

// show variation price
add_filter('woocommerce_show_variation_price', function() {return true;});

//override woocommerce function
function woocommerce_template_single_price() {
    global $product;
    if ( ! $product->is_type('variable') ) { 
        woocommerce_get_template( 'single-product/price.php' );
    }
} 



//AJOUTER MENU MON COMPTE WOOCOMMERCE POUR ROLE FORMATEUR

add_action( 'init', 'add_admin_tools_account_endpoint' );
function add_admin_tools_account_endpoint() {
    add_rewrite_endpoint( 'formateurs-tools', EP_PAGES );
}

add_filter ( 'woocommerce_account_menu_items', 'custom_account_menu_items', 10 );
function custom_account_menu_items( $menu_links ){
    if ( current_user_can('formateurfs') ) {
        $menu_links = array_slice( $menu_links, 0,3 , true )
        + array( 'formateurs-tools' => __('Outils formateurs') )
        + array_slice( $menu_links, 3, NULL, true );
    }
    return $menu_links;
}


add_action( 'woocommerce_account_admin-tools_endpoint', 'add_admin_tools_content' );

// point the endpoint to a custom URL - LIEN PAGE DEDIEE FORMATEURS
add_filter( 'woocommerce_get_endpoint_url', 'wptips_custom_woo_endpoint', 10, 2 );
function wptips_custom_woo_endpoint( $url, $endpoint ){
     if( $endpoint == 'formateurs-tools' ) {
        $url = 'https://formationstrategique.fr/outils-formateurs/'; // Your custom URL to add to the My Account menu
    }
    return $url;
}




/**
 * NOTIFICATION EMAIL ADMIN NOUVEAU COMPTE CLIENT WOOCOMMERCE
*/

add_action( 'woocommerce_created_customer', 'woocommerce_created_customer_admin_notification' );
function woocommerce_created_customer_admin_notification( $customer_id ) {

  add_action( 'wp_new_user_notification_email_admin', 'change_admin_email_to_store_manager_email' );
  function change_admin_email_to_store_manager_email( $wp_new_user_notification_email_admin ) {
    $wp_new_user_notification_email_admin['to'] = 'f.martin@formationstrategique.fr';
    return $wp_new_user_notification_email_admin;
  }

  wp_send_new_user_notifications( $customer_id, 'admin' );
}



/**
 * Changer texte vous aimerez aussi... upsel products Change "You may also like..." text in WooCommerce
 */

add_filter( 'woocommerce_product_upsells_products_heading', 'db_change_upsell_title_text' );
  
function db_change_upsell_title_text() {
   return 'Autres sessions disponibles';
}


// MASQUER LA FIN DES TITRES DES PRODUITS SUR LES PAGES WOOCOMMERCE et la page calendrier dynamique
add_filter( 'the_title', 'masquer_partie_titre_produit', 10, 2 );
function masquer_partie_titre_produit( $title, $id = null ) {
    // Pour la partie front-end
    if ( (is_product() || is_product_category() || is_shop()) && in_the_loop() && get_post_type( $id ) === 'product' ) {
        $parts = explode( '>', $title );
        if ( count( $parts ) > 1 ) {
            return trim($parts[0]);
        }
    }

    return $title;
}

// AFFICHER CHAMP OPTION CHOIX CATEGORIES PRODUITS SESSIONS AUTORISATIONS CONDUITE METABOX
// Afficher les cases à cocher au-dessus de la description de variation, uniquement pour la catégorie 351
add_action( 'woocommerce_before_single_variation', 'ajouter_champ_checkboxes_produit_au_dessus_description' );
function ajouter_champ_checkboxes_produit_au_dessus_description() {
    global $product;

    // Vérifiez si le produit appartient à la catégorie 351
    if( has_term( 351, 'product_cat', $product->get_id() ) ) {
        echo '<div class="champ-checkbox-categorie">';
        echo '<label><strong>Catégorie(s) choisie(s) : </strong></label><br>';
        $options = array('Groupe A', 'Groupe B', '1B', '3', '4', '5', '6', '7', 'B1', 'B2', 'B3', 'C1', 'C2', 'C3', 'D', 'E', 'F', 'G');
        
        foreach( $options as $option ) {
            echo '<label><input type="checkbox" name="choix_categorie[]" value="' . esc_attr( $option ) . '"> ' . esc_html( $option ) . '</label> ';
        }
        echo '</div>';
    }
}

// Enregistrer les cases à cocher dans le panier
add_filter( 'woocommerce_add_cart_item_data', 'enregistrer_choix_categories_dans_panier', 10, 2 );
function enregistrer_choix_categories_dans_panier( $cart_item_data, $product_id ) {
    if( isset( $_POST['choix_categorie'] ) ) {
        $cart_item_data['choix_categorie'] = array_map( 'sanitize_text_field', $_POST['choix_categorie'] );
    }
    return $cart_item_data;
}

// Afficher la sélection des cases à cocher dans le panier et la page de commande
add_filter( 'woocommerce_get_item_data', 'afficher_choix_categories_dans_commande', 10, 2 );
function afficher_choix_categories_dans_commande( $item_data, $cart_item ) {
    if( isset( $cart_item['choix_categorie'] ) && is_array( $cart_item['choix_categorie'] ) ) {
        $item_data[] = array(
            'name' => 'Catégorie(s)',
            'value' => implode( ', ', $cart_item['choix_categorie'] )
        );
    }
    return $item_data;
}

// Enregistrer les cases à cocher dans les métadonnées de la commande
add_action( 'woocommerce_add_order_item_meta', 'enregistrer_meta_commande_choix_categories', 10, 2 );
function enregistrer_meta_commande_choix_categories( $item_id, $values ) {
    if( isset( $values['choix_categorie'] ) && is_array( $values['choix_categorie'] ) ) {
        wc_add_order_item_meta( $item_id, 'choix_categorie', implode( ', ', $values['choix_categorie'] ) );
    }
}

// Afficher les cases à cocher dans les e-mails de commande
add_filter( 'woocommerce_email_order_meta_fields', 'ajouter_choix_categories_dans_email', 10, 3 );
function ajouter_choix_categories_dans_email( $fields, $sent_to_admin, $order ) {
    foreach( $order->get_items() as $item_id => $item ) {
        if( $choix_categories = wc_get_order_item_meta( $item_id, 'choix_categorie' ) ) {
            $fields['choix_categorie'] = array(
                'label' => 'Catégorie(s)',
                'value' => $choix_categories,
            );
        }
    }
    return $fields;
}


/**
 * WOOCOMMERCE CHECKOUT FIELD EDITOR AFFICHER CHAMPS NOMS PRENOM SELON QUANTITE DANS LE PANIER
 */
function custom_checkout_script() {
    if (is_checkout()) {
        ?>
        <script>
            jQuery(document).ready(function($) {
                function updateFieldsBasedOnQuantity(quantity) {
                    // Cachez tous les champs sauf le premier
                    $('.nomstagiaires2, .nomstagiaires3, .nomstagiaires4, .nomstagiaires5, .nomstagiaires6, .nomstagiaires7, .nomstagiaires8, .nomstagiaires9, .nomstagiaires10, .nomstagiaires11, .nomstagiaires12').hide();
                    
                    for(let i = 2; i <= quantity; i++) {
                        $('.nomstagiaires' + i).show();
                    }
                }

                // Récupération de la quantité
                let quantityText = $('.product-quantity').text();
                let match = quantityText.match(/\d+/);
                if (match) {
                    let quantity = parseInt(match[0]);
                    updateFieldsBasedOnQuantity(quantity);
                }
            });
        </script>
        <?php
    }
}
add_action('wp_footer', 'custom_checkout_script');



// MASQUER champs lieu form INTRA si pas de produit intra date a definir
add_filter( 'thwcfd_sections', 'fsbdd_hide_lieuforminter_checkout_fields', 10, 1 );

function fsbdd_hide_lieuforminter_checkout_fields( $sections ) {
    // Vérifier si le panier contient le mot "Intra"
    $contains_intra = false;
    foreach( WC()->cart->get_cart() as $cart_item ) {
        if ( stripos( $cart_item['data']->get_name(), 'Intra' ) !== false ) {
            $contains_intra = true;
            break;
        }
    }
    // Cacher le champ "fsbdd_select_lieuformintra" si le panier ne contient pas le mot "Intra"
    if ( !$contains_intra ) {
        foreach( $sections as &$section ) {
            if ( $section['id'] === 'fsbdd_select_lieuformintra' ) {
                $section['enabled'] = false;
                break;
            }
        }
    }
    return $sections;
}


// MASQUER champs lieu form inter si pas de produit inter date a definir

add_action( 'wp_enqueue_scripts', 'fsbdd_hide_lieuform_scripts' );

function fsbdd_hide_lieuform_scripts() {
    wp_enqueue_script( 'fsbdd-hide-lieuform', '', array(), '', true );
    add_action( 'wp_footer', 'fsbdd_hide_lieuform' );
}

function fsbdd_hide_lieuform() {
    // Vérifier si le panier contient des produits de la catégorie 327
    $has_cat_327 = false;
    foreach( WC()->cart->get_cart() as $cart_item ) {
        $product_cats = get_the_terms( $cart_item['product_id'], 'product_cat' );
        foreach ( $product_cats as $product_cat ) {
            if ( $product_cat->term_id == 327 ) {
                $has_cat_327 = true;
                break;
            }
        }
        if ( $has_cat_327 ) {
            break;
        }
    }
    // Cacher la classe "lieuforminter" si aucun produit de la catégorie 327 n'est présent dans le panier
    if ( !$has_cat_327 ) {
        echo '<style type="text/css">.lieuforminter{display:none;}</style>';
    }
    
 // Vérifier si le panier contient des produits de la catégorie 326
    $has_cat_326 = false;
    foreach( WC()->cart->get_cart() as $cart_item ) {
        $product_cats = get_the_terms( $cart_item['product_id'], 'product_cat' );
        foreach ( $product_cats as $product_cat ) {
            if ( $product_cat->term_id == 326 ) {
                $has_cat_326 = true;
                break;
            }
        }
        if ( $has_cat_326 ) {
            break;
        }
    }
    // Cacher la classe "lieuformintra" si aucun produit de la catégorie 326 n'est présent dans le panier
    if ( !$has_cat_326 ) {
        echo '<style type="text/css">.lieuformintra{display:none;}</style>';
    }
}

// MODIFIER MESSAGE AJOUT PANIER
function custom_add_to_cart_message( $message, $product_id ) {
    $product_name = get_the_title( $product_id );
    $message .= sprintf( ' %s', __( 'Une seule formation par demande devis.', 'woocommerce' ) );
    return $message;
}
add_filter( 'wc_add_to_cart_message', 'custom_add_to_cart_message', 10, 2 );



// Enregistrer l'URL de la signature et du fichier joint dans les métadonnées de la commande avant l'envoi du formulaire pour template e2pdf devis et conventions
add_action('wpcf7_before_send_mail', 'cf7oss_plugin_save_signature_url_and_attachment_to_order_metadata');
function cf7oss_plugin_save_signature_url_and_attachment_to_order_metadata($contact_form)
{
    $submission = WPCF7_Submission::get_instance();
    $posted_data = $submission->get_posted_data();

    if (isset($posted_data['order_id'])) {
        $order_id = absint($posted_data['order_id']);
        $order = wc_get_order($order_id);
        if ($order) {
            $form_id = $contact_form->id();
            $signature_field = '';
            $stamp_field = '';
            $meta_signature_key = '';
            $meta_file_key = '';
            $prefix = '';

            // Déterminer les champs, les clés de métadonnées, et le préfixe selon le formulaire utilisé
            if ($form_id == '259494') {
                // Configuration pour le formulaire 259494
                $signature_field = 'signature-800';
                $stamp_field = 'tampon-520cfdb7_file';
                $meta_signature_key = '_signature_259494';
                $meta_file_key = '_file_259494';
                $prefix = '259494_'; // Préfixe pour distinguer les champs texte
            } elseif ($form_id == '45012') {
                // Configuration pour le formulaire 45012
                $signature_field = 'signature-700';
                $stamp_field = 'tampon-420cfdb7_file';
                $meta_signature_key = '_signature_45012';
                $meta_file_key = '_file_45012';
                $prefix = '45012_'; // Préfixe pour distinguer les champs texte
            } elseif ($form_id == '260960') {
                // Configuration pour le formulaire 260960
                $signature_field = 'signature-900';
                $stamp_field = 'tampon-620cfdb7_file';
                $meta_signature_key = '_signature_260960';
                $meta_file_key = '_file_260960';
                $prefix = '260960_'; // Préfixe pour distinguer les champs texte
            }

            // Enregistrer la signature si disponible
            if (isset($posted_data[$signature_field])) {
                $signature_url = $posted_data[$signature_field];
                $order->update_meta_data($meta_signature_key, $signature_url);
            }

            // Ajouter les champs texte aux métadonnées de la commande avec un préfixe
            if (isset($posted_data['fsbdd_form-prenom'])) {
                $order->update_meta_data($prefix . 'form_prenom', $posted_data['fsbdd_form-prenom']);
            }
            if (isset($posted_data['fsbdd_form-nom'])) {
                $order->update_meta_data($prefix . 'form_nom', $posted_data['fsbdd_form-nom']);
            }
            if (isset($posted_data['fsbdd_form-fonction'])) {
                $order->update_meta_data($prefix . 'form_fonction', $posted_data['fsbdd_form-fonction']);
            }

            // Récupérer les données enregistrées par CF7DB et traiter le fichier tampon
            global $wpdb;
            $cfdb = apply_filters('cfdb7_database', $wpdb);
            $table_name = $cfdb->prefix . 'db7_forms';
            $results = $cfdb->get_results(
                $cfdb->prepare("SELECT * FROM $table_name WHERE form_post_id = %d ORDER BY form_id DESC LIMIT 1", $form_id)
            );

            if (!empty($results)) {
                $result = reset($results);
                $form_data = unserialize($result->form_value);

                if (isset($form_data[$stamp_field])) {
                    $upload_dir = wp_upload_dir();
                    $attachment_file_name = $form_data[$stamp_field];
                    $attachment_url = $upload_dir['baseurl'] . '/cfdb7_uploads/' . $attachment_file_name;

                    // Enregistrer l'URL du fichier joint
                    $order->update_meta_data($meta_file_key, $attachment_url);
                }
            }

            // Sauvegarder les modifications dans la commande
            $order->save();
        }
    }
}



// Ajouter texte en haut pages detail de commande client numéro, date, statut
add_action('woocommerce_account_content', 'my_custom_order_details', 5);
function my_custom_order_details() {
    global $wp;
    if ( ! empty( $wp->query_vars['view-order'] ) ) {
        $order_id = $wp->query_vars['view-order'];
        $order = wc_get_order( $order_id );

        echo '<p class="my-custom-order-message">';
        printf(
            __( 'Commande n°%1$s , passée le %2$s', 'woocommerce' ),
            '<mark class="order-number">' . $order->get_order_number() . '</mark>',
            '<mark class="order-date">' . wc_format_datetime( $order->get_date_created() ) . '</mark>',
            '<mark class="order-status">' . wc_get_order_status_name( $order->get_status() ) . '</mark>'
        );
        echo '</p>';
    }
}

// masquer l'original du texte ci-dessus
add_action( 'wp_footer', 'hide_original_order_message' );
function hide_original_order_message() {
    if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'view-order' ) ) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('p:not(.my-custom-order-message) > mark.order-number').parent('p').hide();
        });
        </script>
        <?php
    }
}



// Créer une metabox pour upload les fichiers vers le dossier /wp-content/uploads/pdfclients si signature par mail
add_action('add_meta_boxes', 'custom_order_metabox');
function custom_order_metabox() {
    add_meta_box(
        'custom_order_metabox',
        __('Envoyer Documents signés par mail', 'woocommerce'),
        'custom_order_metabox_content',
        'shop_order',
        'side',
        'default'
    );
}

function custom_order_metabox_content($post) {
    wp_nonce_field('custom_order_metabox_nonce', 'custom_order_metabox_nonce');
    echo '<input type="file" id="custom_pdf_upload" name="custom_pdf_upload" accept="application/pdf" />';
}



add_action('save_post', 'save_custom_order_metabox', 10, 1);
function save_custom_order_metabox($post_id) {
    if (!isset($_POST['custom_order_metabox_nonce']) || !wp_verify_nonce($_POST['custom_order_metabox_nonce'], 'custom_order_metabox_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['post_type']) && 'shop_order' == $_POST['post_type']) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    if (!empty($_FILES['custom_pdf_upload']['name'])) {
        $uploaded_file = $_FILES['custom_pdf_upload'];
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['basedir'] . '/pdfclients/';
        $upload_file_path = $upload_path . basename($uploaded_file['name']);

        if (!file_exists($upload_path)) {
            wp_mkdir_p($upload_path);
        }

        // Supprimer le fichier existant s'il y en a un avec le même nom
        if (file_exists($upload_file_path)) {
            unlink($upload_file_path);
        }

        move_uploaded_file($uploaded_file['tmp_name'], $upload_file_path);
    }
}



// Afficher les PDFs du dossier wpcontent e2pdf sur les pages de commande client devis conventions convocation...
add_action('woocommerce_order_details_before_order_table', 'afficher_documents_client', 10, 1);
function afficher_documents_client($order) {
    afficher_documents($order->get_id(), 'client');
}

// Code adapté pour côté admin
add_action('add_meta_boxes', 'ajouter_meta_boxes_documents');
function ajouter_meta_boxes_documents() {
    add_meta_box(
        'documents_commande',
        'Documents de la commande',
        'afficher_meta_box_documents',
        'shop_order',
        'side',
        'default'
    );
}

function afficher_meta_box_documents($post) {
    afficher_documents($post->ID, 'admin');
}

// Fonction générique pour afficher les documents
function afficher_documents($order_id, $context) {
    // Chemin vers le dossier contenant les PDFs
    $upload_dir = wp_upload_dir();
    $pdf_folder = $upload_dir['basedir'] . '/pdfclients/';

    // Vérifier si le dossier existe et est accessible
    if (!is_dir($pdf_folder)) {
        echo '<p style="margin-top: 20px;">Aucun document PDF trouvé pour cette commande.</p>';
        return;
    }

    // Ouvrir le dossier
    $dir = opendir($pdf_folder);
    if (!$dir) {
        wp_die(__('Impossible d\'ouvrir le dossier des PDF.', 'text-domain'));
    }

    // Titre avant la liste de documents
    echo '<h2 style="margin-top: 20px;">Vos documents</h2>';

    // Lister tous les fichiers du dossier
    $documents_affiches = false;
    while (($file = readdir($dir)) !== false) {
        // Vérifier si le nom du fichier contient le numéro de la commande et est un fichier PDF
        if (preg_match('/\b' . preg_quote($order_id, '/') . '\b/', $file) && pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
            // Chemin complet du fichier
            $file_path = $upload_dir['baseurl'] . '/pdfclients/' . $file;
            $documents_affiches = true;

            // Générer le lien approprié selon le type de fichier
            if (strpos($file, 'devis-') === 0) {
                echo '<div><h3 style="display:inline;">Devis:</h3> <a href="' . esc_url($file_path) . '" download>Télécharger</a></div>';
            } elseif (strpos($file, 'convention-') === 0) {
                echo '<div><h3 style="display:inline;">Convention:</h3> <a href="' . esc_url($file_path) . '" download>Télécharger</a></div>';
            } elseif (strpos($file, 'convocation-') === 0) {
                echo '<div><h3 style="display:inline;">Convocation:</h3> <a href="' . esc_url($file_path) . '" download>Télécharger</a></div>';
            } elseif (strpos($file, 'avenant-') === 0) {
                echo '<div><h3 style="display:inline;">Avenant:</h3> <a href="' . esc_url($file_path) . '" download>Télécharger</a></div>';
			} elseif (strpos($file, 'realisation-') === 0) {
                echo '<div><h3 style="display:inline;">Certificat de réalisation:</h3> <a href="' . esc_url($file_path) . '" download>Télécharger</a></div>';
			} elseif (strpos($file, 'emargements-') === 0) {
                echo '<div><h3 style="display:inline;">Emargements:</h3> <a href="' . esc_url($file_path) . '" download>Télécharger</a></div>';
            }
        }
    }

    // Fermer le dossier
    closedir($dir);

    // Ajouter de l'espace sous la liste
    if ($documents_affiches) {
        echo '<div style="margin-bottom: 20px;"></div>';
    } else {
        echo '<p style="margin-top: 20px;">Aucun document PDF trouvé pour cette commande.</p>';
    }
}





// remplacer nom statut quote request par demande de devis
add_filter( 'gettext', 'change_np_quote_request_status_label', 20, 3 );

function change_np_quote_request_status_label( $translated_text, $text, $domain ) {
    // Vérifiez si le texte est 'Quote Request' et modifiez-le en conséquence.
    if ( 'Quote Request' === $text ) {
        $translated_text = __( '1 - Demande de devis', 'gplsquote-req' );
    }

    return $translated_text;
}


/**
 * MASQUER EDITEUR WORDPRESS PRINCIPAL POUR LES PRODUITS WOOCOMMERCE
 */

function remove_product_editor() {
  remove_post_type_support( 'product', 'editor' );
}
add_action( 'init', 'remove_product_editor' );



/**
 * WOOCOMMERCE AFFICHER TVA 0 SELON CUSTOM FIELD
 */
  
add_action( 'woocommerce_checkout_update_order_review', 'bbloomer_taxexempt_checkout_based_on_zip' );
  
function bbloomer_taxexempt_checkout_based_on_zip( $post_data ) {
        WC()->customer->set_is_vat_exempt( false );
        parse_str( $post_data, $output );
        if ( $output['fsbdd_check_exotva'] === 'NON' ) WC()->customer->set_is_vat_exempt( true );
}

/**
 * AFFICHER DATES COMMENTAIRES ADMIN POST EDIT
 */

function date_commentaire( ){
	
	$comments = get_comments( array( 'post_id' => get_the_ID() ));
	
    if ( doing_action( 'wp_ajax_get-comments' ) )				   	
		foreach ( $comments as $comment ) :	
		$a = $comment->comment_ID;
		$b = get_comment_ID();
		$date_comment = get_comment_date();
		$time_comment = get_comment_time();
	
	if ( $a==$b )		
		echo '<p class="commentaire-date">'.'Date:    '.$date_comment.' à '.$time_comment.'</p>';
		endforeach;	
		
    return $comment_author_url;	
	}
	
 add_filter( 'get_comment_author_url','date_commentaire');


/**
 * MASQUER SUPPRESSION COMMENTAIRES POUR REFERENTS
 */
add_action('bulk_actions-edit-comments', 'ure_remove_comments_actions');
add_action('comment_row_actions', 'ure_remove_comments_actions');
function ure_remove_comments_actions($actions) {
    if (current_user_can('referent')) {
        if (isset($actions['delete'])) {
            unset($actions['delete']);
        }
        if (isset($actions['trash'])) {
            unset($actions['trash']);
        }
		if (isset($actions['approve'])) {
            unset($actions['approve']);
        }
		if (isset($actions['spam'])) {
            unset($actions['spam']);
        }
		if (isset($actions['unapprove'])) {
            unset($actions['unapprove']);
        }
		if (isset($actions['quickedit'])) {
            unset($actions['quickedit']);
        }
		if (isset($actions['edit'])) {
            unset($actions['edit']);
        }
    }
    return $actions;
}


/**
 * CSS CLASSES PAR USER ROLE
 */
add_filter( 'body_class', 'add_role_to_body_class' );
function add_role_to_body_class( $classes ) {
    $current_user = wp_get_current_user();
    $current_role = (array) $current_user->roles;

    if( $current_role[0] ){
        $classes[] = 'user-role-'.$current_role[0];
    }

    return $classes;
}

/**
 * CSS CLASSES PAR USER ROLE
 */

function remove_quick_edit( $actions ) {    
     unset($actions['edit']);
     unset($actions['trash']);
     unset($actions['view']);
     unset($actions['inline hide-if-no-js']);
     return $actions;
}

add_filter('post_row_actions','remove_quick_edit',10,1);



/**
 * DESACTIVER MESSAGE ALERTE FLOOD COMMENTAIRES
*/
// START REMOVE Duplicate COmments
add_filter('duplicate_comment_id', '__return_false');
//
// END REMOVE Duplicate comments
//
// START stop Going Too Fast on comments
//
add_filter('comment_flood_filter', '__return_false');
//
// END stop Going Too Fast on comments



/**
 * STATUT POSTS SUPPLEMENTAIRES CUSTOM POST STATUS
*/

if ( ! function_exists('custom_post_status_fiche_bloquee') ) {

// Register Custom Status
function custom_post_status_fiche_bloquee() {

	$args = array(
		'label'                     => _x( 'Fiche Bloquée', 'Status General Name', 'text_domain' ),
		'label_count'               => _n_noop( 'Fiche Bloquée (%s)',  'Fiches Bloquées (%s)', 'text_domain' ), 
		'public'                    => true,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'exclude_from_search'       => false,
	);
	register_post_status( 'fiche_bloquee', $args );

}
add_action( 'init', 'custom_post_status_fiche_bloquee', 0 );

}


if ( ! function_exists('custom_post_status_demande_resp') ) {

// Register Custom Status
function custom_post_status_demande_resp() {

	$args = array(
		'label'                     => _x( 'Demander auprès d’un Responsable si prestation possible', 'Status General Name', 'text_domain' ),
		'label_count'               => _n_noop( 'Demander auprès d’un Responsable si prestation possible (%s)',  'Demandez auprès d’un Responsable si prestation possible (%s)', 'text_domain' ), 
		'public'                    => true,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'exclude_from_search'       => false,
	);
	register_post_status( 'fiche_demande_resp', $args );

}
add_action( 'init', 'custom_post_status_demande_resp', 0 );

}


/**
 *AUGMENTER LE NOMBRE DE VARIATIONS PAR PAGE DANS L'ADMIN WOOCOMMERCE
*/
add_filter( 'woocommerce_admin_meta_boxes_variations_per_page', 'handsome_bearded_guy_increase_variations_per_page' );

function handsome_bearded_guy_increase_variations_per_page() {
	return 50;
}


/**
 * Forcer suite séquentielle entre 2 modeles de facture pdf invoice rednao pour templates 2 / 3 et 7 / 8
 */


add_filter( 'wcpdfi_get_latest_invoice_number', 'WooGroupNumberingGetLatestInvoiceNumber', 10, 2 );
add_filter( 'wcpdfi_update_latest_invoice_number', 'WooGroupNumberingUpdateLatestInvoiceNumber', 10, 2 );

function WooGroupNumberingGetLatestInvoiceNumber( $nextNumber, $invoiceId ) {
    if ( $invoiceId == 2 || $invoiceId == 3 ) {
        return get_option( 'wcpdfi_latest_invoice_number_template_2_3', 1 );
    } elseif ( $invoiceId == 7 || $invoiceId == 8 ) {
        return get_option( 'wcpdfi_latest_invoice_number_template_7_8', 1 );
    }

    return $nextNumber;
}

function WooGroupNumberingUpdateLatestInvoiceNumber( $nextNumber, $invoiceId ) {
    if ( $invoiceId == 2 || $invoiceId == 3 ) {
        $latest_invoice_number = get_option( 'wcpdfi_latest_invoice_number_template_2_3', 1 );
        $latest_invoice_number++;
        update_option( 'wcpdfi_latest_invoice_number_template_2_3', $latest_invoice_number );
        return $latest_invoice_number;
    } elseif ( $invoiceId == 7 || $invoiceId == 8 ) {
        $latest_invoice_number = get_option( 'wcpdfi_latest_invoice_number_template_7_8', 1 );
        $latest_invoice_number++;
        update_option( 'wcpdfi_latest_invoice_number_template_7_8', $latest_invoice_number );
        return $latest_invoice_number;
    }

    return $nextNumber;
}




// LIMITER ACCES BOUTONS REDNAO PLUGIN FACTURES COMPTA A ROLE COMPTA ET ADMIN ET LE STYLO EDITION NUMERO FACTURES

function hide_invoice_options() {
    if ( ! current_user_can( 'compta' ) && ! current_user_can( 'administrator' ) ) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.rnbtn, .svg-inline--fa').hide();
        });
        </script>
        <?php
    }
}
add_action( 'admin_footer', 'hide_invoice_options' );


// AJOUT DE LA PAGE FACTURATION DANS LE MENU POUR LES ROLES COMPTA ET ADMINISTRATEUR

function add_invoice_link_for_facturation() {
    if (current_user_can( 'compta' ) || current_user_can( 'administrator' )) {
        add_menu_page('Factures', 'Comptabilité', 'read', 'admin.php?page=PDFInvoiceBuilder%2Fwoocommerce-pdf-invoice.phpmanage_invoices', '', 'dashicons-calculator', 3);
    }
}
add_action( 'admin_menu', 'add_invoice_link_for_facturation' );


// AJOUT DU ROLE COMPTA AU ROLE ADMINISTRATEUR POUR ACCEDER AUX FACTURES SUITE AU CODE MODIFIE DU PLUGIN REDNAO INVOICE

$admins = get_users( array( 'role' => 'administrator' ) );
foreach ( $admins as $admin ) {
    $user = new WP_User( $admin->ID );
    $user->add_role( 'compta' );
}



// AJOUTER METABOX PAGE ADMIN COMMANDES AVEC LIENS VERS DB CF7 FORMULAIRES
add_action( 'add_meta_boxes', 'cf7oss_plugin_add_meta_box' );

function cf7oss_plugin_add_meta_box() {
    add_meta_box(
        'cf7oss-plugin-cf7-data',
        'Signatures et tampons',
        'cf7oss_plugin_render_meta_box',
        'shop_order',
        'side',
        'high'
    );
}

// GENERER LIENS vers cf7db POUR LES FORMULAIRES AVEC SIGNATURES DEPUIS COMMANDES
function cf7oss_plugin_render_meta_box( $post ) {
    $order = wc_get_order( $post->ID );
    if ( $order ) {
        $form_id_1 = '45012'; // Replace with the ID of the first CF7 form
        $form_id_2 = '259494'; // Replace with the ID of the second CF7 form
        $form_url_1 = admin_url( 'admin.php?page=cfdb7-list.php&fid=' . $form_id_1 . '&filter=Filter&s=' . $order->get_id() );
        $form_url_2 = admin_url( 'admin.php?page=cfdb7-list.php&fid=' . $form_id_2 . '&filter=Filter&s=' . $order->get_id() );
        ?>
        <p><a href="<?php echo $form_url_1; ?>" target="_blank">Signature devis</a></p>
        <p><a href="<?php echo $form_url_2; ?>" target="_blank">Signature convention</a></p>
        <?php
    }
}



// envoyer copie mails statuts commande e2pdf a referent fs basé sur le champ Metabox
add_action('woocommerce_order_status_changed', 'send_custom_status_change_email_to_referent', 10, 4);

function send_custom_status_change_email_to_referent($order_id, $from_status, $to_status, $order) {
    // Récupérer l'ID de l'utilisateur référent depuis la metabox
    $user_referent_id = get_post_meta($order_id, 'fsbdd_user_referentrel', true);

    if (!empty($user_referent_id)) {
        $user = get_user_by('id', $user_referent_id);
        if ($user && !empty($user->user_email)) {
            // Sauvegarder l'ancien nom de l'expéditeur
            $old_wp_mail_from_name = add_filter('wp_mail_from_name', function ($name) { return 'Commande FS'; });

            // Récupérer les informations de la commande
            $billing_company = $order->get_billing_company();
            $billing_first_name = $order->get_billing_first_name();
            $billing_last_name = $order->get_billing_last_name();

            // Préparer l'email
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $subject = 'Mise à jour commande "' . $order_id . ' ' . $billing_company . ' ' . $billing_first_name . ' ' . $billing_last_name . '"';
            $admin_url = admin_url('post.php?post=' . $order_id . '&action=edit');
            $body = '<p>Suivi de commande:</p>
                     <p>La commande n° <strong>' . $order_id . '</strong> de <strong>' . $billing_company . '</strong>, par <strong>' . $billing_first_name . ' ' . $billing_last_name . '</strong> a changé de statut:<br>
                     de "<strong>' . wc_get_order_status_name($from_status) . '</strong>" à "<strong>' . wc_get_order_status_name($to_status) . '</strong>"<br>
                     Vérifier le suivi depuis la page d\'administration: <a href="' . $admin_url . '">cliquer ICI</a></p>
                     <p>Merci</p>';
            $to = $user->user_email; // Envoyer seulement au référent

            // Envoyer l'email
            wp_mail($to, $subject, $body, $headers);

            // Restaurer l'ancien nom de l'expéditeur
            remove_filter('wp_mail_from_name', function ($name) { return 'Commande FS'; });
            add_filter('wp_mail_from_name', function ($name) use ($old_wp_mail_from_name) { return $old_wp_mail_from_name; });
        }
    }
    // Ne rien faire si aucun référent n'est spécifié
}


// CALCUL AUTOMATIQUE DE date echeance facturation fsbdd_datefinfact SUR LA PAGE DE COMMANDE WOOCOMMERCE
add_action('woocommerce_process_shop_order_meta', 'update_fsbdd_datefinfact', 20, 2);

function update_fsbdd_datefinfact($post_id, $post) {
    // Récupérer la commande
    $order = wc_get_order($post_id);
    if (!$order) {
        return;
    }

    // Récupérer la date "fsbdd_datefact"
    $date_fact = get_post_meta($post_id, 'fsbdd_datefact', true);
    if (empty($date_fact)) {
        return;
    }

    // Vérifier si "fsbdd_datefinfact" est déjà rempli manuellement
    $date_fin_fact_existing = get_post_meta($post_id, 'fsbdd_datefinfact', true);
    if (!empty($date_fin_fact_existing)) {
        return; // Ne pas écraser la valeur existante si elle a été définie manuellement
    }

    // Convertir la date au format PHP
    $date_fact_obj = DateTime::createFromFormat('d/m/Y', $date_fact);
    if (!$date_fact_obj) {
        return;
    }

    // Ajouter 28 jours à la date
    $date_fact_obj->modify('+28 days');

    // Mettre à jour la valeur du champ "fsbdd_datefinfact" avec le format 22/10/2024
    $date_fin_fact = $date_fact_obj->format('d/m/Y');
    update_post_meta($post_id, 'fsbdd_datefinfact', $date_fin_fact);
}


// DEPLACER ZIP E2PDF BULK EXPORT VERS DOSSIER PERMANENT PROGRAMMES PEDAGOGIQUES

add_action('e2pdf_controller_e2pdf_bulk_export_completed_zip', function ($zip, $bulk) {
    $template_id = $bulk->get('template_id');

    // Définissez le chemin d'accès au nouveau dossier où vous voulez déplacer le fichier ZIP et extraire son contenu
    $new_directory = ABSPATH . 'wp-content/uploads/programmes-pedagogiques-fs/';

    // Créez le nouveau dossier s'il n'existe pas
    if (!file_exists($new_directory)) {
        mkdir($new_directory, 0755, true);
    }

    // Déplacez le fichier ZIP vers le nouveau dossier
    $new_zip_path = $new_directory . basename($zip);
    if (rename($zip, $new_zip_path)) {

        // Extrait le contenu du fichier ZIP dans le nouveau dossier
        $zip = new ZipArchive;
        if ($zip->open($new_zip_path) === TRUE) {
            $zip->extractTo($new_directory);
            $zip->close();
            unlink($new_zip_path); // Supprime le fichier ZIP après extraction (optionnel)
            echo 'Fichier ZIP extrait avec succès';
        } else {
            echo 'Erreur lors de l\'ouverture du fichier ZIP';
        }
    } else {
        echo 'Erreur lors du déplacement du fichier ZIP';
    }
}, 10, 2);



// Charger le champs effectif depuis la quantité de produits dans le panier
add_action( 'woocommerce_admin_order_data_after_order_details', 'auto_fill_fsbdd_effectif_field_with_quantity' );

function auto_fill_fsbdd_effectif_field_with_quantity( $order ) {
    // Vérifier si nous sommes dans le backend et sur la page d'édition de commande
    if ( is_admin() && get_post_type() == 'shop_order' ) {
        $items = $order->get_items();
        $quantity = 0;
        foreach ( $items as $item ) {
            // Supposons qu'il n'y a qu'un seul produit dans la commande
            $quantity = $item->get_quantity();
            break; // Sortir de la boucle après le premier produit
        }

        // Injecter la quantité dans le champ via JavaScript
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Mettre à jour la valeur du champ 'fsbdd_effectif'
                $('#fsbdd_effectif').val('<?php echo $quantity; ?>');
            });
        </script>
        <?php
    }
}



// Ajouter une page d'administration personnalisée nommée "Pilotage" (sans lien dans le menu)
add_action('admin_menu', 'add_pilotage_page_without_menu');

function add_pilotage_page_without_menu() {
    add_menu_page(
        '',               // Ne pas afficher de titre dans le menu
        '',               // Ne pas afficher de nom dans le menu
        'read',           // Capacité requise (minimum pour autoriser l'accès au rôle "referent")
        'pilotage_page',  // Slug de la page
        'pilotage_page_with_metaboxes_callback', // Fonction de callback pour afficher le contenu de la page
        '',               // Icone (optionnel)
        99                // Position dans le menu
    );
}

// Vérifier l'accès à la page "Pilotage"
add_action('load-toplevel_page_pilotage_page', 'pilotage_page_access_control');

function pilotage_page_access_control() {
    // Obtenir l'utilisateur actuellement connecté
    $current_user = wp_get_current_user();

    // Vérifier les rôles de l'utilisateur
    if (!in_array('administrator', $current_user->roles) && !in_array('referent', $current_user->roles)) {
        wp_die(__('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.'));
    }
}

// Fonction de callback pour afficher les metaboxes sur la page "Pilotage"
function pilotage_page_with_metaboxes_callback() {
    global $wpdb;

    echo "<h1>Page de Pilotage</h1>";
    echo "<p>Liste des sessions dont le statut BOOKING est sur 'OUI'.</p>";

    // Récupérer les produits dont le champ 'fsbdd_sessconfirm' est sur '3' (OUI)
    $query = "
        SELECT post_id
        FROM {$wpdb->prefix}postmeta
        WHERE meta_key = 'fsbdd_sessconfirm'
        AND meta_value = '3'
    ";
    $product_ids = $wpdb->get_col($query);

    if (empty($product_ids)) {
        echo "<p>Aucune session trouvée avec le statut BOOKING sur 'OUI'.</p>";
        return;
    }

    // Ajouter des metaboxes pour chaque produit
    foreach ($product_ids as $product_id) {
        $product_title = get_the_title($product_id);
        $product_link = get_edit_post_link($product_id);

        echo "<h2><a href='" . esc_url($product_link) . "' target='_blank'>Produit ID: " . esc_html($product_id) . "</a></h2>";

        // Afficher les commandes liées au produit
        display_linked_orders_metabox($product_id);
    }
}

// Fonction pour afficher les commandes liées dans chaque metabox, seulement si le référent est l'utilisateur connecté
function display_linked_orders_metabox($product_id) {
    global $wpdb;

    $current_user_id = get_current_user_id(); // Récupérer l'ID de l'utilisateur connecté

    $query = "
        SELECT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items as order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        WHERE order_item_meta.meta_key = '_product_id'
        AND order_item_meta.meta_value = %d
    ";
    $order_ids = $wpdb->get_col($wpdb->prepare($query, $product_id));

    if (empty($order_ids)) {
        echo "<p>Aucune commande trouvée pour ce produit.</p>";
        return;
    }

    // Ajouter du style au tableau pour une meilleure lisibilité
    echo "<style>
        .linked-orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .linked-orders-table th, .linked-orders-table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .linked-orders-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .linked-orders-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>";

    echo "<table class='linked-orders-table'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>Commande</th>";
    echo "<th>Client</th>";
    echo "<th>Statut</th>";
    echo "<th>Effectif</th>";
    echo "<th>Référent</th>";
    echo "<th>Marge</th>";  // Colonne pour la marge
    echo "<th>Suivi réalisé</th>";  // Colonne pour le suivi réalisé
    echo "<th>Par</th>";  // Colonne pour "Par"
    echo "<th>Dates de Convocation</th>";  // Colonne pour les dates cochées "Oui"
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        $order_url = admin_url('post.php?post=' . absint($order_id) . '&action=edit');
        $customer_name = $order->get_formatted_billing_full_name();
        $order_status = wc_get_order_status_name($order->get_status());
        $effectif = get_post_meta($order_id, 'fsbdd_effectif', true);
        $user_referent_id = get_post_meta($order_id, 'fsbdd_user_referentrel', true);
        
        // Vérifier si l'utilisateur connecté est le référent
        if ($user_referent_id != $current_user_id) {
            continue; // Si ce n'est pas le référent, ne pas afficher cette commande
        }

        $user_referent = get_userdata($user_referent_id);
        $user_referent_firstname = isset($user_referent->first_name) ? $user_referent->first_name : '';

        // Récupération de la marge
        $marge = get_post_meta($order_id, 'fsbdd_marge', true);

        // Récupération du suivi réalisé
        $suivi_realise = get_post_meta($order_id, 'fsbdd_suivireal', true);

        // Récupération de l'ID utilisateur du champ "Par" et récupération du prénom
        $par_user_id = get_post_meta($order_id, 'fsbdd_refsuivi', true);
        $par_user = get_userdata($par_user_id);
        $par_firstname = isset($par_user->first_name) ? $par_user->first_name : '';

        // Récupérer les dates cochées "Oui" pour la convocation dans cette commande
        $convoc_dates = get_post_meta($order_id, 'fsbdd_convoc_dates', true);
        $convoc_dates = !empty($convoc_dates) ? $convoc_dates : 'Aucune';

        echo "<tr>";
        echo "<td><a href='{$order_url}' target='_blank'>{$order->get_order_number()}</a></td>";
        echo "<td>{$customer_name}</td>";
        echo "<td>{$order_status}</td>";
        echo "<td>{$effectif}</td>";
        echo "<td>{$user_referent_firstname}</td>";
        echo "<td>{$marge}</td>";  // Affichage de la marge
        echo "<td>{$suivi_realise}</td>";  // Affichage du suivi réalisé
        echo "<td>{$par_firstname}</td>";  // Affichage du prénom "Par"
        echo "<td>{$convoc_dates}</td>";  // Affichage des dates cochées "Oui"
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
}



// Récupérer les infos de session inter sur page commande quand MISE A JOUR DATES SESSIONS SI SAISIE MANUELLE ORDER
function fsbdd_update_order_item_meta($item_id, $values, $order_id) {
    if (is_admin()) {
        $order = wc_get_order($order_id);
        $item = $order->get_item($item_id);

        if ($item) {
            $product = $item->get_product();
            $product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();

            // Mettre à jour les métadonnées pour startdate et enddate
            $startdate = get_post_meta($product_id, 'we_startdate', true);
            $enddate = get_post_meta($product_id, 'we_enddate', true);

            if (!empty($startdate)) {
                $formatted_startdate = date('d/m/Y', $startdate);
                $item->update_meta_data('we_startdate', $formatted_startdate);
            }

            if (!empty($enddate)) {
                $formatted_enddate = date('d/m/Y', $enddate);
                $item->update_meta_data('we_enddate', $formatted_enddate);
            }

            // Mettre à jour les métadonnées pour les autres champs personnalisés si nécessaire
            $fsbdd_select_lieusession = get_post_meta($product_id, 'fsbdd_select_lieusession', true);

            if (!empty($fsbdd_select_lieusession)) {
                $item->update_meta_data('fsbdd_select_lieusession', $fsbdd_select_lieusession);
            }

            // Enregistrez les métadonnées mises à jour pour l'élément de commande
            $item->save();
        }
    }
}

add_action('woocommerce_ajax_add_order_item_meta', 'fsbdd_update_order_item_meta', 10, 3);


// trier les statuts woocoomerce par ordre alphabetique dans le menu deroulant des commandes
add_filter( 'wc_order_statuses', 'sort_order_statuses_alphabetically', 10, 1 );

function sort_order_statuses_alphabetically( $order_statuses ) {
    uasort( $order_statuses, 'strcasecmp' );
    return $order_statuses;
}


// Ajouter un lien "Priorités pilotage" dans la barre d'administration menu pilotage
add_action('admin_bar_menu', 'add_pilotage_to_admin_bar', 100);

function add_pilotage_to_admin_bar($admin_bar) {
    $admin_bar->add_menu(array(
        'id'    => 'pilotage',
        'title' => 'Priorités pilotage',
        'href'  => admin_url('admin.php?page=pilotage_page'), // Lien vers la page "Pilotage"
        'meta'  => array(
            'title' => __('Pilotage'),
        ),
    ));

    // Sous-menus existants
    $admin_bar->add_menu(array(
        'id'     => 'eliodata-clients-site',
        'parent' => 'pilotage',
        'title'  => 'Clients site',
        'href'   => 'https://formationstrategique.fr/wp-admin/users.php?role=customer',
        'meta'   => array(
            'title' => __('Clients site'),
        ),
    ));

    $admin_bar->add_menu(array(
        'id'     => 'eliodata-pilotage-formations',
        'parent' => 'pilotage',
        'title'  => 'Pilotage formations',
        'href'   => 'https://formationstrategique.fr/wp-admin/edit.php?layout=656842569b10b&post_type=shop_order',
        'meta'   => array(
            'title' => __('Pilotage formations'),
        ),
    ));

    $admin_bar->add_menu(array(
        'id'     => 'eliodata-calendrier-inter',
        'parent' => 'pilotage',
        'title'  => 'Calendrier INTER',
        'href'   => 'https://formationstrategique.fr/wp-admin/edit.php?layout=66a72f8181510&post_type=product',
        'meta'   => array(
            'title' => __('Calendrier INTER'),
        ),
    ));

    $admin_bar->add_menu(array(
        'id'     => 'eliodata-resultats-mensuels',
        'parent' => 'pilotage',
        'title'  => 'Résultats mensuels',
        'href'   => 'https://formationstrategique.fr/wp-admin/edit.php?layout=66ade2456f196&post_type=shop_order',
        'meta'   => array(
            'title' => __('Résultats mensuels'),
        ),
    ));
}


// Retirer le premier sous-menu "Priorités pilotage" (doublon du menu principal)
add_action('admin_head', 'remove_duplicate_submenu');

function remove_duplicate_submenu() {
    global $submenu;
    if (isset($submenu['pilotage'][0])) {
        unset($submenu['pilotage'][0]);
    }
}



// AJOUTER UNE ADMIN COLUMNS POUR AJOUTER CHAMPS RERLATIFS COMMANDES PRODUITS


// CHAMPS SESSION CONFIRMEE BOOKEE OU PAS
add_filter('ac/column/value', 'acp_modify_converted_fsbdd_sessconfirm_column_content', 10, 3);

function acp_modify_converted_fsbdd_sessconfirm_column_content($value, $id, $column) {
    if ($column->get_type() == 'column-meta' && $column->get_meta_key() == 'fsbdd_sessconfirm_placeholder') { 
        $order = wc_get_order($id);
        $items = $order->get_items();

        if (count($items) == 1) {
            $item = array_shift($items);
            $product_id = $item->get_product_id();
            $fsbdd_sessconfirm = get_post_meta($product_id, 'fsbdd_sessconfirm', true);
            
            if ($fsbdd_sessconfirm) {
                // Afficher la valeur du champ 'fsbdd_sessconfirm'
                return esc_html($fsbdd_sessconfirm);
            }
        }
    }
    return $value;
}



// CHAMPS WE STARTDATE

add_filter('ac/column/value', 'acp_modify_converted_startdate_column_content', 10, 3);

function acp_modify_converted_startdate_column_content($value, $id, $column) {
    if ($column->get_type() == 'column-meta' && $column->get_meta_key() == 'startdate_placeholder') { 
        $order = wc_get_order($id);
        $items = $order->get_items();

        if (count($items) == 1) {
            $item = array_shift($items);
            $product_id = $item->get_product_id();
            $start_date = get_post_meta($product_id, 'we_startdate', true);
            
            if ($start_date) {
                // Convertir le timestamp UNIX en date et stocker comme timestamp pour le tri
                return date('d/m/Y', $start_date);
            }
        }
    }
    return $value;
}


// CHAMPS WE ENDDATE

add_filter('ac/column/value', 'acp_modify_enddate_column_content', 10, 3);

function acp_modify_enddate_column_content($value, $id, $column) {
    if ($column->get_type() == 'column-meta' && $column->get_meta_key() == 'enddate_placeholder') { 
        $order = wc_get_order($id);
        $items = $order->get_items();

        if (count($items) == 1) {
            $item = array_shift($items);
            $product_id = $item->get_product_id();
            $end_date = get_post_meta($product_id, 'we_enddate', true);
            
            if ($end_date) {
                // Convertir le timestamp UNIX en date
                return date('d/m/Y', $end_date);
            }
        }
    }
    return $value;
}



// CHAMPS LIEU SESSION INTER PREDEFINI

add_filter('ac/column/value', 'acp_modify_lieusession_column_content', 10, 3);

function acp_modify_lieusession_column_content($value, $id, $column) {
    if ($column->get_type() == 'column-meta' && $column->get_meta_key() == 'lieusession_placeholder') { 
        $order = wc_get_order($id);
        $items = $order->get_items();

        if (count($items) == 1) {
            $item = array_shift($items);
            $product_id = $item->get_product_id();
            $lieusession = get_post_meta($product_id, 'fsbdd_select_lieusession', true);
            
            if ($lieusession) {
                return $lieusession;
            }
        }
    }
    return $value;
}


// MODIFIER AFFICHAGE CHAMP relationnel clients-wp-bdd METABOX.IO cote BDD clients
add_action( 'add_meta_boxes', 'add_link_to_wp_user_meta_box' );

function add_link_to_wp_user_meta_box() {
    add_meta_box(
        'link_to_wp_user_meta_box',     // ID unique
        'Lien vers le compte utilisateur WP', // Titre de la metabox
        'display_link_to_wp_user_meta_box',  // Fonction callback pour afficher le contenu
        'client',  // Le CPT auquel cette metabox est liée
        'side',    // Position de la metabox
        'high'     // Priorité de l'affichage
    );
}

function display_link_to_wp_user_meta_box( $post ) {
    global $wpdb;

    // Récupérer l'ID de l'utilisateur associé au CPT via la relation 'clients-wp-bdd'
    $client_id = $post->ID;
    $user_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT `from` FROM {$wpdb->prefix}mb_relationships WHERE `to` = %d AND `type` = 'clients-wp-bdd'",
        $client_id
    ));

    if ( !empty( $user_id ) ) {
        // Récupérer les informations de l'utilisateur WP
        $user = get_userdata( $user_id );
        $billing_company = get_user_meta( $user_id, 'billing_company', true );
        $edit_link = get_edit_user_link( $user_id );

        // Afficher un lien cliquable vers la page d'édition de l'utilisateur WP
        if ( !empty( $billing_company ) ) {
            echo '<p>Société : <a href="' . esc_url( $edit_link ) . '">' . esc_html( $billing_company ) . '</a></p>';
        } else {
            echo '<p>Aucun champ "billing_company" trouvé pour cet utilisateur.</p>';
        }
    } else {
        echo '<p>Aucun utilisateur lié trouvé.</p>';
    }
}




// CHAMP CLIENT RELATIONNEL AVEC METABOX

add_filter('ac/column/value', 'acp_modify_client_column_content', 10, 3);

function acp_modify_client_column_content($value, $id, $column) {
    global $wpdb;

    if ($column->get_type() == 'column-meta' && $column->get_meta_key() == 'relation_placeholder') {
        $order = wc_get_order($id);
        $customer_user_id = $order->get_customer_id();

        // Récupérez l'ID du client (custom post type) associé à l'utilisateur (client)
        $client_id = $wpdb->get_var($wpdb->prepare(
            "SELECT `to` FROM {$wpdb->prefix}mb_relationships WHERE `from` = %d AND `type` = 'clients-wp-bdd'",
            $customer_user_id
        ));

        if ($client_id) {
            // Récupérez les informations que vous souhaitez afficher du custom post type client
            $client_post = get_post($client_id);
            $edit_link = get_edit_post_link($client_id);
            return "<a href='{$edit_link}'>{$client_post->post_title}</a>"; // Crée un lien cliquable vers la page d'édition du custom post type client
        }
    }
    return $value;
}


// Fonction pour déterminer la classe CSS en fonction du niveau (champ personnalisé)
function get_niveau_class($niveau) {
    switch ($niveau) {
        case 'Moyen':
            return 'niveau-moyen';
        case 'Chaud':
            return 'niveau-chaud';
        case 'Froid':
            return 'niveau-froid';
        // Ajoutez d'autres conditions ici si nécessaire
        default:
            return '';
    }
}

// Fonction pour déterminer la classe CSS en fonction du statut de la commande
function get_status_class($status_slug) {
    switch ($status_slug) {
        case 'devisproposition':
            return 'statut-devisproposition';
        case 'inscription':
            return 'statut-inscription';
        case 'gplsquote-req':
            return 'statut-gplsquotereq';
        case 'preinscription':
            return 'statut-preinscription';
        case 'modifpreinscript':
            return 'statut-modifpreinscript';
        case 'certifreal':
            return 'statut-certifreal';
        case 'confirme':
            return 'statut-confirme';
		case 'avenantvalide':
            return 'statut-avenantvalide';
		case 'avenantconv':
            return 'statut-avenantconv';
		case 'factureok':
            return 'statut-factureok';
		case 'facturefsc':
            return 'statut-facturefsc';
		case 'facturesent':
            return 'statut-facturesent';
		case 'factureok':
            return 'statut-factureok';
		case 'facturation':
            return 'statut-facturation';
			// Ajoutez d'autres statuts ici si nécessaire
        default:
            return '';
    }
}


// Mettre ajour champ effectif total session selon les inscrits de toutes les commandes
add_action('save_post', 'update_fsbdd_effectifstage_for_product');

function update_fsbdd_effectifstage_for_product($post_id) {
    global $wpdb;
    
    // Vérifier si c'est bien une commande qui est sauvegardée
    if (get_post_type($post_id) !== 'shop_order') {
        return;
    }

    // Récupérer les items de la commande
    $order = wc_get_order($post_id);
    $items = $order->get_items();

    foreach ($items as $item_id => $item) {
        $product_id = $item->get_product_id();

        // Utiliser la requête SQL existante pour récupérer toutes les commandes pour ce produit
        $query = "
            SELECT order_items.order_id
            FROM {$wpdb->prefix}woocommerce_order_items as order_items
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
            WHERE order_item_meta.meta_key = '_product_id'
            AND order_item_meta.meta_value = %d
        ";
        $order_ids = $wpdb->get_col($wpdb->prepare($query, $product_id));

        $total_effectif = 0;

        // Calculer le total des 'fsbdd_effectif' pour chaque commande liée au produit
        foreach ($order_ids as $order_id) {
            $effectif = get_post_meta($order_id, 'fsbdd_effectif', true);
            $total_effectif += intval($effectif);
        }

        // Mettre à jour le champ 'fsbdd_effectifstage' pour le produit avec le total calculé
        update_post_meta($product_id, 'fsbdd_effectifstage', $total_effectif);
    }
}


// Fonction centrale pour mettre à jour le champ fsbdd_inscrits
function update_fsbdd_inscrits_for_product($product_id) {
    global $wpdb;

    // Statuts des commandes à prendre en compte
    $valid_statuses = ['confirme', 'certifreal','avenantvalide','avenantconv', 'facturefsc', 'facturesent', 'facturation', 'factureok'];

    // Récupérer toutes les commandes pour ce produit
    $query = "
        SELECT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items as order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        WHERE order_item_meta.meta_key = '_product_id'
        AND order_item_meta.meta_value = %d
    ";
    $order_ids = $wpdb->get_col($wpdb->prepare($query, $product_id));

    $total_inscrits = 0;

    foreach ($order_ids as $related_order_id) {
        $related_order = wc_get_order($related_order_id);
        // Continuer seulement si le statut de la commande est dans la liste des statuts valides
        if (in_array($related_order->get_status(), $valid_statuses)) {
            $effectif = get_post_meta($related_order_id, 'fsbdd_effectif', true);
            $total_inscrits += intval($effectif);
        }
    }

    // Mettre à jour le champ 'fsbdd_inscrits' pour le produit avec le total calculé
    update_post_meta($product_id, 'fsbdd_inscrits', $total_inscrits);
}

// Fonction pour mettre à jour tous les produits
function update_fsbdd_inscrits_for_all_products() {
    $products = get_posts(array(
        'post_type' => 'product',
        'posts_per_page' => -1,
    ));

    foreach ($products as $product) {
        update_fsbdd_inscrits_for_product($product->ID);
    }
}

// Action 1 : Mettre à jour le champ fsbdd_inscrits lors de la mise à jour d'une commande
add_action('woocommerce_update_order', 'update_fsbdd_inscrits_for_product_based_on_order');
function update_fsbdd_inscrits_for_product_based_on_order($order_id) {
    $order = wc_get_order($order_id);
    $items = $order->get_items();

    foreach ($items as $item) {
        $product_id = $item->get_product_id();
        update_fsbdd_inscrits_for_product($product_id);
    }
}

// Action 2 : Mettre à jour tous les produits lors du chargement de la page d'administration produit spécifique
add_action('current_screen', 'check_and_update_fsbdd_inscrits_on_admin_page_load');
function check_and_update_fsbdd_inscrits_on_admin_page_load() {
    $screen = get_current_screen();
    if ($screen->id == 'edit-product' && isset($_GET['layout']) && $_GET['layout'] === '65faada0f15dc') {
        update_fsbdd_inscrits_for_all_products();
    }
}





// RECUPERER ID PRODUIT POUR L'INSERER DANS LE CHAMP fsbdd_inter_numero DE METABOX POUR LES SESSIONS

add_action('save_post', 'update_custom_field_with_product_id', 10, 3);

function update_custom_field_with_product_id($post_ID, $post, $update) {
    // Vérifiez si le post est un produit
    if ('product' !== $post->post_type) {
        return;
    }

    // Vérifiez si le champ est vide avant de le mettre à jour
    $current_value = get_post_meta($post_ID, 'fsbdd_inter_numero', true);

    // Si le champ est vide, mettez à jour avec l'ID du produit
    if (empty($current_value)) {
        update_post_meta($post_ID, 'fsbdd_inter_numero', $post_ID);
    }
}



// ENREGISTRER DATE CHANGEMENT DE STATUT VERS INSCRIPTION, CONFIRME OU CONFIRMEMAIL, FACTURESENT ET FACTUREOK, COMPLETED-ORDER POUR L UTILISER DANS E2PDF SUR CONVENTIONS ETC

function save_status_change_date($order_id, $old_status, $new_status) {
    // Obtenez la date actuelle et formatez-la pour n'avoir que la date au format d/m/Y
    $formatted_date = date_i18n('d/m/Y');

    // Si le nouveau statut est "inscription", mettez à jour '_inscription_date'
    if ($new_status == 'inscription') {
        update_post_meta($order_id, '_inscription_date', $formatted_date);
    }
    
    // Si le nouveau statut est "confirme" ou "confirmemail", mettez à jour '_confirme_date'
    if (in_array($new_status, array('confirme'))) {
        update_post_meta($order_id, '_confirme_date', $formatted_date);
    }
    
    // Si le nouveau statut est "facturesent" ou "facturefsc", mettez à jour '_facturesent_date'
    if (in_array($new_status, array('facturesent', 'facturefsc'))) {
        update_post_meta($order_id, '_facturesent_date', $formatted_date);
    }
    
    // Si le nouveau statut est "factureok", mettez à jour '_factureok_date'
    if ($new_status == 'factureok') {
        update_post_meta($order_id, '_factureok_date', $formatted_date);
    }
}

add_action('woocommerce_order_status_changed', 'save_status_change_date', 10, 3);



// ENREGISTRER LES FORMATEURS DU PLANNING DES SESSIONS DANS DES CHAMPS PERSONNALISES TEXTES DEDIES

// Fonction pour sauvegarder les formateurs dans des champs personnalisés
function save_formateurs_to_custom_fields($post_id, $post, $update) {
    // Vérifiez si c'est bien un produit (ou ajustez pour votre type de post)
    if ($post->post_type != 'product') {
        return;
    }

    // Réinitialiser les champs personnalisés
    for ($i = 1; $i <= 7; $i++) {
        delete_post_meta($post_id, 'fsbdd_user_'.$i.'formateurrel');
    }

    // Vérifiez si le produit a un planning
    $planning = get_post_meta($post_id, 'fsbdd_planning', true);

    if ($planning && is_array($planning)) {
        $formateurs_uniques = [];

        // Parcourir chaque jour de planning
        foreach ($planning as $day) {
            // Vérifier s'il y a des formateurs pour ce jour
            if (isset($day['fsbdd_gpformatr']) && is_array($day['fsbdd_gpformatr'])) {
                foreach ($day['fsbdd_gpformatr'] as $formateur) {
                    if (!empty($formateur['fsbdd_user_formateurrel'])) {
                        $formateur_id = $formateur['fsbdd_user_formateurrel'];
                        // Enregistrer l'ID du formateur s'il n'est pas déjà dans le tableau
                        if (!in_array($formateur_id, $formateurs_uniques)) {
                            $formateurs_uniques[] = $formateur_id;
                        }
                    }
                }
            }
        }

        // Mettre à jour les champs personnalisés avec le prénom et le nom des formateurs
        $count = 1;
        foreach ($formateurs_uniques as $formateur_id) {
            if ($count > 7) { // Limite à 7 formateurs
                break;
            }
            $first_name = get_post_meta($formateur_id, 'first_name', true);
            $last_name = get_post_meta($formateur_id, 'last_name', true);
            $formateur_info = $first_name . ' ' . $last_name;
            update_post_meta($post_id, 'fsbdd_user_'.$count.'formateurrel', $formateur_info);
            $count++;
        }
    }
}

add_action('save_post', 'save_formateurs_to_custom_fields', 10, 3);





// ENREGISTRER LES CHANGEMENT D ETAT DES FORMATEUR DEPUIS LE PLANNING PRODUIT POUR STOCKER LES DATES CONTRAT ENVOYE EMARGEMENT
function save_date_when_etat_changes($post_id) {
    if (get_post_type($post_id) != 'product' || !isset($_POST['fsbdd_planning'])) {
        return;
    }

    global $wpdb;

    $current_date = date('d/m/Y');
    $planning = $_POST['fsbdd_planning'];

    $etats = ['Contrat envoyé', 'Contrat reçu', 'Emargement OK'];

    foreach ($planning as $day) {
        if (isset($day['fsbdd_gpformatr']) && is_array($day['fsbdd_gpformatr'])) {
            foreach ($day['fsbdd_gpformatr'] as $formateur) {
                if (!empty($formateur['fsbdd_user_formateurrel']) && !empty($formateur['fsbdd_okformatr'])) {
                    $etat = $formateur['fsbdd_okformatr'];
                    $formateur_id = $formateur['fsbdd_user_formateurrel'];

                    if (in_array($etat, $etats)) {
                        $meta_key = 'date_' . sanitize_title($etat) . '_' . $formateur_id;
                        $existing_date = get_post_meta($post_id, $meta_key, true);

                        if (empty($existing_date)) {
                            update_post_meta($post_id, $meta_key, $current_date);
                        }
                    }
                }
            }
        }
    }
}

add_action('save_post', 'save_date_when_etat_changes', 10, 2);


// REMPLISSAGE AUTOMATIQUE DE fsbdd_numcmmde AVEC L'ID DE LA COMMANDE SI LE CHAMP EST VIDE
add_action('woocommerce_update_order', 'remplir_num_commande_si_vide');

function remplir_num_commande_si_vide($order_id) {
    // Vérifier que l'ID de la commande est valide
    if (!$order_id) return;

    // Récupérer la valeur actuelle du champ "fsbdd_numcmmde"
    $num_commande_actuel = get_post_meta($order_id, 'fsbdd_numcmmde', true);

    // Si le champ est vide, le remplir avec l'ID de la commande
    if (empty($num_commande_actuel)) {
        $numero_commande = strval($order_id);
        update_post_meta($order_id, 'fsbdd_numcmmde', $numero_commande);
    }
}



// BLOQUER INDEXATION YOAST ROBOTS RECHERCHE POUR PRODUITS CATEGORIES FACTURATION ET FORMATION PASSEE
add_filter('wpseo_robots', function($robots) {
    if (is_product() && has_term(array(352, 336), 'product_cat')) {
        return 'noindex, nofollow';
    }
    return $robots;
});



/**
 * AFFICHER DATES DU JOUR PAR SHORTCODE 
 
// PLACER LES PRODUITS SESSIONS DANS LE CHAMP PERSONNALISÉ AVEC L'ID "fsbdd_typesession" À "INTER" SI CERTAINS MOTS SONT PRÉSENTS DANS LE TITRE DU PRODUIT
function ajouter_produit_champ_personnalise( $post_id ) {
    $mots_cles = array('montpellier', 'nimes', 'ales', 'mudaison', 'bagard');
    $titre_produit = strtolower(get_the_title( $post_id ));
    $champ_personnalise_id = 'fsbdd_typesession';
    $valeur_a_ajouter = 'INTER';

    // Récupérer la valeur actuelle du champ personnalisé
    $valeur_actuelle = get_post_meta($post_id, $champ_personnalise_id, true);

    // Si le titre contient un des mots clés et le champ ne contient pas déjà la valeur, ajouter la valeur
    if( !strstr($valeur_actuelle, $valeur_a_ajouter) && preg_match('/\b('.implode('|', $mots_cles).')\b/i', $titre_produit) ) {
        update_post_meta($post_id, $champ_personnalise_id, $valeur_a_ajouter);
    } 
    // Si le titre ne contient pas de mot clé et le champ contient déjà la valeur, supprimer la valeur
    elseif( strstr($valeur_actuelle, $valeur_a_ajouter) && !preg_match('/\b('.implode('|', $mots_cles).')\b/i', $titre_produit) ) {
        delete_post_meta($post_id, $champ_personnalise_id, $valeur_a_ajouter);
    }
}
add_action( 'save_post', 'ajouter_produit_champ_personnalise' );
*/