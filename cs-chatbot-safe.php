<?php
/**
 * Plugin Name: CS Chatbot Professional - Safe Version
 * Plugin URI: https://github.com/khiwniti/cs-chatbot-professional
 * Description: Error-proof AI chatbot with comprehensive error handling and WordPress Playground compatibility.
 * Version: 2.1.0
 * Author: CS Professional
 * License: GPL v2 or later
 * Text Domain: cs-chatbot
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Check PHP version before doing anything
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('CS Chatbot requires PHP 7.4 or higher. Current version: ', 'cs-chatbot') . PHP_VERSION;
        echo '</p></div>';
    });
    return;
}

// Check if WordPress is loaded
if (!function_exists('add_action')) {
    exit('WordPress not detected.');
}

// Plugin constants with safety checks
if (!defined('CS_CHATBOT_VERSION')) {
    define('CS_CHATBOT_VERSION', '2.1.0');
}
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
 * Safe CS Chatbot Plugin Class with comprehensive error handling
 */
class CSChatbotSafe {
    
    private static $instance = null;
    private $options = array();
    private $errors = array();
    private $is_initialized = false;
    
    /**
     * Singleton pattern with error handling
     */
    public static function getInstance() {
        if (self::$instance === null) {
            try {
                self::$instance = new self();
            } catch (Exception $e) {
                error_log('CS Chatbot initialization error: ' . $e->getMessage());
                return null;
            }
        }
        return self::$instance;
    }
    
    /**
     * Constructor with comprehensive error handling
     */
    private function __construct() {
        try {
            $this->check_requirements();
            $this->load_options();
            $this->init_hooks();
            $this->is_initialized = true;
        } catch (Exception $e) {
            $this->add_error('Initialization failed: ' . $e->getMessage());
            add_action('admin_notices', array($this, 'show_admin_errors'));
        }
    }
    
