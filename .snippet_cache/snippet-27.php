<?php
/**
 * Snippet ID: 27
 * Name: SAISIE AUTO DATES CHAMPS PLANNING SESSION
 * Description: 
 * @active false
 */

add_action('woocommerce_update_product', 'initialize_planning_excluding_weekends', 10, 1);

function initialize_planning_excluding_weekends($product_id) {
    // Récupérer les métadonnées existantes
    $planning = get_post_meta($product_id, 'fsbdd_planning', true);

    // Vérifier si le groupe est vide
    if (empty($planning)) {
        $we_startdate = get_post_meta($product_id, 'we_startdate', true); // Timestamp
        $we_enddate = get_post_meta($product_id, 'we_enddate', true);     // Timestamp

        if (!empty($we_startdate) && !empty($we_enddate)) {
            $planning = []; // Initialisation du groupe

            // Convertir les timestamps en objets DateTime
            $start_date = new DateTime("@$we_startdate");
            $end_date = new DateTime("@$we_enddate");

            // Parcourir toutes les dates entre start et end
            while ($start_date <= $end_date) {
                // Ignorer les week-ends (samedi = 6, dimanche = 7)
                $day_of_week = $start_date->format('N'); // 1 = lundi, 7 = dimanche
                if (in_array($day_of_week, [6, 7])) {
                    $start_date->modify('+1 day'); // Passer au jour suivant
                    continue;
                }

                // Ajouter un groupe pour chaque jour valide
                $planning[] = [
                    'fsbdd_planjour'    => $start_date->format('d-m-Y'), // Format : dd-mm-yyyy
                    'fsbdd_plannmatin'  => '08:30',
                    'fsbdd_plannmatinfin' => '12:00',
                    'fsbdd_plannam'     => '13:30',
                    'fsbdd_plannamfin'  => '17:00',
                ];

                // Passer au jour suivant
                $start_date->modify('+1 day');
            }

            // Mettre à jour la métadonnée avec tous les groupes
            update_post_meta($product_id, 'fsbdd_planning', $planning);
        }
    }
}
