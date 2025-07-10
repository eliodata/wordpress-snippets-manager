<?php
/**
 * Snippet ID: 190
 * Name: METABOX Template email pour l'envoi des lettres de mission ET doc formateurs
 * Description: 
 * @active true
 */


/**
 * Plugin / Fonctionnalité : Gestion des Emails aux Formateurs
 * Version : 1.0
 * Description : Metabox pour envoyer des emails avec pièces jointes aux formateurs
 */

// =============================================================================
// == FONCTION UTILITAIRE POUR RÉCUPÉRER LES FORMATEURS ==
// =============================================================================

/**
 * Récupère les formateurs associés à une action de formation depuis le planning
 * Cette fonction doit être adaptée selon votre structure de données
 */
if (!function_exists('fsbdd_get_formateurs_from_planning')) {
    function fsbdd_get_formateurs_from_planning($cpt_id) {
        // Méthode 1: Si les formateurs sont stockés dans un meta field
        $formateurs_meta = get_post_meta($cpt_id, 'fsbdd_formateurs_planning', true);
        if (!empty($formateurs_meta) && is_array($formateurs_meta)) {
            return $formateurs_meta;
        }
        
        // Méthode 2: Si les formateurs sont stockés comme une liste d'IDs séparés par des virgules
        $formateurs_string = get_post_meta($cpt_id, 'fsbdd_formateurs_ids', true);
        if (!empty($formateurs_string)) {
            $formateurs_array = explode(',', $formateurs_string);
            return array_map('intval', array_filter($formateurs_array));
        }
        
        // Méthode 3: Recherche via une relation post-to-post ou custom field
        $formateurs = array();
        
        // Exemple: récupérer tous les posts de type 'formateur' liés à cette action
        $args = array(
            'post_type' => 'formateur', // Ajustez selon votre CPT formateur
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'fsbdd_action_formation_id', // Ajustez selon votre structure
                    'value' => $cpt_id,
                    'compare' => '='
                )
            )
        );
        
        $formateurs_posts = get_posts($args);
        foreach ($formateurs_posts as $formateur_post) {
            $formateurs[] = $formateur_post->ID;
        }
        
        // Si aucune méthode ne fonctionne, retourner un tableau vide
        // Vous devrez adapter cette fonction selon votre structure de données
        return $formateurs;
    }
}

// =============================================================================
// == METABOX EMAILS FORMATEURS ==
// =============================================================================

add_action('add_meta_boxes', 'fsbdd_add_emails_formateurs_metabox');
function fsbdd_add_emails_formateurs_metabox() {
    add_meta_box(
        'fsbdd_emails_formateurs',
        'Emails Formateurs',
        'fsbdd_emails_formateurs_metabox_callback',
        'action-de-formation',
        'normal',
        'high'
    );
}

