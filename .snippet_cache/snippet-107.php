<?php
/**
 * Snippet ID: 107
 * Name: Champ durée heures sur variations produits woocommerce
 * Description: 
 * @active true
 */


/**
 * GESTION DES MÉTADONNÉES "DURÉE" POUR LES VARIATIONS DE PRODUIT WooCommerce
 */

/* --- Partie Admin : Ajout et Sauvegarde des Champs Personnalisés --- */

/**
 * Afficher les champs personnalisés dans l'interface d'édition des variations
 */
add_action( 'woocommerce_variation_options_pricing', 'custom_variation_fields', 10, 3 );
function custom_variation_fields( $loop, $variation_data, $variation ) {
    woocommerce_wp_text_input( array(
        'id'                => 'fsbdd_dureevarheures[' . $variation->ID . ']',
        'label'             => __( 'Durée en Heures', 'woocommerce' ),
        'desc_tip'          => 'true',
        'description'       => __( 'Entrez la durée en heures pour cette variation.', 'woocommerce' ),
        'type'              => 'number',
        'custom_attributes' => array(
            'step' => '1',
            'min'  => '0'
        ),
        'value'             => get_post_meta( $variation->ID, 'fsbdd_dureevarheures', true )
    ) );

    woocommerce_wp_text_input( array(
        'id'                => 'fsbdd_dureevarjours[' . $variation->ID . ']',
        'label'             => __( 'Durée en Jours', 'woocommerce' ),
        'desc_tip'          => 'true',
        'description'       => __( 'Entrez la durée en jours pour cette variation.', 'woocommerce' ),
        'type'              => 'number',
        'custom_attributes' => array(
            'step' => '1',
            'min'  => '0'
        ),
        'value'             => get_post_meta( $variation->ID, 'fsbdd_dureevarjours', true )
    ) );
}

/**
 * Sauvegarder les champs personnalisés lorsque la variation est enregistrée
 */
add_action( 'woocommerce_save_product_variation', 'custom_save_variation_fields', 10, 2 );
function custom_save_variation_fields( $variation_id, $i ) {
    // Vérifier et sauvegarder la durée en heures
    if ( isset( $_POST['fsbdd_dureevarheures'][ $variation_id ] ) ) {
        $heures = intval( $_POST['fsbdd_dureevarheures'][ $variation_id ] );
        update_post_meta( $variation_id, 'fsbdd_dureevarheures', $heures );
    }

    // Vérifier et sauvegarder la durée en jours
    if ( isset( $_POST['fsbdd_dureevarjours'][ $variation_id ] ) ) {
        $jours = intval( $_POST['fsbdd_dureevarjours'][ $variation_id ] );
        update_post_meta( $variation_id, 'fsbdd_dureevarjours', $jours );
    }
}

/**
 * Charger les valeurs sauvegardées lors de l'édition des variations
 */
add_action( 'woocommerce_available_variation', 'custom_load_variation_fields', 10, 3 );
function custom_load_variation_fields( $variation_data, $product, $variation ) {
    $variation_data['fsbdd_dureevarheures'] = get_post_meta( $variation->get_id(), 'fsbdd_dureevarheures', true );
    $variation_data['fsbdd_dureevarjours']  = get_post_meta( $variation->get_id(), 'fsbdd_dureevarjours',  true );
    return $variation_data;
}

/* --- Partie Front-End : Affichage et Transfert des Métadonnées --- */

/**
 * AFFICHE LES DONNÉES (HEURES / JOURS) DANS LA DESCRIPTION DE LA VARIATION - FRONT
 */
add_action( 'wp_footer', 'custom_ajouter_durees_variation_script' );
function custom_ajouter_durees_variation_script() {
    if ( ! is_product() ) return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($){

        $('form.variations_form').on('show_variation', function(event, variation) {
            var heures = variation.fsbdd_dureevarheures;
            var jours  = variation.fsbdd_dureevarjours;

            // Singulier / Pluriel
            var labelHeures = ( heures == 1 ) ? 'heure' : 'heures';
            var labelJours  = ( jours == 1 ) ? 'jour'  : 'jours';

            var html = '';
            if( heures && jours ) {
                html = '<p class="duree-variation"><strong>Durée :</strong> ' + heures + ' ' + labelHeures + ' (' + jours + ' ' + labelJours + ')</p>';
            } else if( heures ) {
                html = '<p class="duree-variation"><strong>Durée :</strong> ' + heures + ' ' + labelHeures + '</p>';
            } else if( jours ) {
                html = '<p class="duree-variation"><strong>Durée :</strong> (' + jours + ' ' + labelJours + ')</p>';
            }

            // Nettoyage et insertion
            $('.woocommerce-variation-description').find('p.duree-variation').remove();
            if( html ) {
                $('.woocommerce-variation-description').append(html);
            }
        });

        // Supprimer la durée si la variation est réinitialisée
        $('form.variations_form').on('hide_variation reset_data', function() {
            $('.woocommerce-variation-description').find('p.duree-variation').remove();
        });
    });
    </script>
    <?php
}

