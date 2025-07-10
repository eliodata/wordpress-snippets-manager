<?php
/**
 * Snippet ID: 179
 * Name: SHORTCODE E2PDF EMARGEMENTS dynamiques VERS CERTIFICATS 
 * Description: 
 * @active false
 */


/**
 * Synchronisation automatique des émargements dans les métas de commandes WooCommerce
 * Compatible avec e2pdf et les shortcodes [e2pdf-wc-order]
 */

/**
 * Hook principal pour synchroniser les émargements à chaque modification des documents
 */
add_action('save_post_action-de-formation', 'sync_emargements_to_linked_orders', 30, 3);
add_action('wp_ajax_refresh_linked_orders_metabox', 'sync_all_orders_emargements'); // Lors du refresh de la metabox

function sync_emargements_to_linked_orders($action_id, $post, $update) {
    if (!$update) return;
    
    error_log("Synchronisation des émargements pour l'action ID: $action_id");
    
    // Récupérer toutes les commandes liées à cette action
    $linked_orders = get_orders_linked_to_action($action_id);
    
    foreach ($linked_orders as $order_id) {
        sync_order_emargements_meta($order_id, $action_id);
    }
}

/**
 * Synchroniser manuellement tous les émargements (fonction utilitaire)
 */
function sync_all_orders_emargements() {
    global $wpdb;
    
    // Récupérer toutes les actions de formation qui ont des commandes liées
    $query = "
        SELECT DISTINCT oim.meta_value as action_id
        FROM {$wpdb->prefix}woocommerce_order_itemmeta AS oim
        WHERE oim.meta_key = 'fsbdd_relsessaction_cpt_produit'
        AND oim.meta_value != ''
    ";
    
    $action_ids = $wpdb->get_col($query);
    
    foreach ($action_ids as $action_id) {
        $linked_orders = get_orders_linked_to_action($action_id);
        foreach ($linked_orders as $order_id) {
            sync_order_emargements_meta($order_id, $action_id);
        }
    }
    
    error_log("Synchronisation globale des émargements terminée");
}

/**
 * Récupérer les commandes liées à une action de formation
 */
function get_orders_linked_to_action($action_id) {
    global $wpdb;
    
    $query = "
        SELECT DISTINCT oi.order_id
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim
            ON oi.order_item_id = oim.order_item_id
        WHERE oim.meta_key = 'fsbdd_relsessaction_cpt_produit'
        AND oim.meta_value = %s
    ";
    
    return $wpdb->get_col($wpdb->prepare($query, $action_id));
}

/**
 * Synchroniser les métas d'émargements pour une commande spécifique
 */
function sync_order_emargements_meta($order_id, $action_id) {
    error_log("Synchronisation émargements pour commande $order_id, action $action_id");
    
    // Nettoyer les anciennes métas d'émargements
    cleanup_old_emargements_meta($order_id);
    
    // Récupérer tous les émargements de l'action
    $emargements = get_action_emargements_files($action_id);
    
    if (empty($emargements)) {
        error_log("Aucun émargement trouvé pour l'action $action_id");
        return;
    }
    
    error_log("Trouvé " . count($emargements) . " émargements pour l'action $action_id");
    
    // Enregistrer chaque émargement avec un index
    foreach ($emargements as $index => $emargement_data) {
        $meta_index = $index + 1; // Commencer à 1 pour e2pdf
        
        // URL sécurisée du fichier
        update_post_meta($order_id, "fsbdd_emargements_{$meta_index}", $emargement_data['url']);
        
        // Nom du fichier
        update_post_meta($order_id, "fsbdd_emargements_{$meta_index}_filename", $emargement_data['filename']);
        
        // Nom du formateur
        update_post_meta($order_id, "fsbdd_emargements_{$meta_index}_formateur", $emargement_data['formateur']);
        
        // Statut du document
        update_post_meta($order_id, "fsbdd_emargements_{$meta_index}_status", $emargement_data['status']);
        
        error_log("Émargement $meta_index enregistré: " . $emargement_data['filename']);
    }
    
    // Métadonnées globales
    update_post_meta($order_id, 'fsbdd_emargements_count', count($emargements));
    
    // Compatibilité avec l'ancien système - premier émargement
    if (!empty($emargements)) {
        update_post_meta($order_id, 'fsbdd_emargements', $emargements[0]['url']);
    }
    
    error_log("Synchronisation terminée pour la commande $order_id");
}

/**
 * Nettoyer les anciennes métas d'émargements
 */
