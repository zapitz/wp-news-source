<?php
/**
 * Clase para manejar las operaciones de base de datos
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
     * Obtiene todas las fuentes
     */
    public function get_all_sources() {
        global $wpdb;
        
        $query = "SELECT * FROM {$this->table_name} ORDER BY name ASC";
        return $wpdb->get_results($query);
    }
    
    /**
     * Obtiene una fuente por ID
     */
    public function get_source($id) {
        global $wpdb;
        
        $query = $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id);
        return $wpdb->get_row($query);
    }
    
    /**
     * Obtiene una fuente por slug
     */
    public function get_source_by_slug($slug) {
        global $wpdb;
        
        $query = $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE slug = %s", $slug);
        return $wpdb->get_row($query);
    }
    
    /**
     * Busca fuentes por nombre
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
     * Inserta una nueva fuente
     */
    public function insert_source($data) {
        global $wpdb;
        
        // Generar API key si se solicita
        if (isset($data['generate_api_key']) && $data['generate_api_key']) {
            $data['api_key'] = wp_generate_password(32, false);
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'name' => $data['name'],
                'slug' => $data['slug'],
                'source_type' => $data['source_type'],
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
     * Actualiza una fuente existente
     */
    public function update_source($id, $data) {
        global $wpdb;
        
        $update_data = array(
            'name' => $data['name'],
            'slug' => $data['slug'],
            'source_type' => $data['source_type'],
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
        
        // Solo actualizar API key si se proporciona nueva
        if (isset($data['api_key'])) {
            $update_data['api_key'] = $data['api_key'];
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%s'),
            array('%d')
        );
        
        return $result !== false ? $id : false;
    }
    
    /**
     * Elimina una fuente
     */
    public function delete_source($id) {
        global $wpdb;
        
        // Eliminar historial asociado
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
     * Registra una detección
     */
    public function log_detection($source_id, $post_id = null, $confidence = 0, $method = 'keyword', $content = '') {
        global $wpdb;
        
        // Registrar en historial
        $wpdb->insert(
            $this->history_table,
            array(
                'source_id' => $source_id,
                'post_id' => $post_id,
                'detection_confidence' => $confidence,
                'detection_method' => $method,
                'detected_content' => substr($content, 0, 500) // Solo primeros 500 chars
            ),
            array('%d', '%d', '%f', '%s', '%s')
        );
        
        // Actualizar contadores en la fuente
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_name} 
             SET detection_count = detection_count + 1, 
                 last_detected = NOW() 
             WHERE id = %d",
            $source_id
        ));
    }
    
    /**
     * Obtiene historial de detecciones
     */
    public function get_detection_history($source_id = null, $limit = 50) {
        global $wpdb;
        
        $query = "SELECT d.*, s.name as source_name 
                  FROM {$this->history_table} d
                  LEFT JOIN {$this->table_name} s ON d.source_id = s.id";
        
        if ($source_id) {
            $query .= $wpdb->prepare(" WHERE d.source_id = %d", $source_id);
        }
        
        $query .= " ORDER BY d.created_at DESC LIMIT %d";
        
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
        
        return json_encode($sources, JSON_PRETTY_PRINT);
    }
    
    /**
     * Importa fuentes desde JSON
     */
    public function import_sources($json_data) {
        $sources = json_decode($json_data, true);
        $imported = 0;
        
        if (!is_array($sources)) {
            return false;
        }
        
        foreach ($sources as $source) {
            // Verificar si ya existe
            $existing = $this->get_source_by_slug($source['slug']);
            
            if (!$existing) {
                $this->insert_source($source);
                $imported++;
            }
        }
        
        return $imported;
    }
    
    /**
     * Detecta fuente usando IA/contexto mejorado
     */
    public function detect_source_intelligent($content) {
        $sources = $this->get_all_sources();
        $best_match = null;
        $best_score = 0;
        
        foreach ($sources as $source) {
            $score = 0;
            
            // 1. Búsqueda exacta del nombre (peso alto)
            if (stripos($content, $source->name) !== false) {
                $score += 50;
            }
            
            // 2. Búsqueda de palabras clave (peso medio)
            if (!empty($source->keywords)) {
                $keywords = explode(',', $source->keywords);
                foreach ($keywords as $keyword) {
                    if (stripos($content, trim($keyword)) !== false) {
                        $score += 20;
                    }
                }
            }
            
            // 3. Análisis de descripción/contexto (peso variable)
            if (!empty($source->description)) {
                // Extraer frases clave de la descripción
                $context_phrases = $this->extract_key_phrases($source->description);
                foreach ($context_phrases as $phrase) {
                    if (stripos($content, $phrase) !== false) {
                        $score += 15;
                    }
                }
            }
            
            // 4. Reglas de detección personalizadas
            if (!empty($source->detection_rules)) {
                $rules = json_decode($source->detection_rules, true);
                if (is_array($rules)) {
                    foreach ($rules as $rule) {
                        if ($this->evaluate_rule($rule, $content)) {
                            $score += $rule['weight'] ?? 10;
                        }
                    }
                }
            }
            
            if ($score > $best_score) {
                $best_score = $score;
                $best_match = $source;
            }
        }
        
        // Umbral mínimo de confianza (configurable)
        $min_confidence = apply_filters('wpns_min_detection_confidence', 30);
        
        if ($best_score >= $min_confidence) {
            return array(
                'source' => $best_match,
                'confidence' => min($best_score / 100, 1), // Normalizar a 0-1
                'score' => $best_score
            );
        }
        
        return null;
    }
    
    /**
     * Extrae frases clave de un texto
     */
    private function extract_key_phrases($text) {
        // Simplificado: extraer frases entre comillas o después de dos puntos
        $phrases = array();
        
        // Buscar frases entre comillas
        preg_match_all('/"([^"]+)"/', $text, $matches);
        if (!empty($matches[1])) {
            $phrases = array_merge($phrases, $matches[1]);
        }
        
        // Buscar elementos de lista después de guiones
        preg_match_all('/- (.+)$/m', $text, $matches);
        if (!empty($matches[1])) {
            $phrases = array_merge($phrases, $matches[1]);
        }
        
        return array_unique($phrases);
    }
    
    /**
     * Evalúa una regla de detección
     */
    private function evaluate_rule($rule, $content) {
        if (!isset($rule['type']) || !isset($rule['value'])) {
            return false;
        }
        
        switch ($rule['type']) {
            case 'contains':
                return stripos($content, $rule['value']) !== false;
                
            case 'regex':
                return preg_match($rule['value'], $content) > 0;
                
            case 'starts_with':
                return stripos($content, $rule['value']) === 0;
                
            case 'word_count_min':
                return str_word_count($content) >= intval($rule['value']);
                
            default:
                return false;
        }
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