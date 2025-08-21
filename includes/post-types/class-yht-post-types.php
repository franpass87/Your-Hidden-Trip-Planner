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
}