<?php
/**
 * Snippet ID: 36
 * Name: ENTETE sur CPT Actions de formation commandes liées
 * Description: 
 * @active true
 */

// Ajouter une metabox sous forme de tableau pour afficher les commandes liées aux actions de formation
add_action('add_meta_boxes', 'cpt_linked_orders_table');

function cpt_linked_orders_table() {
    add_meta_box(
        'cpt-linked-orders',
        'Conventions - Commandes - Affaires liées',
        'cpt_linked_orders_table_callback',
        'action-de-formation', // Type de CPT
        'normal',
        'high'
    );

    // Suppression de l'ancienne metabox dédiée au bouton (si elle existe encore)
    remove_meta_box('quick_order_metabox', 'action-de-formation', 'side');
}

// Fonction helper pour récupérer les métadonnées de commande de manière fiable
function get_order_metadata_value($order_id, $meta_key, $default = '') {
    // Essayer d'abord avec la méthode standard
    $value = get_post_meta($order_id, $meta_key, true);
    
    // Si vide, essayer avec un underscore préfixe (format courant pour WooCommerce)
    if (empty($value) && $meta_key[0] !== '_') {
        $value = get_post_meta($order_id, '_' . $meta_key, true);
    }
    
    // Si toujours vide, essayer avec l'API WooCommerce
    if (empty($value)) {
        $order = wc_get_order($order_id);
        if ($order) {
            $value = $order->get_meta($meta_key);
            
            // Certaines métadonnées peuvent être stockées comme champs personnalisés
            if (empty($value)) {
                $value = $order->get_meta('_' . $meta_key);
            }
        }
    }
    
    return !empty($value) ? $value : $default;
}

function cpt_linked_orders_table_callback($post) {
    global $wpdb;

    // Récupérer l'ID du CPT
    $cpt_id = $post->ID;

    // Récupérer les informations pour le bouton de création de commande
    $product_id = get_post_meta($cpt_id, 'fsbdd_relsessproduit', true);

    // Vérifier si l'utilisateur a les permissions et si un produit est associé
    $current_user = wp_get_current_user();
    $allowed_roles = array('administrator', 'referent', 'compta');
    $can_access = false;

    foreach ($allowed_roles as $role) {
        if (in_array($role, (array) $current_user->roles)) {
            $can_access = true;
            break;
        }
    }

    if ($can_access && $product_id) {
        // URL vers notre point d'entrée minimal avec action_id et start_step
        $popup_url = admin_url('admin-ajax.php?action=minimal_order_form&action_id=' . $cpt_id . '&start_step=5');

        // Créer le HTML pour l'en-tête avec filtre et bouton
        $header_html = '<div style="float: right; margin-top: -40px; margin-right: 10px; display: flex; align-items: center; gap: 10px;">';
        $header_html .= '<div style="margin-top: 40px;">';
        $header_html .= '<label for="status-filter-select" style="font-size: 13px; color: #fff !important; margin-right: 5px;">Statut :</label>';
        $header_html .= '<select id="status-filter-select" style="padding: 2px 8px; font-size: 12px; color:#314150 !important; font-weight: 600; width: 200px;">';
        $header_html .= '<option value="all">Toutes les commandes</option>';
        $header_html .= '<option value="devis">Devis</option>';
        $header_html .= '<option value="options">Options</option>';
        $header_html .= '<option value="conventionnees">Conventionnées</option>';
        $header_html .= '<option value="terminees">Terminées</option>';
        $header_html .= '<option value="options_conventionnees">Options et Conventions</option>';
        $header_html .= '<option value="options_conventionnees_terminees">Options, Conv. et Terminées</option>';
        $header_html .= '</select>';
        $header_html .= '</div>';
        $header_html .= '<a href="' . esc_url($popup_url) . '" class="button button-primary" style="background: #299a68; color: #fff !important; margin-top: 40px;" onclick="window.open(this.href, \'Création de commande\', \'width=900,height=700 toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1\'); return false;">';
        $header_html .= '<span class="dashicons dashicons-plus" style="margin-top: 3px; color: #fff !important;"></span> Ajouter';
        $header_html .= '</a>';
        $header_html .= '</div>';

        echo '<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                var metaboxTitle = document.querySelector("#cpt-linked-orders h2.hndle");
                if (metaboxTitle) {
                    metaboxTitle.insertAdjacentHTML("afterend", \'' . addslashes($header_html) . '\');
                }
            });
        </script>';
    } elseif (!$product_id) {
        echo '<div class="notice notice-warning inline"><p>Impossible de créer une commande : aucun produit associé à cette session.</p></div>';
    }

    if (empty($cpt_id)) {
        echo '<p>Aucune commande trouvée.</p>';
        return;
    }

    // Requête pour récupérer les commandes liées à fsbdd_relsessaction_cpt_produit
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
        echo '<p>Aucune commande trouvée pour cette action de formation.</p>';
        return;
    }

    // Styles CSS pour le tableau et le bouton
