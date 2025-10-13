<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English strings for rvs
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'RVS AI Learning Suite';
$string['modulenameplural'] = 'RVS AI Learning Suites';
$string['modulename_help'] = 'The RVS AI Learning Suite uses AI to generate interactive learning content including mind maps, podcasts, videos, reports, flashcards, and quizzes from your course materials.';
$string['pluginadministration'] = 'RVS administration';
$string['pluginname'] = 'RVS AI Learning Suite';

// Capabilities
$string['rvs:addinstance'] = 'Add a new RVS activity';
$string['rvs:view'] = 'View RVS content';
$string['rvs:generate'] = 'Generate AI content';

// Settings
$string['rvsname'] = 'Activity name';
$string['aimodules'] = 'AI Modules';
$string['autodetection'] = 'Auto-detection Settings';

$string['enable_mindmap'] = 'Enable Mind Map';
$string['enable_podcast'] = 'Enable Podcast Generation';
$string['enable_video'] = 'Enable Video Generation';
$string['enable_report'] = 'Enable Report Generation';
$string['enable_flashcard'] = 'Enable Flashcard Generation';
$string['enable_quiz'] = 'Enable Quiz Generation';

$string['auto_detect_books'] = 'Auto-detect Book modules';
$string['auto_detect_files'] = 'Auto-detect File modules';

// Module names
$string['overview'] = 'Overview';
$string['mindmap'] = 'Mind Map';
$string['podcast'] = 'Podcast';
$string['video'] = 'Video Overview';
$string['report'] = 'Report';
$string['flashcard'] = 'Flashcards';
$string['quiz'] = 'Interactive Quiz';

// Content
$string['contentsources'] = 'Content Sources';
$string['nocontentsources'] = 'No content sources detected yet. Add books or files to your course to get started.';
$string['availablemodules'] = 'Available AI Modules';

// Mind Map
$string['nomindmap'] = 'No mind map has been generated yet.';
$string['generatemindmap'] = 'Generate Mind Map';
$string['regeneratemindmap'] = 'Regenerate Mind Map';
$string['downloadmindmap'] = 'Download Mind Map';
$string['mindmapdatainvalid'] = 'The stored mind map data is invalid or incomplete.';
$string['mindmapdatainvalid_help'] = 'Please regenerate the mind map. Ensure there is readable content in the file or book resources located in the same course section as this RVS activity.';

// Podcast
$string['nopodcast'] = 'No podcast has been generated yet.';
$string['generatepodcast'] = 'Generate Podcast';
$string['podcastscript'] = 'Podcast Script';
$string['downloadscript'] = 'Download Script';
$string['downloadaudio'] = 'Download Audio';
$string['audionotgenerated'] = 'Audio has not been generated yet. Regenerate after enabling audio generation in the plugin settings.';
$string['podcastdatamissing'] = 'The podcast script could not be found or is empty.';
$string['regeneratepodcast'] = 'Regenerate Podcast';

// Video
$string['novideo'] = 'No video has been generated yet.';
$string['generatevideo'] = 'Generate Video';
$string['videoscript'] = 'Video Script';
$string['downloadvideo'] = 'Download Video';
$string['videonotgenerated'] = 'Video output has not been generated yet. Generate a script to create the video overview.';
$string['videodatamissing'] = 'The video script could not be found or is empty.';
$string['regeneratevideo'] = 'Regenerate Video';

// Report
$string['noreport'] = 'No report has been generated yet.';
$string['generatereport'] = 'Generate Report';
$string['downloadas'] = 'Download as {$a}';
$string['downloadreportas'] = 'Download report as:';
$string['reportdatamissing'] = 'The report content could not be found or is empty.';
$string['regeneratereport'] = 'Regenerate Report';

// Flashcards
$string['noflashcards'] = 'No flashcards have been generated yet.';
$string['generateflashcards'] = 'Generate Flashcards';
$string['flashcards'] = 'AI Flashcards';
$string['downloadflashcards'] = 'Download Flashcards';
$string['filterbydifficulty'] = 'Filter by difficulty';
$string['flip'] = 'Flip Card';
$string['flashcarddatainvalid'] = 'The flashcard data is invalid or incomplete.';
$string['someflashcardsinvalid'] = '{$a} flashcards could not be displayed because they are missing required information.';
$string['regenerateflashcards'] = 'Regenerate Flashcards';

