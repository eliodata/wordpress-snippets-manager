<?php
/**
 * Plugin Name: Trae Snippet Connector
 * Description: Provides a secure REST API endpoint to manage Code Snippets for the Trae IDE extension.
 * Version: 1.0.0
 * Author: eliodata.com
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