echo '<style>
    .linked-orders-table {
        border-collapse: collapse;
        width: 100%;
        margin: 0;
        border: none;
    }
    .linked-orders-table th, .linked-orders-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    .linked-orders-table th {
        background-color: #d5ebff;
        color: #314150;
        font-weight: bold;
    }
    .linked-orders-table tr:nth-child(even) {
        background-color: #ebebeb;
    }
    .linked-orders-table tr:nth-child(odd) {
        background-color: #fff;
    }
    #cpt-linked-orders .inside {
        padding: 0 !important;
        margin: 0 !important;
    }
    #cpt-linked-orders .postbox-header {
        padding-bottom: 0;
        margin-bottom: 0;
        background-color: #314150 !important;
        color: #ffffff !important;
        border: none !important; /* Remove border around the header */
						text-transform: uppercase !important;
    }
    #cpt-linked-orders .hndle {
        color: #ffffff !important;
        border: none !important; /* Remove border around the header */
						text-transform: uppercase !important;
    }
    #cpt-linked-orders .handlediv {
        display: none !important;
    }
    #cpt-linked-orders {
        margin-top: 0;
        border: none !important; /* Ensure no border is applied to the metabox */
    }
    .category-badge {
        background: #e8f4f8;
        color: #0073aa;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
        margin: 1px;
        display: inline-block;
    }
    .linked-orders-table th:nth-child(4), .linked-orders-table td:nth-child(4) {
        width: 180px;
        max-width: 180px;
    }
    .linked-orders-table th:nth-child(5), .linked-orders-table td:nth-child(5) {
        width: 80px;
        max-width: 80px;
    }
    .linked-orders-table th:nth-child(6), .linked-orders-table td:nth-child(6) {
        width: 80px;
        max-width: 130px;
    }
    .linked-orders-table th:nth-child(7), .linked-orders-table td:nth-child(7) {
        width: 140px;
        max-width: 140px;
    }
    .linked-orders-table th:nth-child(8), .linked-orders-table td:nth-child(8) {
        width: 60px;
        max-width: 50px;
    }
    .linked-orders-table th:nth-child(11), .linked-orders-table td:nth-child(11) {
        width: 280px;
        max-width: 300px;
    }
    .linked-orders-table th:nth-child(12), .linked-orders-table td:nth-child(12) {
        width: 60px;
        max-width: 60px;
        text-align: center;
    }
    .product-cell {
        max-width: 180px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .convocations-cell {
        max-width: 280px;
        word-wrap: break-word;
    }
    .rapprochement-cell {
        text-align: center;
        font-weight: bold;
    }
    .rapprochement-100 {
        color: #46b450;
    }
    .rapprochement-high {
        color: #F98508;
    }
    .rapprochement-low {
        color: #dc3232;
    }
</style>';



    // Construire le tableau HTML
    echo '<table class="linked-orders-table">';
    echo '<thead>
            <tr>
                <th>Commande</th>
                <th>N° Convent°</th>
                <th>Client</th>
                <th>Produit</th>
                <th>Options</th>
                <th>Catégories</th>
                <th>Statut</th>
                <th>Effectif</th>
                <th>Référent</th>
                <th>CA</th>
                <th>Convocations</th>
                <th>Vérif</th>
            </tr>
          </thead>';
    echo '<tbody>';

    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            continue;
        }

        // Récupérer les données existantes
        $order_url = admin_url('post.php?post=' . absint($order_id) . '&action=edit');
        $billing_company = $order->get_billing_company();
        $customer_name = strtolower($billing_company) === 'pas de société' ? $order->get_formatted_billing_full_name() : $billing_company;
        $order_status_name = wc_get_order_status_name($order->get_status());
        
        // Limiter le nom du statut à 20 caractères
        if (strlen($order_status_name) > 20) {
            $order_status_name = substr($order_status_name, 0, 20) . '...';
        }
        
        // Récupérer le statut complet avec le préfixe wc-
        $order_status_full = $order->get_status();
        $order_status_with_prefix = 'wc-' . $order_status_full;
        
        // Récupération du numéro de convention
        $num_conv = get_order_metadata_value($order_id, 'fsbdd_numconv', 'Non défini');
        
        // Récupération de l'effectif à partir de la quantité dans le panier
        $effectif = 0;
        $items = $order->get_items();
        
        foreach ($items as $item) {
            // Vérifier si ce produit est lié à l'action de formation actuelle
            $item_action_id = wc_get_order_item_meta($item->get_id(), 'fsbdd_relsessaction_cpt_produit', true);
            
            // Si l'item est lié à notre action de formation ou si on n'a pas d'ID spécifique
            if ($item_action_id == $cpt_id || empty($item_action_id)) {
                $effectif += $item->get_quantity();
            }
        }
        
        // Utiliser l'effectif du champ meta comme fallback si la quantité est 0
        if ($effectif == 0) {
            $effectif_fallback = get_order_metadata_value($order_id, 'fsbdd_effectif', '');
            if (!empty($effectif_fallback)) {
                $effectif = $effectif_fallback;
            } else {
                $effectif = 'Non défini';
            }
        }
        
        // Autres champs à récupérer
        $user_referent_id = get_order_metadata_value($order_id, 'fsbdd_user_referentrel');
        $user_referent = get_userdata($user_referent_id);
        $user_referent_name = $user_referent ? $user_referent->first_name : 'Non défini';
        $marge = get_order_metadata_value($order_id, 'fsbdd_marge', 'Non défini');

        // Récupérer fsbdd_numconv
        $num_conv = get_post_meta($order_id, 'fsbdd_numconv', true);
        $num_conv_output = !empty($num_conv) ? $num_conv : 'Non défini';

        // Récupérer le nom du produit et les catégories
        $product_name = 'Non défini';
        $full_product_name = 'Non défini';
        $categories_output = 'Aucune';
        
        foreach ($items as $item) {
            // Vérifier si ce produit est lié à l'action de formation actuelle
            $item_action_id = wc_get_order_item_meta($item->get_id(), 'fsbdd_relsessaction_cpt_produit', true);
            
            if ($item_action_id == $cpt_id || empty($item_action_id)) {
                // Récupérer le nom du produit et le limiter à 40 caractères
                $full_product_name = $item->get_name();
                $product_name = strlen($full_product_name) > 40 ? substr($full_product_name, 0, 40) . '...' : $full_product_name;
                
                // Récupérer les catégories
                $choix_categorie = wc_get_order_item_meta($item->get_id(), 'choix_categorie', true);
                
                if ($choix_categorie) {
                    $categories = explode(',', $choix_categorie);
                    $badges = array();
                    
                    foreach ($categories as $cat) {
                        $cat = trim($cat);
                        if (!empty($cat)) {
                            $badges[] = '<span class="category-badge">' . esc_html($cat) . '</span>';
                        }
                    }
                    
                    if (!empty($badges)) {
                        $categories_output = implode(' ', $badges);
                    }
                }
                
                // Comme il n'y a qu'un seul produit par commande, on peut sortir de la boucle
                break;
            }
        }

        // Récupérer les variations pour la colonne "Options"
        $variations_list = [];

        foreach ($items as $item) {
            if ($item->get_variation_id()) {
                $variation_id = $item->get_variation_id();
                $product_variation = new WC_Product_Variation($variation_id);
                $variation_attributes = $product_variation->get_variation_attributes();

                $variation_parts = [];
                foreach ($variation_attributes as $attr_value) {
                    $variation_parts[] = $attr_value;
                }

                if (!empty($variation_parts)) {
                    $variations_list[] = implode(' | ', $variation_parts);
                }
            }
        }

        $options_output = !empty($variations_list) ? implode(' - ', $variations_list) : 'Aucune';

        // LOGIQUE POUR LES CONVOCATIONS - VERSION OPTIMISÉE
        $convoc_summary = [];
        // Récupérer le planning de l'action de formation liée (CPT)
        $planning_meta = get_post_meta($cpt_id, 'fsbdd_planning', true);
        $planning = $planning_meta ? maybe_unserialize($planning_meta) : [];

        if (is_array($planning) && !empty($planning)) {
            // Trier le planning par date pour un affichage chronologique
            usort($planning, function($a, $b) {
                $dateA_str = $a['fsbdd_planjour'] ?? '9999-12-31';
                $dateB_str = $b['fsbdd_planjour'] ?? '9999-12-31';

                $dateA = strtotime($dateA_str);
                 if ($dateA === false) {
                    $dtA = DateTime::createFromFormat('d/m/Y', $dateA_str);
                    $dateA = $dtA ? $dtA->getTimestamp() : strtotime('9999-12-31');
                 }

                $dateB = strtotime($dateB_str);
                 if ($dateB === false) {
                     $dtB = DateTime::createFromFormat('d/m/Y', $dateB_str);
                     $dateB = $dtB ? $dtB->getTimestamp() : strtotime('9999-12-31');
                 }

                return $dateA <=> $dateB;
            });

            // Collecter toutes les convocations valides
            $all_convocations = [];
            
            foreach ($planning as $day) {
                $date_raw = $day['fsbdd_planjour'] ?? '';
                if (empty($date_raw)) {
                    continue;
                }

                $date_obj = false;
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_raw)) {
                    $date_obj = DateTime::createFromFormat('Y-m-d', $date_raw);
                } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date_raw)) {
                     $date_obj = DateTime::createFromFormat('d/m/Y', $date_raw);
                 } else {
                     $timestamp = strtotime($date_raw);
                     if ($timestamp) {
                        $date_obj = new DateTime();
                        $date_obj->setTimestamp($timestamp);
                     }
                 }

                if (!$date_obj) {
                    error_log("Impossible de parser la date du planning: " . $date_raw . " pour CPT ID " . $cpt_id);
                    continue;
                }

                $date_dmy = $date_obj->format('d/m/Y');
                $date_dm = $date_obj->format('d/m');

                // Vérifier les métas de convocation pour cette date sur la commande
                $is_matin = get_post_meta($order_id, 'fsbdd_convoc_matin_' . $date_dmy, true);
                $is_aprem = get_post_meta($order_id, 'fsbdd_convoc_aprem_' . $date_dmy, true);

                $abbr = '';
                if ($is_matin === '1') {
                    $abbr .= 'M';
                }
                if ($is_aprem === '1') {
                    $abbr .= 'A';
                }

                if (!empty($abbr)) {
                    $all_convocations[] = [
                        'date_display' => $date_dm,
                        'abbr' => $abbr,
                        'full_string' => $date_dm . ' ' . $abbr,
                        'timestamp' => $date_obj->getTimestamp()
                    ];
                }
            }

            // Logique d'affichage optimisée - limitée aux 6 premiers jours
            if (!empty($all_convocations)) {
                $total_convocations = count($all_convocations);
                
                if ($total_convocations <= 6) {
                    // Afficher toutes les convocations si <= 6
                    foreach ($all_convocations as $convoc) {
                        $convoc_summary[] = $convoc['full_string'];
                    }
                } else {
                    // Afficher seulement les 6 premiers jours
                    for ($i = 0; $i < 6; $i++) {
                        $convoc_summary[] = $all_convocations[$i]['full_string'];
                    }
                    
                    // Ajouter un indicateur du nombre total
                    $convoc_summary[] = "... ({$total_convocations} dates)";
                }
            }
        }

        // Générer la chaîne finale
        $convoc_dates_output = !empty($convoc_summary) ? implode(', ', $convoc_summary) : 'Aucune';

        // RÉCUPÉRER LES DONNÉES DE RAPPROCHEMENT DE MANIÈRE SÉCURISÉE
        $champs_rapprochement = array('session', 'specificites', 'convocations', 'quantites_couts', 'subro_reglements', 'client_bdd_web');
        $total_etapes = count($champs_rapprochement);
        $etapes_completees = 0;
        
        // Compter les étapes complétées en vérifiant l'existence des métadonnées
        foreach ($champs_rapprochement as $champ) {
            $meta_value = get_post_meta($order_id, 'fsbdd_rappro_' . $champ, true);
            if ($meta_value === '1') {
                $etapes_completees++;
            }
        }
        
        $pourcentage_rapprochement = $total_etapes > 0 ? round(($etapes_completees / $total_etapes) * 100) : 0;
        $rapprochement_display = $etapes_completees . '/' . $total_etapes;
        
        // Déterminer la classe CSS en fonction du pourcentage
        $rapprochement_class = 'rapprochement-low';
        if ($pourcentage_rapprochement == 100) {
            $rapprochement_class = 'rapprochement-100';
        } elseif ($pourcentage_rapprochement >= 50) {
            $rapprochement_class = 'rapprochement-high';
        }

        // Ligne du tableau avec le statut complet pour le filtrage - ORDRE RÉORGANISÉ
        echo '<tr data-status="' . esc_attr($order_status_with_prefix) . '">';
        echo '<td><a href="' . esc_url($order_url) . '" target="_blank">#' . esc_html($order->get_order_number()) . '</a></td>';
        echo '<td>' . esc_html($num_conv_output) . '</td>';
        echo '<td>' . esc_html($customer_name) . '</td>';
        echo '<td class="product-cell" title="' . esc_attr($full_product_name) . '">' . esc_html($product_name) . '</td>';
        echo '<td>' . esc_html($options_output) . '</td>';
        echo '<td>' . $categories_output . '</td>';
        echo '<td>' . esc_html($order_status_name) . '</td>';
        echo '<td>' . esc_html($effectif) . '</td>';
        echo '<td>' . esc_html($user_referent_name) . '</td>';
        echo '<td>' . esc_html($marge) . '</td>';
        echo '<td class="convocations-cell">' . esc_html($convoc_dates_output) . '</td>';
        echo '<td class="rapprochement-cell ' . $rapprochement_class . '" title="' . $pourcentage_rapprochement . '%">' . esc_html($rapprochement_display) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    // SCRIPT DE FILTRAGE FINAL - VERSION STABLE
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusFilter = document.getElementById('status-filter-select');
            
            if (!statusFilter) {
                return;
            }

            // Mapping avec les codes de statuts WooCommerce (ajout du nouveau filtre combiné)
            const statusMapping = {
                'all': [],
                'devis': ['wc-gplsquote-req', 'wc-devisproposition'],
                'options': ['wc-preinscription', 'wc-modifpreinscript', 'wc-inscription'],
                'conventionnees': ['wc-confirme', 'wc-avenantconv', 'wc-avenantvalide'],
                'terminees': ['wc-certifreal', 'wc-attestationform', 'wc-facturesent', 'wc-factureok'],
                'options_conventionnees': ['wc-preinscription', 'wc-modifpreinscript', 'wc-inscription', 'wc-confirme', 'wc-avenantconv', 'wc-avenantvalide'],
                'options_conventionnees_terminees': ['wc-preinscription', 'wc-modifpreinscript', 'wc-inscription', 'wc-confirme', 'wc-avenantconv', 'wc-avenantvalide', 'wc-certifreal', 'wc-attestationform', 'wc-facturesent', 'wc-factureok']
            };

            function filterTable() {
                const tableRows = document.querySelectorAll('.linked-orders-table tbody tr');
                const selected = statusFilter.value;
                const validStatuses = statusMapping[selected];

                tableRows.forEach(function(row) {
                    const rowStatus = row.getAttribute('data-status');
                    
                    if (selected === 'all' || validStatuses.includes(rowStatus)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            // Restaurer l'état depuis la variable globale si elle existe
            if (typeof window.currentFilterState !== 'undefined') {
                statusFilter.value = window.currentFilterState;
            } else {
                // Définir la nouvelle valeur par défaut
                statusFilter.value = 'options_conventionnees_terminees';
                window.currentFilterState = 'options_conventionnees_terminees';
            }
            
            // Appliquer le filtre initial
            filterTable();

            // Écouter les changements de filtre
            statusFilter.addEventListener('change', function() {
                window.currentFilterState = this.value;
                setTimeout(filterTable, 50);
            });
        });
    </script>";
}

// 1. Assurer la sauvegarde correcte de l'effectif
add_action('woocommerce_update_order', 'force_update_effectif_metadata', 20, 2);
function force_update_effectif_metadata($order_id, $order) {
    // Récupérer la valeur d'effectif depuis les données POST si c'est une mise à jour formulaire
    if (isset($_POST['fsbdd_effectif'])) {
        $effectif = sanitize_text_field($_POST['fsbdd_effectif']);
        
        // Force la mise à jour directe en base de données pour contourner le cache
        global $wpdb;
        $meta_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
            $order_id, 'fsbdd_effectif'
        ));
        
        if ($meta_exists) {
            // Mettre à jour la valeur existante
            $wpdb->update(
                $wpdb->postmeta,
                array('meta_value' => $effectif),
                array('post_id' => $order_id, 'meta_key' => 'fsbdd_effectif')
            );
        } else {
            // Insérer une nouvelle valeur
            $wpdb->insert(
                $wpdb->postmeta,
                array(
                    'post_id' => $order_id,
                    'meta_key' => 'fsbdd_effectif',
                    'meta_value' => $effectif
                )
            );
        }
        
        // Forcer le nettoyage du cache pour cette commande
        clean_post_cache($order_id);
    }
}



// 4. Endpoint AJAX simplifié sans script de restauration
add_action('wp_ajax_refresh_linked_orders_metabox', 'refresh_linked_orders_metabox_callback');
function refresh_linked_orders_metabox_callback() {
    check_ajax_referer('refresh_metabox_nonce', 'security');
    
    if (!isset($_POST['post_id'])) {
        wp_send_json_error('Missing post ID');
    }
    
    $post_id = intval($_POST['post_id']);
    $post = get_post($post_id);
    
    ob_start();
    cpt_linked_orders_table_callback($post);
    $metabox_content = ob_get_clean();
    
    // Plus de script de restauration - géré côté client
    wp_send_json_success($metabox_content);
}