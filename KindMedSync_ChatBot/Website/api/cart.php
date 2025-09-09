<?php
require_once '../includes/db.php';
require_once '../includes/functions/format_helpers.php';
require_once '../includes/functions/logger.php';
require_once '../includes/functions/product_functions.php';
session_start();

header('Content-Type: application/json');

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
    
    try {
        // Kiểm tra và tạo bảng nếu chưa tồn tại
        ensureCartTables();
    
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
                p.name, p.price, p.stock, p.discount_amount
        FROM products p
        WHERE p.product_id = ? AND p.is_active = TRUE
    ");
        if (!$stmt) {
            logError('SQL Error', "Prepare statement failed: " . $conn->error);
            throw new Exception("Lỗi khi chuẩn bị truy vấn");
        }
        
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
            logError('Insufficient stock', "Required: {$quantity}, Available: {$product['stock']}");
        echo json_encode(['success' => false, 'message' => 'Không đủ hàng trong kho']);
        return;
    }
    
    // Lấy hoặc tạo cart (order với status = 'cart')
    $stmt = $conn->prepare("SELECT order_id FROM orders WHERE user_id = ? AND status = 'cart' LIMIT 1");
        if (!$stmt) {
            logError('SQL Error', "Prepare statement failed: " . $conn->error);
            throw new Exception("Lỗi khi chuẩn bị truy vấn");
        }
        
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $cart = $stmt->get_result()->fetch_assoc();
    
    if (!$cart) {
        // Tạo cart mới
        $stmt = $conn->prepare("INSERT INTO orders (user_id, status) VALUES (?, 'cart')");
            if (!$stmt) {
                logError('SQL Error', "Prepare statement failed: " . $conn->error);
                throw new Exception("Lỗi khi chuẩn bị truy vấn");
            }
            
        $stmt->bind_param('i', $user_id);
            if (!$stmt->execute()) {
                logError('SQL Error', "Insert cart failed: " . $stmt->error);
                throw new Exception("Lỗi khi tạo giỏ hàng mới");
            }
        $cart_id = $conn->insert_id;
            logCartAction('CART_CREATED', $product_id, $quantity, "New Cart ID: {$cart_id}");
    } else {
        $cart_id = $cart['order_id'];
            logCartAction('CART_FOUND', $product_id, $quantity, "Existing Cart ID: {$cart_id}");
    }
    
    // Kiểm tra sản phẩm đã có trong cart chưa
    $stmt = $conn->prepare("SELECT item_id, quantity FROM order_items WHERE order_id = ? AND product_id = ?");
        if (!$stmt) {
            logError('SQL Error', "Prepare statement failed: " . $conn->error);
            throw new Exception("Lỗi khi chuẩn bị truy vấn");
        }
        
    $stmt->bind_param('ii', $cart_id, $product_id);
    $stmt->execute();
    $existing_item = $stmt->get_result()->fetch_assoc();
    
        // Tính giá sau khi giảm
        $discount_info = calculateProductDiscount($product['price'], $product['discount_amount']);
    $unit_price = $discount_info['discount_price'] ?: $product['price'];
        logCartAction('PRICE_CALCULATED', $product_id, $quantity, "Original: {$product['price']}, Discount: {$product['discount_amount']}, Final: {$unit_price}");
    
    if ($existing_item) {
        // Cập nhật số lượng
        $new_quantity = $existing_item['quantity'] + $quantity;
        if ($new_quantity > $product['stock']) {
                logError('Insufficient stock', "Required: {$new_quantity}, Available: {$product['stock']}");
            echo json_encode(['success' => false, 'message' => 'Vượt quá số lượng trong kho']);
            return;
        }
        
        $stmt = $conn->prepare("UPDATE order_items SET quantity = ? WHERE item_id = ?");
            if (!$stmt) {
                logError('SQL Error', "Prepare statement failed: " . $conn->error);
                throw new Exception("Lỗi khi chuẩn bị truy vấn");
            }
            
        $stmt->bind_param('ii', $new_quantity, $existing_item['item_id']);
            if (!$stmt->execute()) {
                logError('SQL Error', "Update quantity failed: " . $stmt->error);
                throw new Exception("Lỗi khi cập nhật số lượng");
            }
            logCartAction('ITEM_UPDATED', $product_id, $new_quantity, "Item ID: {$existing_item['item_id']}");
    } else {
        // Thêm mới
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            if (!$stmt) {
                logError('SQL Error', "Prepare statement failed: " . $conn->error);
                throw new Exception("Lỗi khi chuẩn bị truy vấn");
            }
            
        $stmt->bind_param('iiid', $cart_id, $product_id, $quantity, $unit_price);
            if (!$stmt->execute()) {
                logError('SQL Error', "Insert item failed: " . $stmt->error);
                throw new Exception("Lỗi khi thêm sản phẩm vào giỏ hàng");
            }
            logCartAction('ITEM_ADDED', $product_id, $quantity, "Cart ID: {$cart_id}, Unit Price: {$unit_price}");
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
        
    } catch (Exception $e) {
        logError('Cart Error', $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
        ]);
    }
}