// Quiz
$string['noquiz'] = 'No quiz has been generated yet.';
$string['generatequiz'] = 'Generate Quiz';
$string['interactivequiz'] = 'Interactive AI Quiz';
$string['downloadquiz'] = 'Download Quiz';
$string['checkanswers'] = 'Check Answers';
$string['resetquiz'] = 'Reset Quiz';
$string['totalquestions'] = 'Total questions: {$a}';
$string['noexplanation'] = 'No explanation provided for this question.';
$string['quizdatainvalid'] = 'The quiz questions could not be displayed because the data is invalid.';
$string['somequestionsinvalid'] = '{$a} questions were skipped because they are missing required information.';
$string['regeneratequiz'] = 'Regenerate Quiz';

// Difficulty levels
$string['easy'] = 'Easy';
$string['medium'] = 'Medium';
$string['hard'] = 'Hard';

// Navigation
$string['previous'] = 'Previous';
$string['next'] = 'Next';

// Actions
$string['regenerateall'] = 'Regenerate All Content';
$string['generationqueued'] = 'Content generation has been queued. Please check back in a few minutes.';
$string['modulenotfound'] = 'The requested module could not be found.';

// Errors
$string['invalidtype'] = 'Invalid download type specified.';

// Settings
$string['aisettings'] = 'AI Provider Settings';
$string['aisettings_desc'] = 'Configure the AI provider for content generation';
$string['default_provider'] = 'Default AI Provider';
$string['default_provider_desc'] = 'The default AI provider to use for content generation (e.g., openai, anthropic)';
$string['api_key'] = 'API Key';
$string['api_key_desc'] = 'API key for the AI provider';
$string['api_endpoint'] = 'API Endpoint';
$string['api_endpoint_desc'] = 'API endpoint URL for the AI provider';

$string['generationsettings'] = 'Content Generation Settings';
$string['generationsettings_desc'] = 'Configure content generation parameters';
$string['max_flashcards'] = 'Maximum Flashcards';
$string['max_flashcards_desc'] = 'Maximum number of flashcards to generate per activity';
$string['max_quiz_questions'] = 'Maximum Quiz Questions';
$string['max_quiz_questions_desc'] = 'Maximum number of quiz questions to generate per activity';
$string['enable_audio_generation'] = 'Enable Audio Generation';
$string['enable_audio_generation_desc'] = 'Enable text-to-speech for podcast generation';
$string['enable_video_generation'] = 'Enable Video Generation';
$string['enable_video_generation_desc'] = 'Enable AI video generation (requires additional services)';

$string['detectionsettings'] = 'Content Detection Settings';
$string['detectionsettings_desc'] = 'Configure automatic content detection';
$string['auto_detect_new_content'] = 'Auto-detect New Content';
$string['auto_detect_new_content_desc'] = 'Automatically detect and add new books and files to RVS activities';
$string['auto_regenerate'] = 'Auto-regenerate Content';
$string['auto_regenerate_desc'] = 'Automatically regenerate AI content when source materials are updated';

// Privacy
$string['privacy:metadata'] = 'The RVS AI Learning Suite plugin does not store any personal data. All generated content is course-level content associated with activities, not individual users.';

// AI Configuration and Errors
$string['ainotconfigured'] = 'AI Provider Not Configured';
$string['ainotconfigured_help'] = 'The AI provider is not configured. To enable AI content generation, please configure an AI provider in the plugin settings.';
$string['ainotavailable'] = 'AI subsystem is not available in this Moodle installation. Please install Moodle 4.4+ with AI subsystem support.';
$string['aigenerationfailed'] = 'AI content generation failed: {$a}';
$string['configurenow'] = 'Configure AI Provider';
$string['nocontentgenerated'] = 'No content has been generated yet. Click "Regenerate All Content" to generate AI content.';
$string['aitest'] = 'Test AI Configuration';
$string['aitest_success'] = 'AI configuration test successful!';
$string['aitest_failed'] = 'AI configuration test failed: {$a}';
$string['aitest_missing_config'] = 'Missing configuration: {$a}';




// Error Handling and Notifications
$string['errorsettings'] = 'Error Handling and Notifications';
$string['errorsettings_desc'] = 'Configure error handling and admin notifications';
$string['enable_admin_notifications'] = 'Enable Admin Notifications';
$string['enable_admin_notifications_desc'] = 'Send notifications to site administrators when critical errors occur (content extraction failures, AI generation failures, etc.)';

