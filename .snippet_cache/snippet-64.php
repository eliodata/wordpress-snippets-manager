<?php
/**
 * Snippet ID: 64
 * Name: Champ PHP callback CHOIX ACTION DE FORMATION DEPUIS COMMANDE - select cpt fsbdd_relsessaction_cpt_produit -
 * Description: 
 * @active false
 */

function render_select_action_html() {
    global $post;

    // Vérifiez si on est sur une commande WooCommerce
    if (!$post || $post->post_type !== 'shop_order') {
        return '<p>Aucun produit lié à une action de formation trouvé.</p>';
    }

    $order = wc_get_order($post->ID);
    $items = $order->get_items();

    if (empty($items)) {
        return '<p>Aucun produit trouvé dans cette commande.</p>';
    }

    $first_item = reset($items);
    $product_id = $first_item->get_product_id();

    // Rechercher les actions de formation liées au produit
    $args = [
        'post_type' => 'action-de-formation',
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'fsbdd_relsessproduit',
                'value' => $product_id,
                'compare' => '=',
            ],
        ],
        'posts_per_page' => -1,
    ];

    $actions = get_posts($args);

    if (empty($actions)) {
        return '<p>Aucune autre session disponible pour ce produit.</p>';
    }

    // Générer le HTML
    $html = '<label for="action_id">Changer la session :</label>';
    $html .= '<select id="action_id" name="action_id">';
    foreach ($actions as $action) {
        $action_title = get_the_title($action->ID);
        $html .= '<option value="' . esc_attr($action->ID) . '">' . esc_html($action_title) . '</option>';
    }
    $html .= '</select>';
    $html .= '<button type="button" class="button" id="change_action_button">Changer la session</button>';

    // Ajouter un script pour gérer la mise à jour via AJAX
    $html .= '<script type="text/javascript">
        jQuery(document).ready(function($) {
            $("#change_action_button").on("click", function() {
                var data = {
                    "action": "change_action_in_order",
                    "order_id": ' . esc_js($post->ID) . ',
                    "action_id": $("#action_id").val()
                };

                $.post(ajaxurl, data, function(response) {
                    alert(response.data.message);
                    location.reload(); // Recharger la page pour appliquer les modifications
                }).fail(function() {
                    alert("Erreur lors de la mise à jour.");
                });
            });
        });
    </script>';

    return $html;
}
