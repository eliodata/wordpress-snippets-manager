<?php
/**
 * Snippet ID: 188
 * Name: e2pdf shortcode listes formateurs planning lettres de mission actions
 * Description: 
 * @active true
 */


/**
 * Fonction utilitaire pour récupérer les formateurs du planning
 */
function fsbdd_get_formateurs_from_planning($cpt_id) {
    try {
        if (empty($cpt_id)) {
            return array();
        }
        
        $planning = get_post_meta($cpt_id, 'fsbdd_planning', true);
        
        if (empty($planning) || !is_array($planning)) {
            return array();
        }
        
        $formateurs_ids = array();
        
        foreach ($planning as $seance) {
            if (isset($seance['fsbdd_gpformatr']) && is_array($seance['fsbdd_gpformatr'])) {
                foreach ($seance['fsbdd_gpformatr'] as $formateur_info) {
                    if (is_array($formateur_info) && isset($formateur_info['fsbdd_user_formateurrel'])) {
                        $formateur_id = (int) $formateur_info['fsbdd_user_formateurrel'];
                        if ($formateur_id > 0 && !in_array($formateur_id, $formateurs_ids)) {
                            $formateur_post = get_post($formateur_id);
                            if ($formateur_post && $formateur_post->post_type === 'formateur') {
                                $formateurs_ids[] = $formateur_id;
                            }
                        }
                    }
                }
            }
        }
        
        return $formateurs_ids;
        
    } catch (Exception $e) {
        return array();
    }
}

/**
 * Shortcode [fsbdd_formateur_count] - Nombre de formateurs distincts
 */
function fsbdd_formateur_count_shortcode($atts, $content = null) {
    try {
        $cpt_id = 0;
        if (!empty($content)) {
            $resolved = do_shortcode($content);
            $cpt_id = (int) trim($resolved);
        }
        
        if (empty($cpt_id)) {
            return '0';
        }
        
        $formateurs_ids = fsbdd_get_formateurs_from_planning($cpt_id);
        return count($formateurs_ids);
        
    } catch (Exception $e) {
        return '0';
    }
}
add_shortcode('fsbdd_formateur_count', 'fsbdd_formateur_count_shortcode');

/**
 * Shortcode [fsbdd_formateur_info] - Informations d'un formateur par index
 */
function fsbdd_formateur_info_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'index' => '1',
        'field' => 'nom_complet',
    ), $atts);
    
    try {
        $cpt_id = 0;
        if (!empty($content)) {
            $resolved = do_shortcode($content);
            $cpt_id = (int) trim($resolved);
        }
        
        $formateurs_ids = fsbdd_get_formateurs_from_planning($cpt_id);
        
        if (empty($formateurs_ids)) {
            return '';
        }
        
        $index = (int) $atts['index'] - 1;
        
        if (!isset($formateurs_ids[$index])) {
            return '';
        }
        
        $formateur_id = $formateurs_ids[$index];
        
        switch ($atts['field']) {
            case 'id':
                return $formateur_id;
            case 'societe':
                $value = get_post_meta($formateur_id, 'fsbdd_text_nomfacture', true);
                break;
            case 'prenom':
                $value = get_post_meta($formateur_id, 'first_name', true);
                break;
            case 'nom':
                $value = get_post_meta($formateur_id, 'last_name', true);
                break;
            case 'nom_complet':
                $prenom = get_post_meta($formateur_id, 'first_name', true);
                $nom = get_post_meta($formateur_id, 'last_name', true);
                $value = trim($prenom . ' ' . $nom);
                break;
            case 'email':
                $value = get_post_meta($formateur_id, 'fsbdd_email_mail1', true);
                break;
            case 'adresse1':
                $value = get_post_meta($formateur_id, 'fsbdd_text_adresse_1', true);
                break;
            case 'adresse2':
                $value = get_post_meta($formateur_id, 'fsbdd_text_adresse_2', true);
                break;
            case 'cp':
                $value = get_post_meta($formateur_id, 'fsbdd_text_cp', true);
                break;
            case 'ville':
                $value = get_post_meta($formateur_id, 'fsbdd_text_ville', true);
                break;
            case 'siret':
                $value = get_post_meta($formateur_id, 'fsbdd_text_siret', true);
                break;
            default:
                $value = '';
        }
        
        // Pour l'ID, on retourne directement sans esc_html
        if ($atts['field'] === 'id') {
            return $value;
        }
        
        return esc_html($value);
        
    } catch (Exception $e) {
        return '';
    }
}
add_shortcode('fsbdd_formateur_info', 'fsbdd_formateur_info_shortcode');

/**
 * Shortcode [fsbdd_formateur_adresse] - Bloc adresse complet d'un formateur
 */
