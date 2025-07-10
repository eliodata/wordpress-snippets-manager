<?php
/**
 * Snippet ID: 71
 * Name: METABOX Résumé Financier CLIENT PROSPECT BDD
 * Description: 
 * @active true
 */

// Ajouter la metabox aux CPT "client" et "prospect"
add_action('add_meta_boxes', 'ajouter_meta_box_balances_reglements');

function ajouter_meta_box_balances_reglements() {
    $cpt_list = ['client', 'prospect'];
    foreach ($cpt_list as $cpt) {
        add_meta_box(
            'meta-box-balances-reglements',
            __('Balances et Réglements', 'text-domain'),
            'afficher_balances_reglements',
            $cpt,
            'normal',
            'high'
        );
    }
}

function afficher_balances_reglements($post) {
    global $wpdb;

    // Récupérer l'ID de l'utilisateur WordPress lié au CPT "client" ou "prospect"
    $relation_type = 'clients-wp-bdd'; // Ajustez si nécessaire
    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT `from` FROM {$wpdb->prefix}mb_relationships WHERE `to` = %d AND `type` = %s",
        $post->ID,
        $relation_type
    ));

    if (!$user_id) {
        echo '<div class="notice notice-info inline"><p>' . __('Aucun utilisateur lié à ce ' . (get_post_type($post) === 'client' ? 'client' : 'prospect') . '.', 'text-domain') . '</p></div>';
        return;
    }

    // Récupérer les commandes de l'utilisateur
    $orders = wc_get_orders(['customer_id' => $user_id, 'limit' => -1]);

    if (empty($orders)) {
        echo '<div class="notice notice-info inline"><p>' . __('Aucune commande trouvée pour cet utilisateur.', 'text-domain') . '</p></div>';
        return;
    }

    // Initialiser les totaux
    $total_ca_ht = 0;
    $total_ca_ttc = 0;
    $total_regles_client = 0;
    $total_regles_opco = 0;
    $solde_from_orders = 0; // somme des fsbdd_solde

    // Collecter les règlements détaillés
    $reglements_details = [];

    // Parcourir les commandes pour calculer les totaux et collecter les règlements
    foreach ($orders as $order) {
        $order_id = $order->get_id();

        // Récupération des métas
$ca_ht = floatval(get_post_meta($order_id, 'fsbdd_totalcaht', true)) ?: 0.0;
$ca_ttc = floatval(get_post_meta($order_id, 'fsbdd_montcattc', true)) ?: 0.0;
$solde = floatval(get_post_meta($order_id, 'fsbdd_solde', true)) ?: 0.0;

        $reglmt_clients_metas = get_post_meta($order_id, 'fsbdd_reglmtclients', false);
        $reglmt_opcos_metas = get_post_meta($order_id, 'fsbdd_reglmtopco', false);

        // Calcul du CA total HT et TTC
        $total_ca_ht += $ca_ht;
        $total_ca_ttc += $ca_ttc;

        // Ajout du solde de la commande
        $solde_from_orders += $solde;

        // Calcul des règlements par le client
        if (!empty($reglmt_clients_metas)) {
            foreach ($reglmt_clients_metas as $reglmt_client_meta) {
                $reglmt_client = maybe_unserialize($reglmt_client_meta);
                if (is_array($reglmt_client) && isset($reglmt_client['fsbdd_clientreglmt'], $reglmt_client['fsbdd_datereglmtclient'])) {
                    $montant_client = floatval($reglmt_client['fsbdd_clientreglmt']);
                    $date_client = sanitize_text_field($reglmt_client['fsbdd_datereglmtclient']);
                    $info_payeur_client = sanitize_text_field($reglmt_client['fsbdd_infosreglemt'] ?? '');
                    $type_reglement_client = sanitize_text_field($reglmt_client['fsbdd_originreglmt'] ?? '');

                    $total_regles_client += $montant_client;

                    $reglements_details[] = [
                        'date' => $date_client,
                        'montant' => $montant_client,
                        'type_reglement' => $type_reglement_client,
                        'info_payeur' => $info_payeur_client,
                        'origine' => 'Client',
                        'affaire' => $order_id,
                    ];
                }
            }
        }

        // Calcul des règlements par l'OPCO
        if (!empty($reglmt_opcos_metas)) {
            foreach ($reglmt_opcos_metas as $reglmt_opco_meta) {
                $reglmt_opco = maybe_unserialize($reglmt_opco_meta);
                if (is_array($reglmt_opco) && isset($reglmt_opco['fsbdd_opcorglmt'], $reglmt_opco['fsbdd_datereglmtopco'])) {
                    $montant_opco = floatval($reglmt_opco['fsbdd_opcorglmt']);
                    $date_opco = sanitize_text_field($reglmt_opco['fsbdd_datereglmtopco']);
                    $info_payeur_opco = sanitize_text_field($reglmt_opco['fsbdd_infosopcoreglemt'] ?? '');
                    $type_reglement_opco = sanitize_text_field($reglmt_opco['fsbdd_originreglmt'] ?? '');

                    $total_regles_opco += $montant_opco;

                    $reglements_details[] = [
                        'date' => $date_opco,
                        'montant' => $montant_opco,
                        'type_reglement' => $type_reglement_opco,
                        'info_payeur' => $info_payeur_opco,
                        'origine' => 'OPCO',
                        'affaire' => $order_id,
                    ];
                }
            }
        }
    }

    // Calcul du Solde Total (calculé)
    $solde_computed = $total_ca_ttc - ($total_regles_client + $total_regles_opco);

    // Vérification de cohérence
    // total fsbdd_montcattc - (total fsbdd_ttrglmtclient + total fsbdd_ttrglmtopco) doit être égal à total fsbdd_solde
    if (abs($solde_computed - $solde_from_orders) > 0.0001) {
        echo '<div class="notice notice-warning inline"><p>' . __('Attention : Le solde calculé ne correspond pas au solde enregistré (fsbdd_solde).', 'text-domain') . '</p></div>';
    }
	
	error_log('CA total HT: ' . $total_ca_ht);
