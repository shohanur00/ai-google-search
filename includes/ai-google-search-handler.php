<?php
if (!defined('ABSPATH')) exit;

/**
 * Fetch top 10 Google News results using the Google Custom Search API.
 *
 * @param string $query The search query.
 * @return string HTML output with results or error message.
 */
function ai_google_news_search($query) {
    // --- CONFIG ---
    $google_api_key   = defined('AI_GOOGLE_API_KEY') ? AI_GOOGLE_API_KEY : '';
    $search_engine_id = defined('AI_GOOGLE_SEARCH_ENGINE_ID') ? AI_GOOGLE_SEARCH_ENGINE_ID : '';
    $number_of_post   = defined('AI_GOOGLE_RESULT_COUNT') ? AI_GOOGLE_RESULT_COUNT : 10;

    // --- VALIDATION ---
    $query = trim($query);
    if (empty($query)) {
        return "<div class='ai-no-query'>Please enter a search term.</div>";
    }

    // --- CACHE HANDLING ---
    $cache_key = 'ai_news_' . md5(strtolower($query));
    if ($cached = get_transient($cache_key)) {
        return $cached;
    }

    // --- API REQUEST ---
    $url = add_query_arg([
        'key' => $google_api_key,
        'cx'  => $search_engine_id,
        'q'   => $query,
        'num' => 10,
        'tbm' => 'nws', // Force news search mode
    ], 'https://www.googleapis.com/customsearch/v1');

    $response = wp_remote_get($url, ['timeout' => 20]);
    if (is_wp_error($response)) {
        return "<div class='ai-error'>⚠️ Unable to connect to Google News API. Please try again later.</div>";
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($data['items'])) {
        return "<div class='ai-no-results'>No recent news found for “" . esc_html($query) . "”.</div>";
    }

    // --- BUILD HTML OUTPUT ---
    ob_start(); ?>
    <div class="ai-news-results wp-block-group entry-content">
        <ul class="ai-news-list wp-block-list">
            <?php foreach ($data['items'] as $item): ?>
                <li class="ai-news-item wp-block-latest-posts__list-item">
                    <a class="ai-news-title wp-block-latest-posts__post-title"
                       href="<?php echo esc_url($item['link']); ?>"
                       target="_blank"
                       rel="noopener noreferrer">
                        <?php echo esc_html($item['title']); ?>
                    </a>

                    <?php if (!empty($item['snippet'])): ?>
                        <p class="ai-news-snippet wp-block-latest-posts__post-excerpt">
                            <?php echo esc_html($item['snippet']); ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($item['displayLink'])): ?>
                        <small class="ai-news-source wp-block-latest-posts__post-date">
                            Source: <?php echo esc_html($item['displayLink']); ?>
                        </small>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
    $output = ob_get_clean();

    // --- CACHE FOR 6 HOURS ---
    set_transient($cache_key, $output, 6 * HOUR_IN_SECONDS);

    return $output;
}