function cleanup_old_emargements_meta($order_id) {
    global $wpdb;
    
    // Supprimer toutes les métas commençant par fsbdd_emargements_
    $wpdb->query($wpdb->prepare("
        DELETE FROM {$wpdb->postmeta} 
        WHERE post_id = %d 
        AND meta_key LIKE 'fsbdd_emargements_%'
    ", $order_id));
    
    // Supprimer aussi la meta principale
    delete_post_meta($order_id, 'fsbdd_emargements');
}

/**
 * Récupérer tous les fichiers d'émargements d'une action avec leurs métadonnées
 */
function get_action_emargements_files($action_id) {
    $action_title_slug = sanitize_title(get_the_title($action_id));
    $formateur_ids = fsbdd_get_action_formateur_ids($action_id);
    
    $emargements = [];
    
    if (empty($formateur_ids)) {
        return $emargements;
    }
    
    foreach ($formateur_ids as $formateur_id) {
        $formateur_post = get_post($formateur_id);
        if (!$formateur_post) continue;
        
        $formateur_name = $formateur_post->post_title;
        $action_dir = fsbdd_get_trainer_action_dir_path($formateur_id, $action_title_slug);
        
        if (!is_dir($action_dir)) continue;
        
        $files_in_dir = glob($action_dir . '/*');
        if (empty($files_in_dir)) continue;
        
        foreach ($files_in_dir as $file_path) {
            if (is_dir($file_path)) continue;
            
            $file_name = basename($file_path);
            if (strpos($file_name, 'emargements') === false) continue;
            
            // Créer l'URL sécurisée
            $secure_url = add_query_arg(
                'fsbdd_file',
                urlencode(str_replace(FSBDD_UPLOAD_DIR_PATH . '/', '', $file_path)),
                site_url('/')
            );
            
            // Déterminer le statut
            $meta_key_sent = '_sent_' . md5($file_path);
            $meta_key_validated = '_validated_' . md5($file_path);
            $send_date = get_post_meta($action_id, $meta_key_sent, true);
            $validation_date = get_post_meta($action_id, $meta_key_validated, true);
            
            $status = 'non_recu';
            if (!empty($validation_date)) {
                $status = 'valide';
            } elseif (!empty($send_date)) {
                $status = 'recu';
            }
            
            $emargements[] = [
                'url' => $secure_url,
                'filename' => $file_name,
                'formateur' => $formateur_name,
                'formateur_id' => $formateur_id,
                'status' => $status,
                'file_path' => $file_path
            ];
        }
    }
    
    // Trier par nom de formateur puis par nom de fichier
    usort($emargements, function($a, $b) {
        if ($a['formateur'] === $b['formateur']) {
            return strcmp($a['filename'], $b['filename']);
        }
        return strcmp($a['formateur'], $b['formateur']);
    });
    
    return $emargements;
}

/**
 * Hook sur la validation/suppression de fichiers pour synchroniser immédiatement
 */
add_action('updated_post_meta', 'sync_on_file_validation', 10, 4);

function sync_on_file_validation($meta_id, $post_id, $meta_key, $meta_value) {
    // Vérifier si c'est une métadonnée de validation/envoi de fichier
    if (strpos($meta_key, '_validated_') === 0 || strpos($meta_key, '_sent_') === 0) {
        $post = get_post($post_id);
        if ($post && $post->post_type === 'action-de-formation') {
            // Synchroniser avec un léger délai pour éviter les appels multiples
            wp_schedule_single_event(time() + 2, 'delayed_sync_emargements', [$post_id]);
        }
    }
}

/**
 * Hook pour la synchronisation différée
 */
add_action('delayed_sync_emargements', 'delayed_sync_emargements_callback');

function delayed_sync_emargements_callback($action_id) {
    $linked_orders = get_orders_linked_to_action($action_id);
    foreach ($linked_orders as $order_id) {
        sync_order_emargements_meta($order_id, $action_id);
    }
}

/**
 * Shortcode simple pour e2pdf (alternative si les métas ne marchent pas)
 */
add_shortcode('fsbdd-emargement-url', 'fsbdd_emargement_url_shortcode');

function fsbdd_emargement_url_shortcode($atts) {
    $atts = shortcode_atts([
        'index' => '1',
        'order_id' => ''
    ], $atts);
    
    $order_id = $atts['order_id'];
    
    // Si pas d'order_id, essayer de le récupérer du contexte
    if (empty($order_id)) {
        if (isset($_GET['order_id'])) {
            $order_id = intval($_GET['order_id']);
        } elseif (isset($GLOBALS['e2pdf_order_id'])) {
            $order_id = $GLOBALS['e2pdf_order_id'];
        }
    }
    
    if (empty($order_id)) {
        return '';
    }
    
    $meta_key = "fsbdd_emargements_{$atts['index']}";
    $url = get_post_meta($order_id, $meta_key, true);
    
    return $url ? $url : '';
}

/**
 * Fonction pour forcer la synchronisation manuelle (à appeler une fois)
 */
function force_sync_all_emargements() {
    sync_all_orders_emargements();
    error_log("Synchronisation forcée de tous les émargements terminée");
}

// Décommenter la ligne suivante pour forcer une synchronisation complète au chargement (à faire une seule fois)
// add_action('init', 'force_sync_all_emargements');
