<?php
/**
 * Plugin Name: WP News Source
 * Plugin URI: https://github.com/zapitz/wp-news-source
 * Description: Smart news source management with AI-powered detection for automated categorization and tagging. Perfect for n8n integration.
 * Version: 1.2.0
 * Author: Ariel Urtaza
 * Author URI: https://urtaza.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-news-source
 * Domain Path: /languages
 */

// Si este archivo es llamado directamente, abortar.
if (!defined('WPINC')) {
    die;
}

// Define constantes del plugin
define('WP_NEWS_SOURCE_VERSION', '1.2.0');
define('WP_NEWS_SOURCE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_NEWS_SOURCE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_NEWS_SOURCE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Código que se ejecuta durante la activación del plugin
 */
function activate_wp_news_source() {
    require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'includes/class-wp-news-source-activator.php';
    WP_News_Source_Activator::activate();
}

/**
 * Código que se ejecuta durante la desactivación del plugin
 */
function deactivate_wp_news_source() {
    require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'includes/class-wp-news-source-deactivator.php';
    WP_News_Source_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wp_news_source');
register_deactivation_hook(__FILE__, 'deactivate_wp_news_source');

/**
 * Incluye las clases principales del plugin
 */
require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'includes/class-wp-news-source.php';
require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'includes/class-wp-news-source-updater.php';

/**
 * Inicia la ejecución del plugin
 */
function run_wp_news_source() {
    $plugin = new WP_News_Source();
    $plugin->run();
}

run_wp_news_source();