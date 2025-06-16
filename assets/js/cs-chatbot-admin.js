/**
 * CS Chatbot Professional - Admin JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    class CSChatbotAdmin {
        constructor() {
            this.currentTab = 'dashboard';
            this.charts = {};
            this.init();
        }

        init() {
            this.bindEvents();
            this.initTabs();
            this.loadDashboard();
        }

        bindEvents() {
            // Tab navigation
            $(document).on('click', '.nav-tab', this.handleTabClick.bind(this));
            
            // Dashboard
            $(document).on('change', '#analytics-range', this.handleDateRangeChange.bind(this));
            
            // Conversations
            $(document).on('change', '#conversation-filter', this.loadConversations.bind(this));
            $(document).on('change', '#conversation-date', this.loadConversations.bind(this));
            $(document).on('click', '.view-conversation', this.viewConversation.bind(this));
            $(document).on('click', '#close-conversation-details', this.closeConversationDetails.bind(this));
            $(document).on('click', '#export-conversations', this.exportConversations.bind(this));
            
            // Live Chat
            $(document).on('change', '#agent-online', this.toggleAgentStatus.bind(this));
            $(document).on('click', '.chat-item', this.selectChat.bind(this));
            $(document).on('click', '.quick-response', this.insertQuickResponse.bind(this));
            $(document).on('click', '#send-live-message', this.sendLiveMessage.bind(this));
            $(document).on('keypress', '#live-message-input', this.handleLiveMessageKeypress.bind(this));
            
            // Analytics
            $(document).on('change', '#analytics-range', this.loadAnalytics.bind(this));
            $(document).on('click', '#export-analytics', this.exportAnalytics.bind(this));
            
            // Knowledge Base
            $(document).on('click', '#add-knowledge-item', this.showKnowledgeModal.bind(this));
            $(document).on('click', '#add-category', this.addCategory.bind(this));
            $(document).on('click', '.edit-item', this.editKnowledgeItem.bind(this));
            $(document).on('click', '.delete-item', this.deleteKnowledgeItem.bind(this));
            $(document).on('click', '.category-item', this.selectCategory.bind(this));
            $(document).on('input', '#knowledge-search', this.searchKnowledge.bind(this));
            $(document).on('click', '#search-knowledge-btn', this.searchKnowledge.bind(this));
            
            // Knowledge Modal
            $(document).on('click', '.close-modal', this.closeModal.bind(this));
            $(document).on('click', '#save-knowledge-item', this.saveKnowledgeItem.bind(this));
            $(document).on('click', '#cancel-knowledge-item', this.closeModal.bind(this));
            
            // Settings
            $(document).on('submit', '#cs-chatbot-settings-form', this.saveSettings.bind(this));
            $(document).on('click', '#test-openrouter-api', this.testAPI.bind(this));
            
            // Auto-refresh for live features
            this.startAutoRefresh();
        }

        initTabs() {
            const hash = window.location.hash.substring(1);
            if (hash && $('.nav-tab[href="#' + hash + '"]').length) {
                this.switchTab(hash);
            } else {
                this.switchTab('dashboard');
            }
        }

        handleTabClick(e) {
            e.preventDefault();
            const tabId = $(e.target).attr('href').substring(1);
            this.switchTab(tabId);
            window.location.hash = tabId;
        }

        switchTab(tabId) {
            // Update nav tabs
            $('.nav-tab').removeClass('nav-tab-active');
            $('.nav-tab[href="#' + tabId + '"]').addClass('nav-tab-active');
            
            // Update tab content
            $('.tab-pane').removeClass('active');
            $('#' + tabId).addClass('active');
            
            this.currentTab = tabId;
            
            // Load tab-specific content
            switch (tabId) {
                case 'dashboard':
                    this.loadDashboard();
                    break;
                case 'conversations':
                    this.loadConversations();
                    break;
                case 'live-chat':
                    this.loadLiveChat();
                    break;
                case 'analytics':
                    this.loadAnalytics();
                    break;
                case 'knowledge-base':
                    this.loadKnowledgeBase();
                    break;
            }
        }

        loadDashboard() {
            this.loadDashboardStats();
            this.loadDashboardCharts();
        }

        loadDashboardStats() {
            const data = {
                action: 'cs_chatbot_get_dashboard_stats',
                nonce: csChatbotAjax.nonce
            };

            $.post(csChatbotAjax.ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.updateDashboardStats(response.data);
                    }
                })
                .fail(() => {
                    this.showNotice('error', csChatbotAjax.strings.error);
                });
        }

        updateDashboardStats(stats) {
            // Update dashboard widgets with real data
            $('.dashboard-widget').each(function() {
                const $widget = $(this);
                const $value = $widget.find('h3');
                const text = $widget.find('p').text().toLowerCase();
                
                if (text.includes('conversations')) {
                    $value.text(stats.total_conversations || 0);
                } else if (text.includes('messages')) {
                    $value.text(stats.messages_today || 0);
                } else if (text.includes('accuracy')) {
                    $value.text((stats.ai_accuracy || 85) + '%');
                } else if (text.includes('response')) {
                    $value.text((stats.avg_response_time || 2.5) + 's');
                }
            });
        }

        loadDashboardCharts() {
            // Conversations Over Time Chart
            if ($('#conversationsChart').length) {
                this.createConversationsChart();
            }
            
            // Message Types Chart
            if ($('#messageTypesChart').length) {
                this.createMessageTypesChart();
            }
        }

        createConversationsChart() {
            const ctx = document.getElementById('conversationsChart');
            if (!ctx) return;

            if (this.charts.conversations) {
                this.charts.conversations.destroy();
            }

            this.charts.conversations = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.getLast7Days(),
                    datasets: [{
                        label: 'Conversations',
                        data: [12, 19, 8, 15, 22, 18, 25],
                        borderColor: '#007cba',
                        backgroundColor: 'rgba(0, 124, 186, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        createMessageTypesChart() {
            const ctx = document.getElementById('messageTypesChart');
            if (!ctx) return;

            if (this.charts.messageTypes) {
                this.charts.messageTypes.destroy();
            }

            this.charts.messageTypes = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Bot Responses', 'User Messages', 'Live Agent'],
                    datasets: [{
                        data: [65, 30, 5],
                        backgroundColor: [
                            '#007cba',
                            '#00a32a',
                            '#dba617'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        getLast7Days() {
            const days = [];
            for (let i = 6; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                days.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
            }
            return days;
        }

        handleDateRangeChange(e) {
            const days = $(e.target).val();
            this.loadAnalyticsData(days);
        }

        loadConversations() {
            const filter = $('#conversation-filter').val() || 'all';
            const date = $('#conversation-date').val() || '';

            const data = {
                action: 'cs_chatbot_get_conversations',
                nonce: csChatbotAjax.nonce,
                filter: filter,
                date: date
            };

            $('#conversations-table-body').html('<tr><td colspan="6">Loading...</td></tr>');

            $.post(csChatbotAjax.ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.updateConversationsTable(response.data.conversations);
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                })
                .fail(() => {
                    this.showNotice('error', csChatbotAjax.strings.error);
                });
        }

        updateConversationsTable(conversations) {
            const $tbody = $('#conversations-table-body');
            
            if (!conversations || conversations.length === 0) {
                $tbody.html('<tr><td colspan="6">No conversations found</td></tr>');
                return;
            }

            let html = '';
            conversations.forEach(conversation => {
                const date = new Date(conversation.created_at).toLocaleDateString();
                const time = new Date(conversation.created_at).toLocaleTimeString();
                
                html += `
                    <tr>
                        <td>${conversation.visitor_name || 'Anonymous'}</td>
                        <td>${date} ${time}</td>
                        <td>${conversation.message_count || 0}</td>
                        <td><span class="status-badge status-${conversation.status}">${conversation.status}</span></td>
                        <td>${conversation.agent_name || '-'}</td>
                        <td>
                            <button class="button button-small view-conversation" data-id="${conversation.id}">View</button>
                        </td>
                    </tr>
                `;
            });
            
            $tbody.html(html);
        }

        viewConversation(e) {
            const conversationId = $(e.target).data('id');
            
            const data = {
                action: 'cs_chatbot_get_conversation_details',
                nonce: csChatbotAjax.nonce,
                conversation_id: conversationId
            };

            $.post(csChatbotAjax.ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.showConversationDetails(response.data);
                    }
                })
                .fail(() => {
                    this.showNotice('error', csChatbotAjax.strings.error);
                });
        }

        showConversationDetails(data) {
            const $details = $('#conversation-details');
            const $messages = $('#conversation-messages');
            
            let messagesHtml = '';
            if (data.messages) {
                data.messages.forEach(message => {
                    const time = new Date(message.created_at).toLocaleTimeString();
                    messagesHtml += `
                        <div class="conversation-message ${message.sender}">
                            <div class="message-header">
                                <strong>${message.sender === 'user' ? 'Visitor' : 'Bot'}</strong>
                                <span class="message-time">${time}</span>
                            </div>
                            <div class="message-text">${message.message}</div>
                        </div>
                    `;
                });
            }
            
            $messages.html(messagesHtml);
            $details.show();
        }

        closeConversationDetails() {
            $('#conversation-details').hide();
        }

        exportConversations() {
            const filter = $('#conversation-filter').val() || 'all';
            const date = $('#conversation-date').val() || '';
            
            const url = `${csChatbotAjax.ajaxurl}?action=cs_chatbot_export_conversations&nonce=${csChatbotAjax.nonce}&filter=${filter}&date=${date}`;
            window.open(url, '_blank');
        }

        loadLiveChat() {
            this.loadActiveChatQueue();
        }

        loadActiveChatQueue() {
            const data = {
                action: 'cs_chatbot_get_active_chats',
                nonce: csChatbotAjax.nonce
            };

            $.post(csChatbotAjax.ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.updateChatQueue(response.data.chats);
                    }
                });
        }

        updateChatQueue(chats) {
            const $queue = $('#chat-queue');
            
            if (!chats || chats.length === 0) {
                $queue.html('<div class="no-active-chats">No active chats</div>');
                return;
            }

            let html = '';
            chats.forEach(chat => {
                const time = this.timeAgo(chat.created_at);
                html += `
                    <div class="chat-item" data-id="${chat.id}">
                        <div class="chat-avatar">👤</div>
                        <div class="chat-info">
                            <div class="chat-name">${chat.visitor_name || 'Anonymous'}</div>
                            <div class="chat-time">${time}</div>
                        </div>
                        <div class="chat-status">
                            <span class="status-indicator active"></span>
                        </div>
                    </div>
                `;
            });
            
            $queue.html(html);
        }

        selectChat(e) {
            const $item = $(e.currentTarget);
            const chatId = $item.data('id');
            
            $('.chat-item').removeClass('active');
            $item.addClass('active');
            
            this.loadChatMessages(chatId);
            $('#live-chat-window').show();
        }

        loadChatMessages(chatId) {
            const data = {
                action: 'cs_chatbot_get_chat_messages',
                nonce: csChatbotAjax.nonce,
                chat_id: chatId
            };

            $.post(csChatbotAjax.ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.updateChatMessages(response.data.messages);
                        this.updateVisitorInfo(response.data.visitor);
                    }
                });
        }

        updateChatMessages(messages) {
            const $messages = $('#live-chat-messages');
            
            let html = '';
            messages.forEach(message => {
                const time = new Date(message.created_at).toLocaleTimeString();
                html += `
                    <div class="chat-message ${message.sender}">
                        <div class="message-content">
                            <div class="message-text">${message.message}</div>
                            <div class="message-time">${time}</div>
                        </div>
                    </div>
                `;
            });
            
            $messages.html(html);
            $messages.scrollTop($messages[0].scrollHeight);
        }

        updateVisitorInfo(visitor) {
            $('#visitor-name').text(visitor.name || 'Anonymous');
            $('#visitor-location').text(visitor.location || 'Unknown location');
        }

        toggleAgentStatus(e) {
            const isOnline = $(e.target).is(':checked');
            
            const data = {
                action: 'cs_chatbot_toggle_agent_status',
                nonce: csChatbotAjax.nonce,
                online: isOnline
            };

            $.post(csChatbotAjax.ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                    }
                });
        }

        insertQuickResponse(e) {
            const message = $(e.target).data('message');
            $('#live-message-input').val(message);
        }

        sendLiveMessage() {
            const message = $('#live-message-input').val().trim();
            if (!message) return;

            const chatId = $('.chat-item.active').data('id');
            if (!chatId) return;

            const data = {
                action: 'cs_chatbot_send_live_message',
                nonce: csChatbotAjax.nonce,
                chat_id: chatId,
                message: message
            };

            $.post(csChatbotAjax.ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        $('#live-message-input').val('');
                        this.loadChatMessages(chatId);
                    }
                });
        }

        handleLiveMessageKeypress(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                this.sendLiveMessage();
            }
        }

        loadAnalytics() {
            const days = $('#analytics-range').val() || 30;
            this.loadAnalyticsData(days);
        }

        loadAnalyticsData(days) {
            const data = {
                action: 'cs_chatbot_get_analytics',
                nonce: csChatbotAjax.nonce,
                days: days
            };

            $.post(csChatbotAjax.ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.updateAnalyticsMetrics(response.data);
                        this.updateAnalyticsCharts(response.data);
                    }
                });
        }

        updateAnalyticsMetrics(data) {
            $('#total-conversations').text(data.total_conversations || 0);
            $('#resolution-rate').text((data.resolution_rate || 0) + '%');
            $('#avg-response-time').text((data.avg_response_time || 0) + 's');
            $('#satisfaction-score').text(data.satisfaction_score || 'N/A');
        }

        updateAnalyticsCharts(data) {
            this.createAnalyticsCharts(data);
        }

        createAnalyticsCharts(data) {
            // Conversations Timeline Chart
            if ($('#conversationsTimelineChart').length) {
                const ctx = document.getElementById('conversationsTimelineChart');
                
                if (this.charts.timeline) {
                    this.charts.timeline.destroy();
                }

                this.charts.timeline = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.daily_data ? data.daily_data.map(d => d.date) : [],
                        datasets: [{
                            label: 'Conversations',
                            data: data.daily_data ? data.daily_data.map(d => d.count) : [],
                            borderColor: '#007cba',
                            backgroundColor: 'rgba(0, 124, 186, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // Response Time Chart
            if ($('#responseTimeChart').length) {
                const ctx = document.getElementById('responseTimeChart');
                
                if (this.charts.responseTime) {
                    this.charts.responseTime.destroy();
                }

                this.charts.responseTime = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['< 1s', '1-3s', '3-5s', '5-10s', '> 10s'],
                        datasets: [{
                            label: 'Response Time Distribution',
                            data: [45, 30, 15, 8, 2],
                            backgroundColor: '#007cba'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        }

        exportAnalytics() {
            const days = $('#analytics-range').val() || 30;
            const url = `${csChatbotAjax.ajaxurl}?action=cs_chatbot_export_analytics&nonce=${csChatbotAjax.nonce}&days=${days}`;
            window.open(url, '_blank');
        }

        loadKnowledgeBase() {
            this.loadKnowledgeCategories();
            this.loadKnowledgeItems();
        }

        loadKnowledgeCategories() {
            const data = {
                action: 'cs_chatbot_get_knowledge_categories',
                nonce: csChatbotAjax.nonce
            };

            $.post(csChatbotAjax.ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.updateKnowledgeCategories(response.data.categories);
                    }
                });
        }

        updateKnowledgeCategories(categories) {
            const $list = $('#knowledge-categories');
            
            let html = '';
            categories.forEach(category => {
                html += `
                    <li class="category-item" data-id="${category.id}">
                        <span class="category-name">${category.name}</span>
                        <span class="category-count">(${category.item_count})</span>
                    </li>
                `;
            });
            
            $list.html(html);
        }

        loadKnowledgeItems(categoryId = null, search = null) {
            const data = {
                action: 'cs_chatbot_get_knowledge_items',
                nonce: csChatbotAjax.nonce,
                category_id: categoryId,
                search: search
            };

            $.post(csChatbotAjax.ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.updateKnowledgeItems(response.data.items);
                    }
                });
        }

        updateKnowledgeItems(items) {
            const $list = $('#knowledge-items-list');
            
            if (!items || items.length === 0) {
                $list.html('<div class="no-knowledge-items">No knowledge items found</div>');
                return;
            }

            let html = '';
            items.forEach(item => {
                const shortAnswer = item.answer.length > 100 ? 
                    item.answer.substring(0, 100) + '...' : item.answer;
                
                html += `
                    <div class="knowledge-item" data-id="${item.id}">
                        <div class="item-question">${item.question}</div>
                        <div class="item-answer">${shortAnswer}</div>
                        <div class="item-actions">
                            <button class="button button-small edit-item">Edit</button>
                            <button class="button button-small delete-item">Delete</button>
                        </div>
                    </div>
                `;
            });
            
            $list.html(html);
        }

        selectCategory(e) {
            const $item = $(e.currentTarget);
            const categoryId = $item.data('id');
            
            $('.category-item').removeClass('active');
            $item.addClass('active');
            
            this.loadKnowledgeItems(categoryId);
        }

        searchKnowledge() {
            const search = $('#knowledge-search').val().trim();
            this.loadKnowledgeItems(null, search);
        }

        showKnowledgeModal(itemId = null) {
            const $modal = $('#knowledge-item-modal');
            const $title = $('#modal-title');
            
            if (itemId) {
                $title.text('Edit Knowledge Item');
                this.loadKnowledgeItemForEdit(itemId);
            } else {
                $title.text('Add Knowledge Item');
                this.clearKnowledgeForm();
            }
            
            $modal.show();
        }

        loadKnowledgeItemForEdit(itemId) {
            const data = {
                action: 'cs_chatbot_get_knowledge_item',
                nonce: csChatbotAjax.nonce,
                item_id: itemId
            };

            $.post(csChatbotAjax.ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        const item = response.data.item;
                        $('#item-id').val(item.id);
                        $('#item-question').val(item.question);
                        $('#item-answer').val(item.answer);
                        $('#item-category').val(item.category_id);
                        $('#item-keywords').val(item.keywords);
                        $('#item-active').prop('checked', item.active == 1);
                    }
                });
        }

        clearKnowledgeForm() {
            $('#knowledge-item-form')[0].reset();
            $('#item-id').val('');
            $('#item-active').prop('checked', true);
        }

        editKnowledgeItem(e) {
            const itemId = $(e.target).closest('.knowledge-item').data('id');
            this.showKnowledgeModal(itemId);
        }

        deleteKnowledgeItem(e) {
            if (!confirm(csChatbotAjax.strings.confirm_delete)) {
                return;
            }

            const itemId = $(e.target).closest('.knowledge-item').data('id');
            
            const data = {
                action: 'cs_chatbot_delete_knowledge_item',
                nonce: csChatbotAjax.nonce,
                item_id: itemId
            };

            $.post(csChatbotAjax.ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        this.loadKnowledgeItems();
                        this.loadKnowledgeCategories();
                    }
                });
        }

        saveKnowledgeItem() {
            const formData = {
                action: 'cs_chatbot_save_knowledge_item',
                nonce: csChatbotAjax.nonce,
                item_id: $('#item-id').val(),
                question: $('#item-question').val(),
                answer: $('#item-answer').val(),
                category_id: $('#item-category').val(),
                keywords: $('#item-keywords').val(),
                active: $('#item-active').is(':checked') ? 1 : 0
            };

            $.post(csChatbotAjax.ajaxurl, formData)
                .done((response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        this.closeModal();
                        this.loadKnowledgeItems();
                        this.loadKnowledgeCategories();
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                });
        }

        addCategory() {
            const name = prompt('Enter category name:');
            if (!name) return;

            const data = {
                action: 'cs_chatbot_add_category',
                nonce: csChatbotAjax.nonce,
                name: name
            };

            $.post(csChatbotAjax.ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                        this.loadKnowledgeCategories();
                    }
                });
        }

        closeModal() {
            $('.cs-chatbot-modal').hide();
        }

        saveSettings(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const formData = $form.serialize();
            
            const data = {
                action: 'cs_chatbot_save_settings',
                nonce: csChatbotAjax.nonce,
                settings: formData
            };

            $.post(csChatbotAjax.ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                });
        }

        testAPI() {
            const $button = $('#test-openrouter-api');
            const $result = $('#api-test-result');
            
            $button.prop('disabled', true).text('Testing...');
            $result.html('<div class="notice notice-info"><p>Testing OpenRouter API connection...</p></div>');
            
            const data = {
                action: 'cs_chatbot_test_api',
                nonce: csChatbotAjax.nonce
            };

            $.post(csChatbotAjax.ajaxurl, data)
                .done((response) => {
                    if (response.success) {
                        $result.html(`
                            <div class="notice notice-success">
                                <p><strong>${response.data.message}</strong></p>
                                ${response.data.sample_response ? `<p><em>Sample response:</em> ${response.data.sample_response}</p>` : ''}
                            </div>
                        `);
                    } else {
                        $result.html(`
                            <div class="notice notice-error">
                                <p><strong>${response.data.message}</strong></p>
                            </div>
                        `);
                    }
                })
                .fail((xhr, status, error) => {
                    $result.html(`
                        <div class="notice notice-error">
                            <p><strong>API test failed:</strong> ${error}</p>
                        </div>
                    `);
                })
                .always(() => {
                    $button.prop('disabled', false).text('Test API Connection');
                });
        }

        startAutoRefresh() {
            // Refresh live chat queue every 30 seconds
            setInterval(() => {
                if (this.currentTab === 'live-chat') {
                    this.loadActiveChatQueue();
                }
            }, 30000);

            // Refresh dashboard stats every 60 seconds
            setInterval(() => {
                if (this.currentTab === 'dashboard') {
                    this.loadDashboardStats();
                }
            }, 60000);
        }

        showNotice(type, message) {
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            $('.cs-chatbot-admin').prepend($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $notice.fadeOut(() => $notice.remove());
            }, 5000);
        }

        timeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);
            
            if (diffInSeconds < 60) {
                return 'Just now';
            } else if (diffInSeconds < 3600) {
                const minutes = Math.floor(diffInSeconds / 60);
                return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
            } else if (diffInSeconds < 86400) {
                const hours = Math.floor(diffInSeconds / 3600);
                return `${hours} hour${hours > 1 ? 's' : ''} ago`;
            } else {
                const days = Math.floor(diffInSeconds / 86400);
                return `${days} day${days > 1 ? 's' : ''} ago`;
            }
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new CSChatbotAdmin();
    });

})(jQuery);