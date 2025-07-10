<?php
/**
 * Snippet ID: 148
 * Name: Theme admin page utilisateur backend
 * Description: 
 * @active true
 */

/**
 * Plugin Name: Simplification Interface Utilisateur
 * Description: Simplifie et améliore l'interface d'édition utilisateur dans le backend WordPress
 * Version: 1.0
 * Author: Claude
 */

// Ne pas exécuter directement
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Regroupe les champs liés aux BDD dans une section collapsible
 */
function simplify_user_edit_screen() {
    // Styles pour améliorer l'interface
    add_action('admin_head', function() {
        ?>
        <style>
            /* Styles généraux pour la page d'édition */
            .user-edit-php .form-table,
            .profile-php .form-table {
                margin-top: 0;
                background: #fff;
                border: 1px solid #e5e5e5;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                padding: 15px;
                border-radius: 4px;
            }
            
            /* Mettre en évidence les en-têtes de section */
            .user-section-header {
                background: #f9f9f9;
                padding: 10px 15px;
                margin: 20px 0 10px;
                border-left: 4px solid #2271b1;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .user-section-header:after {
                content: "\f140"; /* Icône dashicons flèche bas */
                font-family: dashicons;
            }
            
            .user-section-header.collapsed:after {
                content: "\f142"; /* Icône dashicons flèche haut */
            }
            
            .user-section-content {
                padding: 5px 15px;
                background: #fff;
                border: 1px solid #e5e5e5;
                border-top: none;
                margin-top: -10px;
                margin-bottom: 20px;
            }
            
            .user-section-content.hidden {
                display: none;
            }
            
            /* Simplifier les étiquettes de champ */
            .form-table th {
                width: 150px;
                padding: 15px 10px;
            }
            
            /* Améliorations des champs select */
            .form-table select {
                min-width: 300px;
                height: 35px;
            }
            
            /* Style pour les tooltips d'aide */
            .field-help-text {
                color: #666;
                font-style: italic;
                font-size: 12px;
                margin-top: 5px;
                display: block;
            }
            
            /* Champs optionnels en style subtil */
            .optional-field label {
                opacity: 0.8;
            }
            
            /* Style pour les éléments qui doivent prendre toute la largeur */
            .full-width-field {
                grid-column: 1 / span 2;
            }
            
            /* Affichage en 2 colonnes pour tous les champs */
            .form-table, 
            .bdd-fields-container {
                display: grid;
                grid-template-columns: 1fr 1fr;
                grid-gap: 15px;
            }
            
            /* Ajustement pour les lignes de tableau transformées en grid */
            .form-table tr {
                display: contents;
            }
            
            /* Chaque cellule devient un conteneur avec label + input */
            .form-table th, .form-table td {
                display: block;
                width: auto;
                padding: 5px;
            }
            
            /* Style pour les cellules qui doivent occuper toute la largeur */
            .form-table .full-width-field {
                grid-column: 1 / span 2;
            }
            
            /* Masquer les champs moins utilisés par défaut */
            .hide-if-not-needed {
                display: none;
            }
            
            /* Bouton pour afficher les champs avancés */
            .show-advanced-fields {
                margin: 10px 0;
                color: #2271b1;
                cursor: pointer;
                text-decoration: underline;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Organisation en sections collapsibles
            $('.user-section-header').click(function() {
                $(this).toggleClass('collapsed');
                $(this).next('.user-section-content').toggleClass('hidden');
            });
            
            // Afficher/masquer les champs avancés
            $('.show-advanced-fields').click(function() {
                $('.hide-if-not-needed').toggle();
                $(this).text(function(i, text) {
                    return text === "Afficher les champs avancés" ? "Masquer les champs avancés" : "Afficher les champs avancés";
                });
            });
        });
        </script>
        <?php
    });

    // Réorganiser la page d'édition utilisateur
    add_action('show_user_profile', 'reorganize_user_fields', 1);
    add_action('edit_user_profile', 'reorganize_user_fields', 1);
}

/**
 * Réorganise les champs dans l'interface d'édition utilisateur
 */
