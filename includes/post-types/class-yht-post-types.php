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

        // Tour curati - Enhanced pricing
        register_post_meta('yht_tour','yht_giorni',$meta_s);       // JSON dei giorni
        register_post_meta('yht_tour','yht_prezzo_base',$meta_s);  // float
        register_post_meta('yht_tour','yht_prezzo_standard_pax',$meta_s);
        register_post_meta('yht_tour','yht_prezzo_premium_pax',$meta_s);
        register_post_meta('yht_tour','yht_prezzo_luxury_pax',$meta_s);

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
        
        // Decode giorni JSON or set empty array if invalid
        if(!$giorni) $giorni = '[]';
        $giorni_data = json_decode($giorni, true);
        if(!is_array($giorni_data)) $giorni_data = array();
        ?>
        <style>
            .yht-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
            .yht-grid input[type=text], .yht-grid input[type=number]{width:100%}
            .yht-full-width{grid-column:1/3}
            .yht-tour-day{background:#f9f9f9;padding:12px;margin:8px 0;border-radius:6px}
            .yht-tour-day h4{margin:0 0 8px;color:#333}
            .yht-tour-day textarea{width:100%;height:60px;resize:vertical}
        </style>
        <div class="yht-grid">
            <div class="yht-full-width"><h3>Configurazione Prezzi</h3></div>
            <div><label>Prezzo Base (€)</label><input type="number" step="0.01" name="yht_prezzo_base" value="<?php echo $prezzo_base; ?>" placeholder="100.00" /></div>
            <div><label>Prezzo Standard per Persona (€)</label><input type="number" step="0.01" name="yht_prezzo_standard_pax" value="<?php echo $prezzo_standard; ?>" placeholder="150.00" /></div>
            <div><label>Prezzo Premium per Persona (€)</label><input type="number" step="0.01" name="yht_prezzo_premium_pax" value="<?php echo $prezzo_premium; ?>" placeholder="195.00" /></div>
            <div><label>Prezzo Luxury per Persona (€)</label><input type="number" step="0.01" name="yht_prezzo_luxury_pax" value="<?php echo $prezzo_luxury; ?>" placeholder="255.00" /></div>
            
            <div class="yht-full-width"><h3>Itinerario Giorni</h3></div>
            <div class="yht-full-width">
                <p class="description">Configura l'itinerario giorno per giorno. Il JSON verrà aggiornato automaticamente.</p>
                <div id="yht-giorni-container">
                    <?php if(empty($giorni_data)): ?>
                        <div class="yht-tour-day">
                            <h4>Giorno 1</h4>
                            <textarea name="yht_giorno_1" placeholder="Descrizione attività del primo giorno..."></textarea>
                        </div>
                    <?php else: ?>
                        <?php foreach($giorni_data as $index => $giorno): ?>
                            <div class="yht-tour-day">
                                <h4>Giorno <?php echo $index + 1; ?></h4>
                                <textarea name="yht_giorno_<?php echo $index + 1; ?>" placeholder="Descrizione attività..."><?php echo esc_textarea($giorno['description'] ?? ''); ?></textarea>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p>
                    <button type="button" id="yht-add-day" class="button">+ Aggiungi Giorno</button>
                    <button type="button" id="yht-remove-day" class="button" <?php echo empty($giorni_data) || count($giorni_data) <= 1 ? 'disabled' : ''; ?>>- Rimuovi Ultimo Giorno</button>
                </p>
                <input type="hidden" name="yht_giorni" id="yht_giorni_json" value="<?php echo esc_attr($giorni); ?>" />
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
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
            
            $('#yht-add-day').click(function() {
                var dayCount = $('#yht-giorni-container .yht-tour-day').length + 1;
                var newDay = '<div class="yht-tour-day"><h4>Giorno ' + dayCount + '</h4><textarea name="yht_giorno_' + dayCount + '" placeholder="Descrizione attività..."></textarea></div>';
                $('#yht-giorni-container').append(newDay);
                $('#yht-remove-day').prop('disabled', false);
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
            });
            
            $(document).on('input', '#yht-giorni-container textarea', function() {
                updateGiorniJSON();
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