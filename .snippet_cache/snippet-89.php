<?php
/**
 * Snippet ID: 89
 * Name: Page planning global formateurs fournisseurs actions de formation
 * Description: 
 * @active false
 */

/**
 * Plugin Name: Gestion des Plannings Optimisée
 */

// 1. Ajouter un menu dans l'administration
add_action('admin_menu', 'register_planning_admin_page');
function register_planning_admin_page() {
    add_menu_page(
        __('Gestion des Plannings', 'your-text-domain'), // Titre de la page
        __('Plannings', 'your-text-domain'),            // Titre du menu
        'manage_options',                               // Capacité
        'gestion-plannings',                            // Slug de la page
        'render_planning_admin_page',                   // Fonction de rappel
        'dashicons-calendar-alt',                       // Icône
        6                                               // Position
    );
}

// 2. Rendre la page d'administration
function render_planning_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.', 'your-text-domain'));
    }

    // Traitement des formulaires (sauvegarde, suppression et ajout)
    handle_planning_form_submission();

    // Détermine si des filtres ont été soumis
    $filters_submitted = isset($_GET['filter_nom']) || isset($_GET['filter_annee']) || isset($_GET['filter_mois']) || isset($_GET['filter_type']) || isset($_GET['filter_dispo']) || isset($_GET['filter_etat']) || isset($_GET['filter_action']);

    // Récupération des données de planning filtrées
    $plannings = get_filtered_plannings();
	
	// Ne pas afficher les dates antérieures à 3 mois
