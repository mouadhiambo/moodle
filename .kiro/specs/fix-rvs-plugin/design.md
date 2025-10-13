# Design Document

## Overview

This design document outlines the technical approach to fix the RVS AI Learning Suite plugin by implementing proper content extraction from Moodle file and book modules, adding Retrieval-Augmented Generation (RAG) capabilities, and enhancing all six learning modules to generate accurate, high-quality content.

### Key Design Goals

1. **Robust Content Extraction**: Implement reliable text extraction from PDFs, Word documents, and text files
2. **RAG Implementation**: Add intelligent content chunking and retrieval for better AI generation
3. **Enhanced Module Generation**: Improve all six modules to produce high-quality learning materials
4. **Backward Compatibility**: Maintain all existing functionality and database schema
5. **Error Resilience**: Implement comprehensive error handling and fallback mechanisms

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Moodle Course Content                     │
│              (Books, Files, Resources)                       │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Content Detection & Extraction                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │ Book         │  │ PDF          │  │ Document     │     │
│  │ Extractor    │  │ Extractor    │  │ Extractor    │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                  RAG Processing Layer                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │ Content      │  │ Semantic     │  │ Chunk        │     │
│  │ Chunking     │  │ Retrieval    │  │ Storage      │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                  AI Generation Engine                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │ Prompt       │  │ AI API       │  │ Response     │     │
│  │ Builder      │  │ Client       │  │ Parser       │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                    Module Generators                         │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐  │
│  │MindMap │ │Podcast │ │ Video  │ │ Report │ │Flashcrd│  │
│  └────────┘ └────────┘ └────────┘ └────────┘ └────────┘  │
│  ┌────────┐                                                 │
│  │  Quiz  │                                                 │
│  └────────┘                                                 │
└─────────────────────────────────────────────────────────────┘
```

### Component Interaction Flow

1. **Event Detection**: Moodle events trigger content detection
2. **Content Extraction**: Appropriate extractor processes the content
3. **RAG Processing**: Content is chunked and prepared for AI
4. **AI Generation**: Each enabled module generates its content
5. **Storage**: Generated content is stored in database
6. **Display**: Users access content through module views

## Components and Interfaces

### 1. Content Extraction Layer

#### 1.1 File Content Extractor (`classes/content/file_extractor.php`)

**Purpose**: Extract text content from various file formats

**Public Methods**:
```php
class file_extractor {
    /**
     * Extract content from a file
     * @param int $resourceid Resource module ID
     * @return string Extracted text content
     */
    public static function extract_content($resourceid);
    
    /**
     * Extract text from PDF file
     * @param stored_file $file Moodle file object
     * @return string Extracted text
     */
    private static function extract_from_pdf($file);
    
    /**
     * Extract text from Word document
     * @param stored_file $file Moodle file object
     * @return string Extracted text
     */
    private static function extract_from_docx($file);
    
    /**
     * Extract text from plain text file
     * @param stored_file $file Moodle file object
     * @return string File content
     */
    private static function extract_from_text($file);
    
    /**
     * Check if file type is supported
     * @param string $mimetype File MIME type
     * @return bool True if supported
     */
    public static function is_supported_type($mimetype);
}
```

**Dependencies**:
- PHP `pdfparser` library for PDF extraction
- PHP `PhpOffice/PhpWord` for DOCX extraction
- Moodle File API for file access

**Error Handling**:
- Return empty string on extraction failure
- Log detailed error messages
- Continue processing other files

#### 1.2 Book Content Extractor (`classes/content/book_extractor.php`)

**Purpose**: Extract and format content from Moodle book modules

**Public Methods**:
```php
class book_extractor {
    /**
     * Extract content from a book
     * @param int $bookid Book module ID
     * @return string Formatted book content
     */
    public static function extract_content($bookid);
    
    /**
     * Strip HTML while preserving structure
     * @param string $html HTML content
     * @return string Plain text with structure
     */
    private static function html_to_text($html);
    
    /**
     * Extract image alt text and captions
     * @param string $html HTML content
     * @return array Image descriptions
     */
    private static function extract_image_descriptions($html);
}
```

**Processing Steps**:
1. Retrieve all chapters ordered by pagenum
2. For each chapter:
   - Extract title
   - Convert HTML to structured text
   - Extract image descriptions
   - Preserve hierarchy
3. Combine into single formatted document

### 2. RAG Processing Layer

#### 2.1 Content Chunker (`classes/rag/chunker.php`)

**Purpose**: Split content into semantic chunks for RAG processing

**Public Methods**:
```php
class chunker {
    /**
     * Chunk content into semantic segments
     * @param string $content Full content text
     * @param int $max_tokens Maximum tokens per chunk
     * @param int $overlap Overlap tokens between chunks
     * @return array Array of content chunks
     */
    public static function chunk_content($content, $max_tokens = 1000, $overlap = 100);
    
