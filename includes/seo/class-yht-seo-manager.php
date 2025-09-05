<?php
/**
 * Advanced SEO system with Schema.org structured data
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_SEO_Manager {
    
    /**
     * Initialize SEO manager
     */
    public function __construct() {
        add_action('wp_head', array($this, 'add_structured_data'), 5);
        add_action('wp_head', array($this, 'add_meta_tags'), 10);
        add_filter('document_title_parts', array($this, 'filter_title_parts'));
        add_filter('wp_title', array($this, 'filter_wp_title'), 10, 2);
        add_action('init', array($this, 'init_sitemaps'));
        add_action('wp_head', array($this, 'add_opengraph_tags'));
        add_action('wp_head', array($this, 'add_twitter_cards'));
    }
    
    /**
     * Add structured data (Schema.org JSON-LD)
     */
    public function add_structured_data() {
        global $post;
        
        if (!$post) return;
        
        $structured_data = [];
        
        // Organization schema (always include)
        $structured_data[] = $this->get_organization_schema();
        
        // Page-specific schema
        switch ($post->post_type) {
            case 'yht_luogo':
                $structured_data[] = $this->get_place_schema($post);
                break;
                
            case 'yht_tour':
                $structured_data[] = $this->get_tour_schema($post);
                break;
                
            case 'yht_alloggio':
                $structured_data[] = $this->get_accommodation_schema($post);
                break;
                
            case 'page':
                if (is_front_page()) {
                    $structured_data[] = $this->get_website_schema();
                } else {
                    $structured_data[] = $this->get_webpage_schema($post);
                }
                break;
                
            case 'post':
                $structured_data[] = $this->get_article_schema($post);
                break;
        }
        
        // Add breadcrumb schema
        if (!is_front_page()) {
            $structured_data[] = $this->get_breadcrumb_schema();
        }
        
        // Output structured data
        foreach ($structured_data as $schema) {
            if (!empty($schema)) {
                echo '<script type="application/ld+json">' . "\n";
                echo json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
                echo '</script>' . "\n";
            }
        }
    }
    
    /**
     * Get organization schema
     * 
     * @return array
     */
    private function get_organization_schema() {
        $logo = get_option('yht_logo_url', '');
        $social_profiles = get_option('yht_social_profiles', []);
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'TravelAgency',
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url' => home_url(),
            'telephone' => get_option('yht_phone', ''),
            'email' => get_option('admin_email'),
            'address' => [
                '@type' => 'PostalAddress',
                'addressCountry' => 'IT',
                'addressRegion' => get_option('yht_region', 'Lazio'),
                'addressLocality' => get_option('yht_city', ''),
                'postalCode' => get_option('yht_postal_code', ''),
                'streetAddress' => get_option('yht_address', '')
            ]
        ];
        
        if ($logo) {
            $schema['logo'] = [
                '@type' => 'ImageObject',
                'url' => $logo
            ];
        }
        
        if (!empty($social_profiles)) {
            $schema['sameAs'] = array_values($social_profiles);
        }
        
        return $schema;
    }
    
    /**
     * Get place schema for locations
     * 
     * @param WP_Post $post
     * @return array
     */
    private function get_place_schema($post) {
        $coordinates = get_post_meta($post->ID, 'yht_luogo_coordinates', true);
        $category = get_post_meta($post->ID, 'yht_luogo_category', true);
        $price = get_post_meta($post->ID, 'yht_luogo_price_per_pax', true);
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $this->get_place_type($category),
            'name' => $post->post_title,
            'description' => $post->post_excerpt ?: wp_trim_words($post->post_content, 30),
            'url' => get_permalink($post->ID)
        ];
        
        // Add coordinates if available
        if ($coordinates) {
            $coords = explode(',', $coordinates);
            if (count($coords) === 2) {
                $schema['geo'] = [
                    '@type' => 'GeoCoordinates',
                    'latitude' => trim($coords[0]),
                    'longitude' => trim($coords[1])
                ];
            }
        }
        
        // Add price information
        if ($price) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => 'EUR',
                'availability' => 'https://schema.org/InStock'
            ];
        }
        
        // Add images
        $featured_image = get_the_post_thumbnail_url($post->ID, 'large');
        if ($featured_image) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => $featured_image
            ];
        }
        
        return $schema;
    }
    
    /**
     * Get tour schema
     * 
     * @param WP_Post $post
     * @return array
     */
    private function get_tour_schema($post) {
        $duration = get_post_meta($post->ID, 'yht_tour_duration', true);
        $difficulty = get_post_meta($post->ID, 'yht_tour_difficulty', true);
        $price = get_post_meta($post->ID, 'yht_tour_price', true);
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'TouristTrip',
            'name' => $post->post_title,
            'description' => $post->post_excerpt ?: wp_trim_words($post->post_content, 30),
            'url' => get_permalink($post->ID),
            'provider' => [
                '@type' => 'TravelAgency',
                'name' => get_bloginfo('name'),
                'url' => home_url()
            ]
        ];
        
        // Add duration
        if ($duration) {
            $schema['duration'] = 'P' . $duration . 'D'; // ISO 8601 duration format
        }
        
        // Add price
        if ($price) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => 'EUR',
                'availability' => 'https://schema.org/InStock'
            ];
        }
        
        // Add featured image
        $featured_image = get_the_post_thumbnail_url($post->ID, 'large');
        if ($featured_image) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => $featured_image
            ];
        }
        
        return $schema;
    }
    
    /**
     * Get accommodation schema
     * 
     * @param WP_Post $post
     * @return array
     */
    private function get_accommodation_schema($post) {
        $rating = get_post_meta($post->ID, 'yht_alloggio_rating', true);
        $price = get_post_meta($post->ID, 'yht_alloggio_price_per_night', true);
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LodgingBusiness',
            'name' => $post->post_title,
            'description' => $post->post_excerpt ?: wp_trim_words($post->post_content, 30),
            'url' => get_permalink($post->ID)
        ];
        
        // Add rating
        if ($rating) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $rating,
                'bestRating' => '5',
                'worstRating' => '1'
            ];
        }
        
        // Add price
        if ($price) {
            $schema['priceRange'] = '€' . $price . ' per night';
        }
        
        return $schema;
    }
    
    /**
     * Get website schema (for homepage)
     * 
     * @return array
     */
    private function get_website_schema() {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url' => home_url(),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => home_url('/?s={search_term_string}')
                ],
                'query-input' => 'required name=search_term_string'
            ]
        ];
    }
    
    /**
     * Get webpage schema
     * 
     * @param WP_Post $post
     * @return array
     */
    private function get_webpage_schema($post) {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $post->post_title,
            'description' => $post->post_excerpt ?: wp_trim_words($post->post_content, 30),
            'url' => get_permalink($post->ID),
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => get_bloginfo('name'),
                'url' => home_url()
            ]
        ];
    }
    
    /**
     * Get article schema
     * 
     * @param WP_Post $post
     * @return array
     */
    private function get_article_schema($post) {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->post_title,
            'description' => $post->post_excerpt ?: wp_trim_words($post->post_content, 30),
            'url' => get_permalink($post->ID),
            'datePublished' => get_the_date('c', $post->ID),
            'dateModified' => get_the_modified_date('c', $post->ID),
            'author' => [
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', $post->post_author)
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url()
            ]
        ];
    }
    
    /**
     * Get breadcrumb schema
     * 
     * @return array
     */
    private function get_breadcrumb_schema() {
        $breadcrumbs = [];
        $position = 1;
        
        // Home
        $breadcrumbs[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Home',
            'item' => home_url()
        ];
        
        // Add current page
        global $post;
        if ($post) {
            $breadcrumbs[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $post->post_title,
                'item' => get_permalink($post->ID)
            ];
        }
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbs
        ];
    }
    
    /**
     * Get place type based on category
     * 
     * @param string $category
     * @return string
     */
    private function get_place_type($category) {
        $types = [
            'natura' => 'Park',
            'storia' => 'HistoricalSite',
            'cultura' => 'Museum',
            'gastronomia' => 'Restaurant',
            'benessere' => 'HealthAndBeautyBusiness',
            'avventura' => 'TouristAttraction',
            'religioso' => 'PlaceOfWorship'
        ];
        
        return $types[$category] ?? 'TouristAttraction';
    }
    
    /**
     * Add meta tags
     */
    public function add_meta_tags() {
        global $post;
        
        if (!$post) return;
        
        // Meta description
        $description = $this->get_meta_description($post);
        if ($description) {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }
        
        // Meta keywords
        $keywords = $this->get_meta_keywords($post);
        if ($keywords) {
            echo '<meta name="keywords" content="' . esc_attr($keywords) . '">' . "\n";
        }
        
        // Canonical URL
        echo '<link rel="canonical" href="' . esc_url(get_permalink($post->ID)) . '">' . "\n";
    }
    
    /**
     * Add OpenGraph tags
     */
    public function add_opengraph_tags() {
        global $post;
        
        if (!$post) return;
        
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($post->post_title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($this->get_meta_description($post)) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_permalink($post->ID)) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
        
        $featured_image = get_the_post_thumbnail_url($post->ID, 'large');
        if ($featured_image) {
            echo '<meta property="og:image" content="' . esc_url($featured_image) . '">' . "\n";
        }
    }
    
    /**
     * Add Twitter Card tags
     */
    public function add_twitter_cards() {
        global $post;
        
        if (!$post) return;
        
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($post->post_title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($this->get_meta_description($post)) . '">' . "\n";
        
        $featured_image = get_the_post_thumbnail_url($post->ID, 'large');
        if ($featured_image) {
            echo '<meta name="twitter:image" content="' . esc_url($featured_image) . '">' . "\n";
        }
        
        $twitter_handle = get_option('yht_twitter_handle', '');
        if ($twitter_handle) {
            echo '<meta name="twitter:site" content="@' . esc_attr($twitter_handle) . '">' . "\n";
        }
    }
    
    /**
     * Get meta description for a post
     * 
     * @param WP_Post $post
     * @return string
     */
    private function get_meta_description($post) {
        // Use excerpt if available
        if ($post->post_excerpt) {
            return wp_trim_words($post->post_excerpt, 25);
        }
        
        // Otherwise use content
        return wp_trim_words(strip_tags($post->post_content), 25);
    }
    
    /**
     * Get meta keywords for a post
     * 
     * @param WP_Post $post
     * @return string
     */
    private function get_meta_keywords($post) {
        $keywords = [];
        
        // Add post type specific keywords
        switch ($post->post_type) {
            case 'yht_luogo':
                $keywords[] = 'luogo di interesse';
                $keywords[] = 'tuscia';
                $keywords[] = 'umbria';
                break;
                
            case 'yht_tour':
                $keywords[] = 'tour';
                $keywords[] = 'viaggio';
                $keywords[] = 'escursione';
                break;
                
            case 'yht_alloggio':
                $keywords[] = 'alloggio';
                $keywords[] = 'hotel';
                $keywords[] = 'bed and breakfast';
                break;
        }
        
        // Add general keywords
        $keywords[] = 'your hidden trip';
        $keywords[] = 'trip planner';
        $keywords[] = 'viaggi personalizzati';
        
        return implode(', ', $keywords);
    }
    
    /**
     * Filter document title parts
     * 
     * @param array $title_parts
     * @return array
     */
    public function filter_title_parts($title_parts) {
        global $post;
        
        if (!$post) return $title_parts;
        
        // Customize title for different post types
        switch ($post->post_type) {
            case 'yht_luogo':
                $title_parts['title'] = $post->post_title . ' - Luogo di Interesse';
                break;
                
            case 'yht_tour':
                $title_parts['title'] = $post->post_title . ' - Tour Guidato';
                break;
                
            case 'yht_alloggio':
                $title_parts['title'] = $post->post_title . ' - Alloggio';
                break;
        }
        
        return $title_parts;
    }
    
    /**
     * Filter wp_title (fallback for older themes)
     * 
     * @param string $title
     * @param string $sep
     * @return string
     */
    public function filter_wp_title($title, $sep) {
        global $post;
        
        if (!$post) return $title;
        
        // Customize for post types
        switch ($post->post_type) {
            case 'yht_luogo':
                return $post->post_title . ' - Luogo di Interesse ' . $sep . ' ' . get_bloginfo('name');
                
            case 'yht_tour':
                return $post->post_title . ' - Tour Guidato ' . $sep . ' ' . get_bloginfo('name');
                
            case 'yht_alloggio':
                return $post->post_title . ' - Alloggio ' . $sep . ' ' . get_bloginfo('name');
        }
        
        return $title;
    }
    
    /**
     * Initialize XML sitemaps
     */
    public function init_sitemaps() {
        add_action('init', array($this, 'add_sitemap_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_sitemap_request'));
    }
    
    /**
     * Add sitemap rewrite rules
     */
    public function add_sitemap_rewrite_rules() {
        add_rewrite_rule('^sitemap\.xml$', 'index.php?yht_sitemap=main', 'top');
        add_rewrite_rule('^sitemap-([^.]+)\.xml$', 'index.php?yht_sitemap=$matches[1]', 'top');
    }
    
    /**
     * Handle sitemap requests
     */
    public function handle_sitemap_request() {
        $sitemap_type = get_query_var('yht_sitemap');
        
        if (!$sitemap_type) return;
        
        header('Content-Type: application/xml; charset=UTF-8');
        header('X-Robots-Tag: noindex, follow');
        
        switch ($sitemap_type) {
            case 'main':
                $this->output_main_sitemap();
                break;
                
            case 'luoghi':
                $this->output_luoghi_sitemap();
                break;
                
            case 'tour':
                $this->output_tour_sitemap();
                break;
                
            case 'alloggi':
                $this->output_alloggi_sitemap();
                break;
        }
        
        exit;
    }
    
    /**
     * Output main sitemap index
     */
    private function output_main_sitemap() {
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        $sitemaps = [
            'luoghi' => 'Luoghi di Interesse',
            'tour' => 'Tour',
            'alloggi' => 'Alloggi'
        ];
        
        foreach ($sitemaps as $type => $name) {
            echo '<sitemap>' . "\n";
            echo '<loc>' . home_url("sitemap-{$type}.xml") . '</loc>' . "\n";
            echo '<lastmod>' . date('c') . '</lastmod>' . "\n";
            echo '</sitemap>' . "\n";
        }
        
        echo '</sitemapindex>' . "\n";
    }
    
    /**
     * Output luoghi sitemap
     */
    private function output_luoghi_sitemap() {
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        $posts = get_posts([
            'post_type' => 'yht_luogo',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        foreach ($posts as $post) {
            echo '<url>' . "\n";
            echo '<loc>' . get_permalink($post->ID) . '</loc>' . "\n";
            echo '<lastmod>' . date('c', strtotime($post->post_modified)) . '</lastmod>' . "\n";
            echo '<changefreq>weekly</changefreq>' . "\n";
            echo '<priority>0.8</priority>' . "\n";
            echo '</url>' . "\n";
        }
        
        echo '</urlset>' . "\n";
    }
    
    /**
     * Output tour sitemap
     */
    private function output_tour_sitemap() {
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        $posts = get_posts([
            'post_type' => 'yht_tour',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        foreach ($posts as $post) {
            echo '<url>' . "\n";
            echo '<loc>' . get_permalink($post->ID) . '</loc>' . "\n";
            echo '<lastmod>' . date('c', strtotime($post->post_modified)) . '</lastmod>' . "\n";
            echo '<changefreq>weekly</changefreq>' . "\n";
            echo '<priority>0.9</priority>' . "\n";
            echo '</url>' . "\n";
        }
        
        echo '</urlset>' . "\n";
    }
    
    /**
     * Output alloggi sitemap
     */
    private function output_alloggi_sitemap() {
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        $posts = get_posts([
            'post_type' => 'yht_alloggio',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        foreach ($posts as $post) {
            echo '<url>' . "\n";
            echo '<loc>' . get_permalink($post->ID) . '</loc>' . "\n";
            echo '<lastmod>' . date('c', strtotime($post->post_modified)) . '</lastmod>' . "\n";
            echo '<changefreq>monthly</changefreq>' . "\n";
            echo '<priority>0.7</priority>' . "\n";
            echo '</url>' . "\n";
        }
        
        echo '</urlset>' . "\n";
    }
}