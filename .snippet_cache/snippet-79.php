<?php
/**
 * Snippet ID: 79
 * Name: ADMIN COLUMN CPT action de formation COLONNE PLANNING FORMATEURS
 * Description: 
 * @active true
 */

// Ajouter une colonne personnalisée pour afficher les formateurs uniquement
add_filter('manage_action-de-formation_posts_columns', 'add_formateurs_column');
function add_formateurs_column($columns) {
    $columns['formateurs_planning'] = __('Formateurs', 'text-domain');
    return $columns;
}

// Remplir la colonne des formateurs
add_action('manage_action-de-formation_posts_custom_column', 'fill_formateurs_column', 10, 2);
function fill_formateurs_column($column, $post_id) {
    if ($column === 'formateurs_planning') {
        // Récupérer les métadonnées "fsbdd_planning"
        $planning = get_post_meta($post_id, 'fsbdd_planning', true);

        if ($planning && is_array($planning)) {
            $output = [];

            foreach ($planning as $day) {
                $day_output = [];

                if (isset($day['fsbdd_gpformatr']) && is_array($day['fsbdd_gpformatr'])) {
                    foreach ($day['fsbdd_gpformatr'] as $formateur) {
                        // Récupérer les informations du formateur
                        $formateur_titre = isset($formateur['fsbdd_user_formateurrel']) ? get_the_title($formateur['fsbdd_user_formateurrel']) : 'Non défini';
                        $dispo = isset($formateur['fsbdd_dispjourform']) ? $formateur['fsbdd_dispjourform'] : '';
                        $etat = isset($formateur['fsbdd_okformatr']) ? $formateur['fsbdd_okformatr'] : '';

                        // Ajouter au tableau de résultats pour cette journée
                        $day_output[] = sprintf('%s, %s, %s', esc_html($formateur_titre), esc_html($dispo), esc_html($etat));
                    }
                }

                // Ajouter les formateurs de la journée au tableau principal
                if (!empty($day_output)) {
                    $output[] = implode(' | ', $day_output);
                }
            }

            // Afficher les résultats avec un diviseur entre chaque journée
            if (!empty($output)) {
                echo implode('<div class="ac-mb-divider"></div>', $output);
            } else {
                echo __('Aucun formateur défini.', 'text-domain');
            }
        } else {
            echo __('Aucun planning défini.', 'text-domain');
        }
    }
}

// Rendre la colonne "formateurs_planning" éditable
add_filter('ac/column/editable', 'make_formateurs_column_editable', 10, 2);
function make_formateurs_column_editable($editable, $column) {
    if ($column->get_meta_key() === 'formateurs_planning') {
        $editable = true;
    }
    return $editable;
}

// Gérer la sauvegarde des modifications pour la colonne "formateurs_planning"
add_action('acp/editing/save', 'save_formateurs_column_changes', 10, 3);
function save_formateurs_column_changes($post_id, $value, $column) {
    if ($column->get_meta_key() === 'formateurs_planning') {
        // Récupérer le planning existant
        $planning = get_post_meta($post_id, 'fsbdd_planning', true);

        if ($planning && is_array($planning)) {
            $updated_planning = $planning;

            // Parse the updated value (sent as a single string)
            $lines = explode('<div class="ac-mb-divider"></div>', $value);
            foreach ($lines as $index => $line) {
                // Diviser les formateurs d'une journée
                $formateurs = explode(' | ', $line);
                foreach ($formateurs as $formateur_data) {
                    // Diviser les données individuelles du formateur
                    $formateur_parts = explode(', ', $formateur_data);
                    if (count($formateur_parts) === 3) {
                        $formateur_titre = $formateur_parts[0];
                        $dispo = $formateur_parts[1];
                        $etat = $formateur_parts[2];

                        // Mettre à jour les données du formateur dans le planning
                        if (isset($updated_planning[$index]['fsbdd_gpformatr'][$index])) {
                            $updated_planning[$index]['fsbdd_gpformatr'][$index]['fsbdd_user_formateurrel'] = $formateur_titre;
                            $updated_planning[$index]['fsbdd_gpformatr'][$index]['fsbdd_dispjourform'] = $dispo;
                            $updated_planning[$index]['fsbdd_gpformatr'][$index]['fsbdd_okformatr'] = $etat;
                        }
                    }
                }
            }

            // Sauvegarder les métadonnées mises à jour
            update_post_meta($post_id, 'fsbdd_planning', $updated_planning);
        }
    }
}
