<?php
/**
 * Temporary fixed file (DEPRECATED - v6.2 No-ACF)
 * This is a temporary file and should not be treated as a plugin.
 * The main plugin is now handled by your-hidden-trip-planner.php (v6.3)
 * 
 * Original Description: Trip builder reale per Tuscia & Umbria: CPT, tassonomie, importer, generatore tour da CPT, mappa inline (light), lead Brevo, export JSON/ICS/PDF (dompdf), WooCommerce package, share link, GA4 dataLayer.
 * Version: 6.2 (TEMPORARY)
 * Author: YourHiddenTrip
 * Text Domain: your-hidden-trip
 */

if (!defined('ABSPATH')) exit;

// COMPLETELY DISABLED - This file is deprecated and conflicts with the new plugin architecture
// All functionality has been moved to the new class-based system in your-hidden-trip-planner.php
return;

define('YHT_VER', '6.2');
define('YHT_SLUG', 'your-hidden-trip');
define('YHT_OPT',  'yht_settings');

/* ---------------------------------------------------------
 * 1) ATTIVAZIONE / OPZIONI
 * --------------------------------------------------------- */
register_activation_hook(__FILE__, function(){
  $defaults = array(
    'notify_email'    => get_option('admin_email'),
    'brevo_api_key'   => '',
    'ga4_id'          => '',
    'wc_deposit_pct'  => '20',
    'wc_price_per_pax'=> '80',
  );
  add_option(YHT_OPT, $defaults);
});

function yht_get_settings(){
  $opt = get_option(YHT_OPT, array());
  $defaults = array(
    'notify_email'    => get_option('admin_email'),
    'brevo_api_key'   => '',
    'ga4_id'          => '',
    'wc_deposit_pct'  => '20',
    'wc_price_per_pax'=> '80',
  );
  return wp_parse_args($opt, $defaults);
}

/* ---------------------------------------------------------
 * 2) CPT & TASSO
 * --------------------------------------------------------- */
add_action('init', function(){

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

  // CPT Partner (B2B)
  register_post_type('yht_partner', array(
    'label' => 'Partner',
    'public' => false,
    'show_ui' => true,
    'show_in_rest' => true,
    'menu_icon' => 'dashicons-groups',
    'supports' => array('title','editor','thumbnail'),
  ));

  // Tassonomie
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
});

/* ---------------------------------------------------------
 * 3) META (senza ACF) + METABOX
 * --------------------------------------------------------- */
add_action('init', function(){
  $meta_s = array('show_in_rest'=>true, 'single'=>true, 'type'=>'string', 'auth_callback' => '__return_true');

  // Luoghi
  foreach(['yht_lat','yht_lng','yht_cost_ingresso','yht_durata_min','yht_orari_note','yht_chiusure_json'] as $m){
    register_post_meta('yht_luogo',$m,$meta_s);
  }
  register_post_meta('yht_luogo','yht_accesso_family',$meta_s);
  register_post_meta('yht_luogo','yht_accesso_pet',$meta_s);
  register_post_meta('yht_luogo','yht_accesso_mobility',$meta_s);

  // Alloggi
  foreach(['yht_lat','yht_lng','yht_fascia_prezzo','yht_servizi_json','yht_capienza'] as $m){
    register_post_meta('yht_alloggio',$m,$meta_s);
  }

  // Tour curati
  register_post_meta('yht_tour','yht_giorni',$meta_s);       // JSON dei giorni
  register_post_meta('yht_tour','yht_prezzo_base',$meta_s);  // float
});

// Rest of content follows same pattern...