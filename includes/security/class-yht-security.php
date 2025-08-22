<?php
/**
 * YHT Security Enhancement Module
 * Advanced security features including rate limiting, input validation, and threat protection
 */

class YHT_Security {
    private $rate_limiter_table;
    private $security_log_table;
    
    public function __construct() {
        global $wpdb;
        $this->rate_limiter_table = $wpdb->prefix . 'yht_rate_limits';
        $this->security_log_table = $wpdb->prefix . 'yht_security_log';
        
        add_action('rest_api_init', array($this, 'setup_security_middleware'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_security_scripts'));
        add_filter('yht_validate_input', array($this, 'advanced_input_validation'), 10, 3);
        
        // Security headers
        add_action('send_headers', array($this, 'add_security_headers'));
        
        // CSRF protection
        add_action('wp_ajax_yht_security_nonce', array($this, 'refresh_security_nonce'));
        add_action('wp_ajax_nopriv_yht_security_nonce', array($this, 'refresh_security_nonce'));
        
        // Create security tables
        register_activation_hook(YHT_PLUGIN_FILE, array($this, 'create_security_tables'));
    }
    
    /**
     * Setup security middleware for REST API
     */
    public function setup_security_middleware() {
        // Add pre-dispatch hook for rate limiting
        add_filter('rest_pre_dispatch', array($this, 'rate_limit_middleware'), 10, 3);
        
        // Add authentication for sensitive endpoints
        add_filter('rest_authentication_errors', array($this, 'enhanced_authentication'));
    }
    
    /**
     * Rate limiting middleware
     */
    public function rate_limit_middleware($result, $server, $request) {
        $route = $request->get_route();
        
        // Apply rate limiting to YHT endpoints
        if (strpos($route, '/yht/v1/') === 0) {
            $client_ip = $this->get_client_ip();
            $endpoint = $this->normalize_endpoint($route);
            
            if (!$this->check_rate_limit($client_ip, $endpoint)) {
                return new WP_Error(
                    'rate_limit_exceeded',
                    'Rate limit exceeded. Please try again later.',
                    array('status' => 429)
                );
            }
            
            $this->record_api_request($client_ip, $endpoint, $request);
        }
        
        return $result;
    }
    
    /**
     * Enhanced authentication for sensitive endpoints
     */
    public function enhanced_authentication($errors) {
        if (!empty($errors)) {
            return $errors;
        }
        
        global $wp;
        $request_uri = home_url($wp->request);
        
        // Check for suspicious patterns
        if ($this->is_suspicious_request($request_uri)) {
            $this->log_security_event('suspicious_request', array(
                'uri' => $request_uri,
                'ip' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ));
            
            return new WP_Error(
                'suspicious_activity',
                'Request blocked due to suspicious activity.',
                array('status' => 403)
            );
        }
        
        return $errors;
    }
    
    /**
     * Check rate limit for IP and endpoint
     */
    private function check_rate_limit($ip, $endpoint) {
        global $wpdb;
        
        $limits = $this->get_rate_limits();
        $limit_config = $limits[$endpoint] ?? $limits['default'];
        
        $current_time = time();
        $window_start = $current_time - $limit_config['window'];
        
        // Clean old entries
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->rate_limiter_table} WHERE timestamp < %d",
                $window_start
            )
        );
        
