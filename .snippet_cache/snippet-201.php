<?php
/**
 * Snippet ID: 201
 * Name: ALERTES PILOTAGE PAR SESSIONS tableau global
 * Description: 
 * @active false
 */


/**
 * SESSIONS alertes et filtres
 * Version : 1.0
 * Description : Alertes et filtres pour le tableau des sessions
 */

// Ne pas exécuter directement
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fonction principale pour afficher les alertes et filtres des sessions
 */
function fsbdd_sessions_alerts() {
    fsbdd_sessions_alerts_panel();
    fsbdd_sessions_alerts_scripts();
}

/**
 * Ajoute le panneau des alertes pour les sessions
 */
function fsbdd_sessions_alerts_panel() {
    // Vérifier les permissions
    if (!fsbdd_sessions_user_has_required_role()) {
        return;
    }

    // CSS pour les alertes sessions
    echo '<style>
        /* Conteneur alertes sessions */
        .fsbdd-sessions-alerts-container {
            background-color: #314150;
            border-radius: 8px;
            padding: 16px;
            color: #fff;
            margin-bottom: 20px;
        }
        
        .fsbdd-sessions-alerts-container h3 {
            color: #fff;
            margin-top: 0;
        }

        /* Section alertes rapprochements */
        .fsbdd-sessions-rappro-alerts-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
            padding: 12px;
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 3px 2px 8px rgb(0 0 0 / 25%);
        }

        .fsbdd-sessions-rappro-alert-item {
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

        .fsbdd-sessions-rappro-alert-item:hover {
            background-color: #fff8e1;
            transform: translateY(-1px);
            box-shadow: 4px 4px 9px rgb(0 0 0 / 34%);
        }

        .fsbdd-sessions-rappro-alert-item.active {
            background-color: #0073aa;
            color: #fff;
        }

        .fsbdd-sessions-rappro-alert-item .count {
            background-color: #dc3545;
            color: #fff;
            border-radius: 10px;
            padding: 1px 6px;
            margin-left: 4px;
            font-size: 10px;
            font-weight: bold;
        }

        /* Section filtres rapides */
        .fsbdd-sessions-quick-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
            padding: 16px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .fsbdd-sessions-filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 200px;
        }

        .fsbdd-sessions-filter-group h4 {
            margin: 0;
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 4px;
        }

        .fsbdd-sessions-checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .fsbdd-sessions-checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
            cursor: pointer;
            background: white;
            border: 1px solid #e2e8f0;
        }

        .fsbdd-sessions-checkbox-item:hover {
            background: #f1f5f9;
            border-color: #3b82f6;
            transform: translateX(2px);
        }

        .fsbdd-sessions-checkbox-item input[type="checkbox"] {
            margin: 0;
            transform: scale(1.1);
        }

        .fsbdd-sessions-checkbox-item label {
            margin: 0;
            font-size: 12px;
            font-weight: 500;
            color: #374151;
            cursor: pointer;
            flex: 1;
        }

        .fsbdd-sessions-checkbox-item .count {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: bold;
            min-width: 20px;
            text-align: center;
        }

        .fsbdd-sessions-checkbox-item.checked {
            background: #dbeafe;
            border-color: #3b82f6;
        }

        .fsbdd-sessions-checkbox-item.checked .count {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        /* Section alertes documents */
        .fsbdd-sessions-docs-alerts-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
            padding: 12px;
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 3px 2px 8px rgb(0 0 0 / 25%);
        }

        .fsbdd-sessions-docs-alert-item {
            display: flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            background-color: #fff;
            color: #495057;
            border: 1px solid #28a745;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .fsbdd-sessions-docs-alert-item:hover {
            background-color: #f8fff9;
            transform: translateY(-1px);
            box-shadow: 4px 4px 9px rgb(0 0 0 / 34%);
        }

        .fsbdd-sessions-docs-alert-item.active {
            background-color: #28a745;
            color: #fff;
        }

        /* Section alertes lettres de mission */
        .fsbdd-sessions-lm-alerts-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
            padding: 12px;
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 3px 2px 8px rgb(0 0 0 / 25%);
        }

        .fsbdd-sessions-lm-alert-item {
            display: flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            background-color: #fff;
            color: #495057;
            border: 1px solid #ffc107;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .fsbdd-sessions-lm-alert-item:hover {
            background-color: #fffbf0;
            transform: translateY(-1px);
            box-shadow: 4px 4px 9px rgb(0 0 0 / 34%);
        }

        .fsbdd-sessions-lm-alert-item.active {
            background-color: #ffc107;
            color: #000;
        }

        /* Bouton reset */
        .fsbdd-sessions-reset-filters {
            background-color: #6c757d;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 10px;
        }

        .fsbdd-sessions-reset-filters:hover {
            background-color: #5a6268;
        }
    </style>';

    echo '<div class="fsbdd-sessions-alerts-container">';
    echo '<h3>🔍 Filtres et Alertes - Sessions</h3>';
    
    // Section Filtres Rapides
    echo '<div class="fsbdd-sessions-quick-filters">';
    
    // Groupe Rapprochements
    echo '<div class="fsbdd-sessions-filter-group">';
    echo '<h4>📊 Rapprochements</h4>';
    echo '<div class="fsbdd-sessions-checkbox-group">';
    echo '<div class="fsbdd-sessions-checkbox-item" data-filter="rappro-none">';
    echo '<input type="checkbox" id="filter-rappro-none" data-filter="rappro-none">';
    echo '<label for="filter-rappro-none">Aucun rapprochement</label>';
    echo '<span class="count rappro-none-count">0</span>';
    echo '</div>';
    echo '<div class="fsbdd-sessions-checkbox-item" data-filter="rappro-partial">';
    echo '<input type="checkbox" id="filter-rappro-partial" data-filter="rappro-partial">';
    echo '<label for="filter-rappro-partial">Rapprochements partiels</label>';
    echo '<span class="count rappro-partial-count">0</span>';
    echo '</div>';
    echo '<div class="fsbdd-sessions-checkbox-item" data-filter="rappro-complete">';
    echo '<input type="checkbox" id="filter-rappro-complete" data-filter="rappro-complete">';
    echo '<label for="filter-rappro-complete">Rapprochements complets</label>';
    echo '<span class="count rappro-complete-count">0</span>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Groupe Documents
    echo '<div class="fsbdd-sessions-filter-group">';
    echo '<h4>📄 Documents</h4>';
    echo '<div class="fsbdd-sessions-checkbox-group">';
    echo '<div class="fsbdd-sessions-checkbox-item" data-filter="docs-emargement-manquant">';
    echo '<input type="checkbox" id="filter-docs-emargement" data-filter="docs-emargement-manquant">';
    echo '<label for="filter-docs-emargement">Émargements manquants</label>';
    echo '<span class="count docs-emargement-manquant-count">0</span>';
    echo '</div>';
    echo '<div class="fsbdd-sessions-checkbox-item" data-filter="docs-cpte-rendu-manquant">';
    echo '<input type="checkbox" id="filter-docs-cpte-rendu" data-filter="docs-cpte-rendu-manquant">';
    echo '<label for="filter-docs-cpte-rendu">Comptes-rendus manquants</label>';
    echo '<span class="count docs-cpte-rendu-manquant-count">0</span>';
    echo '</div>';
    echo '<div class="fsbdd-sessions-checkbox-item" data-filter="docs-evaluations-manquantes">';
    echo '<input type="checkbox" id="filter-docs-evaluations" data-filter="docs-evaluations-manquantes">';
    echo '<label for="filter-docs-evaluations">Évaluations manquantes</label>';
    echo '<span class="count docs-evaluations-manquantes-count">0</span>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Groupe Lettres de Mission
    echo '<div class="fsbdd-sessions-filter-group">';
    echo '<h4>✉️ Lettres de Mission</h4>';
    echo '<div class="fsbdd-sessions-checkbox-group">';
    echo '<div class="fsbdd-sessions-checkbox-item" data-filter="lm-non-envoyees">';
    echo '<input type="checkbox" id="filter-lm-non-envoyees" data-filter="lm-non-envoyees">';
    echo '<label for="filter-lm-non-envoyees">LM non envoyées</label>';
    echo '<span class="count lm-non-envoyees-count">0</span>';
    echo '</div>';
    echo '<div class="fsbdd-sessions-checkbox-item" data-filter="lm-non-signees">';
    echo '<input type="checkbox" id="filter-lm-non-signees" data-filter="lm-non-signees">';
    echo '<label for="filter-lm-non-signees">LM non signées</label>';
    echo '<span class="count lm-non-signees-count">0</span>';
    echo '</div>';
    echo '<div class="fsbdd-sessions-checkbox-item" data-filter="lm-non-recues">';
    echo '<input type="checkbox" id="filter-lm-non-recues" data-filter="lm-non-recues">';
    echo '<label for="filter-lm-non-recues">LM non reçues</label>';
    echo '<span class="count lm-non-recues-count">0</span>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<button class="fsbdd-sessions-reset-filters" onclick="resetAllQuickFilters()">🔄 Réinitialiser tous les filtres</button>';
    echo '</div>';
    
    // Section Rapprochements (ancienne version - conservée pour compatibilité)
    echo '<div class="fsbdd-sessions-rappro-alerts-wrapper">';
    echo '<strong style="color: #495057; margin-right: 10px;">Rapprochements :</strong>';
    echo '<div class="fsbdd-sessions-rappro-alert-item" data-filter="rappro-none">';
    echo 'Aucun rapprochement <span class="count rappro-none-count">0</span>';
    echo '</div>';
    echo '<div class="fsbdd-sessions-rappro-alert-item" data-filter="rappro-partial">';
    echo 'Rapprochements partiels <span class="count rappro-partial-count">0</span>';
    echo '</div>';
    echo '<div class="fsbdd-sessions-rappro-alert-item" data-filter="rappro-complete">';
    echo 'Rapprochements complets <span class="count rappro-complete-count">0</span>';
    echo '</div>';
    echo '</div>';
    
    // Section Documents
    echo '<div class="fsbdd-sessions-docs-alerts-wrapper">';
    echo '<strong style="color: #495057; margin-right: 10px;">Documents :</strong>';
    echo '<div class="fsbdd-sessions-docs-alert-item" data-filter="docs-emargement-manquant">';
    echo 'Émargements manquants <span class="count docs-emargement-manquant-count">0</span>';
    echo '</div>';
    echo '<div class="fsbdd-sessions-docs-alert-item" data-filter="docs-cpte-rendu-manquant">';
    echo 'Comptes-rendus manquants <span class="count docs-cpte-rendu-manquant-count">0</span>';
    echo '</div>';
    echo '<div class="fsbdd-sessions-docs-alert-item" data-filter="docs-evaluations-manquantes">';
    echo 'Évaluations manquantes <span class="count docs-evaluations-manquantes-count">0</span>';
    echo '</div>';
    echo '</div>';
    
    // Section Lettres de Mission
    echo '<div class="fsbdd-sessions-lm-alerts-wrapper">';
    echo '<strong style="color: #495057; margin-right: 10px;">Lettres de Mission :</strong>';
    echo '<div class="fsbdd-sessions-lm-alert-item" data-filter="lm-non-envoyees">';
    echo 'LM non envoyées <span class="count lm-non-envoyees-count">0</span>';
    echo '</div>';
    echo '<div class="fsbdd-sessions-lm-alert-item" data-filter="lm-non-signees">';
    echo 'LM non signées <span class="count lm-non-signees-count">0</span>';
    echo '</div>';
    echo '<div class="fsbdd-sessions-lm-alert-item" data-filter="lm-non-recues">';
    echo 'LM non reçues <span class="count lm-non-recues-count">0</span>';
    echo '</div>';
    echo '</div>';
    
    echo '<button class="fsbdd-sessions-reset-filters" onclick="resetSessionsFilters()">Réinitialiser les filtres</button>';
    echo '</div>';

    // JavaScript pour les filtres
    echo '<script>
    function resetSessionsFilters() {
        // Retirer toutes les classes active
        document.querySelectorAll(".fsbdd-sessions-rappro-alert-item, .fsbdd-sessions-docs-alert-item, .fsbdd-sessions-lm-alert-item").forEach(function(item) {
            item.classList.remove("active");
        });
        
        // Réafficher toutes les lignes
        document.querySelectorAll("#sessions-table tbody tr").forEach(function(row) {
            row.style.display = "";
        });
        
        // Mettre à jour le compteur
        updateSessionsCount();
    }
    
    function updateSessionsCount() {
        const visibleRows = document.querySelectorAll("#sessions-table tbody tr:not([style*=\"display: none\"])").length;
        const countElement = document.getElementById("sessions-filtered-count");
        if (countElement) {
            countElement.textContent = visibleRows + " session(s) affichée(s)";
        }
    }
    
    // Fonction pour réinitialiser tous les filtres rapides
    function resetAllQuickFilters() {
        // Décocher toutes les cases
        document.querySelectorAll('.fsbdd-sessions-quick-filters input[type="checkbox"]').forEach(function(checkbox) {
            checkbox.checked = false;
        });
        
        // Retirer les classes checked
        document.querySelectorAll('.fsbdd-sessions-checkbox-item').forEach(function(item) {
            item.classList.remove('checked');
        });
        
        // Réafficher toutes les lignes
        document.querySelectorAll('#sessions-table tbody tr').forEach(function(row) {
            row.style.display = '';
        });
        
        // Mettre à jour le compteur
        updateSessionsCount();
    }
    
    // Fonction pour appliquer les filtres multiples
    function applyQuickFilters() {
        const checkedFilters = [];
        document.querySelectorAll('.fsbdd-sessions-quick-filters input[type="checkbox"]:checked').forEach(function(checkbox) {
            checkedFilters.push(checkbox.getAttribute('data-filter'));
        });
        
        if (checkedFilters.length === 0) {
            // Aucun filtre sélectionné, afficher toutes les lignes
            document.querySelectorAll('#sessions-table tbody tr').forEach(function(row) {
                row.style.display = '';
            });
        } else {
            // Appliquer les filtres sélectionnés
            document.querySelectorAll('#sessions-table tbody tr').forEach(function(row) {
                let showRow = false;
                
                checkedFilters.forEach(function(filter) {
                    if (matchesFilter(row, filter)) {
                        showRow = true;
                    }
                });
                
                row.style.display = showRow ? '' : 'none';
            });
        }
        
        updateSessionsCount();
    }
    
    // Fonction pour vérifier si une ligne correspond à un filtre
    function matchesFilter(row, filter) {
        const sessionId = row.getAttribute('data-session-id');
        const indicator = row.querySelector('.session-rappro-indicator');
        
        switch(filter) {
            // Filtres rapprochements
            case 'rappro-none':
                if (indicator) {
                    const bgColor = indicator.style.backgroundColor;
                    return bgColor.includes('255, 205, 210'); // Rouge pastel
                }
                break;
            case 'rappro-partial':
                if (indicator) {
                    const bgColor = indicator.style.backgroundColor;
                    return bgColor.includes('255, 224, 178'); // Orange pastel
                }
                break;
            case 'rappro-complete':
                if (indicator) {
                    const bgColor = indicator.style.backgroundColor;
                    return bgColor.includes('200, 230, 201'); // Vert pastel
                }
                break;
            
            // Filtres documents
            case 'docs-emargement-manquant':
                return checkDocumentStatus(sessionId, 'emargement') === '1';
            case 'docs-cpte-rendu-manquant':
                return checkDocumentStatus(sessionId, 'cpte_rendu') === '1';
            case 'docs-evaluations-manquantes':
                return checkDocumentStatus(sessionId, 'evaluations') === '1';
            
            // Filtres lettres de mission
            case 'lm-non-envoyees':
                return checkLMStatus(sessionId, 'envoyee') === false;
            case 'lm-non-signees':
                return checkLMStatus(sessionId, 'signee') === false;
            case 'lm-non-recues':
                return checkLMStatus(sessionId, 'recue') === false;
        }
        
        return false;
    }
    
    // Gestionnaires d'événements pour les filtres
    document.addEventListener("DOMContentLoaded", function() {
        // Gestionnaires pour les nouveaux filtres rapides (cases à cocher)
        document.querySelectorAll('.fsbdd-sessions-quick-filters input[type="checkbox"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const item = this.closest('.fsbdd-sessions-checkbox-item');
                
                if (this.checked) {
                    item.classList.add('checked');
                } else {
                    item.classList.remove('checked');
                }
                
                applyQuickFilters();
            });
        });
        
        // Gestionnaires pour les clics sur les éléments de filtre
        document.querySelectorAll('.fsbdd-sessions-checkbox-item').forEach(function(item) {
            item.addEventListener('click', function(e) {
                if (e.target.type !== 'checkbox' && e.target.tagName !== 'LABEL') {
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change'));
                }
            });
        });
        // Filtres rapprochements
        document.querySelectorAll(".fsbdd-sessions-rappro-alert-item").forEach(function(item) {
            item.addEventListener("click", function() {
                const filter = this.getAttribute("data-filter");
                const isActive = this.classList.contains("active");
                
                // Retirer active de tous les filtres rapprochements
                document.querySelectorAll(".fsbdd-sessions-rappro-alert-item").forEach(function(el) {
                    el.classList.remove("active");
                });
                
                if (!isActive) {
                    this.classList.add("active");
                    filterSessionsByRappro(filter);
                } else {
                    resetSessionsFilters();
                }
            });
        });
        
        // Filtres documents
        document.querySelectorAll(".fsbdd-sessions-docs-alert-item").forEach(function(item) {
            item.addEventListener("click", function() {
                const filter = this.getAttribute("data-filter");
                const isActive = this.classList.contains("active");
                
                // Retirer active de tous les filtres documents
                document.querySelectorAll(".fsbdd-sessions-docs-alert-item").forEach(function(el) {
                    el.classList.remove("active");
                });
                
                if (!isActive) {
                    this.classList.add("active");
                    filterSessionsByDocs(filter);
                } else {
                    resetSessionsFilters();
                }
            });
        });
        
        // Filtres lettres de mission
        document.querySelectorAll(".fsbdd-sessions-lm-alert-item").forEach(function(item) {
            item.addEventListener("click", function() {
                const filter = this.getAttribute("data-filter");
                const isActive = this.classList.contains("active");
                
                // Retirer active de tous les filtres LM
                document.querySelectorAll(".fsbdd-sessions-lm-alert-item").forEach(function(el) {
                    el.classList.remove("active");
                });
                
                if (!isActive) {
                    this.classList.add("active");
                    filterSessionsByLM(filter);
                } else {
                    resetSessionsFilters();
                }
            });
        });
    });
    
    function filterSessionsByRappro(filter) {
        document.querySelectorAll("#sessions-table tbody tr").forEach(function(row) {
            const indicator = row.querySelector(".session-rappro-indicator");
            let show = false;
            
            if (indicator) {
                const bgColor = indicator.style.backgroundColor;
                
                switch(filter) {
                    case "rappro-none":
                        show = bgColor.includes("255, 205, 210"); // Rouge pastel
                        break;
                    case "rappro-partial":
                        show = bgColor.includes("255, 224, 178"); // Orange pastel
                        break;
                    case "rappro-complete":
                        show = bgColor.includes("200, 230, 201"); // Vert pastel
                        break;
                }
            }
            
            row.style.display = show ? "" : "none";
        });
        
        updateSessionsCount();
    }
    
    function filterSessionsByDocs(filter) {
        document.querySelectorAll("#sessions-table tbody tr").forEach(function(row) {
            const sessionId = row.getAttribute("data-session-id");
            let show = false;
            
            // Logique de filtrage selon le type de document
            // À adapter selon vos données
            switch(filter) {
                case "docs-emargement-manquant":
                    // Vérifier si émargement manquant
                    show = checkDocumentStatus(sessionId, "emargement") === "1";
                    break;
                case "docs-cpte-rendu-manquant":
                    // Vérifier si compte-rendu manquant
                    show = checkDocumentStatus(sessionId, "cpte_rendu") === "1";
                    break;
                case "docs-evaluations-manquantes":
                    // Vérifier si évaluations manquantes
                    show = checkDocumentStatus(sessionId, "evaluations") === "1";
                    break;
            }
            
            row.style.display = show ? "" : "none";
        });
        
        updateSessionsCount();
    }
    
    function filterSessionsByLM(filter) {
        document.querySelectorAll("#sessions-table tbody tr").forEach(function(row) {
            const sessionId = row.getAttribute("data-session-id");
            let show = false;
            
            // Logique de filtrage selon le statut des lettres de mission
            // À adapter selon vos données
            switch(filter) {
                case "lm-non-envoyees":
                    show = checkLMStatus(sessionId, "envoyee") === false;
                    break;
                case "lm-non-signees":
                    show = checkLMStatus(sessionId, "signee") === false;
                    break;
                case "lm-non-recues":
                    show = checkLMStatus(sessionId, "recue") === false;
                    break;
            }
            
            row.style.display = show ? "" : "none";
        });
        
        updateSessionsCount();
    }
    
    function checkDocumentStatus(sessionId, docType) {
        // Fonction utilitaire pour vérifier le statut des documents
        // À implémenter selon votre structure de données
        return "1"; // Placeholder
    }
    
    function checkLMStatus(sessionId, statusType) {
        // Fonction utilitaire pour vérifier le statut des lettres de mission
        // À implémenter selon votre structure de données
        return false; // Placeholder
    }
    </script>';
}

