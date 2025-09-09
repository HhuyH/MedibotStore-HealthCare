<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'count' => 0,
        'items' => [],
        'total' => 0
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy giỏ hàng hiện tại
$cart_query = "SELECT o.order_id, o.total, oi.product_id 
               FROM orders o 
               LEFT JOIN order_items oi ON o.order_id = oi.order_id 
               WHERE o.user_id = ? AND o.status = 'cart'";

$stmt = $conn->prepare($cart_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    if ($row['product_id']) {
        $items[$row['product_id']] = true; // Chỉ đếm số loại sản phẩm
    }
    $total = $row['total'];
}

echo json_encode([
    'count' => count($items), // Trả về số loại sản phẩm
    'items' => array_keys($items),
    'total' => $total ?? 0
]);