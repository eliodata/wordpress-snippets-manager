<?php
/**
 * Snippet ID: 164
 * Name: Note commandes relances factures admin client
 * Description: 
 * @active true
 */

/**
 * ======================================================================
 * Snippet pour les Notes de Relance Facture WooCommerce
 * - Ajoute une case à cocher "Relance facture" 
 * - Style les notes contenant "relance"
 * - Facilite l'ajout de notes de relance via JS
 * - Crée une metabox dédiée pour l'historique des relances
 * - Ajoute la date de la dernière relance au titre de la metabox
 * - Enregistre la date de dernière relance dans le champ "fsbbd_suivifactu"
 * - Compatible avec notes privées et notes client
 * ======================================================================
 */

// ----------------------------------------------------------------------
// 1. CSS et JavaScript pour l'interface d'administration
// ----------------------------------------------------------------------
function fsb_admin_styles_scripts_relance_facture() {
    // Vérifier si nous sommes sur une page d'édition de commande WooCommerce
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'shop_order' || $screen->base !== 'post') {
        return;
    }
    ?>
<style>
    /* Style pour les notes contenant "relance" */
    .order_notes li.note:contains("relance") .note_content { /* Sélecteur jQuery :contains */
        background-color: #FFEAD5 !important;
        border-left: 3px solid #FF9E44 !important;
        padding: 8px !important;
        margin: 5px 0 !important;
        border-radius: 2px !important;
    }

    /* Style pour la case à cocher de relance */
    .fsb-relance-checkbox-wrapper {
        margin-top: 8px;
        margin-bottom: 8px;
        padding: 5px 8px;
        background-color: #fafafa;
        border-radius: 3px;
        border-left: 3px solid #FF9E44;
    }
    
    .fsb-relance-checkbox-wrapper label {
        display: flex;
        align-items: center;
        cursor: pointer;
    }
    
    .fsb-relance-checkbox-wrapper input[type="checkbox"] {
        margin-right: 8px;
    }
    
    /* Style pour le contenu DANS la metabox dédiée */
    #relances_facture_box .relance-content {
        background-color: #FFEAD5;
        border-left: 3px solid #FF9E44;
        padding: 6px 8px;
        border-radius: 2px;
        margin-left: 10px;
    }

    /* Style pour la MetaBox d'historique des relances */
    #relances_facture_box .inside {
        padding: 0;
        margin: 0;
        max-height: 300px;
        overflow-y: auto;
    }

    #relances_facture_box h2.hndle {
        color: #23282d;
        padding: 8px 12px;
        margin: 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 14px;
    }

    /* Span pour la date dans le titre */
    #relances_facture_box h2.hndle .last-relance-date {
        font-weight: normal;
        font-size: 12px;
        color: #50575e;
        margin-left: 8px;
        margin-right: auto;
        white-space: nowrap;
    }

    /* Style pour le lien d'ajout dans le titre */
    .ajouter-relance-link {
        font-size: 12px;
        font-weight: normal;
        background: #fff;
        border: none;
        padding: 3px 8px;
        border-radius: 3px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        transition: all 0.1s ease;
        cursor: pointer;
        flex-shrink: 0;
    }

    .ajouter-relance-link:hover {
        background: #FFC374;
    }

    /* Icône + */
    .ajouter-relance-link:before {
        content: "+";
        margin-right: 5px;
        font-weight: bold;
        font-size: 14px;
        line-height: 1;
    }

    /* Styles pour le contenu de la metabox */
    .relances-historique {
        padding: 0;
        margin: 0;
    }

    .relance-item {
        padding: 8px 12px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        font-size: 13px;
    }

    .relance-item:last-child {
        border-bottom: none;
    }

    .relance-meta {
        font-size: 12px;
        color: #50575e;
        min-width: 160px;
        flex-shrink: 0;
        white-space: nowrap;
    }

    .relance-content {
        margin: 0;
        flex: 1;
    }
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Fonction pour ajouter la case à cocher après le select de type de note
    function ajouterCheckboxRelanceFacture() {
        var $select = $('select#order_note_type, select[name="note_type"]');
        if ($select.length && $('#fsb_relance_facture_checkbox').length === 0) {
            // Ajouter un wrapper stylisé autour de la checkbox
            $select.after('<div class="fsb-relance-checkbox-wrapper"><label><input type="checkbox" id="fsb_relance_facture_checkbox" name="fsb_relance_facture"> Relance facture</label></div>');
        }
    }

    // Fonction pour appliquer le style aux notes contenant "relance"
    function stylerNotesRelanceVisibles() {
        $('.order_notes li.note').each(function() {
            if ($(this).text().toLowerCase().indexOf('relance') !== -1) {
                $(this).find('.note_content').css({
                    'background-color': '#FFEAD5',
                    'border-left': '3px solid #FF9E44',
                    'padding': '8px',
                    'margin': '5px 0',
                    'border-radius': '2px'
                });
            }
        });
    }

    // Ajouter un lien "Ajouter" dans le titre de la metabox
    function ajouterLienAjouterMetabox() {
        var $titreMetabox = $('#relances_facture_box h2.hndle');
        if ($titreMetabox.length) {
            if ($titreMetabox.find('.last-relance-date').length === 0) {
                $titreMetabox.append('<span class="last-relance-date"></span>');
            }
            if ($titreMetabox.find('.ajouter-relance-link').length === 0) {
                $titreMetabox.append('<a href="#add_order_note" class="ajouter-relance-link">Ajouter</a>');
            }
        }
    }

    // Appliquer immédiatement
    ajouterCheckboxRelanceFacture();
    stylerNotesRelanceVisibles();
    ajouterLienAjouterMetabox();

    // Observer les changements du formulaire de note
    var noteFormObserver = new MutationObserver(function(mutations) {
        setTimeout(function() {
            ajouterCheckboxRelanceFacture();
        }, 100);
    });
    
    // Cibler le formulaire ou son conteneur parent
    var $noteForm = $('#commentsdiv .inside form');
    if ($noteForm.length) {
        noteFormObserver.observe($noteForm[0], { childList: true, subtree: true });
    }

    // Observer les changements dans la liste des notes
    var notesObserver = new MutationObserver(function(mutations) {
        let nodeAdded = mutations.some(m => m.addedNodes.length > 0);
        if (nodeAdded) {
            setTimeout(function() {
                ajouterCheckboxRelanceFacture();
                stylerNotesRelanceVisibles();
                
                // Vérifier si une nouvelle note de relance a été ajoutée
                var $newNote = $('.order_notes li.note:first');
                var isRelance = $newNote.length && ($newNote.text().toLowerCase().indexOf('relance') !== -1);
                var hasRelanceMetabox = $('#relances_facture_box').length > 0;
                
                // Si c'est une relance mais que la metabox n'existe pas encore, recharger la page
                if (isRelance && !hasRelanceMetabox) {
                    location.reload();
                }
            }, 150);
        }
    });
    
    var $notesContainer = $('#woocommerce-order-notes .inside .order_notes');
    if ($notesContainer.length) {
        notesObserver.observe($notesContainer[0], { childList: true });
    }

    // Gestionnaire de clic pour les boutons "Ajouter"
    function gererClicAjouterRelance(e) {
        e.preventDefault();
        var $commentsDiv = $("#commentsdiv");
        if(!$commentsDiv.length) return;

        // Défilement vers la section des notes
        $('html, body').animate({ scrollTop: $commentsDiv.offset().top - 60 }, 500);
        
        // Ouvrir la boîte si elle est fermée
        if ($commentsDiv.hasClass('closed')) { 
            $commentsDiv.find('.handlediv').trigger('click'); 
        }

        // Augmenter le délai pour s'assurer que les éléments sont chargés
        setTimeout(function() {
            // S'assurer que la case existe
            ajouterCheckboxRelanceFacture();
            
            // Attendre encore un peu pour s'assurer que la case est bien dans le DOM
            setTimeout(function() {
                var $checkbox = $('#fsb_relance_facture_checkbox');
                var $textarea = $commentsDiv.find('#add_order_note');

                if ($checkbox.length) {
                    // Cocher la case
                    $checkbox.prop('checked', true);
                    
                    // Déclencher l'événement change pour ajouter "RELANCE:" si nécessaire
                    $checkbox.trigger('change');
                    
                    if ($textarea.length) { 
                        $textarea.focus();
                        // Suggestion de texte pour la relance (si vide)
                        if ($textarea.val() === '') {
                            $textarea.val('RELANCE: Relance de facture effectuée');
                        }
                    }
                }
            }, 200);
        }, 800); // Délai augmenté
    }

    // Surveiller le clic sur le bouton "Soumettre" quand la case relance est cochée
    $(document).on('click', '#add_order_note_button', function() {
        if ($('#fsb_relance_facture_checkbox').is(':checked')) {
            var noteText = $('#add_order_note').val();
            if (noteText && !noteText.startsWith('RELANCE:')) {
                $('#add_order_note').val('RELANCE: ' + noteText);
            }
        }
        
        // Attendre que la note soit ajoutée puis décocher la case
        setTimeout(function() {
            // Décocher la case
            $('#fsb_relance_facture_checkbox').prop('checked', false);
            
            // Réinitialiser le champ de texte
            $('#add_order_note').val('');
        }, 500);
    });
    
    // Protéger le préfixe "RELANCE:" contre les suppressions
    $(document).on('input', '#add_order_note', function() {
        if ($('#fsb_relance_facture_checkbox').is(':checked')) {
            var noteText = $(this).val();
            if (!noteText.startsWith('RELANCE:')) {
                // Récupérer le texte sans le préfixe RELANCE:
                var cleanText = noteText.replace(/RELANCE:\s*/g, '');
                // Remettre le préfixe
                $(this).val('RELANCE: ' + cleanText);
            }
        }
    });
    
    // Ajouter/supprimer automatiquement le préfixe quand on coche/décoche
    $(document).on('change', '#fsb_relance_facture_checkbox', function() {
        var $textarea = $('#add_order_note');
        var noteText = $textarea.val();
        
        if ($(this).is(':checked')) {
            if (!noteText.startsWith('RELANCE:')) {
                $textarea.val('RELANCE: ' + noteText);
            }
        } else {
            // Si on décoche, supprimer le préfixe
            $textarea.val(noteText.replace(/^RELANCE:\s*/, ''));
        }
    });

    // Attacher aux sélecteurs pour les liens d'ajout de relance
    $(document).off('click', '.ajouter-relance-link');
    $(document).off('click', '.ajouter-relance-btn');
    $(document).on('click', '.ajouter-relance-link', gererClicAjouterRelance);
    $(document).on('click', '.ajouter-relance-btn', gererClicAjouterRelance);
});
</script>
    <?php
}
add_action('admin_head', 'fsb_admin_styles_scripts_relance_facture', 20);


