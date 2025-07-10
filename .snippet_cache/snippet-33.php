<?php
/**
 * Snippet ID: 33
 * Name: FRONT-END CALENDRIER dynamique ACTIONS DE FORMATION
 * Description: 
 * @active true
 */

function afficher_calendrier_actions() {
    // Obtenir le timestamp actuel basé sur le fuseau horaire de WordPress
    $current_timestamp = current_time('timestamp');
    $args = [
        'post_type'      => 'action-de-formation',
        'post_status'    => 'publish',
        // Exclure la catégorie 354
        'tax_query' => [
            [
                'taxonomy' => 'category',
                'field'    => 'term_id',
                'terms'    => [ 354 ],
                'operator' => 'NOT IN',
            ],
        ],
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => 'we_startdate',
                'compare' => 'EXISTS',
                'type'    => 'NUMERIC',
            ],
            [
                'key'     => 'we_startdate',
                'value'   => $current_timestamp,
                'compare' => '>=',
                'type'    => 'NUMERIC',
            ],
        ],
        'orderby'        => 'meta_value_num',
        'meta_key'       => 'we_startdate',
        'order'          => 'ASC',
        'posts_per_page' => -1,
        'post__not_in'   => [268081],
    ];
    $query = new WP_Query($args);
    $schema_markup = [];
    
    if ($query->have_posts()) {
        ob_start();
        
        // CSS amélioré avec ajustements pour le bloc date et l'icône Dashicons
        echo '<style>
            .formation-container {
                max-width: 100%;
                margin: 0 auto;
            }
            .formation-list {
                padding: 0;
                list-style: none;
                margin: 0;
            }
            .formation-item {
                display: flex;
                padding: 15px;
                margin-bottom: 10px;
                background-color: #f8f8f8;
                border-radius: 5px;
                align-items: flex-start;
                border: 1px solid #e0e0e0;
            }
            .formation-date {
                background-color: #2c3e50;
                color: white;
                padding: 10px;
                border-radius: 4px;
                text-align: center;
                width: 185px;
                margin-right: 20px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                min-height: 80px;
            }
            .formation-date .jour-mois {
                font-weight: bold;
                font-size: 22px; /* Augmenté la taille du jour-mois */
                margin-bottom: 5px;
            }
            .formation-date .annee {
                font-size: 16px; /* Diminué la taille de l\'année */
            }
            .formation-info {
                flex-grow: 1;
                padding-top: 3px;
            }
            .formation-title {
                margin: 0 0 10px 0;
                font-size: 18px;
                color: #2c3e50;
                font-weight: bold;
                padding: 0px;
            }
            .formation-city {
                margin: 0;
                font-size: 15px;
                color: #555;
                line-height: 1.3;
                padding: 0px;
            }
            .formation-dates {
                margin: 3px 0 0 0;
                font-size: 15px;
                color: #555;
                line-height: 1.3;
            }
            .formation-action {
                margin-left: 15px;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .formation-icon {
                font-size: 24px;
                color: #2c3e50;
                margin-bottom: 10px;
            }
            .formation-link {
                display: inline-block;
                background-color: #900;
                color: white;
                padding: 10px 15px;
                text-decoration: none;
                border-radius: 4px;
                font-size: 14px;
                white-space: nowrap;
            }
            .formation-link:hover {
                background-color: #700;
            }
            @media (max-width: 768px) {
                .formation-item {
                    flex-direction: column;
                }
                .formation-date {
                    width: 100%;
                    margin-right: 0;
                    margin-bottom: 10px;
                    flex-direction: row;
                    justify-content: center;
                    align-items: center;
	                min-height: 60px;
                }


                .formation-date .jour-mois {
                    margin-bottom: 0;
                    margin-right: 10px;
                }
                .formation-action {
                    margin-left: 0;
                    margin-top: 10px;
                    flex-direction: row;
                    justify-content: center;
                }
                .formation-icon {
                    margin-bottom: 0;
                    margin-right: 10px;
                }
            }
        </style>';

        // Assurons-nous que les Dashicons sont chargées
        wp_enqueue_style('dashicons');
        
        echo '<div class="formation-container">';
        echo '<ul class="formation-list">';
        
        while ($query->have_posts()) {
            $query->the_post();
            
            // Obtenir les champs personnalisés
            $startdate = rwmb_meta('we_startdate');
            $enddate = rwmb_meta('we_enddate');
            $city = rwmb_meta('fsbdd_select_lieusession');
            $product_id = rwmb_meta('fsbdd_relsessproduit');
            
            // Vérifications des données
            if (empty($startdate) || !is_numeric($startdate) || $startdate < $current_timestamp) {
                continue;
            }
            
            // Formater les valeurs
            $startdate_formatted = date_i18n('j F Y', $startdate);
            $enddate_formatted = $enddate ? date_i18n('j F Y', $enddate) : $startdate_formatted;
            $city_formatted = $city ? ucfirst(strtolower(trim($city))) : 'Lieu inconnu';
            
            // Titre du produit lié (formation)
            $product_name = $product_id ? get_the_title($product_id) : 'Formation inconnue';
            
            // Lien vers la page produit - Assurer qu'il s'agit d'une URL complète
            $product_link = $product_id ? add_query_arg('open_session', get_the_ID(), get_permalink($product_id)) : '#';
            
            // S'assurer que l'URL est absolue pour les crawlers
            if ($product_id && strpos($product_link, 'http') !== 0) {
                $product_link = site_url($product_link);
            }
            
            // Affichage visible - Structure proche de votre capture d'écran
            echo '<li class="formation-item" 
                data-title="' . esc_attr($product_name) . '" 
                data-city="' . esc_attr($city_formatted) . '" 
                data-date="' . esc_attr($startdate_formatted) . '">';
            
            // Bloc de date à gauche - séparation jour/mois et année
            echo '<div class="formation-date">';
            echo '<div class="jour-mois">' . esc_html(date_i18n('j F', $startdate)) . '</div>';
            echo '<div class="annee">' . esc_html(date_i18n('Y', $startdate)) . '</div>';
            echo '</div>';
            
            // Informations centrales
            echo '<div class="formation-info">';
            echo '<h3 class="formation-title">' . esc_html($product_name) . '</h3>';
            echo '<p class="formation-city">' . esc_html($city_formatted) . '</p>';
            echo '<p class="formation-dates">Du ' . esc_html($startdate_formatted) . ' au ' . esc_html($enddate_formatted) . '</p>';
            echo '</div>';
            
            // Bouton à droite avec dashicon
            echo '<div class="formation-action">';
            echo '<span class="formation-icon dashicons dashicons-calendar-alt"></span>';
            echo '<a href="' . esc_url($product_link) . '" class="formation-link">Infos / Réservation</a>';
            echo '</div>';
            
            echo '</li>';
            
            // Obtenir l'image du produit
            $product_image = '';
            if ($product_id && has_post_thumbnail($product_id)) {
                $image_id = get_post_thumbnail_id($product_id);
                $image_array = wp_get_attachment_image_src($image_id, 'full');
                if ($image_array) {
                    $product_image = $image_array[0];
                }
            }
            
            // Récupérer les objectifs de formation pour la description
            $description = '';
            $objectifs = rwmb_meta('textarea_objectifform', '', $product_id);
            if (!empty(trim($objectifs))) {
                $description = wp_strip_all_tags($objectifs);
            } else {
                // Fallback vers l'extrait si pas d'objectifs
                $description = get_the_excerpt();
                if (empty(trim($description)) && $product_id) {
                    // Tenter d'obtenir l'extrait du produit si disponible
                    $product_post = get_post($product_id);
                    if ($product_post) {
                        $description = !empty(trim($product_post->post_excerpt)) 
                            ? $product_post->post_excerpt 
                            : wp_trim_words($product_post->post_content, 30);
                    }
                }
            }
            
            // Fallback si toujours vide
            if (empty(trim($description))) {
                $description = "Formation professionnelle : " . $product_name . " à " . $city_formatted;
            }
            
            // Récupérer le prix du produit WooCommerce (si applicable)
            $price = '0';
            $price_html = '';
            
            if ($product_id && function_exists('wc_get_product')) {
                $product = wc_get_product($product_id);
                if ($product) {
                    // Vérifier si c'est un produit variable
                    if ($product->is_type('variable')) {
                        $price = $product->get_variation_price('min');
                        $price_html = 'À partir de ' . wc_price($price);
                    } else {
                        $price = $product->get_price();
                        $price_html = wc_price($price);
                    }
                }
            }
            
            // Données structurées pour SEO - avec corrections
            $schema_markup[] = [
                '@type' => 'Event',
                'name' => $product_name,
                'startDate' => date('c', $startdate),
                'endDate' => $enddate ? date('c', $enddate) : date('c', $startdate),
                'location' => [
                    '@type' => 'Place',
                    'name' => $city_formatted,
                    'address' => [
                        '@type' => 'PostalAddress',
                        'addressLocality' => $city_formatted,
                        'addressCountry' => 'FR'
                    ]
                ],
                'description' => $description,
                'url' => $product_link,
                'image' => $product_image ?: site_url('/wp-content/uploads/default-formation.jpg'), // Image par défaut si aucune n'est trouvée
                'organizer' => [
                    '@type' => 'Organization',
                    'name' => get_bloginfo('name'),
                    'url' => site_url()
                ],
                'offers' => [
                    '@type' => 'Offer',
                    'url' => $product_link,
                    'availability' => 'https://schema.org/InStock',
                    'validFrom' => date('c', current_time('timestamp')), // Date actuelle
                    'priceCurrency' => 'EUR',
                    'price' => $price
                ],
                'eventStatus' => 'https://schema.org/EventScheduled',
                'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode'
            ];
        }
        
        echo '</ul>';
        echo '</div>';
        
        // Ajouter le balisage schema JSON-LD
        if (!empty($schema_markup)) {
            echo '<script type="application/ld+json">';
            echo json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                'itemListElement' => array_map(function($item, $index) {
                    return [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'item' => $item
                    ];
                }, $schema_markup, array_keys($schema_markup))
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            echo '</script>';
        }
        
        wp_reset_postdata();
        return ob_get_clean();
    } else {
        return '<p>Aucune action de formation disponible.</p>';
    }
}
add_shortcode('calendrier_formations', 'afficher_calendrier_actions');