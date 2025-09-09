<?php
session_start();
include '../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';

// Xử lý cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $old_status = $_POST['old_status'] ?? '';
    

    
    try {
        $conn->begin_transaction();
        
        // Cập nhật trạng thái đơn hàng
        $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE order_id = ?");
        $stmt->bind_param("si", $status, $order_id);
        
        if ($stmt->execute()) {
            // Nếu đơn hàng chuyển từ trạng thái khác sang 'completed', trừ tồn kho
            if ($status === 'completed' && $old_status !== 'completed') {
                // Lấy danh sách sản phẩm trong đơn hàng
                $items_stmt = $conn->prepare("
                    SELECT oi.product_id, oi.quantity, p.name as product_name, p.stock 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.product_id 
                    WHERE oi.order_id = ?
                ");
                $items_stmt->bind_param("i", $order_id);
                $items_stmt->execute();
                $items_result = $items_stmt->get_result();
                
                // Kiểm tra tồn kho trước khi trừ
                $items = [];
                while ($item = $items_result->fetch_assoc()) {
                    if ($item['stock'] < $item['quantity']) {
                        throw new Exception("Sản phẩm '{$item['product_name']}' không đủ hàng trong kho (còn {$item['stock']}, cần {$item['quantity']})");
                    }
                    $items[] = $item;
                }
                
                // Nếu tất cả sản phẩm đều đủ hàng, tiến hành trừ tồn kho
                foreach ($items as $item) {
                    $update_stock_stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
                    $update_stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                    
                    if (!$update_stock_stmt->execute()) {
                        throw new Exception("Không thể cập nhật tồn kho cho sản phẩm '{$item['product_name']}'");
                    }
                }
            }
            // Nếu đơn hàng chuyển từ 'completed' sang trạng thái khác, hoàn trả tồn kho
            elseif ($old_status === 'completed' && $status !== 'completed') {
                // Lấy danh sách sản phẩm trong đơn hàng
                $items_stmt = $conn->prepare("
                    SELECT oi.product_id, oi.quantity, p.name as product_name 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.product_id 
                    WHERE oi.order_id = ?
                ");
                $items_stmt->bind_param("i", $order_id);
                $items_stmt->execute();
                $items_result = $items_stmt->get_result();
                
                while ($item = $items_result->fetch_assoc()) {
                    // Hoàn trả tồn kho cho từng sản phẩm
                    $update_stock_stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE product_id = ?");
                    $update_stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                    
                    if (!$update_stock_stmt->execute()) {
                        throw new Exception("Không thể hoàn trả tồn kho cho sản phẩm '{$item['product_name']}'");
                    }
                }
            }
            
            $conn->commit();
            $message = 'Cập nhật trạng thái đơn hàng thành công!';
        } else {
            $conn->rollback();
            $error = 'Không thể cập nhật trạng thái đơn hàng';
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = 'Lỗi: ' . $e->getMessage();
    }
}

// Lấy thông tin đơn hàng chi tiết
$order_detail = null;
if (isset($_GET['view']) && $_GET['view']) {
    $order_id = (int)$_GET['view'];
    
    // Lấy thông tin đơn hàng với tổng tiền được tính lại
    $order_query = "SELECT o.*, 
                   ui.full_name as customer_name, 
                   ui.phone as phone,
                   o.shipping_address,
                   (SELECT SUM(oi2.quantity * oi2.unit_price) 
                    FROM order_items oi2 
                    WHERE oi2.order_id = o.order_id) as calculated_total
                 FROM orders o
                 LEFT JOIN users u ON o.user_id = u.user_id
                 LEFT JOIN users_info ui ON u.user_id = ui.user_id
                 WHERE o.order_id = ?";
    
    $stmt = $conn->prepare($order_query);
    if ($stmt) {
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order_detail = $stmt->get_result()->fetch_assoc();
        
        // Lấy chi tiết sản phẩm trong đơn hàng
        if ($order_detail) {
            $items_query = "SELECT oi.*, 
                           p.name as product_name, 
                           p.image_url as image,
                           (oi.quantity * oi.unit_price) as subtotal
                           FROM order_items oi 
                           LEFT JOIN products p ON oi.product_id = p.product_id 
                           WHERE oi.order_id = ?
                           ORDER BY oi.item_id ASC";
            
            $stmt = $conn->prepare($items_query);
            if ($stmt) {
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                $order_items = $stmt->get_result();
                
                // Tính tổng tiền từ items
                $total_from_items = 0;
                $items_array = [];
                while ($item = $order_items->fetch_assoc()) {
                    $total_from_items += $item['subtotal'];
                    $items_array[] = $item;
                }
                $order_items = $items_array; // Lưu lại để sử dụng sau
                
                // Cập nhật tổng tiền trong order_detail
                $order_detail['total'] = $total_from_items;
            }
        }
    } else {
        $error = "Lỗi chuẩn bị truy vấn: " . $conn->error;
    }
}

// Lấy danh sách đơn hàng với filter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where_conditions = ["1=1"];
$params = [];
$types = '';

if ($search) {
    $where_conditions[] = "(o.order_id LIKE ? OR ui.full_name LIKE ? OR ui.phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if ($status_filter) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($date_from) {
    $where_conditions[] = "DATE(o.order_date) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $where_conditions[] = "DATE(o.order_date) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);

$sql = "SELECT o.*, ui.full_name as customer_name, ui.phone as phone,
               COUNT(oi.item_id) as item_count,
               SUM(oi.quantity * oi.unit_price) as calculated_total
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.user_id
        LEFT JOIN users_info ui ON u.user_id = ui.user_id 
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE $where_clause 
        GROUP BY o.order_id
        ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $orders = $stmt->get_result();
} else {
    $error = "Lỗi chuẩn bị truy vấn: " . $conn->error;
    $orders = null;
}

// Thống kê đơn hàng
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
    SUM(CASE WHEN status = 'shipping' THEN 1 ELSE 0 END) as shipping_orders,
    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
    SUM(CASE WHEN status = 'completed' THEN 
        (SELECT SUM(oi.quantity * oi.unit_price) 
         FROM order_items oi 
         WHERE oi.order_id = orders.order_id)
    ELSE 0 END) as total_revenue
FROM orders
WHERE status != 'cart'";
$stats_result = $conn->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : [
    'total_orders' => 0,
    'pending_orders' => 0,
    'processing_orders' => 0,
    'shipping_orders' => 0,
    'shipped_orders' => 0,
    'completed_orders' => 0,
    'cancelled_orders' => 0,
    'total_revenue' => 0
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - MediSync Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="assets/css/sidebar.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    <style>
        .product-image-placeholder {
            background: linear-gradient(45deg, #f0f0f0 25%, transparent 25%), 
                        linear-gradient(-45deg, #f0f0f0 25%, transparent 25%), 
                        linear-gradient(45deg, transparent 75%, #f0f0f0 75%), 
                        linear-gradient(-45deg, transparent 75%, #f0f0f0 75%);
            background-size: 10px 10px;
            background-position: 0 0, 0 5px, 5px -5px, -5px 0px;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 12px;
        }
        
        .product-image {
            transition: all 0.3s ease;
        }
        
        .product-image:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'includes/headeradmin.php'; ?>
    <?php include 'includes/sidebaradmin.php'; ?>

    <main class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-shopping-cart me-2"></i>Quản lý đơn hàng
                    </h1>
                    <p class="mb-0 text-muted">Quản lý và theo dõi đơn hàng</p>
                </div>
                <?php if (isset($_GET['view'])): ?>
                <a href="?" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại danh sách
                </a>
                <?php endif; ?>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!isset($_GET['view'])): ?>
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card text-center border-0 shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-shopping-cart fa-2x text-primary mb-2"></i>
                                <h5><?= number_format($stats['total_orders']) ?></h5>
                                <small class="text-muted">Tổng đơn hàng</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center border-0 shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                <h5><?= number_format($stats['pending_orders']) ?></h5>
                                <small class="text-muted">Chờ xử lý</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center border-0 shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-cog fa-2x text-info mb-2"></i>
                                <h5><?= number_format($stats['processing_orders']) ?></h5>
                                <small class="text-muted">Đang xử lý</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center border-0 shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-truck fa-2x text-info mb-2"></i>
                                <h5><?= number_format($stats['shipped_orders']) ?></h5>
                                <small class="text-muted">Đang giao</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center border-0 shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <h5><?= number_format($stats['completed_orders']) ?></h5>
                                <small class="text-muted">Hoàn thành</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center border-0 shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                                <h5><?= number_format($stats['cancelled_orders']) ?></h5>
                                <small class="text-muted">Đã hủy</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center border-0 shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                                <h5><?= number_format($stats['total_revenue']) ?>đ</h5>
                                <small class="text-muted">Doanh thu</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Tìm kiếm</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Mã đơn, tên khách hàng, SĐT..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Trạng thái</label>
                                <select name="status" class="form-select">
                                    <option value="">Tất cả</option>
                                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                                    <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Đang xử lý</option>
                                    <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                                    <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Từ ngày</label>
                                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Đến ngày</label>
                                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-2"></i>Tìm kiếm
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Orders List -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-list me-2"></i>Danh sách đơn hàng
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0">Mã đơn</th>
                                        <th class="border-0">Khách hàng</th>
                                        <th class="border-0">Số sản phẩm</th>
                                        <th class="border-0">Tổng tiền</th>
                                        <th class="border-0">Trạng thái</th>
                                        <th class="border-0">Ngày đặt</th>
                                        <th class="border-0">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($orders && $orders->num_rows > 0): ?>
                                        <?php while ($order = $orders->fetch_assoc()): ?>
                                            <tr>
                                                <td class="fw-bold">#<?= $order['order_id'] ?></td>
                                                <td>
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($order['customer_name'] ?? 'Khách hàng') ?></h6>
                                                        <small class="text-muted"><?= htmlspecialchars($order['phone'] ?? '') ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?= $order['item_count'] ?> sản phẩm</span>
                                                </td>
                                                <td class="fw-bold text-primary">
                                                    <?php
                                                        $display_total = $order['calculated_total'] ?? $order['total'];
                                                        echo number_format($display_total) . 'đ';
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    $status_text = '';
                                                    switch($order['status']) {
                                                        case 'pending':
                                                            $status_class = 'warning';
                                                            $status_text = 'Chờ xử lý';
                                                            break;
                                                        case 'processing':
                                                            $status_class = 'info';
                                                            $status_text = 'Đang xử lý';
                                                            break;
                                                        case 'shipping':
                                                            $status_class = 'primary';
                                                            $status_text = 'Đang vận chuyển';
                                                            break;
                                                        case 'shipped':
                                                            $status_class = 'info';
                                                            $status_text = 'Đang giao';
                                                            break;
                                                        case 'completed':
                                                            $status_class = 'success';
                                                            $status_text = 'Hoàn thành';
                                                            break;
                                                        case 'cancelled':
                                                            $status_class = 'danger';
                                                            $status_text = 'Đã hủy';
                                                            break;
                                                        default:
                                                            $status_class = 'secondary';
                                                            $status_text = 'Không xác định';
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y', strtotime($order['order_date'])) ?><br>
                                                        <?= date('H:i', strtotime($order['order_date'])) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?view=<?= $order['order_id'] ?>" 
                                                           class="btn btn-outline-primary" title="Xem chi tiết">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                                                            <button class="btn btn-outline-success" 
                                                                    onclick="updateOrderStatus(<?= $order['order_id'] ?>, 'completed', '<?= $order['status'] ?>')" 
                                                                    title="Hoàn thành">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button class="btn btn-outline-danger" 
                                                                    onclick="updateOrderStatus(<?= $order['order_id'] ?>, 'cancelled', '<?= $order['status'] ?>')" 
                                                                    title="Hủy đơn">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-shopping-cart fa-2x mb-2 d-block"></i>
                                                Không có đơn hàng nào
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Order Detail -->
                <?php if ($order_detail): ?>
                    <div class="row">
                        <div class="col-lg-8">
                            <!-- Order Items -->
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-white py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-box me-2"></i>Sản phẩm trong đơn hàng
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="border-0">Sản phẩm</th>
                                                    <th class="border-0">Đơn giá</th>
                                                    <th class="border-0">Số lượng</th>
                                                    <th class="border-0">Thành tiền</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($order_items)): ?>
                                                    <?php foreach ($order_items as $item): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <?php 
                                                                    $image_src = $item['image'];
                                                                    if (empty($image_src)) {
                                                                        $image_src = '../assets/images/thuoc_icon.jpg';
                                                                    } else {
                                                                        if (!filter_var($image_src, FILTER_VALIDATE_URL) && substr($image_src, 0, 3) !== '../') {
                                                                            $image_src = '../' . $image_src;
                                                                        }
                                                                    }
                                                                    ?>
                                                                    <img src="<?= htmlspecialchars($image_src) ?>" 
                                                                         alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                                                         class="rounded me-3 product-image" 
                                                                         style="width: 50px; height: 50px; object-fit: cover;"
                                                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                                         onload="this.style.display='block'; this.nextElementSibling.style.display='none';">
                                                                    <div class="product-image-placeholder rounded me-3" style="display: none; width: 50px; height: 50px;">
                                                                        <i class="fas fa-image"></i>
                                                                    </div>
                                                                    <div>
                                                                        <h6 class="mb-0"><?= htmlspecialchars($item['product_name']) ?></h6>
                                                                        <small class="text-muted">SKU: <?= htmlspecialchars($item['product_id']) ?></small>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="text-end"><?= number_format($item['unit_price']) ?>đ</td>
                                                            <td class="text-center"><?= $item['quantity'] ?></td>
                                                            <td class="text-end fw-bold"><?= number_format($item['subtotal']) ?>đ</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center py-4">
                                                            <div class="text-muted">
                                                                <i class="fas fa-box mb-2 d-block"></i>
                                                                Không có sản phẩm nào trong đơn hàng
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                            <tfoot class="table-light">
                                                <tr>
                                                    <td colspan="2"></td>
                                                    <td class="text-end"><strong>Tổng cộng:</strong></td>
                                                    <td class="text-end fw-bold text-primary"><?= number_format($order_detail['total']) ?>đ</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <!-- Order Info -->
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-white py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-info-circle me-2"></i>Thông tin đơn hàng
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong>Mã đơn hàng:</strong> #<?= $order_detail['order_id'] ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($order_detail['order_date'])) ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Tổng tiền:</strong> 
                                        <span class="fw-bold text-primary"><?= number_format($order_detail['total']) ?>đ</span>
                                    </div>
                                    <!-- Thêm bảng tổng kết -->
                                    <div class="mt-4 border-top pt-3">
                                        <h6 class="mb-3">Chi tiết thanh toán</h6>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Tổng tiền hàng:</span>
                                            <span class="fw-bold"><?= number_format($order_detail['total']) ?>đ</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Phí vận chuyển:</span>
                                            <span class="fw-bold">20,000đ</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Giảm giá:</span>
                                            <span class="fw-bold">0đ</span>
                                        </div>
                                        <div class="d-flex justify-content-between pt-2 border-top mt-2">
                                            <span class="fw-bold">Tổng thanh toán:</span>
                                            <span class="fw-bold text-primary fs-5"><?= number_format($order_detail['total'] + 20000) ?>đ</span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Trạng thái:</strong>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        switch($order_detail['status']) {
                                            case 'pending':
                                                $status_class = 'warning';
                                                $status_text = 'Chờ xử lý';
                                                break;
                                            case 'processing':
                                                $status_class = 'info';
                                                $status_text = 'Đang xử lý';
                                                break;
                                            case 'shipped':
                                                $status_class = 'info';
                                                $status_text = 'Đang giao';
                                                break;
                                            case 'completed':
                                                $status_class = 'success';
                                                $status_text = 'Hoàn thành';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'danger';
                                                $status_text = 'Đã hủy';
                                                break;
                                        }
                                        ?>
                                        <span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span>
                                    </div>

                                    <!-- Update Status Form -->
                                    <?php if ($order_detail['status'] !== 'completed' && $order_detail['status'] !== 'cancelled'): ?>
                                        <form method="POST" class="mt-3">
                                            <input type="hidden" name="order_id" value="<?= $order_detail['order_id'] ?>">
                                            <input type="hidden" name="old_status" value="<?= $order_detail['status'] ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Cập nhật trạng thái:</label>
                                                <select name="status" class="form-select">
                                                    <option value="pending" <?= $order_detail['status'] === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                                                    <option value="processing" <?= $order_detail['status'] === 'processing' ? 'selected' : '' ?>>Đang xử lý</option>
                                                    <option value="shipped" <?= $order_detail['status'] === 'shipped' ? 'selected' : '' ?>>Đang giao</option>
                                                    <option value="completed" <?= $order_detail['status'] === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                                                    <option value="cancelled" <?= $order_detail['status'] === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                                                </select>
                                            </div>  
                                            <button type="submit" name="update_status" class="btn btn-primary w-100">
                                                <i class="fas fa-save me-2"></i>Cập nhật trạng thái
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Customer Info -->
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-user me-2"></i>Thông tin khách hàng
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong><i class="fas fa-user me-2"></i>Tên khách hàng:</strong><br>
                                        <span class="text-dark fs-6"><?= htmlspecialchars($order_detail['customer_name'] ?? 'N/A') ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <strong><i class="fas fa-phone me-2"></i>Số điện thoại:</strong><br>
                                        <span class="text-dark fs-6"><?= htmlspecialchars($order_detail['phone'] ?? 'N/A') ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <strong><i class="fas fa-map-marker-alt me-2"></i>Địa chỉ giao hàng:</strong><br>
                                        <span class="text-dark fs-6"><?= nl2br(htmlspecialchars($order_detail['shipping_address'] ?? 'N/A')) ?></span>
                                    </div>
                                    <?php if ($order_detail['order_note']): ?>
                                        <div class="mb-3">
                                            <strong>Ghi chú:</strong><br>
                                            <?= htmlspecialchars($order_detail['order_note']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Không tìm thấy đơn hàng này.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/notifications.js"></script>
    <script src="assets/js/admin.js"></script>
    <script>
        function updateOrderStatus(orderId, status, oldStatus = '') {
            const statusText = status === 'completed' ? 'hoàn thành' : 'hủy';
            if (confirm(`Bạn có chắc chắn muốn ${statusText} đơn hàng này?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="order_id" value="${orderId}">
                    <input type="hidden" name="status" value="${status}">
                    <input type="hidden" name="old_status" value="${oldStatus}">
                    <input type="hidden" name="update_status" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html> 