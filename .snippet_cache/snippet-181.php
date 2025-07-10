<?php
/**
 * Snippet ID: 181
 * Name: Bouton Générer liste stagiaires depuis champ import pilotage
 * Description: 
 * @active true
 */


// Ajouter le script JavaScript sur la page d'édition de commande
add_action( 'admin_footer', 'add_stagiaires_generator_script' );
function add_stagiaires_generator_script() {
    $screen = get_current_screen();
    if ( $screen->id !== 'shop_order' ) {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Fonction pour gérer le clic sur le bouton
        $(document).on('click', '#generate-stagiaires-btn', function(e) {
            e.preventDefault();
            
            // Récupérer le contenu du textarea stagiaires_facturation
            var textarea = $('textarea[name="stagiaires_facturation"]');
            if (!textarea.length) {
                // Essayer avec différents sélecteurs possibles
                textarea = $('#stagiaires_facturation');
            }
            
            if (!textarea.length || !textarea.val().trim()) {
                $('#stagiaires-message').text('Aucun stagiaire trouvé dans le champ de facturation').css('color', 'red').show();
                setTimeout(function() {
                    $('#stagiaires-message').hide();
                }, 3000);
                return;
            }
            
            var stagiairesList = textarea.val().trim();
            var orderId = $('#post_ID').val();
            
            // Afficher un message de chargement
            $('#stagiaires-message').text('Génération en cours...').css('color', 'blue').show();
            
            // Appel AJAX pour traiter les données
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'generate_stagiaires_list',
                    order_id: orderId,
                    stagiaires_text: stagiairesList,
                    security: '<?php echo wp_create_nonce( "generate_stagiaires_nonce" ); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#stagiaires-message').text('Liste générée avec succès ! ' + response.data.count + ' stagiaire(s) ajouté(s).').css('color', 'green').show();
                        // Recharger la page après 2 secondes pour voir les changements
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $('#stagiaires-message').text('Erreur : ' + response.data).css('color', 'red').show();
                    }
                },
                error: function() {
                    $('#stagiaires-message').text('Erreur lors de la génération').css('color', 'red').show();
                }
            });
        });
    });
    </script>
    <?php
}

// Handler AJAX pour générer la liste des stagiaires
add_action( 'wp_ajax_generate_stagiaires_list', 'handle_generate_stagiaires_list' );
function handle_generate_stagiaires_list() {
    // Vérifier le nonce
    if ( ! wp_verify_nonce( $_POST['security'], 'generate_stagiaires_nonce' ) ) {
        wp_send_json_error( 'Erreur de sécurité' );
    }
    
    // Vérifier les permissions
    if ( ! current_user_can( 'edit_shop_orders' ) ) {
        wp_send_json_error( 'Permissions insuffisantes' );
    }
    
    $order_id = intval( $_POST['order_id'] );
    $stagiaires_text = sanitize_textarea_field( $_POST['stagiaires_text'] );
    
    if ( ! $order_id || ! $stagiaires_text ) {
        wp_send_json_error( 'Données manquantes' );
    }
    
    // OPTIONNEL : Débugger les valeurs existantes des convocations
    // Décommentez ces lignes pour voir les valeurs actuelles dans la console
    // $existing_stagiaires = get_post_meta( $order_id, 'fsbdd_gpeffectif', true );
    // if ( ! empty( $existing_stagiaires ) && is_array( $existing_stagiaires ) ) {
    //     foreach ( $existing_stagiaires as $stagiaire ) {
    //         if ( isset( $stagiaire['fsbdd_stagiaconvoc'] ) ) {
    //             error_log( 'Valeurs convocation existantes : ' . print_r( $stagiaire['fsbdd_stagiaconvoc'], true ) );
    //             break;
    //         }
    //     }
    // }
    
    // Parser le texte des stagiaires
    $stagiaires_array = parse_stagiaires_text( $stagiaires_text );
    
    if ( empty( $stagiaires_array ) ) {
        wp_send_json_error( 'Aucun stagiaire trouvé dans le texte' );
    }
    
    // Sauvegarder dans les meta de la commande
    update_post_meta( $order_id, 'fsbdd_gpeffectif', $stagiaires_array );
    
    // Débugger ce qui a été sauvegardé (désactivé)
    // error_log( 'Stagiaires sauvegardés : ' . print_r( $stagiaires_array, true ) );
    
    wp_send_json_success( array( 'count' => count( $stagiaires_array ) ) );
}

