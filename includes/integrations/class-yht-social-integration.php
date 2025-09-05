<?php
/**
 * Social Integration Manager
 * 
 * @package YourHiddenTrip
 * @version 6.3.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Social Integration Manager class
 */
class YHT_Social_Integration {
    
    /**
     * Initialize social integration
     */
    public function __construct() {
        add_action('init', array($this, 'init_social_integration'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_social_scripts'));
        add_action('wp_head', array($this, 'add_open_graph_meta'));
        add_action('wp_head', array($this, 'add_twitter_card_meta'));
        add_action('wp_head', array($this, 'add_schema_markup'));
        
        // Social sharing hooks
        add_action('wp_ajax_yht_share_trip', array($this, 'handle_trip_share'));
        add_action('wp_ajax_nopriv_yht_share_trip', array($this, 'handle_trip_share'));
        add_action('wp_ajax_yht_generate_share_image', array($this, 'generate_share_image'));
        
        // Shortcodes
        add_shortcode('yht_social_share', array($this, 'social_share_shortcode'));
        add_shortcode('yht_social_login', array($this, 'social_login_shortcode'));
        
        // Content filters
        add_filter('the_content', array($this, 'add_social_share_to_content'));
        
        // Admin hooks
        add_action('add_meta_boxes', array($this, 'add_social_meta_box'));
        add_action('save_post', array($this, 'save_social_meta'));
        
        // Social login integration
        add_action('wp_ajax_yht_social_login', array($this, 'handle_social_login'));
        add_action('wp_ajax_nopriv_yht_social_login', array($this, 'handle_social_login'));
        
        // API integrations
        add_action('init', array($this, 'init_social_apis'));
        
        // Review and rating integration
        add_action('wp_ajax_yht_submit_social_review', array($this, 'submit_social_review'));
        add_action('wp_ajax_nopriv_yht_submit_social_review', array($this, 'submit_social_review'));
    }

    /**
     * Initialize social integration
     */
    public function init_social_integration() {
        // Register social share tracking
        add_rewrite_rule('^share/([^/]+)/?', 'index.php?yht_share_redirect=1&share_token=$matches[1]', 'top');
        add_query_var('yht_share_redirect');
        add_query_var('share_token');
        
        add_action('template_redirect', array($this, 'handle_share_redirect'));
        
        // Social media platform settings
        $this->platforms = array(
            'facebook' => array(
                'name' => 'Facebook',
                'icon' => 'fab fa-facebook-f',
                'color' => '#1877F2',
                'enabled' => get_option('yht_social_facebook_enabled', true)
            ),
            'twitter' => array(
                'name' => 'Twitter',
                'icon' => 'fab fa-twitter',
                'color' => '#1DA1F2',
                'enabled' => get_option('yht_social_twitter_enabled', true)
            ),
            'instagram' => array(
                'name' => 'Instagram',
                'icon' => 'fab fa-instagram',
                'color' => '#E4405F',
                'enabled' => get_option('yht_social_instagram_enabled', true)
            ),
            'whatsapp' => array(
                'name' => 'WhatsApp',
                'icon' => 'fab fa-whatsapp',
                'color' => '#25D366',
                'enabled' => get_option('yht_social_whatsapp_enabled', true)
            ),
            'linkedin' => array(
                'name' => 'LinkedIn',
                'icon' => 'fab fa-linkedin-in',
                'color' => '#0A66C2',
                'enabled' => get_option('yht_social_linkedin_enabled', true)
            ),
            'pinterest' => array(
                'name' => 'Pinterest',
                'icon' => 'fab fa-pinterest-p',
                'color' => '#BD081C',
                'enabled' => get_option('yht_social_pinterest_enabled', true)
            ),
            'telegram' => array(
                'name' => 'Telegram',
                'icon' => 'fab fa-telegram-plane',
                'color' => '#0088CC',
                'enabled' => get_option('yht_social_telegram_enabled', true)
            )
        );
    }

    /**
     * Enqueue social scripts
     */
    public function enqueue_social_scripts() {
        wp_enqueue_script(
            'yht-social-integration',
            YHT_PLUGIN_URL . 'assets/js/social-integration.js',
            array('jquery'),
            YHT_VER,
            true
        );

        wp_enqueue_style(
            'yht-social-styles',
            YHT_PLUGIN_URL . 'assets/css/social-styles.css',
            array(),
            YHT_VER
        );

        // Load Font Awesome for social icons
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            array(),
            '6.0.0'
        );

        wp_localize_script('yht-social-integration', 'yhtSocial', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yht_social_nonce'),
            'platforms' => $this->platforms,
            'share_url' => home_url('/share/'),
            'facebook_app_id' => get_option('yht_facebook_app_id', ''),
            'google_client_id' => get_option('yht_google_client_id', ''),
            'strings' => array(
                'share_success' => __('Shared successfully!', 'your-hidden-trip'),
                'share_error' => __('Failed to share', 'your-hidden-trip'),
                'login_success' => __('Login successful', 'your-hidden-trip'),
                'login_error' => __('Login failed', 'your-hidden-trip'),
                'copied_to_clipboard' => __('Link copied to clipboard!', 'your-hidden-trip')
            )
        ));
    }

