<?php
/**
 * Snippet ID: 92
 * Name: ACTIVER MANUELLEMENT MISES A JOUR CPT ACTION DE FORMATION APRES MODIFS PLANNING
 * Description: 
 * @active false
 */

// Fonction pour traiter le déclenchement manuel des hooks
function handle_trigger_rwmb_hook() {
    // Vérifier les permissions
    if ( ! current_user_can( 'manage_options' ) ) { // ajustez selon les besoins
        wp_die( __('Vous n\'avez pas les permissions nécessaires pour effectuer cette action.', 'your-text-domain') );
    }

    // Vérifier le nonce
    if ( ! isset($_POST['trigger_rwmb_hook_nonce_field']) || ! wp_verify_nonce( $_POST['trigger_rwmb_hook_nonce_field'], 'trigger_rwmb_hook_nonce' ) ) {
        wp_die( __('Nonce invalide.', 'your-text-domain') );
    }

    // Récupérer les IDs des actions en attente
    $pending_ids = get_option('pending_rwmb_infos_sessions_after_save_post_ids', []);

    if ( empty($pending_ids) ) {
        wp_redirect( admin_url('admin.php?page=gestion-plannings&message=none') );
        exit;
    }

    // Déclencher le hook pour chaque action ID
    foreach ( $pending_ids as $action_id ) {
        do_action('rwmb_infos-sessions_after_save_post', $action_id);
    }

    // Nettoyer les IDs après traitement
    delete_option('pending_rwmb_infos_sessions_after_save_post_ids');

    // Rediriger avec un message de succès
    wp_redirect( admin_url('admin.php?page=gestion-plannings&message=hooks_triggered') );
    exit;
}
add_action('admin_post_trigger_rwmb_hook', 'handle_trigger_rwmb_hook');
