<?php

defined('ABSPATH') or die('No direct access allowed!');

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
        
        // Get page number for pagination
        $page = isset($_REQUEST['page']) ? max(1, intval($_REQUEST['page'])) : 1;

        // Get search fields from settings
        $search_fields = get_option('wp_ajax_search_fields', [
            'title' => 5,
            'content' => 1,
            'excerpt' => 1,
            'categories' => 2,
            'tags' => 2,
            'author' => 1,
            'custom_fields' => 1,
        ]);

        // Setup search query
        $args = [
            's' => $search_term,
            'posts_per_page' => 10,
            'paged' => $page,
            'post_status' => 'publish',
            'suppress_filters' => false,
        ];
        
        // Get searchable post types
        $post_types = get_option('wp_ajax_search_post_types', ['post', 'page']);
        $args['post_type'] = $post_types;

        // Taxonomy search
        $tax_query = ['relation' => 'OR'];
        
        if (!empty($search_fields['categories'])) {
            $tax_query[] = [
                'taxonomy' => 'category',
                'field'    => 'name',
                'terms'    => $search_term,
                'operator' => 'LIKE'
            ];
            if (class_exists('WooCommerce')) {
                $tax_query[] = [
                    'taxonomy' => 'product_cat',
                    'field'    => 'name',
                    'terms'    => $search_term,
                    'operator' => 'LIKE'
                ];
            }
        }
        
        if (!empty($search_fields['tags'])) {
            $tax_query[] = [
                'taxonomy' => 'post_tag',
                'field'    => 'name',
                'terms'    => $search_term,
                'operator' => 'LIKE'
            ];
            if (class_exists('WooCommerce')) {
                $tax_query[] = [
                    'taxonomy' => 'product_tag',
                    'field'    => 'name',
                    'terms'    => $search_term,
                    'operator' => 'LIKE'
                ];
            }
        }
        
        // WooCommerce attributes (e.g., pa_brand)
        if (function_exists('wc_get_attribute_taxonomies')) {
            $attribute_taxonomies = wc_get_attribute_taxonomies();
            foreach ($attribute_taxonomies as $tax) {
                $tax_query[] = [
                    'taxonomy' => 'pa_' . $tax->attribute_name,
                    'field'    => 'name',
                    'terms'    => $search_term,
                    'operator' => 'LIKE'
                ];
            }
        }
        
        if (count($tax_query) > 1) { // Only if we have actual tax queries
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- LIKE operator is required for flexible search
            $args['tax_query'] = $tax_query;
        }

        // Author search
        if (!empty($search_fields['author'])) {
            $author_query = new WP_User_Query([
                'search'         => '*' . esc_attr($search_term) . '*',
                'search_columns' => ['display_name', 'user_nicename', 'user_login'],
                'fields'         => 'ID',
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- META operator is required for flexible search
                'meta_query'     => [
                    'relation' => 'OR',
                    [
                        'key'     => 'dokan_store_name',
                        'value'   => $search_term,
                        'compare' => 'LIKE'
                    ]
                ]
            ]);
            $author_ids = $author_query->get_results();
            if (!empty($author_ids)) {
                $args['author__in'] = $author_ids;
            }
        }
        
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
            'search_term' => $search_term,
            'page' => $page,
            'max_num_pages' => $search_query->max_num_pages
        ]);
    }
}
