<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions/format_helpers.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php?message=Vui lòng đăng nhập để xem đơn hàng');
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy danh sách đơn hàng của user
$stmt = $conn->prepare("
    SELECT 
        o.order_id, o.total, o.status, o.payment_method, o.payment_status,
        o.shipping_address, o.order_note, o.order_date, o.updated_at,
        COUNT(oi.item_id) as item_count
    FROM orders o 
    LEFT JOIN order_items oi ON o.order_id = oi.order_id 
    WHERE o.user_id = ? AND o.status != 'cart'
    GROUP BY o.order_id 
    ORDER BY o.order_date DESC
");

$orders = [];
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    error_log("Lỗi prepare statement: " . $conn->error);
}

// Lấy chi tiết sản phẩm cho từng đơn hàng
foreach ($orders as $index => $order) {
    $stmt = $conn->prepare("
        SELECT 
            oi.quantity, oi.unit_price,
            p.name, p.image_url as display_image
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.product_id 
        WHERE oi.order_id = ?
    ");
    
    if ($stmt) {
        $stmt->bind_param('i', $order['order_id']);
        $stmt->execute();
        $orders[$index]['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Xử lý ảnh mặc định
        foreach ($orders[$index]['items'] as $item_index => $item) {
            if (empty($item['display_image'])) {
                $orders[$index]['items'][$item_index]['display_image'] = '/assets/images/default-product.jpg';
            }
        }
    } else {
        $orders[$index]['items'] = [];
        error_log("Lỗi prepare statement cho order_id: " . $order['order_id'] . " - " . $conn->error);
    }
}

// Function để format trạng thái
function getStatusBadge($status) {
    $badges = [
        'pending' => ['class' => 'warning', 'text' => 'Chờ xử lý', 'icon' => 'fas fa-clock'],
        'processing' => ['class' => 'info', 'text' => 'Đang xử lý', 'icon' => 'fas fa-cog'],
        'shipped' => ['class' => 'primary', 'text' => 'Đã gửi hàng', 'icon' => 'fas fa-shipping-fast'],
        'completed' => ['class' => 'success', 'text' => 'Hoàn thành', 'icon' => 'fas fa-check-circle'],
        'cancelled' => ['class' => 'danger', 'text' => 'Đã hủy', 'icon' => 'fas fa-times-circle']
    ];
    
    $badge = $badges[$status] ?? ['class' => 'secondary', 'text' => ucfirst($status), 'icon' => 'fas fa-question'];
    return $badge;
}

function getPaymentBadge($method, $status) {
    $methods = [
        'cod' => 'Thanh toán khi nhận hàng',
        'vnpay' => 'VNPay',
        'momo' => 'MoMo'
    ];
    
    $statuses = [
        'pending' => ['class' => 'warning', 'text' => 'Chờ thanh toán'],
        'paid' => ['class' => 'success', 'text' => 'Đã thanh toán'],
        'failed' => ['class' => 'danger', 'text' => 'Thanh toán thất bại']
    ];
    
    $method_name = $methods[$method] ?? ucfirst($method);
    $status_info = $statuses[$status] ?? ['class' => 'secondary', 'text' => ucfirst($status)];
    
    return [
        'method' => $method_name,
        'status_class' => $status_info['class'],
        'status_text' => $status_info['text']
    ];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng của tôi - QickMed</title>
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
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
            --radius: 20px;
            --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 10px 25px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        body {
            background: var(--primary-gradient);
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

        .orders-container {
            padding: 3rem 0;
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

        .order-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--radius);
            padding: 2.5rem;
            margin-bottom: 2rem;
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
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

        .order-status {
            text-align: right;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .status-badge.badge-warning {
            background: linear-gradient(135deg, #fef3cd 0%, #fff3cd 100%);
            color: #856404;
            border: 2px solid #ffeaa7;
        }

        .status-badge.badge-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            color: #0c5460;
            border: 2px solid #74c0fc;
        }

        .status-badge.badge-primary {
            background: linear-gradient(135deg, #cce7ff 0%, #b3d9ff 100%);
            color: #004085;
            border: 2px solid #667eea;
        }

        .status-badge.badge-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 2px solid #48bb78;
        }

        .status-badge.badge-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f1b0b7 100%);
            color: #721c24;
            border: 2px solid #f56565;
        }

        .payment-info {
            font-size: 0.9rem;
            color: var(--text-secondary);
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

        .order-summary {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 15px;
            padding: 1.5rem;
            border: 2px solid var(--glass-border);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            font-size: 1.05rem;
        }

        .summary-row.total {
            font-size: 1.3rem;
            font-weight: 800;
            color: #667eea;
            border-top: 2px solid var(--border-color);
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .shipping-address {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            margin-top: 1.5rem;
            white-space: pre-line;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .order-note {
            background: linear-gradient(135deg, #fff9e6 0%, #fef3cd 100%);
            border: 2px solid #ffeaa7;
            border-radius: 12px;
            padding: 1.25rem;
            margin-top: 1rem;
            color: #856404;
            font-style: italic;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--radius);
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-lg);
        }

        .empty-state i {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 2rem;
        }

        .empty-state h3 {
            color: var(--text-primary);
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .empty-state p {
            color: var(--text-muted);
            margin-bottom: 2rem;
        }

        .btn-shop {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-lg);
        }

        .btn-shop:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
            color: white;
        }

        @media (max-width: 768px) {
            body {
                padding-top: 120px;
            }

            .page-title {
                font-size: 2.5rem;
            }

            .order-card {
                padding: 2rem;
            }

            .order-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .order-status {
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
        }

        .fade-in {
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="orders-container">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-shopping-bag me-3"></i>
                    Đơn hàng của tôi
                </h1>
                <p class="page-subtitle">Theo dõi và quản lý đơn hàng của bạn</p>
            </div>

            <?php if (empty($orders)): ?>
                <!-- Empty State -->
                <div class="empty-state fade-in">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Chưa có đơn hàng nào</h3>
                    <p>Bạn chưa có đơn hàng nào. Hãy khám phá các sản phẩm tuyệt vời của chúng tôi!</p>
                    <a href="shop.php" class="btn-shop">
                        <i class="fas fa-shopping-bag"></i>
                        Mua sắm ngay
                    </a>
                </div>
            <?php else: ?>
                <!-- Orders List -->
                <?php foreach ($orders as $order): ?>
                    <?php $status_badge = getStatusBadge($order['status']); ?>
                    <?php $payment_info = getPaymentBadge($order['payment_method'], $order['payment_status']); ?>
                    
                    <div class="order-card fade-in">
                        <!-- Order Header -->
                        <div class="order-header">
                            <div class="order-info">
                                <h3>Đơn hàng #<?php echo $order['order_id']; ?></h3>
                                <div class="order-meta">
                                    <div><i class="fas fa-calendar me-2"></i>Ngày đặt: <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></div>
                                    <div><i class="fas fa-box me-2"></i><?php echo $order['item_count']; ?> sản phẩm</div>
                                </div>
                            </div>
                            <div class="order-status">
                                <div class="status-badge badge-<?php echo $status_badge['class']; ?>">
                                    <i class="<?php echo $status_badge['icon']; ?>"></i>
                                    <?php echo $status_badge['text']; ?>
                                </div>
                                <div class="payment-info">
                                    <strong><?php echo $payment_info['method']; ?></strong><br>
                                    <span class="badge bg-<?php echo $payment_info['status_class']; ?>"><?php echo $payment_info['status_text']; ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="order-items">
                            <?php foreach ($order['items'] as $item): ?>
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
                            <div class="summary-row total">
                                <span>Tổng cộng:</span>
                                <span><?php echo number_format($order['total'], 0, ',', '.'); ?>đ</span>
                            </div>
                        </div>

                        <!-- Shipping Address -->
                        <div class="shipping-address">
                            <strong><i class="fas fa-map-marker-alt me-2"></i>Địa chỉ giao hàng:</strong><br>
                            <?php echo htmlspecialchars($order['shipping_address']); ?>
                        </div>

                        <!-- Order Note -->
                        <?php if (!empty($order['order_note'])): ?>
                        <div class="order-note">
                            <strong><i class="fas fa-sticky-note me-2"></i>Ghi chú:</strong><br>
                            <?php echo htmlspecialchars($order['order_note']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
            // Add fade-in animation to cards
            $('.order-card').each(function(index) {
                $(this).css('animation-delay', (index * 0.1) + 's');
            });
        });
    </script>
</body>
</html> 