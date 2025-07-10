<?php
/**
 * Snippet ID: 175
 * Name: Ajouter associer nouvelle commande / affaire depuis session cpt action de formation - modale popup
 * Description: 
 * @active true
 */

/**
 * Fonctionnalité de création/association de commande depuis un CPT action-de-formation
 */

/**
 * 1) Ajout du bouton dans la page d'édition du CPT action-de-formation
 */
add_action('add_meta_boxes', 'add_order_button_to_action_formation');
function add_order_button_to_action_formation() {
    add_meta_box(
        'fsbdd_order_metabox',
        'Créer une commande',
        'display_order_button_meta_box',
        'action-de-formation',
        'side',
        'high'
    );
}

/**
 * 2) Affichage du contenu de la metabox
 */
function display_order_button_meta_box($post) {
    $action_id = $post->ID;
    // URL pour ouvrir le modal avec le CPT action-de-formation préchargé
    $popup_url = admin_url('admin-ajax.php?action=minimal_order_form&action_id=' . $action_id . '&start_step=5');
    ?>
    <p>Créer une nouvelle commande ou associer une commande existante à cette action de formation.</p>
    <button type="button" class="button button-primary" onclick="window.open('<?php echo esc_js($popup_url); ?>', 'Création de commande', 'width=900,height=700,toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1'); return false;">
        Créer/Associer une commande
    </button>
    <?php
}

/**
 * 3) Déclaration des actions AJAX spécifiques
 */
add_action('wp_ajax_search_existing_orders', 'search_existing_orders');
add_action('wp_ajax_get_order_details', 'get_order_details');
add_action('wp_ajax_associate_order_with_action', 'associate_order_with_action');
add_action('wp_ajax_update_existing_order_with_categories', 'update_existing_order_with_categories');

/**
 * 4) Fonctions AJAX pour rechercher des commandes existantes
 */
function search_existing_orders() {
    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
    
    if (empty($search_term)) {
        wp_send_json_error(['message' => 'Terme de recherche manquant.']);
        return;
    }
    
    // Initialisation de l'array des IDs de commandes trouvées
    $order_ids = [];
    
    global $wpdb;
    
    // Une seule requête SQL pour tout trouver
    $sql = $wpdb->prepare(
        "SELECT DISTINCT p.ID 
        FROM {$wpdb->posts} p 
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
        WHERE p.post_type = 'shop_order' 
        AND p.post_status != 'trash' 
        AND (
            p.ID = %d 
            OR pm.meta_key = 'fsbdd_numconv' AND pm.meta_value LIKE %s
            OR pm.meta_key = '_billing_first_name' AND pm.meta_value LIKE %s
            OR pm.meta_key = '_billing_last_name' AND pm.meta_value LIKE %s
            OR pm.meta_key = '_billing_company' AND pm.meta_value LIKE %s
            OR pm.meta_key = '_billing_email' AND pm.meta_value LIKE %s
        )
        LIMIT 50",
        is_numeric($search_term) ? intval($search_term) : 0,
        '%' . $wpdb->esc_like($search_term) . '%',
        '%' . $wpdb->esc_like($search_term) . '%',
        '%' . $wpdb->esc_like($search_term) . '%',
        '%' . $wpdb->esc_like($search_term) . '%',
        '%' . $wpdb->esc_like($search_term) . '%'
    );
    
    $results = $wpdb->get_col($sql);
    
    if (!empty($results)) {
        $order_ids = $results;
    }
    
    // Formatage des résultats pour l'affichage
    $orders = [];
    
    foreach ($order_ids as $order_id) {
        try {
            // Vérification supplémentaire pour le statut
            $post_status = get_post_status($order_id);
            if ($post_status === 'trash') {
                continue;
            }
            
            // Tenter de récupérer l'objet commande
            $order = wc_get_order($order_id);
            
            // Récupérer les infos de base même si l'objet commande est invalide
            $numconv = get_post_meta($order_id, 'fsbdd_numconv', true);
            $numconv_display = $numconv ? ' <span style="color:green; font-weight:bold;">Conv: ' . $numconv . '</span>' : '';
            
            // Informations de base si pas d'objet commande
            $order_info = [
                'id'            => $order_id,
                'number'        => '#' . $order_id,
                'customer_name' => 'Client inconnu' . $numconv_display,
                'date'          => get_the_date('d/m/Y', $order_id),
                'status'        => $post_status,
                'total'         => '-'
            ];
            
            // Remplacer par les informations complètes si l'objet commande existe
            if ($order) {
                $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                if ($order->get_billing_company()) {
                    $customer_name .= ' (' . $order->get_billing_company() . ')';
                }
                
                $order_info = [
                    'id'            => $order_id,
                    'number'        => '#' . $order->get_order_number(),
                    'customer_name' => $customer_name . $numconv_display,
                    'date'          => $order->get_date_created() ? $order->get_date_created()->date_i18n('d/m/Y') : '-',
                    'status'        => wc_get_order_status_name($order->get_status()),
                    'total'         => $order->get_formatted_order_total()
                ];
            }
            
            $orders[] = $order_info;
            
        } catch (Exception $e) {
            error_log('Erreur lors du traitement de la commande ' . $order_id . ': ' . $e->getMessage());
        }
    }
    
    wp_send_json_success([
        'orders' => $orders, 
        'count' => count($orders),
        'search_term' => $search_term
    ]);
}

