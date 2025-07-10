<?php
/**
 * Snippet ID: 21
 * Name: FORMULAIRE ENVOI DOCUMENTS FORMATEURS select produits formateur sessions
 * Description: 
 * @active true
 */

// Fonction pour générer le CSS du formulaire - Style simplifié
function fsbdd_get_form_css() {
    return '<style>
        /* Variables CSS pour la personnalisation des couleurs */
        :root {
            --fsbdd-primary-color: #4a6fdc;
            --fsbdd-primary-hover: #3a5ecc;
            --fsbdd-success-color: #28a745;
            --fsbdd-warning-color: #ffc107;
            --fsbdd-danger-color: #dc3545;
            --fsbdd-info-color: #17a2b8;
            --fsbdd-light-color: #f8f9fa;
            --fsbdd-dark-color: #343a40;
            --fsbdd-border-color: #dee2e6;
            --fsbdd-text-color: #333;
        }
        
        /* Réinitialisation et styles de base - Plus compact */
        .fsbdd-form-group, .fsbdd-upload-container, .fsbdd-document-section {
            font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--fsbdd-text-color);
            margin-bottom: 0.8rem;
            line-height: 1.4;
        }
        
        /* Titres */
        .fsbdd-section-title {
            font-size: 1.2rem;
            margin-top: 0.4rem;
            margin-bottom: 0.6rem;
            color: var(--fsbdd-dark-color);
            border-bottom: 2px solid var(--fsbdd-primary-color);
            padding-bottom: 0.3rem;
        }
        
        /* Labels */
        .fsbdd-label {
            display: block;
            margin-bottom: 0.3rem;
            font-weight: 500;
            font-size: 1.1rem; /* Texte grossi pour "Action de formation" */
        }
        
        /* Inputs et selects */
        .fsbdd-input, .fsbdd-select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--fsbdd-border-color);
            border-radius: 4px;
            font-size: 0.9rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .fsbdd-input:focus, .fsbdd-select:focus {
            border-color: var(--fsbdd-primary-color);
            box-shadow: 0 0 0 0.15rem rgba(74, 111, 220, 0.25);
            outline: none;
        }
        
        /* Boutons */
        .fsbdd-submit-btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            line-height: 1.4;
            border-radius: 4px;
            color: white;
            background-color: var(--fsbdd-primary-color);
            cursor: pointer;
            transition: all 0.15s ease-in-out;
        }
        
        .fsbdd-submit-btn:hover {
            background-color: var(--fsbdd-primary-hover);
        }
        
        /* Zone de dépôt de fichier - Plus compacte */
        .fsbdd-file-upload-wrapper {
            position: relative;
            margin-bottom: 0.6rem;
        }
        
        .fsbdd-dropzone {
            border: 2px dashed var(--fsbdd-border-color);
            border-radius: 6px;
            padding: 1.2rem;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.15s ease-in-out;
        }
        
        .fsbdd-dropzone-highlight {
            border-color: var(--fsbdd-primary-color);
            background-color: rgba(74, 111, 220, 0.05);
        }
        
        .fsbdd-dropzone-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .fsbdd-dropzone-icon {
            width: 40px;
            height: 40px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%234a6fdc\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12\'/%3E%3C/svg%3E");
            background-size: contain;
            margin-bottom: 0.5rem;
        }
        
        .fsbdd-dropzone p {
            margin: 0.3rem 0;
            font-size: 0.9rem;
        }
        
        .fsbdd-dropzone-formats {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .fsbdd-dropzone-max-size {
            font-size: 0.8rem;
            color: #dc3545;
            font-weight: bold;
        }
        
        .fsbdd-file-input {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }
        
        /* Affichage du fichier sélectionné */
        .fsbdd-selected-file {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            background-color: #f8f9fa;
            border: 1px solid var(--fsbdd-border-color);
            border-radius: 4px;
        }
        
        .fsbdd-remove-file {
            background: none;
            border: none;
            color: var(--fsbdd-danger-color);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }
        
        /* Barre de progression */
        .fsbdd-progress-bar {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            margin-top: 0.8rem;
            overflow: hidden;
        }
        
        .fsbdd-progress-value {
            height: 100%;
            width: 0;
            background-color: var(--fsbdd-primary-color);
            transition: width 0.3s ease;
        }
        
        /* Alertes - Plus compactes */
        .fsbdd-alert {
            position: relative;
            padding: 0.5rem 0.8rem;
            margin-bottom: 0.6rem;
            border: 1px solid transparent;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .fsbdd-alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .fsbdd-alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .fsbdd-alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeeba;
        }
        
        .fsbdd-alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
        
        /* Animation de chargement */
        .fsbdd-loading {
            position: relative;
            min-height: 60px;
        }
        
        .fsbdd-loading::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 30px;
            height: 30px;
            margin: -15px 0 0 -15px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--fsbdd-primary-color);
            border-radius: 50%;
            animation: fsbdd-spin 1s linear infinite;
        }
        
        @keyframes fsbdd-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Loader */
        .fsbdd-loader {
            display: inline-block;
            width: 30px;
            height: 30px;
            margin: 0.8rem auto;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--fsbdd-primary-color);
            border-radius: 50%;
            animation: fsbdd-spin 1s linear infinite;
        }
        
        /* Liste de documents - Plus compacte */
        .fsbdd-document-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .fsbdd-document-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.4rem;
            padding: 0.5rem;
            border: 1px solid var(--fsbdd-border-color);
            border-radius: 4px;
            transition: transform 0.1s ease-in-out;
            font-size: 0.9rem;
        }
        
        .fsbdd-document-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .fsbdd-document-icon {
            width: 24px;
            height: 24px;
            margin-right: 0.6rem;
            background-size: contain;
            background-repeat: no-repeat;
        }
        
        .fsbdd-icon-pdf {
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%23dc3545\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z\'/%3E%3C/svg%3E");
        }
        
        .fsbdd-icon-word {
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%230d6efd\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z\'/%3E%3C/svg%3E");
        }
        
        .fsbdd-icon-excel {
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%23198754\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z\'/%3E%3C/svg%3E");
        }
        
        .fsbdd-icon-image {
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%23fd7e14\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'/%3E%3C/svg%3E");
        }
        
        .fsbdd-icon-file {
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%236c757d\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z\'/%3E%3C/svg%3E");
        }
        
        .fsbdd-document-info {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .fsbdd-document-link {
            color: var(--fsbdd-primary-color);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 0.1rem;
        }
        
        .fsbdd-document-link:hover {
            text-decoration: underline;
        }
        
        .fsbdd-validation-date, .fsbdd-sent-date {
            font-size: 0.8rem;
            display: inline-block;
            margin-right: 1rem;
        }
        
        .fsbdd-validation-date {
            color: var(--fsbdd-success-color);
        }
        
        .fsbdd-sent-date {
            color: var(--fsbdd-info-color);
        }
        
        .fsbdd-validation-pending {
            color: var(--fsbdd-warning-color);
            font-size: 0.8rem;
        }
        
        .fsbdd-status-validated {
            border-left: 3px solid var(--fsbdd-success-color);
        }
        
        .fsbdd-status-pending {
            border-left: 3px solid var(--fsbdd-warning-color);
        }
        
        /* Responsive pour petit écran */
        @media screen and (max-width: 576px) {
            .fsbdd-document-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .fsbdd-document-icon {
                margin-bottom: 0.3rem;
            }
        }

        /* Styles pour la section lettre de mission */
        .fsbdd-lettre-mission-container {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }

        .fsbdd-lettre-mission-content {
            background: white;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .fsbdd-lettre-info {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .fsbdd-lettre-info h4 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 1.2em;
        }

        .fsbdd-lettre-info p {
            margin: 8px 0;
            color: #6c757d;
        }

        .fsbdd-lettre-actions {
            margin: 20px 0;
            text-align: center;
        }

        .fsbdd-validation-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }

        .fsbdd-validate-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            margin: 15px auto 0;
        }

        .fsbdd-validate-btn:hover {
            background: #218838;
            transform: translateY(-1px);
        }

        .fsbdd-validate-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        @media screen and (max-width: 576px) {
            .fsbdd-lettre-mission-container {
                padding: 15px;
            }

            .fsbdd-lettre-mission-content {
                padding: 15px;
            }

            .fsbdd-validate-btn {
                width: 100%;
                padding: 15px;
            }
        }
    </style>';
}