function fsbdd_formateur_adresse_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'index' => '1',
    ), $atts);
    
    try {
        $cpt_id = 0;
        if (!empty($content)) {
            $resolved = do_shortcode($content);
            $cpt_id = (int) trim($resolved);
        }
        
        $formateurs_ids = fsbdd_get_formateurs_from_planning($cpt_id);
        
        if (empty($formateurs_ids)) {
            return '';
        }
        
        $index = (int) $atts['index'] - 1;
        
        if (!isset($formateurs_ids[$index])) {
            return '';
        }
        
        $formateur_id = $formateurs_ids[$index];
        
        $societe = get_post_meta($formateur_id, 'fsbdd_text_nomfacture', true);
        $prenom = get_post_meta($formateur_id, 'first_name', true);
        $nom = get_post_meta($formateur_id, 'last_name', true);
        $adresse1 = get_post_meta($formateur_id, 'fsbdd_text_adresse_1', true);
        $adresse2 = get_post_meta($formateur_id, 'fsbdd_text_adresse_2', true);
        $cp = get_post_meta($formateur_id, 'fsbdd_text_cp', true);
        $ville = get_post_meta($formateur_id, 'fsbdd_text_ville', true);
        
        $output = '';
        if (!empty($societe)) {
            $output .= esc_html($societe) . '<br>';
        }
        
        $nom_complet = trim($prenom . ' ' . $nom);
        if (!empty($nom_complet)) {
            $output .= esc_html($nom_complet) . '<br>';
        }
        
        if (!empty($adresse1)) {
            $output .= esc_html($adresse1) . '<br>';
        }
        
        if (!empty($adresse2)) {
            $output .= esc_html($adresse2) . '<br>';
        }
        
        $cp_ville = trim($cp . ' ' . $ville);
        if (!empty($cp_ville)) {
            $output .= esc_html($cp_ville);
        }
        
        return $output;
        
    } catch (Exception $e) {
        return '';
    }
}
add_shortcode('fsbdd_formateur_adresse', 'fsbdd_formateur_adresse_shortcode');

/**
 * Shortcode [fsbdd_formateur_planning] - Planning personnel d'un formateur
 */
function fsbdd_formateur_planning_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'index' => '1',
        'format' => 'table',
    ), $atts);
    
    try {
        $cpt_id = 0;
        if (!empty($content)) {
            $resolved = do_shortcode($content);
            $cpt_id = (int) trim($resolved);
        }
        
        $formateurs_ids = fsbdd_get_formateurs_from_planning($cpt_id);
        
        if (empty($formateurs_ids)) {
            return '';
        }
        
        $index = (int) $atts['index'] - 1;
        
        if (!isset($formateurs_ids[$index])) {
            return '';
        }
        
        $formateur_id = $formateurs_ids[$index];
        
        // Récupérer le planning et filtrer pour ce formateur
        $planning = get_post_meta($cpt_id, 'fsbdd_planning', true);
        
        if (empty($planning) || !is_array($planning)) {
            return '';
        }
        
        $seances_formateur = array();
        
        foreach ($planning as $seance) {
            if (isset($seance['fsbdd_gpformatr']) && is_array($seance['fsbdd_gpformatr'])) {
                foreach ($seance['fsbdd_gpformatr'] as $formateur_info) {
                    if (is_array($formateur_info) && 
                        isset($formateur_info['fsbdd_user_formateurrel']) &&
                        $formateur_info['fsbdd_user_formateurrel'] == $formateur_id) {
                        
                        $date_brute = isset($seance['fsbdd_planjour']) ? $seance['fsbdd_planjour'] : '';
                        $date_formatee = $date_brute;
                        if (!empty($date_brute)) {
                            $timestamp = strtotime($date_brute);
                            if ($timestamp !== false) {
                                $date_formatee = date('d/m/y', $timestamp);
                            }
                        }
                        
                        $periode = isset($formateur_info['fsbdd_dispjourform']) ? $formateur_info['fsbdd_dispjourform'] : '';
                        $role = isset($formateur_info['fsbdd_roleformateur']) ? $formateur_info['fsbdd_roleformateur'] : '';
                        
                        $horaires = '';
                        if ($periode === 'Matin') {
                            $debut = isset($seance['fsbdd_plannmatin']) ? $seance['fsbdd_plannmatin'] : '';
                            $fin = isset($seance['fsbdd_plannmatinfin']) ? $seance['fsbdd_plannmatinfin'] : '';
                            if (!empty($debut) && !empty($fin)) {
                                $horaires = $debut . ' - ' . $fin;
                            }
                        } elseif ($periode === 'Aprem') {
                            $debut = isset($seance['fsbdd_plannam']) ? $seance['fsbdd_plannam'] : '';
                            $fin = isset($seance['fsbdd_plannamfin']) ? $seance['fsbdd_plannamfin'] : '';
                            if (!empty($debut) && !empty($fin)) {
                                $horaires = $debut . ' - ' . $fin;
                            }
                        } elseif ($periode === 'Journ') {
                            $debut_matin = isset($seance['fsbdd_plannmatin']) ? $seance['fsbdd_plannmatin'] : '';
                            $fin_aprem = isset($seance['fsbdd_plannamfin']) ? $seance['fsbdd_plannamfin'] : '';
                            if (!empty($debut_matin) && !empty($fin_aprem)) {
                                $horaires = $debut_matin . ' - ' . $fin_aprem;
                            }
                        }
                        
                        $seances_formateur[] = array(
                            'date' => $date_formatee,
                            'periode' => $periode,
                            'horaires' => $horaires,
                            'role' => $role,
                            'date_tri' => $date_brute
                        );
                    }
                }
            }
        }
        
        if (empty($seances_formateur)) {
            return '<p>Aucune séance planifiée.</p>';
        }
        
        // Trier par date
        usort($seances_formateur, function($a, $b) {
            return strcmp($a['date_tri'], $b['date_tri']);
        });
        
        if ($atts['format'] === 'list') {
            $output = '<ul style="margin:0; padding-left:20px;">';
            foreach ($seances_formateur as $seance) {
                $output .= '<li>' . esc_html($seance['date']) . ' (' . esc_html($seance['periode']) . ') - ' . esc_html($seance['horaires']) . '</li>';
            }
            $output .= '</ul>';
            return $output;
        } else {
            // Format tableau
            $output = '<table style="width:100%; border-collapse:collapse; font-size:9px;">';
            $output .= '<thead><tr style="background-color:#f1f1f1;">';
            $output .= '<th style="padding:4px; border:1px solid #ccc;">Date</th>';
            $output .= '<th style="padding:4px; border:1px solid #ccc;">Période</th>';
            $output .= '<th style="padding:4px; border:1px solid #ccc;">Horaires</th>';
            $output .= '<th style="padding:4px; border:1px solid #ccc;">Rôle</th>';
            $output .= '</tr></thead><tbody>';
            
            foreach ($seances_formateur as $seance) {
                $output .= '<tr>';
                $output .= '<td style="padding:4px; border:1px solid #ccc;">' . esc_html($seance['date']) . '</td>';
                $output .= '<td style="padding:4px; border:1px solid #ccc;">' . esc_html($seance['periode']) . '</td>';
                $output .= '<td style="padding:4px; border:1px solid #ccc;">' . esc_html($seance['horaires']) . '</td>';
                $output .= '<td style="padding:4px; border:1px solid #ccc;">' . esc_html($seance['role']) . '</td>';
                $output .= '</tr>';
            }
            
            $output .= '</tbody></table>';
            return $output;
        }
        
    } catch (Exception $e) {
        return '';
    }
}
add_shortcode('fsbdd_formateur_planning', 'fsbdd_formateur_planning_shortcode');