        // Count requests in current window
        $request_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->rate_limiter_table} 
                 WHERE ip_address = %s AND endpoint = %s AND timestamp >= %d",
                $ip, $endpoint, $window_start
            )
        );
        
        return $request_count < $limit_config['requests'];
    }
    
    /**
     * Record API request for rate limiting
     */
    private function record_api_request($ip, $endpoint, $request) {
        global $wpdb;
        
        $wpdb->insert(
            $this->rate_limiter_table,
            array(
                'ip_address' => $ip,
                'endpoint' => $endpoint,
                'method' => $request->get_method(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'timestamp' => time()
            ),
            array('%s', '%s', '%s', '%s', '%d')
        );
    }
    
    /**
     * Get rate limit configuration
     */
    private function get_rate_limits() {
        return array(
            'default' => array('requests' => 60, 'window' => 3600), // 60 requests per hour
            '/yht/v1/generate' => array('requests' => 10, 'window' => 3600), // 10 tour generations per hour
            '/yht/v1/analytics' => array('requests' => 100, 'window' => 300), // 100 analytics requests per 5 min
            '/yht/v1/booking' => array('requests' => 5, 'window' => 3600), // 5 bookings per hour
            '/yht/v1/lead' => array('requests' => 20, 'window' => 3600) // 20 leads per hour
        );
    }
    
    /**
     * Normalize endpoint for rate limiting
     */
    private function normalize_endpoint($route) {
        // Remove dynamic parts and normalize
        $route = preg_replace('/\/\d+$/', '/{id}', $route);
        $route = preg_replace('/\/[a-f0-9-]{36}$/', '/{uuid}', $route);
        
        return $route;
    }
    
    /**
     * Advanced input validation
     */
    public function advanced_input_validation($is_valid, $input, $type) {
        switch ($type) {
            case 'email':
                return $this->validate_email($input);
                
            case 'phone':
                return $this->validate_phone($input);
                
            case 'date':
                return $this->validate_date($input);
                
            case 'coordinates':
                return $this->validate_coordinates($input);
                
            case 'html':
                return $this->validate_html($input);
                
            case 'json':
                return $this->validate_json($input);
                
            default:
                return $this->validate_general($input);
        }
    }
    
    /**
     * Validate email with advanced checks
     */
    private function validate_email($email) {
        if (!is_email($email)) {
            return false;
        }
        
        // Check for disposable email domains
        $disposable_domains = $this->get_disposable_email_domains();
        $domain = substr(strrchr($email, '@'), 1);
        
        if (in_array(strtolower($domain), $disposable_domains)) {
            $this->log_security_event('disposable_email_blocked', array(
                'email' => $email,
                'domain' => $domain
            ));
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate phone number
     */
    private function validate_phone($phone) {
        // Remove common formatting
        $clean_phone = preg_replace('/[^\d+]/', '', $phone);
        
        // Basic validation - adjust regex based on requirements
        $patterns = array(
            '/^\+39\d{10}$/', // Italian mobile
            '/^\+39\d{9,11}$/', // Italian landline
            '/^\+\d{10,15}$/' // International
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $clean_phone)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate date format and range
     */
    private function validate_date($date) {
        $parsed_date = DateTime::createFromFormat('Y-m-d', $date);
        
        if (!$parsed_date || $parsed_date->format('Y-m-d') !== $date) {
            return false;
        }
        
        // Check reasonable date range (not too far in past/future)
        $now = new DateTime();
        $min_date = (clone $now)->sub(new DateInterval('P10Y')); // 10 years ago
        $max_date = (clone $now)->add(new DateInterval('P5Y')); // 5 years ahead
        
        return $parsed_date >= $min_date && $parsed_date <= $max_date;
    }
    
    /**
     * Validate coordinates
     */
    private function validate_coordinates($coords) {
        if (is_string($coords)) {
            $parts = explode(',', $coords);
            if (count($parts) !== 2) {
                return false;
            }
            $lat = floatval(trim($parts[0]));
            $lng = floatval(trim($parts[1]));
        } elseif (is_array($coords) && count($coords) === 2) {
            $lat = floatval($coords[0]);
            $lng = floatval($coords[1]);
        } else {
            return false;
        }
        
        // Validate coordinate ranges
        return ($lat >= -90 && $lat <= 90) && ($lng >= -180 && $lng <= 180);
    }
    
    /**
     * Validate and sanitize HTML
     */
    private function validate_html($html) {
        // Use WordPress sanitization
        $allowed_tags = array(
            'p' => array(),
            'br' => array(),
            'strong' => array(),
            'em' => array(),
            'ul' => array(),
            'ol' => array(),
            'li' => array(),
            'a' => array('href' => array(), 'title' => array())
        );
        
        $sanitized = wp_kses($html, $allowed_tags);
        
        // Check for suspicious patterns
        $suspicious_patterns = array(
            '/<script/i',
            '/javascript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/<iframe/i'
        );
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $html)) {
                $this->log_security_event('suspicious_html_blocked', array(
                    'html' => substr($html, 0, 200),
                    'pattern' => $pattern
                ));
                return false;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Validate JSON input
     */
    private function validate_json($json) {
        if (is_string($json)) {
            $decoded = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }
            $json = $decoded;
        }
        
        // Check for reasonable size and depth
        $json_string = wp_json_encode($json);
        if (strlen($json_string) > 50000) { // 50KB limit
            return false;
        }
        
        return $this->check_json_depth($json, 0, 10); // Max 10 levels deep
    }
    
    /**
     * Check JSON depth recursively
     */
    private function check_json_depth($data, $current_depth, $max_depth) {
        if ($current_depth > $max_depth) {
            return false;
        }
        
        if (is_array($data) || is_object($data)) {
            foreach ($data as $value) {
                if (!$this->check_json_depth($value, $current_depth + 1, $max_depth)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * General input validation
     */
    private function validate_general($input) {
        // Check for common attack patterns
        $attack_patterns = array(
            '/\<script.*?\>/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/\<iframe.*?\>/i',
            '/union.*select/i',
            '/or\s+1\s*=\s*1/i',
            '/\'\s*;\s*drop/i'
        );
        
        foreach ($attack_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->log_security_event('attack_pattern_detected', array(
                    'input' => substr($input, 0, 200),
                    'pattern' => $pattern
                ));
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if request is suspicious
     */
    private function is_suspicious_request($uri) {
        $suspicious_patterns = array(
            '/wp-admin/i',
            '/wp-login/i',
            '/xmlrpc/i',
            '/.git/i',
            '/.env/i',
            '/admin/i',
            '/phpmyadmin/i',
            '/eval\(/i',
            '/base64_decode/i'
        );
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $uri)) {
                return true;
            }
        }
        
        // Check user agent
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $bad_user_agents = array('sqlmap', 'nikto', 'whatweb', 'nmap');
        
        foreach ($bad_user_agents as $bad_agent) {
            if (stripos($user_agent, $bad_agent) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Add security headers
     */
    public function add_security_headers() {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: microphone=(), camera=(), geolocation=(self)');
            
            // Only add HSTS on HTTPS
            if (is_ssl()) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
        }
    }
    
    /**
     * Enqueue security-related frontend scripts
     */
    public function enqueue_security_scripts() {
        wp_enqueue_script(
            'yht-security',
            YHT_PLUGIN_URL . 'assets/js/yht-security.js',
            array(),
            YHT_VER,
            true
        );
        
        wp_localize_script('yht-security', 'yhtSecurity', array(
            'nonce' => wp_create_nonce('yht_security_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'rate_limit_exceeded' => __('Too many requests. Please slow down.', 'your-hidden-trip')
        ));
    }
    
    /**
     * Refresh security nonce via AJAX
     */
    public function refresh_security_nonce() {
        wp_send_json_success(array(
            'nonce' => wp_create_nonce('yht_security_nonce')
        ));
    }
    
    /**
     * Log security events
     */
    private function log_security_event($event_type, $details = array()) {
        global $wpdb;
        
        $wpdb->insert(
            $this->security_log_table,
            array(
                'event_type' => $event_type,
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'details' => wp_json_encode($details),
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
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
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get list of disposable email domains
     */
    private function get_disposable_email_domains() {
        // Cache the list for performance
        $domains = get_transient('yht_disposable_domains');
        if (false === $domains) {
            $domains = array(
                '10minutemail.com', 'tempmail.org', 'guerrillamail.com',
                'mailinator.com', 'throwaway.email', '0-mail.com',
                'temp-mail.org', 'getairmail.com', 'temporary-mail.net'
            );
            set_transient('yht_disposable_domains', $domains, HOUR_IN_SECONDS);
        }
        
        return $domains;
    }
    
    /**
     * Create security-related database tables
     */
    public function create_security_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Rate limiting table
        $rate_limit_sql = "CREATE TABLE {$this->rate_limiter_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            endpoint varchar(255) NOT NULL,
            method varchar(10) NOT NULL,
            user_agent varchar(500),
            timestamp int(11) NOT NULL,
            PRIMARY KEY (id),
            INDEX idx_ip_endpoint (ip_address, endpoint),
            INDEX idx_timestamp (timestamp)
        ) $charset_collate;";
        
        // Security log table
        $security_log_sql = "CREATE TABLE {$this->security_log_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(100) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent varchar(500),
            details longtext,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_event_type (event_type),
            INDEX idx_ip_address (ip_address),
            INDEX idx_timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($rate_limit_sql);
        dbDelta($security_log_sql);
    }
    
    /**
     * Get security statistics for admin dashboard
     */
    public function get_security_stats($timeframe = '24h') {
        global $wpdb;
        
        $hours = $timeframe === '24h' ? 24 : ($timeframe === '7d' ? 168 : 24);
        $since = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        
        $stats = array();
        
        // Blocked requests
        $stats['blocked_requests'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->security_log_table} 
                 WHERE event_type IN ('rate_limit_exceeded', 'suspicious_request', 'attack_pattern_detected') 
                 AND timestamp >= %s",
                $since
            )
        );
        
        // Top attacking IPs
        $stats['top_attackers'] = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ip_address, COUNT(*) as attempts 
                 FROM {$this->security_log_table} 
                 WHERE timestamp >= %s 
                 GROUP BY ip_address 
                 ORDER BY attempts DESC 
                 LIMIT 10",
                $since
            )
        );
        
        // Attack types
        $stats['attack_types'] = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT event_type, COUNT(*) as count 
                 FROM {$this->security_log_table} 
                 WHERE timestamp >= %s 
                 GROUP BY event_type 
                 ORDER BY count DESC",
                $since
            )
        );
        
        return $stats;
    }
    
    /**
     * Cleanup old security logs and rate limit data
     */
    public function cleanup_security_data($retention_days = 30) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        $cutoff_timestamp = strtotime("-{$retention_days} days");
        
        // Clean security logs
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->security_log_table} WHERE timestamp < %s",
                $cutoff_date
            )
        );
        
        // Clean rate limit data  
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->rate_limiter_table} WHERE timestamp < %d",
                $cutoff_timestamp
            )
        );
    }
}