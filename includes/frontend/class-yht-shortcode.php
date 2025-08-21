<?php
/**
 * Handle Frontend Shortcode
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_Shortcode {
    
    public function __construct() {
        add_shortcode('yourhiddentrip_builder', array($this, 'render_shortcode'));
        add_action('wp_head', array($this, 'add_ga4_support'), 5);
    }
    
    /**
     * Render the trip builder shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(), $atts, 'yourhiddentrip_builder');
        
        ob_start();
        include YHT_PLUGIN_PATH . 'includes/frontend/views/trip-builder.php';
        return ob_get_clean();
    }
    
    /**
     * Add GA4 support to wp_head
     */
    public function add_ga4_support() {
        $settings = YHT_Plugin::get_instance()->get_settings();
        if(!empty($settings['ga4_id'])) {
            echo "<script>window.dataLayer=window.dataLayer||[];</script>\n";
        }
    }
}