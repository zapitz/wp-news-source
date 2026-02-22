<?php
/**
 * Executed during plugin activation
 */
class WP_News_Source_Activator {
    
    /**
     * Create necessary database tables
     */
    public static function activate() {
        global $wpdb;
        
        $config = WP_News_Source_Config::get_table_names();
        $table_name = $wpdb->prefix . $config['sources'];
        $charset_collate = $wpdb->get_charset_collate();
        
        // Update table structure to include new fields
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            source_type varchar(50) DEFAULT 'general',
            keywords text DEFAULT NULL,
            description text DEFAULT NULL,
            detection_rules text DEFAULT NULL,
            category_id mediumint(9) DEFAULT NULL,
            category_name varchar(255) DEFAULT NULL,
            tag_ids text DEFAULT NULL,
            tag_names text DEFAULT NULL,
            auto_publish tinyint(1) DEFAULT 0,
            requires_review tinyint(1) DEFAULT 1,
            webhook_url varchar(255) DEFAULT NULL,
            api_key varchar(64) DEFAULT NULL,
            detection_count int DEFAULT 0,
            last_detected datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY detection_count (detection_count),
            KEY last_detected (last_detected)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create detection history table
        $history_table = $wpdb->prefix . $config['history'];
        $sql_history = "CREATE TABLE IF NOT EXISTS $history_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            source_id mediumint(9) NOT NULL,
            post_id mediumint(9) DEFAULT NULL,
            detection_confidence float DEFAULT 0,
            detection_method varchar(50) DEFAULT 'name_match',
            detected_content text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY source_id (source_id),
            KEY post_id (post_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql_history);
        
        
        // Add capabilities
        self::add_capabilities();
        
        // Create default options
        add_option('wp_news_source_version', WP_NEWS_SOURCE_VERSION);
        add_option('wp_news_source_api_key', wp_generate_password(WP_News_Source_Config::get('api_key_length'), false));
        add_option('wp_news_source_webhook_secret', wp_generate_password(WP_News_Source_Config::get('webhook_secret_length'), false));
        
        // Flush rewrite rules for API
        flush_rewrite_rules();
    }
    
    /**
     * Add plugin capabilities to roles
     * This is also called during updates to ensure capabilities are present
     */
    public static function add_capabilities() {
        $capabilities = WP_News_Source_Config::get_capabilities();
        
        // Add to administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }
        
        // Optionally add view stats to editor role
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('view_news_source_stats');
        }
    }
}