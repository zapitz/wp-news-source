<?php
/**
 * Class to handle database operations
 */
class WP_News_Source_DB {
    
    private $table_name;
    private $history_table;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'news_sources';
        $this->history_table = $wpdb->prefix . 'news_source_detections';
    }
    
    /**
     * Get all sources
     */
    public function get_all_sources() {
        global $wpdb;
        
        $query = "SELECT * FROM {$this->table_name} ORDER BY name ASC";
        return $wpdb->get_results($query);
    }
    
    /**
     * Get sources with pagination and optional type filter
     */
    public function get_sources_paginated($page = 1, $per_page = 20, $source_type = null) {
        global $wpdb;

        $offset = ($page - 1) * $per_page;

        if ($source_type) {
            $query = $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE source_type = %s ORDER BY name ASC LIMIT %d OFFSET %d",
                $source_type,
                $per_page,
                $offset
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT * FROM {$this->table_name} ORDER BY name ASC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            );
        }

        return $wpdb->get_results($query);
    }

    /**
     * Get total source count with optional type filter
     */
    public function get_sources_count($source_type = null) {
        global $wpdb;

        if ($source_type) {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE source_type = %s",
                $source_type
            ));
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
    }

    /**
     * Get a source by ID
     */
    public function get_source($id) {
        global $wpdb;
        
        $query = $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id);
        return $wpdb->get_row($query);
    }
    
    /**
     * Get a source by slug
     */
    public function get_source_by_slug($slug) {
        global $wpdb;
        
        $query = $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE slug = %s", $slug);
        return $wpdb->get_row($query);
    }
    
    /**
     * Search sources by name
     */
    public function search_sources_by_name($name) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE name LIKE %s ORDER BY name ASC",
            '%' . $wpdb->esc_like($name) . '%'
        );
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Insert a new source
     */
    public function insert_source($data) {
        global $wpdb;
        
        // Generate API key if requested
        if (isset($data['generate_api_key']) && $data['generate_api_key']) {
            $data['api_key'] = wp_generate_password(WP_News_Source_Config::get('api_key_length'), false);
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'name' => $data['name'],
                'slug' => $data['slug'],
                'source_type' => isset($data['source_type']) ? $data['source_type'] : 'general',
                'description' => isset($data['description']) ? $data['description'] : '',
                'keywords' => isset($data['keywords']) ? $data['keywords'] : '',
                'detection_rules' => isset($data['detection_rules']) ? $data['detection_rules'] : '',
                'category_id' => $data['category_id'],
                'category_name' => $data['category_name'],
                'tag_ids' => isset($data['tag_ids']) ? $data['tag_ids'] : '',
                'tag_names' => isset($data['tag_names']) ? $data['tag_names'] : '',
                'auto_publish' => $data['auto_publish'],
                'requires_review' => $data['requires_review'],
                'webhook_url' => isset($data['webhook_url']) ? $data['webhook_url'] : '',
                'api_key' => isset($data['api_key']) ? $data['api_key'] : ''
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update an existing source
     */
    public function update_source($id, $data) {
        global $wpdb;
        
        $update_data = array(
            'name' => $data['name'],
            'slug' => $data['slug'],
            'source_type' => isset($data['source_type']) ? $data['source_type'] : 'general',
            'description' => isset($data['description']) ? $data['description'] : '',
            'keywords' => isset($data['keywords']) ? $data['keywords'] : '',
            'detection_rules' => isset($data['detection_rules']) ? $data['detection_rules'] : '',
            'category_id' => $data['category_id'],
            'category_name' => $data['category_name'],
            'tag_ids' => isset($data['tag_ids']) ? $data['tag_ids'] : '',
            'tag_names' => isset($data['tag_names']) ? $data['tag_names'] : '',
            'auto_publish' => $data['auto_publish'],
            'requires_review' => $data['requires_review'],
            'webhook_url' => isset($data['webhook_url']) ? $data['webhook_url'] : ''
        );

        // Only update API key if new one provided
        if (isset($data['api_key'])) {
            $update_data['api_key'] = $data['api_key'];
        }

        $format = array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%s');
        if (isset($data['api_key'])) {
            $format[] = '%s';
        }

        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $id),
            $format,
            array('%d')
        );
        
        return $result !== false ? $id : false;
    }
    
    /**
     * Delete a source
     */
    public function delete_source($id) {
        global $wpdb;
        
        // Delete associated history
        $wpdb->delete(
            $this->history_table,
            array('source_id' => $id),
            array('%d')
        );
        
        return $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }
    
    /**
     * Log a detection
     */
    public function log_detection($source_id, $post_id = null, $confidence = 0, $method = 'keyword', $content = '') {
        global $wpdb;
        
        // Log to history
        $wpdb->insert(
            $this->history_table,
            array(
                'source_id' => $source_id,
                'post_id' => $post_id,
                'detection_confidence' => $confidence,
                'detection_method' => $method,
                'detected_content' => substr($content, 0, WP_News_Source_Config::get('detected_content_max_length')) // Limit content length
            ),
            array('%d', '%d', '%f', '%s', '%s')
        );
        
        // Update source counters
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_name} 
             SET detection_count = detection_count + 1, 
                 last_detected = NOW() 
             WHERE id = %d",
            $source_id
        ));
    }
    
    /**
     * Get detection history
     */
    public function get_detection_history($source_id = null, $limit = null) {
        global $wpdb;
        
        $query = "SELECT d.*, s.name as source_name 
                  FROM {$this->history_table} d
                  LEFT JOIN {$this->table_name} s ON d.source_id = s.id";
        
        if ($source_id) {
            $query .= $wpdb->prepare(" WHERE d.source_id = %d", $source_id);
        }
        
        $query .= " ORDER BY d.created_at DESC";
        
        if ($limit === null) {
            $limit = WP_News_Source_Config::get('search_limit_max');
        }
        
        $query .= " LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($query, $limit));
    }
    
    /**
     * Obtiene estadísticas de fuentes
     */
    public function get_source_stats() {
        global $wpdb;
        
        $query = "SELECT 
                    COUNT(*) as total_sources,
                    SUM(detection_count) as total_detections,
                    AVG(detection_count) as avg_detections
                  FROM {$this->table_name}";
        
        return $wpdb->get_row($query);
    }
    
    /**
     * Exporta fuentes a JSON
     */
    public function export_sources() {
        $sources = $this->get_all_sources();
        
        // Limpiar datos sensibles
        foreach ($sources as &$source) {
            unset($source->api_key);
            unset($source->webhook_url);
        }
        
        // Return array, not JSON string - will be encoded by wp_send_json_success
        return $sources;
    }
    
    /**
     * Importa fuentes desde JSON
     */
    public function import_sources($json_data, $options = array()) {
        // Default options
        $defaults = array(
            'match_categories_by_name' => true,  // Try to match categories by name instead of ID
            'skip_missing_categories' => false,   // Skip sources if category not found
            'use_default_category' => false,      // Use default category if not found
            'default_category_id' => 1            // Default category to use
        );
        $options = wp_parse_args($options, $defaults);
        
        // Handle double-encoded JSON (common issue with exports)
        $sources = json_decode($json_data, true);
        
        // Check for JSON decode errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('WP News Source Import: First decode error - ' . json_last_error_msg());
            return false;
        }
        
        // If first decode resulted in a string, it was double-encoded
        if (is_string($sources)) {
            $sources = json_decode($sources, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('WP News Source Import: Second decode error - ' . json_last_error_msg());
                return false;
            }
        }
        
        $imported = 0;
        $skipped = 0;
        
        if (!is_array($sources)) {
            error_log('WP News Source Import: Invalid data type - expected array, got ' . gettype($sources));
            return false;
        }
        
        foreach ($sources as $source) {
            // Skip if critical fields are missing
            if (!isset($source['name']) || !isset($source['slug'])) {
                error_log('WP News Source Import: Skipping source - missing required fields');
                continue;
            }
            
            // Check if already exists
            $existing = $this->get_source_by_slug($source['slug']);
            
            if (!$existing) {
                // Handle category mapping
                $category_id = null;
                $category_name = '';
                
                if ($options['match_categories_by_name'] && !empty($source['category_name'])) {
                    // Try to find category by name
                    $category = get_term_by('name', $source['category_name'], 'category');
                    if ($category && !is_wp_error($category)) {
                        $category_id = $category->term_id;
                        $category_name = $category->name;
                    }
                } elseif (!empty($source['category_id'])) {
                    // Use the provided category ID and verify it exists
                    $category = get_category($source['category_id']);
                    if ($category && !is_wp_error($category)) {
                        $category_id = $source['category_id'];
                        $category_name = $category->name;
                    }
                }
                
                // Handle missing category
                if (!$category_id) {
                    if ($options['skip_missing_categories']) {
                        error_log('WP News Source Import: Skipping source "' . $source['name'] . '" - category not found');
                        $skipped++;
                        continue;
                    } elseif ($options['use_default_category']) {
                        $category_id = $options['default_category_id'];
                        $default_cat = get_category($category_id);
                        $category_name = $default_cat ? $default_cat->name : '';
                    }
                }
                
                // Clean up data for insert
                $insert_data = array(
                    'name' => $source['name'],
                    'slug' => $source['slug'],
                    'description' => isset($source['description']) ? $source['description'] : '',
                    'keywords' => isset($source['keywords']) ? $source['keywords'] : '',
                    'detection_rules' => isset($source['detection_rules']) ? $source['detection_rules'] : '',
                    'category_id' => $category_id,
                    'category_name' => $category_name,
                    'tag_ids' => isset($source['tag_ids']) ? $source['tag_ids'] : '',
                    'tag_names' => isset($source['tag_names']) ? $source['tag_names'] : '',
                    'auto_publish' => isset($source['auto_publish']) ? $source['auto_publish'] : 0,
                    'requires_review' => isset($source['requires_review']) ? $source['requires_review'] : 1,
                    'webhook_url' => isset($source['webhook_url']) ? $source['webhook_url'] : '',
                    'api_key' => '' // Don't import API keys for security
                );
                
                $result = $this->insert_source($insert_data);
                if ($result) {
                    $imported++;
                } else {
                    error_log('WP News Source Import: Failed to insert source: ' . $source['name']);
                }
            }
        }
        
        return array(
            'imported' => $imported,
            'skipped' => $skipped,
            'total' => count($sources)
        );
    }
    
    
    /**
     * Obtiene el mapeo completo para n8n con mejoras
     */
    public function get_mapping_for_n8n() {
        $sources = $this->get_all_sources();
        $mapping = array();
        
        foreach ($sources as $source) {
            $mapping[$source->name] = array(
                'id' => $source->id,
                'slug' => $source->slug,
                'type' => $source->source_type,
                'description' => $source->description,
                'keywords' => !empty($source->keywords) ? explode(',', $source->keywords) : array(),
                'detection_rules' => !empty($source->detection_rules) ? $source->detection_rules : '',
                'category' => array(
                    'id' => $source->category_id,
                    'name' => $source->category_name
                ),
                'tags' => array(),
                'auto_publish' => (bool) $source->auto_publish,
                'requires_review' => (bool) $source->requires_review,
                'has_webhook' => !empty($source->webhook_url),
                'stats' => array(
                    'detection_count' => intval($source->detection_count),
                    'last_detected' => $source->last_detected
                )
            );
            
            if (!empty($source->tag_ids)) {
                $tag_ids = explode(',', $source->tag_ids);
                $tag_names = explode(',', $source->tag_names);
                
                for ($i = 0; $i < count($tag_ids); $i++) {
                    $mapping[$source->name]['tags'][] = array(
                        'id' => intval($tag_ids[$i]),
                        'name' => trim($tag_names[$i])
                    );
                }
            }
        }
        
        return $mapping;
    }
}