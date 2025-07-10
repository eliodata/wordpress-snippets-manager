<?php
/**
 * Snippet ID: 119
 * Name: Documents de sortie e2pdf afficher liste stagiaires shortcode
 * Description: <p>description 1</p>
 * @active true
 */

// 1) Filtre pour informer E2Pdf qu'il doit parser ces shortcodes
add_filter('e2pdf_extension_render_shortcodes_tags', function($shortcodes) {
    $shortcodes[] = 'fsbdd_list_stagiaires';
    $shortcodes[] = 'fsbdd_list_stagiaires_bullets';
    $shortcodes[] = 'fsbdd_cpt_list_stagiaires';
    $shortcodes[] = 'fsbdd_cpt_list_stagiaires_bullets';
    return $shortcodes;
});

/**
 * Fonction utilitaire pour déterminer le contexte (commande ou CPT)
 * et récupérer les stagiaires en conséquence
 */
function fsbdd_get_stagiaires_data($content = null, $atts = array()) {
    // Déterminer si on est dans le contexte d'une commande
    $order_id = null;
    if (!empty($content)) {
        $resolved_content = do_shortcode($content);
        if (is_numeric($resolved_content)) {
            $potential_order = wc_get_order((int) $resolved_content);
            if ($potential_order) {
                $order_id = (int) $resolved_content;
            }
        }
    }
    
    // Si on a trouvé une commande valide, utiliser l'approche originale
    if ($order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            $gpeffectif = $order->get_meta('fsbdd_gpeffectif', true);
            if (!empty($gpeffectif) && is_array($gpeffectif)) {
                return array(
                    'type' => 'order',
                    'id' => $order_id,
                    'data' => $gpeffectif
                );
            }
        }
    }
    
    // Sinon, essayer l'approche CPT
    $cpt_id = 0;
    
    // Récupérer l'ID du CPT depuis les attributs
    if (!empty($atts['cpt_id'])) {
        $cpt_id = (int) $atts['cpt_id'];
    }
    
    // Ou depuis le contenu si pas dans les attributs
    if (empty($cpt_id) && !empty($content)) {
        $resolved = do_shortcode($content);
        $potential_cpt_id = (int) trim($resolved);
        $cpt_post = get_post($potential_cpt_id);
        if ($cpt_post && $cpt_post->post_type === 'action-de-formation') {
            $cpt_id = $potential_cpt_id;
        }
    }
    
    // Ou depuis les paramètres GET
    if (empty($cpt_id) && isset($_GET['post'])) {
        $potential_cpt_id = (int) $_GET['post'];
        $cpt_post = get_post($potential_cpt_id);
        if ($cpt_post && $cpt_post->post_type === 'action-de-formation') {
            $cpt_id = $potential_cpt_id;
        }
    } elseif (empty($cpt_id) && isset($_GET['cpt_id'])) {
        $cpt_id = (int) $_GET['cpt_id'];
    }
    
    // Fallback E2PDF : essayer de récupérer l'ID du post en cours
    if (empty($cpt_id)) {
        global $post;
        if ($post && $post->ID && $post->post_type === 'action-de-formation') {
            $cpt_id = $post->ID;
        }
    }
    
    if (empty($cpt_id)) {
        return array(
            'type' => 'error',
            'message' => 'Impossible de détecter l\'ID de la commande ou du CPT action-de-formation.'
        );
    }
    
    // Vérifier que le CPT existe
    $cpt_post = get_post($cpt_id);
    if (!$cpt_post || $cpt_post->post_type !== 'action-de-formation') {
        return array(
            'type' => 'error',
            'message' => 'CPT action-de-formation introuvable (ID: '.$cpt_id.').'
        );
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
        return array(
            'type' => 'error',
            'message' => 'Aucune commande trouvée pour cette action de formation.'
        );
    }
    
    // Récupérer tous les stagiaires de toutes les commandes CONFIRMÉES uniquement
    $all_gpeffectif = array();
    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            continue;
        }
        
        // ✅ NOUVELLE CONDITION : Vérifier que la commande est confirmée (niveau 4)
        $affaire_niveau = $order->get_meta('fsbdd_affaireniveau', true);
        if ($affaire_niveau !== '4') {
            continue; // Ignorer cette commande si elle n'est pas confirmée
        }
        
        $gpeffectif = $order->get_meta('fsbdd_gpeffectif', true);
        if (!empty($gpeffectif) && is_array($gpeffectif)) {
            // Ajouter l'ID de commande à chaque stagiaire pour traçabilité
            foreach ($gpeffectif as $item) {
                $item['_order_id'] = $order_id;
                $all_gpeffectif[] = $item;
            }
        }
    }
    
    return array(
        'type' => 'cpt',
        'id' => $cpt_id,
        'data' => $all_gpeffectif,
        'order_ids' => $order_ids
    );
}

