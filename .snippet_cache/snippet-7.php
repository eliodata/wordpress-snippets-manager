<?php
/**
 * Snippet ID: 7
 * Name: ENTETE metabox INFOS CLIENTS commandes affaires liées
 * Description: 
 * @active true
 */

// Ajouter une metabox sous forme de tableau pour afficher les commandes liées au client
add_action('add_meta_boxes', 'ajouter_meta_box_commandes_client_adapte_aux_filtres_sidebar_et_balance_et_ca_ttc');

function ajouter_meta_box_commandes_client_adapte_aux_filtres_sidebar_et_balance_et_ca_ttc() {
    add_meta_box(
        'meta-box-commandes-client', 
        'Formations FS - Commandes liées', 
        'afficher_commandes_client_adapte_aux_filtres_sidebar_et_balance_et_ca_ttc', 
        'client', 
        'normal', 
        'high'
    );
}

function afficher_commandes_client_adapte_aux_filtres_sidebar_et_balance_et_ca_ttc($post) {
    global $wpdb;

    // Récupérer l'ID de l'utilisateur WordPress lié au CPT "client"
    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT `from` FROM {$wpdb->prefix}mb_relationships WHERE `to` = %d AND `type` = 'clients-wp-bdd'",
        $post->ID
    ));

    if (!$user_id) {
        echo '<div class="notice notice-info inline"><p>Aucun utilisateur lié à ce client.</p></div>';
        return;
    }

    // Récupérer les commandes de l'utilisateur
    $orders = wc_get_orders(['customer_id' => $user_id]);

    if (empty($orders)) {
        echo '<div class="notice notice-info inline"><p>Aucune commande trouvée pour cet utilisateur.</p></div>';
        return;
    }

    // Vérifier s'il existe un solde négatif dans l'ensemble des commandes
    $found_negative_solde = false;
    foreach ($orders as $check_order) {
        $solde_check = get_post_meta($check_order->get_id(), 'fsbdd_solde', true);
        if (is_numeric($solde_check) && floatval($solde_check) < 0) {
            $found_negative_solde = true;
            break;
        }
    }

    if ($found_negative_solde) {
        echo '<div class="notice notice-warning inline"><p>Attention : Au moins une des commandes présente un solde négatif.</p></div>';
    }

    // Ajouter le filtre "Balance" au-dessus du tableau
    echo '<div style="margin-bottom: 15px;">';
    echo '<label for="balance-filter"><strong>Balance :</strong></label> ';
    echo '<select id="balance-filter" style="margin-left: 10px; padding: 4px; width: 200px;">'; // Élargissement du select
    echo '<option value="all">Toutes les affaires</option>';
    echo '<option value="solde_negative">Soldées</option>'; // "Soldées" -> solde ≤ 0
    echo '<option value="solde_positive">Non soldées</option>'; // "Non soldées" -> solde > 0
    echo '</select>';
    echo '</div>';

    // Tableau
    echo '<table class="linked-orders-table" style="width: 100%; border-collapse: collapse; border: 1px solid #ccc; margin-top: 15px;">';
    echo '<thead style="background-color: #f0f0f0;">';
    echo '<tr>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">Affaire</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">Date</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">Statut</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">Formation</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">Action</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">Effectif</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">CA (HT)</th>';
    echo '<th style="border: 1px solid #ccc; padding: 8px;">CA (TTC)</th>'; 
    echo '<th style="border: 1px solid #ccc; padding: 8px;">Solde</th>'; 
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($orders as $index => $order) {
        $order_id = $order->get_id();
        $order_edit_link = admin_url('post.php?post=' . $order_id . '&action=edit');
        $order_date_obj = $order->get_date_created();
        $order_date = $order_date_obj ? $order_date_obj->date('d/m/Y') : 'Date non définie';
        $order_month = $order_date_obj ? $order_date_obj->date('m') : '';
        $order_year = $order_date_obj ? $order_date_obj->date('Y') : '';
        $order_status = wc_get_order_status_name($order->get_status());
        $formations = '';
        $action_links = '';

        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            if ($product) {
                $product_name = wp_trim_words($product->get_name(), 7, '...');
                $formations .= esc_html($product_name) . '<br>';

                $action_id = $item->get_meta('fsbdd_relsessaction_cpt_produit');
                if ($action_id) {
                    $lieu = get_post_meta($action_id, 'fsbdd_select_lieusession', true);
                    $startdate = get_post_meta($action_id, 'we_startdate', true);
                    $numero = get_the_title($action_id);

                    $lieu_resume = $lieu ? explode(',', $lieu)[0] : 'Lieu inconnu';
                    $startdate_formatted = $startdate ? date_i18n('j F Y', $startdate) : 'Date non définie';

                    $action_link = get_edit_post_link($action_id);
                    $action_links .= '<a href="' . esc_url($action_link) . '" target="_blank">'
                        . esc_html("{$lieu_resume}, {$startdate_formatted}, N°{$numero}") . '</a><br>';
                }
            }
        }

        $effectif = get_post_meta($order_id, 'fsbdd_effectif', true) ?: 'N/A';
        $ca_ht_val = $order->get_total() - $order->get_total_tax();
        $ca_ht = number_format((float)$ca_ht_val, 2, ',', ' ') . ' €';

        // Récupérer le CA (TTC)
        $ca_ttc = get_post_meta($order_id, 'fsbdd_montcattc', true);
        $ca_ttc_display = is_numeric($ca_ttc) ? number_format((float)$ca_ttc, 2, ',', ' ') . ' €' : 'N/A';

        // Récupérer le solde
        $solde = get_post_meta($order_id, 'fsbdd_solde', true);
        $solde_val = is_numeric($solde) ? floatval($solde) : null;

        $solde_display = 'N/A';
        $solde_style = '';
        if ($solde_val !== null) {
            $solde_display = number_format((float)$solde_val, 2, ',', ' ') . ' €';
            if ($solde_val > 0) {
                // solde > 0 = rouge
                $solde_style = 'color: red; font-weight: bold;';
            } elseif ($solde_val == 0) {
                // solde = 0 = vert
                $solde_style = 'color: green; font-weight: bold;';
            } else {
                // solde < 0 (déjà alerté en haut) -> vous pouvez laisser la couleur par défaut ou la personnaliser
                // On peut mettre en orange par exemple
                $solde_style = 'color: orange; font-weight: bold;';
            }
        }

        // Déterminer la classe pour la coloration alternée
        $row_style = $index % 2 === 0 ? 'background-color: #f9f9f9;' : ''; // Gris une ligne sur deux

        // Ajouter des attributs data-month, data-year et data-solde pour le filtrage
        echo '<tr style="' . esc_attr($row_style) . '" data-month="' . esc_attr($order_month) . '" data-year="' . esc_attr($order_year) . '" data-solde="' . esc_attr($solde_val) . '">';
        echo '<td style="border: 1px solid #ccc; padding: 8px;"><a href="' . esc_url($order_edit_link) . '" target="_blank">' . esc_html($order_id) . '</a></td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($order_date) . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($order_status) . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . $formations . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . $action_links . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($effectif) . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . $ca_ht . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($ca_ttc_display) . '</td>';
        echo '<td style="border: 1px solid #ccc; padding: 8px; ' . $solde_style . '">' . esc_html($solde_display) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    // Ajouter le script de filtrage
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sélectionner les éléments de filtre dans la sidebar
            const sidebarMoisCheckboxes = document.querySelectorAll('.filtre-mois');
            const sidebarAnneeCheckboxes = document.querySelectorAll('.filtre-annee');
            const sidebarResetButton = document.getElementById('reset-filters-sidebar');

            // Sélectionner le filtre \"Balance\"
            const balanceFilter = document.getElementById('balance-filter');

            // Sélectionner le tableau des commandes
            const commandesTable = document.querySelector('.linked-orders-table tbody');
            if (!commandesTable) return;

            function filtrerCommandes() {
                const moisSelectionnes = Array.from(sidebarMoisCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);
                const anneesSelectionnees = Array.from(sidebarAnneeCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                const balanceSelectionnee = balanceFilter.value;

                const lignes = commandesTable.querySelectorAll('tr');
                lignes.forEach(ligne => {
                    const mois = ligne.getAttribute('data-month');
                    const annee = ligne.getAttribute('data-year');
                    const solde = parseFloat(ligne.getAttribute('data-solde'));

                    const moisMatch = moisSelectionnes.length === 0 || moisSelectionnes.includes(mois);
                    const anneeMatch = anneesSelectionnees.length === 0 || anneesSelectionnees.includes(annee);

                    let balanceMatch = true;
                    if (balanceSelectionnee === 'solde_positive') {
                        balanceMatch = solde > 0;
                    } else if (balanceSelectionnee === 'solde_negative') {
                        balanceMatch = solde <= 0;
                    }

                    if (moisMatch && anneeMatch && balanceMatch) {
                        ligne.style.display = '';
                    } else {
                        ligne.style.display = 'none';
                    }
                });
            }

            sidebarMoisCheckboxes.forEach(cb => cb.addEventListener('change', filtrerCommandes));
            sidebarAnneeCheckboxes.forEach(cb => cb.addEventListener('change', filtrerCommandes));
            if (balanceFilter) {
                balanceFilter.addEventListener('change', filtrerCommandes);
            }

            if (sidebarResetButton) {
                sidebarResetButton.addEventListener('click', function() {
                    sidebarMoisCheckboxes.forEach(cb => cb.checked = false);
                    sidebarAnneeCheckboxes.forEach(cb => cb.checked = false);
                    if (balanceFilter) {
                        balanceFilter.value = 'all';
                    }
                    filtrerCommandes();
                });
            }

            filtrerCommandes();
        });
    </script>";

    echo '<p style="font-size: 12px; color: #666;">Utilisez les filtres de la sidebar et le filtre "Balance" ci-dessus pour affiner les commandes affichées.</p>';
}
