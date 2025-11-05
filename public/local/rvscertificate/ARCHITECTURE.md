# RVS Certificate Issuance - Architecture & Workflow

## System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                         MOODLE SYSTEM                               │
│                                                                     │
│  ┌──────────────┐      ┌─────────────┐      ┌─────────────────┐  │
│  │   Student    │      │  Instructor │      │  Administrator  │  │
│  │              │      │             │      │                 │  │
│  │ - View Cert  │      │ - Setup     │      │ - Config M-Pesa │  │
│  │ - Pay M-Pesa │      │   Course    │      │ - Set Pricing   │  │
│  │ - Download   │      │ - Add Cert  │      │ - View Reports  │  │
│  └──────┬───────┘      └──────┬──────┘      └────────┬────────┘  │
│         │                     │                       │            │
│         └─────────────────────┼───────────────────────┘            │
│                               │                                    │
│  ┌────────────────────────────┼───────────────────────────────┐   │
│  │         RVS CERTIFICATE PLUGIN                              │   │
│  │                            │                                │   │
│  │  ┌─────────────────────────▼──────────────────────┐        │   │
│  │  │         Core Components                        │        │   │
│  │  │                                                 │        │   │
│  │  │  ┌──────────────────────────────────────────┐  │        │   │
│  │  │  │  1. Course Completion Observer           │  │        │   │
│  │  │  │     - Detects course completion          │  │        │   │
│  │  │  │     - Triggers notifications             │  │        │   │
│  │  │  └──────────────────────────────────────────┘  │        │   │
│  │  │                                                 │        │   │
│  │  │  ┌──────────────────────────────────────────┐  │        │   │
│  │  │  │  2. Payment Processor                    │  │        │   │
│  │  │  │     - Validate completion                │  │        │   │
│  │  │  │     - Create payment record              │  │        │   │
│  │  │  │     - Initiate M-Pesa STK                │  │        │   │
│  │  │  └──────────────────────────────────────────┘  │        │   │
│  │  │                                                 │        │   │
│  │  │  ┌──────────────────────────────────────────┐  │        │   │
│  │  │  │  3. M-Pesa Client                        │  │        │   │
│  │  │  │     - OAuth authentication               │  │        │   │
│  │  │  │     - STK Push API                       │  │        │   │
│  │  │  │     - Phone number formatting            │  │        │   │
│  │  │  └──────────────────────────────────────────┘  │        │   │
│  │  │                                                 │        │   │
│  │  │  ┌──────────────────────────────────────────┐  │        │   │
│  │  │  │  4. Callback Handler                     │  │        │   │
│  │  │  │     - Receive M-Pesa callback            │  │        │   │
│  │  │  │     - Validate payment                   │  │        │   │
│  │  │  │     - Update payment status              │  │        │   │
│  │  │  └──────────────────────────────────────────┘  │        │   │
│  │  │                                                 │        │   │
│  │  │  ┌──────────────────────────────────────────┐  │        │   │
│  │  │  │  5. Certificate Generator                │  │        │   │
│  │  │  │     - Generate verification code         │  │        │   │
│  │  │  │     - Create certificate issue           │  │        │   │
│  │  │  │     - Trigger email                      │  │        │   │
│  │  │  └──────────────────────────────────────────┘  │        │   │
│  │  │                                                 │        │   │
│  │  └─────────────────────────────────────────────────┘        │   │
│  │                                                              │   │
│  │  ┌────────────────────────────────────────────────────────┐ │   │
│  │  │         Database Tables                                │ │   │
│  │  │                                                        │ │   │
│  │  │  • local_rvscertificate_payments                      │ │   │
│  │  │  • local_rvscertificate_logs                          │ │   │
│  │  └────────────────────────────────────────────────────────┘ │   │
│  │                                                              │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
                                 │
                                 │ HTTPS
                                 │
┌────────────────────────────────▼─────────────────────────────────┐
│                    SAFARICOM DARAJA API                          │
│                                                                  │
│  ┌──────────────┐    ┌──────────────┐    ┌─────────────────┐   │
│  │   OAuth      │───▶│  STK Push    │───▶│   Callback      │   │
│  │   /generate  │    │  /process    │    │   Response      │   │
│  └──────────────┘    └──────────────┘    └─────────────────┘   │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

## Complete User Journey Flow

