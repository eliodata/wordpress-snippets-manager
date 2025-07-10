<?php
/**
 * Snippet ID: 65
 * Name: CHOIX ACTION DE FORMATION et variations produit DEPUIS COMMANDE - select cpt fsbdd_relsessaction_cpt_produit - modifier session
 * Description: 
 * @active true
 */

// Ajouter la nouvelle metabox combinée
add_action( 'add_meta_boxes', 'add_combined_meta_box' );
function add_combined_meta_box() {
    add_meta_box(
        'combined_meta_box',
        'Modifier options / session',
        'display_combined_meta_box',
        'shop_order',
        'side',
        'default'
    );
}

function display_combined_meta_box( $post ) {
    // Récupère la commande
    $order = wc_get_order( $post->ID );
    // S'assure qu'on a un objet commande (peut être "faux" dans certains cas de commandes vides)
    $items = $order ? $order->get_items() : array();

    // ------------------------------------------------------------------
    // Préparation d'une liste de tous les produits "visibles" du catalogue.
    // ------------------------------------------------------------------
    $all_visible_products_args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => array( 'exclude-from-catalog' ), // Produits exclus du catalogue
                'operator' => 'NOT IN',
            ),
        ),
        'orderby' => 'title',
        'order'   => 'ASC',
    );
    $all_visible_products = get_posts( $all_visible_products_args );

    // Préparer la liste des produits variables pour le JavaScript
    $variable_products = array();
    foreach ($all_visible_products as $prod) {
        $product = wc_get_product($prod->ID);
        if ($product && $product->is_type('variable')) {
            $variable_products[] = $prod->ID;
        }
    }
    $variable_products_json = json_encode($variable_products);

    // ------------------------------------------------------------------
    // Styles pour l'affichage - AMÉLIORÉS
    // ------------------------------------------------------------------
    echo '<style>
    .combined-forms-container {
        margin-top: 5px;
        font-size: 11px;
    }
    .combined-forms-container form {
        margin-bottom: 6px;
        position: relative;
    }
    .combined-forms-container .form-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4px;
    }
    .combined-forms-container select {
        width: 70%;
        padding: 3px 20px 3px 5px;
        border-radius: 3px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.07);
        border: 1px solid #ddd;
        background-color: #fff;
        color: #32373c;
        height: 24px;
        font-size: 11px;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'8\' height=\'8\' fill=\'%23555\' viewBox=\'0 0 16 16\'%3E%3Cpath d=\'M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z\'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: calc(100% - 5px) center;
        background-size: 8px;
    }
    .combined-forms-container select:focus {
        border-color: #2271b1;
        box-shadow: 0 0 0 1px #2271b1;
        outline: none;
    }
    .combined-forms-container .form-section {
        margin-bottom: 6px;
        padding-bottom: 4px;
        border-bottom: 1px solid #f0f0f0;
    }
    .combined-forms-container .form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    .combined-forms-container button,
    .combined-forms-container .button-link {
        background: #f6f7f7;
        border: 1px solid #2271b1;
        color: #2271b1;
        cursor: pointer;
        font-size: 11px;
        padding: 3px 8px;
        text-align: center;
        border-radius: 2px;
        text-decoration: none;
        transition: all 0.1s ease-in-out;
        height: 24px;
        line-height: 1;
        float: right;
    }
    .combined-forms-container button:hover,
    .combined-forms-container .button-link:hover {
        background: #2271b1;
        border-color: #2271b1;
        color: #fff;
    }
    .combined-forms-container .new-session-link {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: #2271b1;
        padding: 4px;
        border-radius: 2px;
        transition: all 0.1s ease-in-out;
        background: #f6f7f7;
        border: 1px solid #ddd;
    }
    .combined-forms-container .new-session-link:hover {
        background: #2271b1;
        color: #fff!important;
        border-color: #2271b1;
    }
    .combined-forms-container .dashicons {
        margin-right: 3px;
        font-size: 14px;
        height: 14px;
        width: 14px;
    }
    .combined-forms-container .no-items-message {
        background: #f8f9fa;
        padding: 4px;
        border-radius: 2px;
        border-left: 2px solid #ddd;
        margin-bottom: 5px;
        font-style: italic;
        color: #646970;
    }
    .combined-forms-container .variations-container {
        margin-top: 4px;
        display: none;
    }
    .combined-forms-container .variations-title {
        font-size: 10px;
        color: #666;
        margin-bottom: 3px;
    }
    .combined-forms-container .loading-spinner {
        display: inline-block;
        width: 12px;
        height: 12px;
        border: 2px solid rgba(0,0,0,0.1);
        border-radius: 50%;
        border-top-color: #2271b1;
        animation: spinner 0.6s linear infinite;
        margin-left: 5px;
        vertical-align: middle;
    }
    @keyframes spinner {
        to {transform: rotate(360deg);}
    }
    .current-product-info {
        background: #f0f6fc;
        padding: 4px;
        border-radius: 2px;
        border-left: 2px solid #2271b1;
        margin-bottom: 4px;
        font-size: 10px;
    }
    .custom-category-fields {
        margin-top: 4px;
        display: none;
        background: #f9f9f9;
        padding: 4px;
        border-radius: 2px;
        border: 1px solid #ddd;
    }
    .custom-category-fields input {
        width: 100%;
        margin-bottom: 4px;
        padding: 3px 5px;
        border: 1px solid #ddd;
        border-radius: 2px;
        font-size: 11px;
        height: 20px;
    }
    .custom-category-fields .note {
        font-size: 9px;
        color: #666;
        font-style: italic;
    }
    </style>';

    echo '<div class="combined-forms-container">';

    // ---------------------------------------------------------------------------------
    // CAS 1 : La commande est vide => on affiche le sélecteur de tous les produits
    // ---------------------------------------------------------------------------------
    if ( empty( $items ) ) {
        echo '<div class="form-section">';
        if ( ! empty( $all_visible_products ) ) {
            echo '<form id="add_product_form">';
            
            // Select pour choisir le produit
            echo '<div class="form-row">';
            echo '<select name="product_id" id="product_select">';
            echo '<option value="">Commande vierge : sélectionner un produit</option>';
            foreach ( $all_visible_products as $prod ) {
                $prod_obj = wc_get_product( $prod->ID );
                if ( ! $prod_obj ) {
                    continue;
                }
                
                // Exclure les produits avec "définir" dans le nom
                if (stripos($prod_obj->get_name(), 'définir') !== false) {
                    continue;
                }
                
                // Marquer les produits variables
                $product_type = $prod_obj->get_type();
                $is_variable = ($product_type === 'variable') ? ' (Variable)' : '';
                
                echo '<option value="' . esc_attr( $prod->ID ) . '" data-type="' . esc_attr($product_type) . '">' 
                    . esc_html( $prod_obj->get_name() . $is_variable ) . '</option>';
            }
            echo '</select>';
            echo '<div id="product_loading" class="loading-spinner" style="display: none;"></div>';
            echo '</div>';
            
            // Container pour les variations
            echo '<div id="variations_container" class="variations-container">';
            echo '<div class="variations-title">Sélectionner une variation:</div>';
            echo '<div class="form-row">';
            echo '<select name="variation_id" id="variation_select">';
            echo '<option value="">Choisir une variation</option>';
            echo '</select>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="form-row" style="margin-top: 10px;">';
            echo '<input type="hidden" name="order_id" value="' . esc_attr( $post->ID ) . '">';
            echo '<button type="button" id="add_product_button">Ajouter</button>';
            echo '</div>';
            echo '</form>';
        } else {
            echo '<div class="no-items-message">Aucun produit visible dans le catalogue.</div>';
        }
        echo '</div>'; // fin section

        // Lien pour créer une nouvelle session
        echo '<div class="form-section">';
        echo '<a href="https://formationstrategique.fr/wp-admin/post-new.php?post_type=action-de-formation" target="_blank" class="new-session-link">';
        echo '<span class="dashicons dashicons-plus-alt"></span>Créer une nouvelle Session';
        echo '</a>';
        echo '</div>'; // fin section

        echo '</div>'; // fin container
        
        // Ajouter le script spécifique pour la gestion des variations en temps réel
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Liste des produits variables
            var variableProducts = <?php echo $variable_products_json; ?>;
            
            // Gestion du changement de produit
            $('#product_select').on('change', function() {
                var productId = $(this).val();
                var productType = $(this).find('option:selected').data('type');
                
                // Cacher le conteneur de variations par défaut
                $('#variations_container').hide();
                
                // Si c'est un produit variable, charger les variations
                if (productId && productType === 'variable') {
                    $('#product_loading').show();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'get_product_variations',
                            product_id: productId
                        },
                        success: function(response) {
                            $('#product_loading').hide();
                            
                            if (response.success && response.data.variations) {
                                // Mettre à jour le select des variations
                                var variationSelect = $('#variation_select');
                                variationSelect.empty();
                                variationSelect.append('<option value="">Choisir une variation</option>');
                                
                                $.each(response.data.variations, function(index, variation) {
                                    variationSelect.append('<option value="' + variation.id + '">' + variation.name + '</option>');
                                });
                                
                                // Afficher le conteneur des variations
                                $('#variations_container').show();
                            }
                        },
                        error: function() {
                            $('#product_loading').hide();
                            alert('Erreur lors du chargement des variations');
                        }
                    });
                }
            });
            
            // Gestion du bouton d'ajout
            $('#add_product_button').on('click', function() {
                $(this).prop('disabled', true).text('...');
                
                var productId = $('#product_select').val();
                var variationId = $('#variation_select').val();
                var productType = $('#product_select').find('option:selected').data('type');
                
                // Déterminer l'ID à utiliser (variation ou produit)
                var finalId = productId;
                if (productType === 'variable' && variationId) {
                    finalId = variationId;
                } else if (productType === 'variable' && !variationId) {
                    alert('Veuillez sélectionner une variation');
                    $(this).prop('disabled', false).text('Ajouter');
                    return;
                }
                
                if (!finalId) {
                    alert('Veuillez sélectionner un produit');
                    $(this).prop('disabled', false).text('Ajouter');
                    return;
                }
                
                var data = {
                    'action': 'replace_product_in_order',
                    'order_id': $('[name="order_id"]').val(),
                    'product_id': finalId
                };
                
                $.post(ajaxurl, data, function(response) {
                    if(response.success) {
                        alert('Produit ajouté dans la commande !');
                        location.reload();
                    } else {
                        alert('Erreur: ' + response.data.message);
                        $('#add_product_button').prop('disabled', false).text('Ajouter');
                    }
                }).fail(function() {
                    alert('Erreur lors du traitement de la requête.');
                    $('#add_product_button').prop('disabled', false).text('Ajouter');
                });
            });
        });
        </script>
        <?php
        return;
    }

    // ---------------------------------------------------------------------------------
    // CAS 2 : La commande a au moins un produit
    // ---------------------------------------------------------------------------------
    // On prend le premier item
    $first_item = reset( $items );
    $product_id = $first_item->get_product_id();
    $variation_id = $first_item->get_variation_id();
    $product    = wc_get_product( $variation_id ? $variation_id : $product_id );

    if ( ! $product ) {
        echo '<div class="no-items-message">Impossible de trouver le produit associé à cet article de commande.</div>';
        echo '</div>'; // fin container
        return;
    }

    // Récupère les éventuelles "actions-de-formation" liées à ce produit
    // Pour les variations, on cherche par rapport au produit parent
    $search_product_id = $variation_id ? $product->get_parent_id() : $product_id;
    $args_action = [
        'post_type'      => 'action-de-formation',
        'post_status'    => 'publish',
        'meta_key'       => 'we_startdate',
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',
        'meta_query'     => [
            [
                'key'     => 'fsbdd_relsessproduit',
                'value'   => $search_product_id,
                'compare' => '=',
            ],
        ],
        'posts_per_page' => -1,
    ];
    $actions = get_posts( $args_action );

    // Vérifie si ce produit a des actions associées
    $has_actions = ! empty( $actions );

    // Si pas d'actions, on montre le form "tous les produits"
    if ( ! $has_actions ) {
        echo '<div class="form-section">';
        if ( ! empty( $all_visible_products ) ) {
            echo '<form id="replace_product_form">';
            
            // Afficher les informations sur le produit actuel
            echo '<div class="current-product-info">';
            echo '<strong>Produit actuel:</strong> ' . esc_html($product->get_name());
            echo '</div>';
            
            // Ajouter la section de choix de catégorie si applicable
            $product_name = $product->get_name();
            $caces_code = get_caces_code_from_product_name($product_name);
            
            if ($caces_code) {
                // Récupérer les catégories actuelles de l'article
                $current_categories = wc_get_order_item_meta($first_item->get_id(), 'choix_categorie', true);
                $current_nb_categories = wc_get_order_item_meta($first_item->get_id(), 'nombre_categories', true);
                
                echo '<div class="category-selection-section" style="margin-top: 10px;">';
                echo '<div style="margin-bottom: 8px;"><strong>Catégories actuelles:</strong> ' . ($current_categories ? esc_html($current_categories) : 'Aucune') . '</div>';
                
                // Formulaire pour modifier les catégories
                echo '<form id="update_categories_form">';
                echo '<div class="form-row">';
                echo '<select name="new_categories" id="category_select" style="width: 100%; margin-bottom: 8px;">';
                echo '<option value="">Modifier les catégories</option>';
                
                // Récupérer les combinaisons disponibles pour ce produit
                $price_table = get_full_price_table();
                if (isset($price_table[$caces_code])) {
                    // Déterminer le niveau (initial ou recyclage) - par défaut initial
                    $niveau = 'initial';
                    if (stripos($product_name, 'recyclage') !== false) {
                        $niveau = 'recyclage';
                    }
                    
                    if (isset($price_table[$caces_code][$niveau]['combos'])) {
                        foreach ($price_table[$caces_code][$niveau]['combos'] as $combo) {
                            $categories = implode(', ', $combo['categories']);
                            $selected = ($categories === $current_categories) ? ' selected' : '';
                            echo '<option value="' . esc_attr($categories) . '"' . $selected . '>' . esc_html($categories) . '</option>';
                        }
                    }
                }
                
                // Option personnalisé
                echo '<option value="Personnalisé">Personnalisé</option>';
                
                echo '</select>';
                
                // Champs personnalisés (cachés par défaut)
                echo '<div id="custom-category-fields" class="custom-category-fields">';
                echo '<input type="text" name="custom_categories" id="custom_categories" placeholder="Catégories personnalisées" />';
                echo '<input type="number" name="custom_ut_pratique" id="custom_ut_pratique" placeholder="UT Pratiques" min="0" step="0.5" />';
                echo '<div class="note">Note: UT Théoriques = 1</div>';
                echo '</div>';
                echo '</div>';
                echo '<div class="form-row">';
                echo '<button type="button" id="update_categories_button" style="width: 100%;">Mettre à jour les catégories</button>';
                echo '</div>';
                echo '<input type="hidden" name="order_id" value="' . esc_attr($post->ID) . '">';
                echo '<input type="hidden" name="item_id" value="' . esc_attr($first_item->get_id()) . '">';
                echo '</form>';
                echo '</div>';
            }
            
            echo '<div class="form-row" style="margin-top: 8px;">';
            echo '<select name="similar_product_id" id="similar_product_select">';
            echo '<option value="">Remplacer par un autre produit</option>';
            foreach ( $all_visible_products as $prod ) {
                $prod_obj = wc_get_product( $prod->ID );
                if ( ! $prod_obj ) {
                    continue;
                }
                
                // Exclure les produits avec "définir" dans le nom
                if (stripos($prod_obj->get_name(), 'définir') !== false) {
                    continue;
                }
                
                // Marquer les produits variables
                $product_type = $prod_obj->get_type();
                $is_variable = ($product_type === 'variable') ? ' (Variable)' : '';
                
                echo '<option value="' . esc_attr( $prod->ID ) . '" data-type="' . esc_attr($product_type) . '">' 
                    . esc_html( $prod_obj->get_name() . $is_variable ) . '</option>';
            }
            echo '</select>';
            echo '<div id="similar_product_loading" class="loading-spinner" style="display: none;"></div>';
            echo '</div>';
            
            // Container pour les variations du produit de remplacement
            echo '<div id="similar_variations_container" class="variations-container">';
            echo '<div class="variations-title">Sélectionner une variation:</div>';
            echo '<div class="form-row">';
            echo '<select name="similar_variation_id" id="similar_variation_select">';
            echo '<option value="">Choisir une variation</option>';
            echo '</select>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="form-row" style="margin-top: 10px;">';
            echo '<button type="button" id="replace_product_button">OK</button>';
            echo '</div>';
            
            echo '<input type="hidden" name="order_id" value="' . esc_attr( $post->ID ) . '">';
            echo '</form>';
        } else {
            echo '<div class="no-items-message">Aucun produit visible dans le catalogue.</div>';
        }
        echo '</div>'; // fin section

    } else {
        // CAS où il y a bien des actions : on garde le formulaire de "similaires"

        // Préparation de la recherche pour produits similaires
        $parent_product = $variation_id ? wc_get_product($product->get_parent_id()) : $product;
        $search_title = $parent_product->get_title();
        $ignore_character = '>';
        $ignore_index = strpos( $search_title, $ignore_character );
        if ( $ignore_index !== false ) {
            $search_title = substr( $search_title, 0, $ignore_index );
        }

        $args = array(
            'post_type'      => array('product', 'product_variation'),
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'posts_per_page' => -1,
            's'              => $search_title,
        );
        $similar_products = get_posts( $args );

        echo '<div class="form-section">';
        
        // Afficher les informations sur le produit actuel
        echo '<div class="current-product-info">';
        echo '<strong>Produit actuel:</strong> ' . esc_html($product->get_name());
        echo '</div>';
        
        // Récupérer les informations pour la sélection de catégorie
        $product_name = $product->get_name();
        $caces_code = get_caces_code_from_product_name($product_name);
        $current_categories = '';
        $current_nb_categories = '';
        
        if ($caces_code) {
            // Récupérer les catégories actuelles de l'article
            $current_categories = wc_get_order_item_meta($first_item->get_id(), 'choix_categorie', true);
            $current_nb_categories = wc_get_order_item_meta($first_item->get_id(), 'nombre_categories', true);
            
            echo '<div style="margin-bottom: 8px;"><strong>Catégories actuelles:</strong> ' . ($current_categories ? esc_html($current_categories) : 'Aucune') . '</div>';
        }
        
        if ( empty( $similar_products ) ) {
            echo '<div class="no-items-message">Aucun produit similaire trouvé.</div>';
        } else {
            echo '<form id="replace_product_form">';
            echo '<div class="form-row" style="margin-top: 8px;">';
            echo '<select name="similar_product_id">';
            echo '<option value="">Options de produit</option>'; // Ligne par défaut
            
            // Ajouter la variation actuelle en premier
            $current_product_id = $variation_id ? $variation_id : $product_id;
            if ( $product->is_type('variation') ) {
                $variation_attributes = wc_get_formatted_variation($product->get_variation_attributes(), true);
                $simplified_attributes = str_replace(array('Catégorie(s):', 'Niveau:'), '', $variation_attributes);
                echo '<option value="' . esc_attr( $current_product_id ) . '" selected>' . esc_html( $simplified_attributes . ' (Actuel)' ) . '</option>';
            } else {
                echo '<option value="' . esc_attr( $current_product_id ) . '" selected>' . esc_html( $product->get_name() . ' (Actuel)' ) . '</option>';
            }
            
            foreach ( $similar_products as $similar_product ) {
                // Éviter de dupliquer la variation actuelle
                if ( $similar_product->ID == $current_product_id ) {
                    continue;
                }
                
                $product_obj = wc_get_product( $similar_product->ID );
                if ( ! $product_obj ) {
                    continue;
                }

                // Récupère les catégories pour exclure l'ID 336 si nécessaire
                $product_categories = wp_get_post_terms(
                    $product_obj->is_type('variation') ? $product_obj->get_parent_id() : $similar_product->ID,
                    'product_cat',
                    array('fields' => 'ids')
                );

                // On exclut la cat 336
                if ( in_array( 336, $product_categories ) ) {
                    continue;
                }

                $product_name = $product_obj->is_type('variation')
                    ? wc_get_product($product_obj->get_parent_id())->get_name()
                    : $product_obj->get_name();
                
                // On exclut tout produit dont le nom contient "définir"
                if ( stripos($product_name, 'définir') !== false ) {
                    continue;
                }

                // Affichage de l'option
                if ( $product_obj->is_type('variation') ) {
                    $variation_attributes   = wc_get_formatted_variation($product_obj->get_variation_attributes(), true);
                    // Nettoyage de certains libellés (optionnel)
                    $simplified_attributes  = str_replace(array('Catégorie(s):', 'Niveau:'), '', $variation_attributes);
                    echo '<option value="' . esc_attr( $similar_product->ID ) . '">' . esc_html( $simplified_attributes ) . '</option>';
                } else {
                    echo '<option value="' . esc_attr( $similar_product->ID ) . '">' . esc_html( $similar_product->post_title ) . '</option>';
                }
            }
            echo '</select>';
            echo '</div>';
            
            // Ajouter la sélection de catégorie si applicable
            if ($caces_code) {
                echo '<div class="form-row" style="margin-top: 8px;">';
                echo '<select name="new_categories" id="category_select_with_actions">';
                echo '<option value="">Choisir les catégories</option>';
                
                // Récupérer les combinaisons disponibles pour ce produit
                $price_table = get_full_price_table();
                if (isset($price_table[$caces_code])) {
                    // Déterminer le niveau (initial ou recyclage) - par défaut initial
                    $niveau = 'initial';
                    if (stripos($product_name, 'recyclage') !== false) {
                        $niveau = 'recyclage';
                    }
                    
                    if (isset($price_table[$caces_code][$niveau]['combos'])) {
                        foreach ($price_table[$caces_code][$niveau]['combos'] as $combo) {
                            $categories = implode(', ', $combo['categories']);
                            $selected = ($categories === $current_categories) ? ' selected' : '';
                            echo '<option value="' . esc_attr($categories) . '"' . $selected . '>' . esc_html($categories) . '</option>';
                        }
                    }
                }
                
                // Option personnalisé
                echo '<option value="Personnalisé">Personnalisé</option>';
                
                echo '</select>';
                
                // Champs personnalisés (cachés par défaut)
                echo '<div id="custom-category-fields-with-actions" class="custom-category-fields">';
                echo '<input type="text" name="custom_categories" id="custom_categories_with_actions" placeholder="Catégories personnalisées" />';
                echo '<input type="number" name="custom_ut_pratique" id="custom_ut_pratique_with_actions" placeholder="UT Pratiques" min="0" step="0.5" />';
                echo '<div class="note">Note: UT Théoriques = 1</div>';
                echo '</div>';
                echo '</div>';
            }
            
            echo '<div class="form-row" style="margin-top: 10px;">';
            echo '<button type="button" id="replace_product_button">Enregistrer</button>';
            echo '</div>';
            echo '<input type="hidden" name="order_id" value="' . esc_attr( $post->ID ) . '">';
            echo '<input type="hidden" name="item_id" value="' . esc_attr( $first_item->get_id() ) . '">';
            echo '<input type="hidden" name="preserve_associations" value="1">';
            echo '</form>';
        }
        echo '</div>'; // fin section
    }

    // --------------------------------------------------------------
    // Formulaire pour changer la session, seulement si on a un item
    // ET si on a des actions ($actions n'est pas vide)
    // --------------------------------------------------------------
    if ( $has_actions ) {
        echo '<div class="form-section">';
        if ( empty( $actions ) ) {
            echo '<div class="no-items-message">Aucune session disponible pour ce produit.</div>';
        } else {
            echo '<form id="change_action_form">';
            echo '<div class="form-row">';
            echo '<select name="action_id">';
            echo '<option value="">Sessions disponibles</option>'; // Ligne par défaut
            foreach ( $actions as $action ) {
                $cpt_id = $action->ID;

                $lieu      = get_post_meta($cpt_id, 'fsbdd_select_lieusession', true);
                $startdate = get_post_meta($cpt_id, 'we_startdate', true);
                $enddate   = get_post_meta($cpt_id, 'we_enddate', true);
                $numero    = get_the_title($cpt_id);

                $lieu_complet         = $lieu ? trim($lieu) : 'Adresse inconnue';
                $lieu_resume          = $lieu ? explode(',', $lieu)[0] : 'Lieu inconnu';
                $lieu_resume          = ucfirst(strtolower(trim($lieu_resume)));
                $startdate_formatted  = $startdate ? date_i18n('j F Y', $startdate) : 'Date non définie';
                $enddate_formatted    = $enddate ? date_i18n('j F Y', $enddate) : 'Date non définie';

                echo '<option value="' . esc_attr($cpt_id) . '" 
                    data-lieu-complet="' . esc_attr($lieu_complet) . '"
                    data-startdate="' . esc_attr($startdate_formatted) . '"
                    data-enddate="' . esc_attr($enddate_formatted) . '"
                    data-numero="' . esc_attr($numero) . '">'
                    . esc_html("{$lieu_resume}, {$startdate_formatted}, N°{$numero}") .
                    '</option>';
            }
            echo '</select>';
            echo '<button type="button" id="change_action_button">OK</button>';
            echo '</div>';
            echo '<input type="hidden" name="order_id" value="' . esc_attr( $post->ID ) . '">';
            echo '</form>';
        }
        echo '</div>'; // fin section
    }

    // ----------------------------------------------------------------
    // Lien pour créer une nouvelle session
    // ----------------------------------------------------------------
    echo '<div class="form-section">';
    echo '<a href="https://formationstrategique.fr/wp-admin/post-new.php?post_type=action-de-formation" target="_blank" class="new-session-link">';
    echo '<span class="dashicons dashicons-plus-alt"></span>Créer une nouvelle Session';
    echo '</a>';
    echo '</div>'; // Fin section

    echo '</div>'; // Fin du container
}

