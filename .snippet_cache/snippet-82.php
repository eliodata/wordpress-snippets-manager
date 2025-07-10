<?php
/**
 * Snippet ID: 82
 * Name: Tableau général admin formateur calendrier planning
 * Description: 
 * @active false
 */


// Snippet PHP à ajouter dans Code Snippets Pro

add_action('admin_menu', 'add_planning_page');
function add_planning_page() {
    add_menu_page(
        __('Planning Fournisseurs', 'your-text-domain'),
        __('Planning Fournisseurs', 'your-text-domain'),
        'manage_options',
        'global-planning',
        'render_planning_page',
        'dashicons-calendar-alt',
        26
    );
}

add_action('admin_enqueue_scripts', 'global_planning_admin_scripts');
function global_planning_admin_scripts($hook) {
    if ($hook !== 'toplevel_page_global-planning') {
        return;
    }

    // jQuery UI datepicker
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', false, '1.12.1');

    // Inputmask
    wp_enqueue_script('inputmask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.7/jquery.inputmask.min.js', ['jquery'], '5.0.7', true);

    // Script d'initialisation du datepicker et inputmask
    $script = "
    jQuery(document).ready(function($) {
        $('.datepicker').datepicker({
            dateFormat: 'dd.mm.yy'
        });
        $('.datepicker').inputmask('99.99.9999',{placeholder:'dd.mm.yyyy'});
    });
    ";
    wp_add_inline_script('inputmask', $script, 'after');
}

add_action('admin_notices', 'global_planning_admin_notices');
function global_planning_admin_notices() {
    if (!empty($_GET['gp_message'])) {
        $msg = sanitize_text_field($_GET['gp_message']);
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
    }

    if (!empty($_GET['gp_error'])) {
        $err = sanitize_text_field($_GET['gp_error']);
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($err) . '</p></div>';
    }
}

function render_planning_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_date'])) {
            $result = handle_add_standalone_date();
            if (is_wp_error($result)) {
                wp_redirect(add_query_arg('gp_error', urlencode($result->get_error_message()), admin_url('admin.php?page=global-planning')));
                exit;
            } else {
                wp_redirect(add_query_arg('gp_message', urlencode(__('Date(s) ajoutée(s) avec succès.', 'your-text-domain')), admin_url('admin.php?page=global-planning')));
                exit;
            }
        }

        if (isset($_POST['save_changes'])) {
            $result = handle_save_standalone_changes();
            if (is_wp_error($result)) {
                wp_redirect(add_query_arg('gp_error', urlencode($result->get_error_message()), admin_url('admin.php?page=global-planning')));
                exit;
            } else {
                wp_redirect(add_query_arg('gp_message', urlencode(__('Modifications enregistrées avec succès.', 'your-text-domain')), admin_url('admin.php?page=global-planning')));
                exit;
            }
        }

        if (isset($_POST['delete_selected'])) {
            handle_delete_selected_standalone_dates();
            wp_redirect(add_query_arg('gp_message', urlencode(__('Dates sélectionnées supprimées avec succès.', 'your-text-domain')), admin_url('admin.php?page=global-planning')));
            exit;
        }
        
        // Traitement du second formulaire (ajout multiple)
        if (isset($_POST['add_multiple_dates'])) {
            $result = handle_add_multiple_standalone_dates();
            if (is_wp_error($result)) {
                wp_redirect(add_query_arg('gp_error', urlencode($result->get_error_message()), admin_url('admin.php?page=global-planning')));
                exit;
            } else {
                wp_redirect(add_query_arg('gp_message', urlencode(__('Dates multiples ajoutées avec succès.', 'your-text-domain')), admin_url('admin.php?page=global-planning')));
                exit;
            }
        }
    }

    $planning_data = get_global_planning_data();
    usort($planning_data, function($a, $b) {
        $ta = strtotime(convert_dmY_to_ymd($a['date']));
        $tb = strtotime(convert_dmY_to_ymd($b['date']));
        return $ta <=> $tb;
    });

    $formateurs_names = [];
    $salles_names = [];
    foreach ($planning_data as $d) {
        if ($d['type'] === 'formateur') {
            $formateurs_names[] = $d['name'];
        } elseif ($d['type'] === 'salle-de-formation') {
            $salles_names[] = $d['name'];
        }
    }
    $formateurs_names = array_unique($formateurs_names);
    sort($formateurs_names);
    $salles_names = array_unique($salles_names);
    sort($salles_names);

    $unique_etats = array_unique(array_map(fn($d) => $d['etat'], $planning_data));
    sort($unique_etats);

    $unique_actions = array_unique(array_filter(array_map(fn($d) => !empty($d['action_id']) ? get_the_title($d['action_id']) : '', $planning_data)));
    sort($unique_actions);

    $month_names = [
        '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril', '05' => 'Mai', '06' => 'Juin',
        '07' => 'Juillet', '08' => 'Août', '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
    ];

    // Récupérer les années et les mois disponibles
    $years = [];
    $months = [];
    foreach ($planning_data as $entry) {
        $timestamp = strtotime(convert_dmY_to_ymd($entry['date']));
        if ($timestamp) {
            $y = date('Y', $timestamp);
            $m = date('m', $timestamp);
            $years[$y] = $y;
            $months[$m] = $m;
        }
    }
    ksort($years);
    ksort($months);

// Préparer les filtres par défaut
$current_year = date('Y');
$next_year = $current_year + 1;
// Supprimer la sélection par défaut des mois
$default_years = [$current_year, $next_year];
$default_months = []; // Aucun mois sélectionné par défaut

