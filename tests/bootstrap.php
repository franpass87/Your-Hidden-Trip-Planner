<?php
/**
 * Bootstrap file for PHPUnit tests
 */

// Define test environment
define('WP_TESTS_DOMAIN', 'localhost');
define('WP_TESTS_EMAIL', 'admin@localhost');
define('WP_TESTS_TITLE', 'Test Blog');

// WordPress test constants
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Define test-specific paths
define('YHT_PLUGIN_PATH', dirname(__DIR__) . '/');
define('YHT_PLUGIN_URL', 'http://localhost/wp-content/plugins/your-hidden-trip-planner/');
define('YHT_PLUGIN_FILE', YHT_PLUGIN_PATH . 'your-hidden-trip-planner.php');

// Mock WordPress functions for testing
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $args = 1) {
        // Mock implementation
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $args = 1) {
        // Mock implementation
        return true;
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://localhost/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return false;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        return true;
    }
}

// Load Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load test base class
require_once __DIR__ . '/TestCase.php';