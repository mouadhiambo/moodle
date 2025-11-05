#!/usr/bin/env php
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
 * CLI script to test M-Pesa configuration
 *
 * @package    local_rvscertificate
 * @copyright  2025 RVS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/clilib.php');

// Get cli options.
list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'test-auth' => false,
        'test-stk' => false,
        'phone' => ''
    ],
    [
        'h' => 'help'
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "Test M-Pesa configuration for RVS Certificate Issuance plugin.

Options:
--test-auth         Test M-Pesa authentication
--test-stk          Test STK Push (requires --phone)
--phone=VALUE       Phone number for STK Push test (254XXXXXXXXX)
-h, --help          Print out this help

Example:
\$ php test_mpesa.php --test-auth
\$ php test_mpesa.php --test-stk --phone=254712345678
";

    echo $help;
    exit(0);
}

echo "RVS Certificate Issuance - M-Pesa Configuration Test\n";
echo str_repeat("=", 60) . "\n\n";

// Load M-Pesa client
require_once($CFG->dirroot . '/local/rvscertificate/classes/mpesa_client.php');
$mpesa = new \local_rvscertificate\mpesa_client();

// Check configuration
echo "Checking configuration...\n";
$errors = $mpesa->validate_config();

if (!empty($errors)) {
    echo "❌ Configuration errors found:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    exit(1);
}

echo "✅ Configuration is valid\n\n";

// Display current configuration
$environment = get_config('local_rvscertificate', 'mpesa_environment');
$shortcode = get_config('local_rvscertificate', 'mpesa_shortcode');
$price = get_config('local_rvscertificate', 'certificate_price');

echo "Current Settings:\n";
echo "  Environment: $environment\n";
echo "  Shortcode: $shortcode\n";
echo "  Certificate Price: KES $price\n\n";

// Test authentication
if ($options['test-auth']) {
    echo "Testing M-Pesa authentication...\n";
    
    // Use reflection to access private method for testing
    $reflection = new ReflectionClass($mpesa);
    $method = $reflection->getMethod('get_access_token');
    $method->setAccessible(true);
    
    try {
        $token = $method->invoke($mpesa);
        
        if ($token) {
            echo "✅ Authentication successful!\n";
            echo "  Token: " . substr($token, 0, 20) . "...\n";
        } else {
            echo "❌ Authentication failed!\n";
            echo "  Check your Consumer Key and Consumer Secret\n";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Test STK Push
if ($options['test-stk']) {
    if (empty($options['phone'])) {
        echo "❌ Error: --phone parameter is required for STK Push test\n";
        exit(1);
    }
    
    $phone = $mpesa->format_phone_number($options['phone']);
    
    echo "Testing STK Push...\n";
    echo "  Phone: $phone\n";
    echo "  Amount: KES $price\n";
    
    try {
        $response = $mpesa->stk_push(
            $phone,
            $price,
            'TEST-' . time(),
            'RVS Certificate Test'
        );
        
        if ($response) {
            echo "✅ STK Push initiated successfully!\n";
            echo "  Merchant Request ID: " . $response->MerchantRequestID . "\n";
            echo "  Checkout Request ID: " . $response->CheckoutRequestID . "\n";
            echo "  Response Code: " . $response->ResponseCode . "\n";
            echo "  Response Description: " . $response->ResponseDescription . "\n";
            echo "\n";
            echo "Check your phone for the STK Push prompt.\n";
        } else {
            echo "❌ STK Push failed!\n";
            echo "  Check the logs table for details\n";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "Test completed.\n";
echo "\nFor more information, check:\n";
echo "  - Database table: mdl_local_rvscertificate_logs\n";
echo "  - M-Pesa Developer Portal: https://developer.safaricom.co.ke\n";
echo "  - Plugin documentation: INSTALL.md\n";

exit(0);
