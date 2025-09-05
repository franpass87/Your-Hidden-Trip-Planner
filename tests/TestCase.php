<?php
/**
 * Base test case for Your Hidden Trip Planner tests
 */

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    /**
     * Setup before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset any global state
        $this->resetGlobalState();
    }
    
    /**
     * Cleanup after each test
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Reset any global state
        $this->resetGlobalState();
    }
    
    /**
     * Reset global state between tests
     */
    private function resetGlobalState()
    {
        // Clear any global variables or state that might affect tests
        if (isset($GLOBALS['yht_test_options'])) {
            unset($GLOBALS['yht_test_options']);
        }
    }
    
    /**
     * Mock WordPress option functions
     */
    protected function mockWordPressOptions($options = [])
    {
        $GLOBALS['yht_test_options'] = $options;
        
        // Override get_option function
        if (!function_exists('get_option_override')) {
            function get_option_override($option, $default = false) {
                return $GLOBALS['yht_test_options'][$option] ?? $default;
            }
        }
    }
    
    /**
     * Helper to create test data
     */
    protected function createTestData($type = 'luogo', $data = [])
    {
        $defaults = [
            'luogo' => [
                'ID' => 1,
                'post_title' => 'Test Location',
                'post_content' => 'Test location content',
                'post_type' => 'yht_luogo',
                'meta' => [
                    'yht_luogo_coordinates' => '42.4668,12.1056',
                    'yht_luogo_category' => 'natura',
                    'yht_luogo_price_per_pax' => '15.00'
                ]
            ],
            'tour' => [
                'ID' => 2,
                'post_title' => 'Test Tour',
                'post_content' => 'Test tour content',
                'post_type' => 'yht_tour',
                'meta' => [
                    'yht_tour_duration' => '3',
                    'yht_tour_difficulty' => 'easy',
                    'yht_tour_price' => '80.00'
                ]
            ]
        ];
        
        return array_merge($defaults[$type] ?? [], $data);
    }
    
    /**
     * Assert that a string contains valid JSON
     */
    protected function assertValidJson($string, $message = '')
    {
        json_decode($string);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), $message ?: 'String is not valid JSON');
    }
    
    /**
     * Assert that an array has the expected structure
     */
    protected function assertArrayStructure($expected, $actual, $message = '')
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $actual, $message ?: "Array should have key: {$key}");
            
            if (is_array($value)) {
                $this->assertArrayStructure($value, $actual[$key], $message);
            }
        }
    }
}