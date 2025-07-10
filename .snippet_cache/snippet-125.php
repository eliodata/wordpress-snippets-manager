<?php
/**
 * Snippet ID: 125
 * Name: Page création de commande admin devis ajouter nouvelle
 * Description: 
 * @active false
 */


/**
 * 1) Enregistrer le statut personnalisé "Devis Proposition"
 */
add_action('init', 'register_devisproposition_status');
function register_devisproposition_status() {
    register_post_status('wc-devisproposition', array(
        'label'                     => 'Devis Proposition',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop(
            'Devis proposition <span class="count">(%s)</span>',
            'Devis proposition <span class="count">(%s)</span>'
        ),
    ));
}

/**
 * 2) Ajouter le statut "wc-devisproposition" dans la liste des statuts WooCommerce
 */
add_filter('wc_order_statuses', 'add_devisproposition_to_order_statuses');
function add_devisproposition_to_order_statuses($order_statuses) {
    $new_statuses = [];

    // On insère le statut dans la liste où on le souhaite.
    // Ici, on le place juste après "wc-on-hold" par exemple.
    foreach ($order_statuses as $key => $status) {
        $new_statuses[$key] = $status;
        if ('wc-on-hold' === $key) {
            $new_statuses['wc-devisproposition'] = 'Devis Proposition';
        }
    }

    return $new_statuses;
}

/**
 * 3) Ajout d'une page "Nouvelle commande" sans l'afficher dans le menu latéral
 */
add_action('admin_menu', 'register_custom_order_wizard_page');
function register_custom_order_wizard_page() {
    add_submenu_page(
        null, // Pas de menu parent - ne s'affichera dans aucun menu
        'Créer une commande',
        'Nouvelle commande',
        'manage_woocommerce', // Capacité de base
        'my-custom-order-wizard',
        'my_custom_order_wizard_page_callback'
    );
}

/**
 * 3b) Ajout du lien dans la barre d'administration supérieure
 */
add_action('admin_bar_menu', 'add_custom_order_wizard_to_admin_bar', 999); // Priorité 999 pour le placer à la fin
function add_custom_order_wizard_to_admin_bar($admin_bar) {
    // Vérification des rôles autorisés
    $current_user = wp_get_current_user();
    $allowed_roles = array('administrator', 'referent', 'compta');
    
    // Vérifier si l'utilisateur courant a l'un des rôles autorisés
    $can_access = false;
    foreach ($allowed_roles as $role) {
        if (in_array($role, (array) $current_user->roles)) {
            $can_access = true;
            break;
        }
    }
    
    // Ajouter le lien seulement si l'utilisateur a accès
    if ($can_access) {
        $admin_bar->add_node(array(
            'id'    => 'custom-order-wizard',
            'title' => 'Nouvelle commande',
            'href'  => admin_url('admin.php?page=my-custom-order-wizard'),
            'meta'  => array(
                'title' => 'Créer une commande',
            )
        ));
    }
}


/**
 * 4) Callback pour afficher l'assistant (8 étapes)
 */
