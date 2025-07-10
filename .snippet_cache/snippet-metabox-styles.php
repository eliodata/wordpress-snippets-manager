<?php
/**
 * Snippet pour optimiser les styles par défaut des metabox Meta Box
 * Basé sur la documentation officielle de metabox.io
 * 
 * Ce snippet améliore l'apparence générale des metabox en:
 * - Optimisant l'espacement et la densité
 * - Améliorant la lisibilité des labels et champs
 * - Standardisant l'apparence sur tous les types de champs
 * - Ajoutant des styles responsives
 */

// Hook pour charger les styles CSS personnalisés pour Meta Box
add_action('rwmb_enqueue_scripts', 'metabox_custom_styles_enqueue');

function metabox_custom_styles_enqueue() {
    // Enqueue du CSS personnalisé pour les metabox
    wp_add_inline_style('wp-admin', metabox_get_custom_css());
}

function metabox_get_custom_css() {
    return '
    <style>
        /* ==========================================================================
           OPTIMISATION GÉNÉRALE DES METABOX META BOX
           ========================================================================== */
        
        /* Container principal de la metabox */
        .rwmb-meta-box {
            background: #fff;
            border: 1px solid #e1e1e1;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 0;
        }
        
        /* Optimisation de l\'espacement entre les champs */
        .rwmb-field:not(:last-of-type) {
            margin-bottom: 20px;
            border-bottom: 1px solid #f5f5f5;
            padding-bottom: 15px;
        }
        
        .rwmb-field:last-of-type {
            margin-bottom: 0;
        }
        
        /* ==========================================================================
           STYLES DES LABELS
           ========================================================================== */
        
        /* Container du label */
        .rwmb-label {
            margin-bottom: 8px;
            width: 100%;
        }
        
        /* Style du label principal */
        .rwmb-label label {
            font-weight: 600;
            font-size: 13px;
            color: #23282d;
            line-height: 1.4;
            margin: 0;
            display: block;
        }
        
        /* Description du label */
        .rwmb-label .description {
            font-size: 12px;
            color: #666;
            font-style: italic;
            margin: 4px 0 0 0;
            line-height: 1.3;
        }
        
        /* Indicateur de champ requis */
        .rwmb-required {
            color: #d63638;
            font-weight: bold;
            margin-left: 3px;
        }
        
        /* ==========================================================================
           STYLES DES CHAMPS INPUT
           ========================================================================== */
        
        /* Container des inputs */
        .rwmb-input {
            width: 100%;
            margin-top: 0;
        }
        
        /* Styles généraux pour tous les inputs */
        .rwmb-input input[type="text"],
        .rwmb-input input[type="email"],
        .rwmb-input input[type="url"],
        .rwmb-input input[type="password"],
        .rwmb-input input[type="number"],
        .rwmb-input input[type="tel"],
        .rwmb-input input[type="date"],
        .rwmb-input input[type="datetime-local"],
        .rwmb-input input[type="time"],
        .rwmb-input textarea,
        .rwmb-input select {
            width: 100%;
            max-width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            line-height: 1.4;
            background-color: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        
        /* États focus des inputs */
        .rwmb-input input:focus,
        .rwmb-input textarea:focus,
        .rwmb-input select:focus {
            border-color: #0073aa;
            box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.1);
            outline: none;
        }
        
        /* Textarea spécifique */
        .rwmb-input textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        /* Description des champs */
        .rwmb-input .description {
            font-size: 12px;
            color: #666;
            margin: 6px 0 0 0;
            line-height: 1.3;
        }
        
        /* ==========================================================================
           STYLES SPÉCIFIQUES PAR TYPE DE CHAMP
           ========================================================================== */
        
        /* Champs Select */
        .rwmb-select {
            background-image: url("data:image/svg+xml;charset=US-ASCII,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 4 5\'><path fill=\'%23666\' d=\'M2 0L0 2h4zm0 5L0 3h4z\'/></svg>");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 12px;
            padding-right: 32px;
            appearance: none;
        }
        
        /* Champs Checkbox et Radio */
        .rwmb-checkbox-wrapper,
        .rwmb-radio-wrapper {
            margin-bottom: 8px;
        }
        
        .rwmb-checkbox-wrapper:last-child,
        .rwmb-radio-wrapper:last-child {
            margin-bottom: 0;
        }
        
        .rwmb-checkbox-wrapper label,
        .rwmb-radio-wrapper label {
            font-weight: normal;
            margin-left: 8px;
            cursor: pointer;
        }
        
        .rwmb-checkbox-wrapper input[type="checkbox"],
        .rwmb-radio-wrapper input[type="radio"] {
            width: auto;
            margin: 0;
        }
        
        /* Champs File/Image */
        .rwmb-file-wrapper,
        .rwmb-image-wrapper {
            border: 2px dashed #ddd;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            background: #fafafa;
            transition: border-color 0.2s ease;
        }
        
        .rwmb-file-wrapper:hover,
        .rwmb-image-wrapper:hover {
            border-color: #0073aa;
        }
        
        /* ==========================================================================
           CHAMPS CLONABLES
           ========================================================================== */
        
        /* Container des champs clonables */
        .rwmb-clone {
            border: 1px solid #e1e1e1;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
            background: #f9f9f9;
            position: relative;
        }
        
        .rwmb-clone:last-of-type {
            margin-bottom: 15px;
        }
        
        /* Boutons Add/Remove pour les champs clonables */
        .rwmb-button.add-clone,
        .rwmb-button.remove-clone {
            padding: 6px 12px;
            border: 1px solid #0073aa;
            background: #0073aa;
            color: #fff;
            border-radius: 3px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px 5px 0 0;
            transition: background-color 0.2s ease;
        }
        
        .rwmb-button.add-clone:hover {
            background: #005a87;
        }
        
        .rwmb-button.remove-clone {
            background: #d63638;
            border-color: #d63638;
        }
        
        .rwmb-button.remove-clone:hover {
            background: #b32d2e;
        }
        
        /* ==========================================================================
           RESPONSIVE DESIGN
           ========================================================================== */
        
        @media (max-width: 782px) {
            .rwmb-field {
                margin-bottom: 15px;
                padding-bottom: 10px;
            }
            
            .rwmb-label label {
                font-size: 14px;
            }
            
            .rwmb-input input,
            .rwmb-input textarea,
            .rwmb-input select {
                font-size: 16px; /* Évite le zoom sur mobile */
                padding: 10px 12px;
            }
        }
        
        /* ==========================================================================
           AMÉLIORATIONS VISUELLES
           ========================================================================== */
        
        /* Animation subtile pour les transitions */
        .rwmb-field,
        .rwmb-input input,
        .rwmb-input textarea,
        .rwmb-input select {
            transition: all 0.2s ease;
        }
        
        /* Amélioration de la lisibilité */
        .rwmb-meta-box {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        
        /* États d\'erreur */
        .rwmb-input input.error,
        .rwmb-input textarea.error,
        .rwmb-input select.error {
            border-color: #d63638;
            box-shadow: 0 0 0 2px rgba(214, 54, 56, 0.1);
        }
        
        /* États de succès */
        .rwmb-input input.success,
        .rwmb-input textarea.success,
        .rwmb-input select.success {
            border-color: #00a32a;
            box-shadow: 0 0 0 2px rgba(0, 163, 42, 0.1);
        }
        
        /* ==========================================================================
           OPTIMISATIONS SPÉCIFIQUES POUR L\'ADMIN WORDPRESS
           ========================================================================== */
        
        /* Intégration harmonieuse avec l\'interface WordPress */
        .postbox .rwmb-meta-box {
            border: none;
            box-shadow: none;
            margin: 0;
        }
        
        /* Amélioration de la densité pour les écrans plus petits */
        @media (max-width: 1200px) {
            .rwmb-field:not(:last-of-type) {
                margin-bottom: 15px;
                padding-bottom: 12px;
            }
            
            .rwmb-input input,
            .rwmb-input textarea,
            .rwmb-input select {
                padding: 6px 10px;
            }
        }
    </style>
    ';
}

/**
 * Filtre pour personnaliser le HTML des champs Meta Box
 * Utilise le hook rwmb_html pour des modifications avancées
 */
add_filter('rwmb_html', 'metabox_customize_field_html', 10, 3);

function metabox_customize_field_html($html, $field, $value) {
    // Ajouter des classes CSS personnalisées selon le type de champ
    $custom_classes = array(
        'text' => 'metabox-text-enhanced',
        'textarea' => 'metabox-textarea-enhanced',
        'select' => 'metabox-select-enhanced',
        'checkbox' => 'metabox-checkbox-enhanced',
        'radio' => 'metabox-radio-enhanced'
    );
    
    if (isset($custom_classes[$field['type']])) {
        $html = str_replace(
            'class="rwmb-' . $field['type'] . '"',
            'class="rwmb-' . $field['type'] . ' ' . $custom_classes[$field['type']] . '"',
            $html
        );
    }
    
    return $html;
}

/**
 * Fonction utilitaire pour ajouter des styles conditionnels
 * selon le contexte d'utilisation
 */
add_action('admin_head', 'metabox_conditional_styles');

function metabox_conditional_styles() {
    global $pagenow;
    
    // Styles spécifiques pour les pages d'édition
    if (in_array($pagenow, array('post.php', 'post-new.php', 'edit.php'))) {
        echo '
        <style>
            /* Optimisations spécifiques pour les pages d\'édition */
            .rwmb-meta-box {
                margin-top: 10px;
            }
            
            /* Amélioration de la visibilité des champs requis */
            .rwmb-field.required .rwmb-label label:after {
                content: " *";
                color: #d63638;
                font-weight: bold;
            }
        </style>
        ';
    }
}

/**
 * Hook pour personnaliser l'enqueue des scripts selon les besoins
 */
add_action('rwmb_enqueue_scripts', 'metabox_custom_scripts');

function metabox_custom_scripts() {
    // Script pour améliorer l'UX des champs
    wp_add_inline_script('jquery', '
        jQuery(document).ready(function($) {
            // Amélioration de l\'accessibilité
            $(".rwmb-input input, .rwmb-input textarea, .rwmb-input select").on("focus", function() {
                $(this).closest(".rwmb-field").addClass("focused");
            }).on("blur", function() {
                $(this).closest(".rwmb-field").removeClass("focused");
            });
            
            // Validation en temps réel pour les champs requis
            $(".rwmb-field.required input, .rwmb-field.required textarea, .rwmb-field.required select").on("blur", function() {
                var $field = $(this);
                var $wrapper = $field.closest(".rwmb-field");
                
                if ($field.val().trim() === "") {
                    $field.addClass("error").removeClass("success");
                    $wrapper.addClass("has-error");
                } else {
                    $field.addClass("success").removeClass("error");
                    $wrapper.removeClass("has-error");
                }
            });
        });
    ');
}

?>