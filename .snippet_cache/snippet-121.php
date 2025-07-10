<?php
/**
 * Snippet ID: 121
 * Name: Page de commande client documents liste stagiaires... formulaire envoi côté admin détails commande
 * Description: 
 * @active true
 */

/**
 * Affiche la liste des stagiaires (Prénom / Nom / Date de naissance)
 * sous le détail de la commande sur la page "Mon Compte > Commandes"
 */
add_action('woocommerce_order_details_after_order_table', 'fsbdd_show_stagiaires_on_order_page', 10, 1);

function fsbdd_show_stagiaires_on_order_page($order) {
    // $order peut être un WC_Order ou un ID selon la version.
    if (is_numeric($order)) {
        $order = wc_get_order($order);
    }

    if (!$order instanceof WC_Order) {
        return; // Sécurité
    }

    // 1) Récupérer le meta "fsbdd_gpeffectif"
    //    qui contient le tableau sérialisé des stagiaires
    $gpeffectif = $order->get_meta('fsbdd_gpeffectif', true);
    if (empty($gpeffectif) || !is_array($gpeffectif)) {
        // Rien à afficher
        return;
    }

    // 2) Construire le tableau de stagiaires
    //    Chaque item est de la forme:
    //      [
    //        "fsbdd_prenomstagiaire"  => "Frédéric",
    //        "fsbdd_nomstagiaire"     => "Garnault",
    //        "fsbdd_stagidatenaiss"   => "28/01/2000"
    //      ]
    $stagiaires = array();
    foreach ($gpeffectif as $item) {
        $prenom = isset($item['fsbdd_prenomstagiaire']) ? trim($item['fsbdd_prenomstagiaire']) : '';
        $nom    = isset($item['fsbdd_nomstagiaire'])    ? trim($item['fsbdd_nomstagiaire'])    : '';
        $dnaiss = isset($item['fsbdd_stagidatenaiss'])  ? trim($item['fsbdd_stagidatenaiss'])  : '';

        // S'il y a a minima prénom ou nom, on l'ajoute
        if ($prenom || $nom || $dnaiss) {
            $stagiaires[] = array(
                'prenom' => $prenom,
                'nom'    => $nom,
                'dnaiss' => $dnaiss,
            );
        }
    }

    // Si aucun stagiaire valide
    if (empty($stagiaires)) {
        return;
    }

    // 3) Afficher un titre et un tableau
    //    Vous pouvez personnaliser le HTML / style selon vos préférences.
    ?>
<h2><span class="dashicons dashicons-groups" style="margin-right: 8px;"></span><?php esc_html_e('Stagiaires', 'woocommerce'); ?></h2>
<table class="shop_table shop_table_responsive" style="margin-bottom:20px;">
    <thead>
        <tr>
            <th><?php esc_html_e('Prénom', 'woocommerce'); ?></th>
            <th><?php esc_html_e('Nom', 'woocommerce'); ?></th>
            <th><?php esc_html_e('Date de naissance', 'woocommerce'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($stagiaires as $stagiaire) : ?>
            <tr>
                <td data-title="<?php esc_attr_e('Prénom', 'woocommerce'); ?>"><?php echo esc_html($stagiaire['prenom']); ?></td>
                <td data-title="<?php esc_attr_e('Nom', 'woocommerce'); ?>"><?php echo esc_html($stagiaire['nom']); ?></td>
                <td data-title="<?php esc_attr_e('Date de naissance', 'woocommerce'); ?>"><?php echo esc_html($stagiaire['dnaiss']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

    <?php
}

/**
 * Helper: Récupérer l'ID de l'action de formation liée à une commande (pour émargements)
 */
function get_action_formation_from_order($order_id) {
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
 * Helper: Récupérer les émargements pour une commande
 */
function get_emargements_for_order($order_id) {
    if (!defined('FSBDD_UPLOAD_DIR_PATH')) {
        return array();
    }

    $action_id = get_action_formation_from_order($order_id);
    if (!$action_id) {
        return array();
    }

    $action_title_slug = sanitize_title(get_the_title($action_id));
    $formateur_ids = function_exists('fsbdd_get_action_formateur_ids') ? fsbdd_get_action_formateur_ids($action_id) : array();
    
    $emargements = array();

    if (!empty($formateur_ids)) {
        foreach ($formateur_ids as $formateur_id) {
            if (!function_exists('fsbdd_get_trainer_action_dir_path')) {
                continue;
            }
            
            $action_dir = fsbdd_get_trainer_action_dir_path($formateur_id, $action_title_slug);
            $files_in_dir = is_dir($action_dir) ? glob($action_dir . '/*') : array();

            if (!empty($files_in_dir)) {
                foreach ($files_in_dir as $file_path) {
                    if (is_dir($file_path)) continue;
                    $file_name = basename($file_path);
                    
                    // Vérifier si c'est un émargement
                    if (strpos($file_name, 'emargements') !== false) {
                        $secure_url = add_query_arg(
                            'fsbdd_file',
                            urlencode(str_replace(FSBDD_UPLOAD_DIR_PATH . '/', '', $file_path)),
                            site_url('/')
                        );
                        
                        $emargements[] = array(
                            'file_name' => $file_name,
                            'url' => $secure_url,
                            'formateur_id' => $formateur_id
                        );
                    }
                }
            }
        }
    }

    return $emargements;
}

add_action('wp_enqueue_scripts', 'fsbdd_frontend_documents_styles');
function fsbdd_frontend_documents_styles() {
    if (is_account_page() || is_wc_endpoint_url('view-order')) {
        $custom_css = "
            /* Styles pour les tableaux responsive */
            .shop_table_responsive {
                width: 100%;
                border-collapse: collapse;
                table-layout: auto;
                background: #fff;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                border-radius: 3px;
                overflow: hidden;
                margin-bottom: 20px;
            }
            
            .shop_table_responsive th, 
            .shop_table_responsive td {
                padding: 10px;
                text-align: left;
                border: 1px solid #ddd;
                white-space: nowrap;
            }
            
            .shop_table_responsive th {
                background-color: #f4f4f4;
                font-weight: bold;
            }
            
            .shop_table_responsive tbody tr {
                transition: background-color 0.2s ease;
            }
            
            .shop_table_responsive tbody tr:hover {
                background-color: #f8f9fa;
            }

            /* Styles pour la section documents */
            .documents-section {
                background: #fff;
                border-radius: 3px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                margin: 25px 0;
                overflow: hidden;
            }
            
            .documents-header {
                background-color: #314150;
                color: white;
                padding: 12px 15px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .documents-header h2 {
                margin: 0;
                font-size: 16px;
                font-weight: 600;
            }
            
            .documents-header .dashicons {
                font-size: 16px;
            }
            
            .documents-content {
                padding: 0;
            }
            
            .documents-table {
                width: 100%;
                border-collapse: collapse;
                margin: 0;
            }
            
            .documents-table tbody tr {
                border-bottom: 1px solid #e8ecf0;
                transition: background-color 0.2s ease;
            }
            
            .documents-table tbody tr:last-child {
                border-bottom: none;
            }
            
            .documents-table tbody tr:hover {
                background-color: #f8f9fa;
            }
            
            .documents-table td {
                padding: 12px 15px;
                vertical-align: middle;
                font-size: 14px;
            }
            
            .documents-table .doc-type {
                font-weight: 600;
                color: #2d3748;
                min-width: 140px;
            }
            
            .documents-table .doc-actions {
                text-align: right;
            }
            
            .doc-download-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: #48bb78;
                color: white;
                text-decoration: none;
                padding: 6px 12px;
                border-radius: 3px;
                font-weight: 500;
                font-size: 12px;
                transition: background-color 0.2s ease;
            }
            
            .doc-download-btn:hover {
                background: #38a169;
                color: white;
                text-decoration: none;
            }
            
            .doc-download-btn .dashicons {
                font-size: 14px;
            }
            
            .no-documents {
                padding: 30px 20px;
                text-align: center;
                color: #718096;
                font-style: italic;
            }
            
            .no-documents .dashicons {
                font-size: 48px;
                color: #e2e8f0;
                margin-bottom: 15px;
            }

            /* Responsive */
            @media (max-width: 768px) {
                .shop_table_responsive,
                .documents-section {
                    font-size: 12px;
                }
                
                .shop_table_responsive th, 
                .shop_table_responsive td,
                .documents-table td {
                    padding: 5px;
                }
                
                .documents-header {
                    padding: 10px 15px;
                }
                
                .documents-header h2 {
                    font-size: 14px;
                }
                
                .documents-table td {
                    padding: 10px 15px;
                }
                
                .doc-download-btn {
                    padding: 5px 10px;
                    font-size: 11px;
                }
                
                .documents-table .doc-type {
                    min-width: auto;
                    font-size: 12px;
                }
            }
            
            @media (max-width: 480px) {
                .documents-table {
                    display: block;
                }
                
                .documents-table tbody,
                .documents-table tr,
                .documents-table td {
                    display: block;
                    width: 100%;
                }
                
                .documents-table tr {
                    border: 1px solid #e8ecf0;
                    border-radius: 3px;
                    margin-bottom: 10px;
                    padding: 10px;
                    background: #fff;
                }
                
                .documents-table td {
                    padding: 5px 0;
                    border: none;
                    text-align: left;
                }
                
                .documents-table .doc-type {
                    font-size: 13px;
                    margin-bottom: 5px;
                    padding-bottom: 5px;
                    border-bottom: 1px solid #e8ecf0;
                }
                
                .documents-table .doc-actions {
                    text-align: left;
                    margin-top: 8px;
                }
            }
        ";
        wp_add_inline_style('woocommerce-general', $custom_css);
    }
}

// ===============================
// 1) FONCTION GÉNÉRIQUE D'AFFICHAGE DES DOCUMENTS AMÉLIORÉE
// ===============================
function afficher_documents_avec_checkboxes($order_id, $context) {
    $upload_dir = wp_upload_dir();
    $pdf_folder = $upload_dir['basedir'] . '/pdfclients/';
    
    $documents_affiches = false;
    $all_documents = array();

    // 1. Récupérer les documents du dossier pdfclients (existant)
    if (is_dir($pdf_folder)) {
        $dir = opendir($pdf_folder);
        if ($dir) {
            while (($file = readdir($dir)) !== false) {
                if (preg_match('/\b' . preg_quote($order_id, '/') . '\b/', $file) && pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
                    $file_path = $upload_dir['baseurl'] . '/pdfclients/' . $file;
                    
                    // Déterminer le type de document par son préfixe
                    $type_document = 'Document';
                    if (strpos($file, 'devis-') === 0) {
                        $type_document = 'Devis';
                    } elseif (strpos($file, 'convention-') === 0) {
                        $type_document = 'Convention';
                    } elseif (strpos($file, 'convocation-') === 0) {
                        $type_document = 'Convocation';
                    } elseif (strpos($file, 'avenant-') === 0) {
                        $type_document = 'Avenant';
                    } elseif (strpos($file, 'realisation-') === 0) {
                        $type_document = 'Certificat de réalisation';
                    } elseif (strpos($file, 'attestation-') === 0) {
                        $type_document = 'Attestation de formation';
                    }

                    $all_documents[] = array(
                        'type' => $type_document,
                        'url' => $file_path,
                        'file_name' => $file,
                        'source' => 'pdfclients'
                    );
                }
            }
            closedir($dir);
        }
    }

    // 2. Récupérer les émargements (nouveau - seulement côté client)
    if ($context === 'client') {
        $emargements = get_emargements_for_order($order_id);
        foreach ($emargements as $emargement) {
            $all_documents[] = array(
                'type' => 'Émargements',
                'url' => $emargement['url'],
                'file_name' => $emargement['file_name'],
                'source' => 'emargements'
            );
        }
    }

    // 3. Affichage avec checkboxes pour admin
     if ($context === 'admin') {
         echo '<table style="width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 11px;">';
         echo '<tbody>';

        if (!empty($all_documents)) {
            foreach ($all_documents as $document) {
                echo '<tr>';
                echo '<td style="padding: 2px; border-bottom: 1px solid #ddd; width: 20px;">';
                 if ($document['source'] === 'pdfclients') {
                     echo '<input type="checkbox" name="files_to_delete[]" value="' . esc_attr($document['file_name']) . '" class="doc-checkbox" />';
                 }
                 echo '</td>';
                 echo '<td style="padding: 2px 4px; border-bottom: 1px solid #ddd; font-weight: 600; font-size: 10px;">' . esc_html($document['type']) . '</td>';
                 echo '<td style="padding: 2px; border-bottom: 1px solid #ddd;">';

                if ($document['source'] === 'pdfclients') {
                    // Actions admin pour les documents pdfclients
                    echo '<a href="' . esc_url($document['url']) . '" target="_blank" style="text-decoration: none;">
                            <span class="dashicons dashicons-visibility" style="margin-left: 4px;"></span>
                          </a>';
                    
                    $delete_url = add_query_arg(
                        array(
                            'action' => 'delete_pdf_client',
                            'post_id' => $order_id,
                            'file' => urlencode($document['file_name']),
                            '_wpnonce' => wp_create_nonce('delete_pdf_client_' . $document['file_name'])
                        ),
                        admin_url('admin-post.php')
                    );
                    
                    echo '<a href="' . esc_url($delete_url) . '" class="delete-pdf-link" style="text-decoration: none;">
                            <span class="dashicons dashicons-trash" style="margin-left: 4px; color: #dc3545;"></span>
                          </a>';
                } else {
                    // Pour les émargements, juste un lien de visualisation
                    echo '<a href="' . esc_url($document['url']) . '" target="_blank" style="text-decoration: none;">
                            <span class="dashicons dashicons-visibility" style="margin-left: 4px;"></span>
                          </a>';
                }
                echo '</td>';
                echo '</tr>';
            }
        } else {
             echo '<tr>';
             echo '<td colspan="3" style="padding: 2px; text-align: center; border-bottom: 1px solid #ddd; font-size: 10px; color: #666;">Aucun document disponible</td>';
             echo '</tr>';
         }

        echo '</tbody>';
        echo '</table>';
        
        // JavaScript pour la gestion des checkboxes
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Gestion du bouton "Tout sélectionner"
            $('#select-all-docs').on('click', function() {
                var $checkboxes = $('.doc-checkbox');
                var allChecked = $checkboxes.length === $checkboxes.filter(':checked').length;
                $checkboxes.prop('checked', !allChecked);
                $(this).text(allChecked ? 'Tout sélect.' : 'Tout désélect.');
                updateActionButtons();
            });
            
            // Gestion des checkboxes individuelles
            $('.doc-checkbox').on('change', function() {
                updateActionButtons();
                updateSelectAllButton();
            });
            
            // Gérer l'activation/désactivation des boutons
            function updateActionButtons() {
                const deleteBtn = document.getElementById('bulk-delete-btn');
                const downloadBtn = document.getElementById('download-selected-docs');
                const checkedBoxes = document.querySelectorAll('.doc-checkbox:checked');
                const hasSelection = checkedBoxes.length > 0;
                
                if (deleteBtn) {
                    deleteBtn.disabled = !hasSelection;
                }
                if (downloadBtn) {
                    downloadBtn.disabled = !hasSelection;
                }
            }
            
            // Gestion du téléchargement des documents sélectionnés
            $('#download-selected-docs').on('click', function() {
                var checkedBoxes = $('.doc-checkbox:checked');
                if (checkedBoxes.length === 0) {
                    alert('Veuillez sélectionner au moins un document.');
                    return;
                }
                
                checkedBoxes.each(function() {
                    var fileName = $(this).val();
                    var downloadUrl = '<?php echo $upload_dir['baseurl']; ?>/pdfclients/' + fileName;
                    
                    // Créer un lien temporaire pour télécharger
                    var link = document.createElement('a');
                    link.href = downloadUrl;
                    link.download = fileName;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });
            });
            
            function updateSelectAllButton() {
                var $checkboxes = $('.doc-checkbox');
                var allChecked = $checkboxes.length === $checkboxes.filter(':checked').length;
                $('#select-all-docs').text(allChecked ? 'Tout désélect.' : 'Tout sélect.');
            }
            
            // Confirmation pour la suppression en lot
            $('#bulk-delete-form').on('submit', function(e) {
                var checkedCount = $('.doc-checkbox:checked').length;
                if (checkedCount === 0) {
                    e.preventDefault();
                    return false;
                }
                
                var message = checkedCount === 1 ? 
                    'Êtes-vous sûr de vouloir supprimer ce document ?' :
                    'Êtes-vous sûr de vouloir supprimer ces ' + checkedCount + ' documents ?';
                    
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
        });
        </script>
        <style>
        .bulk-actions-row {
            margin-top: 10px;
            display: flex;
            gap: 8px;
        }
        
        .doc-select-all-btn, .doc-bulk-delete-btn, .doc-download-selected-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 3px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .doc-select-all-btn {
            background-color: #0073aa;
            color: white;
        }
        
        .doc-select-all-btn:hover {
            background-color: #005a87;
        }
        
        .doc-bulk-delete-btn {
            background-color: #dc3545;
            color: white;
        }
        
        .doc-bulk-delete-btn:hover:not(:disabled) {
            background-color: #c82333;
        }
        
        .doc-download-selected-btn {
            background-color: #00a32a;
            color: white;
            padding: 6px 8px;
        }
        
        .doc-download-selected-btn:hover:not(:disabled) {
            background-color: #008a20;
        }
        
        .doc-bulk-delete-btn:disabled, .doc-download-selected-btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .doc-download-selected-btn .dashicons {
            font-size: 20px;
            line-height: 1;
        }
        
        .doc-checkbox {
            margin: 0;
        }
        </style>
        <?php
    }
}

function afficher_documents($order_id, $context) {
    $upload_dir = wp_upload_dir();
    $pdf_folder = $upload_dir['basedir'] . '/pdfclients/';
    
    $documents_affiches = false;
    $all_documents = array();

    // 1. Récupérer les documents du dossier pdfclients (existant)
    if (is_dir($pdf_folder)) {
        $dir = opendir($pdf_folder);
        if ($dir) {
            while (($file = readdir($dir)) !== false) {
                if (preg_match('/\b' . preg_quote($order_id, '/') . '\b/', $file) && pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
                    $file_path = $upload_dir['baseurl'] . '/pdfclients/' . $file;
                    
                    // Déterminer le type de document par son préfixe
                    $type_document = 'Document';
                    if (strpos($file, 'devis-') === 0) {
                        $type_document = 'Devis';
                    } elseif (strpos($file, 'convention-') === 0) {
                        $type_document = 'Convention';
                    } elseif (strpos($file, 'convocation-') === 0) {
                        $type_document = 'Convocation';
                    } elseif (strpos($file, 'avenant-') === 0) {
                        $type_document = 'Avenant';
                    } elseif (strpos($file, 'realisation-') === 0) {
                        $type_document = 'Certificat de réalisation';
                    } elseif (strpos($file, 'attestation-') === 0) {
                        $type_document = 'Attestation de formation';
                    }

                    $all_documents[] = array(
                        'type' => $type_document,
                        'url' => $file_path,
                        'file_name' => $file,
                        'source' => 'pdfclients'
                    );
                }
            }
            closedir($dir);
        }
    }

    // 2. Récupérer les émargements (nouveau - seulement côté client)
    if ($context === 'client') {
        $emargements = get_emargements_for_order($order_id);
        foreach ($emargements as $emargement) {
            $all_documents[] = array(
                'type' => 'Émargements',
                'url' => $emargement['url'],
                'file_name' => $emargement['file_name'],
                'source' => 'emargements'
            );
        }
    }

    // 3. Affichage
    if ($context === 'client') {
        // Style moderne pour le front-end
        if (!empty($all_documents)) {
            echo '<div class="documents-section">';
            echo '<div class="documents-header">';
            echo '<span class="dashicons dashicons-media-document"></span>';
            echo '<h2>Documents de formation</h2>';
            echo '</div>';
            echo '<div class="documents-content">';
            echo '<table class="documents-table">';
            echo '<tbody>';

            foreach ($all_documents as $document) {
                echo '<tr>';
                echo '<td class="doc-type">' . esc_html($document['type']) . '</td>';
                echo '<td class="doc-actions">';
                echo '<a href="' . esc_url($document['url']) . '" download class="doc-download-btn">';
                echo 'Télécharger <span class="dashicons dashicons-download"></span>';
                echo '</a>';
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';
            echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="documents-section">';
            echo '<div class="documents-header">';
            echo '<span class="dashicons dashicons-media-document"></span>';
            echo '<h2>Documents de formation</h2>';
            echo '</div>';
            echo '<div class="no-documents">';
            echo '<div class="dashicons dashicons-portfolio"></div>';
            echo '<p>Aucun document disponible pour cette formation.</p>';
            echo '</div>';
            echo '</div>';
        }
    } else {
        // Style admin existant (backend)
        echo '<table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px;">';
        echo '<tbody>';

        if (!empty($all_documents)) {
            foreach ($all_documents as $document) {
                echo '<tr>';
                echo '<td style="padding: 4px; border-bottom: 1px solid #ddd; font-weight: 700;">' . esc_html($document['type']) . '</td>';
                echo '<td style="padding: 4px; border-bottom: 1px solid #ddd;">';

                if ($document['source'] === 'pdfclients') {
                    // Actions admin pour les documents pdfclients
                    echo '<a href="' . esc_url($document['url']) . '" target="_blank" style="text-decoration: none;">
                            <span class="dashicons dashicons-visibility" style="margin-left: 4px;"></span>
                          </a>';
                    
                    $delete_url = add_query_arg(
                        array(
                            'action' => 'delete_pdf_client',
                            'post_id' => $order_id,
                            'file' => urlencode($document['file_name']),
                            '_wpnonce' => wp_create_nonce('delete_pdf_client_' . $document['file_name'])
                        ),
                        admin_url('admin-post.php')
                    );
                    
                    echo '<a href="' . esc_url($delete_url) . '" class="delete-pdf-link" style="text-decoration: none;">
                            <span class="dashicons dashicons-trash" style="margin-left: 4px; color: #dc3545;"></span>
                          </a>';
                } else {
                    // Pour les émargements, juste un lien de visualisation
                    echo '<a href="' . esc_url($document['url']) . '" target="_blank" style="text-decoration: none;">
                            <span class="dashicons dashicons-visibility" style="margin-left: 4px;"></span>
                          </a>';
                }
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr>';
            echo '<td colspan="2" style="padding: 4px; text-align: center; border-bottom: 1px solid #ddd;">Aucun document disponible</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }
}

// ===============================
// 2) AFFICHER LES DOCUMENTS CÔTÉ CLIENT
// ===============================
add_action('woocommerce_order_details_before_order_table', 'afficher_documents_client', 10, 1);
function afficher_documents_client($order) {
    afficher_documents($order->get_id(), 'client');
}

// ===============================
// 3) METABOX ADMIN "DOCUMENTS CLIENT" (INCHANGÉE)
// ===============================
add_action('add_meta_boxes', 'ajouter_meta_boxes_documents');
function ajouter_meta_boxes_documents() {
    add_meta_box(
        'documents_commande',
        'Documents client',
        'afficher_meta_box_documents',
        'shop_order',
        'side',
        'default'
    );
}

// Cette fonction est appelée pour afficher le contenu de la metabox
function afficher_meta_box_documents($post) {
    // Ajouter les styles CSS
    echo get_documents_metabox_css();
    
    // 1) Affiche la liste des documents déjà présents avec cases à cocher
    echo '<div class="doc-metabox-section">';
    echo '<form id="bulk-delete-form" method="POST" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="bulk_delete_pdf_clients" />';
    echo '<input type="hidden" name="post_id" value="' . intval($post->ID) . '" />';
    wp_nonce_field('bulk_delete_pdf_clients_nonce', 'bulk_delete_pdf_clients_nonce');
    
    afficher_documents_avec_checkboxes($post->ID, 'admin');
    
    echo '<div class="bulk-actions-row">';
    echo '<button type="button" id="select-all-docs" class="doc-select-all-btn">Tout sélect.</button>';
    echo '<button type="submit" id="bulk-delete-btn" class="doc-bulk-delete-btn" disabled>Supprimer</button>';
    echo '<button type="button" id="download-selected-docs" class="doc-download-selected-btn" disabled><span class="dashicons dashicons-download"></span></button>';
    echo '</div>';
    echo '</form>';
    echo '</div>';
    
    // 2) Formulaire d'upload
    echo '<div class="doc-metabox-section doc-upload-section">';
    echo '<h4 class="doc-section-title">Ajouter un document</h4>';
    
    echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="POST" enctype="multipart/form-data">';
    // Action pour le hook
    echo '<input type="hidden" name="action" value="upload_pdfclients" />';
    // ID de la commande
    echo '<input type="hidden" name="post_id" value="' . intval($post->ID) . '" />';
    // Nonce pour la sécurité
    wp_nonce_field('upload_pdfclients_nonce', 'upload_pdfclients_nonce');
    
    // Type de document et numéro d'avenant
    echo '<div class="doc-form-row">';
    echo '<select name="doc_type" id="doc_type" class="doc-select">';
    echo '<option value="devis">Devis</option>';
    echo '<option value="convention">Convention</option>';
    echo '<option value="avenant">Avenant</option>';
    echo '</select>';
    echo '<input type="number" name="avenant_num" id="avenant_num" class="doc-input" placeholder="N°" />';
    echo '</div>';
    
    // Upload et bouton de soumission
    echo '<div class="doc-form-row">';
    echo '<div class="doc-file-upload">';
    echo '<input type="file" id="custom_pdf_upload" name="custom_pdf_upload" accept="application/pdf" />';
    echo '<label for="custom_pdf_upload" class="doc-file-label">';
    echo '<span class="doc-file-icon"></span>';
    echo '<span class="doc-file-text">Choisir un fichier</span>';
    echo '</label>';
    echo '<div id="file-chosen" class="doc-file-chosen">Aucun fichier choisi</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="doc-form-row">';
    echo '<button type="submit" class="doc-submit-btn"><span class="doc-submit-icon"></span>Téléverser</button>';
    echo '</div>';
    
    echo '</form>';
    echo '</div>';
    
    // JS : comportement dynamique
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Gestion du champ avenant
        var $docType = $("#doc_type");
        var $avenantField = $("#avenant_num");
        
        function toggleAvenant() {
            if ($docType.val() === "avenant") {
                $avenantField.show();
            } else {
                $avenantField.hide().val("");
            }
        }
        
        $docType.on("change", toggleAvenant);
        toggleAvenant(); // Exécuter au chargement
        
        // Gestion de l'affichage du fichier sélectionné
        $("#custom_pdf_upload").on("change", function() {
            var fileName = $(this).val().split('\\').pop();
            if (fileName) {
                $("#file-chosen").text(fileName).addClass('doc-file-selected');
            } else {
                $("#file-chosen").text("Aucun fichier choisi").removeClass('doc-file-selected');
            }
        });
        
        // Confirmation de suppression
        $(".delete-pdf-link").on("click", function(e) {
            if (!confirm("Êtes-vous sûr de vouloir supprimer ce document ?")) {
                e.preventDefault();
            }
        });
    });
    </script>
    <?php
}

// Fonction pour générer le CSS de la metabox (INCHANGÉE)
function get_documents_metabox_css() {
    return '<style>
        /* Variables CSS */
        :root {
            --doc-primary: #4a6fdc;
            --doc-primary-hover: #3a5ecc;
            --doc-light-bg: #f8f9fa;
            --doc-border: #dee2e6;
            --doc-text: #333;
            --doc-success: #28a745;
            --doc-warning: #ffc107;
            --doc-danger: #dc3545;
        }
        
        /* Styles généraux */
        .doc-metabox-section {
            margin-bottom: 10px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            font-size: 12px;
        }
        
        .doc-section-title {
            font-size: 12px;
            margin: 0 0 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid var(--doc-border);
            color: var(--doc-text);
            font-weight: 600;
        }
        
        .doc-form-row {
            margin-bottom: 8px;
            display: flex;
            gap: 6px;
            align-items: center;
        }
        
        /* Select et Input */
        .doc-select, .doc-input {
            height: 28px;
            border-radius: 3px;
            border: 1px solid var(--doc-border);
            padding: 0 6px;
            font-size: 11px;
            transition: all 0.2s ease;
        }
        
        .doc-select {
            flex: 2;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'10\' height=\'6\' viewBox=\'0 0 10 6\'%3E%3Cpath fill=\'%23666\' d=\'M0 0h10L5 6z\'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            padding-right: 25px;
        }
        
        .doc-input {
            flex: 1;
            display: none; /* Caché par défaut */
			max-width: 40% !important;
        }
        
        .doc-select:focus, .doc-input:focus {
            border-color: var(--doc-primary);
            box-shadow: 0 0 0 1px var(--doc-primary);
            outline: none;
        }
        
        /* Upload de fichier */
        .doc-file-upload {
            width: 100%;
            position: relative;
        }
        
        .doc-file-upload input[type="file"] {
            position: absolute;
            left: -9999px;
        }
        
        .doc-file-label {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            background-color: var(--doc-light-bg);
            border: 1px dashed var(--doc-border);
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
            margin-bottom: 6px;
			max-width: 90% !important;
        }
        
        .doc-file-label:hover {
            border-color: var(--doc-primary);
            background-color: rgba(74, 111, 220, 0.05);
        }
        
        .doc-file-icon {
            width: 18px;
            height: 18px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%234a6fdc\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12\'/%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
        }
        
        .doc-file-text {
            font-size: 11px;
            color: var(--doc-text);
        }
        
        .doc-file-chosen {
            font-size: 10px;
            color: #777;
            padding: 2px 6px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            border-radius: 2px;
        }
        
        .doc-file-selected {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            padding: 2px 6px;
            border-left: 2px solid #28a745;
        }
        
        /* Bouton de soumission */
        .doc-submit-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background-color: var(--doc-primary);
            color: white;
            border: none;
            border-radius: 3px;
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
        }
        
        .doc-submit-btn:hover {
            background-color: var(--doc-primary-hover);
        }
        
        .doc-submit-icon {
            width: 16px;
            height: 16px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'white\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12\'/%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
        }
        
        /* Bouton de suppression */
        .dashicons-trash {
            color: var(--doc-danger);
            transition: transform 0.2s ease;
        }
        .dashicons-trash:hover {
            transform: scale(1.2);
        }
    </style>';
}

// ===============================
// 4) GÉRER LE UPLOAD (admin-post.php) - INCHANGÉ
// ===============================
add_action('admin_post_upload_pdfclients', 'traiter_upload_pdfclients');
function traiter_upload_pdfclients() {
    // Vérifier le nonce
    if (!isset($_POST['upload_pdfclients_nonce']) || 
        !wp_verify_nonce($_POST['upload_pdfclients_nonce'], 'upload_pdfclients_nonce')) {
        wp_die('Security check failed.');
    }

    // Récupérer l'ID de la commande (post_id)
    if (!isset($_POST['post_id'])) {
        wp_die('Missing post_id.');
    }
    $post_id = intval($_POST['post_id']);

    // Vérifier les capacités : l'utilisateur doit pouvoir éditer la commande
    if (!current_user_can('edit_post', $post_id)) {
        wp_die('Not allowed.');
    }

    // Récupérer doc_type et avenant_num
    $doc_type = isset($_POST['doc_type']) ? sanitize_text_field($_POST['doc_type']) : '';
    $avenant_num = isset($_POST['avenant_num']) ? intval($_POST['avenant_num']) : 0;

    // Vérifier si on a bien un fichier PDF
    if (!empty($_FILES['custom_pdf_upload']['name'])) {
        $uploaded_file = $_FILES['custom_pdf_upload'];

        // Construire le nom de fichier en fonction du type
        switch ($doc_type) {
            case 'devis':
                $filename = 'devis-' . $post_id . '.pdf';
                break;
            case 'convention':
                $filename = 'convention-' . $post_id . '.pdf';
                break;
            case 'avenant':
                if ($avenant_num > 0) {
                    $filename = 'avenant-' . $post_id . '-n' . $avenant_num . '.pdf';
                } else {
                    $filename = 'avenant-' . $post_id . '.pdf';
                }
                break;
            default:
                $filename = 'document-' . $post_id . '.pdf';
        }

        // Construire le chemin final
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['basedir'] . '/pdfclients/';
        if (!file_exists($upload_path)) {
            wp_mkdir_p($upload_path);
        }

        $upload_file_path = $upload_path . $filename;

        // S'il existe déjà, on l'écrase
        if (file_exists($upload_file_path)) {
            unlink($upload_file_path);
        }

        // Déplacer le fichier temporaire
        move_uploaded_file($uploaded_file['tmp_name'], $upload_file_path);
    }

    // Rediriger vers la page d'édition de la commande
    wp_redirect(
        add_query_arg(
            array('post' => $post_id, 'action' => 'edit', 'uploaded' => '1'),
            admin_url('post.php')
        )
    );
    exit;
}

// ===============================
// 5) GÉRER LA SUPPRESSION EN LOT (admin-post.php) - NOUVEAU
// ===============================
add_action('admin_post_bulk_delete_pdf_clients', 'supprimer_pdf_clients_en_lot');
function supprimer_pdf_clients_en_lot() {
    // Vérifier les capacités
    if (!current_user_can('edit_posts')) {
        wp_die('Not allowed.');
    }

    // Vérifier le nonce
    if (!isset($_POST['bulk_delete_pdf_clients_nonce']) || !isset($_POST['post_id'])) {
        wp_die('Invalid request.');
    }

    $post_id = intval($_POST['post_id']);
    
    if (!wp_verify_nonce($_POST['bulk_delete_pdf_clients_nonce'], 'bulk_delete_pdf_clients_nonce')) {
        wp_die('Security check failed.');
    }

    // Vérifier que l'utilisateur peut modifier cette commande
    if (!current_user_can('edit_post', $post_id)) {
        wp_die('Not allowed to edit this order.');
    }

    // Récupérer les fichiers à supprimer
    $files_to_delete = isset($_POST['files_to_delete']) ? $_POST['files_to_delete'] : array();
    
    if (!empty($files_to_delete)) {
        $upload_dir = wp_upload_dir();
        $deleted_count = 0;
        
        foreach ($files_to_delete as $file) {
            $file = sanitize_text_field($file);
            $file_path = $upload_dir['basedir'] . '/pdfclients/' . $file;
            
            // Vérifier que le fichier existe et est dans le bon dossier
            if (file_exists($file_path) && strpos($file_path, $upload_dir['basedir'] . '/pdfclients/') === 0) {
                if (unlink($file_path)) {
                    $deleted_count++;
                }
            }
        }
        
        // Rediriger avec un message de succès
        wp_redirect(
            add_query_arg(
                array(
                    'post' => $post_id, 
                    'action' => 'edit', 
                    'bulk_deleted' => $deleted_count
                ),
                admin_url('post.php')
            )
        );
    } else {
        // Rediriger sans message si aucun fichier sélectionné
        wp_redirect(
            add_query_arg(
                array('post' => $post_id, 'action' => 'edit'),
                admin_url('post.php')
            )
        );
    }
    exit;
}

// ===============================
// 6) GÉRER LA SUPPRESSION INDIVIDUELLE (admin-post.php) - INCHANGÉ
// ===============================
add_action('admin_post_delete_pdf_client', 'supprimer_pdf_client');
function supprimer_pdf_client() {
    // Vérifier les capacités
    if (!current_user_can('edit_posts')) {
        wp_die('Not allowed.');
    }

    // Vérifier le nonce
    if (!isset($_GET['_wpnonce']) || !isset($_GET['file']) || !isset($_GET['post_id'])) {
        wp_die('Invalid request.');
    }

    $file = sanitize_text_field(urldecode($_GET['file']));
    $post_id = intval($_GET['post_id']);
    
    if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_pdf_client_' . $file)) {
        wp_die('Security check failed.');
    }

    // Vérifier que l'utilisateur peut modifier cette commande
    if (!current_user_can('edit_post', $post_id)) {
        wp_die('Not allowed to edit this order.');
    }

    // Chemin du fichier
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/pdfclients/' . $file;

    // Vérifier que le fichier existe et est dans le bon dossier
    if (file_exists($file_path) && strpos($file_path, $upload_dir['basedir'] . '/pdfclients/') === 0) {
        unlink($file_path);
    }

    // Rediriger vers la page d'édition de la commande
    wp_redirect(
        add_query_arg(
            array('post' => $post_id, 'action' => 'edit', 'deleted' => '1'),
            admin_url('post.php')
        )
    );
    exit;
}