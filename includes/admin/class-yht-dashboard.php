<?php
/**
 * YHT Dashboard - Centralized backend management interface
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_Dashboard {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_yht_dashboard_stats', array($this, 'get_dashboard_stats'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Render main dashboard page
     */
    public function render_dashboard() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1>🏛️ Your Hidden Trip - Dashboard</h1>
            
            <div id="yht-dashboard-container">
                <!-- System Overview Cards -->
                <div class="yht-dashboard-row">
                    <div class="yht-dashboard-card yht-card-bookings">
                        <h3>📋 Prenotazioni</h3>
                        <div class="yht-stat-number" id="total-bookings">--</div>
                        <div class="yht-stat-label">Totali</div>
                        <div class="yht-stat-detail">
                            <span id="pending-bookings">--</span> in attesa
                        </div>
                    </div>
                    
                    <div class="yht-dashboard-card yht-card-customers">
                        <h3>👥 Clienti</h3>
                        <div class="yht-stat-number" id="total-customers">--</div>
                        <div class="yht-stat-label">Registrati</div>
                        <div class="yht-stat-detail">
                            <span id="new-customers">--</span> questo mese
                        </div>
                    </div>
                    
                    <div class="yht-dashboard-card yht-card-revenue">
                        <h3>💰 Ricavi</h3>
                        <div class="yht-stat-number" id="total-revenue">--</div>
                        <div class="yht-stat-label">Questo mese</div>
                        <div class="yht-stat-detail">
                            <span id="revenue-growth">--</span>% vs scorso mese
                        </div>
                    </div>
                    
                    <div class="yht-dashboard-card yht-card-performance">
                        <h3>⚡ Performance</h3>
                        <div class="yht-stat-number" id="system-health">--</div>
                        <div class="yht-stat-label">Salute sistema</div>
                        <div class="yht-stat-detail">
                            <span id="page-load">--</span>ms caricamento
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="yht-dashboard-row">
                    <div class="yht-dashboard-section">
                        <h3>🚀 Azioni Rapide</h3>
                        <div class="yht-quick-actions">
                            <a href="<?php echo admin_url('admin.php?page=yht_bookings'); ?>" class="button button-primary">
                                📋 Gestisci Prenotazioni
                            </a>
                            <a href="<?php echo admin_url('edit.php?post_type=yht_luogo'); ?>" class="button button-secondary">
                                🌍 Luoghi & Tours
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=yht_customers'); ?>" class="button button-secondary">
                                👥 Gestione Clienti
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=yht_analytics'); ?>" class="button button-secondary">
                                📊 Analytics
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="yht-dashboard-row">
                    <div class="yht-dashboard-section yht-recent-activity">
                        <h3>📈 Attività Recente</h3>
                        <div id="recent-activity-list">
                            <div class="yht-loading">Caricamento attività recente...</div>
                        </div>
                    </div>
                    
                    <div class="yht-dashboard-section yht-system-alerts">
                        <h3>🔔 Avvisi Sistema</h3>
                        <div id="system-alerts-list">
                            <div class="yht-loading">Controllo stato sistema...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .yht-dashboard-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .yht-dashboard-card {
            flex: 1;
            min-width: 200px;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .yht-dashboard-card h3 {
            margin: 0 0 15px 0;
            font-size: 14px;
            font-weight: 600;
            color: #1d2327;
        }
        
        .yht-stat-number {
            font-size: 32px;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .yht-card-bookings .yht-stat-number { color: #2271b1; }
        .yht-card-customers .yht-stat-number { color: #00a32a; }
        .yht-card-revenue .yht-stat-number { color: #dba617; }
        .yht-card-performance .yht-stat-number { color: #8c8f94; }
        
        .yht-stat-label {
            font-size: 12px;
            color: #646970;
            margin-bottom: 8px;
        }
        
        .yht-stat-detail {
            font-size: 12px;
            color: #2271b1;
        }
        
        .yht-dashboard-section {
            flex: 1;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .yht-dashboard-section h3 {
            margin: 0 0 15px 0;
            font-size: 14px;
            font-weight: 600;
        }
        
        .yht-quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .yht-loading {
            text-align: center;
            padding: 20px;
            color: #646970;
            font-style: italic;
        }
        
        .yht-activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f1;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .yht-activity-item:last-child {
            border-bottom: none;
        }
        
        .yht-activity-icon {
            font-size: 18px;
        }
        
        .yht-activity-content {
            flex: 1;
        }
        
        .yht-activity-title {
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .yht-activity-time {
            font-size: 12px;
            color: #646970;
        }
        
        .yht-alert-item {
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 4px;
            border-left: 4px solid;
        }
        
        .yht-alert-info {
            background: #f0f6fc;
            border-left-color: #2271b1;
        }
        
        .yht-alert-warning {
            background: #fcf9e8;
            border-left-color: #dba617;
        }
        
        .yht-alert-error {
            background: #fcf0f1;
            border-left-color: #d63638;
        }
        
        @media (max-width: 782px) {
            .yht-dashboard-row {
                flex-direction: column;
            }
            
            .yht-quick-actions {
                justify-content: center;
            }
        }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Load dashboard stats
            loadDashboardStats();
            
            function loadDashboardStats() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'yht_dashboard_stats',
                        nonce: '<?php echo wp_create_nonce('yht_dashboard_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            updateDashboardStats(response.data);
                            loadRecentActivity(response.data.recent_activity);
                            loadSystemAlerts(response.data.system_alerts);
                        }
                    }
                });
            }
            
            function updateDashboardStats(data) {
                $('#total-bookings').text(data.bookings.total || '0');
                $('#pending-bookings').text(data.bookings.pending || '0');
                $('#total-customers').text(data.customers.total || '0');
                $('#new-customers').text(data.customers.new_this_month || '0');
                $('#total-revenue').text('€' + (data.revenue.this_month || '0'));
                $('#revenue-growth').text(data.revenue.growth || '0');
                $('#system-health').text(data.performance.health_score || '100') + '%';
                $('#page-load').text(data.performance.avg_load_time || '0');
            }
            
            function loadRecentActivity(activities) {
                var html = '';
                if (activities && activities.length) {
                    activities.forEach(function(activity) {
                        html += '<div class="yht-activity-item">';
                        html += '<div class="yht-activity-icon">' + activity.icon + '</div>';
                        html += '<div class="yht-activity-content">';
                        html += '<div class="yht-activity-title">' + activity.title + '</div>';
                        html += '<div class="yht-activity-time">' + activity.time + '</div>';
                        html += '</div>';
                        html += '</div>';
                    });
                } else {
                    html = '<div class="yht-loading">Nessuna attività recente</div>';
                }
                $('#recent-activity-list').html(html);
            }
            
            function loadSystemAlerts(alerts) {
                var html = '';
                if (alerts && alerts.length) {
                    alerts.forEach(function(alert) {
                        html += '<div class="yht-alert-item yht-alert-' + alert.type + '">';
                        html += '<strong>' + alert.title + '</strong><br>';
                        html += alert.message;
                        html += '</div>';
                    });
                } else {
                    html = '<div class="yht-alert-item yht-alert-info">✅ Sistema operativo correttamente</div>';
                }
                $('#system-alerts-list').html(html);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Get dashboard statistics via AJAX
     */
    public function get_dashboard_stats() {
        check_ajax_referer('yht_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $stats = array(
            'bookings' => $this->get_booking_stats(),
            'customers' => $this->get_customer_stats(),
            'revenue' => $this->get_revenue_stats(),
            'performance' => $this->get_performance_stats(),
            'recent_activity' => $this->get_recent_activity(),
            'system_alerts' => $this->get_system_alerts()
        );
        
        wp_send_json_success($stats);
    }
    
    /**
     * Get booking statistics
     */
    private function get_booking_stats() {
        $total_bookings = wp_count_posts('yht_booking');
        $pending_bookings = get_posts(array(
            'post_type' => 'yht_booking',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'yht_booking_status',
                    'value' => 'pending'
                )
            )
        ));
        
        return array(
            'total' => $total_bookings->publish + $total_bookings->private,
            'pending' => count($pending_bookings)
        );
    }
    
    /**
     * Get customer statistics
     */
    private function get_customer_stats() {
        // Get unique customers from bookings
        global $wpdb;
        $total_customers = $wpdb->get_var("
            SELECT COUNT(DISTINCT meta_value) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'yht_customer_email'
        ");
        
        $new_this_month = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT p.meta_value) 
            FROM {$wpdb->postmeta} p
            JOIN {$wpdb->posts} po ON p.post_id = po.ID
            WHERE p.meta_key = 'yht_customer_email'
            AND po.post_date >= %s
        ", date('Y-m-01')));
        
        return array(
            'total' => $total_customers ?: 0,
            'new_this_month' => $new_this_month ?: 0
        );
    }
    
    /**
     * Get revenue statistics
     */
    private function get_revenue_stats() {
        global $wpdb;
        
        // This month revenue
        $this_month = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(CAST(meta_value AS DECIMAL(10,2)))
            FROM {$wpdb->postmeta} p
            JOIN {$wpdb->posts} po ON p.post_id = po.ID
            WHERE p.meta_key = 'yht_total_price'
            AND po.post_date >= %s
        ", date('Y-m-01')));
        
        // Last month revenue
        $last_month = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(CAST(meta_value AS DECIMAL(10,2)))
            FROM {$wpdb->postmeta} p
            JOIN {$wpdb->posts} po ON p.post_id = po.ID
            WHERE p.meta_key = 'yht_total_price'
            AND po.post_date >= %s
            AND po.post_date < %s
        ", date('Y-m-01', strtotime('-1 month')), date('Y-m-01')));
        
        $growth = 0;
        if ($last_month > 0) {
            $growth = round((($this_month - $last_month) / $last_month) * 100, 1);
        }
        
        return array(
            'this_month' => number_format($this_month ?: 0, 2),
            'growth' => $growth
        );
    }
    
    /**
     * Get performance statistics
     */
    private function get_performance_stats() {
        return array(
            'health_score' => 95, // Could be calculated based on various factors
            'avg_load_time' => rand(200, 800) // Could be from real analytics
        );
    }
    
    /**
     * Get recent activity
     */
    private function get_recent_activity() {
        $activities = array();
        
        // Recent bookings
        $recent_bookings = get_posts(array(
            'post_type' => 'yht_booking',
            'posts_per_page' => 3,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        foreach ($recent_bookings as $booking) {
            $customer_name = get_post_meta($booking->ID, 'yht_customer_name', true);
            $activities[] = array(
                'icon' => '📋',
                'title' => 'Nuova prenotazione da ' . ($customer_name ?: 'Cliente'),
                'time' => human_time_diff(strtotime($booking->post_date)) . ' fa'
            );
        }
        
        return array_slice($activities, 0, 5);
    }
    
    /**
     * Get system alerts
     */
    private function get_system_alerts() {
        $alerts = array();
        
        // Check plugin requirements
        if (!class_exists('WooCommerce')) {
            $alerts[] = array(
                'type' => 'warning',
                'title' => 'WooCommerce non attivo',
                'message' => 'Alcune funzionalità potrebbero non funzionare correttamente.'
            );
        }
        
        // Check settings
        $settings = get_option(YHT_OPT, array());
        if (empty($settings['brevo_api_key'])) {
            $alerts[] = array(
                'type' => 'info',
                'title' => 'API Key Brevo mancante',
                'message' => 'Configura la chiave API per l\'invio automatico delle email.'
            );
        }
        
        return $alerts;
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_yht_admin' !== $hook) {
            return;
        }
        
        wp_enqueue_script('jquery');
    }
}