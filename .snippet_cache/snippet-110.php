<?php
/**
 * Snippet ID: 110
 * Name: Afficher MENU choix catégories grille tarifaire listes au-dessus de la description de variation sur produits V2
 * Description: 
 * @active true
 */

/**
 * Ajoute un menu déroulant dynamique basé sur la grille tarifaire et le produit sélectionné.
 * Intègre les améliorations de sécurité, d'internationalisation, et corrige le problème de persistance du prix.
 */
add_action( 'woocommerce_before_single_variation', 'mon_ajout_dropdown_tarifaire' );
function mon_ajout_dropdown_tarifaire() {
    if ( ! is_product() ) {
        return;
    }

    global $product;

    // Sécurité : Vérifier que c'est un produit variable
    if ( ! $product->is_type( 'variable' ) ) {
        return;
    }

    $product_name = $product->get_name();
    $caces_code = get_caces_code_from_product_name( $product_name );
    $price_table = get_full_price_table();

    if ( empty( $caces_code ) || ! isset( $price_table[ $caces_code ] ) ) {
        return; // Pas de tarif applicable
    }

    // Récupère le niveau initial ou recyclage (par défaut : initial)
    $default_attrs = $product->get_default_attributes();
    $niveau = isset( $default_attrs['pa_niveau-recyclage-initial'] )
        ? $default_attrs['pa_niveau-recyclage-initial']
        : 'initial';

    if ( ! isset( $price_table[ $caces_code ][ $niveau ] ) ) {
        return; // Pas de niveau applicable
    }

    $combos = $price_table[ $caces_code ][ $niveau ]['combos'];

    // Internationalisation : Utiliser __() pour les textes
    echo '<div class="champ-dropdown-categorie" style="display: flex; align-items: center; margin-top: 20px;">';
    echo '<label for="choix_categorie" style="margin-right: 10px;"><strong>' . __( 'Catégorie(s) :', 'votre-text-domain' ) . '</strong></label>';
    echo '<select id="choix_categorie" name="choix_categorie" style="padding: 5px; border-radius: 4px; background-color: #2c3e50; color: #ffffff;">';
    echo '<option value="">' . __( '-- Sélectionnez une option --', 'votre-text-domain' ) . '</option>';

    foreach ( $combos as $combo ) {
        $categories = implode( ', ', $combo['categories'] );
        printf( '<option value="%s">%s</option>', esc_attr( $categories ), esc_html( $categories ) );
    }
    
    // Option personnalisée
    echo '<option value="Personnalisé">Personnalisé</option>';

    echo '</select>';
    
    // Champs personnalisés (masqués par défaut)
    echo '<div id="custom_fields" style="display: none; margin-top: 10px;">';
    echo '<label for="custom_categories" style="margin-right: 10px;"><strong>Catégories personnalisées :</strong></label>';
    echo '<input type="text" id="custom_categories" name="custom_categories" placeholder="Ex: A, B, C" style="margin-right: 15px; padding: 5px;" />';
    echo '<label for="custom_ut_pratique" style="margin-right: 10px;"><strong>UT Pratiques :</strong></label>';
    echo '<input type="number" id="custom_ut_pratique" name="custom_ut_pratique" min="0" step="0.5" placeholder="0" style="width: 80px; padding: 5px;" />';
    echo '<span style="margin-left: 10px; font-style: italic;">UT Théoriques : 1</span>';
    echo '</div>';
    echo '</div>';

    // Injection du JavaScript et CSS
    $encoded_table = wp_json_encode( get_full_price_table() );
    ?>

    <style type="text/css">
        /* Styles personnalisés pour le dropdown */

        .champ-dropdown-categorie {
    margin-bottom: 15px; /* Ajoute une marge de 15px en bas du bloc */
    margin-top: 5px !important;
}

        .champ-dropdown-categorie select {
    font-size: 14px;
    color: #ffffff; /* Change la couleur du texte si nécessaire */
}

.champ-dropdown-categorie label {
    font-size: 14px;
    font-weight: bold;
}

    </style>

    <script type="text/javascript">
    jQuery(document).ready(function($){
        var fullTable = <?php echo $encoded_table; ?>;
        var cacesCode = '<?php echo esc_js($caces_code); ?>';
        var defaultNiveau = '<?php echo esc_js($niveau); ?>';
        var currentNiveau = defaultNiveau; // Variable mutable pour le niveau actuel

        var $dropdown = $('#choix_categorie');
        var $hiddenNb = $('#nombre_categories');
        var maxChecked = 5; // On limite à 5 catégories (ou combos)
        
        // Gestion de l'affichage des champs personnalisés
        $dropdown.on('change', function() {
            if ($(this).val() === 'Personnalisé') {
                $('#custom_fields').show();
            } else {
                $('#custom_fields').hide();
                $('#custom_categories').val('');
                $('#custom_ut_pratique').val('');
            }
        });

        // Fonction pour peupler le dropdown en fonction du niveau
        function populateDropdown(niveau) {
            // Vider les options actuelles sauf la première
            $dropdown.find('option:not(:first)').remove();

            var combos = fullTable[cacesCode][niveau]['combos'] || [];
            $.each(combos, function(index, combo){
                var categories = combo.categories.join(', ');
                $dropdown.append(
                    $('<option></option>').val(categories).text(categories)
                );
            });
        }

        // Fonction pour normaliser les labels des catégories
        function normalizeCategoryLabel(label) {
            var map = {
                'Groupe A (élévation verticale)': 'A',
                'Groupe B (élévation multidirectionnelle)': 'B',
                'Groupe C (conduite hors production)': 'C'
            };
            return map[label] ? map[label] : label;
        }

        // Fonction pour trouver le prix correspondant à la sélection
        function findComboPrice(selectedCategories, niveau) {
            var combos = fullTable[cacesCode][niveau]['combos'] || [];
            var matchedPrice = null;

            // Trier les catégories sélectionnées
            var selectedArray = selectedCategories.split(',').map(function(item){ return item.trim(); }).sort();

            $.each(combos, function(index, combo){
                var comboCategories = combo.categories.slice().sort();
                if(comboCategories.length === selectedArray.length && comboCategories.every(function(value, idx) { return value === selectedArray[idx]; })){
                    matchedPrice = combo.price;
                    return false; // Sortir de la boucle
                }
            });

            return matchedPrice;
        }

        // Fonction pour mettre à jour le prix affiché
        function updatePrice() {
            var selectedCategories = $dropdown.val();
            if (!selectedCategories) {
                return;
            }

            var matchedPrice = findComboPrice(selectedCategories, currentNiveau);

            if (matchedPrice !== null) {
                var formattedPrice = matchedPrice.toLocaleString('fr-FR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + ' €';
                $('.single_variation .woocommerce-Price-amount').text(formattedPrice);

                // Mise à jour du nombre de catégories dans un champ caché
                var selectedArray = selectedCategories.split(',').map(function(item){ return item.trim(); }).sort();
                $hiddenNb.val(selectedArray.length);
            }
        }

        // Événement lors du changement de sélection dans le dropdown
        $dropdown.on('change', function(){
            var selectedCategories = $(this).val();

            if (selectedCategories) {
                var selectedArray = selectedCategories.split(',').map(function(item){ return item.trim(); }).sort();
                if (selectedArray.length > maxChecked) {
                    alert('Vous ne pouvez pas sélectionner plus de ' + maxChecked + ' catégories.');
                    // Réinitialiser la sélection
                    $(this).val('');
                    $hiddenNb.val(0);
                    return;
                }
            }

            updatePrice();
        });

        // Initial peuplement du dropdown
        populateDropdown(currentNiveau);
        $dropdown.trigger('change');

        // Gestionnaire pour l'événement show_variation
        $(document).on('show_variation', function(event, variationData){
            // Extraire le niveau de la variation sélectionnée
            var newNiveau = variationData.attributes['attribute_pa_niveau-recyclage-initial'] || 'initial';

            if (newNiveau !== currentNiveau) {
                currentNiveau = newNiveau;
                populateDropdown(currentNiveau);
                $dropdown.trigger('change');
            } else {
                // Si le niveau reste le même, simplement mettre à jour le prix
                $dropdown.trigger('change');
            }
        });

        // Vérification avant l'ajout au panier
        $('form.cart').on('submit', function(event){
            var selectedCategories = $dropdown.val();
            if (!selectedCategories) {
                alert('Veuillez sélectionner une catégorie avant d\'ajouter au panier.');
                event.preventDefault(); // Empêche l'envoi du formulaire
            }
        });
    });
    </script>
    <?php
    // Champ caché pour stocker le nombre de catégories sélectionnées
    echo '<input type="hidden" id="nombre_categories" name="nombre_categories" value="0">';
}

/**
 * Convertit un code CACES en clé de formation pour les UT
 */
function get_formation_key_from_caces_code( $caces_code ) {
    $mapping = array(
        'caces_r489' => 'caces_r489',
        'caces_r482' => 'caces_r482',
        'caces_r485' => 'caces_r485',
        'caces_r486' => 'caces_r486',
        'caces_r490' => 'caces_r490',
        'aces_r489' => 'caces_r489',
        'aces_r482' => 'caces_r482',
        'aces_r485' => 'caces_r485',
        'aces_r486' => 'caces_r486',
        'aces_r490' => 'caces_r490',
        'habilitation_electrique_non_electricien' => 'habilitation_electrique',
        'habilitation_electrique_electricien' => 'habilitation_electrique'
    );
    
    return isset( $mapping[$caces_code] ) ? $mapping[$caces_code] : null;
}

/**
 * Sauvegarde la sélection du menu déroulant dans le panier.
 */
add_filter( 'woocommerce_add_cart_item_data', 'enregistrer_choix_categorie_dans_panier', 10, 2 );
function enregistrer_choix_categorie_dans_panier( $cart_item_data, $product_id ) {
    // Vérifier que le produit est variable
    $product = wc_get_product( $product_id );
    if ( ! $product || ! $product->is_type( 'variable' ) ) {
        return $cart_item_data;
    }

    // Récupérer le nom du produit pour obtenir le code CACES
    $product_name = $product->get_name();
    $caces_code = get_caces_code_from_product_name( $product_name );

    if ( ! empty( $_POST['choix_categorie'] ) ) {
        $cart_item_data['choix_categorie'] = sanitize_text_field( $_POST['choix_categorie'] );
        $cart_item_data['nombre_categories'] = intval( $_POST['nombre_categories'] );
        $cart_item_data['caces_code'] = sanitize_text_field( $caces_code );

        // Ajouter le niveau sélectionné
        if ( isset( $_POST['attribute_pa_niveau-recyclage-initial'] ) ) {
            $cart_item_data['niveau'] = sanitize_text_field( $_POST['attribute_pa_niveau-recyclage-initial'] );
        }

        // Vérifier si c'est le mode personnalisé
        $is_custom_mode = ( $_POST['choix_categorie'] === 'Personnalisé' );
        
        if ( $is_custom_mode ) {
            // Mode personnalisé : utiliser les valeurs fournies
            $cart_item_data['custom_categories'] = isset( $_POST['custom_categories'] ) ? sanitize_text_field( $_POST['custom_categories'] ) : '';
            $cart_item_data['custom_ut_pratique'] = isset( $_POST['custom_ut_pratique'] ) ? floatval( $_POST['custom_ut_pratique'] ) : 0;
            $cart_item_data['custom_ut_theorique'] = 1; // Toujours 1 pour les UT théoriques
            $cart_item_data['is_custom_mode'] = true;
            
            $total_ut_pratique = $cart_item_data['custom_ut_pratique'];
            $total_ut_theorique = $cart_item_data['custom_ut_theorique'];
        } else {
            // Mode normal : calculer les UT à partir de la grille
            $formation_key = get_formation_key_from_caces_code( $caces_code );
            if ( $formation_key ) {
                $categories = array_map( 'trim', explode( ',', $cart_item_data['choix_categorie'] ) );
                $total_ut_pratique = 0;
                $total_ut_theorique = 0;
                
                foreach ( $categories as $category ) {
                    $ut_data = get_ut_for_category( $formation_key, $category );
                    if ( $ut_data ) {
                        $total_ut_pratique += floatval( $ut_data['ut_pratique'] );
                        $total_ut_theorique += floatval( $ut_data['ut_theorique'] );
                    }
                }
            }
            
            $cart_item_data['ut_pratique'] = $total_ut_pratique;
            $cart_item_data['ut_theorique'] = $total_ut_theorique;
        }
    }

    return $cart_item_data;
}

/**
 * Ajuste le prix du panier en fonction de la sélection dans le menu déroulant.
 */
add_action( 'woocommerce_before_calculate_totals', 'ajuster_prix_panier_selon_dropdown', 20, 1 );
function ajuster_prix_panier_selon_dropdown( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) return;

    // Récupérer la table des prix une seule fois
    $price_table = get_full_price_table();

    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {

        if ( empty( $cart_item['choix_categorie'] ) || empty( $cart_item['caces_code'] ) ) {
            continue;
        }

        $caces_code = $cart_item['caces_code'];

        // Utiliser le niveau stocké dans les données du panier
        $niveau = isset( $cart_item['niveau'] ) ? $cart_item['niveau'] : 'initial';

        if ( ! isset( $price_table[ $caces_code ][ $niveau ] ) ) {
            continue;
        }

        $combos = $price_table[ $caces_code ][ $niveau ]['combos'];
        $user_categories = array_map( 'trim', explode( ',', $cart_item['choix_categorie'] ) );
        sort( $user_categories ); // Trier pour une comparaison cohérente

        foreach ( $combos as $combo ) {
            $combo_categories = array_map( 'trim', $combo['categories'] );
            sort( $combo_categories ); // Trier pour une comparaison cohérente

            // Compare les tableaux triés
            if ( count( $combo_categories ) === count( $user_categories ) && ! array_diff( $combo_categories, $user_categories ) ) {
                $new_price = floatval( $combo['price'] );
                if ( $cart_item['data']->get_price() != $new_price ) {
                    $cart_item['data']->set_price( $new_price );
                    // Log pour débogage (à retirer en production)
                    error_log( 'Prix ajusté pour le produit ' . $cart_item['data']->get_name() . ' à ' . $new_price );
                }
                break;
            }
        }
    }
}