$filters = [
    'filter_year'       => isset($_GET['filter_year']) ? (array) $_GET['filter_year'] : $default_years,
    'filter_month'      => isset($_GET['filter_month']) ? (array) $_GET['filter_month'] : $default_months,
    'filter_date'       => $_GET['filter_date'] ?? '',
    'filter_name'       => $_GET['filter_name'] ?? '',
    'filter_type'       => $_GET['filter_type'] ?? 'formateur', // Type par défaut : Formateur
    'filter_etat'       => $_GET['filter_etat'] ?? '',
    'filter_action'     => $_GET['filter_action'] ?? '',
    'filter_standalone' => $_GET['filter_standalone'] ?? '',
];

    $planning_data = apply_filters_to_planning($planning_data, $filters);

    $formateurs = get_posts(['post_type' => 'formateur', 'posts_per_page' => -1]);
    $salles = get_posts(['post_type' => 'salle-de-formation', 'posts_per_page' => -1]);

    ?>
    <div class="wrap" id="global-planning-page">
        <h1><?php _e('Planning Fournisseurs', 'your-text-domain'); ?></h1>

        <!-- Formulaire de filtres -->
        <form method="get" id="global-planning-filters" class="global-planning-filters">
            <input type="hidden" name="page" value="global-planning" />
            
            <!-- Première ligne : Cases à cocher pour les années et les mois, sans labels -->
            <div class="filters-row checkboxes-row">
                <!-- Cases à cocher pour les années -->
                <?php foreach ($years as $year): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="filter_year[]" value="<?php echo esc_attr($year); ?>" <?php echo in_array($year, $filters['filter_year']) ? 'checked' : ''; ?>>
                        <?php echo esc_html($year); ?>
                    </label>
                <?php endforeach; ?>

                <!-- Cases à cocher pour les mois -->
                <?php foreach ($months as $m): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="filter_month[]" value="<?php echo esc_attr($m); ?>" <?php echo in_array($m, $filters['filter_month']) ? 'checked' : ''; ?>>
                        <?php echo esc_html($month_names[$m]); ?>
                    </label>
                <?php endforeach; ?>

                <!-- Date précise avec label "Date :" -->
                <label for="filter_date" class="date-picker-label">
                    <?php _e('Date :', 'your-text-domain'); ?>
                    <input type="text" name="filter_date" id="filter_date" class="datepicker" placeholder="jj.mm.aaaa" value="<?php echo esc_attr($filters['filter_date']); ?>">
                </label>
            </div>

            <!-- Deuxième ligne : Autres filtres avec labels -->
            <div class="filters-row">
                <label for="filter_type"><?php _e('Type', 'your-text-domain'); ?></label>
                <select name="filter_type" id="filter_type">
                    <option value=""><?php _e('Tous', 'your-text-domain'); ?></option>
                    <option value="formateur" <?php selected($filters['filter_type'], 'formateur'); ?>><?php _e('Formateur', 'your-text-domain'); ?></option>
                    <option value="salle-de-formation" <?php selected($filters['filter_type'], 'salle-de-formation'); ?>><?php _e('Salle de formation', 'your-text-domain'); ?></option>
                </select>

                <label for="filter_name"><?php _e('Nom', 'your-text-domain'); ?></label>
                <select name="filter_name" id="filter_name">
                    <option value=""><?php _e('Tous', 'your-text-domain'); ?></option>
                    <?php 
                    // Générer les options en fonction du type sélectionné
                    $names = [];
                    if ($filters['filter_type'] === 'formateur') {
                        $names = $formateurs_names;
                    } elseif ($filters['filter_type'] === 'salle-de-formation') {
                        $names = $salles_names;
                    } else {
                        $names = array_merge($formateurs_names, $salles_names);
                        $names = array_unique($names);
                        sort($names);
                    }

                    foreach ($names as $name): ?>
                        <option value="<?php echo esc_attr($name); ?>" <?php selected($filters['filter_name'], $name); ?>><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="filter_etat"><?php _e('État', 'your-text-domain'); ?></label>
                <select name="filter_etat" id="filter_etat">
                    <option value=""><?php _e('Tous', 'your-text-domain'); ?></option>
                    <?php foreach ($unique_etats as $etat): ?>
                        <option value="<?php echo esc_attr($etat); ?>" <?php selected($filters['filter_etat'], $etat); ?>><?php echo esc_html($etat); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="filter_action"><?php _e('Action', 'your-text-domain'); ?></label>
                <select name="filter_action" id="filter_action">
                    <option value=""><?php _e('Toutes', 'your-text-domain'); ?></option>
                    <?php foreach ($unique_actions as $action): ?>
                        <option value="<?php echo esc_attr($action); ?>" <?php selected($filters['filter_action'], $action); ?>><?php echo esc_html($action); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="filter_standalone"><?php _e('Dispos', 'your-text-domain'); ?></label>
                <select name="filter_standalone" id="filter_standalone">
                    <option value=""><?php _e('Toutes', 'your-text-domain'); ?></option>
                    <option value="standalone" <?php selected($filters['filter_standalone'], 'standalone'); ?>><?php _e('À définir', 'your-text-domain'); ?></option>
                    <option value="linked" <?php selected($filters['filter_standalone'], 'linked'); ?>><?php _e('Définie', 'your-text-domain'); ?></option>
                </select>

                <!-- Bouton Réinitialiser modifié pour décoche tous les filtres via JavaScript -->
                <button type="button" id="reset_filters" class="button button-secondary"><?php _e('Réinitialiser', 'your-text-domain'); ?></button>
            </div>
        </form>
        
        <form method="post">
        
        <!-- Tableau des dates -->
        <table class="wp-list-table widefat fixed striped" id="global-planning-table">
            <thead>
                <tr>
                    <th class="planning-date"><?php _e('Date', 'your-text-domain'); ?></th>
                    <th class="planning-name"><?php _e('Nom', 'your-text-domain'); ?></th>
                    <th class="planning-type"><?php _e('Type', 'your-text-domain'); ?></th>
                    <th class="planning-dispo"><?php _e('Dispo', 'your-text-domain'); ?></th>
                    <th class="planning-etat"><?php _e('État', 'your-text-domain'); ?></th>
                    <th class="planning-action"><?php _e('Action', 'your-text-domain'); ?></th>
                    <th class="planning-formation"><?php _e('Formation', 'your-text-domain'); ?></th>
                    <th class="planning-lieu"><?php _e('Lieu', 'your-text-domain'); ?></th>
                    <th class="planning-inter-intra"><?php _e('Inter / Intra', 'your-text-domain'); ?></th>
                    <th class="planning-booke"><?php _e('Booké', 'your-text-domain'); ?></th>
                    <th class="planning-select"><?php _e('Suppr', 'your-text-domain'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($planning_data)): ?>
                    <?php foreach ($planning_data as $entry): ?>
                    <?php
                    $is_standalone = empty($entry['action_id']);

                    $sessconfirm = $entry['sessconfirm'] ?? '';
                    $typesession = $entry['typesession'] ?? '';
                    $lieusession = $entry['lieusession'] ?? '';
                    $produit_id = $entry['produit_id'] ?? 0;

                    // typesession
                    $typesession_label = '';
                    if ($typesession === '1') {
                        $typesession_label = 'INTER';
                    } elseif ($typesession === '2') {
                        $typesession_label = 'INTER à définir';
                    } elseif ($typesession === '3') {
                        $typesession_label = 'INTRA';
                    }

                    // sessconfirm
                    $booke_display = '';
                    if ($sessconfirm === '1') {
                        $booke_display = 'TODO';
                    } elseif ($sessconfirm === '2') {
                        $booke_display = 'NON';
                    } elseif ($sessconfirm === '3') {
                        $booke_display = 'OUI';
                    } elseif ($sessconfirm === '4') {
                        $booke_display = 'BOOKÉ';
                    }

                    // Formation
                    $formation_title = '';
                    if (!$is_standalone && $produit_id > 0) {
                        $prod_title = get_the_title($produit_id);
                        if ($prod_title) {
                            $formation_title = mb_strimwidth($prod_title, 0, 35, '...');
                        }
                    }

                    // Lieu
                    $lieu_display = '';
                    if (!$is_standalone && !empty($lieusession)) {
                        $parts = explode(' ', trim($lieusession));
                        $lieu_display = $parts[0] ?? '';
                    }
                    ?>
                    <tr class="planning-row">
                        <td class="planning-date">
                            <?php if ($is_standalone): ?>
                                <input type="hidden" name="meta_ids[]" value="<?php echo esc_attr($entry['meta_id']); ?>">
                                <input type="text" name="date_<?php echo esc_attr($entry['meta_id']); ?>" class="datepicker" value="<?php echo esc_attr($entry['date']); ?>" pattern="\d{2}\.\d{2}\.\d{4}" title="Format: jj.mm.aaaa" style="width:100%;">
                            <?php else: ?>
                                <?php echo esc_html($entry['date']); ?>
                            <?php endif; ?>
                        </td>
          <td class="planning-name">
            <?php
              $limitedName = mb_substr($entry['name'], 0, 15, 'UTF-8'); // Truncate name to 15 characters
              if (strlen($entry['name']) > 15) {
                $limitedName .= '...'; // Add ellipsis if name is truncated
              }
              echo esc_html($limitedName);
            ?>
          </td>
						<td class="planning-type"><?php echo esc_html(ucfirst($entry['type'])); ?></td>
                        <td class="planning-dispo">
                            <?php if ($is_standalone): ?>
                                <select name="dispo_<?php echo esc_attr($entry['meta_id']); ?>" style="width:100%;">
                                    <option value="matin" <?php selected(strtolower($entry['dispo']), 'matin'); ?>><?php _e('Matin', 'your-text-domain'); ?></option>
                                    <option value="am" <?php selected(strtolower($entry['dispo']), 'am'); ?>><?php _e('AM', 'your-text-domain'); ?></option>
                                    <option value="journée" <?php selected(strtolower($entry['dispo']), 'journée'); ?>><?php _e('Journée', 'your-text-domain'); ?></option>
                                </select>
                            <?php else: ?>
                                <?php echo esc_html($entry['dispo']); ?>
                            <?php endif; ?>
                        </td>
                        <td class="planning-etat">
                            <?php if ($is_standalone): ?>
                                <select name="etat_<?php echo esc_attr($entry['meta_id']); ?>" style="width:100%;">
                                    <option value="Date libérée" <?php selected($entry['etat'], 'Date libérée'); ?>><?php _e('Date libérée', 'your-text-domain'); ?></option>
                                    <option value="Option" <?php selected($entry['etat'], 'Option'); ?>><?php _e('Option', 'your-text-domain'); ?></option>
                                    <option value="Réservé" <?php selected($entry['etat'], 'Réservé'); ?>><?php _e('Réservé', 'your-text-domain'); ?></option>
                                    <option value="Contrat envoyé" <?php selected($entry['etat'], 'Contrat envoyé'); ?>><?php _e('Contrat envoyé', 'your-text-domain'); ?></option>
                                </select>
                            <?php else: ?>
                                <?php echo esc_html($entry['etat']); ?>
                            <?php endif; ?>
                        </td>
                        <td class="planning-action">
                            <?php if (!empty($entry['action_id'])): ?>
                                <a href="<?php echo esc_url(get_edit_post_link($entry['action_id'])); ?>" target="_blank">
                                    <?php echo esc_html(get_the_title($entry['action_id'])); ?>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo admin_url('edit.php?post_type=action-de-formation'); ?>" target="_blank">
                                    <?php _e('Définir', 'your-text-domain'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td class="planning-formation">
                            <?php if (!$is_standalone && !empty($formation_title)): ?>
                                <?php echo esc_html($formation_title); ?>
                            <?php endif; ?>
                        </td>
                        <td class="planning-lieu">
                            <?php if (!$is_standalone && !empty($lieu_display)): ?>
                                <?php echo esc_html($lieu_display); ?>
                            <?php endif; ?>
                        </td>
                        <td class="planning-inter-intra">
                            <?php if (!$is_standalone && !empty($typesession_label)): ?>
                                <?php echo esc_html($typesession_label); ?>
                            <?php endif; ?>
                        </td>
                        <td class="planning-booke">
                            <?php if (!$is_standalone): ?>
                                <?php echo esc_html($booke_display); ?>
                            <?php endif; ?>
                        </td>
                        <td class="planning-select">
                            <?php if ($is_standalone): ?>
                                <input type="checkbox" name="delete_ids[]" value="<?php echo esc_attr($entry['meta_id']); ?>">
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="no-data"><?php _e('Aucune donnée disponible.', 'your-text-domain'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="actions-bottom">
            <button type="submit" name="save_changes" class="button button-primary"><?php _e('Enregistrer les modifications', 'your-text-domain'); ?></button>
            <button type="submit" name="delete_selected" class="button button-secondary"><?php _e('Supprimer les lignes sélectionnées', 'your-text-domain'); ?></button>
        </div>
        </form>

        <!-- Formulaire d'ajout multiple des dispos fournisseurs -->
<h3><?php _e('Ajouter des dispos fournisseurs', 'your-text-domain'); ?></h3>
<form method="post" class="standalone-date-form multi-dates-form" id="multi-dates-form">
    <div class="form-grid">
        <!-- Première colonne -->
        <div class="form-column">
            <div class="form-group">
                <label for="multi_type"><?php _e('Type Fournisseur', 'your-text-domain'); ?></label>
                <select name="multi_type" id="multi_type" required>
                    <option value="formateur"><?php _e('Formateur', 'your-text-domain'); ?></option>
                    <option value="salle-de-formation"><?php _e('Salle de formation', 'your-text-domain'); ?></option>
                </select>
                <div id="multi_formateur_select" class="conditional-select">
                <label for="multi_formateur"><?php _e('Nom', 'your-text-domain'); ?></label>
                    <select name="multi_formateur_id">
                        <option value=""><?php _e('Sélectionnez un formateur', 'your-text-domain'); ?></option>
                        <?php foreach ($formateurs as $formateur): ?>
                            <option value="<?php echo esc_attr($formateur->ID); ?>">
                                <?php echo esc_html($formateur->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="multi_salle_select" class="conditional-select" style="display:none;">
                <label for="multi_salle"><?php _e('Nom', 'your-text-domain'); ?></label>
                    <select name="multi_salle_id">
                        <option value=""><?php _e('Sélectionnez une salle de formation', 'your-text-domain'); ?></option>
                        <?php foreach ($salles as $salle): ?>
                            <option value="<?php echo esc_attr($salle->ID); ?>">
                                <?php echo esc_html($salle->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Deuxième colonne -->
        <div class="form-column">
            <div class="form-group">
                <label for="multi_dispo"><?php _e('Disponibilité', 'your-text-domain'); ?></label>
                <select name="multi_dispo" required>
                    <option value="matin"><?php _e('Matin', 'your-text-domain'); ?></option>
                    <option value="am"><?php _e('AM', 'your-text-domain'); ?></option>
                    <option value="journée"><?php _e('Journée', 'your-text-domain'); ?></option>
                </select>
            </div>
            <div class="form-group">
                <label for="multi_etat"><?php _e('État', 'your-text-domain'); ?></label>
                <select name="multi_etat" required>
                    <option value="Date libérée"><?php _e('Date libérée', 'your-text-domain'); ?></option>
                    <option value="Option"><?php _e('Option', 'your-text-domain'); ?></option>
                    <option value="Réservé"><?php _e('Réservé', 'your-text-domain'); ?></option>
                    <option value="Contrat envoyé"><?php _e('Contrat envoyé', 'your-text-domain'); ?></option>
                </select>
            </div>
        </div>

        <!-- Troisième colonne -->
        <div class="form-column">
            <div class="form-group">
                <label for="multi_mode"><?php _e('Mode', 'your-text-domain'); ?></label>
                <select name="multi_mode" id="multi_mode" required>
                    <option value="dates"><?php _e('Date(s)', 'your-text-domain'); ?></option>
                    <option value="periode"><?php _e('Période', 'your-text-domain'); ?></option>
                </select>
            </div>
            <div id="multi_dates_block" class="form-group">
                <label><?php _e('Dates', 'your-text-domain'); ?></label>
                <div id="multi_dates_container" class="dates-container">
                    <input type="text" name="multi_dates[]" class="datepicker" pattern="\d{2}\.\d{2}\.\d{4}" placeholder="jj.mm.aaaa">
                </div>
                <button type="button" id="add_more_dates" class="button-secondary"><?php _e('Ajouter une date', 'your-text-domain'); ?></button>
            </div>
            <div id="multi_periode_block" class="form-group" style="display:none;">
                <div>
                    <label for="multi_start_date"><?php _e('Date début', 'your-text-domain'); ?></label>
                    <input type="text" name="multi_start_date" class="datepicker" pattern="\d{2}\.\d{2}\.\d{4}" placeholder="jj.mm.aaaa">
                </div>
                <div>
                    <label for="multi_end_date"><?php _e('Date fin', 'your-text-domain'); ?></label>
                    <input type="text" name="multi_end_date" class="datepicker" pattern="\d{2}\.\d{2}\.\d{4}" placeholder="jj.mm.aaaa">
                </div>
            </div>
        </div>
    </div>

    <!-- Bouton de soumission -->
    <div class="form-footer">
        <button type="submit" name="add_multiple_dates" class="button button-primary">
            <?php _e('Valider', 'your-text-domain'); ?>
        </button>
    </div>
</form>

<style>

    .multi-dates-form {
        background: #4a98ae;
        border: 1px solid #ddd;
        padding: 15px;
        border-radius: 6px;
        margin-top: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        max-width: 100%;
        color: #fff;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        font-size: 14px;
        margin-bottom: 5px;
        text-transform: uppercase;
		margin-top: 14px;
    }

    .form-group select,
    .form-group input {
        width: 100%;
        padding: 4px 10px; /* Réduction du padding */
        font-size: 14px;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-sizing: border-box;
        margin-bottom: 10px;
    }

    .form-group select:focus,
    .form-group input:focus {
        border-color: #4a98ae;
        outline: none;
    }

    .dates-container input {
        margin-bottom: 10px;
    }

    .button-secondary {
        background: #e7f3ff;
        color: #4a98ae;
        border: 1px solid #cce5ff;
        padding: 8px 12px;
        border-radius: 5px;
        font-size: 13px;
        cursor: pointer;
    }

    .button-secondary:hover {
        background: #81bd83;
		color: #fff;
		border: 1px solid #fff;
    }

    .button-primary {
        background: #4a98ae;
        color: #fff;
        border: none;
        padding: 10px 20px;
        font-size: 14px;
        border-radius: 5px;
        cursor: pointer;
    }

    .button-primary:hover {
		color: #fff;
		border: 1px solid #fff;
		background: #81bd83;
    }

    .form-footer {
        text-align: center;
        margin-top: 0px;
    }

    .form-footer .button {
        background: #fff;
        color: #4a98ae;
        font-size: 18px;
		border: 1px solid #4a98ae
        padding: 2px 25px;
    }

    .form-footer .button:hover {
        background: #81bd83;
        color: #fff;
		border: 1px solid #fff
    }

    @media (max-width: 980px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Bouton Ajouter une date */
    #add_more_dates {
        margin-top: 8px; /* pour aligner avec le premier champ date */
    }

    /* Mieux séparer les deux formulaires */
    .standalone-date-form:not(.multi-dates-form) {
        margin-bottom: 30px;
    }

#global-planning-table {
    width: 100%;
    border-collapse: collapse;
}
	

#global-planning-table th,
#global-planning-table td {
    padding: 8px;
    font-size: 14px;
    border-bottom: 1px solid #eee;
    text-align: left; /* Aligner le texte à gauche par défaut pour les td aussi */
}

#global-planning-table th {
    background-color: #4a98af;
    color: white;
    font-weight: bold;
    text-align: left;
    padding-left: 1%;
    text-transform: uppercase;
    font-weight: 300;
    position: sticky;
    font-size: 14px;
}

/* Alternance de couleur */
#global-planning-table tbody tr:nth-child(odd) {
    background: #fff;
}

