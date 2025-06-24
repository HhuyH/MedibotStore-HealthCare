<?php
session_start();
require_once '../../includes/db.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy dữ liệu từ request
$input = json_decode(file_get_contents('php://input'), true);
$product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

try {
    // Kiểm tra sản phẩm có tồn tại không
    $stmt = $conn->prepare("SELECT product_id, name, price, discount_price, stock FROM products WHERE product_id = ? AND is_active = 1");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        exit;
    }
    
    // Kiểm tra tồn kho
    if ($product['stock'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá tồn kho']);
        exit;
    }
    
    // Tìm hoặc tạo giỏ hàng (order với status = 'cart')
    $stmt = $conn->prepare("SELECT order_id FROM orders WHERE user_id = ? AND status = 'cart' LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $cart = $result->fetch_assoc();
        $order_id = $cart['order_id'];
    } else {
        // Tạo giỏ hàng mới
        $stmt = $conn->prepare("INSERT INTO orders (user_id, status) VALUES (?, 'cart')");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $order_id = $conn->insert_id;
    }
    
    // Kiểm tra sản phẩm đã có trong giỏ hàng chưa
    $stmt = $conn->prepare("SELECT item_id, quantity FROM order_items WHERE order_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $order_id, $product_id);
    $stmt->execute();
    $existing_item = $stmt->get_result()->fetch_assoc();
    
    $current_price = $product['discount_price'] ?: $product['price'];
    
    if ($existing_item) {
        // Cập nhật số lượng
        $new_quantity = $existing_item['quantity'] + $quantity;
        
        // Kiểm tra tồn kho cho số lượng mới
        if ($new_quantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Tổng số lượng vượt quá tồn kho']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE order_items SET quantity = ?, unit_price = ? WHERE item_id = ?");
        $stmt->bind_param("idi", $new_quantity, $current_price, $existing_item['item_id']);
        $stmt->execute();
    } else {
        // Thêm sản phẩm mới
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $current_price);
        $stmt->execute();
    }
    
    // Cập nhật tổng tiền giỏ hàng
    $stmt = $conn->prepare("
        UPDATE orders 
        SET total = (
            SELECT SUM(quantity * unit_price) 
            FROM order_items 
            WHERE order_id = ?
        ) 
        WHERE order_id = ?
    ");
    $stmt->bind_param("ii", $order_id, $order_id);
    $stmt->execute();
    
    // Lấy số lượng sản phẩm trong giỏ hàng
    $stmt = $conn->prepare("SELECT SUM(quantity) as cart_count FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $cart_count = $stmt->get_result()->fetch_assoc()['cart_count'];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Đã thêm sản phẩm vào giỏ hàng',
        'cart_count' => $cart_count
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?> 