<?php
/**
 * CS Chatbot Professional - Installation Script
 * 
 * @package CSChatbot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Install CS Chatbot Professional plugin
 */
function cs_chatbot_install() {
    global $wpdb;
    
    // Create database tables
    cs_chatbot_create_tables();
    
    // Set default options
    cs_chatbot_set_default_options();
    
    // Schedule cron jobs
    cs_chatbot_schedule_cron_jobs();
    
    // Create upload directories
    cs_chatbot_create_directories();
    
    // Set plugin version
    update_option('cs_chatbot_version', CS_CHATBOT_VERSION);
    
    // Set installation date
    if (!get_option('cs_chatbot_install_date')) {
        update_option('cs_chatbot_install_date', current_time('mysql'));
    }
    
    // Create default knowledge base items
    cs_chatbot_create_default_knowledge();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Create database tables
 */
function cs_chatbot_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Conversations table
    $conversations_table = $wpdb->prefix . 'cs_chatbot_conversations';
    $conversations_sql = "CREATE TABLE $conversations_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        visitor_id varchar(255) NOT NULL,
        visitor_name varchar(255) DEFAULT '',
        visitor_email varchar(255) DEFAULT '',
        visitor_phone varchar(50) DEFAULT '',
        visitor_ip varchar(45) DEFAULT '',
        visitor_user_agent text,
        status varchar(50) DEFAULT 'active',
        agent_id bigint(20) unsigned DEFAULT NULL,
        agent_name varchar(255) DEFAULT '',
        message_count int(11) DEFAULT 0,
        satisfaction_rating tinyint(1) DEFAULT NULL,
        satisfaction_comment text,
        started_at datetime DEFAULT CURRENT_TIMESTAMP,
        ended_at datetime DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY visitor_id (visitor_id),
        KEY status (status),
        KEY agent_id (agent_id),
        KEY started_at (started_at)
    ) $charset_collate;";
    
    // Messages table
    $messages_table = $wpdb->prefix . 'cs_chatbot_messages';
    $messages_sql = "CREATE TABLE $messages_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        conversation_id bigint(20) unsigned NOT NULL,
        message text NOT NULL,
        sender varchar(50) NOT NULL,
        sender_name varchar(255) DEFAULT '',
        message_type varchar(50) DEFAULT 'text',
        attachments text,
        is_read boolean DEFAULT FALSE,
        response_time decimal(10,2) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY conversation_id (conversation_id),
        KEY sender (sender),
        KEY message_type (message_type),
        KEY created_at (created_at),
        FOREIGN KEY (conversation_id) REFERENCES $conversations_table(id) ON DELETE CASCADE
    ) $charset_collate;";
    
    // Knowledge base table
    $knowledge_table = $wpdb->prefix . 'cs_chatbot_knowledge';
    $knowledge_sql = "CREATE TABLE $knowledge_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        question text NOT NULL,
        answer text NOT NULL,
        keywords text,
        category_id bigint(20) unsigned DEFAULT NULL,
        priority int(11) DEFAULT 0,
        usage_count int(11) DEFAULT 0,
        active boolean DEFAULT TRUE,
        created_by bigint(20) unsigned DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY category_id (category_id),
        KEY active (active),
        KEY priority (priority),
        KEY usage_count (usage_count),
        FULLTEXT KEY search_content (question, answer, keywords)
    ) $charset_collate;";
    
    // Categories table
    $categories_table = $wpdb->prefix . 'cs_chatbot_categories';
    $categories_sql = "CREATE TABLE $categories_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        color varchar(7) DEFAULT '#007cba',
        item_count int(11) DEFAULT 0,
        active boolean DEFAULT TRUE,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY name (name),
        KEY active (active)
    ) $charset_collate;";
    
    // Analytics table
    $analytics_table = $wpdb->prefix . 'cs_chatbot_analytics';
    $analytics_sql = "CREATE TABLE $analytics_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        conversation_id bigint(20) unsigned DEFAULT NULL,
        event_type varchar(100) NOT NULL,
        event_data text,
        visitor_id varchar(255) DEFAULT '',
        page_url varchar(500) DEFAULT '',
        referrer varchar(500) DEFAULT '',
        user_agent text,
        ip_address varchar(45) DEFAULT '',
        session_duration int(11) DEFAULT NULL,
        date_recorded date NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY conversation_id (conversation_id),
        KEY event_type (event_type),
        KEY visitor_id (visitor_id),
        KEY date_recorded (date_recorded),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    // Agents table
    $agents_table = $wpdb->prefix . 'cs_chatbot_agents';
    $agents_sql = "CREATE TABLE $agents_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        display_name varchar(255) NOT NULL,
        avatar_url varchar(500) DEFAULT '',
        status varchar(50) DEFAULT 'offline',
        max_concurrent_chats int(11) DEFAULT 5,
        current_chat_count int(11) DEFAULT 0,
        total_conversations int(11) DEFAULT 0,
        avg_response_time decimal(10,2) DEFAULT NULL,
        satisfaction_rating decimal(3,2) DEFAULT NULL,
        last_activity datetime DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_id (user_id),
        KEY status (status),
        KEY last_activity (last_activity)
    ) $charset_collate;";
    
    // Quick responses table
    $quick_responses_table = $wpdb->prefix . 'cs_chatbot_quick_responses';
    $quick_responses_sql = "CREATE TABLE $quick_responses_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        message text NOT NULL,
        category varchar(100) DEFAULT 'general',
        usage_count int(11) DEFAULT 0,
        created_by bigint(20) unsigned DEFAULT NULL,
        active boolean DEFAULT TRUE,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY category (category),
        KEY active (active),
        KEY usage_count (usage_count)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    dbDelta($conversations_sql);
    dbDelta($messages_sql);
    dbDelta($knowledge_sql);
    dbDelta($categories_sql);
    dbDelta($analytics_sql);
    dbDelta($agents_sql);
    dbDelta($quick_responses_sql);
}

