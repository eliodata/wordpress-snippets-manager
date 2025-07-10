<?php
/**
 * Snippet ID: 187
 * Name: Statut BOOKING auto selon etat réservation formateur fournisseur bloquer planning
 * Description: 
 * @active true
 */


/**
 * Plugin Name: Gestion automatique du statut booking
 * Description: Met à jour automatiquement le statut booking selon l'état des réservations du planning
 * Version: 1.0
 * Author: Votre Nom
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vérifie si tous les éléments du planning sont réservés
 * 
 * @param int $action_id L'ID de l'action de formation
 * @return bool True si tout est réservé, false sinon
 * 
 * FONCTION COMMENTÉE - Plus utilisée pour la mise à jour automatique
 */
/*
function check_planning_all_reserved($action_id) {
    // Récupérer le planning - le nom correct est fsbdd_planning
    $planning = get_post_meta($action_id, 'fsbdd_planning', true);
    
    // Si pas de planning, on ne peut pas vérifier
    if (empty($planning) || !is_array($planning)) {
        return false;
    }
    
    $has_planning_items = false;
    $all_reserved = true;
    
    foreach ($planning as $date_planning) {
        // Vérifier qu'il y a au moins un élément dans le planning
        if (!empty($date_planning)) {
            $has_planning_items = true;
        }
        
        // Vérifier les formateurs (groupe fsbdd_gpformatr)
        if (!empty($date_planning['fsbdd_gpformatr']) && is_array($date_planning['fsbdd_gpformatr'])) {
            foreach ($date_planning['fsbdd_gpformatr'] as $formateur) {
                // L'état est dans le champ fsbdd_okformatr et doit être "Réservé"
                if (empty($formateur['fsbdd_okformatr']) || $formateur['fsbdd_okformatr'] !== 'Réservé') {
                    $all_reserved = false;
                    break 2; // Sortir des deux boucles
                }
            }
        }
        
        // Vérifier les fournisseurs/salles (groupe fournisseur_salle)
        if (!empty($date_planning['fournisseur_salle']) && is_array($date_planning['fournisseur_salle'])) {
            foreach ($date_planning['fournisseur_salle'] as $fournisseur) {
                // L'état est dans le champ fsbdd_okformatr et doit être "Réservé"
                if (empty($fournisseur['fsbdd_okformatr']) || $fournisseur['fsbdd_okformatr'] !== 'Réservé') {
                    $all_reserved = false;
                    break 2; // Sortir des deux boucles
                }
            }
        }
    }
    
    // Retourner true seulement si on a des éléments et qu'ils sont tous réservés
    return $has_planning_items && $all_reserved;
}
*/

/**
 * Met à jour automatiquement le statut booking lors de la sauvegarde
 * 
 * FONCTION COMMENTÉE - Plus de mise à jour automatique des statuts
 */
/*
add_action('save_post_action-de-formation', 'update_statut_booking_automatically', 20);
function update_statut_booking_automatically($post_id) {
    // Éviter les sauvegardes automatiques et les révisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (wp_is_post_revision($post_id)) {
        return;
    }
    
    // Vérifier les permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Éviter les boucles infinies
    remove_action('save_post_action-de-formation', 'update_statut_booking_automatically', 20);
    
    // Vérifier si tout est réservé
    $all_reserved = check_planning_all_reserved($post_id);
    
    // Récupérer le statut actuel
    $current_status = get_post_meta($post_id, 'fsbdd_sessconfirm', true);
    
    // Ne pas toucher au statut "BOOKÉ" (4)
    if ($current_status == 4) {
        add_action('save_post_action-de-formation', 'update_statut_booking_automatically', 20);
        return;
    }
    
    // Si tout est réservé et le statut n'est pas déjà "OUI"
    if ($all_reserved && $current_status != 3) {
        // Mettre à jour vers "OUI" (3)
        update_post_meta($post_id, 'fsbdd_sessconfirm', 3);
    }
    // Si pas tout réservé et le statut n'est pas déjà "NON"
    elseif (!$all_reserved && $current_status != 2) {
        // Mettre à jour vers "NON" (2)
        update_post_meta($post_id, 'fsbdd_sessconfirm', 2);
    }
    
    // Remettre l'action
    add_action('save_post_action-de-formation', 'update_statut_booking_automatically', 20);
}
*/

/**
 * Script pour rendre le planning read-only si le statut est "BOOKÉ"
 * VERSION CORRIGÉE - Les champs restent actifs pour la sauvegarde mais sont verrouillés visuellement
 */
