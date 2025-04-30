<?php
class WP_AJAX_Search_Query {
    public static function init() {
        // Hook into the main search query
        add_action('pre_get_posts', [__CLASS__, 'modify_search_query']);
    }
    
    public static function modify_search_query($query) {
        if (!is_admin() && $query->is_main_query() && $query->is_search()) {
            // Get search term
            $search_term = $query->get('s');
            
            // Don't modify empty searches
            if (empty($search_term)) return;
            
            // Set to not use the default search logic
            $query->set('suppress_filters', true);
            
            // Get searchable post types (default to public types)
            $post_types = get_option('wp_ajax_search_post_types', ['post', 'page']);
            
            // Get search fields from settings
            $search_fields = get_option('wp_ajax_search_fields', [
                'title' => 5,    // Higher weight for title matches
                'content' => 1,
                'excerpt' => 1,
                'tags' => 2,
                'categories' => 2,
                'author' => 1,
                'custom_fields' => 1
            ]);
            
            // Set query parameters
            $query->set('post_type', $post_types);
            
            // Modify the WHERE clause to include additional fields
            add_filter('posts_where', [__CLASS__, 'search_where'], 10, 2);
            
            // Modify the JOIN clause to include taxonomies and meta
            add_filter('posts_join', [__CLASS__, 'search_join'], 10, 2);
            
            // Modify the ORDER BY to prioritize better matches
            add_filter('posts_orderby', [__CLASS__, 'search_orderby'], 10, 2);
        }
    }
    
    public static function search_where($where, $query) {
        global $wpdb;
        
        if ($query->is_search() && !is_admin()) {
            $search_term = $query->get('s');
            $search_term = esc_sql($wpdb->esc_like($search_term));
            
            // Start building the WHERE clause
            $where = " AND (";
            
            // Get search fields from settings
            $search_fields = get_option('wp_ajax_search_fields', []);
            
            // Title search
            if (isset($search_fields['title']) && $search_fields['title'] > 0) {
                $where .= "({$wpdb->posts}.post_title LIKE '%{$search_term}%') OR ";
            }
            
            // Content search
            if (isset($search_fields['content']) && $search_fields['content'] > 0) {
                $where .= "({$wpdb->posts}.post_content LIKE '%{$search_term}%') OR ";
            }
            
            // Excerpt search
            if (isset($search_fields['excerpt']) && $search_fields['excerpt'] > 0) {
                $where .= "({$wpdb->posts}.post_excerpt LIKE '%{$search_term}%') OR ";
            }
            
            // Author search
            if (isset($search_fields['author']) && $search_fields['author'] > 0) {
                $where .= "((SELECT COUNT(*) FROM {$wpdb->users} 
                             WHERE {$wpdb->users}.display_name LIKE '%{$search_term}%'
                             AND {$wpdb->users}.ID = {$wpdb->posts}.post_author) > 0) OR ";
            }
            
            // Remove the trailing OR and close the parentheses
            $where = rtrim($where, "OR ") . ")";
        }
        
        return $where;
    }
    
    public static function search_join($join, $query) {
        global $wpdb;
        
        if ($query->is_search() && !is_admin()) {
            $search_fields = get_option('wp_ajax_search_fields', []);
            
            // Join with postmeta if custom fields are enabled
            if (isset($search_fields['custom_fields']) && $search_fields['custom_fields'] > 0) {
                $join .= " LEFT JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id ";
            }
            
            // Join with taxonomies if tags or categories are enabled
            if ((isset($search_fields['tags']) && $search_fields['tags'] > 0) || 
                (isset($search_fields['categories']) && $search_fields['categories'] > 0)) {
                $join .= " LEFT JOIN {$wpdb->term_relationships} ON {$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id ";
                $join .= " LEFT JOIN {$wpdb->term_taxonomy} ON {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id ";
                $join .= " LEFT JOIN {$wpdb->terms} ON {$wpdb->term_taxonomy}.term_id = {$wpdb->terms}.term_id ";
            }
        }
        
        return $join;
    }
    
    public static function search_orderby($orderby, $query) {
        global $wpdb;
        
        if ($query->is_search() && !is_admin()) {
            $search_term = $query->get('s');
            $search_term = esc_sql($wpdb->esc_like($search_term));
            $search_fields = get_option('wp_ajax_search_fields', []);
            
            // Start building the ORDER BY clause
            $orderby = "CASE ";
            
            // Title matches get highest priority
            if (isset($search_fields['title']) && $search_fields['title'] > 0) {
                $orderby .= "WHEN {$wpdb->posts}.post_title LIKE '%{$search_term}%' THEN {$search_fields['title']} ";
            }
            
            // Then other fields
            if (isset($search_fields['content']) && $search_fields['content'] > 0) {
                $orderby .= "WHEN {$wpdb->posts}.post_content LIKE '%{$search_term}%' THEN {$search_fields['content']} ";
            }
            
            if (isset($search_fields['excerpt']) && $search_fields['excerpt'] > 0) {
                $orderby .= "WHEN {$wpdb->posts}.post_excerpt LIKE '%{$search_term}%' THEN {$search_fields['excerpt']} ";
            }
            
            if (isset($search_fields['tags']) && $search_fields['tags'] > 0) {
                $orderby .= "WHEN {$wpdb->terms}.name LIKE '%{$search_term}%' AND {$wpdb->term_taxonomy}.taxonomy = 'post_tag' THEN {$search_fields['tags']} ";
            }
            
            if (isset($search_fields['categories']) && $search_fields['categories'] > 0) {
                $orderby .= "WHEN {$wpdb->terms}.name LIKE '%{$search_term}%' AND {$wpdb->term_taxonomy}.taxonomy = 'category' THEN {$search_fields['categories']} ";
            }
            
            if (isset($search_fields['author']) && $search_fields['author'] > 0) {
                $orderby .= "WHEN (SELECT COUNT(*) FROM {$wpdb->users} 
                              WHERE {$wpdb->users}.display_name LIKE '%{$search_term}%'
                              AND {$wpdb->users}.ID = {$wpdb->posts}.post_author) > 0 THEN {$search_fields['author']} ";
            }
            
            if (isset($search_fields['custom_fields']) && $search_fields['custom_fields'] > 0) {
                $orderby .= "WHEN {$wpdb->postmeta}.meta_value LIKE '%{$search_term}%' THEN {$search_fields['custom_fields']} ";
            }
            
            $orderby .= "ELSE 0 END DESC, {$wpdb->posts}.post_date DESC";
        }
        
        return $orderby;
    }
}