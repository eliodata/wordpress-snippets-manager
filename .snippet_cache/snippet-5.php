<?php
/**
 * Snippet ID: 5
 * Name: CALCUL AUTOMATIQUES FACTURATION COMMANDE
 * Description: <p>Ces snippets permettent d'automatiser divers calculs dans les commandes WooCommerce, en fonction de champs personnalisés. Charges, frais, règlements etc...</p>
 * @active true
 */


// Fonction pour mettre à jour le champ personnalisé 'fsbdd_totalcafdcht' avec le total complet HT du panier
add_action('woocommerce_before_order_object_save', 'update_fsbdd_totalcafdcht_field', 10, 2);

function update_fsbdd_totalcafdcht_field($order, $data_store) {
    // Vérifie si nous sommes dans l'admin et si l'objet est bien une commande
    if (is_admin() && $order instanceof WC_Order) {
        // Récupère le total complet HT du panier (total sans les taxes)
        $total_complet_ht = $order->get_total() - $order->get_total_tax();
        
        // Mets à jour le champ personnalisé 'fsbdd_totalcafdcht' avec la valeur HT totale
        $order->update_meta_data('fsbdd_totalcafdcht', $total_complet_ht);
    }
}

// Fonction pour mettre à jour le champ personnalisé 'fsbdd_totalcaht' avec le total HT du panier dès la sauvegarde de la commande
add_action('woocommerce_admin_order_data_after_order_details', 'remplir_totalcaht_automatiquement');

function remplir_totalcaht_automatiquement($order) {
    $total_ht = $order->get_subtotal(); // Récupère le total HT de la commande
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Remplit le champ personnalisé avec le total HT
            $('input[name="fsbdd_totalcaht"]').val('<?php echo esc_js($total_ht); ?>');
        });
    </script>
    <?php
}


// Fonction pour mettre à jour le champ personnalisé 'fsbdd_montcattc' avec le total TTC du panier
add_action('woocommerce_admin_order_data_after_order_details', 'remplir_montcattc_automatiquement');

function remplir_montcattc_automatiquement($order) {
    $total_ttc = $order->get_total(); // Récupère le total TTC de la commande
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Remplit le champ personnalisé avec le total TTC
            $('input[name="fsbdd_montcattc"]').val('<?php echo esc_js($total_ttc); ?>');
        });
    </script>
    <?php
}



// CALCUL AUTOMATIQUE DU TOTAL DES FRAIS CLIENT SUR LA PAGE DE COMMANDE WOOCOMMERCE

add_action('woocommerce_update_order', 'mettre_a_jour_total_frais_client');

function mettre_a_jour_total_frais_client($order_id) {
    // Récupérer le groupe de champs clonables 'fsbdd_gpfraisclient'
    $groups = rwmb_meta('fsbdd_gpfraisclient', ['object_type' => 'post'], $order_id);

    // Initialiser le total des frais client à 0
    $total_frais_client = 0;

    // Parcourir chaque groupe pour additionner les valeurs du champ 'fsbdd_montfraisclient'
    foreach ($groups as $group) {
        if (!empty($group['fsbdd_montfraisclient'])) {
            $total_frais_client += (float)$group['fsbdd_montfraisclient'];
        }
    }

    // Mettre à jour le champ 'fsbdd_totalfrais' avec le total des frais client
    update_post_meta($order_id, 'fsbdd_totalfrais', $total_frais_client);
}

// CALCUL AUTOMATIQUE DU TOTAL DES FRAIS TTC SUR LA PAGE DE COMMANDE WOOCOMMERCE

add_action('woocommerce_update_order', 'calculer_total_frais_ttc');

function calculer_total_frais_ttc($order_id) {
    // Récupérer le montant des frais hors taxes (HT)
    $total_frais_ht = (float)get_post_meta($order_id, 'fsbdd_totalfrais', true);

    // Récupérer la valeur du select 'fsbdd_check_exotva'
    $check_exotva = get_post_meta($order_id, 'fsbdd_check_exotva', true);

    // Si 'fsbdd_check_exotva' n'est pas égal à 'NON', ajouter 20% de TVA
    if ($check_exotva !== 'NON') {
        $total_frais_ttc = $total_frais_ht * 1.20;  // Ajout de 20%
    } else {
        $total_frais_ttc = $total_frais_ht;  // Pas de TVA
    }

    // Mettre à jour le champ 'fsbdd_totalfraisttc' avec le montant TTC
    update_post_meta($order_id, 'fsbdd_totalfraisttc', $total_frais_ttc);
}



