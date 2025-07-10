<?php
/**
 * Snippet ID: 6
 * Name: Champs personnalisés création utilisateur et compte utilisateur wp
 * Description: 
 * @active true
 */

// Ajouter des champs personnalisés dans le formulaire d'inscription utilisateur en admin WordPress

function custom_admin_user_new_form() {
    $args = array(
        'post_type' => 'client',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    );

    $posts = get_posts($args);

    ?>
    <h3><?php _e('Informations supplémentaires', 'domain'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="user_client_relation"><?php _e('Client', 'domain'); ?></label></th>
            <td>
                <select name="user_client_relation" id="user_client_relation" class="regular-text">
                    <option value=""><?php _e('Sélectionnez un client', 'domain'); ?></option>
                    <?php foreach ($posts as $post) : ?>
                        <option value="<?php echo esc_attr($post->ID); ?>"><?php echo esc_html($post->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="user_siret"><?php _e('SIRET', 'domain'); ?></label></th>
            <td><input type="text" name="user_siret" id="user_siret" value="" class="regular-text" /></td>
        </tr>
        <!-- Les champs de facturation WooCommerce -->
        <tr>
            <th><label for="billing_company"><?php _e('Société', 'domain'); ?></label></th>
            <td><input type="text" name="billing_company" id="billing_company" value="" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="billing_address_1"><?php _e('Adresse 1', 'domain'); ?></label></th>
            <td><input type="text" name="billing_address_1" id="billing_address_1" value="" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="billing_address_2"><?php _e('Adresse 2', 'domain'); ?></label></th>
            <td><input type="text" name="billing_address_2" id="billing_address_2" value="" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="billing_city"><?php _e('Ville', 'domain'); ?></label></th>
            <td><input type="text" name="billing_city" id="billing_city" value="" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="billing_postcode"><?php _e('Code Postal', 'domain'); ?></label></th>
            <td><input type="text" name="billing_postcode" id="billing_postcode" value="" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="billing_phone"><?php _e('Téléphone', 'domain'); ?></label></th>
            <td><input type="text" name="billing_phone" id="billing_phone" value="" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="billing_email"><?php _e('Email', 'domain'); ?></label></th>
            <td><input type="email" name="billing_email" id="billing_email" value="" class="regular-text" /></td>
        </tr>
    </table>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Décocher la case "Envoyer une notification au compte"
            $('#send_user_notification').prop('checked', false);

            $('#user_client_relation').change(function() {
                var clientId = $(this).val();
                if(clientId) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'get_client_info',
                            client_id: clientId
                        },
                        success: function(response) {
                            var data = JSON.parse(response);
                            $('#user_siret').val(data.siret);
                            $('#email').val(data.email); // Pour le champ email de l'utilisateur
                            $('#user_login').val(data.user_login);
                            $('#first_name').val(data.first_name); // Ajout du champ prénom
                            $('#billing_company').val(data.billing_company);
                            $('#billing_address_1').val(data.billing_address_1);
                            $('#billing_address_2').val(data.billing_address_2);
                            $('#billing_city').val(data.billing_city);
                            $('#billing_postcode').val(data.billing_postcode);
                            $('#billing_phone').val(data.billing_phone);
                            $('#billing_email').val(data.billing_email);
                        }
                    });
                }
            });
        });
    </script>
    <?php
}
add_action('user_new_form', 'custom_admin_user_new_form');

// Gérer la requête AJAX pour remplir les champs utilisateur depuis les champs du compte client

add_action('wp_ajax_get_client_info', 'get_client_info_callback');