/**
 * Affiche la sélection de catégorie dans le récapitulatif du panier et les e-mails.
 */
add_filter( 'woocommerce_get_item_data', 'afficher_infos_categorie_panier', 10, 2 );
function afficher_infos_categorie_panier( $item_data, $cart_item ) {
    if ( ! empty( $cart_item['choix_categorie'] ) ) {
        // Afficher les catégories personnalisées si en mode personnalisé
        if ( isset( $cart_item['is_custom_mode'] ) && $cart_item['is_custom_mode'] && ! empty( $cart_item['custom_categories'] ) ) {
            $item_data[] = array(
                'name' => __( 'Catégories personnalisées', 'votre-text-domain' ),
                'value' => wc_clean( $cart_item['custom_categories'] ),
                'display' => '',
            );
        } else {
            $item_data[] = array(
                'name' => __( 'Catégorie sélectionnée', 'votre-text-domain' ),
                'value' => wc_clean( $cart_item['choix_categorie'] ),
                'display' => '',
            );
        }
    }
    if ( isset( $cart_item['nombre_categories'] ) ) {
        $item_data[] = array(
            'name' => __( 'Nombre de catégories', 'votre-text-domain' ),
            'value' => wc_clean( $cart_item['nombre_categories'] ),
            'display' => '',
        );
    }
    if ( isset( $cart_item['ut_pratique'] ) && $cart_item['ut_pratique'] > 0 ) {
        $item_data[] = array(
            'name' => __( 'UT Pratique', 'votre-text-domain' ),
            'value' => wc_clean( $cart_item['ut_pratique'] ) . ' UT',
            'display' => '',
        );
    }
    if ( isset( $cart_item['ut_theorique'] ) && $cart_item['ut_theorique'] > 0 ) {
        $item_data[] = array(
            'name' => __( 'UT Théorique', 'votre-text-domain' ),
            'value' => wc_clean( $cart_item['ut_theorique'] ) . ' UT',
            'display' => '',
        );
    }
    return $item_data;
}