/**
 * 5) Fonction pour récupérer les détails d'une commande
 */
function get_order_details() {
    // Vérification de sécurité
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error(['message' => 'Permissions insuffisantes.']);
        return;
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    if (!$order_id) {
        wp_send_json_error(['message' => 'ID de commande manquant.']);
        return;
    }
    
    $order = wc_get_order($order_id);
    
    if (!$order) {
        wp_send_json_error(['message' => 'Commande introuvable.']);
        return;
    }
    
    // Récupérer le premier produit de la commande
    $product_name = 'Aucun produit';
    $items = $order->get_items();
    if (!empty($items)) {
        $item = reset($items);
        $product_name = $item->get_name();
    }
    
    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    if ($order->get_billing_company()) {
        $customer_name .= ' (' . $order->get_billing_company() . ')';
    }
    
    // Récupérer le numéro de convention
    $numconv = get_post_meta($order_id, 'fsbdd_numconv', true);
    
    $order_details = [
        'id'             => $order_id,
        'number'         => $order->get_order_number(),
        'customer_name'  => $customer_name,
        'customer_email' => $order->get_billing_email(),
        'date'           => $order->get_date_created()->date_i18n('d/m/Y'),
        'status'         => wc_get_order_status_name($order->get_status()),
        'total'          => $order->get_formatted_order_total(),
        'product_name'   => $product_name,
        'numconv'        => $numconv ? $numconv : 'Non défini'
    ];
    
    wp_send_json_success(['order' => $order_details]);
}

/**
 * 6) Fonction pour associer une commande existante à une action de formation
 */
/**
 * Modification de la fonction associate_order_with_action pour prendre en compte des paramètres supplémentaires
 */
