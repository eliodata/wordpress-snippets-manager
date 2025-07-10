<?php
/**
 * Snippet ID: 174
 * Name: Conditions et alerte statut commande facture payée si reglements effectués
 * Description: 
 * @active true
 */

// Afficher uniquement l'alerte lorsque le paiement est complet
add_action('admin_notices', 'fsbdd_check_payment_status');

function fsbdd_check_payment_status() {
    // Vérifier si nous sommes sur la page d'édition d'une commande
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'shop_order' || !isset($_GET['post'])) {
        return;
    }
    
    $order_id = intval($_GET['post']);
    $order = wc_get_order($order_id);
    
    if (!$order) {
        return;
    }
    
    // Récupérer les valeurs des champs personnalisés
    $total_payments = get_post_meta($order_id, 'fsbdd_ttrglmts', true);
    $total_amount = get_post_meta($order_id, 'fsbdd_montcattc', true);
    
    // Convertir en nombre décimal pour la comparaison
    $total_payments = floatval(str_replace(',', '.', $total_payments));
    $total_amount = floatval(str_replace(',', '.', $total_amount));
    
    // Tolérance pour les erreurs d'arrondi (0.01€)
    $difference = abs($total_payments - $total_amount);
    
    // Vérifier si le statut actuel n'est pas déjà "factureok"
    if ($order->get_status() !== 'factureok') {
        if ($difference <= 0.01 && $total_payments > 0 && $total_amount > 0) {
            // Afficher l'alerte améliorée avec le bouton orange
            echo '<div class="notice notice-warning is-dismissible" style="border-left: 5px solid #ff6600; box-shadow: 0 1px 4px rgba(0,0,0,0.2); padding: 10px 15px;">
                <h3 style="margin-top: 5px; color: #ff6600;">🔔 Règlement complet détecté!</h3>
                <p style="font-size: 14px;">Le montant total des règlements (<strong>' . number_format($total_payments, 2, ',', ' ') . '€</strong>) 
                correspond au montant de la commande (<strong>' . number_format($total_amount, 2, ',', ' ') . '€</strong>).</p>
                <p><a href="#" id="fsbdd-update-status" class="button" 
                   style="background-color: #ff6600; color: white !important; border-color: #d35400; font-weight: bold; padding: 5px 15px; height: auto;"
                   data-order="' . esc_attr($order_id) . '">Changer le statut en "Facture OK"</a></p>
            </div>';
            
            // Ajouter le script JS pour le changement de statut
            add_action('admin_footer', 'fsbdd_add_status_change_script');
        }
    }
}

// Script pour le changement de statut
function fsbdd_add_status_change_script() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#fsbdd-update-status').on('click', function(e) {
            e.preventDefault();
            
            var orderId = $(this).data('order');
            
            // Afficher un loader
            $(this).html('<span class="spinner is-active" style="float:none;margin:0;"></span> Mise à jour...');
            $(this).prop('disabled', true);
            
            // Appel AJAX pour changer le statut
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'fsbdd_update_order_status',
                    order_id: orderId,
                    security: '<?php echo wp_create_nonce('fsbdd_update_status_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erreur: ' + response.data);
                        $('#fsbdd-update-status').html('Réessayer');
                        $('#fsbdd-update-status').prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Erreur de connexion');
                    $('#fsbdd-update-status').html('Réessayer');
                    $('#fsbdd-update-status').prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
}

// Fonction AJAX pour changer le statut
add_action('wp_ajax_fsbdd_update_order_status', 'fsbdd_update_order_status_callback');

function fsbdd_update_order_status_callback() {
    check_ajax_referer('fsbdd_update_status_nonce', 'security');
    
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error('Permissions insuffisantes');
    }
    
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    if (!$order_id) {
        wp_send_json_error('ID de commande invalide');
    }
    
    $order = wc_get_order($order_id);
    
    if (!$order) {
        wp_send_json_error('Commande introuvable');
    }
    
    // Changer le statut en "factureok"
    $order->update_status('factureok', 'Statut mis à jour automatiquement - paiement complet vérifié.');
    
    wp_send_json_success('Statut mis à jour');
}