<?php
/**
 * Snippet ID: 53
 * Name: METABOX PLANNING CALENDRIER détaillé sur CPT ACTION DE FORMATION
 * Description: 
 * @active true
 */

add_action('add_meta_boxes', 'add_action_formation_table_metabox');

function add_action_formation_table_metabox() {
    add_meta_box(
        'planning_table_metabox',
        'Planning et Fournisseurs  - dispo, statut - ',
        'render_planning_table_metabox',
        'action-de-formation',
        'normal',
        'high'
    );
}

function render_planning_table_metabox($post) {
    global $wpdb;
    $planning_data = get_post_meta($post->ID, 'fsbdd_planning', true);

    if (!is_array($planning_data)) {
        echo '<p>Aucun planning trouvé.</p>';
        return;
    }

    // Récupérer toutes les commandes liées à cette action de formation
    $cpt_id = $post->ID;
    $query = "
        SELECT DISTINCT oi.order_id
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim
            ON oi.order_item_id = oim.order_item_id
        WHERE oim.meta_key = 'fsbdd_relsessaction_cpt_produit'
        AND oim.meta_value = %s
    ";
    $order_ids = $wpdb->get_col($wpdb->prepare($query, $cpt_id));

    // Statuts valides pour calculer les inscrits
    $valid_statuses = ['confirme', 'certifreal', 'avenantvalide', 'avenantconv', 'facturefsc', 'facturesent', 'facturation', 'factureok'];

echo '<style>
    .planning-table {
        border-collapse: collapse;
        width: 100%;
        font-size: 13px;
        margin: 0;
        border: none; /* Remove the border */
    }
    .planning-table th, .planning-table td {
        border: 1px solid #fffff;
        padding: 8px;
        text-align: left;
        vertical-align: top;
    }
    .planning-table th {
        background-color: #d5ebff; /* Updated background color */
        color: #314150; /* Updated text color */
        font-weight: bold;
        font-size: 12px;
    }
    .planning-table tr:nth-child(odd) {
        background-color: #fffff;
    }
    .planning-table tr:nth-child(even) {
        background-color: #ebebeb;
    }

    /* Specific columns */
    .date-col {
        width: 7%;
        font-weight: bold;
    }
    .time-col {
        width: 10%;
        font-size: 11px;
    }
    .formateurs-col {
        width: 37%;
    }
    .fournisseurs-col {
        width: 37%;
    }
    .ut-col {
        width: 9%;
        text-align: center;
        font-weight: bold;
    }

    /* Styles for formateurs and fournisseurs */
    .person-item {
        margin: 2px 0;
        padding: 4px;
        border-radius: 4px;
        font-size: 11px;
        background: #f8f9fa;
        display: inline-block;
        margin-right: 8px;
        white-space: nowrap;
        border: 1px solid #e9ecef;
    }
    .person-item.fournisseur {
        background: #fff5f5;
        border: 1px solid #fecaca;
    }

    .person-name {
        font-weight: bold;
        color: #374151;
        margin-right: 4px;
    }
    .person-product {
        font-size: 10px;
        color: #6b7280;
        font-style: italic;
        margin-left: 4px;
    }

    /* Badges for periods - only the letter in color */
    .period-badge {
        display: inline-block;
        padding: 0;
        font-size: 11px;
        font-weight: 700;
        margin-left: 4px;
        cursor: help;
        transition: all 0.2s ease;
    }
    .period-matin {
        color: #9C27B0;
    }
    .period-aprem {
        color: #4338ca;
    }
    .period-journ {
        color: #17795B;
    }
    .period-autre {
        color: #6b7280;
    }

    /* Status badges - harmonious and less saturated colors */
    .status-badge {
        display: inline-block;
        padding: 2px 5px;
        border-radius: 4px;
        font-size: 9px;
        font-weight: 600;
        margin-left: 2px;
        cursor: help;
        transition: all 0.2s ease;
    }
    .status-reserve {
        background: #dcfce7;
        color: #14532d;
        border: 1px solid #bbf7d0;
    }
    .status-libere {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
    .status-option {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #bfdbfe;
    }
    .status-prebloque {
        background: #fef3c7;
        color: #a16207;
        border: 1px solid #fde68a;
    }
    .status-envoye {
        background: #f3e8ff;
        color: #6b21a8;
        border: 1px solid #e9d5ff;
    }
    .status-recu {
        background: #ccfbf1;
        color: #134e4a;
        border: 1px solid #99f6e4;
    }
    .status-emargement {
        background: #d1fae5;
        color: #064e3b;
        border: 1px solid #6ee7b7;
    }
    .status-autre {
        background: #f9fafb;
        color: #374151;
        border: 1px solid #e5e7eb;
    }

    /* Hover effect for badges */
    .period-badge:hover, .status-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .planning-ellipsis {
        text-align: center;
        font-style: italic;
        color: #666;
        background: #ecf0f1 !important;
    }
    .planning-summary {
        background-color: #34495e !important;
        color: white;
        font-weight: bold;
        text-align: center;
    }

    .legend-container {
        margin-top: 12px;
        padding: 12px;
        background: #f8f9fa;
        border-radius: 6px;
        font-size: 11px;
        text-align: center;
        border: 1px solid #e9ecef;
    }
    .legend-item {
        display: inline-block;
        margin-right: 12px;
        margin-bottom: 4px;
    }
    #planning_table_metabox .inside {
        padding: 0 !important;
        margin: 0 !important;
    }

    /* Improved tooltips */
    .tooltip-enhanced {
        position: relative;
    }

    .tooltip-enhanced:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: #1f2937;
        color: white;
        padding: 6px 8px;
        border-radius: 4px;
        font-size: 10px;
        white-space: nowrap;
        z-index: 1000;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    .tooltip-enhanced:hover::before {
        content: "";
        position: absolute;
        bottom: calc(100% - 6px);
        left: 50%;
        transform: translateX(-50%);
        border: 3px solid transparent;
        border-top-color: #1f2937;
        z-index: 1000;
    }

    /* Hide metabox arrows */
    #planning_table_metabox .handlediv {
        display: none !important;
    }

    /* Remove fine border */
    #planning_table_metabox {
        border: none !important;
    }

    /* Set the label color of the status filter to white */
    #status-filter-select {
        color: white !important;
    }
	
	    /* Metabox header styles */
    #planning_table_metabox .hndle {
        background-color: #314150 !important; /* Dark background */
        color: #fff !important; /* Light text */
				text-transform: uppercase !important;
    }

    #planning_table_metabox .postbox-header {
        padding-bottom: 0;
        margin-bottom: 0;
        background-color: #314150 !important; /* Dark background */
        color: #fff !important; /* Light text */
				text-transform: uppercase !important;
    }

    /* Other styles remain the same */