// ----------------------------------------------------------------------
// 2. Logique PHP pour les notes de relance
// ----------------------------------------------------------------------

// Modifiez la fonction fsb_marquer_note_relance_facture
function fsb_marquer_note_relance_facture($comment_id) {
    // Vérifie si la case relance facture est cochée
    if (isset($_POST['fsb_relance_facture'])) {
        // Marque le commentaire avec la meta 'is_relance_facture'
        update_comment_meta($comment_id, 'is_relance_facture', '1');
        
        // Récupérer le commentaire et son contenu
        $comment = get_comment($comment_id);
        if ($comment && $comment->comment_post_ID) {
            $order_id = $comment->comment_post_ID;
            $content = $comment->comment_content;
            
            // TOUJOURS ajouter le préfixe "RELANCE:" si pas déjà présent au début
            if (strpos($content, 'RELANCE:') !== 0) {
                $new_content = "RELANCE: " . $content;
                wp_update_comment(array(
                    'comment_ID' => $comment_id,
                    'comment_content' => $new_content
                ));
            }
            
            // Mettre à jour la date de dernière relance dans le champ metabox.io
            $current_date = date_i18n('d/m/Y');
            update_post_meta($order_id, 'fsbbd_suivifactu', $current_date);
        }
    }
}
add_action('comment_post', 'fsb_marquer_note_relance_facture', 10, 1);


