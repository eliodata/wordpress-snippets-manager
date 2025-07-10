<?php
/**
 * Snippet ID: 189
 * Name: Metabox side action de formation lettres de mission formateurs
 * Description: 
 * @active true
 */

/**
 * Plugin / Fonctionnalité : Gestion des Lettres de Mission des Formateurs
 * Version : Finale avec téléchargement via admin-ajax.php
 * Description : Utilise une action AJAX dédiée pour le téléchargement afin de garantir une compatibilité maximale.
 */

// =============================================================================
// == SYSTÈME DE TÉLÉCHARGEMENT FINAL VIA ADMIN-AJAX ==
// =============================================================================

// Création de deux actions AJAX : une pour les utilisateurs connectés, une pour les non-connectés (sécurité).
add_action('wp_ajax_fsbdd_download_lettre_mission', 'fsbdd_ajax_download_handler');
add_action('wp_ajax_nopriv_fsbdd_download_lettre_mission', 'fsbdd_ajax_download_handler');

function fsbdd_ajax_download_handler() {
    // Récupérer les variables depuis l'URL de la requête AJAX
    $cpt_id = isset($_REQUEST['cpt_id']) ? intval($_REQUEST['cpt_id']) : 0;
    $formateur_id = isset($_REQUEST['formateur_id']) ? intval($_REQUEST['formateur_id']) : 0;
    $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field($_REQUEST['nonce']) : '';

    if (!$cpt_id || !$formateur_id || !wp_verify_nonce($nonce, 'fsbdd_download_pdf_' . $cpt_id . '_' . $formateur_id)) {
        wp_die('Lien de téléchargement invalide ou expiré.', 'Erreur de sécurité', ['response' => 403]);
    }

    // IMPORTANT : Même si l'action est "nopriv", on vérifie que l'utilisateur EST connecté et a les droits.
    if (!is_user_logged_in()) {
        wp_die('Vous devez être connecté pour télécharger ce fichier.', 'Accès refusé', ['response' => 403]);
    }
    
    // Vérifier les permissions : admin, editeur ou formateur associé
    $has_permission = false;
    
    if (current_user_can('manage_options') || current_user_can('edit_posts')) {
        $has_permission = true;
    } else {
        // Vérifier si l'utilisateur est le formateur associé
        $current_user_id = get_current_user_id();
        global $wpdb;
        $linked_formateur_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT `from` FROM {$wpdb->prefix}mb_relationships WHERE `to` = %d AND `type` = 'formateur-bdd-formateur-wp'",
                $current_user_id
            )
        );
        
        if ($linked_formateur_id == $formateur_id) {
            $has_permission = true;
        }
    }
    
    if (!$has_permission) {
        wp_die('Vous n\'avez pas la permission de télécharger ce fichier.', 'Accès refusé', ['response' => 403]);
    }

    $numero_inter = get_post_meta($cpt_id, 'fsbdd_inter_numero', true);
    $upload_dir = wp_upload_dir();
    $pdf_filename = "lettre-mission-{$numero_inter}-{$formateur_id}.pdf";
    $pdf_path = $upload_dir['basedir'] . '/pdfformateurs/' . $cpt_id . '/' . $pdf_filename;

    if (file_exists($pdf_path)) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($pdf_filename) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($pdf_path));
        readfile($pdf_path);
        exit;
    } else {
        wp_die('Le fichier demandé n\'existe pas sur le serveur.', 'Fichier non trouvé', ['response' => 404]);
    }
}


// =============================================================================
// == METABOX, AUTRES AJAX, ETC. ==
// =============================================================================

add_action('add_meta_boxes', 'fsbdd_add_lettres_mission_metabox');
function fsbdd_add_lettres_mission_metabox() {
    add_meta_box(
        'fsbdd_lettres_mission',
        'Lettres de Mission',
        'fsbdd_lettres_mission_metabox_callback',
        'action-de-formation',
        'side',
        'high'
    );
}

