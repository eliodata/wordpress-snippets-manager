<?php
/**
 * Snippet ID: 49
 * Name: METABOX REGLEMENTS BDD formateurs et fournisseurs
 * Description: 
 * @active true
 */

/**
 * Plugin Name: Réglements Fournisseur
 * Description: Gestion des règlements fournisseurs avec ventilation par session et exclusion des sessions déjà validées.
 * Version: 1.6
 * Author: Votre Nom
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Calcule le restant à payer pour une action donnée et un fournisseur donné.
 *
 * @param int $action_id
 * @param int $fournisseur_id
 * @return float
 */
function fs_calcul_restant_a_payer($action_id, $fournisseur_id) {
    $group_charges = get_post_meta($action_id, 'fsbdd_grpctsformation', true);
    $cout_global   = 0.0;
    $total_regle   = 0.0;

    // 1) Somme du coût global pour ce fournisseur
    if (is_array($group_charges)) {
        foreach ($group_charges as $charge) {
            // Vérifie que la charge appartient à ce fournisseur
            $belongsToFournisseur = (
                (isset($charge['fsbdd_selectcoutform']) && intval($charge['fsbdd_selectcoutform']) === intval($fournisseur_id)) ||
                (isset($charge['fsbdd_selectctfourn'])  && intval($charge['fsbdd_selectctfourn'])  === intval($fournisseur_id))
            );
            if ($belongsToFournisseur) {
                $montrechrge = floatval($charge['fsbdd_montrechrge'] ?? 0);
                $cout_global += $montrechrge;
            }
        }
    }

    // 2) Somme déjà réglée pour cette action
    $reglements = get_post_meta($fournisseur_id, 'fsbdd_reglements', true) ?: [];
    if (!empty($reglements)) {
        foreach ($reglements as $r) {
            // Vérifie si cette action fait partie des sessions payées
            if (!empty($r['sessions']) && in_array($action_id, $r['sessions'])) {
                $montantAffecte = isset($r['montants_par_session'][$action_id]) ? floatval($r['montants_par_session'][$action_id]) : 0.0;
                $total_regle += $montantAffecte;
            }
        }
    }

    // Retourne le max entre le calcul et 0 pour éviter un résultat négatif
    return max(0, $cout_global - $total_regle);
}

/**
 * Ajouter la metabox des règlements aux CPT concernés.
 */
add_action('add_meta_boxes', function() {
    $cpt_list = ['formateur', 'formateur-passe', 'salle-de-formation'];
    foreach ($cpt_list as $cpt) {
        add_meta_box(
            'reglements_fournisseur',
            'RÈGLEMENTS',
            'afficher_reglements_fournisseur',
            $cpt,
            'normal',
            'high',
            [
                '__block_editor_compatible_meta_box' => true,
                '__back_compat_meta_box' => true,
                'class' => 'closed'
            ]
        );
    }
});

/**
 * Afficher le contenu de la metabox des règlements.
 *
 * @param WP_Post $post L'objet post actuel.
 */