add_action('admin_footer', 'planning_readonly_when_booked');
function planning_readonly_when_booked() {
    global $post, $pagenow;
    
    // Seulement sur la page d'édition d'une action de formation
    if ($pagenow !== 'post.php' || !$post || $post->post_type !== 'action-de-formation') {
        return;
    }
    
    $statut_booking = get_post_meta($post->ID, 'fsbdd_sessconfirm', true);
    
    // Si le statut est "BOOKÉ" (4), rendre le planning read-only
    if ($statut_booking == 4) {
        ?>
        <script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                var isLocked = true;
                
                // Fonction pour verrouiller les champs du planning
                function lockPlanningFields() {
                    // Cibler spécifiquement les champs du planning
                    var planningSelectors = [
                        '[name*="fsbdd_planning"]',
                        '[name*="fsbdd_grpplanng"]',
                        '[id*="fsbdd_planning"]',
                        '[id*="fsbdd_grpplanng"]',
                        '.rwmb-tab-panel-planning',
                        '#tab-planning'
                    ];
                    
                    var selector = planningSelectors.join(', ');
                    
                    // Styliser les champs pour qu'ils paraissent désactivés
                    $(selector).find('input, select, textarea').each(function() {
                        var $field = $(this);
                        
                        // Ajouter une classe pour identifier les champs verrouillés
                        $field.addClass('planning-locked');
                        
                        // Styliser comme désactivé
                        $field.css({
                            'background-color': '#f0f0f0',
                            'cursor': 'not-allowed',
                            'opacity': '0.7',
                            'color': '#666'
                        });
                        
                        // Empêcher les modifications via des événements
                        $field.on('keydown keyup keypress change input paste', function(e) {
                            if (isLocked) {
                                e.preventDefault();
                                e.stopPropagation();
                                return false;
                            }
                        });
                        
                        // Empêcher le focus
                        $field.on('focus', function() {
                            if (isLocked) {
                                $(this).blur();
                            }
                        });
                        
                        // Pour les selects, empêcher l'ouverture
                        if ($field.is('select')) {
                            $field.on('mousedown', function(e) {
                                if (isLocked) {
                                    e.preventDefault();
                                    return false;
                                }
                            });
                        }
                    });
                    
                    // Cacher les boutons d'ajout/suppression
                    $(selector).find('.rwmb-button, .add-clone, .remove-clone, .rwmb-group-add, .rwmb-group-remove').each(function() {
                        $(this).hide().addClass('planning-button-hidden');
                    });
                    
                    // Ajouter un overlay transparent pour empêcher les clics
                    $(selector).each(function() {
                        if (!$(this).find('.planning-lock-overlay').length) {
                            $(this).css('position', 'relative').append(
                                '<div class="planning-lock-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 9999; background: transparent; cursor: not-allowed;"></div>'
                            );
                        }
                    });
                }
                
                // Fonction pour déverrouiller temporairement (pour la sauvegarde)
                function unlockPlanningFields() {
                    var planningSelectors = [
                        '[name*="fsbdd_planning"]',
                        '[name*="fsbdd_grpplanng"]',
                        '[id*="fsbdd_planning"]',
                        '[id*="fsbdd_grpplanng"]'
                    ];
                    
                    var selector = planningSelectors.join(', ');
                    
                    // Retirer temporairement les restrictions
                    $(selector).find('input, select, textarea').each(function() {
                        $(this).off('keydown keyup keypress change input paste focus mousedown');
                    });
                    
                    // Retirer l'overlay
                    $('.planning-lock-overlay').remove();
                }
                
                // Appliquer le verrouillage
                lockPlanningFields();
                
                // Réappliquer après un délai pour s'assurer que MetaBox a fini de charger
                setTimeout(lockPlanningFields, 1000);
                
                // Déverrouiller temporairement avant la sauvegarde
                $('form#post').on('submit', function() {
                    isLocked = false;
                    unlockPlanningFields();
                });
                
                // Intercepter les boutons de sauvegarde
                $('#publish, #save-post, input[name="save"]').on('click', function() {
                    isLocked = false;
                    unlockPlanningFields();
                });
                
                // Ajouter le message d'avertissement
                var warningHtml = '<div class="notice notice-warning inline planning-warning" style="margin: 10px 0; padding: 10px;">' +
                    '<p><strong>🔒 Planning verrouillé :</strong> Le statut booking est "BOOKÉ". ' +
                    'Le planning ne peut plus être modifié. Pour débloquer, changez d\'abord le statut booking.</p>' +
                    '</div>';
                
                // Essayer plusieurs emplacements possibles
                if ($('#tab-planning').length && !$('#tab-planning .planning-warning').length) {
                    $('#tab-planning').prepend(warningHtml);
                } else if ($('.rwmb-tab-panel-planning').length && !$('.rwmb-tab-panel-planning .planning-warning').length) {
                    $('.rwmb-tab-panel-planning').prepend(warningHtml);
                } else {
                    // Chercher la metabox planning par son titre
                    $('.postbox').each(function() {
                        var title = $(this).find('h2').text();
                        if (title && title.toLowerCase().indexOf('planning') !== -1 && !$(this).find('.planning-warning').length) {
                            $(this).find('.inside').prepend(warningHtml);
                            return false;
                        }
                    });
                }
                
                // Surveiller les changements de statut booking pour déverrouiller si nécessaire
                $('select[name*="fsbdd_sessconfirm"], input[name*="fsbdd_sessconfirm"]').on('change', function() {
                    var newStatus = $(this).val();
                    if (newStatus != 4) { // Si le statut n'est plus BOOKÉ
                        // Déverrouiller complètement
                        isLocked = false;
                        unlockPlanningFields();
                        
                        // Retirer le message d'avertissement
                        $('.planning-warning').remove();
                        
                        // Restaurer l'apparence normale
                        $('.planning-locked').removeClass('planning-locked').css({
                            'background-color': '',
                            'cursor': '',
                            'opacity': '',
                            'color': ''
                        });
                        
                        // Réafficher les boutons
                        $('.planning-button-hidden').show().removeClass('planning-button-hidden');
                    }
                });
            });
        })(jQuery);
        </script>
        <style>
        .planning-lock-overlay {
            pointer-events: all !important;
        }
        .planning-locked:focus {
            outline: none !important;
            box-shadow: none !important;
        }
        </style>
        <?php
    }
}

