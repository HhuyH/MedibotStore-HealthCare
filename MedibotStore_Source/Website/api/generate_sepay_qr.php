<?php
require_once '../includes/config.php';
require_once '../includes/config/sepay_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$amount = $_POST['amount'] ?? 0;
$orderId = $_POST['order_id'] ?? '';

if (!$amount || !$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    // Tạo mã QR từ SePay
    $qrResult = generateSepayQR($amount, $orderId, $orderId);
    
    if (isset($qrResult['qr_url'])) {
        echo json_encode([
            'success' => true,
            'qr_url' => $qrResult['qr_url']
        ]);
    } else {
        throw new Exception('Failed to generate QR code');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
