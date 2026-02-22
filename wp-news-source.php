<?php
/**
 * Plugin Name: WP News Source
 * Plugin URI: https://github.com/zapitz/wp-news-source
 * Description: News source management for automated categorization and tagging. Perfect for n8n integration.
 * Version: 2.6.0
 * Author: Ariel Urtaza
 * Author URI: https://urtaza.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-news-source
 * Domain Path: /languages
 * Update URI: https://github.com/zapitz/wp-news-source
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Emergency performance fix - include if exists
if (file_exists(plugin_dir_path(__FILE__) . 'emergency-config.php')) {
    require_once plugin_dir_path(__FILE__) . 'emergency-config.php';
}

// Define plugin constants
define('WP_NEWS_SOURCE_VERSION', '2.6.0');
define('WP_NEWS_SOURCE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_NEWS_SOURCE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_NEWS_SOURCE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Code executed during plugin activation
 */
function activate_wp_news_source() {
    require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'includes/class-wp-news-source-activator.php';
    WP_News_Source_Activator::activate();
}

/**
 * Code executed during plugin deactivation
 */
function deactivate_wp_news_source() {
    require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'includes/class-wp-news-source-deactivator.php';
    WP_News_Source_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wp_news_source');
register_deactivation_hook(__FILE__, 'deactivate_wp_news_source');

/**
 * Include the core plugin classes
 */
require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'includes/class-wp-news-source.php';
require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'includes/class-wp-news-source-updater.php';

/**
 * Begin plugin execution
 */
function run_wp_news_source() {
    $plugin = new WP_News_Source();
    $plugin->run();
}

run_wp_news_source();