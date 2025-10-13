# Implementation Plan

- [x] 1. Set up dependencies and project structure


















  - Install required PHP libraries (pdfparser, phpword) via composer
  - Create new directory structure for content extraction and RAG classes
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

- [x] 2. Implement file content extraction





  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7_


- [x] 2.1 Create file_extractor class with base structure

  - Create `public/mod/rvs/classes/content/file_extractor.php`
  - Implement `extract_content()` method to handle resource module files
  - Implement `is_supported_type()` method to check file MIME types
  - Add error handling and logging for unsupported files
  - _Requirements: 1.1, 1.5, 1.7_

- [x] 2.2 Implement PDF text extraction

  - Implement `extract_from_pdf()` method using pdfparser library
  - Handle multi-page PDFs and extract all text content
  - Add error handling for corrupted or encrypted PDFs
  - Test with sample PDF files
  - _Requirements: 1.2, 1.7_


- [x] 2.3 Implement Word document extraction

  - Implement `extract_from_docx()` method using PhpOffice/PhpWord
  - Handle both .docx and .doc formats
  - Extract text while preserving paragraph structure
  - Add error handling for corrupted documents
  - _Requirements: 1.3, 1.7_


- [x] 2.4 Implement text file extraction

  - Implement `extract_from_text()` method for plain text files
  - Support .txt, .md, and other text formats
  - Handle different character encodings (UTF-8, ASCII, etc.)
  - _Requirements: 1.4, 1.7_


- [x] 2.5 Integrate file extraction into observer

  - Update `classes/observer.php` to use file_extractor
  - Modify `add_file_content()` method to extract actual content
  - Store extracted content in rvs_content table
  - Add logging for extraction success/failure
  - _Requirements: 1.6, 1.7_

- [x] 3. Implement book content extraction





























  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_









- [x] 3.1 Create book_extractor class











  - Create `public/mod/rvs/classes/content/book_extractor.php`
  - Implement `extract_content()` method to process book chapters
  - Retrieve chapters in correct order using pagenum
  - Combine chapter titles and content into formatted text
  - _Requirements: 2.1, 2.2_


- [x] 3.2 Implement HTML to text conversion



  - Implement `html_to_text()` method to strip HTML tags
  - Preserve text structure (paragraphs, line breaks)
  - Handle special HTML entities and convert to text
  - Maintain readability in plain text output
  - _Requirements: 2.3, 2.6_


- [x] 3.3 Implement image description extraction



  - Implement `extract_image_descriptions()` method
  - Extract alt text from img tags
  - Extract figure captions
  - Include image descriptions in content output
  - _Requirements: 2.4_


- [x] 3.4 Integrate book extraction into observer



  - Update `classes/observer.php` to use book_extractor
  - Modify `add_book_content()` and `update_book_content()` methods
  - Ensure proper content extraction on book create/update events
  - Test with sample book modules
  - _Requirements: 2.1, 2.5, 2.6_

- [x] 4. Implement RAG processing layer







  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7_

- [x] 4.1 Create content chunker class


  - Create `public/mod/rvs/classes/rag/chunker.php`
  - Implement `chunk_content()` method with semantic boundaries
  - Implement `estimate_tokens()` method for token counting
  - Implement `find_semantic_boundaries()` to identify paragraphs and sections
  - Use 1000 token chunks with 100 token overlap
  - _Requirements: 3.1, 3.2_

- [x] 4.2 Create content retriever class


  - Create `public/mod/rvs/classes/rag/retriever.php`
  - Implement `retrieve_relevant_chunks()` method with relevance scoring
  - Implement `calculate_relevance()` using keyword matching
  - Implement `combine_chunks()` to merge selected chunks
  - Define task-specific keywords for each module type
  - _Requirements: 3.4, 3.5_

