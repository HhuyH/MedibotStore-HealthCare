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
$check_query = "SELECT status FROM orders WHERE order_id = ? AND user_id = ? AND status = 'completed'";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param('ii', $order_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Order not found or cannot be reordered']);
    exit;
}

// Lấy cart_id hiện tại hoặc tạo mới
$cart_query = "SELECT order_id FROM orders WHERE user_id = ? AND status = 'cart'";
$cart_stmt = $conn->prepare($cart_query);
$cart_stmt->bind_param('i', $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

if ($cart_result->num_rows > 0) {
    $cart_id = $cart_result->fetch_assoc()['order_id'];
} else {
    // Tạo cart mới
    $create_cart = "INSERT INTO orders (user_id, status, order_date) VALUES (?, 'cart', NOW())";
    $create_stmt = $conn->prepare($create_cart);
    $create_stmt->bind_param('i', $user_id);
    $create_stmt->execute();
    $cart_id = $conn->insert_id;
}

// Lấy các sản phẩm từ đơn hàng cũ
$items_query = "SELECT oi.product_id, oi.quantity, p.name, p.price, p.stock_quantity 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.product_id 
                WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$success = true;
$messages = [];

foreach ($items as $item) {
    // Kiểm tra tồn kho
    if ($item['stock_quantity'] < $item['quantity']) {
        $messages[] = "Sản phẩm '{$item['name']}' chỉ còn {$item['stock_quantity']} trong kho";
        continue;
    }

    // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
    $check_cart = "SELECT quantity FROM order_items WHERE order_id = ? AND product_id = ?";
    $check_cart_stmt = $conn->prepare($check_cart);
    $check_cart_stmt->bind_param('ii', $cart_id, $item['product_id']);
    $check_cart_stmt->execute();
    $existing = $check_cart_stmt->get_result();

    if ($existing->num_rows > 0) {
        // Cập nhật số lượng nếu đã có trong giỏ
        $current_qty = $existing->fetch_assoc()['quantity'];
        $new_qty = min($current_qty + $item['quantity'], $item['stock_quantity']);
        
        $update = "UPDATE order_items SET quantity = ? WHERE order_id = ? AND product_id = ?";
        $update_stmt = $conn->prepare($update);
        $update_stmt->bind_param('iii', $new_qty, $cart_id, $item['product_id']);
        if (!$update_stmt->execute()) {
            $success = false;
            $messages[] = "Không thể cập nhật số lượng cho '{$item['name']}'";
        }
    } else {
        // Thêm mới vào giỏ hàng
        $insert = "INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert);
        $insert_stmt->bind_param('iiid', $cart_id, $item['product_id'], $item['quantity'], $item['price']);
        if (!$insert_stmt->execute()) {
            $success = false;
            $messages[] = "Không thể thêm '{$item['name']}' vào giỏ hàng";
        }
    }
}

// Cập nhật tổng tiền cho giỏ hàng
$update_total = "UPDATE orders o 
                 SET total = (
                     SELECT SUM(oi.quantity * oi.unit_price) 
                     FROM order_items oi 
                     WHERE oi.order_id = o.order_id
                 ) 
                 WHERE order_id = ?";
$total_stmt = $conn->prepare($update_total);
$total_stmt->bind_param('i', $cart_id);
$total_stmt->execute();

if ($success && empty($messages)) {
    echo json_encode([
        'success' => true,
        'message' => 'Đã thêm tất cả sản phẩm vào giỏ hàng',
        'cart_id' => $cart_id
    ]);
} else {
    echo json_encode([
        'success' => $success,
        'message' => 'Đã thêm một số sản phẩm vào giỏ hàng',
        'warnings' => $messages,
        'cart_id' => $cart_id
    ]);
}