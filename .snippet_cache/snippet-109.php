<?php
/**
 * Snippet ID: 109
 * Name: Afficher les catégories cases à cocher au-dessus de la description de variation sur produits
 * Description: 
 * @active false
 */

// Afficher les cases à cocher au-dessus de la description de variation, uniquement pour la catégorie 351
add_action( 'woocommerce_before_single_variation', 'ajouter_champ_checkboxes_produit_au_dessus_description' );
function ajouter_champ_checkboxes_produit_au_dessus_description() {
    global $product;

    // Vérifiez si le produit appartient à la catégorie 351
    if( has_term( 351, 'product_cat', $product->get_id() ) ) {
        echo '<div class="champ-checkbox-categorie">';
        echo '<label><strong>Catégorie(s) choisie(s) : </strong></label><br>';

        // Ajouter un sélecteur pour le nombre de catégories
        echo '<label for="nombre_categories"><strong>Nombre de catégories :</strong></label>';
        echo '<select id="nombre_categories" name="nombre_categories">';
        echo '<option value="1">1 catégorie</option>';
        echo '<option value="2">2 catégories</option>';
        echo '<option value="3">3 catégories</option>';
        // Ajoutez d'autres options si nécessaire
        echo '</select><br><br>';

        // Définir les options de catégories disponibles
        $options = array('A', 'B', 'C1', 'F', 'G', 'H0(V)', 'B0', 'BS', 'BE', 'BR', 'BC', '1B', '2B', '3B'); // Ajustez selon vos besoins

        foreach( $options as $option ) {
            echo '<label><input type="checkbox" class="choix_categorie_checkbox" name="choix_categorie[]" value="' . esc_attr( $option ) . '"> ' . esc_html( $option ) . '</label> ';
        }
        echo '</div>';

        // Ajouter le script JavaScript pour limiter les sélections
        ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                var selectNombre = document.getElementById('nombre_categories');
                var checkboxes = document.querySelectorAll('.choix_categorie_checkbox');
                var maxAllowed = parseInt(selectNombre.value);

                // Mettre à jour la limite lorsque le sélecteur change
                selectNombre.addEventListener('change', function() {
                    maxAllowed = parseInt(this.value);
                    var checked = document.querySelectorAll('.choix_categorie_checkbox:checked');
                    if (checked.length > maxAllowed) {
                        // Désélectionner les cases en excès
                        for (var i = maxAllowed; i < checked.length; i++) {
                            checked[i].checked = false;
                        }
                        alert('Vous ne pouvez sélectionner que ' + maxAllowed + ' catégorie(s).');
                    }
                });

                // Limiter les cases cochées en fonction de la sélection
                checkboxes.forEach(function(checkbox) {
                    checkbox.addEventListener('change', function() {
                        var checkedCount = document.querySelectorAll('.choix_categorie_checkbox:checked').length;
                        if (checkedCount > maxAllowed) {
                            this.checked = false;
                            alert('Vous ne pouvez sélectionner que ' + maxAllowed + ' catégorie(s).');
                        }
                    });
                });
            });
        </script>
        <?php
    }
}

// Enregistrer les cases à cocher dans le panier
add_filter( 'woocommerce_add_cart_item_data', 'enregistrer_choix_categories_dans_panier', 10, 2 );
function enregistrer_choix_categories_dans_panier( $cart_item_data, $product_id ) {
    if( isset( $_POST['choix_categorie'] ) ) {
        $cart_item_data['choix_categorie'] = array_map( 'sanitize_text_field', $_POST['choix_categorie'] );
    }
    if( isset( $_POST['nombre_categories'] ) ) {
        $cart_item_data['nombre_categories'] = intval( $_POST['nombre_categories'] );
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
    if( isset( $cart_item['nombre_categories'] ) ) {
        $item_data[] = array(
            'name' => 'Nombre de catégories',
            'value' => $cart_item['nombre_categories']
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
    if( isset( $values['nombre_categories'] ) ) {
        wc_add_order_item_meta( $item_id, 'nombre_categories', $values['nombre_categories'] );
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
        if( $nombre_categories = wc_get_order_item_meta( $item_id, 'nombre_categories' ) ) {
            $fields['nombre_categories'] = array(
                'label' => 'Nombre de catégories',
                'value' => $nombre_categories,
            );
        }
    }
    return $fields;
}
