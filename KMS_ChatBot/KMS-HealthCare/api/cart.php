<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions/logger.php';
session_start();

// Kiểm tra user đã đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($method) {
        case 'POST':
            if (isset($_GET['action'])) {
                switch($_GET['action']) {
                    case 'add':
                        addToCart();
                        break;
                    case 'update':
                        updateCart();
                        break;
                    case 'remove':
                        removeFromCart();
                        break;
                    default:
                        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
                }
            } else {
                addToCart();
            }
            break;
        case 'GET':
            getCart();
            break;
        case 'DELETE':
            clearCart();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Method không hợp lệ']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}

function addToCart() {
    global $conn, $user_id;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $product_id = (int)$input['product_id'];
    $quantity = (int)($input['quantity'] ?? 1);
    
    logAPI('/api/cart.php', 'POST', $input, '');
    logCartAction('ADD_TO_CART_START', $product_id, $quantity);
    
    if ($product_id <= 0 || $quantity <= 0) {
        logError('Invalid data', "Product ID: {$product_id}, Quantity: {$quantity}");
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    // Kiểm tra sản phẩm có tồn tại và còn hàng
    $stmt = $conn->prepare("
        SELECT 
            p.name, p.price, p.stock,
            COALESCE(AVG(pr.rating), 0) as avg_rating
        FROM products p
        LEFT JOIN product_reviews pr ON p.product_id = pr.product_id
        WHERE p.product_id = ? AND p.is_active = TRUE
        GROUP BY p.product_id
    ");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if (!$product) {
        logError('Product not found', "Product ID: {$product_id}");
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        return;
    }
    
    logCartAction('PRODUCT_FOUND', $product_id, $quantity, "Name: {$product['name']}, Price: {$product['price']}, Stock: {$product['stock']}");
    
    if ($product['stock'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Không đủ hàng trong kho']);
        return;
    }
    
    // Lấy hoặc tạo cart (order với status = 'cart')
    $stmt = $conn->prepare("SELECT order_id FROM orders WHERE user_id = ? AND status = 'cart' LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $cart = $stmt->get_result()->fetch_assoc();
    
    if (!$cart) {
        // Tạo cart mới
        $stmt = $conn->prepare("INSERT INTO orders (user_id, status) VALUES (?, 'cart')");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $cart_id = $conn->insert_id;
    } else {
        $cart_id = $cart['order_id'];
    }
    
    // Kiểm tra sản phẩm đã có trong cart chưa
    $stmt = $conn->prepare("SELECT item_id, quantity FROM order_items WHERE order_id = ? AND product_id = ?");
    $stmt->bind_param('ii', $cart_id, $product_id);
    $stmt->execute();
    $existing_item = $stmt->get_result()->fetch_assoc();
    
    // Tính giá sau giảm (giảm 10% nếu rating >= 4.5)
    $discount_percent = $product['avg_rating'] >= 4.5 ? 10 : 0;
    $unit_price = $discount_percent > 0 
        ? $product['price'] * (1 - $discount_percent/100) 
        : $product['price'];
    
    if ($existing_item) {
        // Cập nhật số lượng
        $new_quantity = $existing_item['quantity'] + $quantity;
        if ($new_quantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Vượt quá số lượng trong kho']);
            return;
        }
        
        $stmt = $conn->prepare("UPDATE order_items SET quantity = ? WHERE item_id = ?");
        $stmt->bind_param('ii', $new_quantity, $existing_item['item_id']);
        $stmt->execute();
    } else {
        // Thêm mới
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iiid', $cart_id, $product_id, $quantity, $unit_price);
        $stmt->execute();
    }
    
    // Cập nhật tổng tiền cart
    updateCartTotal($cart_id);
    
    // Lấy số lượng item trong cart
    $cart_count = getCartCount($user_id);
    
    $response = [
        'success' => true, 
        'message' => 'Đã thêm sản phẩm vào giỏ hàng',
        'cart_count' => $cart_count
    ];
    
    logCartAction('ADD_TO_CART_SUCCESS', $product_id, $quantity, "Cart ID: {$cart_id}, Cart Count: {$cart_count}");
    logAPI('/api/cart.php', 'POST', $input, $response);
    
    echo json_encode($response);
}

function updateCart() {
    global $conn, $user_id;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $product_id = (int)$input['product_id'];
    $quantity = (int)$input['quantity'];
    
    if ($quantity < 0) {
        echo json_encode(['success' => false, 'message' => 'Số lượng không hợp lệ']);
        return;
    }
    
    // Lấy cart
    $stmt = $conn->prepare("SELECT order_id FROM orders WHERE user_id = ? AND status = 'cart' LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $cart = $stmt->get_result()->fetch_assoc();
    
    if (!$cart) {
        echo json_encode(['success' => false, 'message' => 'Giỏ hàng không tồn tại']);
        return;
    }
    
    if ($quantity == 0) {
        // Xóa sản phẩm
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ? AND product_id = ?");
        $stmt->bind_param('ii', $cart['order_id'], $product_id);
        $stmt->execute();
    } else {
        // Kiểm tra stock
        $stmt = $conn->prepare("SELECT stock FROM products WHERE product_id = ?");
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if ($quantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Vượt quá số lượng trong kho']);
            return;
        }
        
        // Cập nhật số lượng
        $stmt = $conn->prepare("UPDATE order_items SET quantity = ? WHERE order_id = ? AND product_id = ?");
        $stmt->bind_param('iii', $quantity, $cart['order_id'], $product_id);
        $stmt->execute();
    }
    
    // Cập nhật tổng tiền
    updateCartTotal($cart['order_id']);
    
    echo json_encode(['success' => true, 'message' => 'Đã cập nhật giỏ hàng']);
}

function removeFromCart() {
    global $conn, $user_id;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $product_id = (int)$input['product_id'];
    
    // Lấy cart
    $stmt = $conn->prepare("SELECT order_id FROM orders WHERE user_id = ? AND status = 'cart' LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $cart = $stmt->get_result()->fetch_assoc();
    
    if ($cart) {
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ? AND product_id = ?");
        $stmt->bind_param('ii', $cart['order_id'], $product_id);
        $stmt->execute();
        
        updateCartTotal($cart['order_id']);
    }
    
    echo json_encode(['success' => true, 'message' => 'Đã xóa sản phẩm']);
}

function getCart() {
    global $conn, $user_id;
    
    // Lấy cart items
    $stmt = $conn->prepare("
        SELECT 
            oi.item_id, oi.product_id, oi.quantity, oi.unit_price,
            p.name, p.image_url as display_image, p.stock, p.price,
            COALESCE(AVG(pr.rating), 0) as avg_rating
        FROM orders o 
        JOIN order_items oi ON o.order_id = oi.order_id 
        JOIN products p ON oi.product_id = p.product_id 
        LEFT JOIN product_reviews pr ON p.product_id = pr.product_id
        WHERE o.user_id = ? AND o.status = 'cart'
        GROUP BY oi.item_id, oi.product_id, oi.quantity, oi.unit_price, p.name, p.image_url, p.stock, p.price
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Tính tổng và thêm thông tin giảm giá
    $total = 0;
    foreach ($items as &$item) {
        // Tính giá giảm
        $item['discount_percent'] = $item['avg_rating'] >= 4.5 ? 10 : 0;
        $item['discount_price'] = $item['discount_percent'] > 0 
            ? $item['price'] * (1 - $item['discount_percent']/100) 
            : null;
        
        $item['subtotal'] = $item['quantity'] * $item['unit_price'];
        $total += $item['subtotal'];
        
        // Xử lý ảnh mặc định
        if (empty($item['display_image'])) {
            $item['display_image'] = '/assets/images/product-placeholder.jpg';
        }
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'total' => $total,
        'count' => count($items)
    ]);
}

function clearCart() {
    global $conn, $user_id;
    
    $stmt = $conn->prepare("SELECT order_id FROM orders WHERE user_id = ? AND status = 'cart' LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $cart = $stmt->get_result()->fetch_assoc();
    
    if ($cart) {
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->bind_param('i', $cart['order_id']);
        $stmt->execute();
        
        $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
        $stmt->bind_param('i', $cart['order_id']);
        $stmt->execute();
    }
    
    echo json_encode(['success' => true, 'message' => 'Đã xóa giỏ hàng']);
}

function updateCartTotal($cart_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT SUM(quantity * unit_price) as total 
        FROM order_items 
        WHERE order_id = ?
    ");
    $stmt->bind_param('i', $cart_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total = $result['total'] ?: 0;
    
    $stmt = $conn->prepare("UPDATE orders SET total = ? WHERE order_id = ?");
    $stmt->bind_param('di', $total, $cart_id);
    $stmt->execute();
}

function getCartCount($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT SUM(oi.quantity) as count 
        FROM orders o 
        JOIN order_items oi ON o.order_id = oi.order_id 
        WHERE o.user_id = ? AND o.status = 'cart'
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return (int)($result['count'] ?: 0);
}
?> 