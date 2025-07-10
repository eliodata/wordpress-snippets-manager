<?php
/**
 * Snippet ID: 83
 * Name: Recherche filtres ajax tableau planning formateurs dispos global en admin
 * Description: 
 * @active false
 */

// Snippet PHP à ajouter dans Code Snippets Pro

add_action('wp_ajax_get_planning_data', 'get_planning_data_ajax');
add_action('wp_ajax_nopriv_get_planning_data', 'get_planning_data_ajax');

function get_planning_data_ajax() {
    // Vérifier les capacités de l'utilisateur si nécessaire
    // if (!current_user_can('manage_options')) {
    //     wp_die(__('Permission refusée.', 'your-text-domain'));
    // }

    // Récupération des filtres depuis $_POST avec gestion des tableaux pour les années et les mois
    $filters = [
        'filter_year'       => isset($_POST['filter_year']) && is_array($_POST['filter_year']) ? array_map('sanitize_text_field', $_POST['filter_year']) : [],
        'filter_month'      => isset($_POST['filter_month']) && is_array($_POST['filter_month']) ? array_map('sanitize_text_field', $_POST['filter_month']) : [],
        'filter_date'       => isset($_POST['filter_date']) ? sanitize_text_field($_POST['filter_date']) : '',
        'filter_name'       => isset($_POST['filter_name']) ? sanitize_text_field($_POST['filter_name']) : '',
        'filter_type'       => isset($_POST['filter_type']) ? sanitize_text_field($_POST['filter_type']) : '',
        'filter_etat'       => isset($_POST['filter_etat']) ? sanitize_text_field($_POST['filter_etat']) : '',
        'filter_action'     => isset($_POST['filter_action']) ? sanitize_text_field($_POST['filter_action']) : '',
        'filter_standalone' => isset($_POST['filter_standalone']) ? sanitize_text_field($_POST['filter_standalone']) : '',
    ];

    // Récupération des données de planning
    $planning_data = get_global_planning_data(); // Cette fonction existe déjà dans votre code

    // Application des filtres
    $planning_data = apply_filters_to_planning($planning_data, $filters); // Cette fonction doit gérer les filtres multiples

    // Tri des données filtrées par date chronologique
    usort($planning_data, function($a, $b) {
        $ta = strtotime(convert_dmY_to_ymd($a['date']));
        $tb = strtotime(convert_dmY_to_ymd($b['date']));
        return $ta <=> $tb;
    });

    // Génération du HTML pour le tableau
    ob_start();
    if (!empty($planning_data)) {
        foreach ($planning_data as $entry) {
            // Reproduire la logique de génération des colonnes supplémentaires
            $is_standalone = empty($entry['action_id']);

            $produit_id = $entry['produit_id'] ?? 0;
            $sessconfirm = $entry['sessconfirm'] ?? '';
            $typesession = $entry['typesession'] ?? '';
            $lieusession = $entry['lieusession'] ?? '';

            // Calcul formation_title
            $formation_title = '';
            if (!$is_standalone && $produit_id > 0) {
                $prod_title = get_the_title($produit_id);
                if ($prod_title) {
                    $formation_title = mb_strimwidth($prod_title, 0, 35, '...');
                }
            }

            // Calcul lieu_display
            $lieu_display = '';
            if (!$is_standalone && !empty($lieusession)) {
                $parts = explode(' ', trim($lieusession));
                $lieu_display = $parts[0] ?? '';
            }

            // Calcul typesession_label
            $typesession_label = '';
            if ($typesession === '1') {
                $typesession_label = 'INTER';
            } elseif ($typesession === '2') {
                $typesession_label = 'INTER à définir';
            } elseif ($typesession === '3') {
                $typesession_label = 'INTRA';
            }

            // Calcul booke_display
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

            ?>
            <tr class="planning-row">
                        <td class="planning-date">
                            <?php if ($is_standalone): ?>
                                <input type="hidden" name="meta_ids[]" value="<?php echo esc_attr($entry['meta_id']); ?>">
                                <input type="text" name="date_<?php echo esc_attr($entry['meta_id']); ?>" class="datepicker" value="<?php echo esc_attr($entry['date']); ?>" pattern="\d{2}\.\d{2}\.\d{4}" title="Format: jj.mm.aaaa" style="width:100%;">
                            <?php else: ?>
                                <?php echo esc_html($entry['date']); ?>
                            <?php endif; ?>
                        </td>          <td class="planning-name">
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
                        </td>                        <td class="planning-etat">
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
                        </td>                <td class="planning-action">
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
                <td class="planning-formation"><?php echo esc_html($formation_title); ?></td>
                <td class="planning-lieu"><?php echo esc_html($lieu_display); ?></td>
                <td class="planning-inter-intra"><?php echo esc_html($typesession_label); ?></td>
                <td class="planning-booke"><?php echo esc_html($booke_display); ?></td>
                <td class="planning-select">
                    <?php if ($is_standalone): ?>
                        <input type="checkbox" name="delete_ids[]" value="<?php echo esc_attr($entry['meta_id']); ?>">
                    <?php endif; ?>
                </td>

            </tr>

				        <div class="actions-bottom">
            <button type="submit" name="save_changes" class="button button-primary"><?php _e('Enregistrer les modifications', 'your-text-domain'); ?></button>
            <button type="submit" name="delete_selected" class="button button-secondary"><?php _e('Supprimer les lignes sélectionnées', 'your-text-domain'); ?></button>
        </div>
				
            <?php
        }
    } else {
        echo '<tr><td colspan="11" class="no-data">' . __('Aucune donnée disponible.', 'your-text-domain') . '</td></tr>';
    }

    $html = ob_get_clean();
    echo $html;
    wp_die();
}