</style>';


    // Déterminer combien de lignes afficher
    $total_planning_days = count($planning_data);
    $show_all = $total_planning_days <= 10;
    
    echo '<table class="planning-table">';
    echo '<thead>
            <tr>
                <th class="date-col">Date</th>
                <th class="time-col">Horaires</th>
                <th class="formateurs-col">Formateurs</th>
                <th class="fournisseurs-col">Fournisseurs</th>
                <th class="ut-col">UT pratiques</th>
            </tr>
          </thead>';
    echo '<tbody>';

    if ($show_all) {
        // Afficher toutes les lignes normalement si <= 10
        $row_index = 0;
        foreach ($planning_data as $day) {
            echo render_planning_row($day, $row_index, $order_ids, $valid_statuses, $cpt_id);
            $row_index++;
        }
    } else {
        // Afficher seulement les 3 premières, ellipsis, et les 3 dernières
        $planning_keys = array_keys($planning_data);
        
        // 3 premières lignes
        for ($i = 0; $i < 3; $i++) {
            if (isset($planning_keys[$i])) {
                echo render_planning_row($planning_data[$planning_keys[$i]], $i, $order_ids, $valid_statuses, $cpt_id);
            }
        }
        
        // Ligne d'ellipsis avec résumé
        echo '<tr class="planning-ellipsis">
                <td colspan="5">... (' . ($total_planning_days - 6) . ' dates masquées) ...</td>
              </tr>';
        
        // 3 dernières lignes
        $start_index = max(3, $total_planning_days - 3);
        for ($i = $start_index; $i < $total_planning_days; $i++) {
            if (isset($planning_keys[$i])) {
                echo render_planning_row($planning_data[$planning_keys[$i]], $i, $order_ids, $valid_statuses, $cpt_id);
            }
        }
        
        // Ligne de résumé avec totaux
        $total_ut_pratiques_all = 0;
        
        foreach ($planning_data as $day) {
            $date = isset($day['fsbdd_planjour']) ? $day['fsbdd_planjour'] : '';
            $date_meta_format = str_replace('.', '/', $date);
            
            if (!empty($order_ids)) {
                foreach ($order_ids as $order_id) {
                    $order = wc_get_order($order_id);
                    if (!$order) continue;
                    
                    $convoc_meta_key = 'fsbdd_convoc_' . $date_meta_format;
                    $is_convoque = get_post_meta($order_id, $convoc_meta_key, true);
                    
                    if ($is_convoque == '1' && in_array($order->get_status(), $valid_statuses)) {
                        $effectif = intval(get_post_meta($order_id, 'fsbdd_effectif', true));
                        
                        // Récupérer ut_pratique depuis les line items
                        $ut_pratique = 0;
                        $items = $order->get_items();
                        foreach ($items as $item) {
                            $item_action_id = wc_get_order_item_meta($item->get_id(), 'fsbdd_relsessaction_cpt_produit', true);
                            if ($item_action_id == $cpt_id || empty($item_action_id)) {
                                $ut_pratique = floatval(wc_get_order_item_meta($item->get_id(), 'ut_pratique', true));
                                break;
                            }
                        }
                        
                        $total_ut_pratiques_all += ($effectif * $ut_pratique);
                    }
                }
            }
        }
        
        echo '<tr class="planning-summary">
                <td colspan="4">TOTAL (' . $total_planning_days . ' jours)</td>
                <td>' . number_format($total_ut_pratiques_all, 2) . '</td>
              </tr>';
    }

    echo '</tbody>';
    echo '</table>';
    
    // Ajouter la légende avec tooltips améliorés
    echo '<div class="legend-container">';
    echo '<span class="legend-item"><span class="period-badge period-matin tooltip-enhanced" data-tooltip="Disponible le matin">M</span> Matin</span>';
    echo '<span class="legend-item"><span class="period-badge period-aprem tooltip-enhanced" data-tooltip="Disponible l\'après-midi">A</span> Après-midi</span>';  
    echo '<span class="legend-item"><span class="period-badge period-journ tooltip-enhanced" data-tooltip="Disponible toute la journée">J</span> Journée</span>';
    echo '<span style="margin: 0 15px; color: #666; font-weight: bold;">|</span>';
    echo '<span class="legend-item"><span class="status-badge status-reserve tooltip-enhanced" data-tooltip="Date réservée">Rés</span> Réservé</span>';
    echo '<span class="legend-item"><span class="status-badge status-libere tooltip-enhanced" data-tooltip="Date libérée">Lib</span> Date libérée</span>';
    echo '<span class="legend-item"><span class="status-badge status-option tooltip-enhanced" data-tooltip="En option">Opt</span> Option</span>';
    echo '<span class="legend-item"><span class="status-badge status-prebloque tooltip-enhanced" data-tooltip="Pré-bloqué par Formation Stratégique">Pré</span> Pré bloqué FS</span>';
    echo '<span class="legend-item"><span class="status-badge status-envoye tooltip-enhanced" data-tooltip="Contrat envoyé">Env</span> Contrat envoyé</span>';
    echo '<span class="legend-item"><span class="status-badge status-recu tooltip-enhanced" data-tooltip="Contrat reçu">Reç</span> Contrat reçu</span>';
    echo '<span class="legend-item"><span class="status-badge status-emargement tooltip-enhanced" data-tooltip="Émargement validé">Ema</span> Emargement OK</span>';
    echo '<span class="legend-item"><span class="status-badge status-autre tooltip-enhanced" data-tooltip="Autre statut">Aut</span> Autres</span>';
    echo '</div>';
}