// Message provider
$string['messageprovider:adminerror'] = 'RVS critical error notifications';

// Content Extraction Errors
$string['error_extraction_failed'] = 'Content extraction failed';
$string['error_extraction_pdf_failed'] = 'Failed to extract content from PDF file: {$a}';
$string['error_extraction_docx_failed'] = 'Failed to extract content from Word document: {$a}';
$string['error_extraction_text_failed'] = 'Failed to extract content from text file: {$a}';
$string['error_extraction_book_failed'] = 'Failed to extract content from book module: {$a}';
$string['error_extraction_unsupported'] = 'Unsupported file type: {$a}';
$string['error_extraction_corrupted'] = 'File appears to be corrupted or encrypted: {$a}';
$string['error_extraction_empty'] = 'No text content could be extracted from file: {$a}';
$string['error_extraction_filenotfound'] = 'File not found or inaccessible: {$a}';
$string['error_extraction_permission'] = 'Permission denied when accessing file: {$a}';

// RAG Processing Errors
$string['error_rag_chunking_failed'] = 'Content chunking failed: {$a}';
$string['error_rag_retrieval_failed'] = 'Content retrieval failed: {$a}';
$string['error_rag_processing_failed'] = 'RAG processing failed, using fallback method: {$a}';
$string['error_rag_content_toolarge'] = 'Content exceeds maximum size limit ({$a} tokens)';
$string['error_rag_no_relevant_chunks'] = 'No relevant content chunks found for this task';
$string['warning_rag_fallback'] = 'RAG processing unavailable, using direct content with truncation';

// AI Generation Errors
$string['error_ai_generation_failed'] = 'AI generation failed: {$a}';
$string['error_ai_api_timeout'] = 'AI API request timed out after {$a} seconds';
$string['error_ai_api_error'] = 'AI API returned an error: {$a}';
$string['error_ai_invalid_response'] = 'AI returned an invalid response format';
$string['error_ai_json_parse'] = 'Failed to parse AI response as JSON: {$a}';
$string['error_ai_validation_failed'] = 'AI response failed validation: {$a}';
$string['error_ai_retry_exhausted'] = 'AI generation failed after {$a} retry attempts';
$string['error_ai_no_content'] = 'No content available for AI generation';
$string['error_ai_provider_notconfigured'] = 'AI provider is not properly configured';
$string['error_ai_quota_exceeded'] = 'AI API quota exceeded. Please try again later.';
$string['error_ai_rate_limited'] = 'AI API rate limit reached. Please wait before retrying.';

// Module-Specific Generation Errors
$string['error_mindmap_generation_failed'] = 'Mind map generation failed: {$a}';
$string['error_mindmap_invalid_structure'] = 'Generated mind map has invalid structure';
$string['error_podcast_generation_failed'] = 'Podcast generation failed: {$a}';
$string['error_podcast_script_empty'] = 'Generated podcast script is empty';
$string['error_video_generation_failed'] = 'Video script generation failed: {$a}';
$string['error_video_script_empty'] = 'Generated video script is empty';
$string['error_report_generation_failed'] = 'Report generation failed: {$a}';
$string['error_report_empty'] = 'Generated report is empty';
$string['error_flashcard_generation_failed'] = 'Flashcard generation failed: {$a}';
$string['error_flashcard_invalid_format'] = 'Generated flashcards have invalid format';
$string['error_quiz_generation_failed'] = 'Quiz generation failed: {$a}';
$string['error_quiz_invalid_format'] = 'Generated quiz questions have invalid format';

// Database and Storage Errors
$string['error_db_write_failed'] = 'Failed to save content to database: {$a}';
$string['error_db_read_failed'] = 'Failed to retrieve content from database: {$a}';
$string['error_content_not_found'] = 'Content not found in database';

// Troubleshooting Help Text
$string['help_extraction_troubleshooting'] = 'Content Extraction Troubleshooting';
$string['help_extraction_troubleshooting_text'] = 'If content extraction is failing:
<ul>
<li>Verify that the file is not corrupted or password-protected</li>
<li>Check that the file format is supported (PDF, DOCX, TXT, MD)</li>
<li>Ensure the file size is within limits (maximum 50MB)</li>
<li>Verify that required PHP libraries are installed (run composer install)</li>
<li>Check the Moodle error logs for detailed error messages</li>
</ul>';

