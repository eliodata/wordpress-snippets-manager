<?php
/**
 * Snippet ID: 138
 * Name: V2 Page plannings global formateurs fournisseurs actions de formation
 * Description: 
 * @active true
 */

/**
 * Plugin Name: Gestion des Plannings Optimisée (Version AJAX Complète)
 * Description: Version avec chargement rapide des filtres et tableau AJAX
 * Version: 2.0
 * Author: Votre nom
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/* ------------------------------------------------------------------------
 * 1. INITIALISATION ET MENUS
 * ------------------------------------------------------------------------ */

// Ajouter le lien dans la barre d'admin du haut
add_action('admin_bar_menu', function($admin_bar) {
    $admin_bar->add_node([
        'id'    => 'gestion-plannings',
        'title' => '<span class="ab-icon dashicons dashicons-calendar-alt"></span>' . __('Plannings', 'your-text-domain'),
        'href'  => admin_url('admin.php?page=gestion-plannings'),
        'meta'  => [
            'title' => __('Gestion des Plannings', 'your-text-domain'),
            'class' => 'menupop'
        ]
    ]);
}, 100);

// Enregistrer la page d'administration
add_action('admin_menu', function() {
    add_submenu_page(
        null,
        __('Gestion des Plannings', 'your-text-domain'),
        __('Plannings', 'your-text-domain'),
        'manage_options',
        'gestion-plannings',
        'render_planning_admin_page'
    );
});

/* ------------------------------------------------------------------------
 * 2. FONCTIONS DE SÉCURITÉ ET VÉRIFICATIONS
 * ------------------------------------------------------------------------ */

function user_has_required_role() {
    $user = wp_get_current_user();
    $allowed_roles = ['administrator', 'compta', 'referent'];
    foreach ($user->roles as $role) {
        if (in_array($role, $allowed_roles)) {
            return true;
        }
    }
    return false;
}

function verify_nonce($field, $action) {
    return (isset($_POST[$field]) && wp_verify_nonce($_POST[$field], $action));
}

/* ------------------------------------------------------------------------
 * 3. PAGE D'ADMINISTRATION PRINCIPALE
 * ------------------------------------------------------------------------ */

function render_planning_admin_page() {
    if (!user_has_required_role()) {
        wp_die(__('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.', 'your-text-domain'));
    }

    // Traitement des formulaires si soumis
    handle_planning_form_submission();
    display_planning_notices();
    ?>

<div class="wrap">
    <h1><?php _e('Gestion des Plannings', 'your-text-domain'); ?></h1>
    
    <!-- Section de sélection rapide -->
    <div id="planning-quick-filters" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: #0073aa; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span class="dashicons dashicons-filter"></span>
                <?php _e('Filtres de recherche', 'your-text-domain'); ?>
            </div>
            <button type="button" id="toggle-filters" class="button button-small">
                <span class="dashicons dashicons-arrow-up"></span>
                <?php _e('Masquer les filtres', 'your-text-domain'); ?>
            </button>
        </h2>
        
        <!-- Tous les filtres sur une ligne -->
        <div id="all-filters">
            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                <!-- Mode temporel -->
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">
                        <?php _e('Période', 'your-text-domain'); ?>
                    </label>
                    <select id="time-mode" style="width: 150px; padding: 8px;">
                        <option value="all"><?php _e('Tous les plannings', 'your-text-domain'); ?></option>
                        <option value="current"><?php _e('En cours / À venir', 'your-text-domain'); ?></option>
                        <option value="past"><?php _e('Plannings passés', 'your-text-domain'); ?></option>
                        <option value="this_month"><?php _e('Ce mois', 'your-text-domain'); ?></option>
                        <option value="next_month"><?php _e('Mois prochain', 'your-text-domain'); ?></option>
                        <option value="custom"><?php _e('Personnalisée', 'your-text-domain'); ?></option>
                    </select>
                </div>

                <!-- Dates personnalisées -->
                <div id="custom-dates" style="display: none;">
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;"><?php _e('Dates', 'your-text-domain'); ?></label>
                    <div style="display: flex; gap: 5px;">
                        <input type="text" id="date-from" class="datepicker" placeholder="Du" style="width: 80px; padding: 8px;">
                        <input type="text" id="date-to" class="datepicker" placeholder="Au" style="width: 80px; padding: 8px;">
                    </div>
                </div>

                <!-- Type -->
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">
                        <?php _e('Type', 'your-text-domain'); ?>
                    </label>
                    <select id="quick-type" style="width: 120px; padding: 8px;">
                        <option value=""><?php _e('Tous', 'your-text-domain'); ?></option>
                        <option value="formateur"><?php _e('Formateurs', 'your-text-domain'); ?></option>
                        <option value="fournisseur"><?php _e('Fournisseurs', 'your-text-domain'); ?></option>
                    </select>
                </div>

                <!-- État -->
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">
                        <?php _e('État', 'your-text-domain'); ?>
                    </label>
                    <select id="quick-etat" style="width: 120px; padding: 8px;">
                        <option value=""><?php _e('Tous', 'your-text-domain'); ?></option>
                        <?php foreach (get_etat_options() as $val => $label): ?>
                            <option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sessions -->
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">
                        <?php _e('Session', 'your-text-domain'); ?>
                    </label>
                    <div style="position: relative;">
                        <input type="text" id="quick-action" placeholder="Numéro session" style="width: 120px; padding: 8px;" autocomplete="off">
                        <div id="session-suggestions" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-top: none; max-height: 200px; overflow-y: auto; z-index: 1000; display: none;"></div>
                    </div>
                </div>

                <!-- Formateur -->
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">
                        <?php _e('Formateur', 'your-text-domain'); ?>
                    </label>
                    <select id="specific-formateur" style="width: 150px; padding: 8px;">
                        <option value=""><?php _e('Tous', 'your-text-domain'); ?></option>
                        <?php
                        $formateurs = get_posts(['post_type' => 'formateur', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                        foreach($formateurs as $f) {
                            echo '<option value="'.esc_attr($f->ID).'">'.esc_html($f->post_title).'</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <!-- Type de session -->
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">
                        <?php _e('Type session', 'your-text-domain'); ?>
                    </label>
                    <select id="specific-lieu" style="width: 100px; padding: 8px;">
                        <option value=""><?php _e('Tous', 'your-text-domain'); ?></option>
                        <option value="INTER">INTER</option>
                        <option value="INTRA">INTRA</option>
                    </select>
                </div>
                
                <!-- Formation -->
                <div style="position: relative;">
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">
                        <?php _e('Formation', 'your-text-domain'); ?>
                    </label>
                    <input type="text" id="formation-search" placeholder="<?php _e('Rechercher...', 'your-text-domain'); ?>" style="width: 150px; padding: 8px;">
                    <input type="hidden" id="formation-id" value="">
                    <div id="formation-suggestions" style="display: none; position: absolute; background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 200px; overflow-y: auto; z-index: 1000; width: 200px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
                </div>

                <!-- Boutons d'action -->
                <div style="display: flex; gap: 10px;">
                    <button id="load-plannings" class="button button-primary" style="height: 40px; padding: 0 15px;">
                        <span class="dashicons dashicons-search" style="margin-right: 5px;"></span>
                        <?php _e('Charger', 'your-text-domain'); ?>
                    </button>
                    <button id="reset-filters" class="button" style="height: 40px; padding: 0 15px;">
                        <span class="dashicons dashicons-dismiss" style="margin-right: 5px;"></span>
                        <?php _e('Réinitialiser', 'your-text-domain'); ?>
                    </button>
                </div>
            </div>

            <!-- Raccourcis rapides -->
            <div style="margin-top: 15px; display: flex; align-items: center; flex-wrap: wrap; gap: 10px;">
                <strong><?php _e('Raccourcis :', 'your-text-domain'); ?></strong>
                <button class="button button-small quick-shortcut" data-mode="today">
                    <span class="dashicons dashicons-calendar"></span>
                    <?php _e('Aujourd\'hui', 'your-text-domain'); ?>
                </button>
                <button class="button button-small quick-shortcut" data-mode="this_week">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php _e('Cette semaine', 'your-text-domain'); ?>
                </button>
                
                <!-- Raccourcis cochables -->
                <label style="display: inline-flex; align-items: center; gap: 5px;">
                    <input type="checkbox" id="shortcut-urgent" class="shortcut-toggle">
                    <span class="dashicons dashicons-warning" style="color: #d63638;"></span>
                    <?php _e('États urgents', 'your-text-domain'); ?>
                </label>
                
                <label style="display: inline-flex; align-items: center; gap: 5px;">
                    <input type="checkbox" id="shortcut-conflicts" class="shortcut-toggle">
                    <span class="dashicons dashicons-flag" style="color: #dba617;"></span>
                    <?php _e('Potentiels conflits', 'your-text-domain'); ?>
                </label>
            </div>
        </div>
    </div>

    <!-- Zone de chargement et résultats -->
    <div id="planning-results">
        <div id="loading-message" style="display: none; text-align: center; padding: 40px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
            <div class="spinner is-active" style="float: none; margin: 0 auto 20px; visibility: visible;"></div>
            <p style="color: #666; font-size: 16px; margin: 0 0 10px 0;"><?php _e('Chargement des plannings en cours...', 'your-text-domain'); ?></p>
            <p style="color: #999; font-size: 14px; margin: 0;" id="loading-details"><?php _e('Préparation des données', 'your-text-domain'); ?></p>
        </div>

        <div id="planning-content" style="display: none;">
            <!-- Le contenu du tableau sera injecté ici via AJAX -->
        </div>

        <div id="no-results" style="display: none; text-align: center; padding: 40px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
            <span class="dashicons dashicons-info" style="font-size: 48px; color: #ccc; margin-bottom: 20px; display: block;"></span>
            <h3 style="color: #666;"><?php _e('Aucun planning trouvé', 'your-text-domain'); ?></h3>
            <p style="color: #999;"><?php _e('Essayez de modifier vos critères de recherche.', 'your-text-domain'); ?></p>
        </div>
    </div>

    <!-- Message de bienvenue initial -->
    <div id="welcome-message" style="text-align: center; padding: 60px; background: #f9f9f9; border: 2px dashed #ddd; border-radius: 5px;">
        <span class="dashicons dashicons-calendar-alt" style="font-size: 72px; color: #ccc; margin-bottom: 20px; display: block;"></span>
        <h2 style="color: #666; margin-bottom: 15px;"><?php _e('Bienvenue dans la gestion des plannings', 'your-text-domain'); ?></h2>
        <p style="color: #999; font-size: 16px; line-height: 1.5;">
            <?php _e('Utilisez les filtres ci-dessus pour charger et afficher les plannings souhaités.', 'your-text-domain'); ?><br>
            <?php _e('Le chargement à la demande permet une navigation plus rapide et plus fluide.', 'your-text-domain'); ?>
        </p>
        <div style="margin-top: 20px;">
            <button class="button button-secondary quick-start" data-action="current">
                <?php _e('Voir les plannings actuels', 'your-text-domain'); ?>
            </button>
        </div>
    </div>

    <!-- Formulaire d'ajout (masqué par défaut) -->
    <div id="add-planning-section" style="display: none;">
        <!-- Section d'ajout sera injectée ici -->
    </div>
</div>

<!-- Scripts et styles -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<style>
/* Styles pour l'interface AJAX */
#planning-quick-filters select,
#planning-quick-filters input[type="text"] {
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 14px;
}

#planning-quick-filters select:focus,
#planning-quick-filters input[type="text"]:focus {
    border-color: #0073aa;
    box-shadow: 0 0 0 1px #0073aa;
    outline: none;
}

.quick-shortcut {
    background: #f0f0f1;
    border: 1px solid #ccc;
    transition: all 0.2s;
    font-size: 13px;
}

.quick-shortcut:hover {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

.quick-shortcut .dashicons {
    font-size: 14px;
    margin-right: 3px;
}

.planning-loaded #planning-table {
    width: 100%;
    border-spacing: 0;
    table-layout: fixed;
}

.planning-loaded #planning-table thead th {
    background: #0073aa;
    color: #fff;
    text-align: center;
    font-weight: 600;
    font-size: 12px;
    padding: 8px 4px;
    position: sticky;
    top: 0;
    z-index: 2;
}

.planning-loaded #planning-table tbody tr:hover {
    background: #f0f8ff;
}

.planning-loaded #planning-table td {
    padding: 4px;
    vertical-align: middle;
    text-align: center;
    font-size: 12px;
    border-bottom: 1px solid #eee;
}

