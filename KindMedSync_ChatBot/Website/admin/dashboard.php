<?php
session_start();
require_once '../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit();
}

// Lấy thống kê tổng quan
$stats = [];

// Tổng số bệnh nhân (role_id = 2)
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role_id = 2");
    $stats['patients'] = $result ? $result->fetch_assoc()['total'] : 0;
} catch (Exception $e) {
    $stats['patients'] = 0;
}

// Tổng số bác sĩ (role_id = 3)
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role_id = 3");
    $stats['doctors'] = $result ? $result->fetch_assoc()['total'] : 0;
} catch (Exception $e) {
    $stats['doctors'] = 0;
}

// Tổng số sản phẩm
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
    $stats['products'] = $result ? $result->fetch_assoc()['total'] : 0;
} catch (Exception $e) {
    $stats['products'] = 0;
}

// Tổng số đơn hàng
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status != 'cart'");
    $stats['orders'] = $result ? $result->fetch_assoc()['total'] : 0;
} catch (Exception $e) {
    $stats['orders'] = 0;
}

// Lịch hẹn hôm nay
$today = date('Y-m-d');
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE DATE(appointment_time) = '$today'");
    $stats['appointments_today'] = $result ? $result->fetch_assoc()['total'] : 0;
} catch (Exception $e) {
    $stats['appointments_today'] = 0;
}

// Lịch hẹn tháng này
$this_month = date('Y-m');
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE DATE_FORMAT(appointment_time, '%Y-%m') = '$this_month'");
    $stats['appointments_month'] = $result ? $result->fetch_assoc()['total'] : 0;
} catch (Exception $e) {
    $stats['appointments_month'] = 0;
}

// Doanh thu tháng này
try {
    $result = $conn->query("SELECT SUM(total) as revenue FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$this_month' AND status = 'completed'");
    $stats['revenue'] = $result ? ($result->fetch_assoc()['revenue'] ?? 0) : 0;
} catch (Exception $e) {
    $stats['revenue'] = 0;
}

// Lịch hẹn cần xử lý (pending)
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'");
    $stats['pending_appointments'] = $result ? $result->fetch_assoc()['total'] : 0;
} catch (Exception $e) {
    $stats['pending_appointments'] = 0;
}

