<?php
/**
 * Plugin Name: CS Chatbot Professional
 * Plugin URI: https://seo-forge.bitebase.app
 * Description: Professional WordPress chatbot plugin with AI-powered customer service, live chat, automated responses, and comprehensive analytics. Built with modern architecture and security best practices.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: CS Chatbot Team
 * Author URI: https://seo-forge.bitebase.app
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cs-chatbot
 * Domain Path: /languages
 * Network: false
 * Update URI: https://seo-forge.bitebase.app/updates
 *
 * @package CSChatbot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Plugin constants (only if not already defined)
if (!defined('CS_CHATBOT_VERSION')) {
    define('CS_CHATBOT_VERSION', '1.0.0');
}
if (!defined('CS_CHATBOT_MIN_PHP')) {
    define('CS_CHATBOT_MIN_PHP', '8.0');
}
if (!defined('CS_CHATBOT_MIN_WP')) {
    define('CS_CHATBOT_MIN_WP', '6.0');
}

// Define plugin paths and URLs (only if not already defined)
if (!defined('CS_CHATBOT_FILE')) {
    define('CS_CHATBOT_FILE', __FILE__);
}
if (!defined('CS_CHATBOT_PATH')) {
    define('CS_CHATBOT_PATH', plugin_dir_path(__FILE__));
}
if (!defined('CS_CHATBOT_URL')) {
    define('CS_CHATBOT_URL', plugin_dir_url(__FILE__));
}
if (!defined('CS_CHATBOT_BASENAME')) {
    define('CS_CHATBOT_BASENAME', plugin_basename(__FILE__));
}

/**
 * Check system requirements before loading the plugin
 */
function cs_chatbot_check_requirements() {
    global $wp_version;
    
    // Check PHP version
    if (version_compare(PHP_VERSION, CS_CHATBOT_MIN_PHP, '<')) {
        add_action('admin_notices', function() {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                sprintf(
                    esc_html__('CS Chatbot requires PHP %1$s or higher. You are running PHP %2$s.', 'cs-chatbot'),
                    CS_CHATBOT_MIN_PHP,
                    PHP_VERSION
                )
            );
        });
        return false;
    }
    
    // Check WordPress version
    if (version_compare($wp_version, CS_CHATBOT_MIN_WP, '<')) {
        add_action('admin_notices', function() use ($wp_version) {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                sprintf(
                    esc_html__('CS Chatbot requires WordPress %1$s or higher. You are running WordPress %2$s.', 'cs-chatbot'),
                    CS_CHATBOT_MIN_WP,
                    $wp_version
                )
            );
        });
        return false;
    }
    
    return true;
}

/**
 * Load the complete plugin
 */
function cs_chatbot_load_plugin() {
    // Load the complete plugin file
    require_once CS_CHATBOT_PATH . 'cs-chatbot-complete.php';
}

/**
 * Plugin activation hook
 */
function cs_chatbot_activate() {
    if (!cs_chatbot_check_requirements()) {
        return;
    }
    
    // Load install script
    require_once CS_CHATBOT_PATH . 'install.php';
    cs_chatbot_install();
}

/**
 * Plugin deactivation hook
 */
function cs_chatbot_deactivate() {
    // Clear scheduled events
    wp_clear_scheduled_hook('cs_chatbot_daily_analytics');
    wp_clear_scheduled_hook('cs_chatbot_weekly_report');
    wp_clear_scheduled_hook('cs_chatbot_cleanup_sessions');
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'cs_chatbot_activate');
register_deactivation_hook(__FILE__, 'cs_chatbot_deactivate');

// Initialize the plugin
if (cs_chatbot_check_requirements()) {
    add_action('plugins_loaded', 'cs_chatbot_load_plugin', 10);
}