<?php
/**
 * Snippet ID: 145
 * Name: side box Envoyer Documents signés par mail depuis commande
 * Description: 
 * @active false
 */

// Créer une metabox pour upload les fichiers vers le dossier /wp-content/uploads/pdfclients si signature par mail
add_action('add_meta_boxes', 'custom_order_metabox');
function custom_order_metabox() {
    add_meta_box(
        'custom_order_metabox',
        __('Envoyer Documents signés par mail', 'woocommerce'),
        'custom_order_metabox_content',
        'shop_order',
        'side',
        'default'
    );
}

function custom_order_metabox_content($post) {
    wp_nonce_field('custom_order_metabox_nonce', 'custom_order_metabox_nonce');
    echo '<input type="file" id="custom_pdf_upload" name="custom_pdf_upload" accept="application/pdf" />';
}



add_action('save_post', 'save_custom_order_metabox', 10, 1);
function save_custom_order_metabox($post_id) {
    if (!isset($_POST['custom_order_metabox_nonce']) || !wp_verify_nonce($_POST['custom_order_metabox_nonce'], 'custom_order_metabox_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['post_type']) && 'shop_order' == $_POST['post_type']) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    if (!empty($_FILES['custom_pdf_upload']['name'])) {
        $uploaded_file = $_FILES['custom_pdf_upload'];
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['basedir'] . '/pdfclients/';
        $upload_file_path = $upload_path . basename($uploaded_file['name']);

        if (!file_exists($upload_path)) {
            wp_mkdir_p($upload_path);
        }

        // Supprimer le fichier existant s'il y en a un avec le même nom
        if (file_exists($upload_file_path)) {
            unlink($upload_file_path);
        }

        move_uploaded_file($uploaded_file['tmp_name'], $upload_file_path);
    }
}