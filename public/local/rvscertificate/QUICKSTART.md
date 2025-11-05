# RVS Certificate Issuance Plugin - Quick Start Guide

## For Administrators

### Quick Setup (5 minutes)

1. **Install Plugin**
   - Place plugin in `moodle/local/rvscertificate`
   - Visit Site Administration > Notifications
   - Click "Upgrade Moodle database now"

2. **Configure Settings**
   - Go to: Site Administration > Plugins > Local plugins > RVS Certificate Issuance > Settings
   - Set certificate price (default: KES 500)
   - Add M-Pesa credentials (see below)
   - Set callback URL: `https://yourdomain.com/local/rvscertificate/callback.php`

3. **M-Pesa Credentials (Sandbox for Testing)**
   ```
   Environment: Sandbox
   Consumer Key: [Get from developer.safaricom.co.ke]
   Consumer Secret: [Get from developer.safaricom.co.ke]
   Shortcode: 174379
   Passkey: [Provided in sandbox]
   ```

### Setting Certificate Price
- Navigate to plugin settings
- Update "Certificate Price (KES)" field
- Click "Save changes"
- New price applies immediately to all new requests

### Viewing Reports
- Go to: Site Administration > Plugins > Local plugins > RVS Certificate Issuance > Payment Report
- View:
  - All transactions
  - Total revenue
  - Pending/Failed payments
  - Certificate details

### Verifying Certificates
- Share this link: `https://yourdomain.com/local/rvscertificate/verify.php`
- Anyone can verify certificate authenticity using verification code

---

## For Course Instructors

### Course Setup

1. **Enable Course Completion**
   - Course settings > Completion tracking
   - Enable and set completion criteria

2. **Add Certificate Activity**
   - Add activity > Custom Certificate
   - Design your certificate template
   - Save

3. **Test**
   - Complete course as student
   - Check certificate appears in navigation
   - Test payment flow

---

## For Students

### Getting Your Certificate

1. **Complete Course**
   - Finish all required activities
   - Meet completion criteria

2. **Request Certificate**
   - Click "My Certificate" in course navigation
   - Enter M-Pesa phone number
   - Click "Pay Now"

3. **Complete Payment**
   - Receive STK Push on phone
   - Enter M-Pesa PIN
   - Wait for confirmation

4. **Download**
   - Certificate generated automatically
   - Download link emailed to you
   - Download anytime from certificate page

### Verification Code
- Each certificate has unique code
- Keep it safe for verification
- Share with employers if needed

---

## Troubleshooting Quick Fixes

### No STK Push Received
- Check phone number format (254XXXXXXXXX)
- Verify M-Pesa credentials
- Check Safaricom network status

### Certificate Not Generated
- Wait 1-2 minutes for callback
- Check payment status page
- Contact administrator if delayed

### Email Not Received
- Check spam folder
- Verify email address in profile
- Download directly from certificate page

---

## Important URLs

| Purpose | URL |
|---------|-----|
| Plugin Settings | /admin/settings.php?section=local_rvscertificate_settings |
| Payment Report | /local/rvscertificate/report.php |
| Certificate Request | /local/rvscertificate/index.php?courseid={id} |
| Verify Certificate | /local/rvscertificate/verify.php |
| M-Pesa Callback | /local/rvscertificate/callback.php |

---

## Database Tables

Quick reference for admins who need to check database:

- `mdl_local_rvscertificate_payments` - Payment records
- `mdl_local_rvscertificate_logs` - Transaction logs
- `mdl_customcert_issues` - Certificate issues (from Custom Certificate plugin)

---

## Support Checklist

When reporting issues, provide:
- [ ] Moodle version
- [ ] Plugin version
- [ ] Payment ID from database
- [ ] Error messages from logs table
- [ ] M-Pesa transaction details
- [ ] Screenshot of issue

---

## Security Notes

✅ Always use HTTPS in production
✅ Keep M-Pesa credentials secure
✅ Regularly backup payment records
✅ Monitor transaction logs
✅ Set appropriate capability permissions

---

## Version: 1.0.0
**Last Updated**: November 5, 2025
