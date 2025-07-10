<?php
/**
 * Snippet ID: 117
 * Name: Affichage noms labels metas actions woocommerce
 * Description: 
 * @active true
 */

// Remplacement des labels des métadonnées
add_filter('woocommerce_order_item_get_formatted_meta_data', function ($formatted_meta, $item) {
    $custom_labels = [
        'fsbdd_relsessaction_cpt_produit' => 'Action',
        'we_startdate' => 'Début',
        'we_enddate' => 'Fin',
        'fsbdd_actionum' => 'Session n°',
        'fsbdd_select_lieuforminter' => 'Adresse',
        'choix_categorie' => 'Choix catégorie(s)',
        'nombre_categories' => 'Catégorie(s)',
    ];

    $hidden_meta = [
        'fsbdd_relsessaction_cpt_produit', // Masquer cette clé pour les clients
    ];

    foreach ($formatted_meta as $key => &$meta) {
        // Masquer les métadonnées spécifiées pour les clients
        if (in_array($meta->key, $hidden_meta)) {
            // Si nous sommes sur le back-end (page d'édition de commande), ne pas masquer
            if (!is_admin()) {
                unset($formatted_meta[$key]);
                continue;
            }
        }

        // Remplacement des labels
        if (array_key_exists($meta->key, $custom_labels)) {
            $meta->display_key = $custom_labels[$meta->key];
        }
    }

    return $formatted_meta;
}, 10, 2);
