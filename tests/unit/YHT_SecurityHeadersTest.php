<?php
/**
 * Unit tests for YHT_Security_Headers class
 */

require_once __DIR__ . '/../TestCase.php';
require_once YHT_PLUGIN_PATH . 'includes/security/class-yht-security-headers.php';

class YHT_SecurityHeadersTest extends TestCase
{
    private $security_headers;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock WordPress functions
        if (!function_exists('wp_doing_ajax')) {
            function wp_doing_ajax() {
                return false;
            }
        }
        
        if (!function_exists('wp_doing_cron')) {
            function wp_doing_cron() {
                return false;
            }
        }
        
        if (!function_exists('is_ssl')) {
            function is_ssl() {
                return true;
            }
        }
        
        if (!function_exists('wp_create_nonce')) {
            function wp_create_nonce($action) {
                return 'test_nonce_' . $action;
            }
        }
        
        if (!function_exists('home_url')) {
            function home_url($path = '') {
                return 'https://example.com' . $path;
            }
        }
        
        if (!function_exists('apply_filters')) {
            function apply_filters($hook, $value) {
                return $value;
            }
        }
        
        if (!function_exists('has_action')) {
            function has_action($hook, $callback = false) {
                return false;
            }
        }
        
        if (!function_exists('fileperms')) {
            function fileperms($file) {
                return 0644;
            }
        }
        
        if (!function_exists('file_exists')) {
            function file_exists($file) {
                return true;
            }
        }
        
        if (!function_exists('glob')) {
            function glob($pattern) {
                return [];
            }
        }
        
        // Set up required constants
        if (!defined('ABSPATH')) {
            define('ABSPATH', '/tmp/');
        }
        
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }
        
        if (!defined('WP_DEBUG')) {
            define('WP_DEBUG', false);
        }
        
        $this->security_headers = new YHT_Security_Headers();
    }
    
    public function testSecurityScanResults()
    {
        $results = $this->security_headers->get_security_scan_results();
        
        $this->assertIsArray($results, 'Security scan should return an array');
        
        // Check for expected keys
        $expected_keys = [
            'wp_version_hidden',
            'file_editing_disabled',
            'xmlrpc_disabled',
            'ssl_enabled',
            'debug_disabled',
            'wp_config_secure',
            'htaccess_secure',
            'no_suspicious_files'
        ];
        
        foreach ($expected_keys as $key) {
            $this->assertArrayHasKey($key, $results, "Results should contain key: {$key}");
            $this->assertIsBool($results[$key], "Result for {$key} should be boolean");
        }
    }
    
    public function testSecurityReport()
    {
        $report = $this->security_headers->generate_security_report();
        
        $this->assertIsArray($report, 'Security report should return an array');
        
        // Check for expected structure
        $this->assertArrayHasKey('score', $report);
        $this->assertArrayHasKey('passed', $report);
        $this->assertArrayHasKey('total', $report);
        $this->assertArrayHasKey('results', $report);
        $this->assertArrayHasKey('recommendations', $report);
        
        // Check data types
        $this->assertIsFloat($report['score']);
        $this->assertIsInt($report['passed']);
        $this->assertIsInt($report['total']);
        $this->assertIsArray($report['results']);
        $this->assertIsArray($report['recommendations']);
        
        // Check score range
        $this->assertGreaterThanOrEqual(0, $report['score']);
        $this->assertLessThanOrEqual(100, $report['score']);
    }
    
    public function testRemoveVersionStrings()
    {
        $src_with_version = 'https://example.com/style.css?ver=6.3.0';
        $src_without_version = 'https://example.com/style.css';
        
        $filtered_src = $this->security_headers->remove_version_strings($src_with_version);
        
        $this->assertEquals($src_without_version, $filtered_src, 'Version string should be removed');
        
        // Test with already clean URL
        $already_clean = $this->security_headers->remove_version_strings($src_without_version);
        $this->assertEquals($src_without_version, $already_clean, 'Clean URL should remain unchanged');
    }
    
    public function testHideLoginErrors()
    {
        $hidden_message = $this->security_headers->hide_login_errors();
        
        $this->assertIsString($hidden_message, 'Should return a string');
        $this->assertNotEmpty($hidden_message, 'Should not return empty string');
        
        // Should not contain specific error information
        $this->assertStringNotContainsString('username', strtolower($hidden_message));
        $this->assertStringNotContainsString('password', strtolower($hidden_message));
    }
    
    public function testSecurityRecommendations()
    {
        // Create a scenario with some failing checks
        $failing_results = [
            'wp_version_hidden' => false,
            'file_editing_disabled' => true,
            'xmlrpc_disabled' => true,
            'ssl_enabled' => false,
            'debug_disabled' => true,
            'wp_config_secure' => true,
            'htaccess_secure' => true,
            'no_suspicious_files' => true
        ];
        
        // Use reflection to test private method
        $reflection = new ReflectionClass($this->security_headers);
        $method = $reflection->getMethod('get_security_recommendations');
        $method->setAccessible(true);
        
        $recommendations = $method->invoke($this->security_headers, $failing_results);
        
        $this->assertIsArray($recommendations);
        $this->assertGreaterThan(0, count($recommendations), 'Should have recommendations for failing checks');
    }
}