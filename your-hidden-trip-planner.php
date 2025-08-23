
<?php
/**
 * Plugin Name: Your Hidden Trip Builder (v6.3 AI-Enhanced)
 * Description: Trip builder super competitivo per Tuscia & Umbria: CPT, tassonomie, importer, generatore tour da CPT, mappa inline (light), lead Brevo, export JSON/ICS/PDF (dompdf), WooCommerce package, share link, GA4 dataLayer, AI recommendations, gamification, PWA support.
 * Version: 6.3.1 // x-release-please-version
 * Author: YourHiddenTrip
 * Text Domain: your-hidden-trip
 */

if (!defined('ABSPATH')) exit;

// Plugin constants
define('YHT_VER', '6.3.1'); // x-release-please-version
define('YHT_SLUG', 'your-hidden-trip');
define('YHT_OPT', 'yht_settings');
define('YHT_PLUGIN_FILE', __FILE__);
define('YHT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('YHT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load main plugin class
require_once YHT_PLUGIN_PATH . 'includes/class-yht-plugin.php';

// Initialize the plugin
YHT_Plugin::get_instance();

// Backward compatibility - maintain global function for settings
if (!function_exists('yht_get_settings')) {
    function yht_get_settings() {
        return YHT_Plugin::get_instance()->get_settings();
    }
}
