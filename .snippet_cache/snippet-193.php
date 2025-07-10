<?php
/**
 * Snippet ID: 193
 * Name: VERSIONING pdf doc formateurs lettres de mission
 * Description: 
 * @active true
 */

/**
 * Intégration du système de versioning des PDFs - Version corrigée pour Code Snippets
 * Compatible avec pdf-versioning.txt
 * 
 * Ce fichier doit être utilisé comme code snippet WordPress pour activer
 * le nouveau système de versioning intelligent.
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// == FONCTIONS COMPLÉMENTAIRES (uniquement si pas déjà définies) ==
// =============================================================================

/**
 * Shortcode pour afficher la version dans les PDFs E2PDF
 */
if (!function_exists('fsbdd_pdf_version_shortcode')) {
    function fsbdd_pdf_version_shortcode($atts) {
        $atts = shortcode_atts([
            'cpt_id' => get_the_ID(),
            'formateur_id' => null,
            'format' => 'version' // 'version', 'date', 'both'
        ], $atts);
        
        if (!$atts['cpt_id'] || !$atts['formateur_id']) {
            return '';
        }
        
        $versions = get_post_meta($atts['cpt_id'], 'fsbdd_pdf_versions', true) ?: [];
        
        if (!isset($versions[$atts['formateur_id']])) {
            return 'v1.0';
        }
        
        $formateur_data = $versions[$atts['formateur_id']];
        $current_version = $formateur_data['current_version'];
        // S'assurer que la version commence par 'v'
        if (strpos($current_version, 'v') !== 0) {
            $version = 'v' . $current_version;
        } else {
            $version = $current_version;
        }
        $date = '';
        
        if ($formateur_data['pdf_generated_date']) {
            $date = date('d/m/Y H:i', $formateur_data['pdf_generated_date']);
        }
        
        switch ($atts['format']) {
            case 'version':
                return $version;
            case 'date':
                return $date;
            case 'both':
                return $version . ($date ? ' - ' . $date : '');
            default:
                return $version;
        }
    }
    add_shortcode('fsbdd_pdf_version', 'fsbdd_pdf_version_shortcode');
}

/**
 * Shortcode global pour E2PDF qui récupère automatiquement les IDs
 * Usage: [fsbdd_pdf_version_auto index="1" format="version"]
 */
if (!function_exists('fsbdd_pdf_version_auto_shortcode')) {
    function fsbdd_pdf_version_auto_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'index' => '1',
            'format' => 'version' // 'version', 'date', 'both'
        ], $atts);
        
        // Récupérer le CPT ID depuis le contenu ou le contexte global
        $cpt_id = 0;
        if (!empty($content)) {
            $resolved = do_shortcode($content);
            $cpt_id = (int) trim($resolved);
        } else {
            $cpt_id = get_the_ID();
        }
        
        if (!$cpt_id) {
            return '';
        }
        
        // Récupérer les formateurs du planning
        $formateurs_ids = fsbdd_get_formateurs_from_planning($cpt_id);
        
        if (empty($formateurs_ids)) {
            return 'v1.0';
        }
        
        $index = (int) $atts['index'] - 1;
        
        if (!isset($formateurs_ids[$index])) {
            return 'v1.0';
        }
        
        $formateur_id = $formateurs_ids[$index];
        
        // Utiliser le shortcode existant
        return fsbdd_pdf_version_shortcode([
            'cpt_id' => $cpt_id,
            'formateur_id' => $formateur_id,
            'format' => $atts['format']
        ]);
    }
    add_shortcode('fsbdd_pdf_version_auto', 'fsbdd_pdf_version_auto_shortcode');
}

/**
 * Styles CSS pour l'interface admin
 */