/**
 * AJOUTER LES INFOS DANS LE CART (PANIER)
 */
add_filter( 'woocommerce_add_cart_item_data', 'custom_add_cart_item_data', 10, 3 );
function custom_add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
    if ( $variation_id > 0 ) {
        $heures = get_post_meta( $variation_id, 'fsbdd_dureevarheures', true );
        $jours  = get_post_meta( $variation_id, 'fsbdd_dureevarjours',  true );
        if ( ! empty( $heures ) || ! empty( $jours ) ) {
            $cart_item_data['custom_duree_variation'] = array(
                'heures' => $heures,
                'jours'  => $jours,
            );
        }
    }
    return $cart_item_data;
}

/**
 * AFFICHER LES INFOS "DURÉE" DANS LE PANIER ET LE CHECKOUT
 */
add_filter( 'woocommerce_get_item_data', 'custom_display_duree_cart_checkout', 10, 2 );
function custom_display_duree_cart_checkout( $item_data, $cart_item ) {
    if ( isset( $cart_item['custom_duree_variation'] ) ) {
        $heures = $cart_item['custom_duree_variation']['heures'];
        $jours  = $cart_item['custom_duree_variation']['jours'];

        $labelHeures = ( $heures == 1 ) ? 'heure' : 'heures';
        $labelJours  = ( $jours == 1 ) ? 'jour'  : 'jours';

        $display_value = '';
        if ( $heures && $jours ) {
            $display_value = $heures . ' ' . $labelHeures . ' (' . $jours . ' ' . $labelJours . ')';
        } elseif ( $heures ) {
            $display_value = $heures . ' ' . $labelHeures;
        } elseif ( $jours ) {
            $display_value = '(' . $jours . ' ' . $labelJours . ')';
        }

        if ( $display_value ) {
            $item_data[] = array(
                'key'   => __( 'Durée', 'woocommerce' ),
                'value' => wc_clean( $display_value ),
            );
        }
    }
    return $item_data;
}

/**
 * TRANSFÉRER LES INFOS "DURÉE" DU PANIER VERS LA COMMANDE
 */
add_action( 'woocommerce_add_order_item_meta', 'custom_add_order_item_meta_duree', 10, 3 );
function custom_add_order_item_meta_duree( $item_id, $values, $cart_item_key ) {
    if ( isset( $values['custom_duree_variation'] ) ) {
        $heures = $values['custom_duree_variation']['heures'];
        $jours  = $values['custom_duree_variation']['jours'];

        $labelHeures = ( $heures == 1 ) ? 'heure' : 'heures';
        $labelJours  = ( $jours == 1 ) ? 'jour'  : 'jours';

        $display_value = '';
        if ( $heures && $jours ) {
            $display_value = $heures . ' ' . $labelHeures . ' (' . $jours . ' ' . $labelJours . ')';
        } elseif ( $heures ) {
            $display_value = $heures . ' ' . $labelHeures;
        } elseif ( $jours ) {
            $display_value = '(' . $jours . ' ' . $labelJours . ')';
        }

        if ( $display_value ) {
            wc_add_order_item_meta( $item_id, 'Durée', $display_value );
        }
    }
}

/**
 * AFFICHER LA MÉTADONNÉE "DURÉE" DANS LES EMAILS
 */
add_filter( 'woocommerce_email_order_meta_fields', 'custom_email_order_meta_fields_duree' , 10, 3 );
function custom_email_order_meta_fields_duree( $fields, $sent_to_admin, $order ) {
    // Cette clé "Durée" doit correspondre au label donné dans wc_add_order_item_meta
    $fields['Durée'] = array(
        'label' => __( 'Durée', 'woocommerce' ),
        'value' => '', // la valeur sera détectée automatiquement
    );
    return $fields;
}
