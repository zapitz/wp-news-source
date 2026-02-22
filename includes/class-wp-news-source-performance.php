<?php
/**
 * Performance optimizations for WP News Source
 * 
 * Handles performance-related issues like update checks
 */

class WP_News_Source_Performance {
    
    /**
     * Initialize performance optimizations
     */
    public static function init() {
        // Always check for connectivity issues first
        add_filter('pre_http_request', array(__CLASS__, 'check_connectivity'), 1, 3);
        
        // Check if we should disable update checks
        if (get_option('wpns_disable_update_checks', false)) {
            self::disable_all_update_checks();
        }
        
        // Auto-detect timeout issues and disable updates
        if (get_transient('wpns_detected_timeout')) {
            self::disable_all_update_checks();
        }
    }
    
    /**
     * Disable all WordPress update checks
     */
    private static function disable_all_update_checks() {
        // Disable core updates with proper empty object
        add_filter('pre_site_transient_update_core', array(__CLASS__, 'return_empty_update_core'));
        remove_action('admin_init', '_maybe_update_core');
        remove_action('wp_version_check', 'wp_version_check');
        
        // Disable plugin updates with proper empty object
        add_filter('pre_site_transient_update_plugins', array(__CLASS__, 'return_empty_update_plugins'));
        remove_action('admin_init', '_maybe_update_plugins');
        remove_action('load-plugins.php', 'wp_update_plugins');
        remove_action('load-update.php', 'wp_update_plugins');
        remove_action('load-update-core.php', 'wp_update_plugins');
        remove_action('wp_update_plugins', 'wp_update_plugins');
        
        // Disable theme updates with proper empty object
        add_filter('pre_site_transient_update_themes', array(__CLASS__, 'return_empty_update_themes'));
        remove_action('admin_init', '_maybe_update_themes');
        remove_action('load-themes.php', 'wp_update_themes');
        remove_action('load-update.php', 'wp_update_themes');
        remove_action('load-update-core.php', 'wp_update_themes');
        remove_action('wp_update_themes', 'wp_update_themes');
        
        // Disable auto-updates
        add_filter('automatic_updater_disabled', '__return_true');
        add_filter('auto_update_core', '__return_false');
        add_filter('auto_update_plugin', '__return_false');
        add_filter('auto_update_theme', '__return_false');
        add_filter('auto_update_translation', '__return_false');
        
        // Disable update emails
        add_filter('auto_core_update_send_email', '__return_false');
        add_filter('auto_plugin_update_send_email', '__return_false');
        add_filter('auto_theme_update_send_email', '__return_false');
        
        // Remove update nag
        remove_action('admin_notices', 'update_nag', 3);
        remove_action('network_admin_notices', 'update_nag', 3);
    }
    
    /**
     * Return empty core update object
     */
    public static function return_empty_update_core() {
        global $wp_version;
        
        return (object) array(
            'updates' => array(),
            'version_checked' => $wp_version,
            'last_checked' => time()
        );
    }
    
    /**
     * Return empty plugin update object
     */
    public static function return_empty_update_plugins() {
        return (object) array(
            'last_checked' => time(),
            'response' => array(),
            'translations' => array(),
            'no_update' => array(),
            'checked' => array()
        );
    }
    
    /**
     * Return empty theme update object
     */
    public static function return_empty_update_themes() {
        return (object) array(
            'last_checked' => time(),
            'response' => array(),
            'translations' => array(),
            'no_update' => array(),
            'checked' => array()
        );
    }
    
    /**
     * Check for connectivity issues and abort requests if needed
     */
    public static function check_connectivity($preempt, $args, $url) {
        // Block requests to WordPress.org update endpoints
        $blocked_hosts = array(
            'api.wordpress.org',
            'downloads.wordpress.org',
            'wordpress.org',
            'api.github.com' // Also block GitHub if having issues
        );
        
        $host = parse_url($url, PHP_URL_HOST);
        
        // Check if this is an update-related request
        $update_endpoints = array(
            '/core/version-check/',
            '/plugins/update-check/',
            '/themes/update-check/',
            '/core/browse-happy/',
            '/events/',
            '/core/serve-happy/'
        );
        
        $is_update_request = false;
        foreach ($update_endpoints as $endpoint) {
            if (strpos($url, $endpoint) !== false) {
                $is_update_request = true;
                break;
            }
        }
        
        // Block if it's a blocked host or update request
        if (in_array($host, $blocked_hosts) || $is_update_request) {
            // Set a flag that we detected timeout issues
            set_transient('wpns_detected_timeout', true, HOUR_IN_SECONDS);
            
            // Return a fake empty response to prevent timeouts
            return array(
                'headers' => array(),
                'body' => '',
                'response' => array(
                    'code' => 200,
                    'message' => 'OK'
                ),
                'cookies' => array(),
                'http_response' => null
            );
        }
        
        return $preempt;
    }
}