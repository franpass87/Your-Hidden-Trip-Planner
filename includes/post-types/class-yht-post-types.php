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
        
        // Tour entity relationships - JSON arrays storing multiple entity options per day
        register_post_meta('yht_tour','yht_tour_luoghi',$meta_s);      // JSON: [{day: 1, luoghi_ids: [id1, id2, id3], time: "10:00", note: "Choose one option"}, ...]
        register_post_meta('yht_tour','yht_tour_alloggi',$meta_s);     // JSON: [{day: 1, alloggi_ids: [id1, id2, id3], nights: 1, note: "Multiple accommodation options"}, ...]
        register_post_meta('yht_tour','yht_tour_servizi',$meta_s);     // JSON: [{day: 1, servizi_ids: [id1, id2, id3], time: "13:00", note: "Alternative service options"}, ...]
        register_post_meta('yht_tour','yht_auto_pricing',$meta_s);     // boolean: whether to auto-calculate pricing from entities
        
        // Admin-selected entities for confirmed bookings - NEW
        register_post_meta('yht_tour','yht_tour_selected_luoghi',$meta_s);      // JSON: [{day: 1, luogo_id: id, time: "10:00", status: "confirmed"}, ...]
        register_post_meta('yht_tour','yht_tour_selected_alloggi',$meta_s);     // JSON: [{day: 1, alloggio_id: id, nights: 1, status: "confirmed"}, ...]
        register_post_meta('yht_tour','yht_tour_selected_servizi',$meta_s);     // JSON: [{day: 1, servizio_id: id, time: "13:00", status: "confirmed"}, ...]
        register_post_meta('yht_tour','yht_entities_selection_status',$meta_s); // 'pending', 'contacted', 'confirmed', 'sent_to_client'
        register_post_meta('yht_tour','yht_last_sent_to_client',$meta_s);       // timestamp of last client notification

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
        add_meta_box('yht_tour_selection_meta','🎯 Gestione Selezione Entità per Cliente', array($this, 'tour_selection_meta_box'), 'yht_tour','normal','high');
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
            .yht-entity-item{display:flex;align-items:center;gap:8px;margin:4px 0;padding:6px;background:#f6f7f7;border-radius:3px;border-left:3px solid #0073aa}
            .yht-entity-item.yht-option{border-left-color:#00a32a}
            .yht-entity-item select{flex:1;font-size:12px}
            .yht-entity-item input[type=time]{width:80px}
            .yht-entity-item input[type=number]{width:60px}
            .yht-entity-item .button-link{color:#a00;text-decoration:none;font-size:12px}
            .yht-entity-item .yht-option-label{font-size:11px;color:#666;margin-right:8px;font-weight:600}
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
                <p class="description">Configura l'itinerario giorno per giorno e seleziona <strong>multiple opzioni</strong> di luoghi, alloggi e servizi per evitare problemi di overbooking. Puoi aggiungere fino a 3-4 alternative per categoria.</p>
                <div id="yht-giorni-container">
                    <?php if(empty($giorni_data)): ?>
                        <div class="yht-tour-day" data-day="1">
                            <h4>📅 Giorno 1</h4>
                            <textarea name="yht_giorno_1" placeholder="Descrizione attività del primo giorno..."></textarea>
                            
                            <div class="yht-entity-section">
                                <h5>📍 Luoghi da Visitare <span style="font-size:11px;color:#666;">(Aggiungi 3-4 opzioni alternative)</span></h5>
                                <div class="yht-luoghi-container"></div>
                                <button type="button" class="button yht-add-entity yht-add-luogo">+ Aggiungi Opzione Luogo</button>
                            </div>
                            
                            <div class="yht-entity-section">
                                <h5>🏨 Alloggio <span style="font-size:11px;color:#666;">(Aggiungi 3-4 opzioni alternative)</span></h5>
                                <div class="yht-alloggi-container"></div>
                                <button type="button" class="button yht-add-entity yht-add-alloggio">+ Aggiungi Opzione Alloggio</button>
                            </div>
                            
                            <div class="yht-entity-section">
                                <h5>🍽️ Servizi (Ristoranti, Trasporti, etc.) <span style="font-size:11px;color:#666;">(Aggiungi 3-4 opzioni alternative)</span></h5>
                                <div class="yht-servizi-container"></div>
                                <button type="button" class="button yht-add-entity yht-add-servizio">+ Aggiungi Opzione Servizio</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach($giorni_data as $index => $giorno): ?>
                            <?php 
                            $day_num = $index + 1;
                            
                            // Find entity groups for this day - handle both old and new format
                            $day_luoghi_group = null;
                            $day_alloggi_group = null;
                            $day_servizi_group = null;
                            
                            foreach($luoghi_data as $item) {
                                if(($item['day'] ?? 0) == $day_num) {
                                    $day_luoghi_group = $item;
                                    break;
                                }
                            }
                            
                            foreach($alloggi_data as $item) {
                                if(($item['day'] ?? 0) == $day_num) {
                                    $day_alloggi_group = $item;
                                    break;
                                }
                            }
                            
                            foreach($servizi_data as $item) {
                                if(($item['day'] ?? 0) == $day_num) {
                                    $day_servizi_group = $item;
                                    break;
                                }
                            }
                            ?>
                            <div class="yht-tour-day" data-day="<?php echo $day_num; ?>">
                                <h4>📅 Giorno <?php echo $day_num; ?></h4>
                                <textarea name="yht_giorno_<?php echo $day_num; ?>" placeholder="Descrizione attività..."><?php echo esc_textarea($giorno['description'] ?? ''); ?></textarea>
                                
                                <div class="yht-entity-section">
                                    <h5>📍 Luoghi da Visitare <span style="font-size:11px;color:#666;">(Aggiungi 3-4 opzioni alternative)</span></h5>
                                    <div class="yht-luoghi-container">
                                        <?php 
                                        // Handle both old format (single luogo_id) and new format (array of luoghi_ids)
                                        $luoghi_ids = array();
                                        if($day_luoghi_group) {
                                            if(isset($day_luoghi_group['luoghi_ids']) && is_array($day_luoghi_group['luoghi_ids'])) {
                                                $luoghi_ids = $day_luoghi_group['luoghi_ids']; // New format
                                            } elseif(isset($day_luoghi_group['luogo_id'])) {
                                                $luoghi_ids = array($day_luoghi_group['luogo_id']); // Old format
                                            }
                                        }
                                        
                                        foreach($luoghi_ids as $option_num => $luogo_id): ?>
                                            <div class="yht-entity-item yht-option">
                                                <span class="yht-option-label">Opzione <?php echo ($option_num + 1); ?>:</span>
                                                <select class="yht-luogo-select" data-day="<?php echo $day_num; ?>">
                                                    <option value="">Seleziona luogo...</option>
                                                    <?php foreach($luoghi as $luogo): ?>
                                                        <option value="<?php echo $luogo->ID; ?>" <?php selected($luogo_id, $luogo->ID); ?>><?php echo esc_html($luogo->post_title); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="time" class="yht-luogo-time" value="<?php echo esc_attr($day_luoghi_group['time'] ?? '10:00'); ?>" />
                                                <a href="#" class="button-link yht-remove-entity">Rimuovi</a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="button yht-add-entity yht-add-luogo">+ Aggiungi Opzione Luogo</button>
                                </div>
                                
                                <div class="yht-entity-section">
                                    <h5>🏨 Alloggio <span style="font-size:11px;color:#666;">(Aggiungi 3-4 opzioni alternative)</span></h5>
                                    <div class="yht-alloggi-container">
                                        <?php 
                                        // Handle both old format (single alloggio_id) and new format (array of alloggi_ids)
                                        $alloggi_ids = array();
                                        if($day_alloggi_group) {
                                            if(isset($day_alloggi_group['alloggi_ids']) && is_array($day_alloggi_group['alloggi_ids'])) {
                                                $alloggi_ids = $day_alloggi_group['alloggi_ids']; // New format
                                            } elseif(isset($day_alloggi_group['alloggio_id'])) {
                                                $alloggi_ids = array($day_alloggi_group['alloggio_id']); // Old format
                                            }
                                        }
                                        
                                        foreach($alloggi_ids as $option_num => $alloggio_id): ?>
                                            <div class="yht-entity-item yht-option">
                                                <span class="yht-option-label">Opzione <?php echo ($option_num + 1); ?>:</span>
                                                <select class="yht-alloggio-select" data-day="<?php echo $day_num; ?>">
                                                    <option value="">Seleziona alloggio...</option>
                                                    <?php foreach($alloggi as $alloggio): ?>
                                                        <option value="<?php echo $alloggio->ID; ?>" <?php selected($alloggio_id, $alloggio->ID); ?>><?php echo esc_html($alloggio->post_title); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="number" class="yht-alloggio-nights" placeholder="Notti" min="1" value="<?php echo esc_attr($day_alloggi_group['nights'] ?? 1); ?>" />
                                                <a href="#" class="button-link yht-remove-entity">Rimuovi</a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="button yht-add-entity yht-add-alloggio">+ Aggiungi Opzione Alloggio</button>
                                </div>
                                
                                <div class="yht-entity-section">
                                    <h5>🍽️ Servizi (Ristoranti, Trasporti, etc.) <span style="font-size:11px;color:#666;">(Aggiungi 3-4 opzioni alternative)</span></h5>
                                    <div class="yht-servizi-container">
                                        <?php 
                                        // Handle both old format (single servizio_id) and new format (array of servizi_ids)
                                        $servizi_ids = array();
                                        if($day_servizi_group) {
                                            if(isset($day_servizi_group['servizi_ids']) && is_array($day_servizi_group['servizi_ids'])) {
                                                $servizi_ids = $day_servizi_group['servizi_ids']; // New format
                                            } elseif(isset($day_servizi_group['servizio_id'])) {
                                                $servizi_ids = array($day_servizi_group['servizio_id']); // Old format
                                            }
                                        }
                                        
                                        foreach($servizi_ids as $option_num => $servizio_id): ?>
                                            <div class="yht-entity-item yht-option">
                                                <span class="yht-option-label">Opzione <?php echo ($option_num + 1); ?>:</span>
                                                <select class="yht-servizio-select" data-day="<?php echo $day_num; ?>">
                                                    <option value="">Seleziona servizio...</option>
                                                    <?php foreach($servizi as $servizio): ?>
                                                        <option value="<?php echo $servizio->ID; ?>" <?php selected($servizio_id, $servizio->ID); ?>><?php echo esc_html($servizio->post_title); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="time" class="yht-servizio-time" value="<?php echo esc_attr($day_servizi_group['time'] ?? '13:00'); ?>" />
                                                <a href="#" class="button-link yht-remove-entity">Rimuovi</a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="button yht-add-entity yht-add-servizio">+ Aggiungi Opzione Servizio</button>
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
                var luoghi_groups = {};
                var alloggi_groups = {};
                var servizi_groups = {};
                
                $('#yht-giorni-container .yht-tour-day').each(function() {
                    var day = parseInt($(this).data('day'));
                    
                    // Collect luoghi options per day
                    var luoghi_for_day = [];
                    var time_for_luoghi = '';
                    $(this).find('.yht-luoghi-container .yht-entity-item').each(function() {
                        var luogo_id = $(this).find('.yht-luogo-select').val();
                        var time = $(this).find('.yht-luogo-time').val();
                        if(luogo_id) {
                            luoghi_for_day.push(parseInt(luogo_id));
                            if(!time_for_luoghi) time_for_luoghi = time; // Use first time found
                        }
                    });
                    
                    if(luoghi_for_day.length > 0) {
                        luoghi_groups[day] = {
                            day: day,
                            luoghi_ids: luoghi_for_day,
                            time: time_for_luoghi || '10:00',
                            note: luoghi_for_day.length > 1 ? 'Multiple options available' : ''
                        };
                    }
                    
                    // Collect alloggi options per day
                    var alloggi_for_day = [];
                    var nights_for_alloggi = 1;
                    $(this).find('.yht-alloggi-container .yht-entity-item').each(function() {
                        var alloggio_id = $(this).find('.yht-alloggio-select').val();
                        var nights = $(this).find('.yht-alloggio-nights').val();
                        if(alloggio_id) {
                            alloggi_for_day.push(parseInt(alloggio_id));
                            if(nights) nights_for_alloggi = parseInt(nights);
                        }
                    });
                    
                    if(alloggi_for_day.length > 0) {
                        alloggi_groups[day] = {
                            day: day,
                            alloggi_ids: alloggi_for_day,
                            nights: nights_for_alloggi,
                            note: alloggi_for_day.length > 1 ? 'Multiple accommodation options' : ''
                        };
                    }
                    
                    // Collect servizi options per day
                    var servizi_for_day = [];
                    var time_for_servizi = '';
                    $(this).find('.yht-servizi-container .yht-entity-item').each(function() {
                        var servizio_id = $(this).find('.yht-servizio-select').val();
                        var time = $(this).find('.yht-servizio-time').val();
                        if(servizio_id) {
                            servizi_for_day.push(parseInt(servizio_id));
                            if(!time_for_servizi) time_for_servizi = time; // Use first time found
                        }
                    });
                    
                    if(servizi_for_day.length > 0) {
                        servizi_groups[day] = {
                            day: day,
                            servizi_ids: servizi_for_day,
                            time: time_for_servizi || '13:00',
                            note: servizi_for_day.length > 1 ? 'Multiple service options' : ''
                        };
                    }
                });
                
                // Convert to arrays
                var luoghi = Object.values(luoghi_groups);
                var alloggi = Object.values(alloggi_groups);
                var servizi = Object.values(servizi_groups);
                
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
                        '<h5>📍 Luoghi da Visitare <span style="font-size:11px;color:#666;">(Aggiungi 3-4 opzioni alternative)</span></h5>' +
                        '<div class="yht-luoghi-container"></div>' +
                        '<button type="button" class="button yht-add-entity yht-add-luogo">+ Aggiungi Opzione Luogo</button>' +
                    '</div>' +
                    
                    '<div class="yht-entity-section">' +
                        '<h5>🏨 Alloggio <span style="font-size:11px;color:#666;">(Aggiungi 3-4 opzioni alternative)</span></h5>' +
                        '<div class="yht-alloggi-container"></div>' +
                        '<button type="button" class="button yht-add-entity yht-add-alloggio">+ Aggiungi Opzione Alloggio</button>' +
                    '</div>' +
                    
                    '<div class="yht-entity-section">' +
                        '<h5>🍽️ Servizi (Ristoranti, Trasporti, etc.) <span style="font-size:11px;color:#666;">(Aggiungi 3-4 opzioni alternative)</span></h5>' +
                        '<div class="yht-servizi-container"></div>' +
                        '<button type="button" class="button yht-add-entity yht-add-servizio">+ Aggiungi Opzione Servizio</button>' +
                    '</div>' +
                '</div>');
                
                $('#yht-giorni-container').append(newDay);
                $('#yht-remove-day').prop('disabled', false);
            }
            
            // Add entity items
            $(document).on('click', '.yht-add-luogo', function() {
                var dayNum = $(this).closest('.yht-tour-day').data('day');
                var container = $(this).prev('.yht-luoghi-container');
                var currentCount = container.find('.yht-entity-item').length;
                
                if(currentCount >= 4) {
                    alert('Massimo 4 opzioni per luoghi per giorno.');
                    return;
                }
                
                var optionNum = currentCount + 1;
                var newItem = $('<div class="yht-entity-item yht-option">' +
                    '<span class="yht-option-label">Opzione ' + optionNum + ':</span>' +
                    '<select class="yht-luogo-select" data-day="' + dayNum + '">' +
                        '<option value="">Seleziona luogo...</option>' + luoghiOptions +
                    '</select>' +
                    '<input type="time" class="yht-luogo-time" value="10:00" />' +
                    '<a href="#" class="button-link yht-remove-entity">Rimuovi</a>' +
                '</div>');
                container.append(newItem);
                updateEntityJSONs();
            });
            
            $(document).on('click', '.yht-add-alloggio', function() {
                var dayNum = $(this).closest('.yht-tour-day').data('day');
                var container = $(this).prev('.yht-alloggi-container');
                var currentCount = container.find('.yht-entity-item').length;
                
                if(currentCount >= 4) {
                    alert('Massimo 4 opzioni per alloggi per giorno.');
                    return;
                }
                
                var optionNum = currentCount + 1;
                var newItem = $('<div class="yht-entity-item yht-option">' +
                    '<span class="yht-option-label">Opzione ' + optionNum + ':</span>' +
                    '<select class="yht-alloggio-select" data-day="' + dayNum + '">' +
                        '<option value="">Seleziona alloggio...</option>' + alloggiOptions +
                    '</select>' +
                    '<input type="number" class="yht-alloggio-nights" placeholder="Notti" min="1" value="1" />' +
                    '<a href="#" class="button-link yht-remove-entity">Rimuovi</a>' +
                '</div>');
                container.append(newItem);
                updateEntityJSONs();
            });
            
            $(document).on('click', '.yht-add-servizio', function() {
                var dayNum = $(this).closest('.yht-tour-day').data('day');
                var container = $(this).prev('.yht-servizi-container');
                var currentCount = container.find('.yht-entity-item').length;
                
                if(currentCount >= 4) {
                    alert('Massimo 4 opzioni per servizi per giorno.');
                    return;
                }
                
                var optionNum = currentCount + 1;
                var newItem = $('<div class="yht-entity-item yht-option">' +
                    '<span class="yht-option-label">Opzione ' + optionNum + ':</span>' +
                    '<select class="yht-servizio-select" data-day="' + dayNum + '">' +
                        '<option value="">Seleziona servizio...</option>' + serviziOptions +
                    '</select>' +
                    '<input type="time" class="yht-servizio-time" value="13:00" />' +
                    '<a href="#" class="button-link yht-remove-entity">Rimuovi</a>' +
                '</div>');
                container.append(newItem);
                updateEntityJSONs();
            });
            
            // Remove entity items
            $(document).on('click', '.yht-remove-entity', function(e) {
                e.preventDefault();
                var container = $(this).closest('.yht-entity-item').parent();
                $(this).closest('.yht-entity-item').remove();
                
                // Renumber remaining options
                container.find('.yht-entity-item').each(function(index) {
                    $(this).find('.yht-option-label').text('Opzione ' + (index + 1) + ':');
                });
                
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
     * Tour selection meta box callback - Admin interface for selecting specific entities to send to clients
     */
    public function tour_selection_meta_box($post) {
        wp_nonce_field('yht_save_meta','yht_meta_nonce');
        
        // Get all entity options for this tour
        $tour_luoghi = get_post_meta($post->ID,'yht_tour_luoghi',true) ?: '[]';
        $tour_alloggi = get_post_meta($post->ID,'yht_tour_alloggi',true) ?: '[]';
        $tour_servizi = get_post_meta($post->ID,'yht_tour_servizi',true) ?: '[]';
        
        // Get currently selected entities
        $selected_luoghi = get_post_meta($post->ID,'yht_tour_selected_luoghi',true) ?: '[]';
        $selected_alloggi = get_post_meta($post->ID,'yht_tour_selected_alloggi',true) ?: '[]';
        $selected_servizi = get_post_meta($post->ID,'yht_tour_selected_servizi',true) ?: '[]';
        $selection_status = get_post_meta($post->ID,'yht_entities_selection_status',true) ?: 'pending';
        $last_sent = get_post_meta($post->ID,'yht_last_sent_to_client',true);
        
        $luoghi_data = json_decode($tour_luoghi, true) ?: array();
        $alloggi_data = json_decode($tour_alloggi, true) ?: array();
        $servizi_data = json_decode($tour_servizi, true) ?: array();
        
        $selected_luoghi_data = json_decode($selected_luoghi, true) ?: array();
        $selected_alloggi_data = json_decode($selected_alloggi, true) ?: array();
        $selected_servizi_data = json_decode($selected_servizi, true) ?: array();
        
        // Get available entities for building selection UI
        $luoghi = get_posts(array('post_type' => 'yht_luogo', 'posts_per_page' => -1, 'post_status' => 'publish'));
        $alloggi = get_posts(array('post_type' => 'yht_alloggio', 'posts_per_page' => -1, 'post_status' => 'publish'));
        $servizi = get_posts(array('post_type' => 'yht_servizio', 'posts_per_page' => -1, 'post_status' => 'publish'));
        
        // Create lookup arrays for entities
        $luoghi_lookup = array();
        foreach($luoghi as $luogo) $luoghi_lookup[$luogo->ID] = $luogo->post_title;
        $alloggi_lookup = array();
        foreach($alloggi as $alloggio) $alloggi_lookup[$alloggio->ID] = $alloggio->post_title;
        $servizi_lookup = array();
        foreach($servizi as $servizio) $servizi_lookup[$servizio->ID] = $servizio->post_title;
        ?>
        <style>
            .yht-selection-header {background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;}
            .yht-selection-status {display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase;}
            .yht-status-pending {background: #ffeaa7; color: #fdcb6e;}
            .yht-status-contacted {background: #74b9ff; color: #0984e3;}
            .yht-status-confirmed {background: #00b894; color: #00cec9;}
            .yht-status-sent {background: #a29bfe; color: #6c5ce7;}
            .yht-selection-day {background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; margin: 10px 0;}
            .yht-selection-day h4 {margin: 0 0 15px; color: #2d3436; font-size: 16px;}
            .yht-entity-selection-group {background: white; border: 1px solid #ddd; border-radius: 6px; padding: 12px; margin: 8px 0;}
            .yht-entity-selection-group h5 {margin: 0 0 10px; color: #2d3436; font-size: 14px; font-weight: 600;}
            .yht-options-vs-selected {display: grid; grid-template-columns: 1fr 1fr; gap: 15px; align-items: start;}
            .yht-available-options {padding: 10px; background: #f1f3f4; border-radius: 4px;}
            .yht-selected-entity {padding: 10px; background: #e8f5e8; border: 2px solid #00b894; border-radius: 4px;}
            .yht-option-item {padding: 6px 10px; margin: 3px 0; background: white; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;}
            .yht-selection-controls {margin-top: 10px;}
            .yht-send-to-client {background: #00b894; color: white; border: none; padding: 12px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;}
            .yht-send-to-client:hover {background: #00a085;}
            .yht-send-to-client:disabled {background: #ddd; cursor: not-allowed;}
            .yht-contact-log {background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 4px; margin: 10px 0; font-size: 13px;}
        </style>
        
        <div class="yht-selection-header">
            <h3 style="margin: 0; font-size: 18px;">🎯 Gestione Selezione Entità per Cliente</h3>
            <p style="margin: 8px 0 0; opacity: 0.9; font-size: 14px;">
                Seleziona le entità specifiche da confermare al cliente dopo aver contattato le strutture per verificare la disponibilità.
            </p>
            <div style="margin-top: 12px;">
                <label>Stato della selezione: </label>
                <select name="yht_entities_selection_status" style="padding: 4px 8px; border-radius: 4px; border: none;">
                    <option value="pending" <?php selected($selection_status, 'pending'); ?>>📋 In attesa di contatti</option>
                    <option value="contacted" <?php selected($selection_status, 'contacted'); ?>>📞 Strutture contattate</option>
                    <option value="confirmed" <?php selected($selection_status, 'confirmed'); ?>>✅ Disponibilità confermata</option>
                    <option value="sent_to_client" <?php selected($selection_status, 'sent_to_client'); ?>>📧 Inviato al cliente</option>
                </select>
                <span class="yht-selection-status yht-status-<?php echo $selection_status; ?>"><?php 
                    switch($selection_status) {
                        case 'pending': echo 'In attesa'; break;
                        case 'contacted': echo 'Contattato'; break;
                        case 'confirmed': echo 'Confermato'; break;
                        case 'sent_to_client': echo 'Inviato'; break;
                    }
                ?></span>
            </div>
            <?php if($last_sent): ?>
                <p style="margin: 8px 0 0; font-size: 12px; opacity: 0.8;">
                    📧 Ultimo invio al cliente: <?php echo date('d/m/Y H:i', $last_sent); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <?php if(empty($luoghi_data) && empty($alloggi_data) && empty($servizi_data)): ?>
            <div class="yht-contact-log">
                ⚠️ <strong>Nessuna opzione configurata</strong><br>
                Configura prima le opzioni multiple nella sezione "Configurazione Tour" sopra.
            </div>
        <?php else: ?>
            <div id="yht-selection-container">
                <?php
                // Organize all data by day
                $days_data = array();
                
                // Collect all days from all entity types
                $all_days = array();
                foreach($luoghi_data as $item) $all_days[] = $item['day'] ?? 0;
                foreach($alloggi_data as $item) $all_days[] = $item['day'] ?? 0;
                foreach($servizi_data as $item) $all_days[] = $item['day'] ?? 0;
                $all_days = array_unique(array_filter($all_days));
                sort($all_days);
                
                foreach($all_days as $day_num):
                    // Find data for this day
                    $day_luoghi = null;
                    $day_alloggi = null;
                    $day_servizi = null;
                    $day_selected_luoghi = null;
                    $day_selected_alloggi = null;
                    $day_selected_servizi = null;
                    
                    foreach($luoghi_data as $item) if(($item['day'] ?? 0) == $day_num) $day_luoghi = $item;
                    foreach($alloggi_data as $item) if(($item['day'] ?? 0) == $day_num) $day_alloggi = $item;
                    foreach($servizi_data as $item) if(($item['day'] ?? 0) == $day_num) $day_servizi = $item;
                    foreach($selected_luoghi_data as $item) if(($item['day'] ?? 0) == $day_num) $day_selected_luoghi = $item;
                    foreach($selected_alloggi_data as $item) if(($item['day'] ?? 0) == $day_num) $day_selected_alloggi = $item;
                    foreach($selected_servizi_data as $item) if(($item['day'] ?? 0) == $day_num) $day_selected_servizi = $item;
                ?>
                    <div class="yht-selection-day" data-day="<?php echo $day_num; ?>">
                        <h4>📅 Giorno <?php echo $day_num; ?></h4>
                        
                        <?php if($day_luoghi && !empty($day_luoghi['luoghi_ids'])): ?>
                        <div class="yht-entity-selection-group">
                            <h5>📍 Luoghi da Visitare</h5>
                            <div class="yht-options-vs-selected">
                                <div class="yht-available-options">
                                    <strong>Opzioni disponibili:</strong>
                                    <?php foreach($day_luoghi['luoghi_ids'] as $index => $luogo_id): ?>
                                        <div class="yht-option-item">
                                            <label>
                                                <input type="radio" name="selected_luogo_day_<?php echo $day_num; ?>" 
                                                       value="<?php echo $luogo_id; ?>" 
                                                       <?php checked($day_selected_luoghi['luogo_id'] ?? 0, $luogo_id); ?> />
                                                Opzione <?php echo ($index + 1); ?>: <?php echo esc_html($luoghi_lookup[$luogo_id] ?? 'ID: ' . $luogo_id); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                    <div style="margin-top: 8px;">
                                        <label>Orario: 
                                            <input type="time" name="selected_luogo_time_<?php echo $day_num; ?>" 
                                                   value="<?php echo esc_attr($day_selected_luoghi['time'] ?? $day_luoghi['time'] ?? '10:00'); ?>" 
                                                   style="width: 100px;" />
                                        </label>
                                    </div>
                                </div>
                                <div class="yht-selected-entity">
                                    <strong>Selezione per il cliente:</strong>
                                    <?php if($day_selected_luoghi && !empty($day_selected_luoghi['luogo_id'])): ?>
                                        <div style="padding: 8px 0; font-weight: 600; color: #00b894;">
                                            ✅ <?php echo esc_html($luoghi_lookup[$day_selected_luoghi['luogo_id']] ?? 'Luogo non trovato'); ?>
                                            <br><small>Orario: <?php echo esc_html($day_selected_luoghi['time'] ?? 'Non specificato'); ?></small>
                                        </div>
                                    <?php else: ?>
                                        <div style="color: #6c757d; font-style: italic;">
                                            Nessuna selezione effettuata
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($day_alloggi && !empty($day_alloggi['alloggi_ids'])): ?>
                        <div class="yht-entity-selection-group">
                            <h5>🏨 Alloggio</h5>
                            <div class="yht-options-vs-selected">
                                <div class="yht-available-options">
                                    <strong>Opzioni disponibili:</strong>
                                    <?php foreach($day_alloggi['alloggi_ids'] as $index => $alloggio_id): ?>
                                        <div class="yht-option-item">
                                            <label>
                                                <input type="radio" name="selected_alloggio_day_<?php echo $day_num; ?>" 
                                                       value="<?php echo $alloggio_id; ?>" 
                                                       <?php checked($day_selected_alloggi['alloggio_id'] ?? 0, $alloggio_id); ?> />
                                                Opzione <?php echo ($index + 1); ?>: <?php echo esc_html($alloggi_lookup[$alloggio_id] ?? 'ID: ' . $alloggio_id); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                    <div style="margin-top: 8px;">
                                        <label>Notti: 
                                            <input type="number" min="1" name="selected_alloggio_nights_<?php echo $day_num; ?>" 
                                                   value="<?php echo esc_attr($day_selected_alloggi['nights'] ?? $day_alloggi['nights'] ?? 1); ?>" 
                                                   style="width: 60px;" />
                                        </label>
                                    </div>
                                </div>
                                <div class="yht-selected-entity">
                                    <strong>Selezione per il cliente:</strong>
                                    <?php if($day_selected_alloggi && !empty($day_selected_alloggi['alloggio_id'])): ?>
                                        <div style="padding: 8px 0; font-weight: 600; color: #00b894;">
                                            ✅ <?php echo esc_html($alloggi_lookup[$day_selected_alloggi['alloggio_id']] ?? 'Alloggio non trovato'); ?>
                                            <br><small>Notti: <?php echo esc_html($day_selected_alloggi['nights'] ?? 1); ?></small>
                                        </div>
                                    <?php else: ?>
                                        <div style="color: #6c757d; font-style: italic;">
                                            Nessuna selezione effettuata
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($day_servizi && !empty($day_servizi['servizi_ids'])): ?>
                        <div class="yht-entity-selection-group">
                            <h5>🍽️ Servizi</h5>
                            <div class="yht-options-vs-selected">
                                <div class="yht-available-options">
                                    <strong>Opzioni disponibili:</strong>
                                    <?php foreach($day_servizi['servizi_ids'] as $index => $servizio_id): ?>
                                        <div class="yht-option-item">
                                            <label>
                                                <input type="radio" name="selected_servizio_day_<?php echo $day_num; ?>" 
                                                       value="<?php echo $servizio_id; ?>" 
                                                       <?php checked($day_selected_servizi['servizio_id'] ?? 0, $servizio_id); ?> />
                                                Opzione <?php echo ($index + 1); ?>: <?php echo esc_html($servizi_lookup[$servizio_id] ?? 'ID: ' . $servizio_id); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                    <div style="margin-top: 8px;">
                                        <label>Orario: 
                                            <input type="time" name="selected_servizio_time_<?php echo $day_num; ?>" 
                                                   value="<?php echo esc_attr($day_selected_servizi['time'] ?? $day_servizi['time'] ?? '13:00'); ?>" 
                                                   style="width: 100px;" />
                                        </label>
                                    </div>
                                </div>
                                <div class="yht-selected-entity">
                                    <strong>Selezione per il cliente:</strong>
                                    <?php if($day_selected_servizi && !empty($day_selected_servizi['servizio_id'])): ?>
                                        <div style="padding: 8px 0; font-weight: 600; color: #00b894;">
                                            ✅ <?php echo esc_html($servizi_lookup[$day_selected_servizi['servizio_id']] ?? 'Servizio non trovato'); ?>
                                            <br><small>Orario: <?php echo esc_html($day_selected_servizi['time'] ?? 'Non specificato'); ?></small>
                                        </div>
                                    <?php else: ?>
                                        <div style="color: #6c757d; font-style: italic;">
                                            Nessuna selezione effettuata
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="yht-selection-controls" style="text-align: center; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <button type="button" id="yht-send-selection-to-client" class="yht-send-to-client"
                        <?php echo ($selection_status !== 'confirmed') ? 'disabled' : ''; ?>>
                    📧 Invia Selezione al Cliente
                </button>
                <p style="margin: 10px 0 0; font-size: 13px; color: #6c757d;">
                    💡 <strong>Flusso di lavoro:</strong> 1) Configura opzioni → 2) Contatta strutture → 3) Conferma disponibilità → 4) Seleziona entità → 5) Invia al cliente
                </p>
            </div>
        <?php endif; ?>
        
        <script>
        jQuery(document).ready(function($) {
            // Handle send to client button
            $('#yht-send-selection-to-client').click(function() {
                var clientEmail = prompt('Inserisci l\'email del cliente:');
                if(!clientEmail) return;
                
                var clientName = prompt('Inserisci il nome del cliente (opzionale):') || '';
                
                if(confirm('Sei sicuro di voler inviare la selezione finale al cliente ' + clientEmail + '?')) {
                    var button = $(this);
                    button.prop('disabled', true).text('Invio in corso...');
                    
                    $.ajax({
                        url: '<?php echo rest_url('yht/v1/send_selection_to_client'); ?>',
                        method: 'POST',
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
                        },
                        data: JSON.stringify({
                            tour_id: <?php echo $post->ID; ?>,
                            client_email: clientEmail,
                            client_name: clientName
                        }),
                        contentType: 'application/json',
                        success: function(response) {
                            if(response.ok) {
                                alert('✅ ' + response.message);
                                // Update status and reload page
                                location.reload();
                            } else {
                                alert('❌ ' + response.message);
                                button.prop('disabled', false).text('📧 Invia Selezione al Cliente');
                            }
                        },
                        error: function() {
                            alert('❌ Errore di connessione');
                            button.prop('disabled', false).text('📧 Invia Selezione al Cliente');
                        }
                    });
                }
            });
            
            // Enable/disable send button based on status
            $('select[name="yht_entities_selection_status"]').change(function() {
                var status = $(this).val();
                var sendButton = $('#yht-send-selection-to-client');
                
                if(status === 'confirmed') {
                    sendButton.prop('disabled', false);
                } else {
                    sendButton.prop('disabled', true);
                }
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
        
        // Enhanced validation and processing of entity relationships for consistency
        if(isset($_POST['yht_tour_luoghi'])) {
            $luoghi_data = json_decode(stripslashes($_POST['yht_tour_luoghi']), true);
            if(is_array($luoghi_data)) {
                $clean_luoghi = self::validate_and_clean_entity_data($luoghi_data, 'luoghi');
                update_post_meta($post_id, 'yht_tour_luoghi', wp_json_encode($clean_luoghi));
            }
        }
        
        if(isset($_POST['yht_tour_alloggi'])) {
            $alloggi_data = json_decode(stripslashes($_POST['yht_tour_alloggi']), true);
            if(is_array($alloggi_data)) {
                $clean_alloggi = self::validate_and_clean_entity_data($alloggi_data, 'alloggi');
                update_post_meta($post_id, 'yht_tour_alloggi', wp_json_encode($clean_alloggi));
            }
        }
        
        if(isset($_POST['yht_tour_servizi'])) {
            $servizi_data = json_decode(stripslashes($_POST['yht_tour_servizi']), true);
            if(is_array($servizi_data)) {
                $clean_servizi = self::validate_and_clean_entity_data($servizi_data, 'servizi');
                update_post_meta($post_id, 'yht_tour_servizi', wp_json_encode($clean_servizi));
            }
        }
        
        // If auto pricing is enabled, calculate prices from connected entities
        if(isset($_POST['yht_auto_pricing']) && $_POST['yht_auto_pricing'] === '1') {
            $this->calculate_auto_pricing($post_id);
        }
        
        // Save entity selection status
        if(isset($_POST['yht_entities_selection_status'])) {
            update_post_meta($post_id, 'yht_entities_selection_status', sanitize_text_field($_POST['yht_entities_selection_status']));
        }
        
        // Process selected entities for client
        $this->save_selected_entities($post_id, $_POST);
    }
    
    /**
     * Calculate automatic pricing based on connected entities - enhanced for multiple options consistency
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
        
        // Enhanced calculation from luoghi (entry fees) - properly handle multiple options
        foreach($luoghi_data as $luoghi_group) {
            $luogo_ids = array();
            
            // Handle both old and new data format for consistency
            if(isset($luoghi_group['luoghi_ids']) && is_array($luoghi_group['luoghi_ids'])) {
                $luogo_ids = $luoghi_group['luoghi_ids']; // New multiple options format
            } elseif(isset($luoghi_group['luogo_id'])) {
                $luogo_ids = array($luoghi_group['luogo_id']); // Old single format - maintain compatibility
            }
            
            $total_entry_costs = 0;
            $valid_count = 0;
            
            foreach($luogo_ids as $luogo_id) {
                $entry_cost = (float)get_post_meta($luogo_id,'yht_cost_ingresso',true);
                $total_entry_costs += $entry_cost;
                $valid_count++;
            }
            
            // Use average cost of all options for better pricing consistency
            if($valid_count > 0) {
                $average_cost = $total_entry_costs / $valid_count;
                $base_cost += $average_cost;
                
                // Add slight buffer for multiple options availability
                if($valid_count > 1) {
                    $multiple_options_buffer = $average_cost * 0.05; // 5% buffer for choice availability
                    $base_cost += $multiple_options_buffer;
                }
            }
        }
        
        // Enhanced calculation from alloggi - properly handle multiple options with quality tiers
        foreach($alloggi_data as $alloggi_group) {
            $alloggio_ids = array();
            $nights = $alloggi_group['nights'] ?? 1;
            
            // Handle both old and new data format for consistency
            if(isset($alloggi_group['alloggi_ids']) && is_array($alloggi_group['alloggi_ids'])) {
                $alloggio_ids = $alloggi_group['alloggi_ids']; // New multiple options format
            } elseif(isset($alloggi_group['alloggio_id'])) {
                $alloggio_ids = array($alloggi_group['alloggio_id']); // Old single format - maintain compatibility
            }
            
            $total_standard = 0;
            $total_premium = 0;
            $total_luxury = 0;
            $valid_count = 0;
            
            foreach($alloggio_ids as $alloggio_id) {
                $standard_night = (float)get_post_meta($alloggio_id,'yht_prezzo_notte_standard',true);
                $premium_night = (float)get_post_meta($alloggio_id,'yht_prezzo_notte_premium',true);
                $luxury_night = (float)get_post_meta($alloggio_id,'yht_prezzo_notte_luxury',true);
                
                // If specific tiers not available, calculate from standard with multipliers
                if(!$premium_night && $standard_night) $premium_night = $standard_night * 1.3;
                if(!$luxury_night && $standard_night) $luxury_night = $standard_night * 1.7;
                
                $total_standard += $standard_night * $nights;
                $total_premium += $premium_night * $nights;
                $total_luxury += $luxury_night * $nights;
                $valid_count++;
            }
            
            // Use average cost of all accommodation options for better pricing consistency
            if($valid_count > 0) {
                $avg_standard = $total_standard / $valid_count;
                $avg_premium = $total_premium / $valid_count;
                $avg_luxury = $total_luxury / $valid_count;
                
                $standard_cost += $avg_standard;
                $premium_cost += $avg_premium;
                $luxury_cost += $avg_luxury;
                
                // Add multiple options premium for choice availability
                if($valid_count > 1) {
                    $choice_premium_standard = $avg_standard * 0.03; // 3% choice premium
                    $choice_premium_premium = $avg_premium * 0.03;
                    $choice_premium_luxury = $avg_luxury * 0.03;
                    
                    $standard_cost += $choice_premium_standard;
                    $premium_cost += $choice_premium_premium;
                    $luxury_cost += $choice_premium_luxury;
                }
            }
        }
        
        // Enhanced calculation from servizi - properly handle multiple options with service types
        foreach($servizi_data as $servizi_group) {
            $servizio_ids = array();
            
            // Handle both old and new data format for consistency
            if(isset($servizi_group['servizi_ids']) && is_array($servizi_group['servizi_ids'])) {
                $servizio_ids = $servizi_group['servizi_ids']; // New multiple options format
            } elseif(isset($servizi_group['servizio_id'])) {
                $servizio_ids = array($servizi_group['servizio_id']); // Old single format - maintain compatibility
            }
            
            $total_standard_service = 0;
            $total_premium_service = 0;
            $total_luxury_service = 0;
            $valid_count = 0;
            
            foreach($servizio_ids as $servizio_id) {
                $prezzo_persona = (float)get_post_meta($servizio_id,'yht_prezzo_persona',true);
                $prezzo_fisso = (float)get_post_meta($servizio_id,'yht_prezzo_fisso',true);
                
                // Enhanced service pricing with quality tiers
                if($prezzo_persona > 0) {
                    $total_standard_service += $prezzo_persona;
                    $total_premium_service += $prezzo_persona * 1.25; // Premium service includes better quality
                    $total_luxury_service += $prezzo_persona * 1.6;  // Luxury includes premium service and extras
                } elseif($prezzo_fisso > 0) {
                    // Fixed costs divided by average group size (standardized at 2 people)
                    $per_person_fixed = $prezzo_fisso / 2;
                    $total_standard_service += $per_person_fixed;
                    $total_premium_service += $per_person_fixed * 1.15; // Slight premium for better coordination
                    $total_luxury_service += $per_person_fixed * 1.3;   // Higher tier for luxury experience
                }
                $valid_count++;
            }
            
            // Use average cost of all service options for better pricing consistency
            if($valid_count > 0) {
                $avg_standard_service = $total_standard_service / $valid_count;
                $avg_premium_service = $total_premium_service / $valid_count;
                $avg_luxury_service = $total_luxury_service / $valid_count;
                
                $standard_cost += $avg_standard_service;
                $premium_cost += $avg_premium_service;
                $luxury_cost += $avg_luxury_service;
                
                // Add multiple options coordination premium
                if($valid_count > 1) {
                    $coordination_premium = $avg_standard_service * 0.02; // 2% coordination premium
                    $standard_cost += $coordination_premium;
                    $premium_cost += $coordination_premium * 1.25;
                    $luxury_cost += $coordination_premium * 1.6;
                }
            }
        }
        
        // Enhanced margin calculation with multiple options consideration
        $has_multiple_options = $this->tour_has_multiple_options($luoghi_data, $alloggi_data, $servizi_data);
        
        if($has_multiple_options) {
            // Higher margins for tours with multiple options due to increased value and complexity
            $margin_standard = 1.35; // 35% margin (was 30%)
            $margin_premium = 1.45;   // 45% margin (was 40%)  
            $margin_luxury = 1.65;    // 65% margin (was 60%)
        } else {
            // Standard margins for single-option tours
            $margin_standard = 1.3; // 30% margin
            $margin_premium = 1.4;   // 40% margin  
            $margin_luxury = 1.6;    // 60% margin
        }
        
        // Calculate final prices with enhanced precision
        $final_base = round($base_cost, 2);
        $final_standard = round($standard_cost * $margin_standard, 2);
        $final_premium = round($premium_cost * $margin_premium, 2);
        $final_luxury = round($luxury_cost * $margin_luxury, 2);
        
        // Ensure logical price progression
        if($final_premium <= $final_standard) {
            $final_premium = round($final_standard * 1.3, 2);
        }
        if($final_luxury <= $final_premium) {
            $final_luxury = round($final_premium * 1.3, 2);
        }
        
        // Update prices with enhanced metadata
        update_post_meta($post_id, 'yht_prezzo_base', $final_base);
        update_post_meta($post_id, 'yht_prezzo_standard_pax', $final_standard);
        update_post_meta($post_id, 'yht_prezzo_premium_pax', $final_premium);
        update_post_meta($post_id, 'yht_prezzo_luxury_pax', $final_luxury);
        
        // Store pricing metadata for transparency
        update_post_meta($post_id, 'yht_pricing_calculation_timestamp', current_time('timestamp'));
        update_post_meta($post_id, 'yht_pricing_has_multiple_options', $has_multiple_options ? '1' : '0');
        update_post_meta($post_id, 'yht_pricing_method', 'auto_calculated_enhanced');
    }
    
    /**
     * Check if tour has multiple options configured
     */
    private function tour_has_multiple_options($luoghi_data, $alloggi_data, $servizi_data) {
        // Check luoghi for multiple options
        foreach($luoghi_data as $group) {
            if(isset($group['luoghi_ids']) && is_array($group['luoghi_ids']) && count($group['luoghi_ids']) > 1) {
                return true;
            }
        }
        
        // Check alloggi for multiple options
        foreach($alloggi_data as $group) {
            if(isset($group['alloggi_ids']) && is_array($group['alloggi_ids']) && count($group['alloggi_ids']) > 1) {
                return true;
            }
        }
        
        // Check servizi for multiple options
        foreach($servizi_data as $group) {
            if(isset($group['servizi_ids']) && is_array($group['servizi_ids']) && count($group['servizi_ids']) > 1) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Save selected entities for client communication
     */
    private function save_selected_entities($post_id, $post_data) {
        $selected_luoghi = array();
        $selected_alloggi = array();
        $selected_servizi = array();
        
        // Process all form data to extract selections
        foreach($post_data as $key => $value) {
            // Process luoghi selections
            if(preg_match('/^selected_luogo_day_(\d+)$/', $key, $matches)) {
                $day = (int)$matches[1];
                $luogo_id = (int)$value;
                $time_key = 'selected_luogo_time_' . $day;
                $time = isset($post_data[$time_key]) ? sanitize_text_field($post_data[$time_key]) : '10:00';
                
                if($luogo_id > 0) {
                    $selected_luoghi[] = array(
                        'day' => $day,
                        'luogo_id' => $luogo_id,
                        'time' => $time,
                        'status' => 'selected',
                        'selected_at' => current_time('timestamp')
                    );
                }
            }
            
            // Process alloggi selections
            if(preg_match('/^selected_alloggio_day_(\d+)$/', $key, $matches)) {
                $day = (int)$matches[1];
                $alloggio_id = (int)$value;
                $nights_key = 'selected_alloggio_nights_' . $day;
                $nights = isset($post_data[$nights_key]) ? max(1, (int)$post_data[$nights_key]) : 1;
                
                if($alloggio_id > 0) {
                    $selected_alloggi[] = array(
                        'day' => $day,
                        'alloggio_id' => $alloggio_id,
                        'nights' => $nights,
                        'status' => 'selected',
                        'selected_at' => current_time('timestamp')
                    );
                }
            }
            
            // Process servizi selections
            if(preg_match('/^selected_servizio_day_(\d+)$/', $key, $matches)) {
                $day = (int)$matches[1];
                $servizio_id = (int)$value;
                $time_key = 'selected_servizio_time_' . $day;
                $time = isset($post_data[$time_key]) ? sanitize_text_field($post_data[$time_key]) : '13:00';
                
                if($servizio_id > 0) {
                    $selected_servizi[] = array(
                        'day' => $day,
                        'servizio_id' => $servizio_id,
                        'time' => $time,
                        'status' => 'selected',
                        'selected_at' => current_time('timestamp')
                    );
                }
            }
        }
        
        // Sort by day
        usort($selected_luoghi, function($a, $b) { return $a['day'] <=> $b['day']; });
        usort($selected_alloggi, function($a, $b) { return $a['day'] <=> $b['day']; });
        usort($selected_servizi, function($a, $b) { return $a['day'] <=> $b['day']; });
        
        // Save to database
        update_post_meta($post_id, 'yht_tour_selected_luoghi', wp_json_encode($selected_luoghi));
        update_post_meta($post_id, 'yht_tour_selected_alloggi', wp_json_encode($selected_alloggi));
        update_post_meta($post_id, 'yht_tour_selected_servizi', wp_json_encode($selected_servizi));
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
    
    /**
     * Enhanced validation and cleaning of entity data for multiple options consistency
     */
    private static function validate_and_clean_entity_data($entity_data, $entity_type) {
        $clean_data = array();
        $max_options_per_group = 4; // Enforce 4 maximum options per group
        
        foreach($entity_data as $item) {
            // Determine the correct ID field based on entity type
            $ids_field = $entity_type . '_ids';
            $single_id_field = substr($entity_type, 0, -1) . '_id'; // Remove 's' and add '_id'
            
            if(isset($item['day']) && isset($item[$ids_field]) && is_array($item[$ids_field])) {
                // Validate all entity IDs are numeric and entities exist
                $valid_ids = array();
                $post_type = 'yht_' . substr($entity_type, 0, -1); // Remove 's' from end
                
                foreach($item[$ids_field] as $id) {
                    if(is_numeric($id)) {
                        $id = (int)$id;
                        // Verify the entity exists and is published
                        $post = get_post($id);
                        if($post && $post->post_type === $post_type && $post->post_status === 'publish') {
                            $valid_ids[] = $id;
                        }
                    }
                }
                
                // Enforce maximum options limit for consistency
                if(count($valid_ids) > $max_options_per_group) {
                    $valid_ids = array_slice($valid_ids, 0, $max_options_per_group);
                }
                
                // Remove duplicate IDs
                $valid_ids = array_unique($valid_ids);
                
                if(!empty($valid_ids)) {
                    $clean_item = array(
                        'day' => (int)$item['day'],
                        $ids_field => array_values($valid_ids), // Reindex array
                        'note' => sanitize_text_field($item['note'] ?? ''),
                        'options_count' => count($valid_ids),
                        'multiple_options_enabled' => count($valid_ids) > 1,
                        'data_validation' => array(
                            'validated_at' => current_time('timestamp'),
                            'original_count' => count($item[$ids_field] ?? array()),
                            'cleaned_count' => count($valid_ids),
                            'max_allowed' => $max_options_per_group
                        )
                    );
                    
                    // Add entity-specific fields
                    if($entity_type === 'luoghi') {
                        $clean_item['time'] = sanitize_text_field($item['time'] ?? '10:00');
                    } elseif($entity_type === 'alloggi') {
                        $clean_item['nights'] = max(1, (int)($item['nights'] ?? 1));
                    } elseif($entity_type === 'servizi') {
                        $clean_item['time'] = sanitize_text_field($item['time'] ?? '13:00');
                    }
                    
                    $clean_data[] = $clean_item;
                }
            }
            // Handle backward compatibility for single entity format
            elseif(isset($item['day']) && isset($item[$single_id_field])) {
                $id = (int)$item[$single_id_field];
                $post_type = 'yht_' . substr($entity_type, 0, -1);
                $post = get_post($id);
                
                if($post && $post->post_type === $post_type && $post->post_status === 'publish') {
                    // Convert single entity to multiple options format
                    $clean_item = array(
                        'day' => (int)$item['day'],
                        $ids_field => array($id),
                        'note' => sanitize_text_field($item['note'] ?? ''),
                        'options_count' => 1,
                        'multiple_options_enabled' => false,
                        'data_validation' => array(
                            'validated_at' => current_time('timestamp'),
                            'converted_from_single' => true,
                            'original_format' => 'single_entity'
                        )
                    );
                    
                    // Add entity-specific fields
                    if($entity_type === 'luoghi') {
                        $clean_item['time'] = sanitize_text_field($item['time'] ?? '10:00');
                    } elseif($entity_type === 'alloggi') {
                        $clean_item['nights'] = max(1, (int)($item['nights'] ?? 1));
                    } elseif($entity_type === 'servizi') {
                        $clean_item['time'] = sanitize_text_field($item['time'] ?? '13:00');
                    }
                    
                    $clean_data[] = $clean_item;
                }
            }
        }
        
        // Sort by day for consistency
        usort($clean_data, function($a, $b) {
            return $a['day'] <=> $b['day'];
        });
        
        return $clean_data;
    }
}