<?php
/**
 * Página de configuración
 */

// Verificar permisos
if (!current_user_can('manage_news_sources')) {
    wp_die(__('No tienes permisos para acceder a esta página.', 'wp-news-source'));
}

// Guardar configuración
if (isset($_POST['submit'])) {
    check_admin_referer('wpns_settings');
    
    update_option('wp_news_source_enable_ai', isset($_POST['enable_ai']));
    update_option('wp_news_source_require_api_key', isset($_POST['require_api_key']));
    update_option('wp_news_source_min_confidence', intval($_POST['min_confidence']));
    
    if (isset($_POST['regenerate_api_key'])) {
        update_option('wp_news_source_api_key', wp_generate_password(32, false));
    }
    
    if (isset($_POST['regenerate_webhook_secret'])) {
        update_option('wp_news_source_webhook_secret', wp_generate_password(16, false));
    }
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Configuración guardada correctamente.', 'wp-news-source') . '</p></div>';
}

// Obtener valores actuales
$enable_ai = get_option('wp_news_source_enable_ai', true);
$require_api_key = get_option('wp_news_source_require_api_key', false);
$api_key = get_option('wp_news_source_api_key');
$webhook_secret = get_option('wp_news_source_webhook_secret');
$min_confidence = get_option('wp_news_source_min_confidence', 30);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('wpns_settings'); ?>
        
        <h2><?php _e('Configuración General', 'wp-news-source'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Detección Inteligente', 'wp-news-source'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_ai" value="1" <?php checked($enable_ai); ?>>
                        <?php _e('Habilitar detección por IA/contexto', 'wp-news-source'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Usa el contexto y palabras clave para mejorar la detección de fuentes.', 'wp-news-source'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="min_confidence"><?php _e('Confianza Mínima', 'wp-news-source'); ?></label>
                </th>
                <td>
                    <input type="number" id="min_confidence" name="min_confidence" value="<?php echo esc_attr($min_confidence); ?>" min="0" max="100" step="5">
                    <p class="description">
                        <?php _e('Puntuación mínima (0-100) para considerar una detección válida.', 'wp-news-source'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Seguridad API', 'wp-news-source'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Requerir API Key', 'wp-news-source'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="require_api_key" value="1" <?php checked($require_api_key); ?>>
                        <?php _e('Requerir API Key para acceder a los endpoints', 'wp-news-source'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Si está habilitado, las peticiones deben incluir el header X-API-Key.', 'wp-news-source'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('API Key', 'wp-news-source'); ?></th>
                <td>
                    <input type="text" class="large-text" value="<?php echo esc_attr($api_key); ?>" readonly>
                    <p>
                        <label>
                            <input type="checkbox" name="regenerate_api_key" value="1">
                            <?php _e('Regenerar API Key', 'wp-news-source'); ?>
                        </label>
                    </p>
                    <p class="description">
                        <?php _e('Usa este key en el header de tus peticiones: X-API-Key: [tu-key]', 'wp-news-source'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Webhook Secret', 'wp-news-source'); ?></th>
                <td>
                    <input type="text" class="regular-text" value="<?php echo esc_attr($webhook_secret); ?>" readonly>
                    <p>
                        <label>
                            <input type="checkbox" name="regenerate_webhook_secret" value="1">
                            <?php _e('Regenerar Webhook Secret', 'wp-news-source'); ?>
                        </label>
                    </p>
                    <p class="description">
                        <?php _e('Secreto para verificar webhooks entrantes.', 'wp-news-source'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Integración n8n', 'wp-news-source'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Endpoints Disponibles', 'wp-news-source'); ?></th>
                <td>
                    <div class="wpns-endpoints-list">
                        <?php 
                        $endpoints = array(
                            'mapping' => __('Obtener mapeo completo', 'wp-news-source'),
                            'detect' => __('Detectar fuente (POST)', 'wp-news-source'),
                            'sources' => __('Listar fuentes', 'wp-news-source'),
                            'validate' => __('Validar contenido (POST)', 'wp-news-source'),
                            'history' => __('Historial de detecciones', 'wp-news-source'),
                            'stats' => __('Estadísticas', 'wp-news-source'),
                            'categories' => __('Categorías de WordPress', 'wp-news-source'),
                            'tags' => __('Etiquetas de WordPress', 'wp-news-source')
                        );
                        
                        foreach ($endpoints as $endpoint => $description): 
                            $url = rest_url('wp-news-source/v1/' . $endpoint);
                        ?>
                            <div class="wpns-endpoint-item">
                                <code><?php echo esc_html($url); ?></code>
                                <button type="button" class="button button-small wpns-copy-endpoint" data-endpoint="<?php echo esc_attr($url); ?>">
                                    <?php _e('Copiar', 'wp-news-source'); ?>
                                </button>
                                <span class="description"><?php echo esc_html($description); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Importar/Exportar', 'wp-news-source'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Exportar Configuración', 'wp-news-source'); ?></th>
                <td>
                    <button type="button" class="button" id="wpns-export-btn">
                        <?php _e('Exportar Fuentes', 'wp-news-source'); ?>
                    </button>
                    <p class="description">
                        <?php _e('Descarga todas las fuentes en formato JSON.', 'wp-news-source'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Importar Configuración', 'wp-news-source'); ?></th>
                <td>
                    <input type="file" id="wpns-import-file" accept=".json" style="display: none;">
                    <button type="button" class="button" id="wpns-import-btn">
                        <?php _e('Importar Fuentes', 'wp-news-source'); ?>
                    </button>
                    <p class="description">
                        <?php _e('Importa fuentes desde un archivo JSON.', 'wp-news-source'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit" class="button button-primary" value="<?php _e('Guardar Cambios', 'wp-news-source'); ?>">
        </p>
    </form>
</div>

<style>
.wpns-endpoints-list {
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 3px;
}

.wpns-endpoint-item {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.wpns-endpoint-item code {
    flex: 1;
    background: #fff;
    padding: 5px 10px;
    border: 1px solid #ddd;
}

.wpns-endpoint-item .description {
    color: #666;
    font-size: 13px;
}
</style>