/**
 * Sauvegarde la sélection dans les métadonnées de commande.
 */
add_action( 'woocommerce_add_order_item_meta', 'enregistrer_meta_commande_choix_categorie', 10, 2 );
function enregistrer_meta_commande_choix_categorie( $item_id, $values ) {
    if ( ! empty( $values['choix_categorie'] ) ) {
        wc_add_order_item_meta( $item_id, 'choix_categorie', sanitize_text_field( $values['choix_categorie'] ) );
    }
    if ( isset( $values['nombre_categories'] ) ) {
        wc_add_order_item_meta( $item_id, 'nombre_categories', intval( $values['nombre_categories'] ) );
    }
    
    // Sauvegarder les données du mode personnalisé
    if ( isset( $values['is_custom_mode'] ) && $values['is_custom_mode'] ) {
        wc_add_order_item_meta( $item_id, 'is_custom_mode', true );
        if ( isset( $values['custom_categories'] ) ) {
            wc_add_order_item_meta( $item_id, 'custom_categories', sanitize_text_field( $values['custom_categories'] ) );
        }
        if ( isset( $values['custom_ut_pratique'] ) ) {
            wc_add_order_item_meta( $item_id, 'custom_ut_pratique', floatval( $values['custom_ut_pratique'] ) );
        }
        if ( isset( $values['custom_ut_theorique'] ) ) {
            wc_add_order_item_meta( $item_id, 'custom_ut_theorique', floatval( $values['custom_ut_theorique'] ) );
        }
    }
    
    if ( isset( $values['ut_pratique'] ) ) {
        wc_add_order_item_meta( $item_id, 'ut_pratique', floatval( $values['ut_pratique'] ) );
    }
    if ( isset( $values['ut_theorique'] ) ) {
        wc_add_order_item_meta( $item_id, 'ut_theorique', floatval( $values['ut_theorique'] ) );
    }
}

