<?php
/**
 * Snippet ID: 60
 * Name: Callback PHP afficher metabox liste documents formateur dans html metabox.io
 * Description: <p>Ajoutez cette fonction à votre thème ou plugin pour que le champ Custom HTML affiche le contenu de votre metabox.</p>
 * @active true
 */

/**
 * Constantes pour les chemins
 */
define('FSBDD_UPLOAD_DIR_NAME', 'documents-internes');
define('FSBDD_UPLOAD_DIR_PATH', WP_CONTENT_DIR . '/' . FSBDD_UPLOAD_DIR_NAME);

/**
 * Helper: Récupérer les IDs des formateurs pour une action de formation
 */
function fsbdd_get_action_formateur_ids($action_id) {
    $formateur_ids = [];
    $formateurs_meta = rwmb_meta('fsbdd_grpctsformation', [], $action_id);
    if (!empty($formateurs_meta) && is_array($formateurs_meta)) {
        foreach ($formateurs_meta as $formateur_data) {
            if (!empty($formateur_data['fsbdd_selectcoutform'])) {
                $formateur_ids[] = $formateur_data['fsbdd_selectcoutform'];
            }
        }
    }
    return $formateur_ids;
}

/**
 * Helper: Obtenir le chemin du dossier pour une action et un formateur
 */
function fsbdd_get_trainer_action_dir_path($formateur_id, $action_slug) {
    return FSBDD_UPLOAD_DIR_PATH . '/' . $formateur_id . '/' . $action_slug;
}

/**
 * Fonction pour rendre le champ HTML des documents téléchargés
 */