function reorganize_user_fields($user) {
    // Ne pas afficher notre organisation personnalisée pour les administrateurs
    if (current_user_can('administrator') && is_super_admin()) {
        return;
    }
    
    // Masquer les sections par défaut que nous allons réorganiser
    add_filter('show_user_profile', 'remove_default_user_fields', 99);
    add_filter('edit_user_profile', 'remove_default_user_fields', 99);
    
    // Informations personnelles
    echo '<div class="user-section-header">Informations personnelles</div>';
    echo '<div class="user-section-content">';
    
    // Champs de base en 2 colonnes
    ?>
    <div class="form-table" role="presentation">
        <div>
            <label for="user_login">Identifiant</label>
            <input type="text" name="user_login" id="user_login" value="<?php echo esc_attr($user->user_login); ?>" disabled="disabled" class="regular-text" />
            <span class="field-help-text">L'identifiant ne peut pas être modifié</span>
        </div>
        
        <div>
            <label for="email">E-mail</label>
            <input type="email" name="email" id="email" value="<?php echo esc_attr($user->user_email); ?>" class="regular-text" />
        </div>
        
        <div>
            <label for="first_name">Prénom</label>
            <input type="text" name="first_name" id="first_name" value="<?php echo esc_attr($user->first_name); ?>" class="regular-text" />
        </div>
        
        <div>
            <label for="last_name">Nom</label>
            <input type="text" name="last_name" id="last_name" value="<?php echo esc_attr($user->last_name); ?>" class="regular-text" />
        </div>
        
        <div>
            <label for="role">Rôle</label>
            <?php 
            // Afficher le sélecteur de rôle seulement pour ceux qui peuvent le modifier
            if (current_user_can('edit_users')) {
                $roles = get_editable_roles();
                echo '<select name="role" id="role">';
                foreach ($roles as $key => $role) {
                    $selected = $user->roles[0] == $key ? 'selected' : '';
                    echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($role['name']) . '</option>';
                }
                echo '</select>';
            } else {
                $user_role = $user->roles[0];
                $roles = get_editable_roles();
                echo '<span>' . $roles[$user_role]['name'] . '</span>';
            }
            ?>
        </div>
        
        <div>
            <label for="display_name">Nom à afficher</label>
            <select name="display_name" id="display_name">
                <?php
                $display_name = $user->display_name;
                $public_display = array();
                $public_display[$user->user_login] = $user->user_login;
                if ( !empty($user->first_name) )
                    $public_display[$user->first_name] = $user->first_name;
                if ( !empty($user->last_name) )
                    $public_display[$user->last_name] = $user->last_name;
                if ( !empty($user->first_name) && !empty($user->last_name) ) {
                    $public_display[$user->first_name . ' ' . $user->last_name] = $user->first_name . ' ' . $user->last_name;
                    $public_display[$user->last_name . ' ' . $user->first_name] = $user->last_name . ' ' . $user->first_name;
                }
                if ( $display_name && ! in_array( $display_name, $public_display ) )
                    $public_display[$display_name] = $display_name;
                
                foreach ( $public_display as $id => $item ) {
                    echo "<option value=\"" . esc_attr($id) . "\"" . selected($display_name, $id, false) . ">" . esc_html($item) . "</option>\n";
                }
                ?>
            </select>
        </div>
    </div>
    <?php
    echo '</div>';
    
    // Section Relations BDD
    echo '<div class="user-section-header">Relations Base de Données</div>';
    echo '<div class="user-section-content">';
    ?>
    <div class="full-width-field">
        <p>Cette section contient les associations entre ce compte utilisateur et les données externes.</p>
    </div>
    
    <div class="form-table">
        <div>
            <label for="formateur">Formateur associé</label>
            <?php 
            // Ici, vous pourriez récupérer et afficher le formateur associé via votre fonction personnalisée
            global $wpdb;
            $linked_formateur_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT `from` FROM {$wpdb->prefix}mb_relationships WHERE `to` = %d AND `type` = 'formateur-bdd-formateur-wp'",
                    $user->ID
                )
            );
            
            // Afficher le formateur sélectionné ou un message
            if ($linked_formateur_id) {
                $formateur_title = get_the_title($linked_formateur_id);
                echo '<span>' . esc_html($formateur_title) . '</span>';
                echo '<input type="hidden" name="linked_formateur_id" value="' . esc_attr($linked_formateur_id) . '">';
            } else {
                echo '<span>Aucun formateur associé</span>';
            }
            ?>
        </div>
        
        <div class="optional-field">
            <label for="client">Client associé</label>
            <select name="client_id" id="client">
                <option value="">-- Sélectionner un client --</option>
                <?php 
                // Ici vous pourriez lister les clients disponibles
                ?>
            </select>
        </div>
        
        <div>
            <label for="siret">SIRET</label>
            <input type="text" name="siret" id="siret" value="<?php echo esc_attr(get_user_meta($user->ID, 'siret', true)); ?>" class="regular-text" />
        </div>
        
        <div>
            <label for="opco">OPCO</label>
            <select name="opco" id="opco">
                <option value="">-- Sélectionner un OPCO --</option>
                <?php 
                $current_opco = get_user_meta($user->ID, 'opco', true);
                $opcos = array('AFDAS', 'ATLAS', 'AKTO', 'OCAPIAT', 'OPCO 2i', 'OPCO EP', 'OPCO Mobilités', 'OPCO Santé', 'Uniformation', 'CONSTRUCTYS');
                foreach ($opcos as $opco) {
                    echo '<option value="' . esc_attr($opco) . '" ' . selected($current_opco, $opco, false) . '>' . esc_html($opco) . '</option>';
                }
                ?>
            </select>
        </div>
    </div>
    
    <div class="full-width-field">
        <p class="show-advanced-fields">Afficher les champs avancés</p>
    </div>
    
    <div class="hide-if-not-needed">
        <div class="form-table">
            <div>
                <label for="prospect">Prospect BDD</label>
                <select name="prospect_id" id="prospect">
                    <option value="">-- Sélectionner un prospect --</option>
                    <?php 
                    // Lister les prospects disponibles
                    ?>
                </select>
            </div>
            
            <div>
                <label for="compte_bdd">Compte BDD</label>
                <select name="compte_bdd" id="compte_bdd">
                    <option value="">-- Sélectionner un compte --</option>
                    <?php 
                    // Lister les comptes disponibles
                    ?>
                </select>
            </div>
            
            <div>
                <label for="relation_compte">Relation compte</label>
                <select name="relation_compte" id="relation_compte">
                    <option value="">-- Sélectionner une relation --</option>
                    <option value="client">Client</option>
                    <option value="prospect">Prospect</option>
                    <option value="partenaire">Partenaire</option>
                </select>
            </div>
            
            <div>
                <label for="referent">Référent</label>
                <input type="checkbox" name="is_referent" id="referent" value="1" <?php checked(get_user_meta($user->ID, 'is_referent', true), '1'); ?> />
                <span class="field-help-text">Cochez si l'utilisateur est référent</span>
            </div>
        </div>
    </div>
    <?php
    echo '</div>';
    
    // Réinitialiser le mot de passe
    echo '<div class="user-section-header">Mot de passe</div>';
    echo '<div class="user-section-content">';
    ?>
    <div class="form-table" role="presentation">
        <div>
            <label for="pass1">Nouveau mot de passe</label>
            <input type="password" name="pass1" id="pass1" class="regular-text" autocomplete="off" />
            <span class="field-help-text">Laissez vide pour conserver le mot de passe actuel</span>
        </div>
        
        <div>
            <label for="pass2">Confirmer le mot de passe</label>
            <input type="password" name="pass2" id="pass2" class="regular-text" autocomplete="off" />
        </div>
        
        <div>
            <label for="send_password">Notifier l'utilisateur</label>
            <input type="checkbox" name="send_user_notification" id="send_password" /> 
            <span class="field-help-text">Envoyer un email à l'utilisateur avec son nouveau mot de passe</span>
        </div>
        
        <div>
            <label for="force_change">Forcer le changement</label>
            <input type="checkbox" name="force_password_change" id="force_change" />
            <span class="field-help-text">L'utilisateur devra changer son mot de passe à sa prochaine connexion</span>
        </div>
    </div>
    <?php
    echo '</div>';
}

/**
 * Supprime les sections par défaut que nous réorganisons
 */
function remove_default_user_fields() {
    return false; // Empêche l'affichage des champs par défaut
}

// Initialiser notre personnalisation
add_action('admin_init', 'simplify_user_edit_screen');