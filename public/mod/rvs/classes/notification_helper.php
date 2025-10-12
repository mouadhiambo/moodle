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
 * Notification helper for mod_rvs
 *
 * @package    mod_rvs
 * @copyright  2025 RVIBS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_rvs;

defined('MOODLE_INTERNAL') || die();

/**
 * Notification helper class for sending admin notifications
 */
class notification_helper {

    /**
     * Check if admin notifications are enabled
     *
     * @return bool True if notifications are enabled
     */
    public static function are_notifications_enabled() {
        return (bool)get_config('mod_rvs', 'enable_admin_notifications');
    }

    /**
     * Send notification to site administrators
     *
     * @param string $subject Notification subject
     * @param string $message Notification message
     * @param string $errortype Type of error (extraction, generation, rag)
     * @param array $context Additional context information
     */
    public static function notify_admins($subject, $message, $errortype = 'general', $context = []) {
        global $DB;

        // Check if notifications are enabled
        if (!self::are_notifications_enabled()) {
            mtrace('[DEBUG] Admin notifications are disabled, skipping notification');
            return;
        }

        try {
            // Get all site administrators
            $admins = get_admins();

            if (empty($admins)) {
                mtrace('[WARNING] No site administrators found to send notification');
                return;
            }

            // Build full message with context
            $fullmessage = self::build_notification_message($message, $errortype, $context);

            // Send notification to each admin
            $sentcount = 0;
            foreach ($admins as $admin) {
                try {
                    $messageid = message_send(self::create_message_object(
                        $admin->id,
                        $subject,
                        $fullmessage
                    ));

                    if ($messageid) {
                        $sentcount++;
                    }
                } catch (\Exception $e) {
                    mtrace('[ERROR] Failed to send notification to admin ' . $admin->id . ': ' . 
                           $e->getMessage());
                }
            }

            mtrace('[INFO] Sent ' . $sentcount . ' admin notifications for: ' . $subject);

        } catch (\Exception $e) {
            mtrace('[ERROR] Failed to send admin notifications: ' . $e->getMessage());
            debugging('Failed to send admin notifications: ' . $e->getMessage(), DEBUG_NORMAL);
        }
    }

    /**
     * Notify admins about content extraction failure
     *
     * @param string $sourcetype Type of source (file, book)
     * @param int $sourceid Source ID
     * @param string $error Error message
     * @param int $rvsid RVS instance ID
     */
    public static function notify_extraction_failure($sourcetype, $sourceid, $error, $rvsid = null) {
        $subject = 'RVS: Content Extraction Failed';
        
        $context = [
            'source_type' => $sourcetype,
            'source_id' => $sourceid,
            'rvs_id' => $rvsid,
            'error' => $error,
        ];

        $message = "Content extraction failed for {$sourcetype} (ID: {$sourceid}).\n\n" .
                  "Error: {$error}\n\n" .
                  "Suggested actions:\n" .
                  "- Check if the file is corrupted or encrypted\n" .
                  "- Verify the file format is supported\n" .
                  "- Check server logs for more details\n" .
                  "- Try re-uploading the content";

        self::notify_admins($subject, $message, 'extraction', $context);
    }

    /**
     * Notify admins about AI generation failure
     *
     * @param string $moduletype Type of module (mindmap, podcast, etc.)
     * @param string $error Error message
     * @param int $rvsid RVS instance ID
     */
    public static function notify_generation_failure($moduletype, $error, $rvsid = null) {
        $subject = 'RVS: AI Generation Failed';
        
        $context = [
            'module_type' => $moduletype,
            'rvs_id' => $rvsid,
            'error' => $error,
        ];

        $message = "AI content generation failed for {$moduletype} module.\n\n" .
                  "Error: {$error}\n\n" .
                  "Suggested actions:\n" .
                  "- Check AI provider configuration (API key, endpoint)\n" .
                  "- Verify API quota and rate limits\n" .
                  "- Check network connectivity to AI provider\n" .
                  "- Review server logs for more details\n" .
                  "- Try regenerating the content";

        self::notify_admins($subject, $message, 'generation', $context);
    }

    /**
     * Notify admins about RAG processing failure
     *
     * @param string $tasktype Task type
     * @param string $error Error message
     * @param int $rvsid RVS instance ID
     */
    public static function notify_rag_failure($tasktype, $error, $rvsid = null) {
        $subject = 'RVS: RAG Processing Failed';
        
        $context = [
            'task_type' => $tasktype,
            'rvs_id' => $rvsid,
            'error' => $error,
        ];

        $message = "RAG processing failed for {$tasktype} task.\n\n" .
                  "Error: {$error}\n\n" .
                  "Note: The system has fallen back to content truncation.\n\n" .
                  "Suggested actions:\n" .
                  "- Check server memory limits\n" .
                  "- Review content size and complexity\n" .
                  "- Check server logs for more details\n" .
                  "- Consider adjusting RAG parameters";

        self::notify_admins($subject, $message, 'rag', $context);
    }

    /**
     * Build full notification message with context
     *
     * @param string $message Base message
     * @param string $errortype Error type
     * @param array $context Context information
     * @return string Full message
     */
    private static function build_notification_message($message, $errortype, $context) {
        global $CFG;

        $fullmessage = $message . "\n\n";
        $fullmessage .= "---\n";
        $fullmessage .= "Error Type: " . $errortype . "\n";
        $fullmessage .= "Time: " . userdate(time()) . "\n";
        
        if (!empty($context)) {
            $fullmessage .= "\nContext:\n";
            foreach ($context as $key => $value) {
                if ($value !== null) {
                    $fullmessage .= "  " . ucfirst(str_replace('_', ' ', $key)) . ": " . $value . "\n";
                }
            }
        }

        $fullmessage .= "\nSite: " . $CFG->wwwroot . "\n";
        $fullmessage .= "---\n\n";
        $fullmessage .= "This is an automated notification from the RVS AI Learning Suite plugin.\n";
        $fullmessage .= "To disable these notifications, go to:\n";
        $fullmessage .= "Site administration > Plugins > Activity modules > RVS AI Learning Suite\n";

        return $fullmessage;
    }

    /**
     * Create message object for sending
     *
     * @param int $userid User ID to send to
     * @param string $subject Message subject
     * @param string $message Message content
     * @return \stdClass Message object
     */
    private static function create_message_object($userid, $subject, $message) {
        $messageobj = new \core\message\message();
        $messageobj->component = 'mod_rvs';
        $messageobj->name = 'adminerror';
        $messageobj->userfrom = \core_user::get_noreply_user();
        $messageobj->userto = $userid;
        $messageobj->subject = $subject;
        $messageobj->fullmessage = $message;
        $messageobj->fullmessageformat = FORMAT_PLAIN;
        $messageobj->fullmessagehtml = nl2br(htmlspecialchars($message));
        $messageobj->smallmessage = $subject;
        $messageobj->notification = 1;

        return $messageobj;
    }
}
