<?php
/**
 * Handle PDF generation using dompdf
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_PDF_Generator {
    
    /**
     * Generate PDF from tour data
     */
    public function generate_pdf($params) {
        $state = $params['state'] ?? array();
        $tour = $params['tour'] ?? array();
        $map_png = $params['map_png'] ?? '';

        if(!$this->has_dompdf()) {
            return rest_ensure_response(array(
                'ok' => false, 
                'error' => 'dompdf_not_found', 
                'message' => 'Dompdf non trovato. Installa vendor/dompdf o Composer vendor.'
            ));
        }

        $html = $this->render_pdf_html($state, $tour, $map_png);
        $upload_dir = wp_upload_dir();
        $dir = trailingslashit($upload_dir['basedir']) . 'yht';
        
        if(!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
        
        $filename = $dir . '/yht_itinerario_' . time() . '.pdf';

        try {
            $dompdf = new \Dompdf\Dompdf(array(
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true
            ));
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            file_put_contents($filename, $dompdf->output());
        } catch(\Throwable $e) {
            return rest_ensure_response(array(
                'ok' => false, 
                'message' => 'Errore PDF: ' . $e->getMessage()
            ));
        }

        $url = trailingslashit($upload_dir['baseurl']) . 'yht/' . basename($filename);
        return rest_ensure_response(array('ok' => true, 'url' => $url));
    }
    
    /**
     * Check if dompdf is available
     */
    private function has_dompdf() {
        $vendor_autoload = YHT_PLUGIN_PATH . 'vendor/autoload.php';
        $dompdf_direct = YHT_PLUGIN_PATH . 'vendor/dompdf/autoload.inc.php';
        
        if(file_exists($vendor_autoload)) { 
            require_once $vendor_autoload; 
            return class_exists('\\Dompdf\\Dompdf'); 
        }
        
        if(file_exists($dompdf_direct)) { 
            require_once $dompdf_direct; 
            return class_exists('\\Dompdf\\Dompdf'); 
        }
        
        return false;
    }
    
    /**
     * Render HTML content for PDF
     */
    private function render_pdf_html($state, $tour, $map_png) {
        $css = $this->get_pdf_styles();
        $share_url = home_url(add_query_arg('yht', base64_encode(json_encode($state)), '/'));
        
        $header = "<h1>Your Hidden Trip – Itinerario</h1><div class='small'>Link: {$share_url}</div>";
        
        $selections = $this->render_selections($state);
        $program = $this->render_program($tour);
        $map = $this->render_map($map_png);
        
        return "
        <html>
            <head>
                <meta charset='utf-8'>
                {$css}
            </head>
            <body>
                {$header}
                <div class='box'>{$selections}</div>
                <h2>" . esc_html($tour['name'] ?? 'Itinerario') . "</h2>
                <div class='small'>Ingressi stimati: €" . esc_html($tour['totalEntryCost'] ?? 0) . "</div>
                <div class='hr'></div>
                <ul>{$program}</ul>
                <div class='hr'></div>
                {$map}
                <div class='hr'></div>
                <div class='small'>PDF generato automaticamente. YourHiddenTrip.</div>
            </body>
        </html>";
    }
    
    /**
     * Get PDF styles
     */
    private function get_pdf_styles() {
        return "
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
    }
    
    /**
     * Render user selections
     */
    private function render_selections($state) {
        return sprintf(
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
    }
    
    /**
     * Render tour program
     */
    private function render_program($tour) {
        $program = '';
        foreach(($tour['days'] ?? []) as $day) {
            $stops = array_map(function($stop){
                return esc_html(($stop['time'] ?? '') . ' ' . ($stop['title'] ?? ''));
            }, $day['stops'] ?? []);
            $program .= '<li><b>Giorno ' . $day['day'] . '</b>: ' . implode(' · ', $stops) . '</li>';
        }
        return $program;
    }
    
    /**
     * Render map section
     */
    private function render_map($map_png) {
        if(is_string($map_png) && strpos($map_png, 'data:image') === 0) {
            return '<h2>Mappa</h2><img class="map" src="' . $map_png . '" alt="Mappa itinerario"/>';
        } else {
            return '<div class="small">Mappa non inclusa (snapshot non disponibile).</div>';
        }
    }
}