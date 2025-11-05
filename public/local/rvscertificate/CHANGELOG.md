# Changelog

All notable changes to the RVS Certificate Issuance plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-11-05

### Added
- Initial release of RVS Certificate Issuance plugin
- M-Pesa Daraja API integration with STK Push
- Automatic certificate generation after course completion
- Course completion event observer
- Payment processing and validation system
- M-Pesa callback handler for payment confirmation
- Unique verification code generation for each certificate
- Email notification system:
  - Certificate available notification
  - Certificate issued notification with download link
- Admin configuration interface:
  - Certificate pricing settings
  - M-Pesa API credentials management
  - Email notification toggle
- Admin payment report dashboard:
  - Transaction list with filters
  - Revenue statistics
  - Payment status monitoring
- Public certificate verification system
- Privacy API implementation (GDPR compliant)
- Capability definitions for access control
- Database schema with two tables:
  - Payments table
  - Transaction logs table
- Scheduled task for cleaning up old pending payments
- CLI test script for M-Pesa configuration validation
- Responsive CSS styling for mobile and desktop
- Navigation integration in course menu
- Integration with Custom Certificate plugin
- Comprehensive documentation:
  - Installation guide (INSTALL.md)
  - Quick start guide (QUICKSTART.md)
  - Complete summary (SUMMARY.md)
  - README file

### Features
- ✅ Course completion triggered certificate availability
- ✅ M-Pesa STK Push payment gateway
- ✅ Automatic phone number formatting (supports 07XX, 254XXX formats)
- ✅ Real-time payment status tracking
- ✅ Secure callback handling from Daraja API
- ✅ Sandbox and Production environment support
- ✅ Certificate download after payment validation
- ✅ Email delivery with verification code
- ✅ Admin configurable pricing
- ✅ Comprehensive transaction logging
- ✅ Payment report with statistics
- ✅ Certificate verification for authenticity
- ✅ Mobile-responsive interface
- ✅ Multi-language support (English included)

### Security
- Implemented CSRF protection with sesskey validation
- SQL injection prevention with parameterized queries
- XSS protection with proper output sanitization
- Capability-based access control
- Secure M-Pesa callback validation
- Unique verification codes to prevent forgery
- HTTPS/SSL ready for production

### Database
- Created `mdl_local_rvscertificate_payments` table
- Created `mdl_local_rvscertificate_logs` table
- Added proper indexes for performance
- Foreign key relationships established

### Compatibility
- Moodle 4.3 or higher
- PHP 7.4 or higher
- Requires mod_customcert plugin
- PostgreSQL, MySQL/MariaDB compatible

### Documentation
- Comprehensive installation guide
- API integration documentation
- Troubleshooting guide
- Quick reference for administrators
- CLI tool usage instructions

### Developer
- Well-structured codebase following Moodle coding standards
- Namespaced classes
- Proper use of Moodle APIs
- Extensive inline documentation
- Privacy API compliance
- Event observer implementation
- Message provider configuration

## [Unreleased]

### Planned for Future Releases
- [ ] Support for additional payment gateways (PayPal, Stripe)
- [ ] Bulk certificate issuance for administrators
- [ ] Per-course certificate pricing override
- [ ] Discount codes and promotional offers
- [ ] Student payment history dashboard
- [ ] Refund management system
- [ ] Advanced analytics and reporting
- [ ] Export functionality (CSV, PDF reports)
- [ ] SMS notifications integration
- [ ] WhatsApp Business API integration
- [ ] Multi-currency support
- [ ] Certificate bundle purchases
- [ ] Subscription-based certificates
- [ ] QR code on certificates linking to verification
- [ ] Certificate revocation system
- [ ] REST API endpoints for external integrations

### Known Issues
- None reported in initial release

### Notes
- This is the stable initial release
- All core features tested and working
- Ready for production deployment
- Sandbox testing recommended before production use

---

## Version History

| Version | Date       | Description                          |
|---------|------------|--------------------------------------|
| 1.0.0   | 2025-11-05 | Initial stable release              |

---

## Upgrade Notes

### Upgrading to 1.0.0
This is the initial release - no upgrade process needed.

For future upgrades:
1. Backup your database
2. Replace plugin files
3. Run Moodle upgrade: Site Administration > Notifications
4. Clear caches
5. Test functionality

---

## Support

For issues, questions, or contributions:
- Check INSTALL.md for setup help
- Check QUICKSTART.md for common tasks
- Review SUMMARY.md for complete feature list
- Check transaction logs in database
- Use CLI test script for M-Pesa diagnostics

---

**Maintained by:** RVS Development Team  
**License:** GNU GPL v3 or later  
**Repository:** Local Moodle Plugin
