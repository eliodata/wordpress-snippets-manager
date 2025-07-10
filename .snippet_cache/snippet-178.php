<?php
/**
 * Snippet ID: 178
 * Name: DOCUMENTS FORMATEUR SIDEBAR COMMANDES
 * Description: 
 * @active true
 */

/**
 * Ajouter une metabox sidebar sur les commandes WooCommerce pour afficher les documents liés
 */
add_action('add_meta_boxes', 'add_woo_order_documents_metabox');

function add_woo_order_documents_metabox() {
    add_meta_box(
        'woo-order-documents',
        'Documents de formation',
        'woo_order_documents_metabox_callback',
        'shop_order', // Type de post WooCommerce
        'side',
        'default'
    );
}

/**
 * Helper: Récupérer l'ID de l'action de formation liée à une commande
 */
function get_linked_action_formation_id($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return false;
    }

    // Parcourir les items de la commande pour trouver l'action de formation liée
    $items = $order->get_items();
    foreach ($items as $item) {
        $action_id = wc_get_order_item_meta($item->get_id(), 'fsbdd_relsessaction_cpt_produit', true);
        if (!empty($action_id)) {
            return $action_id;
        }
    }

    return false;
}

/**
 * Callback de la metabox pour afficher les documents liés à l'action de formation
 */
