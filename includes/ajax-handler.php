<?php
class WP_AJAX_Search_Ajax {
    public static function init() {
        // AJAX handler for live search
        add_action('wp_ajax_wp_ajax_search', [__CLASS__, 'ajax_search']);
        add_action('wp_ajax_nopriv_wp_ajax_search', [__CLASS__, 'ajax_search']);
    }
    
    public static function ajax_search() {
        // Verify nonce
        check_ajax_referer('wp_ajax_search_nonce', 'nonce');
        
        // Get search term
        $search_term = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : '';
        
        if (empty($search_term)) {
            wp_send_json_error(__('Please enter a search term', 'WP-AJAX-Search'));
        }
        
        // Setup search query
        $args = [
            's' => $search_term,
            'posts_per_page' => 10,
            'post_status' => 'publish',
            'suppress_filters' => false
        ];
        
        // Get searchable post types
        $post_types = get_option('wp_ajax_search_post_types', ['post', 'page']);
        $args['post_type'] = $post_types;
        
        // Perform the search
        $search_query = new WP_Query($args);
        
        // Prepare results
        $results = [];
        
        if ($search_query->have_posts()) {
            while ($search_query->have_posts()) {
                $search_query->the_post();
                
                $results[] = [
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'excerpt' => wp_trim_words(get_the_excerpt(), 20),
                    'post_type' => get_post_type(),
                    'thumbnail' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail')
                ];
            }
            wp_reset_postdata();
        }
        
        // Return JSON response
        wp_send_json_success([
            'results' => $results,
            'count' => $search_query->found_posts,
            'search_term' => $search_term
        ]);
    }
}