#global-planning-table tbody tr:nth-child(even) {
    background: #eee;
}

/* Couleur de fond au survol */
#global-planning-table tbody tr:hover {
    background: #d1e7fd; /* Couleur de survol (bleu clair) */
    transition: background 0.3s ease; /* Ajout d'une transition douce */
}

/* Ajout d'une bordure en haut du tableau pour séparer l'entête du reste */
#global-planning-table thead { /* Cibler l'entête <thead> */
    border-bottom: 2px solid #0056b3; /* Bordure plus foncée que le fond */
}

/* Optionnel : pour arrondir les coins du tableau */
#global-planning-table {
    border-collapse: separate; /* Important pour border-radius */
    border-spacing: 0;
    border: 1px solid #ddd; /* Ajoute une bordure autour du tableau */
    border-radius: 8px;
    overflow: hidden; /* Empêche le contenu de dépasser les bords arrondis */
}

#global-planning-table th:first-child {
    border-top-left-radius: 8px;
}

#global-planning-table th:last-child {
    border-top-right-radius: 8px;
}

#global-planning-table tr:last-child td:first-child {
    border-bottom-left-radius: 8px;
}

#global-planning-table tr:last-child td:last-child {
    border-bottom-right-radius: 8px;
}
    /* Largeurs des colonnes personnalisées */
    #global-planning-table th.planning-date,
    #global-planning-table td.planning-date { width: 8%; }
    #global-planning-table th.planning-name,
    #global-planning-table td.planning-name { width: 14%; }
    #global-planning-table th.planning-type,
    #global-planning-table td.planning-type { width: 10%; }
    #global-planning-table th.planning-dispo,
    #global-planning-table td.planning-dispo { width: 6%; }
    #global-planning-table th.planning-etat,
    #global-planning-table td.planning-etat { width: 8%; }
    #global-planning-table th.planning-action,
    #global-planning-table td.planning-action { width: 7%; }
    #global-planning-table th.planning-formation,
    #global-planning-table td.planning-formation { width: 15%; }
    #global-planning-table th.planning-lieu,
    #global-planning-table td.planning-lieu { width: 11%; }
    #global-planning-table th.planning-inter-intra,
    #global-planning-table td.planning-inter-intra { width: 8%; }
    #global-planning-table th.planning-booke,
    #global-planning-table td.planning-booke { width: 5%; }
    #global-planning-table th.planning-select,
    #global-planning-table td.planning-select { width: 6%; }

    .actions-bottom {
        margin-top: 10px;
    }

    .button {
        font-size: 14px;
        cursor: pointer;
    } /* Ajout de l'accolade fermante */

    #global-planning-page {
        font-family: Arial, sans-serif;
        color: #333;
    }

    #global-planning-filters {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 20px;
		background: #4a98ae;
        padding: 10px;
        border-radius: 4px;
    }

    .filters-row.checkboxes-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }

    .filters-row.checkboxes-row .checkbox-label {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 14px;
    }

    .filters-row.checkboxes-row .date-picker-label {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 14px;
    }

    .checkbox-label input[type="checkbox"] {
        transform: scale(1.2);
    }

    .date-picker-label input[type="text"] {
        padding: 4px 8px;
        border: 1px solid #ccc;
        border-radius: 3px;
        width: 120px;
    }

    .filters-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        padding: 8px 5px;
    }

    #global-planning-filters label {
        font-size: 14px;
        font-weight: bold;
        margin-right: 5px;
        width: auto;
		color: #fff
    }

    #global-planning-filters select,
    #global-planning-filters input[type="date"],
    #global-planning-filters input[type="text"] {
        font-size: 14px;
        padding: 2px 5px;
        border: 1px solid #ccc;
        border-radius: 3px;
        min-width: 100px;
    }

    .checkboxes-row label {
        margin-right: 0;
    }

    .checkbox-label,
    .date-picker-label {
        margin-right: 10px;
    }

    .filters-row a.button-secondary,
    .filters-row button#reset_filters {
        margin-left: auto; /* Aligner le bouton Réinitialiser à droite */
		background: #ffffff;
    	font-size: 15px;
    	border: none;
		border: 1px solid #fff;
    	color: #4a98ae;
    }
	
	 .filters-row a.button-secondary,
    .filters-row button#reset_filters:hover {
		background: #81bd83;
		border: 1px solid #fff;
    	color: #fff;
    }
	

