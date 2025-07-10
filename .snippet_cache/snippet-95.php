<?php
/**
 * Snippet ID: 95
 * Name: activer hook liaison plannings frais cpt action depuis page plannings
 * Description: 
 * @active false
 */

/**
 * Gère l'action de déclenchement du hook rwmb_infos-sessions_after_save_post.
 */
function handle_trigger_rwmb_infos_sessions_after_save_post() {
    // Vérifie si l'utilisateur a les permissions nécessaires
    if (!current_user_can('edit_posts')) {
        wp_die(__('Vous n\'avez pas les permissions nécessaires pour effectuer cette action.', 'your-text-domain'));
    }

    // Vérifie la présence du nonce et sa validité
    if (!isset($_GET['trigger_nonce']) || !wp_verify_nonce($_GET['trigger_nonce'], 'trigger_rwmb_infos_sessions_nonce')) {
        wp_die(__('Nonce invalide.', 'your-text-domain'));
    }

    // Récupère et sanitise l'ID du post
    if (!isset($_GET['post_id']) || !is_numeric($_GET['post_id'])) {
        wp_die(__('ID de post invalide.', 'your-text-domain'));
    }

    $post_id = intval($_GET['post_id']);

    // Vérifie si le post existe et est du type 'action-de-formation'
    if (get_post_type($post_id) !== 'action-de-formation') {
        wp_die(__('Le post spécifié n\'est pas du type attendu.', 'your-text-domain'));
    }

    // Déclenche le hook avec l'ID du post
    do_action('rwmb_infos-sessions_after_save_post', $post_id);

    // Redirige vers la page d'origine avec un message de succès
    $redirect_url = add_query_arg('triggered', '1', wp_get_referer());
    wp_redirect($redirect_url);
    exit;
}
add_action('admin_post_trigger_rwmb_infos_sessions_after_save_post', 'handle_trigger_rwmb_infos_sessions_after_save_post');
