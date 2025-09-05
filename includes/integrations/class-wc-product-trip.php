<?php
/**
 * WooCommerce Trip Product Type
 * 
 * @package YourHiddenTrip
 * @version 6.3.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Trip Product Type class
 */
class WC_Product_Trip extends WC_Product {
    
    public function __construct($product = 0) {
        $this->product_type = 'trip';
        parent::__construct($product);
    }

    public function get_type() {
        return 'trip';
    }

    public function is_virtual() {
        return apply_filters('yht_trip_is_virtual', true, $this);
    }

    public function is_downloadable() {
        return false;
    }

    public function needs_shipping() {
        return apply_filters('yht_trip_needs_shipping', false, $this);
    }

    public function is_sold_individually() {
        return apply_filters('yht_trip_sold_individually', true, $this);
    }

    public function get_trip_data() {
        $trip_id = get_post_meta($this->get_id(), '_trip_id', true);
        if ($trip_id) {
            return array(
                'trip_id' => $trip_id,
                'duration' => get_post_meta($trip_id, '_trip_duration', true),
                'difficulty' => get_post_meta($trip_id, '_trip_difficulty', true),
                'max_participants' => get_post_meta($trip_id, '_trip_max_participants', true),
                'includes' => get_post_meta($trip_id, '_trip_includes', true),
                'excludes' => get_post_meta($trip_id, '_trip_excludes', true),
                'requirements' => get_post_meta($trip_id, '_trip_requirements', true),
                'meeting_point' => get_post_meta($trip_id, '_trip_meeting_point', true),
                'schedule' => get_post_meta($trip_id, '_trip_schedule', true)
            );
        }
        return array();
    }

    public function get_available_dates() {
        $trip_id = get_post_meta($this->get_id(), '_trip_id', true);
        if ($trip_id) {
            return get_post_meta($trip_id, '_trip_available_dates', true) ?: array();
        }
        return array();
    }

    public function check_availability($date, $participants = 1) {
        $available_dates = $this->get_available_dates();
        
        if (empty($available_dates)) {
            return false;
        }

        foreach ($available_dates as $available_date) {
            if ($available_date['date'] === $date) {
                $booked = $this->get_bookings_for_date($date);
                $available_spots = $available_date['max_participants'] - $booked;
                return $available_spots >= $participants;
            }
        }

        return false;
    }

    public function get_bookings_for_date($date) {
        // Count existing bookings for this date
        $args = array(
            'post_type' => 'shop_order',
            'post_status' => array('wc-processing', 'wc-completed'),
            'meta_query' => array(
                array(
                    'key' => '_trip_product_id',
                    'value' => $this->get_id(),
                    'compare' => '='
                ),
                array(
                    'key' => '_trip_date',
                    'value' => $date,
                    'compare' => '='
                )
            ),
            'fields' => 'ids'
        );

        $orders = get_posts($args);
        $total_participants = 0;

        foreach ($orders as $order_id) {
            $participants = get_post_meta($order_id, '_trip_participants', true);
            $total_participants += intval($participants) ?: 1;
        }

        return $total_participants;
    }

    public function get_price_for_date($date, $participants = 1) {
        $base_price = $this->get_regular_price();
        $trip_data = $this->get_trip_data();
        
        // Apply seasonal pricing
        $seasonal_multiplier = $this->get_seasonal_multiplier($date);
        
        // Apply group pricing
        $group_discount = $this->get_group_discount($participants);
        
        $final_price = $base_price * $seasonal_multiplier * (1 - $group_discount);
        
        return apply_filters('yht_trip_dynamic_price', $final_price, $this, $date, $participants);
    }

    private function get_seasonal_multiplier($date) {
        $season_pricing = get_post_meta($this->get_id(), '_trip_seasonal_pricing', true);
        
        if (empty($season_pricing)) {
            return 1.0;
        }

        $month = date('n', strtotime($date));
        
        foreach ($season_pricing as $season) {
            if ($month >= $season['start_month'] && $month <= $season['end_month']) {
                return floatval($season['multiplier']) ?: 1.0;
            }
        }

        return 1.0;
    }

    private function get_group_discount($participants) {
        $group_pricing = get_post_meta($this->get_id(), '_trip_group_pricing', true);
        
        if (empty($group_pricing)) {
            return 0;
        }

        foreach ($group_pricing as $tier) {
            if ($participants >= $tier['min_participants']) {
                return floatval($tier['discount']) ?: 0;
            }
        }

        return 0;
    }
}