- [x] 4.3 Create RAG manager class


  - Create `public/mod/rvs/classes/rag/manager.php`
  - Implement `process_for_task()` to coordinate RAG workflow
  - Implement `should_use_rag()` to determine if RAG is needed
  - Integrate chunker and retriever components
  - Add fallback to direct content for small texts
  - _Requirements: 3.1, 3.3, 3.6, 3.7_

- [x] 5. Enhance AI generator with RAG integration






  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 4.1, 4.2, 4.3, 4.4, 4.5_

- [x] 5.1 Update generator class structure


  - Modify `classes/ai/generator.php` to integrate RAG manager
  - Add `build_prompt()` method for task-specific prompts
  - Add `validate_response()` method for response validation
  - Add retry logic for API failures (3 retries with exponential backoff)
  - _Requirements: 3.5, 3.6_

- [x] 5.2 Update generate_mindmap method


  - Integrate RAG processing before AI call
  - Improve prompt with specific instructions for hierarchical structure
  - Validate JSON response structure
  - Add error handling for invalid mind map data
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [x] 5.3 Update generate_podcast method


  - Integrate RAG processing for narrative content
  - Improve prompt for conversational tone
  - Structure script with intro, main content, conclusion
  - Add speaker labels (HOST:) in output
  - _Requirements: 5.1, 5.2, 5.3, 5.6_

- [x] 5.4 Update generate_video_script method


  - Integrate RAG processing for visual content
  - Improve prompt with visual cue instructions
  - Format output with [VISUAL: ...] tags
  - Structure script by scenes
  - _Requirements: 6.1, 6.2, 6.3, 6.6_

- [x] 5.5 Update generate_report method


  - Integrate RAG processing for comprehensive content
  - Improve prompt for structured report format
  - Ensure sections: Executive Summary, Key Topics, Analysis, Conclusions
  - Format output with proper HTML headings
  - _Requirements: 7.1, 7.2, 7.3, 7.5_

- [x] 5.6 Update generate_flashcards method


  - Integrate RAG processing for key concepts
  - Improve prompt for Q&A pair generation
  - Validate JSON structure with question, answer, difficulty
  - Ensure configured number of flashcards (default 15)
  - _Requirements: 8.1, 8.2, 8.3, 8.6_

- [x] 5.7 Update generate_quiz method


  - Integrate RAG processing for factual content
  - Improve prompt for multiple-choice questions
  - Validate JSON structure with question, options, correct answer, explanation
  - Ensure 4 options per question with quality distractors
  - Ensure configured number of questions (default 15)
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.8_

- [x] 6. Update generation task to use enhanced generator





  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 5.1, 5.2, 5.3, 5.6, 6.1, 6.2, 6.3, 6.6, 7.1, 7.2, 7.3, 7.5, 8.1, 8.2, 8.3, 8.6, 9.1, 9.2, 9.3, 9.4, 9.8_


- [x] 6.1 Update generate_content task class

  - Modify `classes/task/generate_content.php` to pass rvsid to generator methods
  - Update all generation method calls to use new signatures
  - Improve error handling and logging in task execution
  - Add timing information to log messages
  - _Requirements: 10.1, 10.2, 10.3, 10.6_


- [ ] 6.2 Update task generation methods
  - Update `generate_mindmap()` task method to handle new response format
  - Update `generate_podcast()` task method with improved error handling
  - Update `generate_video()` task method with improved error handling
  - Update `generate_report()` task method with improved error handling
  - Update `generate_flashcards()` task method to validate array structure
  - Update `generate_quiz()` task method to validate array structure
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 5.1, 5.2, 5.3, 5.6, 6.1, 6.2, 6.3, 6.6, 7.1, 7.2, 7.3, 7.5, 8.1, 8.2, 8.3, 8.6, 9.1, 9.2, 9.3, 9.4, 9.8_

- [x] 7. Enhance module view files





  - _Requirements: 4.5, 5.4, 5.5, 6.4, 6.5, 7.4, 8.4, 8.5, 9.5, 9.6, 9.7_


