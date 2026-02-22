<?php
/**
 * Statistics page
 */

// Check permissions
if (!current_user_can('view_news_source_stats')) {
    wp_die(__('You do not have permission to access this page.', 'wp-news-source'));
}

// Get statistics
$db = new WP_News_Source_DB();
$sources = $db->get_all_sources();
$stats = $db->get_source_stats();
$recent_detections = $db->get_detection_history(null, 20);

// Calculate additional stats
$total_categories = 0;
$total_tags = 0;
$auto_publish_count = 0;
$sources_by_type = array();

foreach ($sources as $source) {
    if ($source->category_id) $total_categories++;
    if (!empty($source->tag_ids)) {
        $tags = explode(',', $source->tag_ids);
        $total_tags += count($tags);
    }
    if ($source->auto_publish) $auto_publish_count++;
    
    if (!isset($sources_by_type[$source->source_type])) {
        $sources_by_type[$source->source_type] = 0;
    }
    $sources_by_type[$source->source_type]++;
}

// Get WordPress stats
$post_count = wp_count_posts();
$published_posts = $post_count->publish;
$draft_posts = $post_count->draft;
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="wpns-stats-container">
        <!-- Overview Cards -->
        <div class="wpns-stats-grid">
            <div class="wpns-stat-card">
                <div class="wpns-stat-icon">
                    <span class="dashicons dashicons-networking"></span>
                </div>
                <div class="wpns-stat-content">
                    <h3><?php echo intval($stats->total_sources); ?></h3>
                    <p><?php _e('Total Sources', 'wp-news-source'); ?></p>
                </div>
            </div>
            
            <div class="wpns-stat-card">
                <div class="wpns-stat-icon">
                    <span class="dashicons dashicons-search"></span>
                </div>
                <div class="wpns-stat-content">
                    <h3><?php echo intval($stats->total_detections); ?></h3>
                    <p><?php _e('Total Detections', 'wp-news-source'); ?></p>
                </div>
            </div>
            
            <div class="wpns-stat-card">
                <div class="wpns-stat-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="wpns-stat-content">
                    <h3><?php echo $auto_publish_count; ?></h3>
                    <p><?php _e('Auto-publish Sources', 'wp-news-source'); ?></p>
                </div>
            </div>
            
            <div class="wpns-stat-card">
                <div class="wpns-stat-icon">
                    <span class="dashicons dashicons-admin-post"></span>
                </div>
                <div class="wpns-stat-content">
                    <h3><?php echo $published_posts; ?></h3>
                    <p><?php _e('Published Posts', 'wp-news-source'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Source Types Distribution -->
        <div class="wpns-stats-section">
            <h2><?php _e('Sources by Type', 'wp-news-source'); ?></h2>
            <div class="wpns-type-distribution">
                <?php 
                $types = array(
                    'government' => __('Government', 'wp-news-source'),
                    'company' => __('Company', 'wp-news-source'),
                    'ngo' => __('NGO', 'wp-news-source'),
                    'general' => __('General', 'wp-news-source')
                );
                foreach ($types as $type => $label): 
                    $count = isset($sources_by_type[$type]) ? $sources_by_type[$type] : 0;
                    $percentage = $stats->total_sources > 0 ? round(($count / $stats->total_sources) * 100) : 0;
                ?>
                    <div class="wpns-type-item">
                        <div class="wpns-type-label">
                            <span><?php echo esc_html($label); ?></span>
                            <span><?php echo $count; ?></span>
                        </div>
                        <div class="wpns-type-bar">
                            <div class="wpns-type-progress" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Recent Detections -->
        <div class="wpns-stats-section">
            <h2><?php _e('Recent Detections', 'wp-news-source'); ?></h2>
            <?php if (empty($recent_detections)): ?>
                <p class="description"><?php _e('No detections recorded yet.', 'wp-news-source'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Source', 'wp-news-source'); ?></th>
                            <th><?php _e('Method', 'wp-news-source'); ?></th>
                            <th><?php _e('Confidence', 'wp-news-source'); ?></th>
                            <th><?php _e('Content Preview', 'wp-news-source'); ?></th>
                            <th><?php _e('Date', 'wp-news-source'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_detections as $detection): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($detection->source_name); ?></strong>
                                </td>
                                <td>
                                    <?php echo esc_html($detection->detection_method); ?>
                                </td>
                                <td>
                                    <span class="wpns-confidence-badge wpns-confidence-<?php echo $detection->detection_confidence >= 80 ? 'high' : ($detection->detection_confidence >= 50 ? 'medium' : 'low'); ?>">
                                        <?php echo round($detection->detection_confidence); ?>%
                                    </span>
                                </td>
                                <td>
                                    <span class="wpns-content-preview">
                                        <?php echo esc_html(substr($detection->detected_content, 0, 100)); ?>...
                                    </span>
                                </td>
                                <td>
                                    <?php echo esc_html(human_time_diff(strtotime($detection->created_at))) . ' ' . __('ago', 'wp-news-source'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Top Performing Sources -->
        <div class="wpns-stats-section">
            <h2><?php _e('Top Performing Sources', 'wp-news-source'); ?></h2>
            <?php 
            $top_sources = array_filter($sources, function($s) { return $s->detection_count > 0; });
            usort($top_sources, function($a, $b) { return $b->detection_count - $a->detection_count; });
            $top_sources = array_slice($top_sources, 0, 10);
            ?>
            
            <?php if (empty($top_sources)): ?>
                <p class="description"><?php _e('No sources have detections yet.', 'wp-news-source'); ?></p>
            <?php else: ?>
                <div class="wpns-top-sources">
                    <?php foreach ($top_sources as $source): ?>
                        <div class="wpns-source-stat">
                            <div class="wpns-source-info">
                                <strong><?php echo esc_html($source->name); ?></strong>
                                <span class="wpns-source-type"><?php echo esc_html($source->source_type); ?></span>
                            </div>
                            <div class="wpns-source-count">
                                <?php echo intval($source->detection_count); ?> <?php _e('detections', 'wp-news-source'); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Export Options -->
        <div class="wpns-stats-section">
            <h2><?php _e('Export Data', 'wp-news-source'); ?></h2>
            <p><?php _e('Export statistics and detection history for analysis.', 'wp-news-source'); ?></p>
            <button class="button" id="wpns-export-stats">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export Statistics CSV', 'wp-news-source'); ?>
            </button>
            <button class="button" id="wpns-export-history">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export Detection History CSV', 'wp-news-source'); ?>
            </button>
        </div>
    </div>
</div>

<style>
/* Statistics Styles */
.wpns-stats-container {
    max-width: 1200px;
    margin-top: 20px;
}

/* Stats Grid */
.wpns-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.wpns-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.wpns-stat-icon {
    margin-right: 20px;
}

.wpns-stat-icon .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #2271b1;
}

.wpns-stat-content h3 {
    margin: 0;
    font-size: 32px;
    color: #1d2327;
}

.wpns-stat-content p {
    margin: 5px 0 0;
    color: #666;
}

/* Stats Sections */
.wpns-stats-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 25px;
    margin-bottom: 30px;
}

.wpns-stats-section h2 {
    margin-top: 0;
}

/* Type Distribution */
.wpns-type-distribution {
    max-width: 600px;
}

.wpns-type-item {
    margin-bottom: 15px;
}

.wpns-type-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.wpns-type-bar {
    background: #f0f0f1;
    height: 20px;
    border-radius: 3px;
    overflow: hidden;
}

.wpns-type-progress {
    background: #2271b1;
    height: 100%;
    transition: width 0.3s ease;
}

/* Confidence Badges */
.wpns-confidence-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.wpns-confidence-high {
    background: #d4f4dd;
    color: #0a7b3e;
}

.wpns-confidence-medium {
    background: #fff3cd;
    color: #856404;
}

.wpns-confidence-low {
    background: #f8d7da;
    color: #721c24;
}

/* Content Preview */
.wpns-content-preview {
    font-size: 12px;
    color: #666;
}

/* Top Sources */
.wpns-top-sources {
    max-width: 800px;
}

.wpns-source-stat {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.wpns-source-stat:last-child {
    border-bottom: none;
}

.wpns-source-info strong {
    display: block;
}

.wpns-source-type {
    font-size: 12px;
    color: #666;
}

.wpns-source-count {
    color: #2271b1;
    font-weight: 600;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Export statistics
    $('#wpns-export-stats').on('click', function() {
        // Create CSV content
        var csv = 'Metric,Value\n';
        csv += 'Total Sources,<?php echo intval($stats->total_sources); ?>\n';
        csv += 'Total Detections,<?php echo intval($stats->total_detections); ?>\n';
        csv += 'Average Detections per Source,<?php echo round($stats->avg_detections, 2); ?>\n';
        csv += 'Auto-publish Sources,<?php echo $auto_publish_count; ?>\n';
        csv += 'Published Posts,<?php echo $published_posts; ?>\n';
        csv += 'Draft Posts,<?php echo $draft_posts; ?>\n';
        
        // Add source type breakdown
        csv += '\nSource Type,Count\n';
        <?php foreach ($types as $type => $label): ?>
        csv += '<?php echo $label; ?>,<?php echo isset($sources_by_type[$type]) ? $sources_by_type[$type] : 0; ?>\n';
        <?php endforeach; ?>
        
        // Download CSV
        var blob = new Blob([csv], { type: 'text/csv' });
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'wp-news-source-stats-' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        window.URL.revokeObjectURL(url);
    });
    
    // Export detection history
    $('#wpns-export-history').on('click', function() {
        // Fetch all detection history via AJAX
        $.post(ajaxurl, {
            action: 'wpns_export_history',
            nonce: WPNewsSource.nonce
        }, function(response) {
            if (response.success) {
                var csv = 'Date,Source,Method,Confidence,Content Preview\n';
                response.data.forEach(function(detection) {
                    csv += '"' + detection.created_at + '",';
                    csv += '"' + detection.source_name + '",';
                    csv += '"' + detection.detection_method + '",';
                    csv += detection.detection_confidence + ',';
                    csv += '"' + detection.detected_content.substring(0, 100).replace(/"/g, '""') + '"\n';
                });
                
                // Download CSV
                var blob = new Blob([csv], { type: 'text/csv' });
                var url = window.URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'wp-news-source-history-' + new Date().toISOString().split('T')[0] + '.csv';
                a.click();
                window.URL.revokeObjectURL(url);
            }
        });
    });
});
</script>