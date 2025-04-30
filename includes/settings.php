<?php
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
        
        // Add sections
        add_settings_section(
            'wp_ajax_search_general',
            'General Settings',
            [__CLASS__, 'render_general_section'],
            'WP-AJAX-Search'
        );
        
        add_settings_section(
            'wp_ajax_search_fields',
            'Search Fields & Weighting',
            [__CLASS__, 'render_fields_section'],
            'WP-AJAX-Search'
        );
    }
    
    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>WP AJAX Search Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_ajax_search_settings');
                do_settings_sections('WP-AJAX-Search');
                submit_button();
                ?>
            </form>
            <div style="margin-top:30px; text-align:center;">
                <p>If you find this plugin useful, please consider supporting its development:</p>
                <a href="https://buymeacoffee.com/TeeJayMusics" target="_blank" style="display:inline-block;padding:10px 20px;background:#FFDD00;color:#222;text-decoration:none;border-radius:5px;font-weight:bold;">
                    ☕ Buy Me a Coffee
                </a>
            </div>
        </div>
        <?php
    }
    
    public static function render_general_section() {
        $post_types = get_post_types(['public' => true], 'objects');
        $selected_types = get_option('wp_ajax_search_post_types', ['post', 'page']);
        $enable_ajax = get_option('wp_ajax_search_enable_ajax', true);
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
        </table>
        <?php
    }
    
    public static function render_fields_section() {
        $search_fields = get_option('wp_ajax_search_fields', [
            'title' => 5,
            'content' => 1,
            'excerpt' => 1,
            'tags' => 2,
            'categories' => 2,
            'author' => 1,
            'custom_fields' => 1
        ]);
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Search Fields</th>
                <td>
                    <p>Select which fields to include in search and their relative weights (higher numbers = more important)</p>
                    
                    <label>
                        <input type="checkbox" name="wp_ajax_search_fields[title]" value="5"
                            <?php checked(isset($search_fields['title'])); ?>>
                        Post Title (Weight: <input type="number" name="wp_ajax_search_fields[title_weight]" value="<?php echo esc_attr($search_fields['title'] ?? 5); ?>" min="1" max="10">)
                    </label><br>
                    
                    <label>
                        <input type="checkbox" name="wp_ajax_search_fields[content]" value="1"
                            <?php checked(isset($search_fields['content'])); ?>>
                        Post Content (Weight: <input type="number" name="wp_ajax_search_fields[content_weight]" value="<?php echo esc_attr($search_fields['content'] ?? 1); ?>" min="1" max="10">)
                    </label><br>
                    
                    <label>
                        <input type="checkbox" name="wp_ajax_search_fields[excerpt]" value="1"
                            <?php checked(isset($search_fields['excerpt'])); ?>>
                        Post Excerpt (Weight: <input type="number" name="wp_ajax_search_fields[excerpt_weight]" value="<?php echo esc_attr($search_fields['excerpt'] ?? 1); ?>" min="1" max="10">)
                    </label><br>
                    
                    <label>
                        <input type="checkbox" name="wp_ajax_search_fields[tags]" value="2"
                            <?php checked(isset($search_fields['tags'])); ?>>
                        Tags (Weight: <input type="number" name="wp_ajax_search_fields[tags_weight]" value="<?php echo esc_attr($search_fields['tags'] ?? 2); ?>" min="1" max="10">)
                    </label><br>
                    
                    <label>
                        <input type="checkbox" name="wp_ajax_search_fields[categories]" value="2"
                            <?php checked(isset($search_fields['categories'])); ?>>
                        Categories (Weight: <input type="number" name="wp_ajax_search_fields[categories_weight]" value="<?php echo esc_attr($search_fields['categories'] ?? 2); ?>" min="1" max="10">)
                    </label><br>
                    
                    <label>
                        <input type="checkbox" name="wp_ajax_search_fields[author]" value="1"
                            <?php checked(isset($search_fields['author'])); ?>>
                        Author (Weight: <input type="number" name="wp_ajax_search_fields[author_weight]" value="<?php echo esc_attr($search_fields['author'] ?? 1); ?>" min="1" max="10">)
                    </label><br>
                    
                    <label>
                        <input type="checkbox" name="wp_ajax_search_fields[custom_fields]" value="1"
                            <?php checked(isset($search_fields['custom_fields'])); ?>>
                        Custom Fields (Weight: <input type="number" name="wp_ajax_search_fields[custom_fields_weight]" value="<?php echo esc_attr($search_fields['custom_fields'] ?? 1); ?>" min="1" max="10">)
                    </label><br>
                </td>
            </tr>
        </table>
        <?php
    }
}
