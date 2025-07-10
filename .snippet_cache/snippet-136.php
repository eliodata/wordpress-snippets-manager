<?php
/**
 * Snippet ID: 136
 * Name: page admin couts formateurs
 * Description: 
 * @active false
 */

// Ajouter la page admin
add_action('admin_menu', 'add_formation_report_page');
function add_formation_report_page() {
    add_submenu_page(
        'edit.php?post_type=action-de-formation',
        __('Rapport des Formations', 'your-text-domain'),
        __('Rapport des Formations', 'your-text-domain'),
        'manage_options',
        'formation-report',
        'display_formation_report'
    );
}

// Afficher le rapport
function display_formation_report() {
    // Récupérer toutes les actions de formation
    $args = array(
        'post_type' => 'action-de-formation',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    );

    $formations = new WP_Query($args);
    
    // Début du contenu HTML
    echo '<div class="wrap">';
    echo '<h1>' . __('Rapport des Actions de Formation', 'your-text-domain') . '</h1>';
    
    if ($formations->have_posts()) {
        // Entête du tableau
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>
            <tr>
                <th>ID</th>
                <th>' . __('Formation', 'your-text-domain') . '</th>
                <th>' . __('Coût Total', 'your-text-domain') . '</th>
                <th>' . __('Formateurs', 'your-text-domain') . '</th>
                <th>' . __('Logistique', 'your-text-domain') . '</th>
                <th>' . __('Frais Mission', 'your-text-domain') . '</th>
                <th>' . __('Frais Fournisseurs', 'your-text-domain') . '</th>
                <th>' . __('Statut', 'your-text-domain') . '</th>
            </tr>
        </thead>
        <tbody>';
        
        while ($formations->have_posts()) {
            $formations->the_post();
            $post_id = get_the_ID();
            
            // Synchroniser les données avant affichage
            sync_formation_planning_costs($post_id, true);
            
            // Récupérer les métadonnées
            $meta = get_post_meta($post_id);
            
            // Formater les valeurs
            $total = isset($meta['fsbdd_coutaction'][0]) ? number_format($meta['fsbdd_coutaction'][0], 2) : '0.00';
            $formateurs = isset($meta['fsbdd_coutsformrs'][0]) ? number_format($meta['fsbdd_coutsformrs'][0], 2) : '0.00';
            $logistique = isset($meta['fsbdd_ttchrglogisti'][0]) ? number_format($meta['fsbdd_ttchrglogisti'][0], 2) : '0.00';
            $frais_mission = isset($meta['fsbdd_fraismission'][0]) ? number_format($meta['fsbdd_fraismission'][0], 2) : '0.00';
            $frais_fournisseurs = isset($meta['fsbdd_fraisfourni'][0]) ? number_format($meta['fsbdd_fraisfourni'][0], 2) : '0.00';
            $statut = isset($meta['fsbdd_sessconfirm'][0]) ? get_planning_booke($meta) : __('Inconnu', 'your-text-domain');
            
            // Ligne du tableau
            echo '<tr>
                <td>' . $post_id . '</td>
                <td><a href="' . get_edit_post_link($post_id) . '">' . get_the_title() . '</a></td>
                <td style="font-weight:bold">' . $total . ' €</td>
                <td>' . $formateurs . ' €</td>
                <td>' . $logistique . ' €</td>
                <td>' . $frais_mission . ' €</td>
                <td>' . $frais_fournisseurs . ' €</td>
                <td>' . $statut . '</td>
            </tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<div class="notice notice-warning"><p>' . __('Aucune formation trouvée', 'your-text-domain') . '</p></div>';
    }
    
    echo '</div>';
    
    // CSS personnalisé
    echo '<style>
        .wp-list-table th { font-weight: 600; }
        .wp-list-table td { vertical-align: middle; }
        .wp-list-table td:nth-child(3) { color: #2271b1; }
    </style>';
    
    wp_reset_postdata();
}
