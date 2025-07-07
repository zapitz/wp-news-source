<?php
/**
 * Vista principal del panel de administración
 */

// Verificar permisos
if (!current_user_can('manage_news_sources')) {
    wp_die(__('No tienes permisos para acceder a esta página.', 'wp-news-source'));
}

// Obtener todas las fuentes
$db = new WP_News_Source_DB();
$sources = $db->get_all_sources();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=wp-news-source-add'); ?>" class="page-title-action">
        <?php _e('Añadir Nueva', 'wp-news-source'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Acción completada correctamente.', 'wp-news-source'); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="wpns-info-box">
        <h3><?php _e('Endpoints de API para n8n', 'wp-news-source'); ?></h3>
        <p><?php _e('Usa estos endpoints en tu workflow de n8n:', 'wp-news-source'); ?></p>
        <ul>
            <li><code><?php echo rest_url('wp-news-source/v1/mapping'); ?></code> - <?php _e('Obtener mapeo completo', 'wp-news-source'); ?></li>
            <li><code><?php echo rest_url('wp-news-source/v1/detect'); ?></code> - <?php _e('Detectar fuente (POST)', 'wp-news-source'); ?></li>
            <li><code><?php echo rest_url('wp-news-source/v1/sources'); ?></code> - <?php _e('Listar todas las fuentes', 'wp-news-source'); ?></li>
        </ul>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column"><?php _e('Nombre', 'wp-news-source'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Tipo', 'wp-news-source'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Categoría', 'wp-news-source'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Etiquetas', 'wp-news-source'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Auto-publicar', 'wp-news-source'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Acciones', 'wp-news-source'); ?></th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if (empty($sources)): ?>
                <tr>
                    <td colspan="6" class="no-items">
                        <?php _e('No se han encontrado fuentes.', 'wp-news-source'); ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($sources as $source): ?>
                    <tr data-source-id="<?php echo esc_attr($source->id); ?>">
                        <td>
                            <strong><?php echo esc_html($source->name); ?></strong>
                            <br>
                            <span class="row-actions">
                                <span class="slug"><?php echo esc_html($source->slug); ?></span>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $types = array(
                                'government' => __('Gobierno', 'wp-news-source'),
                                'company' => __('Empresa', 'wp-news-source'),
                                'ngo' => __('ONG', 'wp-news-source'),
                                'general' => __('General', 'wp-news-source')
                            );
                            echo esc_html($types[$source->source_type] ?? $source->source_type);
                            ?>
                        </td>
                        <td>
                            <?php if ($source->category_id): ?>
                                <span class="wpns-category-badge">
                                    <?php echo esc_html($source->category_name); ?> 
                                    <small>(ID: <?php echo esc_html($source->category_id); ?>)</small>
                                </span>
                            <?php else: ?>
                                <span class="description"><?php _e('Sin categoría', 'wp-news-source'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($source->tag_names)): ?>
                                <?php 
                                $tags = explode(',', $source->tag_names);
                                $tag_ids = explode(',', $source->tag_ids);
                                foreach ($tags as $index => $tag): 
                                ?>
                                    <span class="wpns-tag-badge">
                                        <?php echo esc_html(trim($tag)); ?>
                                        <small>(<?php echo esc_html($tag_ids[$index]); ?>)</small>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="description"><?php _e('Sin etiquetas', 'wp-news-source'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($source->auto_publish): ?>
                                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                            <?php else: ?>
                                <span class="dashicons dashicons-no-alt" style="color: #dc3232;"></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="button button-small wpns-edit-source" 
                                    data-source-id="<?php echo esc_attr($source->id); ?>">
                                <?php _e('Editar', 'wp-news-source'); ?>
                            </button>
                            <button class="button button-small button-link-delete wpns-delete-source" 
                                    data-source-id="<?php echo esc_attr($source->id); ?>"
                                    data-source-name="<?php echo esc_attr($source->name); ?>">
                                <?php _e('Eliminar', 'wp-news-source'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="wpns-stats">
        <h3><?php _e('Estadísticas', 'wp-news-source'); ?></h3>
        <p><?php printf(__('Total de fuentes: %d', 'wp-news-source'), count($sources)); ?></p>
    </div>
</div>

<!-- Modal de edición -->
<div id="wpns-edit-modal" class="wpns-modal" style="display: none;">
    <div class="wpns-modal-content">
        <span class="wpns-close">&times;</span>
        <h2><?php _e('Editar Fuente', 'wp-news-source'); ?></h2>
        <div id="wpns-edit-form-container">
            <!-- El formulario se cargará aquí dinámicamente -->
        </div>
    </div>
</div>