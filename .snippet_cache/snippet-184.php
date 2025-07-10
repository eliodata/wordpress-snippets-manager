<?php
/**
 * Snippet ID: 184
 * Name: e2pdf shortcode liste stagiaire certificat attestation individuelle
 * Description: 
 * @active true
 */


/**
 * Shortcodes pour générer des attestations individuelles avec E2PDF
 */

// 1) Informer E2Pdf qu'il doit parser nos nouveaux shortcodes
add_filter('e2pdf_extension_render_shortcodes_tags', function($shortcodes) {
    $shortcodes[] = 'fsbdd_stagiaire_count';
    $shortcodes[] = 'fsbdd_stagiaire_single';
    $shortcodes[] = 'fsbdd_stagiaire_exists';
    $shortcodes[] = 'fsbdd_stagiaire_info';
    return $shortcodes;
});

/**
 * Shortcode [fsbdd_stagiaire_count]
 * Retourne le nombre total de stagiaires
 * 
 * Usage: [fsbdd_stagiaire_count][e2pdf-wc-order key="get_id"][/fsbdd_stagiaire_count]
 */
function fsbdd_stagiaire_count_shortcode($atts, $content = null) {
    $order_id = (int) do_shortcode($content);
    
    if (empty($order_id)) {
        return '0';
    }
    
    $order = wc_get_order($order_id);
    if (!$order) {
        return '0';
    }
    
    $gpeffectif = $order->get_meta('fsbdd_gpeffectif', true);
    if (empty($gpeffectif) || !is_array($gpeffectif)) {
        return '0';
    }
    
    return count($gpeffectif);
}
add_shortcode('fsbdd_stagiaire_count', 'fsbdd_stagiaire_count_shortcode');

/**
 * Shortcode [fsbdd_stagiaire_exists]
 * Vérifie si un stagiaire existe à l'index donné
 * 
 * Usage: [fsbdd_stagiaire_exists index="0"][e2pdf-wc-order key="get_id"][/fsbdd_stagiaire_exists]
 */
function fsbdd_stagiaire_exists_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'index' => '0',
    ), $atts);
    
    $order_id = (int) do_shortcode($content);
    $index = (int) $atts['index'];
    
    if (empty($order_id)) {
        return '0';
    }
    
    $order = wc_get_order($order_id);
    if (!$order) {
        return '0';
    }
    
    $gpeffectif = $order->get_meta('fsbdd_gpeffectif', true);
    if (empty($gpeffectif) || !is_array($gpeffectif)) {
        return '0';
    }
    
    return isset($gpeffectif[$index]) ? '1' : '0';
}
add_shortcode('fsbdd_stagiaire_exists', 'fsbdd_stagiaire_exists_shortcode');

/**
 * Shortcode [fsbdd_stagiaire_single]
 * Affiche les informations d'un stagiaire spécifique ou le tableau complet si index = "all"
 * 
 * Usage: 
 * - Pour un stagiaire: [fsbdd_stagiaire_single index="0"][e2pdf-wc-order key="get_id"][/fsbdd_stagiaire_single]
 * - Pour tous: [fsbdd_stagiaire_single index="all"][e2pdf-wc-order key="get_id"][/fsbdd_stagiaire_single]
 */
function fsbdd_stagiaire_single_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'index' => '0',
        'format' => 'table', // 'table' ou 'text'
    ), $atts);
    
    $order_id = (int) do_shortcode($content);
    $index = $atts['index'];
    $format = $atts['format'];
    
    if (empty($order_id)) {
        return '<p style="color:red;">Impossible de détecter ID de la commande.</p>';
    }
    
    $order = wc_get_order($order_id);
    if (!$order) {
        return '<p style="color:red;">Commande introuvable (ID: '.$order_id.').</p>';
    }
    
    $gpeffectif = $order->get_meta('fsbdd_gpeffectif', true);
    if (empty($gpeffectif) || !is_array($gpeffectif)) {
        return '<p>Aucun stagiaire enregistré pour cette commande.</p>';
    }
    
    // Si index = "all", on affiche le tableau complet (comme l'original)
    if ($index === 'all') {
        return fsbdd_render_all_stagiaires($gpeffectif, $format);
    }
    
    // Sinon, on affiche un stagiaire spécifique
    $stagiaire_index = (int) $index;
    if (!isset($gpeffectif[$stagiaire_index])) {
        return '<p>Stagiaire non trouvé à l\'index ' . $stagiaire_index . '</p>';
    }
    
    return fsbdd_render_single_stagiaire($gpeffectif[$stagiaire_index], $format);
}
add_shortcode('fsbdd_stagiaire_single', 'fsbdd_stagiaire_single_shortcode');

