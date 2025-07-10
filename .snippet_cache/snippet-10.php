<?php
/**
 * Snippet ID: 10
 * Name: COLONNES AVANCEES POUR VUES ADMIN COLUMN PRO
 * Description: 
 * @active true
 */

/**
 * ACTIVER RECHERCHE ET FILTRES POUR COLONNE PRODUITS REL ACTIONS DE FORMATION
*/
add_filter( 'posts_search', function( $search, $query ) {
    if ( is_admin() && $query->is_search() && $query->get( 'post_type' ) === 'action-de-formation' ) {
        global $wpdb;

        // Récupérer le terme recherché
        $search_term = esc_sql( $query->get( 's' ) );

        if ( ! empty( $search_term ) ) {
            // Ajoute une recherche dans les titres des produits liés via le champ fsbdd_relsessproduit
            $search .= $wpdb->prepare(
                " OR EXISTS (
                    SELECT 1
                    FROM {$wpdb->postmeta} pm
                    INNER JOIN {$wpdb->posts} p ON pm.meta_value = p.ID
                    WHERE pm.post_id = {$wpdb->posts}.ID
                    AND pm.meta_key = 'fsbdd_relsessproduit'
                    AND p.post_title LIKE %s
                )",
                '%' . $search_term . '%'
            );
        }
    }
    return $search;
}, 10, 2 );

/**
 * CHANGER COULEUR ADMIN POST COLONNE PAR STATUT
*/

