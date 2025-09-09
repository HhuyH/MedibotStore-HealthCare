<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions/format_helpers.php';
require_once 'includes/email_system_simple.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php?message=Vui lòng đăng nhập để tiếp tục thanh toán');
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';
$shipping_fee = 20000; // Phí giao hàng cố định 20,000 VND

// Lấy thông tin giỏ hàng
$stmt = $conn->prepare("
    SELECT 
        o.order_id, o.total,
        oi.product_id, oi.quantity, oi.unit_price,
        p.name, p.image_url as display_image, p.stock
    FROM orders o 
    JOIN order_items oi ON o.order_id = oi.order_id 
    JOIN products p ON oi.product_id = p.product_id 
    WHERE o.user_id = ? AND o.status = 'cart'
");

if (!$stmt) {
    die("Lỗi prepare statement: " . $conn->error);
}

$stmt->bind_param('i', $user_id);
if (!$stmt->execute()) {
    die("Lỗi execute statement: " . $stmt->error);
}

$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}   

// Tính lại tổng giá trị đơn hàng từ các items
$cart_total = 0;
foreach ($cart_items as $item) {
    $cart_total += $item['unit_price'] * $item['quantity'];
}

// Cập nhật tổng tiền trong database
$update_total = $conn->prepare("UPDATE orders SET total = ? WHERE order_id = ? AND user_id = ? AND status = 'cart'");
$update_total->bind_param('dii', $cart_total, $cart_items[0]['order_id'], $user_id);
$update_total->execute();

$final_total = $cart_total + $shipping_fee;

// Xử lý ảnh mặc định cho cart items
foreach ($cart_items as $index => $item) {
    if (empty($item['display_image'])) {
        $cart_items[$index]['display_image'] = '/assets/images/product-placeholder.jpg';
    }
}

// Unset any lingering references
unset($item);