// LIMITER LISTE ACTIONS DE FORMATION OU FORMATEUR RELATIONNEL EST INSCRIT
function display_fsbdd_selsessionformatr() {
    global $wpdb;

    // Récupérer l'ID de l'utilisateur connecté
    $current_user_id = get_current_user_id();

    if (!$current_user_id) {
        return '<div class="fsbdd-alert fsbdd-alert-warning">Veuillez vous connecter pour voir les formations disponibles.</div>';
    }

    // Récupérer l'ID du CPT 'formateur' lié à l'utilisateur connecté via la table mb_relationships
    $linked_formateur_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT `from` FROM {$wpdb->prefix}mb_relationships WHERE `to` = %d AND `type` = 'formateur-bdd-formateur-wp'",
            $current_user_id
        )
    );

    // Vérifier si un CPT 'formateur' est lié
    if (empty($linked_formateur_id)) {
        return '<div class="fsbdd-alert fsbdd-alert-warning">Aucun formateur lié trouvé pour cet utilisateur.</div>';
    }

    // Rechercher les actions de formation où le formateur est associé
    $actions_de_formation = get_posts([
        'post_type'   => 'action-de-formation',
        'posts_per_page' => -1,
        'fields'      => 'ids', // Récupérer uniquement les IDs
    ]);

    $linked_actions = [];

    foreach ($actions_de_formation as $action_id) {
        // Exclure l'action de formation avec l'ID 268081
        if ((int)$action_id === 268081) {
            continue;
        }

        // Récupérer le planning pour chaque action de formation
        $planning = get_post_meta($action_id, 'fsbdd_planning', true);

        if ($planning && is_array($planning)) {
            foreach ($planning as $day) {
                if (!empty($day['fsbdd_gpformatr']) && is_array($day['fsbdd_gpformatr'])) {
                    foreach ($day['fsbdd_gpformatr'] as $formateur) {
                        if (!empty($formateur['fsbdd_user_formateurrel']) && (int)$formateur['fsbdd_user_formateurrel'] === (int)$linked_formateur_id) {
                            $linked_actions[] = $action_id; // Ajouter l'ID de l'action de formation à la liste
                            break 2; // Sortir des boucles imbriquées
                        }
                    }
                }
            }
        }
    }

    if (!empty($linked_actions)) {
        // Construire le HTML pour le select
        $output = '<div class="fsbdd-form-group">';
        $output .= '<label for="fsbdd_selsessionformatr" class="fsbdd-label">Action de formation</label>';
        $output .= '<select id="fsbdd_selsessionformatr" name="fsbdd_selsessionformatr" class="fsbdd-select">';
        $output .= '<option value="">-- Sélectionnez une option --</option>'; // Option par défaut
        
        foreach ($linked_actions as $action_id) {
            // Récupérer les mêmes informations que dans le 2e snippet
            $lieu = get_post_meta($action_id, 'fsbdd_select_lieusession', true);
            $startdate = get_post_meta($action_id, 'we_startdate', true);
            $enddate = get_post_meta($action_id, 'we_enddate', true);
            $numero = get_the_title($action_id);

            $lieu_complet = $lieu ? trim($lieu) : 'Adresse inconnue';
            $lieu_resume = $lieu ? explode(',', $lieu)[0] : 'Lieu inconnu'; 
            $lieu_resume = ucfirst(strtolower(trim($lieu_resume))); // Nettoyer et formater
            $startdate_formatted = $startdate ? date_i18n('j F Y', $startdate) : 'Date non définie';
            $enddate_formatted = $enddate ? date_i18n('j F Y', $enddate) : 'Date non définie';

            // Affichage similaire au second snippet
            $option_text = "{$lieu_resume}, {$startdate_formatted}, N°{$numero}";

            $output .= "<option value='" . esc_attr($action_id) . "'>" . esc_html($option_text) . "</option>";
        }

        $output .= '</select>';
        $output .= '<input type="hidden" id="idcptformateur" name="idcptformateur" value="' . esc_attr($linked_formateur_id) . '">';
        $output .= '</div>';
    } else {
        $output = '<div class="fsbdd-alert fsbdd-alert-info">Aucune action de formation associée trouvée.</div>';
    }

    // Ajouter un script JS pour gérer les changements - Version simplifiée
    $output .= "
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var select = document.getElementById('fsbdd_selsessionformatr');
                var idField = document.getElementById('idcommande');
                
                if (select && idField) {
                    // Fonction pour charger les documents
                    function loadDocuments(actionId) {
                        if (!actionId) return;
                        
                        // Mettre à jour le champ idcommande
                        idField.value = actionId;
                        
                        // Sauvegarder la sélection pour la prochaine visite
                        localStorage.setItem('fsbdd_selected_action', actionId);
                        
                        // Récupérer l'ID du formateur
                        var formateur_id = document.getElementById('idcptformateur') 
                            ? document.getElementById('idcptformateur').value 
                            : '';
                        
                        if (!formateur_id) return;
                        
                        // Récupérer l'élément où afficher les documents
                        var documentsList = document.getElementById('fsbdd_documents_list');
                        if (!documentsList) return;
                        
                        // Afficher le chargement
                        documentsList.innerHTML = '<div class=\"fsbdd-loader\"></div>';
                        
                        // Effectuer la requête AJAX
                        var xhr = new XMLHttpRequest();
                        xhr.open('GET', '" . admin_url('admin-ajax.php') . "?action=get_uploaded_documents&cpt_id=' + actionId + '&formateur_id=' + formateur_id, true);
                        
                        xhr.onload = function() {
                            if (xhr.status >= 200 && xhr.status < 400) {
                                documentsList.innerHTML = xhr.responseText;
                            } else {
                                documentsList.innerHTML = '<div class=\"fsbdd-alert fsbdd-alert-danger\">Une erreur est survenue.</div>';
                            }
                        };
                        
                        xhr.onerror = function() {
                            documentsList.innerHTML = '<div class=\"fsbdd-alert fsbdd-alert-danger\">Une erreur de connexion est survenue.</div>';
                        };
                        
                        xhr.send();
                        
                        // Charger les informations de la lettre de mission
                        loadLettresMission(actionId, formateur_id);
                    }
                    


                    
                    // Écouter les changements sur le select
                    select.addEventListener('change', function() {
                        loadDocuments(this.value);
                    });
                    
                    // Restaurer la sélection précédente
                    if (localStorage.getItem('fsbdd_selected_action')) {
                        var savedValue = localStorage.getItem('fsbdd_selected_action');
                        select.value = savedValue;
                        
                        // Charger les documents immédiatement après avoir défini la valeur
                        if (select.value) {
                            setTimeout(function() {
                                loadDocuments(select.value);
                            }, 100);
                        }
                    }
                }
            });
        </script>
    ";

    // Ajouter CSS pour le nouveau design
    $output .= fsbdd_get_form_css();

    return $output;
}

// Créer un shortcode pour afficher le select sur une page
add_shortcode('fsbdd_selsessionformatr', 'display_fsbdd_selsessionformatr');

// AFFICHER DOCUMENTS DEJA UPLOADÉS PAR L'UTILISATEUR SOUS LE SELECT
function display_user_uploaded_documents() {
    global $wpdb;

    // Récupérer l'ID de l'utilisateur connecté
    $current_user_id = get_current_user_id();

    if (!$current_user_id) {
        return '<div class="fsbdd-alert fsbdd-alert-warning">Veuillez vous connecter pour voir vos documents téléversés.</div>';
    }

    // Récupérer l'ID du CPT formateur lié à l'utilisateur connecté
    $linked_formateur_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT `from` FROM {$wpdb->prefix}mb_relationships WHERE `to` = %d AND `type` = 'formateur-bdd-formateur-wp'",
            $current_user_id
        )
    );

    if (!$linked_formateur_id) {
        return '<div class="fsbdd-alert fsbdd-alert-warning">Aucun formateur associé trouvé pour cet utilisateur.</div>';
    }

    // Simplifier la structure HTML - plus de colonnes
    $output = '
        <div id="uploaded_documents_section" class="fsbdd-document-section">
            <h3 class="fsbdd-section-title">Documents Téléversés</h3>
            <div id="fsbdd_documents_list" class="fsbdd-document-list-container">
                <p class="fsbdd-instructions">Sélectionnez une action de formation pour voir vos documents téléversés.</p>
            </div>
        </div>
    ';

    return $output;
}

