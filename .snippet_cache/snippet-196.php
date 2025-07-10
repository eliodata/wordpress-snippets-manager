<?php
/**
 * Snippet ID: 196
 * Name: SHORTCODE affichage lettre de mission et autres docs formateur a valider espace formateur
 * Description: 
 * @active false
 */


// SHORTCODE POUR AFFICHER LES LETTRES DE MISSION DISPONIBLES POUR LE FORMATEUR
// Avec possibilité d'acceptation et gestion des versions

/**
 * Shortcode principal pour afficher les lettres de mission du formateur
 */
function fsbdd_display_lettres_mission_formateur() {
    global $wpdb;

    // Vérifier si l'utilisateur est connecté
    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        return '<div class="fsbdd-alert fsbdd-alert-warning">Veuillez vous connecter pour voir vos lettres de mission.</div>';
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

    // Rechercher les actions de formation où le formateur est associé
    $actions_de_formation = get_posts([
        'post_type'   => 'action-de-formation',
        'posts_per_page' => -1,
        'fields'      => 'ids',
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
                            $linked_actions[] = $action_id;
                            break 2;
                        }
                    }
                }
            }
        }
    }

    if (empty($linked_actions)) {
        return '<div class="fsbdd-alert fsbdd-alert-info">Aucune action de formation associée trouvée.</div>';
    }

    // Construire l'affichage
    $output = fsbdd_get_lettres_mission_css();
    $output .= '<div class="fsbdd-lettres-mission-container">';
    $output .= '<h3 class="fsbdd-section-title">📋 Mes Lettres de Mission</h3>';
    
    foreach ($linked_actions as $action_id) {
        $output .= fsbdd_render_lettre_mission_card($action_id, $linked_formateur_id);
    }
    
    $output .= '</div>';
    $output .= fsbdd_get_lettres_mission_js();
    
    return $output;
}

/**
 * Rendu d'une carte de lettre de mission
 */
