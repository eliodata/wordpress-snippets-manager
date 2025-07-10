<?php
/**
 * Snippet ID: 186
 * Name: Custom html fournisseurs salles produits liés pour couts planning
 * Description: 
 * @active true
 */


// Fonction callback pour le champ custom HTML des produits
function render_products_select_field($meta, $field) {
    // Générer un ID unique pour ce select
    $unique_id = uniqid('products_select_');
    
    $html = '<div class="products-select-wrapper" data-unique-id="' . $unique_id . '">';
    $html .= '<select id="' . $unique_id . '" class="products-select">';
    $html .= '<option value="">Sélectionnez d\'abord un fournisseur</option>';
    $html .= '</select>';
    $html .= '</div>';
    
    return $html;
}

// AJAX Handler pour récupérer les produits du fournisseur
add_action('wp_ajax_get_fournisseur_products', 'get_fournisseur_products_ajax');
add_action('wp_ajax_nopriv_get_fournisseur_products', 'get_fournisseur_products_ajax');

function get_fournisseur_products_ajax() {
    if (!isset($_POST['fournisseur_id']) || !is_numeric($_POST['fournisseur_id'])) {
        wp_send_json_error('Invalid request');
        return;
    }
    
    $fournisseur_id = intval($_POST['fournisseur_id']);
    
    // Récupérer les données sérialisées du champ fsbdd_gpeprodts
    $products_data = get_post_meta($fournisseur_id, 'fsbdd_gpeprodts', true);
    
    $response = array();
    
    if (!empty($products_data) && is_array($products_data)) {
        foreach ($products_data as $index => $product) {
            if (is_array($product) && count($product) >= 2) {
                $response[] = array(
                    'value' => $index,
                    'label' => $product[0], // Nom du produit
                    'price' => floatval(str_replace(',', '.', $product[1]))  // Prix du produit converti
                );
            }
        }
    }
    
    wp_send_json_success($response);
}

// CSS et JavaScript
add_action('admin_enqueue_scripts', 'enqueue_products_select_scripts');

