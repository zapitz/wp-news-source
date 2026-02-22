<?php
/**
 * Admin area functionality
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
     * Add admin header with version display
     */
    public function admin_header() {
        $screen = get_current_screen();
        if (strpos($screen->id, 'wp-news-source') === false) {
            return;
        }
        ?>
        <div class="wpns-admin-header">
            <span class="wpns-version-display">
                <?php echo esc_html($this->plugin_name); ?> 
                <span class="version-number">v<?php echo esc_html($this->version); ?></span>
            </span>
        </div>
        <?php
    }
    
    /**
     * Register admin CSS styles
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
     * Register admin JavaScript scripts
     */
    public function enqueue_scripts() {
        // Only load on our plugin pages
        $screen = get_current_screen();
        if (strpos($screen->id, 'wp-news-source') === false) {
            return;
        }
        
        wp_enqueue_script('wp-api');
        
        // Use full admin script with all functionality
        wp_enqueue_script(
            $this->plugin_name,
            WP_NEWS_SOURCE_PLUGIN_URL . 'admin/js/wp-news-source-admin.js',
            array('jquery'),
            $this->version,
            true // Load in footer for better performance
        );
        
        // Removed debug console.log statements
        
        wp_localize_script($this->plugin_name, 'wpns_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpns_nonce'),
            'rest_url' => esc_url_raw(rest_url()),
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'admin_url' => admin_url(),
            'max_tags' => get_option('wp_news_source_max_tags', 3) // Default to 3
        ));
        
    }
    
    /**
     * Añade el menú del plugin
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            __('News Sources', 'wp-news-source'),
            __('News Sources', 'wp-news-source'),
            WP_News_Source_Config::get_capability('manage'),
            'wp-news-source',
            array($this, 'display_plugin_admin_page'),
            'dashicons-megaphone',
            30
        );
        
        add_submenu_page(
            'wp-news-source',
            __('All Sources', 'wp-news-source'),
            __('All Sources', 'wp-news-source'),
            WP_News_Source_Config::get_capability('manage'),
            'wp-news-source'
        );
        
        add_submenu_page(
            'wp-news-source',
            __('Add New', 'wp-news-source'),
            __('Add New', 'wp-news-source'),
            WP_News_Source_Config::get_capability('manage'),
            'wp-news-source-add',
            array($this, 'display_add_source_page')
        );
        
        // Prompts section disabled until OpenAI integration is implemented
        /*
        add_submenu_page(
            'wp-news-source',
            __('Prompts', 'wp-news-source'),
            __('Prompts', 'wp-news-source'),
            WP_News_Source_Config::get_capability('manage'),
            'wp-news-source-prompts',
            array($this, 'display_prompts_page')
        );
        */
        
        
        add_submenu_page(
            'wp-news-source',
            __('Statistics', 'wp-news-source'),
            __('Statistics', 'wp-news-source'),
            WP_News_Source_Config::get_capability('view_stats'),
            'wp-news-source-stats',
            array($this, 'display_stats_page')
        );
        
        add_submenu_page(
            'wp-news-source',
            __('Settings', 'wp-news-source'),
            __('Settings', 'wp-news-source'),
            WP_News_Source_Config::get_capability('manage'),
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
        include_once WP_NEWS_SOURCE_PLUGIN_DIR . 'admin/partials/wp-news-source-add-source-simple.php';
    }
    
    /**
     * Muestra la página de prompts
     */
    public function display_prompts_page() {
        include_once WP_NEWS_SOURCE_PLUGIN_DIR . 'admin/partials/wp-news-source-prompts.php';
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
        // Clean any previous output and start fresh buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        // Suppress any warnings/notices that might interfere
        error_reporting(E_ERROR | E_PARSE);
        
        // Set JSON content type (only if headers not sent)
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        
        try {
            // Debug logging
            // Removed debug logging
            
            // Verify nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpns_nonce')) {
                wp_send_json_error('Invalid nonce');
                return;
            }
            
            // Check permissions
            if (!current_user_can('manage_news_sources')) {
                wp_send_json_error(__('You do not have permission to perform this action', 'wp-news-source'));
                return;
            }
            
            // Validate required fields
            if (empty($_POST['name'])) {
                wp_send_json_error('Source name is required');
                return;
            }
            
            if (empty($_POST['category_id'])) {
                wp_send_json_error('Category is required');
                return;
            }
        
        $name = sanitize_text_field($_POST['name']);
        $slug = !empty($_POST['slug']) ? sanitize_title($_POST['slug']) : sanitize_title($name);
        
        $source_data = array(
            'name' => $name,
            'slug' => $slug,
            'source_type' => sanitize_text_field($_POST['source_type'] ?? 'general'),
            'keywords' => sanitize_textarea_field($_POST['keywords'] ?? ''),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'detection_rules' => '', // Will be set after validation
            'category_id' => intval($_POST['category_id']),
            'auto_publish' => intval(isset($_POST['auto_publish']) ? $_POST['auto_publish'] : 0),
            'requires_review' => intval(isset($_POST['requires_review']) ? $_POST['requires_review'] : 1), // Default to requires review
            'webhook_url' => '', // Moved to settings
            'api_key' => '' // Moved to settings
        );
        
        // Validate AI detection rules JSON if provided
        if (!empty($_POST['detection_rules'])) {
            $detection_rules = wp_unslash($_POST['detection_rules']);
            
            // Validate JSON
            $json_test = json_decode($detection_rules);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error('Invalid JSON in AI detection rules: ' . json_last_error_msg());
                return;
            }
            
            $source_data['detection_rules'] = $detection_rules;
        }
        
        // Obtener nombre de categoría
        $source_data['category_name'] = '';
        if ($source_data['category_id']) {
            $category = get_category($source_data['category_id']);
            $source_data['category_name'] = $category ? $category->name : '';
            // Removed debug: error_log('WPNS: Category found: ' . $source_data['category_name']);
        }
        
        // Procesar etiquetas
        $source_data['tag_ids'] = '';
        $source_data['tag_names'] = '';
        
        if (!empty($_POST['tags']) && is_array($_POST['tags'])) {
            // Get max tags limit
            $max_tags = get_option('wp_news_source_max_tags', 3);
            
            // Limit the number of tags
            $tags_to_process = array_slice($_POST['tags'], 0, $max_tags);
            
            $tag_ids = array();
            $tag_names = array();
            
            foreach ($tags_to_process as $tag_name) {
                $tag_name = sanitize_text_field($tag_name);
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
            // Removed debug: error_log('WPNS: Processed tags: ' . $source_data['tag_names']);
        }
        
        $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
        
        if ($source_id) {
            $result = $this->db->update_source($source_id, $source_data);
        } else {
            $result = $this->db->insert_source($source_data);
        }
        
            if ($result) {
                // Removed debug: error_log('WPNS: Source saved successfully with ID: ' . $result);
                wp_send_json_success(array(
                    'message' => 'Source saved successfully',
                    'source_id' => $result
                ));
            } else {
                // Removed debug: error_log('WPNS: Failed to save source');
                wp_send_json_error('Error saving source to database');
            }
            
        } catch (Exception $e) {
            // Removed debug: error_log('WPNS: Exception in ajax_save_source: ' . $e->getMessage());
            wp_send_json_error('Server error: ' . $e->getMessage());
        } catch (Error $e) {
            // Removed debug: error_log('WPNS: Fatal error in ajax_save_source: ' . $e->getMessage());
            wp_send_json_error('Fatal error: ' . $e->getMessage());
        }
        
        // Clean output and ensure we exit cleanly
        if (ob_get_level()) {
            ob_end_clean();
        }
        wp_die();
    }
    
    /**
     * AJAX: Eliminar fuente
     */
    public function ajax_delete_source() {
        // Clean any previous output and start fresh buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        // Suppress any warnings/notices that might interfere
        error_reporting(E_ERROR | E_PARSE);
        
        // Set JSON content type (only if headers not sent)
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        
        try {
            // Verify nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpns_nonce')) {
                wp_send_json_error('Invalid nonce');
                return;
            }
            
            // Check permissions
            if (!current_user_can('manage_news_sources')) {
                wp_send_json_error(__('You do not have permission to perform this action', 'wp-news-source'));
                return;
            }
            
            // Validate source ID
            if (!isset($_POST['source_id']) || empty($_POST['source_id'])) {
                wp_send_json_error('Source ID is required');
                return;
            }
            
            $source_id = intval($_POST['source_id']);
            
            if ($source_id <= 0) {
                wp_send_json_error('Invalid source ID');
                return;
            }
            
            // Delete the source
            if ($this->db->delete_source($source_id)) {
                wp_send_json_success(__('Source deleted successfully', 'wp-news-source'));
            } else {
                wp_send_json_error(__('Error deleting source', 'wp-news-source'));
            }
            
        } catch (Exception $e) {
            // Removed debug: error_log('WPNS: Exception in ajax_delete_source: ' . $e->getMessage());
            wp_send_json_error('Server error: ' . $e->getMessage());
        } catch (Error $e) {
            // Removed debug: error_log('WPNS: Fatal error in ajax_delete_source: ' . $e->getMessage());
            wp_send_json_error('Fatal error: ' . $e->getMessage());
        }
        
        // Clean output and ensure we exit cleanly
        if (ob_get_level()) {
            ob_end_clean();
        }
        wp_die();
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
            wp_send_json_error(__('Source not found', 'wp-news-source'));
        }
    }
    
    /**
     * AJAX: Exportar configuración
     */
    public function ajax_export_sources() {
        check_ajax_referer('wpns_nonce', 'nonce');
        
        if (!current_user_can(WP_News_Source_Config::get_capability('manage'))) {
            wp_die(__('You do not have permission to perform this action', 'wp-news-source'));
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
        
        if (!current_user_can(WP_News_Source_Config::get_capability('manage'))) {
            wp_die(__('You do not have permission to perform this action', 'wp-news-source'));
        }
        
        $import_data = stripslashes($_POST['import_data']);
        
        // Validate JSON before import
        $test_decode = json_decode($import_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(sprintf(
                __('Invalid JSON format: %s', 'wp-news-source'),
                json_last_error_msg()
            ));
            return;
        }
        
        // Get import options from POST
        $options = array(
            'match_categories_by_name' => isset($_POST['match_by_name']) ? (bool)$_POST['match_by_name'] : true,
            'skip_missing_categories' => isset($_POST['skip_missing']) ? (bool)$_POST['skip_missing'] : false,
            'use_default_category' => isset($_POST['use_default']) ? (bool)$_POST['use_default'] : false,
            'default_category_id' => isset($_POST['default_category']) ? intval($_POST['default_category']) : 1
        );
        
        $result = $this->db->import_sources($import_data, $options);
        
        if ($result === false) {
            // Check error log for specific error
            wp_send_json_error(__('Import failed. Please check the file format and try again.', 'wp-news-source'));
        } else {
            $imported = is_array($result) ? $result['imported'] : $result;
            $skipped = is_array($result) ? $result['skipped'] : 0;
            
            if ($imported === 0 && $skipped === 0) {
                wp_send_json_error(__('No new sources were imported. All sources may already exist.', 'wp-news-source'));
            } else {
                $message = sprintf(__('Successfully imported %d sources', 'wp-news-source'), $imported);
                if ($skipped > 0) {
                    $message .= sprintf(__(', %d sources skipped', 'wp-news-source'), $skipped);
                }
                wp_send_json_success($message);
            }
        }
    }
    
    /**
     * AJAX: Save prompt
     */
    public function ajax_save_prompt() {
        check_ajax_referer('wpns_nonce', 'nonce');
        
        if (!current_user_can('manage_news_sources')) {
            wp_die('No permission');
        }
        
        $key = sanitize_key($_POST['key']);
        $prompt = wp_kses_post($_POST['prompt']);
        $description = sanitize_text_field($_POST['description']);
        $variables = isset($_POST['variables']) ? array_map('sanitize_text_field', $_POST['variables']) : array();
        
        if (empty($key) || empty($prompt)) {
            wp_send_json_error(__('Key and prompt are required', 'wp-news-source'));
        }
        
        // Get existing prompts
        $prompts = get_option('wp_news_source_prompts', array());
        
        // Save prompt
        $prompts[$key] = array(
            'prompt' => $prompt,
            'description' => $description,
            'variables' => $variables,
            'updated' => current_time('mysql')
        );
        
        update_option('wp_news_source_prompts', $prompts);
        
        wp_send_json_success(array(
            'message' => __('Prompt saved successfully', 'wp-news-source'),
            'prompt' => $prompts[$key]
        ));
    }
    
    /**
     * AJAX: Delete prompt
     */
    public function ajax_delete_prompt() {
        check_ajax_referer('wpns_nonce', 'nonce');
        
        if (!current_user_can('manage_news_sources')) {
            wp_die('No permission');
        }
        
        $key = sanitize_key($_POST['key']);
        
        if (empty($key)) {
            wp_send_json_error(__('Invalid prompt key', 'wp-news-source'));
        }
        
        // Get existing prompts
        $prompts = get_option('wp_news_source_prompts', array());
        
        if (!isset($prompts[$key])) {
            wp_send_json_error(__('Prompt not found', 'wp-news-source'));
        }
        
        unset($prompts[$key]);
        update_option('wp_news_source_prompts', $prompts);
        
        wp_send_json_success(__('Prompt deleted successfully', 'wp-news-source'));
    }
    
    /**
     * AJAX: Get prompt
     */
    public function ajax_get_prompt() {
        check_ajax_referer('wpns_nonce', 'nonce');
        
        if (!current_user_can('manage_news_sources')) {
            wp_die('No permission');
        }
        
        $key = sanitize_key($_POST['key']);
        
        if (empty($key)) {
            wp_send_json_error(__('Invalid prompt key', 'wp-news-source'));
        }
        
        $prompts = get_option('wp_news_source_prompts', array());
        
        if (!isset($prompts[$key])) {
            wp_send_json_error(__('Prompt not found', 'wp-news-source'));
        }
        
        wp_send_json_success($prompts[$key]);
    }
    
    /**
     * AJAX: Export detection history
     */
    public function ajax_export_history() {
        check_ajax_referer('wpns_nonce', 'nonce');
        
        if (!current_user_can('view_news_source_stats')) {
            wp_die('No permission');
        }
        
        // Get all detection history
        $history = $this->db->get_detection_history(null, 1000); // Get up to 1000 records
        
        wp_send_json_success($history);
    }
}