    /**
     * Add Open Graph meta tags
     */
    public function add_open_graph_meta() {
        if (!is_singular('trip')) {
            return;
        }

        global $post;
        $trip_data = $this->get_trip_social_data($post->ID);
        
        ?>
        <meta property="og:type" content="website">
        <meta property="og:title" content="<?php echo esc_attr($trip_data['title']); ?>">
        <meta property="og:description" content="<?php echo esc_attr($trip_data['description']); ?>">
        <meta property="og:url" content="<?php echo esc_url($trip_data['url']); ?>">
        <meta property="og:image" content="<?php echo esc_url($trip_data['image']); ?>">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:site_name" content="<?php echo esc_attr(get_bloginfo('name')); ?>">
        <meta property="og:locale" content="<?php echo esc_attr(get_locale()); ?>">
        
        <!-- Trip-specific Open Graph -->
        <meta property="trip:duration" content="<?php echo esc_attr($trip_data['duration']); ?>">
        <meta property="trip:difficulty" content="<?php echo esc_attr($trip_data['difficulty']); ?>">
        <meta property="trip:price" content="<?php echo esc_attr($trip_data['price']); ?>">
        <meta property="trip:location" content="<?php echo esc_attr($trip_data['location']); ?>">
        
        <!-- Facebook App ID -->
        <?php if ($facebook_app_id = get_option('yht_facebook_app_id')): ?>
        <meta property="fb:app_id" content="<?php echo esc_attr($facebook_app_id); ?>">
        <?php endif; ?>
        <?php
    }

    /**
     * Add Twitter Card meta tags
     */
    public function add_twitter_card_meta() {
        if (!is_singular('trip')) {
            return;
        }

        global $post;
        $trip_data = $this->get_trip_social_data($post->ID);
        $twitter_handle = get_option('yht_twitter_handle', '');
        
        ?>
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?php echo esc_attr($trip_data['title']); ?>">
        <meta name="twitter:description" content="<?php echo esc_attr($trip_data['description']); ?>">
        <meta name="twitter:image" content="<?php echo esc_url($trip_data['image']); ?>">
        <?php if ($twitter_handle): ?>
        <meta name="twitter:site" content="@<?php echo esc_attr($twitter_handle); ?>">
        <meta name="twitter:creator" content="@<?php echo esc_attr($twitter_handle); ?>">
        <?php endif; ?>
        <?php
    }

    /**
     * Add Schema markup for social sharing
     */
    public function add_schema_markup() {
        if (!is_singular('trip')) {
            return;
        }

        global $post;
        $trip_data = $this->get_trip_social_data($post->ID);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'TouristTrip',
            'name' => $trip_data['title'],
            'description' => $trip_data['description'],
            'url' => $trip_data['url'],
            'image' => array(
                '@type' => 'ImageObject',
                'url' => $trip_data['image'],
                'width' => 1200,
                'height' => 630
            ),
            'offers' => array(
                '@type' => 'Offer',
                'price' => $trip_data['price'],
                'priceCurrency' => 'EUR',
                'availability' => 'https://schema.org/InStock'
            ),
            'duration' => $trip_data['duration'],
            'touristType' => $trip_data['difficulty'],
            'itinerary' => array(
                '@type' => 'ItemList',
                'name' => 'Trip Itinerary'
            )
        );