add_action('admin_print_footer_scripts', 'my_admin_footer_script');
function my_admin_footer_script() {
    // On vérifie qu'on est bien sur la page 'global-planning'
    $screen = get_current_screen();
    if ( $screen && $screen->id === 'toplevel_page_global-planning' ) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
            
            function loadData() {
                // Récupérer toutes les années cochées
                var filter_year = [];
                $('input[name="filter_year[]"]:checked').each(function() {
                    filter_year.push($(this).val());
                });

                // Récupérer tous les mois cochés
                var filter_month = [];
                $('input[name="filter_month[]"]:checked').each(function() {
                    filter_month.push($(this).val());
                });

                // Récupérer les autres filtres
                var filter_date = $('#filter_date').val();
                var filter_name = $('#filter_name').val();
                var filter_type = $('#filter_type').val();
                var filter_etat = $('#filter_etat').val();
                var filter_action = $('#filter_action').val();
                var filter_standalone = $('#filter_standalone').val();

                var data = {
                    action: 'get_planning_data',
                    filter_year: filter_year, // Envoi en tant que tableau
                    filter_month: filter_month, // Envoi en tant que tableau
                    filter_date: filter_date,
                    filter_name: filter_name,
                    filter_type: filter_type,
                    filter_etat: filter_etat,
                    filter_action: filter_action,
                    filter_standalone: filter_standalone
                };

                $.post(ajaxurl, data, function(response){
                    $('#global-planning-table tbody').html(response);
                });
            }

            // On déclenche loadData sur chaque changement de filtre
            $('input[name="filter_year[]"], input[name="filter_month[]"], #filter_date, #filter_name, #filter_type, #filter_etat, #filter_action, #filter_standalone').on('change', function() {
                loadData();
            });

            // Chargement initial
            loadData();

            // Gestion du bouton Réinitialiser
            $('#reset_filters').on('click', function() {
                // Décoche toutes les années
                $('input[name="filter_year[]"]').prop('checked', false);
                // Décoche tous les mois
                $('input[name="filter_month[]"]').prop('checked', false);
                // Réinitialise le type à 'formateur'
                $('#filter_type').val('formateur').trigger('change');
                // Efface la date précise
                $('#filter_date').val('');
                // Réinitialise les autres filtres
                $('#filter_name').val('');
                $('#filter_etat').val('');
                $('#filter_action').val('');
                $('#filter_standalone').val('');

                // Recharge les données filtrées
                loadData();
            });
        });
        </script>
        <?php
    }
}