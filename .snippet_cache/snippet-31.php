<?php
/**
 * Snippet ID: 31
 * Name: FRONT-END MENU select actions de formation sur produits
 * Description: 
 * @active true
 */

// Ajouter le sélecteur sur la page produit WooCommerce
add_action( 'woocommerce_single_product_summary', 'afficher_menu_action_de_formation', 25 );

function afficher_menu_action_de_formation() {
    global $product;

    // Vérifier si le plugin Meta Box est actif
    if ( ! function_exists( 'rwmb_meta' ) ) {
        return;
    }

    // Obtenir l'ID du produit actuel
    $product_id = $product->get_id();

    // Vérifier si le produit appartient à la catégorie 326
    if ( has_term( '326', 'product_cat', $product_id ) ) {
        return; // Ne rien afficher pour cette catégorie
    }

    // Obtenir le timestamp actuel
    $current_timestamp = current_time( 'timestamp' );

    // Rechercher les CPT 'action-de-formation' liés à ce produit et dont la date de début est à venir
    $args = [
        'post_type'      => 'action-de-formation',
        'post_status'    => 'publish',
        // Exclure la catégorie 354
        'tax_query'      => [
            [
                'taxonomy' => 'category',  // Ajuster si besoin : 'action_de_formation_cat', etc.
                'field'    => 'term_id',   // ou 'slug' si tu préfères exclure via le slug
                'terms'    => [ 354 ],
                'operator' => 'NOT IN',
            ],
        ],
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => 'fsbdd_relsessproduit',
                'value'   => $product_id,
                'compare' => '='
            ],
            [
                'key'     => 'we_startdate',
                'value'   => $current_timestamp,
                'compare' => '>=',
                'type'    => 'NUMERIC'
            ],
            [
                'key'     => 'fsbdd_typesession', // Vérification du type de session
                'value'   => '1',                // Limiter aux sessions "INTER"
                'compare' => '='
            ]
        ],
        'posts_per_page' => -1, // Toutes les sessions à venir
        'orderby'        => 'meta_value_num',
        'meta_key'       => 'we_startdate',
        'order'          => 'ASC',
    ];
    
    $query = new WP_Query( $args );


    echo '<label for="session-selection">Choisissez une session :</label>';
    echo '<select id="session-selection" name="session_selection" aria-label="Sélectionnez une session liée à ce produit">';
    echo '<option value="" disabled selected>-- Sélectionnez une session --</option>';

    // Ajouter l'option "Intra-entreprise, date à définir ensemble"
    echo '<option value="intra-entreprise-definir" 
        data-lieu-complet="Lieu à définir"
        data-startdate="Date à définir"
        data-enddate="Date à définir"
        data-numero="Intra-entreprise">'
        . esc_html( "INTRA-entreprise, date à définir ensemble" ) .
        '</option>';

    // Ajouter l'option "Inter-entreprises, date à définir ensemble"
    echo '<option value="inter-entreprise-definir" 
        data-lieu-complet="Lieu à définir"
        data-startdate="Date à définir"
        data-enddate="Date à définir"
        data-numero="Inter-entreprises">'
        . esc_html( "Inter-entreprises, date à définir ensemble" ) .
        '</option>';

    // Vérifier s'il y a des sessions disponibles
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();

            // Obtenir les champs personnalisés
            $cpt_id = get_the_ID();
            $lieu = rwmb_meta('fsbdd_select_lieusession');
            $startdate = rwmb_meta('we_startdate');
            $enddate = rwmb_meta('we_enddate');
            $numero = get_the_title();
            
            // Vérifier si we_startdate est défini et est un timestamp valide
            if ( empty( $startdate ) || ! is_numeric( $startdate ) ) {
                continue; // Ignorer si la date de début n'est pas valide
            }

            // Comparer la date de début avec la date actuelle
            if ( $startdate < $current_timestamp ) {
                continue; // Ignorer si la date de début est déjà passée
            }

            // Traiter les valeurs
            $lieu_complet = $lieu ? trim( $lieu ) : 'Adresse inconnue'; // Adresse complète
            $lieu_resume = $lieu ? explode( ',', $lieu )[0] : 'Lieu inconnu'; // Premier terme avant la virgule
            $lieu_resume = ucfirst( strtolower( trim( $lieu_resume ) ) ); // Nettoyer et formater
            $startdate_formatted = $startdate ? date_i18n( 'j F Y', $startdate ) : 'Date non définie';
            $enddate_formatted = $enddate ? date_i18n( 'j F Y', $enddate ) : 'Date non définie';

            // Générer l'option avec des attributs data-*
            echo '<option value="' . esc_attr( get_the_ID() ) . '" 
                data-lieu-complet="' . esc_attr( $lieu_complet ) . '"
                data-startdate="' . esc_attr( $startdate_formatted ) . '"
                data-enddate="' . esc_attr( $enddate_formatted ) . '"
                data-numero="' . esc_attr( $numero ) . '">'
                . esc_html( "{$lieu_resume}, {$startdate_formatted}, N°{$numero}" ) .
                '</option>';
            
            // >>> AJOUT DU BALISAGE SCHEMA.ORG POUR LES DATES DE BOOKING <<<
            echo '<div itemscope itemtype="http://schema.org/Event" style="display:none;">';
            echo '<meta itemprop="name" content="Session N°' . esc_attr( $numero ) . '">';
            echo '<meta itemprop="startDate" content="' . esc_attr( date('c', $startdate) ) . '">';
            // Si enddate n'est pas défini, on utilise startdate en fallback
            echo '<meta itemprop="endDate" content="' . esc_attr( $enddate ? date('c', $enddate) : date('c', $startdate) ) . '">';
            echo '<meta itemprop="location" content="' . esc_attr( $lieu_complet ) . '">';
            echo '</div>';
            // <<< FIN DU BALISAGE SCHEMA.ORG >>>
        }
    }

    echo '</select>';

    // Ajouter la div pour afficher les détails
    echo '<div id="session-details" class="session-infos">
        <p><strong>Adresse de formation :</strong> <span id="info-lieu-complet">-</span></p>
        <p><strong>Date de début :</strong> <span id="info-startdate">-</span></p>
        <p><strong>Date de fin :</strong> <span id="info-enddate">-</span></p>
        <p><strong>Numéro de session :</strong> <span id="info-numero">-</span></p>
    </div>';

    // Réinitialiser la requête
    wp_reset_postdata();
}

