<?php
/**
 * Snippet ID: 161
 * Name: REPORT alertes global accueil admin v2 cursor
 * Description: 
 * @active false
 */


/**
 * Titre: Tableau de Bord Formation Stratégique avec Alertes et Rapport
 * Description: Tableau de bord d'alertes avec filtres dynamiques pour visualiser les éléments concernés
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

class FS_Dashboard {
    
    public function __construct() {
        // Ajouter le menu dans l'admin
        add_action('admin_menu', array($this, 'register_dashboard_page'));
        // Hook pour AJAX
        add_action('wp_ajax_fs_get_filtered_data', array($this, 'ajax_get_filtered_data'));
        // Hooks pour les filtres sur les pages de listing
        add_action('restrict_manage_posts', array($this, 'add_custom_filters'));
        add_filter('parse_query', array($this, 'apply_custom_filters'));
    }
    
    // Enregistrer la page de tableau de bord
    public function register_dashboard_page() {
        add_menu_page(
            'Tableau de bord FS',
            'Tableau de bord',
            'manage_options',
            'fs-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-analytics',
            2
        );
    }
    
    // Afficher le tableau de bord
    public function render_dashboard() {
        // CSS inline pour le tableau de bord
        echo '<style>
            .fs-dashboard-container {
                display: flex;
                flex-direction: column;
                gap: 20px;
                margin-top: 20px;
            }
            
            .fs-alerts-panel {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 10px;
                background: #fff;
                padding: 15px;
                border-radius: 5px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            
            .fs-alert-card {
                padding: 12px;
                border-radius: 4px;
                background: #f9f9f9;
                border-left: 3px solid #ccc;
                transition: all 0.2s;
                cursor: pointer;
                position: relative;
                overflow: hidden;
            }
            
            .fs-alert-card:hover {
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                transform: translateY(-2px);
            }
            
            .fs-alert-card.fs-selected {
                background: #f0f7ff;
                border-left-color: #0073aa;
            }
            
            .fs-alert-title {
                font-size: 13px;
                font-weight: 600;
                margin: 0 0 8px;
                line-height: 1.3;
                display: flex;
                justify-content: space-between;
            }
            
            .fs-alert-count {
                background: #f1f1f1;
                border-radius: 100px;
                padding: 2px 8px;
                font-size: 12px;
                color: #444;
            }
            
            .fs-alert-card.fs-critical {
                background: #fff8f7;
                border-left-color: #d63638;
            }
            
            .fs-alert-card.fs-critical .fs-alert-count {
                background: #d63638;
                color: white;
            }
            
            .fs-alert-card.fs-warning {
                background: #fcf9e8;
                border-left-color: #dba617;
            }
            
            .fs-alert-card.fs-warning .fs-alert-count {
                background: #dba617;
                color: white;
            }
            
            .fs-alert-card.fs-info {
                background: #f0f6fc;
                border-left-color: #72aee6;
            }
            
            .fs-alert-card.fs-info .fs-alert-count {
                background: #72aee6;
                color: white;
            }
            
            .fs-category-title {
                font-size: 14px;
                font-weight: 600;
                margin: 0 0 12px;
                padding-bottom: 8px;
                border-bottom: 1px solid #eee;
            }
            
            .fs-category-alerts {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 10px;
                margin-bottom: 20px;
            }
            
            .fs-data-table-container {
                background: #fff;
                border-radius: 5px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                padding: 15px;
                overflow: auto;
            }
            
            .fs-data-table {
                width: 100%;
                border-collapse: collapse;
                text-align: left;
            }
            
            .fs-data-table th {
                background: #f1f1f1;
                padding: 10px;
                font-weight: 600;
                border-bottom: 1px solid #ddd;
            }
            
            .fs-data-table td {
                padding: 10px;
                border-bottom: 1px solid #eee;
            }
            
            .fs-data-table tr:hover {
                background: #f9f9f9;
            }
            
            .fs-filters-bar {
                display: flex;
                gap: 10px;
                margin-bottom: 15px;
                flex-wrap: wrap;
                align-items: center;
            }
            
            .fs-filter-badge {
                background: #f0f6fc;
                border-radius: 4px;
                padding: 5px 10px;
                font-size: 13px;
                display: flex;
                align-items: center;
                gap: 5px;
            }
            
            .fs-filter-badge .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                cursor: pointer;
            }
            
            .fs-filter-badge .dashicons:hover {
                color: #d63638;
            }
            
            .fs-loading {
                display: none;
                text-align: center;
                padding: 20px;
            }
            
            .fs-no-results {
                text-align: center;
                padding: 30px;
                color: #777;
                font-style: italic;
            }
        </style>';
        
        echo '<div class="wrap fs-dashboard">';
        echo '<h1>Tableau de bord Formation Stratégique</h1>';
        
        echo '<div class="fs-dashboard-container">';
        
        // Section des alertes
        echo '<div class="fs-alerts-panel">';
        
        // Alertes administratives
        $this->render_category('Administration', $this->get_admin_alerts());
        
        // Alertes formateurs
        $this->render_category('Formateurs', $this->get_formateur_alerts());
        
        // Alertes formations
        $this->render_category('Formations', $this->get_formation_alerts());
        
        // Alertes financières
        $this->render_category('Finances', $this->get_financial_alerts());
        
        // Alertes OPCO
        $this->render_category('OPCO', $this->get_opco_alerts());
        
        echo '</div>'; // .fs-alerts-panel
        
        // Section de tableau des données filtrées
        echo '<div class="fs-data-table-container">';
        
        // Barre de filtres actifs
        echo '<div class="fs-filters-bar" id="fs-active-filters"></div>';
        
        // Indicateur de chargement
        echo '<div class="fs-loading" id="fs-loading">Chargement des données...</div>';
        
        // Tableau des données
        echo '<table class="fs-data-table" id="fs-data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Titre</th>
                    <th>Statut</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="fs-data-rows">
                <tr>
                    <td colspan="7" class="fs-no-results">Sélectionnez une alerte pour afficher les éléments correspondants</td>
                </tr>
            </tbody>
        </table>';
        
        echo '</div>'; // .fs-data-table-container
        
        echo '</div>'; // .fs-dashboard-container
        
        // JavaScript pour gérer les interactions
        echo '<script type="text/javascript">
            jQuery(document).ready(function($) {
                var activeFilters = [];
                
                // Clic sur une alerte
                $(".fs-alert-card").on("click", function() {
                    var alertId = $(this).data("alert-id");
                    var alertType = $(this).data("alert-type");
                    var alertTitle = $(this).find(".fs-alert-title").text().trim();
                    
                    // Vérifier si le filtre est déjà actif
                    var filterExists = false;
                    
                    for (var i = 0; i < activeFilters.length; i++) {
                        if (activeFilters[i].id === alertId) {
                            filterExists = true;
                            break;
                        }
                    }
                    
                    // Si le filtre n\'existe pas encore, l\'ajouter
                    if (!filterExists) {
                        activeFilters.push({
                            id: alertId,
                            type: alertType,
                            title: alertTitle
                        });
                        
                        // Mettre à jour l\'UI pour montrer le filtre sélectionné
                        $(this).addClass("fs-selected");
                        
                        // Mettre à jour la barre de filtres
                        refreshFilterBar();
                        
                        // Charger les données filtrées
                        loadFilteredData();
                    }
                });
                
                // Fonction pour rafraîchir la barre de filtres
                function refreshFilterBar() {
                    var filterBar = $("#fs-active-filters");
                    filterBar.empty();
                    
                    if (activeFilters.length === 0) {
                        return;
                    }
                    
                    // Ajouter un badge pour chaque filtre actif
                    activeFilters.forEach(function(filter, index) {
                        var badge = $("<div>")
                            .addClass("fs-filter-badge")
                            .html(filter.title + " <span class=\'dashicons dashicons-no\'></span>")
                            .attr("data-index", index);
                        
                        badge.find(".dashicons").on("click", function(e) {
                            e.stopPropagation();
                            
                            // Retirer la classe sélectionnée de l\'alerte
                            $(".fs-alert-card[data-alert-id=\'" + filter.id + "\']").removeClass("fs-selected");
                            
                            // Supprimer le filtre du tableau
                            activeFilters.splice(index, 1);
                            
                            // Actualiser la barre de filtres
                            refreshFilterBar();
                            
                            // Recharger les données
                            loadFilteredData();
                        });
                        
                        filterBar.append(badge);
                    });
                }
                
                // Fonction pour charger les données filtrées
                function loadFilteredData() {
                    // Afficher le chargement
                    $("#fs-loading").show();
                    $("#fs-data-rows").empty();
                    
                    // Si aucun filtre actif, effacer le tableau
                    if (activeFilters.length === 0) {
                        $("#fs-loading").hide();
                        $("#fs-data-rows").html("<tr><td colspan=\'7\' class=\'fs-no-results\'>Sélectionnez une alerte pour afficher les éléments correspondants</td></tr>");
                        return;
                    }
                    
                    // Préparer les données pour la requête AJAX
                    var data = {
                        action: "fs_get_filtered_data",
                        filters: activeFilters
                    };
                    
                    // Appeler l\'API WordPress pour récupérer les données
                    $.post(ajaxurl, data, function(response) {
                        $("#fs-loading").hide();
                        
                        if (response.success && response.data.length > 0) {
                            var rows = "";
                            
                            response.data.forEach(function(item) {
                                rows += "<tr>";
                                rows += "<td>" + item.id + "</td>";
                                rows += "<td>" + item.type + "</td>";
                                rows += "<td>" + item.title + "</td>";
                                rows += "<td>" + item.status + "</td>";
                                rows += "<td>" + (item.start_date || "-") + "</td>";
                                rows += "<td>" + (item.end_date || "-") + "</td>";
                                rows += "<td><a href=\'" + item.edit_url + "\' class=\'button button-small\'>Éditer</a></td>";
                                rows += "</tr>";
                            });
                            
                            $("#fs-data-rows").html(rows);
                        } else {
                            $("#fs-data-rows").html("<tr><td colspan=\'7\' class=\'fs-no-results\'>Aucun résultat trouvé pour les filtres sélectionnés</td></tr>");
                        }
                    }).fail(function() {
                        $("#fs-loading").hide();
                        $("#fs-data-rows").html("<tr><td colspan=\'7\' class=\'fs-no-results\'>Erreur lors du chargement des données</td></tr>");
                    });
                }
            });
        </script>';
        
        echo '</div>'; // .wrap
    }
    
    // Méthode pour afficher une catégorie d'alertes
    private function render_category($title, $alerts) {
        if (empty($alerts)) {
            return;
        }
        
        echo '<div class="fs-category">';
        echo '<h3 class="fs-category-title">' . esc_html($title) . '</h3>';
        echo '<div class="fs-category-alerts">';
        
        foreach ($alerts as $alert) {
            $this->render_alert_card($alert);
        }
        
        echo '</div>'; // .fs-category-alerts
        echo '</div>'; // .fs-category
    }
    
    // Méthode pour afficher une carte d'alerte
    private function render_alert_card($alert) {
        $count = isset($alert['count']) ? intval($alert['count']) : 0;
        $severity = isset($alert['severity']) ? $alert['severity'] : 'normal';
        $alert_id = isset($alert['id']) ? $alert['id'] : sanitize_title($alert['title']);
        $alert_type = isset($alert['type']) ? $alert['type'] : 'unknown';
        
        if ($count === 0 && $severity !== 'info') {
            return; // Ne pas afficher les alertes vides
        }
        
        echo '<div class="fs-alert-card fs-' . esc_attr($severity) . '" data-alert-id="' . esc_attr($alert_id) . '" data-alert-type="' . esc_attr($alert_type) . '">';
        echo '<div class="fs-alert-title">' . esc_html($alert['title']) . ' <span class="fs-alert-count">' . esc_html($count) . '</span></div>';
        echo '</div>';
    }
    
    // =========== OBTENIR LES DONNÉES DES ALERTES ===========
    
    // Alertes administratives
    private function get_admin_alerts() {
        $alerts = array();
        
        // Conventions non envoyées
        $conventions_not_sent = $this->count_orders_with_meta_query(array(
            'relation' => 'AND',
            array('key' => '_order_status', 'value' => 'wc-inscription'),
            array(
                'relation' => 'OR',
                array('key' => 'fsbdd_dateconvenv', 'compare' => 'NOT EXISTS'),
                array('key' => 'fsbdd_dateconvenv', 'value' => '', 'compare' => '=')
            )
        ));
        
        $alerts[] = array(
            'id' => 'conventions_not_sent',
            'title' => 'Conventions non envoyées',
            'count' => $conventions_not_sent,
            'severity' => ($conventions_not_sent > 0) ? 'critical' : 'normal',
            'type' => 'order'
        );
        
        // Conventions signées non reçues
        $conventions_signed_not_received = $this->count_orders_with_meta_query(array(
            'relation' => 'AND',
            array('key' => 'fsbdd_dateconvenv', 'compare' => 'EXISTS'),
            array('key' => 'fsbdd_dateconvenv', 'value' => '', 'compare' => '!='),
            array(
                'relation' => 'OR',
                array('key' => 'fsbdd_dateconvrec', 'compare' => 'NOT EXISTS'),
                array('key' => 'fsbdd_dateconvrec', 'value' => '', 'compare' => '=')
            )
        ));
        
        $alerts[] = array(
            'id' => 'conventions_signed_not_received',
            'title' => 'Conventions signées non reçues',
            'count' => $conventions_signed_not_received,
            'severity' => ($conventions_signed_not_received > 0) ? 'warning' : 'normal',
            'type' => 'order'
        );
        
        return $alerts;
    }
    
    // Alertes formateurs
    private function get_formateur_alerts() {
        $alerts = array();
        
        // Formateur optionné non confirmé (-15 jours)
        $now = current_time('timestamp');
        $fifteen_days_ahead = $now + (15 * DAY_IN_SECONDS);
        
        $formateurs_option_not_confirmed = $this->count_actions_formations_with_query(array(
            'meta_query' => array(
                'relation' => 'AND',
                array('key' => 'fsbdd_datedebut', 'value' => date('Y-m-d', $fifteen_days_ahead), 'compare' => '<='),
                array('key' => 'fsbdd_formateur_status', 'value' => 'optionné'),
                array('key' => 'fsbdd_formateur_confirmed', 'value' => '0')
            )
        ));
        
        $alerts[] = array(
            'id' => 'formateurs_option_not_confirmed',
            'title' => 'Formateurs optionnés non confirmés',
            'count' => $formateurs_option_not_confirmed,
            'severity' => ($formateurs_option_not_confirmed > 0) ? 'warning' : 'normal',
            'type' => 'formation'
        );
        
        // Formation confirmée - Formateur pas au planning
        $formations_without_formateur = $this->count_actions_formations_with_query(array(
            'meta_query' => array(
                'relation' => 'AND',
                array('key' => 'fsbdd_formation_confirmed', 'value' => '1'),
                array(
                    'relation' => 'OR',
                    array('key' => 'fsbdd_formateur_id', 'compare' => 'NOT EXISTS'),
                    array('key' => 'fsbdd_formateur_id', 'value' => '', 'compare' => '='),
                    array('key' => 'fsbdd_formateur_id', 'value' => '0', 'compare' => '=')
                )
            )
        ));
        
        $alerts[] = array(
            'id' => 'formations_without_formateur',
            'title' => 'Formations sans formateur',
            'count' => $formations_without_formateur,
            'severity' => ($formations_without_formateur > 0) ? 'critical' : 'normal',
            'type' => 'formation'
        );
        
        return $alerts;
    }
    
    // Alertes formations
    private function get_formation_alerts() {
        $alerts = array();
        
        // Formation terminée - Émargement non reçu (+7 jours)
        $now = current_time('timestamp');
        $seven_days_ago = $now - (7 * DAY_IN_SECONDS);
        
        $emarg_not_received = $this->count_actions_formations_with_query(array(
            'meta_query' => array(
                'relation' => 'AND',
                array('key' => 'fsbdd_datedefin', 'value' => date('Y-m-d', $seven_days_ago), 'compare' => '<='),
                array(
                    'relation' => 'OR',
                    array('key' => 'fsbdd_emargement_received', 'compare' => 'NOT EXISTS'),
                    array('key' => 'fsbdd_emargement_received', 'value' => '0')
                )
            )
        ));
        
        $alerts[] = array(
            'id' => 'emarg_not_received',
            'title' => 'Émargements non reçus (+7j)',
            'count' => $emarg_not_received,
            'severity' => ($emarg_not_received > 0) ? 'critical' : 'normal',
            'type' => 'formation'
        );
        
        // Formation terminée - suivi non réalisé (+7 jours)
        $suivi_not_done_7 = $this->count_actions_formations_with_query(array(
            'meta_query' => array(
                'relation' => 'AND',
                array('key' => 'fsbdd_datedefin', 'value' => date('Y-m-d', $seven_days_ago), 'compare' => '<='),
                array(
                    'relation' => 'OR',
                    array('key' => 'fsbdd_suivi_realise', 'compare' => 'NOT EXISTS'),
                    array('key' => 'fsbdd_suivi_realise', 'value' => '0')
                )
            )
        ));
        
        $alerts[] = array(
            'id' => 'suivi_not_done_7',
            'title' => 'Suivi non réalisé (+7j)',
            'count' => $suivi_not_done_7,
            'severity' => ($suivi_not_done_7 > 0) ? 'warning' : 'normal',
            'type' => 'formation'
        );
        
        // Nom des stagiaires non renseignés (formation -10 jours)
        $now = current_time('timestamp');
        $ten_days_ahead = $now + (10 * DAY_IN_SECONDS);
        
        $stagiaires_missing_names = $this->count_orders_with_meta_query(array(
            'relation' => 'AND',
            array('key' => '_order_status', 'value' => 'wc-confirme'),
            array('key' => 'fsbdd_formation_start_date', 'value' => date('Y-m-d', $ten_days_ahead), 'compare' => '<='),
            array(
                'relation' => 'OR',
                array('key' => 'fsbdd_stagiaires_noms', 'compare' => 'NOT EXISTS'),
                array('key' => 'fsbdd_stagiaires_noms', 'value' => '')
            )
        ));
        
        $alerts[] = array(
            'id' => 'stagiaires_missing_names',
            'title' => 'Stagiaires sans nom (-10j)',
            'count' => $stagiaires_missing_names,
            'severity' => ($stagiaires_missing_names > 0) ? 'warning' : 'normal',
            'type' => 'order'
        );
        
        return $alerts;
    }
    
    // Alertes financières
    private function get_financial_alerts() {
        $alerts = array();
        
        // Factures non réglées depuis plus de 30 jours
        $now = current_time('timestamp');
        $thirty_days_ago = $now - (30 * DAY_IN_SECONDS);
        
        $unpaid_invoices_30 = $this->count_orders_with_meta_query(array(
            'relation' => 'AND',
            array('key' => '_order_status', 'value' => 'wc-facturesent'),
            array('key' => '_facturesent_date', 'value' => date('Y-m-d', $thirty_days_ago), 'compare' => '<=')
        ));
        
        $alerts[] = array(
            'id' => 'unpaid_invoices_30',
            'title' => 'Factures non réglées (+30j)',
            'count' => $unpaid_invoices_30,
            'severity' => ($unpaid_invoices_30 > 0) ? 'warning' : 'normal',
            'type' => 'order'
        );
        
        // Factures non réglées depuis plus de 60 jours
        $sixty_days_ago = $now - (60 * DAY_IN_SECONDS);
        
        $unpaid_invoices_60 = $this->count_orders_with_meta_query(array(
            'relation' => 'AND',
            array('key' => '_order_status', 'value' => 'wc-facturesent'),
            array('key' => '_facturesent_date', 'value' => date('Y-m-d', $sixty_days_ago), 'compare' => '<=')
        ));
        
        $alerts[] = array(
            'id' => 'unpaid_invoices_60',
            'title' => 'Factures non réglées (+60j)',
            'count' => $unpaid_invoices_60,
            'severity' => ($unpaid_invoices_60 > 0) ? 'critical' : 'normal',
            'type' => 'order'
        );
        
        // Total factures en attente (formations terminées - facture non envoyée)
        $pending_invoices = $this->count_orders_with_meta_query(array(
            'relation' => 'AND',
            array('key' => '_order_status', 'value' => 'wc-certifreal'),
            array(
                'relation' => 'OR',
                array('key' => '_facturesent_date', 'compare' => 'NOT EXISTS'),
                array('key' => '_facturesent_date', 'value' => '')
            )
        ));
        
        $alerts[] = array(
            'id' => 'pending_invoices',
            'title' => 'Factures en attente',
            'count' => $pending_invoices,
            'severity' => ($pending_invoices > 0) ? 'info' : 'normal',
            'type' => 'order'
        );
        
        return $alerts;
    }
    
    // Alertes OPCO
    private function get_opco_alerts() {
        $alerts = array();
        
        // Financement OPCO : N° dossier non reçu - Formation en attente
        $opco_no_dossier = $this->count_orders_with_meta_query(array(
            'relation' => 'AND',
            array('key' => 'fsbdd_financement_type', 'value' => 'opco'),
            array(
                'relation' => 'OR',
                array('key' => 'fsbdd_opco_dossier_number', 'compare' => 'NOT EXISTS'),
                array('key' => 'fsbdd_opco_dossier_number', 'value' => '')
            )
        ));
        
        $alerts[] = array(
            'id' => 'opco_no_dossier',
            'title' => 'OPCO: N° dossier non reçu',
            'count' => $opco_no_dossier,
            'severity' => ($opco_no_dossier > 0) ? 'warning' : 'normal',
            'type' => 'order'
        );
        
        // Devis à relancer +15 jours
        $now = current_time('timestamp');
        $fifteen_days_ago = $now - (15 * DAY_IN_SECONDS);
        
        $quotes_to_follow_up = $this->count_orders_with_meta_query(array(
            'relation' => 'AND',
            array('key' => '_order_status', 'value' => 'wc-devisproposition'),
            array('key' => '_date', 'value' => date('Y-m-d', $fifteen_days_ago), 'compare' => '<='),
            array(
                'relation' => 'OR',
                array('key' => 'fsbdd_devis_relance_date', 'compare' => 'NOT EXISTS'),
                array('key' => 'fsbdd_devis_relance_date', 'value' => '')
            )
        ));
        
        $alerts[] = array(
            'id' => 'quotes_to_follow_up',
            'title' => 'Devis à relancer (+15j)',
            'count' => $quotes_to_follow_up,
            'severity' => ($quotes_to_follow_up > 0) ? 'info' : 'normal',
            'type' => 'order'
        );
        
        return $alerts;
    }
    
    // =========== MÉTHODES AJAX ET UTILITAIRES ===========
    
    // Traitement AJAX pour récupérer les données filtrées
    public function ajax_get_filtered_data() {
        // Vérifier la sécurité
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        $results = array();
        
        // Traiter chaque filtre
        foreach ($filters as $filter) {
            $filter_id = sanitize_text_field($filter['id']);
            $filter_type = sanitize_text_field($filter['type']);
            
            // Récupérer les éléments correspondant au filtre
            $items = $this->get_items_for_filter($filter_id, $filter_type);
            
            // Fusionner les résultats (en évitant les doublons)
            foreach ($items as $item) {
                $exists = false;
                foreach ($results as $existing) {
                    if ($existing['id'] == $item['id'] && $existing['type'] == $item['type']) {
                        $exists = true;
                        break;
                    }
                }
                
                if (!$exists) {
                    $results[] = $item;
                }
            }
        }
        
        // Trier les résultats (par date de début, les plus récents en premier)
        usort($results, function($a, $b) {
            $date_a = isset($a['start_date']) ? strtotime($a['start_date']) : 0;
            $date_b = isset($b['start_date']) ? strtotime($b['start_date']) : 0;
            
            return $date_b - $date_a;
        });
        
        wp_send_json_success($results);
    }
    
    // Récupérer les éléments correspondant à un filtre
    private function get_items_for_filter($filter_id, $filter_type) {
        $items = array();
        
        switch ($filter_id) {
            case 'conventions_not_sent':
                $orders = wc_get_orders(array(
                    'limit' => 50,
                    'meta_query' => array(
                        'relation' => 'AND',
                        array('key' => '_order_status', 'value' => 'wc-inscription'),
                        array(
                            'relation' => 'OR',
                            array('key' => 'fsbdd_dateconvenv', 'compare' => 'NOT EXISTS'),
                            array('key' => 'fsbdd_dateconvenv', 'value' => '', 'compare' => '=')
                        )
                    )
                ));
                
                foreach ($orders as $order) {
                    $items[] = $this->format_order_for_display($order);
                }
                break;
                
            case 'unpaid_invoices_30':
                $now = current_time('timestamp');
                $thirty_days_ago = $now - (30 * DAY_IN_SECONDS);
                
                $orders = wc_get_orders(array(
                    'limit' => 50,
                    'meta_query' => array(
                        'relation' => 'AND',
                        array('key' => '_order_status', 'value' => 'wc-facturesent'),
                        array('key' => '_facturesent_date', 'value' => date('Y-m-d', $thirty_days_ago), 'compare' => '<=')
                    )
                ));
                
                foreach ($orders as $order) {
                    $items[] = $this->format_order_for_display($order);
                }
                break;
                
            case 'emarg_not_received':
                $now = current_time('timestamp');
                $seven_days_ago = $now - (7 * DAY_IN_SECONDS);
                
                $formations = get_posts(array(
                    'post_type' => 'action-de-formation',
                    'posts_per_page' => 50,
                    'meta_query' => array(
                        'relation' => 'AND',
                        array('key' => 'fsbdd_datedefin', 'value' => date('Y-m-d', $seven_days_ago), 'compare' => '<='),
                        array(
                            'relation' => 'OR',
                            array('key' => 'fsbdd_emargement_received', 'compare' => 'NOT EXISTS'),
                            array('key' => 'fsbdd_emargement_received', 'value' => '0')
                        )
                    )
                ));
                
                foreach ($formations as $formation) {
                    $items[] = $this->format_formation_for_display($formation);
                }
                break;
                
            // Ajouter d'autres cas pour les autres filtres...
            
            default:
                // Si le filtre n'est pas reconnu, tenter une requête générique
                if ($filter_type == 'order') {
                    $orders = wc_get_orders(array(
                        'limit' => 50,
                        'meta_key' => '_fs_alert_' . $filter_id,
                        'meta_value' => '1'
                    ));
                    
                    foreach ($orders as $order) {
                        $items[] = $this->format_order_for_display($order);
                    }
                } elseif ($filter_type == 'formation') {
                    $formations = get_posts(array(
                        'post_type' => 'action-de-formation',
                        'posts_per_page' => 50,
                        'meta_key' => '_fs_alert_' . $filter_id,
                        'meta_value' => '1'
                    ));
                    
                    foreach ($formations as $formation) {
                        $items[] = $this->format_formation_for_display($formation);
                    }
                }
                break;
        }
        
        return $items;
    }
    
    // Formater une commande pour l'affichage dans le tableau
    private function format_order_for_display($order) {
        $order_id = $order->get_id();
        $customer = $order->get_formatted_billing_full_name();
        $status = wc_get_order_status_name($order->get_status());
        
        // Récupérer les dates spécifiques de formation si elles existent
        $start_date = get_post_meta($order_id, 'fsbdd_formation_start_date', true);
        $end_date = get_post_meta($order_id, 'fsbdd_formation_end_date', true);
        
        return array(
            'id' => $order_id,
            'type' => 'Commande',
            'title' => $customer . ' (#' . $order_id . ')',
            'status' => $status,
                        'start_date' => $start_date,
            'end_date' => $end_date,
            'edit_url' => admin_url('post.php?post=' . $order_id . '&action=edit')
        );
    }
    
    // Formater une formation pour l'affichage dans le tableau
    private function format_formation_for_display($formation) {
        $formation_id = $formation->ID;
        $title = get_the_title($formation_id);
        
        // Récupérer les dates de début et de fin
        $start_date = get_post_meta($formation_id, 'fsbdd_datedebut', true);
        $end_date = get_post_meta($formation_id, 'fsbdd_datedefin', true);
        
        // Récupérer le statut personnalisé si disponible
        $status = get_post_meta($formation_id, 'fsbdd_formation_status', true);
        if (empty($status)) {
            $status = 'Publié';
        }
        
        return array(
            'id' => $formation_id,
            'type' => 'Formation',
            'title' => $title,
            'status' => $status,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'edit_url' => admin_url('post.php?post=' . $formation_id . '&action=edit')
        );
    }
    
    // Compter les commandes avec une requête meta spécifique
    private function count_orders_with_meta_query($meta_query) {
        $args = array(
            'limit' => -1,
            'return' => 'ids',
            'meta_query' => $meta_query
        );
        
        $orders = wc_get_orders($args);
        return count($orders);
    }
    
    // Compter les actions de formation avec une requête spécifique
    private function count_actions_formations_with_query($args) {
        $default_args = array(
            'post_type' => 'action-de-formation',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        );
        
        $args = array_merge($default_args, $args);
        $query = new WP_Query($args);
        
        return $query->found_posts;
    }
    
    // Ajouter des filtres personnalisés dans les pages de listing
    public function add_custom_filters() {
        global $typenow, $wp_query;
        
        // Filtres pour les commandes
        if ($typenow == 'shop_order') {
            $filter = isset($_GET['fs_filter']) ? $_GET['fs_filter'] : '';
            
            $filters = array(
                '' => 'Filtres Formation Stratégique',
                'conventions_not_sent' => 'Conventions non envoyées',
                'conventions_signed_not_received' => 'Conventions signées non reçues',
                'unpaid_invoices_30' => 'Factures non réglées +30j',
                'unpaid_invoices_60' => 'Factures non réglées +60j',
                'unpaid_invoices_90' => 'Factures non réglées +90j',
                'pending_invoices' => 'Factures en attente',
                'opco_no_dossier' => 'OPCO: N° dossier non reçu',
                'quotes_to_follow_up' => 'Devis à relancer',
                'stagiaires_missing_names' => 'Noms des stagiaires manquants'
            );
            
            echo '<select name="fs_filter">';
            
            foreach ($filters as $value => $label) {
                echo '<option value="' . esc_attr($value) . '" ' . selected($filter, $value, false) . '>' . esc_html($label) . '</option>';
            }
            
            echo '</select>';
        }
        
        // Filtres pour les actions de formation
        if ($typenow == 'action-de-formation') {
            $filter = isset($_GET['fs_filter']) ? $_GET['fs_filter'] : '';
            
            $filters = array(
                '' => 'Filtres Formation Stratégique',
                'emarg_not_received' => 'Émargements non reçus',
                'suivi_not_done_7' => 'Suivi non réalisé +7j',
                'suivi_not_done_15' => 'Suivi non réalisé +15j',
                'suivi_not_done_30' => 'Suivi non réalisé +30j',
                'formateurs_option_not_confirmed' => 'Formateurs optionnés non confirmés',
                'formations_without_formateur' => 'Formations sans formateur'
            );
            
            echo '<select name="fs_filter">';
            
            foreach ($filters as $value => $label) {
                echo '<option value="' . esc_attr($value) . '" ' . selected($filter, $value, false) . '>' . esc_html($label) . '</option>';
            }
            
            echo '</select>';
        }
    }
    
    // Appliquer les filtres dans les requêtes
    public function apply_custom_filters($query) {
        global $pagenow, $typenow;
        
        if (!is_admin() || $pagenow != 'edit.php' || !isset($_GET['fs_filter']) || empty($_GET['fs_filter'])) {
            return $query;
        }
        
        $filter = $_GET['fs_filter'];
        
        // Filtres pour les commandes WooCommerce
        if ($typenow == 'shop_order') {
            switch ($filter) {
                case 'conventions_not_sent':
                    $query->set('meta_query', array(
                        'relation' => 'AND',
                        array('key' => '_order_status', 'value' => 'wc-inscription'),
                        array(
                            'relation' => 'OR',
                            array('key' => 'fsbdd_dateconvenv', 'compare' => 'NOT EXISTS'),
                            array('key' => 'fsbdd_dateconvenv', 'value' => '', 'compare' => '=')
                        )
                    ));
                    break;
                    
                case 'unpaid_invoices_30':
                    $now = current_time('timestamp');
                    $thirty_days_ago = $now - (30 * DAY_IN_SECONDS);
                    
                    $query->set('meta_query', array(
                        'relation' => 'AND',
                        array('key' => '_order_status', 'value' => 'wc-facturesent'),
                        array('key' => '_facturesent_date', 'value' => date('Y-m-d', $thirty_days_ago), 'compare' => '<=')
                    ));
                    break;
                
                case 'unpaid_invoices_60':
                    $now = current_time('timestamp');
                    $sixty_days_ago = $now - (60 * DAY_IN_SECONDS);
                    
                    $query->set('meta_query', array(
                        'relation' => 'AND',
                        array('key' => '_order_status', 'value' => 'wc-facturesent'),
                        array('key' => '_facturesent_date', 'value' => date('Y-m-d', $sixty_days_ago), 'compare' => '<=')
                    ));
                    break;
                    
                // Ajoutez d'autres cas pour les autres filtres de commandes
            }
        }
        
        // Filtres pour les actions de formation
        if ($typenow == 'action-de-formation') {
            switch ($filter) {
                case 'emarg_not_received':
                    $now = current_time('timestamp');
                    $seven_days_ago = $now - (7 * DAY_IN_SECONDS);
                    
                    $query->set('meta_query', array(
                        'relation' => 'AND',
                        array('key' => 'fsbdd_datedefin', 'value' => date('Y-m-d', $seven_days_ago), 'compare' => '<='),
                        array(
                            'relation' => 'OR',
                            array('key' => 'fsbdd_emargement_received', 'compare' => 'NOT EXISTS'),
                            array('key' => 'fsbdd_emargement_received', 'value' => '0')
                        )
                    ));
                    break;
                    
                case 'formateurs_option_not_confirmed':
                    $now = current_time('timestamp');
                    $fifteen_days_ahead = $now + (15 * DAY_IN_SECONDS);
                    
                    $query->set('meta_query', array(
                        'relation' => 'AND',
                        array('key' => 'fsbdd_datedebut', 'value' => date('Y-m-d', $fifteen_days_ahead), 'compare' => '<='),
                        array('key' => 'fsbdd_formateur_status', 'value' => 'optionné'),
                        array('key' => 'fsbdd_formateur_confirmed', 'value' => '0')
                    ));
                    break;
                    
                // Ajoutez d'autres cas pour les autres filtres de formations
            }
        }
        
        return $query;
    }
}

// Initialiser le tableau de bord
$fs_dashboard = new FS_Dashboard();

// Ajouter un script pour enregistrer les meta données d'alerte sur les commandes et formations
add_action('save_post', 'fs_update_alert_flags', 10, 3);
function fs_update_alert_flags($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    
    // Pour les commandes WooCommerce
    if ($post->post_type == 'shop_order') {
        $order = wc_get_order($post_id);
        if (!$order) return;
        
        // Exemple: marquer les commandes qui nécessitent une convention
        $status = $order->get_status();
        $convention_sent = get_post_meta($post_id, 'fsbdd_dateconvenv', true);
        
        if ($status == 'inscription' && empty($convention_sent)) {
            update_post_meta($post_id, '_fs_alert_conventions_not_sent', '1');
        } else {
            delete_post_meta($post_id, '_fs_alert_conventions_not_sent');
        }
        
        // Ajouter d'autres règles pour les autres types d'alertes
    }
    
    // Pour les actions de formation
    if ($post->post_type == 'action-de-formation') {
        // Exemple: marquer les formations qui nécessitent un émargement
        $date_fin = get_post_meta($post_id, 'fsbdd_datedefin', true);
        $emargement_received = get_post_meta($post_id, 'fsbdd_emargement_received', true);
        
        if (!empty($date_fin)) {
            $now = current_time('timestamp');
            $date_fin_ts = strtotime($date_fin);
            $seven_days_ago = $now - (7 * DAY_IN_SECONDS);
            
            if ($date_fin_ts <= $seven_days_ago && empty($emargement_received)) {
                update_post_meta($post_id, '_fs_alert_emarg_not_received', '1');
            } else {
                delete_post_meta($post_id, '_fs_alert_emarg_not_received');
            }
        }
        
        // Ajouter d'autres règles pour les autres types d'alertes
    }
}