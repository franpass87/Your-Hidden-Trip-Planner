<?php
/**
 * PWA Manager - Progressive Web App functionality
 * 
 * @package YourHiddenTrip
 * @version 6.3.0
 */

if (!defined('ABSPATH')) exit;

/**
 * PWA Manager class
 */
class YHT_PWA_Manager {
    
    /**
     * Initialize PWA features
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_pwa_scripts'));
        add_action('wp_head', array($this, 'add_manifest_link'));
        add_action('init', array($this, 'register_service_worker_endpoint'));
        add_action('template_redirect', array($this, 'serve_service_worker'));
        add_action('wp_footer', array($this, 'add_pwa_install_prompt'));
        add_filter('wp_headers', array($this, 'add_pwa_headers'));
    }

    /**
     * Enqueue PWA scripts and styles
     */
    public function enqueue_pwa_scripts() {
        wp_enqueue_script(
            'yht-pwa-main',
            YHT_PLUGIN_URL . 'assets/js/pwa-main.js',
            array('jquery'),
            YHT_VER,
            true
        );

        wp_enqueue_script(
            'yht-filters-advanced',
            YHT_PLUGIN_URL . 'assets/js/filters-advanced.js',
            array('jquery', 'yht-pwa-main'),
            YHT_VER,
            true
        );

        wp_enqueue_style(
            'yht-pwa-styles',
            YHT_PLUGIN_URL . 'assets/css/pwa-styles.css',
            array(),
            YHT_VER
        );

        // Localize script with PWA data
        wp_localize_script('yht-pwa-main', 'yhtPWA', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yht_pwa_nonce'),
            'manifest_url' => home_url('/?yht-manifest=1'),
            'sw_url' => home_url('/?yht-sw=1'),
            'app_name' => get_bloginfo('name') . ' - Trip Planner',
            'app_description' => 'Plan your hidden trips offline',
            'theme_color' => get_theme_mod('header_textcolor', '#2196F3'),
            'background_color' => '#ffffff',
            'start_url' => home_url('/'),
            'scope' => home_url('/'),
            'display' => 'standalone'
        ));
    }

    /**
     * Add manifest link to head
     */
    public function add_manifest_link() {
        echo '<link rel="manifest" href="' . home_url('/?yht-manifest=1') . '">' . "\n";
        echo '<meta name="theme-color" content="' . get_theme_mod('header_textcolor', '#2196F3') . '">' . "\n";
        echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
        echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
        echo '<meta name="apple-mobile-web-app-title" content="' . esc_attr(get_bloginfo('name')) . ' Trip Planner">' . "\n";
        echo '<link rel="apple-touch-icon" href="' . YHT_PLUGIN_URL . 'assets/images/icon-192x192.png">' . "\n";
    }

    /**
     * Register service worker endpoint
     */
    public function register_service_worker_endpoint() {
        add_rewrite_rule('^yht-sw\.js$', 'index.php?yht-sw=1', 'top');
        add_rewrite_rule('^manifest\.json$', 'index.php?yht-manifest=1', 'top');
    }

    /**
     * Serve service worker and manifest
     */
    public function serve_service_worker() {
        if (get_query_var('yht-sw')) {
            $this->serve_service_worker_file();
            exit;
        }
        
        if (get_query_var('yht-manifest')) {
            $this->serve_manifest_file();
            exit;
        }
    }

    /**
     * Serve service worker JavaScript file
     */
    private function serve_service_worker_file() {
        header('Content-Type: application/javascript');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $cache_version = 'yht-v' . YHT_VER;
        $assets_to_cache = array(
            home_url('/'),
            YHT_PLUGIN_URL . 'assets/css/pwa-styles.css',
            YHT_PLUGIN_URL . 'assets/js/pwa-main.js',
            YHT_PLUGIN_URL . 'assets/js/filters-advanced.js',
            YHT_PLUGIN_URL . 'assets/images/icon-192x192.png',
            YHT_PLUGIN_URL . 'assets/images/icon-512x512.png'
        );

        ?>
const CACHE_NAME = '<?php echo $cache_version; ?>';
const urlsToCache = <?php echo json_encode($assets_to_cache); ?>;

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                // Cache hit - return response
                if (response) {
                    return response;
                }

                return fetch(event.request).then(
                    function(response) {
                        // Check if we received a valid response
                        if(!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }

                        // Clone the response
                        var responseToCache = response.clone();

                        caches.open(CACHE_NAME)
                            .then(function(cache) {
                                cache.put(event.request, responseToCache);
                            });

                        return response;
                    }
                );
            })
    );
});

// Push notification handling
self.addEventListener('push', function(event) {
    if (event.data) {
        const data = event.data.json();
        const options = {
            body: data.body,
            icon: '<?php echo YHT_PLUGIN_URL . 'assets/images/icon-192x192.png'; ?>',
            badge: '<?php echo YHT_PLUGIN_URL . 'assets/images/badge-72x72.png'; ?>',
            vibrate: [100, 50, 100],
            data: {
                dateOfArrival: Date.now(),
                primaryKey: data.primaryKey || '1'
            },
            actions: [
                {
                    action: 'explore',
                    title: 'View Trip',
                    icon: '<?php echo YHT_PLUGIN_URL . 'assets/images/explore-icon.png'; ?>'
                },
                {
                    action: 'close',
                    title: 'Close',
                    icon: '<?php echo YHT_PLUGIN_URL . 'assets/images/close-icon.png'; ?>'
                }
            ]
        };

        event.waitUntil(
            self.registration.showNotification(data.title, options)
        );
    }
});

