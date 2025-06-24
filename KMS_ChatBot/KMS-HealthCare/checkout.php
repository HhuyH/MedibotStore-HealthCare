<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions/format_helpers.php';

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
$stmt->bind_param('i', $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}

$cart_total = $cart_items[0]['total'];
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
    SELECT u.username, u.email, u.phone_number, ui.full_name, ui.gender, ui.date_of_birth, ui.profile_picture 
    FROM users u 
    LEFT JOIN users_info ui ON u.user_id = ui.user_id 
    WHERE u.user_id = ?
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();



// Lấy địa chỉ mặc định của user
$stmt = $conn->prepare("
    SELECT ua.address_line, ua.ward, ua.district, ua.city 
    FROM user_addresses ua 
    WHERE ua.user_id = ? AND ua.is_default = 1 
    LIMIT 1
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
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
    // Validate phone number for saved address
    $address_type = $_POST['address_type'] ?? 'new';
    if ($address_type === 'saved' && empty($user_info['phone_number'])) {
        $error_message = "Vui lòng cập nhật số điện thoại trong hồ sơ trước khi đặt hàng.";
    } else {
        try {
            $conn->begin_transaction();
            
            $payment_method = $_POST['payment_method'] ?? 'cod';
            $order_note = $_POST['order_note'] ?? '';
            
            // Xử lý địa chỉ giao hàng
        
        if ($address_type === 'saved' && !empty($saved_address)) {
            $shipping_address = ($user_info['full_name'] ?: $user_info['username']) . "\n" . 
                            ($user_info['phone_number'] ?: '') . "\n" . 
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
        
        // Cập nhật tồn kho
        foreach ($cart_items as $item) {
            $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
            $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmt->execute();
        }
        
        $conn->commit();
        
        // Chuyển hướng đến trang xác nhận
        header("Location: order-success.php?order_id=" . $cart_order_id);
        exit();
        
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Có lỗi xảy ra khi đặt hàng: " . $e->getMessage();
        }
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
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.3);
            --text-primary: #2d3748;
            --text-secondary: #4a5568;
            --text-muted: #718096;
            --border-color: #e2e8f0;
            --success-color: #48bb78;
            --warning-color: #ed8936;
            --error-color: #f56565;
            --radius: 20px;
            --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 10px 25px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: var(--primary-gradient);
            background-attachment: fixed;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding-top: 140px;
            min-height: 100vh;
            position: relative;
            line-height: 1.6;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(102, 126, 234, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(118, 75, 162, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(240, 147, 251, 0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        .checkout-container {
            padding: 5rem 0;
        }

        .checkout-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--radius);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            margin-bottom: 2rem;
            border: 1px solid var(--glass-border);
            transition: all 0.3s ease;
        }

        .checkout-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
        }

        .page-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .page-title {
            font-size: 3.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, #ffffff 0%, #f0f8ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .page-subtitle {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.3rem;
            font-weight: 500;
        }

        .section-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
        }

        .section-title::before {
            content: '';
            width: 5px;
            height: 28px;
            background: var(--primary-gradient);
            border-radius: 3px;
            box-shadow: var(--shadow-sm);
        }

        .section-title i {
            color: #667eea;
            font-size: 1.4rem;
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
            padding: 2rem;
            margin-top: 1.5rem;
            color: var(--text-secondary);
            font-weight: 500;
            box-shadow: var(--shadow-sm);
        }

        .saved-address-display .address-info {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .saved-address-display .recipient-info {
            font-size: 1.1rem;
            color: var(--text-primary);
        }

        .saved-address-display .phone-info {
            font-size: 1rem;
            color: #667eea;
            font-weight: 600;
        }

        .saved-address-display .address-details {
            font-size: 0.95rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .saved-address-display i {
            color: #667eea;
            width: 18px;
        }

        .new-address-form {
            margin-top: 2rem;
        }

        .payment-method {
            border: 3px solid var(--border-color);
            border-radius: var(--radius);
            padding: 2rem;
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            position: relative;
            overflow: hidden;
        }

        .payment-method::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.6s ease;
        }

        .payment-method:hover::before {
            left: 100%;
        }

        .payment-method:hover {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .payment-method.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.05) 100%);
            box-shadow: var(--shadow-lg);
        }

        .payment-method.selected::after {
            content: '✓';
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            width: 28px;
            height: 28px;
            background: var(--success-gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            box-shadow: var(--shadow-md);
            animation: checkmark 0.5s ease;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border: 2px solid var(--border-color);
            background: white;
            border-radius: 15px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .order-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: #667eea;
        }

        .item-image {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            object-fit: cover;
            margin-right: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 1.05rem;
        }

        .item-quantity {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .item-price {
            color: #667eea;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .order-summary {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: var(--radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
            border: 2px solid var(--glass-border);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 1.05rem;
        }

        .summary-row.shipping {
            color: var(--warning-color);
            font-weight: 600;
        }

        .summary-row.total {
            font-size: 1.4rem;
            font-weight: 800;
            color: #667eea;
            border-top: 3px solid var(--border-color);
            padding-top: 1rem;
            margin-top: 1.5rem;
        }

        .btn-place-order {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 1.25rem 2rem;
            border-radius: var(--radius);
            font-weight: 700;
            font-size: 1.2rem;
            width: 100%;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .btn-place-order::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .btn-place-order:hover::before {
            left: 100%;
        }

        .btn-place-order:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .btn-place-order:active {
            transform: translateY(-1px) scale(0.98);
        }

        .btn-place-order:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
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
                                        <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($user_info['phone_number'] ?? 'Chưa có số điện thoại'); ?>
                                    </div>
                                    <div class="address-details">
                                        <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($saved_address); ?>
                                    </div>
                                    
                                    <?php if (empty($user_info['phone_number'])): ?>
                                    <div class="phone-warning">
                                        <div class="alert alert-warning d-flex align-items-center mt-3">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <div>
                                                <strong>Thiếu số điện thoại!</strong><br>
                                                <small>Vui lòng cập nhật số điện thoại trong hồ sơ để có thể đặt hàng.</small>
                                            </div>
                                        </div>
                                        <a href="profile.php" class="btn btn-warning btn-sm mt-2">
                                            <i class="fas fa-edit me-1"></i>Cập nhật hồ sơ
                                        </a>
                                    </div>
                                    <?php endif; ?>
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
                                                value="<?php echo htmlspecialchars($user_info['phone_number'] ?? ''); ?>">

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

                            <div class="payment-method" data-method="vnpay">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="payment_method" value="vnpay" class="form-check-input me-3">
                                    <div>
                                        <div class="fw-bold">
                                            <i class="fab fa-cc-visa me-2 text-primary"></i>
                                            VNPay
                                        </div>
                                        <small class="text-muted">Thanh toán qua thẻ ATM, Visa, MasterCard</small>
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
                            <button type="submit" name="place_order" value="debug" class="btn-place-order btn btn-warning mt-2 w-100" style="font-size: 1.2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; padding: 1.25rem 2rem; border-radius: 20px; font-weight: 700; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden;" onmouseover="this.style.transform='translateY(-3px) scale(1.02)'; this.style.boxShadow='0 25px 50px rgba(102, 126, 234, 0.4)'" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 20px 40px rgba(0, 0, 0, 0.15)'">
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
                
                // Update order button state
                updateOrderButton();
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
            });

            // Phone number validation function
            function validatePhoneNumber() {
                const addressType = $('input[name="address_type"]:checked').val();
                const hasPhoneNumber = <?php echo !empty($user_info['phone_number']) ? 'true' : 'false'; ?>;
                
                if (addressType === 'saved' && !hasPhoneNumber) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Thiếu số điện thoại',
                        text: 'Vui lòng cập nhật số điện thoại trong hồ sơ trước khi đặt hàng',
                        showCancelButton: true,
                        confirmButtonText: 'Cập nhật hồ sơ',
                        cancelButtonText: 'Hủy'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'profile.php';
                        }
                    });
                    return false;
                }
                return true;
            }
            
            // Update place order button state
            function updateOrderButton() {
                const addressType = $('input[name="address_type"]:checked').val();
                const hasPhoneNumber = <?php echo !empty($user_info['phone_number']) ? 'true' : 'false'; ?>;
                const $orderBtn = $('button[name="place_order"]');
                
                if (addressType === 'saved' && !hasPhoneNumber) {
                    $orderBtn.prop('disabled', true)
                            .removeClass('btn-place-order')
                            .addClass('btn-secondary')
                            .html('<i class="fas fa-exclamation-triangle me-2"></i>Cần cập nhật số điện thoại');
                } else {
                    $orderBtn.prop('disabled', false)
                            .removeClass('btn-secondary')
                            .addClass('btn-place-order')
                            .html('<i class="fas fa-shopping-cart me-2"></i>Đặt hàng ngay');
                }
            }

            // Form submission
            $('#checkoutForm').submit(function(e) {
                // Check phone number first
                if (!validatePhoneNumber()) {
                    e.preventDefault();
                    return false;
                }
                
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
            
            // Initialize order button state
            updateOrderButton();
        });
    </script>
</body>
</html> 