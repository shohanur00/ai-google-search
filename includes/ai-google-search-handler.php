<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

/**
 * Fetch top 10 Google news results and summarize them using Gemini AI.
 *
 * @param string $query The search query.
 * @return string HTML-formatted output.
 */
function ai_google_news_search($query) {
    // --- API KEYS ---
    $google_api_key   = 'AIzaSyBiyEiBEQuG6W2mEaqIW1RMhws5-o8JVRA';
    $search_engine_id = '97855eeb7689f4f39';
    $gemini_api_key   = 'AIzaSyAxDNSIVyenlUEjEhiov4EbpGROxTJ1sq4';

    // --- CACHE HANDLING ---
    $cache_key = 'ai_news_' . md5($query);
    $cached = get_transient($cache_key);
    if ($cached) return $cached;

    // --- GOOGLE NEWS SEARCH ---
    $url = sprintf(
        'https://www.googleapis.com/customsearch/v1?key=%s&cx=%s&q=%s&num=10',
        $google_api_key,
        $search_engine_id,
        urlencode($query)
    );

    $response = wp_remote_get($url, ['timeout' => 20]);
    if (is_wp_error($response)) {
        return "<div class='ai-error'>⚠️ Unable to connect to Google Search API.</div>";
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($data['items'])) {
        return "<div class='ai-no-results'>No news results found for your query.</div>";
    }

    // --- COLLECT TEXT FOR SUMMARY ---
    $summary_input = "";
    foreach ($data['items'] as $item) {
        $summary_input .= "{$item['title']}: {$item['snippet']}\n";
    }

    // --- AI SUMMARY ---
    $summary = ai_generate_news_summary($summary_input, $gemini_api_key);
    if (empty($summary)) {
        $summary = "AI summary could not be generated at this time.";
    }

    // --- BUILD HTML OUTPUT ---
    ob_start();
    ?>
    <div class="ai-news-container">
        <div class="ai-news-summary">
            <h3>📰 AI Summary</h3>
            <p><?php echo nl2br(esc_html($summary)); ?></p>
        </div>

        <ul class="ai-news-list">
            <?php foreach ($data['items'] as $item): ?>
                <li class="ai-news-item">
                    <a href="<?php echo esc_url($item['link']); ?>" target="_blank" rel="noopener noreferrer">
                        <h4><?php echo esc_html($item['title']); ?></h4>
                    </a>
                    <p class="ai-news-snippet"><?php echo esc_html($item['snippet']); ?></p>
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

/**
 * Generate AI summary using Gemini API.
 *
 * @param string $content The text to summarize.
 * @param string $gemini_api_key The Gemini API key.
 * @return string The summary text.
 */
function ai_generate_news_summary($content, $gemini_api_key) {
    $url = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=' . $gemini_api_key;

    $body = [
        'contents' => [
            [
                'parts' => [
                    ['text' => "Summarize the following news headlines clearly and briefly:\n\n" . $content]
                ]
            ]
        ]
    ];

    $response = wp_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode($body),
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        return "⚠️ Gemini connection error: " . esc_html($response->get_error_message());
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
}
