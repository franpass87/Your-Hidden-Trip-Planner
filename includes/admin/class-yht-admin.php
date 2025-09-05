<?php
/**
 * Handle Admin functionality
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_yht_bulk_confirm_bookings', array($this, 'ajax_bulk_confirm_bookings'));
        add_action('admin_init', array($this, 'handle_booking_export'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            'Your Hidden Trip', 
            'Your Hidden Trip', 
            'manage_options', 
            'yht_admin', 
            array($this, 'dashboard_page'), 
            'dashicons-admin-site', 
            58
        );
        
        add_submenu_page(
            'yht_admin',
            'Dashboard',
            '🏛️ Dashboard',
            'manage_options',
            'yht_admin',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'yht_admin',
            'Gestione Prenotazioni',
            '📋 Prenotazioni',
            'manage_woocommerce',
            'yht_bookings',
            array($this, 'bookings_page')
        );
        
        add_submenu_page(
            'yht_admin',
            'Gestione Clienti',
            '👥 Clienti',
            'manage_options',
            'yht_customers',
            array($this, 'customers_page')
        );
        
        add_submenu_page(
            'yht_admin',
            'Analytics Dashboard',
            '📊 Analytics',
            'manage_options',
            'yht_analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'yht_admin',
            'Sistema & Performance',
            '⚡ Sistema',
            'manage_options',
            'yht_system_health',
            array($this, 'system_health_page')
        );
        
        add_submenu_page(
            'yht_admin',
            'Impostazioni',
            '⚙️ Impostazioni',
            'manage_options',
            'yht_settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'yht_admin',
            'Importer CSV',
            '📥 Importer CSV',
            'manage_options',
            'yht_import',
            array($this, 'importer_page')
        );
        
        // Add custom post type menu items for easy access
        add_submenu_page(
            'yht_admin',
            'Gestione Luoghi',
            '📍 Luoghi',
            'edit_posts',
            'edit.php?post_type=yht_luogo'
        );
        
        add_submenu_page(
            'yht_admin',
            'Gestione Tour',
            '🗺️ Tour',
            'edit_posts',
            'edit.php?post_type=yht_tour'
        );
        
        add_submenu_page(
            'yht_admin',
            'Gestione Alloggi',
            '🏨 Alloggi',
            'edit_posts',
            'edit.php?post_type=yht_alloggio'
        );
        
        add_submenu_page(
            'yht_admin',
            'Gestione Servizi',
            '🍽️ Servizi',
            'edit_posts',
            'edit.php?post_type=yht_servizio'
        );
    }
    
    /**
     * Dashboard page callback
     */
    public function dashboard_page() {
        $dashboard = new YHT_Dashboard();
        $dashboard->render_dashboard();
    }
    
    /**
     * Customers management page
     */
    public function customers_page() {
        $customer_manager = new YHT_Customer_Manager();
        $customer_manager->render_page();
    }
    
    /**
     * System health page
     */
    public function system_health_page() {
        $system_health = new YHT_System_Health();
        $system_health->render_page();
    }
    
    /**
     * Settings page callback
     */
    public function settings_page() {
        $settings_handler = new YHT_Settings();
        $settings_handler->render_page();
    }
    
    /**
     * Importer page callback
     */
    public function importer_page() {
        $importer = new YHT_Importer();
        $importer->render_page();
    }
    
    /**
     * Bookings management page
     */
    public function bookings_page() {
        ?>
        <div class="wrap">
            <h1>📋 Gestione Prenotazioni</h1>
            
            <!-- Filters and Search -->
            <div class="yht-bookings-header">
                <div class="yht-search-filters">
                    <input type="text" id="booking-search" placeholder="Cerca per riferimento, cliente o email..." />
                    <select id="status-filter">
                        <option value="">Tutti gli stati</option>
                        <option value="pending_payment">In attesa pagamento</option>
                        <option value="confirmed">Confermato</option>
                        <option value="cancelled">Cancellato</option>
                        <option value="completed">Completato</option>
                    </select>
                    <select id="date-filter">
                        <option value="">Tutti i periodi</option>
                        <option value="today">Oggi</option>
                        <option value="this_week">Questa settimana</option>
                        <option value="this_month">Questo mese</option>
                    </select>
                    <button type="button" id="search-bookings" class="button">🔍 Cerca</button>
                </div>
                
                <div class="yht-booking-actions">
                    <button type="button" id="bulk-confirm" class="button">✅ Conferma Selezionate</button>
                    <button type="button" id="export-bookings" class="button">📥 Esporta CSV</button>
                </div>
            </div>
        <?php
        
        // Get all bookings
        $bookings = get_posts(array(
            'post_type' => 'yht_booking',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (empty($bookings)) {
            echo '<div class="yht-no-bookings">';
            echo '<div class="yht-empty-state">';
            echo '<div class="empty-icon">📋</div>';
            echo '<h3>Nessuna prenotazione trovata</h3>';
            echo '<p>Le prenotazioni effettuate dai clienti appariranno qui.</p>';
            echo '</div>';
            echo '</div>';
        } else {
            echo '<form id="bookings-form">';
            echo '<table class="wp-list-table widefat fixed striped yht-bookings-table">';
            echo '<thead><tr>';
            echo '<td class="check-column"><input type="checkbox" id="select-all-bookings" /></td>';
            echo '<th class="column-reference">Riferimento</th>';
            echo '<th class="column-customer">Cliente</th>';
            echo '<th class="column-tour">Tour</th>';
            echo '<th class="column-travel-date">Data Viaggio</th>';
            echo '<th class="column-pax">Pax</th>';
            echo '<th class="column-total">Totale</th>';
            echo '<th class="column-status">Stato</th>';
            echo '<th class="column-booking-date">Prenotato il</th>';
            echo '<th class="column-actions">Azioni</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            foreach ($bookings as $booking) {
                $ref = get_post_meta($booking->ID, 'yht_booking_reference', true);
                $customer_name = get_post_meta($booking->ID, 'yht_customer_name', true);
                $customer_email = get_post_meta($booking->ID, 'yht_customer_email', true);
                $customer_phone = get_post_meta($booking->ID, 'yht_customer_phone', true);
                $travel_date = get_post_meta($booking->ID, 'yht_travel_date', true);
                $num_pax = get_post_meta($booking->ID, 'yht_num_pax', true);
                $total_price = get_post_meta($booking->ID, 'yht_total_price', true);
                $status = get_post_meta($booking->ID, 'yht_booking_status', true) ?: 'pending_payment';
                $package_type = get_post_meta($booking->ID, 'yht_package_type', true);
                
                $itinerary = json_decode(get_post_meta($booking->ID, 'yht_itinerary_json', true), true);
                $tour_name = $itinerary['name'] ?? 'Tour personalizzato';
                
                $status_labels = array(
                    'pending_payment' => '🔄 In attesa pagamento',
                    'confirmed' => '✅ Confermata',
                    'cancelled' => '❌ Cancellata',
                    'completed' => '🎉 Completata'
                );
                
                $status_colors = array(
                    'pending_payment' => '#f59e0b',
                    'confirmed' => '#10b981',
                    'cancelled' => '#ef4444',
                    'completed' => '#6366f1'
                );
                
                echo '<tr data-booking-id="' . $booking->ID . '">';
                echo '<th class="check-column"><input type="checkbox" name="booking_ids[]" value="' . $booking->ID . '" /></th>';
                echo '<td class="column-reference"><strong>' . esc_html($ref) . '</strong></td>';
                echo '<td class="column-customer">';
                echo '<div class="customer-info">';
                echo '<strong>' . esc_html($customer_name ?: 'Cliente') . '</strong><br>';
                echo '<small>' . esc_html($customer_email) . '</small>';
                if ($customer_phone) {
                    echo '<br><small>📞 ' . esc_html($customer_phone) . '</small>';
                }
                echo '</div>';
                echo '</td>';
                echo '<td class="column-tour"><div class="tour-info">' . esc_html($tour_name) . '<br><small>' . esc_html(ucfirst($package_type)) . '</small></div></td>';
                echo '<td class="column-travel-date">' . esc_html($travel_date) . '</td>';
                echo '<td class="column-pax">' . esc_html($num_pax) . '</td>';
                echo '<td class="column-total"><strong>€' . esc_html($total_price) . '</strong></td>';
                echo '<td class="column-status">';
                echo '<span class="booking-status status-' . esc_attr($status) . '" style="background-color: ' . $status_colors[$status] . '; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;">';
                echo $status_labels[$status];
                echo '</span>';
                echo '</td>';
                echo '<td class="column-booking-date">' . get_the_date('d/m/Y H:i', $booking->ID) . '</td>';
                echo '<td class="column-actions">';
                echo '<div class="row-actions">';
                echo '<button type="button" class="button button-small view-booking-details" data-booking-id="' . $booking->ID . '">👁️ Dettagli</button>';
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
            echo '</form>';
        }
        ?>
        </div>
        
        <style>
        .yht-bookings-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .yht-search-filters {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .yht-search-filters input[type="text"] {
            width: 300px;
        }
        
        .yht-booking-actions {
            display: flex;
            gap: 10px;
        }
        
        .yht-bookings-table {
            margin-top: 0;
        }
        
        .column-reference {
            width: 12%;
        }
        
        .column-customer {
            width: 18%;
        }
        
        .column-tour {
            width: 15%;
        }
        
        .column-travel-date {
            width: 10%;
        }
        
        .column-pax {
            width: 8%;
        }
        
        .column-total {
            width: 10%;
        }
        
        .column-status {
            width: 12%;
        }
        
        .column-booking-date {
            width: 10%;
        }
        
        .column-actions {
            width: 5%;
        }
        
        .customer-info {
            font-size: 13px;
            line-height: 1.4;
        }
        
        .tour-info {
            font-size: 13px;
            line-height: 1.4;
        }
        
        .yht-no-bookings {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 60px 20px;
        }
        
        .yht-empty-state {
            text-align: center;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .empty-icon {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .yht-empty-state h3 {
            margin-bottom: 10px;
            color: #1d2327;
        }
        
        .yht-empty-state p {
            color: #646970;
        }
        
        @media (max-width: 782px) {
            .yht-bookings-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .yht-search-filters {
                flex-wrap: wrap;
            }
            
            .yht-search-filters input[type="text"] {
                width: 100%;
                margin-bottom: 10px;
            }
        }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Select all functionality
            $('#select-all-bookings').on('change', function() {
                $('input[name="booking_ids[]"]').prop('checked', $(this).prop('checked'));
            });
            
            // Bulk actions
            $('#bulk-confirm').on('click', function() {
                const selectedIds = $('input[name="booking_ids[]"]:checked').map(function() {
                    return $(this).val();
                }).get();
                
                if (selectedIds.length === 0) {
                    alert('Seleziona almeno una prenotazione');
                    return;
                }
                
                if (confirm('Confermare ' + selectedIds.length + ' prenotazioni selezionate?')) {
                    var button = $(this);
                    button.prop('disabled', true).text('Aggiornamento in corso...');
                    
                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'yht_bulk_confirm_bookings',
                            booking_ids: selectedIds,
                            _wpnonce: '<?php echo wp_create_nonce("yht_bulk_bookings"); ?>'
                        },
                        success: function(response) {
                            if(response.success) {
                                alert('✅ ' + response.data.message);
                                location.reload();
                            } else {
                                alert('❌ ' + response.data.message);
                                button.prop('disabled', false).text('✅ Conferma Selezionate');
                            }
                        },
                        error: function() {
                            alert('❌ Errore di connessione');
                            button.prop('disabled', false).text('✅ Conferma Selezionate');
                        }
                    });
                }
            });
            
            // Export functionality
            $('#export-bookings').on('click', function() {
                window.location.href = '<?php echo admin_url("admin.php?page=yht_bookings&action=export_csv&_wpnonce=" . wp_create_nonce("yht_export_bookings")); ?>';
            });
        });
        </script>
        <?php
    }
    
    /**
     * Analytics dashboard page
     */
    public function analytics_page() {
        // Check if analytics is enabled
        $settings = get_option('yht_settings', array());
        if (!isset($settings['analytics_enabled']) || !$settings['analytics_enabled']) {
            echo '<div class="wrap">';
            echo '<h1>Analytics Dashboard</h1>';
            echo '<div class="notice notice-warning">';
            echo '<p><strong>Analytics is disabled.</strong> Please enable analytics in the <a href="' . admin_url('admin.php?page=yht_admin') . '">plugin settings</a> to view the dashboard.</p>';
            echo '</div>';
            echo '</div>';
            return;
        }
        
        include YHT_PLUGIN_PATH . 'includes/admin/views/analytics-dashboard.php';
    }
    
    /**
     * Handle booking CSV export
     */
    public function handle_booking_export() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'yht_bookings') {
            return;
        }
        
        if (!isset($_GET['action']) || $_GET['action'] !== 'export_csv') {
            return;
        }
        
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'yht_export_bookings')) {
            wp_die('Accesso negato.');
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Permessi insufficienti.');
        }
        
        $this->export_bookings_csv();
    }
    
    /**
     * Export bookings to CSV
     */
    private function export_bookings_csv() {
        $bookings = get_posts(array(
            'post_type' => 'yht_booking',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $filename = 'yht_bookings_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fputs($output, "\xEF\xBB\xBF");
        
        // CSV Headers
        $headers = array(
            'Riferimento',
            'Data Prenotazione',
            'Cliente Nome',
            'Cliente Email',
            'Cliente Telefono',
            'Tour',
            'Pacchetto',
            'Data Viaggio',
            'Numero Viaggiatori',
            'Prezzo Totale',
            'Stato',
            'Richieste Speciali'
        );
        
        fputcsv($output, $headers);
        
        // Export data
        foreach ($bookings as $booking) {
            $ref = get_post_meta($booking->ID, 'yht_booking_reference', true);
            $customer_name = get_post_meta($booking->ID, 'yht_customer_name', true);
            $customer_email = get_post_meta($booking->ID, 'yht_customer_email', true);
            $customer_phone = get_post_meta($booking->ID, 'yht_customer_phone', true);
            $travel_date = get_post_meta($booking->ID, 'yht_travel_date', true);
            $num_pax = get_post_meta($booking->ID, 'yht_num_pax', true);
            $total_price = get_post_meta($booking->ID, 'yht_total_price', true);
            $status = get_post_meta($booking->ID, 'yht_booking_status', true) ?: 'pending_payment';
            $package_type = get_post_meta($booking->ID, 'yht_package_type', true);
            $special_requests = get_post_meta($booking->ID, 'yht_special_requests', true);
            
            $itinerary = json_decode(get_post_meta($booking->ID, 'yht_itinerary_json', true), true);
            $tour_name = $itinerary['name'] ?? 'Tour personalizzato';
            
            $status_labels = array(
                'pending_payment' => 'In attesa pagamento',
                'confirmed' => 'Confermata',
                'cancelled' => 'Cancellata',
                'completed' => 'Completata'
            );
            
            $row = array(
                $ref,
                get_the_date('d/m/Y H:i', $booking->ID),
                $customer_name,
                $customer_email,
                $customer_phone,
                $tour_name,
                ucfirst($package_type),
                $travel_date,
                $num_pax,
                '€' . $total_price,
                $status_labels[$status] ?? $status,
                $special_requests
            );
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * AJAX handler for bulk booking confirmation
     */
    public function ajax_bulk_confirm_bookings() {
        check_ajax_referer('yht_bulk_bookings');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Permessi insufficienti.'));
        }
        
        $booking_ids = $_POST['booking_ids'] ?? array();
        if (empty($booking_ids) || !is_array($booking_ids)) {
            wp_send_json_error(array('message' => 'Nessuna prenotazione selezionata.'));
        }
        
        $updated = 0;
        $errors = array();
        
        foreach ($booking_ids as $booking_id) {
            $booking_id = intval($booking_id);
            
            // Verify this is a booking post
            $post = get_post($booking_id);
            if (!$post || $post->post_type !== 'yht_booking') {
                $errors[] = "ID $booking_id non è una prenotazione valida.";
                continue;
            }
            
            // Update status to confirmed
            $result = update_post_meta($booking_id, 'yht_booking_status', 'confirmed');
            if ($result !== false) {
                $updated++;
                
                // Optional: Send confirmation email to customer
                $this->maybe_send_confirmation_email($booking_id);
            } else {
                $errors[] = "Errore nell'aggiornamento della prenotazione $booking_id.";
            }
        }
        
        if ($updated > 0) {
            $message = "$updated prenotazioni confermate con successo.";
            if (!empty($errors)) {
                $message .= " Errori: " . implode(', ', array_slice($errors, 0, 3));
            }
            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(array('message' => 'Nessuna prenotazione aggiornata. Errori: ' . implode(', ', $errors)));
        }
    }
    
    /**
     * Send confirmation email (implemented)
     */
    private function maybe_send_confirmation_email($booking_id) {
        $customer_email = get_post_meta($booking_id, 'yht_customer_email', true);
        $customer_name = get_post_meta($booking_id, 'yht_customer_name', true);
        
        if (!$customer_email) {
            return false;
        }
        
        // Get booking details
        $booking_ref = get_post_meta($booking_id, 'yht_booking_reference', true);
        $travel_date = get_post_meta($booking_id, 'yht_travel_date', true);
        $num_pax = get_post_meta($booking_id, 'yht_num_pax', true);
        $total_price = get_post_meta($booking_id, 'yht_total_price', true);
        $package_type = get_post_meta($booking_id, 'yht_package_type', true);
        
        $itinerary = json_decode(get_post_meta($booking_id, 'yht_itinerary_json', true), true);
        $tour_name = $itinerary['name'] ?? 'Tour personalizzato';
        
        // Email subject
        $subject = sprintf('Prenotazione confermata - %s (Rif. %s)', $tour_name, $booking_ref);
        
        // Email content
        $message = $this->build_confirmation_email_content($customer_name, $booking_ref, $tour_name, $package_type, $travel_date, $num_pax, $total_price);
        
        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        // Send email
        $sent = wp_mail($customer_email, $subject, $message, $headers);
        
        if ($sent) {
            // Log successful email
            update_post_meta($booking_id, 'yht_confirmation_email_sent', current_time('timestamp'));
            error_log("YHT: Confirmation email sent successfully to $customer_email for booking $booking_id");
        } else {
            error_log("YHT: Failed to send confirmation email to $customer_email for booking $booking_id");
        }
        
        return $sent;
    }
    
    /**
     * Build confirmation email content
     */
    private function build_confirmation_email_content($customer_name, $booking_ref, $tour_name, $package_type, $travel_date, $num_pax, $total_price) {
        $site_name = get_bloginfo('name');
        $site_url = get_home_url();
        
        $html = '<html><head><meta charset="UTF-8"></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">';
        
        $html .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">';
        
        // Header
        $html .= '<div style="text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; margin: -20px -20px 20px -20px;">';
        $html .= '<h1 style="margin: 0; font-size: 24px;">✅ Prenotazione Confermata</h1>';
        $html .= '<p style="margin: 10px 0 0; opacity: 0.9;">' . esc_html($site_name) . '</p>';
        $html .= '</div>';
        
        // Greeting
        $html .= '<p>Gentile <strong>' . esc_html($customer_name ?: 'Cliente') . '</strong>,</p>';
        $html .= '<p>Siamo lieti di confermare la sua prenotazione per il tour <strong>' . esc_html($tour_name) . '</strong>.</p>';
        
        // Booking details
        $html .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 20px 0;">';
        $html .= '<h3 style="margin: 0 0 15px; color: #2d3436;">📋 Dettagli della Prenotazione</h3>';
        $html .= '<table style="width: 100%; border-collapse: collapse;">';
        $html .= '<tr><td style="padding: 5px 0; font-weight: bold;">Riferimento:</td><td style="padding: 5px 0;">' . esc_html($booking_ref) . '</td></tr>';
        $html .= '<tr><td style="padding: 5px 0; font-weight: bold;">Tour:</td><td style="padding: 5px 0;">' . esc_html($tour_name) . '</td></tr>';
        $html .= '<tr><td style="padding: 5px 0; font-weight: bold;">Pacchetto:</td><td style="padding: 5px 0;">' . esc_html(ucfirst($package_type)) . '</td></tr>';
        $html .= '<tr><td style="padding: 5px 0; font-weight: bold;">Data partenza:</td><td style="padding: 5px 0;">' . esc_html($travel_date) . '</td></tr>';
        $html .= '<tr><td style="padding: 5px 0; font-weight: bold;">Numero viaggiatori:</td><td style="padding: 5px 0;">' . esc_html($num_pax) . '</td></tr>';
        $html .= '<tr><td style="padding: 5px 0; font-weight: bold;">Prezzo totale:</td><td style="padding: 5px 0; color: #00b894; font-weight: bold;">€' . esc_html($total_price) . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        
        // Next steps
        $html .= '<div style="background: #e7f3ff; padding: 15px; border-radius: 6px; margin: 20px 0;">';
        $html .= '<h3 style="margin: 0 0 10px; color: #0984e3;">🎯 Prossimi Passi</h3>';
        $html .= '<ul style="margin: 0; padding-left: 20px;">';
        $html .= '<li>Riceverà a breve l\'itinerario dettagliato del tour</li>';
        $html .= '<li>Il nostro team la contatterà per finalizzare i dettagli</li>';
        $html .= '<li>Conservi questo riferimento per qualsiasi comunicazione: <strong>' . esc_html($booking_ref) . '</strong></li>';
        $html .= '</ul>';
        $html .= '</div>';
        
        // Contact info
        $html .= '<div style="border-top: 1px solid #ddd; padding-top: 15px; margin-top: 20px; text-align: center; color: #666;">';
        $html .= '<p>Per qualsiasi domanda, non esiti a contattarci:</p>';
        $html .= '<p><strong>' . esc_html($site_name) . '</strong><br>';
        $html .= 'Email: <a href="mailto:' . get_option('admin_email') . '">' . get_option('admin_email') . '</a><br>';
        $html .= 'Web: <a href="' . esc_url($site_url) . '">' . esc_url($site_url) . '</a></p>';
        $html .= '</div>';
        
        $html .= '</div></body></html>';
        
        return $html;
    }
}