<?php
/**
 * Plugin Name: AI Google News Search
 * Description: Displays the top 10 latest Google News results below your WordPress search results — even if no posts are found.
 * Version: 2.0
 * Author: ghost
 * License: GPL2
 * Text Domain: ai-google-search
 */

if (!defined('ABSPATH')) exit; // Prevent direct access

// -----------------------------------------------------------------------------
//  Load Text Domain for Translations
// -----------------------------------------------------------------------------
add_action('plugins_loaded', function() {
    load_plugin_textdomain('ai-google-search', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// -----------------------------------------------------------------------------
//  Include Search Handler
// -----------------------------------------------------------------------------
require_once plugin_dir_path(__FILE__) . 'includes/ai-google-search-handler.php';

// -----------------------------------------------------------------------------
//  Enqueue Styles
// -----------------------------------------------------------------------------
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'ai-google-search-style',
        plugin_dir_url(__FILE__) . 'assets/style.css',
        [],
        '2.0'
    );
});

// -----------------------------------------------------------------------------
//  Shortcode: [ai_google_search]
// -----------------------------------------------------------------------------
function ai_google_search_form() {
    $query = isset($_GET['aiq']) ? sanitize_text_field($_GET['aiq']) : '';

    ob_start(); ?>
    <div class="ai-search-container wp-block-group">
        <form method="get" class="ai-search-form" action="">
            <label for="aiq" class="screen-reader-text">
                <?php esc_html_e('Search Google News', 'ai-google-search'); ?>
            </label>
            <input type="text" id="aiq" name="aiq"
                   placeholder="🔍 <?php esc_attr_e('Search latest news...', 'ai-google-search'); ?>"
                   value="<?php echo esc_attr($query); ?>" required>
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

// Show Google News after search results (if posts exist)স
add_action('loop_end', function($query) {
    if (!is_admin() && $query->is_main_query() && is_search()) {
        static $printed = false;
        if ($printed) return;
        $printed = true;

        $search_query = get_search_query();

        if (!empty($search_query)) {
            echo '<section class="ai-search-wrapper wp-block-group ai-news-section">';
            echo '<h2 class="wp-block-heading">' . esc_html__('Online Latest News', 'ai-google-search') . '</h2>';
            echo '<div class="ai-results">';
            echo ai_google_news_search($search_query);
            echo '</div>';
            echo '</section>';
        }
    }
});

// Show Google News even when there are no search results (AFTER "no results found" message)
add_action('loop_no_results', function($query) {
    if (!is_admin() && $query->is_main_query() && is_search()) {
        $search_query = get_search_query();

        if (!empty($search_query)) {
            // Wrap in a div to ensure it appears immediately after "No results found"
            echo '<div class="ai-google-news-after-no-results">';
            echo '<section class="ai-search-wrapper wp-block-group ai-news-section">';
            echo '<h2 class="wp-block-heading">' . esc_html__(' Online Latest News', 'ai-google-search') . '</h2>';
            echo '<div class="ai-results">';
            echo ai_google_news_search($search_query);
            echo '</div>';
            echo '</section>';
            echo '</div>';
        }
    }
});



// -----------------------------------------------------------------------------
//  Admin Page: Cache Management
// -----------------------------------------------------------------------------
function ai_google_news_admin_page() {
    if (isset($_POST['clear_ai_cache']) && check_admin_referer('ai_clear_cache_action', 'ai_clear_cache_nonce')) {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_ai_news_%',
                '_transient_timeout_ai_news_%'
            )
        );
        echo '<div class="updated notice"><p>✅ ' . esc_html__('Google News cache cleared successfully!', 'ai-google-search') . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>🧹 <?php esc_html_e('AI Google News Cache Management', 'ai-google-search'); ?></h1>
        <form method="post">
            <?php wp_nonce_field('ai_clear_cache_action', 'ai_clear_cache_nonce'); ?>
            <p><?php esc_html_e('Click below to clear all cached Google News results immediately.', 'ai-google-search'); ?></p>
            <button type="submit" name="clear_ai_cache" class="button button-primary">
                <?php esc_html_e('Clear Google News Cache', 'ai-google-search'); ?>
            </button>
        </form>
    </div>
    <?php
}

// -----------------------------------------------------------------------------
//  Register Admin Menu
// -----------------------------------------------------------------------------
add_action('admin_menu', function() {
    add_menu_page(
        __('AI Google News', 'ai-google-search'),
        __('AI Google News', 'ai-google-search'),
        'manage_options',
        'ai-google-news',
        'ai_google_news_admin_page',
        'dashicons-rss',
        90
    );
});
