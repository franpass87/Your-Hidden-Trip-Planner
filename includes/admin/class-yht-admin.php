<?php
/**
 * Handle Admin functionality
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            'Your Hidden Trip', 
            'Your Hidden Trip', 
            'manage_options', 
            'yht_admin', 
            array($this, 'settings_page'), 
            'dashicons-admin-site', 
            58
        );
        
        add_submenu_page(
            'yht_admin',
            'Impostazioni',
            'Impostazioni',
            'manage_options',
            'yht_admin',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'yht_admin',
            'Importer CSV',
            'Importer CSV',
            'manage_options',
            'yht_import',
            array($this, 'importer_page')
        );
    }
    
    /**
     * Settings page callback
     */
    public function settings_page() {
        require_once YHT_PLUGIN_PATH . 'includes/admin/class-yht-settings.php';
        $settings_handler = new YHT_Settings();
        $settings_handler->render_page();
    }
    
    /**
     * Importer page callback
     */
    public function importer_page() {
        require_once YHT_PLUGIN_PATH . 'includes/admin/class-yht-importer.php';
        $importer = new YHT_Importer();
        $importer->render_page();
    }
}