function woo_order_documents_metabox_callback($post) {
    $order_id = $post->ID;
    $action_id = get_linked_action_formation_id($order_id);

    // Styles CSS pour la metabox sidebar - Version condensée
    echo '<style>
        .woo-documents-sidebar { font-size: 11px; line-height: 1.3; }
        .woo-documents-sidebar .doc-section { margin-bottom: 8px; border-bottom: 1px solid #e0e0e0; padding-bottom: 6px; }
        .woo-documents-sidebar .doc-section:last-child { border-bottom: none; margin-bottom: 0; }
        .woo-documents-sidebar .formateur-name { font-weight: 600; color: #0073aa; margin-bottom: 3px; font-size: 11px; }
        .woo-documents-sidebar .formateur-name a { color: #0073aa; text-decoration: none; }
        .woo-documents-sidebar .formateur-name a:hover { text-decoration: underline; }
        .woo-documents-sidebar .doc-type-line { margin-bottom: 2px; display: flex; align-items: center; }
        .woo-documents-sidebar .doc-type-label { font-weight: 600; font-size: 10px; margin-right: 6px; min-width: 50px; }
        .woo-documents-sidebar .doc-icon { color: #0073aa; margin-right: 4px; cursor: pointer; text-decoration: none; font-size: 12px; }
        .woo-documents-sidebar .doc-icon:hover { color: #005a87; }
        .woo-documents-sidebar .doc-status { font-size: 9px; margin-left: 4px; }
        .woo-documents-sidebar .status-received { color: #d63384; font-weight: 500; }
        .woo-documents-sidebar .status-validated { color: #198754; font-weight: 500; }
        .woo-documents-sidebar .status-missing { color: #dc3545; }
        .woo-documents-sidebar .doc-summary { background: #f8f9fa; padding: 6px; border-radius: 3px; margin-top: 8px; border-left: 3px solid #0073aa; }
        .woo-documents-sidebar .doc-summary .summary-line { margin-bottom: 2px; font-size: 10px; display: flex; justify-content: space-between; }
        .woo-documents-sidebar .action-title { display: flex; align-items: center; margin-bottom: 8px; }
        .woo-documents-sidebar .action-title h4 { margin: 0; font-size: 12px; flex: 1; }
        .woo-documents-sidebar .action-icon { color: #0073aa; margin-left: 6px; text-decoration: none; }
        .woo-documents-sidebar .action-icon:hover { color: #005a87; }
        .woo-documents-sidebar .doc-item { display: inline-block; margin-right: 8px; margin-bottom: 2px; }
    </style>';

    if (!$action_id) {
        echo '<div class="woo-documents-sidebar">';
        echo '<p style="margin: 0; font-style: italic; color: #666;">Aucune action de formation liée.</p>';
        echo '</div>';
        return;
    }

    $action_post = get_post($action_id);
    if (!$action_post) {
        echo '<div class="woo-documents-sidebar">';
        echo '<p style="margin: 0; font-style: italic; color: #666;">Action de formation introuvable.</p>';
        echo '</div>';
        return;
    }

    $action_title = get_the_title($action_id);
    $action_title_slug = sanitize_title($action_title);
    $action_edit_link = get_edit_post_link($action_id);
    
    echo '<div class="woo-documents-sidebar">';
    
    // Titre avec lien vers l'action de formation et picto
    echo '<div class="action-title">';
    echo '<h4>Action: <a href="' . esc_url($action_edit_link) . '" target="_blank">' . esc_html($action_title) . '</a></h4>';
    echo '<a href="' . esc_url($action_edit_link) . '" target="_blank" class="action-icon" title="Gérer les documents">';
    echo '<span class="dashicons dashicons-edit-page" style="font-size: 14px;"></span>';
    echo '</a>';
    echo '</div>';

    // Récupérer les formateurs
    $formateur_ids = fsbdd_get_action_formateur_ids($action_id);
    
    if (empty($formateur_ids)) {
        echo '<p style="margin: 0; font-style: italic; color: #666;">Aucun formateur associé.</p>';
        echo '</div>';
        return;
    }

    $document_types = ['emargements', 'compterenduf', 'evaluations', 'autres'];
    $document_type_labels = [
        'emargements' => 'Émarg.',
        'compterenduf' => 'C.R.F.',
        'evaluations' => 'Éval.',
        'autres' => 'Autres'
    ];

    $global_document_status = [];
    foreach (['emargements', 'compterenduf', 'evaluations'] as $doc_type) {
        $global_document_status[$doc_type] = [
            'all_received' => !empty($formateur_ids),
            'all_validated' => !empty($formateur_ids),
            'any_received' => false,
            'latest_date' => null,
            'status' => 'missing'
        ];
    }

    // Parcourir chaque formateur
    foreach ($formateur_ids as $formateur_id) {
        $formateur_post = get_post($formateur_id);
        if (!$formateur_post) continue;

        $formateur_title = $formateur_post->post_title;
        $action_dir = fsbdd_get_trainer_action_dir_path($formateur_id, $action_title_slug);
        
        $files_in_dir = is_dir($action_dir) ? glob($action_dir . '/*') : [];
        $file_data = [
            'emargements' => [],
            'compterenduf' => [],
            'evaluations' => [],
            'autres' => [],
        ];

        // Organiser les fichiers par type
        if (!empty($files_in_dir)) {
            foreach ($files_in_dir as $file_path) {
                if (is_dir($file_path)) continue;
                $file_name = basename($file_path);

                if (strpos($file_name, 'emargements') !== false) {
                    $file_data['emargements'][] = $file_path;
                } elseif (strpos($file_name, 'compterenduf') !== false) {
                    $file_data['compterenduf'][] = $file_path;
                } elseif (strpos($file_name, 'evaluations') !== false) {
                    $file_data['evaluations'][] = $file_path;
                } else {
                    $file_data['autres'][] = $file_path;
                }
            }
        }

        // Afficher les documents pour ce formateur
        $has_documents = false;
        foreach ($document_types as $type) {
            if (!empty($file_data[$type])) {
                $has_documents = true;
                break;
            }
        }

        if ($has_documents) {
            echo '<div class="doc-section">';
            $formateur_edit_link = get_edit_post_link($formateur_id);
            echo '<div class="formateur-name">';
            if ($formateur_edit_link) {
                echo '<a href="' . esc_url($formateur_edit_link) . '" target="_blank">' . esc_html($formateur_title) . '</a>';
            } else {
                echo esc_html($formateur_title);
            }
            echo '</div>';

            foreach ($document_types as $type) {
                if (!empty($file_data[$type])) {
                    $type_label = $document_type_labels[$type] ?? ucfirst($type);
                    
                    echo '<div class="doc-type-line">';
                    echo '<span class="doc-type-label">' . esc_html($type_label) . '</span>';
                    
                    foreach ($file_data[$type] as $file_path) {
                        $file_name = basename($file_path);
                        
                        $secure_url = add_query_arg(
                            'fsbdd_file',
                            urlencode(str_replace(FSBDD_UPLOAD_DIR_PATH . '/', '', $file_path)),
                            site_url('/')
                        );

                        // Vérifier le statut du fichier
                        $meta_key_sent = '_sent_' . md5($file_path);
                        $meta_key_validated = '_validated_' . md5($file_path);
                        $send_date = get_post_meta($action_id, $meta_key_sent, true);
                        $validation_date = get_post_meta($action_id, $meta_key_validated, true);

                        echo '<div class="doc-item">';
                        echo '<a href="' . esc_url($secure_url) . '" target="_blank" class="doc-icon" title="' . esc_attr($file_name) . '">';
                        echo '<span class="dashicons dashicons-media-document"></span>';
                        echo '</a>';
                        
                        if (!empty($validation_date)) {
                            $formatted_date = date('d/m', strtotime($validation_date));
                            echo '<span class="doc-status status-validated">' . $formatted_date . '</span>';
                        } elseif (!empty($send_date)) {
                            $formatted_date = date('d/m', strtotime($send_date));
                            echo '<span class="doc-status status-received">' . $formatted_date . '</span>';
                        } else {
                            echo '<span class="doc-status status-missing">—</span>';
                        }
                        echo '</div>';

                        // Mettre à jour les statuts globaux
                        if (in_array($type, ['emargements', 'compterenduf', 'evaluations'])) {
                            if (!empty($validation_date)) {
                                $date_to_compare = $validation_date;
                                $current_status = 'validated';
                            } elseif (!empty($send_date)) {
                                $date_to_compare = $send_date;
                                $current_status = 'received';
                                $global_document_status[$type]['any_received'] = true;
                                $global_document_status[$type]['all_validated'] = false;
                            } else {
                                $global_document_status[$type]['all_received'] = false;
                                $global_document_status[$type]['all_validated'] = false;
                                continue;
                            }

                            // Mettre à jour la date la plus récente
                            if (empty($global_document_status[$type]['latest_date']) || 
                                strtotime($date_to_compare) > strtotime($global_document_status[$type]['latest_date'])) {
                                $global_document_status[$type]['latest_date'] = $date_to_compare;
                                $global_document_status[$type]['status'] = $current_status;
                            }
                        }
                    }
                    echo '</div>';
                }
            }
            echo '</div>';
        }

        // Vérifier si ce formateur n'a aucun document pour les types principaux
        foreach (['emargements', 'compterenduf', 'evaluations'] as $doc_type) {
            if (empty($file_data[$doc_type])) {
                $global_document_status[$doc_type]['all_received'] = false;
                $global_document_status[$doc_type]['all_validated'] = false;
            }
        }
    }

    // Résumé des statuts globaux
    echo '<div class="doc-summary">';
    echo '<div style="font-weight: 600; margin-bottom: 4px; font-size: 10px;">RÉSUMÉ</div>';
    
    foreach (['emargements', 'compterenduf', 'evaluations'] as $doc_type) {
        $status_data = $global_document_status[$doc_type];
        $label = $document_type_labels[$doc_type];
        
        echo '<div class="summary-line">';
        echo '<span>' . esc_html($label) . '</span>';
        
        if (!empty($status_data['latest_date'])) {
            $formatted_date = date('d/m', strtotime($status_data['latest_date']));
            if ($status_data['status'] === 'validated') {
                echo '<span style="color: #198754; font-weight: 500;">' . $formatted_date . '</span>';
            } else {
                echo '<span style="color: #d63384; font-weight: 500;">' . $formatted_date . '</span>';
            }
        } else {
            echo '<span style="color: #dc3545;">—</span>';
        }
        echo '</div>';
    }
    echo '</div>';

    echo '</div>';
}

/**
 * Ajouter un rafraîchissement automatique pour la metabox documents
 */
add_action('admin_footer', 'add_woo_order_documents_refresh_script');
function add_woo_order_documents_refresh_script() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'shop_order' && $screen->base === 'post') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Rafraîchir la métabox toutes les 30 secondes
            setInterval(function() {
                if ($('#woo-order-documents').length) {
                    var postId = $('#post_ID').val();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'refresh_woo_order_documents_metabox',
                            post_id: postId,
                            security: '<?php echo wp_create_nonce("refresh_woo_documents_nonce"); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#woo-order-documents .inside').html(response.data);
                            }
                        }
                    });
                }
            }, 30000); // 30 secondes
        });
        </script>
        <?php
    }
}

/**
 * Endpoint AJAX pour rafraîchir la métabox documents
 */
add_action('wp_ajax_refresh_woo_order_documents_metabox', 'refresh_woo_order_documents_metabox_callback');
function refresh_woo_order_documents_metabox_callback() {
    check_ajax_referer('refresh_woo_documents_nonce', 'security');
    
    if (!isset($_POST['post_id'])) {
        wp_send_json_error('Missing post ID');
    }
    
    $post_id = intval($_POST['post_id']);
    $post = get_post($post_id);
    
    ob_start();
    woo_order_documents_metabox_callback($post);
    $metabox_content = ob_get_clean();
    
    wp_send_json_success($metabox_content);
}