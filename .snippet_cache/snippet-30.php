<?php
/**
 * Snippet ID: 30
 * Name: MODIFS champs personnalisés Metabox.io pour CPT Cliens Formateurs etc...
 * Description: 
 * @active true
 */

// AJOUTER LIEN SOUS CHAMP AGENDA ET GRILLE TARIFAIRE METABOX URL CPT FORMATEURS

add_action('admin_footer', function () {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Fonction pour ajouter un lien en dessous d'un champ URL
            function addLinkBelowField(fieldId, linkText) {
                var url = $('#' + fieldId).val(); // Récupère la valeur de l'URL
                if(url) {
                    // Ajoute le lien en dessous du champ URL
                    $('#' + fieldId).after('<a href="'+url+'" target="_blank">'+linkText+'</a>');
                }
            }

            // Gérer le champ fsform_url_ggle_agenda
            $('#fsform_url_ggle_agenda').change(function() {
                addLinkBelowField('fsform_url_ggle_agenda', 'Voir l\'agenda');
            });

            // Gérer le champ fsform_url_liengrille
            $('#fsform_url_liengrille').change(function() {
                addLinkBelowField('fsform_url_liengrille', 'Voir la grille');
            });

            // Appeler les fonctions au chargement de la page pour gérer les champs pré-remplis
            addLinkBelowField('fsform_url_ggle_agenda', 'Voir l\'agenda');
            addLinkBelowField('fsform_url_liengrille', 'Voir la grille');
        });
    </script>
    <?php
});


// AFFICHER AGE SOUS CHAMP DATE NAISSANCE CPT FORMATEURS
add_action('admin_footer', function () {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Fonction pour calculer l'âge à partir de l'année
            function calculateAgeFromYear(birthYear) {
                var currentYear = new Date().getFullYear();
                return currentYear - birthYear;
            }

            // Fonction pour mettre à jour l'affichage de l'âge
            function updateAgeDisplay() {
                var birthday = $('#fsform_date_naissance').val();
                if(birthday) {
                    var year = birthday.split('/')[2]; // Extrait l'année
                    var age = calculateAgeFromYear(year);
                    $('.ageDisplay').remove(); // Supprime l'affichage précédent de l'âge

                    // Trouver le label associé et ajouter l'âge
                    $("label[for='fsform_date_naissance']").append('<span class="ageDisplay"> (' + age + ' ans)</span>');
                }
            }

            // Mettre à jour l'âge lors du changement de la date de naissance
            $('#fsform_date_naissance').change(updateAgeDisplay);

            // Mettre à jour l'âge lors du chargement initial de la page
            updateAgeDisplay();
        });
    </script>
    <?php
});