function fsbdd_emails_formateurs_metabox_callback($post) {
    wp_nonce_field('fsbdd_emails_formateurs', 'fsbdd_emails_formateurs_nonce');
    
    $numero_inter = get_post_meta($post->ID, 'fsbdd_inter_numero', true);
    $formateurs = fsbdd_get_formateurs_from_planning($post->ID);
    
    ?>
    <div id="fsbdd-emails-formateurs-container">
        <!-- En-tête compact -->
        <div class="fsbdd-header">
            <div class="fsbdd-header-info">
                <span class="fsbdd-intervention">N° <?php echo esc_html($numero_inter); ?></span>
                <span class="fsbdd-count"><?php echo count($formateurs); ?> formateur(s)</span>
            </div>
            <div class="fsbdd-header-actions">
                <label class="fsbdd-select-all">
                    <input type="checkbox" id="fsbdd-select-all-formateurs"> Tout sélectionner
                </label>
                <button type="button" id="fsbdd-send-emails-btn" class="button button-primary" disabled>
                    <span class="dashicons dashicons-email-alt"></span> Envoyer
                </button>
            </div>
        </div>
        
        <?php if (empty($formateurs)): ?>
            <div class="fsbdd-no-data">
                <span class="dashicons dashicons-info"></span>
                <p>Aucun formateur trouvé pour cette intervention.</p>
            </div>
        <?php else: ?>
            <!-- Table compacte des formateurs -->
            <div class="fsbdd-formateurs-table">
                <div class="fsbdd-table-header">
                    <div class="fsbdd-col-select">✓</div>
                    <div class="fsbdd-col-name">Formateur</div>
                    <div class="fsbdd-col-email">Email</div>
                    <div class="fsbdd-col-pdf">PDF</div>
                    <div class="fsbdd-col-last-sent">Dernier envoi</div>
                    <div class="fsbdd-col-validation">Validation</div>
                    <div class="fsbdd-col-status">Statut</div>
                </div>
                
                <?php foreach ($formateurs as $formateur_id): ?>
                    <?php
                    $formateur_id = intval($formateur_id);
                    $nom_complet = get_the_title($formateur_id);
                    $email_formateur = get_post_meta($formateur_id, 'fsbdd_email_mail1', true);
                    
                    // Synchroniser avec le nouveau système de versioning
                    fsbdd_sync_with_legacy_system($post->ID);
                    
                    // Obtenir le statut intelligent du formateur
                    $smart_status = fsbdd_get_formateur_smart_status($post->ID, $formateur_id);
                    $pdf_exists = $smart_status['status'] !== 'no_pdf' && $smart_status['status'] !== 'error_pdf';
                    
                    // Obtenir la date du PDF si il existe
                    $pdf_date = null;
                    if ($pdf_exists) {
                        $versions = get_post_meta($post->ID, 'fsbdd_pdf_versions', true) ?: [];
                        if (isset($versions[$formateur_id]['pdf_generated_at'])) {
                            $pdf_date = strtotime($versions[$formateur_id]['pdf_generated_at']);
                        }
                    }
                    
                    // Maintenir la compatibilité avec l'ancien système
                    $last_sent_key = "fsbdd_email_sent_lettre_mission_{$formateur_id}";
                    $last_sent = get_post_meta($post->ID, $last_sent_key, true);
                    $last_sent_timestamp = $last_sent ? strtotime($last_sent) : null;
                    
                    // Déterminer si le PDF nécessite une attention (changement planning ou mise à jour)
                    $pdf_changed = in_array($smart_status['status'], ['planning_changed', 'pdf_updated']);
                    
                    $has_email = !empty($email_formateur) && is_email($email_formateur);
                    $can_send = $has_email && $pdf_exists;
                    ?>
                    <div class="fsbdd-table-row <?php echo $pdf_changed ? 'fsbdd-pdf-changed' : ''; ?>">
                        <div class="fsbdd-col-select">
                            <input type="checkbox" 
                                   name="formateurs[]" 
                                   value="<?php echo $formateur_id; ?>"
                                   class="fsbdd-formateur-checkbox"
                                   <?php echo $can_send ? '' : 'disabled'; ?>
                                   data-formateur-name="<?php echo esc_attr($nom_complet); ?>">
                        </div>
                        
                        <div class="fsbdd-col-name">
                            <strong><?php echo esc_html($nom_complet); ?></strong>
                        </div>
                        
                        <div class="fsbdd-col-email">
                            <?php if ($has_email): ?>
                                <span class="fsbdd-email-ok" title="<?php echo esc_attr($email_formateur); ?>">
                                    <span class="dashicons dashicons-email-alt"></span>
                                    <?php echo esc_html(substr($email_formateur, 0, 20) . (strlen($email_formateur) > 20 ? '...' : '')); ?>
                                </span>
                            <?php else: ?>
                                <span class="fsbdd-email-missing">
                                    <span class="dashicons dashicons-warning"></span> Manquant
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="fsbdd-col-pdf">
                            <?php if ($pdf_exists && $pdf_date): ?>
                                <span class="fsbdd-pdf-ok" title="Modifié le <?php echo date('d/m/Y H:i', $pdf_date); ?>">
                                    <span class="dashicons dashicons-pdf"></span>
                                    <?php echo date('d/m H:i', $pdf_date); ?>
                                </span>
                            <?php elseif ($pdf_exists): ?>
                                <span class="fsbdd-pdf-ok">
                                    <span class="dashicons dashicons-pdf"></span>
                                    OK
                                </span>
                            <?php else: ?>
                                <span class="fsbdd-pdf-missing">
                                    <span class="dashicons dashicons-dismiss"></span> Absent
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="fsbdd-col-last-sent">
                            <?php if ($last_sent_timestamp): ?>
                                <span class="fsbdd-last-sent" title="<?php echo date('d/m/Y H:i:s', $last_sent_timestamp); ?>">
                                    <?php echo date('d/m H:i', $last_sent_timestamp); ?>
                                </span>
                            <?php else: ?>
                                <span class="fsbdd-never-sent">Jamais</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="fsbdd-col-validation">
                            <?php
                            // Récupérer les informations d'acceptation (système côté formateur)
                            $acceptance_data = get_post_meta($post->ID, 'fsbdd_lettre_acceptee_' . $formateur_id, true);
                            
                            // Récupérer la version actuelle du PDF pour ce formateur
                            $pdf_versions = get_post_meta($post->ID, 'fsbdd_pdf_versions', true);
                            $current_version = 'v1.0';
                            if ($pdf_versions && is_array($pdf_versions) && isset($pdf_versions[$formateur_id])) {
                                $current_version = $pdf_versions[$formateur_id]['current_version'] ?? 'v1.0';
                            }
                            
                            // Vérifier si le PDF existe
                            $numero_inter = get_post_meta($post->ID, 'fsbdd_inter_numero', true);
                            $upload_dir = wp_upload_dir();
                            $pdf_filename = "lettre-mission-{$numero_inter}-{$formateur_id}.pdf";
                            $pdf_path = $upload_dir['basedir'] . '/pdfformateurs/' . $post->ID . '/' . $pdf_filename;
                            $pdf_exists = file_exists($pdf_path);
                            
                            if (!$pdf_exists): ?>
                                <span class="fsbdd-validation-na" title="Aucun PDF disponible">
                                    <span class="dashicons dashicons-minus"></span>
                                    N/A
                                </span>
                            <?php elseif (!empty($acceptance_data)): ?>
                                <?php
                                $accepted_version = $acceptance_data['version'] ?? 'v1.0';
                                $acceptance_date = $acceptance_data['date'] ?? '';
                                
                                // Convertir la date du format français vers un format lisible par PHP
                                $formatted_date = '';
                                $short_date = '';
                                if ($acceptance_date) {
                                    // La date est au format d/m/Y H:i:s (ex: 25/06/2025 16:27:33)
                                    $date_parts = DateTime::createFromFormat('d/m/Y H:i:s', $acceptance_date);
                                    if ($date_parts) {
                                        $formatted_date = $date_parts->format('d/m/Y H:i');
                                        $short_date = $date_parts->format('d/m H:i');
                                    } else {
                                        $formatted_date = $acceptance_date;
                                        $short_date = $acceptance_date;
                                    }
                                }
                                
                                if ($accepted_version === $current_version): ?>
                                    <span class="fsbdd-validation-ok" 
                                          style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; border: 1px solid #c3e6cb; display: inline-block; font-size: 12px;"
                                          title="✅ Lettre de mission déjà validée\nDate de validation: <?php echo esc_html($formatted_date); ?>\nVersion validée: <?php echo esc_html($accepted_version); ?>\nAucune action supplémentaire n'est requise.">
                                        <span class="dashicons dashicons-yes-alt" style="font-size: 14px; vertical-align: middle;"></span>
                                        Validé <?php echo esc_html($short_date); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="fsbdd-validation-outdated" 
                                          style="background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; border: 1px solid #f5c6cb; display: inline-block; font-size: 12px;"
                                          title="🔄 Nouvelle validation requise\nDernière validation: <?php echo esc_html($formatted_date); ?>\nVersion précédente: <?php echo esc_html($accepted_version); ?>\nVersion actuelle: <?php echo esc_html($current_version); ?>\nLe formateur doit valider la nouvelle version.">
                                        <span class="dashicons dashicons-update-alt" style="font-size: 14px; vertical-align: middle;"></span>
                                        Revalider
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="fsbdd-validation-pending" 
                                      style="background: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; border: 1px solid #ffeeba; display: inline-block; font-size: 12px;"
                                      title="⚠️ Validation en attente\nLe formateur n'a pas encore validé cette lettre de mission.\nUn email avec le lien de validation a été envoyé.">
                                    <span class="dashicons dashicons-clock" style="font-size: 14px; vertical-align: middle;"></span>
                                    En attente
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="fsbdd-col-status">
                            <span class="fsbdd-status-smart" 
                                  style="color: <?php echo $smart_status['color']; ?>" 
                                  title="<?php echo esc_attr($smart_status['message']); ?>">
                                <span class="dashicons <?php echo $smart_status['icon']; ?>"></span>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Information sur la validation en ligne -->
            <div class="fsbdd-validation-info">
                <div class="fsbdd-info-box">
                    <span class="dashicons dashicons-info"></span>
                    <strong>Nouveau :</strong> Les formateurs peuvent maintenant valider leurs lettres de mission directement en ligne via leur espace formateur. 
                    Un lien de validation est automatiquement inclus dans chaque email envoyé.
                </div>
            </div>
            
            <!-- Types d'emails (compact) -->
            <div class="fsbdd-email-types">
                <label class="fsbdd-email-type">
                    <input type="checkbox" name="email_types[]" value="lettre_mission" class="fsbdd-email-type-checkbox" checked>
                    <span class="dashicons dashicons-media-document"></span>
                    Lettre de Mission
                </label>
            </div>
            
            <!-- Résumé de sélection -->
            <div class="fsbdd-selection-summary">
                <span id="fsbdd-selection-text">Aucune sélection</span>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Overlay de chargement -->
    <div id="fsbdd-loading-overlay" style="display: none;">
        <div class="fsbdd-loading-content">
            <div class="fsbdd-spinner"></div>
            <p>Envoi des emails en cours...</p>
            <div class="fsbdd-progress-bar">
                <div class="fsbdd-progress-fill"></div>
            </div>
            <div class="fsbdd-progress-text">0%</div>
        </div>
    </div>
    
    <?php

    ?>
    <style>
    #fsbdd-emails-formateurs-container {
        background: #fff;
        border: 1px solid #c3c4c7;
        border-radius: 4px;
    }
    
    .fsbdd-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        background: #f6f7f7;
        border-bottom: 1px solid #c3c4c7;
    }
    
    .fsbdd-header-info {
        display: flex;
        gap: 15px;
        align-items: center;
    }
    
    .fsbdd-intervention {
        font-weight: 600;
        color: #1d2327;
        font-size: 14px;
    }
    
    .fsbdd-count {
        color: #646970;
        font-size: 13px;
    }
    
    .fsbdd-header-actions {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    
    .fsbdd-select-all {
        font-size: 13px;
        margin: 0;
    }
    
    .fsbdd-formateurs-table {
        display: table;
        width: 100%;
        border-collapse: collapse;
    }
    
    .fsbdd-table-header {
        display: table-row;
        background: #f6f7f7;
        font-weight: 600;
        font-size: 12px;
        color: #50575e;
        text-transform: uppercase;
    }
    
    .fsbdd-table-header > div {
        display: table-cell;
        padding: 8px 12px;
        border-bottom: 1px solid #c3c4c7;
        vertical-align: middle;
    }
    
    .fsbdd-table-row {
        display: table-row;
        border-bottom: 1px solid #f0f0f1;
    }
    
    .fsbdd-table-row:hover {
        background: #f6f7f7;
    }
    
    .fsbdd-table-row.fsbdd-pdf-changed {
        background: #fff8e1;
        border-left: 3px solid #ff9800;
    }
    
    /* Styles pour les éléments de validation améliorés */
    .fsbdd-validation-ok,
    .fsbdd-validation-outdated,
    .fsbdd-validation-pending {
        white-space: nowrap;
        font-weight: 500;
        transition: all 0.2s ease;
        cursor: help;
    }
    
    .fsbdd-validation-ok:hover {
        background: #c3e6cb !important;
        transform: scale(1.02);
    }
    
    .fsbdd-validation-outdated:hover {
        background: #f5c6cb !important;
        transform: scale(1.02);
    }
    
    .fsbdd-validation-pending:hover {
        background: #ffeeba !important;
        transform: scale(1.02);
    }
    
    .fsbdd-validation-ok .dashicons,
    .fsbdd-validation-outdated .dashicons,
    .fsbdd-validation-pending .dashicons {
        margin-right: 4px;
    }
    
    .fsbdd-table-row > div {
        display: table-cell;
        padding: 10px 12px;
        vertical-align: middle;
        font-size: 13px;
    }
    
    .fsbdd-col-select {
        width: 40px;
        text-align: center;
    }
    
    .fsbdd-col-name {
        width: 25%;
        min-width: 150px;
    }
    
    .fsbdd-col-email {
        width: 30%;
        min-width: 180px;
    }
    
    .fsbdd-col-pdf {
        width: 15%;
        min-width: 100px;
    }
    
    .fsbdd-col-last-sent {
        width: 12%;
        min-width: 100px;
    }
    
    .fsbdd-col-validation {
        width: 15%;
        min-width: 120px;
    }
    
    .fsbdd-validation-ok {
        color: #00a32a;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .fsbdd-validation-outdated {
        color: #ff9800;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .fsbdd-validation-pending {
        color: #646970;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .fsbdd-validation-na {
        color: #d63638;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .fsbdd-col-status {
        width: 8%;
        min-width: 100px;
        text-align: center;
    }
    
    .fsbdd-validation-info {
        margin: 15px 0;
        padding: 0 16px;
    }
    
    .fsbdd-info-box {
        background: #e7f3ff;
        border-left: 4px solid #0073aa;
        padding: 12px 15px;
        border-radius: 4px;
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-size: 13px;
        line-height: 1.4;
    }
    
    .fsbdd-info-box .dashicons {
        color: #0073aa;
        margin-top: 1px;
        flex-shrink: 0;
    }
    
    .fsbdd-col-status {
        width: 50px;
        text-align: center;
    }
    
    .fsbdd-email-ok {
        color: #00a32a;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .fsbdd-email-missing {
        color: #d63638;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .fsbdd-pdf-ok {
        color: #00a32a;
        display: flex;
    }
    
    .fsbdd-validation-ok {
        color: #00a32a;
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
    }
    
    .fsbdd-validation-outdated {
        color: #dba617;
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
    }
    
    .fsbdd-validation-pending {
        color: #646970;
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
    }
    
    .fsbdd-validation-na {
        color: #8c8f94;
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
    }
    
    .fsbdd-pdf-ok {
        color: #00a32a;
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
    }
    
    .fsbdd-pdf-missing {
        color: #d63638;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .fsbdd-last-sent {
        color: #646970;
        font-size: 12px;
    }
    
    .fsbdd-never-sent {
        color: #d63638;
        font-style: italic;
        font-size: 12px;
    }
    
    .fsbdd-status-error {
        color: #d63638;
        font-size: 16px;
    }
    
    .fsbdd-status-warning {
        color: #ff9800;
        font-size: 16px;
    }
    
    .fsbdd-status-sent {
        color: #00a32a;
        font-size: 16px;
    }
    
    .fsbdd-status-ready {
        color: #0073aa;
        font-size: 16px;
    }
    
    .fsbdd-email-types {
        padding: 12px 16px;
        background: #f6f7f7;
        border-top: 1px solid #c3c4c7;
        border-bottom: 1px solid #c3c4c7;
    }
    
    .fsbdd-email-type {
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0;
        font-size: 13px;
        font-weight: 500;
    }
    
    .fsbdd-selection-summary {
        padding: 8px 16px;
        background: #f6f7f7;
        font-size: 13px;
        color: #646970;
        text-align: center;
    }
    
    .fsbdd-no-data {
        padding: 40px 20px;
        text-align: center;
        color: #646970;
    }
    
    .fsbdd-no-data .dashicons {
        font-size: 32px;
        margin-bottom: 10px;
        opacity: 0.5;
    }
    
    #fsbdd-loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .fsbdd-loading-content {
        background: white;
        padding: 30px;
        border-radius: 8px;
        text-align: center;
        min-width: 300px;
    }
    
    .fsbdd-spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #0073aa;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 0 auto 15px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .fsbdd-progress-bar {
        width: 100%;
        height: 20px;
        background: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
        margin: 15px 0;
    }
    
    .fsbdd-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #0073aa, #005a87);
        width: 0%;
        transition: width 0.3s ease;
    }
    
    .fsbdd-progress-text {
        font-weight: bold;
        color: #0073aa;
    }
    
    /* Responsive */
    @media (max-width: 1200px) {
        .fsbdd-col-email {
            width: 25%;
            min-width: 150px;
        }
        
        .fsbdd-col-pdf,
        .fsbdd-col-last-sent {
            width: 12%;
            min-width: 80px;
        }
    }
    
    @media (max-width: 900px) {
        .fsbdd-header {
            flex-direction: column;
            gap: 10px;
            align-items: stretch;
        }
        
        .fsbdd-header-actions {
            justify-content: space-between;
        }
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Gestion de la sélection globale
        $('#fsbdd-select-all-formateurs').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.fsbdd-formateur-checkbox:not(:disabled)').prop('checked', isChecked);
            updateSelectionSummary();
        });
        
        // Gestion de la sélection individuelle des formateurs
        $('.fsbdd-formateur-checkbox').on('change', function() {
            updateSelectAllState();
            updateSelectionSummary();
        });
        
        // Gestion de la sélection des types d'emails
        $('.fsbdd-email-type-checkbox').on('change', function() {
            updateSelectionSummary();
        });
        
        // Mettre à jour l'état de "Tout sélectionner"
        function updateSelectAllState() {
            const totalCheckboxes = $('.fsbdd-formateur-checkbox:not(:disabled)').length;
            const checkedCheckboxes = $('.fsbdd-formateur-checkbox:not(:disabled):checked').length;
            
            $('#fsbdd-select-all-formateurs').prop('checked', totalCheckboxes > 0 && checkedCheckboxes === totalCheckboxes);
        }
        
        // Fonction pour mettre à jour le résumé de sélection
        function updateSelectionSummary() {
            const selectedFormateurs = $('.fsbdd-formateur-checkbox:checked');
            const selectedEmailTypes = $('.fsbdd-email-type-checkbox:checked').length;
            
            let summaryText = '';
            
            if (selectedFormateurs.length === 0) {
                summaryText = 'Aucune sélection';
            } else {
                const formateurNames = [];
                selectedFormateurs.each(function() {
                    formateurNames.push($(this).data('formateur-name'));
                });
                
                if (selectedFormateurs.length === 1) {
                    summaryText = `1 formateur sélectionné : ${formateurNames[0]}`;
                } else if (selectedFormateurs.length <= 3) {
                    summaryText = `${selectedFormateurs.length} formateurs : ${formateurNames.join(', ')}`;
                } else {
                    summaryText = `${selectedFormateurs.length} formateurs sélectionnés`;
                }
                
                if (selectedEmailTypes > 0) {
                    summaryText += ` • ${selectedEmailTypes} type(s) d'email`;
                }
            }
            
            $('#fsbdd-selection-text').text(summaryText);
            
            // Activer/désactiver le bouton d'envoi
            const canSend = selectedFormateurs.length > 0 && selectedEmailTypes > 0;
            $('#fsbdd-send-emails-btn').prop('disabled', !canSend);
        }
        
        // Gestion du bouton d'envoi
        $('#fsbdd-send-emails-btn').on('click', function() {
            const selectedFormateurs = [];
            const selectedEmailTypes = [];
            
            $('.fsbdd-formateur-checkbox:checked').each(function() {
                selectedFormateurs.push($(this).val());
            });
            
            $('.fsbdd-email-type-checkbox:checked').each(function() {
                selectedEmailTypes.push($(this).val());
            });
            
            if (selectedFormateurs.length === 0 || selectedEmailTypes.length === 0) {
                alert('Veuillez sélectionner au moins un formateur et un type d\'email.');
                return;
            }
            
            // Vérifier s'il y a des PDFs modifiés
            const changedPdfs = $('.fsbdd-table-row.fsbdd-pdf-changed input:checked').length;
            let confirmMessage = `Confirmer l'envoi de ${selectedEmailTypes.length} type(s) d'email à ${selectedFormateurs.length} formateur(s) ?`;
            
            if (changedPdfs > 0) {
                confirmMessage += `\n\n⚠️ ${changedPdfs} PDF(s) ont été modifiés depuis le dernier envoi.`;
            }
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            sendEmails(selectedFormateurs, selectedEmailTypes);
        });
        
        // Fonction d'envoi des emails
        function sendEmails(formateurs, emailTypes) {
            // Afficher l'overlay de chargement
            $('#fsbdd-loading-overlay').show();
            
            // Désactiver le bouton
            $('#fsbdd-send-emails-btn').prop('disabled', true);
            
            // Simulation de progression
            let progress = 0;
            const progressInterval = setInterval(function() {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                $('.fsbdd-progress-fill').css('width', progress + '%');
                $('.fsbdd-progress-text').text(Math.round(progress) + '%');
            }, 200);
            
            // Préparer les données
            const data = {
                action: 'fsbdd_envoyer_emails_formateurs',
                cpt_id: <?php echo $post->ID; ?>,
                formateurs: JSON.stringify(formateurs),
                email_types: JSON.stringify(emailTypes),
                _wpnonce: $('#fsbdd_emails_formateurs_nonce').val()
            };
            
            // Envoi AJAX
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(result => {
                clearInterval(progressInterval);
                $('.fsbdd-progress-fill').css('width', '100%');
                $('.fsbdd-progress-text').text('100%');
                
                setTimeout(function() {
                    $('#fsbdd-loading-overlay').hide();
                    
                    if (result.success) {
                        let message = `✅ Envoi terminé !\n\n`;
                        message += `📧 ${result.data.sent} email(s) envoyé(s) avec succès.`;
                        
                        if (result.data.errors && result.data.errors.length > 0) {
                            message += `\n\n⚠️ Erreurs rencontrées :\n`;
                            result.data.errors.forEach(error => {
                                message += `• ${error}\n`;
                            });
                        }
                        
                        alert(message);
                        
                        // Décocher toutes les cases
                        $('.fsbdd-formateur-checkbox, .fsbdd-email-type-checkbox').prop('checked', false);
                        $('#fsbdd-select-all-formateurs').prop('checked', false);
                        updateSelectionSummary();
                        
                        // Recharger la page pour mettre à jour les statuts
                        location.reload();
                    } else {
                        alert('❌ Erreur lors de l\'envoi : ' + (result.data || 'Erreur inconnue'));
                    }
                    
                    // Réactiver le bouton
                    $('#fsbdd-send-emails-btn').prop('disabled', false);
                }, 1000);
            })
            .catch(error => {
                clearInterval(progressInterval);
                $('#fsbdd-loading-overlay').hide();
                alert('❌ Erreur de connexion : ' + error.message);
                $('#fsbdd-send-emails-btn').prop('disabled', false);
            });
        }
        
        // Initialiser l'état
        updateSelectAllState();
        updateSelectionSummary();
    });
    </script>
    <?php
}

