<?php
/**
 * Helper utility functions
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_Helpers {
    
    /**
     * Convert duration string to days
     */
    public static function duration_to_days($duration) {
        $mapping = array(
            '1_notte' => 2,
            '2_notti' => 3,
            '3_notti' => 4,
            '4_notti' => 5,
            '5+_notti' => 7
        );
        
        return $mapping[$duration] ?? 2;
    }
    
    /**
     * Generate date range
     */
    public static function date_range($start, $days) {
        $output = array();
        if(!$start) return $output;
        
        $date = new DateTime($start);
        for($i = 0; $i < $days; $i++) {
            $output[] = $date->format('Y-m-d');
            $date->modify('+1 day');
        }
        
        return $output;
    }
    
    /**
     * Query places of interest
     */
    public static function query_poi($experiences, $areas, $startdate, $days) {
        $tax_query = array('relation' => 'AND');
        
        if(!empty($experiences)) {
            $tax_query[] = array(
                'taxonomy' => 'yht_esperienza',
                'field' => 'slug',
                'terms' => $experiences,
                'operator' => 'IN'
            );
        }
        
        if(!empty($areas)) {
            $tax_query[] = array(
                'taxonomy' => 'yht_area',
                'field' => 'slug',
                'terms' => $areas,
                'operator' => 'IN'
            );
        }
        
        $query = new WP_Query(array(
            'post_type' => 'yht_luogo',
            'posts_per_page' => -1,
            'tax_query' => (count($tax_query) > 1 ? $tax_query : array()),
            'no_found_rows' => true,
            'meta_query' => array(
                array(
                    'key' => 'yht_lat',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => 'yht_lng',
                    'compare' => 'EXISTS'
                )
            )
        ));

        $results = array();
        $date_range = self::date_range($startdate, $days);
        
        while($query->have_posts()) { 
            $query->the_post();
            $id = get_the_ID();
            $lat = (float) get_post_meta($id,'yht_lat',true);
            $lng = (float) get_post_meta($id,'yht_lng',true);

            // Check for closures
            if(self::is_closed_during_dates($id, $date_range)) continue;

            $results[] = array(
                'id' => $id,
                'title' => get_the_title(),
                'excerpt' => wp_strip_all_tags(get_the_excerpt()),
                'lat' => $lat,
                'lng' => $lng,
                'cost' => (float) get_post_meta($id,'yht_cost_ingresso',true),
                'durata' => (int) get_post_meta($id,'yht_durata_min',true),
                'exp' => wp_get_post_terms($id,'yht_esperienza',array('fields'=>'slugs')),
                'area' => wp_get_post_terms($id,'yht_area',array('fields'=>'slugs')),
                'link' => get_permalink($id),
            );
        }
        wp_reset_postdata();
        
        return $results;
    }
    
    /**
     * Check if place is closed during specified dates
     */
    private static function is_closed_during_dates($place_id, $date_range) {
        $closures_json = get_post_meta($place_id,'yht_chiusure_json',true);
        
        if(!$closures_json) return false;
        
        $closures = json_decode($closures_json, true);
        if(!is_array($closures)) return false;
        
        foreach($closures as $closure) {
            $start = $closure['start'] ?? '';
            $end = $closure['end'] ?? '';
            
            if($start && $end) {
                foreach($date_range as $date) {
                    if($date >= $start && $date <= $end) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Query accommodations
     */
    public static function query_accommodations($areas, $startdate, $days) {
        $tax_query = array();
        
        if(!empty($areas)) {
            $tax_query[] = array(
                'taxonomy' => 'yht_area',
                'field' => 'slug',
                'terms' => $areas,
                'operator' => 'IN'
            );
        }
        
        $query = new WP_Query(array(
            'post_type' => 'yht_alloggio',
            'posts_per_page' => -1,
            'tax_query' => $tax_query,
            'no_found_rows' => true,
        ));

        $results = array();
        $date_range = self::date_range($startdate, $days);
        
        while($query->have_posts()) { 
            $query->the_post();
            $id = get_the_ID();
            $lat = (float) get_post_meta($id,'yht_lat',true);
            $lng = (float) get_post_meta($id,'yht_lng',true);
            
            if(!$lat || !$lng) continue;

            $results[] = array(
                'id' => $id,
                'title' => get_the_title(),
                'excerpt' => wp_strip_all_tags(get_the_excerpt()),
                'lat' => $lat,
                'lng' => $lng,
                'fascia_prezzo' => get_post_meta($id,'yht_fascia_prezzo',true),
                'servizi' => json_decode(get_post_meta($id,'yht_servizi_json',true) ?: '[]', true),
                'capienza' => (int) get_post_meta($id,'yht_capienza',true),
                'prezzo_notte_standard' => (float) get_post_meta($id,'yht_prezzo_notte_standard',true),
                'prezzo_notte_premium' => (float) get_post_meta($id,'yht_prezzo_notte_premium',true),
                'prezzo_notte_luxury' => (float) get_post_meta($id,'yht_prezzo_notte_luxury',true),
                'incluso_colazione' => get_post_meta($id,'yht_incluso_colazione',true) === '1',
                'incluso_pranzo' => get_post_meta($id,'yht_incluso_pranzo',true) === '1',
                'incluso_cena' => get_post_meta($id,'yht_incluso_cena',true) === '1',
                'disponibilita' => json_decode(get_post_meta($id,'yht_disponibilita_json',true) ?: '[]', true),
                'link' => get_permalink($id),
                'type' => 'accommodation'
            );
        }
        wp_reset_postdata();
        
        return $results;
    }
    
    /**
     * Query services (restaurants, car rental, drivers)
     */
    public static function query_services($areas, $trasporto = '') {
        $tax_query = array('relation' => 'AND');
        
        if(!empty($areas)) {
            $tax_query[] = array(
                'taxonomy' => 'yht_area',
                'field' => 'slug',
                'terms' => $areas,
                'operator' => 'IN'
            );
        }
        
        // Filter by service type based on transport preference
        $service_types = array('ristorante'); // Always include restaurants
        if($trasporto === 'noleggio_auto') {
            $service_types[] = 'noleggio_auto';
        } elseif($trasporto === 'autista') {
            $service_types[] = 'autista';
        }
        
        if(!empty($service_types)) {
            $tax_query[] = array(
                'taxonomy' => 'yht_tipo_servizio',
                'field' => 'slug',
                'terms' => $service_types,
                'operator' => 'IN'
            );
        }
        
        $query = new WP_Query(array(
            'post_type' => 'yht_servizio',
            'posts_per_page' => -1,
            'tax_query' => (count($tax_query) > 1 ? $tax_query : array()),
            'no_found_rows' => true,
        ));

        $results = array();
        
        while($query->have_posts()) { 
            $query->the_post();
            $id = get_the_ID();
            $lat = (float) get_post_meta($id,'yht_lat',true);
            $lng = (float) get_post_meta($id,'yht_lng',true);
            
            if(!$lat || !$lng) continue;

            $results[] = array(
                'id' => $id,
                'title' => get_the_title(),
                'excerpt' => wp_strip_all_tags(get_the_excerpt()),
                'lat' => $lat,
                'lng' => $lng,
                'fascia_prezzo' => get_post_meta($id,'yht_fascia_prezzo',true),
                'orari' => get_post_meta($id,'yht_orari',true),
                'telefono' => get_post_meta($id,'yht_telefono',true),
                'sito_web' => get_post_meta($id,'yht_sito_web',true),
                'prezzo_persona' => (float) get_post_meta($id,'yht_prezzo_persona',true),
                'prezzo_fisso' => (float) get_post_meta($id,'yht_prezzo_fisso',true),
                'durata_servizio' => (int) get_post_meta($id,'yht_durata_servizio',true),
                'capacita_max' => (int) get_post_meta($id,'yht_capacita_max',true),
                'prenotazione_richiesta' => get_post_meta($id,'yht_prenotazione_richiesta',true) === '1',
                'disponibilita' => json_decode(get_post_meta($id,'yht_disponibilita_json',true) ?: '[]', true),
                'service_type' => wp_get_post_terms($id,'yht_tipo_servizio',array('fields'=>'slugs')),
                'link' => get_permalink($id),
                'type' => 'service'
            );
        }
        wp_reset_postdata();
        
        return $results;
    }
    /**
     * Plan itinerary with given parameters
     */
    public static function plan_itinerary($name, $pool, $days, $per_day, $weights, $accommodations = array(), $services = array()) {
        if(empty($pool)) {
            return array('name' => $name, 'days' => array(), 'stops' => 0, 'totalEntryCost' => 0, 'accommodations' => array(), 'services' => array());
        }
        
        // Score places by experiences
        foreach($pool as &$place) {
            $place['_score'] = 0;
            foreach(($place['exp'] ?? array()) as $exp) {
                $place['_score'] += isset($weights[$exp]) ? $weights[$exp] : 0;
            }
        }
        unset($place);
        
        // Sort by score
        usort($pool, function($a, $b) { 
            return $b['_score'] <=> $a['_score']; 
        });

        $needed = $days * $per_day;
        $selected = array();
        $selected[] = $pool[0]; // Start with highest scored place

        // Select remaining places based on proximity
        while(count($selected) < min($needed, count($pool))) {
            $last = end($selected);
            $next = null;
            $best_distance = PHP_FLOAT_MAX;
            
            foreach($pool as $candidate) {
                if(in_array($candidate, $selected, true)) continue;
                
                $distance = self::calculate_distance($last['lat'], $last['lng'], $candidate['lat'], $candidate['lng']);
                if($distance < $best_distance) {
                    $best_distance = $distance;
                    $next = $candidate;
                }
            }
            
            if($next) {
                $selected[] = $next;
            } else {
                break;
            }
        }

        // Fill any remaining slots
        foreach($pool as $candidate) {
            if(count($selected) >= $needed) break;
            if(!in_array($candidate, $selected, true)) {
                $selected[] = $candidate;
            }
        }

        // Distribute places across days
        $days_array = array();
        $index = 0;
        $time_slots = ($per_day == 3) ? array('10:00','14:30','17:30') : array('11:00','16:00');
        
        for($day = 0; $day < $days; $day++) {
            $stops = array_slice($selected, $index, $per_day);
            $index += $per_day;
            
            $stops_timed = array();
            foreach($stops as $i => $stop) {
                $stops_timed[] = array_merge($stop, array(
                    'time' => $time_slots[$i] ?? '18:00', 
                    '_day' => $day + 1
                ));
            }
            
            $days_array[] = array('day' => $day + 1, 'stops' => $stops_timed);
        }

        // Calculate total entry cost
        $total_cost = 0;
        foreach($selected as $stop) {
            $total_cost += is_numeric($stop['cost']) ? (float)$stop['cost'] : 0;
        }

        // Select accommodations (1-2 best rated)
        $selected_accommodations = array();
        if(!empty($accommodations)) {
            $selected_accommodations = array_slice($accommodations, 0, 2);
        }

        // Select restaurants near itinerary points
        $selected_restaurants = array();
        $selected_transport_services = array();
        
        if(!empty($services)) {
            foreach($services as $service) {
                $service_types = $service['service_type'] ?? array();
                
                if(in_array('ristorante', $service_types) && count($selected_restaurants) < 3) {
                    // Select restaurants close to itinerary stops
                    $min_distance = PHP_FLOAT_MAX;
                    foreach($selected as $stop) {
                        $distance = self::calculate_distance($stop['lat'], $stop['lng'], $service['lat'], $service['lng']);
                        if($distance < $min_distance) {
                            $min_distance = $distance;
                        }
                    }
                    
                    if($min_distance < 10) { // Within 10km
                        $selected_restaurants[] = $service;
                    }
                } elseif((in_array('noleggio_auto', $service_types) || in_array('autista', $service_types)) && count($selected_transport_services) < 2) {
                    $selected_transport_services[] = $service;
                }
            }
        }

        $all_services = array_merge($selected_restaurants, $selected_transport_services);

        return array(
            'name' => $name, 
            'days' => $days_array, 
            'stops' => count($selected), 
            'totalEntryCost' => round($total_cost),
            'accommodations' => $selected_accommodations,
            'services' => $all_services
        );
    }
    
    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    public static function calculate_distance($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 6371; // km
        
        $d_lat = deg2rad($lat2 - $lat1);
        $d_lon = deg2rad($lon2 - $lon1);
        
        $a = sin($d_lat/2) * sin($d_lat/2) + 
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
             sin($d_lon/2) * sin($d_lon/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earth_radius * $c;
    }
    
    /**
     * Query custom tours from database
     */
    public static function query_custom_tours($experiences = array(), $areas = array()) {
        $tax_query = array('relation' => 'AND');
        
        if(!empty($experiences)) {
            $tax_query[] = array(
                'taxonomy' => 'yht_esperienza',
                'field' => 'slug',
                'terms' => $experiences,
                'operator' => 'IN'
            );
        }
        
        if(!empty($areas)) {
            $tax_query[] = array(
                'taxonomy' => 'yht_area',
                'field' => 'slug',
                'terms' => $areas,
                'operator' => 'IN'
            );
        }
        
        $query = new WP_Query(array(
            'post_type' => 'yht_tour',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => (count($tax_query) > 1 ? $tax_query : array()),
            'no_found_rows' => true,
        ));

        $results = array();
        
        while($query->have_posts()) { 
            $query->the_post();
            $id = get_the_ID();
            $giorni_json = get_post_meta($id,'yht_giorni',true);
            $giorni_data = json_decode($giorni_json, true);
            if(!is_array($giorni_data)) $giorni_data = array();
            
            $results[] = array(
                'id' => $id,
                'name' => get_the_title(),
                'description' => wp_strip_all_tags(get_the_excerpt()),
                'content' => get_the_content(),
                'giorni' => $giorni_data,
                'prezzo_base' => (float) get_post_meta($id,'yht_prezzo_base',true),
                'prezzo_standard_pax' => (float) get_post_meta($id,'yht_prezzo_standard_pax',true),
                'prezzo_premium_pax' => (float) get_post_meta($id,'yht_prezzo_premium_pax',true),
                'prezzo_luxury_pax' => (float) get_post_meta($id,'yht_prezzo_luxury_pax',true),
                'experiences' => wp_get_post_terms($id,'yht_esperienza',array('fields'=>'slugs')),
                'areas' => wp_get_post_terms($id,'yht_area',array('fields'=>'slugs')),
                'targets' => wp_get_post_terms($id,'yht_target',array('fields'=>'slugs')),
                'seasons' => wp_get_post_terms($id,'yht_stagione',array('fields'=>'slugs')),
                'link' => get_permalink($id),
                'type' => 'custom_tour'
            );
        }
        wp_reset_postdata();
        
        return $results;
    }
}