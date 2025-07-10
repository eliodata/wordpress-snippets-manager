<?php
/**
 * Snippet ID: 180
 * Name: Traduire boutons pdf invoice builder pro download facture sur commande client
 * Description: 
 * @active true
 */

add_filter('gettext', 'replace_download_text_everywhere', 999, 3);
function replace_download_text_everywhere($translated_text, $text, $domain) {
    if ($text === 'Download' || $translated_text === 'Download') {
        // Vérifier si nous sommes sur une page WooCommerce
        if (is_woocommerce() || is_account_page() || is_checkout()) {
            return 'Facture';
        }
    }
    return $translated_text;
}