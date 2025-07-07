<?php
/**
 * Se ejecuta durante la activación del plugin
 */
class WP_News_Source_Activator {
    
    /**
     * Crea las tablas necesarias en la base de datos
     */
    public static function activate() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'news_sources';
        $charset_collate = $wpdb->get_charset_collate();
        
        // Actualizar estructura de la tabla para incluir nuevos campos
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            source_type varchar(50) DEFAULT 'general',
            description text DEFAULT NULL,
            keywords text DEFAULT NULL,
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
        
        // Crear tabla de historial de detecciones
        $history_table = $wpdb->prefix . 'news_source_detections';
        $sql_history = "CREATE TABLE IF NOT EXISTS $history_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            source_id mediumint(9) NOT NULL,
            post_id mediumint(9) DEFAULT NULL,
            detection_confidence float DEFAULT 0,
            detection_method varchar(50) DEFAULT 'keyword',
            detected_content text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY source_id (source_id),
            KEY post_id (post_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql_history);
        
        // Añadir capacidades
        $role = get_role('administrator');
        $role->add_cap('manage_news_sources');
        $role->add_cap('view_news_source_stats');
        
        // Crear opciones por defecto
        add_option('wp_news_source_version', WP_NEWS_SOURCE_VERSION);
        add_option('wp_news_source_api_key', wp_generate_password(32, false));
        add_option('wp_news_source_enable_ai', true);
        add_option('wp_news_source_webhook_secret', wp_generate_password(16, false));
        
        // Flush rewrite rules para la API
        flush_rewrite_rules();
    }
}