.planning-loaded #planning-table input[type="text"],
.planning-loaded #planning-table select {
    width: 100%;
    padding: 2px 4px;
    font-size: 11px;
    border: 1px solid #ddd;
    border-radius: 2px;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

#planning-content {
    animation: fadeIn 0.5s ease-out;
}

.quick-start {
    font-size: 16px;
    padding: 10px 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
    
    // Initialisation des datepickers
    $('.datepicker').datepicker({ 
        dateFormat: 'dd.mm.yy',
        changeMonth: true,
        changeYear: true
    });
    
    // Gestion de l'affichage des dates personnalisées
    $('#time-mode').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#custom-dates').show();
        } else {
            $('#custom-dates').hide();
        }
    });
    
    // Gestion du toggle des filtres
    $('#toggle-filters').on('click', function() {
        var $allFilters = $('#all-filters');
        var $icon = $(this).find('.dashicons');
        var $text = $(this);
        
        if ($allFilters.is(':visible')) {
            $allFilters.hide();
            $icon.removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
            $text.html('<span class="dashicons dashicons-arrow-down"></span><?php echo esc_js(__('Afficher les filtres', 'your-text-domain')); ?>');
        } else {
            $allFilters.show();
            $icon.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
            $text.html('<span class="dashicons dashicons-arrow-up"></span><?php echo esc_js(__('Masquer les filtres', 'your-text-domain')); ?>');
        }
    });
    
    // Gestion du bouton de réinitialisation
    $('#reset-filters').on('click', function() {
        // Réinitialiser tous les filtres
        $('#time-mode').val('current');
        $('#date-from, #date-to').val('');
        $('#custom-dates').hide();
        $('#quick-type, #quick-etat').val('');
        $('#quick-action').val('').removeData('session-id');
        $('#specific-formateur, #specific-lieu').val('');
        $('#formation-search').val('');
        $('#formation-id').val('');
        $('#shortcut-urgent, #shortcut-conflicts').prop('checked', false);
        $('#session-suggestions').hide();
        $('#formation-suggestions').hide();
        
        // Recharger les plannings avec les filtres réinitialisés
        loadPlannings();
        
        // Notification visuelle
        $('<div class="notice notice-success is-dismissible" style="margin: 10px 0; padding: 10px;"><p><?php echo esc_js(__('Filtres réinitialisés', 'your-text-domain')); ?></p></div>')
            .insertAfter('#planning-quick-filters')
            .delay(2000)
            .fadeOut();
    });

    // Raccourcis rapides
    $('.quick-shortcut').on('click', function() {
        var mode = $(this).data('mode');
        
        switch(mode) {
            case 'today':
                $('#time-mode').val('custom');
                $('#custom-dates').show();
                var today = new Date();
                var dateStr = ('0' + today.getDate()).slice(-2) + '.' + 
                             ('0' + (today.getMonth() + 1)).slice(-2) + '.' + 
                             today.getFullYear();
                $('#date-from, #date-to').val(dateStr);
                break;
            case 'this_week':
                $('#time-mode').val('current');
                break;
            case 'urgent':
                $('#time-mode').val('current');
                $('#quick-etat').val('Option');
                break;
            case 'conflicts':
                $('#time-mode').val('current');
                break;
        }
        
        // Charger automatiquement
        loadPlannings();
    });

    // Bouton de démarrage rapide
    $('.quick-start').on('click', function() {
        var action = $(this).data('action');
        if (action === 'current') {
            $('#time-mode').val('current');
            loadPlannings();
        }
    });

    // Bouton de chargement principal
    $('#load-plannings').on('click', function() {
        loadPlannings();
    });

    // Fonction de chargement AJAX
    function loadPlannings() {
        // Masquer les sections
        $('#welcome-message').hide();
        $('#planning-content').hide();
        $('#no-results').hide();
        $('#add-planning-section').hide();
        
        // Afficher le chargement
        $('#loading-message').show();
        
        // Progression détaillée
        const steps = [
            'Préparation des filtres...',
            'Récupération des actions...',
            'Traitement des plannings...',
            'Génération du tableau...'
        ];
        
        let currentStep = 0;
        const progressInterval = setInterval(() => {
            if (currentStep < steps.length) {
                updateLoadingMessage(steps[currentStep]);
                currentStep++;
            }
        }, 500);

        // Préparer les paramètres
        var sessionId = $('#quick-action').data('session-id') || '';
        var sessionText = $('#quick-action').val() || '';
        var formationId = $('#formation-id').val() || '';
        var formationText = $('#formation-search').val() || '';
        
        var params = {
            action: 'load_planning_data',
            nonce: '<?php echo wp_create_nonce('planning_ajax_nonce'); ?>',
            time_mode: $('#time-mode').val(),
            date_from: $('#date-from').val(),
            date_to: $('#date-to').val(),
            quick_type: $('#quick-type').val(),
            quick_etat: $('#quick-etat').val(),
            quick_action: sessionId,
            session_search: sessionText,
            specific_formateur: $('#specific-formateur').val(),
            specific_lieu: $('#specific-lieu').val(),
            filter_urgent: $('#filter-urgent').is(':checked') ? 1 : 0,
            formation_id: formationId,
            formation_search: formationText
        };
        


        // Add pagination parameters
        params.page = window.currentPage || 1;
        params.per_page = 50;

        // Requête AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: params,
            timeout: 30000, // 30 secondes
            success: function(response) {
                clearInterval(progressInterval);
                $('#loading-message').hide();
                
                if (response.success) {
                    if (response.data.total_count > 0) {
                        var content = response.data.html;
                        if (response.data.pagination) {
                            content += response.data.pagination;
                        }
                        $('#planning-content').html(content).show();
                        $('#add-planning-section').html(response.data.add_form).show();
                        
                        // Réinitialiser les événements sur le nouveau contenu
                        initializePlanningEvents();
                        initializePaginationEvents();
                    } else {
                        $('#no-results').show();
                    }
                } else {
                    showError('Erreur lors du chargement : ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function(xhr, status, error) {
                clearInterval(progressInterval);
                $('#loading-message').hide();
                if (status === 'timeout') {
                    showError('Le chargement a pris trop de temps. Essayez de réduire la période sélectionnée.');
                } else {
                    showError('Erreur de connexion lors du chargement des plannings.');
                }
            }
        });
    }

    // Mettre à jour le message de chargement
    function updateLoadingMessage(message) {
        $('#loading-details').text(message);
    }

    // Afficher une erreur
    function showError(message) {
        $('<div class="notice notice-error" style="margin: 20px 0; padding: 10px;"><p>' + message + '</p></div>')
            .insertAfter('#planning-quick-filters')
            .delay(5000)
            .fadeOut();
    }

    // Autocomplétion pour les sessions
    let sessionSearchTimeout;
    $('#quick-action').on('input', function() {
        const query = $(this).val().trim();
        const $suggestions = $('#session-suggestions');
        
        if (query.length < 2) {
            $suggestions.hide();
            return;
        }
        
        clearTimeout(sessionSearchTimeout);
        sessionSearchTimeout = setTimeout(function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'search_sessions',
                    nonce: '<?php echo wp_create_nonce('planning_ajax_nonce'); ?>',
                    query: query
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        let html = '';
                        response.data.forEach(function(session) {
                            const displayText = session.display || session.numero;
                            html += '<div class="session-suggestion" data-id="' + session.id + '" data-numero="' + session.numero + '" style="padding: 8px; cursor: pointer; border-bottom: 1px solid #eee;">' + displayText + '</div>';
                        });
                        $suggestions.html(html).show();
                    } else {
                        $suggestions.hide();
                    }
                },
                error: function() {
                    $suggestions.hide();
                }
            });
        }, 300);
    });
    
    // Sélection d'une suggestion
    $(document).on('click', '.session-suggestion', function() {
        const numero = $(this).data('numero');
        const id = $(this).data('id');
        $('#quick-action').val(numero).data('session-id', id);
        $('#session-suggestions').hide();
    });
    
    // Masquer les suggestions quand on clique ailleurs
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#quick-action, #session-suggestions').length) {
            $('#session-suggestions').hide();
        }
    });

    // Initialize pagination events
    function initializePaginationEvents() {
        $(document).off('click', '.pagination-btn');
        $(document).on('click', '.pagination-btn', function() {
            var page = $(this).data('page');
            window.currentPage = page;
            loadPlannings();
        });
    }

    // Initialiser les événements sur le contenu chargé
    function initializePlanningEvents() {
        // Datepickers pour les nouveaux éléments
        $('.datepicker').datepicker({ 
            dateFormat: 'dd.mm.yy',
            changeMonth: true,
            changeYear: true
        });
        
        // Cocher automatiquement la case "edit" si un champ change
        $(document).off('change', '#planning-table input, #planning-table select');
        $(document).on('change', '#planning-table input, #planning-table select', function(){
            if (!$(this).is('input[type="checkbox"][name*="[edit]"]')) {
                $(this).closest('tr').find('input[type="checkbox"][name*="[edit]"]').prop('checked', true);
            }
        });

        // Confirmation des modifications
        $(document).off('click', '#confirm-edit-planning');
        $(document).on('click', '#confirm-edit-planning', function(e){
            e.preventDefault();
            
            const rows = $('#planning-table tbody tr').not('[style*="background"]');
            let messages = [];

            rows.each(function(){
                const $row = $(this);
                const editChecked = $row.find('input[name*="[edit]"]').is(':checked');
                const deleteChecked = $row.find('input[name*="[delete]"]').is(':checked');
                
                if (editChecked && !deleteChecked) {
                    const date = $row.find('input[name*="[date]"]').val();
                    const nomText = $row.find('select[name*="[nom]"] option:selected').text();
                    const type = $row.find('input[name*="[type]"]').val();
                    
                    messages.push(`${nomText} (${type}) - ${date}`);
                }
            });
            
            if (messages.length === 0) {
                alert('<?php echo esc_js(__('Aucune modification sélectionnée.', 'your-text-domain')); ?>');
                return;
            }

            let confirmationText = "<?php echo esc_js(__('Modifications à appliquer :\\n\\n','your-text-domain')); ?>";
            confirmationText += messages.join('\n');
            confirmationText += "\n\n<?php echo esc_js(__('Confirmez-vous ?', 'your-text-domain')); ?>";

            if (confirm(confirmationText)) {
                const form = $('#planning-main-form');
                $('<input>').attr({
                    type: 'hidden',
                    name: 'action',
                    value: 'edit_planning'
                }).appendTo(form);
                form.submit();
            }
        });

        // Gestion des types pour l'ajout
        $(document).off('change', '#new_type');
        $(document).on('change', '#new_type', function(){
            var type = $(this).val();
            $('#new_nom').empty().append('<option value=""><?php echo esc_js(__('Sélectionner','your-text-domain')); ?></option>');
            
            if (type === 'formateur') {
                <?php
                $formateurs = get_posts(['post_type' => 'formateur', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                foreach($formateurs as $f) {
                    echo '$("#new_nom").append(\'<option value="'.$f->ID.'">'.esc_js($f->post_title).'</option>\');';
                }
                ?>
                $('#new_commplanfourn_block').hide();
            } else {
                <?php
                $salles = get_posts(['post_type' => 'salle-de-formation', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                foreach($salles as $s) {
                    echo '$("#new_nom").append(\'<option value="'.$s->ID.'">'.esc_js($s->post_title).'</option>\');';
                }
                ?>
                $('#new_commplanfourn_block').show();
            }
        });

        // Mode dates/période pour l'ajout
        $(document).off('change', '#new_mode');
        $(document).on('change', '#new_mode', function(){
            var mode = $(this).val();
            $('#dates_block, #periode_block, #recurrence_block').hide();
            
            if (mode === 'dates') {
                $('#dates_block').show();
            } else if (mode === 'periode') {
                $('#periode_block').show();
            } else if (mode === 'recurrence') {
                $('#recurrence_block').show();
            }
        });

        // Ajout de dates multiples
        $(document).off('click', '#add_more_dates');
        $(document).on('click', '#add_more_dates', function(){
            var newDateInput = '<div style="margin-bottom: 5px; display: flex; align-items: center; gap: 5px;">' +
                '<input type="text" name="new_dates[]" class="datepicker" placeholder="jj.mm.aaaa" style="width: 100%; padding: 6px; font-size: 12px; border: 1px solid #ccc; border-radius: 3px;" />' +
                '<button type="button" class="remove-date" style="background: #d63638; color: white; border: none; border-radius: 3px; padding: 4px 6px; font-size: 10px; cursor: pointer;" title="Supprimer">×</button>' +
                '</div>';
            $('#dates_container').append(newDateInput);
            $('.datepicker').datepicker({ 
                dateFormat: 'dd.mm.yy',
                changeMonth: true,
                changeYear: true
            });
        });
        
        // Supprimer une date
        $(document).on('click', '.remove-date', function(){
            $(this).parent().remove();
        });

        // Gestion des boutons de raccourci pour les jours de récurrence
        $(document).off('click', '#select_weekdays, #select_all_days, #clear_days');
        $(document).on('click', '#select_weekdays', function(){
            $('input[name="new_recurrence_days[]"]').prop('checked', false);
            $('input[name="new_recurrence_days[]"][value="1"], input[name="new_recurrence_days[]"][value="2"], input[name="new_recurrence_days[]"][value="3"], input[name="new_recurrence_days[]"][value="4"], input[name="new_recurrence_days[]"][value="5"]').prop('checked', true);
        });
        $(document).on('click', '#select_all_days', function(){
            $('input[name="new_recurrence_days[]"]').prop('checked', true);
        });
        $(document).on('click', '#clear_days', function(){
            $('input[name="new_recurrence_days[]"]').prop('checked', false);
        });

        // Gestion des limitations d'heures selon la disponibilité
        function updateTimeConstraints() {
            var dispo = $('select[name="new_dispo"]').val();
            var timeStart = $('#new_recurrence_time_start');
            var timeEnd = $('#new_recurrence_time_end');
            var simpleBlock = $('#simple_time_block');
            var detailedBlock = $('#detailed_time_block');
            
            // Supprimer les anciennes contraintes
            timeStart.removeAttr('min max');
            timeEnd.removeAttr('min max');
            
            if (dispo === 'Journée') {
                // Afficher les horaires détaillés pour journée complète
                simpleBlock.hide();
                detailedBlock.show();
                
                // Contraintes pour les horaires du matin
                $('#new_recurrence_morning_start').attr('min', '07:00').attr('max', '13:00');
                $('#new_recurrence_morning_end').attr('min', '07:00').attr('max', '13:00');
                
                // Contraintes pour les horaires de l'après-midi
                $('#new_recurrence_afternoon_start').attr('min', '13:00').attr('max', '20:00');
                $('#new_recurrence_afternoon_end').attr('min', '13:00').attr('max', '20:00');
            } else {
                // Afficher les horaires simples pour matin ou après-midi
                simpleBlock.show();
                detailedBlock.hide();
                
                if (dispo === 'Matin') {
                    timeStart.attr('min', '07:00').attr('max', '13:00');
                    timeEnd.attr('min', '07:00').attr('max', '13:00');
                    if (timeStart.val() > '13:00' || timeStart.val() < '07:00') timeStart.val('08:00');
                    if (timeEnd.val() > '13:00' || timeEnd.val() < '07:00') timeEnd.val('12:00');
                } else if (dispo === 'Aprem') {
                    timeStart.attr('min', '13:00').attr('max', '20:00');
                    timeEnd.attr('min', '13:00').attr('max', '20:00');
                    if (timeStart.val() < '13:00' || timeStart.val() > '20:00') timeStart.val('14:00');
                    if (timeEnd.val() < '13:00' || timeEnd.val() > '20:00') timeEnd.val('17:00');
                }
            }
        }
        
        // Fonction pour valider l'ordre des heures
        function validateTimeOrder() {
            var dispo = $('select[name="new_dispo"]').val();
            
            if (dispo === 'Journée') {
                // Validation pour les horaires détaillés
                var morningStart = $('#new_recurrence_morning_start').val();
                var morningEnd = $('#new_recurrence_morning_end').val();
                var afternoonStart = $('#new_recurrence_afternoon_start').val();
                var afternoonEnd = $('#new_recurrence_afternoon_end').val();
                
                // Valider l'ordre du matin
                if (morningStart && morningEnd && morningStart >= morningEnd) {
                    var startHour = parseInt(morningStart.split(':')[0]);
                    var endHour = Math.min(startHour + 2, 13);
                    var newEndTime = (endHour < 10 ? '0' : '') + endHour + ':00';
                    $('#new_recurrence_morning_end').val(newEndTime);
                }
                
                // Valider l'ordre de l'après-midi
                if (afternoonStart && afternoonEnd && afternoonStart >= afternoonEnd) {
                    var startHour = parseInt(afternoonStart.split(':')[0]);
                    var endHour = Math.min(startHour + 2, 20);
                    var newEndTime = (endHour < 10 ? '0' : '') + endHour + ':00';
                    $('#new_recurrence_afternoon_end').val(newEndTime);
                }
            } else {
                // Validation pour les horaires simples
                var timeStart = $('#new_recurrence_time_start').val();
                var timeEnd = $('#new_recurrence_time_end').val();
                
                if (timeStart && timeEnd && timeStart >= timeEnd) {
                    var startHour = parseInt(timeStart.split(':')[0]);
                    var maxHour = parseInt($('#new_recurrence_time_end').attr('max').split(':')[0]);
                    var endHour = Math.min(startHour + 2, maxHour);
                    var newEndTime = (endHour < 10 ? '0' : '') + endHour + ':00';
                    $('#new_recurrence_time_end').val(newEndTime);
                }
            }
        }
        
        // Appliquer les contraintes lors du changement de disponibilité
        $(document).on('change', 'select[name="new_dispo"]', updateTimeConstraints);
        
        // Valider l'ordre des heures lors des changements
        $(document).on('change', '#new_recurrence_time_start, #new_recurrence_time_end, #new_recurrence_morning_start, #new_recurrence_morning_end, #new_recurrence_afternoon_start, #new_recurrence_afternoon_end', validateTimeOrder);
        
        // Initialiser les contraintes
        updateTimeConstraints();

        // Initialiser les événements du formulaire d'ajout
        $('#new_type').trigger('change');
        $('#new_mode').trigger('change');
    }

    // Nouveaux raccourcis cochables
    $('.shortcut-toggle').on('change', function() {
        var isUrgent = $('#shortcut-urgent').is(':checked');
        var isConflicts = $('#shortcut-conflicts').is(':checked');
        
        if (isUrgent) {
            $('#quick-etat').val('Option');
            $('#filter-urgent').prop('checked', true);
        } else {
            $('#filter-urgent').prop('checked', false);
        }
        
        // Pour les conflits, on peut définir une logique spécifique
        // Par exemple, chercher les formateurs avec plusieurs plannings le même jour
        if (isConflicts) {
            // Logique pour détecter les conflits
            // À définir selon vos critères
        }
        
        // Recharger automatiquement
        loadPlannings();
    });

    // Permettre le chargement avec Entrée sur les champs
    $('#planning-quick-filters input, #planning-quick-filters select').on('keypress', function(e) {
        if (e.which === 13) { // Touche Entrée
            loadPlannings();
        }
    });
    
    // Recherche de formation avec autocomplétion
    $('#formation-search').on('input', function() {
        var query = $(this).val();
        if (query.length < 2) {
            $('#formation-suggestions').hide();
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'search_formations',
                nonce: '<?php echo wp_create_nonce('planning_ajax_nonce'); ?>',
                query: query
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    var html = '';
                    response.data.forEach(function(formation) {
                        html += '<div class="formation-suggestion" data-id="' + formation.id + '" style="padding: 8px; cursor: pointer; border-bottom: 1px solid #eee;">' + formation.title + '</div>';
                    });
                    $('#formation-suggestions').html(html).show();
                } else {
                    $('#formation-suggestions').hide();
                }
            }
        });
    });
    
    // Sélection d'une formation
    $(document).on('click', '.formation-suggestion', function() {
        var id = $(this).data('id');
        var title = $(this).text();
        $('#formation-search').val(title);
        $('#formation-id').val(id);
        $('#formation-suggestions').hide();
    });
    
    // Masquer les suggestions si on clique ailleurs
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#formation-search, #formation-suggestions').length) {
            $('#formation-suggestions').hide();
        }
    });
    
    // Effacer la sélection si on modifie le texte
    $('#formation-search').on('input', function() {
        if ($(this).val() === '') {
            $('#formation-id').val('');
        }
    });
    
    $('#formation-search').on('keydown', function() {
        $('#formation-id').val('');
    });
    
    // Effacer la sélection de session si on modifie le texte
    $('#quick-action').on('input', function() {
        if ($(this).val() === '') {
            $(this).removeData('session-id');
        }
    });
    
    $('#quick-action').on('keydown', function() {
        $(this).removeData('session-id');
    });

    // Gestion de la case "Sélectionner tout"
    $('#select-all-plannings').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('input[name*="[delete]"]').prop('checked', isChecked);
    });

    // Mettre à jour la case "Sélectionner tout" si toutes les cases individuelles sont cochées/décochées
    $(document).on('change', 'input[name*="[delete]"]', function() {
        var totalCheckboxes = $('input[name*="[delete]"]').length;
        var checkedCheckboxes = $('input[name*="[delete]"]:checked').length;
        $('#select-all-plannings').prop('checked', totalCheckboxes === checkedCheckboxes);
    });

    // Chargement automatique des plannings en cours
    setTimeout(function() {
        $('#time-mode').val('current');
        loadPlannings();
    }, 500);
});
</script>

    <?php
}