// Notification click handling
self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('<?php echo home_url('/trips/'); ?>')
        );
    }
});

// Background sync for offline actions
self.addEventListener('sync', function(event) {
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

function doBackgroundSync() {
    return new Promise(function(resolve, reject) {
        // Sync offline trip data when connection is restored
        resolve();
    });
}
        <?php
    }

    /**
     * Serve web app manifest file
     */
    private function serve_manifest_file() {
        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=86400');

        $manifest = array(
            'name' => get_bloginfo('name') . ' - Trip Planner',
            'short_name' => 'YHT Planner',
            'description' => 'Plan your hidden trips with advanced filters and collaboration',
            'start_url' => home_url('/'),
            'scope' => home_url('/'),
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'theme_color' => get_theme_mod('header_textcolor', '#2196F3'),
            'background_color' => '#ffffff',
            'icons' => array(
                array(
                    'src' => YHT_PLUGIN_URL . 'assets/images/icon-72x72.png',
                    'sizes' => '72x72',
                    'type' => 'image/png'
                ),
                array(
                    'src' => YHT_PLUGIN_URL . 'assets/images/icon-96x96.png',
                    'sizes' => '96x96',
                    'type' => 'image/png'
                ),
                array(
                    'src' => YHT_PLUGIN_URL . 'assets/images/icon-128x128.png',
                    'sizes' => '128x128',
                    'type' => 'image/png'
                ),
                array(
                    'src' => YHT_PLUGIN_URL . 'assets/images/icon-144x144.png',
                    'sizes' => '144x144',
                    'type' => 'image/png'
                ),
                array(
                    'src' => YHT_PLUGIN_URL . 'assets/images/icon-152x152.png',
                    'sizes' => '152x152',
                    'type' => 'image/png'
                ),
                array(
                    'src' => YHT_PLUGIN_URL . 'assets/images/icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png'
                ),
                array(
                    'src' => YHT_PLUGIN_URL . 'assets/images/icon-384x384.png',
                    'sizes' => '384x384',
                    'type' => 'image/png'
                ),
                array(
                    'src' => YHT_PLUGIN_URL . 'assets/images/icon-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png'
                )
            ),
            'categories' => array('travel', 'lifestyle', 'productivity'),
            'lang' => get_locale(),
            'dir' => is_rtl() ? 'rtl' : 'ltr'
        );

        echo json_encode($manifest, JSON_PRETTY_PRINT);
    }

    /**
     * Add PWA install prompt to footer
     */
    public function add_pwa_install_prompt() {
        ?>
        <div id="yht-pwa-install-prompt" style="display: none;" class="yht-pwa-prompt">
            <div class="yht-pwa-prompt-content">
                <img src="<?php echo YHT_PLUGIN_URL . 'assets/images/icon-72x72.png'; ?>" alt="App Icon">
                <div class="yht-pwa-prompt-text">
                    <h3><?php _e('Install Trip Planner App', 'your-hidden-trip'); ?></h3>
                    <p><?php _e('Get quick access to trip planning with our app!', 'your-hidden-trip'); ?></p>
                </div>
                <div class="yht-pwa-prompt-actions">
                    <button id="yht-pwa-install-button" class="button button-primary">
                        <?php _e('Install', 'your-hidden-trip'); ?>
                    </button>
                    <button id="yht-pwa-dismiss-button" class="button">
                        <?php _e('Not now', 'your-hidden-trip'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Add PWA-related headers
     */
    public function add_pwa_headers($headers) {
        if (get_query_var('yht-sw') || get_query_var('yht-manifest')) {
            $headers['Access-Control-Allow-Origin'] = '*';
        }
        return $headers;
    }

    /**
     * Register push notification endpoints
     */
    public function register_push_endpoints() {
        add_action('wp_ajax_yht_subscribe_push', array($this, 'handle_push_subscription'));
        add_action('wp_ajax_nopriv_yht_subscribe_push', array($this, 'handle_push_subscription'));
    }

    /**
     * Handle push notification subscription
     */
    public function handle_push_subscription() {
        check_ajax_referer('yht_pwa_nonce', 'nonce');

        $subscription = json_decode(stripslashes($_POST['subscription']), true);
        
        if ($subscription) {
            // Store subscription in database
            $user_id = get_current_user_id();
            if ($user_id) {
                update_user_meta($user_id, 'yht_push_subscription', $subscription);
            } else {
                // Store for anonymous users using session
                if (!session_id()) {
                    session_start();
                }
                $_SESSION['yht_push_subscription'] = $subscription;
            }

            wp_send_json_success(array('message' => 'Subscription saved'));
        } else {
            wp_send_json_error(array('message' => 'Invalid subscription data'));
        }
    }
}