/**
 * Fonction pour afficher tous les stagiaires (mode tableau original)
 */
function fsbdd_render_all_stagiaires($gpeffectif, $format) {
    $stagiaires = array();
    foreach ($gpeffectif as $item) {
        $prenom = isset($item['fsbdd_prenomstagiaire']) ? trim($item['fsbdd_prenomstagiaire']) : '';
        $nom    = isset($item['fsbdd_nomstagiaire'])    ? trim($item['fsbdd_nomstagiaire'])    : '';
        
        // Déterminer le type de convocation
        $convoc_type = 'Journée'; // Valeur par défaut
        if (isset($item['fsbdd_stagiaconvoc']) && is_array($item['fsbdd_stagiaconvoc'])) {
            $has_matin = in_array('1', $item['fsbdd_stagiaconvoc']);
            $has_aprem = in_array('2', $item['fsbdd_stagiaconvoc']);
            
            if ($has_matin && $has_aprem) {
                $convoc_type = 'Journée';
            } elseif ($has_matin) {
                $convoc_type = 'Matins';
            } elseif ($has_aprem) {
                $convoc_type = 'Après-midi';
            } else {
                $convoc_type = '-';
            }
        }
        
        if ($prenom || $nom) {
            $stagiaires[] = array(
                'prenom' => $prenom,
                'nom'    => $nom,
                'convoc' => $convoc_type
            );
        }
    }
    
    if (empty($stagiaires)) {
        return '<p>Aucun stagiaire enregistré pour cette commande.</p>';
    }
    
    $nb_stagiaires = count($stagiaires);
    $output = sprintf(
        '<p style="margin-bottom:15px; font-weight:bold; font-size:10px;">
            Effectif : %d stagiaire%s
        </p>',
        $nb_stagiaires,
        $nb_stagiaires > 1 ? 's' : ''
    );
    
    if ($format === 'text') {
        foreach ($stagiaires as $stagiaire) {
            $output .= '<p>' . esc_html($stagiaire['prenom'] . ' ' . $stagiaire['nom']) . ' - ' . esc_html($stagiaire['convoc']) . '</p>';
        }
        return $output;
    }
    
    // Format tableau (par défaut)
    $output .= '
        <table style="width:100%; border-collapse:collapse; font-size:8px; font-family:Arial, sans-serif;">
            <thead>
                <tr style="background-color:#f1f1f1;">
                    <th style="width:35%; padding:4px; border:1px solid #ccc; text-align:left;">PRÉNOM</th>
                    <th style="width:35%; padding:4px; border:1px solid #ccc; text-align:left;">NOM</th>
                    <th style="width:30%; padding:4px; border:1px solid #ccc; text-align:left;">SESSION</th>
                </tr>
            </thead>
            <tbody>
    ';
    
    foreach ($stagiaires as $stagiaire) {
        $output .= '<tr>';
        $output .= '<td style="width:35%; padding:2px 4px; border:1px solid #ccc;">' . esc_html($stagiaire['prenom']) . '</td>';
        $output .= '<td style="width:35%; padding:2px 4px; border:1px solid #ccc;">' . esc_html($stagiaire['nom']) . '</td>';
        $output .= '<td style="width:30%; padding:2px 4px; border:1px solid #ccc;">' . esc_html($stagiaire['convoc']) . '</td>';
        $output .= '</tr>';
    }
    
    $output .= '</tbody></table>';
    return $output;
}

/**
 * Fonction pour afficher un seul stagiaire
 */