/**
 * Shortcode [fsbdd_list_stagiaires] - Version unifiée
 */
function fsbdd_list_stagiaires_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'cpt_id' => '',
        'order_id' => '',
    ), $atts);
    
    $result = fsbdd_get_stagiaires_data($content, $atts);
    
    if ($result['type'] === 'error') {
        return '<p style="color:red;">' . $result['message'] . '</p>';
    }
    
    $gpeffectif = $result['data'];
    if (empty($gpeffectif)) {
        return '<p>Aucun stagiaire enregistré.</p>';
    }
    
    // Construire un tableau des stagiaires
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
            if ($result['type'] === 'cpt') {
                // Pour les CPT, organiser par commande
                $order_id = $item['_order_id'];
                if (!isset($stagiaires[$order_id])) {
                    $stagiaires[$order_id] = array();
                }
                
                $stagiaire_key = $prenom . '_' . $nom;
                if (!isset($stagiaires[$order_id][$stagiaire_key])) {
                    $stagiaires[$order_id][$stagiaire_key] = array(
                        'prenom' => $prenom,
                        'nom'    => $nom,
                        'convoc' => $convoc_type
                    );
                } else {
                    // Gérer les types de convocation multiples
                    $existing_convoc = $stagiaires[$order_id][$stagiaire_key]['convoc'];
                    if ($existing_convoc !== $convoc_type && strpos($existing_convoc, $convoc_type) === false) {
                        if (strpos($existing_convoc, '/') === false) {
                            $stagiaires[$order_id][$stagiaire_key]['convoc'] = $existing_convoc . '/' . $convoc_type;
                        } else {
                            $existing_types = explode('/', $existing_convoc);
                            if (!in_array($convoc_type, $existing_types)) {
                                $stagiaires[$order_id][$stagiaire_key]['convoc'] = $existing_convoc . '/' . $convoc_type;
                            }
                        }
                    }
                }
            } else {
                // Pour les commandes, pas de gestion de doublons
                $stagiaires[] = array(
                    'prenom' => $prenom,
                    'nom'    => $nom,
                    'convoc' => $convoc_type
                );
            }
        }
    }
    
    if (empty($stagiaires)) {
        return '<p>Aucun stagiaire valide trouvé.</p>';
    }
    
    // Compter le nombre total de stagiaires
    $nb_stagiaires = 0;
    if ($result['type'] === 'cpt') {
        foreach ($stagiaires as $order_stagiaires) {
            $nb_stagiaires += count($order_stagiaires);
        }
    } else {
        $nb_stagiaires = count($stagiaires);
    }
    
    // Afficher l'effectif + le tableau HTML
    $output = sprintf(
        '<p style="margin-bottom:8px; margin-top:8px; text-align:center; font-weight:bold; font-size:10px;">
            Effectif : %d stagiaire%s
        </p>',
        $nb_stagiaires,
        $nb_stagiaires > 1 ? 's' : ''
    );
    
    // ✅ AJOUT : Afficher fsbdd_infoletrmiss en chapeau pour les CPT
    if ($result['type'] === 'cpt') {
        $cpt_infoletrmiss = get_post_meta($result['id'], 'fsbdd_infoletrmiss', true);
        if (!empty($cpt_infoletrmiss)) {
            $output .= sprintf(
                '<p style="margin-bottom:4px; margin-top:0px; text-align:left; font-size:8px; font-style:italic; color:#3F51B5; border-left:1px solid #ccc; padding-left:8px;">
                    %s
                </p>',
                esc_html($cpt_infoletrmiss)
            );
        }
    }
    
    // ✅ COLONNES MODIFIÉES : Suppression de la colonne "Commande(s)" pour les CPT
    if ($result['type'] === 'cpt') {
        $output .= '
            <table style="width:100%; border-collapse:collapse; font-size:8px; font-family:Arial, sans-serif;">
                <thead>
                    <tr style="background-color:#f1f1f1;">
                        <th style="width:40%; padding:3px 4px; border:1px solid #ccc; text-align:left; vertical-align:middle;">PRÉNOM</th>
                        <th style="width:40%; padding:3px 4px; border:1px solid #ccc; text-align:left; vertical-align:middle;">NOM</th>
                        <th style="width:20%; padding:3px 4px; border:1px solid #ccc; text-align:left; vertical-align:middle;">SESSION</th>
                    </tr>
                </thead>
                <tbody>
        ';
        
        // Parcourir les commandes et afficher les stagiaires groupés par commande
        foreach ($stagiaires as $order_id => $order_stagiaires) {
            // Récupérer les informations de la commande
            $order = wc_get_order($order_id);
            $company_name = '';
            $variations = array();
            $categories = array();
            
            if ($order) {
                $company_name = $order->get_billing_company();
                
                // Si pas de nom d'entreprise, utiliser "Particulier"
                if (empty($company_name)) {
                    $company_name = 'Particulier';
                }
                
                // Récupérer les variations et catégories depuis les items de la commande
                $items = $order->get_items();
                foreach ($items as $item) {
                    // Vérifier si c'est l'item lié au CPT
                    $cpt_produit = $item->get_meta('fsbdd_relsessaction_cpt_produit');
                    if ($cpt_produit == $result['id']) {
                        // Récupérer les variations
                        if ($item->get_variation_id()) {
                            $variation = wc_get_product($item->get_variation_id());
                            if ($variation) {
                                $attributes = $variation->get_variation_attributes();
                                foreach ($attributes as $attr_name => $attr_value) {
                                    $variations[] = $attr_value;
                                }
                            }
                        }
                        
                        // Récupérer la meta choix_categorie
                        $choix_categorie = $item->get_meta('choix_categorie');
                        if (!empty($choix_categorie)) {
                            if (is_array($choix_categorie)) {
                                $categories = array_merge($categories, $choix_categorie);
                            } else {
                                $categories[] = $choix_categorie;
                            }
                        }
                    }
                }
            }
            
            // Construire la ligne d'info entreprise - Toujours afficher une ligne
            $info_parts = array();
            $info_parts[] = esc_html($company_name);
            
            // Nombre de personnes
            $nb_pers = count($order_stagiaires);
            $info_parts[] = $nb_pers . ' pers';
            
            // Variations
            if (!empty($variations)) {
                $info_parts[] = implode(', ', array_unique($variations));
            }
            
            // Catégories
            if (!empty($categories)) {
                $info_parts[] = implode(', ', array_unique($categories));
            }
            
            // ✅ SUPPRESSION : Ne plus ajouter fsbdd_infoletrmiss ici car c'est maintenant en chapeau global
            
            $info_line = implode(' - ', $info_parts);
            
            $output .= '<tr>';
            $output .= '<td colspan="3" style="padding:2px 4px; border:1px solid #ccc; background-color:#f5f5f5; font-weight:bold; font-style:italic; font-size:7px; height:16px; vertical-align:middle;">' . $info_line . '</td>';
            $output .= '</tr>';
            
            // Afficher les stagiaires de cette commande
            foreach ($order_stagiaires as $stagiaire) {
                $prenom = esc_html($stagiaire['prenom']);
                $nom    = esc_html($stagiaire['nom']);
                $convoc = esc_html($stagiaire['convoc']);
                
                $output .= '<tr>';
                $output .= '<td style="width:40%; padding:2px 4px; border:1px solid #ccc; height:16px; vertical-align:middle;">' . $prenom . '</td>';
                $output .= '<td style="width:40%; padding:2px 4px; border:1px solid #ccc; height:16px; vertical-align:middle;">' . $nom . '</td>';
                $output .= '<td style="width:20%; padding:2px 4px; border:1px solid #ccc; height:16px; vertical-align:middle;">' . $convoc . '</td>';
                $output .= '</tr>';
            }
        }
    } else {
        // Pour les commandes, garder le format original
        $output .= '
            <table style="width:100%; border-collapse:collapse; font-size:8px; font-family:Arial, sans-serif;">
                <thead>
                    <tr style="background-color:#f1f1f1;">
                        <th style="width:35%; padding:3px 4px; border:1px solid #ccc; text-align:left; vertical-align:middle;">PRÉNOM</th>
                        <th style="width:35%; padding:3px 4px; border:1px solid #ccc; text-align:left; vertical-align:middle;">NOM</th>
                        <th style="width:30%; padding:3px 4px; border:1px solid #ccc; text-align:left; vertical-align:middle;">SESSION</th>
                    </tr>
                </thead>
                <tbody>
        ';
        
        foreach ($stagiaires as $stagiaire) {
            $prenom = esc_html($stagiaire['prenom']);
            $nom    = esc_html($stagiaire['nom']);
            $convoc = esc_html($stagiaire['convoc']);
            
            $output .= '<tr>';
            $output .= '<td style="width:35%; padding:2px 4px; border:1px solid #ccc; height:16px; vertical-align:middle;">' . $prenom . '</td>';
            $output .= '<td style="width:35%; padding:2px 4px; border:1px solid #ccc; height:16px; vertical-align:middle;">' . $nom . '</td>';
            $output .= '<td style="width:30%; padding:2px 4px; border:1px solid #ccc; height:16px; vertical-align:middle;">' . $convoc . '</td>';
            $output .= '</tr>';
        }
    }
    
    $output .= '</tbody></table>';
    return $output;
}
add_shortcode('fsbdd_list_stagiaires', 'fsbdd_list_stagiaires_shortcode');