- [x] 7.1 Update mindmap module view

  - Modify `modules/mindmap.php` to handle empty or invalid data
  - Add user-friendly error messages for missing mind map
  - Improve JSON data validation before rendering
  - Ensure proper error display when generation fails
  - _Requirements: 4.3, 4.4, 4.5_


- [x] 7.2 Update podcast module view

  - Modify `modules/podcast.php` to display formatted script
  - Add audio player interface when audio URL is available
  - Show helpful message when audio generation is not enabled
  - Improve error handling for missing podcast data
  - _Requirements: 5.3, 5.4, 5.5, 5.6_


- [x] 7.3 Update video module view

  - Modify `modules/video.php` to display formatted script with visual cues
  - Add video player interface when video URL is available
  - Show helpful message when video generation is not enabled
  - Improve error handling for missing video data
  - _Requirements: 6.3, 6.4, 6.5, 6.6_

- [x] 7.4 Update report module view


  - Modify `modules/report.php` to render HTML formatted report
  - Add download options for multiple formats (HTML, PDF, DOCX)
  - Ensure proper HTML structure rendering
  - Improve error handling for missing report data
  - _Requirements: 7.3, 7.4, 7.5_


- [x] 7.5 Update flashcard module view

  - Modify `modules/flashcard.php` to validate flashcard data
  - Ensure interactive flip functionality works correctly
  - Add difficulty filtering interface
  - Improve error handling for missing flashcard data
  - _Requirements: 8.4, 8.5, 8.6_


- [x] 7.6 Update quiz module view

  - Modify `modules/quiz.php` to validate quiz question data
  - Ensure interactive answer checking works correctly
  - Add difficulty filtering interface
  - Display explanations after answer submission
  - Show final score calculation
  - Improve error handling for missing quiz data
  - _Requirements: 9.5, 9.6, 9.7, 9.8_

- [x] 8. Implement comprehensive error handling and logging







  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6_

- [x] 8.1 Add error handling to content extraction


  - Add try-catch blocks in file_extractor methods
  - Log detailed error messages with file information
  - Return empty string on failure with warning log
  - Add user-friendly error messages in observer
  - _Requirements: 10.1, 10.4_

- [x] 8.2 Add error handling to RAG processing


  - Add try-catch blocks in chunker and retriever
  - Log warnings when RAG processing fails
  - Implement fallback to direct content with truncation
  - Add debugging information for troubleshooting
  - _Requirements: 10.3, 10.4_

- [x] 8.3 Add error handling to AI generation


  - Add try-catch blocks in all generator methods
  - Log API errors with request/response details
  - Implement retry logic with exponential backoff
  - Add validation for AI responses before storage
  - _Requirements: 10.2, 10.4_

- [x] 8.4 Add comprehensive logging throughout

  - Add INFO level logs for successful operations
  - Add WARNING level logs for non-critical issues
  - Add ERROR level logs for critical failures
  - Add DEBUG level logs for detailed troubleshooting
  - Include timing information in generation logs
  - _Requirements: 10.6_

- [x] 8.5 Add admin notifications for critical errors


  - Implement notification system for extraction failures
  - Implement notification system for generation failures
  - Add configuration option to enable/disable notifications
  - Include error details and suggested actions in notifications
  - _Requirements: 10.5_

- [x] 9. Update composer.json and install dependencies






  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7_

- [x] 9.1 Create or update composer.json


  - Create `public/mod/rvs/composer.json` if it doesn't exist
  - Add smalot/pdfparser dependency (^2.0)
  - Add phpoffice/phpword dependency (^1.0)
  - Set proper autoload configuration
  - _Requirements: 1.2, 1.3_

- [x] 9.2 Update plugin to use composer autoloader


  - Add composer autoloader require in version.php or lib.php
  - Ensure autoloader is loaded before using libraries
  - Add fallback error message if dependencies not installed
  - _Requirements: 1.2, 1.3_

- [x] 10. Update language strings




  - _Requirements: 10.4, 10.5_