    /**
     * Estimate token count for text
     * @param string $text Text to estimate
     * @return int Estimated token count
     */
    public static function estimate_tokens($text);
    
    /**
     * Find semantic boundaries in text
     * @param string $text Text to analyze
     * @return array Boundary positions
     */
    private static function find_semantic_boundaries($text);
}
```

**Chunking Strategy**:
- Use semantic boundaries (paragraphs, sections, sentences)
- Default chunk size: 1000 tokens (~750 words)
- Overlap: 100 tokens to maintain context
- Preserve complete sentences
- Keep section headers with their content

#### 2.2 Content Retriever (`classes/rag/retriever.php`)

**Purpose**: Retrieve relevant content chunks for specific generation tasks

**Public Methods**:
```php
class retriever {
    /**
     * Retrieve relevant chunks for a task
     * @param array $chunks All content chunks
     * @param string $task_type Type of generation task
     * @param int $max_chunks Maximum chunks to return
     * @return array Relevant chunks
     */
    public static function retrieve_relevant_chunks($chunks, $task_type, $max_chunks = 5);
    
    /**
     * Calculate relevance score for chunk
     * @param string $chunk Content chunk
     * @param string $task_type Generation task type
     * @return float Relevance score (0-1)
     */
    private static function calculate_relevance($chunk, $task_type);
    
    /**
     * Combine chunks into context
     * @param array $chunks Selected chunks
     * @return string Combined context
     */
    public static function combine_chunks($chunks);
}
```

**Retrieval Strategy**:
- For small content (<2000 tokens): Use all content
- For large content: Use keyword-based relevance scoring
- Task-specific keywords:
  - Mind map: "concept", "relationship", "key", "main"
  - Flashcards: "definition", "term", "important", "remember"
  - Quiz: "fact", "process", "explain", "describe"
  - Report: All content with summarization
  - Podcast/Video: Narrative flow prioritized

#### 2.3 RAG Manager (`classes/rag/manager.php`)

**Purpose**: Coordinate RAG processing workflow

**Public Methods**:
```php
class manager {
    /**
     * Process content with RAG
     * @param string $content Full content
     * @param string $task_type Generation task
     * @return string Processed content for AI
     */
    public static function process_for_task($content, $task_type);
    
    /**
     * Check if RAG is needed
     * @param string $content Content to check
     * @return bool True if RAG should be used
     */
    public static function should_use_rag($content);
}
```

### 3. Enhanced AI Generator

#### 3.1 Updated Generator Class (`classes/ai/generator.php`)

**Enhancements**:
- Integrate RAG processing before AI calls
- Improve prompts for each module type
- Add retry logic for API failures
- Implement response validation
- Add structured output parsing

**Updated Methods**:
```php
class generator {
    /**
     * Generate mind map with RAG
     * @param string $content Source content
     * @param int $rvsid RVS instance ID
     * @return array Mind map structure
     */
    public static function generate_mindmap($content, $rvsid = null);
    
    /**
     * Generate podcast with RAG
     * @param string $content Source content
     * @param int $rvsid RVS instance ID
     * @return string Podcast script
     */
    public static function generate_podcast($content, $rvsid = null);
    
    // Similar updates for other generation methods...
    
    /**
     * Build task-specific prompt
     * @param string $content Processed content
     * @param string $task_type Task type
     * @return string Complete prompt
     */
    private static function build_prompt($content, $task_type);
    
    /**
     * Validate AI response
     * @param string $response AI response
     * @param string $task_type Expected type
     * @return bool True if valid
     */
    private static function validate_response($response, $task_type);
}
```

### 4. Module-Specific Enhancements

#### 4.1 Mind Map Module

**Improvements**:
- Better JSON structure validation
- Hierarchical concept extraction
- Relationship mapping
- Interactive visualization enhancements

**Data Structure**:
```json
{
  "central": "Main Topic",
  "branches": [
    {
      "topic": "Branch 1",
      "subtopics": ["Sub 1.1", "Sub 1.2"],
      "relationships": ["Branch 2", "Branch 3"]
    }
  ]
}
```

#### 4.2 Podcast Module

**Improvements**:
- Conversational tone optimization
- Structured script format
- Time estimates
- Optional TTS integration

**Script Format**:
```
[INTRO]
HOST: Welcome to...