/**
 * Shortcode [fsbdd_planning_global] - Planning filtré par formateur de la page actuelle
 */
function fsbdd_planning_global_shortcode($atts, $content = null) {
    try {
        $cpt_id = 0;
        if (!empty($content)) {
            $resolved = do_shortcode($content);
            $cpt_id = (int) trim($resolved);
        }
        
        if (empty($cpt_id)) {
            return '<p style="color:red;">ID du CPT non fourni</p>';
        }
        
        // Vérifier s'il y a un filtre formateur dans l'URL
        $formateur_filter = isset($_GET['formateur_filter']) ? (int) $_GET['formateur_filter'] : null;
        
        $planning = get_post_meta($cpt_id, 'fsbdd_planning', true);
        
        if (empty($planning) || !is_array($planning)) {
            return '<p>Aucun planning trouvé.</p>';
        }
        
        // Si pas de filtre, afficher tous les formateurs (comportement par défaut)
        if ($formateur_filter === null) {
            return fsbdd_planning_complet_shortcode($atts, $content);
        }
        
        // Récupérer les formateurs du planning pour obtenir l'ID du formateur filtré
        $formateurs_ids = fsbdd_get_formateurs_from_planning($cpt_id);
        
        if (empty($formateurs_ids)) {
            return '<p>Aucun formateur trouvé dans le planning.</p>';
        }
        
        // Vérifier que l'index du formateur existe
        $formateur_index = $formateur_filter - 1;
        if (!isset($formateurs_ids[$formateur_index])) {
            return '<p>Formateur non trouvé pour cet index.</p>';
        }
        
        $formateur_id_filtre = $formateurs_ids[$formateur_index];
        
        $planning_rows = array();
        
        foreach ($planning as $seance) {
            if (isset($seance['fsbdd_gpformatr']) && is_array($seance['fsbdd_gpformatr'])) {
                
                foreach ($seance['fsbdd_gpformatr'] as $formateur_info) {
                    if (is_array($formateur_info) && isset($formateur_info['fsbdd_user_formateurrel'])) {
                        
                        $formateur_id = (int) $formateur_info['fsbdd_user_formateurrel'];
                        
                        // Filtrer uniquement le formateur de la page actuelle
                        if ($formateur_id > 0 && $formateur_id === $formateur_id_filtre) {
                            $formateur_post = get_post($formateur_id);
                            if ($formateur_post && $formateur_post->post_type === 'formateur') {
                                
                                $prenom = get_post_meta($formateur_id, 'first_name', true);
                                $nom = get_post_meta($formateur_id, 'last_name', true);
                                $nom_complet = trim($prenom . ' ' . $nom);
                                
                                if (empty($nom_complet)) {
                                    $nom_complet = $formateur_post->post_title;
                                }
                                
                                $date_brute = isset($seance['fsbdd_planjour']) ? $seance['fsbdd_planjour'] : '';
                                $date_formatee = $date_brute;
                                if (!empty($date_brute)) {
                                    $timestamp = strtotime($date_brute);
                                    if ($timestamp !== false) {
                                        $date_formatee = date('d/m/y', $timestamp);
                                    }
                                }
                                
                                $periode = isset($formateur_info['fsbdd_dispjourform']) ? $formateur_info['fsbdd_dispjourform'] : '';
                                $horaires = '';
                                
                                if ($periode === 'Matin') {
                                    $debut = isset($seance['fsbdd_plannmatin']) ? $seance['fsbdd_plannmatin'] : '';
                                    $fin = isset($seance['fsbdd_plannmatinfin']) ? $seance['fsbdd_plannmatinfin'] : '';
                                    if (!empty($debut) && !empty($fin)) {
                                        $horaires = $debut . ' - ' . $fin;
                                    }
                                } elseif ($periode === 'Aprem') {
                                    $debut = isset($seance['fsbdd_plannam']) ? $seance['fsbdd_plannam'] : '';
                                    $fin = isset($seance['fsbdd_plannamfin']) ? $seance['fsbdd_plannamfin'] : '';
                                    if (!empty($debut) && !empty($fin)) {
                                        $horaires = $debut . ' - ' . $fin;
                                    }
                                } elseif ($periode === 'Journ') {
                                    $debut_matin = isset($seance['fsbdd_plannmatin']) ? $seance['fsbdd_plannmatin'] : '';
                                    $fin_aprem = isset($seance['fsbdd_plannamfin']) ? $seance['fsbdd_plannamfin'] : '';
                                    if (!empty($debut_matin) && !empty($fin_aprem)) {
                                        $horaires = $debut_matin . ' - ' . $fin_aprem;
                                    }
                                }
                                
                                $role = isset($formateur_info['fsbdd_roleformateur']) ? $formateur_info['fsbdd_roleformateur'] : '';
                                
                                // Récupérer le commentaire du formateur
                                $commentaire_formateur = isset($formateur_info['fsbdd_commplanfourn']) ? trim($formateur_info['fsbdd_commplanfourn']) : '';
                                
                                // Ajouter le commentaire au nom si présent
                                $nom_avec_commentaire = $nom_complet;
                                if (!empty($commentaire_formateur)) {
                                    $nom_avec_commentaire .= '<br><em style="font-size:7px; color:#666;">' . esc_html($commentaire_formateur) . '</em>';
                                }
                                
                                // Récupérer les informations de fournisseurs pour cette séance
                                $infos_fournisseurs = '';
                                if (isset($seance['fournisseur_salle']) && is_array($seance['fournisseur_salle'])) {
                                    $fournisseurs_info = array();
                                    foreach ($seance['fournisseur_salle'] as $fournisseur_data) {
                                        if (is_array($fournisseur_data)) {
                                            $product_name = isset($fournisseur_data['fsbdd_selected_product_name']) ? $fournisseur_data['fsbdd_selected_product_name'] : '';
                                            $dispo_fournisseur = isset($fournisseur_data['fsbdd_dispjourform']) ? $fournisseur_data['fsbdd_dispjourform'] : '';
                                            $commentaire_fournisseur = isset($fournisseur_data['fsbdd_commplanfourn']) ? trim($fournisseur_data['fsbdd_commplanfourn']) : '';
                                            
                                            if (!empty($product_name)) {
                                                $info_line = $product_name;
                                                if (!empty($dispo_fournisseur) && $dispo_fournisseur !== 'Journ') {
                                                    $info_line .= ' (' . $dispo_fournisseur . ')';
                                                }
                                                if (!empty($commentaire_fournisseur)) {
                                                    $info_line .= '<br><em style="font-size:7px; color:#666;">' . esc_html($commentaire_fournisseur) . '</em>';
                                                }
                                                $fournisseurs_info[] = $info_line;
                                            }
                                        }
                                    }
                                    $infos_fournisseurs = implode('<br>', $fournisseurs_info);
                                }
                                
                                $planning_rows[] = array(
                                    'date' => $date_formatee,
                                    'horaires' => $horaires,
                                    'nom' => $nom_avec_commentaire,
                                    'role' => $role,
                                    'date_tri' => $date_brute,
                                    'periode_tri' => $periode,
                                    'infos' => $infos_fournisseurs
                                );
                            }
                        }
                    }
                }
            }
        }
        
        if (empty($planning_rows)) {
            return '<p>Aucun formateur trouvé dans le planning.</p>';
        }
        
        // Trier par date puis par période
        usort($planning_rows, function($a, $b) {
            $date_cmp = strcmp($a['date_tri'], $b['date_tri']);
            if ($date_cmp !== 0) {
                return $date_cmp;
            }
            
            $ordre_periode = array('Matin' => 1, 'Journ' => 2, 'Aprem' => 3);
            $a_ordre = isset($ordre_periode[$a['periode_tri']]) ? $ordre_periode[$a['periode_tri']] : 4;
            $b_ordre = isset($ordre_periode[$b['periode_tri']]) ? $ordre_periode[$b['periode_tri']] : 4;
            
            return $a_ordre - $b_ordre;
        });
        
        // Récupérer les informations du formateur pour le titre
        $formateur_post = get_post($formateur_id_filtre);
        $prenom = get_post_meta($formateur_id_filtre, 'first_name', true);
        $nom = get_post_meta($formateur_id_filtre, 'last_name', true);
        $nom_complet = trim($prenom . ' ' . $nom);
        
        if (empty($nom_complet)) {
            $nom_complet = $formateur_post->post_title;
        }
        
        // Calculer le nombre total de jours
        $jours_uniques = array();
        foreach ($planning_rows as $row) {
            if (!empty($row['date_tri'])) {
                $jours_uniques[$row['date_tri']] = true;
            }
        }
        $nombre_jours = count($jours_uniques);
        
        // Générer le titre simplifié
        $titre = '<h3 style="margin-bottom:8px; margin-top:8px; text-align:center; font-size:10px; font-weight:bold;">Planning ' . esc_html($nom_complet) . ' : ' . $nombre_jours . ' jour' . ($nombre_jours > 1 ? 's' : '') . '</h3>';
        
        $output = $titre;
        $output .= '<table style="width:100%; border-collapse:collapse; font-size:8px; font-family:Arial, sans-serif;">';
        $output .= '<thead><tr style="background-color:#f1f1f1;">';
        $output .= '<th style="width:8%; padding:4px; border:1px solid #ccc; text-align:left; font-weight:bold;">DATE</th>';
        $output .= '<th style="width:10%; padding:4px; border:1px solid #ccc; text-align:left; font-weight:bold;">HORAIRES</th>';
        $output .= '<th style="width:35%; padding:4px; border:1px solid #ccc; text-align:left; font-weight:bold;">NOM</th>';
        $output .= '<th style="width:10%; padding:4px; border:1px solid #ccc; text-align:left; font-weight:bold;">RÔLE</th>';
        $output .= '<th style="width:37%; padding:4px; border:1px solid #ccc; text-align:left; font-weight:bold;">INFOS</th>';
        $output .= '</tr></thead><tbody>';
        
        foreach ($planning_rows as $row) {
            $output .= '<tr>';
            $output .= '<td style="padding:3px; border:1px solid #ccc;">' . esc_html($row['date']) . '</td>';
            $output .= '<td style="padding:3px; border:1px solid #ccc;">' . esc_html($row['horaires']) . '</td>';
            $output .= '<td style="padding:3px; border:1px solid #ccc;">' . $row['nom'] . '</td>';
            $output .= '<td style="padding:3px; border:1px solid #ccc;">' . esc_html($row['role']) . '</td>';
            $output .= '<td style="padding:3px; border:1px solid #ccc;">' . $row['infos'] . '</td>';
            $output .= '</tr>';
        }
        
        $output .= '</tbody></table>';
        return $output;
        
    } catch (Exception $e) {
        return '<p style="color:red;">Erreur: ' . esc_html($e->getMessage()) . '</p>';
    }
}
add_shortcode('fsbdd_planning_global', 'fsbdd_planning_global_shortcode');

