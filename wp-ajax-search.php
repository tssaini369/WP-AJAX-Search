<?php
/*
Plugin Name: WP AJAX Search
Description: Enhances default WordPress search to include tags, categories, authors and content with AJAX support.
Version: 1.0.1
Author: TeeJay
Author URI: https://buymeacoffee.com/TeeJayMusics
License: GPLv2 or later
*/

defined('ABSPATH') or die('No direct access allowed!');

// Define plugin constants
define('WP_AJAX_SEARCH_VERSION', '1.0');
define('WP_AJAX_SEARCH_PATH', plugin_dir_path(__FILE__));
define('WP_AJAX_SEARCH_URL', plugin_dir_url(__FILE__));

// Include required files
require_once WP_AJAX_SEARCH_PATH . 'includes/class-search-query.php';
require_once WP_AJAX_SEARCH_PATH . 'includes/ajax-handler.php';
require_once WP_AJAX_SEARCH_PATH . 'includes/settings.php';

// Initialize the plugin
WP_AJAX_Search::init();

class WP_AJAX_Search {
    public static function init() {
        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, [__CLASS__, 'activate']);
        register_deactivation_hook(__FILE__, [__CLASS__, 'deactivate']);
        
        // Initialize components
        WP_AJAX_Search_Query::init();
        WP_AJAX_Search_Ajax::init();
        WP_AJAX_Search_Settings::init();
        
        // Load assets
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        
    }
    
    public static function enqueue_assets() {
        // Only load on frontend
        if (is_admin()) return;
        
        // CSS
        wp_enqueue_style(
            'WP-AJAX-Search',
            WP_AJAX_SEARCH_URL . 'assets/css/style.css',
            [],
            WP_AJAX_SEARCH_VERSION
        );
        
        // JS
        wp_enqueue_script(
            'WP-AJAX-Search',
            WP_AJAX_SEARCH_URL . 'assets/js/script.js',
            ['jquery'],
            WP_AJAX_SEARCH_VERSION,
            true
        );
        
        // Localize script for AJAX URL
        wp_localize_script(
            'WP-AJAX-Search',
            'wp_ajax_search',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_ajax_search_nonce'),
                'enable_ajax' => get_option('wp_ajax_search_enable_ajax', true) ? 1 : 0,
                'loading_method' => get_option('wp_ajax_search_loading_method', 'pagination'),
            ]
        );
    }
    
    public static function activate() {
        // Activation code if needed
    }
    
    public static function deactivate() {
        // Deactivation code if needed
    }

}
