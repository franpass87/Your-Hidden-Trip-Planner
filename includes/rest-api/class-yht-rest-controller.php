<?php
/**
 * Handle REST API endpoints
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_Rest_Controller {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('yht/v1','/generate', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_tour'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('yht/v1','/lead', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_lead'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('yht/v1','/wc_create_product', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_wc_product'),
            'permission_callback' => array($this, 'wc_permission_callback')
        ));
        
        register_rest_route('yht/v1','/pdf', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_pdf'),
            'permission_callback' => '__return_true'
        ));

        // New booking endpoints
        register_rest_route('yht/v1','/book_package', array(
            'methods' => 'POST',
            'callback' => array($this, 'book_package'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('yht/v1','/calculate_price', array(
            'methods' => 'POST',
            'callback' => array($this, 'calculate_package_price'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('yht/v1','/check_availability', array(
            'methods' => 'POST',
            'callback' => array($this, 'check_availability'),
            'permission_callback' => '__return_true'
        ));

        // New endpoint for booking stats (social proof)
        register_rest_route('yht/v1','/booking_stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_booking_stats'),
            'permission_callback' => '__return_true'
        ));
        
        // New endpoint for sending entity selections to clients
        register_rest_route('yht/v1','/send_selection_to_client', array(
            'methods' => 'POST',
            'callback' => array($this, 'send_selection_to_client'),
            'permission_callback' => array($this, 'admin_permission_callback')
        ));
    }
    
    /**
     * Generate tour endpoint
     */
    public function generate_tour(WP_REST_Request $request) {
        require_once YHT_PLUGIN_PATH . 'includes/utilities/class-yht-helpers.php';
        
        $params = $request->get_json_params();
        $traveler_type = sanitize_text_field($params['travelerType'] ?? '');
        $experiences = array_map('sanitize_text_field', $params['esperienze'] ?? array());
        $areas = array_map('sanitize_text_field', $params['luogo'] ?? array());
        $duration = sanitize_text_field($params['durata'] ?? '');
        $startdate = sanitize_text_field($params['startdate'] ?? '');
        $trasporto = sanitize_text_field($params['trasporto'] ?? '');

        $days = YHT_Helpers::duration_to_days($duration);
        $per_day = ($traveler_type === 'active') ? 3 : 2;

        $pool = YHT_Helpers::query_poi($experiences, $areas, $startdate, $days);
        $accommodations = YHT_Helpers::query_accommodations($areas, $startdate, $days);
        $services = YHT_Helpers::query_services($areas, $trasporto);

        // First, try to get custom tours from database
        $custom_tours = YHT_Helpers::query_custom_tours($experiences, $areas);
        
        $tours = array();
        
        // Use custom tours if available
        if(!empty($custom_tours)) {
            foreach($custom_tours as $custom_tour) {
                // Calculate comprehensive multiple options data
                $multiple_options_data = self::analyze_multiple_options_comprehensive($custom_tour['days_with_entities']);
                
                // Convert custom tour to the expected format with real entity data
                $tour_data = array(
                    'name' => $custom_tour['name'],
                    'description' => $custom_tour['description'],
                    'days' => $custom_tour['days_with_entities'], // Use organized days with entities (now includes multiple options)
                    'stops' => count($custom_tour['connected_luoghi']),
                    'totalEntryCost' => $custom_tour['prezzo_base'],
                    'accommodations' => $custom_tour['connected_alloggi'],
                    'services' => $custom_tour['connected_servizi'],
                    'pricing' => array(
                        'standard' => $custom_tour['prezzo_standard_pax'],
                        'premium' => $custom_tour['prezzo_premium_pax'],
                        'luxury' => $custom_tour['prezzo_luxury_pax']
                    ),
                    'type' => 'custom',
                    'id' => $custom_tour['id'],
                    'auto_pricing' => $custom_tour['auto_pricing'],
                    
                    // Enhanced metadata for multiple options system - FULLY IMPLEMENTED
                    'has_real_entities' => true,
                    'has_multiple_options' => $multiple_options_data['has_multiple_options'],
                    'multiple_options_system' => array(
                        'enabled' => true,
                        'version' => '2.0',
                        'description' => 'Sistema avanzato con 3-4 opzioni alternative per categoria per prevenire overbooking',
                        'implementation_level' => 'complete',
                        'flexibility_level' => $multiple_options_data['flexibility_level'],
                        'coverage_details' => $multiple_options_data['coverage_details']
                    ),
                    
                    // Comprehensive entity summary with detailed breakdown
                    'entity_summary' => array(
                        'total_luoghi_options' => $multiple_options_data['total_luoghi_options'],
                        'total_alloggi_options' => $multiple_options_data['total_alloggi_options'],
                        'total_servizi_options' => $multiple_options_data['total_servizi_options'],
                        'unique_entities_count' => $multiple_options_data['unique_entities_count'],
                        'average_options_per_category' => $multiple_options_data['average_options_per_category'],
                        'options_distribution' => $multiple_options_data['options_distribution'],
                        'quality_tiers_available' => $multiple_options_data['quality_tiers_available'],
                        'geographic_spread' => $multiple_options_data['geographic_spread']
                    ),
                    
                    // Advanced overbooking protection system
                    'overbooking_protection' => array(
                        'enabled' => true,
                        'strategy' => 'multiple_alternatives_with_quality_matching',
                        'protection_level' => $multiple_options_data['protection_level'],
                        'backup_availability' => $multiple_options_data['backup_availability'],
                        'average_options_per_day' => $multiple_options_data['average_options_per_day'],
                        'coverage_percentage' => $multiple_options_data['coverage_percentage'],
                        'risk_mitigation_score' => $multiple_options_data['risk_mitigation_score'],
                        'message' => sprintf(
                            'Tour completamente protetto: %d opzioni alternative su %d giorni (%d%% copertura)',
                            $multiple_options_data['total_options'],
                            count($custom_tour['days_with_entities']),
                            $multiple_options_data['coverage_percentage']
                        )
                    ),
                    
                    // Business continuity and operational features
                    'business_continuity' => array(
                        'guaranteed_availability' => $multiple_options_data['coverage_percentage'] >= 80,
                        'risk_mitigation' => $multiple_options_data['risk_mitigation_score'] >= 7 ? 'excellent' : ($multiple_options_data['risk_mitigation_score'] >= 5 ? 'good' : 'basic'),
                        'alternative_selection_method' => 'quality_tier_matching',
                        'operator_flexibility' => $multiple_options_data['operator_flexibility'],
                        'client_choice_availability' => $multiple_options_data['client_choice_available'],
                        'peak_season_protection' => $multiple_options_data['peak_season_ready']
                    ),
                    
                    // Backward compatibility
                    'legacy_data' => array(
                        'luoghi_count' => count($custom_tour['connected_luoghi']),
                        'alloggi_count' => count($custom_tour['connected_alloggi']),
                        'servizi_count' => count($custom_tour['connected_servizi']),
                        'options_summary' => self::calculate_options_summary($custom_tour['days_with_entities'])
                    )
                );
                $tours[] = $tour_data;
            }
        }
        
        // If no custom tours found or we want to provide variety, add generated tours as fallbacks
        if(empty($tours) || count($tours) < 3) {
            $fallback_tours = array(
                // Balanced tour for first-time visitors
                YHT_Helpers::plan_itinerary('Tour Classico', $pool, $days, $per_day, array('trekking'=>1.0,'passeggiata'=>1.1,'cultura'=>1.2,'benessere'=>0.7,'enogastronomia'=>0.9), $accommodations, $services),
                // Nature and outdoor focused tour  
                YHT_Helpers::plan_itinerary('Natura & Avventura', $pool, $days, $per_day, array('trekking'=>1.4,'passeggiata'=>1.2,'cultura'=>0.7,'benessere'=>0.8,'enogastronomia'=>0.9), $accommodations, $services),
                // Cultural and culinary focused tour
                YHT_Helpers::plan_itinerary('Cultura & Tradizioni', $pool, $days, $per_day, array('trekking'=>0.6,'passeggiata'=>0.9,'cultura'=>1.5,'benessere'=>0.8,'enogastronomia'=>1.3), $accommodations, $services)
            );
            
            // Add fallback tours to fill up to 3 total tours
            $needed_fallbacks = 3 - count($tours);
            for($i = 0; $i < min($needed_fallbacks, count($fallback_tours)); $i++) {
                $fallback_tours[$i]['type'] = 'generated';
                $tours[] = $fallback_tours[$i];
            }
        }

        return rest_ensure_response(array(
            'ok' => true,
            'days' => $days,
            'perDay' => $per_day,
            'tours' => $tours,
            'trasporto' => $trasporto
        ));
    }
    
    /**
     * Submit lead endpoint
     */
    public function submit_lead(WP_REST_Request $request) {
        $params = $request->get_json_params();
        $email = sanitize_email($params['email'] ?? '');
        $name = sanitize_text_field($params['name'] ?? '');
        $payload = wp_kses_post($params['payload'] ?? '');
        $settings = YHT_Plugin::get_instance()->get_settings();

        // Send internal email notification
        if(!empty($settings['notify_email'])){
            wp_mail($settings['notify_email'], 'Nuovo lead YHT', "Nome: $name\nEmail: $email\n\n$payload");
        }

        $ok = true;
        $message = 'Lead ricevuto';
        
        // Send to Brevo if API key is configured
        if(!empty($settings['brevo_api_key']) && !empty($email)){
            $body = array(
                'email' => $email,
                'attributes' => array(
                    'NOME' => $name,
                    'ORIGINE' => 'YHT Builder',
                ),
                'updateEnabled' => true,
                'listIds' => array()
            );
            
            $response = wp_remote_post('https://api.brevo.com/v3/contacts', array(
                'headers' => array(
                    'accept' => 'application/json',
                    'api-key' => $settings['brevo_api_key'],
                    'content-type' => 'application/json'
                ),
                'body' => wp_json_encode($body),
                'timeout' => 20
            ));
            
            if(is_wp_error($response)){
                $ok = false;
                $message = $response->get_error_message();
            } else {
                $code = wp_remote_retrieve_response_code($response);
                if($code >= 400){
                    $ok = false;
                    $message = 'Errore Brevo ' . $code . ': ' . wp_remote_retrieve_body($response);
                }
            }
        }

        return rest_ensure_response(array('ok' => $ok, 'message' => $message));
    }
    
    /**
     * Create WooCommerce product endpoint
     */
    public function create_wc_product(WP_REST_Request $request) {
        if(!class_exists('WC_Product_Simple')) {
            return rest_ensure_response(array('ok' => false, 'message' => __('WooCommerce non attivo', 'your-hidden-trip')));
        }
        
        $params = $request->get_json_params();
        $tour = $params['tour'] ?? array();
        $settings = YHT_Plugin::get_instance()->get_settings();
        
        $price_per_pax = (float)$settings['wc_price_per_pax'];
        $pax = max(1, intval($params['pax'] ?? 2));
        $price = $price_per_pax * $pax;

        $title = sanitize_text_field($tour['name'] ?? 'Pacchetto tour');
        $description = $this->build_tour_description($tour);

        $product = new WC_Product_Simple();
        $product->set_name($title . ' – ' . $pax . ' pax');
        $product->set_regular_price($price);
        $product->set_catalog_visibility('hidden');
        $product->set_description($description);
        $product->save();

        return rest_ensure_response(array(
            'ok' => true,
            'product_id' => $product->get_id(),
            'price' => $price
        ));
    }
    
    /**
     * Generate PDF endpoint
     */
    public function generate_pdf(WP_REST_Request $request) {
        require_once YHT_PLUGIN_PATH . 'includes/pdf/class-yht-pdf-generator.php';
        
        $params = $request->get_json_params();
        $pdf_generator = new YHT_PDF_Generator();
        
        return $pdf_generator->generate_pdf($params);
    }
    
    /**
     * WooCommerce permission callback
     */
    public function wc_permission_callback() {
        return current_user_can('manage_woocommerce') || current_user_can('manage_options');
    }
    
    /**
     * Build tour description for WooCommerce product
     */
    private function build_tour_description($tour) {
        $description = 'Itinerario: ';
        
        if(!empty($tour['days'])){
            $rows = array();
            foreach($tour['days'] as $day){
                $stops = array_map(function($stop){ 
                    return ($stop['time'] ?? '') . ' ' . ($stop['title'] ?? ''); 
                }, $day['stops'] ?? array());
                $rows[] = 'Giorno ' . $day['day'] . ': ' . implode(' · ', $stops);
            }
            $description .= implode("\n", $rows);
        }
        
        return $description;
    }
    
    /**
     * Calculate package price endpoint
     */
    public function calculate_package_price(WP_REST_Request $request) {
        $params = $request->get_json_params();
        $tour = $params['tour'] ?? array();
        $package_type = sanitize_text_field($params['package_type'] ?? 'standard');
        $num_pax = max(1, intval($params['num_pax'] ?? 2));
        $travel_date = sanitize_text_field($params['travel_date'] ?? '');
        
        $calculation = $this->calculate_all_inclusive_price($tour, $package_type, $num_pax, $travel_date);
        
        return rest_ensure_response($calculation);
    }
    
    /**
     * Check availability endpoint
     */
    public function check_availability(WP_REST_Request $request) {
        $params = $request->get_json_params();
        $tour = $params['tour'] ?? array();
        $travel_date = sanitize_text_field($params['travel_date'] ?? '');
        $num_pax = max(1, intval($params['num_pax'] ?? 2));
        
        $availability = $this->check_tour_availability($tour, $travel_date, $num_pax);
        
        return rest_ensure_response($availability);
    }
    
    /**
     * Book package endpoint
     */
    public function book_package(WP_REST_Request $request) {
        $params = $request->get_json_params();
        
        // Validate required fields
        $required_fields = array('customer_name', 'customer_email', 'tour', 'travel_date', 'num_pax');
        foreach($required_fields as $field) {
            if(empty($params[$field])) {
                return rest_ensure_response(array(
                    'ok' => false,
                    'message' => sprintf(__('Campo richiesto mancante: %s', 'your-hidden-trip'), $field)
                ));
            }
        }
        
        $customer_name = sanitize_text_field($params['customer_name']);
        $customer_email = sanitize_email($params['customer_email']);
        $customer_phone = sanitize_text_field($params['customer_phone'] ?? '');
        $tour = $params['tour'];
        $travel_date = sanitize_text_field($params['travel_date']);
        $num_pax = max(1, intval($params['num_pax']));
        $package_type = sanitize_text_field($params['package_type'] ?? 'standard');
        $special_requests = sanitize_textarea_field($params['special_requests'] ?? '');
        
        // Flexibility options
        $flexible_dates = (bool)($params['flexible_dates'] ?? false);
        $add_insurance = (bool)($params['add_insurance'] ?? false);
        $early_checkin = (bool)($params['early_checkin'] ?? false);
        $late_checkout = (bool)($params['late_checkout'] ?? false);
        
        // Check availability first
        $availability = $this->check_tour_availability($tour, $travel_date, $num_pax);
        if(!$availability['available']) {
            return rest_ensure_response(array(
                'ok' => false,
                'message' => __('Il pacchetto non è disponibile per le date selezionate.', 'your-hidden-trip')
            ));
        }
        
        // Calculate pricing with flexibility options
        $pricing = $this->calculate_all_inclusive_price($tour, $package_type, $num_pax, $travel_date, array(
            'flexible_dates' => $flexible_dates,
            'add_insurance' => $add_insurance,
            'early_checkin' => $early_checkin,
            'late_checkout' => $late_checkout
        ));
        
        // Generate booking reference
        $booking_reference = 'YHT-' . strtoupper(wp_generate_password(8, false));
        
        // Create booking post
        $booking_id = wp_insert_post(array(
            'post_type' => 'yht_booking',
            'post_title' => "Prenotazione $booking_reference - $customer_name",
            'post_status' => 'publish',
            'post_content' => "Prenotazione per {$tour['name']} - $num_pax persone"
        ));
        
        if(is_wp_error($booking_id)) {
            return rest_ensure_response(array(
                'ok' => false,
                'message' => __('Errore nella creazione della prenotazione', 'your-hidden-trip')
            ));
        }
        
        // Save booking meta
        update_post_meta($booking_id, 'yht_customer_name', $customer_name);
        update_post_meta($booking_id, 'yht_customer_email', $customer_email);
        update_post_meta($booking_id, 'yht_customer_phone', $customer_phone);
        update_post_meta($booking_id, 'yht_booking_status', 'pending_payment');
        update_post_meta($booking_id, 'yht_booking_reference', $booking_reference);
        update_post_meta($booking_id, 'yht_total_price', $pricing['total']);
        update_post_meta($booking_id, 'yht_deposit_paid', '0');
        update_post_meta($booking_id, 'yht_travel_date', $travel_date);
        update_post_meta($booking_id, 'yht_num_pax', $num_pax);
        update_post_meta($booking_id, 'yht_package_type', $package_type);
        update_post_meta($booking_id, 'yht_itinerary_json', wp_json_encode($tour));
        update_post_meta($booking_id, 'yht_special_requests', $special_requests);
        
        // Store flexibility options
        update_post_meta($booking_id, 'yht_flexible_dates', $flexible_dates ? '1' : '0');
        update_post_meta($booking_id, 'yht_add_insurance', $add_insurance ? '1' : '0');
        update_post_meta($booking_id, 'yht_early_checkin', $early_checkin ? '1' : '0');
        update_post_meta($booking_id, 'yht_late_checkout', $late_checkout ? '1' : '0');
        
        // Create WooCommerce product for payment
        $wc_result = $this->create_wc_product_from_booking($booking_id, $tour, $pricing, $num_pax);
        
        if($wc_result['ok']) {
            update_post_meta($booking_id, 'yht_wc_order_id', $wc_result['product_id']);
            
            // Send confirmation email
            $this->send_booking_confirmation_email($booking_id);
            
            return rest_ensure_response(array(
                'ok' => true,
                'booking_id' => $booking_id,
                'booking_reference' => $booking_reference,
                'wc_product_id' => $wc_result['product_id'],
                'wc_checkout_url' => wc_get_checkout_url() . '?add-to-cart=' . $wc_result['product_id'],
                'total_price' => $pricing['total'],
                'deposit_amount' => $pricing['deposit']
            ));
        } else {
            return rest_ensure_response(array(
                'ok' => false,
                'message' => sprintf(__('Prenotazione creata ma errore nel sistema di pagamento: %s', 'your-hidden-trip'), $wc_result['message'])
            ));
        }
    }
    
    /**
     * Calculate all-inclusive price for a tour with enhanced multiple options support
     */
    private function calculate_all_inclusive_price($tour, $package_type, $num_pax, $travel_date, $options = array()) {
        $total = 0;
        $breakdown = array();
        
        // Check if this is a custom tour with direct pricing
        if(isset($tour['type']) && $tour['type'] === 'custom' && isset($tour['pricing'])) {
            $base_price = $tour['pricing'][$package_type] ?? $tour['pricing']['standard'] ?? 0;
            $total = $base_price * $num_pax;
            $breakdown['base_tour'] = $total;
            
            // Enhanced accommodation costs calculation with multiple options support
            if(!empty($tour['accommodations'])) {
                $accommodation_cost = $this->calculate_accommodation_costs_multiple_options($tour, $package_type, $num_pax);
                $total += $accommodation_cost;
                $breakdown['accommodation'] = $accommodation_cost;
            }
            
            // Enhanced services costs with multiple options averaging
            if(!empty($tour['days']) && isset($tour['days'][0]['servizi_groups'])) {
                $services_cost = $this->calculate_services_costs_multiple_options($tour, $package_type, $num_pax);
                $total += $services_cost;
                $breakdown['services'] = $services_cost;
            }
        } else {
            // Handle generated tours with multiplier-based pricing
            $multipliers = array(
                'standard' => 1.0,
                'premium' => 1.3,
                'luxury' => 1.7
            );
            $multiplier = $multipliers[$package_type] ?? 1.0;
            
            // Calculate accommodation costs
            if(!empty($tour['accommodations'])) {
                $accommodation_cost = 0;
                foreach($tour['accommodations'] as $acc) {
                    $price_field = "yht_prezzo_notte_$package_type";
                    $price_per_night = (float)get_post_meta($acc['id'], $price_field, true);
                    if(!$price_per_night) {
                        $price_per_night = (float)get_post_meta($acc['id'], 'yht_prezzo_notte_standard', true);
                    }
                    $accommodation_cost += $price_per_night * ($tour['days'] ?? 1);
                }
                $total += $accommodation_cost * $num_pax;
                $breakdown['accommodation'] = $accommodation_cost * $num_pax;
            }
        }
        
        // Calculate activities/places costs (for generated tours only)
        if(!empty($tour['days']) && (!isset($tour['type']) || $tour['type'] !== 'custom')) {
            $multipliers = array(
                'standard' => 1.0,
                'premium' => 1.3,
                'luxury' => 1.7
            );
            $multiplier = $multipliers[$package_type] ?? 1.0;
            
            $activities_cost = 0;
            foreach($tour['days'] as $day) {
                if(!empty($day['stops'])) {
                    foreach($day['stops'] as $stop) {
                        $entry_cost = (float)($stop['cost'] ?? 0);
                        $activities_cost += $entry_cost * $multiplier;
                    }
                }
            }
            $total += $activities_cost * $num_pax;
            $breakdown['activities'] = $activities_cost * $num_pax;
        }
        
        // Calculate meals (restaurants/services) - for generated tours only
        if(!empty($tour['services']) && (!isset($tour['type']) || $tour['type'] !== 'custom')) {
            $multipliers = array(
                'standard' => 1.0,
                'premium' => 1.3,
                'luxury' => 1.7
            );
            $multiplier = $multipliers[$package_type] ?? 1.0;
            
            $meals_cost = 0;
            foreach($tour['services'] as $service) {
                if(in_array('ristorante', $service['service_type'] ?? array())) {
                    $meal_price = (float)get_post_meta($service['id'], 'yht_prezzo_persona', true);
                    if(!$meal_price) $meal_price = 25; // Default meal price
                    $meals_cost += $meal_price * $multiplier;
                }
            }
            $total += $meals_cost * $num_pax;
            $breakdown['meals'] = $meals_cost * $num_pax;
        }
        
        // Transport costs (basic inclusion) - for generated tours only
        if(!isset($tour['type']) || $tour['type'] !== 'custom') {
            $multipliers = array(
                'standard' => 1.0,
                'premium' => 1.3,
                'luxury' => 1.7
            );
            $multiplier = $multipliers[$package_type] ?? 1.0;
            
            $transport_cost = 50 * $multiplier; // Base transport cost per person
            $total += $transport_cost * $num_pax;
            $breakdown['transport'] = $transport_cost * $num_pax;
        }
        
        // Service fees and markup
        $service_fee = $total * 0.1; // 10% service fee
        $total += $service_fee;
        $breakdown['service_fee'] = $service_fee;
        
        // Add flexibility options pricing
        if (!empty($options)) {
            $extra_costs = 0;
            
            // Flexible dates discount
            if ($options['flexible_dates'] ?? false) {
                $discount = $total * 0.15; // 15% discount for flexibility
                $total -= $discount;
                $breakdown['flexible_dates_discount'] = -$discount;
            }
            
            // Insurance cost
            if ($options['add_insurance'] ?? false) {
                $insurance_cost = 19 * $num_pax;
                $total += $insurance_cost;
                $breakdown['insurance'] = $insurance_cost;
            }
            
            // Early check-in
            if ($options['early_checkin'] ?? false) {
                $early_checkin_cost = 25 * (count($tour['days'] ?? array()) ?: 1);
                $total += $early_checkin_cost;
                $breakdown['early_checkin'] = $early_checkin_cost;
            }
            
            // Late checkout  
            if ($options['late_checkout'] ?? false) {
                $late_checkout_cost = 25 * (count($tour['days'] ?? array()) ?: 1);
                $total += $late_checkout_cost;
                $breakdown['late_checkout'] = $late_checkout_cost;
            }
        }
        
        // Multiple options adjustment - slight premium for additional options availability
        if(isset($tour['has_multiple_options']) && $tour['has_multiple_options']) {
            $multiple_options_premium = $total * 0.02; // 2% premium for multiple options security
            $total += $multiple_options_premium;
            $breakdown['multiple_options_premium'] = $multiple_options_premium;
        }
        
        // Calculate deposit (20% of total)
        $settings = YHT_Plugin::get_instance()->get_settings();
        $deposit_pct = (float)($settings['wc_deposit_pct'] ?? 20) / 100;
        $deposit = $total * $deposit_pct;
        
        return array(
            'ok' => true,
            'total' => round($total, 2),
            'deposit' => round($deposit, 2),
            'balance' => round($total - $deposit, 2),
            'breakdown' => $breakdown,
            'package_type' => $package_type,
            'num_pax' => $num_pax,
            'multiple_options_included' => isset($tour['has_multiple_options']) && $tour['has_multiple_options'],
            'pricing_method' => isset($tour['type']) && $tour['type'] === 'custom' ? 'direct_entity_based' : 'generated_multiplier'
        );
    }
    
    /**
     * Calculate accommodation costs with multiple options support
     */
    private function calculate_accommodation_costs_multiple_options($tour, $package_type, $num_pax) {
        $total_cost = 0;
        
        if(!empty($tour['days'])) {
            foreach($tour['days'] as $day) {
                if(isset($day['alloggi_groups'])) {
                    foreach($day['alloggi_groups'] as $group) {
                        if(isset($group['options']) && !empty($group['options'])) {
                            // Calculate average cost of all accommodation options for this group
                            $options_total = 0;
                            $valid_options = 0;
                            $nights = $group['nights'] ?? 1;
                            
                            foreach($group['options'] as $option) {
                                $price_field = "prezzo_notte_$package_type";
                                $price_per_night = $option[$price_field] ?? $option['prezzo_notte_standard'] ?? 0;
                                
                                if($price_per_night > 0) {
                                    $options_total += $price_per_night * $nights;
                                    $valid_options++;
                                }
                            }
                            
                            // Use average price of all available options
                            if($valid_options > 0) {
                                $average_cost = $options_total / $valid_options;
                                $total_cost += $average_cost * $num_pax;
                            }
                        }
                    }
                }
            }
        }
        
        return $total_cost;
    }
    
    /**
     * Calculate services costs with multiple options support
     */
    private function calculate_services_costs_multiple_options($tour, $package_type, $num_pax) {
        $total_cost = 0;
        $multipliers = array('standard' => 1.0, 'premium' => 1.2, 'luxury' => 1.5);
        $multiplier = $multipliers[$package_type] ?? 1.0;
        
        if(!empty($tour['days'])) {
            foreach($tour['days'] as $day) {
                if(isset($day['servizi_groups'])) {
                    foreach($day['servizi_groups'] as $group) {
                        if(isset($group['options']) && !empty($group['options'])) {
                            // Calculate average cost of all service options for this group
                            $options_total = 0;
                            $valid_options = 0;
                            
                            foreach($group['options'] as $option) {
                                $price_per_person = $option['prezzo_persona'] ?? 0;
                                $fixed_price = $option['prezzo_fisso'] ?? 0;
                                
                                if($price_per_person > 0) {
                                    $options_total += $price_per_person * $multiplier;
                                    $valid_options++;
                                } elseif($fixed_price > 0) {
                                    // Divide fixed price by average group size
                                    $options_total += ($fixed_price / 2) * $multiplier;
                                    $valid_options++;
                                }
                            }
                            
                            // Use average price of all available options
                            if($valid_options > 0) {
                                $average_cost = $options_total / $valid_options;
                                $total_cost += $average_cost * $num_pax;
                            }
                        }
                    }
                }
            }
        }
        
        return $total_cost;
    }
    
    /**
     * Check tour availability for given date and pax
     */
    private function check_tour_availability($tour, $travel_date, $num_pax) {
        $available = true;
        $messages = array();
        
        // Check accommodation availability
        if(!empty($tour['accommodations'])) {
            foreach($tour['accommodations'] as $acc) {
                $availability_json = get_post_meta($acc['id'], 'yht_disponibilita_json', true);
                if($availability_json) {
                    $availability_data = json_decode($availability_json, true);
                    // Simple availability check - could be enhanced
                    foreach($availability_data as $period) {
                        if($travel_date >= $period['start'] && $travel_date <= $period['end']) {
                            if($period['available_rooms'] < 1) {
                                $available = false;
                                $messages[] = "Alloggio {$acc['title']} non disponibile per la data selezionata";
                            }
                        }
                    }
                }
            }
        }
        
        // Check service capacity
        if(!empty($tour['services'])) {
            foreach($tour['services'] as $service) {
                $max_capacity = (int)get_post_meta($service['id'], 'yht_capacita_max', true);
                if($max_capacity && $max_capacity < $num_pax) {
                    $available = false;
                    $messages[] = "Servizio {$service['title']} ha capacità massima di $max_capacity persone";
                }
            }
        }
        
        return array(
            'available' => $available,
            'messages' => $messages,
            'travel_date' => $travel_date,
            'num_pax' => $num_pax
        );
    }
    
    /**
     * Create WooCommerce product from booking
     */
    private function create_wc_product_from_booking($booking_id, $tour, $pricing, $num_pax) {
        if(!class_exists('WC_Product_Simple')) {
            return array('ok' => false, 'message' => __('WooCommerce non attivo', 'your-hidden-trip'));
        }
        
        $booking_reference = get_post_meta($booking_id, 'yht_booking_reference', true);
        $package_type = get_post_meta($booking_id, 'yht_package_type', true);
        
        $title = $tour['name'] . " - $package_type ($num_pax pax)";
        $description = $this->build_tour_description($tour);
        $description .= "\n\nPacchetto All-Inclusive:\n";
        $description .= "- Alloggio in " . ucfirst($package_type) . "\n";
        $description .= "- Tutti i pasti inclusi\n";
        $description .= "- Trasporti inclusi\n";
        $description .= "- Attività ed escursioni\n";
        $description .= "- Assistenza dedicata\n";
        $description .= "\nRiferimento prenotazione: $booking_reference";

        $product = new WC_Product_Simple();
        $product->set_name($title);
        $product->set_regular_price($pricing['total']);
        $product->set_catalog_visibility('hidden');
        $product->set_description($description);
        $product->set_short_description("Pacchetto All-Inclusive $package_type per {$tour['name']}");
        $product->save();

        return array(
            'ok' => true,
            'product_id' => $product->get_id(),
            'price' => $pricing['total']
        );
    }
    
    /**
     * Send booking confirmation email
     */
    private function send_booking_confirmation_email($booking_id) {
        $customer_email = get_post_meta($booking_id, 'yht_customer_email', true);
        $customer_name = get_post_meta($booking_id, 'yht_customer_name', true);
        $booking_reference = get_post_meta($booking_id, 'yht_booking_reference', true);
        $total_price = get_post_meta($booking_id, 'yht_total_price', true);
        
        $subject = "Conferma Prenotazione $booking_reference - Your Hidden Trip";
        $message = "Caro/a $customer_name,\n\n";
        $message .= "La tua prenotazione è stata registrata con successo!\n\n";
        $message .= "Dettagli prenotazione:\n";
        $message .= "Riferimento: $booking_reference\n";
        $message .= "Importo totale: €$total_price\n\n";
        $message .= "Ti contatteremo a breve per finalizzare i dettagli del tuo viaggio.\n\n";
        $message .= "Grazie per aver scelto Your Hidden Trip!";
        
        wp_mail($customer_email, $subject, $message);
        
        // Also notify admin
        $settings = YHT_Plugin::get_instance()->get_settings();
        $admin_email = $settings['notify_email'];
        wp_mail($admin_email, "Nuova Prenotazione: $booking_reference", 
                "Nuova prenotazione ricevuta da $customer_name ($customer_email)");
    }
    
    /**
     * Get booking statistics for social proof
     */
    public function get_booking_stats(WP_REST_Request $request) {
        $stats = array();
        
        // Get total bookings count
        $total_bookings = wp_count_posts('yht_booking');
        $stats['total_bookings'] = ($total_bookings->publish ?? 0) + 1200; // Add base number for psychological impact
        
        // Get recent bookings for social proof
        $recent_bookings = get_posts(array(
            'post_type' => 'yht_booking',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $recent_activity = array();
        foreach($recent_bookings as $booking) {
            $customer_name = get_post_meta($booking->ID, 'yht_customer_name', true);
            $package_type = get_post_meta($booking->ID, 'yht_package_type', true);
            
            // Anonymize name for privacy
            $name_parts = explode(' ', $customer_name);
            $anonymous_name = $name_parts[0] . ' da ' . $this->get_random_city();
            
            $recent_activity[] = array(
                'name' => $anonymous_name,
                'package' => ucfirst($package_type),
                'time' => human_time_diff(strtotime($booking->post_date), current_time('timestamp')) . ' fa'
            );
        }
        
        // Add some fake recent activity if not enough real bookings
        while(count($recent_activity) < 4) {
            $fake_names = array('Marco', 'Laura', 'Giuseppe', 'Francesca', 'Alessandro', 'Giulia');
            $fake_cities = array('Roma', 'Milano', 'Napoli', 'Firenze', 'Bologna', 'Torino');
            $packages = array('Standard', 'Premium', 'Luxury');
            
            $recent_activity[] = array(
                'name' => $fake_names[array_rand($fake_names)] . ' da ' . $fake_cities[array_rand($fake_cities)],
                'package' => $packages[array_rand($packages)],
                'time' => rand(1, 30) . ' min fa'
            );
        }
        
        $stats['recent_bookings'] = $recent_activity;
        $stats['satisfaction_rate'] = 98; // Static high satisfaction rate
        $stats['average_rating'] = 4.9; // Static high rating
        
        return rest_ensure_response($stats);
    }
    
    /**
     * Get random Italian city for anonymization
     */
    private function get_random_city() {
        $cities = array('Roma', 'Milano', 'Napoli', 'Firenze', 'Bologna', 'Torino', 'Palermo', 'Genova', 'Bari', 'Verona');
        return $cities[array_rand($cities)];
    }
    
    /**
     * Comprehensive analysis of multiple options system for tour data consistency
     */
    private static function analyze_multiple_options_comprehensive($days_with_entities) {
        $total_luoghi_options = 0;
        $total_alloggi_options = 0;
        $total_servizi_options = 0;
        $days_with_multiple = 0;
        $total_days = count($days_with_entities);
        $unique_entities = array();
        $quality_tiers = array('standard' => 0, 'premium' => 0, 'luxury' => 0);
        $geographic_locations = array();
        
        foreach($days_with_entities as $day) {
            $day_has_multiple = false;
            
            // Analyze luoghi options
            if(isset($day['luoghi_groups'])) {
                foreach($day['luoghi_groups'] as $group) {
                    $options_count = $group['options_count'] ?? 0;
                    $total_luoghi_options += $options_count;
                    
                    if($options_count > 1) {
                        $day_has_multiple = true;
                    }
                    
                    // Track unique entities and quality
                    if(isset($group['options'])) {
                        foreach($group['options'] as $option) {
                            $unique_entities[] = 'luogo_' . $option['id'];
                            if(isset($option['lat'], $option['lng'])) {
                                $geographic_locations[] = array($option['lat'], $option['lng']);
                            }
                        }
                    }
                }
            }
            
            // Analyze alloggi options
            if(isset($day['alloggi_groups'])) {
                foreach($day['alloggi_groups'] as $group) {
                    $options_count = $group['options_count'] ?? 0;
                    $total_alloggi_options += $options_count;
                    
                    if($options_count > 1) {
                        $day_has_multiple = true;
                    }
                    
                    // Track unique entities and quality tiers
                    if(isset($group['options'])) {
                        foreach($group['options'] as $option) {
                            $unique_entities[] = 'alloggio_' . $option['id'];
                            
                            // Determine quality tier based on pricing
                            $standard_price = $option['prezzo_notte_standard'] ?? 0;
                            $premium_price = $option['prezzo_notte_premium'] ?? 0;
                            $luxury_price = $option['prezzo_notte_luxury'] ?? 0;
                            
                            if($luxury_price > 0) $quality_tiers['luxury']++;
                            if($premium_price > 0) $quality_tiers['premium']++;
                            if($standard_price > 0) $quality_tiers['standard']++;
                            
                            if(isset($option['lat'], $option['lng'])) {
                                $geographic_locations[] = array($option['lat'], $option['lng']);
                            }
                        }
                    }
                }
            }
            
            // Analyze servizi options
            if(isset($day['servizi_groups'])) {
                foreach($day['servizi_groups'] as $group) {
                    $options_count = $group['options_count'] ?? 0;
                    $total_servizi_options += $options_count;
                    
                    if($options_count > 1) {
                        $day_has_multiple = true;
                    }
                    
                    // Track unique entities
                    if(isset($group['options'])) {
                        foreach($group['options'] as $option) {
                            $unique_entities[] = 'servizio_' . $option['id'];
                            if(isset($option['lat'], $option['lng'])) {
                                $geographic_locations[] = array($option['lat'], $option['lng']);
                            }
                        }
                    }
                }
            }
            
            if($day_has_multiple) {
                $days_with_multiple++;
            }
        }
        
        $total_options = $total_luoghi_options + $total_alloggi_options + $total_servizi_options;
        $unique_entities_count = count(array_unique($unique_entities));
        $coverage_percentage = $total_days > 0 ? round(($days_with_multiple / $total_days) * 100) : 0;
        
        // Calculate geographic spread
        $geographic_spread = self::calculate_geographic_spread($geographic_locations);
        
        // Calculate flexibility and protection levels
        $average_options_per_day = $total_days > 0 ? round($total_options / $total_days, 1) : 0;
        $flexibility_level = self::determine_flexibility_level($average_options_per_day, $coverage_percentage);
        $protection_level = self::determine_protection_level($total_options, $coverage_percentage, $unique_entities_count);
        $risk_mitigation_score = self::calculate_risk_mitigation_score($total_options, $coverage_percentage, $unique_entities_count, $days_with_multiple);
        
        return array(
            'has_multiple_options' => $total_options > $total_days, // More options than days
            'total_options' => $total_options,
            'total_luoghi_options' => $total_luoghi_options,
            'total_alloggi_options' => $total_alloggi_options,
            'total_servizi_options' => $total_servizi_options,
            'days_with_multiple_options' => $days_with_multiple,
            'coverage_percentage' => $coverage_percentage,
            'average_options_per_day' => $average_options_per_day,
            'unique_entities_count' => $unique_entities_count,
            'flexibility_level' => $flexibility_level,
            'protection_level' => $protection_level,
            'risk_mitigation_score' => $risk_mitigation_score,
            'quality_tiers_available' => $quality_tiers,
            'geographic_spread' => $geographic_spread,
            'average_options_per_category' => $total_days > 0 ? round($total_options / ($total_days * 3), 1) : 0, // 3 categories: luoghi, alloggi, servizi
            'options_distribution' => array(
                'luoghi_percentage' => $total_options > 0 ? round(($total_luoghi_options / $total_options) * 100) : 0,
                'alloggi_percentage' => $total_options > 0 ? round(($total_alloggi_options / $total_options) * 100) : 0,
                'servizi_percentage' => $total_options > 0 ? round(($total_servizi_options / $total_options) * 100) : 0
            ),
            'coverage_details' => array(
                'fully_covered_days' => $days_with_multiple,
                'total_days' => $total_days,
                'coverage_ratio' => $total_days > 0 ? $days_with_multiple / $total_days : 0
            ),
            'backup_availability' => $total_options > $total_days ? 'excellent' : ($total_options == $total_days ? 'basic' : 'insufficient'),
            'operator_flexibility' => $flexibility_level,
            'client_choice_available' => $total_options > $total_days * 2, // More than 2 options per day on average
            'peak_season_ready' => $coverage_percentage >= 75 && $total_options >= $total_days * 2
        );
    }
    
    /**
     * Calculate geographic spread of options
     */
    private static function calculate_geographic_spread($locations) {
        if(count($locations) < 2) {
            return array('spread' => 'limited', 'diversity_score' => 0);
        }
        
        $distances = array();
        for($i = 0; $i < count($locations); $i++) {
            for($j = $i + 1; $j < count($locations); $j++) {
                $distance = self::calculate_distance_between_points($locations[$i], $locations[$j]);
                $distances[] = $distance;
            }
        }
        
        $max_distance = max($distances);
        $avg_distance = array_sum($distances) / count($distances);
        
        $spread = 'limited';
        if($max_distance > 50) $spread = 'excellent';
        elseif($max_distance > 25) $spread = 'good';
        elseif($max_distance > 10) $spread = 'moderate';
        
        return array(
            'spread' => $spread,
            'max_distance_km' => round($max_distance, 1),
            'avg_distance_km' => round($avg_distance, 1),
            'diversity_score' => min(10, round($avg_distance / 5))
        );
    }
    
    /**
     * Calculate distance between two geographic points
     */
    private static function calculate_distance_between_points($point1, $point2) {
        $earth_radius = 6371; // km
        
        $d_lat = deg2rad($point2[0] - $point1[0]);
        $d_lon = deg2rad($point2[1] - $point1[1]);
        
        $a = sin($d_lat/2) * sin($d_lat/2) + 
             cos(deg2rad($point1[0])) * cos(deg2rad($point2[0])) * 
             sin($d_lon/2) * sin($d_lon/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earth_radius * $c;
    }
    
    /**
     * Determine flexibility level based on options availability
     */
    private static function determine_flexibility_level($avg_options_per_day, $coverage_percentage) {
        if($avg_options_per_day >= 3 && $coverage_percentage >= 80) {
            return 'maximum';
        } elseif($avg_options_per_day >= 2 && $coverage_percentage >= 60) {
            return 'high';
        } elseif($avg_options_per_day >= 1.5 && $coverage_percentage >= 40) {
            return 'moderate';
        } else {
            return 'basic';
        }
    }
    
    /**
     * Determine protection level against overbooking
     */
    private static function determine_protection_level($total_options, $coverage_percentage, $unique_entities) {
        $score = 0;
        
        // Options abundance
        if($total_options >= 20) $score += 3;
        elseif($total_options >= 15) $score += 2;
        elseif($total_options >= 10) $score += 1;
        
        // Coverage percentage
        if($coverage_percentage >= 80) $score += 3;
        elseif($coverage_percentage >= 60) $score += 2;
        elseif($coverage_percentage >= 40) $score += 1;
        
        // Entity diversity
        if($unique_entities >= 15) $score += 2;
        elseif($unique_entities >= 10) $score += 1;
        
        if($score >= 7) return 'excellent';
        elseif($score >= 5) return 'good';
        elseif($score >= 3) return 'adequate';
        else return 'basic';
    }
    
    /**
     * Calculate comprehensive risk mitigation score
     */
    private static function calculate_risk_mitigation_score($total_options, $coverage_percentage, $unique_entities, $days_with_multiple) {
        $score = 0;
        
        // Base score from total options
        $score += min(4, floor($total_options / 5)); // Max 4 points, 1 point per 5 options
        
        // Coverage bonus
        $score += ($coverage_percentage / 100) * 3; // Max 3 points
        
        // Entity diversity bonus
        $score += min(2, floor($unique_entities / 8)); // Max 2 points
        
        // Days with multiple options bonus
        $score += min(1, $days_with_multiple / 3); // Max 1 point
        
        return round($score, 1);
    }
    
    /**
     * Calculate options summary across all days
     */
    private static function calculate_options_summary($days_with_entities) {
        $total_luoghi_options = 0;
        $total_alloggi_options = 0;
        $total_servizi_options = 0;
        $days_with_multiple = 0;
        
        foreach($days_with_entities as $day) {
            if(isset($day['options_summary'])) {
                $total_luoghi_options += $day['options_summary']['luoghi_options_count'] ?? 0;
                $total_alloggi_options += $day['options_summary']['alloggi_options_count'] ?? 0;
                $total_servizi_options += $day['options_summary']['servizi_options_count'] ?? 0;
                
                if($day['options_summary']['has_multiple_options'] ?? false) {
                    $days_with_multiple++;
                }
            }
        }
        
        return array(
            'total_luoghi_options' => $total_luoghi_options,
            'total_alloggi_options' => $total_alloggi_options,
            'total_servizi_options' => $total_servizi_options,
            'days_with_multiple_options' => $days_with_multiple,
            'total_days' => count($days_with_entities),
            'coverage_percentage' => count($days_with_entities) > 0 ? round(($days_with_multiple / count($days_with_entities)) * 100) : 0
        );
    }
    
    /**
     * Calculate average number of options per day
     */
    private static function calculate_average_options($days_with_entities) {
        $total_options = 0;
        $total_categories = 0;
        
        foreach($days_with_entities as $day) {
            if(isset($day['options_summary'])) {
                $total_options += ($day['options_summary']['luoghi_options_count'] ?? 0);
                $total_options += ($day['options_summary']['alloggi_options_count'] ?? 0);
                $total_options += ($day['options_summary']['servizi_options_count'] ?? 0);
                
                $categories_with_options = 0;
                if(($day['options_summary']['luoghi_options_count'] ?? 0) > 0) $categories_with_options++;
                if(($day['options_summary']['alloggi_options_count'] ?? 0) > 0) $categories_with_options++;
                if(($day['options_summary']['servizi_options_count'] ?? 0) > 0) $categories_with_options++;
                
                $total_categories += $categories_with_options;
            }
        }
        
        return $total_categories > 0 ? round($total_options / $total_categories, 1) : 0;
    }
    
    /**
     * Send selected entities to client endpoint
     */
    public function send_selection_to_client(WP_REST_Request $request) {
        $params = $request->get_json_params();
        $tour_id = (int)($params['tour_id'] ?? 0);
        $client_email = sanitize_email($params['client_email'] ?? '');
        $client_name = sanitize_text_field($params['client_name'] ?? '');
        
        if(!$tour_id || !$client_email) {
            return rest_ensure_response(array(
                'ok' => false,
                'message' => 'Tour ID e email cliente sono richiesti'
            ));
        }
        
        // Get tour data
        $tour_post = get_post($tour_id);
        if(!$tour_post || $tour_post->post_type !== 'yht_tour') {
            return rest_ensure_response(array(
                'ok' => false,
                'message' => 'Tour non trovato'
            ));
        }
        
        // Get selected entities
        $selected_luoghi = json_decode(get_post_meta($tour_id, 'yht_tour_selected_luoghi', true) ?: '[]', true);
        $selected_alloggi = json_decode(get_post_meta($tour_id, 'yht_tour_selected_alloggi', true) ?: '[]', true);
        $selected_servizi = json_decode(get_post_meta($tour_id, 'yht_tour_selected_servizi', true) ?: '[]', true);
        
        if(empty($selected_luoghi) && empty($selected_alloggi) && empty($selected_servizi)) {
            return rest_ensure_response(array(
                'ok' => false,
                'message' => 'Nessuna entità selezionata per questo tour'
            ));
        }
        
        // Build email content
        $email_content = $this->build_selection_email_content($tour_post, $selected_luoghi, $selected_alloggi, $selected_servizi, $client_name);
        
        // Send email
        $subject = sprintf('La tua selezione tour: %s', $tour_post->post_title);
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        $email_sent = wp_mail($client_email, $subject, $email_content, $headers);
        
        if($email_sent) {
            // Update tour meta with sent timestamp and status
            update_post_meta($tour_id, 'yht_last_sent_to_client', current_time('timestamp'));
            update_post_meta($tour_id, 'yht_entities_selection_status', 'sent_to_client');
            
            return rest_ensure_response(array(
                'ok' => true,
                'message' => 'Selezione inviata con successo al cliente'
            ));
        } else {
            return rest_ensure_response(array(
                'ok' => false,
                'message' => 'Errore nell\'invio dell\'email'
            ));
        }
    }
    
    /**
     * Build email content for selected entities
     */
    private function build_selection_email_content($tour_post, $selected_luoghi, $selected_alloggi, $selected_servizi, $client_name) {
        $tour_title = $tour_post->post_title;
        $tour_description = $tour_post->post_content;
        
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Selezione Tour</title></head><body>';
        $html .= '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">';
        
        // Header
        $html .= '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px;">';
        $html .= '<h1 style="margin: 0; font-size: 24px;">🎯 La Tua Selezione Tour</h1>';
        $html .= '<h2 style="margin: 10px 0 0; font-size: 18px; opacity: 0.9;">' . esc_html($tour_title) . '</h2>';
        $html .= '</div>';
        
        // Greeting
        if($client_name) {
            $html .= '<p>Caro <strong>' . esc_html($client_name) . '</strong>,</p>';
        } else {
            $html .= '<p>Gentile Cliente,</p>';
        }
        
        $html .= '<p>Siamo lieti di condividere con te la selezione finale delle strutture e servizi per il tuo tour. Abbiamo contattato tutte le strutture per garantire la disponibilità nelle date richieste.</p>';
        
        // Tour description if available
        if($tour_description) {
            $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 20px 0;">';
            $html .= '<h3 style="margin: 0 0 10px; color: #2d3436;">📋 Descrizione del Tour</h3>';
            $html .= '<p style="margin: 0;">' . wp_kses_post($tour_description) . '</p>';
            $html .= '</div>';
        }
        
        // Organize by days
        $all_days = array();
        foreach($selected_luoghi as $item) $all_days[] = $item['day'];
        foreach($selected_alloggi as $item) $all_days[] = $item['day'];
        foreach($selected_servizi as $item) $all_days[] = $item['day'];
        $all_days = array_unique($all_days);
        sort($all_days);
        
        $html .= '<h3 style="color: #2d3436; border-bottom: 2px solid #00b894; padding-bottom: 8px;">🗓️ Itinerario Dettagliato</h3>';
        
        foreach($all_days as $day) {
            $html .= '<div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin: 15px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
            $html .= '<h4 style="margin: 0 0 15px; color: #667eea; font-size: 16px;">📅 Giorno ' . $day . '</h4>';
            
            // Find entities for this day
            $day_luoghi = array_filter($selected_luoghi, function($item) use ($day) { return $item['day'] == $day; });
            $day_alloggi = array_filter($selected_alloggi, function($item) use ($day) { return $item['day'] == $day; });
            $day_servizi = array_filter($selected_servizi, function($item) use ($day) { return $item['day'] == $day; });
            
            // Show luoghi
            foreach($day_luoghi as $luogo_item) {
                $luogo_post = get_post($luogo_item['luogo_id']);
                if($luogo_post) {
                    $html .= '<div style="margin: 10px 0; padding: 12px; background: #e8f4f8; border-left: 4px solid #74b9ff; border-radius: 4px;">';
                    $html .= '<strong style="color: #0984e3;">📍 Luogo da Visitare:</strong> ' . esc_html($luogo_post->post_title);
                    $html .= '<br><small style="color: #636e72;">Orario: ' . esc_html($luogo_item['time']) . '</small>';
                    if($luogo_post->post_content) {
                        $html .= '<p style="margin: 8px 0 0; font-size: 14px;">' . wp_trim_words($luogo_post->post_content, 20) . '</p>';
                    }
                    $html .= '</div>';
                }
            }
            
            // Show alloggi
            foreach($day_alloggi as $alloggio_item) {
                $alloggio_post = get_post($alloggio_item['alloggio_id']);
                if($alloggio_post) {
                    $html .= '<div style="margin: 10px 0; padding: 12px; background: #e8f5e8; border-left: 4px solid #00b894; border-radius: 4px;">';
                    $html .= '<strong style="color: #00b894;">🏨 Alloggio:</strong> ' . esc_html($alloggio_post->post_title);
                    $html .= '<br><small style="color: #636e72;">Notti: ' . esc_html($alloggio_item['nights']) . '</small>';
                    if($alloggio_post->post_content) {
                        $html .= '<p style="margin: 8px 0 0; font-size: 14px;">' . wp_trim_words($alloggio_post->post_content, 20) . '</p>';
                    }
                    $html .= '</div>';
                }
            }
            
            // Show servizi
            foreach($day_servizi as $servizio_item) {
                $servizio_post = get_post($servizio_item['servizio_id']);
                if($servizio_post) {
                    $html .= '<div style="margin: 10px 0; padding: 12px; background: #fef4e8; border-left: 4px solid #fdcb6e; border-radius: 4px;">';
                    $html .= '<strong style="color: #e17055;">🍽️ Servizio:</strong> ' . esc_html($servizio_post->post_title);
                    $html .= '<br><small style="color: #636e72;">Orario: ' . esc_html($servizio_item['time']) . '</small>';
                    if($servizio_post->post_content) {
                        $html .= '<p style="margin: 8px 0 0; font-size: 14px;">' . wp_trim_words($servizio_post->post_content, 20) . '</p>';
                    }
                    $html .= '</div>';
                }
            }
            
            $html .= '</div>';
        }
        
        // Footer
        $html .= '<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 30px; text-align: center;">';
        $html .= '<p style="margin: 0; color: #636e72;"><strong>Disponibilità Confermata</strong></p>';
        $html .= '<p style="margin: 10px 0 0; font-size: 14px; color: #636e72;">Tutte le strutture e servizi selezionati sono stati contattati e la disponibilità è stata confermata per le date richieste.</p>';
        $html .= '</div>';
        
        $html .= '<p style="margin-top: 20px;">Per ulteriori informazioni o modifiche, non esitare a contattarci.</p>';
        $html .= '<p>Cordiali saluti,<br><strong>Il Team di Your Hidden Trip</strong></p>';
        
        $html .= '</div></body></html>';
        
        return $html;
    }
    
    /**
     * Admin permission callback
     */
    public function admin_permission_callback() {
        return current_user_can('edit_posts');
    }
}