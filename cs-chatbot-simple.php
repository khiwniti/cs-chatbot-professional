<?php
/**
 * Plugin Name: CS Chatbot Professional - Simple
 * Plugin URI: https://github.com/khiwniti/cs-chatbot-professional
 * Description: Enhanced AI-powered chatbot with modern themes, voice input, and comprehensive REST API following /chatbot pattern. WordPress Playground compatible version.
 * Version: 2.0.0
 * Author: CS Professional
 * License: GPL v2 or later
 * Text Domain: cs-chatbot
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Plugin constants
define('CS_CHATBOT_VERSION', '2.0.0');
define('CS_CHATBOT_FILE', __FILE__);
define('CS_CHATBOT_PATH', plugin_dir_path(__FILE__));
define('CS_CHATBOT_URL', plugin_dir_url(__FILE__));
define('CS_CHATBOT_BASENAME', plugin_basename(__FILE__));

/**
 * Main CS Chatbot Plugin Class - Simplified for WordPress Playground
 */
class CSChatbotSimple {
    
    private static $instance = null;
    private $options = array();
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
        $this->load_options();
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'render_chatbot'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Initialize plugin
        load_plugin_textdomain('cs-chatbot', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    private function load_options() {
        $defaults = array(
            'enabled' => true,
            'openrouter_api_key' => '',
            'model' => 'deepseek/deepseek-r1-qwen3-8b',
            'theme' => 'modern',
            'position' => 'bottom-right',
            'auto_open' => false,
            'auto_open_delay' => 3000,
            'welcome_message' => 'Hello! How can I help you today?',
            'placeholder_text' => 'Type your message...',
            'voice_enabled' => true,
            'suggestions_enabled' => true,
            'rating_enabled' => true
        );
        
        $this->options = wp_parse_args(get_option('cs_chatbot_options', array()), $defaults);
    }
    
    public function enqueue_scripts() {
        if (!$this->options['enabled']) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'cs-chatbot-frontend',
            CS_CHATBOT_URL . 'assets/css/cs-chatbot-simple.css',
            array(),
            CS_CHATBOT_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'cs-chatbot-frontend',
            CS_CHATBOT_URL . 'assets/js/cs-chatbot-simple.js',
            array('jquery'),
            CS_CHATBOT_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('cs-chatbot-frontend', 'csChatbot', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('chatbot/v1/'),
            'nonce' => wp_create_nonce('cs_chatbot_nonce'),
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
        ));
    }
    
    public function render_chatbot() {
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
        echo '<div class="cs-chatbot-messages" id="cs-chatbot-messages"></div>';
        echo '<div class="cs-chatbot-input-area">';
        echo '<div class="cs-chatbot-suggestions" id="cs-chatbot-suggestions"></div>';
        echo '<div class="cs-chatbot-input-wrapper">';
        echo '<input type="text" id="cs-chatbot-input" placeholder="' . esc_attr($this->options['placeholder_text']) . '" />';
        if ($this->options['voice_enabled']) {
            echo '<button id="cs-chatbot-voice" class="cs-chatbot-voice-btn" aria-label="' . esc_attr__('Voice input', 'cs-chatbot') . '">🎤</button>';
        }
        echo '<button id="cs-chatbot-send" class="cs-chatbot-send-btn" aria-label="' . esc_attr__('Send message', 'cs-chatbot') . '">➤</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '<button id="cs-chatbot-toggle" class="cs-chatbot-toggle" aria-label="' . esc_attr__('Open chat', 'cs-chatbot') . '">💬</button>';
        echo '</div>';
    }
    
