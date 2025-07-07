<?php
/**
 * GitHub Updater for WP News Source
 * Detects and installs updates from GitHub releases
 */

class WP_News_Source_Updater {
    
    private $plugin_slug;
    private $plugin_file;
    private $version;
    private $github_username;
    private $github_repo;
    private $github_api_url;
    
    public function __construct($plugin_file, $github_username, $github_repo, $version) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->version = $version;
        $this->github_username = $github_username;
        $this->github_repo = $github_repo;
        $this->github_api_url = "https://api.github.com/repos/{$github_username}/{$github_repo}";
        
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        
        // Add update check to admin
        add_action('admin_init', array($this, 'admin_init'));
    }
    
    /**
     * Check for plugin update
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Get remote version
        $remote_version = $this->get_remote_version();
        
        if (version_compare($this->version, $remote_version, '<')) {
            $plugin_data = $this->get_plugin_data();
            
            $transient->response[$this->plugin_slug] = (object) array(
                'slug' => $this->plugin_slug,
                'plugin' => $this->plugin_slug,
                'new_version' => $remote_version,
                'url' => $plugin_data['PluginURI'],
                'package' => $this->get_download_url($remote_version),
                'icons' => array(),
                'banners' => array(),
                'banners_rtl' => array(),
                'tested' => $plugin_data['tested'],
                'requires_php' => $plugin_data['RequiresPHP'],
                'compatibility' => new stdClass(),
            );
        }
        
        return $transient;
    }
    
    /**
     * Get remote version from GitHub
     */
    private function get_remote_version() {
        $request = wp_remote_get($this->github_api_url . '/releases/latest');
        
        if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
            $body = wp_remote_retrieve_body($request);
            $data = json_decode($body, true);
            
            if (isset($data['tag_name'])) {
                return ltrim($data['tag_name'], 'v'); // Remove 'v' prefix
            }
        }
        
        return false;
    }
    
    /**
     * Get download URL for specific version
     */
    private function get_download_url($version) {
        return $this->github_api_url . "/releases/download/v{$version}/wp-news-source-{$version}.zip";
    }
    
    /**
     * Get plugin data
     */
    private function get_plugin_data() {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return get_plugin_data($this->plugin_file);
    }
    
    /**
     * Show plugin information popup
     */
    public function plugin_popup($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        
        if ($args->slug !== $this->plugin_slug) {
            return $result;
        }
        
        $remote_version = $this->get_remote_version();
        $plugin_data = $this->get_plugin_data();
        
        return (object) array(
            'name' => $plugin_data['Name'],
            'slug' => $this->plugin_slug,
            'version' => $remote_version,
            'author' => $plugin_data['Author'],
            'author_profile' => $plugin_data['AuthorURI'],
            'last_updated' => date('Y-m-d'),
            'homepage' => $plugin_data['PluginURI'],
            'download_link' => $this->get_download_url($remote_version),
            'sections' => array(
                'description' => $plugin_data['Description'],
                'changelog' => $this->get_changelog(),
            ),
            'banners' => array(),
            'icons' => array(),
        );
    }
    
    /**
     * Get changelog from GitHub releases
     */
    private function get_changelog() {
        $request = wp_remote_get($this->github_api_url . '/releases');
        
        if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
            $body = wp_remote_retrieve_body($request);
            $releases = json_decode($body, true);
            
            $changelog = '';
            foreach (array_slice($releases, 0, 5) as $release) {
                $changelog .= '<h4>' . $release['tag_name'] . ' - ' . date('Y-m-d', strtotime($release['published_at'])) . '</h4>';
                $changelog .= wpautop($release['body']);
            }
            
            return $changelog;
        }
        
        return 'No changelog available.';
    }
    
    /**
     * Perform additional actions after installation
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;
        
        $install_directory = plugin_dir_path($this->plugin_file);
        $wp_filesystem->move($result['destination'], $install_directory);
        $result['destination'] = $install_directory;
        
        if ($this->plugin_slug) {
            activate_plugin($this->plugin_slug);
        }
        
        return $result;
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Add settings for update notifications
        add_settings_section(
            'wpns_updates',
            __('Automatic Updates', 'wp-news-source'),
            array($this, 'updates_section_callback'),
            'wp-news-source-settings'
        );
        
        add_settings_field(
            'wpns_auto_update',
            __('Enable automatic updates', 'wp-news-source'),
            array($this, 'auto_update_callback'),
            'wp-news-source-settings',
            'wpns_updates'
        );
        
        register_setting('wp-news-source-settings', 'wpns_auto_update');
        
        // Check for updates daily
        if (!wp_next_scheduled('wpns_check_updates')) {
            wp_schedule_event(time(), 'daily', 'wpns_check_updates');
        }
        
        add_action('wpns_check_updates', array($this, 'force_update_check'));
    }
    
    /**
     * Updates section callback
     */
    public function updates_section_callback() {
        echo '<p>' . __('Configure automatic updates from GitHub releases.', 'wp-news-source') . '</p>';
    }
    
    /**
     * Auto update field callback
     */
    public function auto_update_callback() {
        $value = get_option('wpns_auto_update', true);
        echo '<input type="checkbox" name="wpns_auto_update" value="1" ' . checked(1, $value, false) . '>';
        echo '<p class="description">' . __('Automatically check for and install updates from GitHub releases.', 'wp-news-source') . '</p>';
    }
    
    /**
     * Force update check
     */
    public function force_update_check() {
        delete_site_transient('update_plugins');
        wp_update_plugins();
    }
    
    /**
     * Manual update check via AJAX
     */
    public function manual_update_check() {
        check_ajax_referer('wpns_nonce', 'nonce');
        
        if (!current_user_can('update_plugins')) {
            wp_die(__('You do not have permission to update plugins.', 'wp-news-source'));
        }
        
        $remote_version = $this->get_remote_version();
        $current_version = $this->version;
        
        if ($remote_version && version_compare($current_version, $remote_version, '<')) {
            wp_send_json_success(array(
                'has_update' => true,
                'current_version' => $current_version,
                'new_version' => $remote_version,
                'message' => sprintf(
                    __('Update available! Current: %s, New: %s', 'wp-news-source'),
                    $current_version,
                    $remote_version
                )
            ));
        } else {
            wp_send_json_success(array(
                'has_update' => false,
                'current_version' => $current_version,
                'message' => __('You have the latest version.', 'wp-news-source')
            ));
        }
    }
}