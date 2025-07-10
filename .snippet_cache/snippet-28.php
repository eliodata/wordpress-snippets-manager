<?php
/**
 * Snippet ID: 28
 * Name: SIDE METABOX COMMANDE ACTION DE FORMATION
 * Description: 
 * @active true
 */

// Hook spécifique au type de post 'shop_order'
add_action('add_meta_boxes_shop_order', 'fsbdd_inter_numero_metabox');
function fsbdd_inter_numero_metabox($post) {
    // Chargement de la commande
    $order = wc_get_order($post->ID);

    if (!$order) {
        return; // Si aucune commande n'est trouvée, ne pas ajouter la metabox
    }

    $items = $order->get_items();
    if (empty($items)) {
        return; // Si aucun article dans la commande, ne pas ajouter la metabox
    }

    // Vérifier si au moins un article est lié à un CPT 'action-de-formation'
    $has_action_de_formation = false;
    foreach ($items as $item) {
        $session_id = $item->get_meta('fsbdd_relsessaction_cpt_produit', true);

        if (!empty($session_id)) {
            $cpt_post = get_post($session_id);
            if ($cpt_post && $cpt_post->post_type === 'action-de-formation') {
                $has_action_de_formation = true;
                break; // Aucun besoin de continuer si un CPT valide est trouvé
            }
        }
    }

    // Ajouter la metabox uniquement si un CPT 'action-de-formation' est lié
    if ($has_action_de_formation) {
        add_meta_box(
            'fsbdd_inter_numero_metabox',
            'ACTION DE FORMATION',
            'fsbdd_inter_numero_metabox_callback',
            'shop_order',
            'side',
            'high'
        );
    }
}