/**
 * Set default plugin options
 */
function cs_chatbot_set_default_options() {
    $default_options = [
        // General Settings
        'enable_chatbot' => true,
        'chatbot_name' => 'Assistant',
        'welcome_message' => __('Hello! How can I help you today?', 'cs-chatbot'),
        'widget_position' => 'bottom-right',
        'input_placeholder' => __('Type your message...', 'cs-chatbot'),
        
        // AI Settings
        'openai_api_key' => '',
        'ai_model' => 'gpt-3.5-turbo',
        'ai_personality' => __('You are a helpful customer service assistant. Be friendly, professional, and concise in your responses.', 'cs-chatbot'),
        
        // Appearance
        'widget_theme' => 'modern',
        'primary_color' => '#007cba',
        'widget_size' => 'medium',
        
        // Behavior
        'auto_open_chat' => false,
        'auto_open_delay' => 5,
        'show_typing_indicator' => true,
        'enable_sound' => true,
        
        // Live Chat
        'enable_live_chat' => true,
        'office_hours_start' => '09:00',
        'office_hours_end' => '17:00',
        'offline_message' => __('We are currently offline. Please leave a message and we will get back to you.', 'cs-chatbot'),
        
        // Analytics
        'enable_analytics' => true,
        'enable_weekly_reports' => false,
        'data_retention_days' => 90,
        
        // Advanced
        'enable_knowledge_base' => true,
        'enable_quick_responses' => true,
        'max_message_length' => 1000,
        'rate_limit_messages' => 10,
        'rate_limit_window' => 60
    ];
    
    update_option('cs_chatbot_options', $default_options);
}

/**
 * Schedule cron jobs
 */
function cs_chatbot_schedule_cron_jobs() {
    // Daily analytics sync
    if (!wp_next_scheduled('cs_chatbot_daily_analytics')) {
        wp_schedule_event(time(), 'daily', 'cs_chatbot_daily_analytics');
    }
    
    // Weekly chatbot report
    if (!wp_next_scheduled('cs_chatbot_weekly_report')) {
        wp_schedule_event(time(), 'weekly', 'cs_chatbot_weekly_report');
    }
    
    // Cleanup old sessions (daily)
    if (!wp_next_scheduled('cs_chatbot_cleanup_sessions')) {
        wp_schedule_event(time(), 'daily', 'cs_chatbot_cleanup_sessions');
    }
    
    // Update agent statistics (hourly)
    if (!wp_next_scheduled('cs_chatbot_update_agent_stats')) {
        wp_schedule_event(time(), 'hourly', 'cs_chatbot_update_agent_stats');
    }
}

