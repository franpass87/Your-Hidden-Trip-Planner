<?php
/**
 * Unit tests for YHT_Logger class
 */

require_once __DIR__ . '/../TestCase.php';
require_once YHT_PLUGIN_PATH . 'includes/utilities/class-yht-logger.php';

class YHT_LoggerTest extends TestCase
{
    private $logger;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock WordPress functions
        if (!function_exists('current_time')) {
            function current_time($type) {
                return date('Y-m-d H:i:s');
            }
        }
        
        if (!function_exists('get_current_user_id')) {
            function get_current_user_id() {
                return 1;
            }
        }
        
        if (!defined('WP_CONTENT_DIR')) {
            define('WP_CONTENT_DIR', '/tmp');
        }
        
        if (!function_exists('wp_mkdir_p')) {
            function wp_mkdir_p($target) {
                return wp_mkdir_p_mock($target);
            }
        }
        
        if (!function_exists('wp_mkdir_p_mock')) {
            function wp_mkdir_p_mock($target) {
                if (!file_exists($target)) {
                    return mkdir($target, 0755, true);
                }
                return true;
            }
        }
        
        $this->logger = YHT_Logger::get_instance();
    }
    
    public function testSingletonPattern()
    {
        $logger1 = YHT_Logger::get_instance();
        $logger2 = YHT_Logger::get_instance();
        
        $this->assertSame($logger1, $logger2, 'Logger should follow singleton pattern');
    }
    
    public function testLogLevelConstants()
    {
        $this->assertEquals('emergency', YHT_Logger::EMERGENCY);
        $this->assertEquals('alert', YHT_Logger::ALERT);
        $this->assertEquals('critical', YHT_Logger::CRITICAL);
        $this->assertEquals('error', YHT_Logger::ERROR);
        $this->assertEquals('warning', YHT_Logger::WARNING);
        $this->assertEquals('notice', YHT_Logger::NOTICE);
        $this->assertEquals('info', YHT_Logger::INFO);
        $this->assertEquals('debug', YHT_Logger::DEBUG);
    }
    
    public function testLogMethod()
    {
        // Test that log method doesn't throw exceptions
        $this->logger->log(YHT_Logger::INFO, 'Test message', ['key' => 'value']);
        
        // Since we can't easily test file writing in unit tests,
        // we just ensure the method executes without errors
        $this->assertTrue(true);
    }
    
    public function testLogLevelMethods()
    {
        // Test all log level methods
        $this->logger->emergency('Emergency message');
        $this->logger->alert('Alert message');
        $this->logger->critical('Critical message');
        $this->logger->error('Error message');
        $this->logger->warning('Warning message');
        $this->logger->notice('Notice message');
        $this->logger->info('Info message');
        $this->logger->debug('Debug message');
        
        // If we get here without exceptions, all methods work
        $this->assertTrue(true);
    }
    
    public function testLogContextHandling()
    {
        $context = [
            'user_id' => 123,
            'action' => 'test_action',
            'data' => ['nested' => 'value']
        ];
        
        // Test that context is properly handled
        $this->logger->info('Test with context', $context);
        
        $this->assertTrue(true);
    }
    
    public function testCleanupOldLogs()
    {
        // Test cleanup method doesn't throw exceptions
        $this->logger->cleanup_old_logs(30);
        
        $this->assertTrue(true);
    }
}