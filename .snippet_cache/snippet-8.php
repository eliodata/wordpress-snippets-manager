<?php
/**
 * Snippet ID: 8
 * Name: SNIPPETS POUR PAGE DETAIL FRAIS COMMANDE / SESSIONS / CHAMPS ET METABOX PERSO select société champ radio niveau auto
 * Description: 
 * @active true
 */



// FORCER MISE A JOUR INFOS FACTURATION LIVRAISON PAGE EDITION COMMANDE QUAND CHANGEMENT CLIENT
add_action( 'admin_footer', 'auto_update_billing_address_script' );
function auto_update_billing_address_script() {
    global $post_type;
    
    // Vérifier si on est sur la page d'édition de commande
    if ( 'shop_order' == $post_type ) :
    ?>
    <script type="text/javascript">
        jQuery( document ).ready( function( $ ) {
            // Écouter le changement sur le menu déroulant du client
            $( '#customer_user' ).change( function() {
                // Délai pour laisser le temps à WooCommerce de sauvegarder le changement de client
                setTimeout(function(){
                    // Simuler un clic sur le lien pour modifier l'adresse de facturation
                    $( '.edit_address' ).click();
                }, 1000); // Attendre 1 seconde avant de cliquer
            });
        });
    </script>
    <?php
    endif;
}

// AFFICHER SOCIETE DANS SELECT CLIENT UTILISATEUR SUR EDITION COMMANDES
// Vérifier si nous sommes dans le backend avant d'ajouter le hook
if ( is_admin() ) {
    // Ajouter la fonction au hook approprié, uniquement pour le backend
    add_filter( 'woocommerce_json_search_found_customers', 'customise_customer_display_in_admin_orders' );
}
function customise_customer_display_in_admin_orders( $found_customers ) {
    foreach ( $found_customers as $customer_id => $customer_name ) {
        // Récupérer les données de l'utilisateur
        $user = new WC_Customer( $customer_id );
        // Récupérer le champ 'billing_company'
        $company = $user->get_billing_company();
        
        // Si une société existe, l'afficher en priorité avec le nom du client entre parenthèses
        if ( !empty($company) ) {
            $found_customers[$customer_id] = $company . ' (' . $customer_name . ')';
        }
        // Sinon, conserver uniquement le nom du client
    }
    return $found_customers;
}

// AJOUTER FRAIS COMMANDE WOOCOMMERCE DEPUIS CHAMPS FACTURATIONS
add_action('woocommerce_order_before_calculate_totals', 'mettre_a_jour_frais_custom_sur_recalcul', 10, 2);

function mettre_a_jour_frais_custom_sur_recalcul($and_taxes, $order) {
    if (!is_admin()) {
        return;
    }

    $order_id = $order->get_id();

    // Récupérer la valeur du champ custom 'fsbdd_nomfrais' pour le nom des frais
    $nom_frais = get_post_meta($order_id, 'fsbdd_nomfrais', true);

    // Récupérer la valeur du champ custom 'fsbdd_totalfrais'
    $frais_total = get_post_meta($order_id, 'fsbdd_totalfrais', true);

    // Vérifier si la ligne de frais existe déjà
    $frais_existant = false;
    foreach ($order->get_items('fee') as $item_id => $item_fee) {
        if ($item_fee->get_name() === $nom_frais) {
            $frais_existant = $item_fee;
            break;
        }
    }

    if ($frais_total > 0) {
        if ($frais_existant) {
            // Mettre à jour la ligne de frais existante
            $frais_existant->set_amount($frais_total);
            $frais_existant->set_total($frais_total);
            $frais_existant->save();
        } else {
            // Créer une nouvelle ligne de frais
            $item = new WC_Order_Item_Fee();
            $item->set_name($nom_frais);
            $item->set_amount($frais_total);
            $item->set_total($frais_total);
            $order->add_item($item);
        }
    } else if ($frais_existant) {
        // Supprimer la ligne de frais si le frais total est 0
        $order->remove_item($frais_existant->get_id());
    }
}



/**
 * Auto-sélection du radio button "Confirmé" (valeur 4)
 * si un des champs datepicker est rempli, mais permet la modification manuelle
 */

// Hook pour ajouter du JavaScript dans l'admin des commandes
add_action('admin_footer', 'auto_select_confirme_radio_script');
function auto_select_confirme_radio_script() {
    // Vérifier qu'on est bien sur la page d'édition d'une commande
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'shop_order') {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Fonction pour vérifier et cocher le radio button
        function checkAndSelectConfirme() {
            var confirmeDate = $('input[name*="confirme"][name*="date"]').val();
            var factureDate = $('input[name*="facturesent"][name*="date"]').val();

            // Si un des champs date est rempli et que le statut n'est pas "Annulée", cocher "Confirmé" (valeur 4)
            if ((confirmeDate || factureDate) && $('input[name="fsbdd_affaireniveau"]:checked').val() != '5') {
                $('#fsbdd_affaireniveau_4').prop('checked', true);
            }
        }

        // Vérifier au chargement de la page
        checkAndSelectConfirme();

        // Vérifier quand les champs datepicker changent
        $(document).on('change', 'input[name*="confirme"][name*="date"], input[name*="facturesent"][name*="date"]', function() {
            checkAndSelectConfirme();
        });

        // Pour les datepickers qui utilisent des événements personnalisés
        $(document).on('input', 'input[name*="confirme"][name*="date"], input[name*="facturesent"][name*="date"]', function() {
            checkAndSelectConfirme();
        });

        // Écouteur pour les changements de valeur (compatibilité avec différents types de datepickers)
        $(document).on('blur', 'input[name*="confirme"][name*="date"], input[name*="facturesent"][name*="date"]', function() {
            setTimeout(checkAndSelectConfirme, 100); // Petit délai pour s'assurer que la valeur est bien mise à jour
        });
    });
    </script>
    <?php
}

// Hook pour l'action de sauvegarde (côté serveur)
add_action('woocommerce_process_shop_order_meta', 'auto_set_confirme_on_save', 10, 2);
function auto_set_confirme_on_save($order_id, $order) {
    // Récupérer les valeurs des champs datepicker
    $confirme_date = isset($_POST['_confirme_date']) ? $_POST['_confirme_date'] : '';
    $facture_date = isset($_POST['_facturesent_date']) ? $_POST['_facturesent_date'] : '';

    // Vérifier si un des champs date est rempli et que le statut n'est pas "Annulée"
    if ((!empty($confirme_date) || !empty($facture_date)) && (!isset($_POST['fsbdd_affaireniveau']) || $_POST['fsbdd_affaireniveau'] != '5')) {
        // Forcer la valeur à "Confirmé" (4) uniquement si elle n'est pas "Annulée"
        if (!isset($_POST['fsbdd_affaireniveau']) || $_POST['fsbdd_affaireniveau'] != '4') {
            $_POST['fsbdd_affaireniveau'] = '4';
        }
    }

    // Mettre à jour la méta-donnée
    if (isset($_POST['fsbdd_affaireniveau'])) {
        update_post_meta($order_id, 'fsbdd_affaireniveau', $_POST['fsbdd_affaireniveau']);
    }
}