/**
 * Create necessary directories
 */
function cs_chatbot_create_directories() {
    $upload_dir = wp_upload_dir();
    $cs_chatbot_dir = $upload_dir['basedir'] . '/cs-chatbot';
    
    // Create main directory
    if (!file_exists($cs_chatbot_dir)) {
        wp_mkdir_p($cs_chatbot_dir);
    }
    
    // Create subdirectories
    $subdirs = ['cache', 'exports', 'logs', 'temp', 'attachments'];
    foreach ($subdirs as $subdir) {
        $dir_path = $cs_chatbot_dir . '/' . $subdir;
        if (!file_exists($dir_path)) {
            wp_mkdir_p($dir_path);
        }
        
        // Add index.php to prevent directory browsing
        $index_file = $dir_path . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
    }
    
    // Create .htaccess for security
    $htaccess_file = $cs_chatbot_dir . '/.htaccess';
    if (!file_exists($htaccess_file)) {
        $htaccess_content = "# CS Chatbot Security\n";
        $htaccess_content .= "Options -Indexes\n";
        $htaccess_content .= "<Files *.php>\n";
        $htaccess_content .= "deny from all\n";
        $htaccess_content .= "</Files>\n";
        $htaccess_content .= "<Files *.log>\n";
        $htaccess_content .= "deny from all\n";
        $htaccess_content .= "</Files>\n";
        file_put_contents($htaccess_file, $htaccess_content);
    }
}

/**
 * Create default knowledge base items
 */
function cs_chatbot_create_default_knowledge() {
    global $wpdb;
    
    $categories_table = $wpdb->prefix . 'cs_chatbot_categories';
    $knowledge_table = $wpdb->prefix . 'cs_chatbot_knowledge';
    
    // Create default categories
    $default_categories = [
        [
            'name' => __('General', 'cs-chatbot'),
            'description' => __('General questions and information', 'cs-chatbot'),
            'color' => '#007cba'
        ],
        [
            'name' => __('Support', 'cs-chatbot'),
            'description' => __('Technical support and troubleshooting', 'cs-chatbot'),
            'color' => '#d63638'
        ],
        [
            'name' => __('Sales', 'cs-chatbot'),
            'description' => __('Sales inquiries and product information', 'cs-chatbot'),
            'color' => '#00a32a'
        ],
        [
            'name' => __('Billing', 'cs-chatbot'),
            'description' => __('Billing and payment related questions', 'cs-chatbot'),
            'color' => '#ff8c00'
        ]
    ];
    
    foreach ($default_categories as $category) {
        $wpdb->insert($categories_table, $category);
    }
    
    // Get category IDs
    $general_cat = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $categories_table WHERE name = %s",
        __('General', 'cs-chatbot')
    ));
    
    $support_cat = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $categories_table WHERE name = %s",
        __('Support', 'cs-chatbot')
    ));
    
    // Create default knowledge base items
    $default_knowledge = [
        [
            'question' => __('What are your business hours?', 'cs-chatbot'),
            'answer' => __('Our business hours are Monday to Friday, 9:00 AM to 5:00 PM. We are closed on weekends and public holidays.', 'cs-chatbot'),
            'keywords' => 'hours, time, open, closed, schedule',
            'category_id' => $general_cat,
            'priority' => 10
        ],
        [
            'question' => __('How can I contact support?', 'cs-chatbot'),
            'answer' => __('You can contact our support team through this chat, email us at support@yoursite.com, or call us at (555) 123-4567.', 'cs-chatbot'),
            'keywords' => 'contact, support, help, email, phone',
            'category_id' => $support_cat,
            'priority' => 10
        ],
        [
            'question' => __('Do you offer refunds?', 'cs-chatbot'),
            'answer' => __('Yes, we offer a 30-day money-back guarantee on all our products. Please contact our support team to initiate a refund.', 'cs-chatbot'),
            'keywords' => 'refund, money back, return, guarantee',
            'category_id' => $general_cat,
            'priority' => 8
        ],
        [
            'question' => __('How do I reset my password?', 'cs-chatbot'),
            'answer' => __('To reset your password, go to the login page and click "Forgot Password". Enter your email address and we\'ll send you a reset link.', 'cs-chatbot'),
            'keywords' => 'password, reset, forgot, login, account',
            'category_id' => $support_cat,
            'priority' => 9
        ],
        [
            'question' => __('What payment methods do you accept?', 'cs-chatbot'),
            'answer' => __('We accept all major credit cards (Visa, MasterCard, American Express), PayPal, and bank transfers.', 'cs-chatbot'),
            'keywords' => 'payment, credit card, paypal, visa, mastercard',
            'category_id' => $general_cat,
            'priority' => 7
        ]
    ];
    
    foreach ($default_knowledge as $item) {
        $wpdb->insert($knowledge_table, $item);
    }
    
    // Update category item counts
    $wpdb->query("
        UPDATE $categories_table c 
        SET item_count = (
            SELECT COUNT(*) 
            FROM $knowledge_table k 
            WHERE k.category_id = c.id AND k.active = 1
        )
    ");
}

