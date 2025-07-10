<?php
/**
 * Snippet ID: 203
 * Name: styles css metabox.io champs labels generaux
 * Description: Styles CSS pour Meta Box - Version ultra-simplifiée  Active: false
 * @active true
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// EXECUTION IMMEDIATE - Sans conditions
if (function_exists('add_action')) {
    
    // Hook principal avec priorité maximale
    add_action('wp_loaded', function() {
        if (is_admin()) {
            add_action('admin_head', 'snippet_203_inject_styles', 1);
            add_action('admin_footer', 'snippet_203_inject_styles_footer', 1);
        }
    });
    
    // Hook de secours immédiat
    add_action('admin_head', 'snippet_203_inject_styles', 1);
    add_action('admin_footer', 'snippet_203_inject_styles_footer', 1);
    
    // Hook init de secours
    add_action('init', function() {
        if (is_admin()) {
            add_action('admin_head', 'snippet_203_inject_styles', 1);
        }
    });
}

// Fonction d'injection des styles
function snippet_203_inject_styles() {
    static $injected = false;
    if ($injected) return;
    $injected = true;
    
    echo "\n<!-- SNIPPET 203 ACTIF - STYLES META BOX -->\n";
    echo '<style type="text/css" id="snippet-203-metabox">';
    echo snippet_203_get_css();
    echo '</style>';
    echo "\n<!-- FIN SNIPPET 203 -->\n";
}

// Fonction d'injection footer (secours)
function snippet_203_inject_styles_footer() {
    static $injected_footer = false;
    if ($injected_footer) return;
    $injected_footer = true;
    
    echo "\n<!-- SNIPPET 203 FOOTER - STYLES META BOX -->\n";
    echo '<style type="text/css" id="snippet-203-metabox-footer">';
    echo snippet_203_get_css();
    echo '</style>';
    echo "\n<!-- FIN SNIPPET 203 FOOTER -->\n";
}

// CSS RADICAL POUR TEST
function snippet_203_get_css() {
    return '
        /* MODERN CONDENSED META BOX DESIGN */
        .postbox .rwmb-meta-box {
            background: #ffffff !important;
            border: 1px solid #e1e5e9 !important;
            border-radius: 8px !important;
            padding: 16px !important;
            margin: 12px 0 !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
        }
        
        .rwmb-field {
            background: transparent !important;
            border: none !important;
            padding: 8px 0 !important;
            margin: 6px 0 !important;
            border-bottom: 1px solid #f0f0f1 !important;
        }
        
        .rwmb-field:last-child {
            border-bottom: none !important;
        }
        
        .rwmb-label {
             background: transparent !important;
             color: #d63638 !important;
             font-size: 13px !important;
             font-weight: 600 !important;
             padding: 0 0 4px 0 !important;
             text-transform: none !important;
             letter-spacing: 0.3px !important;
             display: block !important;
         }
        
        .rwmb-input {
            margin-top: 4px !important;
        }
        
        .rwmb-input input[type="text"],
        .rwmb-input input[type="email"],
        .rwmb-input input[type="url"],
        .rwmb-input input[type="number"],
        .rwmb-input textarea,
        .rwmb-input select {
            background: #ffffff !important;
            color: #2c3338 !important;
            border: 1px solid #c3c4c7 !important;
            border-radius: 4px !important;
            padding: 8px 12px !important;
            font-size: 13px !important;
            line-height: 1.4 !important;
            width: 100% !important;
            box-sizing: border-box !important;
            transition: border-color 0.15s ease-in-out !important;
        }
        
        .rwmb-input input:focus,
        .rwmb-input textarea:focus,
        .rwmb-input select:focus {
            border-color: #2271b1 !important;
            box-shadow: 0 0 0 1px #2271b1 !important;
            outline: none !important;
        }
        
        .rwmb-input textarea {
            min-height: 80px !important;
            resize: vertical !important;
        }
        
        .rwmb-column {
            background: transparent !important;
            border: none !important;
            padding: 0 8px !important;
        }
        
        .rwmb-column:first-child {
            padding-left: 0 !important;
        }
        
        .rwmb-column:last-child {
            padding-right: 0 !important;
        }
        
        .rwmb-checkbox-wrapper,
        .rwmb-radio-wrapper {
            display: flex !important;
            align-items: center !important;
            margin: 4px 0 !important;
        }
        
        .rwmb-checkbox-wrapper input[type="checkbox"],
        .rwmb-radio-wrapper input[type="radio"] {
            width: 16px !important;
            height: 16px !important;
            margin: 0 8px 0 0 !important;
            accent-color: #2271b1 !important;
        }
        
        .rwmb-checkbox-wrapper label,
        .rwmb-radio-wrapper label {
            font-size: 13px !important;
            color: #2c3338 !important;
            margin: 0 !important;
            cursor: pointer !important;
        }
        
        .rwmb-button {
            background: #2271b1 !important;
            color: #ffffff !important;
            border: 1px solid #2271b1 !important;
            border-radius: 4px !important;
            padding: 8px 16px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            cursor: pointer !important;
            transition: all 0.15s ease-in-out !important;
        }
        
        .rwmb-button:hover {
            background: #135e96 !important;
            border-color: #135e96 !important;
        }
        
        .rwmb-button.button-secondary {
            background: #ffffff !important;
            color: #2271b1 !important;
            border: 1px solid #c3c4c7 !important;
        }
        
        .rwmb-button.button-secondary:hover {
            background: #f6f7f7 !important;
            border-color: #8c8f94 !important;
        }
        
        /* Groupes répétables */
        .rwmb-group {
            background: #f9f9f9 !important;
            border: 1px solid #e1e5e9 !important;
            border-radius: 6px !important;
            padding: 12px !important;
            margin: 8px 0 !important;
        }
        
        .rwmb-group-title {
            font-size: 14px !important;
            font-weight: 600 !important;
            color: #1d2327 !important;
            margin-bottom: 8px !important;
        }
        
        /* Onglets */
        .rwmb-tab-nav {
            border-bottom: 1px solid #e1e5e9 !important;
            margin-bottom: 16px !important;
        }
        
        .rwmb-tab-nav a {
            background: transparent !important;
            color: #646970 !important;
            border: none !important;
            border-bottom: 2px solid transparent !important;
            padding: 8px 16px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            text-decoration: none !important;
            transition: all 0.15s ease-in-out !important;
        }
        
        .rwmb-tab-nav a:hover,
        .rwmb-tab-nav a.rwmb-tab-active {
            color: #2271b1 !important;
            border-bottom-color: #2271b1 !important;
        }
        
        /* Messages d\'erreur */
        .rwmb-error {
            background: #fcf2f2 !important;
            color: #d63638 !important;
            border: 1px solid #f0a5a5 !important;
            border-radius: 4px !important;
            padding: 8px 12px !important;
            font-size: 13px !important;
            margin: 4px 0 !important;
        }
        
        /* Responsive */
        @media (max-width: 782px) {
            .rwmb-column {
                width: 100% !important;
                padding: 0 !important;
                margin-bottom: 8px !important;
            }
        }
    ';
}

// Notification de diagnostic
if (function_exists('add_action')) {
    add_action('admin_notices', function() {
        if (is_admin() && current_user_can('manage_options')) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Meta Box Design Moderne:</strong> Interface condensée et ergonomique activée! ✨</p>';
            echo '</div>';
        }
    });
}

// Test de diagnostic dans le footer
if (function_exists('add_action')) {
    add_action('admin_footer', function() {
        if (is_admin()) {
            echo "\n<!-- SNIPPET 203 - DIAGNOSTIC: EXECUTION CONFIRMEE -->\n";
        }
    });
}

?>