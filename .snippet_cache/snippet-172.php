<?php
/**
 * Snippet ID: 172
 * Name: REPORT alertes global accueil admin v3 MODAL LIENs
 * Description: 
 * @active true
 */

/**
 * Ajoute les styles et scripts pour le modal
 * À ajouter dans functions.php ou dans votre code snippet
 */
function fsbdd_add_modal_scripts() {
    // Vérifier que nous sommes sur la page d'accueil de l'admin
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'dashboard') {
        return;
    }
    
    // Vérifier les rôles autorisés (comme dans votre fonction originale)
    $current_user = wp_get_current_user();
    if (!$current_user || $current_user->ID === 0) return;
    
    $allowed_roles = array('administrator', 'referent', 'compta');
    $can_access = false;
    
    foreach ($allowed_roles as $role) {
        if (in_array($role, (array) $current_user->roles)) {
            $can_access = true;
            break;
        }
    }
    
    if (!$can_access) {
        return;
    }
    
    // Ajouter les styles CSS pour le modal
    echo '<style>
        .fsbdd-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 99999;
            display: none;
            justify-content: center;
            align-items: center;
        }
        
        .fsbdd-modal-container {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 95%;
            max-width: 1800px;
            height: 90%;
            max-height: 800px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .fsbdd-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            background-color: #f0f0f0;
            border-bottom: 1px solid #ddd;
        }
        
        .fsbdd-modal-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        .fsbdd-modal-close {
            font-size: 24px;
            font-weight: bold;
            color: #666;
            cursor: pointer;
        }
        
        .fsbdd-modal-close:hover {
            color: #333;
        }
        
        .fsbdd-modal-content {
            flex: 1;
            overflow: hidden;
        }
        
        .fsbdd-modal-iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        body.fsbdd-modal-open {
            overflow: hidden;
        }
    </style>';
    
    // Ajouter la structure HTML du modal
    echo '<div id="fsbdd-modal-overlay" class="fsbdd-modal-overlay">
        <div class="fsbdd-modal-container">
            <div class="fsbdd-modal-header">
                <span id="fsbdd-modal-title" class="fsbdd-modal-title">Détails</span>
                <span id="fsbdd-modal-close" class="fsbdd-modal-close">&times;</span>
            </div>
            <div class="fsbdd-modal-content">
                <iframe id="fsbdd-modal-iframe" class="fsbdd-modal-iframe" src="about:blank"></iframe>
            </div>
        </div>
    </div>';
    
    // Ajouter le JavaScript pour gérer le modal
    echo '<script type="text/javascript">
        jQuery(document).ready(function($) {
            // Gestion de la fermeture du modal
            $("#fsbdd-modal-close").on("click", function() {
                closeModal();
            });
            
            // Fermeture avec la touche Escape
            $(document).on("keydown", function(e) {
                if (e.key === "Escape" && $("#fsbdd-modal-overlay").is(":visible")) {
                    closeModal();
                }
            });
            
            // Fermeture en cliquant hors du modal
            $("#fsbdd-modal-overlay").on("click", function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });
            
            // Intercepter les clics sur les liens du tableau
            $(document).on("click", ".fsbdd-orders-table a", function(e) {
                e.preventDefault();
                
                var url = $(this).attr("href");
                var title = $(this).text();
                var columnIndex = $(this).closest("td").index();
                
                // Titre personnalisé selon le type de lien
                var suffix = "";
                if (columnIndex === 0) {
                    suffix = " - Commande";
                } else if (columnIndex === 2) {
                    suffix = " - Client";
                } else if (columnIndex === 3) {
                    suffix = " - Action de formation";
                }
                
                openModal(url, title + suffix);
            });
            
            // Fonction pour ouvrir le modal
            function openModal(url, title) {
                $("#fsbdd-modal-title").text(title);
                $("#fsbdd-modal-iframe").attr("src", url);
                $("#fsbdd-modal-overlay").css("display", "flex").hide().fadeIn(200);
                $("body").addClass("fsbdd-modal-open");
            }
            
            // Fonction pour fermer le modal
            function closeModal() {
                $("#fsbdd-modal-overlay").fadeOut(200);
                setTimeout(function() {
                    $("#fsbdd-modal-iframe").attr("src", "about:blank");
                }, 200);
                $("body").removeClass("fsbdd-modal-open");
            }
        });
    </script>';
}

// Ajouter notre fonction après l'affichage du tableau principal
add_action('admin_notices', 'fsbdd_add_modal_scripts', 30); // Priorité 30 pour s'exécuter après le tableau (20)