$three_months_ago = strtotime('-3 months');
$plannings = array_filter($plannings, function($p) use ($three_months_ago) {
    // On tente de parser la date "jj.mm.yyyy"
    $dateObj = DateTime::createFromFormat('d.m.Y', $p['date']);
    if (!$dateObj) {
        // Sinon on tente "yyyy-mm-dd"
        $dateObj = DateTime::createFromFormat('Y-m-d', $p['date']);
    }
    // Si toujours invalide, on exclut
    if (!$dateObj) {
        return false;
    }
    // Garde seulement si la date >= il y a 3 mois
    return ($dateObj->getTimestamp() >= $three_months_ago);
});


    // Récupération et sécurisation des filtres
    $filter_nom    = isset($_GET['filter_nom'])    ? array_map('sanitize_text_field', (array) $_GET['filter_nom']) : [];
    $filter_annee  = isset($_GET['filter_annee'])  ? array_map('sanitize_text_field', (array) $_GET['filter_annee']) : [];
    $filter_mois   = isset($_GET['filter_mois'])   ? array_map('sanitize_text_field', (array) $_GET['filter_mois']) : [];
    $filter_type   = isset($_GET['filter_type'])   ? array_map('sanitize_text_field', (array) $_GET['filter_type']) : [];
    $filter_dispo  = isset($_GET['filter_dispo'])  ? array_map('sanitize_text_field', (array) $_GET['filter_dispo']) : [];
    $filter_etat   = isset($_GET['filter_etat'])   ? array_map('sanitize_text_field', (array) $_GET['filter_etat']) : [];
    $filter_action = isset($_GET['filter_action']) ? sanitize_text_field($_GET['filter_action']) : '';

    // Par défaut : année en cours + deux suivantes si aucun filtre année
    $current_year   = date('Y');
    $default_years  = [$current_year, $current_year + 1, $current_year + 2];
    if (!$filters_submitted || empty($filter_annee)) {
        $filter_annee = $default_years;
    }

    // Par défaut : mois en cours + deux suivants si aucun filtre mois
    $current_month   = date('n'); // 1-12
    $default_months  = [
        $current_month,
        ($current_month % 12) + 1,
        (($current_month + 1) % 12) + 1
    ];
    if (!$filters_submitted || empty($filter_mois)) {
        $filter_mois = $default_months;
    }

    // Assurer que le type "formateur" est sélectionné par défaut
    if (!$filters_submitted || empty($filter_type)) {
        $filter_type = ['formateur'];
    }

    // OPTIM: Récupérer globalement formateurs et salles une seule fois pour réutilisation
    $formateurs = get_posts([
        'post_type'   => 'formateur',
        'numberposts' => -1,
        'orderby'     => 'title',
        'order'       => 'ASC',
    ]);

    $salles = get_posts([
        'post_type'   => 'salle-de-formation',
        'numberposts' => -1,
        'orderby'     => 'title',
        'order'       => 'ASC',
    ]);

    // Précharger les métadonnées pour optimiser les requêtes
    $action_ids = array_unique(array_column($plannings, 'action_id'));
    $actions_meta = [];
    if (!empty($action_ids)) {
        $actions_objects = get_posts([
            'post_type'   => 'action-de-formation',
            'post__in'    => $action_ids,
            'numberposts' => -1,
            'orderby'     => 'post__in',
        ]);

        foreach ($actions_objects as $action) {
            $actions_meta[$action->ID] = [
                'fsbdd_relsessproduit'     => get_post_meta($action->ID, 'fsbdd_relsessproduit', true),
                'fsbdd_select_lieusession' => get_post_meta($action->ID, 'fsbdd_select_lieusession', true),
                'fsbdd_typesession'        => get_post_meta($action->ID, 'fsbdd_typesession', true),
                'fsbdd_sessconfirm'        => get_post_meta($action->ID, 'fsbdd_sessconfirm', true),
            ];
        }
    }
	
	settings_errors('planning_conflict');

    ?>

    <div class="wrap">
        <h1><?php _e('Gestion des Plannings', 'your-text-domain'); ?></h1>

        <!-- Formulaire de Filtres -->
        <form method="get" class="planning-filters">
            <input type="hidden" name="page" value="gestion-plannings" />
            <div class="filters-container">

                <!-- Filtre Nom -->
                <div class="filter-item">
                    <label>
                        <input type="checkbox" id="select_all_nom" onclick="selectAll(this, 'filter_nom')">
                        <?php _e('Nom', 'your-text-domain'); ?>
                    </label>
                    <select name="filter_nom[]" id="filter_nom" multiple>
                        <?php
                            // OPTIM: On se base sur $plannings pour avoir la liste (comme dans votre code)
                            $noms = get_unique_noms($plannings); 
                            foreach ($noms as $nom_id => $nom_title) {
                                echo '<option value="' . esc_attr($nom_id) . '" ' . (in_array($nom_id, $filter_nom) ? 'selected' : '') . '>' . esc_html($nom_title) . '</option>';
                            }
                        ?>
                    </select>
                </div>

                <!-- Filtre Année -->
                <div class="filter-item">
                    <label>
                        <input type="checkbox" id="select_all_annee" onclick="selectAll(this, 'filter_annee')">
                        <?php _e('Année', 'your-text-domain'); ?>
                    </label>
                    <select name="filter_annee[]" id="filter_annee" multiple>
                        <?php
                        $annees = get_unique_annees_php();
                        foreach ($annees as $annee) {
                            echo '<option value="' . esc_attr($annee) . '" ' . (in_array($annee, $filter_annee) ? 'selected' : '') . '>' . esc_html($annee) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <!-- Filtre Mois -->
                <div class="filter-item">
                    <label>
                        <input type="checkbox" id="select_all_mois" onclick="selectAll(this, 'filter_mois')">
                        <?php _e('Mois', 'your-text-domain'); ?>
                    </label>
                    <select name="filter_mois[]" id="filter_mois" multiple>
                        <?php
                        $mois = get_unique_mois_php();
                        foreach ($mois as $m) {
                            $month_name = date_i18n('F', mktime(0, 0, 0, $m, 10));
                            echo '<option value="' . esc_attr($m) . '" ' . (in_array($m, $filter_mois) ? 'selected' : '') . '>' . esc_html($month_name) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <!-- Filtre Type -->
                <div class="filter-item">
                    <label>
                        <input type="checkbox" id="select_all_type" onclick="selectAll(this, 'filter_type')">
                        <?php _e('Type', 'your-text-domain'); ?>
                    </label>
                    <select name="filter_type[]" id="filter_type" multiple>
                        <option value="formateur"   <?php selected(in_array('formateur', $filter_type)); ?>><?php _e('Formateur', 'your-text-domain'); ?></option>
                        <option value="fournisseur" <?php selected(in_array('fournisseur', $filter_type)); ?>><?php _e('Fournisseur', 'your-text-domain'); ?></option>
                    </select>
                </div>

                <!-- Filtre Dispo -->
                <div class="filter-item">
                    <label>
                        <input type="checkbox" id="select_all_dispo" onclick="selectAll(this, 'filter_dispo')">
                        <?php _e('Dispo', 'your-text-domain'); ?>
                    </label>
                    <select name="filter_dispo[]" id="filter_dispo" multiple>
                        <option value="Journ" <?php echo in_array('Journ', $filter_dispo) ? 'selected' : ''; ?>><?php _e('Journ', 'your-text-domain'); ?></option>
                        <option value="Matin"   <?php echo in_array('Matin', $filter_dispo)   ? 'selected' : ''; ?>><?php _e('Matin', 'your-text-domain'); ?></option>
                        <option value="Aprem"      <?php echo in_array('Aprem', $filter_dispo)      ? 'selected' : ''; ?>><?php _e('Aprem', 'your-text-domain'); ?></option>
                    </select>
                </div>

                <!-- Filtre État -->
                <div class="filter-item">
                    <label>
                        <input type="checkbox" id="select_all_etat" onclick="selectAll(this, 'filter_etat')">
                        <?php _e('État', 'your-text-domain'); ?>
                    </label>
                    <select name="filter_etat[]" id="filter_etat" multiple>
                        <?php
                        $etats = get_etat_options();
                        foreach ($etats as $value => $label) {
                            echo '<option value="' . esc_attr($value) . '" ' . (in_array($value, $filter_etat) ? 'selected' : '') . '>' . esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <!-- Filtre Action -->
                <div class="filter-item">
                    <label for="filter_action"><?php _e('Action:', 'your-text-domain'); ?></label>
                    <input type="text" name="filter_action" id="filter_action" value="<?php echo esc_attr($filter_action); ?>" placeholder="<?php _e('Rechercher une action...', 'your-text-domain'); ?>" />
                </div>

                <div class="filter-buttons">
                    <button type="submit" class="button button-primary"><?php _e('Filtrer', 'your-text-domain'); ?></button>
                    <button type="button" class="button button-secondary" id="reset-filters"><?php _e('Effacer les filtres', 'your-text-domain'); ?></button>
                </div>
            </div>
        </form>

        <!-- Tableau des Plannings -->
        <form method="post" id="planning-form">
            <?php wp_nonce_field('planning_form_nonce', 'planning_form_nonce_field'); ?>
            <table class="widefat fixed" cellspacing="0" id="planning-table">
                <thead>
                    <tr>
                        <th class="column-modifier">
                            <span class="dashicons dashicons-saved"></span>
                        </th>
                        <th class="column-date"><?php _e('Date', 'your-text-domain'); ?></th>
                        <th class="column-nom"><?php _e('Nom', 'your-text-domain'); ?></th>
                        <th class="column-type"><?php _e('Type', 'your-text-domain'); ?></th>
                        <th><?php _e('Dispo', 'your-text-domain'); ?></th>
                        <th><?php _e('État', 'your-text-domain'); ?></th>
                        <th class="column-action"><?php _e('Action', 'your-text-domain'); ?></th>
                        <th class="column-formation"><?php _e('Formation', 'your-text-domain'); ?></th>
                        <th class="column-lieu"><?php _e('Lieu', 'your-text-domain'); ?></th>
                        <th class="column-inter-intra"><?php _e('Inter / Intra', 'your-text-domain'); ?></th>
                        <th class="column-booke"><?php _e('Booké', 'your-text-domain'); ?></th>
                        <!-- Nouvelle Colonne pour Commentaire Fournisseur -->
                        <th class="column-commplanfourn"><?php _e('Commentaire', 'your-text-domain'); ?></th>
                        <th class="column-voir-action">
                            <span class="dashicons dashicons-edit"></span>
                        </th>
                        <th class="column-supprimer">
                            <span class="dashicons dashicons-table-row-delete"></span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (!empty($plannings)) :
                        $current_action = '';
                        foreach ($plannings as $index => $planning) :
                            if ($current_action !== $planning['action_id']) {
                                if ($current_action !== '') {
                                    echo '<tr class="separator"><td colspan="14"></td></tr>'; 
                                }
                                $current_action = $planning['action_id'];
                            }

                            // Récupérer les métadonnées préchargées de l'action-de-formation
                            $action_id = $planning['action_id'];
                            $meta = isset($actions_meta[$action_id]) ? $actions_meta[$action_id] : [];

                            // OPTIM: On extrait la logique d'affichage (formation, lieu...) dans une fonction dédiée pour alléger
                            $formation   = get_planning_formation($meta);
                            $lieu        = get_planning_lieu($meta);
                            $inter_intra = get_planning_inter_intra($meta);
                            $booke       = get_planning_booke($meta);

                            // Si type = fournisseur, récupération éventuelle du commentaire
                            $fsbdd_commplanfourn = '';
                            if ($planning['type'] === 'fournisseur') {
                                $fsbdd_commplanfourn = get_commplanfourn($action_id, $planning['date'], $planning['nom']);
                            }
                    ?>
                            <tr>
                                <!-- Case à cocher pour edition -->
                                <td>
                                    <input type="checkbox" name="plannings[<?php echo $index; ?>][edit]" value="1" />
                                </td>

                                <!-- Date (readonly) + champs cachés pour mémoriser l'original -->
                                <td>
                                    <input type="text" name="plannings[<?php echo $index; ?>][date]" value="<?php echo esc_attr($planning['date']); ?>" readonly />
                                    <input type="hidden" name="plannings[<?php echo $index; ?>][action_id]" value="<?php echo esc_attr($planning['action_id']); ?>" />
                                    <input type="hidden" name="plannings[<?php echo $index; ?>][original_nom]" value="<?php echo esc_attr($planning['nom']); ?>" />
                                    <input type="hidden" name="plannings[<?php echo $index; ?>][original_type]" value="<?php echo esc_attr($planning['type']); ?>" />
                                    <input type="hidden" name="plannings[<?php echo $index; ?>][original_date]" value="<?php echo esc_attr($planning['date']); ?>" />
                                    <input type="hidden" name="plannings[<?php echo $index; ?>][original_dispo]" value="<?php echo esc_attr($planning['dispo']); ?>" />
                                    <input type="hidden" name="plannings[<?php echo $index; ?>][original_etat]" value="<?php echo esc_attr($planning['etat']); ?>" />
                                </td>

                                <!-- Nom -->
                                <td>
                                    <select name="plannings[<?php echo $index; ?>][nom]" required>
                                        <?php echo get_nom_options($planning['type'], $planning['nom']); ?>
                                    </select>
                                </td>

                                <!-- Type (readonly) -->
                                <td>
                                    <input type="text" name="plannings[<?php echo $index; ?>][type]" value="<?php echo esc_attr($planning['type']); ?>" readonly />
                                </td>

                                <!-- Dispo -->
                                <td>
    <select name="plannings[<?php echo $index; ?>][dispo]" required>
        <option value="Journ" <?php selected($planning['dispo'], 'Journ'); ?>><?php _e('Journ', 'your-text-domain'); ?></option>
        <option value="Matin" <?php selected($planning['dispo'], 'Matin'); ?>><?php _e('Matin', 'your-text-domain'); ?></option>
        <option value="Aprem" <?php selected($planning['dispo'], 'Aprem'); ?>><?php _e('Aprem', 'your-text-domain'); ?></option>
    </select>
    <div class="conflict-warning">
        <span class="dashicons dashicons-warning"></span>
        <?php _e('Conflit de disponibilité détecté!', 'your-text-domain'); ?>
    </div>
</td>

                                <!-- État -->
                                <td>
                                    <select name="plannings[<?php echo $index; ?>][etat]" required>
                                        <?php foreach (get_etat_options() as $value => $label) : ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($planning['etat'], $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <!-- Assignation action (nouvelle) -->
                                <td class="column-action">
                                    <select name="plannings[<?php echo $index; ?>][new_action]" required>
                                        <?php 
                                        // OPTIM: Au lieu de charger toutes les actions dans chaque ligne, on peut faire une fois au-dessus.
                                        // Mais on le laisse ainsi pour rester fidèle à votre code.
                                        $all_actions = get_posts([
                                            'post_type'   => 'action-de-formation',
                                            'numberposts' => -1,
                                            'orderby'     => 'title',
                                            'order'       => 'ASC',
                                        ]);
                                        foreach ($all_actions as $action) {
                                            $selected = ($action->ID == $planning['action_id']) ? 'selected' : '';
                                            echo '<option value="' . esc_attr($action->ID) . '" ' . $selected . '>' . esc_html($action->post_title) . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <input type="hidden" name="plannings[<?php echo $index; ?>][original_action_id]" value="<?php echo esc_attr($planning['action_id']); ?>" />
                                </td>

                                <!-- Formation, Lieu, Inter/Intra, Booké (affichage pur) -->
                                <td class="column-formation"><?php echo esc_html($formation); ?></td>
                                <td class="column-lieu"><?php echo esc_html($lieu); ?></td>
                                <td class="column-inter-intra"><?php echo esc_html($inter_intra); ?></td>
                                <td class="column-booke"><?php echo esc_html($booke); ?></td>

                                <!-- Commentaire Fournisseur (saisissable seulement si "fournisseur") -->
                                <td class="column-commplanfourn">
                                    <?php if ($planning['type'] === 'fournisseur') : ?>
                                        <input type="text" name="plannings[<?php echo $index; ?>][commplanfourn]" value="<?php echo esc_attr($fsbdd_commplanfourn); ?>" />
                                    <?php endif; ?>
                                </td>

                                <!-- Lien d'édition post -->
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($planning['action_id'])); ?>" target="_blank">
                                        <span class="dashicons dashicons-admin-links"></span>
                                    </a>
                                </td>

                                <!-- Case à cocher pour suppression -->
                                <td>
                                    <input type="checkbox" name="plannings[<?php echo $index; ?>][delete]" value="1" />
                                </td>
                            </tr>
                    <?php 
                        endforeach;
                    else :
                    ?>
                        <tr>
                            <td colspan="14"><?php _e('Aucun planning trouvé.', 'your-text-domain'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <p class="submit">
                <!-- Bouton de confirmation d’édition -->
                <button type="button" class="button button-primary" id="confirm-modifications">
                    <?php _e('Enregistrer les modifications', 'your-text-domain'); ?>
                </button>
                <!-- Bouton masqué -->
                <input type="submit" id="submit-modifications" name="save_plannings" class="button button-primary" value="<?php _e('Confirmer et Enregistrer', 'your-text-domain'); ?>" style="display: none;" />
                <!-- Bouton de suppression -->
                <input type="submit" name="delete_plannings" class="button button-secondary" value="<?php _e('Supprimer les lignes sélectionnées', 'your-text-domain'); ?>" onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer les lignes sélectionnées ?', 'your-text-domain'); ?>');" />
            </p>
        </form>

        <!-- Formulaire d'Ajout de Nouveau Planning -->
        <h2><?php _e('Ajouter au planning', 'your-text-domain'); ?></h2>
        <form method="post" id="add-planning-form" class="add-planning-form">
            <?php wp_nonce_field('add_planning_nonce', 'add_planning_nonce_field'); ?>
            <div class="add-planning-container">
                <div class="add-planning-item">
                    <label for="new_type"><?php _e('Type', 'your-text-domain'); ?></label>
                    <select name="new_type" id="new_type" class="type-select" required>
                        <option value="formateur"><?php _e('Formateur', 'your-text-domain'); ?></option>
                        <option value="fournisseur"><?php _e('Fournisseur', 'your-text-domain'); ?></option>
                    </select>
                </div>

                <div class="add-planning-item">
                    <label for="new_nom"><?php _e('Nom', 'your-text-domain'); ?></label>
                    <select name="new_nom" id="new_nom" required>
                        <option value=""><?php _e('Sélectionner', 'your-text-domain'); ?></option>
                        <!-- Par défaut on liste les formateurs -->
                        <?php echo get_nom_options('formateur'); ?>
                    </select>
                </div>

                <div class="add-planning-item">
                    <label for="new_dispo"><?php _e('Dispo', 'your-text-domain'); ?></label>
                    <select name="new_dispo" id="new_dispo" required>
                        <option value="Journ"><?php _e('Journ', 'your-text-domain'); ?></option>
                        <option value="Matin"><?php _e('Matin', 'your-text-domain'); ?></option>
                        <option value="Aprem"><?php _e('Aprem', 'your-text-domain'); ?></option>
                    </select>
                </div>

                <div class="add-planning-item">
                    <label for="new_etat"><?php _e('État', 'your-text-domain'); ?></label>
                    <select name="new_etat" id="new_etat" required>
                        <?php foreach (get_etat_options() as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="add-planning-item">
                    <label for="new_mode"><?php _e('Mode', 'your-text-domain'); ?></label>
                    <select name="new_mode" id="new_mode" required>
                        <option value="dates"><?php _e('Date(s)', 'your-text-domain'); ?></option>
                        <option value="periode"><?php _e('Période', 'your-text-domain'); ?></option>
                    </select>
                </div>

                <!-- Bloc pour dates multiples -->
                <div class="add-planning-item" id="new_dates_block">
                    <label><?php _e('Dates', 'your-text-domain'); ?></label>
                    <div id="new_dates_container" class="dates-container">
                        <input type="text" name="new_dates[]" class="datepicker" pattern="\d{2}\.\d{2}\.\d{4}" placeholder="jj.mm.aaaa">
                    </div>
                    <button type="button" id="add_more_dates" class="button-secondary"><?php _e('Ajouter une date', 'your-text-domain'); ?></button>
                </div>

                <!-- Bloc pour période -->
                <div class="add-planning-item" id="new_periode_block" style="display:none;">
                    <div>
                        <label for="new_start_date"><?php _e('Date début', 'your-text-domain'); ?></label>
                        <input type="text" name="new_start_date" class="datepicker" pattern="\d{2}\.\d{2}\.\d{4}" placeholder="jj.mm.aaaa">
                    </div>
                    <div>
                        <label for="new_end_date"><?php _e('Date fin', 'your-text-domain'); ?></label>
                        <input type="text" name="new_end_date" class="datepicker" pattern="\d{2}\.\d{2}\.\d{4}" placeholder="jj.mm.aaaa">
                    </div>
                </div>

                <!-- Nouveau champ Commentaire Fournisseur -->
                <div class="add-planning-item" id="new_commplanfourn_block" style="display:none;">
                    <label for="new_commplanfourn"><?php _e('Commentaire Fournisseur', 'your-text-domain'); ?></label>
                    <input type="text" name="new_commplanfourn" id="new_commplanfourn" placeholder="<?php _e('Ajouter un commentaire...', 'your-text-domain'); ?>" />
                </div>

                <div class="add-planning-item">
                    <label for="new_action"><?php _e('Assigné à l\'action', 'your-text-domain'); ?></label>
                    <select name="new_action" id="new_action" required>
                        <option value=""><?php _e('Sélectionner', 'your-text-domain'); ?></option>
                        <?php
                        $actions = get_posts([
                            'post_type'   => 'action-de-formation',
                            'numberposts' => -1,
                            'orderby'     => 'title',
                            'order'       => 'ASC',
                        ]);
                        foreach ($actions as $action) {
                            echo '<option value="' . esc_attr($action->ID) . '">' . esc_html($action->post_title) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="add-planning-button">
                    <input type="submit" name="add_planning" class="button button-primary" value="<?php _e('Ajouter Planning', 'your-text-domain'); ?>" />
                </div>
            </div>
        </form>
    </div>

    <!-- Scripts JavaScript -->
    <script>
    jQuery(document).ready(function($) {
        // Bouton "Enregistrer les modifications" avec résumé
        $('#confirm-modifications').on('click', function(e) {
            e.preventDefault();
            const modifications = [];

            // Exclure les lignes de séparation
            $('#planning-table tbody tr').not('.separator').each(function() {
                const $row = $(this);
                const isChecked = $row.find('input[type="checkbox"][name*="[edit]"]').is(':checked');

                if (isChecked) {
                    const originalActionId = $.trim($row.find('input[name*="[original_action_id]"]').val());
                    const newActionId      = $.trim($row.find('select[name*="[new_action]"]').val());
                    const date             = $.trim($row.find('input[name*="[date]"]').val());
                    const nom              = $.trim($row.find('select[name*="[nom]"] option:selected').text());
                    const dispo            = $.trim($row.find('select[name*="[dispo]"]').val());
                    const etat             = $.trim($row.find('select[name*="[etat]"]').val());
                    const formation        = $.trim($row.find('.column-formation').text());
                    const lieu             = $.trim($row.find('.column-lieu').text());
                    const inter_intra      = $.trim($row.find('.column-inter-intra').text());
                    const booke            = $.trim($row.find('.column-booke').text());
                    const commplanfourn    = $.trim($row.find('.column-commplanfourn input').val() || '');

                    const message = 
                        `Action originale ID : ${originalActionId} → Nouvelle Action ID : ${newActionId}\n` +
                        `Nom : ${nom}\n` +
                        `Disponibilité : ${dispo}\n` +
                        `État : ${etat}\n` +
                        `Formation : ${formation}\n` +
                        `Lieu : ${lieu}\n` +
                        `Inter / Intra : ${inter_intra}\n` +
                        `Booké : ${booke}\n` +
                        (commplanfourn ? `Commentaire Fournisseur : ${commplanfourn}\n` : '') +
                        `Date : ${date}\n`;

                    modifications.push(message);
                }
            });

            if (modifications.length > 0) {
                const confirmationMessage =
                    `Les modifications suivantes seront appliquées :\n\n` +
                    `${modifications.join('\n\n')}\n\n` +
                    `Confirmez-vous ces modifications ?`;
                const confirmation = confirm(confirmationMessage);
                if (confirmation) {
                    // Ajoute le champ caché pour la sauvegarde et soumet
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'save_plannings',
                        value: '1'
                    }).appendTo('#planning-form');
                    $('#planning-form').submit();
                }
            } else {
                alert('<?php _e("Aucune modification détectée.", "your-text-domain"); ?>');
            }
        });

        // Initialiser les datepickers
        $(".datepicker").datepicker({
            dateFormat: "dd.mm.yy"
        });

        // Changement dynamique du champ "Nom" selon le type (formateur/fournisseur)
        $('.type-select').on('change', function() {
            const type     = $(this).val();
            const selectNom = $(this).closest('.add-planning-container').find('select[name="new_nom"]');
            const currentValue = selectNom.val();

            selectNom.html('<option value=""><?php echo addslashes(__('Sélectionner', 'your-text-domain')); ?></option>');
            if (type === 'formateur') {
                <?php
                // On insère via PHP les formateurs
                foreach ($formateurs as $formateur) {
                    echo 'selectNom.append(\'<option value="' . esc_js($formateur->ID) . '">' . esc_js($formateur->post_title) . '</option>\');';
                }
                ?>
            } else if (type === 'fournisseur') {
                <?php
                // On insère via PHP les salles
                foreach ($salles as $salle) {
                    echo 'selectNom.append(\'<option value="' . esc_js($salle->ID) . '">' . esc_js($salle->post_title) . '</option>\');';
                }
                ?>
            }
            // Réappliquer la sélection précédente si possible
            selectNom.val(currentValue);

            // Afficher ou masquer le champ "Commentaire Fournisseur" selon le type
            if (type === 'fournisseur') {
                $('#new_commplanfourn_block').show();
            } else {
                $('#new_commplanfourn_block').hide();
            }
        });

        // Mode (dates / période)
        $('#new_mode').on('change', function() {
            const mode = $(this).val();
            if (mode === 'dates') {
                $('#new_dates_block').show();
                $('#new_periode_block').hide();
            } else {
                $('#new_dates_block').hide();
                $('#new_periode_block').show();
            }
        }).trigger('change');

        // Ajouter plus de dates
        $('#add_more_dates').on('click', function() {
            $('#new_dates_container').append(
                '<input type="text" name="new_dates[]" class="datepicker" pattern="\\d{2}\\.\\d{2}\\.\\d{4}" placeholder="jj.mm.aaaa">'
            );
            $(".datepicker").datepicker({
                dateFormat: "dd.mm.yy"
            });
        });

        // Afficher/Masquer champ commentaire fournisseur selon le type
        $('#new_type').on('change', function() {
            $(this).val() === 'fournisseur'
                ? $('#new_commplanfourn_block').show()
                : $('#new_commplanfourn_block').hide();
        }).trigger('change');

        // Réinitialiser les filtres visuellement (sans rechargement)
        $('#reset-filters').on('click', function() {
            $('.planning-filters select').each(function() {
                $(this).find('option').prop('selected', false);
            });
            $('input[type="checkbox"][id^="select_all_"]').prop('checked', false);
            $('#filter_action').val('');
        });

        // Tout sélectionner / désélectionner dans un <select multiple>
        window.selectAll = function(checkbox, selectId) {
            const select = document.getElementById(selectId);
            for (let i = 0; i < select.options.length; i++) {
                select.options[i].selected = checkbox.checked;
            }
        }

        // Cocher automatiquement la case 'edit' lors d'un changement dans la ligne
        $('#planning-table tbody').on('change', 'input, select, textarea', function() {
            if (!$(this).is('input[type="checkbox"][name*="[edit]"]')) {
                $(this).closest('tr').find('input[type="checkbox"][name*="[edit]"]').prop('checked', true);
            }
        });
    });
		
		// Vérification des conflits en temps réel
function checkForConflicts() {
    $('#planning-table tbody tr').not('.separator').each(function() {
        const $row = $(this);
        const date = $row.find('input[name*="[date]"]').val();
        const nomId = $row.find('select[name*="[nom]"]').val();
        const type = $row.find('input[name*="[type]"]').val();
        const $dispoSelect = $row.find('select[name*="[dispo]"]');

        if (date && nomId) {
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'check_dispo_conflict',
                    date: date,
                    nom_id: nomId,
                    type: type
                },
                success: function(conflicts) {
                    $dispoSelect.find('option').prop('disabled', false);
                    conflicts.forEach(dispo => {
                        $dispoSelect.find('option[value="' + dispo + '"]')
                                  .prop('disabled', true);
                    });
                    
                    if (conflicts.includes($dispoSelect.val())) {
                        $row.addClass('conflict-dispo');
                        $row.find('.conflict-warning').show();
                    } else {
                        $row.removeClass('conflict-dispo');
                        $row.find('.conflict-warning').hide();
                    }
                }
            });
        }
    });
}

// Déclencheur sur modification
$('#planning-table').on('change', 'input[name*="[date]"], select[name*="[nom]"], select[name*="[dispo]"]', function() {
    checkForConflicts();
});

// Initialisation
checkForConfictions();
		
    </script>

    <!-- Feuilles de style et styles inline -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>
		
		/* Conflits de disponibilité */
.conflict-dispo {
    background-color: #ffebee !important;
    border-left: 3px solid #ff5252 !important;
}

.conflict-dispo select {
    border-color: #ff1744 !important;
}

.conflict-warning {
    color: #d32f2f;
    font-size: 0.9em;
    margin-top: 3px;
    display: none;
}
		
        /* Vos styles condensés, réorganisés pour plus de lisibilité */
        
        #wpcontent, #wpfooter {
            background: #c9c9c9;
        }
        .wrap h1 {
            font-size: 2em;
            margin-bottom: 20px;
            color: #fff;
            background: #4998ae;
            padding: 8px 12px;
            border-radius: 4px;
            text-align: center;
        }
        h2 {
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 4px;
            color: #fff;
            background: #4998ae;
        }
        .planning-filters {
            background: #4a98ae;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 30px;
            color: #fff;
            text-transform: uppercase;
        }
        .filters-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
        }
        .filter-item {
            flex: 1 1 100px;
            display: flex;
            flex-direction: column;
            max-width: 11.5%;
        }
        .filter-item label {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            font-weight: 400;
            font-size: 14px;
        }
        .filter-item label input[type="checkbox"] {
            margin-right: 5px;
        }
        .filter-item select,
        .filter-item input[type="text"] {
            padding: 6px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
        }
        .filter-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 10px;
        }
        .filter-buttons .button {
            width: 100%;
        }
        .planning-filters select[multiple] {
            height: auto;
            max-height: 120px;
            overflow-y: auto;
        }
        #planning-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            table-layout: fixed;
            max-height: 600px;
            overflow-y: auto;
            display: block;
            box-shadow: 0px 0px 10px 2px rgb(68 67 67 / 54%);
            border-radius: 5px;
        }
        #planning-table th, #planning-table td {
            text-align: center;
            vertical-align: middle;
            padding: 1px 4px;
            font-size: 13px;
            word-wrap: break-word;
        }
        #planning-table th {
            color: #fff;
            padding: 7px 0;
            background-color: #4a98ae;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        #planning-table tr.separator {
            background-color: #4a98ae;
        }
        #planning-table tr:nth-child(even) {
            background-color: #EAEAEA;
        }
        #planning-table tbody tr:hover {
            background: #d1e7fd;
            transition: background 0.3s ease;
        }
        #planning-table .column-action {
            width: 5% !important;
            background-color: #4a98ae !important;
        }
        #planning-table .column-action select {
            border-color: #74bcd1 !important;
            font-size: 14px;
            color: #fff;
        }
        #planning-table .column-action select:hover {
            background: #d1e7fd;
            color: #000;
            transition: background 0.3s ease;
        }
        #planning-table .column-booke { width: 5% !important; }
        #planning-table .column-commplanfourn { width: 12% !important; }
		
		        /* Largeur des colonnes date, type et action */
        .column-date {
            width: 9% !important;
        }

        .column-type {
            width: 8% !important;
        }

        /* Colonne "Action" mise en valeur */