[MAIN CONTENT]
HOST: Let's explore...

[CONCLUSION]
HOST: To summarize...
```

#### 4.3 Video Module

**Improvements**:
- Visual cue formatting
- Scene descriptions
- Timing suggestions
- Storyboard structure

**Script Format**:
```
[SCENE 1]
[VISUAL: Title card with main topic]
NARRATION: Today we'll learn about...

[SCENE 2]
[VISUAL: Diagram showing...]
NARRATION: The key concept is...
```

#### 4.4 Report Module

**Improvements**:
- Structured sections
- HTML formatting
- Citation support
- Export options

**Report Structure**:
```html
<h1>Executive Summary</h1>
<p>...</p>

<h1>Key Topics</h1>
<h2>Topic 1</h2>
<p>...</p>

<h1>Detailed Analysis</h1>
<p>...</p>

<h1>Conclusions</h1>
<p>...</p>
```

#### 4.5 Flashcards Module

**Improvements**:
- Difficulty calibration
- Spaced repetition hints
- Category tagging
- Progress tracking

**Flashcard Structure**:
```json
{
  "question": "What is...?",
  "answer": "It is...",
  "difficulty": "medium",
  "category": "Concepts",
  "hints": ["Think about...", "Remember that..."]
}
```

#### 4.6 Quiz Module

**Improvements**:
- Distractor quality
- Explanation depth
- Difficulty progression
- Immediate feedback

**Question Structure**:
```json
{
  "question": "Which of the following...?",
  "options": ["Option A", "Option B", "Option C", "Option D"],
  "correctanswer": 2,
  "explanation": "The correct answer is C because...",
  "difficulty": "medium",
  "topic": "Key Concepts"
}
```

## Data Models

### Existing Tables (No Changes)

All existing database tables remain unchanged to maintain backward compatibility:
- `mdl_rvs`
- `mdl_rvs_content`
- `mdl_rvs_mindmap`
- `mdl_rvs_podcast`
- `mdl_rvs_video`
- `mdl_rvs_report`
- `mdl_rvs_flashcard`
- `mdl_rvs_quiz`

### New Table: RAG Chunks (Optional)

**Purpose**: Cache chunked content for performance

```xml
<TABLE NAME="rvs_chunks" COMMENT="Cached content chunks for RAG">
  <FIELDS>
    <FIELD NAME="id" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="true"/>
    <FIELD NAME="rvsid" TYPE="int" LENGTH="10" NOTNULL="true"/>
    <FIELD NAME="contenthash" TYPE="char" LENGTH="40" NOTNULL="true"/>
    <FIELD NAME="chunkindex" TYPE="int" LENGTH="5" NOTNULL="true"/>
    <FIELD NAME="chunktext" TYPE="text" NOTNULL="false"/>
    <FIELD NAME="tokens" TYPE="int" LENGTH="10" NOTNULL="true"/>
    <FIELD NAME="timecreated" TYPE="int" LENGTH="10" NOTNULL="true"/>
  </FIELDS>
  <KEYS>
    <KEY NAME="primary" TYPE="primary" FIELDS="id"/>
    <KEY NAME="rvsid" TYPE="foreign" FIELDS="rvsid" REFTABLE="rvs" REFFIELDS="id"/>
  </KEYS>
  <INDEXES>
    <INDEX NAME="contenthash" UNIQUE="false" FIELDS="contenthash"/>
  </INDEXES>
