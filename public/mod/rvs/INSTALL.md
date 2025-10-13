# RVS AI Learning Suite - Installation Guide

## System Requirements

### Minimum Requirements
- Moodle 4.4 or later
- PHP 8.0 or later
- MySQL 8.0+ or PostgreSQL 13+
- Web server (Apache 2.4+ or Nginx 1.18+)
- **Composer** (for PHP dependency management)
- Cron properly configured for background tasks

### Recommended Requirements
- Moodle 4.5+
- PHP 8.2+
- MySQL 8.0+ or PostgreSQL 15+
- SSL/TLS certificate (for secure API communication)
- At least 512MB PHP memory limit (1GB+ for large documents)
- Cron running every minute

### PHP Extensions Required
- `mbstring` - Multi-byte string support
- `xml` - XML processing
- `zip` - ZIP archive handling
- `gd` or `imagick` - Image processing (optional, for enhanced features)

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

2. **Install Composer Dependencies**
   ```bash
   cd mod/rvs
   composer install --no-dev
   ```
   
   **Note**: If composer is not installed, install it first:
   ```bash
   # On Ubuntu/Debian
   sudo apt-get update
   sudo apt-get install composer
   
   # On CentOS/RHEL
   sudo yum install composer
   
   # Or download directly
   curl -sS https://getcomposer.org/installer | php
   sudo mv composer.phar /usr/local/bin/composer
   ```

3. **Set Permissions**
   ```bash
   chown -R www-data:www-data mod/rvs
   chmod -R 755 mod/rvs
   ```

4. **Install via Moodle**
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

2. **Install Dependencies**
   ```bash
   cd rvs
   composer install --no-dev
   ```

3. **Complete Installation**
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

2. **Install Dependencies After Upload**
   ```bash
   cd /path/to/moodle/mod/rvs
   composer install --no-dev
   ```
   
   **Important**: The plugin installer does not automatically install composer dependencies. You must run composer manually after installation.

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

## Verifying Composer Dependencies

After installation, verify that dependencies are properly installed:

```bash
cd /path/to/moodle/mod/rvs

# Check installed packages
composer show

# Should see:
# smalot/pdfparser    (version)
# phpoffice/phpword   (version)
```

If dependencies are missing:
```bash
composer install --no-dev
```

## Testing the Installation

### 1. Verify Content Extraction

**Test PDF Extraction:**
1. Create a test course
2. Add a File resource with a PDF document
3. Add an RVS activity with auto-detection enabled
4. Check the Overview tab - PDF should be detected
5. Check Moodle logs for extraction success messages

**Test Word Document Extraction:**
1. Add a File resource with a .docx document
2. Verify it appears in RVS Overview tab
3. Check logs for successful extraction

**Test Book Extraction:**
1. Add a Book module with 2-3 chapters
2. Add content to each chapter (text and images)
3. Verify RVS detects the book
4. Check that all chapters are extracted in order

### 2. Create a Test Activity

1. Go to any course
2. Turn editing on
3. Click "Add an activity or resource"
4. Select "RVS AI Learning Suite"
5. Configure settings:
   - Name: "Test RVS Activity"
   - Enable all modules
   - Enable auto-detection
6. Save and display

### 3. Add Content Sources

1. Add a Book module to the course with some content
2. Or upload a PDF/Word document as a File resource
3. Verify RVS auto-detects it (check Overview tab)
4. Verify content is extracted (check database or logs)
5. Click "Regenerate All Content"
6. Wait for cron to process (or run manually)

### 4. Verify Generated Content

1. Check each tab:
   - Overview: Shows content sources with extracted text
   - Mind Map: Displays visual map
   - Podcast: Shows script
   - Video: Shows script
   - Report: Shows report
   - Flashcards: Interactive cards
   - Quiz: Interactive questions

### 5. Test Content Extraction Quality

1. View a content source in the Overview tab
2. Verify extracted text is readable and complete
3. For books: Check that chapter order is preserved
4. For PDFs: Verify text is extracted (not just blank)
5. For Word docs: Verify formatting is reasonably preserved

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

### Dependency Issues

**Problem**: "Class 'Smalot\PdfParser\Parser' not found"
```bash
cd /path/to/moodle/mod/rvs
composer install --no-dev

# Verify installation
composer show smalot/pdfparser
```

**Problem**: "Class 'PhpOffice\PhpWord\IOFactory' not found"
```bash
cd /path/to/moodle/mod/rvs
composer install --no-dev

# Verify installation
composer show phpoffice/phpword
```