#planning-table .column-action {
    width: 6% !important;
}

        .column-dispo {
            width: 3% !important;
        }

        .column-etat {
            width: 6% !important;
        }

        /* Largeur des nouvelles colonnes */
        .column-formation {
            width: 15% !important;
        }

        .column-lieu {
            width: 11% !important;
        }

        .column-inter-intra {
            width: 8% !important;
        }

        .column-booke {
            width: 5% !important;
        }

        /* Nouvelle Colonne pour Commentaire Fournisseur */
        .column-commplanfourn {
            width: 12% !important;
        }

        /* Largeur des colonnes restantes (Nom, Dispo, État) */
        .column-nom {
            width: 10% !important;
        }

        #planning-table th {
            color: #fff;
            padding: 7px 0px;
        }


        /* Cases à cocher */
        #planning-table input[type="checkbox"] {
            margin: auto;
            height: 18px;
            width: 18px;
            border: 1px solid #949494;
        }
        #planning-table input[type="checkbox"]:checked::before {
            background: #61f758;
            border-radius: 4px;
            border: none;
            margin: auto;
            height: 16px !important;
            width: 16px !important;
        }
        #planning-table input, #planning-table select {
            width: 100%;
            box-sizing: border-box;
            padding: 4px 6px;
            border: 1px solid #e2e2e2;
            border-radius: 4px;
            font-size: 12px;
            background: transparent;
        }
        input[readonly] {
            border: none !important;
        }

        /* Formulaire d'ajout */
        #add-planning-form {
            color: #fff;
            padding: 10px;
            border-radius: 5px;
            margin-top: 5px;
            background: #4a98ae;
            text-transform: uppercase;
            margin-bottom: 40px;
        }
        .add-planning-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
        }
        .add-planning-item {
            flex: 1 1 150px;
            display: flex;
            flex-direction: column;
        }
        .add-planning-item label {
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 14px;
        }
        .add-planning-item input[type="text"],
        .add-planning-item select {
            padding: 6px 8px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            width: 100%;
        }
        .add-planning-button {
            flex: 1 1 150px;
            display: flex;
            align-items: flex-end;
        }
        .add-planning-button input[type="submit"] {
            padding: 8px 16px;
            font-size: 13px;
        }
        .submit {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .submit .button-primary {
            background-color: #0073aa;
            color: #fff;
            border: none;
        }
        .submit .button-primary:hover {
            background-color: green !important;
        }
        .submit .button-secondary {
            background-color: #ffd8ad;
            color: #333;
        }
        .submit .button-secondary:hover {
            background-color: #cc7c24;
            color: #fff;
        }

        /* Dates multiples */
        .dates-container input {
            display: block;
            margin-bottom: 5px;
        }
        .dates-container input:last-child {
            margin-bottom: 0;
        }
        /* Notices */
        .updated, .error {
            margin-top: 20px;
            padding: 10px;
            border-radius: 4px;
        }
        .updated {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
    <?php
}

// ---------------------------------------------------------------------------
// 3. Fonction pour récupérer les plannings (avec filtres)
// ---------------------------------------------------------------------------
function get_filtered_plannings() {
    $args = [
        'post_type'      => 'action-de-formation',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ];
    $posts = get_posts($args);
    $plannings = [];

    // Récupération et sécurisation des filtres
    $filter_nom      = isset($_GET['filter_nom'])     ? array_map('sanitize_text_field', (array) $_GET['filter_nom']) : [];
    $filter_annee    = isset($_GET['filter_annee'])   ? array_map('sanitize_text_field', (array) $_GET['filter_annee']) : [];
    $filter_mois     = isset($_GET['filter_mois'])    ? array_map('sanitize_text_field', (array) $_GET['filter_mois']) : [];
    $filter_type     = isset($_GET['filter_type'])    ? array_map('sanitize_text_field', (array) $_GET['filter_type']) : [];
    $filter_dispo    = isset($_GET['filter_dispo'])   ? array_map('sanitize_text_field', (array) $_GET['filter_dispo']) : [];
    $filter_etat     = isset($_GET['filter_etat'])    ? array_map('sanitize_text_field', (array) $_GET['filter_etat']) : [];
    $filter_action   = isset($_GET['filter_action'])  ? sanitize_text_field($_GET['filter_action']) : '';

    foreach ($posts as $post) {
        $planning_data = get_post_meta($post->ID, 'fsbdd_planning', true);
        if (!empty($planning_data) && is_array($planning_data)) {
            foreach ($planning_data as $entry) {
                $date = isset($entry['fsbdd_planjour']) ? $entry['fsbdd_planjour'] : '';
                $date_obj = parse_planning_date($date); // OPTIM: fonction centralisée
                if (!$date_obj) {
                    continue;
                }

                $month = intval($date_obj->format('n'));
                $year  = intval($date_obj->format('Y'));

                // Filtre année / mois
                if (!empty($filter_annee) && !in_array($year, $filter_annee)) {
                    continue;
                }
                if (!empty($filter_mois) && !in_array($month, $filter_mois)) {
                    continue;
                }

                // Filtre action (par titre)
                if (!empty($filter_action) && stripos(get_the_title($post->ID), $filter_action) === false) {
                    continue;
                }

                // Formateurs
                if (!empty($entry['fsbdd_gpformatr']) && is_array($entry['fsbdd_gpformatr'])) {
                    foreach ($entry['fsbdd_gpformatr'] as $formateur) {
                        $nom_id = isset($formateur['fsbdd_user_formateurrel']) ? $formateur['fsbdd_user_formateurrel'] : '';
                        $dispo  = isset($formateur['fsbdd_dispjourform'])      ? $formateur['fsbdd_dispjourform']      : '';
                        $etat   = isset($formateur['fsbdd_okformatr'])         ? $formateur['fsbdd_okformatr']         : '';

                        // Filtrer par nom, type, dispo, état
                        if (!empty($filter_nom)   && !in_array($nom_id, $filter_nom))    { continue; }
                        if (!empty($filter_type)  && !in_array('formateur', $filter_type)) { continue; }
                        if (!empty($filter_dispo) && !in_array($dispo, $filter_dispo))     { continue; }
                        if (!empty($filter_etat)  && !in_array($etat,  $filter_etat))      { continue; }

                        $plannings[] = [
                            'action_id' => $post->ID,
                            'date'      => $date_obj->format('d.m.Y'),
                            'nom'       => $nom_id,
                            'type'      => 'formateur',
                            'dispo'     => $dispo,
                            'etat'      => $etat,
                        ];
                    }
                }

                // Fournisseurs / Salles
                if (!empty($entry['fournisseur_salle']) && is_array($entry['fournisseur_salle'])) {
                    foreach ($entry['fournisseur_salle'] as $salle) {
                        $nom_id = isset($salle['fsbdd_user_foursalle']) ? $salle['fsbdd_user_foursalle'] : '';
                        $dispo  = isset($salle['fsbdd_dispjourform'])   ? $salle['fsbdd_dispjourform']   : '';
                        $etat   = isset($salle['fsbdd_okformatr'])      ? $salle['fsbdd_okformatr']      : '';

                        // Filtrer par nom, type, dispo, état
                        if (!empty($filter_nom)   && !in_array($nom_id, $filter_nom))         { continue; }
                        if (!empty($filter_type)  && !in_array('fournisseur', $filter_type))  { continue; }
                        if (!empty($filter_dispo) && !in_array($dispo, $filter_dispo))        { continue; }
                        if (!empty($filter_etat)  && !in_array($etat,  $filter_etat))         { continue; }

                        $plannings[] = [
                            'action_id' => $post->ID,
                            'date'      => $date_obj->format('d.m.Y'),
                            'nom'       => $nom_id,
                            'type'      => 'fournisseur',
                            'dispo'     => $dispo,
                            'etat'      => $etat,
                        ];
                    }
                }
            }
        }
    }

    // Tri chronologique
    usort($plannings, function($a, $b) {
        $date_a = parse_planning_date($a['date']);
        $date_b = parse_planning_date($b['date']);
        if (!$date_a || !$date_b) {
            return 0;
        }
        return $date_a <=> $date_b;
    });

    return $plannings;
}

// ---------------------------------------------------------------------------
// 4. Générer les options pour le champ "Nom"
// ---------------------------------------------------------------------------
function get_nom_options($type, $selected_id = '') {
    // OPTIM: on peut mémoriser ces requêtes en static si on les appelle souvent
    $options = '<option value="">' . __('Sélectionner', 'your-text-domain') . '</option>';

    if ($type === 'formateur') {
        $formateurs = get_posts([
            'post_type'   => 'formateur',
            'numberposts' => -1,
            'orderby'     => 'title',
            'order'       => 'ASC',
        ]);
        foreach ($formateurs as $formateur) {
            $selected = ($formateur->ID == $selected_id) ? 'selected' : '';
            $options .= '<option value="' . esc_attr($formateur->ID) . '" ' . $selected . '>' . esc_html($formateur->post_title) . '</option>';
        }
    } elseif ($type === 'fournisseur') {
        $salles = get_posts([
            'post_type'   => 'salle-de-formation',
            'numberposts' => -1,
            'orderby'     => 'title',
            'order'       => 'ASC',
        ]);
        foreach ($salles as $salle) {
            $selected = ($salle->ID == $selected_id) ? 'selected' : '';
            $options .= '<option value="' . esc_attr($salle->ID) . '" ' . $selected . '>' . esc_html($salle->post_title) . '</option>';
        }
    }

    return $options;
}

// ---------------------------------------------------------------------------
// 5. Obtenir les options d'état
// ---------------------------------------------------------------------------
function get_etat_options() {
    // Vous pouvez personnaliser le libellé / l'ordre
    return [
        'Date libérée'   => __('Date libérée', 'your-text-domain'),
        'Option'         => __('Option', 'your-text-domain'),
        'Pré bloqué FS'  => __('Pré bloqué FS', 'your-text-domain'),
        'Réservé'        => __('Réservé', 'your-text-domain'),
        'Contrat envoyé' => __('Contrat envoyé', 'your-text-domain'),
        'Contrat reçu'   => __('Contrat reçu', 'your-text-domain'),
        'Emargement OK'  => __('Emargement OK', 'your-text-domain'),
    ];
}

// ---------------------------------------------------------------------------
// 6. Récupérer les noms uniques pour les filtres (basés sur $filtered_plannings)
// ---------------------------------------------------------------------------
function get_unique_noms($filtered_plannings) {
    $noms = [];
    foreach ($filtered_plannings as $planning) {
        if (!empty($planning['nom'])) {
            $noms[$planning['nom']] = get_the_title($planning['nom']);
        }
    }
    asort($noms);
    return $noms;
}

// ---------------------------------------------------------------------------
// 7. Récupérer les années uniques via PHP
// ---------------------------------------------------------------------------
function get_unique_annees_php() {
    $args = [
        'post_type'      => 'action-de-formation',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ];
    $posts = get_posts($args);
    $annees = [];

    foreach ($posts as $post) {
        $planning_data = get_post_meta($post->ID, 'fsbdd_planning', true);
        if (!empty($planning_data) && is_array($planning_data)) {
            foreach ($planning_data as $entry) {
                $date_obj = parse_planning_date($entry['fsbdd_planjour'] ?? '');
                if ($date_obj) {
                    $annees[] = $date_obj->format('Y');
                }
            }
        }
    }
    $unique_annees = array_unique($annees);
    sort($unique_annees);
    return $unique_annees;
}

// ---------------------------------------------------------------------------
// 8. Récupérer les mois uniques via PHP
// ---------------------------------------------------------------------------
function get_unique_mois_php() {
    $args = [
        'post_type'      => 'action-de-formation',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ];
    $posts = get_posts($args);
    $mois = [];

    foreach ($posts as $post) {
        $planning_data = get_post_meta($post->ID, 'fsbdd_planning', true);
        if (!empty($planning_data) && is_array($planning_data)) {
            foreach ($planning_data as $entry) {
                $date_obj = parse_planning_date($entry['fsbdd_planjour'] ?? '');
                if ($date_obj) {
                    $mois[] = intval($date_obj->format('n'));
                }
            }
        }
    }
    $unique_mois = array_unique($mois);
    sort($unique_mois);
    return $unique_mois;
}

// ---------------------------------------------------------------------------
// 9. Traitement des soumissions de formulaire
// ---------------------------------------------------------------------------
function handle_planning_form_submission() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if (isset($_POST['save_plannings']) && isset($_POST['plannings'])) {
        handle_save_plannings($_POST['plannings']);
    }
    if (isset($_POST['delete_plannings']) && isset($_POST['plannings'])) {
        handle_delete_plannings($_POST['plannings']);
    }
    if (isset($_POST['add_planning'])) {
        handle_add_planning($_POST);
    }
}

