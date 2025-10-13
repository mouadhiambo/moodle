# Requirements Document

## Introduction

The RVS (AI Learning Suite) plugin for Moodle is an activity module that generates diverse learning materials from course content using AI. Currently, the plugin has several critical issues that prevent it from functioning correctly:

1. **Incomplete Content Extraction**: The plugin does not properly extract text content from Moodle file modules (PDFs, documents, etc.) and book modules
2. **Missing RAG Implementation**: The plugin lacks a proper Retrieval-Augmented Generation (RAG) system to intelligently process and chunk content before sending to AI
3. **Module Generation Issues**: The six learning modules (mind map, podcast, video, report, flashcards, quiz) may not be generating content correctly due to the above issues

This specification addresses these issues while maintaining all existing functionality of the plugin.

## Requirements

### Requirement 1: Content Extraction from File Modules

**User Story:** As a teacher, I want the RVS plugin to automatically extract text content from uploaded files (PDFs, Word documents, text files) so that AI can generate learning materials from them.

#### Acceptance Criteria

1. WHEN a file module (resource) is detected by the RVS plugin THEN the system SHALL extract text content from supported file formats
2. WHEN a PDF file is processed THEN the system SHALL extract all readable text from the PDF
3. WHEN a Word document (.docx, .doc) is processed THEN the system SHALL extract all text content from the document
4. WHEN a text file (.txt, .md) is processed THEN the system SHALL read and store the file content
5. WHEN an unsupported file format is encountered THEN the system SHALL log a warning and skip the file
6. WHEN file content is extracted THEN the system SHALL store it in the rvs_content table with proper metadata
7. IF file extraction fails THEN the system SHALL log the error and continue processing other files

### Requirement 2: Content Extraction from Book Modules

**User Story:** As a teacher, I want the RVS plugin to properly extract all chapter content from Moodle book modules so that comprehensive learning materials can be generated.

#### Acceptance Criteria

1. WHEN a book module is detected THEN the system SHALL extract content from all chapters in the correct order
2. WHEN extracting book chapters THEN the system SHALL preserve chapter titles and hierarchy
3. WHEN chapter content contains HTML THEN the system SHALL strip HTML tags while preserving text structure
4. WHEN chapter content contains images THEN the system SHALL extract alt text and captions
5. WHEN a book is updated THEN the system SHALL re-extract all content and update the stored data
6. WHEN extracting book content THEN the system SHALL handle special characters and encoding correctly

### Requirement 3: Retrieval-Augmented Generation (RAG) Implementation

**User Story:** As a system, I need to implement RAG techniques to intelligently process large content before sending to AI, ensuring better quality outputs and staying within API token limits.

#### Acceptance Criteria

1. WHEN content is prepared for AI generation THEN the system SHALL chunk content into manageable segments
2. WHEN chunking content THEN the system SHALL use semantic boundaries (paragraphs, sections) rather than arbitrary character limits
3. WHEN content exceeds token limits THEN the system SHALL create embeddings for content chunks
4. WHEN generating AI content THEN the system SHALL retrieve the most relevant chunks based on the generation task
5. WHEN using RAG THEN the system SHALL maintain context across chunks for coherent output
6. WHEN content is small enough THEN the system SHALL send it directly without chunking
7. IF RAG processing fails THEN the system SHALL fall back to truncating content with a warning

### Requirement 4: Mind Map Module Enhancement

**User Story:** As a student, I want to view an interactive mind map that accurately represents the key concepts and relationships from my course materials.

#### Acceptance Criteria

1. WHEN mind map is generated THEN the system SHALL use RAG-processed content to identify key concepts
2. WHEN displaying the mind map THEN the system SHALL show a central topic with hierarchical branches
3. WHEN mind map data is invalid or empty THEN the system SHALL display a helpful error message
4. WHEN mind map is generated THEN the system SHALL create proper JSON structure with nodes and relationships
5. WHEN user views mind map THEN the system SHALL render it using an interactive visualization library

### Requirement 5: Podcast Module Enhancement

**User Story:** As a student, I want to access an audio summary of my course materials in podcast format for auditory learning.

#### Acceptance Criteria

1. WHEN podcast script is generated THEN the system SHALL use RAG-processed content to create a conversational narrative
2. WHEN podcast script is created THEN the system SHALL include introduction, main content, and conclusion
3. WHEN podcast is displayed THEN the system SHALL show the script text
4. IF audio generation is enabled THEN the system SHALL generate audio from the script using TTS
5. WHEN audio is available THEN the system SHALL provide an audio player interface
6. WHEN podcast generation fails THEN the system SHALL display an appropriate error message