// Lấy dữ liệu cho bảng
// Lịch hẹn gần đây
try {
    $recent_appointments = $conn->query("
        SELECT a.*, 
               COALESCE(up.full_name, u_patient.username) as patient_name,
               COALESCE(ui_doctor.full_name, u_doctor.username, u_doctor.email) as doctor_name,
               gu.full_name as guest_name,
               c.name as clinic_name
        FROM appointments a
        LEFT JOIN users_info up ON a.user_id = up.user_id
        LEFT JOIN users u_patient ON a.user_id = u_patient.user_id
        LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
        LEFT JOIN users u_doctor ON d.user_id = u_doctor.user_id
        LEFT JOIN users_info ui_doctor ON d.user_id = ui_doctor.user_id
        LEFT JOIN guest_users gu ON a.guest_id = gu.guest_id
        LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
        ORDER BY a.created_at DESC
        LIMIT 8
    ");
} catch (Exception $e) {
    $recent_appointments = false;
}

// Đơn hàng gần đây
try {
    $recent_orders = $conn->query("
        SELECT o.*, ui.full_name as customer_name
        FROM orders o
        LEFT JOIN users_info ui ON o.user_id = ui.user_id
        WHERE o.status != 'cart'
        ORDER BY o.order_date DESC
        LIMIT 5
    ");
} catch (Exception $e) {
    $recent_orders = false;
}

// Sản phẩm bán chạy
try {
    $top_products = $conn->query("
        SELECT p.*, COUNT(oi.product_id) as sold_count
        FROM products p
        LEFT JOIN order_items oi ON p.product_id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.order_id
        WHERE o.status = 'completed' AND p.is_active = 1
        GROUP BY p.product_id
        ORDER BY sold_count DESC
        LIMIT 5
    ");
} catch (Exception $e) {
    $top_products = false;
}

// Bệnh nhân mới đăng ký
try {
    $new_patients = $conn->query("
        SELECT u.*, ui.full_name, ui.phone
        FROM users u
        LEFT JOIN users_info ui ON u.user_id = ui.user_id
        WHERE u.role_id = 2
        ORDER BY u.created_at DESC
        LIMIT 5
    ");
} catch (Exception $e) {
    $new_patients = false;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MediSync Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="assets/css/sidebar.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    <style>
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .stats-card.success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stats-card.warning {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        .stats-card.info {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
        }
        .stats-card.danger {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            color: #333;
        }
        .quick-action-card {
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
            height: 100%;
        }
        .quick-action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .table-modern {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .badge-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
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
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </h1>
                    <p class="mb-0 text-muted">Chào mừng trở lại, <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?>!</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-success">
                        <i class="fas fa-circle me-1"></i>Online
                    </span>
                    <span class="text-muted"><?= date('d/m/Y H:i') ?></span>
                </div>
            </div>
        
            <!-- Stats Cards Row 1 -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase mb-1" style="font-size: 0.8rem; opacity: 0.8;">
                                    Tổng bệnh nhân
                                </div>
                                <div class="h4 mb-0 font-weight-bold"><?= number_format($stats['patients']) ?></div>
                                <small class="opacity-75">
                                    <i class="fas fa-arrow-up me-1"></i>+12% so với tháng trước
                                </small>
                            </div>
                            <div class="text-end">
                                <i class="fas fa-users fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="stats-card success">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase mb-1" style="font-size: 0.8rem; opacity: 0.8;">
                                    Tổng bác sĩ
                                </div>
                                <div class="h4 mb-0 font-weight-bold"><?= number_format($stats['doctors']) ?></div>
                                <small class="opacity-75">
                                    <i class="fas fa-arrow-up me-1"></i>+3 bác sĩ mới
                                </small>
                            </div>
                            <div class="text-end">
                                <i class="fas fa-user-md fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="stats-card warning">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase mb-1" style="font-size: 0.8rem; opacity: 0.8;">
                                    Tổng sản phẩm
                                </div>
                                <div class="h4 mb-0 font-weight-bold"><?= number_format($stats['products']) ?></div>
                                <small class="opacity-75">
                                    <i class="fas fa-box me-1"></i>Đang hoạt động
                                </small>
                            </div>
                            <div class="text-end">
                                <i class="fas fa-pills fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="stats-card info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase mb-1" style="font-size: 0.8rem; opacity: 0.8;">
                                    Tổng đơn hàng
                                </div>
                                <div class="h4 mb-0 font-weight-bold"><?= number_format($stats['orders']) ?></div>
                                <small class="opacity-75">
                                    <i class="fas fa-shopping-cart me-1"></i>Đã xử lý
                                </small>
                            </div>
                            <div class="text-end">
                                <i class="fas fa-shopping-bag fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards Row 2 -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card danger">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase mb-1" style="font-size: 0.8rem; opacity: 0.8;">
                                    Lịch hẹn hôm nay
                                </div>
                                <div class="h4 mb-0 font-weight-bold"><?= number_format($stats['appointments_today']) ?></div>
                                <small class="opacity-75">
                                    <i class="fas fa-calendar-day me-1"></i><?= date('d/m/Y') ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <i class="fas fa-calendar-check fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase mb-1" style="font-size: 0.8rem; opacity: 0.8;">
                                    Lịch hẹn tháng này
                                </div>
                                <div class="h4 mb-0 font-weight-bold"><?= number_format($stats['appointments_month']) ?></div>
                                <small class="opacity-75">
                                    <i class="fas fa-calendar-alt me-1"></i>Tháng <?= date('m/Y') ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <i class="fas fa-calendar fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="stats-card success">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase mb-1" style="font-size: 0.8rem; opacity: 0.8;">
                                    Doanh thu tháng
                                </div>
                                <div class="h4 mb-0 font-weight-bold"><?= number_format($stats['revenue']) ?>đ</div>
                                <small class="opacity-75">
                                    <i class="fas fa-chart-line me-1"></i>+8% so với tháng trước
                                </small>
                            </div>
                            <div class="text-end">
                                <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="stats-card warning">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase mb-1" style="font-size: 0.8rem; opacity: 0.8;">
                                    Lịch hẹn chờ duyệt
                                </div>
                                <div class="h4 mb-0 font-weight-bold"><?= number_format($stats['pending_appointments']) ?></div>
                                <small class="opacity-75">
                                    <i class="fas fa-clock me-1"></i>Cần xử lý
                                </small>
                            </div>
                            <div class="text-end">
                                <i class="fas fa-hourglass-half fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-bolt me-2"></i>Thao tác nhanh
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                    <a href="appointments.php" class="text-decoration-none">
                                        <div class="quick-action-card card text-center p-3">
                                            <i class="fas fa-calendar-plus fa-2x text-primary mb-2"></i>
                                            <h6 class="mb-0">Lịch hẹn</h6>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                    <a href="users.php" class="text-decoration-none">
                                        <div class="quick-action-card card text-center p-3">
                                            <i class="fas fa-users fa-2x text-success mb-2"></i>
                                            <h6 class="mb-0">Người dùng</h6>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                    <a href="products.php" class="text-decoration-none">
                                        <div class="quick-action-card card text-center p-3">
                                            <i class="fas fa-pills fa-2x text-warning mb-2"></i>
                                            <h6 class="mb-0">Sản phẩm</h6>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                    <a href="orders.php" class="text-decoration-none">
                                        <div class="quick-action-card card text-center p-3">
                                            <i class="fas fa-shopping-cart fa-2x text-info mb-2"></i>
                                            <h6 class="mb-0">Đơn hàng</h6>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                    <a href="users.php?role=doctor" class="text-decoration-none">
                                        <div class="quick-action-card card text-center p-3">
                                            <i class="fas fa-user-md fa-2x text-danger mb-2"></i>
                                            <h6 class="mb-0">Bác sĩ</h6>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                    <a href="reports.php" class="text-decoration-none">
                                        <div class="quick-action-card card text-center p-3">
                                            <i class="fas fa-chart-bar fa-2x text-secondary mb-2"></i>
                                            <h6 class="mb-0">Báo cáo</h6>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Row -->
            <div class="row">
                <!-- Recent Appointments -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-calendar-check me-2"></i>Lịch hẹn gần đây
                            </h6>
                            <a href="appointments.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>Xem tất cả
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 table-modern">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-0">Bệnh nhân</th>
                                            <th class="border-0">Bác sĩ</th>
                                            <th class="border-0">Phòng khám</th>
                                            <th class="border-0">Ngày hẹn</th>
                                            <th class="border-0">Trạng thái</th>
                                            <th class="border-0">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($recent_appointments && $recent_appointments->num_rows > 0): ?>
                                            <?php while ($appointment = $recent_appointments->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-sm me-2">
                                                                <div class="avatar-title bg-soft-primary text-primary rounded-circle">
                                                                    <?php 
                                                                    $patient_name = $appointment['patient_name'] ?? $appointment['guest_name'] ?? 'N/A';
                                                                    echo strtoupper(substr($patient_name, 0, 1));
                                                                    ?>
                                                                </div>
                                                            </div>
                                                            <span><?= htmlspecialchars($patient_name) ?></span>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($appointment['doctor_name'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars($appointment['clinic_name'] ?? 'N/A') ?></td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?= date('d/m/Y', strtotime($appointment['appointment_time'])) ?><br>
                                                            <?= date('H:i', strtotime($appointment['appointment_time'])) ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $status_class = '';
                                                        $status_text = '';
                                                        switch($appointment['status']) {
                                                            case 'pending':
                                                                $status_class = 'warning';
                                                                $status_text = 'Chờ xác nhận';
                                                                break;
                                                            case 'confirmed':
                                                                $status_class = 'success';
                                                                $status_text = 'Đã xác nhận';
                                                                break;
                                                            case 'completed':
                                                                $status_class = 'primary';
                                                                $status_text = 'Hoàn thành';
                                                                break;
                                                            case 'canceled':
                                                                $status_class = 'danger';
                                                                $status_text = 'Đã hủy';
                                                                break;
                                                            default:
                                                                $status_class = 'secondary';
                                                                $status_text = 'Không xác định';
                                                        }
                                                        ?>
                                                        <span class="badge badge-status bg-<?= $status_class ?>"><?= $status_text ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($appointment['status'] == 'pending'): ?>
                                                            <div class="btn-group btn-group-sm">
                                                                <button class="btn btn-success btn-sm" onclick="updateAppointmentStatus(<?= $appointment['appointment_id'] ?>, 'confirmed')">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                                <button class="btn btn-danger btn-sm" onclick="updateAppointmentStatus(<?= $appointment['appointment_id'] ?>, 'canceled')">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </div>
                                                        <?php else: ?>
                                                            <a href="appointments.php?id=<?= $appointment['appointment_id'] ?>" class="btn btn-outline-primary btn-sm">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">
                                                    <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
                                                    Chưa có lịch hẹn nào
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Info -->
                <div class="col-lg-4">
                    <!-- Recent Orders -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-shopping-cart me-2"></i>Đơn hàng gần đây
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                                <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-3">
                                                <div class="avatar-title bg-soft-success text-success rounded-circle">
                                                    <i class="fas fa-shopping-bag"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">#<?= $order['order_id'] ?></h6>
                                                <p class="text-muted mb-0 small">
                                                    <?= htmlspecialchars($order['customer_name'] ?? 'Khách hàng') ?>
                                                </p>
                                                <p class="text-muted mb-0 small">
                                                    <?= number_format($order['total']) ?>đ
                                                </p>
                                            </div>
                                        </div>
                                        <span class="badge bg-<?= $order['status'] == 'completed' ? 'success' : ($order['status'] == 'pending' ? 'warning' : 'secondary') ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-center text-muted">Chưa có đơn hàng nào</p>
                            <?php endif; ?>
                            
                            <div class="text-center mt-3">
                                <a href="orders.php" class="btn btn-success btn-sm">
                                    <i class="fas fa-eye me-2"></i>Xem tất cả
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Top Products -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 font-weight-bold text-warning">
                                <i class="fas fa-star me-2"></i>Sản phẩm bán chạy
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if ($top_products && $top_products->num_rows > 0): ?>
                                <?php while ($product = $top_products->fetch_assoc()): ?>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar-sm me-3">
                                            <img src="<?= htmlspecialchars($product['image_url'] ?? 'assets/images/default-product.jpg') ?>" 
                                                 alt="<?= htmlspecialchars($product['name']) ?>" 
                                                 class="avatar-title rounded">
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0"><?= htmlspecialchars($product['name']) ?></h6>
                                            <p class="text-muted mb-0 small">
                                                Đã bán: <?= $product['sold_count'] ?? 0 ?> | 
                                                Giá: <?= number_format($product['price']) ?>đ
                                            </p>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-center text-muted">Chưa có dữ liệu bán hàng</p>
                            <?php endif; ?>
                            
                            <div class="text-center mt-3">
                                <a href="products.php" class="btn btn-warning btn-sm">
                                    <i class="fas fa-eye me-2"></i>Xem tất cả
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- New Patients -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 font-weight-bold text-info">
                                <i class="fas fa-user-plus me-2"></i>Bệnh nhân mới
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if ($new_patients && $new_patients->num_rows > 0): ?>
                                <?php while ($patient = $new_patients->fetch_assoc()): ?>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar-sm me-3">
                                            <div class="avatar-title bg-soft-info text-info rounded-circle">
                                                <?= strtoupper(substr($patient['full_name'] ?? $patient['username'], 0, 1)) ?>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0"><?= htmlspecialchars($patient['full_name'] ?? $patient['username']) ?></h6>
                                            <p class="text-muted mb-0 small">
                                                <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($patient['email']) ?>
                                            </p>
                                            <p class="text-muted mb-0 small">
                                                <i class="fas fa-clock me-1"></i><?= date('d/m/Y', strtotime($patient['created_at'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-center text-muted">Chưa có bệnh nhân mới</p>
                            <?php endif; ?>
                            
                            <div class="text-center mt-3">
                                <a href="users.php?role=patient" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye me-2"></i>Xem tất cả
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/notifications.js"></script>
    <script src="assets/js/admin.js"></script>
    <script>
        // Function to update appointment status
        function updateAppointmentStatus(appointmentId, status) {
            if (confirm('Bạn có chắc chắn muốn ' + (status === 'confirmed' ? 'xác nhận' : 'hủy') + ' lịch hẹn này?')) {
                fetch('ajax/update_appointment_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        appointment_id: appointmentId,
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Có lỗi xảy ra: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi cập nhật trạng thái');
                });
            }
        }

        // Auto refresh every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html> 