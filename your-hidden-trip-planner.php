
/**
 * Plugin Name: Your Hidden Trip Builder (v6.2 No-ACF)
 * Description: Trip builder reale per Tuscia & Umbria: CPT, tassonomie, importer, generatore tour da CPT, mappa inline (light), lead Brevo, export JSON/ICS/PDF (dompdf), WooCommerce package, share link, GA4 dataLayer.
 * Version: 6.2
 * Author: YourHiddenTrip
 * Text Domain: your-hidden-trip
 */

if (!defined('ABSPATH')) exit;

// Plugin constants
define('YHT_VER', '6.2');
define('YHT_SLUG', 'your-hidden-trip');
define('YHT_OPT',  'yht_settings');

// Traveler types
define('YHT_TRAVELER_ACTIVE', 'active');
define('YHT_TRAVELER_RELAXED', 'relaxed');

// Default values
define('YHT_DEFAULT_PAX', 2);
define('YHT_DEFAULT_TIMEOUT', 20);
define('YHT_ACTIVE_STOPS_PER_DAY', 3);
define('YHT_RELAXED_STOPS_PER_DAY', 2);

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

/**
 * Get plugin settings with defaults
 * @return array Plugin settings
 */
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

add_action('add_meta_boxes', function(){
  add_meta_box('yht_luogo_meta','Dati Luogo','yht_mb_luogo','yht_luogo','normal','high');
});
/**
 * Get admin styles for metaboxes
 * @return string CSS styles for admin interface
 */
function yht_get_admin_styles() {
  return '<style>
    .yht-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .yht-grid input[type=text], .yht-grid textarea{width:100%}
    .yht-chiusure-list li{margin-bottom:6px}
  </style>';
}

/**
 * Metabox callback for luoghi (places) custom post type
 * @param WP_Post $post Current post object
 */
