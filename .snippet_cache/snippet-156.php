<?php
/**
 * Snippet ID: 156
 * Name: nom prenom cpt client prospect vers utilisateur wp et champs facturation
 * Description: 
 * @active false
 */

/**
 * Remplissage des champs utilisateur à partir des CPT clients/prospects
 * 
 * @package   UserFieldsCopier
 * @author    Claude Assistant
 * @version   2.0.0
 */

// Éviter l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Fonction pour séparer le nom complet en prénom et nom
if (!function_exists('fs_split_full_name')) {
    function fs_split_full_name($full_name) {
        $full_name = trim($full_name);
        $parts = explode(' ', $full_name);
        
        // Si un seul mot, traiter comme nom de famille
        if (count($parts) < 2) {
            return [
                'first_name' => '',
                'last_name' => $full_name
            ];
        }
        
        // Premier mot comme prénom, reste comme nom
        $first_name = array_shift($parts);
        $last_name = implode(' ', $parts);
        
        return [
            'first_name' => $first_name,
            'last_name' => $last_name
        ];
    }
}

// Fonction pour mettre à jour les utilisateurs
if (!function_exists('fs_copy_cpt_names_to_user_fields')) {
    function fs_copy_cpt_names_to_user_fields() {
        global $wpdb;
        
        // Nombre d'utilisateurs à traiter par lot
        $users_per_batch = 50;
        $paged = 1;
        $updated_count = 0;
        $total_processed = 0;
        
        do {
            // Récupérer les utilisateurs par lot
            $args = array(
                'number' => $users_per_batch,
                'paged' => $paged,
                'fields' => array('ID'),
            );
            
            $user_query = new WP_User_Query($args);
            $users = $user_query->get_results();
            
            // Si pas d'utilisateurs, sortir de la boucle
            if (empty($users)) {
                break;
            }
            
            $total_processed += count($users);
            
            // Traiter chaque utilisateur
            foreach ($users as $user) {
                $user_id = $user->ID;
                $updated = false;
                
                // Récupérer les CPT clients/prospects liés à cet utilisateur
                $cpt_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT `to` FROM {$wpdb->prefix}mb_relationships WHERE `from` = %d AND `type` = 'clients-wp-bdd'",
                    $user_id
                ));
                
                if (!empty($cpt_ids)) {
                    // Utiliser le premier CPT trouvé (généralement un seul)
                    $cpt_id = $cpt_ids[0];
                    
                    // Récupérer le champ nom du CPT client/prospect
                    $nom_complet = get_post_meta($cpt_id, 'fsbdd_text_nom2', true);
                    
                    if (!empty($nom_complet)) {
                        // Séparer le nom complet en prénom et nom
                        $name_parts = fs_split_full_name($nom_complet);
                        
                        // Récupérer les valeurs actuelles
                        $current_first_name = get_user_meta($user_id, 'first_name', true);
                        $current_last_name = get_user_meta($user_id, 'last_name', true);
                        $current_billing_first_name = get_user_meta($user_id, 'billing_first_name', true);
                        $current_billing_last_name = get_user_meta($user_id, 'billing_last_name', true);
                        
                        // Mise à jour du prénom principal si vide
                        if (empty($current_first_name) && !empty($name_parts['first_name'])) {
                            update_user_meta($user_id, 'first_name', $name_parts['first_name']);
                            $updated = true;
                        }
                        
                        // Mise à jour du nom principal si vide
                        if (empty($current_last_name) && !empty($name_parts['last_name'])) {
                            update_user_meta($user_id, 'last_name', $name_parts['last_name']);
                            $updated = true;
                        }
                        
                        // Mise à jour du prénom de facturation si vide
                        if (empty($current_billing_first_name) && !empty($name_parts['first_name'])) {
                            update_user_meta($user_id, 'billing_first_name', $name_parts['first_name']);
                            $updated = true;
                        }
                        
                        // Mise à jour du nom de facturation si vide
                        if (empty($current_billing_last_name) && !empty($name_parts['last_name'])) {
                            update_user_meta($user_id, 'billing_last_name', $name_parts['last_name']);
                            $updated = true;
                        }
                    }
                }
                
                // Incrémenter le compteur si l'utilisateur a été mis à jour
                if ($updated) {
                    $updated_count++;
                }
            }
            
            // Passer au lot suivant
            $paged++;
            
            // Libérer la mémoire
            wp_cache_flush();
            
        } while (count($users) === $users_per_batch); // Continuer tant qu'il y a des utilisateurs
        
        // Enregistrer un message dans la base de données pour l'afficher à l'administrateur
        set_transient('fs_fields_cpt_notice', [
            'type' => 'success',
            'message' => sprintf('Opération terminée : %d utilisateurs mis à jour sur %d traités. Les noms ont été extraits des fiches client/prospect.', $updated_count, $total_processed)
        ], 60 * 60); // Garde la notice pendant 1 heure
        
        // Rediriger pour afficher la notification
        if (is_admin()) {
            wp_redirect(admin_url('users.php'));
            exit;
        }
    }
}

// Exécuter la fonction si l'action est demandée
if (isset($_GET['fs_copy_cpt_fields']) && current_user_can('manage_options')) {
    add_action('admin_init', 'fs_copy_cpt_names_to_user_fields');
}

// Afficher la notification admin spécifique à ce script
if (!function_exists('fs_display_cpt_copy_notice')) {
    function fs_display_cpt_copy_notice() {
        $notice = get_transient('fs_fields_cpt_notice');
        
        if ($notice) {
            echo '<div class="notice notice-' . esc_attr($notice['type']) . ' is-dismissible">';
            echo '<p>' . esc_html($notice['message']) . '</p>';
            echo '</div>';
            
            // Supprimer la notification après affichage
            delete_transient('fs_fields_cpt_notice');
        }
    }
    add_action('admin_notices', 'fs_display_cpt_copy_notice');
}

// Ajouter un bouton en haut de la page Utilisateurs
if (!function_exists('fs_add_copy_cpt_fields_button')) {
    function fs_add_copy_cpt_fields_button() {
        $screen = get_current_screen();
        
        // Vérifier qu'on est sur la page des utilisateurs
        if ($screen && $screen->id === 'users' && current_user_can('manage_options')) {
            $url = add_query_arg('fs_copy_cpt_fields', 'true', admin_url('users.php'));
            ?>
            <div class="notice notice-info is-dismissible">
                <h3>Import des noms depuis les fiches client/prospect</h3>
                <p>Cliquez sur le bouton ci-dessous pour copier les noms depuis les fiches client/prospect vers les champs utilisateur (uniquement si ces derniers sont vides).</p>
                <p><a href="<?php echo esc_url($url); ?>" class="button button-primary">Importer les noms depuis les fiches client/prospect</a></p>
            </div>
            <?php
        }
    }
    add_action('admin_notices', 'fs_add_copy_cpt_fields_button');
}