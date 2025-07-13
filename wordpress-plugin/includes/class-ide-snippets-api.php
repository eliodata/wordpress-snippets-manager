<?php
/**
 * IDE Snippets API Class
 *
 * Handles REST API endpoints for code snippet management
 * in conjunction with IDE extensions (like Trae AI, VS Code).
 *
 * @package IDESnippets
 * @subpackage API
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main API class for IDE Snippets Bridge
 *
 * This class handles all REST API endpoints for communication
 * between WordPress and IDE extensions. It provides secure
 * Code Snippets plugin and IDE extensions like Trae AI or VS Code.
 *
 * @since 1.0.0
 */
class IDE_Snippets_API {

    /**
     * Constructor
     *
     * @since 1.2.0
     */
    public function __construct() {
        if (is_plugin_active('fluent-snippets/fluent-snippets.php')) {
            $this->active_plugin = 'FluentSnippets';
        }
    }

    /**
     * API namespace for IDE snippets endpoints.
     *
     * @since 1.0.0
     * @var string
     */
    protected $namespace = 'ide/v1';

    /**
     * Active snippet plugin.
     *
     * @since 1.2.0
     * @var string
     */
    protected $active_plugin = 'CodeSnippets'; // Default to Code Snippets

    /**
     * Register REST API routes
     *
     * Registers all the REST API endpoints for snippet management.
     * Called during the 'rest_api_init' action.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_routes() {
        register_rest_route($this->namespace, '/snippets',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_snippets'],
                    'permission_callback' => [$this, 'check_permission'],
                ],
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'create_snippet'],
                    'permission_callback' => [$this, 'check_permission'],
                ],
            ]
        );

        register_rest_route($this->namespace, '/snippets/(?P<id>\d+)',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_snippet'],
                    'permission_callback' => [$this, 'check_permission'],
                ],
                [
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => [$this, 'update_snippet'],
                    'permission_callback' => [$this, 'check_permission'],
                ],
                [
                    'methods' => WP_REST_Server::DELETABLE,
                    'callback' => [$this, 'delete_snippet'],
                    'permission_callback' => [$this, 'check_permission'],
                ],
            ]
        );

        // FluentSnippets specific endpoint
        register_rest_route($this->namespace, '/fluent-snippets',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_fluent_snippets'],
                    'permission_callback' => [$this, 'check_permission'],
                ],
            ]
        );

        register_rest_route($this->namespace, '/fluent-snippets/(?P<id>\d+)',
            [
                [
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => [$this, 'update_fluent_snippet'],
                    'permission_callback' => [$this, 'check_permission'],
                    'args' => [
                        'id' => [
                            'required' => true,
                            'validate_callback' => function($param, $request, $key) {
                                return is_numeric($param);
                            }
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Check if current user has permission to access API
     *
     * Ensures only administrators can manage snippets through the API.
     * This is a security measure to prevent unauthorized access.
     *
     * @since 1.0.0
     * @return bool True if user has manage_options capability, false otherwise
     */
    public function check_permission() {
        return current_user_can('manage_options');
    }

    /**
     * Get the snippets table name
     *
     * Returns the full table name for the Code Snippets plugin table.
     * Uses WordPress database prefix for multisite compatibility.
     *
     * @since 1.0.0
     * @return string Full table name with WordPress prefix
     */
    private function get_snippets_table_name() {
        global $wpdb;
        if ('FluentSnippets' === $this->active_plugin) {
            return $wpdb->prefix . 'fluent_snippets';
        }
        return $wpdb->prefix . 'snippets';
    }