function yht_mb_luogo($post){
  // Get current meta values
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
  
  echo yht_get_admin_styles();
  ?>
  <div class="yht-grid">
    <div><label>Latitudine</label><input type="text" name="yht_lat" value="<?php echo $lat; ?>" /></div>
    <div><label>Longitudine</label><input type="text" name="yht_lng" value="<?php echo $lng; ?>" /></div>
    <div><label>Costo ingresso (€)</label><input type="text" name="yht_cost_ingresso" value="<?php echo $cst; ?>" /></div>
    <div><label>Durata media visita (min)</label><input type="text" name="yht_durata_min" value="<?php echo $dur; ?>" /></div>
    <div><label><input type="checkbox" name="yht_accesso_family" value="1" <?php checked($fam,'1'); ?> /> Family-friendly</label></div>
    <div><label><input type="checkbox" name="yht_accesso_pet" value="1" <?php checked($pet,'1'); ?> /> Pet-friendly</label></div>
    <div><label><input type="checkbox" name="yht_accesso_mobility" value="1" <?php checked($mob,'1'); ?> /> Accessibilità ridotta</label></div>
    <div style="grid-column:1/3"><label>Orari / Note</label><textarea name="yht_orari_note" rows="3"><?php echo $ora; ?></textarea></div>
  </div>
  <hr/>
  <h4>Chiusure/Disponibilità</h4>
  <p class="description">Aggiungi periodi non prenotabili (es. manutenzione). Verranno esclusi dal generatore.</p>
  <div id="yht-chiusure-wrap" data-json="<?php echo esc_attr($chi); ?>">
    <table class="widefat">
      <thead><tr><th>Dal</th><th>Al</th><th>Nota</th><th></th></tr></thead>
      <tbody id="yht-chiusure-body"></tbody>
    </table>
    <p><button class="button" type="button" id="yht-add-closure">+ Aggiungi chiusura</button></p>
    <input type="hidden" name="yht_chiusure_json" id="yht_chiusure_json" value="<?php echo esc_attr($chi); ?>"/>
  </div>
  <script>
    (function($){
      const $wrap = $('#yht-chiusure-wrap');
      let data = [];
      try{ data = JSON.parse($wrap.data('json')||'[]'); }catch(e){ data=[]; }
      function render(){
        const $body = $('#yht-chiusure-body'); $body.empty();
        data.forEach((row,i)=>{
          const tr = $(`
            <tr>
              <td><input type="date" value="${row.start||''}" class="yht-c-start"/></td>
              <td><input type="date" value="${row.end||''}" class="yht-c-end"/></td>
              <td><input type="text" value="${row.note||''}" class="yht-c-note"/></td>
              <td><a href="#" data-i="${i}" class="yht-c-del">Rimuovi</a></td>
            </tr>`);
          $body.append(tr);
        });
        $('#yht_chiusure_json').val(JSON.stringify(data));
      }
      render();
      $('#yht-add-closure').on('click', function(){
        data.push({start:'',end:'',note:''}); render();
      });
      $(document).on('input','.yht-c-start', function(){ data[$(this).closest('tr').index()].start = this.value; $('#yht_chiusure_json').val(JSON.stringify(data)); });
      $(document).on('input','.yht-c-end', function(){ data[$(this).closest('tr').index()].end = this.value; $('#yht_chiusure_json').val(JSON.stringify(data)); });
      $(document).on('input','.yht-c-note', function(){ data[$(this).closest('tr').index()].note = this.value; $('#yht_chiusure_json').val(JSON.stringify(data)); });
      $(document).on('click','.yht-c-del', function(e){ e.preventDefault(); data.splice($(this).data('i'),1); render(); });
    })(jQuery);
  </script>
  <?php

add_action('save_post_yht_luogo', function($post_id){
  if(!isset($_POST['yht_meta_nonce']) || !wp_verify_nonce($_POST['yht_meta_nonce'],'yht_save_meta')) return;
  if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if(!current_user_can('edit_post',$post_id)) return;

  $fields = array('yht_lat','yht_lng','yht_cost_ingresso','yht_durata_min','yht_orari_note','yht_chiusure_json');
  foreach($fields as $f){
    if(isset($_POST[$f])) update_post_meta($post_id, $f, sanitize_text_field($_POST[$f]));
  }
  update_post_meta($post_id,'yht_accesso_family', isset($_POST['yht_accesso_family'])?'1':'');
  update_post_meta($post_id,'yht_accesso_pet', isset($_POST['yht_accesso_pet'])?'1':'');
  update_post_meta($post_id,'yht_accesso_mobility', isset($_POST['yht_accesso_mobility'])?'1':'');
}, 10, 1);

/* ---------------------------------------------------------
 * 4) MENU: IMPOSTAZIONI + IMPORTER
 * --------------------------------------------------------- */
add_action('admin_menu', function(){
  add_menu_page('Your Hidden Trip','Your Hidden Trip','manage_options','yht_admin','yht_admin_settings','dashicons-admin-site',58);
  add_submenu_page('yht_admin','Impostazioni','Impostazioni','manage_options','yht_admin','yht_admin_settings');
  add_submenu_page('yht_admin','Importer CSV','Importer CSV','manage_options','yht_import','yht_admin_importer');
});

function yht_admin_settings(){
  if(!current_user_can('manage_options')) return;
  $s = yht_get_settings();
  if(isset($_POST['yht_save'])){
    check_admin_referer('yht_settings');
    $s['notify_email']     = sanitize_email($_POST['notify_email'] ?? '');
    $s['brevo_api_key']    = sanitize_text_field($_POST['brevo_api_key'] ?? '');
    $s['ga4_id']           = sanitize_text_field($_POST['ga4_id'] ?? '');
    $s['wc_deposit_pct']   = sanitize_text_field($_POST['wc_deposit_pct'] ?? '20');
    $s['wc_price_per_pax'] = sanitize_text_field($_POST['wc_price_per_pax'] ?? '80');
    update_option(YHT_OPT,$s);
    echo '<div class="updated"><p>Impostazioni salvate.</p></div>';
  }
  ?>
  <div class="wrap">
    <h1>Your Hidden Trip – Impostazioni</h1>
    <form method="post"><?php wp_nonce_field('yht_settings'); ?>
      <table class="form-table">
        <tr><th scope="row">Email notifiche</th><td><input type="email" name="notify_email" value="<?php echo esc_attr($s['notify_email']); ?>" class="regular-text"/></td></tr>
        <tr><th scope="row">Brevo API Key</th><td><input type="text" name="brevo_api_key" value="<?php echo esc_attr($s['brevo_api_key']); ?>" class="regular-text"/></td></tr>
        <tr><th scope="row">GA4 ID (opz.)</th><td><input type="text" name="ga4_id" value="<?php echo esc_attr($s['ga4_id']); ?>" class="regular-text" placeholder="G-XXXXXX"/></td></tr>
        <tr><th scope="row">Woo – Prezzo base per pax (€)</th><td><input type="number" step="1" name="wc_price_per_pax" value="<?php echo esc_attr($s['wc_price_per_pax']); ?>"/></td></tr>
        <tr><th scope="row">Woo – Acconto (%)</th><td><input type="number" step="1" name="wc_deposit_pct" value="<?php echo esc_attr($s['wc_deposit_pct']); ?>"/></td></tr>
      </table>
      <p><button class="button button-primary" name="yht_save" value="1">Salva</button></p>
    </form>
  </div>
  <?php
}

/* ---------- Importer esteso: Luoghi + Alloggi + Tour + utility featured ---------- */

/**
 * Handle CSV template downloads
 * @return void Outputs CSV and exits if template requested
 */
function yht_handle_template_download() {
  if(!current_user_can('manage_options')) return;

  if(isset($_GET['download_template']) && $_GET['download_template']==='luoghi'){
    $csv = "title,descr,lat,lng,esperienze|pipe,aree|pipe,costo_ingresso,durata_min,family,pet,mobility,stagioni|pipe\n";
    $csv.= "Civita di Bagnoregio,Il borgo sospeso,42.627,12.092,cultura|passeggiata,collina|centro_storico,5,90,0,0,0,primavera|autunno\n";
    header('Content-Type: text/csv'); 
    header('Content-Disposition: attachment; filename="yht_luoghi_template.csv"'); 
    echo $csv; 
    exit;
  }
  
  if(isset($_GET['download_template']) && $_GET['download_template']==='alloggi'){
    $csv = "title,descr,lat,lng,fascia_prezzo,servizi|pipe,capienza\n";
    $csv.= "Bolsena – Hotel Lungolago,Hotel fronte lago,42.644,11.990,med,colazione|wi-fi|parcheggio|pet,40\n";
    header('Content-Type: text/csv'); 
    header('Content-Disposition: attachment; filename="yht_alloggi_template.csv"'); 
    echo $csv; 
    exit;
  }
  
  if(isset($_GET['download_template']) && $_GET['download_template']==='tours'){
    $sample = json_encode([["day"=>1,"stops"=>[["luogo_title"=>"Viterbo – Quartiere San Pellegrino","time"=>"10:00"]]]], JSON_UNESCAPED_UNICODE);
    $csv = "title,descr,prezzo_base,giorni_json\n";
    $csv.= "Classico Tuscia 3 giorni,Itinerario esempio,120,\"".str_replace('"','""',$sample)."\"\n";
    header('Content-Type: text/csv'); 
    header('Content-Disposition: attachment; filename="yht_tours_template.csv"'); 
    echo $csv; 
    exit;
  }
}

/**
 * Import luoghi from CSV data
 * @param resource $file_handle CSV file handle
 * @return int Number of imported items
 */
function yht_import_luoghi_from_csv($file_handle) {
  $row = 0;
  $imported = 0;
  
  while(($data = fgetcsv($file_handle, 0, ",")) !== false){
    $row++; 
    if($row == 1) continue; // Skip header
    
    list($title,$descr,$lat,$lng,$exp,$aree,$costo,$durata,$fam,$pet,$mob,$stag) = array_pad($data, 12, '');
    
    $post_id = wp_insert_post(array(
      'post_type' => 'yht_luogo',
      'post_title' => sanitize_text_field($title),
      'post_content' => wp_kses_post($descr),
      'post_status' => 'publish'
    ));
    
    if($post_id){
      // Set taxonomies
      if($exp)  wp_set_post_terms($post_id, array_map('trim', explode('|',$exp)),  'yht_esperienza', false);
      if($aree) wp_set_post_terms($post_id, array_map('trim', explode('|',$aree)), 'yht_area', false);
      if($stag) wp_set_post_terms($post_id, array_map('trim', explode('|',$stag)), 'yht_stagione', false);
      
      // Set meta fields
      update_post_meta($post_id,'yht_lat', sanitize_text_field($lat));
      update_post_meta($post_id,'yht_lng', sanitize_text_field($lng));
      update_post_meta($post_id,'yht_cost_ingresso', sanitize_text_field($costo));
      update_post_meta($post_id,'yht_durata_min', sanitize_text_field($durata));
      update_post_meta($post_id,'yht_accesso_family', $fam=='1'?'1':'');
      update_post_meta($post_id,'yht_accesso_pet', $pet=='1'?'1':'');
      update_post_meta($post_id,'yht_accesso_mobility', $mob=='1'?'1':'');
      
      $imported++;
    }
  }
  
  return $imported;
}

/**
 * Main admin importer page handler
 */
function yht_admin_importer(){
  if(!current_user_can('manage_options')) return;

  // Handle template downloads
  yht_handle_template_download();

  $msg = '';
  if(isset($_POST['yht_import'])){
    check_admin_referer('yht_import');
    $type = sanitize_text_field($_POST['yht_type'] ?? 'luoghi');
    if(!empty($_FILES['yht_csv']['tmp_name'])){
      $h = fopen($_FILES['yht_csv']['tmp_name'],'r');
      $imported = 0;

      if($type === 'luoghi'){
        $imported = yht_import_luoghi_from_csv($h);
        $msg = 'Luoghi importati: '.$imported;
      }
      elseif($type==='alloggi'){
        while(($data = fgetcsv($h, 0, ",")) !== false){
          $row++; if($row==1) continue;
          list($title,$descr,$lat,$lng,$fascia,$servizi,$capienza) = array_pad($data,7,'');
          $post_id = wp_insert_post(array('post_type'=>'yht_alloggio','post_title'=>sanitize_text_field($title),'post_content'=>wp_kses_post($descr),'post_status'=>'publish'));
          if($post_id){
            update_post_meta($post_id,'yht_lat', sanitize_text_field($lat));
            update_post_meta($post_id,'yht_lng', sanitize_text_field($lng));
            update_post_meta($post_id,'yht_fascia_prezzo', sanitize_text_field($fascia));
            update_post_meta($post_id,'yht_servizi_json', wp_json_encode(array_map('trim', explode('|',$servizi))));
            update_post_meta($post_id,'yht_capienza', intval($capienza));
            $imported++;
          }
        }
        $msg = 'Alloggi importati: '.$imported;
      }
      elseif($type==='tours'){
        while(($data = fgetcsv($h, 0, ",")) !== false){
          $row++; if($row==1) continue;
          list($title,$descr,$prezzo,$giorni_json) = array_pad($data,4,'');
          $post_id = wp_insert_post(array('post_type'=>'yht_tour','post_title'=>sanitize_text_field($title),'post_content'=>wp_kses_post($descr),'post_status'=>'publish'));
          if($post_id){
            update_post_meta($post_id,'yht_prezzo_base', sanitize_text_field($prezzo));
            // parse giorni JSON e salva come meta strutturato (con risoluzione luoghi)
            $giorni = json_decode($giorni_json, true);
            if(is_array($giorni)){
              foreach($giorni as &$g){
                if(!isset($g['stops']) || !is_array($g['stops'])) $g['stops']=[];
                foreach($g['stops'] as &$s){
                  $t = sanitize_text_field($s['luogo_title'] ?? '');
                  $q = new WP_Query(array(
                    'post_type'=>'yht_luogo',
                    'posts_per_page'=>1,
                    's'=>$t,
                    'no_found_rows'=>true
                  ));
                  if($q->have_posts()){ $q->the_post();
                    $sid = get_the_ID();
                    $s['luogo_id'] = $sid;
                    $s['title'] = get_the_title($sid);
                    $s['lat'] = (float)get_post_meta($sid,'yht_lat',true);
                    $s['lng'] = (float)get_post_meta($sid,'yht_lng',true);
                    $s['exp'] = wp_get_post_terms($sid,'yht_esperienza',array('fields'=>'slugs'));
                    $s['area']= wp_get_post_terms($sid,'yht_area',array('fields'=>'slugs'));
                  } else {
                    $s['title'] = $t ?: 'Tappa';
                  }
                  wp_reset_postdata();
                }
              }
              update_post_meta($post_id,'yht_giorni', wp_json_encode($giorni));
            }
            $imported++;
          }
        }
        $msg = 'Tour importati: '.$imported;
      }
      fclose($h);
    }
  }

  // Utility: assegna featured image
  if(isset($_POST['yht_set_featured'])){
    check_admin_referer('yht_import');
    $q = new WP_Query(array('post_type'=>array('yht_luogo','yht_alloggio'),'posts_per_page'=>-1,'no_found_rows'=>true));
    $done=0;
    while($q->have_posts()){ $q->the_post();
      if(has_post_thumbnail()) continue;
      $atts = get_children(array('post_parent'=>get_the_ID(),'post_type'=>'attachment','post_mime_type'=>'image','numberposts'=>1,'orderby'=>'menu_order'));
      if($atts){ $att = array_shift($atts); set_post_thumbnail(get_the_ID(), $att->ID); $done++; }
    }
    wp_reset_postdata();
    $msg = 'Featured images assegnate: '.$done;
  }

  ?>
  <div class="wrap">
    <h1>YHT Importer</h1>
    <?php if($msg) echo '<div class="updated"><p>'.esc_html($msg).'</p></div>'; ?>
    <h2 class="title">Importa CSV</h2>
    <form method="post" enctype="multipart/form-data">
      <?php wp_nonce_field('yht_import'); ?>
      <p>
        <label><input type="radio" name="yht_type" value="luoghi" checked /> Luoghi</label>
        &nbsp;&nbsp;
        <label><input type="radio" name="yht_type" value="alloggi" /> Alloggi</label>
        &nbsp;&nbsp;
        <label><input type="radio" name="yht_type" value="tours" /> Tour curati</label>
      </p>
      <p><input type="file" name="yht_csv" accept=".csv" required /></p>
      <p><button class="button button-primary" name="yht_import" value="1">Importa</button></p>
    </form>

    <h2 class="title">Template</h2>
    <p>
      <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=yht_import&download_template=luoghi')); ?>">Luoghi CSV</a>
      <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=yht_import&download_template=alloggi')); ?>">Alloggi CSV</a>
      <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=yht_import&download_template=tours')); ?>">Tour CSV</a>
    </p>

    <h2 class="title">Utility</h2>
    <form method="post">
      <?php wp_nonce_field('yht_import'); ?>
      <p><button class="button" name="yht_set_featured" value="1">Assegna featured dal primo media</button></p>
    </form>
  </div>
  <?php
}

/* ---------------------------------------------------------
 * 5) REST: GENERAZIONE TOUR, LEAD BREVO, WOO PRODUCT, PDF
 * --------------------------------------------------------- */
add_action('rest_api_init', function(){
  register_rest_route('yht/v1','/generate', array(
    'methods'=>'POST','callback'=>'yht_api_generate','permission_callback'=>'__return_true'
  ));
  register_rest_route('yht/v1','/lead', array(
    'methods'=>'POST','callback'=>'yht_api_lead','permission_callback'=>'__return_true'
  ));
  register_rest_route('yht/v1','/wc_create_product', array(
    'methods'=>'POST','callback'=>'yht_api_wc_create_product','permission_callback'=>function(){
      return current_user_can('manage_woocommerce') || current_user_can('manage_options');
    }
  ));
  register_rest_route('yht/v1','/pdf', array(
    'methods'=>'POST','callback'=>'yht_api_pdf','permission_callback'=>'__return_true'
  ));
});

/**
 * REST API endpoint to generate tour suggestions
 * @param WP_REST_Request $req Request object
 * @return WP_REST_Response Tour generation results
 */
function yht_api_generate(WP_REST_Request $req){
  try {
    $p = $req->get_json_params();
    if(!$p) {
      return rest_ensure_response(array('ok'=>false,'message'=>'Dati richiesta non validi'));
    }
    
    $traveler_type = sanitize_text_field($p['travelerType'] ?? '');
    $exps = array_map('sanitize_text_field', $p['esperienze'] ?? array());
    $areas= array_map('sanitize_text_field', $p['luogo'] ?? array());
    $dur  = sanitize_text_field($p['durata'] ?? '');
    $date = sanitize_text_field($p['startdate'] ?? '');

    // Validate required parameters
    if(empty($exps) || empty($areas) || empty($dur) || empty($date) || empty($traveler_type)) {
      return rest_ensure_response(array('ok'=>false,'message'=>'Parametri obbligatori mancanti'));
    }

    $days = yht_durata_to_days($dur);
    $perDay = ($traveler_type === YHT_TRAVELER_ACTIVE) ? YHT_ACTIVE_STOPS_PER_DAY : YHT_RELAXED_STOPS_PER_DAY;

    $pool = yht_query_poi($exps, $areas, $date, $days);
    
    if(empty($pool)) {
      return rest_ensure_response(array('ok'=>false,'message'=>'Nessun luogo trovato per i criteri selezionati'));
    }

    // Tour generation with different profiles
    $WB = array('trekking'=>1,'passeggiata'=>1,'cultura'=>1,'benessere'=>0.6,'enogastronomia'=>0.8);
    $WN = array('trekking'=>1.2,'passeggiata'=>1,'cultura'=>0.6,'benessere'=>0.5,'enogastronomia'=>0.8);
    $WC = array('trekking'=>0.5,'passeggiata'=>0.9,'cultura'=>1.3,'benessere'=>0.7,'enogastronomia'=>1.1);

    $tours = array(
      yht_plan_itinerary('Tour Essenziale', $pool, $days, $perDay, $WB),
      yht_plan_itinerary('Natura & Borghi', $pool, $days, $perDay, $WN),
      yht_plan_itinerary('Arte & Sapori',   $pool, $days, $perDay, $WC),
    );

    // Filter out empty tours
    $tours = array_filter($tours, function($tour) {
      return !empty($tour['days']);
    });

    if(empty($tours)) {
      return rest_ensure_response(array('ok'=>false,'message'=>'Impossibile generare tour con i criteri selezionati'));
    }

    return rest_ensure_response(array('ok'=>true,'days'=>$days,'perDay'=>$perDay,'tours'=>array_values($tours)));
    
  } catch(Exception $e) {
    error_log('YHT API Generate Error: ' . $e->getMessage());
    return rest_ensure_response(array('ok'=>false,'message'=>'Errore interno del server'));
  }
}

/**
 * Query POI (Points of Interest) based on criteria
 * @param array $exps Experience types
 * @param array $areas Location areas 
 * @param string $startdate Start date
 * @param int $days Number of days
 * @return array Array of POI matches
 */
function yht_query_poi($exps, $areas, $startdate, $days){
  $tax = array('relation'=>'AND');
  if(!empty($exps)){
    $tax[] = array('taxonomy'=>'yht_esperienza','field'=>'slug','terms'=>$exps,'operator'=>'IN');
  }
  if(!empty($areas)){
    $tax[] = array('taxonomy'=>'yht_area','field'=>'slug','terms'=>$areas,'operator'=>'IN');
  }
  $q = new WP_Query(array(
    'post_type'=>'yht_luogo',
    'posts_per_page'=>-1,
    'tax_query'=> (count($tax)>1 ? $tax : array()),
    'no_found_rows'=>true,
  ));

  $res = array();
  $range = yht_date_range($startdate, $days);
  while($q->have_posts()){ $q->the_post();
    $id = get_the_ID();
    $lat = (float) get_post_meta($id,'yht_lat',true);
    $lng = (float) get_post_meta($id,'yht_lng',true);
    if(!$lat || !$lng) continue;

    // esclusione chiusure
    $chi = get_post_meta($id,'yht_chiusure_json',true);
    $closed = false;
    if($chi){
      $arr = json_decode($chi,true);
      if(is_array($arr)){
        foreach($arr as $c){
          $cs = $c['start'] ?? ''; $ce=$c['end'] ?? '';
          if($cs && $ce){
            foreach($range as $d){
              if($d >= $cs && $d <= $ce){ $closed = true; break 2; }
            }
          }
        }
      }
    }
    if($closed) continue;

    $res[] = array(
      'id'=>$id,
      'title'=>get_the_title(),
      'excerpt'=>wp_strip_all_tags(get_the_excerpt()),
      'lat'=>$lat,'lng'=>$lng,
      'cost'=> (float) get_post_meta($id,'yht_cost_ingresso',true),
      'durata'=> (int) get_post_meta($id,'yht_durata_min',true),
      'exp'=> wp_get_post_terms($id,'yht_esperienza',array('fields'=>'slugs')),
      'area'=> wp_get_post_terms($id,'yht_area',array('fields'=>'slugs')),
      'link'=> get_permalink($id),
    );
  }
  wp_reset_postdata();
  return $res;
}

function yht_date_range($start, $days){
  $out = array();
  if(!$start) return $out;
  $d = new DateTime($start);
  for($i=0;$i<$days;$i++){
    $out[] = $d->format('Y-m-d');
    $d->modify('+1 day');
  }
  return $out;
}

function yht_plan_itinerary($name, $pool, $days, $perDay, $weights){
  if(empty($pool)){
    return array('name'=>$name, 'days'=>array(), 'stops'=>0, 'totalEntryCost'=>0);
  }
  // score by experiences
  foreach($pool as &$p){
    $p['_score'] = 0;
    foreach(($p['exp'] ?? array()) as $e){
      $p['_score'] += isset($weights[$e]) ? $weights[$e] : 0;
    }
  }
  unset($p);
  usort($pool, function($a,$b){ return $b['_score'] <=> $a['_score']; });

  $needed = $days * $perDay;
  $selected = array();
  $selected[] = $pool[0];

  while(count($selected) < min($needed, count($pool))){
    $last = end($selected);
    $next = null; $best=PHP_FLOAT_MAX;
    foreach($pool as $cand){
      if(in_array($cand, $selected, true)) continue;
      $d = yht_dist($last['lat'],$last['lng'],$cand['lat'],$cand['lng']);
      if($d < $best){ $best=$d; $next=$cand; }
    }
    if($next) $selected[] = $next; else break;
  }

  foreach($pool as $cand){
    if(count($selected) >= $needed) break;
    if(!in_array($cand, $selected, true)) $selected[] = $cand;
  }

  // distribute per day
  $daysArr = array();
  $idx=0;
  $slots = ($perDay==3) ? array('10:00','14:30','17:30') : array('11:00','16:00');
  for($d=0;$d<$days;$d++){
    $stops = array_slice($selected, $idx, $perDay);
    $idx += $perDay;
    $stopsTimed = array();
    foreach($stops as $i=>$s){
      $stopsTimed[] = array_merge($s, array('time'=>$slots[$i] ?? '18:00', '_day'=>$d+1));
    }
    $daysArr[] = array('day'=>$d+1, 'stops'=>$stopsTimed);
  }

  $cost = 0;
  foreach($selected as $s){ $cost += is_numeric($s['cost']) ? (float)$s['cost'] : 0; }

  return array('name'=>$name, 'days'=>$daysArr, 'stops'=>count($selected), 'totalEntryCost'=>round($cost));
}

/**
 * Calculate distance between two coordinates using Haversine formula
 * @param float $lat1 Latitude of first point
 * @param float $lon1 Longitude of first point  
 * @param float $lat2 Latitude of second point
 * @param float $lon2 Longitude of second point
 * @return float Distance in kilometers
 */
function yht_dist($lat1,$lon1,$lat2,$lon2){
  $R = 6371;
  $dLat = deg2rad($lat2-$lat1);
  $dLon = deg2rad($lon2-$lon1);
  $a = sin($dLat/2)*sin($dLat/2) + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLon/2)*sin($dLon/2);
  $c = 2 * atan2(sqrt($a), sqrt(1-$a));
  return $R * $c;
}

