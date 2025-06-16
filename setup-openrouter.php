<?php
/**
 * CS Chatbot Professional - OpenRouter Setup Helper
 * Quick setup script for configuring OpenRouter API
 * 
 * @package CSChatbot
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Setup OpenRouter API configuration
 */
function cs_chatbot_setup_openrouter() {
    // Default settings for OpenRouter
    $default_settings = [
        'enable_chatbot' => true,
        'chatbot_name' => 'AI Assistant',
        'welcome_message' => 'Hello! I\'m here to help you. How can I assist you today?',
        'ai_model' => 'deepseek/deepseek-r1-0528-qwen3-8b:free',
        'ai_personality' => 'You are a helpful and friendly customer service assistant. Respond naturally and conversationally. Be professional yet approachable, and provide clear, helpful answers to customer questions. Keep responses concise but informative.',
        'widget_theme' => 'modern',
        'widget_position' => 'bottom-right',
        'widget_size' => 'medium',
        'primary_color' => '#007cba',
        'auto_open_chat' => false,
        'auto_open_delay' => 5,
        'show_typing_indicator' => true,
        'enable_sound' => true,
        'enable_live_chat' => true,
        'office_hours_start' => '09:00',
        'office_hours_end' => '17:00',
        'offline_message' => 'We\'re currently offline. Please leave a message and we\'ll get back to you soon!'
    ];
    
    // Get existing options
    $existing_options = get_option('cs_chatbot_options', []);
    
    // Merge with defaults (existing options take precedence)
    $options = array_merge($default_settings, $existing_options);
    
    // Update options
    update_option('cs_chatbot_options', $options);
    
    return $options;
}

/**
 * Validate OpenRouter API key
 */
function cs_chatbot_validate_openrouter_key($api_key) {
    if (empty($api_key)) {
        return false;
    }
    
    // Test API call
    $response = wp_remote_post('https://openrouter.ai/api/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => home_url(),
            'X-Title' => get_bloginfo('name') . ' - CS Chatbot Setup',
        ],
        'body' => wp_json_encode([
            'model' => 'deepseek/deepseek-r1-0528-qwen3-8b:free',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello']
            ],
            'max_tokens' => 10,
        ]),
        'timeout' => 30,
    ]);
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    return $response_code === 200;
}

/**
 * Setup wizard for first-time configuration
 */
function cs_chatbot_setup_wizard() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions', 'cs-chatbot'));
    }
    
    $step = $_GET['step'] ?? 1;
    $step = intval($step);
    
    switch ($step) {
        case 1:
            cs_chatbot_setup_step_1();
            break;
        case 2:
            cs_chatbot_setup_step_2();
            break;
        case 3:
            cs_chatbot_setup_step_3();
            break;
        default:
            cs_chatbot_setup_complete();
            break;
    }
}

function cs_chatbot_setup_step_1() {
    ?>
    <div class="wrap">
        <h1><?php _e('CS Chatbot Setup - Step 1: Welcome', 'cs-chatbot'); ?></h1>
        <div class="cs-setup-wizard">
            <h2><?php _e('Welcome to CS Chatbot Professional!', 'cs-chatbot'); ?></h2>
            <p><?php _e('This setup wizard will help you configure your chatbot with OpenRouter AI in just a few steps.', 'cs-chatbot'); ?></p>
            
            <h3><?php _e('What you\'ll need:', 'cs-chatbot'); ?></h3>
            <ul>
                <li>✅ <?php _e('OpenRouter API key (free account available)', 'cs-chatbot'); ?></li>
                <li>✅ <?php _e('5 minutes to complete setup', 'cs-chatbot'); ?></li>
            </ul>
            
            <p>
                <a href="<?php echo admin_url('admin.php?page=cs-chatbot-setup&step=2'); ?>" class="button button-primary button-large">
                    <?php _e('Get Started', 'cs-chatbot'); ?>
                </a>
            </p>
        </div>
    </div>
    <?php
}

function cs_chatbot_setup_step_2() {
    if ($_POST['api_key'] ?? false) {
        $api_key = sanitize_text_field($_POST['api_key']);
        
        if (cs_chatbot_validate_openrouter_key($api_key)) {
            $options = get_option('cs_chatbot_options', []);
            $options['openrouter_api_key'] = $api_key;
            update_option('cs_chatbot_options', $options);
            
            wp_redirect(admin_url('admin.php?page=cs-chatbot-setup&step=3'));
            exit;
        } else {
            $error = __('Invalid API key. Please check and try again.', 'cs-chatbot');
        }
    }
    ?>
    <div class="wrap">
        <h1><?php _e('CS Chatbot Setup - Step 2: API Configuration', 'cs-chatbot'); ?></h1>
        <div class="cs-setup-wizard">
            <?php if (isset($error)): ?>
                <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
            <?php endif; ?>
            
            <h2><?php _e('Configure OpenRouter API', 'cs-chatbot'); ?></h2>
            <p><?php _e('Enter your OpenRouter API key to enable AI-powered responses.', 'cs-chatbot'); ?></p>
            
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="api_key"><?php _e('OpenRouter API Key', 'cs-chatbot'); ?></label></th>
                        <td>
                            <input type="password" id="api_key" name="api_key" class="regular-text" required />
                            <p class="description">
                                <?php _e('Get your free API key at', 'cs-chatbot'); ?> 
                                <a href="https://openrouter.ai" target="_blank">https://openrouter.ai</a>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button button-primary" value="<?php _e('Validate & Continue', 'cs-chatbot'); ?>" />
                    <a href="<?php echo admin_url('admin.php?page=cs-chatbot-setup&step=1'); ?>" class="button">
                        <?php _e('Back', 'cs-chatbot'); ?>
                    </a>
                </p>
            </form>
        </div>
    </div>
    <?php
}

