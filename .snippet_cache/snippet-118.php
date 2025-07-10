<?php
/**
 * Snippet ID: 118
 * Name: génération planning documents de sortie e2pdf shortcode
 * Description: <p>description génération planning documents de sortie e2pdf shortcode</p>
 * @active true
 */


/**
 * 1) Informer E2Pdf qu'il doit "parser" nos shortcodes personnalisés
 */
add_filter('e2pdf_extension_render_shortcodes_tags', function($shortcodes) {
    $shortcodes[] = 'fsbdd_all_days';
    $shortcodes[] = 'fsbdd_duration_only';
    $shortcodes[] = 'fsbdd_cpt_all_days';
    $shortcodes[] = 'fsbdd_cpt_duration_only';
    return $shortcodes;
});

/**
 * 2) Shortcode [fsbdd_all_days] - Version ORIGINALE pour commandes WooCommerce
 */
function fsbdd_all_days_shortcode($atts, $content = null) {

    // a) Récupération des attributs
    $atts = shortcode_atts(array(
        'order_id' => '',
    ), $atts);

    // b) Déterminer l'ID de commande
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

    // Boucler sur les articles de la commande
    $output = '';
    foreach ($order->get_items() as $item_id => $item) {

        // Vérifier si l'article a la meta 'fsbdd_relsessaction_cpt_produit'
        $cpt_id = $item->get_meta('fsbdd_relsessaction_cpt_produit', true);

        if (!empty($cpt_id)) {

            // On récupère le champ fsbdd_planning sur le CPT
            $fsbdd_planning = get_post_meta($cpt_id, 'fsbdd_planning', true);

            if (is_array($fsbdd_planning) && !empty($fsbdd_planning)) {

                // Préparer un tableau "valid_jours"
                $valid_jours = array();
                $total_demi_journees = 0;

                foreach ($fsbdd_planning as $jour_data) {
                    $plan_date = $jour_data['fsbdd_planjour'] ?? '';
                    $plan_date_slash = str_replace('.', '/', $plan_date);
                    
                    // Récupérer les statuts des demi-journées
                    $matin_meta_key = 'fsbdd_convoc_matin_' . $plan_date_slash;
                    $aprem_meta_key = 'fsbdd_convoc_aprem_' . $plan_date_slash;
                    
                    $is_convoc_matin = $order->get_meta($matin_meta_key, true);
                    $is_convoc_aprem = $order->get_meta($aprem_meta_key, true);
                    
                    // Compatibilité avec l'ancien système
                    $old_meta_key = 'fsbdd_convoc_' . $plan_date_slash;
                    $is_convoc_old = $order->get_meta($old_meta_key, true);
                    
                    if ($is_convoc_old == '1' && $is_convoc_matin === '' && $is_convoc_aprem === '') {
                        $is_convoc_matin = '1';
                        $is_convoc_aprem = '1';
                    }
                    
                    // On n'inclut cette journée que si au moins une demi-journée est cochée
                    if ($is_convoc_matin == '1' || $is_convoc_aprem == '1') {
                        $jour_data['convoc_matin'] = $is_convoc_matin == '1';
                        $jour_data['convoc_aprem'] = $is_convoc_aprem == '1';
                        
                        $valid_jours[] = $jour_data;
                        
                        if ($is_convoc_matin == '1') $total_demi_journees += 0.5;
                        if ($is_convoc_aprem == '1') $total_demi_journees += 0.5;
                    }
                }

                if (empty($valid_jours)) {
                    $output .= '<p>Aucune journée sélectionnée pour la convocation.</p>';
                    return $output;
                }

                // Calculer la durée totale avec prise en compte des présences des stagiaires
                $total_minutes = 0;
                $unique_days = [];
                
                // Récupérer les stagiaires
                $gpeffectif = $order->get_meta('fsbdd_gpeffectif', true);
                $nb_stagiaires = is_array($gpeffectif) ? count($gpeffectif) : 0;
                
                if ($nb_stagiaires === 0) {
                    // S'il n'y a pas de stagiaires définis, on utilise juste la convocation de la commande
                    foreach ($valid_jours as $jour_data) {
                        $raw_date = $jour_data['fsbdd_planjour'] ?? '';
                        if (!empty($raw_date) && !in_array($raw_date, $unique_days)) {
                            $unique_days[] = $raw_date;
                        }

                        $matin_debut  = $jour_data['fsbdd_plannmatin']    ?? '';
                        $matin_fin    = $jour_data['fsbdd_plannmatinfin'] ?? '';
                        $aprem_debut  = $jour_data['fsbdd_plannam']       ?? '';
                        $aprem_fin    = $jour_data['fsbdd_plannamfin']    ?? '';

                        if ($jour_data['convoc_matin']) {
                            $total_minutes += get_minutes_diff($matin_debut, $matin_fin);
                        }
                        
                        if ($jour_data['convoc_aprem']) {
                            $total_minutes += get_minutes_diff($aprem_debut, $aprem_fin);
                        }
                    }
                } else {
                    // S'il y a des stagiaires définis, calculer plus précisément
                    $stagiaires_demi_journees = 0;
                    $stagiaires_minutes = 0;
                    
                    foreach ($valid_jours as $jour_data) {
                        $raw_date = $jour_data['fsbdd_planjour'] ?? '';
                        if (!empty($raw_date) && !in_array($raw_date, $unique_days)) {
                            $unique_days[] = $raw_date;
                        }

                        $matin_debut  = $jour_data['fsbdd_plannmatin']    ?? '';
                        $matin_fin    = $jour_data['fsbdd_plannmatinfin'] ?? '';
                        $aprem_debut  = $jour_data['fsbdd_plannam']       ?? '';
                        $aprem_fin    = $jour_data['fsbdd_plannamfin']    ?? '';
                        
                        $matin_minutes = get_minutes_diff($matin_debut, $matin_fin);
                        $aprem_minutes = get_minutes_diff($aprem_debut, $aprem_fin);
                        
                        $matin_disponible = $jour_data['convoc_matin'];
                        $aprem_disponible = $jour_data['convoc_aprem'];
                        
                        // Pour chaque stagiaire
                        foreach ($gpeffectif as $stagiaire) {
                            $stagiaire_present_matin = false;
                            $stagiaire_present_aprem = false;
                            
                            if (isset($stagiaire['fsbdd_stagiaconvoc']) && is_array($stagiaire['fsbdd_stagiaconvoc'])) {
                                $jour_key = str_replace('.', '', $raw_date); // ex: 15102025
                                
                                foreach ($stagiaire['fsbdd_stagiaconvoc'] as $creneau_jour => $creneau_type) {
                                    if ($creneau_jour == $jour_key) {
                                        if ($creneau_type == '1' && $matin_disponible) {
                                            $stagiaire_present_matin = true;
                                        } elseif ($creneau_type == '2' && $aprem_disponible) {
                                            $stagiaire_present_aprem = true;
                                        }
                                    }
                                }
                            } else {
                                $stagiaire_present_matin = $matin_disponible;
                                $stagiaire_present_aprem = $aprem_disponible;
                            }
                            
                            if ($stagiaire_present_matin) {
                                $stagiaires_demi_journees += 0.5;
                                $stagiaires_minutes += $matin_minutes;
                            }
                            
                            if ($stagiaire_present_aprem) {
                                $stagiaires_demi_journees += 0.5;
                                $stagiaires_minutes += $aprem_minutes;
                            }
                        }
                    }
                    
                    if ($nb_stagiaires > 0) {
                        $total_demi_journees = $stagiaires_demi_journees / $nb_stagiaires;
                        $total_minutes = $stagiaires_minutes / $nb_stagiaires;
                    }
                }
                
                // Convertir les demi-journées en jours
                $total_jours = $total_demi_journees / 2;
                $total_heures = round($total_minutes / 60, 1);

                // S'assurer que nous avons des valeurs cohérentes
                if ($total_jours == 0 && !empty($unique_days)) {
                    $total_jours = count($unique_days);
                    
                    $minutes_recalculees = 0;
                    foreach ($valid_jours as $jour_data) {
                        if ($jour_data['convoc_matin']) {
                            $minutes_recalculees += get_minutes_diff($jour_data['fsbdd_plannmatin'] ?? '', $jour_data['fsbdd_plannmatinfin'] ?? '');
                        }
                        if ($jour_data['convoc_aprem']) {
                            $minutes_recalculees += get_minutes_diff($jour_data['fsbdd_plannam'] ?? '', $jour_data['fsbdd_plannamfin'] ?? '');
                        }
                    }
                    
                    if ($minutes_recalculees > 0) {
                        $total_minutes = $minutes_recalculees;
                        $total_heures = round($total_minutes / 60, 1);
                    } else {
                        $total_heures = $total_jours * 7;
                    }
                }

                // Afficher "Durée / stagiaire : X jours (Y heures)"
                $output .= sprintf(
                    '<p style="margin-bottom:15px; font-weight:bold; font-size:10px;">
                        Durée / stagiaire : %s jour%s (%s heure%s)
                    </p>',
                    $total_jours,
                    ($total_jours > 1 ? 's' : ''),
                    str_replace('.', ',', $total_heures),
                    ($total_heures > 1 ? 's' : '')
                );

                // Construire le tableau
                $output .= '
                <table style="border-collapse: collapse; width:100%; font-family:Arial, sans-serif; font-size:8px;">
                    <thead>
                        <tr style="background-color:#800000; color:#fff; font-weight:bold;">
                            <th style="width:12%; padding:4px; text-align:left;">DATE</th>
                            <th style="width:20%; padding:4px; text-align:left;">MATIN</th>
                            <th style="width:20%; padding:4px; text-align:left;">APRÈS-MIDI</th>
                            <th style="width:48%; padding:4px; text-align:left;">FORMATEUR(S)</th>
                        </tr>
                    </thead>
                    <tbody>
                ';

                foreach ($valid_jours as $jour_data) {
                    $raw_date = $jour_data['fsbdd_planjour'] ?? '';
                    $date_formatted = format_date_ddmmyy($raw_date);

                    $matin_debut  = format_hour($jour_data['fsbdd_plannmatin']     ?? '');
                    $matin_fin    = format_hour($jour_data['fsbdd_plannmatinfin'] ?? '');
                    $aprem_debut  = format_hour($jour_data['fsbdd_plannam']       ?? '');
                    $aprem_fin    = format_hour($jour_data['fsbdd_plannamfin']   ?? '');

                    // Formateurs
                    $formateurs_html = '';
                    if (!empty($jour_data['fsbdd_gpformatr']) && is_array($jour_data['fsbdd_gpformatr'])) {
                        $formateurs_noms = array();
                        foreach ($jour_data['fsbdd_gpformatr'] as $fmt) {
                            $id_formateur = $fmt['fsbdd_user_formateurrel'] ?? '';
                            if ($id_formateur) {
                                $prenom = get_post_meta($id_formateur, 'first_name', true);
                                $nom    = get_post_meta($id_formateur, 'last_name',  true);
                                $initiale_prenom = $prenom ? mb_substr($prenom, 0, 1) . '.' : '';
                                $fullname = trim($initiale_prenom . ' ' . $nom); 
                                if ($fullname) {
                                    $formateurs_noms[] = $fullname;
                                }
                            }
                        }
                        if ($formateurs_noms) {
                            $formateurs_html = implode(', ', $formateurs_noms);
                        }
                    }

                    $output .= '<tr style="border-bottom:1px solid #ccc;">';
                    $output .= '<td style="width:12%; padding:4px;">'.esc_html($date_formatted).'</td>';

                    if ($jour_data['convoc_matin']) {
                        $output .= '<td style="width:20%; padding:4px; white-space:nowrap;">'.esc_html($matin_debut).' à '.esc_html($matin_fin).'</td>';
                    } else {
                        $output .= '<td style="width:20%; padding:4px; color:#aaa; font-style:italic;">N/A</td>';
                    }

                    if ($jour_data['convoc_aprem']) {
                        $output .= '<td style="width:20%; padding:4px; white-space:nowrap;">'.esc_html($aprem_debut).' à '.esc_html($aprem_fin).'</td>';
                    } else {
                        $output .= '<td style="width:20%; padding:4px; color:#aaa; font-style:italic;">N/A</td>';
                    }
                    
                    $output .= '<td style="width:48%; padding:4px;">'.esc_html($formateurs_html).'</td>';
                    $output .= '</tr>';
                }

                $output .= '</tbody></table>';

            } else {
                $output .= '<p>Aucun planning trouvé.</p>';
            }
            
            break;
        }
    }

    if (empty($output)) {
        return '<p style="color:orange">Aucun CPT lié ou pas de données de planning.</p>';
    }

    return $output;
}

