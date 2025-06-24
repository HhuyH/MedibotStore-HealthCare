<?php
session_start();
require_once '../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit();
}

$user_id = (int)($_GET['id'] ?? 0);
if (!$user_id) {
    header('Location: users.php?error=invalid_id');
    exit();
}

// Lấy thông tin user với query đơn giản
$user = null;
$user_info = null;
$role = null;

// Query 1: Basic user info
try {
    $result = $conn->query("SELECT * FROM users WHERE user_id = $user_id");
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        header('Location: users.php?error=not_found');
        exit();
    }
} catch (Exception $e) {
    header('Location: users.php?error=database_error');
    exit();
}

// Query 2: User info (optional)
try {
    $result = $conn->query("SELECT * FROM users_info WHERE user_id = $user_id");
    if ($result && $result->num_rows > 0) {
        $user_info = $result->fetch_assoc();
    } else {
        $user_info = null;
    }
} catch (Exception $e) {
    // Ignore error, user_info is optional
    $user_info = null;
}

// Query 3: Role info
try {
    $result = $conn->query("SELECT * FROM roles WHERE role_id = " . $user['role_id']);
    if ($result && $result->num_rows > 0) {
        $role = $result->fetch_assoc();
    } else {
        $role = null;
    }
} catch (Exception $e) {
    // Ignore error
    $role = null;
}

// Lấy thống kê hoạt động với query đơn giản
$stats = ['total_appointments' => 0, 'completed_appointments' => 0, 'total_orders' => 0, 'total_spent' => 0];

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE user_id = $user_id");
    if ($result) {
        $stats['total_appointments'] = $result->fetch_assoc()['count'];
    }
} catch (Exception $e) {
    // Ignore
}

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE user_id = $user_id AND status = 'completed'");
    if ($result) {
        $stats['completed_appointments'] = $result->fetch_assoc()['count'];
    }
} catch (Exception $e) {
    // Ignore
}

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id");
    if ($result) {
        $stats['total_orders'] = $result->fetch_assoc()['count'];
    }
} catch (Exception $e) {
    // Ignore
}

try {
    $result = $conn->query("SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE user_id = $user_id AND status = 'completed'");
    if ($result) {
        $stats['total_spent'] = $result->fetch_assoc()['total'];
    }
} catch (Exception $e) {
    // Ignore
}