function get_client_info_callback() {
    $client_id = intval($_POST['client_id']);
    if ($client_id) {
        $response = array(
            'siret' => get_post_meta($client_id, 'fsbdd_text_siret', true),
            'email' => get_post_meta($client_id, 'fsbdd_email_nom2', true),
            'user_login' => get_the_title($client_id),
            'first_name' => get_post_meta($client_id, 'fsbdd_text_nom2', true), // Ajout du champ prénom
            'billing_company' => get_the_title($client_id),
            'billing_address_1' => get_post_meta($client_id, 'fsbdd_text_adresse_1', true),
            'billing_address_2' => get_post_meta($client_id, 'fsbdd_text_adresse_2', true),
            'billing_city' => get_post_meta($client_id, 'fsbdd_text_ville', true),
            'billing_postcode' => get_post_meta($client_id, 'fsbdd_text_cp', true),
            'billing_phone' => get_post_meta($client_id, 'fsbdd_text_teldirect', true),
            'billing_email' => get_post_meta($client_id, 'fsbdd_email_nom2', true),
        );

        echo json_encode($response);
    }
    wp_die();
}

// Enregistrer les champs personnalisés lors de la création d'un nouvel utilisateur

add_action('user_register', 'save_custom_user_meta', 10, 1);

function save_custom_user_meta($user_id) {
    if (isset($_POST['user_siret'])) {
        update_user_meta($user_id, 'user_siret', $_POST['user_siret']);
    }
    if (isset($_POST['billing_company'])) {
        update_user_meta($user_id, 'billing_company', $_POST['billing_company']);
    }
    if (isset($_POST['billing_address_1'])) {
        update_user_meta($user_id, 'billing_address_1', $_POST['billing_address_1']);
    }
    if (isset($_POST['billing_address_2'])) {
        update_user_meta($user_id, 'billing_address_2', $_POST['billing_address_2']);
    }
    if (isset($_POST['billing_city'])) {
        update_user_meta($user_id, 'billing_city', $_POST['billing_city']);
    }
    if (isset($_POST['billing_postcode'])) {
        update_user_meta($user_id, 'billing_postcode', $_POST['billing_postcode']);
    }
    if (isset($_POST['billing_phone'])) {
        update_user_meta($user_id, 'billing_phone', $_POST['billing_phone']);
    }
    if (isset($_POST['billing_email'])) {
        update_user_meta($user_id, 'billing_email', $_POST['billing_email']);
    }
    if (isset($_POST['first_name'])) {
        update_user_meta($user_id, 'first_name', $_POST['first_name']); // Ajout de la sauvegarde du prénom
    }
}




// AJOUTER pas de société par defaut champ société creation utilisateur
function custom_admin_user_new_form_save($user_id) {
    $billing_company = isset($_POST['billing_company']) && !empty($_POST['billing_company']) ? sanitize_text_field($_POST['billing_company']) : 'Pas de société';
    update_user_meta($user_id, 'billing_company', $billing_company);
}
add_action('user_register', 'custom_admin_user_new_form_save');



// CHARGER CHAMP SIRET COMPTE CLIENT WP VERS INFOS FACTURATION COMMANDE CHAMP SIRET
add_action( 'admin_footer', 'update_order_custom_field_from_user_on_edit' );
function update_order_custom_field_from_user_on_edit() {
    global $post, $pagenow;

    // Vérifier si nous sommes sur la page d'édition de commande
    if ( $pagenow == 'post.php' && get_post_type( $post->ID ) == 'shop_order' ) {
        // Obtenir l'objet de la commande
        $order = wc_get_order( $post->ID );

        // Obtenir l'ID de l'utilisateur/client actuel
        $user_id = $order->get_customer_id();

        // Obtenir l'ID de l'utilisateur/client précédent stocké dans les métadonnées
        $previous_user_id = $order->get_meta( '_previous_customer_id' );

        // Si l'ID de l'utilisateur a changé ou n'a jamais été stocké
        if ( $user_id != $previous_user_id ) {
            // Mettre à jour l'ID de l'utilisateur précédent dans les métadonnées
            $order->update_meta_data( '_previous_customer_id', $user_id );

            // Vérifier si l'utilisateur existe et a un champ fsbdd_siret
            if ( $user_id && $siret = get_user_meta( $user_id, 'fsbdd_text_siret', true ) ) {
                // Mettre à jour le champ personnalisé de la commande avec la valeur de l'utilisateur
                $order->update_meta_data( 'fsbdd_text_siret', $siret );
                $order->save_meta_data();
            }
        }
    }
}