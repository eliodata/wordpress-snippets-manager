<?php
/**
 * Snippet ID: 61
 * Name: PHP Callback pour Metabox.io formulaire envoi documents formateurs
 * Description: 
 * @active true
 */

function render_upload_documents_html_field($meta, $field) {
    global $post;

    if (!$post || $post->post_type !== 'action-de-formation') {
        return '<p>Ce contenu n\'est disponible que pour les actions de formation.</p>';
    }
	
	if ($post->ID === 268081) {
		return '<p>Ce contenu n\'est pas disponible pour cette action de formation.</p>';
	}

    $action_id = $post->ID;
    $action_title_slug = sanitize_title(get_the_title($action_id));
    $planning = get_post_meta($action_id, 'fsbdd_planning', true);

    // Récupérer les formateurs liés au planning
    $formateurs = [];
    if (!empty($planning) && is_array($planning)) {
        foreach ($planning as $day) {
            if (!empty($day['fsbdd_gpformatr']) && is_array($day['fsbdd_gpformatr'])) {
                foreach ($day['fsbdd_gpformatr'] as $formateur) {
                    if (!empty($formateur['fsbdd_user_formateurrel'])) {
                        $formateur_id = $formateur['fsbdd_user_formateurrel'];
                        $formateurs[$formateur_id] = get_the_title($formateur_id);
                    }
                }
            }
        }
    }

    ob_start();

    // Afficher le formulaire si des formateurs sont disponibles
    if (!empty($formateurs)) {
        echo '<form id="upload_document_form" method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('upload_document_action', 'upload_document_nonce');

        echo '<input type="hidden" name="action" value="fsbdd_upload_document">';
        echo '<input type="hidden" name="action_title_slug" value="' . esc_attr($action_title_slug) . '">';
        echo '<input type="hidden" name="action_id" value="' . esc_attr($action_id) . '">';

        // Sélecteur de formateur
        echo '<label for="formateur_id">Formateur :</label>';
        echo '<select id="formateur_id" name="formateur_id" required>';
        echo '<option value="">-- Sélectionnez un formateur --</option>';
        foreach ($formateurs as $formateur_id => $formateur_name) {
            echo "<option value='" . esc_attr($formateur_id) . "'>" . esc_html($formateur_name) . "</option>";
        }
        echo '</select><br><br>';

        // Sélecteur de type de document
        echo '<label for="document_type">Type de document :</label>';
        echo '<select id="document_type" name="document_type" required>';
        echo '<option value="emargements">Émargements</option>';
        echo '<option value="compterenduf">Compte rendu formateur</option>';
        echo '<option value="evaluations">Évaluations</option>';
        echo '<option value="autre">Autre document</option>';
        echo '</select><br><br>';

        // Champ personnalisé pour "autre document"
        echo '<div id="custom_doc_name_wrapper" style="display:none;">';
        echo '<label for="custom_doc_name">Nom du document :</label>';
        echo '<input type="text" id="custom_doc_name" name="custom_doc_name" placeholder="Nom personnalisé">';
        echo '</div><br>';

        // Champ pour téléverser le fichier
        echo '<label for="fsbdd_file_upload">Fichier :</label>';
        echo '<input type="file" id="fsbdd_file_upload" name="fsbdd_file_upload" accept=".pdf, .png, .jpg, .jpeg, .doc, .xls" required><br><br>';

        // Bouton d'envoi
        echo '<button type="submit" class="button-primary">Téléverser</button>';
        echo '</form>';
    } else {
        echo '<p>Aucun formateur trouvé pour cette action de formation.</p>';
    }

    // Script pour gérer les champs dynamiques
    echo "
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const docTypeSelect = document.getElementById('document_type');
                const customDocNameWrapper = document.getElementById('custom_doc_name_wrapper');
                const customDocNameInput = document.getElementById('custom_doc_name');

                docTypeSelect.addEventListener('change', function() {
                    if (this.value === 'autre') {
                        customDocNameWrapper.style.display = 'block';
                        customDocNameInput.required = true;
                    } else {
                        customDocNameWrapper.style.display = 'none';
                        customDocNameInput.required = false;
                    }
                });
            });
        </script>";

    return ob_get_clean();
}

// Gestion du téléversement (inchangé)
function fsbdd_handle_file_upload_admin() {
    // Vérifier la soumission
    if (!isset($_POST['upload_document_nonce']) || !wp_verify_nonce($_POST['upload_document_nonce'], 'upload_document_action')) {
        wp_die('Vérification de sécurité échouée.');
    }

    // Vérifier les droits de l'utilisateur
    $action_id = intval($_POST['action_id']);
    if (!current_user_can('edit_post', $action_id)) {
        wp_die('Vous n’avez pas les permissions nécessaires.');
    }

    // Vérifier les champs obligatoires
    if (empty($_POST['formateur_id']) || empty($_POST['document_type']) || empty($_FILES['fsbdd_file_upload'])) {
        wp_die('Données manquantes.');
    }

    $formateur_id = intval($_POST['formateur_id']);
    $action_title_slug = sanitize_text_field($_POST['action_title_slug']);
    $document_type = sanitize_text_field($_POST['document_type']);
    $custom_doc_name = isset($_POST['custom_doc_name']) ? sanitize_file_name($_POST['custom_doc_name']) : '';
    $file = $_FILES['fsbdd_file_upload'];

    // Définir le chemin de stockage
    $upload_dir = WP_CONTENT_DIR . "/documents-internes/$formateur_id/$action_title_slug";

    // Créer le dossier si nécessaire
    if (!file_exists($upload_dir)) {
        wp_mkdir_p($upload_dir);
    }

    // Renommer le fichier
    $new_file_name = match ($document_type) {
        'emargements' => "emargements-$formateur_id-$action_title_slug",
        'compterenduf' => "compterenduf-$formateur_id-$action_title_slug",
        'evaluations' => "evaluations-$formateur_id-$action_title_slug",
        'autre' => $custom_doc_name ? "$custom_doc_name-$formateur_id-$action_title_slug" : wp_die('Nom personnalisé requis pour "autre".'),
        default => wp_die('Type de document invalide.'),
    };

    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_file_name .= ".$file_extension";

    $file_path = "$upload_dir/$new_file_name";

    // Vérifier si le fichier existe et est validé
    $meta_key_sent = '_sent_' . md5($file_path);
    $meta_key_validated = '_validated_' . md5($file_path);

    if (file_exists($file_path)) {
        $validation_date = get_post_meta($action_id, $meta_key_validated, true);
        if ($validation_date) {
            wp_die("Ce document a déjà été validé le $validation_date et ne peut être remplacé.");
        }
    }

    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        wp_die('Impossible de déplacer le fichier.');
    }

    // Enregistrer la date d'envoi
    $current_date = date('d/m/Y');
    update_post_meta($action_id, $meta_key_sent, $current_date);

    // *** Appeler les mises à jour d'état pour chaque type de document ***
    update_fsbdd_etat_documents($action_id, 'emargements');
    update_fsbdd_etat_documents($action_id, 'compterenduf');
    update_fsbdd_etat_documents($action_id, 'evaluations');

    // Rediriger avec un message de succès
    wp_redirect(add_query_arg('file_uploaded', '1', wp_get_referer()));
    exit;
}

// Ajouter le hook pour gérer l'upload dans l'admin
add_action('admin_post_fsbdd_upload_document', 'fsbdd_handle_file_upload_admin');

// Ajouter des notifications de succès
add_action('admin_notices', function () {
    if (isset($_GET['file_uploaded']) && $_GET['file_uploaded'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Fichier téléversé avec succès.</p></div>';
    }
});