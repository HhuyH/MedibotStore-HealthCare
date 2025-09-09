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

// Lấy thống kê đơn hàng theo trạng thái
$stats_query = "
    SELECT 
        status,
        COUNT(*) as count
    FROM orders 
    WHERE user_id = ? AND status != 'cart'
    GROUP BY status
";

$stats_stmt = $conn->prepare($stats_query);
$order_stats = [
    'pending' => 0,
    'processing' => 0, 
    'shipped' => 0,
    'completed' => 0,
    'cancelled' => 0
];

if ($stats_stmt) {
    $stats_stmt->bind_param('i', $user_id);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    
    while ($row = $stats_result->fetch_assoc()) {
        $order_stats[$row['status']] = (int)$row['count'];
    }
}

// Tính tổng đơn hàng
$total_orders = array_sum($order_stats);

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
            oi.quantity, oi.unit_price, oi.product_id,
            p.name, p.image_url as display_image
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.product_id 
        WHERE oi.order_id = ?
    ");
    
    if ($stmt) {
        $stmt->bind_param('i', $order['order_id']);
        $stmt->execute();
        $orders[$index]['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($orders[$index]['items'] as $item_index => $item) {
            if (empty($item['display_image'])) {
                $orders[$index]['items'][$item_index]['display_image'] = 'assets/images/default-product.jpg';
            }
        }
    } else {
        $orders[$index]['items'] = [];
        error_log("Lỗi prepare statement cho order_id: " . $order['order_id'] . " - " . $conn->error);
    }
}

function getStatusInfo($status) {
    $statuses = [
        'pending' => [
            'text' => 'Chờ xử lý',
            'icon' => 'clock',
            'class' => 'pending'
        ],
        'processing' => [
            'text' => 'Đang xử lý',
            'icon' => 'cog',
            'class' => 'processing'
        ],
        'shipped' => [
            'text' => 'Đang giao hàng',
            'icon' => 'shipping-fast',
            'class' => 'shipped'
        ],
        'completed' => [
            'text' => 'Hoàn thành',
            'icon' => 'check-circle',
            'class' => 'completed'
        ],
        'cancelled' => [
            'text' => 'Đã hủy',
            'icon' => 'times-circle',
            'class' => 'cancelled'
        ]
    ];
    
    return $statuses[$status] ?? [
        'text' => ucfirst($status),
        'icon' => 'question',
        'class' => 'secondary'
    ];
}

