<?php
/**
 * Snippet ID: 13
 * Name: METABOX CHARGES FRAIS FORMATION BDD formateurs et fournisseurs
 * Description: 
 * @active false
 */

// Ajouter la metabox aux pages des CPT "formateur", "formateur-passe" et "salle-de-formation"
add_action('add_meta_boxes', function() {
    $cpt_list = ['formateur', 'formateur-passe', 'salle-de-formation'];
    foreach ($cpt_list as $cpt) {
        add_meta_box(
            'charges_fournisseur',
            'CHARGES', // Modifié ici
            'afficher_charges_fournisseur',
            $cpt,
            'normal',
            'high'
        );
    }
});

function afficher_charges_fournisseur($post) {
    if (!current_user_can('administrator')) {
        return;
    }

    // Mois en français
    $mois_francais = [
        '01' => 'Janvier',
        '02' => 'Février',
        '03' => 'Mars',
        '04' => 'Avril',
        '05' => 'Mai',
        '06' => 'Juin',
        '07' => 'Juillet',
        '08' => 'Août',
        '09' => 'Septembre',
        '10' => 'Octobre',
        '11' => 'Novembre',
        '12' => 'Décembre'
    ];

    $annee_courante = date('Y');
    $mois_courant = date('m');

    $mois_precedents = [];
    for ($i = 0; $i < 3; $i++) {
        $mois = date('m', strtotime("-$i month"));
        $mois_precedents[] = $mois;
    }

    echo '<div style="margin-bottom: 10px;">';
    foreach ($mois_francais as $value => $mois) {
        $checked = in_array($value, $mois_precedents) ? 'checked' : '';
        echo '<label style="margin-right: 10px;"><input type="checkbox" name="filtre-mois" value="' . esc_attr($value) . '" ' . $checked . '> ' . esc_html($mois) . '</label>';
    }
    echo '</div>';

    echo '<div style="margin-bottom: 10px;">';
    for ($y = $annee_courante - 5; $y <= $annee_courante + 1; $y++) {
        $checked = ($y == $annee_courante) ? 'checked' : '';
        echo '<label style="margin-right: 10px;"><input type="checkbox" name="filtre-annee" value="' . esc_attr($y) . '" ' . $checked . '> ' . esc_html($y) . '</label>';
    }
    echo '</div>';

    echo '<div style="margin-top: 10px; font-weight: bold;">
        <span id="total-charges">Total charges : 0 €</span> | 
        <span id="solde-impaye">En attente : 0 €</span>
      </div>';

	
	// Ajout du bloc HTML pour afficher les totaux

    echo '<table id="charges-table" style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">';
echo '<thead style="background-color: #f0f0f0;">
        <tr>
            <th style="border: 1px solid #ddd; padding: 8px;">Montant</th>
            <th style="border: 1px solid #ddd; padding: 8px;">Charge</th>
            <th style="border: 1px solid #ddd; padding: 8px;">Détails</th>
            <th style="border: 1px solid #ddd; padding: 8px;">Date</th>
            <th style="border: 1px solid #ddd; padding: 8px;">Affaire</th> <!-- Modifié -->
            <th style="border: 1px solid #ddd; padding: 8px;">Valider</th>
            <th style="border: 1px solid #ddd; padding: 8px;">Date</th> <!-- Modifié -->
        </tr>
      </thead>';


    $args = array(
        'limit' => -1,
        'return' => 'ids',
    );
    $orders = wc_get_orders($args);

    foreach ($orders as $order_id) {
        $charges = get_post_meta($order_id, 'fsbdd_grpctsformation', false);

        if ($charges && is_array($charges)) {
            foreach ($charges as $index => $charge_serialized) {
                $charge = maybe_unserialize($charge_serialized);

                if (is_array($charge)) {
                    $montant = isset($charge['fsbdd_montrechrge']) ? $charge['fsbdd_montrechrge'] : '';
                    $type_charge = isset($charge['fsbdd_typechargedue']) ? $charge['fsbdd_typechargedue'] : '';
                    $details = isset($charge['fsbdd_infoschargedue']) ? $charge['fsbdd_infoschargedue'] : '';
                    $date_debut_formation = get_post_meta($order_id, 'fsbdddate_datedebform', true);
                    $commande_id = $order_id;

                    $formatted_date = DateTime::createFromFormat('d/m/Y', $date_debut_formation);
                    $filter_month = $formatted_date ? $formatted_date->format('m') : '';
                    $filter_year = $formatted_date ? $formatted_date->format('Y') : '';

                    $afficher_charge = false;
                    if (($post->post_type === 'formateur' || $post->post_type === 'formateur-passe') && isset($charge['fsbdd_selectcoutform']) && $charge['fsbdd_selectcoutform'] == $post->ID) {
                        $afficher_charge = true;
                    } elseif ($post->post_type === 'salle-de-formation' && isset($charge['fsbdd_selectctfourn']) && $charge['fsbdd_selectctfourn'] == $post->ID) {
                        $afficher_charge = true;
                    }

                    if ($afficher_charge) {
                        $label_charge = '';
                        switch ($type_charge) {
                            case '1': $label_charge = 'Coûts formateur'; break;
                            case '2': $label_charge = 'Frais de mission'; break;
                            case '3': $label_charge = 'Autres (salle, matériel...)'; break;
                            default: $label_charge = 'Inconnu';
                        }

                        $date_reglement = isset($charge['fsbdd_daterchrge']) ? $charge['fsbdd_daterchrge'] : '';

                        echo '<tr data-month="' . esc_attr($filter_month) . '" data-year="' . esc_attr($filter_year) . '" data-montant="' . esc_attr($montant) . '" data-commande-id="' . esc_attr($commande_id) . '" data-charge-index="' . esc_attr($index) . '">';
                        echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($montant) . ' €</td>';
                        echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($label_charge) . '</td>';
                        echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($details) . '</td>';
                        echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($date_debut_formation) . '</td>';
                        echo '<td style="border: 1px solid #ddd; padding: 8px;"><a href="' . esc_url(admin_url('post.php?post=' . $commande_id . '&action=edit')) . '" target="_blank">' . esc_html($commande_id) . '</a></td>';

                        echo '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">';
                        if (empty($date_reglement)) {
                            echo '<input type="checkbox" class="reglement-checkbox" name="reglement[' . esc_attr($commande_id) . '][' . esc_attr($index) . ']">';
                        } else {
                            echo '<input type="checkbox" class="annuler-reglement-checkbox" name="annuler_reglement[' . esc_attr($commande_id) . '][' . esc_attr($index) . ']"> Annuler?';
                        }
                        echo '</td>';

                        echo '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . esc_html($date_reglement) . '</td>';

                        echo '</tr>';
                    }
                }
            }
        }
    }

    echo '</tbody></table>';

    echo '<div style="margin-top: 15px; font-weight: bold;">Total à valider ce jour : <span id="total-reglement">0.00 €</span></div>';


	
	// Ajouter le second tableau
    echo '<div style="margin-top: 20px; font-weight: bold;">RÉGLEMENTS</div>';
    echo '<table id="reglements-table" style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">';
    echo '<thead style="background-color: #f0f0f0;"><tr>
            <th style="border: 1px solid #ddd; padding: 8px;">Montant</th>
            <th style="border: 1px solid #ddd; padding: 8px;">Date</th>
            <th style="border: 1px solid #ddd; padding: 8px;">Détails</th>
            <th style="border: 1px solid #ddd; padding: 8px;">Annuler</th>
          </tr></thead><tbody>';

		echo '<div style="margin-top: 10px; font-weight: bold;">
        <span id="total-regle">Total réglé : 0 €</span> |
        <span id="solde">Solde : 0 €</span>
      </div>';
	
	
    // Charger les données des métadonnées du CPT
    $montants = get_post_meta($post->ID, 'fsbdd_montreglmt', true);
    $dates = get_post_meta($post->ID, 'fsbdd_datereglmt', true);
    $details = get_post_meta($post->ID, 'fsbdd_infoscharge', true);
    $annulations = get_post_meta($post->ID, 'fsbdd_dateannulreglmt', true);


if (is_array($montants)) {
    foreach ($montants as $index => $montant) {
        $date = isset($dates[$index]) ? $dates[$index] : '';
        if (!empty($date)) {
            // Convertir la date en format d/m/Y et extraire mois et année
            $formatted_date = DateTime::createFromFormat('d/m/Y', $date);
            $filter_month = $formatted_date ? $formatted_date->format('m') : '';
            $filter_year = $formatted_date ? $formatted_date->format('Y') : '';
            $date = $formatted_date ? $formatted_date->format('d/m/Y') : $date;
        }
        $detail = isset($details[$index]) ? $details[$index] : '';
        $annuler = isset($annulations[$index]) && $annulations[$index] === '1' ? 'checked' : '';

        // Ajout des attributs data-month et data-year
        echo '<tr data-month="' . esc_attr($filter_month) . '" data-year="' . esc_attr($filter_year) . '" data-index="' . esc_attr($index) . '">';
        echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($montant) . ' €</td>';
        echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($date) . '</td>';
        echo '<td style="border: 1px solid #ddd; padding: 8px;">' . esc_html($detail) . '</td>';
        echo '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                <input type="checkbox" name="fsbdd_dateannulreglmt[' . esc_attr($index) . ']" ' . $annuler . '>
              </td>';
        echo '</tr>';
    }
}


    echo '</tbody></table>';

    // Ajouter les champs pour ajouter un nouveau règlement
echo '<form method="post">';
wp_nonce_field('ajout_reglement', 'ajout_reglement_nonce');
echo '<div style="margin-top: 20px;">';
echo '<label>Montant : </label><input type="number" step="0.01" name="new-montant" style="width: 100px; margin-right: 10px;">';
echo '<label>Date : </label><input type="date" name="new-date" style="margin-right: 10px;">';
echo '<label>Détails : </label><input type="text" name="new-details" style="margin-right: 10px;">';
echo '<button type="submit" name="add-reglement" style="background: #0073aa; color: white; border: none; padding: 5px 10px; cursor: pointer;">Enregistrer le règlement</button>';
echo '</div>';
echo '</form>';


    ?>


<script>
document.addEventListener('DOMContentLoaded', function () {
	const moisCheckboxes = document.querySelectorAll('input[name="filtre-mois"]');
    const anneeCheckboxes = document.querySelectorAll('input[name="filtre-annee"]');
    const chargesRows = document.querySelectorAll('#charges-table tbody tr');
    const reglementsRows = document.querySelectorAll('#reglements-table tbody tr');
    const totalChargesSpan = document.getElementById('total-charges');
    const soldeImpayeSpan = document.getElementById('solde-impaye');
    const totalReglementSpan = document.getElementById('total-reglement');
    const totalRegleSpan = document.getElementById('total-regle'); // Nouveau
    const soldeSpan = document.getElementById('solde'); // Nouveau


    function filterRows() {
        const selectedMonths = Array.from(moisCheckboxes).filter(checkbox => checkbox.checked).map(checkbox => checkbox.value);
        const selectedYears = Array.from(anneeCheckboxes).filter(checkbox => checkbox.checked).map(checkbox => checkbox.value);
        let totalCharges = 0;
        let soldeImpaye = 0;

        chargesRows.forEach(row => {
            const rowMonth = row.getAttribute('data-month');
            const rowYear = row.getAttribute('data-year');
            const montant = parseFloat(row.getAttribute('data-montant')) || 0;
            const isValidated = row.querySelector('td:last-child').textContent.trim(); // "Validé le" colonne

            // Afficher/masquer la ligne en fonction des filtres mois/année
            if (
                (selectedMonths.length === 0 || selectedMonths.includes(rowMonth)) &&
                (selectedYears.length === 0 || selectedYears.includes(rowYear))
            ) {
                row.style.display = '';
                totalCharges += montant;

                // Compter les montants non validés pour le solde impayé
                if (!isValidated) {
                    soldeImpaye += montant;
                }
            } else {
                row.style.display = 'none';
            }
        });

totalChargesSpan.textContent = `Total charges : ${totalCharges.toFixed(2)} €`;
soldeImpayeSpan.textContent = `En attente : ${soldeImpaye.toFixed(2)} €`;
		        soldeImpayeSpan.style.color = soldeImpaye >= 1 ? 'red' : 'green';


        // Calculer le solde global
        calculateTotalRegle(totalCharges);
    }

    function calculateTotalReglement() {
        let totalReglement = 0;

        chargesRows.forEach(row => {
            const checkbox = row.querySelector('.reglement-checkbox');
            const montant = parseFloat(row.getAttribute('data-montant')) || 0;

            if (checkbox && checkbox.checked) {
                totalReglement += montant;
            }
        });

        totalReglementSpan.textContent = `${totalReglement.toFixed(2)} €`;
    }

    function calculateTotalRegle(totalCharges) {
        let totalRegle = 0;

        reglementsRows.forEach(row => {
            const rowMonth = row.getAttribute('data-month');
            const rowYear = row.getAttribute('data-year');
            const montant = parseFloat(row.querySelector('td:first-child').textContent.replace('€', '').trim()) || 0;

            const selectedMonths = Array.from(moisCheckboxes).filter(checkbox => checkbox.checked).map(checkbox => checkbox.value);
            const selectedYears = Array.from(anneeCheckboxes).filter(checkbox => checkbox.checked).map(checkbox => checkbox.value);

            if (
                (selectedMonths.length === 0 || selectedMonths.includes(rowMonth)) &&
                (selectedYears.length === 0 || selectedYears.includes(rowYear))
            ) {
                row.style.display = '';
                totalRegle += montant;
            } else {
                row.style.display = 'none';
            }
        });

        totalRegleSpan.textContent = `Total réglé : ${totalRegle.toFixed(2)} €`;

        // Calculer le solde
        const solde = totalRegle - totalCharges;

        soldeSpan.textContent = `Solde : ${solde.toFixed(2)} €`;
        soldeSpan.style.color = solde >= 0 ? 'green' : 'red';
    }

    // Ajouter un nouveau règlement
    document.getElementById('add-reglement')?.addEventListener('click', function () {
        const montant = document.getElementById('new-montant').value;
        const date = document.getElementById('new-date').value;
        const details = document.getElementById('new-details').value;

        if (!montant || !date || !details) {
            alert('Veuillez remplir tous les champs.');
            return;
        }

        const tableBody = document.querySelector('#reglements-table tbody');
        const newRow = document.createElement('tr');

        newRow.innerHTML = `
            <td style="border: 1px solid #ddd; padding: 8px;">${parseFloat(montant).toFixed(2)} €</td>
            <td style="border: 1px solid #ddd; padding: 8px;">${date}</td>
            <td style="border: 1px solid #ddd; padding: 8px;">${details}</td>
            <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                <input type="checkbox" name="fsbdd_dateannulreglmt[]">
            </td>`;
        tableBody.appendChild(newRow);

        // Réinitialiser les champs
        document.getElementById('new-montant').value = '';
        document.getElementById('new-date').value = '';
        document.getElementById('new-details').value = '';

        // Recalculer les totaux
        calculateTotalRegle(parseFloat(totalChargesSpan.textContent.replace('Total :', '').replace('€', '').trim()) || 0);
    });

    // Ajouter les gestionnaires d'événements
    moisCheckboxes.forEach(checkbox => checkbox.addEventListener('change', filterRows));
    anneeCheckboxes.forEach(checkbox => checkbox.addEventListener('change', filterRows));
    chargesRows.forEach(row => {
        const reglementCheckbox = row.querySelector('.reglement-checkbox');
        if (reglementCheckbox) {
            reglementCheckbox.addEventListener('change', calculateTotalReglement);
        }
    });

    // Initialiser les calculs au chargement
    filterRows();
    calculateTotalReglement();
});
</script>





    <?php
}