    public function get_snippets(WP_REST_Request $request) {
        global $wpdb;
        $table_name = $this->get_snippets_table_name();
        $status = $request->get_param('status');

        if ($status === 'active') {
            $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}snippets WHERE active = %d", 1));
        } elseif ($status === 'inactive') {
            $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}snippets WHERE active = %d", 0));
        } else {
            $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}snippets"));
        }
        foreach ($results as $key => $snippet) {
            $results[$key]->active = (bool) $snippet->active;
        }
        return new WP_REST_Response($results, 200);
    }

    public function get_snippet(WP_REST_Request $request) {
        global $wpdb;
        $id = $request['id'];
        $table_name = $this->get_snippets_table_name();
        $snippet = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}snippets WHERE id = %d", $id));
        if (!$snippet) {
            return new WP_Error('not_found', 'Snippet not found', ['status' => 404]);
        }
        $snippet->active = (bool) $snippet->active;
        return new WP_REST_Response($snippet, 200);
    }

    public function create_snippet(WP_REST_Request $request) {
        global $wpdb;
        $params = $request->get_json_params();
        $table_name = $this->get_snippets_table_name();

        $data = [
            'name' => sanitize_text_field($params['name']),
            'description' => sanitize_textarea_field($params['description']),
            'code' => wp_kses_post($params['code']),
            'tags' => '',
            'scope' => 'global',
            'priority' => 10,
            'active' => isset($params['active']) ? intval($params['active']) : 1,
            'modified' => current_time('mysql'),
        ];

        $result = $wpdb->insert($table_name, $data);

        if (false === $result) {
            return new WP_Error('db_error', 'Could not insert snippet', ['status' => 500]);
        }

        $new_id = $wpdb->insert_id;
        $new_snippet = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}snippets WHERE id = %d", $new_id));
        $new_snippet->active = (bool) $new_snippet->active;

        return new WP_REST_Response($new_snippet, 201);
    }

    public function update_snippet(WP_REST_Request $request) {
        global $wpdb;
        $id = $request['id'];
        $params = $request->get_json_params();
        $table_name = $this->get_snippets_table_name();

        $data = [];
        if (isset($params['name'])) {
            $data['name'] = sanitize_text_field($params['name']);
        }
        if (isset($params['code'])) {
            $data['code'] = $params['code'];
        }
        if (isset($params['description'])) {
            $data['description'] = sanitize_textarea_field($params['description']);
        }
        if (isset($params['tags'])) {
            $data['tags'] = sanitize_text_field($params['tags']);
        }
        if (isset($params['active'])) {
            $data['active'] = intval($params['active']);
        }
        $data['modified'] = current_time('mysql');

        $result = $wpdb->update($table_name, $data, ['id' => $id]);

        if (false === $result) {
            return new WP_Error('db_error', 'Could not update snippet', ['status' => 500]);
        }

        $updated_snippet = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}snippets WHERE id = %d", $id));
        $updated_snippet->active = (bool) $updated_snippet->active;

        return new WP_REST_Response($updated_snippet, 200);
    }

    public function delete_snippet(WP_REST_Request $request) {
        global $wpdb;
        $id = $request['id'];
        $table_name = $this->get_snippets_table_name();

        $wpdb->delete($table_name, ['id' => $id]);

        return new WP_REST_Response(null, 204);
    }

    /**
     * Get FluentSnippets from file system
     *
     * @since 1.2.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_fluent_snippets(WP_REST_Request $request) {
        // Find FluentSnippets storage path
        $possible_paths = [
            WP_CONTENT_DIR . '/fluent-snippet-storage',
            WP_CONTENT_DIR . '/fluent-snippets-storage',
            wp_upload_dir()['basedir'] . '/fluent-snippet-storage',
            wp_upload_dir()['basedir'] . '/fluent-snippets-storage'
        ];
        
        $fluent_snippets_path = null;
        foreach ($possible_paths as $path) {
            if (is_dir($path)) {
                $fluent_snippets_path = $path;
                break;
            }
        }
        
        if (!$fluent_snippets_path) {
            return new WP_Error('not_found', 'FluentSnippets storage directory not found', ['status' => 404]);
        }
        
        $snippets = [];
        
        // Read all .php files directly from the directory
        $files = glob($fluent_snippets_path . '/*.php');
        
        foreach ($files as $file_path) {
            $filename = basename($file_path);
            
            // Skip index.php
            if ($filename === 'index.php') {
                continue;
            }
            
            $snippet_content = file_get_contents($file_path);
            
            // Extract ID from filename
            preg_match('/^(\d+)-/', $filename, $matches);
            $id = isset($matches[1]) ? intval($matches[1]) : rand(1000, 9999);
            
            // Extract name from filename (remove ID and .php extension)
            $name = preg_replace('/^\d+-/', '', $filename);
            $name = str_replace('.php', '', $name);
            $name = str_replace('-', ' ', $name);
            $name = ucwords($name);
            
            // Clean the PHP content - remove opening PHP tag and extract actual code
            $clean_code = $snippet_content;
            // Remove opening PHP tag
            $clean_code = preg_replace('/^<\?php\s*/', '', $clean_code);
            // Remove closing PHP tag if present
            $clean_code = preg_replace('/\?>\s*$/', '', $clean_code);
            $clean_code = trim($clean_code);
            
            $snippets[] = [
                'id' => $id,
                'name' => $name,
                'description' => 'FluentSnippet: ' . $name,
                'code' => $clean_code,
                'active' => true, // Assume all files in directory are active
                'scope' => 'backend',
                'created' => date('Y-m-d H:i:s', filemtime($file_path)),
                'modified' => date('Y-m-d H:i:s', filemtime($file_path)),
                'tags' => 'fluent-snippets'
            ];
        }
        
        return new WP_REST_Response(['snippets' => $snippets], 200);
    }

    /**
     * Update FluentSnippets file
     *
     * @since 1.3.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function update_fluent_snippet(WP_REST_Request $request) {
        $id = $request['id'];
        $params = $request->get_json_params();
        
        // Find FluentSnippets storage path
        $possible_paths = [
            WP_CONTENT_DIR . '/fluent-snippet-storage',
            WP_CONTENT_DIR . '/fluent-snippets-storage',
            wp_upload_dir()['basedir'] . '/fluent-snippet-storage',
            wp_upload_dir()['basedir'] . '/fluent-snippets-storage'
        ];
        
        $fluent_snippets_path = null;
        foreach ($possible_paths as $path) {
            if (is_dir($path)) {
                $fluent_snippets_path = $path;
                break;
            }
        }
        
        if (!$fluent_snippets_path) {
            return new WP_Error('not_found', 'FluentSnippets storage directory not found', ['status' => 404]);
        }
        
        // Find the existing file for this ID
        $files = glob($fluent_snippets_path . '/' . $id . '-*.php');
        
        if (empty($files)) {
            return new WP_Error('not_found', 'FluentSnippet file not found', ['status' => 404]);
        }
        
        $file_path = $files[0]; // Take the first match
        
        // Validate required parameters
        if (!isset($params['content'])) {
            return new WP_Error('missing_content', 'Content parameter is required', ['status' => 400]);
        }
        
        // Sanitize and prepare content
        $content = $params['content'];
        
        // Ensure content starts with <?php if it doesn't already
        if (!str_starts_with(trim($content), '<?php')) {
            $content = '<?php\n' . $content;
        }
        
        // Write the updated content to the file
        $result = file_put_contents($file_path, $content);
        
        if ($result === false) {
            return new WP_Error('write_error', 'Failed to write to FluentSnippets file', ['status' => 500]);
        }
        
        // Return success response
        return new WP_REST_Response([
            'success' => true,
            'message' => 'FluentSnippet updated successfully',
            'file_path' => basename($file_path),
            'bytes_written' => $result
        ], 200);
    }
    
    /**
     * Parse FluentSnippets index.php file
     *
     * @since 1.2.0
     * @param string $content
     * @return array|null
     */
    private function parse_fluent_snippets_index($content) {
        // Remove PHP opening tag and security check
        $content = preg_replace('/<\?php[\s\S]*?\*\//', '', $content);
        $content = preg_replace('/if \(!defined\("ABSPATH"\)\) \{return;\}/', '', $content);
        
        // Extract the array part
        if (preg_match('/return\s+(array\([\s\S]*\));/', $content, $matches)) {
            $array_string = $matches[1];
            
            // Simple PHP array to JSON conversion
            $array_string = preg_replace('/array\s*\(/', '[', $array_string);
            $array_string = preg_replace('/\)/', ']', $array_string);
            $array_string = preg_replace('/\'([^\']+)\'\s*=>/', '"$1":', $array_string);
            $array_string = preg_replace('/=>/', ':', $array_string);
            $array_string = preg_replace('/,\s*\]/', ']', $array_string);
            
            // Try to decode as JSON
            $decoded = json_decode($array_string, true);
            if ($decoded !== null) {
                return $decoded;
            }
        }
        
        return null;
    }
}