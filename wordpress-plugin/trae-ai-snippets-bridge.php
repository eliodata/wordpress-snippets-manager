<?php
/**
 * Plugin Name: IDE Code Snippets Bridge
 * Plugin URI: https://github.com/ide-snippets/wordpress-snippets-manager
 * Description: Bridge plugin that provides a secure REST API to connect your WordPress site with IDE extensions (like Trae AI, VS Code) for seamless code snippet management with AI-powered editing capabilities.
 * Version: 1.1.0
 * Author: IDE Snippets by eliodata.com
 * Author URI: https://eliodata.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * Text Domain: ide-snippets-bridge
 * Domain Path: /languages
 *
 * @package TraeAI
 * @subpackage SnippetsBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('IDE_SNIPPETS_BRIDGE_VERSION', '1.1.0');
define('IDE_SNIPPETS_BRIDGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IDE_SNIPPETS_BRIDGE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IDE_SNIPPETS_BRIDGE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include the API endpoint class
require_once IDE_SNIPPETS_BRIDGE_PLUGIN_DIR . 'includes/class-ide-snippets-api.php';

/**
 * Main plugin class
 *
 * @since 1.0.0
 */
class IDE_Snippets_Bridge {

    /**
     * Plugin instance
     *
     * @since 1.0.0
     * @var IDE_Snippets_Bridge
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @since 1.0.0
     * @return IDE_Snippets_Bridge
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        add_action('rest_api_init', array($this, 'init_api'));
        add_action('admin_notices', array($this, 'check_dependencies'));
        
        // Plugin activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Initialize the API endpoints
     *
     * @since 1.0.0
     */
    public function init_api() {
        $api = new IDE_Snippets_API();
        $api->register_routes();
    }

    /**
     * Check for required dependencies
     *
     * @since 1.1.0
     */
    public function check_dependencies() {
        if (!is_plugin_active('code-snippets/code-snippets.php')) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>IDE Code Snippets Bridge:</strong> ';
            echo 'This plugin requires the <a href="' . admin_url('plugin-install.php?s=code-snippets&tab=search&type=term') . '">Code Snippets</a> plugin to be installed and activated.';
            echo '</p>';
            echo '</div>';
        }
    }

    /**
     * Plugin activation
     *
     * @since 1.0.0
     */
    public function activate() {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            deactivate_plugins(IDE_SNIPPETS_BRIDGE_PLUGIN_BASENAME);
            wp_die(__('IDE Code Snippets Bridge requires WordPress 5.0 or higher.', 'ide-snippets-bridge'));
        }

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(IDE_SNIPPETS_BRIDGE_PLUGIN_BASENAME);
            wp_die(__('IDE Code Snippets Bridge requires PHP 7.4 or higher.', 'ide-snippets-bridge'));
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     *
     * @since 1.0.0
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

/**
 * Initialize the plugin
 *
 * @since 1.0.0
 */
function ide_snippets_bridge_init() {
    return IDE_Snippets_Bridge::get_instance();
}

// Initialize the plugin
ide_snippets_bridge_init();