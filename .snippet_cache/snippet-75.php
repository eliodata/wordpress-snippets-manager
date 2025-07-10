<?php
/**
 * Snippet ID: 75
 * Name: Générer comptes cpt stagiaires depuis commande certificat de realisation
 * Description: 
 * @active true
 */

add_action('woocommerce_order_status_changed', 'create_stagiaires_cpt_on_status_change', 10, 3);

function create_stagiaires_cpt_on_status_change($order_id, $old_status, $new_status) {
    // Vérifier si le statut cible est atteint
    if ($new_status !== 'certifreal') {
        return;
    }

    // Récupérer la commande
    $order = wc_get_order($order_id);

    // Récupérer le champ billing company
    $billing_company = $order->get_billing_company();

    // Récupérer les données des stagiaires
    $stagiaires = get_post_meta($order_id, 'fsbdd_gpeffectif', true);

    if (empty($stagiaires)) {
        return;
    }

    // Parcourir les produits pour récupérer les données d'action de formation
    $formations = [];
    foreach ($order->get_items() as $item) {
        // Récupérer le titre du produit et son ID
        $product_title = $item->get_name(); // Nom du produit depuis la commande
        $product_id = $item->get_product_id(); // ID du produit

        // Récupérer l'ID de la session d'action de formation
        $session_id = $item->get_meta('fsbdd_relsessaction_cpt_produit', true);

        if (empty($session_id)) {
            continue;
        }

        $cpt_post = get_post($session_id);
        if (!$cpt_post || $cpt_post->post_type !== 'action-de-formation') {
            continue;
        }

        $cpt_meta = get_post_meta($cpt_post->ID);

        // Structure pour enregistrer les formations
        $formation_data = [
            'id' => $cpt_post->ID,
            'title' => $cpt_post->post_title,
            'product_title' => $product_title, // Utiliser le titre du produit récupéré
            'start_date' => isset($cpt_meta['we_startdate'][0]) ? date('d/m/Y', strtotime($cpt_meta['we_startdate'][0])) : 'Non défini',
            'end_date' => isset($cpt_meta['we_enddate'][0]) ? date('d/m/Y', strtotime($cpt_meta['we_enddate'][0])) : 'Non défini',
            'location' => $cpt_meta['fsbdd_select_lieusession'][0] ?? 'Non défini',
            'type_session' => $cpt_meta['fsbdd_typesession'][0] ?? 'Non défini',
        ];

        $formations[] = $formation_data;
    }

    // Créer les CPT stagiaires
    foreach ($stagiaires as $index => $stagiaire) {
        $prenom = $stagiaire['fsbdd_prenomstagiaire'];
        $nom = $stagiaire['fsbdd_nomstagiaire'];
        $date_naissance = $stagiaire['fsbdd_stagidatenaiss'] ?? '';
        $email = $stagiaire['fsbdd_emailstagi'] ?? '';
        $nirstagiaire = $stagiaire['fsbdd_nirstagiaire'] ?? '';

        // Vérifier si le stagiaire existe déjà dans la base
        $existing_stagiaire = find_existing_stagiaire($prenom, $nom, $date_naissance, $nirstagiaire);

        if ($existing_stagiaire) {
            continue; // Ignorer si un doublon est détecté
        }

        // Créer un nouveau CPT stagiaire
        $stagiaire_id = wp_insert_post([
            'post_type' => 'stagiaire',
            'post_status' => 'publish',
        ]);

        if ($stagiaire_id) {
            // Mettre à jour le titre du CPT
            wp_update_post([
                'ID' => $stagiaire_id,
                'post_title' => "$prenom-$nom-$stagiaire_id", // Format prénom-nom-IDCPT
            ]);

            // Enregistrer les informations principales
            update_post_meta($stagiaire_id, 'fsbdd_prenomstagiaire', $prenom);
            update_post_meta($stagiaire_id, 'fsbdd_nomstagiaire', $nom);
            update_post_meta($stagiaire_id, 'fsbdd_nirstagiaire', $stagiaire_id); // Enregistrer l'ID unique comme identifiant
            update_post_meta($stagiaire_id, 'fsbdd_stagidatenaiss', $date_naissance);
            update_post_meta($stagiaire_id, 'fsbdd_emailstagi', $email);

            // **Enregistrer le champ billing company dans fsbdd_employstagi**
            update_post_meta($stagiaire_id, 'fsbdd_employstagi', $billing_company);

            // Enregistrer les informations sur les formations liées
            update_post_meta($stagiaire_id, 'fsbdd_related_formations', $formations);

            // Mettre à jour le champ 'fsbdd_nirstagiaire' côté commande
            $stagiaires[$index]['fsbdd_nirstagiaire'] = $stagiaire_id;
        }
    }

    // Sauvegarder les données mises à jour des stagiaires dans la commande
    update_post_meta($order_id, 'fsbdd_gpeffectif', $stagiaires);
}

// Fonction pour trouver un stagiaire existant
function find_existing_stagiaire($prenom, $nom, $date_naissance, $nirstagiaire) {
    // Si un ID unique existe, vérifier directement
    if (!empty($nirstagiaire)) {
        $existing = get_posts([
            'post_type' => 'stagiaire',
            'meta_query' => [
                [
                    'key' => 'fsbdd_nirstagiaire',
                    'value' => $nirstagiaire,
                    'compare' => '=',
                ],
            ],
            'fields' => 'ids',
        ]);

        return !empty($existing) ? $existing[0] : false;
    }

    // Sinon, vérifier par prénom, nom et date de naissance
    $meta_query = [
        [
            'key' => 'fsbdd_prenomstagiaire',
            'value' => $prenom,
            'compare' => '=',
        ],
        [
            'key' => 'fsbdd_nomstagiaire',
            'value' => $nom,
            'compare' => '=',
        ],
    ];

    if (!empty($date_naissance)) {
        $meta_query[] = [
            'key' => 'fsbdd_stagidatenaiss',
            'value' => $date_naissance,
            'compare' => '=',
        ];
    }

    $existing = get_posts([
        'post_type' => 'stagiaire',
        'meta_query' => $meta_query,
        'fields' => 'ids',
    ]);

    return !empty($existing) ? $existing[0] : false;
}
