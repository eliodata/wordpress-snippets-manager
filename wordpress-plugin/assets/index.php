<?php
/**
 * Security file to prevent direct access to assets directory
 *
 * @package IDESnippets
 * @subpackage SnippetsBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Silence is golden