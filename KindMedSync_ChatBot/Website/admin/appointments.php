<?php
session_start();
include '../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit;
}

// Xử lý các actions
$action = $_GET['action'] ?? 'list';
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Xử lý update status
if ($action == 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $appointment_id = (int)$_GET['id'];
    $new_status = $_GET['status'];
    
    $allowed_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (in_array($new_status, $allowed_statuses)) {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
        $stmt->bind_param("si", $new_status, $appointment_id);
        
        if ($stmt->execute()) {
            header('Location: appointments.php?success=status_updated');
        } else {
            header('Location: appointments.php?error=update_failed');
        }
        exit;
    }
}

// Xử lý delete appointment
if ($action == 'delete' && isset($_GET['id'])) {
    $appointment_id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
    $stmt->bind_param("i", $appointment_id);
    
    if ($stmt->execute()) {
        header('Location: appointments.php?success=deleted');
    } else {
        header('Location: appointments.php?error=delete_failed');
    }
    exit;
}

// Xây dựng query
$where_conditions = [];
$params = [];

if ($status_filter != 'all') {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(a.appointment_time) = ?";
    $params[] = $date_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(COALESCE(ui_patient.full_name, u_patient.username, gu.full_name) LIKE ? OR COALESCE(ui_doctor.full_name, u_doctor.username, u_doctor.email) LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Count total
$count_sql = "SELECT COUNT(*) as total 
              FROM appointments a
              LEFT JOIN users u_patient ON a.user_id = u_patient.user_id
              LEFT JOIN users_info ui_patient ON a.user_id = ui_patient.user_id
              LEFT JOIN guest_users gu ON a.guest_id = gu.guest_id
              LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
              LEFT JOIN users u_doctor ON d.user_id = u_doctor.user_id
              LEFT JOIN users_info ui_doctor ON d.user_id = ui_doctor.user_id
              LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
              $where_clause";

try {
    if (!empty($params)) {
        $count_stmt = $conn->prepare($count_sql);
        if ($count_stmt) {
            $count_stmt->bind_param(str_repeat('s', count($params)), ...$params);
            $count_stmt->execute();
            $result = $count_stmt->get_result();
            $total_records = $result ? $result->fetch_assoc()['total'] : 0;
        } else {
            $total_records = 0;
        }
    } else {
        $result = $conn->query($count_sql);
        $total_records = $result ? $result->fetch_assoc()['total'] : 0;
    }
} catch (Exception $e) {
    $total_records = 0;
}

$total_pages = ceil($total_records / $limit);

// Get appointments
$sql = "SELECT a.*, 
               COALESCE(ui_patient.full_name, u_patient.username, gu.full_name) as patient_name,
               COALESCE(ui_patient.phone, gu.phone) as patient_phone,
               COALESCE(ui_doctor.full_name, u_doctor.username, u_doctor.email) as doctor_name,
               c.name as clinic_name
        FROM appointments a
        LEFT JOIN users u_patient ON a.user_id = u_patient.user_id
        LEFT JOIN users_info ui_patient ON a.user_id = ui_patient.user_id
        LEFT JOIN guest_users gu ON a.guest_id = gu.guest_id
        LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
        LEFT JOIN users u_doctor ON d.user_id = u_doctor.user_id
        LEFT JOIN users_info ui_doctor ON d.user_id = ui_doctor.user_id
        LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
        $where_clause
        ORDER BY a.appointment_time DESC
        LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;

try {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params) - 2) . 'ii', ...$params);
        }
        $stmt->execute();
        $appointments = $stmt->get_result();
    } else {
        $appointments = false;
    }
} catch (Exception $e) {
    $appointments = false;
}

// Thống kê nhanh
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN DATE(appointment_time) = CURDATE() THEN 1 ELSE 0 END) as today
FROM appointments";

