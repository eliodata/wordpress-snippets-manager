<?php
/**
 * Snippet ID: 24
 * Name: ENTETE metabox FORMATEUR FOURNISSEURS ACTIONS DE FORMATIONS RÉALISÉES
 * Description: 
 * @active true
 */

function add_actions_table_metabox() {
    add_meta_box(
        'linked_actions_table',
        'HISTORIQUE', // Le titre sera mis à jour dynamiquement
        'render_actions_table_metabox',
        ['formateur', 'salle-de-formation'], // Appliqué aux deux CPT
        'normal',
        'high',
    );
}
add_action('add_meta_boxes', 'add_actions_table_metabox');

function render_actions_table_metabox($post) {
    $current_cpt_id = $post->ID; // ID du formateur ou salle actuelle
    $is_formateur = ($post->post_type === 'formateur');

    // Récupérer toutes les actions de formation, en excluant l'ID 268081
    $actions = get_posts([
        'post_type' => 'action-de-formation',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'fields' => 'ids', // Récupérer uniquement les IDs
        'post__not_in' => [268081], // Exclure l'ID 268081
    ]);

    $grouped_actions = [];
    $total_cout_journee = 0;
    $total_inscrits = 0;
    $count_cout = 0;
    $count_inscrits = 0;
    $unique_actions = []; // Tableau pour stocker les IDs des actions uniques

    foreach ($actions as $action_id) {
    $planning = get_post_meta($action_id, 'fsbdd_planning', true);
    $charges = get_post_meta($action_id, 'fsbdd_grpctsformation', true); // Charges liées
    $inscrits = get_post_meta($action_id, 'fsbdd_inscrits', true) ?: 0;

    $start_date_raw = get_post_meta($action_id, 'we_startdate', true);
    $start_date = !empty($start_date_raw) ? date('d/m/Y', intval($start_date_raw)) : '';

    if ($planning && is_array($planning)) {
        foreach ($planning as $day) {
            // Logique pour les formateurs
            if ($is_formateur && isset($day['fsbdd_gpformatr']) && is_array($day['fsbdd_gpformatr'])) {
                foreach ($day['fsbdd_gpformatr'] as $formateur) {
                    if (!empty($formateur['fsbdd_user_formateurrel']) && $formateur['fsbdd_user_formateurrel'] == $current_cpt_id) {
                        // Ajouter l'ID de l'action au tableau des actions uniques
                        if (!in_array($action_id, $unique_actions)) {
                            $unique_actions[] = $action_id;
                        }

                        // Trouver le coût journée correspondant
                        $cout_journee = 'N/A';
                        if (is_array($charges)) {
                            foreach ($charges as $charge) {
                                if (isset($charge['fsbdd_selectcoutform']) && $charge['fsbdd_selectcoutform'] == $current_cpt_id) {
                                    $cout_journee = $charge['fsbdd_coutjourf'] ?? 'N/A';
                                    if (is_numeric($cout_journee)) {
                                        $total_cout_journee += $cout_journee;
                                        $count_cout++;
                                    }
                                    break;
                                }
                            }
                        }

                        $total_inscrits += $inscrits;
                        $count_inscrits++;

                        // Grouper les informations par action
                        $grouped_actions[$action_id]['details'] = [
                            'id' => $action_id,
                            'title' => get_the_title($action_id),
                            'start_date' => $start_date,
                            'end_date' => date('d/m/Y', intval(get_post_meta($action_id, 'we_enddate', true))),
                            'cout_journee' => $cout_journee,
                            'type_session' => get_post_meta($action_id, 'fsbdd_typesession', true) ?: 'N/A',
                            'inscrits' => $inscrits,
                        ];

                        $grouped_actions[$action_id]['plannings'][] = [
                            'date' => $day['fsbdd_planjour'] ?? '',
                            'dispo' => $formateur['fsbdd_dispjourform'] ?? '',
                            'etat' => $formateur['fsbdd_okformatr'] ?? '',
                        ];
                    }
                }
            }

            // Logique pour les salles
            if (!$is_formateur && isset($day['fournisseur_salle']) && is_array($day['fournisseur_salle'])) {
                foreach ($day['fournisseur_salle'] as $salle) {
                    if (!empty($salle['fsbdd_user_foursalle']) && $salle['fsbdd_user_foursalle'] == $current_cpt_id) {
                        // Ajouter l'action si elle est liée à cette salle
                        if (!in_array($action_id, $unique_actions)) {
                            $unique_actions[] = $action_id;
                        }

                        // Ajouter les informations nécessaires
                        $cout_journee = 'N/A';
                        if (is_array($charges)) {
                            foreach ($charges as $charge) {
                                if (isset($charge['fsbdd_selectctfourn']) && $charge['fsbdd_selectctfourn'] == $current_cpt_id) {
                                    $cout_journee = $charge['fsbdd_coutjourf'] ?? 'N/A';
                                    if (is_numeric($cout_journee)) {
                                        $total_cout_journee += $cout_journee;
                                        $count_cout++;
                                    }
                                    break;
                                }
                            }
                        }

                        $total_inscrits += $inscrits;
                        $count_inscrits++;

                        // Grouper les informations par action
                        $grouped_actions[$action_id]['details'] = [
                            'id' => $action_id,
                            'title' => get_the_title($action_id),
                            'start_date' => $start_date,
                            'end_date' => date('d/m/Y', intval(get_post_meta($action_id, 'we_enddate', true))),
                            'cout_journee' => $cout_journee,
                            'type_session' => get_post_meta($action_id, 'fsbdd_typesession', true) ?: 'N/A',
                            'inscrits' => $inscrits,
                        ];

                        $grouped_actions[$action_id]['plannings'][] = [
                            'date' => $day['fsbdd_planjour'] ?? '',
                            'dispo' => $salle['fsbdd_dispjourform'] ?? '',
                            'etat' => $salle['fsbdd_okformatr'] ?? '',
                        ];
                    }
                }
            }
        }
    }
}


    // Calcul des moyennes
    $moyenne_cout_journee = $count_cout > 0 ? $total_cout_journee / $count_cout : 0;
    $moyenne_inscrits = $count_inscrits > 0 ? $total_inscrits / $count_inscrits : 0;

    // Générer le tableau
    echo '<style>
        .linked-actions-table { border-collapse: collapse; width: 100%; }
        .linked-actions-table th { background-color: #f0f0f0; font-weight: bold; text-align: left; padding: 8px; border: 1px solid #ddd; }
        .linked-actions-table td { border: 1px solid #ddd; padding: 8px; }
        .linked-actions-table tr:nth-child(even) { background-color: #f9f9f9; }
        .linked-actions-table tr:hover { background-color: #f1f1f1; }
    </style>';

    if (!empty($grouped_actions)) {
        echo '<table class="linked-actions-table" id="actions-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Action</th>';
        echo '<th>Début</th>';
        echo '<th>Fin</th>';
        echo '<th>Coût journée</th>';
        echo '<th>Planning</th>';
        echo '<th>Type</th>';
        echo '<th>Stagiaires</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($grouped_actions as $action) {
            $details = $action['details'];
            echo '<tr>';
            echo '<td><a href="' . esc_url(get_edit_post_link($details['id'])) . '" target="_blank">' . esc_html($details['title']) . '</a></td>';
            echo '<td>' . esc_html($details['start_date']) . '</td>';
            echo '<td>' . esc_html($details['end_date']) . '</td>';
            echo '<td>' . esc_html($details['cout_journee']) . ' €</td>';

            // Fusionner les données de planning
            echo '<td>';
            foreach ($action['plannings'] as $planning) {
                echo esc_html($planning['date'] . ' | ' . $planning['dispo'] . ' | ' . $planning['etat']) . '<br>';
            }
            echo '</td>';
			
			// Afficher le label pour le type
 
	$type_labels = [
    '1' => 'INTER',
    '2' => 'INTER à définir',
    '3' => 'INTRA',
];
		
			
	$type_value = $details['type_session'];
    $type_label = isset($type_labels[$type_value]) ? $type_labels[$type_value] : 'Non défini';
    echo '<td>' . esc_html($type_label) . '</td>';

    echo '<td>' . esc_html($details['inscrits']) . '</td>';
    echo '</tr>';
}

        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>Aucune action de formation associée trouvée.</p>';
    }

    // Ajout du script pour filtrer par mois et année
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            const moisCheckboxes = document.querySelectorAll('.filtre-mois');
            const anneeCheckboxes = document.querySelectorAll('.filtre-annee');

            function filterTable() {
                const activeMonths = Array.from(moisCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
                const activeYears = Array.from(anneeCheckboxes).filter(cb => cb.checked).map(cb => cb.value);

                document.querySelectorAll('#actions-table tbody tr').forEach(row => {
                    const startDateCell = row.querySelector('td:nth-child(2)');
                    if (!startDateCell) return;

                    const [day, month, year] = startDateCell.textContent.split('/');
                    const monthMatch = activeMonths.length === 0 || activeMonths.includes(month);
                    const yearMatch = activeYears.length === 0 || activeYears.includes(year);

                    row.style.display = (monthMatch && yearMatch) ? '' : 'none';
                });
            }

            moisCheckboxes.forEach(cb => cb.addEventListener('change', filterTable));
            anneeCheckboxes.forEach(cb => cb.addEventListener('change', filterTable));

            // Appliquer le filtrage initial
            filterTable();
        });
    </script>";

    // Mettre à jour dynamiquement le titre de la metabox
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            const metaboxTitle = document.querySelector('#linked_actions_table .hndle');
            if (metaboxTitle) {
                metaboxTitle.innerHTML += ' | Coût journée moy: ' + " . json_encode(number_format($moyenne_cout_journee, 2)) . " + ' € | Effectifs moy: ' + " . json_encode(number_format($moyenne_inscrits, 2)) . " + ' | Total actions réalisées: ' + " . json_encode(count($unique_actions)) . ";
            }
        });
    </script>";
}