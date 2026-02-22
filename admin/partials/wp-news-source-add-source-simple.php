<?php
/**
 * Formulario simple para añadir/editar fuentes
 */

// Check permissions
if (!current_user_can('manage_news_sources')) {
    wp_die(__('You do not have permission to access this page.', 'wp-news-source'));
}

// Get data if we're editing
$source_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$source = null;

if ($source_id) {
    $db = new WP_News_Source_DB();
    $source = $db->get_source($source_id);
    
    if (!$source) {
        wp_die(__('Source not found.', 'wp-news-source'));
    }
}

// Default values
$name = $source ? $source->name : '';
$description = $source ? $source->description : '';
$keywords = $source ? $source->keywords : '';
$category_id = $source ? $source->category_id : '';
$category_name = $source ? $source->category_name : '';
$auto_publish = $source ? $source->auto_publish : 0;
$source_type = $source && isset($source->source_type) ? $source->source_type : 'general';

// AI Detection Config - usar detection_rules existente
$ai_config = '';
if ($source && !empty($source->detection_rules)) {
    $ai_config = $source->detection_rules;
}

// Tags
$selected_tags = array();
if ($source && !empty($source->tag_names)) {
    $selected_tags = explode(',', $source->tag_names);
}
?>

<div class="wrap wpns-admin">
    <h1>
        <?php echo $source_id ? __('Edit Source', 'wp-news-source') : __('Add New Source', 'wp-news-source'); ?>
    </h1>

    <form id="wpns-add-source-form" class="wpns-form">
        <?php if ($source_id): ?>
            <input type="hidden" name="source_id" value="<?php echo esc_attr($source_id); ?>">
        <?php endif; ?>

        <div class="wpns-card">
            <div class="wpns-card-body">
                <!-- Nombre de la fuente -->
                <div class="wpns-form-group">
                    <label for="source-name" class="wpns-form-label">
                        <?php _e('Source Name', 'wp-news-source'); ?> <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="source-name" 
                           name="name" 
                           class="wpns-form-control" 
                           value="<?php echo esc_attr($name); ?>" 
                           required
                           placeholder="<?php esc_attr_e('e.g. Government of Jalisco', 'wp-news-source'); ?>">
                    <p class="description">
                        <?php _e('This name will be used to detect the source in content.', 'wp-news-source'); ?>
                    </p>
                </div>

                <!-- Source Type -->
                <div class="wpns-form-group">
                    <label for="source-type-select" class="wpns-form-label">
                        <?php _e('Source Type', 'wp-news-source'); ?>
                    </label>
                    <select id="source-type-select" name="source_type" class="wpns-form-control" style="width: 300px;">
                        <?php foreach (WP_News_Source_Config::get_source_types() as $type_key => $type_label): ?>
                            <option value="<?php echo esc_attr($type_key); ?>" <?php selected($source_type, $type_key); ?>>
                                <?php echo esc_html($type_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php _e('Type of news source for filtering and organization.', 'wp-news-source'); ?>
                    </p>
                </div>

                <!-- Categoría -->
                <div class="wpns-form-group">
                    <label for="source-category" class="wpns-form-label">
                        <?php _e('WordPress Category', 'wp-news-source'); ?> <span class="required">*</span>
                    </label>
                    <div class="wpns-autocomplete-container">
                        <input type="text" 
                               id="source-category-search" 
                               class="wpns-form-control" 
                               placeholder="<?php esc_attr_e('Search category...', 'wp-news-source'); ?>"
                               <?php echo $category_id ? 'style="display:none;"' : ''; ?>>
                        
                        <div id="source-category-selected" class="wpns-selected-container" <?php echo !$category_id ? 'style="display:none;"' : ''; ?>>
                            <?php if ($category_id): ?>
                                <div class="wpns-selected-category">
                                    <span><strong><?php echo esc_html($category_name); ?></strong> (ID: <?php echo esc_html($category_id); ?>)</span>
                                    <span class="wpns-remove-category" title="<?php esc_attr_e('Remove category', 'wp-news-source'); ?>">×</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div id="source-category-results" class="wpns-autocomplete-results"></div>
                        <input type="hidden" id="source-category" name="category_id" value="<?php echo esc_attr($category_id); ?>" required>
                    </div>
                </div>

                <!-- Tags -->
                <div class="wpns-form-group">
                    <label for="source-tags" class="wpns-form-label">
                        <?php _e('WordPress Tags', 'wp-news-source'); ?>
                    </label>
                    <div class="wpns-autocomplete-container">
                        <input type="text" 
                               id="source-tags-search" 
                               class="wpns-form-control" 
                               placeholder="<?php esc_attr_e('Search or create tags...', 'wp-news-source'); ?>">
                        
                        <div id="source-tags-results" class="wpns-autocomplete-results"></div>
                        
                        <div id="source-tags-selected" class="wpns-tags-container">
                            <?php foreach ($selected_tags as $tag): ?>
                                <span class="wpns-tag-item" data-tag-name="<?php echo esc_attr(trim($tag)); ?>">
                                    <?php echo esc_html(trim($tag)); ?>
                                    <span class="wpns-remove-tag">×</span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <p class="description">
                        <?php _e('Press Enter to add tags.', 'wp-news-source'); ?>
                    </p>
                </div>

                <!-- Keywords -->
                <div class="wpns-form-group">
                    <label for="source-keywords" class="wpns-form-label">
                        <?php _e('Keywords', 'wp-news-source'); ?>
                    </label>
                    <textarea id="source-keywords" 
                              name="keywords" 
                              class="wpns-form-control" 
                              rows="3"
                              placeholder="<?php esc_attr_e('e.g. mayor, municipality, city hall, local government (comma separated)', 'wp-news-source'); ?>"><?php echo esc_textarea($keywords); ?></textarea>
                    <p class="description">
                        <?php _e('Keywords to help detect this source in content. Separate with commas.', 'wp-news-source'); ?>
                    </p>
                </div>

                <!-- Description -->
                <div class="wpns-form-group">
                    <label for="source-description" class="wpns-form-label">
                        <?php _e('Description', 'wp-news-source'); ?>
                    </label>
                    <textarea id="source-description" 
                              name="description" 
                              class="wpns-form-control" 
                              rows="3"
                              placeholder="<?php esc_attr_e('e.g. Official bulletins from the municipal government including press releases, announcements, and public programs', 'wp-news-source'); ?>"><?php echo esc_textarea($description); ?></textarea>
                    <p class="description">
                        <?php _e('Description to provide context for AI detection. Be specific about the type of content from this source.', 'wp-news-source'); ?>
                    </p>
                </div>

                <!-- AI Detection Configuration -->
                <div class="wpns-form-group wpns-ai-detection-section">
                    <h3 class="wpns-section-title">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php _e('AI Detection Configuration', 'wp-news-source'); ?>
                    </h3>
                    
                    <!-- Mode Toggle -->
                    <div class="wpns-mode-toggle">
                        <label class="wpns-radio-toggle">
                            <input type="radio" name="ai-config-mode" value="visual" checked>
                            <span><?php _e('Visual Mode', 'wp-news-source'); ?></span>
                        </label>
                        <label class="wpns-radio-toggle">
                            <input type="radio" name="ai-config-mode" value="json">
                            <span><?php _e('Advanced (JSON)', 'wp-news-source'); ?></span>
                        </label>
                    </div>

                    <!-- Visual Mode -->
                    <div id="wpns-visual-mode" class="wpns-config-mode">
                        <!-- Source Type -->
                        <div class="wpns-config-field">
                            <label for="source-type" class="wpns-form-label">
                                <span class="dashicons dashicons-category"></span>
                                <?php _e('Source Type', 'wp-news-source'); ?>
                            </label>
                            <select id="source-type" class="wpns-form-control" style="width: 300px;">
                                <option value=""><?php _e('Select type...', 'wp-news-source'); ?></option>
                                <option value="government"><?php _e('Government', 'wp-news-source'); ?></option>
                                <option value="company"><?php _e('Company/Business', 'wp-news-source'); ?></option>
                                <option value="association"><?php _e('Association/Chamber', 'wp-news-source'); ?></option>
                                <option value="person"><?php _e('Public Figure', 'wp-news-source'); ?></option>
                                <option value="institution"><?php _e('Institution', 'wp-news-source'); ?></option>
                            </select>
                        </div>

                        <!-- Main Identifiers -->
                        <div class="wpns-config-field">
                            <label class="wpns-form-label">
                                <span class="dashicons dashicons-tag"></span>
                                <?php _e('Main Identifiers', 'wp-news-source'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Names or terms that identify this source', 'wp-news-source'); ?>
                                <span class="wpns-help-tip" title="<?php _e('Add multiple identifiers by typing each one and pressing Enter. Examples: Mayor Name, Company Name, Institution Acronym', 'wp-news-source'); ?>">
                                    <span class="dashicons dashicons-info"></span>
                                </span>
                            </p>
                            <div class="wpns-tags-input-container">
                                <input type="text" id="main-identifiers-input" class="wpns-form-control" 
                                       placeholder="<?php _e('Enter identifier and press Enter (e.g., Mayor Name, Company Name)', 'wp-news-source'); ?>">
                                <div id="main-identifiers-tags" class="wpns-visual-tags"></div>
                                <small class="description" style="display: block; margin-top: 5px;">
                                    <?php _e('Add multiple identifiers by pressing Enter after each one', 'wp-news-source'); ?>
                                </small>
                            </div>
                        </div>

                        <!-- Required Context -->
                        <div class="wpns-config-field">
                            <label class="wpns-form-label">
                                <span class="dashicons dashicons-location"></span>
                                <?php _e('Required Context', 'wp-news-source'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Words that must appear together with identifiers', 'wp-news-source'); ?>
                                <span class="wpns-help-tip" title="<?php _e('Context words help distinguish this source from others with similar names. Example: If your mayor is John Smith, add the city name as context to avoid confusion with other John Smiths', 'wp-news-source'); ?>">
                                    <span class="dashicons dashicons-info"></span>
                                </span>
                            </p>
                            <div class="wpns-tags-input-container">
                                <input type="text" id="required-context-input" class="wpns-form-control" 
                                       placeholder="<?php _e('Enter context word and press Enter (e.g., city name, department)', 'wp-news-source'); ?>">
                                <div id="required-context-tags" class="wpns-visual-tags"></div>
                                <small class="description" style="display: block; margin-top: 5px;">
                                    <?php _e('Example: For "Mayor Johnson", add "Springfield" to ensure it only matches when both appear together', 'wp-news-source'); ?>
                                </small>
                            </div>
                        </div>

                        <!-- Jurisdiction/Coverage -->
                        <div class="wpns-config-field">
                            <label for="jurisdiction" class="wpns-form-label">
                                <span class="dashicons dashicons-admin-site"></span>
                                <?php _e('Jurisdiction/Coverage', 'wp-news-source'); ?>
                            </label>
                            <input type="text" id="jurisdiction" class="wpns-form-control" 
                                   placeholder="<?php _e('e.g., City Name, State/Province', 'wp-news-source'); ?>">
                        </div>

                        <!-- Level/Scope -->
                        <div class="wpns-config-field">
                            <label for="level-scope" class="wpns-form-label">
                                <span class="dashicons dashicons-chart-bar"></span>
                                <?php _e('Level/Scope', 'wp-news-source'); ?>
                            </label>
                            <select id="level-scope" class="wpns-form-control" style="width: 300px;">
                                <option value=""><?php _e('Select...', 'wp-news-source'); ?></option>
                                <option value="federal"><?php _e('Federal', 'wp-news-source'); ?></option>
                                <option value="state"><?php _e('State', 'wp-news-source'); ?></option>
                                <option value="municipal"><?php _e('Municipal', 'wp-news-source'); ?></option>
                                <option value="local"><?php _e('Local', 'wp-news-source'); ?></option>
                                <option value="national"><?php _e('National', 'wp-news-source'); ?></option>
                                <option value="international"><?php _e('International', 'wp-news-source'); ?></option>
                            </select>
                        </div>

                        <!-- Exclusions -->
                        <div class="wpns-config-field">
                            <label class="wpns-form-label">
                                <span class="dashicons dashicons-dismiss"></span>
                                <?php _e('Exclusions', 'wp-news-source'); ?>
                            </label>
                            <p class="description">
                                <?php _e('If these words appear, it\'s NOT this source', 'wp-news-source'); ?>
                                <span class="wpns-help-tip" title="<?php _e('Add words that indicate the content is from a different source. This helps prevent false positives.', 'wp-news-source'); ?>">
                                    <span class="dashicons dashicons-info"></span>
                                </span>
                            </p>
                            <div class="wpns-tags-input-container">
                                <input type="text" id="exclusions-input" class="wpns-form-control" 
                                       placeholder="<?php _e('Enter exclusion word and press Enter (e.g., other city names)', 'wp-news-source'); ?>">
                                <div id="exclusions-tags" class="wpns-visual-tags"></div>
                                <small class="description" style="display: block; margin-top: 5px;">
                                    <?php _e('Example: If detecting \"Mayor of Springfield\", exclude \"Mayor of Shelbyville\" to avoid confusion', 'wp-news-source'); ?>
                                </small>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="wpns-config-actions">
                            <button type="button" id="wpns-generate-config" class="button button-primary">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php _e('Generate Configuration', 'wp-news-source'); ?>
                            </button>
                            <button type="button" id="wpns-view-json" class="button">
                                <span class="dashicons dashicons-code-standards"></span>
                                <?php _e('View JSON', 'wp-news-source'); ?>
                            </button>
                        </div>
                        
                        <!-- JSON Preview -->
                        <div id="wpns-json-preview" style="display: none; margin-top: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h4 style="margin: 0;"><?php _e('Generated JSON Configuration:', 'wp-news-source'); ?></h4>
                                <button type="button" id="wpns-copy-json" class="button button-small">
                                    <span class="dashicons dashicons-clipboard"></span>
                                    <?php _e('Copy', 'wp-news-source'); ?>
                                </button>
                            </div>
                            <pre style="background: #f6f7f7; border: 1px solid #c3c4c7; padding: 15px; border-radius: 4px; overflow: auto; max-height: 300px;">
                                <code id="wpns-json-preview-content"></code>
                            </pre>
                        </div>
                    </div>

                    <!-- JSON Mode -->
                    <div id="wpns-json-mode" class="wpns-config-mode" style="display: none;">
                        <div class="wpns-json-toolbar">
                            <button type="button" id="wpns-load-template" class="button">
                                <?php _e('Load Template', 'wp-news-source'); ?>
                            </button>
                            <button type="button" id="wpns-validate-json" class="button">
                                <?php _e('Validate JSON', 'wp-news-source'); ?>
                            </button>
                            <button type="button" id="wpns-format-json" class="button">
                                <?php _e('Format JSON', 'wp-news-source'); ?>
                            </button>
                        </div>
                        <textarea id="source-ai-config" 
                                  name="detection_rules" 
                                  class="wpns-form-control wpns-code-editor" 
                                  rows="15"><?php echo esc_textarea($ai_config); ?></textarea>
                    </div>

                    <!-- Messages -->
                    <div id="wpns-ai-config-error" class="notice notice-error inline" style="display: none; margin-top: 10px;">
                        <p></p>
                    </div>
                    <div id="wpns-ai-config-success" class="notice notice-success inline" style="display: none; margin-top: 10px;">
                        <p></p>
                    </div>
                </div>

                <!-- Auto publicar -->
                <div class="wpns-form-group">
                    <label class="wpns-checkbox-label">
                        <input type="checkbox" 
                               id="source-auto-publish" 
                               name="auto_publish" 
                               value="1" 
                               <?php checked($auto_publish, 1); ?>>
                        <?php _e('Auto-publish', 'wp-news-source'); ?>
                    </label>
                    <p class="description">
                        <?php _e('If checked, posts will be published automatically. Otherwise, they will be saved as drafts.', 'wp-news-source'); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="wpns-form-actions">
            <button type="submit" class="button button-primary button-large">
                <?php echo $source_id ? __('Update Source', 'wp-news-source') : __('Create Source', 'wp-news-source'); ?>
            </button>
            <a href="<?php echo admin_url('admin.php?page=wp-news-source'); ?>" class="button button-secondary button-large">
                <?php _e('Cancel', 'wp-news-source'); ?>
            </a>
        </div>
    </form>
</div>

<style>
/* Estilos simples y limpios */
.wpns-form {
    max-width: 100%;
    width: 100%;
}

@media (min-width: 1200px) {
    .wpns-form {
        max-width: 1000px;
    }
}

.wpns-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.wpns-card-body {
    padding: 20px;
}

.wpns-form-group {
    margin-bottom: 20px;
}

.wpns-form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
}

.wpns-form-label .required {
    color: #dc3232;
}

.wpns-form-control {
    width: 100%;
    padding: 8px 12px;
    font-size: 14px;
    line-height: 1.5;
    border: 1px solid #7e8993;
    border-radius: 4px;
    background-color: #fff;
    box-shadow: inset 0 1px 2px rgba(0,0,0,.07);
}

.wpns-form-control:focus {
    border-color: #007cba;
    outline: 0;
    box-shadow: 0 0 0 1px #007cba;
}

.description {
    font-size: 13px;
    font-style: italic;
    color: #666;
    margin-top: 4px;
}

.wpns-checkbox-label {
    display: flex;
    align-items: center;
    font-weight: normal;
}

.wpns-checkbox-label input[type="checkbox"] {
    margin-right: 8px;
}

/* Autocomplete */
.wpns-autocomplete-container {
    position: relative;
}

.wpns-autocomplete-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-top: none;
    border-radius: 0 0 4px 4px;
    max-height: 200px;
    overflow-y: auto;
    display: none;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.wpns-autocomplete-results.show {
    display: block;
}

.wpns-category-result,
.wpns-tag-result {
    padding: 10px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}

.wpns-category-result:hover,
.wpns-tag-result:hover {
    background-color: #f3f4f5;
}

.wpns-selected-category {
    padding: 8px 12px;
    background: #f0f0f1;
    border: 1px solid #ddd;
    border-radius: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #2c3338;
    font-weight: normal;
}

.wpns-remove-category {
    cursor: pointer;
    color: #dc3232;
    font-weight: bold;
    font-size: 20px;
    line-height: 1;
    margin-left: 15px;
    padding: 0 5px;
    display: inline-block;
}

.wpns-remove-tag {
    cursor: pointer;
    color: #dc3232;
    font-weight: bold;
    font-size: 18px;
    line-height: 1;
    margin-left: 10px;
}

/* Tags */
.wpns-tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
}

.wpns-tag-item {
    display: inline-flex;
    align-items: center;
    padding: 5px 32px 5px 12px;
    background-color: #007cba;
    color: #fff;
    border-radius: 3px;
    font-size: 13px;
    position: relative;
    max-width: 100%;
    word-break: break-word;
}

.wpns-tag-item .wpns-remove-tag {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    color: #fff;
    margin: 0;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 50%;
    width: 16px;
    height: 16px;
    min-width: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    line-height: 1;
    flex-shrink: 0;
    cursor: pointer;
}

.wpns-tag-item .wpns-remove-tag:hover {
    background: rgba(0, 0, 0, 0.4);
}

/* Actions */
.wpns-form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

/* Loading state */
.wpns-loading {
    opacity: 0.6;
    pointer-events: none;
}

.button.loading:before {
    content: '';
    display: inline-block;
    width: 14px;
    height: 14px;
    margin-right: 5px;
    border: 2px solid #fff;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Code editor */
.wpns-code-editor {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', 'source-code-pro', monospace;
    font-size: 13px;
    line-height: 1.5;
    background-color: #f6f7f7;
    border-color: #8c8f94;
}

.wpns-code-editor:focus {
    background-color: #fff;
}

/* Template button */
#wpns-generate-template {
    vertical-align: middle;
}

/* JSON error message */
#wpns-ai-config-error {
    padding: 8px 12px;
}

