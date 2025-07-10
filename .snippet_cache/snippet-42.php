<?php
/**
 * Snippet ID: 42
 * Name: FRON-END Bouton emailing calendrier
 * Description: 
 * @active true
 */


// Générer le planning pour les actions de formation
function generer_planning_formations() {
    // Obtenir le timestamp actuel basé sur le fuseau horaire de WordPress
    $current_timestamp = current_time( 'timestamp' );

    $args = [
        'post_type'      => 'action-de-formation',
        'post_status'    => 'publish',
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
        'post__not_in'   => [268081], // Exclure l'ID 268081
    ];
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $formations = [];

        while ($query->have_posts()) {
            $query->the_post();

            // Obtenir les champs personnalisés
            $startdate = rwmb_meta('we_startdate');
            $city_raw = rwmb_meta('fsbdd_select_lieusession');
            $product_id = rwmb_meta('fsbdd_relsessproduit'); // ID du produit lié

            if (!$product_id) {
                continue; // Ignorer les sessions sans produit lié
            }

            // Vérifier si we_startdate est défini et est un timestamp valide
            if (empty($startdate) || !is_numeric($startdate)) {
                continue; // Ignorer si la date de début n'est pas valide
            }

            // Comparer la date de début avec la date actuelle
            if ($startdate < $current_timestamp) {
                continue; // Ignorer si la date de début est déjà passée
            }

            // Extraire uniquement la ville
            $city = $city_raw ? explode(',', $city_raw)[0] : 'Lieu inconnu';
            $city_formatted = ucfirst(strtolower(trim($city)));

            $startdate_formatted = date_i18n('j F Y', $startdate);
            $product_name = get_the_title($product_id);
            $product_link = add_query_arg('open_session', get_the_ID(), get_permalink($product_id));

            // Grouper par produit
            if (!isset($formations[$product_id])) {
                $formations[$product_id] = [
                    'name' => $product_name,
                    'sessions' => []
                ];
            }

            $formations[$product_id]['sessions'][] = [
                'date' => $startdate_formatted,
                'city' => $city_formatted,
                'link' => $product_link
            ];
        }

        wp_reset_postdata();

        // Trier les formations par nom (ordre alphabétique)
        uasort($formations, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        // Générer le contenu HTML du planning
        ob_start();
        echo '<h2>Prochaines formations disponibles</h2>';
        echo '<ul>';

        foreach ($formations as $formation) {
            echo '<li>';
            echo '<h3>' . esc_html($formation['name']) . '</h3>';
            echo '<ul>';
            foreach ($formation['sessions'] as $session) {
                echo '<li>';
                echo esc_html($session['date']) . ', ' . esc_html($session['city']);
                echo ' - <a href="' . esc_url($session['link']) . '" target="_blank">Infos / Inscription</a>';
                echo '</li>';
            }
            echo '</ul>';
            echo '</li>';
        }

        echo '</ul>';

        return ob_get_clean();
    } else {
        return '<p>Aucune session de formation disponible pour l\'instant.</p>';
    }
}

// Shortcode pour afficher le bouton et le contenu généré
function bouton_generer_planning() {
    $planning_content = generer_planning_formations();

    ob_start();
    ?>
    <button id="generate-planning-button" style="margin-bottom: 20px;">Générer le planning</button>
    <div id="planning-content" style="display: none;">
        <?php echo $planning_content; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const button = document.getElementById('generate-planning-button');
            const content = document.getElementById('planning-content');

            button.addEventListener('click', function () {
                content.style.display = content.style.display === 'none' ? 'block' : 'none';
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('generer_planning', 'bouton_generer_planning');
