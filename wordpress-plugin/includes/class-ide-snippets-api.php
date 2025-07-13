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
        // Ensure plugin functions are available
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        // Check for FluentSnippets variants
        if (is_plugin_active('fluent-snippets/fluent-snippets.php') || 
            is_plugin_active('easy-code-manager/easy-code-manager.php')) {
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
        // Log to confirm route registration is called
        error_log('IDE Snippets Bridge: Registering REST API routes.');

        // Rewrite rules are flushed on plugin activation.

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

        register_rest_route($this->namespace, '/fluent-snippets/(?P<id>[a-zA-Z0-9-]+)',
            [
                [
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => [$this, 'update_fluent_snippet'],
                    'permission_callback' => [$this, 'check_permission'],
                    'args' => [
                        'id' => [
                            'required' => true,
                            'validate_callback' => function($param, $request, $key) {
                                return is_string($param);
                            }
                        ],
                    ],
                ],
            ]
        );

        // FluentSnippets toggle endpoint
        register_rest_route($this->namespace, '/fluent-snippets/(?P<id>[a-zA-Z0-9-]+)/toggle',
            [
                [
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => [$this, 'toggle_fluent_snippet'],
                    'permission_callback' => [$this, 'check_permission'],
                    'args' => [
                        'id' => [
                            'required' => true,
                            'validate_callback' => function($param, $request, $key) {
                                return is_string($param);
                            }
                        ],
                    ],
                ],
            ]
        );
        
        error_log('IDE Snippets Bridge: FluentSnippets routes registered. Active plugin: ' . $this->active_plugin);
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
        
        // Use the correct table name based on active plugin
        $table_name = $wpdb->prefix . 'snippets'; // Always use Code Snippets table for this endpoint

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
        if ($updated_snippet) {
            $updated_snippet->active = (bool) $updated_snippet->active;
        }

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
        
        // Read all .php files directly from the directory (active snippets)
        $active_files = glob($fluent_snippets_path . '/*.php');
        
        // Read all .php files from disabled directory (inactive snippets)
        $disabled_dir = $fluent_snippets_path . '/disabled';
        $disabled_files = is_dir($disabled_dir) ? glob($disabled_dir . '/*.php') : [];
        
        // Process active files
        foreach ($active_files as $file_path) {
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
                'active' => true, // Files in main directory are active
                'scope' => 'backend',
                'created' => date('Y-m-d H:i:s', filemtime($file_path)),
                'modified' => date('Y-m-d H:i:s', filemtime($file_path)),
                'tags' => 'fluent-snippets'
            ];
        }
        
        // Process disabled files
        foreach ($disabled_files as $file_path) {
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
                'active' => false, // Files in disabled directory are inactive
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
        $id = str_replace('FS', '', $request['id']);
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

    /**
     * Regenerate FluentSnippets index.php file
     *
     * @since 1.3.1
     * @param string $fluent_snippets_path
     * @return void
     */
    private function regenerate_fluent_snippets_index($fluent_snippets_path) {
        $index_file = $fluent_snippets_path . '/index.php';
        
        // Get all snippet files (active and disabled)
        $active_files = glob($fluent_snippets_path . '/*.php');
        $disabled_files = glob($fluent_snippets_path . '/disabled/*.php');
        
        $published_snippets = [];
        $draft_snippets = [];
        $backend_hooks = [];
        
        // Process active files (published)
        foreach ($active_files as $file_path) {
            $filename = basename($file_path);
            
            // Skip the index.php file itself
            if ($filename === 'index.php') {
                continue;
            }
            
            // Extract ID and name from filename (format: ID-name.php)
            if (preg_match('/^(\d+)-(.+)\.php$/', $filename, $matches)) {
                $snippet_id = $matches[1];
                $snippet_name = str_replace('-', ' ', ucwords($matches[2]));
                
                // Create FluentSnippets compatible entry
                $published_snippets[$filename] = [
                    'name' => $snippet_name,
                    'description' => '',
                    'type' => 'PHP',
                    'status' => 'published',
                    'tags' => '',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'run_at' => 'backend',
                    'priority' => 10,
                    'group' => 'IDE Snippets',
                    'condition' => [
                        'status' => 'no',
                        'run_if' => 'assertive',
                        'items' => [[]]
                    ],
                    'load_as_file' => '',
                    'file_name' => $filename
                ];
                
                // Add to backend hooks
                $backend_hooks[] = $filename;
            }
        }
        
        // Process disabled files (draft)
        foreach ($disabled_files as $file_path) {
            $filename = basename($file_path);
            
            // Extract ID and name from filename (format: ID-name.php)
            if (preg_match('/^(\d+)-(.+)\.php$/', $filename, $matches)) {
                $snippet_id = $matches[1];
                $snippet_name = str_replace('-', ' ', ucwords($matches[2]));
                
                // Create FluentSnippets compatible entry for draft
                $draft_snippets[$filename] = [
                    'name' => $snippet_name,
                    'description' => '',
                    'type' => 'PHP',
                    'status' => 'draft',
                    'tags' => '',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'run_at' => 'backend',
                    'priority' => 10,
                    'group' => 'IDE Snippets',
                    'condition' => [
                        'status' => 'no',
                        'run_if' => 'assertive',
                        'items' => [[]]
                    ],
                    'load_as_file' => '',
                    'file_name' => $filename
                ];
            }
        }
        
        // Create FluentSnippets compatible structure
        $fluent_data = [
            'published' => $published_snippets,
            'draft' => $draft_snippets,
            'hooks' => [
                'backend' => $backend_hooks
            ],
            'meta' => [
                'secret_key' => md5(uniqid()),
                'force_disabled' => 'no',
                'cached_at' => date('Y-m-d H:i:s'),
                'cached_version' => '10.51',
                'cashed_domain' => get_site_url(),
                'legacy_status' => 'new',
                'auto_disable' => 'yes',
                'auto_publish' => 'no',
                'remove_on_uninstall' => 'no'
            ],
            'error_files' => []
        ];
        
        // Create the index.php content in FluentSnippets format
        $index_content = "<?php\n";
        $index_content .= "if (!defined(\"ABSPATH\")) {return;}\n";
        $index_content .= "/*\n";
        $index_content .= " * This is an auto-generated file by Fluent Snippets plugin.\n";
        $index_content .= " * Please do not edit manually.\n";
        $index_content .= " */\n";
        $index_content .= "return " . var_export($fluent_data, true) . ";\n";
        
        // Write the index file
        file_put_contents($index_file, $index_content);
        
        error_log('IDE Snippets Bridge: Regenerated FluentSnippets-compatible index.php with ' . count($published_snippets) . ' published and ' . count($draft_snippets) . ' draft snippets');
    }

    /**
     * Toggle FluentSnippets active status
     *
     * @since 1.3.1
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function toggle_fluent_snippet(WP_REST_Request $request) {
        error_log('IDE Snippets Bridge: toggle_fluent_snippet called with ID: ' . $request['id']);
        $id = str_replace('FS', '', $request['id']);
        $params = $request->get_json_params();
        error_log('IDE Snippets Bridge: Processed ID: ' . $id . ', Active plugin: ' . $this->active_plugin);
        
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
        
        $active = isset($params['active']) ? (bool) $params['active'] : false;
        $disabled_dir = $fluent_snippets_path . '/disabled';

        // Find the snippet file in either the main or disabled directory
        $files = glob($fluent_snippets_path . '/' . $id . '-*.php');
        $disabled_files = glob($disabled_dir . '/' . $id . '-*.php');

        $source_path = null;
        if (!empty($files)) {
            $source_path = $files[0];
        } elseif (!empty($disabled_files)) {
            $source_path = $disabled_files[0];
        }

        if (!$source_path) {
            return new WP_Error('not_found', 'FluentSnippet file not found in any directory', ['status' => 404]);
        }

        $filename = basename($source_path);
        $main_path = $fluent_snippets_path . '/' . $filename;
        $disabled_path = $disabled_dir . '/' . $filename;

        if ($active) {
            // Activating: move from 'disabled' to main directory
            if (file_exists($disabled_path)) {
                if (!rename($disabled_path, $main_path)) {
                    return new WP_Error('file_error', 'Failed to activate snippet by moving file.', ['status' => 500]);
                }
            }
        } else {
            // Deactivating: move from main to 'disabled' directory
            if (!is_dir($disabled_dir)) {
                wp_mkdir_p($disabled_dir);
            }
            if (file_exists($main_path)) {
                if (!rename($main_path, $disabled_path)) {
                    return new WP_Error('file_error', 'Failed to deactivate snippet by moving file.', ['status' => 500]);
                }
            }
        }
        
        // Regenerate the index.php file instead of just deleting it
        $this->regenerate_fluent_snippets_index($fluent_snippets_path);

        // Return success response
        return new WP_REST_Response([
            'success' => true,
            'message' => 'FluentSnippet status toggled successfully',
            'active' => $active,
            'id' => $id
        ], 200);
    }
}