function fsbdd_render_single_stagiaire($item, $format) {
    $prenom = isset($item['fsbdd_prenomstagiaire']) ? trim($item['fsbdd_prenomstagiaire']) : '';
    $nom    = isset($item['fsbdd_nomstagiaire'])    ? trim($item['fsbdd_nomstagiaire'])    : '';
    
    // Déterminer le type de convocation
    $convoc_type = 'Journée'; // Valeur par défaut
    if (isset($item['fsbdd_stagiaconvoc']) && is_array($item['fsbdd_stagiaconvoc'])) {
        $has_matin = in_array('1', $item['fsbdd_stagiaconvoc']);
        $has_aprem = in_array('2', $item['fsbdd_stagiaconvoc']);
        
        if ($has_matin && $has_aprem) {
            $convoc_type = 'Journée';
        } elseif ($has_matin) {
            $convoc_type = 'Matins';
        } elseif ($has_aprem) {
            $convoc_type = 'Après-midi';
        } else {
            $convoc_type = '-';
        }
    }
    
    if (!$prenom && !$nom) {
        return '<p>Informations stagiaire manquantes.</p>';
    }
    
    if ($format === 'text') {
        return '<p>' . esc_html($prenom . ' ' . $nom) . ' - ' . esc_html($convoc_type) . '</p>';
    }
    
    // Format tableau (par défaut) - pour une seule ligne SANS la ligne "Effectif"
    $output = '
        <table style="width:100%; border-collapse:collapse; font-size:8px; font-family:Arial, sans-serif;">
            <thead>
                <tr style="background-color:#f1f1f1;">
                    <th style="width:35%; padding:4px; border:1px solid #ccc; text-align:left;">PRÉNOM</th>
                    <th style="width:35%; padding:4px; border:1px solid #ccc; text-align:left;">NOM</th>
                    <th style="width:30%; padding:4px; border:1px solid #ccc; text-align:left;">SESSION</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="width:35%; padding:2px 4px; border:1px solid #ccc;">' . esc_html($prenom) . '</td>
                    <td style="width:35%; padding:2px 4px; border:1px solid #ccc;">' . esc_html($nom) . '</td>
                    <td style="width:30%; padding:2px 4px; border:1px solid #ccc;">' . esc_html($convoc_type) . '</td>
                </tr>
            </tbody>
        </table>
    ';
    
    return $output;
}

/**
 * Shortcode [fsbdd_stagiaire_info]
 * Récupère une information spécifique d'un stagiaire
 * 
 * Usage: [fsbdd_stagiaire_info index="0" field="prenom"][e2pdf-wc-order key="get_id"][/fsbdd_stagiaire_info]
 * 
 * Fields disponibles: prenom, nom, fullname, convoc, convoc_text
 */
function fsbdd_stagiaire_info_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'index' => '0',
        'field' => 'fullname', // prenom, nom, fullname, convoc, convoc_text
    ), $atts);
    
    $order_id = (int) do_shortcode($content);
    $index = (int) $atts['index'];
    $field = $atts['field'];
    
    if (empty($order_id)) {
        return '';
    }
    
    $order = wc_get_order($order_id);
    if (!$order) {
        return '';
    }
    
    $gpeffectif = $order->get_meta('fsbdd_gpeffectif', true);
    if (empty($gpeffectif) || !is_array($gpeffectif) || !isset($gpeffectif[$index])) {
        return '';
    }
    
    $item = $gpeffectif[$index];
    $prenom = isset($item['fsbdd_prenomstagiaire']) ? trim($item['fsbdd_prenomstagiaire']) : '';
    $nom    = isset($item['fsbdd_nomstagiaire'])    ? trim($item['fsbdd_nomstagiaire'])    : '';
    
    // Déterminer le type de convocation
    $convoc_type = 'Journée'; // Valeur par défaut
    $convoc_code = 'full'; // full, morning, afternoon, none
    
    if (isset($item['fsbdd_stagiaconvoc']) && is_array($item['fsbdd_stagiaconvoc'])) {
        $has_matin = in_array('1', $item['fsbdd_stagiaconvoc']);
        $has_aprem = in_array('2', $item['fsbdd_stagiaconvoc']);
        
        if ($has_matin && $has_aprem) {
            $convoc_type = 'Journée';
            $convoc_code = 'full';
        } elseif ($has_matin) {
            $convoc_type = 'Matins';
            $convoc_code = 'morning';
        } elseif ($has_aprem) {
            $convoc_type = 'Après-midi';
            $convoc_code = 'afternoon';
        } else {
            $convoc_type = '-';
            $convoc_code = 'none';
        }
    }
    
    switch ($field) {
        case 'prenom':
            return esc_html($prenom);
            
        case 'nom':
            return esc_html($nom);
            
        case 'fullname':
            return esc_html(trim($prenom . ' ' . $nom));
            
        case 'convoc':
            return $convoc_code;
            
        case 'convoc_text':
            return esc_html($convoc_type);
            
        default:
            return '';
    }
}
add_shortcode('fsbdd_stagiaire_info', 'fsbdd_stagiaire_info_shortcode');