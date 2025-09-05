<?php
/**
 * Handle CSV Import functionality
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_Importer {
    
    /**
     * Render importer page
     */
    public function render_page() {
        if(!current_user_can('manage_options')) return;
        
        $message = '';
        
        // Handle download templates
        if(isset($_GET['download_template'])) {
            $this->download_template($_GET['download_template']);
            return;
        }
        
        // Handle import
        if(isset($_POST['yht_import'])) {
            $message = $this->process_import();
        }
        
        // Handle utility functions
        if(isset($_POST['yht_set_featured'])) {
            $message = $this->set_featured_images();
        }
        
        $this->render_form($message);
    }
    
    /**
     * Process CSV import
     */
    private function process_import() {
        check_admin_referer('yht_import');
        $type = sanitize_text_field($_POST['yht_type'] ?? 'luoghi');
        
        if(empty($_FILES['yht_csv']['tmp_name'])) {
            return 'Nessun file selezionato.';
        }
        
        // Validate file upload
        if (!isset($_FILES['yht_csv']['error']) || $_FILES['yht_csv']['error'] !== UPLOAD_ERR_OK) {
            return 'Errore nel caricamento del file CSV.';
        }
        
        $file_path = $_FILES['yht_csv']['tmp_name'];
        if (!is_readable($file_path)) {
            return 'File CSV non leggibile.';
        }
        
        // Set time limit for large imports
        set_time_limit(300);
        
        return $this->import_csv_data($file_path, $type);
    }
    
    /**
     * Import CSV data with full implementation
     */
    private function import_csv_data($file_path, $type) {
        $imported = 0;
        $errors = array();
        $created_posts = array(); // For rollback capability
        
        try {
            // Open and parse CSV
            $handle = fopen($file_path, 'r');
            if (!$handle) {
                return 'Impossibile aprire il file CSV.';
            }
            
            // Get headers from first row
            $headers = fgetcsv($handle);
            if (!$headers) {
                fclose($handle);
                return 'File CSV vuoto o formato non valido.';
            }
            
            // Validate headers based on type
            $required_headers = $this->get_required_headers($type);
            $missing_headers = array_diff($required_headers, $headers);
            if (!empty($missing_headers)) {
                fclose($handle);
                return 'Colonne mancanti nel CSV: ' . implode(', ', $missing_headers);
            }
            
            $line_number = 1; // Start from 1 (header row)
            
            // Process each row
            while (($row = fgetcsv($handle)) !== FALSE) {
                $line_number++;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Combine headers with row data
                if (count($row) !== count($headers)) {
                    $errors[] = "Riga $line_number: numero di colonne non corrispondente.";
                    continue;
                }
                
                $data = array_combine($headers, $row);
                
                // Process this row
                $result = $this->import_single_row($data, $type, $line_number);
                if (is_wp_error($result)) {
                    $errors[] = "Riga $line_number: " . $result->get_error_message();
                } else {
                    $imported++;
                    $created_posts[] = $result;
                }
                
                // Stop if too many errors
                if (count($errors) > 10) {
                    $errors[] = "Troppi errori, importazione interrotta.";
                    break;
                }
            }
            
            fclose($handle);
            
            // Prepare result message
            $message = "Importazione completata: $imported elementi importati.";
            if (!empty($errors)) {
                $message .= " Errori riscontrati:\n" . implode("\n", array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= "\n...e altri " . (count($errors) - 5) . " errori.";
                }
            }
            
            return $message;
            
        } catch (Exception $e) {
            // Rollback created posts on fatal error
            foreach ($created_posts as $post_id) {
                wp_delete_post($post_id, true);
            }
            return 'Errore fatale durante l\'importazione: ' . $e->getMessage();
        }
    }
    
    /**
     * Get required headers for each import type
     */
    private function get_required_headers($type) {
        switch ($type) {
            case 'luoghi':
                return array('title', 'descr', 'lat', 'lng');
            case 'alloggi':
                return array('title', 'descr', 'lat', 'lng');
            case 'servizi':
                return array('title', 'descr', 'lat', 'lng', 'tipo_servizio');
            case 'tours':
                return array('title', 'descr', 'prezzo_base');
            default:
                return array('title', 'descr');
        }
    }
    
    /**
     * Import a single row of data
     */
    private function import_single_row($data, $type, $line_number) {
        // Validate required fields
        $title = trim($data['title'] ?? '');
        if (empty($title)) {
            return new WP_Error('missing_title', 'Titolo mancante.');
        }
        
        $description = trim($data['descr'] ?? '');
        
        // Check for existing post with same title (prevent duplicates)
        $existing_post = get_page_by_title($title, OBJECT, 'yht_' . rtrim($type, 's'));
        if ($type === 'tours') {
            $existing_post = get_page_by_title($title, OBJECT, 'yht_tour');
        }
        
        if ($existing_post) {
            return new WP_Error('duplicate_title', "Post con titolo '$title' già esistente (ID: {$existing_post->ID}).");
        }
        
        // Determine post type
        $post_type = 'yht_' . rtrim($type, 's'); // Remove 's' if present
        if ($type === 'tours') {
            $post_type = 'yht_tour';
        }
        
        // Create the post
        $post_data = array(
            'post_title'   => sanitize_text_field($title),
            'post_content' => sanitize_textarea_field($description),
            'post_type'    => $post_type,
            'post_status'  => 'publish',
            'meta_input'   => array()
        );
        
        // Add import metadata
        $post_data['meta_input']['yht_imported_at'] = current_time('timestamp');
        $post_data['meta_input']['yht_import_source'] = 'csv_import';
        $post_data['meta_input']['yht_import_line'] = $line_number;
        
        // Add type-specific processing
        switch ($type) {
            case 'luoghi':
                $result = $this->process_luoghi_data($post_data, $data);
                break;
            case 'alloggi':
                $result = $this->process_alloggi_data($post_data, $data);
                break;
            case 'servizi':
                $result = $this->process_servizi_data($post_data, $data);
                break;
            case 'tours':
                $result = $this->process_tours_data($post_data, $data);
                break;
            default:
                return new WP_Error('invalid_type', 'Tipo di importazione non valido.');
        }
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $post_data = $result;
        
        // Insert the post
        $post_id = wp_insert_post($post_data);
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Process taxonomies after post creation
        $this->process_taxonomies($post_id, $data, $type);
        
        return $post_id;
    }
    
    /**
     * Process luoghi-specific data
     */
    private function process_luoghi_data($post_data, $data) {
        // Validate coordinates
        $lat = $this->validate_coordinate($data['lat'] ?? '', 'latitudine');
        $lng = $this->validate_coordinate($data['lng'] ?? '', 'longitudine');
        
        if (is_wp_error($lat)) return $lat;
        if (is_wp_error($lng)) return $lng;
        
        $post_data['meta_input']['yht_lat'] = $lat;
        $post_data['meta_input']['yht_lng'] = $lng;
        
        // Optional fields
        if (!empty($data['costo_ingresso'])) {
            $cost = floatval($data['costo_ingresso']);
            if ($cost >= 0) {
                $post_data['meta_input']['yht_cost_ingresso'] = $cost;
            }
        }
        
        if (!empty($data['durata_min'])) {
            $duration = intval($data['durata_min']);
            if ($duration > 0) {
                $post_data['meta_input']['yht_durata_min'] = $duration;
            }
        }
        
        // Boolean accessibility fields
        $post_data['meta_input']['yht_accesso_family'] = $this->parse_boolean($data['family'] ?? '');
        $post_data['meta_input']['yht_accesso_pet'] = $this->parse_boolean($data['pet'] ?? '');
        $post_data['meta_input']['yht_accesso_mobility'] = $this->parse_boolean($data['mobility'] ?? '');
        
        return $post_data;
    }
    
    /**
     * Process alloggi-specific data
     */
    private function process_alloggi_data($post_data, $data) {
        // Validate coordinates
        $lat = $this->validate_coordinate($data['lat'] ?? '', 'latitudine');
        $lng = $this->validate_coordinate($data['lng'] ?? '', 'longitudine');
        
        if (is_wp_error($lat)) return $lat;
        if (is_wp_error($lng)) return $lng;
        
        $post_data['meta_input']['yht_lat'] = $lat;
        $post_data['meta_input']['yht_lng'] = $lng;
        
        // Optional fields
        if (!empty($data['fascia_prezzo'])) {
            $post_data['meta_input']['yht_fascia_prezzo'] = sanitize_text_field($data['fascia_prezzo']);
        }
        
        if (!empty($data['capienza'])) {
            $capacity = intval($data['capienza']);
            if ($capacity > 0) {
                $post_data['meta_input']['yht_capienza'] = $capacity;
            }
        }
        
        // Process services (pipe-separated)
        if (!empty($data['servizi|pipe'])) {
            $services = array_map('trim', explode('|', $data['servizi|pipe']));
            $post_data['meta_input']['yht_servizi_json'] = wp_json_encode($services);
        }
        
        return $post_data;
    }
    
    /**
     * Process servizi-specific data
     */
    private function process_servizi_data($post_data, $data) {
        // Validate coordinates
        $lat = $this->validate_coordinate($data['lat'] ?? '', 'latitudine');
        $lng = $this->validate_coordinate($data['lng'] ?? '', 'longitudine');
        
        if (is_wp_error($lat)) return $lat;
        if (is_wp_error($lng)) return $lng;
        
        $post_data['meta_input']['yht_lat'] = $lat;
        $post_data['meta_input']['yht_lng'] = $lng;
        
        // Required field for servizi
        if (empty($data['tipo_servizio'])) {
            return new WP_Error('missing_service_type', 'Tipo servizio mancante.');
        }
        
        // Optional fields
        if (!empty($data['fascia_prezzo'])) {
            $post_data['meta_input']['yht_fascia_prezzo'] = sanitize_text_field($data['fascia_prezzo']);
        }
        
        if (!empty($data['orari'])) {
            $post_data['meta_input']['yht_orari'] = sanitize_text_field($data['orari']);
        }
        
        if (!empty($data['telefono'])) {
            $post_data['meta_input']['yht_telefono'] = sanitize_text_field($data['telefono']);
        }
        
        if (!empty($data['sito_web'])) {
            $url = esc_url_raw($data['sito_web']);
            if ($url) {
                $post_data['meta_input']['yht_sito_web'] = $url;
            }
        }
        
        return $post_data;
    }
    
    /**
     * Process tours-specific data
     */
    private function process_tours_data($post_data, $data) {
        // Required field
        if (empty($data['prezzo_base'])) {
            return new WP_Error('missing_price', 'Prezzo base mancante.');
        }
        
        $price = floatval($data['prezzo_base']);
        if ($price <= 0) {
            return new WP_Error('invalid_price', 'Prezzo base non valido.');
        }
        
        $post_data['meta_input']['yht_prezzo_base'] = $price;
        
        // Process JSON itinerary if provided
        if (!empty($data['giorni_json'])) {
            $giorni_data = json_decode($data['giorni_json'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($giorni_data)) {
                $post_data['meta_input']['yht_giorni'] = wp_json_encode($giorni_data);
            }
        }
        
        return $post_data;
    }
    
    /**
     * Process taxonomies for imported posts
     */
    private function process_taxonomies($post_id, $data, $type) {
        // Process pipe-separated taxonomy fields
        $taxonomy_mappings = array(
            'esperienze|pipe' => 'yht_esperienza',
            'aree|pipe' => 'yht_area',
            'stagioni|pipe' => 'yht_stagione',
            'tipo_servizio' => 'yht_tipo_servizio'
        );
        
        foreach ($taxonomy_mappings as $field => $taxonomy) {
            if (empty($data[$field])) continue;
            
            $terms = array();
            if (strpos($field, '|pipe') !== false) {
                // Pipe-separated values
                $terms = array_map('trim', explode('|', $data[$field]));
            } else {
                // Single value
                $terms = array(trim($data[$field]));
            }
            
            if (!empty($terms)) {
                // Get or create terms
                $term_ids = array();
                foreach ($terms as $term_name) {
                    if (empty($term_name)) continue;
                    
                    $term = get_term_by('name', $term_name, $taxonomy);
                    if (!$term) {
                        $result = wp_insert_term($term_name, $taxonomy);
                        if (!is_wp_error($result)) {
                            $term_ids[] = $result['term_id'];
                        }
                    } else {
                        $term_ids[] = $term->term_id;
                    }
                }
                
                if (!empty($term_ids)) {
                    wp_set_object_terms($post_id, $term_ids, $taxonomy);
                }
            }
        }
    }
    
    /**
     * Validate coordinate value
     */
    private function validate_coordinate($value, $type) {
        if (empty($value)) {
            return new WP_Error('missing_coordinate', "Coordinata $type mancante.");
        }
        
        $coord = floatval($value);
        
        // Basic coordinate validation
        if ($type === 'latitudine' && ($coord < -90 || $coord > 90)) {
            return new WP_Error('invalid_latitude', 'Latitudine non valida (deve essere tra -90 e 90).');
        }
        
        if ($type === 'longitudine' && ($coord < -180 || $coord > 180)) {
            return new WP_Error('invalid_longitude', 'Longitudine non valida (deve essere tra -180 e 180).');
        }
        
        return $coord;
    }
    
    /**
     * Parse boolean values from CSV
     */
    private function parse_boolean($value) {
        $value = strtolower(trim($value));
        return in_array($value, array('1', 'true', 'yes', 'si', 'sì')) ? '1' : '';
    }
    
    /**
     * Set featured images from attachments
     */
    private function set_featured_images() {
        check_admin_referer('yht_import');
        
        $query = new WP_Query(array(
            'post_type' => array('yht_luogo','yht_alloggio','yht_servizio','yht_tour'),
            'posts_per_page' => -1,
            'no_found_rows' => true,
            'meta_query' => array(
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        
        $done = 0;
        $processed = 0;
        
        while($query->have_posts()) { 
            $query->the_post();
            $processed++;
            
            // Skip if already has featured image
            if(has_post_thumbnail()) continue;
            
            $attachments = get_children(array(
                'post_parent' => get_the_ID(),
                'post_type' => 'attachment',
                'post_mime_type' => 'image',
                'numberposts' => 1,
                'orderby' => 'menu_order'
            ));
            
            if($attachments) { 
                $attachment = array_shift($attachments); 
                $result = set_post_thumbnail(get_the_ID(), $attachment->ID);
                if($result) {
                    $done++; 
                }
            }
        }
        wp_reset_postdata();
        
        return "Featured images processate: $processed elementi analizzati, $done featured images assegnate.";
    }
    
    /**
     * Download CSV template
     */
    private function download_template($type) {
        $csv = '';
        $filename = '';
        
        switch($type) {
            case 'luoghi':
                $filename = 'yht_luoghi_template.csv';
                $csv = "title,descr,lat,lng,esperienze|pipe,aree|pipe,costo_ingresso,durata_min,family,pet,mobility,stagioni|pipe\n";
                $csv.= "Civita di Bagnoregio,Il borgo sospeso,42.627,12.092,cultura|passeggiata,collina|centro_storico,5,90,0,0,0,primavera|autunno\n";
                break;
                
            case 'alloggi':
                $filename = 'yht_alloggi_template.csv';
                $csv = "title,descr,lat,lng,fascia_prezzo,servizi|pipe,capienza\n";
                $csv.= "Bolsena – Hotel Lungolago,Hotel fronte lago,42.644,11.990,med,colazione|wi-fi|parcheggio|pet,40\n";
                break;
                
            case 'servizi':
                $filename = 'yht_servizi_template.csv';
                $csv = "title,descr,lat,lng,tipo_servizio,fascia_prezzo,orari,telefono,sito_web\n";
                $csv.= "Trattoria da Mario,Cucina tipica locale,42.420,12.104,ristorante,med,12:00-14:30|19:00-22:00,0761123456,https://trattoriadamario.it\n";
                $csv.= "Autonoleggio Centro,Noleggio auto e furgoni,42.425,12.110,noleggio_auto,varia,08:00-19:00,0761654321,https://autonoleggio.it\n";
                $csv.= "Taxi Service,Servizio taxi e autisti,42.415,12.095,autista,varia,24h,3331234567,\n";
                break;
                
            case 'tours':
                $filename = 'yht_tours_template.csv';
                $sample = json_encode([["day"=>1,"stops"=>[["luogo_title"=>"Viterbo – Quartiere San Pellegrino","time"=>"10:00"]]]], JSON_UNESCAPED_UNICODE);
                $csv = "title,descr,prezzo_base,giorni_json\n";
                $csv.= "Classico Tuscia 3 giorni,Itinerario esempio,120,\"".str_replace('"','""',$sample)."\"\n";
                break;
        }
        
        if($csv) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            echo $csv;
            exit;
        }
    }
    
    /**
     * Render importer form
     */
    private function render_form($message) {
        ?>
        <div class="wrap">
            <h1>YHT Importer</h1>
            <?php if($message): ?>
                <div class="updated"><p><?php echo esc_html($message); ?></p></div>
            <?php endif; ?>
            
            <h2 class="title">Importa CSV</h2>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('yht_import'); ?>
                <p>
                    <label><input type="radio" name="yht_type" value="luoghi" checked /> Luoghi</label>
                    &nbsp;&nbsp;
                    <label><input type="radio" name="yht_type" value="alloggi" /> Alloggi</label>
                    &nbsp;&nbsp;
                    <label><input type="radio" name="yht_type" value="servizi" /> Servizi</label>
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
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=yht_import&download_template=servizi')); ?>">Servizi CSV</a>
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
}