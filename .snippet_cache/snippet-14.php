<?php
/**
 * Snippet ID: 14
 * Name: ENTETE INFOS COMMANDE BOITE RESUMÉ
 * Description: 
 * @active true
 */

// Ajoute les informations sous forme de tableau avec des colonnes dans la boîte d'informations "notice notice-info inline" sur les pages de commande
add_action( 'admin_footer', 'add_order_details_table_to_info_box' );

function add_order_details_table_to_info_box() {
    global $post, $wpdb;

    // 1. Vérification du rôle de l'utilisateur
    $user = wp_get_current_user();
    $allowed_roles = ['administrator', 'compta', 'referent']; // Définir les rôles autorisés

    // Vérifie si l'utilisateur a au moins un des rôles autorisés
    // array_intersect trouve les valeurs communes entre les rôles de l'utilisateur et les rôles autorisés.
    // Si l'intersection n'est pas vide, l'utilisateur a au moins un des rôles.
    if ( empty( array_intersect( $allowed_roles, (array) $user->roles ) ) ) {
        return; // Si l'utilisateur n'a aucun des rôles requis, ne rien faire.
    }

    // 2. Vérification du type de post (comme avant)
    if ( !isset($post) || 'shop_order' !== get_post_type( $post ) ) {
        return;
    }

    // Récupération de la session liée
    $session_data = get_session_data_from_order($post->ID);

    // Récupération des métadonnées
    $fsbdd_check_exotva = get_post_meta( $post->ID, 'fsbdd_check_exotva', true );
    $organisme = ($fsbdd_check_exotva === 'NON') ? 'FS Conseil' : (($fsbdd_check_exotva === 'OUI') ? 'FS' : 'TODO');
    $affaire_num = get_post_meta( $post->ID, 'fsbdd_numcmmde', true );
    $convention_num = $session_data['inter_numero'] ?? 'N/A';

    // Récupération des utilisateurs
    $referent_id = get_post_meta( $post->ID, 'fsbdd_user_referentrel', true );
    $referent = $referent_id ? get_user_by( 'ID', $referent_id )->first_name : 'Non défini';
    $ref_facturation_id = get_post_meta( $post->ID, 'fsbdd_reffactu', true );
    $ref_facturation = $ref_facturation_id ? get_user_by( 'ID', $ref_facturation_id )->first_name : 'Non défini';

    // Gestion OPCO
    $opco_value = get_post_meta( $post->ID, 'fsbdd_financeopco', true );
    $opco_label = ($opco_value === '2') ? 'OUI' : 'NON';
    $num_dossier = get_post_meta( $post->ID, 'fsbddtext_numdossier', true );
    $opco = $opco_label . ($num_dossier ? ' - dossier ' . esc_html( $num_dossier ) : '');

    // Déclenchement
    $order = wc_get_order($post->ID);
    if (!$order) { // Vérification si l'objet commande est valide
        return;
    }
    $customer_user_id = $order->get_customer_id();

    // Utilisation de client_id déjà récupéré dans l'autre fonction si possible, sinon récupération ici
    $client_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT `to` FROM {$wpdb->prefix}mb_relationships WHERE `from` = %d AND `type` = 'clients-wp-bdd'",
        $customer_user_id
    ));
    // Si on a un client_id lié, on le récupère
    $client_title = $client_id ? get_the_title($client_id) : 'Aucun';


    $declenchement = 'N/A';
    if ($client_id) {
        $declenchement_value = get_post_meta($client_id, 'fsbdd_select_suivi_declenchmt', true);
        $declenchement = ($declenchement_value === '1') ? 'OUI' : (($declenchement_value === '0') ? 'NON' : 'N/A');
    }

    // Nouveaux champs de session
    $type_session = $session_data['type_session'] ?? 'N/A';
    $emargements = $session_data['emargements'] ?? 'N/A';
    $cpte_rendu = $session_data['cpte_rendu'] ?? 'N/A';
    $evaluations = $session_data['evaluations'] ?? 'N/A';

    // Création du tableau
    $html_content = '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                        <tr style="background-color: #f0f0f0;">
                            <th style="border: 1px solid #ccc; padding: 8px; width: 60px;">Type</th>
                            <th style="border: 1px solid #ccc; padding: 8px;">Organisme</th>
                            <th style="border: 1px solid #ccc; padding: 8px;">Client</th>
                            <th style="border: 1px solid #ccc; padding: 8px;">Affaire n°</th>
                            <th style="border: 1px solid #ccc; padding: 8px;">Cn N°</th>
                            <th style="border: 1px solid #ccc; padding: 8px;">Référent</th>
                            <th style="border: 1px solid #ccc; padding: 8px;">OPCO</th>
                            <th style="border: 1px solid #ccc; padding: 8px; width: 70px;">Déclenchmt</th>
                            <th style="border: 1px solid #ccc; padding: 8px;">Émargements</th>
                            <th style="border: 1px solid #ccc; padding: 8px;">Cpte rendu F.</th>
                            <th style="border: 1px solid #ccc; padding: 8px;">Évaluations</th>
                            <th style="border: 1px solid #ccc; padding: 8px;">Réf facturation</th>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($type_session) . '</td>
                            <td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($organisme) . '</td>
                            <td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($client_title) . '</td>
                            <td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($affaire_num) . '</td>
                            <td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($convention_num) . '</td>
                            <td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($referent) . '</td>
                            <td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($opco) . '</td>
                            <td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($declenchement) . '</td>
                            <td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($emargements) . '</td>
                            <td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($cpte_rendu) . '</td>
                            <td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($evaluations) . '</td>
                            <td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($ref_facturation) . '</td>
                        </tr>
                    </table>';

    echo '<script type="text/javascript">
            jQuery(document).ready(function($) {
                var infoBox = $(".notice.notice-info.inline");
                if (infoBox.length && infoBox.find("table").length === 0) { // Append table only if not already present
                    infoBox.append(' . json_encode($html_content) . ');
                }
            });
          </script>';
}



