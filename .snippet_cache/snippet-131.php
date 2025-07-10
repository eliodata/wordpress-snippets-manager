<?php
/**
 * Snippet ID: 131
 * Name: afficher champ fonction client facturation sur page commande admin
 * Description: 
 * @active true
 */

// 1. Corriger l'affichage dans l'admin des commandes (position + sauvegarde)
add_filter('woocommerce_admin_billing_fields', 'custom_admin_billing_fields');
function custom_admin_billing_fields($fields) {
    $new_fields = array();
    
    // Réorganiser l'ordre des champs
    foreach($fields as $key => $field) {
        if($key === 'first_name' || $key === 'last_name') {
            $new_fields[$key] = $field;
        }

		
		        
        // Insérer notre champ après le nom
        if($key === 'last_name') {
            $new_fields['form_fonction'] = array(
                'label' => __('Fonction', 'woocommerce'),
                'show' => true,
                'wrapper_class' => 'form-field-wide'
            );
        }
    }
    
    // Conserver les autres champs existants
    return array_merge($new_fields, $fields);
}

// 2. Forcer l'affichage dans les formats d'adresse
add_filter('woocommerce_get_order_address', 'force_form_fonction_display', 10, 3);
function force_form_fonction_display($address, $type, $order) {
    if($type === 'billing') {
        $function_value = $order->get_meta('_billing_form_fonction');
        if(!empty($function_value)) {
            $address['form_fonction'] = $function_value;
        }
    }
    return $address;
}

// 3. CSS supplémentaire pour positionnement précis
add_action('admin_head', 'custom_admin_billing_field_css');
function custom_admin_billing_field_css() {
    echo '<style>
        .order-billing-fields .edit-address .form-field-wide:nth-child(3) {
            order: 3 !important;
            width: 100% !important;
            clear: both !important;
        }
    </style>';
}

// 3. Ajouter le champ aux adresses formatées
add_filter('woocommerce_order_formatted_billing_address', 'add_form_fonction_to_formatted_address', 10, 2);
function add_form_fonction_to_formatted_address($address, $order) {
    $address['form_fonction'] = $order->get_meta('_billing_form_fonction');
    return $address;
}

// 4. Modifier le format d'adresse pour inclure la fonction
add_filter('woocommerce_localisation_address_formats', 'custom_address_format');
function custom_address_format($formats) {
    foreach ($formats as $key => $format) {
        $formats[$key] = str_replace("{name}\n", "{name}\n{form_fonction}\n", $format);
    }
    return $formats;
}

// 5. Ajouter le remplacement pour le nouveau placeholder
add_filter('woocommerce_formatted_address_replacements', 'custom_address_replacement', 10, 2);
function custom_address_replacement($replacements, $args) {
    $replacements['{form_fonction}'] = !empty($args['form_fonction']) ? $args['form_fonction'] : '';
    return $replacements;
}

// 6. Afficher le champ dans le frontend (page de commande, compte utilisateur)
add_filter('woocommerce_get_order_address', 'add_form_fonction_frontend', 10, 3);
function add_form_fonction_frontend($address, $type, $order) {
    if ($type === 'billing') {
        $address['form_fonction'] = $order->get_meta('_billing_form_fonction');
    }
    return $address;
}