<?php
/**
 * Configuration class for WP News Source
 * 
 * Centralizes all configurable values to avoid hardcoding
 * throughout the plugin codebase
 */
class WP_News_Source_Config {
    
    /**
     * Get source types
     * @return array Available source types
     */
    public static function get_source_types() {
        return apply_filters('wpns_source_types', array(
            'government' => __('Government', 'wp-news-source'),
            'company' => __('Company', 'wp-news-source'),
            'ngo' => __('NGO', 'wp-news-source'),
            'general' => __('General', 'wp-news-source')
        ));
    }
    
    /**
     * Get default values
     * @return array Default configuration values
     */
    public static function get_defaults() {
        return apply_filters('wpns_defaults', array(
            'api_key_length' => 32,
            'webhook_secret_length' => 16,
            'max_tags_default' => 3,
            'max_tags_limit' => 5,
            'search_limit_default' => 10,
            'search_limit_max' => 50,
            'detection_confidence_min' => 30,
            'history_export_limit' => 1000,
            'default_author_id' => 1,
            'content_preview_length' => 100,
            'detected_content_max_length' => 500
        ));
    }
    
    /**
     * Get capability names
     * @return array WordPress capabilities used by the plugin
     */
    public static function get_capabilities() {
        return apply_filters('wpns_capabilities', array(
            'manage' => 'manage_news_sources',
            'view_stats' => 'view_news_source_stats'
        ));
    }
    
    /**
     * Get API namespace
     * @return string REST API namespace
     */
    public static function get_api_namespace() {
        return apply_filters('wpns_api_namespace', 'wp-news-source/v1');
    }
    
    /**
     * Get table names
     * @return array Database table names (without prefix)
     */
    public static function get_table_names() {
        return array(
            'sources' => 'news_sources',
            'history' => 'news_source_detections'
        );
    }
    
    /**
     * Get a specific default value
     * @param string $key The configuration key
     * @param mixed $default Default value if key not found
     * @return mixed The configuration value
     */
    public static function get($key, $default = null) {
        $defaults = self::get_defaults();
        return isset($defaults[$key]) ? $defaults[$key] : $default;
    }
    
    /**
     * Get a specific capability
     * @param string $key The capability key
     * @return string The capability name
     */
    public static function get_capability($key) {
        $capabilities = self::get_capabilities();
        return isset($capabilities[$key]) ? $capabilities[$key] : 'manage_options';
    }
    
    /**
     * Check if API key is required
     * @return bool Whether API key authentication is required
     */
    public static function is_api_key_required() {
        return get_option('wp_news_source_require_api_key', false);
    }
    
    /**
     * Get the API key
     * @return string The API key
     */
    public static function get_api_key() {
        $api_key = get_option('wp_news_source_api_key');
        
        // Generate if doesn't exist
        if (empty($api_key)) {
            $api_key = wp_generate_password(self::get('api_key_length'), false);
            update_option('wp_news_source_api_key', $api_key);
        }
        
        return $api_key;
    }
    
    /**
     * Get webhook secret
     * @return string The webhook secret
     */
    public static function get_webhook_secret() {
        $secret = get_option('wp_news_source_webhook_secret');
        
        // Generate if doesn't exist
        if (empty($secret)) {
            $secret = wp_generate_password(self::get('webhook_secret_length'), false);
            update_option('wp_news_source_webhook_secret', $secret);
        }
        
        return $secret;
    }
    
    /**
     * Get max tags setting
     * @return int Maximum number of tags per source
     */
    public static function get_max_tags() {
        return get_option('wp_news_source_max_tags', self::get('max_tags_default'));
    }
    
    /**
     * Get default prompts
     * @return array Default AI prompts
     */
    public static function get_default_prompts() {
        return apply_filters('wpns_default_prompts', array(
            'bulletin_processor' => array(
                'prompt' => 'Process the following bulletin from {{source_name}}. Extract the key information and format it as a news article. Focus on: facts, dates, quotes, and actionable items. Keep the tone professional and neutral.',
                'description' => 'Process government bulletins into news articles',
                'variables' => array('source_name')
            ),
            'content_analyzer' => array(
                'prompt' => 'Analyze the following content and identify: 1) Main topics (max 5), 2) Key figures mentioned, 3) Sentiment (positive/neutral/negative), 4) Suggested tags (max 5), 5) Summary in 2 sentences.',
                'description' => 'Analyze content for metadata extraction',
                'variables' => array()
            ),
            'title_generator' => array(
                'prompt' => 'Generate a compelling news title for the following content. The title should be: 1) Under 70 characters, 2) Include the main subject, 3) Be factual and not clickbait, 4) Include {{source_type}} if relevant.',
                'description' => 'Generate optimized titles for news articles',
                'variables' => array('source_type')
            )
        ));
    }
}