/**
 * Shortcode [fsbdd_planning_complet] - Planning global de tous les formateurs (version complète)
 */
function fsbdd_planning_complet_shortcode($atts, $content = null) {
    try {
        $cpt_id = 0;
        if (!empty($content)) {
            $resolved = do_shortcode($content);
            $cpt_id = (int) trim($resolved);
        }
        
        if (empty($cpt_id)) {
            return '<p style="color:red;">ID du CPT non fourni</p>';
        }
        
        $planning = get_post_meta($cpt_id, 'fsbdd_planning', true);
        
        if (empty($planning) || !is_array($planning)) {
            return '<p>Aucun planning trouvé.</p>';
        }
        
        $planning_rows = array();
        
        foreach ($planning as $seance) {
            if (isset($seance['fsbdd_gpformatr']) && is_array($seance['fsbdd_gpformatr'])) {
                
                foreach ($seance['fsbdd_gpformatr'] as $formateur_info) {
                    if (is_array($formateur_info) && isset($formateur_info['fsbdd_user_formateurrel'])) {
                        
                        $formateur_id = (int) $formateur_info['fsbdd_user_formateurrel'];
                        
                        if ($formateur_id > 0) {
                            $formateur_post = get_post($formateur_id);
                            if ($formateur_post && $formateur_post->post_type === 'formateur') {
                                
                                $prenom = get_post_meta($formateur_id, 'first_name', true);
                                $nom = get_post_meta($formateur_id, 'last_name', true);
                                $nom_complet = trim($prenom . ' ' . $nom);
                                
                                if (empty($nom_complet)) {
                                    $nom_complet = $formateur_post->post_title;
                                }
                                
                                $date_brute = isset($seance['fsbdd_planjour']) ? $seance['fsbdd_planjour'] : '';
                                $date_formatee = $date_brute;
                                if (!empty($date_brute)) {
                                    $timestamp = strtotime($date_brute);
                                    if ($timestamp !== false) {
                                        $date_formatee = date('d/m/y', $timestamp);
                                    }
                                }
                                
                                $periode = isset($formateur_info['fsbdd_dispjourform']) ? $formateur_info['fsbdd_dispjourform'] : '';
                                $horaires = '';
                                
                                if ($periode === 'Matin') {
                                    $debut = isset($seance['fsbdd_plannmatin']) ? $seance['fsbdd_plannmatin'] : '';
                                    $fin = isset($seance['fsbdd_plannmatinfin']) ? $seance['fsbdd_plannmatinfin'] : '';
                                    if (!empty($debut) && !empty($fin)) {
                                        $horaires = $debut . ' - ' . $fin;
                                    }
                                } elseif ($periode === 'Aprem') {
                                    $debut = isset($seance['fsbdd_plannam']) ? $seance['fsbdd_plannam'] : '';
                                    $fin = isset($seance['fsbdd_plannamfin']) ? $seance['fsbdd_plannamfin'] : '';
                                    if (!empty($debut) && !empty($fin)) {
                                        $horaires = $debut . ' - ' . $fin;
                                    }
                                } elseif ($periode === 'Journ') {
                                    $debut_matin = isset($seance['fsbdd_plannmatin']) ? $seance['fsbdd_plannmatin'] : '';
                                    $fin_aprem = isset($seance['fsbdd_plannamfin']) ? $seance['fsbdd_plannamfin'] : '';
                                    if (!empty($debut_matin) && !empty($fin_aprem)) {
                                        $horaires = $debut_matin . ' - ' . $fin_aprem;
                                    }
                                }
                                
                                $role = isset($formateur_info['fsbdd_roleformateur']) ? $formateur_info['fsbdd_roleformateur'] : '';
                                
                                // Récupérer le commentaire du formateur
                                $commentaire_formateur = isset($formateur_info['fsbdd_commplanfourn']) ? trim($formateur_info['fsbdd_commplanfourn']) : '';
                                
                                // Ajouter le commentaire au nom si présent
                                $nom_avec_commentaire = $nom_complet;
                                if (!empty($commentaire_formateur)) {
                                    $nom_avec_commentaire .= '<br><em style="font-size:7px; color:#666;">' . esc_html($commentaire_formateur) . '</em>';
                                }
                                
                                // Récupérer les informations de fournisseurs pour cette séance
                                $infos_fournisseurs = '';
                                if (isset($seance['fournisseur_salle']) && is_array($seance['fournisseur_salle'])) {
                                    $fournisseurs_info = array();
                                    foreach ($seance['fournisseur_salle'] as $fournisseur_data) {
                                        if (is_array($fournisseur_data)) {
                                            $product_name = isset($fournisseur_data['fsbdd_selected_product_name']) ? $fournisseur_data['fsbdd_selected_product_name'] : '';
                                            $dispo_fournisseur = isset($fournisseur_data['fsbdd_dispjourform']) ? $fournisseur_data['fsbdd_dispjourform'] : '';
                                            $commentaire_fournisseur = isset($fournisseur_data['fsbdd_commplanfourn']) ? trim($fournisseur_data['fsbdd_commplanfourn']) : '';
                                            
                                            if (!empty($product_name)) {
                                                $info_line = $product_name;
                                                if (!empty($dispo_fournisseur) && $dispo_fournisseur !== 'Journ') {
                                                    $info_line .= ' (' . $dispo_fournisseur . ')';
                                                }
                                                if (!empty($commentaire_fournisseur)) {
                                                    $info_line .= '<br><em style="font-size:7px; color:#666;">' . esc_html($commentaire_fournisseur) . '</em>';
                                                }
                                                $fournisseurs_info[] = $info_line;
                                            }
                                        }
                                    }
                                    $infos_fournisseurs = implode('<br>', $fournisseurs_info);
                                }
                                
                                $planning_rows[] = array(
                                    'date' => $date_formatee,
                                    'horaires' => $horaires,
                                    'nom' => $nom_avec_commentaire,
                                    'role' => $role,
                                    'date_tri' => $date_brute,
                                    'periode_tri' => $periode,
                                    'infos' => $infos_fournisseurs
                                );
                            }
                        }
                    }
                }
            }
        }
        
        if (empty($planning_rows)) {
            return '<p>Aucun formateur trouvé dans le planning.</p>';
        }
        
        // Trier par date puis par période
        usort($planning_rows, function($a, $b) {
            $date_cmp = strcmp($a['date_tri'], $b['date_tri']);
            if ($date_cmp !== 0) {
                return $date_cmp;
            }
            
            $ordre_periode = array('Matin' => 1, 'Journ' => 2, 'Aprem' => 3);
            $a_ordre = isset($ordre_periode[$a['periode_tri']]) ? $ordre_periode[$a['periode_tri']] : 4;
            $b_ordre = isset($ordre_periode[$b['periode_tri']]) ? $ordre_periode[$b['periode_tri']] : 4;
            
            return $a_ordre - $b_ordre;
        });
        
        $output = '<table style="width:100%; border-collapse:collapse; font-size:8px; font-family:Arial, sans-serif;">';
        $output .= '<thead><tr style="background-color:#f1f1f1;">';
        $output .= '<th style="width:8%; padding:4px; border:1px solid #ccc; text-align:left; font-weight:bold;">DATE</th>';
        $output .= '<th style="width:10%; padding:4px; border:1px solid #ccc; text-align:left; font-weight:bold;">HORAIRES</th>';
        $output .= '<th style="width:35%; padding:4px; border:1px solid #ccc; text-align:left; font-weight:bold;">NOM</th>';
        $output .= '<th style="width:10%; padding:4px; border:1px solid #ccc; text-align:left; font-weight:bold;">RÔLE</th>';
        $output .= '<th style="width:37%; padding:4px; border:1px solid #ccc; text-align:left; font-weight:bold;">INFOS</th>';
        $output .= '</tr></thead><tbody>';
        
        foreach ($planning_rows as $row) {
            $output .= '<tr>';
            $output .= '<td style="padding:3px; border:1px solid #ccc;">' . esc_html($row['date']) . '</td>';
            $output .= '<td style="padding:3px; border:1px solid #ccc;">' . esc_html($row['horaires']) . '</td>';
            $output .= '<td style="padding:3px; border:1px solid #ccc;">' . $row['nom'] . '</td>';
            $output .= '<td style="padding:3px; border:1px solid #ccc;">' . esc_html($row['role']) . '</td>';
            $output .= '<td style="padding:3px; border:1px solid #ccc;">' . $row['infos'] . '</td>';
            $output .= '</tr>';
        }
        
        $output .= '</tbody></table>';
        return $output;
        
    } catch (Exception $e) {
        return '<p style="color:red;">Erreur: ' . esc_html($e->getMessage()) . '</p>';
    }
}
add_shortcode('fsbdd_planning_complet', 'fsbdd_planning_complet_shortcode');