// SCRIPT MISE À JOUR DES INFOS SELON LE CHOIX DANS LE SÉLECTEUR
add_action( 'wp_footer', 'ajouter_script_session_dynamique' );
function ajouter_script_session_dynamique() {
    if ( ! is_product() ) {
        return;
    }
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const select = document.getElementById('session-selection');
            const lieuComplet = document.getElementById('info-lieu-complet');
            const startdate = document.getElementById('info-startdate');
            const enddate = document.getElementById('info-enddate');
            const numero = document.getElementById('info-numero');
            const priceElement = document.querySelector('.woocommerce-Price-amount'); // Sélecteur prix pour produits simples
            const variationPriceElements = document.querySelectorAll('.woocommerce-variation-price .price'); // Prix des variations
            

            // Vérifier si le sélecteur existe
            if (!select) return;

            // Écouter les changements dans le sélecteur
            select.addEventListener('change', function () {
                const selectedOption = select.options[select.selectedIndex];
                
                // Vérification : afficher une alerte pour "Intra-entreprise"
                if (selectedOption.value === 'intra-entreprise-definir') {
                    alert('Intra-entreprise, prix à définir après étude de votre dossier');
                }

                // Mettre à jour les champs dynamiques
                lieuComplet.textContent = selectedOption.getAttribute('data-lieu-complet') || '-';
                startdate.textContent = selectedOption.getAttribute('data-startdate') || '-';
                enddate.textContent = selectedOption.getAttribute('data-enddate') || '-';
                numero.textContent = selectedOption.getAttribute('data-numero') || '-';
                
            });

            // Sauvegarder le prix original des variations
            if (variationPriceElements) {
                variationPriceElements.forEach(function (element) {
                    if (!element.getAttribute('data-original-price')) {
                        element.setAttribute('data-original-price', element.textContent);
                    }
                });
            }
        });
    </script>
    <?php
}



