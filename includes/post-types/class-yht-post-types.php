<?php
/**
 * Handle Custom Post Types and Taxonomies
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_Post_Types {
    
    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('init', array($this, 'register_meta_fields'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_yht_luogo', array($this, 'save_luogo_meta'), 10, 1);
        add_action('save_post_yht_tour', array($this, 'save_tour_meta'), 10, 1);
        add_action('save_post_yht_alloggio', array($this, 'save_alloggio_meta'), 10, 1);
        add_action('save_post_yht_servizio', array($this, 'save_servizio_meta'), 10, 1);
        add_action('save_post_yht_booking', array($this, 'save_booking_meta'), 10, 1);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        // CPT Luoghi
        register_post_type('yht_luogo', array(
            'label' => 'Luoghi',
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-location-alt',
            'supports' => array('title','editor','thumbnail','excerpt'),
            'rewrite' => array('slug'=>'luogo'),
        ));

        // CPT Tour (per tour curati a mano)
        register_post_type('yht_tour', array(
            'label' => 'Tour',
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-palmtree',
            'supports' => array('title','editor','thumbnail','excerpt'),
            'rewrite' => array('slug'=>'tour'),
        ));

        // CPT Alloggi
        register_post_type('yht_alloggio', array(
            'label' => 'Alloggi',
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-admin-multisite',
            'supports' => array('title','editor','thumbnail','excerpt'),
            'rewrite' => array('slug'=>'alloggio'),
        ));

        // CPT Servizi (restaurants, car rental, drivers, etc.)
        register_post_type('yht_servizio', array(
            'label' => 'Servizi',
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-food',
            'supports' => array('title','editor','thumbnail','excerpt'),
            'rewrite' => array('slug'=>'servizio'),
        ));

        // CPT Partner (B2B)
        register_post_type('yht_partner', array(
            'label' => 'Partner',
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-groups',
            'supports' => array('title','editor','thumbnail'),
        ));

        // CPT Bookings (for managing customer bookings)
        register_post_type('yht_booking', array(
            'label' => 'Prenotazioni',
            'labels' => array(
                'name' => 'Prenotazioni',
                'singular_name' => 'Prenotazione',
                'add_new' => 'Aggiungi Prenotazione',
                'add_new_item' => 'Nuova Prenotazione',
                'edit_item' => 'Modifica Prenotazione'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => array('title','editor'),
            'capabilities' => array(
                'create_posts' => 'manage_woocommerce'
            ),
            'map_meta_cap' => true
        ));
    }
    
    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        register_taxonomy('yht_esperienza', array('yht_luogo','yht_tour'), array(
            'label'=>'Esperienze', 'public'=>true, 'hierarchical'=>false, 'show_in_rest'=>true
        ));
        register_taxonomy('yht_area', array('yht_luogo','yht_tour'), array(
            'label'=>'Aree', 'public'=>true, 'hierarchical'=>false, 'show_in_rest'=>true
        ));
        register_taxonomy('yht_target', array('yht_luogo','yht_tour','yht_alloggio'), array(
            'label'=>'Target', 'public'=>true, 'hierarchical'=>false, 'show_in_rest'=>true
        ));
        register_taxonomy('yht_stagione', array('yht_luogo','yht_tour'), array(
            'label'=>'Stagionalità', 'public'=>true, 'hierarchical'=>false, 'show_in_rest'=>true
        ));
        register_taxonomy('yht_tipo_servizio', array('yht_servizio'), array(
            'label'=>'Tipo Servizio', 'public'=>true, 'hierarchical'=>false, 'show_in_rest'=>true
        ));
    }
    
    /**
     * Register meta fields
     */
    public function register_meta_fields() {
        $meta_s = array('show_in_rest'=>true, 'single'=>true, 'type'=>'string', 'auth_callback' => '__return_true');

        // Luoghi
        $luogo_fields = array('yht_lat','yht_lng','yht_cost_ingresso','yht_durata_min','yht_orari_note','yht_chiusure_json');
        foreach($luogo_fields as $field) {
            register_post_meta('yht_luogo', $field, $meta_s);
        }
        register_post_meta('yht_luogo','yht_accesso_family',$meta_s);
        register_post_meta('yht_luogo','yht_accesso_pet',$meta_s);
        register_post_meta('yht_luogo','yht_accesso_mobility',$meta_s);

        // Alloggi - Enhanced with all-inclusive pricing
        $alloggio_fields = array('yht_lat','yht_lng','yht_fascia_prezzo','yht_servizi_json','yht_capienza', 
                                'yht_prezzo_notte_standard','yht_prezzo_notte_premium','yht_prezzo_notte_luxury',
                                'yht_incluso_colazione','yht_incluso_pranzo','yht_incluso_cena','yht_disponibilita_json');
        foreach($alloggio_fields as $field) {
            register_post_meta('yht_alloggio', $field, $meta_s);
        }

        // Tour curati - Enhanced pricing and entity relationships
        register_post_meta('yht_tour','yht_giorni',$meta_s);       // JSON dei giorni
        register_post_meta('yht_tour','yht_prezzo_base',$meta_s);  // float
        register_post_meta('yht_tour','yht_prezzo_standard_pax',$meta_s);
        register_post_meta('yht_tour','yht_prezzo_premium_pax',$meta_s);
        register_post_meta('yht_tour','yht_prezzo_luxury_pax',$meta_s);
        
        // Tour entity relationships - JSON arrays storing entity IDs and assignments
        register_post_meta('yht_tour','yht_tour_luoghi',$meta_s);      // JSON: [{day: 1, luoghi: [id1, id2], time: "10:00"}, ...]
        register_post_meta('yht_tour','yht_tour_alloggi',$meta_s);     // JSON: [{day: 1, alloggio_id: 123, nights: 1}, ...]
        register_post_meta('yht_tour','yht_tour_servizi',$meta_s);     // JSON: [{day: 1, servizio_id: 456, type: "ristorante", time: "13:00"}, ...]
        register_post_meta('yht_tour','yht_auto_pricing',$meta_s);     // boolean: whether to auto-calculate pricing from entities

        // Servizi - Enhanced with activity pricing
        $servizio_fields = array('yht_lat','yht_lng','yht_fascia_prezzo','yht_orari','yht_telefono','yht_sito_web',
                                'yht_prezzo_persona','yht_prezzo_fisso','yht_durata_servizio','yht_capacita_max',
                                'yht_prenotazione_richiesta','yht_disponibilita_json');
        foreach($servizio_fields as $field) {
            register_post_meta('yht_servizio', $field, $meta_s);
        }

        // Bookings 
        $booking_fields = array('yht_customer_name','yht_customer_email','yht_customer_phone',
                              'yht_booking_status','yht_booking_reference','yht_total_price','yht_deposit_paid',
                              'yht_travel_date','yht_num_pax','yht_package_type','yht_itinerary_json',
                              'yht_wc_order_id','yht_special_requests');
        foreach($booking_fields as $field) {
            register_post_meta('yht_booking', $field, $meta_s);
        }
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box('yht_luogo_meta','Dati Luogo', array($this, 'luogo_meta_box'), 'yht_luogo','normal','high');
        add_meta_box('yht_tour_meta','Configurazione Tour', array($this, 'tour_meta_box'), 'yht_tour','normal','high');
        add_meta_box('yht_alloggio_meta','Prezzi All-Inclusive', array($this, 'alloggio_meta_box'), 'yht_alloggio','normal','high');
        add_meta_box('yht_servizio_meta','Dati Servizio', array($this, 'servizio_meta_box'), 'yht_servizio','normal','high');
        add_meta_box('yht_booking_meta','Dettagli Prenotazione', array($this, 'booking_meta_box'), 'yht_booking','normal','high');
    }
    
    /**
     * Luogo meta box callback
     */
    public function luogo_meta_box($post) {
        $lat = esc_attr(get_post_meta($post->ID,'yht_lat',true));
        $lng = esc_attr(get_post_meta($post->ID,'yht_lng',true));
        $cst = esc_attr(get_post_meta($post->ID,'yht_cost_ingresso',true));
        $dur = esc_attr(get_post_meta($post->ID,'yht_durata_min',true));
        $fam = esc_attr(get_post_meta($post->ID,'yht_accesso_family',true));
        $pet = esc_attr(get_post_meta($post->ID,'yht_accesso_pet',true));
        $mob = esc_attr(get_post_meta($post->ID,'yht_accesso_mobility',true));
        $ora = esc_textarea(get_post_meta($post->ID,'yht_orari_note',true));
        $chi = get_post_meta($post->ID,'yht_chiusure_json',true);
        if(!$chi) $chi = '[]';
        wp_nonce_field('yht_save_meta','yht_meta_nonce');
        
        include YHT_PLUGIN_PATH . 'includes/admin/views/luogo-meta-box.php';
    }
    
    /**
     * Tour meta box callback
     */
    public function tour_meta_box($post) {
        wp_nonce_field('yht_save_meta','yht_meta_nonce');
        
        $giorni = get_post_meta($post->ID,'yht_giorni',true);
        $prezzo_base = esc_attr(get_post_meta($post->ID,'yht_prezzo_base',true));
        $prezzo_standard = esc_attr(get_post_meta($post->ID,'yht_prezzo_standard_pax',true));
        $prezzo_premium = esc_attr(get_post_meta($post->ID,'yht_prezzo_premium_pax',true));
        $prezzo_luxury = esc_attr(get_post_meta($post->ID,'yht_prezzo_luxury_pax',true));
        
        // Tour entity relationships
        $tour_luoghi = get_post_meta($post->ID,'yht_tour_luoghi',true) ?: '[]';
        $tour_alloggi = get_post_meta($post->ID,'yht_tour_alloggi',true) ?: '[]';
        $tour_servizi = get_post_meta($post->ID,'yht_tour_servizi',true) ?: '[]';
        $auto_pricing = get_post_meta($post->ID,'yht_auto_pricing',true);
        
        $luoghi_data = json_decode($tour_luoghi, true);
        $alloggi_data = json_decode($tour_alloggi, true);
        $servizi_data = json_decode($tour_servizi, true);
        
        if(!is_array($luoghi_data)) $luoghi_data = array();
        if(!is_array($alloggi_data)) $alloggi_data = array();
        if(!is_array($servizi_data)) $servizi_data = array();
        
        // Decode giorni JSON or set empty array if invalid
        if(!$giorni) $giorni = '[]';
        $giorni_data = json_decode($giorni, true);
        if(!is_array($giorni_data)) $giorni_data = array();
        
        // Get available entities for selectors
        $luoghi = get_posts(array('post_type' => 'yht_luogo', 'posts_per_page' => -1, 'post_status' => 'publish'));
        $alloggi = get_posts(array('post_type' => 'yht_alloggio', 'posts_per_page' => -1, 'post_status' => 'publish'));
        $servizi = get_posts(array('post_type' => 'yht_servizio', 'posts_per_page' => -1, 'post_status' => 'publish'));
        ?>
        <style>
            .yht-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
            .yht-grid input[type=text], .yht-grid input[type=number], .yht-grid select{width:100%}
            .yht-full-width{grid-column:1/3}
            .yht-tour-day{background:#f9f9f9;padding:12px;margin:8px 0;border-radius:6px}
            .yht-tour-day h4{margin:0 0 8px;color:#333}
            .yht-tour-day textarea{width:100%;height:60px;resize:vertical}
            .yht-entity-section{background:#fff;border:1px solid #ddd;padding:12px;margin:8px 0;border-radius:4px}
            .yht-entity-section h5{margin:0 0 8px;color:#23282d;font-size:13px;font-weight:600}
            .yht-entity-item{display:flex;align-items:center;gap:8px;margin:4px 0;padding:6px;background:#f6f7f7;border-radius:3px}
            .yht-entity-item select{flex:1;font-size:12px}
            .yht-entity-item input[type=time]{width:80px}
            .yht-entity-item .button-link{color:#a00;text-decoration:none;font-size:12px}
            .yht-add-entity{margin:8px 0 0;padding:4px 8px;font-size:12px}
            .yht-pricing-section{background:#e7f3ff;padding:12px;border-radius:6px;margin:8px 0}
        </style>
        <div class="yht-grid">
            <div class="yht-full-width"><h3>🧮 Configurazione Prezzi</h3></div>
            <div>
                <label><input type="checkbox" name="yht_auto_pricing" value="1" <?php checked($auto_pricing,'1'); ?> id="yht_auto_pricing" /> Calcolo automatico dai costi entità</label>
                <p class="description">Se abilitato, i prezzi verranno calcolati automaticamente dai costi di luoghi, alloggi e servizi selezionati.</p>
            </div>
            <div></div>
            <div class="yht-pricing-section yht-full-width" id="yht_manual_pricing">
                <div class="yht-grid">
                    <div><label>Prezzo Base (€)</label><input type="number" step="0.01" name="yht_prezzo_base" value="<?php echo $prezzo_base; ?>" placeholder="100.00" /></div>
                    <div><label>Prezzo Standard per Persona (€)</label><input type="number" step="0.01" name="yht_prezzo_standard_pax" value="<?php echo $prezzo_standard; ?>" placeholder="150.00" /></div>
                    <div><label>Prezzo Premium per Persona (€)</label><input type="number" step="0.01" name="yht_prezzo_premium_pax" value="<?php echo $prezzo_premium; ?>" placeholder="195.00" /></div>
                    <div><label>Prezzo Luxury per Persona (€)</label><input type="number" step="0.01" name="yht_prezzo_luxury_pax" value="<?php echo $prezzo_luxury; ?>" placeholder="255.00" /></div>
                </div>
            </div>
            
            <div class="yht-full-width"><h3>🗓️ Itinerario e Entità Collegate</h3></div>
            <div class="yht-full-width">
                <p class="description">Configura l'itinerario giorno per giorno e seleziona luoghi, alloggi e servizi reali dal database.</p>
                <div id="yht-giorni-container">
                    <?php if(empty($giorni_data)): ?>
                        <div class="yht-tour-day" data-day="1">
                            <h4>📅 Giorno 1</h4>
                            <textarea name="yht_giorno_1" placeholder="Descrizione attività del primo giorno..."></textarea>
                            
                            <div class="yht-entity-section">
                                <h5>📍 Luoghi da Visitare</h5>
                                <div class="yht-luoghi-container"></div>
                                <button type="button" class="button yht-add-entity yht-add-luogo">+ Aggiungi Luogo</button>
                            </div>
                            
                            <div class="yht-entity-section">
                                <h5>🏨 Alloggio</h5>
                                <div class="yht-alloggi-container"></div>
                                <button type="button" class="button yht-add-entity yht-add-alloggio">+ Aggiungi Alloggio</button>
                            </div>
                            
                            <div class="yht-entity-section">
                                <h5>🍽️ Servizi (Ristoranti, Trasporti, etc.)</h5>
                                <div class="yht-servizi-container"></div>
                                <button type="button" class="button yht-add-entity yht-add-servizio">+ Aggiungi Servizio</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach($giorni_data as $index => $giorno): ?>
                            <?php 
                            $day_num = $index + 1;
                            $day_luoghi = array_filter($luoghi_data, function($item) use ($day_num) { return ($item['day'] ?? 0) == $day_num; });
                            $day_alloggi = array_filter($alloggi_data, function($item) use ($day_num) { return ($item['day'] ?? 0) == $day_num; });
                            $day_servizi = array_filter($servizi_data, function($item) use ($day_num) { return ($item['day'] ?? 0) == $day_num; });
                            ?>
                            <div class="yht-tour-day" data-day="<?php echo $day_num; ?>">
                                <h4>📅 Giorno <?php echo $day_num; ?></h4>
                                <textarea name="yht_giorno_<?php echo $day_num; ?>" placeholder="Descrizione attività..."><?php echo esc_textarea($giorno['description'] ?? ''); ?></textarea>
                                
                                <div class="yht-entity-section">
                                    <h5>📍 Luoghi da Visitare</h5>
                                    <div class="yht-luoghi-container">
                                        <?php foreach($day_luoghi as $luogo_item): ?>
                                            <div class="yht-entity-item">
                                                <select class="yht-luogo-select" data-day="<?php echo $day_num; ?>">
                                                    <option value="">Seleziona luogo...</option>
                                                    <?php foreach($luoghi as $luogo): ?>
                                                        <option value="<?php echo $luogo->ID; ?>" <?php selected($luogo_item['luogo_id'] ?? '', $luogo->ID); ?>><?php echo esc_html($luogo->post_title); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="time" class="yht-luogo-time" value="<?php echo esc_attr($luogo_item['time'] ?? '10:00'); ?>" />
                                                <a href="#" class="button-link yht-remove-entity">Rimuovi</a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="button yht-add-entity yht-add-luogo">+ Aggiungi Luogo</button>
                                </div>
                                
                                <div class="yht-entity-section">
                                    <h5>🏨 Alloggio</h5>
                                    <div class="yht-alloggi-container">
                                        <?php foreach($day_alloggi as $alloggio_item): ?>
                                            <div class="yht-entity-item">
                                                <select class="yht-alloggio-select" data-day="<?php echo $day_num; ?>">
                                                    <option value="">Seleziona alloggio...</option>
                                                    <?php foreach($alloggi as $alloggio): ?>
                                                        <option value="<?php echo $alloggio->ID; ?>" <?php selected($alloggio_item['alloggio_id'] ?? '', $alloggio->ID); ?>><?php echo esc_html($alloggio->post_title); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="number" class="yht-alloggio-nights" placeholder="Notti" min="1" value="<?php echo esc_attr($alloggio_item['nights'] ?? 1); ?>" style="width:60px" />
                                                <a href="#" class="button-link yht-remove-entity">Rimuovi</a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="button yht-add-entity yht-add-alloggio">+ Aggiungi Alloggio</button>
                                </div>
                                
                                <div class="yht-entity-section">
                                    <h5>🍽️ Servizi (Ristoranti, Trasporti, etc.)</h5>
                                    <div class="yht-servizi-container">
                                        <?php foreach($day_servizi as $servizio_item): ?>
                                            <div class="yht-entity-item">
                                                <select class="yht-servizio-select" data-day="<?php echo $day_num; ?>">
                                                    <option value="">Seleziona servizio...</option>
                                                    <?php foreach($servizi as $servizio): ?>
                                                        <option value="<?php echo $servizio->ID; ?>" <?php selected($servizio_item['servizio_id'] ?? '', $servizio->ID); ?>><?php echo esc_html($servizio->post_title); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="time" class="yht-servizio-time" value="<?php echo esc_attr($servizio_item['time'] ?? '13:00'); ?>" />
                                                <a href="#" class="button-link yht-remove-entity">Rimuovi</a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="button yht-add-entity yht-add-servizio">+ Aggiungi Servizio</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p>
                    <button type="button" id="yht-add-day" class="button">+ Aggiungi Giorno</button>
                    <button type="button" id="yht-remove-day" class="button" <?php echo empty($giorni_data) || count($giorni_data) <= 1 ? 'disabled' : ''; ?>>- Rimuovi Ultimo Giorno</button>
                </p>
                <input type="hidden" name="yht_giorni" id="yht_giorni_json" value="<?php echo esc_attr($giorni); ?>" />
                <input type="hidden" name="yht_tour_luoghi" id="yht_tour_luoghi_json" value="<?php echo esc_attr($tour_luoghi); ?>" />
                <input type="hidden" name="yht_tour_alloggi" id="yht_tour_alloggi_json" value="<?php echo esc_attr($tour_alloggi); ?>" />
                <input type="hidden" name="yht_tour_servizi" id="yht_tour_servizi_json" value="<?php echo esc_attr($tour_servizi); ?>" />
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Entity options for selectors
            var luoghiOptions = '<?php foreach($luoghi as $luogo) echo "<option value=\"{$luogo->ID}\">" . esc_js($luogo->post_title) . "</option>"; ?>';
            var alloggiOptions = '<?php foreach($alloggi as $alloggio) echo "<option value=\"{$alloggio->ID}\">" . esc_js($alloggio->post_title) . "</option>"; ?>';
            var serviziOptions = '<?php foreach($servizi as $servizio) echo "<option value=\"{$servizio->ID}\">" . esc_js($servizio->post_title) . "</option>"; ?>';
            
            // Auto pricing toggle
            $('#yht_auto_pricing').change(function() {
                if($(this).is(':checked')) {
                    $('#yht_manual_pricing').hide();
                } else {
                    $('#yht_manual_pricing').show();
                }
            }).trigger('change');
            
            function updateGiorniJSON() {
                var giorni = [];
                $('#yht-giorni-container .yht-tour-day').each(function(index) {
                    var description = $(this).find('textarea').val();
                    if(description.trim()) {
                        giorni.push({
                            day: index + 1,
                            description: description.trim()
                        });
                    }
                });
                $('#yht_giorni_json').val(JSON.stringify(giorni));
            }
            
            function updateEntityJSONs() {
                var luoghi = [];
                var alloggi = [];
                var servizi = [];
                
                $('#yht-giorni-container .yht-tour-day').each(function() {
                    var day = parseInt($(this).data('day'));
                    
                    // Collect luoghi
                    $(this).find('.yht-luoghi-container .yht-entity-item').each(function() {
                        var luogo_id = $(this).find('.yht-luogo-select').val();
                        var time = $(this).find('.yht-luogo-time').val();
                        if(luogo_id) {
                            luoghi.push({
                                day: day,
                                luogo_id: parseInt(luogo_id),
                                time: time
                            });
                        }
                    });
                    
                    // Collect alloggi
                    $(this).find('.yht-alloggi-container .yht-entity-item').each(function() {
                        var alloggio_id = $(this).find('.yht-alloggio-select').val();
                        var nights = $(this).find('.yht-alloggio-nights').val();
                        if(alloggio_id) {
                            alloggi.push({
                                day: day,
                                alloggio_id: parseInt(alloggio_id),
                                nights: parseInt(nights) || 1
                            });
                        }
                    });
                    
                    // Collect servizi
                    $(this).find('.yht-servizi-container .yht-entity-item').each(function() {
                        var servizio_id = $(this).find('.yht-servizio-select').val();
                        var time = $(this).find('.yht-servizio-time').val();
                        if(servizio_id) {
                            servizi.push({
                                day: day,
                                servizio_id: parseInt(servizio_id),
                                time: time
                            });
                        }
                    });
                });
                
                $('#yht_tour_luoghi_json').val(JSON.stringify(luoghi));
                $('#yht_tour_alloggi_json').val(JSON.stringify(alloggi));
                $('#yht_tour_servizi_json').val(JSON.stringify(servizi));
            }
            
            function addNewDay() {
                var dayCount = $('#yht-giorni-container .yht-tour-day').length + 1;
                var newDay = $('<div class="yht-tour-day" data-day="' + dayCount + '">' +
                    '<h4>📅 Giorno ' + dayCount + '</h4>' +
                    '<textarea name="yht_giorno_' + dayCount + '" placeholder="Descrizione attività..."></textarea>' +
                    
                    '<div class="yht-entity-section">' +
                        '<h5>📍 Luoghi da Visitare</h5>' +
                        '<div class="yht-luoghi-container"></div>' +
                        '<button type="button" class="button yht-add-entity yht-add-luogo">+ Aggiungi Luogo</button>' +
                    '</div>' +
                    
                    '<div class="yht-entity-section">' +
                        '<h5>🏨 Alloggio</h5>' +
                        '<div class="yht-alloggi-container"></div>' +
                        '<button type="button" class="button yht-add-entity yht-add-alloggio">+ Aggiungi Alloggio</button>' +
                    '</div>' +
                    
                    '<div class="yht-entity-section">' +
                        '<h5>🍽️ Servizi (Ristoranti, Trasporti, etc.)</h5>' +
                        '<div class="yht-servizi-container"></div>' +
                        '<button type="button" class="button yht-add-entity yht-add-servizio">+ Aggiungi Servizio</button>' +
                    '</div>' +
                '</div>');
                
                $('#yht-giorni-container').append(newDay);
                $('#yht-remove-day').prop('disabled', false);
            }
            
            // Add entity items
            $(document).on('click', '.yht-add-luogo', function() {
                var dayNum = $(this).closest('.yht-tour-day').data('day');
                var newItem = $('<div class="yht-entity-item">' +
                    '<select class="yht-luogo-select" data-day="' + dayNum + '">' +
                        '<option value="">Seleziona luogo...</option>' + luoghiOptions +
                    '</select>' +
                    '<input type="time" class="yht-luogo-time" value="10:00" />' +
                    '<a href="#" class="button-link yht-remove-entity">Rimuovi</a>' +
                '</div>');
                $(this).prev('.yht-luoghi-container').append(newItem);
            });
            
            $(document).on('click', '.yht-add-alloggio', function() {
                var dayNum = $(this).closest('.yht-tour-day').data('day');
                var newItem = $('<div class="yht-entity-item">' +
                    '<select class="yht-alloggio-select" data-day="' + dayNum + '">' +
                        '<option value="">Seleziona alloggio...</option>' + alloggiOptions +
                    '</select>' +
                    '<input type="number" class="yht-alloggio-nights" placeholder="Notti" min="1" value="1" style="width:60px" />' +
                    '<a href="#" class="button-link yht-remove-entity">Rimuovi</a>' +
                '</div>');
                $(this).prev('.yht-alloggi-container').append(newItem);
            });
            
            $(document).on('click', '.yht-add-servizio', function() {
                var dayNum = $(this).closest('.yht-tour-day').data('day');
                var newItem = $('<div class="yht-entity-item">' +
                    '<select class="yht-servizio-select" data-day="' + dayNum + '">' +
                        '<option value="">Seleziona servizio...</option>' + serviziOptions +
                    '</select>' +
                    '<input type="time" class="yht-servizio-time" value="13:00" />' +
                    '<a href="#" class="button-link yht-remove-entity">Rimuovi</a>' +
                '</div>');
                $(this).prev('.yht-servizi-container').append(newItem);
            });
            
            // Remove entity items
            $(document).on('click', '.yht-remove-entity', function(e) {
                e.preventDefault();
                $(this).closest('.yht-entity-item').remove();
                updateEntityJSONs();
            });
            
            $('#yht-add-day').click(function() {
                addNewDay();
            });
            
            $('#yht-remove-day').click(function() {
                var days = $('#yht-giorni-container .yht-tour-day');
                if(days.length > 1) {
                    days.last().remove();
                    if(days.length <= 2) {
                        $(this).prop('disabled', true);
                    }
                }
                updateGiorniJSON();
                updateEntityJSONs();
            });
            
            $(document).on('input change', '#yht-giorni-container textarea, #yht-giorni-container select, #yht-giorni-container input', function() {
                updateGiorniJSON();
                updateEntityJSONs();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save luogo meta data
     */
    public function save_luogo_meta($post_id) {
        if(!isset($_POST['yht_meta_nonce']) || !wp_verify_nonce($_POST['yht_meta_nonce'],'yht_save_meta')) return;
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if(!current_user_can('edit_post',$post_id)) return;

        $fields = array('yht_lat','yht_lng','yht_cost_ingresso','yht_durata_min','yht_orari_note','yht_chiusure_json');
        foreach($fields as $field) {
            if(isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Checkbox fields
        update_post_meta($post_id,'yht_accesso_family', isset($_POST['yht_accesso_family'])?'1':'');
        update_post_meta($post_id,'yht_accesso_pet', isset($_POST['yht_accesso_pet'])?'1':'');
        update_post_meta($post_id,'yht_accesso_mobility', isset($_POST['yht_accesso_mobility'])?'1':'');
    }
    
    /**
     * Save tour meta data
     */
    public function save_tour_meta($post_id) {
        if(!isset($_POST['yht_meta_nonce']) || !wp_verify_nonce($_POST['yht_meta_nonce'],'yht_save_meta')) return;
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if(!current_user_can('edit_post',$post_id)) return;

        // Save pricing fields
        $price_fields = array('yht_prezzo_base','yht_prezzo_standard_pax','yht_prezzo_premium_pax','yht_prezzo_luxury_pax');
        foreach($price_fields as $field) {
            if(isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Save auto pricing setting
        update_post_meta($post_id,'yht_auto_pricing', isset($_POST['yht_auto_pricing']) ? '1' : '');
        
        // Process giorni from individual day textareas
        $giorni_data = array();
        $day_num = 1;
        while(isset($_POST['yht_giorno_' . $day_num])) {
            $description = sanitize_textarea_field($_POST['yht_giorno_' . $day_num]);
            if(!empty(trim($description))) {
                $giorni_data[] = array(
                    'day' => $day_num,
                    'description' => $description
                );
            }
            $day_num++;
        }
        
        // Save as JSON
        update_post_meta($post_id, 'yht_giorni', wp_json_encode($giorni_data));
        
        // Save entity relationships from hidden JSON fields (updated via JavaScript)
        if(isset($_POST['yht_tour_luoghi'])) {
            $luoghi_data = json_decode(stripslashes($_POST['yht_tour_luoghi']), true);
            if(is_array($luoghi_data)) {
                // Validate and sanitize luoghi data
                $clean_luoghi = array();
                foreach($luoghi_data as $item) {
                    if(isset($item['day'], $item['luogo_id']) && is_numeric($item['luogo_id'])) {
                        $clean_luoghi[] = array(
                            'day' => (int)$item['day'],
                            'luogo_id' => (int)$item['luogo_id'],
                            'time' => sanitize_text_field($item['time'] ?? '10:00')
                        );
                    }
                }
                update_post_meta($post_id, 'yht_tour_luoghi', wp_json_encode($clean_luoghi));
            }
        }
        
        if(isset($_POST['yht_tour_alloggi'])) {
            $alloggi_data = json_decode(stripslashes($_POST['yht_tour_alloggi']), true);
            if(is_array($alloggi_data)) {
                // Validate and sanitize alloggi data
                $clean_alloggi = array();
                foreach($alloggi_data as $item) {
                    if(isset($item['day'], $item['alloggio_id']) && is_numeric($item['alloggio_id'])) {
                        $clean_alloggi[] = array(
                            'day' => (int)$item['day'],
                            'alloggio_id' => (int)$item['alloggio_id'],
                            'nights' => (int)($item['nights'] ?? 1)
                        );
                    }
                }
                update_post_meta($post_id, 'yht_tour_alloggi', wp_json_encode($clean_alloggi));
            }
        }
        
        if(isset($_POST['yht_tour_servizi'])) {
            $servizi_data = json_decode(stripslashes($_POST['yht_tour_servizi']), true);
            if(is_array($servizi_data)) {
                // Validate and sanitize servizi data
                $clean_servizi = array();
                foreach($servizi_data as $item) {
                    if(isset($item['day'], $item['servizio_id']) && is_numeric($item['servizio_id'])) {
                        $clean_servizi[] = array(
                            'day' => (int)$item['day'],
                            'servizio_id' => (int)$item['servizio_id'],
                            'time' => sanitize_text_field($item['time'] ?? '13:00')
                        );
                    }
                }
                update_post_meta($post_id, 'yht_tour_servizi', wp_json_encode($clean_servizi));
            }
        }
        
        // If auto pricing is enabled, calculate prices from connected entities
        if(isset($_POST['yht_auto_pricing']) && $_POST['yht_auto_pricing'] === '1') {
            $this->calculate_auto_pricing($post_id);
        }
    }
    
    /**
     * Calculate automatic pricing based on connected entities
     */
    private function calculate_auto_pricing($post_id) {
        $luoghi_json = get_post_meta($post_id,'yht_tour_luoghi',true);
        $alloggi_json = get_post_meta($post_id,'yht_tour_alloggi',true);
        $servizi_json = get_post_meta($post_id,'yht_tour_servizi',true);
        
        $luoghi_data = json_decode($luoghi_json, true) ?: array();
        $alloggi_data = json_decode($alloggi_json, true) ?: array();
        $servizi_data = json_decode($servizi_json, true) ?: array();
        
        $base_cost = 0;
        $standard_cost = 0;
        $premium_cost = 0;
        $luxury_cost = 0;
        
        // Calculate costs from luoghi (entry fees)
        foreach($luoghi_data as $luogo_item) {
            $luogo_id = $luogo_item['luogo_id'];
            $entry_cost = (float)get_post_meta($luogo_id,'yht_cost_ingresso',true);
            $base_cost += $entry_cost;
        }
        
        // Calculate costs from alloggi
        foreach($alloggi_data as $alloggio_item) {
            $alloggio_id = $alloggio_item['alloggio_id'];
            $nights = $alloggio_item['nights'];
            
            $standard_night = (float)get_post_meta($alloggio_id,'yht_prezzo_notte_standard',true);
            $premium_night = (float)get_post_meta($alloggio_id,'yht_prezzo_notte_premium',true);
            $luxury_night = (float)get_post_meta($alloggio_id,'yht_prezzo_notte_luxury',true);
            
            $standard_cost += $standard_night * $nights;
            $premium_cost += $premium_night * $nights;
            $luxury_cost += $luxury_night * $nights;
        }
        
        // Calculate costs from servizi
        foreach($servizi_data as $servizio_item) {
            $servizio_id = $servizio_item['servizio_id'];
            $prezzo_persona = (float)get_post_meta($servizio_id,'yht_prezzo_persona',true);
            $prezzo_fisso = (float)get_post_meta($servizio_id,'yht_prezzo_fisso',true);
            
            // For now, use per-person pricing. In a real system, this might be more complex
            if($prezzo_persona > 0) {
                $standard_cost += $prezzo_persona;
                $premium_cost += $prezzo_persona * 1.2; // Premium includes better service
                $luxury_cost += $prezzo_persona * 1.5;  // Luxury includes premium service
            } elseif($prezzo_fisso > 0) {
                // Fixed costs divided by average group size (e.g., 2 people)
                $per_person_fixed = $prezzo_fisso / 2;
                $standard_cost += $per_person_fixed;
                $premium_cost += $per_person_fixed;
                $luxury_cost += $per_person_fixed;
            }
        }
        
        // Add margin and update prices
        $margin_standard = 1.3; // 30% margin
        $margin_premium = 1.4;   // 40% margin  
        $margin_luxury = 1.6;    // 60% margin
        
        update_post_meta($post_id, 'yht_prezzo_base', round($base_cost, 2));
        update_post_meta($post_id, 'yht_prezzo_standard_pax', round($standard_cost * $margin_standard, 2));
        update_post_meta($post_id, 'yht_prezzo_premium_pax', round($premium_cost * $margin_premium, 2));
        update_post_meta($post_id, 'yht_prezzo_luxury_pax', round($luxury_cost * $margin_luxury, 2));
    }
    
    /**
     * Alloggio meta box callback
     */
    public function alloggio_meta_box($post) {
        wp_nonce_field('yht_save_meta','yht_meta_nonce');
        
        $lat = esc_attr(get_post_meta($post->ID,'yht_lat',true));
        $lng = esc_attr(get_post_meta($post->ID,'yht_lng',true));
        $fascia = esc_attr(get_post_meta($post->ID,'yht_fascia_prezzo',true));
        $capienza = esc_attr(get_post_meta($post->ID,'yht_capienza',true));
        $prezzo_standard = esc_attr(get_post_meta($post->ID,'yht_prezzo_notte_standard',true));
        $prezzo_premium = esc_attr(get_post_meta($post->ID,'yht_prezzo_notte_premium',true));
        $prezzo_luxury = esc_attr(get_post_meta($post->ID,'yht_prezzo_notte_luxury',true));
        $colazione = get_post_meta($post->ID,'yht_incluso_colazione',true);
        $pranzo = get_post_meta($post->ID,'yht_incluso_pranzo',true);
        $cena = get_post_meta($post->ID,'yht_incluso_cena',true);
        
        include YHT_PLUGIN_PATH . 'includes/admin/views/alloggio-meta-box.php';
    }
    
    /**
     * Servizio meta box callback
     */
    public function servizio_meta_box($post) {
        wp_nonce_field('yht_save_meta','yht_meta_nonce');
        
        $lat = esc_attr(get_post_meta($post->ID,'yht_lat',true));
        $lng = esc_attr(get_post_meta($post->ID,'yht_lng',true));
        $fascia = esc_attr(get_post_meta($post->ID,'yht_fascia_prezzo',true));
        $orari = esc_attr(get_post_meta($post->ID,'yht_orari',true));
        $telefono = esc_attr(get_post_meta($post->ID,'yht_telefono',true));
        $sito = esc_attr(get_post_meta($post->ID,'yht_sito_web',true));
        $prezzo_persona = esc_attr(get_post_meta($post->ID,'yht_prezzo_persona',true));
        $prezzo_fisso = esc_attr(get_post_meta($post->ID,'yht_prezzo_fisso',true));
        $durata = esc_attr(get_post_meta($post->ID,'yht_durata_servizio',true));
        $capacita = esc_attr(get_post_meta($post->ID,'yht_capacita_max',true));
        $prenotazione = get_post_meta($post->ID,'yht_prenotazione_richiesta',true);
        ?>
        <div class="yht-grid">
            <div><label>Latitudine</label><input type="text" name="yht_lat" value="<?php echo $lat; ?>" /></div>
            <div><label>Longitudine</label><input type="text" name="yht_lng" value="<?php echo $lng; ?>" /></div>
            <div><label>Fascia prezzo</label><input type="text" name="yht_fascia_prezzo" value="<?php echo $fascia; ?>" /></div>
            <div><label>Orari</label><input type="text" name="yht_orari" value="<?php echo $orari; ?>" /></div>
            <div><label>Telefono</label><input type="text" name="yht_telefono" value="<?php echo $telefono; ?>" /></div>
            <div><label>Sito Web</label><input type="url" name="yht_sito_web" value="<?php echo $sito; ?>" /></div>
            
            <div style="grid-column:1/3;"><h3>Prezzi</h3></div>
            <div><label>Prezzo per persona (€)</label><input type="number" step="0.01" name="yht_prezzo_persona" value="<?php echo $prezzo_persona; ?>" /></div>
            <div><label>Prezzo fisso totale (€)</label><input type="number" step="0.01" name="yht_prezzo_fisso" value="<?php echo $prezzo_fisso; ?>" /></div>
            <div><label>Durata servizio (min)</label><input type="number" name="yht_durata_servizio" value="<?php echo $durata; ?>" /></div>
            <div><label>Capacità massima</label><input type="number" name="yht_capacita_max" value="<?php echo $capacita; ?>" /></div>
            <div><label><input type="checkbox" name="yht_prenotazione_richiesta" value="1" <?php checked($prenotazione,'1'); ?> /> Prenotazione richiesta</label></div>
        </div>
        <?php
    }
    
    /**
     * Booking meta box callback
     */
    public function booking_meta_box($post) {
        $customer_name = esc_attr(get_post_meta($post->ID,'yht_customer_name',true));
        $customer_email = esc_attr(get_post_meta($post->ID,'yht_customer_email',true));
        $customer_phone = esc_attr(get_post_meta($post->ID,'yht_customer_phone',true));
        $booking_status = get_post_meta($post->ID,'yht_booking_status',true);
        $booking_reference = esc_attr(get_post_meta($post->ID,'yht_booking_reference',true));
        $total_price = esc_attr(get_post_meta($post->ID,'yht_total_price',true));
        $travel_date = esc_attr(get_post_meta($post->ID,'yht_travel_date',true));
        $num_pax = esc_attr(get_post_meta($post->ID,'yht_num_pax',true));
        $package_type = esc_attr(get_post_meta($post->ID,'yht_package_type',true));
        $special_requests = esc_textarea(get_post_meta($post->ID,'yht_special_requests',true));
        
        $itinerary = json_decode(get_post_meta($post->ID,'yht_itinerary_json',true), true);
        ?>
        <div class="yht-grid">
            <div><strong>Riferimento:</strong> <?php echo $booking_reference; ?></div>
            <div><strong>Stato:</strong> 
                <select name="yht_booking_status">
                    <option value="pending_payment" <?php selected($booking_status, 'pending_payment'); ?>>In attesa pagamento</option>
                    <option value="confirmed" <?php selected($booking_status, 'confirmed'); ?>>Confermata</option>
                    <option value="cancelled" <?php selected($booking_status, 'cancelled'); ?>>Cancellata</option>
                    <option value="completed" <?php selected($booking_status, 'completed'); ?>>Completata</option>
                </select>
            </div>
            
            <div style="grid-column:1/3;"><h3>Dettagli Cliente</h3></div>
            <div><strong>Nome:</strong> <?php echo $customer_name; ?></div>
            <div><strong>Email:</strong> <a href="mailto:<?php echo $customer_email; ?>"><?php echo $customer_email; ?></a></div>
            <div><strong>Telefono:</strong> <?php echo $customer_phone; ?></div>
            <div></div>
            
            <div style="grid-column:1/3;"><h3>Dettagli Viaggio</h3></div>
            <div><strong>Tour:</strong> <?php echo $itinerary['name'] ?? 'N/D'; ?></div>
            <div><strong>Pacchetto:</strong> <?php echo ucfirst($package_type); ?></div>
            <div><strong>Data partenza:</strong> <?php echo $travel_date; ?></div>
            <div><strong>Numero viaggiatori:</strong> <?php echo $num_pax; ?></div>
            <div><strong>Prezzo totale:</strong> €<?php echo $total_price; ?></div>
            <div></div>
            
            <?php if ($special_requests): ?>
            <div style="grid-column:1/3;"><h3>Richieste speciali</h3></div>
            <div style="grid-column:1/3;"><?php echo nl2br($special_requests); ?></div>
            <?php endif; ?>
        </div>
        <?php
        wp_nonce_field('yht_save_booking_meta','yht_booking_meta_nonce');
    }
    
    /**
     * Save alloggio meta data
     */
    public function save_alloggio_meta($post_id) {
        if(!isset($_POST['yht_meta_nonce']) || !wp_verify_nonce($_POST['yht_meta_nonce'],'yht_save_meta')) return;
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if(!current_user_can('edit_post',$post_id)) return;

        $fields = array('yht_lat','yht_lng','yht_fascia_prezzo','yht_capienza','yht_prezzo_notte_standard',
                       'yht_prezzo_notte_premium','yht_prezzo_notte_luxury');
        foreach($fields as $field) {
            if(isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Checkbox fields
        update_post_meta($post_id,'yht_incluso_colazione', isset($_POST['yht_incluso_colazione'])?'1':'');
        update_post_meta($post_id,'yht_incluso_pranzo', isset($_POST['yht_incluso_pranzo'])?'1':'');
        update_post_meta($post_id,'yht_incluso_cena', isset($_POST['yht_incluso_cena'])?'1':'');
    }
    
    /**
     * Save servizio meta data
     */
    public function save_servizio_meta($post_id) {
        if(!isset($_POST['yht_meta_nonce']) || !wp_verify_nonce($_POST['yht_meta_nonce'],'yht_save_meta')) return;
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if(!current_user_can('edit_post',$post_id)) return;

        $fields = array('yht_lat','yht_lng','yht_fascia_prezzo','yht_orari','yht_telefono','yht_sito_web',
                       'yht_prezzo_persona','yht_prezzo_fisso','yht_durata_servizio','yht_capacita_max');
        foreach($fields as $field) {
            if(isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Checkbox fields
        update_post_meta($post_id,'yht_prenotazione_richiesta', isset($_POST['yht_prenotazione_richiesta'])?'1':'');
    }
    
    /**
     * Save booking meta data
     */
    public function save_booking_meta($post_id) {
        if(!isset($_POST['yht_booking_meta_nonce']) || !wp_verify_nonce($_POST['yht_booking_meta_nonce'],'yht_save_booking_meta')) return;
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if(!current_user_can('edit_post',$post_id)) return;

        // Only allow updating status
        if(isset($_POST['yht_booking_status'])) {
            update_post_meta($post_id, 'yht_booking_status', sanitize_text_field($_POST['yht_booking_status']));
        }
    }
    
    /**
     * Enqueue admin scripts for meta boxes
     */
    public function enqueue_admin_scripts($hook) {
        // Only enqueue on post edit screens for our custom post types
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }
        
        global $post;
        if (!$post || !in_array($post->post_type, ['yht_luogo', 'yht_tour', 'yht_alloggio', 'yht_servizio', 'yht_booking'])) {
            return;
        }
        
        wp_enqueue_script(
            'yht-admin-meta-boxes',
            YHT_PLUGIN_URL . 'assets/js/admin-meta-boxes.js',
            array('jquery'),
            YHT_VER,
            true
        );
    }
}