// Lấy thông tin user và địa chỉ đã lưu
$stmt = $conn->prepare("
    SELECT u.username, u.email, ui.phone, ui.full_name, ui.gender, ui.date_of_birth, ui.profile_picture 
    FROM users u 
    LEFT JOIN users_info ui ON u.user_id = ui.user_id 
    WHERE u.user_id = ?
");

if (!$stmt) {
    die("Lỗi prepare user info: " . $conn->error);
}

$stmt->bind_param('i', $user_id);
if (!$stmt->execute()) {
    die("Lỗi execute user info: " . $stmt->error);
}

$user_info = $stmt->get_result()->fetch_assoc();



// Lấy địa chỉ mặc định của user
$stmt = $conn->prepare("
    SELECT ua.address_line, ua.ward, ua.district, ua.city 
    FROM user_addresses ua 
    WHERE ua.user_id = ? AND ua.is_default = 1 
    LIMIT 1
");

if (!$stmt) {
    die("Lỗi prepare address: " . $conn->error);
}

$stmt->bind_param('i', $user_id);
if (!$stmt->execute()) {
    die("Lỗi execute address: " . $stmt->error);
}

$address_result = $stmt->get_result()->fetch_assoc();

// Tạo địa chỉ đầy đủ nếu có
$saved_address = '';
if ($address_result) {
    $saved_address = $address_result['address_line'] . "\n" . 
                    $address_result['ward'] . ", " . 
                    $address_result['district'] . ", " . 
                    $address_result['city'];
}

// Log form submission for debugging if needed
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received. place_order: " . (isset($_POST['place_order']) ? 'YES' : 'NO'));
}

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        $conn->begin_transaction();
        
        $payment_method = $_POST['payment_method'] ?? 'cod';
        $order_note = $_POST['order_note'] ?? '';
        
        // Xử lý địa chỉ giao hàng
        $address_type = $_POST['address_type'] ?? 'new';
        
        if ($address_type === 'saved' && !empty($saved_address)) {
            $shipping_address = ($user_info['full_name'] ?: $user_info['username']) . "\n" . 
                            ($user_info['phone'] ?: '') . "\n" . 
                            $saved_address;
        } else {
            $recipient_name = $_POST['recipient_name'] ?? '';
            $recipient_phone = $_POST['recipient_phone'] ?? '';
            $address_line = $_POST['address_line'] ?? '';
            $ward = $_POST['ward_text'] ?? '';
            $district = $_POST['district_text'] ?? '';
            $city = $_POST['city_text'] ?? '';
            
            $shipping_address = $recipient_name . "\n" . 
                            $recipient_phone . "\n" . 
                            $address_line . "\n" . 
                            $ward . ", " . $district . ", " . $city;
        }
        
        // Lấy order_id của giỏ hàng hiện tại
        $cart_order_id = $cart_items[0]['order_id'];
        
        // Cập nhật đơn hàng từ cart thành pending
        $stmt = $conn->prepare("
            UPDATE orders 
            SET status = 'pending', 
                shipping_address = ?, 
                total = ?, 
                payment_method = ?, 
                payment_status = 'pending',
                order_note = ?,
                order_date = NOW() 
            WHERE order_id = ? AND user_id = ? AND status = 'cart'
        ");
        $stmt->bind_param("sdssii", $shipping_address, $final_total, $payment_method, $order_note, $cart_order_id, $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Không thể cập nhật đơn hàng: " . $stmt->error);
        }
        
        // NOTE: Không trừ tồn kho ở đây, chỉ trừ khi admin xác nhận đơn hàng
        // Tồn kho sẽ được trừ trong admin/orders.php khi status chuyển thành 'completed'
        
        // Chỉ kiểm tra tồn kho để đảm bảo đủ hàng
        foreach ($cart_items as $item) {
            $stmt = $conn->prepare("SELECT stock, name FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $item['product_id']);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            
            if (!$product || $product['stock'] < $item['quantity']) {
                throw new Exception("Sản phẩm '{$product['name']}' không đủ hàng trong kho");
            }
        }
        
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
        $stmt->bind_param('i', $cart_order_id);
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
        
        // Chuyển hướng dựa vào phương thức thanh toán
        if ($payment_method === 'cod') {
            header("Location: order-success.php?order_id=" . $cart_order_id);
        } else {
            header("Location: order-payment.php?order_id=" . $cart_order_id);
        }
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Có lỗi xảy ra khi đặt hàng: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - QickMed</title>
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="assets/css/sepay.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #3b82f6;
            --secondary-color: #6b7280;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
            --background-color: #f3f4f6;
            --card-background: #ffffff;
            --text-primary: #1f2937;
            --text-secondary: #4b5563;
            --text-muted: #9ca3af;
            --border-color: #e5e7eb;
            --radius: 12px;
            --shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: var(--background-color);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding-top: 80px;
            min-height: 100vh;
            line-height: 1.6;
        }

        .checkout-container {
            padding: 5rem 0;
        }

        .checkout-card {
            background: var(--card-background);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }

        .form-label i {
            color: #667eea;
            width: 16px;
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 1rem 1.25rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            box-shadow: var(--shadow-sm);
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1), var(--shadow-md);
            background: white;
            transform: translateY(-1px);
        }

        /* Address Type Selection */
        .address-type-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .address-type-option {
            border: 3px solid var(--border-color);
            border-radius: var(--radius);
            padding: 2rem;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .address-type-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.6s ease;
        }

        .address-type-option:hover::before {
            left: 100%;
        }

        .address-type-option:hover {
            border-color: #667eea;
            transform: translateY(-4px) scale(1.02);
            box-shadow: var(--shadow-lg);
        }

        .address-type-option.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.05) 100%);
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .address-type-option.selected::after {
            content: '✓';
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 32px;
            height: 32px;
            background: var(--success-gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
            box-shadow: var(--shadow-md);
            animation: checkmark 0.5s ease;
        }

        @keyframes checkmark {
            0% { transform: scale(0) rotate(180deg); opacity: 0; }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }

        .address-type-option i {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 1rem;
            display: block;
        }

        .address-type-option h4 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .address-type-option p {
            font-size: 0.95rem;
            color: var(--text-muted);
            margin: 0;
        }

        .saved-address-display {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            white-space: pre-line;
            color: var(--text-secondary);
            font-weight: 500;
            box-shadow: var(--shadow-sm);
        }

        .new-address-form {
            margin-top: 2rem;
        }

        .payment-method {
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }

        .payment-method:hover {
            border-color: var(--primary-color);
        }

        .payment-method.selected {
            border-color: var(--primary-color);
            background: rgba(59, 130, 246, 0.05);
        }

        /* .payment-method.selected::after {
            content: '✓';
            float: right;
            color: var(--primary-color);
            font-weight: bold;
        } */

        .order-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 1px solid var(--border-color);
            background: white;
            border-radius: var(--radius);
            margin-bottom: 0.75rem;
        }

        .item-image {
            width: 50px;
            height: 50px;
            border-radius: var(--radius);
            object-fit: cover;
            margin-right: 1rem;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            font-size: 0.95rem;
        }

        .item-quantity {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .item-price {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .order-summary {
            background: var(--background-color);
            border-radius: var(--radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }

        .summary-row.shipping {
            color: var(--warning-color);
            font-weight: 500;
        }

        .summary-row.total {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            border-top: 1px solid var(--border-color);
            padding-top: 0.75rem;
            margin-top: 0.75rem;
        }

        .btn-place-order {
            background: var(--primary-color);
            border: none;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            transition: all 0.2s ease;
        }

        .btn-place-order:hover {
            opacity: 0.9;
        }

        .btn-place-order:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .select2-container--default .select2-selection--single {
            height: 50px !important;
            border: 2px solid var(--border-color) !important;
            border-radius: 15px !important;
            box-shadow: var(--shadow-sm) !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 46px !important;
            padding-left: 1.25rem !important;
            font-size: 1rem !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #667eea !important;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1), var(--shadow-md) !important;
        }

        .address-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .security-info {
            background: linear-gradient(135deg, rgba(72, 187, 120, 0.1) 0%, rgba(56, 178, 172, 0.1) 100%);
            border: 2px solid rgba(72, 187, 120, 0.2);
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            margin-top: 1.5rem;
        }

        .security-info i {
            color: var(--success-color);
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            body {
                padding-top: 120px;
            }
            
            .page-title {
                font-size: 2.5rem;
            }
            
            .checkout-card {
                padding: 2rem;
            }
            
            .address-grid {
                grid-template-columns: 1fr;
            }

            .address-type-selector {
                grid-template-columns: 1fr;
            }

            .address-type-option {
                padding: 1.5rem;
            }

            .payment-method {
                padding: 1.5rem;
            }
        }

        .loading-spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .hidden {
            display: none !important;
        }

        .fade-in {
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert {
            border-radius: 15px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            border: none;
            box-shadow: var(--shadow-md);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(245, 101, 101, 0.1) 0%, rgba(229, 62, 62, 0.1) 100%);
            border-left: 5px solid var(--error-color);
            color: var(--error-color);
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="checkout-container">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-shopping-cart me-3"></i>
                    Thanh toán
                </h1>
                <p class="page-subtitle">Hoàn tất đơn hàng của bạn</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="checkoutForm">
                <div class="row">
                    <!-- Left Column - Checkout Form -->
                    <div class="col-lg-8">

                        <!-- Shipping Address -->
                        <div class="checkout-card">
                            <h3 class="section-title">
                                <i class="fas fa-map-marker-alt"></i>
                                Thông tin giao hàng
                            </h3>
                            
                            <!-- Address Type Selection -->
                            <div class="address-type-selector">
                                <?php if (!empty($saved_address)): ?>
                                <div class="address-type-option selected" data-type="saved">
                                    <input type="radio" name="address_type" value="saved" checked class="d-none">
                                    <i class="fas fa-bookmark"></i>
                                    <h4>Địa chỉ đã lưu</h4>
                                    <p>Sử dụng địa chỉ trong hồ sơ</p>
                                </div>
                                <?php endif; ?>
                                
                                <div class="address-type-option <?php echo empty($saved_address) ? 'selected' : ''; ?>" data-type="new">
                                    <input type="radio" name="address_type" value="new" <?php echo empty($saved_address) ? 'checked' : ''; ?> class="d-none">
                                    <i class="fas fa-plus-circle"></i>
                                    <h4>Địa chỉ mới</h4>
                                    <p>Nhập địa chỉ giao hàng khác</p>
                                </div>
                            </div>

                            <!-- Saved Address Display -->
                            <?php if (!empty($saved_address)): ?>
                            <div id="savedAddressDisplay" class="saved-address-display">
                                <div class="address-info">
                                    <div class="recipient-info">
                                        <strong><i class="fas fa-user me-2"></i><?php echo htmlspecialchars($user_info['full_name'] ?: $user_info['username']); ?></strong>
                                    </div>
                                    <div class="phone-info">
                                        <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($user_info['phone'] ?? 'Chưa có số điện thoại'); ?>
                                    </div>
                                    <div class="address-details">
                                        <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($saved_address); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- New Address Form -->
                            <div id="newAddressForm" class="new-address-form <?php echo !empty($saved_address) ? 'hidden' : ''; ?>">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-user"></i>
                                                Họ tên người nhận *
                                            </label>
                                            <input type="text" class="form-control" name="recipient_name" 
                                                value="<?php echo htmlspecialchars($user_info['full_name'] ?: $user_info['username']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-phone"></i>
                                                Số điện thoại *
                                            </label>
                                            <input type="tel" class="form-control" name="recipient_phone" 
                                                value="<?php echo htmlspecialchars($user_info['phone'] ?? ''); ?>">

                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-home"></i>
                                        Địa chỉ cụ thể *
                                    </label>
                                    <input type="text" class="form-control" name="address_line" 
                                        placeholder="Số nhà, tên đường, khu vực...">
                                </div>

                                <div class="address-grid">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-map"></i>
                                            Tỉnh/Thành phố *
                                        </label>
                                        <select name="city" id="citySelect" class="form-select select2">
                                            <option value="">-- Chọn Tỉnh/Thành phố --</option>
                                        </select>
                                        <input type="hidden" name="city_text" id="cityText">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-building"></i>
                                            Quận/Huyện *
                                        </label>
                                        <select name="district" id="districtSelect" class="form-select select2" disabled>
                                            <option value="">-- Chọn Quận/Huyện --</option>
                                        </select>
                                        <input type="hidden" name="district_text" id="districtText">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-map-pin"></i>
                                            Phường/Xã *
                                        </label>
                                        <select name="ward" id="wardSelect" class="form-select select2" disabled>
                                            <option value="">-- Chọn Phường/Xã --</option>
                                        </select>
                                        <input type="hidden" name="ward_text" id="wardText">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="checkout-card">
                            <h3 class="section-title">
                                <i class="fas fa-credit-card"></i>
                                Phương thức thanh toán
                            </h3>
                            
                            <div class="payment-method selected" data-method="cod">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="cod" checked class="form-check-input me-3">
                                    <div>
                                        <div class="fw-bold">
                                            <i class="fas fa-money-bill-wave me-2 text-success"></i>
                                            Thanh toán khi nhận hàng (COD)
                                        </div>
                                        <small class="text-muted">Thanh toán bằng tiền mặt khi nhận hàng</small>
                                    </div>
                                </div>
                            </div>

                            <div class="payment-method" data-method="sepay">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="sepay" class="form-check-input me-3">
                                    <div>
                                        <div class="fw-bold">
                                            <i class="fas fa-university me-2 text-primary"></i>
                                            Chuyển khoản ngân hàng
                                        </div>
                                        <small class="text-muted">Thanh toán qua MB Bank (Quét mã QR)</small>
                                    </div>
                                </div>
                                

                            </div>

                            <div class="payment-method" data-method="momo">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="momo" class="form-check-input me-3">
                                    <div>
                                        <div class="fw-bold">
                                            <i class="fas fa-mobile-alt me-2 text-warning"></i>
                                            MoMo
                                        </div>
                                        <small class="text-muted">Thanh toán qua ví điện tử MoMo</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Note -->
                        <div class="checkout-card">
                            <h3 class="section-title">
                                <i class="fas fa-sticky-note"></i>
                                Ghi chú đơn hàng
                            </h3>
                            <textarea name="order_note" class="form-control" rows="3" 
                                    placeholder="Ghi chú cho người bán (tùy chọn)"></textarea>
                        </div>
                    </div>

                    <!-- Right Column - Order Summary -->
                    <div class="col-lg-4">
                        <div class="checkout-card">
                            <h3 class="section-title">
                                <i class="fas fa-receipt"></i>
                                Tóm tắt đơn hàng
                            </h3>

                            <!-- Order Items -->
                            <div class="order-items mb-4">
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="order-item">
                                        <img src="<?php echo htmlspecialchars($item['display_image']); ?>" 
                                            alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                            class="item-image">
                                        <div class="item-info">
                                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <div class="item-quantity">Số lượng: <?php echo $item['quantity']; ?></div>
                                        </div>
                                        <div class="item-price">
                                            <?php echo number_format($item['unit_price'] * $item['quantity'], 0, ',', '.'); ?>đ
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Order Summary -->
                            <div class="order-summary">
                                <div class="summary-row">
                                    <span>Tạm tính:</span>
                                    <span><?php echo number_format($cart_total, 0, ',', '.'); ?>đ</span>
                                </div>
                                <div class="summary-row shipping">
                                    <span>Phí vận chuyển:</span>
                                    <span><?php echo number_format($shipping_fee, 0, ',', '.'); ?>đ</span>
                                </div>
                                <div class="summary-row total">
                                    <span>Tổng cộng:</span>
                                    <span><?php echo number_format($final_total, 0, ',', '.'); ?>đ</span>
                                </div>
                            </div>

                            <!-- Place Order Button -->
                            <!-- <button type="submit" name="place_order" class="btn-place-order" id="placeOrderBtn">
                                <i class="fas fa-lock me-2"></i>Đặt hàng ngay
                            </button> -->
                            
                            <!-- Debug Button -->
                            <button type="submit" name="place_order" value="debug" 
                            class="btn-place-order btn btn-warning mt-2 w-100" 
                            style="font-size: 1.2rem; background: linear-gradient(135deg, #1976d2, #1ec0f7);
                             border: none; color: white; padding: 1.25rem 2rem; border-radius: 20px; 
                             font-weight: 700; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15); 
                             transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: relative;
                             text-align: center;
                             overflow: hidden;" 
                             onmouseover="this.style.transform='translateY(-3px) scale(1.02)'; 
                             this.style.boxShadow='0 25px 50px rgba(102, 126, 234, 0.4)'" onmouseout="this.style.transform='translateY(0) scale(1)'; 
                             this.style.boxShadow='0 20px 40px rgba(0, 0, 0, 0.15)'">
                                <i class="fas fa-shopping-cart me-2"></i>Đặt hàng ngay
                            </button>

                            <!-- Security Info -->
                            <div class="security-info">
                                <i class="fas fa-shield-alt"></i>
                                <strong>Thanh toán an toàn & bảo mật</strong>
                                <div style="font-size: 0.9rem; margin-top: 0.5rem; opacity: 0.8;">
                                    Thông tin của bạn được mã hóa và bảo vệ
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Appointment Modal -->
    <?php include 'includes/appointment-modal.php'; ?>

    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/sepay.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                placeholder: 'Chọn...',
                allowClear: true,
                width: '100%'
            });

            let provincesData = [];

            // Address type selection
            $('.address-type-option').click(function() {
                $('.address-type-option').removeClass('selected');
                $(this).addClass('selected');
                $(this).find('input[type="radio"]').prop('checked', true);
                
                const addressType = $(this).data('type');
                if (addressType === 'saved') {
                    $('#savedAddressDisplay').removeClass('hidden');
                    $('#newAddressForm').addClass('hidden');
                    // Clear required attributes for new address form
                    $('#newAddressForm input[required], #newAddressForm select[required]').removeAttr('required');
                } else {
                    $('#savedAddressDisplay').addClass('hidden');
                    $('#newAddressForm').removeClass('hidden');
                    // Add required attributes back to new address form
                    $('#newAddressForm input[name="recipient_name"], #newAddressForm input[name="recipient_phone"], #newAddressForm input[name="address_line"]').attr('required', true);
                    $('#newAddressForm select[name="city"], #newAddressForm select[name="district"], #newAddressForm select[name="ward"]').attr('required', true);
                }
            });

            // Load provinces from Vietnam API
            function loadProvinces() {
                $('#citySelect').html('<option value="">Đang tải...</option>');
                
                $.ajax({
                    url: 'https://provinces.open-api.vn/api/?depth=3',
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        provincesData = data;
                        populateProvinces(data);
                    },
                    error: function() {
                        $('#citySelect').html('<option value="">Lỗi tải dữ liệu</option>');
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: 'Không thể tải danh sách tỉnh/thành phố'
                        });
                    }
                });
            }

            function populateProvinces(provinces) {
                const $citySelect = $('#citySelect');
                $citySelect.empty().append('<option value="">-- Chọn Tỉnh/Thành phố --</option>');
                
                provinces.forEach(province => {
                    $citySelect.append(`<option value="${province.code}">${province.name}</option>`);
                });
            }

            function populateDistricts(districts) {
                const $districtSelect = $('#districtSelect');
                $districtSelect.empty().append('<option value="">-- Chọn Quận/Huyện --</option>');
                
                districts.forEach(district => {
                    $districtSelect.append(`<option value="${district.code}">${district.name}</option>`);
                });
                
                $districtSelect.prop('disabled', false);
            }

            function populateWards(wards) {
                const $wardSelect = $('#wardSelect');
                $wardSelect.empty().append('<option value="">-- Chọn Phường/Xã --</option>');
                
                wards.forEach(ward => {
                    $wardSelect.append(`<option value="${ward.code}">${ward.name}</option>`);
                });
                
                $wardSelect.prop('disabled', false);
            }

            // Event handlers for address selection
            $('#citySelect').change(function() {
                const provinceCode = $(this).val();
                const provinceName = $(this).find('option:selected').text();
                $('#cityText').val(provinceName);
                
                if (provinceCode) {
                    const province = provincesData.find(p => p.code == provinceCode);
                    if (province) {
                        populateDistricts(province.districts);
                        $('#wardSelect').html('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true);
                        $('#wardText').val('');
                    }
                } else {
                    $('#districtSelect').html('<option value="">-- Chọn Quận/Huyện --</option>').prop('disabled', true);
                    $('#wardSelect').html('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true);
                    $('#districtText').val('');
                    $('#wardText').val('');
                }
            });

            $('#districtSelect').change(function() {
                const districtCode = $(this).val();
                const districtName = $(this).find('option:selected').text();
                $('#districtText').val(districtName);
                
                if (districtCode) {
                    const provinceCode = $('#citySelect').val();
                    const province = provincesData.find(p => p.code == provinceCode);
                    if (province) {
                        const district = province.districts.find(d => d.code == districtCode);
                        if (district) {
                            populateWards(district.wards);
                        }
                    }
                } else {
                    $('#wardSelect').html('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true);
                    $('#wardText').val('');
                }
            });

            $('#wardSelect').change(function() {
                const wardName = $(this).find('option:selected').text();
                $('#wardText').val(wardName);
            });

            // Payment method selection
            $('.payment-method').click(function() {
                $('.payment-method').removeClass('selected');
                $(this).addClass('selected');
                $(this).find('input[type="radio"]').prop('checked', true);
                
                const method = $(this).data('method');
            });

            // Form submission
            $('#checkoutForm').submit(function(e) {
                // Validation for new address
                const addressType = $('input[name="address_type"]:checked').val();
                if (addressType === 'new') {
                    if (!$('#citySelect').val()) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Thiếu thông tin',
                            text: 'Vui lòng chọn Tỉnh/Thành phố'
                        });
                        return false;
                    }
                    
                    if (!$('#districtSelect').val()) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Thiếu thông tin',
                            text: 'Vui lòng chọn Quận/Huyện'
                        });
                        return false;
                    }
                    
                    if (!$('#wardSelect').val()) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Thiếu thông tin',
                            text: 'Vui lòng chọn Phường/Xã'
                        });
                        return false;
                    }
                }
                
                // Show loading
                const $submitBtn = $('#placeOrderBtn');
                $submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...').prop('disabled', true);
                
                // Allow form to submit normally
                return true;
            });

            // Load provinces on page load
            loadProvinces();
        });
    </script>
</body>
</html> 