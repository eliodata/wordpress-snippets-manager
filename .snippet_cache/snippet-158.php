<?php
/**
 * Snippet ID: 158
 * Name: envoyer copie mails statuts commande e2pdf a referent fs basé sur le champ Metabox
 * Description: 
 * @active false
 */

// envoyer copie mails statuts commande e2pdf a referent fs basé sur le champ Metabox
add_action('woocommerce_order_status_changed', 'send_custom_status_change_email_to_referent', 10, 4);

function send_custom_status_change_email_to_referent($order_id, $from_status, $to_status, $order) {
    // Récupérer l'ID de l'utilisateur référent depuis la metabox
    $user_referent_id = get_post_meta($order_id, 'fsbdd_user_referentrel', true);

    if (!empty($user_referent_id)) {
        $user = get_user_by('id', $user_referent_id);
        if ($user && !empty($user->user_email)) {
            // Sauvegarder l'ancien nom de l'expéditeur
            $old_wp_mail_from_name = add_filter('wp_mail_from_name', function ($name) { return 'Commande FS'; });

            // Récupérer les informations de la commande
            $billing_company = $order->get_billing_company();
            $billing_first_name = $order->get_billing_first_name();
            $billing_last_name = $order->get_billing_last_name();

            // Préparer l'email
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $subject = 'Mise à jour commande "' . $order_id . ' ' . $billing_company . ' ' . $billing_first_name . ' ' . $billing_last_name . '"';
            $admin_url = admin_url('post.php?post=' . $order_id . '&action=edit');
            $body = '<p>Suivi de commande:</p>
                     <p>La commande n° <strong>' . $order_id . '</strong> de <strong>' . $billing_company . '</strong>, par <strong>' . $billing_first_name . ' ' . $billing_last_name . '</strong> a changé de statut:<br>
                     de "<strong>' . wc_get_order_status_name($from_status) . '</strong>" à "<strong>' . wc_get_order_status_name($to_status) . '</strong>"<br>
                     Vérifier le suivi depuis la page d\'administration: <a href="' . $admin_url . '">cliquer ICI</a></p>
                     <p>Merci</p>';
            $to = $user->user_email; // Envoyer seulement au référent

            // Envoyer l'email
            wp_mail($to, $subject, $body, $headers);

            // Restaurer l'ancien nom de l'expéditeur
            remove_filter('wp_mail_from_name', function ($name) { return 'Commande FS'; });
            add_filter('wp_mail_from_name', function ($name) use ($old_wp_mail_from_name) { return $old_wp_mail_from_name; });
        }
    }
    // Ne rien faire si aucun référent n'est spécifié
}