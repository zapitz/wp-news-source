<?php
/**
 * Settings Page - WP News Source
 */

// Check permissions
if (!current_user_can('manage_news_sources')) {
    wp_die(__('You do not have permission to access this page.', 'wp-news-source'));
}

// Save settings
if (isset($_POST['submit'])) {
    check_admin_referer('wpns_settings');
    
    // API settings
    update_option('wp_news_source_require_api_key', isset($_POST['require_api_key']) ? 1 : 0);

    // Telegram bot token
    if (isset($_POST['telegram_bot_token'])) {
        update_option('wp_news_source_telegram_bot_token', sanitize_text_field($_POST['telegram_bot_token']));
    }
    
    // Max tags
    $max_tags = isset($_POST['max_tags']) ? intval($_POST['max_tags']) : 3;
    $max_tags = max(1, min(5, $max_tags));
    update_option('wp_news_source_max_tags', $max_tags);
    
    // Version settings
    update_option('wpns_enable_prereleases', isset($_POST['wpns_enable_prereleases']) ? 1 : 0);
    update_option('wpns_auto_update', isset($_POST['wpns_auto_update']) ? 1 : 0);
    
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully.', 'wp-news-source') . '</p></div>';
}

// Handle AJAX actions for API Key and Webhook regeneration
if (isset($_POST['regenerate_api_key'])) {
    check_admin_referer('wpns_regenerate_api_key');
    update_option('wp_news_source_api_key', wp_generate_password(32, false));
    echo '<div class="notice notice-success is-dismissible"><p>' . __('API Key regenerated successfully.', 'wp-news-source') . '</p></div>';
}

if (isset($_POST['regenerate_webhook_secret'])) {
    check_admin_referer('wpns_regenerate_webhook_secret');
    update_option('wp_news_source_webhook_secret', wp_generate_password(16, false));
    echo '<div class="notice notice-warning is-dismissible"><p>' . __('Webhook Secret regenerated. Update your webhooks!', 'wp-news-source') . '</p></div>';
}

// Get current values
$require_api_key = get_option('wp_news_source_require_api_key', false);
$api_key = get_option('wp_news_source_api_key');
if (empty($api_key)) {
    $api_key = wp_generate_password(32, false);
    update_option('wp_news_source_api_key', $api_key);
}

$webhook_secret = get_option('wp_news_source_webhook_secret');
if (empty($webhook_secret)) {
    $webhook_secret = wp_generate_password(16, false);
    update_option('wp_news_source_webhook_secret', $webhook_secret);
}

$max_tags = get_option('wp_news_source_max_tags', 3);
?>