</TABLE>
```

**Note**: This table is optional and can be added in a future version for performance optimization.

## Error Handling

### Error Handling Strategy

1. **Graceful Degradation**: System continues functioning even if some components fail
2. **Detailed Logging**: All errors logged with context for debugging
3. **User-Friendly Messages**: End users see helpful messages, not technical errors
4. **Fallback Mechanisms**: Alternative approaches when primary method fails

### Error Scenarios and Handling

| Scenario | Handling Strategy |
|----------|------------------|
| PDF extraction fails | Log error, store empty content, notify admin |
| DOCX extraction fails | Try alternative library, fallback to empty |
| Book HTML parsing fails | Use raw text, log warning |
| RAG chunking fails | Use full content with truncation warning |
| AI API timeout | Retry up to 3 times with exponential backoff |
| AI response invalid | Log error, mark generation as failed |
| JSON parsing fails | Return error structure, display message |
| Database write fails | Rollback transaction, log error |

### Logging Levels

- **ERROR**: Critical failures requiring admin attention
- **WARNING**: Non-critical issues that should be reviewed
- **INFO**: Successful operations and milestones
- **DEBUG**: Detailed information for troubleshooting

## Testing Strategy

### Unit Testing

**Test Coverage**:
- Content extraction for each file type
- HTML to text conversion
- Content chunking with various sizes
- Relevance scoring algorithms
- JSON structure validation
- Error handling paths

**Test Files**:
- `tests/content/file_extractor_test.php`
- `tests/content/book_extractor_test.php`
- `tests/rag/chunker_test.php`
- `tests/rag/retriever_test.php`
- `tests/ai/generator_test.php`

### Integration Testing

**Test Scenarios**:
1. End-to-end content extraction from book
2. End-to-end content extraction from PDF
3. Complete RAG processing workflow
4. Full generation cycle for each module
5. Error recovery and fallback mechanisms

### Manual Testing Checklist

- [ ] Upload PDF and verify extraction
- [ ] Upload DOCX and verify extraction
- [ ] Create book and verify extraction
- [ ] Generate mind map and verify structure
- [ ] Generate podcast and verify script
- [ ] Generate video and verify script
- [ ] Generate report and verify formatting
- [ ] Generate flashcards and verify interactivity
- [ ] Generate quiz and verify functionality
- [ ] Test with large content (>10,000 words)
- [ ] Test with small content (<500 words)
- [ ] Test error scenarios (invalid files, API failures)
- [ ] Verify backward compatibility with existing data

## Performance Considerations

### Optimization Strategies

1. **Lazy Loading**: Generate content only when requested
2. **Caching**: Cache chunked content and AI responses
3. **Async Processing**: Use adhoc tasks for generation
4. **Batch Processing**: Process multiple items in single task
5. **Resource Limits**: Set timeouts and memory limits

### Performance Targets

- PDF extraction: <5 seconds for 50-page document
- Content chunking: <2 seconds for 10,000 words
- AI generation: <30 seconds per module
- Total generation time: <5 minutes for all modules

### Resource Management

- Maximum file size: 50MB
- Maximum content length: 100,000 words
- Maximum chunks per content: 100
- API timeout: 60 seconds
- Task timeout: 300 seconds (5 minutes)

## Security Considerations

### Input Validation

- Validate file types before processing
- Sanitize extracted text content
- Validate AI responses before storage
- Escape HTML in user-facing displays

### Access Control

- Respect Moodle capability system
- Verify user permissions before generation
- Protect API keys in configuration
- Secure file access through Moodle File API

### Data Privacy

- No personal data sent to AI providers
- Content anonymization option
- GDPR compliance maintained
- Audit trail for content generation

## Dependencies

### PHP Libraries

```json
{
  "require": {
    "smalot/pdfparser": "^2.0",
    "phpoffice/phpword": "^1.0"
  }
}
```

### Moodle APIs

- File API: File access and management
- Database API: Data persistence
- Task API: Background processing
- Event API: Content detection
- Capability API: Permission checking

### External Services

- AI Provider API (OpenAI, Anthropic, etc.)
- Optional: Text-to-Speech API for podcasts
- Optional: Video generation API for videos

## Migration and Deployment

### Deployment Steps

1. **Backup**: Full database and file backup
2. **Install Dependencies**: Run `composer install`
3. **Database Upgrade**: Run Moodle upgrade process
4. **Clear Caches**: Purge all caches
5. **Test Configuration**: Verify AI provider settings
6. **Regenerate Content**: Optionally regenerate existing content

### Rollback Plan

1. Restore database from backup
2. Restore plugin files from backup
3. Clear caches
4. Verify functionality

### Backward Compatibility

- All existing data remains accessible
- No breaking changes to database schema
- Existing generated content preserved
- Settings and configurations maintained

## Future Enhancements

### Phase 2 Features

- Vector embeddings for better RAG
- Multi-language content generation
- Custom AI model fine-tuning
- Advanced analytics dashboard
- Collaborative content editing
- SCORM export functionality

### Extensibility Points

- Custom content extractors
- Custom AI providers
- Custom module types
- Custom export formats
- Webhook integrations