/**
 * Fonction utilitaire pour forcer la mise à jour du statut booking sur toutes les actions
 * 
 * FONCTION COMMENTÉE - Plus de mise à jour automatique des statuts
 */
/*
function update_all_booking_statuses() {
    $actions = get_posts([
        'post_type' => 'action-de-formation',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);
    
    $updated = 0;
    foreach ($actions as $action) {
        $all_reserved = check_planning_all_reserved($action->ID);
        $current_status = get_post_meta($action->ID, 'fsbdd_sessconfirm', true);
        
        // Ne pas toucher au statut "BOOKÉ" (4)
        if ($current_status == 4) {
            continue;
        }
        
        if ($all_reserved && $current_status != 3) {
            update_post_meta($action->ID, 'fsbdd_sessconfirm', 3);
            $updated++;
        } elseif (!$all_reserved && $current_status != 2) {
            update_post_meta($action->ID, 'fsbdd_sessconfirm', 2);
            $updated++;
        }
    }
    
    return $updated;
}
*/

/**
 * Ajouter les pages d'administration
 * 
 * FONCTION COMMENTÉE - Plus besoin des pages d'admin pour la mise à jour automatique
 */
/*
add_action('admin_menu', 'add_booking_admin_pages');
function add_booking_admin_pages() {
    // Page de mise à jour des statuts
    add_submenu_page(
        'tools.php',
        'Mise à jour Statuts Booking',
        'Statuts Booking',
        'manage_options',
        'update-booking-statuses',
        'render_booking_status_update_page'
    );
    
    // Page de debug du planning - COMMENTÉE
    /*
    add_submenu_page(
        'tools.php',
        'Debug Planning',
        'Debug Planning',
        'manage_options',
        'debug-planning',
        'render_debug_planning_page'
    );
    */
