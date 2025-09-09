<?php
session_start();
require_once '../../includes/db.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'items' => [], 'total' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Lấy giỏ hàng hiện tại
    $stmt = $conn->prepare("SELECT order_id, total FROM orders WHERE user_id = ? AND status = 'cart' LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart = $stmt->get_result()->fetch_assoc();
    
    if (!$cart) {
        echo json_encode(['success' => true, 'items' => [], 'total' => 0, 'cart_count' => 0]);
        exit;
    }
    
    // Lấy chi tiết sản phẩm trong giỏ hàng
    $stmt = $conn->prepare("
        SELECT 
            oi.item_id,
            oi.product_id,
            oi.quantity,
            oi.unit_price,
            p.name,
            p.image_url,
            p.price as original_price,
            p.discount_price,
            p.stock,
            c.name as category_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE oi.order_id = ?
        ORDER BY oi.item_id DESC
    ");
    $stmt->bind_param("i", $cart['order_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    $total_quantity = 0;
    
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => $row['product_id'],
            'item_id' => $row['item_id'],
            'name' => $row['name'],
            'image' => $row['image_url'] ?: '/assets/images/default-product.jpg',
            'price' => $row['unit_price'],
            'original_price' => $row['original_price'],
            'discount_price' => $row['discount_price'],
            'quantity' => $row['quantity'],
            'stock' => $row['stock'],
            'category' => $row['category_name'],
            'subtotal' => $row['unit_price'] * $row['quantity']
        ];
        $total_quantity += $row['quantity'];
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'total' => $cart['total'],
        'cart_count' => $total_quantity,
        'order_id' => $cart['order_id']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?> 