// ---------------------------------------------------------------------------
// 9.1 Sauvegarde des plannings (édition) corrigée pour back-end uniquement
// ---------------------------------------------------------------------------
function handle_save_plannings($plannings) {
    if (!verify_nonce('planning_form_nonce_field', 'planning_form_nonce')) {
        error_log('Nonce verification failed for save_plannings');
        return;
    }

    $confirmations = [];
    $errors = [];

    foreach ($plannings as $planning) {
        if (isset($planning['edit']) && $planning['edit'] == '1') {
            // Champs requis
            $required_fields = [
                'action_id', 'date', 'nom', 'type', 'dispo', 'etat',
                'original_nom', 'original_type', 'original_date', 'original_dispo', 'original_etat'
            ];
            if (!has_required_fields($planning, $required_fields)) {
                continue;
            }

            // Validation des conflits avant mise à jour
            $data = get_sanitized_planning_data($planning);
            if (has_planning_conflict($data['new_nom'], $data['new_date'], $data['new_dispo'], $data['new_type'], $data)) {
                $errors[] = sprintf(
                    __('Conflit : %s est déjà réservé en %s le %s', 'your-text-domain'),
                    get_the_title($data['new_nom']),
                    $data['new_dispo'],
                    $data['new_date']
                );
                continue;
            }

            // Vérifie si quelque chose a changé
            $is_changed = (
                $data['new_nom'] !== $data['original_nom'] ||
                $data['new_type'] !== $data['original_type'] ||
                $data['new_date'] !== $data['original_date'] ||
                $data['new_dispo'] !== $data['original_dispo'] ||
                $data['new_etat'] !== $data['original_etat']
            );

            if (!$is_changed) {
                continue;
            }

            // Mise à jour ou déplacement du planning
            if ($data['new_action_id'] !== $data['original_action_id']) {
                // Retirer de l'action d'origine et ajouter à la nouvelle
                if (remove_planning_from_action($data['original_action_id'], $data['original_type'], $data['original_nom'], $data['original_date'])) {
                    if (add_planning_to_action($data['new_action_id'], $data['new_type'], $data['new_nom'], $data['new_date'], $data['new_dispo'], $data['new_etat'])) {
                        $confirmations[$data['original_action_id']][] = sprintf(
                            __('Déplacement du planning pour %s vers %s le %s', 'your-text-domain'),
                            esc_html(get_the_title($data['new_nom'])),
                            esc_html(get_the_title($data['new_action_id'])),
                            $data['new_date']
                        );
                    }
                }
            } else {
                // Mise à jour dans la même action
                if (update_planning_in_action(
                    $data['original_action_id'], $data['original_type'], $data['original_nom'], $data['original_date'],
                    $data['new_nom'], $data['new_type'], $data['new_date'], $data['new_dispo'], $data['new_etat']
                )) {
                    $confirmations[$data['original_action_id']][] = sprintf(
                        __('Modification du planning pour %s le %s', 'your-text-domain'),
                        esc_html(get_the_title($data['new_nom'])),
                        $data['new_date']
                    );
                }
            }
        }
    }

    // Stocker les confirmations et erreurs pour affichage
    if (!empty($confirmations)) {
        set_transient('planning_confirmations', $confirmations, 30);
    }

    if (!empty($errors)) {
        set_transient('planning_errors', $errors, 30);
    }

    wp_redirect($_SERVER['HTTP_REFERER']);
    exit;
}


