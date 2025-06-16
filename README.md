# CS Chatbot Professional

A comprehensive WordPress chatbot plugin with AI-powered customer service, live chat, automated responses, and detailed analytics. Built with modern architecture and security best practices.

## 🚀 Features

### 🤖 **AI-Powered Chatbot**
- **Primary API**: OpenRouter (https://openrouter.ai) - Supports multiple AI models
- **Default Model**: DeepSeek R1 Qwen3 8B (Free tier available)
- **Multiple Models**: GPT-3.5, GPT-4o Mini, Claude 3 Haiku, Llama 3.2, Phi-3 Mini
- **Smart Responses**: Context-aware conversations with natural language processing
- **Knowledge Base**: Custom Q&A management
- **Multi-language Support**: Internationalization ready

### 💬 **Live Chat Management**
- **Real-time Chat**: Live agent support
- **Agent Dashboard**: Manage multiple conversations
- **Quick Responses**: Pre-defined message templates
- **Chat Queue**: Organized conversation management
- **Agent Status**: Online/offline availability

### 📊 **Analytics & Reporting**
- **Conversation Analytics**: Track chat performance
- **Response Time Metrics**: Monitor agent efficiency
- **Customer Satisfaction**: Rating and feedback system
- **Export Reports**: CSV export functionality
- **Real-time Dashboard**: Live statistics

### 🎨 **Customizable Widget**
- **Multiple Themes**: Modern, Classic, Minimal, Dark
- **Position Control**: All corners of the screen
- **Size Options**: Small, Medium, Large
- **Color Customization**: Brand color integration
- **Mobile Responsive**: Optimized for all devices

### 🔧 **Advanced Features**
- **Auto-open Chat**: Configurable delay settings
- **Typing Indicators**: Enhanced user experience
- **Sound Notifications**: Audio alerts for new messages
- **Conversation History**: Complete chat logs
- **Knowledge Base Search**: Instant answer lookup

## 📋 **Requirements**

- **WordPress**: 6.0 or higher
- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher
- **Memory**: 128MB minimum (256MB recommended)
- **Disk Space**: 10MB

## 🛠️ **Installation**

### **Method 1: WordPress Admin Upload**
1. Download `cs-chatbot-professional.zip`
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the ZIP file
4. Activate the plugin
5. Configure settings in CS Chatbot admin menu

### **Method 2: Manual Installation**
1. Extract plugin files to `/wp-content/plugins/cs-chatbot-professional/`
2. Activate plugin in WordPress admin
3. Configure settings in CS Chatbot admin page

### **Method 3: Git Clone**
```bash
cd /wp-content/plugins/
git clone https://github.com/your-repo/cs-chatbot-professional.git
```

## ⚙️ **Configuration**

### **Basic Setup**
1. Go to **CS Chatbot** in WordPress admin
2. Navigate to **Settings** tab
3. Configure basic options:
   - Enable/disable chatbot
   - Set chatbot name
   - Configure welcome message
   - Choose widget position and theme

### **AI Configuration**
- **OpenRouter API**: Add your API key from https://openrouter.ai
- **Model Selection**: Choose from free and paid AI models
- **AI Personality**: Customize bot behavior and responses for natural conversations

### **Live Chat Setup**
1. Enable live chat in settings
2. Set office hours
3. Configure offline messages
4. Train agents on the dashboard

### **Knowledge Base**
1. Go to **Knowledge Base** tab
2. Create categories for organization
3. Add questions and answers
4. Use keywords for better matching

## 🎯 **Usage Guide**

### **For Visitors**
- Click the chat widget to start conversation
- Type messages naturally
- Request live agent when needed
- Rate conversation experience

### **For Agents**
- Monitor **Live Chat** tab for active conversations
- Use quick responses for efficiency
- Transfer chats between agents
- Add notes to conversations

### **For Administrators**
- View analytics in **Analytics** tab
- Manage conversations in **Conversations** tab
- Update knowledge base regularly
- Monitor performance metrics

## 🔌 **API Integration**

### **OpenRouter Integration**
- **Endpoint**: https://openrouter.ai/api/v1/chat/completions
- **Authentication**: API key required (get one at https://openrouter.ai)
- **Models Supported**: 
  - **Free Models**: DeepSeek R1 Qwen3 8B, Llama 3.2 3B, Phi-3 Mini
  - **Paid Models**: GPT-3.5 Turbo, GPT-4o Mini, Claude 3 Haiku
- **Response Format**: OpenAI-compatible JSON with conversation context
- **Rate Limits**: Varies by model (free models have generous limits)
- **Cost**: Free models available, paid models based on OpenRouter pricing

## 📊 **Analytics Features**

### **Conversation Metrics**
- Total conversations
- Messages per day
- Response time averages
- Resolution rates

### **Performance Tracking**
- Agent efficiency
- Customer satisfaction scores
- Popular topics
- Peak usage times

### **Export Options**
- CSV conversation exports
- Analytics reports
- Custom date ranges
- Filtered data exports

## 🎨 **Customization**

### **Widget Themes**
- **Modern**: Clean, contemporary design
- **Classic**: Traditional chat interface
- **Minimal**: Simple, lightweight appearance
- **Dark**: Dark mode for modern sites

### **CSS Customization**
```css
/* Custom widget styling */
.cs-chatbot-widget.theme-custom {
    /* Your custom styles */
}
```

### **JavaScript Hooks**
```javascript
// Access chatbot instance
window.CSChatbot.openChatWindow();
window.CSChatbot.sendCustomMessage('Hello!');
```

## 🔒 **Security Features**

- **Nonce Verification**: All AJAX requests protected
- **Capability Checks**: User permission validation
- **Data Sanitization**: Input cleaning and validation
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Output escaping

## 🌐 **Internationalization**

### **Supported Languages**
- English (default)
- Ready for translation
- POT file included
- RTL language support

### **Adding Translations**
1. Use provided POT file
2. Create language-specific PO files
3. Place in `/languages/` directory
4. Activate in WordPress settings

## 🚨 **Troubleshooting**

### **Common Issues**

**Widget Not Appearing**
- Check if chatbot is enabled in settings
- Verify theme compatibility
- Clear cache if using caching plugins

**API Not Working**
- Test API connection in settings
- Check error logs for details
- Verify internet connectivity

**Live Chat Issues**
- Ensure agent is online
- Check office hours settings
- Verify user permissions

### **Debug Mode**
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📞 **Support**

### **Documentation**
- Plugin settings help text
- Inline documentation
- Code comments

### **Community Support**
- GitHub Issues
- WordPress.org forums
- Community discussions

### **Professional Support**
- Priority email support
- Custom development
- Training sessions

## 🔄 **Updates**

### **Automatic Updates**
- WordPress admin notifications
- One-click updates
- Backup recommendations

### **Manual Updates**
- Download latest version
- Replace plugin files
- Activate updated version

## 📝 **Changelog**

### **Version 1.0.0**
- Initial release
- AI-powered chatbot
- Live chat functionality
- Analytics dashboard
- Knowledge base management
- Multi-theme support
- Mobile responsive design

## 🤝 **Contributing**

### **Development**
- Fork the repository
- Create feature branches
- Submit pull requests
- Follow coding standards

### **Bug Reports**
- Use GitHub Issues
- Provide detailed descriptions
- Include error logs
- Specify environment details

## 📄 **License**

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

## 🙏 **Credits**

- **OpenRouter**: AI model routing and API service
- **DeepSeek**: Advanced AI model for natural language processing
- **Chart.js**: Analytics visualization
- **WordPress**: Platform and UI components
- **Contributors**: Community developers

---

**Ready to enhance your customer service? Install CS Chatbot Professional today! 🚀**