if (!function_exists('fsbdd_versioning_admin_styles')) {
    function fsbdd_versioning_admin_styles() {
        if (!is_admin()) {
            return;
        }
        
        echo '<style>
        /* Styles pour les statuts intelligents */
        .fsbdd-smart-status {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 500;
            margin: 2px 4px 2px 0;
        }
        
        .fsbdd-smart-status .dashicons {
            font-size: 14px;
            margin-right: 4px;
        }
        
        .fsbdd-status-error_email {
            background-color: #dc3232;
            color: white;
        }
        
        .fsbdd-status-error_pdf {
            background-color: #dc3232;
            color: white;
        }
        
        .fsbdd-status-planning_changed {
            background-color: #ff9800;
            color: white;
        }
        
        .fsbdd-status-pdf_updated {
            background-color: #ff9800;
            color: white;
        }
        
        .fsbdd-status-ready_to_send {
            background-color: #0073aa;
            color: white;
        }
        
        .fsbdd-status-sent {
            background-color: #00a32a;
            color: white;
        }
        
        .fsbdd-version-info {
            font-size: 11px;
            color: #666;
            margin-left: 8px;
        }
        
        .fsbdd-status-container {
            margin: 5px 0;
        }
        
        .fsbdd-formateur-row {
            padding: 8px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .fsbdd-formateur-name {
            font-weight: 500;
            flex: 1;
        }
        
        .fsbdd-formateur-status {
            display: flex;
            align-items: center;
        }
        
        /* Dashboard des statuts */
        .fsbdd-status-dashboard {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
        }
        
        .fsbdd-dashboard-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        
        .fsbdd-dashboard-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .fsbdd-summary-item {
            text-align: center;
            padding: 8px;
            background: white;
            border-radius: 3px;
            border: 1px solid #ddd;
        }
        
        .fsbdd-summary-number {
            font-size: 18px;
            font-weight: bold;
            display: block;
        }
        
        .fsbdd-summary-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }
        </style>';
    }
    add_action('admin_head', 'fsbdd_versioning_admin_styles');
}

/**
 * Migration des données existantes
 */
