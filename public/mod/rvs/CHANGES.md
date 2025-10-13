# Changelog

All notable changes to the RVS AI Learning Suite plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-10-08

### Added
- Initial release of RVS AI Learning Suite
- AI-powered content generation for 6 different learning modalities:
  - Mind Maps with visual relationship diagrams
  - Podcasts with audio narration scripts
  - Video scripts with visual cues
  - Comprehensive reports with structured analysis
  - Interactive flashcards with difficulty levels
  - Multiple-choice quizzes with explanations
- Automatic content detection from Book and Resource modules
- Event observers for tracking content changes:
  - Book chapter creation and updates
  - Resource module viewing
  - Course module creation
- Background content generation using adhoc tasks
- Tabbed interface for easy navigation between content types
- Download functionality for all generated content types:
  - JSON for mind maps, flashcards, and quizzes
  - Text files for scripts
  - HTML, PDF, and DOCX for reports
- Responsive design with mobile support
- Dark mode compatibility
- Full backup and restore support
- Privacy API compliance (GDPR)
- Accessibility features (WCAG 2.1 Level AA)
- Admin configuration panel with:
  - AI provider settings
  - Content generation parameters
  - Auto-detection preferences
- Activity-level settings for module customization
- Comprehensive documentation and README

### Features
- **AI Integration**: Compatible with Moodle's AI subsystem (4.4+)
- **Extensible Architecture**: Easy to add new content types or AI providers
- **User Capabilities**: Fine-grained permission control
- **Event System**: Automatic content updates when sources change
- **Caching**: Efficient content storage and retrieval
- **Localization**: Full language support (English included)

### Technical Details
- Moodle 4.4+ compatible
- PHP 8.0+ compatible
- Modern JavaScript (ES6+) with AMD modules
- CSS3 with responsive design
- SVG icons for scalability
- Database schema with proper foreign keys and indexes

### Known Limitations
- Audio generation requires external TTS service (optional)
- Video generation requires external video service (optional)
- AI provider must be configured for content generation
- Background tasks require cron to be running

## [1.1.0] - 2025-10-12

### Added
- **RAG (Retrieval-Augmented Generation) System**
  - Intelligent content chunking with semantic boundaries
  - Token-aware content splitting (1000 tokens per chunk, 100 token overlap)
  - Relevance-based chunk retrieval for each generation task
  - Automatic fallback for small content (< 2000 tokens)
  - Context preservation across chunks

- **Content Extraction Framework**
  - PDF text extraction using smalot/pdfparser library
  - Word document extraction (.docx, .doc) using phpoffice/phpword
  - Plain text file extraction (.txt, .md, etc.)
  - Book module content extraction with chapter ordering
  - HTML to text conversion with structure preservation
  - Image description extraction (alt text and captions)

- **New PHP Classes**
  - `\mod_rvs\content\file_extractor` - File content extraction
  - `\mod_rvs\content\book_extractor` - Book content extraction
  - `\mod_rvs\rag\chunker` - Content chunking with semantic boundaries
  - `\mod_rvs\rag\retriever` - Relevance-based chunk retrieval
  - `\mod_rvs\rag\manager` - RAG workflow coordination

- **Enhanced Error Handling**
  - Comprehensive try-catch blocks throughout
  - Detailed error logging with context
  - User-friendly error messages
  - Admin notifications for critical failures
  - Fallback mechanisms for non-critical errors

- **Composer Dependencies**
  - smalot/pdfparser (^2.0) for PDF extraction
  - phpoffice/phpword (^1.0) for Word document processing

### Changed
- **AI Generator Enhancements**
  - All generation methods now use RAG processing
  - Improved prompts for each module type
  - Added retry logic with exponential backoff (3 retries)
  - Response validation before storage
  - Better structured output parsing

- **Mind Map Generation**
  - Enhanced hierarchical structure extraction
  - Improved relationship mapping
  - Better JSON structure validation
  - More accurate concept identification

- **Podcast Generation**
  - Improved conversational tone
  - Better narrative flow with intro/main/conclusion structure
  - Speaker labels (HOST:) in output
  - Enhanced script formatting

- **Video Script Generation**
  - Improved visual cue formatting with [VISUAL: ...] tags
  - Better scene structuring
  - Enhanced timing suggestions
  - More detailed storyboard elements

- **Report Generation**
  - Structured sections: Executive Summary, Key Topics, Analysis, Conclusions
  - Improved HTML formatting
  - Better content organization
  - Enhanced readability

- **Flashcard Generation**
  - Improved Q&A pair quality
  - Better difficulty calibration
  - Enhanced key concept identification
  - Validated JSON structure

- **Quiz Generation**
  - Higher quality multiple-choice questions
  - Better distractor options
  - Improved explanations
  - Enhanced difficulty progression

- **Observer Updates**
  - `add_file_content()` now extracts actual file content
  - `add_book_content()` extracts all chapters with proper ordering
  - `update_book_content()` re-extracts content on updates
  - Better error handling and logging

### Fixed
- **Content Extraction Issues**
  - Fixed: File modules not extracting actual content
  - Fixed: Book chapters not being processed in correct order
  - Fixed: HTML tags not being properly stripped from book content
  - Fixed: Image descriptions not being captured
  - Fixed: Character encoding issues in text extraction

- **Generation Quality Issues**
  - Fixed: AI generation failing with large content (now uses RAG)
  - Fixed: Token limit errors causing generation failures
  - Fixed: Poor quality outputs due to content truncation
  - Fixed: Context loss in long documents

