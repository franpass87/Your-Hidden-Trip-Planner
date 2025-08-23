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
        
        if (empty($_FILES['yht_csv']['tmp_name'])) {
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
        
        // Validate file size (max 5MB)
        if ($_FILES['yht_csv']['size'] > 5 * 1024 * 1024) {
            return 'File CSV troppo grande. Dimensione massima: 5MB.';
        }
        
        try {
            return $this->import_csv_data($file_path, $type);
        } catch (Exception $e) {
            error_log('YHT Import Error: ' . $e->getMessage());
            return 'Errore durante l\'importazione: ' . $e->getMessage();
        }
    }
    
    /**
     * Import CSV data
     * @param string $file_path Path to CSV file
     * @param string $type Type of data (luoghi, alloggi, servizi, tours)
     * @return string Result message
     */
    private function import_csv_data($file_path, $type) {
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            throw new Exception('Impossibile aprire il file CSV.');
        }
        
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new Exception('File CSV vuoto o headers mancanti.');
        }
        
        // Validate headers based on type
        $required_fields = $this->get_required_fields($type);
        $missing_fields = array_diff($required_fields, $headers);
        if (!empty($missing_fields)) {
            fclose($handle);
            throw new Exception('Campi obbligatori mancanti: ' . implode(', ', $missing_fields));
        }
        
        $imported = 0;
        $errors = array();
        $row_number = 1;
        
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            
            if (count($row) !== count($headers)) {
                $errors[] = "Riga $row_number: numero di colonne non corrispondente";
                continue;
            }
            
            $data = array_combine($headers, $row);
            
            try {
                if ($this->import_single_item($data, $type)) {
                    $imported++;
                }
            } catch (Exception $e) {
                $errors[] = "Riga $row_number: " . $e->getMessage();
            }
        }
        
        fclose($handle);
        
        $result = "Importazione completata. $imported elementi importati.";
        if (!empty($errors)) {
            $result .= " Errori: " . implode(', ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $result .= " (e " . (count($errors) - 5) . " altri errori)";
            }
        }
        
        return $result;
    }
    
    /**
     * Get required fields for each type
     * @param string $type Data type
     * @return array Required field names
     */
    private function get_required_fields($type) {
        $fields = array(
            'luoghi' => array('title', 'descr'),
            'alloggi' => array('title', 'descr', 'tipologia'),
            'servizi' => array('title', 'descr', 'categoria'),
            'tours' => array('title', 'descr', 'durata')
        );
        
        return $fields[$type] ?? array('title', 'descr');
    }
    
    /**
     * Import single item from CSV row
     * @param array $data Row data
     * @param string $type Data type
     * @return bool Success status
     */
    private function import_single_item($data, $type) {
        // Validate required fields
        $title = YHT_Validators::text($data['title'] ?? '', 200);
        if (!$title) {
            throw new Exception('Titolo obbligatorio');
        }
        
        $description = wp_kses_post($data['descr'] ?? '');
        if (empty($description)) {
            throw new Exception('Descrizione obbligatoria');
        }
        
        // Prepare post data
        $post_data = array(
            'post_title' => $title,
            'post_content' => $description,
            'post_status' => 'publish',
            'post_type' => $this->get_post_type($type),
            'post_author' => get_current_user_id()
        );
        
        // Check if post already exists (by title)
        $existing_post = get_page_by_title($title, OBJECT, $post_data['post_type']);
        
        if ($existing_post) {
            $post_data['ID'] = $existing_post->ID;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }
        
        if (is_wp_error($post_id)) {
            throw new Exception('Errore creazione post: ' . $post_id->get_error_message());
        }
        
        // Handle meta fields
        $this->import_meta_fields($post_id, $data, $type);
        
        // Handle taxonomies
        $this->import_taxonomies($post_id, $data, $type);
        
        return true;
    }
    
    /**
     * Get WordPress post type for import type
     * @param string $type Import type
     * @return string Post type
     */
    private function get_post_type($type) {
        $post_types = array(
            'luoghi' => 'yht_luogo',
            'alloggi' => 'yht_alloggio',
            'servizi' => 'yht_servizio',
            'tours' => 'yht_tour'
        );
        
        return $post_types[$type] ?? 'yht_luogo';
    }
    
    /**
     * Import meta fields for post
     * @param int $post_id Post ID
     * @param array $data Row data
     * @param string $type Data type
     */
    private function import_meta_fields($post_id, $data, $type) {
        // Handle coordinates
        if (isset($data['lat']) && isset($data['lng'])) {
            $lat = floatval($data['lat']);
            $lng = floatval($data['lng']);
            
            if (YHT_Validators::coordinates($lat, $lng)) {
                update_post_meta($post_id, 'yht_lat', $lat);
                update_post_meta($post_id, 'yht_lng', $lng);
            }
        }
        
        // Handle pricing fields
        if (isset($data['costo_ingresso'])) {
            $cost = floatval($data['costo_ingresso']);
            if ($cost >= 0) {
                update_post_meta($post_id, 'yht_costo_ingresso', $cost);
            }
        }
        
        // Handle duration
        if (isset($data['durata_min'])) {
            $duration = intval($data['durata_min']);
            if ($duration > 0) {
                update_post_meta($post_id, 'yht_durata_min', $duration);
            }
        }
        
        // Handle boolean fields
        $boolean_fields = array('family', 'pet', 'mobility');
        foreach ($boolean_fields as $field) {
            if (isset($data[$field])) {
                $value = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($value !== null) {
                    update_post_meta($post_id, "yht_$field", $value ? 1 : 0);
                }
            }
        }
        
        // Handle type-specific fields
        switch ($type) {
            case 'alloggi':
                if (isset($data['stelle'])) {
                    $stars = intval($data['stelle']);
                    if ($stars >= 1 && $stars <= 5) {
                        update_post_meta($post_id, 'yht_stelle', $stars);
                    }
                }
                break;
                
            case 'servizi':
                if (isset($data['prezzo_base'])) {
                    $price = floatval($data['prezzo_base']);
                    if ($price >= 0) {
                        update_post_meta($post_id, 'yht_prezzo_base', $price);
                    }
                }
                break;
        }
    }
    
    /**
     * Import taxonomies for post
     * @param int $post_id Post ID
     * @param array $data Row data
     * @param string $type Data type
     */
    private function import_taxonomies($post_id, $data, $type) {
        // Handle pipe-separated taxonomies
        $taxonomy_fields = array(
            'esperienze' => 'yht_esperienza',
            'aree' => 'yht_area',
            'stagioni' => 'yht_stagione'
        );
        
        foreach ($taxonomy_fields as $field => $taxonomy) {
            if (isset($data[$field])) {
                $terms = array_filter(array_map('trim', explode('|', $data[$field])));
                if (!empty($terms)) {
                    // Create terms if they don't exist and assign to post
                    $term_ids = array();
                    foreach ($terms as $term_name) {
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
                        wp_set_post_terms($post_id, $term_ids, $taxonomy);
                    }
                }
            }
        }
    }
    
    /**
     * Set featured images from attachments
     */
    private function set_featured_images() {
        check_admin_referer('yht_import');
        
        $query = new WP_Query(array(
            'post_type' => array('yht_luogo','yht_alloggio'),
            'posts_per_page' => -1,
            'no_found_rows' => true
        ));
        
        $done = 0;
        while($query->have_posts()) { 
            $query->the_post();
            
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
                set_post_thumbnail(get_the_ID(), $attachment->ID); 
                $done++; 
            }
        }
        wp_reset_postdata();
        
        return "Featured images assegnate: $done";
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