<div class="wrap wpns-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Tab Navigation -->
    <nav class="wpns-nav-tabs" aria-label="<?php _e('Settings Navigation', 'wp-news-source'); ?>">
        <a href="#general" class="nav-tab nav-tab-active" data-tab="general">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php _e('General', 'wp-news-source'); ?>
        </a>
        <a href="#api-security" class="nav-tab" data-tab="api-security">
            <span class="dashicons dashicons-lock"></span>
            <?php _e('API & Security', 'wp-news-source'); ?>
        </a>
        <a href="#n8n-integration" class="nav-tab" data-tab="n8n-integration">
            <span class="dashicons dashicons-rest-api"></span>
            <?php _e('n8n Integration', 'wp-news-source'); ?>
        </a>
        <a href="#import-export" class="nav-tab" data-tab="import-export">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php _e('Import/Export', 'wp-news-source'); ?>
        </a>
    </nav>
    
    <div class="wpns-container">
        <!-- Main Content Area -->
        <div class="wpns-main">
            <form method="post" action="" class="wpns-form">
                <?php wp_nonce_field('wpns_settings'); ?>
                
                <!-- General Tab -->
                <div id="general-tab" class="wpns-tab-content active">
                    <div class="wpns-card">
                        <h2><?php _e('Source Settings', 'wp-news-source'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Tag Limit', 'wp-news-source'); ?></th>
                                <td>
                                    <select name="max_tags" id="max_tags">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php selected($max_tags, $i); ?>><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <p class="description">
                                        <?php _e('Maximum number of tags per source.', 'wp-news-source'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="wpns-card">
                        <h2><?php _e('Version Management', 'wp-news-source'); ?></h2>
                        
                        <div class="wpns-version-info-box">
                            <div class="wpns-version-current">
                                <strong><?php _e('Current Version:', 'wp-news-source'); ?></strong>
                                <span class="version-number">v<?php echo WP_NEWS_SOURCE_VERSION; ?></span>
                                <span id="wpns-update-status" class="update-status"></span>
                            </div>
                            <button type="button" class="button button-primary" id="wpns-check-updates-btn">
                                <span class="dashicons dashicons-update"></span> <?php _e('Check for Updates', 'wp-news-source'); ?>
                            </button>
                        </div>
                        
                        <div id="wpns-update-message" style="display: none;"></div>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Version Options', 'wp-news-source'); ?></th>
                                <td>
                                    <select id="wpns-version-select" disabled>
                                        <option value=""><?php _e('Loading versions...', 'wp-news-source'); ?></option>
                                    </select>
                                    <button type="button" class="button" id="wpns-rollback-btn" disabled>
                                        <?php _e('Switch Version', 'wp-news-source'); ?>
                                    </button>
                                    <p class="description">
                                        <?php _e('Roll back to a previous version if needed.', 'wp-news-source'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Update Settings', 'wp-news-source'); ?></th>
                                <td>
                                    <label class="wpns-checkbox-label">
                                        <input type="checkbox" name="wpns_enable_prereleases" id="wpns_enable_prereleases" value="1" <?php checked(get_option('wpns_enable_prereleases'), 1); ?>>
                                        <?php _e('Include pre-release versions', 'wp-news-source'); ?>
                                    </label>
                                    <br>
                                    <label class="wpns-checkbox-label">
                                        <input type="checkbox" name="wpns_auto_update" id="wpns_auto_update" value="1" <?php checked(get_option('wpns_auto_update', 1), 1); ?>>
                                        <?php _e('Enable automatic updates', 'wp-news-source'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- API & Security Tab -->
                <div id="api-security-tab" class="wpns-tab-content">
                    <div class="wpns-card">
                        <h2><?php _e('API Configuration', 'wp-news-source'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('API Key Requirement', 'wp-news-source'); ?></th>
                                <td>
                                    <label class="wpns-toggle-switch">
                                        <input type="checkbox" name="require_api_key" value="1" <?php checked($require_api_key); ?>>
                                        <span class="wpns-toggle-slider"></span>
                                    </label>
                                    <span class="wpns-toggle-label"><?php _e('Require API Key for all requests', 'wp-news-source'); ?></span>
                                    <p class="description">
                                        <?php _e('When enabled, all API requests must include the X-API-Key header.', 'wp-news-source'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('API Key', 'wp-news-source'); ?></th>
                                <td>
                                    <div class="wpns-api-key-container">
                                        <input type="text" class="regular-text wpns-api-key-field" value="<?php echo esc_attr($api_key); ?>" readonly>
                                        <button type="button" class="button wpns-copy-api-key" data-api-key="<?php echo esc_attr($api_key); ?>">
                                            <span class="dashicons dashicons-clipboard"></span> <?php _e('Copy', 'wp-news-source'); ?>
                                        </button>
                                    </div>
                                    <p class="description">
                                        <?php _e('Use this key in the X-API-Key header for all API requests.', 'wp-news-source'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Webhook Secret', 'wp-news-source'); ?></th>
                                <td>
                                    <div class="wpns-webhook-secret-container">
                                        <input type="text" class="regular-text" value="<?php echo esc_attr($webhook_secret); ?>" readonly>
                                        <button type="button" class="button wpns-copy-webhook" data-secret="<?php echo esc_attr($webhook_secret); ?>">
                                            <span class="dashicons dashicons-clipboard"></span> <?php _e('Copy', 'wp-news-source'); ?>
                                        </button>
                                    </div>
                                    <p class="description">
                                        <?php _e('Include this secret in the X-Webhook-Secret header for incoming webhooks.', 'wp-news-source'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php _e('Telegram Bot Token', 'wp-news-source'); ?></th>
                                <td>
                                    <input type="password" name="telegram_bot_token" class="regular-text"
                                           value="<?php echo esc_attr(get_option('wp_news_source_telegram_bot_token', '')); ?>"
                                           autocomplete="off">
                                    <p class="description">
                                        <?php _e('Bot token for downloading Telegram images. Used by the create-post and upload-image endpoints.', 'wp-news-source'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- n8n Integration Tab -->
                <div id="n8n-integration-tab" class="wpns-tab-content">
                    <div class="wpns-card">
                        <h2><?php _e('API Endpoints', 'wp-news-source'); ?></h2>
                        <p><?php _e('Complete list of available REST API endpoints for n8n integration:', 'wp-news-source'); ?></p>
                        
                        <div class="wpns-endpoints-grid">
                            <?php 
                            $endpoints = array(
                                'mapping' => __('Get complete source mapping with categories and tags', 'wp-news-source'),
                                'sources' => __('List sources (GET), Create source (POST). Supports pagination: ?page=1&per_page=20&source_type=government', 'wp-news-source'),
                                'sources/{id}' => __('Update source (PUT/PATCH), Delete source (DELETE)', 'wp-news-source'),
                                'detect' => __('Detect source in content using AI rules (POST)', 'wp-news-source'),
                                'generate-detection-prompt' => __('Generate AI detection prompt for external AI processing (POST)', 'wp-news-source'),
                                'validate' => __('Validate content against source AI rules (POST)', 'wp-news-source'),
                                'create-post' => __('Create WordPress post with auto-categorization (POST)', 'wp-news-source'),
                                'search-posts' => __('Search posts with advanced filters (POST)', 'wp-news-source'),
                                'upload-image' => __('Upload image independently (POST)', 'wp-news-source'),
                                'prompts' => __('Get all AI prompts (GET), Save prompt (POST) - API only', 'wp-news-source'),
                                'prompts/{key}' => __('Get specific AI prompt by key (GET) - API only', 'wp-news-source'),
                                'webhook/{source_id}' => __('Webhook endpoint for source notifications (POST)', 'wp-news-source'),
                                'history' => __('Get detection history with optional filters', 'wp-news-source'),
                                'stats' => __('Get source statistics and performance', 'wp-news-source'),
                                'categories' => __('List WordPress categories', 'wp-news-source'),
                                'tags' => __('List WordPress tags', 'wp-news-source'),
                                'export' => __('Export sources configuration as JSON', 'wp-news-source'),
                                'import' => __('Import sources from JSON (POST)', 'wp-news-source')
                            );
                            
                            foreach ($endpoints as $endpoint => $description): 
                                $url = rest_url('wp-news-source/v1/' . $endpoint);
                            ?>
                                <div class="wpns-endpoint-item">
                                    <code><?php echo esc_html($url); ?></code>
                                    <span class="description"><?php echo esc_html($description); ?></span>
                                    <button type="button" class="button button-small wpns-copy-endpoint" data-endpoint="<?php echo esc_attr($url); ?>">
                                        <?php _e('Copy', 'wp-news-source'); ?>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="wpns-card">
                        <h2><?php _e('n8n Code Node Script', 'wp-news-source'); ?></h2>
                        <p class="description"><?php _e('Copy this code into an n8n Code node for easy integration:', 'wp-news-source'); ?></p>
                        
                        <div class="wpns-code-container">
                            <textarea id="wpns-n8n-code" readonly><?php 
                                $site_url = home_url();
                                $n8n_code = file_get_contents(WP_NEWS_SOURCE_PLUGIN_DIR . 'n8n-integration.js');
                                $n8n_code = str_replace('https://your-site.com', $site_url, $n8n_code);
                                $n8n_code = str_replace('your-api-key-here', $api_key, $n8n_code);
                                $n8n_code = str_replace('requireApiKey: true', 'requireApiKey: ' . ($require_api_key ? 'true' : 'false'), $n8n_code);
                                echo esc_textarea($n8n_code);
                            ?></textarea>
                            <button type="button" class="button button-primary wpns-copy-code" onclick="copyN8nCode()">
                                <span class="dashicons dashicons-clipboard"></span> <?php _e('Copy Code', 'wp-news-source'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Import/Export Tab -->
                <div id="import-export-tab" class="wpns-tab-content">
                    <div class="wpns-card">
                        <h2><?php _e('Export Sources', 'wp-news-source'); ?></h2>
                        <p><?php _e('Export all your sources configuration to a JSON file for backup or migration.', 'wp-news-source'); ?></p>
                        <p>
                            <button type="button" class="button button-primary" id="wpns-export-btn">
                                <span class="dashicons dashicons-download"></span> <?php _e('Export All Sources', 'wp-news-source'); ?>
                            </button>
                        </p>
                    </div>
                    
                    <div class="wpns-card">
                        <h2><?php _e('Import Sources', 'wp-news-source'); ?></h2>
                        <p><?php _e('Import sources from a previously exported JSON file.', 'wp-news-source'); ?></p>
                        
                        <div class="wpns-import-options">
                            <h4><?php _e('Import Options', 'wp-news-source'); ?></h4>
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" id="wpns-match-by-name" checked>
                                <?php _e('Match categories by name (recommended for different sites)', 'wp-news-source'); ?>
                            </label>
                            
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" id="wpns-skip-missing">
                                <?php _e('Skip sources if category not found', 'wp-news-source'); ?>
                            </label>
                            
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" id="wpns-use-default">
                                <?php _e('Use default category for missing categories', 'wp-news-source'); ?>
                            </label>
                            
                            <div id="wpns-default-category-wrapper" style="display: none; margin-left: 20px;">
                                <label>
                                    <?php _e('Default category:', 'wp-news-source'); ?>
                                    <?php 
                                    wp_dropdown_categories(array(
                                        'show_option_none' => __('— Select —', 'wp-news-source'),
                                        'option_none_value' => '',
                                        'orderby' => 'name',
                                        'id' => 'wpns-default-category',
                                        'name' => 'wpns_default_category',
                                        'class' => 'wpns-select',
                                        'hide_empty' => false
                                    ));
                                    ?>
                                </label>
                            </div>
                        </div>
                        
                        <div class="wpns-import-section" style="margin-top: 20px;">
                            <input type="file" id="wpns-import-file" accept=".json" style="display: none;">
                            <button type="button" class="button button-primary" id="wpns-import-btn">
                                <span class="dashicons dashicons-upload"></span> <?php _e('Select File to Import', 'wp-news-source'); ?>
                            </button>
                            <p class="description"><?php _e('Note: This will add to your existing sources, not replace them.', 'wp-news-source'); ?></p>
                        </div>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button button-primary" value="<?php _e('Save Settings', 'wp-news-source'); ?>">
                </p>
            </form>
        </div>
        
        <!-- Sidebar -->
        <aside class="wpns-sidebar">
            <div class="wpns-sidebar-section">
                <h3><?php _e('Quick Actions', 'wp-news-source'); ?></h3>
                
                <div class="wpns-quick-action">
                    <h4><?php _e('API Key', 'wp-news-source'); ?></h4>
                    <form method="post" action="">
                        <?php wp_nonce_field('wpns_regenerate_api_key'); ?>
                        <p class="description"><?php _e('Generate a new API key. This will invalidate the current key.', 'wp-news-source'); ?></p>
                        <p>
                            <input type="submit" name="regenerate_api_key" class="button button-secondary" value="<?php _e('Regenerate API Key', 'wp-news-source'); ?>" onclick="return confirm('<?php _e('Are you sure? This will invalidate the current API key.', 'wp-news-source'); ?>');">
                        </p>
                    </form>
                </div>
                
                <div class="wpns-quick-action">
                    <h4><?php _e('Webhook Secret', 'wp-news-source'); ?></h4>
                    <form method="post" action="">
                        <?php wp_nonce_field('wpns_regenerate_webhook_secret'); ?>
                        <p class="description"><?php _e('Generate a new webhook secret.', 'wp-news-source'); ?></p>
                        <p>
                            <input type="submit" name="regenerate_webhook_secret" class="button button-secondary" value="<?php _e('Regenerate Secret', 'wp-news-source'); ?>" onclick="return confirm('<?php _e('Are you sure? You will need to update all webhooks.', 'wp-news-source'); ?>');">
                        </p>
                    </form>
                </div>
            </div>
            
            <div class="wpns-sidebar-section">
                <h3><?php _e('Quick Links', 'wp-news-source'); ?></h3>
                <ul class="wpns-quick-links">
                    <li><a href="<?php echo admin_url('admin.php?page=wp-news-source'); ?>"><span class="dashicons dashicons-list-view"></span> <?php _e('All Sources', 'wp-news-source'); ?></a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=wp-news-source-add'); ?>"><span class="dashicons dashicons-plus-alt"></span> <?php _e('Add New Source', 'wp-news-source'); ?></a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=wp-news-source-stats'); ?>"><span class="dashicons dashicons-chart-bar"></span> <?php _e('Statistics', 'wp-news-source'); ?></a></li>
                    <li><a href="https://github.com/zapitz/wp-news-source" target="_blank"><span class="dashicons dashicons-book"></span> <?php _e('Documentation', 'wp-news-source'); ?></a></li>
                </ul>
            </div>
            
            <div class="wpns-sidebar-section" id="wpns-help-section">
                <h3><?php _e('Help', 'wp-news-source'); ?></h3>
                <div class="wpns-help-content">
                    <p><?php _e('Select a tab to see relevant help information.', 'wp-news-source'); ?></p>
                </div>
            </div>
        </aside>
    </div>
</div>

<style>
/* Base Layout */
.wpns-admin {
    max-width: 1200px;
    margin: 20px 0;
}

/* Tab Navigation */
.wpns-nav-tabs {
    border-bottom: 1px solid #c3c4c7;
    margin: 0 0 20px 0;
    display: flex;
    flex-wrap: wrap;
    gap: 0;
}

.wpns-nav-tabs .nav-tab {
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    text-decoration: none;
    color: #646970;
    border: 1px solid transparent;
    border-bottom: none;
    background: #f9f9f9;
    transition: all 0.2s ease;
    font-weight: 500;
    margin: 0;
}

.wpns-nav-tabs .nav-tab:hover {
    background: #fff;
    color: #1d2327;
}

.wpns-nav-tabs .nav-tab-active {
    background: #fff;
    color: #0073aa;
    border-color: #c3c4c7;
    border-bottom-color: #fff;
    margin-bottom: -1px;
}

.wpns-nav-tabs .nav-tab .dashicons {
    font-size: 16px;
}

/* Container Layout */
.wpns-container {
    display: flex;
    gap: 20px;
    align-items: flex-start;
}

.wpns-main {
    flex: 2;
    min-width: 0;
}

.wpns-sidebar {
    flex: 1;
    min-width: 280px;
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    padding: 20px;
    border-radius: 4px;
}

/* Tab Content */
.wpns-tab-content {
    display: none;
}

.wpns-tab-content.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Cards */
.wpns-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 20px;
    margin-bottom: 20px;
}

.wpns-card h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    font-size: 1.3em;
    font-weight: 600;
    color: #1d2327;
}

/* Toggle Switch */
.wpns-toggle-switch,
.wpns-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
    vertical-align: middle;
    margin-right: 10px;
}

.wpns-toggle-switch input,
.wpns-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.wpns-toggle-slider,
.wpns-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.wpns-toggle-slider:before,
.wpns-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

.wpns-toggle-switch input:checked + .wpns-toggle-slider,
.wpns-switch input:checked + .wpns-slider {
    background-color: #0073aa;
}

.wpns-toggle-switch input:checked + .wpns-toggle-slider:before,
.wpns-switch input:checked + .wpns-slider:before {
    transform: translateX(26px);
}

.wpns-toggle-label {
    font-weight: 600;
}

/* OpenAI API Key Input */
.wpns-api-key-input {
    display: flex;
    gap: 10px;
    align-items: center;
}

.wpns-success-message {
    color: #00a32a;
    margin-top: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.wpns-success-message .dashicons {
    font-size: 16px;
}

.button-warning {
    background: #d63638 !important;
    color: #fff !important;
    border-color: #d63638 !important;
}

/* API Key and Secret Containers */
.wpns-api-key-container,
.wpns-webhook-secret-container {
    display: flex;
    gap: 10px;
    align-items: center;
}

.wpns-api-key-field {
    font-family: monospace;
    background: #f0f0f1;
}

/* Version Info Box */
.wpns-version-info-box {
    background: #f0f0f1;
    padding: 15px;
    border-radius: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.wpns-version-current {
    display: flex;
    align-items: center;
    gap: 10px;
}

.version-number {
    font-weight: bold;
    color: #0073aa;
}

.update-status {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}

.update-status.up-to-date {
    background: #46b450;
    color: white;
}

.update-status.update-available {
    background: #ffb900;
    color: #1d2327;
}

/* Endpoints Grid */
.wpns-endpoints-grid {
    display: grid;
    gap: 10px;
    margin-top: 15px;
}

.wpns-endpoint-item {
    display: grid;
    grid-template-columns: 1fr auto auto;
    gap: 10px;
    align-items: center;
    padding: 10px;
    background: #f6f7f7;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.wpns-endpoint-item code {
    font-size: 12px;
    background: none;
    padding: 0;
    word-break: break-all;
}

.wpns-endpoint-item .description {
    font-size: 12px;
    color: #666;
}

/* Code Container */
.wpns-code-container {
    position: relative;
    margin-top: 15px;
}

#wpns-n8n-code {
    width: 100%;
    height: 400px;
    font-family: monospace;
    font-size: 12px;
    background: #f0f0f1;
    padding: 15px;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.wpns-copy-code {
    position: absolute;
    top: 10px;
    right: 10px;
}

/* Sidebar Sections */
.wpns-sidebar-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #ddd;
}

.wpns-sidebar-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.wpns-sidebar-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.1em;
    color: #1d2327;
}

.wpns-quick-action {
    margin-bottom: 20px;
}

.wpns-quick-action h4 {
    margin: 0 0 8px 0;
    font-size: 14px;
    color: #3c434a;
}

.wpns-quick-action .description {
    font-size: 13px;
    color: #646970;
    margin: 0 0 10px 0;
}

/* Quick Links */
.wpns-quick-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.wpns-quick-links li {
    margin-bottom: 10px;
}

.wpns-quick-links a {
    text-decoration: none;
    color: #0073aa;
    display: flex;
    align-items: center;
    gap: 5px;
}

.wpns-quick-links a:hover {
    color: #005a87;
}

.wpns-quick-links .dashicons {
    font-size: 16px;
}

/* Update Message */
#wpns-update-message {
    margin: 15px 0;
    padding: 10px 15px;
    border-radius: 4px;
    border-left: 4px solid;
}

#wpns-update-message.notice-success {
    background: #f0f9ff;
    border-left-color: #46b450;
}

#wpns-update-message.notice-info {
    background: #e7f5ff;
    border-left-color: #0073aa;
}

#wpns-update-message.notice-error {
    background: #fee;
    border-left-color: #dc3232;
}

/* Checkbox Labels */
.wpns-checkbox-label {
    display: block;
    margin-bottom: 8px;
}

/* Import Section */
.wpns-import-section {
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
}

/* Responsive */
@media (max-width: 1024px) {
    .wpns-container {
        flex-direction: column;
    }
    
    .wpns-sidebar {
        width: 100%;
        margin-top: 20px;
    }
    
    .wpns-endpoint-item {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .wpns-nav-tabs {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .wpns-nav-tabs .nav-tab {
        min-width: 120px;
        justify-content: center;
    }
}

/* Dashicons spin animation */
.dashicons.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab navigation
    $('.wpns-nav-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this).data('tab');
        
        // Update tabs
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Update content
        $('.wpns-tab-content').removeClass('active');
        $('#' + target + '-tab').addClass('active');
        
        // Update URL
        history.replaceState(null, null, '#' + target);
        
        // Update help content based on tab
        updateHelpContent(target);
    });
    
    // Load tab from URL
    if (window.location.hash) {
        var hash = window.location.hash.substring(1);
        $('[data-tab="' + hash + '"]').click();
    }
    
    // Update help content
    function updateHelpContent(tab) {
        var helpContent = '';
        
        switch(tab) {
            case 'general':
                helpContent = '<?php _e('<p>Configure basic plugin settings and manage versions.</p><ul><li>Set the maximum number of tags per source</li><li>Check for plugin updates</li><li>Enable automatic updates</li></ul>', 'wp-news-source'); ?>';
                break;
            case 'api-security':
                helpContent = '<?php _e('<p>Manage API security settings and credentials.</p><ul><li>Enable/disable API key requirement</li><li>View and copy your API key</li><li>Manage webhook secret</li></ul>', 'wp-news-source'); ?>';
                break;
            case 'n8n-integration':
                helpContent = '<?php _e('<p>Everything you need for n8n integration.</p><ul><li>Complete list of API endpoints</li><li>Ready-to-use n8n code</li><li>Integration examples</li></ul>', 'wp-news-source'); ?>';
                break;
            case 'import-export':
                helpContent = '<?php _e('<p>Backup and restore your sources.</p><ul><li>Export all sources to JSON</li><li>Import sources from backup</li><li>Migrate between sites</li></ul>', 'wp-news-source'); ?>';
                break;
        }
        
        $('.wpns-help-content').html(helpContent);
    }
    
    // Initialize help content
    updateHelpContent('general');
    
    // Copy functions
    function copyToClipboard(text, button) {
        var $btn = $(button);
        var originalText = $btn.text();

        function showCopied() {
            $btn.text('<?php _e("Copied!", "wp-news-source"); ?>');
            setTimeout(function() {
                $btn.text(originalText);
            }, 2000);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(showCopied).catch(function() {
                // Fallback for clipboard API failure
                var tempInput = $('<textarea>');
                $('body').append(tempInput);
                tempInput.val(text).select();
                document.execCommand('copy');
                tempInput.remove();
                showCopied();
            });
        } else {
            var tempInput = $('<textarea>');
            $('body').append(tempInput);
            tempInput.val(text).select();
            document.execCommand('copy');
            tempInput.remove();
            showCopied();
        }
    }

    $('.wpns-copy-api-key, .wpns-copy-webhook, .wpns-copy-endpoint').on('click', function() {
        var button = this;
        var $btn = $(button);
        var text = '';

        if ($btn.hasClass('wpns-copy-api-key')) {
            text = $btn.data('api-key');
        } else if ($btn.hasClass('wpns-copy-webhook')) {
            text = $btn.data('secret');
        } else if ($btn.hasClass('wpns-copy-endpoint')) {
            text = $btn.data('endpoint');
        }

        copyToClipboard(text, button);
    });
    
    // Version Management
    let availableVersions = [];
    
    // Load available versions on page load
    loadAvailableVersions();
    
    // Check for updates button
    $('#wpns-check-updates-btn').on('click', function() {
        var $btn = $(this);
        var originalHtml = $btn.html();
        
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php _e("Checking...", "wp-news-source"); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpns_check_updates',
                nonce: '<?php echo wp_create_nonce('wpns_check_updates'); ?>'
            },
            success: function(response) {
                if (response.success && response.data) {
                    displayUpdateStatus(response.data);
                    if (response.data.has_update) {
                        loadAvailableVersions();
                    }
                } else {
                    showUpdateMessage('error', response.data?.message || '<?php _e("Error checking for updates", "wp-news-source"); ?>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Update check error:', error);
                showUpdateMessage('error', '<?php _e("Failed to check for updates. Please try again.", "wp-news-source"); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
    
    // Load available versions
    function loadAvailableVersions() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpns_get_versions',
                nonce: '<?php echo wp_create_nonce('wpns_get_versions'); ?>',
                include_prereleases: $('#wpns_enable_prereleases').is(':checked')
            },
            success: function(response) {
                if (response.success && response.data.versions) {
                    availableVersions = response.data.versions;
                    updateVersionSelector();
                }
            }
        });
    }
    
    // Update version selector
    function updateVersionSelector() {
        var $select = $('#wpns-version-select');
        var currentVersion = '<?php echo WP_NEWS_SOURCE_VERSION; ?>';
        
        $select.empty().prop('disabled', false);
        $select.append('<option value=""><?php _e("Select a version", "wp-news-source"); ?></option>');
        
        availableVersions.forEach(function(version) {
            if (version.version !== currentVersion) {
                var label = 'v' + version.version;
                if (version.prerelease) {
                    label += ' (beta)';
                }
                label += ' - ' + version.date;
                
                $select.append($('<option>', {
                    value: version.version,
                    text: label
                }));
            }
        });
        
        $('#wpns-rollback-btn').prop('disabled', false);
    }
    
    // Display update status
    function displayUpdateStatus(data) {
        var $status = $('#wpns-update-status');
        
        if (data.has_update) {
            $status.removeClass('up-to-date').addClass('update-available')
                   .text('<?php _e("Update Available", "wp-news-source"); ?>').show();
            showUpdateMessage('info', data.message);
        } else if (data.api_error) {
            // Handle API error case - still show "up to date" status but with warning message
            $status.removeClass('update-available').addClass('up-to-date')
                   .text('<?php _e("Up to date", "wp-news-source"); ?>').show();
            showUpdateMessage('info', data.message);
        } else {
            $status.removeClass('update-available').addClass('up-to-date')
                   .text('<?php _e("Up to date", "wp-news-source"); ?>').show();
            showUpdateMessage('success', data.message);
        }
    }
    
    // Show update message
    function showUpdateMessage(type, message) {
        var $msg = $('#wpns-update-message');
        $msg.removeClass('notice-success notice-error notice-info')
            .addClass('notice-' + type)
            .html(message)
            .slideDown();
    }
    
    // Version rollback
    $('#wpns-rollback-btn').on('click', function() {
        var selectedVersion = $('#wpns-version-select').val();
        if (!selectedVersion) {
            alert('<?php _e("Please select a version", "wp-news-source"); ?>');
            return;
        }
        
        if (!confirm('<?php _e("Are you sure you want to switch to version", "wp-news-source"); ?> ' + selectedVersion + '?')) {
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.text();
        
        $btn.prop('disabled', true).text('<?php _e("Switching...", "wp-news-source"); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpns_rollback_version',
                version: selectedVersion,
                nonce: '<?php echo wp_create_nonce('wpns_rollback_version'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showUpdateMessage('success', response.data.message);
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    showUpdateMessage('error', response.data.message || '<?php _e("Error switching versions", "wp-news-source"); ?>');
                }
            },
            error: function() {
                showUpdateMessage('error', '<?php _e("Failed to switch versions", "wp-news-source"); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Pre-release checkbox change
    $('#wpns_enable_prereleases').on('change', function() {
        loadAvailableVersions();
    });
    
    // Export functionality
    $('#wpns-export-btn').on('click', function() {
        var $btn = $(this);
        var originalHtml = $btn.html();
        
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php _e("Exporting...", "wp-news-source"); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wpns_export_sources',
                nonce: '<?php echo wp_create_nonce('wpns_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var blob = new Blob([JSON.stringify(response.data.data, null, 2)], { type: 'application/json' });
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = response.data.filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
    
    // Import functionality
    $('#wpns-import-btn').on('click', function() {
        $('#wpns-import-file').click();
    });
    
    // Show/hide default category dropdown
    $('#wpns-use-default').on('change', function() {
        if ($(this).is(':checked')) {
            $('#wpns-default-category-wrapper').slideDown();
        } else {
            $('#wpns-default-category-wrapper').slideUp();
        }
    });
    
    // Disable conflicting options
    $('#wpns-skip-missing, #wpns-use-default').on('change', function() {
        if ($(this).is(':checked')) {
            var otherId = $(this).attr('id') === 'wpns-skip-missing' ? 'wpns-use-default' : 'wpns-skip-missing';
            $('#' + otherId).prop('checked', false);
            if (otherId === 'wpns-use-default') {
                $('#wpns-default-category-wrapper').slideUp();
            }
        }
    });
    
    $('#wpns-import-file').on('change', function() {
        var file = this.files[0];
        if (!file) return;
        
        var reader = new FileReader();
        reader.onload = function(e) {
            var content = e.target.result;
            
            // Try to validate and fix JSON before sending
            try {
                var parsed = JSON.parse(content);
                
                // If parsed is a string, it's double-encoded
                if (typeof parsed === 'string') {
                    console.log('Double-encoded JSON detected, attempting to fix...');
                    parsed = JSON.parse(parsed);
                }
                
                // Validate it's an array
                if (!Array.isArray(parsed)) {
                    throw new Error('Import file must contain an array of sources');
                }
                
                // Re-stringify properly
                content = JSON.stringify(parsed);
                
            } catch (e) {
                alert('<?php _e("Invalid JSON file", "wp-news-source"); ?>: ' + e.message);
                return;
            }
            
            if (confirm('<?php _e("Are you sure you want to import sources? This will add to your existing sources.", "wp-news-source"); ?>')) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wpns_import_sources',
                        import_data: content,
                        match_by_name: $('#wpns-match-by-name').is(':checked') ? 1 : 0,
                        skip_missing: $('#wpns-skip-missing').is(':checked') ? 1 : 0,
                        use_default: $('#wpns-use-default').is(':checked') ? 1 : 0,
                        default_category: $('#wpns-default-category').val(),
                        nonce: '<?php echo wp_create_nonce('wpns_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data);
                            window.location.reload();
                        } else {
                            alert('<?php _e("Import failed", "wp-news-source"); ?>: ' + (response.data || 'Unknown error'));
                        }
                    }
                });
            }
        };
        reader.readAsText(file);
        this.value = '';
    });
});

// Copy n8n code
function copyN8nCode() {
    var codeTextarea = document.getElementById('wpns-n8n-code');
    var text = codeTextarea.value;
    var button = event.target.closest('button');
    var originalHtml = button.innerHTML;

    function showCopied() {
        button.innerHTML = '<span class="dashicons dashicons-yes"></span> <?php _e("Copied!", "wp-news-source"); ?>';
        setTimeout(function() {
            button.innerHTML = originalHtml;
        }, 2000);
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(showCopied).catch(function() {
            codeTextarea.select();
            document.execCommand('copy');
            showCopied();
        });
    } else {
        codeTextarea.select();
        document.execCommand('copy');
        showCopied();
    }
}
</script>