function fsbdd_inter_numero_metabox_callback($post) {
    // Chargement de la commande
    $order = wc_get_order($post->ID);

    if (!$order) {
        echo '<p>Aucune commande trouvée.</p>';
        return;
    }

    $items = $order->get_items();
    if (empty($items)) {
        echo '<p>Aucun article trouvé dans cette commande.</p>';
        return;
    }

    // CSS moderne et optimisé
    echo '<style>
        .fsbdd-metabox {
            margin: -6px -12px -12px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        
        .fsbdd-session-header {
            background: #299a68;
            border: none;
            padding: 4px 10px;
            margin-bottom: 10px;
            margin-top: 16px;
            border-radius: 4px;
        }
        
        .fsbdd-session-title {
            font-size: 14px;
            font-weight: 500;
            margin: 0 0 4px;
            align-items: center;
            display: flex;
            justify-content: space-between;
            color: #fff;
        }
        
        .fsbdd-session-title a {
            text-decoration: none;
            display: flex;
            align-items: center;
            color: #fff!important;
            font-size: 16px;
            justify-content: space-between;
        }
        
        .fsbdd-session-title .dashicons {
            margin-right: 4px;
            color: #fff;
            font-size: 20px;
        }
        
        .fsbdd-session-details {
            padding: 0 12px;
            margin-bottom: 10px;
        }
        
        .fsbdd-session-details .dashicons {
            color: #acb9c4;
            font-size: 18px;
        }
        
        .fsbdd-detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .fsbdd-detail-row-full {
            margin-bottom: 5px;
        }
        .fsbdd-detail-item {
            display: flex;
            font-size: 12px;
            flex: 1;
            color: #fff;
            align-items: center;
        }
        .fsbdd-detail-label {
            font-weight: 500;
            margin-right: 5px;
            color: #50575e;
            white-space: nowrap;
            color: #a6aeb6;
        }
        .fsbdd-detail-value {
            word-break: break-word;
        }
        .fsbdd-day-card {
            background: #fff;
            border: 1px solid #e2e4e7;
            border-radius: 4px;
            margin: 10px 0px;
            overflow: hidden;
        }
        .fsbdd-day-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #d5ebff;
            padding: 6px 10px;
            border-bottom: 1px solid #e2e4e7;
        }
        .fsbdd-day-date {
            font-weight: 600;
            font-size: 13px;
        }
        .fsbdd-day-convoc {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }
        .fsbdd-day-convoc label {
            margin-left: 6px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            margin-right: 8px;
        }
        .fsbdd-day-convoc input {
            margin: 0 3px 0 0;
        }
        .fsbdd-day-times {
            display: flex;
            justify-content: center;
            font-size: 10px;
            padding: 4px 0;
            background: #f9f9f9;
            color: #555;
            font-style: italic;
        }
        
        .fsbdd-day-times .dashicons {
            font-size: 14px;
        }
        
        .fsbdd-day-time-sep {
            margin: 0 8px;
            color: #999;
        }
        .fsbdd-day-content {
            padding: 0px 10px;
			margin-top: -7px;
        }
        .fsbdd-section-title {
	font-size: 12px;
    font-weight: 600;
    margin: 0px;
    color: #314150;
    border-bottom: 1px dotted #738e96;
        }
        .fsbdd-person-list {
            margin: 0 0 8px 0;
            padding: 0;
            list-style: none;
        }
        .fsbdd-person-item {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            padding: 0px;
            margin: 0px;
        }
        .fsbdd-person-item:last-child {
            border-bottom: none;
        }
        .fsbdd-person-name {
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 60%;
        }
        .fsbdd-person-status {
            color: #666;
            font-size: 10px;
        }
        .fsbdd-no-data {
            font-style: italic;
            color: #666;
            font-size: 11px;
            text-align: center;
            padding: 5px 0;
        }
    </style>';

    echo '<div class="fsbdd-metabox">';

    foreach ($items as $item) {
        $session_id = $item->get_meta('fsbdd_relsessaction_cpt_produit', true);

        if (empty($session_id)) {
            continue; // Passer à l'article suivant si aucun CPT lié
        }

        $cpt_post = get_post($session_id);
        if (!$cpt_post || $cpt_post->post_type !== 'action-de-formation') {
            continue; // Passer si le CPT n'est pas valide
        }

        $cpt_meta = get_post_meta($cpt_post->ID);
        $planning = isset($cpt_meta['fsbdd_planning'][0]) ? maybe_unserialize($cpt_meta['fsbdd_planning'][0]) : [];
        if (!is_array($planning)) {
            $planning = [];
        }

        $lieu = $cpt_meta['fsbdd_select_lieusession'][0] ?? 'Non défini';
        $startdate = isset($cpt_meta['we_startdate'][0]) ? date('d/m/Y', $cpt_meta['we_startdate'][0]) : 'Non défini';
        $enddate = isset($cpt_meta['we_enddate'][0]) ? date('d/m/Y', $cpt_meta['we_enddate'][0]) : 'Non défini';
        $nombre_stagiaires = $cpt_meta['fsbdd_inscrits'][0] ?? 'Non défini';
        
        // Nouveaux champs
        $nombre_groupes = $cpt_meta['fsbdd_grpesession'][0] ?? 'Non défini';
        $distanciel_value = $cpt_meta['fsbdd_distanciel'][0] ?? '1';
        
        // Convertir la valeur distanciel en texte lisible
        $distanciel_text = 'NON';
        if ($distanciel_value == '2') {
            $distanciel_text = 'OUI';
        } elseif ($distanciel_value == '3') {
            $distanciel_text = 'PARTIEL';
        }

                // Récupérer le champ "fsbdd_inter_numero"
        $fsbdd_inter_numero = $cpt_meta['fsbdd_inter_numero'][0] ?? 'Non défini';

        // En-tête de la session
        echo '<div class="fsbdd-session-header">';
        echo '<h3 class="fsbdd-session-title"><a href="' . esc_url(get_edit_post_link($cpt_post->ID)) . '" target="_blank"><span class="dashicons dashicons-welcome-write-blog"></span>' . esc_html($fsbdd_inter_numero) . '</a><span class="dashicons dashicons-welcome-view-site fsbdd-detail-icon"></span><span class="fsbdd-detail-value">' . esc_html($distanciel_text) . '</span></h3>';
        echo '</div>';
        
        // Informations principales avec la nouvelle organisation
        echo '<div class="fsbdd-session-details">';
        
        // Ligne 1: Début et Fin
        echo '<div class="fsbdd-detail-row">';
        echo '<div class="fsbdd-detail-item"><span class="dashicons dashicons-calendar-alt fsbdd-detail-icon"></span><span class="fsbdd-detail-value">' . esc_html($startdate) . '</span></div>';
        echo '<div class="fsbdd-detail-item"><span class="dashicons dashicons-groups fsbdd-detail-icon"></span><span class="fsbdd-detail-value">' . esc_html($nombre_stagiaires) . '</span></div>';
        echo '</div>';

        // Ligne 2: Stagiaires et Groupes
        echo '<div class="fsbdd-detail-row">';
        echo '<div class="fsbdd-detail-item"><span class="dashicons dashicons-calendar fsbdd-detail-icon"></span><span class="fsbdd-detail-value">' . esc_html($enddate) . '</span></div>';
        echo '<div class="fsbdd-detail-item"><span class="dashicons dashicons-share fsbdd-detail-icon"></span><span class="fsbdd-detail-value">' . esc_html($nombre_groupes) . '</span></div>';
        echo '</div>';
        
        // Ligne 3: Lieu (pleine largeur)
        echo '<div class="fsbdd-detail-row-full">';
        echo '<div class="fsbdd-detail-item"><span class="dashicons dashicons-location fsbdd-detail-icon"></span><span class="fsbdd-detail-value">' . esc_html($lieu) . '</span></div>';
        echo '</div>'; 
        
        echo '</div>'; // Fin de fsbdd-session-details

        // Planning
        if (!empty($planning)) {
            foreach ($planning as $day) {
                $date_raw = $day['fsbdd_planjour'] ?? '';
                $date = $date_raw ? date('d/m/Y', strtotime($date_raw)) : 'Non défini';
                $matin_start = $day['fsbdd_plannmatin'] ?? 'ND';
                $matin_end = $day['fsbdd_plannmatinfin'] ?? 'ND';
                $am_start = $day['fsbdd_plannam'] ?? 'ND';
                $am_end = $day['fsbdd_plannamfin'] ?? 'ND';

                // Récupérer les convocations pour cette date (matin, après-midi et non)
                $convoc_matin = get_post_meta($post->ID, 'fsbdd_convoc_matin_' . $date, true);
                $convoc_aprem = get_post_meta($post->ID, 'fsbdd_convoc_aprem_' . $date, true);
                $convoc_non = get_post_meta($post->ID, 'fsbdd_convoc_non_' . $date, true);
                
                // Cas d'une nouvelle entrée : par défaut, on met Matin et Après-midi cochés, Non décoché
                // MAIS seulement si les trois valeurs sont vides (aucune valeur sauvegardée)
                if ($convoc_matin === '' && $convoc_aprem === '' && $convoc_non === '') {
                    $convoc_matin = '1';
                    $convoc_aprem = '1';
                    $convoc_non = '0';
                }
                
                // Assurer la cohérence des états (matin et après-midi ne peuvent pas être cochés si non est coché)
                if ($convoc_non === '1') {
                    $convoc_matin = '0';
                    $convoc_aprem = '0';
                }
                // Si matin ou après-midi est coché, Non ne peut pas être coché
                elseif ($convoc_matin === '1' || $convoc_aprem === '1') {
                    $convoc_non = '0';
                }
                // Si ni matin ni après-midi n'est coché, Non doit être coché
                elseif ($convoc_matin === '0' && $convoc_aprem === '0') {
                    $convoc_non = '1';
                }

                // Carte pour chaque jour
                echo '<div class="fsbdd-day-card">';
                
                // En-tête du jour
                echo '<div class="fsbdd-day-header">';
                echo '<span class="fsbdd-day-date">' . esc_html($date) . '</span>';
                echo '<div class="fsbdd-day-convoc">';
                
                // Checkboxes avec nonce caché pour éviter les manipulations JavaScript qui ne persistaient pas
                echo '<input type="hidden" name="fsbdd_convoc_nonce" value="' . wp_create_nonce('fsbdd_convoc_save') . '">';
                echo '<label><input type="checkbox" name="fsbdd_convoc_matin_' . esc_attr($date) . '" value="1" ' . checked($convoc_matin, '1', false) . ' class="fsbdd-convoc-checkbox" data-date="' . esc_attr($date) . '" data-type="matin"> Mat.</label>';
                echo '<label><input type="checkbox" name="fsbdd_convoc_aprem_' . esc_attr($date) . '" value="1" ' . checked($convoc_aprem, '1', false) . ' class="fsbdd-convoc-checkbox" data-date="' . esc_attr($date) . '" data-type="aprem"> Aprm</label>';
                echo '<label><input type="checkbox" name="fsbdd_convoc_non_' . esc_attr($date) . '" value="1" ' . checked($convoc_non, '1', false) . ' class="fsbdd-convoc-checkbox" data-date="' . esc_attr($date) . '" data-type="non"> Non</label>';
                echo '</div>';
                echo '</div>';
                
                // Horaires
                $matin_start_h = str_replace(':', 'h', $matin_start);
                $matin_end_h = str_replace(':', 'h', $matin_end);
                $am_start_h = str_replace(':', 'h', $am_start);
                $am_end_h = str_replace(':', 'h', $am_end);
                
                echo '<div class="fsbdd-day-times">';
                echo '<span class="fsbdd-day-time"><span class="dashicons dashicons-clock"></span>' . esc_html($matin_start_h) . ' - ' . esc_html($matin_end_h) . '</span>';
                echo '<span class="fsbdd-day-time-sep">|</span>';
                echo '<span class="fsbdd-day-time"><span class="dashicons dashicons-clock"></span>' . esc_html($am_start_h) . ' - ' . esc_html($am_end_h) . '</span>';
                echo '</div>';
                
                echo '<div class="fsbdd-day-content">';
                
                // Formateurs
                if (!empty($day['fsbdd_gpformatr'])) {
                    echo '<h4 class="fsbdd-section-title">Formateurs</h4>';
                    echo '<ul class="fsbdd-person-list">';
                    foreach ($day['fsbdd_gpformatr'] as $formateur) {
                        $formateur_name = isset($formateur['fsbdd_user_formateurrel']) ? get_the_title($formateur['fsbdd_user_formateurrel']) : 'Inconnu';
                        $dispo = $formateur['fsbdd_dispjourform'] ?? 'ND';
                        $etat = $formateur['fsbdd_okformatr'] ?? 'ND';
                        
                        echo '<li class="fsbdd-person-item">';
                        echo '<span class="fsbdd-person-name" title="'.esc_attr($formateur_name).'">'.esc_html($formateur_name).'</span>';
                        echo '<span class="fsbdd-person-status">'.esc_html($dispo).' - '.esc_html($etat).'</span>';
                        echo '</li>';
                    }
                    echo '</ul>';
                }

                // Fournisseurs / Salles
                if (!empty($day['fournisseur_salle'])) {
                    echo '<h4 class="fsbdd-section-title">Fournisseurs / Salles</h4>';
                    echo '<ul class="fsbdd-person-list">';
                    foreach ($day['fournisseur_salle'] as $fournisseur) {
                        $fournisseur_name = isset($fournisseur['fsbdd_user_foursalle']) ? get_the_title($fournisseur['fsbdd_user_foursalle']) : 'Inconnu';
                        $dispo = $fournisseur['fsbdd_dispjourform'] ?? 'ND';
                        $etat = $fournisseur['fsbdd_okformatr'] ?? 'ND';
                        
                        echo '<li class="fsbdd-person-item">';
                        echo '<span class="fsbdd-person-name" title="'.esc_attr($fournisseur_name).'">'.esc_html($fournisseur_name).'</span>';
                        echo '<span class="fsbdd-person-status">'.esc_html($dispo).' - '.esc_html($etat).'</span>';
                        echo '</li>';
                    }
                    echo '</ul>';
                }
                
                echo '</div>'; // .fsbdd-day-content
                echo '</div>'; // .fsbdd-day-card
            }
        } else {
            echo '<div class="fsbdd-no-data">Aucun planning défini pour cette action de formation.</div>';
        }

        // On s'arrête après le premier article trouvé avec une session d'action-de-formation
        break;
    }

    // JavaScript amélioré pour gérer l'interdépendance des cases à cocher
    echo '<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        var checkboxes = document.querySelectorAll(".fsbdd-convoc-checkbox");
        
        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener("change", function() {
                var date = this.getAttribute("data-date");
                var type = this.getAttribute("data-type");
                
                if (type === "non" && this.checked) {
                    // Si "Non" est coché, décocher Matin et Après-midi
                    document.querySelector("input[name=\'fsbdd_convoc_matin_" + date + "\']").checked = false;
                    document.querySelector("input[name=\'fsbdd_convoc_aprem_" + date + "\']").checked = false;
                } else if ((type === "matin" || type === "aprem") && this.checked) {
                    // Si Matin ou Après-midi est coché, décocher Non
                    document.querySelector("input[name=\'fsbdd_convoc_non_" + date + "\']").checked = false;
                } else if ((type === "matin" || type === "aprem") && !this.checked) {
                    // Si Matin ou Après-midi est décoché, vérifier si lautre est aussi décoché
                    var matinChecked = document.querySelector("input[name=\'fsbdd_convoc_matin_" + date + "\']").checked;
                    var apremChecked = document.querySelector("input[name=\'fsbdd_convoc_aprem_" + date + "\']").checked;
                    
                    if (!matinChecked && !apremChecked) {
                        // Si les deux sont décochés, cocher Non
                        document.querySelector("input[name=\'fsbdd_convoc_non_" + date + "\']").checked = true;
                    }
                }
            });
        });
    });
    </script>';

    echo '</div>'; // .fsbdd-metabox
}

