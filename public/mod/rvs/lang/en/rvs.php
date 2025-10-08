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
$string['downloadmindmap'] = 'Download Mind Map';

// Podcast
$string['nopodcast'] = 'No podcast has been generated yet.';
$string['generatepodcast'] = 'Generate Podcast';
$string['podcastscript'] = 'Podcast Script';
$string['downloadscript'] = 'Download Script';
$string['downloadaudio'] = 'Download Audio';

// Video
$string['novideo'] = 'No video has been generated yet.';
$string['generatevideo'] = 'Generate Video';
$string['videoscript'] = 'Video Script';
$string['downloadvideo'] = 'Download Video';

// Report
$string['noreport'] = 'No report has been generated yet.';
$string['generatereport'] = 'Generate Report';
$string['downloadas'] = 'Download as {$a}';

// Flashcards
$string['noflashcards'] = 'No flashcards have been generated yet.';
$string['generateflashcards'] = 'Generate Flashcards';
$string['flashcards'] = 'AI Flashcards';
$string['downloadflashcards'] = 'Download Flashcards';
$string['filterbydifficulty'] = 'Filter by difficulty';
$string['flip'] = 'Flip Card';

// Quiz
$string['noquiz'] = 'No quiz has been generated yet.';
$string['generatequiz'] = 'Generate Quiz';
$string['interactivequiz'] = 'Interactive AI Quiz';
$string['downloadquiz'] = 'Download Quiz';
$string['checkanswerss'] = 'Check Answers';
$string['resetquiz'] = 'Reset Quiz';

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



