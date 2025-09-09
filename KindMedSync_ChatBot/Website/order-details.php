<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions/format_helpers.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?message=Vui lòng đăng nhập để xem chi tiết đơn hàng');
    exit();
}

// Kiểm tra order_id
if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['id'];

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("
    SELECT 
        o.order_id, o.total, o.status, o.payment_method, o.payment_status,
        o.shipping_address, o.order_note, o.order_date, o.updated_at,
        COUNT(oi.item_id) as item_count
    FROM orders o 
    LEFT JOIN order_items oi ON o.order_id = oi.order_id 
    WHERE o.user_id = ? AND o.order_id = ? AND o.status != 'cart'
    GROUP BY o.order_id
");

if ($stmt) {
    $stmt->bind_param('ii', $user_id, $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        header('Location: orders.php');
        exit();
    }
    
    // Lấy chi tiết sản phẩm
    $stmt = $conn->prepare("
        SELECT 
            oi.quantity, oi.unit_price, oi.product_id,
            p.name, p.image_url as display_image
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.product_id 
        WHERE oi.order_id = ?
    ");
    
    if ($stmt) {
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $order['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($order['items'] as &$item) {
            if (empty($item['display_image'])) {
                $item['display_image'] = 'assets/images/default-product.jpg';
            }
        }
    }
} else {
    header('Location: orders.php');
    exit();
}

$status_info = getStatusInfo($order['status']);
$payment_info = getPaymentInfo($order['payment_method'], $order['payment_status']);
$timeline_progress = getTimelineProgress($order['status']);

function getStatusInfo($status) {
    $statuses = [
        'pending' => [
            'text' => 'Chờ xử lý',
            'icon' => 'clock',
            'class' => 'warning'
        ],
        'processing' => [
            'text' => 'Đang xử lý',
            'icon' => 'cog',
            'class' => 'primary'
        ],
        'shipped' => [
            'text' => 'Đang giao hàng',
            'icon' => 'shipping-fast',
            'class' => 'info'
        ],
        'completed' => [
            'text' => 'Hoàn thành',
            'icon' => 'check-circle',
            'class' => 'success'
        ],
        'cancelled' => [
            'text' => 'Đã hủy',
            'icon' => 'times-circle',
            'class' => 'danger'
        ]
    ];
    
    return $statuses[$status] ?? [
        'text' => ucfirst($status),
        'icon' => 'question',
        'class' => 'secondary'
    ];
}

function getPaymentInfo($method, $status) {
    global $order;
    
    $methods = [
        'cod' => 'Thanh toán khi nhận hàng',
        'vnpay' => 'VNPay',
        'momo' => 'MoMo'
    ];
    
    // Nếu đơn hàng đã hoàn thành, tự động set trạng thái thanh toán thành công
    if ($order['status'] === 'completed') {
        $status = 'paid';
    }
    
    $statuses = [
        'pending' => [
            'text' => 'Chờ thanh toán',
            'class' => 'warning'
        ],
        'paid' => [
            'text' => 'Đã thanh toán',
            'class' => 'success'
        ],
        'failed' => [
            'text' => 'Thanh toán thất bại',
            'class' => 'danger'
        ]
    ];
    
    return [
        'method' => $methods[$method] ?? ucfirst($method),
        'status' => $statuses[$status] ?? [
            'text' => ucfirst($status),
            'class' => 'secondary'
        ]
    ];
}

function getTimelineProgress($status) {
    $steps = ['pending', 'processing', 'shipped', 'completed'];
    $current_index = array_search($status, $steps);
    
    if ($current_index === false || $status === 'cancelled') {
        return 0;
    }
    
    return (($current_index + 1) / count($steps)) * 100;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?php echo $order['order_id']; ?> - MediBot Store</title>
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: #f8fafc;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .empty-state i {
            font-size: 4rem;
            color: #6b7280;
            margin-bottom: 1.5rem;
        }

        .empty-state h2 {
            color: #1f2937;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: #6b7280;
            margin-bottom: 2rem;
        }
        
        .order-header {
            background: #fff;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .order-status {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-warning { background: #fff7ed; color: #c2410c; }
        .status-primary { background: #eff6ff; color: #1d4ed8; }
        .status-info { background: #f0f9ff; color: #0369a1; }
        .status-success { background: #f0fdf4; color: #15803d; }
        .status-danger { background: #fef2f2; color: #b91c1c; }
        
        .order-timeline {
            background: #fff;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .timeline-line {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #e5e7eb;
            transform: translateY(-50%);
        }
        
        .timeline-progress {
            position: absolute;
            top: 50%;
            left: 0;
            height: 2px;
            background: #3b82f6;
            transform: translateY(-50%);
            transition: width 0.3s ease;
        }
        
        .timeline-steps {
            position: relative;
            display: flex;
            justify-content: space-between;
            z-index: 1;
        }
        
        .timeline-step {
            text-align: center;
            min-width: 120px;
        }
        
        .timeline-icon {
            width: 40px;
            height: 40px;
            background: #fff;
            border: 2px solid #e5e7eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            color: #6b7280;
            transition: all 0.3s ease;
        }
        
        .timeline-step.done .timeline-icon {
            background: #3b82f6;
            border-color: #3b82f6;
            color: #fff;
        }
        
        .timeline-step.current .timeline-icon {
            border-color: #3b82f6;
            color:rgb(255, 255, 255);
            transform: scale(1.2);
        }
        
        .timeline-label {
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        .timeline-step.done .timeline-label,
        .timeline-step.current .timeline-label {
            color: #1f2937;
            font-weight: 600;
        }
        
        .order-details {
            background: #fff;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .order-section {
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .order-section:last-child {
            padding-bottom: 0;
            margin-bottom: 0;
            border-bottom: none;
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .info-item {
            padding: 1rem;
            background: #f9fafb;
            border-radius: 10px;
        }
        
        .info-label {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-weight: 500;
            color: #1f2937;
        }
        
        .product-list {
            display: grid;
            gap: 1rem;
        }
        
        .product-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 10px;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-name {
            font-weight: 500;
            color: #1f2937;
            text-decoration: none;
            margin-bottom: 0.25rem;
            display: block;
        }
        
        .product-name:hover {
            color: #3b82f6;
        }
        
        .product-meta {
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .product-price {
            font-weight: 600;
            color: #3b82f6;
            text-align: right;
            white-space: nowrap;
        }
        
        .order-summary {
            background: #f9fafb;
            border-radius: 10px;
            padding: 1.5rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }
        
        .summary-row:last-child {
            margin-bottom: 0;
            padding-top: 0.75rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .summary-label {
            color: #6b7280;
        }
        
        .summary-value {
            font-weight: 500;
            color: #1f2937;
        }
        
        .summary-row:last-child .summary-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: #3b82f6;
        }
        
        .order-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn-action {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Ẩn các phần tử trên mobile */
        @media (max-width: 576px) {
            .timeline-steps {
                display: none;
            }
            
            .order-timeline {
                display: none;
            }

            .order-status {
                font-size: 0.75rem !important;
                padding: 0.25rem 0.5rem !important;
            }

            .section-title i,
            .info-label,
            .product-meta,
            .summary-label {
                display: none;
            }

            .product-info {
                width: calc(100% - 50px);
            }

            .product-image {
                width: 40px;
                height: 40px;
            }

            .product-name {
                font-size: 0.85rem;
                -webkit-line-clamp: 1;
                display: -webkit-box;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .info-value {
                font-size: 0.85rem;
            }

            .summary-row {
                justify-content: flex-end;
            }

            .summary-value {
                font-size: 0.85rem;
                font-weight: 600;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }

            .order-header,
            .order-timeline,
            .order-details {
                padding: 1rem;
                margin-bottom: 1rem;
                border-radius: 10px;
            }
            
            .timeline-steps {
                overflow-x: auto;
                padding-bottom: 10px;
                -webkit-overflow-scrolling: touch;
            }
            
            .timeline-step {
                min-width: 80px;
                flex-shrink: 0;
            }
            
            .timeline-icon {
                width: 28px;
                height: 28px;
                font-size: 0.8rem;
            }
            
            .timeline-label {
                font-size: 0.75rem;
            }

            /* Điều chỉnh layout sản phẩm trên mobile */
            .product-item {
                flex-wrap: wrap;
                gap: 0.5rem;
                padding: 0.75rem;
            }
            
            .product-image {
                width: 50px;
                height: 50px;
            }
            
            .product-info {
                width: calc(100% - 60px); /* Trừ đi width của ảnh */
            }
            
            .product-name {
                font-size: 0.9rem;
                margin-bottom: 0.2rem;
            }

            .product-meta {
                font-size: 0.8rem;
            }
            
            .product-price {
                width: 100%;
                text-align: left;
                margin-top: 0.5rem;
                padding-top: 0.5rem;
                border-top: 1px solid #e5e7eb;
            }

            /* Điều chỉnh thông tin đơn hàng */
            .info-grid {
                grid-template-columns: 1fr;
            }

            .info-item {
                padding: 0.75rem;
            }

            /* Điều chỉnh timeline */
            .order-timeline {
                margin: 1rem 0;
                padding: 0.75rem;
            }

            /* Điều chỉnh các nút hành động */
            .order-actions {
                flex-direction: column;
                gap: 0.5rem;
                margin-top: 1rem;
            }
            
            .btn-action {
                width: 100%;
                justify-content: center;
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }

            /* Điều chỉnh header đơn hàng */
            .order-header h1 {
                font-size: 1.2rem;
            }

            .order-status {
                font-size: 0.8rem;
                padding: 0.35rem 0.75rem;
            }

            /* Điều chỉnh section titles */
            .section-title {
                font-size: 1rem;
                margin-bottom: 0.75rem;
            }

            /* Điều chỉnh summary */
            .order-summary {
                padding: 1rem;
            }

            .summary-row {
                margin-bottom: 0.5rem;
                font-size: 0.9rem;
            }

            .summary-row:last-child {
                margin-top: 0.5rem;
            }

            .summary-row:last-child .summary-value {
                font-size: 1.1rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <!-- Back button -->
        <a href="orders.php" class="btn btn-link text-dark mb-4">
            <i class="fas fa-arrow-left me-2"></i>
            Quay lại danh sách đơn hàng
        </a>

        <?php if (!$order || empty($order['items'])): ?>
        <!-- Empty State -->
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="fas fa-box-open fa-4x text-muted"></i>
            </div>
            <h2 class="h4 mb-3">Không tìm thấy thông tin đơn hàng</h2>
            <p class="text-muted mb-4">Đơn hàng không tồn tại hoặc đã bị xóa</p>
            <a href="orders.php" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i>
                Xem danh sách đơn hàng
            </a>
        </div>
        <?php else: ?>
        
        <!-- Order Header -->
        <div class="order-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-2">Đơn hàng #<?php echo $order['order_id']; ?></h1>
                    <div class="text-muted">
                        <i class="fas fa-calendar me-2"></i>
                        Đặt ngày <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?>
                    </div>
                </div>
                <div class="order-status status-<?php echo $status_info['class']; ?>">
                    <i class="fas fa-<?php echo $status_info['icon']; ?> me-2"></i>
                    <?php echo $status_info['text']; ?>
                </div>
            </div>
            
            <?php if ($order['status'] !== 'cancelled'): ?>
            <div class="order-timeline">
                <div class="timeline-line"></div>
                <div class="timeline-progress" style="width: <?php echo $timeline_progress; ?>%"></div>
                <div class="timeline-steps">
                    <?php
                    $steps = [
                        'pending' => ['text' => 'Đặt hàng', 'icon' => 'shopping-cart'],
                        'processing' => ['text' => 'Xử lý', 'icon' => 'cog'],
                        'shipped' => ['text' => 'Vận chuyển', 'icon' => 'truck'],
                        'completed' => ['text' => 'Hoàn thành', 'icon' => 'check-circle']
                    ];
                    
                    foreach ($steps as $step => $label):
                        $is_done = array_search($step, array_keys($steps)) <= array_search($order['status'], array_keys($steps));
                        $is_current = $step === $order['status'];
                    ?>
                        <div class="timeline-step <?php echo $is_done ? 'done' : ''; ?> <?php echo $is_current ? 'current' : ''; ?>">
                            <div class="timeline-icon">
                                <i class="fas fa-<?php echo $label['icon']; ?>"></i>
                            </div>
                            <div class="timeline-label"><?php echo $label['text']; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Order Details -->
        <div class="order-details">
            <!-- Shipping & Payment Info -->
            <div class="order-section">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h2 class="section-title">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Địa chỉ giao hàng
                        </h2>
                        <div class="info-item">
                                                            <div class="info-value">
                                    <?php 
                                    if (!empty($order['shipping_address'])) {
                                        echo nl2br(htmlspecialchars($order['shipping_address']));
                                    } else {
                                        echo '<span class="text-muted">Chưa có địa chỉ giao hàng</span>';
                                    }
                                    ?>
                                </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h2 class="section-title">
                            <i class="fas fa-credit-card me-2"></i>
                            Thông tin thanh toán
                        </h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Phương thức</div>
                                <div class="info-value"><?php echo $payment_info['method']; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Trạng thái</div>
                                <div class="info-value">
                                    <span class="badge bg-<?php echo $payment_info['status']['class']; ?>">
                                        <?php echo $payment_info['status']['text']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Note -->
            <?php if (!empty($order['order_note'])): ?>
            <div class="order-section">
                <h2 class="section-title">
                    <i class="fas fa-sticky-note me-2"></i>
                    Ghi chú đơn hàng
                </h2>
                <div class="info-item">
                    <div class="info-value fst-italic">
                        <?php echo nl2br(htmlspecialchars($order['order_note'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Products -->
            <div class="order-section">
                <h2 class="section-title">
                    <i class="fas fa-shopping-basket me-2"></i>
                    Sản phẩm (<?php echo count($order['items']); ?>)
                </h2>
                <div class="product-list">
                    <?php foreach ($order['items'] as $item): ?>
                        <div class="product-item">
                            <img src="<?php echo htmlspecialchars($item['display_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="product-image">
                            <div class="product-info">
                                <a href="shop/details.php?id=<?php echo $item['product_id']; ?>" 
                                   class="product-name">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </a>
                                <div class="product-meta">
                                    <?php echo $item['quantity']; ?> x 
                                    <?php echo number_format($item['unit_price'], 0, ',', '.'); ?>đ
                                </div>
                            </div>
                            <div class="product-price">
                                <?php echo number_format($item['unit_price'] * $item['quantity'], 0, ',', '.'); ?>đ
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="order-section">
                <h2 class="section-title">
                    <i class="fas fa-calculator me-2"></i>
                    Tổng cộng
                </h2>
                <div class="order-summary">
                    <div class="summary-row">
                        <div class="summary-label">Tạm tính</div>
                        <div class="summary-value"><?php echo number_format($order['total'], 0, ',', '.'); ?>đ</div>
                    </div>
                    <div class="summary-row">
                        <div class="summary-label">Phí vận chuyển</div>
                        <div class="summary-value">0đ</div>
                    </div>
                    <div class="summary-row">
                        <div class="summary-label">Tổng cộng</div>
                        <div class="summary-value"><?php echo number_format($order['total'], 0, ',', '.'); ?>đ</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Actions -->
        <div class="order-actions">
            <a href="orders.php" class="btn btn-light btn-action">
                <i class="fas fa-arrow-left"></i>
                Quay lại
            </a>
            
            <?php if ($order['status'] === 'pending'): ?>
                <?php if ($order['payment_method'] === 'sepay' && $order['payment_status'] === 'pending'): ?>
                <a href="order-payment.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-primary btn-action">
                    <i class="fas fa-money-bill"></i>
                    Thanh toán lại
                </a>
                <?php endif; ?>
                <button type="button" class="btn btn-danger btn-action" onclick="cancelOrder(<?php echo $order['order_id']; ?>)">
                    <i class="fas fa-times"></i>
                    Hủy đơn hàng
                </button>
            <?php endif; ?>
            
            <?php if ($order['status'] === 'completed'): ?>
            <button type="button" class="btn btn-primary btn-action" onclick="reorder(<?php echo $order['order_id']; ?>)">
                <i class="fas fa-shopping-cart"></i>
                Mua lại
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function reorder(orderId) {
            Swal.fire({
                title: 'Xác nhận mua lại',
                text: 'Bạn có muốn thêm tất cả sản phẩm từ đơn hàng này vào giỏ hàng?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Có, thêm vào giỏ',
                cancelButtonText: 'Không'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'api/reorder.php',
                        type: 'POST',
                        data: { order_id: orderId },
                        success: function(response) {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Swal.fire({
                                    title: 'Thành công!',
                                    text: data.message,
                                    icon: 'success',
                                    showCancelButton: true,
                                    confirmButtonColor: '#3b82f6',
                                    cancelButtonColor: '#6b7280',
                                    confirmButtonText: 'Đến giỏ hàng',
                                    cancelButtonText: 'Ở lại trang này'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = 'cart.php';
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: 'Lỗi!',
                                    text: data.message,
                                    icon: 'error'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Lỗi!',
                                text: 'Không thể kết nối đến máy chủ',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        }

        function cancelOrder(orderId) {
            Swal.fire({
                title: 'Xác nhận hủy đơn hàng',
                text: 'Bạn có chắc chắn muốn hủy đơn hàng này?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Có, hủy đơn hàng',
                cancelButtonText: 'Không'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'api/cancel-order.php',
                        type: 'POST',
                        data: { order_id: orderId },
                        success: function(response) {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Swal.fire({
                                    title: 'Thành công!',
                                    text: 'Đơn hàng đã được hủy thành công',
                                    icon: 'success'
                                }).then(() => {
                                    window.location.href = 'orders.php';
                                });
                            } else {
                                Swal.fire({
                                    title: 'Lỗi!',
                                    text: data.message,
                                    icon: 'error'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Lỗi!',
                                text: 'Không thể kết nối đến máy chủ',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>