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
                    
                    // Enhanced metadata for multiple options system
                    'has_real_entities' => true,
                    'has_multiple_options' => self::has_multiple_options($custom_tour['days_with_entities']),
                    'entity_summary' => array(
                        'luoghi_count' => count($custom_tour['connected_luoghi']),
                        'alloggi_count' => count($custom_tour['connected_alloggi']),
                        'servizi_count' => count($custom_tour['connected_servizi']),
                        'options_summary' => self::calculate_options_summary($custom_tour['days_with_entities'])
                    ),
                    
                    // Multiple options information for overbooking prevention
                    'overbooking_protection' => array(
                        'enabled' => true,
                        'message' => 'Questo tour include opzioni multiple per ogni categoria per evitare problemi di disponibilità',
                        'average_options_per_day' => self::calculate_average_options($custom_tour['days_with_entities'])
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
     * Calculate all-inclusive price for a tour
     */
    private function calculate_all_inclusive_price($tour, $package_type, $num_pax, $travel_date, $options = array()) {
        $total = 0;
        $breakdown = array();
        
        // Check if this is a custom tour with direct pricing
        if(isset($tour['type']) && $tour['type'] === 'custom' && isset($tour['pricing'])) {
            $base_price = $tour['pricing'][$package_type] ?? $tour['pricing']['standard'] ?? 0;
            $total = $base_price * $num_pax;
            $breakdown['base_tour'] = $total;
            
            // Add accommodation costs if available
            if(!empty($tour['accommodations'])) {
                $accommodation_cost = 0;
                foreach($tour['accommodations'] as $acc) {
                    $price_field = "yht_prezzo_notte_$package_type";
                    $price_per_night = (float)get_post_meta($acc['id'], $price_field, true);
                    if(!$price_per_night) {
                        $price_per_night = (float)get_post_meta($acc['id'], 'yht_prezzo_notte_standard', true) * ($package_type === 'premium' ? 1.3 : ($package_type === 'luxury' ? 1.7 : 1.0));
                    }
                    $accommodation_cost += $price_per_night * (count($tour['giorni'] ?? $tour['days']) ?? 1);
                }
                $total += $accommodation_cost;
                $breakdown['accommodation'] = $accommodation_cost;
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
                $early_checkin_cost = 25 * ($tour['days'] ?? 1);
                $total += $early_checkin_cost;
                $breakdown['early_checkin'] = $early_checkin_cost;
            }
            
            // Late checkout  
            if ($options['late_checkout'] ?? false) {
                $late_checkout_cost = 25 * ($tour['days'] ?? 1);
                $total += $late_checkout_cost;
                $breakdown['late_checkout'] = $late_checkout_cost;
            }
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
            'num_pax' => $num_pax
        );
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
     * Check if tour has multiple options configured
     */
    private static function has_multiple_options($days_with_entities) {
        foreach($days_with_entities as $day) {
            if(isset($day['options_summary']['has_multiple_options']) && $day['options_summary']['has_multiple_options']) {
                return true;
            }
        }
        return false;
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
}