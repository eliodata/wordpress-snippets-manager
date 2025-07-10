<?php
/**
 * Snippet ID: 157
 * Name: REPORT alertes global accueil admin
 * Description: 
 * @active false
 */



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
 * Ajoute un tableau de bord personnalisé sur la page daccueil admin
 * pour les utilisateurs avec rôles administrator, referent et compta
 */
add_action('admin_notices', 'fsbdd_dashboard_for_consultants');

function fsbdd_dashboard_for_consultants() {
    // Vérifier que nous sommes sur la page daccueil de ladmin
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'dashboard') { // Ajout verification $screen existe
        return;
    }

    // Vérifier les rôles autorisés
    $current_user = wp_get_current_user();
    if (!$current_user || $current_user->ID === 0) return; // Verifier si user existe

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

    // Si lutilisateur est un référent, ne montrer que ses commandes
    $is_referent = in_array('referent', (array) $current_user->roles);
    $user_id = $current_user->ID;

    // Titre dynamique du tableau de bord
    $dashboard_title = $is_referent ? 'Mes actions de formation et commandes' : 'Tableau de bord des actions de formation';

    // CSS pour le tableau de bord (ajout de styles pour les alertes)
    // Utilisation de double quotes pour les commentaires CSS pour eviter conflit
    echo '<style>
        /* Styles generaux */
        .fsbdd-dashboard {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        .fsbdd-dashboard h2 {
            margin-top: 0;
            border-bottom: 2px solid #299a68;
            padding-bottom: 10px;
            color: #23282d;
            font-size: 1.5em;
        }

        /* Section des Alertes */
        .fsbdd-alerts-container {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #e3e6ea;
            border-radius: 4px;
        }
        .fsbdd-alerts-container h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 16px;
            color: #5a5a5a;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .fsbdd-alerts-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .fsbdd-alert-item {
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            padding: 5px 10px;
            border-radius: 20px; /* Pill shape */
            font-size: 12px;
            cursor: pointer;
            transition: background-color 0.2s, border-color 0.2s, color 0.2s;
            color: #495057;
            user-select: none; /* Empêche la sélection de texte au double-clic */
        }
        .fsbdd-alert-item:hover {
            background-color: #dde1e5;
            border-color: #adb5bd;
        }
        .fsbdd-alert-item.active {
            background-color: #dc3545; /* Rouge pour lalerte active */
            border-color: #c82333;
            color: white;
            font-weight: 500;
        }
        #fsbdd-clear-alert-filter {
            font-size: 12px;
            color: #0073aa;
            cursor: pointer;
            text-decoration: none;
            display: none; /* Cache par defaut */
        }
         #fsbdd-clear-alert-filter:hover {
            text-decoration: underline;
         }
        /* Style pour les alertes non filtrables */
        .fsbdd-alert-warning {
             background-color: #fff3cd;
             border-color: #ffeeba;
             color: #856404;
             cursor: default !important; /* Non cliquable */
        }
        .fsbdd-alert-warning:hover {
             background-color: #fff3cd; /* Pas de changement au survol */
             border-color: #ffeeba;
        }
        .fsbdd-alert-info {
             background-color: #d1ecf1;
             border-color: #bee5eb;
             color: #0c5460;
             cursor: default !important; /* Non cliquable */
        }
         .fsbdd-alert-info:hover {
             background-color: #d1ecf1; /* Pas de changement au survol */
             border-color: #bee5eb;
         }

        /* Filtres Standards */
        .fsbdd-filter-bar { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .fsbdd-filter-item { flex-grow: 1; max-width: 250px; min-width: 150px; }
        .fsbdd-filter-item label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 13px; color: #333; }
        .fsbdd-filter-item select, .fsbdd-filter-item input { width: 100%; padding: 6px 8px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size:13px;}

        /* Compteur Resultats */
        #fsbdd-filtered-count { margin-bottom: 10px; font-size: 13px; color: #666; }

        /* Tableau */
        .fsbdd-table-wrapper { overflow-x: auto; max-width: 100%; margin-bottom: 15px;}
        .fsbdd-dashboard table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .fsbdd-dashboard th { background-color: #f0f0f0; text-align: left; padding: 10px 12px; border: 1px solid #ddd; font-weight: 600; white-space: nowrap; }
        .fsbdd-dashboard td { padding: 9px 12px; border: 1px solid #ddd; vertical-align: middle; line-height: 1.4; }
        .fsbdd-dashboard tr:nth-child(even) { background-color: #f9f9f9; }
        .fsbdd-dashboard tr:hover { background-color: #f1f1f1; }
        .fsbdd-dashboard td a { text-decoration: none; color: #0073aa; }
        .fsbdd-dashboard td a:hover { text-decoration: underline; }

        /* Badges Statut */
        .fsbdd-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; color: white; line-height: 1; white-space: nowrap; }
        .fsbdd-badge-default { background-color: #6c757d; }
        .fsbdd-badge-confirme, .fsbdd-badge-certifreal, .fsbdd-badge-wc-confirme, .fsbdd-badge-wc-certifreal { background-color: #28a745; } /* Vert */
        .fsbdd-badge-avenantvalide, .fsbdd-badge-wc-avenantvalide { background-color: #20c997; } /* Turquoise */
        .fsbdd-badge-avenantconv, .fsbdd-badge-wc-avenantconv { background-color: #fd7e14; } /* Orange */
        .fsbdd-badge-preinscription, .fsbdd-badge-wc-preinscription { background-color: #ffc107; color: #333; } /* Jaune */
        .fsbdd-badge-inscription, .fsbdd-badge-modifpreinscript, .fsbdd-badge-wc-inscription, .fsbdd-badge-wc-modifpreinscript { background-color: #17a2b8; } /* Bleu Cyan */
        .fsbdd-badge-devisproposition, .fsbdd-badge-wc-devisproposition { background-color: #0dcaf0; color: #333; } /* Bleu clair */
        .fsbdd-badge-on-hold, .fsbdd-badge-wc-on-hold { background-color: #ffc107; color:#333;} /* Jaune pour attente */
        .fsbdd-badge-pending, .fsbdd-badge-wc-pending { background-color: #6c757d; } /* Gris */
        .fsbdd-badge-processing, .fsbdd-badge-wc-processing { background-color: #6f42c1; } /* Violet */
        .fsbdd-badge-completed, .fsbdd-badge-wc-completed { background-color: #0d6efd; } /* Bleu standard */
        .fsbdd-badge-failed, .fsbdd-badge-wc-failed { background-color: #dc3545; } /* Rouge */
        .fsbdd-badge-cancelled, .fsbdd-badge-wc-cancelled { background-color: #adb5bd; color:#333; } /* Gris clair */
        .fsbdd-badge-refunded, .fsbdd-badge-wc-refunded { background-color: #343a40; } /* Noir */
        /* Assigner les autres statuts specifiques si necessaire */
        .fsbdd-badge-gplsquote-req, .fsbdd-badge-wc-gplsquote-req { background-color: #6e44ff; }

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
        .fsbdd-dashboard .dashicons {
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
        .fsbdd-actions-links { display: flex; gap: 15px; }
        .fsbdd-actions-link { font-size: 13px; text-decoration: none; color: #0073aa; }
        .fsbdd-actions-link:hover { text-decoration: underline; }
         #fsbdd-show-all {
            font-size: 13px;
            cursor: pointer;
            color: #0073aa;
            background: #f0f0f0;
            padding: 5px 10px;
            border-radius: 3px;
            border: 1px solid #ddd;
        }
        #fsbdd-show-all:hover { background: #e0e0e0; border-color: #ccc; }

        /* Specific column widths (optional) */
        .fsbdd-dashboard th:nth-child(1), .fsbdd-dashboard td:nth-child(1) { width: 70px; } /* Affaire */
        .fsbdd-dashboard th:nth-child(2), .fsbdd-dashboard td:nth-child(2) { width: 100px; } /* Convention */
        .fsbdd-dashboard th:nth-child(3), .fsbdd-dashboard td:nth-child(3) { min-width: 150px; } /* Client */
        .fsbdd-dashboard th:nth-child(4), .fsbdd-dashboard td:nth-child(4) { min-width: 180px; } /* Session */
        .fsbdd-dashboard th:nth-child(5), .fsbdd-dashboard td:nth-child(5) { width: 140px; } /* Dates & Lieu */
        .fsbdd-dashboard th:nth-child(6), .fsbdd-dashboard td:nth-child(6) { width: 120px; text-align: center; } /* Statut */
        .fsbdd-dashboard td:nth-child(6) span { margin: auto; }
        .fsbdd-dashboard th:nth-child(7), .fsbdd-dashboard td:nth-child(7) { width: 60px; text-align: center; } /* Effectif */
        .fsbdd-dashboard th:nth-child(8), .fsbdd-dashboard td:nth-child(8) { width: 60px; text-align: center; } /* OPCO */
        .fsbdd-dashboard th:nth-child(9), .fsbdd-dashboard td:nth-child(9) { width: 90px; text-align: center; } /* Documents */
        .fsbdd-dashboard th:nth-child(10), .fsbdd-dashboard td:nth-child(10) { min-width: 130px; } /* Formateurs */
        .fsbdd-dashboard th:nth-child(11), .fsbdd-dashboard td:nth-child(11) { min-width: 110px; } /* Referent */
        .fsbdd-dashboard th:nth-child(12), .fsbdd-dashboard td:nth-child(12) { width: 100px; } /* Declenchement */
        .fsbdd-dashboard th:nth-child(13), .fsbdd-dashboard td:nth-child(13) { width: 100px; } /* Suivi Realise */

    </style>';

    echo '<div class="fsbdd-dashboard">';
    echo '<h2>' . esc_html($dashboard_title) . '</h2>';

    // --- Section des Alertes ---
    echo '<div class="fsbdd-alerts-container">';
    echo '<h3>Alertes <a href="#" id="fsbdd-clear-alert-filter">Effacer le filtre d`alerte</a></h3>';
    echo '<ul class="fsbdd-alerts-list">';
    // --- Alertes Cliquables (transformées en filtres) ---
    echo '<li class="fsbdd-alert-item" data-alert-type="conv_signee_non_recue">Conventions signées non reçues</li>'; // Placeholder
    echo '<li class="fsbdd-alert-item" data-alert-type="conv_non_envoyee">Convention non envoyée</li>'; // Placeholder
    echo '<li class="fsbdd-alert-item" data-alert-type="emargement_non_recu_7j">Formation terminée - Émargement non reçu (+7j)</li>'; // Implémentable
    echo '<li class="fsbdd-alert-item" data-alert-type="formation_erreur">Formation en erreur (fact. bloquée)</li>'; // Placeholder
    echo '<li class="fsbdd-alert-item" data-alert-type="formateur_option_non_confirme_15j">Formateur optionné non confirmé (-15j)</li>'; // Placeholder
    echo '<li class="fsbdd-alert-item" data-alert-type="formateur_prebloque_non_confirme_15j">Formateur prébloqué non confirmé (-15j)</li>'; // Placeholder
    echo '<li class="fsbdd-alert-item" data-alert-type="formateur_confirme_lm_non_envoyee">Formateur confirmé – LM non envoyée</li>'; // Placeholder
    echo '<li class="fsbdd-alert-item" data-alert-type="formation_confirmee_formateur_absent">Formation confirmée – Formateur pas au planning</li>'; // Implémentable
    echo '<li class="fsbdd-alert-item" data-alert-type="inter_confirme_manque_element">Inter confirmée - Élément manquant</li>'; // Placeholder
    echo '<li class="fsbdd-alert-item" data-alert-type="inter_non_confirme_15j">Inter non confirmée (-15j)</li>'; // Placeholder
    echo '<li class="fsbdd-alert-item" data-alert-type="option_non_confirmee_7j">Option posée non confirmée (+7j)</li>'; // Placeholder
    echo '<li class="fsbdd-alert-item" data-alert-type="suivi_non_realise_7j">Terminée - Suivi non réalisé (+7j)</li>'; // Implémentable
    echo '<li class="fsbdd-alert-item" data-alert-type="suivi_non_realise_15j">Terminée - Suivi non réalisé (+15j)</li>'; // Implémentable
    echo '<li class="fsbdd-alert-item" data-alert-type="suivi_non_realise_30j">Terminée - Suivi non réalisé (+30j)</li>'; // Implémentable
    echo '<li class="fsbdd-alert-item" data-alert-type="stagiaires_non_renseignes_10j">Noms stagiaires manquants (-10j)</li>'; // Placeholder
    echo '<li class="fsbdd-alert-item" data-alert-type="convocations_attente">Convocations en attente</li>'; // Placeholder
    echo '<li class="fsbdd-alert-item" data-alert-type="opco_num_dossier_manquant">OPCO : N° dossier manquant</li>'; // Placeholder
    echo '<li class="fsbdd-alert-item" data-alert-type="opco_dossier_non_recu_passe_10j">OPCO : dossier non reçu (passée +10j)</li>'; // Placeholder
    echo '<li class="fsbdd-alert-item" data-alert-type="devis_relance_15j">Devis à relancer (+15j)</li>'; // Placeholder

    // --- Alertes Non-Cliquables (Infos / Totaux) ---
    // Ajoute des tooltips simples pour expliquer pourquoi elles ne sont pas filtrables
    echo '<li class="fsbdd-alert-item fsbdd-alert-warning" title="Info globale - Non filtrable par ligne">Facture encours non réglée</li>';
    echo '<li class="fsbdd-alert-item fsbdd-alert-info" title="Info globale - Non filtrable par ligne">Total factures non réglées</li>';
    echo '<li class="fsbdd-alert-item fsbdd-alert-info" title="Info globale - Non filtrable par ligne">Total factures réglées</li>';
    echo '<li class="fsbdd-alert-item fsbdd-alert-info" title="Info globale - Non filtrable par ligne">Total formateur à régler / réglé</li>';
    echo '<li class="fsbdd-alert-item fsbdd-alert-warning" title="Info globale - Non filtrable par ligne">Total factures en attente réalisation</li>';

    echo '</ul>';
    echo '</div>';
    // --- Fin de la section des Alertes ---

    // Récupération des données
    // Augmenter la limite si necessaire pour couvrir plus de donnees pour les alertes
    $orders_data = get_consultant_orders_data($is_referent ? $user_id : null, 1000); // Limite augmentee

    // Filtres existants
    echo '<div class="fsbdd-filter-bar">';

    echo '<div class="fsbdd-filter-item">';
    echo '<label for="fsbdd-filter-status">Statut Commande</label>';
    echo '<select id="fsbdd-filter-status" class="fsbdd-filter">';
    echo '<option value="">Tous les statuts</option>';
    // Utiliser wc_get_order_statuses() pour une liste dynamique et traduite
    $wc_statuses = wc_get_order_statuses();
    foreach ($wc_statuses as $status_key => $status_name) {
        // Nettoyer la clef (enlever 'wc-') pour correspondre au data-status
        $clean_key = str_replace('wc-', '', $status_key);
        echo '<option value="' . esc_attr($clean_key) . '">' . esc_html($status_name) . '</option>';
    }
    echo '</select>';
    echo '</div>';

    echo '<div class="fsbdd-filter-item">';
    echo '<label for="fsbdd-filter-periode">Période Session</label>';
    echo '<select id="fsbdd-filter-periode" class="fsbdd-filter">';
    echo '<option value="">Toutes les périodes</option>';
    echo '<option value="recent_future" selected>Proche (±20 jours)</option>'; // Selection par defaut
    echo '<option value="recent">Récente (-20 jours)</option>';
    echo '<option value="futur">Future</option>';
    echo '<option value="passe">Passée</option>';
    echo '</select>';
    echo '</div>';

    if (!$is_referent) {
        echo '<div class="fsbdd-filter-item">';
        echo '<label for="fsbdd-filter-referent">Référent</label>';
        echo '<select id="fsbdd-filter-referent" class="fsbdd-filter">';
        echo '<option value="">Tous les référents</option>';
        $referents = get_referents_list();
        foreach ($referents as $ref_id => $ref_name) {
            echo '<option value="' . esc_attr($ref_id) . '">' . esc_html($ref_name) . '</option>';
        }
        echo '</select>';
        echo '</div>';
    }

    echo '<div class="fsbdd-filter-item">';
    echo '<label for="fsbdd-filter-search">Recherche</label>';
    echo '<input type="text" id="fsbdd-filter-search" class="fsbdd-filter" placeholder="Client, N° commande, Session...">';
    echo '</div>';

    echo '</div>'; // Fin des filtres

    // Affichage du compteur de résultats filtré
    echo '<div id="fsbdd-filtered-count"></div>';

    // Wrapper pour la table avec scroll horizontal
    echo '<div class="fsbdd-table-wrapper">';

    // Tableau des commandes/actions
    echo '<table id="fsbdd-orders-table">';
    echo '<thead>
            <tr>
                <th>Affaire</th>
                <th>Convention</th>
                <th>Client</th>
                <th>Session</th>
                <th>Dates & Lieu</th>
                <th>Statut</th>
                <th>Effectif</th>
                <th>OPCO</th>
                <th>Docs</th>
                <th>Formateurs</th>
                <th>Référent</th>
                <th>Déclench.</th>
                <th>Suivi</th>
            </tr>
          </thead>';

    echo '<tbody>';

    if (empty($orders_data)) {
        echo '<tr><td colspan="13" style="text-align:center; padding: 20px;">Aucune action de formation trouvée pour les critères actuels.</td></tr>';
    } else {
        // Trier par date de début de session (la plus proche en premier)
        // Mettre les sessions sans date à la fin
        usort($orders_data, function($a, $b) {
            $ts_a = $a['start_timestamp'] ?? 0;
            $ts_b = $b['start_timestamp'] ?? 0;

            if ($ts_a == 0 && $ts_b == 0) return 0; // Les deux sans date
            if ($ts_a == 0) return 1; // a sans date après b
            if ($ts_b == 0) return -1; // b sans date après a

            // Comparer les dates normalement (plus récent = plus petit timestamp ?)
             // Non, on veut les plus proches d'aujourd'hui, puis les futures, puis les passées lointaines
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
                 return $ts_a <=> $ts_b; // Ordre chronologique standard
            }

            // Si les deux sont passées, la plus récente (moins ancienne) vient avant
             if (!$is_future_a && !$is_future_b) {
                 return $ts_b <=> $ts_a; // Ordre chronologique inverse
             }

             return 0; // Normalement ne devrait pas arriver
        });


        foreach ($orders_data as $order_data) {
            // Nettoyer le statut WC ('wc-' prefix) pour le badge CSS et data-status
            $clean_status = str_replace('wc-', '', $order_data['status']);
            $status_class = 'fsbdd-badge-' . $clean_status;

            // Classes pour les icones de documents
            $doc_emargements_class = 'fsbdd-badge-' . ($order_data['emargements'] ?? '1');
            $doc_cpterendu_class = 'fsbdd-badge-' . ($order_data['cpte_rendu'] ?? '1');
            $doc_evaluations_class = 'fsbdd-badge-' . ($order_data['evaluations'] ?? '1');

            // Ajouter data-* attributs pour le filtrage des alertes
            echo '<tr data-order-id="' . esc_attr($order_data['order_id']) .'"
                      data-session-id="' . esc_attr($order_data['session_id'] ?? '') .'"
                      data-status="' . esc_attr($clean_status) . '"
                      data-referent="' . esc_attr($order_data['referent_id'] ?? '') . '"
                      data-start-timestamp="' . esc_attr($order_data['start_timestamp'] ?? '0') . '"
                      data-end-timestamp="' . esc_attr($order_data['end_timestamp'] ?? '0') . '"
                      data-emargements="' . esc_attr($order_data['emargements'] ?? '1') . '"
                      data-formateurs="' . esc_attr($order_data['formateurs'] ?? '') . '"
                      data-suivi-realise="' . esc_attr(strtolower($order_data['suivi_realise'] ?? 'non')) . '"
                      data-opco="' . esc_attr(strtolower($order_data['opco'] ?? 'non')) . '"
                      data-convention-status="' . esc_attr($order_data['convention_status'] ?? 'na') . '" '. // Example: Ajoutez ceci si vous le recuperez
                      /* Ajoutez dautres data-* attributs ici si necessaire pour les alertes restantes */
                      '>';

            // Colonne: Affaire (N° Commande)
            echo '<td><a href="' . esc_url(admin_url('post.php?post=' . $order_data['order_id'] . '&action=edit')) . '" target="_blank">#' . esc_html($order_data['order_number']) . '</a></td>';

            // Colonne: Convention
            echo '<td>' . esc_html($order_data['convention'] ?? '') . '</td>';

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
            if (!empty($order_data['session_id'])) {
                echo '<a href="' . esc_url(admin_url('post.php?post=' . $order_data['session_id'] . '&action=edit')) . '" target="_blank" title="Voir action de formation">' . esc_html($order_data['session_title'] ?? 'Session sans titre') . '</a>';
            } else {
                echo 'Non définie';
            }
            echo '</td>';

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
            echo $date_str . '<br><small>' . esc_html($order_data['lieu_resume'] ?? 'Lieu N/D') . '</small>';
            echo '</td>';

            // Colonne: Statut Commande
            echo '<td style="text-align: center;"><span class="fsbdd-badge ' . esc_attr($status_class) . '">' . esc_html($order_data['status_name'] ?? $clean_status) . '</span></td>';

            // Colonne: Effectif
            echo '<td style="text-align: center;">' . esc_html($order_data['effectif'] ?? '0') . '</td>';

            // Colonne: OPCO
            echo '<td style="text-align: center;">' . esc_html(strtoupper($order_data['opco'] ?? 'NON')) . '</td>';

            // Colonne: Documents
            echo '<td style="white-space: nowrap; text-align: center;">';
            echo '<span class="dashicons dashicons-clipboard" style="color: ' . get_doc_color($doc_emargements_class) . ';" title="Émargements: ' . esc_attr($order_data['emargements_text'] ?? 'Vide') .'"></span>';
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
    // Le bouton change de texte selon letat
    echo '<span id="fsbdd-show-all" class="fsbdd-show-all">Afficher tout</span>';
    echo '</div>';

    // --- JavaScript pour les filtres et l'affichage ---
    echo '<script type="text/javascript">
        jQuery(document).ready(function($) {
            var itemsPerPage = 20; // Nombre delements par "page"
            var currentPage = 1; // Non utilise si showAll est true par defaut
            var showAll = true; // Afficher tout par défaut
            var activeAlertType = null; // Pour stocker le filtre dalerte actif
            var allRowsCache = $("#fsbdd-orders-table tbody tr"); // Mettre en cache les lignes

            // Fonction pour obtenir les lignes actuellement visibles selon TOUS les filtres
            function getFilteredRows() {
                var statusFilter = $("#fsbdd-filter-status").val();
                var periodeFilter = $("#fsbdd-filter-periode").val();
                var referentFilter = $("#fsbdd-filter-referent").val(); // Peut etre undefined si non present
                var searchFilter = $("#fsbdd-filter-search").val().toLowerCase();

                var today = new Date();
                today.setHours(0, 0, 0, 0);
                var todayTimestamp = Math.floor(today.getTime() / 1000); // Timestamp en secondes

                var filteredRows = allRowsCache.filter(function() {
                    var row = $(this);
                    var showRow = true;

                    // 1. Filtre Alerte (si actif)
                    if (activeAlertType) {
                        showRow = checkAlertCondition(row, activeAlertType, todayTimestamp);
                        if (!showRow) return false; // Si lalerte ne correspond pas, on cache la ligne
                    }

                    // 2. Filtres Standards (appliques si pas deja cachee par lalerte)
                    // Filtre statut commande
                    if (showRow && statusFilter && row.data("status") !== statusFilter) {
                        showRow = false;
                    }

                    // Filtre referent (seulement si le select existe)
                    if (showRow && referentFilter && typeof referentFilter !== "undefined" && row.data("referent") != referentFilter) { // Utiliser != car data peut etre nombre et val string
                        showRow = false;
                    }

                    // Filtre recherche globale
                    if (showRow && searchFilter) {
                        var rowText = row.text().toLowerCase();
                        if (rowText.indexOf(searchFilter) === -1) {
                            showRow = false;
                        }
                    }

                    // Filtre periode session (base sur date de debut)
                    if (showRow && periodeFilter) {
                         var startTimestamp = parseInt(row.data("start-timestamp"), 10) || 0;

                         if (startTimestamp > 0) {
                            // Comparaison en secondes
                            var diffDays = Math.round((startTimestamp - todayTimestamp) / (60 * 60 * 24));

                             switch (periodeFilter) {
                                case "recent_future": // +/- 20 jours
                                    showRow = Math.abs(diffDays) <= 20;
                                    break;
                                case "recent": // Passé récent (-20 jours à aujourdhui)
                                    showRow = diffDays <= 0 && diffDays >= -20;
                                    break;
                                case "futur": // Strictement dans le futur
                                    showRow = diffDays > 0;
                                    break;
                                case "passe": // Strictement dans le passé
                                    showRow = diffDays < 0;
                                    break;
                                // case "": // "Toutes les periodes" - ne fait rien
                             }
                         } else if (periodeFilter !== "") {
                             // Si pas de date et un filtre periode est actif (sauf "tous"), on cache
                             showRow = false;
                         }
                    }

                    return showRow;
                });
                return filteredRows;
            }

            // Fonction pour verifier la condition dune alerte specifique
            function checkAlertCondition(row, alertType, todayTimestamp) {
                 var endTimestamp = parseInt(row.data("end-timestamp"), 10) || 0;
                 var startTimestamp = parseInt(row.data("start-timestamp"), 10) || 0;
                 var sevenDaysInSeconds = 7 * 24 * 60 * 60;
                 var fifteenDaysInSeconds = 15 * 24 * 60 * 60;
                 var thirtyDaysInSeconds = 30 * 24 * 60 * 60;
                 var tenDaysInSeconds = 10 * 24 * 60 * 60;

                 var sevenDaysAgo = todayTimestamp - sevenDaysInSeconds;
                 var fifteenDaysAgo = todayTimestamp - fifteenDaysInSeconds;
                 var thirtyDaysAgo = todayTimestamp - thirtyDaysInSeconds;
                 // var tenDaysFromNow = todayTimestamp + tenDaysInSeconds; // Moins utile ici?

                 switch (alertType) {
                     case "emargement_non_recu_7j":
                         // Formation terminee depuis plus de 7 jours ET emargements non reçus (etat 1=Vide, 2=Partiel, 3=Reçus? -> on veut 1 ou 2)
                         var emargementsStatus = row.data("emargements").toString(); // Comparer comme string
                         return endTimestamp > 0 && endTimestamp < sevenDaysAgo && (emargementsStatus === "1" || emargementsStatus === "2");

                    case "formation_confirmee_formateur_absent":
                         // Statut commande confirme (ou equivalents comme certifreal) ET champ formateurs vide ou "Non defini"
                         var status = row.data("status");
                         var isConfirmed = ["confirme", "certifreal", "avenantvalide", "avenantconv"].includes(status);
                         var formateurs = (row.data("formateurs") || "").trim();
                         return isConfirmed && (formateurs === "" || formateurs.toLowerCase() === "non défini" || formateurs.toLowerCase() === "n/d");

                    case "suivi_non_realise_7j":
                         var suivi = (row.data("suivi-realise") || "non").toLowerCase();
                         // Formation terminee depuis > 7 jours ET suivi = non
                         return endTimestamp > 0 && endTimestamp < sevenDaysAgo && suivi === "non";
                    case "suivi_non_realise_15j":
                         var suivi = (row.data("suivi-realise") || "non").toLowerCase();
                         // Formation terminee depuis > 15 jours ET suivi = non
                         return endTimestamp > 0 && endTimestamp < fifteenDaysAgo && suivi === "non";
                    case "suivi_non_realise_30j":
                          var suivi = (row.data("suivi-realise") || "non").toLowerCase();
                         // Formation terminee depuis > 30 jours ET suivi = non
                         return endTimestamp > 0 && endTimestamp < thirtyDaysAgo && suivi === "non";

                    // --- PLACEHOLDERS --- Ajoutez la logique quand les donnees seront disponibles via data-*
                    case "conv_signee_non_recue":
                         // Necessite data-convention-status="envoyee" (ou similaire) et date envoi > X jours?
                         console.warn("Filtre Alerte non implemente: conv_signee_non_recue");
                         return false; // Bloque tout pour cette alerte tant que non implemente
                    case "conv_non_envoyee":
                         // Necessite data-convention-status="non_envoyee" (ou similaire) et statut commande pertinent?
                         console.warn("Filtre Alerte non implemente: conv_non_envoyee");
                         return false;
                    case "formation_erreur":
                         // Necessite data-formation-erreur="true" (ou similaire)
                         console.warn("Filtre Alerte non implemente: formation_erreur");
                         return false;
                     case "formateur_option_non_confirme_15j": // etc...
                     case "formateur_prebloque_non_confirme_15j":
                     case "formateur_confirme_lm_non_envoyee":
                     case "inter_confirme_manque_element":
                     case "inter_non_confirme_15j":
                     case "option_non_confirmee_7j":
                     case "stagiaires_non_renseignes_10j":
                     case "convocations_attente":
                     case "opco_num_dossier_manquant":
                     case "opco_dossier_non_recu_passe_10j":
                     case "devis_relance_15j":
                         console.warn("Filtre Alerte non implemente: " + alertType);
                         return false; // Bloque laffichage pour ces alertes pour linstant

                    default:
                         console.warn("Type dalerte inconnu: " + alertType);
                         return true; // Si alerte inconnue, on ne filtre pas par defaut (ou false pour etre strict?)
                 }
            }


            // Fonction pour appliquer tous les filtres et mettre a jour laffichage
            function applyFiltersAndDisplay() {
                var filteredRows = getFilteredRows();

                allRowsCache.hide(); // Cacher toutes les lignes (meme celles qui ne sont pas dans le cache initial si ajout dynamique)

                // Appliquer la pagination ou Afficher tout
                if (showAll) {
                    filteredRows.show();
                    $("#fsbdd-show-all").text("Afficher les " + itemsPerPage + " premières"); // Texte pour passer en mode pagine
                } else {
                    // Mode pagine: n affiche que la tranche [start, end)
                    var start = (currentPage - 1) * itemsPerPage;
                    var end = start + itemsPerPage;
                    filteredRows.slice(start, end).show();
                    // Mettre a jour le bouton pour passer en mode "Afficher tout"
                    $("#fsbdd-show-all").text("Afficher tout (" + filteredRows.length + ")");
                }

                updateFilterCount(filteredRows.length);

                // Afficher/cacher le bouton "Effacer filtre alerte"
                $("#fsbdd-clear-alert-filter").toggle(!!activeAlertType);
            }

            // Fonction pour mettre a jour le compteur de resultats
            function updateFilterCount(visibleCount) {
                 var totalInTable = allRowsCache.length; // Total initial
                 var text = visibleCount + " résultat(s) trouvé(s)";
                 if (totalInTable > 0 && totalInTable !== visibleCount) {
                     //text += " sur " + totalInTable + " au total"; // Moins utile si trié
                 }
                 if (!showAll && visibleCount > itemsPerPage) {
                     var totalPages = Math.ceil(visibleCount / itemsPerPage);
                     // text += " (Page " + currentPage + "/" + totalPages + ")"; // On na pas de pagination reelle
                     var start = (currentPage - 1) * itemsPerPage + 1;
                     var end = Math.min(start + itemsPerPage -1, visibleCount);
                     text = "Affichage de " + start + " à " + end + " sur " + visibleCount + " résultats";
                 } else if (visibleCount > 0) {
                     text = visibleCount + " résultat(s) affiché(s)";
                 }

                 $("#fsbdd-filtered-count").text(text);

                 // Afficher/Masquer le bouton AfficherTout/Paginer si necessaire
                 $("#fsbdd-show-all").toggle(visibleCount > itemsPerPage);
                 if(visibleCount <= itemsPerPage) {
                     // Si moins de resultats que la taille de page, on est toujours en mode "tout afficher"
                     showAll = true;
                 }


            }

            // Evenements de changement sur les filtres standards
            $(".fsbdd-filter").on("change keyup", function() {
                // Utiliser un delai pour la recherche pour ne pas filtrer a chaque touche
                clearTimeout($.data(this, "timer"));
                var wait = $(this).is("input") ? 300 : 0; // Delai seulement pour input text
                 $(this).data("timer", setTimeout(function() {
                    currentPage = 1; // Revenir a la premiere page lors du filtrage standard
                    applyFiltersAndDisplay();
                }, wait));
            });

            // Evenement de clic sur une alerte
            $(".fsbdd-alert-item").on("click", function() {
                 var clickedItem = $(this);
                 // Ne rien faire pour les alertes non filtrables
                 if (clickedItem.hasClass("fsbdd-alert-warning") || clickedItem.hasClass("fsbdd-alert-info")) {
                     return;
                 }

                var clickedAlertType = clickedItem.data("alert-type");

                if (clickedItem.hasClass("active")) {
                    // Si on clique sur lalerte deja active, on la desactive
                    activeAlertType = null;
                    clickedItem.removeClass("active");
                } else {
                    // Sinon, on active la nouvelle alerte
                    $(".fsbdd-alert-item").removeClass("active"); // Desactiver les autres eventuellement actives
                    clickedItem.addClass("active");
                    activeAlertType = clickedAlertType;
                }
                currentPage = 1; // Revenir a la premiere page quand on change de filtre alerte
                applyFiltersAndDisplay();
            });

             // Evenement pour effacer le filtre dalerte via le lien
             $("#fsbdd-clear-alert-filter").on("click", function(e) {
                 e.preventDefault();
                 activeAlertType = null;
                 $(".fsbdd-alert-item").removeClass("active");
                 currentPage = 1;
                 applyFiltersAndDisplay();
             });


            // Bouton Afficher tout / par page
            $("#fsbdd-show-all").on("click", function() {
                showAll = !showAll; // Basculer letat
                currentPage = 1; // Revenir a la page 1 quand on change de mode daffichage
                applyFiltersAndDisplay(); // Re-applique les filtres avec le nouvel etat showAll
            });

            // Initialiser laffichage au chargement (avec filtres par defaut)
             applyFiltersAndDisplay();

        });
    </script>';

    echo '</div>'; // Fin du dashboard
}


/**
 * Recupere les donnees des commandes et sessions associees.
 * Enrichit les donnees pour permettre le filtrage des alertes.
 */
function get_consultant_orders_data($referent_id = null, $limit = 1000) {
    global $wpdb;

    // Statuts consideres comme "Terminé" pour certaines alertes (emargement, suivi)
    $completed_statuses = ['wc-completed', 'wc-certifreal']; // A adapter si necessaire

    // Recuperer les commandes recentes/pertinentes
    $query_args = array(
        'limit'   => $limit, // Limite elevee pour avoir assez de donnees pour les filtres
        'orderby' => 'date',
        'order'   => 'DESC',
        'return'  => 'ids',
        // On pourrait ajouter un filtre de date ici pour optimiser,
        // mais les alertes peuvent concerner des elements anciens (ex: factures)
        // 'date_query' => array(
        //     array(
        //         'after' => '1 year ago', // Exemple: ne prendre que les commandes de la derniere annee
        //         'inclusive' => true,
        //     ),
        // ),
    );

    // Filtre par referent si specifie (role 'referent')
    if ($referent_id) {
        $query_args['meta_key'] = 'fsbdd_user_referentrel'; // Assurez-vous que cette cle est correcte
        $query_args['meta_value'] = $referent_id;
        $query_args['meta_compare'] = '=';
    }

    $order_ids = wc_get_orders($query_args);

    if (empty($order_ids)) {
        return array();
    }

    // Precharger les donnees necessaires en une fois si possible (moins performant pour beaucoup de metas)
    // Il est souvent plus performant de recuperer les metas dans la boucle pour chaque commande
    // Mais on liste les cles necessaires pour clarte
    $needed_order_meta_keys = [
        'fsbdd_user_referentrel', 'fsbdd_financeopco', 'fsbdd_effectif',
        'fsbdd_numconv', 'fsbdd_suivireal', '_customer_user',
        'fsbdd_convention_status', /* Cle a ajouter pour etat convention */
        'fsbdd_formation_erreur', /* Cle a ajouter pour formation en erreur */
        // Ajoutez dautres cles de meta COMMANDE ici si necessaire
    ];
    $needed_session_meta_keys = [
         'we_startdate', 'we_enddate', 'fsbdd_select_lieusession',
         'fsbdd_etatemargm', 'fsbdd_etatcpterenduf', 'fsbdd_etateval',
         // Ajoutez dautres cles de meta SESSION ici si necessaire
    ];

    // Recuperer tous les IDs de clients/prospects associes aux users des commandes
    $customer_user_ids = [];
    foreach($order_ids as $order_id) {
        $user_id = get_post_meta($order_id, '_customer_user', true);
        if ($user_id) {
            $customer_user_ids[$user_id] = $user_id; // Stocke les IDs uniques
        }
    }
    $client_relations = []; // Tableau pour stocker les relations user_id => CPT_id, CPT_type
    if (!empty($customer_user_ids)) {
        // Assurez-vous que le nom de table et les types de relation sont corrects
        $rel_table = $wpdb->prefix . 'mb_relationships';
        $allowed_rel_types = "'clients-wp-bdd', 'prospects-wp-bdd'"; // Adaptez si necessaire
        $user_ids_string = implode(',', array_map('intval', $customer_user_ids));

        if ($wpdb->get_var("SHOW TABLES LIKE '$rel_table'") == $rel_table) { // Verifier si la table existe
             $results = $wpdb->get_results(
                 $wpdb->prepare(
                     "SELECT `from`, `to`, `type`
                      FROM {$rel_table}
                      WHERE `from` IN ($user_ids_string) AND `type` IN ($allowed_rel_types)"
                 ), ARRAY_A
             );
             if ($results) {
                 foreach ($results as $row) {
                     $client_relations[$row['from']] = [
                         'id' => $row['to'],
                         'type' => strpos($row['type'], 'client') !== false ? 'client' : 'prospect'
                     ];
                 }
             }
        } else {
             error_log("FSBDD Dashboard: La table de relations $rel_table n existe pas.");
        }
    }


    // Traitement des commandes pour construire le tableau de donnees
    $result = array();
    $referent_cache = []; // Cache pour les noms de referents
    $doc_status_map = [ // Mapper les valeurs des etats de doc en texte
        '1' => 'Vide',
        '2' => 'Partiel',
        '3' => 'Reçus',
        '4' => 'Certifié',
        ''  => 'Vide', // Gerer le cas vide
        null => 'Vide'
    ];

    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) continue;

        $order_meta = get_post_meta($order_id); // Recuperer toutes les metas pour cette commande

        // --- Donnees de la commande ---
        $status = $order->get_status(); // Ex: 'processing', 'completed', 'confirme', etc.
        $status_name = wc_get_order_status_name($status); // Nom traduit
        $customer_user_id = $order_meta['_customer_user'][0] ?? null;

        // --- Referent ---
        $referent_id_meta = $order_meta['fsbdd_user_referentrel'][0] ?? null;
        $referent_name = 'N/A';
        if ($referent_id_meta) {
            if (!isset($referent_cache[$referent_id_meta])) {
                $user_info = get_userdata($referent_id_meta);
                if ($user_info) {
                     $name = trim($user_info->first_name . ' ' . $user_info->last_name);
                     $referent_cache[$referent_id_meta] = !empty($name) ? $name : $user_info->display_name;
                } else {
                    $referent_cache[$referent_id_meta] = 'ID: ' . $referent_id_meta; // Fallback
                }
            }
            $referent_name = $referent_cache[$referent_id_meta];
        }

        // --- Client ---
        $billing_company = $order->get_billing_company();
        $customer_name = empty($billing_company) || strtolower($billing_company) === 'pas de société' ? $order->get_formatted_billing_full_name() : $billing_company;
        $client_cpt_id = null;
        $client_cpt_type = null;
        if ($customer_user_id && isset($client_relations[$customer_user_id])) {
             $client_cpt_id = $client_relations[$customer_user_id]['id'];
             $client_cpt_type = $client_relations[$customer_user_id]['type'];
        }

        // --- OPCO ---
        $opco_value = $order_meta['fsbdd_financeopco'][0] ?? '1'; // Default 'NON'
        $opco = ($opco_value === '2') ? 'OUI' : 'NON';

        // --- Autres metas de commande ---
        $effectif = $order_meta['fsbdd_effectif'][0] ?? '0';
        $convention = $order_meta['fsbdd_numconv'][0] ?? '';
        $suivi_realise_raw = $order_meta['fsbdd_suivireal'][0] ?? 'non'; // Supposons 'oui'/'non' ou 1/0
        $suivi_realise = (strtolower($suivi_realise_raw) === 'oui' || $suivi_realise_raw === '1' || $suivi_realise_raw === true) ? 'oui' : 'non';


        // --- Donnees liees a la session (Action de Formation) ---
        $session_id = null;
        $session_title = '';
        $start_date = ''; $end_date = '';
        $start_timestamp = 0; $end_timestamp = 0;
        $lieu_resume = 'N/D';
        $emargements = '1'; $cpte_rendu = '1'; $evaluations = '1';
        $formateurs_string = '';

        // Trouver la session liee (prendre la premiere trouvee dans les items)
        foreach ($order->get_items() as $item_id => $item) {
            // La cle meta peut varier, verifier 'fsbdd_relsessaction_cpt_produit' ou autre
            $item_session_id = wc_get_order_item_meta($item_id, 'fsbdd_relsessaction_cpt_produit', true);
            if ($item_session_id && get_post_type($item_session_id) === 'action-de-formation') {
                $session_id = $item_session_id;
                break; // On prend la premiere session trouvee
            }
             // Peut-etre essayer une autre cle si la premiere ne marche pas?
             // $item_session_id = wc_get_order_item_meta($item_id, '_linked_session_id', true); // Exemple
        }

        if ($session_id) {
            $session_post = get_post($session_id);
            $session_title = $session_post ? $session_post->post_title : 'Session ID ' . $session_id;
            $session_meta = get_post_meta($session_id);

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

            // Etats des documents
            $emargements = $session_meta['fsbdd_etatemargm'][0] ?? '1';
            $cpte_rendu = $session_meta['fsbdd_etatcpterenduf'][0] ?? '1';
            $evaluations = $session_meta['fsbdd_etateval'][0] ?? '1';

            // Formateurs (via Meta Box Group)
            $formateurs_list = [];
            // Assurer que la fonction rwmb_meta existe (Meta Box)
            if (function_exists('rwmb_meta')) {
                 // La cle du groupe est 'fsbdd_grpctsformation'
                 // La cle du champ select DANS le groupe est 'fsbdd_selectcoutform'
                $formateurs_group = rwmb_meta('fsbdd_grpctsformation', ['object_type' => 'post'], $session_id);
                if (!empty($formateurs_group) && is_array($formateurs_group)) {
                    foreach ($formateurs_group as $formateur_data) {
                        if (!empty($formateur_data['fsbdd_selectcoutform'])) {
                            $formateur_id = $formateur_data['fsbdd_selectcoutform'];
                            // Verifier si l ID est valide et recuperer le titre
                            if (is_numeric($formateur_id) && $formateur_post = get_post($formateur_id)) {
                                $formateurs_list[] = $formateur_post->post_title;
                            } elseif (is_string($formateur_id)) {
                                 $formateurs_list[] = $formateur_id; // Si ce nest pas un ID mais un nom?
                            }
                        }
                    }
                }
            } else {
                 error_log("FSBDD Dashboard: La fonction rwmb_meta (Meta Box) nest pas disponible.");
            }
            $formateurs_string = !empty($formateurs_list) ? implode(', ', array_unique($formateurs_list)) : ''; // Utiliser '' si vide
        } // Fin si session_id trouve

        // --- Declenchement (depuis CPT Client si disponible) ---
        $declenchement = 'N/A';
        if ($client_cpt_id) {
            // Assurez-vous que la cle meta est correcte
            $declenchement_value = get_post_meta($client_cpt_id, 'fsbdd_select_suivi_declenchmt', true);
            $declenchement = ($declenchement_value === '1') ? 'OUI' : (($declenchement_value === '0') ? 'NON' : 'N/A');
        }


        // --- Assembler les donnees pour cette ligne ---
        $result[] = [
            'order_id' => $order_id,
            'order_number' => $order->get_order_number(),
            'client_name' => $customer_name,
            'client_cpt_id' => $client_cpt_id,
            'client_cpt_type' => $client_cpt_type,
            'session_id' => $session_id,
            'session_title' => $session_title,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'start_timestamp' => $start_timestamp,
            'end_timestamp' => $end_timestamp,
            'lieu_resume' => $lieu_resume,
            'convention' => $convention,
            'status' => $status, // Statut brut (ex: 'wc-processing')
            'status_name' => $status_name, // Nom traduit
            'effectif' => $effectif,
            'opco' => $opco, // 'OUI' ou 'NON'
            'emargements' => $emargements, // Code etat (1, 2, 3, 4)
            'cpte_rendu' => $cpte_rendu,     // Code etat (1, 2, 3, 4)
            'evaluations' => $evaluations,   // Code etat (1, 2, 3, 4)
            'emargements_text' => $doc_status_map[$emargements] ?? 'Vide', // Texte pour tooltip
            'cpte_rendu_text' => $doc_status_map[$cpte_rendu] ?? 'Vide', // Texte pour tooltip
            'evaluations_text' => $doc_status_map[$evaluations] ?? 'Vide', // Texte pour tooltip
            'referent_id' => $referent_id_meta,
            'referent_name' => $referent_name,
            'declenchement' => $declenchement, // 'OUI', 'NON', 'N/A'
            'suivi_realise' => $suivi_realise, // 'oui' ou 'non'
            'formateurs' => $formateurs_string, // Noms separes par virgule, ou vide
            // Ajoutez ici les autres donnees recuperees pour les alertes
            // 'convention_status' => $order_meta['fsbdd_convention_status'][0] ?? 'na',
            // 'formation_erreur' => $order_meta['fsbdd_formation_erreur'][0] ?? 'false',
        ];
    }

    return $result;
}

/**
 * Recupere la liste des utilisateurs avec le role referent
 */
function get_referents_list() {
    $referents = [];
    // Specifier les champs necessaires pour optimiser
    $referent_users = get_users(['role' => 'referent', 'fields' => ['ID', 'display_name', 'first_name', 'last_name']]);
    foreach ($referent_users as $user) {
        $name = trim($user->first_name . ' ' . $user->last_name);
        $referents[$user->ID] = !empty($name) ? $name : $user->display_name; // Utiliser nom complet ou display_name
    }
    // Trier par nom pour le select
    asort($referents);
    return $referents;
}

// La fonction display_consultant_summary nest plus utilisee par le dashboard principal.
// Vous pouvez la supprimer ou la garder si elle sert ailleurs.
/*
function display_consultant_summary($orders_data) {
    // ... code de lancienne fonction ...
}
*/