// Ajout des données au panier
add_filter('woocommerce_add_cart_item_data', 'ajouter_infos_session_au_panier', 10, 2);
function ajouter_infos_session_au_panier($cart_item_data, $product_id) {
    if (isset($_POST['session_selection']) && !empty($_POST['session_selection'])) {
        $session_id = sanitize_text_field($_POST['session_selection']);
        $lieu_complet = sanitize_text_field($_POST['info_lieu_complet']);
        $startdate = sanitize_text_field($_POST['info_startdate']);
        $enddate = sanitize_text_field($_POST['info_enddate']);
        $numero = sanitize_text_field($_POST['info_numero']);

        // Enregistrer uniquement l'ID de la session pour logique admin
        $cart_item_data['fsbdd_relsessaction_cpt_produit'] = $session_id;

        // Ajouter les informations de session uniquement pour affichage
        $cart_item_data['session_data'] = [
            'lieu_complet' => $lieu_complet,
            'startdate'    => $startdate,
            'enddate'      => $enddate,
            'numero'       => $numero,
        ];
        
        // Forcer le prix à 0 pour l'option "Intra-entreprise"
        if ($session_id === 'intra-entreprise-definir') {
            $cart_item_data['price_override'] = 0;
        }

        // Générer un identifiant unique pour chaque ajout
        $cart_item_data['unique_key'] = md5(microtime() . rand());
    }

    return $cart_item_data;
}

// Forcer le prix dans le panier (en priorité 99)
add_filter('woocommerce_before_calculate_totals', 'appliquer_prix_zero_si_intra', 99);
function appliquer_prix_zero_si_intra($cart) {
    if ( is_admin() && ! defined('DOING_AJAX') ) return;

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        // Si la session sélectionnée est "intra-entreprise-definir"
        if ( isset($cart_item['fsbdd_relsessaction_cpt_produit']) 
             && $cart_item['fsbdd_relsessaction_cpt_produit'] === 'intra-entreprise-definir' ) {

            // On force le prix à 0, peu importe la grille tarifaire
            $cart_item['data']->set_price(0);
        }
    }
}

// Affichage des données dans le panier
add_filter('woocommerce_get_item_data', 'afficher_infos_session_dans_panier', 10, 2);
function afficher_infos_session_dans_panier($item_data, $cart_item) {
    if (isset($cart_item['session_data'])) {
        $session_data = $cart_item['session_data'];

        $item_data[] = [
            'name'  => 'Session de formation',
            'value' => sprintf(
                'Lieu : %s<br>Début : %s<br>Fin : %s<br>Numéro : %s',
                esc_html($session_data['lieu_complet']),
                esc_html($session_data['startdate']),
                esc_html($session_data['enddate']),
                esc_html($session_data['numero'])
            ),
        ];
    }

    return $item_data;
}

add_action('woocommerce_checkout_create_order_line_item', 'enregistrer_session_dans_commande', 10, 4);
function enregistrer_session_dans_commande($item, $cart_item_key, $values, $order) {
    // 1. ID de session choisi
    if (! empty($values['fsbdd_relsessaction_cpt_produit'])) {
        $session_id = $values['fsbdd_relsessaction_cpt_produit'];
        $item->add_meta_data('fsbdd_relsessaction_cpt_produit', $session_id);
    }

    // 2. Dates + numéro de session
    if (! empty($values['session_data'])) {
        $session_data = $values['session_data'];

        if (!empty($session_data['startdate'])) {
            $item->add_meta_data('we_startdate', $session_data['startdate']);
        }
        if (!empty($session_data['enddate'])) {
            $item->add_meta_data('we_enddate', $session_data['enddate']);
        }
        if (!empty($session_data['numero'])) {
            $item->add_meta_data('fsbdd_actionum', $session_data['numero']);
        }
    }

    // 3. Optionnel : si le $session_id est numérique (CPT existant), récupérer le meta "fsbdd_select_lieusession" 
    //    et le stocker aussi au niveau du line item (par exemple sous la clé "fsbdd_select_lieuforminter" 
    //    si c’est de l’INTER).
    //    On peut vous conseiller de le faire au niveau "woocommerce_checkout_create_order" 
    //    pour être sûr de la logique INTRA/INTER, mais vous pouvez aussi le faire ici.

    if (! empty($session_id) && is_numeric($session_id)) {
        // Récupérer l'adresse depuis le CPT
        $lieu_complet = get_post_meta($session_id, 'fsbdd_select_lieusession', true);
        if (! empty($lieu_complet)) {
            // On ajoute la meta sur le line item
            $item->add_meta_data('fsbdd_select_lieuforminter', $lieu_complet);
        }
    }
}

