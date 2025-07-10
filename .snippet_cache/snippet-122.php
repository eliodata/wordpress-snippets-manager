<?php
/**
 * Snippet ID: 122
 * Name: Documents de sortie e2pdf dates debut fin Shortcode
 * Description: 
 * @active true
 */

/**
 * On ajoute fsbdd_firstlast_days à la liste des shortcodes « à parser » pour E2Pdf
 */
add_filter('e2pdf_extension_render_shortcodes_tags', function($shortcodes) {
    // conservez l'existant pour fsbdd_all_days si nécessaire
    $shortcodes[] = 'fsbdd_all_days';
    
    // on ajoute notre nouveau shortcode
    $shortcodes[] = 'fsbdd_firstlast_days';
    
    return $shortcodes;
});

/**
 * Shortcode [fsbdd_firstlast_days]
 *
 * Usage : 
 *    [fsbdd_firstlast_days order_id="123"]
 *       ou
 *    [fsbdd_firstlast_days][e2pdf-wc-order key="get_id"][/fsbdd_firstlast_days]
 */
function fsbdd_firstlast_days_shortcode($atts, $content = null) {
    // a) Récupération des attributs
    $atts = shortcode_atts(array(
        'order_id' => '',
    ), $atts);
    
    // b) Déterminer l'ID de commande (même logique que dans fsbdd_all_days)
    $order_id = (int) $atts['order_id'];
    if (empty($order_id) && !empty($content)) {
        $resolved = do_shortcode($content);
        $order_id = (int) trim($resolved);
    }
    if (empty($order_id) && isset($_GET['order_id'])) {
        $order_id = (int) $_GET['order_id'];
    } elseif (empty($order_id) && isset($_GET['post'])) {
        $order_id = (int) $_GET['post'];
    }
    
    // c) Vérification
    if (empty($order_id)) {
        return '<p style="color:red">Impossible de détecter ID de la commande.</p>';
    }
    
    // d) Charger la commande
    $order = wc_get_order($order_id);
    if (!$order) {
        return '<p style="color:red">Commande introuvable (ID: '.$order_id.').</p>';
    }
    
    // e) Boucler sur les articles de la commande pour trouver un CPT lié + planning
    $valid_jours = array();
    foreach ($order->get_items() as $item_id => $item) {
        $cpt_id = $item->get_meta('fsbdd_relsessaction_cpt_produit', true);
        if (!empty($cpt_id)) {
            $fsbdd_planning = get_post_meta($cpt_id, 'fsbdd_planning', true);
            
            // On récupère ici la liste des journées réellement cochées (soit matin, soit après-midi)
            if (is_array($fsbdd_planning) && !empty($fsbdd_planning)) {
                foreach ($fsbdd_planning as $jour_data) {
                    $plan_date = $jour_data['fsbdd_planjour'] ?? '';
                    $plan_date_slash = str_replace('.', '/', $plan_date);
                    
                    // Vérifier les deux demi-journées
                    $matin_meta_key = 'fsbdd_convoc_matin_' . $plan_date_slash;
                    $aprem_meta_key = 'fsbdd_convoc_aprem_' . $plan_date_slash;
                    
                    $is_convoc_matin = $order->get_meta($matin_meta_key, true);
                    $is_convoc_aprem = $order->get_meta($aprem_meta_key, true);
                    
                    // Compatibilité avec l'ancien système
                    $old_meta_key = 'fsbdd_convoc_' . $plan_date_slash;
                    $is_convoc_old = $order->get_meta($old_meta_key, true);
                    
                    // Une journée est valide si au moins l'une des demi-journées est cochée
                    // ou si l'ancienne méta est à '1'
                    if ($is_convoc_matin == '1' || $is_convoc_aprem == '1' || $is_convoc_old == '1') {
                        $valid_jours[] = $plan_date; 
                    }
                }
            }
            
            // Si vous ne voulez prendre en compte que le premier article trouvé,
            // vous pouvez faire un "break;" ici.
        }
    }
    
    // f) S'il n'y a pas de journées valides, on sort
    if (empty($valid_jours)) {
        return '<p>Aucune journée sélectionnée pour la convocation.</p>';
    }
    
    // g) Déterminer la date la plus ancienne et la plus récente
    //    On convertit chaque "dd.mm.yyyy" en timestamp
    $timestamps = array();
    foreach ($valid_jours as $raw_date) {
        $dateObj = DateTime::createFromFormat('d.m.Y', $raw_date);
        if ($dateObj) {
            $timestamps[] = $dateObj->getTimestamp();
        }
    }
    
    if (empty($timestamps)) {
        return '<p>Impossible de convertir les dates.</p>';
    }
    
    $min_ts = min($timestamps);
    $max_ts = max($timestamps);
    
    // h) Conversion en "dd/mm/yy"
    $date_debut = date('d/m/Y', $min_ts);
    $date_fin   = date('d/m/Y', $max_ts);
    
    // i) Construire l'affichage "Du dd/mm/yy au dd/mm/yy"
    $output = sprintf(
        'Du %s au %s',
        esc_html($date_debut),
        esc_html($date_fin)
    );
    
    return $output;
}
add_shortcode('fsbdd_firstlast_days', 'fsbdd_firstlast_days_shortcode');