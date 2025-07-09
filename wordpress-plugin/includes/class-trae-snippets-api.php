<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Trae_Snippets_API {

    protected $namespace = 'trae/v1';

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
    }

    public function check_permission() {
        return current_user_can('manage_options');
    }

    private function get_snippets_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'snippets';
    }

    public function get_snippets(WP_REST_Request $request) {
        global $wpdb;
        $table_name = $this->get_snippets_table_name();
        $status = $request->get_param('status');

        $query = "SELECT * FROM $table_name";

        if ($status === 'active') {
            $query .= " WHERE active = 1";
        } elseif ($status === 'inactive') {
            $query .= " WHERE active = 0";
        }

        $results = $wpdb->get_results($query);
        foreach ($results as $key => $snippet) {
            $results[$key]->active = (bool) $snippet->active;
        }
        return new WP_REST_Response($results, 200);
    }

    public function get_snippet(WP_REST_Request $request) {
        global $wpdb;
        $id = $request['id'];
        $table_name = $this->get_snippets_table_name();
        $snippet = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
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
        $new_snippet = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $new_id));
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

        $updated_snippet = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
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
}