add_shortcode('fsbdd_all_days', 'fsbdd_all_days_shortcode');

/**
 * Shortcode [fsbdd_duration_only] - Version ORIGINALE pour commandes WooCommerce
 */
function fsbdd_duration_only_shortcode($atts, $content = null) {

    // a) Récupération des attributs
    $atts = shortcode_atts(array(
        'order_id' => '',
    ), $atts);

    // b) Déterminer l'ID de commande
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

    if (empty($order_id)) {
        return '<p style="color:red">Impossible de détecter ID de la commande.</p>';
    }

    // c) Charger la commande
    $order = wc_get_order($order_id);
    if (!$order) {
        return '<p style="color:red">Commande introuvable (ID: '.$order_id.').</p>';
    }

    // d) Boucler sur les articles pour récupérer le planning
    $total_minutes = 0;
    $total_demi_journees = 0;
    $unique_days = [];

    foreach ($order->get_items() as $item_id => $item) {
        $cpt_id = $item->get_meta('fsbdd_relsessaction_cpt_produit', true);

        if (!empty($cpt_id)) {
            $fsbdd_planning = get_post_meta($cpt_id, 'fsbdd_planning', true);

            if (is_array($fsbdd_planning) && !empty($fsbdd_planning)) {
                $valid_jours = array();

                // Récupérer les stagiaires
                $gpeffectif = $order->get_meta('fsbdd_gpeffectif', true);
                $nb_stagiaires = is_array($gpeffectif) ? count($gpeffectif) : 0;

                // 1. Identifier les jours valides en fonction des convocations au niveau commande
                foreach ($fsbdd_planning as $jour_data) {
                    $plan_date = $jour_data['fsbdd_planjour'] ?? '';
                    $plan_date_slash = str_replace('.', '/', $plan_date);
                    
                    $matin_meta_key = 'fsbdd_convoc_matin_' . $plan_date_slash;
                    $aprem_meta_key = 'fsbdd_convoc_aprem_' . $plan_date_slash;
                    
                    $is_convoc_matin = $order->get_meta($matin_meta_key, true);
                    $is_convoc_aprem = $order->get_meta($aprem_meta_key, true);
                    
                    $old_meta_key = 'fsbdd_convoc_' . $plan_date_slash;
                    $is_convoc_old = $order->get_meta($old_meta_key, true);
                    
                    if ($is_convoc_old == '1' && $is_convoc_matin === '' && $is_convoc_aprem === '') {
                        $is_convoc_matin = '1';
                        $is_convoc_aprem = '1';
                    }
                    
                    if ($is_convoc_matin == '1' || $is_convoc_aprem == '1') {
                        $jour_data['convoc_matin'] = $is_convoc_matin == '1';
                        $jour_data['convoc_aprem'] = $is_convoc_aprem == '1';
                        
                        $valid_jours[] = $jour_data;
                        
                        if (!empty($plan_date) && !in_array($plan_date, $unique_days)) {
                            $unique_days[] = $plan_date;
                        }
                    }
                }

                // 2. Calculer les durées en fonction des stagiaires
                if ($nb_stagiaires === 0) {
                    foreach ($valid_jours as $jour_data) {
                        $matin_debut  = $jour_data['fsbdd_plannmatin']     ?? '';
                        $matin_fin    = $jour_data['fsbdd_plannmatinfin'] ?? '';
                        $aprem_debut  = $jour_data['fsbdd_plannam']       ?? '';
                        $aprem_fin    = $jour_data['fsbdd_plannamfin']   ?? '';

                        if ($jour_data['convoc_matin']) {
                            $total_minutes += get_minutes_diff($matin_debut, $matin_fin);
                            $total_demi_journees += 0.5;
                        }
                        
                        if ($jour_data['convoc_aprem']) {
                            $total_minutes += get_minutes_diff($aprem_debut, $aprem_fin);
                            $total_demi_journees += 0.5;
                        }
                    }
                } else {
                    $stagiaires_demi_journees = 0;
                    $stagiaires_minutes = 0;
                    
                    foreach ($valid_jours as $jour_data) {
                        $raw_date = $jour_data['fsbdd_planjour'] ?? '';
                        
                        $matin_debut  = $jour_data['fsbdd_plannmatin']    ?? '';
                        $matin_fin    = $jour_data['fsbdd_plannmatinfin'] ?? '';
                        $aprem_debut  = $jour_data['fsbdd_plannam']       ?? '';
                        $aprem_fin    = $jour_data['fsbdd_plannamfin']    ?? '';
                        
                        $matin_minutes = get_minutes_diff($matin_debut, $matin_fin);
                        $aprem_minutes = get_minutes_diff($aprem_debut, $aprem_fin);
                        
                        $matin_disponible = $jour_data['convoc_matin'];
                        $aprem_disponible = $jour_data['convoc_aprem'];
                        
                        foreach ($gpeffectif as $stagiaire) {
                            $stagiaire_present_matin = false;
                            $stagiaire_present_aprem = false;
                            
                            if (isset($stagiaire['fsbdd_stagiaconvoc']) && is_array($stagiaire['fsbdd_stagiaconvoc'])) {
                                $jour_key = str_replace('.', '', $raw_date); // ex: 15102025
                                
                                foreach ($stagiaire['fsbdd_stagiaconvoc'] as $creneau_jour => $creneau_type) {
                                    if ($creneau_jour == $jour_key) {
                                        if ($creneau_type == '1' && $matin_disponible) {
                                            $stagiaire_present_matin = true;
                                        } elseif ($creneau_type == '2' && $aprem_disponible) {
                                            $stagiaire_present_aprem = true;
                                        }
                                    }
                                }
                            } else {
                                $stagiaire_present_matin = $matin_disponible;
                                $stagiaire_present_aprem = $aprem_disponible;
                            }
                            
                            if ($stagiaire_present_matin) {
                                $stagiaires_demi_journees += 0.5;
                                $stagiaires_minutes += $matin_minutes;
                            }
                            
                            if ($stagiaire_present_aprem) {
                                $stagiaires_demi_journees += 0.5;
                                $stagiaires_minutes += $aprem_minutes;
                            }
                        }
                    }
                    
                    if ($nb_stagiaires > 0) {
                        $total_demi_journees = $stagiaires_demi_journees / $nb_stagiaires;
                        $total_minutes = $stagiaires_minutes / $nb_stagiaires;
                    }
                }
            }

            break;
        }
    }

    // Convertir les demi-journées en jours
    $total_jours = $total_demi_journees / 2;

    if ($total_jours == 0 && !empty($unique_days)) {
        $total_jours = count($unique_days);
        
        $minutes_recalculees = 0;
        foreach ($valid_jours as $jour_data) {
            if ($jour_data['convoc_matin']) {
                $minutes_recalculees += get_minutes_diff($jour_data['fsbdd_plannmatin'] ?? '', $jour_data['fsbdd_plannmatinfin'] ?? '');
            }
            if ($jour_data['convoc_aprem']) {
                $minutes_recalculees += get_minutes_diff($jour_data['fsbdd_plannam'] ?? '', $jour_data['fsbdd_plannamfin'] ?? '');
            }
        }
        
        if ($minutes_recalculees > 0) {
            $total_minutes = $minutes_recalculees;
        } else {
            $total_minutes = $total_jours * 7 * 60;
        }
    }

    if ($total_jours === 0) {
        return '';
    }

    $total_heures = round($total_minutes / 60, 1);

    return sprintf(
        '%s jour%s (%s heure%s)',
        $total_jours,
        ($total_jours > 1 ? 's' : ''),
        str_replace('.', ',', $total_heures),
        ($total_heures > 1 ? 's' : '')
    );
}