/**
 * Shortcode [fsbdd_list_stagiaires_bullets] - Version unifiée
 */
function fsbdd_list_stagiaires_bullets_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'cpt_id' => '',
        'order_id' => '',
    ), $atts);
    
    $result = fsbdd_get_stagiaires_data($content, $atts);
    
    if ($result['type'] === 'error') {
        return '<p style="color:red;">' . $result['message'] . '</p>';
    }
    
    $gpeffectif = $result['data'];
    if (empty($gpeffectif)) {
        return '<p>Aucun stagiaire enregistré.</p>';
    }
    
    $stagiaires = array();
    foreach ($gpeffectif as $item) {
        $prenom = isset($item['fsbdd_prenomstagiaire']) ? trim($item['fsbdd_prenomstagiaire']) : '';
        $nom    = isset($item['fsbdd_nomstagiaire'])    ? trim($item['fsbdd_nomstagiaire'])    : '';
        
        if ($prenom || $nom) {
            $full_name = "{$prenom} {$nom}";
            
            if ($result['type'] === 'cpt') {
                // Pour les CPT, gérer les doublons
                $stagiaire_key = $prenom . '_' . $nom;
                if (!isset($stagiaires[$stagiaire_key])) {
                    $stagiaires[$stagiaire_key] = $full_name;
                }
            } else {
                // Pour les commandes, pas de gestion de doublons
                $stagiaires[] = $full_name;
            }
        }
    }
    
    if (empty($stagiaires)) {
        return '<p>Aucun stagiaire valide trouvé.</p>';
    }
    
    // Convertir le tableau associatif en indexé pour les CPT
    if ($result['type'] === 'cpt') {
        $stagiaires = array_values($stagiaires);
    }
    
    // Limiter et organiser en colonnes
    $stagiaires = array_slice($stagiaires, 0, 40);
    $total_stagiaires = count($stagiaires);
    $col_count = ceil($total_stagiaires / 10);
    $columns = array_chunk($stagiaires, ceil($total_stagiaires / $col_count));
    
    // Générer le tableau
    $output = '<table style="width:100%; border-collapse: collapse; text-align: left;">';
    $output .= '<tr>';
    
    $num = 1;
    foreach ($columns as $column) {
        $output .= '<td style="width: ' . (100 / $col_count) . '%; vertical-align: top; padding: 5px;">';
        $output .= '<ul style="list-style-type: none; margin: 0; padding-left: 10px;">';
        foreach ($column as $stagiaire) {
            $output .= '<li style="margin-bottom: 3px;">' . $num . '. ' . esc_html($stagiaire) . '</li>';
            $num++;
        }
        $output .= '</ul></td>';
    }
    
    $output .= '</tr></table>';
    return $output;
}
add_shortcode('fsbdd_list_stagiaires_bullets', 'fsbdd_list_stagiaires_bullets_shortcode');

/**
 * Shortcode [fsbdd_cpt_list_stagiaires] - Maintenu pour compatibilité, utilise la version unifiée
 */
function fsbdd_cpt_list_stagiaires_shortcode($atts, $content = null) {
    return fsbdd_list_stagiaires_shortcode($atts, $content);
}
add_shortcode('fsbdd_cpt_list_stagiaires', 'fsbdd_cpt_list_stagiaires_shortcode');

/**
 * Shortcode [fsbdd_cpt_list_stagiaires_bullets] - Maintenu pour compatibilité, utilise la version unifiée
 */
function fsbdd_cpt_list_stagiaires_bullets_shortcode($atts, $content = null) {
    return fsbdd_list_stagiaires_bullets_shortcode($atts, $content);
}
add_shortcode('fsbdd_cpt_list_stagiaires_bullets', 'fsbdd_cpt_list_stagiaires_bullets_shortcode');