// Ajouter le shortcode
add_shortcode('fsbdd_uploaded_documents', 'display_user_uploaded_documents');

// AFFICHER LA SECTION LETTRE DE MISSION - VERSION CORRIGÉE
function display_lettre_mission_section() {
    global $wpdb;

    // Récupérer l'ID de l'utilisateur connecté
    $current_user_id = get_current_user_id();

    if (!$current_user_id) {
        return '<div class="fsbdd-alert fsbdd-alert-warning">Veuillez vous connecter pour voir la lettre de mission.</div>';
    }

    // Récupérer l'ID du CPT formateur lié à l'utilisateur connecté
    $linked_formateur_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT `from` FROM {$wpdb->prefix}mb_relationships WHERE `to` = %d AND `type` = 'formateur-bdd-formateur-wp'",
            $current_user_id
        )
    );

    if (!$linked_formateur_id) {
        return '<div class="fsbdd-alert fsbdd-alert-warning">Aucun formateur associé trouvé pour cet utilisateur.</div>';
    }

    $output = '
        <div id="lettre_mission_section" class="fsbdd-document-section">
            <h3 class="fsbdd-section-title">📋 Lettre de Mission</h3>
            <div id="fsbdd_lettre_mission_section" class="fsbdd-lettre-mission-container">
                <p class="fsbdd-instructions">Sélectionnez une action de formation pour voir votre lettre de mission.</p>
            </div>
        </div>
    ';

    // Ajouter le script JavaScript pour définir ajaxUrl
    $output .= '
    <script>
        // Définir ajaxUrl pour la fonction loadLettresMission
        var ajaxUrl = "' . admin_url('admin-ajax.php') . '";
        
        // Fonction pour charger les informations de lettre de mission
        function loadLettresMission(actionId, formateurId) {
            if (!actionId || !formateurId) return;
            
            var lettreSection = document.getElementById("fsbdd_lettre_mission_section");
            if (!lettreSection) return;
            
            // Afficher le chargement
            lettreSection.innerHTML = \'<div class="fsbdd-loader"></div>\';
            
            // Effectuer la requête AJAX pour la lettre de mission
            var xhr = new XMLHttpRequest();
            var timestamp = new Date().getTime();
            xhr.open("GET", ajaxUrl + "?action=get_lettre_mission_info&cpt_id=" + actionId + "&formateur_id=" + formateurId + "&t=" + timestamp, true);
            
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 400) {
                    lettreSection.innerHTML = xhr.responseText;
                } else {
                    lettreSection.innerHTML = \'<div class="fsbdd-alert fsbdd-alert-danger">Erreur lors du chargement de la lettre de mission.</div>\';
                }
            };
            
            xhr.onerror = function() {
                lettreSection.innerHTML = \'<div class="fsbdd-alert fsbdd-alert-danger">Erreur de connexion.</div>\';
            };
            
            xhr.send();
        }
    </script>';

    return $output;
}

// Ajouter le shortcode pour la lettre de mission
add_shortcode('fsbdd_lettre_mission', 'display_lettre_mission_section');

// Gérer l'Ajax 
add_action('wp_ajax_get_uploaded_documents', 'get_uploaded_documents');
add_action('wp_ajax_nopriv_get_uploaded_documents', 'get_uploaded_documents');

// Gérer l'Ajax pour les lettres de mission
add_action('wp_ajax_get_lettre_mission_info', 'get_lettre_mission_info');
add_action('wp_ajax_nopriv_get_lettre_mission_info', 'get_lettre_mission_info');

// Gérer l'Ajax pour la validation de lettre de mission
add_action('wp_ajax_validate_lettre_mission', 'validate_lettre_mission');
add_action('wp_ajax_nopriv_validate_lettre_mission', 'validate_lettre_mission');

// Gérer l'Ajax pour l'acceptation de lettre de mission
add_action('wp_ajax_fsbdd_accept_lettre_mission', 'fsbdd_accept_lettre_mission');
add_action('wp_ajax_nopriv_fsbdd_accept_lettre_mission', 'fsbdd_accept_lettre_mission');

function get_uploaded_documents() {
    if (!isset($_GET['cpt_id']) || !isset($_GET['formateur_id'])) {
        echo '<div class="fsbdd-alert fsbdd-alert-danger">Paramètres manquants.</div>';
        wp_die();
    }

    $cpt_id = intval($_GET['cpt_id']);
    $formateur_id = intval($_GET['formateur_id']);
    $current_user_id = get_current_user_id();

    global $wpdb;
    $linked_formateur_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT `from` FROM {$wpdb->prefix}mb_relationships WHERE `to` = %d AND `type` = 'formateur-bdd-formateur-wp'",
            $current_user_id
        )
    );

    // Vérifier si l'utilisateur est admin, référent ou le formateur associé
    $can_access = current_user_can('administrator') || current_user_can('referent') || ($linked_formateur_id && $linked_formateur_id == $formateur_id);

    if (!$can_access) {
        echo '<div class="fsbdd-alert fsbdd-alert-danger">Vous n\'êtes pas autorisé à voir ces documents.</div>';
        wp_die();
    }

    // Récupérer le titre du CPT "action-de-formation"
    $cpt_title = get_the_title($cpt_id);
    if (!$cpt_title) {
        echo '<div class="fsbdd-alert fsbdd-alert-danger">Action de formation introuvable.</div>';
        wp_die();
    }

    // Utiliser le titre du CPT comme nom de dossier
    $sanitized_cpt_title = sanitize_title($cpt_title);

    // Chemin des fichiers
    $upload_dir = WP_CONTENT_DIR . "/documents-internes/$formateur_id/$sanitized_cpt_title";
    $output = '';

    // Récupérer les dates d'envoi depuis les métadonnées spécifiques
    $emargements_date = get_post_meta($cpt_id, 'fsbdd_recepmargmts', true);
    $compterendu_date = get_post_meta($cpt_id, 'fsbdd_recepcpterenduf', true);
    $evaluations_date = get_post_meta($cpt_id, 'fsbdd_recepeval', true);

    if (is_dir($upload_dir)) {
        $files = glob("$upload_dir/*");

        if ($files) {
            $output .= '<ul class="fsbdd-document-list">';
            foreach ($files as $file) {
                $file_name = basename($file);
                
                // Construire l'URL sécurisée via la fonction d'autorisation
                $secure_file_url = add_query_arg(
                    array('fsbdd_file' => "$formateur_id/$sanitized_cpt_title/$file_name"),
                    site_url()
                );
                
                // Utiliser l'URL sécurisée
                $file_url = $secure_file_url;
                
                $validation_date = get_post_meta($cpt_id, '_validated_' . md5($file), true);
                $sent_date = get_post_meta($cpt_id, '_sent_' . md5($file), true);
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                
                // Déterminer l'icône en fonction de l'extension
                $icon_class = 'fsbdd-icon-file';
                if (in_array(strtolower($file_extension), ['pdf'])) {
                    $icon_class = 'fsbdd-icon-pdf';
                } elseif (in_array(strtolower($file_extension), ['doc', 'docx'])) {
                    $icon_class = 'fsbdd-icon-word';
                } elseif (in_array(strtolower($file_extension), ['xls', 'xlsx'])) {
                    $icon_class = 'fsbdd-icon-excel';
                } elseif (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])) {
                    $icon_class = 'fsbdd-icon-image';
                }

                // Déterminer le type de document en fonction du nom
                $document_type = '';
                if (strpos($file_name, 'emargements-') === 0) {
                    $document_type = 'Émargements';
                    $specific_date = $emargements_date;
                } elseif (strpos($file_name, 'compterenduf-') === 0) {
                    $document_type = 'Compte rendu';
                    $specific_date = $compterendu_date;
                } elseif (strpos($file_name, 'evaluations-') === 0) {
                    $document_type = 'Évaluations';
                    $specific_date = $evaluations_date;
                } else {
                    $document_type = 'Autre document';
                    $specific_date = '';
                }

                if ($validation_date) {
                    $status_class = 'fsbdd-status-validated';
                    $validation_info = "<span class='fsbdd-validation-date'>Validé le : $validation_date</span>";
                } else {
                    $status_class = 'fsbdd-status-pending';
                    $validation_info = "<span class='fsbdd-validation-pending'>Non validé</span>";
                }

                // Utiliser la date spécifique si disponible, sinon utiliser la date générique
                $sent_info = '';
                if ($specific_date) {
                    $sent_info = "<span class='fsbdd-sent-date'>Envoyé le : $specific_date</span>";
                } elseif ($sent_date) {
                    $sent_info = "<span class='fsbdd-sent-date'>Envoyé le : $sent_date</span>";
                }

                $output .= "<li class='fsbdd-document-item $status_class'>";
                $output .= "<div class='fsbdd-document-icon $icon_class'></div>";
                $output .= "<div class='fsbdd-document-info'>";
                $output .= "<strong>$document_type</strong>";
                $output .= "<a href='$file_url' target='_blank' class='fsbdd-document-link'>$file_name</a>";
                $output .= "<div>" . $sent_info . " " . $validation_info . "</div>";
                $output .= "</div>";
                $output .= "</li>";
            }
            $output .= '</ul>';
        } else {
            $output .= '<div class="fsbdd-alert fsbdd-alert-info">Aucun document téléversé pour cette action de formation.</div>';
        }
    } else {
        $output .= '<div class="fsbdd-alert fsbdd-alert-info">Aucun document téléversé pour cette action de formation.</div>';
    }

    echo $output;
    wp_die();
}

