<?php
/**
 * Snippet ID: 163
 * Name: REPORT alertes global accueil admin v3 FILTRES & ALERTES
 * Description: 
 * @active true
 */

/**
 * REPORT alertes global accueil admin v3 filtres et alertes
 */
/**
 * Filtres et alertes pour le Dashboard des actions de formation
 * Séparé du tableau principal pour une meilleure modularité
 */

// Ne pas exécuter directement
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajoute le panneau des alertes sur la page d'accueil admin
 * pour les utilisateurs autorisés
 */
add_action('admin_notices', 'fsbdd_dashboard_alerts', 10);

function fsbdd_dashboard_alerts() {
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
	
	
	// Si l'utilisateur est un référent, ajouter champs cachés pour filtrage automatique
//$is_referent = in_array('referent', (array) $current_user->roles);
//$is_admin = in_array('administrator', (array) $current_user->roles);

//if ($is_referent && !$is_admin) {
//    echo '<input type="hidden" id="fsbdd-current-user-role" value="referent">';
//   echo '<input type="hidden" id="fsbdd-current-user-id" value="' . esc_attr($current_user->ID) . '">';
//}

    // CSS pour le nouveau design des alertes
    echo '<style>
        /* Conteneur alertes plus compact et organisé */
        .fsbdd-alerts-container {
            background-color: #314150;
            border-radius: 8px;
            padding: 16px 0px;
			color: #fff;
        }
		
		.fsbdd-alerts-container h3 {
    color: #fff;
}


        /* Section spéciale pour les alertes de rapprochements */
        .fsbdd-rappro-alerts-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
    padding: 12px;
    background-color: #fff;
    border-radius: 4px;
    box-shadow: 3px 2px 8px rgb(0 0 0 / 25%);
}

        .fsbdd-rappro-alert-item {
            display: flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            background-color: #fff;
            color: #495057;
            border: 1px solid #0073aa;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .fsbdd-rappro-alert-item:hover {
            backgroud-color: #fff8e1;
            transform: translateY(-1px);
            box-shadow: 4px 4px 9px rgb(0 0 0 / 34%);
        }

        .fsbdd-rappro-alert-item.active {
            background-color: #daecff;
            font-weight: 600;
            box-shadow: 4px 4px 9px rgb(0 0 0 / 25%);
        }

        .fsbdd-rappro-alert-item .fsbdd-alert-counter {
    font-size: 12px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 12px;
    background-color: transparent;
    color: #dc3545a1;
        }
		

        .fsbdd-rappro-section-title {
            width: 100%;
            font-size: 13px;
            font-weight: 600;
            color: #000;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

.fsbdd-alerts-wrapper { 
    display: grid; 
    grid-template-columns: repeat(6, 1fr);
    gap: 8px; 
}

@media (max-width: 1100px) {
    .fsbdd-alerts-wrapper {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 480px) {
    .fsbdd-alerts-wrapper {
        grid-template-columns: repeat(2, 1fr);
    }
}

        .fsbdd-alerts-section {
    background-color: white;
    border-radius: 4px;
    box-shadow: 5px 3px 8px rgb(0 0 0 / 20%);
    padding: 6px;
    transition: all 0.2s ease;
	color: #000;
}

        .fsbdd-empty-section {
            min-height: 60px;
        }

        .fsbdd-alerts-section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 8px;
            margin-bottom: 4px;
			margin-top: 4px;
        }

        .fsbdd-counter-badge {
    font-size: 18px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 12px;
    background-color: #fff;
    color: #dc3545a1;
}

        /* Options de contrôle */
        .fsbdd-controls {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        /* Style pour les valeurs non-zéro */
        .fsbdd-alert-counter:not(:empty):not(:contains("0")) {
            font-weight: 700;
            color: white;
            background-color: #dc3545;
        }

        /* Liste alertes avec hover cards */
        .fsbdd-alerts-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        /* Alerte avec indicateur urgence */
        .fsbdd-alert-item {
            display: flex;
            align-items: center;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            cursor: pointer;
            background-color: #fff;
            color: #495057;
            border-left: 4px solid #ccc;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
            position: relative;
        }

        .fsbdd-alert-item:hover {
            background-color: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 2px 2px 7px rgb(0 0 0 / 34%);
        }

        .fsbdd-alert-item.active {
            background-color: #e7f5ff;
            color: #1864ab;
            font-weight: 500;
            border-left-width: 4px;
			box-shadow: 2px 2px 7px rgb(0 0 0 / 34%);
        }

        /* Indicateur urgence - masqué */
        .fsbdd-urgence-indicator {
            display: none;
        }

        /* Compteurs avec couleurs durgence haute (rouge clair) */
        .fsbdd-alert-item[data-alert-type="rappro_session_manquant"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="rappro_quantites_manquant"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="emargement_non_recu_7j"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="formation_erreur"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="conv_non_envoyee"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="formation_confirmee_formateur_absent"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="suivi_non_realise_30j"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="facture_non_envoyee"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="attestation_facture_non_envoyee"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="facture_echeance_depassee"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="facture_statut_incorrect"] .fsbdd-alert-counter {
            background-color: #ffe0e0;
            color: #c92a2a;
        }
        
        /* Compteurs avec couleurs durgence moyenne (orange clair) */
        .fsbdd-alert-item[data-alert-type="rappro_specificites_manquant"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="rappro_convocations_manquant"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="rappro_subro_manquant"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="conv_signee_non_recue"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="conv_statut_inchange"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="formateur_option_non_confirme_15j"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="formateur_prebloque_non_confirme_15j"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="inter_confirme_manque_element"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="inter_non_confirme_15j"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="option_non_confirmee_7j"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="suivi_non_realise_15j"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="stagiaires_non_renseignes_10j"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="opco_dossier_non_recu_passe_10j"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="facture_envoyee_non_reglee"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="attestation_facture_non_reglee"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="facture_relance_15j"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="facture_payee_sans_convention"] .fsbdd-alert-counter {
            background-color: #fff4e0;
            color: #e8590c;
        }
        
        /* Compteurs avec couleurs durgence basse (vert clair) */
        .fsbdd-alert-item[data-alert-type="rappro_complet"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="rappro_client_manquant"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="formateur_confirme_lm_non_envoyee"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="suivi_non_realise_7j"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="convocations_attente"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="opco_num_dossier_manquant"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="devis_relance_15j"] .fsbdd-alert-counter,
        .fsbdd-alert-item[data-alert-type="facture_payee_a_valider"] .fsbdd-alert-counter {
            background-color: #e0f9f0;
            color: #087f5b;
        }

        /* Compteur par alerte */
        .fsbdd-alert-counter {
            margin-left: auto;
            font-size: 11px;
            font-weight: 600;
            background-color: #e9ecef;
            color: #495057;
            min-width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            padding: 0 5px;
        }

        /* Alertes par catégorie */
        .fsbdd-alert-item[data-alert-type^="conv_"] {
            border-left-color: #7950f2;
        }
        .fsbdd-alert-item[data-alert-type^="emargement_"],
        .fsbdd-alert-item[data-alert-type^="formation_"] {
            border-left-color: #fa5252;
        }
        .fsbdd-alert-item[data-alert-type^="formateur_"] {
            border-left-color: #20c997;
        }
        .fsbdd-alert-item[data-alert-type^="inter_"],
        .fsbdd-alert-item[data-alert-type^="option_"] {
            border-left-color: #228be6;
        }
        .fsbdd-alert-item[data-alert-type^="suivi_"] {
            border-left-color: #40c057;
        }
        .fsbdd-alert-item[data-alert-type^="stagiaires_"],
        .fsbdd-alert-item[data-alert-type^="convocations_"] {
            border-left-color: #fab005;
        }
        .fsbdd-alert-item[data-alert-type^="opco_"],
        .fsbdd-alert-item[data-alert-type^="devis_"] {
            border-left-color: #fd7e14;
        }
        .fsbdd-alert-item[data-alert-type^="facture_"],
        .fsbdd-alert-item[data-alert-type^="attestation_"] {
            border-left-color: #12b886;
        }
        .fsbdd-alert-item[data-alert-type^="rappro_"] {
            border-left-color: #9775fa;
        }

        /* Bouton pour effacer le filtre */
        #fsbdd-clear-alert-filter {
            font-size: 11px;
            color: #495057;
            cursor: pointer;
            text-decoration: none;
            display: none;
            background: #e9ecef;
            padding: 3px 8px;
            border-radius: 3px;
            border: 1px solid #ced4da;
            transition: all 0.2s ease;
        }
        #fsbdd-clear-alert-filter:hover {
            background: #dee2e6;
            color: #212529;
        }
    </style>';

    // Section des Alertes avec design amélioré et compteurs
    echo '<div class="fsbdd-alerts-container">';
    echo '<h3>Alertes <a href="#" id="fsbdd-clear-alert-filter">Effacer le filtre</a></h3>';

    // Ajout du contrôle pour afficher ou masquer les alertes à zéro
    echo '<div class="fsbdd-controls">
        <label><input type="checkbox" id="fsbdd-show-zeros" onchange="toggleZeroAlerts()"> Afficher alertes à zéro</label>
        <button id="fsbdd-refresh-alerts" class="button button-small">Actualiser</button>
    </div>';

    // Section spéciale pour les alertes de rapprochements (provisoires) - affichage horizontal
    echo '<div class="fsbdd-rappro-alerts-wrapper">';
    echo '<div class="fsbdd-rappro-section-title">Rappros à contrôler (provisoires) <span class="fsbdd-counter-badge rapprochements-count">0</span></div>';
    echo '<div class="fsbdd-rappro-alert-item" data-alert-type="rappro_complet">Complets <span class="fsbdd-alert-counter rappro-complet-count">0</span></div>';
    echo '<div class="fsbdd-rappro-alert-item" data-alert-type="rappro_session_manquant">Session <span class="fsbdd-alert-counter rappro-session-count">0</span></div>';
    echo '<div class="fsbdd-rappro-alert-item" data-alert-type="rappro_specificites_manquant">Spécificités <span class="fsbdd-alert-counter rappro-specificites-count">0</span></div>';
    echo '<div class="fsbdd-rappro-alert-item" data-alert-type="rappro_convocations_manquant">Convocations <span class="fsbdd-alert-counter rappro-convocations-count">0</span></div>';
    echo '<div class="fsbdd-rappro-alert-item" data-alert-type="rappro_quantites_manquant">Qtés/Coûts <span class="fsbdd-alert-counter rappro-quantites-count">0</span></div>';
    echo '<div class="fsbdd-rappro-alert-item" data-alert-type="rappro_subro_manquant">Subro/Règlements <span class="fsbdd-alert-counter rappro-subro-count">0</span></div>';
    echo '<div class="fsbdd-rappro-alert-item" data-alert-type="rappro_client_manquant">Client BDD/Web <span class="fsbdd-alert-counter rappro-client-count">0</span></div>';
    
    // Filtres par stade de completion
    echo '<div class="fsbdd-rappro-alert-item" data-alert-type="rappro_stade_1">1/6 <span class="fsbdd-alert-counter rappro-stade-1-count">0</span></div>';
    echo '<div class="fsbdd-rappro-alert-item" data-alert-type="rappro_stade_2">2/6 <span class="fsbdd-alert-counter rappro-stade-2-count">0</span></div>';
    echo '<div class="fsbdd-rappro-alert-item" data-alert-type="rappro_stade_3">3/6 <span class="fsbdd-alert-counter rappro-stade-3-count">0</span></div>';
    echo '<div class="fsbdd-rappro-alert-item" data-alert-type="rappro_stade_4">4/6 <span class="fsbdd-alert-counter rappro-stade-4-count">0</span></div>';
    echo '<div class="fsbdd-rappro-alert-item" data-alert-type="rappro_stade_5">5/6 <span class="fsbdd-alert-counter rappro-stade-5-count">0</span></div>';
    
    echo '</div>';

    // Wrapper pour layout horizontal par section (autres alertes)
    echo '<div class="fsbdd-alerts-wrapper">';

    // Documents & Émargements
    echo '<div class="fsbdd-alerts-section">';
    echo '<h4 class="fsbdd-alerts-section-title">Docs & Émargemts <span class="fsbdd-counter-badge documents-count">0</span></h4>';
    echo '<ul class="fsbdd-alerts-list">';
    echo '<li class="fsbdd-alert-item" data-alert-type="emargement_non_recu_7j"><span class="fsbdd-urgence-indicator urgence-haute"></span>Non reçus (+7j) <span class="fsbdd-alert-counter emargement-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="formation_erreur"><span class="fsbdd-urgence-indicator urgence-haute"></span>Formation en erreur <span class="fsbdd-alert-counter formation-erreur-count">0</span></li>';
    echo '</ul>';
    echo '</div>';

    // Conventions
    echo '<div class="fsbdd-alerts-section">';
    echo '<h4 class="fsbdd-alerts-section-title">Conventions <span class="fsbdd-counter-badge conventions-count">0</span></h4>';
    echo '<ul class="fsbdd-alerts-list">';
    echo '<li class="fsbdd-alert-item" data-alert-type="conv_signee_non_recue"><span class="fsbdd-urgence-indicator urgence-moyenne"></span>Non reçues <span class="fsbdd-alert-counter conv-non-recue-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="conv_non_envoyee"><span class="fsbdd-urgence-indicator urgence-haute"></span>Non envoyées <span class="fsbdd-alert-counter conv-non-envoyee-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="conv_statut_inchange"><span class="fsbdd-urgence-indicator urgence-moyenne"></span>Statut inchangé (+2j) <span class="fsbdd-alert-counter conv-statut-count">0</span></li>';
    echo '</ul>';
    echo '</div>';

    // Formateurs
    echo '<div class="fsbdd-alerts-section">';
    echo '<h4 class="fsbdd-alerts-section-title">Formateurs <span class="fsbdd-counter-badge formateurs-count">0</span></h4>';
    echo '<ul class="fsbdd-alerts-list">';
    echo '<li class="fsbdd-alert-item" data-alert-type="formateur_option_non_confirme_15j"><span class="fsbdd-urgence-indicator urgence-moyenne"></span>Optionné non confirmé (-15j) <span class="fsbdd-alert-counter formateur-option-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="formateur_prebloque_non_confirme_15j"><span class="fsbdd-urgence-indicator urgence-moyenne"></span>Prébloqué non confirmé (-15j) <span class="fsbdd-alert-counter formateur-prebloque-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="formateur_confirme_lm_non_envoyee"><span class="fsbdd-urgence-indicator urgence-basse"></span>LM non envoyée <span class="fsbdd-alert-counter formateur-lm-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="formation_confirmee_formateur_absent"><span class="fsbdd-urgence-indicator urgence-haute"></span>Formateur manquant <span class="fsbdd-alert-counter formateur-absent-count">0</span></li>';
    echo '</ul>';
    echo '</div>';

    // Sessions & Suivi
    echo '<div class="fsbdd-alerts-section">';
    echo '<h4 class="fsbdd-alerts-section-title">Sessions & Suivi <span class="fsbdd-counter-badge sessions-count">0</span></h4>';
    echo '<ul class="fsbdd-alerts-list">';
    echo '<li class="fsbdd-alert-item" data-alert-type="inter_confirme_manque_element"><span class="fsbdd-urgence-indicator urgence-moyenne"></span>Élément manquant <span class="fsbdd-alert-counter element-manquant-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="inter_non_confirme_15j"><span class="fsbdd-urgence-indicator urgence-moyenne"></span>Non confirmée (-15j) <span class="fsbdd-alert-counter non-confirmee-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="option_non_confirmee_7j"><span class="fsbdd-urgence-indicator urgence-moyenne"></span>Option non confirmée (+7j) <span class="fsbdd-alert-counter option-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="suivi_non_realise_7j"><span class="fsbdd-urgence-indicator urgence-basse"></span>Suivi non fait (+7j) <span class="fsbdd-alert-counter suivi-7j-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="suivi_non_realise_15j"><span class="fsbdd-urgence-indicator urgence-moyenne"></span>Suivi non fait (+15j) <span class="fsbdd-alert-counter suivi-15j-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="suivi_non_realise_30j"><span class="fsbdd-urgence-indicator urgence-haute"></span>Suivi non fait (+30j) <span class="fsbdd-alert-counter suivi-30j-count">0</span></li>';
    echo '</ul>';
    echo '</div>';

    // Stagiaires & OPCO
    echo '<div class="fsbdd-alerts-section">';
    echo '<h4 class="fsbdd-alerts-section-title">Stagiaires & OPCO <span class="fsbdd-counter-badge stagiaires-count">0</span></h4>';
    echo '<ul class="fsbdd-alerts-list">';
    echo '<li class="fsbdd-alert-item" data-alert-type="stagiaires_non_renseignes_10j"><span class="fsbdd-urgence-indicator urgence-moyenne"></span>Manquants (-10j) <span class="fsbdd-alert-counter stagiaires-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="convocations_attente"><span class="fsbdd-urgence-indicator urgence-basse"></span>Convocations en attente <span class="fsbdd-alert-counter convocations-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="opco_num_dossier_manquant"><span class="fsbdd-urgence-indicator urgence-basse"></span>N° dossier OPCO manquant <span class="fsbdd-alert-counter opco-num-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="opco_dossier_non_recu_passe_10j"><span class="fsbdd-urgence-indicator urgence-moyenne"></span>Dossier OPCO non reçu (+10j) <span class="fsbdd-alert-counter opco-dossier-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="devis_relance_15j"><span class="fsbdd-urgence-indicator urgence-basse"></span>Devis à relancer (+15j) <span class="fsbdd-alert-counter devis-count">0</span></li>';
    echo '</ul>';
    echo '</div>';

    // Facturation et Règlements (ajouté comme filtrable)
    echo '<div class="fsbdd-alerts-section">';
    echo '<h4 class="fsbdd-alerts-section-title">Facturat° & Règlemts <span class="fsbdd-counter-badge facturation-count">0</span></h4>';
    echo '<ul class="fsbdd-alerts-list">';
    echo '<li class="fsbdd-alert-item" data-alert-type="facture_non_envoyee"><span class="fsbdd-urgence-indicator urgence-haute"></span>Facture à envoyer <span class="fsbdd-alert-counter facture-non-envoyee-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="attestation_facture_non_envoyee"><span class="fsbdd-urgence-indicator urgence-haute"></span>Attest° OK, fact non env <span class="fsbdd-alert-counter attestation-facture-non-envoyee-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="facture_envoyee_non_reglee"><span class="fsbdd-urgence-indicator urgence-moyenne"></span>Fact env non réglée <span class="fsbdd-alert-counter facture-non-reglee-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="attestation_facture_non_reglee"><span class="fsbdd-urgence-indicator urgence-moyenne"></span>Attest OK, fact non régl <span class="fsbdd-alert-counter attestation-facture-non-reglee-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="facture_echeance_depassee"><span class="fsbdd-urgence-indicator urgence-haute"></span>Échéance dépassée <span class="fsbdd-alert-counter facture-echeance-count">0</span></li>';
    echo '<li class="fsbdd-alert-item" data-alert-type="facture_relance_15j"><span class="fsbdd-urgence-indicator urgence-moyenne"></span>À relancer (+15j) <span class="fsbdd-alert-counter facture-relance-count">0</span></li>';
	echo '<li class="fsbdd-alert-item" data-alert-type="facture_payee_a_valider"><span class="fsbdd-urgence-indicator urgence-basse"></span>Factures payées à valider <span class="fsbdd-alert-counter facture-payee-valider-count">0</span></li>';
