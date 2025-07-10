<?php
/**
 * Snippet ID: 200
 * Name: PILOTAGE PAR SESSIONS tableau global
 * Description: * Active: true
 * @active true
 */

/**
 * TABLEAU PAR SESSION - Dashboard admin
 * Affiche un tableau centré sur les sessions avec les commandes associées
 */

// Ne pas exécuter directement
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajoute le tableau des sessions sur une page dédiée en admin
 */
add_action('admin_menu', 'fsbdd_add_sessions_table_page');

function fsbdd_sessions_user_has_required_role() {
    $user = wp_get_current_user();
    $allowed_roles = ['administrator', 'referent'];
    foreach ($user->roles as $role) {
        if (in_array($role, $allowed_roles)) {
            return true;
        }
    }
    return false;
}

function fsbdd_add_sessions_table_page() {
    add_submenu_page(
        'edit.php?post_type=action-de-formation',
        'Tableau par Sessions',
        'Tableau Sessions',
        'read',
        'sessions-table',
        'fsbdd_render_sessions_table_page'
    );
}

function fsbdd_render_sessions_table_page() {
    // Vérifier les permissions
    if (!fsbdd_sessions_user_has_required_role()) {
        wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.'));
    }

    echo '<div class="wrap">';
    echo '<h1>Tableau par Sessions</h1>';
    
    // Afficher le tableau
    fsbdd_display_sessions_table();
    
    echo '</div>';
}

