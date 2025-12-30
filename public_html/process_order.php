<?php
// process_order.php
// Handles order submission: saves to JSON and emails invoice.

// 0. STRICT OUTPUT BUFFERING
// Catch any stray whitespace, warnings, or errors to ensure clean JSON output
ob_start();

// Enable error logging but DISABLE display
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';
require_once 'env_loader.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Default headers
header('Content-Type: application/json');

// Helper to send JSON response cleanly
function sendResponse($status, $message, $data = []) {
    // Clear any previous output (warnings, HTML errors)
    ob_clean(); 
    
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    
    // Flush buffer and exit
    ob_end_flush();
    exit;
}

try {
    // 1. Data Validation
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $name = $_POST['customer_name'] ?? '';
    $email = $_POST['customer_email'] ?? '';
    $githubUsername = $_POST['github_username'] ?? '';
    $orderJson = $_POST['order_json'] ?? '';
    $pdfFile = $_FILES['invoice_pdf'] ?? null;

    if (empty($name) || empty($email) || empty($orderJson) || !$pdfFile) {
        throw new Exception('Missing required fields.');
    }

    // 2. Save Order to persistent storage
    $ordersFile = __DIR__ . '/../private_data/orders.json';
    $currentOrders = [];

    if (file_exists($ordersFile)) {
        $fileContent = file_get_contents($ordersFile);
        $currentOrders = json_decode($fileContent, true) ?? [];
    }

    $newOrder = [
        'id' => uniqid('ORD-'),
        'timestamp' => date('c'),
        'customer' => [
            'name' => htmlspecialchars($name),
            'email' => htmlspecialchars($email),
            'github' => htmlspecialchars($githubUsername)
        ],
        'details' => json_decode($orderJson, true)
    ];

    $currentOrders[] = $newOrder;

    // Save PDF
    $invoicesDir = __DIR__ . '/../private_data/invoices';
    if (!is_dir($invoicesDir)) {
        if (!mkdir($invoicesDir, 0755, true)) {
            error_log("Failed to create invoices directory: $invoicesDir");
        }
    }
    
    $invoiceFilename = $newOrder['id'] . '.pdf';
    $invoicePath = $invoicesDir . '/' . $invoiceFilename;
    $invoiceSaved = false;

    if (move_uploaded_file($pdfFile['tmp_name'], $invoicePath)) {
        $currentOrders[count($currentOrders) - 1]['invoice_file'] = $invoiceFilename;
        $invoiceSaved = true;
    } else {
        error_log("Failed to save invoice PDF to disk.");
    }

    // Save Order JSON (Safely)
    if (empty($currentOrders) && file_exists($ordersFile) && filesize($ordersFile) > 10) {
         error_log("CRITICAL: Attempted to save empty orders list over existing data. Aborted.");
         throw new Exception("Internal storage error. Please contact support.");
    }
    
    $jsonOutput = json_encode($currentOrders, JSON_PRETTY_PRINT);
    if ($jsonOutput === false) {
        throw new Exception("JSON encoding failed: " . json_last_error_msg());
    }

    if (file_put_contents($ordersFile, $jsonOutput) === false) {
         error_log("Failed to write to orders.json");
    } else {
        // Debug Log
        file_put_contents(__DIR__ . '/../private_data/debug.log', date('c') . " - SAVED: " . $newOrder['id'] . "\n", FILE_APPEND);
    }

    // 3. Send Emails (The risky part)
    $emailWarning = null;
    
    try {
        if (get_config('SMTP_USER') === false) {
            $emailWarning = "SMTP not configured.";
        } else {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = get_config('SMTP_HOST') ?: 'smtp.hostinger.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = get_config('SMTP_USER') ?: 'orders@example.com';
            $mail->Password   = get_config('SMTP_PASS') ?: 'secret';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = get_config('SMTP_PORT') ?: 587;
            $mail->Timeout    = 8;

            $fromEmail = get_config('SMTP_FROM_EMAIL') ?: 'admin@gradientsolutions.ca';
            $fromName = get_config('SMTP_FROM_NAME') ?: 'Gradient Solutions';
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($email, $name);

            if ($invoiceSaved && file_exists($invoicePath)) {
                $mail->addAttachment($invoicePath, 'Gradient_Invoice.pdf');
            } elseif ($pdfFile && isset($pdfFile['tmp_name']) && file_exists($pdfFile['tmp_name'])) {
                 $mail->addAttachment($pdfFile['tmp_name'], 'Gradient_Invoice.pdf');
            }

            $mail->isHTML(true);
            $mail->Subject = 'Your Gradient Solutions Invoice';
            $mail->Body    = "
                <h1>Thank you for your order, $name!</h1>
                <p>We have received your order and it is being processed.</p>
                " . ($githubUsername ? "<p>An admin will invite your GitHub account (<strong>$githubUsername</strong>) to the private repository shortly.</p>" : "") . "
                <p>Please find your invoice attached.</p>
                <br>
                <p>Best regards,<br>The Gradient Solutions Team</p>
            ";

            $mail->send();
            
            // Try Admin Email
            $adminEmail = get_config('ADMIN_EMAIL');
            if ($adminEmail) {
                // ... (Simplified Admin Email Logic for robustness) ...
                $mail->clearAddresses();
                $mail->clearAttachments(); // Clear previous attachments
                $mail->addAddress($adminEmail);
                if ($invoiceSaved && file_exists($invoicePath)) {
                    $mail->addAttachment($invoicePath, 'Gradient_Invoice.pdf');
                }
                $mail->Subject = "New Order: " . $newOrder['id'];
                $mail->Body = "New order received from $name ($email). Total: " . ($newOrder['details']['total'] ?? 'N/A');
                $mail->send();
            }
        }

    } catch (Exception $emailEx) {
        error_log("Email Error: " . $emailEx->getMessage());
        $emailWarning = "Email failed: " . $emailEx->getMessage();
    }

    $finalMsg = "Order processed successfully.";
    if ($emailWarning) {
        $finalMsg .= " (" . $emailWarning . ")";
    }

    sendResponse('success', $finalMsg);

} catch (Exception $e) {
    error_log("Global Process Error: " . $e->getMessage());
    // Even global errors get sent as JSON
    sendResponse('error', "Processing Error: " . $e->getMessage());
}