/* ------------------------------------------------------------------------
 * 4. GESTIONNAIRE AJAX POUR LE CHARGEMENT DES PLANNINGS
 * ------------------------------------------------------------------------ */

add_action('wp_ajax_load_planning_data', 'handle_ajax_load_planning');
add_action('wp_ajax_search_formations', 'handle_ajax_search_formations');

function handle_ajax_search_formations() {
    if (!wp_verify_nonce($_POST['nonce'], 'planning_ajax_nonce')) {
        wp_send_json_error('Nonce invalide');
        return;
    }

    if (!user_has_required_role()) {
        wp_send_json_error('Permissions insuffisantes');
        return;
    }

    $query = sanitize_text_field($_POST['query'] ?? '');
    if (strlen($query) < 2) {
        wp_send_json_success([]);
        return;
    }

    $results = [];
    $added_ids = [];

    // Rechercher directement dans les actions de formation par le champ fsbdd_titreform
    $formations = get_posts([
        'post_type' => 'action-de-formation',
        'posts_per_page' => 30,
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'fsbdd_planning',
                'value' => '',
                'compare' => '!='
            ],
            [
                'key' => 'fsbdd_titreform',
                'value' => $query,
                'compare' => 'LIKE'
            ]
        ]
    ]);

    foreach ($formations as $formation) {
        if (!in_array($formation->ID, $added_ids)) {
            // Utiliser le champ fsbdd_titreform pour l'affichage
            $titre_form = get_post_meta($formation->ID, 'fsbdd_titreform', true);
            $display_title = $titre_form ? $titre_form : $formation->post_title;
            
            // Ajouter le numéro de session si disponible
            $numero_session = get_post_meta($formation->ID, 'fsbdd_inter_numero', true);
            if ($numero_session) {
                $display_title .= ' (Session ' . $numero_session . ')';
            }
            
            $results[] = [
                'id' => $formation->ID,
                'title' => $display_title
            ];
            $added_ids[] = $formation->ID;
        }
    }

    // Recherche secondaire dans le titre de l'action si pas assez de résultats
    if (count($results) < 10) {
        $formations_titre = get_posts([
            'post_type' => 'action-de-formation',
            'posts_per_page' => 20,
            'post_status' => 'publish',
            's' => $query,
            'meta_query' => [
                [
                    'key' => 'fsbdd_planning',
                    'value' => '',
                    'compare' => '!='
                ]
            ]
        ]);

        foreach ($formations_titre as $formation) {
            if (!in_array($formation->ID, $added_ids)) {
                // Utiliser le champ fsbdd_titreform pour l'affichage
                $titre_form = get_post_meta($formation->ID, 'fsbdd_titreform', true);
                $display_title = $titre_form ? $titre_form : $formation->post_title;
                
                // Ajouter le numéro de session si disponible
                $numero_session = get_post_meta($formation->ID, 'fsbdd_inter_numero', true);
                if ($numero_session) {
                    $display_title .= ' (Session ' . $numero_session . ')';
                }
                
                $results[] = [
                    'id' => $formation->ID,
                    'title' => $display_title
                ];
                $added_ids[] = $formation->ID;
            }
        }
    }

    // Limiter les résultats et trier par pertinence
    $results = array_slice($results, 0, 15);
    
    wp_send_json_success($results);
}

