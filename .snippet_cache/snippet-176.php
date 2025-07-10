<?php
/**
 * Snippet ID: 176
 * Name: Ajouter créer nouvelle commande / affaire depuis bouton en barre d'administration - modale popup
 * Description: 
 * @active true
 */


/**
 * Fonctionnalité de création de commande complète
 * Accessible depuis la barre d'administration
 */

/**
 * 1) Enregistrer le statut personnalisé "Devis Proposition"
 */
add_action('init', 'register_devisproposition_status');
function register_devisproposition_status() {
    register_post_status('wc-devisproposition', array(
        'label'                     => 'Devis Proposition',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop(
            'Devis proposition <span class="count">(%s)</span>',
            'Devis proposition <span class="count">(%s)</span>'
        ),
    ));
}

/**
 * 2) Ajouter le statut "wc-devisproposition" dans la liste des statuts WooCommerce
 */
add_filter('wc_order_statuses', 'add_devisproposition_to_order_statuses');
function add_devisproposition_to_order_statuses($order_statuses) {
    $new_statuses = [];
    foreach ($order_statuses as $key => $status) {
        $new_statuses[$key] = $status;
        if ('wc-on-hold' === $key) {
            $new_statuses['wc-devisproposition'] = 'Devis Proposition';
        }
    }
    return $new_statuses;
}

/**
 * 3) Ajout du lien dans la barre d'administration supérieure
 */
add_action('admin_bar_menu', 'add_custom_order_wizard_to_admin_bar', 999);
function add_custom_order_wizard_to_admin_bar($admin_bar) {
    // Vérification des rôles autorisés
    $current_user = wp_get_current_user();
    $allowed_roles = array('administrator', 'referent', 'compta');

    // Vérifier si l'utilisateur courant a l'un des rôles autorisés
    $can_access = false;
    foreach ($allowed_roles as $role) {
        if (in_array($role, (array) $current_user->roles)) {
            $can_access = true;
            break;
        }
    }

    // Ajouter le lien seulement si l'utilisateur a accès
    if ($can_access) {
        // URL vers notre point d'entrée minimal (sans action_id = commencer à l'étape 1)
        $popup_url = admin_url('admin-ajax.php?action=minimal_order_form');

        $admin_bar->add_node(array(
            'id'    => 'custom-order-wizard',
            'title' => '<span class="ab-icon dashicons dashicons-plus"></span> Convention / Affaire',
            'href'  => '#',
            'meta'  => array(
                'title' => 'Créer une commande',
                'onclick' => 'window.open("' . esc_js($popup_url) . '", "Création de commande", "width=900,height=700,toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1"); return false;'
            )
        ));
    }
}

/**
 * 4) Déclaration des actions AJAX nécessaires
 */
add_action('wp_ajax_minimal_order_form', 'display_minimal_order_form');
add_action('wp_ajax_get_variations_wizard', 'get_variations_wizard');
add_action('wp_ajax_get_default_price_wizard', 'get_default_price_wizard');
add_action('wp_ajax_get_combos_for_wizard', 'get_combos_for_wizard');
add_action('wp_ajax_get_actions_wizard', 'get_actions_wizard');
add_action('wp_ajax_get_price_from_grid', 'get_price_from_grid');
add_action('wp_ajax_get_session_type', 'get_session_type');
add_action('wp_ajax_create_custom_order_wizard', 'create_custom_order_wizard');

/**
 * 5) Fonctions AJAX pour la gestion des données
 */
function get_variations_wizard() {
    // Vérification de sécurité
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => 'Permissions insuffisantes.']);
    }

    $pid = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    if (!$pid) {
        wp_send_json_error(['message' => 'ID du produit manquant.']);
    }

    try {
        $product = wc_get_product($pid);
        if (!$product) {
            wp_send_json_error(['message' => 'Produit introuvable.']);
        }

        if ($product->is_type('variable')) {
            $av = $product->get_available_variations();
            $vars = [];
            foreach ($av as $v) {
                $var_id = $v['variation_id'];
                $var_obj = wc_get_product($var_id);
                if (!$var_obj) continue;

                $attrib_str = wc_get_formatted_variation($var_obj->get_variation_attributes(), true);
                $attrib_str = str_replace(['Catégorie(s):', 'Niveau:'], '', $attrib_str);
                $reg_price = (float) $var_obj->get_regular_price();

                $vars[] = [
                    'id'            => $var_id,
                    'name'          => trim($attrib_str),
                    'regular_price' => $reg_price
                ];
            }
            wp_send_json_success(['variations' => $vars]);
        } else {
            // Produit simple => pas de variations
            wp_send_json_success(['variations' => []]);
        }
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Erreur: ' . $e->getMessage()]);
    }
}