function fsbdd_render_lettre_mission_card($action_id, $formateur_id) {
    // Récupérer les informations de base
    $lieu = get_post_meta($action_id, 'fsbdd_select_lieusession', true);
    $startdate = get_post_meta($action_id, 'we_startdate', true);
    $enddate = get_post_meta($action_id, 'we_enddate', true);
    $numero = get_the_title($action_id);
    
    $lieu_complet = $lieu ? trim($lieu) : 'Adresse inconnue';
    $lieu_resume = $lieu ? explode(',', $lieu)[0] : 'Lieu inconnu';
    $lieu_resume = ucfirst(strtolower(trim($lieu_resume)));
    $startdate_formatted = $startdate ? date_i18n('j F Y', $startdate) : 'Date non définie';
    $enddate_formatted = $enddate ? date_i18n('j F Y', $enddate) : 'Date non définie';
    
    // Récupérer les informations de versioning
    $version_info = fsbdd_get_version_info_for_formateur($action_id, $formateur_id);
    
    // Récupérer le statut d'acceptation
    $acceptance_status = fsbdd_get_acceptance_status($action_id, $formateur_id);
    
    // Vérifier si une lettre de mission existe
    $pdf_exists = fsbdd_check_pdf_exists($action_id, $formateur_id);
    
    $output = '<div class="fsbdd-lettre-card">';
    
    // En-tête de la carte
    $output .= '<div class="fsbdd-lettre-header">';
    $output .= '<h4 class="fsbdd-lettre-title">' . esc_html($lieu_resume) . '</h4>';
    $output .= '<div class="fsbdd-lettre-meta">';
    $output .= '<span class="fsbdd-lettre-dates">' . esc_html($startdate_formatted) . ' - ' . esc_html($enddate_formatted) . '</span>';
    $output .= '<span class="fsbdd-lettre-numero">N° ' . esc_html($numero) . '</span>';
    $output .= '</div>';
    $output .= '</div>';
    
    // Corps de la carte
    $output .= '<div class="fsbdd-lettre-body">';
    
    if (!$pdf_exists) {
        $output .= '<div class="fsbdd-lettre-status fsbdd-status-unavailable">';
        $output .= '<span class="fsbdd-status-icon">⏳</span>';
        $output .= '<span>Lettre de mission en cours de génération</span>';
        $output .= '</div>';
    } else {
        // Informations de version
        if ($version_info) {
            $output .= '<div class="fsbdd-version-info">';
            $output .= '<div class="fsbdd-version-current">';
            $output .= '<span class="fsbdd-version-label">Version actuelle :</span> ';
            $output .= '<span class="fsbdd-version-number">' . esc_html($version_info['current_version']) . '</span>';
            $output .= '</div>';
            $output .= '<div class="fsbdd-version-date">';
            $output .= '<span class="fsbdd-date-label">Générée le :</span> ';
            $output .= '<span class="fsbdd-date-value">' . esc_html($version_info['generation_date']) . '</span>';
            $output .= '</div>';
            
            if ($version_info['planning_changed']) {
                $output .= '<div class="fsbdd-planning-alert">';
                $output .= '<span class="fsbdd-alert-icon">⚠️</span>';
                $output .= '<span>Planning modifié - Nouvelle version disponible</span>';
                $output .= '</div>';
            }
            $output .= '</div>';
        }
        
        // Statut d'acceptation
        $output .= '<div class="fsbdd-acceptance-section">';
        
        if ($acceptance_status['accepted']) {
            $output .= '<div class="fsbdd-acceptance-status fsbdd-status-accepted">';
            $output .= '<span class="fsbdd-status-icon">✅</span>';
            $output .= '<span>Acceptée le ' . esc_html($acceptance_status['accepted_date']) . '</span>';
            if ($acceptance_status['accepted_version']) {
                $output .= '<span class="fsbdd-accepted-version"> (Version ' . esc_html($acceptance_status['accepted_version']) . ')</span>';
            }
            $output .= '</div>';
            
            // Vérifier si une nouvelle version est disponible
            if ($version_info && $acceptance_status['accepted_version'] !== $version_info['current_version']) {
                $output .= '<div class="fsbdd-new-version-alert">';
                $output .= '<span class="fsbdd-alert-icon">🔄</span>';
                $output .= '<span>Nouvelle version disponible - Acceptation requise</span>';
                $output .= '</div>';
            }
        } else {
            $output .= '<div class="fsbdd-acceptance-status fsbdd-status-pending">';
            $output .= '<span class="fsbdd-status-icon">⏳</span>';
            $output .= '<span>En attente d\'acceptation</span>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    // Actions
    $output .= '<div class="fsbdd-lettre-actions">';
    
    if ($pdf_exists) {
        // Bouton de téléchargement
        $pdf_url = fsbdd_get_pdf_url($action_id, $formateur_id);
        $output .= '<a href="' . esc_url($pdf_url) . '" class="fsbdd-btn fsbdd-btn-download" target="_blank">';
        $output .= '<span class="fsbdd-btn-icon">📄</span> Télécharger';
        $output .= '</a>';
        
        // Bouton d'acceptation
        $need_acceptance = !$acceptance_status['accepted'] || 
                          ($version_info && $acceptance_status['accepted_version'] !== $version_info['current_version']);
        
        if ($need_acceptance) {
            $output .= '<button class="fsbdd-btn fsbdd-btn-accept" ';
            $output .= 'data-action-id="' . esc_attr($action_id) . '" ';
            $output .= 'data-formateur-id="' . esc_attr($formateur_id) . '" ';
            $output .= 'data-version="' . esc_attr($version_info ? $version_info['current_version'] : '') . '">';
            $output .= '<span class="fsbdd-btn-icon">✅</span> Accepter';
            $output .= '</button>';
        }
    }
    
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}

/**
 * Récupère les informations de version pour un formateur
 */
function fsbdd_get_version_info_for_formateur($action_id, $formateur_id) {
    $versions = get_post_meta($action_id, 'fsbdd_pdf_versions', true) ?: [];
    
    if (!isset($versions[$formateur_id])) {
        return null;
    }
    
    $formateur_data = $versions[$formateur_id];
    $current_version = $formateur_data['current_version'];
    $generation_date = date('d/m/Y à H:i', $formateur_data['pdf_generated_date']);
    
    // Vérifier si le planning a changé
    $planning_changed = fsbdd_has_planning_changed($action_id, $formateur_id);
    
    return [
        'current_version' => $current_version,
        'generation_date' => $generation_date,
        'planning_changed' => $planning_changed
    ];
}

/**
 * Récupère le statut d'acceptation d'une lettre de mission
 */
function fsbdd_get_acceptance_status($action_id, $formateur_id) {
    $acceptances = get_post_meta($action_id, 'fsbdd_lettres_acceptances', true) ?: [];
    
    if (!isset($acceptances[$formateur_id])) {
        return [
            'accepted' => false,
            'accepted_date' => null,
            'accepted_version' => null
        ];
    }
    
    $acceptance_data = $acceptances[$formateur_id];
    
    return [
        'accepted' => true,
        'accepted_date' => date('d/m/Y à H:i', $acceptance_data['date']),
        'accepted_version' => $acceptance_data['version']
    ];
}

/**
 * Vérifie si un PDF existe pour un formateur
 */
function fsbdd_check_pdf_exists($action_id, $formateur_id) {
    $lettres_generees = get_post_meta($action_id, 'fsbdd_lettres_generees', true) ?: [];
    return isset($lettres_generees[$formateur_id]);
}

/**
 * Récupère l'URL du PDF pour un formateur
 */
function fsbdd_get_pdf_url($action_id, $formateur_id) {
    $lettres_generees = get_post_meta($action_id, 'fsbdd_lettres_generees', true) ?: [];
    
    if (!isset($lettres_generees[$formateur_id])) {
        return '';
    }
    
    return $lettres_generees[$formateur_id]['url'];
}

/**
 * Gestion AJAX pour l'acceptation des lettres de mission
 */
add_action('wp_ajax_fsbdd_accept_lettre_mission', 'fsbdd_handle_accept_lettre_mission');
add_action('wp_ajax_nopriv_fsbdd_accept_lettre_mission', 'fsbdd_handle_accept_lettre_mission');

function fsbdd_handle_accept_lettre_mission() {
    // Vérifier le nonce
    if (!wp_verify_nonce($_POST['nonce'], 'fsbdd_accept_lettre_nonce')) {
        wp_send_json_error('Vérification de sécurité échouée.');
    }
    
    $action_id = intval($_POST['action_id']);
    $formateur_id = intval($_POST['formateur_id']);
    $version = sanitize_text_field($_POST['version']);
    $current_user_id = get_current_user_id();
    
    if (!$current_user_id) {
        wp_send_json_error('Utilisateur non connecté.');
    }
    
    // Vérifier que l'utilisateur est bien associé au formateur
    global $wpdb;
    $linked_formateur_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT `from` FROM {$wpdb->prefix}mb_relationships WHERE `to` = %d AND `type` = 'formateur-bdd-formateur-wp'",
            $current_user_id
        )
    );
    
    if (!$linked_formateur_id || $linked_formateur_id != $formateur_id) {
        wp_send_json_error('Vous n\'êtes pas autorisé à accepter cette lettre de mission.');
    }
    
    // Enregistrer l'acceptation
    $acceptances = get_post_meta($action_id, 'fsbdd_lettres_acceptances', true) ?: [];
    
    $acceptances[$formateur_id] = [
        'date' => time(),
        'version' => $version,
        'user_id' => $current_user_id
    ];
    
    update_post_meta($action_id, 'fsbdd_lettres_acceptances', $acceptances);
    
    wp_send_json_success([
        'message' => 'Lettre de mission acceptée avec succès.',
        'accepted_date' => date('d/m/Y à H:i'),
        'accepted_version' => $version
    ]);
}