// Fonctions helper pour les tooltips (définies en dehors pour éviter les conflits)
function get_period_tooltip($dispo) {
    switch(strtolower($dispo)) {
        case 'matin': return 'Disponible le matin';
        case 'aprem': return 'Disponible l\'après-midi';
        case 'journ': return 'Disponible toute la journée';
        default: return 'Période : ' . $dispo;
    }
}

function get_status_tooltip($etat) {
    switch(strtolower(trim($etat))) {
        case 'réservé': return 'Date réservée';
        case 'date libérée': return 'Date libérée';
        case 'option': return 'En option';
        case 'pré bloqué fs': return 'Pré-bloqué par Formation Stratégique';
        case 'contrat envoyé': return 'Contrat envoyé';
        case 'contrat reçu': return 'Contrat reçu';
        case 'emargement ok': return 'Émargement validé';
        default: return 'Statut : ' . $etat;
    }
}

function render_planning_row($day, $row_index, $order_ids, $valid_statuses, $cpt_id) {
    $row_class = ($row_index % 2 === 0) ? 'row-even' : 'row-odd';

    $date = isset($day['fsbdd_planjour']) ? $day['fsbdd_planjour'] : '';
    // Conversion du format "22.01.2025" en "22/01/2025"
    $date_meta_format = str_replace('.', '/', $date);

    $morning_start = esc_html($day['fsbdd_plannmatin'] ?? '');
    $morning_end = esc_html($day['fsbdd_plannmatinfin'] ?? '');
    $afternoon_start = esc_html($day['fsbdd_plannam'] ?? '');
    $afternoon_end = esc_html($day['fsbdd_plannamfin'] ?? '');

    $horaires = $morning_start . ' - ' . $morning_end . '<br>' . $afternoon_start . ' - ' . $afternoon_end;

    // Formateurs
    $formateurs = '';
    if (!empty($day['fsbdd_gpformatr']) && is_array($day['fsbdd_gpformatr'])) {
        foreach ($day['fsbdd_gpformatr'] as $formateur) {
            $formateur_id = $formateur['fsbdd_user_formateurrel'] ?? '';
            $dispo = $formateur['fsbdd_dispjourform'] ?? '';
            $etat = $formateur['fsbdd_okformatr'] ?? '';
            $role = $formateur['fsbdd_roleformateur'] ?? '';

            if (!empty($formateur_id)) {
                $formateur_title = get_the_title($formateur_id);
                
                // Limiter le nom à 15 caractères
                if (strlen($formateur_title) > 15) {
                    $formateur_title_display = substr($formateur_title, 0, 15) . '...';
                } else {
                    $formateur_title_display = $formateur_title;
                }
                
                // Badge période
                $period_class = '';
                $period_text = '';
                switch(strtolower($dispo)) {
                    case 'matin': $period_class = 'period-matin'; $period_text = 'M'; break;
                    case 'aprem': $period_class = 'period-aprem'; $period_text = 'A'; break;
                    case 'journ': $period_class = 'period-journ'; $period_text = 'J'; break;
                    default: $period_class = 'period-autre'; $period_text = substr($dispo, 0, 1); break;
                }
                
                // Badge statut avec tous les cas possibles
                $status_class = 'status-autre';
                $status_text = 'Aut';
                
                switch(strtolower(trim($etat))) {
                    case 'réservé':
                        $status_class = 'status-reserve';
                        $status_text = 'Rés';
                        break;
                    case 'date libérée':
                        $status_class = 'status-libere';
                        $status_text = 'Lib';
                        break;
                    case 'option':
                        $status_class = 'status-option';
                        $status_text = 'Opt';
                        break;
                    case 'pré bloqué fs':
                        $status_class = 'status-prebloque';
                        $status_text = 'Pré';
                        break;
                    case 'contrat envoyé':
                        $status_class = 'status-envoye';
                        $status_text = 'Env';
                        break;
                    case 'contrat reçu':
                        $status_class = 'status-recu';
                        $status_text = 'Reç';
                        break;
                    case 'emargement ok':
                        $status_class = 'status-emargement';
                        $status_text = 'Ema';
                        break;
                    default:
                        $status_text = substr($etat, 0, 3);
                        break;
                }
                
                $role_display = '';
                if (!empty($role)) {
                    // Limiter le rôle à 15 caractères
                    $role_short = strlen($role) > 15 ? substr($role, 0, 15) . '...' : $role;
                    $role_display = '<span class="person-product">(' . esc_html($role_short) . ')</span>';
                }
                
                $formateurs .= '<div class="person-item">
                                    <span class="person-name">' . esc_html($formateur_title_display) . '</span>
                                    ' . $role_display . '
                                    <span class="period-badge ' . $period_class . ' tooltip-enhanced" data-tooltip="' . esc_attr(get_period_tooltip($dispo)) . '">' . $period_text . '</span>
                                    <span class="status-badge ' . $status_class . ' tooltip-enhanced" data-tooltip="' . esc_attr(get_status_tooltip($etat)) . '">' . esc_html($status_text) . '</span>
                                </div>';
            }
        }
    }

    // Fournisseurs et salles
    $fournisseurs = '';
    if (!empty($day['fournisseur_salle']) && is_array($day['fournisseur_salle'])) {
        foreach ($day['fournisseur_salle'] as $fournisseur) {
            $fournisseur_id = $fournisseur['fsbdd_user_foursalle'] ?? '';
            $dispo = $fournisseur['fsbdd_dispjourform'] ?? '';
            $etat = $fournisseur['fsbdd_okformatr'] ?? '';
            $product_name = $fournisseur['fsbdd_selected_product_name'] ?? '';

            if (!empty($fournisseur_id)) {
                $fournisseur_title = get_the_title($fournisseur_id);
                
                // Limiter le nom du fournisseur à 12 caractères
                if (strlen($fournisseur_title) > 12) {
                    $fournisseur_title_display = substr($fournisseur_title, 0, 12) . '...';
                } else {
                    $fournisseur_title_display = $fournisseur_title;
                }
                
                // Badge période
                $period_class = '';
                $period_text = '';
                switch(strtolower($dispo)) {
                    case 'matin': $period_class = 'period-matin'; $period_text = 'M'; break;
                    case 'aprem': $period_class = 'period-aprem'; $period_text = 'A'; break;
                    case 'journ': $period_class = 'period-journ'; $period_text = 'J'; break;
                    default: $period_class = 'period-autre'; $period_text = substr($dispo, 0, 1); break;
                }
                
                // Badge statut avec tous les cas possibles
                $status_class = 'status-autre';
                $status_text = 'Aut';
                
                switch(strtolower(trim($etat))) {
                    case 'réservé':
                        $status_class = 'status-reserve';
                        $status_text = 'Rés';
                        break;
                    case 'date libérée':
                        $status_class = 'status-libere';
                        $status_text = 'Lib';
                        break;
                    case 'option':
                        $status_class = 'status-option';
                        $status_text = 'Opt';
                        break;
                    case 'pré bloqué fs':
                        $status_class = 'status-prebloque';
                        $status_text = 'Pré';
                        break;
                    case 'contrat envoyé':
                        $status_class = 'status-envoye';
                        $status_text = 'Env';
                        break;
                    case 'contrat reçu':
                        $status_class = 'status-recu';
                        $status_text = 'Reç';
                        break;
                    case 'emargement ok':
                        $status_class = 'status-emargement';
                        $status_text = 'Ema';
                        break;
                    default:
                        $status_text = substr($etat, 0, 3);
                        break;
                }
                
                $product_display = '';
                if (!empty($product_name)) {
                    // Limiter le nom du produit à 15 caractères
                    $product_short = strlen($product_name) > 15 ? substr($product_name, 0, 15) . '...' : $product_name;
                    $product_display = '<span class="person-product">(' . esc_html($product_short) . ')</span>';
                }
                
                $fournisseurs .= '<div class="person-item fournisseur">
                                    <span class="person-name">' . esc_html($fournisseur_title_display) . '</span>
                                    ' . $product_display . '
                                    <span class="period-badge ' . $period_class . ' tooltip-enhanced" data-tooltip="' . esc_attr(get_period_tooltip($dispo)) . '">' . $period_text . '</span>
                                    <span class="status-badge ' . $status_class . ' tooltip-enhanced" data-tooltip="' . esc_attr(get_status_tooltip($etat)) . '">' . esc_html($status_text) . '</span>
                                </div>';
            }
        }
    }

    // Calcul des UT pratiques pour ce jour
    $total_ut_pratiques = 0;

    if (!empty($order_ids)) {
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) {
                continue;
            }

            $convoc_meta_key = 'fsbdd_convoc_' . $date_meta_format;
            $is_convoque = get_post_meta($order_id, $convoc_meta_key, true);

            if ($is_convoque == '1' && in_array($order->get_status(), $valid_statuses)) {
                $effectif = intval(get_post_meta($order_id, 'fsbdd_effectif', true));
                
                // Récupérer ut_pratique depuis les line items
                $ut_pratique = 0;
                $items = $order->get_items();
                foreach ($items as $item) {
                    $item_action_id = wc_get_order_item_meta($item->get_id(), 'fsbdd_relsessaction_cpt_produit', true);
                    if ($item_action_id == $cpt_id || empty($item_action_id)) {
                        $ut_pratique = floatval(wc_get_order_item_meta($item->get_id(), 'ut_pratique', true));
                        break; // Un seul produit par commande
                    }
                }
                
                $total_ut_pratiques += ($effectif * $ut_pratique);
            }
        }
    }

    return '<tr>
            <td class="date-col">' . esc_html($date) . '</td>
            <td class="time-col">' . $horaires . '</td>
            <td class="formateurs-col">' . $formateurs . '</td>
            <td class="fournisseurs-col">' . $fournisseurs . '</td>
            <td class="ut-col">' . number_format($total_ut_pratiques, 2) . '</td>
          </tr>';
}