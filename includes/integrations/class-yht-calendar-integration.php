<?php
/**
 * Calendar Integration Manager
 * 
 * @package YourHiddenTrip
 * @version 6.3.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Calendar Integration Manager class
 */
class YHT_Calendar_Integration {
    
    /**
     * Initialize calendar integration
     */
    public function __construct() {
        add_action('init', array($this, 'init_calendar_integration'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_calendar_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_yht_sync_google_calendar', array($this, 'sync_google_calendar'));
        add_action('wp_ajax_yht_sync_outlook_calendar', array($this, 'sync_outlook_calendar'));
        add_action('wp_ajax_yht_export_calendar_event', array($this, 'export_calendar_event'));
        add_action('wp_ajax_nopriv_yht_export_calendar_event', array($this, 'export_calendar_event'));
        
        // Shortcodes
        add_shortcode('yht_calendar_sync', array($this, 'calendar_sync_shortcode'));
        add_shortcode('yht_add_to_calendar', array($this, 'add_to_calendar_shortcode'));
        
        // Admin hooks
        add_action('add_meta_boxes', array($this, 'add_calendar_meta_box'));
        add_action('save_post', array($this, 'save_calendar_meta'));
        
        // REST API endpoints
        add_action('rest_api_init', array($this, 'register_calendar_endpoints'));
        
        // Automatic sync hooks
        add_action('yht_trip_booking_confirmed', array($this, 'auto_add_to_calendar'), 10, 2);
        add_action('woocommerce_order_status_completed', array($this, 'handle_wc_calendar_sync'));
    }

    /**
     * Initialize calendar integration
     */
    public function init_calendar_integration() {
        // Register calendar event post type for storing sync data
        register_post_type('calendar_event', array(
            'labels' => array(
                'name' => __('Calendar Events', 'your-hidden-trip'),
                'singular_name' => __('Calendar Event', 'your-hidden-trip')
            ),
            'public' => false,
            'show_ui' => false,
            'supports' => array('title', 'meta'),
            'capability_type' => 'post'
        ));
        
        // Schedule automatic sync
        if (!wp_next_scheduled('yht_calendar_sync')) {
            wp_schedule_event(time(), 'hourly', 'yht_calendar_sync');
        }
        
        add_action('yht_calendar_sync', array($this, 'scheduled_calendar_sync'));
    }

    /**
     * Enqueue calendar scripts
     */
    public function enqueue_calendar_scripts() {
        if (is_singular('trip') || is_page_template('page-trip-planner.php')) {
            wp_enqueue_script(
                'yht-calendar-integration',
                YHT_PLUGIN_URL . 'assets/js/calendar-integration.js',
                array('jquery'),
                YHT_VER,
                true
            );

            wp_localize_script('yht-calendar-integration', 'yhtCalendar', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('yht_calendar_nonce'),
                'google_client_id' => get_option('yht_google_client_id', ''),
                'outlook_client_id' => get_option('yht_outlook_client_id', ''),
                'strings' => array(
                    'add_to_google' => __('Add to Google Calendar', 'your-hidden-trip'),
                    'add_to_outlook' => __('Add to Outlook', 'your-hidden-trip'),
                    'download_ics' => __('Download .ics file', 'your-hidden-trip'),
                    'sync_success' => __('Event added to calendar', 'your-hidden-trip'),
                    'sync_error' => __('Failed to add to calendar', 'your-hidden-trip'),
                    'auth_required' => __('Please authorize calendar access', 'your-hidden-trip')
                )
            ));
        }
    }

    /**
     * Add calendar meta box to trip posts
     */
    public function add_calendar_meta_box() {
        add_meta_box(
            'yht_calendar_settings',
            __('Calendar Integration', 'your-hidden-trip'),
            array($this, 'calendar_meta_box_callback'),
            'trip',
            'side',
            'default'
        );
    }

    /**
     * Calendar meta box callback
     */
    public function calendar_meta_box_callback($post) {
        wp_nonce_field('yht_calendar_meta', 'yht_calendar_nonce');
        
        $auto_sync = get_post_meta($post->ID, '_calendar_auto_sync', true);
        $sync_google = get_post_meta($post->ID, '_calendar_sync_google', true);
        $sync_outlook = get_post_meta($post->ID, '_calendar_sync_outlook', true);
        $calendar_category = get_post_meta($post->ID, '_calendar_category', true);
        $reminder_minutes = get_post_meta($post->ID, '_calendar_reminder_minutes', true) ?: 60;
        
        ?>
        <p>
            <label>
                <input type="checkbox" name="calendar_auto_sync" value="1" <?php checked($auto_sync, '1'); ?>>
                <?php _e('Auto-sync bookings to calendar', 'your-hidden-trip'); ?>
            </label>
        </p>
        
        <p>
            <label>
                <input type="checkbox" name="calendar_sync_google" value="1" <?php checked($sync_google, '1'); ?>>
                <?php _e('Sync to Google Calendar', 'your-hidden-trip'); ?>
            </label>
        </p>
        
        <p>
            <label>
                <input type="checkbox" name="calendar_sync_outlook" value="1" <?php checked($sync_outlook, '1'); ?>>
                <?php _e('Sync to Outlook Calendar', 'your-hidden-trip'); ?>
            </label>
        </p>
        
        <p>
            <label for="calendar_category"><?php _e('Calendar Category:', 'your-hidden-trip'); ?></label>
            <select name="calendar_category" id="calendar_category">
                <option value=""><?php _e('Default', 'your-hidden-trip'); ?></option>
                <option value="business" <?php selected($calendar_category, 'business'); ?>><?php _e('Business', 'your-hidden-trip'); ?></option>
                <option value="personal" <?php selected($calendar_category, 'personal'); ?>><?php _e('Personal', 'your-hidden-trip'); ?></option>
                <option value="travel" <?php selected($calendar_category, 'travel'); ?>><?php _e('Travel', 'your-hidden-trip'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="calendar_reminder_minutes"><?php _e('Reminder (minutes before):', 'your-hidden-trip'); ?></label>
            <input type="number" name="calendar_reminder_minutes" id="calendar_reminder_minutes" 
                   value="<?php echo esc_attr($reminder_minutes); ?>" min="0" max="10080">
        </p>
        <?php
    }

    /**
     * Save calendar meta data
     */
    public function save_calendar_meta($post_id) {
        if (!isset($_POST['yht_calendar_nonce']) || !wp_verify_nonce($_POST['yht_calendar_nonce'], 'yht_calendar_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = array(
            'calendar_auto_sync' => '_calendar_auto_sync',
            'calendar_sync_google' => '_calendar_sync_google',
            'calendar_sync_outlook' => '_calendar_sync_outlook',
            'calendar_category' => '_calendar_category',
            'calendar_reminder_minutes' => '_calendar_reminder_minutes'
        );

        foreach ($fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
            } else {
                delete_post_meta($post_id, $meta_key);
            }
        }
    }

    /**
     * Register REST API endpoints
     */
    public function register_calendar_endpoints() {
        register_rest_route('yht/v1', '/calendar/events', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_calendar_events'),
            'permission_callback' => array($this, 'calendar_permissions_check')
        ));

        register_rest_route('yht/v1', '/calendar/sync', array(
            'methods' => 'POST',
            'callback' => array($this, 'sync_calendar_event'),
            'permission_callback' => array($this, 'calendar_permissions_check')
        ));

        register_rest_route('yht/v1', '/calendar/export/(?P<trip_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'export_trip_calendar'),
            'permission_callback' => '__return_true'
        ));
    }

