<?php
/**
 * Página de estadísticas
 */

// Verificar permisos
if (!current_user_can('view_news_source_stats')) {
    wp_die(__('No tienes permisos para acceder a esta página.', 'wp-news-source'));
}

$db = new WP_News_Source_DB();
$stats = $db->get_source_stats();
$history = $db->get_detection_history(null, 20);
$sources = $db->get_all_sources();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="wpns-stats-grid">
        <div class="wpns-stat-box">
            <h3><?php _e('Total de Fuentes', 'wp-news-source'); ?></h3>
            <div class="wpns-stat-number"><?php echo intval($stats->total_sources); ?></div>
        </div>
        
        <div class="wpns-stat-box">
            <h3><?php _e('Total de Detecciones', 'wp-news-source'); ?></h3>
            <div class="wpns-stat-number"><?php echo intval($stats->total_detections); ?></div>
        </div>
        
        <div class="wpns-stat-box">
            <h3><?php _e('Promedio por Fuente', 'wp-news-source'); ?></h3>
            <div class="wpns-stat-number"><?php echo number_format($stats->avg_detections, 1); ?></div>
        </div>
    </div>
    
    <h2><?php _e('Top Fuentes por Detecciones', 'wp-news-source'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Fuente', 'wp-news-source'); ?></th>
                <th><?php _e('Tipo', 'wp-news-source'); ?></th>
                <th><?php _e('Detecciones', 'wp-news-source'); ?></th>
                <th><?php _e('Última Detección', 'wp-news-source'); ?></th>
                <th><?php _e('Confianza Promedio', 'wp-news-source'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $top_sources = array();
            foreach ($sources as $source) {
                if ($source->detection_count > 0) {
                    $top_sources[] = $source;
                }
            }
            usort($top_sources, function($a, $b) {
                return $b->detection_count - $a->detection_count;
            });
            $top_sources = array_slice($top_sources, 0, 10);
            
            foreach ($top_sources as $source): 
            ?>
                <tr>
                    <td><strong><?php echo esc_html($source->name); ?></strong></td>
                    <td><?php echo esc_html($source->source_type); ?></td>
                    <td><?php echo intval($source->detection_count); ?></td>
                    <td>
                        <?php 
                        if ($source->last_detected) {
                            echo esc_html(human_time_diff(strtotime($source->last_detected), current_time('timestamp'))) . ' ' . __('atrás', 'wp-news-source');
                        } else {
                            echo __('Nunca', 'wp-news-source');
                        }
                        ?>
                    </td>
                    <td>-</td>
                </tr>
            <?php endforeach; ?>
            
            <?php if (empty($top_sources)): ?>
                <tr>
                    <td colspan="5"><?php _e('No hay detecciones registradas aún.', 'wp-news-source'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <h2><?php _e('Historial de Detecciones Recientes', 'wp-news-source'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Fecha', 'wp-news-source'); ?></th>
                <th><?php _e('Fuente', 'wp-news-source'); ?></th>
                <th><?php _e('Método', 'wp-news-source'); ?></th>
                <th><?php _e('Confianza', 'wp-news-source'); ?></th>
                <th><?php _e('Vista Previa', 'wp-news-source'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($history as $detection): ?>
                <tr>
                    <td><?php echo esc_html(human_time_diff(strtotime($detection->created_at), current_time('timestamp'))) . ' ' . __('atrás', 'wp-news-source'); ?></td>
                    <td><?php echo esc_html($detection->source_name); ?></td>
                    <td>
                        <?php 
                        $methods = array(
                            'keyword' => __('Palabra clave', 'wp-news-source'),
                            'name_match' => __('Nombre exacto', 'wp-news-source'),
                            'ai_context' => __('IA/Contexto', 'wp-news-source')
                        );
                        echo esc_html($methods[$detection->detection_method] ?? $detection->detection_method);
                        ?>
                    </td>
                    <td><?php echo number_format($detection->detection_confidence * 100, 0); ?>%</td>
                    <td>
                        <span class="wpns-preview" title="<?php echo esc_attr($detection->detected_content); ?>">
                            <?php echo esc_html(substr($detection->detected_content, 0, 50)) . '...'; ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            
            <?php if (empty($history)): ?>
                <tr>
                    <td colspan="5"><?php _e('No hay historial de detecciones.', 'wp-news-source'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.wpns-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.wpns-stat-box {
    background: #fff;
    border: 1px solid #ddd;
    padding: 20px;
    text-align: center;
    border-radius: 5px;
}

.wpns-stat-box h3 {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 14px;
    font-weight: normal;
}

.wpns-stat-number {
    font-size: 36px;
    font-weight: bold;
    color: #0073aa;
}

.wpns-preview {
    color: #666;
    font-size: 12px;
    cursor: help;
}
</style>