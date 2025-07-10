<?php
/**
 * Snippet ID: 152
 * Name: Menu pilotage, plannings, raccourcis... barre haut admin wordpress
 * Description: 
 * @active true
 */

// MENUS PERSONNALISES BARRE ADMINISTRATION
add_action('admin_bar_menu', 'add_custom_admin_bar_menus', 100);
function add_custom_admin_bar_menus($wp_admin_bar) {
    
    // 1. LIEN PILOTAGE
    $wp_admin_bar->add_node(array(
        'id'    => 'dashboard-link',
        'title' => '<span class="ab-icon dashicons-dashboard"></span>Pilotage',
        'href'  => 'https://formationstrategique.fr/wp-admin/index.php',
        'meta'  => array('title' => 'Pilotage')
    ));
    
    // 2. MENU OUTILS ADMIN (Admin et ID 5 uniquement)
    if (current_user_can('administrator') || get_current_user_id() === 5) {
        $wp_admin_bar->add_node(array(
            'id'    => 'outils_admin',
            'title' => '<span class="ab-icon dashicons-admin-tools"></span>Outils Admin',
            'href'  => '#',
            'meta'  => array('class' => 'outils-admin-menu')
        ));
        
        // Sous-menus Outils Admin
        $wp_admin_bar->add_node(array(
            'id'     => 'outils_admin_commentaires',
            'parent' => 'outils_admin',
            'title'  => 'Commentaires',
            'href'   => admin_url('edit-comments.php')
        ));
        
        $wp_admin_bar->add_node(array(
            'id'     => 'outils_admin_activite',
            'parent' => 'outils_admin',
            'title'  => 'Activité',
            'href'   => admin_url('admin.php?page=activity-log-page')
        ));
    }
    
    // 3. MENU SESSIONS
    $wp_admin_bar->add_node(array(
        'id'    => 'sessions',
        'title' => '<span class="ab-icon dashicons-calendar-alt"></span>Sessions',
        'href'  => admin_url('admin.php?page=sessions-table'),
        'meta'  => array('title' => 'Sessions')
    ));
    
    // 4. MENU RACCOURCIS
    $wp_admin_bar->add_node(array(
        'id'    => 'raccourcis',
        'title' => '<span class="ab-icon dashicons-chart-line"></span>Raccourcis',
        'href'  => admin_url('admin.php?page=pilotage_page'),
        'meta'  => array('title' => 'Raccourcis')
    ));
    
    // Sous-menus Raccourcis
    $raccourcis_items = array(
        'clients-site' => array(
            'title' => 'Clients site',
            'href'  => 'https://formationstrategique.fr/wp-admin/users.php?role=customer'
        ),
        'pilotage-formations' => array(
            'title' => 'Pilotage formations',
            'href'  => 'https://formationstrategique.fr/wp-admin/edit.php?layout=656842569b10b&post_type=shop_order'
        ),
        'calendrier-inter' => array(
            'title' => 'Calendrier INTER',
            'href'  => 'https://formationstrategique.fr/wp-admin/edit.php?layout=66a72f8181510&post_type=product'
        ),
        'resultats-mensuels' => array(
            'title' => 'Résultats mensuels',
            'href'  => 'https://formationstrategique.fr/wp-admin/edit.php?layout=66ade2456f196&post_type=shop_order'
        )
    );
    
    foreach ($raccourcis_items as $key => $item) {
        $wp_admin_bar->add_node(array(
            'id'     => 'raccourcis-' . $key,
            'parent' => 'raccourcis',
            'title'  => $item['title'],
            'href'   => $item['href'],
            'meta'   => array('title' => $item['title'])
        ));
    }
}