// =============================================================================
// == AJAX HANDLER POUR L'ENVOI D'EMAILS ==
// =============================================================================

add_action('wp_ajax_fsbdd_envoyer_emails_formateurs', 'fsbdd_ajax_envoyer_emails_formateurs');

function fsbdd_ajax_envoyer_emails_formateurs() {
    // Log de débogage
    error_log('=== DÉBUT ENVOI EMAILS FORMATEURS ===');
    error_log('POST data: ' . print_r($_POST, true));
    
    if (!wp_verify_nonce($_POST['_wpnonce'], 'fsbdd_emails_formateurs')) {
        error_log('Erreur: Nonce invalide');
        wp_send_json_error('Nonce invalide');
    }
    
    if (!current_user_can('edit_posts')) {
        error_log('Erreur: Permissions insuffisantes');
        wp_send_json_error('Permissions insuffisantes');
    }
    
    $cpt_id = intval($_POST['cpt_id']);
    $formateurs = json_decode(stripslashes($_POST['formateurs']), true);
    $email_types = json_decode(stripslashes($_POST['email_types']), true);
    
    error_log('CPT ID: ' . $cpt_id);
    error_log('Formateurs: ' . print_r($formateurs, true));
    error_log('Types emails: ' . print_r($email_types, true));
    
    if (empty($formateurs) || empty($email_types)) {
        error_log('Erreur: Paramètres manquants');
        wp_send_json_error('Paramètres manquants');
    }
    
    try {
        $sent_count = 0;
        $errors = array();
        $debug_info = array();
        
        $numero_inter = get_post_meta($cpt_id, 'fsbdd_inter_numero', true);
        $titre_formation = get_the_title($cpt_id);
        
        error_log('Numéro intervention: ' . $numero_inter);
        error_log('Titre formation: ' . $titre_formation);
        
        foreach ($formateurs as $formateur_id) {
            $formateur_id = intval($formateur_id);
            $formateur_post = get_post($formateur_id);
            
            error_log('--- Traitement formateur ID: ' . $formateur_id . ' ---');
            
            if (!$formateur_post) {
                $error_msg = "Formateur ID {$formateur_id} introuvable";
                $errors[] = $error_msg;
                error_log('Erreur: ' . $error_msg);
                continue;
            }
            
            $nom_formateur = get_the_title($formateur_id);
            $email_formateur = get_post_meta($formateur_id, 'fsbdd_email_mail1', true);
            
            error_log('Nom formateur: ' . $nom_formateur);
            error_log('Email formateur brut: "' . $email_formateur . '"');
            error_log('Email vide? ' . (empty($email_formateur) ? 'OUI' : 'NON'));
            error_log('Email valide? ' . (is_email($email_formateur) ? 'OUI' : 'NON'));
            
            // Vérifications détaillées de l'email
            if (empty($email_formateur)) {
                $error_msg = "Email manquant pour {$nom_formateur} (champ vide)";
                $errors[] = $error_msg;
                error_log('Erreur: ' . $error_msg);
                continue;
            }
            
            if (!is_email($email_formateur)) {
                $error_msg = "Email invalide pour {$nom_formateur}: '{$email_formateur}'";
                $errors[] = $error_msg;
                error_log('Erreur: ' . $error_msg);
                continue;
            }
            
            // Traiter chaque type d'email
            foreach ($email_types as $email_type) {
                error_log('Envoi email type: ' . $email_type . ' à ' . $email_formateur);
                
                try {
                    $result = fsbdd_envoyer_email_formateur($cpt_id, $formateur_id, $email_type);
                    if ($result) {
                        $sent_count++;
                        
                        // Enregistrer la date d'envoi avec le nouveau système
                        fsbdd_update_email_sent_date($cpt_id, $formateur_id, $email_type);
                        
                        // Maintenir la compatibilité avec l'ancien système
                        $meta_key = "fsbdd_email_sent_{$email_type}_{$formateur_id}";
                        update_post_meta($cpt_id, $meta_key, current_time('mysql'));
                        
                        error_log('✅ Email envoyé avec succès à ' . $nom_formateur);
                    } else {
                        $error_msg = "Échec envoi {$email_type} à {$nom_formateur}";
                        $errors[] = $error_msg;
                        error_log('❌ ' . $error_msg);
                    }
                } catch (Exception $e) {
                    $error_msg = "Erreur {$email_type} pour {$nom_formateur}: " . $e->getMessage();
                    $errors[] = $error_msg;
                    error_log('❌ Exception: ' . $error_msg);
                }
            }
            
            // Petite pause pour ne pas surcharger le serveur
            usleep(500000); // 0.5 seconde
        }
        
        error_log('=== RÉSULTAT FINAL ===');
        error_log('Emails envoyés: ' . $sent_count);
        error_log('Erreurs: ' . count($errors));
        if (!empty($errors)) {
            error_log('Liste des erreurs: ' . print_r($errors, true));
        }
        
        wp_send_json_success(array(
            'sent' => $sent_count,
            'errors' => $errors
        ));
        
    } catch (Exception $e) {
        $error_msg = 'Erreur envoi emails formateurs pour CPT ' . $cpt_id . ': ' . $e->getMessage();
        error_log('❌ EXCEPTION GLOBALE: ' . $error_msg);
        wp_send_json_error($e->getMessage());
    }
}