/**
 * Calcule et met à jour les compteurs d'alertes pour les sessions
 */
function fsbdd_calculate_sessions_alerts() {
    // Cette fonction sera appelée via AJAX pour mettre à jour les compteurs
    // en temps réel
    
    $alerts = array(
        'rappro_none' => 0,
        'rappro_partial' => 0,
        'rappro_complete' => 0,
        'docs_emargement_manquant' => 0,
        'docs_cpte_rendu_manquant' => 0,
        'docs_evaluations_manquantes' => 0,
        'lm_non_envoyees' => 0,
        'lm_non_signees' => 0,
        'lm_non_recues' => 0
    );
    
    // Récupérer toutes les sessions
    $sessions = get_posts(array(
        'post_type' => 'action-de-formation',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));
    
    foreach ($sessions as $session) {
        $session_data = fsbdd_get_session_data($session->ID);
        
        // Calculer l'état des rapprochements
        if (!empty($session_data['commands'])) {
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
                $alerts['rappro_complete']++;
            } elseif ($started_commands > 0 || $complete_commands > 0) {
                $alerts['rappro_partial']++;
            } else {
                $alerts['rappro_none']++;
            }
        } else {
            $alerts['rappro_none']++;
        }
        
        // Calculer les alertes documents
        if ($session_data['emargements'] === '1') {
            $alerts['docs_emargement_manquant']++;
        }
        if ($session_data['cpte_rendu'] === '1') {
            $alerts['docs_cpte_rendu_manquant']++;
        }
        if ($session_data['evaluations'] === '1') {
            $alerts['docs_evaluations_manquantes']++;
        }
        
        // Calculer les alertes lettres de mission
        // À implémenter selon la structure des données LM
        // Placeholder pour l'instant
    }
    
    return $alerts;
}

