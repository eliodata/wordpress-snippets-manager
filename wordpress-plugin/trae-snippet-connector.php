<?php
/**
 * Plugin Name: Trae AI Code Snippets Bridge
 * Plugin URI: https://github.com/trae-ai/wordpress-snippets-manager
 * Description: Bridge plugin that provides a secure REST API to connect your WordPress site with Trae AI IDE extension for seamless code snippet management with AI-powered editing capabilities.
 * Version: 1.1.0
 * Author: Trae AI by eliodata.com
 * Author URI: https://eliodata.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * Text Domain: trae-ai-snippets-bridge
 * Domain Path: /languages
 *
 * @package TraeAI
 * @subpackage SnippetsBridge
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include the API endpoint class.
require_once plugin_dir_path(__FILE__) . 'includes/class-trae-snippets-api.php';

/**
 * Initialize the plugin and register the API endpoint.
 */
function trae_snippet_connector_init() {
    $api = new Trae_Snippets_API();
    $api->register_routes();
}

add_action('rest_api_init', 'trae_snippet_connector_init');