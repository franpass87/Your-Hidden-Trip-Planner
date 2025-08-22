<?php
/**
 * YHT Analytics Handler
 * Server-side analytics processing, data storage, and reporting
 */

class YHT_Analytics {
    private $table_name;
    private $heatmap_table;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'yht_analytics';
        $this->heatmap_table = $wpdb->prefix . 'yht_heatmap';
        
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_analytics_script'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Create tables on activation
        register_activation_hook(YHT_PLUGIN_FILE, array($this, 'create_analytics_tables'));
    }
    
    /**
     * Register REST API endpoints for analytics
     */
    public function register_routes() {
        // Analytics data collection endpoint
        register_rest_route('yht/v1', '/analytics', array(
            'methods' => 'POST',
            'callback' => array($this, 'store_analytics_data'),
            'permission_callback' => '__return_true', // Public endpoint for data collection
        ));
        
        // Heatmap data collection endpoint  
        register_rest_route('yht/v1', '/heatmap', array(
            'methods' => 'POST',
            'callback' => array($this, 'store_heatmap_data'),
            'permission_callback' => '__return_true',
        ));
        
        // Analytics report endpoint (admin only)
        register_rest_route('yht/v1', '/analytics/report', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_analytics_report'),
            'permission_callback' => array($this, 'check_admin_permissions'),
        ));
        
        // Dashboard data endpoint
        register_rest_route('yht/v1', '/analytics/dashboard', array(
            'methods' => 'GET', 
            'callback' => array($this, 'get_dashboard_data'),
            'permission_callback' => array($this, 'check_admin_permissions'),
        ));
    }
    
    /**
     * Check admin permissions
     */
    public function check_admin_permissions() {
        return current_user_can('manage_options');
    }
    
    /**
     * Enqueue analytics script on frontend
     */
    public function enqueue_analytics_script() {
        // Only load on YHT pages or where shortcode is used
        global $post;
        if (is_admin() || 
            (isset($post->post_content) && has_shortcode($post->post_content, 'yht_trip_builder')) ||
            get_post_type() === 'yht_luoghi' || 
            get_post_type() === 'yht_tour') {
            
            wp_enqueue_script(
                'yht-analytics',
                YHT_PLUGIN_URL . 'assets/js/yht-analytics.js',
                array(),
                YHT_VER,
                true
            );
            
            // Pass configuration data
            wp_localize_script('yht-analytics', 'yhtAnalyticsConfig', array(
                'enabled' => $this->is_analytics_enabled(),
                'nonce' => wp_create_nonce('wp_rest'),
                'api_url' => rest_url('yht/v1/'),
                'user_consent' => $this->has_user_consent()
            ));
        }
    }
    
    /**
     * Enqueue admin analytics scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on YHT admin pages
        if (strpos($hook, 'yht') !== false || $hook === 'toplevel_page_yht-settings') {
            wp_enqueue_script(
                'yht-admin-analytics',
                YHT_PLUGIN_URL . 'assets/js/yht-admin-analytics.js',
                array('jquery', 'chart-js'),
                YHT_VER,
                true
            );
            
            // Enqueue Chart.js for visualizations
            wp_enqueue_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
                array(),
                '3.9.1',
                true
            );
        }
    }
    
    /**
     * Check if analytics is enabled
     */
    private function is_analytics_enabled() {
        $settings = get_option('yht_settings', array());
        return isset($settings['analytics_enabled']) && $settings['analytics_enabled'];
    }
    
    /**
     * Check user consent for analytics
     */
    private function has_user_consent() {
        // Check for cookie consent or privacy settings
        return isset($_COOKIE['yht_analytics_consent']) && $_COOKIE['yht_analytics_consent'] === 'accepted';
    }
    
    /**
     * Create analytics database tables
     */
    public function create_analytics_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Main analytics table
        $analytics_sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            user_id varchar(100) NOT NULL,
            event_name varchar(100) NOT NULL,
            event_data longtext,
            page_url varchar(500),
            user_agent varchar(500),
            ip_address varchar(45),
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_session_id (session_id),
            INDEX idx_user_id (user_id),
            INDEX idx_event_name (event_name),
            INDEX idx_timestamp (timestamp)
        ) $charset_collate;";
        
        // Heatmap data table
        $heatmap_sql = "CREATE TABLE {$this->heatmap_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            user_id varchar(100) NOT NULL,
            page_url varchar(500),
            viewport_width int,
            viewport_height int,
            click_data longtext,
            move_data longtext,
            scroll_data longtext,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_session_id (session_id),
            INDEX idx_page_url (page_url),
            INDEX idx_timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($analytics_sql);
        dbDelta($heatmap_sql);
    }
    
    /**
     * Store analytics data
     */
    public function store_analytics_data($request) {
        if (!$this->is_analytics_enabled() || !$this->has_user_consent()) {
            return new WP_Error('analytics_disabled', 'Analytics is disabled', array('status' => 200));
        }
        
        global $wpdb;
        
        $data = $request->get_json_params();
        $events = $data['events'] ?? array();
        
        if (empty($events)) {
            return new WP_Error('no_events', 'No events provided', array('status' => 400));
        }
        
        $inserted = 0;
        
        foreach ($events as $event) {
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'session_id' => sanitize_text_field($data['session_id']),
                    'user_id' => sanitize_text_field($data['user_id']),
                    'event_name' => sanitize_text_field($event['name']),
                    'event_data' => wp_json_encode($event['data']),
                    'page_url' => esc_url_raw($event['page_url']),
                    'user_agent' => sanitize_text_field($event['user_agent']),
                    'ip_address' => $this->get_client_ip(),
                    'timestamp' => date('Y-m-d H:i:s', $event['timestamp'] / 1000)
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result !== false) {
                $inserted++;
            }
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'inserted' => $inserted
        ));
    }
    
    /**
     * Store heatmap data
     */
    public function store_heatmap_data($request) {
        if (!$this->is_analytics_enabled() || !$this->has_user_consent()) {
            return new WP_Error('analytics_disabled', 'Analytics is disabled', array('status' => 200));
        }
        
        global $wpdb;
        
        $data = $request->get_json_params();
        $heatmap_data = $data['heatmap_data'] ?? array();
        
        if (empty($heatmap_data)) {
            return new WP_Error('no_heatmap_data', 'No heatmap data provided', array('status' => 400));
        }
        
        $result = $wpdb->insert(
            $this->heatmap_table,
            array(
                'session_id' => sanitize_text_field($data['session_id']),
                'user_id' => sanitize_text_field($data['user_id']),
                'page_url' => esc_url_raw($data['page_url']),
                'viewport_width' => intval($data['viewport']['width']),
                'viewport_height' => intval($data['viewport']['height']),
                'click_data' => wp_json_encode($heatmap_data['clickHeatmap']),
                'move_data' => wp_json_encode($heatmap_data['moveHeatmap']),
                'scroll_data' => wp_json_encode($heatmap_data['scrollHeatmap']),
                'timestamp' => date('Y-m-d H:i:s', $data['timestamp'] / 1000)
            ),
            array('%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s')
        );
        
        return rest_ensure_response(array(
            'success' => $result !== false
        ));
    }
    
    /**
     * Get analytics report
     */
    public function get_analytics_report($request) {
        $timeframe = $request->get_param('timeframe') ?? '7d';
        $page_url = $request->get_param('page_url');
        
        $report = array(
            'summary' => $this->get_analytics_summary($timeframe),
            'events' => $this->get_event_analytics($timeframe, $page_url),
            'performance' => $this->get_performance_analytics($timeframe),
            'funnel' => $this->get_conversion_funnel($timeframe),
            'experiments' => $this->get_experiment_results($timeframe),
            'user_journey' => $this->get_user_journey_analytics($timeframe)
        );
        
        return rest_ensure_response($report);
    }
    
    /**
     * Get dashboard data
     */
    public function get_dashboard_data($request) {
        $dashboard_data = array(
            'overview' => $this->get_analytics_summary('30d'),
            'top_pages' => $this->get_top_pages('7d'),
            'real_time' => $this->get_real_time_data(),
            'alerts' => $this->get_performance_alerts()
        );
        
        return rest_ensure_response($dashboard_data);
    }
    
    /**
     * Get analytics summary
     */
    private function get_analytics_summary($timeframe) {
        global $wpdb;
        
        $days = $this->get_days_from_timeframe($timeframe);
        $date_condition = "timestamp >= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
        
        // Total events
        $total_events = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE {$date_condition}"
        );
        
        // Unique users
        $unique_users = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) FROM {$this->table_name} WHERE {$date_condition}"
        );
        
        // Total sessions
        $total_sessions = $wpdb->get_var(
            "SELECT COUNT(DISTINCT session_id) FROM {$this->table_name} WHERE {$date_condition}"
        );
        
        // Average session duration
        $avg_session_duration = $wpdb->get_var(
            "SELECT AVG(duration) FROM (
                SELECT session_id, 
                       MAX(UNIX_TIMESTAMP(timestamp)) - MIN(UNIX_TIMESTAMP(timestamp)) as duration
                FROM {$this->table_name} 
                WHERE {$date_condition}
                GROUP BY session_id
                HAVING COUNT(*) > 1
            ) as session_durations"
        );
        
        return array(
            'total_events' => intval($total_events),
            'unique_users' => intval($unique_users),
            'total_sessions' => intval($total_sessions),
            'avg_session_duration' => round(floatval($avg_session_duration), 2),
            'timeframe' => $timeframe
        );
    }
    
    /**
     * Get event analytics
     */
    private function get_event_analytics($timeframe, $page_url = null) {
        global $wpdb;
        
        $days = $this->get_days_from_timeframe($timeframe);
        $date_condition = "timestamp >= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
        
        if ($page_url) {
            $date_condition .= $wpdb->prepare(" AND page_url = %s", $page_url);
        }
        
        // Most frequent events
        $top_events = $wpdb->get_results(
            "SELECT event_name, COUNT(*) as count 
             FROM {$this->table_name} 
             WHERE {$date_condition}
             GROUP BY event_name 
             ORDER BY count DESC 
             LIMIT 10"
        );
        
        // Events over time
        $events_timeline = $wpdb->get_results(
            "SELECT DATE(timestamp) as date, COUNT(*) as count 
             FROM {$this->table_name} 
             WHERE {$date_condition}
             GROUP BY DATE(timestamp) 
             ORDER BY date ASC"
        );
        
        return array(
            'top_events' => $top_events,
            'timeline' => $events_timeline
        );
    }
    
    /**
     * Get performance analytics
     */
    private function get_performance_analytics($timeframe) {
        global $wpdb;
        
        $days = $this->get_days_from_timeframe($timeframe);
        $date_condition = "timestamp >= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
        
        // Core Web Vitals
        $core_vitals = $wpdb->get_results(
            "SELECT 
                JSON_EXTRACT(event_data, '$.metric') as metric,
                AVG(CAST(JSON_EXTRACT(event_data, '$.value') AS DECIMAL(10,2))) as avg_value,
                COUNT(*) as count
             FROM {$this->table_name} 
             WHERE event_name = 'core_web_vitals' AND {$date_condition}
             GROUP BY JSON_EXTRACT(event_data, '$.metric')"
        );
        
        // Page load performance
        $page_performance = $wpdb->get_results(
            "SELECT 
                page_url,
                AVG(CAST(JSON_EXTRACT(event_data, '$.total_load_time') AS DECIMAL(10,2))) as avg_load_time,
                COUNT(*) as measurements
             FROM {$this->table_name} 
             WHERE event_name = 'page_performance' AND {$date_condition}
             GROUP BY page_url
             ORDER BY avg_load_time DESC
             LIMIT 10"
        );
        
        return array(
            'core_vitals' => $core_vitals,
            'page_performance' => $page_performance
        );
    }
    
    /**
     * Get conversion funnel data
     */
    private function get_conversion_funnel($timeframe) {
        global $wpdb;
        
        $days = $this->get_days_from_timeframe($timeframe);
        $date_condition = "timestamp >= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
        
        $funnel_steps = $wpdb->get_results(
            "SELECT 
                JSON_EXTRACT(event_data, '$.step') as step,
                COUNT(DISTINCT user_id) as users
             FROM {$this->table_name} 
             WHERE event_name = 'funnel_step' AND {$date_condition}
             GROUP BY JSON_EXTRACT(event_data, '$.step')
             ORDER BY users DESC"
        );
        
        return $funnel_steps;
    }
    
    /**
     * Get A/B test results
     */
    private function get_experiment_results($timeframe) {
        global $wpdb;
        
        $days = $this->get_days_from_timeframe($timeframe);
        $date_condition = "timestamp >= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
        
        // Experiment assignments
        $assignments = $wpdb->get_results(
            "SELECT 
                JSON_EXTRACT(event_data, '$.experiment') as experiment,
                JSON_EXTRACT(event_data, '$.variant') as variant,
                COUNT(DISTINCT user_id) as users
             FROM {$this->table_name} 
             WHERE event_name = 'experiment_assignment' AND {$date_condition}
             GROUP BY JSON_EXTRACT(event_data, '$.experiment'), JSON_EXTRACT(event_data, '$.variant')"
        );
        
        // Experiment conversions
        $conversions = $wpdb->get_results(
            "SELECT 
                JSON_EXTRACT(event_data, '$.experiment') as experiment,
                JSON_EXTRACT(event_data, '$.variant') as variant,
                COUNT(DISTINCT user_id) as conversions
             FROM {$this->table_name} 
             WHERE event_name = 'experiment_conversion' AND {$date_condition}
             GROUP BY JSON_EXTRACT(event_data, '$.experiment'), JSON_EXTRACT(event_data, '$.variant')"
        );
        
        return array(
            'assignments' => $assignments,
            'conversions' => $conversions
        );
    }
    
    /**
     * Get user journey analytics
     */
    private function get_user_journey_analytics($timeframe) {
        global $wpdb;
        
        $days = $this->get_days_from_timeframe($timeframe);
        $date_condition = "timestamp >= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
        
        // Most common page sequences
        $page_sequences = $wpdb->get_results(
            "SELECT page_url, COUNT(*) as visits
             FROM {$this->table_name} 
             WHERE event_name = 'page_visit' AND {$date_condition}
             GROUP BY page_url
             ORDER BY visits DESC
             LIMIT 10"
        );
        
        return array(
            'page_sequences' => $page_sequences
        );
    }
    
    /**
     * Get top pages by traffic
     */
    private function get_top_pages($timeframe) {
        global $wpdb;
        
        $days = $this->get_days_from_timeframe($timeframe);
        $date_condition = "timestamp >= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
        
        return $wpdb->get_results(
            "SELECT 
                page_url,
                COUNT(DISTINCT session_id) as sessions,
                COUNT(DISTINCT user_id) as users,
                COUNT(*) as events
             FROM {$this->table_name} 
             WHERE {$date_condition}
             GROUP BY page_url
             ORDER BY sessions DESC
             LIMIT 10"
        );
    }
    
    /**
     * Get real-time data (last 30 minutes)
     */
    private function get_real_time_data() {
        global $wpdb;
        
        $active_users = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) 
             FROM {$this->table_name} 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)"
        );
        
        $recent_events = $wpdb->get_results(
            "SELECT event_name, COUNT(*) as count
             FROM {$this->table_name} 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
             GROUP BY event_name
             ORDER BY count DESC
             LIMIT 5"
        );
        
        return array(
            'active_users' => intval($active_users),
            'recent_events' => $recent_events
        );
    }
    
    /**
     * Get performance alerts
     */
    private function get_performance_alerts() {
        global $wpdb;
        
        $alerts = array();
        
        // Check for slow page load times
        $slow_pages = $wpdb->get_results(
            "SELECT page_url, AVG(CAST(JSON_EXTRACT(event_data, '$.total_load_time') AS DECIMAL(10,2))) as avg_load_time
             FROM {$this->table_name} 
             WHERE event_name = 'page_performance' 
               AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 DAY)
             GROUP BY page_url
             HAVING avg_load_time > 3000"
        );
        
        if (!empty($slow_pages)) {
            $alerts[] = array(
                'type' => 'warning',
                'message' => 'Detected slow page load times',
                'data' => $slow_pages
            );
        }
        
        // Check for high error rates
        $error_rate = $wpdb->get_var(
            "SELECT (COUNT(*) / (SELECT COUNT(*) FROM {$this->table_name} WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)) * 100) as error_rate
             FROM {$this->table_name} 
             WHERE event_name IN ('javascript_error', 'promise_rejection') 
               AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
        
        if (floatval($error_rate) > 5) {
            $alerts[] = array(
                'type' => 'error',
                'message' => 'High JavaScript error rate detected',
                'data' => array('error_rate' => round($error_rate, 2))
            );
        }
        
        return $alerts;
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Convert timeframe to days
     */
    private function get_days_from_timeframe($timeframe) {
        $timeframes = array(
            '1d' => 1,
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365
        );
        
        return $timeframes[$timeframe] ?? 7;
    }
    
    /**
     * Data cleanup - remove old analytics data
     */
    public function cleanup_old_data($retention_days = 365) {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $retention_days
            )
        );
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->heatmap_table} WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $retention_days
            )
        );
    }
    
    /**
     * Export analytics data
     */
    public function export_analytics_data($timeframe = '30d', $format = 'csv') {
        global $wpdb;
        
        $days = $this->get_days_from_timeframe($timeframe);
        $date_condition = "timestamp >= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
        
        $results = $wpdb->get_results(
            "SELECT * FROM {$this->table_name} WHERE {$date_condition} ORDER BY timestamp DESC",
            ARRAY_A
        );
        
        if ($format === 'csv') {
            return $this->array_to_csv($results);
        }
        
        return wp_json_encode($results);
    }
    
    /**
     * Convert array to CSV
     */
    private function array_to_csv($data) {
        if (empty($data)) return '';
        
        $output = fopen('php://temp', 'r+');
        
        // Headers
        fputcsv($output, array_keys($data[0]));
        
        // Data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}