/**
 * CSS pour les lettres de mission
 */
function fsbdd_get_lettres_mission_css() {
    return '<style>
        .fsbdd-lettres-mission-container {
            font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .fsbdd-section-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #2271b1;
            border-bottom: 2px solid #2271b1;
            padding-bottom: 0.5rem;
        }
        
        .fsbdd-lettre-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: box-shadow 0.3s ease;
        }
        
        .fsbdd-lettre-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .fsbdd-lettre-header {
            background: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #ddd;
            border-radius: 8px 8px 0 0;
        }
        
        .fsbdd-lettre-title {
            margin: 0 0 0.5rem 0;
            font-size: 1.2rem;
            color: #2271b1;
        }
        
        .fsbdd-lettre-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #666;
        }
        
        .fsbdd-lettre-body {
            padding: 1rem;
        }
        
        .fsbdd-version-info {
            background: #e7f3ff;
            border-left: 4px solid #2271b1;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 0 4px 4px 0;
        }
        
        .fsbdd-version-current, .fsbdd-version-date {
            margin-bottom: 0.25rem;
        }
        
        .fsbdd-version-label, .fsbdd-date-label {
            font-weight: 500;
            color: #2271b1;
        }
        
        .fsbdd-planning-alert {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 0.5rem;
            border-radius: 4px;
            margin-top: 0.5rem;
            color: #856404;
        }
        
        .fsbdd-acceptance-section {
            margin-bottom: 1rem;
        }
        
        .fsbdd-acceptance-status {
            padding: 0.5rem;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }
        
        .fsbdd-status-accepted {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .fsbdd-status-pending {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .fsbdd-status-unavailable {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 0.75rem;
            border-radius: 4px;
            text-align: center;
        }
        
        .fsbdd-new-version-alert {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 0.5rem;
            border-radius: 4px;
            color: #0c5460;
        }
        
        .fsbdd-lettre-actions {
            padding: 1rem;
            border-top: 1px solid #ddd;
            background: #f8f9fa;
            border-radius: 0 0 8px 8px;
            display: flex;
            gap: 0.5rem;
        }
        
        .fsbdd-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .fsbdd-btn-download {
            background: #2271b1;
            color: white;
        }
        
        .fsbdd-btn-download:hover {
            background: #1e5a8a;
            color: white;
            text-decoration: none;
        }
        
        .fsbdd-btn-accept {
            background: #28a745;
            color: white;
        }
        
        .fsbdd-btn-accept:hover {
            background: #218838;
        }
        
        .fsbdd-btn-accept:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .fsbdd-alert {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .fsbdd-alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeaa7;
        }
        
        .fsbdd-alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
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
        
        @media (max-width: 768px) {
            .fsbdd-lettre-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
            }
            
            .fsbdd-lettre-actions {
                flex-direction: column;
            }
        }
    </style>';
}

