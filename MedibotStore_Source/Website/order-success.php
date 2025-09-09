<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions/format_helpers.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    header('Location: orders.php');
    exit();
}

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("
    SELECT 
        o.order_id, o.total, o.status, o.payment_method, o.payment_status,
        o.shipping_address, o.order_note, o.order_date,
        COUNT(oi.item_id) as item_count
    FROM orders o 
    LEFT JOIN order_items oi ON o.order_id = oi.order_id 
    WHERE o.order_id = ? AND o.user_id = ?
    GROUP BY o.order_id
");
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Lấy chi tiết sản phẩm trong đơn hàng
$stmt = $conn->prepare("
    SELECT 
        oi.quantity, oi.unit_price,
        p.name, p.image_url as display_image
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.product_id 
    WHERE oi.order_id = ?
");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Xử lý ảnh mặc định
foreach ($order_items as $index => $item) {
    if (empty($item['display_image'])) {
        $order_items[$index]['display_image'] = '/assets/images/default-product.jpg';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công - QickMed</title>
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.3);
            --text-primary: #2d3748;
            --text-secondary: #4a5568;
            --text-muted: #718096;
            --border-color: #e2e8f0;
            --success-color: #48bb78;
            --radius: 20px;
            --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 10px 25px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        body {
            /* background: var(--primary-gradient);
            background: linear-gradient(135deg,rgba(121, 188, 255, 0.73),rgb(93, 208, 246)) !important; */
            background-attachment: fixed;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding-top: 140px;
            min-height: 100vh;
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

        .success-container {
            padding: 3rem 0;
        }

        .success-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--radius);
            padding: 3rem;
            margin-bottom: 2rem;
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-lg);
            text-align: center;
            animation: slideInUp 0.8s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-icon {
            width: 120px;
            height: 120px;
            background: var(--success-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            animation: bounceIn 1s ease-out 0.3s both;
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .success-icon i {
            font-size: 3rem;
            color: white;
        }

        .success-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 1rem;
            animation: fadeInUp 0.8s ease-out 0.6s both;
        }

        .success-subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            animation: fadeInUp 0.8s ease-out 0.8s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .order-summary-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--radius);
            padding: 2.5rem;
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-lg);
            animation: fadeInUp 0.8s ease-out 1s both;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--border-color);
        }

        .order-info h3 {
            color: var(--text-primary);
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 1.4rem;
        }

        .order-meta {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            /* background: linear-gradient(135deg,rgb(17, 223, 41) 0%,rgb(16, 235, 78) 100%); */
            background: linear-gradient(135deg,rgb(144, 194, 243),rgb(197, 213, 218)) !important;
            color: black;
            border: 2px solid #667eea;
        }

        .order-items {
            margin-bottom: 2rem;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 15px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
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

        .order-total {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 2px solid var(--glass-border);
            text-align: center;
        }

        .total-amount {
            font-size: 2rem;
            font-weight: 800;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .total-label {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .shipping-address {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            white-space: pre-line;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 0.8s ease-out 1.2s both;
        }

        .btn-action {
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
        }

        .btn-primary {
            background: var(--primary-gradient);
            background: linear-gradient(135deg, #1976d2, #1ec0f7) !important;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-outline {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-outline:hover {
            background: #667eea;
            background: linear-gradient(135deg,rgb(71, 161, 250),rgb(77, 200, 238)) !important;
            color: white;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            body {
                padding-top: 120px;
            }

            .success-card {
                padding: 2rem;
            }

            .success-title {
                font-size: 2rem;
            }

            .order-summary-card {
                padding: 2rem;
            }

            .order-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .order-item {
                padding: 1rem;
            }

            .item-image {
                width: 60px;
                height: 60px;
                margin-right: 1rem;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn-action {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="success-container">
        <div class="container">
            <!-- Success Message -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="success-card">
                        <div class="success-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <h1 class="success-title">Đặt hàng thành công!</h1>
                        <p class="success-subtitle">
                            Cảm ơn bạn đã tin tưởng MediBotStore. Đơn hàng #<?php echo $order['order_id']; ?> của bạn đã được xác nhận và đang được xử lý.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Order Details -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="order-summary-card">
                        <!-- Order Header -->
                        <div class="order-header">
                            <div class="order-info">
                                <h3>Đơn hàng #<?php echo $order['order_id']; ?></h3>
                                <div class="order-meta">
                                    <div><i class="fas fa-calendar me-2"></i>Ngày đặt: <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></div>
                                    <div><i class="fas fa-box me-2"></i><?php echo $order['item_count']; ?> sản phẩm</div>
                                </div>
                            </div>
                            <div class="status-badge">
                                <i class="fas fa-shipping-fast"></i>
                                Đang xử lý
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="order-items">
                            <?php foreach ($order_items as $item): ?>
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

                        <!-- Order Total -->
                        <div class="order-total">
                            <div class="total-amount"><?php echo number_format($order['total'], 0, ',', '.'); ?>đ</div>
                            <div class="total-label">Tổng cộng (đã bao gồm phí vận chuyển)</div>
                        </div>

                        <!-- Shipping Address -->
                        <div class="shipping-address">
                            <strong><i class="fas fa-map-marker-alt me-2"></i>Địa chỉ giao hàng:</strong><br>
                            <?php echo htmlspecialchars($order['shipping_address']); ?>
                        </div>

                        <!-- Payment Info -->
                        <div class="shipping-address">
                            <strong><i class="fas fa-credit-card me-2"></i>Phương thức thanh toán:</strong><br>
                            <?php 
                            $payment_methods = [
                                'cod' => 'Thanh toán khi nhận hàng (COD)',
                                'vnpay' => 'VNPay',
                                'momo' => 'MoMo'
                            ];
                            echo $payment_methods[$order['payment_method']] ?? ucfirst($order['payment_method']);
                            ?>
                        </div>

                        <!-- Order Note -->
                        <?php if (!empty($order['order_note'])): ?>
                        <div class="shipping-address">
                            <strong><i class="fas fa-sticky-note me-2"></i>Ghi chú:</strong><br>
                            <?php echo htmlspecialchars($order['order_note']); ?>
                        </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <a href="orders.php" class="btn-action btn-primary">
                                <i class="fas fa-list"></i>
                                Xem tất cả đơn hàng
                            </a>
                            <a href="shop.php" class="btn-action btn-outline">
                                <i class="fas fa-shopping-bag"></i>
                                Tiếp tục mua sắm
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointment Modal -->
    <?php include 'includes/appointment-modal.php'; ?>

    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Add celebration effect
            setTimeout(function() {
                // You can add confetti or other celebration effects here
                console.log('Order placed successfully!');
            }, 1000);
        });
    </script>
</body>
</html> 