// -----------------------------------------------------
// JS pour remplacer le produit ou changer la session
// -----------------------------------------------------
add_action( 'admin_footer', 'add_replace_product_js' );
function add_replace_product_js() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {

        // Gestion des variations pour le produit de remplacement (cas sans actions)
        $('#similar_product_select').on('change', function() {
            var productId = $(this).val();
            var productType = $(this).find('option:selected').data('type');
            
            // Cacher le conteneur de variations par défaut
            $('#similar_variations_container').hide();
            
            // Si c'est un produit variable, charger les variations
            if (productId && productType === 'variable') {
                $('#similar_product_loading').show();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_product_variations',
                        product_id: productId
                    },
                    success: function(response) {
                        $('#similar_product_loading').hide();
                        
                        if (response.success && response.data.variations) {
                            // Mettre à jour le select des variations
                            var variationSelect = $('#similar_variation_select');
                            variationSelect.empty();
                            variationSelect.append('<option value="">Choisir une variation</option>');
                            
                            $.each(response.data.variations, function(index, variation) {
                                variationSelect.append('<option value="' + variation.id + '">' + variation.name + '</option>');
                            });
                            
                            // Afficher le conteneur des variations
                            $('#similar_variations_container').show();
                        }
                    },
                    error: function() {
                        $('#similar_product_loading').hide();
                        alert('Erreur lors du chargement des variations');
                    }
                });
            }
        });

        // Gestion de l'affichage des champs personnalisés
        $('#category_select').on('change', function() {
            if ($(this).val() === 'Personnalisé') {
                $('#custom-category-fields').show();
            } else {
                $('#custom-category-fields').hide();
                $('#custom_categories').val('');
                $('#custom_ut_pratique').val('');
            }
        });
        
        $('#category_select_with_actions').on('change', function() {
            if ($(this).val() === 'Personnalisé') {
                $('#custom-category-fields-with-actions').show();
            } else {
                $('#custom-category-fields-with-actions').hide();
                $('#custom_categories_with_actions').val('');
                $('#custom_ut_pratique_with_actions').val('');
            }
        });

        // Remplacer le produit dans la commande et mettre à jour les catégories
        $('#replace_product_button').on('click', function() {
            // Désactiver le bouton pendant le traitement
            $(this).prop('disabled', true).text('...');
            
            var productId = $('[name="similar_product_id"]').val();
            var variationId = $('[name="similar_variation_id"]').val();
            var productType = $('#similar_product_select').find('option:selected').data('type');
            var preserveAssociations = $('[name="preserve_associations"]').val();
            var newCategories = $('[name="new_categories"]').val();
            var orderId = $('[name="order_id"]').val();
            var itemId = $('[name="item_id"]').val();
            
            // Gérer les catégories personnalisées (cas avec actions)
            if (newCategories === 'Personnalisé') {
                var customCategories = $('#custom_categories_with_actions').val();
                var customUTPratique = $('#custom_ut_pratique_with_actions').val();
                
                if (!customCategories || !customUTPratique) {
                    alert('Veuillez remplir les catégories et UT pratiques personnalisées');
                    $(this).prop('disabled', false).text('Enregistrer');
                    return;
                }
                
                newCategories = customCategories;
            }
            
            // Déterminer l'ID à utiliser (variation ou produit)
            var finalId = productId;
            if (productType === 'variable' && variationId) {
                finalId = variationId;
            } else if (productType === 'variable' && !variationId) {
                alert('Veuillez sélectionner une variation');
                $(this).prop('disabled', false).text('Enregistrer');
                return;
            }

            if (!finalId) {
                alert('Veuillez sélectionner un produit');
                $(this).prop('disabled', false).text('Enregistrer');
                return;
            }

            // Fonction pour mettre à jour les catégories après le remplacement
            function updateCategories() {
                if (newCategories && itemId) {
                    var categoryData = {
                        'action': 'update_order_item_categories',
                        'order_id': orderId,
                        'item_id': itemId,
                        'categories': newCategories
                    };
                    
                    // Ajouter les données personnalisées si nécessaire
                    if ($('#category_select_with_actions').val() === 'Personnalisé') {
                        categoryData.is_custom_mode = true;
                        categoryData.custom_ut_pratique = $('#custom_ut_pratique_with_actions').val();
                        categoryData.custom_ut_theorique = 1; // Toujours 1 pour les théoriques
                    }
                    
                    $.post(ajaxurl, categoryData, function(categoryResponse) {
                        if(categoryResponse.success) {
                            alert('Produit et catégories mis à jour avec succès !');
                            location.reload();
                        } else {
                            alert('Produit remplacé mais erreur lors de la mise à jour des catégories: ' + categoryResponse.data);
                            location.reload();
                        }
                    }).fail(function() {
                        alert('Produit remplacé mais erreur lors de la mise à jour des catégories.');
                        location.reload();
                    });
                } else {
                    alert('Produit remplacé avec succès !');
                    location.reload();
                }
            }

            var data = {
                'action': 'replace_product_in_order',
                'order_id': orderId,
                'product_id': finalId,
                'preserve_associations': preserveAssociations || '0'
            };

            $.post(ajaxurl, data, function(response) {
                if(response.success) {
                    // Mettre à jour les catégories après le remplacement réussi
                    updateCategories();
                } else {
                    alert('Erreur: ' + response.data.message);
                    $('#replace_product_button').prop('disabled', false).text('Enregistrer');
                }
            }).fail(function() {
                alert('Erreur lors du traitement de la requête.');
                $('#replace_product_button').prop('disabled', false).text('Enregistrer');
            });
        });

        // Changer la session
        $('#change_action_button').on('click', function() {
            // Désactiver le bouton pendant le traitement
            $(this).prop('disabled', true).text('...');
            
            var data = {
                'action': 'change_action_in_order',
                'order_id': $('[name="order_id"]').val(),
                'action_id': $('[name="action_id"]').val()
            };

            if (!data.action_id) {
                alert('Veuillez sélectionner une session');
                $(this).prop('disabled', false).text('OK');
                return;
            }

            $.post(ajaxurl, data, function(response) {
                if(response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Erreur: ' + response.data.message);
                    $('#change_action_button').prop('disabled', false).text('OK');
                }
            }).fail(function() {
                alert('Erreur lors de la mise à jour.');
                $('#change_action_button').prop('disabled', false).text('OK');
            });
        });
        
        // Gestion de la mise à jour des catégories (produits sans actions)
        $('#update_categories_button').on('click', function() {
            var button = $(this);
            var originalText = button.text();
            button.prop('disabled', true).text('Mise à jour...');
            
            var categories = $('#category_select').val();
            var orderId = $('[name="order_id"]').val();
            var itemId = $('[name="item_id"]').val();
            
            // Gestion du mode personnalisé
            if (categories === 'Personnalisé') {
                var customCategories = $('#custom_categories').val();
                var customUTPratique = $('#custom_ut_pratique').val();
                
                if (!customCategories || !customUTPratique) {
                    alert('Veuillez remplir les catégories et UT pratiques personnalisées');
                    button.prop('disabled', false).text(originalText);
                    return;
                }
                
                categories = customCategories;
            }
            
            if (!categories) {
                alert('Veuillez sélectionner des catégories');
                button.prop('disabled', false).text(originalText);
                return;
            }
            
            var ajaxData = {
                action: 'update_order_item_categories',
                order_id: orderId,
                item_id: itemId,
                categories: categories
            };
            
            // Ajouter les données personnalisées si nécessaire
            if ($('#category_select').val() === 'Personnalisé') {
                ajaxData.is_custom_mode = true;
                ajaxData.custom_ut_pratique = $('#custom_ut_pratique').val();
                ajaxData.custom_ut_theorique = 1; // Toujours 1 pour les théoriques
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    if (response.success) {
                        alert('Catégories mises à jour avec succès!');
                        location.reload();
                    } else {
                        alert('Erreur: ' + response.data.message);
                        button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert('Erreur lors de la mise à jour des catégories');
                    button.prop('disabled', false).text(originalText);
                }
            });
        });

    });
    </script>
    <?php
}

