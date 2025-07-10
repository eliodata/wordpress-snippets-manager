<?php
/**
 * Snippet ID: 12
 * Name: Facturation OPCO adresses cpt vers commandes et bouton radio financement opco select
 * Description: 
 * @active true
 */

add_action('admin_footer', function() {
    // Vérifie si nous sommes sur la page de détail de commande WooCommerce
    if (get_current_screen()->id === 'shop_order') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Fonction pour récupérer l'adresse via AJAX
            function fetchAddress(postID) {
                return $.ajax({
                    url: ajaxurl, // URL pour les appels AJAX WordPress
                    type: 'POST',
                    data: {
                        action: 'get_opco_address',
                        post_id: postID
                    }
                });
            }

            // Sur changement du champ select
            $('#fsbdd_select_subro').change(function() {
                const selectedPostID = $(this).val();

                if (selectedPostID) {
                    fetchAddress(selectedPostID).done(function(response) {
                        if (response.success && response.data) {
                            $('#fsbddtextarea_coordopco').val(response.data); // Remplir avec l'adresse
                        }
                    });
                } else {
                    $('#fsbddtextarea_coordopco').val(''); // Effacer le champ si aucune sélection
                }
            });
        });
        </script>
        <?php
    }
});

// Fonction PHP pour retourner l'adresse via AJAX
add_action('wp_ajax_get_opco_address', function() {
    // Vérifie les permissions et les paramètres
    if (!current_user_can('edit_shop_orders') || empty($_POST['post_id'])) {
        wp_send_json_error('Non autorisé ou ID manquant');
    }

    $post_id = intval($_POST['post_id']);
    $address = get_post_meta($post_id, 'fsbdd_adressopco', true);

    if ($address) {
        wp_send_json_success($address);
    } else {
        wp_send_json_error('Adresse introuvable');
    }
});


// afficher choix radio fsbdd_financeopco sur NON si fsbdd_select_opca est sur NON
add_action('admin_footer', function() {
    // Vérifie si nous sommes sur la page de détail de commande WooCommerce
    $screen = get_current_screen();
    if ($screen->id === 'shop_order') {
        // Récupère les données nécessaires pour le script
        $order_id = isset($_GET['post']) ? intval($_GET['post']) : 0; // ID de la commande
        $opca_value = $order_id ? get_post_meta($order_id, 'fsbdd_select_opca', true) : '';
        $is_new_order = $order_id ? '0' : '1'; // Identifier si c'est une commande existante
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Fonction pour initialiser le champ radio
            function initializeFinanceOPCO() {
                const opcaValue = "<?php echo esc_js($opca_value); ?>"; // Valeur de fsbdd_select_opca passée par PHP
                const isNewOrder = "<?php echo esc_js($is_new_order); ?>"; // Détecte si c'est une nouvelle commande
                const financeOPCOField = $('input[name="fsbdd_financeopco"]'); // Champ radio

                // Appliquer la logique seulement si c'est une nouvelle commande ou au premier passage
                if (isNewOrder === '1' || !financeOPCOField.is(':checked')) {
                    if (opcaValue === 'NON') {
                        financeOPCOField.filter('[value="1"]').prop('checked', true); // "NON"
                    } else if (opcaValue) {
                        financeOPCOField.filter('[value="2"]').prop('checked', true); // "OUI"
                    }
                }
            }

            // Initialiser au chargement de la page
            initializeFinanceOPCO();
        });
        </script>
        <?php
    }
});
