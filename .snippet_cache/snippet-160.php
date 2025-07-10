<?php
/**
 * Snippet ID: 160
 * Name: afficher statuts woocommerce
 * Description: 
 * @active false
 */

// Ajouter une meta box pour afficher les statuts de commande
function add_order_status_meta_box() {
    add_meta_box(
        'woocommerce-order-statuses', // ID de la meta box
        'Statuts de Commande',        // Titre de la meta box
        'display_order_statuses',     // Fonction de callback pour afficher le contenu
        'shop_order',                // Type de post (commande WooCommerce)
        'side'                       // Position de la meta box
    );
}
add_action('add_meta_boxes', 'add_order_status_meta_box');

// Fonction de callback pour afficher les statuts de commande
function display_order_statuses($post) {
    // Récupérer tous les statuts de commande WooCommerce
    $order_statuses = wc_get_order_statuses();

    // Afficher les statuts dans une liste
    echo '<ul>';
    foreach ($order_statuses as $status_slug => $status_name) {
        echo '<li>' . esc_html($status_slug) . ': ' . esc_html($status_name) . '</li>';
    }
    echo '</ul>';
}