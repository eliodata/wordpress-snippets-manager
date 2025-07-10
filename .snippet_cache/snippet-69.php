<?php
/**
 * Snippet ID: 69
 * Name: MENU SELECT PAGES COMMANDES LISTE PRODUITS CIEL ET SIMILAIRES
 * Description: 
 * @active false
 */

// AFFICHER PRODUITS SIMILAIRES AUTRES SESSIONS SUR PAGE EDITION COMMANDE POUR CHANGER DATE
// Hook into the admin_init action to add our meta box
add_action( 'add_meta_boxes', 'add_similar_products_meta_box' );

// Hook into admin_footer to add JavaScript for AJAX request
add_action( 'admin_footer', 'add_similar_products_meta_box_js' );

function add_similar_products_meta_box() {
    add_meta_box( 'similar_products_meta_box', 'Autres options disponibles', 'display_similar_products_meta_box', 'shop_order', 'side', 'default' );
}

function display_similar_products_meta_box( $post ) {
    $order = wc_get_order( $post->ID );
    $items = $order->get_items();

    // Vérifier s'il y a au moins un produit dans la commande
    if ( empty( $items ) ) {
        echo '<p>Aucun produit dans cette commande.</p>';
        return;
    }

    // Récupérer le premier produit de la commande
    $first_item = reset( $items );
    $product = wc_get_product( $first_item->get_product_id() );

    if ( ! $product ) {
        echo '<p>Impossible de trouver le produit associé à cet article de commande.</p>';
        return;
    }

    // Rechercher le titre du produit sans le caractère à ignorer
    $search_title = $product->get_title();
    $ignore_character = '>';
    $ignore_index = strpos( $search_title, $ignore_character );
    if ( $ignore_index !== false ) {
        $search_title = substr( $search_title, 0, $ignore_index );
    }

    $args = array(
        'post_type'      => array('product', 'product_variation'),
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'posts_per_page' => -1,
        's'              => $search_title,
    );

    $similar_products = get_posts( $args );

    if ( empty( $similar_products ) ) {
        echo '<p>Aucun produit similaire trouvé.</p>';
        return;
    }

    echo '<form id="add_similar_product_form">';
    echo '<select name="similar_product_id">';
    foreach ( $similar_products as $similar_product ) {
        $product_obj = wc_get_product( $similar_product->ID );
        
        // Récupérer les catégories associées au produit ou à sa variation
        $product_categories = wp_get_post_terms( 
            $product_obj->is_type('variation') ? $product_obj->get_parent_id() : $similar_product->ID, 
            'product_cat', 
            array('fields' => 'ids') 
        );

        // Vérifier si le produit n'est pas dans la catégorie 336
        if ( !in_array( 336, $product_categories ) ) {
            $product_name = $product_obj->is_type('variation') ? wc_get_product($product_obj->get_parent_id())->get_name() : $product_obj->get_name();

            if ( stripos($product_name, 'définir') === false ) {
if ($product_obj->is_type('variation')) {
    $parent = wc_get_product($product_obj->get_parent_id());
    $variation_attributes = wc_get_formatted_variation($product_obj->get_variation_attributes(), true);
    // Simplifier l'affichage de la variation
    $simplified_attributes = str_replace(array('Catégorie(s):', 'Niveau:'), '', $variation_attributes);
    echo '<option value="' . esc_attr( $similar_product->ID ) . '">' . esc_html( $simplified_attributes ) . '</option>';
} else {
    echo '<option value="' . esc_attr( $similar_product->ID ) . '">' . esc_html( $similar_product->post_title ) . '</option>';
}

            }
        }
    }
    echo '</select>';
    echo '<input type="hidden" name="order_id" value="' . esc_attr( $post->ID ) . '">';
    echo '<button type="button" class="button" id="add_similar_product_to_order">Ajouter à la commande</button>';
    echo '</form>';
}

function add_similar_products_meta_box_js() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#add_similar_product_to_order').on('click', function() {
                var data = {
                    'action': 'add_similar_product_to_order',
                    'order_id': $('[name="order_id"]').val(),
                    'product_id': $('[name="similar_product_id"]').val()
                };

                $.post(ajaxurl, data, function(response) {
                    alert('Produit ajouté à la commande !');
                    location.reload(); // Recharger la page pour mettre à jour la liste des articles de commande
                });
            });
        });
    </script>
    <?php
}

// Handle AJAX request to add selected product to order
add_action( 'wp_ajax_add_similar_product_to_order', 'handle_add_similar_product_to_order' );