function enqueue_products_select_scripts($hook) {
    if ('post.php' != $hook && 'post-new.php' != $hook) {
        return;
    }
    
    global $post_type;
    if ($post_type !== 'action-de-formation') {
        return;
    }
    
    // CSS amélioré pour une meilleure lisibilité
    wp_add_inline_style('wp-admin', '
        .products-select-wrapper {
            position: relative;
            display: block !important;
        }
        
        .products-select {
            width: 100% !important;
            padding: 6px 10px !important;
            border: 1px solid #8c8f94 !important;
            border-radius: 3px !important;
            background-color: #fff !important;
            background-image: linear-gradient(to bottom, #fff, #f9f9f9) !important;
            font-size: 13px !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important;
            line-height: 1.4 !important;
            color: #2c3338 !important;
            height: 30px !important;
            box-sizing: border-box !important;
            box-shadow: inset 0 1px 2px rgba(0,0,0,.07) !important;
            cursor: pointer !important;
        }
        
        .products-select:hover {
            border-color: #8c8f94 !important;
        }
        
        .products-select:focus {
            border-color: #2271b1 !important;
            box-shadow: 0 0 0 1px #2271b1 !important;
            outline: 2px solid transparent !important;
        }
        
        .products-select:disabled {
            background-color: #f6f7f7 !important;
            background-image: none !important;
            color: #a7aaad !important;
            border-color: #dcdcde !important;
            cursor: default !important;
            box-shadow: none !important;
        }
        
        /* Style des options */
        .products-select option {
            padding: 3px 6px !important;
            color: #2c3338 !important;
            background-color: #fff !important;
        }
        
        .products-select option:hover,
        .products-select option:focus {
            background-color: #2271b1 !important;
            color: #fff !important;
        }
        
        /* Masquer les champs cachés */
        .hidden-product-id .rwmb-field,
        .hidden-product-name .rwmb-field,
        .hidden-product-price .rwmb-field {
            display: none !important;
        }
        
        /* Assurer que le select soit bien visible dans le layout */
        .products-select-container .rwmb-input {
            min-height: 30px !important;
        }
    ');
}

// JavaScript pour la fonctionnalité (sans debug)
add_action('admin_footer', 'add_products_select_js');

function add_products_select_js() {
    global $post_type;
    if ($post_type !== 'action-de-formation') {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        
        // Fonction pour trouver les champs cachés dans le même groupe
        function findHiddenFields($container) {
            var $group = $container.closest('.rwmb-group-clone');
            return {
                productId: $group.find('input[name*="fsbdd_selected_product_id"]'),
                productName: $group.find('input[name*="fsbdd_selected_product_name"]'),
                productPrice: $group.find('input[name*="fsbdd_selected_product_price"]')
            };
        }
        
        // Fonction pour charger les produits
        function loadProducts($container, fournisseurId, restoreSelection = false) {
            var $productsSelect = $container.find('.products-select');
            var hiddenFields = findHiddenFields($container);
            
            $productsSelect.empty().append('<option value="">Chargement...</option>').prop('disabled', true);
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'get_fournisseur_products',
                    fournisseur_id: fournisseurId,
                    nonce: '<?php echo wp_create_nonce('products_select_nonce'); ?>'
                },
                success: function(response) {
                    $productsSelect.empty();
                    
                    if (response.success && response.data && response.data.length > 0) {
                        $productsSelect.append('<option value="">Sélectionnez un produit</option>');
                        
                        $.each(response.data, function(index, product) {
                            $productsSelect.append(
                                '<option value="' + product.value + '" data-price="' + product.price + '" data-name="' + product.label + '">' + 
                                product.label + ' (' + product.price + '€)</option>'
                            );
                        });
                        
                        $productsSelect.prop('disabled', false);
                        
                        // Restaurer la sélection précédente si elle existe
                        if (restoreSelection && hiddenFields.productId.val()) {
                            var savedId = hiddenFields.productId.val();
                            var savedName = hiddenFields.productName.val();
                            var savedPrice = hiddenFields.productPrice.val();
                            
                            $productsSelect.val(savedId);
                            
                            // Si la valeur n'existe plus, l'ajouter
                            if ($productsSelect.val() !== savedId && savedName) {
                                $productsSelect.append(
                                    '<option value="' + savedId + '" data-price="' + savedPrice + '" data-name="' + savedName + '" selected>' + 
                                    savedName + ' (' + savedPrice + '€)</option>'
                                );
                                $productsSelect.val(savedId);
                            }
                        }
                        
                    } else {
                        $productsSelect.append('<option value="">Aucun produit disponible</option>');
                    }
                },
                error: function(xhr, status, error) {
                    $productsSelect.empty().append('<option value="">Erreur de chargement</option>');
                }
            });
        }
        
        // Gérer le changement de fournisseur
        $(document).on('change', '.fournisseur-select select, input[name*="fsbdd_user_foursalle"]', function() {
            var fournisseurId = $(this).val();
            var $container = $(this).closest('.rwmb-group-clone').find('.products-select-wrapper');
            
            if (fournisseurId && fournisseurId !== '') {
                loadProducts($container, fournisseurId, false);
            } else {
                var $productsSelect = $container.find('.products-select');
                var hiddenFields = findHiddenFields($container);
                
                $productsSelect.empty().append('<option value="">Sélectionnez d\'abord un fournisseur</option>').prop('disabled', true);
                
                // Reset des champs cachés
                hiddenFields.productId.val('');
                hiddenFields.productName.val('');
                hiddenFields.productPrice.val('');
            }
        });
        
        // Gérer le changement de produit
        $(document).on('change', '.products-select', function() {
            var $select = $(this);
            var $selectedOption = $select.find(':selected');
            var $container = $select.closest('.products-select-wrapper');
            var hiddenFields = findHiddenFields($container);
            
            var selectedValue = $select.val();
            var selectedPrice = $selectedOption.data('price') || '';
            var selectedName = $selectedOption.data('name') || $selectedOption.text().split(' (')[0];
            
            // Normaliser le prix : garder le format original (avec virgule si nécessaire)
            // Le champ est maintenant de type texte, donc on peut conserver les virgules
            if (selectedPrice && typeof selectedPrice === 'number') {
                selectedPrice = selectedPrice.toString().replace('.', ',');
            }
            
            // Sauvegarder dans les champs cachés MetaBox
            hiddenFields.productId.val(selectedValue);
            hiddenFields.productName.val(selectedName);
            hiddenFields.productPrice.val(selectedPrice);
        });
        
        // Initialisation au chargement de la page
        setTimeout(function() {
            $('.fournisseur-select select, input[name*="fsbdd_user_foursalle"]').each(function() {
                var fournisseurId = $(this).val();
                if (fournisseurId) {
                    var $container = $(this).closest('.rwmb-group-clone').find('.products-select-wrapper');
                    if ($container.length > 0) {
                        loadProducts($container, fournisseurId, true);
                    }
                }
            });
        }, 1500);
        
        // Gérer les nouveaux groupes clonés
        $(document).on('clone_instance', '.rwmb-group-clone', function() {
            var $newGroup = $(this);
            setTimeout(function() {
                var $productsSelect = $newGroup.find('.products-select');
                var hiddenFields = findHiddenFields($newGroup.find('.products-select-wrapper'));
                
                // Reset du nouveau groupe
                $productsSelect.empty().append('<option value="">Sélectionnez d\'abord un fournisseur</option>').prop('disabled', true);
                hiddenFields.productId.val('');
                hiddenFields.productName.val('');
                hiddenFields.productPrice.val('');
            }, 100);
        });
    });
    </script>
    <?php
}

