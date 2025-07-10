<?php
/**
 * Snippet ID: 87
 * Name: test planning v3
 * Description: 
 * @active false
 */


// Ajouter une page d'administration personnalisée
add_action('admin_menu', 'add_custom_planning_page');
function add_custom_planning_page() {
    add_menu_page(
        'Gestion du Planning des Actions de Formation',
        'Gestion Planning',
        'manage_options',
        'custom-planning-management',
        'custom_planning_page_content',
        'dashicons-calendar-alt',
        6
    );
}

// Contenu de la page d'administration personnalisée
function custom_planning_page_content() {
    ?>
    <div class="wrap">
        <h1>Gestion du Planning des Actions de Formation</h1>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="save_custom_planning_data" />
            <?php
            // Récupérer tous les CPT "action-de-formation" avec des dates de planning
            $args = array(
                'post_type' => 'action-de-formation',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'fsbdd_planning',
                        'compare' => 'EXISTS',
                    ),
                ),
            );
            $query = new WP_Query($args);

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $post_id = get_the_ID();
                    $planning_data = get_post_meta($post_id, 'fsbdd_planning', true);
                    ?>
                    <h2><?php the_title(); ?></h2>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Formateurs</th>
                                <th>Fournisseur / Salle</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($planning_data)) {
                                foreach ($planning_data as $index => $day) {
                                    ?>
                                    <tr>
                                        <td>
                                            <input type="text" name="planning[<?php echo $post_id; ?>][<?php echo $index; ?>][date]" value="<?php echo esc_attr($day['fsbdd_planjour']); ?>" />
                                            <input type="hidden" name="planning[<?php echo $post_id; ?>][<?php echo $index; ?>][fsbdd_plannmatin]" value="<?php echo esc_attr($day['fsbdd_plannmatin']); ?>" />
                                            <input type="hidden" name="planning[<?php echo $post_id; ?>][<?php echo $index; ?>][fsbdd_plannmatinfin]" value="<?php echo esc_attr($day['fsbdd_plannmatinfin']); ?>" />
                                            <input type="hidden" name="planning[<?php echo $post_id; ?>][<?php echo $index; ?>][fsbdd_plannam]" value="<?php echo esc_attr($day['fsbdd_plannam']); ?>" />
                                            <input type="hidden" name="planning[<?php echo $post_id; ?>][<?php echo $index; ?>][fsbdd_plannamfin]" value="<?php echo esc_attr($day['fsbdd_plannamfin']); ?>" />
                                        </td>
                                        <td>
                                            <?php
                                            if (!empty($day['fsbdd_gpformatr'])) {
                                                foreach ($day['fsbdd_gpformatr'] as $formateur_index => $formateur) {
                                                    ?>
                                                    <div>
                                                        <select name="planning[<?php echo $post_id; ?>][<?php echo $index; ?>][formateurs][<?php echo $formateur_index; ?>][formateur]">
                                                            <?php
                                                            $formateurs = get_posts(array('post_type' => 'formateur', 'posts_per_page' => -1));
                                                            foreach ($formateurs as $f) {
                                                                ?>
                                                                <option value="<?php echo $f->ID; ?>" <?php selected($formateur['fsbdd_user_formateurrel'], $f->ID); ?>><?php echo esc_html($f->post_title); ?></option>
                                                                <?php
                                                            }
                                                            ?>
                                                        </select>
                                                        <select name="planning[<?php echo $post_id; ?>][<?php echo $index; ?>][formateurs][<?php echo $formateur_index; ?>][dispo]">
                                                            <option value="Journée" <?php selected($formateur['fsbdd_dispjourform'], 'Journée'); ?>>Journée</option>
                                                            <option value="Matin" <?php selected($formateur['fsbdd_dispjourform'], 'Matin'); ?>>Matin</option>
                                                            <option value="AM" <?php selected($formateur['fsbdd_dispjourform'], 'AM'); ?>>AM</option>
                                                        </select>
                                                        <select name="planning[<?php echo $post_id; ?>][<?php echo $index; ?>][formateurs][<?php echo $formateur_index; ?>][etat]">
                                                            <option value="Date libérée" <?php selected($formateur['fsbdd_okformatr'], 'Date libérée'); ?>>Date libérée</option>
                                                            <option value="Option" <?php selected($formateur['fsbdd_okformatr'], 'Option'); ?>>Option</option>
                                                            <option value="Pré bloqué FS" <?php selected($formateur['fsbdd_okformatr'], 'Pré bloqué FS'); ?>>Pré bloqué FS</option>
                                                            <option value="Réservé" <?php selected($formateur['fsbdd_okformatr'], 'Réservé'); ?>>Réservé</option>
                                                            <option value="Contrat envoyé" <?php selected($formateur['fsbdd_okformatr'], 'Contrat envoyé'); ?>>Contrat envoyé</option>
                                                            <option value="Contrat reçu" <?php selected($formateur['fsbdd_okformatr'], 'Contrat reçu'); ?>>Contrat reçu</option>
                                                            <option value="Emargement OK" <?php selected($formateur['fsbdd_okformatr'], 'Emargement OK'); ?>>Emargement OK</option>
                                                        </select>
                                                        <button type="button" class="remove-formateur" data-index="<?php echo $formateur_index; ?>">Supprimer</button>
                                                    </div>
                                                    <?php
                                                }
                                            }
                                            ?>
                                            <button type="button" class="add-formateur" data-index="<?php echo $index; ?>" data-post-id="<?php echo $post_id; ?>">Ajouter Formateur</button>
                                        </td>
                                        <td>
                                            <?php
                                            if (!empty($day['fournisseur_salle'])) {
                                                foreach ($day['fournisseur_salle'] as $salle_index => $salle) {
                                                    ?>
                                                    <div>
                                                        <select name="planning[<?php echo $post_id; ?>][<?php echo $index; ?>][salles][<?php echo $salle_index; ?>][salle]">
                                                            <?php
                                                            $salles = get_posts(array('post_type' => 'salle-de-formation', 'posts_per_page' => -1));
                                                            foreach ($salles as $s) {
                                                                ?>
                                                                <option value="<?php echo $s->ID; ?>" <?php selected($salle['fsbdd_user_foursalle'], $s->ID); ?>><?php echo esc_html($s->post_title); ?></option>
                                                                <?php
                                                            }
                                                            ?>
                                                        </select>
                                                        <select name="planning[<?php echo $post_id; ?>][<?php echo $index; ?>][salles][<?php echo $salle_index; ?>][dispo]">
                                                            <option value="Journée" <?php selected($salle['fsbdd_dispjourform'], 'Journée'); ?>>Journée</option>
                                                            <option value="Matin" <?php selected($salle['fsbdd_dispjourform'], 'Matin'); ?>>Matin</option>
                                                            <option value="AM" <?php selected($salle['fsbdd_dispjourform'], 'AM'); ?>>AM</option>
                                                        </select>
                                                        <select name="planning[<?php echo $post_id; ?>][<?php echo $index; ?>][salles][<?php echo $salle_index; ?>][etat]">
                                                            <option value="Date libérée" <?php selected($salle['fsbdd_okformatr'], 'Date libérée'); ?>>Date libérée</option>
                                                            <option value="Option" <?php selected($salle['fsbdd_okformatr'], 'Option'); ?>>Option</option>
                                                            <option value="Pré bloqué FS" <?php selected($salle['fsbdd_okformatr'], 'Pré bloqué FS'); ?>>Pré bloqué FS</option>
                                                            <option value="Réservé" <?php selected($salle['fsbdd_okformatr'], 'Réservé'); ?>>Réservé</option>
                                                            <option value="Contrat envoyé" <?php selected($salle['fsbdd_okformatr'], 'Contrat envoyé'); ?>>Contrat envoyé</option>
                                                            <option value="Contrat reçu" <?php selected($salle['fsbdd_okformatr'], 'Contrat reçu'); ?>>Contrat reçu</option>
                                                            <option value="Emargement OK" <?php selected($salle['fsbdd_okformatr'], 'Emargement OK'); ?>>Emargement OK</option>
                                                        </select>
                                                        <button type="button" class="remove-salle" data-index="<?php echo $salle_index; ?>">Supprimer</button>
                                                    </div>
                                                    <?php
                                                }
                                            }
                                            ?>
                                            <button type="button" class="add-salle" data-index="<?php echo $index; ?>" data-post-id="<?php echo $post_id; ?>">Ajouter Fournisseur</button>
                                        </td>
                                        <td>
                                            <button type="button" class="remove-day" data-index="<?php echo $index; ?>" data-post-id="<?php echo $post_id; ?>">Supprimer Journée</button>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                    <button type="button" class="add-day" data-post-id="<?php echo $post_id; ?>">Ajouter Journée</button>
                    <?php
                }
            } else {
                echo '<p>Aucune action de formation trouvée avec des dates de planning.</p>';
            }
            wp_reset_postdata();
            ?>
            <input type="submit" name="submit_planning" value="Enregistrer le planning" class="button button-primary" />
        </form>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Ajouter une journée
        $('.add-day').on('click', function() {
            var post_id = $(this).data('post-id');
            var index = $(this).closest('table').find('tbody tr').length;
            var newRow = `
                <tr>
                    <td>
                        <input type="text" name="planning[${post_id}][${index}][date]" />
                        <input type="hidden" name="planning[${post_id}][${index}][fsbdd_plannmatin]" value="08:30" />
                        <input type="hidden" name="planning[${post_id}][${index}][fsbdd_plannmatinfin]" value="12:00" />
                        <input type="hidden" name="planning[${post_id}][${index}][fsbdd_plannam]" value="13:30" />
                        <input type="hidden" name="planning[${post_id}][${index}][fsbdd_plannamfin]" value="17:00" />
                    </td>
                    <td><button type="button" class="add-formateur" data-index="${index}" data-post-id="${post_id}">Ajouter Formateur</button></td>
                    <td><button type="button" class="add-salle" data-index="${index}" data-post-id="${post_id}">Ajouter Fournisseur</button></td>
                    <td><button type="button" class="remove-day" data-index="${index}" data-post-id="${post_id}">Supprimer</button></td>
                </tr>
            `;
            $(this).closest('table').find('tbody').append(newRow);
        });

        // Supprimer une journée
        $(document).on('click', '.remove-day', function() {
            var index = $(this).data('index');
            var post_id = $(this).data('post-id');
            $(this).closest('tr').remove();
        });

        // Ajouter un formateur
        $(document).on('click', '.add-formateur', function() {
            var index = $(this).data('index');
            var post_id = $(this).data('post-id');
            var formateurIndex = $(this).closest('td').find('div').length;
            var newFormateur = `
                <div>
                    <select name="planning[${post_id}][${index}][formateurs][${formateurIndex}][formateur]">
                        <?php
                        $formateurs = get_posts(array('post_type' => 'formateur', 'posts_per_page' => -1));
                        foreach ($formateurs as $f) {
                            echo '<option value="' . $f->ID . '">' . esc_html($f->post_title) . '</option>';
                        }
                        ?>
                    </select>
                    <select name="planning[${post_id}][${index}][formateurs][${formateurIndex}][dispo]">
                        <option value="Journée">Journée</option>
                        <option value="Matin">Matin</option>
                        <option value="AM">AM</option>
                    </select>
                    <select name="planning[${post_id}][${index}][formateurs][${formateurIndex}][etat]">
                        <option value="Date libérée">Date libérée</option>
                        <option value="Option">Option</option>
                        <option value="Pré bloqué FS">Pré bloqué FS</option>
                        <option value="Réservé">Réservé</option>
                        <option value="Contrat envoyé">Contrat envoyé</option>
                        <option value="Contrat reçu">Contrat reçu</option>
                        <option value="Emargement OK">Emargement OK</option>
                    </select>
                    <button type="button" class="remove-formateur" data-index="${formateurIndex}">Supprimer</button>
                </div>
            `;
            $(this).closest('td').append(newFormateur);
        });

        // Supprimer un formateur
        $(document).on('click', '.remove-formateur', function() {
            var index = $(this).data('index');
            $(this).closest('div').remove();
        });

        // Ajouter une salle
        $(document).on('click', '.add-salle', function() {
            var index = $(this).data('index');
            var post_id = $(this).data('post-id');
            var salleIndex = $(this).closest('td').find('div').length;
            var newSalle = `
                <div>
                    <select name="planning[${post_id}][${index}][salles][${salleIndex}][salle]">
                        <?php
                        $salles = get_posts(array('post_type' => 'salle-de-formation', 'posts_per_page' => -1));
                        foreach ($salles as $s) {
                            echo '<option value="' . $s->ID . '">' . esc_html($s->post_title) . '</option>';
                        }
                        ?>
                    </select>
                    <select name="planning[${post_id}][${index}][salles][${salleIndex}][dispo]">
                        <option value="Journée">Journée</option>
                        <option value="Matin">Matin</option>
                        <option value="AM">AM</option>
                    </select>
                    <select name="planning[${post_id}][${index}][salles][${salleIndex}][etat]">
                        <option value="Date libérée">Date libérée</option>
                        <option value="Option">Option</option>
                        <option value="Pré bloqué FS">Pré bloqué FS</option>
                        <option value="Réservé">Réservé</option>
                        <option value="Contrat envoyé">Contrat envoyé</option>
                        <option value="Contrat reçu">Contrat reçu</option>
                        <option value="Emargement OK">Emargement OK</option>
                    </select>
                    <button type="button" class="remove-salle" data-index="${salleIndex}">Supprimer</button>
                </div>
            `;
            $(this).closest('td').append(newSalle);
        });

        // Supprimer une salle
        $(document).on('click', '.remove-salle', function() {
            var index = $(this).data('index');
            $(this).closest('div').remove();
        });
    });
    </script>
    <?php
}

