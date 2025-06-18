# 🔧 CS Chatbot Professional - Troubleshooting Guide

## WordPress Playground Issues

### 500 Internal Server Error on Activation

**Problem:** Plugin causes 500 error when activating in WordPress Playground.

**Solutions:**

#### 1. Use the Simplified Version
- Download `cs-chatbot-simple.zip` instead of the full version
- This version is specifically optimized for WordPress Playground compatibility
- Reduced complexity and dependencies

#### 2. Check PHP Version Compatibility
- Ensure WordPress Playground is using PHP 7.4 or higher
- Some advanced features require modern PHP versions

#### 3. Memory Limit Issues
- WordPress Playground has limited memory
- The simplified version uses less memory

#### 4. File Structure Issues
- Ensure the plugin folder is named correctly: `cs-chatbot-simple`
- Main plugin file should be: `cs-chatbot-simple.php`

### Installation Steps for WordPress Playground

1. **Download the Simple Version**
   ```
   cs-chatbot-simple.zip
   ```

2. **Upload via WordPress Admin**
   - Go to Plugins → Add New → Upload Plugin
   - Choose `cs-chatbot-simple.zip`
   - Click "Install Now"

3. **Activate Carefully**
   - Click "Activate Plugin"
   - If you get a 500 error, try the manual installation method

4. **Manual Installation (Alternative)**
   - Extract the ZIP file
   - Upload the `cs-chatbot-simple` folder to `/wp-content/plugins/`
   - Activate via WordPress admin

### Configuration for WordPress Playground

1. **Go to Settings → CS Chatbot**
2. **Enable the chatbot**
3. **Add your OpenRouter API key**
   - Get one free at [OpenRouter.ai](https://openrouter.ai)
4. **Choose a theme** (start with "Modern")
5. **Save settings**

## Common Issues & Solutions

### 1. Chatbot Not Appearing

**Symptoms:**
- Plugin activated but no chat widget visible
- No errors in console

**Solutions:**
- Check if chatbot is enabled in settings
- Verify theme compatibility
- Clear browser cache
- Check for JavaScript conflicts

**Debug Steps:**
```javascript
// Open browser console and check:
console.log(typeof csChatbot); // Should not be 'undefined'
console.log(jQuery('#cs-chatbot-container').length); // Should be > 0
```

### 2. API Errors

**Symptoms:**
- Messages not getting responses
- "Something went wrong" errors
- Network errors in console

**Solutions:**
- Verify OpenRouter API key is correct
- Check internet connectivity
- Ensure API key has credits/permissions
- Try a different AI model

**Test API Connection:**
```bash
curl -X POST https://openrouter.ai/api/v1/chat/completions \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"model":"deepseek/deepseek-r1-qwen3-8b","messages":[{"role":"user","content":"Hello"}]}'
```

### 3. Voice Input Not Working

**Symptoms:**
- Voice button not visible
- Voice recognition fails
- Browser permissions denied

**Solutions:**
- Use HTTPS (required for voice recognition)
- Grant microphone permissions
- Use supported browsers (Chrome, Firefox, Safari)
- Check if feature is enabled in settings

### 4. Styling Issues

**Symptoms:**
- Chat widget looks broken
- Overlapping elements
- Mobile display issues

**Solutions:**
- Check for CSS conflicts
- Try different themes
- Clear browser cache
- Disable other plugins temporarily

### 5. REST API Issues

**Symptoms:**
- 404 errors on API endpoints
- CORS errors
- Authentication failures

**Solutions:**
- Flush permalinks (Settings → Permalinks → Save)
- Check .htaccess file
- Verify WordPress REST API is working
- Test with: `yoursite.com/wp-json/chatbot/v1/status`

## WordPress Playground Specific Limitations

### 1. File System Limitations
- Limited file upload size
- Temporary file system (resets on refresh)
- No persistent storage for some features

### 2. Network Limitations
- Some external API calls may be blocked
- CORS restrictions
- Limited bandwidth

### 3. PHP Limitations
- Reduced memory limit
- Some PHP extensions may not be available
- Limited execution time

## Debugging Tools

### 1. Browser Console
```javascript
// Check if plugin loaded
console.log('CS Chatbot loaded:', typeof csChatbot !== 'undefined');

// Check REST API
fetch('/wp-json/chatbot/v1/status')
  .then(r => r.json())
  .then(console.log);

// Test chat API
fetch('/wp-json/chatbot/v1/chat', {
  method: 'POST',
  headers: {'Content-Type': 'application/x-www-form-urlencoded'},
  body: 'message=Hello&language=en'
}).then(r => r.json()).then(console.log);
```

### 2. WordPress Debug Mode
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### 3. Plugin Debug Info
Check Settings → CS Chatbot for:
- API Status
- Feature availability
- Configuration issues

## Performance Optimization

### 1. For WordPress Playground
- Use the simplified version
- Disable unused features
- Choose lightweight themes
- Limit conversation history

### 2. For Production Sites
- Enable caching
- Optimize images
- Use CDN for assets
- Monitor API usage

## Getting Help

### 1. Check Logs
- WordPress error logs
- Browser console errors
- Network tab in developer tools

### 2. Test Environment
- Try in different browsers
- Test on different devices
- Compare with working installation

### 3. Community Support
- GitHub Issues: [Report bugs](https://github.com/khiwniti/cs-chatbot-professional/issues)
- Documentation: Check README.md
- API Documentation: Test endpoints manually

## Version Compatibility

### WordPress Playground Compatible
- `cs-chatbot-simple.php` - Simplified version
- Minimal dependencies
- Reduced memory usage
- Basic features only

### Full Production Version
- `cs-chatbot-complete.php` - Full featured
- All themes and features
- Advanced functionality
- Higher resource requirements

## Quick Fixes

### Reset Plugin Settings
```php
// Add to functions.php temporarily
delete_option('cs_chatbot_options');
```

### Force Refresh Assets
```php
// Add to functions.php temporarily
wp_cache_flush();
```

### Test API Manually
Visit: `yoursite.com/wp-json/chatbot/v1/status`

Expected response:
```json
{
  "status": "active",
  "version": "2.0.0",
  "features": {...},
  "api_configured": true
}
```

---

**Still having issues?** 

1. Try the simplified version first
2. Check all requirements are met
3. Test in a clean WordPress environment
4. Report bugs with detailed error messages

**For WordPress Playground specifically, always use `cs-chatbot-simple.zip`** - it's designed to work within the platform's limitations.