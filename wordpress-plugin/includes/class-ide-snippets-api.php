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
            
            // Extract real status from Internal Doc @status field
            $real_status = $this->extract_status_from_internal_doc($snippet_content);
            
            $snippets[] = [
                'id' => $id,
                'name' => $name,
                'description' => 'FluentSnippet: ' . $name,
                'code' => $clean_code,
                'active' => $real_status, // Read from Internal Doc @status
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
            
            // Extract real status from Internal Doc @status field
            $real_status = $this->extract_status_from_internal_doc($snippet_content);
            
            $snippets[] = [
                'id' => $id,
                'name' => $name,
                'description' => 'FluentSnippet: ' . $name,
                'code' => $clean_code,
                'active' => $real_status, // Read from Internal Doc @status
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
     * Extract status from Internal Doc @status field
     *
     * @since 1.5.0
     * @param string $content
     * @return bool
     */
    private function extract_status_from_internal_doc($content) {
        // Look for the @status: field in the Internal Doc section, accounting for optional asterisk
        if (preg_match('/(?:\/\/|\*)?\s*@status:\s*([^\n\r]+)/', $content, $matches)) {
            $status = trim(strtolower($matches[1]));
            // FluentSnippets uses 'published' for active, 'draft' for inactive
            return $status === 'published';
        }
        // If no @status found in Internal Doc, fallback to false (draft)
        return false;
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
        
        // Get all snippet files from both main directory and disabled directory
        $all_files = array_merge(
            glob($fluent_snippets_path . '/*.php'),
            glob($fluent_snippets_path . '/disabled/*.php')
        );
        
        $published_snippets = [];
        $draft_snippets = [];
        $backend_hooks = [];
        
        // Process all files and read their actual status from Internal Doc
        foreach ($all_files as $file_path) {
            $filename = basename($file_path);
            
            // Skip the index.php file itself
            if ($filename === 'index.php') {
                continue;
            }
            
            // Extract ID and name from filename (format: ID-name.php)
            if (preg_match('/^(\d+)-(.+)\.php$/', $filename, $matches)) {
                $snippet_id = $matches[1];
                $snippet_name = str_replace('-', ' ', ucwords($matches[2]));
                
                // Read the actual status from the file's Internal Doc
                $file_content = file_get_contents($file_path);
                $status = $this->extract_status_from_file($file_content);
                
                // Create FluentSnippets compatible entry
                $snippet_entry = [
                    'name' => $snippet_name,
                    'description' => '',
                    'type' => 'PHP',
                    'status' => $status,
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
                
                // Add to the appropriate array based on actual status
                if ($status === 'published') {
                    $published_snippets[$filename] = $snippet_entry;
                    $backend_hooks[] = $filename;
                } else {
                    $draft_snippets[$filename] = $snippet_entry;
                }
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
        
        // Create the index.php content in FluentSnippets format with output buffering protection
        $index_content = "<?php\n";
        $index_content .= "if (!defined(\"ABSPATH\")) {return;}\n";
        $index_content .= "/*\n";
        $index_content .= " * This is an auto-generated file by Fluent Snippets plugin.\n";
        $index_content .= " * Please do not edit manually.\n";
        $index_content .= " * Enhanced by IDE Snippets Bridge for header safety.\n";
        $index_content .= " */\n";
        $index_content .= "\n";
        $index_content .= "// IDE Snippets Bridge: Add output buffering protection for snippets with HTML\n";
        $index_content .= "add_action('init', function() {\n";
        $index_content .= "    if (!headers_sent()) {\n";
        $index_content .= "        ob_start();\n";
        $index_content .= "    }\n";
        $index_content .= "}, 1);\n";
        $index_content .= "\n";
        $index_content .= "add_action('wp_footer', function() {\n";
        $index_content .= "    if (ob_get_level()) {\n";
        $index_content .= "        \$content = ob_get_clean();\n";
        $index_content .= "        // Only output if it's not a complete HTML document\n";
        $index_content .= "        if (!preg_match('/<!DOCTYPE|<html/i', \$content)) {\n";
        $index_content .= "            echo \$content;\n";
        $index_content .= "        }\n";
        $index_content .= "    }\n";
        $index_content .= "}, 999);\n";
        $index_content .= "\n";
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
        global $wpdb;
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
        
        // Find the snippet file. It should be in the main directory.
        $files = glob($fluent_snippets_path . '/' . $id . '-*.php');
        $source_path = null;

        if (!empty($files)) {
            $source_path = $files[0];
        } else {
            // As a fallback for snippets disabled with older versions of the bridge, check the disabled directory.
            $disabled_dir = $fluent_snippets_path . '/disabled';
            $disabled_files = glob($disabled_dir . '/' . $id . '-*.php');
            if (!empty($disabled_files)) {
                $old_path = $disabled_files[0];
                $filename = basename($old_path);
                $new_path = $fluent_snippets_path . '/' . $filename;
                // Move it back to the main directory.
                if (rename($old_path, $new_path)) {
                    $source_path = $new_path;
                    error_log('IDE Snippets Bridge: Moved snippet from disabled directory back to main directory: ' . $filename);
                } else {
                     return new WP_Error('file_error', 'Failed to move snippet from disabled to main directory.', ['status' => 500]);
                }
            }
        }

        if (!$source_path || !file_exists($source_path)) {
            return new WP_Error('not_found', 'FluentSnippet file not found.', ['status' => 404]);
        }

        // Update status in FluentSnippets file metadata
        $new_status = $active ? 'published' : 'draft';
        
        $this->update_snippet_status_in_file($source_path, $new_status);
        error_log('IDE Snippets Bridge: Updated FluentSnippet ID ' . $id . ' status to ' . $new_status . ' in file metadata at ' . $source_path);
        
        // Regenerate the index.php file
        $this->regenerate_fluent_snippets_index($fluent_snippets_path);

        // Return success response
        return new WP_REST_Response([
            'success' => true,
            'message' => 'FluentSnippet status toggled successfully. File not moved.',
            'active' => $active,
            'status' => $new_status,
            'id' => $id
        ], 200);
    }

    /**
     * Extract status from file's Internal Doc section
     *
     * @param string $file_content
     * @return string 'published' or 'draft'
     */
    private function extract_status_from_file($file_content) {
        // Look for @status: field in the Internal Doc section
        if (preg_match('/@status:\s*(published|draft|active|inactive)/i', $file_content, $matches)) {
            $status = strtolower(trim($matches[1]));
            // Convert legacy status values
            if ($status === 'active') {
                return 'published';
            } elseif ($status === 'inactive') {
                return 'draft';
            }
            return $status;
        }
        
        // Fallback: check for @active tag in header
        if (preg_match('/@active\s+(true|false)/i', $file_content, $matches)) {
            return (strtolower($matches[1]) === 'true') ? 'published' : 'draft';
        }
        
        // Default to draft if no status found
        return 'draft';
    }

    /**
     * Update snippet status in file metadata
     */
    private function update_snippet_status_in_file($file_path, $new_status) {
        if (!file_exists($file_path)) {
            return;
        }

        $content = file_get_contents($file_path);
        if ($content === false) {
            return;
        }

        // IMPORTANT: Remove @active tags completely to eliminate dual status confusion
        // Only use @status field in Internal Doc as the single source of truth
        
        // Remove any @active tag from the main docblock
        $content = preg_replace(
            '/^\s*\*\s*@active\s+(true|false)\s*$/m',
            '',
            $content
        );
        
        // Remove empty comment lines left after @active removal
        $content = preg_replace(
            '/^\s*\*\s*$/m',
            '',
            $content
        );

        // Update the @status field in the Internal Doc section
        $content = preg_replace(
            '/(\* @status:\s+)(published|draft|active|inactive)/i',
            '$1' . $new_status,
            $content
        );

        // Also update any standalone @status lines
        $content = preg_replace(
            '/(\* @status\s+)(published|draft|active|inactive)/i',
            '$1' . $new_status,
            $content
        );

        // Write the updated content back to the file
        file_put_contents($file_path, $content);
    }

    /**
     * Fix snippet HTML output to prevent header conflicts
     */
    private function fix_snippet_html_output($file_path) {
        if (!file_exists($file_path)) {
            return;
        }

        $content = file_get_contents($file_path);
        if ($content === false) {
            return;
        }

        // Check if snippet contains full HTML structure
        if (preg_match('/<!DOCTYPE\s+html|<html[^>]*>|<head[^>]*>|<body[^>]*>/i', $content)) {
            // Extract the PHP header and internal doc
            preg_match('/(.*?<\?php if \(!defined\("ABSPATH"\)\) \{ return;\} \/\/ <Internal Doc End> \?>)/s', $content, $header_matches);
            $header = isset($header_matches[1]) ? $header_matches[1] : '';

            // Extract CSS content
            preg_match('/<style[^>]*>(.*?)<\/style>/s', $content, $css_matches);
            $css = isset($css_matches[1]) ? trim($css_matches[1]) : '';

            // Extract HTML content (everything between body tags)
            preg_match('/<body[^>]*>(.*?)<\/body>/s', $content, $body_matches);
            $html = isset($body_matches[1]) ? trim($body_matches[1]) : '';

            // Extract JavaScript content
            preg_match('/<script[^>]*>(.*?)<\/script>/s', $content, $js_matches);
            $js = isset($js_matches[1]) ? trim($js_matches[1]) : '';

            // Reconstruct the snippet using WordPress hooks
            $new_content = $header . "\n<?php\n";

            if (!empty($css)) {
                $new_content .= "// Ajouter le CSS dans le head\n";
                $new_content .= "add_action('wp_head', function() {\n";
                $new_content .= "    ?>\n";
                $new_content .= "    <style>\n";
                $new_content .= "        " . str_replace("\n", "\n        ", $css) . "\n";
                $new_content .= "    </style>\n";
                $new_content .= "    <?php\n";
                $new_content .= "});\n\n";
            }

            if (!empty($html) || !empty($js)) {
                $new_content .= "// Ajouter le HTML et JavaScript dans le footer\n";
                $new_content .= "add_action('wp_footer', function() {\n";
                $new_content .= "    ?>\n";
                if (!empty($html)) {
                    $new_content .= "    " . str_replace("\n", "\n    ", $html) . "\n";
                }
                if (!empty($js)) {
                    $new_content .= "\n    <script>\n";
                    $new_content .= "        " . str_replace("\n", "\n        ", $js) . "\n";
                    $new_content .= "    </script>\n";
                }
                $new_content .= "    <?php\n";
                $new_content .= "});\n";
            }

            $new_content .= "?>";

            // Write the fixed content back to the file
            file_put_contents($file_path, $new_content);
        }
    }
}