// Traitement du formulaire
add_action('admin_post_save_custom_planning_data', 'save_custom_planning_data');
function save_custom_planning_data() {
    if (isset($_POST['planning'])) {
        foreach ($_POST['planning'] as $post_id => $days) {
            $planning_data = [];
            foreach ($days as $index => $day) {
                $planning_data[] = [
                    'fsbdd_planjour' => sanitize_text_field($day['date']),
                    'fsbdd_plannmatin' => isset($day['fsbdd_plannmatin']) ? sanitize_text_field($day['fsbdd_plannmatin']) : '08:30',
                    'fsbdd_plannmatinfin' => isset($day['fsbdd_plannmatinfin']) ? sanitize_text_field($day['fsbdd_plannmatinfin']) : '12:00',
                    'fsbdd_plannam' => isset($day['fsbdd_plannam']) ? sanitize_text_field($day['fsbdd_plannam']) : '13:30',
                    'fsbdd_plannamfin' => isset($day['fsbdd_plannamfin']) ? sanitize_text_field($day['fsbdd_plannamfin']) : '17:00',
                    'fsbdd_gpformatr' => [],
                    'fournisseur_salle' => [],
                ];

                if (!empty($day['formateurs'])) {
                    foreach ($day['formateurs'] as $formateur) {
                        $planning_data[count($planning_data) - 1]['fsbdd_gpformatr'][] = [
                            'fsbdd_user_formateurrel' => intval($formateur['formateur']),
                            'fsbdd_dispjourform' => sanitize_text_field($formateur['dispo']),
                            'fsbdd_okformatr' => sanitize_text_field($formateur['etat']),
                        ];
                    }
                }

                if (!empty($day['salles'])) {
                    foreach ($day['salles'] as $salle) {
                        $planning_data[count($planning_data) - 1]['fournisseur_salle'][] = [
                            'fsbdd_user_foursalle' => intval($salle['salle']),
                            'fsbdd_dispjourform' => sanitize_text_field($salle['dispo']),
                            'fsbdd_okformatr' => sanitize_text_field($salle['etat']),
                        ];
                    }
                }
            }
            update_post_meta($post_id, 'fsbdd_planning', $planning_data);
        }
    }
    wp_redirect(admin_url('admin.php?page=custom-planning-management'));
    exit;
}