function handle_ajax_load_planning() {
    // Vérification de sécurité
    if (!wp_verify_nonce($_POST['nonce'], 'planning_ajax_nonce')) {
        wp_send_json_error('Nonce invalide');
        return;
    }

    if (!user_has_required_role()) {
        wp_send_json_error('Permissions insuffisantes');
        return;
    }

    // Add pagination parameters
    $page = intval($_POST['page'] ?? 1);
    $per_page = 50; // Limiter à 50 résultats par page

    // Récupération des paramètres
    $time_mode = sanitize_text_field($_POST['time_mode'] ?? 'current');
    $date_from = sanitize_text_field($_POST['date_from'] ?? '');
    $date_to = sanitize_text_field($_POST['date_to'] ?? '');
    $quick_type = sanitize_text_field($_POST['quick_type'] ?? '');
    $quick_etat = sanitize_text_field($_POST['quick_etat'] ?? '');
    $quick_action = intval($_POST['quick_action'] ?? 0);
    $session_search = sanitize_text_field($_POST['session_search'] ?? '');
    $specific_formateur = intval($_POST['specific_formateur'] ?? 0);
    $specific_lieu = sanitize_text_field($_POST['specific_lieu'] ?? '');
    $filter_urgent = intval($_POST['filter_urgent'] ?? 0);
    $formation_id = intval($_POST['formation_id'] ?? 0);
    $formation_search = sanitize_text_field($_POST['formation_search'] ?? '');
    


    try {
        // Get all plannings
        $all_plannings = get_plannings_by_criteria([
            'time_mode' => $time_mode,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'type' => $quick_type,
            'etat' => $quick_etat,
            'action_id' => $quick_action,
            'session_search' => $session_search,
            'specific_formateur' => $specific_formateur,
            'specific_lieu' => $specific_lieu,
            'filter_urgent' => $filter_urgent,
            'formation_id' => $formation_id,
            'formation_search' => $formation_search
        ]);

        $total_count = count($all_plannings);
        $total_pages = ceil($total_count / $per_page);
        
        // Paginate results
        $offset = ($page - 1) * $per_page;
        $plannings = array_slice($all_plannings, $offset, $per_page);

        if ($total_count > 0) {
            // Génération du HTML du tableau
            $table_html = generate_planning_table_html($plannings);
            
            // Génération du formulaire d'ajout
            $add_form_html = generate_add_form_html();

            wp_send_json_success([
                'count' => count($plannings),
                'total' => $total_count,
                'page' => $page,
                'has_more' => ($page * $per_page) < $total_count,
                'total_count' => $total_count,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'html' => $table_html,
                'add_form' => $add_form_html,
                'pagination' => generate_pagination_html($page, $total_pages)
            ]);
        } else {
            wp_send_json_success([
                'count' => 0,
                'total' => 0,
                'page' => $page,
                'has_more' => false,
                'total_count' => 0,
                'html' => '',
                'add_form' => ''
            ]);
        }

    } catch (Exception $e) {
        wp_send_json_error('Erreur lors du chargement : ' . $e->getMessage());
    }
}

/* ------------------------------------------------------------------------
 * 5. PAGINATION HTML GENERATOR
 * ------------------------------------------------------------------------ */

function generate_pagination_html($current_page, $total_pages) {
    if ($total_pages <= 1) return '';
    
    $html = '<div class="pagination-wrapper" style="text-align: center; margin: 20px 0;">';
    
    // Previous button
    if ($current_page > 1) {
        $html .= '<button type="button" class="button pagination-btn" data-page="' . ($current_page - 1) . '">« Précédent</button> ';
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $class = ($i == $current_page) ? 'button-primary' : 'button';
        $html .= '<button type="button" class="button ' . $class . ' pagination-btn" data-page="' . $i . '">' . $i . '</button> ';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $html .= '<button type="button" class="button pagination-btn" data-page="' . ($current_page + 1) . '">Suivant »</button>';
    }
    
    $html .= '</div>';
    return $html;
}

/* ------------------------------------------------------------------------
 * 6. RÉCUPÉRATION DES PLANNINGS SELON CRITÈRES
 * ------------------------------------------------------------------------ */

function get_plannings_by_criteria($criteria) {
    // Generate cache key based on criteria
    $cache_key = 'plannings_' . md5(serialize($criteria));
    $cached_result = get_transient($cache_key);
    
    if ($cached_result !== false) {
        return $cached_result;
    }
    
    $time_mode = $criteria['time_mode'];
    $date_from = $criteria['date_from'];
    $date_to = $criteria['date_to'];
    $type_filter = $criteria['type'];
    $etat_filter = $criteria['etat'];
    $action_id_filter = $criteria['action_id'];
    $session_search = $criteria['session_search'] ?? '';
    $specific_formateur = $criteria['specific_formateur'] ?? 0;
    $specific_lieu = $criteria['specific_lieu'] ?? '';
    $filter_urgent = $criteria['filter_urgent'] ?? 0;
    $formation_id = $criteria['formation_id'] ?? 0;
    $formation_search = $criteria['formation_search'] ?? '';
    
    // États considérés comme urgents
    $urgent_states = ['Option', 'Pré bloqué FS', 'Contrat envoyé'];

    // Détermination des dates selon le mode
    $today = new DateTime();
    
    switch ($time_mode) {
        case 'all':
            // Pas de restriction de date - récupérer tous les plannings
            $date_from = null;
            $date_to = null;
            break;
        case 'current':
            $start_date = (clone $today)->modify('-7 days');
            $end_date = (clone $today)->modify('+6 months');
            break;
        case 'past':
            $start_date = (clone $today)->modify('-6 months');
            $end_date = (clone $today)->modify('-1 day');
            break;
        case 'this_month':
            $start_date = new DateTime('first day of this month');
            $end_date = new DateTime('last day of this month');
            break;
        case 'next_month':
            $start_date = new DateTime('first day of next month');
            $end_date = new DateTime('last day of next month');
            break;
        case 'custom':
            if ($date_from && $date_to) {
                $start_date = DateTime::createFromFormat('d.m.Y', $date_from);
                $end_date = DateTime::createFromFormat('d.m.Y', $date_to);
                if (!$start_date || !$end_date) {
                    throw new Exception('Format de date invalide');
                }
            } else {
                $start_date = (clone $today)->modify('-1 month');
                $end_date = (clone $today)->modify('+1 month');
            }
            break;
        default:
            $start_date = (clone $today)->modify('-7 days');
            $end_date = (clone $today)->modify('+6 months');
    }

    // Construction de la requête optimisée
    $meta_query = [
        [
            'key' => 'fsbdd_planning',
            'value' => '',
            'compare' => '!='
        ]
    ];

    // Filtrer par action spécifique si demandé
    if ($action_id_filter) {
        $posts = [$action_id_filter];
    } else {
        $query_args = [
            'post_type' => 'action-de-formation',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => $meta_query,
            'fields' => 'ids'
        ];
        
        // Filtrer par formation si spécifiée (ID valide)
        if ($formation_id && $formation_id > 0) {
            $query_args['include'] = [$formation_id];
        }
        
        // Ajouter des filtres de recherche textuelle
        if ($session_search || $formation_search) {
            $search_meta_query = ['relation' => 'OR'];
            
            if ($session_search) {
                $search_meta_query[] = [
                    'key' => 'fsbdd_inter_numero',
                    'value' => $session_search,
                    'compare' => 'LIKE'
                ];
            }
            
            if ($formation_search) {
                $search_meta_query[] = [
                    'key' => 'fsbdd_titreform',
                    'value' => $formation_search,
                    'compare' => 'LIKE'
                ];
                // Recherche aussi dans le titre du post
                $query_args['s'] = $formation_search;
            }
            
            // Combiner avec la meta_query existante
            $query_args['meta_query'] = [
                'relation' => 'AND',
                $meta_query[0], // fsbdd_planning != ''
                $search_meta_query
            ];
        }
        
        $posts = get_posts($query_args);
    }

    if (empty($posts)) {
        return [];
    }

    // Traitement des plannings
    $plannings = [];
    
    // Pré-filtrage par lieu si nécessaire
    if ($specific_lieu) {
        $filtered_posts = [];
        foreach ($posts as $post_id) {
            $type_session = get_post_meta($post_id, 'fsbdd_typesession', true);
            $matches_lieu = false;
            
            if ($specific_lieu === 'INTER' && in_array($type_session, ['1', '2'])) {
                $matches_lieu = true;
            } elseif ($specific_lieu === 'INTRA' && $type_session === '3') {
                $matches_lieu = true;
            }
            
            if ($matches_lieu) {
                $filtered_posts[] = $post_id;
            }
        }
        $posts = $filtered_posts;
    }
    
    foreach ($posts as $post_id) {
        $plan_data = get_post_meta($post_id, 'fsbdd_planning', true);
        if (empty($plan_data) || !is_array($plan_data)) continue;

        foreach ($plan_data as $entry) {
            $date_str = $entry['fsbdd_planjour'] ?? '';
            $date_obj = parse_planning_date($date_str);
            if (!$date_obj) continue;

            // Filtrage par date (sauf pour le mode 'all')
            if ($time_mode !== 'all' && ($date_obj < $start_date || $date_obj > $end_date)) continue;

            $date_formatted = $date_obj->format('d.m.Y');

            // Traitement des formateurs
            if (!empty($entry['fsbdd_gpformatr'])) {
                foreach ($entry['fsbdd_gpformatr'] as $f) {
                    $nid = $f['fsbdd_user_formateurrel'] ?? 0;
                    $dispo = $f['fsbdd_dispjourform'] ?? '';
                    $etat = $f['fsbdd_okformatr'] ?? '';
                    
                    if ($nid) {
                        // Filtres
                        if ($type_filter && $type_filter !== 'formateur') continue;
                        if ($etat_filter && $etat_filter !== $etat) continue;
                        if ($specific_formateur && $specific_formateur != $nid) continue;
                        if ($filter_urgent && !in_array($etat, $urgent_states)) continue;
                        
                        $plannings[] = [
                            'action_id' => $post_id,
                            'date' => $date_formatted,
                            'nom' => $nid,
                            'type' => 'formateur',
                            'dispo' => $dispo,
                            'etat' => $etat,
                        ];
                    }
                }
            }
            
            // Traitement des fournisseurs
            if (!empty($entry['fournisseur_salle'])) {
                foreach ($entry['fournisseur_salle'] as $s) {
                    $nid = $s['fsbdd_user_foursalle'] ?? 0;
                    $dispo = $s['fsbdd_dispjourform'] ?? '';
                    $etat = $s['fsbdd_okformatr'] ?? '';
                    
                    if ($nid) {
                        // Filtres
                        if ($type_filter && $type_filter !== 'fournisseur') continue;
                        if ($etat_filter && $etat_filter !== $etat) continue;
                        if ($filter_urgent && !in_array($etat, $urgent_states)) continue;
                        
                        $plannings[] = [
                            'action_id' => $post_id,
                            'date' => $date_formatted,
                            'nom' => $nid,
                            'type' => 'fournisseur',
                            'dispo' => $dispo,
                            'etat' => $etat,
                        ];
                    }
                }
            }
        }
    }

    // Tri par date
    usort($plannings, function($a, $b) {
        $da = parse_planning_date($a['date']);
        $db = parse_planning_date($b['date']);
        if (!$da || !$db) return 0;
        return $da <=> $db;
    });

    // Cache the result for 5 minutes
    set_transient($cache_key, $plannings, 300);

    return $plannings;
}

/* ------------------------------------------------------------------------
 * 7. GÉNÉRATION DU HTML DU TABLEAU
 * ------------------------------------------------------------------------ */

