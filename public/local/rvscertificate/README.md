RVS Certificate Issuance Plugin

This plugin allows learners to purchase and download certificates after completing course requirements.

Features:
- Triggers certificate availability after course completion
- M-Pesa STK Push integration for payment
- Automatic certificate generation and email delivery after payment confirmation
- Admin configurable pricing
- Unique verification codes for each certificate
- Download certificates anytime after purchase

Installation:
1. Place this folder in moodle/local/rvscertificate
2. Visit Site Administration > Notifications to install
3. Configure M-Pesa API credentials in Site Administration > Plugins > Local plugins > RVS Certificate Issuance
4. Set certificate pricing in the same settings page

Requirements:
- Custom Certificate plugin (mod_customcert) must be installed
- M-Pesa Daraja API credentials (Consumer Key, Consumer Secret, Passkey)
