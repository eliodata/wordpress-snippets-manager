<?php
/**
 * Snippet ID: 169
 * Name: JS Tableau de bord des alertes page admin 
 * Description: 
 * @active false
 */


function fs_get_dashboard_js() {
    ob_start();
?>
/**
 * JavaScript pour le Tableau de Bord des Formations
 */
jQuery(document).ready(function($) {
    // Gestion des onglets
    $('.fs-tab-button').on('click', function() {
        // Supprimer la classe active de tous les boutons et contenus
        $('.fs-tab-button').removeClass('active');
        $('.fs-tab-content').removeClass('active');
        
        // Ajouter la classe active au bouton cliqué
        $(this).addClass('active');
        
        // Afficher le contenu correspondant
        const tabId = $(this).data('tab');
        $('#' + tabId).addClass('active');
        
        // Sauvegarder la préférence de l'onglet dans localStorage
        localStorage.setItem('fs_dashboard_active_tab', tabId);
    });
    
    // Restaurer l'onglet précédemment sélectionné
    const savedTab = localStorage.getItem('fs_dashboard_active_tab');
    if (savedTab) {
        $('.fs-tab-button[data-tab="' + savedTab + '"]').trigger('click');
    }
    
    // Boutons "Voir tous"
    $('.fs-show-all').on('click', function() {
        const alertId = $(this).data('alert');
        const $cardItems = $(this).closest('.fs-alert-items');
        
        // Ouvrir une modal ou un dialog avec la liste complète
        openFullListModal(alertId, $cardItems);
    });
    
    // Fonction pour ouvrir une modal avec la liste complète
    function openFullListModal(alertId, $cardItems) {
        // Cloner les éléments pour la modal
        const $items = $cardItems.find('ul').clone();
        const title = $cardItems.closest('.fs-alert-card').find('h3').text();
        
        // Créer la modal
        const $modal = $('<div class="fs-modal"></div>');
        $modal.html(`
            <div class="fs-modal-content">
                <div class="fs-modal-header">
                    <h3>${title}</h3>
                    <button class="fs-modal-close">&times;</button>
                </div>
                <div class="fs-modal-body"></div>
            </div>
        `);
        
        // Ajouter les éléments à la modal
        $modal.find('.fs-modal-body').append($items);
        
        // Ajouter la modal au document
        $('body').append($modal);
        
        // Afficher la modal
        setTimeout(() => {
            $modal.addClass('fs-modal-open');
        }, 10);
        
        // Fermer la modal
        $modal.find('.fs-modal-close').on('click', function() {
            closeModal($modal);
        });
        
        // Fermer la modal si on clique en dehors
        $modal.on('click', function(e) {
            if ($(e.target).hasClass('fs-modal')) {
                closeModal($modal);
            }
        });
        
        // Fermer la modal avec Escape
        $(document).on('keydown.fsmodal', function(e) {
            if (e.key === 'Escape') {
                closeModal($modal);
            }
        });
    }
    
    // Fonction pour fermer la modal
    function closeModal($modal) {
        $modal.removeClass('fs-modal-open');
        setTimeout(() => {
            $modal.remove();
            $(document).off('keydown.fsmodal');
        }, 300);
    }
    
    // Ajouter le style de la modal dynamiquement
    const modalStyle = `
        .fs-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 100000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        
        .fs-modal-open {
            opacity: 1;
            visibility: visible;
        }
        
        .fs-modal-content {
            background-color: white;
            border-radius: 6px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }
        
        .fs-modal-open .fs-modal-content {
            transform: translateY(0);
        }
        
        .fs-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #dcdcde;
        }
        
        .fs-modal-header h3 {
            margin: 0;
            font-size: 18px;
            color: #1d2327;
        }
        
        .fs-modal-close {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 24px;
            line-height: 1;
            color: #646970;
            padding: 0;
        }
        
        .fs-modal-close:hover {
            color: #d63638;
        }
        
        .fs-modal-body {
            padding: 20px;
            overflow-y: auto;
            max-height: calc(80vh - 70px);
        }
        
        .fs-modal-body ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .fs-modal-body li {
            margin-bottom: 10px;
        }
        
        .fs-modal-body a {
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: #2271b1;
            font-size: 14px;
            padding: 8px 12px;
            border-radius: 4px;
            background-color: #f6f7f7;
            transition: background-color 0.2s ease;
        }
        
        .fs-modal-body a:hover {
            background-color: #f0f0f1;
            color: #135e96;
        }
    `;
    
    $('<style>').text(modalStyle).appendTo('head');
    
    // Rafraîchissement automatique du tableau de bord
    function setupAutoRefresh() {
        // Rafraîchir toutes les 5 minutes
        const refreshInterval = 5 * 60 * 1000;
        
        setInterval(function() {
            // Sauvegarder l'onglet actif
            const activeTab = localStorage.getItem('fs_dashboard_active_tab');
            
            // Recharger la page
            location.reload();
            
            // L'onglet actif sera restauré après le rechargement grâce au localStorage
        }, refreshInterval);
    }
    
    // Initialiser le rafraîchissement automatique
    setupAutoRefresh();
    
    // Gestion des filtres avancés (à implémenter selon les besoins)
    function setupAdvancedFilters() {
        // Code pour les filtres avancés
    }
    
    // Initialiser les filtres avancés
    setupAdvancedFilters();
});
		

<?php
    return ob_get_clean();
}