// Hook pour mettre à jour la date de règlement à la sauvegarde du CPT
add_action('save_post', function($post_id) {
    // Vérifiez que c'est bien un enregistrement de CPT autorisé
    $cpt_list = ['formateur', 'formateur-passe', 'salle-de-formation'];
    if (!in_array(get_post_type($post_id), $cpt_list) || !current_user_can('edit_post', $post_id)) {
        return;
    }
	
	    // Sécurité nonce
	if (!isset($_POST['ajout_reglement_nonce']) || !wp_verify_nonce($_POST['ajout_reglement_nonce'], 'ajout_reglement')) {
    return;
}


    // Vérifiez que le bouton "Valider" a été cliqué
    if (isset($_POST['add-reglement'])) {
        $new_montant = sanitize_text_field($_POST['new-montant']);
        $new_date = sanitize_text_field($_POST['new-date']);
        $formatted_date = DateTime::createFromFormat('Y-m-d', $new_date);
        $new_date = $formatted_date ? $formatted_date->format('d/m/Y') : $new_date;
        $new_details = sanitize_text_field($_POST['new-details']);

        // Récupérez les métadonnées existantes
        $montants = get_post_meta($post_id, 'fsbdd_montreglmt', true) ?: [];
        $dates = get_post_meta($post_id, 'fsbdd_datereglmt', true) ?: [];
        $details = get_post_meta($post_id, 'fsbdd_infoscharge', true) ?: [];
        $annulations = get_post_meta($post_id, 'fsbdd_dateannulreglmt', true) ?: [];

        // Ajoutez les nouvelles données
        $montants[] = $new_montant;
        $dates[] = $new_date;
        $details[] = $new_details;
        $annulations[] = ''; // Par défaut, pas d'annulation pour une nouvelle entrée

        // Mettez à jour les métadonnées
        update_post_meta($post_id, 'fsbdd_montreglmt', $montants);
        update_post_meta($post_id, 'fsbdd_datereglmt', $dates);
        update_post_meta($post_id, 'fsbdd_infoscharge', $details);
        update_post_meta($post_id, 'fsbdd_dateannulreglmt', $annulations);
    }

    // Supprimer les lignes dont la case d'annulation est cochée
    $montants = get_post_meta($post_id, 'fsbdd_montreglmt', true) ?: [];
    $dates = get_post_meta($post_id, 'fsbdd_datereglmt', true) ?: [];
    $details = get_post_meta($post_id, 'fsbdd_infoscharge', true) ?: [];
    $annulations = get_post_meta($post_id, 'fsbdd_dateannulreglmt', true) ?: [];

    if (isset($_POST['fsbdd_dateannulreglmt']) && is_array($_POST['fsbdd_dateannulreglmt'])) {
        foreach ($_POST['fsbdd_dateannulreglmt'] as $index => $value) {
            if ($value === 'on') {
                // Supprimer les données pour cet index
                unset($montants[$index]);
                unset($dates[$index]);
                unset($details[$index]);
                unset($annulations[$index]);
            }
        }

        // Réindexez les tableaux après suppression
        $montants = array_values($montants);
        $dates = array_values($dates);
        $details = array_values($details);
        $annulations = array_values($annulations);

        // Mettez à jour les métadonnées
        update_post_meta($post_id, 'fsbdd_montreglmt', $montants);
        update_post_meta($post_id, 'fsbdd_datereglmt', $dates);
        update_post_meta($post_id, 'fsbdd_infoscharge', $details);
        update_post_meta($post_id, 'fsbdd_dateannulreglmt', $annulations);
    }

    // Gestion des paiements ou annulations sur les commandes liées
    if (isset($_POST['reglement']) && is_array($_POST['reglement'])) {
        foreach ($_POST['reglement'] as $commande_id => $charges) {
            $groups = rwmb_meta('fsbdd_grpctsformation', ['object_type' => 'post'], $commande_id);

            foreach ($charges as $charge_index => $value) {
                if (isset($groups[$charge_index])) {
                    $groups[$charge_index]['fsbdd_daterchrge'] = date('d/m/Y');
                }
            }
            rwmb_set_meta($commande_id, 'fsbdd_grpctsformation', $groups);
        }
    }

    if (isset($_POST['annuler_reglement']) && is_array($_POST['annuler_reglement'])) {
        foreach ($_POST['annuler_reglement'] as $commande_id => $charges) {
            $groups = rwmb_meta('fsbdd_grpctsformation', ['object_type' => 'post'], $commande_id);

            foreach ($charges as $charge_index => $value) {
                if (isset($groups[$charge_index])) {
                    $groups[$charge_index]['fsbdd_daterchrge'] = ''; // Vider la date de paiement pour annulation
                }
            }
            rwmb_set_meta($commande_id, 'fsbdd_grpctsformation', $groups);
        }
    }
});