// Récupérer les notes de relance pour une commande - VERSION CORRIGÉE
function fsb_get_relance_facture_notes($order_id) {
    global $wpdb;

    // Requête pour récupérer UNIQUEMENT les notes marquées comme relance via la meta 'is_relance_facture'
    // OU les notes qui commencent explicitement par "RELANCE:"
    $notes = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT comments.*, users.display_name
        FROM {$wpdb->comments} AS comments
        LEFT JOIN {$wpdb->users} AS users ON comments.user_id = users.ID
        LEFT JOIN {$wpdb->commentmeta} AS meta ON comments.comment_ID = meta.comment_id AND meta.meta_key = 'is_relance_facture'
        WHERE comments.comment_post_ID = %d
        AND comments.comment_type = 'order_note'
        AND (
            (meta.meta_value = '1')
            OR comments.comment_content LIKE %s
        )
        ORDER BY comments.comment_date_gmt DESC",
        $order_id,
        'RELANCE:%'  // Changement ici : on cherche les notes qui COMMENCENT par "RELANCE:"
    ));
    
    return $notes;
}


// Ajouter la metabox pour les relances de facture
function fsb_ajouter_metabox_relances_facture($notes = null) {
    global $post;
    if (!$post || get_post_type($post->ID) !== 'shop_order') {
        return;
    }
    
    $order_id = $post->ID;
    
    // Si les notes ne sont pas fournies, les récupérer
    if ($notes === null) {
        $notes = fsb_get_relance_facture_notes($order_id);
    }

    // Définir le titre de la metabox
    $metabox_title_base = 'Relances facturation';
    $metabox_title = $metabox_title_base;
    $last_relance_date_str = '';

    // S'il y a des notes de relance, construire le titre dynamique avec la date
    if (!empty($notes)) {
        $latest_note = $notes[0]; // La plus récente
        $latest_timestamp = strtotime($latest_note->comment_date_gmt);
        if ($latest_timestamp) {
            // Formater la date pour l'affichage et la métadonnée
            $formatted_last_date = date_i18n('d/m/Y', $latest_timestamp);
            $short_date_format = date_i18n('d/m/Y', $latest_timestamp);
            
            $last_relance_date_str = '(Dernière: ' . esc_html($formatted_last_date) . ')';
            
            // Enregistrer dans le champ meta
            update_post_meta($order_id, 'fsbbd_suivifactu', $short_date_format);
        }
        
        // Concaténer le titre avec le span contenant la date
        $metabox_title = esc_html($metabox_title_base) . ' <span class="last-relance-date">' . $last_relance_date_str . '</span>';
    } else {
        $metabox_title = esc_html($metabox_title_base) . ' <span class="last-relance-date"></span>';
    }

    // Ajouter la metabox
    add_meta_box(
        'relances_facture_box',
        $metabox_title,
        'fsb_afficher_relances_facture_metabox',
        'shop_order',
        'normal',
        'high'
    );
}