// Fonction pour parser le texte des stagiaires
function parse_stagiaires_text( $text ) {
    $stagiaires = array();
    
    // Nettoyer le texte
    $text = trim( $text );
    
    // Détecter le séparateur principal (- ou ,)
    if ( strpos( $text, ' - ' ) !== false ) {
        // Format : NOM PRENOM - NOM PRENOM
        $separator = ' - ';
    } else {
        // Format : Prénom NOM, Prénom NOM
        $separator = ',';
    }
    
    // Séparer les stagiaires
    $stagiaires_raw = array_map( 'trim', explode( $separator, $text ) );
    
    foreach ( $stagiaires_raw as $stagiaire_str ) {
        if ( empty( $stagiaire_str ) ) {
            continue;
        }
        
        // Parser nom et prénom
        $parsed = parse_nom_prenom( $stagiaire_str );
        if ( $parsed ) {
            $stagiaires[] = $parsed;
        }
    }
    
    return $stagiaires;
}

// Fonction pour détecter les valeurs des checkboxes de convocation
function get_convocation_values() {
    // D'après votre configuration MetaBox, les valeurs sont "1" et "2"
    $default_values = array( '1', '2' );
    
    // IMPORTANT : Si vos checkboxes utilisent d'autres valeurs,
    // modifiez le tableau ci-dessus. Exemples :
    // - Pour des valeurs texte : array( 'matin', 'apres-midi' );
    // - Pour des valeurs en anglais : array( 'morning', 'afternoon' );
    // - Pour d'autres valeurs : array( 'am', 'pm' );
    
    // Pour vérifier les bonnes valeurs dans votre installation :
    // 1. Créez manuellement un stagiaire avec les cases cochées
    // 2. Regardez dans la base de données la valeur de 'fsbdd_stagiaconvoc'
    // 3. Utilisez ces valeurs ici
    
    // Permettre la personnalisation via un filtre
    return apply_filters( 'stagiaires_convocation_default_values', $default_values );
}

// Fonction pour parser un nom complet en prénom et nom
function parse_nom_prenom( $fullname ) {
    $fullname = trim( $fullname );
    if ( empty( $fullname ) ) {
        return false;
    }
    
    // Séparer les mots
    $parts = array_filter( explode( ' ', $fullname ) );
    
    // Récupérer les valeurs de convocation
    $convocation_values = get_convocation_values();
    
    if ( count( $parts ) < 2 ) {
        // Pas assez de parties pour avoir nom et prénom
        return array(
            'fsbdd_prenomstagiaire' => '',
            'fsbdd_nomstagiaire'    => $fullname,
            'fsbdd_stagiaconvoc'    => $convocation_values, // Convocations cochées par défaut
        );
    }
    
    // Déterminer si c'est format "NOM PRENOM(S)" ou "Prénom NOM"
    // Si le premier mot est tout en majuscules, c'est probablement le nom
    $first_is_uppercase = ( strtoupper( $parts[0] ) === $parts[0] );
    
    if ( $first_is_uppercase ) {
        // Format "NOM PRENOM PRENOM2..."
        $nom = array_shift( $parts );
        $prenom = implode( ' ', $parts );
    } else {
        // Format "Prénom NOM" ou cas ambigu
        // On suppose que le dernier mot est le nom
        $nom = array_pop( $parts );
        $prenom = implode( ' ', $parts );
    }
    
    return array(
        'fsbdd_prenomstagiaire' => $prenom,
        'fsbdd_nomstagiaire'    => $nom,
        'fsbdd_stagiaconvoc'    => $convocation_values, // Convocations cochées par défaut
    );
}

// EXEMPLE : Pour personnaliser les valeurs des convocations, ajoutez ce code :
// add_filter( 'stagiaires_convocation_default_values', function( $values ) {
//     return array( '1', '2' ); // Remplacez par vos valeurs réelles
// });
