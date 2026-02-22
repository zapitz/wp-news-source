<?php
/**
 * Fix for WordPress Update Page when updates are disabled
 * 
 * Prevents PHP warnings on update-core.php
 */

class WP_News_Source_Update_Page_Fix {
    
    /**
     * Initialize the fix
     */
    public static function init() {
        // Only apply on update-core.php page
        add_action('load-update-core.php', array(__CLASS__, 'fix_update_page'));
        
        // Fix the get_preferred_from_update_core function
        add_filter('pre_site_transient_update_core', array(__CLASS__, 'fix_update_core_transient'), 20);
    }
    
    /**
     * Fix the update page to handle empty update objects
     */
    public static function fix_update_page() {
        // Force proper update objects before the page loads
        add_filter('site_transient_update_core', array(__CLASS__, 'ensure_valid_update_core'), 1);
    }
    
    /**
     * Ensure update_core transient is a valid object
     */
    public static function ensure_valid_update_core($updates) {
        global $wp_version;
        
        // If updates is false or null, create a proper empty object
        if (!$updates || !is_object($updates)) {
            $updates = (object) array(
                'updates' => array(),
                'version_checked' => $wp_version,
                'last_checked' => time()
            );
        }
        
        // Ensure updates property exists and is an array
        if (!isset($updates->updates) || !is_array($updates->updates)) {
            $updates->updates = array();
        }
        
        // Add a fake "current" update to prevent errors
        if (empty($updates->updates)) {
            $updates->updates[] = (object) array(
                'response' => 'latest',
                'current' => $wp_version,
                'locale' => get_locale(),
                'packages' => (object) array(
                    'full' => '',
                    'no_content' => '',
                    'new_bundled' => '',
                    'partial' => false,
                    'rollback' => false
                ),
                'download' => '',
                'version' => $wp_version,
                'php_version' => PHP_VERSION,
                'mysql_version' => $GLOBALS['wpdb']->db_version(),
                'new_bundled' => '',
                'partial_version' => ''
            );
        }
        
        return $updates;
    }
    
    /**
     * Fix the update core transient for other pages
     */
    public static function fix_update_core_transient($pre) {
        // If we're returning false/null, return a proper object instead
        if ($pre === false || $pre === null) {
            return self::ensure_valid_update_core(null);
        }
        
        return $pre;
    }
}