// ------------------------------------------------------------------
// Nouvel endpoint AJAX pour récupérer les variations d'un produit
// ------------------------------------------------------------------
add_action('wp_ajax_get_product_variations', 'get_product_variations_ajax');
function get_product_variations_ajax() {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    if (!$product_id) {
        wp_send_json_error(['message' => 'ID de produit manquant']);
    }
    
    $product = wc_get_product($product_id);
    
    if (!$product || !$product->is_type('variable')) {
        wp_send_json_error(['message' => 'Ce n\'est pas un produit variable']);
    }
    
    $variations = [];
    $available_variations = $product->get_available_variations();
    
    foreach ($available_variations as $variation) {
        $variation_obj = wc_get_product($variation['variation_id']);
        if (!$variation_obj) continue;
        
        $variation_attributes = wc_get_formatted_variation($variation_obj->get_variation_attributes(), true);
        // Nettoyage des libellés si nécessaire
        $simplified_attributes = str_replace(array('Catégorie(s):', 'Niveau:'), '', $variation_attributes);
        
        $variations[] = [
            'id' => $variation['variation_id'],
            'name' => $simplified_attributes
        ];
    }
    
    wp_send_json_success(['variations' => $variations]);
}

// ------------------------------------------------------------------
// Handler AJAX pour remplacer le produit dans la commande - VERSION OPTIMISÉE
// ------------------------------------------------------------------
add_action( 'wp_ajax_replace_product_in_order', 'handle_replace_product_in_order' );
function handle_replace_product_in_order() {
    $order_id   = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $preserve_associations = isset($_POST['preserve_associations']) ? $_POST['preserve_associations'] === '1' : false;

    if ( !$order_id || !$product_id ) {
        wp_send_json_error(['message' => 'Paramètres manquants.']);
    }

    $order = wc_get_order( $order_id );
    if (!$order) {
        wp_send_json_error(['message' => 'Commande introuvable.']);
    }

    $items = $order->get_items();

    // Si la commande est vide, on ajoute directement le produit
    if ( empty($items) ) {
        $product = wc_get_product( $product_id );
        if (!$product) {
            wp_send_json_error(['message' => 'Produit introuvable.']);
        }
        $order->add_product( $product, 1 );
        $order->calculate_totals();
        $order->save();
        wp_send_json_success(['message' => 'Produit ajouté avec succès.']);
    }

    // Si on veut préserver les associations (cas avec action-de-formation)
    if ( $preserve_associations ) {
        $first_item = reset($items);
        
        // Sauvegarder toutes les métadonnées importantes AVANT la modification
        $existing_meta = [];
        $meta_data = $first_item->get_meta_data();
        
        foreach ( $meta_data as $meta ) {
            // Préserver toutes les métadonnées liées aux sessions/formations
            if ( strpos($meta->key, 'fsbdd_') === 0 || 
                 strpos($meta->key, 'we_') === 0 || 
                 in_array($meta->key, ['session_data']) ) {
                $existing_meta[$meta->key] = $meta->value;
            }
        }
        
        // Récupérer le nouveau produit
        $new_product = wc_get_product( $product_id );
        if (!$new_product) {
            wp_send_json_error(['message' => 'Nouveau produit introuvable.']);
        }
        
        // Modifier directement l'item existant au lieu de le supprimer/recréer
        $first_item->set_product_id( $new_product->is_type('variation') ? $new_product->get_parent_id() : $product_id );
        $first_item->set_variation_id( $new_product->is_type('variation') ? $product_id : 0 );
        $first_item->set_name( $new_product->get_name() );
        
        // Restaurer toutes les métadonnées préservées
        foreach ( $existing_meta as $key => $value ) {
            $first_item->update_meta_data( $key, $value );
        }
        
        $first_item->save();
        
    } else {
        // Cas normal : remplacer complètement l'item (sans associations à préserver)
        $first_item = reset($items);
        $order->remove_item($first_item->get_id());

        $product = wc_get_product( $product_id );
        if (!$product) {
            wp_send_json_error(['message' => 'Produit introuvable.']);
        }

        // Ajouter le nouveau produit
        $item_id     = $order->add_product( $product, 1 );
        $product_meta = $product->get_meta_data();
        $order_item   = new WC_Order_Item_Product( $item_id );

        // Copier des métadonnées essentielles si nécessaire
        foreach ( $product_meta as $meta ) {
            if ( in_array( $meta->key, ['essential_meta_1', 'essential_meta_2', 'essential_meta_3'] ) ) {
                $order_item->add_meta_data( $meta->key, $meta->value );
            }
        }
        $order_item->save();
    }

    // Recalculer les totaux
    $order->calculate_totals();
    $order->save();

    wp_send_json_success(['message' => 'Produit remplacé avec succès.']);
}

