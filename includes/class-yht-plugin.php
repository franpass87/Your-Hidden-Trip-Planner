<?php
/**
 * Main plugin bootstrap file
 * 
 * @package YourHiddenTrip
 * @version 6.3
 */

if (!defined('ABSPATH')) exit;

/**
 * Main plugin class - Singleton pattern
 */
class YHT_Plugin {
    
    /**
     * Single instance of the plugin
     * @var YHT_Plugin
     */
    private static $instance = null;
    
    /**
     * Plugin version
     * @var string
     */
    public $version = '6.3';
    
    /**
     * Plugin settings
     * @var array
     */
    private $settings = array();
    
    /**
     * Get singleton instance
     * @return YHT_Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor (singleton pattern)
     */
    private function __construct() {
        $this->register_autoloader();
        $this->init();
    }

    /**
     * Register class autoloader for plugin classes.
     */
    private function register_autoloader() {
        spl_autoload_register(function($class) {
            if (strpos($class, 'YHT_') !== 0) {
                return;
            }

            $filename = 'class-' . strtolower(str_replace('_', '-', $class)) . '.php';
            $directories = array(
                'includes/',
                'includes/admin/',
                'includes/frontend/',
                'includes/post-types/',
                'includes/rest-api/',
                'includes/utilities/',
                'includes/analytics/',
                'includes/security/',
                'includes/pdf/',
            );

            foreach ($directories as $dir) {
                $path = YHT_PLUGIN_PATH . $dir . $filename;
                if (file_exists($path)) {
                    require_once $path;
                    return;
                }
            }
        });
    }
    
    /**
     * Initialize the plugin
     */
    private function init() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'load_components'));
        add_action('admin_init', array($this, 'maybe_run_updates'));
        register_activation_hook(YHT_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(YHT_PLUGIN_FILE, array($this, 'deactivate'));
    }
    
    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain('your-hidden-trip', false, dirname(plugin_basename(YHT_PLUGIN_FILE)) . '/languages');
        
        // WPML compatibility - ensure current language is detected
        if (function_exists('icl_get_current_language')) {
            $current_lang = icl_get_current_language();
            if ($current_lang) {
                add_filter('locale', array($this, 'wpml_locale_filter'));
            }
        }
    }
    
    /**
     * WPML locale filter
     */
    public function wpml_locale_filter($locale) {
        if (function_exists('icl_get_current_language')) {
            $current_lang = icl_get_current_language();
            if ($current_lang === 'en') {
                return 'en_US';
            } elseif ($current_lang === 'it') {
                return 'it_IT';
            }
        }
        return $locale;
    }
    
    /**
     * Get current language for WPML compatibility
     */
    public function get_current_language() {
        if (function_exists('icl_get_current_language')) {
            return icl_get_current_language();
        }
        return 'it'; // Default to Italian
    }
    
    /**
     * Check if WPML is active and configured
     */
    public function is_wpml_active() {
        return function_exists('icl_get_current_language') && function_exists('icl_get_languages');
    }
    
    /**
     * Get available languages from WPML
     */
    public function get_available_languages() {
        if (function_exists('icl_get_languages')) {
            return icl_get_languages('skip_missing=0&orderby=code');
        }
        return array(
            'it' => array('code' => 'it', 'native_name' => 'Italiano'),
            'en' => array('code' => 'en', 'native_name' => 'English')
        );
    }
    
    /**
     * Load plugin components
     */
    public function load_components() {
        $this->load_post_types();
        $this->load_admin();
        $this->load_rest_api();
        $this->load_frontend();
        $this->load_utilities();
        $this->load_analytics();
        $this->load_security();
    }
    
    /**
     * Load post types and taxonomies
     */
    private function load_post_types() {
        new YHT_Post_Types();
    }
    
    /**
     * Load admin functionality
     */
    private function load_admin() {
        if (is_admin()) {
            new YHT_Admin();

            // Load backend management components
            new YHT_Dashboard();
            new YHT_Customer_Manager();
            new YHT_System_Health();
            // Settings and Importer classes load on demand via autoloader
            new YHT_Email_Templates();
            new YHT_Advanced_Reports();
            new YHT_API_Manager();
            new YHT_Backup_Restore();
            new YHT_User_Roles();
        }
    }
    
    /**
     * Load REST API endpoints
     */
    private function load_rest_api() {
        new YHT_Rest_Controller();
    }
    
    /**
     * Load frontend functionality
     */
    private function load_frontend() {
        if (!is_admin()) {
            new YHT_Shortcode();
            new YHT_Reviews();
        }
    }
    
    /**
     * Load utility classes
     */
    private function load_utilities() {
        // Utility classes are loaded on demand via autoloader
    }
    
    /**
     * Load analytics module
     */
    private function load_analytics() {
        new YHT_Analytics();
    }
    
    /**
     * Load security module
     */
    private function load_security() {
        new YHT_Security();
    }

    /**
     * Check for plugin updates and reset transients if version changed
     */
    public function maybe_run_updates() {
        $stored_version = get_option('yht_plugin_version');
        if ($stored_version !== $this->version) {
            $this->clear_transients();
            update_option('yht_plugin_version', $this->version);
        }
    }

    /**
     * Remove plugin-related transients
     */
    private function clear_transients() {
        global $wpdb;
        $pattern1 = $wpdb->esc_like('_transient_yht_') . '%';
        $pattern2 = $wpdb->esc_like('_transient_timeout_yht_') . '%';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $pattern1,
                $pattern2
            )
        );
    }

    /**
     * Default plugin settings.
     *
     * @return array
     */
    private function get_default_settings() {
        return array(
            'notify_email'    => get_option('admin_email'),
            'brevo_api_key'   => '',
            'ga4_id'          => '',
            'wc_deposit_pct'  => '20',
            'wc_price_per_pax'=> '80',
        );
    }

    /**
     * Get plugin settings
     * @return array
     */
    public function get_settings() {
        if (empty($this->settings)) {
            $this->settings = get_option(YHT_OPT, array());
            $this->settings = wp_parse_args($this->settings, $this->get_default_settings());
        }
        return $this->settings;
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        add_option(YHT_OPT, $this->get_default_settings());
        update_option('yht_plugin_version', $this->version);
        $this->clear_transients();

        // Create custom post types and taxonomies for rewrite rules
        $this->load_post_types();
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}