/**
 * Convert duration string to number of days
 * @param string $v Duration string (e.g., '2_notti')
 * @return int Number of days
 */
function yht_durata_to_days($v){
  if($v==='1_notte') return 2;
  if($v==='2_notti') return 3;
  if($v==='3_notti') return 4;
  if($v==='4_notti') return 5;
  if($v==='5+_notti') return 7;
  return 2;
}

/**
 * REST API endpoint to submit lead to Brevo
 * @param WP_REST_Request $req Request object
 * @return WP_REST_Response Lead submission result
 */
function yht_api_lead(WP_REST_Request $req){
  try {
    $p = $req->get_json_params();
    if(!$p) {
      return rest_ensure_response(array('ok'=>false,'message'=>'Dati richiesta non validi'));
    }
    
    $email = sanitize_email($p['email'] ?? '');
    $name  = sanitize_text_field($p['name'] ?? '');
    $payload = wp_kses_post($p['payload'] ?? '');
    
    // Validate required fields
    if(empty($email) || empty($name)) {
      return rest_ensure_response(array('ok'=>false,'message'=>'Nome ed email sono obbligatori'));
    }
    
    if(!is_email($email)) {
      return rest_ensure_response(array('ok'=>false,'message'=>'Formato email non valido'));
    }
    
    $settings = yht_get_settings();

    // Send internal notification email
    if(!empty($settings['notify_email'])){
      $subject = 'Nuovo lead YHT - ' . $name;
      $message = "Nome: $name\nEmail: $email\n\n$payload";
      wp_mail($settings['notify_email'], $subject, $message);
    }

    // Brevo integration (if API key is present)
    $ok = true; 
    $msg = 'Lead ricevuto';
    
    if(!empty($settings['brevo_api_key'])){
      $body = array(
        'email'=>$email,
        'attributes'=> array(
          'NOME'=>$name,
          'ORIGINE'=>'YHT Builder',
        ),
        'updateEnabled'=> true,
        'listIds'=> array()
      );
      
      $resp = wp_remote_post('https://api.brevo.com/v3/contacts', array(
        'headers'=> array(
          'accept'=>'application/json',
          'api-key'=>$settings['brevo_api_key'],
          'content-type'=>'application/json'
        ),
        'body'=> wp_json_encode($body),
        'timeout'=> YHT_DEFAULT_TIMEOUT
      ));
      
      if(is_wp_error($resp)){
        $ok = false; 
        $msg = 'Errore connessione Brevo: ' . $resp->get_error_message();
        error_log('YHT Brevo Error: ' . $resp->get_error_message());
      } else {
        $code = wp_remote_retrieve_response_code($resp);
        if($code >= 400){ 
          $ok = false; 
          $msg = 'Errore Brevo ' . $code . ': ' . wp_remote_retrieve_body($resp);
          error_log('YHT Brevo API Error: ' . $code . ' - ' . wp_remote_retrieve_body($resp));
        }
      }
    }

    return rest_ensure_response(array('ok'=>$ok,'message'=>$msg));
    
  } catch(Exception $e) {
    error_log('YHT API Lead Error: ' . $e->getMessage());
    return rest_ensure_response(array('ok'=>false,'message'=>'Errore interno del server'));
  }
}

