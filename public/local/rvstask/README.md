# RVS Task - Moodle Email Scheduling Plugin

## Overview

RVS Task is a comprehensive Moodle plugin that provides scheduled email functionality with template management, recipient selection, and automated course completion notifications.

## Features

### 1. **Scheduled Task**
- Automatically runs every 15 minutes (configurable)
- Processes queued emails with retry logic
- Handles up to 100 emails per run
- Logs all activities for monitoring

### 2. **Email Template Management**
- Create and store reusable email templates
- Rich HTML editor with formatting options
- Dynamic placeholder support:
  - `{firstname}` - Recipient's first name
  - `{lastname}` - Recipient's last name
  - `{fullname}` - Recipient's full name
  - `{email}` - Recipient's email
  - `{coursename}` - Course short name
  - `{coursefullname}` - Course full name
  - `{sitename}` - Site name
- Enable/disable templates
- Edit and delete templates

### 3. **Recipient Selection**
Multiple ways to identify recipients:
- **All Students** - Send to all users with student role
- **All Teachers** - Send to all users with teacher/editing teacher role
- **Course Completions** - Send to users who completed specific courses
- **Specific Users** - Manually specify user IDs

### 4. **Course Completion Emails**
- Automatically send emails when students complete courses
- Configure which template to use
- Enable/disable feature in plugin settings
- Immediate email queuing upon completion

### 5. **Send Options**
- **Send Now** - Queue emails for immediate sending
- **Schedule Send** - Queue emails for a specific date/time
- Queue management with statistics
- Retry failed emails automatically (up to 3 attempts)

## Installation

1. Copy the plugin folder to `moodle/local/rvstask`

2. Log in as an administrator and navigate to:
   - Site administration → Notifications

3. Click "Upgrade Moodle database now" to install the plugin tables

4. Configure plugin settings at:
   - Site administration → Plugins → Local plugins → RVS Task

## Configuration

### Enable Course Completion Emails

1. Navigate to: Site administration → Plugins → Local plugins → RVS Task
2. Enable "Enable course completion emails"
3. Select a template from the dropdown
4. Save changes

### Configure Scheduled Task

1. Navigate to: Site administration → Server → Scheduled tasks
2. Find "Send scheduled emails"
3. Configure the schedule (default: every 15 minutes)
4. Save changes

## Usage

### Creating Email Templates

1. Navigate to: Site administration → RVS Task → Manage email templates
2. Click "Create email template"
3. Enter:
   - Template name
   - Email subject (can use placeholders)
   - Email body (HTML editor, can use placeholders)
   - Enable checkbox
4. Click "Save changes"

### Sending Emails on Demand

1. Navigate to: Site administration → RVS Task → Send email
2. Select an email template
3. Choose recipient type:
   - All students
   - All teachers
   - Course completions (select courses)
   - Specific users (enter user IDs)
4. Choose send option:
   - Send now (immediate)
   - Schedule send (select date/time)
5. Click "Send email"

### Viewing Queue Statistics

The send email page displays:
- **Pending** - Emails waiting to be sent
- **Sent** - Successfully sent emails
- **Failed** - Emails that failed after 3 attempts

## Database Tables

### local_rvstask_templates
Stores email templates with:
- Template name, subject, body
- Format and enabled status
- Creation and modification timestamps

### local_rvstask_queue
Stores email queue with:
- Template and recipient references
- Scheduled time
- Send status and attempts
- Error logging

## Capabilities

### local/rvstask:manage
- Create, edit, and delete email templates
- Configure plugin settings
- **Risk**: RISK_CONFIG
- **Default**: Managers

### local/rvstask:send
- Send emails on demand
- View queue statistics
- **Risk**: RISK_SPAM
- **Default**: Managers and Editing Teachers

## Email Sending

The plugin uses Moodle's built-in `email_to_user()` function, which:
- Respects user email preferences
- Handles HTML and plain text formats
- Manages bounces and delivery issues
- Logs email activities
- Supports digest settings

## Task Schedule

The scheduled task runs by default every 15 minutes and:
1. Queries unsent emails where `scheduledtime <= now()`
2. Processes up to 100 emails per run
3. Retries failed emails (max 3 attempts)
4. Updates queue status
5. Logs all activities

## Troubleshooting

### Emails Not Sending

1. Check scheduled task is enabled:
   - Site administration → Server → Scheduled tasks

2. Verify cron is running:
   - Check `admin/cron.php` is being executed

3. Check email queue:
   - View statistics on the send email page
   - Check for failed emails with error messages

4. Verify email configuration:
   - Site administration → Server → Email → Outgoing mail configuration

### Templates Not Appearing

1. Ensure templates are enabled
2. Check user has `local/rvstask:manage` or `local/rvstask:send` capability
3. Clear Moodle caches

## Development

### File Structure
```
local/rvstask/
├── classes/
│   ├── task/
│   │   └── send_emails_task.php     # Scheduled task
│   ├── observer.php                  # Event observer
│   ├── template_manager.php          # Template CRUD
│   └── queue_manager.php             # Queue management
├── db/
│   ├── access.php                    # Capabilities
│   ├── events.php                    # Event observers
│   ├── install.xml                   # Database schema
│   ├── tasks.php                     # Task registration
│   └── upgrade.php                   # Upgrade script
├── lang/
│   └── en/
│       └── local_rvstask.php         # Language strings
├── edit_template.php                 # Template editor
├── send.php                          # Send interface
├── settings.php                      # Plugin settings
├── templates.php                     # Template manager
└── version.php                       # Plugin version
```

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

## Copyright

Copyright 2025 RVIBS

## Support

For issues and feature requests, please contact your system administrator.
