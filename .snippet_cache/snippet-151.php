<?php
/**
 * Snippet ID: 151
 * Name: REDNAO roles compta page compta dans menu et modif code redano numéros factures
 * Description: 
 * @active true
 */

/**
 * Forcer suite séquentielle entre 2 modeles de facture pdf invoice rednao pour templates 2 / 3 et 7 / 8
 */


add_filter( 'wcpdfi_get_latest_invoice_number', 'WooGroupNumberingGetLatestInvoiceNumber', 10, 2 );
add_filter( 'wcpdfi_update_latest_invoice_number', 'WooGroupNumberingUpdateLatestInvoiceNumber', 10, 2 );

function WooGroupNumberingGetLatestInvoiceNumber( $nextNumber, $invoiceId ) {
    if ( $invoiceId == 2 || $invoiceId == 3 ) {
        return get_option( 'wcpdfi_latest_invoice_number_template_2_3', 1 );
    } elseif ( $invoiceId == 7 || $invoiceId == 8 ) {
        return get_option( 'wcpdfi_latest_invoice_number_template_7_8', 1 );
    }

    return $nextNumber;
}

function WooGroupNumberingUpdateLatestInvoiceNumber( $nextNumber, $invoiceId ) {
    if ( $invoiceId == 2 || $invoiceId == 3 ) {
        $latest_invoice_number = get_option( 'wcpdfi_latest_invoice_number_template_2_3', 1 );
        $latest_invoice_number++;
        update_option( 'wcpdfi_latest_invoice_number_template_2_3', $latest_invoice_number );
        return $latest_invoice_number;
    } elseif ( $invoiceId == 7 || $invoiceId == 8 ) {
        $latest_invoice_number = get_option( 'wcpdfi_latest_invoice_number_template_7_8', 1 );
        $latest_invoice_number++;
        update_option( 'wcpdfi_latest_invoice_number_template_7_8', $latest_invoice_number );
        return $latest_invoice_number;
    }

    return $nextNumber;
}



// LIMITER ACCES BOUTONS REDNAO PLUGIN FACTURES COMPTA A ROLE COMPTA ET ADMIN ET LE STYLO EDITION NUMERO FACTURES
function hide_invoice_options() {
    if ( ! current_user_can( 'compta' ) && ! current_user_can( 'administrator' ) && get_current_user_id() != 58 ) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.rnbtn, .svg-inline--fa').hide();
        });
        </script>
        <?php
    }
}
add_action( 'admin_footer', 'hide_invoice_options' );


// AJOUT DU LIEN DE FACTURATION DANS LA BARRE DE MENU DU HAUT POUR LES RÔLES COMPTA ET ADMINISTRATEUR
function add_invoice_link_to_admin_bar() {
    if (current_user_can('compta') || current_user_can('administrator')) {
        global $wp_admin_bar;

        $wp_admin_bar->add_menu(array(
            'id'    => 'invoice-link',
            'title' => '<span class="ab-icon dashicons dashicons-calculator"></span> Compta',
            'href'  => admin_url('admin.php?page=PDFInvoiceBuilder%2Fwoocommerce-pdf-invoice.phpmanage_invoices'),
            'meta'  => array(
                'title' => 'Gérer les factures',
            ),
        ));
    }
}
add_action('admin_bar_menu', 'add_invoice_link_to_admin_bar', 100);



// AJOUT DU ROLE COMPTA AU ROLE ADMINISTRATEUR POUR ACCEDER AUX FACTURES SUITE AU CODE MODIFIE DU PLUGIN REDNAO INVOICE

$admins = get_users( array( 'role' => 'administrator' ) );
foreach ( $admins as $admin ) {
    $user = new WP_User( $admin->ID );
    $user->add_role( 'compta' );
}