// Ajout des champs div au formulaire d'ajout au panier
add_action('wp_footer', 'ajouter_champs_cache_session');
function ajouter_champs_cache_session() {
    if (!is_product()) return;
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form.cart');
            const sessionSelect = document.getElementById('session-selection');
            const hiddenInputs = {
                session_selection: document.createElement('input'),
                info_lieu_complet: document.createElement('input'),
                info_startdate: document.createElement('input'),
                info_enddate: document.createElement('input'),
                info_numero: document.createElement('input')
            };

            // Configurer les champs cachés
            Object.keys(hiddenInputs).forEach(key => {
                hiddenInputs[key].type = 'hidden';
                hiddenInputs[key].name = key;
                form.appendChild(hiddenInputs[key]);
            });

            // Mettre à jour les champs cachés au changement
            sessionSelect.addEventListener('change', function () {
                const selected = sessionSelect.options[sessionSelect.selectedIndex];

                if (!selected.hasAttribute('data-lieu-complet')) {
                    alert('Certaines informations sur la session sont manquantes. Veuillez réessayer.');
                    return;
                }

                hiddenInputs.session_selection.value = sessionSelect.value;
                hiddenInputs.info_lieu_complet.value = selected.getAttribute('data-lieu-complet') || '';
                hiddenInputs.info_startdate.value = selected.getAttribute('data-startdate') || '';
                hiddenInputs.info_enddate.value = selected.getAttribute('data-enddate') || '';
                hiddenInputs.info_numero.value = selected.getAttribute('data-numero') || '';
            });

            // Validation : empêcher soumission sans sélection
            form.addEventListener('submit', function (e) {
                if (!sessionSelect.value) {
                    e.preventDefault();
                    alert('Veuillez sélectionner une session avant d’ajouter au panier.');
                }
            });
        });
    </script>
    <?php
}

// Indique à WooCommerce si le panier a besoin d’une expédition
add_filter('woocommerce_cart_needs_shipping', 'prefix_needs_shipping_only_for_intra', 10, 1);
function prefix_needs_shipping_only_for_intra($needs_shipping) {
    // Éviter de faire la boucle dans l'admin si pas en AJAX
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return $needs_shipping;
    }

    // Vérifier si l'objet WC()->cart existe
    if ( ! WC()->cart ) {
        return $needs_shipping;
    }

    $intra_selected = false;

    // On regarde tous les produits du panier
    foreach ( WC()->cart->get_cart() as $cart_item ) {
        if (
            isset($cart_item['fsbdd_relsessaction_cpt_produit']) 
            && $cart_item['fsbdd_relsessaction_cpt_produit'] === 'intra-entreprise-definir'
        ) {
            $intra_selected = true;
            break;
        }
    }

    return $intra_selected;
}

// Indique si WooCommerce doit demander une adresse de livraison
add_filter('woocommerce_cart_needs_shipping_address', 'prefix_needs_shipping_address_only_for_intra', 10, 1);
function prefix_needs_shipping_address_only_for_intra($needs_shipping_address) {
    // Pareil : on sort si on est en back office
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return $needs_shipping_address;
    }

    // Vérifier si WC()->cart est disponible
    if ( ! WC()->cart ) {
        return $needs_shipping_address;
    }

    $intra_selected = false;
    foreach ( WC()->cart->get_cart() as $cart_item ) {
        if (
            isset($cart_item['fsbdd_relsessaction_cpt_produit']) 
            && $cart_item['fsbdd_relsessaction_cpt_produit'] === 'intra-entreprise-definir'
        ) {
            $intra_selected = true;
            break;
        }
    }

    return $intra_selected;
}
