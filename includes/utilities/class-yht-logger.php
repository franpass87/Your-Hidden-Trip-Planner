<?php
/**
 * Structured logging system for Your Hidden Trip Planner
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_Logger {
    
    /**
     * Single instance of the logger
     * @var YHT_Logger
     */
    private static $instance = null;
    
    /**
     * Log levels
     */
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';
    
    /**
     * Log file path
     * @var string
     */
    private $log_file;
    
    /**
     * Whether logging is enabled
     * @var bool
     */
    private $enabled;
    
    /**
     * Get singleton instance
     * @return YHT_Logger
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor (singleton pattern)
     */
    private function __construct() {
        $this->enabled = defined('WP_DEBUG') && WP_DEBUG;
        $this->log_file = WP_CONTENT_DIR . '/yht-logs/yht-' . date('Y-m-d') . '.log';
        
        // Create log directory if it doesn't exist
        $log_dir = dirname($this->log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
    }
    
    /**
     * Log a message
     * 
     * @param string $level   Log level
     * @param string $message Log message
     * @param array  $context Additional context data
     */
    public function log($level, $message, $context = []) {
        if (!$this->enabled) {
            return;
        }
        
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'user_id' => get_current_user_id(),
            'ip' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ];
        
        // Add WordPress specific context
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen) {
                $log_entry['context']['wp_screen'] = $screen->id;
            }
        }
        
        // Format log entry
        $formatted_entry = $this->format_log_entry($log_entry);
        
        // Write to file
        $this->write_to_file($formatted_entry);
        
        // Send to WordPress error log for critical issues
        if (in_array($level, [self::EMERGENCY, self::ALERT, self::CRITICAL, self::ERROR])) {
            error_log("YHT_{$level}: {$message}");
        }
    }
    
    /**
     * Log emergency message
     */
    public function emergency($message, $context = []) {
        $this->log(self::EMERGENCY, $message, $context);
    }
    
    /**
     * Log alert message
     */
    public function alert($message, $context = []) {
        $this->log(self::ALERT, $message, $context);
    }
    
    /**
     * Log critical message
     */
    public function critical($message, $context = []) {
        $this->log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Log error message
     */
    public function error($message, $context = []) {
        $this->log(self::ERROR, $message, $context);
    }
    
    /**
     * Log warning message
     */
    public function warning($message, $context = []) {
        $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * Log notice message
     */
    public function notice($message, $context = []) {
        $this->log(self::NOTICE, $message, $context);
    }
    
    /**
     * Log info message
     */
    public function info($message, $context = []) {
        $this->log(self::INFO, $message, $context);
    }
    
    /**
     * Log debug message
     */
    public function debug($message, $context = []) {
        $this->log(self::DEBUG, $message, $context);
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
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
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Format log entry for writing
     * 
     * @param array $entry Log entry data
     * @return string
     */
    private function format_log_entry($entry) {
        return json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }
    
    /**
     * Write log entry to file
     * 
     * @param string $formatted_entry
     */
    private function write_to_file($formatted_entry) {
        if (is_writable(dirname($this->log_file))) {
            file_put_contents($this->log_file, $formatted_entry, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Clean up old log files
     * 
     * @param int $days Number of days to keep logs
     */
    public function cleanup_old_logs($days = 30) {
        $log_dir = dirname($this->log_file);
        $files = glob($log_dir . '/yht-*.log');
        
        $cutoff_time = time() - ($days * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get recent log entries
     * 
     * @param int    $limit Number of entries to return
     * @param string $level Filter by log level
     * @return array
     */
    public function get_recent_logs($limit = 100, $level = null) {
        if (!file_exists($this->log_file)) {
            return [];
        }
        
        $lines = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = [];
        
        // Get last $limit lines
        $lines = array_slice($lines, -$limit);
        
        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            
            if ($entry && (!$level || $entry['level'] === strtoupper($level))) {
                $logs[] = $entry;
            }
        }
        
        return array_reverse($logs);
    }
}