function associate_order_with_action() {
    // Vérification de sécurité
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error(['message' => 'Permissions insuffisantes.']);
        return;
    }

    // Paramètres de base
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $action_id = isset($_POST['action_id']) ? intval($_POST['action_id']) : 0;
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;
    $categories = isset($_POST['categories']) && is_array($_POST['categories']) 
        ? array_map('sanitize_text_field', $_POST['categories']) 
        : [];
    $is_custom_mode = isset($_POST['is_custom_mode']) ? (bool)$_POST['is_custom_mode'] : false;
    $custom_ut_pratique = isset($_POST['custom_ut_pratique']) ? floatval($_POST['custom_ut_pratique']) : 0;
    $custom_ut_theorique = isset($_POST['custom_ut_theorique']) ? floatval($_POST['custom_ut_theorique']) : 1;
    
    // Nouveaux paramètres
    $effectif = isset($_POST['effectif']) ? intval($_POST['effectif']) : 0;
    $stagiaires = isset($_POST['stagiaires']) && is_array($_POST['stagiaires']) ? $_POST['stagiaires'] : [];
    $custom_global_price = isset($_POST['custom_unit_price']) ? floatval($_POST['custom_unit_price']) : 0; // Renamed for clarity, this is global price
    $vat_rate_value = isset($_POST['vat_rate']) ? sanitize_text_field($_POST['vat_rate']) : '20'; // Default 20%
    $frais_montant = isset($_POST['fraisclient_montant']) ? floatval($_POST['fraisclient_montant']) : 0;
    $frais_date = isset($_POST['fraisclient_date']) ? sanitize_text_field($_POST['fraisclient_date']) : '';
    $frais_nom = isset($_POST['fraisclient_nom']) ? sanitize_text_field($_POST['fraisclient_nom']) : '';
    
    if (!$order_id || !$action_id || !$product_id) {
        wp_send_json_error(['message' => 'Paramètres manquants.']);
        return;
    }
    
    try {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(['message' => 'Commande introuvable.']);
            return;
        }
        
        // Récupérer les informations de l'action de formation
        $lieu_complet = get_post_meta($action_id, 'fsbdd_select_lieusession', true) ?: 'Adresse inconnue';
        $startdate = get_post_meta($action_id, 'we_startdate', true);
        $enddate = get_post_meta($action_id, 'we_enddate', true);
        $numero = get_the_title($action_id);
        
        $start_fmt = $startdate ? date_i18n('j F Y', $startdate) : 'Date non définie';
        $end_fmt = $enddate ? date_i18n('j F Y', $enddate) : 'Date non définie';
        
        // 1. Supprimer les produits existants
        foreach ($order->get_items() as $item_id => $item) {
            $order->remove_item($item_id);
        }
        
        // 2. Ajouter le nouveau produit
        $product_to_add = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
        if (!$product_to_add) {
            wp_send_json_error(['message' => 'Produit / Variation introuvable.']);
            return;
        }
        
        // Le prix global est pour tous les stagiaires, la quantité est l'effectif.
        $item_id = $order->add_product($product_to_add, $effectif, [
            'subtotal' => $custom_global_price,
            'total'    => $custom_global_price,
        ]);
        $line_item = $order->get_item($item_id);

        if (!$line_item) {
            wp_send_json_error(['message' => 'Impossible d\'ajouter le produit à la commande lors de l\'association.']);
            return;
        }
        
        // Apply VAT tax class based on $vat_rate_value
        $tax_class_slug = null; // Use null to signify no specific class initially
        switch ($vat_rate_value) {
            case '0':
                $tax_class_slug = 'taux-zero'; // Slug for "Taux zéro"
                break;
            case '5.5':
                // No specific tax class defined by user for 5.5%
                // Let WooCommerce handle it (likely defaults to standard or product's tax status)
                // $tax_class_slug remains null
                break;
            case '10':
                // No specific tax class defined by user for 10%
                // Let WooCommerce handle it
                // $tax_class_slug remains null
                break;
            case '20':
            default:
                $tax_class_slug = ''; // Empty string for standard/default tax rate
                break;
        }

        // Only set tax class if a specific one is determined (0% or 20%/default)
        // For 5.5% and 10%, $tax_class_slug will be null, so set_tax_class won't be called.
        if ($tax_class_slug !== null) {
            $line_item->set_tax_class($tax_class_slug);
        }
        $line_item->add_meta_data('_chosen_vat_rate_percentage', $vat_rate_value, true);
        // Note: $line_item->save() is called later after all meta updates.
        
        // 3. Ajouter les catégories
        if (!empty($categories)) {
            $line_item->update_meta_data('choix_categorie', implode(', ', $categories));
            $line_item->update_meta_data('nombre_categories', count($categories));
            
            // Gestion des UT selon le mode
            if ($is_custom_mode) {
                // Mode personnalisé : utiliser les valeurs fournies
                $line_item->update_meta_data('ut_pratique', $custom_ut_pratique);
                $line_item->update_meta_data('ut_theorique', $custom_ut_theorique);
                
                // Log pour debug
                error_log("UT Debug Modal CPT (Mode personnalisé) - Categories: " . implode(', ', $categories) . ", UT Pratique: $custom_ut_pratique, UT Théorique: $custom_ut_theorique");
            } else {
                // Mode normal : calculer les UT à partir de la grille
                if (function_exists('get_caces_code_from_product_name') && function_exists('get_ut_for_category')) {
                    $product = $line_item->get_product();
                    if ($product) {
                        $product_name = $product->get_name();
                        $formation_key = get_caces_code_from_product_name($product_name);
                        
                        if ($formation_key) {
                            $total_ut_pratique = 0;
                            $total_ut_theorique = 0;
                            
                            foreach ($categories as $category) {
                                $ut_data = get_ut_for_category($formation_key, $category);
                                if ($ut_data && is_array($ut_data)) {
                                    $total_ut_pratique += floatval($ut_data['ut_pratique'] ?? 0);
                                    $total_ut_theorique += floatval($ut_data['ut_theorique'] ?? 0);
                                }
                            }
                            
                            // Toujours sauvegarder les UT, même si elles sont à 0
                            $line_item->update_meta_data('ut_pratique', $total_ut_pratique);
                            $line_item->update_meta_data('ut_theorique', $total_ut_theorique);
                            
                            // Log pour debug
                            error_log("UT Debug Modal CPT (Mode normal) - Formation: $formation_key, Categories: " . implode(', ', $categories) . ", UT Pratique: $total_ut_pratique, UT Théorique: $total_ut_theorique");
                        } else {
                            error_log("UT Debug Modal CPT - Formation key not found for product: $product_name");
                        }
                    }
                }
            }
        }
        
        // 4. Ajouter les informations de l'action de formation
        $line_item->update_meta_data('fsbdd_relsessaction_cpt_produit', $action_id);
        $line_item->update_meta_data('we_startdate', $start_fmt);
        $line_item->update_meta_data('we_enddate', $end_fmt);
        $line_item->update_meta_data('fsbdd_actionum', $numero);
        $line_item->update_meta_data('fsbdd_select_lieuforminter', $lieu_complet);
        
        // Supprimer l'ancienne métadonnée groupée si elle existe
        $line_item->delete_meta_data('session_data');
        $line_item->save();
        
        // 5. Stagiaires
        if (!empty($stagiaires)) {
            $p_array = [];
            foreach ($stagiaires as $i => $stg) {
                $p_array[$i] = [
                    'fsbdd_prenomstagiaire' => sanitize_text_field($stg['prenom'] ?? ''),
                    'fsbdd_nomstagiaire'    => sanitize_text_field($stg['nom'] ?? ''),
                    'fsbdd_stagidatenaiss'  => sanitize_text_field($stg['date_naiss'] ?? ''),
                    'fsbdd_nirstagiaire'    => sanitize_text_field($stg['nir'] ?? ''),
                    'fsbdd_stagiaconvoc'    => ['1', '2'] // Matin & Aprem cochés par défaut
                ];
            }
            update_post_meta($order->get_id(), 'fsbdd_gpeffectif', $p_array);
        }

        // 6. Frais formation
        if ($frais_montant > 0 || $frais_date || $frais_nom) {
            update_post_meta($order->get_id(), 'fsbdd_nomfrais', $frais_nom);
            $arr_frais = [
                'fsbdd_montfraisclient' => $frais_montant,
                'fsbdd_typefraisclient' => $frais_nom,
                'fsbdd_datefraisclient' => $frais_date,
            ];
            update_post_meta($order->get_id(), 'fsbdd_gpfraisclient', $arr_frais);
        }
        
        // 7. Calculer les totaux et sauvegarder
        $order->calculate_totals();
        $order->save();
        
        wp_send_json_success([
            'message' => 'Commande associée avec succès à l\'action de formation.',
            'order_id' => $order_id
        ]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Erreur lors de l\'association de la commande: ' . $e->getMessage()]);
    }
}