/**
 * AJAX handler pour mettre à jour les compteurs
 */
add_action('wp_ajax_update_sessions_alerts', 'fsbdd_ajax_update_sessions_alerts');
function fsbdd_ajax_update_sessions_alerts() {
    $alerts = fsbdd_calculate_sessions_alerts();
    wp_send_json_success($alerts);
}

/**
 * Script pour mettre à jour les compteurs via AJAX
 */
function fsbdd_sessions_alerts_scripts() {
    echo '<script>
    function updateSessionsAlertsCounters() {
        jQuery.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
                action: "update_sessions_alerts"
            },
            success: function(response) {
                if (response.success) {
                    const alerts = response.data;
                    
                    // Mettre à jour les compteurs
                    jQuery(".rappro-none-count").text(alerts.rappro_none);
                    jQuery(".rappro-partial-count").text(alerts.rappro_partial);
                    jQuery(".rappro-complete-count").text(alerts.rappro_complete);
                    jQuery(".docs-emargement-manquant-count").text(alerts.docs_emargement_manquant);
                    jQuery(".docs-cpte-rendu-manquant-count").text(alerts.docs_cpte_rendu_manquant);
                    jQuery(".docs-evaluations-manquantes-count").text(alerts.docs_evaluations_manquantes);
                    jQuery(".lm-non-envoyees-count").text(alerts.lm_non_envoyees);
                    jQuery(".lm-non-signees-count").text(alerts.lm_non_signees);
                    jQuery(".lm-non-recues-count").text(alerts.lm_non_recues);
                }
            }
        });
    }
    
    // Mettre à jour les compteurs au chargement de la page
    jQuery(document).ready(function() {
        updateSessionsAlertsCounters();
    });
    </script>';
}
