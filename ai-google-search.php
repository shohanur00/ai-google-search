<?php
/**
 * Plugin Name: AI Google News Search
 * Description: Displays top 10 latest Google News results below your WordPress search results — even if no posts are found.
 * Version: 1.8
 * Author: Engr. Shohanur Rahman
 * License: GPL2
 * Text Domain: ai-google-search
 */

if (!defined('ABSPATH')) exit;

// Include search handler
require_once plugin_dir_path(__FILE__) . 'includes/ai-google-search-handler.php';

// Enqueue frontend CSS
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'ai-google-search-style',
        plugin_dir_url(__FILE__) . 'assets/style.css',
        [],
        '1.8'
    );
});

/**
 * Shortcode: [ai_google_search]
 * Standalone Google News search form
 */
function ai_google_search_form() {
    $query = isset($_GET['aiq']) ? sanitize_text_field($_GET['aiq']) : '';

    ob_start(); ?>
    <div class="ai-search-container wp-block-group">
        <form method="get" class="ai-search-form" action="">
            <input type="text" name="aiq"
                   placeholder="🔍 Search latest news..."
                   value="<?php echo esc_attr($query); ?>"
                   required>
            <button type="submit"><?php esc_html_e('Search', 'ai-google-search'); ?></button>
        </form>

        <div class="ai-results">
            <?php
            if (!empty($query)) {
                echo ai_google_news_search($query);
            } else {
                echo '<p class="ai-placeholder">' . esc_html__('Enter a topic to see the latest Google News results.', 'ai-google-search') . '</p>';
            }
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ai_google_search', 'ai_google_search_form');

/**
 * Show Google News results below WordPress search results
 * (Print only once, whether posts exist or not)
 */
add_action('wp', function() {
    if (is_search() && !is_admin()) {
        add_action('get_footer', function() {
            global $wp_query;

            // Avoid duplicate rendering
            static $printed = false;
            if ($printed) return;
            $printed = true;

            $search_query = get_search_query();

            // Only display if there’s a valid search term
            if (!empty($search_query)) {
                echo '<section class="ai-search-wrapper wp-block-group" style="margin-top:30px;">';
                echo '<h2 class="wp-block-heading">' . esc_html__('🌐 Latest Google News', 'ai-google-search') . '</h2>';
                echo '<div class="ai-results">';
                echo ai_google_news_search($search_query);
                echo '</div>';
                echo '</section>';
            }
        }, 20); // Add near footer after loop_end
    }
});

/**
 * Admin page: Cache Management
 */
function ai_google_news_admin_page() {
    if (isset($_POST['clear_ai_cache'])) {
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_ai_news_%' OR option_name LIKE '_transient_timeout_ai_news_%'");
        echo '<div class="updated notice"><p>✅ Google News cache cleared successfully!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>🧹 AI Google News Cache Management</h1>
        <form method="post">
            <p>Click below to clear all cached Google News results immediately.</p>
            <button type="submit" name="clear_ai_cache" class="button button-primary">Clear Google News Cache</button>
        </form>
    </div>
    <?php
}

/**
 * Register admin menu
 */
add_action('admin_menu', function() {
    add_menu_page(
        'AI Google News',
        'AI Google News',
        'manage_options',
        'ai-google-news',
        'ai_google_news_admin_page',
        'dashicons-rss',
        90
    );
});
