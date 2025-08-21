<?php
/**
 * Main plugin bootstrap file
 * 
 * @package YourHiddenTrip
 * @version 6.2
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
    public $version = '6.2';
    
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
        add_action('init', array($this, 'load_components'));
        register_activation_hook(YHT_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(YHT_PLUGIN_FILE, array($this, 'deactivate'));
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
        }
    }
    
    /**
     * Load utility classes
     */
    private function load_utilities() {
        require_once YHT_PLUGIN_PATH . 'includes/utilities/class-yht-helpers.php';
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