    /**
     * Check all requirements before initialization
     */
    private function check_requirements() {
        $errors = array();
        
        // Check WordPress version
        global $wp_version;
        if (version_compare($wp_version, '5.0', '<')) {
            $errors[] = 'WordPress 5.0 or higher required. Current: ' . $wp_version;
        }
        
        // Check required functions
        $required_functions = array('wp_enqueue_script', 'wp_enqueue_style', 'add_action', 'register_rest_route');
        foreach ($required_functions as $func) {
            if (!function_exists($func)) {
                $errors[] = 'Required function missing: ' . $func;
            }
        }
        
        // Check if we can write to options
        if (!function_exists('get_option') || !function_exists('update_option')) {
            $errors[] = 'WordPress options system not available';
        }
        
        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }
    }
    
    /**
     * Initialize hooks with error handling
     */
    private function init_hooks() {
        if (!$this->is_initialized) {
            return;
        }
        
        try {
            // Core hooks
            add_action('init', array($this, 'safe_init'));
            add_action('wp_enqueue_scripts', array($this, 'safe_enqueue_scripts'));
            add_action('wp_footer', array($this, 'safe_render_chatbot'));
            add_action('rest_api_init', array($this, 'safe_register_rest_routes'));
            add_action('admin_menu', array($this, 'safe_add_admin_menu'));
            add_action('admin_init', array($this, 'safe_register_settings'));
            
            // Activation/Deactivation hooks with error handling
            register_activation_hook(__FILE__, array($this, 'safe_activate'));
            register_deactivation_hook(__FILE__, array($this, 'safe_deactivate'));
            
            // Error handling
            add_action('admin_notices', array($this, 'show_admin_errors'));
            
        } catch (Exception $e) {
            $this->add_error('Hook initialization failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Safe initialization
     */
    public function safe_init() {
        try {
            // Load text domain safely
            if (function_exists('load_plugin_textdomain')) {
                load_plugin_textdomain('cs-chatbot', false, dirname(plugin_basename(__FILE__)) . '/languages');
            }
        } catch (Exception $e) {
            $this->add_error('Init failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Load options with defaults and validation
     */
    private function load_options() {
        try {
            $defaults = array(
                'enabled' => false, // Default to disabled for safety
                'openrouter_api_key' => '',
                'model' => 'deepseek/deepseek-r1-qwen3-8b',
                'theme' => 'modern',
                'position' => 'bottom-right',
                'auto_open' => false,
                'auto_open_delay' => 3000,
                'welcome_message' => 'Hello! How can I help you today?',
                'placeholder_text' => 'Type your message...',
                'voice_enabled' => false, // Default to disabled for compatibility
                'suggestions_enabled' => true,
                'rating_enabled' => true,
                'debug_mode' => false
            );
            
            if (function_exists('get_option') && function_exists('wp_parse_args')) {
                $saved_options = get_option('cs_chatbot_options', array());
                if (!is_array($saved_options)) {
                    $saved_options = array();
                }
                $this->options = wp_parse_args($saved_options, $defaults);
            } else {
                $this->options = $defaults;
            }
            
            // Validate options
            $this->validate_options();
            
        } catch (Exception $e) {
            $this->add_error('Options loading failed: ' . $e->getMessage());
            $this->options = $defaults;
        }
    }
    
    /**
     * Validate all options
     */
    private function validate_options() {
        // Sanitize theme
        $valid_themes = array('modern', 'dark', 'minimal', 'classic', 'gradient', 'neon', 'professional');
        if (!in_array($this->options['theme'], $valid_themes)) {
            $this->options['theme'] = 'modern';
        }
        
        // Sanitize position
        $valid_positions = array('bottom-right', 'bottom-left', 'top-right', 'top-left');
        if (!in_array($this->options['position'], $valid_positions)) {
            $this->options['position'] = 'bottom-right';
        }
        
        // Validate delay
        $this->options['auto_open_delay'] = max(1000, min(30000, intval($this->options['auto_open_delay'])));
        
        // Sanitize text fields
        if (function_exists('sanitize_text_field')) {
            $this->options['welcome_message'] = sanitize_text_field($this->options['welcome_message']);
            $this->options['placeholder_text'] = sanitize_text_field($this->options['placeholder_text']);
        }
    }
    
    /**
     * Safe script enqueuing with fallbacks
     */
    public function safe_enqueue_scripts() {
        try {
            if (!$this->options['enabled'] || !function_exists('wp_enqueue_style')) {
                return;
            }
            
            // Check if files exist before enqueuing
            $css_file = CS_CHATBOT_PATH . 'assets/css/cs-chatbot-simple.css';
            $js_file = CS_CHATBOT_PATH . 'assets/js/cs-chatbot-simple.js';
            
            if (file_exists($css_file)) {
                wp_enqueue_style(
                    'cs-chatbot-frontend',
                    CS_CHATBOT_URL . 'assets/css/cs-chatbot-simple.css',
                    array(),
                    CS_CHATBOT_VERSION
                );
            } else {
                // Inline minimal CSS as fallback
                add_action('wp_head', array($this, 'inline_fallback_css'));
            }
            
            if (file_exists($js_file) && function_exists('wp_enqueue_script')) {
                wp_enqueue_script(
                    'cs-chatbot-frontend',
                    CS_CHATBOT_URL . 'assets/js/cs-chatbot-simple.js',
                    array('jquery'),
                    CS_CHATBOT_VERSION,
                    true
                );
                
                // Localize script safely
                if (function_exists('wp_localize_script')) {
                    $this->localize_script();
                }
            } else {
                // Inline minimal JS as fallback
                add_action('wp_footer', array($this, 'inline_fallback_js'));
            }
            
        } catch (Exception $e) {
            $this->add_error('Script enqueuing failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Safe script localization
     */
    private function localize_script() {
        try {
            $localize_data = array(
                'ajaxUrl' => function_exists('admin_url') ? admin_url('admin-ajax.php') : '',
                'restUrl' => function_exists('rest_url') ? rest_url('chatbot/v1/') : '',
                'nonce' => function_exists('wp_create_nonce') ? wp_create_nonce('cs_chatbot_nonce') : '',
                'options' => $this->options,
                'strings' => array(
                    'welcome' => $this->options['welcome_message'],
                    'placeholder' => $this->options['placeholder_text'],
                    'send' => __('Send', 'cs-chatbot'),
                    'listening' => __('Listening...', 'cs-chatbot'),
                    'typing' => __('Typing...', 'cs-chatbot'),
                    'error' => __('Sorry, something went wrong. Please try again.', 'cs-chatbot'),
                    'copy' => __('Copy', 'cs-chatbot'),
                    'copied' => __('Copied!', 'cs-chatbot'),
                    'like' => __('Like', 'cs-chatbot'),
                    'dislike' => __('Dislike', 'cs-chatbot')
                )
            );
            
            wp_localize_script('cs-chatbot-frontend', 'csChatbot', $localize_data);
            
        } catch (Exception $e) {
            $this->add_error('Script localization failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Fallback inline CSS
     */
    public function inline_fallback_css() {
        echo '<style>
        #cs-chatbot-container{position:fixed;bottom:20px;right:20px;z-index:999999;font-family:sans-serif}
        .cs-chatbot-toggle{width:60px;height:60px;border-radius:50%;border:none;background:#007cba;color:white;font-size:24px;cursor:pointer}
        .cs-chatbot-widget{width:350px;height:500px;background:white;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.1);display:none;flex-direction:column}
        .cs-chatbot-widget.open{display:flex}
        .cs-chatbot-header{background:#007cba;color:white;padding:15px 20px;display:flex;justify-content:space-between}
        .cs-chatbot-messages{flex:1;padding:20px;overflow-y:auto;background:#f8f9fa}
        .cs-chatbot-input-area{padding:20px;background:white}
        #cs-chatbot-input{width:100%;border:1px solid #ddd;border-radius:20px;padding:12px 16px}
        </style>';
    }
    
    /**
     * Fallback inline JavaScript
     */
    public function inline_fallback_js() {
        echo '<script>
        jQuery(document).ready(function($) {
            $("#cs-chatbot-toggle").click(function() {
                $(".cs-chatbot-widget").toggleClass("open");
            });
            $(".cs-chatbot-close").click(function() {
                $(".cs-chatbot-widget").removeClass("open");
            });
        });
        </script>';
    }
    
    /**
     * Safe chatbot rendering
     */
    public function safe_render_chatbot() {
        try {
            if (!$this->options['enabled']) {
                return;
            }
            
            $theme = sanitize_text_field($this->options['theme']);
            $position = sanitize_text_field($this->options['position']);
            
            echo '<div id="cs-chatbot-container" class="cs-chatbot-theme-' . esc_attr($theme) . ' cs-chatbot-position-' . esc_attr($position) . '">';
            echo '<div id="cs-chatbot-widget" class="cs-chatbot-widget">';
            echo '<div class="cs-chatbot-header">';
            echo '<h3>' . esc_html__('Chat Support', 'cs-chatbot') . '</h3>';
            echo '<button class="cs-chatbot-close" aria-label="' . esc_attr__('Close chat', 'cs-chatbot') . '">&times;</button>';
            echo '</div>';
            echo '<div class="cs-chatbot-messages" id="cs-chatbot-messages">';
            echo '<div class="cs-chatbot-message bot">';
            echo '<div class="cs-chatbot-message-content">' . esc_html($this->options['welcome_message']) . '</div>';
            echo '</div>';
            echo '</div>';
            echo '<div class="cs-chatbot-input-area">';
            echo '<div class="cs-chatbot-suggestions" id="cs-chatbot-suggestions"></div>';
            echo '<div class="cs-chatbot-input-wrapper">';
            echo '<input type="text" id="cs-chatbot-input" placeholder="' . esc_attr($this->options['placeholder_text']) . '" />';
            if ($this->options['voice_enabled'] && $this->is_voice_supported()) {
                echo '<button id="cs-chatbot-voice" class="cs-chatbot-voice-btn" aria-label="' . esc_attr__('Voice input', 'cs-chatbot') . '">🎤</button>';
            }
            echo '<button id="cs-chatbot-send" class="cs-chatbot-send-btn" aria-label="' . esc_attr__('Send message', 'cs-chatbot') . '">➤</button>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '<button id="cs-chatbot-toggle" class="cs-chatbot-toggle" aria-label="' . esc_attr__('Open chat', 'cs-chatbot') . '">💬</button>';
            echo '</div>';
            
        } catch (Exception $e) {
            $this->add_error('Chatbot rendering failed: ' . $e->getMessage());
            // Render minimal fallback
            echo '<div id="cs-chatbot-container"><button id="cs-chatbot-toggle" class="cs-chatbot-toggle">💬</button></div>';
        }
    }
    
    /**
     * Check if voice is supported
     */
    private function is_voice_supported() {
        // Only enable voice on HTTPS or localhost
        $is_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                     (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);
        return $is_secure;
    }
    
    /**
     * Safe REST API registration
     */
    public function safe_register_rest_routes() {
        try {
            if (!function_exists('register_rest_route')) {
                return;
            }
            
            // Chat endpoint
            register_rest_route('chatbot/v1', '/chat', array(
                'methods' => 'POST',
                'callback' => array($this, 'safe_handle_chat_request'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'message' => array(
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => array($this, 'validate_message')
                    ),
                    'language' => array(
                        'required' => false,
                        'type' => 'string',
                        'default' => 'en',
                        'sanitize_callback' => 'sanitize_text_field'
                    )
                )
            ));
            
            // Status endpoint
            register_rest_route('chatbot/v1', '/status', array(
                'methods' => 'GET',
                'callback' => array($this, 'safe_get_status'),
                'permission_callback' => '__return_true'
            ));
            
            // Feedback endpoint
            register_rest_route('chatbot/v1', '/feedback', array(
                'methods' => 'POST',
                'callback' => array($this, 'safe_handle_feedback'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'rating' => array(
                        'required' => true,
                        'type' => 'string',
                        'enum' => array('like', 'dislike')
                    )
                )
            ));
            
            // Themes endpoint
            register_rest_route('chatbot/v1', '/themes', array(
                'methods' => 'GET',
                'callback' => array($this, 'safe_get_themes'),
                'permission_callback' => '__return_true'
            ));
            
        } catch (Exception $e) {
            $this->add_error('REST API registration failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate message input
     */
    public function validate_message($param, $request, $key) {
        if (empty($param) || strlen($param) > 1000) {
            return false;
        }
        return true;
    }
    
    /**
     * Safe chat request handler
     */
    public function safe_handle_chat_request($request) {
        try {
            $message = $request->get_param('message');
            $language = $request->get_param('language');
            
            if (empty($this->options['openrouter_api_key'])) {
                return new WP_Error('no_api_key', 'API key not configured', array('status' => 400));
            }
            
            $response = $this->safe_call_openrouter_api($message, $language);
            
            if (is_wp_error($response)) {
                return $response;
            }
            
            return rest_ensure_response(array(
                'success' => true,
                'response' => $response,
                'timestamp' => current_time('mysql'),
                'language' => $language
            ));
            
        } catch (Exception $e) {
            return new WP_Error('chat_error', 'Chat processing failed: ' . $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Safe OpenRouter API call with comprehensive error handling
     */
    private function safe_call_openrouter_api($message, $language = 'en') {
        try {
            if (!function_exists('wp_remote_post')) {
                return new WP_Error('no_http', 'HTTP functions not available', array('status' => 500));
            }
            
            $api_key = sanitize_text_field($this->options['openrouter_api_key']);
            $model = sanitize_text_field($this->options['model']);
            
            if (empty($api_key)) {
                return new WP_Error('no_api_key', 'API key is empty', array('status' => 400));
            }
            
            $system_prompt = ($language === 'th') 
                ? 'คุณเป็นผู้ช่วยที่เป็นมิตรและมีประโยชน์ ตอบคำถามด้วยภาษาไทยที่เข้าใจง่าย'
                : 'You are a helpful and friendly assistant. Provide clear, concise, and helpful responses.';
            
            $body = array(
                'model' => $model,
                'messages' => array(
                    array('role' => 'system', 'content' => $system_prompt),
                    array('role' => 'user', 'content' => $message)
                ),
                'max_tokens' => 500,
                'temperature' => 0.7
            );
            
            $response = wp_remote_post('https://openrouter.ai/api/v1/chat/completions', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => function_exists('home_url') ? home_url() : '',
                    'X-Title' => function_exists('get_bloginfo') ? get_bloginfo('name') : 'WordPress Site'
                ),
                'body' => wp_json_encode($body),
                'timeout' => 30,
                'sslverify' => true
            ));
            
            if (is_wp_error($response)) {
                return new WP_Error('api_request_failed', 'API request failed: ' . $response->get_error_message(), array('status' => 500));
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                return new WP_Error('api_error', 'API returned error code: ' . $response_code, array('status' => 500));
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new WP_Error('json_error', 'Invalid JSON response', array('status' => 500));
            }
            
            if (!isset($data['choices'][0]['message']['content'])) {
                return new WP_Error('invalid_response', 'Invalid API response structure', array('status' => 500));
            }
            
            return sanitize_text_field($data['choices'][0]['message']['content']);
            
        } catch (Exception $e) {
            return new WP_Error('api_exception', 'API call exception: ' . $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Safe status endpoint
     */
    public function safe_get_status($request) {
        try {
            return rest_ensure_response(array(
                'status' => 'active',
                'version' => CS_CHATBOT_VERSION,
                'features' => array(
                    'voice_input' => $this->options['voice_enabled'] && $this->is_voice_supported(),
                    'suggestions' => $this->options['suggestions_enabled'],
                    'rating' => $this->options['rating_enabled'],
                    'themes' => array('modern', 'dark', 'minimal', 'classic', 'gradient', 'neon', 'professional')
                ),
                'api_configured' => !empty($this->options['openrouter_api_key']),
                'errors' => $this->options['debug_mode'] ? $this->errors : array()
            ));
        } catch (Exception $e) {
            return new WP_Error('status_error', 'Status check failed', array('status' => 500));
        }
    }
    
    /**
     * Safe feedback handler
     */
    public function safe_handle_feedback($request) {
        try {
            $rating = $request->get_param('rating');
            
            if (!function_exists('get_option') || !function_exists('update_option')) {
                return new WP_Error('no_options', 'Options system not available', array('status' => 500));
            }
            
            $feedback = get_option('cs_chatbot_feedback', array());
            if (!is_array($feedback)) {
                $feedback = array();
            }
            
            $feedback[] = array(
                'rating' => sanitize_text_field($rating),
                'timestamp' => current_time('mysql'),
                'ip' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'unknown'
            );
            
            // Limit feedback storage to prevent database bloat
            if (count($feedback) > 1000) {
                $feedback = array_slice($feedback, -1000);
            }
            
            update_option('cs_chatbot_feedback', $feedback);
            
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Feedback recorded'
            ));
            
        } catch (Exception $e) {
            return new WP_Error('feedback_error', 'Feedback processing failed', array('status' => 500));
        }
    }
    
    /**
     * Safe themes endpoint
     */
    public function safe_get_themes($request) {
        try {
            return rest_ensure_response(array(
                'themes' => array(
                    'modern' => array('name' => 'Modern', 'description' => 'Clean, contemporary design'),
                    'dark' => array('name' => 'Dark', 'description' => 'Dark theme for modern websites'),
                    'minimal' => array('name' => 'Minimal', 'description' => 'Simple, lightweight appearance'),
                    'classic' => array('name' => 'Classic', 'description' => 'Traditional chat interface'),
                    'gradient' => array('name' => 'Gradient', 'description' => 'Beautiful gradient background'),
                    'neon' => array('name' => 'Neon', 'description' => 'Futuristic neon theme'),
                    'professional' => array('name' => 'Professional', 'description' => 'Business-focused design')
                )
            ));
        } catch (Exception $e) {
            return new WP_Error('themes_error', 'Themes retrieval failed', array('status' => 500));
        }
    }
    
    /**
     * Safe admin menu
     */
    public function safe_add_admin_menu() {
        try {
            if (!function_exists('add_options_page') || !current_user_can('manage_options')) {
                return;
            }
            
            add_options_page(
                __('CS Chatbot Settings', 'cs-chatbot'),
                __('CS Chatbot', 'cs-chatbot'),
                'manage_options',
                'cs-chatbot',
                array($this, 'safe_admin_page')
            );
        } catch (Exception $e) {
            $this->add_error('Admin menu creation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Safe settings registration
     */
    public function safe_register_settings() {
        try {
            if (!function_exists('register_setting')) {
                return;
            }
            
            register_setting('cs_chatbot_options', 'cs_chatbot_options', array(
                'sanitize_callback' => array($this, 'safe_sanitize_options')
            ));
        } catch (Exception $e) {
            $this->add_error('Settings registration failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Safe options sanitization
     */
    public function safe_sanitize_options($input) {
        try {
            if (!is_array($input)) {
                return $this->options;
            }
            
            $sanitized = array();
            
            $sanitized['enabled'] = !empty($input['enabled']);
            $sanitized['openrouter_api_key'] = sanitize_text_field($input['openrouter_api_key'] ?? '');
            $sanitized['model'] = sanitize_text_field($input['model'] ?? 'deepseek/deepseek-r1-qwen3-8b');
            $sanitized['theme'] = sanitize_text_field($input['theme'] ?? 'modern');
            $sanitized['position'] = sanitize_text_field($input['position'] ?? 'bottom-right');
            $sanitized['auto_open'] = !empty($input['auto_open']);
            $sanitized['auto_open_delay'] = max(1000, min(30000, absint($input['auto_open_delay'] ?? 3000)));
            $sanitized['welcome_message'] = sanitize_textarea_field($input['welcome_message'] ?? 'Hello! How can I help you today?');
            $sanitized['placeholder_text'] = sanitize_text_field($input['placeholder_text'] ?? 'Type your message...');
            $sanitized['voice_enabled'] = !empty($input['voice_enabled']);
            $sanitized['suggestions_enabled'] = !empty($input['suggestions_enabled']);
            $sanitized['rating_enabled'] = !empty($input['rating_enabled']);
            $sanitized['debug_mode'] = !empty($input['debug_mode']);
            
            return $sanitized;
            
        } catch (Exception $e) {
            $this->add_error('Options sanitization failed: ' . $e->getMessage());
            return $this->options;
        }
    }
    
    /**
     * Safe admin page
     */
    public function safe_admin_page() {
        try {
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }
            
            // Handle form submission
            if (isset($_POST['submit']) && check_admin_referer('cs_chatbot_settings')) {
                $options = $_POST['cs_chatbot_options'] ?? array();
                $sanitized = $this->safe_sanitize_options($options);
                update_option('cs_chatbot_options', $sanitized);
                $this->load_options();
                echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'cs-chatbot') . '</p></div>';
            }
            
            $this->render_admin_page();
            
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>Admin page error: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }
    
    /**
     * Render admin page HTML
     */
    private function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('CS Chatbot Professional Settings', 'cs-chatbot'); ?></h1>
            
            <?php if (!empty($this->errors)): ?>
                <div class="notice notice-error">
                    <p><strong><?php _e('Plugin Errors:', 'cs-chatbot'); ?></strong></p>
                    <ul>
                        <?php foreach ($this->errors as $error): ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('cs_chatbot_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Chatbot', 'cs-chatbot'); ?></th>
                        <td>
                            <input type="checkbox" name="cs_chatbot_options[enabled]" value="1" <?php checked($this->options['enabled']); ?> />
                            <p class="description"><?php _e('Enable or disable the chatbot on your website.', 'cs-chatbot'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('OpenRouter API Key', 'cs-chatbot'); ?></th>
                        <td>
                            <input type="password" name="cs_chatbot_options[openrouter_api_key]" value="<?php echo esc_attr($this->options['openrouter_api_key']); ?>" class="regular-text" />
                            <p class="description"><?php _e('Get your API key from <a href="https://openrouter.ai" target="_blank">OpenRouter.ai</a>', 'cs-chatbot'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Theme', 'cs-chatbot'); ?></th>
                        <td>
                            <select name="cs_chatbot_options[theme]">
                                <option value="modern" <?php selected($this->options['theme'], 'modern'); ?>><?php _e('Modern', 'cs-chatbot'); ?></option>
                                <option value="dark" <?php selected($this->options['theme'], 'dark'); ?>><?php _e('Dark', 'cs-chatbot'); ?></option>
                                <option value="minimal" <?php selected($this->options['theme'], 'minimal'); ?>><?php _e('Minimal', 'cs-chatbot'); ?></option>
                                <option value="classic" <?php selected($this->options['theme'], 'classic'); ?>><?php _e('Classic', 'cs-chatbot'); ?></option>
                                <option value="gradient" <?php selected($this->options['theme'], 'gradient'); ?>><?php _e('Gradient', 'cs-chatbot'); ?></option>
                                <option value="neon" <?php selected($this->options['theme'], 'neon'); ?>><?php _e('Neon', 'cs-chatbot'); ?></option>
                                <option value="professional" <?php selected($this->options['theme'], 'professional'); ?>><?php _e('Professional', 'cs-chatbot'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Welcome Message', 'cs-chatbot'); ?></th>
                        <td>
                            <textarea name="cs_chatbot_options[welcome_message]" rows="3" class="large-text"><?php echo esc_textarea($this->options['welcome_message']); ?></textarea>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Features', 'cs-chatbot'); ?></th>
                        <td>
                            <label><input type="checkbox" name="cs_chatbot_options[voice_enabled]" value="1" <?php checked($this->options['voice_enabled']); ?> /> <?php _e('Voice Input (HTTPS required)', 'cs-chatbot'); ?></label><br>
                            <label><input type="checkbox" name="cs_chatbot_options[suggestions_enabled]" value="1" <?php checked($this->options['suggestions_enabled']); ?> /> <?php _e('Smart Suggestions', 'cs-chatbot'); ?></label><br>
                            <label><input type="checkbox" name="cs_chatbot_options[rating_enabled]" value="1" <?php checked($this->options['rating_enabled']); ?> /> <?php _e('Message Rating', 'cs-chatbot'); ?></label><br>
                            <label><input type="checkbox" name="cs_chatbot_options[debug_mode]" value="1" <?php checked($this->options['debug_mode']); ?> /> <?php _e('Debug Mode', 'cs-chatbot'); ?></label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <h2><?php _e('System Status', 'cs-chatbot'); ?></h2>
            <table class="widefat">
                <tr>
                    <td><strong><?php _e('Plugin Version:', 'cs-chatbot'); ?></strong></td>
                    <td><?php echo esc_html(CS_CHATBOT_VERSION); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('WordPress Version:', 'cs-chatbot'); ?></strong></td>
                    <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('PHP Version:', 'cs-chatbot'); ?></strong></td>
                    <td><?php echo esc_html(PHP_VERSION); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('REST API Base:', 'cs-chatbot'); ?></strong></td>
                    <td><code><?php echo esc_html(rest_url('chatbot/v1/')); ?></code></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Voice Support:', 'cs-chatbot'); ?></strong></td>
                    <td><?php echo $this->is_voice_supported() ? '✅ Available' : '❌ Requires HTTPS'; ?></td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Safe activation
     */
    public function safe_activate() {
        try {
            // Create default options if they don't exist
            if (!get_option('cs_chatbot_options')) {
                $this->load_options();
                update_option('cs_chatbot_options', $this->options);
            }
            
            // Flush rewrite rules for REST API
            if (function_exists('flush_rewrite_rules')) {
                flush_rewrite_rules();
            }
            
        } catch (Exception $e) {
            $this->add_error('Activation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Safe deactivation
     */
    public function safe_deactivate() {
        try {
            // Flush rewrite rules
            if (function_exists('flush_rewrite_rules')) {
                flush_rewrite_rules();
            }
        } catch (Exception $e) {
            error_log('CS Chatbot deactivation error: ' . $e->getMessage());
        }
    }
    
    /**
     * Add error to error list
     */
    private function add_error($message) {
        $this->errors[] = $message;
        if (function_exists('error_log')) {
            error_log('CS Chatbot Error: ' . $message);
        }
    }
    
    /**
     * Show admin errors
     */
    public function show_admin_errors() {
        if (!empty($this->errors) && current_user_can('manage_options')) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>CS Chatbot Errors:</strong><br>';
            foreach ($this->errors as $error) {
                echo esc_html($error) . '<br>';
            }
            echo '</p></div>';
        }
    }
}

/**
 * Safe plugin initialization
 */
function cs_chatbot_safe_init() {
    try {
        return CSChatbotSafe::getInstance();
    } catch (Exception $e) {
        error_log('CS Chatbot initialization failed: ' . $e->getMessage());
        add_action('admin_notices', function() use ($e) {
            echo '<div class="notice notice-error"><p>';
            echo 'CS Chatbot failed to initialize: ' . esc_html($e->getMessage());
            echo '</p></div>';
        });
        return null;
    }
}

// Initialize the plugin safely
add_action('plugins_loaded', 'cs_chatbot_safe_init');

// Add settings link to plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    try {
        if (function_exists('admin_url')) {
            $settings_link = '<a href="' . admin_url('options-general.php?page=cs-chatbot') . '">' . __('Settings', 'cs-chatbot') . '</a>';
            array_unshift($links, $settings_link);
        }
    } catch (Exception $e) {
        error_log('CS Chatbot settings link error: ' . $e->getMessage());
    }
    return $links;
});