add_shortcode('fsbdd_duration_only', 'fsbdd_duration_only_shortcode');

/**
 * 3) Shortcode [fsbdd_cpt_all_days] - Version pour CPT action-de-formation
 */
function fsbdd_cpt_all_days_shortcode($atts, $content = null) {

    // a) Récupération des attributs
    $atts = shortcode_atts(array(
        'cpt_id' => '',
        'order_id' => '',
    ), $atts);

    // b) Déterminer l'ID du CPT
    $cpt_id = (int) $atts['cpt_id'];

    if (empty($cpt_id) && !empty($content)) {
        $resolved = do_shortcode($content);
        $cpt_id = (int) trim($resolved);
    }

    if (empty($cpt_id) && isset($_GET['post'])) {
        $cpt_id = (int) $_GET['post'];
    } elseif (empty($cpt_id) && isset($_GET['cpt_id'])) {
        $cpt_id = (int) $_GET['cpt_id'];
    }
    
    // Fallback E2PDF
    if (empty($cpt_id)) {
        global $post;
        if ($post && $post->ID) {
            $cpt_id = $post->ID;
        }
    }

    if (empty($cpt_id)) {
        return '<p style="color:red">Impossible de détecter l\'ID du CPT action-de-formation.</p>';
    }

    // c) Vérifier que le post existe et est du bon type
    $cpt_post = get_post($cpt_id);
    if (!$cpt_post || $cpt_post->post_type !== 'action-de-formation') {
        return '<p style="color:red">CPT action-de-formation introuvable (ID: '.$cpt_id.').</p>';
    }

    // d) Récupérer le planning du CPT
    $fsbdd_planning = get_post_meta($cpt_id, 'fsbdd_planning', true);

    if (!is_array($fsbdd_planning) || empty($fsbdd_planning)) {
        return '<p>Aucun planning trouvé pour cette action de formation.</p>';
    }

    // e) Récupérer l'ordre associé si fourni (pour les convocations)
    $order = null;
    $use_order_convocations = false;
    if (!empty($atts['order_id'])) {
        $order = wc_get_order((int) $atts['order_id']);
        $use_order_convocations = ($order !== false);
    }

    // f) Préparer les journées valides
    $valid_jours = array();
    $total_demi_journees = 0;

    foreach ($fsbdd_planning as $jour_data) {
        $plan_date = $jour_data['fsbdd_planjour'] ?? '';
        $plan_date_slash = str_replace('.', '/', $plan_date);
        
        $convoc_matin = true;
        $convoc_aprem = true;
        
        if ($use_order_convocations) {
            $matin_meta_key = 'fsbdd_convoc_matin_' . $plan_date_slash;
            $aprem_meta_key = 'fsbdd_convoc_aprem_' . $plan_date_slash;
            
            $is_convoc_matin = $order->get_meta($matin_meta_key, true);
            $is_convoc_aprem = $order->get_meta($aprem_meta_key, true);
            
            $old_meta_key = 'fsbdd_convoc_' . $plan_date_slash;
            $is_convoc_old = $order->get_meta($old_meta_key, true);
            
            if ($is_convoc_old == '1' && $is_convoc_matin === '' && $is_convoc_aprem === '') {
                $is_convoc_matin = '1';
                $is_convoc_aprem = '1';
            }
            
            $convoc_matin = ($is_convoc_matin == '1');
            $convoc_aprem = ($is_convoc_aprem == '1');
            
            if (!$convoc_matin && !$convoc_aprem) {
                continue;
            }
        }
        
        $jour_data['convoc_matin'] = $convoc_matin;
        $jour_data['convoc_aprem'] = $convoc_aprem;
        
        $valid_jours[] = $jour_data;
        
        if ($convoc_matin) $total_demi_journees += 0.5;
        if ($convoc_aprem) $total_demi_journees += 0.5;
    }

    if (empty($valid_jours)) {
        return '<p>Aucune journée sélectionnée pour cette action de formation.</p>';
    }

    // g) Calculer la durée totale
    $total_minutes = 0;
    $unique_days = [];
    
    foreach ($valid_jours as $jour_data) {
        $raw_date = $jour_data['fsbdd_planjour'] ?? '';
        if (!empty($raw_date) && !in_array($raw_date, $unique_days)) {
            $unique_days[] = $raw_date;
        }

        $matin_debut  = $jour_data['fsbdd_plannmatin']    ?? '';
        $matin_fin    = $jour_data['fsbdd_plannmatinfin'] ?? '';
        $aprem_debut  = $jour_data['fsbdd_plannam']       ?? '';
        $aprem_fin    = $jour_data['fsbdd_plannamfin']    ?? '';

        if ($jour_data['convoc_matin']) {
            $total_minutes += get_minutes_diff($matin_debut, $matin_fin);
        }
        
        if ($jour_data['convoc_aprem']) {
            $total_minutes += get_minutes_diff($aprem_debut, $aprem_fin);
        }
    }
    
    // Convertir en jours et heures
    $total_jours = $total_demi_journees / 2;
    $total_heures = round($total_minutes / 60, 1);

    if ($total_jours == 0 && !empty($unique_days)) {
        $total_jours = count($unique_days);
        if ($total_heures == 0) {
            $total_heures = $total_jours * 7;
        }
    }

    // h) Construire l'output
    $output = '';
    
    $output .= sprintf(
        '<p style="margin-bottom:15px; font-weight:bold; font-size:10px;">
            Durée : %s jour%s (%s heure%s)
        </p>',
        $total_jours,
        ($total_jours > 1 ? 's' : ''),
        str_replace('.', ',', $total_heures),
        ($total_heures > 1 ? 's' : '')
    );

    $output .= '
    <table style="border-collapse: collapse; width:100%; font-family:Arial, sans-serif; font-size:8px;">
        <thead>
            <tr style="background-color:#800000; color:#fff; font-weight:bold;">
                <th style="width:12%; padding:4px; text-align:left;">DATE</th>
                <th style="width:20%; padding:4px; text-align:left;">MATIN</th>
                <th style="width:20%; padding:4px; text-align:left;">APRÈS-MIDI</th>
                <th style="width:48%; padding:4px; text-align:left;">FORMATEUR(S)</th>
            </tr>
        </thead>
        <tbody>
    ';

    foreach ($valid_jours as $jour_data) {
        $raw_date = $jour_data['fsbdd_planjour'] ?? '';
        $date_formatted = format_date_ddmmyy($raw_date);

        $matin_debut  = format_hour($jour_data['fsbdd_plannmatin']     ?? '');
        $matin_fin    = format_hour($jour_data['fsbdd_plannmatinfin'] ?? '');
        $aprem_debut  = format_hour($jour_data['fsbdd_plannam']       ?? '');
        $aprem_fin    = format_hour($jour_data['fsbdd_plannamfin']   ?? '');

        // Formateurs
        $formateurs_html = '';
        if (!empty($jour_data['fsbdd_gpformatr']) && is_array($jour_data['fsbdd_gpformatr'])) {
            $formateurs_noms = array();
            foreach ($jour_data['fsbdd_gpformatr'] as $fmt) {
                $id_formateur = $fmt['fsbdd_user_formateurrel'] ?? '';
                if ($id_formateur) {
                    $prenom = get_post_meta($id_formateur, 'first_name', true);
                    $nom    = get_post_meta($id_formateur, 'last_name',  true);
                    $initiale_prenom = $prenom ? mb_substr($prenom, 0, 1) . '.' : '';
                    $fullname = trim($initiale_prenom . ' ' . $nom); 
                    if ($fullname) {
                        $formateurs_noms[] = $fullname;
                    }
                }
            }
            if ($formateurs_noms) {
                $formateurs_html = implode(', ', $formateurs_noms);
            }
        }

        $output .= '<tr style="border-bottom:1px solid #ccc;">';
        $output .= '<td style="width:12%; padding:4px;">'.esc_html($date_formatted).'</td>';

        if ($jour_data['convoc_matin']) {
            $output .= '<td style="width:20%; padding:4px; white-space:nowrap;">'.esc_html($matin_debut).' à '.esc_html($matin_fin).'</td>';
        } else {
            $output .= '<td style="width:20%; padding:4px; color:#aaa; font-style:italic;">N/A</td>';
        }

        if ($jour_data['convoc_aprem']) {
            $output .= '<td style="width:20%; padding:4px; white-space:nowrap;">'.esc_html($aprem_debut).' à '.esc_html($aprem_fin).'</td>';
        } else {
            $output .= '<td style="width:20%; padding:4px; color:#aaa; font-style:italic;">N/A</td>';
        }
        
        $output .= '<td style="width:48%; padding:4px;">'.esc_html($formateurs_html).'</td>';
        $output .= '</tr>';
    }

    $output .= '</tbody></table>';

    return $output;
}

