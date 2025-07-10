<?php
/**
 * Snippet ID: 39
 * Name: AFFICHAGE texte avant prix variations et afficher devis si prix zero 0
 * Description: 
 * @active true
 */


// AJOUTER TEXTE AVANT PRIX VARIATIONS

add_filter( 'woocommerce_get_price_html', 'cw_change_product_price_display' );
add_filter( 'woocommerce_cart_item_price', 'cw_change_product_price_display' );
function cw_change_product_price_display( $price ) {
    // Your additional text in a translatable string
    $text = __('Prix public, selon options et prise en charge: ');

    // returning the text before the price
    return $text . ' ' . $price;
}



// Remplacer le prix par "devis" quand il est à 0
// 1. Page Produit (prix HTML) - produits simples/variations
add_filter('woocommerce_get_price_html', 'replace_zero_price_with_devis', 999, 2);
function replace_zero_price_with_devis($price, $product) {
    if ( (float) $product->get_price() <= 0 ) {
        return '<span class="devis-text">Sur demande</span>';
    }
    return $price;
}

// 2. Variation (ex: "A partir de X €" sur un produit variable)
add_filter('woocommerce_variation_price_html', 'replace_zero_price_variations', 999, 2);
add_filter('woocommerce_variation_sale_price_html', 'replace_zero_price_variations', 999, 2);
function replace_zero_price_variations($price, $variation) {
    if ( (float) $variation->get_price() <= 0 ) {
        return '<span class="devis-text">Sur demande</span>';
    }
    return $price;
}

// 3. Panier : afficher "devis" si prix = 0
add_filter('woocommerce_cart_item_price', 'replace_zero_price_in_cart', 999, 3);
add_filter('woocommerce_cart_item_subtotal', 'replace_zero_price_in_cart', 999, 3);
function replace_zero_price_in_cart($price, $cart_item, $cart_item_key) {
    if ( (float) $cart_item['data']->get_price() <= 0 ) {
        return '<span class="devis-text">Sur demande</span>';
    }
    return $price;
}

// 4. Commande / Emails : ligne de sous-total
add_filter('woocommerce_order_formatted_line_subtotal', 'replace_zero_price_in_order', 999, 3);
function replace_zero_price_in_order($subtotal, $item, $order) {
    // On peut aussi vérifier get_total() selon le besoin
    if ( (float) $item->get_subtotal() <= 0 ) {
        return '<span class="devis-text">Sur demande</span>';
    }
    return $subtotal;
}


/**
 * 1) Filtre : Remplacer le total du panier par "Sur demande" 
 *    si le total est 0 ou si un produit est à 0.
 */
add_filter('woocommerce_cart_totals_order_total_html', 'override_cart_total_if_any_zero', 999);
function override_cart_total_if_any_zero($cart_html) {
    // Récupérer le cart
    $cart = WC()->cart;
    if ( ! $cart ) {
        return $cart_html;
    }

    // Si total = 0 ou s'il y a au moins un item à 0, on écrase l'affichage
    if ( (float) $cart->get_total('edit') <= 0.0 || cart_has_zero_priced_item($cart) ) {
        return '<span class="devis-text">Sur demande</span>';
    }

    return $cart_html;
}

/**
 * Vérifie si le panier contient au moins un article à 0€
 */
function cart_has_zero_priced_item($cart) {
    foreach ($cart->get_cart() as $cart_item) {
        // Vérifie le prix "line item"
        if ( (float) $cart_item['data']->get_price() <= 0.0 ) {
            return true;
        }
    }
    return false;
}

/**
 * 2) Filtre : Remplacer le total dans la commande (emails, page Merci, etc.)
 *    si total = 0 ou si au moins un line item est à 0
 */
add_filter('woocommerce_get_formatted_order_total', 'override_order_total_if_any_zero', 999, 2);
function override_order_total_if_any_zero($formatted_total, $order) {
    if ( (float) $order->get_total() <= 0.0 || order_has_zero_priced_item($order) ) {
        return '<span class="devis-text">Sur demande</span>';
    }
    return $formatted_total;
}

/**
 * Vérifie si la commande contient au moins un line item à 0€
 */
function order_has_zero_priced_item($order) {
    foreach ($order->get_items() as $item) {
        // On compare avec le total (après éventuelles remises)
        if ( (float) $item->get_total() <= 0.0 ) {
            return true;
        }
    }
    return false;
}




//Pour afficher 0 sur la page produit quand on choisit « Intra »
add_action('wp_footer', 'override_variation_price_if_intra', 99);
function override_variation_price_if_intra() {
    // S’assurer qu’on est bien sur une page produit
    if ( ! is_product() ) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        var $form           = $('.variations_form');
        var $sessionSelect  = $('#session-selection');

        if ( ! $form.length || ! $sessionSelect.length ) {
            return; // Pas de variation ou pas de sélecteur => on sort
        }

        // Événement déclenché quand la variation est trouvée
        $form.on('show_variation found_variation', function(event, variationData) {
            // Vérifier si la sélection est "intra"
            if ($sessionSelect.val() === 'intra-entreprise-definir') {
                // Sélectionner la zone de prix affichée
                var $priceContainer = $form.find('.woocommerce-variation-price .price');

                if ($priceContainer.length) {
                    // Écraser le HTML avec 0,00 €
                    $priceContainer.html('Sur demande');
                }
            }
        });

        // Si l’utilisateur change le menu "Intra/Inter", on relance la logique de variation
        $sessionSelect.on('change', function(){
            $form.trigger('check_variations');
        });
    });
    </script>
    <?php
}

