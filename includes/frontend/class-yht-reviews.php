<?php
/**
 * Reviews and Ratings System
 * 
 * @package YourHiddenTrip
 * @version 6.2
 */

if (!defined('ABSPATH')) exit;

class YHT_Reviews {
    
    public function __construct() {
        add_action('init', array($this, 'create_reviews_table'));
        add_action('wp_ajax_yht_submit_review', array($this, 'ajax_submit_review'));
        add_action('wp_ajax_nopriv_yht_submit_review', array($this, 'ajax_submit_review'));
        add_action('wp_ajax_yht_get_reviews', array($this, 'ajax_get_reviews'));
        add_action('wp_ajax_nopriv_yht_get_reviews', array($this, 'ajax_get_reviews'));
        add_shortcode('yht_reviews', array($this, 'render_reviews_shortcode'));
    }
    
    /**
     * Create reviews table on initialization
     */
    public function create_reviews_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'yht_reviews';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            trip_id varchar(100) NOT NULL,
            user_name varchar(100) NOT NULL,
            user_email varchar(100) NOT NULL,
            rating tinyint(1) NOT NULL,
            review_title varchar(255) DEFAULT '',
            review_text text DEFAULT '',
            review_date datetime DEFAULT CURRENT_TIMESTAMP,
            is_approved tinyint(1) DEFAULT 0,
            user_location varchar(100) DEFAULT '',
            trip_type varchar(100) DEFAULT '',
            PRIMARY KEY (id),
            KEY trip_id (trip_id),
            KEY rating (rating),
            KEY is_approved (is_approved)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Add some sample reviews if table is empty
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count == 0) {
            $this->add_sample_reviews();
        }
    }
    
    /**
     * Add sample reviews for demonstration
     */
    private function add_sample_reviews() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'yht_reviews';
        
        $sample_reviews = array(
            array(
                'trip_id' => 'viterbo_tuscia',
                'user_name' => 'Marco R.',
                'user_email' => 'marco@example.com',
                'rating' => 5,
                'review_title' => 'Esperienza fantastica!',
                'review_text' => 'Abbiamo trascorso un weekend meraviglioso alla scoperta della Tuscia. Organizzazione perfetta e guide molto preparate.',
                'is_approved' => 1,
                'user_location' => 'Roma',
                'trip_type' => 'enogastronomica'
            ),
            array(
                'trip_id' => 'orvieto_umbria',
                'user_name' => 'Giulia M.',
                'user_email' => 'giulia@example.com',
                'rating' => 5,
                'review_title' => 'Orvieto da sogno',
                'review_text' => 'Il Duomo di Orvieto è semplicemente mozzafiato. Il tour enogastronomico ci ha fatto scoprire sapori unici.',
                'is_approved' => 1,
                'user_location' => 'Firenze',
                'trip_type' => 'storico_culturale'
            ),
            array(
                'trip_id' => 'lago_bolsena',
                'user_name' => 'Andrea e Sara',
                'user_email' => 'andrea@example.com',
                'rating' => 4,
                'review_title' => 'Romantico weekend',
                'review_text' => 'Il lago di Bolsena è perfetto per una fuga romantica. Abbiamo cenato con vista lago, indimenticabile!',
                'is_approved' => 1,
                'user_location' => 'Milano',
                'trip_type' => 'romantica'
            ),
            array(
                'trip_id' => 'assisi_perugia',
                'user_name' => 'Famiglia Bianchi',
                'user_email' => 'bianchi@example.com',
                'rating' => 5,
                'review_title' => 'Perfetto per famiglie',
                'review_text' => 'I nostri bambini si sono divertiti molto. Assisi è magica e le attività erano adatte anche ai più piccoli.',
                'is_approved' => 1,
                'user_location' => 'Bologna',
                'trip_type' => 'famiglia'
            )
        );
        
        foreach ($sample_reviews as $review) {
            $wpdb->insert($table_name, $review);
        }
    }
    
    /**
     * AJAX handler for submitting reviews
     */
    public function ajax_submit_review() {
        check_ajax_referer('yht_nonce', 'nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'yht_reviews';
        
        $trip_id = sanitize_text_field($_POST['trip_id']);
        $user_name = sanitize_text_field($_POST['user_name']);
        $user_email = sanitize_email($_POST['user_email']);
        $rating = intval($_POST['rating']);
        $review_title = sanitize_text_field($_POST['review_title']);
        $review_text = sanitize_textarea_field($_POST['review_text']);
        $user_location = sanitize_text_field($_POST['user_location']);
        $trip_type = sanitize_text_field($_POST['trip_type']);
        
        // Validation
        if (empty($trip_id) || empty($user_name) || empty($user_email) || $rating < 1 || $rating > 5) {
            wp_die(__('Dati mancanti o non validi.', 'your-hidden-trip'));
        }
        
        // Check for duplicate reviews from same email for same trip
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE trip_id = %s AND user_email = %s",
            $trip_id, $user_email
        ));
        
        if ($existing > 0) {
            wp_send_json_error(__('Hai già inviato una recensione per questo viaggio.', 'your-hidden-trip'));
            return;
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'trip_id' => $trip_id,
                'user_name' => $user_name,
                'user_email' => $user_email,
                'rating' => $rating,
                'review_title' => $review_title,
                'review_text' => $review_text,
                'user_location' => $user_location,
                'trip_type' => $trip_type,
                'is_approved' => 0 // Requires approval
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(__('Recensione inviata con successo! Sarà pubblicata dopo la moderazione.', 'your-hidden-trip'));
        } else {
            wp_send_json_error(__('Errore nell\'invio della recensione.', 'your-hidden-trip'));
        }
    }
    
    /**
     * AJAX handler for getting reviews
     */
    public function ajax_get_reviews() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'yht_reviews';
        
        $trip_id = sanitize_text_field($_GET['trip_id'] ?? '');
        $limit = intval($_GET['limit'] ?? 10);
        $offset = intval($_GET['offset'] ?? 0);
        
        $where = "WHERE is_approved = 1";
        $params = array();
        
        if (!empty($trip_id)) {
            $where .= " AND trip_id = %s";
            $params[] = $trip_id;
        }
        
        $sql = "SELECT * FROM $table_name $where ORDER BY review_date DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        $reviews = $wpdb->get_results($wpdb->prepare($sql, $params));
        
        // Get average rating
        $avg_sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM $table_name $where";
        $avg_params = array_slice($params, 0, -2); // Remove limit and offset
        
        if (!empty($avg_params)) {
            $stats = $wpdb->get_row($wpdb->prepare($avg_sql, $avg_params));
        } else {
            $stats = $wpdb->get_row($avg_sql);
        }
        
        wp_send_json_success(array(
            'reviews' => $reviews,
            'average_rating' => round($stats->avg_rating, 1),
            'total_reviews' => $stats->total_reviews
        ));
    }
    
    /**
     * Get reviews for a specific trip
     */
    public function get_reviews($trip_id, $limit = 10) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'yht_reviews';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE trip_id = %s AND is_approved = 1 ORDER BY review_date DESC LIMIT %d",
            $trip_id, $limit
        ));
    }
    
    /**
     * Get average rating for a trip
     */
    public function get_average_rating($trip_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'yht_reviews';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM $table_name WHERE trip_id = %s AND is_approved = 1",
            $trip_id
        ));
        
        return array(
            'average' => $result ? round($result->avg_rating, 1) : 0,
            'count' => $result ? $result->total_reviews : 0
        );
    }
    
    /**
     * Render reviews shortcode
     */
    public function render_reviews_shortcode($atts) {
        $atts = shortcode_atts(array(
            'trip_id' => '',
            'limit' => 5,
            'show_form' => 'true'
        ), $atts);
        
        $reviews = $this->get_reviews($atts['trip_id'], $atts['limit']);
        $rating_data = $this->get_average_rating($atts['trip_id']);
        
        ob_start();
        ?>
        <div class="yht-reviews-container">
            <div class="yht-reviews-header">
                <h3><?php _e('Recensioni dei viaggiatori', 'your-hidden-trip'); ?></h3>
                <?php if ($rating_data['count'] > 0): ?>
                    <div class="yht-rating-summary">
                        <div class="rating-stars">
                            <?php echo $this->render_stars($rating_data['average']); ?>
                            <span class="rating-number"><?php echo $rating_data['average']; ?></span>
                        </div>
                        <div class="rating-count">
                            <?php printf(__('Basato su %d recensioni', 'your-hidden-trip'), $rating_data['count']); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="yht-reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <div class="yht-review-item">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <strong class="reviewer-name"><?php echo esc_html($review->user_name); ?></strong>
                                <span class="reviewer-location"><?php echo esc_html($review->user_location); ?></span>
                            </div>
                            <div class="review-meta">
                                <div class="review-rating"><?php echo $this->render_stars($review->rating); ?></div>
                                <div class="review-date"><?php echo date_i18n('d/m/Y', strtotime($review->review_date)); ?></div>
                            </div>
                        </div>
                        <?php if (!empty($review->review_title)): ?>
                            <h4 class="review-title"><?php echo esc_html($review->review_title); ?></h4>
                        <?php endif; ?>
                        <p class="review-text"><?php echo esc_html($review->review_text); ?></p>
                        <?php if (!empty($review->trip_type)): ?>
                            <div class="review-tag"><?php echo ucfirst(str_replace('_', ' ', $review->trip_type)); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($atts['show_form'] === 'true'): ?>
                <div class="yht-review-form-container">
                    <h4><?php _e('Scrivi una recensione', 'your-hidden-trip'); ?></h4>
                    <form class="yht-review-form" data-trip-id="<?php echo esc_attr($atts['trip_id']); ?>">
                        <div class="form-row">
                            <input type="text" name="user_name" placeholder="<?php _e('Il tuo nome', 'your-hidden-trip'); ?>" required>
                            <input type="email" name="user_email" placeholder="<?php _e('La tua email', 'your-hidden-trip'); ?>" required>
                        </div>
                        <div class="form-row">
                            <input type="text" name="user_location" placeholder="<?php _e('La tua città', 'your-hidden-trip'); ?>">
                            <select name="trip_type">
                                <option value=""><?php _e('Tipo di esperienza', 'your-hidden-trip'); ?></option>
                                <option value="enogastronomica"><?php _e('Enogastronomica', 'your-hidden-trip'); ?></option>
                                <option value="storico_culturale"><?php _e('Storico-Culturale', 'your-hidden-trip'); ?></option>
                                <option value="natura_relax"><?php _e('Natura e Relax', 'your-hidden-trip'); ?></option>
                                <option value="avventura"><?php _e('Avventura', 'your-hidden-trip'); ?></option>
                                <option value="romantica"><?php _e('Romantica', 'your-hidden-trip'); ?></option>
                                <option value="famiglia"><?php _e('Famiglia', 'your-hidden-trip'); ?></option>
                            </select>
                        </div>
                        <div class="rating-input">
                            <label><?php _e('Valutazione:', 'your-hidden-trip'); ?></label>
                            <div class="star-rating-input">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                                    <label for="star<?php echo $i; ?>" class="star">★</label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <input type="text" name="review_title" placeholder="<?php _e('Titolo della recensione', 'your-hidden-trip'); ?>">
                        <textarea name="review_text" rows="4" placeholder="<?php _e('Racconta la tua esperienza...', 'your-hidden-trip'); ?>" required></textarea>
                        <button type="submit" class="yht-btn yht-btn-primary"><?php _e('Invia Recensione', 'your-hidden-trip'); ?></button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .yht-reviews-container {
            font-family: Inter, system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
        }
        
        .yht-reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .yht-rating-summary {
            text-align: right;
        }
        
        .rating-stars {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #fbbf24;
            font-size: 1.1rem;
        }
        
        .rating-number {
            font-weight: 700;
            color: #111827;
            margin-left: 6px;
        }
        
        .rating-count {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .yht-review-item {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        
        .reviewer-name {
            color: #111827;
            font-size: 1rem;
        }
        
        .reviewer-location {
            color: #6b7280;
            font-size: 0.9rem;
            margin-left: 8px;
        }
        
        .review-meta {
            text-align: right;
        }
        
        .review-rating {
            color: #fbbf24;
            margin-bottom: 4px;
        }
        
        .review-date {
            color: #9ca3af;
            font-size: 0.8rem;
        }
        
        .review-title {
            color: #111827;
            font-size: 1.1rem;
            margin: 8px 0;
            font-weight: 600;
        }
        
        .review-text {
            color: #374151;
            line-height: 1.6;
            margin-bottom: 12px;
        }
        
        .review-tag {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .yht-review-form-container {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            margin-top: 24px;
        }
        
        .yht-review-form .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .yht-review-form input,
        .yht-review-form select,
        .yht-review-form textarea {
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        
        .rating-input {
            margin: 16px 0;
        }
        
        .star-rating-input {
            display: flex;
            gap: 4px;
            margin-top: 8px;
        }
        
        .star-rating-input input[type="radio"] {
            display: none;
        }
        
        .star-rating-input .star {
            font-size: 1.5rem;
            color: #d1d5db;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .star-rating-input input[type="radio"]:checked ~ .star,
        .star-rating-input .star:hover,
        .star-rating-input .star:hover ~ .star {
            color: #fbbf24;
        }
        
        @media (max-width: 768px) {
            .yht-reviews-header {
                flex-direction: column;
                text-align: center;
            }
            
            .yht-rating-summary {
                text-align: center;
            }
            
            .review-header {
                flex-direction: column;
                gap: 8px;
            }
            
            .review-meta {
                text-align: left;
            }
            
            .yht-review-form .form-row {
                grid-template-columns: 1fr;
            }
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.yht-review-form');
            if (!form) return;
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                formData.append('action', 'yht_submit_review');
                formData.append('nonce', yhtSettings.nonce);
                formData.append('trip_id', form.dataset.tripId);
                
                fetch(yhtSettings.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.data);
                        form.reset();
                    } else {
                        alert(data.data || 'Errore nell\'invio');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Errore di connessione');
                });
            });
            
            // Star rating interaction
            const stars = document.querySelectorAll('.star-rating-input .star');
            stars.forEach((star, index) => {
                star.addEventListener('click', function() {
                    const rating = index + 1;
                    const radioInput = document.querySelector(`input[name="rating"][value="${rating}"]`);
                    if (radioInput) radioInput.checked = true;
                    
                    // Update visual state
                    stars.forEach((s, i) => {
                        if (i <= index) {
                            s.style.color = '#fbbf24';
                        } else {
                            s.style.color = '#d1d5db';
                        }
                    });
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render star rating HTML
     */
    private function render_stars($rating) {
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5;
        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
        
        $html = '';
        
        // Full stars
        for ($i = 0; $i < $full_stars; $i++) {
            $html .= '<span class="star filled">★</span>';
        }
        
        // Half star
        if ($half_star) {
            $html .= '<span class="star half">★</span>';
        }
        
        // Empty stars
        for ($i = 0; $i < $empty_stars; $i++) {
            $html .= '<span class="star empty">☆</span>';
        }
        
        return $html;
    }
}

// Initialize reviews system
new YHT_Reviews();