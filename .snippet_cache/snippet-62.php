<?php
/**
 * Snippet ID: 62
 * Name: PLUGIN SWITCH CLIENT PROSPECT FORMATEUR FOURNISSEUR POST TYPE
 * Description: 
 * @active true
 */

// Ajouter une métabox avec un sélecteur
function add_custom_post_type_switcher_metabox() {
    // Vérifier si l'utilisateur connecté a l'un des rôles spécifiés
    if (!current_user_can('administrator') && !current_user_can('referent') && !current_user_can('compta')) {
        return;
    }

    add_meta_box(
        'custom_post_type_switcher',
        'Changer le type de contenu',
        'render_custom_post_type_switcher_metabox',
        ['prospect', 'client', 'formateur', 'formateur-passe', 'salle-de-formation'], // Ajouter la métabox pour ces types uniquement
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'add_custom_post_type_switcher_metabox');

function render_custom_post_type_switcher_metabox($post) {
    // Obtenir le type actuel
    $current_post_type = get_post_type($post);

    // Liste des types autorisés par groupe
    $post_type_groups = [
        'client' => ['client', 'prospect'],
        'prospect' => ['client', 'prospect'],
        'formateur' => ['formateur', 'formateur-passe', 'salle-de-formation'],
        'formateur-passe' => ['formateur', 'formateur-passe', 'salle-de-formation'],
        'salle-de-formation' => ['formateur', 'formateur-passe', 'salle-de-formation'],
    ];

    // Vérifier si le type actuel est dans un groupe autorisé
    if (!isset($post_type_groups[$current_post_type])) {
        echo '<p>Ce type de contenu ne peut pas être modifié.</p>';
        return;
    }

    // Générer le sélecteur uniquement pour les types autorisés
    $allowed_types = $post_type_groups[$current_post_type];
    echo '<label for="custom_post_type_selector">Type : </label>';
    echo '<select name="custom_post_type_selector" id="custom_post_type_selector">';
    foreach ($allowed_types as $type) {
        $label = ucfirst(str_replace('-', ' ', $type)); // Formater le label
        echo '<option value="' . esc_attr($type) . '" ' . selected($type, $current_post_type, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';

    // Ajout d'un champ nonce pour la sécurité
    wp_nonce_field('custom_post_type_switcher_nonce', 'custom_post_type_switcher_nonce_field');
}

// Sauvegarder le changement de type de contenu
function save_custom_post_type_switcher($post_id) {
    // Vérifier les autorisations pour sauvegarder
    if (
        !current_user_can('administrator') && 
        !current_user_can('referent') && 
        !current_user_can('compta')
    ) {
        return;
    }

    // Vérifier le nonce pour la sécurité
    if (!isset($_POST['custom_post_type_switcher_nonce_field']) || 
        !wp_verify_nonce($_POST['custom_post_type_switcher_nonce_field'], 'custom_post_type_switcher_nonce')) {
        return;
    }

    // Vérifier les autorisations pour l'édition du post
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Obtenir le type actuel
    $current_post_type = get_post_type($post_id);

    // Liste des types autorisés par groupe
    $post_type_groups = [
        'client' => ['client', 'prospect'],
        'prospect' => ['client', 'prospect'],
        'formateur' => ['formateur', 'formateur-passe', 'salle-de-formation'],
        'formateur-passe' => ['formateur', 'formateur-passe', 'salle-de-formation'],
        'salle-de-formation' => ['formateur', 'formateur-passe', 'salle-de-formation'],
    ];

    // Vérifier si le type actuel est dans un groupe autorisé
    if (!isset($post_type_groups[$current_post_type])) {
        return;
    }

    // Vérifier si un type a été sélectionné
    if (isset($_POST['custom_post_type_selector'])) {
        $new_post_type = sanitize_text_field($_POST['custom_post_type_selector']);

        // Vérifier que le nouveau type est dans le même groupe que l'actuel
        if (in_array($new_post_type, $post_type_groups[$current_post_type], true) && $new_post_type !== $current_post_type) {
            set_post_type($post_id, $new_post_type);
        }
    }
}
add_action('save_post', 'save_custom_post_type_switcher');