function my_custom_order_wizard_page_callback() {
    // On récupère une liste de clients WP
    $customers = get_users([
        'role__in' => ['customer', 'subscriber']
    ]);
    ?>
    <style>
        .wrap {
            font-family: Arial, sans-serif;
            margin: 20px;
            max-width: 800px;
        }
        h1, h2 {
            color: #333;
        }
        h2 {
            border-bottom: 2px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        h2 .dashicons {
            margin-right: 10px;
            color: #0073aa;
        }
        select, input[type="text"], input[type="number"] {
            width: calc(100% - 40px);
            padding: 10px;
            margin: 10px 20px 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input[type="number"] {
            width: 80px;
        }
        button {
            background-color: #0073aa;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #005177;
        }
        .stagiaire-line {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .stagiaire-fields {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .stagiaire-fields input {
            flex: 1;
            width: auto !important; /* Surcharge la largeur globale */
            padding: 8px;
            margin: 0 !important; /* Reset des marges */
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .champ-dropdown-categorie {
            display: flex;
            align-items: center;
            margin-top: 20px;
        }
        .champ-dropdown-categorie select {
            flex: 1;
        }
        .champ-dropdown-categorie button {
            margin-left: 10px;
        }
        .frais-fields {
            display: flex;
            gap: 10px;
            margin: 15px 0;
        }
        .frais-fields input {
            flex: 1;
            width: auto !important;
            padding: 8px;
            margin: 0 !important;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
    </style>
    
    <div class="wrap">
        <h1>Création d'une commande personnalisée</h1>

        <!-- Étape 1 : Sélection produit -->
        <div id="step-1">
            <h2><span class="dashicons dashicons-cart"></span> Étape 1 : Choisir un produit</h2>
            <select id="select-product">
                <option value="">-- Choisir --</option>
                <?php
                $products = get_posts([
                    'post_type'   => 'product',
                    'numberposts' => -1,
                    'tax_query'   => [
                        [
                            'taxonomy' => 'product_visibility',
                            'field'    => 'name',
                            'terms'    => ['exclude-from-catalog'],
                            'operator' => 'NOT IN',
                        ]
                    ],
                    'orderby' => 'title',
                    'order'   => 'ASC',
                ]);
                foreach ($products as $p) {
                    $pobj = wc_get_product($p->ID);
                    if (!$pobj) continue;
                    echo '<option value="' . $p->ID . '">' . esc_html($pobj->get_name()) . '</option>';
                }
                ?>
            </select>
            <button id="go-step-2">Suivant</button>
        </div>

        <!-- Étape 2 : Variation -->
        <div id="step-2" style="display:none;">
            <h2><span class="dashicons dashicons-forms"></span> Étape 2 : Niveau</h2>
            <select id="select-variation">
                <!-- Rempli en AJAX -->
            </select>
            <button id="go-step-3">Suivant</button>
        </div>

        <!-- Étape 3 : Catégorie(s) -->
        <div id="step-3" style="display:none;">
            <h2><span class="dashicons dashicons-category"></span> Étape 3 : Catégorie(s)</h2>
            <div class="champ-dropdown-categorie">
                <select id="choix_categorie" name="choix_categorie">
                    <option value="">-- Sélectionnez une option --</option>
                </select>
                <button id="go-step-4">Suivant</button>
            </div>
        </div>

        <!-- Étape 4 : Session (Action de formation) -->
        <div id="step-4" style="display:none;">
            <h2><span class="dashicons dashicons-calendar"></span> Étape 4 : Sélectionner la session</h2>
            <select id="select-action-formation"></select>
            <button id="go-step-5">Suivant</button>
        </div>

        <!-- Étape 5 : Effectif + Stagiaires -->
        <div id="step-5" style="display:none;">
            <h2><span class="dashicons dashicons-groups"></span> Étape 5 : Effectif + Stagiaires</h2>
            <label>Effectif :</label>
            <input type="number" id="input-effectif" value="0" min="0" max="50" />
            <div id="stagiaires-container"></div>
            <button id="go-step-6">Suivant</button>
        </div>

        <!-- Étape 6 : Coût unitaire HT + Frais -->
        <div id="step-6" style="display:none;">
            <h2><span class="dashicons dashicons-money"></span> Étape 6 : Détails financiers</h2>
            <label>Coût unitaire HT :</label>
            <input type="text" id="input-unit-price" value="" />
            
            <h3>Frais de formation</h3>
            <div class="frais-fields">
                <input type="text" id="input-fraisclient" placeholder="Montant frais client (HT)" />
                <input type="text" id="input-datefrais" placeholder="Date frais client (jj/mm/aaaa)" />
                <input type="text" id="input-nomfrais" placeholder="Dénomination des frais sur facture" />
            </div>
            <button id="go-step-7">Suivant</button>
        </div>

        <!-- Étape 7 : Sélection du client -->
        <div id="step-7" style="display:none;">
            <h2><span class="dashicons dashicons-admin-users"></span> Étape 7 : Sélection du client</h2>
<select id="select-customer">
    <option value="">-- Sélectionnez un client --</option>
    <?php foreach ($customers as $cust) : 
        $company = get_user_meta($cust->ID, 'billing_company', true);
        $display = $company 
            ? sprintf('%s - %s (%s)', $company, $cust->display_name, $cust->user_email)
            : sprintf('%s (%s)', $cust->display_name, $cust->user_email);
    ?>
        <option value="<?php echo $cust->ID; ?>">
            <?php echo esc_html($display); ?>
        </option>
    <?php endforeach; ?>
</select>
            <button id="go-step-8">Suivant</button>
        </div>

        <!-- Étape 8 : Validation finale -->
        <div id="step-8" style="display:none;">
            <h2><span class="dashicons dashicons-yes"></span> Étape 8 : Validation</h2>

            <label for="select-order-status">Statut de la commande :</label>
            <select id="select-order-status">
                <option value="wc-on-hold" selected>En attente (On hold)</option>
                <option value="wc-pending">En attente de paiement (Pending)</option>
                <option value="wc-processing">En cours (Processing)</option>
                <option value="wc-completed">Terminée (Completed)</option>
                <option value="wc-devisproposition">Devis Proposition</option>
            </select>
            <br><br>
            <button id="finalize-order">Créer la commande</button>
        </div>
    </div>

    <script>
    (function($){
        var selectedProductId       = null;
        var selectedVariationId     = null;
        var selectedCategories      = [];
        var selectedActionFormation = null;
        var defaultUnitPrice        = 0;
        var finalUnitPrice          = 0;
        var selectedCustomerId      = null;
        var selectedOrderStatus     = 'wc-on-hold';

        // Fonction pour réinitialiser les étapes à partir d'une étape donnée
        function resetSteps(fromStep) {
            for (let i = fromStep; i <= 8; i++) {
                $('#step-' + i).hide();
            }
        }

        // ========== ÉTAPE 1 => 2 ==========
        $('#go-step-2').on('click', function(){
            selectedProductId = $('#select-product').val();
            if(!selectedProductId){
                alert('Veuillez sélectionner un produit.');
                return;
            }

            // Récupère la liste de variations via AJAX
            $.post(ajaxurl, {
                action: 'get_variations_wizard',
                product_id: selectedProductId
            }, function(resp){
                if(resp.success){
                    var vars = resp.data.variations;
                    if(vars.length > 0){
                        // Produit variable
                        $('#select-variation').empty().append('<option value="">-- Choisir --</option>');
                        $.each(vars, function(i,v){
                            $('#select-variation').append('<option value="'+v.id+'" data-price="'+(v.regular_price || 0)+'">'+v.name+'</option>');
                        });
                        resetSteps(2);
                        $('#step-2').show();
                    } else {
                        // Pas de variations => produit simple
                        selectedVariationId = null;
                        // Récupérer le prix du produit simple
                        $.post(ajaxurl, {
                            action: 'get_default_price_wizard',
                            product_id: selectedProductId
                        }, function(resp2){
                            if(resp2.success){
                                defaultUnitPrice = parseFloat(resp2.data.regular_price) || 0;
                            }
                            resetSteps(3);
                            $('#step-3').show();
                        });
                    }
                } else {
                    alert('Erreur: '+resp.data.message);
                }
            });
        });

        // ========== ÉTAPE 2 => 3 ==========
        $('#go-step-3').on('click', function(){
            selectedVariationId = $('#select-variation').val() || null;

            // Récupère le prix si variation
            if(selectedVariationId){
                var chosen = $('#select-variation option:selected');
                var p = parseFloat(chosen.data('price')) || 0;
                defaultUnitPrice = p;
            }

            // Récupère la liste de combos de catégories
            var productOrVariationId = selectedVariationId ? selectedVariationId : selectedProductId;
            $.post(ajaxurl, {
                action: 'get_combos_for_wizard',
                product_id: productOrVariationId
            }, function(resp){
                if(resp.success){
                    var combos = resp.data.combos || [];
                    var $dropdown = $('#choix_categorie');
                    $dropdown.empty().append('<option value="">-- Sélectionnez une option --</option>');
                    if(combos.length === 0){
                        resetSteps(3);
                        $('#step-3').show();
                    } else {
                        $.each(combos, function(i,combo){
                            var categories = combo.categories.join(', ');
                            $dropdown.append('<option value="'+categories+'">'+categories+'</option>');
                        });
                        resetSteps(3);
                        $('#step-3').show();
                    }
                } else {
                    alert('Erreur: '+resp.data.message);
                }
            });
        });

        // ========== ÉTAPE 3 => 4 ==========
        $('#go-step-4').on('click', function(){
            var catVal = $('#choix_categorie').val();
            if(catVal){
                selectedCategories = catVal.split(',');
            } else {
                selectedCategories = [];
            }

            // Récupère la liste d'actions (sessions)
            $.post(ajaxurl, {
                action: 'get_actions_wizard',
                product_id: selectedProductId
            }, function(resp){
                if(resp.success){
                    var acts = resp.data.actions || [];
                    var sel = $('#select-action-formation');
                    sel.empty().append('<option value="">-- Choisir --</option>');
                    $.each(acts, function(i,a){
                        sel.append('<option value="'+a.id+'">'+a.text+'</option>');
                    });
                    resetSteps(4);
                    $('#step-4').show();
                } else {
                    alert('Erreur: '+resp.data.message);
                }
            });
        });

        // ========== ÉTAPE 4 => 5 ==========
        $('#go-step-5').on('click', function(){
            selectedActionFormation = $('#select-action-formation').val() || null;
            resetSteps(5);
            $('#step-5').show();
        });

        // Génération dynamique des champs stagiaires
        $('#input-effectif').on('change keyup', function(){
            var nb = parseInt($(this).val()) || 0;
            var container = $('#stagiaires-container');
            container.empty();
            for(var i=0; i<nb; i++){
                container.append(
                    '<div class="stagiaire-line">' +
                        '<strong>Stagiaire '+(i+1)+'</strong>' +
                        '<div class="stagiaire-fields">' +
                            '<input type="text" name="stagiaire['+i+'][prenom]" placeholder="Prénom" />' +
                            '<input type="text" name="stagiaire['+i+'][nom]" placeholder="Nom" />' +
                            '<input type="text" name="stagiaire['+i+'][date_naiss]" placeholder="Date naissance (jj/mm/aaaa)" />' +
                        '</div>' +
                    '</div>'
                );
            }
        });

        // ========== ÉTAPE 5 => 6 ==========
        $('#go-step-6').on('click', function() {
            $.post(ajaxurl, {
                action: 'get_price_from_grid',
                product_id: selectedProductId,
                variation_id: selectedVariationId,
                categories: selectedCategories
            }, function(resp) {
                if (resp.success && resp.data.price) {
                    $('#input-unit-price').val(parseFloat(resp.data.price).toFixed(2));
                } else {
                    const price = selectedVariationId 
                        ? parseFloat($('#select-variation option:selected').data('price')) || 0
                        : defaultUnitPrice;
                    $('#input-unit-price').val(price.toFixed(2));
                }
            });

            resetSteps(6);
            $('#step-6').show();
        });

        // ========== ÉTAPE 6 => 7 ==========
        $('#go-step-7').on('click', function(){
            resetSteps(7);
            $('#step-7').show();
        });

        // ========== ÉTAPE 7 => 8 ==========
        $('#go-step-8').on('click', function(){
            selectedCustomerId = $('#select-customer').val() || null;
            resetSteps(8);
            $('#step-8').show();
        });

        // ========== CRÉER LA COMMANDE (Étape 8) ==========
        $('#finalize-order').on('click', function(){
            var eff = parseInt($('#input-effectif').val()) || 0;
            // Récupération stagiaires
            var stgs = [];
            for(var i=0; i<eff; i++){
                stgs.push({
                    prenom:     $('[name="stagiaire['+i+'][prenom]"]').val() || '',
                    nom:        $('[name="stagiaire['+i+'][nom]"]').val() || '',
                    date_naiss: $('[name="stagiaire['+i+'][date_naiss]"]').val() || '',
                    nir:        '' // adapter si besoin
                });
            }

            // Récupérer prix unitaire
            var overridePrice = parseFloat($('#input-unit-price').val()) || 0;

            // Récupérer frais
            var fraisMontant = $('#input-fraisclient').val() || '';
            var fraisDate    = $('#input-datefrais').val() || '';
            var fraisNom     = $('#input-nomfrais').val() || '';

            // Statut
            selectedOrderStatus = $('#select-order-status').val() || 'wc-on-hold';

            $.post(ajaxurl, {
                action:              'create_custom_order_wizard',
                product_id:          selectedProductId,
                variation_id:        selectedVariationId,
                effectif:            eff,
                categories_selected: selectedCategories,
                action_formation_id: selectedActionFormation,
                stagiaires:          stgs,
                custom_unit_price:   overridePrice,
                fraisclient_montant: fraisMontant,
                fraisclient_date:    fraisDate,
                fraisclient_nom:     fraisNom,
                customer_id:         selectedCustomerId,
                order_status:        selectedOrderStatus
            }, function(resp){
    if(resp.success){
        alert(resp.data.message + ' (ID: '+resp.data.order_id+')');
        window.location = resp.data.edit_link; // Un SEUL rechargement via redirection
    } else {
        alert('Erreur: '+resp.data.message);
    }
});
        });
    })(jQuery);
    </script>
    <?php
}

/**
 * 5) AJAX : Récupération du prix selon la grille tarifaire
 */
add_action('wp_ajax_get_price_from_grid', 'get_price_from_grid');
function get_price_from_grid() {
    $product_id   = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;
    $categories   = isset($_POST['categories']) && is_array($_POST['categories'])
        ? array_map('sanitize_text_field', $_POST['categories'])
        : [];

    // Pas de catégories => on renvoie price null
    if (empty($categories)) {
        wp_send_json_success(['price' => null]);
    }

    // Normalisation
    $normalized_categories = array_map('normalize_category_label', $categories);

    // Récupération de la grille
    $table = get_full_price_table();

    // Produit ou Variation
    $product = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
    if (!$product) {
        wp_send_json_success(['price' => null]);
    }

    // Code caces/hab
    $caces_code = get_caces_code_from_product_name($product->get_name());
    if (!$caces_code || !isset($table[$caces_code])) {
        wp_send_json_success(['price' => null]);
    }

    // Niveau initial/recyclage
    $niveau = 'initial';
    if ($variation_id) {
        $variation = wc_get_product($variation_id);
        $attributes = $variation->get_attributes();
        if (!empty($attributes['pa_niveau-recyclage-initial'])) {
            $niveau = $attributes['pa_niveau-recyclage-initial'];
        }
    }
    if (!isset($table[$caces_code][$niveau])) {
        wp_send_json_success(['price' => null]);
    }

    // Cherche la combo
    $combos = $table[$caces_code][$niveau]['combos'] ?? [];
    foreach ($combos as $combo) {
        $combo_categories_sorted = $combo['categories'];
        sort($combo_categories_sorted);
        $input_categories_sorted = $normalized_categories;
        sort($input_categories_sorted);

        if ($combo_categories_sorted === $input_categories_sorted) {
            wp_send_json_success(['price' => floatval($combo['price'])]);
        }
    }
    wp_send_json_success(['price' => null]);
}

/**
 * 6) AJAX : Récupération des variations d'un produit variable
 */
add_action('wp_ajax_get_variations_wizard', 'get_variations_wizard');
function get_variations_wizard() {
    $pid = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $product = wc_get_product($pid);
    if(!$product) {
        wp_send_json_error(['message'=>'Produit introuvable.']);
    }

    if($product->is_type('variable')){
        $av = $product->get_available_variations();
        $vars = [];
        foreach($av as $v){
            $var_id  = $v['variation_id'];
            $var_obj = wc_get_product($var_id);
            if(!$var_obj) continue;

            $attrib_str = wc_get_formatted_variation($var_obj->get_variation_attributes(), true);
            $attrib_str = str_replace(['Catégorie(s):','Niveau:'], '', $attrib_str);
            $reg_price  = (float) $var_obj->get_regular_price();

            $vars[] = [
                'id'            => $var_id,
                'name'          => trim($attrib_str),
                'regular_price' => $reg_price
            ];
        }
        wp_send_json_success(['variations'=>$vars]);
    } else {
        // Produit simple => pas de variations
        wp_send_json_success(['variations'=>[]]);
    }
}

/**
 * 7) AJAX : Récupérer le prix d'un produit simple
 */
add_action('wp_ajax_get_default_price_wizard', 'get_default_price_wizard');
function get_default_price_wizard() {
    $pid = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $product = wc_get_product($pid);
    if(!$product){
        wp_send_json_error(['message'=>'Produit introuvable.']);
    }
    $reg_price = (float) $product->get_regular_price();
    wp_send_json_success(['regular_price'=>$reg_price]);
}

/**
 * 8) AJAX : Récupérer les combos de catégories
 */
add_action('wp_ajax_get_combos_for_wizard', 'get_combos_for_wizard');
function get_combos_for_wizard() {
    $pid = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $vid = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;

    $product = $vid ? wc_get_product($vid) : wc_get_product($pid);
    if (!$product) {
        wp_send_json_error(['message' => 'Produit / Variation introuvable.']);
    }

    $caces_code = get_caces_code_from_product_name($product->get_name());
    if (!$caces_code) {
        wp_send_json_success(['combos' => []]);
    }

    $price_table = get_full_price_table();

    // Niveau
    $niveau = 'initial';
    if ($vid) {
        $variation = wc_get_product($vid);
        $attributes = $variation->get_attributes();
        if (!empty($attributes['pa_niveau-recyclage-initial'])) {
            $niveau = $attributes['pa_niveau-recyclage-initial'];
        }
    }

    if (!isset($price_table[$caces_code][$niveau])) {
        wp_send_json_error(['message' => 'Niveau invalide ou non défini.']);
    }

    $combos = $price_table[$caces_code][$niveau]['combos'] ?? [];
    wp_send_json_success(['combos' => $combos]);
}

/**
 * 9) AJAX : Récupérer la liste de sessions (action-de-formation) liées au produit
 */
add_action('wp_ajax_get_actions_wizard', 'get_actions_wizard');
function get_actions_wizard() {
    $pid = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    if(!$pid) {
        wp_send_json_error(['message'=>'Produit manquant']);
    }

    $args = [
        'post_type'      => 'action-de-formation',
        'post_status'    => 'publish',
        'meta_key'       => 'we_startdate',
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',
        'meta_query'     => [
            [
                'key'     => 'fsbdd_relsessproduit',
                'value'   => $pid,
                'compare' => '='
            ]
        ],
        'posts_per_page' => -1,
    ];
    $list = get_posts($args);
    $actions = [];
    foreach($list as $act){
        $cpt_id  = $act->ID;
        $lieu    = get_post_meta($cpt_id, 'fsbdd_select_lieusession', true);
        $start   = get_post_meta($cpt_id, 'we_startdate', true);
        $numero  = get_the_title($cpt_id);

        $lieu_resume = $lieu ? explode(',', $lieu)[0] : 'Lieu inconnu';
        $lieu_resume = ucfirst(strtolower(trim($lieu_resume)));
        $start_fmt   = $start ? date_i18n('j F Y', $start) : 'Date non définie';

        $actions[] = [
            'id'   => $cpt_id,
            'text' => "{$lieu_resume}, {$start_fmt}, N°{$numero}"
        ];
    }
    wp_send_json_success(['actions'=>$actions]);
}

/**
 * 10) AJAX : Création de la commande finale
 */
add_action('wp_ajax_create_custom_order_wizard', 'create_custom_order_wizard');
function create_custom_order_wizard() {
    $product_id         = isset($_POST['product_id'])          ? intval($_POST['product_id']) : 0;
    $variation_id       = isset($_POST['variation_id'])        ? intval($_POST['variation_id']) : 0;
    $categories_selected= isset($_POST['categories_selected']) && is_array($_POST['categories_selected'])
                            ? array_map('sanitize_text_field', $_POST['categories_selected'])
                            : [];
    $action_id          = isset($_POST['action_formation_id']) ? intval($_POST['action_formation_id']) : 0;
    $effectif           = isset($_POST['effectif'])            ? intval($_POST['effectif']) : 0;
    $stagiaires         = isset($_POST['stagiaires']) && is_array($_POST['stagiaires']) ? $_POST['stagiaires'] : [];

    $frais_montant  = isset($_POST['fraisclient_montant']) ? floatval($_POST['fraisclient_montant']) : 0;
    $frais_date     = isset($_POST['fraisclient_date'])    ? sanitize_text_field($_POST['fraisclient_date']) : '';
    $frais_nom      = isset($_POST['fraisclient_nom'])     ? sanitize_text_field($_POST['fraisclient_nom']) : '';

    $custom_unit_price = isset($_POST['custom_unit_price']) ? floatval($_POST['custom_unit_price']) : 0;
    $customer_id       = isset($_POST['customer_id'])       ? intval($_POST['customer_id']) : 0;
    $order_status      = isset($_POST['order_status'])      ? sanitize_text_field($_POST['order_status']) : 'wc-on-hold';

    if (!$product_id) {
        wp_send_json_error(['message' => 'Aucun produit sélectionné.']);
    }

    // 1) Créer la commande
    $order = wc_create_order(['status' => $order_status]);
    if (is_wp_error($order)) {
        wp_send_json_error(['message' => 'Impossible de créer la commande.']);
    }

    // Associer un client (si fourni)
    if ($customer_id > 0) {
        $order->set_customer_id($customer_id);
    }

    // 2) Ajouter le produit
    $product_to_add = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
    if (!$product_to_add) {
        wp_send_json_error(['message' => 'Produit / Variation introuvable.']);
    }
    $qty        = ($effectif > 0) ? $effectif : 1;
    $line_total = $custom_unit_price * $qty;

    $item_id = $order->add_product($product_to_add, $qty, [
        'subtotal' => $line_total,
        'total'    => $line_total,
    ]);
    $line_item = $order->get_item($item_id);

    // 3) Catégories cochées
    if (!empty($categories_selected)) {
        $line_item->update_meta_data('choix_categorie', implode(', ', $categories_selected));
        $line_item->update_meta_data('nombre_categories', count($categories_selected));
    }

// 4) Action de formation
if ($action_id > 0) {
    $lieu_complet = get_post_meta($action_id, 'fsbdd_select_lieusession', true) ?: 'Adresse inconnue';
    $startdate    = get_post_meta($action_id, 'we_startdate', true);
    $enddate      = get_post_meta($action_id, 'we_enddate', true);
    $numero       = get_the_title($action_id);

    $start_fmt = $startdate ? date_i18n('j F Y', $startdate) : 'Date non définie';
    $end_fmt   = $enddate   ? date_i18n('j F Y', $enddate)   : 'Date non définie';

    // Mettre à jour les métadonnées INDIVIDUELLES comme le frontend
    $line_item->update_meta_data('fsbdd_relsessaction_cpt_produit', $action_id);
    $line_item->update_meta_data('we_startdate', $start_fmt);
    $line_item->update_meta_data('we_enddate', $end_fmt);
    $line_item->update_meta_data('fsbdd_actionum', $numero);
    $line_item->update_meta_data('fsbdd_select_lieuforminter', $lieu_complet);

    // Supprimer l'ancienne métadonnée groupée si elle existe
    $line_item->delete_meta_data('session_data');
}
$line_item->save();

    // 5) Stagiaires
    if (!empty($stagiaires)) {
        $p_array = [];
        foreach ($stagiaires as $i => $stg) {
            $p_array[$i] = [
                'fsbdd_prenomstagiaire' => sanitize_text_field($stg['prenom']    ?? ''),
                'fsbdd_nomstagiaire'    => sanitize_text_field($stg['nom']       ?? ''),
                'fsbdd_stagidatenaiss'  => sanitize_text_field($stg['date_naiss'] ?? ''),
                'fsbdd_nirstagiaire'    => sanitize_text_field($stg['nir']       ?? ''),
            ];
        }
        update_post_meta($order->get_id(), 'fsbdd_gpeffectif', $p_array);
    }

    // 6) Frais formation => comme l'ancien code, sans multiplier par 1.20
    if ($frais_montant > 0 || $frais_date || $frais_nom) {
    update_post_meta($order->get_id(), 'fsbdd_nomfrais', $frais_nom); // Nouvelle ligne
        $arr_frais = [
            'fsbdd_montfraisclient' => $frais_montant,
            'fsbdd_typefraisclient' => $frais_nom,  // <-- (important : c'est le nouveau nom)
			'fsbdd_datefraisclient' => $frais_date,
        ];
        // Stocker dans la meta comme avant
        update_post_meta($order->get_id(), 'fsbdd_gpfraisclient', $arr_frais);
    }

    // 7) Calcul des totaux
    $order->calculate_totals();
    $order->save();

    $edit_link = admin_url('post.php?post=' . $order->get_id() . '&action=edit');
    wp_send_json_success([
        'message'  => 'Commande créée avec succès (Quantité = '.$qty.' pour '.$effectif.' stagiaires).',
        'order_id' => $order->get_id(),
        'edit_link'=> $edit_link
    ]);
}