// Fonction qui décide si la metabox doit être ajoutée
function fsb_maybe_ajouter_metabox_relances_facture() {
    global $post;
    if (!$post || get_post_type($post->ID) !== 'shop_order') {
        return;
    }
    
    $order_id = $post->ID;
    $notes = fsb_get_relance_facture_notes($order_id);
    
    // Vérifier si des notes de relance existent
    if (!empty($notes)) {
        // Des notes de relance existent, on ajoute la metabox
        fsb_ajouter_metabox_relances_facture($notes);
    }
}
add_action('add_meta_boxes_shop_order', 'fsb_maybe_ajouter_metabox_relances_facture', 30);


// Afficher le contenu de la metabox
function fsb_afficher_relances_facture_metabox($post) {
    $order_id = $post->ID;
    $notes = fsb_get_relance_facture_notes($order_id);

    // Conteneur avec classe pour le style et scroll
    echo '<div class="relances-historique">';

    if (empty($notes)) {
        // Message si aucune note n'est trouvée
        echo '<p style="padding: 12px; text-align: center; color: #777;">Aucune relance de facture enregistrée.</p>';
    } else {
        // Boucle sur les notes trouvées
        foreach ($notes as $note) {
            // Formater la date
            $date_formatee = date_i18n('d/m/Y @ H:i', strtotime($note->comment_date));

            // Obtenir l'auteur
            $auteur = !empty($note->display_name) ? $note->display_name : ($note->comment_author ? $note->comment_author : 'Système');

            // Nettoyer le contenu de la note
            $contenu = wp_strip_all_tags($note->comment_content);

            // Afficher l'item de relance
            echo '<div class="relance-item">';
            echo '<div class="relance-meta">' . esc_html($date_formatee . ' - ' . $auteur) . '</div>';
            echo '<div class="relance-content">' . nl2br(esc_html($contenu)) . '</div>';
            echo '</div>';
        }
    }

    echo '</div>';
}


