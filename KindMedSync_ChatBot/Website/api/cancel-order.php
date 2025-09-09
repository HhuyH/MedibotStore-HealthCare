<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Kiểm tra đơn hàng tồn tại và thuộc về user
$check_query = "SELECT status FROM orders WHERE order_id = ? AND user_id = ? AND status = 'pending'";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param('ii', $order_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Order not found or cannot be cancelled']);
    exit;
}

// Cập nhật trạng thái đơn hàng
$update_query = "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE order_id = ? AND user_id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param('ii', $order_id, $user_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to cancel order']);
}