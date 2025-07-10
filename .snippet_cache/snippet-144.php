<?php
/**
 * Snippet ID: 144
 * Name: CALCULER TOTAL ABSENCES pages commandes
 * Description: 
 * @active true
 */

// TOTAL JOURNÉES ABSENCES
add_action('rwmb_after_save_post', 'fsbdd_calculate_total_absences');

function fsbdd_calculate_total_absences($post_id) {
    if (get_post_type($post_id) !== 'shop_order') return;

    $group_data = get_post_meta($post_id, 'fsbdd_gpeffectif', true);
    $total_absences = 0.0;

    if (is_array($group_data)) {
        foreach ($group_data as $entry) {
            if (isset($entry['fsbdd_stagiabst']) && is_numeric($entry['fsbdd_stagiabst'])) {
                $total_absences += (float)$entry['fsbdd_stagiabst'];
            }
        }
    }

    // Formatage optionnel pour éviter les .0 inutiles
    $total_absences = ($total_absences == floor($total_absences)) 
        ? (int)$total_absences 
        : $total_absences;

    update_post_meta($post_id, 'fsbdd_ttabssession', $total_absences);
}

// TOTAL JOURNÉES A FACTURER

add_action('rwmb_after_save_post', function($post_id) {
    // Vérifier que nous sommes bien sur une commande WooCommerce
    if (get_post_type($post_id) !== 'shop_order') {
        return;
    }

    // Récupérer les valeurs des champs Metabox.io en tant que flottants (float)
    $effectif = (float) rwmb_meta('fsbdd_effectif', '', $post_id);
    $convoc_total = (float) rwmb_meta('fsbdd_convoc_total', '', $post_id);
    $ttabssession = (float) rwmb_meta('fsbdd_ttabssession', '', $post_id);

    // Calcul en conservant les valeurs décimales
    $tt_jour_effectif = ($effectif * $convoc_total) - $ttabssession;

    // Mise à jour du champ fsbdd_ttjoureffectif avec un arrondi à 1 décimale
    rwmb_set_meta($post_id, 'fsbdd_ttjoureffectif', round($tt_jour_effectif, 1));
}, 20);