/**
 * Shortcode [fsbdd_formateur_couts] - Coûts d'un formateur
 */
function fsbdd_formateur_couts_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'index' => '1',
        'field' => 'montant_total',
        'format' => 'number',
    ), $atts);
    
    try {
        $cpt_id = 0;
        if (!empty($content)) {
            $resolved = do_shortcode($content);
            $cpt_id = (int) trim($resolved);
        }
        
        $formateurs_ids = fsbdd_get_formateurs_from_planning($cpt_id);
        
        if (empty($formateurs_ids)) {
            return '';
        }
        
        $index = (int) $atts['index'] - 1;
        
        if (!isset($formateurs_ids[$index])) {
            return '';
        }
        
        $formateur_id = $formateurs_ids[$index];
        
        $grpctsformation = get_post_meta($cpt_id, 'fsbdd_grpctsformation', true);
        
        if (empty($grpctsformation) || !is_array($grpctsformation)) {
            return '';
        }
        
        $cout_formateur = null;
        foreach ($grpctsformation as $cout_item) {
            if (isset($cout_item['fsbdd_typechargedue']) && $cout_item['fsbdd_typechargedue'] === '1') {
                if (isset($cout_item['fsbdd_selectcoutform']) && $cout_item['fsbdd_selectcoutform'] == $formateur_id) {
                    $cout_formateur = $cout_item;
                    break;
                }
            }
        }
        
        if (!$cout_formateur) {
            return '';
        }
        
        $value = 0;
        switch ($atts['field']) {
            case 'montant_total':
                $value = isset($cout_formateur['fsbdd_montrechrge']) ? (float) $cout_formateur['fsbdd_montrechrge'] : 0;
                break;
            case 'cout_jour':
                $value = isset($cout_formateur['fsbdd_coutjourf']) ? (float) $cout_formateur['fsbdd_coutjourf'] : 0;
                break;
            case 'cout_demijour':
                $value = isset($cout_formateur['fsbdd_coutdemijourf']) ? (float) $cout_formateur['fsbdd_coutdemijourf'] : 0;
                break;
            case 'frais_mission':
                $value = isset($cout_formateur['fsbdd_fraismission']) ? (float) $cout_formateur['fsbdd_fraismission'] : 0;
                break;
            case 'qtite_jour':
                $value = isset($cout_formateur['fsbdd_qtitectjour']) ? (int) $cout_formateur['fsbdd_qtitectjour'] : 0;
                break;
            case 'qtite_demijour':
                $value = isset($cout_formateur['fsbdd_qtitectdemijour']) ? (int) $cout_formateur['fsbdd_qtitectdemijour'] : 0;
                break;
            case 'total_frais_mission':
                $value = isset($cout_formateur['fsbdd_ttfraismission']) ? (float) $cout_formateur['fsbdd_ttfraismission'] : 0;
                break;
            default:
                return '';
        }
        
        if ($atts['format'] === 'currency') {
            return number_format($value, 2, ',', ' ') . ' €';
        } else {
            return $value;
        }
        
    } catch (Exception $e) {
        return '';
    }
}
add_shortcode('fsbdd_formateur_couts', 'fsbdd_formateur_couts_shortcode');