/**
 * Kiểm tra và tạo các bảng cần thiết cho giỏ hàng
 */
function ensureCartTables() {
    global $conn;
    
    // Kiểm tra bảng orders
    $check_orders = $conn->query("SHOW TABLES LIKE 'orders'");
    if ($check_orders->num_rows == 0) {
        $sql = "CREATE TABLE orders (
            order_id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'cart',
            total_amount DECIMAL(16,0) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        )";
        $conn->query($sql);
    } else {
        // Kiểm tra và thêm cột total_amount nếu chưa có
        $check_column = $conn->query("SHOW COLUMNS FROM orders LIKE 'total_amount'");
        if ($check_column->num_rows == 0) {
            $sql = "ALTER TABLE orders ADD COLUMN total_amount DECIMAL(16,0) DEFAULT 0 AFTER status";
            $conn->query($sql);
        }
    }
    
    // Kiểm tra bảng order_items
    $check_items = $conn->query("SHOW TABLES LIKE 'order_items'");
    if ($check_items->num_rows == 0) {
        $sql = "CREATE TABLE order_items (
            item_id INT PRIMARY KEY AUTO_INCREMENT,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            unit_price DECIMAL(16,0) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(order_id),
            FOREIGN KEY (product_id) REFERENCES products(product_id)
        )";
        $conn->query($sql);
    }
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
        // Kiểm tra stock và lấy giá
        $stmt = $conn->prepare("SELECT price, stock, discount_amount FROM products WHERE product_id = ?");
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if ($quantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Vượt quá số lượng trong kho']);
            return;
        }
        
        // Tính giá sau khi giảm
        $discount_info = calculateProductDiscount($product['price'], $product['discount_amount']);
        $unit_price = $discount_info['discount_price'] ?: $product['price'];
        
        // Cập nhật số lượng và đơn giá
        $stmt = $conn->prepare("UPDATE order_items SET quantity = ?, unit_price = ? WHERE order_id = ? AND product_id = ?");
        $stmt->bind_param('idii', $quantity, $unit_price, $cart['order_id'], $product_id);
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
    
    try {
    // Lấy cart items
    $stmt = $conn->prepare("
        SELECT 
                oi.item_id, 
                oi.product_id, 
                oi.quantity, 
                oi.unit_price,
                p.name, 
                p.image_url as display_image, 
                p.stock, 
                p.price, 
                p.discount_amount,
                o.total_amount
        FROM orders o 
        JOIN order_items oi ON o.order_id = oi.order_id 
        JOIN products p ON oi.product_id = p.product_id 
        WHERE o.user_id = ? AND o.status = 'cart'
    ");
        if (!$stmt) {
            throw new Exception("Lỗi khi chuẩn bị truy vấn");
        }
        
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
        $result = $stmt->get_result();
    
        $items = [];
    $total = 0;
        
        while ($row = $result->fetch_assoc()) {
            // Tính giá sau khi giảm
            $discount_info = calculateProductDiscount($row['price'], $row['discount_amount']);
            $row['discount_percent'] = $discount_info['discount_percent'];
            $row['discount_price'] = $discount_info['discount_price'];
        
            // Format image URL
            $row['display_image'] = !empty($row['display_image']) ? $row['display_image'] : '/assets/images/default-product.jpg';
            
            // Tính tổng tiền của item
            $row['subtotal'] = $row['quantity'] * $row['unit_price'];
            $total += $row['subtotal'];
            
            $items[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'total' => $total,
        'count' => count($items)
    ]);
        
    } catch (Exception $e) {
        logError('Get Cart Error', $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi khi lấy thông tin giỏ hàng: ' . $e->getMessage()
        ]);
    }
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
    
    try {
        // Tính tổng tiền từ các items
    $stmt = $conn->prepare("
        SELECT SUM(quantity * unit_price) as total 
        FROM order_items 
        WHERE order_id = ?
    ");
        if (!$stmt) {
            throw new Exception("Lỗi khi chuẩn bị truy vấn tính tổng");
        }
        
    $stmt->bind_param('i', $cart_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total = $result['total'] ?: 0;
    
        // Cập nhật tổng tiền vào orders
        $stmt = $conn->prepare("
            UPDATE orders 
            SET total_amount = ? 
            WHERE order_id = ?
        ");
        if (!$stmt) {
            throw new Exception("Lỗi khi chuẩn bị truy vấn cập nhật tổng");
        }
        
    $stmt->bind_param('di', $total, $cart_id);
        if (!$stmt->execute()) {
            throw new Exception("Lỗi khi cập nhật tổng tiền");
        }
        
        return true;
    } catch (Exception $e) {
        logError('Update Cart Total Error', $e->getMessage());
        return false;
    }
}

function getCartCount($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM orders o 
        JOIN order_items oi ON o.order_id = oi.order_id 
        WHERE o.user_id = ? AND o.status = 'cart'
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc()['count'];
}
?> 