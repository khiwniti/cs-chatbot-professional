/**
 * CS Chatbot Professional - Frontend JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    class CSChatbotWidget {
        constructor() {
            this.isOpen = false;
            this.isMinimized = false;
            this.conversationId = null;
            this.visitorId = this.generateVisitorId();
            this.messageQueue = [];
            this.isTyping = false;
            this.settings = csChatbotFrontend.settings || {};
            this.strings = csChatbotFrontend.strings || {};
            this.currentLanguage = this.detectLanguage();
            
            this.init();
        }

        init() {
            this.createWidget();
            this.bindEvents();
            this.loadSettings();
            this.startSession();
            
            // Auto-open if enabled
            if (this.settings.auto_open) {
                setTimeout(() => {
                    this.openChat();
                }, this.settings.auto_open_delay || 5000);
            }
        }

        createWidget() {
            // Widget is already rendered by PHP, just show it
            $('#cs-chatbot-widget').show();
        }

        bindEvents() {
            // Toggle chat
            $(document).on('click', '#chatbot-toggle', this.toggleChat.bind(this));
            
            // Window controls
            $(document).on('click', '#minimize-chat', this.minimizeChat.bind(this));
            $(document).on('click', '#close-chat', this.closeChat.bind(this));
            
            // Message sending
            $(document).on('click', '#send-message', this.sendMessage.bind(this));
            $(document).on('keypress', '#message-input', this.handleKeypress.bind(this));
            $(document).on('input', '#message-input', this.handleInput.bind(this));
            
            // Quick actions
            $(document).on('click', '#request-live-agent', this.requestLiveAgent.bind(this));
            $(document).on('click', '#restart-conversation', this.restartConversation.bind(this));
            
            // Auto-resize textarea
            $(document).on('input', '#message-input', this.autoResizeTextarea.bind(this));
            
            // Click outside to close (optional)
            $(document).on('click', (e) => {
                if (this.isOpen && !$(e.target).closest('#cs-chatbot-widget').length) {
                    // Don't auto-close for now
                }
            });
            
            // Prevent form submission on enter in textarea
            $(document).on('keydown', '#message-input', (e) => {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }

        loadSettings() {
            // Apply theme
            if (this.settings.theme) {
                $('#cs-chatbot-widget').addClass(`theme-${this.settings.theme}`);
            }
            
            // Apply position
            if (this.settings.position) {
                $('#cs-chatbot-widget').addClass(`position-${this.settings.position}`);
            }
            
            // Apply primary color
            if (this.settings.primary_color) {
                this.applyPrimaryColor(this.settings.primary_color);
            }
        }

        applyPrimaryColor(color) {
            const style = `
                <style id="cs-chatbot-custom-colors">
                    .chatbot-toggle { background: ${color} !important; }
                    .chatbot-header { background: linear-gradient(135deg, ${color} 0%, ${this.darkenColor(color, 20)} 100%) !important; }
                    .user-message .message-text { background: ${color} !important; }
                    .send-btn { background: ${color} !important; }
                    .send-btn:hover { background: ${this.darkenColor(color, 10)} !important; }
                </style>
            `;
            
            $('#cs-chatbot-custom-colors').remove();
            $('head').append(style);
        }

        darkenColor(color, percent) {
            const num = parseInt(color.replace("#", ""), 16);
            const amt = Math.round(2.55 * percent);
            const R = (num >> 16) - amt;
            const G = (num >> 8 & 0x00FF) - amt;
            const B = (num & 0x0000FF) - amt;
            return "#" + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
                (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
                (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
        }

        generateVisitorId() {
            let visitorId = localStorage.getItem('cs_chatbot_visitor_id');
            if (!visitorId) {
                visitorId = 'visitor_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                localStorage.setItem('cs_chatbot_visitor_id', visitorId);
            }
            return visitorId;
        }

        detectLanguage() {
            // Check URL parameter first
            const urlParams = new URLSearchParams(window.location.search);
            const langParam = urlParams.get('lang');
            if (langParam && (langParam === 'th' || langParam === 'en')) {
                return langParam;
            }

            // Check if auto-detect is enabled
            if (!this.settings.auto_detect_language) {
                return this.settings.default_language || 'en';
            }

            // Check browser language
            const browserLang = navigator.language || navigator.userLanguage;
            if (browserLang && browserLang.toLowerCase().startsWith('th')) {
                return 'th';
            }

            // Check HTML lang attribute
            const htmlLang = document.documentElement.lang;
            if (htmlLang && htmlLang.toLowerCase().startsWith('th')) {
                return 'th';
            }

            // Default to configured language or English
            return this.settings.default_language || 'en';
        }

        getWelcomeMessage() {
            if (this.currentLanguage === 'th' && this.settings.welcome_message_th) {
                return this.settings.welcome_message_th;
            }
            return this.settings.welcome_message || this.strings.welcome || 'Hello! How can I help you today?';
        }

        startSession() {
            // Track page visit
            this.trackEvent('page_visit', {
                url: window.location.href,
                title: document.title,
                referrer: document.referrer
            });
        }

        toggleChat() {
            if (this.isOpen) {
                this.closeChat();
            } else {
                this.openChat();
            }
        }

        openChat() {
            const $window = $('#chatbot-window');
            const $toggle = $('#chatbot-toggle');
            
            $window.show().addClass('show');
            $toggle.addClass('active');
            this.isOpen = true;
            this.isMinimized = false;
            
            // Focus on input
            setTimeout(() => {
                $('#message-input').focus();
            }, 300);
            
            // Track event
            this.trackEvent('chat_opened');
            
            // Clear notification badge
            $('#notification-badge').hide();
            
            // Add welcome message if no messages exist
            if ($('#chat-messages .message').length === 0) {
                this.addMessage(this.getWelcomeMessage(), 'bot');
            }
        }

        closeChat() {
            const $window = $('#chatbot-window');
            const $toggle = $('#chatbot-toggle');
            
            $window.removeClass('show');
            $toggle.removeClass('active');
            
            setTimeout(() => {
                $window.hide();
            }, 300);
            
            this.isOpen = false;
            this.isMinimized = false;
            
            // Track event
            this.trackEvent('chat_closed');
        }

        minimizeChat() {
            const $window = $('#chatbot-window');
            
            $window.removeClass('show');
            this.isMinimized = true;
            this.isOpen = false;
            
            setTimeout(() => {
                $window.hide();
            }, 300);
            
            // Track event
            this.trackEvent('chat_minimized');
        }

        sendMessage() {
            const $input = $('#message-input');
            const message = $input.val().trim();
            
            if (!message) {
                return;
            }
            
            // Clear input
            $input.val('').trigger('input');
            
            // Add user message to chat
            this.addMessage(message, 'user');
            
            // Show typing indicator
            this.showTypingIndicator();
            
            // Send to server
            this.sendMessageToServer(message);
            
            // Track event
            this.trackEvent('message_sent', { message_length: message.length });
        }

        addMessage(message, sender, timestamp = null) {
            const $messages = $('#chatbot-messages');
            const time = timestamp || new Date().toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            const avatar = sender === 'user' ? '👤' : '🤖';
            const messageClass = sender === 'user' ? 'user-message' : 'bot-message';
            
            const messageHtml = `
                <div class="message ${messageClass}">
                    <div class="message-avatar">${avatar}</div>
                    <div class="message-content">
                        <div class="message-text">${this.escapeHtml(message)}</div>
                        <div class="message-time">${time}</div>
                    </div>
                </div>
            `;
            
            $messages.append(messageHtml);
            this.scrollToBottom();
            
            // Play sound if enabled
            if (this.settings.sound_enabled && sender === 'bot') {
                this.playNotificationSound();
            }
        }

        sendMessageToServer(message) {
            const data = {
                action: 'cs_chatbot_send_message',
                nonce: csChatbotFrontend.nonce,
                message: message,
                conversation_id: this.conversationId || 0,
                visitor_id: this.visitorId
            };

            $.post(csChatbotFrontend.ajaxurl, data)
                .done((response) => {
                    this.hideTypingIndicator();
                    
                    if (response.success) {
                        // Update conversation ID
                        this.conversationId = response.data.conversation_id;
                        
                        // Add bot response
                        this.addMessage(response.data.response, 'bot', response.data.timestamp);
                        
                        // Show notification if chat is closed
                        if (!this.isOpen) {
                            this.showNotification();
                        }
                    } else {
                        this.addMessage(this.strings.error, 'bot');
                    }
                })
                .fail(() => {
                    this.hideTypingIndicator();
                    this.addMessage(this.strings.error, 'bot');
                });
        }

        showTypingIndicator() {
            if (!this.settings.typing_indicator) {
                return;
            }
            
            $('#typing-indicator').show();
            this.isTyping = true;
            this.scrollToBottom();
        }

        hideTypingIndicator() {
            $('#typing-indicator').hide();
            this.isTyping = false;
        }

        requestLiveAgent() {
            if (!this.conversationId) {
                this.addMessage(this.strings.error, 'bot');
                return;
            }
            
            const data = {
                action: 'cs_chatbot_start_live_chat',
                nonce: csChatbotFrontend.nonce,
                conversation_id: this.conversationId,
                visitor_id: this.visitorId
            };

            $.post(csChatbotFrontend.ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.addMessage(response.data.message, 'bot');
                    } else {
                        this.addMessage(this.strings.offline, 'bot');
                    }
                })
                .fail(() => {
                    this.addMessage(this.strings.error, 'bot');
                });
            
            // Track event
            this.trackEvent('live_agent_requested');
        }

        restartConversation() {
            // Clear messages
            $('#chatbot-messages').empty();
            
            // Reset conversation
            this.conversationId = null;
            
            // Add welcome message
            this.addMessage(this.getWelcomeMessage(), 'bot');
            
            // Track event
            this.trackEvent('conversation_restarted');
        }

        handleKeypress(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        }

        handleInput(e) {
            this.autoResizeTextarea(e);
            
            // Track typing (throttled)
            clearTimeout(this.typingTimeout);
            this.typingTimeout = setTimeout(() => {
                this.trackEvent('user_typing');
            }, 1000);
        }

        autoResizeTextarea(e) {
            const textarea = e.target;
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 100) + 'px';
        }

        scrollToBottom() {
            const $messages = $('#chatbot-messages');
            $messages.scrollTop($messages[0].scrollHeight);
        }

        showNotification() {
            const $badge = $('#notification-badge');
            $badge.text('1').show();
            
            // Flash the toggle button
            $('#chatbot-toggle').addClass('notification-flash');
            setTimeout(() => {
                $('#chatbot-toggle').removeClass('notification-flash');
            }, 1000);
        }

        playNotificationSound() {
            // Create a simple beep sound
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.1);
            } catch (e) {
                // Fallback: no sound
            }
        }

        trackEvent(eventType, data = {}) {
            const eventData = {
                action: 'cs_chatbot_track_interaction',
                nonce: csChatbotFrontend.nonce,
                type: eventType,
                data: {
                    visitor_id: this.visitorId,
                    conversation_id: this.conversationId,
                    timestamp: new Date().toISOString(),
                    url: window.location.href,
                    ...data
                }
            };

            // Send asynchronously without blocking UI
            $.post(csChatbotFrontend.ajaxurl, eventData).fail(() => {
                // Silently fail - don't interrupt user experience
            });
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Public methods for external integration
        openChatWindow() {
            this.openChat();
        }

        closeChatWindow() {
            this.closeChat();
        }

        sendCustomMessage(message) {
            if (message && message.trim()) {
                this.addMessage(message.trim(), 'user');
                this.sendMessageToServer(message.trim());
            }
        }

        addCustomMessage(message, sender = 'bot') {
            if (message && message.trim()) {
                this.addMessage(message.trim(), sender);
            }
        }

        getCurrentConversationId() {
            return this.conversationId;
        }

        getVisitorId() {
            return this.visitorId;
        }
    }

    // Initialize widget when document is ready
    $(document).ready(() => {
        // Only initialize if the widget exists
        if ($('#cs-chatbot-widget').length) {
            window.CSChatbot = new CSChatbotWidget();
        }
    });

    // Add CSS for notification flash effect
    const flashCSS = `
        <style>
            .notification-flash {
                animation: flash 0.5s ease-in-out 2;
            }
            
            @keyframes flash {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }
            
            .chatbot-toggle.notification-flash {
                box-shadow: 0 0 20px rgba(0, 124, 186, 0.6);
            }
        </style>
    `;
    
    $('head').append(flashCSS);

})(jQuery);