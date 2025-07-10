<?php
/**
 * Snippet ID: 154
 * Name: Metabox champs convoc matin aprem cochés par defaut dans liste stagiaires commande
 * Description: 
 * @active true
 */

// Ajouter ce code dans votre functions.php
function force_check_stagia_convoc_boxes() {
    global $pagenow, $post_type, $typenow;
    
    // Vérifier si nous sommes sur une page d'édition de commande WooCommerce
    $is_order_page = (
        ($pagenow == 'post.php' || $pagenow == 'post-new.php') && 
        ($post_type == 'shop_order' || $typenow == 'shop_order' || 
        (isset($_GET['post_type']) && $_GET['post_type'] == 'shop_order'))
    ) || (
        isset($_GET['page']) && ($_GET['page'] == 'wc-orders' || $_GET['page'] == 'wc-order')
    );
    
    if ($is_order_page) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Pour les nouvelles commandes, cocher toutes les cases des éléments initiaux
            if (window.location.href.indexOf('post-new.php') > -1) {
                $('input[name^="fsbdd_gpeffectif"][name$="[fsbdd_stagiaconvoc][]"]').prop('checked', true);
            }
            
            // Surveiller le clic sur le bouton d'ajout
            $(document).on('click', '.rwmb-add-clone', function() {
                // Attendre que le nouveau clone soit ajouté au DOM
                setTimeout(function() {
                    // Trouver le dernier clone ajouté dans ce groupe
                    const $group = $('.rwmb-group-wrapper');
                    const $lastClone = $group.find('.rwmb-group-clone:last-child, .rwmb-clone:last-child');
                    
                    // Cocher toutes les cases à cocher dans ce nouveau clone uniquement
                    $lastClone.find('input[name^="fsbdd_gpeffectif"][name$="[fsbdd_stagiaconvoc][]"]').prop('checked', true);
                }, 300); // Délai plus long pour être sûr que le clone est ajouté
            });
            
            // Alternative avec l'événement fourni par MetaBox
            $(document).on('clone_added', function(event, $clone) {
                // Cocher les cases dans le clone nouvellement ajouté
                $clone.find('input[name^="fsbdd_gpeffectif"][name$="[fsbdd_stagiaconvoc][]"]').prop('checked', true);
            });
            
            // Backup: observer les mutations du DOM pour attraper tous les cas possibles
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length) {
                        $(mutation.addedNodes).each(function() {
                            const $node = $(this);
                            if ($node.hasClass('rwmb-group-clone') || $node.hasClass('rwmb-clone')) {
                                // Nouveau clone détecté, cocher ses cases
                                setTimeout(function() {
                                    $node.find('input[name^="fsbdd_gpeffectif"][name$="[fsbdd_stagiaconvoc][]"]').prop('checked', true);
                                }, 100);
                            }
                        });
                    }
                });
            });
            
            // Démarrer l'observation sur le conteneur parent
            const $metaBox = $('.rwmb-meta-box');
            if ($metaBox.length) {
                observer.observe($metaBox[0], { childList: true, subtree: true });
            }
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'force_check_stagia_convoc_boxes');