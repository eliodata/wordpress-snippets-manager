<?php
/**
 * Snippet ID: 18
 * Name: Metadata commande vers action de formations
 * Description: 
 * @active false
 */

add_action('add_meta_boxes', 'ajouter_commandes_liees_cpt_metabox');

function ajouter_commandes_liees_cpt_metabox() {
    add_meta_box(
        'commandes_liees_cpt',
        'Commandes Associées',
        'afficher_commandes_liees_cpt',
        'action-de-formation', // Type de post (CPT)
        'side',
        'high'
    );
}

function afficher_commandes_liees_cpt($post) {
    global $wpdb;

    // Récupérer l'ID du CPT actuel
    $cpt_id = $post->ID;

    // Récupérer le titre du CPT (Numéro de session)
    $cpt_title = get_the_title($cpt_id);

    if (empty($cpt_title)) {
        echo '<p>Le titre du CPT est introuvable.</p>';
        return;
    }

    // Requête pour trouver les commandes liées
    $query = "
        SELECT DISTINCT oi.order_id
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim
            ON oi.order_item_id = oim.order_item_id
        WHERE oim.meta_key = 'Numéro de session'
        AND oim.meta_value = %s
    ";

    $order_ids = $wpdb->get_col($wpdb->prepare($query, $cpt_title));

    if (empty($order_ids)) {
        echo '<p>Aucune commande liée trouvée pour cette action de formation.</p>';
        return;
    }

    // Afficher les commandes liées dans un tableau
    echo '<p>Liste des commandes associées à cette action de formation :</p>';
    echo '<table style="width: 100%; border-collapse: collapse; border: 1px solid #ccc;">';
    echo '<thead style="background-color: #f9f9f9; border-bottom: 1px solid #ddd;">';
    echo '<tr>';
    echo '<th style="border: 1px solid #ccc; padding: 5px;">Commande</th>';
    echo '<th style="border: 1px solid #ccc; padding: 5px;">Client</th>';
    echo '<th style="border: 1px solid #ccc; padding: 5px;">Statut</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            continue;
        }

        $order_url = admin_url('post.php?post=' . absint($order_id) . '&action=edit');
        $billing_company = $order->get_billing_company();
        $customer_name = $billing_company && strtolower($billing_company) !== 'pas de société' ? $billing_company : $order->get_formatted_billing_full_name();
        $order_status = wc_get_order_status_name($order->get_status());

        echo '<tr>';
        echo '<td style="border: 1px solid #ccc; padding: 5px;"><a href="' . esc_url($order_url) . '" target="_blank">#' . esc_html($order->get_order_number()) . '</a></td>';
        echo '<td style="border: 1px solid #ccc; padding: 5px;">' . esc_html($customer_name) . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 5px;">' . esc_html($order_status) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}
