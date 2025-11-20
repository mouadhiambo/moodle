# RVS Task Plugin - File Structure and Summary

## Complete File List

### Core Plugin Files
```
local/rvstask/
├── version.php                    # Plugin version and metadata
├── settings.php                   # Plugin settings and admin menu
├── README.md                      # Plugin documentation
└── INSTALLATION.md               # Installation guide
```

### Database Schema and Configuration
```
local/rvstask/db/
├── install.xml                    # Database table definitions
├── tasks.php                      # Scheduled task registration
├── events.php                     # Event observer registration
├── access.php                     # Capability definitions
└── upgrade.php                    # Database upgrade script
```

### Classes (Business Logic)
```
local/rvstask/classes/
├── task/
│   └── send_emails_task.php      # Scheduled task implementation
├── observer.php                   # Event observer (course completion)
├── template_manager.php           # Email template CRUD operations
└── queue_manager.php              # Email queue management
```

### Language Files
```
local/rvstask/lang/en/
└── local_rvstask.php             # English language strings
```

### User Interface Files
```
local/rvstask/
├── templates.php                  # List email templates
├── edit_template.php              # Create/edit email templates
└── send.php                       # Send emails on demand
```

### CLI Scripts
```
local/rvstask/cli/
└── send_emails.php               # Manual task execution script
```

## Database Tables

### local_rvstask_templates
Stores reusable email templates.

**Fields:**
- `id` - Primary key
- `name` - Template name
- `subject` - Email subject line
- `body` - Email body content
- `bodyformat` - Format (HTML, plain text, etc.)
- `enabled` - Whether template is active
- `timecreated` - Creation timestamp
- `timemodified` - Last modification timestamp
- `usermodified` - User who last modified

### local_rvstask_queue
Stores emails waiting to be sent.

**Fields:**
- `id` - Primary key
- `templateid` - Foreign key to templates table
- `userid` - Recipient user ID
- `courseid` - Related course ID (optional)
- `scheduledtime` - When to send (timestamp)
- `sent` - Whether email has been sent (0/1)
- `timesent` - When email was sent
- `attempts` - Number of send attempts
- `lasterror` - Last error message if failed
- `timecreated` - When queued

## Capabilities

### local/rvstask:manage
- Create, edit, delete email templates
- Configure plugin settings
- Access template management interface
- **Default roles**: Manager

### local/rvstask:send
- Send emails on demand
- View queue statistics
- Access send email interface
- **Default roles**: Manager, Editing Teacher

## Key Features Implementation

### 1. Scheduled Task ✓
**File**: `classes/task/send_emails_task.php`
- Extends `\core\task\scheduled_task`
- Runs every 15 minutes (configurable)
- Processes up to 100 emails per run
- Automatic retry (max 3 attempts)
- Uses `email_to_user()` function

### 2. Email Template Management ✓
**Files**: 
- `classes/template_manager.php` (backend)
- `templates.php` (list view)
- `edit_template.php` (editor)

Features:
- Create/edit/delete templates
- Rich HTML editor
- Enable/disable templates
- Store for reuse

### 3. Course Completion Emails ✓
**Files**:
- `db/events.php` (registration)
- `classes/observer.php` (handler)
- `settings.php` (configuration)

Features:
- Automatic trigger on course completion
- Configurable template selection
- Enable/disable in settings
- Immediate email queuing

### 4. Recipient Selection ✓
**File**: `classes/queue_manager.php`

Methods:
- `get_recipients($type, $params)`
  - All students
  - All teachers
  - Course completions
  - Specific users
  - Custom SQL (extensible)

### 5. Send Options ✓
**File**: `send.php`

Features:
- Send now (immediate)
- Schedule for specific date/time
- Queue management
- Statistics display

### 6. Placeholder Support ✓
**File**: `classes/task/send_emails_task.php`

Method: `replace_placeholders()`

Supported placeholders:
- `{firstname}`, `{lastname}`, `{fullname}`
- `{email}`
- `{coursename}`, `{coursefullname}`
- `{sitename}`

## User Workflows

### Workflow 1: Create and Use Template
1. Admin creates template at `templates.php`
2. Saves with placeholders in subject/body
3. Template stored in database
4. Can be reused for multiple sends

