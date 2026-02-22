<?php
/**
 * Executed when the plugin is uninstalled
 * 
 * @package WP_News_Source
 */

// If uninstall.php is not called by WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Delete database tables
global $wpdb;

// Delete main tables
$table_name = $wpdb->prefix . 'news_sources';
$history_table = $wpdb->prefix . 'news_source_detections';
$publibot_table = $wpdb->prefix . 'news_source_publibot_history';

$wpdb->query("DROP TABLE IF EXISTS {$table_name}");
$wpdb->query("DROP TABLE IF EXISTS {$history_table}");
$wpdb->query("DROP TABLE IF EXISTS {$publibot_table}");

// Delete options
delete_option('wp_news_source_version');
delete_option('wp_news_source_api_key');
delete_option('wp_news_source_webhook_secret');
delete_option('wp_news_source_require_api_key');
delete_option('wp_news_source_max_tags');
delete_option('wp_news_source_telegram_bot_token');
delete_option('wp_news_source_prompts');

// Delete OpenAI/Publibot options
delete_option('wpns_openai_enabled');
delete_option('wpns_openai_api_key');
delete_option('wpns_openai_model');
delete_option('wpns_openai_temperature');
delete_option('wpns_openai_max_tokens');
delete_option('wpns_ai_extract_title');
delete_option('wpns_ai_detect_source');
delete_option('wpns_ai_generate_tags');
delete_option('wpns_ai_clean_content');
delete_option('wpns_ai_generate_excerpt');

// Delete version management options
delete_option('wpns_enable_prereleases');
delete_option('wpns_auto_update');

// Remove capabilities from roles
$capabilities = array(
    'manage_news_sources',
    'view_news_source_stats'
);

$role = get_role('administrator');
if ($role) {
    foreach ($capabilities as $cap) {
        $role->remove_cap($cap);
    }
}

$editor_role = get_role('editor');
if ($editor_role) {
    $editor_role->remove_cap('view_news_source_stats');
}

// Clean up any transients created by the plugin
delete_transient('wpns_temp_data');

// Flush rewrite rules to clean API endpoints
flush_rewrite_rules();