// Fonction AJAX pour gérer l'acceptation de lettre de mission
function fsbdd_accept_lettre_mission() {
    // Vérifier les paramètres requis
    if (!isset($_POST['cpt_id']) || !isset($_POST['formateur_id']) || !isset($_POST['nonce'])) {
        wp_send_json_error('Paramètres manquants.');
    }

    $cpt_id = intval($_POST['cpt_id']);
    $formateur_id = intval($_POST['formateur_id']);
    $nonce = sanitize_text_field($_POST['nonce']);

    // Vérifier le nonce pour la sécurité
    if (!wp_verify_nonce($nonce, 'fsbdd_accept_lettre_' . $cpt_id . '_' . $formateur_id)) {
        wp_send_json_error('Vérification de sécurité échouée.');
    }

    // Vérifier que l'utilisateur est connecté
    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error('Vous devez être connecté pour effectuer cette action.');
    }

    // Vérifier les permissions - l'utilisateur doit être le formateur associé ou admin
    $can_accept = false;
    
    if (current_user_can('administrator')) {
        $can_accept = true;
    } else {
        // Vérifier que l'utilisateur est bien lié au formateur
        global $wpdb;
        $linked_formateur_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT `from` FROM {$wpdb->prefix}mb_relationships WHERE `to` = %d AND `type` = 'formateur-bdd-formateur-wp'",
                $current_user_id
            )
        );
        
        if ($linked_formateur_id && (int)$linked_formateur_id === (int)$formateur_id) {
            $can_accept = true;
        }
    }

    if (!$can_accept) {
        wp_send_json_error('Vous n\'êtes pas autorisé à accepter cette lettre de mission.');
    }

    // Vérifier que l'action de formation existe
    $action_post = get_post($cpt_id);
    if (!$action_post || $action_post->post_type !== 'action-de-formation') {
        wp_send_json_error('Action de formation introuvable.');
    }

    // Vérifier que le formateur existe
    $formateur_post = get_post($formateur_id);
    if (!$formateur_post || $formateur_post->post_type !== 'formateur') {
        wp_send_json_error('Formateur introuvable.');
    }

    // Vérifier que la lettre de mission existe physiquement
    $numero_inter = get_post_meta($cpt_id, 'fsbdd_inter_numero', true);
    $upload_dir = wp_upload_dir();
    $pdf_filename = "lettre-mission-{$numero_inter}-{$formateur_id}.pdf";
    $pdf_path = $upload_dir['basedir'] . '/pdfformateurs/' . $cpt_id . '/' . $pdf_filename;
    
    if (!file_exists($pdf_path)) {
        wp_send_json_error('La lettre de mission n\'existe pas. Veuillez demander sa génération.');
    }

    // Vérifier si la lettre a déjà été acceptée (irréversibilité)
    $existing_acceptance = get_post_meta($cpt_id, 'fsbdd_lettre_acceptee_' . $formateur_id, true);
    if (!empty($existing_acceptance)) {
        wp_send_json_error('Cette lettre de mission a déjà été acceptée le ' . $existing_acceptance['date'] . '.');
    }

    // Préparer les données d'acceptation
    $current_user = wp_get_current_user();
    $acceptance_data = array(
        'date' => current_time('d/m/Y H:i:s'),
        'timestamp' => current_time('timestamp'),
        'user_id' => $current_user_id,
        'user_name' => $current_user->display_name,
        'user_email' => $current_user->user_email,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Inconnue',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu'
    );

    // Enregistrer l'acceptation dans les métadonnées
    $meta_key = 'fsbdd_lettre_acceptee_' . $formateur_id;
    $update_result = update_post_meta($cpt_id, $meta_key, $acceptance_data);

    if ($update_result === false) {
        wp_send_json_error('Erreur lors de l\'enregistrement de l\'acceptation.');
    }

    // Log de traçabilité (optionnel - peut être ajouté dans les logs WordPress)
    error_log(sprintf(
        'FSBDD - Lettre de mission acceptée: Action %d, Formateur %d, Utilisateur %d (%s) le %s',
        $cpt_id,
        $formateur_id,
        $current_user_id,
        $current_user->user_login,
        $acceptance_data['date']
    ));

    // Retourner une réponse de succès
    wp_send_json_success(array(
        'message' => 'Lettre de mission acceptée avec succès.',
        'acceptance_date' => $acceptance_data['date'],
        'user_name' => $acceptance_data['user_name']
    ));
}

