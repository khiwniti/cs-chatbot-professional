<?php
/**
 * CS Chatbot Professional - Uninstall Script
 * 
 * @package CSChatbot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit('Direct access forbidden.');
}

/**
 * Uninstall CS Chatbot Professional plugin
 */
function cs_chatbot_uninstall() {
    global $wpdb;
    
    // Remove database tables
    cs_chatbot_remove_tables();
    
    // Remove plugin options
    cs_chatbot_remove_options();
    
    // Clear scheduled events
    cs_chatbot_clear_cron_jobs();
    
    // Remove upload directories
    cs_chatbot_remove_directories();
    
    // Remove user capabilities
    cs_chatbot_remove_capabilities();
    
    // Clear any cached data
    wp_cache_flush();
}

/**
 * Remove database tables
 */
function cs_chatbot_remove_tables() {
    global $wpdb;
    
    $tables = [
        $wpdb->prefix . 'cs_chatbot_conversations',
        $wpdb->prefix . 'cs_chatbot_messages',
        $wpdb->prefix . 'cs_chatbot_knowledge',
        $wpdb->prefix . 'cs_chatbot_categories',
        $wpdb->prefix . 'cs_chatbot_analytics',
        $wpdb->prefix . 'cs_chatbot_agents',
        $wpdb->prefix . 'cs_chatbot_quick_responses'
    ];
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}

/**
 * Remove plugin options
 */
function cs_chatbot_remove_options() {
    $options = [
        'cs_chatbot_options',
        'cs_chatbot_version',
        'cs_chatbot_install_date',
        'cs_chatbot_db_version',
        'cs_chatbot_activation_redirect',
        'cs_chatbot_analytics_data',
        'cs_chatbot_cache_data'
    ];
    
    foreach ($options as $option) {
        delete_option($option);
        delete_site_option($option); // For multisite
    }
}

/**
 * Clear scheduled cron jobs
 */
function cs_chatbot_clear_cron_jobs() {
    $cron_jobs = [
        'cs_chatbot_daily_analytics',
        'cs_chatbot_weekly_report',
        'cs_chatbot_cleanup_sessions',
        'cs_chatbot_update_agent_stats'
    ];
    
    foreach ($cron_jobs as $job) {
        wp_clear_scheduled_hook($job);
    }
}

/**
 * Remove upload directories
 */
function cs_chatbot_remove_directories() {
    $upload_dir = wp_upload_dir();
    $cs_chatbot_dir = $upload_dir['basedir'] . '/cs-chatbot';
    
    if (file_exists($cs_chatbot_dir)) {
        cs_chatbot_remove_directory_recursive($cs_chatbot_dir);
    }
}

/**
 * Recursively remove directory and all contents
 */
function cs_chatbot_remove_directory_recursive($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            cs_chatbot_remove_directory_recursive($path);
        } else {
            unlink($path);
        }
    }
    
    return rmdir($dir);
}

/**
 * Remove custom capabilities
 */
function cs_chatbot_remove_capabilities() {
    $capabilities = [
        'manage_cs_chatbot',
        'view_chatbot_conversations',
        'manage_chatbot_knowledge',
        'handle_live_chat',
        'view_chatbot_analytics'
    ];
    
    $roles = ['administrator', 'editor'];
    
    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            foreach ($capabilities as $cap) {
                $role->remove_cap($cap);
            }
        }
    }
}

/**
 * Remove user meta data
 */
function cs_chatbot_remove_user_meta() {
    global $wpdb;
    
    $meta_keys = [
        'cs_chatbot_agent_status',
        'cs_chatbot_preferences',
        'cs_chatbot_stats'
    ];
    
    foreach ($meta_keys as $meta_key) {
        $wpdb->delete(
            $wpdb->usermeta,
            ['meta_key' => $meta_key],
            ['%s']
        );
    }
}

/**
 * Remove post meta data
 */
function cs_chatbot_remove_post_meta() {
    global $wpdb;
    
    $meta_keys = [
        '_cs_chatbot_enabled',
        '_cs_chatbot_settings'
    ];
    
    foreach ($meta_keys as $meta_key) {
        $wpdb->delete(
            $wpdb->postmeta,
            ['meta_key' => $meta_key],
            ['%s']
        );
    }
}

/**
 * Remove transients
 */
function cs_chatbot_remove_transients() {
    global $wpdb;
    
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cs_chatbot_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_cs_chatbot_%'");
    
    // For multisite
    if (is_multisite()) {
        $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_site_transient_cs_chatbot_%'");
        $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_site_transient_timeout_cs_chatbot_%'");
    }
}

/**
 * Log uninstallation
 */
function cs_chatbot_log_uninstall() {
    error_log('CS Chatbot Professional: Plugin uninstalled at ' . current_time('mysql'));
}

// Run uninstallation
cs_chatbot_uninstall();
cs_chatbot_remove_user_meta();
cs_chatbot_remove_post_meta();
cs_chatbot_remove_transients();
cs_chatbot_log_uninstall();