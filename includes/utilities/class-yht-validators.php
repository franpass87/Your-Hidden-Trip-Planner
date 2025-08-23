<?php
/**
 * Input validation utilities for YHT plugin
 */

if (!defined('ABSPATH')) exit;

class YHT_Validators {
    
    /**
     * Validate email address
     * @param string $email Email to validate
     * @return bool|string Returns sanitized email or false if invalid
     */
    public static function email($email) {
        if (empty($email)) {
            return false;
        }
        
        $sanitized = sanitize_email($email);
        return is_email($sanitized) ? $sanitized : false;
    }
    
    /**
     * Validate URL
     * @param string $url URL to validate
     * @return bool|string Returns sanitized URL or false if invalid
     */
    public static function url($url) {
        if (empty($url)) {
            return false;
        }
        
        $sanitized = esc_url_raw($url);
        return filter_var($sanitized, FILTER_VALIDATE_URL) ? $sanitized : false;
    }
    
    /**
     * Validate API key based on provider
     * @param string $key API key to validate
     * @param string $provider API provider name
     * @return bool|string Returns sanitized key or false if invalid
     */
    public static function api_key($key, $provider) {
        if (empty($key)) {
            return false;
        }
        
        $sanitized = sanitize_text_field($key);
        
        switch ($provider) {
            case 'stripe':
                // Stripe keys start with sk_ or pk_
                return (strpos($sanitized, 'sk_') === 0 || strpos($sanitized, 'pk_') === 0) ? $sanitized : false;
            
            case 'mailchimp':
                // Mailchimp keys are typically 32 chars followed by region
                return preg_match('/^[a-f0-9]{32}-[a-z]{2,4}[0-9]+$/', $sanitized) ? $sanitized : false;
            
            case 'google_analytics':
                // GA4 Measurement IDs format: G-XXXXXXXXXX
                return preg_match('/^G-[A-Z0-9]{10}$/', $sanitized) ? $sanitized : false;
            
            case 'hubspot':
                // HubSpot keys are typically UUID format or specific patterns
                return (strlen($sanitized) >= 20 && ctype_alnum(str_replace('-', '', $sanitized))) ? $sanitized : false;
            
            default:
                // Generic validation - non-empty alphanumeric with common special chars
                return preg_match('/^[a-zA-Z0-9_\-\.]+$/', $sanitized) ? $sanitized : false;
        }
    }
    
    /**
     * Validate WordPress capability
     * @param string $capability Capability to check
     * @return bool
     */
    public static function user_capability($capability = 'manage_options') {
        return current_user_can($capability);
    }
    
    /**
     * Validate nonce
     * @param string $nonce_action Action for the nonce
     * @param string $nonce_key Key for the nonce in $_REQUEST
     * @return bool
     */
    public static function nonce($nonce_action, $nonce_key = 'nonce') {
        return wp_verify_nonce($_REQUEST[$nonce_key] ?? '', $nonce_action);
    }
    
    /**
     * Validate and sanitize text input
     * @param string $input Input to validate
     * @param int $max_length Maximum allowed length
     * @return string|false Sanitized text or false if invalid
     */
    public static function text($input, $max_length = 255) {
        if (empty($input)) {
            return '';
        }
        
        $sanitized = sanitize_text_field($input);
        return (strlen($sanitized) <= $max_length) ? $sanitized : false;
    }
    
    /**
     * Validate coordinates (lat, lng)
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @return bool
     */
    public static function coordinates($lat, $lng) {
        return (is_numeric($lat) && is_numeric($lng) && 
                $lat >= -90 && $lat <= 90 && 
                $lng >= -180 && $lng <= 180);
    }
    
    /**
     * Validate date in Y-m-d format
     * @param string $date Date string
     * @return bool|string Returns valid date string or false
     */
    public static function date($date) {
        if (empty($date)) {
            return false;
        }
        
        $parsed = date_parse($date);
        if ($parsed === false || $parsed['error_count'] > 0) {
            return false;
        }
        
        return date('Y-m-d', strtotime($date));
    }
    
    /**
     * Validate JSON string
     * @param string $json JSON string to validate
     * @return bool|array Returns decoded array or false if invalid
     */
    public static function json($json) {
        if (empty($json)) {
            return false;
        }
        
        $decoded = json_decode($json, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : false;
    }
}