    /**
     * Calendar permissions check
     */
    public function calendar_permissions_check() {
        return current_user_can('edit_posts');
    }

    /**
     * Handle Google Calendar sync
     */
    public function sync_google_calendar() {
        check_ajax_referer('yht_calendar_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $trip_id = intval($_POST['trip_id']);
        $access_token = sanitize_text_field($_POST['access_token']);
        $event_data = json_decode(stripslashes($_POST['event_data']), true);

        if (!$trip_id || !$access_token || !$event_data) {
            wp_send_json_error(array('message' => 'Missing required data'));
        }

        $result = $this->create_google_calendar_event($access_token, $event_data);

        if ($result && !is_wp_error($result)) {
            // Store sync information
            $this->store_calendar_sync($trip_id, 'google', $result['id'], $result);
            wp_send_json_success(array('event_id' => $result['id']));
        } else {
            wp_send_json_error(array('message' => is_wp_error($result) ? $result->get_error_message() : 'Sync failed'));
        }
    }

    /**
     * Create Google Calendar event
     */
    private function create_google_calendar_event($access_token, $event_data) {
        $api_url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';

        $google_event = array(
            'summary' => $event_data['title'],
            'description' => $event_data['description'],
            'start' => array(
                'dateTime' => $event_data['start_datetime'],
                'timeZone' => $event_data['timezone'] ?: 'Europe/Rome'
            ),
            'end' => array(
                'dateTime' => $event_data['end_datetime'],
                'timeZone' => $event_data['timezone'] ?: 'Europe/Rome'
            ),
            'location' => $event_data['location'],
            'reminders' => array(
                'useDefault' => false,
                'overrides' => array(
                    array(
                        'method' => 'email',
                        'minutes' => intval($event_data['reminder_minutes']) ?: 60
                    ),
                    array(
                        'method' => 'popup',
                        'minutes' => 15
                    )
                )
            )
        );

        if (!empty($event_data['attendees'])) {
            $google_event['attendees'] = array_map(function($email) {
                return array('email' => $email);
            }, $event_data['attendees']);
        }

        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($google_event),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (wp_remote_retrieve_response_code($response) === 200 && isset($data['id'])) {
            return $data;
        }

        return new WP_Error('google_calendar_error', isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error');
    }

    /**
     * Handle Outlook Calendar sync
     */
    public function sync_outlook_calendar() {
        check_ajax_referer('yht_calendar_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $trip_id = intval($_POST['trip_id']);
        $access_token = sanitize_text_field($_POST['access_token']);
        $event_data = json_decode(stripslashes($_POST['event_data']), true);

        if (!$trip_id || !$access_token || !$event_data) {
            wp_send_json_error(array('message' => 'Missing required data'));
        }

        $result = $this->create_outlook_calendar_event($access_token, $event_data);

        if ($result && !is_wp_error($result)) {
            $this->store_calendar_sync($trip_id, 'outlook', $result['id'], $result);
            wp_send_json_success(array('event_id' => $result['id']));
        } else {
            wp_send_json_error(array('message' => is_wp_error($result) ? $result->get_error_message() : 'Sync failed'));
        }
    }

    /**
     * Create Outlook Calendar event
     */
    private function create_outlook_calendar_event($access_token, $event_data) {
        $api_url = 'https://graph.microsoft.com/v1.0/me/events';

        $outlook_event = array(
            'subject' => $event_data['title'],
            'body' => array(
                'contentType' => 'HTML',
                'content' => $event_data['description']
            ),
            'start' => array(
                'dateTime' => $event_data['start_datetime'],
                'timeZone' => $event_data['timezone'] ?: 'Europe/Rome'
            ),
            'end' => array(
                'dateTime' => $event_data['end_datetime'],
                'timeZone' => $event_data['timezone'] ?: 'Europe/Rome'
            ),
            'location' => array(
                'displayName' => $event_data['location']
            ),
            'reminderMinutesBeforeStart' => intval($event_data['reminder_minutes']) ?: 60
        );

        if (!empty($event_data['attendees'])) {
            $outlook_event['attendees'] = array_map(function($email) {
                return array(
                    'emailAddress' => array(
                        'address' => $email,
                        'name' => $email
                    ),
                    'type' => 'required'
                );
            }, $event_data['attendees']);
        }

        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($outlook_event),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (wp_remote_retrieve_response_code($response) === 201 && isset($data['id'])) {
            return $data;
        }

        return new WP_Error('outlook_calendar_error', isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error');
    }

    /**
     * Export calendar event as ICS
     */
    public function export_calendar_event() {
        $trip_id = intval($_GET['trip_id']);
        $event_date = sanitize_text_field($_GET['event_date']);
        $format = sanitize_text_field($_GET['format']) ?: 'ics';

        if (!$trip_id) {
            wp_die('Invalid trip ID');
        }

        $trip = get_post($trip_id);
        if (!$trip || $trip->post_type !== 'trip') {
            wp_die('Trip not found');
        }

        $event_data = $this->prepare_event_data($trip, $event_date);

        switch ($format) {
            case 'ics':
                $this->export_ics($event_data);
                break;
            case 'google':
                $this->export_google_url($event_data);
                break;
            case 'outlook':
                $this->export_outlook_url($event_data);
                break;
            default:
                wp_die('Invalid format');
        }
    }

    /**
     * Prepare event data for export
     */
    private function prepare_event_data($trip, $event_date = null) {
        $duration = get_post_meta($trip->ID, '_trip_duration', true) ?: '4 hours';
        $meeting_point = get_post_meta($trip->ID, '_trip_meeting_point', true) ?: '';
        $reminder_minutes = get_post_meta($trip->ID, '_calendar_reminder_minutes', true) ?: 60;

        // Parse duration to get end time
        $start_time = $event_date ? strtotime($event_date) : time();
        $duration_hours = $this->parse_duration_to_hours($duration);
        $end_time = $start_time + ($duration_hours * 3600);

        return array(
            'title' => $trip->post_title,
            'description' => strip_tags($trip->post_content),
            'location' => $meeting_point,
            'start_datetime' => date('Y-m-d\TH:i:s', $start_time),
            'end_datetime' => date('Y-m-d\TH:i:s', $end_time),
            'timezone' => 'Europe/Rome',
            'reminder_minutes' => $reminder_minutes,
            'url' => get_permalink($trip->ID)
        );
    }

    /**
     * Parse duration string to hours
     */
    private function parse_duration_to_hours($duration) {
        if (preg_match('/(\d+)\s*hour/i', $duration, $matches)) {
            return intval($matches[1]);
        } elseif (preg_match('/(\d+)\s*day/i', $duration, $matches)) {
            return intval($matches[1]) * 8; // Assume 8-hour days
        } elseif (preg_match('/half[- ]?day/i', $duration)) {
            return 4;
        } elseif (preg_match('/full[- ]?day/i', $duration)) {
            return 8;
        } else {
            return 4; // Default 4 hours
        }
    }

    /**
     * Export ICS file
     */
    private function export_ics($event_data) {
        $ics_content = $this->generate_ics($event_data);
        
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="trip-' . sanitize_file_name($event_data['title']) . '.ics"');
        
        echo $ics_content;
        exit;
    }

    /**
     * Generate ICS content
     */
    private function generate_ics($event_data) {
        $uid = uniqid() . '@' . parse_url(home_url(), PHP_URL_HOST);
        $dtstamp = gmdate('Ymd\THis\Z');
        $dtstart = gmdate('Ymd\THis\Z', strtotime($event_data['start_datetime']));
        $dtend = gmdate('Ymd\THis\Z', strtotime($event_data['end_datetime']));

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Your Hidden Trip//Trip Planner//EN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:{$uid}\r\n";
        $ics .= "DTSTAMP:{$dtstamp}\r\n";
        $ics .= "DTSTART:{$dtstart}\r\n";
        $ics .= "DTEND:{$dtend}\r\n";
        $ics .= "SUMMARY:" . $this->escape_ics_string($event_data['title']) . "\r\n";
        $ics .= "DESCRIPTION:" . $this->escape_ics_string($event_data['description']) . "\r\n";
        
        if (!empty($event_data['location'])) {
            $ics .= "LOCATION:" . $this->escape_ics_string($event_data['location']) . "\r\n";
        }
        
        if (!empty($event_data['url'])) {
            $ics .= "URL:" . $event_data['url'] . "\r\n";
        }
        
        if ($event_data['reminder_minutes'] > 0) {
            $ics .= "BEGIN:VALARM\r\n";
            $ics .= "TRIGGER:-PT{$event_data['reminder_minutes']}M\r\n";
            $ics .= "ACTION:DISPLAY\r\n";
            $ics .= "DESCRIPTION:Reminder\r\n";
            $ics .= "END:VALARM\r\n";
        }
        
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    /**
     * Escape string for ICS format
     */
    private function escape_ics_string($string) {
        $string = str_replace(array("\\", "\n", "\r", ",", ";"), array("\\\\", "\\n", "\\r", "\\,", "\\;"), $string);
        return $string;
    }

    /**
     * Store calendar sync information
     */
    private function store_calendar_sync($trip_id, $provider, $event_id, $response_data) {
        $sync_data = array(
            'post_title' => "Calendar Sync - {$provider} - " . get_the_title($trip_id),
            'post_type' => 'calendar_event',
            'post_status' => 'publish',
            'meta_input' => array(
                '_trip_id' => $trip_id,
                '_calendar_provider' => $provider,
                '_calendar_event_id' => $event_id,
                '_sync_response' => $response_data,
                '_sync_date' => current_time('mysql')
            )
        );

        return wp_insert_post($sync_data);
    }

    /**
     * Auto add to calendar when trip is booked
     */
    public function auto_add_to_calendar($trip_id, $booking_data) {
        $auto_sync = get_post_meta($trip_id, '_calendar_auto_sync', true);
        
        if (!$auto_sync) {
            return;
        }

        $event_data = $this->prepare_event_data(get_post($trip_id), $booking_data['trip_date']);
        
        // Add booking-specific data
        $event_data['attendees'] = array($booking_data['customer_email']);
        $event_data['description'] .= "\n\nBooking details:\n";
        $event_data['description'] .= "Participants: " . $booking_data['participants'] . "\n";
        $event_data['description'] .= "Customer: " . $booking_data['customer_name'] . "\n";
        
        // Sync to configured calendars
        if (get_post_meta($trip_id, '_calendar_sync_google', true)) {
            // This would require stored admin credentials or service account
            $this->admin_google_calendar_sync($event_data);
        }
        
        if (get_post_meta($trip_id, '_calendar_sync_outlook', true)) {
            // This would require stored admin credentials
            $this->admin_outlook_calendar_sync($event_data);
        }
    }

    /**
     * Calendar sync shortcode
     */
    public function calendar_sync_shortcode($atts) {
        $atts = shortcode_atts(array(
            'trip_id' => get_the_ID(),
            'show_google' => 'yes',
            'show_outlook' => 'yes',
            'show_ics' => 'yes',
            'date' => '',
            'style' => 'buttons'
        ), $atts, 'yht_calendar_sync');

        if (!$atts['trip_id']) {
            return '';
        }

        ob_start();
        include YHT_PLUGIN_PATH . 'templates/shortcodes/calendar-sync.php';
        return ob_get_clean();
    }

    /**
     * Add to calendar shortcode
     */
    public function add_to_calendar_shortcode($atts) {
        $atts = shortcode_atts(array(
            'trip_id' => get_the_ID(),
            'text' => __('Add to Calendar', 'your-hidden-trip'),
            'provider' => 'google',
            'date' => '',
            'class' => 'yht-add-to-calendar'
        ), $atts, 'yht_add_to_calendar');

        if (!$atts['trip_id']) {
            return '';
        }

        $trip = get_post($atts['trip_id']);
        if (!$trip) {
            return '';
        }

        $event_data = $this->prepare_event_data($trip, $atts['date']);
        $url = $this->get_calendar_url($atts['provider'], $event_data);

        return sprintf(
            '<a href="%s" class="%s" target="_blank" rel="noopener">%s</a>',
            esc_url($url),
            esc_attr($atts['class']),
            esc_html($atts['text'])
        );
    }

    /**
     * Get calendar URL for different providers
     */
    private function get_calendar_url($provider, $event_data) {
        switch ($provider) {
            case 'google':
                return $this->get_google_calendar_url($event_data);
            case 'outlook':
                return $this->get_outlook_calendar_url($event_data);
            case 'yahoo':
                return $this->get_yahoo_calendar_url($event_data);
            default:
                return home_url('/wp-admin/admin-ajax.php?action=yht_export_calendar_event&trip_id=' . $event_data['trip_id'] . '&format=ics');
        }
    }

    /**
     * Get Google Calendar URL
     */
    private function get_google_calendar_url($event_data) {
        $params = array(
            'action' => 'TEMPLATE',
            'text' => $event_data['title'],
            'dates' => gmdate('Ymd\THis\Z', strtotime($event_data['start_datetime'])) . '/' . gmdate('Ymd\THis\Z', strtotime($event_data['end_datetime'])),
            'details' => $event_data['description'],
            'location' => $event_data['location']
        );

        return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
    }

    /**
     * Get Outlook Calendar URL
     */
    private function get_outlook_calendar_url($event_data) {
        $params = array(
            'subject' => $event_data['title'],
            'startdt' => gmdate('Y-m-d\TH:i:s\Z', strtotime($event_data['start_datetime'])),
            'enddt' => gmdate('Y-m-d\TH:i:s\Z', strtotime($event_data['end_datetime'])),
            'body' => $event_data['description'],
            'location' => $event_data['location']
        );

        return 'https://outlook.live.com/calendar/0/deeplink/compose?' . http_build_query($params);
    }

    /**
     * Get Yahoo Calendar URL
     */
    private function get_yahoo_calendar_url($event_data) {
        $duration_hours = (strtotime($event_data['end_datetime']) - strtotime($event_data['start_datetime'])) / 3600;
        
        $params = array(
            'v' => '60',
            'title' => $event_data['title'],
            'st' => gmdate('Ymd\THis\Z', strtotime($event_data['start_datetime'])),
            'dur' => sprintf('%02d%02d', floor($duration_hours), ($duration_hours - floor($duration_hours)) * 60),
            'desc' => $event_data['description'],
            'in_loc' => $event_data['location']
        );

        return 'https://calendar.yahoo.com/?' . http_build_query($params);
    }

    /**
     * Scheduled calendar sync
     */
    public function scheduled_calendar_sync() {
        // Sync upcoming events
        $upcoming_events = $this->get_upcoming_calendar_events();
        
        foreach ($upcoming_events as $event) {
            $this->sync_single_event($event);
        }
    }

    /**
     * Get upcoming calendar events
     */
    private function get_upcoming_calendar_events() {
        // This would query for trips with bookings in the next few days
        // Implementation depends on your booking system
        return array();
    }
}