// Fonction pour récupérer les données de session
function get_session_data_from_order($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return [];
    }
    $data = [];

    foreach ($order->get_items() as $item_id => $item) {
        // Vérifier si l'item est un produit
        if ( $item instanceof WC_Order_Item_Product ) {
            $session_id = $item->get_meta('fsbdd_relsessaction_cpt_produit', true);
            if ($session_id && get_post_type($session_id) === 'action-de-formation') {
                // Type de session
                $type_value = get_post_meta($session_id, 'fsbdd_typesession', true);
                $data['type_session'] = match($type_value) {
                    '1' => 'INTER',
                    '2' => 'INTER à définir',
                    '3' => 'INTRA',
                    default => 'N/A'
                };

                // Numéro d'intervention
                $data['inter_numero'] = get_post_meta($session_id, 'fsbdd_inter_numero', true) ?: 'N/A';

                // État des documents
                $data['emargements'] = get_status_label(get_post_meta($session_id, 'fsbdd_etatemargm', true));
                $data['cpte_rendu'] = get_status_label(get_post_meta($session_id, 'fsbdd_etatcpterenduf', true));
                $data['evaluations'] = get_status_label(get_post_meta($session_id, 'fsbdd_etateval', true));

                return $data; // On prend la première session trouvée liée à un produit
            }
        }
    }

    // Si aucune session n'est trouvée dans les métas des items, on met des valeurs par défaut
    $data['type_session'] = 'N/A';
    $data['inter_numero'] = 'N/A';
    $data['emargements'] = 'N/A';
    $data['cpte_rendu'] = 'N/A';
    $data['evaluations'] = 'N/A';

    return $data;
}