/**
 * REST API endpoint to create WooCommerce product from tour
 * @param WP_REST_Request $req Request object
 * @return WP_REST_Response Product creation result
 */
function yht_api_wc_create_product(WP_REST_Request $req){
  if(!class_exists('WC_Product_Simple')) return rest_ensure_response(array('ok'=>false,'message'=>'WooCommerce non attivo'));
  $p = $req->get_json_params();
  $tour = $p['tour'] ?? array();
  $state = $p['state'] ?? array();

  $settings = yht_get_settings();
  $price_per_pax = (float)$settings['wc_price_per_pax'];
  $pax = max(1, intval($p['pax'] ?? 2));
  $price = $price_per_pax * $pax;

  $title = sanitize_text_field($tour['name'] ?? 'Pacchetto tour');
  $desc = 'Itinerario: ';
  if(!empty($tour['days'])){
    $rows = array();
    foreach($tour['days'] as $d){
      $rows[] = 'Giorno '.$d['day'].': '.implode(' · ', array_map(function($s){ return ($s['time']??'').' '.($s['title']??''); }, $d['stops']));
    }
    $desc .= implode("\n",$rows);
  }

  $product = new WC_Product_Simple();
  $product->set_name($title.' – '.$pax.' pax');
  $product->set_regular_price($price);
  $product->set_catalog_visibility('hidden');
  $product->set_description($desc);
  $product->save();

  return rest_ensure_response(array('ok'=>true,'product_id'=>$product->get_id(),'price'=>$price));
}

/* ---------------- PDF (dompdf) ---------------- */
/**
 * Check if dompdf library is available
 * @return bool True if dompdf is available
 */
function yht_has_dompdf(){
  $vendor = plugin_dir_path(__FILE__).'vendor/autoload.php';
  $alt    = plugin_dir_path(__FILE__).'vendor/dompdf/autoload.inc.php';
  if(file_exists($vendor)){ require_once $vendor; return class_exists('\\Dompdf\\Dompdf'); }
  if(file_exists($alt)){ require_once $alt; return class_exists('\\Dompdf\\Dompdf'); }
  return false;
}

/**
 * REST API endpoint to generate PDF from tour data
 * @param WP_REST_Request $req Request object
 * @return WP_REST_Response PDF generation result
 */
function yht_api_pdf(WP_REST_Request $req){
  $p = $req->get_json_params();
  $state = $p['state'] ?? array();
  $tour  = $p['tour'] ?? array();
  $map_png = $p['map_png'] ?? '';

  if(!yht_has_dompdf()){
    return rest_ensure_response(array('ok'=>false,'error'=>'dompdf_not_found','message'=>'Dompdf non trovato. Installa vendor/dompdf o Composer vendor.'));
  }

  $html = yht_render_pdf_html($state, $tour, $map_png);
  $upload_dir = wp_upload_dir();
  $dir = trailingslashit($upload_dir['basedir']).'yht';
  if(!file_exists($dir)) wp_mkdir_p($dir);
  $file = $dir.'/yht_itinerario_'.time().'.pdf';

  try{
    $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled'=>true,'isHtml5ParserEnabled'=>true]);
    $dompdf->loadHtml($html,'UTF-8');
    $dompdf->setPaper('A4','portrait');
    $dompdf->render();
    file_put_contents($file, $dompdf->output());
  }catch(\Throwable $e){
    return rest_ensure_response(array('ok'=>false,'message'=>'Errore PDF: '.$e->getMessage()));
  }

  $url = trailingslashit($upload_dir['baseurl']).'yht/'.basename($file);
  return rest_ensure_response(array('ok'=>true,'url'=>$url));
}

