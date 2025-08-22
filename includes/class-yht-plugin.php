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
        $this->init();
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
        require_once YHT_PLUGIN_PATH . 'includes/post-types/class-yht-post-types.php';
        new YHT_Post_Types();
    }
    
    /**
     * Load admin functionality
     */
    private function load_admin() {
        if (is_admin()) {
            require_once YHT_PLUGIN_PATH . 'includes/admin/class-yht-admin.php';
            new YHT_Admin();
            
            // Load backend management components
            require_once YHT_PLUGIN_PATH . 'includes/admin/class-yht-dashboard.php';
            new YHT_Dashboard();
            
            require_once YHT_PLUGIN_PATH . 'includes/admin/class-yht-customer-manager.php';
            new YHT_Customer_Manager();
            
            require_once YHT_PLUGIN_PATH . 'includes/admin/class-yht-system-health.php';
            new YHT_System_Health();
            
            require_once YHT_PLUGIN_PATH . 'includes/admin/class-yht-settings.php';
            
            require_once YHT_PLUGIN_PATH . 'includes/admin/class-yht-importer.php';
            
            require_once YHT_PLUGIN_PATH . 'includes/admin/class-yht-email-templates.php';
            new YHT_Email_Templates();
            
            require_once YHT_PLUGIN_PATH . 'includes/admin/class-yht-advanced-reports.php';
            new YHT_Advanced_Reports();
            
            require_once YHT_PLUGIN_PATH . 'includes/admin/class-yht-api-manager.php';
            new YHT_API_Manager();
            
            require_once YHT_PLUGIN_PATH . 'includes/admin/class-yht-backup-restore.php';
            new YHT_Backup_Restore();
            
            require_once YHT_PLUGIN_PATH . 'includes/admin/class-yht-user-roles.php';
            new YHT_User_Roles();
        }
    }
    
    /**
     * Load REST API endpoints
     */
    private function load_rest_api() {
        require_once YHT_PLUGIN_PATH . 'includes/rest-api/class-yht-rest-controller.php';
        new YHT_Rest_Controller();
    }
    
    /**
     * Load frontend functionality
     */
    private function load_frontend() {
        if (!is_admin()) {
            require_once YHT_PLUGIN_PATH . 'includes/frontend/class-yht-shortcode.php';
            new YHT_Shortcode();
            
            require_once YHT_PLUGIN_PATH . 'includes/frontend/class-yht-reviews.php';
            new YHT_Reviews();
        }
    }
    
    /**
     * Load utility classes
     */
    private function load_utilities() {
        require_once YHT_PLUGIN_PATH . 'includes/utilities/class-yht-helpers.php';
    }
    
    /**
     * Load analytics module
     */
    private function load_analytics() {
        require_once YHT_PLUGIN_PATH . 'includes/analytics/class-yht-analytics.php';
        new YHT_Analytics();
    }
    
    /**
     * Load security module
     */
    private function load_security() {
        require_once YHT_PLUGIN_PATH . 'includes/security/class-yht-security.php';
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
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_yht_%'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_yht_%'));
    }

    /**
     * Get plugin settings
     * @return array
     */
    public function get_settings() {
        if (empty($this->settings)) {
            $this->settings = get_option(YHT_OPT, array());
            $defaults = array(
                'notify_email'    => get_option('admin_email'),
                'brevo_api_key'   => '',
                'ga4_id'          => '',
                'wc_deposit_pct'  => '20',
                'wc_price_per_pax'=> '80',
            );
            $this->settings = wp_parse_args($this->settings, $defaults);
        }
        return $this->settings;
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        $defaults = array(
            'notify_email'    => get_option('admin_email'),
            'brevo_api_key'   => '',
            'ga4_id'          => '',
            'wc_deposit_pct'  => '20',
            'wc_price_per_pax'=> '80',
        );
        add_option(YHT_OPT, $defaults);
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