// FORMULAIRE ENVOI DOCUMENTS
function fsbdd_file_upload_form() {
    global $wpdb;

    // Récupérer l'ID de l'utilisateur connecté
    $current_user_id = get_current_user_id();

    if (!$current_user_id) {
        return '<div class="fsbdd-alert fsbdd-alert-warning">Veuillez vous connecter pour envoyer des fichiers.</div>';
    }

    // Récupérer l'ID du CPT 'formateur' lié à l'utilisateur connecté
    $linked_formateur_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT `from` FROM {$wpdb->prefix}mb_relationships WHERE `to` = %d AND `type` = 'formateur-bdd-formateur-wp'",
            $current_user_id
        )
    );

    if (!$linked_formateur_id) {
        return '<div class="fsbdd-alert fsbdd-alert-warning">Aucun formateur associé trouvé pour cet utilisateur.</div>';
    }

    // On affiche le nonce pour la sécurité
    $nonce_field = wp_nonce_field('fsbdd_upload_file_action', 'fsbdd_upload_nonce', true, false);

    // Construire le formulaire
    $output = '
        <div class="fsbdd-upload-container">
            <form id="fsbdd_upload_form" method="post" enctype="multipart/form-data" class="fsbdd-form">
                <h3 class="fsbdd-section-title">Envoyer un document</h3>
                ' . $nonce_field . '
                <input type="hidden" id="idcommande" name="idcommande" value="">
                <input type="hidden" id="idformateur" name="idformateur" value="' . esc_attr($linked_formateur_id) . '">
                
                <div class="fsbdd-form-group">
                    <label for="document_type" class="fsbdd-label">Type de document</label>
                    <select id="document_type" name="document_type" required class="fsbdd-select">
                        <option value="">-- Sélectionnez un type --</option>
                        <option value="emargements">Émargements</option>
                        <option value="compterenduf">Compte rendu formateur</option>
                        <option value="evaluations">Évaluations</option>
                        <option value="autre">Autre document</option>
                    </select>
                </div>

                <div id="custom_doc_name_wrapper" class="fsbdd-form-group" style="display:none;">
                    <label for="custom_doc_name" class="fsbdd-label">Nom du document</label>
                    <input type="text" id="custom_doc_name" name="custom_doc_name" class="fsbdd-input" placeholder="Nom personnalisé">
                </div>

                <div class="fsbdd-form-group">
                    <div class="fsbdd-file-upload-wrapper">
                        <div id="fsbdd_dropzone" class="fsbdd-dropzone">
                            <div class="fsbdd-dropzone-content">
                                <div class="fsbdd-dropzone-icon"></div>
                                <p>Glissez votre fichier ici ou cliquez pour en sélectionner un</p>
                                <p class="fsbdd-dropzone-formats">Format accepté: PDF</p>
                                <p class="fsbdd-dropzone-max-size">Taille maximale: 2 Mo</p>
                            </div>
                            <input type="file" id="fsbdd_file_upload" name="fsbdd_file_upload" accept=".pdf" required class="fsbdd-file-input">
                        </div>
                        <div id="fsbdd_selected_file" class="fsbdd-selected-file" style="display:none;">
                            <span id="fsbdd_file_name"></span>
                            <button type="button" id="fsbdd_remove_file" class="fsbdd-remove-file">×</button>
                        </div>
                    </div>
                </div>

                <div class="fsbdd-form-group">
                    <button type="submit" name="fsbdd_upload_submit" class="fsbdd-submit-btn">Envoyer le document</button>
                    <div id="fsbdd_upload_progress" class="fsbdd-progress-bar" style="display:none;">
                        <div class="fsbdd-progress-value"></div>
                    </div>
                </div>
            </form>
            <div id="fsbdd_upload_result" class="fsbdd-upload-result" style="display:none;"></div>
        </div>';

    // Message de confirmation ou d'erreur (affiché via JS maintenant)
    if (isset($_POST['fsbdd_upload_submit']) && wp_verify_nonce($_POST['fsbdd_upload_nonce'], 'fsbdd_upload_file_action')) {
        $upload_result = fsbdd_handle_file_upload();
        if (is_wp_error($upload_result)) {
            $error_message = $upload_result->get_error_message();
            $output .= "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    const resultDiv = document.getElementById('fsbdd_upload_result');
                    resultDiv.innerHTML = '<div class=\"fsbdd-alert fsbdd-alert-danger\">Erreur : " . esc_js($error_message) . "</div>';
                    resultDiv.style.display = 'block';
                });
            </script>";
        } else {
            $success_message = 'Document téléversé avec succès';
            $output .= "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    const resultDiv = document.getElementById('fsbdd_upload_result');
                    resultDiv.innerHTML = '<div class=\"fsbdd-alert fsbdd-alert-success\">" . esc_js($success_message) . "</div>';
                    resultDiv.style.display = 'block';
                    
                    // Rafraîchir la liste des documents
                    var select = document.getElementById('fsbdd_selsessionformatr');
                    if (select && select.value) {
                        // Utiliser la fonction de chargement si disponible
                        if (typeof loadDocuments === 'function') {
                            loadDocuments(select.value);
                        } else {
                            // Sinon, déclencher simplement l'événement change
                            var event = new Event('change');
                            select.dispatchEvent(event);
                        }
                    }
                });
            </script>";
        }
    }

    // Script pour gérer l'interface utilisateur
    $output .= "
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var docTypeSelect = document.getElementById('document_type');
                var customDocNameWrapper = document.getElementById('custom_doc_name_wrapper');
                var customDocNameInput = document.getElementById('custom_doc_name');
                var fileInput = document.getElementById('fsbdd_file_upload');
                var dropzone = document.getElementById('fsbdd_dropzone');
                var selectedFileDiv = document.getElementById('fsbdd_selected_file');
                var fileNameSpan = document.getElementById('fsbdd_file_name');
                var removeFileBtn = document.getElementById('fsbdd_remove_file');
                var uploadForm = document.getElementById('fsbdd_upload_form');
                var progressBar = document.getElementById('fsbdd_upload_progress');
                var progressValue = progressBar.querySelector('.fsbdd-progress-value');
                
                // Taille maximale de fichier en octets (2 Mo)
                var MAX_FILE_SIZE = 2 * 1024 * 1024;
                
                // Gestion du type de document
                if (docTypeSelect) {
                    docTypeSelect.addEventListener('change', function() {
                        if (this.value === 'autre') {
                            customDocNameWrapper.style.display = 'block';
                            customDocNameInput.required = true;
                        } else {
                            customDocNameWrapper.style.display = 'none';
                            customDocNameInput.required = false;
                        }
                    });
                }
                
                // Gestion du drag & drop
                if (dropzone) {
                    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(eventName) {
                        dropzone.addEventListener(eventName, function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                        }, false);
                    });
                    
                    ['dragenter', 'dragover'].forEach(function(eventName) {
                        dropzone.addEventListener(eventName, function() {
                            this.classList.add('fsbdd-dropzone-highlight');
                        }, false);
                    });
                    
                    ['dragleave', 'drop'].forEach(function(eventName) {
                        dropzone.addEventListener(eventName, function() {
                            this.classList.remove('fsbdd-dropzone-highlight');
                        }, false);
                    });
                    
                    dropzone.addEventListener('drop', function(e) {
                        if (e.dataTransfer && e.dataTransfer.files) {
                            fileInput.files = e.dataTransfer.files;
                            handleFileSelection();
                        }
                    }, false);
                }
                
                // Gestion de la sélection de fichier
                if (fileInput) {
                    fileInput.addEventListener('change', handleFileSelection);
                }
                
                function handleFileSelection() {
                    if (fileInput && fileInput.files && fileInput.files[0]) {
                        var file = fileInput.files[0];
                        
                        // Vérifier la taille du fichier
                        if (file.size > MAX_FILE_SIZE) {
                            alert('Le fichier est trop volumineux. La taille maximale autorisée est de 2 Mo.');
                            fileInput.value = '';
                            return;
                        }
                        
                        fileNameSpan.textContent = file.name;
                        dropzone.style.display = 'none';
                        selectedFileDiv.style.display = 'flex';
                    }
                }
                
                // Suppression du fichier sélectionné
                if (removeFileBtn) {
                    removeFileBtn.addEventListener('click', function() {
                        fileInput.value = '';
                        selectedFileDiv.style.display = 'none';
                        dropzone.style.display = 'block';
                    });
                }
                
                // Gestion de l'envoi du formulaire
                if (uploadForm) {
                    uploadForm.addEventListener('submit', function(e) {
                        // Vérifier si une action de formation est sélectionnée
                        var idcommande = document.getElementById('idcommande').value;
                        if (!idcommande) {
                            e.preventDefault();
                            alert('Veuillez sélectionner une action de formation.');
                            return false;
                        }
                        
                        // Vérifier la taille du fichier
                        if (fileInput.files && fileInput.files[0]) {
                            var file = fileInput.files[0];
                            if (file.size > MAX_FILE_SIZE) {
                                e.preventDefault();
                                alert('Le fichier est trop volumineux. La taille maximale autorisée est de 2 Mo.');
                                return false;
                            }
                        }
                        
                        // Afficher la barre de progression
                        progressBar.style.display = 'block';
                        var progress = 0;
                        
                        // Simuler la progression
                        var interval = setInterval(function() {
                            progress += 5;
                            if (progress > 90) clearInterval(interval);
                            progressValue.style.width = progress + '%';
                        }, 100);
                    });
                }
            });
        </script>
    ";

    return $output;
}

