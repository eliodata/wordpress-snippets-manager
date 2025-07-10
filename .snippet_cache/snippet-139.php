<?php
/**
 * Snippet ID: 139
 * Name: HOOK pour ouvrir automatiquement les cpt action de formation modifié et les enregistrer depuis le planning global modal
 * Description: 
 * @active true
 */

add_action('admin_head-post.php', 'auto_update_and_close_window');

function auto_update_and_close_window() {
    // Vérifier la présence du paramètre
    if (!isset($_GET['auto_update']) || $_GET['auto_update'] !== '1') {
        return; // on ne fait rien
    }

    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Pour éviter le popup "Voulez-vous vraiment quitter..."
        window.onbeforeunload = null; 

        // Fonction pour fermer la fenêtre
        function closeWindow() {
            try {
                console.log('Fermeture de la fenêtre');
                window.close();
            } catch(e) {
                console.log('Impossible de fermer la fenêtre automatiquement');
            }
        }

        // Fonction pour vérifier si un message de succès est présent
        function checkForSuccess() {
            var successMessage = document.querySelector('#message.updated, .notice-success, .updated, .notice-success.is-dismissible, .updated.notice-success');
            if (successMessage && successMessage.offsetParent !== null) {
                console.log('Succès détecté, fermeture du modal dans 1s');
                return true;
            }
            return false;
        }

        // Trouver le bouton #publish (mise à jour ou publier)
        var publishBtn = document.getElementById('publish');
        if (publishBtn) {
            // Cliquer sur le bouton
            publishBtn.click();
            
            // Vérifier immédiatement si un message de succès est déjà présent
            if (checkForSuccess()) {
                setTimeout(closeWindow, 1000);
            } else {
                // Observer les changements dans le DOM pour détecter la confirmation
                var observer = new MutationObserver(function(mutations) {
                    if (checkForSuccess()) {
                        observer.disconnect();
                        setTimeout(closeWindow, 1000);
                    }
                });
                
                // Observer les changements dans le body
                observer.observe(document.body, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    characterData: true
                });
                
                // Fermeture de sécurité après 8 secondes
                setTimeout(function() {
                    observer.disconnect();
                    console.log('Fermeture de sécurité après 8 secondes');
                    closeWindow();
                }, 8000);
            }
        } else {
            // Si pas de bouton trouvé, fermer après 3 secondes
            console.log('Bouton non trouvé, fermeture après 3 secondes');
            setTimeout(closeWindow, 3000);
        }
    });
    </script>
    <?php
}