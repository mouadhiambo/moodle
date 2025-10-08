# RVS AI Learning Suite - Installation Guide

## System Requirements

### Minimum Requirements
- Moodle 4.4 or later
- PHP 8.0 or later
- MySQL 8.0+ or PostgreSQL 13+
- Web server (Apache 2.4+ or Nginx 1.18+)
- Cron properly configured for background tasks

### Recommended Requirements
- Moodle 4.5+
- PHP 8.2+
- MySQL 8.0+ or PostgreSQL 15+
- SSL/TLS certificate (for secure API communication)
- At least 512MB PHP memory limit
- Cron running every minute

### External Services (Optional)
- AI Provider API access (OpenAI, Anthropic, etc.)
- Text-to-Speech service (for audio generation)
- Video generation service (for video creation)

## Installation Methods

### Method 1: Manual Installation

1. **Download the Plugin**
   ```bash
   cd /path/to/moodle
   mkdir -p mod/rvs
   # Download and extract plugin files to mod/rvs/
   ```

2. **Set Permissions**
   ```bash
   chown -R www-data:www-data mod/rvs
   chmod -R 755 mod/rvs
   ```

3. **Install via Moodle**
   - Log in as administrator
   - Navigate to: **Site Administration → Notifications**
   - Follow the upgrade prompts
   - Click "Upgrade Moodle database now"

### Method 2: Git Installation

1. **Clone Repository**
   ```bash
   cd /path/to/moodle/mod
   git clone https://github.com/yourusername/moodle-mod_rvs.git rvs
   ```

2. **Complete Installation**
   - Visit your Moodle site as administrator
   - Navigate to **Site Administration → Notifications**
   - Complete the installation process

### Method 3: Plugin Installer

1. **Upload ZIP File**
   - Download the plugin ZIP file
   - Log in to Moodle as administrator
   - Navigate to: **Site Administration → Plugins → Install plugins**
   - Choose the ZIP file and upload
   - Follow installation prompts

## Post-Installation Configuration

### Step 1: Configure AI Provider

1. Navigate to: **Site Administration → Plugins → Activity modules → RVS AI Learning Suite**

2. **AI Provider Settings**
   ```
   Default AI Provider: openai
   API Key: [Your API Key]
   API Endpoint: https://api.openai.com/v1
   ```

3. Click "Save changes"

### Step 2: Configure Content Generation

1. In the same settings page, set:
   ```
   Maximum Flashcards: 15
   Maximum Quiz Questions: 15
   Enable Audio Generation: [Check if TTS available]
   Enable Video Generation: [Check if video service available]
   ```

### Step 3: Configure Auto-detection

1. Set auto-detection preferences:
   ```
   Auto-detect New Content: Yes
   Auto-regenerate Content: No (initially)
   ```

2. Click "Save changes"

### Step 4: Verify Cron Configuration

1. Ensure cron is running:
   ```bash
   # Test cron manually
   php /path/to/moodle/admin/cli/cron.php
   ```

2. Set up automated cron (if not already):
   ```bash
   # Add to crontab
   * * * * * php /path/to/moodle/admin/cli/cron.php
   ```

### Step 5: Configure Permissions

1. Navigate to: **Site Administration → Users → Permissions → Define roles**

2. Verify these capabilities are set correctly:
   - `mod/rvs:addinstance` - Editing teachers, Managers
   - `mod/rvs:view` - All authenticated users
   - `mod/rvs:generate` - Students, Teachers, Managers

3. Adjust as needed for your institution

## Testing the Installation

### 1. Create a Test Activity

1. Go to any course
2. Turn editing on
3. Click "Add an activity or resource"
4. Select "RVS AI Learning Suite"
5. Configure settings:
   - Name: "Test RVS Activity"
   - Enable all modules
   - Enable auto-detection
6. Save and display

### 2. Add Content Sources

1. Add a Book module to the course with some content
2. Verify RVS auto-detects it (check Overview tab)
3. Click "Regenerate All Content"
4. Wait for cron to process (or run manually)

### 3. Verify Generated Content

1. Check each tab:
   - Overview: Shows content sources
   - Mind Map: Displays visual map
   - Podcast: Shows script
   - Video: Shows script
   - Report: Shows report
   - Flashcards: Interactive cards
   - Quiz: Interactive questions