echo '<li class="fsbdd-alert-item" data-alert-type="facture_statut_incorrect"><span class="fsbdd-urgence-indicator urgence-haute"></span>Statut factureok incorrect <span class="fsbdd-alert-counter facture-statut-incorrect-count">0</span></li>';
echo '<li class="fsbdd-alert-item" data-alert-type="facture_payee_sans_convention"><span class="fsbdd-urgence-indicator urgence-moyenne"></span>Payée, conv NON reçue <span class="fsbdd-alert-counter facture-sans-convention-count">0</span></li>';
    echo '</ul>';
    echo '</div>';

    echo '</div>'; // Fin du wrapper grid
    echo '</div>'; // Fin de la section des Alertes

    // Section des filtres standards
    echo '<div class="fsbdd-filters-container" style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 15px; padding: 15px; background: #fff; border: 1px solid #e3e6ea; border-radius: 4px;">';

// Filtre par statut de commande
echo '<div style="flex: 1 1 200px;">';
echo '<label for="fsbdd-filter-status" style="display: block; font-weight: 600; font-size: 12px; color: #444; margin-bottom: 5px;">Statut Commande</label>';
echo '<select id="fsbdd-filter-status" class="fsbdd-filter" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">';
echo '<option value="all">Tous les statuts</option>';

