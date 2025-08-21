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

        $days = YHT_Helpers::duration_to_days($duration);
        $per_day = ($traveler_type === 'active') ? 3 : 2;

        $pool = YHT_Helpers::query_poi($experiences, $areas, $startdate, $days);

        // Generate tours with different profiles
        $tours = array(
            YHT_Helpers::plan_itinerary('Tour Essenziale', $pool, $days, $per_day, array('trekking'=>1,'passeggiata'=>1,'cultura'=>1,'benessere'=>0.6,'enogastronomia'=>0.8)),
            YHT_Helpers::plan_itinerary('Natura & Borghi', $pool, $days, $per_day, array('trekking'=>1.2,'passeggiata'=>1,'cultura'=>0.6,'benessere'=>0.5,'enogastronomia'=>0.8)),
            YHT_Helpers::plan_itinerary('Arte & Sapori', $pool, $days, $per_day, array('trekking'=>0.5,'passeggiata'=>0.9,'cultura'=>1.3,'benessere'=>0.7,'enogastronomia'=>1.1))
        );

        return rest_ensure_response(array(
            'ok' => true,
            'days' => $days,
            'perDay' => $per_day,
            'tours' => $tours
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
            return rest_ensure_response(array('ok' => false, 'message' => 'WooCommerce non attivo'));
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
}