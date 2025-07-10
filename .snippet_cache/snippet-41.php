<?php
/**
 * Snippet ID: 41
 * Name: METABOX CHARGES FRAIS FORMATION BDD formateurs et fournisseurs V2
 * Description: 
 * @active true
 */

/**
 * Plugin Name: Charges Fournisseur
 * Description: Gestion des charges fournisseurs avec validation par session.
 * Version: 1.3
 * Author: Votre Nom
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Ajouter la metabox des charges aux CPT concernés
 */
add_action('add_meta_boxes', function() {
    $cpt_list = ['formateur', 'formateur-passe', 'salle-de-formation'];
    foreach ($cpt_list as $cpt) {
        add_meta_box(
            'charges_fournisseur',
            'CHARGES',
            'afficher_charges_fournisseur',
            $cpt,
            'normal',
            'high'
        );
    }
});

/**
 * Afficher le contenu de la metabox des charges.
 *
 * @param WP_Post $post
 */
function afficher_charges_fournisseur($post) {
    if (!current_user_can('administrator')) {
        return;
    }

    // Ajout d'un nonce
    wp_nonce_field('charges_fournisseur_save', 'charges_fournisseur_nonce');

    // Récupérer les règlements pour calculer le solde
    $reglements = get_post_meta($post->ID, 'fsbdd_reglements', true) ?: [];
    $reglements_totals = [];
    foreach ($reglements as $reglement) {
        if (isset($reglement['sessions'], $reglement['montants_par_session']) && is_array($reglement['sessions']) && is_array($reglement['montants_par_session'])) {
            foreach ($reglement['sessions'] as $session_id) {
                if (!isset($reglements_totals[$session_id])) {
                    $reglements_totals[$session_id] = 0;
                }
                $assigned_amount = isset($reglement['montants_par_session'][$session_id]) ? floatval($reglement['montants_par_session'][$session_id]) : 0;
                $reglements_totals[$session_id] += $assigned_amount;
            }
        }
    }

    echo '<div id="charges_fournisseur">'; // Conteneur de la metabox CHARGES
    echo '<table id="charges-table" style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">
            <thead style="background-color: #f0f0f0;">
                <tr>
                    <th style="border: 1px solid #ddd; padding: 8px; width: 8%;">Action</th>
                    <th style="border: 1px solid #ddd; padding: 8px; width: 11%;">Coût formation</th>
                    <th style="border: 1px solid #ddd; padding: 8px; width: 11%;">Frais annexes</th>
                    <th style="border: 1px solid #ddd; padding: 8px; width: 10%;">Coût global</th>
                    <th style="border: 1px solid #ddd; padding: 8px; width: 10%;">Solde</th>
                    <th style="border: 1px solid #ddd; padding: 8px; width: 24%;">Infos</th>
                    <th style="border: 1px solid #ddd; padding: 8px; width: 9%;" class="date-column">Date</th>
                    <th style="border: 1px solid #ddd; padding: 8px; width: 10%;">Soldée</th>
                    <th style="border: 1px solid #ddd; padding: 8px; width: 9%;">le</th>
                </tr>
            </thead>
            <tbody>';

    // Récupérer toutes les actions
    $actions = get_posts([
        'post_type' => 'action-de-formation',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'post__not_in' => [268081],
    ]);

foreach ($actions as $action) {
    $action_id = $action->ID;
    $titre_action = get_the_title($action_id);
    $date_action_raw = get_post_meta($action_id, 'we_startdate', true);
    $date_action = !empty($date_action_raw) ? date('d/m/Y', intval($date_action_raw)) : '';
    $group_charges = get_post_meta($action_id, 'fsbdd_grpctsformation', true);

    if (is_array($group_charges)) {
        // Calculer le coût total de cette action pour ce fournisseur
        $cout_total_action = 0;
        $charges_action = [];
        
        foreach ($group_charges as $index => $charge) {
            $charge_type = $charge['fsbdd_typechargedue'];
            
            // Vérifier si la charge appartient au CPT actuel
            if (
                ($charge_type === '1' && isset($charge['fsbdd_selectcoutform']) && intval($charge['fsbdd_selectcoutform']) === $post->ID) ||
                ($charge_type === '2' && isset($charge['fsbdd_selectctfourn']) && intval($charge['fsbdd_selectctfourn']) === $post->ID)
            ) {
                $cout_global = floatval($charge['fsbdd_montrechrge'] ?? 0);
                $cout_total_action += $cout_global;
                $charges_action[] = array_merge($charge, ['index' => $index]);
            }
        }
        
        // Calculer le règlement total pour cette action
        $total_reglements_action = isset($reglements_totals[$action_id]) ? $reglements_totals[$action_id] : 0;
        
        // Calculer le solde global pour cette action
        $solde_global_action = $total_reglements_action - $cout_total_action;
        
        // Afficher chaque charge avec le solde réparti proportionnellement
        foreach ($charges_action as $charge_data) {
            $charge = $charge_data;
            $index = $charge_data['index'];
            
            $date_validation = $charge['fsbdd_daterchrge'] ?? '';
            $cout_formation = floatval($charge['fsbdd_ttcout_journalier'] ?? 0) + floatval($charge['fsbdd_ttcout_demijournalier'] ?? 0);
            $frais_annexes = floatval($charge['fsbdd_ttfraismission'] ?? 0);
            $cout_global = floatval($charge['fsbdd_montrechrge'] ?? 0);
            $details = ($charge['fsbdd_infoschargedue'] ?? '') . ' | ' . ($charge['fsbdd_infosfraisannex'] ?? '');

            // Calculer le solde proportionnel pour cette charge
            if ($cout_total_action > 0) {
                $proportion = $cout_global / $cout_total_action;
                $solde = $solde_global_action * $proportion;
            } else {
                $solde = 0;
            }
            
            $solde_formatted = number_format($solde, 2) . ' €';
            $solde_color = $solde < 0 ? 'red' : ($solde == 0 ? 'green' : 'black');

            echo '<tr>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">
                    <a href="' . esc_url(get_edit_post_link($action_id)) . '" target="_blank">' . esc_html($titre_action) . '</a>
                  </td>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html(number_format($cout_formation, 2)) . ' €</td>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html(number_format($frais_annexes, 2)) . ' €</td>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html(number_format($cout_global, 2)) . ' €</td>';
            echo '<td style="border: 1px solid #ddd; padding: 8px; color: ' . esc_attr($solde_color) . ';">' . esc_html($solde_formatted) . '</td>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($details) . '</td>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;" class="date-column">' . esc_html($date_action) . '</td>';
            echo '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">';
            if (empty($date_validation)) {
                echo '<input type="checkbox" name="reglement[' . esc_attr($action_id) . '][' . esc_attr($index) . ']" value="1">';
            } else {
                echo '<input type="checkbox" name="annuler_reglement[' . esc_attr($action_id) . '][' . esc_attr($index) . ']" value="1"> Annuler?';
            }
            echo '</td>';
            echo '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . esc_html($date_validation) . '</td>';
            echo '</tr>';
        }
    }
}

    echo '</tbody>
        </table>';

    // Section montants de sélection et validés
    echo '<div style="margin-top: 5px; font-weight: bold;">
            <span id="total-valider-label">Montant total de la sélection : 0 €</span>
          </div>';

    echo '<span id="total-valide">Montant total charges validées : 0 €</span>';

    // Style pour le titre de la metabox CHARGES
    echo '<style>
        #charges_fournisseur .hndle span {
            display: inline-block;
            width: 40%;
        }
    </style>';

    // Script pour le calcul initial des totaux CHARGES
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const metabox = document.getElementById("charges_fournisseur");
            const title = metabox.querySelector(".hndle");
            const rows = metabox.querySelectorAll("#charges-table tbody tr");

            let totalCharges = 0;
            let totalSolde = 0; // Nouveau calcul du total Solde

            rows.forEach(row => {
                const coutGlobalCell = row.querySelector("td:nth-child(4)");
                const soldeCell = row.querySelector("td:nth-child(5)");

                const coutGlobal = parseFloat(coutGlobalCell.textContent.replace("€", "").trim()) || 0;
                const solde = parseFloat(soldeCell.textContent.replace("€", "").trim()) || 0;

                totalCharges += coutGlobal;
                totalSolde += solde; 
            });

            // Afficher Total charges et Solde
            if (title) {
                title.innerHTML = "CHARGES <span>Total charges : " + totalCharges.toFixed(2) + " € </span>" +
                                  "<span style=\\"color: " + (totalSolde >= 0 ? "green" : "red") + "; margin-left: 10px;\\"> Solde : " + totalSolde.toFixed(2) + " € </span>";
            }
        });
    </script>';

    // Script pour recalculer lors de la sélection/désélection des cases
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const metabox = document.getElementById("charges_fournisseur");
            const title = metabox.querySelector(".hndle");
            const checkboxes = metabox.querySelectorAll("#charges-table input[type=\'checkbox\']");
            const totalLabel = document.getElementById("total-valider-label");
            const totalValideSpan = document.getElementById("total-valide");
            const rows = metabox.querySelectorAll("#charges-table tbody tr");

            function calculerTotaux() {
                let totalSelection = 0;
                let totalValide = 0;
                let totalCharges = 0;
                let totalSolde = 0; // On recalcule Solde global à chaque fois

                rows.forEach(row => {
                    const coutGlobalCell = row.querySelector("td:nth-child(4)");
                    const soldeCell = row.querySelector("td:nth-child(5)");
                    const validationCell = row.querySelector("td:nth-child(9)");
                    const checkbox = row.querySelector("input[type=\'checkbox\']");

                    const coutGlobal = parseFloat(coutGlobalCell.textContent.replace("€", "").trim()) || 0;
                    const solde = parseFloat(soldeCell.textContent.replace("€", "").trim()) || 0;

                    totalCharges += coutGlobal;
                    totalSolde += solde;

                    if (checkbox && checkbox.checked) {
                        totalSelection += coutGlobal;
                    }

                    if (validationCell && validationCell.textContent.trim() !== "") {
                        totalValide += coutGlobal;
                    }
                });

                if (totalLabel) {
                    totalLabel.textContent = "Montant total de la sélection : " + totalSelection.toFixed(2) + " €";
                }
                if (totalValideSpan) {
                    totalValideSpan.textContent = "Montant total charges validées : " + totalValide.toFixed(2) + " €";
                }

                if (title) {
                    title.innerHTML = "CHARGES <span style=\\"font-weight: 300; color: #0073aa;\\">Total charges : " + totalCharges.toFixed(2) + " €</span>" +
                                      "<span style=\\"font-weight: 300; color: " + (totalSolde >= 0 ? "green" : "red") + "; margin-left: 10px;\\">Solde : " + totalSolde.toFixed(2) + " €</span>";
                }
            }

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener("change", calculerTotaux);
            });

            calculerTotaux();
        });
    </script>';

    echo '</div>'; // fin du conteneur CHARGES
}

