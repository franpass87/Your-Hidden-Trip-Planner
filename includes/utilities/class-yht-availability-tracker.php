<?php
/**
 * Real-time Availability Tracking System
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_Availability_Tracker {
    
    public function __construct() {
        add_action('init', array($this, 'init_availability_system'));
        add_action('wp_ajax_yht_update_availability', array($this, 'update_availability'));
        add_action('wp_ajax_nopriv_yht_check_availability', array($this, 'check_availability_public'));
        add_action('wp_ajax_yht_check_availability', array($this, 'check_availability_public'));
        
        // Schedule availability checks
        add_action('yht_hourly_availability_check', array($this, 'run_availability_checks'));
        
        // Hook into booking events
        add_action('yht_booking_confirmed', array($this, 'update_entity_availability'));
        add_action('yht_booking_cancelled', array($this, 'restore_entity_availability'));
    }
    
    /**
     * Initialize availability system
     */
    public function init_availability_system() {
        // Schedule hourly availability checks if not already scheduled
        if (!wp_next_scheduled('yht_hourly_availability_check')) {
            wp_schedule_event(time(), 'hourly', 'yht_hourly_availability_check');
        }
        
        // Create availability tracking table if it doesn't exist
        $this->create_availability_table();
    }
    
    /**
     * Create availability tracking table
     */
    private function create_availability_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'yht_availability';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            entity_id bigint(20) NOT NULL,
            entity_type varchar(50) NOT NULL,
            date_from date NOT NULL,
            date_to date NOT NULL,
            available_slots int(11) DEFAULT 1,
            booked_slots int(11) DEFAULT 0,
            blocked_slots int(11) DEFAULT 0,
            price_multiplier decimal(5,2) DEFAULT 1.00,
            notes text,
            last_updated timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY entity_id (entity_id),
            KEY entity_type (entity_type),
            KEY date_range (date_from, date_to),
            UNIQUE KEY unique_entity_date (entity_id, entity_type, date_from, date_to)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Update availability for an entity
     */
    public function update_availability() {
        check_ajax_referer('yht_availability_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        
        $entity_id = (int)$_POST['entity_id'];
        $entity_type = sanitize_text_field($_POST['entity_type']);
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        $available_slots = (int)$_POST['available_slots'];
        $price_multiplier = (float)$_POST['price_multiplier'];
        $notes = sanitize_textarea_field($_POST['notes']);
        
        $result = $this->set_entity_availability($entity_id, $entity_type, $date_from, $date_to, $available_slots, $price_multiplier, $notes);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Disponibilità aggiornata con successo',
                'availability' => $this->get_entity_availability($entity_id, $entity_type, $date_from, $date_to)
            ));
        } else {
            wp_send_json_error('Errore nell\'aggiornamento della disponibilità');
        }
    }
    
    /**
     * Check availability (public endpoint)
     */
    public function check_availability_public() {
        $entity_ids = array_map('intval', $_POST['entity_ids'] ?? array());
        $date_from = sanitize_text_field($_POST['date_from'] ?? '');
        $date_to = sanitize_text_field($_POST['date_to'] ?? '');
        
        if (empty($entity_ids) || empty($date_from) || empty($date_to)) {
            wp_send_json_error('Parametri mancanti');
        }
        
        $availability_results = array();
        
        foreach ($entity_ids as $entity_id) {
            $entity_post = get_post($entity_id);
            if (!$entity_post) continue;
            
            $entity_type = str_replace('yht_', '', $entity_post->post_type);
            $availability = $this->check_entity_availability($entity_id, $entity_type, $date_from, $date_to);
            
            $availability_results[$entity_id] = array(
                'entity_id' => $entity_id,
                'entity_name' => $entity_post->post_title,
                'entity_type' => $entity_type,
                'available' => $availability['available'],
                'available_slots' => $availability['available_slots'],
                'total_slots' => $availability['total_slots'],
                'price_multiplier' => $availability['price_multiplier'],
                'availability_score' => $this->calculate_availability_score($availability),
                'risk_level' => $this->assess_risk_level($availability),
                'alternative_dates' => $this->suggest_alternative_dates($entity_id, $entity_type, $date_from, $date_to)
            );
        }
        
        wp_send_json_success(array(
            'availability' => $availability_results,
            'checked_at' => current_time('c'),
            'recommendations' => $this->generate_availability_recommendations($availability_results)
        ));
    }
    
    /**
     * Set entity availability
     */
    public function set_entity_availability($entity_id, $entity_type, $date_from, $date_to, $available_slots = 1, $price_multiplier = 1.0, $notes = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'yht_availability';
        
        return $wpdb->replace(
            $table_name,
            array(
                'entity_id' => $entity_id,
                'entity_type' => $entity_type,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'available_slots' => $available_slots,
                'price_multiplier' => $price_multiplier,
                'notes' => $notes
            ),
            array('%d', '%s', '%s', '%s', '%d', '%f', '%s')
        );
    }
    
    /**
     * Check entity availability
     */
    public function check_entity_availability($entity_id, $entity_type, $date_from, $date_to) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'yht_availability';
        
        // Get availability records that overlap with the requested period
        $availability_records = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE entity_id = %d 
             AND entity_type = %s 
             AND date_from <= %s 
             AND date_to >= %s
             ORDER BY date_from",
            $entity_id, $entity_type, $date_to, $date_from
        ));
        
        if (empty($availability_records)) {
            // No specific availability record, check default entity settings
            return $this->get_default_availability($entity_id, $entity_type);
        }
        
        // Calculate availability across the date range
        $total_slots = 0;
        $available_slots = 0;
        $booked_slots = 0;
        $avg_price_multiplier = 1.0;
        $multiplier_count = 0;
        
        foreach ($availability_records as $record) {
            $total_slots += $record->available_slots;
            $available_slots += ($record->available_slots - $record->booked_slots - $record->blocked_slots);
            $booked_slots += $record->booked_slots;
            
            if ($record->price_multiplier > 0) {
                $avg_price_multiplier += $record->price_multiplier;
                $multiplier_count++;
            }
        }
        
        if ($multiplier_count > 0) {
            $avg_price_multiplier = $avg_price_multiplier / $multiplier_count;
        }
        
        return array(
            'available' => $available_slots > 0,
            'total_slots' => $total_slots,
            'available_slots' => max(0, $available_slots),
            'booked_slots' => $booked_slots,
            'price_multiplier' => $avg_price_multiplier,
            'records' => $availability_records
        );
    }
    
    /**
     * Get default availability for entity
     */
    private function get_default_availability($entity_id, $entity_type) {
        // Default availability based on entity type
        $default_slots = 1;
        
        switch ($entity_type) {
            case 'alloggio':
                $default_slots = (int)get_post_meta($entity_id, 'yht_max_guests', true) ?: 4;
                break;
            case 'servizio':
                $default_slots = (int)get_post_meta($entity_id, 'yht_max_participants', true) ?: 10;
                break;
            case 'luogo':
                $default_slots = 50; // High capacity for attractions
                break;
        }
        
        return array(
            'available' => true,
            'total_slots' => $default_slots,
            'available_slots' => $default_slots,
            'booked_slots' => 0,
            'price_multiplier' => 1.0,
            'is_default' => true
        );
    }
    
    /**
     * Update entity availability when booking is confirmed
     */
    public function update_entity_availability($booking_data) {
        if (!isset($booking_data['entity_selections']) || !isset($booking_data['date_range'])) {
            return;
        }
        
        $date_from = $booking_data['date_range']['from'];
        $date_to = $booking_data['date_range']['to'];
        $participants = (int)($booking_data['participants'] ?? 1);
        
        foreach ($booking_data['entity_selections'] as $day => $entities) {
            foreach ($entities as $category => $entity_data) {
                $entity_id = $entity_data['entity_id'];
                $entity_type = $category;
                
                $this->reduce_available_slots($entity_id, $entity_type, $date_from, $date_to, $participants);
            }
        }
    }
    
    /**
     * Restore entity availability when booking is cancelled
     */
    public function restore_entity_availability($booking_data) {
        if (!isset($booking_data['entity_selections']) || !isset($booking_data['date_range'])) {
            return;
        }
        
        $date_from = $booking_data['date_range']['from'];
        $date_to = $booking_data['date_range']['to'];
        $participants = (int)($booking_data['participants'] ?? 1);
        
        foreach ($booking_data['entity_selections'] as $day => $entities) {
            foreach ($entities as $category => $entity_data) {
                $entity_id = $entity_data['entity_id'];
                $entity_type = $category;
                
                $this->increase_available_slots($entity_id, $entity_type, $date_from, $date_to, $participants);
            }
        }
    }
    
    /**
     * Reduce available slots for entity
     */
    private function reduce_available_slots($entity_id, $entity_type, $date_from, $date_to, $slots_needed) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'yht_availability';
        
        // Update existing records or create new one
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE entity_id = %d AND entity_type = %s AND date_from = %s AND date_to = %s",
            $entity_id, $entity_type, $date_from, $date_to
        ));
        
        if ($existing) {
            $wpdb->update(
                $table_name,
                array('booked_slots' => $existing->booked_slots + $slots_needed),
                array('id' => $existing->id),
                array('%d'),
                array('%d')
            );
        } else {
            // Create new availability record
            $default_availability = $this->get_default_availability($entity_id, $entity_type);
            $this->set_entity_availability(
                $entity_id, 
                $entity_type, 
                $date_from, 
                $date_to, 
                $default_availability['total_slots'], 
                1.0, 
                'Auto-created from booking'
            );
            
            // Now update with booked slots
            $wpdb->update(
                $table_name,
                array('booked_slots' => $slots_needed),
                array(
                    'entity_id' => $entity_id,
                    'entity_type' => $entity_type,
                    'date_from' => $date_from,
                    'date_to' => $date_to
                ),
                array('%d'),
                array('%d', '%s', '%s', '%s')
            );
        }
    }
    
    /**
     * Increase available slots for entity
     */
    private function increase_available_slots($entity_id, $entity_type, $date_from, $date_to, $slots_to_restore) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'yht_availability';
        
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name 
             SET booked_slots = GREATEST(0, booked_slots - %d)
             WHERE entity_id = %d AND entity_type = %s AND date_from = %s AND date_to = %s",
            $slots_to_restore, $entity_id, $entity_type, $date_from, $date_to
        ));
    }
    
    /**
     * Calculate availability score (0-100)
     */
    private function calculate_availability_score($availability) {
        if (!$availability['available']) {
            return 0;
        }
        
        $total_slots = $availability['total_slots'];
        $available_slots = $availability['available_slots'];
        
        if ($total_slots == 0) {
            return 100;
        }
        
        $percentage = ($available_slots / $total_slots) * 100;
        
        // Adjust score based on absolute availability
        if ($available_slots >= 10) {
            return min(100, $percentage + 10); // Bonus for high availability
        } elseif ($available_slots <= 2) {
            return max(0, $percentage - 20); // Penalty for low availability
        }
        
        return round($percentage);
    }
    
    /**
     * Assess risk level for booking
     */
    private function assess_risk_level($availability) {
        $score = $this->calculate_availability_score($availability);
        
        if ($score >= 80) return 'low';
        if ($score >= 50) return 'medium';
        if ($score >= 20) return 'high';
        return 'critical';
    }
    
    /**
     * Suggest alternative dates
     */
    private function suggest_alternative_dates($entity_id, $entity_type, $original_from, $original_to) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'yht_availability';
        
        // Look for dates within 2 weeks before/after with better availability
        $search_from = date('Y-m-d', strtotime($original_from . ' -14 days'));
        $search_to = date('Y-m-d', strtotime($original_to . ' +14 days'));
        
        $alternatives = $wpdb->get_results($wpdb->prepare(
            "SELECT date_from, date_to, 
                    (available_slots - booked_slots - blocked_slots) as free_slots,
                    price_multiplier
             FROM $table_name 
             WHERE entity_id = %d 
             AND entity_type = %s 
             AND date_from >= %s 
             AND date_to <= %s
             AND (available_slots - booked_slots - blocked_slots) > 0
             ORDER BY free_slots DESC, price_multiplier ASC
             LIMIT 5",
            $entity_id, $entity_type, $search_from, $search_to
        ));
        
        return array_map(function($alt) {
            return array(
                'date_from' => $alt->date_from,
                'date_to' => $alt->date_to,
                'available_slots' => $alt->free_slots,
                'price_multiplier' => $alt->price_multiplier
            );
        }, $alternatives);
    }
    
    /**
     * Generate availability recommendations
     */
    private function generate_availability_recommendations($availability_results) {
        $recommendations = array();
        $total_entities = count($availability_results);
        $available_count = 0;
        $high_risk_count = 0;
        
        foreach ($availability_results as $result) {
            if ($result['available']) {
                $available_count++;
            }
            if ($result['risk_level'] === 'high' || $result['risk_level'] === 'critical') {
                $high_risk_count++;
            }
        }
        
        $availability_percentage = ($available_count / $total_entities) * 100;
        
        if ($availability_percentage == 100) {
            $recommendations[] = array(
                'type' => 'success',
                'message' => '✅ Disponibilità Eccellente: Tutte le opzioni sono disponibili!'
            );
        } elseif ($availability_percentage >= 80) {
            $recommendations[] = array(
                'type' => 'success',
                'message' => '✅ Buona Disponibilità: La maggior parte delle opzioni è disponibile.'
            );
        } elseif ($availability_percentage >= 50) {
            $recommendations[] = array(
                'type' => 'warning',
                'message' => '⚠️ Disponibilità Limitata: Considera date alternative o opzioni aggiuntive.'
            );
        } else {
            $recommendations[] = array(
                'type' => 'error',
                'message' => '❌ Bassa Disponibilità: Prenotazione difficile. Contatta il team per assistenza.'
            );
        }
        
        if ($high_risk_count > 0) {
            $recommendations[] = array(
                'type' => 'warning',
                'message' => "⚡ Attenzione: {$high_risk_count} opzioni hanno disponibilità limitata."
            );
        }
        
        return $recommendations;
    }
    
    /**
     * Run scheduled availability checks
     */
    public function run_availability_checks() {
        // Check for expired availability records
        $this->cleanup_expired_records();
        
        // Update dynamic pricing based on availability
        $this->update_dynamic_pricing();
        
        // Send availability alerts if needed
        $this->send_availability_alerts();
    }
    
    /**
     * Cleanup expired availability records
     */
    private function cleanup_expired_records() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'yht_availability';
        
        // Remove records older than 1 year
        $wpdb->query(
            "DELETE FROM $table_name WHERE date_to < DATE_SUB(CURDATE(), INTERVAL 1 YEAR)"
        );
    }
    
    /**
     * Update dynamic pricing based on availability
     */
    private function update_dynamic_pricing() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'yht_availability';
        
        // Increase price multiplier for low availability
        $wpdb->query(
            "UPDATE $table_name 
             SET price_multiplier = CASE 
                 WHEN (available_slots - booked_slots - blocked_slots) <= 1 THEN 1.5
                 WHEN (available_slots - booked_slots - blocked_slots) <= 3 THEN 1.3
                 WHEN (available_slots - booked_slots - blocked_slots) <= 5 THEN 1.1
                 ELSE 1.0
             END
             WHERE date_from >= CURDATE()"
        );
    }
    
    /**
     * Send availability alerts
     */
    private function send_availability_alerts() {
        // This would send alerts to admins about low availability
        // Implementation would depend on specific requirements
    }
    
    /**
     * Get entity availability for date range
     */
    public function get_entity_availability($entity_id, $entity_type, $date_from, $date_to) {
        return $this->check_entity_availability($entity_id, $entity_type, $date_from, $date_to);
    }
}

// Initialize availability tracker
new YHT_Availability_Tracker();