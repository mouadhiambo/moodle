# RVS Certificate Issuance Plugin - Installation & Configuration Guide

## Overview
The RVS Certificate Issuance plugin enables learners to purchase and download certificates after completing Moodle courses. It integrates with M-Pesa for payment processing and automatically generates certificates upon successful payment.

## Features
- ✅ Automatic certificate availability after course completion
- ✅ M-Pesa STK Push payment integration
- ✅ Automatic certificate generation after payment validation
- ✅ Email notifications to learners
- ✅ Unique verification codes for each certificate
- ✅ Certificate verification system
- ✅ Admin configurable pricing
- ✅ Comprehensive payment reporting
- ✅ Privacy API compliance

## Requirements
1. **Moodle Version**: 4.3 or higher
2. **Custom Certificate Plugin**: The `mod_customcert` plugin must be installed and enabled
3. **M-Pesa Account**: You need M-Pesa Daraja API credentials:
   - Consumer Key
   - Consumer Secret
   - Business Shortcode
   - Passkey (Lipa Na M-Pesa Online)

## Installation

### Step 1: Install the Plugin
1. Download or clone this plugin
2. Place the `rvscertificate` folder in `moodle/local/`
3. Navigate to **Site Administration > Notifications**
4. Click **Upgrade Moodle database now**
5. Follow the on-screen prompts to complete installation

### Step 2: Install Custom Certificate Plugin (if not already installed)
1. Download the Custom Certificate plugin from the Moodle plugins directory
2. Install it following the same procedure
3. Ensure it's enabled in **Site Administration > Plugins > Activity modules**

### Step 3: Configure M-Pesa Credentials

#### Getting M-Pesa Daraja API Credentials

**For Sandbox (Testing):**
1. Visit https://developer.safaricom.co.ke
2. Register for a developer account
3. Create a new app
4. Select "Lipa Na M-Pesa Online" API
5. Note down your:
   - Consumer Key
   - Consumer Secret
   - Passkey (provided in sandbox environment)
   - Test Credentials (Shortcode: 174379)

**For Production:**
1. Contact Safaricom M-Pesa Business team
2. Apply for Lipa Na M-Pesa Online
3. Once approved, you'll receive:
   - Consumer Key
   - Consumer Secret
   - Your Business Shortcode
   - Passkey

#### Configure Plugin Settings
1. Go to **Site Administration > Plugins > Local plugins > RVS Certificate Issuance > Settings**
2. Configure the following:

**Certificate Pricing:**
- **Certificate Price (KES)**: Set the price (e.g., 500)

**M-Pesa Configuration:**
- **Environment**: Select `Sandbox` for testing or `Production` for live
- **Consumer Key**: Enter your M-Pesa Consumer Key
- **Consumer Secret**: Enter your M-Pesa Consumer Secret
- **Business Shortcode**: Enter your Paybill/Till Number
- **Passkey**: Enter your Lipa Na M-Pesa Online Passkey
- **Callback URL**: Enter your full callback URL:
  ```
  https://yourdomain.com/local/rvscertificate/callback.php
  ```
  ⚠️ **Important**: This URL must be accessible from the internet for M-Pesa to send payment confirmations

**Email Notifications:**
- **Send Email**: Check to enable email notifications when certificates are issued

3. Click **Save changes**

## Course Setup

### Step 1: Enable Course Completion
1. Edit your course settings
2. Go to **Completion tracking**
3. Enable **Completion tracking** and set completion criteria

### Step 2: Add Custom Certificate Activity
1. In your course, click **Add an activity or resource**
2. Select **Custom Certificate**
3. Configure the certificate template:
   - Design your certificate layout
   - Add elements (name, course name, date, etc.)
   - **Important**: The verification code from RVS Certificate plugin will be used
4. Save the certificate activity

### Step 3: Configure Activity Completion
1. Ensure all activities in the course have completion tracking enabled
2. Set up course completion criteria under **Course administration > Course completion**

## How It Works