// Fonctions utilitaires pour récupérer les données
function get_session_fournisseurs_products($post_id) {
    $fournisseurs_data = get_post_meta($post_id, 'fournisseur_salle', true);
    $result = array();
    
    if (!empty($fournisseurs_data) && is_array($fournisseurs_data)) {
        foreach ($fournisseurs_data as $index => $fournisseur) {
            $fournisseur_id = $fournisseur['fsbdd_user_foursalle'] ?? '';
            $product_id = $fournisseur['fsbdd_selected_product_id'] ?? '';
            $product_name = $fournisseur['fsbdd_selected_product_name'] ?? '';
            $product_price = $fournisseur['fsbdd_selected_product_price'] ?? '';
            $dispo = $fournisseur['fsbdd_dispjourform'] ?? '';
            $etat = $fournisseur['fsbdd_okformatr'] ?? '';
            $infos = $fournisseur['fsbdd_commplanfourn'] ?? '';
            
            if ($fournisseur_id) {
                $result[] = array(
                    'index' => $index,
                    'fournisseur_id' => $fournisseur_id,
                    'fournisseur_name' => get_the_title($fournisseur_id),
                    'product_id' => $product_id,
                    'product_name' => $product_name,
                    'product_price' => $product_price,
                    'disponibilite' => $dispo,
                    'etat' => $etat,
                    'infos' => $infos
                );
            }
        }
    }
    
    return $result;
}

function calculate_total_products_cost($post_id) {
    $fournisseurs = get_session_fournisseurs_products($post_id);
    $total = 0;
    
    foreach ($fournisseurs as $fournisseur) {
        if (!empty($fournisseur['product_price']) && is_numeric($fournisseur['product_price'])) {
            $total += floatval($fournisseur['product_price']);
        }
    }
    
    return $total;
}

// Fonction de test simplifiée (optionnelle - supprimez si vous ne voulez pas la notice)
add_action('admin_notices', 'test_products_save');
function test_products_save() {
    if (isset($_GET['post']) && get_post_type($_GET['post']) === 'action-de-formation') {
        $fournisseurs = get_session_fournisseurs_products($_GET['post']);
        if (!empty($fournisseurs)) {
            $count = 0;
            foreach ($fournisseurs as $f) {
                if (!empty($f['product_name'])) {
                    $count++;
                }
            }
            if ($count > 0) {
                echo '<div class="notice notice-success is-dismissible"><p>✅ <strong>' . $count . ' produit(s) sauvegardé(s)</strong></p></div>';
            }
        }
    }
}