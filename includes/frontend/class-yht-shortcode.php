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
        add_action('wp_head', array($this, 'add_pwa_support'), 6);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    /**
     * Enqueue frontend CSS and JS assets
     */
    public function enqueue_frontend_assets() {
        // Only enqueue on pages that use the shortcode or if it's a known page
        if (is_page() && (has_shortcode(get_post()->post_content, 'yourhiddentrip_builder') || $this->should_load_assets())) {
            // Enqueue enhanced CSS
            wp_enqueue_style(
                'yht-frontend-enhanced',
                YHT_PLUGIN_URL . 'assets/css/yht-frontend.css',
                array(),
                YHT_VER,
                'all'
            );
            
            // Enqueue search filters CSS
            wp_enqueue_style(
                'yht-search-filters',
                YHT_PLUGIN_URL . 'assets/css/yht-search-filters.css',
                array('yht-frontend-enhanced'),
                YHT_VER,
                'all'
            );
            
            // Enqueue enhanced JavaScript
            wp_enqueue_script(
                'yht-enhancer',
                YHT_PLUGIN_URL . 'assets/js/yht-enhancer.js',
                array('jquery'),
                YHT_VER,
                true
            );
            
            // Enqueue competitive enhancement CSS
            wp_enqueue_style(
                'yht-competitive-enhancements',
                YHT_PLUGIN_URL . 'assets/css/yht-competitive-enhancements.css',
                array('yht-frontend-enhanced'),
                YHT_VER,
                'all'
            );
            
            // Enqueue AI recommendations system
            wp_enqueue_script(
                'yht-ai-recommendations',
                YHT_PLUGIN_URL . 'assets/js/yht-ai-recommendations.js',
                array('yht-enhancer'),
                YHT_VER,
                true
            );
            
            // Enqueue gamification system
            wp_enqueue_script(
                'yht-gamification',
                YHT_PLUGIN_URL . 'assets/js/yht-gamification.js',
                array('yht-enhancer'),
                YHT_VER,
                true
            );
            
            // Enqueue mobile enhancements
            wp_enqueue_style(
                'yht-mobile-enhancements',
                YHT_PLUGIN_URL . 'assets/css/yht-mobile-enhancements.css',
                array('yht-competitive-enhancements'),
                YHT_VER,
                'all'
            );
            
            wp_enqueue_script(
                'yht-mobile-enhancer',
                YHT_PLUGIN_URL . 'assets/js/yht-mobile-enhancer.js',
                array('yht-enhancer'),
                YHT_VER,
                true
            );
            
            // Enqueue search functionality
            wp_enqueue_script(
                'yht-search',
                YHT_PLUGIN_URL . 'assets/js/yht-search.js',
                array('yht-enhancer'),
                YHT_VER,
                true
            );
            
            // Localize script with settings
            $current_lang = 'it'; // Default fallback
            try {
                if (class_exists('YHT_Plugin')) {
                    $plugin_instance = YHT_Plugin::get_instance();
                    if ($plugin_instance && method_exists($plugin_instance, 'get_current_language')) {
                        $current_lang = $plugin_instance->get_current_language();
                    }
                }
            } catch (Exception $e) {
                // Use default language if plugin instance fails
                $current_lang = 'it';
            }
            
            wp_localize_script('yht-enhancer', 'yhtSettings', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('yht_nonce'),
                'restUrl' => rest_url('yourhiddentrip/v1/'),
                'currentLang' => $current_lang,
                'strings' => array(
                    'loading' => __('Caricamento...', 'your-hidden-trip'),
                    'error' => __('Si è verificato un errore', 'your-hidden-trip'),
                    'success' => __('Operazione completata con successo', 'your-hidden-trip'),
                    'addedToWishlist' => __('Aggiunto ai preferiti!', 'your-hidden-trip'),
                    'removedFromWishlist' => __('Rimosso dai preferiti', 'your-hidden-trip'),
                    'linkCopied' => __('Link copiato negli appunti!', 'your-hidden-trip'),
                    'searching' => __('Ricerca in corso...', 'your-hidden-trip'),
                    'noResults' => __('Nessun risultato trovato', 'your-hidden-trip'),
                    'filtersCleared' => __('Filtri rimossi', 'your-hidden-trip'),
                )
            ));
        }
    }
    
    /**
     * Check if assets should be loaded on this page
     */
    private function should_load_assets() {
        // Load on specific pages that might use the trip builder
        global $post;
        if (!$post) return false;
        
        // Check if post content contains our shortcode or specific keywords
        $content = get_post_field('post_content', $post->ID);
        return (
            strpos($content, 'yourhiddentrip_builder') !== false ||
            strpos($content, 'trip-builder') !== false ||
            strpos($content, 'hidden-trip') !== false
        );
    }
    
    /**
     * Render the trip builder shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'template' => 'enhanced', // Use enhanced template by default
            'theme' => 'auto', // auto, light, dark
        ), $atts, 'yourhiddentrip_builder');
        
        // Pass current language to the view for WPML compatibility  
        $current_lang = 'it'; // Default fallback
        try {
            if (class_exists('YHT_Plugin')) {
                $plugin_instance = YHT_Plugin::get_instance();
                if ($plugin_instance && method_exists($plugin_instance, 'get_current_language')) {
                    $current_lang = $plugin_instance->get_current_language();
                }
            }
        } catch (Exception $e) {
            // Use default language if plugin instance fails
            $current_lang = 'it';
        }
        
        ob_start();
        
        // Choose template based on attribute
        if ($atts['template'] === 'enhanced') {
            include YHT_PLUGIN_PATH . 'includes/frontend/views/trip-builder-enhanced.php';
        } else {
            // Fallback to original template
            include YHT_PLUGIN_PATH . 'includes/frontend/views/trip-builder.php';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Add PWA and mobile optimization support to wp_head
     */
    public function add_pwa_support() {
        // Only add PWA support if the shortcode is being used
        if (is_page() && (has_shortcode(get_post()->post_content, 'yourhiddentrip_builder') || $this->should_load_assets())) {
            // Add PWA manifest
            echo '<link rel="manifest" href="' . YHT_PLUGIN_URL . 'assets/manifest.json">' . "\n";
            
            // Add theme color for mobile browsers
            echo '<meta name="theme-color" content="#10b981">' . "\n";
            
            // Add Apple touch icon
            echo '<link rel="apple-touch-icon" href="' . YHT_PLUGIN_URL . 'assets/images/icon-192.png">' . "\n";
            
            // Add PWA meta tags
            echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
            echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
            echo '<meta name="apple-mobile-web-app-title" content="YHT Planner">' . "\n";
            
            // Add Windows tile support
            echo '<meta name="msapplication-TileColor" content="#10b981">' . "\n";
            echo '<meta name="msapplication-TileImage" content="' . YHT_PLUGIN_URL . 'assets/images/icon-144.png">' . "\n";
            
            // Add mobile viewport optimization
            echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, maximum-scale=2.0">' . "\n";
        }
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