#wpns-ai-config-error p {
    margin: 0;
}
/* AI Detection Configuration Styles */
.wpns-ai-detection-section {
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin: 30px 0;
}

.wpns-section-title {
    margin: 0 0 20px;
    padding: 0;
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.wpns-mode-toggle {
    display: flex;
    gap: 20px;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #ddd;
}

.wpns-radio-toggle {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-weight: 500;
}

.wpns-radio-toggle input[type="radio"] {
    margin-right: 6px;
}

.wpns-radio-toggle input[type="radio"]:checked + span {
    color: #007cba;
}

.wpns-config-field {
    margin-bottom: 20px;
}

.wpns-config-field .wpns-form-label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
    font-weight: 600;
}

.wpns-tags-input-container {
    position: relative;
}

.wpns-visual-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
    min-height: 32px;
}

.wpns-visual-tag {
    display: inline-flex;
    align-items: center;
    padding: 6px 32px 6px 12px;
    background: #007cba;
    color: white;
    border-radius: 3px;
    font-size: 13px;
    position: relative;
    word-break: break-word;
}

.wpns-visual-tag .remove {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    line-height: 1;
    transition: background 0.2s;
}

.wpns-visual-tag .remove:hover {
    background: rgba(255,255,255,0.4);
}

.wpns-config-actions {
    display: flex;
    gap: 10px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.wpns-config-actions .button {
    display: flex;
    align-items: center;
    gap: 5px;
}

.wpns-json-toolbar {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}

/* AI Help Modal - Removed (not implemented) */

/* Help tips */
.wpns-help-tip {
    display: inline-block;
    margin-left: 5px;
    cursor: help;
    position: relative;
}

.wpns-help-tip .dashicons {
    font-size: 16px;
    color: #666;
    vertical-align: middle;
}

.wpns-help-tip:hover .dashicons {
    color: #007cba;
}

.wpns-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    z-index: 99999;
}

