<?php
/**
 * Snippet ID: 124
 * Name: Menu raccourcis admin forian commentaires activité
 * Description: 
 * @active false
 */

add_action('admin_bar_menu', function($wp_admin_bar) {
    // Vérifie si l'utilisateur connecté a le rôle admin ou l'ID 5
    if (!current_user_can('administrator') && get_current_user_id() !== 5) {
        return;
    }

    // Ajouter le menu principal avec une icône Dashicon
    $wp_admin_bar->add_node([
        'id'    => 'outils_admin',
        'title' => '<span class="ab-icon dashicons dashicons-admin-tools"></span><span class="ab-label">Outils Admin</span>',
        'href'  => '#', // Lien principal, peut être # si non cliquable
        'meta'  => [
            'class' => 'outils-admin-menu', // Classe CSS pour personnalisation si nécessaire
        ],
    ]);

    // Ajouter le sous-menu \"Commentaires\"
    $wp_admin_bar->add_node([
        'id'     => 'outils_admin_commentaires',
        'parent' => 'outils_admin',
        'title'  => 'Commentaires',
        'href'   => admin_url('edit-comments.php'),
    ]);

    // Ajouter le sous-menu \"Activité\"
    $wp_admin_bar->add_node([
        'id'     => 'outils_admin_activite',
        'parent' => 'outils_admin',
        'title'  => 'Activité',
        'href'   => admin_url('admin.php?page=activity-log-page'),
    ]);
}, 100);