// ------------------------------------------------------------------
// Handler AJAX pour changer la session
// ------------------------------------------------------------------
add_action('wp_ajax_change_action_in_order', 'change_action_in_order');
function change_action_in_order() {
    $order_id  = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $action_id = isset($_POST['action_id']) ? intval($_POST['action_id']) : 0;

    if (!$order_id || !$action_id) {
        wp_send_json_error(['message' => 'ID de commande ou d\'action manquant.']);
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error(['message' => 'Commande introuvable.']);
    }

    $items = $order->get_items();
    if (empty($items)) {
        wp_send_json_error(['message' => 'Aucun produit dans la commande.']);
    }

    $first_item = reset($items);
    $product_id = $first_item->get_product_id();

    // Nettoyer les anciennes convocations
    global $wpdb;
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}postmeta WHERE post_id = %d AND meta_key LIKE 'fsbdd_convoc_%%'",
        $order_id
    ));

    // Mettre à jour les métadonnées de la nouvelle session
    $lieu_complet = get_post_meta($action_id, 'fsbdd_select_lieusession', true) ?: 'Adresse inconnue';
    $startdate    = get_post_meta($action_id, 'we_startdate', true);
    $enddate      = get_post_meta($action_id, 'we_enddate', true);
    $numero       = get_the_title($action_id);

    $startdate_formatted = $startdate ? date_i18n('j F Y', $startdate) : 'Date non définie';
    $enddate_formatted   = $enddate ? date_i18n('j F Y', $enddate) : 'Date non définie';

    // Mettre à jour les métadonnées INDIVIDUELLES comme le frontend
    $first_item->update_meta_data('fsbdd_relsessaction_cpt_produit', $action_id);
    $first_item->update_meta_data('we_startdate', $startdate_formatted);
    $first_item->update_meta_data('we_enddate', $enddate_formatted);
    $first_item->update_meta_data('fsbdd_actionum', $numero);
    $first_item->update_meta_data('fsbdd_select_lieuforminter', $lieu_complet);

    // Supprimer l'ancienne métadonnée groupée si elle existe
    $first_item->delete_meta_data('session_data');

    // Ajouter de nouvelles convocations si des dates sont définies pour la nouvelle session
    $planning = get_post_meta($action_id, 'fsbdd_planning', true);
    $planning = maybe_unserialize($planning);

    if (!empty($planning) && is_array($planning)) {
        foreach ($planning as $day) {
            if (!empty($day['fsbdd_planjour'])) {
                $date = date('d/m/Y', strtotime($day['fsbdd_planjour']));
                update_post_meta($order_id, 'fsbdd_convoc_' . $date, '1'); // Coché par défaut
            }
        }
    }

    $first_item->save();
    $order->calculate_totals();
    $order->save();

    wp_send_json_success(['message' => 'La session a été changée avec succès et les convocations mises à jour.']);
}

