<?php
/**
 * Snippet ID: 183
 * Name: upadate mise a jour bulk en masse tous les cpt action-de-formation
 * Description: 
 * @active false
 */


// Ajouter une page d'administration pour la mise à jour en masse
add_action('admin_menu', 'add_formation_bulk_update_page');
function add_formation_bulk_update_page() {
    add_submenu_page(
        'edit.php?post_type=action-de-formation',
        'Mise à jour en masse',
        'Mise à jour en masse',
        'manage_options',
        'bulk-update-formations',
        'formation_bulk_update_page'
    );
}

// Page d'administration
function formation_bulk_update_page() {
    ?>
    <div class="wrap">
        <h1>Mise à jour en masse des Actions de Formation</h1>
        
        <div id="update-progress" style="display:none;">
            <p><strong>Mise à jour en cours...</strong></p>
            <div style="background: #f1f1f1; border: 1px solid #ddd; height: 20px; width: 100%; position: relative;">
                <div id="progress-bar" style="background: #0073aa; height: 100%; width: 0%; transition: width 0.3s;"></div>
                <span id="progress-text" style="position: absolute; width: 100%; text-align: center; line-height: 20px; font-size: 12px;"></span>
            </div>
            <p id="current-post"></p>
            <p><em>⚠️ Les emails sont temporairement désactivés pendant la mise à jour</em></p>
        </div>
        
        <div id="update-results" style="display:none;">
            <h3>Résultats</h3>
            <div id="results-content"></div>
        </div>
        
        <?php
        $formations = get_posts(array(
            'post_type' => 'action-de-formation',
            'post_status' => array('publish', 'draft', 'private'),
            'numberposts' => -1
        ));
        $total_formations = count($formations);
        ?>
        
        <div id="update-form">
            <p>Nombre total d'actions de formation trouvées : <strong><?php echo $total_formations; ?></strong></p>
            
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 15px 0; border-radius: 4px;">
                <h4>⚠️ Attention</h4>
                <p>Cette opération va :</p>
                <ul>
                    <li>Sauvegarder tous les posts "action-de-formation" existants</li>
                    <li>Déclencher tous les hooks de sauvegarde (comme une sauvegarde manuelle)</li>
                    <li>Mettre à jour les métadonnées et relations</li>
                    <li>Désactiver temporairement les emails pendant le processus</li>
                </ul>
                <p><strong>Durée estimée :</strong> ~<?php echo ceil($total_formations / 10); ?> minutes</p>
            </div>
            
            <button type="button" id="start-bulk-update" class="button button-primary">
                Démarrer la mise à jour en masse
            </button>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const startBtn = document.getElementById('start-bulk-update');
        const progressDiv = document.getElementById('update-progress');
        const resultsDiv = document.getElementById('update-results');
        const formDiv = document.getElementById('update-form');
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        const currentPostDiv = document.getElementById('current-post');
        const resultsContent = document.getElementById('results-content');
        
        let totalPosts = <?php echo $total_formations; ?>;
        let processedPosts = 0;
        let successCount = 0;
        let errorCount = 0;
        
        startBtn.addEventListener('click', function() {
            if (!confirm('Êtes-vous sûr de vouloir mettre à jour tous les posts action-de-formation ?\n\nCette opération peut prendre plusieurs minutes et va désactiver temporairement les emails.')) {
                return;
            }
            
            formDiv.style.display = 'none';
            progressDiv.style.display = 'block';
            
            // Commencer le traitement
            processFormations();
        });
        
        function processFormations() {
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=bulk_update_formations&nonce=<?php echo wp_create_nonce("bulk_update_formations"); ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    processBatch(data.data.posts, 0);
                } else {
                    alert('Erreur lors de la récupération des posts : ' + data.data);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la communication avec le serveur');
            });
        }
        
        function processBatch(posts, index) {
            if (index >= posts.length) {
                // Terminé - réactiver les emails
                reactivateEmails().then(() => {
                    showResults();
                });
                return;
            }
            
            const post = posts[index];
            updateProgress(index + 1, post.post_title);
            
            // Mettre à jour le post
            updateSinglePost(post.ID).then(() => {
                successCount++;
                // Petite pause pour éviter de surcharger le serveur
                setTimeout(() => {
                    processBatch(posts, index + 1);
                }, 100);
            }).catch(() => {
                errorCount++;
                setTimeout(() => {
                    processBatch(posts, index + 1);
                }, 100);
            });
        }
        
        function updateSinglePost(postId) {
            return fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_single_formation&post_id=${postId}&nonce=<?php echo wp_create_nonce("update_single_formation"); ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.data);
                }
                return data;
            });
        }
        
        function reactivateEmails() {
            return fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=reactivate_emails&nonce=<?php echo wp_create_nonce("reactivate_emails"); ?>`
            });
        }
        
        function updateProgress(current, postTitle) {
            processedPosts = current;
            const percentage = Math.round((current / totalPosts) * 100);
            
            progressBar.style.width = percentage + '%';
            progressText.textContent = `${current}/${totalPosts} (${percentage}%)`;
            currentPostDiv.textContent = `Post en cours : ${postTitle}`;
        }
        
        function showResults() {
            progressDiv.style.display = 'none';
            resultsDiv.style.display = 'block';
            
            resultsContent.innerHTML = `
                <div class="notice notice-success">
                    <p><strong>Mise à jour terminée !</strong></p>
                    <p>Posts traités avec succès : ${successCount}</p>
                    <p>Erreurs rencontrées : ${errorCount}</p>
                    <p>Total traité : ${processedPosts}</p>
                    <p>✅ Emails réactivés</p>
                </div>
                <button type="button" onclick="location.reload()" class="button">Actualiser la page</button>
            `;
        }
    });
    </script>
    <?php
}

// Fonction pour désactiver temporairement les emails
function disable_emails_temporarily() {
    // Désactiver les emails WordPress
    add_filter('wp_mail', '__return_false');
    
    // Désactiver spécifiquement les emails WooCommerce si présent
    if (class_exists('WooCommerce')) {
        add_filter('woocommerce_email_enabled_new_order', '__return_false');
        add_filter('woocommerce_email_enabled_customer_processing_order', '__return_false');
        add_filter('woocommerce_email_enabled_customer_completed_order', '__return_false');
        add_filter('woocommerce_email_enabled_customer_invoice', '__return_false');
        add_filter('woocommerce_email_enabled_customer_note', '__return_false');
        add_filter('woocommerce_email_enabled_customer_reset_password', '__return_false');
        add_filter('woocommerce_email_enabled_customer_new_account', '__return_false');
    }
    
    // Stocker l'état pour pouvoir réactiver
    update_option('bulk_update_emails_disabled', true);
}

// Fonction pour réactiver les emails
function reactivate_emails() {
    // Supprimer tous les filtres d'email
    remove_filter('wp_mail', '__return_false');
    
    if (class_exists('WooCommerce')) {
        remove_filter('woocommerce_email_enabled_new_order', '__return_false');
        remove_filter('woocommerce_email_enabled_customer_processing_order', '__return_false');
        remove_filter('woocommerce_email_enabled_customer_completed_order', '__return_false');
        remove_filter('woocommerce_email_enabled_customer_invoice', '__return_false');
        remove_filter('woocommerce_email_enabled_customer_note', '__return_false');
        remove_filter('woocommerce_email_enabled_customer_reset_password', '__return_false');
        remove_filter('woocommerce_email_enabled_customer_new_account', '__return_false');
    }
    
    delete_option('bulk_update_emails_disabled');
}

// AJAX pour récupérer la liste des posts
add_action('wp_ajax_bulk_update_formations', 'handle_bulk_update_formations');
function handle_bulk_update_formations() {
    // Vérification de sécurité
    if (!check_ajax_referer('bulk_update_formations', 'nonce', false)) {
        wp_die('Erreur de sécurité');
    }
    
    if (!current_user_can('manage_options')) {
        wp_die('Permissions insuffisantes');
    }
    
    // Désactiver les emails avant de commencer
    disable_emails_temporarily();
    
    $posts = get_posts(array(
        'post_type' => 'action-de-formation',
        'post_status' => array('publish', 'draft', 'private'),
        'numberposts' => -1,
        'fields' => 'ID,post_title'
    ));
    
    wp_send_json_success(array('posts' => $posts));
}

// AJAX pour mettre à jour un post individuel
add_action('wp_ajax_update_single_formation', 'handle_update_single_formation');
function handle_update_single_formation() {
    // Vérification de sécurité
    if (!check_ajax_referer('update_single_formation', 'nonce', false)) {
        wp_send_json_error('Erreur de sécurité');
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissions insuffisantes');
    }
    
    $post_id = intval($_POST['post_id']);
    
    if (!$post_id) {
        wp_send_json_error('ID de post invalide');
    }
    
    // Vérifier que le post existe et est du bon type
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'action-de-formation') {
        wp_send_json_error('Post non trouvé ou type incorrect');
    }
    
    // Simuler une sauvegarde complète comme depuis l'interface admin
    // Récupérer tous les champs du post
    $post_data = array(
        'ID' => $post_id,
        'post_title' => $post->post_title,
        'post_content' => $post->post_content,
        'post_excerpt' => $post->post_excerpt,
        'post_status' => $post->post_status,
        'post_type' => $post->post_type,
        'post_author' => $post->post_author,
        'post_parent' => $post->post_parent,
        'menu_order' => $post->menu_order,
        'post_modified' => current_time('mysql'),
        'post_modified_gmt' => current_time('mysql', 1)
    );
    
    // Mettre à jour le post
    $result = wp_update_post($post_data, true);
    
    if (is_wp_error($result)) {
        wp_send_json_error('Erreur lors de la mise à jour : ' . $result->get_error_message());
    }
    
    // Simuler $_POST pour les hooks qui en dépendent
    global $_POST;
    $original_post = $_POST;
    
    // Préparer les données comme si elles venaient du formulaire admin
    $_POST['post_ID'] = $post_id;
    $_POST['post_type'] = 'action-de-formation';
    $_POST['action'] = 'editpost';
    
    // Récupérer et ajouter les custom fields
    $custom_fields = get_post_meta($post_id);
    foreach ($custom_fields as $key => $values) {
        if (!empty($values)) {
            $_POST[$key] = is_array($values) ? $values[0] : $values;
        }
    }
    
    // Déclencher les hooks dans le bon ordre
    do_action('pre_post_update', $post_id, $post_data);
    do_action('edit_post', $post_id, $post);
    do_action('save_post', $post_id, $post, true);
    do_action('save_post_' . $post->post_type, $post_id, $post, true);
    do_action('wp_insert_post', $post_id, $post, true);
    
    // Déclencher les hooks spécifiques aux custom fields
    do_action('acf/save_post', $post_id);
    
    // Restaurer $_POST
    $_POST = $original_post;
    
    // Vider le cache
    clean_post_cache($post_id);
    
    wp_send_json_success('Post mis à jour avec succès');
}

// AJAX pour réactiver les emails
add_action('wp_ajax_reactivate_emails', 'handle_reactivate_emails');
function handle_reactivate_emails() {
    if (!check_ajax_referer('reactivate_emails', 'nonce', false)) {
        wp_send_json_error('Erreur de sécurité');
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissions insuffisantes');
    }
    
    reactivate_emails();
    wp_send_json_success('Emails réactivés');
}

// Sécurité : réactiver les emails en cas d'interruption
add_action('init', function() {
    if (get_option('bulk_update_emails_disabled')) {
        // Si les emails sont désactivés depuis plus de 1 heure, les réactiver automatiquement
        $start_time = get_option('bulk_update_start_time');
        if (!$start_time || (time() - $start_time) > 3600) {
            reactivate_emails();
        }
    }
});

// Stocker l'heure de début lors du démarrage
add_action('wp_ajax_bulk_update_formations', function() {
    update_option('bulk_update_start_time', time());
}, 5);

// Fonction alternative pour mise à jour directe (sans interface)
function bulk_update_all_formations() {
    disable_emails_temporarily();
    
    $posts = get_posts(array(
        'post_type' => 'action-de-formation',
        'post_status' => array('publish', 'draft', 'private'),
        'numberposts' => -1
    ));
    
    $updated_count = 0;
    $error_count = 0;
    
    foreach ($posts as $post) {
        // Simuler une sauvegarde complète
        $post_data = array(
            'ID' => $post->ID,
            'post_title' => $post->post_title,
            'post_content' => $post->post_content,
            'post_excerpt' => $post->post_excerpt,
            'post_status' => $post->post_status,
            'post_type' => $post->post_type,
            'post_author' => $post->post_author,
            'post_parent' => $post->post_parent,
            'menu_order' => $post->menu_order,
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', 1)
        );
        
        $result = wp_update_post($post_data, true);
        
        if (!is_wp_error($result)) {
            $updated_count++;
            
            // Déclencher tous les hooks de sauvegarde
            do_action('save_post', $post->ID, $post, true);
            do_action('save_post_action-de-formation', $post->ID, $post, true);
            do_action('acf/save_post', $post->ID);
        } else {
            $error_count++;
        }
    }
    
    reactivate_emails();
    
    return array(
        'total' => count($posts),
        'updated' => $updated_count,
        'errors' => $error_count
    );
}

// Pour appeler la fonction directement via URL (décommentez si besoin)
/*
add_action('init', function() {
    if (isset($_GET['bulk_update_formations']) && current_user_can('manage_options')) {
        $results = bulk_update_all_formations();
        wp_die("Mise à jour terminée. Total: {$results['total']}, Mis à jour: {$results['updated']}, Erreurs: {$results['errors']}");
    }
});
*/