### Workflow 2: Send Email on Demand
1. User navigates to `send.php`
2. Selects template
3. Chooses recipients
4. Picks send time (now or scheduled)
5. Emails queued in database
6. Scheduled task processes queue

### Workflow 3: Automatic Course Completion
1. Admin enables feature in settings
2. Selects template
3. Student completes course
4. Event fires → Observer catches it
5. Email queued immediately
6. Scheduled task sends email

### Workflow 4: Monitor Queue
1. User views `send.php`
2. Statistics displayed at top:
   - Pending emails
   - Sent emails
   - Failed emails
3. Admin can check task logs for details

## Technical Implementation Details

### Email Sending Process
1. **Queue Creation**: Email records inserted into `local_rvstask_queue`
2. **Scheduled Task**: Runs every 15 minutes, queries unsent emails
3. **Template Loading**: Retrieves template from `local_rvstask_templates`
4. **Placeholder Replacement**: Replaces placeholders with actual data
5. **Email Dispatch**: Calls `email_to_user()` with proper parameters
6. **Status Update**: Marks as sent or increments attempt counter
7. **Error Handling**: Logs errors for failed sends

### Security Features
- Capability checks on all admin pages
- CSRF protection via sesskey
- SQL injection protection (using Moodle DB API)
- User validation (deleted/suspended check)
- Template existence verification

### Performance Considerations
- Batch processing (100 emails per run)
- Indexed database fields (scheduledtime, sent)
- Lazy loading of templates
- Efficient recipient queries
- Automatic retry with backoff (failed emails not retried immediately)

## Testing Checklist

### Installation Testing
- [ ] Plugin installs without errors
- [ ] Database tables created correctly
- [ ] Settings page accessible
- [ ] Admin menu items appear

### Template Management Testing
- [ ] Can create new template
- [ ] Can edit existing template
- [ ] Can enable/disable template
- [ ] Can delete unused template
- [ ] Cannot delete template with pending emails

### Email Sending Testing
- [ ] Send now works
- [ ] Scheduled send queues correctly
- [ ] All recipient types work
- [ ] Placeholders replaced correctly
- [ ] HTML and plain text both work

### Course Completion Testing
- [ ] Setting enables/disables feature
- [ ] Email queued on course completion
- [ ] Correct template used
- [ ] Placeholders include course info

### Scheduled Task Testing
- [ ] Task runs via cron
- [ ] Processes correct number of emails
- [ ] Updates queue status
- [ ] Retries failed emails
- [ ] Logs output correctly

## Customization Points

### Adding New Placeholders
Edit: `classes/task/send_emails_task.php`
Method: `replace_placeholders()`

```php
$replacements['{newplaceholder}'] = $value;
```

### Adding New Recipient Types
Edit: `classes/queue_manager.php`
Method: `get_recipients()`

```php
case 'newtype':
    // Custom logic
    break;
```

### Changing Task Schedule
Navigate to: Site administration → Server → Scheduled tasks
Find: "Send scheduled emails"
Edit: Minute, hour, day, month, dayofweek fields

### Custom Email Processing
Edit: `classes/task/send_emails_task.php`
Method: `send_queued_email()`

Add custom logic before or after `email_to_user()` call.

## Maintenance

### Regular Monitoring
- Check task execution logs weekly
- Monitor queue statistics
- Review failed email errors
- Verify cron is running

### Database Cleanup
Old sent emails can be archived/deleted:

```sql
-- Archive emails older than 90 days
DELETE FROM mdl_local_rvstask_queue 
WHERE sent = 1 
AND timesent < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 90 DAY));
```

### Performance Tuning
- Adjust batch size in `send_emails_task.php` (default: 100)
- Modify task schedule frequency
- Add indexes if queue grows large

## Version History

### Version 1.0 (2025112000)
- Initial release
- Email template management
- Scheduled email sending
- Course completion emails
- Multiple recipient types
- On-demand sending
- Queue management
- Retry logic

## Future Enhancements (Not Implemented)

Potential features for future versions:
- Email preview before sending
- A/B testing for templates
- Email analytics and tracking
- Attachment support
- Template categories/folders
- Email scheduling rules (e.g., "send weekly digest")
- User opt-out management
- Email logs and audit trail
- Template import/export
- Multi-language template support

---

**Plugin Complete!** All files have been created and are ready for installation.