// Statuts personnalisés
echo '<option value="wc-devisproposition">1b - Proposition mail</option>';
echo '<option value="wc-preinscription">2a - Préinscription</option>';
echo '<option value="wc-modifpreinscript">2b - Préinscription modifiée</option>';
echo '<option value="wc-inscription">3 - Inscription</option>';
echo '<option value="wc-confirme">4a - Inscription confirmée</option>';
echo '<option value="wc-avenantconv">4c - Avenant convention</option>';
echo '<option value="wc-avenantvalide">4d - Avenant signé</option>';
echo '<option value="wc-certifreal">5a - Certificat de réalisation</option>';
echo '<option value="wc-attestationform">5b - Attestation de formation</option>';
echo '<option value="wc-facturesent">6a - Facture(s) envoyée(s)</option>';
echo '<option value="wc-factureok">7 - Facture(s) payée(s)</option>';
echo '<option value="wc-echecdevis">9 - Devis non abouti</option>';

// Statuts standards WooCommerce
echo '<option value="wc-pending">Attente paiement</option>';
echo '<option value="wc-checkout-draft">Brouillon</option>';
echo '<option value="wc-on-hold">En attente</option>';
echo '<option value="wc-processing">En cours</option>';
echo '<option value="wc-refunded">Remboursée</option>';
echo '<option value="wc-completed">Terminée</option>';
echo '<option value="wc-failed">Échouée</option>';
echo '<option value="wc-cancelled">Annulée</option>';
echo '<option value="wc-gplsquote-req">Quote Request</option>';