</style>


        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterType = document.getElementById('filter_type');
            const filterName = document.getElementById('filter_name');

            const formateursNames = <?php echo json_encode($formateurs_names); ?>;
            const sallesNames = <?php echo json_encode($salles_names); ?>;
            const selectedName = '<?php echo esc_js($filters['filter_name']); ?>';

            function updateNameOptions() {
                const typeVal = filterType.value;
                let names = [];

                if (typeVal === 'formateur') {
                    names = formateursNames;
                } else if (typeVal === 'salle-de-formation') {
                    names = sallesNames;
                } else {
                    names = [...formateursNames, ...sallesNames];
                    names = [...new Set(names)].sort();
                }

                filterName.innerHTML = '<option value=""><?php _e('Tous', 'your-text-domain'); ?></option>';
                names.forEach(function(n) {
                    const opt = document.createElement('option');
                    opt.value = n;
                    opt.textContent = n;
                    if (n === selectedName) {
                        opt.selected = true;
                    }
                    filterName.appendChild(opt);
                });
            }

            filterType.addEventListener('change', updateNameOptions);
            updateNameOptions();

            // Gestion formateur/salle du second formulaire
            const multiType = document.getElementById('multi_type');
            const multiFormSelect = document.getElementById('multi_formateur_select');
            const multiSalleSelect = document.getElementById('multi_salle_select');
            multiType.addEventListener('change', function() {
                if (this.value === 'formateur') {
                    multiFormSelect.style.display = 'block';
                    multiSalleSelect.style.display = 'none';
                } else {
                    multiFormSelect.style.display = 'none';
                    multiSalleSelect.style.display = 'block';
                }
            });

            // Mode dates/periode
            const multiMode = document.getElementById('multi_mode');
            const multiDatesBlock = document.getElementById('multi_dates_block');
            const multiPeriodeBlock = document.getElementById('multi_periode_block');
            multiMode.addEventListener('change', function() {
                if (this.value === 'dates') {
                    multiDatesBlock.style.display = 'block';
                    multiPeriodeBlock.style.display = 'none';
                } else {
                    multiDatesBlock.style.display = 'none';
                    multiPeriodeBlock.style.display = 'block';
                }
            });

            // Ajouter une date
            const addMoreDatesBtn = document.getElementById('add_more_dates');
            const multiDatesContainer = document.getElementById('multi_dates_container');
            addMoreDatesBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const input = document.createElement('input');
                input.type = 'text';
                input.name = 'multi_dates[]';
                input.className = 'datepicker';
                input.pattern = '\\d{2}\\.\\d{2}\\.\\d{4}';
                input.placeholder = 'jj.mm.aaaa';
                multiDatesContainer.appendChild(input);
                jQuery(input).datepicker({ dateFormat:'dd.mm.yy' }).inputmask('99.99.9999',{placeholder:'dd.mm.yyyy'});
            });

            // Gestion du bouton Réinitialiser
            const resetButton = document.getElementById('reset_filters');
            resetButton.addEventListener('click', function() {
                // Réinitialiser les filtres
                // Décoche toutes les années
                $('input[name="filter_year[]"]').prop('checked', false);
                // Décoche tous les mois
                $('input[name="filter_month[]"]').prop('checked', false);
                // Réinitialiser le type à 'formateur'
                $('#filter_type').val('formateur');
                // Mettre à jour les options de nom
                updateNameOptions();
                // Effacer la date précise
                $('#filter_date').val('');
                // Réinitialiser les autres filtres
                $('#filter_name').val('');
                $('#filter_etat').val('');
                $('#filter_action').val('');
                $('#filter_standalone').val('');

                // Recharger les données filtrées
                loadData();
            });
        });
        </script>
    </div>
    <?php
}