try {
    $stats_result = $conn->query($stats_sql);
    $stats = $stats_result ? $stats_result->fetch_assoc() : [
        'total' => 0, 'pending' => 0, 'confirmed' => 0, 
        'completed' => 0, 'cancelled' => 0, 'today' => 0
    ];
} catch (Exception $e) {
    $stats = [
        'total' => 0, 'pending' => 0, 'confirmed' => 0, 
        'completed' => 0, 'cancelled' => 0, 'today' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý lịch hẹn - MediBot Store Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="assets/css/sidebar.css" rel="stylesheet">
    <link href="assets/css/header.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/headeradmin.php'; ?>
    <?php include 'includes/sidebaradmin.php'; ?>

    <main class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Quản lý lịch hẹn</h1>
                    <p class="mb-0 text-muted">Quản lý và theo dõi tất cả lịch hẹn</p>
                </div>
                <div>
                    <a href="appointment-add.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Thêm lịch hẹn
                    </a>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php
                    switch($success) {
                        case 'status_updated': echo 'Cập nhật trạng thái thành công!'; break;
                        case 'deleted': echo 'Xóa lịch hẹn thành công!'; break;
                        default: echo 'Thao tác thành công!';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php
                    switch($error) {
                        case 'update_failed': echo 'Cập nhật trạng thái thất bại!'; break;
                        case 'delete_failed': echo 'Xóa lịch hẹn thất bại!'; break;
                        default: echo 'Có lỗi xảy ra!';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-primary mb-2">
                                <i class="fas fa-calendar-alt fa-2x"></i>
                            </div>
                            <h5 class="mb-0"><?= number_format($stats['total']) ?></h5>
                            <small class="text-muted">Tổng lịch hẹn</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-warning mb-2">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                            <h5 class="mb-0"><?= number_format($stats['pending']) ?></h5>
                            <small class="text-muted">Chờ xác nhận</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-success mb-2">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                            <h5 class="mb-0"><?= number_format($stats['confirmed']) ?></h5>
                            <small class="text-muted">Đã xác nhận</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-primary mb-2">
                                <i class="fas fa-check-double fa-2x"></i>
                            </div>
                            <h5 class="mb-0"><?= number_format($stats['completed']) ?></h5>
                            <small class="text-muted">Hoàn thành</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-danger mb-2">
                                <i class="fas fa-times-circle fa-2x"></i>
                            </div>
                            <h5 class="mb-0"><?= number_format($stats['cancelled']) ?></h5>
                            <small class="text-muted">Đã hủy</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="text-info mb-2">
                                <i class="fas fa-calendar-day fa-2x"></i>
                            </div>
                            <h5 class="mb-0"><?= number_format($stats['today']) ?></h5>
                            <small class="text-muted">Hôm nay</small>
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
                            <input type="text" class="form-control" name="search" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Tên bệnh nhân, bác sĩ, dịch vụ...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Trạng thái</label>
                            <select class="form-select" name="status">
                                <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>Tất cả</option>
                                <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Chờ xác nhận</option>
                                <option value="confirmed" <?= $status_filter == 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                                <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                                <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Ngày hẹn</label>
                            <input type="date" class="form-control" name="date" value="<?= htmlspecialchars($date_filter) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Tìm kiếm
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <a href="appointments.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo me-2"></i>Đặt lại
                                </a>
                                <button type="button" class="btn btn-outline-info" onclick="location.reload()">
                                    <i class="fas fa-sync me-2"></i>Làm mới
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Appointments Table -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="m-0 font-weight-bold text-primary">
                                Danh sách lịch hẹn 
                                <span class="badge bg-primary ms-2"><?= number_format($total_records) ?></span>
                            </h6>
                        </div>
                        <div class="col-auto">
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                                        data-bs-toggle="dropdown">
                                    <i class="fas fa-download me-2"></i>Xuất dữ liệu
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-file-excel me-2"></i>Excel</a></li>
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-file-pdf me-2"></i>PDF</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">Bệnh nhân</th>
                                    <th width="15%">Bác sĩ</th>
                                    <th width="15%">Phòng khám</th>
                                    <th width="15%">Ngày giờ hẹn</th>
                                    <th width="10%">Trạng thái</th>
                                    <th width="10%">Lý do</th>
                                    <th width="15%">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($appointments && $appointments->num_rows > 0): ?>
                                    <?php $i = $offset + 1; ?>
                                    <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td>
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($appointment['patient_name']) ?></h6>
                                                    <?php if ($appointment['patient_phone']): ?>
                                                        <small class="text-muted">
                                                            <i class="fas fa-phone me-1"></i><?= htmlspecialchars($appointment['patient_phone']) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <h6 class="mb-0"><?= htmlspecialchars($appointment['doctor_name']) ?></h6>
                                            </td>
                                            <td>
                                                <h6 class="mb-0"><?= htmlspecialchars($appointment['clinic_name'] ?? 'Chưa chọn') ?></h6>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= date('d/m/Y', strtotime($appointment['appointment_time'])) ?></strong>
                                                </div>
                                                <small class="text-muted"><?= date('H:i', strtotime($appointment['appointment_time'])) ?></small>
                                            </td>
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
                                            <td>
                                                <?php if ($appointment['reason']): ?>
                                                    <small><?= htmlspecialchars(substr($appointment['reason'], 0, 50)) ?><?= strlen($appointment['reason']) > 50 ? '...' : '' ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">Không có</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <!-- Quick Status Actions -->
                                                    <?php if ($appointment['status'] == 'pending'): ?>
                                                        <button class="btn btn-success btn-sm" 
                                                                onclick="updateStatus(<?= $appointment['appointment_id'] ?>, 'confirmed')"
                                                                data-bs-toggle="tooltip" title="Xác nhận">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-danger btn-sm" 
                                                                onclick="updateStatus(<?= $appointment['appointment_id'] ?>, 'cancelled')"
                                                                data-bs-toggle="tooltip" title="Hủy bỏ">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php elseif ($appointment['status'] == 'confirmed'): ?>
                                                        <button class="btn btn-primary btn-sm" 
                                                                onclick="updateStatus(<?= $appointment['appointment_id'] ?>, 'completed')"
                                                                data-bs-toggle="tooltip" title="Hoàn thành">
                                                            <i class="fas fa-check-double"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <!-- View Button -->
                                                    <a href="appointment-view.php?id=<?= $appointment['appointment_id'] ?>" 
                                                       class="btn btn-info btn-sm"
                                                       data-bs-toggle="tooltip" title="Xem chi tiết">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <!-- More Actions Dropdown -->
                                                    <div class="dropdown">
                                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                                                type="button" data-bs-toggle="dropdown"
                                                                data-bs-toggle="tooltip" title="Thêm thao tác">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="appointment-edit.php?id=<?= $appointment['appointment_id'] ?>">
                                                                <i class="fas fa-edit text-primary me-2"></i>Chỉnh sửa
                                                            </a></li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <?php if ($appointment['status'] != 'cancelled'): ?>
                                                                <li><a class="dropdown-item text-warning" 
                                                                       href="?action=update_status&id=<?= $appointment['appointment_id'] ?>&status=cancelled"
                                                                       onclick="return confirm('Bạn có chắc chắn muốn hủy lịch hẹn này?')">
                                                                    <i class="fas fa-ban me-2"></i>Hủy lịch hẹn
                                                                </a></li>
                                                            <?php endif; ?>
                                                            <li><a class="dropdown-item text-danger" 
                                                                   href="?action=delete&id=<?= $appointment['appointment_id'] ?>"
                                                                   onclick="return confirm('Bạn có chắc chắn muốn xóa lịch hẹn này? Hành động này không thể hoàn tác!')">
                                                                <i class="fas fa-trash me-2"></i>Xóa vĩnh viễn
                                                            </a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">Không có lịch hẹn nào</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer bg-white">
                        <div class="row align-items-center">
                            <div class="col">
                                <small class="text-muted">
                                    Hiển thị <?= min($offset + 1, $total_records) ?> - <?= min($offset + $limit, $total_records) ?> 
                                    trong tổng số <?= number_format($total_records) ?> bản ghi
                                </small>
                            </div>
                            <div class="col-auto">
                                <nav>
                                    <ul class="pagination pagination-sm mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&date=<?= $date_filter ?>&search=<?= urlencode($search) ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);
                                        ?>

                                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>&date=<?= $date_filter ?>&search=<?= urlencode($search) ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&date=<?= $date_filter ?>&search=<?= urlencode($search) ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/notifications.js"></script>
    <script src="assets/js/admin.js"></script>
    
    <style>
        .btn-group .btn {
            border-radius: 6px !important;
            margin-right: 2px;
        }
        
        .gap-1 {
            gap: 0.25rem !important;
        }
        
        .dropdown-menu-end {
            --bs-position: end;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .badge {
            font-size: 0.75em;
            padding: 0.35em 0.65em;
        }
        
        .btn-sm {
            --bs-btn-padding-y: 0.25rem;
            --bs-btn-padding-x: 0.5rem;
            --bs-btn-font-size: 0.875rem;
        }
        
        .dropdown-toggle::after {
            display: none;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }
    </style>
    
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Update appointment status function
        function updateStatus(appointmentId, status) {
            const statusText = {
                'confirmed': 'xác nhận',
                'cancelled': 'hủy bỏ', 
                'completed': 'hoàn thành'
            };
            
            if (confirm(`Bạn có chắc chắn muốn ${statusText[status]} lịch hẹn này?`)) {
                // Show loading
                const btn = event.target.closest('button');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;
                
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'GET';
                form.innerHTML = `
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" value="${appointmentId}">
                    <input type="hidden" name="status" value="${status}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Auto refresh every 30 seconds
        setInterval(function() {
            // Only refresh if no modal is open
            if (!document.querySelector('.modal.show')) {
                const url = new URL(window.location);
                url.searchParams.set('auto_refresh', '1');
                if (!url.searchParams.get('search') && !url.searchParams.get('status') && !url.searchParams.get('date')) {
                    window.location.reload();
                }
            }
        }, 30000);
    </script>
</body>
</html> 