/**
 * Fonction pour envoyer un email à un formateur selon le type
 */
function fsbdd_envoyer_email_formateur($cpt_id, $formateur_id, $email_type) {
    error_log('--- DÉBUT fsbdd_envoyer_email_formateur ---');
    error_log('CPT ID: ' . $cpt_id . ', Formateur ID: ' . $formateur_id . ', Type: ' . $email_type);
    
    $formateur_post = get_post($formateur_id);
    if (!$formateur_post) {
        error_log('❌ Formateur post introuvable pour ID: ' . $formateur_id);
        throw new Exception('Formateur introuvable');
    }
    
    $nom_formateur = get_the_title($formateur_id);
    $email_formateur = get_post_meta($formateur_id, 'fsbdd_email_mail1', true);
    $titre_formation = get_the_title($cpt_id);
    $numero_inter = get_post_meta($cpt_id, 'fsbdd_inter_numero', true);
    
    error_log('Nom formateur: ' . $nom_formateur);
    error_log('Email formateur: "' . $email_formateur . '"');
    error_log('Numéro intervention: ' . $numero_inter);
    error_log('Titre formation: ' . $titre_formation);
    
    if (!is_email($email_formateur)) {
        error_log('❌ Email formateur invalide: ' . $email_formateur);
        throw new Exception('Email formateur invalide');
    }
    
    $attachments = array();
    $subject = '';
    $message = '';
    
    switch ($email_type) {
        case 'lettre_mission':
            // Vérifier que la lettre de mission existe
            $upload_dir = wp_upload_dir();
            $pdf_filename = "lettre-mission-{$numero_inter}-{$formateur_id}.pdf";
            $pdf_path = $upload_dir['basedir'] . '/pdfformateurs/' . $cpt_id . '/' . $pdf_filename;
            
            error_log('Chemin PDF: ' . $pdf_path);
            error_log('PDF existe? ' . (file_exists($pdf_path) ? 'OUI' : 'NON'));
            
            if (!file_exists($pdf_path)) {
                error_log('❌ Lettre de mission non trouvée: ' . $pdf_path);
                throw new Exception('Lettre de mission non trouvée');
            }
            
            $attachments[] = $pdf_path;
            // Récupérer les informations de versioning
            $version_info = fsbdd_get_version_info_for_email($cpt_id, $formateur_id);
            
            // Récupérer les informations de validation
            $acceptance_data = get_post_meta($cpt_id, 'fsbdd_lettre_acceptee_' . $formateur_id, true);
            $current_version = $version_info['current_version'];
            
            $validation_info = [];
            if (!empty($acceptance_data)) {
                $accepted_version = $acceptance_data['version'] ?? 'v1.0';
                $acceptance_date = $acceptance_data['date'] ?? '';
                
                if ($accepted_version === $current_version) {
                    $validation_info = [
                        'is_validated' => true,
                        'needs_revalidation' => false,
                        'validation_date' => $acceptance_date,
                        'validated_version' => $accepted_version
                    ];
                } else {
                    $validation_info = [
                        'is_validated' => false,
                        'needs_revalidation' => true,
                        'validation_date' => $acceptance_date,
                        'validated_version' => $accepted_version
                    ];
                }
            } else {
                $validation_info = [
                    'is_validated' => false,
                    'needs_revalidation' => false,
                    'validation_date' => '',
                    'validated_version' => ''
                ];
            }
            
            $subject = "Lettre de mission - {$titre_formation} (N° {$numero_inter}) - {$version_info['current_version']}";
            $message = fsbdd_get_email_template_lettre_mission($nom_formateur, $titre_formation, $numero_inter, $version_info, $validation_info);
            
            error_log('PDF ajouté en pièce jointe');
            break;
            
        default:
            error_log('❌ Type d\'email non reconnu: ' . $email_type);
            throw new Exception('Type d\'email non reconnu: ' . $email_type);
    }
    
    // Configuration des headers
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_option('admin_email')
    );
    
    error_log('Subject: ' . $subject);
    error_log('Headers: ' . print_r($headers, true));
    error_log('Attachments: ' . print_r($attachments, true));
    error_log('Tentative d\'envoi email à: ' . $email_formateur);
    
    // Test de la fonction wp_mail
    if (!function_exists('wp_mail')) {
        error_log('❌ Fonction wp_mail non disponible');
        throw new Exception('Fonction wp_mail non disponible');
    }
    
    // Envoi de l'email
    $sent = wp_mail($email_formateur, $subject, $message, $headers, $attachments);
    
    if ($sent) {
        // Enregistrer la date d'envoi avec le nouveau système
        fsbdd_update_email_sent_date($cpt_id, $formateur_id, $email_type);
        
        // Maintenir la compatibilité avec l'ancien système
        $meta_key = "fsbdd_email_sent_{$email_type}_{$formateur_id}";
        update_post_meta($cpt_id, $meta_key, current_time('mysql'));
        
        error_log('✅ Email envoyé avec succès à ' . $email_formateur);
    } else {
        error_log('❌ Échec envoi email à ' . $email_formateur . ' pour ' . $nom_formateur);
        
        // Informations supplémentaires sur l'erreur
        global $phpmailer;
        if (isset($phpmailer) && is_object($phpmailer)) {
            error_log('Erreur PHPMailer: ' . $phpmailer->ErrorInfo);
        }
        
        throw new Exception('Échec de l\'envoi de l\'email');
    }
    
    error_log('--- FIN fsbdd_envoyer_email_formateur ---');
    return true;
}

