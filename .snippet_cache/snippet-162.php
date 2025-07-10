<?php
/**
 * Snippet ID: 162
 * Name: REPORT alertes global accueil admin v3 TABLEAU
 * Description: 
 * @active true
 */



/**
 * REPORT alertes global accueil admin v3 tableau
 */
/**
 * Tableau principal du Dashboard des actions de formation
 * Séparé des alertes et filtres pour une meilleure modularité
 */

// Ne pas exécuter directement
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtient la couleur d'arrière-plan en fonction de la classe
 */
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

/**
 * Ajoute le tableau principal sur la page d'accueil admin
 * pour les utilisateurs autorisés
 */
add_action('admin_notices', 'fsbdd_dashboard_table', 20); // Priorité 20 pour s'exécuter après les alertes (10)

function fsbdd_dashboard_table() {
    // Vérifier que nous sommes sur la page d'accueil de l'admin
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'dashboard') {
        return;
    }

    // Vérifier les rôles autorisés
    $current_user = wp_get_current_user();
    if (!$current_user || $current_user->ID === 0) return;

    $allowed_roles = array('administrator', 'referent', 'compta');
    $can_access = false;

    foreach ($allowed_roles as $role) {
        if (in_array($role, (array) $current_user->roles)) {
            $can_access = true;
            break;
        }
    }

    if (!$can_access) {
        return;
    }

    // Si l'utilisateur est un référent, ne montrer que ses commandes
    $is_referent = in_array('referent', (array) $current_user->roles);
    $user_id = $current_user->ID;

    // CSS pour le tableau (styles spécifiques au tableau uniquement)
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
	
        /* Wrapper pour le tableau */
        .fsbdd-table-container {
            background: #ebedef;
            padding: 0px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        
        /* Compteur Résultats */
        #fsbdd-filtered-count {
            margin-bottom: 10px;
			margin-left: 20px;
            font-size: 13px;
            color: #666;
        }
        
        /* Tableau */
        .fsbdd-table-wrapper {
            overflow-x: auto;
            overflow-y: auto;
            max-width: 100%;
            max-height: 600px; /* Hauteur maximale pour permettre le défilement vertical */
            margin-bottom: 15px;
            position: relative; /* Pour que les barres de défilement soient bien positionnées */
        }
        
        /* Double scrollbar wrapper - Nouveau */
        .fsbdd-double-scroll-container {
            position: relative;
            width: 100%;
        }
        
        /* Top scrollbar - Nouveau */
        .fsbdd-top-scrollbar-container {
            overflow-x: auto;
            overflow-y: hidden;
            height: 15px; /* Hauteur de la barre de défilement */
            margin-bottom: 5px; /* Espace entre les deux barres */
        }
        
        /* Dummy content pour la barre du haut - Nouveau */
        .fsbdd-top-scrollbar-content {
            height: 1px; /* Minimum nécessaire */
        }
        
        .fsbdd-orders-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
			color: #2c3e50 !important;
        }
        .fsbdd-orders-table th {
            background-color: #d2e9ff;
            text-align: left;
            padding: 10px 12px;
            border: 1px solid #ddd;
            font-weight: 600;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .fsbdd-orders-table td {
            padding: 9px 12px;
            border: 1px solid #ddd;
            vertical-align: middle;
            line-height: 1.4;
        }
        .fsbdd-orders-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .fsbdd-orders-table tr:hover {
            background-color: #f1f1f1;
        }
        .fsbdd-orders-table td a {
            text-decoration: none;
            color: #0073aa;
        }
        .fsbdd-orders-table td a:hover {
            text-decoration: underline;
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
        .fsbdd-badge-confirme, .fsbdd-badge-wc-confirme { background-color: #28a745; }
        .fsbdd-badge-avenantvalide, .fsbdd-badge-wc-avenantvalide { background-color: #20c997; }
        .fsbdd-badge-avenantconv, .fsbdd-badge-wc-avenantconv { background-color: #fd7e14; }
        .fsbdd-badge-preinscription, .fsbdd-badge-wc-preinscription { background-color: #ffc107; color: #333; }
        .fsbdd-badge-inscription, .fsbdd-badge-modifpreinscript, .fsbdd-badge-wc-inscription, .fsbdd-badge-wc-modifpreinscript { background-color: #17a2b8; }
        .fsbdd-badge-devisproposition, .fsbdd-badge-wc-devisproposition { background-color: #0dcaf0; color: #333; }
        .fsbdd-badge-on-hold, .fsbdd-badge-wc-on-hold { background-color: #ffc107; color:#333; }
        .fsbdd-badge-pending, .fsbdd-badge-wc-pending { background-color: #6c757d; }
        .fsbdd-badge-processing, .fsbdd-badge-wc-processing { background-color: #6f42c1; }
        .fsbdd-badge-completed, .fsbdd-badge-wc-completed, .fsbdd-badge-wc-certifreal, .fsbdd-badge-certifreal { background-color: #0d6efd; }
        .fsbdd-badge-failed, .fsbdd-badge-wc-failed { background-color: #dc3545; }
        .fsbdd-badge-cancelled, .fsbdd-badge-wc-cancelled { background-color: #adb5bd; color:#333; }
        .fsbdd-badge-refunded, .fsbdd-badge-wc-refunded { background-color: #343a40; }
        .fsbdd-badge-gplsquote-req, .fsbdd-badge-wc-gplsquote-req { background-color: #6e44ff; }
		.fsbdd-badge-facturesent, .fsbdd-badge-wc-facturesent { background-color: #20c997; color: white; }
.fsbdd-badge-factureok, .fsbdd-badge-wc-factureok { background-color: #198754; color: white; }
.fsbdd-badge-echecdevis, .fsbdd-badge-wc-echecdevis { background-color: #dc3545; color: white; }
.fsbdd-badge-checkout-draft, .fsbdd-badge-wc-checkout-draft { background-color: #6c757d; color: white; }
        
        /* Icones Documents */
        .fsbdd-doc-icon {
            display: inline-block;
            width: 20px;
            height: 20px;
            text-align: center;
            line-height: 20px;
            margin-right: 3px;
            border-radius: 3px;
            color: white;
            font-weight: bold;
            font-size: 10px;
        }
        .fsbdd-orders-table .dashicons {
            font-size: 18px;
            vertical-align: middle;
            margin-right: 4px;
        }
        
        /* Boutons daction et pagination */
        .fsbdd-action-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .fsbdd-actions-links {
            display: flex;
            gap: 15px;
        }
        .fsbdd-actions-link {
            font-size: 13px;
            text-decoration: none;
            color: #0073aa;
        }
        .fsbdd-actions-link:hover {
            text-decoration: underline;
        }
        #fsbdd-show-all {
            font-size: 13px;
            cursor: pointer;
            color: #0073aa;
            background: #f0f0f0;
            padding: 5px 10px;
            border-radius: 3px;
            border: 1px solid #ddd;
        }
        #fsbdd-show-all:hover {
            background: #e0e0e0;
            border-color: #ccc;
        }
        
        /* Largeurs de colonnes spécifiques */
        .fsbdd-orders-table th:nth-child(1), .fsbdd-orders-table td:nth-child(1) { min-width: 120px; } /* Affaire (élargie pour inclure convention) */
        .fsbdd-orders-table th:nth-child(2), .fsbdd-orders-table td:nth-child(2) { min-width: 200px; } /* Client - Élargi */
        .fsbdd-orders-table th:nth-child(3), .fsbdd-orders-table td:nth-child(3) { width: 260px; } /* Session - Augmenté pour le contenu fusion */
        .fsbdd-orders-table th:nth-child(4), .fsbdd-orders-table td:nth-child(4) { min-width: 180px; } /* Dates & Lieu */
        .fsbdd-orders-table th:nth-child(5), .fsbdd-orders-table td:nth-child(5) { width: 120px; text-align: center; } /* Statut */
        .fsbdd-orders-table td:nth-child(5) span { margin: auto; }
        .fsbdd-orders-table th:nth-child(6), .fsbdd-orders-table td:nth-child(6) { width: 60px; text-align: center; } /* Effectif */
        .fsbdd-orders-table th:nth-child(7), .fsbdd-orders-table td:nth-child(7) { width: 60px; text-align: center; } /* OPCO */
        .fsbdd-orders-table th:nth-child(8), .fsbdd-orders-table td:nth-child(8) { width: 90px; text-align: center; } /* Documents */
        .fsbdd-orders-table th:nth-child(9), .fsbdd-orders-table td:nth-child(9) { min-width: 180px; } /* Formateurs - Élargi */
        .fsbdd-orders-table th:nth-child(10), .fsbdd-orders-table td:nth-child(10) { min-width: 80px; } /* Referent */
        .fsbdd-orders-table th:nth-child(11), .fsbdd-orders-table td:nth-child(11) { width: 100px; } /* Declenchement */
        .fsbdd-orders-table th:nth-child(12), .fsbdd-orders-table td:nth-child(12) { width: 100px; } /* Suivi Realise */
        
        /* Format de date sur une seule ligne */
        .fsbdd-orders-table td:nth-child(4) {
            font-size: 11px;
            line-height: 1.3;
            white-space: nowrap; /* Pour éviter que les dates se cassent */
        }

        /* Limiter le texte des colonnes Session à 2 lignes max et tronquer */
        .fsbdd-orders-table td:nth-child(3) {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: grid;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    font-size: 12px;
    line-height: 0.8;
}

        /* Formatter les formateurs */
        .fsbdd-orders-table td:nth-child(9) {
            font-size: 11px;
            line-height: 1.3;
        }
        
        /* Style pour la colonne Affaire/Convention fusionnée */
        .fsbdd-orders-table td:nth-child(1) {
            font-size: 12px;
        }
        
        .fsbdd-orders-table td:nth-child(1) .convention-num {
            font-weight: 600;
            color: #0073aa;
        }
        
        .fsbdd-orders-table td:nth-child(1) .order-id {
            font-size: 11px;
            color: #888;
            margin-top: 3px;
        }

        /* Assurer que len-tête reste visible pendant le défilement */
        .fsbdd-orders-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f0f0f0;
        }


		
		
    </style>';

    // Début du conteneur du tableau
    echo '<div id="fsbdd-table-container" class="fsbdd-table-container">';

    // Zone pour les résultats filtrés (sera remplie par JavaScript)
    echo '<div id="fsbdd-filtered-count"></div>';

    // Structure pour double scrollbar - Nouveau
    echo '<div class="fsbdd-double-scroll-container">';
    
    // Conteneur de scrollbar du haut - Nouveau
    echo '<div class="fsbdd-top-scrollbar-container" id="fsbdd-top-scrollbar">';
    echo '<div class="fsbdd-top-scrollbar-content" id="fsbdd-top-scrollbar-content"></div>';
    echo '</div>';
    
    // Tableau avec sa scrollbar naturelle
    echo '<div class="fsbdd-table-wrapper" id="fsbdd-table-wrapper">';
    echo '<table id="fsbdd-orders-table" class="fsbdd-orders-table">';
    echo '<thead>
            <tr>
                <th data-sort="text">Affaire</th>
                <th data-sort="text">Client</th>
                <th data-sort="text">Session</th>
                <th data-sort="date">Dates & Lieu</th>
                <th data-sort="text">Statut</th>
                <th data-sort="number">Effectif</th>
                <th data-sort="text">OPCO</th>
                <th>Docs</th>
                <th data-sort="text">Formateurs</th>
                <th data-sort="text">Référent</th>
                <th data-sort="text">Déclench.</th>
                <th data-sort="text">Suivi</th>
            </tr>
          </thead>';

    echo '<tbody>';

    // Récupérer les données
    // Condition temporairement commentée pour afficher toutes les données aux référents
    // $orders_data = get_consultant_orders_data($is_referent ? $user_id : null, 1600);
    $orders_data = get_consultant_orders_data(null, 1600);

    if (empty($orders_data)) {
        echo '<tr><td colspan="13" style="text-align:center; padding: 20px;">Aucune action de formation trouvée pour les critères actuels.</td></tr>';
    } else {
        // Trier par date de début de session (la plus proche en premier)
        usort($orders_data, function($a, $b) {
            $ts_a = $a['start_timestamp'] ?? 0;
            $ts_b = $b['start_timestamp'] ?? 0;

            if ($ts_a == 0 && $ts_b == 0) return 0;
            if ($ts_a == 0) return 1;
            if ($ts_b == 0) return -1;

            // Comparer la proximité avec aujourd'hui
            $today = time();
            $diff_a = abs($ts_a - $today);
            $diff_b = abs($ts_b - $today);

            // Si une est future et l'autre passée, la future vient avant
            $is_future_a = $ts_a >= $today;
            $is_future_b = $ts_b >= $today;
            if ($is_future_a && !$is_future_b) return -1;
            if (!$is_future_a && $is_future_b) return 1;

            // Si les deux sont futures, la plus proche vient avant
            if ($is_future_a && $is_future_b) {
                return $ts_a <=> $ts_b;
            }

            // Si les deux sont passées, la plus récente vient avant
            if (!$is_future_a && !$is_future_b) {
                return $ts_b <=> $ts_a;
            }

            return 0;
        });

        foreach ($orders_data as $order_data) {
            // Nettoyer le statut WC ('wc-' prefix) pour le badge CSS et data-status
            $clean_status = str_replace('wc-', '', $order_data['status']);
            $status_class = 'fsbdd-badge-' . $clean_status;

            // Classes pour les icones de documents
            $doc_emargements_class = 'fsbdd-badge-' . ($order_data['emargements'] ?? '1');
            $doc_cpterendu_class = 'fsbdd-badge-' . ($order_data['cpte_rendu'] ?? '1');
            $doc_evaluations_class = 'fsbdd-badge-' . ($order_data['evaluations'] ?? '1');

            // Ajouter tous les attributs data-* nécessaires pour le filtrage
            echo '<tr data-order-id="' . esc_attr($order_data['order_id']) . '"
			data-ttrglmts="' . esc_attr($order_meta['fsbdd_ttrglmts'][0] ?? '0') . '" 
data-montcattc="' . esc_attr($order_meta['fsbdd_montcattc'][0] ?? '0') . '"
                     data-order-number="' . esc_attr($order_data['order_number']) . '"
                     data-client-name="' . esc_attr($order_data['client_name']) . '"
                     data-client-has-cpt="' . ((!empty($order_data['client_cpt_id']) && !empty($order_data['client_cpt_type'])) ? 'yes' : 'no') . '"
                     data-session-title="' . esc_attr($order_data['session_title']) . '"
					 data-inter-numero="' . esc_attr($order_data['inter_numero'] ?? '') . '" 
                     data-start-date="' . esc_attr($order_data['start_date']) . '"
                     data-end-date="' . esc_attr($order_data['end_date']) . '"
                     data-start-timestamp="' . esc_attr($order_data['start_timestamp']) . '"
                     data-end-timestamp="' . esc_attr($order_data['end_timestamp']) . '"
                     data-status="' . esc_attr($order_data['status']) . '"
                     data-status-name="' . esc_attr($order_data['status_name']) . '"
                     data-referent-id="' . esc_attr($order_data['referent_id'] ?? '') . '"
                     data-referent-name="' . esc_attr($order_data['referent_name']) . '"
                     data-emargements="' . esc_attr($order_data['emargements']) . '"
                     data-cpte-rendu="' . esc_attr($order_data['cpte_rendu']) . '"
                     data-evaluations="' . esc_attr($order_data['evaluations']) . '"
                     data-formation-erreur="' . (isset($order_data['formation_erreur']) && $order_data['formation_erreur'] ? 'oui' : 'non') . '"
                     data-convention-envoyee="' . (isset($order_data['convention_envoyee']) && $order_data['convention_envoyee'] ? 'oui' : 'non') . '"
                     data-convention-signee-recue="' . (isset($order_data['convention_signee_recue']) && $order_data['convention_signee_recue'] ? 'oui' : 'non') . '"
                     data-stagiaires-renseignes="' . (isset($order_data['stagiaires_renseignes']) && $order_data['stagiaires_renseignes'] ? 'oui' : 'non') . '"
                     data-convocations-status="' . esc_attr($order_data['convocations_status'] ?? '') . '"
                     data-formateur-status="' . esc_attr($order_data['formateur_status'] ?? '') . '"
                     data-inter-status="' . esc_attr($order_data['inter_status'] ?? '') . '"
                     data-formateurs-lm-status="' . esc_attr($order_data['formateurs_lm_status'] ?? '') . '"
                     data-inter-elements-status="' . esc_attr($order_data['inter_elements_status'] ?? '') . '"
                     data-option-status="' . esc_attr($order_data['option_status'] ?? '') . '"
                     data-inscription-date="' . esc_attr($order_data['inscription_date'] ?? '') . '"
                     data-confirme-date="' . esc_attr($order_data['confirme_date'] ?? '') . '"
                     data-suivi-realise="' . esc_attr($order_data['suivi_realise'] ?? '') . '"
                     data-suivireal-date="' . esc_attr($order_data['suivireal_date'] ?? '') . '"
                     data-recepmargmts="' . esc_attr($order_data['recepmargmts'] ?? '') . '"
                     data-datemargmts="' . esc_attr($order_data['datemargmts'] ?? '') . '"
                     data-recepcpterenduf="' . esc_attr($order_data['recepcpterenduf'] ?? '') . '"
                     data-datecpterenduf="' . esc_attr($order_data['datecpterenduf'] ?? '') . '"
                     data-recepeval="' . esc_attr($order_data['recepeval'] ?? '') . '"
                     data-dateeval="' . esc_attr($order_data['dateeval'] ?? '') . '"
                     data-last-status-change="' . esc_attr($order_data['last_status_change'] ?? '') . '"
					 data-datefact="' . esc_attr($order_data['datefact'] ?? '') . '"
					 data-facturesent-date="' . esc_attr($order_data['facturesent_date'] ?? '') . '"
					 data-suivifactu="' . esc_attr($order_data['suivifactu'] ?? '') . '"
					 data-datefinfact="' . esc_attr($order_data['datefinfact'] ?? '') . '"
					 data-factureok-date="' . esc_attr($order_data['factureok_date'] ?? '') . '"
					 data-fsbdd-rappro-session="' . esc_attr($order_data['rappro_session'] ?? '0') . '"
                     data-fsbdd-rappro-specificites="' . esc_attr($order_data['rappro_specificites'] ?? '0') . '"
                     data-fsbdd-rappro-convocations="' . esc_attr($order_data['rappro_convocations'] ?? '0') . '"
                     data-fsbdd-rappro-quantites-couts="' . esc_attr($order_data['rappro_quantites_couts'] ?? '0') . '"
                     data-fsbdd-rappro-subro-reglements="' . esc_attr($order_data['rappro_subro_reglements'] ?? '0') . '"
                     data-fsbdd-rappro-client-bdd-web="' . esc_attr($order_data['rappro_client_bdd_web'] ?? '0') . '"
                     data-convention="' . esc_attr($order_data['convention'] ?? '') . '">';
			

            // Colonne fusionnée: Affaire/Convention
            echo '<td>';
            echo '<div style="display: flex; justify-content: space-between; align-items: center;">';
            echo '<div>';
            if (!empty($order_data['convention']) && trim($order_data['convention']) !== '') {
                // Si convention existe, l'afficher en priorité avec un lien vers la commande
                echo '<a href="' . esc_url(admin_url('post.php?post=' . $order_data['order_id'] . '&action=edit')) . '" target="_blank" title="Voir la commande">';
                echo '<span class="convention-num">' . esc_html($order_data['convention']) . '</span>';
                echo '</a>';
                // Afficher le numéro de commande en plus petit en dessous
                echo '<div class="order-id">#' . esc_html($order_data['order_number']) . '</div>';
            } else {
                // Sinon afficher juste le numéro de commande comme avant
                echo '<a href="' . esc_url(admin_url('post.php?post=' . $order_data['order_id'] . '&action=edit')) . '" target="_blank">#' . esc_html($order_data['order_number']) . '</a>';
            }
            echo '</div>';
            
            // Indicateur de rapprochement pour l'affaire (aligné à droite)
            $rappro_session = $order_data['rappro_session'] === '1';
            $rappro_specificites = $order_data['rappro_specificites'] === '1';
            $rappro_convocations = $order_data['rappro_convocations'] === '1';
            $rappro_quantites = $order_data['rappro_quantites_couts'] === '1';
            $rappro_subro = $order_data['rappro_subro_reglements'] === '1';
            $rappro_client = $order_data['rappro_client_bdd_web'] === '1';
            
            $completed_steps = array_sum([$rappro_session, $rappro_specificites, $rappro_convocations, $rappro_quantites, $rappro_subro, $rappro_client]);
            
            if ($completed_steps === 6) {
                $rappro_status = 'OK';
                $rappro_color = '#81c784'; // Vert pastel éclairci
            } else {
                $rappro_status = $completed_steps . '/6';
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
            
            echo '<span class="rappro-status" style="color: ' . $rappro_color . '; font-weight: bold; margin-left: 8px;">' . $rappro_status . '</span>';
            echo '</div>';
            echo '</td>';

            // Colonne: Client (avec lien CPT si disponible)
            echo '<td>';
            if (!empty($order_data['client_cpt_id']) && !empty($order_data['client_cpt_type'])) {
                echo '<a href="' . esc_url(admin_url('post.php?post=' . $order_data['client_cpt_id'] . '&action=edit')) . '" target="_blank" title="Voir fiche ' . esc_attr($order_data['client_cpt_type']) . '">' . esc_html($order_data['client_name']) . '</a>';
            } else {
                echo esc_html($order_data['client_name'] ?? 'N/A');
            }
            echo '</td>';

            // Colonne: Session (avec lien si disponible)
            echo '<td>';
            echo '<div style="position: relative;">';
            if (!empty($order_data['session_id'])) {
                // Use session_title for the link text
                $link_text = esc_html($order_data['session_title'] ?? 'Session sans titre');
                
                echo '<a href="' . esc_url(admin_url('post.php?post=' . $order_data['session_id'] . '&action=edit')) . '" target="_blank" title="Voir action de formation">' . $link_text . '</a>';
                
                // Add the formation name (product name) if it exists
                if (!empty($order_data['formation']) && $order_data['formation'] !== 'N/A') {
                    echo '<br><small>' . esc_html($order_data['formation']) . '</small>';
                }
                
                // Pastille de rapprochement de session (en haut à droite)
                // Déterminer l'état global du rapprochement pour cette session
                if ($completed_steps === 6) {
                    $session_indicator_color = '#c8e6c9'; // Vert pastel
                    $session_indicator_title = 'Rapprochements complets';
                } elseif ($completed_steps > 0) {
                    $session_indicator_color = '#ffe0b2'; // Orange pastel
                    $session_indicator_title = 'Rapprochements en cours';
                } else {
                    $session_indicator_color = '#ffcdd2'; // Rouge pastel
                    $session_indicator_title = 'Aucun rapprochement commencé';
                }
                
                echo '<div class="session-rappro-indicator" style="position: absolute; top: 0; right: 0; width: 12px; height: 12px; border-radius: 50%; background-color: ' . $session_indicator_color . '; border: 1px solid #ddd;" title="' . $session_indicator_title . '"></div>';
            } else {
                echo 'Non définie';
                if (!empty($order_data['formation']) && $order_data['formation'] !== 'N/A') {
                    echo '<br><small>' . esc_html($order_data['formation']) . '</small>';
                }
            }
            echo '</div>';
            echo '</td>';

            // Suppression de la colonne Formation qui est maintenant fusionnée avec Session
            // Colonne: Dates & Lieu
            echo '<td>';
            $date_str = '';
            if (!empty($order_data['start_date'])) {
                $date_str = esc_html($order_data['start_date']);
                if (!empty($order_data['end_date']) && $order_data['start_date'] !== $order_data['end_date']) {
                    $date_str .= ' - ' . esc_html($order_data['end_date']);
                }
            } else {
                $date_str = 'Date N/D';
            }
            echo '<div>' . $date_str . '</div><small>' . esc_html($order_data['lieu_resume'] ?? 'Lieu N/D') . '</small>';
            echo '</td>';

            // Colonne: Statut Commande
            echo '<td style="text-align: center;"><span class="fsbdd-badge ' . esc_attr($status_class) . '">' . esc_html($order_data['status_name'] ?? $clean_status) . '</span></td>';

            // Colonne: Effectif
            echo '<td style="text-align: center;">' . esc_html($order_data['effectif'] ?? '0') . '</td>';

            // Colonne: OPCO
            echo '<td style="text-align: center;">' . esc_html(strtoupper($order_data['opco'] ?? 'NON')) . '</td>';

            // Colonne: Documents
            echo '<td style="white-space: nowrap; text-align: center;">';
            echo '<span class="dashicons dashicons-yes-alt" style="color: ' . get_doc_color($doc_emargements_class) . ';" title="Émargements: ' . esc_attr($order_data['emargements_text'] ?? 'Vide') .'"></span>';
            echo '<span class="dashicons dashicons-text-page" style="color: ' . get_doc_color($doc_cpterendu_class) . ';" title="Compte rendu: ' . esc_attr($order_data['cpte_rendu_text'] ?? 'Vide') .'"></span>';
            echo '<span class="dashicons dashicons-chart-bar" style="color: ' . get_doc_color($doc_evaluations_class) . ';" title="Évaluations: ' . esc_attr($order_data['evaluations_text'] ?? 'Vide') .'"></span>';
            echo '</td>';

            // Colonne: Formateurs
            echo '<td>' . esc_html($order_data['formateurs'] ?: 'N/D') . '</td>';

            // Colonne: Référent
            echo '<td>' . esc_html($order_data['referent_name'] ?? 'N/A') . '</td>';

            // Colonne: Déclenchement
            echo '<td style="text-align: center;">' . esc_html($order_data['declenchement'] ?? 'N/A') . '</td>';

            // Colonne: Suivi Réalisé
            echo '<td style="text-align: center;">' . esc_html(ucfirst($order_data['suivi_realise'] ?? 'non')) . '</td>';

            echo '</tr>';
        }
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>'; // Fin du wrapper table

    // Actions et pagination/affichage complet
    echo '<div class="fsbdd-action-buttons">';
    echo '<div class="fsbdd-actions-links">';
    echo '<a href="' . esc_url(admin_url('edit.php?post_type=action-de-formation')) . '" class="fsbdd-actions-link">Toutes les actions de formation</a>';
    echo '<a href="' . esc_url(admin_url('edit.php?post_type=shop_order')) . '" class="fsbdd-actions-link">Toutes les commandes</a>';
    echo '</div>';
    // Le bouton change de texte selon l'état
    echo '<span id="fsbdd-show-all" class="fsbdd-show-all">Afficher tout</span>';
    echo '</div>';

    // Fermeture de la structure
    echo '</div>'; // Ferme .fsbdd-table-wrapper
    echo '</div>'; // Ferme .fsbdd-double-scroll-container
    
    // Script pour synchroniser les barres de défilement - Nouveau
    echo '<script>
    jQuery(document).ready(function($) {
        var itemsPerPage = 20;
        var currentPage = 1;
        var showAll = false; // Initialisé à false pour afficher seulement 20 éléments par défaut
        var allRowsCache = $("#fsbdd-orders-table tbody tr");
        
        // Détecter les événements de filtre (déclenchés par le snippet des filtres)
        $(document).on("fsbdd-filter-changed", function(e, filteredRows) {
            updateTable(filteredRows || allRowsCache);
        });
        
        // Répondre à la demande de lignes filtrées
        $(document).on("fsbdd-request-filtered-rows", function() {
            // Récupérer les lignes actuellement filtrées (visibles ou non)
            var currentFilteredRows = allRowsCache.filter(function() {
                return $(this).css("display") !== "none" || $(this).data("filtered") !== "hidden";
            });
            
            updateTable(currentFilteredRows);
        });
        
        // Mettre à jour l\'affichage du tableau
        function updateTable(filteredRows) {
            // Cacher toutes les lignes
            allRowsCache.hide();
            
            // Mettre à jour le texte du bouton en fonction de l\'état
            if (showAll) {
                // Afficher toutes les lignes filtrées
                filteredRows.show();
                $("#fsbdd-show-all").text("Afficher les " + itemsPerPage + " premières");
            } else {
                // Afficher seulement les premières lignes
                var start = (currentPage - 1) * itemsPerPage;
                var end = start + itemsPerPage;
                filteredRows.slice(start, end).show();
                $("#fsbdd-show-all").text("Afficher tout (" + filteredRows.length + ")");
            }
            
            // Stocker l\'information de filtrage sur les lignes pour les retrouver plus tard
            filteredRows.data("filtered", "visible");
            allRowsCache.not(filteredRows).data("filtered", "hidden");
            
            updateFilterCount(filteredRows.length);
            
            // Mettre à jour la largeur de la barre de défilement du haut après filtrage
            updateTopScrollbarWidth();
        }
        
        // Mettre à jour le compteur de résultats
        function updateFilterCount(visibleCount) {
            var text = visibleCount + " résultat(s) trouvé(s)";
            
            if (!showAll && visibleCount > itemsPerPage) {
                var start = (currentPage - 1) * itemsPerPage + 1;
                var end = Math.min(start + itemsPerPage - 1, visibleCount);
                text = "Affichage de " + start + " à " + end + " sur " + visibleCount + " résultats";
            } else if (visibleCount > 0) {
                text = visibleCount + " résultat(s) affiché(s)";
            }
            
            $("#fsbdd-filtered-count").text(text);
            
            // N\'afficher le bouton que s\'il y a plus d\'entrées que itemsPerPage
            $("#fsbdd-show-all").toggle(visibleCount > itemsPerPage);
            
            if (visibleCount <= itemsPerPage) {
                showAll = true;
            }
        }
        
        // Bouton Afficher tout / par page
        $("#fsbdd-show-all").on("click", function() {
            showAll = !showAll;
            currentPage = 1;
            
            // Récupérer les lignes filtrées actuelles
            var currentFilteredRows = allRowsCache.filter(function() {
                return $(this).data("filtered") === "visible";
            });
            
            updateTable(currentFilteredRows.length > 0 ? currentFilteredRows : allRowsCache);
        });
        
        // Tri des colonnes
        $(".fsbdd-orders-table th[data-sort]").on("click", function() {
            var sortType = $(this).data("sort");
            var columnIndex = $(this).index();
            var rows = allRowsCache.toArray();
            var direction = $(this).hasClass("fsbdd-sort-asc") ? -1 : 1;
            
            // Réinitialiser les classes de tri sur toutes les colonnes
            $(".fsbdd-orders-table th").removeClass("fsbdd-sort-asc fsbdd-sort-desc");
            
            // Ajouter la classe de tri à la colonne actuelle
            $(this).addClass(direction === 1 ? "fsbdd-sort-asc" : "fsbdd-sort-desc");
            
            rows.sort(function(a, b) {
                var aValue, bValue;
                
                if (sortType === "number") {
                    aValue = parseFloat($(a).find("td").eq(columnIndex).text()) || 0;
                    bValue = parseFloat($(b).find("td").eq(columnIndex).text()) || 0;
                } else if (sortType === "date") {
                    // Pour les dates, utiliser l\'attribut data-start-timestamp
                    aValue = parseInt($(a).data("start-timestamp")) || 0;
                    bValue = parseInt($(b).data("start-timestamp")) || 0;
                } else {
                    // Tri par texte par défaut
                    aValue = $(a).find("td").eq(columnIndex).text().toLowerCase();
                    bValue = $(b).find("td").eq(columnIndex).text().toLowerCase();
                }
                
                if (sortType === "number" || sortType === "date") {
                    return (aValue - bValue) * direction;
                } else {
                    return aValue.localeCompare(bValue) * direction;
                }
            });
            
            // Réattacher les lignes triées
            var tbody = $(".fsbdd-orders-table tbody");
            $.each(rows, function(index, row) {
                tbody.append(row);
            });
            
            // Mettre à jour l\'affichage après le tri
            allRowsCache = $("#fsbdd-orders-table tbody tr");
            
            // Récupérer les lignes filtrées actuelles
            var currentFilteredRows = allRowsCache.filter(function() {
                return $(this).data("filtered") === "visible";
            });
            
            updateTable(currentFilteredRows.length > 0 ? currentFilteredRows : allRowsCache);
        });
        
        // Initialisation au chargement - Afficher seulement les premières lignes
        updateTable(allRowsCache);
        
        // Configuration pour la barre de défilement du haut - Nouveau
        function updateTopScrollbarWidth() {
            // Récupérer la largeur réelle du tableau
            var tableWidth = $("#fsbdd-orders-table").width();
            // Définir la même largeur pour le contenu de la scrollbar du haut
            $("#fsbdd-top-scrollbar-content").width(tableWidth);
        }
        
        // Synchroniser le défilement entre les deux barres - Nouveau
        $("#fsbdd-top-scrollbar").on("scroll", function() {
            $("#fsbdd-table-wrapper").scrollLeft($(this).scrollLeft());
        });
        
        $("#fsbdd-table-wrapper").on("scroll", function() {
            $("#fsbdd-top-scrollbar").scrollLeft($(this).scrollLeft());
        });
        
        // Mettre à jour la largeur si la fenêtre change de taille - Nouveau
        $(window).on("resize", updateTopScrollbarWidth);
    });
    </script>';

    echo '</div>'; // Fin du conteneur table
}

/**
 * Récupère les données des commandes et sessions associées
 * Enrichit les données pour permettre le filtrage
 */
function get_consultant_orders_data($referent_id = null, $limit = 1600) {
    global $wpdb;

    // Récupérer les commandes récentes/pertinentes
    $query_args = array(
        'limit'   => $limit,
        'orderby' => 'date',
        'order'   => 'DESC',
        'return'  => 'ids',
    );

    // Filtre par référent si spécifié (rôle 'referent')
    if ($referent_id) {
        $query_args['meta_key'] = 'fsbdd_user_referentrel';
        $query_args['meta_value'] = $referent_id;
        $query_args['meta_compare'] = '=';
    }

    $order_ids = wc_get_orders($query_args);

    if (empty($order_ids)) {
        return array();
    }

    // Clés de métadonnées nécessaires
    $needed_order_meta_keys = [
        'fsbdd_user_referentrel', 'fsbdd_financeopco', 'fsbdd_effectif',
        'fsbdd_numconv', 'fsbdd_suivireal', '_customer_user',
        'fsbdd_convention_status', 'fsbdd_formation_erreur',
        'fsbdd_convention_envoyee', 'fsbdd_convention_signee_recue',
        'fsbdd_stagiaires_renseignes', 'fsbdd_convocations_status',
        'fsbdd_opco_dossier_number', 'fsbdd_opco_dossier_recu_date',
        'fsbdd_devis_last_relance_date', 'fsbdd_option_date',
        '_inscription_date', '_confirme_date', 'fsbdd_etatsuivi', 
        'fsbdd_suivipret', 'fsbdd_datefact', '_facturesent_date', 
        'fsbdd_suivifactu', 'fsbdd_datefinfact', '_factureok_date',
        'fsbdd_soldopco', 'fsbdd_soldeclient','fsbdd_ttrglmts', 'fsbdd_montcattc'
    ];
    
    $needed_session_meta_keys = [
        'fsbdd_inter_numero', // Added for Session column
        'we_startdate', 'we_enddate', 'fsbdd_select_lieusession',
        'fsbdd_etatemargm', 'fsbdd_etatcpterenduf', 'fsbdd_etateval',
        'fsbdd_planning', 'fsbdd_formateurs_status', 'fsbdd_inter_status',
        'fsbdd_formateurs_lm_status', 'fsbdd_inter_elements_status',
        'fsbdd_option_status', 'fsbdd_recepmargmts', 'fsbdd_datemargmts',
        'fsbdd_recepcpterenduf', 'fsbdd_datecpterenduf', 'fsbdd_recepeval',
        'fsbdd_dateeval'
    ];

    // Récupérer les relations user_id => CPT client/prospect
    $customer_user_ids = [];
    foreach($order_ids as $order_id) {
        $user_id = get_post_meta($order_id, '_customer_user', true);
        if ($user_id) {
            $customer_user_ids[$user_id] = $user_id;
        }
    }
    
    $client_relations = [];
    if (!empty($customer_user_ids)) {
        $rel_table = $wpdb->prefix . 'mb_relationships';
        $allowed_rel_types = "'clients-wp-bdd', 'prospects-wp-bdd'";
        $user_ids_string = implode(',', array_map('intval', $customer_user_ids));

        if ($wpdb->get_var("SHOW TABLES LIKE '$rel_table'") == $rel_table) {
            $results = $wpdb->get_results(
                "SELECT `from`, `to`, `type` 
                 FROM {$rel_table} 
                 WHERE `from` IN ($user_ids_string) AND `type` IN ($allowed_rel_types)",
                ARRAY_A
            );
            
            if ($results) {
                foreach ($results as $row) {
                    $client_relations[$row['from']] = [
                        'id' => $row['to'],
                        'type' => strpos($row['type'], 'client') !== false ? 'client' : 'prospect'
                    ];
                }
            }
        }
    }

    // Traitement des commandes pour construire le tableau de données
    $result = array();
    $referent_cache = [];
    $doc_status_map = [
        '1' => 'Vide',
        '2' => 'Partiel',
        '3' => 'Reçus',
        '4' => 'Certifié',
        ''  => 'Vide',
        null => 'Vide'
    ];

    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) continue;

        $order_meta = get_post_meta($order_id);

        // Données de la commande
        $status = $order->get_status();
        $status_name = wc_get_order_status_name($status);
        $customer_user_id = $order_meta['_customer_user'][0] ?? null;

        // Référent
        $referent_id_meta = $order_meta['fsbdd_user_referentrel'][0] ?? null;
        $referent_name = 'N/A';
        if ($referent_id_meta) {
            if (!isset($referent_cache[$referent_id_meta])) {
                $user_info = get_userdata($referent_id_meta);
                if ($user_info) {
                    $first_name = trim($user_info->first_name);
                    if (!empty($first_name)) {
                        $referent_cache[$referent_id_meta] = $first_name;
                    } else {
                        // Fallback to the first part of display_name if first_name is empty
                        $display_name_parts = explode(' ', $user_info->display_name, 2); // Get only the first part
                        $referent_cache[$referent_id_meta] = $display_name_parts[0];
                    }
                } else {
                    $referent_cache[$referent_id_meta] = 'ID: ' . $referent_id_meta; 
                }
            }
            $referent_name = $referent_cache[$referent_id_meta];
        }

        // Client
        $billing_company = $order->get_billing_company();
        $customer_name = empty($billing_company) || strtolower($billing_company) === 'pas de société' 
            ? $order->get_formatted_billing_full_name() 
            : $billing_company;
        
        $client_cpt_id = null;
        $client_cpt_type = null;
        if ($customer_user_id && isset($client_relations[$customer_user_id])) {
            $client_cpt_id = $client_relations[$customer_user_id]['id'];
            $client_cpt_type = $client_relations[$customer_user_id]['type'];
        }

        // OPCO
        $opco_value = $order_meta['fsbdd_financeopco'][0] ?? '1';
        $opco = ($opco_value === '2') ? 'OUI' : 'NON';

        // Autres métadonnées de commande
        $effectif = $order_meta['fsbdd_effectif'][0] ?? '0';
        $convention = $order_meta['fsbdd_numconv'][0] ?? '';
        $suivi_realise_raw = $order_meta['fsbdd_suivireal'][0] ?? 'non';
        $suivi_realise = (strtolower($suivi_realise_raw) === 'oui' || $suivi_realise_raw === '1' || $suivi_realise_raw === true) ? 'oui' : 'non';

        // Métadonnées supplémentaires pour les alertes
        $convention_status = $order_meta['fsbdd_convention_status'][0] ?? '';
        $convention_envoyee = $order_meta['fsbdd_convention_envoyee'][0] ?? '';
        $convention_signee_recue = $order_meta['fsbdd_convention_signee_recue'][0] ?? '';
        $formation_erreur = $order_meta['fsbdd_formation_erreur'][0] ?? '';
        $stagiaires_renseignes = $order_meta['fsbdd_stagiaires_renseignes'][0] ?? '';
        $convocations_status = $order_meta['fsbdd_convocations_status'][0] ?? '';
        $opco_dossier_number = $order_meta['fsbdd_opco_dossier_number'][0] ?? '';
        $opco_dossier_recu_date = $order_meta['fsbdd_opco_dossier_recu_date'][0] ?? '';
        $devis_last_relance_date = $order_meta['fsbdd_devis_last_relance_date'][0] ?? '';
        $option_date = $order_meta['fsbdd_option_date'][0] ?? '';
        
        // Dates des conventions
        $inscription_date = $order_meta['_inscription_date'][0] ?? '';
        $confirme_date = $order_meta['_confirme_date'][0] ?? '';
        
        // Données de suivi
        $etatsuivi = $order_meta['fsbdd_etatsuivi'][0] ?? '';
        $suivipret = $order_meta['fsbdd_suivipret'][0] ?? '';
        $suivireal = $order_meta['fsbdd_suivireal'][0] ?? '';
        
        // Dates des factures
        $datefact = $order_meta['fsbdd_datefact'][0] ?? '';
        $facturesent_date = $order_meta['_facturesent_date'][0] ?? '';
        $suivifactu = $order_meta['fsbdd_suivifactu'][0] ?? '';
        $datefinfact = $order_meta['fsbdd_datefinfact'][0] ?? '';
        $factureok_date = $order_meta['_factureok_date'][0] ?? '';
        
        // Données financières
        $soldopco = $order_meta['fsbdd_soldopco'][0] ?? '';
        $soldeclient = $order_meta['fsbdd_soldeclient'][0] ?? '';
        
        // Données de rapprochement
        $rappro_session = $order_meta['fsbdd_rappro_session'][0] ?? '0';
        $rappro_specificites = $order_meta['fsbdd_rappro_specificites'][0] ?? '0';
        $rappro_convocations = $order_meta['fsbdd_rappro_convocations'][0] ?? '0';
        $rappro_quantites_couts = $order_meta['fsbdd_rappro_quantites_couts'][0] ?? '0';
        $rappro_subro_reglements = $order_meta['fsbdd_rappro_subro_reglements'][0] ?? '0';
        $rappro_client_bdd_web = $order_meta['fsbdd_rappro_client_bdd_web'][0] ?? '0';

        // Données liées à la session (Action de Formation)
        $session_id = null;
        $session_title = '';
        $inter_numero = ''; // Initialize inter_numero
        $product_name = 'N/A';
        $start_date = ''; $end_date = '';
        $start_timestamp = 0; $end_timestamp = 0;
        $lieu_resume = 'N/D';
        $emargements = '1'; $cpte_rendu = '1'; $evaluations = '1';
        $formateurs_string = '';
        $formateur_status = '';
        $inter_status = '';
        $formateurs_lm_status = '';
        $inter_elements_status = '';
        $option_status = '';

        // Trouver la session liée
        foreach ($order->get_items() as $item_id => $item) {
            $item_session_id = wc_get_order_item_meta($item_id, 'fsbdd_relsessaction_cpt_produit', true);
            if ($item_session_id && get_post_type($item_session_id) === 'action-de-formation') {
                $session_id = $item_session_id;
                // Récupérer le produit lié
                $product_id = $item->get_product_id();
                $product = wc_get_product($product_id);
                $product_name = $product ? $product->get_name() : 'Produit non trouvé';
                break;
            }
        }

        if ($session_id) {
            $session_post = get_post($session_id);
            $session_title = $session_post ? $session_post->post_title : 'Session ID ' . $session_id;
            $session_meta = get_post_meta($session_id);
            $inter_numero = $session_meta['fsbdd_inter_numero'][0] ?? ''; // Fetch inter_numero

            // Dates & Timestamps
            $start_timestamp = isset($session_meta['we_startdate'][0]) ? intval($session_meta['we_startdate'][0]) : 0;
            $end_timestamp = isset($session_meta['we_enddate'][0]) ? intval($session_meta['we_enddate'][0]) : 0;
            if ($start_timestamp) $start_date = date('d/m/Y', $start_timestamp);
            if ($end_timestamp) $end_date = date('d/m/Y', $end_timestamp);

            // Lieu
            $lieu_complet = $session_meta['fsbdd_select_lieusession'][0] ?? 'Lieu N/D';
            if ($lieu_complet && strpos($lieu_complet, ',') !== false) {
                $lieu_parts = explode(',', $lieu_complet);
                $lieu_resume = ucfirst(strtolower(trim($lieu_parts[0])));
            } else {
                $lieu_resume = $lieu_complet;
            }

            // États des documents
            $emargements = $session_meta['fsbdd_etatemargm'][0] ?? '1';
            $cpte_rendu = $session_meta['fsbdd_etatcpterenduf'][0] ?? '1';
            $evaluations = $session_meta['fsbdd_etateval'][0] ?? '1';

            // Métadonnées supplémentaires pour les alertes
            $formateur_status = $session_meta['fsbdd_formateurs_status'][0] ?? '';
            $inter_status = $session_meta['fsbdd_inter_status'][0] ?? '';
            $formateurs_lm_status = $session_meta['fsbdd_formateurs_lm_status'][0] ?? '';
            $inter_elements_status = $session_meta['fsbdd_inter_elements_status'][0] ?? '';
            $option_status = $session_meta['fsbdd_option_status'][0] ?? '';
            
            // Documents - Émargements
            $recepmargmts = $session_meta['fsbdd_recepmargmts'][0] ?? '';
            $datemargmts = $session_meta['fsbdd_datemargmts'][0] ?? '';
            
            // Documents - Comptes rendus
            $recepcpterenduf = $session_meta['fsbdd_recepcpterenduf'][0] ?? '';
            $datecpterenduf = $session_meta['fsbdd_datecpterenduf'][0] ?? '';
            
            // Documents - Évaluations
            $recepeval = $session_meta['fsbdd_recepeval'][0] ?? '';
            $dateeval = $session_meta['fsbdd_dateeval'][0] ?? '';

            // Formateurs (via Meta Box Group)
            $formateurs_list = [];
            if (function_exists('rwmb_meta')) {
                $formateurs_group = rwmb_meta('fsbdd_grpctsformation', ['object_type' => 'post'], $session_id);
                if (!empty($formateurs_group) && is_array($formateurs_group)) {
                    foreach ($formateurs_group as $formateur_data) {
                        if (!empty($formateur_data['fsbdd_selectcoutform'])) {
                            $formateur_id = $formateur_data['fsbdd_selectcoutform'];
                            if (is_numeric($formateur_id) && $formateur_post = get_post($formateur_id)) {
                                $formateurs_list[] = $formateur_post->post_title;
                            } elseif (is_string($formateur_id)) {
                                $formateurs_list[] = $formateur_id;
                            }
                        }
                    }
                }
            }
            $formateurs_string = !empty($formateurs_list) ? implode(', ', array_unique($formateurs_list)) : '';
        }

        // Déclenchement (depuis CPT Client si disponible)
        $declenchement = 'N/A';
        if ($client_cpt_id) {
            $declenchement_value = get_post_meta($client_cpt_id, 'fsbdd_select_suivi_declenchmt', true);
            $declenchement = ($declenchement_value === '1') ? 'OUI' : (($declenchement_value === '0') ? 'NON' : 'N/A');
        }

        // Assembler les données pour cette ligne
        $result[] = [
            'order_id' => $order_id,
            'order_number' => $order->get_order_number(),
            'client_name' => $customer_name,
            'client_cpt_id' => $client_cpt_id,
            'client_cpt_type' => $client_cpt_type,
            'session_id' => $session_id,
            'session_title' => $session_title,
            'inter_numero' => $inter_numero, // Added this field
            'formation' => $product_name ?? 'N/A',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'start_timestamp' => $start_timestamp,
            'end_timestamp' => $end_timestamp,
            'lieu_resume' => $lieu_resume,
            'convention' => $convention,
            'status' => $status,
            'status_name' => $status_name,
            'effectif' => $effectif,
            'opco' => $opco,
            'emargements' => $emargements,
            'cpte_rendu' => $cpte_rendu,
            'evaluations' => $evaluations,
            'emargements_text' => $doc_status_map[$emargements] ?? 'Vide',
            'cpte_rendu_text' => $doc_status_map[$cpte_rendu] ?? 'Vide',
            'evaluations_text' => $doc_status_map[$evaluations] ?? 'Vide',
            'referent_id' => $referent_id_meta,
            'referent_name' => $referent_name,
            'declenchement' => $declenchement,
            'suivi_realise' => $suivi_realise,
            'formateurs' => $formateurs_string,
            
            // Données supplémentaires pour les alertes
            'convention_status' => $convention_status,
            'convention_envoyee' => $convention_envoyee,
            'convention_signee_recue' => $convention_signee_recue,
            'formation_erreur' => $formation_erreur,
            'stagiaires_renseignes' => $stagiaires_renseignes,
            'convocations_status' => $convocations_status,
            'opco_dossier_number' => $opco_dossier_number,
            'opco_dossier_recu_date' => $opco_dossier_recu_date,
            'devis_last_relance_date' => $devis_last_relance_date,
            'formateur_status' => $formateur_status,
            'inter_status' => $inter_status,
            'formateurs_lm_status' => $formateurs_lm_status,
            'inter_elements_status' => $inter_elements_status,
            'option_date' => $option_date,
            'option_status' => $option_status,
            
            // Dates des conventions
            'inscription_date' => $inscription_date,
            'confirme_date' => $confirme_date,
            
            // Données de suivi
            'etatsuivi' => $etatsuivi,
            'suivipret' => $suivipret,
            'suivireal_date' => $suivireal,
            
            // Dates des factures
            'datefact' => $datefact,
            'facturesent_date' => $facturesent_date,
            'suivifactu' => $suivifactu,
            'datefinfact' => $datefinfact,
            'factureok_date' => $factureok_date,
            
            // Données financières
            'soldopco' => $soldopco,
            'soldeclient' => $soldeclient,
            
            // Données de rapprochement
            'rappro_session' => $rappro_session,
            'rappro_specificites' => $rappro_specificites,
            'rappro_convocations' => $rappro_convocations,
            'rappro_quantites_couts' => $rappro_quantites_couts,
            'rappro_subro_reglements' => $rappro_subro_reglements,
            'rappro_client_bdd_web' => $rappro_client_bdd_web,
            
            // Documents - Émargements
            'recepmargmts' => $recepmargmts,
            'datemargmts' => $datemargmts,
            
            // Documents - Comptes rendus
            'recepcpterenduf' => $recepcpterenduf,
            'datecpterenduf' => $datecpterenduf,
            
            // Documents - Évaluations
            'recepeval' => $recepeval,
            'dateeval' => $dateeval,
            
            // Date de dernière modification du statut
            'last_status_change' => $order->get_date_modified() ? $order->get_date_modified()->getTimestamp() : ''
        ];
    }

    return $result;
}