    public function register_rest_routes() {
        // Chat endpoint
        register_rest_route('chatbot/v1', '/chat', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_chat_request'),
            'permission_callback' => '__return_true',
            'args' => array(
                'message' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
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
            'callback' => array($this, 'get_status'),
            'permission_callback' => '__return_true'
        ));
        
        // Feedback endpoint
        register_rest_route('chatbot/v1', '/feedback', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_feedback'),
            'permission_callback' => '__return_true',
            'args' => array(
                'rating' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('like', 'dislike')
                ),
                'message_id' => array(
                    'required' => false,
                    'type' => 'string'
                )
            )
        ));
        
        // Themes endpoint
        register_rest_route('chatbot/v1', '/themes', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_themes'),
            'permission_callback' => '__return_true'
        ));
    }
    
    public function handle_chat_request($request) {
        $message = $request->get_param('message');
        $language = $request->get_param('language');
        
        if (empty($this->options['openrouter_api_key'])) {
            return new WP_Error('no_api_key', 'OpenRouter API key not configured', array('status' => 500));
        }
        
        try {
            $response = $this->call_openrouter_api($message, $language);
            
            return rest_ensure_response(array(
                'success' => true,
                'response' => $response,
                'timestamp' => current_time('mysql'),
                'language' => $language
            ));
            
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    private function call_openrouter_api($message, $language = 'en') {
        $api_key = $this->options['openrouter_api_key'];
        $model = $this->options['model'];
        
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
                'HTTP-Referer' => home_url(),
                'X-Title' => get_bloginfo('name')
            ),
            'body' => wp_json_encode($body),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response');
        }
        
        return $data['choices'][0]['message']['content'];
    }
    
    public function get_status($request) {
        return rest_ensure_response(array(
            'status' => 'active',
            'version' => CS_CHATBOT_VERSION,
            'features' => array(
                'voice_input' => $this->options['voice_enabled'],
                'suggestions' => $this->options['suggestions_enabled'],
                'rating' => $this->options['rating_enabled'],
                'themes' => array('modern', 'dark', 'minimal', 'classic', 'gradient', 'neon', 'professional')
            ),
            'api_configured' => !empty($this->options['openrouter_api_key'])
        ));
    }
    
    public function handle_feedback($request) {
        $rating = $request->get_param('rating');
        $message_id = $request->get_param('message_id');
        
        // Store feedback (simplified for WordPress Playground)
        $feedback = get_option('cs_chatbot_feedback', array());
        $feedback[] = array(
            'rating' => $rating,
            'message_id' => $message_id,
            'timestamp' => current_time('mysql'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        );
        update_option('cs_chatbot_feedback', $feedback);
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Feedback recorded'
        ));
    }
    
    public function get_themes($request) {
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
    }
    
    public function add_admin_menu() {
        add_options_page(
            __('CS Chatbot Settings', 'cs-chatbot'),
            __('CS Chatbot', 'cs-chatbot'),
            'manage_options',
            'cs-chatbot',
            array($this, 'admin_page')
        );
    }
    
    public function register_settings() {
        register_setting('cs_chatbot_options', 'cs_chatbot_options', array($this, 'sanitize_options'));
    }
    
    public function sanitize_options($input) {
        $sanitized = array();
        
        $sanitized['enabled'] = !empty($input['enabled']);
        $sanitized['openrouter_api_key'] = sanitize_text_field($input['openrouter_api_key'] ?? '');
        $sanitized['model'] = sanitize_text_field($input['model'] ?? 'deepseek/deepseek-r1-qwen3-8b');
        $sanitized['theme'] = sanitize_text_field($input['theme'] ?? 'modern');
        $sanitized['position'] = sanitize_text_field($input['position'] ?? 'bottom-right');
        $sanitized['auto_open'] = !empty($input['auto_open']);
        $sanitized['auto_open_delay'] = absint($input['auto_open_delay'] ?? 3000);
        $sanitized['welcome_message'] = sanitize_textarea_field($input['welcome_message'] ?? 'Hello! How can I help you today?');
        $sanitized['placeholder_text'] = sanitize_text_field($input['placeholder_text'] ?? 'Type your message...');
        $sanitized['voice_enabled'] = !empty($input['voice_enabled']);
        $sanitized['suggestions_enabled'] = !empty($input['suggestions_enabled']);
        $sanitized['rating_enabled'] = !empty($input['rating_enabled']);
        
        return $sanitized;
    }
    
    public function admin_page() {
        if (isset($_POST['submit'])) {
            $options = $_POST['cs_chatbot_options'] ?? array();
            update_option('cs_chatbot_options', $this->sanitize_options($options));
            $this->load_options();
            echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'cs-chatbot') . '</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('CS Chatbot Professional Settings', 'cs-chatbot'); ?></h1>
            
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
                            <label><input type="checkbox" name="cs_chatbot_options[voice_enabled]" value="1" <?php checked($this->options['voice_enabled']); ?> /> <?php _e('Voice Input', 'cs-chatbot'); ?></label><br>
                            <label><input type="checkbox" name="cs_chatbot_options[suggestions_enabled]" value="1" <?php checked($this->options['suggestions_enabled']); ?> /> <?php _e('Smart Suggestions', 'cs-chatbot'); ?></label><br>
                            <label><input type="checkbox" name="cs_chatbot_options[rating_enabled]" value="1" <?php checked($this->options['rating_enabled']); ?> /> <?php _e('Message Rating', 'cs-chatbot'); ?></label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <h2><?php _e('API Status', 'cs-chatbot'); ?></h2>
            <p>
                <strong><?php _e('REST API Base URL:', 'cs-chatbot'); ?></strong> 
                <code><?php echo rest_url('chatbot/v1/'); ?></code>
            </p>
            
            <h3><?php _e('Available Endpoints:', 'cs-chatbot'); ?></h3>
            <ul>
                <li><code>POST /chatbot/v1/chat</code> - <?php _e('Send chat message', 'cs-chatbot'); ?></li>
                <li><code>GET /chatbot/v1/status</code> - <?php _e('Get chatbot status', 'cs-chatbot'); ?></li>
                <li><code>POST /chatbot/v1/feedback</code> - <?php _e('Submit feedback', 'cs-chatbot'); ?></li>
                <li><code>GET /chatbot/v1/themes</code> - <?php _e('Get available themes', 'cs-chatbot'); ?></li>
            </ul>
        </div>
        <?php
    }
    
    public function activate() {
        // Create default options
        if (!get_option('cs_chatbot_options')) {
            $this->load_options();
            update_option('cs_chatbot_options', $this->options);
        }
        
        // Flush rewrite rules for REST API
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize the plugin
function cs_chatbot_init() {
    return CSChatbotSimple::getInstance();
}

// Start the plugin
add_action('plugins_loaded', 'cs_chatbot_init');

// Add settings link to plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=cs-chatbot') . '">' . __('Settings', 'cs-chatbot') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});