/**
 * JavaScript pour les lettres de mission
 */
function fsbdd_get_lettres_mission_js() {
    $nonce = wp_create_nonce('fsbdd_accept_lettre_nonce');
    
    return '<script>
        document.addEventListener("DOMContentLoaded", function() {
            // Gestion des boutons d\'acceptation
            document.querySelectorAll(".fsbdd-btn-accept").forEach(function(button) {
                button.addEventListener("click", function() {
                    const actionId = this.dataset.actionId;
                    const formateurId = this.dataset.formateurId;
                    const version = this.dataset.version;
                    
                    if (confirm("Êtes-vous sûr de vouloir accepter cette lettre de mission ?")) {
                        acceptLettreMission(actionId, formateurId, version, this);
                    }
                });
            });
            
            function acceptLettreMission(actionId, formateurId, version, button) {
                // Désactiver le bouton
                button.disabled = true;
                button.innerHTML = "<span class=\"fsbdd-btn-icon\">⏳</span> Traitement...";
                
                // Préparer les données
                const formData = new FormData();
                formData.append("action", "fsbdd_accept_lettre_mission");
                formData.append("action_id", actionId);
                formData.append("formateur_id", formateurId);
                formData.append("version", version);
                formData.append("nonce", "' . $nonce . '");
                
                // Envoyer la requête
                fetch("' . admin_url('admin-ajax.php') . '", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Succès - mettre à jour l\'interface
                        showSuccessMessage("Lettre de mission acceptée avec succès !");
                        
                        // Recharger la page après un délai
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        // Erreur
                        showErrorMessage(data.data || "Une erreur est survenue.");
                        
                        // Réactiver le bouton
                        button.disabled = false;
                        button.innerHTML = "<span class=\"fsbdd-btn-icon\">✅</span> Accepter";
                    }
                })
                .catch(error => {
                    console.error("Erreur:", error);
                    showErrorMessage("Erreur de connexion.");
                    
                    // Réactiver le bouton
                    button.disabled = false;
                    button.innerHTML = "<span class=\"fsbdd-btn-icon\">✅</span> Accepter";
                });
            }
            
            function showSuccessMessage(message) {
                showMessage(message, "success");
            }
            
            function showErrorMessage(message) {
                showMessage(message, "danger");
            }
            
            function showMessage(message, type) {
                // Créer le message
                const alertDiv = document.createElement("div");
                alertDiv.className = `fsbdd-alert fsbdd-alert-${type}`;
                alertDiv.textContent = message;
                
                // Insérer au début du container
                const container = document.querySelector(".fsbdd-lettres-mission-container");
                if (container) {
                    container.insertBefore(alertDiv, container.firstChild);
                    
                    // Supprimer après 5 secondes
                    setTimeout(() => {
                        if (alertDiv.parentNode) {
                            alertDiv.parentNode.removeChild(alertDiv);
                        }
                    }, 5000);
                }
            }
        });
    </script>';
}

// Enregistrer le shortcode
add_shortcode('fsbdd_lettres_mission_formateur', 'fsbdd_display_lettres_mission_formateur');

/**
 * Fonction pour afficher les acceptations dans l'admin
 * À intégrer dans les metaboxes existantes
 */
function fsbdd_display_acceptances_in_admin($cpt_id) {
    $acceptances = get_post_meta($cpt_id, 'fsbdd_lettres_acceptances', true) ?: [];
    
    if (empty($acceptances)) {
        echo '<p>Aucune acceptation enregistrée.</p>';
        return;
    }
    
    echo '<div class="fsbdd-acceptances-list">';
    echo '<h4>📋 Acceptations des lettres de mission</h4>';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><th>Formateur</th><th>Date d\'acceptation</th><th>Version acceptée</th><th>Utilisateur</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($acceptances as $formateur_id => $acceptance_data) {
        $formateur_name = get_the_title($formateur_id);
        $accepted_date = date('d/m/Y à H:i', $acceptance_data['date']);
        $accepted_version = $acceptance_data['version'];
        $user_info = get_userdata($acceptance_data['user_id']);
        $user_name = $user_info ? $user_info->display_name : 'Utilisateur inconnu';
        
        echo '<tr>';
        echo '<td>' . esc_html($formateur_name) . '</td>';
        echo '<td>' . esc_html($accepted_date) . '</td>';
        echo '<td><strong>' . esc_html($accepted_version) . '</strong></td>';
        echo '<td>' . esc_html($user_name) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>';
}

