<?php
/**
 * QR Code Management System for Tours
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_QR_Manager {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_qr_meta_box'));
        add_action('wp_ajax_yht_generate_qr_code', array($this, 'generate_qr_code_ajax'));
        add_action('wp_ajax_yht_regenerate_token', array($this, 'regenerate_token_ajax'));
        add_shortcode('yht_tour_qr', array($this, 'tour_qr_shortcode'));
    }
    
    /**
     * Add QR code meta box to tour edit page
     */
    public function add_qr_meta_box() {
        add_meta_box(
            'yht-qr-code-box',
            '🔗 QR Code & Link Cliente',
            array($this, 'render_qr_meta_box'),
            'yht_tour',
            'side',
            'high'
        );
    }
    
    /**
     * Render QR code meta box
     */
    public function render_qr_meta_box($post) {
        $client_token = get_post_meta($post->ID, 'yht_client_token', true);
        $qr_generated = get_post_meta($post->ID, 'yht_qr_generated', true);
        $portal_stats = get_post_meta($post->ID, 'yht_portal_stats', true) ?: array();
        
        wp_nonce_field('yht_qr_nonce', 'yht_qr_nonce');
        ?>
        <style>
            .yht-qr-container {
                text-align: center;
                padding: 15px;
            }
            .yht-qr-code {
                margin: 15px 0;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 10px;
                background: #f9f9f9;
            }
            .yht-qr-code img {
                max-width: 100%;
                height: auto;
            }
            .yht-portal-url {
                background: #f0f0f1;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 8px;
                font-family: monospace;
                font-size: 12px;
                word-break: break-all;
                margin: 10px 0;
            }
            .yht-stats-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                margin: 15px 0;
                font-size: 12px;
            }
            .yht-stat-item {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 8px;
                text-align: center;
            }
            .yht-stat-number {
                font-size: 18px;
                font-weight: bold;
                color: #667eea;
                display: block;
            }
            .yht-stat-label {
                color: #666;
                font-size: 10px;
                text-transform: uppercase;
            }
            .yht-action-buttons {
                display: flex;
                flex-direction: column;
                gap: 8px;
                margin-top: 15px;
            }
            .yht-action-buttons .button {
                font-size: 12px;
                padding: 6px 12px;
            }
        </style>
        
        <div class="yht-qr-container">
            <?php if ($client_token): ?>
                <?php
                $portal_url = home_url('/client-portal/' . $client_token);
                $qr_code_url = $this->get_qr_code_url($portal_url);
                ?>
                
                <div class="yht-qr-code">
                    <img src="<?php echo esc_url($qr_code_url); ?>" alt="QR Code Tour">
                    <p><strong>QR Code Portale Cliente</strong></p>
                </div>
                
                <div class="yht-portal-url">
                    <strong>Link Diretto:</strong><br>
                    <span id="portal-url"><?php echo esc_url($portal_url); ?></span>
                </div>
                
                <?php if (!empty($portal_stats)): ?>
                <div class="yht-stats-grid">
                    <div class="yht-stat-item">
                        <span class="yht-stat-number"><?php echo $portal_stats['visits'] ?? 0; ?></span>
                        <span class="yht-stat-label">Visite</span>
                    </div>
                    <div class="yht-stat-item">
                        <span class="yht-stat-number"><?php echo $portal_stats['selections'] ?? 0; ?></span>
                        <span class="yht-stat-label">Selezioni</span>
                    </div>
                    <div class="yht-stat-item">
                        <span class="yht-stat-number"><?php echo $portal_stats['bookings'] ?? 0; ?></span>
                        <span class="yht-stat-label">Prenotazioni</span>
                    </div>
                    <div class="yht-stat-item">
                        <span class="yht-stat-number"><?php echo round(($portal_stats['conversion_rate'] ?? 0), 1); ?>%</span>
                        <span class="yht-stat-label">Conversione</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="yht-action-buttons">
                    <button type="button" class="button" onclick="yhtCopyPortalUrl()">
                        📋 Copia Link
                    </button>
                    <button type="button" class="button" onclick="yhtDownloadQR()">
                        ⬇️ Scarica QR Code
                    </button>
                    <button type="button" class="button" onclick="yhtSharePortal()">
                        📤 Condividi
                    </button>
                    <button type="button" class="button button-secondary" onclick="yhtRegenerateToken(<?php echo $post->ID; ?>)">
                        🔄 Rigenera Token
                    </button>
                </div>
                
                <div class="yht-usage-info">
                    <p style="font-size: 11px; color: #666; margin-top: 15px;">
                        <strong>💡 Come usare:</strong><br>
                        • Condividi il QR code o link con i clienti<br>
                        • I clienti potranno selezionare le loro preferenze<br>
                        • Riceverai notifiche delle selezioni<br>
                        • Potrai confermare la disponibilità e finalizzare
                    </p>
                </div>
                
            <?php else: ?>
                <div class="yht-no-qr">
                    <p>🔗 <strong>Genera QR Code</strong></p>
                    <p style="font-size: 12px; color: #666; margin-bottom: 15px;">
                        Crea un QR code unico per permettere ai clienti di accedere al portale di selezione per questo tour.
                    </p>
                    <button type="button" class="button button-primary" onclick="yhtGenerateQR(<?php echo $post->ID; ?>)">
                        ✨ Genera QR Code
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        function yhtGenerateQR(tourId) {
            const button = event.target;
            button.disabled = true;
            button.textContent = '⏳ Generazione...';
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'yht_generate_qr_code',
                    tour_id: tourId,
                    nonce: jQuery('#yht_qr_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Errore: ' + response.data.message);
                        button.disabled = false;
                        button.textContent = '✨ Genera QR Code';
                    }
                },
                error: function() {
                    alert('Errore di connessione');
                    button.disabled = false;
                    button.textContent = '✨ Genera QR Code';
                }
            });
        }
        
        function yhtRegenerateToken(tourId) {
            if (!confirm('Rigenerare il token? Il link attuale diventerà non valido.')) {
                return;
            }
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'yht_regenerate_token',
                    tour_id: tourId,
                    nonce: jQuery('#yht_qr_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Errore: ' + response.data.message);
                    }
                }
            });
        }
        
        function yhtCopyPortalUrl() {
            const url = document.getElementById('portal-url').textContent;
            navigator.clipboard.writeText(url).then(function() {
                alert('✅ Link copiato negli appunti!');
            });
        }
        
        function yhtDownloadQR() {
            const qrImg = document.querySelector('.yht-qr-code img');
            if (qrImg) {
                const link = document.createElement('a');
                link.download = 'tour-qr-code.png';
                link.href = qrImg.src;
                link.click();
            }
        }
        
        function yhtSharePortal() {
            const url = document.getElementById('portal-url').textContent;
            const title = 'Portale Selezione Tour - Your Hidden Trip';
            
            if (navigator.share) {
                navigator.share({
                    title: title,
                    text: 'Accedi al portale per selezionare le tue preferenze per il tour',
                    url: url
                });
            } else {
                // Fallback: copy to clipboard
                yhtCopyPortalUrl();
            }
        }
        </script>
        <?php
    }
    
    /**
     * Generate QR code via AJAX
     */
    public function generate_qr_code_ajax() {
        check_ajax_referer('yht_qr_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $tour_id = (int)$_POST['tour_id'];
        
        if (!$tour_id) {
            wp_send_json_error(array('message' => 'Tour ID richiesto'));
        }
        
        $tour_post = get_post($tour_id);
        if (!$tour_post || $tour_post->post_type !== 'yht_tour') {
            wp_send_json_error(array('message' => 'Tour non trovato'));
        }
        
        // Generate unique token
        $client_token = $this->generate_unique_token();
        
        // Save token and metadata
        update_post_meta($tour_id, 'yht_client_token', $client_token);
        update_post_meta($tour_id, 'yht_qr_generated', current_time('Y-m-d H:i:s'));
        update_post_meta($tour_id, 'yht_portal_stats', array(
            'visits' => 0,
            'selections' => 0,
            'bookings' => 0,
            'conversion_rate' => 0,
            'created_at' => current_time('Y-m-d H:i:s')
        ));
        
        $portal_url = home_url('/client-portal/' . $client_token);
        $qr_code_url = $this->get_qr_code_url($portal_url);
        
        wp_send_json_success(array(
            'message' => 'QR Code generato con successo',
            'token' => $client_token,
            'portal_url' => $portal_url,
            'qr_code_url' => $qr_code_url
        ));
    }
    
    /**
     * Regenerate token via AJAX
     */
    public function regenerate_token_ajax() {
        check_ajax_referer('yht_qr_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $tour_id = (int)$_POST['tour_id'];
        
        // Generate new token
        $new_token = $this->generate_unique_token();
        
        // Update token and reset stats
        update_post_meta($tour_id, 'yht_client_token', $new_token);
        update_post_meta($tour_id, 'yht_qr_generated', current_time('Y-m-d H:i:s'));
        update_post_meta($tour_id, 'yht_portal_stats', array(
            'visits' => 0,
            'selections' => 0,
            'bookings' => 0,
            'conversion_rate' => 0,
            'regenerated_at' => current_time('Y-m-d H:i:s')
        ));
        
        wp_send_json_success(array(
            'message' => 'Token rigenerato con successo',
            'new_token' => $new_token,
            'portal_url' => home_url('/client-portal/' . $new_token)
        ));
    }
    
    /**
     * Generate unique token
     */
    private function generate_unique_token($length = 32) {
        global $wpdb;
        
        do {
            $token = wp_generate_password($length, false);
            
            // Check if token already exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'yht_client_token' AND meta_value = %s",
                $token
            ));
        } while ($exists > 0);
        
        return $token;
    }
    
    /**
     * Get QR code URL
     */
    public function get_qr_code_url($url, $size = 300, $format = 'png') {
        // Multiple QR code service options
        $services = array(
            'google' => "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($url),
            'qrserver' => "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&format={$format}&data=" . urlencode($url),
            'quickchart' => "https://quickchart.io/qr?text=" . urlencode($url) . "&size={$size}"
        );
        
        // Use QR Server as primary (more reliable than Google Charts)
        return $services['qrserver'];
    }
    
    /**
     * Track portal statistics
     */
    public function track_portal_visit($tour_token, $event_type = 'visit', $data = array()) {
        global $wpdb;
        
        $tour_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'yht_client_token' AND meta_value = %s",
            $tour_token
        ));
        
        if (!$tour_id) return false;
        
        $stats = get_post_meta($tour_id, 'yht_portal_stats', true) ?: array();
        
        switch ($event_type) {
            case 'visit':
                $stats['visits'] = ($stats['visits'] ?? 0) + 1;
                break;
            case 'selection':
                $stats['selections'] = ($stats['selections'] ?? 0) + 1;
                break;
            case 'booking':
                $stats['bookings'] = ($stats['bookings'] ?? 0) + 1;
                break;
        }
        
        // Calculate conversion rate
        if (($stats['visits'] ?? 0) > 0) {
            $stats['conversion_rate'] = (($stats['bookings'] ?? 0) / $stats['visits']) * 100;
        }
        
        $stats['last_activity'] = current_time('Y-m-d H:i:s');
        
        update_post_meta($tour_id, 'yht_portal_stats', $stats);
        
        return true;
    }
    
    /**
     * Shortcode for displaying tour QR code
     */
    public function tour_qr_shortcode($atts) {
        $atts = shortcode_atts(array(
            'tour_id' => 0,
            'size' => 200,
            'show_url' => 'true',
            'title' => 'Scansiona per accedere al portale tour'
        ), $atts);
        
        $tour_id = (int)$atts['tour_id'];
        if (!$tour_id) {
            return '<p>Errore: Tour ID richiesto</p>';
        }
        
        $client_token = get_post_meta($tour_id, 'yht_client_token', true);
        if (!$client_token) {
            return '<p>QR Code non ancora generato per questo tour</p>';
        }
        
        $portal_url = home_url('/client-portal/' . $client_token);
        $qr_code_url = $this->get_qr_code_url($portal_url, $atts['size']);
        
        ob_start();
        ?>
        <div class="yht-tour-qr-display" style="text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin: 20px 0;">
            <h4 style="margin-top: 0;"><?php echo esc_html($atts['title']); ?></h4>
            <img src="<?php echo esc_url($qr_code_url); ?>" alt="QR Code Tour" style="max-width: 100%; height: auto; border: 1px solid #eee; padding: 10px; background: white;">
            
            <?php if ($atts['show_url'] === 'true'): ?>
            <p style="font-size: 12px; color: #666; margin-top: 15px;">
                <strong>Link diretto:</strong><br>
                <a href="<?php echo esc_url($portal_url); ?>" target="_blank" style="word-break: break-all; font-family: monospace;">
                    <?php echo esc_url($portal_url); ?>
                </a>
            </p>
            <?php endif; ?>
            
            <div style="margin-top: 15px; font-size: 12px; color: #888;">
                <p>📱 Scansiona con la fotocamera del tuo telefono<br>
                🔗 Oppure clicca sul link diretto sopra</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get QR code analytics for tour
     */
    public function get_qr_analytics($tour_id) {
        $stats = get_post_meta($tour_id, 'yht_portal_stats', true) ?: array();
        $client_token = get_post_meta($tour_id, 'yht_client_token', true);
        
        return array(
            'tour_id' => $tour_id,
            'token' => $client_token,
            'stats' => $stats,
            'portal_url' => $client_token ? home_url('/client-portal/' . $client_token) : null,
            'qr_generated' => get_post_meta($tour_id, 'yht_qr_generated', true),
            'total_interactions' => ($stats['visits'] ?? 0) + ($stats['selections'] ?? 0) + ($stats['bookings'] ?? 0)
        );
    }
    
    /**
     * Export QR code data for tour
     */
    public function export_qr_data($tour_id, $format = 'json') {
        $analytics = $this->get_qr_analytics($tour_id);
        $tour_post = get_post($tour_id);
        
        $export_data = array(
            'tour' => array(
                'id' => $tour_id,
                'title' => $tour_post ? $tour_post->post_title : '',
                'url' => get_permalink($tour_id)
            ),
            'qr_data' => $analytics,
            'exported_at' => current_time('c')
        );
        
        switch ($format) {
            case 'csv':
                return $this->array_to_csv($export_data);
            case 'xml':
                return $this->array_to_xml($export_data);
            default:
                return json_encode($export_data, JSON_PRETTY_PRINT);
        }
    }
    
    /**
     * Convert array to CSV format
     */
    private function array_to_csv($data) {
        $csv = "Field,Value\n";
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subkey => $subvalue) {
                    $csv .= "{$key}_{$subkey}," . (is_array($subvalue) ? json_encode($subvalue) : $subvalue) . "\n";
                }
            } else {
                $csv .= "{$key},{$value}\n";
            }
        }
        
        return $csv;
    }
    
    /**
     * Convert array to XML format
     */
    private function array_to_xml($data, $root = 'qr_data') {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<{$root}>\n";
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $xml .= "  <{$key}>\n";
                foreach ($value as $subkey => $subvalue) {
                    $xml .= "    <{$subkey}>" . htmlspecialchars(is_array($subvalue) ? json_encode($subvalue) : $subvalue) . "</{$subkey}>\n";
                }
                $xml .= "  </{$key}>\n";
            } else {
                $xml .= "  <{$key}>" . htmlspecialchars($value) . "</{$key}>\n";
            }
        }
        
        $xml .= "</{$root}>";
        return $xml;
    }
}

// Initialize QR manager
new YHT_QR_Manager();