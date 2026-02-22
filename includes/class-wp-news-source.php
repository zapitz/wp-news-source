<?php
/**
 * The main plugin class
 */
class WP_News_Source {
    
    /**
     * The loader that registers all plugin hooks
     */
    protected $loader;
    
    /**
     * The unique plugin identifier
     */
    protected $plugin_name;
    
    /**
     * The current plugin version
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
     * Load required dependencies
     */
    private function load_dependencies() {
        // Loader for registering hooks
        require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'includes/class-wp-news-source-loader.php';
        
        // Admin area functionality
        require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'admin/class-wp-news-source-admin.php';
        
        // REST API
        require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'includes/class-wp-news-source-api.php';
        
        // Database
        require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'database/class-wp-news-source-db.php';
        
        // Configuration
        require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'includes/class-wp-news-source-config.php';
        
        // Performance optimizations
        require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'includes/class-wp-news-source-performance.php';
        
        // Update page fix
        require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'includes/class-wp-news-source-update-page-fix.php';
        
        $this->loader = new WP_News_Source_Loader();
    }
    
    /**
     * Define plugin localization
     */
    private function set_locale() {
        $this->loader->add_action('plugins_loaded', $this, 'load_plugin_textdomain');
    }
    
    /**
     * Register all admin area hooks
     */
    private function define_admin_hooks() {
        $plugin_admin = new WP_News_Source_Admin($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_action('in_admin_header', $plugin_admin, 'admin_header');
        
        // AJAX handlers
        $this->loader->add_action('wp_ajax_wpns_save_source', $plugin_admin, 'ajax_save_source');
        $this->loader->add_action('wp_ajax_wpns_delete_source', $plugin_admin, 'ajax_delete_source');
        $this->loader->add_action('wp_ajax_wpns_get_source', $plugin_admin, 'ajax_get_source');
        $this->loader->add_action('wp_ajax_wpns_export_sources', $plugin_admin, 'ajax_export_sources');
        $this->loader->add_action('wp_ajax_wpns_import_sources', $plugin_admin, 'ajax_import_sources');
        
        // Prompts AJAX handlers
        $this->loader->add_action('wp_ajax_wpns_save_prompt', $plugin_admin, 'ajax_save_prompt');
        $this->loader->add_action('wp_ajax_wpns_delete_prompt', $plugin_admin, 'ajax_delete_prompt');
        $this->loader->add_action('wp_ajax_wpns_get_prompt', $plugin_admin, 'ajax_get_prompt');
        
        // Statistics AJAX handlers
        $this->loader->add_action('wp_ajax_wpns_export_history', $plugin_admin, 'ajax_export_history');
        
        // Check for version updates
        $this->loader->add_action('admin_init', $this, 'check_version');
    }
    
    /**
     * Register REST API endpoints
     */
    private function define_api_hooks() {
        $plugin_api = new WP_News_Source_API($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('rest_api_init', $plugin_api, 'register_routes');
        
        // Initialize GitHub updater
        $this->define_updater_hooks();
    }
    
    /**
     * Initialize GitHub updater
     */
    private function define_updater_hooks() {
        $plugin_updater = new WP_News_Source_Updater(
            WP_NEWS_SOURCE_PLUGIN_DIR . 'wp-news-source.php',
            'zapitz',
            'wp-news-source',
            $this->get_version()
        );
        
        // Add AJAX handlers for version management
        $this->loader->add_action('wp_ajax_wpns_check_updates', $plugin_updater, 'manual_update_check');
        $this->loader->add_action('wp_ajax_wpns_get_versions', $plugin_updater, 'get_available_versions');
        $this->loader->add_action('wp_ajax_wpns_get_changelog', $plugin_updater, 'get_changelog');
        $this->loader->add_action('wp_ajax_wpns_rollback_version', $plugin_updater, 'rollback_version');
    }
    
    /**
     * Execute the loader to run all hooks
     */
    public function run() {
        // Initialize performance optimizations early
        WP_News_Source_Performance::init();
        
        // Initialize update page fix
        WP_News_Source_Update_Page_Fix::init();
        
        $this->loader->run();
    }
    
    /**
     * Load translation files
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'wp-news-source',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
    
    /**
     * Get the plugin name
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }
    
    /**
     * Get the plugin version
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Check plugin version and run updates if needed
     */
    public function check_version() {
        $current_version = get_option('wp_news_source_version', '1.0.0');
        
        // If version has changed, ensure capabilities are added
        if (version_compare($current_version, WP_NEWS_SOURCE_VERSION, '<')) {
            require_once WP_NEWS_SOURCE_PLUGIN_DIR . 'includes/class-wp-news-source-activator.php';
            WP_News_Source_Activator::activate();

            // Update stored version
            update_option('wp_news_source_version', WP_NEWS_SOURCE_VERSION);
        }
    }
}