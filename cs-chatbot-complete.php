<?php
/**
 * CS Chatbot Professional - Complete Plugin Implementation
 * All functionality consolidated into a single file for optimal performance
 * 
 * @package CSChatbot
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Plugin constants (only if not already defined)
if (!defined('CS_CHATBOT_VERSION')) {
    define('CS_CHATBOT_VERSION', '1.0.0');
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
 * Main CS Chatbot Plugin Class
 */
class CSChatbotProfessional {
    
    private static $instance = null;
    private $options = [];
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_options();
        $this->init_hooks();
    }
    
    private function load_options() {
        $this->options = get_option('cs_chatbot_options', []);
    }
    
    private function init_hooks() {
        // Admin hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('admin_init', [$this, 'admin_init']);
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('wp_footer', [$this, 'render_chatbot_widget']);
        
        // AJAX hooks
        add_action('wp_ajax_cs_chatbot_send_message', [$this, 'ajax_send_message']);
        add_action('wp_ajax_nopriv_cs_chatbot_send_message', [$this, 'ajax_send_message']);
        add_action('wp_ajax_cs_chatbot_get_conversations', [$this, 'ajax_get_conversations']);
        add_action('wp_ajax_cs_chatbot_save_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_cs_chatbot_get_analytics', [$this, 'ajax_get_analytics']);
        add_action('wp_ajax_cs_chatbot_test_api', [$this, 'ajax_test_api']);
        add_action('wp_ajax_cs_chatbot_start_live_chat', [$this, 'ajax_start_live_chat']);
        
        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        
        // Cron jobs
        add_action('cs_chatbot_daily_analytics', [$this, 'daily_analytics_sync']);
        add_action('cs_chatbot_weekly_report', [$this, 'weekly_chatbot_report']);
        add_action('cs_chatbot_cleanup_sessions', [$this, 'cleanup_old_sessions']);
    }
    
    // Admin Menu
    public function add_admin_menu() {
        add_menu_page(
            __('CS Chatbot', 'cs-chatbot'),
            __('CS Chatbot', 'cs-chatbot'),
            'manage_options',
            'cs-chatbot',
            [$this, 'admin_page'],
            'dashicons-format-chat',
            30
        );
    }
    
    public function admin_init() {
        // Register settings
        register_setting('cs_chatbot_settings', 'cs_chatbot_options');
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'cs-chatbot') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.9.1', true);
        
        wp_enqueue_script(
            'cs-chatbot-admin',
            CS_CHATBOT_URL . 'assets/js/cs-chatbot-admin.js',
            ['jquery', 'chart-js'],
            CS_CHATBOT_VERSION,
            true
        );
        
        wp_enqueue_style(
            'cs-chatbot-admin',
            CS_CHATBOT_URL . 'assets/css/cs-chatbot-admin.css',
            [],
            CS_CHATBOT_VERSION
        );
        
        wp_localize_script('cs-chatbot-admin', 'csChatbotAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cs_chatbot_nonce'),
            'strings' => [
                'confirm_delete' => __('Are you sure you want to delete this conversation?', 'cs-chatbot'),
                'loading' => __('Loading...', 'cs-chatbot'),
                'error' => __('An error occurred. Please try again.', 'cs-chatbot'),
                'success' => __('Operation completed successfully.', 'cs-chatbot')
            ]
        ]);
    }
    
    public function enqueue_frontend_scripts() {
        if (!$this->get_option('enable_chatbot', true)) {
            return;
        }
        
        wp_enqueue_script('jquery');
        
        wp_enqueue_script(
            'cs-chatbot-frontend',
            CS_CHATBOT_URL . 'assets/js/cs-chatbot-frontend.js',
            ['jquery'],
            CS_CHATBOT_VERSION,
            true
        );
        
        wp_enqueue_style(
            'cs-chatbot-frontend',
            CS_CHATBOT_URL . 'assets/css/cs-chatbot-frontend.css',
            [],
            CS_CHATBOT_VERSION
        );
        
        wp_localize_script('cs-chatbot-frontend', 'csChatbotFrontend', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cs_chatbot_nonce'),
            'settings' => [
                'position' => $this->get_option('widget_position', 'bottom-right'),
                'theme' => $this->get_option('widget_theme', 'modern'),
                'welcome_message' => $this->get_option('welcome_message', __('Hello! How can I help you today?', 'cs-chatbot')),
                'placeholder' => $this->get_option('input_placeholder', __('Type your message...', 'cs-chatbot')),
                'typing_indicator' => $this->get_option('show_typing_indicator', true),
                'sound_enabled' => $this->get_option('enable_sound', true),
                'auto_open' => $this->get_option('auto_open_chat', false),
                'auto_open_delay' => $this->get_option('auto_open_delay', 5000)
            ],
            'strings' => [
                'send' => __('Send', 'cs-chatbot'),
                'minimize' => __('Minimize', 'cs-chatbot'),
                'close' => __('Close', 'cs-chatbot'),
                'typing' => __('Bot is typing...', 'cs-chatbot'),
                'error' => __('Sorry, something went wrong. Please try again.', 'cs-chatbot'),
                'offline' => __('We are currently offline. Please leave a message.', 'cs-chatbot'),
                'live_agent' => __('Connect to live agent', 'cs-chatbot')
            ]
        ]);
    }
    
    // Admin Page
    public function admin_page() {
        ?>
        <div class="wrap cs-chatbot-admin">
            <?php $this->render_admin_header(__('CS Chatbot Professional', 'cs-chatbot')); ?>
            
            <nav class="nav-tab-wrapper wp-clearfix">
                <a href="#dashboard" class="nav-tab nav-tab-active"><?php _e('Dashboard', 'cs-chatbot'); ?></a>
                <a href="#conversations" class="nav-tab"><?php _e('Conversations', 'cs-chatbot'); ?></a>
                <a href="#live-chat" class="nav-tab"><?php _e('Live Chat', 'cs-chatbot'); ?></a>
                <a href="#analytics" class="nav-tab"><?php _e('Analytics', 'cs-chatbot'); ?></a>
                <a href="#knowledge-base" class="nav-tab"><?php _e('Knowledge Base', 'cs-chatbot'); ?></a>
                <a href="#settings" class="nav-tab"><?php _e('Settings', 'cs-chatbot'); ?></a>
            </nav>
            
            <div class="tab-content">
                <!-- Dashboard Tab -->
                <div id="dashboard" class="tab-pane active">
                    <?php $this->render_dashboard_tab(); ?>
                </div>
                
                <!-- Conversations Tab -->
                <div id="conversations" class="tab-pane">
                    <?php $this->render_conversations_tab(); ?>
                </div>
                
                <!-- Live Chat Tab -->
                <div id="live-chat" class="tab-pane">
                    <?php $this->render_live_chat_tab(); ?>
                </div>
                
                <!-- Analytics Tab -->
                <div id="analytics" class="tab-pane">
                    <?php $this->render_analytics_tab(); ?>
                </div>
                
                <!-- Knowledge Base Tab -->
                <div id="knowledge-base" class="tab-pane">
                    <?php $this->render_knowledge_base_tab(); ?>
                </div>
                
                <!-- Settings Tab -->
                <div id="settings" class="tab-pane">
                    <?php $this->render_settings_tab(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_dashboard_tab() {
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="cs-chatbot-dashboard">
            <div class="dashboard-widgets">
                <div class="dashboard-widget">
                    <div class="widget-icon">📊</div>
                    <div class="widget-content">
                        <h3><?php echo number_format($stats['total_conversations']); ?></h3>
                        <p><?php _e('Total Conversations', 'cs-chatbot'); ?></p>
                    </div>
                </div>
                
                <div class="dashboard-widget">
                    <div class="widget-icon">💬</div>
                    <div class="widget-content">
                        <h3><?php echo number_format($stats['messages_today']); ?></h3>
                        <p><?php _e('Messages Today', 'cs-chatbot'); ?></p>
                    </div>
                </div>
                
                <div class="dashboard-widget">
                    <div class="widget-icon">🤖</div>
                    <div class="widget-content">
                        <h3><?php echo $stats['ai_accuracy']; ?>%</h3>
                        <p><?php _e('AI Accuracy', 'cs-chatbot'); ?></p>
                    </div>
                </div>
                
                <div class="dashboard-widget">
                    <div class="widget-icon">⏱️</div>
                    <div class="widget-content">
                        <h3><?php echo $stats['avg_response_time']; ?>s</h3>
                        <p><?php _e('Avg Response Time', 'cs-chatbot'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-charts">
                <div class="chart-container">
                    <h3><?php _e('Conversations Over Time', 'cs-chatbot'); ?></h3>
                    <canvas id="conversationsChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3><?php _e('Message Types Distribution', 'cs-chatbot'); ?></h3>
                    <canvas id="messageTypesChart"></canvas>
                </div>
            </div>
            
            <div class="recent-activity">
                <h3><?php _e('Recent Activity', 'cs-chatbot'); ?></h3>
                <div class="activity-list">
                    <?php $this->render_recent_activity(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_conversations_tab() {
        ?>
        <div class="cs-chatbot-conversations">
            <div class="conversations-header">
                <h3><?php _e('Chat Conversations', 'cs-chatbot'); ?></h3>
                <div class="conversations-filters">
                    <select id="conversation-filter">
                        <option value="all"><?php _e('All Conversations', 'cs-chatbot'); ?></option>
                        <option value="active"><?php _e('Active', 'cs-chatbot'); ?></option>
                        <option value="resolved"><?php _e('Resolved', 'cs-chatbot'); ?></option>
                        <option value="pending"><?php _e('Pending', 'cs-chatbot'); ?></option>
                    </select>
                    <input type="date" id="conversation-date" />
                    <button class="button" id="export-conversations"><?php _e('Export', 'cs-chatbot'); ?></button>
                </div>
            </div>
            
            <div class="conversations-list">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Visitor', 'cs-chatbot'); ?></th>
                            <th><?php _e('Started', 'cs-chatbot'); ?></th>
                            <th><?php _e('Messages', 'cs-chatbot'); ?></th>
                            <th><?php _e('Status', 'cs-chatbot'); ?></th>
                            <th><?php _e('Agent', 'cs-chatbot'); ?></th>
                            <th><?php _e('Actions', 'cs-chatbot'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="conversations-table-body">
                        <?php $this->render_conversations_table(); ?>
                    </tbody>
                </table>
            </div>
            
            <div class="conversation-details" id="conversation-details" style="display: none;">
                <div class="conversation-header">
                    <h4><?php _e('Conversation Details', 'cs-chatbot'); ?></h4>
                    <button class="button" id="close-conversation-details"><?php _e('Close', 'cs-chatbot'); ?></button>
                </div>
                <div class="conversation-messages" id="conversation-messages"></div>
                <div class="conversation-actions">
                    <button class="button button-primary" id="assign-agent"><?php _e('Assign Agent', 'cs-chatbot'); ?></button>
                    <button class="button" id="mark-resolved"><?php _e('Mark Resolved', 'cs-chatbot'); ?></button>
                    <button class="button button-secondary" id="add-note"><?php _e('Add Note', 'cs-chatbot'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_live_chat_tab() {
        ?>
        <div class="cs-chatbot-live-chat">
            <div class="live-chat-header">
                <h3><?php _e('Live Chat Management', 'cs-chatbot'); ?></h3>
                <div class="agent-status">
                    <label>
                        <input type="checkbox" id="agent-online" <?php checked($this->get_option('agent_online', false)); ?> />
                        <?php _e('Available for Live Chat', 'cs-chatbot'); ?>
                    </label>
                </div>
            </div>
            
            <div class="live-chat-dashboard">
                <div class="active-chats">
                    <h4><?php _e('Active Chats', 'cs-chatbot'); ?></h4>
                    <div class="chat-queue" id="chat-queue">
                        <?php $this->render_active_chats(); ?>
                    </div>
                </div>
                
                <div class="chat-window" id="live-chat-window" style="display: none;">
                    <div class="chat-header">
                        <div class="visitor-info">
                            <span class="visitor-name" id="visitor-name"></span>
                            <span class="visitor-location" id="visitor-location"></span>
                        </div>
                        <div class="chat-actions">
                            <button class="button" id="transfer-chat"><?php _e('Transfer', 'cs-chatbot'); ?></button>
                            <button class="button" id="end-chat"><?php _e('End Chat', 'cs-chatbot'); ?></button>
                        </div>
                    </div>
                    
                    <div class="chat-messages" id="live-chat-messages"></div>
                    
                    <div class="chat-input">
                        <div class="quick-responses">
                            <button class="quick-response" data-message="<?php esc_attr_e('Hello! How can I help you today?', 'cs-chatbot'); ?>">
                                <?php _e('Greeting', 'cs-chatbot'); ?>
                            </button>
                            <button class="quick-response" data-message="<?php esc_attr_e('Thank you for contacting us. Is there anything else I can help you with?', 'cs-chatbot'); ?>">
                                <?php _e('Thank You', 'cs-chatbot'); ?>
                            </button>
                            <button class="quick-response" data-message="<?php esc_attr_e('I\'ll need to check on that for you. Please give me a moment.', 'cs-chatbot'); ?>">
                                <?php _e('Checking', 'cs-chatbot'); ?>
                            </button>
                        </div>
                        <div class="message-input">
                            <textarea id="live-message-input" placeholder="<?php esc_attr_e('Type your message...', 'cs-chatbot'); ?>"></textarea>
                            <button class="button button-primary" id="send-live-message"><?php _e('Send', 'cs-chatbot'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_analytics_tab() {
        ?>
        <div class="cs-chatbot-analytics">
            <div class="analytics-header">
                <h3><?php _e('Chatbot Analytics', 'cs-chatbot'); ?></h3>
                <div class="date-range-selector">
                    <select id="analytics-range">
                        <option value="7"><?php _e('Last 7 days', 'cs-chatbot'); ?></option>
                        <option value="30" selected><?php _e('Last 30 days', 'cs-chatbot'); ?></option>
                        <option value="90"><?php _e('Last 90 days', 'cs-chatbot'); ?></option>
                        <option value="365"><?php _e('Last year', 'cs-chatbot'); ?></option>
                    </select>
                    <button class="button" id="export-analytics"><?php _e('Export Report', 'cs-chatbot'); ?></button>
                </div>
            </div>
            
            <div class="analytics-metrics">
                <div class="metric-card">
                    <h4><?php _e('Total Conversations', 'cs-chatbot'); ?></h4>
                    <div class="metric-value" id="total-conversations">-</div>
                    <div class="metric-change" id="conversations-change">-</div>
                </div>
                
                <div class="metric-card">
                    <h4><?php _e('Resolution Rate', 'cs-chatbot'); ?></h4>
                    <div class="metric-value" id="resolution-rate">-</div>
                    <div class="metric-change" id="resolution-change">-</div>
                </div>
                
                <div class="metric-card">
                    <h4><?php _e('Avg Response Time', 'cs-chatbot'); ?></h4>
                    <div class="metric-value" id="avg-response-time">-</div>
                    <div class="metric-change" id="response-time-change">-</div>
                </div>
                
                <div class="metric-card">
                    <h4><?php _e('Customer Satisfaction', 'cs-chatbot'); ?></h4>
                    <div class="metric-value" id="satisfaction-score">-</div>
                    <div class="metric-change" id="satisfaction-change">-</div>
                </div>
            </div>
            
            <div class="analytics-charts">
                <div class="chart-row">
                    <div class="chart-container">
                        <h4><?php _e('Conversations Timeline', 'cs-chatbot'); ?></h4>
                        <canvas id="conversationsTimelineChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h4><?php _e('Response Time Distribution', 'cs-chatbot'); ?></h4>
                        <canvas id="responseTimeChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-row">
                    <div class="chart-container">
                        <h4><?php _e('Popular Topics', 'cs-chatbot'); ?></h4>
                        <canvas id="topicsChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h4><?php _e('User Satisfaction Ratings', 'cs-chatbot'); ?></h4>
                        <canvas id="satisfactionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_knowledge_base_tab() {
        ?>
        <div class="cs-chatbot-knowledge-base">
            <div class="knowledge-base-header">
                <h3><?php _e('Knowledge Base Management', 'cs-chatbot'); ?></h3>
                <button class="button button-primary" id="add-knowledge-item"><?php _e('Add New Item', 'cs-chatbot'); ?></button>
            </div>
            
            <div class="knowledge-base-content">
                <div class="knowledge-categories">
                    <h4><?php _e('Categories', 'cs-chatbot'); ?></h4>
                    <ul class="category-list" id="knowledge-categories">
                        <?php $this->render_knowledge_categories(); ?>
                    </ul>
                    <button class="button" id="add-category"><?php _e('Add Category', 'cs-chatbot'); ?></button>
                </div>
                
                <div class="knowledge-items">
                    <div class="search-knowledge">
                        <input type="text" id="knowledge-search" placeholder="<?php esc_attr_e('Search knowledge base...', 'cs-chatbot'); ?>" />
                        <button class="button" id="search-knowledge-btn"><?php _e('Search', 'cs-chatbot'); ?></button>
                    </div>
                    
                    <div class="knowledge-list" id="knowledge-items-list">
                        <?php $this->render_knowledge_items(); ?>
                    </div>
                </div>
            </div>
            
            <!-- Knowledge Item Modal -->
            <div id="knowledge-item-modal" class="cs-chatbot-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 id="modal-title"><?php _e('Add Knowledge Item', 'cs-chatbot'); ?></h4>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="knowledge-item-form">
                            <input type="hidden" id="item-id" />
                            
                            <div class="form-field">
                                <label for="item-question"><?php _e('Question/Trigger', 'cs-chatbot'); ?></label>
                                <input type="text" id="item-question" required />
                            </div>
                            
                            <div class="form-field">
                                <label for="item-answer"><?php _e('Answer/Response', 'cs-chatbot'); ?></label>
                                <textarea id="item-answer" rows="5" required></textarea>
                            </div>
                            
                            <div class="form-field">
                                <label for="item-category"><?php _e('Category', 'cs-chatbot'); ?></label>
                                <select id="item-category">
                                    <option value=""><?php _e('Select Category', 'cs-chatbot'); ?></option>
                                    <?php $this->render_category_options(); ?>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label for="item-keywords"><?php _e('Keywords (comma separated)', 'cs-chatbot'); ?></label>
                                <input type="text" id="item-keywords" />
                            </div>
                            
                            <div class="form-field">
                                <label>
                                    <input type="checkbox" id="item-active" checked />
                                    <?php _e('Active', 'cs-chatbot'); ?>
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="button button-primary" id="save-knowledge-item"><?php _e('Save', 'cs-chatbot'); ?></button>
                        <button class="button" id="cancel-knowledge-item"><?php _e('Cancel', 'cs-chatbot'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_settings_tab() {
        ?>
        <form method="post" action="" id="cs-chatbot-settings-form">
            <?php wp_nonce_field('cs_chatbot_settings', 'cs_chatbot_nonce'); ?>
            
            <!-- General Settings -->
            <div class="cs-chatbot-settings-section">
                <h3><?php _e('General Settings', 'cs-chatbot'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label for="enable_chatbot"><?php _e('Enable Chatbot', 'cs-chatbot'); ?></label></th>
                        <td>
                            <input type="checkbox" id="enable_chatbot" name="enable_chatbot" value="1" <?php checked($this->get_option('enable_chatbot', true)); ?> />
                            <p class="description"><?php _e('Enable or disable the chatbot on your website', 'cs-chatbot'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="chatbot_name"><?php _e('Chatbot Name', 'cs-chatbot'); ?></label></th>
                        <td>
                            <input type="text" id="chatbot_name" name="chatbot_name" value="<?php echo esc_attr($this->get_option('chatbot_name', 'Assistant')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="welcome_message"><?php _e('Welcome Message', 'cs-chatbot'); ?></label></th>
                        <td>
                            <textarea id="welcome_message" name="welcome_message" rows="3" class="large-text"><?php echo esc_textarea($this->get_option('welcome_message', __('Hello! How can I help you today?', 'cs-chatbot'))); ?></textarea>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="widget_position"><?php _e('Widget Position', 'cs-chatbot'); ?></label></th>
                        <td>
                            <select id="widget_position" name="widget_position">
                                <option value="bottom-right" <?php selected($this->get_option('widget_position', 'bottom-right'), 'bottom-right'); ?>><?php _e('Bottom Right', 'cs-chatbot'); ?></option>
                                <option value="bottom-left" <?php selected($this->get_option('widget_position', 'bottom-right'), 'bottom-left'); ?>><?php _e('Bottom Left', 'cs-chatbot'); ?></option>
                                <option value="top-right" <?php selected($this->get_option('widget_position', 'bottom-right'), 'top-right'); ?>><?php _e('Top Right', 'cs-chatbot'); ?></option>
                                <option value="top-left" <?php selected($this->get_option('widget_position', 'bottom-right'), 'top-left'); ?>><?php _e('Top Left', 'cs-chatbot'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- AI Settings -->
            <div class="cs-chatbot-settings-section">
                <h3><?php _e('AI Configuration', 'cs-chatbot'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><?php _e('SEO-Forge API', 'cs-chatbot'); ?></th>
                        <td>
                            <p class="description"><?php _e('✅ Primary AI API for chatbot responses (No API key required)', 'cs-chatbot'); ?><br>
                            <?php _e('Powered by https://seo-forge.bitebase.app', 'cs-chatbot'); ?></p>
                            <button type="button" id="test-seo-forge-api" class="button button-secondary"><?php _e('Test API Connection', 'cs-chatbot'); ?></button>
                            <div id="api-test-result" style="margin-top: 10px;"></div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="openai_api_key"><?php _e('OpenAI API Key (Optional)', 'cs-chatbot'); ?></label></th>
                        <td>
                            <input type="password" id="openai_api_key" name="openai_api_key" value="<?php echo esc_attr($this->get_option('openai_api_key', '')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Optional fallback API when SEO-Forge API is unavailable', 'cs-chatbot'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="ai_model"><?php _e('AI Model', 'cs-chatbot'); ?></label></th>
                        <td>
                            <select id="ai_model" name="ai_model">
                                <option value="gpt-3.5-turbo" <?php selected($this->get_option('ai_model', 'gpt-3.5-turbo'), 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                                <option value="gpt-4" <?php selected($this->get_option('ai_model', 'gpt-3.5-turbo'), 'gpt-4'); ?>>GPT-4</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="ai_personality"><?php _e('AI Personality', 'cs-chatbot'); ?></label></th>
                        <td>
                            <textarea id="ai_personality" name="ai_personality" rows="3" class="large-text"><?php echo esc_textarea($this->get_option('ai_personality', __('You are a helpful customer service assistant. Be friendly, professional, and concise in your responses.', 'cs-chatbot'))); ?></textarea>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Appearance Settings -->
            <div class="cs-chatbot-settings-section">
                <h3><?php _e('Appearance', 'cs-chatbot'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label for="widget_theme"><?php _e('Widget Theme', 'cs-chatbot'); ?></label></th>
                        <td>
                            <select id="widget_theme" name="widget_theme">
                                <option value="modern" <?php selected($this->get_option('widget_theme', 'modern'), 'modern'); ?>><?php _e('Modern', 'cs-chatbot'); ?></option>
                                <option value="classic" <?php selected($this->get_option('widget_theme', 'modern'), 'classic'); ?>><?php _e('Classic', 'cs-chatbot'); ?></option>
                                <option value="minimal" <?php selected($this->get_option('widget_theme', 'modern'), 'minimal'); ?>><?php _e('Minimal', 'cs-chatbot'); ?></option>
                                <option value="dark" <?php selected($this->get_option('widget_theme', 'modern'), 'dark'); ?>><?php _e('Dark', 'cs-chatbot'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="primary_color"><?php _e('Primary Color', 'cs-chatbot'); ?></label></th>
                        <td>
                            <input type="color" id="primary_color" name="primary_color" value="<?php echo esc_attr($this->get_option('primary_color', '#007cba')); ?>" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="widget_size"><?php _e('Widget Size', 'cs-chatbot'); ?></label></th>
                        <td>
                            <select id="widget_size" name="widget_size">
                                <option value="small" <?php selected($this->get_option('widget_size', 'medium'), 'small'); ?>><?php _e('Small', 'cs-chatbot'); ?></option>
                                <option value="medium" <?php selected($this->get_option('widget_size', 'medium'), 'medium'); ?>><?php _e('Medium', 'cs-chatbot'); ?></option>
                                <option value="large" <?php selected($this->get_option('widget_size', 'medium'), 'large'); ?>><?php _e('Large', 'cs-chatbot'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Behavior Settings -->
            <div class="cs-chatbot-settings-section">
                <h3><?php _e('Behavior', 'cs-chatbot'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label for="auto_open_chat"><?php _e('Auto Open Chat', 'cs-chatbot'); ?></label></th>
                        <td>
                            <input type="checkbox" id="auto_open_chat" name="auto_open_chat" value="1" <?php checked($this->get_option('auto_open_chat', false)); ?> />
                            <p class="description"><?php _e('Automatically open chat widget after page load', 'cs-chatbot'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="auto_open_delay"><?php _e('Auto Open Delay (seconds)', 'cs-chatbot'); ?></label></th>
                        <td>
                            <input type="number" id="auto_open_delay" name="auto_open_delay" value="<?php echo esc_attr($this->get_option('auto_open_delay', 5)); ?>" min="1" max="60" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="show_typing_indicator"><?php _e('Show Typing Indicator', 'cs-chatbot'); ?></label></th>
                        <td>
                            <input type="checkbox" id="show_typing_indicator" name="show_typing_indicator" value="1" <?php checked($this->get_option('show_typing_indicator', true)); ?> />
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="enable_sound"><?php _e('Enable Sound Notifications', 'cs-chatbot'); ?></label></th>
                        <td>
                            <input type="checkbox" id="enable_sound" name="enable_sound" value="1" <?php checked($this->get_option('enable_sound', true)); ?> />
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Live Chat Settings -->
            <div class="cs-chatbot-settings-section">
                <h3><?php _e('Live Chat', 'cs-chatbot'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label for="enable_live_chat"><?php _e('Enable Live Chat', 'cs-chatbot'); ?></label></th>
                        <td>
                            <input type="checkbox" id="enable_live_chat" name="enable_live_chat" value="1" <?php checked($this->get_option('enable_live_chat', true)); ?> />
                            <p class="description"><?php _e('Allow visitors to connect with live agents', 'cs-chatbot'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="office_hours"><?php _e('Office Hours', 'cs-chatbot'); ?></label></th>
                        <td>
                            <input type="time" id="office_hours_start" name="office_hours_start" value="<?php echo esc_attr($this->get_option('office_hours_start', '09:00')); ?>" />
                            <?php _e('to', 'cs-chatbot'); ?>
                            <input type="time" id="office_hours_end" name="office_hours_end" value="<?php echo esc_attr($this->get_option('office_hours_end', '17:00')); ?>" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="offline_message"><?php _e('Offline Message', 'cs-chatbot'); ?></label></th>
                        <td>
                            <textarea id="offline_message" name="offline_message" rows="3" class="large-text"><?php echo esc_textarea($this->get_option('offline_message', __('We are currently offline. Please leave a message and we will get back to you.', 'cs-chatbot'))); ?></textarea>
                        </td>
                    </tr>
                </table>
            </div>
            
            <p class="submit">
                <input type="submit" name="submit" class="button button-primary" value="<?php _e('Save Settings', 'cs-chatbot'); ?>" />
            </p>
        </form>
        <?php
    }
    
    // Helper Methods
    private function render_admin_header($title) {
        echo '<div class="cs-chatbot-header">';
        echo '<h1 class="wp-heading-inline">' . esc_html($title) . '</h1>';
        echo '<span class="cs-chatbot-version">v' . CS_CHATBOT_VERSION . '</span>';
        echo '</div>';
    }
    
    private function get_option($key, $default = '') {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }
    
    private function get_dashboard_stats() {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'cs_chatbot_conversations';
        $messages_table = $wpdb->prefix . 'cs_chatbot_messages';
        
        $stats = [
            'total_conversations' => 0,
            'messages_today' => 0,
            'ai_accuracy' => 85,
            'avg_response_time' => 2.5
        ];
        
        // Get total conversations
        $total_conversations = $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table");
        $stats['total_conversations'] = $total_conversations ?: 0;
        
        // Get messages today
        $messages_today = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $messages_table WHERE DATE(created_at) = %s",
            current_time('Y-m-d')
        ));
        $stats['messages_today'] = $messages_today ?: 0;
        
        return $stats;
    }
    
    private function render_recent_activity() {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'cs_chatbot_conversations';
        $recent_conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $conversations_table ORDER BY created_at DESC LIMIT %d",
            10
        ));
        
        if (empty($recent_conversations)) {
            echo '<p>' . __('No recent activity', 'cs-chatbot') . '</p>';
            return;
        }
        
        foreach ($recent_conversations as $conversation) {
            echo '<div class="activity-item">';
            echo '<div class="activity-icon">💬</div>';
            echo '<div class="activity-content">';
            echo '<p><strong>' . esc_html($conversation->visitor_name ?: __('Anonymous', 'cs-chatbot')) . '</strong> ' . __('started a conversation', 'cs-chatbot') . '</p>';
            echo '<span class="activity-time">' . human_time_diff(strtotime($conversation->created_at)) . ' ' . __('ago', 'cs-chatbot') . '</span>';
            echo '</div>';
            echo '</div>';
        }
    }
    
    private function render_conversations_table() {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'cs_chatbot_conversations';
        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $conversations_table ORDER BY created_at DESC LIMIT %d",
            50
        ));
        
        if (empty($conversations)) {
            echo '<tr><td colspan="6">' . __('No conversations found', 'cs-chatbot') . '</td></tr>';
            return;
        }
        
        foreach ($conversations as $conversation) {
            echo '<tr>';
            echo '<td>' . esc_html($conversation->visitor_name ?: __('Anonymous', 'cs-chatbot')) . '</td>';
            echo '<td>' . esc_html(date('M j, Y H:i', strtotime($conversation->created_at))) . '</td>';
            echo '<td>' . intval($conversation->message_count) . '</td>';
            echo '<td><span class="status-badge status-' . esc_attr($conversation->status) . '">' . esc_html(ucfirst($conversation->status)) . '</span></td>';
            echo '<td>' . esc_html($conversation->agent_name ?: '-') . '</td>';
            echo '<td>';
            echo '<button class="button button-small view-conversation" data-id="' . esc_attr($conversation->id) . '">' . __('View', 'cs-chatbot') . '</button>';
            echo '</td>';
            echo '</tr>';
        }
    }
    
    private function render_active_chats() {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'cs_chatbot_conversations';
        $active_chats = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $conversations_table WHERE status = %s ORDER BY created_at DESC",
            'active'
        ));
        
        if (empty($active_chats)) {
            echo '<div class="no-active-chats">' . __('No active chats', 'cs-chatbot') . '</div>';
            return;
        }
        
        foreach ($active_chats as $chat) {
            echo '<div class="chat-item" data-id="' . esc_attr($chat->id) . '">';
            echo '<div class="chat-avatar">👤</div>';
            echo '<div class="chat-info">';
            echo '<div class="chat-name">' . esc_html($chat->visitor_name ?: __('Anonymous', 'cs-chatbot')) . '</div>';
            echo '<div class="chat-time">' . human_time_diff(strtotime($chat->created_at)) . ' ' . __('ago', 'cs-chatbot') . '</div>';
            echo '</div>';
            echo '<div class="chat-status">';
            echo '<span class="status-indicator active"></span>';
            echo '</div>';
            echo '</div>';
        }
    }
    
    private function render_knowledge_categories() {
        global $wpdb;
        
        $categories_table = $wpdb->prefix . 'cs_chatbot_categories';
        $categories = $wpdb->get_results("SELECT * FROM $categories_table ORDER BY name ASC");
        
        if (empty($categories)) {
            echo '<li>' . __('No categories found', 'cs-chatbot') . '</li>';
            return;
        }
        
        foreach ($categories as $category) {
            echo '<li class="category-item" data-id="' . esc_attr($category->id) . '">';
            echo '<span class="category-name">' . esc_html($category->name) . '</span>';
            echo '<span class="category-count">(' . intval($category->item_count) . ')</span>';
            echo '</li>';
        }
    }
    
    private function render_knowledge_items() {
        global $wpdb;
        
        $knowledge_table = $wpdb->prefix . 'cs_chatbot_knowledge';
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $knowledge_table ORDER BY created_at DESC LIMIT %d",
            20
        ));
        
        if (empty($items)) {
            echo '<div class="no-knowledge-items">' . __('No knowledge items found', 'cs-chatbot') . '</div>';
            return;
        }
        
        foreach ($items as $item) {
            echo '<div class="knowledge-item" data-id="' . esc_attr($item->id) . '">';
            echo '<div class="item-question">' . esc_html($item->question) . '</div>';
            echo '<div class="item-answer">' . esc_html(wp_trim_words($item->answer, 20)) . '</div>';
            echo '<div class="item-actions">';
            echo '<button class="button button-small edit-item">' . __('Edit', 'cs-chatbot') . '</button>';
            echo '<button class="button button-small delete-item">' . __('Delete', 'cs-chatbot') . '</button>';
            echo '</div>';
            echo '</div>';
        }
    }
    
    private function render_category_options() {
        global $wpdb;
        
        $categories_table = $wpdb->prefix . 'cs_chatbot_categories';
        $categories = $wpdb->get_results("SELECT * FROM $categories_table ORDER BY name ASC");
        
        foreach ($categories as $category) {
            echo '<option value="' . esc_attr($category->id) . '">' . esc_html($category->name) . '</option>';
        }
    }
    
    // Frontend Widget
    public function render_chatbot_widget() {
        if (!$this->get_option('enable_chatbot', true)) {
            return;
        }
        
        $position = $this->get_option('widget_position', 'bottom-right');
        $theme = $this->get_option('widget_theme', 'modern');
        $size = $this->get_option('widget_size', 'medium');
        
        ?>
        <div id="cs-chatbot-widget" class="cs-chatbot-widget position-<?php echo esc_attr($position); ?> theme-<?php echo esc_attr($theme); ?> size-<?php echo esc_attr($size); ?>" style="display: none;">
            <div class="chatbot-toggle" id="chatbot-toggle">
                <div class="toggle-icon">💬</div>
                <div class="notification-badge" id="notification-badge" style="display: none;">1</div>
            </div>
            
            <div class="chatbot-window" id="chatbot-window" style="display: none;">
                <div class="chatbot-header">
                    <div class="header-info">
                        <div class="bot-avatar">🤖</div>
                        <div class="bot-details">
                            <div class="bot-name"><?php echo esc_html($this->get_option('chatbot_name', 'Assistant')); ?></div>
                            <div class="bot-status"><?php _e('Online', 'cs-chatbot'); ?></div>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="minimize-btn" id="minimize-chat">−</button>
                        <button class="close-btn" id="close-chat">×</button>
                    </div>
                </div>
                
                <div class="chatbot-messages" id="chatbot-messages">
                    <div class="message bot-message">
                        <div class="message-avatar">🤖</div>
                        <div class="message-content">
                            <div class="message-text"><?php echo esc_html($this->get_option('welcome_message', __('Hello! How can I help you today?', 'cs-chatbot'))); ?></div>
                            <div class="message-time"><?php echo current_time('H:i'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="typing-indicator" id="typing-indicator" style="display: none;">
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <span class="typing-text"><?php _e('Bot is typing...', 'cs-chatbot'); ?></span>
                </div>
                
                <div class="chatbot-input">
                    <div class="quick-actions" id="quick-actions">
                        <?php if ($this->get_option('enable_live_chat', true)): ?>
                        <button class="quick-action" id="request-live-agent">
                            👨‍💼 <?php _e('Live Agent', 'cs-chatbot'); ?>
                        </button>
                        <?php endif; ?>
                        <button class="quick-action" id="restart-conversation">
                            🔄 <?php _e('Restart', 'cs-chatbot'); ?>
                        </button>
                    </div>
                    
                    <div class="input-area">
                        <textarea id="message-input" placeholder="<?php echo esc_attr($this->get_option('input_placeholder', __('Type your message...', 'cs-chatbot'))); ?>" rows="1"></textarea>
                        <button id="send-message" class="send-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="chatbot-footer">
                    <div class="powered-by">
                        <?php _e('Powered by', 'cs-chatbot'); ?> <strong>CS Chatbot</strong>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    // AJAX Handlers
    public function ajax_send_message() {
        check_ajax_referer('cs_chatbot_nonce', 'nonce');
        
        $message = sanitize_text_field($_POST['message'] ?? '');
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        $visitor_id = sanitize_text_field($_POST['visitor_id'] ?? '');
        
        if (empty($message)) {
            wp_send_json_error(['message' => __('Message cannot be empty', 'cs-chatbot')]);
        }
        
        // Save user message
        $this->save_message($conversation_id, $visitor_id, $message, 'user');
        
        // Generate AI response
        $ai_response = $this->generate_ai_response($message, $conversation_id);
        
        // Save AI response
        $this->save_message($conversation_id, $visitor_id, $ai_response, 'bot');
        
        wp_send_json_success([
            'response' => $ai_response,
            'conversation_id' => $conversation_id,
            'timestamp' => current_time('H:i')
        ]);
    }
    
    public function ajax_get_conversations() {
        check_ajax_referer('cs_chatbot_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cs-chatbot'));
        }
        
        global $wpdb;
        $conversations_table = $wpdb->prefix . 'cs_chatbot_conversations';
        
        $filter = sanitize_text_field($_POST['filter'] ?? 'all');
        $date = sanitize_text_field($_POST['date'] ?? '');
        
        $where_clause = "WHERE 1=1";
        $params = [];
        
        if ($filter !== 'all') {
            $where_clause .= " AND status = %s";
            $params[] = $filter;
        }
        
        if (!empty($date)) {
            $where_clause .= " AND DATE(created_at) = %s";
            $params[] = $date;
        }
        
        $query = "SELECT * FROM $conversations_table $where_clause ORDER BY created_at DESC LIMIT 50";
        
        if (!empty($params)) {
            $conversations = $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            $conversations = $wpdb->get_results($query);
        }
        
        wp_send_json_success(['conversations' => $conversations]);
    }
    
    public function ajax_save_settings() {
        check_ajax_referer('cs_chatbot_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cs-chatbot'));
        }
        
        $settings = $_POST['settings'] ?? [];
        $this->save_settings($settings);
        
        wp_send_json_success(['message' => __('Settings saved successfully', 'cs-chatbot')]);
    }
    
    public function ajax_get_analytics() {
        check_ajax_referer('cs_chatbot_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cs-chatbot'));
        }
        
        $days = intval($_POST['days'] ?? 30);
        $analytics_data = $this->get_analytics_data($days);
        
        wp_send_json_success($analytics_data);
    }
    
    public function ajax_test_api() {
        check_ajax_referer('cs_chatbot_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'cs-chatbot'));
        }
        
        // Test the SEO-Forge API with a simple request
        $test_response = $this->generate_seo_forge_response(
            'Hello, how are you?',
            1
        );
        
        if ($test_response) {
            wp_send_json_success([
                'message' => __('✅ SEO-Forge API is working correctly!', 'cs-chatbot'),
                'sample_response' => substr($test_response, 0, 200) . '...',
                'status' => 'success'
            ]);
        } else {
            wp_send_json_error([
                'message' => __('❌ SEO-Forge API test failed. Check error logs for details.', 'cs-chatbot'),
                'status' => 'error'
            ]);
        }
    }
    
    public function ajax_start_live_chat() {
        check_ajax_referer('cs_chatbot_nonce', 'nonce');
        
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        $visitor_id = sanitize_text_field($_POST['visitor_id'] ?? '');
        
        // Update conversation status to request live agent
        global $wpdb;
        $conversations_table = $wpdb->prefix . 'cs_chatbot_conversations';
        
        $wpdb->update(
            $conversations_table,
            ['status' => 'pending_agent'],
            ['id' => $conversation_id],
            ['%s'],
            ['%d']
        );
        
        wp_send_json_success([
            'message' => __('Live agent requested. Please wait while we connect you.', 'cs-chatbot')
        ]);
    }
    
    // Core Functions
    private function generate_ai_response($message, $conversation_id = 0) {
        // Try SEO-Forge API first
        $seo_forge_response = $this->generate_seo_forge_response($message, $conversation_id);
        if ($seo_forge_response) {
            return $seo_forge_response;
        }
        
        // Fallback to OpenAI if SEO-Forge API fails
        $openai_response = $this->generate_openai_response($message, $conversation_id);
        if ($openai_response) {
            return $openai_response;
        }
        
        // Final fallback to knowledge base or default response
        return $this->generate_fallback_response($message, $conversation_id);
    }
    
    private function generate_seo_forge_response($message, $conversation_id = 0) {
        $api_endpoint = 'https://seo-forge.bitebase.app/api/chat/response';
        
        // Get conversation context
        $context = $this->get_conversation_context($conversation_id);
        
        $response = wp_remote_post($api_endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'CS-Chatbot-WordPress-Plugin/1.0.0',
                'Accept' => 'application/json',
            ],
            'body' => wp_json_encode([
                'message' => sanitize_text_field($message),
                'context' => $context,
                'personality' => $this->get_option('ai_personality', 'You are a helpful customer service assistant.'),
                'conversation_id' => $conversation_id,
                'site_url' => home_url(),
                'plugin_version' => '1.0.0'
            ]),
            'timeout' => 30,
            'sslverify' => true,
        ]);
        
        if (is_wp_error($response)) {
            error_log('CS-Chatbot API Error: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            error_log('CS-Chatbot API Response Code: ' . $response_code);
            return false;
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('CS-Chatbot API JSON Error: ' . json_last_error_msg());
            return false;
        }
        
        if (isset($data['response']) && !empty($data['response'])) {
            return $data['response'];
        }
        
        if (isset($data['data']['response']) && !empty($data['data']['response'])) {
            return $data['data']['response'];
        }
        
        return false;
    }
    
    private function generate_openai_response($message, $conversation_id = 0) {
        $api_key = $this->get_option('openai_api_key', '');
        
        if (empty($api_key)) {
            return false;
        }
        
        $context = $this->get_conversation_context($conversation_id);
        $personality = $this->get_option('ai_personality', 'You are a helpful customer service assistant.');
        
        $messages = [
            ['role' => 'system', 'content' => $personality]
        ];
        
        // Add conversation context
        foreach ($context as $ctx_message) {
            $role = $ctx_message['sender'] === 'user' ? 'user' : 'assistant';
            $messages[] = ['role' => $role, 'content' => $ctx_message['message']];
        }
        
        // Add current message
        $messages[] = ['role' => 'user', 'content' => $message];
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'model' => $this->get_option('ai_model', 'gpt-3.5-turbo'),
                'messages' => $messages,
                'max_tokens' => 150,
                'temperature' => 0.7,
            ]),
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return trim($data['choices'][0]['message']['content']);
        }
        
        return false;
    }
    
    private function generate_fallback_response($message, $conversation_id = 0) {
        // Check knowledge base first
        $kb_response = $this->search_knowledge_base($message);
        if ($kb_response) {
            return $kb_response;
        }
        
        // Default responses based on message content
        $message_lower = strtolower($message);
        
        if (strpos($message_lower, 'hello') !== false || strpos($message_lower, 'hi') !== false) {
            return __('Hello! How can I help you today?', 'cs-chatbot');
        }
        
        if (strpos($message_lower, 'thank') !== false) {
            return __('You\'re welcome! Is there anything else I can help you with?', 'cs-chatbot');
        }
        
        if (strpos($message_lower, 'bye') !== false || strpos($message_lower, 'goodbye') !== false) {
            return __('Goodbye! Have a great day!', 'cs-chatbot');
        }
        
        if (strpos($message_lower, 'help') !== false) {
            return __('I\'m here to help! What specific information are you looking for?', 'cs-chatbot');
        }
        
        // Default response
        return __('I understand you\'re asking about that. Let me help you find the right information. Could you please provide more details?', 'cs-chatbot');
    }
    
    private function search_knowledge_base($message) {
        global $wpdb;
        
        $knowledge_table = $wpdb->prefix . 'cs_chatbot_knowledge';
        $message_lower = strtolower($message);
        
        // Search for matching keywords
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $knowledge_table 
             WHERE active = 1 
             AND (LOWER(question) LIKE %s OR LOWER(keywords) LIKE %s)
             ORDER BY CHAR_LENGTH(question) ASC
             LIMIT 1",
            '%' . $wpdb->esc_like($message_lower) . '%',
            '%' . $wpdb->esc_like($message_lower) . '%'
        ));
        
        if (!empty($results)) {
            return $results[0]->answer;
        }
        
        return false;
    }
    
    private function get_conversation_context($conversation_id, $limit = 5) {
        if (!$conversation_id) {
            return [];
        }
        
        global $wpdb;
        $messages_table = $wpdb->prefix . 'cs_chatbot_messages';
        
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT message, sender FROM $messages_table 
             WHERE conversation_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d",
            $conversation_id,
            $limit
        ));
        
        return array_reverse($messages);
    }
    
    private function save_message($conversation_id, $visitor_id, $message, $sender) {
        global $wpdb;
        
        // Create conversation if it doesn't exist
        if (!$conversation_id) {
            $conversation_id = $this->create_conversation($visitor_id);
        }
        
        $messages_table = $wpdb->prefix . 'cs_chatbot_messages';
        
        $wpdb->insert(
            $messages_table,
            [
                'conversation_id' => $conversation_id,
                'message' => $message,
                'sender' => $sender,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s']
        );
        
        // Update conversation message count
        $this->update_conversation_message_count($conversation_id);
        
        return $conversation_id;
    }
    
    private function create_conversation($visitor_id) {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'cs_chatbot_conversations';
        
        $wpdb->insert(
            $conversations_table,
            [
                'visitor_id' => $visitor_id,
                'visitor_name' => '',
                'visitor_email' => '',
                'status' => 'active',
                'message_count' => 0,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%d', '%s']
        );
        
        return $wpdb->insert_id;
    }
    
    private function update_conversation_message_count($conversation_id) {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'cs_chatbot_conversations';
        $messages_table = $wpdb->prefix . 'cs_chatbot_messages';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $messages_table WHERE conversation_id = %d",
            $conversation_id
        ));
        
        $wpdb->update(
            $conversations_table,
            ['message_count' => $count],
            ['id' => $conversation_id],
            ['%d'],
            ['%d']
        );
    }
    
    private function get_analytics_data($days = 30) {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'cs_chatbot_conversations';
        $messages_table = $wpdb->prefix . 'cs_chatbot_messages';
        
        $date_from = date('Y-m-d', strtotime("-$days days"));
        
        // Get conversation stats
        $total_conversations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $conversations_table WHERE DATE(created_at) >= %s",
            $date_from
        ));
        
        $resolved_conversations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $conversations_table WHERE status = 'resolved' AND DATE(created_at) >= %s",
            $date_from
        ));
        
        $resolution_rate = $total_conversations > 0 ? round(($resolved_conversations / $total_conversations) * 100, 1) : 0;
        
        // Get daily conversation data for chart
        $daily_data = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM $conversations_table 
             WHERE DATE(created_at) >= %s 
             GROUP BY DATE(created_at) 
             ORDER BY date ASC",
            $date_from
        ));
        
        return [
            'total_conversations' => $total_conversations,
            'resolution_rate' => $resolution_rate,
            'avg_response_time' => 2.5, // Placeholder
            'satisfaction_score' => 4.2, // Placeholder
            'daily_data' => $daily_data
        ];
    }
    
    private function save_settings($settings) {
        $sanitized_settings = [];
        
        foreach ($settings as $key => $value) {
            switch ($key) {
                case 'enable_chatbot':
                case 'auto_open_chat':
                case 'show_typing_indicator':
                case 'enable_sound':
                case 'enable_live_chat':
                    $sanitized_settings[$key] = (bool) $value;
                    break;
                    
                case 'chatbot_name':
                case 'widget_position':
                case 'widget_theme':
                case 'widget_size':
                case 'ai_model':
                    $sanitized_settings[$key] = sanitize_text_field($value);
                    break;
                    
                case 'welcome_message':
                case 'ai_personality':
                case 'offline_message':
                    $sanitized_settings[$key] = sanitize_textarea_field($value);
                    break;
                    
                case 'openai_api_key':
                    $sanitized_settings[$key] = sanitize_text_field($value);
                    break;
                    
                case 'primary_color':
                    $sanitized_settings[$key] = sanitize_hex_color($value);
                    break;
                    
                case 'auto_open_delay':
                    $sanitized_settings[$key] = intval($value);
                    break;
                    
                case 'office_hours_start':
                case 'office_hours_end':
                    $sanitized_settings[$key] = sanitize_text_field($value);
                    break;
                    
                default:
                    $sanitized_settings[$key] = sanitize_text_field($value);
                    break;
            }
        }
        
        $this->options = array_merge($this->options, $sanitized_settings);
        update_option('cs_chatbot_options', $this->options);
    }
    
    // REST API
    public function register_rest_routes() {
        register_rest_route('cs-chatbot/v1', '/message', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_send_message'],
            'permission_callback' => '__return_true'
        ]);
        
        register_rest_route('cs-chatbot/v1', '/conversations', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_conversations'],
            'permission_callback' => [$this, 'rest_permission_check']
        ]);
    }
    
    public function rest_send_message($request) {
        $message = sanitize_text_field($request->get_param('message'));
        $conversation_id = intval($request->get_param('conversation_id'));
        $visitor_id = sanitize_text_field($request->get_param('visitor_id'));
        
        if (empty($message)) {
            return new WP_Error('empty_message', __('Message cannot be empty', 'cs-chatbot'), ['status' => 400]);
        }
        
        // Save user message
        $conversation_id = $this->save_message($conversation_id, $visitor_id, $message, 'user');
        
        // Generate AI response
        $ai_response = $this->generate_ai_response($message, $conversation_id);
        
        // Save AI response
        $this->save_message($conversation_id, $visitor_id, $ai_response, 'bot');
        
        return rest_ensure_response([
            'response' => $ai_response,
            'conversation_id' => $conversation_id,
            'timestamp' => current_time('c')
        ]);
    }
    
    public function rest_get_conversations($request) {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'cs_chatbot_conversations';
        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $conversations_table ORDER BY created_at DESC LIMIT %d",
            50
        ));
        
        return rest_ensure_response($conversations);
    }
    
    public function rest_permission_check() {
        return current_user_can('manage_options');
    }
    
    // Cron Jobs
    public function daily_analytics_sync() {
        // Sync analytics data
        $this->cleanup_old_sessions();
    }
    
    public function weekly_chatbot_report() {
        // Generate and send weekly report
        if (!$this->get_option('enable_weekly_reports', false)) {
            return;
        }
        
        $admin_email = get_option('admin_email');
        $analytics = $this->get_analytics_data(7);
        
        $subject = sprintf(__('Weekly Chatbot Report - %s', 'cs-chatbot'), get_bloginfo('name'));
        $message = sprintf(
            __('Here\'s your weekly chatbot report:\n\nTotal Conversations: %d\nResolution Rate: %s%%\nAverage Response Time: %ss\n\nBest regards,\nCS Chatbot Team', 'cs-chatbot'),
            $analytics['total_conversations'],
            $analytics['resolution_rate'],
            $analytics['avg_response_time']
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    public function cleanup_old_sessions() {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'cs_chatbot_conversations';
        $messages_table = $wpdb->prefix . 'cs_chatbot_messages';
        
        // Delete conversations older than 90 days
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $conversations_table WHERE created_at < %s",
            date('Y-m-d H:i:s', strtotime('-90 days'))
        ));
        
        // Delete orphaned messages
        $wpdb->query("DELETE m FROM $messages_table m LEFT JOIN $conversations_table c ON m.conversation_id = c.id WHERE c.id IS NULL");
    }
}

// Initialize the plugin
function cs_chatbot_init() {
    CSChatbotProfessional::getInstance();
}

// Frontend tracking function
function cs_chatbot_track_interaction() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cs_chatbot_nonce')) {
        wp_die('Security check failed');
    }
    
    $interaction_type = sanitize_text_field($_POST['type'] ?? '');
    $data = $_POST['data'] ?? [];
    
    // Log interaction
    error_log('CS Chatbot Interaction: ' . $interaction_type . ' - ' . wp_json_encode($data));
    
    wp_send_json_success(['status' => 'logged']);
}

// Hook the tracking function
add_action('wp_ajax_nopriv_cs_chatbot_track_interaction', 'cs_chatbot_track_interaction');
add_action('wp_ajax_cs_chatbot_track_interaction', 'cs_chatbot_track_interaction');

// Initialize plugin
cs_chatbot_init();