// ----------------------------------------------------------------------
// 3. Remplacer les libellés des types de notes
// ----------------------------------------------------------------------

// Fonction pour remplacer les textes des types de notes
function fsb_modifier_libelles_notes_woocommerce($translated_text, $text, $domain) {
    // Ne s'applique qu'aux textes de WooCommerce
    if ($domain !== 'woocommerce') {
        return $translated_text;
    }
    
    // Vérifier si nous sommes sur une page d'édition de commande
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'shop_order' || $screen->base !== 'post') {
        return $translated_text;
    }

    // Remplacer les textes spécifiques
    switch ($text) {
        case 'Private note':
        case 'Note privée':
            return 'Note interne';
            
        case 'Note to customer':
        case 'Note au client':
            return 'Mail au client';
            
        // Optionnel : vous pouvez ajouter d'autres remplacements ici
    }
    
    return $translated_text;
}
add_filter('gettext', 'fsb_modifier_libelles_notes_woocommerce', 10, 3);

// Pour être certain de capturer toutes les variations possibles
function fsb_modifier_libelles_notes_woocommerce_avec_contexte($translated_text, $text, $context, $domain) {
    // Ne s'applique qu'aux textes de WooCommerce
    if ($domain !== 'woocommerce') {
        return $translated_text;
    }
    
    // Vérifier si nous sommes sur une page d'édition de commande
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'shop_order' || $screen->base !== 'post') {
        return $translated_text;
    }

    // Remplacer les textes spécifiques avec contexte
    if ($context === 'Order note type') {
        switch ($text) {
            case 'Private note':
            case 'Note privée':
                return 'Note interne';
                
            case 'Note to customer':
            case 'Note au client':
                return 'Mail au client';
        }
    }
    
    return $translated_text;
}
add_filter('gettext_with_context', 'fsb_modifier_libelles_notes_woocommerce_avec_contexte', 10, 4);