echo '</select>';
echo '</div>';

    // Filtre par période de session
    echo '<div style="flex: 1 1 200px;">';
    echo '<label for="fsbdd-filter-period" style="display: block; font-weight: 600; font-size: 12px; color: #444; margin-bottom: 5px;">Période Session</label>';
    echo '<select id="fsbdd-filter-period" class="fsbdd-filter" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">';
    echo '<option value="proche">Proche (+20 jours)</option>';
    echo '<option value="semaine">Cette semaine</option>';
    echo '<option value="mois">Ce mois</option>';
    echo '<option value="mois+1">Mois prochain</option>';
    echo '<option value="mois+2">Mois +2</option>';
    echo '<option value="mois+3">Mois +3</option>';
    echo '<option value="mois+6">Mois +6</option>';
    echo '<option value="passee">Passée</option>';
    echo '<option value="all" selected>Toutes les périodes</option>';
    echo '</select>';
    echo '</div>';

    // Filtre par référent
    echo '<div style="flex: 1 1 200px;">';
    echo '<label for="fsbdd-filter-referent" style="display: block; font-weight: 600; font-size: 12px; color: #444; margin-bottom: 5px;">Référent</label>';
    echo '<select id="fsbdd-filter-referent" class="fsbdd-filter" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">';
    echo '<option value="all">Tous les référents</option>';

    // Récupérer la liste des référents
    $referents = get_users(array(
        'role__in' => array('administrator', 'referent'),
        'orderby' => 'display_name',
        'order' => 'ASC'
    ));

    foreach ($referents as $referent) {
        echo '<option value="' . esc_attr($referent->ID) . '">' . esc_html($referent->display_name) . '</option>';
    }

    echo '</select>';
    echo '</div>';

    // Filtre par type de client
    echo '<div style="flex: 1 1 200px;">';
    echo '<label for="fsbdd-filter-client-type" style="display: block; font-weight: 600; font-size: 12px; color: #444; margin-bottom: 5px;">Type Client</label>';
    echo '<select id="fsbdd-filter-client-type" class="fsbdd-filter" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">';
    echo '<option value="all">Tous les clients</option>';
    echo '<option value="with-cpt">Avec fiche client</option>';
    echo '<option value="without-cpt">Sans fiche client</option>';
    echo '</select>';
    echo '</div>';

    // Champ de recherche
    echo '<div style="flex: 1 1 200px;">';
    echo '<label for="fsbdd-orders-search" style="display: block; font-weight: 600; font-size: 12px; color: #444; margin-bottom: 5px;">Recherche</label>';
    echo '<input type="text" id="fsbdd-orders-search" placeholder="Client, N° commande, Session..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">';
    echo '</div>';

    echo '</div>'; // Fin de la section des filtres

    // JavaScript pour les filtres et alertes, correctement encapsulé
    echo '<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Variables globales pour stocker létat des filtres
        let activeAlertType = null;
        
        // Initialisation au chargement de la page
        updateAlertCounters();
        
        // Appliquer filtrage automatique par rôle
        var userRole = $("#fsbdd-current-user-role").val();
        var userId = $("#fsbdd-current-user-id").val();
        
        if (userRole === "referent" && userId) {
            // Forcer filtre par référent si l\'utilisateur est référent
            $("#fsbdd-filter-referent").val(userId).prop("disabled", true);
            
            // Filtrer immédiatement les lignes
            setTimeout(function() {
                applyAllFilters();
            }, 200);
        }
        
        // Filtrage par statut des alertes (alertes normales)
        $(".fsbdd-alert-item").on("click", function() {
            const alertType = $(this).data("alert-type");
            const wasActive = $(this).hasClass("active");
            
            // Enlever le filtre actif précédent
            $(".fsbdd-alert-item, .fsbdd-rappro-alert-item").removeClass("active");
            
            // Si le même élément était déjà actif, enlever le filtre
            if (wasActive) {
                activeAlertType = null;
                $("#fsbdd-clear-alert-filter").hide();
            } else {
                // Activer l\'élément cliqué
                $(this).addClass("active");
                activeAlertType = alertType;
                // Afficher le bouton pour effacer le filtre
                $("#fsbdd-clear-alert-filter").css("display", "inline-block");
            }
            
            // Appliquer tous les filtres
            applyAllFilters();
        });
        
        // Filtrage par statut des alertes (alertes de rapprochements horizontales)
        $(".fsbdd-rappro-alert-item").on("click", function() {
            const alertType = $(this).data("alert-type");
            const wasActive = $(this).hasClass("active");
            
            // Enlever le filtre actif précédent
            $(".fsbdd-alert-item, .fsbdd-rappro-alert-item").removeClass("active");
            
            // Si le même élément était déjà actif, enlever le filtre
            if (wasActive) {
                activeAlertType = null;
                $("#fsbdd-clear-alert-filter").hide();
            } else {
                // Activer l\'élément cliqué
                $(this).addClass("active");
                activeAlertType = alertType;
                // Afficher le bouton pour effacer le filtre
                $("#fsbdd-clear-alert-filter").css("display", "inline-block");
            }
            
            // Appliquer tous les filtres
            applyAllFilters();
        });
        
        // Effacer uniquement le filtre d\'alerte
        $("#fsbdd-clear-alert-filter").on("click", function(e) {
            e.preventDefault();
            // Réinitialiser seulement l\'alerte, pas les autres filtres
            $(".fsbdd-alert-item, .fsbdd-rappro-alert-item").removeClass("active");
            activeAlertType = null;
            $(this).hide();
            
            // Appliquer à nouveau les filtres standards
            applyAllFilters();
        });
        
        // Gestion des filtres standards
        $("#fsbdd-filter-status, #fsbdd-filter-period, #fsbdd-filter-referent, #fsbdd-filter-client-type").on("change", function() {
            applyAllFilters();
        });
        
        $("#fsbdd-orders-search").on("keyup", function() {
            applyAllFilters();
        });
        
        // Fonction principale qui applique tous les filtres
        function applyAllFilters() {
            const rows = $("#fsbdd-orders-table tbody tr");
            
            // Appliquer d\'abord les filtres standards
            const filteredRows = applyStandardFilters(rows);
            
            // Ensuite, appliquer le filtre d\'alerte si actif
            let finalFilteredRows;
            if (activeAlertType) {
                finalFilteredRows = applyAlertFilter(filteredRows, activeAlertType);
            } else {
                finalFilteredRows = filteredRows;
            }
            
            // Cacher toutes les lignes
            rows.hide();
            
            // Afficher uniquement les lignes filtrées finales
            finalFilteredRows.show();
            
            // Déclencher l\'événement de changement de filtre
            $(document).trigger("fsbdd-filter-changed", [finalFilteredRows]);
        }
        
        // Fonction pour appliquer les filtres standards
        function applyStandardFilters(rows) {
            const statusFilter = $("#fsbdd-filter-status").val();
            const periodFilter = $("#fsbdd-filter-period").val();
            const referentFilter = $("#fsbdd-filter-referent").val();
            const clientTypeFilter = $("#fsbdd-filter-client-type").val();
            const searchQuery = $("#fsbdd-orders-search").val().toLowerCase();
            
            return rows.filter(function() {
                const row = $(this);
                let shouldShow = true;
                
                // Filtre par statut
                if (statusFilter !== "all") {
                    const rowStatus = row.attr("data-status") || "";
                    const normalizedRowStatus = rowStatus.startsWith("wc-") ? rowStatus : "wc-" + rowStatus;
                    const normalizedFilterStatus = statusFilter.startsWith("wc-") ? statusFilter : "wc-" + statusFilter;
                    
                    if (normalizedRowStatus !== normalizedFilterStatus) {
                        shouldShow = false;
                    }
                }
                
                // Filtre par période
                if (periodFilter !== "all" && shouldShow) {
                    const startTimestamp = parseInt(row.attr("data-start-timestamp") || 0);
                    const endTimestamp = parseInt(row.attr("data-end-timestamp") || 0);
                    const today = new Date();
                    const todayTimestamp = today.getTime() / 1000;
                    
                    if (periodFilter === "proche") {
                        // Proche: dans les 20 prochains jours
                        const futureTimestamp = todayTimestamp + (20 * 86400); // 20 jours en secondes
                        shouldShow = startTimestamp >= todayTimestamp && startTimestamp <= futureTimestamp;
                    } else if (periodFilter === "semaine") {
                        // Cette semaine
                        const startOfWeek = new Date(today);
                        startOfWeek.setDate(today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1)); // Lundi
                        startOfWeek.setHours(0, 0, 0, 0);
                        
                        const endOfWeek = new Date(startOfWeek);
                        endOfWeek.setDate(startOfWeek.getDate() + 6); // Dimanche
                        endOfWeek.setHours(23, 59, 59, 999);
                        
                        shouldShow = startTimestamp >= startOfWeek.getTime() / 1000 && startTimestamp <= endOfWeek.getTime() / 1000;
                    } else if (periodFilter === "mois") {
                        // Ce mois
                        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                        const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        endOfMonth.setHours(23, 59, 59, 999);
                        
                        shouldShow = startTimestamp >= startOfMonth.getTime() / 1000 && startTimestamp <= endOfMonth.getTime() / 1000;
                    } else if (periodFilter === "mois+1") {
                        // Mois prochain
                        const startOfNextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);
                        const endOfNextMonth = new Date(today.getFullYear(), today.getMonth() + 2, 0);
                        endOfNextMonth.setHours(23, 59, 59, 999);
                        
                        shouldShow = startTimestamp >= startOfNextMonth.getTime() / 1000 && startTimestamp <= endOfNextMonth.getTime() / 1000;
                    } else if (periodFilter === "mois+2") {
                        // Mois +2
                        const startOfMonth = new Date(today.getFullYear(), today.getMonth() + 2, 1);
                        const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 3, 0);
                        endOfMonth.setHours(23, 59, 59, 999);
                        
                        shouldShow = startTimestamp >= startOfMonth.getTime() / 1000 && startTimestamp <= endOfMonth.getTime() / 1000;
                    } else if (periodFilter === "mois+3") {
                        // Mois +3
                        const startOfMonth = new Date(today.getFullYear(), today.getMonth() + 3, 1);
                        const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 4, 0);
                        endOfMonth.setHours(23, 59, 59, 999);
                        
                        shouldShow = startTimestamp >= startOfMonth.getTime() / 1000 && startTimestamp <= endOfMonth.getTime() / 1000;
                    } else if (periodFilter === "mois+6") {
                        // Mois +6
                        const startOfMonth = new Date(today.getFullYear(), today.getMonth() + 6, 1);
                        const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 7, 0);
                        endOfMonth.setHours(23, 59, 59, 999);
                        
                        shouldShow = startTimestamp >= startOfMonth.getTime() / 1000 && startTimestamp <= endOfMonth.getTime() / 1000;
                    } else if (periodFilter === "passee") {
                        // Sessions passées
                        shouldShow = endTimestamp < todayTimestamp;
                    }
                }
                
                // Filtre par référent
                if (referentFilter !== "all" && shouldShow) {
                    const rowReferent = String(row.attr("data-referent-id") || row.attr("data-referent") || "");
                    if (rowReferent !== String(referentFilter)) {
                        shouldShow = false;
                    }
                }
                
                // Filtre par type de client
                if (clientTypeFilter !== "all" && shouldShow) {
                    const clientHasCpt = row.attr("data-client-has-cpt");
                    if (clientTypeFilter === "with-cpt" && clientHasCpt !== "yes") {
                        shouldShow = false;
                    } else if (clientTypeFilter === "without-cpt" && clientHasCpt !== "no") {
                        shouldShow = false;
                    }
                }
                
                // Filtre par recherche
                if (searchQuery && shouldShow) {
                    const rowText = row.text().toLowerCase();
                    const rowId = row.attr("data-order-id");
                    const rowClient = row.attr("data-client") ? row.attr("data-client").toLowerCase() : "";
                    const rowSession = row.attr("data-session") ? row.attr("data-session").toLowerCase() : "";
                    
                    shouldShow = rowText.includes(searchQuery) || 
                                  rowId.includes(searchQuery) || 
                                  rowClient.includes(searchQuery) || 
                                  rowSession.includes(searchQuery);
                }
                
                return shouldShow;
            });
        }
        
        // Fonction pour appliquer les filtres d\'alerte
        function applyAlertFilter(rows, alertType) {
            return rows.filter(function() {
                return checkAlertCondition($(this), alertType);
            });
        }
        
        // Connecter la fonction de tri existante aux nouveaux filtres
        $(document).on("fsbdd-request-filtered-rows", function() {
            // Ne pas réappliquer les filtres, retourner simplement les lignes visibles
            var visibleRows = $("#fsbdd-orders-table tbody tr:visible");
            $(document).trigger("fsbdd-filter-changed", [visibleRows]);
        });
        
        // Vérifier si une ligne correspond à une condition dalerte
        function checkAlertCondition(row, alertType) {
            const today = new Date();
            // Mettre en millisecondes puis diviser par 1000 pour avoir des secondes
            const todayTimestamp = Math.floor(today.getTime() / 1000); 
            
            // --- ALERTE EMAGREMENTS ---
            if (alertType === "emargement_non_recu_7j") {
                 // Vérifier si end_timestamp existe et est dans le passé
                const endTimestampAttr = row.attr("data-end-timestamp");
                if (!endTimestampAttr || endTimestampAttr === "") return false;

                try {
                    const endTimestamp = parseInt(endTimestampAttr);
                    if (isNaN(endTimestamp) || endTimestamp === 0 || endTimestamp > todayTimestamp) {
                        return false;
                    }

                    // Vérifier si les émargements sont reçus ou certifiés
                    const emargements = row.attr("data-emargements");
                    if (emargements && (emargements === "3" || emargements === "4")) {
                        return false;
                    }
                    
                    // Vérifier si la date de réception des émargements existe
                    const recepmargmts = row.attr("data-recepmargmts"); // Ceci est peut-être une date?
                    const datemargmts = row.attr("data-datemargmts"); // Ou ceci?
                    // Si un des deux champs date liés aux émargements est rempli, on considère OK
                    if ((recepmargmts && recepmargmts !== "") || (datemargmts && datemargmts !== "")) {
                         return false;
                    }
                    
                    // Exclure les formations en erreur
                    if (row.attr("data-formation-erreur") === "oui") {
                        return false;
                    }
                    
                    // Exclure les statuts particuliers
                    const status = row.attr("data-status");
                    const excludedStatuses = ["wc-cancelled", "wc-failed", "wc-echecdevis"];
                    if (excludedStatuses.includes(status)) {
                        return false;
                    }
                    
                    // Calculer si 7 jours se sont écoulés depuis la fin
                    const diffDays = Math.floor((todayTimestamp - endTimestamp) / 86400);
                    return diffDays >= 7;

                } catch (e) {
                     console.error("Erreur parsing endTimestamp:", endTimestampAttr, e);
                     return false;
                }
            }
            
            // --- ALERTE FORMATION ERREUR ---
            if (alertType === "formation_erreur") {
                return row.attr("data-formation-erreur") === "oui";
            }
            
            // --- ALERTE CONVENTION SIGNEE NON RECUE ---
            if (alertType === "conv_signee_non_recue") {
                const inscriptionDate = row.attr("data-inscription-date");
                const confirmeDate = row.attr("data-confirme-date");
                // Convention envoyée (date inscription existe) ET convention non reçue (date confirmation vide)
                return (inscriptionDate && inscriptionDate !== "" && inscriptionDate !== "0") && 
                       (!confirmeDate || confirmeDate === "" || confirmeDate === "0") && 
                       // Exclure statuts finaux ou erreurs
                       row.attr("data-status") !== "wc-cancelled" && 
                       row.attr("data-status") !== "wc-failed" && 
                       row.attr("data-status") !== "wc-echecdevis";
            }
            
            // --- ALERTE CONVENTION NON ENVOYEE ---
            if (alertType === "conv_non_envoyee") {
                const inscriptionDate = row.attr("data-inscription-date");
                // Convention non envoyée (pas de date inscription)
                return (!inscriptionDate || inscriptionDate === "" || inscriptionDate === "0") && 
                       // Exclure statuts non pertinents ou erreurs
                       row.attr("data-status") !== "wc-cancelled" && 
                       row.attr("data-status") !== "wc-failed" && 
                       row.attr("data-status") !== "wc-echecdevis" && 
                       row.attr("data-status") !== "wc-gplsquote-req" && // Devis pas encore accepté
                       row.attr("data-formation-erreur") !== "oui";
            }
            
            // --- ALERTE STATUT INCHANGE ---
             if (alertType === "conv_statut_inchange") {
                const status = row.attr("data-status");
                const earlyStatuses = [
                    "wc-gplsquote-req", "wc-devisproposition", 
                    "wc-preinscription", "wc-modifpreinscript"
                ];
                // Ne concerne que les statuts précoces
                if (!earlyStatuses.includes(status)) {
                    return false;
                }
                // Exclure les erreurs ou statuts finaux
                if (row.attr("data-formation-erreur") === "oui" || status === "wc-cancelled" || status === "wc-failed" || status === "wc-echecdevis") {
                    return false;
                }
                
                const lastModTimestampAttr = row.attr("data-last-status-change");
                if (!lastModTimestampAttr || lastModTimestampAttr === "" || lastModTimestampAttr === "0") {
                    return false; // Pas de date de modification
                }
                
                try {
                    const lastModTimestamp = parseInt(lastModTimestampAttr);
                    if (isNaN(lastModTimestamp) || lastModTimestamp === 0) return false;
                    
                    const diffDays = Math.floor((todayTimestamp - lastModTimestamp) / 86400);
                    return diffDays >= 2;
                } catch (e) {
                     console.error("Erreur parsing last_status_change:", lastModTimestampAttr, e);
                     return false;
                }
            }

            // --- ALERTE FACTURE A ENVOYER ---
if (alertType === "facture_non_envoyee") {
    const factureSentDate = row.attr("data-facturesent-date");
    const emargements = row.attr("data-emargements");
    
    // Vérifications:
    // 1. Les émargements doivent être certifiés (statut 4)
    // 2. La facture ne doit pas encore être envoyée (date de facture envoyée vide)
    
    const emargementsOk = (emargements === "4");
    const factureNonEnvoyee = (!factureSentDate || factureSentDate === "" || factureSentDate === "0");
    
    return emargementsOk && factureNonEnvoyee;
}

// --- NOUVELLE ALERTE ATTESTATION FACTURE NON ENVOYEE ---
if (alertType === "attestation_facture_non_envoyee") {
    const status = row.attr("data-status");
    const dateFact = row.attr("data-datefact");
    const factureSentDate = row.attr("data-facturesent-date");
    
    // Seulement pour le statut attestation
    if (status !== "wc-attestationform") {
        return false;
    }
    
    // Facture créée mais non envoyée
    return (dateFact && dateFact !== "" && dateFact !== "0") && 
           (!factureSentDate || factureSentDate === "" || factureSentDate === "0");
}

// ALERTE FACTURE PAYÉE À VALIDER
if (alertType === "facture_payee_a_valider") {
    const totalPayments = parseFloat(row.attr("data-ttrglmts") || "0");
    const totalAmount = parseFloat(row.attr("data-montcattc") || "0");
    const status = row.attr("data-status");
    const factureSentDate = row.attr("data-facturesent-date");
    
    // Vérifier que les montants correspondent (avec une marge de 0.01) et que la facture est envoyée
    const difference = Math.abs(totalPayments - totalAmount);
    return difference <= 0.01 && totalPayments > 0 && totalAmount > 0 && 
           status !== "wc-factureok" && factureSentDate && factureSentDate !== "";
}

// ALERTE STATUT FACTUREOK INCORRECT
if (alertType === "facture_statut_incorrect") {
    const totalPayments = parseFloat(row.attr("data-ttrglmts") || "0");
    const totalAmount = parseFloat(row.attr("data-montcattc") || "0");
    const status = row.attr("data-status");
    
    // Si statut factureok mais montants ne correspondent pas
    const difference = Math.abs(totalPayments - totalAmount);
    return status === "wc-factureok" && (difference > 0.01 || totalPayments <= 0 || totalAmount <= 0);
}

// ALERTE FACTURE PAYÉE SANS CONVENTION REÇUE
if (alertType === "facture_payee_sans_convention") {
    const factureOkDate = row.attr("data-factureok-date");
    const confirmeDate = row.attr("data-confirme-date");
    
    // Facture payée mais convention non reçue
    return (factureOkDate && factureOkDate !== "" && factureOkDate !== "0") && 
           (!confirmeDate || confirmeDate === "" || confirmeDate === "0");
}
            
            // --- ALERTE FACTURE ENVOYEE NON REGLEE ---
            if (alertType === "facture_envoyee_non_reglee") {
                const factureSentDate = row.attr("data-facturesent-date");
                const factureOkDate = row.attr("data-factureok-date");
                const status = row.attr("data-status");
                
                // Exclure le statut attestation pour cette alerte
                if (status === "wc-attestationform") {
                    return false;
                }
                
                // Facture envoyée (date existe) ET non réglée (pas de date de paiement)
                return (factureSentDate && factureSentDate !== "" && factureSentDate !== "0") && 
                       (!factureOkDate || factureOkDate === "" || factureOkDate === "0");
            }

            // --- NOUVELLE ALERTE ATTESTATION FACTURE NON REGLEE ---
            if (alertType === "attestation_facture_non_reglee") {
                const status = row.attr("data-status");
                const factureSentDate = row.attr("data-facturesent-date");
                const factureOkDate = row.attr("data-factureok-date");
                
                // Seulement pour le statut attestation
                if (status !== "wc-attestationform") {
                    return false;
                }
                
                // Facture envoyée mais non réglée
                return (factureSentDate && factureSentDate !== "" && factureSentDate !== "0") && 
                       (!factureOkDate || factureOkDate === "" || factureOkDate === "0");
            }
            
            // --- ALERTE ECHEANCE DEPASSEE ---
            if (alertType === "facture_echeance_depassee") {
                const dateFinFactAttr = row.attr("data-datefinfact"); // Supposé être un timestamp
                const factureOkDate = row.attr("data-factureok-date");
                // Doit avoir une date de fin ET ne pas être payée
                if (!dateFinFactAttr || dateFinFactAttr === "" || dateFinFactAttr === "0" || (factureOkDate && factureOkDate !== "" && factureOkDate !== "0")) {
                    return false;
                }
                
                try {
                    const echeanceTimestamp = parseInt(dateFinFactAttr);
                    if (isNaN(echeanceTimestamp) || echeanceTimestamp === 0) return false;
                    // Échéance dépassée si timestamp < aujourdhui
                    return echeanceTimestamp < todayTimestamp;
                } catch (e) {
                    console.error("Erreur parsing datefinfact:", dateFinFactAttr, e);
                    return false;
                }
            }
            
            // --- ALERTE FACTURE A RELANCER (+15J) ---
            if (alertType === "facture_relance_15j") {
                const suiviFactuAttr = row.attr("data-suivifactu"); // Supposé être un timestamp de la dernière relance
                const factureOkDate = row.attr("data-factureok-date");
                 // Doit avoir une date de suivi/relance ET ne pas être payée
                if (!suiviFactuAttr || suiviFactuAttr === "" || suiviFactuAttr === "0" || (factureOkDate && factureOkDate !== "" && factureOkDate !== "0")) {
                    return false;
                }
                
                try {
                    const relanceTimestamp = parseInt(suiviFactuAttr);
                    if (isNaN(relanceTimestamp) || relanceTimestamp === 0) return false;

                    const diffDays = Math.floor((todayTimestamp - relanceTimestamp) / 86400);
                    // Relancer si 15 jours ou plus se sont écoulés depuis la dernière relance
                    return diffDays >= 15; 
                } catch (e) {
                    console.error("Erreur parsing suivifactu:", suiviFactuAttr, e);
                    return false;
                }
            }
            
            // --- ALERTES RAPPROCHEMENTS ---
            
            // Verifier dabord si la commande a un numero de convention contenant un tiret
            const hasConventionWithDash = function(row) {
                const convention = row.attr("data-convention") || "";
                return convention.includes("-");
            };
            
            // ALERTE RAPPROCHEMENT COMPLET (100%)
            if (alertType === "rappro_complet") {
                // Verifier dabord si la commande a un numero de convention avec tiret
                if (!hasConventionWithDash(row)) {
                    return false;
                }
                
                const session = row.attr("data-fsbdd-rappro-session") === "1";
                const specificites = row.attr("data-fsbdd-rappro-specificites") === "1";
                const convocations = row.attr("data-fsbdd-rappro-convocations") === "1";
                const quantites = row.attr("data-fsbdd-rappro-quantites-couts") === "1";
                const subro = row.attr("data-fsbdd-rappro-subro-reglements") === "1";
                const client = row.attr("data-fsbdd-rappro-client-bdd-web") === "1";
                
                const totalEtapes = 6;
                const etapesCompletes = [session, specificites, convocations, quantites, subro, client].filter(Boolean).length;
                
                return etapesCompletes === totalEtapes;
            }
            
            // Fonction helper pour verifier si le rapprochement a commence
            const hasRapprochementStarted = function(row) {
                const session = row.attr("data-fsbdd-rappro-session") === "1";
                const specificites = row.attr("data-fsbdd-rappro-specificites") === "1";
                const convocations = row.attr("data-fsbdd-rappro-convocations") === "1";
                const quantites = row.attr("data-fsbdd-rappro-quantites-couts") === "1";
                const subro = row.attr("data-fsbdd-rappro-subro-reglements") === "1";
                const client = row.attr("data-fsbdd-rappro-client-bdd-web") === "1";
                
                return [session, specificites, convocations, quantites, subro, client].some(Boolean);
            };
            
            // ALERTE SESSION NON VALIDEE
            if (alertType === "rappro_session_manquant") {
                // Verifier dabord si la commande a un numero de convention avec tiret
                if (!hasConventionWithDash(row)) {
                    return false;
                }
                // Verifier que session nest pas cochee
                return row.attr("data-fsbdd-rappro-session") !== "1";
            }
            
            // ALERTE SPECIFICITES NON VALIDEES
            if (alertType === "rappro_specificites_manquant") {
                // Verifier dabord si la commande a un numero de convention avec tiret
                if (!hasConventionWithDash(row)) {
                    return false;
                }
                // Verifier que specificites nest pas cochee
                return row.attr("data-fsbdd-rappro-specificites") !== "1";
            }
            
            // ALERTE CONVOCATIONS NON VALIDEES
            if (alertType === "rappro_convocations_manquant") {
                // Verifier dabord si la commande a un numero de convention avec tiret
                if (!hasConventionWithDash(row)) {
                    return false;
                }
                // Verifier que convocations nest pas cochee
                return row.attr("data-fsbdd-rappro-convocations") !== "1";
            }
            
            // ALERTE QUANTITES/COUTS NON VALIDES
            if (alertType === "rappro_quantites_manquant") {
                // Verifier dabord si la commande a un numero de convention avec tiret
                if (!hasConventionWithDash(row)) {
                    return false;
                }
                // Verifier que quantites nest pas cochee
                return row.attr("data-fsbdd-rappro-quantites-couts") !== "1";
            }
            
            // ALERTE SUBRO/REGLEMENTS NON VALIDES
            if (alertType === "rappro_subro_manquant") {
                // Verifier dabord si la commande a un numero de convention avec tiret
                if (!hasConventionWithDash(row)) {
                    return false;
                }
                // Verifier que subro nest pas cochee
                return row.attr("data-fsbdd-rappro-subro-reglements") !== "1";
            }
            
            // ALERTE CLIENT BDD/WEB NON VALIDE
            if (alertType === "rappro_client_manquant") {
                // Verifier dabord si la commande a un numero de convention avec tiret
                if (!hasConventionWithDash(row)) {
                    return false;
                }
                // Verifier que client nest pas cochee
                return row.attr("data-fsbdd-rappro-client-bdd-web") !== "1";
            }
            
            // FILTRES PAR STADE DE COMPLETION
            
            // Fonction helper pour calculer le nombre détapes complétées
            const getCompletedSteps = function(row) {
                const session = row.attr("data-fsbdd-rappro-session") === "1";
                const specificites = row.attr("data-fsbdd-rappro-specificites") === "1";
                const convocations = row.attr("data-fsbdd-rappro-convocations") === "1";
                const quantites = row.attr("data-fsbdd-rappro-quantites-couts") === "1";
                const subro = row.attr("data-fsbdd-rappro-subro-reglements") === "1";
                const client = row.attr("data-fsbdd-rappro-client-bdd-web") === "1";
                
                return [session, specificites, convocations, quantites, subro, client].filter(Boolean).length;
            };
            
            // STADE 1/6
            if (alertType === "rappro_stade_1") {
                if (!hasConventionWithDash(row)) {
                    return false;
                }
                return getCompletedSteps(row) === 1;
            }
            
            // STADE 2/6
            if (alertType === "rappro_stade_2") {
                if (!hasConventionWithDash(row)) {
                    return false;
                }
                return getCompletedSteps(row) === 2;
            }
            
            // STADE 3/6
            if (alertType === "rappro_stade_3") {
                if (!hasConventionWithDash(row)) {
                    return false;
                }
                return getCompletedSteps(row) === 3;
            }
            
            // STADE 4/6
            if (alertType === "rappro_stade_4") {
                if (!hasConventionWithDash(row)) {
                    return false;
                }
                return getCompletedSteps(row) === 4;
            }
            
            // STADE 5/6
            if (alertType === "rappro_stade_5") {
                if (!hasConventionWithDash(row)) {
                    return false;
                }
                return getCompletedSteps(row) === 5;
            }
            
            // --- AUTRES ALERTES (Suivi, Stagiaires, OPCO, Devis, Formateurs, etc.) ---
            // (Le code existant pour ces alertes reste ici)
            
            // Alerte: Formation terminée - Suivi non réalisé après 7 jours
            if (alertType === "suivi_non_realise_7j") {
                const endTimestamp = parseInt(row.attr("data-end-timestamp") || 0);
                if (!endTimestamp || endTimestamp > todayTimestamp) {
                    return false;
                }
                
                // Vérifier si le suivi a été réalisé
                if (row.attr("data-suivi-realise") === "oui" || row.attr("data-suivireal-date")) {
                    return false;
                }
                
                // Calculer si 7 jours se sont écoulés depuis la fin
                const diffDays = Math.floor((todayTimestamp - endTimestamp) / 86400);
                return diffDays >= 7 && diffDays < 15;
            }
            
            // Alerte: Formation terminée - Suivi non réalisé après 15 jours
            if (alertType === "suivi_non_realise_15j") {
                const endTimestamp = parseInt(row.attr("data-end-timestamp") || 0);
                if (!endTimestamp || endTimestamp > todayTimestamp) {
                    return false;
                }
                
                // Vérifier si le suivi a été réalisé
                if (row.attr("data-suivi-realise") === "oui" || row.attr("data-suivireal-date")) {
                    return false;
                }
                
                // Calculer si 15 jours se sont écoulés depuis la fin
                const diffDays = Math.floor((todayTimestamp - endTimestamp) / 86400);
                return diffDays >= 15 && diffDays < 30;
            }
            
            // Alerte: Formation terminée - Suivi non réalisé après 30 jours
            if (alertType === "suivi_non_realise_30j") {
                const endTimestamp = parseInt(row.attr("data-end-timestamp") || 0);
                if (!endTimestamp || endTimestamp > todayTimestamp) {
                    return false;
                }
                
                // Vérifier si le suivi a été réalisé
                if (row.attr("data-suivi-realise") === "oui" || row.attr("data-suivireal-date")) {
                    return false;
                }
                
                // Calculer si 30 jours se sont écoulés depuis la fin
                const diffDays = Math.floor((todayTimestamp - endTimestamp) / 86400);
                return diffDays >= 30;
            }
            
            // Alerte: Stagiaires non renseignés pour formation à venir (10 jours)
            if (alertType === "stagiaires_non_renseignes_10j") {
                const startTimestamp = parseInt(row.attr("data-start-timestamp") || 0);
                if (!startTimestamp || startTimestamp < todayTimestamp) {
                    return false;
                }
                
                // Vérifier si les stagiaires sont renseignés
                if (row.attr("data-stagiaires-renseignes") === "oui") {
                    return false;
                }
                
                // Calculer s\'il reste moins de 10 jours avant le début
                const diffDays = Math.floor((startTimestamp - todayTimestamp) / 86400);
                return diffDays <= 10 && diffDays > 0;
            }
            
            // Alerte: Convocations en attente
            if (alertType === "convocations_attente") {
                return row.attr("data-convocations-status") === "attente";
            }
            
            // Alerte: Formateur optionné non confirmé 15j avant
            if (alertType === "formateur_option_non_confirme_15j") {
                const startTimestamp = parseInt(row.attr("data-start-timestamp") || 0);
                if (!startTimestamp || startTimestamp < todayTimestamp) {
                    return false;
                }
                
                // Vérifier si le statut du formateur est "Option"
                if (row.attr("data-formateur-status") !== "option") {
                    return false;
                }
                
                // Calculer s\'il reste moins de 15 jours avant le début
                const diffDays = Math.floor((startTimestamp - todayTimestamp) / 86400);
                return diffDays <= 15 && diffDays > 0;
            }
            
            // Alerte: Formateur prébloqué non confirmé 15j avant
            if (alertType === "formateur_prebloque_non_confirme_15j") {
                const startTimestamp = parseInt(row.attr("data-start-timestamp") || 0);
                if (!startTimestamp || startTimestamp < todayTimestamp) {
                    return false;
                }
                
                // Vérifier si le statut du formateur est "Prébloqué FS"
                if (row.attr("data-formateur-status") !== "prebloque") {
                    return false;
                }
                
                // Calculer s\'il reste moins de 15 jours avant le début
                const diffDays = Math.floor((startTimestamp - todayTimestamp) / 86400);
                return diffDays <= 15 && diffDays > 0;
            }
            
            // Alerte: Formateur confirmé mais LM non envoyée
            if (alertType === "formateur_confirme_lm_non_envoyee") {
                // Vérifier si le formateur est confirmé mais pas la LM
                return row.attr("data-formateur-status") === "reserve" && 
                       row.attr("data-formateurs-lm-status") !== "envoyee";
            }
            
            // Alerte: Formation confirmée mais formateur absent
            if (alertType === "formation_confirmee_formateur_absent") {
                return row.attr("data-inter-status") === "confirme" && 
                      (!row.attr("data-formateur-status") || row.attr("data-formateur-status") === "");
            }
            
            // Alerte: Formation inter confirmée mais élément manquant
            if (alertType === "inter_confirme_manque_element") {
                return row.attr("data-inter-status") === "confirme" && 
                       row.attr("data-inter-elements-status") === "manque";
            }
            
            // Alerte: Formation inter non confirmée à 15j de début
            if (alertType === "inter_non_confirme_15j") {
                const startTimestamp = parseInt(row.attr("data-start-timestamp") || 0);
                if (!startTimestamp || startTimestamp < todayTimestamp) {
                    return false;
                }
                
                // Vérifier si le statut inter n\'est pas "confirme"
                if (row.attr("data-inter-status") === "confirme") {
                    return false;
                }
                
                // Calculer s\'il reste moins de 15 jours avant le début
                const diffDays = Math.floor((startTimestamp - todayTimestamp) / 86400);
                return diffDays <= 15 && diffDays > 0;
            }
            
            // Alerte: Option posée non confirmée après 7 jours
            if (alertType === "option_non_confirmee_7j") {
                if (!row.attr("data-option-date") || row.attr("data-option-status") !== "posee") {
                    return false;
                }
                
                const optionDate = new Date(row.attr("data-option-date"));
                const optionTimestamp = optionDate.getTime() / 1000;
                
                // Calculer si 7 jours se sont écoulés depuis l\'option
                const diffDays = Math.floor((todayTimestamp - optionTimestamp) / 86400);
                return diffDays >= 7;
            }
            
            // Alerte: Numéro de dossier OPCO manquant
            if (alertType === "opco_num_dossier_manquant") {
                return row.attr("data-opco") === "oui" && 
                      (!row.attr("data-opco-dossier-number") || row.attr("data-opco-dossier-number") === "");
            }
            
            // Alerte: Dossier OPCO non reçu 10j après session
            if (alertType === "opco_dossier_non_recu_passe_10j") {
                const endTimestamp = parseInt(row.attr("data-end-timestamp") || 0);
                if (!endTimestamp || endTimestamp > todayTimestamp) {
                    return false;
                }
                
                // Vérifier si l\'OPCO est bien indiqué
                if (row.attr("data-opco") !== "oui") {
                    return false;
                }
                
                // Vérifier si le dossier a été reçu
                if (row.attr("data-opco-dossier-recu-date")) {
                    return false;
                }
                
                // Calculer si 10 jours se sont écoulés depuis la fin
                const diffDays = Math.floor((todayTimestamp - endTimestamp) / 86400);
                return diffDays >= 10;
            }
            
            // Alerte: Devis à relancer après 15 jours
            if (alertType === "devis_relance_15j") {
                if (!row.attr("data-devis-last-relance-date")) {
                    return false;
                }
                
                const relanceDate = new Date(row.attr("data-devis-last-relance-date"));
                const relanceTimestamp = relanceDate.getTime() / 1000;
                
                // Calculer si 15 jours se sont écoulés depuis la dernière relance
                const diffDays = Math.floor((todayTimestamp - relanceTimestamp) / 86400);
                return diffDays >= 15;
            }
            
            return false;
        }
        
        // Mise à jour des compteurs dalertes
        function updateAlertCounters() {
            // Obtenir toutes les lignes du tableau
            const rows = $("#fsbdd-orders-table tbody tr");
            
            // Réinitialiser les compteurs
			let facturePayeeValiderCount = 0;
let factureStatutIncorrectCount = 0;
let factureSansConventionCount = 0;
            let emargementCount = 0;
            let formationErreurCount = 0;
            let convNonRecueCount = 0;
            let convNonEnvoyeeCount = 0;
            let convStatutInchangeCount = 0;
            let formateurOptionCount = 0;
            let formateurPrebloqueteCount = 0;
            let formateurLmCount = 0;
            let formateurAbsentCount = 0;
            let elementManquantCount = 0;
            let nonConfirmeeCount = 0;
            let optionCount = 0;
            let suivi7jCount = 0;
            let suivi15jCount = 0;
            let suivi30jCount = 0;
            let stagiairesCount = 0;
            let convocationsCount = 0;
            let opcoNumCount = 0;
            let opcoDossierCount = 0;
            let devisCount = 0;
            let factureNonEnvoyeeCount = 0;
            let attestationFactureNonEnvoyeeCount = 0;
            let factureNonRegleeCount = 0;
            let attestationFactureNonRegleeCount = 0;
            let factureEcheanceCount = 0;
            let factureRelanceCount = 0;
            let rapproCompletCount = 0;
            let rapproSessionCount = 0;
            let rapproSpecificitesCount = 0;
            let rapproConvocationsCount = 0;
            let rapproQuantitesCount = 0;
            let rapproSubroCount = 0;
            let rapproClientCount = 0;
            let rapproStade1Count = 0;
            let rapproStade2Count = 0;
            let rapproStade3Count = 0;
            let rapproStade4Count = 0;
            let rapproStade5Count = 0;
            
            // Sets pour compter les commandes uniques par section
            const documentsOrderIds = new Set();
            const conventionsOrderIds = new Set();
            const formateursOrderIds = new Set();
            const sessionsOrderIds = new Set();
            const stagiairesOrderIds = new Set();
            const facturationOrderIds = new Set();
            const rapprochmentsOrderIds = new Set();
            
            // Parcourir toutes les lignes pour verifier les conditions dalerte
            rows.each(function() {
                // Obtenir la ligne courante
                const row = $(this);
                const orderId = row.attr("data-order-id");
                
                // Verifier chaque type dalerte
                if (checkAlertCondition(row, "facture_payee_a_valider")) facturePayeeValiderCount++;
                if (checkAlertCondition(row, "facture_statut_incorrect")) factureStatutIncorrectCount++;
                if (checkAlertCondition(row, "facture_payee_sans_convention")) factureSansConventionCount++;
                if (checkAlertCondition(row, "emargement_non_recu_7j")) emargementCount++;
                if (checkAlertCondition(row, "formation_erreur")) formationErreurCount++;
                if (checkAlertCondition(row, "conv_signee_non_recue")) convNonRecueCount++;
                if (checkAlertCondition(row, "conv_non_envoyee")) convNonEnvoyeeCount++;
                if (checkAlertCondition(row, "conv_statut_inchange")) convStatutInchangeCount++;
                if (checkAlertCondition(row, "formateur_option_non_confirme_15j")) formateurOptionCount++;
                if (checkAlertCondition(row, "formateur_prebloque_non_confirme_15j")) formateurPrebloqueteCount++;
                if (checkAlertCondition(row, "formateur_confirme_lm_non_envoyee")) formateurLmCount++;
                if (checkAlertCondition(row, "formation_confirmee_formateur_absent")) formateurAbsentCount++;
                if (checkAlertCondition(row, "inter_confirme_manque_element")) elementManquantCount++;
                if (checkAlertCondition(row, "inter_non_confirme_15j")) nonConfirmeeCount++;
                if (checkAlertCondition(row, "option_non_confirmee_7j")) optionCount++;
                if (checkAlertCondition(row, "suivi_non_realise_7j")) suivi7jCount++;
                if (checkAlertCondition(row, "suivi_non_realise_15j")) suivi15jCount++;
                if (checkAlertCondition(row, "suivi_non_realise_30j")) suivi30jCount++;
                if (checkAlertCondition(row, "stagiaires_non_renseignes_10j")) stagiairesCount++;
                if (checkAlertCondition(row, "convocations_attente")) convocationsCount++;
                if (checkAlertCondition(row, "opco_num_dossier_manquant")) opcoNumCount++;
                if (checkAlertCondition(row, "opco_dossier_non_recu_passe_10j")) opcoDossierCount++;
                if (checkAlertCondition(row, "devis_relance_15j")) devisCount++;
                if (checkAlertCondition(row, "facture_non_envoyee")) factureNonEnvoyeeCount++;
                if (checkAlertCondition(row, "attestation_facture_non_envoyee")) attestationFactureNonEnvoyeeCount++;
                if (checkAlertCondition(row, "facture_envoyee_non_reglee")) factureNonRegleeCount++;
                if (checkAlertCondition(row, "attestation_facture_non_reglee")) attestationFactureNonRegleeCount++;
                if (checkAlertCondition(row, "facture_echeance_depassee")) factureEcheanceCount++;
                if (checkAlertCondition(row, "facture_relance_15j")) factureRelanceCount++;
                if (checkAlertCondition(row, "rappro_complet")) rapproCompletCount++;
                if (checkAlertCondition(row, "rappro_session_manquant")) rapproSessionCount++;
                if (checkAlertCondition(row, "rappro_specificites_manquant")) rapproSpecificitesCount++;
                if (checkAlertCondition(row, "rappro_convocations_manquant")) rapproConvocationsCount++;
                if (checkAlertCondition(row, "rappro_quantites_manquant")) rapproQuantitesCount++;
                if (checkAlertCondition(row, "rappro_subro_manquant")) rapproSubroCount++;
                if (checkAlertCondition(row, "rappro_client_manquant")) rapproClientCount++;
                if (checkAlertCondition(row, "rappro_stade_1")) rapproStade1Count++;
                if (checkAlertCondition(row, "rappro_stade_2")) rapproStade2Count++;
                if (checkAlertCondition(row, "rappro_stade_3")) rapproStade3Count++;
                if (checkAlertCondition(row, "rappro_stade_4")) rapproStade4Count++;
                if (checkAlertCondition(row, "rappro_stade_5")) rapproStade5Count++;
                
                // Ajouter aux sets de commandes uniques par section
                if (checkAlertCondition(row, "emargement_non_recu_7j") || checkAlertCondition(row, "formation_erreur")) {
                    documentsOrderIds.add(orderId);
                }
                if (checkAlertCondition(row, "conv_signee_non_recue") || checkAlertCondition(row, "conv_non_envoyee") || checkAlertCondition(row, "conv_statut_inchange")) {
                    conventionsOrderIds.add(orderId);
                }
                if (checkAlertCondition(row, "formateur_option_non_confirme_15j") || checkAlertCondition(row, "formateur_prebloque_non_confirme_15j") || checkAlertCondition(row, "formateur_confirme_lm_non_envoyee") || checkAlertCondition(row, "formation_confirmee_formateur_absent")) {
                    formateursOrderIds.add(orderId);
                }
                if (checkAlertCondition(row, "inter_confirme_manque_element") || checkAlertCondition(row, "inter_non_confirme_15j") || checkAlertCondition(row, "option_non_confirmee_7j") || checkAlertCondition(row, "suivi_non_realise_7j") || checkAlertCondition(row, "suivi_non_realise_15j") || checkAlertCondition(row, "suivi_non_realise_30j")) {
                    sessionsOrderIds.add(orderId);
                }
                if (checkAlertCondition(row, "stagiaires_non_renseignes_10j") || checkAlertCondition(row, "convocations_attente") || checkAlertCondition(row, "opco_num_dossier_manquant") || checkAlertCondition(row, "opco_dossier_non_recu_passe_10j") || checkAlertCondition(row, "devis_relance_15j")) {
                    stagiairesOrderIds.add(orderId);
                }
                if (checkAlertCondition(row, "facture_non_envoyee") || checkAlertCondition(row, "attestation_facture_non_envoyee") || checkAlertCondition(row, "facture_envoyee_non_reglee") || checkAlertCondition(row, "attestation_facture_non_reglee") || checkAlertCondition(row, "facture_echeance_depassee") || checkAlertCondition(row, "facture_relance_15j") || checkAlertCondition(row, "facture_payee_a_valider") || checkAlertCondition(row, "facture_statut_incorrect") || checkAlertCondition(row, "facture_payee_sans_convention")) {
                    facturationOrderIds.add(orderId);
                }
                if (checkAlertCondition(row, "rappro_complet") || checkAlertCondition(row, "rappro_session_manquant") || checkAlertCondition(row, "rappro_specificites_manquant") || checkAlertCondition(row, "rappro_convocations_manquant") || checkAlertCondition(row, "rappro_quantites_manquant") || checkAlertCondition(row, "rappro_subro_manquant") || checkAlertCondition(row, "rappro_client_manquant") || checkAlertCondition(row, "rappro_stade_1") || checkAlertCondition(row, "rappro_stade_2") || checkAlertCondition(row, "rappro_stade_3") || checkAlertCondition(row, "rappro_stade_4") || checkAlertCondition(row, "rappro_stade_5")) {
                    rapprochmentsOrderIds.add(orderId);
                }
            });
            
            // Mettre à jour les compteurs affichés
			$(".facture-payee-valider-count").text(facturePayeeValiderCount);
$(".facture-statut-incorrect-count").text(factureStatutIncorrectCount);
$(".facture-sans-convention-count").text(factureSansConventionCount);
            $(".emargement-count").text(emargementCount);
            $(".formation-erreur-count").text(formationErreurCount);
            $(".conv-non-recue-count").text(convNonRecueCount);
            $(".conv-non-envoyee-count").text(convNonEnvoyeeCount);
            $(".conv-statut-count").text(convStatutInchangeCount);
            $(".formateur-option-count").text(formateurOptionCount);
            $(".formateur-prebloque-count").text(formateurPrebloqueteCount);
            $(".formateur-lm-count").text(formateurLmCount);
            $(".formateur-absent-count").text(formateurAbsentCount);
            $(".element-manquant-count").text(elementManquantCount);
            $(".non-confirmee-count").text(nonConfirmeeCount);
            $(".option-count").text(optionCount);
            $(".suivi-7j-count").text(suivi7jCount);
            $(".suivi-15j-count").text(suivi15jCount);
            $(".suivi-30j-count").text(suivi30jCount);
            $(".stagiaires-count").eq(1).text(stagiairesCount);
            $(".convocations-count").text(convocationsCount);
            $(".opco-num-count").text(opcoNumCount);
            $(".opco-dossier-count").text(opcoDossierCount);
            $(".devis-count").text(devisCount);
            $(".facture-non-envoyee-count").text(factureNonEnvoyeeCount);
            $(".attestation-facture-non-envoyee-count").text(attestationFactureNonEnvoyeeCount);
            $(".facture-non-reglee-count").text(factureNonRegleeCount);
            $(".attestation-facture-non-reglee-count").text(attestationFactureNonRegleeCount);
            $(".facture-echeance-count").text(factureEcheanceCount);
            $(".facture-relance-count").text(factureRelanceCount);
            $(".rappro-complet-count").text(rapproCompletCount);
            $(".rappro-session-count").text(rapproSessionCount);
            $(".rappro-specificites-count").text(rapproSpecificitesCount);
            $(".rappro-convocations-count").text(rapproConvocationsCount);
            $(".rappro-quantites-count").text(rapproQuantitesCount);
            $(".rappro-subro-count").text(rapproSubroCount);
            $(".rappro-client-count").text(rapproClientCount);
            $(".rappro-stade-1-count").text(rapproStade1Count);
            $(".rappro-stade-2-count").text(rapproStade2Count);
            $(".rappro-stade-3-count").text(rapproStade3Count);
            $(".rappro-stade-4-count").text(rapproStade4Count);
            $(".rappro-stade-5-count").text(rapproStade5Count);
            
            // Mettre a jour les totaux par section avec les commandes uniques
            $(".documents-count").text(documentsOrderIds.size);
            $(".conventions-count").text(conventionsOrderIds.size);
            $(".formateurs-count").text(formateursOrderIds.size);
            $(".sessions-count").text(sessionsOrderIds.size);
            $(".stagiaires-count").eq(0).text(stagiairesOrderIds.size);
            $(".facturation-count").text(facturationOrderIds.size);
            $(".rapprochements-count").text(rapprochmentsOrderIds.size);
        }
        
        // Fonction pour afficher/masquer les alertes à zéro
        function toggleZeroAlerts() {
            const showZeros = jQuery("#fsbdd-show-zeros").is(":checked");
            // Gérer les alertes normales
            jQuery(".fsbdd-alert-item").each(function() {
                const count = parseInt(jQuery(this).find(".fsbdd-alert-counter").text()) || 0;
                if (count === 0) {
                    jQuery(this).toggle(showZeros);
                }
            });
            // Gérer les alertes de rapprochements horizontales
            jQuery(".fsbdd-rappro-alert-item").each(function() {
                const count = parseInt(jQuery(this).find(".fsbdd-alert-counter").text()) || 0;
                if (count === 0) {
                    jQuery(this).toggle(showZeros);
                }
            });
            // Ajuster la hauteur des sections
            jQuery(".fsbdd-alerts-section").each(function() {
                const visibleItems = jQuery(this).find(".fsbdd-alert-item:visible").length;
                if (visibleItems === 0) {
                    jQuery(this).addClass("fsbdd-empty-section");
                } else {
                    jQuery(this).removeClass("fsbdd-empty-section");
                }
            });
            // Ajuster la visibilité de la section rapprochements
            const visibleRappro = jQuery(".fsbdd-rappro-alert-item:visible").length;
            if (visibleRappro === 0) {
                jQuery(".fsbdd-rappro-alerts-wrapper").hide();
            } else {
                jQuery(".fsbdd-rappro-alerts-wrapper").show();
            }
        }
        
        // Initialiser la fonction au chargement
        toggleZeroAlerts();
        
        // Associer lévénement à la checkbox directement
        jQuery("#fsbdd-show-zeros").on("change", toggleZeroAlerts);
        
        // Ajouter la fonctionnalité au bouton Actualiser
        jQuery("#fsbdd-refresh-alerts").on("click", function(e) {
            e.preventDefault();
            // Réinitialiser et recalculer les alertes
            updateAlertCounters();
            // Réappliquer le filtre des alertes à zéro
            toggleZeroAlerts();
            // Animation de feedback visuel
            const $btn = jQuery(this);
            $btn.prop("disabled", true).text("Actualisation...");
            setTimeout(function() {
                $btn.prop("disabled", false).text("Actualiser");
            }, 500);
        });
        
        // Appeler la fonction au chargement et après filtrage
        updateAlertCounters();
        
        // Après chaque filtrage
        jQuery(document).on("fsbdd-filter-changed", function() {
            setTimeout(updateAlertCounters, 100);
        });
    });
    </script>';
}