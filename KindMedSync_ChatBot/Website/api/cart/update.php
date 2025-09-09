<?php
session_start();
require_once '../../includes/db.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy dữ liệu từ request
$input = json_decode(file_get_contents('php://input'), true);
$product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;

if ($product_id <= 0 || $quantity < 0) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

try {
    // Lấy giỏ hàng hiện tại
    $stmt = $conn->prepare("SELECT order_id FROM orders WHERE user_id = ? AND status = 'cart' LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart = $stmt->get_result()->fetch_assoc();
    
    if (!$cart) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy giỏ hàng']);
        exit;
    }
    
    if ($quantity == 0) {
        // Xóa sản phẩm khỏi giỏ hàng
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $cart['order_id'], $product_id);
        $stmt->execute();
    } else {
        // Kiểm tra tồn kho
        $stmt = $conn->prepare("SELECT stock FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if (!$product || $product['stock'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá tồn kho']);
            exit;
        }
        
        // Cập nhật số lượng
        $stmt = $conn->prepare("UPDATE order_items SET quantity = ? WHERE order_id = ? AND product_id = ?");
        $stmt->bind_param("iii", $quantity, $cart['order_id'], $product_id);
        $stmt->execute();
    }
    
    // Cập nhật tổng tiền giỏ hàng
    $stmt = $conn->prepare("
        UPDATE orders 
        SET total = (
            SELECT COALESCE(SUM(quantity * unit_price), 0) 
            FROM order_items 
            WHERE order_id = ?
        ) 
        WHERE order_id = ?
    ");
    $stmt->bind_param("ii", $cart['order_id'], $cart['order_id']);
    $stmt->execute();
    
    // Lấy số lượng sản phẩm trong giỏ hàng
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as cart_count FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $cart['order_id']);
    $stmt->execute();
    $cart_count = $stmt->get_result()->fetch_assoc()['cart_count'];
    
    // Lấy tổng tiền mới
    $stmt = $conn->prepare("SELECT total FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $cart['order_id']);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật giỏ hàng',
        'cart_count' => $cart_count,
        'total' => $total
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?> 