// CALCUL AUTOMATIQUE DU TOTAL DES CHARGES SUR LA PAGE DE COMMANDE WOOCOMMERCE

add_action('woocommerce_update_order', 'mettre_a_jour_total_charge');

function mettre_a_jour_total_charge($order_id) {
    // Récupérer les valeurs des champs individuels
    $fraismission = (float)get_post_meta($order_id, 'fsbdd_fraismission', true);
    $coutsformrs = (float)get_post_meta($order_id, 'fsbdd_coutsformrs', true);
    $charges_logistiques = (float)get_post_meta($order_id, 'fsbdd_ttchrglogisti', true);

    // Calculer le total des charges
    $total_charge = $fraismission + $coutsformrs + $charges_logistiques;

    // Mettre à jour le champ 'fsbdd_totalcharge' avec le total des charges
    update_post_meta($order_id, 'fsbdd_totalcharge', $total_charge);
}


// CALCUL AUTOMATIQUE DE LA MARGE SUR LA PAGE DE COMMANDE WOOCOMMERCE

add_action('woocommerce_update_order', 'mettre_a_jour_marge');

function mettre_a_jour_marge($order_id) {
    $order = wc_get_order($order_id);

    // Récupérer le total HT de la commande
    $order_total_ht = (float)$order->get_subtotal();

    // Récupérer le total des charges
    $total_charge = (float)get_post_meta($order_id, 'fsbdd_totalcharge', true);

    // Calculer la marge
    $marge = $order_total_ht - $total_charge;

    // Mettre à jour le champ marge
    update_post_meta($order_id, 'fsbdd_marge', $marge);
}


// CALCUL AUTOMATIQUE DU TOTAL DES RÈGLEMENTS CLIENTS SUR LA PAGE DE COMMANDE WOOCOMMERCE

add_action('woocommerce_update_order', 'mettre_a_jour_total_reglement_client');

function mettre_a_jour_total_reglement_client($order_id) {
    // Récupérer le groupe de champs clonables 'fsbdd_reglmtclients'
    $groups = rwmb_meta('fsbdd_reglmtclients', ['object_type' => 'post'], $order_id);

    // Initialiser le total des règlements client à 0
    $total_reglement_client = 0;

    // Parcourir chaque groupe pour additionner les valeurs du champ 'fsbdd_clientreglmt'
    foreach ($groups as $group) {
        if (!empty($group['fsbdd_clientreglmt'])) {
            $total_reglement_client += (float)$group['fsbdd_clientreglmt'];
        }
    }

    // Mettre à jour le champ 'fsbdd_ttrglmtclient' avec le total des règlements client
    update_post_meta($order_id, 'fsbdd_ttrglmtclient', $total_reglement_client);
}

// CALCUL AUTOMATIQUE DU SOLDE CLIENT SUR LA PAGE DE COMMANDE WOOCOMMERCE

add_action('woocommerce_update_order', 'mettre_a_jour_solde_client');

function mettre_a_jour_solde_client($order_id) {
    // Récupérer le total client
    $total_client = (float)get_post_meta($order_id, 'fsbdd_totalclient', true);

    // Récupérer le total des règlements du client
    $total_reglement_client = (float)get_post_meta($order_id, 'fsbdd_ttrglmtclient', true);

    // Calculer le solde client
    $solde_client = $total_client - $total_reglement_client;

    // Mettre à jour le champ 'fsbdd_soldeclient' avec le solde client
    update_post_meta($order_id, 'fsbdd_soldeclient', $solde_client);
}


// CALCUL AUTOMATIQUE DU TOTAL DES RÈGLEMENTS FOURNISSEURS SUR LA PAGE DE COMMANDE WOOCOMMERCE

add_action('woocommerce_update_order', 'calculer_total_reglement_fournisseurs');

function calculer_total_reglement_fournisseurs($order_id) {
    // Récupérer les montants des règlements formateurs, frais de mission, et logistiques
    $reglement_formateurs = (float)get_post_meta($order_id, 'fsbdd_ttrglmtformatrs', true);
    $reglement_frais_mission = (float)get_post_meta($order_id, 'fsbdd_ttrglfraimiss', true);
    $reglement_logistiques = (float)get_post_meta($order_id, 'fsbdd_ttrglmtlogist', true);

    // Calculer le total des règlements fournisseurs
    $total_reglement_fournisseurs = $reglement_formateurs + $reglement_frais_mission + $reglement_logistiques;

    // Mettre à jour le champ 'fsbdd_ttrglmtfourn' avec le total des règlements fournisseurs
    update_post_meta($order_id, 'fsbdd_ttrglmtfourn', $total_reglement_fournisseurs);
}


