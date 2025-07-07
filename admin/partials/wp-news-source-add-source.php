<?php
/**
 * Professional Add Source Form with Enhanced UI
 */

// Verify permissions
if (!current_user_can('manage_news_sources')) {
    wp_die(__('You do not have permission to access this page.', 'wp-news-source'));
}

// No need to load all categories and tags - using AJAX autocomplete
// Performance optimization for sites with thousands of categories/tags
?>

<div class="wrap wpns-admin">
    <a href="#wpns-main-content" class="wpns-skip-link"><?php _e('Skip to main content', 'wp-news-source'); ?></a>
    
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <nav class="wpns-nav-tabs" aria-label="<?php _e('Source Configuration', 'wp-news-source'); ?>">
        <a href="#general" class="nav-tab nav-tab-active" 
           role="tab" aria-selected="true" aria-controls="general-tab">
            <span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
            <?php _e('General', 'wp-news-source'); ?>
        </a>
        <a href="#detection" class="nav-tab" 
           role="tab" aria-selected="false" aria-controls="detection-tab">
            <span class="dashicons dashicons-search" aria-hidden="true"></span>
            <?php _e('Detection', 'wp-news-source'); ?>
        </a>
        <a href="#publishing" class="nav-tab" 
           role="tab" aria-selected="false" aria-controls="publishing-tab">
            <span class="dashicons dashicons-admin-post" aria-hidden="true"></span>
            <?php _e('Publishing', 'wp-news-source'); ?>
        </a>
        <a href="#advanced" class="nav-tab" 
           role="tab" aria-selected="false" aria-controls="advanced-tab">
            <span class="dashicons dashicons-admin-tools" aria-hidden="true"></span>
            <?php _e('Advanced', 'wp-news-source'); ?>
        </a>
    </nav>

    <div class="wpns-container">
        <main class="wpns-main" id="wpns-main-content">
                <!-- General Tab -->
                <div id="general-tab" class="wpns-tab-content active" 
                     role="tabpanel" aria-labelledby="general-tab-button">
                    
                    <section class="wpns-card" aria-labelledby="basic-info-heading">
                        <h2 id="basic-info-heading">
                            <span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
                            <?php _e('Basic Information', 'wp-news-source'); ?>
                        </h2>

                        <div class="wpns-form-group">
                            <label for="source-name"><?php _e('Source Name', 'wp-news-source'); ?> <span class="required">*</span></label>
                            <input type="text" id="source-name" name="name" class="regular-text" required
                                   aria-describedby="source-name-desc">
                            <p class="description" id="source-name-desc">
                                <?php _e('Example: Government Agency, Red Cross, Company Name, etc.', 'wp-news-source'); ?>
                            </p>
                        </div>

                        <div class="wpns-form-group">
                            <label for="source-slug"><?php _e('Slug', 'wp-news-source'); ?></label>
                            <input type="text" id="source-slug" name="slug" class="regular-text"
                                   aria-describedby="source-slug-desc">
                            <p class="description" id="source-slug-desc">
                                <?php _e('Unique identifier. Will be generated automatically if left empty.', 'wp-news-source'); ?>
                            </p>
                        </div>

                        <div class="wpns-form-group">
                            <label for="source-type"><?php _e('Source Type', 'wp-news-source'); ?></label>
                            <select id="source-type" name="source_type">
                                <option value="general"><?php _e('General', 'wp-news-source'); ?></option>
                                <option value="government"><?php _e('Government', 'wp-news-source'); ?></option>
                                <option value="company"><?php _e('Company', 'wp-news-source'); ?></option>
                                <option value="ngo"><?php _e('NGO', 'wp-news-source'); ?></option>
                            </select>
                        </div>

                        <div class="wpns-form-group">
                            <label for="source-category"><?php _e('Primary Category', 'wp-news-source'); ?> <span class="required">*</span></label>
                            <div class="wpns-autocomplete-container">
                                <input type="text" id="source-category-search" class="regular-text" 
                                       placeholder="<?php _e('Start typing to search categories...', 'wp-news-source'); ?>"
                                       aria-describedby="source-category-desc">
                                <input type="hidden" id="source-category" name="category_id" value="" required>
                                <div id="source-category-results" class="wpns-autocomplete-results"></div>
                                <div id="source-category-selected" class="wpns-selected-item"></div>
                            </div>
                            <p class="description" id="source-category-desc">
                                <?php _e('This category will be automatically assigned when this source is detected.', 'wp-news-source'); ?>
                            </p>
                        </div>

                        <div class="wpns-form-group">
                            <label for="source-tags"><?php _e('Associated Tags', 'wp-news-source'); ?></label>
                            <div class="wpns-autocomplete-container">
                                <input type="text" id="source-tags-search" class="regular-text" 
                                       placeholder="<?php _e('Start typing to search tags...', 'wp-news-source'); ?>"
                                       aria-describedby="source-tags-desc">
                                <div id="source-tags-results" class="wpns-autocomplete-results"></div>
                                <div id="source-tags-selected" class="wpns-selected-items">
                                    <!-- Selected tags will appear here -->
                                </div>
                            </div>
                            <p class="description" id="source-tags-desc">
                                <?php _e('These tags will be automatically assigned. If they don\'t exist, they will be created.', 'wp-news-source'); ?>
                            </p>
                        </div>
                    </section>
                </div>

                <!-- Detection Tab -->
                <div id="detection-tab" class="wpns-tab-content" 
                     role="tabpanel" aria-labelledby="detection-tab-button">
                    
                    <section class="wpns-card" aria-labelledby="detection-heading">
                        <h2 id="detection-heading">
                            <span class="dashicons dashicons-search" aria-hidden="true"></span>
                            <?php _e('AI Detection Settings', 'wp-news-source'); ?>
                        </h2>

                        <div class="wpns-form-group">
                            <label for="source-description"><?php _e('Description / Context', 'wp-news-source'); ?></label>
                            <textarea id="source-description" name="description" rows="5" class="large-text"
                                      aria-describedby="source-description-desc"></textarea>
                            <p class="description" id="source-description-desc">
                                <?php _e('Describe the context to detect this source. AI will use this information to improve detection.', 'wp-news-source'); ?>
                                <br>
                                <?php _e('Example: "This source applies when the bulletin mentions: State Government, Governor, state works, state social programs, state government departments"', 'wp-news-source'); ?>
                            </p>
                        </div>

                        <div class="wpns-form-group">
                            <label for="source-keywords"><?php _e('Keywords', 'wp-news-source'); ?></label>
                            <input type="text" id="source-keywords" name="keywords" class="large-text"
                                   aria-describedby="source-keywords-desc">
                            <p class="description" id="source-keywords-desc">
                                <?php _e('Comma-separated list of keywords that help identify this source.', 'wp-news-source'); ?>
                                <br>
                                <?php _e('Example: governor, state government, department, official statement', 'wp-news-source'); ?>
                            </p>
                        </div>

                        <div class="wpns-form-group">
                            <label for="source-detection-rules"><?php _e('Custom Detection Rules (JSON)', 'wp-news-source'); ?></label>
                            <textarea id="source-detection-rules" name="detection_rules" rows="6" class="large-text" 
                                      data-type="json"
                                      placeholder='[{"type": "contains", "value": "government", "weight": 30}]'
                                      aria-describedby="source-detection-rules-desc"></textarea>
                            <p class="description" id="source-detection-rules-desc">
                                <?php _e('Advanced rules in JSON format for custom detection.', 'wp-news-source'); ?>
                                <br>
                                <strong><?php _e('Available rule types:', 'wp-news-source'); ?></strong>
                                <code>contains</code>, <code>regex</code>, <code>starts_with</code>, <code>word_count_min</code>
                            </p>
                        </div>
                    </section>
                </div>

                <!-- Publishing Tab -->
                <div id="publishing-tab" class="wpns-tab-content" 
                     role="tabpanel" aria-labelledby="publishing-tab-button">
                    
                    <section class="wpns-card" aria-labelledby="publishing-heading">
                        <h2 id="publishing-heading">
                            <span class="dashicons dashicons-admin-post" aria-hidden="true"></span>
                            <?php _e('Publishing Settings', 'wp-news-source'); ?>
                        </h2>

                        <div class="wpns-form-group">
                            <fieldset>
                                <legend class="screen-reader-text"><?php _e('Publishing behavior', 'wp-news-source'); ?></legend>
                                <div class="wpns-checkbox-group">
                                    <label>
                                        <input type="checkbox" id="source-auto-publish" name="auto_publish" value="1">
                                        <?php _e('Auto-publish bulletins from this source', 'wp-news-source'); ?>
                                    </label>
                                    <label>
                                        <input type="checkbox" id="source-requires-review" name="requires_review" value="1" checked>
                                        <?php _e('Requires review before publishing', 'wp-news-source'); ?>
                                    </label>
                                </div>
                            </fieldset>
                        </div>

                        <div class="wpns-form-group">
                            <label for="source-webhook"><?php _e('Webhook URL (Optional)', 'wp-news-source'); ?></label>
                            <input type="url" id="source-webhook" name="webhook_url" class="large-text"
                                   aria-describedby="source-webhook-desc">
                            <p class="description" id="source-webhook-desc">
                                <?php _e('URL for notifications when this source is detected. Useful for n8n integration.', 'wp-news-source'); ?>
                            </p>
                        </div>
                    </section>
                </div>

                <!-- Advanced Tab -->
                <div id="advanced-tab" class="wpns-tab-content" 
                     role="tabpanel" aria-labelledby="advanced-tab-button">
                    
                    <section class="wpns-card" aria-labelledby="advanced-heading">
                        <h2 id="advanced-heading">
                            <span class="dashicons dashicons-admin-tools" aria-hidden="true"></span>
                            <?php _e('Advanced Settings', 'wp-news-source'); ?>
                        </h2>

                        <div class="wpns-form-group">
                            <label>
                                <input type="checkbox" name="generate_api_key" value="1">
                                <?php _e('Generate specific API Key for this source', 'wp-news-source'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Creates a unique API key that can be used for this source specifically.', 'wp-news-source'); ?>
                            </p>
                        </div>
                    </section>
                </div>

                <div class="wpns-quick-actions">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-yes" aria-hidden="true"></span>
                        <?php _e('Save Source', 'wp-news-source'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=wp-news-source'); ?>" class="button">
                        <span class="dashicons dashicons-arrow-left-alt" aria-hidden="true"></span>
                        <?php _e('Cancel', 'wp-news-source'); ?>
                    </a>
                </div>
            </form>
        </main>

        <aside class="wpns-sidebar">
            <div class="wpns-card" aria-labelledby="help-heading">
                <h3 id="help-heading">
                    <span class="dashicons dashicons-lightbulb" aria-hidden="true"></span>
                    <?php _e('Quick Tips', 'wp-news-source'); ?>
                </h3>
                <ul class="wpns-help-list">
                    <li><?php _e('Use descriptive names for easy identification', 'wp-news-source'); ?></li>
                    <li><?php _e('Add relevant keywords for better AI detection', 'wp-news-source'); ?></li>
                    <li><?php _e('Test detection with sample content first', 'wp-news-source'); ?></li>
                    <li><?php _e('Configure webhooks for automated workflows', 'wp-news-source'); ?></li>
                </ul>
            </div>

            <div class="wpns-card" aria-labelledby="api-heading">
                <h3 id="api-heading">
                    <span class="dashicons dashicons-admin-links" aria-hidden="true"></span>
                    <?php _e('API Integration', 'wp-news-source'); ?>
                </h3>
                <p><?php _e('Once saved, this source will be available via:', 'wp-news-source'); ?></p>
                <div class="wpns-info-box">
                    <code data-copy="<?php echo esc_attr(rest_url('wp-news-source/v1/detect')); ?>">
                        /wp-json/wp-news-source/v1/detect
                    </code>
                    <p class="wpns-mb-0"><?php _e('Click to copy endpoint URL', 'wp-news-source'); ?></p>
                </div>
            </div>
        </aside>
    </div>
</div>