/**
 * Sauvegarder les charges, gérer les validations et annulations.
 */
add_action('save_post', function ($post_id) {
    if (!isset($_POST['charges_fournisseur_nonce']) || !wp_verify_nonce($_POST['charges_fournisseur_nonce'], 'charges_fournisseur_save')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $cpt_list = ['formateur', 'formateur-passe', 'salle-de-formation'];
    if (!in_array(get_post_type($post_id), $cpt_list)) {
        return;
    }

    // Gestion des validations
    if (isset($_POST['reglement']) && is_array($_POST['reglement'])) {
        foreach ($_POST['reglement'] as $action_id => $charges) {
            foreach ($charges as $index => $value) {
                if ($value == '1') {
                    $group_charges = get_post_meta($action_id, 'fsbdd_grpctsformation', true);
                    if (isset($group_charges[$index])) {
                        $today = date('d/m/Y', current_time('timestamp'));
                        $group_charges[$index]['fsbdd_daterchrge'] = $today;
                        update_post_meta($action_id, 'fsbdd_grpctsformation', $group_charges);
                    }
                }
            }
        }
    }

    // Gestion des annulations
    if (isset($_POST['annuler_reglement']) && is_array($_POST['annuler_reglement'])) {
        foreach ($_POST['annuler_reglement'] as $action_id => $charges) {
            foreach ($charges as $index => $value) {
                if ($value == '1') {
                    $group_charges = get_post_meta($action_id, 'fsbdd_grpctsformation', true);
                    if (isset($group_charges[$index])) {
                        $group_charges[$index]['fsbdd_daterchrge'] = '';
                        update_post_meta($action_id, 'fsbdd_grpctsformation', $group_charges);
                    }
                }
            }
        }
    }

    // Recalculer les totaux (logiciel identique à l'original)
    $total_montants = 0;
    $actions = get_posts([
        'post_type' => 'action-de-formation',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'post__not_in' => [268081],
    ]);

    foreach ($actions as $action) {
        $group_charges = get_post_meta($action->ID, 'fsbdd_grpctsformation', true);
        if (is_array($group_charges)) {
            foreach ($group_charges as $charge) {
                if (
                    (isset($charge['fsbdd_selectcoutform']) && intval($charge['fsbdd_selectcoutform']) === $post_id) ||
                    (isset($charge['fsbdd_selectctfourn']) && intval($charge['fsbdd_selectctfourn']) === $post_id)
                ) {
                    $montant_formateur = floatval($charge['fsbdd_montrechrge'] ?? 0) - floatval($charge['fsbdd_typechrgfrmiss'] ?? 0);
                    $montant_frais_annexes = floatval($charge['fsbdd_typechrgfrmiss'] ?? 0);

                    $total_montants += $montant_formateur + $montant_frais_annexes;
                }
            }
        }
    }

    update_post_meta($post_id, 'fsbdd_total_montants', $total_montants);

    // Calcul du montant total des charges validées
    $total_charges_validees = 0;

    foreach ($actions as $action) {
        $group_charges = get_post_meta($action->ID, 'fsbdd_grpctsformation', true);
        if (is_array($group_charges)) {
            foreach ($group_charges as $charge) {
                if (
                    !empty($charge['fsbdd_daterchrge']) &&
                    (
                        (isset($charge['fsbdd_selectcoutform']) && intval($charge['fsbdd_selectcoutform']) === $post_id) ||
                        (isset($charge['fsbdd_selectctfourn']) && intval($charge['fsbdd_selectctfourn']) === $post_id)
                    )
                ) {
                    $montant = floatval($charge['fsbdd_montrechrge'] ?? 0);
                    $total_charges_validees += $montant;
                }
            }
        }
    }

    update_post_meta($post_id, 'fsbdd_total_charges_validees', $total_charges_validees);

    // Appeler la fonction de mise à jour globale si nécessaire
    maj_reglements_et_charges($post_id);
});
