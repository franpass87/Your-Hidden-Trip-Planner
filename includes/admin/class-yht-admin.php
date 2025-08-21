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
        
        add_submenu_page(
            'yht_admin',
            'Gestione Prenotazioni',
            'Prenotazioni',
            'manage_woocommerce',
            'yht_bookings',
            array($this, 'bookings_page')
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
    
    /**
     * Bookings management page
     */
    public function bookings_page() {
        // Get all bookings
        $bookings = get_posts(array(
            'post_type' => 'yht_booking',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        echo '<div class="wrap">';
        echo '<h1>Gestione Prenotazioni</h1>';
        
        if (empty($bookings)) {
            echo '<p>Nessuna prenotazione trovata.</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>Riferimento</th>';
            echo '<th>Cliente</th>';
            echo '<th>Tour</th>';
            echo '<th>Data Viaggio</th>';
            echo '<th>Pax</th>';
            echo '<th>Totale</th>';
            echo '<th>Stato</th>';
            echo '<th>Data Prenotazione</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            foreach ($bookings as $booking) {
                $ref = get_post_meta($booking->ID, 'yht_booking_reference', true);
                $customer_name = get_post_meta($booking->ID, 'yht_customer_name', true);
                $customer_email = get_post_meta($booking->ID, 'yht_customer_email', true);
                $travel_date = get_post_meta($booking->ID, 'yht_travel_date', true);
                $num_pax = get_post_meta($booking->ID, 'yht_num_pax', true);
                $total_price = get_post_meta($booking->ID, 'yht_total_price', true);
                $status = get_post_meta($booking->ID, 'yht_booking_status', true);
                $package_type = get_post_meta($booking->ID, 'yht_package_type', true);
                
                $itinerary = json_decode(get_post_meta($booking->ID, 'yht_itinerary_json', true), true);
                $tour_name = $itinerary['name'] ?? 'N/D';
                
                $status_labels = array(
                    'pending_payment' => '<span style="color:#f59e0b;">🔄 In attesa pagamento</span>',
                    'confirmed' => '<span style="color:#10b981;">✅ Confermata</span>',
                    'cancelled' => '<span style="color:#ef4444;">❌ Cancellata</span>',
                    'completed' => '<span style="color:#6366f1;">🎉 Completata</span>'
                );
                
                echo '<tr>';
                echo '<td><strong>' . esc_html($ref) . '</strong></td>';
                echo '<td>' . esc_html($customer_name) . '<br><small>' . esc_html($customer_email) . '</small></td>';
                echo '<td>' . esc_html($tour_name) . '<br><small>' . ucfirst($package_type) . '</small></td>';
                echo '<td>' . esc_html($travel_date) . '</td>';
                echo '<td>' . esc_html($num_pax) . '</td>';
                echo '<td>€' . esc_html($total_price) . '</td>';
                echo '<td>' . ($status_labels[$status] ?? $status) . '</td>';
                echo '<td>' . get_the_date('d/m/Y H:i', $booking->ID) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        }
        
        echo '</div>';
    }
}