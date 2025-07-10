<?php
/**
 * Snippet ID: 85
 * Name: test page Planning des Actions pixtral
 * Description: 
 * @active false
 */


// Hook pour ajouter une page admin personnalisée
add_action('admin_menu', 'add_custom_admin_page');

function add_custom_admin_page() {
    add_menu_page(
        'Planning des Actions de Formation',
        'Planning des Actions',
        'manage_options',
        'custom-planning-page',
        'display_custom_planning_page',
        'dashicons-calendar-alt',
        6
    );
}

function display_custom_planning_page() {
    if (isset($_POST['action']) && $_POST['action'] === 'save_planning') {
        save_planning();
    }
    ?>
    <div class="wrap">
        <h1>Planning des Actions de Formation</h1>
        <form method="post" action="">
            <input type="hidden" name="action" value="save_planning">
            <label for="filter">Afficher : </label>
            <select name="filter" id="filter">
                <option value="all" <?php echo isset($_POST['filter']) && $_POST['filter'] === 'all' ? 'selected' : ''; ?>>Tous</option>
                <option value="formateurs" <?php echo isset($_POST['filter']) && $_POST['filter'] === 'formateurs' ? 'selected' : ''; ?>>Formateurs</option>
                <option value="fournisseurs" <?php echo isset($_POST['filter']) && $_POST['filter'] === 'fournisseurs' ? 'selected' : ''; ?>>Fournisseurs / Salles</option>
            </select>
            <input type="submit" value="Filtrer">
        </form>
        <form method="post" action="">
            <input type="hidden" name="action" value="save_planning">
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th>Journée</th>
                        <th>Nom</th>
                        <th>Dispo</th>
                        <th>Etat</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Récupérer toutes les actions de formation
                    $args = array(
                        'post_type' => 'action-de-formation',
                        'posts_per_page' => -1,
                    );
                    $query = new WP_Query($args);

                    if ($query->have_posts()) {
                        while ($query->have_posts()) {
                            $query->the_post();
                            $post_id = get_the_ID();
                            $planning = get_post_meta($post_id, 'fsbdd_planning', true);

                            if (!empty($planning)) {
                                foreach ($planning as $day_index => $day_data) {
                                    $day = isset($day_data['fsbdd_planjour']) ? $day_data['fsbdd_planjour'] : '';
                                    $formateurs = isset($day_data['fsbdd_gpformatr']) ? $day_data['fsbdd_gpformatr'] : [];
                                    $fournisseurs = isset($day_data['fournisseur_salle']) ? $day_data['fournisseur_salle'] : [];

                                    $filter = isset($_POST['filter']) ? $_POST['filter'] : 'all';

                                    if ($filter === 'all' || $filter === 'formateurs') {
                                        foreach ($formateurs as $index => $formateur) {
                                            $formateur_nom = get_the_title($formateur['fsbdd_user_formateurrel']);
                                            $formateur_dispo = $formateur['fsbdd_dispjourform'];
                                            $formateur_etat = $formateur['fsbdd_okformatr'];
                                            ?>
                                            <tr class="formateur-row" data-day-index="<?php echo esc_attr($day_index); ?>">
                                                <td>
                                                    <input type="text" name="planning[<?php echo esc_attr($post_id); ?>][<?php echo esc_attr($day_index); ?>][fsbdd_planjour]" value="<?php echo esc_attr($day); ?>" class="date-input" readonly>
                                                </td>
                                                <td>
                                                    <select name="planning[<?php echo esc_attr($post_id); ?>][<?php echo esc_attr($day_index); ?>][fsbdd_gpformatr][<?php echo esc_attr($index); ?>][fsbdd_user_formateurrel]" class="nom-select">
                                                        <?php
                                                        $formateurs_list = get_posts(array('post_type' => 'formateur', 'posts_per_page' => -1));
                                                        foreach ($formateurs_list as $formateur_item) {
                                                            echo '<option value="' . esc_attr($formateur_item->ID) . '" ' . selected($formateur_nom, $formateur_item->post_title, false) . '>' . esc_html($formateur_item->post_title) . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="planning[<?php echo esc_attr($post_id); ?>][<?php echo esc_attr($day_index); ?>][fsbdd_gpformatr][<?php echo esc_attr($index); ?>][fsbdd_dispjourform]" class="dispo-select">
                                                        <option value="Journée" <?php selected($formateur_dispo, 'Journée'); ?>>Journée</option>
                                                        <option value="Matin" <?php selected($formateur_dispo, 'Matin'); ?>>Matin</option>
                                                        <option value="AM" <?php selected($formateur_dispo, 'AM'); ?>>AM</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="planning[<?php echo esc_attr($post_id); ?>][<?php echo esc_attr($day_index); ?>][fsbdd_gpformatr][<?php echo esc_attr($index); ?>][fsbdd_okformatr]" class="etat-select">
                                                        <option value="Date libérée" <?php selected($formateur_etat, 'Date libérée'); ?>>Date libérée</option>
                                                        <option value="Option" <?php selected($formateur_etat, 'Option'); ?>>Option</option>
                                                        <option value="Pré bloqué FS" <?php selected($formateur_etat, 'Pré bloqué FS'); ?>>Pré bloqué FS</option>
                                                        <option value="Réservé" <?php selected($formateur_etat, 'Réservé'); ?>>Réservé</option>
                                                        <option value="Contrat envoyé" <?php selected($formateur_etat, 'Contrat envoyé'); ?>>Contrat envoyé</option>
                                                        <option value="Contrat reçu" <?php selected($formateur_etat, 'Contrat reçu'); ?>>Contrat reçu</option>
                                                        <option value="Emargement OK" <?php selected($formateur_etat, 'Emargement OK'); ?>>Emargement OK</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <a href="<?php echo get_edit_post_link($post_id); ?>" class="action-link"><?php echo get_the_title($post_id); ?></a>
                                                    <button type="button" class="remove-button button" data-post-id="<?php echo esc_attr($post_id); ?>" data-day-index="<?php echo esc_attr($day_index); ?>" data-type="formateur" data-index="<?php echo esc_attr($index); ?>">Supprimer</button>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    }

                                    if ($filter === 'all' || $filter === 'fournisseurs') {
                                        foreach ($fournisseurs as $index => $fournisseur) {
                                            $fournisseur_nom = get_the_title($fournisseur['fsbdd_user_foursalle']);
                                            $fournisseur_dispo = $fournisseur['fsbdd_dispjourform'];
                                            $fournisseur_etat = $fournisseur['fsbdd_okformatr'];
                                            ?>
                                            <tr class="fournisseur-row" data-day-index="<?php echo esc_attr($day_index); ?>">
                                                <td>
                                                    <input type="text" name="planning[<?php echo esc_attr($post_id); ?>][<?php echo esc_attr($day_index); ?>][fsbdd_planjour]" value="<?php echo esc_attr($day); ?>" class="date-input" readonly>
                                                </td>
                                                <td>
                                                    <select name="planning[<?php echo esc_attr($post_id); ?>][<?php echo esc_attr($day_index); ?>][fournisseur_salle][<?php echo esc_attr($index); ?>][fsbdd_user_foursalle]" class="nom-select">
                                                        <?php
                                                        $fournisseurs_list = get_posts(array('post_type' => 'salle-de-formation', 'posts_per_page' => -1));
                                                        foreach ($fournisseurs_list as $fournisseur_item) {
                                                            echo '<option value="' . esc_attr($fournisseur_item->ID) . '" ' . selected($fournisseur_nom, $fournisseur_item->post_title, false) . '>' . esc_html($fournisseur_item->post_title) . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="planning[<?php echo esc_attr($post_id); ?>][<?php echo esc_attr($day_index); ?>][fournisseur_salle][<?php echo esc_attr($index); ?>][fsbdd_dispjourform]" class="dispo-select">
                                                        <option value="Journée" <?php selected($fournisseur_dispo, 'Journée'); ?>>Journée</option>
                                                        <option value="Matin" <?php selected($fournisseur_dispo, 'Matin'); ?>>Matin</option>
                                                        <option value="AM" <?php selected($fournisseur_dispo, 'AM'); ?>>AM</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="planning[<?php echo esc_attr($post_id); ?>][<?php echo esc_attr($day_index); ?>][fournisseur_salle][<?php echo esc_attr($index); ?>][fsbdd_okformatr]" class="etat-select">
                                                        <option value="Date libérée" <?php selected($fournisseur_etat, 'Date libérée'); ?>>Date libérée</option>
                                                        <option value="Option" <?php selected($fournisseur_etat, 'Option'); ?>>Option</option>
                                                        <option value="Pré bloqué FS" <?php selected($fournisseur_etat, 'Pré bloqué FS'); ?>>Pré bloqué FS</option>
                                                        <option value="Réservé" <?php selected($fournisseur_etat, 'Réservé'); ?>>Réservé</option>
                                                        <option value="Contrat envoyé" <?php selected($fournisseur_etat, 'Contrat envoyé'); ?>>Contrat envoyé</option>
                                                        <option value="Contrat reçu" <?php selected($fournisseur_etat, 'Contrat reçu'); ?>>Contrat reçu</option>
                                                        <option value="Emargement OK" <?php selected($fournisseur_etat, 'Emargement OK'); ?>>Emargement OK</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <a href="<?php echo get_edit_post_link($post_id); ?>" class="action-link"><?php echo get_the_title($post_id); ?></a>
                                                    <button type="button" class="remove-button button" data-post-id="<?php echo esc_attr($post_id); ?>" data-day-index="<?php echo esc_attr($day_index); ?>" data-type="fournisseur" data-index="<?php echo esc_attr($index); ?>">Supprimer</button>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                    ?>
                                    <tr class="action-row" data-day-index="<?php echo esc_attr($day_index); ?>">
                                        <td colspan="5">
                                            <button type="button" class="add-formateur-button button" data-post-id="<?php echo esc_attr($post_id); ?>" data-day-index="<?php echo esc_attr($day_index); ?>">Ajouter Formateur</button>
                                            <button type="button" class="add-fournisseur-button button" data-post-id="<?php echo esc_attr($post_id); ?>" data-day-index="<?php echo esc_attr($day_index); ?>">Ajouter Fournisseur</button>
                                            <button type="button" class="remove-day-button button" data-post-id="<?php echo esc_attr($post_id); ?>" data-day-index="<?php echo esc_attr($day_index); ?>">Supprimer Journée</button>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                <tr class="add-day-row">
                                    <td colspan="5">
                                        <button type="button" class="add-day-button button" data-post-id="<?php echo esc_attr($post_id); ?>">Ajouter Journée</button>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        wp_reset_postdata();
                    }
                    ?>
                </tbody>
            </table>
            <input type="submit" value="Enregistrer les modifications">
        </form>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialisation des compteurs
            var dayCounter = <?php
                $planning = get_post_meta(get_the_ID(), 'fsbdd_planning', true);
                echo is_array($planning) ? count($planning) : 0;
            ?>;
            var formateurCounter = {};
            var fournisseurCounter = {};

            // Initialiser les compteurs pour les jours existants
            <?php
            if (is_array($planning)) {
                foreach ($planning as $day_index => $day_data) {
                    echo "formateurCounter['" . esc_js($day_index) . "'] = " . count($day_data['fsbdd_gpformatr']) . ";\n";
                    echo "fournisseurCounter['" . esc_js($day_index) . "'] = " . count($day_data['fournisseur_salle']) . ";\n";
                }
            }
            ?>

            // Gestion de la suppression des éléments
            $('.remove-button').click(function() {
                var post_id = $(this).data('post-id');
                var day_index = $(this).data('day-index');
                var type = $(this).data('type');
                var index = $(this).data('index');

                $(this).closest('tr').remove();

                var input = $('<input>')
                    .attr('type', 'hidden')
                    .attr('name', 'remove_item[' + post_id + '][' + day_index + '][' + type + '][' + index + ']')
                    .val('1');
                $('form').append(input);
            });

            // Gestion de l'ajout de formateur
            $('.add-formateur-button').click(function() {
                var post_id = $(this).data('post-id');
                var day_index = $(this).data('day-index');
                if (!(day_index in formateurCounter)) {
                    formateurCounter[day_index] = 0;
                }
                var index = formateurCounter[day_index]++;

                var day = $('input[name="planning[' + post_id + '][' + day_index + '][fsbdd_planjour]"]').val();

                var newRow = '<tr class="formateur-row" data-day-index="' + day_index + '">' +
                    '<td><input type="text" name="planning[' + post_id + '][' + day_index + '][fsbdd_planjour]" value="' + day + '" class="date-input" readonly></td>' +
                    '<td><select name="planning[' + post_id + '][' + day_index + '][fsbdd_gpformatr][' + index + '][fsbdd_user_formateurrel]" class="nom-select">' +
                    '<?php
                    $formateurs_list = get_posts(array('post_type' => 'formateur', 'posts_per_page' => -1));
                    foreach ($formateurs_list as $formateur_item) {
                        echo '<option value="' . esc_attr($formateur_item->ID) . '">' . esc_html($formateur_item->post_title) . '</option>';
                    }
                    ?>' +
                    '</select></td>' +
                    '<td><select name="planning[' + post_id + '][' + day_index + '][fsbdd_gpformatr][' + index + '][fsbdd_dispjourform]" class="dispo-select">' +
                    '<option value="Journée">Journée</option>' +
                    '<option value="Matin">Matin</option>' +
                    '<option value="AM">AM</option>' +
                    '</select></td>' +
                    '<td><select name="planning[' + post_id + '][' + day_index + '][fsbdd_gpformatr][' + index + '][fsbdd_okformatr]" class="etat-select">' +
                    '<option value="Date libérée">Date libérée</option>' +
                    '<option value="Option">Option</option>' +
                    '<option value="Pré bloqué FS">Pré bloqué FS</option>' +
                    '<option value="Réservé">Réservé</option>' +
                    '<option value="Contrat envoyé">Contrat envoyé</option>' +
                    '<option value="Contrat reçu">Contrat reçu</option>' +
                    '<option value="Emargement OK">Emargement OK</option>' +
                    '</select></td>' +
                    '<td><a href="<?php echo get_edit_post_link(get_the_ID()); ?>" class="action-link"><?php echo get_the_title(get_the_ID()); ?></a>' +
                    '<button type="button" class="remove-button button" data-post-id="' + post_id + '" data-day-index="' + day_index + '" data-type="formateur" data-index="' + index + '">Supprimer</button>' +
                    '</td>' +
                    '</tr>';

                $(this).closest('tr').before(newRow);
            });

            // Gestion de l'ajout de fournisseur
            $('.add-fournisseur-button').click(function() {
                var post_id = $(this).data('post-id');
                var day_index = $(this).data('day-index');
                if (!(day_index in fournisseurCounter)) {
                    fournisseurCounter[day_index] = 0;
                }
                var index = fournisseurCounter[day_index]++;

                var day = $('input[name="planning[' + post_id + '][' + day_index + '][fsbdd_planjour]"]').val();

                var newRow = '<tr class="fournisseur-row" data-day-index="' + day_index + '">' +
                    '<td><input type="text" name="planning[' + post_id + '][' + day_index + '][fsbdd_planjour]" value="' + day + '" class="date-input" readonly></td>' +
                    '<td><select name="planning[' + post_id + '][' + day_index + '][fournisseur_salle][' + index + '][fsbdd_user_foursalle]" class="nom-select">' +
                    '<?php
                    $fournisseurs_list = get_posts(array('post_type' => 'salle-de-formation', 'posts_per_page' => -1));
                    foreach ($fournisseurs_list as $fournisseur_item) {
                        echo '<option value="' . esc_attr($fournisseur_item->ID) . '">' . esc_html($fournisseur_item->post_title) . '</option>';
                    }
                    ?>' +
                    '</select></td>' +
                    '<td><select name="planning[' + post_id + '][' + day_index + '][fournisseur_salle][' + index + '][fsbdd_dispjourform]" class="dispo-select">' +
                    '<option value="Journée">Journée</option>' +
                    '<option value="Matin">Matin</option>' +
                    '<option value="AM">AM</option>' +
                    '</select></td>' +
                    '<td><select name="planning[' + post_id + '][' + day_index + '][fournisseur_salle][' + index + '][fsbdd_okformatr]" class="etat-select">' +
                    '<option value="Date libérée">Date libérée</option>' +
                    '<option value="Option">Option</option>' +
                    '<option value="Pré bloqué FS">Pré bloqué FS</option>' +
                    '<option value="Réservé">Réservé</option>' +
                    '<option value="Contrat envoyé">Contrat envoyé</option>' +
                    '<option value="Contrat reçu">Contrat reçu</option>' +
                    '<option value="Emargement OK">Emargement OK</option>' +
                    '</select></td>' +
                    '<td><a href="<?php echo get_edit_post_link(get_the_ID()); ?>" class="action-link"><?php echo get_the_title(get_the_ID()); ?></a>' +
                    '<button type="button" class="remove-button button" data-post-id="' + post_id + '" data-day-index="' + day_index + '" data-type="fournisseur" data-index="' + index + '">Supprimer</button>' +
                    '</td>' +
                    '</tr>';

                $(this).closest('tr').before(newRow);
            });

            // Gestion de la suppression des journées
            $('.remove-day-button').click(function() {
                var post_id = $(this).data('post-id');
                var day_index = $(this).data('day-index');

                // Supprimer toutes les lignes associées à ce day_index
                $(this).closest('tr').prevAll('tr').filter(function() {
                    return $(this).data('day-index') === day_index;
                }).remove();

                $(this).closest('tr').remove();

                var input = $('<input>')
                    .attr('type', 'hidden')
                    .attr('name', 'remove_day[' + post_id + '][' + day_index + ']')
                    .val('1');
                $('form').append(input);
            });

            // Gestion de l'ajout de journée
            $('.add-day-button').click(function() {
                var post_id = $(this).data('post-id');
                var unique_day_index = 'day_' + dayCounter++;

                var newDay = '<tr class="formateur-row" data-day-index="' + unique_day_index + '">' +
                    '<td><input type="text" name="planning[' + post_id + '][' + unique_day_index + '][fsbdd_planjour]" class="date-input"></td>' +
                    '<td><select name="planning[' + post_id + '][' + unique_day_index + '][fsbdd_gpformatr][' + 0 + '][fsbdd_user_formateurrel]" class="nom-select">' +
                    '<?php
                    $formateurs_list = get_posts(array('post_type' => 'formateur', 'posts_per_page' => -1));
                    foreach ($formateurs_list as $formateur_item) {
                        echo '<option value="' . esc_attr($formateur_item->ID) . '">' . esc_html($formateur_item->post_title) . '</option>';
                    }
                    ?>' +
                    '</select></td>' +
                    '<td><select name="planning[' + post_id + '][' + unique_day_index + '][fsbdd_gpformatr][' + 0 + '][fsbdd_dispjourform]" class="dispo-select">' +
                    '<option value="Journée">Journée</option>' +
                    '<option value="Matin">Matin</option>' +
                    '<option value="AM">AM</option>' +
                    '</select></td>' +
                    '<td><select name="planning[' + post_id + '][' + unique_day_index + '][fsbdd_gpformatr][' + 0 + '][fsbdd_okformatr]" class="etat-select">' +
                    '<option value="Date libérée">Date libérée</option>' +
                    '<option value="Option">Option</option>' +
                    '<option value="Pré bloqué FS">Pré bloqué FS</option>' +
                    '<option value="Réservé">Réservé</option>' +
                    '<option value="Contrat envoyé">Contrat envoyé</option>' +
                    '<option value="Contrat reçu">Contrat reçu</option>' +
                    '<option value="Emargement OK">Emargement OK</option>' +
                    '</select></td>' +
                    '<td><a href="<?php echo get_edit_post_link(get_the_ID()); ?>" class="action-link"><?php echo get_the_title(get_the_ID()); ?></a>' +
                    '<button type="button" class="remove-button button" data-post-id="' + post_id + '" data-day-index="' + unique_day_index + '" data-type="formateur" data-index="0">Supprimer</button>' +
                    '</td>' +
                    '</tr>' +
                    '<tr class="action-row" data-day-index="' + unique_day_index + '">' +
                    '<td colspan="5">' +
                    '<button type="button" class="add-formateur-button button" data-post-id="' + post_id + '" data-day-index="' + unique_day_index + '">Ajouter Formateur</button>' +
                    '<button type="button" class="add-fournisseur-button button" data-post-id="' + post_id + '" data-day-index="' + unique_day_index + '">Ajouter Fournisseur</button>' +
                    '<button type="button" class="remove-day-button button" data-post-id="' + post_id + '" data-day-index="' + unique_day_index + '">Supprimer Journée</button>' +
                    '</td>' +
                    '</tr>';

                $(this).closest('tr').before(newDay);

                // Initialiser les compteurs pour le nouveau jour
                formateurCounter[unique_day_index] = 1;
                fournisseurCounter[unique_day_index] = 1;
            });
        });
    </script>
    <?php
}

// Fonction pour enregistrer les modifications
function save_planning() {
    if (isset($_POST['planning'])) {
        foreach ($_POST['planning'] as $post_id => $days) {
            $planning = array();
            foreach ($days as $day_index => $day_data) {
                if (isset($day_data['fsbdd_planjour'])) {
                    $planning[$day_index] = array(
                        'fsbdd_planjour'     => sanitize_text_field($day_data['fsbdd_planjour']),
                        'fsbdd_plannmatin'   => '08:30',
                        'fsbdd_plannmatinfin' => '12:00',
                        'fsbdd_plannam'      => '13:30',
                        'fsbdd_plannamfin'   => '17:00',
                        'fsbdd_gpformatr'     => array(),
                        'fournisseur_salle'   => array()
                    );

                    if (isset($day_data['fsbdd_gpformatr'])) {
                        foreach ($day_data['fsbdd_gpformatr'] as $formateur) {
                            if (isset($formateur['fsbdd_user_formateurrel']) && !empty($formateur['fsbdd_user_formateurrel'])) {
                                $planning[$day_index]['fsbdd_gpformatr'][] = array(
                                    'fsbdd_user_formateurrel' => intval($formateur['fsbdd_user_formateurrel']),
                                    'fsbdd_dispjourform'     => sanitize_text_field($formateur['fsbdd_dispjourform']),
                                    'fsbdd_okformatr'        => sanitize_text_field($formateur['fsbdd_okformatr'])
                                );
                            }
                        }
                    }

                    if (isset($day_data['fournisseur_salle'])) {
                        foreach ($day_data['fournisseur_salle'] as $fournisseur) {
                            if (isset($fournisseur['fsbdd_user_foursalle']) && !empty($fournisseur['fsbdd_user_foursalle'])) {
                                $planning[$day_index]['fournisseur_salle'][] = array(
                                    'fsbdd_user_foursalle' => intval($fournisseur['fsbdd_user_foursalle']),
                                    'fsbdd_dispjourform'   => sanitize_text_field($fournisseur['fsbdd_dispjourform']),
                                    'fsbdd_okformatr'      => sanitize_text_field($fournisseur['fsbdd_okformatr'])
                                );
                            }
                        }
                    }
                }
            }

            // Gestion des suppressions de formateurs et fournisseurs
            if (isset($_POST['remove_item'][$post_id])) {
                foreach ($_POST['remove_item'][$post_id] as $day_index => $items) {
                    foreach ($items as $type => $indices) {
                        foreach ($indices as $index => $value) {
                            if ($type === 'formateur' && isset($planning[$day_index]['fsbdd_gpformatr'][$index])) {
                                unset($planning[$day_index]['fsbdd_gpformatr'][$index]);
                                $planning[$day_index]['fsbdd_gpformatr'] = array_values($planning[$day_index]['fsbdd_gpformatr']);
                            } elseif ($type === 'fournisseur' && isset($planning[$day_index]['fournisseur_salle'][$index])) {
                                unset($planning[$day_index]['fournisseur_salle'][$index]);
                                $planning[$day_index]['fournisseur_salle'] = array_values($planning[$day_index]['fournisseur_salle']);
                            }
                        }
                    }
                }
            }

            // Gestion des suppressions de journées
            if (isset($_POST['remove_day'][$post_id])) {
                foreach ($_POST['remove_day'][$post_id] as $day_index => $value) {
                    unset($planning[$day_index]);
                }
                // Réindexer le tableau si nécessaire
                $planning = array_values($planning);
            }

            update_post_meta($post_id, 'fsbdd_planning', $planning);
        }

        echo '<div class="updated"><p>Les modifications ont été enregistrées avec succès.</p></div>';
    }
}
