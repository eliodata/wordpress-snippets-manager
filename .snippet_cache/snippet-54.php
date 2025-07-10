<?php
/**
 * Snippet ID: 54
 * Name: BOUTON MISE A JOUR STICKY & MASQUER BOX BOUTON PUBLIER WORDPRESS
 * Description: 
 * @active true
 */

/**
 * Footer personnalisé pour l'administration WordPress
 * 
 * - Replie automatiquement le menu pour certains CPT
 * - Ajoute une barre d'outils fixe en bas de l'écran
 * - Fournit des raccourcis vers les sections principales
 * - Inclut un bouton de sauvegarde rapide
 * - Option pour masquer/afficher la barre
 */

class FS_Admin_Toolbar {
    // CPT supportés pour la barre d'outils et le menu replié
    private $supported_cpts = [
        'action-de-formation',
        'formateur',
        'formateur-passe',
        'salle-de-formation',
        'prospect',
        'client',
        'shop_order'
    ];

    // Pages spéciales où activer la barre d'outils
    private $special_pages = ['gestion-plannings', 'my-custom-order-wizard'];

    public function __construct() {
        // Initialiser les hooks
        add_filter('admin_body_class', [$this, 'add_folded_class']);
        add_action('admin_footer', [$this, 'render_footer']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Ajoute la classe 'folded' au body pour les CPT spécifiés
     */
    public function add_folded_class($classes) {
        global $post_type;
        $current_page = isset($_GET['page']) ? $_GET['page'] : '';
        
        if ((isset($post_type) && in_array($post_type, $this->supported_cpts)) || 
            in_array($current_page, $this->special_pages)) {
            $classes .= ' folded';
        }
        
        return $classes;
    }
    
    /**
     * Charge les styles et scripts nécessaires
     */
    public function enqueue_scripts() {
        if (!is_admin()) return;
        
        // Enregistrer et charger le CSS
        wp_register_style('fs-admin-toolbar', false);
        wp_enqueue_style('fs-admin-toolbar');
        
        // Ajouter le CSS inline
        wp_add_inline_style('fs-admin-toolbar', $this->get_toolbar_styles());
    }
    
    /**
     * Retourne les styles CSS pour la barre d'outils
     */
    private function get_toolbar_styles() {
        global $post_type;
        
        ob_start();
        ?>
        .fs-admin-toolbar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 40px;
            background-color: #2271b1;
            border-top: 1px solid rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            z-index: 9998;
            box-shadow: 0 -2px 8px rgba(0,0,0,0.15);
            transition: transform 0.3s ease;
        }
        
        .fs-admin-toolbar.fs-toolbar-hidden {
            transform: translateY(100%);
        }
        
        .fs-toolbar-toggle {
            position: fixed;
            bottom: 40px;
            right: 20px;
            width: 36px;
            height: 36px;
            background-color: #2271b1;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 9999;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            border: none;
        }
        
        .fs-toolbar-toggle:hover {
            background-color: #135e96;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        
        .fs-toolbar-toggle:focus {
            outline: 2px solid #72aee6;
        }
        
        .fs-toolbar-container {
            display: flex;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            position: relative;
        }
        
        .fs-toggle-menu-button {
            position: absolute;
            left: 0;
            background-color: transparent;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .fs-toggle-menu-button:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        .fs-sticky-links {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            overflow-x: auto;
            white-space: nowrap;
            -ms-overflow-style: none;
            scrollbar-width: none;
            padding: 0 10px;
        }
        
        .fs-sticky-links::-webkit-scrollbar {
            display: none;
        }
        
        .fs-sticky-links a {
            margin: 0 6px;
            text-decoration: none;
            color: #fff;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 4px;
            padding: 4px 8px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .fs-sticky-links a .dashicons {
            margin-right: 4px;
        }
        
        .fs-sticky-links a:hover {
            background-color: rgba(255,255,255,0.1);
            border-color: #fff;
        }
        
        .fs-sticky-links a.fs-active {
            background-color: rgba(255,255,255,0.2);
            border-color: #fff;
        }
        
        .fs-sticky-save-button {
            position: absolute;
            right: 0;
            background-color: #fff;
            color: #2271b1;
            padding: 6px 12px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            transition: all 0.2s ease;
            border: none;
            margin-right: 5px;
        }
        
        .fs-sticky-save-button:hover {
            background-color: #f0f0f1;
            color: #135e96;
        }
        
        .fs-sticky-save-button:focus {
            outline: 2px solid #72aee6;
            outline-offset: 1px;
        }
        
        .fs-loading-message {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            display: none;
            z-index: 10000;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        @media screen and (max-width: 782px) {
            .fs-admin-toolbar {
                height: 48px;
                padding: 0 12px;
            }
            
            .fs-toolbar-toggle {
                bottom: 48px;
            }
            
            .fs-sticky-links {
                margin: 0 40px;
            }
            
            .fs-sticky-links a {
                font-size: 12px;
                padding: 4px 6px;
                margin: 0 4px;
            }
            
            .fs-sticky-save-button {
                padding: 5px 10px;
                font-size: 12px;
            }
        }
        
        /* Pour les CPT spécifiques, masquer le submitdiv mais le garder visible en mobile */
        <?php if (isset($post_type) && in_array($post_type, array_diff($this->supported_cpts, ['shop_order']))) : ?>
        #submitdiv {
            display: none !important;
        }
        @media screen and (max-width: 782px) {
            #submitdiv {
                display: block !important;
            }
        }
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Affiche le footer personnalisé avec la barre d'outils
     */
    public function render_footer() {
        if (!is_admin()) return;
        
        global $post_type, $pagenow;
        $current_page = isset($_GET['page']) ? $_GET['page'] : '';
        $current_url = admin_url($pagenow . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
        
        // Détection de l'écran de commande WooCommerce
        $screen = get_current_screen();
        $is_woo_order_edit = false;
        if ($post_type === 'shop_order' && $pagenow === 'post.php' && isset($_GET['action']) && $_GET['action'] === 'edit') {
            $is_woo_order_edit = true;
        }
        
        ?>
        <button id="fs-toolbar-toggle" class="fs-toolbar-toggle" title="Afficher/masquer la barre d'outils">
            <span class="dashicons dashicons-arrow-up-alt2"></span>
        </button>
        
        <div id="fs-admin-toolbar" class="fs-admin-toolbar">
            <div class="fs-toolbar-container">
                <button id="fs-toggle-menu-button" class="fs-toggle-menu-button" title="Replier/déplier le menu latéral">
                    <span class="dashicons dashicons-menu-alt"></span>
                </button>
                
                <div id="fs-sticky-links" class="fs-sticky-links">
                    <?php $this->render_nav_links($current_url); ?>
                </div>
                
                <?php 
                // Ne pas afficher le bouton "Enregistrer" rapide uniquement sur certaines pages
                $hide_save_button = ($current_page === 'gestion-plannings' || 
                                    $current_page === 'my-custom-order-wizard');
                                    
                if (!$hide_save_button) : ?>
                    <button id="fs-sticky-save-button" class="fs-sticky-save-button" title="Enregistrer les modifications" 
                           <?php echo $is_woo_order_edit ? 'data-woo-order="true"' : ''; ?>>
                        <span class="dashicons dashicons-saved"></span> Enregistrer
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="fs-loading-message" class="fs-loading-message">
            <span class="spinner is-active" style="float:left; margin-right:10px;"></span>
            Enregistrement en cours...
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialiser les variables
            const toolbar = document.getElementById('fs-admin-toolbar');
            const toggleButton = document.getElementById('fs-toolbar-toggle');
            const toggleMenuButton = document.getElementById('fs-toggle-menu-button');
            const saveButton = document.getElementById('fs-sticky-save-button');
            const loadingMessage = document.getElementById('fs-loading-message');
            
            // Gérer l'état de visibilité de la barre d'outils (utilisation du localStorage)
            if(toggleButton && toolbar) {
                // Vérifier si l'état était caché lors de la dernière visite
                if(localStorage.getItem('fs_toolbar_hidden') === 'true') {
                    toolbar.classList.add('fs-toolbar-hidden');
                    toggleButton.querySelector('.dashicons').classList.remove('dashicons-arrow-up-alt2');
                    toggleButton.querySelector('.dashicons').classList.add('dashicons-arrow-down-alt2');
                }
                
                // Gérer le clic sur le bouton de toggle
                toggleButton.addEventListener('click', function() {
                    toolbar.classList.toggle('fs-toolbar-hidden');
                    const isHidden = toolbar.classList.contains('fs-toolbar-hidden');
                    
                    // Mettre à jour l'icône
                    if(isHidden) {
                        toggleButton.querySelector('.dashicons').classList.remove('dashicons-arrow-up-alt2');
                        toggleButton.querySelector('.dashicons').classList.add('dashicons-arrow-down-alt2');
                    } else {
                        toggleButton.querySelector('.dashicons').classList.remove('dashicons-arrow-down-alt2');
                        toggleButton.querySelector('.dashicons').classList.add('dashicons-arrow-up-alt2');
                    }
                    
                    // Sauvegarder l'état
                    localStorage.setItem('fs_toolbar_hidden', isHidden);
                });
            }
            
            // Gestion du bouton de menu
            if(toggleMenuButton) {
                toggleMenuButton.addEventListener('click', function() {
                    const collapseButton = document.getElementById('collapse-button');
                    if(collapseButton) {
                        collapseButton.click();
                    } else {
                        document.body.classList.toggle('folded');
                    }
                });
            }
            
            // Gestion du bouton de sauvegarde rapide
            if(saveButton) {
                saveButton.addEventListener('click', function() {
                    if(loadingMessage) loadingMessage.style.display = 'block';
                    
                    // Vérifier si c'est une commande WooCommerce
                    const isWooOrder = saveButton.getAttribute('data-woo-order') === 'true';
                    
                    // Utiliser requestAnimationFrame pour laisser le temps à l'UI de se mettre à jour
                    requestAnimationFrame(function() {
                        if (isWooOrder) {
                            // Gestion spécifique pour les commandes WooCommerce
                            handleWooSave();
                        } else {
                            // Gestion standard
                            const publishButton = document.getElementById('publish');
                            if(publishButton) {
                                publishButton.click();
                            } else {
                                if(loadingMessage) loadingMessage.style.display = 'none';
                                alert('Le bouton de sauvegarde est introuvable.');
                            }
                        }
                    });
                });
            }
            
            // Fonction pour gérer l'enregistrement des commandes WooCommerce
            function handleWooSave() {
                // Rechercher les boutons d'enregistrement spécifiques à WooCommerce
                let wooSaveButton = null;
                
                // Plusieurs possibilités selon la version de WooCommerce
                const possibleButtons = [
                    document.querySelector('button.save_order'),
                    document.querySelector('button.button-primary.save_order'),
                    document.querySelector('.button-primary.save_order'),
                    document.querySelector('#order_status'),
                    document.querySelector('.save_order:not(.button-disabled)')
                ];
                
                for (const btn of possibleButtons) {
                    if (btn) {
                        wooSaveButton = btn;
                        break;
                    }
                }
                
                if (wooSaveButton) {
                    wooSaveButton.click();
                } else {
                    // Fallback : essayer de soumettre le formulaire directement
                    const wooForm = document.getElementById('woocommerce-order-data') || 
                                   document.getElementById('post') ||
                                   document.querySelector('form[name="post"]');
                    
                    if (wooForm) {
                        wooForm.submit();
                    } else {
                        if(loadingMessage) loadingMessage.style.display = 'none';
                        alert('Impossible de trouver le bouton de sauvegarde WooCommerce.');
                    }
                }
            }
            
            // Gérer l'affichage du chargement lors des soumissions de formulaire
            const forms = document.querySelectorAll('form#post');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    if(loadingMessage) loadingMessage.style.display = 'block';
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Génère les liens de navigation pour la barre d'outils
     */
    private function render_nav_links($current_url) {
        // Configuration des liens avec leurs icônes et titres
        $links = [
            [
                'url' => admin_url('edit.php?post_type=client'),
                'icon' => 'dashicons-businessperson',
                'title' => 'Clients',
                'active' => strpos($current_url, 'post_type=client') !== false
            ],
            [
                'url' => admin_url('edit.php?post_type=prospect'),
                'icon' => 'dashicons-groups',
                'title' => 'Prospects',
                'active' => strpos($current_url, 'post_type=prospect') !== false
            ],
            [
                'url' => admin_url('edit.php?post_type=formateur'),
                'icon' => 'dashicons-welcome-learn-more',
                'title' => 'Formateurs',
                'active' => strpos($current_url, 'post_type=formateur') !== false
            ],
            [
                'url' => admin_url('edit.php?post_type=salle-de-formation'),
                'icon' => 'dashicons-admin-home',
                'title' => 'Fournisseurs',
                'active' => strpos($current_url, 'post_type=salle-de-formation') !== false
            ],
            [
                'url' => admin_url('edit.php?post_type=action-de-formation'),
                'icon' => 'dashicons-welcome-write-blog',
                'title' => 'Actions',
                'active' => strpos($current_url, 'post_type=action-de-formation') !== false
            ],
            [
                'url' => admin_url('edit.php?post_type=shop_order'),
                'icon' => 'dashicons-cart',
                'title' => 'Affaires',
                'active' => strpos($current_url, 'post_type=shop_order') !== false && strpos($current_url, 'page=my-custom-order-wizard') === false
            ],
            [
                'url' => admin_url('admin.php?page=gestion-plannings'),
                'icon' => 'dashicons-calendar',
                'title' => 'Plannings',
                'active' => strpos($current_url, 'page=gestion-plannings') !== false
            ]
        ];
        
        // Afficher les liens
        foreach($links as $link) {
            $active_class = $link['active'] ? 'fs-active' : '';
            echo '<a href="' . esc_url($link['url']) . '" class="' . esc_attr($active_class) . '">';
            echo '<span class="dashicons ' . esc_attr($link['icon']) . '"></span> ' . esc_html($link['title']);
            echo '</a>';
        }
    }
}

// Initialiser la classe
new FS_Admin_Toolbar();