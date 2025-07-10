<?php
/**
 * Snippet ID: 58
 * Name: METABOX SUR LES PAGES DU CPT "action-de-formation" POUR LISTER LES DOCUMENTS
 * Description: 
 * @active false
 */

// METABOX SUR LES PAGES DU CPT "action-de-formation" POUR LISTER LES DOCUMENTS
function add_uploaded_documents_metabox_action_de_formation() {
    add_meta_box(
        'uploaded_documents',
        'Documents Formateurs',
        'render_uploaded_documents_metabox_action_de_formation',
        'action-de-formation', // Appliqué au CPT "action-de-formation"
        'normal', // Placer la metabox dans la zone principale
        'default'
    );
}
add_action('add_meta_boxes', 'add_uploaded_documents_metabox_action_de_formation');

function render_uploaded_documents_metabox_action_de_formation($post) {
    $action_id = $post->ID;
    $action_title_slug = sanitize_title(get_the_title($action_id));
    $upload_dir = WP_CONTENT_DIR . '/documents-internes';

    $formateur_dirs = glob($upload_dir . '/*', GLOB_ONLYDIR);

    if ($formateur_dirs) {
        echo '<form id="validate_documents_form" method="post" style="width: 100%;">';
        foreach ($formateur_dirs as $formateur_dir) {
            $formateur_id = basename($formateur_dir);
            $formateur_title = strtoupper(get_the_title($formateur_id));

            $action_dirs = glob($formateur_dir . '/' . $action_title_slug, GLOB_ONLYDIR);
            if ($action_dirs) {
                echo "<div style='background-color: #d5ebff; padding: 5px; margin-bottom: 5px;'><strong>$formateur_title</strong></div>";
                echo '<table style="width: 100%; border-collapse: collapse; text-align: left; margin-bottom: 10px;">';
                echo '<thead>
                    <tr>
                        <th style="border-bottom: 1px solid #d5ebff; width: 25%; padding: 5px;">Émargements</th>
                        <th style="border-bottom: 1px solid #d5ebff; width: 25%; padding: 5px;">Compte rendu F.</th>
                        <th style="border-bottom: 1px solid #d5ebff; width: 25%; padding: 5px;">Évaluations</th>
                        <th style="border-bottom: 1px solid #d5ebff; width: 25%; padding: 5px;">Autres docs</th>
                    </tr>
                </thead>';
                echo '<tbody>';
                foreach ($action_dirs as $action_dir) {
                    $files = glob($action_dir . '/*');
                    $file_data = [
                        'emargements' => [],
                        'compterenduf' => [],
                        'evaluations' => [],
                        'autres' => [],
                    ];
                    if ($files) {
                        foreach ($files as $file) {
                            $file_name = basename($file);
                            $short_name = (strlen($file_name) > 13) ? substr($file_name, 0, 13) . '...' : $file_name;

                            if (strpos($file_name, 'emargements') !== false) {
                                $file_data['emargements'][] = ['file' => $file, 'short_name' => $short_name];
                            } elseif (strpos($file_name, 'compterenduf') !== false) {
                                $file_data['compterenduf'][] = ['file' => $file, 'short_name' => $short_name];
                            } elseif (strpos($file_name, 'evaluations') !== false) {
                                $file_data['evaluations'][] = ['file' => $file, 'short_name' => $short_name];
                            } else {
                                $file_data['autres'][] = ['file' => $file, 'short_name' => $short_name];
                            }
                        }
                    }

                    echo '<tr>';
                    foreach ($file_data as $type => $files) {
                        echo '<td style="padding: 5px; vertical-align: top;">';
                        if (!empty($files)) {
                            foreach ($files as $file_info) {
                                $file = $file_info['file'];
                                $short_name = $file_info['short_name'];
                                $secure_url = add_query_arg(
                                    'fsbdd_file',
                                    urlencode(str_replace(WP_CONTENT_DIR . '/documents-internes/', '', $file)),
                                    site_url('/')
                                );

                                $meta_key_sent = '_sent_' . md5($file);
                                $meta_key_validated = '_validated_' . md5($file);

                                $send_date = get_post_meta($action_id, $meta_key_sent, true);
                                $validation_date = get_post_meta($action_id, $meta_key_validated, true);

                                echo "<div style='margin-bottom: 3px;'>";
                                echo "<input type='checkbox' name='selected_files[]' value='" . esc_attr($file) . "' id='file_$short_name'>";
                                echo "<label for='file_$short_name'><a href='$secure_url' target='_blank'>$short_name</a></label><br>";

                                if (!empty($send_date) && empty($validation_date)) {
                                    echo "<small style='color: orange;'>$send_date</small>";
                                } elseif (!empty($validation_date)) {
                                    echo "<small style='color: green;'>$send_date - Validé le $validation_date</small>";
                                } else {
                                    echo "<small style='color: red;'>Non reçu</small>";
                                }
                                echo "</div>";
                            }
                        } else {
                            echo "<small style='color: red;'>Non reçu</small>";
                        }
                        echo '</td>';
                    }
                    echo '</tr>';
                }
                echo '</tbody>';
                echo '</table>';
            }
        }
        echo '<div style="margin-top: 10px;">';
        echo '<button type="submit" name="validate_files" class="button-primary">Valider</button> ';
        echo '<button type="submit" name="delete_files" class="button-secondary" onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer les fichiers sélectionnés ?\');">Supprimer</button>';
        echo '</div>';
        echo '</form>';
    } else {
        echo '<p>Aucun document trouvé.</p>';
    }
}




add_action('save_post_action-de-formation', 'process_file_validation_action_de_formation', 10, 3);

function process_file_validation_action_de_formation($post_id, $post, $update) {
    if (!$update || empty($_POST)) {
        return;
    }

    // Vérifier les permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Gestion de la validation des fichiers
    if (isset($_POST['validate_files']) && !empty($_POST['selected_files'])) {
        $selected_files = $_POST['selected_files'];
        foreach ($selected_files as $file_path) {
            $file_path = sanitize_text_field($file_path);

            // Sauvegarder la date de validation
            update_post_meta($post_id, '_validated_' . md5($file_path), current_time('d/m/Y'));
        }
        // Ajouter un message d'administration après validation
        add_filter('redirect_post_location', function ($location) {
            return add_query_arg('files_validated', '1', $location);
        });
    }

    // Gestion de la suppression des fichiers
    if (isset($_POST['delete_files']) && !empty($_POST['selected_files'])) {
        $selected_files = $_POST['selected_files'];
        foreach ($selected_files as $file_path) {
            $file_path = sanitize_text_field($file_path);

            if (file_exists($file_path)) {
                // Supprimer le fichier du serveur
                unlink($file_path);

                // Supprimer les métadonnées associées
                delete_post_meta($post_id, '_sent_' . md5($file_path));
                delete_post_meta($post_id, '_validated_' . md5($file_path));
            }
        }
        // Ajouter un message d'administration après suppression
        add_filter('redirect_post_location', function ($location) {
            return add_query_arg('files_deleted', '1', $location);
        });
    }
}

// Ajouter des notifications après validation ou suppression
add_action('admin_notices', function () {
    if (isset($_GET['files_validated']) && $_GET['files_validated'] == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Fichiers validés avec succès.</p></div>';
    }
    if (isset($_GET['files_deleted']) && $_GET['files_deleted'] == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Fichiers supprimés avec succès.</p></div>';
    }
});