function generate_planning_table_html($plannings) {
    if (empty($plannings)) {
        return '';
    }

    // Chargement optimisé des métadonnées des actions
    $action_ids = array_unique(array_column($plannings, 'action_id'));
    $actions_meta = [];
    
    if (!empty($action_ids)) {
        global $wpdb;
        $meta_results = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} 
             WHERE post_id IN (" . implode(',', array_fill(0, count($action_ids), '%d')) . ")
             AND meta_key IN ('fsbdd_select_lieusession', 'fsbdd_typesession', 'fsbdd_sessconfirm', 'fsbdd_titreform')",
            ...$action_ids
        ));
        
        // Organiser les métadonnées par post_id
        foreach ($meta_results as $meta) {
            $actions_meta[$meta->post_id][$meta->meta_key] = $meta->meta_value;
        }
    }

    ob_start();
    ?>
    <div class="planning-loaded">
        <div style="margin-bottom: 15px; background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
            <h3 style="margin: 0 0 10px 0; color: #0073aa;">
                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                <?php printf(__('%d plannings affichés', 'your-text-domain'), count($plannings)); ?>
            </h3>
            <p style="margin: 0; color: #666;">
                <?php _e('Vous pouvez maintenant modifier, supprimer ou ajouter des plannings.', 'your-text-domain'); ?>
            </p>
        </div>

        <form method="post" id="planning-main-form">
            <?php wp_nonce_field('planning_main_nonce', 'planning_main_nonce_field'); ?>
            
            <div style="max-height: 600px; overflow-y: auto; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px; background: #fff;">
                <table id="planning-table" style="width: 100%; border-spacing: 0; table-layout: fixed;">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="select-all-plannings" title="Sélectionner tout" /></th>
                            <th style="width: 80px;">Date</th>
                            <th style="width: 120px;">Nom</th>
                            <th style="width: 80px;">Type</th>
                            <th style="width: 70px;">Dispo</th>
                            <th style="width: 100px;">État</th>
                            <th style="width: 80px;">Action</th>
                            <th style="width: 200px;">Formation</th>
                            <th style="width: 120px;">Lieu</th>
                            <th style="width: 80px;">Type de session</th>
                            <th style="width: 60px;">Booké</th>
                            <th style="width: 120px;">Comment.</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $current_action = '';
                    foreach ($plannings as $index => $p):
                        $action_id = $p['action_id'];
                        $meta = isset($actions_meta[$action_id]) ? $actions_meta[$action_id] : [];
                        $formation = get_planning_formation($meta, $action_id);
                        $lieu = get_planning_lieu($meta);
                        $inter_intra = get_planning_inter_intra($meta);
                        $booke = get_planning_booke($meta);
                        $action_numero = get_planning_action($action_id);
                        $comm = ($p['type'] === 'fournisseur') ? get_commplanfourn($action_id, $p['date'], $p['nom']) : '';

                        // Séparateur d'action
                        if ($current_action !== $action_id) {
                            if ($current_action !== '') {
                                echo '<tr style="background:#e9ecee;"><td colspan="12" style="height: 2px; padding: 0;"></td></tr>';
                            }
                            $current_action = $action_id;
                        }
                        ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 5px; justify-content: center;">
                                    <input type="checkbox" name="plannings[<?php echo $index; ?>][delete]" value="1" title="Sélectionner pour suppression" />
                                    <?php if ($action_id): ?>
                                        <a href="<?php echo esc_url(get_edit_post_link($action_id)); ?>" target="_blank" 
                                           title="Éditer l'action">
                                            <span class="dashicons dashicons-admin-links" style="font-size: 16px; color: #0073aa;"></span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" name="plannings[<?php echo $index; ?>][edit]" value="1" />
                            </td>
                            <td>
                                <input type="text" name="plannings[<?php echo $index; ?>][date]" 
                                       value="<?php echo esc_attr($p['date']); ?>" readonly 
                                       style="background: #f9f9f9; font-size: 11px;" />
                                <input type="hidden" name="plannings[<?php echo $index; ?>][original_date]" 
                                       value="<?php echo esc_attr($p['date']); ?>" />
                                <input type="hidden" name="plannings[<?php echo $index; ?>][action_id]" 
                                       value="<?php echo esc_attr($action_id); ?>" />
                            </td>
                            <td>
                                <select name="plannings[<?php echo $index; ?>][nom]" style="font-size: 11px;">
                                    <?php echo get_nom_options($p['type'], $p['nom']); ?>
                                </select>
                                <input type="hidden" name="plannings[<?php echo $index; ?>][original_nom]" 
                                       value="<?php echo esc_attr($p['nom']); ?>" />
                            </td>
                            <td>
                                <input type="text" name="plannings[<?php echo $index; ?>][type]"
                                       value="<?php echo esc_attr($p['type']); ?>" readonly 
                                       style="background: #f9f9f9; font-size: 11px;" />
                                <input type="hidden" name="plannings[<?php echo $index; ?>][original_type]"
                                       value="<?php echo esc_attr($p['type']); ?>" />
                            </td>
                            <td>
                                <select name="plannings[<?php echo $index; ?>][dispo]" style="font-size: 11px;">
                                    <option value="Journ" <?php selected($p['dispo'], 'Journ'); ?>>Journ</option>
                                    <option value="Matin" <?php selected($p['dispo'], 'Matin'); ?>>Matin</option>
                                    <option value="Aprem" <?php selected($p['dispo'], 'Aprem'); ?>>Aprem</option>
                                </select>
                                <input type="hidden" name="plannings[<?php echo $index; ?>][original_dispo]" 
                                       value="<?php echo esc_attr($p['dispo']); ?>" />
                            </td>
                            <td>
                                <select name="plannings[<?php echo $index; ?>][etat]" style="font-size: 11px;">
                                    <?php foreach (get_etat_options() as $val => $label): ?>
                                        <option value="<?php echo esc_attr($val); ?>" <?php selected($p['etat'], $val); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="plannings[<?php echo $index; ?>][original_etat]" 
                                       value="<?php echo esc_attr($p['etat']); ?>" />
                            </td>
                            <td>
                                <?php echo esc_html($action_numero); ?>
                                <input type="hidden" name="plannings[<?php echo $index; ?>][original_action_id]"
                                       value="<?php echo esc_attr($action_id); ?>" />
                            </td>
                            <td style="font-size: 11px; text-align: left; padding-left: 5px;">
                                <?php echo esc_html($formation); ?>
                            </td>
                            <td style="font-size: 11px; text-align: left; padding-left: 5px;">
                                <?php echo esc_html($lieu); ?>
                            </td>
                            <td style="font-size: 11px;">
                                <?php echo esc_html($inter_intra); ?>
                            </td>
                            <td style="font-size: 11px;">
                                <?php echo esc_html($booke); ?>
                            </td>
                            <td>
                                <?php if ($p['type'] === 'fournisseur'): ?>
                                    <input type="text" name="plannings[<?php echo $index; ?>][commplanfourn]" 
                                           value="<?php echo esc_attr($comm); ?>" style="font-size: 11px;" />
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-bottom: 30px; text-align: center; background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
                <button type="button" class="button button-primary" id="confirm-edit-planning" style="margin-right: 10px;">
                    <span class="dashicons dashicons-yes" style="margin-right: 5px;"></span>
                    <?php _e('Enregistrer les modifications', 'your-text-domain'); ?>
                </button>
                <button type="submit" class="button button-secondary" name="action" value="delete_planning"
                        onclick="return confirm('<?php _e('Supprimer les lignes sélectionnées ?', 'your-text-domain'); ?>');">
                    <span class="dashicons dashicons-trash" style="margin-right: 5px;"></span>
                    <?php _e('Supprimer', 'your-text-domain'); ?>
                </button>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/* ------------------------------------------------------------------------
 * 8. GÉNÉRATION DU FORMULAIRE D'AJOUT
 * ------------------------------------------------------------------------ */