// ---------------------------------------------------------------------------
// 9.2 Suppression des plannings
// ---------------------------------------------------------------------------
function handle_delete_plannings($plannings) {
    if (!verify_nonce('planning_form_nonce_field', 'planning_form_nonce')) {
        return;
    }
    $confirmations = [];

    foreach ($plannings as $planning) {
        if (isset($planning['delete']) && $planning['delete'] == '1') {
            $required_fields = ['action_id','date','nom','type'];
            if (!has_required_fields($planning, $required_fields)) {
                continue;
            }
            $action_id = intval($planning['action_id']);
            $date      = sanitize_text_field($planning['date']);
            $nom_id    = intval($planning['nom']);
            $type      = sanitize_text_field($planning['type']);

            $existing_plannings = get_post_meta($action_id, 'fsbdd_planning', true);
            if (empty($existing_plannings) || !is_array($existing_plannings)) {
                continue;
            }

            foreach ($existing_plannings as $index => &$entry) {
                if (($entry['fsbdd_planjour'] ?? '') === $date) {

                    // --- Suppression Formateur
                    if ($type === 'formateur' && !empty($entry['fsbdd_gpformatr'])) {
                        foreach ($entry['fsbdd_gpformatr'] as $f_index => $formateur) {
                            if (($formateur['fsbdd_user_formateurrel'] ?? '') == $nom_id) {
                                unset($entry['fsbdd_gpformatr'][$f_index]);
                                $action_title = get_the_title($action_id);
                                $confirmations[$action_id][] = sprintf(
                                    __('Suppression du formateur %s le %s', 'your-text-domain'),
                                    esc_html(get_post_field('post_title', $nom_id)),
                                    esc_html($date)
                                );
                                break; // <-- stoppe la boucle après la première suppression
                            }
                        }
                        // Nettoyage si plus de formateur
                        if (empty($entry['fsbdd_gpformatr'])) {
                            unset($entry['fsbdd_gpformatr']);
                        }
                    }

                    // --- Suppression Fournisseur
                    elseif ($type === 'fournisseur' && !empty($entry['fournisseur_salle'])) {
                        foreach ($entry['fournisseur_salle'] as $s_index => $salle) {
                            if (($salle['fsbdd_user_foursalle'] ?? '') == $nom_id) {
                                unset($entry['fournisseur_salle'][$s_index]);
                                $action_title = get_the_title($action_id);
                                $confirmations[$action_id][] = sprintf(
                                    __('Suppression du fournisseur %s le %s', 'your-text-domain'),
                                    esc_html(get_post_field('post_title', $nom_id)),
                                    esc_html($date)
                                );
                                break; // <-- stoppe la boucle après la première suppression
                            }
                        }
                        // Nettoyage si plus de fournisseur
                        if (empty($entry['fournisseur_salle'])) {
                            unset($entry['fournisseur_salle']);
                        }
                    }

                    // --- Enfin, si plus personne sur cette date...
                    if (empty($entry['fsbdd_gpformatr']) && empty($entry['fournisseur_salle'])) {
                        unset($existing_plannings[$index]);
                    }
                }
            }

            update_post_meta($action_id, 'fsbdd_planning', $existing_plannings);
            if (function_exists('sync_formation_planning_costs')) {
                sync_formation_planning_costs($action_id);
            }
        }
    }

    // Stocke et redirige comme les autres
    set_transient('planning_confirmations', $confirmations, 30);
    wp_redirect($_SERVER['HTTP_REFERER']);
    exit;
}


