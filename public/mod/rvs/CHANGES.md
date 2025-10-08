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

## [Unreleased]

### Planned Features
- Additional AI providers support
- Real-time generation progress indicators
- Content rating and feedback system
- Export to SCORM packages
- Integration with Moodle Workplace
- Advanced analytics and reporting
- Custom prompt templates
- Multi-language content generation
- Collaborative content editing

---

## Version Support

| Version | Moodle Version | PHP Version | Support Status |
|---------|---------------|-------------|----------------|
| 1.0.x   | 4.4+          | 8.0+        | Active         |

## Upgrade Notes

### From Development to 1.0.0
This is the first stable release. No upgrade path needed.

## Security Updates

No security issues reported for version 1.0.0.

## Credits

Developed by RVIBS Team with contributions from the Moodle community.

## License

GPL v3 or later

