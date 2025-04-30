<?php

defined('ABSPATH') or die('No direct access allowed!');

class WP_AJAX_Search_Settings {
    public static function init() {
        // Add settings page
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }
    
    public static function add_settings_page() {
        // Change 'manage_options' to 'edit_others_posts' if you want editors to access
        add_options_page(
            'WP AJAX Search Settings',
            'Search Settings',
            'manage_options',
            'wp-ajax-search', // <-- lowercase
            [__CLASS__, 'render_settings_page']
        );
    }
    
    public static function register_settings() {
        // Register settings sections with correct IDs
        add_settings_section(
            'general_section',  // Section ID
            'General Settings', // Title
            [__CLASS__, 'render_general_section'],
            'wp-ajax-search'    // Page slug (lowercase)
        );
        
        add_settings_section(
            'fields_section',   // Section ID
            'Search Fields & Weighting',
            [__CLASS__, 'render_fields_section'],
            'wp-ajax-search'    // Page slug (lowercase)
        );

        // Register settings
        register_setting('wp_ajax_search_settings', 'wp_ajax_search_post_types', [
            'type' => 'array',
            'sanitize_callback' => function($input) {
                return array_map('sanitize_text_field', (array)$input);
            },
        ]);
        register_setting('wp_ajax_search_settings', 'wp_ajax_search_fields', [
            'type' => 'array',
            'sanitize_callback' => function($input) {
                $sanitized = [];
                foreach ((array)$input as $key => $value) {
                    $sanitized[sanitize_text_field($key)] = is_numeric($value) ? intval($value) : sanitize_text_field($value);
                }
                return $sanitized;
            },
        ]);
        register_setting('wp_ajax_search_settings', 'wp_ajax_search_enable_ajax', [
            'type' => 'boolean',
            'sanitize_callback' => function($input) {
                return $input ? 1 : 0;
            },
        ]);
        register_setting('wp_ajax_search_settings', 'wp_ajax_search_loading_method', [
            'type' => 'string',
            'sanitize_callback' => function($input) {
                return in_array($input, ['pagination', 'infinite'], true) ? $input : 'pagination';
            },
        ]);
    }
    