// ---------------------------------------------------------------------------
// 9.3 Ajout de planning
// ---------------------------------------------------------------------------
function handle_add_planning($post_data) {
	
	if (has_planning_conflict($nom_id, $formatted_date, $new_dispo, $type)) {
    $error_message = sprintf(
        __('Conflit de disponibilité pour %s le %s (%s existe déjà)'),
        get_the_title($nom_id),
        $formatted_date,
        $existing_dispo
    );
    add_settings_error('planning_conflict', 'conflict', $error_message, 'error');
    return;
}
	
    if (!verify_nonce('add_planning_nonce_field', 'add_planning_nonce')) {
        return;
    }

    $new_type         = sanitize_text_field($post_data['new_type'] ?? '');
    $new_nom          = intval($post_data['new_nom'] ?? 0);
    $new_dispo        = sanitize_text_field($post_data['new_dispo'] ?? '');
    $new_etat         = sanitize_text_field($post_data['new_etat'] ?? '');
    $new_mode         = sanitize_text_field($post_data['new_mode'] ?? 'dates');
    $new_action       = intval($post_data['new_action'] ?? 0);
    $new_commplanfourn= sanitize_text_field($post_data['new_commplanfourn'] ?? '');

    $confirmations = [];

    if ($new_type && $new_nom && $new_dispo && $new_etat && $new_mode && $new_action) {
        if ($new_mode === 'dates') {
            $new_dates = isset($post_data['new_dates']) ? array_map('sanitize_text_field', (array)$post_data['new_dates']) : [];
            if (empty($new_dates)) {
                echo '<div class="error"><p>' . __('Veuillez ajouter au moins une date.', 'your-text-domain') . '</p></div>';
                return;
            }
            foreach ($new_dates as $date) {
                if (!$date) { continue; }
                $formatted_date = validate_and_format_date($date);
                if (!$formatted_date) {
                    echo '<div class="error"><p>' . __('Date invalide:', 'your-text-domain') . ' ' . esc_html($date) . '</p></div>';
                    continue;
                }
				
				    if (has_planning_conflict($new_nom, $formatted_date, $new_dispo, $new_type)) {
        $error_message = sprintf(
            __('Conflit : %s est déjà réservé en %s le %s', 'your-text-domain'),
            get_the_title($new_nom),
            $new_dispo,
            $formatted_date
        );
        add_settings_error('planning_conflict', 'conflict', $error_message, 'error');
        return;
    }
				
                $added = add_planning_to_action($new_action, $new_type, $new_nom, $formatted_date, $new_dispo, $new_etat, ($new_type === 'fournisseur' ? $new_commplanfourn : ''));
                if ($added) {
                    if (function_exists('sync_formation_planning_costs')) {
                        sync_formation_planning_costs($new_action);
                    }
                    $action_title = get_the_title($new_action);
                    $nom_title    = get_post_field('post_title', $new_nom);
                    $confirmations[$new_action][] = sprintf(
                        __('Ajout du %s %s le %s', 'your-text-domain'),
                        $new_type === 'formateur' ? __('formateur', 'your-text-domain') : __('fournisseur', 'your-text-domain'),
                        esc_html($nom_title),
                        esc_html($formatted_date)
                    );
                }
            }
        } elseif ($new_mode === 'periode') {
            $start_date = sanitize_text_field($post_data['new_start_date'] ?? '');
            $end_date   = sanitize_text_field($post_data['new_end_date']   ?? '');
            if (!$start_date || !$end_date) {
                echo '<div class="error"><p>' . __('Veuillez fournir une date de début et une date de fin.', 'your-text-domain') . '</p></div>';
                return;
            }
            $start_formatted = validate_and_format_date($start_date);
            $end_formatted   = validate_and_format_date($end_date);
            if (!$start_formatted || !$end_formatted) {
                echo '<div class="error"><p>' . __('L\'une des dates saisies n\'est pas valide.', 'your-text-domain') . '</p></div>';
                return;
            }
            $start_obj = DateTime::createFromFormat('d.m.Y', $start_formatted);
            $end_obj   = DateTime::createFromFormat('d.m.Y', $end_formatted);
            if ($start_obj > $end_obj) {
                echo '<div class="error"><p>' . __('La date de début doit être antérieure à la date de fin.', 'your-text-domain') . '</p></div>';
                return;
            }
            $interval   = new DateInterval('P1D');
            $date_range = new DatePeriod($start_obj, $interval, $end_obj->modify('+1 day'));
            foreach ($date_range as $d) {
                $formatted_date = $d->format('d.m.Y');
                $added = add_planning_to_action($new_action, $new_type, $new_nom, $formatted_date, $new_dispo, $new_etat, ($new_type === 'fournisseur' ? $new_commplanfourn : ''));
                if ($added) {
                    if (function_exists('sync_formation_planning_costs')) {
                        sync_formation_planning_costs($new_action);
                    }
                    $nom_title    = get_post_field('post_title', $new_nom);
                    $confirmations[$new_action][] = sprintf(
                        __('Ajout du %s %s le %s', 'your-text-domain'),
                        $new_type === 'formateur' ? __('formateur', 'your-text-domain') : __('fournisseur', 'your-text-domain'),
                        esc_html($nom_title),
                        esc_html($formatted_date)
                    );
                }
            }
        } else {
            echo '<div class="error"><p>' . __('Mode de saisie invalide.', 'your-text-domain') . '</p></div>';
            return;
        }

        // -- NOUVEAU : Stockage + redirection comme les autres
        set_transient('planning_confirmations', $confirmations, 30);
        wp_redirect($_SERVER['HTTP_REFERER']);
        exit;
    }
}