// CALCUL AUTOMATIQUE DES SOLDES FOURNISSEURS SUR LA PAGE DE COMMANDE WOOCOMMERCE

add_action('woocommerce_update_order', 'mettre_a_jour_soldes_fournisseurs');

function mettre_a_jour_soldes_fournisseurs($order_id) {
    // Récupérer les valeurs des champs requis
    $fsbdd_coutsformrs = (float) rwmb_meta('fsbdd_coutsformrs', ['object_type' => 'post'], $order_id);
    $fsbdd_ttrglmtformatrs = (float) rwmb_meta('fsbdd_ttrglmtformatrs', ['object_type' => 'post'], $order_id);
    $fsbdd_fraismission = (float) rwmb_meta('fsbdd_fraismission', ['object_type' => 'post'], $order_id);
    $fsbdd_ttrglfraimiss = (float) rwmb_meta('fsbdd_ttrglfraimiss', ['object_type' => 'post'], $order_id);
    $fsbdd_ttchrglogisti = (float) rwmb_meta('fsbdd_ttchrglogisti', ['object_type' => 'post'], $order_id);
    $fsbdd_ttrglmtlogist = (float) rwmb_meta('fsbdd_ttrglmtlogist', ['object_type' => 'post'], $order_id);

    // Calculer les soldes individuels
    $fsbdd_sldctforms = $fsbdd_coutsformrs - $fsbdd_ttrglmtformatrs;
    $fsbdd_sldfrsmiss = $fsbdd_fraismission - $fsbdd_ttrglfraimiss;
    $fsbdd_sldfrlogis = $fsbdd_ttchrglogisti - $fsbdd_ttrglmtlogist;

    // Calculer le solde total des fournisseurs
    $fsbdd_soldfourni = $fsbdd_sldctforms + $fsbdd_sldfrsmiss + $fsbdd_sldfrlogis;

    // Mettre à jour les champs correspondants
    update_post_meta($order_id, 'fsbdd_sldctforms', $fsbdd_sldctforms);
    update_post_meta($order_id, 'fsbdd_sldfrsmiss', $fsbdd_sldfrsmiss);
    update_post_meta($order_id, 'fsbdd_sldfrlogis', $fsbdd_sldfrlogis);
    update_post_meta($order_id, 'fsbdd_soldfourni', $fsbdd_soldfourni);
}


// CALCUL AUTOMATIQUE DU TOTAL DES RÈGLEMENTS (CLIENT + OPCO) SUR LA PAGE DE COMMANDE WOOCOMMERCE

add_action('woocommerce_update_order', 'mettre_a_jour_total_reglements');

function mettre_a_jour_total_reglements($order_id) {
    // Récupérer le total des règlements client
    $total_reglement_client = (float)get_post_meta($order_id, 'fsbdd_ttrglmtclient', true);

    // Récupérer le total des règlements OPCO
    $total_reglement_opco = (float)get_post_meta($order_id, 'fsbdd_ttrglmtopco', true);

    // Calculer le total des règlements
    $total_reglements = $total_reglement_client + $total_reglement_opco;

    // Mettre à jour le champ 'fsbdd_ttrglmts' avec le total des règlements
    update_post_meta($order_id, 'fsbdd_ttrglmts', $total_reglements);
}

// CALCUL AUTOMATIQUE DU TOTAL DES FRAIS DE MISSION SUR LA PAGE DE COMMANDE WOOCOMMERCE

add_action('woocommerce_update_order', 'mettre_a_jour_total_frais_mission');

function mettre_a_jour_total_frais_mission($order_id) {
    // Récupérer le groupe de champs clonables 'fsbdd_rglemtchrges'
    $groups = rwmb_meta('fsbdd_rglemtchrges', ['object_type' => 'post'], $order_id);

    // Initialiser le total des frais de mission à 0
    $total_frais_mission = 0;

    // Parcourir chaque groupe pour vérifier le type de charge et additionner si c'est un frais de mission
    foreach ($groups as $group) {
        // Vérifier si le select 'fsbdd_typecharge' est égal à 2 (Frais de mission)
        if (!empty($group['fsbdd_typecharge']) && $group['fsbdd_typecharge'] == 2) {
            // Si c'est un frais de mission, additionner le montant 'fsbdd_montreglmt'
            if (!empty($group['fsbdd_montreglmt'])) {
                $total_frais_mission += (float)$group['fsbdd_montreglmt'];
            }
        }
    }

    // Mettre à jour le champ 'fsbdd_ttrglfraimiss' avec le total des frais de mission
    update_post_meta($order_id, 'fsbdd_ttrglfraimiss', $total_frais_mission);
}