function fsbdd_lettres_mission_metabox_callback($post) {
    $formateurs = fsbdd_get_formateurs_from_planning($post->ID);
    $e2pdf_template_id = 41;
    $lettres_generees = get_post_meta($post->ID, 'fsbdd_lettres_generees', true) ?: [];

    echo '<div class="fsbdd-lettres-mission">';

    if (empty($formateurs)) {
        echo '<p><em>Aucun formateur dans le planning.</em></p></div>';
        return;
    }

    echo '<div class="actions-group">';
    echo '<button type="button" class="button button-generer" onclick="genererToutesLettres(' . $post->ID . ', ' . $e2pdf_template_id . ')">Tout générer</button>';
    if (!empty($lettres_generees)) {
         echo '<button type="button" class="button button-supprimer-tout" onclick="supprimerToutesLettres(' . $post->ID . ')">Tout supprimer</button>';
    }
    echo '</div>';

    echo '<hr style="margin-top: 15px; margin-bottom: 10px;">';

    foreach ($formateurs as $index => $formateur_id) {
        $formateur_post = get_post($formateur_id);
        if (!$formateur_post) continue;

        $nom_complet = get_the_title($formateur_id);
        $formateur_index = $index + 1;

        echo '<div class="formateur-item">';
        echo '<div class="formateur-line-1">';
            echo '<strong class="formateur-name">' . esc_html(strtoupper($nom_complet)) . '</strong>';
            
            $numero_inter = get_post_meta($post->ID, 'fsbdd_inter_numero', true);
            $upload_dir = wp_upload_dir();
            $pdf_filename = "lettre-mission-{$numero_inter}-{$formateur_id}.pdf";
            $pdf_path = $upload_dir['basedir'] . '/pdfformateurs/' . $post->ID . '/' . $pdf_filename;
            $file_exists = file_exists($pdf_path);
            $meta_exists = isset($lettres_generees[$formateur_id]);

            echo '<span class="formateur-date">';
            if ($file_exists && $meta_exists) {
                $date_generation = date('d/m/Y H:i', $lettres_generees[$formateur_id]);
                echo $date_generation;
            }
            echo '</span>';
        echo '</div>';
        
        echo '<div class="formateur-line-2">';
            echo '<span class="formateur-status-icon">';
            if ($file_exists && $meta_exists) {
                $file_size = size_format(filesize($pdf_path), 0);
                echo '<span class="dashicons dashicons-yes-alt status-ok"></span> ' . $file_size;
            } elseif ($meta_exists && !$file_exists) {
                echo '<span class="dashicons dashicons-warning status-warning"></span>';
            }
            echo '</span>';
            
            echo '<div class="formateur-actions">';
        if ($file_exists && $meta_exists) {
            // **MODIFICATION CLÉ** : Construire l'URL vers admin-ajax.php
            $download_url = add_query_arg([
                'action'       => 'fsbdd_download_lettre_mission',
                'cpt_id'       => $post->ID,
                'formateur_id' => $formateur_id,
                'nonce'        => wp_create_nonce('fsbdd_download_pdf_' . $post->ID . '_' . $formateur_id)
            ], admin_url('admin-ajax.php'));
            
            // Ajout de target="_blank" pour ouvrir dans un nouvel onglet
            echo '<a href="' . esc_url($download_url) . '" class="button-icon" title="Télécharger" target="_blank"><span class="dashicons dashicons-download"></span></a>';
            echo '<button type="button" class="button-icon" onclick="genererLettreFormateur(' . $post->ID . ', ' . $formateur_index . ', ' . $formateur_id . ', ' . $e2pdf_template_id . ')" title="Remplacer"><span class="dashicons dashicons-update-alt"></span></button>';
            echo '<button type="button" class="button-icon icon-danger" onclick="supprimerLettre(' . $formateur_id . ', ' . $post->ID . ')" title="Supprimer"><span class="dashicons dashicons-trash"></span></button>';

        } else {
            echo '<button type="button" class="button button-primary" onclick="genererLettreFormateur(' . $post->ID . ', ' . $formateur_index . ', ' . $formateur_id . ', ' . $e2pdf_template_id . ')" title="Générer la lettre">Générer</button>';
        }
            echo '</div>'; // fermeture formateur-actions
        echo '</div>'; // fermeture formateur-line-2
        echo '</div>'; // fermeture formateur-item
    }
    echo '</div>';

    ?>
    <style>
        .fsbdd-lettres-mission .actions-group { display: flex; gap: 10px; }
        .fsbdd-lettres-mission .actions-group .button { flex-grow: 1; text-align: center; }
        .fsbdd-lettres-mission .button-generer { background-color: #8BC34A !important; border-color: #7CB342 !important; color: #fff !important; }
        .fsbdd-lettres-mission .button-generer:hover { background-color: #7CB342 !important; }
        .fsbdd-lettres-mission .button-supprimer-tout { background-color: #F44336 !important; border-color: #D32F2F !important; color: #fff !important; }
        .fsbdd-lettres-mission .button-supprimer-tout:hover { background-color: #D32F2F !important; }
        .fsbdd-lettres-mission .formateur-item { padding: 8px; margin-bottom: 6px; border-radius: 4px; border: 1px solid #ccd0d4; background: #fff; }
        .fsbdd-lettres-mission .formateur-line-1 { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
        .fsbdd-lettres-mission .formateur-line-2 { display: flex; justify-content: space-between; align-items: center; }
        .fsbdd-lettres-mission .formateur-name { font-weight: 600; color: #1d2327; }
        .fsbdd-lettres-mission .formateur-status-icon { font-size: 12px; color: #50575e; display: flex; align-items: center; gap: 4px; }
        .fsbdd-lettres-mission .formateur-status-icon .dashicons { font-size: 16px; }
        .fsbdd-lettres-mission .formateur-date { font-size: 12px; color: #50575e; }
        .fsbdd-lettres-mission .status-ok { color: #4CAF50; }
        .fsbdd-lettres-mission .status-warning { color: #ffb900; }
        .fsbdd-lettres-mission .status-loading { color: #2271b1; }
        .fsbdd-lettres-mission .formateur-actions { display: flex; gap: 4px; }
        .fsbdd-lettres-mission .button-icon { display: inline-flex; justify-content: center; align-items: center; width: 28px; height: 28px; padding: 0; background: #f0f0f1; border: 1px solid #ccd0d4; color: #50575e; box-shadow: none; }
        .fsbdd-lettres-mission .button-icon:hover { background: #e0e0e1; border-color: #a7aaad; color: #1d2327; }
        .fsbdd-lettres-mission .button-icon .dashicons { font-size: 16px; }
        .fsbdd-lettres-mission .button-icon.icon-danger { border-color: #F44336; color: #F44336; background: #fff; }
        .fsbdd-lettres-mission .button-icon.icon-danger:hover { background: #F44336; color: #fff; }
        
        /* Styles pour les indicateurs de chargement */
        .fsbdd-lettres-mission .loading-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.8); display: flex; align-items: center; justify-content: center; border-radius: 4px; z-index: 10; }
        .fsbdd-lettres-mission .formateur-item { position: relative; }
        .fsbdd-lettres-mission .loading-text { font-size: 12px; color: #2271b1; margin-left: 5px; }
        .fsbdd-lettres-mission .progress-bar { width: 100%; height: 4px; background: #e0e0e0; border-radius: 2px; margin-top: 5px; overflow: hidden; }
        .fsbdd-lettres-mission .progress-fill { height: 100%; background: #2271b1; border-radius: 2px; transition: width 0.3s ease; }
        .fsbdd-lettres-mission .global-loading { background: rgba(255,255,255,0.9); position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 9999; display: none; align-items: center; justify-content: center; flex-direction: column; }
        .fsbdd-lettres-mission .global-loading .spinner { font-size: 24px; margin-bottom: 10px; }
        
        /* Animation de rotation pour les spinners */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .fsbdd-lettres-mission .spin { animation: spin 1s linear infinite; }
    </style>
    <script>
    function genererLettreFormateur(cptId, formateurIndex, formateurId, templateId) {
        const button = event.currentTarget;
        const originalContent = button.innerHTML;
        const formateurItem = button.closest('.formateur-item');
        const statusElement = formateurItem.querySelector('.formateur-status');

        if (button.title.includes('Remplacer')) {
            if (!confirm('Une lettre existe déjà pour ce formateur. Voulez-vous la remplacer ?\n\nCette action est irréversible.')) {
                return;
            }
        }

        // Afficher l'indicateur de chargement
        button.innerHTML = '<span class="dashicons dashicons-update-alt spin"></span>';
        button.disabled = true;
        
        // Ajouter un overlay de chargement sur l'item formateur
        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'loading-overlay';
        loadingOverlay.innerHTML = '<span class="dashicons dashicons-update-alt spin"></span><span class="loading-text">Génération en cours...</span>';
        formateurItem.appendChild(loadingOverlay);
        
        // Mettre à jour le statut
        if (statusElement) {
            statusElement.innerHTML = '<span class="dashicons dashicons-update-alt spin status-loading"></span> Génération du PDF...';
        }

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                'action': 'fsbdd_sauvegarder_pdf_formateur',
                'cpt_id': cptId,
                'formateur_id': formateurId,
                'formateur_index': formateurIndex,
                'template_id': templateId,
                '_wpnonce': '<?php echo wp_create_nonce('fsbdd_lettres_mission'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            // Supprimer l'overlay de chargement
            if (loadingOverlay.parentNode) {
                loadingOverlay.parentNode.removeChild(loadingOverlay);
            }
            
            if (data.success) {
                // Afficher un message de succès temporaire
                if (statusElement) {
                    statusElement.innerHTML = '<span class="dashicons dashicons-yes-alt status-ok"></span> PDF généré avec succès !';
                }
                
                // Recharger après un court délai pour voir le message de succès
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                alert('Erreur lors de la génération : ' + (data.data || 'Erreur inconnue.'));
                button.innerHTML = originalContent;
                button.disabled = false;
                
                // Restaurer le statut original
                if (statusElement) {
                    statusElement.innerHTML = '';
                }
            }
        })
        .catch(error => {
            // Supprimer l'overlay de chargement
            if (loadingOverlay.parentNode) {
                loadingOverlay.parentNode.removeChild(loadingOverlay);
            }
            
            alert('Erreur de communication : ' + error.message);
            button.innerHTML = originalContent;
            button.disabled = false;
            
            // Restaurer le statut original
            if (statusElement) {
                statusElement.innerHTML = '';
            }
        });
    }

    function genererToutesLettres(cptId, templateId) {
        const button = event.target;
        const originalText = button.textContent;
        const existingCount = document.querySelectorAll('.status-ok').length;
        const totalFormateurs = document.querySelectorAll('.formateur-item').length;

        if (existingCount > 0) {
            if (!confirm(`Attention: ${existingCount} lettre(s) existe(nt) déjà et seront remplacées.\n\nVoulez-vous continuer ?`)) {
                return;
            }
        } else {
             if (!confirm('Êtes-vous sûr de vouloir générer les lettres pour tous les formateurs ?')) {
                return;
            }
        }

        // Créer un overlay global de chargement
        const globalLoading = document.createElement('div');
        globalLoading.className = 'global-loading';
        globalLoading.style.display = 'flex';
        globalLoading.innerHTML = `
            <div>
                <div class="spinner"><span class="dashicons dashicons-update-alt spin"></span></div>
                <div style="text-align: center; font-size: 16px; font-weight: 600;">Génération en cours...</div>
                <div style="text-align: center; font-size: 14px; margin-top: 5px;">Traitement de <span id="current-formateur">0</span> sur ${totalFormateurs} formateurs</div>
                <div class="progress-bar" style="width: 300px; margin-top: 15px;">
                    <div class="progress-fill" id="progress-fill" style="width: 0%;"></div>
                </div>
            </div>
        `;
        document.body.appendChild(globalLoading);

        button.innerHTML = '<span class="dashicons dashicons-update-alt spin"></span> Génération...';
        button.disabled = true;
        
        // Désactiver tous les autres boutons
        const allButtons = document.querySelectorAll('.fsbdd-lettres-mission button, .fsbdd-lettres-mission .button-icon');
        allButtons.forEach(btn => btn.disabled = true);

        // Simuler la progression (approximative)
        let currentProgress = 0;
        const progressInterval = setInterval(() => {
            if (currentProgress < 90) {
                currentProgress += Math.random() * 10;
                const progressFill = document.getElementById('progress-fill');
                const currentFormateurSpan = document.getElementById('current-formateur');
                if (progressFill) {
                    progressFill.style.width = Math.min(currentProgress, 90) + '%';
                }
                if (currentFormateurSpan) {
                    currentFormateurSpan.textContent = Math.min(Math.floor(currentProgress / 90 * totalFormateurs), totalFormateurs - 1);
                }
            }
        }, 500);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                'action': 'fsbdd_sauvegarder_toutes_lettres',
                'cpt_id': cptId,
                'template_id': templateId,
                '_wpnonce': '<?php echo wp_create_nonce('fsbdd_lettres_mission'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            clearInterval(progressInterval);
            
            // Compléter la barre de progression
            const progressFill = document.getElementById('progress-fill');
            const currentFormateurSpan = document.getElementById('current-formateur');
            if (progressFill) progressFill.style.width = '100%';
            if (currentFormateurSpan) currentFormateurSpan.textContent = totalFormateurs;
            
            setTimeout(() => {
                // Supprimer l'overlay
                if (globalLoading.parentNode) {
                    globalLoading.parentNode.removeChild(globalLoading);
                }
                
                if (data.success) {
                    let message = `${data.data.saved} lettre(s) générée(s) et sauvegardée(s) avec succès !`;
                    if (data.data.errors && data.data.errors.length > 0) {
                        message += `\n\n${data.data.errors.length} erreur(s) rencontrée(s):\n` + data.data.errors.join('\n');
                    }
                    alert(message);
                    location.reload();
                } else {
                    alert('Erreur : ' + (data.data || 'Une erreur est survenue.'));
                    button.innerHTML = originalText;
                    button.disabled = false;
                    // Réactiver tous les boutons
                    allButtons.forEach(btn => btn.disabled = false);
                }
            }, 1000);
        })
        .catch(error => {
            clearInterval(progressInterval);
            
            // Supprimer l'overlay
            if (globalLoading.parentNode) {
                globalLoading.parentNode.removeChild(globalLoading);
            }
            
            alert('Erreur de communication : ' + error.message);
            button.innerHTML = originalText;
            button.disabled = false;
            // Réactiver tous les boutons
            allButtons.forEach(btn => btn.disabled = false);
        });
    }

    function supprimerLettre(formateurId, cptId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer définitivement cette lettre ?')) {
            const button = event.currentTarget;
            const originalContent = button.innerHTML;
            const formateurItem = button.closest('.formateur-item');
            const statusElement = formateurItem.querySelector('.formateur-status');
            
            // Afficher l'indicateur de chargement
            button.innerHTML = '<span class="dashicons dashicons-update-alt spin"></span>';
            button.disabled = true;
            
            // Ajouter un overlay de chargement
            const loadingOverlay = document.createElement('div');
            loadingOverlay.className = 'loading-overlay';
            loadingOverlay.innerHTML = '<span class="dashicons dashicons-trash spin"></span><span class="loading-text">Suppression en cours...</span>';
            formateurItem.appendChild(loadingOverlay);
            
            // Mettre à jour le statut
            if (statusElement) {
                statusElement.innerHTML = '<span class="dashicons dashicons-trash spin status-loading"></span> Suppression...';
            }
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    'action': 'fsbdd_supprimer_lettre_complete',
                    'formateur_id': formateurId,
                    'cpt_id': cptId,
                    '_wpnonce': '<?php echo wp_create_nonce('fsbdd_lettres_mission'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                // Supprimer l'overlay de chargement
                if (loadingOverlay.parentNode) {
                    loadingOverlay.parentNode.removeChild(loadingOverlay);
                }
                
                if (data.success) {
                    // Afficher un message de succès temporaire
                    if (statusElement) {
                        statusElement.innerHTML = '<span class="dashicons dashicons-yes-alt status-ok"></span> Supprimé avec succès !';
                    }
                    
                    // Recharger après un court délai
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    alert('Erreur : ' + (data.data || 'Erreur inconnue.'));
                    button.innerHTML = originalContent;
                    button.disabled = false;
                    
                    // Restaurer le statut original
                    if (statusElement) {
                        statusElement.innerHTML = '';
                    }
                }
            })
            .catch(error => {
                // Supprimer l'overlay de chargement
                if (loadingOverlay.parentNode) {
                    loadingOverlay.parentNode.removeChild(loadingOverlay);
                }
                
                alert('Erreur de communication : ' + error.message);
                button.innerHTML = originalContent;
                button.disabled = false;
                
                // Restaurer le statut original
                if (statusElement) {
                    statusElement.innerHTML = '';
                }
            });
        }
    }

    function supprimerToutesLettres(cptId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer TOUTES les lettres de cette action de formation ?\n\nCette action est irréversible.')) {
            const button = event.target;
            const originalText = button.textContent;
            const totalLettres = document.querySelectorAll('.status-ok').length;
            
            // Créer un overlay global de chargement
            const globalLoading = document.createElement('div');
            globalLoading.className = 'global-loading';
            globalLoading.style.display = 'flex';
            globalLoading.innerHTML = `
                <div>
                    <div class="spinner"><span class="dashicons dashicons-trash spin"></span></div>
                    <div style="text-align: center; font-size: 16px; font-weight: 600; color: #d63638;">Suppression en cours...</div>
                    <div style="text-align: center; font-size: 14px; margin-top: 5px;">Suppression de ${totalLettres} lettre(s)</div>
                    <div class="progress-bar" style="width: 300px; margin-top: 15px;">
                        <div class="progress-fill" style="width: 0%; background: #d63638;"></div>
                    </div>
                </div>
            `;
            document.body.appendChild(globalLoading);
            
            button.innerHTML = '<span class="dashicons dashicons-trash spin"></span> Suppression...';
            button.disabled = true;
            
            // Désactiver tous les autres boutons
            const allButtons = document.querySelectorAll('.fsbdd-lettres-mission button, .fsbdd-lettres-mission .button-icon');
            allButtons.forEach(btn => btn.disabled = true);
            
            // Simuler la progression
            let currentProgress = 0;
            const progressInterval = setInterval(() => {
                if (currentProgress < 90) {
                    currentProgress += Math.random() * 15;
                    const progressFill = globalLoading.querySelector('.progress-fill');
                    if (progressFill) {
                        progressFill.style.width = Math.min(currentProgress, 90) + '%';
                    }
                }
            }, 300);
            
             fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    'action': 'fsbdd_supprimer_toutes_lettres',
                    'cpt_id': cptId,
                    '_wpnonce': '<?php echo wp_create_nonce('fsbdd_lettres_mission'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                clearInterval(progressInterval);
                
                // Compléter la barre de progression
                const progressFill = globalLoading.querySelector('.progress-fill');
                if (progressFill) progressFill.style.width = '100%';
                
                setTimeout(() => {
                    // Supprimer l'overlay
                    if (globalLoading.parentNode) {
                        globalLoading.parentNode.removeChild(globalLoading);
                    }
                    
                    if (data.success) {
                        alert(data.data.message);
                        location.reload();
                    } else {
                        alert('Erreur : ' + (data.data || 'Erreur inconnue.'));
                        button.innerHTML = originalText;
                        button.disabled = false;
                        // Réactiver tous les boutons
                        allButtons.forEach(btn => btn.disabled = false);
                    }
                }, 1000);
            })
            .catch(error => {
                clearInterval(progressInterval);
                
                // Supprimer l'overlay
                if (globalLoading.parentNode) {
                    globalLoading.parentNode.removeChild(globalLoading);
                }
                
                alert('Erreur de communication : ' + error.message);
                button.innerHTML = originalText;
                button.disabled = false;
                // Réactiver tous les boutons
                allButtons.forEach(btn => btn.disabled = false);
            });
        }
    }
    </script>

    <?php
}


/**
 * AJAX Handler pour sauvegarder le PDF d'un formateur sur le serveur - Robuste
 */
add_action('wp_ajax_fsbdd_sauvegarder_pdf_formateur', 'fsbdd_ajax_sauvegarder_pdf_formateur');

function fsbdd_ajax_sauvegarder_pdf_formateur() {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'fsbdd_lettres_mission')) {
        wp_send_json_error('Nonce invalide');
    }
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permissions insuffisantes');
    }
    
    $cpt_id = intval($_POST['cpt_id']);
    $formateur_id = intval($_POST['formateur_id']);
    $formateur_index = intval($_POST['formateur_index']);
    $template_id = intval($_POST['template_id']);
    
    try {
        $pdf_content = fsbdd_generer_pdf_e2pdf($template_id, $cpt_id, $formateur_index);

        if ($pdf_content === false) {
            throw new Exception('La génération du PDF via E2PDF a échoué.');
        }

        // Préparer le dossier et le nom de fichier
        $numero_inter = get_post_meta($cpt_id, 'fsbdd_inter_numero', true);
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/pdfformateurs/' . $cpt_id;
        
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }

        if (!is_writable($pdf_dir)) {
            throw new Exception('Le dossier de destination n\'est pas accessible en écriture : ' . $pdf_dir);
        }

        $pdf_filename = "lettre-mission-{$numero_inter}-{$formateur_id}.pdf";
        $pdf_path = $pdf_dir . '/' . $pdf_filename;
        
        // Sauvegarder le fichier
        if (file_put_contents($pdf_path, $pdf_content) === false) {
            throw new Exception('Échec de l\'écriture du fichier PDF sur le serveur.');
        }

        // Mettre à jour le système de versioning
        $new_version = fsbdd_update_pdf_version($cpt_id, $formateur_id);
        
        // Maintenir la compatibilité avec l'ancien système
        $lettres_generees = get_post_meta($cpt_id, 'fsbdd_lettres_generees', true);
        if (!is_array($lettres_generees)) {
            $lettres_generees = array();
        }
        $lettres_generees[$formateur_id] = current_time('timestamp');
        update_post_meta($cpt_id, 'fsbdd_lettres_generees', $lettres_generees);
        
        error_log('PDF généré - Version: ' . $new_version . ' pour formateur ' . $formateur_id);
        
        wp_send_json_success(array('message' => 'PDF sauvegardé avec succès'));
        
    } catch (Exception $e) {
        error_log('Erreur sauvegarde PDF formateur ' . $formateur_id . ' pour CPT ' . $cpt_id . ': ' . $e->getMessage());
        wp_send_json_error($e->getMessage());
    }
}

/**
 * AJAX Handler pour sauvegarder tous les PDFs
 */
add_action('wp_ajax_fsbdd_sauvegarder_toutes_lettres', 'fsbdd_ajax_sauvegarder_toutes_lettres');

function fsbdd_ajax_sauvegarder_toutes_lettres() {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'fsbdd_lettres_mission')) {
        wp_send_json_error('Nonce invalide');
    }
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permissions insuffisantes');
    }
    
    $cpt_id = intval($_POST['cpt_id']);
    $template_id = intval($_POST['template_id']);
    
    try {
        $formateurs = fsbdd_get_formateurs_from_planning($cpt_id);
        if(empty($formateurs)){
            throw new Exception("Aucun formateur trouvé pour cette action.");
        }
        
        $saved_count = 0;
        $errors = array();
        
        $numero_inter = get_post_meta($cpt_id, 'fsbdd_inter_numero', true);
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/pdfformateurs/' . $cpt_id;
        
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }
        
        $lettres_generees = get_post_meta($cpt_id, 'fsbdd_lettres_generees', true);
        if (!is_array($lettres_generees)) {
            $lettres_generees = array();
        }
        
        foreach ($formateurs as $index => $formateur_id) {
            $formateur_index = $index + 1;
            
            try {
                $pdf_content = fsbdd_generer_pdf_e2pdf($template_id, $cpt_id, $formateur_index);
                if ($pdf_content === false) {
                    throw new Exception("Échec de la génération E2PDF");
                }
                
                $pdf_filename = "lettre-mission-{$numero_inter}-{$formateur_id}.pdf";
                $pdf_path = $pdf_dir . '/' . $pdf_filename;
                        
                if (file_put_contents($pdf_path, $pdf_content) !== false) {
                    // Mettre à jour le système de versioning
                    $new_version = fsbdd_update_pdf_version($cpt_id, $formateur_id);
                    
                    // Maintenir la compatibilité
                    $lettres_generees[$formateur_id] = current_time('timestamp');
                    $saved_count++;
                    
                    error_log('PDF généré en lot - Version: ' . $new_version . ' pour formateur ' . $formateur_id);
                } else {
                    throw new Exception("Échec de l'écriture du fichier");
                }
                
                // Petite pause pour ne pas surcharger le serveur
                sleep(1); 
                
            } catch (Exception $e) {
                $nom_formateur = get_the_title($formateur_id);
                $errors[] = "Formateur '{$nom_formateur}' (ID: {$formateur_id}): " . $e->getMessage();
            }
        }
        
        // Sauvegarder toutes les meta en une fois
        update_post_meta($cpt_id, 'fsbdd_lettres_generees', $lettres_generees);
        
        wp_send_json_success(array('saved' => $saved_count, 'errors' => $errors));
        
    } catch (Exception $e) {
        error_log('Erreur sauvegarde toutes lettres pour CPT ' . $cpt_id . ': ' . $e->getMessage());
        wp_send_json_error($e->getMessage());
    }
}

