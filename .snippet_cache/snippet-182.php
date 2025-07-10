<?php
/**
 * Snippet ID: 182
 * Name: bouton Mise a jour statut de commande page edition admin
 * Description: 
 * @active true
 */

/**
 * Plugin Name: WooCommerce Status Retrigger
 * Description: Ajoute un bouton pour relancer les actions du statut actuel d'une commande
 * Version: 1.0.0
 */

// Sécurité - empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class WC_Status_Retrigger {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Hooks pour l'admin seulement
        if (is_admin()) {
            add_action('woocommerce_admin_order_data_after_order_details', array($this, 'add_retrigger_button'));
            add_action('wp_ajax_retrigger_order_status', array($this, 'handle_ajax_retrigger'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        }
    }
    
    /**
     * Ajoute le bouton de relance sous le select des statuts
     */
    public function add_retrigger_button($order) {
        if (!$order instanceof WC_Order) {
            return;
        }
        
        $order_id = $order->get_id();
        $current_status = $order->get_status();
        $status_name = wc_get_order_status_name($current_status);
        
        // Créer le nonce pour la sécurité
        $nonce = wp_create_nonce('retrigger_status_' . $order_id);
        
        ?>
        <div class="retrigger-status-section" style="margin-top: 10px;">
            <h4><?php _e('Actions du statut', 'textdomain'); ?></h4>
            <p class="form-field">
                <button type="button" 
                        id="retrigger-status-btn" 
                        class="button"
                        style="background-color: #0073aa; color: white; border-color: #0073aa; text-shadow: none; box-shadow: none;"
                        data-order-id="<?php echo esc_attr($order_id); ?>"
                        data-nonce="<?php echo esc_attr($nonce); ?>"
                        data-status="<?php echo esc_attr($current_status); ?>">
                    <?php _e('Regénérer le PDF', 'textdomain'); ?>
                </button>
                <span id="retrigger-status-loader" style="display:none; margin-left: 10px;">
                    <span class="spinner is-active" style="float: none; margin: 0;"></span>
                    <?php _e('Traitement en cours...', 'textdomain'); ?>
                </span>
            </p>
            <p class="description" style="font-size: 12px; color: #666; margin-top: 5px;">
                <?php _e('Cliquez pour regénérer les PDFs et renvoyer les emails associés au statut actuel.', 'textdomain'); ?>
            </p>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#retrigger-status-btn').on('click', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var $loader = $('#retrigger-status-loader');
                var orderId = $btn.data('order-id');
                var nonce = $btn.data('nonce');
                var status = $btn.data('status');
                
                // Confirmation
                if (!confirm('<?php _e("Êtes-vous sûr de vouloir regénérer le PDF ?", "textdomain"); ?>')) {
                    return;
                }
                
                // Désactiver le bouton et afficher le loader
                $btn.prop('disabled', true);
                $loader.show();
                
                // Requête AJAX
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'retrigger_order_status',
                        order_id: orderId,
                        nonce: nonce,
                        status: status
                    },
                    success: function(response) {
                        if (response.success) {
                            // Afficher le message de succès et recharger la page
                            $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
                                .insertAfter('.retrigger-status-section');
                            
                            // Recharger la page après un court délai
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            // Afficher le message d'erreur
                            $('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>')
                                .insertAfter('.retrigger-status-section');
                        }
                    },
                    error: function() {
                        $('<div class="notice notice-error is-dismissible"><p><?php _e("Erreur lors de la requête AJAX", "textdomain"); ?></p></div>')
                            .insertAfter('.retrigger-status-section');
                    },
                    complete: function() {
                        // En cas de succès, la page va se recharger
                        // Sinon, réactiver le bouton et masquer le loader
                        if (!$('.notice-success').length) {
                            $btn.prop('disabled', false);
                            $loader.hide();
                        }
                        
                        // Masquer automatiquement les notices d'erreur après 5 secondes
                        setTimeout(function() {
                            $('.notice-error').fadeOut();
                        }, 5000);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Traite la requête AJAX pour relancer les actions du statut
     */
    public function handle_ajax_retrigger() {
        // Vérifications de sécurité
        if (!current_user_can('edit_shop_orders')) {
            wp_die(__('Permissions insuffisantes', 'textdomain'));
        }
        
        $order_id = intval($_POST['order_id']);
        $nonce = sanitize_text_field($_POST['nonce']);
        $status = sanitize_text_field($_POST['status']);
        
        // Vérifier le nonce
        if (!wp_verify_nonce($nonce, 'retrigger_status_' . $order_id)) {
            wp_send_json_error(array('message' => __('Échec de la vérification de sécurité', 'textdomain')));
        }
        
        // Récupérer la commande
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => __('Commande introuvable', 'textdomain')));
        }
        
        // Vérifier que le statut correspond
        if ($order->get_status() !== $status) {
            wp_send_json_error(array('message' => __('Le statut de la commande a changé', 'textdomain')));
        }
        
        try {
            // Déclencher les actions du statut actuel
            $this->retrigger_status_actions($order, $status);
            
            $status_name = wc_get_order_status_name($status);
            wp_send_json_success(array(
                'message' => __('Le PDF a été regénéré avec succès.', 'textdomain')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => sprintf(__('Erreur lors de la relance : %s', 'textdomain'), $e->getMessage())
            ));
        }
    }
    
    /**
     * Relance les actions associées au statut de la commande
     */
    private function retrigger_status_actions($order, $status) {
        // Obtenir le statut avec le préfixe 'wc-' si nécessaire
        $full_status = 'wc-' . $status;
        
        // Simuler un changement de statut pour déclencher les hooks
        // On utilise une transition de statut "artificielle"
        $previous_status = $order->get_status();
        
        // Actions avant la transition
        do_action('woocommerce_order_status_changed', $order->get_id(), $previous_status, $status, $order);
        do_action('woocommerce_order_status_' . $previous_status . '_to_' . $status, $order->get_id(), $order);
        do_action('woocommerce_order_status_' . $status, $order->get_id(), $order);
        
        // Hook spécifique pour votre plugin personnalisé si vous avez un nom d'action spécifique
        // Remplacez 'your_custom_hook' par le nom réel de votre hook
        // do_action('your_custom_status_action', $order, $status);
        
        // Ajouter une note à la commande pour traçabilité
        $order->add_order_note(
            sprintf(__('Actions du statut "%s" relancées manuellement.', 'textdomain'), wc_get_order_status_name($status)),
            false,
            true
        );
        
        // Log pour debug (optionnel)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('WC Status Retrigger: Actions relancées pour la commande #%d (statut: %s)', $order->get_id(), $status));
        }
    }
    
    /**
     * Enregistre les scripts nécessaires
     */
    public function enqueue_scripts($hook) {
        // Charger seulement sur les pages d'édition de commande
        if ('post.php' !== $hook || 'shop_order' !== get_post_type()) {
            return;
        }
        
        // jQuery est déjà chargé dans l'admin WordPress
        // Pas besoin d'enregistrer de scripts supplémentaires pour ce cas simple
    }
}

// Initialiser le plugin
new WC_Status_Retrigger();

/**
 * Fonction utilitaire pour les développeurs
 * Permet de relancer programmatiquement les actions d'un statut
 */
function wc_retrigger_order_status_actions($order_id, $force_status = null) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return false;
    }
    
    $status = $force_status ?: $order->get_status();
    $retrigger = new WC_Status_Retrigger();
    
    try {
        // Utiliser la reflection pour accéder à la méthode privée
        $method = new ReflectionMethod($retrigger, 'retrigger_status_actions');
        $method->setAccessible(true);
        $method->invoke($retrigger, $order, $status);
        return true;
    } catch (Exception $e) {
        return false;
    }
}