</style>

<script>
jQuery(document).ready(function($) {
    // Initialize visual tags system
    const visualTags = {
        'main-identifiers': [],
        'required-context': [],
        'exclusions': []
    };
    
    // Parse existing AI config if editing
    <?php if ($source_id && $ai_config): ?>
    try {
        const existingConfig = JSON.parse(<?php echo json_encode($ai_config); ?>);
        if (existingConfig.identifiers) {
            visualTags['main-identifiers'] = existingConfig.identifiers.main || [];
            visualTags['required-context'] = existingConfig.identifiers.required_context || [];
        }
        if (existingConfig.exclusions) {
            visualTags['exclusions'] = existingConfig.exclusions;
        }
        if (existingConfig.source_type) {
            $('#source-type').val(existingConfig.source_type);
        }
        if (existingConfig.context) {
            $('#jurisdiction').val(existingConfig.context.jurisdiction || '');
            $('#level-scope').val(existingConfig.context.level || '');
        }
    } catch(e) {
        console.log('Could not parse existing config');
    }
    <?php endif; ?>
    
    // Render existing tags
    function renderTags() {
        Object.keys(visualTags).forEach(field => {
            const container = $(`#${field}-tags`);
            container.empty();
            visualTags[field].forEach(tag => {
                container.append(createTagElement(tag, field));
            });
        });
    }
    
    // Create tag element
    function createTagElement(text, field) {
        return $('<span class="wpns-visual-tag">')
            .text(text)
            .append($('<span class="remove">×</span>')
                .on('click', function() {
                    removeTag(field, text);
                }));
    }
    
    // Add tag
    function addTag(field, text) {
        text = text.trim();
        if (text && !visualTags[field].includes(text)) {
            visualTags[field].push(text);
            renderTags();
        }
    }
    
    // Remove tag
    function removeTag(field, text) {
        visualTags[field] = visualTags[field].filter(t => t !== text);
        renderTags();
    }
    
    // Setup tag inputs
    ['main-identifiers', 'required-context', 'exclusions'].forEach(field => {
        $(`#${field}-input`).on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                addTag(field, $(this).val());
                $(this).val('');
            }
        });
    });
    
    // Mode toggle
    $('input[name="ai-config-mode"]').on('change', function() {
        if ($(this).val() === 'visual') {
            $('#wpns-visual-mode').show();
            $('#wpns-json-mode').hide();
        } else {
            $('#wpns-visual-mode').hide();
            $('#wpns-json-mode').show();
            // Update JSON from visual if needed
            if (visualTags['main-identifiers'].length > 0) {
                generateJSONFromVisual();
            }
        }
    });
    
    // Generate JSON from visual fields
    function generateJSONFromVisual(showMessage = true) {
        const sourceType = $('#source-type').val();
        const jurisdiction = $('#jurisdiction').val();
        const levelScope = $('#level-scope').val();
        
        if (!sourceType && visualTags['main-identifiers'].length === 0) {
            if (showMessage) {
                showError('<?php _e('Please select a source type or add identifiers', 'wp-news-source'); ?>');
            }
            return false;
        }
        
        const config = {
            source_type: sourceType || 'general',
            identifiers: {
                main: visualTags['main-identifiers'],
                required_context: visualTags['required-context'],
                combinations: []
            },
            context: {},
            validation_rules: [],
            exclusions: visualTags['exclusions']
        };
        
        // Add context based on source type
        if (sourceType === 'government') {
            config.context.level = levelScope || 'municipal';
            config.context.jurisdiction = jurisdiction;
        } else if (sourceType === 'company') {
            config.context.sector = 'service';
            config.context.coverage = levelScope || 'local';
        } else if (sourceType === 'association') {
            config.context.association_type = 'chamber';
            config.context.scope = levelScope || 'local';
        } else if (sourceType === 'person') {
            config.context.current_position = '';
            config.context.scope = levelScope || 'local';
        } else if (sourceType === 'institution') {
            config.context.institution_type = '';
            config.context.location = jurisdiction;
        }
        
        // Add validation rules
        if (visualTags['main-identifiers'].length > 0) {
            config.validation_rules.push(`MUST mention ${visualTags['main-identifiers'][0]}`);
        }
        if (jurisdiction) {
            config.validation_rules.push(`MUST be in context of ${jurisdiction}`);
        }
        
        $('#source-ai-config').val(JSON.stringify(config, null, 2));
        if (showMessage) {
            showSuccess('<?php _e('Configuration generated successfully', 'wp-news-source'); ?>');
        }
        return true;
    }
    
    // Generate configuration button
    $('#wpns-generate-config').on('click', function() {
        if (generateJSONFromVisual()) {
            // Show success and update preview if visible
            updateJSONPreview();
        }
    });
    
    // View JSON button
    $('#wpns-view-json').on('click', function() {
        const $preview = $('#wpns-json-preview');
        if ($preview.is(':visible')) {
            $preview.slideUp();
            $(this).find('.dashicons').removeClass('dashicons-minus').addClass('dashicons-code-standards');
        } else {
            // Generate JSON first if needed
            generateJSONFromVisual(false);
            updateJSONPreview();
            $preview.slideDown();
            $(this).find('.dashicons').removeClass('dashicons-code-standards').addClass('dashicons-minus');
        }
    });
    
    // Copy JSON button
    $('#wpns-copy-json').on('click', function() {
        const jsonContent = $('#wpns-json-preview-content').text();
        
        if (navigator.clipboard && navigator.clipboard.writeText) {
            // Modern way using Clipboard API
            navigator.clipboard.writeText(jsonContent).then(() => {
                showSuccess('<?php _e('JSON copied to clipboard!', 'wp-news-source'); ?>');
                
                // Change button text temporarily
                const $btn = $(this);
                const originalHtml = $btn.html();
                $btn.html('<span class="dashicons dashicons-yes"></span> <?php _e('Copied!', 'wp-news-source'); ?>');
                setTimeout(() => {
                    $btn.html(originalHtml);
                }, 2000);
            }).catch(() => {
                // Fallback to old method
                copyWithFallback(jsonContent);
            });
        } else {
            // Fallback for older browsers
            copyWithFallback(jsonContent);
        }
    });
    
    // Fallback copy method for older browsers
    function copyWithFallback(text) {
        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        
        try {
            document.execCommand('copy');
            showSuccess('<?php _e('JSON copied to clipboard!', 'wp-news-source'); ?>');
            
            // Change button text temporarily
            const $btn = $('#wpns-copy-json');
            const originalHtml = $btn.html();
            $btn.html('<span class="dashicons dashicons-yes"></span> <?php _e('Copied!', 'wp-news-source'); ?>');
            setTimeout(() => {
                $btn.html(originalHtml);
            }, 2000);
        } catch (err) {
            showError('<?php _e('Failed to copy to clipboard', 'wp-news-source'); ?>');
        }
        
        $temp.remove();
    }
    
    // Update JSON preview
    function updateJSONPreview() {
        const jsonContent = $('#source-ai-config').val();
        if (jsonContent) {
            try {
                const parsed = JSON.parse(jsonContent);
                $('#wpns-json-preview-content').text(JSON.stringify(parsed, null, 2));
            } catch (e) {
                $('#wpns-json-preview-content').text(jsonContent);
            }
        } else {
            $('#wpns-json-preview-content').text('<?php _e('No configuration generated yet', 'wp-news-source'); ?>');
        }
    }
    
    // Show messages
    function showError(message) {
        $('#wpns-ai-config-error p').text(message);
        $('#wpns-ai-config-error').show();
        $('#wpns-ai-config-success').hide();
    }
    
    function showSuccess(message) {
        $('#wpns-ai-config-success p').text(message);
        $('#wpns-ai-config-success').show();
        $('#wpns-ai-config-error').hide();
        setTimeout(() => $('#wpns-ai-config-success').fadeOut(), 3000);
    }
    
    // AI Config Templates
    const aiTemplates = {
        government: {
            source_type: "government",
            identifiers: {
                main: ["[Official Name]", "[Specific Title]"],
                required_context: ["[title] + [jurisdiction]", "[department] + [location]"],
                combinations: [
                    ["mayor", "[city]", "[name]"],
                    ["municipal government", "[city]"],
                    ["city hall", "[city]"]
                ]
            },
            context: {
                level: "municipal|state|federal",
                jurisdiction: "[specific city/state]",
                officials: ["[key officials names]"],
                departments: ["[departments or offices]"]
            },
            validation_rules: [
                "MUST mention [title] AND [jurisdiction]",
                "Must NOT refer to another city/state"
            ],
            exclusions: [
                "If mentions another city, discard",
                "If from different government level, ignore"
            ]
        },
        company: {
            source_type: "company",
            identifiers: {
                main: ["[Company Name]", "[Brand Name]"],
                required_context: ["[main service] + [location]", "[industry] + [company]"],
                combinations: [
                    ["[brand]", "[product/service]"],
                    ["[trade name]", "[sector]"]
                ]
            },
            context: {
                sector: "[water|energy|transport|telecommunications|retail]",
                coverage: "[local|regional|national]",
                services: ["[list of main services]"],
                locations: ["[cities of operation]"]
            },
            validation_rules: [
                "MUST mention [company] OR [brand]",
                "MUST be in context of [sector]"
            ]
        },
        association: {
            source_type: "association",
            identifiers: {
                main: ["[Association Name]", "[Acronym]"],
                required_context: ["[association type] + [sector]", "[chamber] + [location]"],
                combinations: [
                    ["association", "[sector]", "[location]"],
                    ["chamber", "[industry]", "[city]"]
                ]
            },
            context: {
                association_type: "chamber|union|guild|civil association",
                sector: "[hospitality|restaurant|commerce|industrial]",
                scope: "[municipal|state|national]",
                members: "[type of members represented]"
            },
            validation_rules: [
                "MUST mention [name] OR [acronym]",
                "MUST be in context of [sector]"
            ]
        },
        person: {
            source_type: "person",
            identifiers: {
                main: ["[Full Name]", "[Known Nickname]"],
                required_context: ["[name] + [current position]", "[name] + [profession]"],
                combinations: [
                    ["[name]", "[position]", "[party/institution]"],
                    ["[nickname]", "[activity]"]
                ]
            },
            context: {
                current_position: "[deputy|senator|director|etc]",
                party_affiliation: "[if applicable]",
                scope: "local|state|national",
                main_activity: "[political|business|artistic|sports]"
            },
            validation_rules: [
                "MUST mention full name OR known nickname",
                "MUST include activity context"
            ]
        },
        institution: {
            source_type: "institution",
            identifiers: {
                main: ["[Institution Name]", "[Acronym]"],
                required_context: ["[institution type] + [location]", "[name] + [activity]"],
                combinations: [
                    ["university", "[name]"],
                    ["institute", "[specialty]"],
                    ["center", "[type]", "[location]"]
                ]
            },
            context: {
                institution_type: "university|hospital|museum|cultural center",
                dependency: "public|private|autonomous",
                location: "[address or city]",
                specialty: "[focus area]"
            },
            validation_rules: [
                "MUST mention full name OR official acronym",
                "MUST be in institutional context"
            ]
        }
    };

    // Load template button (JSON mode)
    $('#wpns-load-template').on('click', function() {
        const sourceType = $('#source-type').val();
        const sourceName = $('#source-name').val();
        const keywords = $('#source-keywords').val();
        
        if (!sourceType) {
            alert('<?php _e('Please select a source type first', 'wp-news-source'); ?>');
            return;
        }
        
        let template = aiTemplates[sourceType] || aiTemplates.government;
        
        // Customize template with actual data
        if (sourceName) {
            template.identifiers.main[0] = sourceName;
        }
        
        if (keywords) {
            const keywordArray = keywords.split(',').map(k => k.trim()).filter(k => k);
            if (keywordArray.length > 0) {
                template.identifiers.required_context = keywordArray.slice(0, 3);
            }
        }
        
        // Set formatted JSON
        $('#source-ai-config').val(JSON.stringify(template, null, 2));
        validateAIConfig();
    });
    
    // Validate JSON button
    $('#wpns-validate-json').on('click', function() {
        validateAIConfig();
    });
    
    // Format JSON button
    $('#wpns-format-json').on('click', function() {
        const $textarea = $('#source-ai-config');
        const value = $textarea.val().trim();
        
        if (!value) return;
        
        try {
            const parsed = JSON.parse(value);
            $textarea.val(JSON.stringify(parsed, null, 2));
            showSuccess('<?php _e('JSON formatted successfully', 'wp-news-source'); ?>');
        } catch (e) {
            showError('<?php _e('Cannot format invalid JSON', 'wp-news-source'); ?>');
        }
    });
    
    // Validate JSON on change
    $('#source-ai-config').on('blur', validateAIConfig);
    
    function validateAIConfig() {
        const $textarea = $('#source-ai-config');
        const $error = $('#wpns-ai-config-error');
        const value = $textarea.val().trim();
        
        if (!value) {
            $error.hide();
            return true;
        }
        
        try {
            JSON.parse(value);
            $error.hide();
            $textarea.css('border-color', '');
            return true;
        } catch (e) {
            $error.find('p').text('<?php _e('Invalid JSON:', 'wp-news-source'); ?> ' + e.message);
            $error.show();
            $textarea.css('border-color', '#dc3232');
            return false;
        }
    }
    
    // AI Help functionality removed - not implemented with real AI
    
    // Initialize tags on load
    renderTags();
    
    // Auto-detect source type if editing
    <?php if ($source_id && $ai_config): ?>
    try {
        const config = JSON.parse(<?php echo json_encode($ai_config); ?>);
        if (config.source_type) {
            $('#source-type').val(config.source_type);
        }
    } catch(e) {
        console.log('Could not parse existing AI config');
    }
    <?php endif; ?>
    
    // Hook into form submission to ensure JSON is generated from visual mode
    $(document).on('submit', '#wpns-add-source-form', function(e) {
        // Check if we're in visual mode
        if ($('input[name="ai-config-mode"]:checked').val() === 'visual') {
            // Generate JSON from visual fields before submission
            const sourceType = $('#source-type').val();
            if (sourceType || visualTags['main-identifiers'].length > 0) {
                generateJSONFromVisual(false); // Don't show success message during form submission
            }
        }
        // Let the form continue with submission
    });
});
</script>