// Gestion du téléversement avec vérification des fichiers déjà validés
function fsbdd_handle_file_upload() {
    // Vérifier le nonce
    if (!isset($_POST['fsbdd_upload_nonce']) || !wp_verify_nonce($_POST['fsbdd_upload_nonce'], 'fsbdd_upload_file_action')) {
        return new WP_Error('invalid_nonce', 'Vérification de sécurité échouée.');
    }

    // Vérifier que les champs nécessaires ont été soumis
    if (!isset($_FILES['fsbdd_file_upload']) || empty($_FILES['fsbdd_file_upload']['name'])) {
        return new WP_Error('no_file', 'Aucun fichier sélectionné.');
    }

    if (empty($_POST['idcommande']) || empty($_POST['idformateur']) || empty($_POST['document_type'])) {
        return new WP_Error('no_id', 'Action de formation, formateur ou type de document manquant.');
    }

    $file = $_FILES['fsbdd_file_upload'];
    $idcommande = sanitize_text_field($_POST['idcommande']);
    $idformateur = sanitize_text_field($_POST['idformateur']);
    $document_type = sanitize_text_field($_POST['document_type']);
    $custom_doc_name = isset($_POST['custom_doc_name']) ? sanitize_file_name($_POST['custom_doc_name']) : '';

    // Vérifier la taille du fichier (2 Mo maximum)
    $max_size = 2 * 1024 * 1024; // 2 Mo en octets
    if ($file['size'] > $max_size) {
        return new WP_Error('file_too_large', 'Le fichier est trop volumineux. La taille maximale autorisée est de 2 Mo.');
    }

    // Récupérer le titre du CPT "action-de-formation"
    $cpt_title = get_the_title($idcommande);
    if (!$cpt_title) {
        return new WP_Error('invalid_cpt', 'Impossible de récupérer le titre de l\'action de formation.');
    }

    // Créer un nom de dossier basé sur le titre du CPT
    $sanitized_cpt_title = sanitize_title($cpt_title);

    // Vérifier les erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return new WP_Error('upload_error', 'Erreur lors du téléversement du fichier.');
    }

    // Définir le dossier cible dans wp-content
    $upload_dir = WP_CONTENT_DIR . "/documents-internes/$idformateur/$sanitized_cpt_title";

    // Créer les dossiers s'ils n'existent pas
    if (!file_exists($upload_dir)) {
        wp_mkdir_p($upload_dir);
    }

    // Renommage du fichier basé sur le type de document
    $new_file_name = '';
    
    if ($document_type === 'emargements') {
        $new_file_name = "emargements-$idformateur-$sanitized_cpt_title";
        
        // Vérifier si un document du même type existe déjà et est validé (peu importe le format)
        $existing_files = glob("$upload_dir/emargements-$idformateur-$sanitized_cpt_title.*");
        foreach ($existing_files as $existing_file) {
            $validation_date = get_post_meta($idcommande, '_validated_' . md5($existing_file), true);
            if ($validation_date) {
                return new WP_Error(
                    'file_already_validated',
                    "Un document d'émargement a déjà été validé le $validation_date et ne peut être remplacé."
                );
            }
        }
    } elseif ($document_type === 'compterenduf') {
        $new_file_name = "compterenduf-$idformateur-$sanitized_cpt_title";
        
        // Vérifier si un document du même type existe déjà et est validé
        $existing_files = glob("$upload_dir/compterenduf-$idformateur-$sanitized_cpt_title.*");
        foreach ($existing_files as $existing_file) {
            $validation_date = get_post_meta($idcommande, '_validated_' . md5($existing_file), true);
            if ($validation_date) {
                return new WP_Error(
                    'file_already_validated',
                    "Un compte rendu formateur a déjà été validé le $validation_date et ne peut être remplacé."
                );
            }
        }
    } elseif ($document_type === 'evaluations') {
        $new_file_name = "evaluations-$idformateur-$sanitized_cpt_title";
        
        // Vérifier si un document du même type existe déjà et est validé
        $existing_files = glob("$upload_dir/evaluations-$idformateur-$sanitized_cpt_title.*");
        foreach ($existing_files as $existing_file) {
            $validation_date = get_post_meta($idcommande, '_validated_' . md5($existing_file), true);
            if ($validation_date) {
                return new WP_Error(
                    'file_already_validated',
                    "Un document d'évaluations a déjà été validé le $validation_date et ne peut être remplacé."
                );
            }
        }
    } elseif ($document_type === 'autre') {
        if (empty($custom_doc_name)) {
            return new WP_Error('custom_name_missing', 'Veuillez saisir un nom personnalisé pour le document.');
        }
        $new_file_name = "$custom_doc_name-$idformateur-$sanitized_cpt_title";
        
        // Pour les documents personnalisés, vérifier si un document avec le même nom existe déjà et est validé
        $existing_files = glob("$upload_dir/$custom_doc_name-$idformateur-$sanitized_cpt_title.*");
        foreach ($existing_files as $existing_file) {
            $validation_date = get_post_meta($idcommande, '_validated_' . md5($existing_file), true);
            if ($validation_date) {
                return new WP_Error(
                    'file_already_validated',
                    "Un document avec ce nom a déjà été validé le $validation_date et ne peut être remplacé."
                );
            }
        }
    } else {
        return new WP_Error('invalid_doc_type', 'Type de document invalide.');
    }

    // Ajouter l'extension originale
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_file_name .= ".$file_extension";

    $file_path = $upload_dir . '/' . $new_file_name;

    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        return new WP_Error('upload_error', 'Impossible de déplacer le fichier.');
    }

    // Enregistrer la date d'envoi
    $current_date = date('d/m/Y'); // Format dd/mm/yyyy
    
    // Enregistrer dans la meta générique
    $meta_key_sent = '_sent_' . md5($file_path);
    update_post_meta($idcommande, $meta_key_sent, $current_date);
    
    // Mettre à jour les métadonnées pour ce type de document
    if ($document_type === 'emargements') {
        update_post_meta($idcommande, 'fsbdd_recepmargmts', $current_date);
        update_fsbdd_etat_documents($idcommande, 'emargements');
    } elseif ($document_type === 'compterenduf') {
        update_post_meta($idcommande, 'fsbdd_recepcpterenduf', $current_date);
        update_fsbdd_etat_documents($idcommande, 'compterenduf');
    } elseif ($document_type === 'evaluations') {
        update_post_meta($idcommande, 'fsbdd_recepeval', $current_date);
        update_fsbdd_etat_documents($idcommande, 'evaluations');
    }
	
    // Retourner l'URL du fichier et la date d'envoi
    $site_url = get_site_url();
    return [
        'path' => $file_path,
        'url' => "$site_url/wp-content/documents-internes/$idformateur/$sanitized_cpt_title/$new_file_name",
        'date_sent' => $current_date,
    ];
}

// Créer un shortcode pour afficher le formulaire d'upload
add_shortcode('fsbdd_file_upload', 'fsbdd_file_upload_form');

// SECURITE DES FICHIERS UPLOADES
// Ces fonctions sont maintenues identiques pour la compatibilité
add_action('init', function () {
    if (isset($_GET['fsbdd_file']) && !empty($_GET['fsbdd_file'])) {
        $requested_file = sanitize_text_field($_GET['fsbdd_file']);
        $current_user_id = get_current_user_id();

        // Vérifier si l'utilisateur est admin ou référent
        if (current_user_can('administrator') || current_user_can('referent')) {
            authorize_and_serve_file($requested_file);
        }

        // Vérifier les autorisations pour les formateurs
        if (is_user_allowed_to_access_file($current_user_id, $requested_file)) {
            authorize_and_serve_file($requested_file);
        }

        wp_die('Accès refusé.');
    }
});

// Vérifie si un utilisateur a le droit d'accéder au fichier
function is_user_allowed_to_access_file($user_id, $requested_file) {
    global $wpdb;

    // Récupérer le formateur lié
    $linked_formateur_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT `from` FROM {$wpdb->prefix}mb_relationships WHERE `to` = %d AND `type` = 'formateur-bdd-formateur-wp'",
            $user_id
        )
    );

    if (!$linked_formateur_id) {
        return false;
    }

    // Valider le chemin demandé
    $file_segments = explode('/', $requested_file);
    if (count($file_segments) < 3) {
        return false;
    }

    $formateur_id = sanitize_text_field($file_segments[0]);
    $cpt_title_slug = sanitize_text_field($file_segments[1]);
    $file_name = sanitize_text_field($file_segments[2]);

    // Vérifier que l'utilisateur accède à ses propres fichiers
    if ((int)$formateur_id === (int)$linked_formateur_id) {
        $file_path = WP_CONTENT_DIR . "/documents-internes/$formateur_id/$cpt_title_slug/$file_name";
        return file_exists($file_path);
    }

    return false;
}