function handle_add_similar_product_to_order() {
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    if ( $order_id && $product_id ) {
        $order = wc_get_order( $order_id );
        $product = wc_get_product( $product_id );

        if ( $order && $product ) {
            // Ajouter le produit à la commande et obtenir le nouvel ID d'article de commande
            $item_id = $order->add_product( $product, 1 ); // Ajouter une unité du produit

            // Récupérer les métadonnées du produit
            $product_meta = $product->get_meta_data();

            // Ajouter les métadonnées essentielles à l'article de commande
            $order_item = new WC_Order_Item_Product( $item_id );
            foreach ( $product_meta as $meta ) {
                // Filtrer pour inclure uniquement les métadonnées essentielles
                if ( in_array( $meta->key, ['essential_meta_1', 'essential_meta_2', 'essential_meta_3'] ) ) {
                    $order_item->add_meta_data( $meta->key, $meta->value );
                }
            }
            $order_item->save();

            $order->calculate_totals();
            $order->save();
        }
    }

    wp_die(); // Terminer la requête AJAX
}



// AFFICHER PRODUITS IMPORT CIEL FACTURATION SUR PAGE EDITION COMMANDE
// Hook pour ajouter la metabox sur la page de l'édition de commande
add_action( 'add_meta_boxes', 'add_dynamic_search_products_meta_box' );

// Hook pour ajouter le JavaScript à la fin de la page admin pour la requête AJAX
add_action( 'admin_footer', 'add_dynamic_search_products_meta_box_js' );

function add_dynamic_search_products_meta_box() {
    add_meta_box( 'dynamic_search_products_meta_box', 'Import Factures CIEL', 'display_dynamic_search_products_meta_box', 'shop_order', 'side', 'default' );
}

function display_dynamic_search_products_meta_box( $post ) {
    echo '<form id="dynamic_search_product_form">';
    echo '<input type="text" id="product_search_input" placeholder="Saisir 2 caractères minimum..." />';
    echo '<select name="dynamic_product_id" id="dynamic_product_select" style="width: 100%; display: none;"></select>';
    echo '<input type="hidden" name="order_id" value="' . esc_attr( $post->ID ) . '">';
    echo '<button type="button" class="button" id="add_dynamic_product_to_order" style="display: none;">Ajouter à la commande</button>';
    echo '</form>';
}

function add_dynamic_search_products_meta_box_js() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var typingTimer;
            var doneTypingInterval = 300; // Délai après la saisie avant d'exécuter la recherche

            // Événement lors de la saisie dans le champ de recherche
            $('#product_search_input').on('keyup', function() {
                clearTimeout(typingTimer);
                var searchTerm = $(this).val();

                // Exécuter la recherche seulement après 2 caractères saisis
                if (searchTerm.length >= 2) {
                    typingTimer = setTimeout(function() {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                'action': 'search_products_by_category',
                                'search_term': searchTerm,
                                'category_id': 353
                            },
                            success: function(response) {
                                var $select = $('#dynamic_product_select');
                                $select.empty();

                                if (response.data && response.data.length > 0) {
                                    $select.show();
                                    $('#add_dynamic_product_to_order').show();
                                    $.each(response.data, function(index, product) {
                                        $select.append('<option value="' + product.id + '">' + product.name + '</option>');
                                    });
                                } else {
                                    $select.hide();
                                    $('#add_dynamic_product_to_order').hide();
                                }
                            }
                        });
                    }, doneTypingInterval);
                } else {
                    $('#dynamic_product_select').hide();
                    $('#add_dynamic_product_to_order').hide();
                }
            });

            // Ajouter le produit sélectionné à la commande avec la même action que le snippet existant
            $('#add_dynamic_product_to_order').on('click', function() {
                var data = {
                    'action': 'add_similar_product_to_order', // Utilise l'action existante
                    'order_id': $('[name="order_id"]').val(),
                    'product_id': $('#dynamic_product_select').val()
                };

                $.post(ajaxurl, data, function(response) {
                    alert('Produit ajouté à la commande !');
                    location.reload(); // Recharger la page pour mettre à jour la liste des articles de commande
                });
            });
        });
    </script>
    <?php
}

// Handle AJAX request to search products
add_action( 'wp_ajax_search_products_by_category', 'handle_search_products_by_category' );

function handle_search_products_by_category() {
    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

    if ( ! empty( $search_term ) && $category_id ) {
        // Arguments pour rechercher les produits correspondants dans la catégorie spécifiée
        $args = array(
            'post_type'      => array('product', 'product_variation'),
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
            'posts_per_page' => -1,
            's'              => $search_term,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'id',
                    'terms'    => $category_id,
                ),
            ),
        );

        $products = get_posts( $args );
        $result = array();

        foreach ( $products as $product ) {
            $product_obj = wc_get_product( $product->ID );
            if ( $product_obj->is_type('variation') ) {
                $parent = wc_get_product( $product_obj->get_parent_id() );
                $variation_attributes = wc_get_formatted_variation( $product_obj->get_variation_attributes(), true );
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

        wp_send_json_success( $result );
    }

    wp_send_json_error();
}