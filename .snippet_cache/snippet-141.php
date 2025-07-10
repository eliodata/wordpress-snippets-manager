<?php
/**
 * Snippet ID: 141
 * Name: conditionner affichage nom société et siret pendant commande selon choix select type client
 * Description: 
 * @active true
 */

// Ajouter le script personnalisé pour gérer l'affichage conditionnel des champs
function custom_woocommerce_scripts() {
    if (is_checkout()) {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Fonction pour gérer l'affichage des champs
                function toggleFields() {
                    var typeClient = $('#billing_form_typeclient').val();

                    // Afficher ou masquer le champ billing_company
                    if (typeClient === '1') {
                        $('#billing_company_field').show();
                        $('#billing_company_field').find('input').prop('required', true);
                    } else {
                        $('#billing_company_field').hide();
                        $('#billing_company_field').find('input').prop('required', false);
                    }

                    // Afficher ou masquer le champ fsbdd_text_siret
                    if (typeClient === '1' || typeClient === '2') {
                        $('#fsbdd_text_siret_field').show();
                        $('#fsbdd_text_siret_field').find('input').prop('required', true);
                    } else {
                        $('#fsbdd_text_siret_field').hide();
                        $('#fsbdd_text_siret_field').find('input').prop('required', false);
                    }

                    // Afficher ou masquer le champ billing_form_fonction
                    if (typeClient === '1') {
                        $('#billing_form_fonction_field').show();
                        $('#billing_form_fonction_field').find('input').prop('required', true);
                    } else {
                        $('#billing_form_fonction_field').hide();
                        $('#billing_form_fonction_field').find('input').prop('required', false);
                    }

                    // Afficher ou masquer le champ fsbdd_check_exotva
                    if (typeClient === '1' || typeClient === '2') {
                        $('#fsbdd_check_exotva_field').show();
                        $('#fsbdd_check_exotva_field').find('input').prop('required', true);
                    } else {
                        $('#fsbdd_check_exotva_field').hide();
                        $('#fsbdd_check_exotva_field').find('input').prop('required', false);
                    }
                }

                // Appeler la fonction au chargement de la page
                toggleFields();

                // Appeler la fonction lorsque la valeur du champ change
                $('#billing_form_typeclient').change(function() {
                    toggleFields();
                });
            });
        </script>
        <?php
    }
}
add_action('wp_footer', 'custom_woocommerce_scripts');