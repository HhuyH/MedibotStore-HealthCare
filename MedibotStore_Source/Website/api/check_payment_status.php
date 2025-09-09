<?php
require_once '../includes/config.php';
require_once '../includes/config/sepay_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$orderId = $_POST['order_id'] ?? '';

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing order ID']);
    exit;
}

// Kiểm tra trạng thái thanh toán trong database
$stmt = $conn->prepare("
    SELECT payment_status 
    FROM orders 
    WHERE order_id = ?
");

$stmt->bind_param('i', $orderId);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if ($order) {
    echo json_encode([
        'status' => $order['payment_status']
    ]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
}
?>

