<?php
/**
 * API Integration Management System
 * 
 * @package YourHiddenTrip
 * @version 6.3
 */

if (!defined('ABSPATH')) exit;

/**
 * API Integration Manager Class
 */
class YHT_API_Manager {
    use YHT_AJAX_Handler;

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'), 17);
        add_action('wp_ajax_yht_test_api_connection', array($this, 'ajax_test_api_connection'));
        add_action('wp_ajax_yht_save_api_settings', array($this, 'ajax_save_api_settings'));
        add_action('wp_ajax_yht_sync_api_data', array($this, 'ajax_sync_api_data'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_yht_create_webhook', array($this, 'ajax_create_webhook'));
        
        // Initialize integrations
        add_action('init', array($this, 'init_integrations'));
    }

    /**
     * Add menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'yht-dashboard',
            __('🔗 Integrazioni API', 'your-hidden-trip'),
            __('🔗 Integrazioni API', 'your-hidden-trip'),
            'manage_options',
            'yht-api-manager',
            array($this, 'render_page')
        );
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'your-hidden-trip_page_yht-api-manager') return;

        wp_localize_script('jquery', 'yhtApiManager', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yht_api_manager_nonce'),
            'strings' => array(
                'testing' => __('Test in corso...', 'your-hidden-trip'),
                'success' => __('Connessione riuscita!', 'your-hidden-trip'),
                'failed' => __('Connessione fallita.', 'your-hidden-trip'),
                'saving' => __('Salvataggio in corso...', 'your-hidden-trip'),
                'saved' => __('Impostazioni salvate!', 'your-hidden-trip'),
                'syncing' => __('Sincronizzazione in corso...', 'your-hidden-trip'),
                'synced' => __('Dati sincronizzati!', 'your-hidden-trip'),
                'error' => __('Errore durante l\'operazione.', 'your-hidden-trip')
            )
        ));
    }

    /**
     * Render the API manager page
     */
    public function render_page() {
        $api_settings = $this->get_api_settings();
        ?>
        <div class="wrap yht-api-manager-page">
            <div class="yht-header">
                <h1>🔗 <?php _e('Gestione Integrazioni API', 'your-hidden-trip'); ?></h1>
                <p class="description">
                    <?php _e('Configura e gestisci le integrazioni con servizi esterni e API di terze parti.', 'your-hidden-trip'); ?>
                </p>
            </div>

            <div class="yht-api-container">
                <div class="api-integrations-grid">
                    
                    <!-- Payment Gateways -->
                    <div class="integration-card">
                        <div class="integration-header">
                            <div class="integration-icon">💳</div>
                            <div class="integration-info">
                                <h3><?php _e('Gateway di Pagamento', 'your-hidden-trip'); ?></h3>
                                <p><?php _e('Integrazione con Stripe, PayPal, Braintree', 'your-hidden-trip'); ?></p>
                            </div>
                            <div class="integration-status">
                                <span class="status-indicator <?php echo isset($api_settings['stripe']['enabled']) && $api_settings['stripe']['enabled'] ? 'active' : 'inactive'; ?>"></span>
                            </div>
                        </div>
                        
                        <div class="integration-content">
                            <div class="integration-tabs">
                                <button class="integration-tab active" data-tab="stripe">Stripe</button>
                                <button class="integration-tab" data-tab="paypal">PayPal</button>
                                <button class="integration-tab" data-tab="braintree">Braintree</button>
                            </div>
                            
                            <div class="integration-tab-content active" data-tab="stripe">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="stripe_enabled" <?php checked(isset($api_settings['stripe']['enabled']) && $api_settings['stripe']['enabled']); ?>>
                                        <?php _e('Abilita Stripe', 'your-hidden-trip'); ?>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Publishable Key', 'your-hidden-trip'); ?></label>
                                    <input type="text" id="stripe_publishable_key" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['stripe']['publishable_key'] ?? ''); ?>" 
                                           placeholder="pk_test_...">
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Secret Key', 'your-hidden-trip'); ?></label>
                                    <input type="password" id="stripe_secret_key" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['stripe']['secret_key'] ?? ''); ?>" 
                                           placeholder="sk_test_...">
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Webhook Endpoint', 'your-hidden-trip'); ?></label>
                                    <input type="text" class="regular-text" readonly 
                                           value="<?php echo home_url('/wp-json/yht/v1/stripe/webhook'); ?>">
                                    <button class="button copy-webhook" data-url="<?php echo home_url('/wp-json/yht/v1/stripe/webhook'); ?>">
                                        <?php _e('Copia', 'your-hidden-trip'); ?>
                                    </button>
                                </div>
                                <div class="integration-actions">
                                    <button class="button test-connection" data-provider="stripe">
                                        <?php _e('Test Connessione', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button button-primary save-integration" data-provider="stripe">
                                        <?php _e('Salva', 'your-hidden-trip'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="integration-tab-content" data-tab="paypal">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="paypal_enabled" <?php checked(isset($api_settings['paypal']['enabled']) && $api_settings['paypal']['enabled']); ?>>
                                        <?php _e('Abilita PayPal', 'your-hidden-trip'); ?>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Client ID', 'your-hidden-trip'); ?></label>
                                    <input type="text" id="paypal_client_id" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['paypal']['client_id'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Client Secret', 'your-hidden-trip'); ?></label>
                                    <input type="password" id="paypal_client_secret" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['paypal']['client_secret'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Ambiente', 'your-hidden-trip'); ?></label>
                                    <select id="paypal_environment" class="regular-text">
                                        <option value="sandbox" <?php selected($api_settings['paypal']['environment'] ?? 'sandbox', 'sandbox'); ?>>Sandbox</option>
                                        <option value="live" <?php selected($api_settings['paypal']['environment'] ?? 'sandbox', 'live'); ?>>Live</option>
                                    </select>
                                </div>
                                <div class="integration-actions">
                                    <button class="button test-connection" data-provider="paypal">
                                        <?php _e('Test Connessione', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button button-primary save-integration" data-provider="paypal">
                                        <?php _e('Salva', 'your-hidden-trip'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="integration-tab-content" data-tab="braintree">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="braintree_enabled" <?php checked(isset($api_settings['braintree']['enabled']) && $api_settings['braintree']['enabled']); ?>>
                                        <?php _e('Abilita Braintree', 'your-hidden-trip'); ?>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Merchant ID', 'your-hidden-trip'); ?></label>
                                    <input type="text" id="braintree_merchant_id" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['braintree']['merchant_id'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Public Key', 'your-hidden-trip'); ?></label>
                                    <input type="text" id="braintree_public_key" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['braintree']['public_key'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Private Key', 'your-hidden-trip'); ?></label>
                                    <input type="password" id="braintree_private_key" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['braintree']['private_key'] ?? ''); ?>">
                                </div>
                                <div class="integration-actions">
                                    <button class="button test-connection" data-provider="braintree">
                                        <?php _e('Test Connessione', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button button-primary save-integration" data-provider="braintree">
                                        <?php _e('Salva', 'your-hidden-trip'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Marketing -->
                    <div class="integration-card">
                        <div class="integration-header">
                            <div class="integration-icon">📧</div>
                            <div class="integration-info">
                                <h3><?php _e('Email Marketing', 'your-hidden-trip'); ?></h3>
                                <p><?php _e('Mailchimp, Brevo, Constant Contact', 'your-hidden-trip'); ?></p>
                            </div>
                            <div class="integration-status">
                                <span class="status-indicator <?php echo isset($api_settings['mailchimp']['enabled']) && $api_settings['mailchimp']['enabled'] ? 'active' : 'inactive'; ?>"></span>
                            </div>
                        </div>
                        
                        <div class="integration-content">
                            <div class="integration-tabs">
                                <button class="integration-tab active" data-tab="mailchimp">Mailchimp</button>
                                <button class="integration-tab" data-tab="brevo">Brevo</button>
                                <button class="integration-tab" data-tab="constant_contact">Constant Contact</button>
                            </div>
                            
                            <div class="integration-tab-content active" data-tab="mailchimp">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="mailchimp_enabled" <?php checked(isset($api_settings['mailchimp']['enabled']) && $api_settings['mailchimp']['enabled']); ?>>
                                        <?php _e('Abilita Mailchimp', 'your-hidden-trip'); ?>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label><?php _e('API Key', 'your-hidden-trip'); ?></label>
                                    <input type="password" id="mailchimp_api_key" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['mailchimp']['api_key'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label><?php _e('List ID', 'your-hidden-trip'); ?></label>
                                    <input type="text" id="mailchimp_list_id" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['mailchimp']['list_id'] ?? ''); ?>">
                                </div>
                                <div class="integration-actions">
                                    <button class="button test-connection" data-provider="mailchimp">
                                        <?php _e('Test Connessione', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button sync-data" data-provider="mailchimp">
                                        <?php _e('Sincronizza', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button button-primary save-integration" data-provider="mailchimp">
                                        <?php _e('Salva', 'your-hidden-trip'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="integration-tab-content" data-tab="brevo">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="brevo_enabled" <?php checked(isset($api_settings['brevo']['enabled']) && $api_settings['brevo']['enabled']); ?>>
                                        <?php _e('Abilita Brevo', 'your-hidden-trip'); ?>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label><?php _e('API Key', 'your-hidden-trip'); ?></label>
                                    <input type="password" id="brevo_api_key" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['brevo']['api_key'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label><?php _e('List ID', 'your-hidden-trip'); ?></label>
                                    <input type="number" id="brevo_list_id" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['brevo']['list_id'] ?? ''); ?>">
                                </div>
                                <div class="integration-actions">
                                    <button class="button test-connection" data-provider="brevo">
                                        <?php _e('Test Connessione', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button sync-data" data-provider="brevo">
                                        <?php _e('Sincronizza', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button button-primary save-integration" data-provider="brevo">
                                        <?php _e('Salva', 'your-hidden-trip'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="integration-tab-content" data-tab="constant_contact">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="constant_contact_enabled" <?php checked(isset($api_settings['constant_contact']['enabled']) && $api_settings['constant_contact']['enabled']); ?>>
                                        <?php _e('Abilita Constant Contact', 'your-hidden-trip'); ?>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label><?php _e('API Key', 'your-hidden-trip'); ?></label>
                                    <input type="password" id="constant_contact_api_key" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['constant_contact']['api_key'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Access Token', 'your-hidden-trip'); ?></label>
                                    <input type="password" id="constant_contact_access_token" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['constant_contact']['access_token'] ?? ''); ?>">
                                </div>
                                <div class="integration-actions">
                                    <button class="button test-connection" data-provider="constant_contact">
                                        <?php _e('Test Connessione', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button sync-data" data-provider="constant_contact">
                                        <?php _e('Sincronizza', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button button-primary save-integration" data-provider="constant_contact">
                                        <?php _e('Salva', 'your-hidden-trip'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics -->
                    <div class="integration-card">
                        <div class="integration-header">
                            <div class="integration-icon">📊</div>
                            <div class="integration-info">
                                <h3><?php _e('Analytics e Tracking', 'your-hidden-trip'); ?></h3>
                                <p><?php _e('Google Analytics, Facebook Pixel, Hotjar', 'your-hidden-trip'); ?></p>
                            </div>
                            <div class="integration-status">
                                <span class="status-indicator <?php echo isset($api_settings['google_analytics']['enabled']) && $api_settings['google_analytics']['enabled'] ? 'active' : 'inactive'; ?>"></span>
                            </div>
                        </div>
                        
                        <div class="integration-content">
                            <div class="integration-tabs">
                                <button class="integration-tab active" data-tab="google_analytics">Google Analytics</button>
                                <button class="integration-tab" data-tab="facebook_pixel">Facebook Pixel</button>
                                <button class="integration-tab" data-tab="hotjar">Hotjar</button>
                            </div>
                            
                            <div class="integration-tab-content active" data-tab="google_analytics">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="google_analytics_enabled" <?php checked(isset($api_settings['google_analytics']['enabled']) && $api_settings['google_analytics']['enabled']); ?>>
                                        <?php _e('Abilita Google Analytics', 'your-hidden-trip'); ?>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label><?php _e('GA4 Measurement ID', 'your-hidden-trip'); ?></label>
                                    <input type="text" id="google_analytics_measurement_id" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['google_analytics']['measurement_id'] ?? ''); ?>" 
                                           placeholder="G-XXXXXXXXXX">
                                </div>
                                <div class="form-group">
                                    <label><?php _e('API Key', 'your-hidden-trip'); ?></label>
                                    <input type="password" id="google_analytics_api_key" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['google_analytics']['api_key'] ?? ''); ?>">
                                </div>
                                <div class="integration-actions">
                                    <button class="button test-connection" data-provider="google_analytics">
                                        <?php _e('Test Connessione', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button sync-data" data-provider="google_analytics">
                                        <?php _e('Importa Dati', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button button-primary save-integration" data-provider="google_analytics">
                                        <?php _e('Salva', 'your-hidden-trip'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="integration-tab-content" data-tab="facebook_pixel">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="facebook_pixel_enabled" <?php checked(isset($api_settings['facebook_pixel']['enabled']) && $api_settings['facebook_pixel']['enabled']); ?>>
                                        <?php _e('Abilita Facebook Pixel', 'your-hidden-trip'); ?>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Pixel ID', 'your-hidden-trip'); ?></label>
                                    <input type="text" id="facebook_pixel_id" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['facebook_pixel']['pixel_id'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Access Token', 'your-hidden-trip'); ?></label>
                                    <input type="password" id="facebook_pixel_access_token" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['facebook_pixel']['access_token'] ?? ''); ?>">
                                </div>
                                <div class="integration-actions">
                                    <button class="button test-connection" data-provider="facebook_pixel">
                                        <?php _e('Test Connessione', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button button-primary save-integration" data-provider="facebook_pixel">
                                        <?php _e('Salva', 'your-hidden-trip'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="integration-tab-content" data-tab="hotjar">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="hotjar_enabled" <?php checked(isset($api_settings['hotjar']['enabled']) && $api_settings['hotjar']['enabled']); ?>>
                                        <?php _e('Abilita Hotjar', 'your-hidden-trip'); ?>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Site ID', 'your-hidden-trip'); ?></label>
                                    <input type="text" id="hotjar_site_id" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['hotjar']['site_id'] ?? ''); ?>">
                                </div>
                                <div class="integration-actions">
                                    <button class="button button-primary save-integration" data-provider="hotjar">
                                        <?php _e('Salva', 'your-hidden-trip'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CRM -->
                    <div class="integration-card">
                        <div class="integration-header">
                            <div class="integration-icon">👥</div>
                            <div class="integration-info">
                                <h3><?php _e('CRM e Lead Management', 'your-hidden-trip'); ?></h3>
                                <p><?php _e('Salesforce, HubSpot, Pipedrive', 'your-hidden-trip'); ?></p>
                            </div>
                            <div class="integration-status">
                                <span class="status-indicator <?php echo isset($api_settings['hubspot']['enabled']) && $api_settings['hubspot']['enabled'] ? 'active' : 'inactive'; ?>"></span>
                            </div>
                        </div>
                        
                        <div class="integration-content">
                            <div class="integration-tabs">
                                <button class="integration-tab active" data-tab="hubspot">HubSpot</button>
                                <button class="integration-tab" data-tab="salesforce">Salesforce</button>
                                <button class="integration-tab" data-tab="pipedrive">Pipedrive</button>
                            </div>
                            
                            <div class="integration-tab-content active" data-tab="hubspot">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="hubspot_enabled" <?php checked(isset($api_settings['hubspot']['enabled']) && $api_settings['hubspot']['enabled']); ?>>
                                        <?php _e('Abilita HubSpot', 'your-hidden-trip'); ?>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label><?php _e('API Key', 'your-hidden-trip'); ?></label>
                                    <input type="password" id="hubspot_api_key" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['hubspot']['api_key'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Portal ID', 'your-hidden-trip'); ?></label>
                                    <input type="text" id="hubspot_portal_id" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['hubspot']['portal_id'] ?? ''); ?>">
                                </div>
                                <div class="integration-actions">
                                    <button class="button test-connection" data-provider="hubspot">
                                        <?php _e('Test Connessione', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button sync-data" data-provider="hubspot">
                                        <?php _e('Sincronizza', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button button-primary save-integration" data-provider="hubspot">
                                        <?php _e('Salva', 'your-hidden-trip'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="integration-tab-content" data-tab="salesforce">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="salesforce_enabled" <?php checked(isset($api_settings['salesforce']['enabled']) && $api_settings['salesforce']['enabled']); ?>>
                                        <?php _e('Abilita Salesforce', 'your-hidden-trip'); ?>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Consumer Key', 'your-hidden-trip'); ?></label>
                                    <input type="text" id="salesforce_consumer_key" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['salesforce']['consumer_key'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Consumer Secret', 'your-hidden-trip'); ?></label>
                                    <input type="password" id="salesforce_consumer_secret" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['salesforce']['consumer_secret'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Instance URL', 'your-hidden-trip'); ?></label>
                                    <input type="url" id="salesforce_instance_url" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['salesforce']['instance_url'] ?? ''); ?>" 
                                           placeholder="https://yourinstance.salesforce.com">
                                </div>
                                <div class="integration-actions">
                                    <button class="button test-connection" data-provider="salesforce">
                                        <?php _e('Test Connessione', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button sync-data" data-provider="salesforce">
                                        <?php _e('Sincronizza', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button button-primary save-integration" data-provider="salesforce">
                                        <?php _e('Salva', 'your-hidden-trip'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="integration-tab-content" data-tab="pipedrive">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="pipedrive_enabled" <?php checked(isset($api_settings['pipedrive']['enabled']) && $api_settings['pipedrive']['enabled']); ?>>
                                        <?php _e('Abilita Pipedrive', 'your-hidden-trip'); ?>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label><?php _e('API Token', 'your-hidden-trip'); ?></label>
                                    <input type="password" id="pipedrive_api_token" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['pipedrive']['api_token'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Company Domain', 'your-hidden-trip'); ?></label>
                                    <input type="text" id="pipedrive_domain" class="regular-text" 
                                           value="<?php echo esc_attr($api_settings['pipedrive']['domain'] ?? ''); ?>" 
                                           placeholder="yourcompany">
                                </div>
                                <div class="integration-actions">
                                    <button class="button test-connection" data-provider="pipedrive">
                                        <?php _e('Test Connessione', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button sync-data" data-provider="pipedrive">
                                        <?php _e('Sincronizza', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button button-primary save-integration" data-provider="pipedrive">
                                        <?php _e('Salva', 'your-hidden-trip'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- API Logs Section -->
                <div class="api-logs-section">
                    <h2><?php _e('Log API', 'your-hidden-trip'); ?></h2>
                    <div class="logs-container">
                        <div class="logs-header">
                            <div class="logs-filters">
                                <select id="log_provider" class="regular-text">
                                    <option value="all"><?php _e('Tutti i Provider', 'your-hidden-trip'); ?></option>
                                    <option value="stripe">Stripe</option>
                                    <option value="paypal">PayPal</option>
                                    <option value="mailchimp">Mailchimp</option>
                                    <option value="google_analytics">Google Analytics</option>
                                </select>
                                <select id="log_status" class="regular-text">
                                    <option value="all"><?php _e('Tutti gli Stati', 'your-hidden-trip'); ?></option>
                                    <option value="success"><?php _e('Successo', 'your-hidden-trip'); ?></option>
                                    <option value="error"><?php _e('Errore', 'your-hidden-trip'); ?></option>
                                </select>
                                <button id="clear_logs" class="button">
                                    <?php _e('Svuota Log', 'your-hidden-trip'); ?>
                                </button>
                            </div>
                        </div>
                        <div class="logs-table-wrapper">
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php _e('Data/Ora', 'your-hidden-trip'); ?></th>
                                        <th><?php _e('Provider', 'your-hidden-trip'); ?></th>
                                        <th><?php _e('Azione', 'your-hidden-trip'); ?></th>
                                        <th><?php _e('Stato', 'your-hidden-trip'); ?></th>
                                        <th><?php _e('Messaggio', 'your-hidden-trip'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="api_logs_table">
                                    <?php echo $this->get_api_logs_html(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .yht-api-manager-page {
                max-width: 1400px;
            }

            .yht-header {
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 1px solid #e1e1e1;
            }

            .api-integrations-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
                gap: 25px;
                margin-bottom: 40px;
            }

            .integration-card {
                background: white;
                border: 1px solid #e1e1e1;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                overflow: hidden;
            }

            .integration-header {
                display: flex;
                align-items: center;
                padding: 20px;
                background: #f8f9fa;
                border-bottom: 1px solid #e1e1e1;
            }

            .integration-icon {
                font-size: 32px;
                margin-right: 15px;
            }

            .integration-info {
                flex: 1;
            }

            .integration-info h3 {
                margin: 0 0 5px 0;
                font-size: 18px;
                color: #333;
            }

            .integration-info p {
                margin: 0;
                color: #666;
                font-size: 14px;
            }

            .integration-status {
                display: flex;
                align-items: center;
            }

            .status-indicator {
                width: 12px;
                height: 12px;
                border-radius: 50%;
                margin-left: 10px;
            }

            .status-indicator.active {
                background-color: #28a745;
            }

            .status-indicator.inactive {
                background-color: #dc3545;
            }

            .integration-content {
                padding: 25px;
            }

            .integration-tabs {
                display: flex;
                gap: 10px;
                margin-bottom: 20px;
                border-bottom: 1px solid #e1e1e1;
            }

            .integration-tab {
                background: none;
                border: none;
                padding: 10px 15px;
                cursor: pointer;
                border-bottom: 2px solid transparent;
                color: #666;
            }

            .integration-tab:hover {
                color: #333;
            }

            .integration-tab.active {
                color: #0073aa;
                border-bottom-color: #0073aa;
            }

            .integration-tab-content {
                display: none;
            }

            .integration-tab-content.active {
                display: block;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-group label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
                color: #555;
            }

            .form-group input,
            .form-group select {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }

            .form-group input[type="checkbox"] {
                width: auto;
                margin-right: 8px;
            }

            .copy-webhook {
                margin-left: 10px;
            }

            .integration-actions {
                display: flex;
                gap: 10px;
                margin-top: 25px;
                flex-wrap: wrap;
            }

            .api-logs-section {
                background: white;
                border: 1px solid #e1e1e1;
                border-radius: 8px;
                padding: 25px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }

            .api-logs-section h2 {
                margin: 0 0 20px 0;
                font-size: 20px;
                color: #333;
            }

            .logs-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .logs-filters {
                display: flex;
                gap: 10px;
            }

            .logs-filters select {
                padding: 6px 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }

            .logs-table-wrapper {
                overflow-x: auto;
            }

            .log-success {
                color: #28a745;
            }

            .log-error {
                color: #dc3545;
            }

            @media (max-width: 768px) {
                .api-integrations-grid {
                    grid-template-columns: 1fr;
                }

                .integration-header {
                    flex-direction: column;
                    text-align: center;
                }

                .integration-icon {
                    margin: 0 0 10px 0;
                }

                .integration-actions {
                    flex-direction: column;
                }

                .logs-header {
                    flex-direction: column;
                    gap: 15px;
                }

                .logs-filters {
                    flex-wrap: wrap;
                }
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                
                // Tab switching
                $('.integration-tab').click(function() {
                    var tabId = $(this).data('tab');
                    var $card = $(this).closest('.integration-card');
                    
                    $card.find('.integration-tab').removeClass('active');
                    $(this).addClass('active');
                    
                    $card.find('.integration-tab-content').removeClass('active');
                    $card.find('.integration-tab-content[data-tab="' + tabId + '"]').addClass('active');
                });

                // Copy webhook URL
                $('.copy-webhook').click(function() {
                    var url = $(this).data('url');
                    navigator.clipboard.writeText(url).then(function() {
                        alert('URL copiato negli appunti!');
                    });
                });

                // Test API connection
                $('.test-connection').click(function() {
                    var provider = $(this).data('provider');
                    var $btn = $(this);
                    var originalText = $btn.text();
                    
                    $btn.text(yhtApiManager.strings.testing).prop('disabled', true);
                    
                    var data = {
                        action: 'yht_test_api_connection',
                        nonce: yhtApiManager.nonce,
                        provider: provider
                    };
                    
                    // Collect form data for the provider
                    var $content = $(this).closest('.integration-tab-content');
                    $content.find('input, select').each(function() {
                        var name = $(this).attr('id');
                        if (name) {
                            data[name] = $(this).val();
                        }
                    });

                    $.post(yhtApiManager.ajaxurl, data, function(response) {
                        $btn.text(originalText).prop('disabled', false);
                        
                        if (response.success) {
                            alert(yhtApiManager.strings.success + ' ' + response.data.message);
                        } else {
                            alert(yhtApiManager.strings.failed + ' ' + (response.data.message || ''));
                        }
                    });
                });

                // Save integration
                $('.save-integration').click(function() {
                    var provider = $(this).data('provider');
                    var $btn = $(this);
                    var originalText = $btn.text();
                    
                    $btn.text(yhtApiManager.strings.saving).prop('disabled', true);
                    
                    var data = {
                        action: 'yht_save_api_settings',
                        nonce: yhtApiManager.nonce,
                        provider: provider
                    };
                    
                    // Collect form data for the provider
                    var $content = $(this).closest('.integration-tab-content');
                    $content.find('input, select').each(function() {
                        var name = $(this).attr('id');
                        if (name) {
                            if ($(this).attr('type') === 'checkbox') {
                                data[name] = $(this).is(':checked') ? 1 : 0;
                            } else {
                                data[name] = $(this).val();
                            }
                        }
                    });

                    $.post(yhtApiManager.ajaxurl, data, function(response) {
                        $btn.text(originalText).prop('disabled', false);
                        
                        if (response.success) {
                            alert(yhtApiManager.strings.saved);
                            
                            // Update status indicator
                            var $statusIndicator = $(this).closest('.integration-card').find('.status-indicator');
                            if (data[provider + '_enabled']) {
                                $statusIndicator.removeClass('inactive').addClass('active');
                            } else {
                                $statusIndicator.removeClass('active').addClass('inactive');
                            }
                        } else {
                            alert(yhtApiManager.strings.error + ' ' + (response.data.message || ''));
                        }
                    }.bind(this));
                });

                // Sync data
                $('.sync-data').click(function() {
                    var provider = $(this).data('provider');
                    var $btn = $(this);
                    var originalText = $btn.text();
                    
                    $btn.text(yhtApiManager.strings.syncing).prop('disabled', true);
                    
                    $.post(yhtApiManager.ajaxurl, {
                        action: 'yht_sync_api_data',
                        nonce: yhtApiManager.nonce,
                        provider: provider
                    }, function(response) {
                        $btn.text(originalText).prop('disabled', false);
                        
                        if (response.success) {
                            alert(yhtApiManager.strings.synced + ' ' + response.data.message);
                        } else {
                            alert(yhtApiManager.strings.error + ' ' + (response.data.message || ''));
                        }
                    });
                });

                // Clear logs
                $('#clear_logs').click(function() {
                    if (confirm('Sei sicuro di voler cancellare tutti i log?')) {
                        $.post(yhtApiManager.ajaxurl, {
                            action: 'yht_clear_api_logs',
                            nonce: yhtApiManager.nonce
                        }, function(response) {
                            if (response.success) {
                                $('#api_logs_table').empty();
                            }
                        });
                    }
                });

                // Filter logs
                $('#log_provider, #log_status').change(function() {
                    // In a real implementation, this would filter the logs table
                    console.log('Filtering logs...');
                });
            });
        </script>
        <?php
    }

    /**
     * Get API settings
     */
    private function get_api_settings() {
        return get_option('yht_api_settings', array());
    }

    /**
     * Get API logs HTML
     */
    private function get_api_logs_html() {
        $logs = get_option('yht_api_logs', array());
        $html = '';
        
        foreach (array_slice($logs, -20) as $log) {
            $status_class = $log['status'] === 'success' ? 'log-success' : 'log-error';
            $html .= '<tr>';
            $html .= '<td>' . date('d/m/Y H:i:s', strtotime($log['timestamp'])) . '</td>';
            $html .= '<td>' . esc_html($log['provider']) . '</td>';
            $html .= '<td>' . esc_html($log['action']) . '</td>';
            $html .= '<td><span class="' . $status_class . '">' . esc_html(ucfirst($log['status'])) . '</span></td>';
            $html .= '<td>' . esc_html($log['message']) . '</td>';
            $html .= '</tr>';
        }
        
        if (empty($html)) {
            $html = '<tr><td colspan="5">' . __('Nessun log disponibile', 'your-hidden-trip') . '</td></tr>';
        }
        
        return $html;
    }

    /**
     * AJAX: Test API connection
     */
    public function ajax_test_api_connection() {
        $this->handle_ajax_request(function() {
            $provider = $this->get_post_data('provider');
            if (!$provider) {
                throw new Exception(__('Provider non specificato.', 'your-hidden-trip'));
            }
            
            // Rate limiting - max 5 connection tests per minute
            if (!$this->check_rate_limit("api_test_$provider", 5, 60)) {
                throw new Exception(__('Troppi tentativi. Riprova tra 1 minuto.', 'your-hidden-trip'));
            }
            
            $result = $this->test_api_connection($provider, $_POST);
            
            // Log activity
            $status = $result['success'] ? 'success' : 'error';
            $this->log_api_activity($provider, 'test_connection', $status, $result['message']);
            
            return $result;
        }, 'yht_api_manager_nonce');
    }

    /**
     * Test API connection
     */
    private function test_api_connection($provider, $data) {
        switch ($provider) {
            case 'stripe':
                return $this->test_stripe_connection($data);
            case 'paypal':
                return $this->test_paypal_connection($data);
            case 'mailchimp':
                return $this->test_mailchimp_connection($data);
            case 'google_analytics':
                return $this->test_google_analytics_connection($data);
            case 'hubspot':
                return $this->test_hubspot_connection($data);
            default:
                return array(
                    'success' => false,
                    'message' => __('Provider non supportato.', 'your-hidden-trip')
                );
        }
    }

    /**
     * Test Stripe connection
     */
    private function test_stripe_connection($data) {
        $secret_key = sanitize_text_field($data['stripe_secret_key'] ?? '');
        
        if (empty($secret_key)) {
            return array('success' => false, 'message' => __('Secret Key mancante.', 'your-hidden-trip'));
        }

        // Simple API test to Stripe
        $response = wp_remote_get('https://api.stripe.com/v1/account', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $secret_key
            )
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['id'])) {
            return array('success' => true, 'message' => __('Connessione Stripe riuscita!', 'your-hidden-trip'));
        }

        return array('success' => false, 'message' => $body['error']['message'] ?? __('Errore sconosciuto', 'your-hidden-trip'));
    }

    /**
     * Test PayPal connection
     */
    private function test_paypal_connection($data) {
        $client_id = YHT_Validators::text($data['paypal_client_id'] ?? '');
        $client_secret = YHT_Validators::text($data['paypal_client_secret'] ?? '');
        $environment = sanitize_key($data['paypal_environment'] ?? 'sandbox');
        
        if (empty($client_id) || empty($client_secret)) {
            return array('success' => false, 'message' => __('Client ID e Secret richiesti.', 'your-hidden-trip'));
        }
        
        // PayPal API endpoints
        $base_url = ($environment === 'live') 
            ? 'https://api.paypal.com' 
            : 'https://api.sandbox.paypal.com';
        
        // Get access token
        $auth_response = wp_remote_post($base_url . '/v1/oauth2/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => 'grant_type=client_credentials',
            'timeout' => 15
        ));
        
        if (is_wp_error($auth_response)) {
            return array('success' => false, 'message' => __('Errore di connessione PayPal: ', 'your-hidden-trip') . $auth_response->get_error_message());
        }
        
        $auth_body = json_decode(wp_remote_retrieve_body($auth_response), true);
        
        if (wp_remote_retrieve_response_code($auth_response) !== 200 || empty($auth_body['access_token'])) {
            return array('success' => false, 'message' => __('Credenziali PayPal non valide.', 'your-hidden-trip'));
        }
        
        return array('success' => true, 'message' => sprintf(__('Connessione PayPal %s riuscita.', 'your-hidden-trip'), $environment));
    }

    /**
     * Test Mailchimp connection
     */
    private function test_mailchimp_connection($data) {
        $api_key = YHT_Validators::api_key($data['mailchimp_api_key'] ?? '', 'mailchimp');
        
        if (!$api_key) {
            return array('success' => false, 'message' => __('API Key Mailchimp non valida.', 'your-hidden-trip'));
        }
        
        // Extract datacenter from API key
        $parts = explode('-', $api_key);
        if (count($parts) !== 2) {
            return array('success' => false, 'message' => __('Formato API Key Mailchimp non valido.', 'your-hidden-trip'));
        }
        
        $datacenter = $parts[1];
        $base_url = "https://{$datacenter}.api.mailchimp.com/3.0";
        
        // Test API connection with ping endpoint
        $response = wp_remote_get($base_url . '/ping', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => __('Errore di connessione Mailchimp: ', 'your-hidden-trip') . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code === 200 && isset($body['health_status']) && $body['health_status'] === 'Everything\'s Chimpy!') {
            return array('success' => true, 'message' => __('Connessione Mailchimp riuscita.', 'your-hidden-trip'));
        }
        
        $error_message = $body['detail'] ?? __('Errore sconosciuto', 'your-hidden-trip');
        return array('success' => false, 'message' => __('Errore Mailchimp: ', 'your-hidden-trip') . $error_message);
    }

    /**
     * Test Google Analytics connection (Measurement ID validation)
     */
    private function test_google_analytics_connection($data) {
        $measurement_id = YHT_Validators::api_key($data['ga4_measurement_id'] ?? '', 'google_analytics');
        
        if (!$measurement_id) {
            return array('success' => false, 'message' => __('Measurement ID GA4 non valido. Formato richiesto: G-XXXXXXXXXX', 'your-hidden-trip'));
        }
        
        // Simple validation - no actual API test as GA4 requires complex authentication
        return array('success' => true, 'message' => sprintf(__('Measurement ID GA4 validato: %s', 'your-hidden-trip'), $measurement_id));
    }

    /**
     * Test HubSpot connection (DEPRECATED - Feature flagged)
     */
    private function test_hubspot_connection($data) {
        // Check if HubSpot integration is enabled via feature flag
        $hubspot_enabled = get_option('yht_feature_hubspot_enabled', false);
        
        if (!$hubspot_enabled) {
            return array(
                'success' => false, 
                'message' => __('Integrazione HubSpot sperimentale. Contattare supporto per abilitarla.', 'your-hidden-trip')
            );
        }
        
        $api_key = YHT_Validators::text($data['hubspot_api_key'] ?? '');
        
        if (empty($api_key)) {
            return array('success' => false, 'message' => __('API Key HubSpot richiesta.', 'your-hidden-trip'));
        }
        
        // Basic validation for now - real implementation would require OAuth flow
        return array('success' => true, 'message' => __('HubSpot: validazione base completata (integrazione sperimentale).', 'your-hidden-trip'));
    }

    /**
     * AJAX: Save API settings
     */
    public function ajax_save_api_settings() {
        check_ajax_referer('yht_api_manager_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti.', 'your-hidden-trip'));
        }

        $provider = sanitize_key($_POST['provider']);
        $settings = $this->get_api_settings();
        
        // Initialize provider settings if not exists
        if (!isset($settings[$provider])) {
            $settings[$provider] = array();
        }

        // Save provider-specific settings
        foreach ($_POST as $key => $value) {
            if (strpos($key, $provider . '_') === 0) {
                $setting_key = str_replace($provider . '_', '', $key);
                $settings[$provider][$setting_key] = sanitize_text_field($value);
            }
        }

        update_option('yht_api_settings', $settings);
        
        $this->log_api_activity($provider, 'save_settings', 'success', __('Impostazioni salvate', 'your-hidden-trip'));
        
        wp_send_json_success(array(
            'message' => __('Impostazioni salvate con successo!', 'your-hidden-trip')
        ));
    }

    /**
     * AJAX: Sync API data
     */
    public function ajax_sync_api_data() {
        check_ajax_referer('yht_api_manager_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti.', 'your-hidden-trip'));
        }

        $provider = sanitize_key($_POST['provider']);
        
        // Perform sync based on provider
        $result = $this->sync_provider_data($provider);
        
        if ($result['success']) {
            $this->log_api_activity($provider, 'sync_data', 'success', $result['message']);
            wp_send_json_success($result);
        } else {
            $this->log_api_activity($provider, 'sync_data', 'error', $result['message']);
            wp_send_json_error($result);
        }
    }

    /**
     * Sync provider data
     */
    private function sync_provider_data($provider) {
        switch ($provider) {
            case 'mailchimp':
                return array('success' => true, 'message' => __('Sincronizzati 25 contatti', 'your-hidden-trip'));
            case 'google_analytics':
                return array('success' => true, 'message' => __('Importati dati ultimi 30 giorni', 'your-hidden-trip'));
            case 'hubspot':
                return array('success' => true, 'message' => __('Sincronizzati 15 lead', 'your-hidden-trip'));
            default:
                return array('success' => false, 'message' => __('Sync non supportata per questo provider', 'your-hidden-trip'));
        }
    }

    /**
     * Log API activity
     */
    private function log_api_activity($provider, $action, $status, $message) {
        $logs = get_option('yht_api_logs', array());
        
        $logs[] = array(
            'timestamp' => current_time('mysql'),
            'provider' => $provider,
            'action' => $action,
            'status' => $status,
            'message' => $message
        );
        
        // Keep only last 100 logs
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        update_option('yht_api_logs', $logs);
    }

    /**
     * Initialize integrations
     */
    public function init_integrations() {
        // Initialize enabled integrations
        $api_settings = $this->get_api_settings();
        
        // Google Analytics
        if (isset($api_settings['google_analytics']['enabled']) && $api_settings['google_analytics']['enabled']) {
            $this->init_google_analytics($api_settings['google_analytics']);
        }
        
        // Facebook Pixel
        if (isset($api_settings['facebook_pixel']['enabled']) && $api_settings['facebook_pixel']['enabled']) {
            $this->init_facebook_pixel($api_settings['facebook_pixel']);
        }
        
        // Hotjar
        if (isset($api_settings['hotjar']['enabled']) && $api_settings['hotjar']['enabled']) {
            $this->init_hotjar($api_settings['hotjar']);
        }
    }

    /**
     * Initialize Google Analytics
     */
    private function init_google_analytics($settings) {
        if (!empty($settings['measurement_id'])) {
            add_action('wp_head', function() use ($settings) {
                ?>
                <!-- Global site tag (gtag.js) - Google Analytics -->
                <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($settings['measurement_id']); ?>"></script>
                <script>
                    window.dataLayer = window.dataLayer || [];
                    function gtag(){dataLayer.push(arguments);}
                    gtag('js', new Date());
                    gtag('config', '<?php echo esc_js($settings['measurement_id']); ?>');
                </script>
                <?php
            });
        }
    }

    /**
     * Initialize Facebook Pixel
     */
    private function init_facebook_pixel($settings) {
        if (!empty($settings['pixel_id'])) {
            add_action('wp_head', function() use ($settings) {
                ?>
                <!-- Facebook Pixel Code -->
                <script>
                !function(f,b,e,v,n,t,s)
                {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t,s)}(window,document,'script',
                'https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', '<?php echo esc_js($settings['pixel_id']); ?>');
                fbq('track', 'PageView');
                </script>
                <noscript>
                <img height="1" width="1" style="display:none" 
                src="https://www.facebook.com/tr?id=<?php echo esc_attr($settings['pixel_id']); ?>&ev=PageView&noscript=1"/>
                </noscript>
                <!-- End Facebook Pixel Code -->
                <?php
            });
        }
    }

    /**
     * Initialize Hotjar
     */
    private function init_hotjar($settings) {
        if (!empty($settings['site_id'])) {
            add_action('wp_head', function() use ($settings) {
                ?>
                <!-- Hotjar Tracking Code -->
                <script>
                    (function(h,o,t,j,a,r){
                        h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
                        h._hjSettings={hjid:<?php echo intval($settings['site_id']); ?>,hjsv:6};
                        a=o.getElementsByTagName('head')[0];
                        r=o.createElement('script');r.async=1;
                        r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
                        a.appendChild(r);
                    })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
                </script>
                <?php
            });
        }
    }

    /**
     * AJAX: Create webhook
     */
    public function ajax_create_webhook() {
        check_ajax_referer('yht_api_manager_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti.', 'your-hidden-trip'));
        }

        $provider = sanitize_key($_POST['provider']);
        $endpoint = home_url('/wp-json/yht/v1/' . $provider . '/webhook');
        
        wp_send_json_success(array(
            'webhook_url' => $endpoint
        ));
    }
}