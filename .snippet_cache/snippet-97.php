<?php
/**
 * Snippet ID: 97
 * Name: Hook saisie auto frais depuis page plannings
 * Description: 
 * @active false
 */

// 10. Handler pour exécuter le hook rwmb_infos-sessions_after_save_post pour tous les CPT modifiés
function execute_custom_hook_for_modified_cpts() {
    // Vérifier les permissions utilisateur
    if (!current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas les permissions nécessaires pour effectuer cette action.', 'your-text-domain'));
    }

    // Vérifier le nonce pour la sécurité
    if (
        !isset($_POST['execute_custom_hook_nonce_field']) ||
        !wp_verify_nonce($_POST['execute_custom_hook_nonce_field'], 'execute_custom_hook_nonce')
    ) {
        wp_die(__('Nonce invalide.', 'your-text-domain'));
    }

    // Récupérer les confirmations stockées dans le transient
    $confirmations = get_transient('planning_confirmations');
    if (!$confirmations) {
        wp_redirect(admin_url('admin.php?page=gestion-plannings'));
        exit;
    }

    // Itérer sur chaque action_id et exécuter le hook
    foreach ($confirmations as $action_id => $actions) {
        do_action('rwmb_infos-sessions_after_save_post', $action_id);
    }

    // Ajouter une notice de succès
    add_action('admin_notices', function() use ($confirmations) {
        echo '<div class="notice notice-success is-dismissible"><p>';
        echo __('Le hook a été exécuté pour tous les CPT modifiés.', 'your-text-domain');
        echo '</p></div>';
    });

    // Rediriger vers la page admin pour afficher la notice
    wp_redirect(admin_url('admin.php?page=gestion-plannings'));
    exit;
}
add_action('admin_post_execute_custom_hook', 'execute_custom_hook_for_modified_cpts');
