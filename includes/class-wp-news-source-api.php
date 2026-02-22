<?php
/**
 * REST API for n8n integration
 */
class WP_News_Source_API {
    
    private $plugin_name;
    private $version;
    private $namespace;
    private $db;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->namespace = 'wp-news-source/v1';
        $this->db = new WP_News_Source_DB();
    }
    
    /**
     * Register API routes
     */
    public function register_routes() {
        // Sources collection: GET (list with pagination) and POST (create)
        register_rest_route($this->namespace, '/sources', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_sources'),
                'permission_callback' => array($this, 'check_api_permission'),
                'args' => array(
                    'page' => array(
                        'required' => false,
                        'type' => 'integer',
                        'default' => 1,
                        'minimum' => 1
                    ),
                    'per_page' => array(
                        'required' => false,
                        'type' => 'integer',
                        'default' => 20,
                        'minimum' => 1,
                        'maximum' => 100
                    ),
                    'source_type' => array(
                        'required' => false,
                        'type' => 'string',
                        'description' => 'Filter by source type'
                    )
                )
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_source'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => array(
                    'name' => array(
                        'required' => true,
                        'type' => 'string',
                        'description' => 'Source name'
                    ),
                    'source_type' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => 'general',
                        'description' => 'Source type'
                    ),
                    'description' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => ''
                    ),
                    'keywords' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => ''
                    ),
                    'category_id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'description' => 'WordPress category ID'
                    ),
                    'tag_ids' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => '',
                        'description' => 'Comma-separated tag IDs'
                    ),
                    'auto_publish' => array(
                        'required' => false,
                        'type' => 'boolean',
                        'default' => false
                    ),
                    'requires_review' => array(
                        'required' => false,
                        'type' => 'boolean',
                        'default' => true
                    ),
                    'detection_rules' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => '',
                        'description' => 'JSON detection rules'
                    )
                )
            )
        ));

        // Single source: PUT (update) and DELETE
        register_rest_route($this->namespace, '/sources/(?P<id>\d+)', array(
            array(
                'methods' => 'PUT,PATCH',
                'callback' => array($this, 'update_source'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'type' => 'integer'
                    ),
                    'name' => array(
                        'required' => false,
                        'type' => 'string'
                    ),
                    'source_type' => array(
                        'required' => false,
                        'type' => 'string'
                    ),
                    'description' => array(
                        'required' => false,
                        'type' => 'string'
                    ),
                    'keywords' => array(
                        'required' => false,
                        'type' => 'string'
                    ),
                    'category_id' => array(
                        'required' => false,
                        'type' => 'integer'
                    ),
                    'tag_ids' => array(
                        'required' => false,
                        'type' => 'string'
                    ),
                    'auto_publish' => array(
                        'required' => false,
                        'type' => 'boolean'
                    ),
                    'requires_review' => array(
                        'required' => false,
                        'type' => 'boolean'
                    ),
                    'detection_rules' => array(
                        'required' => false,
                        'type' => 'string'
                    )
                )
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_source'),
                'permission_callback' => array($this, 'check_admin_permission'),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'type' => 'integer'
                    )
                )
            )
        ));
        
        // Get complete mapping
        register_rest_route($this->namespace, '/mapping', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_mapping'),
            'permission_callback' => array($this, 'check_api_permission')
        ));
        
        // Detect source by content
        register_rest_route($this->namespace, '/detect', array(
            'methods' => 'POST',
            'callback' => array($this, 'detect_source'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'content' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Content to analyze'
                ),
                'title' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Content title (improves detection)'
                )
            )
        ));
        
        // Generate AI detection prompt
        register_rest_route($this->namespace, '/generate-detection-prompt', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_detection_prompt'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'source_id' => array(
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'Source ID to generate prompt for specific source'
                ),
                'content' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Content to analyze'
                ),
                'title' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Content title'
                ),
                'all_sources' => array(
                    'required' => false,
                    'type' => 'boolean',
                    'default' => true,
                    'description' => 'Include all sources in prompt generation'
                )
            )
        ));
        
        // Validate content before publishing
        register_rest_route($this->namespace, '/validate', array(
            'methods' => 'POST',
            'callback' => array($this, 'validate_content'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'source_id' => array(
                    'required' => true,
                    'type' => 'integer'
                ),
                'content' => array(
                    'required' => true,
                    'type' => 'string'
                )
            )
        ));
        
        // Webhook for notifications
        register_rest_route($this->namespace, '/webhook/(?P<source_id>\d+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true',
            'args' => array(
                'source_id' => array(
                    'required' => true,
                    'type' => 'integer'
                )
            )
        ));
        
        // Get detection history
        register_rest_route($this->namespace, '/history', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_history'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'source_id' => array(
                    'required' => false,
                    'type' => 'integer'
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 50
                )
            )
        ));
        
        // Estadísticas
        register_rest_route($this->namespace, '/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_stats'),
            'permission_callback' => array($this, 'check_api_permission')
        ));
        
        // Exportar/Importar configuración
        register_rest_route($this->namespace, '/export', array(
            'methods' => 'GET',
            'callback' => array($this, 'export_config'),
            'permission_callback' => array($this, 'check_api_permission')
        ));
        
        register_rest_route($this->namespace, '/import', array(
            'methods' => 'POST',
            'callback' => array($this, 'import_config'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'data' => array(
                    'required' => true,
                    'type' => 'string'
                )
            )
        ));
        
        // Search categories with AJAX autocomplete
        register_rest_route($this->namespace, '/categories/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'search_categories'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'search' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => '',
                    'description' => 'Search term'
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 20,
                    'description' => 'Number of results to return'
                )
            )
        ));
        
        // Obtener categorías de WordPress
        register_rest_route($this->namespace, '/categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_categories'),
            'permission_callback' => array($this, 'check_api_permission')
        ));
        
        // Search tags with AJAX autocomplete
        register_rest_route($this->namespace, '/tags/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'search_tags'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'search' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => '',
                    'description' => 'Search term'
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 20,
                    'description' => 'Number of results to return'
                )
            )
        ));
        
        // Obtener etiquetas de WordPress
        register_rest_route($this->namespace, '/tags', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_tags'),
            'permission_callback' => array($this, 'check_api_permission')
        ));
        
        // Search posts
        register_rest_route($this->namespace, '/search-posts', array(
            'methods' => 'POST',
            'callback' => array($this, 'search_posts'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'title' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Search in post title'
                ),
                'content' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Search in post content'
                ),
                'date_from' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Start date (YYYY-MM-DD)'
                ),
                'date_to' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'End date (YYYY-MM-DD)'
                ),
                'category_id' => array(
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'Filter by category ID'
                ),
                'tag_ids' => array(
                    'required' => false,
                    'type' => 'array',
                    'description' => 'Filter by tag IDs'
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => WP_News_Source_Config::get('search_limit_default'),
                    'description' => 'Maximum results (max 50)'
                ),
                'status' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'any',
                    'description' => 'Post status (publish, draft, any)'
                )
            )
        ));
        
        // Create post directly with full features
        register_rest_route($this->namespace, '/create-post', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_post'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'title' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Post title'
                ),
                'content' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Post content (HTML allowed)'
                ),
                'source_id' => array(
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'Source ID to auto-assign categories and tags'
                ),
                'source_name' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Source name to detect and auto-assign'
                ),
                'category_ids' => array(
                    'required' => false,
                    'type' => 'array',
                    'description' => 'Array of category IDs (overrides source)'
                ),
                'tag_ids' => array(
                    'required' => false,
                    'type' => 'array',
                    'description' => 'Array of tag IDs (overrides source)'
                ),
                'tag_names' => array(
                    'required' => false,
                    'type' => 'array',
                    'description' => 'Array of tag names (creates if not exist)'
                ),
                'author_id' => array(
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'WordPress user ID for post author (defaults to current user)'
                ),
                'featured_image_url' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'URL of image to download and set as featured'
                ),
                'featured_image_base64' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Base64 encoded image data'
                ),
                'attachment_id' => array(
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'ID of previously uploaded image to use as featured'
                ),
                'telegram_file_id' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Telegram file ID to download and set as featured'
                ),
                'telegram_bot_token' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Telegram bot token (optional, uses settings if not provided)'
                ),
                'status' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'draft',
                    'enum' => array('publish', 'draft', 'pending', 'private'),
                    'description' => 'Post status'
                ),
                'post_date' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Post date (YYYY-MM-DD HH:MM:SS)'
                ),
                'meta_fields' => array(
                    'required' => false,
                    'type' => 'object',
                    'description' => 'Custom meta fields as key-value pairs'
                )
            )
        ));
        
        // Manage prompts for AI agents
        register_rest_route($this->namespace, '/prompts', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_prompts'),
                'permission_callback' => array($this, 'check_api_permission')
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'save_prompt'),
                'permission_callback' => array($this, 'check_api_permission'),
                'args' => array(
                    'key' => array(
                        'required' => true,
                        'type' => 'string',
                        'description' => 'Unique prompt key'
                    ),
                    'prompt' => array(
                        'required' => true,
                        'type' => 'string',
                        'description' => 'The prompt content'
                    ),
                    'description' => array(
                        'required' => false,
                        'type' => 'string',
                        'description' => 'Prompt description'
                    ),
                    'variables' => array(
                        'required' => false,
                        'type' => 'array',
                        'description' => 'Variables used in the prompt'
                    )
                )
            )
        ));
        
        // Get specific prompt
        register_rest_route($this->namespace, '/prompts/(?P<key>[a-zA-Z0-9_-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_prompt'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'key' => array(
                    'required' => true,
                    'type' => 'string'
                )
            )
        ));
        
        // Upload image endpoint
        register_rest_route($this->namespace, '/upload-image', array(
            'methods' => 'POST',
            'callback' => array($this, 'upload_image'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'image_url' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'URL of image to download'
                ),
                'image_base64' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Base64 encoded image data'
                ),
                'telegram_file_id' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Telegram file ID to download'
                ),
                'telegram_bot_token' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Telegram bot token (optional, uses settings if not provided)'
                ),
                'title' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'Uploaded Image',
                    'description' => 'Title for the image'
                )
            )
        ));
    }
    
    /**
     * Verifica permisos de API
     */
    public function check_api_permission($request) {
        // Si está habilitado el API key
        $require_api_key = get_option('wp_news_source_require_api_key', false);
        
        if ($require_api_key) {
            $provided_key = $request->get_header('X-API-Key');
            $stored_key = get_option('wp_news_source_api_key');
            
            if ($provided_key !== $stored_key) {
                return new WP_Error('invalid_api_key', 'Invalid API key', array('status' => 401));
            }
        }
        
        return true;
    }
    
    /**
     * Check admin permissions
     */
    public function check_admin_permission($request) {
        return current_user_can('manage_news_sources');
    }
    
    /**
     * Get all sources with pagination
     */
    public function get_sources($request) {
        $page = absint($request->get_param('page')) ?: 1;
        $per_page = absint($request->get_param('per_page')) ?: 20;
        $source_type = $request->get_param('source_type') ? sanitize_text_field($request->get_param('source_type')) : null;

        $per_page = min($per_page, 100);

        $sources = $this->db->get_sources_paginated($page, $per_page, $source_type);
        $total = $this->db->get_sources_count($source_type);

        // Clean sensitive data
        foreach ($sources as &$source) {
            unset($source->api_key);
            unset($source->webhook_url);
            $source->keywords = isset($source->keywords) ? $source->keywords : '';
            $source->description = isset($source->description) ? $source->description : '';
            $source->detection_rules = isset($source->detection_rules) ? $source->detection_rules : '';
            $source->source_type = isset($source->source_type) ? $source->source_type : 'general';
        }

        return rest_ensure_response(array(
            'success' => true,
            'sources' => $sources,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ));
    }

    /**
     * Create a new source via REST API
     */
    public function create_source($request) {
        $name = sanitize_text_field($request->get_param('name'));
        $slug = sanitize_title($name);

        // Check for duplicate slug
        $existing = $this->db->get_source_by_slug($slug);
        if ($existing) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => __('A source with this name already exists', 'wp-news-source')
            ), 409);
        }

        // Validate category exists
        $category_id = absint($request->get_param('category_id'));
        $category = get_category($category_id);
        if (!$category || is_wp_error($category)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => __('Invalid category ID', 'wp-news-source')
            ), 400);
        }

        // Validate tag count
        $tag_ids_str = sanitize_text_field($request->get_param('tag_ids'));
        $tag_ids = array_filter(array_map('absint', explode(',', $tag_ids_str)));
        $max_tags = WP_News_Source_Config::get_max_tags();
        if (count($tag_ids) > $max_tags) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => sprintf(__('Maximum %d tags allowed', 'wp-news-source'), $max_tags)
            ), 400);
        }

        // Resolve tag names from IDs
        $tag_names = array();
        foreach ($tag_ids as $tag_id) {
            $tag = get_term($tag_id, 'post_tag');
            if ($tag && !is_wp_error($tag)) {
                $tag_names[] = $tag->name;
            }
        }

        // Validate detection_rules JSON if provided
        $detection_rules = $request->get_param('detection_rules');
        if (!empty($detection_rules)) {
            $json_test = json_decode($detection_rules);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error' => __('Invalid JSON in detection_rules', 'wp-news-source')
                ), 400);
            }
        }

        $source_data = array(
            'name' => $name,
            'slug' => $slug,
            'source_type' => sanitize_text_field($request->get_param('source_type')) ?: 'general',
            'description' => sanitize_textarea_field($request->get_param('description')),
            'keywords' => sanitize_textarea_field($request->get_param('keywords')),
            'detection_rules' => $detection_rules ?: '',
            'category_id' => $category_id,
            'category_name' => $category->name,
            'tag_ids' => implode(',', $tag_ids),
            'tag_names' => implode(',', $tag_names),
            'auto_publish' => $request->get_param('auto_publish') ? 1 : 0,
            'requires_review' => $request->get_param('requires_review') !== false ? 1 : 0,
        );

        $result = $this->db->insert_source($source_data);

        if (!$result) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => __('Failed to create source', 'wp-news-source')
            ), 500);
        }

        $source = $this->db->get_source($result);
        unset($source->api_key);
        unset($source->webhook_url);

        return new WP_REST_Response(array(
            'success' => true,
            'source' => $source,
            'message' => __('Source created successfully', 'wp-news-source')
        ), 201);
    }

    /**
     * Update a source via REST API
     */
    public function update_source($request) {
        $id = absint($request->get_param('id'));
        $existing = $this->db->get_source($id);

        if (!$existing) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => __('Source not found', 'wp-news-source')
            ), 404);
        }

        // Merge provided fields with existing values (partial update)
        $name = $request->get_param('name') !== null
            ? sanitize_text_field($request->get_param('name'))
            : $existing->name;

        $slug = sanitize_title($name);

        // Check category if provided
        $category_id = $request->get_param('category_id') !== null
            ? absint($request->get_param('category_id'))
            : $existing->category_id;
        $category_name = $existing->category_name;

        if ($request->get_param('category_id') !== null) {
            $category = get_category($category_id);
            if (!$category || is_wp_error($category)) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error' => __('Invalid category ID', 'wp-news-source')
                ), 400);
            }
            $category_name = $category->name;
        }

        // Handle tags if provided
        $tag_ids_str = $existing->tag_ids;
        $tag_names_str = $existing->tag_names;

        if ($request->get_param('tag_ids') !== null) {
            $tag_ids = array_filter(array_map('absint', explode(',', sanitize_text_field($request->get_param('tag_ids')))));

            // Validate tag count
            $max_tags = WP_News_Source_Config::get_max_tags();
            if (count($tag_ids) > $max_tags) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error' => sprintf(__('Maximum %d tags allowed', 'wp-news-source'), $max_tags)
                ), 400);
            }

            $tag_names = array();
            foreach ($tag_ids as $tag_id) {
                $tag = get_term($tag_id, 'post_tag');
                if ($tag && !is_wp_error($tag)) {
                    $tag_names[] = $tag->name;
                }
            }
            $tag_ids_str = implode(',', $tag_ids);
            $tag_names_str = implode(',', $tag_names);
        }

        // Validate detection_rules JSON if provided
        $detection_rules = $request->get_param('detection_rules') !== null
            ? $request->get_param('detection_rules')
            : $existing->detection_rules;

        if ($request->get_param('detection_rules') !== null && !empty($detection_rules)) {
            $json_test = json_decode($detection_rules);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error' => __('Invalid JSON in detection_rules', 'wp-news-source')
                ), 400);
            }
        }

        $source_data = array(
            'name' => $name,
            'slug' => $slug,
            'source_type' => $request->get_param('source_type') !== null
                ? sanitize_text_field($request->get_param('source_type'))
                : (isset($existing->source_type) ? $existing->source_type : 'general'),
            'description' => $request->get_param('description') !== null
                ? sanitize_textarea_field($request->get_param('description'))
                : $existing->description,
            'keywords' => $request->get_param('keywords') !== null
                ? sanitize_textarea_field($request->get_param('keywords'))
                : $existing->keywords,
            'detection_rules' => $detection_rules ?: '',
            'category_id' => $category_id,
            'category_name' => $category_name,
            'tag_ids' => $tag_ids_str,
            'tag_names' => $tag_names_str,
            'auto_publish' => $request->get_param('auto_publish') !== null
                ? ($request->get_param('auto_publish') ? 1 : 0)
                : $existing->auto_publish,
            'requires_review' => $request->get_param('requires_review') !== null
                ? ($request->get_param('requires_review') ? 1 : 0)
                : $existing->requires_review,
        );

        $result = $this->db->update_source($id, $source_data);

        if ($result === false) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => __('Failed to update source', 'wp-news-source')
            ), 500);
        }

        $source = $this->db->get_source($id);
        unset($source->api_key);
        unset($source->webhook_url);

        return rest_ensure_response(array(
            'success' => true,
            'source' => $source,
            'message' => __('Source updated successfully', 'wp-news-source')
        ));
    }

    /**
     * Delete a source via REST API
     */
    public function delete_source($request) {
        $id = absint($request->get_param('id'));
        $existing = $this->db->get_source($id);

        if (!$existing) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => __('Source not found', 'wp-news-source')
            ), 404);
        }

        $result = $this->db->delete_source($id);

        if (!$result) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => __('Failed to delete source', 'wp-news-source')
            ), 500);
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => sprintf(__('Source "%s" deleted successfully', 'wp-news-source'), $existing->name)
        ));
    }
    
    /**
     * Get complete mapping for n8n
     */
    public function get_mapping($request) {
        $sources = $this->db->get_all_sources();
        $mapping = array();
        
        foreach ($sources as $source) {
            // Parse tags properly with slugs
            $tag_names = !empty($source->tag_names) ? array_map('trim', explode(',', $source->tag_names)) : array();
            $tag_ids = !empty($source->tag_ids) ? array_map('intval', explode(',', $source->tag_ids)) : array();
            
            $tags = array();
            if (!empty($tag_names)) {
                foreach ($tag_names as $i => $tag_name) {
                    $tag_id = isset($tag_ids[$i]) ? $tag_ids[$i] : 0;
                    if ($tag_id) {
                        $tag = get_term($tag_id, 'post_tag');
                        if ($tag && !is_wp_error($tag)) {
                            $tags[] = array(
                                'id' => $tag_id,
                                'name' => $tag_name,
                                'slug' => $tag->slug
                            );
                        }
                    }
                }
            }
            
            // Get category slug
            $category_slug = '';
            if ($source->category_id) {
                $category = get_category($source->category_id);
                if ($category && !is_wp_error($category)) {
                    $category_slug = $category->slug;
                }
            }
            
            $mapping[] = array(
                'source_name' => $source->name,
                'source_slug' => $source->slug,
                'keywords' => $source->keywords,
                'description' => $source->description,
                'detection_rules' => isset($source->detection_rules) ? $source->detection_rules : '',
                'category' => array(
                    'id' => intval($source->category_id),
                    'name' => $source->category_name,
                    'slug' => $category_slug
                ),
                'tags' => $tags,
                'auto_publish' => (bool)$source->auto_publish,
                'requires_review' => (bool)$source->requires_review,
                'detection_count' => intval($source->detection_count),
                'last_detected' => $source->last_detected
            );
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'sources' => $mapping,
            'total' => count($mapping),
            'purpose' => 'Use this mapping to detect sources in content and get their assigned categories and tags'
        ));
    }
    
    /**
     * Detect source based on content
     */
    public function detect_source($request) {
        $content = sanitize_textarea_field($request->get_param('content'));
        $title = sanitize_text_field($request->get_param('title'));
        
        // Combine title and content
        $full_content = '';
        if (!empty($title)) {
            $full_content .= $title . "\n\n";
        }
        $full_content .= $content;
        
        $detected = null;
        $confidence = 0;
        $detection_method = 'none';
        
        // Get all sources
        $sources = $this->db->get_all_sources();
        
        // Detection by exact name match (highest confidence)
        foreach ($sources as $source) {
            if (stripos($full_content, $source->name) !== false) {
                $detected = $source;
                $confidence = 1.0;
                $detection_method = 'name_match';
                break;
            }
        }
        
        // If not found by name, try keywords (medium confidence)
        if (!$detected) {
            foreach ($sources as $source) {
                if (!empty($source->keywords)) {
                    $keywords = array_map('trim', explode(',', $source->keywords));
                    $matches = 0;
                    $total_keywords = count($keywords);
                    
                    foreach ($keywords as $keyword) {
                        if (!empty($keyword) && stripos($full_content, $keyword) !== false) {
                            $matches++;
                        }
                    }
                    
                    // If at least 30% of keywords match
                    if ($total_keywords > 0 && ($matches / $total_keywords) >= 0.3) {
                        $detected = $source;
                        $confidence = min(0.9, 0.3 + ($matches / $total_keywords) * 0.6);
                        $detection_method = 'keyword_match';
                        
                        // If confidence is high enough, stop searching
                        if ($confidence >= 0.8) {
                            break;
                        }
                    }
                }
            }
        }
        
        if ($detected) {
            // Log detection with actual confidence and method
            $this->db->log_detection(
                $detected->id,
                null,
                $confidence,
                $detection_method,
                substr($full_content, 0, 500)
            );
            
            // Parse tags with slugs
            $tag_names = !empty($detected->tag_names) ? array_map('trim', explode(',', $detected->tag_names)) : array();
            $tag_ids = !empty($detected->tag_ids) ? array_map('intval', explode(',', $detected->tag_ids)) : array();
            
            $tags = array();
            if (!empty($tag_names)) {
                foreach ($tag_names as $i => $tag_name) {
                    $tag_id = isset($tag_ids[$i]) ? $tag_ids[$i] : 0;
                    if ($tag_id) {
                        $tag = get_term($tag_id, 'post_tag');
                        if ($tag && !is_wp_error($tag)) {
                            $tags[] = array(
                                'id' => $tag_id,
                                'name' => $tag_name,
                                'slug' => $tag->slug
                            );
                        }
                    }
                }
            }
            
            // Get category slug
            $category_slug = '';
            if ($detected->category_id) {
                $category = get_category($detected->category_id);
                if ($category && !is_wp_error($category)) {
                    $category_slug = $category->slug;
                }
            }
            
            // Prepare response
            $response = array(
                'success' => true,
                'detected' => true,
                'confidence' => $confidence,
                'detection_method' => $detection_method,
                'source' => array(
                    'id' => intval($detected->id),
                    'name' => $detected->name,
                    'slug' => $detected->slug,
                    'type' => $detected->source_type,
                    'keywords' => $detected->keywords,
                    'description' => $detected->description,
                    'detection_rules' => isset($detected->detection_rules) ? $detected->detection_rules : '',
                    'category' => array(
                        'id' => intval($detected->category_id),
                        'name' => $detected->category_name,
                        'slug' => $category_slug
                    ),
                    'tags' => $tags,
                    'auto_publish' => (bool) $detected->auto_publish,
                    'requires_review' => (bool) $detected->requires_review
                ),
                'instructions' => array(
                    'category_id' => intval($detected->category_id),
                    'tag_ids' => $tag_ids,
                    'auto_publish' => (bool) $detected->auto_publish,
                    'post_status' => $detected->auto_publish ? 'publish' : 'draft'
                )
            );
            
            // Notificar vía webhook si está configurado
            if (!empty($detected->webhook_url)) {
                wp_remote_post($detected->webhook_url, array(
                    'body' => json_encode(array(
                        'event' => 'source_detected',
                        'source' => $response['source'],
                        'timestamp' => current_time('mysql')
                    )),
                    'headers' => array(
                        'Content-Type' => 'application/json',
                        'X-WP-News-Source' => 'v' . $this->version
                    ),
                    'timeout' => 10
                ));
            }
            
            return rest_ensure_response($response);
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'detected' => false,
            'message' => __('No known source detected', 'wp-news-source'),
            'suggestion' => __('Try adding more context or keywords to your sources', 'wp-news-source')
        ));
    }
    
    /**
     * Valida contenido según las reglas de la fuente
     */
    public function validate_content($request) {
        $source_id = absint($request->get_param('source_id'));
        $content = sanitize_textarea_field($request->get_param('content'));
        
        $source = $this->db->get_source($source_id);
        
        if (!$source) {
            return rest_ensure_response(array(
                'success' => false,
                'error' => __('Source not found', 'wp-news-source')
            ));
        }
        
        $validation = array(
            'valid' => true,
            'warnings' => array(),
            'errors' => array()
        );
        
        // Validar longitud mínima
        if (str_word_count($content) < 50) {
            $validation['warnings'][] = __('Content is too short (less than 50 words)', 'wp-news-source');
        }
        
        // Validar presencia de palabras clave
        if (!empty($source->keywords)) {
            $keywords = explode(',', $source->keywords);
            $found_keywords = 0;
            
            foreach ($keywords as $keyword) {
                if (stripos($content, trim($keyword)) !== false) {
                    $found_keywords++;
                }
            }
            
            if ($found_keywords === 0) {
                $validation['warnings'][] = __('No keywords found for this source', 'wp-news-source');
            }
        }
        
        // Aplicar reglas personalizadas
        if (!empty($source->detection_rules)) {
            $rules = json_decode($source->detection_rules, true);
            
            if (is_array($rules)) {
                foreach ($rules as $rule) {
                    if (isset($rule['type']) && $rule['type'] === 'required') {
                        if (stripos($content, $rule['value']) === false) {
                            $validation['errors'][] = sprintf(
                                __('Missing required content: %s', 'wp-news-source'),
                                $rule['value']
                            );
                            $validation['valid'] = false;
                        }
                    }
                }
            }
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'validation' => $validation,
            'source' => array(
                'name' => $source->name,
                'auto_publish' => (bool) $source->auto_publish
            )
        ));
    }
    
    /**
     * Generate AI detection prompt
     */
    public function generate_detection_prompt($request) {
        $source_id = $request->get_param('source_id') ? absint($request->get_param('source_id')) : null;
        $content = sanitize_textarea_field($request->get_param('content'));
        $title = sanitize_text_field($request->get_param('title'));
        $all_sources = $request->get_param('all_sources');
        
        // Combine title and content
        $full_content = '';
        if (!empty($title)) {
            $full_content .= "Title: " . $title . "\n\n";
        }
        $full_content .= "Content: " . $content;
        
        // Get sources to analyze
        $sources = array();
        if ($source_id) {
            $source = $this->db->get_source($source_id);
            if ($source) {
                $sources[] = $source;
            }
        } else if ($all_sources) {
            $sources = $this->db->get_all_sources();
        }
        
        if (empty($sources)) {
            return rest_ensure_response(array(
                'success' => false,
                'error' => 'No sources found'
            ));
        }
        
        // Build the AI prompt
        $prompt = "Analyze the following content and determine which news source it belongs to based on the configured detection rules.\n\n";
        $prompt .= "CONTENT TO ANALYZE:\n";
        $prompt .= $full_content . "\n\n";
        $prompt .= "AVAILABLE SOURCES AND THEIR DETECTION RULES:\n\n";
        
        $source_info = array();
        foreach ($sources as $source) {
            $info = array(
                'id' => $source->id,
                'name' => $source->name,
                'keywords' => $source->keywords,
                'description' => $source->description
            );
            
            // Include AI detection rules if available
            if (!empty($source->detection_rules)) {
                $rules = json_decode($source->detection_rules, true);
                if ($rules) {
                    $info['detection_rules'] = $rules;
                    
                    // Add to prompt
                    $prompt .= "Source: " . $source->name . "\n";
                    if (!empty($source->description)) {
                        $prompt .= "Description: " . $source->description . "\n";
                    }
                    if (!empty($source->keywords)) {
                        $prompt .= "Keywords: " . $source->keywords . "\n";
                    }
                    $prompt .= "Detection Rules: " . json_encode($rules, JSON_PRETTY_PRINT) . "\n\n";
                }
            } else {
                // Basic detection for sources without AI rules
                $prompt .= "Source: " . $source->name . "\n";
                if (!empty($source->description)) {
                    $prompt .= "Description: " . $source->description . "\n";
                }
                if (!empty($source->keywords)) {
                    $prompt .= "Keywords: " . $source->keywords . "\n";
                }
                $prompt .= "Detection: Look for exact name match or keyword matches\n\n";
            }
            
            $source_info[] = $info;
        }
        
        $prompt .= "INSTRUCTIONS:\n";
        $prompt .= "1. Analyze the content against each source's detection rules\n";
        $prompt .= "2. Consider the context, jurisdiction, and validation rules\n";
        $prompt .= "3. Return the most likely source with a confidence score (0-1)\n";
        $prompt .= "4. If no source matches with confidence > 0.5, return 'unknown'\n";
        $prompt .= "5. Explain your reasoning\n\n";
        $prompt .= "RESPONSE FORMAT:\n";
        $prompt .= "{\n";
        $prompt .= "  \"source_id\": number or null,\n";
        $prompt .= "  \"source_name\": \"string\" or \"unknown\",\n";
        $prompt .= "  \"confidence\": 0.0 to 1.0,\n";
        $prompt .= "  \"reasoning\": \"explanation of detection\",\n";
        $prompt .= "  \"matched_rules\": [\"list of matched rules or keywords\"]\n";
        $prompt .= "}\n";
        
        return rest_ensure_response(array(
            'success' => true,
            'prompt' => $prompt,
            'sources_analyzed' => count($sources),
            'source_info' => $source_info,
            'usage_instructions' => array(
                'send_to_ai' => 'Send this prompt to your AI model (GPT, Claude, etc.)',
                'parse_response' => 'Parse the JSON response to get source_id and confidence',
                'apply_threshold' => 'Only accept detections with confidence > 0.7 for auto-assignment'
            )
        ));
    }
    
    /**
     * Maneja webhooks entrantes
     */
    public function handle_webhook($request) {
        $source_id = $request->get_param('source_id');
        $source = $this->db->get_source($source_id);

        if (!$source) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => 'Invalid source'
            ), 404);
        }

        // Webhook secret is always required
        $webhook_secret = get_option('wp_news_source_webhook_secret');
        $provided_secret = $request->get_header('X-Webhook-Secret');

        if (!$webhook_secret || !$provided_secret || !hash_equals($webhook_secret, $provided_secret)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => 'Invalid or missing webhook secret'
            ), 401);
        }

        // Process the webhook
        $data = $request->get_json_params();

        do_action('wpns_webhook_received', $source, $data);

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Webhook processed'
        ));
    }
    
    /**
     * Obtiene historial de detecciones
     */
    public function get_history($request) {
        $source_id = $request->get_param('source_id');
        $limit = $request->get_param('limit');
        
        $history = $this->db->get_detection_history($source_id, $limit);
        
        return rest_ensure_response(array(
            'success' => true,
            'history' => $history,
            'total' => count($history)
        ));
    }
    
    /**
     * Obtiene estadísticas
     */
    public function get_stats($request) {
        $stats = $this->db->get_source_stats();
        $sources = $this->db->get_all_sources();
        
        // Top fuentes por detecciones
        $top_sources = array();
        foreach ($sources as $source) {
            if ($source->detection_count > 0) {
                $top_sources[] = array(
                    'name' => $source->name,
                    'count' => intval($source->detection_count),
                    'last_detected' => $source->last_detected
                );
            }
        }
        
        // Ordenar por conteo
        usort($top_sources, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        return rest_ensure_response(array(
            'success' => true,
            'stats' => array(
                'total_sources' => intval($stats->total_sources),
                'total_detections' => intval($stats->total_detections),
                'avg_detections' => round($stats->avg_detections, 2),
                'top_sources' => array_slice($top_sources, 0, 10)
            )
        ));
    }
    
    /**
     * Exporta configuración
     */
    public function export_config($request) {
        $export_data = $this->db->export_sources();
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $export_data,
            'exported_at' => current_time('mysql'),
            'version' => $this->version
        ));
    }
    
    /**
     * Importa configuración
     */
    public function import_config($request) {
        $data = $request->get_param('data');

        $result = $this->db->import_sources($data);

        if ($result === false) {
            return rest_ensure_response(array(
                'success' => false,
                'error' => __('Invalid import data', 'wp-news-source')
            ));
        }

        return rest_ensure_response(array(
            'success' => true,
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
            'total' => $result['total'],
            'message' => sprintf(
                __('%d sources imported, %d skipped out of %d total', 'wp-news-source'),
                $result['imported'],
                $result['skipped'],
                $result['total']
            )
        ));
    }
    
    /**
     * Search categories with pagination and limit
     */
    public function search_categories($request) {
        // Removed debug: error_log('WP News Source Debug: search_categories called with params: ' . print_r($request->get_params(), true));
        
        $search = $request->get_param('search');
        $limit = $request->get_param('limit');
        
        $args = array(
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
            'number' => $limit
        );
        
        if (!empty($search)) {
            $args['search'] = $search;
        }
        
        // Removed debug: error_log('WP News Source Debug: get_categories args: ' . print_r($args, true));
        
        $categories = get_categories($args);
        $formatted_categories = array();
        
        // Removed debug: error_log('WP News Source Debug: Raw categories found: ' . count($categories));
        
        foreach ($categories as $category) {
            $formatted_categories[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->count,
                'description' => $category->description
            );
        }
        
        // Removed debug: error_log('WP News Source Debug: Formatted categories: ' . count($formatted_categories));
        
        return rest_ensure_response(array(
            'success' => true,
            'categories' => $formatted_categories,
            'total' => count($formatted_categories)
        ));
    }
    
    /**
     * Obtiene todas las categorías de WordPress
     */
    public function get_categories($request) {
        $categories = get_categories(array(
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        $formatted_categories = array();
        
        foreach ($categories as $category) {
            $formatted_categories[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->count,
                'description' => $category->description
            );
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'categories' => $formatted_categories,
            'total' => count($formatted_categories)
        ));
    }
    
    /**
     * Search tags with pagination and limit
     */
    public function search_tags($request) {
        // Removed debug: error_log('WP News Source Debug: search_tags called with params: ' . print_r($request->get_params(), true));
        
        $search = $request->get_param('search');
        $limit = $request->get_param('limit');
        
        $args = array(
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
            'number' => $limit
        );
        
        if (!empty($search)) {
            $args['search'] = $search;
        }
        
        // Removed debug: error_log('WP News Source Debug: get_tags args: ' . print_r($args, true));
        
        $tags = get_tags($args);
        $formatted_tags = array();
        
        // Removed debug: error_log('WP News Source Debug: Raw tags found: ' . count($tags));
        
        foreach ($tags as $tag) {
            $formatted_tags[] = array(
                'id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'count' => $tag->count
            );
        }
        
        // Removed debug: error_log('WP News Source Debug: Formatted tags: ' . count($formatted_tags));
        
        return rest_ensure_response(array(
            'success' => true,
            'tags' => $formatted_tags,
            'total' => count($formatted_tags)
        ));
    }
    
    /**
     * Obtiene todas las etiquetas de WordPress
     */
    public function get_tags($request) {
        $tags = get_tags(array(
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        $formatted_tags = array();
        
        foreach ($tags as $tag) {
            $formatted_tags[] = array(
                'id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'count' => $tag->count
            );
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'tags' => $formatted_tags,
            'total' => count($formatted_tags)
        ));
    }
    
    /**
     * Search posts with various filters
     */
    public function search_posts($request) {
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => min(
                intval($request->get_param('limit') ?: WP_News_Source_Config::get('search_limit_default')), 
                WP_News_Source_Config::get('search_limit_max')
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // Title search
        if ($title = $request->get_param('title')) {
            $args['s'] = $title;
        }
        
        // Date range (validate YYYY-MM-DD format)
        if ($date_from = $request->get_param('date_from')) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
                $args['date_query'][] = array(
                    'after' => $date_from,
                    'inclusive' => true
                );
            }
        }

        if ($date_to = $request->get_param('date_to')) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
                $args['date_query'][] = array(
                    'before' => $date_to,
                    'inclusive' => true
                );
            }
        }
        
        // Category filter
        if ($category_id = $request->get_param('category_id')) {
            $args['cat'] = $category_id;
        }
        
        // Tag filter
        if ($tag_ids = $request->get_param('tag_ids')) {
            $args['tag__in'] = $tag_ids;
        }
        
        // Status filter
        $status = $request->get_param('status') ?: 'any';
        if ($status !== 'any') {
            $args['post_status'] = $status;
        } else {
            $args['post_status'] = array('publish', 'draft', 'pending', 'private');
        }
        
        $query = new WP_Query($args);
        $posts = array();
        
        foreach ($query->posts as $post) {
            $featured_image_id = get_post_thumbnail_id($post->ID);
            $featured_image_url = get_the_post_thumbnail_url($post->ID, 'full');
            
            // Get categories and tags
            $categories = wp_get_post_categories($post->ID, array('fields' => 'all'));
            $tags = wp_get_post_tags($post->ID, array('fields' => 'all'));
            
            $posts[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'date' => $post->post_date,
                'status' => $post->post_status,
                'url' => get_permalink($post->ID),
                'featured_image' => array(
                    'id' => $featured_image_id ?: null,
                    'url' => $featured_image_url ?: null
                ),
                'categories' => array_map(function($cat) {
                    return array(
                        'id' => $cat->term_id,
                        'name' => $cat->name,
                        'slug' => $cat->slug
                    );
                }, $categories),
                'tags' => array_map(function($tag) {
                    return array(
                        'id' => $tag->term_id,
                        'name' => $tag->name,
                        'slug' => $tag->slug
                    );
                }, $tags)
            );
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'posts' => $posts,
            'total' => $query->found_posts,
            'page' => 1,
            'per_page' => count($posts)
        ));
    }
    
    /**
     * Create a post with full features
     */
    public function create_post($request) {
        $title = sanitize_text_field($request->get_param('title'));
        $content = wp_kses_post($request->get_param('content'));
        $author_id = $request->get_param('author_id') ?: get_current_user_id();
        $status = $request->get_param('status') ?: 'draft';
        
        // Log post creation attempt
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WP News Source - Creating post: ' . $title . ' (source_id: ' . $request->get_param('source_id') . ')');
        }
        
        // Prepare post data
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => $status,
            'post_type' => 'post',
            'post_author' => $author_id
        );
        
        // Set custom date if provided
        if ($post_date = $request->get_param('post_date')) {
            $post_data['post_date'] = $post_date;
            $post_data['post_date_gmt'] = get_gmt_from_date($post_date);
        }
        
        // Determine categories and tags
        $category_ids = array();
        $tag_ids = array();
        $source = null;
        
        // Option 1: Use source_id
        if ($source_id = $request->get_param('source_id')) {
            $source = $this->db->get_source($source_id);
        }
        
        // Option 2: Detect source by name
        elseif ($source_name = $request->get_param('source_name')) {
            $sources = $this->db->get_all_sources();
            foreach ($sources as $s) {
                if (strcasecmp($s->name, $source_name) === 0) {
                    $source = $s;
                    break;
                }
            }
        }
        
        // Apply source settings if found
        if ($source) {
            
            if ($source->category_id) {
                $category_ids[] = intval($source->category_id);
            }
            if (!empty($source->tag_ids)) {
                // Handle both string and array formats
                if (is_string($source->tag_ids)) {
                    $tag_ids = array_map('intval', array_filter(explode(',', $source->tag_ids)));
                } else if (is_array($source->tag_ids)) {
                    $tag_ids = array_map('intval', $source->tag_ids);
                }
            }
            // Override status if source has auto_publish
            if ($source->auto_publish && $status === 'draft') {
                $post_data['post_status'] = 'publish';
            }
        }
        
        // Override with explicit categories if provided
        if ($explicit_categories = $request->get_param('category_ids')) {
            $category_ids = $explicit_categories;
        }
        
        // Set categories
        if (!empty($category_ids)) {
            $post_data['post_category'] = $category_ids;
        }
        
        // Create the post
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'Failed to create post: ' . $post_id->get_error_message()
            ));
        }
        
        // Handle tags
        $final_tags = array();
        
        // Start with source tags
        if (!empty($tag_ids)) {
            $final_tags = $tag_ids;
        }
        
        // Override with explicit tag_ids if provided
        if ($explicit_tag_ids = $request->get_param('tag_ids')) {
            $final_tags = $explicit_tag_ids;
        }
        
        // Handle tag names (creates if not exist)
        if ($tag_names = $request->get_param('tag_names')) {
            foreach ($tag_names as $tag_name) {
                $tag_name = trim($tag_name);
                $tag = get_term_by('name', $tag_name, 'post_tag');
                
                if (!$tag) {
                    // Create the tag
                    $tag_data = wp_insert_term($tag_name, 'post_tag');
                    if (!is_wp_error($tag_data)) {
                        $final_tags[] = $tag_data['term_id'];
                    }
                } else {
                    $final_tags[] = $tag->term_id;
                }
            }
        }
        
        // Set all tags
        if (!empty($final_tags)) {
            $tag_result = wp_set_post_tags($post_id, $final_tags, false);
            if (is_wp_error($tag_result) && defined('WP_DEBUG') && WP_DEBUG) {
                error_log('WP News Source - Error setting tags: ' . $tag_result->get_error_message());
            }
        }
        
        // Handle featured image
        $featured_image_id = null;
        
        // Option 1: Use existing attachment
        if ($attachment_id = $request->get_param('attachment_id')) {
            $featured_image_id = intval($attachment_id);
        }
        
        // Option 2: Download from Telegram
        elseif ($telegram_file_id = $request->get_param('telegram_file_id')) {
            $bot_token = $request->get_param('telegram_bot_token') ?: get_option('wp_news_source_telegram_bot_token');
            if ($bot_token) {
                $telegram_url = $this->get_telegram_file_url($telegram_file_id, $bot_token);
                if ($telegram_url) {
                    $featured_image_id = $this->download_and_attach_image($telegram_url, $post_id, $title);
                }
            }
        }
        
        // Option 3: Download from URL
        elseif ($image_url = $request->get_param('featured_image_url')) {
            $featured_image_id = $this->download_and_attach_image($image_url, $post_id, $title);
        }
        
        // Option 4: Base64 image
        elseif ($base64_image = $request->get_param('featured_image_base64')) {
            $featured_image_id = $this->upload_base64_image($base64_image, $post_id, $title);
        }
        
        // Set featured image if uploaded successfully
        if ($featured_image_id && !is_wp_error($featured_image_id)) {
            set_post_thumbnail($post_id, $featured_image_id);
        }
        
        // Handle custom meta fields
        if ($meta_fields = $request->get_param('meta_fields')) {
            foreach ($meta_fields as $key => $value) {
                update_post_meta($post_id, sanitize_key($key), $value);
            }
        }
        
        // Save source information as post meta
        if ($source) {
            update_post_meta($post_id, '_wp_news_source_id', $source->id);
            update_post_meta($post_id, '_wp_news_source_name', $source->name);
            
            // Track detection in history
            $this->db->log_detection($source->id, $post_id, 1.0, 'manual_selection', $title);
        }
        
        // Get the created post with all details
        $post = get_post($post_id);
        $categories = wp_get_post_categories($post_id, array('fields' => 'all'));
        $tags = wp_get_post_tags($post_id, array('fields' => 'all'));
        
        return rest_ensure_response(array(
            'success' => true,
            'post' => array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'status' => $post->post_status,
                'date' => $post->post_date,
                'author_id' => $post->post_author,
                'url' => get_permalink($post->ID),
                'edit_url' => get_edit_post_link($post->ID, 'raw'),
                'featured_image_id' => get_post_thumbnail_id($post->ID),
                'featured_image_url' => get_the_post_thumbnail_url($post->ID, 'full'),
                'categories' => array_map(function($cat) {
                    return array(
                        'id' => $cat->term_id,
                        'name' => $cat->name,
                        'slug' => $cat->slug
                    );
                }, $categories),
                'tags' => array_map(function($tag) {
                    return array(
                        'id' => $tag->term_id,
                        'name' => $tag->name,
                        'slug' => $tag->slug
                    );
                }, $tags)
            ),
            'source_used' => $source ? array(
                'id' => $source->id,
                'name' => $source->name,
                'auto_publish' => (bool)$source->auto_publish
            ) : null
        ));
    }
    
    /**
     * Get all AI prompts
     */
    public function get_prompts($request) {
        $prompts = get_option('wp_news_source_prompts', array());
        
        return rest_ensure_response(array(
            'success' => true,
            'prompts' => $prompts,
            'total' => count($prompts)
        ));
    }
    
    /**
     * Get specific prompt
     */
    public function get_prompt($request) {
        $key = $request->get_param('key');
        $prompts = get_option('wp_news_source_prompts', array());
        
        if (isset($prompts[$key])) {
            return rest_ensure_response(array(
                'success' => true,
                'key' => $key,
                'prompt' => $prompts[$key]['prompt'],
                'description' => $prompts[$key]['description'] ?? '',
                'variables' => $prompts[$key]['variables'] ?? array(),
                'updated' => $prompts[$key]['updated'] ?? null
            ));
        }
        
        return rest_ensure_response(array(
            'success' => false,
            'message' => 'Prompt not found'
        ));
    }
    
    /**
     * Save AI prompt
     */
    public function save_prompt($request) {
        $key = sanitize_key($request->get_param('key'));
        $prompt = $request->get_param('prompt');
        $description = $request->get_param('description');
        $variables = $request->get_param('variables');
        
        $prompts = get_option('wp_news_source_prompts', array());
        
        $prompts[$key] = array(
            'prompt' => $prompt,
            'description' => $description,
            'variables' => $variables ?: array(),
            'updated' => current_time('mysql')
        );
        
        update_option('wp_news_source_prompts', $prompts);
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Prompt saved successfully',
            'key' => $key
        ));
    }
    
    /**
     * Upload image endpoint
     */
    public function upload_image($request) {
        $title = $request->get_param('title') ?: 'Uploaded Image';
        $attachment_id = null;
        
        // Option 1: Telegram file
        if ($telegram_file_id = $request->get_param('telegram_file_id')) {
            $bot_token = $request->get_param('telegram_bot_token') ?: get_option('wp_news_source_telegram_bot_token');
            
            if (!$bot_token) {
                return rest_ensure_response(array(
                    'success' => false,
                    'message' => 'Telegram bot token required'
                ));
            }
            
            $telegram_url = $this->get_telegram_file_url($telegram_file_id, $bot_token);
            if ($telegram_url) {
                $attachment_id = $this->download_and_attach_image($telegram_url, 0, $title);
            } else {
                return rest_ensure_response(array(
                    'success' => false,
                    'message' => 'Failed to get Telegram file URL'
                ));
            }
        }
        
        // Option 2: Direct URL
        elseif ($image_url = $request->get_param('image_url')) {
            $attachment_id = $this->download_and_attach_image($image_url, 0, $title);
        }
        
        // Option 3: Base64
        elseif ($base64_image = $request->get_param('image_base64')) {
            $attachment_id = $this->upload_base64_image($base64_image, 0, $title);
        }
        
        else {
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'No image data provided. Use image_url, image_base64, or telegram_file_id'
            ));
        }
        
        // Check for errors
        if (is_wp_error($attachment_id)) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'Upload failed: ' . $attachment_id->get_error_message()
            ));
        }
        
        // Get attachment details
        $attachment_url = wp_get_attachment_url($attachment_id);
        $attachment_metadata = wp_get_attachment_metadata($attachment_id);
        
        return rest_ensure_response(array(
            'success' => true,
            'attachment' => array(
                'id' => $attachment_id,
                'url' => $attachment_url,
                'title' => get_the_title($attachment_id),
                'mime_type' => get_post_mime_type($attachment_id),
                'width' => isset($attachment_metadata['width']) ? $attachment_metadata['width'] : null,
                'height' => isset($attachment_metadata['height']) ? $attachment_metadata['height'] : null,
                'file_size' => isset($attachment_metadata['filesize']) ? $attachment_metadata['filesize'] : null
            ),
            'message' => 'Image uploaded successfully. Use attachment_id: ' . $attachment_id . ' in create-post endpoint'
        ));
    }
    
    /**
     * Get Telegram file URL from file_id
     */
    private function get_telegram_file_url($file_id, $bot_token) {
        // Get file path from Telegram
        $api_url = "https://api.telegram.org/bot{$bot_token}/getFile?file_id={$file_id}";
        
        $response = wp_remote_get($api_url);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !$data['ok'] || !isset($data['result']['file_path'])) {
            return false;
        }
        
        // Construct download URL
        $file_path = $data['result']['file_path'];
        $download_url = "https://api.telegram.org/file/bot{$bot_token}/{$file_path}";
        
        return $download_url;
    }
    
    /**
     * Download and attach image from URL
     */
    private function download_and_attach_image($url, $post_id, $title) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Download image
        $tmp = download_url($url);
        
        if (is_wp_error($tmp)) {
            return $tmp;
        }
        
        // Get file info
        $file_array = array(
            'name' => basename($url),
            'tmp_name' => $tmp
        );
        
        // Check file type
        $wp_filetype = wp_check_filetype($file_array['name'], null);
        
        if (!$wp_filetype['type']) {
            @unlink($file_array['tmp_name']);
            return new WP_Error('invalid_file_type', 'Invalid file type');
        }
        
        // Upload
        $id = media_handle_sideload($file_array, $post_id, $title);
        
        // Clean up
        @unlink($file_array['tmp_name']);
        
        return $id;
    }
    
    /**
     * Upload base64 image
     */
    private function upload_base64_image($base64_string, $post_id, $title) {
        // Extract image data
        $image_parts = explode(';base64,', $base64_string);
        
        if (count($image_parts) !== 2) {
            return new WP_Error('invalid_base64', 'Invalid base64 image format');
        }
        
        $image_type_aux = explode('image/', $image_parts[0]);
        $image_type = isset($image_type_aux[1]) ? $image_type_aux[1] : 'jpeg';
        $image_base64 = base64_decode($image_parts[1]);
        
        if (!$image_base64) {
            return new WP_Error('decode_failed', 'Failed to decode base64 image');
        }
        
        // Generate filename
        $filename = sanitize_title($title) . '-' . time() . '.' . $image_type;
        
        // Get upload directory
        $upload = wp_upload_dir();
        $upload_dir = $upload['path'];
        $upload_url = $upload['url'];
        
        // Save file
        $file_path = $upload_dir . '/' . $filename;
        $file_saved = file_put_contents($file_path, $image_base64);
        
        if (!$file_saved) {
            return new WP_Error('save_failed', 'Failed to save image file');
        }
        
        // Create attachment
        $attachment = array(
            'post_mime_type' => 'image/' . $image_type,
            'post_title' => $title,
            'post_content' => '',
            'post_status' => 'inherit',
            'post_parent' => $post_id
        );
        
        $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);
        
        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        return $attach_id;
    }
}
