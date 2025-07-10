<?php
/**
 * Snippet ID: 102
 * Name: empecher selection texte pendant drag scroll barre defilement admin column 
 * Description: 
 * @active true
 */


/**
 * Custom Scrollbar Enhancement for Admin Columns Pro
 * 
 * Ce snippet ajoute du CSS et du JavaScript personnalisés pour améliorer la barre de défilement horizontale
 * dans Admin Columns Pro, en empêchant la sélection de texte pendant le drag et en gérant le défilement.
 */

// Fonction pour enqueuer les styles et scripts personnalisés
function acp_enqueue_custom_scroll_scripts() {
    // Vérifiez si vous êtes dans l'admin, sinon ajustez selon vos besoins
    if ( is_admin() ) {
        // Ajouter le CSS personnalisé
        wp_register_style( 'acp-custom-scrollbar', false );
        wp_enqueue_style( 'acp-custom-scrollbar' );
        wp_add_inline_style( 'acp-custom-scrollbar', '
            .no-select {
                -webkit-user-select: none;
                -moz-user-select: none;
                user-select: none;
            }
            
            .acp-scrolling-indicator__dragger {
                background: #494949 !important;
                border: none !important;
                margin: 3px;
                border-radius: 3px;
                cursor: pointer;
            }
            
            .acp-scrolling-indicator {
                background: #fff !important;
                height: 30px !important;
                bottom: 39px !important;
                position: relative;
            }
            
            .acp-scrolling-indicator__dragger:before,
            .acp-scrolling-indicator__dragger:after {
                color: #98fff1 !important;
            }
        ' );

        // Ajouter le JavaScript personnalisé
        wp_register_script( 'acp-custom-scrollbar-js', '', [], false, true );
        wp_enqueue_script( 'acp-custom-scrollbar-js' );
        wp_add_inline_script( 'acp-custom-scrollbar-js', '
            document.addEventListener("DOMContentLoaded", function() {
                const dragger = document.querySelector(".acp-scrolling-indicator__dragger");
                const container = document.querySelector(".acp-scrolling-indicator");
                
                if (!dragger || !container) return; // Assurez-vous que les éléments existent
                
                let isDragging = false;
                let startX = 0;
                let scrollLeft = 0;

                // Ajouter un gestionnaire pour le début du drag
                dragger.addEventListener("mousedown", function(e) {
                    isDragging = true;
                    startX = e.pageX;
                    scrollLeft = container.scrollLeft;
                    document.body.classList.add("no-select");
                });

                // Ajouter un gestionnaire pour le mouvement de la souris
                document.addEventListener("mousemove", function(e) {
                    if (!isDragging) return;
                    e.preventDefault(); // Empêche la sélection de texte
                    const x = e.pageX - startX;
                    container.scrollLeft = scrollLeft - x;
                });

                // Ajouter un gestionnaire pour la fin du drag
                document.addEventListener("mouseup", function() {
                    isDragging = false;
                    document.body.classList.remove("no-select");
                });

                // Optionnel : Empêcher le drag lorsque la souris quitte la fenêtre
                document.addEventListener("mouseleave", function() {
                    if (isDragging) {
                        isDragging = false;
                        document.body.classList.remove("no-select");
                    }
                });
            });
        ' );
    }
}
add_action( 'admin_enqueue_scripts', 'acp_enqueue_custom_scroll_scripts' );
