# RVS AI Setup Guide

## AI Provider Configuration Required

The RVS plugin **requires an AI provider** to be configured to generate content. Without AI configuration, the plugin will show "AI not configured" warnings and no content will be generated.

To enable AI-powered content generation, follow these steps:

## Option 1: Using OpenAI (Recommended for Testing)

### Step 1: Get an OpenAI API Key

1. Go to [OpenAI Platform](https://platform.openai.com/)
2. Sign up or log in
3. Navigate to **API Keys** section
4. Click **Create new secret key**
5. Copy the key (starts with `sk-...`)

### Step 2: Configure in Moodle

1. Log in to Moodle as **administrator**
2. Navigate to:
   ```
   Site Administration → Plugins → Activity modules → RVS AI Learning Suite
   ```

3. Set the following:
   ```
   Default AI Provider: openai
   API Key: sk-your-actual-api-key-here
   API Endpoint: https://api.openai.com/v1
   ```

4. Click **Save changes**

### Step 3: Test the Configuration

1. Go to an existing RVS activity or create a new one
2. Click **Regenerate All Content**
3. Wait for cron to process (or run manually: `php admin/cli/cron.php`)
4. Check the generated content

## Option 2: Using Moodle's AI Subsystem (Moodle 4.4+)

If your Moodle installation has the AI subsystem configured:

1. Navigate to:
   ```
   Site Administration → AI → AI Providers
   ```

2. Configure your preferred AI provider there

3. In RVS settings, set:
   ```
   Default AI Provider: [name of your configured provider]
   ```

## Current Status Without AI Configuration

Without AI configured:
- ⚠️ **No content will be generated**
- ⚠️ **Warning messages will be displayed**
- ⚠️ **Users will see "AI not configured" alerts**
- 🔧 **To enable content generation** - Follow Option 1 or 2 above

The plugin **requires** AI to be configured to function properly.  

## Troubleshooting

### Error: "AI provider not configured"

**What it means:** The AI provider hasn't been configured in the plugin settings.

**How to fix:**
1. Configure AI provider in admin settings (see Option 1 above)
2. Set API key and endpoint
3. Regenerate content

### Error: "AI API error"

**Possible causes:**
- Invalid API key
- Network connectivity issues
- API endpoint incorrect
- Rate limits exceeded

**How to fix:**
1. Verify API key is correct
2. Check API endpoint URL
3. Test connectivity:
   ```bash
   curl -H "Authorization: Bearer YOUR_API_KEY" \
        https://api.openai.com/v1/models
   ```

### No content is being generated

**Causes:**
- AI provider not configured
- API key invalid or missing
- Provider not available
- Cron not running

**Solution:**
1. Check admin settings are correct
2. Verify API key is valid
3. Run cron manually: `php admin/cli/cron.php`
4. Check Moodle error logs for details

## API Costs (OpenAI Example)

Be aware of API costs:

- **GPT-3.5-turbo**: ~$0.002 per 1K tokens
- **GPT-4**: ~$0.03 per 1K tokens (input)

**Estimated costs per RVS activity:**
- Typical activity (1000 words source): $0.10 - $0.50
- Large activity (5000+ words): $0.50 - $2.00

Set up billing alerts in your OpenAI account!

## Alternative: Using Local AI Models

To avoid API costs, you can use local AI models:

1. **Set up a local LLM** (e.g., Ollama, LM Studio)
2. **Configure custom endpoint** in RVS settings
3. **Point to your local API** endpoint
4. **No per-request costs** to cloud providers

This requires technical setup but eliminates ongoing costs.

## For Developers

### Check AI Configuration Programmatically

```php
if (\mod_rvs\ai\generator::is_ai_configured()) {
    // AI is ready, can generate content
} else {
    // AI not configured, show warning
}
```

### Custom AI Provider

To add a custom AI provider:

1. Extend the generator class
2. Override `call_ai_api()` method
3. Configure endpoint in settings
4. Ensure it throws proper exceptions on failure

## Summary

| Status | Description | Action Needed |
|--------|-------------|---------------|
| ✅ Installed | Plugin installed successfully | Configure AI provider |
| ⚠️ Not Configured | AI provider not set up | Add API key (required) |
| 🔧 To Use | Content generation requires AI | Follow Option 1 or 2 above |

## Next Steps

1. **Get an API key** from OpenAI or configure Moodle AI subsystem
2. **Configure settings** in Site Admin → Plugins → Activity modules → RVS
3. **Test generation** by clicking "Regenerate All Content" in any RVS activity
4. **Monitor costs** if using paid API services

## Support

- **Documentation**: See README.md
- **Settings**: Site Admin → Plugins → Activity modules → RVS
- **Installation Guide**: See INSTALL.md

---

**Important:** The RVS plugin requires an AI provider to be configured. Without AI configuration, no content will be generated and users will see warning messages. Follow the steps above to enable AI-powered content generation.

