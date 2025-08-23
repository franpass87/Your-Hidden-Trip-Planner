<?php
/**
 * Base AJAX handler trait for YHT plugin
 * Provides consistent security and error handling for AJAX requests
 */

if (!defined('ABSPATH')) exit;

trait YHT_AJAX_Handler {
    
    /**
     * Validate AJAX request with nonce and capability check
     * @param string $nonce_action Nonce action to verify
     * @param string $capability Required capability (default: manage_options)
     * @param string $nonce_key Request key for nonce (default: nonce)
     * @throws Exception If validation fails
     */
    protected function validate_ajax_request($nonce_action = 'yht_system_nonce', $capability = 'manage_options', $nonce_key = 'nonce') {
        // Verify nonce
        if (!YHT_Validators::nonce($nonce_action, $nonce_key)) {
            throw new Exception(__('Invalid security token.', 'your-hidden-trip'));
        }
        
        // Check user capability
        if (!YHT_Validators::user_capability($capability)) {
            throw new Exception(__('Insufficient permissions.', 'your-hidden-trip'));
        }
    }
    
    /**
     * Send standardized success response
     * @param mixed $data Data to send
     * @param string $message Success message
     */
    protected function ajax_success($data = null, $message = '') {
        $response = array('success' => true);
        
        if (!empty($message)) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * Send standardized error response
     * @param string $message Error message
     * @param mixed $data Additional error data
     * @param int $code Error code
     */
    protected function ajax_error($message, $data = null, $code = 400) {
        $response = array(
            'success' => false,
            'message' => $message,
            'code' => $code
        );
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        // Log error for debugging
        error_log(sprintf('YHT AJAX Error [%d]: %s', $code, $message));
        
        wp_send_json_error($response, $code);
    }
    
    /**
     * Handle AJAX request with automatic error handling
     * @param callable $handler Function to execute
     * @param string $nonce_action Nonce action to verify
     * @param string $capability Required capability
     */
    protected function handle_ajax_request($handler, $nonce_action = 'yht_system_nonce', $capability = 'manage_options') {
        try {
            $this->validate_ajax_request($nonce_action, $capability);
            $result = call_user_func($handler);
            
            if (is_array($result) && isset($result['success'])) {
                // Handler returned standardized response
                if ($result['success']) {
                    $this->ajax_success($result['data'] ?? null, $result['message'] ?? '');
                } else {
                    $this->ajax_error($result['message'] ?? 'Unknown error', $result['data'] ?? null, $result['code'] ?? 400);
                }
            } else {
                // Handler returned raw data
                $this->ajax_success($result);
            }
        } catch (Exception $e) {
            $this->ajax_error($e->getMessage(), null, 500);
        }
    }
    
    /**
     * Get sanitized POST data
     * @param string $key Data key
     * @param mixed $default Default value if key not found
     * @param string $sanitizer Sanitization method (text, email, url, int, float, bool)
     * @return mixed Sanitized value
     */
    protected function get_post_data($key, $default = null, $sanitizer = 'text') {
        if (!isset($_POST[$key])) {
            return $default;
        }
        
        $value = $_POST[$key];
        
        switch ($sanitizer) {
            case 'text':
                return sanitize_text_field($value);
            case 'email':
                return sanitize_email($value);
            case 'url':
                return esc_url_raw($value);
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'array':
                return is_array($value) ? array_map('sanitize_text_field', $value) : $default;
            case 'json':
                return YHT_Validators::json($value) ?: $default;
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Rate limiting for AJAX requests
     * @param string $action Action identifier
     * @param int $limit Maximum requests per time window
     * @param int $window Time window in seconds
     * @return bool True if request allowed, false if rate limited
     */
    protected function check_rate_limit($action, $limit = 10, $window = 60) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user_id = get_current_user_id();
        $transient_key = "yht_rate_limit_{$user_id}_{$action}";
        
        $requests = get_transient($transient_key);
        if ($requests === false) {
            // First request in window
            set_transient($transient_key, 1, $window);
            return true;
        }
        
        if ($requests >= $limit) {
            return false;
        }
        
        // Increment counter
        set_transient($transient_key, $requests + 1, $window);
        return true;
    }
}