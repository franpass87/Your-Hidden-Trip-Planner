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
            'manage_options',
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
    }
    
    /**
     * Dashboard page callback
     */
    public function dashboard_page() {
        require_once YHT_PLUGIN_PATH . 'includes/admin/class-yht-dashboard.php';
        $dashboard = new YHT_Dashboard();
        $dashboard->render_dashboard();
    }
    
    /**
     * Customers management page
     */
    public function customers_page() {
        require_once YHT_PLUGIN_PATH . 'includes/admin/class-yht-customer-manager.php';
        $customer_manager = new YHT_Customer_Manager();
        $customer_manager->render_page();
    }
    
    /**
     * System health page
     */
    public function system_health_page() {
        require_once YHT_PLUGIN_PATH . 'includes/admin/class-yht-system-health.php';
        $system_health = new YHT_System_Health();
        $system_health->render_page();
    }
    
    /**
     * Settings page callback
     */
    public function settings_page() {
        require_once YHT_PLUGIN_PATH . 'includes/admin/class-yht-settings.php';
        $settings_handler = new YHT_Settings();
        $settings_handler->render_page();
    }
    
    /**
     * Importer page callback
     */
    public function importer_page() {
        require_once YHT_PLUGIN_PATH . 'includes/admin/class-yht-importer.php';
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
                    // Implementation for bulk status update would go here
                    alert('Funzionalità in sviluppo: aggiornamento bulk status');
                }
            });
            
            // Export functionality
            $('#export-bookings').on('click', function() {
                alert('Funzionalità in sviluppo: esportazione CSV');
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
}