/**
 * Récupère les informations de versioning pour l'email
 */
function fsbdd_get_version_info_for_email($cpt_id, $formateur_id) {
    $versions = get_post_meta($cpt_id, 'fsbdd_pdf_versions', true) ?: [];
    
    if (!isset($versions[$formateur_id])) {
        return null;
    }
    
    $formateur_data = $versions[$formateur_id];
    $current_version = $formateur_data['current_version'];
    $generation_date = date('d/m/Y à H:i', $formateur_data['pdf_generated_date']);
    
    // Récupérer la version précédente
    $previous_version = '';
    $previous_date = '';
    $versions_history = $formateur_data['versions'] ?? [];
    
    if (!empty($versions_history)) {
        // Trier par date pour récupérer la plus récente
        uasort($versions_history, function($a, $b) {
            return $b['date'] - $a['date'];
        });
        
        $last_version = array_key_first($versions_history);
        if ($last_version && $last_version !== $current_version) {
            $previous_version = $last_version;
            $previous_date = date('d/m/Y à H:i', $versions_history[$last_version]['date']);
        }
    }
    
    // Vérifier si le planning a changé
    $planning_changed = fsbdd_has_planning_changed($cpt_id, $formateur_id);
    
    return [
        'current_version' => $current_version,
        'generation_date' => $generation_date,
        'previous_version' => $previous_version,
        'previous_date' => $previous_date,
        'planning_changed' => $planning_changed
    ];
}

