<?php
/**
 * WooCommerce Integration Manager
 * 
 * @package YourHiddenTrip
 * @version 6.3.0
 */

if (!defined('ABSPATH')) exit;

/**
 * WooCommerce Integration Manager class
 */
class YHT_WooCommerce_Integration {
    
    /**
     * Initialize WooCommerce integration
     */
    public function __construct() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        add_action('init', array($this, 'init_integration'));
        add_action('woocommerce_loaded', array($this, 'load_integration'));
    }

    /**
     * Initialize integration hooks
     */
    public function init_integration() {
        // Product creation and management
        add_action('save_post_trip', array($this, 'sync_trip_to_product'));
        add_action('woocommerce_product_meta_boxes', array($this, 'add_trip_meta_box'));
        add_action('woocommerce_process_product_meta', array($this, 'save_trip_product_meta'));
        
        // Cart and checkout enhancements
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_trip_data_to_cart'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_trip_data_in_cart'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_trip_data_to_order'), 10, 4);
        
        // Advanced checkout fields
        add_filter('woocommerce_checkout_fields', array($this, 'add_trip_checkout_fields'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_trip_checkout_fields'));
        
        // Payment and booking confirmations
        add_action('woocommerce_order_status_completed', array($this, 'handle_trip_booking_confirmation'));
        add_action('woocommerce_payment_complete', array($this, 'handle_trip_payment_complete'));
        
        // Email notifications
        add_action('woocommerce_email_order_details', array($this, 'add_trip_details_to_email'), 20, 4);
        
        // Product display enhancements
        add_action('woocommerce_single_product_summary', array($this, 'add_trip_booking_form'), 25);
        add_filter('woocommerce_product_tabs', array($this, 'add_trip_details_tab'));
        
        // Pricing and availability
        add_filter('woocommerce_product_get_price', array($this, 'get_dynamic_trip_price'), 10, 2);
        add_filter('woocommerce_product_is_in_stock', array($this, 'check_trip_availability'), 10, 2);
        
        // REST API extensions
        add_action('rest_api_init', array($this, 'register_trip_api_fields'));
        
        // Admin enhancements
        add_filter('manage_product_posts_columns', array($this, 'add_trip_admin_columns'));
        add_action('manage_product_posts_custom_column', array($this, 'display_trip_admin_columns'), 10, 2);
        
        // Shortcodes
        add_shortcode('yht_trip_booking', array($this, 'trip_booking_shortcode'));
        add_shortcode('yht_trip_price', array($this, 'trip_price_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_yht_check_trip_availability', array($this, 'ajax_check_availability'));
        add_action('wp_ajax_nopriv_yht_check_trip_availability', array($this, 'ajax_check_availability'));
        add_action('wp_ajax_yht_get_trip_pricing', array($this, 'ajax_get_pricing'));
        add_action('wp_ajax_nopriv_yht_get_trip_pricing', array($this, 'ajax_get_pricing'));
    }

    /**
     * Load integration after WooCommerce is loaded
     */
    public function load_integration() {
        $this->create_trip_product_type();
        $this->setup_trip_categories();
    }

    /**
     * Create custom trip product type
     */
    public function create_trip_product_type() {
        // Include the product class
        require_once YHT_PLUGIN_PATH . 'includes/integrations/class-wc-product-trip.php';

        // Register the product type
        add_filter('product_type_selector', function($types) {
            $types['trip'] = __('Trip/Experience', 'your-hidden-trip');
            return $types;
        });

        add_filter('woocommerce_product_class', function($classname, $product_type) {
            if ($product_type === 'trip') {
                return 'WC_Product_Trip';
            }
            return $classname;
        }, 10, 2);
    }

    /**
     * Setup trip product categories
     */
    public function setup_trip_categories() {
        // Create trip categories if they don't exist
        $categories = array(
            'adventure-trips' => array(
                'name' => __('Adventure Trips', 'your-hidden-trip'),
                'description' => __('Exciting adventure experiences', 'your-hidden-trip')
            ),
            'cultural-tours' => array(
                'name' => __('Cultural Tours', 'your-hidden-trip'),
                'description' => __('Immersive cultural experiences', 'your-hidden-trip')
            ),
            'food-wine' => array(
                'name' => __('Food & Wine', 'your-hidden-trip'),
                'description' => __('Culinary and wine experiences', 'your-hidden-trip')
            ),
            'family-friendly' => array(
                'name' => __('Family Friendly', 'your-hidden-trip'),
                'description' => __('Perfect for families with children', 'your-hidden-trip')
            ),
            'romantic-getaways' => array(
                'name' => __('Romantic Getaways', 'your-hidden-trip'),
                'description' => __('Perfect for couples', 'your-hidden-trip')
            )
        );

        foreach ($categories as $slug => $category) {
            if (!term_exists($category['name'], 'product_cat')) {
                wp_insert_term(
                    $category['name'],
                    'product_cat',
                    array(
                        'slug' => $slug,
                        'description' => $category['description']
                    )
                );
            }
        }
    }

    /**
     * Sync trip posts to WooCommerce products
     */
    public function sync_trip_to_product($trip_id) {
        if (get_post_type($trip_id) !== 'trip') {
            return;
        }

        $trip = get_post($trip_id);
        $existing_product_id = get_post_meta($trip_id, '_wc_product_id', true);

        $product_data = array(
            'post_title' => $trip->post_title,
            'post_content' => $trip->post_content,
            'post_status' => 'publish',
            'post_type' => 'product'
        );

        if ($existing_product_id && get_post($existing_product_id)) {
            // Update existing product
            $product_data['ID'] = $existing_product_id;
            $product_id = wp_update_post($product_data);
        } else {
            // Create new product
            $product_id = wp_insert_post($product_data);
            update_post_meta($trip_id, '_wc_product_id', $product_id);
        }

        if ($product_id && !is_wp_error($product_id)) {
            // Set product type
            wp_set_object_terms($product_id, 'trip', 'product_type');
            
            // Link product to trip
            update_post_meta($product_id, '_trip_id', $trip_id);
            
            // Set basic product data
            update_post_meta($product_id, '_virtual', 'yes');
            update_post_meta($product_id, '_sold_individually', 'yes');
            
            // Set pricing
            $base_price = get_post_meta($trip_id, '_trip_price', true);
            if ($base_price) {
                update_post_meta($product_id, '_regular_price', $base_price);
                update_post_meta($product_id, '_price', $base_price);
            }

            // Set trip-specific data
            $trip_meta = array(
                '_trip_duration' => get_post_meta($trip_id, '_trip_duration', true),
                '_trip_difficulty' => get_post_meta($trip_id, '_trip_difficulty', true),
                '_trip_max_participants' => get_post_meta($trip_id, '_trip_max_participants', true),
                '_trip_available_dates' => get_post_meta($trip_id, '_trip_available_dates', true),
                '_trip_seasonal_pricing' => get_post_meta($trip_id, '_trip_seasonal_pricing', true),
                '_trip_group_pricing' => get_post_meta($trip_id, '_trip_group_pricing', true)
            );

            foreach ($trip_meta as $key => $value) {
                if ($value) {
                    update_post_meta($product_id, $key, $value);
                }
            }

            // Set categories based on trip taxonomies
            $this->sync_trip_categories($product_id, $trip_id);
            
            // Set featured image
            $thumbnail_id = get_post_thumbnail_id($trip_id);
            if ($thumbnail_id) {
                set_post_thumbnail($product_id, $thumbnail_id);
            }
        }
    }

    /**
     * Sync trip categories to product categories
     */
    private function sync_trip_categories($product_id, $trip_id) {
        // Get trip types and convert to product categories
        $trip_types = wp_get_post_terms($trip_id, 'trip_type', array('fields' => 'slugs'));
        $locations = wp_get_post_terms($trip_id, 'location', array('fields' => 'slugs'));
        
        $product_categories = array_merge($trip_types, $locations);
        
        if (!empty($product_categories)) {
            wp_set_object_terms($product_id, $product_categories, 'product_cat');
        }
    }

    /**
     * Add trip booking form to product page
     */
    public function add_trip_booking_form() {
        global $product;
        
        if (!$product || $product->get_type() !== 'trip') {
            return;
        }

        $trip_data = $product->get_trip_data();
        $available_dates = $product->get_available_dates();
        
        include YHT_PLUGIN_PATH . 'templates/woocommerce/trip-booking-form.php';
    }

    /**
     * Add trip details tab to product page
     */
    public function add_trip_details_tab($tabs) {
        global $product;
        
        if ($product && $product->get_type() === 'trip') {
            $tabs['trip_details'] = array(
                'title' => __('Trip Details', 'your-hidden-trip'),
                'priority' => 50,
                'callback' => array($this, 'trip_details_tab_content')
            );
        }
        
        return $tabs;
    }

    /**
     * Trip details tab content
     */
    public function trip_details_tab_content() {
        global $product;
        
        $trip_data = $product->get_trip_data();
        include YHT_PLUGIN_PATH . 'templates/woocommerce/trip-details-tab.php';
    }

    /**
     * Add trip data to cart
     */
    public function add_trip_data_to_cart($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['trip_date']) && isset($_POST['trip_participants'])) {
            $cart_item_data['trip_date'] = sanitize_text_field($_POST['trip_date']);
            $cart_item_data['trip_participants'] = intval($_POST['trip_participants']);
            
            // Additional trip options
            if (isset($_POST['trip_dietary_requirements'])) {
                $cart_item_data['trip_dietary_requirements'] = sanitize_textarea_field($_POST['trip_dietary_requirements']);
            }
            
            if (isset($_POST['trip_special_requests'])) {
                $cart_item_data['trip_special_requests'] = sanitize_textarea_field($_POST['trip_special_requests']);
            }
            
            if (isset($_POST['trip_emergency_contact'])) {
                $cart_item_data['trip_emergency_contact'] = sanitize_text_field($_POST['trip_emergency_contact']);
            }
        }
        
        return $cart_item_data;
    }

    /**
     * Display trip data in cart
     */
    public function display_trip_data_in_cart($item_data, $cart_item) {
        if (isset($cart_item['trip_date'])) {
            $item_data[] = array(
                'key' => __('Trip Date', 'your-hidden-trip'),
                'value' => date_i18n(get_option('date_format'), strtotime($cart_item['trip_date']))
            );
        }
        
        if (isset($cart_item['trip_participants'])) {
            $item_data[] = array(
                'key' => __('Participants', 'your-hidden-trip'),
                'value' => $cart_item['trip_participants']
            );
        }
        
        if (isset($cart_item['trip_dietary_requirements']) && !empty($cart_item['trip_dietary_requirements'])) {
            $item_data[] = array(
                'key' => __('Dietary Requirements', 'your-hidden-trip'),
                'value' => $cart_item['trip_dietary_requirements']
            );
        }
        
        return $item_data;
    }

    /**
     * Save trip data to order
     */
    public function save_trip_data_to_order($item, $cart_item_key, $values, $order) {
        if (isset($values['trip_date'])) {
            $item->add_meta_data(__('Trip Date', 'your-hidden-trip'), $values['trip_date']);
        }
        
        if (isset($values['trip_participants'])) {
            $item->add_meta_data(__('Participants', 'your-hidden-trip'), $values['trip_participants']);
        }
        
        if (isset($values['trip_dietary_requirements'])) {
            $item->add_meta_data(__('Dietary Requirements', 'your-hidden-trip'), $values['trip_dietary_requirements']);
        }
        
        if (isset($values['trip_special_requests'])) {
            $item->add_meta_data(__('Special Requests', 'your-hidden-trip'), $values['trip_special_requests']);
        }
        
        if (isset($values['trip_emergency_contact'])) {
            $item->add_meta_data(__('Emergency Contact', 'your-hidden-trip'), $values['trip_emergency_contact']);
        }
    }

    /**
     * Add trip-specific checkout fields
     */
    public function add_trip_checkout_fields($fields) {
        // Check if cart contains trip products
        $has_trip = false;
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            if ($product->get_type() === 'trip') {
                $has_trip = true;
                break;
            }
        }
        
        if ($has_trip) {
            $fields['trip_info'] = array(
                'trip_experience_level' => array(
                    'type' => 'select',
                    'label' => __('Experience Level', 'your-hidden-trip'),
                    'required' => false,
                    'options' => array(
                        '' => __('Select experience level', 'your-hidden-trip'),
                        'beginner' => __('Beginner', 'your-hidden-trip'),
                        'intermediate' => __('Intermediate', 'your-hidden-trip'),
                        'advanced' => __('Advanced', 'your-hidden-trip')
                    )
                ),
                'trip_medical_conditions' => array(
                    'type' => 'textarea',
                    'label' => __('Medical Conditions', 'your-hidden-trip'),
                    'placeholder' => __('Please list any medical conditions we should be aware of', 'your-hidden-trip'),
                    'required' => false
                ),
                'trip_how_heard' => array(
                    'type' => 'select',
                    'label' => __('How did you hear about us?', 'your-hidden-trip'),
                    'required' => false,
                    'options' => array(
                        '' => __('Please select', 'your-hidden-trip'),
                        'search_engine' => __('Search Engine', 'your-hidden-trip'),
                        'social_media' => __('Social Media', 'your-hidden-trip'),
                        'friend_referral' => __('Friend Referral', 'your-hidden-trip'),
                        'travel_blog' => __('Travel Blog', 'your-hidden-trip'),
                        'other' => __('Other', 'your-hidden-trip')
                    )
                )
            );
        }
        
        return $fields;
    }

    /**
     * Save trip checkout fields
     */
    public function save_trip_checkout_fields($order_id) {
        if (isset($_POST['trip_experience_level']) && !empty($_POST['trip_experience_level'])) {
            update_post_meta($order_id, '_trip_experience_level', sanitize_text_field($_POST['trip_experience_level']));
        }
        
        if (isset($_POST['trip_medical_conditions']) && !empty($_POST['trip_medical_conditions'])) {
            update_post_meta($order_id, '_trip_medical_conditions', sanitize_textarea_field($_POST['trip_medical_conditions']));
        }
        
        if (isset($_POST['trip_how_heard']) && !empty($_POST['trip_how_heard'])) {
            update_post_meta($order_id, '_trip_how_heard', sanitize_text_field($_POST['trip_how_heard']));
        }
    }

    /**
     * Handle trip booking confirmation
     */
    public function handle_trip_booking_confirmation($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            
            if ($product && $product->get_type() === 'trip') {
                // Send booking confirmation email
                $this->send_trip_confirmation_email($order, $item);
                
                // Update availability
                $this->update_trip_availability($item);
                
                // Log booking
                if (class_exists('YHT_Logger')) {
                    YHT_Logger::info('Trip booking confirmed', array(
                        'order_id' => $order_id,
                        'product_id' => $product->get_id(),
                        'trip_date' => $item->get_meta('Trip Date'),
                        'participants' => $item->get_meta('Participants')
                    ));
                }
            }
        }
    }

    /**
     * Send trip confirmation email
     */
    private function send_trip_confirmation_email($order, $item) {
        $product = $item->get_product();
        $trip_data = $product->get_trip_data();
        
        $to = $order->get_billing_email();
        $subject = sprintf(__('Trip Booking Confirmed - %s', 'your-hidden-trip'), $item->get_name());
        
        // Load email template
        ob_start();
        include YHT_PLUGIN_PATH . 'templates/emails/trip-confirmation.php';
        $message = ob_get_clean();
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Update trip availability after booking
     */
    private function update_trip_availability($item) {
        $trip_date = $item->get_meta('Trip Date');
        $participants = intval($item->get_meta('Participants')) ?: 1;
        
        // This would update your availability tracking system
        // Implementation depends on how you store availability data
    }

    /**
     * Check trip availability via AJAX
     */
    public function ajax_check_availability() {
        check_ajax_referer('yht_wc_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $date = sanitize_text_field($_POST['date']);
        $participants = intval($_POST['participants']) ?: 1;
        
        $product = wc_get_product($product_id);
        
        if (!$product || $product->get_type() !== 'trip') {
            wp_send_json_error(array('message' => 'Invalid product'));
        }
        
        $available = $product->check_availability($date, $participants);
        
        wp_send_json_success(array(
            'available' => $available,
            'message' => $available ? 
                __('Available', 'your-hidden-trip') : 
                __('Not available for selected date and participants', 'your-hidden-trip')
        ));
    }

    /**
     * Get trip pricing via AJAX
     */
    public function ajax_get_pricing() {
        check_ajax_referer('yht_wc_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $date = sanitize_text_field($_POST['date']);
        $participants = intval($_POST['participants']) ?: 1;
        
        $product = wc_get_product($product_id);
        
        if (!$product || $product->get_type() !== 'trip') {
            wp_send_json_error(array('message' => 'Invalid product'));
        }
        
        $price = $product->get_price_for_date($date, $participants);
        $total = $price * $participants;
        
        wp_send_json_success(array(
            'price_per_person' => wc_price($price),
            'total_price' => wc_price($total),
            'participants' => $participants
        ));
    }

    /**
     * Trip booking shortcode
     */
    public function trip_booking_shortcode($atts) {
        $atts = shortcode_atts(array(
            'trip_id' => 0,
            'product_id' => 0,
            'show_price' => 'yes',
            'show_availability' => 'yes'
        ), $atts, 'yht_trip_booking');

        if ($atts['product_id']) {
            $product = wc_get_product($atts['product_id']);
        } elseif ($atts['trip_id']) {
            $product_id = get_post_meta($atts['trip_id'], '_wc_product_id', true);
            $product = $product_id ? wc_get_product($product_id) : null;
        } else {
            return '';
        }

        if (!$product || $product->get_type() !== 'trip') {
            return '';
        }

        ob_start();
        include YHT_PLUGIN_PATH . 'templates/shortcodes/trip-booking.php';
        return ob_get_clean();
    }

    /**
     * Trip price shortcode
     */
    public function trip_price_shortcode($atts) {
        $atts = shortcode_atts(array(
            'trip_id' => 0,
            'product_id' => 0,
            'format' => 'from',
            'show_currency' => 'yes'
        ), $atts, 'yht_trip_price');

        if ($atts['product_id']) {
            $product = wc_get_product($atts['product_id']);
        } elseif ($atts['trip_id']) {
            $product_id = get_post_meta($atts['trip_id'], '_wc_product_id', true);
            $product = $product_id ? wc_get_product($product_id) : null;
        } else {
            return '';
        }

        if (!$product) {
            return '';
        }

        $price = $product->get_price();
        
        if ($atts['format'] === 'from') {
            return sprintf(__('From %s', 'your-hidden-trip'), wc_price($price));
        }
        
        return $atts['show_currency'] === 'yes' ? wc_price($price) : $price;
    }
}