add_shortcode('fsbdd_cpt_all_days', 'fsbdd_cpt_all_days_shortcode');

/**
 * 4) Shortcode [fsbdd_cpt_duration_only] - Version pour CPT action-de-formation
 */
function fsbdd_cpt_duration_only_shortcode($atts, $content = null) {

    // a) Récupération des attributs
    $atts = shortcode_atts(array(
        'cpt_id' => '',
        'order_id' => '',
    ), $atts);

    // b) Déterminer l'ID du CPT
    $cpt_id = (int) $atts['cpt_id'];

    if (empty($cpt_id) && !empty($content)) {
        $resolved = do_shortcode($content);
        $cpt_id = (int) trim($resolved);
    }

    if (empty($cpt_id) && isset($_GET['post'])) {
        $cpt_id = (int) $_GET['post'];
    } elseif (empty($cpt_id) && isset($_GET['cpt_id'])) {
        $cpt_id = (int) $_GET['cpt_id'];
    }
    
    // Fallback E2PDF
    if (empty($cpt_id)) {
        global $post;
        if ($post && $post->ID) {
            $cpt_id = $post->ID;
        }
    }

    if (empty($cpt_id)) {
        return '';
    }

    // c) Vérifier que le post existe
    $cpt_post = get_post($cpt_id);
    if (!$cpt_post || $cpt_post->post_type !== 'action-de-formation') {
        return '';
    }

    // d) Récupérer le planning
    $fsbdd_planning = get_post_meta($cpt_id, 'fsbdd_planning', true);

    if (!is_array($fsbdd_planning) || empty($fsbdd_planning)) {
        return '';
    }

    // e) Récupérer l'ordre associé si fourni
    $order = null;
    $use_order_convocations = false;
    if (!empty($atts['order_id'])) {
        $order = wc_get_order((int) $atts['order_id']);
        $use_order_convocations = ($order !== false);
    }

    // f) Calculer la durée
    $total_minutes = 0;
    $total_demi_journees = 0;
    $unique_days = [];

    foreach ($fsbdd_planning as $jour_data) {
        $plan_date = $jour_data['fsbdd_planjour'] ?? '';
        $plan_date_slash = str_replace('.', '/', $plan_date);
        
        $convoc_matin = true;
        $convoc_aprem = true;
        
        if ($use_order_convocations) {
            $matin_meta_key = 'fsbdd_convoc_matin_' . $plan_date_slash;
            $aprem_meta_key = 'fsbdd_convoc_aprem_' . $plan_date_slash;
            
            $is_convoc_matin = $order->get_meta($matin_meta_key, true);
            $is_convoc_aprem = $order->get_meta($aprem_meta_key, true);
            
            $old_meta_key = 'fsbdd_convoc_' . $plan_date_slash;
            $is_convoc_old = $order->get_meta($old_meta_key, true);
            
            if ($is_convoc_old == '1' && $is_convoc_matin === '' && $is_convoc_aprem === '') {
                $is_convoc_matin = '1';
                $is_convoc_aprem = '1';
            }
            
            $convoc_matin = ($is_convoc_matin == '1');
            $convoc_aprem = ($is_convoc_aprem == '1');
            
            if (!$convoc_matin && !$convoc_aprem) {
                continue;
            }
        }
        
        if (!empty($plan_date) && !in_array($plan_date, $unique_days)) {
            $unique_days[] = $plan_date;
        }

        $matin_debut  = $jour_data['fsbdd_plannmatin']     ?? '';
        $matin_fin    = $jour_data['fsbdd_plannmatinfin'] ?? '';
        $aprem_debut  = $jour_data['fsbdd_plannam']       ?? '';
        $aprem_fin    = $jour_data['fsbdd_plannamfin']   ?? '';

        if ($convoc_matin) {
            $total_minutes += get_minutes_diff($matin_debut, $matin_fin);
            $total_demi_journees += 0.5;
        }
        
        if ($convoc_aprem) {
            $total_minutes += get_minutes_diff($aprem_debut, $aprem_fin);
            $total_demi_journees += 0.5;
        }
    }

    $total_jours = $total_demi_journees / 2;

    if ($total_jours == 0 && !empty($unique_days)) {
        $total_jours = count($unique_days);
        if ($total_minutes == 0) {
            $total_minutes = $total_jours * 7 * 60;
        }
    }

    if ($total_jours === 0) {
        return '';
    }

    $total_heures = round($total_minutes / 60, 1);

    return sprintf(
        '%s jour%s (%s heure%s)',
        $total_jours,
        ($total_jours > 1 ? 's' : ''),
        str_replace('.', ',', $total_heures),
        ($total_heures > 1 ? 's' : '')
    );
}

add_shortcode('fsbdd_cpt_duration_only', 'fsbdd_cpt_duration_only_shortcode');

/**
 * Fonctions utilitaires
 */
function format_date_ddmmyy($raw_date) {
    $dateObj = DateTime::createFromFormat('d.m.Y', $raw_date);
    if ($dateObj) {
        return $dateObj->format('d/m/y');
    }
    return $raw_date;
}

function format_hour($raw_hour) {
    if (strpos($raw_hour, ':') !== false) {
        return str_replace(':', 'h', $raw_hour);
    }
    return $raw_hour;
}

function get_minutes_diff($start, $end) {
    if (!$start || !$end) {
        return 0;
    }
    $start_parts = explode(':', $start);
    $end_parts   = explode(':', $end);
    if (count($start_parts) < 2 || count($end_parts) < 2) {
        return 0;
    }
    $start_min = $start_parts[0]*60 + $start_parts[1];
    $end_min   = $end_parts[0]*60 + $end_parts[1];
    $diff      = $end_min - $start_min;
    return ($diff > 0) ? $diff : 0;
}