```
START: Student Completes Course
    │
    ▼
┌─────────────────────────────────────────────────────────┐
│ 1. Course Completion Event                             │
│    - Moodle marks course as complete                   │
│    - completion_info API validates completion          │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 2. Observer Triggered                                   │
│    - local_rvscertificate\observer::course_completed()  │
│    - Checks if Custom Certificate exists in course     │
│    - Sends "Certificate Available" notification        │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 3. Student Receives Notification                       │
│    - Email/popup notification                          │
│    - "My Certificate" link appears in course menu      │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 4. Student Visits Certificate Page                     │
│    - URL: /local/rvscertificate/index.php             │
│    - Validates course completion                       │
│    - Checks payment status                             │
│    - Shows payment form if not paid                    │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 5. Student Enters Phone Number                         │
│    - Form with M-Pesa phone input                      │
│    - Format: 07XX or 254XXX                            │
│    - Displays certificate price                        │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 6. Payment Request Submitted                           │
│    - POST to /local/rvscertificate/request.php        │
│    - Creates payment record (status: pending)          │
│    - Generates payment ID                              │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 7. M-Pesa Client Initiates STK Push                    │
│    - mpesa_client::stk_push()                          │
│    - Gets OAuth token                                   │
│    - Formats phone number                              │
│    - Sends STK Push request to Daraja API              │
│    - Logs request in database                          │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 8. Daraja API Processes Request                        │
│    - Validates credentials                             │
│    - Sends STK Push to phone                           │
│    - Returns Checkout Request ID                       │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 9. Student Receives STK Push on Phone                  │
│    - Popup on phone requesting PIN                     │
│    - Shows amount and business name                    │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 10. Student Enters M-Pesa PIN                          │
│     - Confirms payment on phone                        │
│     - M-Pesa validates PIN and balance                 │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 11. M-Pesa Processes Transaction                       │
│     - Deducts amount from account                      │
│     - Generates receipt number                         │
│     - Prepares callback data                           │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 12. Daraja Sends Callback                              │
│     - POST to callback.php                             │
│     - Contains payment result                          │
│     - Includes receipt number and timestamp            │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 13. Callback Handler Validates Payment                 │
│     - Receives callback data                           │
│     - Logs to database                                 │
│     - Finds payment record by Checkout Request ID     │
│     - Updates payment status to 'completed'            │
│     - Stores receipt number                            │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 14. Certificate Generator Triggered                    │
│     - certificate_generator::process_payment()         │
│     - Generates unique verification code               │
│     - Creates customcert_issues record                 │
│     - Links certificate to user                        │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 15. Email Notification Sent                            │
│     - Composes email with details                      │
│     - Includes download link                           │
│     - Includes verification code                       │
│     - Sends via Moodle messaging                       │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 16. Student Receives Email                             │
│     - Download link to certificate                     │
│     - Verification code displayed                      │
│     - Instructions for future downloads                │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 17. Student Downloads Certificate                      │
│     - Clicks download link                             │
│     - PDF generated by Custom Certificate plugin       │
│     - Verification code included on certificate        │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 18. Future Downloads Available                         │
│     - Can download anytime from certificate page       │
│     - No additional payment required                   │
│     - Verification code remains same                   │
└─────────────────────────────────────────────────────────┘
                     │
                     ▼
                   END

OPTIONAL: Certificate Verification
    │
    ▼
┌─────────────────────────────────────────────────────────┐
│ Anyone (Employer/Institution) Visits verify.php        │
│    - Enters verification code                          │
│    - System looks up payment record                    │
│    - Displays certificate authenticity                 │
│    - Shows recipient, course, date                     │
└─────────────────────────────────────────────────────────┘
```

## Data Flow Diagram

```
┌──────────┐
│ Student  │
└────┬─────┘
     │ Phone Number + Request
     ▼
┌──────────────────┐
│  request.php     │
│  - Validate      │
│  - Create record │
└────┬─────────────┘
     │ Payment ID
     ▼
┌──────────────────┐      OAuth Token      ┌──────────────┐
│  mpesa_client    │◄─────────────────────►│ Daraja API   │
│  - Get token     │                       │ /oauth       │
│  - STK Push      │──────────────────────▶│ /stkpush     │
└────┬─────────────┘  STK Request          └──────────────┘
     │ Checkout Request ID                        │
     ▼                                            │
┌──────────────────┐                              │
│  Database        │                              │
│  - payments      │                              │
│  - logs          │                              │
└────┬─────────────┘                              │
     │                                            │
     │                            Callback Data   │
     │                          ┌─────────────────┘
     │                          ▼
     │                  ┌──────────────────┐
     └─────────────────►│  callback.php    │
                        │  - Validate      │
                        │  - Update status │
                        └────┬─────────────┘
                             │ Payment Validated
                             ▼
                ┌────────────────────────┐
                │ certificate_generator  │
                │ - Generate code        │
                │ - Create issue         │
                │ - Send email           │
                └────┬───────────────────┘
                     │ Certificate + Code
                     ▼
                ┌──────────────────┐
                │  Email System    │
                │  - Send to user  │
                └────┬─────────────┘
                     │
                     ▼
                ┌──────────┐
                │ Student  │
                │ Downloads│
                └──────────┘
```

## Component Integration Map

```
┌────────────────────────────────────────────────────────────┐
│                    MOODLE CORE APIS                        │
├────────────────────────────────────────────────────────────┤
│                                                            │
│  Completion API ──┐                                        │
│  Event System ────┼───► Observer ───► Notification        │
│  Messaging API ───┤                                        │
│  Privacy API ─────┤                                        │
│  Navigation API ──┤                                        │
│  Database API ────┼───► Plugin Core ───► M-Pesa Client    │
│  Context API ─────┤                                        │
│  Capability API ──┤                                        │
│  Task API ────────┘                                        │
│                                                            │
└────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌────────────────────────────────────────────────────────────┐
│              CUSTOM CERTIFICATE PLUGIN                     │
│  - Certificate Templates                                   │
│  - Certificate Issues                                      │
│  - PDF Generation                                          │
└────────────────────────────────────────────────────────────┘
```

---

## Key Technical Decisions

### 1. **Local Plugin Type**
   - Chosen for flexibility
   - No interference with core modules
   - Easy to install/uninstall

### 2. **Database Design**
   - Two tables for separation of concerns
   - Proper indexing for performance
   - Foreign key relationships

### 3. **M-Pesa Integration**
   - Direct API integration (not third-party library)
   - Full control over requests/responses
   - Comprehensive logging

### 4. **Certificate Generation**
   - Leverages existing Custom Certificate plugin
   - No reinventing the wheel
   - Professional PDF output

### 5. **Security Approach**
   - Multi-layered validation
   - Capability-based access control
   - Proper sanitization throughout

### 6. **Callback Handling**
   - No authentication required (public endpoint)
   - Validation through checkout request ID
   - Comprehensive logging for debugging

---

**Last Updated:** November 5, 2025  
**Version:** 1.0.0
