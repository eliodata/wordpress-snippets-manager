<?php
/**
 * Snippet ID: 143
 * Name: Synchro programme pedagogique entre produit et action de formation _ modifs cote cpt si besoin
 * Description: 
 * @active true
 */

// Copier contenu produit vers cpt
add_filter('rwmb_before_get_post_meta', 'prefill_formation_fields_from_product', 10, 4);

function prefill_formation_fields_from_product($meta_value, $field, $object_id, $saved) {
    // Vérifiez que c'est une "action-de-formation"
    if (get_post_type($object_id) !== 'action-de-formation') {
        return $meta_value;
    }

    // Récupérez le produit associé
    $product_id = rwmb_meta('fsbdd_relsessproduit', ['object_type' => 'action-de-formation'], $object_id);
    if (!$product_id) {
        return $meta_value;
    }

    // Liste des champs à préremplir
    $fields_to_prefill = [
            'fsbdd_thematprod',
            'textarea_objectifform',
            'textarea_pointsforts',
            'textarea_contenuform',
            'textarea_resultatsattds',
            'textarea_certificationform',
            'text_dureeform',
            'text_langueformation',
            'textarea_encaform',
            'text_publicvise',
            'textarea_prerequisform',
            'textarea_methodemoyens',
            'textarea_suivievals',
            'textarea_accessibilite',
            'wysiwyg_lieuform',
            'forma_select_cpfok',
            'url_lienverscpf',
            'fsbdd_select_materielreqintra',
            'fsbdd_select_materielreqinter',
            'fsbdd_textarea_orgaplanini',
            'fsbdd_textarea_orgaplanrec',
            'fsbdd_titreform', // Ajout du champ titre
    ];

    // Si le champ est vide, préremplissez avec la valeur du produit
    if (in_array($field['id'], $fields_to_prefill) && empty($meta_value)) {
        // Traitement spécial pour le titre du produit
        if ($field['id'] === 'fsbdd_titreform') {
            $product_value = get_the_title($product_id);
        } else {
            $product_value = rwmb_meta($field['id'], ['object_type' => 'product'], $product_id);
        }
        return $product_value;
    }

    return $meta_value;
}





// Modification sur "action-de-formation" et enregistrement des modifs uniquement

add_action('save_post', 'sync_formation_fields', 10, 2);

function sync_formation_fields($post_id, $post) {
    // Vérifiez que c'est une "action-de-formation"
    if ($post->post_type !== 'action-de-formation') {
        return;
    }

    // Récupérez le produit associé actuel
    $new_product_id = rwmb_meta('fsbdd_relsessproduit', ['object_type' => 'action-de-formation'], $post_id);

    // Récupérez l'ancien produit associé
    $old_product_id = get_post_meta($post_id, '_old_product_id', true);

    // Si le produit associé a changé
    if ($new_product_id != $old_product_id) {
        // Mettez à jour les champs avec les valeurs du nouveau produit
        $fields_to_sync = [
            'fsbdd_thematprod',
            'textarea_objectifform',
            'textarea_pointsforts',
            'textarea_contenuform',
            'textarea_resultatsattds',
            'textarea_certificationform',
            'text_dureeform',
            'text_langueformation',
            'textarea_encaform',
            'text_publicvise',
            'textarea_prerequisform',
            'textarea_methodemoyens',
            'textarea_suivievals',
            'textarea_accessibilite',
            'wysiwyg_lieuform',
            'forma_select_cpfok',
            'url_lienverscpf',
            'fsbdd_select_materielreqintra',
            'fsbdd_select_materielreqinter',
            'fsbdd_textarea_orgaplanini',
            'fsbdd_textarea_orgaplanrec',
            'fsbdd_titreform', // Ajout du champ titre
        ];

        foreach ($fields_to_sync as $field) {
            // Traitement spécial pour le titre du produit
            if ($field === 'fsbdd_titreform') {
                $product_value = get_the_title($new_product_id);
            } else {
                // Récupérez la valeur du champ du nouveau produit
                $product_value = rwmb_meta($field, ['object_type' => 'product'], $new_product_id);
            }
            // Mettez à jour le champ du CPT avec la valeur du nouveau produit
            update_post_meta($post_id, $field, $product_value);
        }

        // Mettez à jour l'ancien produit associé
        update_post_meta($post_id, '_old_product_id', $new_product_id);
    } else {
        // Si le produit associé n'a pas changé, sauvegardez uniquement les modifications
        $fields_to_sync = [
            'fsbdd_thematprod',
            'textarea_objectifform',
            'textarea_pointsforts',
            'textarea_contenuform',
            'textarea_resultatsattds',
            'textarea_certificationform',
            'text_dureeform',
            'text_langueformation',
            'textarea_encaform',
            'text_publicvise',
            'textarea_prerequisform',
            'textarea_methodemoyens',
            'textarea_suivievals',
            'textarea_accessibilite',
            'wysiwyg_lieuform',
            'forma_select_cpfok',
            'url_lienverscpf',
            'fsbdd_select_materielreqintra',
            'fsbdd_select_materielreqinter',
            'fsbdd_textarea_orgaplanini',
            'fsbdd_textarea_orgaplanrec',
            'fsbdd_titreform', // Ajout du champ titre
        ];

        foreach ($fields_to_sync as $field) {
            if ($field === 'fsbdd_titreform') {
                $current_value = rwmb_meta($field, ['object_type' => 'action-de-formation'], $post_id);
                $product_value = get_the_title($new_product_id);
            } else {
                $current_value = rwmb_meta($field, ['object_type' => 'action-de-formation'], $post_id);
                $product_value = rwmb_meta($field, ['object_type' => 'product'], $new_product_id);
            }

            if ($current_value !== $product_value) {
                update_post_meta($post_id, $field, $current_value);
            }
        }
    }
}