<?php
// SePay Configuration
define('SEPAY_API_KEY', 'HTEOCSEHG6PL8CKFMTW4RWVVTYDDKOHXQDJIAEGZBM6C2PSPJIGZAZMBIIE2S50N');
define('SEPAY_MERCHANT_NAME', 'Medibot');
define('SEPAY_BANK_ACCOUNT', '8280111012003');
define('SEPAY_BANK_NAME', 'DANG VUONG THAI DANG');
define('SEPAY_BANK_CODE', 'MBBank');

// Discord Webhook URL for notifications
define('DISCORD_WEBHOOK_URL', 'https://discord.com/api/webhooks/1403617225948270623/L41XvRU2nc-0b5iUr1tu3lALiZTXin9PNFDKmhxbAck-wDTLidXL-OUSe1rK1aMWE_6A');

// Function to generate bank transfer QR code using VietQR format
function generateSepayQR($amount, $orderId, $description = '') {
    // Format: https://api.vietqr.io/image/970422-8280111012003-7JDyWN7.jpg?amount=100000&addInfo=DH123&accountName=DANG%20VUONG%20THAI%20DANG
    $bankId = '970422'; // MB Bank ID
    $accountNo = SEPAY_BANK_ACCOUNT;
    $accountName = urlencode(SEPAY_BANK_NAME);
    $amount = (int)$amount;
    // Không thêm nội dung vào QR code để SePay có thể nhận diện giao dịch
    $description = '';
    
    // Log QR generation
    debug_log('Generating QR for order:', [
        'orderId' => $orderId,
        'amount' => $amount,
        'description' => $description
    ]);
    
    $qrUrl = "https://api.vietqr.io/image/{$bankId}-{$accountNo}-7JDyWN7.jpg?amount={$amount}&addInfo={$description}&accountName={$accountName}";
    
    return [
        'success' => true,
        'qr_url' => $qrUrl
    ];
}

// Function to verify webhook signature
function verifySepayWebhook($payload, $signature) {
    return true; // For testing, we'll accept all webhooks
}

// Function to update order payment status
function updateOrderPaymentStatus($orderId, $status, $transactionData) {
    global $conn;
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update order status
        $stmt = $conn->prepare("UPDATE orders SET payment_status = ?, status = ? WHERE order_id = ?");
        $orderStatus = ($status === 'completed') ? 'processing' : 'pending';
        $stmt->bind_param("ssi", $status, $orderStatus, $orderId);
        $stmt->execute();

        // Insert transaction record
        $stmt = $conn->prepare("INSERT INTO sepay_transactions (order_id, transaction_id, amount, status, webhook_data) VALUES (?, ?, ?, ?, ?)");
        $webhookJson = json_encode($transactionData);
        $stmt->bind_param("isdss", $orderId, $transactionData['transactionId'], $transactionData['amount'], $status, $webhookJson);
        $stmt->execute();

        // Send Discord notification
        sendDiscordNotification($orderId, $transactionData['amount'], $status);

        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Error updating payment status: " . $e->getMessage());
        return false;
    }
}

// Function to send payment notification to Discord
function sendDiscordNotification($orderId, $amount, $status) {
    $webhookUrl = DISCORD_WEBHOOK_URL;
    
    $message = [
        'content' => null,
        'embeds' => [
            [
                'title' => '💰 Thông Báo Thanh Toán Mới',
                'description' => "Mã đơn hàng: #$orderId\nSố tiền: " . number_format($amount, 0, ',', '.') . " VND\nTrạng thái: " . ($status === 'completed' ? '✅ Thành công' : '⏳ Đang xử lý'),
                'color' => ($status === 'completed') ? 0x00ff00 : 0xffa500,
                'timestamp' => date('c')
            ]
        ]
    ];

    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

// Debug function
function debug_log($message, $data = null) {
    $logDir = __DIR__ . '/../../logs/sepay';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    if ($data !== null) {
        $logMessage .= json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
    
    file_put_contents($logDir . '/debug.log', $logMessage, FILE_APPEND);
}
?>