function apply_filters_to_planning($data, $filters) {
    return array_filter($data, function($entry) use ($filters) {
        $timestamp = strtotime(convert_dmY_to_ymd($entry['date']));

        // Filtre année (multiples)
        if (!empty($filters['filter_year']) && $timestamp) {
            $year = date('Y', $timestamp);
            if (!in_array($year, $filters['filter_year'])) {
                return false;
            }
        }

        // Filtre mois (multiples)
        if (!empty($filters['filter_month']) && $timestamp) {
            $month = date('m', $timestamp);
            if (!in_array($month, $filters['filter_month'])) {
                return false;
            }
        }

        // Filtre date précise
        if (!empty($filters['filter_date'])) {
            $filter_ts = strtotime($filters['filter_date']);
            $entry_ts = strtotime(convert_dmY_to_ymd($entry['date']));
            if ($filter_ts && $entry_ts && date('Y-m-d', $entry_ts) !== date('Y-m-d', $filter_ts)) {
                return false;
            }
        }

        // Filtre nom
        if (!empty($filters['filter_name']) && $entry['name'] !== $filters['filter_name']) {
            return false;
        }

        // Filtre type
        if (!empty($filters['filter_type']) && $entry['type'] !== $filters['filter_type']) {
            return false;
        }

        // Filtre état
        if (!empty($filters['filter_etat']) && $entry['etat'] !== $filters['filter_etat']) {
            return false;
        }

        // Filtre action
        if (!empty($filters['filter_action'])) {
            $action_name = !empty($entry['action_id']) ? get_the_title($entry['action_id']) : '';
            if ($action_name !== $filters['filter_action']) {
                return false;
            }
        }

        // Filtre stand-alone / lié
        if ($filters['filter_standalone'] === 'standalone' && !empty($entry['action_id'])) {
            return false;
        } elseif ($filters['filter_standalone'] === 'linked' && empty($entry['action_id'])) {
            return false;
        }

        return true;
    });
}

