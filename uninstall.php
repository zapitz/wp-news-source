<?php
/**
 * Se ejecuta cuando el plugin es desinstalado
 * 
 * @package WP_News_Source
 */

// Si uninstall.php no es llamado por WordPress, salir
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Eliminar la tabla de la base de datos
global $wpdb;
$table_name = $wpdb->prefix . 'news_sources';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

// Eliminar opciones
delete_option('wp_news_source_version');

// Eliminar capacidades de los roles
$role = get_role('administrator');
if ($role) {
    $role->remove_cap('manage_news_sources');
}

// Limpiar cualquier transient creado por el plugin
delete_transient('wpns_temp_data');

// Flush rewrite rules para limpiar los endpoints de la API
flush_rewrite_rules();