function get_default_price_wizard() {
    $pid = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    if (!$pid) {
        wp_send_json_error(['message' => 'ID du produit manquant.']);
    }

    try {
        $product = wc_get_product($pid);
        if (!$product) {
            wp_send_json_error(['message' => 'Produit introuvable.']);
        }
        $reg_price = (float) $product->get_regular_price();
        wp_send_json_success(['regular_price' => $reg_price]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Erreur: ' . $e->getMessage()]);
    }
}

function get_combos_for_wizard() {
    $pid = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $vid = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;

    if (!$pid && !$vid) {
        wp_send_json_error(['message' => 'ID du produit manquant.']);
    }

    try {
        $product = $vid ? wc_get_product($vid) : wc_get_product($pid);
        if (!$product) {
            wp_send_json_error(['message' => 'Produit / Variation introuvable.']);
        }

        $caces_code = get_caces_code_from_product_name($product->get_name());
        if (!$caces_code) {
            wp_send_json_success(['combos' => []]);
        }

        $price_table = get_full_price_table();

        // Niveau
        $niveau = 'initial';
        if ($vid) {
            $variation = wc_get_product($vid);
            $attributes = $variation->get_attributes();
            if (!empty($attributes['pa_niveau-recyclage-initial'])) {
                $niveau = $attributes['pa_niveau-recyclage-initial'];
            }
        }

        if (!isset($price_table[$caces_code][$niveau])) {
            wp_send_json_error(['message' => 'Niveau invalide ou non défini.']);
        }

        $combos = $price_table[$caces_code][$niveau]['combos'] ?? [];
        wp_send_json_success(['combos' => $combos]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Erreur: ' . $e->getMessage()]);
    }
}

function get_actions_wizard() {
    $pid = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    if (!$pid) {
        wp_send_json_error(['message' => 'Produit manquant']);
    }

    try {
        $args = [
            'post_type'      => 'action-de-formation',
            'post_status'    => 'publish',
            'meta_key'       => 'we_startdate',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
            'meta_query'     => [
                [
                    'key'     => 'fsbdd_relsessproduit',
                    'value'   => $pid,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => -1,
        ];
        $list = get_posts($args);
        $actions = [];
        foreach ($list as $act) {
            $cpt_id = $act->ID;
            $lieu = get_post_meta($cpt_id, 'fsbdd_select_lieusession', true);
            $start = get_post_meta($cpt_id, 'we_startdate', true);
            $numero = get_the_title($cpt_id);

            $lieu_resume = $lieu ? explode(',', $lieu)[0] : 'Lieu inconnu';
            $lieu_resume = ucfirst(strtolower(trim($lieu_resume)));
            $start_fmt = $start ? date_i18n('j F Y', $start) : 'Date non définie';

            $actions[] = [
                'id'   => $cpt_id,
                'text' => "{$lieu_resume}, {$start_fmt}, N°{$numero}"
            ];
        }
        wp_send_json_success(['actions' => $actions]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Erreur: ' . $e->getMessage()]);
    }
}

function get_price_from_grid() {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;
    $categories = isset($_POST['categories']) && is_array($_POST['categories'])
        ? array_map('sanitize_text_field', $_POST['categories'])
        : [];

    // Pas de catégories => on renvoie price null
    if (empty($categories)) {
        wp_send_json_success(['price' => null]);
        return;
    }

    try {
        // Normalisation
        $normalized_categories = array_map('normalize_category_label', $categories);

        // Récupération de la grille
        $table = get_full_price_table();

        // Produit ou Variation
        $product = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
        if (!$product) {
            wp_send_json_success(['price' => null]);
            return;
        }

        // Code caces/hab
        $caces_code = get_caces_code_from_product_name($product->get_name());
        if (!$caces_code || !isset($table[$caces_code])) {
            wp_send_json_success(['price' => null]);
            return;
        }

        // Niveau initial/recyclage
        $niveau = 'initial';
        if ($variation_id) {
            $variation = wc_get_product($variation_id);
            $attributes = $variation->get_attributes();
            if (!empty($attributes['pa_niveau-recyclage-initial'])) {
                $niveau = $attributes['pa_niveau-recyclage-initial'];
            }
        }
        if (!isset($table[$caces_code][$niveau])) {
            wp_send_json_success(['price' => null]);
            return;
        }

        // Cherche la combo
        $combos = $table[$caces_code][$niveau]['combos'] ?? [];
        foreach ($combos as $combo) {
            $combo_categories_sorted = $combo['categories'];
            sort($combo_categories_sorted);
            $input_categories_sorted = $normalized_categories;
            sort($input_categories_sorted);

            if ($combo_categories_sorted === $input_categories_sorted) {
                wp_send_json_success(['price' => floatval($combo['price'])]);
                return;
            }
        }
        wp_send_json_success(['price' => null]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Erreur: ' . $e->getMessage()]);
    }
}

function get_session_type() {
    if (!isset($_POST['action_id']) || empty($_POST['action_id'])) {
        wp_send_json_error(['message' => 'ID de session manquant.']);
        return;
    }
    
    $action_id = intval($_POST['action_id']);
    $session_type = get_post_meta($action_id, 'fsbdd_typesession', true);
    
    // 3 = INTRA, 1 & 2 = INTER
    $is_intra = ($session_type == '3');
    
    // Récupérer le lieu complet pour l'afficher
    $lieu_complet = get_post_meta($action_id, 'fsbdd_select_lieusession', true) ?: 'Adresse non définie';
    
    wp_send_json_success([
        'is_intra' => $is_intra,
        'session_type' => $session_type,
        'lieu_complet' => $lieu_complet
    ]);
}

function create_custom_order_wizard() {
    // Vérification de sécurité
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error(['message' => 'Permissions insuffisantes.']);
        return;
    }

    // Récupération des données avec vérifications
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;
    $categories_selected = isset($_POST['categories_selected']) && is_array($_POST['categories_selected'])
        ? array_map('sanitize_text_field', $_POST['categories_selected'])
        : [];
    $action_id = isset($_POST['action_formation_id']) ? intval($_POST['action_formation_id']) : 0;
    $effectif = isset($_POST['effectif']) ? intval($_POST['effectif']) : 0;
    $stagiaires = isset($_POST['stagiaires']) && is_array($_POST['stagiaires']) ? $_POST['stagiaires'] : [];

    $frais_montant = isset($_POST['fraisclient_montant']) ? floatval($_POST['fraisclient_montant']) : 0;
    $frais_date = isset($_POST['fraisclient_date']) ? sanitize_text_field($_POST['fraisclient_date']) : '';
    $frais_nom = isset($_POST['fraisclient_nom']) ? sanitize_text_field($_POST['fraisclient_nom']) : '';

    $custom_global_price = isset($_POST['custom_unit_price']) ? floatval($_POST['custom_unit_price']) : 0; // This is now global price, from 'custom_unit_price' POST field
    $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    $order_status = isset($_POST['order_status']) ? sanitize_text_field($_POST['order_status']) : 'wc-on-hold';
    $vat_rate_value = isset($_POST['vat_rate']) ? sanitize_text_field($_POST['vat_rate']) : '20'; // Default 20%
    
    // Variables pour les adresses
    $use_shipping_address = isset($_POST['use_shipping_address']) ? filter_var($_POST['use_shipping_address'], FILTER_VALIDATE_BOOLEAN) : false;
    $shipping_option = isset($_POST['shipping_option']) ? sanitize_text_field($_POST['shipping_option']) : 'billing';
    $shipping_address = isset($_POST['shipping_address']) && is_array($_POST['shipping_address']) ? $_POST['shipping_address'] : [];
    
    // Variables pour le mode personnalisé
    $is_custom_mode = isset($_POST['is_custom_mode']) ? filter_var($_POST['is_custom_mode'], FILTER_VALIDATE_BOOLEAN) : false;
    $custom_ut_pratique = isset($_POST['custom_ut_pratique']) ? floatval($_POST['custom_ut_pratique']) : 0;
    $custom_ut_theorique = isset($_POST['custom_ut_theorique']) ? floatval($_POST['custom_ut_theorique']) : 1;

    try {
        if (!$product_id) {
            wp_send_json_error(['message' => 'Aucun produit sélectionné.']);
            return;
        }

        // 1) Créer la commande
        $order = wc_create_order(['status' => $order_status]);
        if (is_wp_error($order)) {
            wp_send_json_error(['message' => 'Impossible de créer la commande: ' . $order->get_error_message()]);
            return;
        }

        // Associer un client (si fourni) et copier les informations de facturation
        if ($customer_id > 0) {
            $order->set_customer_id($customer_id);
            
            // Récupérer les informations client
            $customer = new WC_Customer($customer_id);
            
            // Copier les informations de facturation
            $order->set_billing_first_name($customer->get_billing_first_name());
            $order->set_billing_last_name($customer->get_billing_last_name());
            $order->set_billing_company($customer->get_billing_company());
            $order->set_billing_address_1($customer->get_billing_address_1());
            $order->set_billing_address_2($customer->get_billing_address_2());
            $order->set_billing_city($customer->get_billing_city());
            $order->set_billing_state($customer->get_billing_state());
            $order->set_billing_postcode($customer->get_billing_postcode());
            $order->set_billing_country($customer->get_billing_country());
            $order->set_billing_email($customer->get_billing_email());
            $order->set_billing_phone($customer->get_billing_phone());
        }
        
        // Gérer l'adresse de livraison si nécessaire
        if ($use_shipping_address) {
            if ($shipping_option === 'billing' && $customer_id > 0) {
                // Utiliser l'adresse de facturation comme adresse de livraison
                $customer = new WC_Customer($customer_id);
                $order->set_shipping_first_name($customer->get_billing_first_name());
                $order->set_shipping_last_name($customer->get_billing_last_name());
                $order->set_shipping_company($customer->get_billing_company());
                $order->set_shipping_address_1($customer->get_billing_address_1());
                $order->set_shipping_address_2($customer->get_billing_address_2());
                $order->set_shipping_city($customer->get_billing_city());
                $order->set_shipping_state($customer->get_billing_state());
                $order->set_shipping_postcode($customer->get_billing_postcode());
                $order->set_shipping_country($customer->get_billing_country());
            } elseif ($shipping_option === 'formation' && $action_id > 0) {
                // Utiliser le lieu de la formation comme adresse de livraison
                $lieu_complet = get_post_meta($action_id, 'fsbdd_select_lieusession', true) ?: '';
                
                // Si le lieu est défini, essayer de le parser
                if (!empty($lieu_complet)) {
                    // Exemple simple de parsing - à adapter selon le format réel
                    $parts = explode(',', $lieu_complet);
                    
                    $shipping_address_1 = trim($parts[0] ?? '');
                    $shipping_city = trim($parts[1] ?? '');
                    $shipping_postcode = '';
                    
                    // Essayer d'extraire le code postal s'il est présent
                    if (isset($parts[1])) {
                        $city_parts = explode(' ', trim($parts[1]));
                        if (count($city_parts) > 1 && is_numeric($city_parts[0])) {
                            $shipping_postcode = $city_parts[0];
                            array_shift($city_parts);
                            $shipping_city = implode(' ', $city_parts);
                        }
                    }
                    
                    $order->set_shipping_address_1($shipping_address_1);
                    $order->set_shipping_city($shipping_city);
                    $order->set_shipping_postcode($shipping_postcode);
                    $order->set_shipping_country('FR'); // Par défaut France
                }
            } elseif ($shipping_option === 'custom') {
                // Utiliser l'adresse personnalisée fournie
                $order->set_shipping_first_name($shipping_address['first_name'] ?? '');
                $order->set_shipping_last_name($shipping_address['last_name'] ?? '');
                $order->set_shipping_company($shipping_address['company'] ?? '');
                $order->set_shipping_address_1($shipping_address['address_1'] ?? '');
                $order->set_shipping_address_2($shipping_address['address_2'] ?? '');
                $order->set_shipping_city($shipping_address['city'] ?? '');
                $order->set_shipping_state($shipping_address['state'] ?? '');
                $order->set_shipping_postcode($shipping_address['postcode'] ?? '');
                $order->set_shipping_country($shipping_address['country'] ?? 'FR');
            }
        }

        // 2) Ajouter le produit
        $product_to_add = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
        if (!$product_to_add) {
            wp_send_json_error(['message' => 'Produit / Variation introuvable.']);
            return;
        }
        // $custom_global_price is already defined. $effectif is the number of trainees.
        // The line item quantity is 1, and its total is the global price.

        $item_id = $order->add_product($product_to_add, $effectif, [
            'subtotal' => $custom_global_price,
            'total'    => $custom_global_price,
        ]);
        $line_item = $order->get_item($item_id);

        if (!$line_item) {
            wp_send_json_error(['message' => 'Impossible d\'ajouter le produit à la commande.']);
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

        // 3) Catégories cochées
        if (!empty($categories_selected)) {
            $line_item->update_meta_data('choix_categorie', implode(', ', $categories_selected));
            $line_item->update_meta_data('nombre_categories', count($categories_selected));
            
            // Gestion des UT selon le mode
            if ($is_custom_mode) {
                // Mode personnalisé : utiliser les valeurs fournies
                $line_item->update_meta_data('ut_pratique', $custom_ut_pratique);
                $line_item->update_meta_data('ut_theorique', $custom_ut_theorique);
                
                // Log pour debug
                error_log("UT Debug Modal Admin (Mode personnalisé) - Categories: " . implode(', ', $categories_selected) . ", UT Pratique: $custom_ut_pratique, UT Théorique: $custom_ut_theorique");
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
                            
                            foreach ($categories_selected as $category) {
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
                            error_log("UT Debug Modal Admin (Mode normal) - Formation: $formation_key, Categories: " . implode(', ', $categories_selected) . ", UT Pratique: $total_ut_pratique, UT Théorique: $total_ut_theorique");
                        } else {
                            error_log("UT Debug Modal Admin - Formation key not found for product: $product_name");
                        }
                    }
                }
            }
        }

        // 4) Action de formation
        if ($action_formation_id > 0) {
            $lieu_complet = get_post_meta($action_formation_id, 'fsbdd_select_lieusession', true) ?: 'Adresse inconnue';
            $startdate = get_post_meta($action_formation_id, 'we_startdate', true);
            $enddate = get_post_meta($action_formation_id, 'we_enddate', true);
            $numero = get_the_title($action_formation_id);

            $start_fmt = $startdate ? date_i18n('j F Y', $startdate) : 'Date non définie';
            $end_fmt = $enddate ? date_i18n('j F Y', $enddate) : 'Date non définie';
            
            // Ajouter les informations de l'action de formation individuellement
            $line_item->update_meta_data('fsbdd_relsessaction_cpt_produit', $action_formation_id);
            $line_item->update_meta_data('we_startdate', $start_fmt);
            $line_item->update_meta_data('we_enddate', $end_fmt);
            $line_item->update_meta_data('fsbdd_actionum', $numero);
            $line_item->update_meta_data('fsbdd_select_lieuforminter', $lieu_complet);
            
            // Supprimer l'ancienne métadonnée groupée si elle existe
            $line_item->delete_meta_data('session_data');
        }
        
        $line_item->save();

        // 5) Stagiaires
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

        // 6) Frais formation => comme l'ancien code, sans multiplier par 1.20
        if ($frais_montant > 0 || $frais_date || $frais_nom) {
            update_post_meta($order->get_id(), 'fsbdd_nomfrais', $frais_nom); // Nouvelle ligne
            $arr_frais = [
                'fsbdd_montfraisclient' => $frais_montant,
                'fsbdd_typefraisclient' => $frais_nom,  // <-- (important : c'est le nouveau nom)
                'fsbdd_datefraisclient' => $frais_date,
            ];
            // Stocker dans la meta comme avant
            update_post_meta($order->get_id(), 'fsbdd_gpfraisclient', $arr_frais);
        }

        // 7) Calcul des totaux
        $order->calculate_totals();
        $order->save();

        $edit_link = admin_url('post.php?post=' . $order->get_id() . '&action=edit');
        wp_send_json_success([
            'message' => 'Commande créée avec succès (Quantité = ' . $qty . ' pour ' . $effectif . ' stagiaires).',
            'order_id' => $order->get_id(),
            'edit_link' => $edit_link
        ]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Erreur lors de la création de la commande: ' . $e->getMessage()]);
    }
}

/**
 * 6) Fonction principale pour afficher le formulaire
 */
function display_minimal_order_form() {
    // Vérifier les permissions
    $current_user = wp_get_current_user();
    $allowed_roles = array('administrator', 'referent', 'compta');
    $can_access = false;
    
    foreach ($allowed_roles as $role) {
        if (in_array($role, (array) $current_user->roles)) {
            $can_access = true;
            break;
        }
    }
    
    if (!$can_access) {
        wp_die('Vous n\'avez pas les permissions nécessaires.');
    }
    
    // Récupérer les paramètres
    $action_id = isset($_GET['action_id']) ? intval($_GET['action_id']) : 0;
    $start_step = isset($_GET['start_step']) ? intval($_GET['start_step']) : 1;
    
    // Variables pour le formulaire
    $product_id = 0;
    $variation_id = null;
    $categories = array();
    $product_name = '';
    $action_title = '';
    $lieu = '';
    $start_date_formatted = '';
    
    // Si action_id est fourni, récupérer les informations de la session
    if ($action_id > 0) {
        $action_post = get_post($action_id);
        if (!$action_post || $action_post->post_type !== 'action-de-formation') {
            wp_die('Action de formation invalide.');
        }
        
        $product_id = get_post_meta($action_id, 'fsbdd_relsessproduit', true);
        if (!$product_id) {
            wp_die('Aucun produit associé à cette session.');
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_die('Produit invalide.');
        }
        
        $product_name = $product->get_name();
        $action_title = get_the_title($action_id);
        $lieu = get_post_meta($action_id, 'fsbdd_select_lieusession', true) ?: 'Non défini';
        $start_date = get_post_meta($action_id, 'we_startdate', true);
        $start_date_formatted = $start_date ? date_i18n('j F Y', $start_date) : 'Non définie';
    }
    
    // Récupérer la liste de clients WP pour l'étape 7
    $customers = get_users([
        'role__in' => ['customer', 'subscriber']
    ]);
    
    // Récupérer la liste des produits pour l'étape 1
    $products = get_posts([
        'post_type'   => 'product',
        'numberposts' => -1,
        'tax_query'   => [
            [
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => ['exclude-from-catalog'],
                'operator' => 'NOT IN',
            ]
        ],
        'orderby' => 'title',
        'order'   => 'ASC',
    ]);
    
    // Créer un nonce pour sécuriser les requêtes AJAX
    $ajax_nonce = wp_create_nonce('order_form_nonce');
    
    // Charger les scripts nécessaires
    wp_enqueue_script('jquery');
    wp_print_scripts('jquery');
    
    // Afficher le formulaire HTML directement
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Création de commande<?php echo $action_id ? ' - ' . esc_html($action_title) : ''; ?></title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                margin: 0;
                padding: 20px;
                background-color: #f0f0f1;
                color: #3c434a;
            }
            .wrap {
                max-width: 800px;
                margin: 0 auto;
                background: #fff;
                padding: 20px;
                border-radius: 5px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            h1 {
                font-size: 24px;
                margin-top: 0;
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }
            h2 {
                font-size: 18px;
                margin-top: 30px;
                background: #f9f9f9;
                padding: 10px;
                border-radius: 3px;
            }
            h3 {
                font-size: 16px;
                margin-top: 20px;
            }
            .info-box {
                background: #f8f8f8;
                padding: 15px;
                margin-bottom: 20px;
                border-left: 4px solid #0073aa;
                border-radius: 3px;
            }
            .info-line {
                margin-bottom: 5px;
            }
            select, input[type="text"], input[type="number"] {
                width: calc(100% - 40px);
                padding: 10px;
                margin: 10px 20px 10px 0;
                border: 1px solid #ccc;
                border-radius: 5px;
            }
            input[type="number"] {
                width: 80px;
            }
            button {
                background-color: #0073aa;
                color: #fff;
                border: none;
                padding: 10px 20px;
                border-radius: 5px;
                cursor: pointer;
                margin-top: 10px;
            }
            button:hover {
                background-color: #005177;
            }
            .buttons-container {
                display: flex;
                gap: 10px;
                margin-top: 20px;
            }
            button.secondary {
                background-color: #f0f0f1;
                color: #3c434a;
                border: 1px solid #ccc;
            }
            button.secondary:hover {
                background-color: #e0e0e0;
            }
            .stagiaire-line {
                margin-bottom: 15px;
                padding: 10px;
                border: 1px solid #ccc;
                border-radius: 5px;
            }
            .stagiaire-fields {
                display: flex;
                gap: 10px;
                margin-top: 10px;
            }
            .stagiaire-fields input {
                flex: 1;
                width: auto !important;
                padding: 8px;
                margin: 0 !important;
                border: 1px solid #ccc;
                border-radius: 5px;
            }
            .frais-fields {
                display: flex;
                gap: 10px;
                margin: 15px 0;
            }
            .frais-fields input {
                flex: 1;
                width: auto !important;
                padding: 8px;
                margin: 0 !important;
                border: 1px solid #ccc;
                border-radius: 5px;
            }
            .step-number {
                display: inline-block;
                width: 24px;
                height: 24px;
                background-color: #0073aa;
                color: white;
                border-radius: 50%;
                text-align: center;
                line-height: 24px;
                margin-right: 10px;
            }
            .champ-dropdown-categorie {
                display: flex;
                align-items: center;
                margin-top: 20px;
            }
            .champ-dropdown-categorie select {
                flex: 1;
            }
            /* Pour le débogage */
            .debug-info {
                margin: 20px 0;
                padding: 10px;
                background: #f5f5f5;
                border: 1px solid #ddd;
                border-radius: 3px;
                font-family: monospace;
                white-space: pre-wrap;
                display: none;
            }
            /* Styles pour les notifications */
            .notification {
                padding: 10px 15px;
                margin: 15px 0;
                border-radius: 4px;
                display: none;
            }
            .notification.success {
                background-color: #dff0d8;
                border: 1px solid #d6e9c6;
                color: #3c763d;
            }
            .notification.error {
                background-color: #f2dede;
                border: 1px solid #ebccd1;
                color: #a94442;
            }
            .notification.info {
                background-color: #d9edf7;
                border: 1px solid #bce8f1;
                color: #31708f;
            }
            /* Styles pour l'adresse de livraison */
            #shipping-address-section {
                margin-top: 20px;
                border: 1px solid #ddd;
                padding: 15px;
                border-radius: 4px;
                background-color: #f9f9f9;
            }
            .shipping-option {
                margin: 10px 0;
            }
            #custom-shipping-form {
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px solid #eee;
            }
            .shipping-field {
                margin-bottom: 10px;
            }
            .shipping-field label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
            }
            
            /* Nouveaux styles pour la sélection d'une commande existante */
            .command-type-options {
                display: flex;
                gap: 20px;
                margin: 30px 0;
            }
            
            .option-card {
                flex: 1;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 20px;
                text-align: center;
                transition: all 0.3s ease;
                cursor: pointer;
            }
            
            .option-card:hover {
                border-color: #0073aa;
                box-shadow: 0 0 10px rgba(0, 115, 170, 0.2);
            }
            
            .option-card.selected {
                border-color: #0073aa;
                background-color: #f0f7fb;
            }
            
            .option-icon {
                font-size: 30px;
                margin-bottom: 15px;
                color: #0073aa;
            }
            
            .option-card h3 {
                margin: 10px 0;
                font-size: 16px;
            }
            
            .option-card p {
                color: #777;
                margin: 0;
                font-size: 14px;
            }
            
            .search-container {
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .search-container input {
                flex-grow: 1;
            }
            
            .orders-list {
                max-height: 300px;
                overflow-y: auto;
                border: 1px solid #ddd;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            
            .order-item {
                padding: 10px 15px;
                border-bottom: 1px solid #eee;
                cursor: pointer;
                transition: background-color 0.2s;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .order-item:hover {
                background-color: #f5f5f5;
            }
            
            .order-item.selected {
                background-color: #f0f7fb;
                border-left: 3px solid #0073aa;
            }
            
            .order-details {
                background-color: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 15px;
                margin-bottom: 20px;
            }
            
            .order-info-line {
                display: flex;
                justify-content: space-between;
                padding: 5px 0;
                border-bottom: 1px solid #eee;
            }
            
            .loading {
                text-align: center;
                padding: 20px;
                color: #777;
            }
            
            .error-message {
                color: #a94442;
                background-color: #f2dede;
                border: 1px solid #ebccd1;
                padding: 10px;
                margin: 10px 0;
                border-radius: 4px;
            }
            
            .no-results {
                text-align: center;
                padding: 20px;
                color: #777;
                font-style: italic;
            }
			
			#step-configure-association {
    padding: 15px;
    background-color: #f9f9f9;
    border-radius: 5px;
    margin-bottom: 20px;
}

#step-configure-association h3 {
    margin-top: 20px;
    color: #2271b1;
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}
			
        </style>
    </head>
    <body>
        <div class="wrap">
            <h1>Nouvelle convention</h1>
            
            <!-- Zone de notification -->
            <div id="notification" class="notification"></div>
            
            <?php if ($action_id): ?>
            <div class="info-box">
                <div class="info-line"><strong>Session :</strong> <?php echo esc_html($action_title); ?></div>
                <div class="info-line"><strong>Lieu :</strong> <?php echo esc_html($lieu); ?></div>
                <div class="info-line"><strong>Date de début :</strong> <?php echo esc_html($start_date_formatted); ?></div>
                <div class="info-line"><strong>Produit associé :</strong> <?php echo esc_html($product_name); ?></div>
            </div>
            <?php endif; ?>
            
            <!-- Pour le débogage -->
            <div id="debug-info" class="debug-info"></div>
            
            <!-- Étape 0 : Choix du type d'opération (nouvelle commande ou existante) -->
            <div id="step-0" style="<?php echo $action_id > 0 ? 'display:block;' : 'display:none;'; ?>">
                <h2><span class="step-number">0</span> Type d'opération</h2>
                
                <div class="command-type-options">
                    <div class="option-card" id="option-new-order">
                        <div class="option-icon"><i class="dashicons dashicons-plus-alt"></i></div>
                        <h3>Créer une nouvelle commande</h3>
                        <p>Créer une nouvelle commande associée à cette action de formation</p>
                    </div>
                    
                    <div class="option-card" id="option-existing-order">
                        <div class="option-icon"><i class="dashicons dashicons-admin-links"></i></div>
                        <h3>Associer une commande existante</h3>
                        <p>Sélectionner une commande déjà créée et l'associer à cette action</p>
                    </div>
                </div>
            </div>
            
            <!-- Écran de sélection d'une commande existante -->
            <div id="step-existing-order" style="display:none;">
                <h2><span class="step-number">E</span> Sélectionner une commande existante</h2>
                
                <div class="search-container">
                    <input type="text" id="search-order" placeholder="Rechercher par n°, client, email..." />
                    <button id="search-btn">Rechercher</button>
                </div>
                
                <div id="orders-results" class="orders-list">
                    <!-- Les résultats seront chargés ici par AJAX -->
                    <div class="no-results">Utilisez la recherche pour trouver une commande</div>
                </div>
                
                <div id="selected-order-details" class="order-details" style="display:none;">
                    <h3>Détails de la commande sélectionnée</h3>
                    <div id="order-info"></div>
                </div>
                
                <div class="buttons-container">
                    <button class="secondary" id="back-to-options">Retour</button>
                    <button id="associate-order" disabled>Associer la commande</button>
                </div>
            </div>
            
            <!-- Étape 1 : Sélection produit -->
            <div id="step-1" style="<?php echo $start_step > 1 ? 'display:none;' : ''; ?>">
                <h2><span class="step-number">1</span> Choisir un produit</h2>
                <select id="select-product">
                    <option value="">-- Choisir --</option>
                    <?php
                    foreach ($products as $p) {
                        $pobj = wc_get_product($p->ID);
                        if (!$pobj) continue;
                        $selected = ($p->ID == $product_id) ? 'selected' : '';
                        echo '<option value="' . $p->ID . '" ' . $selected . '>' . esc_html($pobj->get_name()) . '</option>';
                    }
                    ?>
                </select>
                <div class="buttons-container">
                    <button id="go-step-2">Suivant</button>
                </div>
            </div>

            <!-- Étape 2 : Variation -->
            <div id="step-2" style="display:none;">
                <h2><span class="step-number">2</span> Niveau</h2>
                <select id="select-variation">
                    <!-- Rempli en AJAX -->
                </select>
                <div class="buttons-container">
                    <button class="secondary prev-step" data-prev="1">Retour</button>
                    <button id="go-step-3">Suivant</button>
                </div>
            </div>

            <!-- Étape 3 : Catégorie(s) -->
            <div id="step-3" style="display:none;">
                <h2><span class="step-number">3</span> Catégorie(s)</h2>
                <div class="champ-dropdown-categorie">
                    <select id="choix_categorie" name="choix_categorie">
                        <option value="">-- Sélectionnez une option --</option>
                        <option value="personnalise">Personnalisé</option>
                    </select>
                </div>
                
                <!-- Champs personnalisés (masqués par défaut) -->
                <div id="custom-fields" style="display: none; margin-top: 15px;">
                    <div style="margin-bottom: 10px;">
                        <label for="custom-categories" style="display: block; margin-bottom: 5px;">Catégories personnalisées :</label>
                        <input type="text" id="custom-categories" name="custom_categories" placeholder="Ex: A, B, C" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />
                    </div>
                    <div style="margin-bottom: 10px;">
                        <label for="custom-ut-pratique" style="display: block; margin-bottom: 5px;">UT Pratiques :</label>
                        <input type="number" id="custom-ut-pratique" name="custom_ut_pratique" min="0" step="0.5" placeholder="Ex: 7" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />
                        <small style="color: #666; font-size: 12px;">Les UT théoriques restent à 1</small>
                    </div>
                </div>
                <div class="buttons-container">
                    <button class="secondary prev-step" data-prev="2">Retour</button>
                    <button id="go-step-4">Suivant</button>
                </div>
            </div>

            <!-- Étape 4 : Session (Action de formation) -->
            <div id="step-4" style="display:none;">
                <h2><span class="step-number">4</span> Sélectionner la session</h2>
                <select id="select-action-formation">
                    <?php if ($action_id): ?>
                    <option value="<?php echo $action_id; ?>" selected><?php echo esc_html("{$lieu}, {$start_date_formatted}, N°{$action_title}"); ?></option>
                    <?php endif; ?>
                </select>
                <div class="buttons-container">
                    <button class="secondary prev-step" data-prev="3">Retour</button>
                    <button id="go-step-5">Suivant</button>
                </div>
            </div>

            <!-- Étape 5 : Effectif + Stagiaires -->
            <div id="step-5" style="display:none;">
    <h2><span class="step-number">5</span> Effectif + Stagiaires</h2>
                <label>Effectif :</label>
                <input type="number" id="input-effectif" value="0" min="0" max="50" />
                <div id="stagiaires-container"></div>
                <div class="buttons-container">
                    <?php if ($start_step < 5): ?>
                    <button class="secondary prev-step" data-prev="4">Retour</button>
                    <?php endif; ?>
                    <button id="go-step-6">Suivant</button>
                </div>
            </div>

            <!-- Étape 6 : Coût unitaire HT + Frais -->
            <div id="step-6" style="display:none;">
                <h2><span class="step-number"><?php echo $start_step == 5 ? '2' : '6'; ?></span> Détails financiers</h2>
                <label>Coût global HT :</label>
                <input type="text" id="input-unit-price" value="" />

                <label for="select-vat-rate" style="display: block; margin-top: 10px;">Taux de TVA :</label>
                <select id="select-vat-rate" name="select_vat_rate" style="width: calc(100% - 40px); padding: 10px; margin: 10px 20px 10px 0; border: 1px solid #ccc; border-radius: 5px;">
                    <option value="20">20% (Normal)</option>
                    <option value="10">10% (Intermédiaire)</option>
                    <option value="5.5">5.5% (Réduit)</option>
                    <option value="0">0% (Exonéré)</option>
                </select>
                
                <h3>Frais de formation</h3>
                <div class="frais-fields">
                    <input type="text" id="input-fraisclient" placeholder="Montant frais client (HT)" />
                    <input type="text" id="input-datefrais" placeholder="Date frais client (jj/mm/aaaa)" />
                    <input type="text" id="input-nomfrais" placeholder="Dénomination des frais sur facture" />
                </div>
                <div class="buttons-container">
                    <button class="secondary prev-step" data-prev="5">Retour</button>
                    <button id="go-step-7">Suivant</button>
                </div>
            </div>

            <!-- Étape 7 : Sélection du client -->
            <div id="step-7" style="display:none;">
                <h2><span class="step-number"><?php echo $start_step == 5 ? '3' : '7'; ?></span> Sélection du client</h2>
                <select id="select-customer">
                    <option value="">-- Sélectionnez un client --</option>
                    <?php foreach ($customers as $cust) : 
                        $company = get_user_meta($cust->ID, 'billing_company', true);
                        $display = $company 
                            ? sprintf('%s - %s (%s)', $company, $cust->display_name, $cust->user_email)
                            : sprintf('%s (%s)', $cust->display_name, $cust->user_email);
                    ?>
                        <option value="<?php echo $cust->ID; ?>">
                            <?php echo esc_html($display); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Section pour l'adresse de livraison (conditionnelle pour INTRA) -->
                <div id="shipping-address-section" style="display:none;">
                    <h3>Lieu de la formation si différent</h3>
                    <p id="formation-lieu-display" class="info-line"></p>
                    
                    <div class="shipping-option">
                        <label>
                            <input type="radio" name="shipping_option" value="billing" checked> 
                            Utiliser l'adresse de facturation du client
                        </label>
                    </div>
                    
                    <div class="shipping-option">
                        <label>
                            <input type="radio" name="shipping_option" value="formation"> 
                            Utiliser le lieu de la formation indiqué ci-dessus
                        </label>
                    </div>
                    
                    <div class="shipping-option">
                        <label>
                            <input type="radio" name="shipping_option" value="custom"> 
                            Utiliser une adresse personnalisée
                        </label>
                    </div>
                    
                    <!-- Formulaire d'adresse personnalisée -->
                    <div id="custom-shipping-form" style="display:none;">
                        <div class="shipping-field">
                            <label>Prénom</label>
                            <input type="text" id="shipping_first_name" style="width: 100%;" placeholder="Prénom">
                        </div>
                        <div class="shipping-field">
                            <label>Nom</label>
                            <input type="text" id="shipping_last_name" style="width: 100%;" placeholder="Nom">
                        </div>
                        <div class="shipping-field">
                            <label>Entreprise</label>
                            <input type="text" id="shipping_company" style="width: 100%;" placeholder="Entreprise">
                        </div>
                        <div class="shipping-field">
                            <label>Adresse</label>
                            <input type="text" id="shipping_address_1" style="width: 100%;" placeholder="Adresse">
                        </div>
                        <div class="shipping-field">
                            <label>Complément d'adresse</label>
                            <input type="text" id="shipping_address_2" style="width: 100%;" placeholder="Appartement, suite, etc.">
                        </div>
                        <div class="shipping-field">
                            <label>Ville</label>
                            <input type="text" id="shipping_city" style="width: 100%;" placeholder="Ville">
                        </div>
                        <div class="shipping-field">
                            <label>Code postal</label>
                            <input type="text" id="shipping_postcode" style="width: 100%;" placeholder="Code postal">
                        </div>
                        <div class="shipping-field">
                            <label>Pays</label>
                            <input type="text" id="shipping_country" style="width: 100%;" value="FR" placeholder="Pays (code ISO)">
                        </div>
                    </div>
                </div>
                
                <div class="buttons-container">
                    <button class="secondary prev-step" data-prev="6">Retour</button>
                    <button id="go-step-8">Suivant</button>
                </div>
            </div>

            <!-- Étape 8 : Validation finale -->
            <div id="step-8" style="display:none;">
                <h2><span class="step-number">8</span> Validation</h2>

                <label for="select-order-status">Statut de la commande :</label>
                <select id="select-order-status">
                    <option value="wc-on-hold" selected>En attente (On hold)</option>
                    <option value="wc-pending">En attente de paiement (Pending)</option>
                    <option value="wc-processing">En cours (Processing)</option>
                    <option value="wc-completed">Terminée (Completed)</option>
                    <option value="wc-devisproposition">Devis Proposition</option>
                </select>
                <br><br>
                <div class="buttons-container">
                    <button class="secondary prev-step" data-prev="7">Retour</button>
                    <button id="finalize-order">Créer la commande</button>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        (function($){
            // Variables globales
            var selectedProductId = <?php echo $product_id > 0 ? json_encode($product_id) : 'null'; ?>;
            var selectedVariationId = <?php echo $variation_id ? json_encode($variation_id) : 'null'; ?>;
            var selectedCategories = <?php echo !empty($categories) ? json_encode($categories) : '[]'; ?>;
            var selectedActionFormation = <?php echo $action_id > 0 ? json_encode($action_id) : 'null'; ?>;
            var defaultUnitPrice = 0;
            var finalUnitPrice = 0;
            var selectedCustomerId = null;
            var selectedOrderStatus = 'wc-on-hold';
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var ajax_nonce = '<?php echo $ajax_nonce; ?>';
            var startStep = <?php echo $start_step; ?>;
            var isQuickMode = <?php echo $action_id > 0 ? 'true' : 'false'; ?>;
            var isSessionIntra = false;
            var sessionLieu = '';
            var selectedOrderId = null;
            var isCustomMode = false;
            var customUTPratique = 0;
            var customUTTheorique = 1;
            
            // Fonction pour afficher les notifications
            function showNotification(message, type) {
                var notification = $('#notification');
                notification.removeClass('success error info');
                notification.addClass(type);
                notification.text(message);
                notification.fadeIn(300);
                
                // Masquer après 5 secondes si c'est un succès ou une info
                if (type === 'success' || type === 'info') {
                    setTimeout(function() {
                        notification.fadeOut(300);
                    }, 5000);
                }
            }
            
            // Fonction de débogage
            function debug(message, obj) {
                if (obj) {
                    console.log(message, obj);
                    $('#debug-info').append(message + ': ' + JSON.stringify(obj) + "\n\n");
                } else {
                    console.log(message);
                    $('#debug-info').append(message + "\n\n");
                }
            }

            // Fonction pour passer de l'étape actuelle à la suivante
            function goToStep(current, next) {
                $('#step-' + current).hide();
                $('#step-' + next).show();
            }
            
            // Initialisation - Affichage de l'étape 0 si on est en mode rapide
            $(document).ready(function() {
                if (isQuickMode) {
                    $('#step-1').hide();
                    $('#step-0').show();
                }
                
                // Gestion des options initiales
                $('#option-new-order').on('click', function() {
                    $(this).addClass('selected');
                    $('#option-existing-order').removeClass('selected');
                    // Si on est en "quick mode" (depuis une action de formation)
                    if (isQuickMode) {
                        goToStep(0, 1); // Aller à l'étape 1 (Choix du produit) même en mode rapide
                    } else {
                        goToStep(0, 1); // Sinon aller à l'étape 1 (Choix du produit)
                    }
                });
                
                $('#option-existing-order').on('click', function() {
                    $(this).addClass('selected');
                    $('#option-new-order').removeClass('selected');
                    $('#step-0').hide();
                    $('#step-existing-order').show();
                });
                
                // Retour à l'écran de choix
                $('#back-to-options').on('click', function() {
                    $('#step-existing-order').hide();
                    $('#step-0').show();
                });
                
                // Recherche de commandes
                $('#search-btn').on('click', function() {
                    searchOrders();
                });
                
                $('#search-order').on('keypress', function(e) {
                    if (e.which === 13) {
                        searchOrders();
                    }
                });
                
                // Association de la commande
                $('#associate-order').on('click', function() {
                    associateOrderWithAction();
                });
            });
            
            // Fonction de recherche de commandes
            function searchOrders() {
                var searchTerm = $('#search-order').val();
                if (!searchTerm) {
                    showNotification('Veuillez saisir un terme de recherche', 'info');
                    return;
                }
                
                $('#orders-results').html('<div class="loading">Recherche en cours...</div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'search_existing_orders',
                        search_term: searchTerm
                    },
                    success: function(response) {
                        if (response.success) {
                            if (response.data.count > 0) {
                                displayOrdersResults(response.data.orders);
                            } else {
                                $('#orders-results').html('<div class="no-results">Aucune commande trouvée</div>');
                            }
                        } else {
                            $('#orders-results').html('<div class="error-message">Erreur: ' + response.data.message + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log("Erreur AJAX:", xhr.responseText);
                        $('#orders-results').html('<div class="error-message">Erreur lors de la recherche des commandes. Voir console pour détails.</div>');
                    }
                });
            }
            
            // Affichage des résultats
            function displayOrdersResults(orders) {
                if (!orders || orders.length === 0) {
                    $('#orders-results').html('<div class="no-results">Aucune commande trouvée</div>');
                    return;
                }
                
                var html = '';
                $.each(orders, function(i, order) {
                    html += '<div class="order-item" data-order-id="' + order.id + '">' +
                            '<div class="order-number">#' + order.number + '</div>' +
                            '<div class="order-customer">' + order.customer_name + '</div>' +
                            '<div class="order-date">' + order.date + '</div>' +
                            '<div class="order-status">' + order.status + '</div>' +
                            '<div class="order-total">' + order.total + '</div>' +
                            '</div>';
                });
                
                $('#orders-results').html(html);
                
                // Ajouter les événements de clic
                $('.order-item').on('click', function() {
                    var orderId = $(this).data('order-id');
                    $('.order-item').removeClass('selected');
                    $(this).addClass('selected');
                    loadOrderDetails(orderId);
                });
            }
            
            // Charger les détails d'une commande
            function loadOrderDetails(orderId) {
                selectedOrderId = orderId;
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_order_details',
                        order_id: orderId
                    },
                    success: function(response) {
                        if (response.success) {
                            displayOrderDetails(response.data.order);
                            $('#associate-order').prop('disabled', false);
                        } else {
                            showNotification('Erreur: ' + response.data.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showNotification('Erreur lors du chargement des détails de la commande', 'error');
                    }
                });
            }
            
            // Afficher les détails de la commande
            function displayOrderDetails(order) {
                var html = '<div class="order-info-line"><span>Numéro:</span><span>#' + order.number + '</span></div>';
                
                if (order.numconv) {
                    html += '<div class="order-info-line"><span>N° Convention:</span><span>' + order.numconv + '</span></div>';
                }
                
                html += '<div class="order-info-line"><span>Client:</span><span>' + order.customer_name + '</span></div>' +
                       '<div class="order-info-line"><span>Email:</span><span>' + order.customer_email + '</span></div>' +
                       '<div class="order-info-line"><span>Produit actuel:</span><span>' + order.product_name + '</span></div>' +
                       '<div class="order-info-line"><span>Total:</span><span>' + order.total + '</span></div>';
                       
                $('#order-info').html(html);
                $('#selected-order-details').show();
            }
            
            // Associer la commande à l'action de formation
            // Modification de la fonction d'association pour ajouter une étape intermédiaire
function associateOrderWithAction() {
    if (!selectedOrderId) {
        showNotification('Veuillez sélectionner une commande', 'error');
        return;
    }
    
    // Au lieu d'envoyer directement la requête, afficher un formulaire de configuration
    $('#step-existing-order').hide();
    
    // Récupérer les informations unitaires
    var html = '<div id="step-configure-association">' +
        '<h2><span class="step-number">C</span> Configuration de l\'association</h2>' +
        '<p>Vous allez associer la commande #' + selectedOrderId + ' à cette action de formation.</p>' +
        
        '<h3>Effectif</h3>' +
        '<div class="form-group">' +
            '<label>Nombre de stagiaires:</label>' +
            '<input type="number" id="assoc-effectif" value="1" min="1" max="50" />' +
        '</div>' +
        
        '<div id="assoc-stagiaires-container"></div>' +
        
        '<h3>Sélection du produit</h3>' +
        '<div class="form-group">' +
            '<label>Variation du produit:</label>' +
            '<select id="assoc-select-variation">' +
                '<option value="">-- Choisir une variation --</option>' +
            '</select>' +
        '</div>' +
        
        '<div class="form-group">' +
            '<label>Catégories:</label>' +
            '<select id="assoc-choix-categorie">' +
                '<option value="">-- Sélectionnez une option --</option>' +
            '</select>' +
        '</div>' +
        
        '<div id="assoc-custom-fields" style="display: none; margin-top: 10px;">' +
            '<div class="form-group">' +
                '<label>Catégories personnalisées:</label>' +
                '<input type="text" id="assoc-custom-categories" placeholder="Ex: A, B, C" />' +
            '</div>' +
            '<div class="form-group">' +
                '<label>UT Pratiques:</label>' +
                '<input type="number" id="assoc-custom-ut-pratique" min="0" step="0.5" placeholder="0" />' +
                '<span style="margin-left: 10px; font-style: italic;">UT Théoriques : 1</span>' +
            '</div>' +
        '</div>' +
        
        '<h3>Détails financiers</h3>' +
        '<div class="form-group">' +
            '<label>Coût global HT:</label>' +
            '<input type="text" id="assoc-unit-price" value="" />' +
        '</div>' +

        '<div class="form-group">' +
            '<label for="assoc-vat-rate">Taux de TVA:</label>' +
            '<select id="assoc-vat-rate" name="assoc_vat_rate" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;">' +
                '<option value="20">20% (Normal)</option>' +
                '<option value="10">10% (Intermédiaire)</option>' +
                '<option value="5.5">5.5% (Réduit)</option>' +
                '<option value="0">0% (Exonéré)</option>' +
            '</select>' +
        '</div>' +
        
        '<h3>Frais de formation (optionnel)</h3>' +
        '<div class="frais-fields">' +
            '<input type="text" id="assoc-fraisclient" placeholder="Montant frais client (HT)" />' +
            '<input type="text" id="assoc-datefrais" placeholder="Date frais client (jj/mm/aaaa)" />' +
            '<input type="text" id="assoc-nomfrais" placeholder="Dénomination des frais sur facture" />' +
        '</div>' +
        
        '<div class="buttons-container">' +
            '<button class="secondary" id="back-to-search">Retour</button>' +
            '<button id="confirm-association">Confirmer l\'association</button>' +
        '</div>' +
    '</div>';
    
    // Ajouter le HTML avant le premier step
    $('#step-0').before(html);
    $('#step-configure-association').show();
    
    // Récupérer le prix par défaut du produit
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'get_default_price_wizard',
            _ajax_nonce: ajax_nonce,
            product_id: selectedProductId
        },
        success: function(resp) {
            if(resp.success) {
                $('#assoc-unit-price').val(parseFloat(resp.data.regular_price).toFixed(2));
            } else {
                $('#assoc-unit-price').val('0.00');
            }
        },
        error: function() {
            $('#assoc-unit-price').val('0.00');
        }
    });
    
    // Charger les variations du produit
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'get_variations_wizard',
            _ajax_nonce: ajax_nonce,
            product_id: selectedProductId
        },
        success: function(resp) {
            if(resp.success && resp.data.variations) {
                var $dropdown = $('#assoc-select-variation');
                $dropdown.empty().append('<option value="">-- Choisir une variation --</option>');
                $.each(resp.data.variations, function(i, v) {
                    $dropdown.append('<option value="'+v.id+'" data-price="'+(v.regular_price || 0)+'">'+v.name+'</option>');
                });
            }
        }
    });
    
    // Charger les catégories disponibles
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'get_combos_for_wizard',
            _ajax_nonce: ajax_nonce,
            product_id: selectedProductId,
            variation_id: selectedVariationId
        },
        success: function(resp) {
            if(resp.success && resp.data.combos) {
                var $dropdown = $('#assoc-choix-categorie');
                $dropdown.empty().append('<option value="">-- Sélectionnez une option --</option>');
                $.each(resp.data.combos, function(i, combo) {
                    var categories = combo.categories.join(', ');
                    $dropdown.append('<option value="'+categories+'">'+categories+'</option>');
                });
                // Ajouter l'option personnalisée
                $dropdown.append('<option value="Personnalisé">Personnalisé</option>');
            }
        }
    });
    
    // Gérer la génération dynamique des champs stagiaires
    $('#assoc-effectif').on('change keyup', function(){
        var nb = parseInt($(this).val()) || 0;
        var container = $('#assoc-stagiaires-container');
        container.empty();
        for(var i=0; i<nb; i++){
            container.append(
                '<div class="stagiaire-line">' +
                    '<strong>Stagiaire '+(i+1)+'</strong>' +
                    '<div class="stagiaire-fields">' +
                        '<input type="text" name="assoc-stagiaire['+i+'][prenom]" placeholder="Prénom" />' +
                        '<input type="text" name="assoc-stagiaire['+i+'][nom]" placeholder="Nom" />' +
                        '<input type="text" name="assoc-stagiaire['+i+'][date_naiss]" placeholder="Date naissance (jj/mm/aaaa)" />' +
                    '</div>' +
                '</div>'
            );
        }
    });
    
    // Déclencher le changement initial pour afficher un stagiaire
    $('#assoc-effectif').trigger('change');
    
    // Gérer le changement de variation
    $('#assoc-select-variation').on('change', function() {
        var variationId = $(this).val();
        selectedVariationId = variationId;
        
        // Recharger les catégories pour cette variation
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_combos_for_wizard',
                _ajax_nonce: ajax_nonce,
                product_id: selectedProductId,
                variation_id: variationId
            },
            success: function(resp) {
                if(resp.success && resp.data.combos) {
                    var $dropdown = $('#assoc-choix-categorie');
                    $dropdown.empty().append('<option value="">-- Sélectionnez une option --</option>');
                    $.each(resp.data.combos, function(i, combo) {
                        var categories = combo.categories.join(', ');
                        $dropdown.append('<option value="'+categories+'">'+categories+'</option>');
                    });
                    // Ajouter l'option personnalisée
                    $dropdown.append('<option value="Personnalisé">Personnalisé</option>');
                }
            }
        });
    });
    
    // Gérer l'affichage des champs personnalisés
    $('#assoc-choix-categorie').on('change', function() {
        var categories = $(this).val();
        if (categories === 'Personnalisé') {
            $('#assoc-custom-fields').show();
        } else {
            $('#assoc-custom-fields').hide();
            $('#assoc-custom-categories').val('');
            $('#assoc-custom-ut-pratique').val('');
        }
        
        if (categories && categories !== 'Personnalisé') {
            selectedCategories = categories.split(', ');
            
            // Calculer le prix basé sur la grille tarifaire
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_price_from_grid',
                    _ajax_nonce: ajax_nonce,
                    product_id: selectedProductId,
                    variation_id: selectedVariationId,
                    categories: selectedCategories
                },
                success: function(resp) {
                    if(resp.success && resp.data.price) {
                        $('#assoc-unit-price').val(parseFloat(resp.data.price).toFixed(2));
                    }
                }
            });
        } else if (categories !== 'Personnalisé') {
            selectedCategories = [];
        }
    });
    
    // Gérer le bouton de retour
    $('#back-to-search').on('click', function() {
        $('#step-configure-association').remove();
        $('#step-existing-order').show();
    });
    
    // Gérer le bouton de confirmation
    $('#confirm-association').on('click', function() {
        // Collecter toutes les données
        var effectif = parseInt($('#assoc-effectif').val()) || 1;
        
        // Collecter les informations des stagiaires
        var stagiaires = [];
        for(var i=0; i<effectif; i++){
            stagiaires.push({
                prenom: $('input[name="assoc-stagiaire['+i+'][prenom]"]').val() || '',
                nom: $('input[name="assoc-stagiaire['+i+'][nom]"]').val() || '',
                date_naiss: $('input[name="assoc-stagiaire['+i+'][date_naiss]"]').val() || '',
                nir: ''
            });
        }
        
        // Récupérer les informations financières
        var unitPrice = parseFloat($('#assoc-unit-price').val()) || 0;
        var fraisMontant = $('#assoc-fraisclient').val() || '';
        var fraisDate = $('#assoc-datefrais').val() || '';
        var fraisNom = $('#assoc-nomfrais').val() || '';
        
        // Vérifier si c'est le mode personnalisé
        var isCustomMode = $('#assoc-choix-categorie').val() === 'Personnalisé';
        var ajaxData = {
            action: 'associate_order_with_action',
            order_id: selectedOrderId,
            action_id: selectedActionFormation,
            product_id: selectedProductId,
            variation_id: selectedVariationId,
            categories: selectedCategories,
            effectif: effectif,
            stagiaires: stagiaires,
            custom_unit_price: unitPrice, // This is the global price
            vat_rate: $('#assoc-vat-rate').val(), // Send selected VAT rate
            fraisclient_montant: fraisMontant,
            fraisclient_date: fraisDate,
            fraisclient_nom: fraisNom
        };
        
        // Ajouter les données du mode personnalisé si nécessaire
        if (isCustomMode) {
            ajaxData.is_custom_mode = true;
            ajaxData.custom_ut_pratique = parseFloat($('#assoc-custom-ut-pratique').val()) || 0;
            ajaxData.custom_ut_theorique = 1;
            // Utiliser les catégories personnalisées au lieu des catégories sélectionnées
            ajaxData.categories = [$('#assoc-custom-categories').val() || ''];
        }
        
        // Désactiver le bouton pendant le traitement
        $(this).prop('disabled', true).text('Association en cours...');
        
        // Envoyer la requête d'association avec toutes les données
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: ajaxData,
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    setTimeout(function() {
                        if (window.opener) {
                            window.opener.location.reload();
                            window.close();
                        }
                    }, 1500);
                } else {
                    $('#confirm-association').prop('disabled', false).text('Confirmer l\'association');
                    showNotification('Erreur: ' + response.data.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                $('#confirm-association').prop('disabled', false).text('Confirmer l\'association');
                showNotification('Erreur lors de l\'association de la commande', 'error');
            }
        });
    });
}

            // Gestion des boutons "Retour"
            $('.prev-step').on('click', function() {
                var prevStep = $(this).data('prev');
                $('[id^="step-"]').hide();
                $('#step-' + prevStep).show();
            });

            // Génération dynamique des champs stagiaires
            $('#input-effectif').on('change keyup', function(){
                var nb = parseInt($(this).val()) || 0;
                var container = $('#stagiaires-container');
                container.empty();
                for(var i=0; i<nb; i++){
                    container.append(
                        '<div class="stagiaire-line">' +
                            '<strong>Stagiaire '+(i+1)+'</strong>' +
                            '<div class="stagiaire-fields">' +
                                '<input type="text" name="stagiaire['+i+'][prenom]" placeholder="Prénom" />' +
                                '<input type="text" name="stagiaire['+i+'][nom]" placeholder="Nom" />' +
                                '<input type="text" name="stagiaire['+i+'][date_naiss]" placeholder="Date naissance (jj/mm/aaaa)" />' +
                            '</div>' +
                        '</div>'
                    );
                }
            });

            // Gestion des options d'adresse de livraison
            $('input[name="shipping_option"]').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#custom-shipping-form').slideDown(200);
                } else {
                    $('#custom-shipping-form').slideUp(200);
                }
            });

            // ========== ÉTAPE 1 => 2 ==========
            $('#go-step-2').on('click', function(){
                selectedProductId = $('#select-product').val();
                if(!selectedProductId){
                    showNotification('Veuillez sélectionner un produit.', 'error');
                    return;
                }
                
                debug("Produit sélectionné", selectedProductId);
                
                // Désactiver le bouton pendant la requête
                var $button = $(this);
                $button.prop('disabled', true).text('Chargement...');
                showNotification('Récupération des variations en cours...', 'info');

                // Récupère la liste de variations via AJAX
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_variations_wizard',
                        _ajax_nonce: ajax_nonce,
                        product_id: selectedProductId
                    },
                    success: function(resp) {
                        debug("Réponse variations", resp);
                        $button.prop('disabled', false).text('Suivant');
                        
                        if(resp.success){
                            var vars = resp.data.variations;
                            if(vars.length > 0){
                                // Produit variable
                                $('#select-variation').empty().append('<option value="">-- Choisir --</option>');
                                $.each(vars, function(i,v){
                                    $('#select-variation').append('<option value="'+v.id+'" data-price="'+(v.regular_price || 0)+'">'+v.name+'</option>');
                                });
                                $('#notification').fadeOut(300);
                                goToStep(1, 2);
                            } else {
                                // Pas de variations => produit simple
                                selectedVariationId = null;
                                // Récupérer le prix du produit simple
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    dataType: 'json',
                                    data: {
                                        action: 'get_default_price_wizard',
                                        _ajax_nonce: ajax_nonce,
                                        product_id: selectedProductId
                                    },
                                    success: function(resp2) {
                                        debug("Réponse prix par défaut", resp2);
                                        if(resp2.success){
                                            defaultUnitPrice = parseFloat(resp2.data.regular_price) || 0;
                                        }
                                        $('#notification').fadeOut(300);
                                        goToStep(1, 3);
                                    },
                                    error: function(xhr, status, error) {
                                        debug("Erreur AJAX prix par défaut: " + error);
                                        showNotification("Erreur lors de la récupération du prix: " + error, 'error');
                                    }
                                });
                            }
                        } else {
                            debug("Erreur récupération variations: " + resp.data.message);
                            showNotification('Erreur: ' + resp.data.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        debug("Erreur AJAX variations: " + error);
                        $button.prop('disabled', false).text('Suivant');
                        showNotification("Erreur lors de la récupération des variations: " + error, 'error');
                    }
                });
            });

            // ========== ÉTAPE 2 => 3 ==========
            $('#go-step-3').on('click', function(){
                selectedVariationId = $('#select-variation').val() || null;
                debug("Variation sélectionnée", selectedVariationId);
                
                if (!selectedVariationId) {
                    showNotification('Veuillez sélectionner une variation.', 'error');
                    return;
                }
                
                // Récupère le prix si variation
                if(selectedVariationId){
                    var chosen = $('#select-variation option:selected');
                    var p = parseFloat(chosen.data('price')) || 0;
                    defaultUnitPrice = p;
                    debug("Prix de la variation", defaultUnitPrice);
                }
                
                // Désactiver le bouton pendant la requête
                var $button = $(this);
                $button.prop('disabled', true).text('Chargement...');
                showNotification('Récupération des catégories en cours...', 'info');

                // Récupère la liste de combos de catégories
                var productOrVariationId = selectedVariationId ? selectedVariationId : selectedProductId;
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_combos_for_wizard',
                        _ajax_nonce: ajax_nonce,
                        product_id: productOrVariationId
                    },
                    success: function(resp) {
                        debug("Réponse combos", resp);
                        $button.prop('disabled', false).text('Suivant');
                        
                        if(resp.success){
                            var combos = resp.data.combos || [];
                            var $dropdown = $('#choix_categorie');
                            $dropdown.empty().append('<option value="">-- Sélectionnez une option --</option>');
                            $dropdown.append('<option value="personnalise">Personnalisé</option>');
                            if(combos.length === 0){
                                $('#notification').fadeOut(300);
                                goToStep(2, 3);
                            } else {
                                $.each(combos, function(i,combo){
                                    var categories = combo.categories.join(', ');
                                    $dropdown.append('<option value="'+categories+'">'+categories+'</option>');
                                });
                                $('#notification').fadeOut(300);
                                goToStep(2, 3);
                            }
                        } else {
                            debug("Erreur récupération combos: " + resp.data.message);
                            showNotification('Erreur: ' + resp.data.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        debug("Erreur AJAX combos: " + error);
                        $button.prop('disabled', false).text('Suivant');
                        showNotification("Erreur lors de la récupération des catégories: " + error, 'error');
                    }
                });
            });

            // ========== Gestion des champs personnalisés ==========
            $(document).on('change', '#choix_categorie', function() {
                var selectedValue = $(this).val();
                if (selectedValue === 'personnalise') {
                    $('#custom-fields').show();
                } else {
                    $('#custom-fields').hide();
                    // Réinitialiser les champs personnalisés
                    $('#custom-categories').val('');
                    $('#custom-ut-pratique').val('');
                }
            });

            // ========== ÉTAPE 3 => 4 ==========
            $('#go-step-4').on('click', function(){
                var catVal = $('#choix_categorie').val();
                if(catVal === 'personnalise'){
                    // Mode personnalisé
                    var customCat = $('#custom-categories').val().trim();
                    var customUT = $('#custom-ut-pratique').val();
                    
                    if(!customCat || !customUT) {
                        showNotification('Veuillez remplir les catégories et UT personnalisées.', 'error');
                        return;
                    }
                    
                    selectedCategories = [customCat];
                    customUTPratique = parseFloat(customUT);
                    customUTTheorique = 1; // Toujours 1 pour les UT théoriques
                    isCustomMode = true;
                } else if(catVal){
                    selectedCategories = catVal.split(',');
                    isCustomMode = false;
                } else {
                    selectedCategories = [];
                    isCustomMode = false;
                }
                debug("Catégories sélectionnées", selectedCategories);
                debug("Mode personnalisé", isCustomMode);
                
                // Désactiver le bouton pendant la requête
                var $button = $(this);
                $button.prop('disabled', true).text('Chargement...');
                showNotification('Récupération des sessions en cours...', 'info');

                // Récupère la liste d'actions (sessions)
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_actions_wizard',
                        _ajax_nonce: ajax_nonce,
                        product_id: selectedProductId
                    },
                    success: function(resp) {
                        debug("Réponse sessions", resp);
                        $button.prop('disabled', false).text('Suivant');
                        
                        if(resp.success){
                            var acts = resp.data.actions || [];
                            var sel = $('#select-action-formation');
                            // Ne pas écraser si en mode démarrage étape 5
                            if (!isQuickMode) {
                                sel.empty().append('<option value="">-- Choisir --</option>');
                                $.each(acts, function(i,a){
                                    sel.append('<option value="'+a.id+'">'+a.text+'</option>');
                                });
                            }
                            $('#notification').fadeOut(300);
                            goToStep(3, 4);
                        } else {
                            debug("Erreur récupération sessions: " + resp.data.message);
                            showNotification('Erreur: ' + resp.data.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        debug("Erreur AJAX sessions: " + error);
                        $button.prop('disabled', false).text('Suivant');
                        showNotification("Erreur lors de la récupération des sessions: " + error, 'error');
                    }
                });
            });

            // ========== ÉTAPE 4 => 5 ==========
            $('#go-step-5').on('click', function(){
                selectedActionFormation = $('#select-action-formation').val() || null;
                debug("Session sélectionnée", selectedActionFormation);
                
                if (!selectedActionFormation) {
                    showNotification("Veuillez sélectionner une session.", 'error');
                    return;
                }
                
                // Vérifier si c'est une session INTRA
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_session_type',
                        action_id: selectedActionFormation
                    },
                    success: function(resp) {
                        debug("Réponse type de session", resp);
                        if(resp.success){
                            // Stocker les informations pour utilisation ultérieure
                            isSessionIntra = resp.data.is_intra;
                            sessionLieu = resp.data.lieu_complet;
                            debug("Session INTRA ?", isSessionIntra);
                            debug("Lieu de la session", sessionLieu);
                            
                            $('#notification').fadeOut(300);
                            goToStep(4, 5);
                        } else {
                            debug("Erreur récupération type de session: " + (resp.data.message || "Erreur inconnue"));
                            showNotification('Erreur: ' + (resp.data.message || "Erreur inconnue"), 'error');
                            // On continue quand même
                            goToStep(4, 5);
                        }
                    },
                    error: function(xhr, status, error) {
                        debug("Erreur AJAX type de session: " + error);
                        showNotification("Erreur lors de la récupération du type de session: " + error, 'error');
                        // On continue quand même
                        goToStep(4, 5);
                    }
                });
            });

            // ========== ÉTAPE 5 => 6 ==========
            $('#go-step-6').on('click', function() {
                debug("Passage à l'étape 6");
                
                // Vérifier si l'effectif est valide
                var effectif = parseInt($('#input-effectif').val()) || 0;
                if (effectif <= 0) {
                    showNotification("Veuillez saisir un effectif valide.", 'error');
                    return;
                }
                
                // Désactiver le bouton pendant la requête
                var $button = $(this);
                $button.prop('disabled', true).text('Chargement...');
                showNotification('Récupération du prix en cours...', 'info');
                
                debug("Données pour le prix", {
                    product_id: selectedProductId,
                    variation_id: selectedVariationId,
                    categories: selectedCategories
                });
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_price_from_grid',
                        _ajax_nonce: ajax_nonce,
                        product_id: selectedProductId,
                        variation_id: selectedVariationId,
                        categories: selectedCategories
                    },
                    success: function(resp) {
                        debug("Réponse prix grille", resp);
                        $button.prop('disabled', false).text('Suivant');
                        
                        if (resp.success && resp.data.price) {
                            $('#input-unit-price').val(parseFloat(resp.data.price).toFixed(2));
                            $('#notification').fadeOut(300);
                            goToStep(5, 6);
                        } else {
                            // Si pas de prix trouvé, essayer de récupérer le prix par défaut
                            if (defaultUnitPrice > 0) {
                                $('#input-unit-price').val(defaultUnitPrice.toFixed(2));
                                $('#notification').fadeOut(300);
                                goToStep(5, 6);
                            } else {
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    dataType: 'json',
                                    data: {
                                        action: 'get_default_price_wizard',
                                        _ajax_nonce: ajax_nonce,
                                        product_id: selectedProductId
                                    },
                                    success: function(resp2) {
                                        debug("Réponse prix défaut", resp2);
                                        if(resp2.success){
                                            defaultUnitPrice = parseFloat(resp2.data.regular_price) || 0;
                                            $('#input-unit-price').val(defaultUnitPrice.toFixed(2));
                                        } else {
                                            $('#input-unit-price').val('0.00');
                                        }
                                        $('#notification').fadeOut(300);
                                        goToStep(5, 6);
                                    },
                                    error: function(xhr, status, error) {
                                        debug("Erreur AJAX prix défaut: " + error);
                                        $('#input-unit-price').val('0.00');
                                        showNotification("Erreur lors de la récupération du prix par défaut: " + error, 'error');
                                        goToStep(5, 6);
                                    }
                                });
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        debug("Erreur AJAX prix grille: " + error);
                        $button.prop('disabled', false).text('Suivant');
                        $('#input-unit-price').val('0.00');
                        showNotification("Erreur lors de la récupération du prix: " + error, 'error');
                        goToStep(5, 6);
                    }
                });
            });

            // ========== ÉTAPE 6 => 7 ==========
            $('#go-step-7').on('click', function(){
                debug("Passage à l'étape 7");
                
                // Vérifier si le prix unitaire est valide
                var price = parseFloat($('#input-unit-price').val()) || 0;
                if (price <= 0) {
                    showNotification("Veuillez saisir un prix unitaire valide.", 'error');
                    return;
                }
                
                // Configurer la section d'adresse de livraison si INTRA
                if (isSessionIntra) {
                    $('#formation-lieu-display').text('Lieu actuel : ' + sessionLieu);
                    $('#shipping-address-section').show();
                    debug("Affichage options livraison pour INTRA", { lieu: sessionLieu });
                } else {
                    $('#shipping-address-section').hide();
                    debug("Session non-INTRA, options de livraison masquées");
                }
                
                $('#notification').fadeOut(300);
                goToStep(6, 7);
            });

            // ========== ÉTAPE 7 => 8 ==========
            $('#go-step-8').on('click', function(){
                selectedCustomerId = $('#select-customer').val() || null;
                debug("Client sélectionné", selectedCustomerId);
                
                if (!selectedCustomerId) {
                    showNotification("Veuillez sélectionner un client.", 'error');
                    return;
                }
                
                $('#notification').fadeOut(300);
                goToStep(7, 8);
            });

            // ========== CRÉER LA COMMANDE (Étape 8) ==========
            $('#finalize-order').on('click', function(e){
                e.preventDefault();
                debug("Tentative de création de commande");
                
                var eff = parseInt($('#input-effectif').val()) || 0;
                // Récupération stagiaires
                var stgs = [];
                for(var i=0; i<eff; i++){
                    stgs.push({
                        prenom:     $('[name="stagiaire['+i+'][prenom]"]').val() || '',
                        nom:        $('[name="stagiaire['+i+'][nom]"]').val() || '',
                        date_naiss: $('[name="stagiaire['+i+'][date_naiss]"]').val() || '',
                        nir:        '' // adapter si besoin
                    });
                }

                // Récupérer prix unitaire
                var overridePrice = parseFloat($('#input-unit-price').val()) || 0;

                // Récupérer frais
                var fraisMontant = $('#input-fraisclient').val() || '';
                var fraisDate    = $('#input-datefrais').val() || '';
                var fraisNom     = $('#input-nomfrais').val() || '';

                // Statut
                selectedOrderStatus = $('#select-order-status').val() || 'wc-on-hold';
                
                // Données d'adresse de livraison
                var useShippingAddress = isSessionIntra;
                var shippingOption = $('input[name="shipping_option"]:checked').val() || 'billing';
                var shippingAddress = {};
                
                if (shippingOption === 'custom') {
                    shippingAddress = {
                        first_name: $('#shipping_first_name').val() || '',
                        last_name: $('#shipping_last_name').val() || '',
                        company: $('#shipping_company').val() || '',
                        address_1: $('#shipping_address_1').val() || '',
                        address_2: $('#shipping_address_2').val() || '',
                        city: $('#shipping_city').val() || '',
                        state: '',
                        postcode: $('#shipping_postcode').val() || '',
                        country: $('#shipping_country').val() || 'FR'
                    };
                }
                
                var formData = {
                    action:              'create_custom_order_wizard',
                    _ajax_nonce:         ajax_nonce,
                    product_id:          selectedProductId,
                    variation_id:        selectedVariationId,
                    effectif:            eff,
                    categories_selected: selectedCategories,
                    action_formation_id: selectedActionFormation,
                    stagiaires:          stgs,
                    custom_unit_price:   overridePrice,
                    fraisclient_montant: fraisMontant,
                    fraisclient_date:    fraisDate,
                    fraisclient_nom:     fraisNom,
                    customer_id:         selectedCustomerId,
                    order_status:        selectedOrderStatus,
                    vat_rate:            $('#select-vat-rate').val(),
                    use_shipping_address: useShippingAddress,
                    shipping_option:     shippingOption,
                    shipping_address:    shippingAddress,
                    is_custom_mode:      isCustomMode,
                    custom_ut_pratique:  customUTPratique,
                    custom_ut_theorique: customUTTheorique
                };
                
                debug("Données envoyées pour la création", formData);

                // Désactiver le bouton pour éviter les clics multiples
                $('#finalize-order').prop('disabled', true).text('Création en cours...');
                showNotification('Création de la commande en cours...', 'info');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: formData,
                    success: function(resp) {
                        debug("Réponse création commande", resp);
                        if(resp.success){
                            showNotification(resp.data.message + ' (ID: '+resp.data.order_id+')', 'success');
                            
                            // Comportement différent selon l'origine
                            setTimeout(function() {
                                if (isQuickMode) {
                                    // Rafraîchir la page parent et fermer cette fenêtre
                                    if (window.opener) {
                                        window.opener.location.reload();
                                        window.close();
                                    }
                                } else {
                                    // Rediriger vers la commande créée
                                    window.location.href = resp.data.edit_link;
                                }
                            }, 1500);
                        } else {
                            $('#finalize-order').prop('disabled', false).text('Créer la commande');
                            showNotification('Erreur: ' + (resp.data.message || 'Une erreur est survenue lors de la création de la commande.'), 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        debug("Erreur AJAX création commande: " + error);
                        $('#finalize-order').prop('disabled', false).text('Créer la commande');
                        showNotification("Erreur lors de la création de la commande: " + error, 'error');
                    }
                });
                
                return false;
            });
        })(jQuery);
        </script>
    </body>
    </html>
    <?php
    exit;
}