## Troubleshooting

### Installation Issues

**Problem**: Plugin not appearing in notifications
```bash
# Clear caches
php admin/cli/purge_caches.php

# Check file permissions
ls -la mod/rvs/
```

**Problem**: Database errors during installation
- Check database user has CREATE TABLE permissions
- Verify database connection settings in config.php
- Check error logs: `/path/to/moodle/error.log`

### Configuration Issues

**Problem**: AI content not generating
1. Verify API key is correct
2. Check API endpoint is accessible
3. Test connection:
   ```bash
   curl -H "Authorization: Bearer YOUR_API_KEY" \
        https://api.openai.com/v1/models
   ```

**Problem**: Cron not processing tasks
```bash
# Check adhoc tasks
php admin/cli/adhoc_task.php --execute

# View task status
php admin/cli/scheduled_task.php --list
```

### Permission Issues

**Problem**: Students can't generate content
- Check capability `mod/rvs:generate` is granted
- Verify role assignments
- Check context permissions

## Upgrading

### From Development Version

1. **Backup Database**
   ```bash
   mysqldump -u user -p database > backup.sql
   ```

2. **Backup Plugin**
   ```bash
   cp -r mod/rvs mod/rvs.backup
   ```

3. **Update Files**
   ```bash
   cd mod/rvs
   git pull origin main
   # Or replace files manually
   ```

4. **Run Upgrade**
   - Visit **Site Administration → Notifications**
   - Complete upgrade process

### Version-Specific Notes

Check CHANGES.md for version-specific upgrade instructions.

## Uninstallation

### Complete Removal

1. **Via Moodle Interface**
   - Navigate to: **Site Administration → Plugins → Activity modules → RVS AI Learning Suite**
   - Click "Uninstall"
   - Confirm deletion

2. **Manual Removal** (if needed)
   ```bash
   # Remove plugin files
   rm -rf /path/to/moodle/mod/rvs
   
   # Drop database tables
   mysql -u user -p database
   DROP TABLE IF EXISTS mdl_rvs;
   DROP TABLE IF EXISTS mdl_rvs_content;
   DROP TABLE IF EXISTS mdl_rvs_mindmap;
   DROP TABLE IF EXISTS mdl_rvs_podcast;
   DROP TABLE IF EXISTS mdl_rvs_video;
   DROP TABLE IF EXISTS mdl_rvs_report;
   DROP TABLE IF EXISTS mdl_rvs_flashcard;
   DROP TABLE IF EXISTS mdl_rvs_quiz;
   ```

### Preserve Data Backup

Before uninstalling, export all RVS activities:
1. Backup individual courses containing RVS activities
2. Or export data using download functionality

## Security Considerations

### API Key Protection

1. **Never commit API keys** to version control
2. **Store securely** in Moodle config or environment variables
3. **Rotate keys regularly** (quarterly recommended)
4. **Use environment-specific keys** (dev, staging, production)

### File Permissions

```bash
# Secure permissions
find mod/rvs -type f -exec chmod 644 {} \;
find mod/rvs -type d -exec chmod 755 {} \;

# Protect sensitive files
chmod 600 mod/rvs/settings.php
```

### Network Security

1. Use HTTPS for all API communications
2. Configure firewall rules for API endpoints
3. Implement rate limiting for AI API calls
4. Monitor API usage and costs

## Performance Optimization

### Database Indexing

Indexes are created automatically during installation. To verify:
```sql
SHOW INDEX FROM mdl_rvs;
SHOW INDEX FROM mdl_rvs_content;
```

### Caching

1. Enable Moodle caching:
   - **Site Administration → Plugins → Caching → Configuration**
   - Enable application cache
   - Enable session cache

2. Consider external cache stores (Redis, Memcached)

### Task Processing

For high-volume sites:
```bash
# Run adhoc tasks separately
*/5 * * * * php admin/cli/adhoc_task.php

# Increase max execution time in php.ini
max_execution_time = 300
```

## Support Resources

- **Documentation**: See README.md
- **Issue Tracker**: [GitHub Issues URL]
- **Community Forum**: [Moodle Forum URL]
- **Commercial Support**: [Contact information]

## License

GPL v3 or later. See LICENSE file for details.

## Credits

Developed by RVIBS Team
Copyright © 2025 RVIBS