function get_global_planning_data() {
    $results = [];
    $formateurs = get_posts(['post_type' => 'formateur', 'posts_per_page' => -1]);
    $salles = get_posts(['post_type' => 'salle-de-formation', 'posts_per_page' => -1]);

    $all_posts = array_merge($formateurs, $salles);

    foreach ($all_posts as $post) {
        $type = $post->post_type;
        $post_name = $post->post_title;
        $meta_data = get_post_meta($post->ID, 'fsbdd_planning_data', true) ?: [];

        foreach ($meta_data as $key => $entry) {
            $stored_date = $entry['date'] ?? '';
            $dispo = strtolower($entry['dispo'] ?? '');
            $etat = $entry['etat'] ?? '';
            $action_id = $entry['action_id'] ?? '';

            $sessconfirm = $entry['sessconfirm'] ?? '';
            $typesession = $entry['typesession'] ?? '';
            $lieusession = $entry['lieusession'] ?? '';
            $produit_id = $entry['produit_id'] ?? 0;

            $results[] = [
                'date'        => $stored_date,
                'type'        => $type,
                'name'        => $post_name,
                'dispo'       => $dispo,
                'etat'        => $etat,
                'action_id'   => $action_id,
                'meta_id'     => "{$post->ID}_{$key}",
                'sessconfirm' => $sessconfirm,
                'typesession' => $typesession,
                'lieusession' => $lieusession,
                'produit_id'  => $produit_id,
            ];
        }
    }

    return $results;
}

