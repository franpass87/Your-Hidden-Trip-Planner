<?php
/**
 * Enhanced Client Portal for Interactive Tour Management
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_Client_Portal {
    
    public function __construct() {
        add_action('init', array($this, 'init_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_client_portal'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_portal_assets'));
        add_shortcode('yht_client_portal', array($this, 'render_client_portal_shortcode'));
    }
    
    /**
     * Initialize rewrite rules for client portal
     */
    public function init_rewrite_rules() {
        add_rewrite_rule('^client-portal/([^/]+)/?', 'index.php?yht_client_portal=1&tour_token=$matches[1]', 'top');
        add_rewrite_tag('%yht_client_portal%', '([^&]+)');
        add_rewrite_tag('%tour_token%', '([^&]+)');
    }
    
    /**
     * Handle client portal requests
     */
    public function handle_client_portal() {
        if (get_query_var('yht_client_portal')) {
            $tour_token = get_query_var('tour_token');
            $this->render_client_portal($tour_token);
            exit;
        }
    }
    
    /**
     * Enqueue portal assets
     */
    public function enqueue_portal_assets() {
        if (get_query_var('yht_client_portal') || is_page() && has_shortcode(get_post()->post_content, 'yht_client_portal')) {
            wp_enqueue_style('yht-client-portal', YHT_PLUGIN_URL . 'assets/css/client-portal.css', array(), YHT_VERSION);
            wp_enqueue_script('yht-client-portal', YHT_PLUGIN_URL . 'assets/js/client-portal.js', array('jquery'), YHT_VERSION, true);
            
            wp_localize_script('yht-client-portal', 'yht_portal_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'rest_url' => rest_url('yht/v1/'),
                'nonce' => wp_create_nonce('yht_portal_nonce')
            ));
        }
    }
    
    /**
     * Render client portal
     */
    public function render_client_portal($tour_token) {
        // Validate token and get tour data
        $tour_data = $this->get_tour_by_token($tour_token);
        
        if (!$tour_data) {
            $this->render_error_page('Tour non trovato o token non valido');
            return;
        }
        
        // Get portal template
        get_header();
        ?>
        <div id="yht-client-portal" class="yht-portal-container">
            <div class="yht-portal-header">
                <h1>🌟 Il Tuo Tour Personalizzato</h1>
                <div class="yht-portal-breadcrumb">
                    <span class="breadcrumb-item active">Selezione Entità</span>
                    <span class="breadcrumb-separator">→</span>
                    <span class="breadcrumb-item">Conferma</span>
                    <span class="breadcrumb-separator">→</span>
                    <span class="breadcrumb-item">Prenotazione</span>
                </div>
            </div>
            
            <div class="yht-portal-content">
                <div class="yht-tour-overview">
                    <div class="tour-info-card">
                        <h2><?php echo esc_html($tour_data['name']); ?></h2>
                        <p class="tour-description"><?php echo esc_html($tour_data['description']); ?></p>
                        <div class="tour-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo count($tour_data['days']); ?></span>
                                <span class="stat-label">Giorni</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $tour_data['total_options']; ?></span>
                                <span class="stat-label">Opzioni Disponibili</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $tour_data['flexibility_score']; ?>/10</span>
                                <span class="stat-label">Flessibilità</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="yht-portal-main">
                    <div class="yht-days-selector">
                        <h3>🗓️ Seleziona le Tue Preferenze per Ogni Giorno</h3>
                        <p class="portal-description">
                            Abbiamo preparato diverse opzioni per ogni categoria. Le tue scelte ci aiuteranno a 
                            creare l'itinerario perfetto per te!
                        </p>
                        
                        <div id="yht-days-container">
                            <?php foreach ($tour_data['days'] as $day): ?>
                                <div class="yht-day-card" data-day="<?php echo $day['day']; ?>">
                                    <div class="day-header">
                                        <h4>📅 Giorno <?php echo $day['day']; ?></h4>
                                        <p class="day-description"><?php echo esc_html($day['description']); ?></p>
                                    </div>
                                    
                                    <?php if (!empty($day['luoghi_options'])): ?>
                                    <div class="entity-category">
                                        <h5>📍 Luoghi da Visitare</h5>
                                        <div class="options-grid">
                                            <?php foreach ($day['luoghi_options'] as $i => $luogo): ?>
                                                <div class="option-card luoghi-option" data-entity-id="<?php echo $luogo['id']; ?>">
                                                    <div class="option-image">
                                                        <?php if ($luogo['thumbnail']): ?>
                                                            <img src="<?php echo esc_url($luogo['thumbnail']); ?>" alt="<?php echo esc_attr($luogo['name']); ?>">
                                                        <?php else: ?>
                                                            <div class="placeholder-image">📍</div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="option-content">
                                                        <h6><?php echo esc_html($luogo['name']); ?></h6>
                                                        <p class="option-price">€<?php echo number_format($luogo['price'], 2); ?></p>
                                                        <p class="option-description"><?php echo esc_html(wp_trim_words($luogo['description'], 15)); ?></p>
                                                        <div class="option-features">
                                                            <?php if ($luogo['family_friendly']): ?><span class="feature">👨‍👩‍👧‍👦 Family</span><?php endif; ?>
                                                            <?php if ($luogo['pet_friendly']): ?><span class="feature">🐕 Pet Friendly</span><?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="option-actions">
                                                        <button class="select-option-btn" data-category="luoghi" data-day="<?php echo $day['day']; ?>" data-entity="<?php echo $luogo['id']; ?>">
                                                            ✨ Preferisco Questo
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($day['alloggi_options'])): ?>
                                    <div class="entity-category">
                                        <h5>🏨 Alloggi</h5>
                                        <div class="options-grid">
                                            <?php foreach ($day['alloggi_options'] as $alloggio): ?>
                                                <div class="option-card alloggi-option" data-entity-id="<?php echo $alloggio['id']; ?>">
                                                    <div class="option-image">
                                                        <?php if ($alloggio['thumbnail']): ?>
                                                            <img src="<?php echo esc_url($alloggio['thumbnail']); ?>" alt="<?php echo esc_attr($alloggio['name']); ?>">
                                                        <?php else: ?>
                                                            <div class="placeholder-image">🏨</div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="option-content">
                                                        <h6><?php echo esc_html($alloggio['name']); ?></h6>
                                                        <p class="option-price">€<?php echo number_format($alloggio['price_per_night'], 2); ?>/notte</p>
                                                        <div class="quality-tier"><?php echo ucfirst($alloggio['quality_tier']); ?></div>
                                                        <p class="option-description"><?php echo esc_html(wp_trim_words($alloggio['description'], 15)); ?></p>
                                                    </div>
                                                    <div class="option-actions">
                                                        <button class="select-option-btn" data-category="alloggi" data-day="<?php echo $day['day']; ?>" data-entity="<?php echo $alloggio['id']; ?>">
                                                            ✨ Preferisco Questo
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($day['servizi_options'])): ?>
                                    <div class="entity-category">
                                        <h5>🍽️ Servizi & Esperienze</h5>
                                        <div class="options-grid">
                                            <?php foreach ($day['servizi_options'] as $servizio): ?>
                                                <div class="option-card servizi-option" data-entity-id="<?php echo $servizio['id']; ?>">
                                                    <div class="option-image">
                                                        <?php if ($servizio['thumbnail']): ?>
                                                            <img src="<?php echo esc_url($servizio['thumbnail']); ?>" alt="<?php echo esc_attr($servizio['name']); ?>">
                                                        <?php else: ?>
                                                            <div class="placeholder-image">🍽️</div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="option-content">
                                                        <h6><?php echo esc_html($servizio['name']); ?></h6>
                                                        <p class="option-price">€<?php echo number_format($servizio['price'], 2); ?></p>
                                                        <p class="service-type"><?php echo esc_html($servizio['service_type']); ?></p>
                                                        <p class="option-description"><?php echo esc_html(wp_trim_words($servizio['description'], 15)); ?></p>
                                                    </div>
                                                    <div class="option-actions">
                                                        <button class="select-option-btn" data-category="servizi" data-day="<?php echo $day['day']; ?>" data-entity="<?php echo $servizio['id']; ?>">
                                                            ✨ Preferisco Questo
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="yht-portal-actions">
                            <div class="selections-summary">
                                <h4>📋 Le Tue Selezioni</h4>
                                <div id="selections-list"></div>
                                <div class="total-estimate">
                                    <strong>Stima Totale: €<span id="total-estimate">0.00</span></strong>
                                </div>
                            </div>
                            
                            <div class="portal-buttons">
                                <button id="save-preferences" class="btn btn-primary" disabled>
                                    💾 Salva le Mie Preferenze
                                </button>
                                <button id="request-booking" class="btn btn-success" style="display:none;">
                                    🎯 Richiedi Prenotazione
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="yht-portal-sidebar">
                        <div class="help-card">
                            <h4>🆘 Hai Bisogno di Aiuto?</h4>
                            <p>Il nostro team è qui per aiutarti a creare il tour perfetto!</p>
                            <div class="contact-options">
                                <a href="tel:+390123456789" class="contact-btn">📞 Chiama Ora</a>
                                <a href="mailto:info@yourhiddentrip.com" class="contact-btn">✉️ Invia Email</a>
                                <button id="open-chat" class="contact-btn">💬 Chat Live</button>
                            </div>
                        </div>
                        
                        <div class="guarantee-card">
                            <h4>✅ Le Nostre Garanzie</h4>
                            <ul>
                                <li>🛡️ Prenotazione Sicura e Protetta</li>
                                <li>🔄 Modifiche Gratuite fino a 48h Prima</li>
                                <li>💰 Miglior Prezzo Garantito</li>
                                <li>⭐ Qualità Certificata</li>
                            </ul>
                        </div>
                        
                        <div class="social-proof">
                            <h4>❤️ Dicono di Noi</h4>
                            <div class="testimonial">
                                <p>"Un'esperienza incredibile! Il sistema di selezione è fantastico."</p>
                                <cite>- Marco R.</cite>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <input type="hidden" id="tour-token" value="<?php echo esc_attr($tour_token); ?>">
        <input type="hidden" id="tour-id" value="<?php echo esc_attr($tour_data['id']); ?>">
        
        <?php
        get_footer();
    }
    
    /**
     * Get tour data by token
     */
    private function get_tour_by_token($token) {
        // This would typically validate the token and return tour data
        // For now, we'll return sample data structure
        
        global $wpdb;
        
        // Look up tour by token (you'd need to implement token system)
        $tour_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'yht_client_token' AND meta_value = %s",
            $token
        ));
        
        if (!$tour_id) {
            return false;
        }
        
        $tour_post = get_post($tour_id);
        if (!$tour_post || $tour_post->post_type !== 'yht_tour') {
            return false;
        }
        
        // Get tour data with options
        $tour_data = $this->format_tour_for_portal($tour_post);
        return $tour_data;
    }
    
    /**
     * Format tour data for client portal
     */
    private function format_tour_for_portal($tour_post) {
        $giorni = get_post_meta($tour_post->ID, 'yht_giorni', true);
        $tour_luoghi = get_post_meta($tour_post->ID, 'yht_tour_luoghi', true);
        $tour_alloggi = get_post_meta($tour_post->ID, 'yht_tour_alloggi', true);
        $tour_servizi = get_post_meta($tour_post->ID, 'yht_tour_servizi', true);
        
        $giorni_data = json_decode($giorni ?: '[]', true);
        $luoghi_data = json_decode($tour_luoghi ?: '[]', true);
        $alloggi_data = json_decode($tour_alloggi ?: '[]', true);
        $servizi_data = json_decode($tour_servizi ?: '[]', true);
        
        $formatted_days = array();
        $total_options = 0;
        
        foreach ($giorni_data as $index => $giorno) {
            $day_num = $index + 1;
            
            $day_data = array(
                'day' => $day_num,
                'description' => $giorno['description'] ?? '',
                'luoghi_options' => array(),
                'alloggi_options' => array(),
                'servizi_options' => array()
            );
            
            // Get luoghi options for this day
            foreach ($luoghi_data as $luoghi_group) {
                if (($luoghi_group['day'] ?? 0) == $day_num) {
                    $luoghi_ids = $luoghi_group['luoghi_ids'] ?? array();
                    foreach ($luoghi_ids as $luogo_id) {
                        $luogo_post = get_post($luogo_id);
                        if ($luogo_post) {
                            $day_data['luoghi_options'][] = array(
                                'id' => $luogo_id,
                                'name' => $luogo_post->post_title,
                                'description' => $luogo_post->post_excerpt ?: wp_trim_words($luogo_post->post_content, 20),
                                'thumbnail' => get_the_post_thumbnail_url($luogo_id, 'medium'),
                                'price' => (float) get_post_meta($luogo_id, 'yht_cost_ingresso', true),
                                'family_friendly' => get_post_meta($luogo_id, 'yht_accesso_family', true),
                                'pet_friendly' => get_post_meta($luogo_id, 'yht_accesso_pet', true)
                            );
                            $total_options++;
                        }
                    }
                }
            }
            
            // Get alloggi options for this day
            foreach ($alloggi_data as $alloggi_group) {
                if (($alloggi_group['day'] ?? 0) == $day_num) {
                    $alloggi_ids = $alloggi_group['alloggi_ids'] ?? array();
                    foreach ($alloggi_ids as $alloggio_id) {
                        $alloggio_post = get_post($alloggio_id);
                        if ($alloggio_post) {
                            $day_data['alloggi_options'][] = array(
                                'id' => $alloggio_id,
                                'name' => $alloggio_post->post_title,
                                'description' => $alloggio_post->post_excerpt ?: wp_trim_words($alloggio_post->post_content, 20),
                                'thumbnail' => get_the_post_thumbnail_url($alloggio_id, 'medium'),
                                'price_per_night' => (float) get_post_meta($alloggio_id, 'yht_prezzo_notte', true),
                                'quality_tier' => get_post_meta($alloggio_id, 'yht_categoria', true) ?: 'standard'
                            );
                            $total_options++;
                        }
                    }
                }
            }
            
            // Get servizi options for this day
            foreach ($servizi_data as $servizi_group) {
                if (($servizi_group['day'] ?? 0) == $day_num) {
                    $servizi_ids = $servizi_group['servizi_ids'] ?? array();
                    foreach ($servizi_ids as $servizio_id) {
                        $servizio_post = get_post($servizio_id);
                        if ($servizio_post) {
                            $day_data['servizi_options'][] = array(
                                'id' => $servizio_id,
                                'name' => $servizio_post->post_title,
                                'description' => $servizio_post->post_excerpt ?: wp_trim_words($servizio_post->post_content, 20),
                                'thumbnail' => get_the_post_thumbnail_url($servizio_id, 'medium'),
                                'price' => (float) get_post_meta($servizio_id, 'yht_prezzo', true),
                                'service_type' => get_post_meta($servizio_id, 'yht_tipo_servizio', true) ?: 'Servizio'
                            );
                            $total_options++;
                        }
                    }
                }
            }
            
            $formatted_days[] = $day_data;
        }
        
        return array(
            'id' => $tour_post->ID,
            'name' => $tour_post->post_title,
            'description' => $tour_post->post_excerpt ?: wp_trim_words($tour_post->post_content, 30),
            'days' => $formatted_days,
            'total_options' => $total_options,
            'flexibility_score' => min(10, round($total_options / count($giorni_data) * 2))
        );
    }
    
    /**
     * Render error page
     */
    private function render_error_page($message) {
        get_header();
        ?>
        <div class="yht-error-page">
            <h1>❌ Errore</h1>
            <p><?php echo esc_html($message); ?></p>
            <a href="/" class="btn">🏠 Torna alla Home</a>
        </div>
        <?php
        get_footer();
    }
    
    /**
     * Shortcode for embedding client portal
     */
    public function render_client_portal_shortcode($atts) {
        $atts = shortcode_atts(array(
            'tour_id' => 0,
            'show_selection' => 'true'
        ), $atts);
        
        if (!$atts['tour_id']) {
            return '<p>Tour ID richiesto per il portale cliente.</p>';
        }
        
        // Generate client portal embed
        ob_start();
        ?>
        <div id="yht-portal-embed" data-tour-id="<?php echo esc_attr($atts['tour_id']); ?>">
            <div class="portal-loading">
                <p>🔄 Caricamento portale cliente...</p>
            </div>
        </div>
        <script>
        // Load portal content via AJAX
        jQuery(document).ready(function($) {
            // Implementation would load portal content
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

// Initialize client portal
new YHT_Client_Portal();