**Problem**: Composer command not found
```bash
# Install composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Or use package manager
sudo apt-get install composer  # Ubuntu/Debian
sudo yum install composer      # CentOS/RHEL
```

**Problem**: Composer install fails with memory errors
```bash
# Increase PHP memory limit temporarily
php -d memory_limit=512M /usr/local/bin/composer install --no-dev
```

### Content Extraction Issues

**Problem**: PDF content not extracting
1. Verify composer dependencies: `composer show smalot/pdfparser`
2. Check PDF is not encrypted or password-protected
3. Test with a simple PDF first
4. Check error logs: `/path/to/moodle/error.log`
5. Verify PHP has sufficient memory (512MB+)

**Problem**: Word document extraction fails
1. Verify dependencies: `composer show phpoffice/phpword`
2. Ensure document is .docx format (not .doc)
3. Check file is not corrupted
4. Review error logs for specific errors

**Problem**: Book content not extracting
1. Verify book has chapters with content
2. Check database: `SELECT * FROM mdl_book_chapters WHERE bookid = X`
3. Clear caches and retry
4. Check observer is registered: `php admin/cli/purge_caches.php`

**Problem**: "Content extraction failed" in logs
1. Check file MIME type is supported
2. Verify file permissions are readable
3. Check PHP extensions: `php -m | grep -E 'mbstring|xml|zip'`
4. Increase PHP memory limit in php.ini

### Configuration Issues

**Problem**: AI content not generating
1. Verify API key is correct
2. Check API endpoint is accessible
3. Test connection:
   ```bash
   curl -H "Authorization: Bearer YOUR_API_KEY" \
        https://api.openai.com/v1/models
   ```
4. Verify content extraction is working first
5. Check that extracted content is not empty

**Problem**: Cron not processing tasks
```bash
# Check adhoc tasks
php admin/cli/adhoc_task.php --execute

# View task status
php admin/cli/scheduled_task.php --list

# Check for failed tasks
php admin/cli/adhoc_task.php --execute --failed
```

**Problem**: RAG processing fails
1. Check PHP memory limit (increase to 1GB for large documents)
2. Review error logs for chunking failures
3. Verify content is being extracted properly
4. Test with smaller content first

### Permission Issues

**Problem**: Students can't generate content
- Check capability `mod/rvs:generate` is granted
- Verify role assignments
- Check context permissions

## Upgrading

### From Version 1.0.0 to 1.1.0

Version 1.1.0 adds content extraction and RAG capabilities. Follow these steps:

1. **Backup Database**
   ```bash
   mysqldump -u user -p database > backup_before_1.1.0.sql
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

4. **Install New Dependencies**
   ```bash
   composer install --no-dev
   ```
   
   **Critical**: This step is required for version 1.1.0. The plugin will not work without these dependencies.

5. **Run Upgrade**
   - Visit **Site Administration → Notifications**
   - Complete upgrade process
   - Database schema remains unchanged (backward compatible)

6. **Verify Installation**
   ```bash
   # Check dependencies
   composer show
   
   # Test content extraction
   # Upload a test PDF and verify extraction works
   ```

7. **Regenerate Existing Content (Optional)**
   - Existing RVS activities will continue to work
   - To benefit from new RAG features, regenerate content:
     - Visit each RVS activity
     - Click "Regenerate All Content"
     - Wait for background tasks to complete

### From Development Version

Follow the same steps as upgrading from 1.0.0 to 1.1.0.

### Version-Specific Notes

**Version 1.1.0**:
- **New Dependencies**: Requires composer packages (smalot/pdfparser, phpoffice/phpword)
- **No Database Changes**: Existing data is fully compatible
- **No Breaking Changes**: All existing functionality preserved
- **Enhanced Features**: Content extraction and RAG improve generation quality

Check CHANGES.md for detailed version history.

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

### PHP Configuration

For optimal content extraction and RAG processing:

```ini
# php.ini settings
memory_limit = 1G              # Increased for large documents
max_execution_time = 300       # 5 minutes for generation tasks
upload_max_filesize = 50M      # For large PDF/Word files
post_max_size = 50M
```

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

### Content Extraction Performance

- **PDF Extraction**: ~5 seconds for 50-page document
- **Word Extraction**: ~2 seconds for typical document
- **Book Extraction**: ~1 second per chapter
- **RAG Chunking**: ~2 seconds for 10,000 words

For large documents (100+ pages):
- Increase PHP memory to 2GB
- Consider processing in smaller batches
- Monitor server resources during extraction

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