/**
 * AJAX Handler pour supprimer complètement une lettre (fichier + meta)
 */
add_action('wp_ajax_fsbdd_supprimer_lettre_complete', 'fsbdd_ajax_supprimer_lettre_complete');

function fsbdd_ajax_supprimer_lettre_complete() {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'fsbdd_lettres_mission')) {
        wp_send_json_error('Nonce invalide');
    }
    
    if (!current_user_can('delete_posts')) {
        wp_send_json_error('Permissions insuffisantes');
    }
    
    $cpt_id = intval($_POST['cpt_id']);
    $formateur_id = intval($_POST['formateur_id']);
    
    try {
        // Supprimer le fichier
        $numero_inter = get_post_meta($cpt_id, 'fsbdd_inter_numero', true);
        $upload_dir = wp_upload_dir();
        $pdf_filename = "lettre-mission-{$numero_inter}-{$formateur_id}.pdf";
        $pdf_path = $upload_dir['basedir'] . '/pdfformateurs/' . $cpt_id . '/' . $pdf_filename;
        
        $file_deleted = true;
        if (file_exists($pdf_path)) {
            $file_deleted = unlink($pdf_path);
        }
        
        // Supprimer la meta
        $lettres_generees = get_post_meta($cpt_id, 'fsbdd_lettres_generees', true);
        if (is_array($lettres_generees) && isset($lettres_generees[$formateur_id])) {
            unset($lettres_generees[$formateur_id]);
            update_post_meta($cpt_id, 'fsbdd_lettres_generees', $lettres_generees);
        }
        
        // Mettre à jour le système de versioning pour marquer le PDF comme inexistant
        $versions = get_post_meta($cpt_id, 'fsbdd_pdf_versions', true) ?: [];
        if (isset($versions[$formateur_id])) {
            $versions[$formateur_id]['pdf_file_exists'] = false;
            update_post_meta($cpt_id, 'fsbdd_pdf_versions', $versions);
        }
        
        wp_send_json_success('Lettre supprimée avec succès.');
        
    } catch (Exception $e) {
        wp_send_json_error('Erreur : ' . $e->getMessage());
    }
}