// Lấy lịch hẹn gần đây với query đơn giản
try {
    $recent_appointments = $conn->query("
        SELECT a.*, 
               COALESCE(ui_doctor.full_name, u_doctor.username) as doctor_name,
               c.name as clinic_name
        FROM appointments a
        LEFT JOIN users u_doctor ON a.doctor_id = u_doctor.user_id
        LEFT JOIN users_info ui_doctor ON a.doctor_id = ui_doctor.user_id
        LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
        WHERE a.user_id = $user_id
        ORDER BY a.appointment_time DESC
        LIMIT 5
    ");
} catch (Exception $e) {
    $recent_appointments = false;
}

// Lấy đơn hàng gần đây với query đơn giản
try {
    $recent_orders = $conn->query("
        SELECT o.*, 
               COUNT(oi.order_item_id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.user_id = $user_id AND o.status != 'cart'
        GROUP BY o.order_id
        ORDER BY o.order_date DESC
        LIMIT 5
    ");
} catch (Exception $e) {
    $recent_orders = false;
}

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $allowed_statuses = ['active', 'inactive', 'suspended'];
    
    if (in_array($new_status, $allowed_statuses)) {
        $update_sql = "UPDATE users SET status = ?, updated_at = NOW() WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        
        if ($update_stmt) {
            $update_stmt->bind_param("si", $new_status, $user_id);
            
            if ($update_stmt->execute()) {
                $user['status'] = $new_status;
                $success_message = "Cập nhật trạng thái thành công!";
            } else {
                $error_message = "Cập nhật trạng thái thất bại!";
            }
        } else {
            $error_message = "Lỗi chuẩn bị câu lệnh SQL!";
        }
    }
}

$role_colors = [
    'admin' => 'danger',
    'patient' => 'primary', 
    'doctor' => 'success'
];
$role_name = $role ? $role['role_name'] : 'unknown';
$role_color = $role_colors[$role_name] ?? 'secondary';

$status_configs = [
    'active' => ['class' => 'success', 'text' => 'Hoạt động', 'icon' => 'check-circle'],
    'inactive' => ['class' => 'warning', 'text' => 'Không hoạt động', 'icon' => 'pause-circle'],
    'suspended' => ['class' => 'danger', 'text' => 'Bị khóa', 'icon' => 'ban']
];
$current_status = $status_configs[$user['status']] ?? ['class' => 'secondary', 'text' => 'Không xác định', 'icon' => 'question'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết người dùng - <?= htmlspecialchars($user['username']) ?> - QickMed Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="assets/css/sidebar.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
    <style>
        .info-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .user-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            font-weight: bold;
            margin: 0 auto 1rem;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
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
        .activity-item {
            border-left: 3px solid #e9ecef;
            padding-left: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }
        .activity-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 0;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #6c757d;
        }
        .activity-item.success::before {
            background: #198754;
        }
        .activity-item.warning::before {
            background: #ffc107;
        }
        .activity-item.danger::before {
            background: #dc3545;
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
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="users.php">Người dùng</a></li>
                            <li class="breadcrumb-item active"><?= htmlspecialchars($user['username']) ?></li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-0 text-gray-800">Chi tiết người dùng</h1>
                </div>
                <div>
                    <a href="users.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                    </a>
                    <a href="user-edit.php?id=<?= $user_id ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Chỉnh sửa
                    </a>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Thông tin chính -->
                <div class="col-lg-8">
                    <!-- Profile Card -->
                    <div class="card info-card mb-4">
                        <div class="card-body text-center">
                            <div class="user-avatar">
                                <?= strtoupper(substr(($user_info['full_name'] ?? null) ?: $user['username'], 0, 1)) ?>
                            </div>
                            <h4 class="mb-1"><?= htmlspecialchars(($user_info['full_name'] ?? null) ?: $user['username']) ?></h4>
                            <p class="text-muted mb-2">@<?= htmlspecialchars($user['username']) ?></p>
                            <div class="d-flex justify-content-center gap-3 mb-3">
                                <span class="badge bg-<?= $role_color ?> px-3 py-2">
                                    <i class="fas fa-user-tag me-1"></i><?= ucfirst($role_name) ?>
                                </span>
                                <span class="badge bg-<?= $current_status['class'] ?> px-3 py-2">
                                    <i class="fas fa-<?= $current_status['icon'] ?> me-1"></i><?= $current_status['text'] ?>
                                </span>
                            </div>
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="h5 mb-0"><?= number_format($stats['total_appointments']) ?></div>
                                    <small class="text-muted">Lịch hẹn</small>
                                </div>
                                <div class="col-4">
                                    <div class="h5 mb-0"><?= number_format($stats['total_orders']) ?></div>
                                    <small class="text-muted">Đơn hàng</small>
                                </div>
                                <div class="col-4">
                                    <div class="h5 mb-0"><?= number_format($stats['total_spent']) ?>₫</div>
                                    <small class="text-muted">Tổng chi tiêu</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Thông tin cá nhân -->
                    <div class="card info-card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user me-2"></i>Thông tin cá nhân
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Họ tên đầy đủ</label>
                                        <div class="h6"><?= htmlspecialchars(($user_info['full_name'] ?? null) ?: 'Chưa cập nhật') ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Email</label>
                                        <div>
                                            <a href="mailto:<?= htmlspecialchars($user['email']) ?>">
                                                <?= htmlspecialchars($user['email']) ?>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Số điện thoại</label>
                                        <div>
                                            <?php if ($user['phone_number']): ?>
                                                <a href="tel:<?= htmlspecialchars($user['phone_number']) ?>">
                                                    <?= htmlspecialchars($user['phone_number']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Chưa cập nhật</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Ngày sinh</label>
                                        <div>
                                            <?php if ($user_info && isset($user_info['date_of_birth']) && $user_info['date_of_birth']): ?>
                                                <?= date('d/m/Y', strtotime($user_info['date_of_birth'])) ?>
                                                <small class="text-muted">
                                                    (<?= date_diff(date_create($user_info['date_of_birth']), date_create('today'))->y ?> tuổi)
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">Chưa cập nhật</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Ngày đăng ký</label>
                                        <div><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Cập nhật cuối</label>
                                        <div><?= date('d/m/Y H:i', strtotime($user['updated_at'])) ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($user_info && isset($user_info['address']) && $user_info['address']): ?>
                                <div class="mb-3">
                                    <label class="form-label text-muted">Địa chỉ</label>
                                    <div><?= htmlspecialchars($user_info['address']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Lịch hẹn gần đây -->
                    <div class="card info-card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-alt me-2"></i>Lịch hẹn gần đây
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($recent_appointments && $recent_appointments->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Ngày giờ</th>
                                                <th>Bác sĩ</th>
                                                <th>Phòng khám</th>
                                                <th>Trạng thái</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($appointment = $recent_appointments->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <div><?= date('d/m/Y', strtotime($appointment['appointment_time'])) ?></div>
                                                        <small class="text-muted"><?= date('H:i', strtotime($appointment['appointment_time'])) ?></small>
                                                    </td>
                                                    <td><?= htmlspecialchars($appointment['doctor_name']) ?></td>
                                                    <td><?= htmlspecialchars($appointment['clinic_name'] ?? 'Chưa chọn') ?></td>
                                                    <td>
                                                        <?php
                                                        $status_configs = [
                                                            'pending' => ['class' => 'warning', 'text' => 'Chờ xác nhận'],
                                                            'confirmed' => ['class' => 'success', 'text' => 'Đã xác nhận'],
                                                            'completed' => ['class' => 'primary', 'text' => 'Hoàn thành'],
                                                            'cancelled' => ['class' => 'danger', 'text' => 'Đã hủy']
                                                        ];
                                                        $status_config = $status_configs[$appointment['status']] ?? ['class' => 'secondary', 'text' => 'Không xác định'];
                                                        ?>
                                                        <span class="badge bg-<?= $status_config['class'] ?>"><?= $status_config['text'] ?></span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Chưa có lịch hẹn nào</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Đơn hàng gần đây -->
                    <div class="card info-card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-shopping-cart me-2"></i>Đơn hàng gần đây
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Mã đơn</th>
                                                <th>Ngày đặt</th>
                                                <th>Số lượng</th>
                                                <th>Tổng tiền</th>
                                                <th>Trạng thái</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                                <tr>
                                                    <td>#<?= $order['order_id'] ?></td>
                                                    <td><?= date('d/m/Y', strtotime($order['order_date'])) ?></td>
                                                    <td><?= $order['item_count'] ?> sản phẩm</td>
                                                    <td><?= number_format($order['total']) ?>₫</td>
                                                    <td>
                                                        <?php
                                                        $order_status_configs = [
                                                            'pending' => ['class' => 'warning', 'text' => 'Chờ xử lý'],
                                                            'confirmed' => ['class' => 'info', 'text' => 'Đã xác nhận'],
                                                            'shipping' => ['class' => 'primary', 'text' => 'Đang giao'],
                                                            'completed' => ['class' => 'success', 'text' => 'Hoàn thành'],
                                                            'cancelled' => ['class' => 'danger', 'text' => 'Đã hủy']
                                                        ];
                                                        $order_status_config = $order_status_configs[$order['status']] ?? ['class' => 'secondary', 'text' => 'Không xác định'];
                                                        ?>
                                                        <span class="badge bg-<?= $order_status_config['class'] ?>"><?= $order_status_config['text'] ?></span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Chưa có đơn hàng nào</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Thống kê -->
                    <div class="row mb-4">
                        <div class="col-12 mb-3">
                            <div class="stats-card">
                                <div class="h4 mb-0"><?= number_format($stats['total_appointments']) ?></div>
                                <small>Tổng lịch hẹn</small>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="stats-card success">
                                <div class="h4 mb-0"><?= number_format($stats['completed_appointments']) ?></div>
                                <small>Lịch hẹn hoàn thành</small>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="stats-card warning">
                                <div class="h4 mb-0"><?= number_format($stats['total_orders']) ?></div>
                                <small>Tổng đơn hàng</small>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="stats-card info">
                                <div class="h4 mb-0"><?= number_format($stats['total_spent']) ?>₫</div>
                                <small>Tổng chi tiêu</small>
                            </div>
                        </div>
                    </div>

                    <!-- Cập nhật trạng thái -->
                    <div class="card info-card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-edit me-2"></i>Cập nhật trạng thái
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Trạng thái tài khoản</label>
                                    <select class="form-select" name="status" required>
                                        <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>>
                                            Hoạt động
                                        </option>
                                        <option value="inactive" <?= $user['status'] == 'inactive' ? 'selected' : '' ?>>
                                            Không hoạt động
                                        </option>
                                        <option value="suspended" <?= $user['status'] == 'suspended' ? 'selected' : '' ?>>
                                            Bị khóa
                                        </option>
                                    </select>
                                </div>
                                <button type="submit" name="update_status" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i>Cập nhật
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Thao tác nhanh -->
                    <div class="card info-card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-tools me-2"></i>Thao tác nhanh
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="user-edit.php?id=<?= $user_id ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-edit me-2"></i>Chỉnh sửa thông tin
                                </a>
                                <a href="mailto:<?= htmlspecialchars($user['email']) ?>" class="btn btn-outline-info">
                                    <i class="fas fa-envelope me-2"></i>Gửi email
                                </a>
                                <?php if ($user['phone_number']): ?>
                                    <a href="tel:<?= htmlspecialchars($user['phone_number']) ?>" class="btn btn-outline-success">
                                        <i class="fas fa-phone me-2"></i>Gọi điện
                                    </a>
                                <?php endif; ?>
                                <button class="btn btn-outline-secondary" onclick="window.print()">
                                    <i class="fas fa-print me-2"></i>In thông tin
                                </button>
                                <?php if ($user_id != $_SESSION['user_id']): ?>
                                    <a href="users.php?action=delete&id=<?= $user_id ?>" 
                                       class="btn btn-outline-danger"
                                       onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng này?')">
                                        <i class="fas fa-trash me-2"></i>Xóa tài khoản
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html> 