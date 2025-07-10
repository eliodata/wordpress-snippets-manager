<?php
/**
 * Snippet ID: 16
 * Name: SYNCHRO GROUPES CHAMPS CLONABLE STAGIAIRES COMMANDE
 * Description: 
 * @active false
 */

add_action('woocommerce_update_order', 'update_stagiaires_clonable_fields');

function update_stagiaires_clonable_fields($order_id) {
    // Récupérer les données existantes du groupe de champ clonable pour éviter d'écraser des saisies manuelles
    $stagiaires_clonable = get_post_meta($order_id, 'fsbdd_gpeffectif', true);
    
    // Si le groupe de champ clonable est vide, on récupère les données des métadonnées individuelles pour le remplir
    if (empty($stagiaires_clonable)) {
        $stagiaires_data = [];
        $index = 1;

        // Boucle pour récupérer chaque paire Nom/Prénom
        while (true) {
            $nom_key = 'fsbddtext_nomstagiaires' . ($index > 1 ? $index : '');
            $prenom_key = 'fsbddtext_prenomstagiaires' . ($index > 1 ? $index : '');

            $nom_value = get_post_meta($order_id, $nom_key, true);
            $prenom_value = get_post_meta($order_id, $prenom_key, true);

            // Arrêter la boucle si aucun nom/prénom n'est trouvé
            if (empty($nom_value) && empty($prenom_value)) break;

            // Ajouter les données de stagiaire au tableau
            $stagiaires_data[] = [
                'fsbdd_nomstagiaire' => $nom_value,
                'fsbdd_prenomstagiaire' => $prenom_value,
            ];

            $index++;
        }

        // Mettre à jour le champ clonable uniquement si des données ont été trouvées
        if (!empty($stagiaires_data)) {
            update_post_meta($order_id, 'fsbdd_gpeffectif', $stagiaires_data);
        }
    }
}