if (!function_exists('fsbdd_migrate_existing_data')) {
    function fsbdd_migrate_existing_data() {
        // Vérifier si la migration a déjà été effectuée
        if (get_option('fsbdd_versioning_migrated', false)) {
            return;
        }
        
        $posts = get_posts([
            'post_type' => 'intervention',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'fsbdd_lettres_generees',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        $migrated_count = 0;
        
        foreach ($posts as $post) {
            $cpt_id = $post->ID;
            $lettres_generees = get_post_meta($cpt_id, 'fsbdd_lettres_generees', true) ?: [];
            
            if (empty($lettres_generees)) {
                continue;
            }
            
            // Obtenir les formateurs (utiliser la fonction existante si disponible)
            $formateurs = [];
            if (function_exists('fsbdd_get_formateurs_from_planning')) {
                $formateurs = fsbdd_get_formateurs_from_planning($cpt_id);
            }
            
            $versions = get_post_meta($cpt_id, 'fsbdd_pdf_versions', true) ?: [];
            
            foreach ($lettres_generees as $formateur_id => $timestamp) {
                if (!isset($versions[$formateur_id])) {
                    $versions[$formateur_id] = [
                        'current_version' => 1,
                        'pdf_generated_date' => $timestamp,
                        'planning_hash' => '',
                        'last_email_sent' => 0,
                        'versions' => []
                    ];
                    
                    // Chercher la date d'email correspondante
                    $email_meta_key = "fsbdd_email_sent_lettre_mission_{$formateur_id}";
                    $email_date = get_post_meta($cpt_id, $email_meta_key, true);
                    
                    if ($email_date) {
                        $versions[$formateur_id]['last_email_sent'] = strtotime($email_date);
                    }
                }
            }
            
            if (!empty($versions)) {
                update_post_meta($cpt_id, 'fsbdd_pdf_versions', $versions);
                $migrated_count++;
            }
        }
        
        // Marquer la migration comme terminée
        update_option('fsbdd_versioning_migrated', true);
        
        // Afficher un message d'admin
        if ($migrated_count > 0) {
            add_action('admin_notices', function() use ($migrated_count) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>Migration du système de versioning terminée :</strong> ' . $migrated_count . ' interventions migrées.</p>';
                echo '</div>';
            });
        }
    }
}

// =============================================================================
// == ACTIVATION AUTOMATIQUE ==
// =============================================================================

// Lancer la migration automatiquement si nécessaire
if (!get_option('fsbdd_versioning_migrated', false)) {
    add_action('init', 'fsbdd_migrate_existing_data');
}

/**
 * Système de versioning léger pour les PDFs de lettres de mission
 * 
 * Fonctionnalités :
 * - Détection automatique des changements dans le planning
 * - Versioning simple avec hash de contenu
 * - Synchronisation des statuts avec la réalité
 * - Optimisation des performances
 */

// =============================================================================
// == SYSTÈME DE VERSIONING DES PDFS ==
// =============================================================================

/**
 * Structure des métadonnées de versioning :
 * fsbdd_pdf_versions = [
 *     formateur_id => [
 *         'current_version' => 'v1.2',
 *         'planning_hash' => 'abc123...',
 *         'pdf_generated_date' => timestamp,
 *         'pdf_file_exists' => true/false,
 *         'last_email_sent' => timestamp,
 *         'versions' => [
 *             'v1.0' => ['date' => timestamp, 'planning_hash' => 'xyz789...'],
 *             'v1.1' => ['date' => timestamp, 'planning_hash' => 'def456...'],
 *             'v1.2' => ['date' => timestamp, 'planning_hash' => 'abc123...']
 *         ]
 *     ]
 * ]
 */

/**
 * Génère un hash du planning pour un formateur spécifique
 */
function fsbdd_get_planning_hash_for_formateur($cpt_id, $formateur_id) {
    $planning = get_post_meta($cpt_id, 'fsbdd_planning', true);
    
    if (empty($planning) || !is_array($planning)) {
        return '';
    }
    
    $formateur_planning = [];
    
    foreach ($planning as $seance) {
        if (isset($seance['fsbdd_gpformatr']) && is_array($seance['fsbdd_gpformatr'])) {
            foreach ($seance['fsbdd_gpformatr'] as $formateur_info) {
                if (is_array($formateur_info) && 
                    isset($formateur_info['fsbdd_user_formateurrel']) &&
                    (int) $formateur_info['fsbdd_user_formateurrel'] === (int) $formateur_id) {
                    
                    // Extraire les données pertinentes pour le hash
                    $seance_data = [
                        'date' => $seance['fsbdd_date'] ?? '',
                        'heure_debut' => $seance['fsbdd_heure_debut'] ?? '',
                        'heure_fin' => $seance['fsbdd_heure_fin'] ?? '',
                        'lieu' => $seance['fsbdd_lieu'] ?? '',
                        'formateur_role' => $formateur_info['fsbdd_role'] ?? '',
                        'formateur_infos' => $formateur_info['fsbdd_infos'] ?? ''
                    ];
                    
                    $formateur_planning[] = $seance_data;
                }
            }
        }
    }
    
    // Trier pour avoir un hash cohérent
    usort($formateur_planning, function($a, $b) {
        return strcmp($a['date'] . $a['heure_debut'], $b['date'] . $b['heure_debut']);
    });
    
    return md5(serialize($formateur_planning));
}

/**
 * Vérifie si le planning a changé pour un formateur
 */
function fsbdd_has_planning_changed($cpt_id, $formateur_id) {
    $versions = get_post_meta($cpt_id, 'fsbdd_pdf_versions', true) ?: [];
    $current_hash = fsbdd_get_planning_hash_for_formateur($cpt_id, $formateur_id);
    
    if (!isset($versions[$formateur_id])) {
        return true; // Première fois
    }
    
    // Utiliser le hash précédent pour détecter les vrais changements de planning
    $previous_hash = $versions[$formateur_id]['previous_planning_hash'] ?? $versions[$formateur_id]['planning_hash'];
    return $current_hash !== $previous_hash;
}

/**
 * Met à jour la version du PDF pour un formateur
 */
function fsbdd_update_pdf_version($cpt_id, $formateur_id) {
    $versions = get_post_meta($cpt_id, 'fsbdd_pdf_versions', true) ?: [];
    $current_hash = fsbdd_get_planning_hash_for_formateur($cpt_id, $formateur_id);
    $timestamp = current_time('timestamp');
    
    // Initialiser si nécessaire
    if (!isset($versions[$formateur_id])) {
        $versions[$formateur_id] = [
            'current_version' => 'v1.0',
            'planning_hash' => $current_hash,
            'previous_planning_hash' => $current_hash,
            'pdf_generated_date' => $timestamp,
            'pdf_file_exists' => false,
            'last_email_sent' => null,
            'versions' => []
        ];
    }
    
    $formateur_data = &$versions[$formateur_id];
    
    // Sauvegarder l'ancienne version avant d'incrémenter
    $old_version = $formateur_data['current_version'];
    $formateur_data['versions'][$old_version] = [
        'date' => $formateur_data['pdf_generated_date'],
        'planning_hash' => $formateur_data['planning_hash']
    ];
    
    // Créer une nouvelle version à chaque génération
    $version_parts = explode('.', $old_version);
    $major = (int) str_replace('v', '', $version_parts[0]);
    $minor = isset($version_parts[1]) ? (int) $version_parts[1] + 1 : 1;
    $new_version = "v{$major}.{$minor}";
    
    $formateur_data['current_version'] = $new_version;
    
    // Conserver l'ancien hash temporairement pour la détection de changement
    $formateur_data['previous_planning_hash'] = $formateur_data['planning_hash'];
    $formateur_data['planning_hash'] = $current_hash;
    
    // Mettre à jour les infos de génération
    $formateur_data['pdf_generated_date'] = $timestamp;
    
    // Vérifier l'existence du fichier
    $upload_dir = wp_upload_dir();
    $numero_inter = get_post_meta($cpt_id, 'fsbdd_inter_numero', true);
    $pdf_filename = "lettre-mission-{$numero_inter}-{$formateur_id}.pdf";
    $pdf_path = $upload_dir['basedir'] . '/pdfformateurs/' . $cpt_id . '/' . $pdf_filename;
    $formateur_data['pdf_file_exists'] = file_exists($pdf_path);
    
    // Nettoyer les anciennes versions (garder seulement les 5 dernières)
    if (count($formateur_data['versions']) > 5) {
        $formateur_data['versions'] = array_slice($formateur_data['versions'], -5, null, true);
    }
    
    update_post_meta($cpt_id, 'fsbdd_pdf_versions', $versions);
    
    return $formateur_data['current_version'];
}

/**
 * Obtient le statut intelligent d'un formateur
 */
function fsbdd_get_formateur_smart_status($cpt_id, $formateur_id) {
    $versions = get_post_meta($cpt_id, 'fsbdd_pdf_versions', true) ?: [];
    $email_formateur = get_post_meta($formateur_id, 'fsbdd_email_mail1', true);
    
    // Vérifications de base
    if (empty($email_formateur) || !is_email($email_formateur)) {
        return [
            'status' => 'error_email',
            'message' => 'Email manquant ou invalide',
            'priority' => 'high',
            'icon' => 'dashicons-warning',
            'color' => '#d63638'
        ];
    }
    
    if (!isset($versions[$formateur_id])) {
        return [
            'status' => 'no_pdf',
            'message' => 'PDF non généré',
            'priority' => 'high',
            'icon' => 'dashicons-media-document',
            'color' => '#d63638'
        ];
    }
    
    $formateur_data = $versions[$formateur_id];
    
    if (!$formateur_data['pdf_file_exists']) {
        return [
            'status' => 'error_pdf',
            'message' => 'Fichier PDF manquant',
            'priority' => 'high',
            'icon' => 'dashicons-warning',
            'color' => '#d63638'
        ];
    }
    
    // Vérifier le statut de validation de la lettre de mission
    $acceptance_data = get_post_meta($cpt_id, 'fsbdd_lettre_acceptee_' . $formateur_id, true);
    $validation_status = !empty($acceptance_data) ? 'validated' : null;
    $validation_version = !empty($acceptance_data) ? ($acceptance_data['version'] ?? 'v1.0') : null;
    $current_version = $formateur_data['current_version'] ?? null;
    
    // Vérifier si le planning a changé depuis la dernière génération
    $planning_changed = fsbdd_has_planning_changed($cpt_id, $formateur_id);
    
    if ($planning_changed) {
        return [
            'status' => 'planning_changed',
            'message' => 'Planning modifié - PDF à régénérer',
            'priority' => 'high',
            'icon' => 'dashicons-update-alt',
            'color' => '#ff9800'
        ];
    }
    
    // Priorité aux statuts de validation
    if ($validation_status === 'validated') {
        // Normaliser les versions pour la comparaison (enlever les espaces, convertir en minuscules)
        $normalized_validation_version = trim(strtolower($validation_version));
        $normalized_current_version = trim(strtolower($current_version));
        
        if ($normalized_validation_version === $normalized_current_version) {
            return [
                'status' => 'validated',
                'message' => 'Lettre validée en ligne',
                'priority' => 'low',
                'icon' => 'dashicons-yes-alt',
                'color' => '#00a32a'
            ];
        } else {
            return [
                'status' => 'needs_revalidation',
                'message' => 'Revalidation requise - Version mise à jour',
                'priority' => 'medium',
                'icon' => 'dashicons-update-alt',
                'color' => '#ff9800'
            ];
        }
    }
    
    // Vérifier l'état des emails
    $last_email_sent = $formateur_data['last_email_sent'];
    $pdf_generated = $formateur_data['pdf_generated_date'];
    
    if (!$last_email_sent) {
        return [
            'status' => 'ready_to_send',
            'message' => 'Prêt à envoyer',
            'priority' => 'low',
            'icon' => 'dashicons-email-alt',
            'color' => '#0073aa'
        ];
    }
    
    if ($pdf_generated > $last_email_sent) {
        return [
            'status' => 'pdf_updated',
            'message' => 'PDF mis à jour - Email à renvoyer',
            'priority' => 'medium',
            'icon' => 'dashicons-update',
            'color' => '#ff9800'
        ];
    }
    
    // Si email envoyé mais pas encore validé
    if ($validation_status !== 'validated') {
        return [
            'status' => 'awaiting_validation',
            'message' => 'En attente de validation en ligne',
            'priority' => 'low',
            'icon' => 'dashicons-clock',
            'color' => '#646970'
        ];
    }
    
    return [
        'status' => 'sent',
        'message' => 'Email envoyé (' . date('d/m H:i', $last_email_sent) . ')',
        'priority' => 'low',
        'icon' => 'dashicons-email-alt',
        'color' => '#0073aa'
    ];
}

/**
 * Met à jour la date d'envoi d'email
 */
function fsbdd_update_email_sent_date($cpt_id, $formateur_id, $email_type = 'lettre_mission') {
    $versions = get_post_meta($cpt_id, 'fsbdd_pdf_versions', true) ?: [];
    
    if (!isset($versions[$formateur_id])) {
        // Initialiser si nécessaire
        fsbdd_update_pdf_version($cpt_id, $formateur_id);
        $versions = get_post_meta($cpt_id, 'fsbdd_pdf_versions', true) ?: [];
    }
    
    $versions[$formateur_id]['last_email_sent'] = current_time('timestamp');
    update_post_meta($cpt_id, 'fsbdd_pdf_versions', $versions);
    
    // Maintenir la compatibilité avec l'ancien système
    $meta_key = "fsbdd_email_sent_{$email_type}_{$formateur_id}";
    update_post_meta($cpt_id, $meta_key, current_time('mysql'));
}

/**
 * Synchronise les données avec l'ancien système
 */
function fsbdd_sync_with_legacy_system($cpt_id) {
    $versions = get_post_meta($cpt_id, 'fsbdd_pdf_versions', true) ?: [];
    $lettres_generees = get_post_meta($cpt_id, 'fsbdd_lettres_generees', true) ?: [];
    $formateurs = fsbdd_get_formateurs_from_planning($cpt_id);
    
    $updated = false;
    
    foreach ($formateurs as $formateur_id) {
        // Synchroniser avec fsbdd_lettres_generees
        if (isset($lettres_generees[$formateur_id])) {
            if (!isset($versions[$formateur_id]) || 
                $versions[$formateur_id]['pdf_generated_date'] < $lettres_generees[$formateur_id]) {
                
                fsbdd_update_pdf_version($cpt_id, $formateur_id);
                $updated = true;
            }
        }
        
        // Synchroniser les dates d'email
        $email_meta_key = "fsbdd_email_sent_lettre_mission_{$formateur_id}";
        $email_date = get_post_meta($cpt_id, $email_meta_key, true);
        
        if ($email_date && isset($versions[$formateur_id])) {
            $email_timestamp = strtotime($email_date);
            if (!$versions[$formateur_id]['last_email_sent'] || 
                $versions[$formateur_id]['last_email_sent'] < $email_timestamp) {
                
                $versions[$formateur_id]['last_email_sent'] = $email_timestamp;
                $updated = true;
            }
        }
    }
    
    if ($updated) {
        update_post_meta($cpt_id, 'fsbdd_pdf_versions', $versions);
    }
}

// =============================================================================
// == HOOKS ET INTÉGRATIONS ==
// =============================================================================

/**
 * Hook pour détecter les changements de planning
 */
add_action('updated_post_meta', 'fsbdd_detect_planning_changes', 10, 4);
function fsbdd_detect_planning_changes($meta_id, $post_id, $meta_key, $meta_value) {
    if ($meta_key === 'fsbdd_planning') {
        // Marquer que le planning a changé
        update_post_meta($post_id, 'fsbdd_planning_last_modified', current_time('timestamp'));
        
        // Optionnel : invalider le cache des versions
        delete_post_meta($post_id, 'fsbdd_pdf_versions_cache');
    }
}

/**
 * Fonction utilitaire pour obtenir un résumé des statuts
 */
function fsbdd_get_status_summary($cpt_id) {
    $formateurs = fsbdd_get_formateurs_from_planning($cpt_id);
    $summary = [
        'total' => count($formateurs),
        'error_email' => 0,
        'error_pdf' => 0,
        'planning_changed' => 0,
        'pdf_updated' => 0,
        'ready_to_send' => 0,
        'sent' => 0
    ];
    
    foreach ($formateurs as $formateur_id) {
        $status = fsbdd_get_formateur_smart_status($cpt_id, $formateur_id);
        $summary[$status['status']]++;
    }
    
    return $summary;
}

/**
 * Fonction pour nettoyer les anciennes données
 */
function fsbdd_cleanup_old_versions($cpt_id, $days_to_keep = 30) {
    $versions = get_post_meta($cpt_id, 'fsbdd_pdf_versions', true) ?: [];
    $cutoff_date = current_time('timestamp') - ($days_to_keep * DAY_IN_SECONDS);
    $updated = false;
    
    foreach ($versions as $formateur_id => &$formateur_data) {
        if (isset($formateur_data['versions'])) {
            foreach ($formateur_data['versions'] as $version => $version_data) {
                if ($version_data['date'] < $cutoff_date) {
                    unset($formateur_data['versions'][$version]);
                    $updated = true;
                }
            }
        }
    }
    
    if ($updated) {
        update_post_meta($cpt_id, 'fsbdd_pdf_versions', $versions);
    }
}

/**
 * Tâche cron pour nettoyer les anciennes versions
 */
add_action('wp', 'fsbdd_schedule_cleanup');
function fsbdd_schedule_cleanup() {
    if (!wp_next_scheduled('fsbdd_cleanup_versions')) {
        wp_schedule_event(time(), 'weekly', 'fsbdd_cleanup_versions');
    }
}

add_action('fsbdd_cleanup_versions', 'fsbdd_run_cleanup');
function fsbdd_run_cleanup() {
    $posts = get_posts([
        'post_type' => 'intervention',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'fsbdd_pdf_versions',
                'compare' => 'EXISTS'
            ]
        ]
    ]);
    
    foreach ($posts as $post) {
        fsbdd_cleanup_old_versions($post->ID);
    }
}

