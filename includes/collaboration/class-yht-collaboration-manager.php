<?php
/**
 * Real-time Collaboration Manager
 * 
 * @package YourHiddenTrip
 * @version 6.3.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Real-time Collaboration Manager class
 */
class YHT_Collaboration_Manager {
    
    /**
     * Initialize collaboration features
     */
    public function __construct() {
        add_action('init', array($this, 'register_collaboration_endpoints'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_collaboration_scripts'));
        add_action('wp_ajax_yht_join_collaboration', array($this, 'handle_join_collaboration'));
        add_action('wp_ajax_nopriv_yht_join_collaboration', array($this, 'handle_join_collaboration'));
        add_action('wp_ajax_yht_collaboration_action', array($this, 'handle_collaboration_action'));
        add_action('wp_ajax_nopriv_yht_collaboration_action', array($this, 'handle_collaboration_action'));
        add_action('wp_ajax_yht_get_collaboration_updates', array($this, 'handle_get_updates'));
        add_action('wp_ajax_nopriv_yht_get_collaboration_updates', array($this, 'handle_get_updates'));
        
        // Server-Sent Events endpoint
        add_action('template_redirect', array($this, 'handle_sse_endpoint'));
        
        // Cleanup old sessions
        add_action('yht_cleanup_collaboration_sessions', array($this, 'cleanup_old_sessions'));
        if (!wp_next_scheduled('yht_cleanup_collaboration_sessions')) {
            wp_schedule_event(time(), 'hourly', 'yht_cleanup_collaboration_sessions');
        }
    }

    /**
     * Register collaboration endpoints
     */
    public function register_collaboration_endpoints() {
        add_rewrite_rule('^yht-collaboration-stream/([^/]+)/?', 'index.php?yht_collaboration_stream=1&session_id=$matches[1]', 'top');
        add_query_var('yht_collaboration_stream');
        add_query_var('session_id');
    }

    /**
     * Enqueue collaboration scripts
     */
    public function enqueue_collaboration_scripts() {
        if (is_singular('trip') || is_page_template('page-trip-planner.php')) {
            wp_enqueue_script(
                'yht-collaboration',
                YHT_PLUGIN_URL . 'assets/js/collaboration.js',
                array('jquery'),
                YHT_VER,
                true
            );

            wp_enqueue_style(
                'yht-collaboration-styles',
                YHT_PLUGIN_URL . 'assets/css/collaboration.css',
                array(),
                YHT_VER
            );

            // Localize script with collaboration data
            wp_localize_script('yht-collaboration', 'yhtCollaboration', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('yht_collaboration_nonce'),
                'user_id' => get_current_user_id(),
                'user_name' => wp_get_current_user()->display_name ?: __('Anonymous', 'your-hidden-trip'),
                'user_avatar' => get_avatar_url(get_current_user_id(), array('size' => 32)),
                'sse_endpoint' => home_url('/yht-collaboration-stream/'),
                'ping_interval' => 30000, // 30 seconds
                'max_participants' => 10,
                'session_timeout' => 300000 // 5 minutes
            ));
        }
    }

    /**
     * Handle SSE endpoint for real-time updates
     */
    public function handle_sse_endpoint() {
        if (get_query_var('yht_collaboration_stream')) {
            $session_id = get_query_var('session_id');
            
            if (!$session_id) {
                wp_die('Session ID required', 'Bad Request', array('response' => 400));
            }

            $this->serve_sse_stream($session_id);
            exit;
        }
    }

    /**
     * Serve Server-Sent Events stream
     */
    private function serve_sse_stream($session_id) {
        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');

        // Prevent timeout
        ignore_user_abort(true);
        set_time_limit(0);

        $last_update = isset($_GET['last_update']) ? intval($_GET['last_update']) : 0;
        $start_time = time();
        $max_duration = 300; // 5 minutes max connection

        while (time() - $start_time < $max_duration) {
            // Check for new updates
            $updates = $this->get_collaboration_updates($session_id, $last_update);
            
            if (!empty($updates)) {
                foreach ($updates as $update) {
                    echo "data: " . json_encode($update) . "\n\n";
                    $last_update = max($last_update, $update['timestamp']);
                }
                ob_flush();
                flush();
            }

            // Send keep-alive ping
            echo "event: ping\n";
            echo "data: " . json_encode(array('timestamp' => time())) . "\n\n";
            ob_flush();
            flush();

            // Check if connection is still alive
            if (connection_aborted()) {
                break;
            }

            sleep(2); // Check every 2 seconds
        }
    }

    /**
     * Handle join collaboration request
     */
    public function handle_join_collaboration() {
        check_ajax_referer('yht_collaboration_nonce', 'nonce');

        $session_id = sanitize_text_field($_POST['session_id']);
        $trip_id = intval($_POST['trip_id']);
        $user_data = array(
            'id' => get_current_user_id() ?: 'anonymous_' . wp_generate_uuid4(),
            'name' => sanitize_text_field($_POST['user_name']),
            'avatar' => esc_url($_POST['user_avatar']),
            'joined_at' => time(),
            'last_seen' => time()
        );

        // Create or join collaboration session
        $session = $this->get_collaboration_session($session_id);
        
        if (!$session) {
            $session = array(
                'id' => $session_id,
                'trip_id' => $trip_id,
                'created_at' => time(),
                'participants' => array(),
                'trip_data' => $this->get_trip_data($trip_id),
                'updates' => array()
            );
        }

        // Check participant limit
        if (count($session['participants']) >= 10) {
            wp_send_json_error(array('message' => 'Session is full'));
        }

        // Add or update participant
        $session['participants'][$user_data['id']] = $user_data;
        
        // Save session
        $this->save_collaboration_session($session);

        // Broadcast join event
        $this->broadcast_update($session_id, array(
            'type' => 'user_joined',
            'user' => $user_data,
            'participants' => array_values($session['participants'])
        ));

        wp_send_json_success(array(
            'session' => $session,
            'user_id' => $user_data['id']
        ));
    }

    /**
     * Handle collaboration actions
     */
    public function handle_collaboration_action() {
        check_ajax_referer('yht_collaboration_nonce', 'nonce');

        $session_id = sanitize_text_field($_POST['session_id']);
        $action_type = sanitize_text_field($_POST['action_type']);
        $action_data = json_decode(stripslashes($_POST['action_data']), true);
        $user_id = sanitize_text_field($_POST['user_id']);

        $session = $this->get_collaboration_session($session_id);
        
        if (!$session) {
            wp_send_json_error(array('message' => 'Session not found'));
        }

        // Update user's last seen
        if (isset($session['participants'][$user_id])) {
            $session['participants'][$user_id]['last_seen'] = time();
        }

        // Process the action
        $result = $this->process_collaboration_action($session, $action_type, $action_data, $user_id);
        
        if ($result) {
            // Save updated session
            $this->save_collaboration_session($session);

            // Broadcast the update
            $this->broadcast_update($session_id, array(
                'type' => $action_type,
                'data' => $action_data,
                'user_id' => $user_id,
                'timestamp' => time()
            ));

            wp_send_json_success(array('message' => 'Action processed'));
        } else {
            wp_send_json_error(array('message' => 'Failed to process action'));
        }
    }

    /**
     * Process collaboration action
     */
    private function process_collaboration_action(&$session, $action_type, $action_data, $user_id) {
        switch ($action_type) {
            case 'add_stop':
                $session['trip_data']['stops'][] = array(
                    'id' => wp_generate_uuid4(),
                    'name' => $action_data['name'],
                    'location' => $action_data['location'],
                    'duration' => $action_data['duration'],
                    'notes' => $action_data['notes'],
                    'added_by' => $user_id,
                    'added_at' => time()
                );
                return true;

            case 'remove_stop':
                $stop_id = $action_data['stop_id'];
                $session['trip_data']['stops'] = array_filter(
                    $session['trip_data']['stops'],
                    function($stop) use ($stop_id) {
                        return $stop['id'] !== $stop_id;
                    }
                );
                return true;

            case 'update_stop':
                foreach ($session['trip_data']['stops'] as &$stop) {
                    if ($stop['id'] === $action_data['stop_id']) {
                        $stop = array_merge($stop, $action_data['updates']);
                        $stop['updated_by'] = $user_id;
                        $stop['updated_at'] = time();
                        return true;
                    }
                }
                return false;

            case 'add_comment':
                if (!isset($session['trip_data']['comments'])) {
                    $session['trip_data']['comments'] = array();
                }
                $session['trip_data']['comments'][] = array(
                    'id' => wp_generate_uuid4(),
                    'text' => $action_data['text'],
                    'user_id' => $user_id,
                    'timestamp' => time(),
                    'replies' => array()
                );
                return true;

            case 'update_trip_settings':
                if (!isset($session['trip_data']['settings'])) {
                    $session['trip_data']['settings'] = array();
                }
                $session['trip_data']['settings'] = array_merge(
                    $session['trip_data']['settings'],
                    $action_data
                );
                return true;

            case 'cursor_move':
                // Handle cursor movement for live editing
                if (!isset($session['cursors'])) {
                    $session['cursors'] = array();
                }
                $session['cursors'][$user_id] = array(
                    'x' => $action_data['x'],
                    'y' => $action_data['y'],
                    'timestamp' => time()
                );
                return true;

            default:
                return false;
        }
    }

    /**
     * Handle get updates request
     */
    public function handle_get_updates() {
        check_ajax_referer('yht_collaboration_nonce', 'nonce');

        $session_id = sanitize_text_field($_POST['session_id']);
        $last_update = intval($_POST['last_update']);
        $user_id = sanitize_text_field($_POST['user_id']);

        // Update user's last seen
        $session = $this->get_collaboration_session($session_id);
        if ($session && isset($session['participants'][$user_id])) {
            $session['participants'][$user_id]['last_seen'] = time();
            $this->save_collaboration_session($session);
        }

        $updates = $this->get_collaboration_updates($session_id, $last_update);
        wp_send_json_success(array('updates' => $updates));
    }

    /**
     * Get collaboration session data
     */
    private function get_collaboration_session($session_id) {
        $sessions = get_option('yht_collaboration_sessions', array());
        return isset($sessions[$session_id]) ? $sessions[$session_id] : null;
    }

    /**
     * Save collaboration session data
     */
    private function save_collaboration_session($session) {
        $sessions = get_option('yht_collaboration_sessions', array());
        $sessions[$session['id']] = $session;
        update_option('yht_collaboration_sessions', $sessions);
    }

    /**
     * Get collaboration updates since timestamp
     */
    private function get_collaboration_updates($session_id, $since_timestamp = 0) {
        $session = $this->get_collaboration_session($session_id);
        
        if (!$session) {
            return array();
        }

        $updates = isset($session['updates']) ? $session['updates'] : array();
        
        return array_filter($updates, function($update) use ($since_timestamp) {
            return $update['timestamp'] > $since_timestamp;
        });
    }

    /**
     * Broadcast update to all participants
     */
    private function broadcast_update($session_id, $update_data) {
        $session = $this->get_collaboration_session($session_id);
        
        if (!$session) {
            return;
        }

        $update = array_merge($update_data, array(
            'session_id' => $session_id,
            'timestamp' => time()
        ));

        // Add to session updates
        if (!isset($session['updates'])) {
            $session['updates'] = array();
        }
        
        $session['updates'][] = $update;
        
        // Keep only last 100 updates
        if (count($session['updates']) > 100) {
            $session['updates'] = array_slice($session['updates'], -100);
        }

        $this->save_collaboration_session($session);
    }

    /**
     * Get trip data for collaboration
     */
    private function get_trip_data($trip_id) {
        $trip = get_post($trip_id);
        
        if (!$trip || $trip->post_type !== 'trip') {
            return array();
        }

        $meta = get_post_meta($trip_id);
        
        return array(
            'id' => $trip_id,
            'title' => $trip->post_title,
            'description' => $trip->post_content,
            'stops' => get_post_meta($trip_id, '_trip_stops', true) ?: array(),
            'settings' => array(
                'duration' => get_post_meta($trip_id, '_trip_duration', true),
                'difficulty' => get_post_meta($trip_id, '_trip_difficulty', true),
                'max_participants' => get_post_meta($trip_id, '_trip_max_participants', true),
                'budget' => get_post_meta($trip_id, '_trip_budget', true)
            ),
            'comments' => array(),
            'last_modified' => $trip->post_modified
        );
    }

    /**
     * Create collaboration session
     */
    public function create_collaboration_session($trip_id, $creator_data = array()) {
        $session_id = wp_generate_uuid4();
        
        $session = array(
            'id' => $session_id,
            'trip_id' => $trip_id,
            'created_at' => time(),
            'creator' => $creator_data,
            'participants' => array(),
            'trip_data' => $this->get_trip_data($trip_id),
            'updates' => array(),
            'settings' => array(
                'max_participants' => 10,
                'allow_anonymous' => true,
                'auto_save' => true
            )
        );

        $this->save_collaboration_session($session);
        
        return $session_id;
    }

    /**
     * Get active participants for a session
     */
    public function get_active_participants($session_id) {
        $session = $this->get_collaboration_session($session_id);
        
        if (!$session) {
            return array();
        }

        $current_time = time();
        $timeout = 300; // 5 minutes

        return array_filter($session['participants'], function($participant) use ($current_time, $timeout) {
            return ($current_time - $participant['last_seen']) < $timeout;
        });
    }

    /**
     * Cleanup old collaboration sessions
     */
    public function cleanup_old_sessions() {
        $sessions = get_option('yht_collaboration_sessions', array());
        $current_time = time();
        $timeout = 3600; // 1 hour

        $active_sessions = array_filter($sessions, function($session) use ($current_time, $timeout) {
            // Keep sessions that have active participants or recent activity
            $has_recent_activity = ($current_time - $session['created_at']) < $timeout;
            
            if (isset($session['participants'])) {
                $has_active_participants = !empty(array_filter($session['participants'], function($p) use ($current_time) {
                    return ($current_time - $p['last_seen']) < 300; // 5 minutes
                }));
                
                return $has_recent_activity || $has_active_participants;
            }
            
            return $has_recent_activity;
        });

        update_option('yht_collaboration_sessions', $active_sessions);
    }

    /**
     * Generate collaboration share link
     */
    public function generate_share_link($session_id, $trip_id) {
        $base_url = get_permalink($trip_id);
        $share_url = add_query_arg(array(
            'collaborate' => '1',
            'session' => $session_id
        ), $base_url);

        return $share_url;
    }

    /**
     * Save collaborative trip changes to database
     */
    public function save_collaborative_trip($session_id, $user_id = null) {
        $session = $this->get_collaboration_session($session_id);
        
        if (!$session) {
            return false;
        }

        $trip_id = $session['trip_id'];
        $trip_data = $session['trip_data'];

        // Only allow saving by authorized users
        if (!current_user_can('edit_post', $trip_id)) {
            return false;
        }

        // Update trip stops
        if (isset($trip_data['stops'])) {
            update_post_meta($trip_id, '_trip_stops', $trip_data['stops']);
        }

        // Update trip settings
        if (isset($trip_data['settings'])) {
            foreach ($trip_data['settings'] as $key => $value) {
                update_post_meta($trip_id, '_trip_' . $key, $value);
            }
        }

        // Update modification time
        wp_update_post(array(
            'ID' => $trip_id,
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', 1)
        ));

        // Log the save action
        if (class_exists('YHT_Logger')) {
            YHT_Logger::info('Collaborative trip saved', array(
                'trip_id' => $trip_id,
                'session_id' => $session_id,
                'user_id' => $user_id,
                'participants' => count($session['participants'])
            ));
        }

        return true;
    }
}