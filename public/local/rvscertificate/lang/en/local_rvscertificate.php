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
 * English language strings
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin info
$string['pluginname'] = 'RVS Certificate Issuance';
$string['privacy:metadata'] = 'The RVS Certificate Issuance plugin stores payment and certificate information for users.';

// Capabilities
$string['rvscertificate:request'] = 'Request certificate';
$string['rvscertificate:view'] = 'View certificate';
$string['rvscertificate:manage'] = 'Manage certificate settings';

// Settings
$string['pricing_heading'] = 'Certificate Pricing';
$string['pricing_heading_desc'] = 'Configure certificate pricing';
$string['certificate_price'] = 'Default Certificate Price (KES)';
$string['certificate_price_desc'] = 'Default amount to charge for certificates when no course-specific price is set. Set to 0 to make certificates free by default.';
$string['courseprices_note'] = 'To set different prices for individual courses, use the {$a} page.';
$string['managecourseprices'] = 'Manage Course Prices';

$string['mpesa_heading'] = 'M-Pesa Configuration';
$string['mpesa_heading_desc'] = 'Configure M-Pesa Daraja API credentials';
$string['mpesa_environment'] = 'Environment';
$string['mpesa_environment_desc'] = 'Select sandbox for testing or production for live transactions';
$string['mpesa_sandbox'] = 'Sandbox (Testing)';
$string['mpesa_production'] = 'Production (Live)';
$string['mpesa_consumer_key'] = 'Consumer Key';
$string['mpesa_consumer_key_desc'] = 'M-Pesa API Consumer Key';
$string['mpesa_consumer_secret'] = 'Consumer Secret';
$string['mpesa_consumer_secret_desc'] = 'M-Pesa API Consumer Secret';
$string['mpesa_shortcode'] = 'Business Shortcode';
$string['mpesa_shortcode_desc'] = 'M-Pesa Paybill or Till Number';
$string['mpesa_passkey'] = 'Passkey';
$string['mpesa_passkey_desc'] = 'M-Pesa Lipa Na M-Pesa Online Passkey';
$string['mpesa_callback_url'] = 'Callback URL';
$string['mpesa_callback_url_desc'] = 'URL for M-Pesa payment callbacks (e.g., https://yourdomain.com/local/rvscertificate/callback.php)';

$string['email_heading'] = 'Email Notifications';
$string['email_heading_desc'] = 'Configure email notifications';
$string['send_email'] = 'Send Email';
$string['send_email_desc'] = 'Send email notification when certificate is issued';

// Pages
$string['mycertificate'] = 'My Certificate';
$string['requestcertificate'] = 'Request Certificate';
$string['requestcertificate_desc'] = 'Congratulations on completing this course! You can now purchase and download your certificate.';
$string['viewcertificate'] = 'View Certificate';
$string['downloadcertificate'] = 'Download Certificate';
$string['certificateavailable'] = 'Certificate Available';
$string['certificateavailable_desc'] = 'Your certificate has been issued and is ready to download.';
$string['certificateprice'] = 'Certificate Price';

// Payment
$string['phonenumber'] = 'M-Pesa Phone Number';
$string['phonenumber_help'] = 'Enter your M-Pesa registered phone number (e.g., 0712345678 or 254712345678)';
$string['paynow'] = 'Pay Now';
$string['amount'] = 'Amount';
$string['mpesareceipt'] = 'M-Pesa Receipt';
$string['paymentdate'] = 'Payment Date';
$string['paymentdetails'] = 'Payment Details';
$string['verificationcode'] = 'Verification Code';
$string['checkstatus'] = 'Check Payment Status';

// Payment status
$string['paymentpending'] = 'Payment Pending';
$string['paymentpending_desc'] = 'We have sent an STK Push to your phone. Please enter your M-Pesa PIN to complete the payment. This page will automatically update once payment is confirmed.';
$string['paymentdescription'] = 'Certificate for {$a}';

// Messages
$string['stkpush_sent'] = 'Payment request sent to your phone. Please check your phone and enter your M-Pesa PIN.';
$string['stkpush_failed'] = 'Failed to initiate payment. Please try again or contact support.';
$string['alreadypaid'] = 'You have already paid for this certificate.';
$string['coursenotcompleted'] = 'You must complete the course before requesting a certificate.';
$string['customcertnotavailable'] = 'Custom certificate module is not available. Please contact your administrator.';
$string['nocertificateincourse'] = 'No certificate activity found in this course. Please contact your instructor.';
$string['paymentrequired'] = 'Payment is required to access your certificate. Please complete the payment to continue.';
$string['paymentnotrequired'] = 'Payment is not required for this course certificate.';
$string['certificateavailable_free'] = 'Your certificate is available for free. You can view and download it below.';