        if (!empty($trip_data['location'])) {
            $schema['location'] = array(
                '@type' => 'Place',
                'name' => $trip_data['location']
            );
        }

        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }

    /**
     * Get trip social data
     */
    private function get_trip_social_data($trip_id) {
        $trip = get_post($trip_id);
        
        // Custom social title and description
        $social_title = get_post_meta($trip_id, '_social_title', true);
        $social_description = get_post_meta($trip_id, '_social_description', true);
        $social_image = get_post_meta($trip_id, '_social_image', true);
        
        $title = $social_title ?: $trip->post_title;
        $description = $social_description ?: wp_trim_words(strip_tags($trip->post_content), 30);
        
        // Get social image or fallback to featured image
        if ($social_image) {
            $image = wp_get_attachment_image_url($social_image, 'large');
        } else {
            $image = get_the_post_thumbnail_url($trip_id, 'large');
        }
        
        if (!$image) {
            $image = YHT_PLUGIN_URL . 'assets/images/default-share-image.jpg';
        }
        
        return array(
            'title' => $title,
            'description' => $description,
            'url' => get_permalink($trip_id),
            'image' => $image,
            'duration' => get_post_meta($trip_id, '_trip_duration', true),
            'difficulty' => get_post_meta($trip_id, '_trip_difficulty', true),
            'price' => get_post_meta($trip_id, '_trip_price', true),
            'location' => get_post_meta($trip_id, '_trip_location', true)
        );
    }

    /**
     * Add social meta box to trip posts
     */
    public function add_social_meta_box() {
        add_meta_box(
            'yht_social_settings',
            __('Social Media Settings', 'your-hidden-trip'),
            array($this, 'social_meta_box_callback'),
            'trip',
            'normal',
            'default'
        );
    }

    /**
     * Social meta box callback
     */
    public function social_meta_box_callback($post) {
        wp_nonce_field('yht_social_meta', 'yht_social_nonce');
        
        $social_title = get_post_meta($post->ID, '_social_title', true);
        $social_description = get_post_meta($post->ID, '_social_description', true);
        $social_image = get_post_meta($post->ID, '_social_image', true);
        $auto_share = get_post_meta($post->ID, '_social_auto_share', true);
        $share_platforms = get_post_meta($post->ID, '_social_share_platforms', true) ?: array();
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="social_title"><?php _e('Social Title:', 'your-hidden-trip'); ?></label></th>
                <td>
                    <input type="text" id="social_title" name="social_title" 
                           value="<?php echo esc_attr($social_title); ?>" class="large-text">
                    <p class="description"><?php _e('Custom title for social media sharing (leave empty to use post title)', 'your-hidden-trip'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="social_description"><?php _e('Social Description:', 'your-hidden-trip'); ?></label></th>
                <td>
                    <textarea id="social_description" name="social_description" rows="3" class="large-text"><?php echo esc_textarea($social_description); ?></textarea>
                    <p class="description"><?php _e('Custom description for social media sharing (leave empty to use excerpt)', 'your-hidden-trip'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="social_image"><?php _e('Social Image:', 'your-hidden-trip'); ?></label></th>
                <td>
                    <input type="hidden" id="social_image" name="social_image" value="<?php echo esc_attr($social_image); ?>">
                    <div id="social_image_preview">
                        <?php if ($social_image): ?>
                            <img src="<?php echo wp_get_attachment_image_url($social_image, 'medium'); ?>" style="max-width: 300px;">
                        <?php endif; ?>
                    </div>
                    <button type="button" class="button" id="upload_social_image"><?php _e('Select Image', 'your-hidden-trip'); ?></button>
                    <button type="button" class="button" id="remove_social_image"><?php _e('Remove', 'your-hidden-trip'); ?></button>
                    <p class="description"><?php _e('Recommended size: 1200x630px (leave empty to use featured image)', 'your-hidden-trip'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Auto-share to:', 'your-hidden-trip'); ?></label></th>
                <td>
                    <?php foreach ($this->platforms as $platform => $config): ?>
                        <?php if ($config['enabled']): ?>
                        <label>
                            <input type="checkbox" name="social_share_platforms[]" 
                                   value="<?php echo esc_attr($platform); ?>" 
                                   <?php checked(in_array($platform, $share_platforms)); ?>>
                            <?php echo esc_html($config['name']); ?>
                        </label><br>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <p class="description"><?php _e('Automatically share when trip is published', 'your-hidden-trip'); ?></p>
                </td>
            </tr>
        </table>

        <script>
        jQuery(document).ready(function($) {
            var mediaUploader;
            
            $('#upload_social_image').click(function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e('Choose Social Image', 'your-hidden-trip'); ?>',
                    button: {
                        text: '<?php _e('Choose Image', 'your-hidden-trip'); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#social_image').val(attachment.id);
                    $('#social_image_preview').html('<img src="' + attachment.url + '" style="max-width: 300px;">');
                });
                
                mediaUploader.open();
            });
            
            $('#remove_social_image').click(function(e) {
                e.preventDefault();
                $('#social_image').val('');
                $('#social_image_preview').empty();
            });
        });
        </script>
        <?php
    }

    /**
     * Save social meta data
     */
    public function save_social_meta($post_id) {
        if (!isset($_POST['yht_social_nonce']) || !wp_verify_nonce($_POST['yht_social_nonce'], 'yht_social_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = array(
            'social_title' => '_social_title',
            'social_description' => '_social_description',
            'social_image' => '_social_image'
        );

        foreach ($fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
            }
        }

        // Handle array fields
        if (isset($_POST['social_share_platforms'])) {
            $platforms = array_map('sanitize_text_field', $_POST['social_share_platforms']);
            update_post_meta($post_id, '_social_share_platforms', $platforms);
        } else {
            delete_post_meta($post_id, '_social_share_platforms');
        }
    }

    /**
     * Handle trip sharing
     */
    public function handle_trip_share() {
        check_ajax_referer('yht_social_nonce', 'nonce');

        $trip_id = intval($_POST['trip_id']);
        $platform = sanitize_text_field($_POST['platform']);
        $custom_message = sanitize_textarea_field($_POST['custom_message']);

        if (!$trip_id || !$platform) {
            wp_send_json_error(array('message' => 'Missing required data'));
        }

        // Generate share link with tracking
        $share_token = $this->generate_share_token($trip_id, $platform);
        $share_url = home_url("/share/{$share_token}");

        // Log the share action
        $this->log_share_action($trip_id, $platform, get_current_user_id());

        // Get sharing URL for the platform
        $sharing_url = $this->get_platform_sharing_url($platform, $trip_id, $share_url, $custom_message);

        wp_send_json_success(array(
            'sharing_url' => $sharing_url,
            'share_url' => $share_url
        ));
    }

    /**
     * Generate share token
     */
    private function generate_share_token($trip_id, $platform) {
        $token = wp_generate_uuid4();
        
        // Store share data
        set_transient("yht_share_{$token}", array(
            'trip_id' => $trip_id,
            'platform' => $platform,
            'timestamp' => time(),
            'user_id' => get_current_user_id()
        ), WEEK_IN_SECONDS);

        return $token;
    }

    /**
     * Handle share redirect
     */
    public function handle_share_redirect() {
        if (!get_query_var('yht_share_redirect')) {
            return;
        }

        $share_token = get_query_var('share_token');
        if (!$share_token) {
            wp_die('Invalid share link');
        }

        $share_data = get_transient("yht_share_{$share_token}");
        if (!$share_data) {
            wp_die('Share link expired');
        }

        // Track the click
        $this->track_share_click($share_data);

        // Redirect to the actual trip
        wp_redirect(get_permalink($share_data['trip_id']));
        exit;
    }

    /**
     * Get platform sharing URL
     */
    private function get_platform_sharing_url($platform, $trip_id, $share_url, $custom_message = '') {
        $trip_data = $this->get_trip_social_data($trip_id);
        $message = $custom_message ?: $trip_data['title'];

        switch ($platform) {
            case 'facebook':
                return 'https://www.facebook.com/sharer/sharer.php?' . http_build_query(array(
                    'u' => $share_url,
                    'quote' => $message
                ));

            case 'twitter':
                return 'https://twitter.com/intent/tweet?' . http_build_query(array(
                    'text' => $message,
                    'url' => $share_url,
                    'hashtags' => 'travel,hiddentrip'
                ));

            case 'whatsapp':
                return 'https://wa.me/?' . http_build_query(array(
                    'text' => $message . ' ' . $share_url
                ));

            case 'linkedin':
                return 'https://www.linkedin.com/sharing/share-offsite/?' . http_build_query(array(
                    'url' => $share_url
                ));

            case 'telegram':
                return 'https://t.me/share/url?' . http_build_query(array(
                    'url' => $share_url,
                    'text' => $message
                ));

            case 'pinterest':
                return 'https://pinterest.com/pin/create/button/?' . http_build_query(array(
                    'url' => $share_url,
                    'description' => $message,
                    'media' => $trip_data['image']
                ));

            default:
                return $share_url;
        }
    }

    /**
     * Log share action
     */
    private function log_share_action($trip_id, $platform, $user_id = 0) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'yht_social_shares';
        
        // Create table if it doesn't exist
        $this->create_shares_table();

        $wpdb->insert(
            $table_name,
            array(
                'trip_id' => $trip_id,
                'platform' => $platform,
                'user_id' => $user_id,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'shared_at' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%s', '%s', '%s')
        );
    }

    /**
     * Create shares tracking table
     */
    private function create_shares_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'yht_social_shares';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            trip_id bigint(20) NOT NULL,
            platform varchar(50) NOT NULL,
            user_id bigint(20) DEFAULT 0,
            ip_address varchar(45),
            user_agent text,
            shared_at datetime NOT NULL,
            clicked_at datetime NULL,
            clicks int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY trip_id (trip_id),
            KEY platform (platform),
            KEY shared_at (shared_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Track share click
     */
    private function track_share_click($share_data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'yht_social_shares';
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET clicks = clicks + 1, clicked_at = %s 
             WHERE trip_id = %d AND platform = %s AND user_id = %d 
             ORDER BY shared_at DESC LIMIT 1",
            current_time('mysql'),
            $share_data['trip_id'],
            $share_data['platform'],
            $share_data['user_id']
        ));
    }

    /**
     * Add social share to content
     */
    public function add_social_share_to_content($content) {
        if (!is_singular('trip') || !is_main_query()) {
            return $content;
        }

        $auto_add = get_option('yht_social_auto_add_shares', true);
        if (!$auto_add) {
            return $content;
        }

        $social_buttons = $this->get_social_share_buttons(get_the_ID());
        
        return $content . $social_buttons;
    }

    /**
     * Get social share buttons HTML
     */
    private function get_social_share_buttons($trip_id, $style = 'default') {
        $trip_data = $this->get_trip_social_data($trip_id);
        
        ob_start();
        ?>
        <div class="yht-social-share <?php echo esc_attr($style); ?>" data-trip-id="<?php echo esc_attr($trip_id); ?>">
            <h4><?php _e('Share this trip:', 'your-hidden-trip'); ?></h4>
            <div class="social-buttons">
                <?php foreach ($this->platforms as $platform => $config): ?>
                    <?php if ($config['enabled']): ?>
                    <button class="social-share-btn" 
                            data-platform="<?php echo esc_attr($platform); ?>"
                            style="background-color: <?php echo esc_attr($config['color']); ?>">
                        <i class="<?php echo esc_attr($config['icon']); ?>"></i>
                        <span><?php echo esc_html($config['name']); ?></span>
                    </button>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <button class="social-share-btn copy-link" data-platform="copy">
                    <i class="fas fa-link"></i>
                    <span><?php _e('Copy Link', 'your-hidden-trip'); ?></span>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Social share shortcode
     */
    public function social_share_shortcode($atts) {
        $atts = shortcode_atts(array(
            'trip_id' => get_the_ID(),
            'style' => 'default',
            'platforms' => 'all',
            'title' => __('Share this trip:', 'your-hidden-trip')
        ), $atts, 'yht_social_share');

        if (!$atts['trip_id']) {
            return '';
        }

        return $this->get_social_share_buttons($atts['trip_id'], $atts['style']);
    }

    /**
     * Social login shortcode
     */
    public function social_login_shortcode($atts) {
        $atts = shortcode_atts(array(
            'providers' => 'google,facebook',
            'redirect' => '',
            'style' => 'buttons'
        ), $atts, 'yht_social_login');

        if (is_user_logged_in()) {
            return '';
        }

        $providers = explode(',', $atts['providers']);
        
        ob_start();
        ?>
        <div class="yht-social-login <?php echo esc_attr($atts['style']); ?>">
            <h4><?php _e('Login with:', 'your-hidden-trip'); ?></h4>
            <?php if (in_array('google', $providers) && get_option('yht_google_client_id')): ?>
            <button class="social-login-btn google" data-provider="google">
                <i class="fab fa-google"></i>
                <?php _e('Login with Google', 'your-hidden-trip'); ?>
            </button>
            <?php endif; ?>
            
            <?php if (in_array('facebook', $providers) && get_option('yht_facebook_app_id')): ?>
            <button class="social-login-btn facebook" data-provider="facebook">
                <i class="fab fa-facebook-f"></i>
                <?php _e('Login with Facebook', 'your-hidden-trip'); ?>
            </button>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle social login
     */
    public function handle_social_login() {
        check_ajax_referer('yht_social_nonce', 'nonce');

        $provider = sanitize_text_field($_POST['provider']);
        $token = sanitize_text_field($_POST['token']);
        $user_data = json_decode(stripslashes($_POST['user_data']), true);

        if (!$provider || !$token || !$user_data) {
            wp_send_json_error(array('message' => 'Missing required data'));
        }

        // Verify token with social provider
        $verified_data = $this->verify_social_token($provider, $token);
        
        if (!$verified_data) {
            wp_send_json_error(array('message' => 'Invalid token'));
        }

        // Find or create user
        $user = $this->find_or_create_social_user($provider, $verified_data);
        
        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => $user->get_error_message()));
        }

        // Log in user
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        wp_send_json_success(array(
            'user_id' => $user->ID,
            'redirect_url' => home_url()
        ));
    }

    /**
     * Verify social token
     */
    private function verify_social_token($provider, $token) {
        switch ($provider) {
            case 'google':
                return $this->verify_google_token($token);
            case 'facebook':
                return $this->verify_facebook_token($token);
            default:
                return false;
        }
    }

    /**
     * Verify Google token
     */
    private function verify_google_token($token) {
        $url = 'https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=' . $token;
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['error'])) {
            return false;
        }

        return $data;
    }

    /**
     * Verify Facebook token
     */
    private function verify_facebook_token($token) {
        $app_id = get_option('yht_facebook_app_id');
        $app_secret = get_option('yht_facebook_app_secret');
        
        if (!$app_id || !$app_secret) {
            return false;
        }

        $url = "https://graph.facebook.com/me?access_token={$token}&fields=id,name,email";
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['error'])) {
            return false;
        }

        return $data;
    }

    /**
     * Find or create social user
     */
    private function find_or_create_social_user($provider, $social_data) {
        $social_id = $social_data['id'];
        $email = $social_data['email'] ?? '';
        
        // Check if user exists with this social ID
        $users = get_users(array(
            'meta_key' => "social_{$provider}_id",
            'meta_value' => $social_id,
            'number' => 1
        ));

        if (!empty($users)) {
            return $users[0];
        }

        // Check if user exists with this email
        if ($email) {
            $user = get_user_by('email', $email);
            if ($user) {
                // Link social account to existing user
                update_user_meta($user->ID, "social_{$provider}_id", $social_id);
                return $user;
            }
        }

        // Create new user
        $username = $this->generate_username_from_social($social_data);
        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'display_name' => $social_data['name'] ?? $username,
            'user_pass' => wp_generate_password(),
            'role' => 'subscriber'
        );

        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Store social data
        update_user_meta($user_id, "social_{$provider}_id", $social_id);
        update_user_meta($user_id, 'social_login_provider', $provider);

        return get_user_by('ID', $user_id);
    }

    /**
     * Generate username from social data
     */
    private function generate_username_from_social($social_data) {
        $base_username = sanitize_user($social_data['name'] ?? 'user');
        $username = $base_username;
        $counter = 1;

        while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Initialize social APIs
     */
    public function init_social_apis() {
        // This would initialize various social media APIs
        // Implementation depends on specific requirements
    }

    /**
     * Generate share image
     */
    public function generate_share_image() {
        check_ajax_referer('yht_social_nonce', 'nonce');

        $trip_id = intval($_POST['trip_id']);
        
        if (!$trip_id) {
            wp_send_json_error(array('message' => 'Invalid trip ID'));
        }

        // This would generate a custom share image with trip details
        // Using GD or ImageMagick to create an attractive social media image
        
        wp_send_json_success(array('image_url' => ''));
    }
}