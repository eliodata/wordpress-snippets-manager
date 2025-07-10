<?php
/**
 * Snippet ID: 80
 * Name: ADMIN COLUMN CPT formateur COLONNE dates planning
 * Description: 
 * @active true
 */

// Ajouter une colonne personnalisée pour afficher les dates associées au formateur
add_filter('manage_formateur_posts_columns', 'add_formateur_dates_column');
function add_formateur_dates_column($columns) {
    $columns['formateur_dates'] = __('Dates de formation', 'text-domain');
    return $columns;
}

// Remplir la colonne des dates pour le formateur
add_action('manage_formateur_posts_custom_column', 'fill_formateur_dates_column', 10, 2);
function fill_formateur_dates_column($column, $post_id) {
    if ($column === 'formateur_dates') {
        // Identifier le formateur par son ID
        $formateur_id = $post_id;

        // Rechercher toutes les actions de formation (CPT action-de-formation) liées
        $args = [
            'post_type' => 'action-de-formation',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'fsbdd_planning',
                    'compare' => 'EXISTS'
                ]
            ]
        ];
        $query = new WP_Query($args);

        if ($query->have_posts()) {
            $dates = [];

            while ($query->have_posts()) {
                $query->the_post();
                $planning = get_post_meta(get_the_ID(), 'fsbdd_planning', true);

                if ($planning && is_array($planning)) {
                    foreach ($planning as $day) {
                        if (isset($day['fsbdd_gpformatr']) && is_array($day['fsbdd_gpformatr'])) {
                            foreach ($day['fsbdd_gpformatr'] as $formateur) {
                                if (isset($formateur['fsbdd_user_formateurrel']) && $formateur['fsbdd_user_formateurrel'] == $formateur_id) {
                                    // Ajouter la date au tableau si elle est définie
                                    if (isset($day['fsbdd_planjour'])) {
                                        $date = date('d/m/Y', strtotime($day['fsbdd_planjour']));
                                        $dates[] = $date;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            wp_reset_postdata();

            // Afficher les dates uniques, une par ligne
            if (!empty($dates)) {
                $unique_dates = array_unique($dates);
                echo implode('<br>', $unique_dates);
            } else {
                echo __('Aucune date associée.', 'text-domain');
            }
        } else {
            echo __('Aucune action de formation trouvée.', 'text-domain');
        }
    }
}
