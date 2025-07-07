<?php
/**
 * La clase principal del plugin
 */
class WP_News_Source {
    
    /**
     * El loader que registra todos los hooks del plugin
     */
    protected $loader;
    
    /**
     * El identificador único del plugin
     */
    protected $plugin_name;
    
    /**
     * La versión actual del plugin
     */
    protected $version;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->version = WP_NEWS_SOURCE_VERSION;
        $this->plugin_name = 'wp-news-source';
        
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_api_hooks();
    }
    
    /**
     * Carga las dependencias requeridas
     */
    private function load_dependencies() {
        // Loader para registrar hooks
        require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'includes/class-wp-news-source-loader.php';
        
        // Funcionalidad del área de administración
        require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'admin/class-wp-news-source-admin.php';
        
        // API REST
        require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'includes/class-wp-news-source-api.php';
        
        // Base de datos
        require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'database/class-wp-news-source-db.php';
        
        $this->loader = new WP_News_Source_Loader();
    }
    
    /**
     * Define la localización del plugin
     */
    private function set_locale() {
        $this->loader->add_action('plugins_loaded', $this, 'load_plugin_textdomain');
    }
    
    /**
     * Registra todos los hooks del área de administración
     */
    private function define_admin_hooks() {
        $plugin_admin = new WP_News_Source_Admin($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // AJAX handlers
        $this->loader->add_action('wp_ajax_wpns_save_source', $plugin_admin, 'ajax_save_source');
        $this->loader->add_action('wp_ajax_wpns_delete_source', $plugin_admin, 'ajax_delete_source');
        $this->loader->add_action('wp_ajax_wpns_get_source', $plugin_admin, 'ajax_get_source');
        $this->loader->add_action('wp_ajax_wpns_export_sources', $plugin_admin, 'ajax_export_sources');
        $this->loader->add_action('wp_ajax_wpns_import_sources', $plugin_admin, 'ajax_import_sources');
    }
    
    /**
     * Registra los endpoints de la API REST
     */
    private function define_api_hooks() {
        $plugin_api = new WP_News_Source_API($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('rest_api_init', $plugin_api, 'register_routes');
    }
    
    /**
     * Ejecuta el loader para ejecutar todos los hooks
     */
    public function run() {
        $this->loader->run();
    }
    
    /**
     * Carga los archivos de traducción
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'wp-news-source',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
    
    /**
     * Obtiene el nombre del plugin
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }
    
    /**
     * Obtiene la versión del plugin
     */
    public function get_version() {
        return $this->version;
    }
}

/**
 * Clase Loader para registrar hooks
 */
class WP_News_Source_Loader {
    
    protected $actions;
    protected $filters;
    
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }
    
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook' => $hook,
            'component' => $component,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args
        );
        
        return $hooks;
    }
    
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
        
        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
    }
}