// CALCUL AUTOMATIQUE DU TOTAL DES RÈGLEMENTS FORMATEURS SUR LA PAGE DE COMMANDE WOOCOMMERCE

add_action('woocommerce_update_order', 'calculer_total_reglement_formateurs');

function calculer_total_reglement_formateurs($order_id) {
    // Récupérer le groupe de champs clonables 'fsbdd_rglemtchrges'
    $groups = rwmb_meta('fsbdd_rglemtchrges', ['object_type' => 'post'], $order_id);

    // Initialiser le total des règlements formateurs à 0
    $total_reglement_formateurs = 0;

    // Parcourir chaque groupe pour vérifier le type de charge et additionner si c'est un règlement de formateur
    foreach ($groups as $group) {
        // Vérifier si le select 'fsbdd_typecharge' est égal à 1 (Règlement formateur)
        if (!empty($group['fsbdd_typecharge']) && $group['fsbdd_typecharge'] == 1) {
            // Si c'est un règlement de formateur, additionner le montant 'fsbdd_montreglmt'
            if (!empty($group['fsbdd_montreglmt'])) {
                $total_reglement_formateurs += (float)$group['fsbdd_montreglmt'];
            }
        }
    }

    // Mettre à jour le champ 'fsbdd_ttrglmtformatrs' avec le total des règlements formateurs
    update_post_meta($order_id, 'fsbdd_ttrglmtformatrs', $total_reglement_formateurs);
}


// CALCUL AUTOMATIQUE DU TOTAL DES RÈGLEMENTS LOGISTIQUES SUR LA PAGE DE COMMANDE WOOCOMMERCE

add_action('woocommerce_update_order', 'calculer_total_reglement_logistiques');

function calculer_total_reglement_logistiques($order_id) {
    // Récupérer le groupe de champs clonables 'fsbdd_rglemtchrges'
    $groups = rwmb_meta('fsbdd_rglemtchrges', ['object_type' => 'post'], $order_id);

    // Initialiser le total des règlements logistiques à 0
    $total_reglement_logistiques = 0;

    // Parcourir chaque groupe pour vérifier le type de charge et additionner si c'est un règlement logistique
    foreach ($groups as $group) {
        // Vérifier si le select 'fsbdd_typecharge' est égal à 3 (Règlement logistique)
        if (!empty($group['fsbdd_typecharge']) && $group['fsbdd_typecharge'] == 3) {
            // Si c'est un règlement logistique, additionner le montant 'fsbdd_montreglmt'
            if (!empty($group['fsbdd_montreglmt'])) {
                $total_reglement_logistiques += (float)$group['fsbdd_montreglmt'];
            }
        }
    }

    // Mettre à jour le champ 'fsbdd_ttrglmtlogist' avec le total des règlements logistiques
    update_post_meta($order_id, 'fsbdd_ttrglmtlogist', $total_reglement_logistiques);
}



// CALCUL AUTOMATIQUE DU TOTAL DES CHARGES LOGISTIQUES SUR LA PAGE DE COMMANDE WOOCOMMERCE

add_action('woocommerce_update_order', 'mettre_a_jour_total_charges_logistiques');

function mettre_a_jour_total_charges_logistiques($order_id) {
    // Récupérer le groupe de champs clonables 'fsbdd_grpctsformation'
    $groups = rwmb_meta('fsbdd_grpctsformation', ['object_type' => 'post'], $order_id);

    // Initialiser le total des charges logistiques à 0
    $total_charges_logistiques = 0;

    // Parcourir chaque groupe pour vérifier le type de charge et additionner si c'est une charge logistique
    foreach ($groups as $group) {
        // Vérifier si le select 'fsbdd_typechargedue' est égal à 3 (Charges logistiques)
        if (!empty($group['fsbdd_typechargedue']) && $group['fsbdd_typechargedue'] == 3) {
            // Si c'est une charge logistique, additionner le montant 'fsbdd_montrechrge'
            if (!empty($group['fsbdd_montrechrge'])) {
                $total_charges_logistiques += (float)$group['fsbdd_montrechrge'];
            }
        }
    }

    // Mettre à jour le champ 'fsbdd_ttchrglogisti' avec le total des charges logistiques
    update_post_meta($order_id, 'fsbdd_ttchrglogisti', $total_charges_logistiques);
}