// Sauvegarde des données de la metabox
add_action('save_post', 'save_fsbdd_inter_numero_metabox');
function save_fsbdd_inter_numero_metabox($post_id) {
    // Vérification du type de post
    if (get_post_type($post_id) !== 'shop_order') {
        return;
    }

    // Vérifier le nonce pour la sécurité
    if (!isset($_POST['fsbdd_convoc_nonce']) || !wp_verify_nonce($_POST['fsbdd_convoc_nonce'], 'fsbdd_convoc_save')) {
        return;
    }

    // Charger la commande
    $order = wc_get_order($post_id);
    if (!$order) {
        return;
    }

    $items = $order->get_items();
    $valid_dates = [];

    // Identifier les dates valides à partir des CPT liés
    foreach ($items as $item) {
        $session_id = $item->get_meta('fsbdd_relsessaction_cpt_produit', true);
        if (empty($session_id)) {
            continue;
        }

        $cpt_meta = get_post_meta($session_id);
        $planning = isset($cpt_meta['fsbdd_planning'][0]) 
                    ? maybe_unserialize($cpt_meta['fsbdd_planning'][0]) 
                    : [];
        if (is_array($planning)) {
            foreach ($planning as $day) {
                $date_raw = $day['fsbdd_planjour'] ?? '';
                if (!empty($date_raw)) {
                    $valid_dates[] = date('d/m/Y', strtotime($date_raw));
                }
            }
        }
    }

    // Sauvegarder les nouvelles valeurs et calcul du total
    $total_jours = 0;
    
    foreach ($valid_dates as $date) {
        // Traiter les valeurs du matin
        if (isset($_POST['fsbdd_convoc_matin_' . $date])) {
            $matin_value = '1';
            $total_jours += 0.5;
        } else {
            $matin_value = '0';
        }
        update_post_meta($post_id, 'fsbdd_convoc_matin_' . $date, $matin_value);
        
        // Traiter les valeurs de l'après-midi
        if (isset($_POST['fsbdd_convoc_aprem_' . $date])) {
            $aprem_value = '1';
            $total_jours += 0.5;
        } else {
            $aprem_value = '0';
        }
        update_post_meta($post_id, 'fsbdd_convoc_aprem_' . $date, $aprem_value);
        
        // Traiter l'état "non" (explicitement sauvegardé)
        if (isset($_POST['fsbdd_convoc_non_' . $date])) {
            $non_value = '1';
        } else {
            $non_value = '0';
        }
        update_post_meta($post_id, 'fsbdd_convoc_non_' . $date, $non_value);
        
        // Cohérence avec l'ancienne méta pour compatibilité
        if ($matin_value === '1' || $aprem_value === '1') {
            update_post_meta($post_id, 'fsbdd_convoc_' . $date, '1');
        } else {
            update_post_meta($post_id, 'fsbdd_convoc_' . $date, '0');
        }
    }

    // Enregistrer le total des jours (maintenant avec des demi-journées possibles)
    update_post_meta($post_id, 'fsbdd_convoc_total', $total_jours);
}