<?php
/**
 * API REST para integración con n8n
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
     * Registra las rutas de la API
     */
    public function register_routes() {
        // Obtener todas las fuentes
        register_rest_route($this->namespace, '/sources', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_sources'),
            'permission_callback' => array($this, 'check_api_permission')
        ));
        
        // Obtener mapeo completo
        register_rest_route($this->namespace, '/mapping', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_mapping'),
            'permission_callback' => array($this, 'check_api_permission')
        ));
        
        // Detectar fuente por contenido (mejorado con IA)
        register_rest_route($this->namespace, '/detect', array(
            'methods' => 'POST',
            'callback' => array($this, 'detect_source'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'content' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Contenido para analizar'
                ),
                'use_ai' => array(
                    'required' => false,
                    'type' => 'boolean',
                    'default' => true,
                    'description' => 'Usar detección inteligente con contexto'
                ),
                'title' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Título del contenido (mejora la detección)'
                )
            )
        ));
        
        // Validar contenido antes de publicar
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
        
        // Webhook para notificaciones
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
        
        // Obtener historial de detecciones
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
     * Verifica permisos de administrador
     */
    public function check_admin_permission($request) {
        return current_user_can('manage_news_sources');
    }
    
    /**
     * Obtiene todas las fuentes
     */
    public function get_sources($request) {
        $sources = $this->db->get_all_sources();
        
        // Limpiar datos sensibles
        foreach ($sources as &$source) {
            unset($source->api_key);
            unset($source->webhook_url);
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'sources' => $sources,
            'total' => count($sources)
        ));
    }
    
    /**
     * Obtiene el mapeo completo para n8n
     */
    public function get_mapping($request) {
        $mapping = $this->db->get_mapping_for_n8n();
        
        // Añadir categorías generales de WordPress
        $categories = get_categories(array('hide_empty' => false));
        $category_mapping = array();
        
        foreach ($categories as $category) {
            $category_mapping[$category->name] = $category->term_id;
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'sources' => $mapping,
            'categories' => $category_mapping,
            'api_version' => $this->version,
            'features' => array(
                'ai_detection' => get_option('wp_news_source_enable_ai', true),
                'webhooks' => true,
                'history' => true,
                'stats' => true
            )
        ));
    }
    
    /**
     * Detecta la fuente basándose en el contenido (mejorado)
     */
    public function detect_source($request) {
        $content = $request->get_param('content');
        $title = $request->get_param('title');
        $use_ai = $request->get_param('use_ai');
        
        // Combinar título y contenido para mejor detección
        $full_content = '';
        if (!empty($title)) {
            $full_content .= $title . "\n\n";
        }
        $full_content .= $content;
        
        $detected = null;
        $method = 'none';
        $confidence = 0;
        
        if ($use_ai && get_option('wp_news_source_enable_ai', true)) {
            // Usar detección inteligente
            $result = $this->db->detect_source_intelligent($full_content);
            
            if ($result) {
                $detected = $result['source'];
                $confidence = $result['confidence'];
                $method = 'ai_context';
            }
        } else {
            // Detección simple por nombre
            $sources = $this->db->get_all_sources();
            
            foreach ($sources as $source) {
                if (stripos($full_content, $source->name) !== false) {
                    $detected = $source;
                    $confidence = 0.8;
                    $method = 'name_match';
                    break;
                }
            }
        }
        
        if ($detected) {
            // Registrar la detección
            $this->db->log_detection(
                $detected->id,
                null,
                $confidence,
                $method,
                substr($full_content, 0, 500)
            );
            
            // Preparar respuesta
            $response = array(
                'success' => true,
                'detected' => true,
                'confidence' => $confidence,
                'method' => $method,
                'source' => array(
                    'id' => intval($detected->id),
                    'name' => $detected->name,
                    'slug' => $detected->slug,
                    'type' => $detected->source_type,
                    'category' => array(
                        'id' => intval($detected->category_id),
                        'name' => $detected->category_name
                    ),
                    'tags' => array(),
                    'auto_publish' => (bool) $detected->auto_publish,
                    'requires_review' => (bool) $detected->requires_review
                )
            );
            
            // Procesar tags
            if (!empty($detected->tag_ids)) {
                $tag_ids = explode(',', $detected->tag_ids);
                $tag_names = explode(',', $detected->tag_names);
                
                for ($i = 0; $i < count($tag_ids); $i++) {
                    $response['source']['tags'][] = array(
                        'id' => intval($tag_ids[$i]),
                        'name' => trim($tag_names[$i])
                    );
                }
            }
            
            // Notificar vía webhook si está configurado
            if (!empty($detected->webhook_url)) {
                wp_remote_post($detected->webhook_url, array(
                    'body' => json_encode(array(
                        'event' => 'source_detected',
                        'source' => $response['source'],
                        'confidence' => $confidence,
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
            'message' => 'No se detectó ninguna fuente conocida',
            'suggestion' => 'Intenta añadir más contexto o palabras clave a las fuentes'
        ));
    }
    
    /**
     * Valida contenido según las reglas de la fuente
     */
    public function validate_content($request) {
        $source_id = $request->get_param('source_id');
        $content = $request->get_param('content');
        
        $source = $this->db->get_source($source_id);
        
        if (!$source) {
            return rest_ensure_response(array(
                'success' => false,
                'error' => 'Fuente no encontrada'
            ));
        }
        
        $validation = array(
            'valid' => true,
            'warnings' => array(),
            'errors' => array()
        );
        
        // Validar longitud mínima
        if (str_word_count($content) < 50) {
            $validation['warnings'][] = 'El contenido es muy corto (menos de 50 palabras)';
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
                $validation['warnings'][] = 'No se encontraron palabras clave de esta fuente';
            }
        }
        
        // Aplicar reglas personalizadas
        if (!empty($source->detection_rules)) {
            $rules = json_decode($source->detection_rules, true);
            
            if (is_array($rules)) {
                foreach ($rules as $rule) {
                    if (isset($rule['type']) && $rule['type'] === 'required') {
                        if (stripos($content, $rule['value']) === false) {
                            $validation['errors'][] = 'Falta contenido requerido: ' . $rule['value'];
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
     * Maneja webhooks entrantes
     */
    public function handle_webhook($request) {
        $source_id = $request->get_param('source_id');
        $source = $this->db->get_source($source_id);
        
        if (!$source) {
            return rest_ensure_response(array(
                'success' => false,
                'error' => 'Invalid source'
            ), 404);
        }
        
        // Verificar secreto si está configurado
        $webhook_secret = get_option('wp_news_source_webhook_secret');
        if ($webhook_secret) {
            $provided_secret = $request->get_header('X-Webhook-Secret');
            if ($provided_secret !== $webhook_secret) {
                return rest_ensure_response(array(
                    'success' => false,
                    'error' => 'Invalid webhook secret'
                ), 401);
            }
        }
        
        // Procesar el webhook
        $data = $request->get_json_params();
        
        // Disparar acción para que otros plugins puedan procesarlo
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
        
        $imported = $this->db->import_sources($data);
        
        if ($imported === false) {
            return rest_ensure_response(array(
                'success' => false,
                'error' => 'Datos de importación inválidos'
            ));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'imported' => $imported,
            'message' => sprintf('Se importaron %d fuentes', $imported)
        ));
    }
    
    /**
     * Search categories with pagination and limit
     */
    public function search_categories($request) {
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
        
        $categories = get_categories($args);
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
        
        $tags = get_tags($args);
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
}