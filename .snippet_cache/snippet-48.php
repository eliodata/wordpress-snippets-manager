<?php
/**
 * Snippet ID: 48
 * Name: FRAIS FOURNISSEURS CASES A COCHER
 * Description: 
 * @active false
 */

add_action('wp_footer', function() {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.reglement-checkbox');
            const totalField = document.getElementById('total-valider-jour');
            const boutonValider = document.getElementById('bouton-valider-sessions');
            
            // Calcul du total des montants cochés
            function calculerTotal() {
                let total = 0;
                checkboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        const montant = parseFloat(checkbox.closest('tr').querySelector('td:first-child').innerText.replace(' €', ''));
                        if (!isNaN(montant)) {
                            total += montant;
                        }
                    }
                });
                totalField.innerText = total.toFixed(2) + ' €';
            }
            
            // Écoute des changements de case à cocher
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', calculerTotal);
            });

            // Bouton "Valider"
            boutonValider.addEventListener('click', function() {
                checkboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        const row = checkbox.closest('tr');
                        const dateCell = row.querySelector('td:last-child');
                        const today = new Date();
                        const formattedDate = today.toLocaleDateString('fr-FR');

                        // Mise à jour de la date
                        dateCell.innerText = formattedDate;

                        // Remplacement de la case à cocher par "Annuler?"
                        const validerCell = row.querySelector('td:nth-child(6)');
                        validerCell.innerHTML = '<input type="checkbox" class="annuler-reglement-checkbox"> Annuler?';
                    }
                });

                // Réinitialisation du total
                totalField.innerText = '0.00 €';
            });
        });
    </script>
    <?php
});