// ------------------------------------------------------------------
// Handler AJAX pour mettre à jour les catégories d'un article de commande
// ------------------------------------------------------------------
add_action('wp_ajax_update_order_item_categories', 'update_order_item_categories');
function update_order_item_categories() {
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $categories = isset($_POST['categories']) ? sanitize_text_field($_POST['categories']) : '';
    $is_custom_mode = isset($_POST['is_custom_mode']) ? (bool)$_POST['is_custom_mode'] : false;
    $custom_ut_pratique = isset($_POST['custom_ut_pratique']) ? floatval($_POST['custom_ut_pratique']) : 0;
    $custom_ut_theorique = isset($_POST['custom_ut_theorique']) ? floatval($_POST['custom_ut_theorique']) : 1;

    if (!$order_id || !$item_id || !$categories) {
        wp_send_json_error(['message' => 'Paramètres manquants.']);
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error(['message' => 'Commande introuvable.']);
    }

    // Récupérer l'article de commande
    $order_item = $order->get_item($item_id);
    if (!$order_item) {
        wp_send_json_error(['message' => 'Article de commande introuvable.']);
    }

    // Calculer le nombre de catégories
    $categories_array = array_map('trim', explode(',', $categories));
    $nb_categories = count($categories_array);

    // Mettre à jour les métadonnées
    $order_item->update_meta_data('choix_categorie', $categories);
    $order_item->update_meta_data('nombre_categories', $nb_categories);

    // Gestion des UT selon le mode
    if ($is_custom_mode) {
        // Mode personnalisé : utiliser les valeurs fournies
        $order_item->update_meta_data('ut_pratique', $custom_ut_pratique);
        $order_item->update_meta_data('ut_theorique', $custom_ut_theorique);
        
        // Log pour debug
        error_log("UT Debug Side Commande (Mode personnalisé) - Categories: $categories, UT Pratique: $custom_ut_pratique, UT Théorique: $custom_ut_theorique");
    } else {
        // Mode normal : calculer les UT à partir de la grille
        if (function_exists('get_caces_code_from_product_name') && function_exists('get_ut_for_category')) {
            $product = $order_item->get_product();
            if ($product) {
                $product_name = $product->get_name();
                $formation_key = get_caces_code_from_product_name($product_name);
                
                if ($formation_key) {
                    $total_ut_pratique = 0;
                    $total_ut_theorique = 0;
                    
                    foreach ($categories_array as $category) {
                        $ut_data = get_ut_for_category($formation_key, $category);
                        if ($ut_data && is_array($ut_data)) {
                            $total_ut_pratique += floatval($ut_data['ut_pratique'] ?? 0);
                            $total_ut_theorique += floatval($ut_data['ut_theorique'] ?? 0);
                        }
                    }
                    
                    // Toujours sauvegarder les UT, même si elles sont à 0
                    $order_item->update_meta_data('ut_pratique', $total_ut_pratique);
                    $order_item->update_meta_data('ut_theorique', $total_ut_theorique);
                    
                    // Log pour debug
                    error_log("UT Debug Side Commande (Mode normal) - Formation: $formation_key, Categories: $categories, UT Pratique: $total_ut_pratique, UT Théorique: $total_ut_theorique");
                } else {
                    error_log("UT Debug Side Commande - Formation key not found for product: $product_name");
                }
            }
        }
    }

    // Sauvegarder les modifications
    $order_item->save();
    $order->calculate_totals();
    $order->save();

    wp_send_json_success(['message' => 'Catégories mises à jour avec succès.']);
}