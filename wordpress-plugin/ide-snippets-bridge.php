<?php
/**
 * Plugin Name: IDE Code Snippets Bridge
 * Plugin URI: https://github.com/ide-snippets/wordpress-snippets-manager
 * Description: Bridge plugin that provides a secure REST API to connect your WordPress site with IDE extensions (like Trae AI, VS Code) for seamless code snippet management with AI-powered editing capabilities.
 * Version: 1.3.1
 * Author: IDE Snippets by eliodata.com
 * Author URI: https://eliodata.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Text Domain: ide-snippets-bridge
 *
 * @package TraeAI
 * @subpackage SnippetsBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('IDE_SNIPPETS_BRIDGE_VERSION', '1.3.1');
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
        add_action('rest_api_init', array($this, 'add_status_endpoint'));

        // Plugin activation/deactivation hooks
        // Plugin activation/deactivation hooks
        register_activation_hook(IDE_SNIPPETS_BRIDGE_PLUGIN_BASENAME, array($this, 'activate'));
        register_deactivation_hook(IDE_SNIPPETS_BRIDGE_PLUGIN_BASENAME, array($this, 'deactivate'));
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
     * Add status endpoint to check active snippet plugin
     *
     * @since 1.2.0
     */
    public function add_status_endpoint() {
        register_rest_route('ide/v1', '/status', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_status'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Get the active snippet plugin status
     *
     * @since 1.2.0
     * @return WP_REST_Response
     */
    public function get_status() {
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        $response_data = [
            'active_plugin' => null,
            'fluent_snippets_path' => null,
            'message' => 'Aucun plugin de snippets compatible n\'est actif.'
        ];

        $response_data['active_plugins'] = [];
        $fluent_snippets_active = is_plugin_active('fluent-snippets/fluent-snippets.php') ||
                                 is_plugin_active('easy-code-manager/easy-code-manager.php');
        
        // Check for various Code Snippets plugin variants
        $code_snippets_active = is_plugin_active('code-snippets/code-snippets.php') || 
                               is_plugin_active('code-snippets-pro/code-snippets-pro.php') ||
                               is_plugin_active('code-snippets-pro/code-snippets.php') ||
                               function_exists('code_snippets') ||
                               class_exists('Code_Snippets');

        if ($fluent_snippets_active) {
            $response_data['active_plugins'][] = 'FluentSnippets';
            if (defined('FLUENT_SNIPPETS_STORAGE_PATH')) {
                $response_data['fluent_snippets_path'] = FLUENT_SNIPPETS_STORAGE_PATH;
            } else {
                // Try multiple possible paths for FluentSnippets storage
                $possible_paths = [
                    WP_CONTENT_DIR . '/fluent-snippet-storage',
                    WP_CONTENT_DIR . '/fluent-snippets-storage',
                    wp_upload_dir()['basedir'] . '/fluent-snippet-storage',
                    wp_upload_dir()['basedir'] . '/fluent-snippets-storage'
                ];
                
                $fluent_path = null;
                foreach ($possible_paths as $path) {
                    if (file_exists($path)) {
                        $fluent_path = $path;
                        break;
                    }
                }
                
                $response_data['fluent_snippets_path'] = $fluent_path ?: WP_CONTENT_DIR . '/fluent-snippet-storage';
            }
        }

        if ($code_snippets_active) {
            $response_data['active_plugins'][] = 'Code Snippets';
        }

        if (!empty($response_data['active_plugins'])) {
            $response_data['message'] = 'Plugins de snippets actifs: ' . implode(', ', $response_data['active_plugins']);
        } else {
            $response_data['message'] = 'Aucun plugin de snippets compatible n\'est actif.';
        }

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Check for required dependencies
     *
     * @since 1.1.0
     */
    public function check_dependencies() {
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        $screen = get_current_screen();
        if ( ! $screen || strpos($screen->id, 'plugins') === false) {
            return;
        }

        // Check for various Code Snippets plugin variants
        $code_snippets_active = is_plugin_active('code-snippets/code-snippets.php') || 
                               is_plugin_active('code-snippets-pro/code-snippets-pro.php') ||
                               is_plugin_active('code-snippets-pro/code-snippets.php') ||
                               function_exists('code_snippets') ||
                               class_exists('Code_Snippets');
        $fluent_snippets_active = is_plugin_active('fluent-snippets/fluent-snippets.php') ||
                                 is_plugin_active('easy-code-manager/easy-code-manager.php');

        if (!$code_snippets_active && !$fluent_snippets_active) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>IDE Code Snippets Bridge:</strong> ';
            echo 'This plugin requires either the <a href="' . esc_url(admin_url('plugin-install.php?s=code-snippets&tab=search&type=term')) . '">Code Snippets</a> or <a href="' . esc_url(admin_url('plugin-install.php?s=fluent-snippets&tab=search&type=term')) . '">FluentSnippets</a> plugin to be installed and activated.';
            echo '</p>';
            echo '</div>';
        }
    }

    /**
     * Plugin activation callback
     *
     * @since 1.3.1
     */
    public function activate() {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            deactivate_plugins(IDE_SNIPPETS_BRIDGE_PLUGIN_BASENAME);
            wp_die(esc_html__('IDE Code Snippets Bridge requires WordPress 5.0 or higher.', 'ide-snippets-bridge'));
        }

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(IDE_SNIPPETS_BRIDGE_PLUGIN_BASENAME);
            wp_die(esc_html__('IDE Code Snippets Bridge requires PHP 7.4 or higher.', 'ide-snippets-bridge'));
        }

        // Ensure the API routes are registered and rewrite rules are flushed.
        $this->init_api();
        flush_rewrite_rules();
        error_log('IDE Snippets Bridge: Plugin activated and rewrite rules flushed.');
    }

    /**
     * Plugin deactivation callback
     *
     * @since 1.3.1
     */
    public function deactivate() {
        // Flush rewrite rules to remove the API endpoints.
        flush_rewrite_rules();
        error_log('IDE Snippets Bridge: Plugin deactivated and rewrite rules flushed.');
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