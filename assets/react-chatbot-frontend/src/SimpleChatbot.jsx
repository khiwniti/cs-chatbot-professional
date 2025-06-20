import { useState, useEffect, useRef } from 'react';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import { chatConfig as importedChatConfig } from './lib/ai-config.js'; // Renamed to avoid conflict
import { useTextAnimation } from './lib/textAnimation.js';
import './markdown-content.css'; // Corrected path
import './SimpleChatbot.css'; // Ensure this is imported

// WordPress Localized Data
const csData = window.csReactChatbotData || {
  settings: {
    default_language: 'en',
    welcome_message_en: 'Hello! How can I help you today? (Fallback)',
    welcome_message_th: 'สวัสดีค่ะ! ยินดีต้อนรับ มีอะไรให้ช่วยไหมคะ? (Fallback)'
  },
  strings: { error: 'Sorry, I encountered an error. Please try again. (Fallback)' },
  nonce: '',
  ajaxurl: '',
  rest_url: ''
};

const WELCOME_MESSAGES = {
  en: csData.settings.welcome_message_en,
  th: csData.settings.welcome_message_th,
};

// Define Language type if not already globally available
// type Language = 'en' | 'th';

const SimpleChatbot = () => {
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState('');
    const messagesEndRef = useRef(null);
    const { animateElement } = useTextAnimation();

    // Initialize currentLang state using csData
    const [currentLang, setCurrentLang] = useState(
        ['en', 'th'].includes(csData.settings.default_language) ? csData.settings.default_language : 'en'
    );
    const [visitorId, setVisitorId] = useState('');
    const [conversationId, setConversationId] = useState(null);
    const [chatConfig, setChatConfig] = useState(importedChatConfig); // Allow chatConfig to be dynamic

    useEffect(() => {
      let storedVisitorId = localStorage.getItem('cs_chatbot_visitor_id');
      if (!storedVisitorId) {
        storedVisitorId = 'visitor_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);
        localStorage.setItem('cs_chatbot_visitor_id', storedVisitorId);
      }
      setVisitorId(storedVisitorId);
    }, []);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!input.trim() || !visitorId) return;

        const userMessage = { text: input, sender: 'user', id: 'user-' + Date.now() };
        // Add user message to UI immediately
        setMessages(prevMessages => [...prevMessages, userMessage]);
        const currentInput = input; // Capture input before clearing
        setInput('');
        setIsLoading(true);
        setError('');

        try {
            // Filter out any existing welcome messages or initial prompts before sending to backend
            // Also, only send processed messages (text and sender) rather than full component state
            const messagesForBackend = messages
                .filter(msg => msg.id !== 'welcome-msg' && !msg.isClickablePrompt && msg.id !== 'initial-prompt-0')
                .map(msg => ({ text: msg.text, sender: msg.sender, role: msg.sender === 'user' ? 'user' : 'assistant' }));

            const response = await fetch(csData.ajaxurl, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                  action: 'cs_chatbot_send_message',
                  _ajax_nonce: csData.nonce,
                  messages: [...messagesForBackend, { text: currentInput, sender: 'user', role: 'user' }], // Send history and current
                  config: { ...chatConfig, language: currentLang },
                  conversation_id: conversationId || 0,
                  visitor_id: visitorId,
                })
            });

            if (!response.ok) {
                // Try to parse error from backend if available
                let errorMsg = csData.strings.error;
                try {
                    const errorData = await response.json();
                    errorMsg = errorData.data?.message || errorData.message || csData.strings.error;
                } catch (parseError) {
                    // Fallback if parsing error response fails
                    errorMsg = `HTTP error ${response.status}: ${csData.strings.error}`;
                }
                throw new Error(errorMsg);
            }

            const data = await response.json();
            if (data.success && data.data) { // Ensure data.data is present
                const newAiMessage = { text: data.data.content, sender: 'ai', id: data.data.id };
                setMessages(prevMessages => [...prevMessages, newAiMessage]);
                if (data.data.conversation_id) {
                    setConversationId(data.data.conversation_id);
                }
            } else {
                throw new Error(data.data?.message || csData.strings.error);
            }
        } catch (err) {
            console.error("Error during chat submission:", err);
            const errorMessageText = err.message || csData.strings.error;
            setError(errorMessageText);
            // Optionally display the error as a message in the chat
            const errorAiMessage = { text: errorMessageText, sender: 'ai', id: 'error-' + Date.now(), isMarkdown: false };
            setMessages(prevMessages => [...prevMessages, errorAiMessage]);
        } finally {
            setIsLoading(false);
        }
    };

    // Sanitize and render Markdown
    const renderMarkdown = (text) => {
        const rawMarkup = marked(text, { breaks: true, gfm: true });
        return DOMPurify.sanitize(rawMarkup);
    };

    // Handle welcome message and initial prompt using localized data
    useEffect(() => {
        // Ensure messages are empty before adding welcome/initial prompts to avoid duplication on HMR or re-renders
        if (messages.length === 0) {
            if (chatConfig.showWelcomeMessage && WELCOME_MESSAGES[currentLang]) {
                const welcomeMsg = {
                    text: WELCOME_MESSAGES[currentLang],
                    sender: 'ai',
                    id: 'welcome-msg',
                    isMarkdown: chatConfig.parseWelcomeMessageMarkdown
                };
                setMessages(prev => [...prev, welcomeMsg]);
            }
            // Initial prompts logic (can also be localized if needed)
            if (chatConfig.showInitialPrompt && chatConfig.initialPrompts && chatConfig.initialPrompts.length > 0) {
                const firstPrompt = chatConfig.initialPrompts[0]; // Assuming prompts are not language-specific yet
                const promptMsg = {
                    text: firstPrompt,
                    sender: 'ai',
                    id: 'initial-prompt-0',
                    isClickablePrompt: true
                };
                setMessages(prev => [...prev, promptMsg]);
            }
        }
    }, [chatConfig, currentLang, messages.length]); // Depend on messages.length to run only if messages are reset

    const handlePromptClick = async (promptText) => {
        // Add user message (the prompt) to UI immediately
        const userMessage = { text: promptText, sender: 'user', id: 'user-' + Date.now() };
        setMessages(prevMessages =>
            // Remove other prompts and add this one as a user message
            [...prevMessages.filter(msg => !msg.isClickablePrompt && msg.id !== 'initial-prompt-0'), userMessage]
        );

        setIsLoading(true);
        setError('');

        try {
            const messagesForBackend = messages
                .filter(msg => msg.id !== 'welcome-msg' && !msg.isClickablePrompt && msg.id !== 'initial-prompt-0')
                .map(msg => ({ text: msg.text, sender: msg.sender, role: msg.sender === 'user' ? 'user' : 'assistant'  }));

            const response = await fetch(csData.ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'cs_chatbot_send_message',
                    _ajax_nonce: csData.nonce,
                    messages: [...messagesForBackend, { text: promptText, sender: 'user', role: 'user' }],
                    config: { ...chatConfig, language: currentLang },
                    conversation_id: conversationId || 0,
                    visitor_id: visitorId,
                })
            });

            if (!response.ok) {
                let errorMsg = csData.strings.error;
                try {
                    const errorData = await response.json();
                    errorMsg = errorData.data?.message || errorData.message || csData.strings.error;
                } catch (parseError) {
                     errorMsg = `HTTP error ${response.status}: ${csData.strings.error}`;
                }
                throw new Error(errorMsg);
            }

            const data = await response.json();
            if (data.success && data.data) {
                const newAiMessage = { text: data.data.content, sender: 'ai', id: data.data.id };
                setMessages(prevMessages => [...prevMessages, newAiMessage]);
                if (data.data.conversation_id) {
                    setConversationId(data.data.conversation_id);
                }
            } else {
                 throw new Error(data.data?.message || csData.strings.error);
            }
        } catch (err) {
            console.error("Error during prompt click:", err);
            const errorMessageText = err.message || csData.strings.error;
            setError(errorMessageText);
            const errorAiMessage = { text: errorMessageText, sender: 'ai', id: 'error-' + Date.now(), isMarkdown: false };
            setMessages(prevMessages => [...prevMessages, errorAiMessage]);
        } finally {
            setIsLoading(false);
        }
    };


    return (
        <div className="simple-chatbot">
            <div className="chatbot-header">
                <h2>{chatConfig.chatbotName || (currentLang === 'th' ? "แชทบอท" : "Chatbot")}</h2>
            </div>
            <div className="chatbot-messages">
                {messages.map((msg, index) => (
                    <div
                        key={msg.id || index}
                        className={`message ${msg.sender === 'user' ? 'user-message' : 'ai-message'}`}
                        ref={el => {
                            // Animate AI messages
                            if (msg.sender === 'ai' && el && !msg.animated) {
                                animateElement(el);
                                msg.animated = true; // Mark as animated
                            }
                        }}
                    >
                        {msg.isClickablePrompt ? (
                            <button onClick={() => handlePromptClick(msg.text)} className="prompt-button">
                                {msg.text}
                            </button>
                        ) : (
                            msg.isMarkdown || (msg.sender === 'ai' && chatConfig.parseAIMessagesMarkdown) ?
                            <div dangerouslySetInnerHTML={{ __html: renderMarkdown(msg.text) }} /> :
                            msg.text
                        )}
                    </div>
                ))}
                <div ref={messagesEndRef} /> {/* For scrolling to bottom */}
            </div>
            {isLoading && <div className="typing-indicator"><span></span><span></span><span></span></div>}
            {error && <div className="error-message">{error}</div>}
            <form onSubmit={handleSubmit} className="chatbot-input-form">
                <textarea
                    value={input}
                    onChange={(e) => setInput(e.target.value)}
                    placeholder={chatConfig.inputPlaceholder?.[currentLang] || (currentLang === 'th' ? "พิมพ์ข้อความ..." : "Type your message...")}
                    disabled={isLoading}
                    rows={1}
                    onKeyPress={(e) => {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            handleSubmit(e); // Pass event to handleSubmit
                        }
                    }}
                />
                <button type="submit" disabled={isLoading}>
                    {isLoading ? (chatConfig.sendingButtonText?.[currentLang] || (currentLang === 'th' ? 'กำลังส่ง...' : 'Sending...')) : (chatConfig.sendButtonText?.[currentLang] || (currentLang === 'th' ? 'ส่ง' : 'Send'))}
                </button>
            </form>
        </div>
    );
};

export default SimpleChatbot;
// Make sure to define Language type if using TypeScript, e.g.:
// type Language = 'en' | 'th';
// interface Message {
//   id: string;
//   text: string;
//   sender: 'user' | 'ai';
//   isMarkdown?: boolean;
//   isClickablePrompt?: boolean;
//   animated?: boolean; // For animation tracking
//   // Consider role for messages sent to backend if it differs from sender
//   role?: 'user' | 'assistant' | 'system';
//   content?: string; // if role/content is used for backend
// }
