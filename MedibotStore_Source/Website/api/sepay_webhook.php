<?php
require_once '../includes/config.php';
require_once '../includes/config/sepay_config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/../logs/sepay';
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}

// Log function
function logWebhook($message, $data = null) {
    global $logDir;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    if ($data) {
        $logMessage .= json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
    file_put_contents($logDir . '/webhook.log', $logMessage, FILE_APPEND);
}

// Get webhook payload
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_SEPAY_SIGNATURE'] ?? '';

// Log received data
logWebhook('Received webhook:', [
    'payload' => $payload,
    'signature' => $signature,
    'headers' => getallheaders()
]);

// For testing, temporarily skip signature verification
// Remove this in production
if (true) {
    // Parse webhook data
    $data = json_decode($payload, true);
    logWebhook('Parsed data:', $data);

    if (!$data) {
        logWebhook('Invalid payload');
        http_response_code(400);
        die('Invalid payload');
    }

    // Extract order ID from transaction description
    $description = $data['description'] ?? '';
    preg_match('/MDBDH(\d+)/', $description, $matches);
    $orderId = $matches[1] ?? null;
    
    // Log extracted data
    logWebhook('Extracting order ID:', [
        'description' => $description,
        'matches' => $matches,
        'orderId' => $orderId
    ]);

    logWebhook('Extracted order ID:', ['orderId' => $orderId, 'description' => $description]);

    if (!$orderId) {
        logWebhook('Invalid order ID');
        http_response_code(400);
        die('Invalid order ID');
    }

    // Update order status
    $status = ($data['status'] === 'success') ? 'completed' : 'pending';
    if (updateOrderPaymentStatus($orderId, $status, $data)) {
        logWebhook('Successfully updated order status', [
            'orderId' => $orderId,
            'status' => $status
        ]);
        
        // Send notification to Discord
        $amount = $data['amount'] ?? 0;
        sendDiscordNotification($orderId, $amount, $status);
        
        http_response_code(200);
        echo json_encode(['status' => 'success']);
    } else {
        logWebhook('Failed to update order status', [
            'orderId' => $orderId,
            'status' => $status
        ]);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update order status']);
    }
} else {
    // Original signature verification code
    if (!verifySepayWebhook($payload, $signature)) {
        logWebhook('Invalid signature', [
            'received_signature' => $signature
        ]);
        http_response_code(401);
        die('Invalid signature');
    }
}
?>