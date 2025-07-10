<?php
/**
 * Snippet ID: 127
 * Name: gestion grille tarifaire
 * Description: 
 * @active false
 */

/**
 * Création d’une page d’administration pour gérer la grille tarifaire
 */

// 1. Ajout d’un menu dédié dans l’admin
add_action( 'admin_menu', 'my_caces_price_table_admin_menu' );
function my_caces_price_table_admin_menu() {
    add_menu_page(
        'Grille Tarifaire CACES',
        'Grille CACES',            // Titre dans le menu
        'manage_options',          // Capacité requise
        'grille-caces',            // Slug unique de la page
        'my_caces_price_table_page_html',  // Fonction d’affichage
        'dashicons-chart-area',    // Icône (optionnel)
        70                         // Position dans le menu (optionnel)
    );
}

// 2. Déclaration de la setting pour stocker la grille (format JSON)
add_action( 'admin_init', 'my_caces_register_settings' );
function my_caces_register_settings() {
    register_setting( 'my_caces_price_table_group', 'my_caces_price_table', array(
        'type'              => 'string',
        'sanitize_callback' => 'wp_kses_post', // ou sanitize_textarea_field()
        'default'           => ''
    ) );
}

// 3. Contenu de la page d’admin
function my_caces_price_table_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Message de confirmation après enregistrement
    if ( isset($_GET['settings-updated']) ) {
        add_settings_error( 'my_caces_price_table_messages', 'my_caces_price_table_message', 'Grille mise à jour.', 'updated' );
    }
    settings_errors( 'my_caces_price_table_messages' );
    ?>
    <div class="wrap">
        <h1>Grille Tarifaire CACES</h1>
        <form method="post" action="options.php">
            <?php 
            // Champs de sécurité et sections
            settings_fields( 'my_caces_price_table_group' );
            do_settings_sections( 'my_caces_price_table_group' );
            ?>
            <p>
                <textarea name="my_caces_price_table" rows="20" cols="100"><?php 
                    echo esc_textarea( get_option('my_caces_price_table') ); 
                ?></textarea>
            </p>
            <?php submit_button('Enregistrer'); ?>
        </form>
    </div>
    <?php
}

// 4. Fonction de récupération de la grille (appelée depuis ton code)
function get_full_price_table() {
    $json_data = get_option( 'my_caces_price_table' );
    if ( empty( $json_data ) ) {
        // Ici, tu peux retourner un tableau par défaut si aucune grille n’est enregistrée.
        return array();
    }
    $decoded = json_decode( $json_data, true );
    return is_array($decoded) ? $decoded : array();
}
