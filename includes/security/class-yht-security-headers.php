<?php
/**
 * Enhanced security headers and protection system
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_Security_Headers {
    
    /**
     * Initialize security headers
     */
    public function __construct() {
        add_action('send_headers', array($this, 'add_security_headers'));
        add_action('wp_head', array($this, 'add_meta_security'), 1);
        add_action('init', array($this, 'init_security_measures'));
    }
    
    /**
     * Add HTTP security headers
     */
    public function add_security_headers() {
        // Only add headers on frontend and admin
        if (wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // X-Frame-Options: Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // X-Content-Type-Options: Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // X-XSS-Protection: Enable XSS filtering
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer Policy: Control referrer information
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions Policy: Control browser features
        $permissions_policy = [
            'geolocation=(self)',
            'camera=()',
            'microphone=()',
            'payment=(self)',
            'usb=()',
            'magnetometer=()',
            'accelerometer=()',
            'gyroscope=()'
        ];
        header('Permissions-Policy: ' . implode(', ', $permissions_policy));
        
        // HTTPS Strict Transport Security (only for HTTPS)
        if (is_ssl()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Content Security Policy
        $this->add_content_security_policy();
    }
    
    /**
     * Add Content Security Policy header
     */
    private function add_content_security_policy() {
        $nonce = wp_create_nonce('yht_csp_nonce');
        $site_url = parse_url(home_url(), PHP_URL_HOST);
        
        $csp_directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' *.googleapis.com *.gstatic.com *.google.com *.googletagmanager.com *.google-analytics.com",
            "style-src 'self' 'unsafe-inline' *.googleapis.com *.gstatic.com fonts.googleapis.com",
            "font-src 'self' fonts.gstatic.com fonts.googleapis.com",
            "img-src 'self' data: blob: *.googleapis.com *.gstatic.com *.google.com *.googleusercontent.com {$site_url}",
            "connect-src 'self' *.googleapis.com *.google-analytics.com *.googletagmanager.com",
            "frame-src 'self' *.google.com *.youtube.com *.vimeo.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "upgrade-insecure-requests"
        ];
        
        // Allow for admin customization
        $csp_directives = apply_filters('yht_csp_directives', $csp_directives);
        
        $csp = implode('; ', $csp_directives);
        
        // Use report-only mode in development
        if (defined('WP_DEBUG') && WP_DEBUG) {
            header("Content-Security-Policy-Report-Only: {$csp}");
        } else {
            header("Content-Security-Policy: {$csp}");
        }
    }
    
    /**
     * Add security meta tags
     */
    public function add_meta_security() {
        echo '<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex, notranslate" />' . "\n";
        echo '<meta http-equiv="X-DNS-Prefetch-Control" content="off" />' . "\n";
    }
    
    /**
     * Initialize additional security measures
     */
    public function init_security_measures() {
        // Remove WordPress version from head
        remove_action('wp_head', 'wp_generator');
        
        // Remove version from scripts and styles
        add_filter('style_loader_src', array($this, 'remove_version_strings'), 9999);
        add_filter('script_loader_src', array($this, 'remove_version_strings'), 9999);
        
        // Disable XML-RPC if not needed
        if (!get_option('yht_enable_xmlrpc', false)) {
            add_filter('xmlrpc_enabled', '__return_false');
        }
        
        // Remove unnecessary WordPress headers
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');
        
        // Disable file editing in WordPress admin
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }
        
        // Hide login error messages
        add_filter('login_errors', array($this, 'hide_login_errors'));
        
        // Prevent direct access to PHP files
        add_action('init', array($this, 'prevent_direct_access'));
    }
    
    /**
     * Remove version strings from scripts and styles
     * 
     * @param string $src Script/style source
     * @return string
     */
    public function remove_version_strings($src) {
        if (strpos($src, 'ver=')) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }
    
    /**
     * Hide login error messages
     * 
     * @return string
     */
    public function hide_login_errors() {
        return __('Something is wrong! Please try again.', 'your-hidden-trip');
    }
    
    /**
     * Prevent direct access to sensitive PHP files
     */
    public function prevent_direct_access() {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        // List of file patterns to block direct access
        $blocked_patterns = [
            '/wp-config\.php',
            '/wp-admin/includes/',
            '/wp-includes/.*\.php',
            '/\.htaccess',
            '/readme\.html',
            '/license\.txt'
        ];
        
        foreach ($blocked_patterns as $pattern) {
            if (preg_match($pattern, $request_uri)) {
                wp_die(__('Direct access not allowed.', 'your-hidden-trip'), 403);
            }
        }
    }
    
    /**
     * Get security scan results
     * 
     * @return array
     */
    public function get_security_scan_results() {
        $results = [];
        
        // Check for common security issues
        $results['wp_version_hidden'] = !has_action('wp_head', 'wp_generator');
        $results['file_editing_disabled'] = defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT;
        $results['xmlrpc_disabled'] = !apply_filters('xmlrpc_enabled', true);
        $results['ssl_enabled'] = is_ssl();
        $results['debug_disabled'] = !defined('WP_DEBUG') || !WP_DEBUG;
        
        // Check file permissions
        $results['wp_config_secure'] = $this->check_file_permissions(ABSPATH . 'wp-config.php', 0600);
        $results['htaccess_secure'] = $this->check_file_permissions(ABSPATH . '.htaccess', 0644);
        
        // Check for suspicious files
        $results['no_suspicious_files'] = $this->scan_for_suspicious_files();
        
        return $results;
    }
    
    /**
     * Check file permissions
     * 
     * @param string $file File path
     * @param int    $expected_permissions Expected permissions
     * @return bool
     */
    private function check_file_permissions($file, $expected_permissions) {
        if (!file_exists($file)) {
            return true; // File doesn't exist, so it's secure
        }
        
        $current_permissions = fileperms($file) & 0777;
        return $current_permissions <= $expected_permissions;
    }
    
    /**
     * Scan for suspicious files
     * 
     * @return bool
     */
    private function scan_for_suspicious_files() {
        $suspicious_patterns = [
            '*.php.suspected',
            '*.php.bak',
            '*shell*.php',
            '*backdoor*.php',
            'c99.php',
            'r57.php'
        ];
        
        foreach ($suspicious_patterns as $pattern) {
            $files = glob(ABSPATH . $pattern);
            if (!empty($files)) {
                // Log suspicious file found
                if (class_exists('YHT_Logger')) {
                    YHT_Logger::get_instance()->warning('Suspicious file found', [
                        'files' => $files,
                        'pattern' => $pattern
                    ]);
                }
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Generate security report
     * 
     * @return array
     */
    public function generate_security_report() {
        $scan_results = $this->get_security_scan_results();
        $total_checks = count($scan_results);
        $passed_checks = count(array_filter($scan_results));
        $security_score = ($passed_checks / $total_checks) * 100;
        
        return [
            'score' => round($security_score, 2),
            'passed' => $passed_checks,
            'total' => $total_checks,
            'results' => $scan_results,
            'recommendations' => $this->get_security_recommendations($scan_results)
        ];
    }
    
    /**
     * Get security recommendations based on scan results
     * 
     * @param array $scan_results
     * @return array
     */
    private function get_security_recommendations($scan_results) {
        $recommendations = [];
        
        if (!$scan_results['wp_version_hidden']) {
            $recommendations[] = __('Hide WordPress version information', 'your-hidden-trip');
        }
        
        if (!$scan_results['file_editing_disabled']) {
            $recommendations[] = __('Disable file editing in WordPress admin', 'your-hidden-trip');
        }
        
        if (!$scan_results['ssl_enabled']) {
            $recommendations[] = __('Enable SSL/HTTPS for your website', 'your-hidden-trip');
        }
        
        if (!$scan_results['debug_disabled']) {
            $recommendations[] = __('Disable debug mode in production', 'your-hidden-trip');
        }
        
        if (!$scan_results['wp_config_secure']) {
            $recommendations[] = __('Secure wp-config.php file permissions', 'your-hidden-trip');
        }
        
        if (!$scan_results['no_suspicious_files']) {
            $recommendations[] = __('Remove suspicious files from the server', 'your-hidden-trip');
        }
        
        return $recommendations;
    }
}