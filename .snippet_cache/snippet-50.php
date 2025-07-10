<?php
/**
 * Snippet ID: 50
 * Name: FILTRE MOIS ANNEES METABOXES
 * Description: 
 * @active true
 */

// Ajouter la metabox aux CPT "formateur", "salle-de-formation", "client", et "prospect"
add_action('add_meta_boxes', 'add_filters_sidebar_metabox');

function add_filters_sidebar_metabox() {
    add_meta_box(
        'filters_sidebar',
        __('Filtres (Mois / Année)', 'text-domain'),
        'render_filters_sidebar_metabox',
        ['formateur', 'salle-de-formation', 'client', 'prospect'], // Appliqué aux quatre CPT
        'side',
        'low'
    );
}

function render_filters_sidebar_metabox($post) {
    // Générer les mois et années
    $mois_francais = [
        '01' => 'Janvier', '02' => 'Février', '03' => 'Mars',
        '04' => 'Avril', '05' => 'Mai', '06' => 'Juin',
        '07' => 'Juillet', '08' => 'Août', '09' => 'Septembre',
        '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
    ];

    $annee_courante = date('Y');
    $trois_derniers_mois = [];
    for ($i = 2; $i >= 0; $i--) {
        $trois_derniers_mois[] = date('m', strtotime("-{$i} months"));
    }

    // Section mois
    echo '<div style="margin-bottom: 15px;">';
    echo '<strong>' . __('Mois :', 'text-domain') . '</strong><br>';
    echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 5px;">';
    foreach ($mois_francais as $value => $mois) {
        $checked = in_array($value, $trois_derniers_mois) ? 'checked' : '';
        echo '<label style="margin-right: 10px;">
                <input type="checkbox" class="filtre-mois" value="' . esc_attr($value) . '" ' . $checked . '> ' . esc_html($mois) . '
              </label>';
    }
    echo '</div>';
    echo '</div>';

    // Section années
    echo '<div style="margin-bottom: 15px;">';
    echo '<strong>' . __('Années :', 'text-domain') . '</strong><br>';
    echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 5px;">';
    for ($y = $annee_courante - 5; $y <= $annee_courante + 1; $y++) {
        $checked = ($y == $annee_courante) ? 'checked' : '';
        echo '<label style="margin-right: 10px;">
                <input type="checkbox" class="filtre-annee" value="' . esc_attr($y) . '" ' . $checked . '> ' . esc_html($y) . '
              </label>';
    }
    echo '</div>';
    echo '</div>';

    // Bouton "Toutes les périodes"
    echo '<div style="margin-top: 15px; text-align: center;">';
    echo '<style>
        #reset-filters-sidebar {
            padding: 4px 8px;
            color: #0073aa;
            border: 1px solid #0073aa;
            border-radius: 4px;
            cursor: pointer;
            background: none;
        }
        #reset-filters-sidebar:hover {
            color: #fff;
            border: 1px solid #0073aa;
            background: #0073aa;
        }
    </style>';
    echo '<button id="reset-filters-sidebar" type="button">' . __('Toutes les périodes', 'text-domain') . '</button>';
    echo '</div>';

    // Ajouter le script pour gérer le bouton "Toutes les périodes" et les filtres de mois/année
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            const resetButton = document.getElementById('reset-filters-sidebar');
            const moisCheckboxes = document.querySelectorAll('.filtre-mois');
            const anneeCheckboxes = document.querySelectorAll('.filtre-annee');

            // Fonction pour réinitialiser les filtres
            resetButton.addEventListener('click', function() {
                moisCheckboxes.forEach(checkbox => checkbox.checked = false);
                anneeCheckboxes.forEach(checkbox => checkbox.checked = false);

                // Déclenche le filtrage
                filterFinancialSummarySidebar();
                filterReglementsDetailsSidebar();
                filterActionsTable(); // Ajout si vous avez d'autres tables à filtrer
            });

            // Filtrage basé sur mois et année
            const filtreMoisAnnee = () => {
                filterFinancialSummarySidebar();
                filterReglementsDetailsSidebar();
                filterActionsTable(); // Ajout si vous avez d'autres tables à filtrer
            };

            moisCheckboxes.forEach(checkbox => checkbox.addEventListener('change', filtreMoisAnnee));
            anneeCheckboxes.forEach(checkbox => checkbox.addEventListener('change', filtreMoisAnnee));

            // Fonction de filtrage pour le Résumé Financier
            function filterFinancialSummarySidebar() {
                const selectedMois = Array.from(moisCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
                const selectedAnnees = Array.from(anneeCheckboxes).filter(cb => cb.checked).map(cb => cb.value);

                const resumeTable = document.querySelector('.resume-financier-table');
                if (!resumeTable) return;

                const rows = resumeTable.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const dateCell = row.querySelector('td.date-debut'); // Assurez-vous que la cellule date a une classe spécifique
                    if (!dateCell) {
                        row.style.display = 'none';
                        return;
                    }

                    const dateText = dateCell.textContent.trim();
                    if (dateText === '-') {
                        row.style.display = 'none';
                        return;
                    }

                    const dateParts = dateText.split('/');
                    if (dateParts.length !== 3) {
                        row.style.display = 'none';
                        return;
                    }

                    const mois = dateParts[1];
                    const annee = dateParts[2];

                    const moisMatch = selectedMois.length === 0 || selectedMois.includes(mois);
                    const anneeMatch = selectedAnnees.length === 0 || selectedAnnees.includes(annee);

                    if (moisMatch && anneeMatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            // Fonction de filtrage pour les Détails des Règlements
            function filterReglementsDetailsSidebar() {
                const selectedMois = Array.from(moisCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
                const selectedAnnees = Array.from(anneeCheckboxes).filter(cb => cb.checked).map(cb => cb.value);

                const reglementsTable = document.querySelector('.reglements-table tbody');
                if (!reglementsTable) return;
                const rows = reglementsTable.querySelectorAll('tr');

                rows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    const dateCell = cells[0].textContent.trim();
                    if (dateCell === '-') {
                        row.style.display = 'none';
                        return;
                    }
                    const dateParts = dateCell.split('/');
                    if (dateParts.length !== 3) {
                        row.style.display = 'none';
                        return;
                    }
                    const mois = dateParts[1];
                    const annee = dateParts[2];

                    const moisMatch = selectedMois.length === 0 || selectedMois.includes(mois);
                    const anneeMatch = selectedAnnees.length === 0 || selectedAnnees.includes(annee);

                    if (moisMatch && anneeMatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            // Fonction de filtrage pour d'autres tables (exemple: Actions)
            function filterActionsTable() {
                const selectedMois = Array.from(moisCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
                const selectedAnnees = Array.from(anneeCheckboxes).filter(cb => cb.checked).map(cb => cb.value);

                const actionsTable = document.querySelector('#actions-table tbody');
                if (!actionsTable) return;
                const rows = actionsTable.querySelectorAll('tr');

                rows.forEach(row => {
                    const startDateCell = row.querySelector('td:nth-child(2)');
                    if (!startDateCell) {
                        row.style.display = 'none';
                        return;
                    }

                    const [day, month, year] = startDateCell.textContent.split('/');
                    const moisMatch = selectedMois.length === 0 || selectedMois.includes(month);
                    const anneeMatch = selectedAnnees.length === 0 || selectedAnnees.includes(year);

                    row.style.display = (moisMatch && anneeMatch) ? '' : 'none';
                });
            }

            // Appliquer le filtrage initial
            filtreMoisAnnee();
        });
    </script>";

    // Explication
    echo '<p style="font-size: 12px; color: #666;">' . __('Cochez la période désirée : mois, années, ou combinaison des deux. Si aucune case n\'est cochée, toutes les périodes seront affichées.', 'text-domain') . '</p>';
}