// CALCUL AUTOMATIQUE DU TOTAL DES FRAIS DE MISSION SUR LA PAGE DE COMMANDE WOOCOMMERCE

add_action('woocommerce_update_order', 'calculer_total_frais_mission_commande');

function calculer_total_frais_mission_commande($order_id) {
    // Récupérer le groupe de champs clonables 'fsbdd_grpctsformation'
    $groups = rwmb_meta('fsbdd_grpctsformation', ['object_type' => 'post'], $order_id);

    // Initialiser le total des frais de mission à 0
    $total_frais_mission = 0;

    // Parcourir chaque groupe pour vérifier le type de charge et additionner si c'est un frais de mission
    foreach ($groups as $group) {
        // Vérifier si le select 'fsbdd_typechargedue' est égal à 2 (Frais de mission)
        if (!empty($group['fsbdd_typechargedue']) && $group['fsbdd_typechargedue'] == 2) {
            // Si c'est un frais de mission, additionner le montant 'fsbdd_montrechrge'
            if (!empty($group['fsbdd_montrechrge'])) {
                $total_frais_mission += (float)$group['fsbdd_montrechrge'];
            }
        }
    }

    // Mettre à jour le champ 'fsbdd_fraismission' avec le total des frais de mission
    update_post_meta($order_id, 'fsbdd_fraismission', $total_frais_mission);
}


// CALCUL AUTOMATIQUE DU TOTAL DES COÛTS DE FORMATION formateurs SUR LA PAGE DE COMMANDE WOOCOMMERCE

add_action('woocommerce_update_order', 'mettre_a_jour_total_couts_formation');

function mettre_a_jour_total_couts_formation($order_id) {
    // Récupérer le groupe de champs clonables 'fsbdd_grpctsformation'
    $groups = rwmb_meta('fsbdd_grpctsformation', ['object_type' => 'post'], $order_id);

    // Initialiser le total des coûts de formation à 0
    $total_couts_formation = 0;

    // Parcourir chaque groupe pour vérifier le type de charge et additionner si c'est un coût de formation
    foreach ($groups as $group) {
        // Vérifier si le select 'fsbdd_typechargedue' est égal à 1 (Coûts de formation)
        if (!empty($group['fsbdd_typechargedue']) && $group['fsbdd_typechargedue'] == 1) {
            // Si c'est un coût de formation, additionner le montant 'fsbdd_montrechrge'
            if (!empty($group['fsbdd_montrechrge'])) {
                $total_couts_formation += (float)$group['fsbdd_montrechrge'];
            }
        }
    }

    // Mettre à jour le champ 'fsbdd_coutsformrs' avec le total des coûts de formation
    update_post_meta($order_id, 'fsbdd_coutsformrs', $total_couts_formation);
}



// CALCUL AUTOMATIQUE DU SOLDE GLOBAL (CLIENT + OPCO) SUR LA PAGE DE COMMANDE WOOCOMMERCE

add_action('woocommerce_update_order', 'mettre_a_jour_solde');

function mettre_a_jour_solde($order_id) {
    // Récupérer le solde client
    $solde_client = (float)get_post_meta($order_id, 'fsbdd_soldeclient', true);

    // Récupérer le solde OPCO
    $solde_opco = (float)get_post_meta($order_id, 'fsbdd_soldopco', true);

    // Calculer le solde global
    $solde_total = $solde_client + $solde_opco;

    // Mettre à jour le champ 'fsbdd_solde' avec le solde global
    update_post_meta($order_id, 'fsbdd_solde', $solde_total);
}


// CALCUL AUTOMATIQUE DU TOTAL DES RÈGLEMENTS OPCO SUR LA PAGE DE COMMANDE WOOCOMMERCE

add_action('woocommerce_update_order', 'mettre_a_jour_total_reglement_opco');