/**
 * 7) Fonction pour mettre à jour une commande existante avec les catégories sélectionnées
 */
function update_existing_order_with_categories() {
    // Vérification de sécurité
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error(['message' => 'Permissions insuffisantes.']);
        return;
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $action_id = isset($_POST['action_id']) ? intval($_POST['action_id']) : 0;
    $categories = isset($_POST['categories']) && is_array($_POST['categories']) 
        ? array_map('sanitize_text_field', $_POST['categories']) 
        : [];
    $is_custom_mode = isset($_POST['is_custom_mode']) ? (bool)$_POST['is_custom_mode'] : false;
    $custom_ut_pratique = isset($_POST['custom_ut_pratique']) ? floatval($_POST['custom_ut_pratique']) : 0;
    $custom_ut_theorique = isset($_POST['custom_ut_theorique']) ? floatval($_POST['custom_ut_theorique']) : 1;
    
    if (!$order_id || !$action_id) {
        wp_send_json_error(['message' => 'Paramètres manquants.']);
        return;
    }
    
    try {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(['message' => 'Commande introuvable.']);
            return;
        }
        
        // Récupérer les informations de l'action de formation
        $lieu_complet = get_post_meta($action_id, 'fsbdd_select_lieusession', true) ?: 'Adresse inconnue';
        $startdate = get_post_meta($action_id, 'we_startdate', true);
        $enddate = get_post_meta($action_id, 'we_enddate', true);
        $numero = get_the_title($action_id);
        
        $start_fmt = $startdate ? date_i18n('j F Y', $startdate) : 'Date non définie';
        $end_fmt = $enddate ? date_i18n('j F Y', $enddate) : 'Date non définie';
        
        // Mettre à jour le premier item de la commande
        $items = $order->get_items();
        if (!empty($items)) {
            $item = reset($items);
            
            // Ajouter les catégories
            if (!empty($categories)) {
                $item->update_meta_data('choix_categorie', implode(', ', $categories));
                $item->update_meta_data('nombre_categories', count($categories));
                
                // Gestion des UT selon le mode
                if ($is_custom_mode) {
                    // Mode personnalisé : utiliser les valeurs fournies
                    $item->update_meta_data('ut_pratique', $custom_ut_pratique);
                    $item->update_meta_data('ut_theorique', $custom_ut_theorique);
                    
                    // Log pour debug
                    error_log("UT Debug Modal CPT Update (Mode personnalisé) - Categories: " . implode(', ', $categories) . ", UT Pratique: $custom_ut_pratique, UT Théorique: $custom_ut_theorique");
                } else {
                    // Mode normal : calculer les UT à partir de la grille
                    if (function_exists('get_caces_code_from_product_name') && function_exists('get_ut_for_category')) {
                        $product = $item->get_product();
                        if ($product) {
                            $product_name = $product->get_name();
                            $formation_key = get_caces_code_from_product_name($product_name);
                            
                            if ($formation_key) {
                                $total_ut_pratique = 0;
                                $total_ut_theorique = 0;
                                
                                foreach ($categories as $category) {
                                    $ut_data = get_ut_for_category($formation_key, $category);
                                    if ($ut_data && is_array($ut_data)) {
                                        $total_ut_pratique += floatval($ut_data['ut_pratique'] ?? 0);
                                        $total_ut_theorique += floatval($ut_data['ut_theorique'] ?? 0);
                                    }
                                }
                                
                                // Toujours sauvegarder les UT, même si elles sont à 0
                                $item->update_meta_data('ut_pratique', $total_ut_pratique);
                                $item->update_meta_data('ut_theorique', $total_ut_theorique);
                                
                                // Log pour debug
                                error_log("UT Debug Modal CPT Update (Mode normal) - Formation: $formation_key, Categories: " . implode(', ', $categories) . ", UT Pratique: $total_ut_pratique, UT Théorique: $total_ut_theorique");
                            } else {
                                error_log("UT Debug Modal CPT Update - Formation key not found for product: $product_name");
                            }
                        }
                    }
                }
            }
            
            // Ajouter les informations de l'action de formation individuellement
            $item->update_meta_data('fsbdd_relsessaction_cpt_produit', $action_id);
            $item->update_meta_data('we_startdate', $start_fmt);
            $item->update_meta_data('we_enddate', $end_fmt);
            $item->update_meta_data('fsbdd_actionum', $numero);
            $item->update_meta_data('fsbdd_select_lieuforminter', $lieu_complet);
            
            // Supprimer l'ancienne métadonnée groupée si elle existe
            $item->delete_meta_data('session_data');
            $item->save();
        }
        
        $order->save();
        
        wp_send_json_success([
            'message' => 'Commande mise à jour avec succès.',
            'order_id' => $order_id
        ]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Erreur lors de la mise à jour de la commande: ' . $e->getMessage()]);
    }
}