$string['help_rag_troubleshooting'] = 'RAG Processing Troubleshooting';
$string['help_rag_troubleshooting_text'] = 'If RAG processing is failing:
<ul>
<li>Check that the content is not empty or too short</li>
<li>Verify that the content does not exceed maximum size (100,000 words)</li>
<li>The system will automatically fall back to direct content if RAG fails</li>
<li>Check the Moodle error logs for detailed processing information</li>
</ul>';

$string['help_ai_troubleshooting'] = 'AI Generation Troubleshooting';
$string['help_ai_troubleshooting_text'] = 'If AI generation is failing:
<ul>
<li>Verify that the AI provider is properly configured in plugin settings</li>
<li>Check that your API key is valid and has not expired</li>
<li>Ensure you have not exceeded your API quota or rate limits</li>
<li>Verify that the API endpoint URL is correct</li>
<li>Check your network connection and firewall settings</li>
<li>Review the Moodle error logs for detailed API error messages</li>
<li>Try regenerating the content after a few minutes</li>
</ul>';

$string['help_general_troubleshooting'] = 'General Troubleshooting';
$string['help_general_troubleshooting_text'] = 'For general issues:
<ul>
<li>Clear Moodle caches (Site administration > Development > Purge all caches)</li>
<li>Check that all required dependencies are installed (composer install)</li>
<li>Verify that the plugin is up to date</li>
<li>Review the Moodle error logs for detailed information</li>
<li>Contact your site administrator if problems persist</li>
</ul>';

// User-Friendly Error Messages
$string['usererror_extraction_failed'] = 'We couldn\'t extract content from this file. Please ensure the file is not corrupted or password-protected.';
$string['usererror_generation_failed'] = 'Content generation failed. Please try again later or contact your site administrator if the problem persists.';
$string['usererror_no_content'] = 'No content is available for generation. Please add books or files to your course first.';
$string['usererror_ai_unavailable'] = 'The AI service is temporarily unavailable. Please try again in a few minutes.';
$string['usererror_configuration_required'] = 'AI content generation requires configuration. Please contact your site administrator.';

// Success Messages
$string['success_extraction_complete'] = 'Content extracted successfully from {$a}';
$string['success_generation_complete'] = '{$a} generated successfully';
$string['success_all_generated'] = 'All enabled modules generated successfully';

// Feature Descriptions
$string['feature_content_extraction'] = 'Content Extraction';
$string['feature_content_extraction_desc'] = 'Automatically extracts text content from PDF files, Word documents, text files, and Moodle book modules';
$string['feature_rag_processing'] = 'RAG Processing';
$string['feature_rag_processing_desc'] = 'Uses Retrieval-Augmented Generation to intelligently process large content and improve AI output quality';
$string['feature_smart_chunking'] = 'Smart Content Chunking';
$string['feature_smart_chunking_desc'] = 'Splits content into semantic chunks at paragraph and section boundaries for better AI processing';
$string['feature_relevance_scoring'] = 'Relevance Scoring';
$string['feature_relevance_scoring_desc'] = 'Retrieves the most relevant content chunks for each generation task type';

// Status Messages
$string['status_extracting'] = 'Extracting content...';
$string['status_processing'] = 'Processing content with RAG...';
$string['status_generating'] = 'Generating {$a}...';
$string['status_complete'] = 'Generation complete';
$string['status_queued'] = 'Generation queued';

// Admin Notification Messages
$string['notification_extraction_failed_subject'] = 'RVS: Content extraction failed';
$string['notification_extraction_failed_body'] = 'Content extraction failed for RVS activity "{$a->activityname}" in course "{$a->coursename}".

Error: {$a->error}
File: {$a->filename}
Time: {$a->time}

Please check the error logs for more details.';

$string['notification_generation_failed_subject'] = 'RVS: AI generation failed';
$string['notification_generation_failed_body'] = 'AI content generation failed for RVS activity "{$a->activityname}" in course "{$a->coursename}".

Module: {$a->module}
Error: {$a->error}
Time: {$a->time}

Please check the AI provider configuration and error logs for more details.';

$string['notification_rag_failed_subject'] = 'RVS: RAG processing failed';
$string['notification_rag_failed_body'] = 'RAG processing failed for RVS activity "{$a->activityname}" in course "{$a->coursename}".

Error: {$a->error}
Time: {$a->time}

The system has fallen back to direct content processing. Please check the error logs for more details.';
