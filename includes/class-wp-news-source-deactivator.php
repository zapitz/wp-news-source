<?php
/**
 * Se ejecuta durante la desactivación del plugin
 */
class WP_News_Source_Deactivator {
    
    /**
     * Limpieza durante la desactivación
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Limpiar opciones temporales si existen
        delete_transient('wpns_temp_data');
    }
}