function generate_add_form_html() {
    ob_start();
    ?>
    <div style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-top: 15px;">
        <h3 style="background: #0073aa; color: #fff; padding: 8px 15px; border-radius: 3px; text-align: center; margin: -15px -15px 15px -15px; font-size: 14px;">
            <span class="dashicons dashicons-plus-alt" style="font-size: 16px;"></span>
            <?php _e('Ajouter au planning', 'your-text-domain'); ?>
        </h3>
        
        <form method="post" id="add-planning-form">
            <?php wp_nonce_field('planning_main_nonce', 'planning_main_nonce_field'); ?>
            
            <!-- Ligne principale avec tous les champs essentiels -->
            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: end; margin-bottom: 15px;">
                <div style="flex: 0 0 100px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 3px; font-size: 12px;">
                        <?php _e('Type', 'your-text-domain'); ?>
                    </label>
                    <select name="new_type" id="new_type" style="width: 100%; padding: 6px; font-size: 12px;">
                        <option value="formateur"><?php _e('Formateur', 'your-text-domain'); ?></option>
                        <option value="fournisseur"><?php _e('Fournisseur', 'your-text-domain'); ?></option>
                    </select>
                </div>

                <div style="flex: 1 1 150px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 3px; font-size: 12px;">
                        <?php _e('Nom', 'your-text-domain'); ?>
                    </label>
                    <select name="new_nom" id="new_nom" style="width: 100%; padding: 6px; font-size: 12px;">
                        <option value=""><?php _e('Sélectionner', 'your-text-domain'); ?></option>
                    </select>
                </div>

                <div style="flex: 0 0 90px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 3px; font-size: 12px;">
                        <?php _e('Dispo', 'your-text-domain'); ?>
                    </label>
                    <select name="new_dispo" style="width: 100%; padding: 6px; font-size: 12px;">
                        <option value="Journ"><?php _e('Journée', 'your-text-domain'); ?></option>
                        <option value="Matin"><?php _e('Matin', 'your-text-domain'); ?></option>
                        <option value="Aprem"><?php _e('Après-midi', 'your-text-domain'); ?></option>
                    </select>
                </div>

                <div style="flex: 1 1 120px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 3px; font-size: 12px;">
                        <?php _e('État', 'your-text-domain'); ?>
                    </label>
                    <select name="new_etat" style="width: 100%; padding: 6px; font-size: 12px;">
                        <?php foreach (get_etat_options() as $val => $label): ?>
                            <option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="flex: 0 0 120px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 3px; font-size: 12px;">
                        <?php _e('Mode', 'your-text-domain'); ?>
                    </label>
                    <select name="new_mode" id="new_mode" style="width: 100%; padding: 6px; font-size: 12px;">
                        <option value="dates"><?php _e('Dates', 'your-text-domain'); ?></option>
                        <option value="periode"><?php _e('Période', 'your-text-domain'); ?></option>
                        <option value="recurrence"><?php _e('Récurrence', 'your-text-domain'); ?></option>
                    </select>
                </div>

                <div id="dates_block" style="flex: 1 1 140px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 3px; font-size: 12px;">
                        <?php _e('Dates', 'your-text-domain'); ?>
                    </label>
                    <div id="dates_container">
                        <input type="text" name="new_dates[]" class="datepicker" 
                               placeholder="jj.mm.aaaa" style="width: 100%; padding: 6px; font-size: 12px;" />
                    </div>
                </div>

                <div id="periode_block" style="display: none; flex: 1 1 200px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 3px; font-size: 12px;">
                        <?php _e('Période', 'your-text-domain'); ?>
                    </label>
                    <div style="display: flex; gap: 5px;">
                        <input type="text" name="new_start_date" class="datepicker" 
                               placeholder="Du" style="width: 50%; padding: 6px; font-size: 12px;" />
                        <input type="text" name="new_end_date" class="datepicker" 
                               placeholder="Au" style="width: 50%; padding: 6px; font-size: 12px;" />
                    </div>
                </div>

                <div id="recurrence_block" style="display: none; flex: 1 1 400px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 5px; font-size: 12px;">
                        <?php _e('Récurrence', 'your-text-domain'); ?>
                    </label>
                    <div style="background: #f9f9f9; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                        <!-- Ligne 1: Jours de la semaine -->
                        <div style="margin-bottom: 8px;">
                            <div style="font-size: 11px; font-weight: 600; margin-bottom: 3px; color: #666;"><?php _e('Jours:', 'your-text-domain'); ?></div>
                            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                <div style="display: flex; gap: 6px;">
                                    <label style="font-size: 11px; display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="new_recurrence_days[]" value="1" style="margin-right: 3px;" />
                                        <?php _e('Lun', 'your-text-domain'); ?>
                                    </label>
                                    <label style="font-size: 11px; display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="new_recurrence_days[]" value="2" style="margin-right: 3px;" />
                                        <?php _e('Mar', 'your-text-domain'); ?>
                                    </label>
                                    <label style="font-size: 11px; display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="new_recurrence_days[]" value="3" style="margin-right: 3px;" />
                                        <?php _e('Mer', 'your-text-domain'); ?>
                                    </label>
                                    <label style="font-size: 11px; display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="new_recurrence_days[]" value="4" style="margin-right: 3px;" />
                                        <?php _e('Jeu', 'your-text-domain'); ?>
                                    </label>
                                    <label style="font-size: 11px; display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="new_recurrence_days[]" value="5" style="margin-right: 3px;" />
                                        <?php _e('Ven', 'your-text-domain'); ?>
                                    </label>
                                    <label style="font-size: 11px; display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="new_recurrence_days[]" value="6" style="margin-right: 3px;" />
                                        <?php _e('Sam', 'your-text-domain'); ?>
                                    </label>
                                    <label style="font-size: 11px; display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="new_recurrence_days[]" value="0" style="margin-right: 3px;" />
                                        <?php _e('Dim', 'your-text-domain'); ?>
                                    </label>
                                </div>
                                <div style="display: flex; gap: 3px; margin-left: 10px;">
                                    <button type="button" class="button-small" id="select_weekdays" style="font-size: 10px; padding: 2px 6px; background: #0073aa; color: white; border: none; border-radius: 3px;" title="Jours ouvrables">
                                        <?php _e('L-V', 'your-text-domain'); ?>
                                    </button>
                                    <button type="button" class="button-small" id="select_all_days" style="font-size: 10px; padding: 2px 6px; background: #00a32a; color: white; border: none; border-radius: 3px;" title="Tous les jours">
                                        <?php _e('Tous', 'your-text-domain'); ?>
                                    </button>
                                    <button type="button" class="button-small" id="clear_days" style="font-size: 10px; padding: 2px 6px; background: #d63638; color: white; border: none; border-radius: 3px;" title="Aucun">
                                        <?php _e('Aucun', 'your-text-domain'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ligne 2: Période et horaires -->
                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                            <div style="display: flex; gap: 5px; align-items: center;">
                                <span style="font-size: 11px; font-weight: 600; color: #666;"><?php _e('Du:', 'your-text-domain'); ?></span>
                                <input type="text" name="new_recurrence_start" class="datepicker" 
                                       placeholder="jj.mm.aaaa" style="width: 85px; padding: 4px; font-size: 11px; border: 1px solid #ccc; border-radius: 3px;" />
                            </div>
                            <div style="display: flex; gap: 5px; align-items: center;">
                                <span style="font-size: 11px; font-weight: 600; color: #666;"><?php _e('Au:', 'your-text-domain'); ?></span>
                                <input type="text" name="new_recurrence_end" class="datepicker" 
                                       placeholder="jj.mm.aaaa" style="width: 85px; padding: 4px; font-size: 11px; border: 1px solid #ccc; border-radius: 3px;" />
                            </div>
                            <!-- Horaires simples pour Matin/Après-midi -->
                            <div id="simple_time_block" style="display: flex; gap: 5px; align-items: center;">
                                <span style="font-size: 11px; font-weight: 600; color: #666;"><?php _e('De:', 'your-text-domain'); ?></span>
                                <input type="time" name="new_recurrence_time_start" id="new_recurrence_time_start"
                                       style="width: 85px; padding: 4px; font-size: 11px; border: 1px solid #ccc; border-radius: 3px;" value="08:00" />
                                <span style="font-size: 11px; color: #666;"><?php _e('à', 'your-text-domain'); ?></span>
                                <input type="time" name="new_recurrence_time_end" id="new_recurrence_time_end"
                                       style="width: 85px; padding: 4px; font-size: 11px; border: 1px solid #ccc; border-radius: 3px;" value="10:00" />
                            </div>
                            
                            <!-- Horaires détaillés pour Journée complète -->
                            <div id="detailed_time_block" style="display: none; flex-direction: column; gap: 5px;">
                                <div style="display: flex; gap: 5px; align-items: center;">
                                    <span style="font-size: 11px; font-weight: 600; color: #666; width: 40px;"><?php _e('Matin:', 'your-text-domain'); ?></span>
                                    <input type="time" name="new_recurrence_morning_start" id="new_recurrence_morning_start"
                                           style="width: 70px; padding: 4px; font-size: 11px; border: 1px solid #ccc; border-radius: 3px;" value="08:00" />
                                    <span style="font-size: 11px; color: #666;"><?php _e('à', 'your-text-domain'); ?></span>
                                    <input type="time" name="new_recurrence_morning_end" id="new_recurrence_morning_end"
                                           style="width: 70px; padding: 4px; font-size: 11px; border: 1px solid #ccc; border-radius: 3px;" value="12:00" />
                                </div>
                                <div style="display: flex; gap: 5px; align-items: center;">
                                    <span style="font-size: 11px; font-weight: 600; color: #666; width: 40px;"><?php _e('A-midi:', 'your-text-domain'); ?></span>
                                    <input type="time" name="new_recurrence_afternoon_start" id="new_recurrence_afternoon_start"
                                           style="width: 70px; padding: 4px; font-size: 11px; border: 1px solid #ccc; border-radius: 3px;" value="14:00" />
                                    <span style="font-size: 11px; color: #666;"><?php _e('à', 'your-text-domain'); ?></span>
                                    <input type="time" name="new_recurrence_afternoon_end" id="new_recurrence_afternoon_end"
                                           style="width: 70px; padding: 4px; font-size: 11px; border: 1px solid #ccc; border-radius: 3px;" value="18:00" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="flex: 1 1 120px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 3px; font-size: 12px;">
                        <?php _e('Session', 'your-text-domain'); ?>
                    </label>
                    <select name="new_action" style="width: 100%; padding: 6px; font-size: 12px;">
                        <option value=""><?php _e('Sélectionner', 'your-text-domain'); ?></option>
                        <?php
                        $actions = get_posts([
                            'post_type' => 'action-de-formation',
                            'numberposts' => -1,
                            'post_status' => 'publish',
                            'meta_query' => [
                                [
                                    'key' => 'fsbdd_inter_numero',
                                    'value' => '',
                                    'compare' => '!='
                                ],
                                [
                                    'key' => 'fsbdd_planning',
                                    'value' => '',
                                    'compare' => '!='
                                ]
                            ],
                            'meta_key' => 'fsbdd_inter_numero',
                            'orderby' => 'meta_value_num',
                            'order' => 'DESC',
                        ]);

                        // Ajouter la session de test en première position
                        $test_action = get_post(268081);
                        if ($test_action && $test_action->post_type === 'action-de-formation') {
                            $test_numero = get_post_meta(268081, 'fsbdd_inter_numero', true);
                            $test_titre = get_post_meta(268081, 'fsbdd_titreform', true);
                            $test_display = $test_numero ? $test_numero : 'TEST-' . $test_action->ID;
                            if ($test_titre) {
                                $test_display .= ' - ' . $test_titre;
                            }
                            $test_display .= ' (TEST)';
                            echo '<option value="268081">'.esc_html($test_display).'</option>';
                        }

                        // Filtrer la session de test des résultats normaux pour éviter les doublons
                        $filtered_actions = array_filter($actions, function($act) {
                            return $act->ID != 268081;
                        });

                        foreach ($filtered_actions as $act) {
                            $numero_inter = get_post_meta($act->ID, 'fsbdd_inter_numero', true);
                            $titre_form = get_post_meta($act->ID, 'fsbdd_titreform', true);
                            $planning_data = get_post_meta($act->ID, 'fsbdd_planning', true);
                            
                            $display_title = $numero_inter;
                            if ($titre_form) {
                                $display_title .= ' - ' . $titre_form;
                            }
                            
                            // Ajouter les informations d'horaires si disponibles
                            if (is_array($planning_data) && !empty($planning_data)) {
                                $first_planning = $planning_data[0];
                                $dates = array_column($planning_data, 'fsbdd_planjour');
                                if (!empty($dates)) {
                                    sort($dates);
                                    $start_date = reset($dates);
                                    $end_date = end($dates);
                                    
                                    // Récupérer les horaires du premier planning
                                    $time_start = isset($first_planning['fsbdd_plannmatin']) ? $first_planning['fsbdd_plannmatin'] : '';
                                    $time_end = isset($first_planning['fsbdd_plannmatinfin']) ? $first_planning['fsbdd_plannmatinfin'] : '';
                                    
                                    if ($start_date && $end_date && $time_start && $time_end) {
                                        $display_title .= ' (du ' . $start_date . ' au ' . $end_date . ' de ' . $time_start . ' à ' . $time_end . ')';
                                    }
                                }
                            }
                            
                            echo '<option value="'.esc_attr($act->ID).'">'.esc_html($display_title).'</option>';
                        }
                        ?>
                    </select>
                </div>

                <div style="flex: 0 0 35px;">
                    <button type="button" class="button button-small" id="add_more_dates" style="padding: 6px; font-size: 12px; background: #0073aa; color: white; border: none; border-radius: 3px;" title="Ajouter une date supplémentaire">
                        <span class="dashicons dashicons-plus-alt" style="font-size: 14px;"></span>
                    </button>
                </div>

                <div style="flex: 0 0 100px;">
                    <button type="submit" class="button button-primary" name="action" value="add_planning" 
                            style="padding: 6px 15px; font-size: 12px; width: 100%;">
                        <span class="dashicons dashicons-plus-alt" style="margin-right: 3px; font-size: 14px;"></span>
                        <?php _e('Ajouter', 'your-text-domain'); ?>
                    </button>
                </div>
            </div>

            <!-- Ligne pour le commentaire fournisseur (masquée par défaut) -->
            <div id="new_commplanfourn_block" style="display: none; margin-top: 10px;">
                <label style="font-weight: 600; display: block; margin-bottom: 3px; font-size: 12px;">
                    <?php _e('Commentaire Fournisseur', 'your-text-domain'); ?>
                </label>
                <input type="text" name="new_commplanfourn" style="width: 100%; padding: 6px; font-size: 12px;" 
                       placeholder="<?php _e('Commentaire optionnel...', 'your-text-domain'); ?>" />
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/* ------------------------------------------------------------------------
 * 9. FONCTIONS DE TRAITEMENT DES FORMULAIRES
 * ------------------------------------------------------------------------ */

function handle_planning_form_submission() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    if (!isset($_POST['action'])) return;
    if (!verify_nonce('planning_main_nonce_field', 'planning_main_nonce')) return;

    switch ($_POST['action']) {
        case 'edit_planning':
            if (!empty($_POST['plannings'])) {
                handle_edit_plannings($_POST['plannings']);
            }
            break;
        case 'delete_planning':
            if (!empty($_POST['plannings'])) {
                handle_delete_plannings($_POST['plannings']);
            }
            break;
        case 'add_planning':
            handle_add_planning($_POST);
            break;
    }
}

function display_planning_notices() {
    if (isset($_GET['updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Planning mis à jour.', 'your-text-domain') . '</p></div>';
    }
    if (isset($_GET['deleted'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Entrée(s) supprimée(s).', 'your-text-domain') . '</p></div>';
    }
    if (isset($_GET['added'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Nouvelle entrée ajoutée.', 'your-text-domain') . '</p></div>';
    }

    $errors = get_transient('planning_errors');
    if (!empty($errors)) {
        echo '<div class="notice notice-error is-dismissible">';
        foreach ($errors as $error) {
            echo '<p>' . esc_html($error) . '</p>';
        }
        echo '</div>';
        delete_transient('planning_errors');
    }

    $confirmations = get_transient('planning_confirmations');
    if (!empty($confirmations)) {
        echo '<div class="notice notice-success is-dismissible" id="planning-confirmations-notice">';
        foreach ($confirmations as $action_id => $messages) {
            $action_title = get_the_title($action_id);
            $base_edit_link = get_edit_post_link($action_id);
            $edit_link_with_auto = add_query_arg('auto_update', '1', $base_edit_link);

            if ($action_title && $base_edit_link) {
                echo '<p><strong><a href="' . esc_url($edit_link_with_auto) . '" target="_blank" data-url="' . esc_url($edit_link_with_auto) . '">' . esc_html($action_title) . '</a> :</strong></p>';
            } else {
                echo '<p><strong>' . esc_html(__('Action inconnue', 'your-text-domain')) . ':</strong></p>';
            }

            echo '<ul>';
            foreach ($messages as $msg) {
                echo '<li>' . esc_html($msg) . '</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function(){
            const notice = document.getElementById('planning-confirmations-notice');
            if (notice) {
                const links = notice.querySelectorAll('a[data-url]');
                links.forEach(link => {
                    const url = link.getAttribute('data-url');
                    if (url) {
                        window.open(url, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
                    }
                });
            }
        });
        </script>
        <?php
        delete_transient('planning_confirmations');
    }
}

/* ------------------------------------------------------------------------
 * 10. FONCTIONS DE GESTION DES PLANNINGS
 * ------------------------------------------------------------------------ */

function handle_edit_plannings($plannings) {
    $errors = [];
    $confirmations = [];

    foreach ($plannings as $p) {
        if (empty($p['edit']) || !empty($p['delete'])) continue;

        $req = ['original_action_id', 'original_nom', 'original_type', 'original_date', 'original_dispo', 'original_etat', 'date', 'nom', 'type', 'dispo', 'etat'];
        foreach ($req as $r) {
            if (!isset($p[$r])) continue 2;
        }

        $original_action_id = (int)$p['original_action_id'];
        $original_nom = (int)$p['original_nom'];
        $original_type = sanitize_text_field($p['original_type']);
        $original_date = sanitize_text_field($p['original_date']);
        $original_dispo = sanitize_text_field($p['original_dispo']);
        $original_etat = sanitize_text_field($p['original_etat']);

        $new_action_id = isset($p['new_action']) ? (int)$p['new_action'] : $original_action_id;
        $new_nom = (int)$p['nom'];
        $new_type = sanitize_text_field($p['type']);
        $new_date = sanitize_text_field($p['date']);
        $new_dispo = sanitize_text_field($p['dispo']);
        $new_etat = sanitize_text_field($p['etat']);
        $new_comm = $p['commplanfourn'] ?? '';

        if (has_planning_conflict($new_nom, $new_date, $new_dispo, $new_type, [
            'original_nom' => $original_nom,
            'original_date' => $original_date,
            'original_dispo' => $original_dispo
        ])) {
            $errors[] = sprintf(__('Conflit : %s est déjà réservé en %s le %s', 'your-text-domain'),
                get_the_title($new_nom), $new_dispo, $new_date);
            continue;
        }

        $nom_title = get_the_title($new_nom);
        $action_msg = ($new_action_id !== $original_action_id)
            ? sprintf(__('Déplacement vers action ID %d', 'your-text-domain'), $new_action_id)
            : sprintf(__('Mise à jour dans action ID %d', 'your-text-domain'), $original_action_id);

        $detail_msg = sprintf(__('%s (%s) - Date:%s, Dispo:%s, État:%s', 'your-text-domain'),
            $nom_title, $new_type, $new_date, $new_dispo, $new_etat
        );
        if ($new_comm) {
            $detail_msg .= ' / ' . sprintf(__('Commentaire: %s', 'your-text-domain'), $new_comm);
        }

        if ($new_action_id !== $original_action_id) {
            remove_planning_from_action($original_action_id, $original_type, $original_nom, $original_date);
            add_planning_to_action($new_action_id, $new_type, $new_nom, $new_date, $new_dispo, $new_etat, $new_comm);
            $confirmations[$new_action_id][] = $action_msg . ' - ' . $detail_msg;
        } else {
            update_planning_in_action($original_action_id, $original_type, $original_nom, $original_date,
                $new_nom, $new_type, $new_date, $new_dispo, $new_etat, $new_comm);
            $confirmations[$original_action_id][] = $action_msg . ' - ' . $detail_msg;
        }
    }

    if (!empty($errors)) {
        set_transient('planning_errors', $errors, 30);
    }
    if (!empty($confirmations)) {
        set_transient('planning_confirmations', $confirmations, 30);
    }
    $redirect_url = admin_url('admin.php?page=gestion-plannings&updated=1');
    wp_redirect($redirect_url);
    exit;
}

function handle_delete_plannings($plannings) {
    $confirmations = [];
    foreach ($plannings as $p) {
        if (empty($p['delete'])) continue;
        if (!isset($p['action_id'], $p['date'], $p['nom'], $p['type'])) continue;

        $action_id = (int)$p['action_id'];
        $date = sanitize_text_field($p['date']);
        $nom_id = (int)$p['nom'];
        $type = sanitize_text_field($p['type']);

        remove_planning_from_action($action_id, $type, $nom_id, $date);

        $confirmations[$action_id][] = sprintf(
            __('Suppression de %s (type:%s) le %s', 'your-text-domain'),
            get_the_title($nom_id), $type, $date
        );
    }
    if (!empty($confirmations)) {
        set_transient('planning_confirmations', $confirmations, 30);
    }
    $redirect_url = admin_url('admin.php?page=gestion-plannings&deleted=1');
    wp_redirect($redirect_url);
    exit;
}

function handle_add_planning($post_data) {
    $new_type = sanitize_text_field($post_data['new_type'] ?? '');
    $new_nom = (int)($post_data['new_nom'] ?? 0);
    $new_dispo = sanitize_text_field($post_data['new_dispo'] ?? '');
    $new_etat = sanitize_text_field($post_data['new_etat'] ?? '');
    $new_mode = sanitize_text_field($post_data['new_mode'] ?? 'dates');
    $new_action = (int)($post_data['new_action'] ?? 0);
    $new_comm = sanitize_text_field($post_data['new_commplanfourn'] ?? '');

    if (!$new_type || !$new_nom || !$new_dispo || !$new_etat || !$new_action) {
        $redirect_url = admin_url('admin.php?page=gestion-plannings');
        wp_redirect($redirect_url);
        exit;
    }

    $confirmations = [];
    $nom_title = get_the_title($new_nom);

    if ($new_mode === 'dates') {
        $dates = isset($post_data['new_dates']) ? (array)$post_data['new_dates'] : [];
        foreach ($dates as $d) {
            $d_clean = validate_and_format_date($d);
            if (!$d_clean) continue;

            if (has_planning_conflict($new_nom, $d_clean, $new_dispo, $new_type)) {
                set_transient('planning_errors', [sprintf(
                    __('Conflit : %s est déjà réservé en %s le %s', 'your-text-domain'),
                    $nom_title, $new_dispo, $d_clean
                )], 30);
                continue;
            }
            add_planning_to_action($new_action, $new_type, $new_nom, $d_clean, $new_dispo, $new_etat, $new_comm);
            $confirmations[$new_action][] = sprintf(
                __('Ajout %s (%s) le %s', 'your-text-domain'),
                $nom_title, $new_type, $d_clean
            );
        }
    } elseif ($new_mode === 'periode') {
        $start = validate_and_format_date($post_data['new_start_date'] ?? '');
        $end = validate_and_format_date($post_data['new_end_date'] ?? '');
        if (!$start || !$end) {
            $redirect_url = admin_url('admin.php?page=gestion-plannings');
            wp_redirect($redirect_url);
            exit;
        }
        
        $start_obj = DateTime::createFromFormat('d.m.Y', $start);
        $end_obj = DateTime::createFromFormat('d.m.Y', $end);
        
        if ($start_obj > $end_obj) {
            $redirect_url = admin_url('admin.php?page=gestion-plannings');
            wp_redirect($redirect_url);
            exit;
        }
        $interval = new DateInterval('P1D');
        $range = new DatePeriod($start_obj, $interval, $end_obj->modify('+1 day'));
        foreach ($range as $rd) {
            $d_clean = $rd->format('d.m.Y');
            if (has_planning_conflict($new_nom, $d_clean, $new_dispo, $new_type)) {
                set_transient('planning_errors', [sprintf(
                    __('Conflit : %s est déjà réservé en %s le %s', 'your-text-domain'),
                    $nom_title, $new_dispo, $d_clean
                )], 30);
                continue;
            }
            add_planning_to_action($new_action, $new_type, $new_nom, $d_clean, $new_dispo, $new_etat, $new_comm);
            $confirmations[$new_action][] = sprintf(
                __('Ajout %s (%s) le %s', 'your-text-domain'),
                $nom_title, $new_type, $d_clean
            );
        }
    } elseif ($new_mode === 'recurrence') {
        $recurrence_days = isset($post_data['new_recurrence_days']) ? (array)$post_data['new_recurrence_days'] : [];
        $start = validate_and_format_date($post_data['new_recurrence_start'] ?? '');
        $end = validate_and_format_date($post_data['new_recurrence_end'] ?? '');
        // Gestion des horaires selon la disponibilité
        if ($new_dispo === 'Journée') {
            // Pour journée complète, utiliser les horaires détaillés
            $morning_start = sanitize_text_field($post_data['new_recurrence_morning_start'] ?? '08:00');
            $morning_end = sanitize_text_field($post_data['new_recurrence_morning_end'] ?? '12:00');
            $afternoon_start = sanitize_text_field($post_data['new_recurrence_afternoon_start'] ?? '14:00');
            $afternoon_end = sanitize_text_field($post_data['new_recurrence_afternoon_end'] ?? '18:00');
            $time_start = $morning_start;
            $time_end = $afternoon_end;
        } else {
            // Pour matin ou après-midi, utiliser les horaires simples
            $time_start = sanitize_text_field($post_data['new_recurrence_time_start'] ?? '08:00');
            $time_end = sanitize_text_field($post_data['new_recurrence_time_end'] ?? '10:00');
            // Définir les horaires par défaut pour l'autre période
            if ($new_dispo === 'Matin') {
                $morning_start = $time_start;
                $morning_end = $time_end;
                $afternoon_start = '14:00'; // Horaire par défaut après-midi
                $afternoon_end = '18:00';   // Horaire par défaut après-midi
            } else { // Aprem
                $morning_start = '08:00';   // Horaire par défaut matin
                $morning_end = '12:00';     // Horaire par défaut matin
                $afternoon_start = $time_start;
                $afternoon_end = $time_end;
            }
        }
        
        if (!$start || !$end || empty($recurrence_days)) {
            $redirect_url = admin_url('admin.php?page=gestion-plannings');
            wp_redirect($redirect_url);
            exit;
        }
        
        $start_obj = DateTime::createFromFormat('d.m.Y', $start);
        $end_obj = DateTime::createFromFormat('d.m.Y', $end);
        
        if ($start_obj > $end_obj) {
            $redirect_url = admin_url('admin.php?page=gestion-plannings');
            wp_redirect($redirect_url);
            exit;
        }
        
        // Pour chaque jour de la semaine sélectionné
        foreach ($recurrence_days as $recurrence_day) {
            $recurrence_day = (int)$recurrence_day;
            
            // Trouver le premier jour de la semaine demandée dans la période
            $current = clone $start_obj;
            while ($current->format('w') != $recurrence_day && $current <= $end_obj) {
                $current->modify('+1 day');
            }
            
            // Ajouter tous les jours de cette semaine dans la période
            while ($current <= $end_obj) {
                $d_clean = $current->format('d.m.Y');
                if (has_planning_conflict($new_nom, $d_clean, $new_dispo, $new_type)) {
                    set_transient('planning_errors', [sprintf(
                        __('Conflit : %s est déjà réservé en %s le %s', 'your-text-domain'),
                        $nom_title, $new_dispo, $d_clean
                    )], 30);
                } else {
                    add_planning_to_action($new_action, $new_type, $new_nom, $d_clean, $new_dispo, $new_etat, $new_comm, $morning_start, $morning_end, $afternoon_start, $afternoon_end);
                    // Message de confirmation selon la disponibilité
                    if ($new_dispo === 'Journée') {
                        $confirmations[$new_action][] = sprintf(
                            __('Ajout %s (%s) le %s - Matin: %s à %s, Après-midi: %s à %s', 'your-text-domain'),
                            $nom_title, $new_type, $d_clean, $morning_start, $morning_end, $afternoon_start, $afternoon_end
                        );
                    } else {
                        $confirmations[$new_action][] = sprintf(
                            __('Ajout %s (%s) le %s de %s à %s', 'your-text-domain'),
                            $nom_title, $new_type, $d_clean, $time_start, $time_end
                        );
                    }
                }
                $current->modify('+7 days'); // Passer au même jour de la semaine suivante
            }
        }
    }

    if (!empty($confirmations)) {
        set_transient('planning_confirmations', $confirmations, 30);
    }
    $redirect_url = admin_url('admin.php?page=gestion-plannings&added=1');
    wp_redirect($redirect_url);
    exit;
}

/* ------------------------------------------------------------------------
 * 11. FONCTIONS UTILITAIRES
 * ------------------------------------------------------------------------ */

function parse_planning_date($date) {
    static $date_cache = [];
    if (isset($date_cache[$date])) {
        return $date_cache[$date];
    }
    
    $d = DateTime::createFromFormat('d.m.Y', $date) ?: DateTime::createFromFormat('d.m.y', $date);
    $date_cache[$date] = $d ?: null;
    return $date_cache[$date];
}

function validate_and_format_date($date) {
    $o = parse_planning_date($date);
    return $o ? $o->format('d.m.Y') : false;
}

function has_planning_conflict($nom_id, $date, $dispo, $type, $current_data = null) {
    // Exclure les modèles des conflits
    $excluded_formateur_ids = [271865]; // Formateur modèle
    $excluded_action_ids = [268081]; // Action modèle
    
    if (in_array($nom_id, $excluded_formateur_ids)) {
        return false;
    }
    
    static $cache = [];
    $key = md5("$nom_id-$date-$type");
    if (isset($cache[$key])) return $cache[$key];

    $d = parse_planning_date($date);
    if (!$d) return false;

    $args = [
        'post_type' => 'action-de-formation',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'post__not_in' => $excluded_action_ids, // Exclure les actions modèles
        'meta_query' => [['key' => 'fsbdd_planning', 'value' => $date, 'compare' => 'LIKE']]
    ];
    $ids = get_posts($args);
    $ret = false;

    foreach ($ids as $aid) {
        $pl = get_post_meta($aid, 'fsbdd_planning', true);
        if (empty($pl)) continue;
        foreach ($pl as $entry) {
            if (($entry['fsbdd_planjour'] ?? '') !== $date) continue;
            $group = ($type === 'formateur') ? ($entry['fsbdd_gpformatr'] ?? []) : ($entry['fournisseur_salle'] ?? []);
            foreach ($group as $g) {
                $gid = ($g['fsbdd_user_formateurrel'] ?? ($g['fsbdd_user_foursalle'] ?? 0));
                $gd = ($g['fsbdd_dispjourform'] ?? '');
                
                // Exclure les formateurs modèles des conflits
                if (in_array($gid, $excluded_formateur_ids)) {
                    continue;
                }
                
                if ($current_data && $current_data['original_nom'] == $gid && $current_data['original_date'] === $date && $current_data['original_dispo'] === $gd) {
                    continue;
                }
                if ($gid == $nom_id && is_conflict($dispo, $gd)) {
                    $ret = true;
                    break 3;
                }
            }
        }
    }
    $cache[$key] = $ret;
    return $ret;
}

function is_conflict($new, $old) {
    $matrix = [
        'Journ' => ['Journ', 'Matin', 'Aprem'],
        'Matin' => ['Journ', 'Matin'],
        'Aprem' => ['Journ', 'Aprem'],
    ];
    return in_array($new, $matrix[$old] ?? []);
}

function add_planning_to_action($action_id, $type, $nom_id, $date, $dispo, $etat, $commplan = '', $morning_start = null, $morning_end = null, $afternoon_start = null, $afternoon_end = null) {
    $pls = get_post_meta($action_id, 'fsbdd_planning', true);
    if (!is_array($pls)) $pls = [];
    $found = false;
    foreach ($pls as &$ent) {
        if (($ent['fsbdd_planjour'] ?? '') === $date) {
            if ($type === 'formateur') {
                $ent['fsbdd_gpformatr'][] = [
                    'fsbdd_user_formateurrel' => $nom_id,
                    'fsbdd_dispjourform' => $dispo,
                    'fsbdd_okformatr' => $etat
                ];
            } else {
                $ent['fournisseur_salle'][] = [
                    'fsbdd_user_foursalle' => $nom_id,
                    'fsbdd_dispjourform' => $dispo,
                    'fsbdd_okformatr' => $etat,
                    'fsbdd_commplanfourn' => $commplan
                ];
            }
            $found = true;
            break;
        }
    }
    if (!$found) {
        // Utiliser les heures personnalisées si fournies, sinon les valeurs par défaut
        $matin_start = $morning_start ? $morning_start : '08:30';
        $matin_end = $morning_end ? $morning_end : '12:00';
        $am_start = $afternoon_start ? $afternoon_start : '13:30';
        $am_end = $afternoon_end ? $afternoon_end : '17:00';
        
        $new = [
            'fsbdd_planjour' => $date,
            'fsbdd_plannmatin' => $matin_start,
            'fsbdd_plannmatinfin' => $matin_end,
            'fsbdd_plannam' => $am_start,
            'fsbdd_plannamfin' => $am_end,
            'fsbdd_gpformatr' => [],
            'fournisseur_salle' => [],
        ];
        if ($type === 'formateur') {
            $new['fsbdd_gpformatr'][] = [
                'fsbdd_user_formateurrel' => $nom_id,
                'fsbdd_dispjourform' => $dispo,
                'fsbdd_okformatr' => $etat
            ];
        } else {
            $new['fournisseur_salle'][] = [
                'fsbdd_user_foursalle' => $nom_id,
                'fsbdd_dispjourform' => $dispo,
                'fsbdd_okformatr' => $etat,
                'fsbdd_commplanfourn' => $commplan
            ];
        }
        $pls[] = $new;
    }
    update_post_meta($action_id, 'fsbdd_planning', $pls);
    return true;
}

function remove_planning_from_action($action_id, $type, $nom_id, $date) {
    $pls = get_post_meta($action_id, 'fsbdd_planning', true);
    if (!is_array($pls)) return false;
    foreach ($pls as $i => &$ent) {
        if (($ent['fsbdd_planjour'] ?? '') === $date) {
            if ($type === 'formateur' && !empty($ent['fsbdd_gpformatr'])) {
                foreach ($ent['fsbdd_gpformatr'] as $fi => $f) {
                    if (($f['fsbdd_user_formateurrel'] ?? 0) == $nom_id) {
                        unset($ent['fsbdd_gpformatr'][$fi]);
                        if (empty($ent['fsbdd_gpformatr'])) unset($ent['fsbdd_gpformatr']);
                        if (empty($ent['fsbdd_gpformatr']) && empty($ent['fournisseur_salle'])) unset($pls[$i]);
                        update_post_meta($action_id, 'fsbdd_planning', $pls);
                        return true;
                    }
                }
            } elseif ($type === 'fournisseur' && !empty($ent['fournisseur_salle'])) {
                foreach ($ent['fournisseur_salle'] as $si => $s) {
                    if (($s['fsbdd_user_foursalle'] ?? 0) == $nom_id) {
                        unset($ent['fournisseur_salle'][$si]);
                        if (empty($ent['fournisseur_salle'])) unset($ent['fournisseur_salle']);
                        if (empty($ent['fsbdd_gpformatr']) && empty($ent['fournisseur_salle'])) unset($pls[$i]);
                        update_post_meta($action_id, 'fsbdd_planning', $pls);
                        return true;
                    }
                }
            }
        }
    }
    return false;
}

function update_planning_in_action($aid, $orig_type, $orig_nom, $orig_date, $new_nom, $new_type, $new_date, $new_dispo, $new_etat, $new_comm) {
    $pls = get_post_meta($aid, 'fsbdd_planning', true);
    if (!is_array($pls)) return false;
    foreach ($pls as &$ent) {
        if (($ent['fsbdd_planjour'] ?? '') === $orig_date) {
            if ($orig_type === 'formateur' && !empty($ent['fsbdd_gpformatr'])) {
                foreach ($ent['fsbdd_gpformatr'] as &$f) {
                    if (($f['fsbdd_user_formateurrel'] ?? 0) == $orig_nom) {
                        $f['fsbdd_user_formateurrel'] = $new_nom;
                        $f['fsbdd_dispjourform'] = $new_dispo;
                        $f['fsbdd_okformatr'] = $new_etat;
                        if ($new_date !== $orig_date) $ent['fsbdd_planjour'] = $new_date;
                        update_post_meta($aid, 'fsbdd_planning', $pls);
                        return true;
                    }
                }
            } elseif ($orig_type === 'fournisseur' && !empty($ent['fournisseur_salle'])) {
                foreach ($ent['fournisseur_salle'] as &$s) {
                    if (($s['fsbdd_user_foursalle'] ?? 0) == $orig_nom) {
                        $s['fsbdd_user_foursalle'] = $new_nom;
                        $s['fsbdd_dispjourform'] = $new_dispo;
                        $s['fsbdd_okformatr'] = $new_etat;
                        $s['fsbdd_commplanfourn'] = $new_comm;
                        if ($new_date !== $orig_date) $ent['fsbdd_planjour'] = $new_date;
                        update_post_meta($aid, 'fsbdd_planning', $pls);
                        return true;
                    }
                }
            }
        }
    }
    return false;
}

/* ------------------------------------------------------------------------
 * 12. FONCTIONS D'AFFICHAGE UTILITAIRES
 * ------------------------------------------------------------------------ */

function get_planning_formation($meta, $action_id = null) {
    // Utiliser l'action_id principal si fourni
    if ($action_id) {
        // Récupérer directement le champ fsbdd_titreform de l'action principale
        $titre_form = get_post_meta($action_id, 'fsbdd_titreform', true);
        if ($titre_form) {
            return mb_substr($titre_form, 0, 40);
        }
        
        // Fallback sur le titre de l'action si fsbdd_titreform n'existe pas
        $action = get_post($action_id);
        if ($action) {
            return mb_substr($action->post_title, 0, 40);
        }
    }
    
    return '';
}

function get_planning_lieu($meta) {
    $lieu = $meta['fsbdd_select_lieusession'] ?? '';
    return $lieu ? strtok($lieu, ',') : '';
}

function get_planning_inter_intra($meta) {
    switch ($meta['fsbdd_typesession'] ?? '') {
        case '1': return 'INTER';
        case '2': return 'INTER à définir';
        case '3': return 'INTRA';
    }
    return 'Inconnu';
}

function get_planning_booke($meta) {
    switch ($meta['fsbdd_sessconfirm'] ?? '') {
        case '1': return 'TODO';
        case '2': return 'NON';
        case '3': return 'OUI';
        case '4': return 'BOOKÉ';
    }
    return 'Inconnu';
}

function get_planning_action($action_id) {
    if (!$action_id) return '';
    
    $numero_inter = get_post_meta($action_id, 'fsbdd_inter_numero', true);
    return $numero_inter ? $numero_inter : '';
}

function get_commplanfourn($action_id, $date, $nom_id) {
    $pls = get_post_meta($action_id, 'fsbdd_planning', true);
    if (!is_array($pls)) return '';
    
    foreach ($pls as $ent) {
        if (($ent['fsbdd_planjour'] ?? '') === $date && !empty($ent['fournisseur_salle'])) {
            foreach ($ent['fournisseur_salle'] as $s) {
                if (($s['fsbdd_user_foursalle'] ?? 0) == $nom_id) {
                    return sanitize_text_field($s['fsbdd_commplanfourn'] ?? '');
                }
            }
        }
    }
    return '';
}

function get_etat_options() {
    return [
        'Date libérée' => __('Date libérée', 'your-text-domain'),
        'Option' => __('Option', 'your-text-domain'),
        'Pré bloqué FS' => __('Pré bloqué FS', 'your-text-domain'),
        'Réservé' => __('Réservé', 'your-text-domain'),
        'Contrat envoyé' => __('Contrat envoyé', 'your-text-domain'),
        'Contrat reçu' => __('Contrat reçu', 'your-text-domain'),
        'Emargement OK' => __('Emargement OK', 'your-text-domain'),
    ];
}

function get_nom_options($type, $selected_id = '') {
    $html = '<option value="">' . __('Sélectionner', 'your-text-domain') . '</option>';
    
    if ($type === 'formateur') {
        $formateurs = get_posts([
            'post_type' => 'formateur',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        foreach ($formateurs as $f) {
            $sel = ($f->ID == $selected_id) ? 'selected' : '';
            $html .= '<option value="' . $f->ID . '" ' . $sel . '>' . esc_html($f->post_title) . '</option>';
        }
    } else {
        $salles = get_posts([
            'post_type' => 'salle-de-formation',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        foreach ($salles as $s) {
            $sel = ($s->ID == $selected_id) ? 'selected' : '';
            $html .= '<option value="' . $s->ID . '" ' . $sel . '>' . esc_html($s->post_title) . '</option>';
        }
    }
    return $html;
}

// AJAX handler pour la recherche de sessions
add_action('wp_ajax_search_sessions', 'handle_search_sessions');
function handle_search_sessions() {
    // Vérification de sécurité
    if (!wp_verify_nonce($_POST['nonce'], 'planning_ajax_nonce')) {
        wp_send_json_error('Nonce invalide');
        return;
    }
    
    if (!user_has_required_role()) {
        wp_send_json_error('Permissions insuffisantes');
        return;
    }
    
    $query = sanitize_text_field($_POST['query'] ?? '');
    
    if (strlen($query) < 1) {
        wp_send_json_success([]);
        return;
    }
    
    // Rechercher dans les actions de formation par le champ fsbdd_inter_numero
    $actions = get_posts([
        'post_type' => 'action-de-formation',
        'numberposts' => 30,
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'fsbdd_inter_numero',
                'value' => $query,
                'compare' => 'LIKE'
            ],
            [
                'key' => 'fsbdd_planning',
                'value' => '',
                'compare' => '!='
            ]
        ],
        'orderby' => 'meta_value_num',
        'meta_key' => 'fsbdd_inter_numero',
        'order' => 'DESC'
    ]);
    
    $results = [];
    foreach ($actions as $action) {
        $numero = get_post_meta($action->ID, 'fsbdd_inter_numero', true);
        if ($numero && stripos($numero, $query) !== false) {
            // Ajouter le titre de formation pour plus de contexte
            $titre_form = get_post_meta($action->ID, 'fsbdd_titreform', true);
            $display_numero = $numero;
            if ($titre_form) {
                $display_numero .= ' - ' . mb_substr($titre_form, 0, 30);
            }
            
            $results[] = [
                'id' => $action->ID,
                'numero' => $numero,
                'display' => $display_numero
            ];
        }
    }
    
    wp_send_json_success($results);
}