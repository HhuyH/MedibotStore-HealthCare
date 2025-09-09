<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/email_system_simple.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method không hợp lệ']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

try { 
    $conn->begin_transaction();
    
    // Lấy cart hiện tại
    $stmt = $conn->prepare("SELECT order_id, total FROM orders WHERE user_id = ? AND status = 'cart' LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $cart = $stmt->get_result()->fetch_assoc();
    
    if (!$cart) {
        throw new Exception('Giỏ hàng không tồn tại');
    }
    
    $order_id = $cart['order_id'];
    $address_id = null;
    $shipping_address = '';
    
    // Xử lý địa chỉ
    if ($input['address_type'] === 'saved' && isset($input['address_id'])) {
        // Sử dụng địa chỉ đã lưu
        $address_id = (int)$input['address_id'];
        
        // Lấy thông tin địa chỉ để lưu snapshot
        $stmt = $conn->prepare("
            SELECT ua.*, u.username, ui.phone, ui.full_name 
            FROM user_addresses ua 
            LEFT JOIN users u ON ua.user_id = u.user_id
            LEFT JOIN users_info ui ON u.user_id = ui.user_id
            WHERE ua.id = ? AND ua.user_id = ?
        ");
        $stmt->bind_param('ii', $address_id, $user_id);
        $stmt->execute();
        $address = $stmt->get_result()->fetch_assoc();
        
        if (!$address) {
            throw new Exception('Địa chỉ không tồn tại');
        }
        
        $shipping_address = json_encode([
            'name' => $address['full_name'] ?: $address['username'],
            'phone' => $address['phone'],
            'address_line' => $address['address_line'],
            'ward' => $address['ward'],
            'district' => $address['district'],
            'city' => $address['city'],
            'postal_code' => $address['postal_code'],
            'country' => $address['country']
        ]);
        
    } else {
        // Sử dụng địa chỉ mới
        $recipient_name = trim($input['recipient_name'] ?? '');
        $recipient_phone = trim($input['recipient_phone'] ?? '');
        $address_line = trim($input['address_line'] ?? '');
        $ward = trim($input['ward'] ?? '');
        $district = trim($input['district'] ?? '');
        $city = trim($input['city'] ?? '');
        
        if (empty($recipient_name) || empty($recipient_phone) || empty($address_line) || 
            empty($ward) || empty($district) || empty($city)) {
            throw new Exception('Vui lòng điền đầy đủ thông tin địa chỉ');
        }
        
        $shipping_address = json_encode([
            'name' => $recipient_name,
            'phone' => $recipient_phone,
            'address_line' => $address_line,
            'ward' => $ward,
            'district' => $district,
            'city' => $city,
            'postal_code' => '',
            'country' => 'Vietnam'
        ]);
        
        // Lưu địa chỉ mới nếu user chọn
        if (!empty($input['save_address'])) {
            $stmt = $conn->prepare("
                INSERT INTO user_addresses (user_id, address_line, ward, district, city, country, is_default) 
                VALUES (?, ?, ?, ?, ?, 'Vietnam', FALSE)
            ");
            $stmt->bind_param('issss', $user_id, $address_line, $ward, $district, $city);
            $stmt->execute();
            $address_id = $conn->insert_id;
        }
    }
    
    // Lấy payment method
    $payment_method = $input['payment_method'] ?? 'cod';
    $order_note = trim($input['order_note'] ?? '');
    
    // Cập nhật đơn hàng
    $stmt = $conn->prepare("
        UPDATE orders 
        SET address_id = ?, 
            shipping_address = ?, 
            payment_method = ?, 
            payment_status = 'pending', 
            status = 'pending',
            order_note = ?,
            order_date = NOW()
        WHERE order_id = ? AND user_id = ?
    ");
    $stmt->bind_param('isssii', $address_id, $shipping_address, $payment_method, $order_note, $order_id, $user_id);
    $stmt->execute();
    
    // Kiểm tra tồn kho các sản phẩm (nhưng không trừ, chỉ trừ khi admin xác nhận)
    $stmt = $conn->prepare("
        SELECT oi.product_id, oi.quantity, p.stock, p.name as product_name
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.product_id 
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($items as $item) {
        if ($item['stock'] < $item['quantity']) {
            throw new Exception("Sản phẩm '{$item['product_name']}' không đủ hàng trong kho (còn {$item['stock']}, cần {$item['quantity']})");
        }
        
        // NOTE: Không trừ stock ở đây, chỉ trừ khi admin xác nhận đơn hàng
        // Stock sẽ được trừ trong admin/orders.php khi status chuyển thành 'completed'
    }
    
    // Tạo payment record
    $stmt = $conn->prepare("
        INSERT INTO payments (user_id, order_id, payment_method, payment_status, amount) 
        VALUES (?, ?, ?, 'pending', ?)
    ");
    $stmt->bind_param('iisd', $user_id, $order_id, $payment_method, $cart['total']);
    $stmt->execute();
    
    $conn->commit();
    
    // Lấy thông tin đơn hàng và user để gửi email
    $stmt = $conn->prepare("
        SELECT o.order_id, o.total, o.payment_method, o.shipping_address, 
               u.email, ui.full_name
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        LEFT JOIN users_info ui ON u.user_id = ui.user_id
        WHERE o.order_id = ?
    ");
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $order_info = $stmt->get_result()->fetch_assoc();
    
    // Gửi email xác nhận đơn hàng
    if ($order_info && $order_info['email']) {
        try {
            sendOrderEmail($order_info['email'], $order_info['full_name'], $order_info);
            error_log("Order email sent to: " . $order_info['email']);
        } catch (Exception $e) {
            error_log("Failed to send order email: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Đặt hàng thành công',
        'order_id' => $order_id
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 