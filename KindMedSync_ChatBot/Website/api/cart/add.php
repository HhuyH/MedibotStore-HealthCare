<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions/enhanced_logger.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kiểm tra kết nối database
if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    echo json_encode(['success' => false, 'message' => 'Không thể kết nối đến hệ thống']);
    exit;
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy dữ liệu từ request
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

if ($contentType === 'application/json') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ: ' . json_last_error_msg()]);
        exit;
    }
} else {
    $input = $_POST;
}

$product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

try {
    // Log thông tin request
    EnhancedLogger::logCart('ADD_TO_CART', $product_id, null, $quantity);
    
    // Debug: Log product ID being checked
    error_log("Checking product ID: " . $product_id);

    try {
        // Kiểm tra sản phẩm có tồn tại không
        $check_sql = "SELECT product_id, name, price, discount_amount, stock FROM products WHERE product_id = ? AND is_active = 1";
        error_log("SQL Query: " . $check_sql);
        
        $stmt = $conn->prepare($check_sql);
        if (!$stmt) {
            error_log("Prepare Error: " . $conn->error);
            throw new Exception("Lỗi chuẩn bị truy vấn: " . $conn->error);
        }
        
        $bind_result = $stmt->bind_param("i", $product_id);
        if (!$bind_result) {
            error_log("Bind Error: " . $stmt->error);
            throw new Exception("Lỗi bind tham số: " . $stmt->error);
        }
        
        $execute_result = $stmt->execute();
        if (!$execute_result) {
            error_log("Execute Error: " . $stmt->error);
            throw new Exception("Lỗi thực thi truy vấn: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if (!$result) {
            error_log("Get Result Error: " . $stmt->error);
            throw new Exception("Lỗi lấy kết quả: " . $stmt->error);
        }
        
        $product = $result->fetch_assoc();
        error_log("Product data: " . print_r($product, true));
        
    } catch (Exception $e) {
        error_log("Error in product check: " . $e->getMessage());
        throw new Exception("Lỗi kiểm tra sản phẩm: " . $e->getMessage());
    }
    
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
    
    // Tính giá cuối cùng: giá gốc - giảm giá
    $current_price = $product['price'] - ($product['discount_amount'] ?? 0);
    
    if ($existing_item) {
        // Cập nhật số lượng - sử dụng số lượng mới trực tiếp, không cộng dồn
        // Kiểm tra tồn kho cho số lượng mới
        if ($quantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá tồn kho']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE order_items SET quantity = ?, unit_price = ? WHERE item_id = ?");
        $stmt->bind_param("idi", $quantity, $current_price, $existing_item['item_id']);
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
    EnhancedLogger::logError("Error in add to cart: " . $e->getMessage(), 'cart_actions', $e->getTraceAsString());
    
    // Trả về thông báo lỗi phù hợp cho người dùng
    $error_message = 'Có lỗi xảy ra khi thêm sản phẩm vào giỏ hàng';
    if (strpos($e->getMessage(), 'Lỗi hệ thống') === 0) {
        $error_message = $e->getMessage();
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $error_message,
        'error_code' => $e->getCode()
    ]);
}
?> 