error_log('CA total TTC: ' . $total_ca_ttc);
error_log('Règlements Client: ' . $total_regles_client);
error_log('Règlements OPCO: ' . $total_regles_opco);
error_log('Solde Total Calculé: ' . $solde_computed);


    // Afficher le tableau résumé
echo '<h3>' . __('Résumé Financier', 'text-domain') . '</h3>';
echo '<table class="resume-financier-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">';
echo '<thead style="background-color: #f0f0f0;">';
echo '<tr>';
echo '<th style="border: 1px solid #ccc; padding: 8px;">' . __('CA total HT', 'text-domain') . '</th>';
echo '<th style="border: 1px solid #ccc; padding: 8px;">' . __('CA total TTC', 'text-domain') . '</th>';
echo '<th style="border: 1px solid #ccc; padding: 8px;">' . __('Réglements Client', 'text-domain') . '</th>';
echo '<th style="border: 1px solid #ccc; padding: 8px;">' . __('Réglements OPCO', 'text-domain') . '</th>';
echo '<th style="border: 1px solid #ccc; padding: 8px;">' . __('Solde Total', 'text-domain') . '</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';
echo '<tr>';
echo '<td style="border: 1px solid #ccc; padding: 8px;">' . number_format(floatval($total_ca_ht), 2, ',', ' ') . ' €</td>';
echo '<td style="border: 1px solid #ccc; padding: 8px;">' . number_format(floatval($total_ca_ttc), 2, ',', ' ') . ' €</td>';
echo '<td style="border: 1px solid #ccc; padding: 8px;">' . number_format(floatval($total_regles_client), 2, ',', ' ') . ' €</td>';
echo '<td style="border: 1px solid #ccc; padding: 8px;">' . number_format(floatval($total_regles_opco), 2, ',', ' ') . ' €</td>';
echo '<td style="border: 1px solid #ccc; padding: 8px; color: ' . ($solde_computed < 0 ? 'red' : 'green') . ';">' . number_format(floatval($solde_computed), 2, ',', ' ') . ' €</td>';
echo '</tr>';
echo '</tbody>';
echo '</table>';


    // Ajouter les filtres
    echo '<h3>' . __('Détails des Règlements', 'text-domain') . '</h3>';
    echo '<div style="margin-bottom: 15px; display: flex; align-items: center; gap: 20px;">';
    
    // Filtre Origine
    echo '<label for="filtre-origine">' . __('Origine', 'text-domain') . ': </label>';
    echo '<select id="filtre-origine" style="padding: 4px 8px; min-width: 150px;">';
    echo '<option value="all">' . __('Client & OPCO', 'text-domain') . '</option>';
    echo '<option value="Client">' . __('Client', 'text-domain') . '</option>';
    echo '<option value="OPCO">' . __('OPCO', 'text-domain') . '</option>';
    echo '</select>';

    // Filtre Affaire (Commande)
    echo '<label for="filtre-affaire">' . __('Affaire', 'text-domain') . ': </label>';
    echo '<input type="text" id="filtre-affaire" placeholder="' . __('Rechercher par Affaire', 'text-domain') . '" style="padding: 4px 8px;">';
    
    echo '</div>';

    // Afficher le tableau détaillé des règlements
    echo '<table class="reglements-table" style="width: 100%; border-collapse: collapse; margin-top: 15px;">';
    echo '<thead style="background-color: #f0f0f0;">';
    echo '<tr>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">' . __('Date', 'text-domain') . '</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">' . __('Montant', 'text-domain') . '</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">' . __('Origine', 'text-domain') . '</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">' . __('Type', 'text-domain') . '</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">' . __('Info', 'text-domain') . '</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">' . __('Affaire', 'text-domain') . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    if (!empty($reglements_details)) {
        foreach ($reglements_details as $reglement) {
            $order_edit_link = admin_url('post.php?post=' . $reglement['affaire'] . '&action=edit');
            $order_id_display = '<a href="' . esc_url($order_edit_link) . '" target="_blank">' . esc_html($reglement['affaire']) . '</a>';

            // Formater la date si disponible
            $date_reglement = !empty($reglement['date']) ? date_i18n('d/m/Y', strtotime($reglement['date'])) : '-';

            // Formater le montant
            $montant_reglement = number_format($reglement['montant'], 2, ',', ' ') . ' €';

            // Type de règlement
            $type_reglement = esc_html($reglement['type_reglement']);

            // Info payeur
            $info_payeur = esc_html($reglement['info_payeur']);

            // Origine
            $origine = esc_html($reglement['origine']);

            echo '<tr>';
            echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($date_reglement) . '</td>';
            echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($montant_reglement) . '</td>';
            echo '<td style="border: 1px solid #ccc; padding: 8px;">' . $origine . '</td>';
            echo '<td style="border: 1px solid #ccc; padding: 8px;">' . $type_reglement . '</td>';
            echo '<td style="border: 1px solid #ccc; padding: 8px;">' . $info_payeur . '</td>';
            echo '<td style="border: 1px solid #ccc; padding: 8px;">' . $order_id_display . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6" style="border: 1px solid #ccc; padding: 8px; text-align: center;">' . __('Aucun règlement trouvé.', 'text-domain') . '</td></tr>';
    }

    echo '</tbody>';
    echo '</table>';

    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            const origineSelect = document.getElementById('filtre-origine');
            const affaireInput = document.getElementById('filtre-affaire');
            const reglementsTable = document.querySelector('.reglements-table tbody');
            if (!reglementsTable) return;
            const rows = reglementsTable.querySelectorAll('tr');

            // Sélection des checkboxes de la sidebar
            const moisCheckboxes = document.querySelectorAll('.filtre-mois');
            const anneeCheckboxes = document.querySelectorAll('.filtre-annee');
            const resetButtonSidebar = document.getElementById('reset-filters-sidebar');

            function filterReglementsDetails() {
                const selectedMois = Array.from(moisCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
                const selectedAnnees = Array.from(anneeCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
                const selectedOrigine = origineSelect.value.toLowerCase();
                const affaireSearch = affaireInput.value.trim().toLowerCase();

                rows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    const dateCell = cells[0].textContent.trim();
                    const origineCell = cells[2].textContent.toLowerCase();
                    const affaireCell = cells[5].textContent.toLowerCase();

                    // Filtrer par Origine
                    let matchesOrigine = (selectedOrigine === 'all') ? true : (origineCell === selectedOrigine);

                    // Filtrer par Affaire
                    let matchesAffaire = (affaireSearch === '') ? true : affaireCell.includes(affaireSearch);

                    // Filtrer par Mois/Année
                    let matchesMoisAnnee = false;
                    if (selectedMois.length === 0 && selectedAnnees.length === 0) {
                        matchesMoisAnnee = true;
                    } else {
                        const dateParts = dateCell.split('/');
                        if (dateParts.length === 3) {
                            const mois = dateParts[1];
                            const annee = dateParts[2];
                            const moisMatch = (selectedMois.length === 0 || selectedMois.includes(mois));
                            const anneeMatch = (selectedAnnees.length === 0 || selectedAnnees.includes(annee));
                            matchesMoisAnnee = moisMatch && anneeMatch;
                        }
                    }

                    if (matchesOrigine && matchesAffaire && matchesMoisAnnee) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            // Événements sur les filtres Origine et Affaire
            origineSelect.addEventListener('change', filterReglementsDetails);
            affaireInput.addEventListener('input', filterReglementsDetails);

            // Événements sur les filtres Mois et Année (sidebar)
            moisCheckboxes.forEach(cb => cb.addEventListener('change', filterReglementsDetails));
            anneeCheckboxes.forEach(cb => cb.addEventListener('change', filterReglementsDetails));

            // Bouton de réinitialisation (Toutes les périodes) dans la sidebar
            if (resetButtonSidebar) {
                resetButtonSidebar.addEventListener('click', filterReglementsDetails);
            }

            // Filtrage initial
            filterReglementsDetails();
        });
    </script>";

    // Styles optionnels
    echo "<style>
        .reglements-table th, .reglements-table td {
            border: 1px solid #ccc;
            padding: 8px;
        }
        .reglements-table thead {
            background-color: #f0f0f0;
        }
        .reglements-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .reglements-table tbody tr:hover {
            background-color: #eaeaea;
        }
    </style>";
}
