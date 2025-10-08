# RVS AI Learning Suite

An intelligent Moodle activity module that leverages AI to automatically generate diverse learning materials from your course content.

## Overview

The RVS (AI Learning Suite) plugin transforms your existing course materials (books, files, resources) into multiple interactive learning formats using artificial intelligence. Students can access the same content through different learning modalities - visual mind maps, audio podcasts, video explanations, comprehensive reports, flashcards, and interactive quizzes.

## Features

### AI-Powered Content Generation

- **Mind Maps**: Visual representation of key concepts and their relationships
- **Podcasts**: Audio narration scripts for auditory learners
- **Video Scripts**: Structured video scripts with visual cues
- **Reports**: Comprehensive written summaries and analyses
- **Flashcards**: Interactive Q&A cards for memorization
- **Quizzes**: Multiple-choice questions with explanations

### Automatic Content Detection

- Automatically detects Book modules in your course
- Detects and processes File/Resource modules
- Triggers content regeneration when source materials are updated
- Configurable auto-detection settings per activity

### User-Friendly Interface

- Tabbed navigation between different content types
- Download options for all generated content
- Responsive design for mobile devices
- Dark mode support
- Accessibility-compliant interface

## Installation

1. Download the plugin files
2. Extract to `{MOODLE_ROOT}/mod/rvs/`
3. Visit Site Administration → Notifications to complete installation
4. Configure AI provider settings in Site Administration → Plugins → Activity modules → RVS AI Learning Suite

## Configuration

### Site-Wide Settings

Navigate to **Site Administration → Plugins → Activity modules → RVS AI Learning Suite**:

#### AI Provider Settings
- **Default AI Provider**: Choose your AI provider (e.g., OpenAI, Anthropic)
- **API Key**: Your AI provider API key
- **API Endpoint**: API endpoint URL

#### Content Generation Settings
- **Maximum Flashcards**: Number of flashcards to generate (default: 15)
- **Maximum Quiz Questions**: Number of quiz questions to generate (default: 15)
- **Enable Audio Generation**: Enable text-to-speech for podcasts
- **Enable Video Generation**: Enable AI video generation (requires additional services)

#### Content Detection Settings
- **Auto-detect New Content**: Automatically detect and add new materials
- **Auto-regenerate Content**: Regenerate content when sources are updated

### Activity Settings

When creating an RVS activity:

1. **General Settings**
   - Activity name
   - Introduction/Description

2. **AI Modules**
   - Enable/disable specific modules:
     - Mind Map
     - Podcast Generation
     - Video Generation
     - Report Generation
     - Flashcard Generation
     - Quiz Generation

3. **Auto-detection Settings**
   - Auto-detect Book modules
   - Auto-detect File modules

## Usage

### For Teachers

1. **Create an RVS Activity**
   - Add activity to course
   - Configure which AI modules to enable
   - Set auto-detection preferences

2. **Add Content Sources**
   - Add Book or Resource modules to your course
   - RVS will automatically detect them if auto-detection is enabled
   - Or trigger manual content detection

3. **Generate AI Content**
   - Click "Regenerate All Content" to queue generation
   - Content generation happens in background
   - Check back in a few minutes for results

4. **Review and Share**
   - Review generated content
   - Download in various formats
   - Students can access all formats

### For Students

1. **Access RVS Activity**
   - Click on the RVS activity in your course

2. **Choose Learning Format**
   - **Overview**: See all available formats
   - **Mind Map**: Visual concept relationships
   - **Podcast**: Listen to audio explanation
   - **Video**: Watch video script/recording
   - **Report**: Read comprehensive summary
   - **Flashcards**: Study with interactive cards
   - **Quiz**: Test your knowledge

3. **Download Content**
   - Download mind maps as JSON
   - Download scripts as text files
   - Download reports as HTML, PDF, or DOCX
   - Download flashcards and quizzes as JSON

## Technical Details

### Database Tables