// ---------------------------------------------------------------------------
// 10. Affichage unique des confirmations via le hook admin_notices
// ---------------------------------------------------------------------------
add_action('admin_notices', function () {
    $errors = get_transient('planning_errors');
    if ($errors) {
        echo '<div class="notice notice-error is-dismissible">';
        foreach ($errors as $error) {
            echo '<p>' . esc_html($error) . '</p>';
        }
        echo '</div>';
        delete_transient('planning_errors');
    }

    $confirmations = get_transient('planning_confirmations');
    if ($confirmations) {
        echo '<div class="notice notice-success is-dismissible" id="planning-confirmations-notice">';
        foreach ($confirmations as $action_id => $messages) {
            $action_title = get_the_title($action_id);
            $edit_link = get_edit_post_link($action_id);

            if ($action_title && $edit_link) {
                echo '<p><strong><a href="' . esc_url($edit_link) . '" target="_blank" data-url="' . esc_url($edit_link) . '">' . esc_html($action_title) . '</a>:</strong></p>';
            } else {
                echo '<p><strong>' . esc_html(__('Action inconnue', 'your-text-domain')) . ':</strong></p>';
            }

            echo '<ul>';
            foreach ($messages as $message) {
                echo '<li>' . esc_html($message) . '</li>';
            }
            echo '</ul>';
        }
        echo '</div>';

        // Nettoyage
        delete_transient('planning_confirmations');

        // Script JavaScript pour ouvrir automatiquement les fenêtres
        echo "<script>
            document.addEventListener('DOMContentLoaded', function () {
                // Parcourt tous les liens avec l'attribut data-url dans la notice
                const links = document.querySelectorAll('#planning-confirmations-notice a[data-url]');
                links.forEach(link => {
                    const url = link.getAttribute('data-url');
                    if (url) {
                        // Ouvre chaque lien dans une nouvelle fenêtre
                        window.open(url, '_blank', 'width=1024,height=768,scrollbars=yes,resizable=yes');
                    }
                });
            });
        </script>";
    }
});


// ---------------------------------------------------------------------------
// 11. Fonctions utilitaires (pas modifiées sauf suppression JS dans display_confirmations)
// ---------------------------------------------------------------------------
function verify_nonce($field, $action) {
    return isset($_POST[$field]) && wp_verify_nonce($_POST[$field], $action);
}

function has_required_fields($planning, $required_fields) {
    foreach ($required_fields as $field) {
        if (!isset($planning[$field])) {
            return false;
        }
    }
    return true;
}

function get_sanitized_planning_data($planning) {
    return [
        'original_action_id' => intval($planning['original_action_id']),
        'original_nom'       => intval($planning['original_nom']),
        'original_type'      => sanitize_text_field($planning['original_type']),
        'original_date'      => sanitize_text_field($planning['original_date']),
        'original_dispo'     => sanitize_text_field($planning['original_dispo']),
        'original_etat'      => sanitize_text_field($planning['original_etat']),
        'new_action_id'      => isset($planning['new_action']) ? intval($planning['new_action']) : intval($planning['original_action_id']),
        'new_date'           => sanitize_text_field($planning['date']),
        'new_nom'            => intval($planning['nom']),
        'new_type'           => sanitize_text_field($planning['type']),
        'new_dispo'          => sanitize_text_field($planning['dispo']),
        'new_etat'           => sanitize_text_field($planning['etat']),
    ];
}

function validate_and_format_date($date) {
    $date_obj = parse_planning_date($date);
    return $date_obj ? $date_obj->format('d.m.Y') : false;
}

function parse_planning_date($date) {
    if (!$date) return null;
    $date_obj = DateTime::createFromFormat('d.m.Y', $date);
    if (!$date_obj) {
        $date_obj = DateTime::createFromFormat('d.m.y', $date);
    }
    return $date_obj ?: null;
}

function get_existing_commplanfourn($action_id, $type, $nom_id, $date) {
    if ($type !== 'fournisseur') {
        return '';
    }
    return get_commplanfourn($action_id, $date, $nom_id);
}

function get_commplanfourn($action_id, $date, $nom_id) {
    $plannings = get_post_meta($action_id, 'fsbdd_planning', true);
    if (empty($plannings) || !is_array($plannings)) {
        return '';
    }
    foreach ($plannings as $entry) {
        if (($entry['fsbdd_planjour'] ?? '') === $date) {
            if (!empty($entry['fournisseur_salle'])) {
                foreach ($entry['fournisseur_salle'] as $salle) {
                    if (($salle['fsbdd_user_foursalle'] ?? '') == $nom_id) {
                        return sanitize_text_field($salle['fsbdd_commplanfourn'] ?? '');
                    }
                }
            }
        }
    }
    return '';
}

function remove_planning_from_action($action_id, $type, $nom_id, $date) {
    $plannings = get_post_meta($action_id, 'fsbdd_planning', true);
    if (empty($plannings) || !is_array($plannings)) {
        return false;
    }
    foreach ($plannings as $p_index => &$entry) {
        if (($entry['fsbdd_planjour'] ?? '') === $date) {
            if ($type === 'formateur' && !empty($entry['fsbdd_gpformatr'])) {
                foreach ($entry['fsbdd_gpformatr'] as $f_index => $formateur) {
                    if (($formateur['fsbdd_user_formateurrel'] ?? '') == $nom_id) {
                        unset($entry['fsbdd_gpformatr'][$f_index]);
                        if (empty($entry['fsbdd_gpformatr'])) {
                            unset($entry['fsbdd_gpformatr']);
                        }
                        if (empty($entry['fsbdd_gpformatr']) && empty($entry['fournisseur_salle'])) {
                            unset($plannings[$p_index]);
                        }
                        update_post_meta($action_id, 'fsbdd_planning', $plannings);
                        return true;
                    }
                }
            } elseif ($type === 'fournisseur' && !empty($entry['fournisseur_salle'])) {
                foreach ($entry['fournisseur_salle'] as $s_index => $salle) {
                    if (($salle['fsbdd_user_foursalle'] ?? '') == $nom_id) {
                        unset($entry['fournisseur_salle'][$s_index]);
                        if (empty($entry['fournisseur_salle'])) {
                            unset($entry['fournisseur_salle']);
                        }
                        if (empty($entry['fsbdd_gpformatr']) && empty($entry['fournisseur_salle'])) {
                            unset($plannings[$p_index]);
                        }
                        update_post_meta($action_id, 'fsbdd_planning', $plannings);
                        return true;
                    }
                }
            }
        }
    }
    return false;
}