function mettre_a_jour_total_reglement_opco($order_id) {
    // Récupérer le groupe de champs clonables 'fsbdd_reglmtopco'
    $groups = rwmb_meta('fsbdd_reglmtopco', ['object_type' => 'post'], $order_id);

    // Initialiser le total des règlements OPCO à 0
    $total_reglement_opco = 0;

    // Parcourir chaque groupe pour additionner les valeurs du champ 'fsbdd_opcorglmt'
    foreach ($groups as $group) {
        if (!empty($group['fsbdd_opcorglmt'])) {
            $total_reglement_opco += (float)$group['fsbdd_opcorglmt'];
        }
    }

    // Mettre à jour le champ 'fsbdd_ttrglmtopco' avec le total des règlements OPCO
    update_post_meta($order_id, 'fsbdd_ttrglmtopco', $total_reglement_opco);
}


// CALCUL AUTOMATIQUE DU SOLDE OPCO SUR LA PAGE DE COMMANDE WOOCOMMERCE

add_action('woocommerce_update_order', 'mettre_a_jour_solde_opco');

function mettre_a_jour_solde_opco($order_id) {
    // Récupérer le total OPCO
    $total_opco = (float)get_post_meta($order_id, 'fsbdd_totalopco', true);

    // Récupérer le total des règlements OPCO
    $total_reglement_opco = (float)get_post_meta($order_id, 'fsbdd_ttrglmtopco', true);

    // Calculer le solde OPCO
    $solde_opco = $total_opco - $total_reglement_opco;

    // Mettre à jour le champ 'fsbdd_soldopco' avec le solde OPCO
    update_post_meta($order_id, 'fsbdd_soldopco', $solde_opco);
}

// CALCUL AUTOMATIQUE DE fsbdd_totalopco ET fsbdd_totalclient SUR LA PAGE DE COMMANDE WOOCOMMERCE depuis OPCO
// CALCUL AUTOMATIQUE DE fsbdd_totalopco, fsbddnumber_tvaopco, fsbdd_totalclient, fsbddnumber_tvaclient et fsbdd_totalclientht
add_action('woocommerce_update_order', 'calculer_total_client_et_opco');

function calculer_total_client_et_opco($order_id) {
    // Récupérer la commande WooCommerce
    $order = wc_get_order($order_id);

    // Vérifier si l'objet commande est valide
    if (!$order) {
        return;
    }

    // 1) Récupérer le total de la commande (TTC)
    $order_total = (float) $order->get_total();

    // 2) Récupérer le champ select "fsbdd_check_exotva"
    $exotva_status = get_post_meta($order_id, 'fsbdd_check_exotva', true);

    // 3) Récupérer le montant hors taxes pour l'OPCO
    $total_opco_ht = get_post_meta($order_id, 'fsbddnumber_totalopcoht', true);
    if (empty($total_opco_ht) || !is_numeric($total_opco_ht)) {
        $total_opco_ht = 0;
    }
    $total_opco_ht = floatval($total_opco_ht);

    // Préparer nos variables de calcul
    $total_opco = 0.0;
    $tva_opco   = 0.0;

    // 4) Calculer "fsbdd_totalopco" et "fsbddnumber_tvaopco" selon "fsbdd_check_exotva"
    switch ($exotva_status) {
        case 'NON':
            // OPCO en HT -> pas de TVA OPCO
            $total_opco = $total_opco_ht;
            $tva_opco   = 0.0;
            break;

        case 'OUI':
        case 'TODO':
            // OPCO en TTC
            $total_opco = $total_opco_ht * 1.2;  // OPCO TTC
            $tva_opco   = $total_opco_ht * 0.2;  // TVA OPCO
            break;

        default:
            // Par défaut OPCO HT
            $total_opco = $total_opco_ht;
            $tva_opco   = 0.0;
    }

    // Mettre à jour les métas OPCO
    update_post_meta($order_id, 'fsbdd_totalopco', $total_opco);
    update_post_meta($order_id, 'fsbddnumber_tvaopco', $tva_opco);

    // 5) Calculer la part client (TTC)
    $total_client = $order_total - $total_opco;
    update_post_meta($order_id, 'fsbdd_totalclient', $total_client);

    // 6) Calcul de la TVA totale de la commande
    $total_tva = (float) $order->get_total_tax(); 
    // (ou en alternative : 
    //  $order_tax = get_post_meta($order_id, '_order_tax', true);
    //  $total_tva = floatval($order_tax);
    // )

    // 7) TVA client = TVA totale - TVA OPCO
    $tva_client = $total_tva - $tva_opco;
    update_post_meta($order_id, 'fsbddnumber_tvaclient', $tva_client);

    // 8) Calcul du total client HT = total client TTC - TVA client
    $total_client_ht = $total_client - $tva_client;
    update_post_meta($order_id, 'fsbdd_totalclientht', $total_client_ht);
}