// Modification directe pour le sélecteur via JavaScript
function fsb_ajouter_js_modification_libelles() {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'shop_order' || $screen->base !== 'post') {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Remplacer les libellés des types de notes
        function remplacerLibellesNotes() {
            $('select#order_note_type option, select[name="note_type"] option').each(function() {
                var $option = $(this);
                if ($option.text() === 'Note privée' || $option.text() === 'Private note') {
                    $option.text('Note interne');
                }
                else if ($option.text() === 'Note au client' || $option.text() === 'Note to customer') {
                    $option.text('Mail au client');
                }
            });
        }
        
        // IMPORTANT: Ajouter un gestionnaire spécifique pour le bouton Ajouter de la metabox
        $(document).off('click', '.ajouter-relance-link').on('click', '.ajouter-relance-link', function(e) {
            e.preventDefault();
            console.log('Bouton Ajouter metabox cliqué');
            
            var $commentsDiv = $("#commentsdiv");
            if(!$commentsDiv.length) return;

            // Défilement vers les notes
            $('html, body').animate({
                scrollTop: $("#add_order_note").offset().top - 50
            }, 500);
            
            // Ouvrir la section si fermée
            if ($commentsDiv.hasClass('closed')) {
                $commentsDiv.find('.handlediv').trigger('click');
            }
            
            // Attendre que tout soit chargé avec un délai plus long
            setTimeout(function() {
                // Cocher la case de relance
                var $checkbox = $('#fsb_relance_facture_checkbox');
                if ($checkbox.length) {
                    console.log('Case cochée');
                    $checkbox.prop('checked', true).trigger('change');
                }
                
                var $textarea = $('#add_order_note');
                if ($textarea.length) { 
                    $textarea.focus();
                    if ($textarea.val() === '') {
                        $textarea.val('RELANCE: Relance de facture effectuée');
                    }
                }
            }, 1000);
        });
        
        // Observer spécifiquement l'ajout de notes pour décocher la case
        var $notesContainer = $('#woocommerce-order-notes .inside .order_notes');
        if ($notesContainer.length) {
            var notesObserver = new MutationObserver(function(mutations) {
                // Vérifier si un élément LI.note a été ajouté
                let isNoteLiAdded = mutations.some(m =>
                    Array.from(m.addedNodes).some(node => 
                        node.nodeType === 1 && 
                        node.tagName === 'LI' && 
                        node.classList.contains('note')
                    )
                );

                if (isNoteLiAdded) {
                    console.log('Note ajoutée - Décochage de la case');
                    // Décocher la case APRÈS ajout effectif de la note
                    $('#fsb_relance_facture_checkbox').prop('checked', false);
                    $('#add_order_note').val('');
                }
            });
            
            notesObserver.observe($notesContainer[0], { 
                childList: true,
                subtree: true
            });
        }
        
        // Exécuter immédiatement et observer les changements
        remplacerLibellesNotes();
        var notesFormObserver = new MutationObserver(function() {
            setTimeout(remplacerLibellesNotes, 100);
        });
        var $notesForm = $('#commentsdiv .inside');
        if ($notesForm.length) {
            notesFormObserver.observe($notesForm[0], { childList: true, subtree: true });
        }
    });
    </script>
    <?php
}
add_action('admin_footer', 'fsb_ajouter_js_modification_libelles');


function fsb_debug_relance_facture() {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'shop_order' || $screen->base !== 'post') {
        return;
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Vérifier si les éléments existent et sont cliquables
        console.log("Bouton relance: ", $('.ajouter-relance-link').length);
        
        // Fonction pour tracer les clics
        $(document).on('click', '.ajouter-relance-link, .ajouter-relance-btn', function(e) {
            console.log("Bouton relance cliqué");
        });
        
        // Surveiller l'ajout de la checkbox
        var checkboxObserver = new MutationObserver(function() {
            if ($('#fsb_relance_facture_checkbox').length) {
                console.log("Checkbox trouvée!");
            }
        });
        var $body = $('body');
        checkboxObserver.observe($body[0], { childList: true, subtree: true });
    });
    </script>
    <?php
}
add_action('admin_footer', 'fsb_debug_relance_facture');