/**
 * Template d'email pour la lettre de mission
 */
function fsbdd_get_email_template_lettre_mission($nom_formateur, $titre_formation, $numero_inter, $version_info = null, $validation_info = null) {
    $site_name = get_bloginfo('name');
    
    // Construire les informations de version
    $version_section = '';
    if ($version_info && !empty($version_info)) {
        $version_section = "
            <div style='background: #e7f3ff; border-left: 4px solid #2271b1; padding: 15px; margin: 15px 0;'>
                <h4 style='margin: 0 0 10px 0; color: #2271b1;'>📋 Informations du document</h4>
                <p style='margin: 5px 0;'><strong>Version actuelle :</strong> " . esc_html($version_info['current_version']) . "</p>
                <p style='margin: 5px 0;'><strong>Date de génération :</strong> " . esc_html($version_info['generation_date']) . "</p>";
        
        if (!empty($version_info['previous_version'])) {
            $version_section .= "<p style='margin: 5px 0;'><strong>Version précédente :</strong> " . esc_html($version_info['previous_version']) . " (" . esc_html($version_info['previous_date']) . ")</p>";
        }
        
        if ($version_info['planning_changed']) {
            $version_section .= "<p style='margin: 5px 0; color: #d63638;'><strong>⚠️ Planning modifié</strong> - Nouvelle version générée</p>";
        }
        
        $version_section .= "</div>";
    }
    
    // Section de validation en ligne - adaptée selon le statut
    $validation_section = '';
    
    if ($validation_info && !empty($validation_info)) {
        $is_validated = $validation_info['is_validated'] ?? false;
        $needs_revalidation = $validation_info['needs_revalidation'] ?? false;
        $validation_date = $validation_info['validation_date'] ?? '';
        $validated_version = $validation_info['validated_version'] ?? '';
        
        if ($is_validated) {
            // Déjà validé pour la version actuelle
            $validation_section = "
                <div style='background: #e8f5e8; border: 2px solid #46b450; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: center;'>
                    <h3 style='margin: 0 0 15px 0; color: #46b450;'>✅ Lettre de mission déjà validée</h3>
                    <p style='margin: 10px 0; font-size: 16px;'>Vous avez déjà validé cette lettre de mission.</p>
                    <p style='margin: 5px 0; font-size: 14px; color: #666;'>
                        <strong>Date de validation :</strong> " . esc_html($validation_date) . "<br>
                        <strong>Version validée :</strong> " . esc_html($validated_version) . "
                    </p>
                    <p style='margin: 10px 0; font-size: 14px; color: #666;'>
                        Aucune action supplémentaire n'est requise de votre part.
                    </p>
                </div>";
        } elseif ($needs_revalidation) {
            // Besoin de revalidation
            $validation_section = "
                <div style='background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: center;'>
                    <h3 style='margin: 0 0 15px 0; color: #856404;'>🔄 Nouvelle validation requise</h3>
                    <p style='margin: 10px 0; font-size: 16px;'>Une nouvelle version de votre lettre de mission a été générée.</p>
                    <p style='margin: 5px 0; font-size: 14px; color: #666;'>
                        <strong>Dernière validation :</strong> " . esc_html($validation_date) . " (" . esc_html($validated_version) . ")
                    </p>
                    <a href='https://formationstrategique.fr/mon-compte/' 
                       style='display: inline-block; background: #ffc107; color: #212529; padding: 12px 25px; 
                              text-decoration: none; border-radius: 5px; font-weight: bold; margin: 10px 0;'>
                        🔗 Valider la nouvelle version
                    </a>
                    <p style='margin: 10px 0; font-size: 14px; color: #666;'>
                        Veuillez vous connecter à votre espace formateur pour valider cette nouvelle version.
                    </p>
                </div>";
        } else {
            // Première validation
            $validation_section = "
                <div style='background: #f0f8ff; border: 2px solid #0073aa; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: center;'>
                    <h3 style='margin: 0 0 15px 0; color: #0073aa;'>✅ Validation en ligne</h3>
                    <p style='margin: 10px 0; font-size: 16px;'>Pour valider votre lettre de mission directement en ligne :</p>
                    <a href='https://formationstrategique.fr/mon-compte/' 
                       style='display: inline-block; background: #0073aa; color: white; padding: 12px 25px; 
                              text-decoration: none; border-radius: 5px; font-weight: bold; margin: 10px 0;'>
                        🔗 Accéder à mon espace formateur
                    </a>
                    <p style='margin: 10px 0; font-size: 14px; color: #666;'>
                        Une fois connecté, sélectionnez votre formation et cliquez sur \"Accepter la lettre de mission\".
                    </p>
                </div>";
        }
    } else {
        // Pas d'informations de validation - affichage par défaut
        $validation_section = "
            <div style='background: #f0f8ff; border: 2px solid #0073aa; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: center;'>
                <h3 style='margin: 0 0 15px 0; color: #0073aa;'>✅ Validation en ligne</h3>
                <p style='margin: 10px 0; font-size: 16px;'>Pour valider votre lettre de mission directement en ligne :</p>
                <a href='https://formationstrategique.fr/mon-compte/' 
                   style='display: inline-block; background: #0073aa; color: white; padding: 12px 25px; 
                          text-decoration: none; border-radius: 5px; font-weight: bold; margin: 10px 0;'>
                    🔗 Accéder à mon espace formateur
                </a>
                <p style='margin: 10px 0; font-size: 14px; color: #666;'>
                    Une fois connecté, sélectionnez votre formation et cliquez sur \"Accepter la lettre de mission\".
                </p>
            </div>";
    }
    
    return "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2271b1; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .footer { padding: 15px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Lettre de Mission</h2>
            </div>
            <div class='content'>
                <p>Bonjour <strong>" . esc_html($nom_formateur) . "</strong>,</p>
                
                <p>Veuillez trouver en pièce jointe votre lettre de mission pour la formation :</p>
                
                <p><strong>" . esc_html($titre_formation) . "</strong><br>
                <em>Numéro d'intervention : " . esc_html($numero_inter) . "</em></p>
                
                " . $version_section . "
                
                " . $validation_section . "
                
                <p><strong>Alternative :</strong> Vous pouvez également signer la lettre de mission en pièce jointe et nous la retourner par email.</p>
                
                <p>Cordialement,<br>
                L'équipe " . esc_html($site_name) . "</p>
            </div>
            <div class='footer'>
                <p>Cet email a été envoyé automatiquement depuis " . esc_html($site_name) . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