    public static function render_settings_page() {
        ?>
        <style>
        /* WP AJAX Search Settings Admin Styling */
        .wp-ajax-search-settings {
            display: flex;
            gap: 40px;
            margin: 20px 0;
        }
        
        .settings-column {
            flex: 1;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .settings-column + .settings-column {
            border-left: 1px solid #ddd;
        }
        
        .settings-column h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #0073aa;
            color: #23282d;
        }
        
        .form-table {
            margin-top: 0;
        }
        
        .form-table th {
            width: 180px;
            padding: 15px 10px 15px 0;
        }
        
        .form-table td label {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .form-table td label input[type="number"] {
            width: 60px;
            margin-left: 10px;
            padding: 4px 8px;
        }
        
        .form-table td label input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .form-table td select {
            min-width: 200px;
        }
        
        .form-table td p small {
            color: #666;
            display: block;
            margin-top: 5px;
        }
        
        .submit-row {
            text-align: center;
            margin: 20px 0;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .submit-row .submit {
            text-align: center !important;
        }
        .submit-row .submit input[type="submit"] {
            float: none !important;
            margin-left: auto;
            margin-right: auto;
            display: inline-block;
        }
        
        @media (max-width: 782px) {
            .wp-ajax-search-settings {
                flex-direction: column;
            }
            .settings-column + .settings-column {
                border-left: none;
                border-top: 1px solid #ddd;
                margin-top: 20px;
                padding-top: 20px;
            }
        }

        .wp-ajax-search-coffee {
            text-align: center;
            margin: 40px 0 0 0;
        }
        
        </style>

        <div class="wrap">
            <h1>WP AJAX Search Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wp_ajax_search_settings'); ?>
                <div class="wp-ajax-search-settings">
                    <div class="settings-column">
                        <h2>General Settings</h2>
                        <?php self::render_general_section(); ?>
                    </div>
                    <div class="settings-column">
                        <h2>Search Fields & Weighting</h2>
                        <?php self::render_fields_section(); ?>
                    </div>
                </div>
                <div class="submit-row">
                    <?php submit_button(); ?>
                </div>
            </form>
            <div class="wp-ajax-search-coffee">
                <p>If you find this plugin useful, please consider supporting its development:</p>
                <a href="https://buymeacoffee.com/TeeJayMusics" target="_blank" style="display:inline-block;padding:10px 20px;background:#FFDD00;color:#222;text-decoration:none;border-radius:5px;font-weight:bold;">
                    ☕ Buy me a coffee
                </a>
            </div>
        </div>
        <?php
    }
    
    public static function render_general_section() {
        $post_types = get_post_types(['public' => true], 'objects');
        $selected_types = get_option('wp_ajax_search_post_types', ['post', 'page']);
        $enable_ajax = get_option('wp_ajax_search_enable_ajax', true);
        $loading_method = get_option('wp_ajax_search_loading_method', 'pagination');
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Searchable Post Types</th>
                <td>
                    <?php foreach ($post_types as $post_type) : ?>
                        <label>
                            <input type="checkbox" name="wp_ajax_search_post_types[]" value="<?php echo esc_attr($post_type->name); ?>"
                                <?php checked(in_array($post_type->name, $selected_types)); ?>>
                            <?php echo esc_html($post_type->label); ?>
                        </label><br>
                    <?php endforeach; ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Enable AJAX Live Search</th>
                <td>
                    <label>
                        <input type="checkbox" name="wp_ajax_search_enable_ajax" value="1"
                            <?php checked($enable_ajax, 1); ?>>
                        Enable live search results as you type
                    </label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Results Loading Method</th>
                <td>
                    <select name="wp_ajax_search_loading_method">
                        <option value="pagination" <?php selected($loading_method, 'pagination'); ?>>Pagination</option>
                        <option value="infinite" <?php selected($loading_method, 'infinite'); ?>>Infinite Scroll</option>
                    </select>
                    <br>
                    <small>Choose how users load more results: with buttons or by scrolling.</small>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public static function render_fields_section() {
        $fields = get_option('wp_ajax_search_fields', [
            'title' => 5,
            'content' => 1,
            'excerpt' => 1,
            'categories' => 2,
            'tags' => 2,
            'author' => 1,
            'custom_fields' => 1,
        ]);
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Fields to Search (Weight)</th>
                <td>
                    <label>Title:
                        <input type="number" name="wp_ajax_search_fields[title]" value="<?php echo esc_attr($fields['title'] ?? 5); ?>" min="0" max="10">
                    </label><br>
                    <label>Content:
                        <input type="number" name="wp_ajax_search_fields[content]" value="<?php echo esc_attr($fields['content'] ?? 1); ?>" min="0" max="10">
                    </label><br>
                    <label>Excerpt:
                        <input type="number" name="wp_ajax_search_fields[excerpt]" value="<?php echo esc_attr($fields['excerpt'] ?? 1); ?>" min="0" max="10">
                    </label><br>
                    <label>Categories:
                        <input type="number" name="wp_ajax_search_fields[categories]" value="<?php echo esc_attr($fields['categories'] ?? 2); ?>" min="0" max="10">
                    </label><br>
                    <label>Tags:
                        <input type="number" name="wp_ajax_search_fields[tags]" value="<?php echo esc_attr($fields['tags'] ?? 2); ?>" min="0" max="10">
                    </label><br>
                    <label>Author:
                        <input type="number" name="wp_ajax_search_fields[author]" value="<?php echo esc_attr($fields['author'] ?? 1); ?>" min="0" max="10">
                    </label><br>
                    <label>Custom Fields:
                        <input type="number" name="wp_ajax_search_fields[custom_fields]" value="<?php echo esc_attr($fields['custom_fields'] ?? 1); ?>" min="0" max="10">
                    </label>
                    <p><small>Enter a weight (0-10) for each field. Higher weights prioritize matches in that field.</small></p>
                </td>
            </tr>
        </table>
        <?php
    }
}