// **NOUVEAU** : AJAX Handler pour supprimer toutes les lettres
add_action('wp_ajax_fsbdd_supprimer_toutes_lettres', 'fsbdd_ajax_supprimer_toutes_lettres');

function fsbdd_ajax_supprimer_toutes_lettres() {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'fsbdd_lettres_mission')) {
        wp_send_json_error('Nonce invalide');
    }
    
    if (!current_user_can('delete_posts')) {
        wp_send_json_error('Permissions insuffisantes');
    }

    $cpt_id = intval($_POST['cpt_id']);

    try {
        $upload_dir = wp_upload_dir();
        $pdf_dir_path = $upload_dir['basedir'] . '/pdfformateurs/' . $cpt_id;
        $files_deleted_count = 0;

        // Supprimer le dossier de PDF pour ce CPT
        if (is_dir($pdf_dir_path)) {
            $files = glob($pdf_dir_path . '/*');
            foreach($files as $file){ 
              if(is_file($file)) {
                unlink($file); 
                $files_deleted_count++;
              }
            }
            rmdir($pdf_dir_path);
        }

        // Supprimer la meta du post
        delete_post_meta($cpt_id, 'fsbdd_lettres_generees');
        
        // Mettre à jour le système de versioning pour marquer tous les PDFs comme inexistants
        $versions = get_post_meta($cpt_id, 'fsbdd_pdf_versions', true) ?: [];
        foreach ($versions as $formateur_id => $formateur_data) {
            $versions[$formateur_id]['pdf_file_exists'] = false;
        }
        if (!empty($versions)) {
            update_post_meta($cpt_id, 'fsbdd_pdf_versions', $versions);
        }

        wp_send_json_success(array('message' => 'Toutes les lettres ont été supprimées (' . $files_deleted_count . ' fichiers).'));

    } catch (Exception $e) {
        wp_send_json_error('Erreur lors de la suppression : ' . $e->getMessage());
    }
}