function handle_add_multiple_standalone_dates() {
    // Vérifier les champs requis
    if (!isset($_POST['multi_type'], $_POST['multi_dispo'], $_POST['multi_etat'], $_POST['multi_mode'])) {
        return new WP_Error('missing_data', __('Informations manquantes.', 'your-text-domain'));
    }

    $type = sanitize_text_field($_POST['multi_type']);
    $dispo = strtolower(sanitize_text_field($_POST['multi_dispo']));
    $etat = sanitize_text_field($_POST['multi_etat']);
    $mode = sanitize_text_field($_POST['multi_mode']);

    // Déterminer le post_id du formateur ou de la salle
    if ($type === 'formateur' && !empty($_POST['multi_formateur_id'])) {
        $post_id = (int)$_POST['multi_formateur_id'];
    } elseif ($type === 'salle-de-formation' && !empty($_POST['multi_salle_id'])) {
        $post_id = (int)$_POST['multi_salle_id'];
    } else {
        return new WP_Error('no_post_id', __('Aucun fournisseur valide sélectionné.', 'your-text-domain'));
    }

    $post = get_post($post_id);
    if (!$post) return new WP_Error('no_post', __('Post introuvable.', 'your-text-domain'));
    $post_name = $post->post_title;
    $post_type = $post->post_type;

    $dates_to_add = [];

    if ($mode === 'dates') {
        // On récupère toutes les dates du tableau multi_dates[]
        if (empty($_POST['multi_dates'])) {
            return new WP_Error('no_dates', __('Aucune date fournie.', 'your-text-domain'));
        }
        foreach ($_POST['multi_dates'] as $date_str) {
            $date_str = trim($date_str);
            if (empty($date_str)) continue;
            $ymd = format_date_for_storage($date_str);
            if (!$ymd) {
                return new WP_Error('invalid_date', sprintf(__('Date invalide: %s', 'your-text-domain'), $date_str));
            }
            $dates_to_add[] = $ymd;
        }
    } elseif ($mode === 'periode') {
        // On récupère multi_start_date et multi_end_date
        if (!isset($_POST['multi_start_date'])) {
            return new WP_Error('missing_data', __('Date début manquante.', 'your-text-domain'));
        }
        $start_date = sanitize_text_field($_POST['multi_start_date']);
        $start_ymd = format_date_for_storage($start_date);
        if (!$start_ymd) {
            return new WP_Error('invalid_date', sprintf(__('Date début invalide: %s', 'your-text-domain'), $start_date));
        }

        $end_date = !empty($_POST['multi_end_date']) ? sanitize_text_field($_POST['multi_end_date']) : '';
        if (!empty($end_date)) {
            $end_ymd = format_date_for_storage($end_date);
            if (!$end_ymd) {
                // Si fin invalide, on ajoute quand même la date de début seule
                $dates_to_add[] = $start_ymd;
            } else {
                $start_ts = strtotime($start_ymd);
                $end_ts = strtotime($end_ymd);
                if ($end_ts < $start_ts) {
                    // Si la fin est avant le début, on ajoute juste le début
                    $dates_to_add[] = $start_ymd;
                } else {
                    for ($ts = $start_ts; $ts <= $end_ts; $ts = strtotime('+1 day', $ts)) {
                        $dates_to_add[] = date('Y-m-d', $ts);
                    }
                }
            }
        } else {
            // Pas de date de fin, on ajoute juste la date de début
            $dates_to_add[] = $start_ymd;
        }
    } else {
        return new WP_Error('invalid_mode', __('Mode invalide.', 'your-text-domain'));
    }

    // Vérifier les conflits
    foreach ($dates_to_add as $d_ymd) {
        $d_dmY = date('d.m.Y', strtotime($d_ymd));
        if (global_date_conflict($post_type, $post_name, $d_dmY, $dispo)) {
            return new WP_Error('date_conflict', sprintf(__('La date %s est déjà prise pour cette disponibilité.', 'your-text-domain'), $d_dmY));
        }
    }

    // Pas de conflit, on ajoute
    $meta_data = get_post_meta($post_id, 'fsbdd_planning_data', true) ?: [];
    foreach ($dates_to_add as $d_ymd) {
        $d_dmY = date('d.m.Y', strtotime($d_ymd));
        $meta_data[] = [
            'date' => $d_dmY,
            'dispo' => $dispo,
            'etat' => $etat,
        ];
    }
    update_post_meta($post_id, 'fsbdd_planning_data', $meta_data);

    return true;
}