function yht_render_pdf_html($state, $tour, $map_png){
  $css = "
    <style>
      body{font-family: DejaVu Sans,Arial,sans-serif;color:#111827}
      h1{font-size:20px;margin:0 0 6px}
      h2{font-size:16px;margin:14px 0 6px}
      .badge{display:inline-block;background:#eef2ff;border:1px solid #dbeafe;border-radius:999px;padding:2px 8px;font-size:11px;margin-right:6px}
      .box{border:1px solid #e5e7eb;border-radius:8px;padding:10px;margin:8px 0}
      .small{color:#6b7280;font-size:12px}
      .hr{border-top:1px solid #e5e7eb;margin:10px 0}
      ul{padding-left:18px;margin:6px 0}
      img.map{width:100%;max-height:380px;object-fit:cover;border:1px solid #e5e7eb;border-radius:8px}
    </style>";
  $share = home_url(add_query_arg('yht', base64_encode(json_encode($state)), '/'));
  $head = "<h1>Your Hidden Trip – Itinerario</h1><div class='small'>Link: {$share}</div>";

  $scelte = sprintf(
    '<span class="badge">Viaggiatore: <b>%s</b></span>
     <span class="badge">Esperienze: <b>%s</b></span>
     <span class="badge">Aree: <b>%s</b></span>
     <span class="badge">Durata: <b>%s</b></span>
     <span class="badge">Partenza: <b>%s</b></span>
     <span class="badge">Persone: <b>%s</b></span>',
    esc_html($state['travelerType'] ?? '-'),
    esc_html(implode(', ', $state['esperienze'] ?? [])),
    esc_html(implode(', ', $state['luogo'] ?? [])),
    esc_html($state['durata'] ?? '-'),
    esc_html($state['startdate'] ?? '-'),
    esc_html($state['pax'] ?? '2')
  );

  $prog = '';
  foreach(($tour['days'] ?? []) as $d){
    $stops = array_map(function($s){
      return esc_html(($s['time'] ?? '').' '.($s['title'] ?? ''));
    }, $d['stops'] ?? []);
    $prog .= '<li><b>Giorno '.$d['day'].'</b>: '.implode(' · ', $stops).'</li>';
  }

  $map = '';
  if(is_string($map_png) && strpos($map_png, 'data:image') === 0){
    $map = '<h2>Mappa</h2><img class="map" src="'.$map_png.'" alt="Mappa itinerario"/>';
  } else {
    $map = '<div class="small">Mappa non inclusa (snapshot non disponibile).</div>';
  }

  return "<html><head><meta charset='utf-8'>{$css}</head><body>
    {$head}
    <div class='box'>{$scelte}</div>
    <h2>".esc_html($tour['name'] ?? 'Itinerario')."</h2>
    <div class='small'>Ingressi stimati: €".esc_html($tour['totalEntryCost'] ?? 0)."</div>
    <div class='hr'></div>
    <ul>{$prog}</ul>
    <div class='hr'></div>
    {$map}
    <div class='hr'></div>
    <div class='small'>PDF generato automaticamente. YourHiddenTrip.</div>
  </body></html>";
}

/* ---------------------------------------------------------
 * 6) SHORTCODE BUILDER (UI + LOGICA)
 * --------------------------------------------------------- */

/**
 * Get the CSS styles for the trip builder interface
 * @return string CSS styles
 */
function yht_get_builder_styles() {
  return '<style>
    :root{
      --bg:#f8fafc; --text:#111827; --muted:#6b7280; --card:#ffffff; --line:#e5e7eb;
      --primary:#10b981; --primary-600:#059669; --accent:#38bdf8; --danger:#ef4444; --warning:#f59e0b;
      --radius:14px; --shadow:0 10px 25px rgba(0,0,0,.08);
    }
    .yht-wrap{max-width:980px;margin:0 auto;padding:20px;background:var(--bg);color:var(--text);font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial;border-radius:var(--radius);box-shadow:var(--shadow);position:relative;overflow:hidden}
    .yht-header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
    .yht-badge{font-size:.78rem;border:1px solid var(--line);padding:4px 10px;border-radius:999px;color:var(--muted)}
    .yht-title{font-size:1.4rem;font-weight:700}
    .yht-progressbar{height:8px;background:var(--line);border-radius:999px;margin:14px 0 22px;overflow:hidden}
    .yht-progressbar>i{display:block;height:100%;width:0;background:linear-gradient(90deg,var(--primary),#34d399);transition:width .4s ease}
    .yht-steps{display:flex;align-items:center;gap:10px;margin-bottom:20px}
    .yht-step{width:38px;height:38px;border-radius:999px;background:#dde2e7;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;cursor:not-allowed;position:relative;user-select:none}
    .yht-step[data-active="true"]{background:var(--primary);cursor:pointer}
    .yht-step[data-done="true"]{background:#4b5563;cursor:pointer}
    .yht-step::after{content:attr(data-label);position:absolute;top:-22px;white-space:nowrap;font-size:.78rem;color:var(--muted)}
    .yht-line{flex:1;height:4px;background:var(--line);border-radius:4px;position:relative;overflow:hidden}
    .yht-line>i{position:absolute;inset:0;width:0;background:var(--primary);transition:width .4s ease}
    .yht-stepview{display:none;opacity:0;transform:translateY(8px);transition:opacity .25s,transform .25s}
    .yht-stepview[data-show="true"]{display:block;opacity:1;transform:none}
    .yht-h2{font-size:1.25rem;margin:0 0 8px}
    .yht-help{color:var(--muted);font-size:.92rem;margin-bottom:14px}
    .yht-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
    @media (max-width:720px){ .yht-grid{grid-template-columns:1fr 1fr} }
    @media (max-width:500px){ .yht-grid{grid-template-columns:1fr} }
    .yht-card{background:var(--card);border:2px solid var(--line);border-radius:12px;padding:16px;cursor:pointer;position:relative;transition:.2s;outline:none}
    .yht-card:hover{transform:translateY(-2px);border-color:var(--primary);box-shadow:var(--shadow)}
    .yht-card[data-selected="true"]{border-color:var(--primary);box-shadow:var(--shadow)}
    .yht-ico{font-size:1.6rem;line-height:1;margin-bottom:8px}
    .yht-t{font-weight:700}
    .yht-d{color:var(--muted);font-size:.9rem;margin-top:2px}
    .yht-actions{display:flex;gap:10px;margin-top:14px;flex-wrap:wrap}
    .yht-btn{appearance:none;border:0;border-radius:10px;padding:12px 18px;font-weight:700;background:var(--primary);color:#fff;cursor:pointer}
    .yht-btn:hover{background:var(--primary-600)}
    .yht-btn.secondary{background:#e2e8f0;color:#111827}
    .yht-btn.ghost{background:transparent;border:2px solid var(--line);color:#111827}
    .yht-error{display:none;color:var(--danger);font-size:.95rem;margin:8px 0}
    .yht-error[data-show="true"]{display:block}
    .yht-summary{margin:16px 0;padding:16px;background:var(--card);border:1px solid var(--line);border-radius:12px}
    .yht-itinerary{margin-top:10px;padding:12px;background:#eef2ff;border-radius:10px;border:1px solid #dbeafe}
    .yht-badge-mini{display:inline-block;padding:2px 8px;border-radius:999px;background:#f1f5f9;color:#0f172a;font-size:.75rem;margin-right:6px}
    .yht-tourcards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin:12px 0}
    @media (max-width:900px){ .yht-tourcards{grid-template-columns:1fr 1fr} }
    @media (max-width:600px){ .yht-tourcards{grid-template-columns:1fr} }
    .yht-tour{background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px}
    .yht-tour h4{margin:.2rem 0}
    .yht-tour .meta{color:var(--muted);font-size:.9rem;margin:6px 0}
    .yht-tour .pick{margin-top:8px}
    #yht-map-inline{width:100%;height:440px;border-radius:10px;border:1px solid var(--line);margin-top:12px;overflow:hidden}
    .yht-row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .yht-col{flex:1 1 auto}
    .yht-input{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:#fff;color:#111827}
    .yht-small{font-size:.86rem;color:var(--muted)}
  </style>';
}

/**
 * Main shortcode callback for the trip builder interface
 * @return string HTML output
 */
add_shortcode('yourhiddentrip_builder', function(){
  ob_start(); ?>
<div id="yht-builder" class="yht-wrap" aria-live="polite">
  <?php echo yht_get_builder_styles(); ?>

  <div class="yht-header">
    <span class="yht-badge">Your Hidden Trip</span>
    <div class="yht-title">Crea il tuo viaggio su misura</div>
  </div>

  <div class="yht-progressbar" aria-hidden="true"><i id="yht-progress"></i></div>

  <div class="yht-steps" role="navigation" aria-label="Step di compilazione">
    <div id="yht-s1" class="yht-step" data-label="Viaggiatore" data-active="true" tabindex="0">1</div>
    <div class="yht-line"><i id="yht-l1"></i></div>
    <div id="yht-s2" class="yht-step" data-label="Esperienze" tabindex="-1">2</div>
    <div class="yht-line"><i id="yht-l2"></i></div>
    <div id="yht-s3" class="yht-step" data-label="Luogo" tabindex="-1">3</div>
    <div class="yht-line"><i id="yht-l3"></i></div>
    <div id="yht-s4" class="yht-step" data-label="Durata & Data" tabindex="-1">4</div>
    <div class="yht-line"><i id="yht-l4"></i></div>
    <div id="yht-s5" class="yht-step" data-label="Riepilogo" tabindex="-1">5</div>
  </div>

  <!-- STEP 1 -->
  <section id="yht-step1" class="yht-stepview" data-show="true" role="region" aria-labelledby="yht-h2-1">
    <h2 id="yht-h2-1" class="yht-h2">Che tipo di viaggiatore sei?</h2>
    <p class="yht-help">Scegli lo stile: incide su tappe al giorno e budget.</p>
    <div class="yht-grid" role="radiogroup" aria-label="Tipo viaggiatore">
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="travelerType" data-value="active">
        <div class="yht-ico">⚡</div><div class="yht-t">Ami fare tante cose</div><div class="yht-d">Ritmo alto (3–4 tappe/giorno)</div>
      </article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="travelerType" data-value="relaxed">
        <div class="yht-ico">☕</div><div class="yht-t">Giornata rilassata</div><div class="yht-d">Ritmo lento (1–2 tappe/giorno)</div>
      </article>
    </div>
    <p id="yht-err1" class="yht-error" aria-live="polite">Seleziona un tipo di viaggiatore.</p>
    <div class="yht-actions">
      <button class="yht-btn" data-next="1">Prosegui</button>
      <button class="yht-btn ghost" data-reset="1">Reset</button>
    </div>
  </section>

  <!-- STEP 2 -->
  <section id="yht-step2" class="yht-stepview" role="region" aria-labelledby="yht-h2-2">
    <h2 id="yht-h2-2" class="yht-h2">Che tipo di esperienza cerchi?</h2>
    <p class="yht-help">Puoi selezionare più opzioni.</p>
    <div class="yht-grid" role="group" aria-label="Esperienze">
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="esperienze" data-value="trekking"><div class="yht-ico">🥾</div><div class="yht-t">Trekking</div><div class="yht-d">Sentieri e natura</div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="esperienze" data-value="passeggiata"><div class="yht-ico">🚶</div><div class="yht-t">Passeggiata</div><div class="yht-d">Percorsi facili</div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="esperienze" data-value="cultura"><div class="yht-ico">🏛️</div><div class="yht-t">Cultura</div><div class="yht-d">Borghi, musei, siti</div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="esperienze" data-value="benessere"><div class="yht-ico">🧖</div><div class="yht-t">Benessere</div><div class="yht-d">Terme e spa</div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="esperienze" data-value="enogastronomia"><div class="yht-ico">🍷</div><div class="yht-t">Enogastronomia</div><div class="yht-d">Cantine e sapori</div></article>
    </div>
    <p id="yht-err2" class="yht-error" aria-live="polite">Seleziona almeno un’esperienza.</p>
    <div class="yht-actions">
      <button class="yht-btn" data-next="2">Prosegui</button>
      <button class="yht-btn ghost" data-reset="2">Reset</button>
    </div>
  </section>

  <!-- STEP 3 -->
  <section id="yht-step3" class="yht-stepview" role="region" aria-labelledby="yht-h2-3">
    <h2 id="yht-h2-3" class="yht-h2">Dove preferisci?</h2>
    <p class="yht-help">Seleziona aree d’interesse.</p>
    <div class="yht-grid" role="group" aria-label="Luoghi">
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="luogo" data-value="natura"><div class="yht-ico">🌳</div><div class="yht-t">Natura</div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="luogo" data-value="citta"><div class="yht-ico">🏙️</div><div class="yht-t">Città</div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="luogo" data-value="collina"><div class="yht-ico">⛰️</div><div class="yht-t">Collina</div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="luogo" data-value="lago"><div class="yht-ico">🌊</div><div class="yht-t">Lago</div></article>
      <article class="yht-card" tabindex="0" role="checkbox" aria-checked="false" data-group="luogo" data-value="centro_storico"><div class="yht-ico">🏰</div><div class="yht-t">Centro storico</div></article>
    </div>
    <p id="yht-err3" class="yht-error" aria-live="polite">Seleziona almeno un luogo.</p>
    <div class="yht-actions">
      <button class="yht-btn" data-next="3">Prosegui</button>
      <button class="yht-btn ghost" data-reset="3">Reset</button>
    </div>
  </section>

  <!-- STEP 4 -->
  <section id="yht-step4" class="yht-stepview" role="region" aria-labelledby="yht-h2-4">
    <h2 id="yht-h2-4" class="yht-h2">Quanto tempo hai?</h2>
    <p class="yht-help">Scegli durata e data di partenza.</p>
    <div class="yht-grid" role="radiogroup" aria-label="Durata">
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="1_notte"><div class="yht-ico">🌙</div><div class="yht-t">1 notte</div></article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="2_notti"><div class="yht-ico">🌙🌙</div><div class="yht-t">2 notti</div></article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="3_notti"><div class="yht-ico">🌙🌙🌙</div><div class="yht-t">3 notti</div></article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="4_notti"><div class="yht-ico">🌙🌙🌙🌙</div><div class="yht-t">4 notti</div></article>
      <article class="yht-card" tabindex="0" role="radio" aria-checked="false" data-group="durata" data-value="5+_notti"><div class="yht-ico">🌙+</div><div class="yht-t">5+ notti</div></article>
    </div>
    <div class="yht-row yht-mt">
      <div class="yht-col">
        <label for="yht-startdate" class="yht-small">Data di partenza</label>
        <input id="yht-startdate" class="yht-input" type="date" />
      </div>
      <div class="yht-col">
        <label for="yht-pax" class="yht-small">Persone</label>
        <input id="yht-pax" class="yht-input" type="number" min="1" value="2" />
      </div>
    </div>
    <p id="yht-err4" class="yht-error" aria-live="polite">Seleziona una durata e la data di partenza.</p>
    <div class="yht-actions">
      <button class="yht-btn" data-next="4">Vai al riepilogo</button>
      <button class="yht-btn ghost" data-reset="4">Reset</button>
    </div>
  </section>

  <!-- STEP 5 -->
  <section id="yht-step5" class="yht-stepview" role="region" aria-labelledby="yht-h2-5">
    <h2 id="yht-h2-5" class="yht-h2">Riepilogo</h2>
    <div class="yht-summary" id="yht-summary"></div>
    <h3 style="margin:12px 0 6px">Proposte di tour</h3>
    <div id="yht-tours" class="yht-tourcards"></div>
    <div class="yht-actions">
      <button class="yht-btn secondary" id="yht-print">Stampa</button>
      <button class="yht-btn ghost" id="yht-export">Esporta JSON</button>
      <button class="yht-btn ghost" id="yht-ics">Esporta ICS</button>
      <button class="yht-btn ghost" id="yht-pdf">Scarica PDF</button>
    </div>

    <div class="yht-row yht-mt">
      <div class="yht-col">
        <label class="yht-small">Condividi/Salva</label>
        <input id="yht-sharelink" class="yht-input" type="text" readonly />
      </div>
      <button class="yht-btn secondary" id="yht-copy">Copia link</button>
      <a id="yht-mail" class="yht-btn warning" href="#" target="_blank" rel="noopener">Richiedi preventivo</a>
      <button class="yht-btn" id="yht-wc">Crea pacchetto (Woo)</button>
    </div>

    <h3 style="margin:16px 0 6px">Mappa del tour selezionato</h3>
    <div id="yht-map-inline" aria-label="Mappa risultati"></div>
    <div class="yht-actions" style="margin-top:14px">
      <button class="yht-btn" data-backto="4">Modifica durata/data</button>
      <button class="yht-btn ghost" id="yht-reset-all">Ricomincia</button>
    </div>

    <h3 style="margin:16px 0 6px">Lasciaci i tuoi contatti</h3>
    <div class="yht-row">
      <input id="yht-name" class="yht-input" type="text" placeholder="Nome"/>
      <input id="yht-email" class="yht-input" type="email" placeholder="Email"/>
      <button class="yht-btn" id="yht-lead">Invia richiesta</button>
    </div>
  </section>

  <script>
  (function(){
    const el = (s,p=document)=>p.querySelector(s);
    const els = (s,p=document)=>Array.from(p.querySelectorAll(s));
    const REST = '<?php echo esc_js( rest_url('yht/v1') ); ?>';
    const SITE = '<?php echo esc_js( home_url() ); ?>';
    const GA4  = '<?php echo esc_js( yht_get_settings()['ga4_id'] ); ?>';

    window.dataLayer = window.dataLayer || [];
    function dl(eventName, obj){ try{ window.dataLayer.push(Object.assign({event:eventName}, obj||{})); }catch(e){} }

    const state = { travelerType:null, esperienze:[], luogo:[], durata:null, startdate:null, pax:2, chosenTour:0, tours:[] };
    const steps=[1,2,3,4,5]; let current=1;

    function bind(){
      els('.yht-card').forEach(card=>{
        card.addEventListener('click', ()=>toggleCard(card));
        card.addEventListener('keydown', e=>{ if(e.key===' '||e.key==='Enter'){ e.preventDefault(); toggleCard(card); }});
      });
      els('[data-next]').forEach(b=>b.addEventListener('click', ()=>goNext(parseInt(b.dataset.next,10))));
      els('[data-reset]').forEach(b=>b.addEventListener('click', ()=>resetStep(parseInt(b.dataset.reset,10))));
      el('#yht-reset-all')?.addEventListener('click', resetAll);
      steps.forEach(i=>{
        const n=el('#yht-s'+i);
        n.addEventListener('click', ()=>{ if(n.dataset.done==='true'||n.dataset.active==='true') showStep(i); });
      });
      el('#yht-startdate').addEventListener('change', e=>{ state.startdate=e.target.value; persist(); });
      el('#yht-pax').addEventListener('change', e=>{ state.pax=Math.max(1,parseInt(e.target.value||'2',10)); persist(); });

      // actions
      el('#yht-print').addEventListener('click', ()=>window.print());
      el('#yht-export').addEventListener('click', exportJSON);
      el('#yht-ics').addEventListener('click', exportICS);
      el('#yht-pdf').addEventListener('click', exportPDF);
      el('#yht-copy').addEventListener('click', ()=>{ navigator.clipboard.writeText(el('#yht-sharelink').value); el('#yht-copy').textContent='Copiato'; setTimeout(()=>el('#yht-copy').textContent='Copia link',1200); });
      el('#yht-lead').addEventListener('click', submitLead);
      el('#yht-wc').addEventListener('click', createWoo);
    }

    function toggleCard(card){
      const group=card.dataset.group; const value=card.dataset.value;
      if(card.getAttribute('role')==='radio'){
        els('.yht-card[data-group="'+group+'"]').forEach(c=>{ c.dataset.selected='false'; c.setAttribute('aria-checked','false'); });
        card.dataset.selected='true'; card.setAttribute('aria-checked','true'); state[group]=value;
      } else {
        const sel = card.dataset.selected==='true';
        card.dataset.selected = sel?'false':'true';
        card.setAttribute('aria-checked', sel?'false':'true');
        const arr = state[group]||[];
        if(sel){ state[group]=arr.filter(v=>v!==value); } else { if(!arr.includes(value)) arr.push(value); state[group]=arr; }
      }
      persist();
    }

    function goNext(step){
      if(step===1 && !state.travelerType){ showErr(1,true); return; }
      if(step===2 && (!state.esperienze||state.esperienze.length===0)){ showErr(2,true); return; }
      if(step===3 && (!state.luogo||state.luogo.length===0)){ showErr(3,true); return; }
      if(step===4 && (!state.durata||!state.startdate)){ showErr(4,true); return; }
      showErr(step,false);
      if(step+1===5){ generateTours(); }
      showStep(step+1);
    }

    function showErr(n,flag){ const e=el('#yht-err'+n); if(e) e.dataset.show=flag?'true':'false'; }

    function showStep(n){
      steps.forEach(i=>{
        el('#yht-step'+i).dataset.show = (i===n)?'true':'false';
        const s=el('#yht-s'+i);
        s.dataset.active = (i===n)?'true':'false';
        s.dataset.done   = (i<n)?'true':'false';
        el('#yht-l'+(i-1))?.style && (el('#yht-l'+(i-1)).style.width = (i-1)<n ? '100%':'0%');
      });
      current=n;
      el('#yht-progress').style.width = (((n-1)/(steps.length-1))*100)+'%';
      dl('yht_step_view',{step:n});
      persist();
    }

    function resetStep(n){
      if(n===1){ state.travelerType=null; els('[data-group="travelerType"]').forEach(c=>{c.dataset.selected='false'; c.setAttribute('aria-checked','false');}); }
      if(n===2){ state.esperienze=[]; els('[data-group="esperienze"]').forEach(c=>{c.dataset.selected='false'; c.setAttribute('aria-checked','false');}); }
      if(n===3){ state.luogo=[]; els('[data-group="luogo"]').forEach(c=>{c.dataset.selected='false'; c.setAttribute('aria-checked','false');}); }
      if(n===4){ state.durata=null; state.startdate=null; el('#yht-startdate').value=''; els('[data-group="durata"]').forEach(c=>{c.dataset.selected='false'; c.setAttribute('aria-checked','false');}); }
      showErr(n,false); persist();
    }
    function resetAll(){ localStorage.removeItem('yht_state'); window.location.href = window.location.pathname; }

    function buildSummary(basicBudget){
      const s=el('#yht-summary');
      s.innerHTML = `
        <p>
          <span class="yht-badge-mini">Viaggiatore: <b>${labelTraveler(state.travelerType)}</b></span>
          <span class="yht-badge-mini">Esperienze: <b>${state.esperienze.map(labelEsperienza).join(', ')}</b></span>
          <span class="yht-badge-mini">Luoghi: <b>${state.luogo.map(labelLuogo).join(', ')}</b></span>
          <span class="yht-badge-mini">Durata: <b>${labelDurata(state.durata)}</b></span>
          <span class="yht-badge-mini">Partenza: <b>${fmtDateIT(state.startdate)}</b></span>
          <span class="yht-badge-mini">Persone: <b>${state.pax}</b></span>
        </p>
        <p style="margin-top:6px"><b>Stima budget soggiorno (base):</b> ~ € ${basicBudget}</p>
      `;
    }

    function labelTraveler(v){ return v==='active'?'Attivo':'Rilassato'; }
    function labelEsperienza(v){ return ({trekking:'Trekking',passeggiata:'Passeggiata',cultura:'Cultura',benessere:'Benessere',enogastronomia:'Enogastronomia'})[v]||v; }
    function labelLuogo(v){ return ({natura:'Natura',citta:'Città',collina:'Collina',lago:'Lago',centro_storico:'Centro storico'})[v]||v; }
    function labelDurata(v){ return ({'1_notte':'1 notte','2_notti':'2 notti','3_notti':'3 notti','4_notti':'4 notti','5+_notti':'5+ notti'})[v]||'—'; }
    function fmtDateIT(d){ if(!d) return '—'; const [y,m,dd]=d.split('-'); return `${dd}/${m}/${y}`; }
    function durataToDays(v){ if(v==='1_notte') return 2; if(v==='2_notti') return 3; if(v==='3_notti') return 4; if(v==='4_notti') return 5; if(v==='5+_notti') return 7; return 2; }
    function estimateBudget(days){ const base = state.travelerType==='active'?120:90; return Math.round((base + state.esperienze.length*15)*days); }

    async function generateTours(){
      const days = durataToDays(state.durata);
      buildSummary(estimateBudget(days));
      const res = await fetch(REST+'/generate', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({
          travelerType: state.travelerType,
          esperienze: state.esperienze,
          luogo: state.luogo,
          durata: state.durata,
          startdate: state.startdate
        })
      });
      const json = await res.json();
      state.tours = json.tours || [];
      renderTours();
      buildShareLink();
      ensureLeaflet().then(initMapInline);
      dl('yht_tour_generated', {days:json.days, perDay:json.perDay});
      persist();
    }

    function renderTours(){
      const w=el('#yht-tours'); w.innerHTML='';
      state.tours.forEach((t,idx)=>{
        const meta = `${t.days.length} ${t.days.length>1?'giorni':'giorno'} · ${t.stops} tappe · €${t.totalEntryCost} ingressi (stima)`;
        const card = document.createElement('div');
        card.className='yht-tour';
        card.innerHTML = `
          <h4>${t.name}</h4>
          <div class="meta">${meta}</div>
          <div>${uniqExp(t).map(x=>`<span class="yht-badge-mini">${labelEsperienza(x)}</span>`).join(' ')}</div>
          <div class="pick"><button class="yht-btn secondary" data-pick="${idx}">Seleziona</button></div>
        `;
        w.appendChild(card);
      });
      els('[data-pick]').forEach(b=>b.addEventListener('click', e=>{
        state.chosenTour = parseInt(e.currentTarget.dataset.pick,10);
        renderChosenSummary(); ensureLeaflet().then(initMapInline);
        dl('yht_tour_selected',{tour_index:state.chosenTour});
        persist();
      }));
      state.chosenTour = 0; renderChosenSummary();
    }

    function uniqExp(t){ const s=new Set(); t.days.forEach(d=>d.stops.forEach(p=> (p.exp||[]).forEach(e=>s.add(e)))); return Array.from(s); }

    function renderChosenSummary(){
      const t = state.tours[state.chosenTour] || state.tours[0];
      if(!t) return;
      const wrap=el('#yht-summary');
      const html = `
        <div class="yht-itinerary">
          <b>${t.name} – Programma</b>
          <ul class="yht-list">
            ${t.days.map(d=>`<li><b>Giorno ${d.day}:</b> ${d.stops.map(s=>`${s.time} ${s.title}`).join(' · ')}</li>`).join('')}
          </ul>
          <p style="margin-top:6px">Ingressi/degustazioni stimati: ~ €${t.totalEntryCost}</p>
        </div>`;
      const old = wrap.querySelector('.yht-itinerary'); if(old) old.remove();
      wrap.insertAdjacentHTML('beforeend', html);

      // mailto
      const url = buildShareLink();
      const subj = encodeURIComponent('Richiesta preventivo – Your Hidden Trip');
      const body = encodeURIComponent(
        `Ciao, vorrei un preventivo per questo itinerario:\n\n`+
        `Viaggiatore: ${labelTraveler(state.travelerType)}\n`+
        `Esperienze: ${state.esperienze.map(labelEsperienza).join(', ')}\n`+
        `Luoghi: ${state.luogo.map(labelLuogo).join(', ')}\n`+
        `Durata: ${labelDurata(state.durata)}\n`+
        `Partenza: ${fmtDateIT(state.startdate)}\n`+
        `Persone: ${state.pax}\n\n`+
        `Link: ${url}`
      );
      el('#yht-mail').href = `mailto:info@yourhiddentrip.com?subject=${subj}&body=${body}`;
    }

    function buildShareLink(){
      const data = btoa(unescape(encodeURIComponent(JSON.stringify(state))));
      const url = new URL(window.location.href);
      url.searchParams.set('yht', data);
      el('#yht-sharelink').value = url.toString();
      return url.toString();
    }

    // MAPPA
    let LReady=false, LIReady=false, mapInstance=null;
    function ensureLeaflet(){
      return new Promise((res)=>{
        if(LReady){ res(); return; }
        if(!document.getElementById('leaflet-css')){
          const link=document.createElement('link'); link.id='leaflet-css'; link.rel='stylesheet';
          link.href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'; document.head.appendChild(link);
        }
        if(!document.getElementById('leaflet-js')){
          const s=document.createElement('script'); s.id='leaflet-js';
          s.src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
          s.onload=()=>{ LReady=true; res(); }; document.body.appendChild(s);
        } else { LReady=true; res(); }
      });
    }
    function ensureLeafletImage(){
      return new Promise((res)=>{
        if(LIReady){ res(); return; }
        if(!document.getElementById('leaflet-image-js')){
          const s=document.createElement('script'); s.id='leaflet-image-js';
          s.src='https://unpkg.com/leaflet-image@0.0.4/leaflet-image.js';
          s.onload=()=>{ LIReady=true; res(); };
          document.body.appendChild(s);
        } else { LIReady=true; res(); }
      });
    }

    function initMapInline(){
      const c = el('#yht-map-inline'); if(!c) return;
      if(mapInstance){ mapInstance.remove(); mapInstance=null; c.innerHTML=''; }
      mapInstance = L.map(c, {scrollWheelZoom:true});
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'&copy; OpenStreetMap'}).addTo(mapInstance);

      const t = state.tours[state.chosenTour] || state.tours[0]; if(!t) return;
      const colors = ['#ef4444','#3b82f6','#10b981','#f59e0b','#8b5cf6','#06b6d4','#84cc16'];
      const markers=[];
      t.days.forEach((d,idx)=>{
        const latlngs = d.stops.map(s=>[s.lat,s.lng]);
        if(latlngs.length>1) L.polyline(latlngs, {color:colors[idx%colors.length], weight:4, opacity:0.85}).addTo(mapInstance);
        d.stops.forEach(s=>{
          markers.push(L.marker([s.lat,s.lng]).addTo(mapInstance)
            .bindPopup(`<b>${s.title}</b><br>${s.time} · ${(s.exp||[]).map(labelEsperienza).join(', ')}<br><i>${(s.area||[]).map(labelLuogo).join(', ')}</i>`));
        });
      });
      if(markers.length){
        const group = L.featureGroup(markers);
        mapInstance.fitBounds(group.getBounds().pad(0.2));
      } else {
        mapInstance.setView([42.75,12.45], 8);
      }
    }

    // EXPORTS
    function exportJSON(){
      const t = state.tours[state.chosenTour] || {};
      const payload = {state, selectedTourIndex: state.chosenTour, selectedTour: t};
      const blob = new Blob([JSON.stringify(payload,null,2)], {type:'application/json'});
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob); a.download='your-hidden-trip.json'; a.click();
      dl('yht_export_json');
    }
    function exportICS(){
      const t = state.tours[state.chosenTour] || {};
      const start = state.startdate ? new Date(state.startdate+'T09:00:00') : new Date();
      let ics = 'BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//YourHiddenTrip//TripBuilder//IT\r\n';
      (t.days||[]).forEach((d,idx)=>{
        (d.stops||[]).forEach((s,i)=>{
          const dt = new Date(start); dt.setDate(start.getDate()+idx);
          const hhmm = (s.time||'10:00').split(':'); dt.setHours(parseInt(hhmm[0]||'10',10), parseInt(hhmm[1]||'00',10),0,0);
          const dtend = new Date(dt); dtend.setHours(dt.getHours()+2);
          function pad(n){ return (n<10?'0':'')+n; }
          const DTSTART = dt.getFullYear()+pad(dt.getMonth()+1)+pad(dt.getDate())+'T'+pad(dt.getHours())+pad(dt.getMinutes())+'00';
          const DTEND  = dtend.getFullYear()+pad(dtend.getMonth()+1)+pad(dtend.getDate())+'T'+pad(dtend.getHours())+pad(dtend.getMinutes())+'00';
          const uid = `yht-${(s.id||'stop')}-${idx}-${i}@yourhiddentrip`;
          const loc = `${s.title}\\, ${((s.area||[]).map(labelLuogo).join('/'))} (${d.day})`;
          function esc(x){ return (x||'').replace(/,/g,'\\,').replace(/;/g,'\\;').replace(/\n/g,'\\n'); }
          ics += 'BEGIN:VEVENT\r\n';
          ics += `UID:${uid}\r\nDTSTAMP:${DTSTART}\r\nDTSTART:${DTSTART}\r\nDTEND:${DTEND}\r\n`;
          ics += `SUMMARY:${esc(t.name)} – ${esc(s.title)}\r\nLOCATION:${esc(loc)}\r\nDESCRIPTION:${esc((s.exp||[]).join(', '))}\r\nEND:VEVENT\r\n`;
        });
      });
      ics += 'END:VCALENDAR\r\n';
      const blob = new Blob([ics], {type:'text/calendar;charset=utf-8'});
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob); a.download='your-hidden-trip.ics'; a.click();
      dl('yht_export_ics');
    }

    async function exportPDF(){
      await ensureLeaflet(); await ensureLeafletImage();
      let map_png = '';
      try{
        if(typeof window.leafletImage==='function' && mapInstance){
          await new Promise((resolve)=>{
            window.leafletImage(mapInstance, function(err, canvas){
              if(!err && canvas){ map_png = canvas.toDataURL('image/png'); }
              resolve();
            });
          });
        }
      }catch(e){}
      const tour = state.tours[state.chosenTour] || {};
      const res = await fetch(REST+'/pdf', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ state, tour, map_png })
      });
      const j = await res.json();
      if(j.ok && j.url){
        window.open(j.url, '_blank');
        dl('yht_export_pdf',{ok:true});
      } else {
        alert('PDF non disponibile. '+(j.message||'Installa dompdf in /vendor/.'));
        dl('yht_export_pdf',{ok:false});
      }
    }

    // LEAD
    async function submitLead(){
      const name = el('#yht-name').value.trim();
      const email= el('#yht-email').value.trim();
      const t = state.tours[state.chosenTour] || {};
      const payload = JSON.stringify({state, tour:t}, null, 2);
      const res = await fetch(REST+'/lead', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({name, email, payload})
      });
      const j = await res.json();
      alert(j.ok?'Richiesta inviata. Ti contatteremo a breve.':'Errore: '+j.message);
      dl('yht_lead_submitted',{ok:j.ok});
    }

    // WOO PRODUCT
    async function createWoo(){
      const t = state.tours[state.chosenTour] || {};
      const res = await fetch(REST+'/wc_create_product', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({tour:t, state, pax: state.pax})
      });
      const j = await res.json();
      if(j.ok){ alert('Prodotto creato (ID: '+j.product_id+') – Prezzo: €'+j.price); }
      else { alert('WooCommerce: '+j.message); }
      dl('yht_wc_product_created',{ok:j.ok});
    }

    // SHARE / PERSIST
    function persist(){ localStorage.setItem('yht_state', JSON.stringify({state,current})); }
    function restore(){
      const usp = new URLSearchParams(window.location.search);
      if(usp.has('yht')){
        try{ const parsed = JSON.parse(decodeURIComponent(escape(atob(usp.get('yht'))))); applyState(parsed); return; }catch(e){}
      }
      try{ const saved = JSON.parse(localStorage.getItem('yht_state')); if(saved && saved.state){ applyState(saved.state); if(saved.current) showStep(saved.current); } }catch(e){}
    }
    function applyState(s){
      ['travelerType','durata','startdate','pax','chosenTour'].forEach(k=>{ if(s[k]!==undefined) state[k]=s[k]; });
      ['esperienze','luogo','tours'].forEach(k=>{ if(Array.isArray(s[k])) state[k]=s[k]; });
      if(state.travelerType){ const c=el(`.yht-card[data-group="travelerType"][data-value="${state.travelerType}"]`); if(c){ c.dataset.selected='true'; c.setAttribute('aria-checked','true');}}
      (state.esperienze||[]).forEach(v=>{ const c=el(`.yht-card[data-group="esperienze"][data-value="${v}"]`); if(c){ c.dataset.selected='true'; c.setAttribute('aria-checked','true'); }});
      (state.luogo||[]).forEach(v=>{ const c=el(`.yht-card[data-group="luogo"][data-value="${v}"]`); if(c){ c.dataset.selected='true'; c.setAttribute('aria-checked','true'); }});
      if(state.durata){ const c=el(`.yht-card[data-group="durata"][data-value="${state.durata}"]`); if(c){ c.dataset.selected='true'; c.setAttribute('aria-checked','true'); } }
      if(state.startdate){ el('#yht-startdate').value = state.startdate; }
      if(state.pax){ el('#yht-pax').value = state.pax; }
      const stepIndex = computeMaxStep(); showStep(stepIndex);
      if(stepIndex===5){
        buildSummary(estimateBudget(durataToDays(state.durata)));
        if(!state.tours.length){ generateTours(); } else { renderTours(); buildShareLink(); ensureLeaflet().then(initMapInline); }
      }
    }
    function computeMaxStep(){
      if(!state.travelerType) return 1;
      if(!state.esperienze || state.esperienze.length===0) return 2;
      if(!state.luogo || state.luogo.length===0) return 3;
      if(!state.durata || !state.startdate) return 4;
      return 5;
    }

    // init
    bind(); restore();

  })();
  </script>
</div>
<?php
  return ob_get_clean();
});

/* ---------------------------------------------------------
 * 7) GA4 (opzionale) – dataLayer base (carica GTM/GA4 dal tema)
 * --------------------------------------------------------- */
add_action('wp_head', function(){
  $s = yht_get_settings();
  if(!empty($s['ga4_id'])){
    echo "<script>window.dataLayer=window.dataLayer||[];</script>\n";
  }
}, 5);