### User Flow
1. **Course Completion**: Student completes all course requirements
2. **Notification**: Student receives notification that certificate is available
3. **Certificate Request**: Student navigates to the certificate page
4. **Payment**: Student enters M-Pesa phone number and initiates payment
5. **STK Push**: Student receives STK Push on their phone
6. **PIN Entry**: Student enters M-Pesa PIN to complete payment
7. **Callback**: Daraja API sends payment confirmation to your callback URL
8. **Certificate Generation**: Plugin automatically generates certificate with unique verification code
9. **Email Delivery**: Certificate download link emailed to student
10. **Download**: Student can download certificate anytime

### Admin Features
- **Payment Report**: View all certificate payments at **Site Administration > Plugins > Local plugins > RVS Certificate Issuance > Payment Report**
- **Statistics**: View total certificates issued, revenue, pending and failed payments
- **Price Management**: Change certificate price anytime in settings

### Certificate Verification
- Students and employers can verify certificates at: `https://yourdomain.com/local/rvscertificate/verify.php`
- Enter the verification code to see certificate details

## Testing

### Sandbox Testing
1. Use Sandbox credentials from Safaricom Developer Portal
2. Test phone number: `254708374149` (Sandbox test number)
3. When you receive STK Push, use test PIN: `1234`
4. Monitor callback logs in the database table `mdl_local_rvscertificate_logs`

### Verify Installation
1. Create a test course with completion criteria
2. Add a Custom Certificate activity
3. Enroll a test user and mark course as complete
4. Check if certificate page appears in course navigation
5. Test the payment flow with sandbox credentials

## Troubleshooting

### Common Issues

**1. STK Push Not Received**
- Verify M-Pesa credentials are correct
- Check phone number format (should be 254XXXXXXXXX)
- Ensure M-Pesa account has sufficient balance for testing
- Check `mdl_local_rvscertificate_logs` table for error messages

**2. Callback Not Working**
- Ensure callback URL is publicly accessible (not localhost)
- Check server logs for incoming POST requests
- Verify callback URL is correctly configured in plugin settings
- For testing, use ngrok or similar service to expose local server

**3. Certificate Not Generated**
- Check if Custom Certificate plugin is installed and enabled
- Verify certificate activity exists in the course
- Check `mdl_local_rvscertificate_logs` for generation errors
- Ensure payment status is "completed"

**4. Email Not Sent**
- Verify Moodle email settings are configured
- Check user has valid email address
- Look in **Site Administration > Server > Email > Outgoing mail configuration**
- Check message processor settings

## Database Tables

### `mdl_local_rvscertificate_payments`
Stores payment records with status, M-Pesa details, and verification codes.

### `mdl_local_rvscertificate_logs`
Logs all API requests/responses for debugging and audit purposes.

## Security Considerations

1. **SSL Required**: Always use HTTPS for production
2. **Callback Security**: The callback endpoint validates incoming requests
3. **Verification Codes**: Unique codes prevent certificate forgery
4. **Privacy**: Complies with GDPR via Privacy API implementation
5. **Permissions**: Only enrolled students can request certificates

## File Structure
```
local/rvscertificate/
├── callback.php                  # M-Pesa callback handler
├── check_status.php             # Payment status checker
├── index.php                    # Certificate request page
├── lib.php                      # Plugin library functions
├── README.md                    # Basic readme
├── INSTALL.md                   # This file
├── report.php                   # Admin payment report
├── request.php                  # Payment processor
├── settings.php                 # Plugin settings (deprecated, use db/admin.php)
├── verify.php                   # Certificate verification page
├── version.php                  # Plugin version
├── classes/
│   ├── certificate_generator.php  # Certificate generation logic
│   ├── mpesa_client.php          # M-Pesa API client
│   ├── observer.php              # Event observers
│   └── privacy/
│       └── provider.php          # Privacy API implementation
├── db/
│   ├── access.php               # Capability definitions
│   ├── admin.php                # Admin menu configuration
│   ├── events.php               # Event observers configuration
│   ├── install.xml              # Database schema
│   ├── messages.php             # Message providers
│   └── upgrade.php              # Upgrade scripts
└── lang/
    └── en/
        └── local_rvscertificate.php  # English language strings
```

## Support & Contribution
For issues, questions, or contributions, please contact the RVS development team.

## License
This plugin is licensed under GNU GPL v3 or later.

## Version History
- **1.0.0** (2025-11-05): Initial release
  - M-Pesa STK Push integration
  - Automatic certificate generation
  - Email notifications
  - Verification system
  - Admin reporting