function afficher_reglements_fournisseur($post) {
    if (!current_user_can('administrator')) {
        return;
    }

    // Nonce pour sécuriser les données
    wp_nonce_field('reglements_fournisseur_save', 'reglements_fournisseur_nonce');

    // Récupérer les règlements existants
    $reglements = get_post_meta($post->ID, 'fsbdd_reglements', true) ?: [];
    $total_regle = array_sum(array_column($reglements, 'montant'));

    // Récupérer toutes les actions de formation, en excluant l'ID 268081
    $all_actions = get_posts([
        'post_type'   => 'action-de-formation',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'post__not_in' => [268081],
    ]);

    // Filtrer les actions associées au CPT actuel et non validées dans les charges
    $cpt = get_post_type($post->ID);
    $actions = [];

foreach ($all_actions as $action) {
    $group_charges = get_post_meta($action->ID, 'fsbdd_grpctsformation', true);
    if (is_array($group_charges)) {
        $is_validated = false;
        $has_charge_for_this_cpt = false;
        
        foreach ($group_charges as $charge) {
            $charge_type = $charge['fsbdd_typechargedue'] ?? '';
            
            // Vérifier si cette charge appartient au CPT actuel (même logique que dans le plugin charges)
            $belongs_to_current_cpt = (
                ($charge_type === '1' && isset($charge['fsbdd_selectcoutform']) && intval($charge['fsbdd_selectcoutform']) === $post->ID) ||
                ($charge_type === '2' && isset($charge['fsbdd_selectctfourn']) && intval($charge['fsbdd_selectctfourn']) === $post->ID)
            );
            
            if ($belongs_to_current_cpt) {
                $has_charge_for_this_cpt = true;
                
                // Vérifier si cette charge est validée (fsbdd_daterchrge non vide)
                if (!empty($charge['fsbdd_daterchrge'])) {
                    $is_validated = true;
                    break;
                }
            }
        }

        // Ajouter l'action si elle a des charges pour ce CPT et qu'aucune charge n'est validée
        if ($has_charge_for_this_cpt && !$is_validated) {
            $actions[$action->ID] = $action->post_title;
        }
    }
}

    // Injection du titre dynamique avec seulement le Total réglé
    echo '<script>
        document.addEventListener("DOMContentLoaded", function () {
            const metabox = document.getElementById("reglements_fournisseur");
            if (metabox) {
                const title = metabox.querySelector(".hndle");
                if (title) {
                    title.innerHTML = "RÈGLEMENTS <span style=\"width: 40%; font-weight: 300; color: #0073aa;\"> Total réglé : ' . esc_js(number_format($total_regle, 2)) . ' € </span>";
                }
            }
        });
    </script>';

    // Afficher les règlements existants
    echo '<table id="reglements-table" style="width: 100%; border-collapse: collapse; border: 1px solid #ddd; margin-bottom: 20px;">
            <thead style="background-color: #f0f0f0;">
                <tr>
                    <th style="border: 1px solid #ddd; padding: 8px; width: 10%;">Montant</th>
                    <th style="border: 1px solid #ddd; padding: 8px; width: 15%;">Date</th>
                    <th style="border: 1px solid #ddd; padding: 8px; width: 40%;">Actions</th>
                    <th style="border: 1px solid #ddd; padding: 8px; width: 25%;">Infos</th>
                    <th style="border: 1px solid #ddd; padding: 8px; width: 10%;">Annuler</th>
                </tr>
            </thead>
            <tbody>';

    if (!empty($reglements)) {
        foreach ($reglements as $index => $reglement) {
            $montant = esc_html(number_format($reglement['montant'], 2));
            $date = esc_html(date('d/m/Y', strtotime($reglement['date'] ?? '')));
            $details = esc_html($reglement['details'] ?? '');
            $actions_list = [];

            if (isset($reglement['sessions']) && is_array($reglement['sessions']) && isset($reglement['montants_par_session'])) {
                foreach ($reglement['sessions'] as $session_id) {
                    $session_title = get_the_title($session_id);
                    $montants_par_session = $reglement['montants_par_session'];
                    $montant_session = isset($montants_par_session[$session_id]) ? floatval($montants_par_session[$session_id]) : 0;

                    // N'afficher que si > 0
                    if ($montant_session > 0) {
                        $action_link = '<a href="post.php?post=' . intval($session_id) . '&action=edit" target="_blank">' 
                                        . esc_html($session_title) 
                                        . '</a>';
                        $actions_list[] = $action_link . ' ' . number_format($montant_session, 2) . '€';
                    }
                }
            } 
            // Si aucun règlement non nul, on met "N/A"
            $sessions_str = (!empty($actions_list)) ? implode(' | ', $actions_list) : 'N/A';

            echo '<tr>';
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$montant} €</td>";
            echo "<td class='reglement-date' style='border: 1px solid #ddd; padding: 8px;'>{$date}</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$sessions_str}</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$details}</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'>
                    <input type='checkbox' name='annuler_reglement[{$index}]' value='1'>
                  </td>";
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5" style="text-align: center; padding: 8px;">Aucune action réglée</td></tr>';
    }

    echo '</tbody>
        </table>';

    // Formulaire pour ajouter un nouveau règlement
    echo '<form method="post">';
    echo '<div style="margin-top: 10px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <label>Montant total : </label>
            <input type="number" step="0.01" name="new-montant-total" id="new-montant-total" style="width: 100px;" readonly>
            
            <label>Date : </label>
            <input type="date" name="new-date" required style="margin-right: 10px;">

            <label>Détails : </label>
            <input type="text" name="new-details" required style="margin-right: 10px; width: 200px;">

            <input type="submit" name="ajouter_reglement" value="Ajouter Règlement" class="button button-primary">
          </div>';

    if (!empty($actions)) {
        echo '<div style="margin-top: 10px;">
                <label>Actions non soldées :</label>
                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 5px;">';

        foreach ($actions as $action_id => $action_title) {
            // Calcul du restant à payer
            $restant = fs_calcul_restant_a_payer($action_id, $post->ID);

            echo '<div style="display: flex; align-items: center;">
                    <!-- Case pour auto-remplir le montant restant -->
                    <input type="checkbox"
                           class="auto-fill-restant"
                           data-target="session_' . esc_attr($action_id) . '"
                           data-restant="' . esc_attr($restant) . '"
                           style="margin-right:5px;" />

                    <label for="session_' . esc_attr($action_id) . '" style="margin-right: 5px;">' 
                        . esc_html($action_title) . ' :</label>
                    <input type="number" step="0.01" 
                           name="new-sessions-montants[' . esc_attr($action_id) . ']" 
                           id="session_' . esc_attr($action_id) . '" 
                           class="session-montant" 
                           style="width: 100px;">
                  </div>';
        }
        echo '</div>
              </div>';
    } else {
        echo '<p>Aucune action disponible pour ce CPT.</p>';
    }

    echo '</form>';

    // Script pour filtrer le tableau par mois et année (inchangé)
    echo '<script>
        document.addEventListener("DOMContentLoaded", function () {
            const moisCheckboxes = document.querySelectorAll(".filtre-mois");
            const anneeCheckboxes = document.querySelectorAll(".filtre-annee");
            const rows = document.querySelectorAll("#reglements-table tbody tr");
            const resetButton = document.getElementById("reset-filters-sidebar");

            function filterTable() {
                const selectedMonths = Array.from(moisCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
                const selectedYears = Array.from(anneeCheckboxes).filter(cb => cb.checked).map(cb => cb.value);

                rows.forEach(row => {
                    const dateCell = row.querySelector(".reglement-date");
                    if (!dateCell) return;

                    const [day, month, year] = dateCell.textContent.split("/");
                    const monthMatch = selectedMonths.length === 0 || selectedMonths.includes(month);
                    const yearMatch = selectedYears.length === 0 || selectedYears.includes(year);

                    row.style.display = (monthMatch && yearMatch) ? "" : "none";
                });
            }

            if (resetButton) {
                resetButton.addEventListener("click", function() {
                    moisCheckboxes.forEach(cb => cb.checked = false);
                    anneeCheckboxes.forEach(cb => cb.checked = false);
                    rows.forEach(row => row.style.display = "");
                });
            }

            moisCheckboxes.forEach(cb => cb.addEventListener("change", filterTable));
            anneeCheckboxes.forEach(cb => cb.addEventListener("change", filterTable));

            filterTable();
        });
    </script>';

    // Script pour calculer le montant total en fonction des sessions
    echo '<script>
        document.addEventListener("DOMContentLoaded", function () {
            const montantTotalField = document.getElementById("new-montant-total");
            const sessionMontantFields = document.querySelectorAll(".session-montant");

            function updateMontantTotal() {
                let total = 0;
                sessionMontantFields.forEach(field => {
                    const value = parseFloat(field.value) || 0;
                    total += value;
                });
                montantTotalField.value = total.toFixed(2);
            }

            sessionMontantFields.forEach(field => {
                field.addEventListener("input", updateMontantTotal);
            });

            updateMontantTotal();
        });
    </script>';

    // Script pour auto-remplir le champ quand la case est cochée
    echo '<script>
        document.addEventListener("DOMContentLoaded", function () {
            const checkboxAutoFill = document.querySelectorAll(".auto-fill-restant");

            checkboxAutoFill.forEach(chk => {
                chk.addEventListener("change", function() {
                    const targetId = chk.getAttribute("data-target");
                    const restant = parseFloat(chk.getAttribute("data-restant")) || 0;
                    const inputEl = document.getElementById(targetId);
                    if (!inputEl) return;

                    if (chk.checked) {
                        // On remplit le champ avec le restant à payer
                        inputEl.value = restant;
                    } else {
                        // On réinitialise le champ
                        inputEl.value = "";
                    }
                    // Déclenche l\'événement "input" pour mettre à jour le total
                    inputEl.dispatchEvent(new Event("input"));
                });
            });
        });
    </script>';
}

/**
 * Sauvegarder les règlements avec association aux sessions.
 *
 * @param int $post_id ID du post en cours de sauvegarde.
 */
add_action('save_post', function ($post_id) {
    if (!isset($_POST['reglements_fournisseur_nonce']) || !wp_verify_nonce($_POST['reglements_fournisseur_nonce'], 'reglements_fournisseur_save')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $cpt_list = ['formateur', 'formateur-passe', 'salle-de-formation'];
    if (!in_array(get_post_type($post_id), $cpt_list)) {
        return;
    }

    // Annuler les règlements
    if (isset($_POST['annuler_reglement']) && is_array($_POST['annuler_reglement'])) {
        $reglements = get_post_meta($post_id, 'fsbdd_reglements', true) ?: [];
        foreach ($_POST['annuler_reglement'] as $index => $value) {
            if ($value == '1') {
                unset($reglements[$index]);
            }
        }
        update_post_meta($post_id, 'fsbdd_reglements', $reglements);
    }

    // Ajouter un nouveau règlement avec montants par session
    if (isset($_POST['ajouter_reglement']) && 
        isset($_POST['new-montant-total']) && 
        isset($_POST['new-date']) && 
        isset($_POST['new-details']) && 
        isset($_POST['new-sessions-montants']) && 
        is_array($_POST['new-sessions-montants'])) {
        
        $montant_total = floatval($_POST['new-montant-total']);
        $date = sanitize_text_field($_POST['new-date']);
        $details = sanitize_text_field($_POST['new-details']);
        $sessions_montants = array_map('floatval', $_POST['new-sessions-montants']);
        $sessions = array_keys($_POST['new-sessions-montants']);
        
        $calculated_total = array_sum($sessions_montants);
        if (abs($calculated_total - $montant_total) > 0.01) {
            $montant_total = $calculated_total;
        }

        $new_reglement = [
            'montant'             => $montant_total,
            'date'                => $date,
            'details'             => $details,
            'sessions'            => $sessions,
            'montants_par_session'=> $sessions_montants,
        ];

        $reglements = get_post_meta($post_id, 'fsbdd_reglements', true) ?: [];
        $reglements[] = $new_reglement;
        update_post_meta($post_id, 'fsbdd_reglements', $reglements);
    }

    // Recalculer les totaux
    $reglements = get_post_meta($post_id, 'fsbdd_reglements', true) ?: [];
    $total_regle = array_sum(array_column($reglements, 'montant'));

    $total_montants = get_post_meta($post_id, 'fsbdd_total_montants', true) ?: 0;
    $solde = $total_regle - $total_montants;

    // Mettre à jour les données globales
    maj_reglements_et_charges($post_id);
});
