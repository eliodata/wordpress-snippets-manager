<?php
/**
 * Snippet ID: 133
 * Name: nom prenom front end stagiaires liste champs clones commandes metabox io
 * Description: <p>description 2</p>
 * @active true
 */


// Affichage du formulaire de stagiaires sur la page de checkout
function display_stagiaires_clone_fields() {
    if ( ! WC()->cart ) {
        return;
    }
    
    // Nombre total de stagiaires = quantité totale d'articles dans le panier
    $total_fields = WC()->cart->get_cart_contents_count();
    
    // Logique des champs requis :
    // Si le total est inférieur ou égal à 6, tous les champs sont requis,
    // sinon, aucun champ n'est requis.
    $fields_required     = ( $total_fields <= 6 );
    $required_attribute  = $fields_required ? 'required' : '';
    $prenom_placeholder  = $fields_required ? 'Prénom *' : 'Prénom';
    $nom_placeholder     = $fields_required ? 'Nom *'    : 'Nom';
    
    // Calcul du nombre de pages (6 stagiaires par page)
    $pages = ceil( $total_fields / 6 );
    ?>
    <h3 style="margin-bottom:10px;">Stagiaires</h3>
    <div id="stagiaires_clone_fields" style="display: flex; flex-wrap: wrap; gap: 20px;">
        <?php 
        // Boucle sur l'ensemble des stagiaires à afficher
        for ( $i = 0; $i < $total_fields; $i++ ):
            // Calcul de la "page" à laquelle appartient ce stagiaire
            $page = floor( $i / 6 );
            ?>
            <div class="stagiaire_item page-<?php echo $page; ?>" 
                 style="flex: 1 1 calc(50% - 20px); box-sizing: border-box; <?php echo ($page === 0 ? '' : 'display:none;'); ?>">
                <div class="field-group" style="display: flex; gap: 10px;">
                    <input type="text" 
                           name="fsbdd_gpeffectif[<?php echo $i; ?>][fsbdd_prenomstagiaire]" 
                           id="fsbdd_gpeffectif_<?php echo $i; ?>_prenom" 
                           placeholder="<?php echo $prenom_placeholder; ?>" 
                           style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 3px;"
                           <?php echo $required_attribute; ?> />
                    <input type="text" 
                           name="fsbdd_gpeffectif[<?php echo $i; ?>][fsbdd_nomstagiaire]" 
                           id="fsbdd_gpeffectif_<?php echo $i; ?>_nom" 
                           placeholder="<?php echo $nom_placeholder; ?>" 
                           style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 3px;"
                           <?php echo $required_attribute; ?> />
                </div>
            </div>
        <?php endfor; ?>
    </div>
    
    <?php if ( $pages > 1 ): ?>
        <div id="stagiaires_pagination" style="margin-top:20px; text-align: right; width:100%;">
            <button type="button" id="stagiaires_next" 
                    style="padding:8px 16px; border: none; background: #0071a1; color: #fff; border-radius: 3px; cursor: pointer;">
                Suivant(s)
            </button>
        </div>
    <?php endif; ?>
    
    <script>
    jQuery(document).ready(function($){
        // S'assurer que la première page (page-0) est affichée
        $('.stagiaire_item.page-0').show();
        
        // On démarre le compteur à 1 puisque la page 0 est déjà affichée
        var currentPage = 1;
        var totalPages  = <?php echo $pages; ?>;
        
        function updatePagination() {
            if ( currentPage >= totalPages ) {
                $('#stagiaires_next').hide();
            } else {
                $('#stagiaires_next').show();
            }
        }
        
        updatePagination();
        
        $('#stagiaires_next').on('click', function(e){
            e.preventDefault();
            // Affiche le bloc de stagiaires de la page "currentPage" sans masquer ceux déjà affichés
            $('.stagiaire_item.page-' + currentPage).show();
            currentPage++;
            updatePagination();
        });
    });
    </script>
    <?php
}
add_action( 'woocommerce_after_order_notes', 'display_stagiaires_clone_fields' );


// Sauvegarde des données saisies dans l’order meta lors du checkout
add_action( 'woocommerce_checkout_update_order_meta', 'save_stagiaires_fields_to_order' );
function save_stagiaires_fields_to_order( $order_id ) {
    if ( isset( $_POST['fsbdd_gpeffectif'] ) && is_array( $_POST['fsbdd_gpeffectif'] ) ) {
        $stagiaires = array();
        foreach ( $_POST['fsbdd_gpeffectif'] as $key => $value ) {
            $stagiaires[] = array(
                'fsbdd_prenomstagiaire' => isset( $value['fsbdd_prenomstagiaire'] ) ? sanitize_text_field( $value['fsbdd_prenomstagiaire'] ) : '',
                'fsbdd_nomstagiaire'    => isset( $value['fsbdd_nomstagiaire'] ) ? sanitize_text_field( $value['fsbdd_nomstagiaire'] ) : '',
            );
        }
        update_post_meta( $order_id, 'fsbdd_gpeffectif', $stagiaires );
    }
}


// Affichage des données des stagiaires dans les emails WooCommerce
add_filter( 'woocommerce_email_order_meta_fields', 'display_stagiaires_in_email', 10, 3 );
function display_stagiaires_in_email( $fields, $sent_to_admin, $order ) {
    $stagiaires = get_post_meta( $order->get_id(), 'fsbdd_gpeffectif', true );
    if ( ! empty( $stagiaires ) && is_array( $stagiaires ) ) {
        $value = '';
        foreach ( $stagiaires as $stagiaire ) {
            $prenom = isset( $stagiaire['fsbdd_prenomstagiaire'] ) ? $stagiaire['fsbdd_prenomstagiaire'] : '';
            $nom    = isset( $stagiaire['fsbdd_nomstagiaire'] ) ? $stagiaire['fsbdd_nomstagiaire'] : '';
            $value .= $prenom . ' ' . $nom . '<br/>';
        }
        $fields['fsbdd_gpeffectif'] = array(
            'label' => __( 'Liste des stagiaires', 'textdomain' ),
            'value' => $value,
        );
    }
    return $fields;
}


