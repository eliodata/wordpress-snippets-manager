<?php
/**
 * Snippet ID: 177
 * Name: associations couplages commandes / affaires depuis import csv rapprochements sessions
 * Description: 
 * @active true
 */


// Ajouter la page d'administration
add_action('admin_menu', 'fsbdd_importer_add_admin_page');
function fsbdd_importer_add_admin_page() {
    add_management_page(
        'Import Associations Commandes-Actions',
        'Import Commandes-Actions',
        'manage_options',
        'fsbdd-import-associations',
        'fsbdd_importer_admin_page'
    );
}

// Activation - Créer les options nécessaires
register_activation_hook(__FILE__, 'fsbdd_importer_activation');
function fsbdd_importer_activation() {
    add_option('fsbdd_import_state', [
        'status' => 'idle',
        'total' => 0,
        'processed' => 0,
        'success' => 0,
        'errors' => 0,
        'associations' => [],
        'results' => []
    ]);
}

// Désactivation - Nettoyer les options
register_deactivation_hook(__FILE__, 'fsbdd_importer_deactivation');
function fsbdd_importer_deactivation() {
    delete_option('fsbdd_import_state');
}

// Page d'administration
function fsbdd_importer_admin_page() {
    $import_state = get_option('fsbdd_import_state', [
        'status' => 'idle', 'total' => 0, 'processed' => 0, 'success' => 0, 'errors' => 0, 'associations' => [], 'results' => []
    ]);

    // Étape 1: Upload du fichier CSV
    if (isset($_POST['fsbdd_upload_submit']) && isset($_FILES['fsbdd_csv_file'])) {
        if (check_admin_referer('fsbdd_import_step1', 'fsbdd_upload_nonce')) {
            $file = $_FILES['fsbdd_csv_file'];

            if ($file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
                $handle = fopen($file['tmp_name'], 'r');
                if ($handle !== FALSE) {
                    $sample = fgets($handle);
                    rewind($handle);
                    $delimiter = (strpos($sample, ';') !== false) ? ';' : ',';

                    $associations = [];
                    while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                        if (count($data) >= 2) {
                            $num_conv = trim($data[0]);
                            $inter_numero = trim($data[1]);

                            if (!empty($num_conv) && !empty($inter_numero)) {
                                $associations[] = [
                                    'num_conv' => $num_conv,
                                    'inter_numero' => $inter_numero,
                                    'status' => 'pending'
                                ];
                            }
                        }
                    }
                    fclose($handle);

                    if (!empty($associations)) {
                        $import_state = [
                            'status' => 'ready',
                            'total' => count($associations),
                            'processed' => 0,
                            'success' => 0,
                            'errors' => 0,
                            'associations' => $associations,
                            'results' => []
                        ];
                        update_option('fsbdd_import_state', $import_state);
                        wp_redirect(admin_url('tools.php?page=fsbdd-import-associations&step=2'));
                        exit;
                    } else {
                        echo '<div class="notice notice-error"><p>Aucune association valide trouvée dans le fichier. Vérifiez le format (fsbdd_numconv,fsbdd_inter_numero) et le délimiteur.</p></div>';
                    }
                } else {
                     echo '<div class="notice notice-error"><p>Impossible d\'ouvrir le fichier téléversé.</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>Erreur lors du téléversement du fichier: Code ' . esc_html($file['error']) . '</p></div>';
            }
        }
    }

    // Étape 2: Traitement par lots
    if (isset($_GET['step']) && $_GET['step'] == '2' && $import_state['status'] == 'ready') {
        $batch_size = 10;
        $start_index = $import_state['processed'];

        if (!is_array($import_state['associations'])) {
             $import_state['associations'] = [];
        }

        $batch = array_slice($import_state['associations'], $start_index, $batch_size);

        foreach ($batch as $index => $assoc) {
            $real_index = $start_index + $index;

            $num_conv_to_process = isset($assoc['num_conv']) ? $assoc['num_conv'] : null;
            $inter_numero_to_process = isset($assoc['inter_numero']) ? $assoc['inter_numero'] : null;

            if ($num_conv_to_process === null || $inter_numero_to_process === null) {
                $import_state['errors']++;
                $import_state['results'][] = [
                    'status' => 'error',
                    'num_conv' => $num_conv_to_process ?? 'N/A',
                    'inter_numero' => $inter_numero_to_process ?? 'N/A',
                    'message' => 'Données d\'association manquantes ou corrompues.'
                ];
                if (isset($import_state['associations'][$real_index])) {
                    $import_state['associations'][$real_index]['status'] = 'error';
                }
                continue;
            }

            $result = fsbdd_associate_order_with_action($num_conv_to_process, $inter_numero_to_process);

            if (is_wp_error($result)) {
                $import_state['errors']++;
                $import_state['results'][] = [
                    'status' => 'error',
                    'num_conv' => $num_conv_to_process,
                    'inter_numero' => $inter_numero_to_process,
                    'message' => $result->get_error_message()
                ];
                if (isset($import_state['associations'][$real_index])) {
                    $import_state['associations'][$real_index]['status'] = 'error';
                }
            } else {
                $import_state['success']++;
                $import_state['results'][] = [
                    'status' => 'success',
                    'num_conv' => $num_conv_to_process,
                    'inter_numero' => $inter_numero_to_process
                ];
                 if (isset($import_state['associations'][$real_index])) {
                    $import_state['associations'][$real_index]['status'] = 'success';
                }
            }
        }

        $import_state['processed'] = min($start_index + count($batch), $import_state['total']);

        if ($import_state['processed'] >= $import_state['total']) {
            $import_state['status'] = 'completed';
        }
        update_option('fsbdd_import_state', $import_state);

        if ($import_state['status'] == 'ready') {
            echo '<meta http-equiv="refresh" content="1;url=' . esc_url(admin_url('tools.php?page=fsbdd-import-associations&step=2')) . '">';
        }
    }

    if (isset($_POST['fsbdd_reset_state'])) {
        if (check_admin_referer('fsbdd_reset_state', 'fsbdd_reset_nonce')) {
            $import_state = [
                'status' => 'idle', 'total' => 0, 'processed' => 0, 'success' => 0, 'errors' => 0, 'associations' => [], 'results' => []
            ];
            update_option('fsbdd_import_state', $import_state);
            wp_redirect(admin_url('tools.php?page=fsbdd-import-associations'));
            exit;
        }
    }
    ?>
    <div class="wrap">
        <h1>Import d'associations Commandes-Actions de formation</h1>

        <?php if ($import_state['status'] == 'idle'): ?>
            <div class="card">
                <h2>Instructions</h2>
                <p>Importez un fichier CSV contenant les associations entre commandes et actions de formation.</p>
                <p>Format attendu : <code>NUMERO_CONVENTION_COMMANDE,NUMERO_INTER_ACTION</code> (une association par ligne).</p>
                <p>Exemple: <code>CONV001,ACTIONXYZ002</code></p>
                <p>Le fichier peut utiliser une virgule (<code>,</code>) ou un point-virgule (<code>;</code>) comme délimiteur.</p>
                <p><strong>Assurez-vous que les numéros de convention et les numéros INTER sont uniques et existent dans le système.</strong></p>
                <p><em>Optionnel : Si votre CSV contient une ligne d'en-tête (ex: "NumConv,NumInter"), elle sera ignorée si vous décommentez la ligne appropriée dans le code du plugin.</em></p>
            </div>

            <form method="post" enctype="multipart/form-data" id="fsbdd-import-form">
                <?php wp_nonce_field('fsbdd_import_step1', 'fsbdd_upload_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="fsbdd_csv_file">Fichier CSV</label></th>
                        <td><input type="file" name="fsbdd_csv_file" id="fsbdd_csv_file" accept=".csv" required></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="fsbdd_upload_submit" class="button button-primary" value="Téléverser et Analyser">
                </p>
            </form>

        <?php elseif ($import_state['status'] == 'ready'):
            $progress_percent = ($import_state['total'] > 0) ? round(($import_state['processed'] / $import_state['total']) * 100) : 0;
            ?>
            <div class="notice notice-info">
                <p>Traitement en cours... Veuillez patienter.</p>
                <p>Progrès : <?php echo esc_html($import_state['processed']); ?> / <?php echo esc_html($import_state['total']); ?> (<?php echo esc_html($progress_percent); ?>%)</p>
            </div>
            <div style="width: 100%; height: 20px; background-color: #f1f1f1; margin-bottom: 20px;">
                <div style="width: <?php echo esc_attr($progress_percent); ?>%; height: 100%; background-color: #0073aa;"></div>
            </div>
            <p class="description">La page se rafraîchira automatiquement pour continuer le traitement...</p>

        <?php elseif ($import_state['status'] == 'completed'): ?>
            <div class="notice notice-success">
                <p>Import terminé !</p>
                <p>Total: <?php echo esc_html($import_state['total']); ?> | Réussites: <?php echo esc_html($import_state['success']); ?> | Échecs: <?php echo esc_html($import_state['errors']); ?></p>
            </div>

            <h3>Résultats de l'import</h3>
            <div style="max-height: 400px; overflow-y: auto; margin: 20px 0; padding: 10px; border: 1px solid #ddd;">
                <?php if (is_array($import_state['results'])): ?>
                    <?php foreach ($import_state['results'] as $result): ?>
                        <?php if ($result['status'] == 'success'): ?>
                            <p class="success">Succès - Commande (NumConv: <?php echo esc_html($result['num_conv']); ?>) → Action (NumInter: <?php echo esc_html($result['inter_numero']); ?>)</p>
                        <?php else: ?>
                            <p class="error">Erreur - Commande (NumConv: <?php echo esc_html($result['num_conv']); ?>) → Action (NumInter: <?php echo esc_html($result['inter_numero']); ?>) : <?php echo esc_html($result['message']); ?></p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                     <p>Aucun résultat à afficher.</p>
                <?php endif; ?>
            </div>

            <form method="post">
                <?php wp_nonce_field('fsbdd_reset_state', 'fsbdd_reset_nonce'); ?>
                <p class="submit">
                    <input type="submit" name="fsbdd_reset_state" class="button button-primary" value="Nouvel Import">
                </p>
            </form>
        <?php endif; ?>
    </div>
    <style> .success { color: #46b450; } .error { color: #dc3232; } </style>
    <?php
}

// --- Fonctions d'aide pour récupérer les objets par meta ---

/**
 * Récupère une commande WooCommerce par sa méta 'fsbdd_numconv'.
 * @param string $num_conv La valeur de la méta 'fsbdd_numconv'.
 * @return WC_Order|null L'objet commande ou null si non trouvée.
 */
function fsbdd_get_order_by_numconv($num_conv) {
    if (empty($num_conv)) return null;

    $orders = wc_get_orders([
        'limit' => 1,
        'meta_key' => 'fsbdd_numconv',
        'meta_value' => $num_conv,
        'meta_compare' => '=',
    ]);

    return !empty($orders) ? $orders[0] : null;
}

/**
 * Récupère un post 'action-de-formation' par sa méta 'fsbdd_inter_numero'.
 * @param string $inter_numero La valeur de la méta 'fsbdd_inter_numero'.
 * @return WP_Post|null L'objet post ou null si non trouvé.
 */
function fsbdd_get_action_by_inter_numero($inter_numero) {
    if (empty($inter_numero)) return null;

    $args = [
        'post_type' => 'action-de-formation',
        'posts_per_page' => 1,
        'meta_key' => 'fsbdd_inter_numero',
        'meta_value' => $inter_numero,
        'meta_compare' => '=',
        'post_status' => 'any',
    ];
    $actions = get_posts($args);

    return !empty($actions) ? $actions[0] : null;
}

// Fonction d'association modifiée
function fsbdd_associate_order_with_action($num_conv, $inter_numero) {
    // Récupérer la commande par fsbdd_numconv
    $order = fsbdd_get_order_by_numconv($num_conv);
    if (!$order) {
        return new WP_Error('invalid_order_numconv', "La commande avec NumConv '$num_conv' n'existe pas.");
    }
    $order_id = $order->get_id();

    // Récupérer l'action de formation par fsbdd_inter_numero
    $action_post = fsbdd_get_action_by_inter_numero($inter_numero);
    if (!$action_post || $action_post->post_type !== 'action-de-formation') {
        return new WP_Error('invalid_action_inter_numero', "L'action de formation avec NumInter '$inter_numero' n'existe pas ou n'est pas du bon type.");
    }
    $action_id = $action_post->ID;

    $product_id = get_post_meta($action_id, 'fsbdd_relsessproduit', true);
    if (!$product_id) {
        return new WP_Error('no_product', "Aucun produit associé à l'action (NumInter: $inter_numero, ID: $action_id)");
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        return new WP_Error('invalid_product', "Le produit (ID: $product_id) associé à l'action (NumInter: $inter_numero) est invalide");
    }

    $lieu_complet = get_post_meta($action_id, 'fsbdd_select_lieusession', true) ?: 'Adresse inconnue';
    $startdate = get_post_meta($action_id, 'we_startdate', true);
    $enddate = get_post_meta($action_id, 'we_enddate', true);
    $numero_action_title = get_the_title($action_id);

    $start_fmt = $startdate ? date_i18n(get_option('date_format'), $startdate) : 'Date non définie';
    $end_fmt = $enddate ? date_i18n(get_option('date_format'), $enddate) : 'Date non définie';

    try {
        // 1. Supprimer les produits existants de la commande
        foreach ($order->get_items() as $item_id_to_remove => $item) {
            $order->remove_item($item_id_to_remove);
        }

        // 2. Ajouter le nouveau produit de l'action à la commande
        $effectif = 1;
        $new_item_id = $order->add_product($product, $effectif);

        if (!$new_item_id) {
            return new WP_Error('add_item_failed', "Impossible d'ajouter le produit (ID: $product_id) à la commande (NumConv: $num_conv)");
        }

        $line_item = $order->get_item($new_item_id);
        if (!$line_item) {
            return new WP_Error('get_item_failed', "Impossible de récupérer l'item nouvellement ajouté à la commande (NumConv: $num_conv)");
        }

        // 3. Ajouter/Mettre à jour les métadonnées de l'item de commande
        $line_item->update_meta_data('fsbdd_relsessaction_cpt_produit', $action_id);
        $line_item->update_meta_data('we_startdate', $start_fmt);
        $line_item->update_meta_data('we_enddate', $end_fmt);
        $line_item->update_meta_data('fsbdd_actionum', $numero_action_title);
        $line_item->update_meta_data('fsbdd_inter_numero_associe', $inter_numero);
        $line_item->update_meta_data('fsbdd_select_lieuforminter', $lieu_complet);

        $line_item->delete_meta_data('session_data');
        $line_item->save();

        // 4. Recalculer les totaux et sauvegarder la commande
        $order->calculate_totals(true);
        $order->save();

        return true;
    } catch (Exception $e) {
        return new WP_Error('association_error', "Erreur lors de l'association pour Commande (NumConv: $num_conv) et Action (NumInter: $inter_numero): " . $e->getMessage());
    }
}
