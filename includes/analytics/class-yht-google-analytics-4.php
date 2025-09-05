<?php
/**
 * Google Analytics 4 integration for Your Hidden Trip Planner
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_Google_Analytics_4 {
    
    /**
     * GA4 Measurement ID
     * @var string
     */
    private $measurement_id;
    
    /**
     * API Secret for Measurement Protocol
     * @var string
     */
    private $api_secret;
    
    /**
     * Initialize GA4 integration
     */
    public function __construct() {
        $settings = get_option('yht_settings', []);
        $this->measurement_id = $settings['ga4_measurement_id'] ?? '';
        $this->api_secret = $settings['ga4_api_secret'] ?? '';
        
        if ($this->measurement_id) {
            add_action('wp_head', array($this, 'add_ga4_tracking_code'), 5);
            add_action('wp_footer', array($this, 'add_enhanced_ecommerce_tracking'));
            add_action('yht_tour_generated', array($this, 'track_tour_generation'), 10, 2);
            add_action('yht_booking_completed', array($this, 'track_booking_completion'), 10, 2);
            add_action('yht_lead_submitted', array($this, 'track_lead_submission'), 10, 2);
        }
    }
    
    /**
     * Add GA4 tracking code to head
     */
    public function add_ga4_tracking_code() {
        if (empty($this->measurement_id)) {
            return;
        }
        
        ?>
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($this->measurement_id); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            
            gtag('config', '<?php echo esc_js($this->measurement_id); ?>', {
                // Enhanced ecommerce
                'send_page_view': true,
                'anonymize_ip': <?php echo get_option('yht_ga4_anonymize_ip', true) ? 'true' : 'false'; ?>,
                // GDPR compliance
                'ads_data_redaction': <?php echo get_option('yht_ga4_ads_redaction', true) ? 'true' : 'false'; ?>,
                // Custom dimensions
                'custom_map': {
                    'dimension1': 'trip_type',
                    'dimension2': 'trip_duration', 
                    'dimension3': 'trip_category',
                    'dimension4': 'user_type'
                }
            });
            
            // YHT specific GA4 helper functions
            window.yhtGA4 = {
                trackEvent: function(eventName, parameters) {
                    if (typeof gtag !== 'undefined') {
                        gtag('event', eventName, parameters);
                    }
                },
                
                trackPageView: function(pagePath, pageTitle) {
                    if (typeof gtag !== 'undefined') {
                        gtag('config', '<?php echo esc_js($this->measurement_id); ?>', {
                            page_path: pagePath,
                            page_title: pageTitle
                        });
                    }
                },
                
                trackTripStep: function(stepName, stepNumber) {
                    this.trackEvent('yht_trip_step', {
                        'step_name': stepName,
                        'step_number': stepNumber,
                        'trip_builder_session': this.getSessionId()
                    });
                },
                
                trackTourGeneration: function(tourData) {
                    this.trackEvent('yht_tour_generated', {
                        'tour_type': tourData.type || 'custom',
                        'tour_duration': tourData.duration || 'unknown',
                        'tour_locations': tourData.locations_count || 0,
                        'tour_price': tourData.estimated_price || 0,
                        'value': tourData.estimated_price || 0,
                        'currency': 'EUR'
                    });
                },
                
                trackBookingStart: function(tourData) {
                    this.trackEvent('begin_checkout', {
                        'currency': 'EUR',
                        'value': tourData.price || 0,
                        'items': [{
                            'item_id': tourData.id || 'generated_tour',
                            'item_name': tourData.title || 'Custom Tour',
                            'item_category': 'Tour',
                            'item_variant': tourData.type || 'custom',
                            'quantity': 1,
                            'price': tourData.price || 0
                        }]
                    });
                },
                
                trackBookingComplete: function(bookingData) {
                    this.trackEvent('purchase', {
                        'transaction_id': bookingData.transaction_id,
                        'value': bookingData.total_amount,
                        'currency': 'EUR',
                        'tax': bookingData.tax_amount || 0,
                        'shipping': bookingData.shipping_amount || 0,
                        'items': bookingData.items || []
                    });
                },
                
                trackLeadSubmission: function(leadData) {
                    this.trackEvent('generate_lead', {
                        'currency': 'EUR',
                        'value': leadData.estimated_value || 80,
                        'lead_type': leadData.type || 'contact_form',
                        'trip_interest': leadData.trip_type || 'general'
                    });
                },
                
                trackSearchUsage: function(searchTerm, resultCount) {
                    this.trackEvent('search', {
                        'search_term': searchTerm,
                        'result_count': resultCount
                    });
                },
                
                trackContentInteraction: function(contentType, contentId, action) {
                    this.trackEvent('yht_content_interaction', {
                        'content_type': contentType,
                        'content_id': contentId,
                        'interaction_type': action
                    });
                },
                
                getSessionId: function() {
                    // Generate or retrieve session ID for trip builder
                    if (!sessionStorage.getItem('yht_session_id')) {
                        sessionStorage.setItem('yht_session_id', 'yht_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9));
                    }
                    return sessionStorage.getItem('yht_session_id');
                },
                
                setUserProperties: function(properties) {
                    if (typeof gtag !== 'undefined') {
                        gtag('config', '<?php echo esc_js($this->measurement_id); ?>', {
                            'user_properties': properties
                        });
                    }
                }
            };
            
            // Track initial page load with YHT context
            <?php if (is_single() && get_post_type() === 'yht_luogo'): ?>
            yhtGA4.trackEvent('view_item', {
                'currency': 'EUR',
                'value': <?php echo get_post_meta(get_the_ID(), 'yht_luogo_price_per_pax', true) ?: 0; ?>,
                'items': [{
                    'item_id': '<?php echo get_the_ID(); ?>',
                    'item_name': '<?php echo esc_js(get_the_title()); ?>',
                    'item_category': 'Location',
                    'item_variant': '<?php echo esc_js(get_post_meta(get_the_ID(), 'yht_luogo_category', true)); ?>',
                    'price': <?php echo get_post_meta(get_the_ID(), 'yht_luogo_price_per_pax', true) ?: 0; ?>
                }]
            });
            <?php elseif (is_single() && get_post_type() === 'yht_tour'): ?>
            yhtGA4.trackEvent('view_item', {
                'currency': 'EUR',
                'value': <?php echo get_post_meta(get_the_ID(), 'yht_tour_price', true) ?: 0; ?>,
                'items': [{
                    'item_id': '<?php echo get_the_ID(); ?>',
                    'item_name': '<?php echo esc_js(get_the_title()); ?>',
                    'item_category': 'Tour',
                    'item_variant': '<?php echo esc_js(get_post_meta(get_the_ID(), 'yht_tour_difficulty', true)); ?>',
                    'price': <?php echo get_post_meta(get_the_ID(), 'yht_tour_price', true) ?: 0; ?>
                }]
            });
            <?php endif; ?>
        </script>
        <?php
    }
    
    /**
     * Add enhanced ecommerce tracking to footer
     */
    public function add_enhanced_ecommerce_tracking() {
        if (empty($this->measurement_id)) {
            return;
        }
        
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Track trip builder interactions
            const tripBuilder = document.querySelector('.yht-trip-builder');
            if (tripBuilder) {
                // Track step completions
                tripBuilder.addEventListener('yht:step_completed', function(e) {
                    yhtGA4.trackTripStep(e.detail.stepName, e.detail.stepNumber);
                });
                
                // Track tour generation
                tripBuilder.addEventListener('yht:tour_generated', function(e) {
                    yhtGA4.trackTourGeneration(e.detail.tourData);
                });
            }
            
            // Track booking button clicks
            document.querySelectorAll('.yht-book-tour, .yht-checkout-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    const tourData = this.dataset.tourData ? JSON.parse(this.dataset.tourData) : {};
                    yhtGA4.trackBookingStart(tourData);
                });
            });
            
            // Track search usage
            const searchForm = document.querySelector('.yht-search-form');
            if (searchForm) {
                searchForm.addEventListener('submit', function(e) {
                    const searchTerm = this.querySelector('input[type="search"]').value;
                    // Track after a short delay to allow search to complete
                    setTimeout(function() {
                        const resultCount = document.querySelectorAll('.yht-search-result').length;
                        yhtGA4.trackSearchUsage(searchTerm, resultCount);
                    }, 1000);
                });
            }
            
            // Track content interactions
            document.querySelectorAll('.yht-luogo-card, .yht-tour-card').forEach(function(card) {
                card.addEventListener('click', function(e) {
                    const contentType = this.classList.contains('yht-luogo-card') ? 'location' : 'tour';
                    const contentId = this.dataset.postId || 'unknown';
                    yhtGA4.trackContentInteraction(contentType, contentId, 'click');
                });
            });
            
            // Track PDF downloads
            document.querySelectorAll('a[href*=".pdf"], .yht-download-pdf').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    yhtGA4.trackEvent('file_download', {
                        'file_name': this.href || this.dataset.filename || 'unknown',
                        'file_extension': 'pdf',
                        'link_text': this.textContent || 'PDF Download'
                    });
                });
            });
            
            // Track external link clicks
            document.querySelectorAll('a[href^="http"]:not([href*="' + location.hostname + '"])').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    yhtGA4.trackEvent('click', {
                        'link_url': this.href,
                        'link_domain': new URL(this.href).hostname,
                        'outbound': true
                    });
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Track tour generation via Measurement Protocol
     * 
     * @param array $tour_data Tour data
     * @param array $user_data User data
     */
    public function track_tour_generation($tour_data, $user_data = []) {
        if (empty($this->api_secret)) {
            return;
        }
        
        $event_data = [
            'client_id' => $this->get_client_id(),
            'events' => [
                [
                    'name' => 'yht_tour_generated_server',
                    'params' => [
                        'tour_type' => $tour_data['type'] ?? 'custom',
                        'tour_duration' => $tour_data['duration'] ?? 0,
                        'tour_locations' => count($tour_data['locations'] ?? []),
                        'estimated_price' => $tour_data['estimated_price'] ?? 0,
                        'user_type' => $user_data['type'] ?? 'guest',
                        'generation_method' => $tour_data['method'] ?? 'manual'
                    ]
                ]
            ]
        ];
        
        $this->send_measurement_protocol_event($event_data);
    }
    
    /**
     * Track booking completion via Measurement Protocol
     * 
     * @param array $booking_data Booking data
     * @param array $user_data User data
     */
    public function track_booking_completion($booking_data, $user_data = []) {
        if (empty($this->api_secret)) {
            return;
        }
        
        $event_data = [
            'client_id' => $this->get_client_id(),
            'events' => [
                [
                    'name' => 'purchase',
                    'params' => [
                        'transaction_id' => $booking_data['transaction_id'],
                        'value' => $booking_data['total_amount'],
                        'currency' => 'EUR',
                        'items' => $this->format_booking_items($booking_data['items'] ?? [])
                    ]
                ]
            ]
        ];
        
        $this->send_measurement_protocol_event($event_data);
    }
    
    /**
     * Track lead submission via Measurement Protocol
     * 
     * @param array $lead_data Lead data
     * @param array $user_data User data
     */
    public function track_lead_submission($lead_data, $user_data = []) {
        if (empty($this->api_secret)) {
            return;
        }
        
        $event_data = [
            'client_id' => $this->get_client_id(),
            'events' => [
                [
                    'name' => 'generate_lead',
                    'params' => [
                        'lead_type' => $lead_data['type'] ?? 'contact_form',
                        'estimated_value' => $lead_data['estimated_value'] ?? 80,
                        'currency' => 'EUR',
                        'trip_interest' => $lead_data['trip_type'] ?? 'general',
                        'source' => $lead_data['source'] ?? 'website'
                    ]
                ]
            ]
        ];
        
        $this->send_measurement_protocol_event($event_data);
    }
    
    /**
     * Send event via Measurement Protocol
     * 
     * @param array $event_data Event data
     */
    private function send_measurement_protocol_event($event_data) {
        $url = "https://www.google-analytics.com/mp/collect?measurement_id={$this->measurement_id}&api_secret={$this->api_secret}";
        
        $response = wp_remote_post($url, [
            'body' => json_encode($event_data),
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'timeout' => 5
        ]);
        
        if (is_wp_error($response)) {
            error_log('YHT GA4 Measurement Protocol Error: ' . $response->get_error_message());
        }
    }
    
    /**
     * Get or generate client ID
     * 
     * @return string
     */
    private function get_client_id() {
        // Try to get GA client ID from cookie
        if (isset($_COOKIE['_ga'])) {
            $parts = explode('.', $_COOKIE['_ga']);
            if (count($parts) >= 4) {
                return $parts[2] . '.' . $parts[3];
            }
        }
        
        // Generate fallback client ID
        if (!isset($_COOKIE['yht_client_id'])) {
            $client_id = time() . '.' . rand(100000000, 999999999);
            setcookie('yht_client_id', $client_id, time() + (365 * 24 * 60 * 60), '/');
            return $client_id;
        }
        
        return $_COOKIE['yht_client_id'];
    }
    
    /**
     * Format booking items for GA4
     * 
     * @param array $items Raw booking items
     * @return array Formatted items
     */
    private function format_booking_items($items) {
        $formatted_items = [];
        
        foreach ($items as $item) {
            $formatted_items[] = [
                'item_id' => $item['id'] ?? 'unknown',
                'item_name' => $item['name'] ?? 'Unknown Item',
                'item_category' => $item['category'] ?? 'Tour',
                'item_variant' => $item['variant'] ?? '',
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item['price'] ?? 0
            ];
        }
        
        return $formatted_items;
    }
    
    /**
     * Get GA4 custom audiences data
     * 
     * @return array
     */
    public function get_custom_audiences() {
        return [
            'frequent_travelers' => [
                'name' => 'Frequent Travelers',
                'description' => 'Users who completed more than 3 bookings',
                'conditions' => [
                    'event_name' => 'purchase',
                    'event_count' => ['>=', 3]
                ]
            ],
            'high_value_customers' => [
                'name' => 'High Value Customers', 
                'description' => 'Users with total purchase value > €500',
                'conditions' => [
                    'event_name' => 'purchase',
                    'parameter_name' => 'value',
                    'total_value' => ['>=', 500]
                ]
            ],
            'tour_generators' => [
                'name' => 'Active Tour Generators',
                'description' => 'Users who generated tours but didn\'t book',
                'conditions' => [
                    'event_name' => 'yht_tour_generated',
                    'event_count' => ['>=', 2]
                ],
                'exclusions' => [
                    'event_name' => 'purchase'
                ]
            ]
        ];
    }
    
    /**
     * Get GA4 conversion goals
     * 
     * @return array
     */
    public function get_conversion_goals() {
        return [
            'tour_completion' => [
                'name' => 'Tour Generation Completion',
                'event_name' => 'yht_tour_generated',
                'value' => 25 // Estimated value per tour generation
            ],
            'booking_completion' => [
                'name' => 'Booking Completion',
                'event_name' => 'purchase',
                'value' => 0 // Dynamic value from purchase
            ],
            'lead_generation' => [
                'name' => 'Lead Generation',
                'event_name' => 'generate_lead',
                'value' => 15 // Estimated value per lead
            ],
            'newsletter_signup' => [
                'name' => 'Newsletter Signup',
                'event_name' => 'sign_up',
                'value' => 5 // Estimated value per signup
            ]
        ];
    }
    
    /**
     * Generate GA4 setup report
     * 
     * @return array
     */
    public function get_setup_report() {
        return [
            'measurement_id' => !empty($this->measurement_id),
            'api_secret' => !empty($this->api_secret),
            'tracking_active' => !empty($this->measurement_id),
            'enhanced_ecommerce' => true,
            'custom_events' => [
                'yht_tour_generated',
                'yht_trip_step',
                'yht_content_interaction',
                'generate_lead'
            ],
            'custom_dimensions' => [
                'trip_type',
                'trip_duration',
                'trip_category',
                'user_type'
            ],
            'conversion_goals' => count($this->get_conversion_goals()),
            'custom_audiences' => count($this->get_custom_audiences())
        ];
    }
}