/**
 * Create custom capabilities
 */
function cs_chatbot_create_capabilities() {
    $admin_role = get_role('administrator');
    $editor_role = get_role('editor');
    
    $capabilities = [
        'manage_cs_chatbot',
        'view_chatbot_conversations',
        'manage_chatbot_knowledge',
        'handle_live_chat',
        'view_chatbot_analytics'
    ];
    
    if ($admin_role) {
        foreach ($capabilities as $cap) {
            $admin_role->add_cap($cap);
        }
    }
    
    if ($editor_role) {
        $editor_role->add_cap('view_chatbot_conversations');
        $editor_role->add_cap('handle_live_chat');
    }
}

/**
 * Insert sample data for testing
 */
function cs_chatbot_insert_sample_data() {
    global $wpdb;
    
    $conversations_table = $wpdb->prefix . 'cs_chatbot_conversations';
    $messages_table = $wpdb->prefix . 'cs_chatbot_messages';
    
    // Only insert sample data if tables are empty
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table");
    
    if ($count == 0) {
        // Insert sample conversations
        $sample_conversations = [
            [
                'visitor_id' => 'visitor_001',
                'visitor_name' => 'John Doe',
                'visitor_email' => 'john@example.com',
                'status' => 'resolved',
                'message_count' => 5,
                'satisfaction_rating' => 5,
                'started_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'ended_at' => date('Y-m-d H:i:s', strtotime('-2 days +10 minutes'))
            ],
            [
                'visitor_id' => 'visitor_002',
                'visitor_name' => 'Jane Smith',
                'visitor_email' => 'jane@example.com',
                'status' => 'active',
                'message_count' => 3,
                'started_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ],
            [
                'visitor_id' => 'visitor_003',
                'visitor_name' => '',
                'visitor_email' => '',
                'status' => 'resolved',
                'message_count' => 2,
                'satisfaction_rating' => 4,
                'started_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'ended_at' => date('Y-m-d H:i:s', strtotime('-1 day +5 minutes'))
            ]
        ];
        
        foreach ($sample_conversations as $conversation) {
            $wpdb->insert($conversations_table, $conversation);
        }
        
        // Insert sample messages
        $sample_messages = [
            [
                'conversation_id' => 1,
                'message' => 'Hello, I need help with my order',
                'sender' => 'user',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'conversation_id' => 1,
                'message' => 'Hello! I\'d be happy to help you with your order. Could you please provide your order number?',
                'sender' => 'bot',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days +1 minute'))
            ],
            [
                'conversation_id' => 2,
                'message' => 'What are your business hours?',
                'sender' => 'user',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ],
            [
                'conversation_id' => 2,
                'message' => 'Our business hours are Monday to Friday, 9:00 AM to 5:00 PM. We are closed on weekends and public holidays.',
                'sender' => 'bot',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour +30 seconds'))
            ]
        ];
        
        foreach ($sample_messages as $message) {
            $wpdb->insert($messages_table, $message);
        }
    }
}

/**
 * Run installation
 */
if (!function_exists('cs_chatbot_install')) {
    // This function is already defined above
}