/**
 * Ajoute la catégorie sélectionnée dans les e-mails de commande.
 */
add_filter( 'woocommerce_email_order_meta_fields', 'ajouter_choix_categorie_dans_email', 10, 3 );
function ajouter_choix_categorie_dans_email( $fields, $sent_to_admin, $order ) {
    foreach ( $order->get_items() as $item_id => $item ) {
        $categorie = wc_get_order_item_meta( $item_id, 'choix_categorie', true );
        if ( $categorie ) {
            $fields['choix_categorie'] = array(
                'label' => __( 'Catégorie sélectionnée', 'votre-text-domain' ),
                'value' => wc_clean( $categorie ),
            );
        }
        $nombre_categories = wc_get_order_item_meta( $item_id, 'nombre_categories', true );
        if ( $nombre_categories ) {
            $fields['nombre_categories'] = array(
                'label' => __( 'Nombre de catégories', 'votre-text-domain' ),
                'value' => intval( $nombre_categories ),
            );
        }
        $ut_pratique = wc_get_order_item_meta( $item_id, 'ut_pratique', true );
        if ( $ut_pratique ) {
            $fields['ut_pratique'] = array(
                'label' => __( 'UT Pratique', 'votre-text-domain' ),
                'value' => floatval( $ut_pratique ) . ' UT',
            );
        }
        $ut_theorique = wc_get_order_item_meta( $item_id, 'ut_theorique', true );
        if ( $ut_theorique ) {
            $fields['ut_theorique'] = array(
                'label' => __( 'UT Théorique', 'votre-text-domain' ),
                'value' => floatval( $ut_theorique ) . ' UT',
            );
        }
    }
    return $fields;
}
