<?php
/**
 * Snippet ID: 104
 * Name: bouton mise a jour planning
 * Description: 
 * @active false
 */

function myplugin_add_button_gestion_plannings() {
    if ( isset($_GET['page']) && $_GET['page'] === 'gestion-plannings' ) {
        $nonce = wp_create_nonce('execute_my_update_logic_for_267258');
        $url   = add_query_arg([
            'page'     => 'gestion-plannings',
            'my_action'=> 'update_267258',
            '_wpnonce' => $nonce
        ], admin_url('admin.php'));

        echo '<div style="margin: 20px 0;">';
        echo '<a href="'. esc_url($url) .'" class="button button-primary">Mettre à jour le CPT #267258</a>';
        echo '</div>';
    }
}
add_action('admin_notices', 'myplugin_add_button_gestion_plannings');

function myplugin_handle_execute_my_update_logic() {
    if (
        isset($_GET['my_action']) && 
        $_GET['my_action'] === 'update_267258' &&
        isset($_GET['_wpnonce']) && 
        wp_verify_nonce($_GET['_wpnonce'], 'execute_my_update_logic_for_267258')
    ) {
        // Ici on appelle directement la fonction de logique
        my_update_fsbdd_grpctsformation(267258);

        // Redirection pour éviter la ré-exécution en rafraîchissant la page
        wp_redirect(admin_url('admin.php?page=gestion-plannings&update_done=1'));
        exit;
    }
}
add_action('admin_init', 'myplugin_handle_execute_my_update_logic');
