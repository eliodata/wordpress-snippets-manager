<?php
/**
 * Snippet ID: 137
 * Name: script de surveillance des metas pour synchroniser planning et couts formateurs depuis page planning global
 * Description: 
 * @active false
 */

add_action('all', function($hook) {
    if(strpos($hook, 'meta') !== false) {
        error_log("Hook meta déclenché : $hook");
    }
});

add_filter('rwmb_pre_process_update_meta', function($null, $post_id, $meta_key) {
    error_log("Meta modifiée via RWMB : $meta_key sur $post_id");
    return $null;
}, 10, 3);