function add_planning_to_action($action_id, $type, $nom_id, $date, $dispo, $etat, $commplanfourn = '') {
    $plannings = get_post_meta($action_id, 'fsbdd_planning', true);
    if (empty($plannings) || !is_array($plannings)) {
        $plannings = [];
    }
    $found = false;
    // On vérifie si la date existe déjà
    foreach ($plannings as &$entry) {
        if (($entry['fsbdd_planjour'] ?? '') === $date) {
            if ($type === 'formateur') {
                $entry['fsbdd_gpformatr'][] = [
                    'fsbdd_user_formateurrel' => $nom_id,
                    'fsbdd_dispjourform'      => $dispo,
                    'fsbdd_okformatr'         => $etat,
                ];
            } else {
                $entry['fournisseur_salle'][] = [
                    'fsbdd_user_foursalle'     => $nom_id,
                    'fsbdd_dispjourform'       => $dispo,
                    'fsbdd_okformatr'          => $etat,
                    'fsbdd_commplanfourn'      => $commplanfourn,
                ];
            }
            $found = true;
            break;
        }
    }
    // Sinon on crée une nouvelle entrée
    if (!$found) {
        $new_entry = [
            'fsbdd_planjour'     => $date,
            'fsbdd_plannmatin'   => '08:30',
            'fsbdd_plannmatinfin'=> '12:00',
            'fsbdd_plannam'      => '13:30',
            'fsbdd_plannamfin'   => '17:00',
            'fsbdd_gpformatr'    => [],
            'fournisseur_salle'  => [],
        ];
        if ($type === 'formateur') {
            $new_entry['fsbdd_gpformatr'][] = [
                'fsbdd_user_formateurrel' => $nom_id,
                'fsbdd_dispjourform'      => $dispo,
                'fsbdd_okformatr'         => $etat,
            ];
        } else {
            $new_entry['fournisseur_salle'][] = [
                'fsbdd_user_foursalle' => $nom_id,
                'fsbdd_dispjourform'   => $dispo,
                'fsbdd_okformatr'      => $etat,
                'fsbdd_commplanfourn'  => $commplanfourn,
            ];
        }
        $plannings[] = $new_entry;
    }
    update_post_meta($action_id, 'fsbdd_planning', $plannings);
    return true;
}

function update_planning_in_action($action_id, $original_type, $original_nom, $original_date, $new_nom, $new_type, $new_date, $new_dispo, $new_etat, $new_commplanfourn = '') {
    $plannings = get_post_meta($action_id, 'fsbdd_planning', true);
    if (empty($plannings) || !is_array($plannings)) {
        return false;
    }
    foreach ($plannings as &$entry) {
        if (($entry['fsbdd_planjour'] ?? '') === $original_date) {
            if ($original_type === 'formateur' && !empty($entry['fsbdd_gpformatr'])) {
                foreach ($entry['fsbdd_gpformatr'] as &$formateur) {
                    if (($formateur['fsbdd_user_formateurrel'] ?? '') == $original_nom) {
                        $formateur['fsbdd_user_formateurrel'] = $new_nom;
                        $formateur['fsbdd_dispjourform']      = $new_dispo;
                        $formateur['fsbdd_okformatr']         = $new_etat;

                        if ($new_date !== $original_date) {
                            $entry['fsbdd_planjour'] = $new_date;
                        }
                        update_post_meta($action_id, 'fsbdd_planning', $plannings);
                        return true;
                    }
                }
            } elseif ($original_type === 'fournisseur' && !empty($entry['fournisseur_salle'])) {
                foreach ($entry['fournisseur_salle'] as &$salle) {
                    if (($salle['fsbdd_user_foursalle'] ?? '') == $original_nom) {
                        $salle['fsbdd_user_foursalle']   = $new_nom;
                        $salle['fsbdd_dispjourform']     = $new_dispo;
                        $salle['fsbdd_okformatr']        = $new_etat;
                        $salle['fsbdd_commplanfourn']    = $new_commplanfourn;

                        if ($new_date !== $original_date) {
                            $entry['fsbdd_planjour'] = $new_date;
                        }
                        update_post_meta($action_id, 'fsbdd_planning', $plannings);
                        return true;
                    }
                }
            }
        }
    }
    return false;
}

add_action('wp_ajax_check_dispo_conflict', function() {
    $nom_id = intval($_GET['nom_id']);
    $date = sanitize_text_field($_GET['date']);
    $type = sanitize_text_field($_GET['type']);

    $conflicts = [];
    $all_dispos = ['Journ', 'Matin', 'Aprem'];

    foreach ($all_dispos as $dispo) {
        if (has_planning_conflict($nom_id, $date, $dispo, $type)) {
            $conflicts[] = $dispo;
        }
    }

    wp_send_json($conflicts);
});

// ---------------------------------------------------------------------------
// Fonctions pour obtenir Formation, Lieu, Inter/Intra, Booké (pour l’affichage)
// ---------------------------------------------------------------------------
function get_planning_formation($meta) {
    $fsbdd_relsessproduit = $meta['fsbdd_relsessproduit'] ?? '';
    if (!$fsbdd_relsessproduit) {
        return '';
    }
    $product = get_post($fsbdd_relsessproduit);
    return $product ? mb_substr($product->post_title, 0, 30) : '';
}

function get_planning_lieu($meta) {
    $lieu_full = $meta['fsbdd_select_lieusession'] ?? '';
    if (!$lieu_full) {
        return '';
    }
    // on prend avant la virgule
    return strtok($lieu_full, ',');
}

function get_planning_inter_intra($meta) {
    $fsbdd_typesession = $meta['fsbdd_typesession'] ?? '';
    switch ($fsbdd_typesession) {
        case '1': return __('INTER', 'your-text-domain');
        case '2': return __('INTER à définir', 'your-text-domain');
        case '3': return __('INTRA', 'your-text-domain');
        default:  return __('Inconnu', 'your-text-domain');
    }
}

function get_planning_booke($meta) {
    $fsbdd_sessconfirm = $meta['fsbdd_sessconfirm'] ?? '';
    switch ($fsbdd_sessconfirm) {
        case '1': return __('TODO', 'your-text-domain');
        case '2': return __('NON', 'your-text-domain');
        case '3': return __('OUI', 'your-text-domain');
        case '4': return __('BOOKÉ', 'your-text-domain');
        default:  return __('Inconnu', 'your-text-domain');
    }
}

// ---------------------------------------------------------------------------
// Nouvelle fonction pour nettoyer et synchroniser les métadonnées
// ---------------------------------------------------------------------------
function clean_and_sync_plannings($action_id) {
    $plannings = get_post_meta($action_id, 'fsbdd_planning', true);
    if (empty($plannings) || !is_array($plannings)) {
        return;
    }

    foreach ($plannings as $index => &$entry) {
        // Nettoyage des groupes vides
        if (empty($entry['fsbdd_gpformatr']) && empty($entry['fournisseur_salle'])) {
            unset($plannings[$index]);
        }
    }

    // Réenregistrer les métadonnées nettoyées
    update_post_meta($action_id, 'fsbdd_planning', $plannings);
}

// ---------------------------------------------------------------------------
// Vérification des conflits renforcée pour le back-end
// ---------------------------------------------------------------------------
function has_planning_conflict($nom_id, $date, $dispo, $type, $current_data = null) {
    static $cache = [];
    $cache_key = md5("$nom_id-$date-$type");

    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    $date_obj = DateTime::createFromFormat('d.m.Y', $date);
    if (!$date_obj) return false;

    // Requête optimisée
    $args = [
        'post_type' => 'action-de-formation',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [[
            'key' => 'fsbdd_planning',
            'value' => $date,
            'compare' => 'LIKE'
        ]]
    ];

    $conflicts = [];
    foreach (get_posts($args) as $post_id) {
        $planning_data = get_post_meta($post_id, 'fsbdd_planning', true);
        foreach ($planning_data as $entry) {
            if ($entry['fsbdd_planjour'] !== $date) continue;

            $group = ($type === 'formateur') ? 
                ($entry['fsbdd_gpformatr'] ?? []) : 
                ($entry['fournisseur_salle'] ?? []);

            foreach ($group as $item) {
                $item_id = $item['fsbdd_user_formateurrel'] ?? $item['fsbdd_user_foursalle'] ?? 0;
                $existing_dispo = $item['fsbdd_dispjourform'] ?? '';

                // Ignore self-conflict
                if ($current_data && $current_data['original_nom'] == $item_id && $current_data['original_date'] == $date && $current_data['original_dispo'] == $existing_dispo) {
                    continue;
                }

                if ($item_id == $nom_id && is_conflict($dispo, $existing_dispo)) {
                    $cache[$cache_key] = true;
                    return true;
                }
            }
        }
    }

    $cache[$cache_key] = false;
    return false;
}

function is_conflict($new_dispo, $existing_dispo) {
    $conflict_matrix = [
        'Journ' => ['Journ', 'Matin', 'Aprem'],
        'Matin' => ['Journ', 'Matin'],
        'Aprem' => ['Journ', 'Aprem']
    ];
    return in_array($new_dispo, $conflict_matrix[$existing_dispo] ?? []);
}

// ---------------------------------------------------------------------------
// Affichage des erreurs et confirmations après rechargement
// ---------------------------------------------------------------------------
add_action('admin_notices', function () {
    $errors = get_transient('planning_errors');
    if ($errors) {
        echo '<div class="notice notice-error is-dismissible">';
        foreach ($errors as $error) {
            echo '<p>' . esc_html($error) . '</p>';
        }
        echo '</div>';
        delete_transient('planning_errors');
    }

    $confirmations = get_transient('planning_confirmations');
    if ($confirmations) {
        echo '<div class="notice notice-success is-dismissible">';
        foreach ($confirmations as $action_id => $messages) {
            foreach ($messages as $message) {
                echo '<p>' . esc_html($message) . '</p>';
            }
        }
        echo '</div>';
        delete_transient('planning_confirmations');
    }
});
