<?php
/**
 * Plugin Name: AI Google News Search
 * Description: AI-powered Google News Search that summarizes top 10 results using Gemini AI.
 * Version: 1.2
 * Author: Engr. Shohanur Rahman
 * License: GPL2
 * Text Domain: ai-google-search
 */

if (!defined('ABSPATH')) exit; // Prevent direct access

// --- Include main logic ---
require_once plugin_dir_path(__FILE__) . 'includes/ai-google-search-handler.php';

// --- Enqueue frontend CSS ---
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'ai-google-search-style',
        plugin_dir_url(__FILE__) . 'assets/style.css',
        [],
        '1.2'
    );
});

/**
 * Renders AI search form and results.
 */
function ai_google_search_form() {
    $query = isset($_GET['aiq']) ? sanitize_text_field($_GET['aiq']) : '';

    ob_start();
    ?>
    <div class="ai-search-container">
        <form method="get" class="ai-search-form" action="">
            <input type="text" name="aiq" 
                   placeholder="🔍 Search news with AI..." 
                   value="<?php echo esc_attr($query); ?>" 
                   required>
            <button type="submit">Search</button>
        </form>

        <div class="ai-results">
            <?php
            if (!empty($query)) {
                echo ai_google_news_search($query);
            } else {
                echo "<p class='ai-placeholder'>Enter a topic to get AI-powered Google News results.</p>";
            }
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ai_google_search', 'ai_google_search_form');
