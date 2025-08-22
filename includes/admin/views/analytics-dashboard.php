<?php
/**
 * YHT Analytics Dashboard Admin View
 * Provides admin interface for analytics visualization
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Check user permissions
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div id="yht-analytics-dashboard">
        <!-- Dashboard will be populated by JavaScript -->
        <div class="yht-dashboard-loading">
            <p>📊 Loading analytics dashboard...</p>
        </div>
    </div>
</div>

<style>
.yht-dashboard-loading {
    text-align: center;
    padding: 60px 20px;
    color: #666;
    font-size: 16px;
}

.yht-dashboard-loading p {
    margin: 0;
}
</style>

<script type="text/javascript">
// Pass WordPress data to JavaScript
window.yhtData = {
    nonce: '<?php echo wp_create_nonce('wp_rest'); ?>',
    ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
    restUrl: '<?php echo rest_url('yht/v1/'); ?>',
    isAdmin: <?php echo current_user_can('manage_options') ? 'true' : 'false'; ?>
};
</script>