/**
 * Fonction pour générer un PDF avec E2PDF via un appel serveur-à-serveur
 */
function fsbdd_generer_pdf_e2pdf($template_id, $cpt_id, $formateur_index = null) {
    try {
        $url = admin_url('admin.php');
        $params = array(
            'page' => 'e2pdf',
            'action' => 'export',
            'id' => $template_id,
            'dataset' => $cpt_id
        );
        
        if ($formateur_index !== null) {
            $params['formateur_filter'] = $formateur_index;
        }
        
        $url = add_query_arg($params, $url);
        
        // Préparer les cookies pour l'authentification
        $cookies = array();
        foreach ($_COOKIE as $name => $value) {
            if (strpos($name, 'wordpress_') === 0 || strpos($name, 'wp-settings-') === 0) {
                 $cookies[] = new WP_Http_Cookie(array('name' => $name, 'value' => $value));
            }
        }

        $response = wp_remote_get($url, array(
            'timeout'   => 120, // Augmenter le timeout pour la génération de PDF
            'cookies'   => $cookies,
            'sslverify' => false // Peut être nécessaire en environnement de dev
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('Erreur WP_Http : ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 200 && strpos($body, '%PDF-') === 0) {
            return $body;
        } else {
            // Le corps peut contenir un message d'erreur HTML
            $error_preview = substr(strip_tags($body), 0, 300);
            throw new Exception("Réponse invalide du serveur (Code: {$response_code}). Prévisualisation : {$error_preview}...");
        }
        
    } catch (Exception $e) {
        error_log('Erreur fsbdd_generer_pdf_e2pdf: ' . $e->getMessage());
        return false;
    }
}





/**
 * Shortcode pour filtrer les pages par formateur dans E2PDF
 * [fsbdd_page_formateur_visible index="1"]
 */
function fsbdd_page_formateur_visible_shortcode($atts) {
    $atts = shortcode_atts(array(
        'index' => '1',
    ), $atts);
    
    $formateur_index = (int) $atts['index'];
    
    // Vérifier s'il y a un filtre formateur dans l'URL
    $formateur_filter = isset($_GET['formateur_filter']) ? (int) $_GET['formateur_filter'] : null;
    
    // Si pas de filtre, la page est visible
    if ($formateur_filter === null) {
        return '1'; // Page visible
    }
    
    // Si le filtre correspond à l'index de cette page, la page est visible
    if ($formateur_filter === $formateur_index) {
        return '1'; // Page visible
    }
    
    // Sinon, la page n'est pas visible
    return '0'; // Page masquée
}
add_shortcode('fsbdd_page_formateur_visible', 'fsbdd_page_formateur_visible_shortcode');

/**
 * Shortcode alternatif pour masquer les pages
 * [fsbdd_page_formateur_masquee index="1"]
 */
function fsbdd_page_formateur_masquee_shortcode($atts) {
    $atts = shortcode_atts(array(
        'index' => '1',
    ), $atts);
    
    $formateur_index = (int) $atts['index'];
    
    // Vérifier s'il y a un filtre formateur dans l'URL
    $formateur_filter = isset($_GET['formateur_filter']) ? (int) $_GET['formateur_filter'] : null;
    
    // Si pas de filtre, la page n'est pas masquée
    if ($formateur_filter === null) {
        return '0'; // Page non masquée
    }
    
    // Si le filtre correspond à l'index de cette page, la page n'est pas masquée
    if ($formateur_filter === $formateur_index) {
        return '0'; // Page non masquée
    }
    
    // Sinon, la page est masquée
    return '1'; // Page masquée
}
add_shortcode('fsbdd_page_formateur_masquee', 'fsbdd_page_formateur_masquee_shortcode');

// Ajouter ces shortcodes au filtre E2PDF
add_filter('e2pdf_extension_render_shortcodes_tags', function($shortcodes) {
    $shortcodes[] = 'fsbdd_page_formateur_visible';
    $shortcodes[] = 'fsbdd_page_formateur_masquee';
    return $shortcodes;
});
