/**
 * CS Chatbot Professional - Simplified JavaScript for WordPress Playground
 */

(function($) {
    'use strict';
    
    let chatbot = {
        isOpen: false,
        isListening: false,
        recognition: null,
        conversationHistory: [],
        
        init: function() {
            this.bindEvents();
            this.initSuggestions();
            this.initVoiceRecognition();
            
            // Auto-open if enabled
            if (csChatbot.options.auto_open) {
                setTimeout(() => {
                    this.openChat();
                }, parseInt(csChatbot.options.auto_open_delay) || 3000);
            }
            
            // Add welcome message
            this.addMessage(csChatbot.strings.welcome, 'bot');
        },
        
        bindEvents: function() {
            // Toggle chat
            $(document).on('click', '#cs-chatbot-toggle', (e) => {
                e.preventDefault();
                this.toggleChat();
            });
            
            // Close chat
            $(document).on('click', '.cs-chatbot-close', (e) => {
                e.preventDefault();
                this.closeChat();
            });
            
            // Send message
            $(document).on('click', '#cs-chatbot-send', (e) => {
                e.preventDefault();
                this.sendMessage();
            });
            
            // Enter key to send
            $(document).on('keypress', '#cs-chatbot-input', (e) => {
                if (e.which === 13) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
            
            // Voice input
            $(document).on('click', '#cs-chatbot-voice', (e) => {
                e.preventDefault();
                this.toggleVoiceInput();
            });
            
            // Suggestion clicks
            $(document).on('click', '.cs-chatbot-suggestion', (e) => {
                e.preventDefault();
                const text = $(e.target).text();
                $('#cs-chatbot-input').val(text);
                this.sendMessage();
            });
            
            // Keyboard shortcuts
            $(document).on('keydown', (e) => {
                // Ctrl+Shift+C to toggle chat
                if (e.ctrlKey && e.shiftKey && e.key === 'C') {
                    e.preventDefault();
                    this.toggleChat();
                }
                
                // Escape to close chat
                if (e.key === 'Escape' && this.isOpen) {
                    e.preventDefault();
                    this.closeChat();
                }
            });
            
            // Message actions
            $(document).on('click', '.cs-chatbot-copy-btn', (e) => {
                e.preventDefault();
                const message = $(e.target).closest('.cs-chatbot-message').find('.cs-chatbot-message-content').text();
                this.copyToClipboard(message);
            });
            
            $(document).on('click', '.cs-chatbot-like-btn, .cs-chatbot-dislike-btn', (e) => {
                e.preventDefault();
                const isLike = $(e.target).hasClass('cs-chatbot-like-btn');
                this.submitFeedback(isLike ? 'like' : 'dislike');
            });
        },
        
        toggleChat: function() {
            if (this.isOpen) {
                this.closeChat();
            } else {
                this.openChat();
            }
        },
        
        openChat: function() {
            $('.cs-chatbot-widget').addClass('open').show();
            $('#cs-chatbot-toggle').hide();
            this.isOpen = true;
            this.scrollToBottom();
            $('#cs-chatbot-input').focus();
        },
        
        closeChat: function() {
            $('.cs-chatbot-widget').removeClass('open').hide();
            $('#cs-chatbot-toggle').show();
            this.isOpen = false;
        },
        
        sendMessage: function() {
            const input = $('#cs-chatbot-input');
            const message = input.val().trim();
            
            if (!message) return;
            
            // Add user message
            this.addMessage(message, 'user');
            input.val('');
            
            // Show typing indicator
            this.showTypingIndicator();
            
            // Send to API
            this.callChatAPI(message);
        },
        
        addMessage: function(content, type, actions = true) {
            const messagesContainer = $('#cs-chatbot-messages');
            const messageId = 'msg-' + Date.now();
            
            let actionsHtml = '';
            if (actions && type === 'bot') {
                actionsHtml = `
                    <div class="cs-chatbot-message-actions">
                        <button class="cs-chatbot-action-btn cs-chatbot-copy-btn" title="${csChatbot.strings.copy}">📋</button>
                        <button class="cs-chatbot-action-btn cs-chatbot-like-btn" title="${csChatbot.strings.like}">👍</button>
                        <button class="cs-chatbot-action-btn cs-chatbot-dislike-btn" title="${csChatbot.strings.dislike}">👎</button>
                    </div>
                `;
            }
            
            const messageHtml = `
                <div class="cs-chatbot-message ${type}" data-message-id="${messageId}">
                    <div class="cs-chatbot-message-content">${this.escapeHtml(content)}</div>
                    ${actionsHtml}
                </div>
            `;
            
            messagesContainer.append(messageHtml);
            this.scrollToBottom();
            
            // Store in conversation history
            this.conversationHistory.push({
                id: messageId,
                content: content,
                type: type,
                timestamp: new Date().toISOString()
            });
        },
        
        showTypingIndicator: function() {
            const typingHtml = `
                <div class="cs-chatbot-message bot cs-chatbot-typing-message">
                    <div class="cs-chatbot-typing">
                        <div class="cs-chatbot-typing-dot"></div>
                        <div class="cs-chatbot-typing-dot"></div>
                        <div class="cs-chatbot-typing-dot"></div>
                    </div>
                </div>
            `;
            
            $('#cs-chatbot-messages').append(typingHtml);
            this.scrollToBottom();
        },
        
        hideTypingIndicator: function() {
            $('.cs-chatbot-typing-message').remove();
        },
        
        callChatAPI: function(message) {
            const language = this.detectLanguage(message);
            
            $.ajax({
                url: csChatbot.restUrl + 'chat',
                method: 'POST',
                data: {
                    message: message,
                    language: language
                },
                success: (response) => {
                    this.hideTypingIndicator();
                    if (response.success && response.response) {
                        this.addMessage(response.response, 'bot');
                    } else {
                        this.addMessage(csChatbot.strings.error, 'bot', false);
                    }
                },
                error: () => {
                    this.hideTypingIndicator();
                    this.addMessage(csChatbot.strings.error, 'bot', false);
                }
            });
        },
        
        detectLanguage: function(text) {
            // Simple Thai language detection
            const thaiPattern = /[\u0E00-\u0E7F]/;
            return thaiPattern.test(text) ? 'th' : 'en';
        },
        
        initSuggestions: function() {
            if (!csChatbot.options.suggestions_enabled) return;
            
            const suggestions = [
                'How can I help you?',
                'What are your services?',
                'Contact information',
                'Business hours'
            ];
            
            const suggestionsHtml = suggestions.map(suggestion => 
                `<span class="cs-chatbot-suggestion">${this.escapeHtml(suggestion)}</span>`
            ).join('');
            
            $('#cs-chatbot-suggestions').html(suggestionsHtml);
        },
        
        initVoiceRecognition: function() {
            if (!csChatbot.options.voice_enabled || !('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
                $('#cs-chatbot-voice').hide();
                return;
            }
            
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            this.recognition = new SpeechRecognition();
            
            this.recognition.continuous = false;
            this.recognition.interimResults = false;
            this.recognition.lang = 'en-US';
            
            this.recognition.onstart = () => {
                this.isListening = true;
                $('#cs-chatbot-voice').addClass('listening').text('🔴');
            };
            
            this.recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                $('#cs-chatbot-input').val(transcript);
            };
            
            this.recognition.onend = () => {
                this.isListening = false;
                $('#cs-chatbot-voice').removeClass('listening').text('🎤');
            };
            
            this.recognition.onerror = () => {
                this.isListening = false;
                $('#cs-chatbot-voice').removeClass('listening').text('🎤');
                this.showToast('Voice recognition error');
            };
        },
        
        toggleVoiceInput: function() {
            if (!this.recognition) return;
            
            if (this.isListening) {
                this.recognition.stop();
            } else {
                this.recognition.start();
            }
        },
        
        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showToast(csChatbot.strings.copied);
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                this.showToast(csChatbot.strings.copied);
            }
        },
        
        submitFeedback: function(rating) {
            $.ajax({
                url: csChatbot.restUrl + 'feedback',
                method: 'POST',
                data: {
                    rating: rating,
                    message_id: this.conversationHistory[this.conversationHistory.length - 1]?.id
                },
                success: () => {
                    this.showToast('Thank you for your feedback!');
                }
            });
        },
        
        showToast: function(message) {
            const toast = $(`<div class="cs-chatbot-toast">${this.escapeHtml(message)}</div>`);
            $('body').append(toast);
            
            setTimeout(() => toast.addClass('show'), 100);
            setTimeout(() => {
                toast.removeClass('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        },
        
        scrollToBottom: function() {
            const messages = $('#cs-chatbot-messages');
            messages.scrollTop(messages[0].scrollHeight);
        },
        
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if (typeof csChatbot !== 'undefined') {
            chatbot.init();
        }
    });
    
})(jQuery);