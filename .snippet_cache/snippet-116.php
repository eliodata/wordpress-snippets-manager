<?php
/**
 * Snippet ID: 116
 * Name: bouton hook test
 * Description: 
 * @active false
 */


/**
 * Ajoute une page d'administration pour gérer la synchronisation des CPT 'action-de-formation'.
 */
function add_sync_formation_admin_page() {
    add_menu_page(
        'Synchronisation Formation', // Titre de la page
        'Synchronisation Formation', // Titre du menu
        'manage_options', // Capacité requise
        'sync-formation-page', // Slug de la page
        'render_sync_formation_page', // Fonction de rendu de la page
        'dashicons-update', // Icône du menu
        6 // Position dans le menu (après "Articles" par défaut)
    );
}
add_action('admin_menu', 'add_sync_formation_admin_page');

/**
 * Affiche le contenu de la page d'administration de synchronisation.
 */
function render_sync_formation_page() {
    ?>
    <div class="wrap">
        <h1>Synchronisation des Actions de Formation</h1>
        <form method="post" action="">
            <?php wp_nonce_field('sync_formation_nonce', 'sync_formation_nonce_field'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="cpt_select">Action de Formation</label></th>
                    <td>
                        <select name="cpt_select" id="cpt_select">
                            <option value="">Sélectionnez une action de formation</option>
                            <?php
                            $args = array(
                                'post_type' => 'action-de-formation',
                                'posts_per_page' => -1, // Récupérer tous les CPT
                                'post_status' => 'any', // Inclure tous les statuts
                            );
                            $cpts = get_posts($args);
                            foreach ($cpts as $cpt) {
                                echo '<option value="' . $cpt->ID . '">' . esc_html($cpt->post_title) . ' (ID: ' . $cpt->ID . ')</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button('Lancer la synchronisation', 'primary', 'submit_sync_formation'); ?>
        </form>
    </div>
    <?php
}

/**
 * Gère la soumission du formulaire et déclenche la synchronisation.
 */
function handle_sync_formation_submission() {
    if (isset($_POST['submit_sync_formation']) && isset($_POST['sync_formation_nonce_field']) && wp_verify_nonce($_POST['sync_formation_nonce_field'], 'sync_formation_nonce')) {
        if (isset($_POST['cpt_select']) && !empty($_POST['cpt_select'])) {
            $post_id = intval($_POST['cpt_select']);

            // Vérification supplémentaire : s'assurer que le post existe et est du bon type
            if (get_post_type($post_id) !== 'action-de-formation') {
                echo '<div class="notice notice-error is-dismissible"><p>L\'ID sélectionné ne correspond pas à une action de formation valide.</p></div>';
                return; // Arrête l'exécution de la fonction
            }

            // --- SOLUTION : Parcourir et mettre à jour chaque jour du planning ---
            // CORRECTION : Ajout du paramètre true pour $single
            $planning_data = rwmb_meta('fsbdd_planning', ['object_type' => 'post', 'single' => true], $post_id); 

            if (is_array($planning_data)) {
                foreach ($planning_data as $index => $day_data) {
                    rwmb_set_meta($post_id, 'fsbdd_planning', $day_data, ['object_type' => 'post'], $index);
                }
            }
            // --- FIN DE LA SOLUTION ---

            // Déclencher une action personnalisée
            do_action('sync_formation_couts_action', $post_id);

            echo '<div class="notice notice-success is-dismissible"><p>Synchronisation effectuée avec succès pour l\'action de formation ID: ' . $post_id . '.</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Veuillez sélectionner une action de formation.</p></div>';
        }
    }
}
add_action('admin_init', 'handle_sync_formation_submission');

/**
 * Hook personnalisé pour la synchronisation des coûts
 */
add_action('sync_formation_couts_action', 'sync_formation_planning_costs_custom');

/**
 * Fonction de synchronisation appelée par l'action personnalisée
 */
function sync_formation_planning_costs_custom($post_id) {
    // Retirer les hooks standards pour éviter les conflits
    remove_action('rwmb_infos-sessions_after_save_post', 'sync_formation_planning_costs');
    remove_action('save_post_action-de-formation', 'sync_formation_planning_costs');

    sync_formation_planning_costs($post_id);
}