/**
 * Shortcode [fsbdd_entreprises_commandes] - Liste des entreprises des commandes liées au CPT
 */
function fsbdd_entreprises_commandes_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'separator' => ', ', // Séparateur entre les entreprises
        'unique' => 'true', // Enlever les doublons ou non
        'confirmed_only' => 'true', // Seulement les commandes confirmées (niveau 4)
    ), $atts);
    
    try {
        $cpt_id = 0;
        if (!empty($content)) {
            $resolved = do_shortcode($content);
            $cpt_id = (int) trim($resolved);
        }
        
        if (empty($cpt_id)) {
            return '';
        }
        
        // Vérifier que le CPT existe
        $cpt_post = get_post($cpt_id);
        if (!$cpt_post || $cpt_post->post_type !== 'action-de-formation') {
            return '';
        }
        
        // Récupérer toutes les commandes liées à ce CPT
        global $wpdb;
        
        $query = "
            SELECT DISTINCT oi.order_id
            FROM {$wpdb->prefix}woocommerce_order_items AS oi
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim
                ON oi.order_item_id = oim.order_item_id
            WHERE oim.meta_key = 'fsbdd_relsessaction_cpt_produit'
            AND oim.meta_value = %s
        ";
        $order_ids = $wpdb->get_col($wpdb->prepare($query, $cpt_id));
        
        if (empty($order_ids)) {
            return '';
        }
        
        $entreprises = array();
        
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) {
                continue;
            }
            
            // Si on veut seulement les commandes confirmées
            if ($atts['confirmed_only'] === 'true') {
                $affaire_niveau = $order->get_meta('fsbdd_affaireniveau', true);
                if ($affaire_niveau !== '4') {
                    continue;
                }
            }
            
            // Récupérer le nom de l'entreprise
            $billing_company = $order->get_billing_company();
            
            if (!empty($billing_company)) {
                if ($atts['unique'] === 'true') {
                    // Éviter les doublons
                    if (!in_array($billing_company, $entreprises)) {
                        $entreprises[] = $billing_company;
                    }
                } else {
                    // Garder tous, même les doublons
                    $entreprises[] = $billing_company;
                }
            }
        }
        
        if (empty($entreprises)) {
            return '';
        }
        
        // Trier par ordre alphabétique
        sort($entreprises);
        
        return implode($atts['separator'], array_map('esc_html', $entreprises));
        
    } catch (Exception $e) {
        return '';
    }
}
add_shortcode('fsbdd_entreprises_commandes', 'fsbdd_entreprises_commandes_shortcode');

// Ajouter tous les shortcodes au filtre E2PDF
add_filter('e2pdf_extension_render_shortcodes_tags', function($shortcodes) {
    $shortcodes[] = 'fsbdd_formateur_count';
    $shortcodes[] = 'fsbdd_formateur_info';
    $shortcodes[] = 'fsbdd_formateur_adresse';
    $shortcodes[] = 'fsbdd_formateur_planning';
    $shortcodes[] = 'fsbdd_planning_global';
    $shortcodes[] = 'fsbdd_planning_complet';
    $shortcodes[] = 'fsbdd_formateur_couts';
    $shortcodes[] = 'fsbdd_entreprises_commandes';
    return $shortcodes;
});