function render_uploaded_documents_html_field($meta, $field) {
    global $post;

    if (!$post || $post->post_type !== 'action-de-formation') {
        return '<p>' . esc_html__('Ce contenu n\'est disponible que pour les actions de formation.', 'your-text-domain') . '</p>';
    }

    // TODO: Remplacer ce hardcoding par une métadonnée ou une option
    if ($post->ID === 268081) {
        return '<p>' . esc_html__('Ce contenu n\'est pas disponible pour cette action de formation.', 'your-text-domain') . '</p>';
    }

    $action_id = $post->ID;
    $action_title_slug = sanitize_title(get_the_title($action_id));

    $formateur_ids = fsbdd_get_action_formateur_ids($action_id);

    ob_start();

    // Styles CSS modernes
    echo '<style>
        .fsbdd-documents-container {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .fsbdd-documents-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            line-height: 1.4;
        }
        .fsbdd-documents-table thead th {
            background: #f8fafc;
            color: #374151;
            font-weight: 600;
            padding: 12px 8px;
            text-align: center;
            border-bottom: 2px solid #e5e7eb;
            font-size: 12px;
        }
        .fsbdd-documents-table tbody td {
            padding: 10px 8px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
            font-size: 12px;
        }
        .fsbdd-documents-table tbody tr:hover {
            background-color: #f9fafb;
        }
        .fsbdd-formateur-name {
            font-weight: 600;
            color: #1f2937;
        }
        .fsbdd-formateur-name a {
            color: #2563eb;
            text-decoration: none;
        }
        .fsbdd-formateur-name a:hover {
            text-decoration: underline;
        }
        .fsbdd-document-item {
            margin-bottom: 8px;
            padding: 4px 0;
        }
        .fsbdd-document-item:last-child {
            margin-bottom: 0;
        }
        .fsbdd-document-link {
            color: #2563eb;
            text-decoration: none;
            font-size: 11px;
        }
        .fsbdd-document-link:hover {
            text-decoration: underline;
        }
        .fsbdd-document-status {
            display: block;
            font-size: 10px;
            margin-top: 2px;
            font-style: italic;
        }
        .fsbdd-status-non-recu { color: #dc2626; }
        .fsbdd-status-recu { color: #ea580c; }
        .fsbdd-status-valide { color: #16a34a; }
        .fsbdd-no-document {
            color: #6b7280;
            font-size: 11px;
            font-style: italic;
        }
        .fsbdd-document-checkbox {
            margin-right: 6px;
        }
        .fsbdd-states-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 12px 16px;
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            font-weight: 500;
        }
        .fsbdd-state-item {
            display: flex;
            align-items: center;
        }
        .fsbdd-state-label {
            color: #374151;
            margin-right: 8px;
        }
        .fsbdd-state-value {
            font-weight: 600;
        }
        .fsbdd-state-non-recu { color: #dc2626; }
        .fsbdd-state-partiel { color: #ea580c; }
        .fsbdd-state-recu { color: #2563eb; }
        .fsbdd-state-valide { color: #16a34a; }
        .fsbdd-actions {
            padding: 16px;
            background: #fff;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 10px;
        }
        .fsbdd-btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .fsbdd-btn-primary {
            background: #2563eb;
            color: white;
        }
        .fsbdd-btn-primary:hover {
            background: #1d4ed8;
        }
        .fsbdd-btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .fsbdd-btn-secondary:hover {
            background: #e5e7eb;
        }
    </style>';

    echo '<div class="fsbdd-documents-container">';
    echo '<form id="validate_documents_form" method="post">';
    wp_nonce_field('validate_documents_action_' . $action_id, 'validate_documents_nonce');

    echo '<table class="fsbdd-documents-table">';
    echo '<thead>
            <tr>
                <th style="width: 15%;">' . esc_html__('Formateur', 'your-text-domain') . '</th>
                <th style="width: 21%;">' . esc_html__('Émargements', 'your-text-domain') . '</th>
                <th style="width: 21%;">' . esc_html__('Compte rendu F.', 'your-text-domain') . '</th>
                <th style="width: 21%;">' . esc_html__('Évaluations', 'your-text-domain') . '</th>
                <th style="width: 22%;">' . esc_html__('Autres docs', 'your-text-domain') . '</th>
            </tr>
        </thead>';
    echo '<tbody>';

    $row_index = 0;
    $document_types = ['emargements', 'compterenduf', 'evaluations'];
    $document_statuses = [];

    foreach ($document_types as $doc_type) {
        $document_statuses[$doc_type] = [
            'all_received' => !empty($formateur_ids),
            'all_validated' => !empty($formateur_ids),
            'any_received' => false,
            'latest_sent_date' => null,
            'latest_validation_date' => null,
        ];
    }
    
    if (empty($formateur_ids)) {
        echo '<tr><td colspan="5" class="fsbdd-no-document" style="text-align: center; padding: 20px;">' . esc_html__('Aucun formateur associé à cette action.', 'your-text-domain') . '</td></tr>';
        foreach ($document_types as $doc_type) {
            $document_statuses[$doc_type]['all_received'] = false;
            $document_statuses[$doc_type]['all_validated'] = false;
        }
    } else {
        foreach ($formateur_ids as $formateur_id) {
            $formateur_post = get_post($formateur_id);
            if (!$formateur_post) continue;

            $formateur_title = strtoupper($formateur_post->post_title);
            $formateur_edit_link = get_edit_post_link($formateur_id);
            $action_dir = fsbdd_get_trainer_action_dir_path($formateur_id, $action_title_slug);

            $files_in_dir = is_dir($action_dir) ? glob($action_dir . '/*') : [];
            $file_data = [
                'emargements' => [],
                'compterenduf' => [],
                'evaluations' => [],
                'autres' => [],
            ];

            if (!empty($files_in_dir)) {
                foreach ($files_in_dir as $file_path_current_file) {
                    if (is_dir($file_path_current_file)) continue;
                    $file_name = basename($file_path_current_file);
                    $short_name = (mb_strlen($file_name) > 20) ? mb_substr($file_name, 0, 20) . '...' : $file_name;

                    if (strpos($file_name, 'emargements') !== false) {
                        $file_data['emargements'][] = ['file' => $file_path_current_file, 'short_name' => $short_name];
                    } elseif (strpos($file_name, 'compterenduf') !== false) {
                        $file_data['compterenduf'][] = ['file' => $file_path_current_file, 'short_name' => $short_name];
                    } elseif (strpos($file_name, 'evaluations') !== false) {
                        $file_data['evaluations'][] = ['file' => $file_path_current_file, 'short_name' => $short_name];
                    } else {
                        $file_data['autres'][] = ['file' => $file_path_current_file, 'short_name' => $short_name];
                    }
                }
            }

            foreach ($document_types as $doc_type) {
                if (empty($file_data[$doc_type])) {
                    $document_statuses[$doc_type]['all_received'] = false;
                    $document_statuses[$doc_type]['all_validated'] = false;
                } else {
                    $document_statuses[$doc_type]['any_received'] = true;
                    $all_files_of_type_for_trainer_validated = true;
                    foreach ($file_data[$doc_type] as $file_info) {
                        $file_path = $file_info['file'];
                        
                        $meta_key_sent = '_sent_' . md5($file_path);
                        $meta_key_validated = '_validated_' . md5($file_path);

                        $send_date = get_post_meta($action_id, $meta_key_sent, true);
                        $validation_date = get_post_meta($action_id, $meta_key_validated, true);

                        if (!empty($send_date)) {
                            if (!$document_statuses[$doc_type]['latest_sent_date'] || strtotime($send_date) > strtotime($document_statuses[$doc_type]['latest_sent_date'])) {
                                $document_statuses[$doc_type]['latest_sent_date'] = $send_date;
                            }
                        }

                        if (!empty($validation_date)) {
                            if (!$document_statuses[$doc_type]['latest_validation_date'] || strtotime($validation_date) > strtotime($document_statuses[$doc_type]['latest_validation_date'])) {
                                $document_statuses[$doc_type]['latest_validation_date'] = $validation_date;
                            }
                        } else {
                            $all_files_of_type_for_trainer_validated = false;
                        }
                    }
                    if (!$all_files_of_type_for_trainer_validated) {
                        $document_statuses[$doc_type]['all_validated'] = false;
                    }
                }
            }

            echo "<tr>";
            echo '<td class="fsbdd-formateur-name">';
            if ($formateur_edit_link) {
                echo '<a href="' . esc_url($formateur_edit_link) . '" target="_blank">' . esc_html($formateur_title) . '</a>';
            } else {
                echo esc_html($formateur_title);
            }
            echo '</td>';

            $all_doc_categories = array_merge($document_types, ['autres']); 
            foreach ($all_doc_categories as $type) {
                echo '<td>';
                if (!empty($file_data[$type])) {
                    foreach ($file_data[$type] as $file_info) {
                        $file = $file_info['file'];
                        $short_name = $file_info['short_name'];
                        $secure_url = add_query_arg(
                            'fsbdd_file',
                            urlencode(str_replace(FSBDD_UPLOAD_DIR_PATH . '/', '', $file)),
                            site_url('/')
                        );

                        $meta_key_sent = '_sent_' . md5($file);
                        $meta_key_validated = '_validated_' . md5($file);

                        $send_date = get_post_meta($action_id, $meta_key_sent, true);
                        $validation_date = get_post_meta($action_id, $meta_key_validated, true);
                        $file_id = 'file_' . sanitize_title($formateur_id . '_' . md5($file));

                        echo "<div class='fsbdd-document-item'>";
                        echo "<input type='checkbox' name='selected_files[]' value='" . esc_attr($file) . "' id='" . esc_attr($file_id) . "' class='fsbdd-document-checkbox'>";
                        echo "<label for='" . esc_attr($file_id) . "'><a href='" . esc_url($secure_url) . "' target='_blank' class='fsbdd-document-link'>" . esc_html($short_name) . "</a></label>";

                        if (!empty($send_date) && empty($validation_date)) {
                            echo "<span class='fsbdd-document-status fsbdd-status-recu'>" . sprintf(esc_html__('Reçu le %s', 'your-text-domain'), esc_html($send_date)) . "</span>";
                        } elseif (!empty($validation_date)) {
                            echo "<span class='fsbdd-document-status fsbdd-status-valide'>" . sprintf(esc_html__('Reçu le %s - Validé le %s', 'your-text-domain'), esc_html($send_date), esc_html($validation_date)) . "</span>";
                        } else {
                            echo "<span class='fsbdd-document-status fsbdd-status-non-recu'>" . esc_html__('Non reçu', 'your-text-domain') . "</span>";
                        }
                        echo "</div>";
                    }
                } else {
                    echo "<span class='fsbdd-no-document'>" . esc_html__('Aucun document', 'your-text-domain') . "</span>";
                }
                echo '</td>';
            }
            echo '</tr>';
            $row_index++;
        }
    }

    echo '</tbody>';
    echo '</table>';

    // États des documents sur une seule ligne
    if (!empty($formateur_ids)) {
        echo '<div class="fsbdd-states-summary">';
        
        foreach ($document_types as $doc_type) {
            $etat_label = '';
            $etat_class = '';
            $latest_sent_date_display = $document_statuses[$doc_type]['latest_sent_date'] ? esc_html($document_statuses[$doc_type]['latest_sent_date']) : __('N/A', 'your-text-domain');
            $latest_validation_date_display = $document_statuses[$doc_type]['latest_validation_date'] ? esc_html($document_statuses[$doc_type]['latest_validation_date']) : __('N/A', 'your-text-domain');

            if ($document_statuses[$doc_type]['all_received']) {
                if ($document_statuses[$doc_type]['all_validated']) {
                    $etat_label = sprintf(__('Reçus le %s, validés le %s', 'your-text-domain'), $latest_sent_date_display, $latest_validation_date_display);
                    $etat_class = 'fsbdd-state-valide';
                } else {
                    $etat_label = sprintf(__('Reçus le %s', 'your-text-domain'), $latest_sent_date_display);
                    $etat_class = 'fsbdd-state-recu';
                }
            } else {
                if ($document_statuses[$doc_type]['any_received']) {
                    $etat_label = __('Partiel', 'your-text-domain');
                    $etat_class = 'fsbdd-state-partiel';
                } else {
                    $etat_label = __('Non reçu', 'your-text-domain');
                    $etat_class = 'fsbdd-state-non-recu';
                }
            }

            $doc_type_label_map = [
                'emargements' => __('Émargements', 'your-text-domain'),
                'compterenduf' => __('Comptes rendus F.', 'your-text-domain'),
                'evaluations' => __('Évaluations', 'your-text-domain'),
            ];
            $doc_type_display_label = $doc_type_label_map[$doc_type] ?? ucfirst($doc_type);

            echo '<div class="fsbdd-state-item">';
            echo '<span class="fsbdd-state-label">' . esc_html($doc_type_display_label) . ' :</span>';
            echo '<span class="fsbdd-state-value ' . $etat_class . '">' . esc_html($etat_label) . '</span>';
            echo '</div>';
        }
        
        echo '</div>';
    }

    echo '<div class="fsbdd-actions">';
    echo '<button type="submit" name="validate_files" class="fsbdd-btn fsbdd-btn-primary">' . esc_html__('Certifier', 'your-text-domain') . '</button>';
    echo '<button type="submit" name="delete_files" class="fsbdd-btn fsbdd-btn-secondary" onclick="return confirm(\'' . esc_js(__('Êtes-vous sûr de vouloir supprimer les fichiers sélectionnés ?', 'your-text-domain')) . '\');">' . esc_html__('Supprimer', 'your-text-domain') . '</button>';
    echo '</div>';

    echo '</form>';
    echo '</div>';

    return ob_get_clean();
}

/**
 * Fonction pour déterminer et mettre à jour l'état des documents.
 */
function update_fsbdd_etat_documents($post_id, $document_type) {
    $action_title_slug = sanitize_title(get_the_title($post_id));
    $formateur_ids = fsbdd_get_action_formateur_ids($post_id);

    $meta_key_map = [
        'emargements' => ['etat' => 'fsbdd_etatemargm', 'recept' => 'fsbdd_recepmargmts', 'date' => 'fsbdd_datemargmts'],
        'compterenduf' => ['etat' => 'fsbdd_etatcpterenduf', 'recept' => 'fsbdd_recepcpterenduf', 'date' => 'fsbdd_datecpterenduf'],
        'evaluations' => ['etat' => 'fsbdd_etateval', 'recept' => 'fsbdd_recepeval', 'date' => 'fsbdd_dateeval'],
    ];

    if (!isset($meta_key_map[$document_type])) {
        error_log("Type de document inconnu pour la mise à jour de l'état: $document_type pour le post ID: $post_id");
        return;
    }

    $current_meta_keys = $meta_key_map[$document_type];
    $meta_key_etat = $current_meta_keys['etat'];
    $meta_key_recept = $current_meta_keys['recept'];
    $meta_key_date = $current_meta_keys['date'];

    $all_documents_received = !empty($formateur_ids);
    $all_documents_validated = !empty($formateur_ids);
    $any_documents_received = false;

    if (empty($formateur_ids)) {
        $all_documents_received = false;
        $all_documents_validated = false;
    } else {
        foreach ($formateur_ids as $formateur_id) {
            $action_dir = fsbdd_get_trainer_action_dir_path($formateur_id, $action_title_slug);
            
            $files_in_dir = is_dir($action_dir) ? glob($action_dir . '/*') : [];
            
            $document_files_for_trainer = [];
            if (!empty($files_in_dir)) {
                foreach ($files_in_dir as $file) {
                    if (is_dir($file)) continue;
                    if (strpos(basename($file), $document_type) !== false) {
                        $document_files_for_trainer[] = $file;
                    }
                }
            }

            if (empty($document_files_for_trainer)) {
                $all_documents_received = false;
                $all_documents_validated = false; 
            } else {
                $any_documents_received = true;
                $trainer_all_docs_of_type_validated = true;
                foreach ($document_files_for_trainer as $file) {
                    $meta_key_validated_file = '_validated_' . md5($file);
                    $validation_date = get_post_meta($post_id, $meta_key_validated_file, true);

                    if (empty($validation_date)) {
                        $trainer_all_docs_of_type_validated = false;
                    }
                }
                if (!$trainer_all_docs_of_type_validated) {
                    $all_documents_validated = false;
                }
            }
        }
    }

    $etat = 1; 
    if ($all_documents_received) {
        $etat = $all_documents_validated ? 4 : 3;
    } elseif ($any_documents_received) {
        $etat = 2;
    }

    update_post_meta($post_id, $meta_key_etat, $etat);
    $current_date_dmy = date('d/m/Y');

    if ($etat >= 3) {
        update_post_meta($post_id, $meta_key_recept, $current_date_dmy);
        error_log("Date de réception $meta_key_recept màj/enregistrée ($current_date_dmy) pour le post ID: $post_id");

        if ($etat == 4) {
            update_post_meta($post_id, $meta_key_date, $current_date_dmy);
            error_log("Date de validation $meta_key_date màj/enregistrée ($current_date_dmy) pour le post ID: $post_id");
        }
    }

    error_log("Metadonnée $meta_key_etat mise à jour pour le post ID: $post_id avec l'état: $etat");
}

/**
 * Action pour sauvegarder les fichiers et mettre à jour les métadonnées d'état
 */
add_action('save_post_action-de-formation', 'process_file_validation_action_de_formation', 20, 3);

function process_file_validation_action_de_formation($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!$update && $post->post_status === 'auto-draft') return;
    if (empty($_POST)) return;

    if (!isset($_POST['validate_documents_nonce']) || !wp_verify_nonce($_POST['validate_documents_nonce'], 'validate_documents_action_' . $post_id)) {
        error_log('Échec de la vérification du nonce pour la validation des documents. Post ID: ' . $post_id);
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        error_log('Permission refusée pour la validation des documents. User ID: ' . get_current_user_id() . ', Post ID: ' . $post_id);
        return;
    }

    $current_date_dmy = date('d/m/Y');

    if (isset($_POST['validate_files']) && !empty($_POST['selected_files']) && is_array($_POST['selected_files'])) {
        $selected_files = array_map('sanitize_text_field', $_POST['selected_files']);
        foreach ($selected_files as $file_path) {
            if (strpos($file_path, FSBDD_UPLOAD_DIR_PATH) !== 0 || !file_exists($file_path) || is_dir($file_path)) {
                error_log("Tentative de validation d'un fichier invalide/inexistant: $file_path");
                continue;
            }
            update_post_meta($post_id, '_validated_' . md5($file_path), $current_date_dmy);

            error_log("Date de validation mise à jour pour le fichier: $file_path dans le post ID: $post_id");

            $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
            if (in_array($file_extension, ['jpg', 'jpeg', 'png']) && function_exists('apply_stamp_to_image')) {
                apply_stamp_to_image($file_path);
            } elseif ($file_extension === 'pdf' && function_exists('apply_stamp_to_pdf')) {
                apply_stamp_to_pdf($file_path);
            }
        }
        if (!empty($selected_files)) {
            add_filter('redirect_post_location', function ($location) {
                return add_query_arg('files_validated', '1', $location);
            });
        }
    }

    if (isset($_POST['delete_files']) && !empty($_POST['selected_files']) && is_array($_POST['selected_files'])) {
        $selected_files = array_map('sanitize_text_field', $_POST['selected_files']);
        foreach ($selected_files as $file_path) {
            if (strpos($file_path, FSBDD_UPLOAD_DIR_PATH) !== 0 || !file_exists($file_path) || is_dir($file_path)) {
                 error_log("Tentative de suppression d'un fichier invalide/inexistant: $file_path");
                continue;
            }
            if (unlink($file_path)) {
                delete_post_meta($post_id, '_sent_' . md5($file_path));
                delete_post_meta($post_id, '_validated_' . md5($file_path));
                error_log("Fichier supprimé: $file_path et métas associées pour le post ID: $post_id");
            } else {
                error_log("Échec de la suppression du fichier: $file_path pour le post ID: $post_id");
            }
        }
        if (!empty($selected_files)) {
            add_filter('redirect_post_location', function ($location) {
                return add_query_arg('files_deleted', '1', $location);
            });
        }
    }

    $document_types = ['emargements', 'compterenduf', 'evaluations'];
    foreach ($document_types as $doc_type) {
        update_fsbdd_etat_documents($post_id, $doc_type);
    }
}

/**
 * Notifications d'administration
 */
add_action('admin_notices', function () {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'action-de-formation' && $screen->base === 'post') {
        if (isset($_GET['files_validated']) && $_GET['files_validated'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Fichiers validés avec succès.', 'your-text-domain') . '</p></div>';
        }
        if (isset($_GET['files_deleted']) && $_GET['files_deleted'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Fichiers supprimés avec succès.', 'your-text-domain') . '</p></div>';
        }
    }
});