### Requirement 6: Video Module Enhancement

**User Story:** As a student, I want to access a video script with visual cues that explains my course materials in video format.

#### Acceptance Criteria

1. WHEN video script is generated THEN the system SHALL use RAG-processed content to create an engaging narrative
2. WHEN video script is created THEN the system SHALL include visual cues in [VISUAL: ...] format
3. WHEN video script is created THEN the system SHALL structure content for video presentation
4. WHEN video is displayed THEN the system SHALL show the formatted script
5. IF video generation is enabled THEN the system SHALL generate video from the script
6. WHEN video generation fails THEN the system SHALL display the script with a helpful message

### Requirement 7: Report Module Enhancement

**User Story:** As a student, I want to read a comprehensive formatted report that summarizes my course materials in a structured document.

#### Acceptance Criteria

1. WHEN report is generated THEN the system SHALL use RAG-processed content to create a comprehensive summary
2. WHEN report is created THEN the system SHALL include sections: Executive Summary, Key Topics, Detailed Analysis, Conclusions
3. WHEN report is displayed THEN the system SHALL format it with proper HTML headings and structure
4. WHEN report is displayed THEN the system SHALL support downloading in multiple formats (HTML, PDF, DOCX)
5. WHEN report generation fails THEN the system SHALL display an appropriate error message

### Requirement 8: Flashcards Module Enhancement

**User Story:** As a student, I want to study with interactive flashcards that test my knowledge of key concepts from the course materials.

#### Acceptance Criteria

1. WHEN flashcards are generated THEN the system SHALL use RAG-processed content to create relevant Q&A pairs
2. WHEN flashcards are generated THEN the system SHALL create the configured number of cards (default 15)
3. WHEN flashcards are created THEN each card SHALL have a question, answer, and difficulty level
4. WHEN flashcards are displayed THEN the system SHALL provide an interactive flip interface
5. WHEN flashcards are displayed THEN the system SHALL allow filtering by difficulty level
6. WHEN flashcard generation fails THEN the system SHALL display an appropriate error message

### Requirement 9: Interactive Quiz Module Enhancement

**User Story:** As a student, I want to test my knowledge with an interactive quiz that provides immediate feedback and explanations.

#### Acceptance Criteria

1. WHEN quiz is generated THEN the system SHALL use RAG-processed content to create relevant questions
2. WHEN quiz is generated THEN the system SHALL create the configured number of questions (default 15)
3. WHEN quiz questions are created THEN each SHALL have 4 options with one correct answer
4. WHEN quiz questions are created THEN each SHALL include an explanation for the correct answer
5. WHEN quiz is displayed THEN the system SHALL provide an interactive interface for answering
6. WHEN user submits an answer THEN the system SHALL provide immediate feedback
7. WHEN quiz is completed THEN the system SHALL display the final score
8. WHEN quiz generation fails THEN the system SHALL display an appropriate error message

### Requirement 10: Error Handling and Logging

**User Story:** As a system administrator, I want comprehensive error handling and logging so I can troubleshoot issues with content generation.

#### Acceptance Criteria

1. WHEN any content extraction fails THEN the system SHALL log detailed error information
2. WHEN AI generation fails THEN the system SHALL log the error with context
3. WHEN RAG processing fails THEN the system SHALL log the failure and use fallback method
4. WHEN errors occur THEN the system SHALL display user-friendly messages to end users
5. WHEN critical errors occur THEN the system SHALL notify administrators via Moodle's notification system
6. WHEN content generation completes THEN the system SHALL log success with timing information

### Requirement 11: Maintain Existing Functionality

**User Story:** As a user of the RVS plugin, I want all existing features to continue working as expected after the fixes are applied.

#### Acceptance Criteria

1. WHEN plugin is updated THEN all existing database tables SHALL remain intact
2. WHEN plugin is updated THEN all existing settings SHALL be preserved
3. WHEN plugin is updated THEN the auto-detection feature SHALL continue to work
4. WHEN plugin is updated THEN the backup/restore functionality SHALL continue to work
5. WHEN plugin is updated THEN all existing capabilities and permissions SHALL remain unchanged
6. WHEN plugin is updated THEN the admin settings interface SHALL remain functional
7. WHEN plugin is updated THEN the regenerate content feature SHALL continue to work