/*
}

function render_booking_status_update_page() {
    if (isset($_POST['update_statuses']) && check_admin_referer('update_booking_statuses')) {
        $updated = update_all_booking_statuses();
        echo '<div class="notice notice-success"><p>' . sprintf('Mise à jour terminée : %d statuts modifiés.', $updated) . '</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h1>Mise à jour des Statuts Booking</h1>
        <p>Cette page permet de mettre à jour tous les statuts booking en fonction de l'état des réservations dans le planning.</p>
        
        <form method="post">
            <?php wp_nonce_field('update_booking_statuses'); ?>
            <p>
                <input type="submit" name="update_statuses" class="button button-primary" value="Mettre à jour tous les statuts">
            </p>
        </form>
        
        <h2>Information sur les statuts</h2>
        <ul>
            <li><strong>TODO (1)</strong> : Statut par défaut</li>
            <li><strong>NON (2)</strong> : Non confirmé</li>
            <li><strong>OUI (3)</strong> : Confirmé (automatique si tout est réservé)</li>
            <li><strong>BOOKÉ (4)</strong> : Réservation finalisée</li>
        </ul>
        
        <p><strong>Note :</strong> Le statut passe automatiquement à "OUI" quand tous les formateurs et fournisseurs du planning sont à l'état "Réservé".</p>
    </div>
    <?php
}

function render_debug_planning_page() {
    ?>
    <div class="wrap">
        <h1>Debug Planning</h1>
        <form method="post">
            <label>ID de l'action : </label>
            <input type="number" name="action_id" value="<?php echo isset($_POST['action_id']) ? intval($_POST['action_id']) : ''; ?>">
            <input type="submit" value="Analyser" class="button">
        </form>
        
        <?php
        if (isset($_POST['action_id']) && !empty($_POST['action_id'])) {
            $action_id = intval($_POST['action_id']);
            
            // Essayer les deux noms possibles de méta
            $planning = get_post_meta($action_id, 'fsbdd_planning', true);
            if (empty($planning)) {
                $planning = get_post_meta($action_id, 'fsbdd_grpplanng', true);
            }
            
            echo '<h2>Structure du planning pour l\'action ' . $action_id . '</h2>';
            echo '<pre style="background: #f0f0f0; padding: 10px; overflow: auto;">';
            print_r($planning);
            echo '</pre>';
            
            echo '<h2>Analyse des réservations</h2>';
            $all_reserved = check_planning_all_reserved($action_id);
            echo '<p>Tous les éléments sont réservés : <strong>' . ($all_reserved ? 'OUI' : 'NON') . '</strong></p>';
            
            if (is_array($planning)) {
                foreach ($planning as $index => $date_planning) {
                    echo '<h3>Date ' . ($index + 1) . ' : ' . ($date_planning['fsbdd_planjour'] ?? '') . '</h3>';
                    
                    // Formateurs
                    if (!empty($date_planning['fsbdd_gpformatr'])) {
                        echo '<h4>Formateurs :</h4><ul>';
                        foreach ($date_planning['fsbdd_gpformatr'] as $formateur) {
                            $nom = get_the_title($formateur['fsbdd_user_formateurrel'] ?? 0);
                            $etat = $formateur['fsbdd_okformatr'] ?? 'Non défini';
                            $reserved = ($etat === 'Réservé') ? '✓' : '✗';
                            echo '<li>' . $nom . ' - État : ' . $etat . ' ' . $reserved . '</li>';
                        }
                        echo '</ul>';
                    }
                    
                    // Fournisseurs
                    if (!empty($date_planning['fournisseur_salle'])) {
                        echo '<h4>Fournisseurs/Salles :</h4><ul>';
                        foreach ($date_planning['fournisseur_salle'] as $fournisseur) {
                            $nom = get_the_title($fournisseur['fsbdd_user_foursalle'] ?? 0);
                            $produit = $fournisseur['fsbdd_selected_product_name'] ?? '';
                            $etat = $fournisseur['fsbdd_okformatr'] ?? 'Non défini';
                            $reserved = ($etat === 'Réservé') ? '✓' : '✗';
                            echo '<li>' . $nom . ' (' . $produit . ') - État : ' . $etat . ' ' . $reserved . '</li>';
                        }
                        echo '</ul>';
                    }
                }
            }
            
            // Afficher aussi le statut booking actuel
            $statut_booking = get_post_meta($action_id, 'fsbdd_sessconfirm', true);
            $statuts = [
                1 => 'TODO',
                2 => 'NON',
                3 => 'OUI',
                4 => 'BOOKÉ'
            ];
            echo '<h3>Statut Booking actuel : ' . ($statuts[$statut_booking] ?? 'Non défini') . ' (' . $statut_booking . ')</h3>';
            
            // Bouton pour forcer la mise à jour
            echo '<form method="post" style="margin-top: 20px;">';
            echo '<input type="hidden" name="action_id" value="' . $action_id . '">';
            echo '<input type="hidden" name="force_update" value="1">';
            echo '<input type="submit" value="Forcer la mise à jour du statut" class="button button-primary">';
            echo '</form>';
            
            // Si on demande de forcer la mise à jour
            if (isset($_POST['force_update']) && $_POST['force_update'] == '1') {
                $current_status = get_post_meta($action_id, 'fsbdd_sessconfirm', true);
                
                // Ne pas toucher au statut BOOKÉ
                if ($current_status == 4) {
                    echo '<div class="notice notice-warning"><p>Le statut BOOKÉ ne peut pas être modifié automatiquement</p></div>';
                } elseif ($all_reserved) {
                    update_post_meta($action_id, 'fsbdd_sessconfirm', 3);
                    echo '<div class="notice notice-success"><p>Statut mis à jour vers "OUI"</p></div>';
                } else {
                    update_post_meta($action_id, 'fsbdd_sessconfirm', 2);
                    echo '<div class="notice notice-success"><p>Statut mis à jour vers "NON" car tous les éléments ne sont pas réservés</p></div>';
                }
            }
        }
        ?>
    </div>
    <?php
}
*/