function cs_chatbot_setup_step_3() {
    if ($_POST) {
        $settings = [
            'chatbot_name' => sanitize_text_field($_POST['chatbot_name'] ?? 'AI Assistant'),
            'welcome_message' => sanitize_textarea_field($_POST['welcome_message'] ?? 'Hello! How can I help you today?'),
            'ai_model' => sanitize_text_field($_POST['ai_model'] ?? 'deepseek/deepseek-r1-0528-qwen3-8b:free'),
            'widget_position' => sanitize_text_field($_POST['widget_position'] ?? 'bottom-right'),
            'widget_theme' => sanitize_text_field($_POST['widget_theme'] ?? 'modern'),
        ];
        
        $options = get_option('cs_chatbot_options', []);
        $options = array_merge($options, $settings);
        update_option('cs_chatbot_options', $options);
        
        wp_redirect(admin_url('admin.php?page=cs-chatbot-setup&step=4'));
        exit;
    }
    ?>
    <div class="wrap">
        <h1><?php _e('CS Chatbot Setup - Step 3: Customize Your Bot', 'cs-chatbot'); ?></h1>
        <div class="cs-setup-wizard">
            <h2><?php _e('Customize Your Chatbot', 'cs-chatbot'); ?></h2>
            <p><?php _e('Configure the basic settings for your chatbot.', 'cs-chatbot'); ?></p>
            
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="chatbot_name"><?php _e('Chatbot Name', 'cs-chatbot'); ?></label></th>
                        <td><input type="text" id="chatbot_name" name="chatbot_name" value="AI Assistant" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="welcome_message"><?php _e('Welcome Message', 'cs-chatbot'); ?></label></th>
                        <td><textarea id="welcome_message" name="welcome_message" rows="3" class="large-text">Hello! I'm here to help you. How can I assist you today?</textarea></td>
                    </tr>
                    <tr>
                        <th><label for="ai_model"><?php _e('AI Model', 'cs-chatbot'); ?></label></th>
                        <td>
                            <select id="ai_model" name="ai_model">
                                <option value="deepseek/deepseek-r1-0528-qwen3-8b:free">DeepSeek R1 Qwen3 8B (Free)</option>
                                <option value="meta-llama/llama-3.2-3b-instruct:free">Llama 3.2 3B (Free)</option>
                                <option value="microsoft/phi-3-mini-128k-instruct:free">Phi-3 Mini (Free)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="widget_position"><?php _e('Widget Position', 'cs-chatbot'); ?></label></th>
                        <td>
                            <select id="widget_position" name="widget_position">
                                <option value="bottom-right">Bottom Right</option>
                                <option value="bottom-left">Bottom Left</option>
                                <option value="top-right">Top Right</option>
                                <option value="top-left">Top Left</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="widget_theme"><?php _e('Widget Theme', 'cs-chatbot'); ?></label></th>
                        <td>
                            <select id="widget_theme" name="widget_theme">
                                <option value="modern">Modern</option>
                                <option value="classic">Classic</option>
                                <option value="minimal">Minimal</option>
                                <option value="dark">Dark</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button button-primary" value="<?php _e('Complete Setup', 'cs-chatbot'); ?>" />
                    <a href="<?php echo admin_url('admin.php?page=cs-chatbot-setup&step=2'); ?>" class="button">
                        <?php _e('Back', 'cs-chatbot'); ?>
                    </a>
                </p>
            </form>
        </div>
    </div>
    <?php
}

function cs_chatbot_setup_complete() {
    // Initialize default settings
    cs_chatbot_setup_openrouter();
    ?>
    <div class="wrap">
        <h1><?php _e('CS Chatbot Setup Complete!', 'cs-chatbot'); ?></h1>
        <div class="cs-setup-wizard">
            <h2>🎉 <?php _e('Congratulations!', 'cs-chatbot'); ?></h2>
            <p><?php _e('Your CS Chatbot is now configured and ready to use with OpenRouter AI.', 'cs-chatbot'); ?></p>
            
            <h3><?php _e('What\'s Next?', 'cs-chatbot'); ?></h3>
            <ul>
                <li>✅ <?php _e('Visit your website to see the chatbot in action', 'cs-chatbot'); ?></li>
                <li>✅ <?php _e('Add knowledge base entries for better responses', 'cs-chatbot'); ?></li>
                <li>✅ <?php _e('Monitor conversations and analytics', 'cs-chatbot'); ?></li>
                <li>✅ <?php _e('Customize the appearance and behavior', 'cs-chatbot'); ?></li>
            </ul>
            
            <p>
                <a href="<?php echo admin_url('admin.php?page=cs-chatbot'); ?>" class="button button-primary button-large">
                    <?php _e('Go to Dashboard', 'cs-chatbot'); ?>
                </a>
                <a href="<?php echo home_url(); ?>" class="button button-large" target="_blank">
                    <?php _e('View Website', 'cs-chatbot'); ?>
                </a>
            </p>
        </div>
    </div>
    <?php
}
?>