add_action('admin_footer','posts_status_color');
function posts_status_color(){
?>
<style>
 .status-draft { background: #ffffe0 !important;}
 .status-future { background: #E9F2D3 !important;}
 .status-publish {/* no background keep WordPress colors */}
 .status-pending { background: #D3E4ED !important;}
 .status-private { background: #FFECE6 !important;}
 .status-sticky { background: #F9F9F9 !important;}
 .status-fiche_bloquee { background: #ffcccc !important;}
 .status-fiche_demande_resp { background: #ffd9b3 !important;}
 .post-password-required { background: #F7FCFE !important;}
</style>
<?php	
}
							 
							 // COLONNE GROUPE CHAMPS CLONABLE PLANNING DATES HEURES AVEC FORMATEURS
add_filter('ac/column/value', 'acp_modify_planning_column_content', 10, 3);

function acp_modify_planning_column_content($value, $id, $column) {
    if ($column->get_type() == 'column-meta' && $column->get_meta_key() == 'planning_placeholder') { 
        $order = wc_get_order($id);
        $items = $order->get_items();

        if (count($items) == 1) {
            $item = array_shift($items);
            $product_id = $item->get_product_id();
            $planning = get_post_meta($product_id, 'fsbdd_planning', true);
            
            if ($planning && is_array($planning)) {
                $output = '<ul>';
                foreach ($planning as $day) {
                    // Dates et heures
                    $matin_start = isset($day['fsbdd_plannmatin']) ? date('H\hi', strtotime($day['fsbdd_plannmatin'])) : '';
                    $matin_end = isset($day['fsbdd_plannmatinfin']) ? date('H\hi', strtotime($day['fsbdd_plannmatinfin'])) : '';
                    $am_start = isset($day['fsbdd_plannam']) ? date('H\hi', strtotime($day['fsbdd_plannam'])) : '';
                    $am_end = isset($day['fsbdd_plannamfin']) ? date('H\hi', strtotime($day['fsbdd_plannamfin'])) : '';
                    $date = date('d/m/Y', strtotime($day['fsbdd_plannmatin'])); // Assuming one of them always has a date
                    
                    // Vérifier si "Recyclage" est cochée pour cette journée spécifique
                    $is_recyclage = isset($day['fsbdd_planrecycl']) && $day['fsbdd_planrecycl'] == '1'; // Assuming '1' is the value when checked
                    $date_label = $is_recyclage ? $date . ' (R)' : $date;
                    
                    // Informations des formateurs pour chaque jour
                    $formateurs_info = '';
                    if (isset($day['fsbdd_gpformatr']) && is_array($day['fsbdd_gpformatr'])) {
                        foreach ($day['fsbdd_gpformatr'] as $formateur) {
                            if (!empty($formateur['fsbdd_user_formateurrel'])) {
                                $titre_formateur = get_the_title($formateur['fsbdd_user_formateurrel']);
                                $dispo = isset($formateur['fsbdd_dispjourform']) ? $formateur['fsbdd_dispjourform'] : '';
                                $etat = isset($formateur['fsbdd_okformatr']) ? $formateur['fsbdd_okformatr'] : '';
                                $formateurs_info .= sprintf('%s, %s, %s<br>', esc_html($titre_formateur), esc_html($dispo), esc_html($etat));
                            }
                        }
                    }

                    $output .= sprintf('<li>%s : %s > %s / %s > %s<br>%s</li>', $date_label, $matin_start, $matin_end, $am_start, $am_end, $formateurs_info);
                }
                $output .= '</ul>';
                return $output;
            }
        }
    }
    return $value;
}


// MEME COLONNE MAIS POUR LES PRODUITS, FORMATAGE DES RESULTATS DU PLANNING
add_filter('ac/column/value', 'acp_modify_product_planning_column_content', 10, 3);

function acp_modify_product_planning_column_content($value, $id, $column) {
    if ($column->get_type() == 'column-meta' && $column->get_meta_key() == 'fsbdd_planning') { 
        $product_id = $id;
        $planning = get_post_meta($product_id, 'fsbdd_planning', true);
        
        if ($planning && is_array($planning)) {
            $output = '';
            foreach ($planning as $day) {
                // Vérifier si "Recyclage" est cochée pour cette journée spécifique
                $is_recyclage = isset($day['fsbdd_planrecycl']) && $day['fsbdd_planrecycl'] == '1'; // Assuming '1' is the value when checked
                
                // Dates et heures
                $matin_start = isset($day['fsbdd_plannmatin']) ? date('H\hi', strtotime($day['fsbdd_plannmatin'])) : '';
                $matin_end = isset($day['fsbdd_plannmatinfin']) ? date('H\hi', strtotime($day['fsbdd_plannmatinfin'])) : '';
                $am_start = isset($day['fsbdd_plannam']) ? date('H\hi', strtotime($day['fsbdd_plannam'])) : '';
                $am_end = isset($day['fsbdd_plannamfin']) ? date('H\hi', strtotime($day['fsbdd_plannamfin'])) : '';
                $date = date('d/m/Y', strtotime($day['fsbdd_plannmatin']));
                
                // Ajouter "(R)" si applicable
                $date_label = $is_recyclage ? $date . ' (R)' : $date;

                // Format de l'affichage
                $output .= sprintf('%s : %s > %s / %s > %s<br>', $date_label, $matin_start, $matin_end, $am_start, $am_end);

                // Informations des formateurs pour chaque jour
                if (isset($day['fsbdd_gpformatr']) && is_array($day['fsbdd_gpformatr'])) {
                    foreach ($day['fsbdd_gpformatr'] as $formateur) {
                        if (!empty($formateur['fsbdd_user_formateurrel'])) {
                            $titre_formateur = get_the_title($formateur['fsbdd_user_formateurrel']);
                            $dispo = isset($formateur['fsbdd_dispjourform']) ? $formateur['fsbdd_dispjourform'] : '';
                            $etat = isset($formateur['fsbdd_okformatr']) ? $formateur['fsbdd_okformatr'] : '';
                            $output .= sprintf('%s, %s, %s<br>', esc_html($titre_formateur), esc_html($dispo), esc_html($etat));
                        }
                    }
                }
            }
            return $output;
        }
    }
    return $value;
}



// GROUPE CHAMPS NOM STAGIAIRES COMBINES EN UNE SEULE COLONNE

add_filter('ac/column/value', 'acp_modify_combined_stagiaires_column_content', 10, 3);

function acp_modify_combined_stagiaires_column_content($value, $id, $column) {
    if ($column->get_type() == 'column-meta' && $column->get_meta_key() == 'combined_stagiaires_placeholder') { // Assurez-vous de cibler la colonne correcte
        
        // Liste des clés des champs noms
        $nom_keys = [
            'fsbddtext_nomstagiaires',
            'fsbddtext_nomstagiaires2',
            'fsbddtext_nomstagiaires3',
            'fsbddtext_nomstagiaires4',
            'fsbddtext_nomstagiaires5',
            'fsbddtext_nomstagiaires6',
            'fsbddtext_nomstagiaires7',
            'fsbddtext_nomstagiaires8',
            'fsbddtext_nomstagiaires9',
            'fsbddtext_nomstagiaires10',
			'fsbddtext_nomstagiaires11',
			'fsbddtext_nomstagiaires12'
        ];

        // Liste des clés des champs prénoms
        $prenom_keys = [
            'fsbddtext_prenomstagiaires',
            'fsbddtext_prenomstagiaires2',
            'fsbddtext_prenomstagiaires3',
            'fsbddtext_prenomstagiaires4',
            'fsbddtext_prenomstagiaires5',
            'fsbddtext_prenomstagiaires6',
            'fsbddtext_prenomstagiaires7',
            'fsbddtext_prenomstagiaires8',
            'fsbddtext_prenomstagiaires9',
            'fsbddtext_prenomstagiaires10',
			'fsbddtext_prenomstagiaires11',
			'fsbddtext_prenomstagiaires12'
        ];

        $stagiaires = [];

        for ($i = 0; $i < count($nom_keys); $i++) {
            $nom = get_post_meta($id, $nom_keys[$i], true);
            $prenom = get_post_meta($id, $prenom_keys[$i], true);

            if (!empty($nom)) {
                $fullname = $nom;
                if (!empty($prenom)) {
                    $fullname .= ' ' . $prenom;
                }
                $stagiaires[] = $fullname;
            }
        }

        return implode(', ', $stagiaires);
    }
    return $value;
}



// Colonne pour l'état "Contrat envoyé" avec date des changements d'état
add_filter('ac/column/value', 'acp_modify_contrat_envoye_column_content', 10, 3);

function acp_modify_contrat_envoye_column_content($value, $id, $column) {
    if ($column->get_type() == 'column-meta' && $column->get_meta_key() == 'contrat_envoye_placeholder') {
        $order = wc_get_order($id);
        $items = $order->get_items();

        if (count($items) == 1) {
            $item = array_shift($items);
            $product_id = $item->get_product_id();
            $planning = get_post_meta($product_id, 'fsbdd_planning', true);

            if ($planning && is_array($planning)) {
                $output = '<ul>';
                foreach ($planning as $day) {
                    $date_jour = date('d/m/Y', strtotime($day['fsbdd_plannmatin'])); // Assuming one of them always has a date
                    if (isset($day['fsbdd_gpformatr']) && is_array($day['fsbdd_gpformatr'])) {
                        foreach ($day['fsbdd_gpformatr'] as $formateur) {
                            if (!empty($formateur['fsbdd_user_formateurrel'])) {
                                $titre_formateur = get_the_title($formateur['fsbdd_user_formateurrel']);
                                $etat = isset($formateur['fsbdd_okformatr']) ? $formateur['fsbdd_okformatr'] : '';
                                $meta_key = 'date_' . sanitize_title('Contrat envoyé') . '_' . $formateur['fsbdd_user_formateurrel'];
                                $date = get_post_meta($product_id, $meta_key, true);
                                $date_text = $date ? $date : 'TODO';

                                // Ajout de la date du planning pour distinguer les différents états
                                $output .= sprintf('<li>%s (%s): %s</li>', esc_html($titre_formateur), esc_html($date_jour), esc_html($date_text));
                            }
                        }
                    }
                }
                $output .= '</ul>';
                return $output;
            }
        }
    }
    return $value;
}

// Colonne pour l'état "Contrat reçu" avec date des changements d'état
add_filter('ac/column/value', 'acp_modify_contrat_recu_column_content', 10, 3);

function acp_modify_contrat_recu_column_content($value, $id, $column) {
    if ($column->get_type() == 'column-meta' && $column->get_meta_key() == 'contrat_recu_placeholder') {
        $order = wc_get_order($id);
        $items = $order->get_items();

        if (count($items) == 1) {
            $item = array_shift($items);
            $product_id = $item->get_product_id();
            $planning = get_post_meta($product_id, 'fsbdd_planning', true);

            if ($planning && is_array($planning)) {
                $output = '<ul>';
                foreach ($planning as $day) {
                    $date_jour = date('d/m/Y', strtotime($day['fsbdd_plannmatin'])); // Assuming one of them always has a date
                    if (isset($day['fsbdd_gpformatr']) && is_array($day['fsbdd_gpformatr'])) {
                        foreach ($day['fsbdd_gpformatr'] as $formateur) {
                            if (!empty($formateur['fsbdd_user_formateurrel'])) {
                                $titre_formateur = get_the_title($formateur['fsbdd_user_formateurrel']);
                                $meta_key = 'date_' . sanitize_title('Contrat reçu') . '_' . $formateur['fsbdd_user_formateurrel'];
                                $date = get_post_meta($product_id, $meta_key, true);
                                $date_text = $date ? $date : 'TODO';

                                $output .= sprintf('<li>%s (%s): %s</li>', esc_html($titre_formateur), esc_html($date_jour), esc_html($date_text));
                            }
                        }
                    }
                }
                $output .= '</ul>';
                return $output;
            }
        }
    }
    return $value;
}

// Colonne pour l'état "emargement OK" avec date des changements d'état
add_filter('ac/column/value', 'acp_modify_emargement_ok_column_content', 10, 3);

function acp_modify_emargement_ok_column_content($value, $id, $column) {
    if ($column->get_type() == 'column-meta' && $column->get_meta_key() == 'emargement_ok_placeholder') {
        $order = wc_get_order($id);
        $items = $order->get_items();

        if (count($items) == 1) {
            $item = array_shift($items);
            $product_id = $item->get_product_id();
            $planning = get_post_meta($product_id, 'fsbdd_planning', true);

            if ($planning && is_array($planning)) {
                $output = '<ul>';
                foreach ($planning as $day) {
                    $date_jour = date('d/m/Y', strtotime($day['fsbdd_plannmatin'])); // Assuming one of them always has a date
                    if (isset($day['fsbdd_gpformatr']) && is_array($day['fsbdd_gpformatr'])) {
                        foreach ($day['fsbdd_gpformatr'] as $formateur) {
                            if (!empty($formateur['fsbdd_user_formateurrel'])) {
                                $titre_formateur = get_the_title($formateur['fsbdd_user_formateurrel']);
                                $meta_key = 'date_' . sanitize_title('Emargement OK') . '_' . $formateur['fsbdd_user_formateurrel'];
                                $date = get_post_meta($product_id, $meta_key, true);
                                $date_text = $date ? $date : 'TODO';

                                $output .= sprintf('<li>%s (%s): %s</li>', esc_html($titre_formateur), esc_html($date_jour), esc_html($date_text));
                            }
                        }
                    }
                }
                $output .= '</ul>';
                return $output;
            }
        }
    }
    return $value;
}


// COLONNE POUR LE CHAMP fsbdd_inter_numero
add_filter('ac/column/value', 'acp_modify_inter_numero_column_content', 10, 3);

function acp_modify_inter_numero_column_content($value, $id, $column) {
    if ($column->get_type() == 'column-meta' && $column->get_meta_key() == 'inter_numero_placeholder') { // Assurez-vous de cibler la colonne correcte
        $order = wc_get_order($id);
        $items = $order->get_items();

        if (count($items) == 1) {
            $item = array_shift($items);
            $product_id = $item->get_product_id();
            $inter_numero = get_post_meta($product_id, 'fsbdd_inter_numero', true);
            
            return $inter_numero ?: 'N/A'; // Retourne le numéro ou 'N/A' si vide
        }
    }
    return $value;
}



// COLONNE POUR LE CHAMP RELATION SESSION ACTION - ACTION NUM

add_filter('ac/column/value', 'acp_modify_actionum_column_content', 10, 3);
function acp_modify_actionum_column_content($value, $id, $column) {
    if ($column->get_type() == 'column-meta' && $column->get_meta_key() == 'actionum_placeholder') { // Adaptez le placeholder selon votre configuration
        $order = wc_get_order($id);
        if (!$order) return $value;
        
        $items = $order->get_items();
        if (count($items) > 0) {
            foreach ($items as $item) {
                // Récupérer directement la métadonnée de l'item
                $actionum = $item->get_meta('fsbdd_actionum');
                if (!empty($actionum)) {
                    return $actionum; // Retourne la première valeur non vide trouvée
                }
            }
        }
        return 'N/A';
    }
    return $value;
}




// COLONNE POUR LE CHAMP TYPE DE SESSION INTER INTRA
add_filter('ac/column/value', 'acp_modify_typesession_column_content', 10, 3);
function acp_modify_typesession_column_content($value, $id, $column) {
    if ($column->get_type() == 'column-meta' && $column->get_meta_key() == 'typesession_placeholder') {
        $order = wc_get_order($id);
        if (!$order) return $value;
        
        $items = $order->get_items();
        if (count($items) == 1) {
            $item = array_shift($items);
            $product_id = $item->get_product_id();
            $typesession = get_post_meta($product_id, 'fsbdd_typesession', true);
            
            // Convertir la valeur numérique en libellé
            $typesession_labels = [
                '1' => 'INTER',
                '2' => 'INTER à définir',
                '3' => 'INTRA'
            ];
            
            if (isset($typesession_labels[$typesession])) {
                return $typesession_labels[$typesession];
            }
            
            return $typesession ?: 'N/A'; // Retourne la valeur brute si pas de correspondance
        }
    }
    return $value;
}


// COLONNE CHAMP LISTE CLIENTS AYANT ACHETÉ CE PRODUIT POUR ADMIN COLUMNS PRODUITS
// Afficher la liste des clients, le statut des commandes, le niveau, et le nombre d'effectifs par commande pour un produit
add_filter('ac/column/value', 'acp_modify_product_orders_and_effectif_column_content', 10, 3);

function acp_modify_product_orders_and_effectif_column_content($value, $id, $column) {
    if ($column->get_type() !== 'column-meta' || $column->get_meta_key() !== 'product_orders_placeholder') {
        return $value;
    }

    global $wpdb;
    $items_output = [];

    // Récupérer les IDs de commande contenant ce produit, triées par statut spécifique puis par date de commande
    $order_item_ids = $wpdb->get_col($wpdb->prepare("
        SELECT order_item.order_id
        FROM {$wpdb->prefix}woocommerce_order_items as order_item
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_item.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->prefix}posts as posts ON order_item.order_id = posts.ID
        WHERE order_item_meta.meta_key = '_product_id' AND order_item_meta.meta_value = %d
        ORDER BY 
            CASE WHEN posts.post_status IN ('wc-confirme', 'wc-factureok', 'wc-certifreal', 'wc-avenantvalide', 'wc-avenantconv', 'wc-facturefsc', 'wc-facturesent', 'wc-facturation', 'wc-factureok') THEN 0 ELSE 1 END,
            posts.post_date DESC
    ", $id));

    foreach ($order_item_ids as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) continue;

        $niveau_class = get_niveau_class(get_post_meta($order_id, 'fsbdd_affaireniveau', true));
        $customer_name = acp_get_customer_name($order);
        $status_name = wc_get_order_status_name($order->get_status());
        $status_class = get_status_class($order->get_status());
        $effectif = get_post_meta($order_id, 'fsbdd_effectif', true);

        $items_output[] = sprintf(
            '<li class="%s %s"><a href="%s" target="_blank">#%s</a>: <a href="%s" target="_blank">%s</a> - %s - %s pers</li>',
            $niveau_class, $status_class, esc_url(admin_url('post.php?action=edit&post=' . $order_id)), 
            $order_id, esc_url(get_edit_user_link($order->get_customer_id())), 
            esc_html($customer_name), esc_html($status_name), 
            !empty($effectif) ? esc_html($effectif) : '0'
        );
    }

    return !empty($items_output) ? '<ul style="margin: 0; padding: 0; list-style: none;">' . implode('', $items_output) . '</ul>' : 'N/A';
}

function acp_get_customer_name($order) {
    $company = get_user_meta($order->get_customer_id(), 'billing_company', true);
    $first_name = get_user_meta($order->get_customer_id(), 'billing_first_name', true);
    $last_name = get_user_meta($order->get_customer_id(), 'billing_last_name', true);
    return (!empty($company) && strtolower($company) != 'pas de société') ? $company : trim($first_name . ' ' . $last_name);
}
							 
							 // COLONNE inscrits noms prenoms stagiaires par commande pour tableau produits admin columns
// Afficher la liste des stagiaires par commande pour un produit, triée par statut spécifique puis par date de commande
add_filter('ac/column/value', 'acp_modify_product_orders_stagiaires_column_content', 10, 3);

function acp_modify_product_orders_stagiaires_column_content($value, $id, $column) {
    if ($column->get_type() == 'column-meta' && $column->get_meta_key() == 'product_orders_stagiaires_placeholder') {
        global $wpdb;

        // Récupérer les IDs de commandes contenant ce produit, triées par statut spécifique puis par date de commande
        $order_ids = $wpdb->get_col($wpdb->prepare("
            SELECT items.order_id
            FROM {$wpdb->prefix}woocommerce_order_itemmeta meta
            INNER JOIN {$wpdb->prefix}woocommerce_order_items items ON meta.order_item_id = items.order_item_id
            INNER JOIN {$wpdb->prefix}posts p ON items.order_id = p.ID
            WHERE meta.meta_key = '_product_id' AND meta.meta_value = %d
            GROUP BY items.order_id
            ORDER BY 
                CASE WHEN p.post_status IN ('wc-confirme', 'wc-factureok', 'wc-certifreal', 'wc-avenantvalide', 'wc-avenantconv', 'wc-facturefsc', 'wc-facturesent', 'wc-facturation', 'wc-factureok') THEN 0 ELSE 1 END, 
                p.post_date DESC
        ", $id));

        $output = '<ul style="margin: 0; padding: 0; list-style: none;">';
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) continue;

            $stagiaires_info = [];
            for ($i = 1; $i <= 10; $i++) {
                $nom = get_post_meta($order_id, "fsbddtext_nomstagiaires" . ($i > 1 ? $i : '') , true);
                $prenom = get_post_meta($order_id, "fsbddtext_prenomstagiaires" . ($i > 1 ? $i : ''), true);

                if (!empty($nom) || !empty($prenom)) {
                    $stagiaires_info[] = trim("$nom $prenom");
                }
            }

            if (!empty($stagiaires_info)) {
                $order_edit_url = esc_url(admin_url('post.php?action=edit&post=' . $order_id));
                $stagiaires_list = implode(', ', $stagiaires_info);
                $output .= sprintf('<li><a href="%s" target="_blank">#%s</a>: %s</li>', $order_edit_url, $order_id, $stagiaires_list);
            }
        }
        $output .= '</ul>';

        return !empty($output) ? $output : 'N/A';
    }
    return $value;
}


// colonne Afficher les variations commandées pour chaque produit, simplifiées et triées par statut et date, dans une colonne dédiée
add_filter('ac/column/value', 'acp_display_ordered_product_variations_column_simple', 10, 3);

function acp_display_ordered_product_variations_column_simple($value, $id, $column) {
    if ($column->get_type() !== 'column-meta' || $column->get_meta_key() !== 'ordered_product_variations_placeholder') {
        return $value;
    }

    global $wpdb;
    $variations_output = [];

    // Récupérer les IDs de variation et les dates de commande des variations commandées pour le produit spécifié, triées par statut spécifique puis par date
    $variation_orders = $wpdb->get_results($wpdb->prepare("
        SELECT oim.meta_value AS variation_id, p.post_date
        FROM {$wpdb->prefix}woocommerce_order_itemmeta oim
        INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oim.order_item_id = oi.order_item_id
        INNER JOIN {$wpdb->prefix}posts p ON oi.order_id = p.ID
        WHERE oim.meta_key = '_variation_id'
        AND oi.order_item_type = 'line_item'
        AND EXISTS (
            SELECT 1 FROM {$wpdb->prefix}woocommerce_order_itemmeta oim2
            WHERE oim2.order_item_id = oi.order_item_id
            AND oim2.meta_key = '_product_id' AND oim2.meta_value = %d
        )
        ORDER BY 
            CASE WHEN p.post_status IN ('wc-confirme', 'wc-factureok', 'wc-certifreal', 'wc-avenantvalide', 'wc-avenantconv', 'wc-facturefsc', 'wc-facturesent', 'wc-facturation', 'wc-factureok') THEN 0 ELSE 1 END, 
            p.post_date DESC
    ", $id), OBJECT);

    foreach ($variation_orders as $variation_order) {
        $variation_id = $variation_order->variation_id;
        if (!$variation_id) continue; // Ignorer si aucune variation n'est trouvée

        $variation = wc_get_product($variation_id);
        if ($variation) {
            $attributes = $variation->get_attributes();
            $variation_details = [];
            foreach ($attributes as $attr => $value) {
                $variation_details[] = $value; // Obtenir uniquement la valeur de l'attribut sans l'étiquette
            }
            $variations_output[] = implode(', ', $variation_details); // Joindre les détails de la variation
        }
    }

    // Éliminer les doublons
    $variations_output = array_unique($variations_output);
    if (!empty($variations_output)) {
        return '<ul style="list-style: none; padding: 0; margin: 0;">' . implode('<li>', $variations_output) . '</ul>';
    }

    return 'Aucune variation commandée'; // Message par défaut si aucune variation commandée n'est trouvée
}




// Colonne quantité totale commandée pour chaque variation de produit dans Admin Columns page variations produits
add_filter('ac/column/value', 'acp_modify_product_variation_quantity_column_content', 10, 3);

function acp_modify_product_variation_quantity_column_content($value, $id, $column) {
    if ($column->get_type() == 'column-meta' && $column->get_meta_key() == 'variation_orders_quantity_placeholder') {
        global $wpdb;

        // Récupérer les IDs des éléments de commande contenant cette variation de produit
        $order_item_ids = $wpdb->get_col($wpdb->prepare("
            SELECT order_item_id
            FROM {$wpdb->prefix}woocommerce_order_itemmeta
            WHERE meta_key = '_variation_id' AND meta_value = %d
        ", $id));

        $total_quantity = 0;

        // Parcourir chaque ID d'élément de commande pour additionner les quantités
        foreach ($order_item_ids as $item_id) {
            $quantity = $wpdb->get_var($wpdb->prepare("
                SELECT meta_value
                FROM {$wpdb->prefix}woocommerce_order_itemmeta
                WHERE order_item_id = %d AND meta_key = '_qty'
            ", $item_id));

            $total_quantity += intval($quantity);
        }

        // Retourne la quantité totale, ou '0' si aucune quantité n'est trouvée
        return $total_quantity ?: '0';
    }
    return $value; // Retourne la valeur originale si la condition de la colonne n'est pas remplie
}