- **Module-Specific Fixes**
  - Fixed: Mind map JSON structure validation
  - Fixed: Podcast script formatting inconsistencies
  - Fixed: Video script visual cue formatting
  - Fixed: Report section structure
  - Fixed: Flashcard difficulty assignment
  - Fixed: Quiz question validation

### Improved
- **Logging and Debugging**
  - Added INFO level logs for successful operations
  - Added WARNING level logs for non-critical issues
  - Added ERROR level logs for critical failures
  - Added DEBUG level logs with detailed information
  - Included timing information in generation logs

- **Performance**
  - Optimized content chunking algorithm
  - Improved database queries for content retrieval
  - Better memory management for large documents
  - Reduced API calls through intelligent chunk selection

- **Documentation**
  - Updated README.md with RAG capabilities and troubleshooting
  - Enhanced INSTALL.md with dependency installation instructions
  - Added verification steps for content extraction
  - Improved troubleshooting guides

### Technical Details
- **Database Schema**: No changes (fully backward compatible)
- **API Compatibility**: Maintains compatibility with existing integrations
- **Moodle Version**: Still requires Moodle 4.4+
- **PHP Version**: Still requires PHP 8.0+
- **New Requirements**: Composer for dependency management

### Upgrade Notes

**From 1.0.0 to 1.1.0:**

1. **Install Composer Dependencies** (Required)
   ```bash
   cd /path/to/moodle/mod/rvs
   composer install --no-dev
   ```

2. **No Database Changes**
   - Existing data is fully compatible
   - No manual database updates required
   - Upgrade process is automatic

3. **Regenerate Content** (Optional but Recommended)
   - Existing generated content will continue to work
   - To benefit from RAG improvements, regenerate content:
     - Visit each RVS activity
     - Click "Regenerate All Content"
     - Background tasks will process with new RAG system

4. **Verify Installation**
   - Check composer dependencies: `composer show`
   - Test PDF extraction with a sample file
   - Test Word document extraction
   - Review error logs for any issues

### Breaking Changes
**None** - This release is fully backward compatible with version 1.0.0.

### Security
- No security vulnerabilities fixed in this release
- API keys continue to be stored securely in Moodle configuration
- File extraction uses Moodle's File API for secure access
- Input validation enhanced for extracted content

### Known Issues
- Very large PDFs (500+ pages) may require increased PHP memory limit
- Encrypted or password-protected PDFs cannot be extracted
- Legacy .doc format support is limited (use .docx when possible)
- RAG processing requires sufficient PHP memory (512MB minimum, 1GB recommended)

### Deprecations
**None** - No features or APIs deprecated in this release.

## [Unreleased]

### Planned Features
- Vector embeddings for enhanced RAG retrieval
- Additional AI providers support
- Real-time generation progress indicators
- Content rating and feedback system
- Export to SCORM packages
- Integration with Moodle Workplace
- Advanced analytics and reporting
- Custom prompt templates
- Multi-language content generation
- Collaborative content editing
- Caching of chunked content for performance

---

## Version Support

| Version | Moodle Version | PHP Version | Composer Required | Support Status |
|---------|---------------|-------------|-------------------|----------------|
| 1.1.x   | 4.4+          | 8.0+        | Yes               | Active         |
| 1.0.x   | 4.4+          | 8.0+        | No                | Maintenance    |

## Detailed Upgrade Notes

### From 1.0.0 to 1.1.0

**Prerequisites:**
- Composer must be installed on your server
- PHP memory_limit should be at least 512MB (1GB recommended)
- Backup your database before upgrading

**Step-by-Step Process:**

1. **Backup Everything**
   ```bash
   # Database backup
   mysqldump -u user -p database > backup_v1.0.0.sql
   
   # Plugin backup
   cp -r /path/to/moodle/mod/rvs /path/to/backup/rvs_v1.0.0
   ```

2. **Update Plugin Files**
   ```bash
   cd /path/to/moodle/mod/rvs
   # Replace files with version 1.1.0
   ```

3. **Install Dependencies**
   ```bash
   composer install --no-dev
   ```
   
   Expected output:
   ```
   Installing dependencies from lock file
   - Installing smalot/pdfparser (v2.x.x)
   - Installing phpoffice/phpword (v1.x.x)
   ```

4. **Run Moodle Upgrade**
   - Visit: Site Administration → Notifications
   - Click "Upgrade Moodle database now"
   - Process completes automatically (no database changes)

5. **Verify Installation**
   ```bash
   # Check dependencies
   composer show | grep -E 'pdfparser|phpword'
   
   # Should show both packages installed
   ```

6. **Test Content Extraction**
   - Upload a test PDF to a course
   - Create/edit an RVS activity
   - Verify PDF is detected and content extracted
   - Check error logs for any issues

7. **Regenerate Existing Content (Optional)**
   - Visit existing RVS activities
   - Click "Regenerate All Content"
   - Monitor cron/adhoc tasks
   - Verify improved generation quality

**Rollback Procedure (if needed):**

If you encounter issues:

```bash
# Restore plugin files
rm -rf /path/to/moodle/mod/rvs
cp -r /path/to/backup/rvs_v1.0.0 /path/to/moodle/mod/rvs

# Restore database (if modified)
mysql -u user -p database < backup_v1.0.0.sql

# Clear caches
php admin/cli/purge_caches.php
```

### From Development to 1.0.0
This is the first stable release. No upgrade path needed.

## Security Updates

No security issues reported for version 1.0.0.

## Credits

Developed by RVIBS Team with contributions from the Moodle community.

## License

GPL v3 or later