function handle_delete_selected_standalone_dates() {
    if (empty($_POST['delete_ids'])) {
        return;
    }

    $delete_ids = $_POST['delete_ids'];
    foreach ($delete_ids as $meta_id) {
        [$post_id, $key] = explode('_', $meta_id);
        $meta_data = get_post_meta($post_id, 'fsbdd_planning_data', true) ?: [];
        if (isset($meta_data[$key])) {
            unset($meta_data[$key]);
            update_post_meta($post_id, 'fsbdd_planning_data', $meta_data);
        }
    }
}

function handle_save_standalone_changes() {
    if (empty($_POST['meta_ids'])) {
        return true;
    }

    foreach ($_POST['meta_ids'] as $mid) {
        [$post_id, $key] = explode('_', $mid);
        $post_id = (int)$post_id;

        if (!isset($_POST['date_'.$mid], $_POST['dispo_'.$mid], $_POST['etat_'.$mid])) {
            continue;
        }

        $date_display = sanitize_text_field($_POST['date_'.$mid]);
        $date_ymd = format_date_for_storage($date_display);
        if (!$date_ymd) {
            return new WP_Error('invalid_date', sprintf(__('Date invalide pour la ligne %s.', 'your-text-domain'), $date_display));
        }

        $dispo = strtolower(sanitize_text_field($_POST['dispo_'.$mid]));
        $etat = sanitize_text_field($_POST['etat_'.$mid]);

        $post = get_post($post_id);
        if (!$post) return new WP_Error('no_post', __('Post introuvable.', 'your-text-domain'));
        $post_name = $post->post_title;
        $post_type = $post->post_type;

        $d_dmY = date('d.m.Y', strtotime($date_ymd));
        if (global_date_conflict($post_type, $post_name, $d_dmY, $dispo, $exclude_meta_id=$mid)) {
            return new WP_Error('conflict', sprintf(__('La date %s est déjà prise pour cette disponibilité.', 'your-text-domain'), $d_dmY));
        }

        $meta_data = get_post_meta($post_id, 'fsbdd_planning_data', true) ?: [];
        if (isset($meta_data[$key])) {
            unset($meta_data[$key]);
        }
        $meta_data[$key] = [
            'date' => $d_dmY,
            'dispo' => $dispo,
            'etat' => $etat,
        ];
        update_post_meta($post_id, 'fsbdd_planning_data', $meta_data);
    }

    return true;
}

function global_date_conflict($type, $name, $new_date_dmY, $new_dispo, $exclude_meta_id = null) {
    $data = get_global_planning_data();
    $new_dispo = strtolower($new_dispo);
    foreach ($data as $entry) {
        if ($exclude_meta_id && $entry['meta_id'] === $exclude_meta_id) {
            continue; 
        }

        if ($entry['type'] === $type && $entry['name'] === $name) {
            if (dates_conflict($entry['date'], strtolower($entry['dispo']), $new_date_dmY, $new_dispo)) {
                return true;
            }
        }
    }
    return false;
}

function dates_conflict($existing_date_dmY, $existing_dispo, $new_date_dmY, $new_dispo) {
    $existing_ymd = convert_dmY_to_ymd($existing_date_dmY);
    $new_ymd = convert_dmY_to_ymd($new_date_dmY);
    if (!$existing_ymd || !$new_ymd) return false;

    if ($existing_ymd !== $new_ymd) return false;
    // Même date
    if ($existing_dispo === 'journée' || $new_dispo === 'journée') {
        return true; // journée bloque tout
    }
    if ($existing_dispo === $new_dispo) {
        return true;
    }
    return false;
}

function format_date_for_display($date_ymd) {
    if (!$date_ymd) return '';
    $timestamp = strtotime($date_ymd);
    if (!$timestamp) return $date_ymd;
    return date('d.m.Y', $timestamp);
}

function format_date_for_storage($date_display) {
    $parts = explode('.', $date_display);
    if (count($parts) !== 3) return false;
    [$d, $m, $y] = $parts;
    if (!checkdate((int)$m, (int)$d, (int)$y)) return false;
    return sprintf('%04d-%02d-%02d', $y, $m, $d);
}

function convert_dmY_to_ymd($date_dmY) {
    $parts = explode('.', $date_dmY);
    if (count($parts) !== 3) return false;
    [$d, $m, $y] = $parts;
    if (!checkdate((int)$m, (int)$d, (int)$y)) return false;
    return sprintf('%04d-%02d-%02d', $y, $m, $d);
}

function convert_ymd_to_dmY($date_ymd) {
    if (!$date_ymd) return false;
    $timestamp = strtotime($date_ymd);
    if (!$timestamp) return false;
    return date('d.m.Y', $timestamp);
}