function getPaymentInfo($method, $status) {
    $methods = [
        'cod' => 'Thanh toán khi nhận hàng',
        'vnpay' => 'VNPay',
        'momo' => 'MoMo'
    ];
    
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
    <title>Đơn hàng của tôi - MediBot Store</title>
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/orders.css" rel="stylesheet">
    <style>
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .page-header {
                text-align: center;
                padding: 1rem;
            }
            
            .order-filter {
                overflow-x: auto;
                margin: 0 -1rem;
                padding: 0 1rem;
            }
            
            .order-filter .nav-pills {
                flex-wrap: nowrap;
                min-width: max-content;
            }
            
            .order-filter .nav-link {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
                white-space: nowrap;
            }

            .order-card {
                margin-bottom: 1rem;
                border-radius: 10px;
            }

            .order-timeline {
                display: none;
            }

            .order-content {
                padding: 1rem;
            }

            .order-item {
                flex-direction: row;
                align-items: center;
                padding: 0.75rem 0;
                gap: 1rem;
            }

            .item-image {
                width: 60px;
                height: 60px;
                border-radius: 8px;
            }

            .item-details {
                flex: 1;
            }

            .item-name {
                font-size: 0.95rem;
                margin-bottom: 0.25rem;
            }

            .item-meta {
                font-size: 0.85rem;
            }

            .item-price {
                font-size: 0.95rem;
                font-weight: 600;
                color: #3b82f6;
            }

            .order-summary {
                padding: 1rem 0 0;
                margin-top: 1rem;
                border-top: 1px solid #e5e7eb;
            }

            .order-actions {
                flex-direction: column;
                gap: 0.5rem;
                padding: 1rem;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
                background: linear-gradient(135deg,rgb(144, 194, 243),rgb(197, 213, 218)) !important;
            }

            /* Thêm status badge vào dưới thông tin đơn hàng */
            .order-card .d-flex {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-card .badge {
                margin-top: 0.5rem;
                align-self: flex-start;
            }

            /* Cải thiện hiển thị thông tin đơn hàng */
            .text-muted.small {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                margin-top: 0.5rem;
            }

            .text-muted.small i {
                width: 16px;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
            <!-- Page Header -->
            <div class="page-header">
            <h1 class="page-title">Đơn hàng của tôi</h1>
                <p class="page-subtitle">Theo dõi và quản lý đơn hàng của bạn</p>
            </div>

            <!-- Order Filter -->
            <div class="order-filter mb-4">
                <div class="nav nav-pills">
                    <a class="nav-link active" href="#" data-status="all">
                        Tất cả (<?php echo $total_orders; ?>)
                    </a>
                    <a class="nav-link text-warning" href="#" data-status="pending">
                        <i class="fas fa-clock me-1"></i>
                        Chờ xử lý (<?php echo $order_stats['pending']; ?>)
                    </a>
                    <a class="nav-link text-primary" href="#" data-status="processing">
                        <i class="fas fa-cog me-1"></i>
                        Đang xử lý (<?php echo $order_stats['processing']; ?>)
                    </a>
                    <a class="nav-link text-info" href="#" data-status="shipped">
                        <i class="fas fa-shipping-fast me-1"></i>
                        Đang giao (<?php echo $order_stats['shipped']; ?>)
                    </a>
                    <a class="nav-link text-success" href="#" data-status="completed">
                        <i class="fas fa-check-circle me-1"></i>
                        Hoàn thành (<?php echo $order_stats['completed']; ?>)
                    </a>
                    <a class="nav-link text-danger" href="#" data-status="cancelled">
                        <i class="fas fa-times-circle me-1"></i>
                        Đã hủy (<?php echo $order_stats['cancelled']; ?>)
                    </a>
                </div>
            </div>

            <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-shopping-cart fa-4x text-muted"></i>
                </div>
                <h3 class="h4 mb-3">Chưa có đơn hàng nào</h3>
                <p class="text-muted mb-4">Bạn chưa có đơn hàng nào. Hãy khám phá các sản phẩm tuyệt vời của chúng tôi!</p>
                <a href="shop.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i>
                        Mua sắm ngay
                    </a>
                </div>
            <?php else: ?>
            <?php foreach ($orders as $order): 
                $status_info = getStatusInfo($order['status']);
                $payment_info = getPaymentInfo($order['payment_method'], $order['payment_status']);
                $timeline_progress = getTimelineProgress($order['status']);
            ?>
                <div class="order-card">
                    <div class="order-header d-flex align-items-center justify-content-between p-3" onclick="window.location.href='order-details.php?id=<?php echo $order['order_id']; ?>'" style="cursor: pointer;">
                        <div>
                            <h5 class="mb-2">Đơn hàng #<?php echo $order['order_id']; ?></h5>
                            <div class="text-muted small">
                                <i class="fas fa-calendar me-2"></i><?php echo date('d/m/Y', strtotime($order['order_date'])); ?>
                                <i class="fas fa-box ms-3 me-2"></i><?php echo $order['item_count']; ?> sản phẩm
                                <i class="fas fa-money-bill ms-3 me-2"></i><?php echo number_format($order['total'], 0, ',', '.'); ?>đ
                            </div>
                        </div>
                        <span class="badge bg-<?php echo $status_info['class']; ?>">
                            <i class="fas fa-<?php echo $status_info['icon']; ?> me-1"></i>
                            <?php echo $status_info['text']; ?>
                        </span>
                    </div>

                    <?php if ($order['status'] !== 'cancelled'): ?>
                    <div class="order-timeline">
                        <div class="timeline-line"></div>
                        <div class="timeline-progress" style="width: <?php echo $timeline_progress; ?>%"></div>
                        
                        <?php
                        $steps = [
                            'pending' => 'Đặt hàng',
                            'processing' => 'Xử lý',
                            'shipped' => 'Vận chuyển',
                            'completed' => 'Hoàn thành'
                        ];
                        
                        foreach ($steps as $step => $label):
                            $is_done = array_search($step, array_keys($steps)) <= array_search($order['status'], array_keys($steps));
                            $is_current = $step === $order['status'];
                        ?>
                            <div class="timeline-step <?php echo $is_done ? 'done' : ''; ?> <?php echo $is_current ? 'current' : ''; ?>">
                                <div class="timeline-icon">
                                    <i class="fas fa-<?php echo getStatusInfo($step)['icon']; ?>"></i>
                                </div>
                                <div class="timeline-label"><?php echo $label; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="order-content">
                        <div class="order-items">
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="order-item">
                                        <img src="<?php echo htmlspecialchars($item['display_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             class="item-image">
                                    <div class="item-details">
                                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="item-meta">
                                            Số lượng: <?php echo $item['quantity']; ?> x 
                                            <?php echo number_format($item['unit_price'], 0, ',', '.'); ?>đ
                                            </div>
                                        </div>
                                    <div class="item-price">
                                        <?php echo number_format($item['unit_price'] * $item['quantity'], 0, ',', '.'); ?>đ
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-summary">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Tổng cộng:</span>
                                <span class="h4 mb-0 text-primary">
                                    <?php echo number_format($order['total'], 0, ',', '.'); ?>đ
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="order-actions">
                        <button type="button" class="btn btn-action btn-primary" 
                                onclick="event.stopPropagation(); window.location.href='order-details.php?id=<?php echo $order['order_id']; ?>'">
                            <i class="fas fa-eye"></i>
                            Xem chi tiết
                        </button>
                        
                                                    <?php if ($order['status'] === 'pending'): ?>
                                <?php if ($order['payment_method'] === 'sepay' && $order['payment_status'] === 'pending'): ?>
                                <a href="order-payment.php?order_id=<?php echo $order['order_id']; ?>" 
                                   class="btn btn-action btn-primary"
                                   onclick="event.stopPropagation();">
                                    <i class="fas fa-money-bill"></i>
                                    Thanh toán lại
                                </a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-action btn-danger" 
                                        onclick="event.stopPropagation(); cancelOrder(<?php echo $order['order_id']; ?>)">
                                    <i class="fas fa-times"></i>
                                    Hủy đơn hàng
                                </button>
                            <?php endif; ?>

                        <?php if ($order['status'] === 'completed'): ?>
                        <button type="button" class="btn btn-action btn-success" 
                                onclick="event.stopPropagation(); reorder(<?php echo $order['order_id']; ?>)">
                            <i class="fas fa-shopping-cart"></i>
                            Mua lại
                        </button>
                        <?php endif; ?>
                    </div>
                </div>


                <?php endforeach; ?>
            <?php endif; ?>
        </div>

  
  <?php include 'includes/footer.php'; ?>
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Filter orders
        $('.nav-pills .nav-link').click(function(e) {
            e.preventDefault();
            const status = $(this).data('status');
            
            // Update active link
            $('.nav-pills .nav-link').removeClass('active');
            $(this).addClass('active');
            
            if (status === 'all') {
                $('.order-card').show();
            } else {
                $('.order-card').each(function() {
                    const orderStatus = $(this).find('.badge').text().trim();
                    const statusMap = {
                        'Chờ xử lý': 'pending',
                        'Đang xử lý': 'processing',
                        'Đang giao hàng': 'shipped',
                        'Hoàn thành': 'completed',
                        'Đã hủy': 'cancelled'
                    };
                    $(this).toggle(statusMap[orderStatus] === status);
                });
            }
        });

        function reorder(orderId) {
            Swal.fire({
                title: 'Xác nhận mua lại',
                text: 'Bạn có muốn thêm tất cả sản phẩm từ đơn hàng này vào giỏ hàng?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#22c55e',
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
                                let message = data.message;
                                if (data.warnings && data.warnings.length > 0) {
                                    message += '<br><br>Lưu ý:<br>' + data.warnings.join('<br>');
                                }
                                
                                Swal.fire({
                                    title: 'Thành công!',
                                    html: message,
                                    icon: 'success',
                                    showCancelButton: true,
                                    confirmButtonColor: '#3b82f6',
                                    cancelButtonColor: '#6b7280',
                                    confirmButtonText: 'Đến giỏ hàng',
                                    cancelButtonText: 'Tiếp tục mua sắm'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = 'cart.php';
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: 'Lỗi!',
                                    html: data.message + '<br><br>' + data.warnings.join('<br>'),
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
                cancelButtonText: 'Không, giữ lại'
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
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Lỗi!',
                                    text: data.message || 'Có lỗi xảy ra khi hủy đơn hàng',
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

        $(document).ready(function() {
            // Add fade-in animation to cards
            $('.order-card').each(function(index) {
                $(this).css({
                    'animation': 'fadeIn 0.6s ease-in-out',
                    'animation-delay': (index * 0.1) + 's'
                });
            });

            // Counter animation for statistics
            $('.stat-number').each(function() {
                const $this = $(this);
                const finalValue = parseInt($this.text());
                
                if (finalValue > 0) {
                    $this.prop('Counter', 0).animate({
                        Counter: finalValue
                    }, {
                            duration: 1500,
                            easing: 'swing',
                        step: function(now) {
                            $this.text(Math.ceil(now));
                            },
                            complete: function() {
                                $this.text(finalValue);
                            }
                        });
                }
            });
        });
    </script>
 
</body>
</html> 