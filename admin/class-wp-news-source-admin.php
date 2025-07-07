<?php
/**
 * Funcionalidad del área de administración
 */
class WP_News_Source_Admin {
    
    private $plugin_name;
    private $version;
    private $db;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->db = new WP_News_Source_DB();
    }
    
    /**
     * Registra los estilos CSS del admin
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            WP_NEWS_SOURCE_PLUGIN_URL . 'admin/css/wp-news-source-admin.css',
            array(),
            $this->version,
            'all'
        );
    }
    
    /**
     * Registra los scripts JavaScript del admin
     */
    public function enqueue_scripts() {
        wp_enqueue_script('wp-api');
        
        wp_enqueue_script(
            $this->plugin_name,
            WP_NEWS_SOURCE_PLUGIN_URL . 'admin/js/wp-news-source-admin.js',
            array('jquery', 'wp-api'),
            $this->version,
            false
        );
        
        wp_localize_script($this->plugin_name, 'wpns_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpns_nonce'),
            'rest_url' => esc_url_raw(rest_url()),
            'rest_nonce' => wp_create_nonce('wp_rest')
        ));
    }
    
    /**
     * Añade el menú del plugin
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            __('News Sources', 'wp-news-source'),
            __('News Sources', 'wp-news-source'),
            'manage_news_sources',
            'wp-news-source',
            array($this, 'display_plugin_admin_page'),
            'dashicons-megaphone',
            30
        );
        
        add_submenu_page(
            'wp-news-source',
            __('Todas las Fuentes', 'wp-news-source'),
            __('Todas las Fuentes', 'wp-news-source'),
            'manage_news_sources',
            'wp-news-source'
        );
        
        add_submenu_page(
            'wp-news-source',
            __('Añadir Nueva', 'wp-news-source'),
            __('Añadir Nueva', 'wp-news-source'),
            'manage_news_sources',
            'wp-news-source-add',
            array($this, 'display_add_source_page')
        );
        
        add_submenu_page(
            'wp-news-source',
            __('Estadísticas', 'wp-news-source'),
            __('Estadísticas', 'wp-news-source'),
            'view_news_source_stats',
            'wp-news-source-stats',
            array($this, 'display_stats_page')
        );
        
        add_submenu_page(
            'wp-news-source',
            __('Configuración', 'wp-news-source'),
            __('Configuración', 'wp-news-source'),
            'manage_news_sources',
            'wp-news-source-settings',
            array($this, 'display_settings_page')
        );
    }
    
    /**
     * Muestra la página principal del plugin
     */
    public function display_plugin_admin_page() {
        include_once WP_NEWS_SOURCE_PLUGIN_DIR . 'admin/partials/wp-news-source-admin-display.php';
    }
    
    /**
     * Muestra la página de añadir fuente
     */
    public function display_add_source_page() {
        include_once WP_NEWS_SOURCE_PLUGIN_DIR . 'admin/partials/wp-news-source-add-source.php';
    }
    
    /**
     * Muestra la página de estadísticas
     */
    public function display_stats_page() {
        include_once WP_NEWS_SOURCE_PLUGIN_DIR . 'admin/partials/wp-news-source-stats.php';
    }
    
    /**
     * Muestra la página de configuración
     */
    public function display_settings_page() {
        include_once WP_NEWS_SOURCE_PLUGIN_DIR . 'admin/partials/wp-news-source-settings.php';
    }
    
    /**
     * AJAX: Guardar fuente
     */
    public function ajax_save_source() {
        check_ajax_referer('wpns_nonce', 'nonce');
        
        if (!current_user_can('manage_news_sources')) {
            wp_die('No tienes permisos para realizar esta acción');
        }
        
        $source_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'slug' => sanitize_title($_POST['slug']),
            'source_type' => sanitize_text_field($_POST['source_type']),
            'description' => wp_kses_post($_POST['description'] ?? ''),
            'keywords' => sanitize_text_field($_POST['keywords'] ?? ''),
            'detection_rules' => sanitize_textarea_field($_POST['detection_rules'] ?? ''),
            'category_id' => intval($_POST['category_id']),
            'auto_publish' => intval($_POST['auto_publish']),
            'requires_review' => intval($_POST['requires_review']),
            'webhook_url' => esc_url_raw($_POST['webhook_url'] ?? ''),
            'generate_api_key' => isset($_POST['generate_api_key']) && $_POST['generate_api_key']
        );
        
        // Validar reglas de detección JSON si se proporcionan
        if (!empty($source_data['detection_rules'])) {
            $rules = json_decode($source_data['detection_rules'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(__('Las reglas de detección deben ser JSON válido', 'wp-news-source'));
                return;
            }
        }
        
        // Obtener nombre de categoría
        if ($source_data['category_id']) {
            $category = get_category($source_data['category_id']);
            $source_data['category_name'] = $category ? $category->name : '';
        }
        
        // Procesar etiquetas
        if (!empty($_POST['tags'])) {
            $tag_ids = array();
            $tag_names = array();
            
            foreach ($_POST['tags'] as $tag_name) {
                $tag = get_term_by('name', $tag_name, 'post_tag');
                
                if (!$tag) {
                    // Crear la etiqueta si no existe
                    $tag_data = wp_insert_term($tag_name, 'post_tag');
                    if (!is_wp_error($tag_data)) {
                        $tag = get_term($tag_data['term_id'], 'post_tag');
                    }
                }
                
                if ($tag) {
                    $tag_ids[] = $tag->term_id;
                    $tag_names[] = $tag->name;
                }
            }
            
            $source_data['tag_ids'] = implode(',', $tag_ids);
            $source_data['tag_names'] = implode(',', $tag_names);
        }
        
        $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
        
        if ($source_id) {
            $result = $this->db->update_source($source_id, $source_data);
        } else {
            $result = $this->db->insert_source($source_data);
        }
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Fuente guardada correctamente', 'wp-news-source'),
                'source_id' => $result
            ));
        } else {
            wp_send_json_error(__('Error al guardar la fuente', 'wp-news-source'));
        }
    }
    
    /**
     * AJAX: Eliminar fuente
     */
    public function ajax_delete_source() {
        check_ajax_referer('wpns_nonce', 'nonce');
        
        if (!current_user_can('manage_news_sources')) {
            wp_die('No tienes permisos para realizar esta acción');
        }
        
        $source_id = intval($_POST['source_id']);
        
        if ($this->db->delete_source($source_id)) {
            wp_send_json_success(__('Fuente eliminada correctamente', 'wp-news-source'));
        } else {
            wp_send_json_error(__('Error al eliminar la fuente', 'wp-news-source'));
        }
    }
    
    /**
     * AJAX: Obtener fuente
     */
    public function ajax_get_source() {
        check_ajax_referer('wpns_nonce', 'nonce');
        
        $source_id = intval($_POST['source_id']);
        $source = $this->db->get_source($source_id);
        
        if ($source) {
            wp_send_json_success($source);
        } else {
            wp_send_json_error(__('Fuente no encontrada', 'wp-news-source'));
        }
    }
    
    /**
     * AJAX: Exportar configuración
     */
    public function ajax_export_sources() {
        check_ajax_referer('wpns_nonce', 'nonce');
        
        if (!current_user_can('manage_news_sources')) {
            wp_die('No tienes permisos para realizar esta acción');
        }
        
        $export_data = $this->db->export_sources();
        
        wp_send_json_success(array(
            'data' => $export_data,
            'filename' => 'wp-news-sources-' . date('Y-m-d') . '.json'
        ));
    }
    
    /**
     * AJAX: Importar configuración
     */
    public function ajax_import_sources() {
        check_ajax_referer('wpns_nonce', 'nonce');
        
        if (!current_user_can('manage_news_sources')) {
            wp_die('No tienes permisos para realizar esta acción');
        }
        
        $import_data = stripslashes($_POST['import_data']);
        $imported = $this->db->import_sources($import_data);
        
        if ($imported === false) {
            wp_send_json_error(__('Datos de importación inválidos', 'wp-news-source'));
        } else {
            wp_send_json_success(sprintf(__('Se importaron %d fuentes correctamente', 'wp-news-source'), $imported));
        }
    }
}