// Fonction pour vérifier et servir le fichier
function authorize_and_serve_file($requested_file) {
    $file_path = realpath(WP_CONTENT_DIR . '/documents-internes/' . $requested_file);

    // Assurez-vous que le fichier est dans le répertoire autorisé
    $base_path = realpath(WP_CONTENT_DIR . '/documents-internes/');
    if (strpos($file_path, $base_path) !== 0 || !file_exists($file_path)) {
        wp_die('Fichier introuvable.');
    }

    // Envoyez les en-têtes pour le téléchargement
    header('Content-Type: ' . mime_content_type($file_path));
    header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
    header('Content-Length: ' . filesize($file_path));
    header('X-Content-Type-Options: nosniff'); // Ajout de sécurité
    readfile($file_path);
    exit;
}

// Fonction AJAX pour récupérer les informations de lettre de mission - VERSION CORRIGÉE
function get_lettre_mission_info() {
    // Traitement de l'acceptation de lettre de mission via formulaire POST
    if (isset($_POST['fsbdd_accept_action']) && $_POST['fsbdd_accept_action'] == '1') {
        $cpt_id = intval($_POST['cpt_id']);
        $formateur_id = intval($_POST['formateur_id']);
        $nonce = sanitize_text_field($_POST['accept_nonce']);
        
        // Vérifier le nonce
        if (!wp_verify_nonce($nonce, 'fsbdd_accept_lettre_' . $cpt_id . '_' . $formateur_id)) {
            wp_send_json_error('Erreur de sécurité.');
        }
        
        // Vérifier les permissions
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error('Vous devez être connecté.');
        }
        
        $can_accept = false;
        if (current_user_can('administrator')) {
            $can_accept = true;
        } else {
            global $wpdb;
            $linked_formateur_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT `from` FROM {$wpdb->prefix}mb_relationships WHERE `to` = %d AND `type` = 'formateur-bdd-formateur-wp'",
                    $current_user_id
                )
            );
            if ($linked_formateur_id && (int)$linked_formateur_id === (int)$formateur_id) {
                $can_accept = true;
            }
        }
        
        if (!$can_accept) {
            wp_send_json_error('Vous n\'êtes pas autorisé à accepter cette lettre.');
        }
        
        // Récupérer la version actuelle du PDF
        $versions = get_post_meta($cpt_id, 'fsbdd_pdf_versions', true) ?: [];
        $current_version = 'v1.0';
        if (isset($versions[$formateur_id])) {
            $current_version = $versions[$formateur_id]['current_version'] ?? 'v1.0';
        }
        
        // Vérifier si déjà acceptée pour la version actuelle
        $existing_acceptance = get_post_meta($cpt_id, 'fsbdd_lettre_acceptee_' . $formateur_id, true);
        if (!empty($existing_acceptance)) {
            $accepted_version = $existing_acceptance['version'] ?? 'v1.0';
            if ($accepted_version === $current_version) {
                wp_send_json_error('Cette lettre a déjà été acceptée le ' . $existing_acceptance['date'] . ' pour la version ' . $accepted_version . '.');
            }
            // Si c'est une ancienne version, on peut continuer pour accepter la nouvelle
        }
        
        // Enregistrer l'acceptation avec la version actuelle
        $current_user = wp_get_current_user();
        $acceptance_data = array(
            'date' => current_time('d/m/Y H:i:s'),
            'timestamp' => current_time('timestamp'),
            'version' => $current_version,
            'user_id' => $current_user_id,
            'user_name' => $current_user->display_name,
            'user_email' => $current_user->user_email,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Inconnue',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu'
        );
        
        $meta_key = 'fsbdd_lettre_acceptee_' . $formateur_id;
        $update_result = update_post_meta($cpt_id, $meta_key, $acceptance_data);
        
        // Vider le cache pour s'assurer que les données sont à jour
        if ($update_result) {
            wp_cache_delete($cpt_id, 'post_meta');
        }
        
        if ($update_result !== false) {
            // Log de traçabilité
            error_log(sprintf(
                'FSBDD - Lettre de mission acceptée: Action %d, Formateur %d, Utilisateur %d (%s) le %s',
                $cpt_id,
                $formateur_id,
                $current_user_id,
                $current_user->user_login,
                $acceptance_data['date']
            ));
            
            wp_send_json_success(array(
                'message' => 'Lettre de mission acceptée avec succès !',
                'cpt_id' => $cpt_id,
                'formateur_id' => $formateur_id
            ));
        } else {
            wp_send_json_error('Erreur lors de l\'enregistrement.');
        }
    }
    
    if (!isset($_GET['cpt_id']) || !isset($_GET['formateur_id'])) {
        echo '<div class="fsbdd-alert fsbdd-alert-danger">Paramètres manquants.</div>';
        wp_die();
    }

    $cpt_id = intval($_GET['cpt_id']);
    $formateur_id = intval($_GET['formateur_id']);

    // Vérifier que l'utilisateur a accès à ce formateur
    $current_user_id = get_current_user_id();
    if (!current_user_can('manage_options')) {
        global $wpdb;
        $linked_formateur_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT `from` FROM {$wpdb->prefix}mb_relationships WHERE `to` = %d AND `type` = 'formateur-bdd-formateur-wp'",
                $current_user_id
            )
        );
        
        if ($linked_formateur_id != $formateur_id) {
            echo '<div class="fsbdd-alert fsbdd-alert-danger">Accès non autorisé.</div>';
            wp_die();
        }
    }

    // Vérifier d'abord si le fichier PDF existe physiquement
    $numero_inter = get_post_meta($cpt_id, 'fsbdd_inter_numero', true);
    $upload_dir = wp_upload_dir();
    $pdf_filename = "lettre-mission-{$numero_inter}-{$formateur_id}.pdf";
    $pdf_path = $upload_dir['basedir'] . '/pdfformateurs/' . $cpt_id . '/' . $pdf_filename;
    
    if (!file_exists($pdf_path)) {
        echo '<div class="fsbdd-alert fsbdd-alert-warning">Aucune lettre de mission disponible pour cette action. Veuillez demander la génération de votre lettre de mission.</div>';
        wp_die();
    }

    // Récupérer les informations de versioning depuis le nouveau système
    $versions = get_post_meta($cpt_id, 'fsbdd_pdf_versions', true) ?: [];
    $pdf_generated_timestamp = null;
    $current_version = 'v1.0'; // Version par défaut
    
    if (isset($versions[$formateur_id])) {
        $current_version = $versions[$formateur_id]['current_version'] ?? 'v1.0';
        $pdf_generated_timestamp = $versions[$formateur_id]['pdf_generated_date'] ?? null;
    }
    
    // Fallback sur l'ancien système si pas de données dans le nouveau
    if (!$pdf_generated_timestamp) {
        $lettres_generees = get_post_meta($cpt_id, 'fsbdd_lettres_generees', true);
        if (is_array($lettres_generees) && isset($lettres_generees[$formateur_id])) {
            $pdf_generated_timestamp = $lettres_generees[$formateur_id];
        } else {
            // Si pas dans les métadonnées, utiliser la date de modification du fichier
            $pdf_generated_timestamp = filemtime($pdf_path);
        }
    }

    $output = '<div class="fsbdd-lettre-mission-content">';
    
    // Afficher un message de confirmation si l'utilisateur revient après acceptation
    if (isset($_GET['accepted']) && $_GET['accepted'] == '1') {
        $output .= '<div class="fsbdd-alert fsbdd-alert-success" style="margin-bottom: 15px;">✅ Lettre de mission acceptée avec succès ! La page a été rechargée pour afficher le nouveau statut.</div>';
    }
    
    // Informations de la lettre de mission
    $output .= '<div class="fsbdd-lettre-info">';
    $output .= '<h4>📄 Lettre de Mission - Version ' . esc_html($current_version) . '</h4>';
    
    if ($pdf_generated_timestamp) {
        $output .= '<p><strong>Date de génération :</strong> ' . esc_html(date('d/m/Y H:i', $pdf_generated_timestamp)) . '</p>';
    }
    
    // Informations sur la taille du fichier
    $file_size = size_format(filesize($pdf_path), 0);
    $output .= '<p><strong>Taille du fichier :</strong> ' . esc_html($file_size) . '</p>';
    $output .= '</div>';

    // Vérifier si la lettre a déjà été acceptée pour la version actuelle
    $meta_key_check = 'fsbdd_lettre_acceptee_' . $formateur_id;
    $acceptance_data = get_post_meta($cpt_id, $meta_key_check, true);
    
    // Vérifier si l'acceptation correspond à la version actuelle
    $is_accepted = false;
    $needs_revalidation = false;
    
    if (!empty($acceptance_data)) {
        $accepted_version = $acceptance_data['version'] ?? 'v1.0';
        if ($accepted_version === $current_version) {
            $is_accepted = true;
        } else {
            $needs_revalidation = true;
        }
    }
    

    
    // Bouton de téléchargement - CORRIGÉ avec le bon système
    $nonce = wp_create_nonce('fsbdd_download_pdf_' . $cpt_id . '_' . $formateur_id);
    $download_url = add_query_arg([
        'action'       => 'fsbdd_download_lettre_mission',
        'cpt_id'       => $cpt_id,
        'formateur_id' => $formateur_id,
        'nonce'        => $nonce
    ], admin_url('admin-ajax.php'));
    
    $output .= '<div class="fsbdd-lettre-actions">';
    $output .= '<a href="' . esc_url($download_url) . '" class="fsbdd-submit-btn" target="_blank" style="display: inline-block; text-decoration: none; margin-bottom: 15px;">📥 Télécharger la lettre de mission</a>';
    
    // Section d'acceptation de la lettre de mission
    if ($is_accepted) {
        $output .= '<div class="fsbdd-acceptance-status" style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 6px; margin-top: 15px;">';
        $output .= '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
        $output .= '<span style="font-size: 24px; margin-right: 10px;">✅</span>';
        $output .= '<strong>Lettre de mission acceptée</strong>';
        $output .= '</div>';
        $output .= '<p style="margin: 0; font-size: 14px;"><strong>Date d\'acceptation :</strong> ' . esc_html($acceptance_data['date']) . '</p>';
        $output .= '<p style="margin: 5px 0 0 0; font-size: 14px;"><strong>Acceptée par :</strong> ' . esc_html($acceptance_data['user_name']) . '</p>';
        $accepted_version = $acceptance_data['version'] ?? 'v1.0';
        $output .= '<p style="margin: 5px 0 0 0; font-size: 14px;"><strong>Version acceptée :</strong> ' . esc_html($accepted_version) . '</p>';
        $output .= '</div>';
    } elseif ($needs_revalidation) {
        $output .= '<div class="fsbdd-acceptance-section" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 6px; margin-top: 15px;">';
        $output .= '<div style="display: flex; align-items: center; margin-bottom: 15px;">';
        $output .= '<span style="font-size: 24px; margin-right: 10px;">🔄</span>';
        $output .= '<strong>Nouvelle validation requise</strong>';
        $output .= '</div>';
        $old_version = $acceptance_data['version'] ?? 'v1.0';
        $output .= '<p style="margin: 0 0 10px 0; font-size: 14px;">Une nouvelle version de la lettre de mission a été générée. Votre précédente acceptation (version ' . esc_html($old_version) . ') n\'est plus valide.</p>';
        $output .= '<p style="margin: 0 0 15px 0; font-size: 14px;"><strong>Version actuelle :</strong> ' . esc_html($current_version) . '</p>';
        $output .= '<p style="margin: 0 0 15px 0; font-size: 14px;">Veuillez accepter la nouvelle version pour confirmer votre participation.</p>';
        
        // Formulaire d'acceptation pour la nouvelle version
        $accept_nonce = wp_create_nonce('fsbdd_accept_lettre_' . $cpt_id . '_' . $formateur_id);
        $output .= '<form id="accept-form-' . $cpt_id . '-' . $formateur_id . '" style="margin: 0;" onsubmit="return acceptLettreAjax(event, ' . $cpt_id . ', ' . $formateur_id . ', \'' . esc_js($accept_nonce) . '\');">';
        $output .= '<button type="submit" class="fsbdd-validate-btn" style="background: #dc3545; margin: 0;">';
        $output .= '🔄 Accepter la nouvelle version';
        $output .= '</button>';
        $output .= '</form>';
        $output .= '</div>';
    } else {
        $output .= '<div class="fsbdd-acceptance-section" style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; border-radius: 6px; margin-top: 15px;">';
        $output .= '<div style="display: flex; align-items: center; margin-bottom: 15px;">';
        $output .= '<span style="font-size: 24px; margin-right: 10px;">⚠️</span>';
        $output .= '<strong>Acceptation requise</strong>';
        $output .= '</div>';
        $output .= '<p style="margin: 0 0 15px 0; font-size: 14px;">Vous devez accepter cette lettre de mission pour confirmer votre participation à cette action de formation.</p>';
        
        // Formulaire d'acceptation qui soumet vers admin-ajax.php
        $accept_nonce = wp_create_nonce('fsbdd_accept_lettre_' . $cpt_id . '_' . $formateur_id);
        $output .= '<form id="accept-form-' . $cpt_id . '-' . $formateur_id . '" style="margin: 0;" onsubmit="return acceptLettreAjax(event, ' . $cpt_id . ', ' . $formateur_id . ', \'' . esc_js($accept_nonce) . '\');">';
        $output .= '<button type="submit" class="fsbdd-validate-btn" style="background: #28a745; margin: 0;">';
        $output .= '✅ Accepter la lettre de mission';
        $output .= '</button>';
        $output .= '</form>';
        

        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    $output .= '</div>';

    echo $output;
    wp_die();
}