// Fonction de conversion des statuts
function get_status_label($value) {
    return match($value) {
        '1' => 'Vide',
        '2' => 'Partiel',
        '3' => 'Reçus',
        '4' => 'Certifié',
        default => 'N/A'
    };
}


// Relation bdd clients prospects metabox.io ET ajout du nom produit
add_action( 'admin_footer', 'add_order_details_with_relations_to_info_box' );

function add_order_details_with_relations_to_info_box() {
    global $post, $wpdb;

    // Assurez-vous que c'est une page de commande WooCommerce
    if ( !isset($post) || 'shop_order' !== get_post_type( $post ) ) {
        return;
    }

    // Récupération de la commande
    $order = wc_get_order( $post->ID );
    if (!$order) {
        return; // Sortir si l'objet commande n'est pas valide
    }
    $customer_user_id = $order->get_customer_id();

    // --- Récupération du nom et ID du premier produit ---
    $product_output = __('Formation non trouvée', 'textdomain'); // Valeur par défaut
    $items = $order->get_items();
    if ( ! empty( $items ) ) {
        $first_item = reset( $items ); // Prend le premier élément du tableau d'items
        if ( $first_item instanceof WC_Order_Item_Product ) {
            $product_id = $first_item->get_product_id();
            $product_name = $first_item->get_name();
            if ( $product_id && $product_name ) {
                 // Crée le lien vers la page d'édition du produit dans un nouvel onglet
                 $product_edit_link = get_edit_post_link( $product_id );
                 if ($product_edit_link) {
                      $product_output = sprintf(
                          '<a href="%s" target="_blank">%s</a>',
                          esc_url( $product_edit_link ),
                          esc_html( $product_name )
                      );
                 } else {
                      // Si on ne trouve pas le lien d'édition (peu probable mais par sécurité)
                      $product_output = esc_html( $product_name );
                 }
            }
        }
    }
    // --- Fin récupération produit ---


    // Récupération des relations Prospect et Client
    $prospect_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT `to` FROM {$wpdb->prefix}mb_relationships WHERE `from` = %d AND `type` = 'prospects-wp-bdd'",
        $customer_user_id
    ));
    $client_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT `to` FROM {$wpdb->prefix}mb_relationships WHERE `from` = %d AND `type` = 'clients-wp-bdd'",
        $customer_user_id
    ));

    // Préparer les informations pour l'affichage
    $prospect_info = $prospect_id
        ? '<a href="' . get_edit_post_link( $prospect_id ) . '">' . esc_html( get_the_title( $prospect_id ) ) . '</a>'
        : __('Aucun prospect associé', 'textdomain');

    $client_info = $client_id
        ? '<a href="' . get_edit_post_link( $client_id ) . '">' . esc_html( get_the_title( $client_id ) ) . '</a>'
        : __('Aucun client associé', 'textdomain');


    // Créer le contenu HTML, incluant le lien vers la formation
    // Changement de "Produit" à "Formation" et utilisation de $product_output
    $relations_content = '<p style="margin-top: 10px;" id="bdd-relations-info"><strong>RELATIONS BDD - Client :</strong> ' . $client_info . ' | <strong>Prospect :</strong> ' . $prospect_info . ' | <strong>Formation :</strong> ' . $product_output . '</p>';

    // JavaScript pour insérer le contenu après le tableau existant (ou à la fin si le tableau n'existe pas encore)
    echo '<script type="text/javascript">
            jQuery(document).ready(function($) {
                var infoBox = $(".notice.notice-info.inline");
                if (infoBox.length) {
                    // Try to append after the table, otherwise append to the box
                    var existingTable = infoBox.find("table");
                    // Check if the relations info is already added to prevent duplicates
                    if ($("#bdd-relations-info").length === 0) {
                         if (existingTable.length) {
                             existingTable.after(' . json_encode( $relations_content ) . ');
                         } else {
                             infoBox.append(' . json_encode( $relations_content ) . ');
                         }
                    }
                }
            });
          </script>';
}

