<?php
/**
 * Snippet ID: 56
 * Name: COMMENTAIRE ADMIN DATES & AUTORISATIONS
 * Description: 
 * @active true
 */

/**
 * AFFICHER DATES COMMENTAIRES ADMIN POST EDIT
 */
function afficher_date_dans_auteur() {
    add_filter('get_comment_author_url', function ($author_url) {
        global $comment;

        // Vérifier si nous sommes en admin et que $comment est défini
        if (is_admin() && !empty($comment)) {
            $date_comment = date_i18n(get_option('date_format'), strtotime($comment->comment_date));
            $time_comment = date_i18n(get_option('time_format'), strtotime($comment->comment_date));

            // Ajouter la date et l'heure au bloc auteur
            echo '<p class="commentaire-date">Date : ' . $date_comment . ' à ' . $time_comment . '</p>';
        }

        return $author_url;
    });
}
add_action('admin_init', 'afficher_date_dans_auteur');

/**
 * MASQUER SUPPRESSION COMMENTAIRES POUR REFERENTS ET COMPTA
 */
function masquer_actions_commentaires_roles($actions, $comment) {
    $current_user = wp_get_current_user();

    // Vérifier les rôles `referent` et `compta`, mais laisser les permissions si `administrator`
    if ((in_array('referent', $current_user->roles) || in_array('compta', $current_user->roles)) && !current_user_can('administrator')) {
        unset($actions['delete']);
        unset($actions['trash']);
        unset($actions['approve']);
        unset($actions['spam']);
        unset($actions['unapprove']);
        unset($actions['quickedit']);
        unset($actions['edit']);
    }

    return $actions;
}
add_filter('comment_row_actions', 'masquer_actions_commentaires_roles', 10, 2);

/**
 * MASQUER LES ACTIONS EN VRAC POUR REFERENTS ET COMPTA
 */
function masquer_actions_vrac_roles($actions) {
    $current_user = wp_get_current_user();

    if ((in_array('referent', $current_user->roles) || in_array('compta', $current_user->roles)) && !current_user_can('administrator')) {
        unset($actions['delete']);
        unset($actions['spam']);
        unset($actions['approve']);
    }

    return $actions;
}
add_filter('bulk_actions-edit-comments', 'masquer_actions_vrac_roles');
