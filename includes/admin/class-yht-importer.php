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
        
        // TODO: Implement full CSV import functionality
        // This would include the original import logic for luoghi, alloggi, tours
        
        return 'Import funzionalità non ancora implementata nella versione refactored.';
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
}