// Email messages
$string['certificateemailsubject'] = 'Your certificate for {$a->course}';
$string['certificateemailbody'] = '
<p>Dear {$a->fullname},</p>

<p>Congratulations! Your certificate for <strong>{$a->course}</strong> has been successfully issued.</p>

<p><strong>Verification Code:</strong> {$a->verificationcode}</p>

<p>You can download your certificate by clicking the link below:</p>

<p><a href="{$a->downloadurl}">Download Certificate</a></p>

<p>Keep this verification code safe as it can be used to verify the authenticity of your certificate.</p>

<p>Best regards,<br>
{$a->sitename}</p>
';

$string['certificateavailablesubject'] = 'Certificate available for {$a->course}';
$string['certificateavailablebody'] = '
<p>Dear {$a->fullname},</p>

<p>Congratulations on completing <strong>{$a->course}</strong>!</p>

<p>Your course certificate is now available for purchase at <strong>{$a->price}</strong>.</p>

<p>To request your certificate, please visit:</p>

<p><a href="{$a->url}">Request Certificate</a></p>

<p>Best regards,<br>
{$a->sitename}</p>
';

// Message providers
$string['messageprovider:certificateavailable'] = 'Certificate available notification';
$string['messageprovider:certificateissued'] = 'Certificate issued notification';

// Errors
$string['error_missing_consumerkey'] = 'M-Pesa Consumer Key is not configured';
$string['error_missing_consumersecret'] = 'M-Pesa Consumer Secret is not configured';
$string['error_missing_shortcode'] = 'M-Pesa Business Shortcode is not configured';
$string['error_missing_passkey'] = 'M-Pesa Passkey is not configured';
$string['error_missing_callbackurl'] = 'M-Pesa Callback URL is not configured';
$string['paymentnotcompleted'] = 'Payment not completed';
$string['certificatenotissued'] = 'Certificate has not been issued yet';
$string['couldnotgenerateverificationcode'] = 'Could not generate unique verification code';
$string['couldnotgeneratepdf'] = 'Could not generate certificate PDF';

// Tasks
$string['task_cleanup_pending_payments'] = 'Cleanup old pending payments';

// Privacy API
$string['privacy:metadata:local_rvscertificate_payments'] = 'Certificate payment records';
$string['privacy:metadata:local_rvscertificate_payments:userid'] = 'ID of the user who made the payment';
$string['privacy:metadata:local_rvscertificate_payments:courseid'] = 'ID of the course for which certificate was purchased';
$string['privacy:metadata:local_rvscertificate_payments:amount'] = 'Payment amount';
$string['privacy:metadata:local_rvscertificate_payments:phone'] = 'Phone number used for payment';
$string['privacy:metadata:local_rvscertificate_payments:status'] = 'Payment status';
$string['privacy:metadata:local_rvscertificate_payments:verificationcode'] = 'Certificate verification code';
$string['privacy:metadata:local_rvscertificate_payments:timecreated'] = 'Time when payment record was created';
$string['privacy:metadata:local_rvscertificate_payments:timemodified'] = 'Time when payment record was modified';

$string['privacy:metadata:local_rvscertificate_logs'] = 'Transaction logs';
$string['privacy:metadata:local_rvscertificate_logs:paymentid'] = 'Related payment ID';
$string['privacy:metadata:local_rvscertificate_logs:type'] = 'Log entry type';
$string['privacy:metadata:local_rvscertificate_logs:timecreated'] = 'Time when log was created';

// Course prices management
$string['courseprices'] = 'Course Prices';
$string['addcourseprice'] = 'Add Course Price';
$string['editcourseprice'] = 'Edit Course Price';
$string['coursepricelist'] = 'Course Price List';
$string['nocourseprices'] = 'No course prices have been set. Add a course price using the form above.';
$string['selectcourse'] = 'Select a course...';
$string['price'] = 'Price';
$string['enabled'] = 'Enabled';
$string['disabled'] = 'Disabled';
$string['status'] = 'Status';
$string['actions'] = 'Actions';
$string['priceadded'] = 'Course price added successfully.';
$string['priceupdated'] = 'Course price updated successfully.';
$string['pricedeleted'] = 'Course price deleted successfully.';
$string['confirmdelete'] = 'Are you sure you want to delete this course price?';
$string['enable'] = 'Enable';
$string['disable'] = 'Disable';
