<?php
/**
 * Main plugin bootstrap file
 * 
 * @package YourHiddenTrip
 * @version 6.3.0 // x-release-please-version
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
    public $version = '6.3.0'; // x-release-please-version
    
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
                'includes/seo/',
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
        add_action('admin_notices', array($this, 'check_dependencies'));
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
        $this->load_seo();
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
            
            // Load enhanced analytics dashboard
            new YHT_Advanced_Analytics();
            
            // Load QR code manager
            new YHT_QR_Manager();
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
        
        // Load client portal (works both admin and frontend)
        new YHT_Client_Portal();
    }
    
    /**
     * Load utility classes
     */
    private function load_utilities() {
        // Load availability tracker
        new YHT_Availability_Tracker();
        
        // Load enhanced logger
        YHT_Logger::get_instance();
        
        // Utility classes are loaded on demand via autoloader
    }
    
    /**
     * Load analytics module
     */
    private function load_analytics() {
        new YHT_Analytics();
        
        // Load Google Analytics 4 integration
        new YHT_Google_Analytics_4();
    }
    
    /**
     * Load security module
     */
    private function load_security() {
        new YHT_Security();
        // Load enhanced security headers
        new YHT_Security_Headers();
    }
    
    /**
     * Load SEO module
     */
    private function load_seo() {
        new YHT_SEO_Manager();
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
     * Check plugin dependencies and show admin notices
     */
    public function check_dependencies() {
        // Check if we're on an admin page
        if (!is_admin()) {
            return;
        }
        
        // Skip if user cannot manage plugins
        if (!current_user_can('manage_plugins')) {
            return;
        }
        
        // Check for vendor dependencies
        $vendor_missing = !$this->has_vendor_dependencies();
        
        if ($vendor_missing) {
            $this->show_dependency_notice();
        }
    }
    
    /**
     * Check if vendor dependencies are available
     */
    private function has_vendor_dependencies() {
        $vendor_autoload = YHT_PLUGIN_PATH . 'vendor/autoload.php';
        $dompdf_direct = YHT_PLUGIN_PATH . 'vendor/dompdf/autoload.inc.php';
        
        if (file_exists($vendor_autoload)) {
            require_once $vendor_autoload;
            return class_exists('\\Dompdf\\Dompdf');
        }
        
        if (file_exists($dompdf_direct)) {
            require_once $dompdf_direct;
            return class_exists('\\Dompdf\\Dompdf');
        }
        
        return false;
    }
    
    /**
     * Show dependency missing notice
     */
    private function show_dependency_notice() {
        $class = 'notice notice-error';
        $message = __('<strong>Your Hidden Trip Planner:</strong> Missing required dependencies for PDF generation.', 'your-hidden-trip');
        $details = __('It looks like you downloaded the source code directly from GitHub. Please download the pre-built distribution package from the <a href="https://github.com/franpass87/Your-Hidden-Trip-Planner/releases" target="_blank">Releases page</a> instead, which includes all necessary dependencies.', 'your-hidden-trip');
        $composer_info = __('For developers: Run <code>composer install</code> in the plugin directory to install dependencies.', 'your-hidden-trip');
        
        printf('<div class="%1$s"><p>%2$s</p><p>%3$s</p><p><em>%4$s</em></p></div>', 
            esc_attr($class), 
            wp_kses_post($message), 
            wp_kses_post($details),
            wp_kses_post($composer_info)
        );
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