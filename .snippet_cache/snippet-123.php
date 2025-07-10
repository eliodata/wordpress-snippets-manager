<?php
/**
 * Snippet ID: 123
 * Name: Liste produits catalogue page commande pour modifier commande action session
 * Description: 
 * @active false
 */


/**
 * AJOUTER UNE METABOX POUR RECHERCHER TOUS LES PRODUITS VISIBLES
 * ET REMPLACER LE CONTENU DE LA COMMANDE AVEC LE PRODUIT SÉLECTIONNÉ
 */

// 1. Ajout de la metabox
add_action( 'add_meta_boxes', 'add_replace_product_meta_box' );
function add_replace_product_meta_box() {
    add_meta_box(
        'replace_product_meta_box',
        'Remplacer par un produit visible',
        'display_replace_product_meta_box',
        'shop_order',
        'side',
        'default'
    );
}

// 2. Affichage du formulaire dans la metabox
function display_replace_product_meta_box( $post ) {
    ?>
    <form id="replace_product_form">
        <input
            type="text"
            id="replace_product_search_input"
            placeholder="Saisir au moins 2 caractères..."
            style="width: 100%;"
        />
        <select
            name="replace_product_id"
            id="replace_product_select"
            style="width: 100%; display: none; margin-top: 6px;"
        ></select>
        <input
            type="hidden"
            name="order_id"
            value="<?php echo esc_attr( $post->ID ); ?>"
        />
        <button
            type="button"
            class="button"
            id="replace_product_in_order"
            style="display: none; margin-top: 6px;"
        >
            Remplacer
        </button>
    </form>
    <?php
}

// 3. JS pour la recherche et le remplacement AJAX
add_action( 'admin_footer', 'replace_product_meta_box_js' );
function replace_product_meta_box_js() {
    // On restreint l'injection du JS à la page "shop_order" (édition d’une commande)
    $screen = get_current_screen();
    if ( 'shop_order' !== $screen->id ) {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var typingTimer;
        var doneTypingInterval = 300; // Délai avant d'exécuter la recherche après la saisie

        // Recherche dynamique
        $('#replace_product_search_input').on('keyup', function() {
            clearTimeout(typingTimer);
            var searchTerm = $(this).val();

            if (searchTerm.length >= 2) {
                typingTimer = setTimeout(function() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'search_visible_catalog_products',
                            search_term: searchTerm
                        },
                        success: function(response) {
                            var $select = $('#replace_product_select');
                            $select.empty();

                            if (response.data && response.data.length > 0) {
                                $select.show();
                                $('#replace_product_in_order').show();
                                $.each(response.data, function(index, product) {
                                    $select.append(
                                        '<option value="' + product.id + '">' + product.name + '</option>'
                                    );
                                });
                            } else {
                                $select.hide();
                                $('#replace_product_in_order').hide();
                            }
                        }
                    });
                }, doneTypingInterval);
            } else {
                $('#replace_product_select').hide();
                $('#replace_product_in_order').hide();
            }
        });

        // Remplacement du produit dans la commande
        $('#replace_product_in_order').on('click', function() {
            var data = {
                action: 'replace_product_in_order',
                order_id: $('[name="order_id"]').val(),
                product_id: $('#replace_product_select').val()
            };

            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    alert('Produit remplacé avec succès !');
                    location.reload(); // Recharger pour voir le changement
                } else {
                    alert('Erreur lors du remplacement du produit.');
                }
            });
        });
    });
    </script>
    <?php
}

// 4. AJAX : recherche de tous les produits visibles dans le catalogue
add_action( 'wp_ajax_search_visible_catalog_products', 'handle_search_visible_catalog_products' );
function handle_search_visible_catalog_products() {
    $search_term = isset( $_POST['search_term'] ) ? sanitize_text_field( $_POST['search_term'] ) : '';

    if ( empty( $search_term ) ) {
        wp_send_json_error();
    }

    // On filtre les produits qui ont pour meta _visibility = 'visible' ou 'catalog'
    $args = array(
        'post_type'      => array( 'product', 'product_variation' ),
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
        'posts_per_page' => -1,
        's'              => $search_term,
        'meta_query'     => array(
            array(
                'key'     => '_visibility',
                'value'   => array( 'visible', 'catalog' ),
                'compare' => 'IN',
            ),
        ),
    );

    $products = get_posts( $args );
    $result   = array();

    foreach ( $products as $product ) {
        $wc_product = wc_get_product( $product->ID );
        if ( $wc_product->is_type( 'variation' ) ) {
            $parent               = wc_get_product( $wc_product->get_parent_id() );
            $variation_attributes = wc_get_formatted_variation( $wc_product->get_variation_attributes(), true );
            $result[] = array(
                'id'   => $product->ID,
                'name' => $parent->get_name() . ' - ' . $variation_attributes,
            );
        } else {
            $result[] = array(
                'id'   => $product->ID,
                'name' => $product->post_title,
            );
        }
    }

    if ( ! empty( $result ) ) {
        wp_send_json_success( $result );
    } else {
        wp_send_json_error();
    }
}

// 5. AJAX : remplacer tous les articles d’une commande par le nouveau produit
add_action( 'wp_ajax_replace_product_in_order', 'handle_replace_product_in_order' );
function handle_replace_product_in_order() {
    $order_id   = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

    if ( ! $order_id || ! $product_id ) {
        wp_send_json_error();
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        wp_send_json_error();
    }

    // Supprime tous les articles existants
    foreach ( $order->get_items() as $item_id => $item ) {
        $order->remove_item( $item_id );
    }
    $order->save();

    // Ajoute le nouveau produit (quantité = 1, à ajuster selon vos besoins)
    $order->add_product( wc_get_product( $product_id ), 1 );
    $order->calculate_totals();
    $order->save();

    wp_send_json_success();
}
