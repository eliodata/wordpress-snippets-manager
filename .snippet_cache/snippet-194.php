<?php
/**
 * Snippet ID: 194
 * Name: PDF versioning doc formateurs lettres de mission
 * Description: 
 * @active false
 */

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
    
    // Vérifier si le planning a changé depuis la dernière génération
    $planning_changed = fsbdd_has_planning_changed($cpt_id, $formateur_id);
    
    if ($planning_changed) {
        return [
            'status' => 'planning_changed',
            'message' => 'Planning modifié - PDF à régénérer',
            'priority' => 'medium',
            'icon' => 'dashicons-update-alt',
            'color' => '#ff9800'
        ];
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
    
    return [
        'status' => 'sent',
        'message' => 'Email envoyé (' . date('d/m H:i', $last_email_sent) . ')',
        'priority' => 'low',
        'icon' => 'dashicons-yes-alt',
        'color' => '#00a32a'
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