function fsbdd_display_sessions_table() {
    global $wpdb;
    
    // CSS pour le tableau des sessions
    echo '<style>
        body {
            background-color: #2c3e50 !important;
            color: #ffffff;
        }
        
        .wrap {
            background-color: #2c3e50;
            color: #ffffff;
        }
        
        #wpcontent {
            background-color: #314150 !important;
            color: #ffffff;
        }
        
        h1, h2, h3, h4, h5, h6 {
            color: #ffffff !important;
        }
        
        .wrap h1 {
            color: #ffffff !important;
        }
        
        .fsbdd-sessions-container {
            background: #314150;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        
        /* Compteur Résultats */
        #fsbdd-sessions-filtered-count {
            margin-bottom: 10px;
            font-size: 13px;
            color: #ffffff;
        }
        
        /* Structure pour double scrollbar */
        .fsbdd-sessions-double-scroll-container {
            position: relative;
            width: 100%;
        }
        
        /* Top scrollbar */
        .fsbdd-sessions-top-scrollbar-container {
            overflow-x: auto;
            overflow-y: hidden;
            height: 15px;
            margin-bottom: 5px;
        }
        
        /* Dummy content pour la barre du haut */
        .fsbdd-sessions-top-scrollbar-content {
            height: 1px;
        }
        
        .fsbdd-sessions-table-wrapper {
            overflow-x: auto;
            overflow-y: auto;
            max-width: 100%;
            max-height: 600px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .table-container {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .fsbdd-sessions-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .fsbdd-sessions-table th {
            background-color: #d2e9ff;
			color: #000;
            text-align: left;
            padding: 10px 12px;
            border: 1px solid #ddd;
            font-weight: 600;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .fsbdd-sessions-table td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            vertical-align: middle;
            line-height: 1.4;
			color: #545454;
        }
        
        .fsbdd-sessions-table tbody tr:nth-child(even) {
            background-color: #f0f0f0 !important;
        }
        
        .fsbdd-sessions-table tbody tr:nth-child(odd) {
            background-color: #ffffff !important;
        }
        
        .fsbdd-sessions-table tbody tr:hover {
            background-color: #e4ebf1 !important;
            transition: background-color 0.2s ease;
        }
        
        .fsbdd-sessions-table td a {
            text-decoration: none;
            color: #314150;
        }
        
        .fsbdd-sessions-table td a:hover {
            text-decoration: underline;
            color: #339af0;
        }
        
        /* Badges Statut */
        .fsbdd-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            color: white;
            line-height: 1;
            white-space: nowrap;
        }
        .fsbdd-badge-default { background-color: #6c757d; }
        .fsbdd-badge-1 { background-color: #ffc107; } /* Jaune */
        .fsbdd-badge-2 { background-color: #17a2b8; } /* Bleu */
        .fsbdd-badge-3 { background-color: #fd7e14; } /* Orange */
        .fsbdd-badge-4 { background-color: #28a745; } /* Vert */
        
        /* Icones Documents */
        .fsbdd-sessions-table .dashicons {
            font-size: 18px;
            vertical-align: middle;
            margin-right: 4px;
        }
        

        
        /* Largeurs spécifiques des colonnes */
        /* Session */
        .fsbdd-sessions-table th:nth-child(1),
        .fsbdd-sessions-table td:nth-child(1) {
            width: 140px;
            min-width: 140px;
        }
        
        /* Affaires */
        .fsbdd-sessions-table th:nth-child(2),
        .fsbdd-sessions-table td:nth-child(2) {
            width: 250px;
            min-width: 250px;
        }
        
        /* Début */
        .fsbdd-sessions-table th:nth-child(3),
        .fsbdd-sessions-table td:nth-child(3) {
            width: 50px;
            min-width: 50px;
            text-align: center;
        }
        
        /* Fin */
        .fsbdd-sessions-table th:nth-child(4),
        .fsbdd-sessions-table td:nth-child(4) {
            width: 50px;
            min-width: 50px;
            text-align: center;
        }
        
        /* Lieu */
        .fsbdd-sessions-table th:nth-child(5),
        .fsbdd-sessions-table td:nth-child(5) {
            width: 100px;
            min-width: 100px;
        }
        
        /* Effectif Total */
        .fsbdd-sessions-table th:nth-child(6),
        .fsbdd-sessions-table td:nth-child(6) {
            width: 50px;
            min-width: 50px;
            text-align: center;
        }
        
        /* Stock */
        .fsbdd-sessions-table th:nth-child(7),
        .fsbdd-sessions-table td:nth-child(7) {
            width: 50px;
            min-width: 50px;
            text-align: center;
        }
        
        /* Formateurs */
        .fsbdd-sessions-table th:nth-child(8),
        .fsbdd-sessions-table td:nth-child(8) {
            width: 150px;
            min-width: 150px;
        }
        
        /* Fournisseurs */
        .fsbdd-sessions-table th:nth-child(9),
        .fsbdd-sessions-table td:nth-child(9) {
            width: 150px;
            min-width: 150px;
        }
        
        /* Type */
        .fsbdd-sessions-table th:nth-child(10),
        .fsbdd-sessions-table td:nth-child(10) {
            width: 70px;
            min-width: 70px;
        }
        
        /* Confirmation */
        .fsbdd-sessions-table th:nth-child(11),
        .fsbdd-sessions-table td:nth-child(11) {
            width: 80px;
            min-width: 80px;
        }
        
        /* UT Pratiques */
        .fsbdd-sessions-table th:nth-child(12),
        .fsbdd-sessions-table td:nth-child(12) {
            width: 70px;
            min-width: 70px;
            text-align: center;
        }
        
		        /* Filtres */
        
        .fsbdd-sessions-filters {
            background: white;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 5px;
            align-items: end;
            width: 100%;
            border-radius: 4px;
        }
        
        .fsbdd-sessions-filter-group {
            display: flex;
            flex-direction: column;
            flex: 1 1 200px;
        }
        
        .fsbdd-sessions-filter-group label,
        .fsbdd-sessions-search-container label {
            display: block;
            font-weight: 600;
            font-size: 12px;
            color: #444;
            margin-bottom: 5px;
        }
        
        .fsbdd-sessions-filter-group select,
        .fsbdd-sessions-filter-group input,
        .fsbdd-sessions-search-container input {
            width: 100%;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .fsbdd-sessions-search-container {
            flex: 1 1 200px;
        }
                
        #fsbdd-sessions-search {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #0073aa;
            border-radius: 4px;
            font-size: 14px;
        }
        
        /* Boutons daction et pagination */
        .fsbdd-sessions-action-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .fsbdd-sessions-actions-links {
            display: flex;
            gap: 15px;
        }
        
        .fsbdd-sessions-actions-link {
            font-size: 13px;
            text-decoration: none;
            color: #0073aa;
        }
        
        .fsbdd-sessions-actions-link:hover {
            text-decoration: underline;
        }
        
        #fsbdd-sessions-show-all {
            font-size: 13px;
            cursor: pointer;
            color: #0073aa;
            background: #f0f0f0;
            padding: 5px 10px;
            border-radius: 3px;
            border: 1px solid #ddd;
        }
        
        #fsbdd-sessions-show-all:hover {
             background: #e0e0e0;
             border-color: #ccc;
         }
         
         /* Badges de statut */
         .fsbdd-badge {
             padding: 4px 8px;
             border-radius: 12px;
             font-size: 10px;
             font-weight: 600;
             text-transform: uppercase;
             letter-spacing: 0.5px;
         }
         
         /* Styles pour les indicateurs de tri */
         .fsbdd-sessions-table th[data-sort] {
             cursor: pointer;
             user-select: none;
         }
         
         .fsbdd-sessions-table th[data-sort]:hover {
             background-color: #f8f9fa;
         }
         
         .fsbdd-sessions-table th[data-sort]:after {
             content: " ⇅";
             opacity: 0.3;
             font-size: 12px;
             margin-left: 5px;
         }
         
         .fsbdd-sessions-table th.sort-asc:after {
             content: " ↑";
             opacity: 1;
             color: #007cba;
             margin-left: 5px;
         }
         
         .fsbdd-sessions-table th.sort-desc:after {
             content: " ↓";
             opacity: 1;
             color: #007cba;
             margin-left: 5px;
         }
         
         .fsbdd-badge-publish {
             background: linear-gradient(135deg, #10b981 0%, #059669 100%);
             color: white;
         }
         
         .fsbdd-badge-draft {
             background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
             color: white;
         }
         
         .fsbdd-badge-confirmed {
             background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
             color: white;
         }
         
         .fsbdd-badge-pending {
             background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
             color: white;
         }
         
         /* Amélioration des cellules du tableau */
         .fsbdd-sessions-table td {
             padding: 12px 8px;
             border-bottom: 1px solid #e5e7eb;
             vertical-align: top;
             font-size: 12px;
             line-height: 1.4;
         }
         
         /* Styles supprimés - conflits avec les styles principaux */
         
         /* Type Session */
        .fsbdd-sessions-table th:nth-child(10),
        .fsbdd-sessions-table td:nth-child(10) {
            width: 70px;
            min-width: 70px;
            text-align: center;
        }
        
        /* Confirmation */
        .fsbdd-sessions-table th:nth-child(11),
        .fsbdd-sessions-table td:nth-child(11) {
            width: 70px;
            min-width: 70px;
            text-align: center;
        }
        
        /* UT Pratiques */
        .fsbdd-sessions-table th:nth-child(12),
        .fsbdd-sessions-table td:nth-child(12) {
            width: 60px;
            min-width: 60px;
            text-align: center;
        }
        
        /* Docs */
        .fsbdd-sessions-table th:nth-child(13),
        .fsbdd-sessions-table td:nth-child(13) {
            width: 65px;
            min-width: 65px;
            text-align: center;
        }
        
        /* Alertes */
        .fsbdd-sessions-table th:nth-child(14),
        .fsbdd-sessions-table td:nth-child(14) {
            width: 80px;
            min-width: 80px;
            text-align: center;
        }
        
        /* Filtres */
        .fsbdd-sessions-table th:nth-child(15),
        .fsbdd-sessions-table td:nth-child(15) {
            width: 80px;
            min-width: 80px;
            text-align: center;
        }
        
        .fsbdd-sessions-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            border-right: 1px solid #f1f3f4;
            vertical-align: top;
            line-height: 1.4;
            transition: background-color 0.2s ease;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
        }
        
        .fsbdd-sessions-table td:last-child {
            border-right: none;
        }
        
        .fsbdd-sessions-table tbody tr {
            transition: all 0.2s ease;
        }
        
        /* Styles supprimés - conflits avec les styles principaux */
        
        .session-title {
            font-weight: 700;
            color: #1e40af;
            font-size: 15px;
            margin-bottom: 4px;
        }
        
        .session-title a {
            color: #1e40af;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .session-title a:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }
        
        .inter-numero {
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .inter-numero a {
            color: #2563eb;
            text-decoration: none;
        }
        
        .inter-numero a:hover {
            text-decoration: underline;
        }
        
        .formation-name {
            font-size: 11px;
            color: #6b7280;
            background: #f9fafb;
            padding: 2px 4px;
            border-radius: 3px;
            display: block;
            text-align: left;
            margin-top: 2px;
        }
        
        .commands-list {
            max-height: 70px;
            overflow-y: auto;
            padding-right: 4px;
            width: 100%;
        }
        
        .commands-list::-webkit-scrollbar {
            width: 4px;
        }
        
        .commands-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 2px;
        }
        
        .commands-list::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 2px;
        }
        
        .command-item {
            margin: 3px 0;
    padding: 5px 4px;
    font-size: 11px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
    width: 100%;
    box-sizing: border-box;
    border-bottom: 1px solid #314150
        }
        
        .command-item:hover {
            transform: translateX(2px);
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        
        .command-item a {
            color: #0c4a6e;
            text-decoration: none;
            font-weight: 600;
        }
        
        .command-item a:hover {
            color: #075985;
            text-decoration: underline;
        }
        
        .command-convention {
            font-weight: 700;
            color: #0c4a6e;
        }
        
        .command-client {
            color: #64748b;
            font-size: 11px;
            margin-top: 2px;
            font-style: italic;
        }
        
        .session-dates {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            line-height: 1.3;
            margin-bottom: 4px;
        }
        
        .session-lieu {
            font-size: 11px;
            color: #6b7280;
            background: #f9fafb;
            padding: 2px 4px;
            border-radius: 3px;
            display: block;
            text-align: center;
            margin-top: 2px;
        }
        
        .session-status {
            text-align: center;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 2px solid transparent;
            transition: all 0.2s ease;
        }
        
        .status-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .status-active,
        .status-publish {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-color: #047857;
        }
        
        .status-draft {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            border-color: #b45309;
        }
        
        .status-archived {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border-color: #b91c1c;
        }
        
        /* Styles simplifiés pour formateurs et fournisseurs */
        .fsbdd-sessions-table td:nth-child(8) a,
        .fsbdd-sessions-table td:nth-child(9) a {
            font-size: 10px;
            font-weight: 500;
        }
        
        .alerts-column {
            text-align: center;
            width: 80px;
            padding: 8px;
        }
        
        .alert-indicator {
            display: inline-block;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            margin: 2px;
            border: 2px solid white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            transition: all 0.2s ease;
        }
        
        .alert-indicator:hover {
            transform: scale(1.2);
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        
        .alert-ok {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .alert-error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        /* Styles de filtres dupliqués supprimés - utilisation des classes fsbdd-sessions-filter-group */
    </style>';
    
    // Récupérer toutes les sessions (actions de formation)
    $sessions = get_posts([
        'post_type' => 'action-de-formation',
        'post_status' => ['publish', 'draft'],
        'posts_per_page' => -1,
        'orderby' => 'meta_value',
        'meta_key' => 'we_startdate',
        'order' => 'DESC'
    ]);
    
    echo '<div class="fsbdd-sessions-container">';
    
    // Section des filtres améliorée
    echo '<div class="fsbdd-sessions-filters" style="display: flex; flex-direction: column;">';
    
    // Première ligne des filtres principaux (limitée à 5 éléments)
    echo '<div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 15px; margin-bottom: 15px; width: 100%;">';
    
    echo '<div class="fsbdd-sessions-search-container">';
    echo '<label for="fsbdd-sessions-search">Recherche globale</label>';
    echo '<input type="text" id="fsbdd-sessions-search" placeholder="Rechercher dans toutes les colonnes..." />';
    echo '</div>';
    
    echo '<div class="fsbdd-sessions-search-container">';
    echo '<label for="fsbdd-sessions-session-search">Recherche Sessions</label>';
    echo '<input type="text" id="fsbdd-sessions-session-search" placeholder="Rechercher dans les sessions..." />';
    echo '</div>';
    
    echo '<div class="fsbdd-sessions-filter-group">';
    echo '<label>Confirmation</label>';
    echo '<select id="fsbdd-sessions-confirmation-filter">';
    echo '<option value="">Toutes les confirmations</option>';
    echo '<option value="TODO">TODO</option>';
    echo '<option value="NON">NON</option>';
    echo '<option value="OUI">OUI</option>';
    echo '<option value="BOOKÉ">BOOKÉ</option>';
    echo '<option value="ANNULÉ">ANNULÉ</option>';
    echo '</select>';
    echo '</div>';
    
    echo '<div class="fsbdd-sessions-filter-group">';
    echo '<label>Formateur</label>';
    echo '<select id="fsbdd-sessions-formateur-filter">';
    echo '<option value="">Tous les formateurs</option>';
    
    // Récupérer tous les formateurs uniques
    $all_formateurs = [];
    foreach ($sessions as $session) {
        $planning_data = get_post_meta($session->ID, 'fsbdd_planning', true);
        if (!empty($planning_data) && is_array($planning_data)) {
            foreach ($planning_data as $day) {
                if (!empty($day['fsbdd_gpformatr']) && is_array($day['fsbdd_gpformatr'])) {
                    foreach ($day['fsbdd_gpformatr'] as $formateur) {
                        $formateur_id = $formateur['fsbdd_user_formateurrel'] ?? '';
                        if (!empty($formateur_id)) {
                            $formateur_title = get_the_title($formateur_id);
                            if (!empty($formateur_title) && !in_array($formateur_title, $all_formateurs)) {
                                $all_formateurs[] = $formateur_title;
                            }
                        }
                    }
                }
            }
        }
    }
    
    sort($all_formateurs);
    foreach ($all_formateurs as $formateur) {
        echo '<option value="' . esc_attr($formateur) . '">' . esc_html($formateur) . '</option>';
    }
    
    echo '</select>';
    echo '</div>';
    
    echo '<div class="fsbdd-sessions-filter-group">';
    echo '<label>Type</label>';
    echo '<select id="fsbdd-sessions-type-filter">';
    echo '<option value="">Tous les types</option>';
    echo '<option value="INTER">INTER</option>';
    echo '<option value="INTRA Mutualisé">INTRA Mutualisé</option>';
    echo '<option value="INTRA">INTRA</option>';
    echo '</select>';
    echo '</div>';
    
    // New filter: Date status select
    echo '<div class="fsbdd-sessions-filter-group">';
    echo '<label>Statut Date</label>';
    echo '<select id="fsbdd-sessions-date-status-filter">';
    echo '<option value="">Toutes les sessions</option>';
    echo '<option value="terminees">Sessions terminées</option>';
    echo '<option value="encours">Sessions en cours</option>';
    echo '<option value="avenir">Sessions à venir</option>';
    echo '<option value="mois_courant">Mois en cours</option>';
    echo '</select>';
    echo '</div>';
    
    // New filter: Datepicker for start date
    echo '<div class="fsbdd-sessions-filter-group">';
    echo '<label>Date de début</label>';
    echo '<input type="date" id="fsbdd-sessions-start-date-filter" />';
    echo '</div>';
    
    echo '</div>'; // Fin de la première ligne
    
    // Deuxième ligne : Filtres rapides avec cases à cocher (toute la largeur)
    echo '<div style="display: flex; align-items: center; gap: 20px; padding: 5px; background-color: #f8f9fa; border-radius: 5px; border: 1px solid #e9ecef;">';
    
    echo '<span style="font-weight: bold; color: #333; margin-right: 10px;">Rapprochements:</span>';
    
    echo '<label style="display: flex; align-items: center; cursor: pointer; font-size: 13px; color: #333; margin-right: 15px;">';
    echo '<input type="checkbox" id="filter-rappro-vierge" style="margin-right: 5px;" />';
    echo 'vierges <span id="count-rappro-vierge" style="color: #dc3545; font-weight: bold;"></span>';
    echo '</label>';
    
    echo '<label style="display: flex; align-items: center; cursor: pointer; font-size: 13px; color: #333; margin-right: 15px;">';
    echo '<input type="checkbox" id="filter-rappro-encours" style="margin-right: 5px;" />';
    echo 'en cours <span id="count-rappro-encours" style="color: #ffc107; font-weight: bold;"></span>';
    echo '</label>';
    
    echo '<label style="display: flex; align-items: center; cursor: pointer; font-size: 13px; color: #333; margin-right: 15px;">';
    echo '<input type="checkbox" id="filter-rappro-termines" style="margin-right: 5px;" />';
    echo 'terminés <span id="count-rappro-termines" style="color: #28a745; font-weight: bold;"></span>';
    echo '</label>';
    
    echo '<label style="display: flex; align-items: center; cursor: pointer; font-size: 13px; color: #333;">';
    echo '<input type="checkbox" id="filter-docs-missing" style="margin-right: 8px;" />';
    echo '<span style="display: flex; align-items: center; color: #333;"><span class="dashicons dashicons-media-document" style="font-size: 12px; margin-right: 5px; color: #ff9800;"></span>Documents manquants</span>';
    echo '</label>';
    
    echo '<label style="display: flex; align-items: center; cursor: pointer; font-size: 13px; color: #333;">';
    echo '<input type="checkbox" id="filter-lm-pending" style="margin-right: 8px;" />';
    echo '<span style="display: flex; align-items: center; color: #333;"><span class="dashicons dashicons-email-alt" style="font-size: 12px; margin-right: 5px; color: #2196f3;"></span>Lettres de mission en attente</span>';
    echo '</label>';
    
    echo '<button type="button" id="clear-quick-filters" style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; margin-left: auto;">Effacer les filtres</button>';
    
    echo '</div>'; // Fin de la deuxième ligne des filtres rapides
    
    echo '</div>'; // Fin du conteneur des filtres
    
    // Compteur de résultats
    echo '<div id="fsbdd-sessions-filtered-count">Chargement...</div>';
    
    // Structure pour double scrollbar
    echo '<div class="fsbdd-sessions-double-scroll-container">';
    echo '<div class="fsbdd-sessions-top-scrollbar-container">';
    echo '<div class="fsbdd-sessions-top-scrollbar-content"></div>';
    echo '</div>';
    
    echo '<div class="fsbdd-sessions-table-wrapper">';
    echo '<table id="fsbdd-sessions-table" class="fsbdd-sessions-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th data-sort="text">Session</th>';
    echo '<th>Affaires</th>';
    echo '<th data-sort="date">Début</th>';
    echo '<th data-sort="date">Fin</th>';
    echo '<th>Lieu</th>';
    echo '<th data-sort="number">Effect.</th>';
    echo '<th>Stock</th>';
    echo '<th>Formateurs</th>';
    echo '<th>Fournisseurs</th>';
    echo '<th>Type</th>';
    echo '<th>Confirm.</th>';
    echo '<th>UT prat.</th>';
    echo '<th>Docs</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($sessions as $session) {
        $session_data = fsbdd_get_session_data($session->ID);
        fsbdd_render_session_row($session_data);
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>'; // Fermeture fsbdd-sessions-table-wrapper
    echo '</div>'; // Fermeture fsbdd-sessions-double-scroll-container
    
    // Boutons d'action et pagination
    echo '<div class="fsbdd-sessions-action-buttons">';
    echo '<div class="fsbdd-sessions-actions-links">';
    echo '<a href="#" class="fsbdd-sessions-actions-link" id="fsbdd-sessions-export-csv">Exporter CSV</a>';
    echo '<a href="#" class="fsbdd-sessions-actions-link" id="fsbdd-sessions-export-excel">Exporter Excel</a>';
    echo '<a href="#" class="fsbdd-sessions-actions-link" id="fsbdd-sessions-print">Imprimer</a>';
    echo '</div>';
    echo '<div id="fsbdd-sessions-show-all">Afficher tout</div>';
    echo '</div>';
    
    echo '</div>'; // Fermeture fsbdd-sessions-container
}

function fsbdd_get_session_data($session_id) {
    global $wpdb;
    
    // Récupérer les métadonnées de la session
    $session_meta = get_post_meta($session_id);
    $session_post = get_post($session_id);
    
    // Récupérer toutes les commandes liées à cette session
    $query = "
        SELECT DISTINCT oi.order_id, om.meta_value as convention, o.post_title as order_number
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim
            ON oi.order_item_id = oim.order_item_id
        LEFT JOIN {$wpdb->prefix}posts AS o ON oi.order_id = o.ID
        LEFT JOIN {$wpdb->prefix}postmeta AS om ON (oi.order_id = om.post_id AND om.meta_key = 'fsbdd_numconv')
        WHERE oim.meta_key = 'fsbdd_relsessaction_cpt_produit'
        AND oim.meta_value = %s
        ORDER BY oi.order_id DESC
    ";
    
    $orders = $wpdb->get_results($wpdb->prepare($query, $session_id));
    
    // Calculer l'effectif total
    $total_effectif = 0;
    $commands_data = [];
    
    foreach ($orders as $order_row) {
        $order = wc_get_order($order_row->order_id);
        if (!$order) continue;
        
        $effectif = intval(get_post_meta($order_row->order_id, 'fsbdd_effectif', true));
        $total_effectif += $effectif;
        
        // Utiliser billing_society en priorité
        $client_name = $order->get_meta('billing_society');
        if (empty($client_name)) {
            $client_name = $order->get_billing_company();
        }
        if (empty($client_name)) {
            $client_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        }
        
        $commands_data[] = [
            'order_id' => $order_row->order_id,
            'order_number' => $order_row->order_number,
            'convention' => $order_row->convention,
            'client_name' => $client_name,
            'effectif' => $effectif,
            'status' => $order->get_status()
        ];
    }
    
    // Récupérer les formateurs depuis les données de planning
    $formateurs_data = [];
    $planning_data = get_post_meta($session_id, 'fsbdd_planning', true);
    if (!empty($planning_data) && is_array($planning_data)) {
        foreach ($planning_data as $day) {
            if (!empty($day['fsbdd_gpformatr']) && is_array($day['fsbdd_gpformatr'])) {
                foreach ($day['fsbdd_gpformatr'] as $formateur) {
                    $formateur_id = $formateur['fsbdd_user_formateurrel'] ?? '';
                    if (!empty($formateur_id)) {
                        $formateur_title = get_the_title($formateur_id);
                        if (!empty($formateur_title)) {
                            $key = $formateur_id . '_' . $formateur_title;
                            if (!isset($formateurs_data[$key])) {
                                $formateurs_data[$key] = [
                                    'id' => $formateur_id,
                                    'title' => $formateur_title
                                ];
                            }
                        }
                    }
                }
            }
        }
    }
    
    // Dates
    $start_timestamp = isset($session_meta['we_startdate'][0]) ? intval($session_meta['we_startdate'][0]) : 0;
    $end_timestamp = isset($session_meta['we_enddate'][0]) ? intval($session_meta['we_enddate'][0]) : 0;
    
    $start_date = $start_timestamp ? date('d/m/y', $start_timestamp) : 'N/D';
    $end_date = $end_timestamp ? date('d/m/y', $end_timestamp) : 'N/D';
    
    // Récupérer les fournisseurs depuis les données de planning
    $fournisseurs_data = [];
    if (!empty($planning_data) && is_array($planning_data)) {
        foreach ($planning_data as $day) {
            if (!empty($day['fournisseur_salle']) && is_array($day['fournisseur_salle'])) {
                foreach ($day['fournisseur_salle'] as $fournisseur) {
                    $fournisseur_id = $fournisseur['fsbdd_user_foursalle'] ?? '';
                    if (!empty($fournisseur_id)) {
                        $fournisseur_title = get_the_title($fournisseur_id);
                        if (!empty($fournisseur_title)) {
                            $key = $fournisseur_id . '_' . $fournisseur_title;
                            if (!isset($fournisseurs_data[$key])) {
                                $fournisseurs_data[$key] = [
                                    'id' => $fournisseur_id,
                                    'title' => $fournisseur_title
                                ];
                            }
                        }
                    }
                }
            }
        }
    }
    
    // Traitement du lieu (comme dans reports.txt)
    $lieu_resume = 'N/D';
    $lieu_complet = $session_meta['fsbdd_select_lieusession'][0] ?? 'Lieu N/D';
    if ($lieu_complet && strpos($lieu_complet, ',') !== false) {
        $lieu_parts = explode(',', $lieu_complet);
        $lieu_resume = ucfirst(strtolower(trim($lieu_parts[0])));
    } else {
        $lieu_resume = $lieu_complet;
    }

    // Calculer les UT pratiques totales
    $total_ut_pratiques = 0;
    if (!empty($planning_data) && is_array($planning_data)) {
        foreach ($planning_data as $day) {
            $date_meta_format = $day['fsbdd_dateform'] ?? '';
            if (!empty($date_meta_format)) {
                foreach ($orders as $order_row) {
                    $order = wc_get_order($order_row->order_id);
                    if (!$order) continue;
                    
                    $convoc_meta_key = 'fsbdd_convoc_' . $date_meta_format;
                    $is_convoque = get_post_meta($order_row->order_id, $convoc_meta_key, true);
                    
                    if ($is_convoque == '1' && in_array($order->get_status(), ['processing', 'completed'])) {
                        $effectif = intval(get_post_meta($order_row->order_id, 'fsbdd_effectif', true));
                        
                        $items = $order->get_items();
                        foreach ($items as $item) {
                            $item_action_id = wc_get_order_item_meta($item->get_id(), 'fsbdd_relsessaction_cpt_produit', true);
                            if ($item_action_id == $session_id || empty($item_action_id)) {
                                $ut_pratique = floatval(wc_get_order_item_meta($item->get_id(), 'ut_pratique', true));
                                $total_ut_pratiques += ($effectif * $ut_pratique);
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    return [
        'session_id' => $session_id,
        'session_title' => $session_post->post_title,
        'session_status' => $session_post->post_status,
        'inter_numero' => $session_meta['fsbdd_inter_numero'][0] ?? '',
        'start_date' => $start_date,
        'end_date' => $end_date,
        'lieu' => $lieu_resume,
        'type_session' => fsbdd_get_type_session_label($session_meta['fsbdd_typesession'][0] ?? ''),
        'total_effectif' => $total_effectif,
        'stock' => $session_meta['fsbdd_placedispo'][0] ?? '0',
        'formateurs' => array_values($formateurs_data),
        'fournisseurs' => array_values($fournisseurs_data),
        'commands' => $commands_data,
        'emargements' => $session_meta['fsbdd_etatemargm'][0] ?? '1',
        'cpte_rendu' => $session_meta['fsbdd_etatcpterenduf'][0] ?? '1',
        'evaluations' => $session_meta['fsbdd_etateval'][0] ?? '1',
        'confirmation' => fsbdd_get_confirmation_label($session_meta['fsbdd_sessconfirm'][0] ?? ''),
        'titre_formation' => $session_meta['fsbdd_titreform'][0] ?? '',
        'ut_pratiques' => $total_ut_pratiques,
        // Alertes (à développer)
        'alerts' => [
            'formateur_status' => $session_meta['fsbdd_formateurs_status'][0] ?? '',
            'inter_status' => $session_meta['fsbdd_inter_status'][0] ?? '',
            'option_status' => $session_meta['fsbdd_option_status'][0] ?? ''
        ]
    ];
}

function fsbdd_render_session_row($session_data) {
    echo '<tr data-session-id="' . esc_attr($session_data['session_id']) . '"';
    echo ' data-session-status="' . esc_attr($session_data['session_status']) . '"';
    echo ' data-start-date="' . esc_attr($session_data['start_date']) . '"';
    echo ' data-type-session="' . esc_attr($session_data['type_session']) . '">';
    
    // Colonne: Session
    echo '<td>';
    if (!empty($session_data['inter_numero'])) {
        echo '<div class="inter-numero" style="display: flex; justify-content: space-between; align-items: center;">';
        echo '<a href="' . esc_url(admin_url('post.php?post=' . $session_data['session_id'] . '&action=edit')) . '" target="_blank">';
        echo esc_html($session_data['inter_numero']);
        echo '</a>';
    
    // Indicateur d'état des rapprochements pour la session
    if (!empty($session_data['commands'])) {
        $session_rappro_status = 'none'; // none, partial, complete
        $total_commands = count($session_data['commands']);
        $complete_commands = 0;
        $started_commands = 0;
        
        foreach ($session_data['commands'] as $command) {
            $rappro_session = get_post_meta($command['order_id'], 'fsbdd_rappro_session', true) === '1';
            $rappro_specificites = get_post_meta($command['order_id'], 'fsbdd_rappro_specificites', true) === '1';
            $rappro_convocations = get_post_meta($command['order_id'], 'fsbdd_rappro_convocations', true) === '1';
            $rappro_quantites = get_post_meta($command['order_id'], 'fsbdd_rappro_quantites_couts', true) === '1';
            $rappro_subro = get_post_meta($command['order_id'], 'fsbdd_rappro_subro_reglements', true) === '1';
            $rappro_client = get_post_meta($command['order_id'], 'fsbdd_rappro_client_bdd_web', true) === '1';
            
            $completed_steps = array_sum([$rappro_session, $rappro_specificites, $rappro_convocations, $rappro_quantites, $rappro_subro, $rappro_client]);
            
            if ($completed_steps === 6) {
                $complete_commands++;
            } elseif ($completed_steps > 0) {
                $started_commands++;
            }
        }
        
        if ($complete_commands === $total_commands) {
            $session_rappro_status = 'complete';
            $indicator_color = '#c8e6c9'; // Vert pastel
            $indicator_title = 'Tous les rapprochements sont complets';
        } elseif ($started_commands > 0 || $complete_commands > 0) {
            $session_rappro_status = 'partial';
            $indicator_color = '#ffe0b2'; // Orange pastel
            $indicator_title = 'Rapprochements en cours';
        } else {
            $session_rappro_status = 'none';
            $indicator_color = '#ffcdd2'; // Rouge pastel
            $indicator_title = 'Aucun rapprochement commencé';
        }
        
        echo '<div class="session-rappro-indicator" style="width: 12px; height: 12px; border-radius: 50%; background-color: ' . $indicator_color . '; border: 1px solid #ddd; margin-left: 8px;" title="' . $indicator_title . '"></div>';
    }
    
    echo '</div>';
    }
    if (!empty($session_data['titre_formation'])) {
        // Limiter le nom du produit à 22 caractères
        $titre_formation_short = strlen($session_data['titre_formation']) > 22 ? substr($session_data['titre_formation'], 0, 22) . '...' : $session_data['titre_formation'];
        echo '<div class="formation-name">' . esc_html($titre_formation_short) . '</div>';
    }
    echo '</td>';
    
    // Colonne: Affaires
    echo '<td>';
    echo '<div class="commands-list">';
    if (!empty($session_data['commands'])) {
        foreach ($session_data['commands'] as $command) {
            // Calculer l'état du rapprochement
            $rappro_session = get_post_meta($command['order_id'], 'fsbdd_rappro_session', true) === '1';
            $rappro_specificites = get_post_meta($command['order_id'], 'fsbdd_rappro_specificites', true) === '1';
            $rappro_convocations = get_post_meta($command['order_id'], 'fsbdd_rappro_convocations', true) === '1';
            $rappro_quantites = get_post_meta($command['order_id'], 'fsbdd_rappro_quantites_couts', true) === '1';
            $rappro_subro = get_post_meta($command['order_id'], 'fsbdd_rappro_subro_reglements', true) === '1';
            $rappro_client = get_post_meta($command['order_id'], 'fsbdd_rappro_client_bdd_web', true) === '1';
            
            $completed_steps = array_sum([$rappro_session, $rappro_specificites, $rappro_convocations, $rappro_quantites, $rappro_subro, $rappro_client]);
            
            // Déterminer l'état et la couleur (tons pastels éclaircis)
             if ($completed_steps === 6) {
                 $rappro_status = 'OK';
                 $rappro_color = '#81c784'; // Vert pastel éclairci
             } else {
                 $rappro_status = $completed_steps . '/6';
                 // Couleurs plus contrastées et visibles
                 switch ($completed_steps) {
                     case 0:
                         $rappro_color = '#dc3545'; // Rouge vif
                         break;
                     case 1:
                         $rappro_color = '#fd7e14'; // Orange vif
                         break;
                     case 2:
                         $rappro_color = '#ffc107'; // Jaune vif
                         break;
                     case 3:
                         $rappro_color = '#20c997'; // Turquoise
                         break;
                     case 4:
                         $rappro_color = '#17a2b8'; // Bleu cyan
                         break;
                     case 5:
                         $rappro_color = '#6f42c1'; // Violet
                         break;
                     default:
                         $rappro_color = '#dc3545'; // Rouge vif par défaut
                 }
             }
            
            // Limiter le nom du client à 19 caractères
            $client_name_short = strlen($command['client_name']) > 19 ? substr($command['client_name'], 0, 19) . '...' : $command['client_name'];
            
            echo '<div class="command-item" style="display: flex; justify-content: space-between; align-items: center;">';
            echo '<div>';
            echo '<a href="' . esc_url(admin_url('post.php?post=' . $command['order_id'] . '&action=edit')) . '" target="_blank">';
            
            if (!empty($command['convention'])) {
                echo '<span class="command-convention">' . esc_html($command['convention']) . '</span>';
            } else {
                echo '#' . esc_html($command['order_number']);
            }
            echo '</a>';
             echo ' - ' . esc_html($client_name_short) . ' (' . $command['effectif'] . ')';
             echo '</div>';
             echo '<span class="rappro-status" style="color: ' . $rappro_color . '; font-weight: bold; margin-left: 8px;">' . $rappro_status . '</span>';
            echo '</div>';
        }
    } else {
        echo '<em>Aucune affaire</em>';
    }
    echo '</div>';
    echo '</td>';
    
    // Colonne: Début
    echo '<td>';
    echo '<div class="session-start-date">' . esc_html($session_data['start_date']) . '</div>';
    echo '</td>';
    
    // Colonne: Fin
    echo '<td>';
    echo '<div class="session-end-date">' . esc_html($session_data['end_date']) . '</div>';
    echo '</td>';
    
    // Colonne: Lieu
    echo '<td>';
    echo '<div class="session-lieu">' . esc_html($session_data['lieu']) . '</div>';
    echo '</td>';
    
    // Colonne: Effectif Total
    echo '<td style="text-align: center;">' . esc_html($session_data['total_effectif']) . '</td>';
    
    // Colonne: Stock
    echo '<td style="text-align: center;">' . esc_html($session_data['stock']) . '</td>';
    
    // Colonne: Formateurs
    echo '<td>';
    if (!empty($session_data['formateurs'])) {
        $formateur_links = [];
        foreach ($session_data['formateurs'] as $formateur) {
            $formateur_links[] = '<a href="' . esc_url(admin_url('post.php?post=' . $formateur['id'] . '&action=edit')) . '" target="_blank">' . esc_html($formateur['title']) . '</a>';
        }
        echo implode(', ', $formateur_links);
    } else {
        echo '<em>Non défini</em>';
    }
    echo '</td>';
    
    // Colonne: Fournisseurs
    echo '<td>';
    if (!empty($session_data['fournisseurs'])) {
        $fournisseur_links = [];
        foreach ($session_data['fournisseurs'] as $fournisseur) {
            $fournisseur_links[] = '<a href="' . esc_url(admin_url('post.php?post=' . $fournisseur['id'] . '&action=edit')) . '" target="_blank">' . esc_html($fournisseur['title']) . '</a>';
        }
        echo implode(', ', $fournisseur_links);
    } else {
        echo '<em>Non défini</em>';
    }
    echo '</td>';
    
    // Colonne: Type Session (afficher la valeur plutôt que le label)
    echo '<td>' . esc_html($session_data['type_session']) . '</td>';
    
    // Colonne: Confirmation
    echo '<td>' . esc_html($session_data['confirmation']) . '</td>';
    
    // Colonne: UT Pratiques
    echo '<td style="text-align: center;">' . number_format($session_data['ut_pratiques'], 2) . '</td>';
    
    // Colonne: Documents
    echo '<td style="text-align: center;">';
    $doc_emargements_class = 'fsbdd-badge-' . $session_data['emargements'];
    $doc_cpterendu_class = 'fsbdd-badge-' . $session_data['cpte_rendu'];
    $doc_evaluations_class = 'fsbdd-badge-' . $session_data['evaluations'];
    
    echo '<span class="dashicons dashicons-yes-alt" style="color: ' . get_doc_color($doc_emargements_class) . ';" title="Émargements"></span>';
    echo '<span class="dashicons dashicons-text-page" style="color: ' . get_doc_color($doc_cpterendu_class) . ';" title="Compte rendu"></span>';
    echo '<span class="dashicons dashicons-chart-bar" style="color: ' . get_doc_color($doc_evaluations_class) . ';" title="Évaluations"></span>';
    echo '</td>';
    

    
    echo '</tr>';
}

/**
 * Fonction helper pour convertir le code type session en libellé
 */
if (!function_exists('fsbdd_get_type_session_label')) {
    function fsbdd_get_type_session_label($code) {
        switch ($code) {
            case '1': return 'INTER';
            case '2': return 'INTRA Mutualisé'; // Assurez-vous que cette valeur correspond exactement à celle utilisée dans le filtre
            case '3': return 'INTRA';
            default: return 'N/D';
        }
    }
}


/**
 * Fonction helper pour convertir le code confirmation en libellé
 */
if (!function_exists('fsbdd_get_confirmation_label')) {
    function fsbdd_get_confirmation_label($code) {
        switch ($code) {
            case '1': return 'TODO';
            case '2': return 'NON';
            case '3': return 'OUI';
            case '4': return 'BOOKÉ';
            case '4': return 'ANNULÉ';
			default: return 'N/D';
        }
    }
}

/**
 * Fonction helper pour les couleurs des documents (réutilisée depuis reports.txt)
 */
if (!function_exists('get_doc_color')) {
    function get_doc_color($class) {
        switch ($class) {
            case 'fsbdd-badge-1': // Vide
                return '#ffc107'; // Jaune
            case 'fsbdd-badge-2': // Partiel
                return '#17a2b8'; // Bleu
            case 'fsbdd-badge-3': // Reçus
                return '#fd7e14'; // Orange
            case 'fsbdd-badge-4': // Certifié / OK
                return '#28a745'; // Vert
            default:
                return '#6c757d'; // Gris par défaut
        }
    }
}

// JavaScript pour les fonctionnalités interactives
add_action('admin_footer', 'fsbdd_sessions_table_scripts');

function fsbdd_sessions_table_scripts() {
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'sessions-table') === false) {
        return;
    }

    echo '<script>
    jQuery(document).ready(function($) {
        // Tri des colonnes
        $(".fsbdd-sessions-table th[data-sort]").click(function() {
            var column = $(this).index();
            var sortType = $(this).data("sort");
            var isAsc = $(this).hasClass("sort-asc");

            // Réinitialiser les classes de tri
            $(".fsbdd-sessions-table th").removeClass("sort-asc sort-desc");

            // Ajouter la classe appropriée
            $(this).addClass(isAsc ? "sort-desc" : "sort-asc");

            // Trier les lignes
            var rows = $(".fsbdd-sessions-table tbody tr").get();

            rows.sort(function(a, b) {
                var aVal = $(a).children().eq(column).text().trim();
                var bVal = $(b).children().eq(column).text().trim();

                if (sortType === "number") {
                    aVal = parseInt(aVal) || 0;
                    bVal = parseInt(bVal) || 0;
                } else if (sortType === "date") {
                    aVal = parseDate(aVal);
                    bVal = parseDate(bVal);
                }

                if (aVal < bVal) return isAsc ? 1 : -1;
                if (aVal > bVal) return isAsc ? -1 : 1;
                return 0;
            });

            // Réinsérer les lignes triées
            $.each(rows, function(index, row) {
                $(".fsbdd-sessions-table tbody").append(row);
            });

            updateSessionsCount();
        });

        // Recherche globale
        $("#fsbdd-sessions-search").on("keyup", function() {
            applyFilters();
        });

        // Recherche spécifique Sessions
        $("#fsbdd-sessions-session-search").on("keyup", function() {
            applyFilters();
        });

        // Filtres
        $("#fsbdd-sessions-confirmation-filter, #fsbdd-sessions-formateur-filter, #fsbdd-sessions-type-filter, #fsbdd-sessions-date-status-filter, #fsbdd-sessions-start-date-filter").on("change", function() {
            applyFilters();
        });

        // Filtres rapides
        $("#filter-rappro-encours, #filter-rappro-vierge, #filter-rappro-termines, #filter-docs-missing, #filter-lm-pending").on("change", function() {
            applyFilters();
        });

        function applyFilters() {
            var confirmationFilter = $("#fsbdd-sessions-confirmation-filter").val().toLowerCase();
            var formateurFilter = $("#fsbdd-sessions-formateur-filter").val().toLowerCase();
            var typeFilter = $("#fsbdd-sessions-type-filter").val().toLowerCase();
            var globalSearch = $("#fsbdd-sessions-search").val().toLowerCase();
            var sessionSearch = $("#fsbdd-sessions-session-search").val().toLowerCase();
            var dateStatusFilter = $("#fsbdd-sessions-date-status-filter").val().toLowerCase();
            var startDateFilter = $("#fsbdd-sessions-start-date-filter").val();
            var rapproEnCoursFilter = $("#filter-rappro-encours").is(":checked");
            var rapproViergeFilter = $("#filter-rappro-vierge").is(":checked");
            var rapproTerminesFilter = $("#filter-rappro-termines").is(":checked");
            var docsFilter = $("#filter-docs-missing").is(":checked");
            var lmFilter = $("#filter-lm-pending").is(":checked");

            $(".fsbdd-sessions-table tbody tr").each(function() {
                var row = $(this);
                var showRow = true;

                // Filtre confirmation
                if (confirmationFilter && confirmationFilter !== "") {
                    var confirmationText = row.find("td:eq(10)").text().toLowerCase().trim();
                    if (confirmationText !== confirmationFilter) {
                        showRow = false;
                    }
                }

                // Filtre formateur
                if (formateurFilter && formateurFilter !== "") {
                    var formateurText = row.find("td:eq(7)").text().toLowerCase().trim();
                    if (formateurText.indexOf(formateurFilter) === -1) {
                        showRow = false;
                    }
                }

                // Filtre type session
                if (typeFilter && typeFilter !== "") {
                    var typeText = row.find("td:eq(9)").text().toLowerCase().trim();
                    if (typeText !== typeFilter) {
                        showRow = false;
                    }
                }

                // Filtre statut date
                if (dateStatusFilter && dateStatusFilter !== "") {
                    var startDate = row.find("td:eq(2)").text().trim();
                    var endDate = row.find("td:eq(3)").text().trim();
                    var currentDate = new Date();
                    var startDateObj = parseDate(startDate);
                    var endDateObj = parseDate(endDate);

                    if (dateStatusFilter === "terminees" && endDateObj >= currentDate) {
                        showRow = false;
                    } else if (dateStatusFilter === "encours" && !(startDateObj <= currentDate && endDateObj >= currentDate)) {
                        showRow = false;
                    } else if (dateStatusFilter === "avenir" && startDateObj <= currentDate) {
                        showRow = false;
                    } else if (dateStatusFilter === "mois_courant" && !(startDateObj.getMonth() === currentDate.getMonth() && startDateObj.getFullYear() === currentDate.getFullYear())) {
                        showRow = false;
                    }
                }

                // Filtre date de début
                if (startDateFilter && startDateFilter !== "") {
                    var startDate = row.find("td:eq(2)").text().trim();
                    var startDateObj = parseDate(startDate);
                    var filterDateObj = new Date(startDateFilter);

                    if (startDateObj < filterDateObj) {
                        showRow = false;
                    }
                }

                // Recherche globale
                if (globalSearch && globalSearch !== "") {
                    var rowText = row.text().toLowerCase();
                    if (rowText.indexOf(globalSearch) === -1) {
                        showRow = false;
                    }
                }

                // Recherche spécifique Sessions
                if (sessionSearch && sessionSearch !== "") {
                    var sessionText = row.find("td:eq(0)").text().toLowerCase();
                    if (sessionText.indexOf(sessionSearch) === -1) {
                        showRow = false;
                    }
                }

                // Filtre rapprochements en cours (pastille orange)
                if (rapproEnCoursFilter) {
                    var sessionIndicator = row.find("td:eq(0) .session-rappro-indicator");
                    if (sessionIndicator.length > 0) {
                        var indicatorTitle = sessionIndicator.attr("title");
                        if (indicatorTitle && indicatorTitle.includes("en cours")) {
                            showRow = showRow && true;
                        } else {
                            showRow = false;
                        }
                    } else {
                        showRow = false;
                    }
                }

                // Filtre rapprochements vierges (pastille rouge)
                if (rapproViergeFilter) {
                    var sessionIndicator = row.find("td:eq(0) .session-rappro-indicator");
                    if (sessionIndicator.length > 0) {
                        var indicatorTitle = sessionIndicator.attr("title");
                        if (indicatorTitle && indicatorTitle.includes("Aucun rapprochement")) {
                            showRow = showRow && true;
                        } else {
                            showRow = false;
                        }
                    } else {
                        showRow = false;
                    }
                }

                // Filtre rapprochements terminés (pastille verte)
                if (rapproTerminesFilter) {
                    var sessionIndicator = row.find("td:eq(0) .session-rappro-indicator");
                    if (sessionIndicator.length > 0) {
                        var indicatorTitle = sessionIndicator.attr("title");
                        if (indicatorTitle && indicatorTitle.includes("complets")) {
                            showRow = showRow && true;
                        } else {
                            showRow = false;
                        }
                    } else {
                        showRow = false;
                    }
                }

                // Filtre documents manquants (colonne 12 avec Stock ajoutée)
                if (docsFilter) {
                    var docsCell = row.find("td:eq(12)");
                    var docsText = docsCell.text().trim();
                    if (docsText.includes("Manquant") || docsText.includes("manquant") || docsText.includes("❌")) {
                        showRow = showRow && true;
                    } else {
                        showRow = false;
                    }
                }

                // Filtre lettres de mission en attente
                if (lmFilter) {
                    var formateursCell = row.find("td:eq(7)");
                    var formateursText = formateursCell.text().trim();
                    if (formateursText && formateursText !== "Aucun formateur" &&
                        !formateursText.includes("Envoyé") && !formateursText.includes("OK")) {
                        showRow = showRow && true;
                    } else {
                        showRow = false;
                    }
                }

                if (showRow) {
                    row.show();
                } else {
                    row.hide();
                }
            });

            updateSessionsCount();
        }

        // Synchronisation des barres de défilement
        var topScrollbar = $(".fsbdd-sessions-top-scrollbar-container");
        var tableWrapper = $(".fsbdd-sessions-table-wrapper");
        var topScrollbarContent = $(".fsbdd-sessions-top-scrollbar-content");

        // Ajuster la largeur du contenu de la barre du haut
        function updateTopScrollbarWidth() {
            var tableWidth = $(".fsbdd-sessions-table").outerWidth();
            topScrollbarContent.width(tableWidth);
        }

        updateTopScrollbarWidth();
        $(window).on("resize", updateTopScrollbarWidth);

        // Synchroniser le défilement
        topScrollbar.on("scroll", function() {
            tableWrapper.scrollLeft($(this).scrollLeft());
        });

        tableWrapper.on("scroll", function() {
            topScrollbar.scrollLeft($(this).scrollLeft());
        });

        // Compteur de résultats
        function updateSessionsCount() {
            var totalRows = $(".fsbdd-sessions-table tbody tr").length;
            var visibleRows = $(".fsbdd-sessions-table tbody tr:visible").length;

            var countText = visibleRows + " session(s) affichée(s)";
            if (visibleRows !== totalRows) {
                countText += " sur " + totalRows + " au total";
            }

            $("#fsbdd-sessions-filtered-count").text(countText);

            // Mettre à jour les compteurs de rapprochements
            updateRapprochementCounts();
        }

        // Fonction pour compter les rapprochements par catégorie
        function updateRapprochementCounts() {
            var viergeCount = 0;
            var enCoursCount = 0;
            var terminesCount = 0;

            $(".fsbdd-sessions-table tbody tr").each(function() {
                var sessionIndicator = $(this).find("td:eq(0) .session-rappro-indicator");
                if (sessionIndicator.length > 0) {
                    var indicatorTitle = sessionIndicator.attr("title");
                    if (indicatorTitle) {
                        if (indicatorTitle.includes("Aucun rapprochement")) {
                            viergeCount++;
                        } else if (indicatorTitle.includes("en cours")) {
                            enCoursCount++;
                        } else if (indicatorTitle.includes("complets")) {
                            terminesCount++;
                        }
                    }
                }
            });

            $("#count-rappro-vierge").text("(" + viergeCount + ")");
            $("#count-rappro-encours").text("(" + enCoursCount + ")");
            $("#count-rappro-termines").text("(" + terminesCount + ")");
        }

        // Bouton "Afficher tout"
        $("#fsbdd-sessions-show-all").on("click", function() {
            // Réinitialiser tous les filtres
            $("#fsbdd-sessions-search").val("");
            $("#fsbdd-sessions-confirmation-filter").val("");
            $("#fsbdd-sessions-formateur-filter").val("");
            $("#fsbdd-sessions-type-filter").val("");
            $("#fsbdd-sessions-date-status-filter").val("");
            $("#fsbdd-sessions-start-date-filter").val("");

            // Réinitialiser les filtres rapides
            $("#filter-rappro-encours, #filter-rappro-vierge, #filter-rappro-termines, #filter-docs-missing, #filter-lm-pending").prop("checked", false);

            // Afficher toutes les lignes
            $(".fsbdd-sessions-table tbody tr").show();

            updateSessionsCount();
        });

        // Bouton effacer filtres rapides
        $("#clear-quick-filters").on("click", function() {
            $("#filter-rappro-encours, #filter-rappro-vierge, #filter-rappro-termines, #filter-docs-missing, #filter-lm-pending").prop("checked", false);
            applyFilters();
        });

        // Actions dexport (placeholder)
        $("#fsbdd-sessions-export-csv").on("click", function(e) {
            e.preventDefault();
            alert("Fonctionnalité dexport CSV à implémenter");
        });

        $("#fsbdd-sessions-export-excel").on("click", function(e) {
            e.preventDefault();
            alert("Fonctionnalité dexport Excel à implémenter");
        });

        $("#fsbdd-sessions-print").on("click", function(e) {
            e.preventDefault();
            window.print();
        });

        // Fonction pour parser les dates au format dd/mm/yy
        function parseDate(dateStr) {
            if (!dateStr || dateStr === "N/D") {
                return new Date(0); // Date très ancienne pour les valeurs manquantes
            }

            var parts = dateStr.split("/");
            if (parts.length === 3) {
                var day = parseInt(parts[0], 10);
                var month = parseInt(parts[1], 10) - 1; // Les mois commencent à 0 en JavaScript
                var year = parseInt(parts[2], 10);

                // Convertir lannée à 2 chiffres en année complète
                if (year < 50) {
                    year += 2000;
                } else if (year < 100) {
                    year += 1900;
                }

                return new Date(year, month, day);
            }

            return new Date(0);
        }

        // Initialiser le compteur
        updateSessionsCount();
    });
    </script>';
}