- [x] 10.1 Add new language strings


  - Add error message strings for content extraction failures
  - Add error message strings for RAG processing issues
  - Add error message strings for AI generation failures
  - Add user-friendly help text for troubleshooting
  - Add strings for new features and functionality
  - Update `lang/en/rvs.php` with all new strings
  - _Requirements: 10.4_

- [x] 11. Update documentation





  - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7_


- [x] 11.1 Update README.md

  - Document new RAG capabilities
  - Document supported file formats for extraction
  - Update troubleshooting section with new error scenarios
  - Add information about composer dependencies
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7_


- [x] 11.2 Update INSTALL.md

  - Add composer installation instructions
  - Document dependency installation steps
  - Add verification steps for content extraction
  - Update configuration instructions
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7_


- [x] 11.3 Update CHANGES.md

  - Document all new features in version 1.1.0
  - List bug fixes and improvements
  - Note any breaking changes (none expected)
  - Add upgrade instructions
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7_

- [ ] 12. Testing and validation
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 4.1, 4.2, 4.3, 4.4, 4.5, 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 7.1, 7.2, 7.3, 7.4, 7.5, 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7, 9.8, 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7_

- [ ] 12.1 Test file content extraction
  - Test PDF extraction with sample files
  - Test DOCX extraction with sample files
  - Test text file extraction
  - Test unsupported file type handling
  - Verify content stored correctly in database
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7_

- [ ] 12.2 Test book content extraction
  - Create test book with multiple chapters
  - Verify chapter order preservation
  - Test HTML to text conversion
  - Test image description extraction
  - Verify content stored correctly in database
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

- [ ] 12.3 Test RAG processing
  - Test chunking with various content sizes
  - Test relevance scoring for different tasks
  - Test chunk retrieval and combination
  - Verify fallback for small content
  - Test error handling in RAG components
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7_

- [ ] 12.4 Test all module generation
  - Test mind map generation with RAG
  - Test podcast generation with RAG
  - Test video generation with RAG
  - Test report generation with RAG
  - Test flashcard generation with RAG
  - Test quiz generation with RAG
  - Verify all outputs are properly formatted
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 5.1, 5.2, 5.3, 5.6, 6.1, 6.2, 6.3, 6.6, 7.1, 7.2, 7.3, 7.5, 8.1, 8.2, 8.3, 8.6, 9.1, 9.2, 9.3, 9.4, 9.8_

- [ ] 12.5 Test module views
  - Test each module view with valid data
  - Test each module view with missing data
  - Test error message display
  - Test interactive features (flashcards, quiz)
  - Test download functionality
  - _Requirements: 4.5, 5.4, 5.5, 6.4, 6.5, 7.4, 8.4, 8.5, 9.5, 9.6, 9.7_

- [ ] 12.6 Test error handling
  - Test extraction failures
  - Test AI API failures
  - Test invalid responses
  - Verify error logging
  - Verify user-friendly error messages
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6_

- [ ] 12.7 Test backward compatibility
  - Verify existing RVS activities still work
  - Verify existing generated content displays correctly
  - Verify settings are preserved
  - Verify backup/restore functionality
  - Test regeneration of existing content
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7_

- [ ] 13. Final integration and deployment preparation
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7_

- [ ] 13.1 Run code quality checks
  - Run PHP linter on all modified files
  - Check Moodle coding standards compliance
  - Verify no syntax errors
  - Check for deprecated function usage
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7_

- [ ] 13.2 Update version number
  - Update version in `version.php` to 1.1.0
  - Update version date
  - Update requires (Moodle version)
  - Update maturity to MATURITY_STABLE
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7_

- [ ] 13.3 Create upgrade script if needed
  - Check if database changes require upgrade script
  - Create `db/upgrade.php` entries if needed
  - Test upgrade from version 1.0.0 to 1.1.0
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7_

- [ ] 13.4 Final verification
  - Clear all caches
  - Test complete workflow end-to-end
  - Verify all features work as expected
  - Check error logs for any issues
  - Verify documentation is complete and accurate
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7_