/**
 * Enregistre et enqueue le script AJAX pour l'acceptation des lettres de mission.
 */
function fsbdd_enqueue_accept_lettre_script() {
    // Créer le contenu JavaScript
    $script_content = '
    function acceptLettreAjax(event, cptId, formateurId, nonce) {
        event.preventDefault();

        if (!confirm("Êtes-vous sûr de vouloir accepter cette lettre de mission ? Cette action est irréversible.")) {
            return false;
        }

        var button = event.target.querySelector("button[type=submit]");
        var originalText = button.innerHTML;
        button.innerHTML = "⏳ Traitement...";
        button.disabled = true;

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "' . admin_url('admin-ajax.php') . '", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                button.innerHTML = originalText;
                button.disabled = false;

                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            if (typeof loadLettresMission === "function") {
                                loadLettresMission(cptId, formateurId);
                            } else {
                                window.location.reload();
                            }
                        } else {
                            console.error("Erreur (réponse du serveur): " + response.data);
                            alert("Une erreur est survenue: " + response.data);
                        }
                    } catch (e) {
                        console.error("Erreur de traitement de la réponse: ", e, xhr.responseText);
                        alert("Une erreur technique est survenue lors du traitement de la réponse du serveur.");
                    }
                } else {
                    console.error("Erreur de connexion. Statut: " + xhr.status);
                    alert("Une erreur de connexion est survenue. Veuillez réessayer.");
                }
            }
        };

        var params = "action=get_lettre_mission_info&fsbdd_accept_action=1&cpt_id=" + cptId + "&formateur_id=" + formateurId + "&accept_nonce=" + encodeURIComponent(nonce);
        xhr.send(params);

        return false;
    }
    ';
    
    // Ajouter le script inline
    wp_add_inline_script('jquery', $script_content);
}
add_action('wp_enqueue_scripts', 'fsbdd_enqueue_accept_lettre_script');