- `mdl_rvs`: Main activity instances
- `mdl_rvs_content`: Content sources (books, files)
- `mdl_rvs_mindmap`: Generated mind maps
- `mdl_rvs_podcast`: Generated podcasts
- `mdl_rvs_video`: Generated videos
- `mdl_rvs_report`: Generated reports
- `mdl_rvs_flashcard`: Generated flashcards
- `mdl_rvs_quiz`: Generated quiz questions

### Event Observers

The plugin observes the following events:
- `\mod_book\event\chapter_created`
- `\mod_book\event\chapter_updated`
- `\mod_resource\event\course_module_viewed`
- `\core\event\course_module_created`

### Scheduled Tasks

- **Content Generation Task**: Adhoc task that generates AI content
- Runs asynchronously to avoid blocking user interface

### Capabilities

- `mod/rvs:addinstance`: Add new RVS activity
- `mod/rvs:view`: View RVS content
- `mod/rvs:generate`: Generate/regenerate AI content

## AI Integration

The plugin is designed to work with Moodle's AI subsystem (Moodle 4.4+) but includes fallback mechanisms:

1. **Primary**: Uses `\core_ai\ai` if available
2. **Fallback**: Returns placeholder responses with configuration instructions
3. **Extensible**: Easy to integrate custom AI providers

### Supported AI Providers

- OpenAI GPT models
- Anthropic Claude
- Any provider compatible with Moodle's AI subsystem
- Custom providers (via configuration)

## Backup and Restore

The plugin fully supports Moodle's backup and restore functionality:

- All generated content is included in backups
- Content sources are preserved
- Settings are restored correctly
- Links are properly rewritten

## Accessibility

- WCAG 2.1 Level AA compliant
- Keyboard navigation support
- Screen reader compatible
- High contrast mode support
- Semantic HTML structure

## Browser Support

- Chrome/Edge (latest 2 versions)
- Firefox (latest 2 versions)
- Safari (latest 2 versions)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Content Not Generating

1. Check AI provider configuration
2. Verify API key is valid
3. Check cron is running (`php admin/cli/cron.php`)
4. Review error logs

### Auto-detection Not Working

1. Verify auto-detection is enabled in activity settings
2. Check event observers are registered
3. Clear caches (`php admin/cli/purge_caches.php`)

### Display Issues

1. Clear browser cache
2. Purge Moodle caches
3. Check JavaScript console for errors
4. Verify AMD modules are built

## Development

### File Structure

```
mod/rvs/
├── amd/src/              # JavaScript modules
├── backup/moodle2/       # Backup/restore
├── classes/              # PHP classes
│   ├── ai/              # AI generators
│   ├── event/           # Events
│   └── task/            # Tasks
├── db/                   # Database definitions
├── lang/en/             # Language strings
├── modules/             # Module view scripts
├── pix/                 # Icons
├── download.php         # Download handler
├── index.php            # Course view
├── lib.php              # Core functions
├── mod_form.php         # Activity form
├── regenerate.php       # Regeneration handler
├── settings.php         # Admin settings
├── styles.css           # CSS styles
├── version.php          # Version info
└── view.php             # Activity view
```

### Adding Custom AI Providers

1. Extend the `\mod_rvs\ai\generator` class
2. Implement custom `call_ai_api()` method
3. Configure in settings

### Extending Module Types

1. Add database table in `db/install.xml`
2. Create module view in `modules/`
3. Add generation method in `\mod_rvs\ai\generator`
4. Update language strings
5. Add to task generation logic

## Support

- **Documentation**: [Plugin documentation page]
- **Issues**: [GitHub Issues]
- **Discussion**: [Moodle forums]

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

## Credits

- **Copyright**: 2025 RVIBS
- **License**: GPL v3 or later
- **Maintainer**: RVIBS Team

## Changelog

### Version 1.0.0 (2025-10-08)
- Initial release
- Support for 6 AI-generated content types
- Auto-detection of course materials
- Backup/restore support
- Mobile-responsive interface
- Accessibility features

