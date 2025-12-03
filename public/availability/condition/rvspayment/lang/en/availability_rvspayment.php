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
 * Language strings for availability_rvspayment.
 *
 * @package    availability_rvspayment
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin info.
$string['pluginname'] = 'Restriction by payment';
$string['title'] = 'Payment';
$string['description'] = 'Require students to pay to access this activity or section.';

// Form labels.
$string['label_price'] = 'Price';
$string['label_currency'] = 'Currency';
$string['label_isfree'] = 'Free access';
$string['label_requireprevious'] = 'Require previous sections paid';

// Currency.
$string['currency_kes'] = 'Kenyan Shilling (KES)';

// Errors.
$string['error_setprice'] = 'You must set a price greater than 0, or mark as free access.';

// Descriptions shown to students.
$string['description_free'] = 'This content is available for free.';
$string['description_not'] = 'You must NOT have paid {$a->currency} {$a->price} for this content.';
$string['description_withpayment'] = 'Payment of <strong>{$a->currency} {$a->price}</strong> is required. <a href="{$a->payurl}" class="btn btn-primary btn-sm rvspayment-pay-btn">Pay to unlock</a>';
$string['description_paid'] = 'You have paid for this content.';
$string['description_requireprevious'] = 'You must pay for all previous sections before accessing this one.';

// Payment page.
$string['pagetitle'] = 'Unlock Content';
$string['paymentfor_section'] = 'Payment to unlock: {$a}';
$string['paymentfor_module'] = 'Payment to unlock: {$a}';
$string['sectionname'] = 'Section {$a->num}: {$a->name}';
$string['sectionname_noname'] = 'Section {$a->num}';
$string['amount_to_pay'] = 'Amount to pay';
$string['phonenumber'] = 'M-Pesa Phone Number';
$string['phonenumber_help'] = 'Enter your M-Pesa registered phone number (e.g., 0712345678 or 254712345678)';
$string['paynow'] = 'Pay Now via M-Pesa';
$string['processing'] = 'Processing payment...';
$string['backtocourse'] = 'Back to course';

// Payment status.
$string['paymentpending'] = 'Payment Pending';
$string['paymentpending_desc'] = 'We have sent an STK Push to your phone. Please enter your M-Pesa PIN to complete the payment.';
$string['paymentsuccess'] = 'Payment Successful';
$string['paymentsuccess_desc'] = 'Your payment has been received. You can now access the content.';
$string['paymentfailed'] = 'Payment Failed';
$string['paymentfailed_desc'] = 'Your payment could not be processed. Please try again.';
$string['checkstatus'] = 'Check Payment Status';
$string['tryagain'] = 'Try Again';
$string['gotocontent'] = 'Access Content';

// STK Push messages.
$string['stkpush_sent'] = 'Payment request sent to your phone. Please check your phone and enter your M-Pesa PIN.';
$string['stkpush_failed'] = 'Failed to initiate payment. Please try again or contact support.';
$string['stkpush_description'] = 'Unlock {$a}';

// Status messages.
$string['alreadypaid'] = 'You have already paid for this content.';
$string['previousnotpaid'] = 'You must pay for the previous sections before unlocking this one.';
$string['invaliditem'] = 'Invalid item specified.';
$string['notloggedin'] = 'You must be logged in to make a payment.';
$string['notenrolled'] = 'You must be enrolled in this course to make a payment.';
$string['paymentnotfound'] = 'Payment record not found.';
$string['invalidphonenumber'] = 'Please enter a valid phone number.';

// Callback messages.
$string['callback_success'] = 'Payment successful. Content unlocked.';
$string['callback_failed'] = 'Payment failed or was cancelled.';

// Notifications.
$string['notification_subject'] = 'Content unlocked: {$a}';
$string['notification_body'] = 'Your payment of {$a->currency} {$a->amount} has been received. You can now access "{$a->itemname}" in the course "{$a->coursename}".';

// Admin settings.
$string['settings'] = 'RVS Payment Settings';
$string['default_price'] = 'Default price';
$string['default_price_desc'] = 'Default price for new payment restrictions (can be overridden per section/activity).';
$string['callback_url'] = 'Callback URL';
$string['callback_url_desc'] = 'URL for M-Pesa payment callbacks for section payments. This should be: {$a}';

// Privacy.
$string['privacy:metadata:availability_rvspayment_pay'] = 'Stores information about payments made to unlock course sections and activities.';
$string['privacy:metadata:availability_rvspayment_pay:userid'] = 'The user who made the payment.';
$string['privacy:metadata:availability_rvspayment_pay:courseid'] = 'The course where the payment was made.';
$string['privacy:metadata:availability_rvspayment_pay:amount'] = 'The amount paid.';
$string['privacy:metadata:availability_rvspayment_pay:phone'] = 'The phone number used for payment.';
$string['privacy:metadata:availability_rvspayment_pay:status'] = 'The status of the payment.';
$string['privacy:metadata:availability_rvspayment_pay:timecreated'] = 'The time when the payment was initiated.';

// Report page.
$string['report_title'] = 'Section Payment Report';
$string['report_description'] = 'View all section and activity unlock payments.';
$string['report_col_id'] = 'ID';
$string['report_col_user'] = 'User';
$string['report_col_course'] = 'Course';
$string['report_col_item'] = 'Item';
$string['report_col_amount'] = 'Amount';
$string['report_col_phone'] = 'Phone';
$string['report_col_status'] = 'Status';
$string['report_col_receipt'] = 'M-Pesa Receipt';
$string['report_col_date'] = 'Date';

// Report filters.
$string['allcourses'] = 'All courses';
$string['allstatuses'] = 'All statuses';
$string['filter'] = 'Filter';
$string['status_pending'] = 'Pending';
$string['status_completed'] = 'Completed';
$string['status_failed'] = 'Failed';

// Report stats.
$string['stat_total'] = 'Total Payments';
$string['stat_completed'] = 'Completed';
$string['stat_pending'] = 'Pending';
$string['stat_revenue'] = 'Total Revenue';

// Report downloads.
$string['downloadcsv'] = 'Download CSV';
$string['downloadexcel'] = 'Download Excel';

// Message provider.
$string['messageprovider:payment_success'] = 'Section unlock payment confirmation';
$string['privacy:metadata:availability_rvspayment_pay:timecreated'] = 'The time when the payment was initiated.';
