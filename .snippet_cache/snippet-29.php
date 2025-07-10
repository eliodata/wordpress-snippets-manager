<?php
/**
 * Snippet ID: 29
 * Name: ENTETE metabox INFOS SESSIONS commandes liées au produit
 * Description: 
 * @active true
 */

// Ajouter une metabox sous forme de tableau pour afficher les commandes liées aux produits
add_action('add_meta_boxes', 'woocommerce_linked_orders_table');

function woocommerce_linked_orders_table() {
    add_meta_box(
        'woocommerce-linked-orders',
        'Conventions - Commandes liées',
        'woocommerce_linked_orders_table_callback',
        'product',
        'normal',
        'high',
        [
            '__back_compat_meta_box' => false, // Pour WordPress 5.3+ pour supporter le mode fermé
        ]
    );
}

// Forcer la metabox à être fermée par défaut
add_filter('default_hidden_meta_boxes', function ($hidden, $screen) {
    if ($screen->id === 'product' && !in_array('woocommerce-linked-orders', $hidden)) {
        $hidden[] = 'woocommerce-linked-orders';
    }
    return $hidden;
}, 10, 2);

function woocommerce_linked_orders_table_callback($post) {
    global $wpdb;
    $product_id = $post->ID;

    // Requête pour récupérer les commandes liées au produit
    $query = "
        SELECT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items as order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        WHERE order_item_meta.meta_key = '_product_id'
        AND order_item_meta.meta_value = %d
    ";
    $order_ids = $wpdb->get_col($wpdb->prepare($query, $product_id));

    if (empty($order_ids)) {
        echo "<div class='notice notice-info inline'><p>Aucune commande trouvée pour ce produit.</p></div>";
        return;
    }

    // Construire le tableau HTML
    echo '<div class="notice notice-info inline" style="padding: 15px;">';
    echo '<p style="font-weight: bold;">Liste des commandes liées à ce produit :</p>';
    echo '<table style="width: 100%; border-collapse: collapse; border: 1px solid #ccc;">';
    echo '<thead style="background-color: #f0f0f0;">';
    echo '<tr>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">Commande</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">Client</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">Statut</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">Effectif</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">Référent</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">Marge</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">Suivi réalisé</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">Par</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">Convocations</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        $order_url = admin_url('post.php?post=' . absint($order_id) . '&action=edit');

        // Récupérer les données liées à chaque commande
        $billing_company = $order->get_billing_company();
        $customer_name = strtolower($billing_company) === 'pas de société' ? $order->get_formatted_billing_full_name() : $billing_company;

        $order_status = wc_get_order_status_name($order->get_status());
        $effectif = get_post_meta($order_id, 'fsbdd_effectif', true);
        $user_referent_id = get_post_meta($order_id, 'fsbdd_user_referentrel', true);
        $user_referent = get_userdata($user_referent_id);
        $user_referent_name = $user_referent ? $user_referent->first_name : 'Non défini';

        $marge = get_post_meta($order_id, 'fsbdd_marge', true);
        $suivi_realise = get_post_meta($order_id, 'fsbdd_suivireal', true);

        $par_user_id = get_post_meta($order_id, 'fsbdd_refsuivi', true);
        $par_user = get_userdata($par_user_id);
        $par_name = $par_user ? $par_user->first_name : 'Non défini';

        // Récupérer les dates cochées pour les convocations
        $convoc_dates = [];
        $meta_keys = $wpdb->get_col($wpdb->prepare(
            "SELECT meta_key FROM {$wpdb->prefix}postmeta WHERE post_id = %d AND meta_key LIKE 'fsbdd_convoc_%%' AND meta_value = '1'",
            $order_id
        ));
        foreach ($meta_keys as $meta_key) {
            $date = str_replace('fsbdd_convoc_', '', $meta_key);
            $convoc_dates[] = $date;
        }
        $convoc_dates_output = !empty($convoc_dates) ? implode(', ', $convoc_dates) : 'Aucune';

        // Ligne du tableau
        echo '<tr>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;"><a href="' . esc_url($order_url) . '" target="_blank">' . esc_html($order->get_order_number()) . '</a></td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($customer_name) . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($order_status) . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($effectif) . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($user_referent_name) . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($marge) . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($suivi_realise) . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($par_name) . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($convoc_dates_output) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}
