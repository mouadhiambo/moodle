# RVS Task Plugin - Installation and Setup Guide

## Quick Installation Steps

### 1. Install the Plugin

The plugin files are already in place at: `d:\RVIBS\moodle\local\rvstask`

### 2. Install Database Tables

1. Log in to your Moodle site as an administrator
2. Navigate to: **Site administration → Notifications**
3. Moodle will detect the new plugin
4. Click **"Upgrade Moodle database now"**
5. The plugin will install two database tables:
   - `mdl_local_rvstask_templates` (email templates)
   - `mdl_local_rvstask_queue` (email queue)

### 3. Verify Installation

After installation completes, verify the plugin is active:

1. Navigate to: **Site administration → Plugins → Plugins overview**
2. Search for "RVS Task"
3. You should see:
   - **Name**: RVS Task
   - **Version**: 2025112000
   - **Release**: v1.0
   - **Status**: Enabled

## Initial Configuration

### Step 1: Configure Scheduled Task

1. Navigate to: **Site administration → Server → Scheduled tasks**
2. Find "**Send scheduled emails**" in the task list
3. Click the gear icon (⚙) to edit
4. Configure the schedule (default is every 15 minutes):
   - Minute: `*/15`
   - Hour: `*`
   - Day: `*`
   - Month: `*`
   - Day of week: `*`
5. Ensure it's **Enabled**
6. Click **Save changes**

### Step 2: Verify Cron is Running

The scheduled task requires Moodle's cron to be running:

```powershell
# Test cron manually
php admin\cli\cron.php
```

Ensure your server's cron job is configured to run regularly (recommended: every 1-5 minutes).

### Step 3: Create Your First Email Template

1. Navigate to: **Site administration → Local plugins → RVS Task → Manage email templates**
2. Click **"Create email template"**
3. Fill in the form:
   - **Template name**: Welcome Email
   - **Email subject**: Welcome to {sitename}, {firstname}!
   - **Email body**: 
     ```html
     <p>Dear {fullname},</p>
     <p>Welcome to our {sitename} platform!</p>
     <p>We're excited to have you here.</p>
     <p>Best regards,<br>The Team</p>
     ```
   - Check **Enabled**
4. Click **Save changes**

### Step 4: Configure Course Completion Emails (Optional)

1. Navigate to: **Site administration → Plugins → Local plugins → RVS Task**
2. Check **"Enable course completion emails"**
3. Select the template you created from the dropdown
4. Click **Save changes**

Now, whenever a student completes a course, they'll automatically receive an email!

## Testing the Plugin

### Test 1: Send an Email Manually

1. Navigate to: **Site administration → Local plugins → RVS Task → Send email**
2. Select your template
3. Choose **"Specific users"**
4. Enter your user ID in the textarea (you can find this at: Site administration → Users → Browse list of users)
5. Select **"Send now"**
6. Click **Send email**
7. Check your email inbox

### Test 2: Run the Scheduled Task Manually

Using the CLI:

```powershell
# Navigate to your Moodle directory
cd d:\RVIBS\moodle

# Run the custom CLI script (shows stats only)
php local\rvstask\cli\send_emails.php

# Execute the task
php local\rvstask\cli\send_emails.php --execute
```

Or use Moodle's scheduled task CLI:

```powershell
php admin\cli\scheduled_task.php --execute=\\local_rvstask\\task\\send_emails_task
```

### Test 3: Test Course Completion Email

1. Enroll yourself in a test course
2. Enable course completion for that course:
   - Go to: Course administration → Course completion
   - Set completion criteria
3. Complete the course
4. Check that an email was queued:
   - Site administration → Local plugins → RVS Task → Send email
   - Check the queue statistics
5. Run the scheduled task or wait for cron
6. Verify you received the email

## Using the Plugin

### Creating Additional Templates

Create templates for different purposes:

**Example: Course Completion Template**
- Name: Course Completion Congratulations
- Subject: Congratulations on completing {coursefullname}!
- Body:
  ```html
  <p>Dear {firstname},</p>
  <p>Congratulations! You have successfully completed the course <strong>{coursefullname}</strong>.</p>
  <p>Certificate and other course materials are now available in your profile.</p>
  <p>Best regards,<br>The Learning Team</p>
  ```

**Example: Welcome Template**
- Name: New Student Welcome
- Subject: Welcome to {sitename}!
- Body:
  ```html
  <p>Hi {firstname},</p>
  <p>Welcome to {sitename}! We're excited to have you join our learning community.</p>
  <p>Your account details:</p>
  <ul>
    <li>Email: {email}</li>
    <li>Name: {fullname}</li>
  </ul>
  <p>Happy learning!</p>
  ```

### Sending Bulk Emails

**To All Students:**
1. Go to: Site administration → Local plugins → RVS Task → Send email
2. Select template
3. Choose **"All students"**
4. Choose send option (now or scheduled)
5. Click Send email

**To Course Completions:**
1. Select template
2. Choose **"Course completions"**
3. Select specific courses
4. Choose send option
5. Click Send email

**Scheduled Sending:**
1. Select template
2. Choose recipients
3. Select **"Schedule send"**
4. Pick date and time
5. Click Send email
6. Emails will be queued and sent at the specified time

## Available Placeholders

Use these in your email templates:

| Placeholder | Description | Example |
|------------|-------------|---------|
| `{firstname}` | Recipient's first name | John |
| `{lastname}` | Recipient's last name | Doe |
| `{fullname}` | Recipient's full name | John Doe |
| `{email}` | Recipient's email | john@example.com |
| `{coursename}` | Course short name | COURSE101 |
| `{coursefullname}` | Course full name | Introduction to Programming |
| `{sitename}` | Site name | My Moodle Site |

## Monitoring

### Check Queue Statistics

Navigate to: **Site administration → Local plugins → RVS Task → Send email**

At the top of the page, you'll see:
- **Pending**: Emails waiting to be sent
- **Sent**: Successfully delivered emails
- **Failed**: Emails that failed after 3 attempts

### View Task Logs

1. Navigate to: **Site administration → Server → Scheduled tasks**
2. Find "Send scheduled emails"
3. Click on it to view execution logs
4. Recent runs will show:
   - Execution time
   - Duration
   - Success/failure status
   - Output (including number of emails sent)

### Troubleshooting

**Emails not sending?**
- Check that cron is running
- Verify outgoing mail is configured: Site administration → Server → Email
- Check scheduled task is enabled
- View task logs for errors

**Template not appearing?**
- Ensure template is enabled
- Clear Moodle caches: Site administration → Development → Purge all caches

**Course completion emails not working?**
- Verify feature is enabled in plugin settings
- Check that a template is selected
- Ensure course completion is properly configured in the course

## Advanced Usage

### Custom Recipient Queries

For advanced users, you can modify `queue_manager.php` to add custom recipient selection logic:

```php
case 'custom':
    if (!empty($params['sql'])) {
        $users = $DB->get_records_sql($params['sql']);
        $userids = array_keys($users);
    }
    break;
```

### Retry Failed Emails

Failed emails (attempts < 3) will be automatically retried on the next scheduled task run.

To manually clear failed emails:

```sql
DELETE FROM mdl_local_rvstask_queue WHERE sent = 0 AND attempts >= 3;
```

## Security Considerations

- Only users with `local/rvstask:manage` can create templates
- Only users with `local/rvstask:send` can send emails
- Email sending uses Moodle's built-in `email_to_user()` function (secure and logged)
- Failed emails are logged with error messages for troubleshooting

## Support

For issues or questions:
1. Check the task logs
2. Review the queue statistics
3. Verify your email configuration
4. Contact your system administrator

## Backup Recommendations

The plugin stores data in two tables:
- `mdl_local_rvstask_templates` (your email templates)
- `mdl_local_rvstask_queue` (temporary queue data)

Ensure your Moodle backup strategy includes these tables.

---

**Congratulations!** Your RVS Task plugin is now fully installed and configured. Start creating templates and sending emails to your users!
