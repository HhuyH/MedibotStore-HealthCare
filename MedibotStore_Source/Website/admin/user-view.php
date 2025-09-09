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

    // Xử lý cập nhật thông tin cá nhân
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
        try {
            // Update email in users table first
            if (!empty($_POST['email']) && $_POST['email'] !== $user['email']) {
                // Check if email already exists
                $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
                $check_email->bind_param('si', $_POST['email'], $user_id);
                $check_email->execute();
                $result = $check_email->get_result();
                
                if ($result->num_rows > 0) {
                    throw new Exception("Email đã được sử dụng bởi tài khoản khác!");
                }
                
                // Update email
                $update_email = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                $update_email->bind_param('si', $_POST['email'], $user_id);
                if (!$update_email->execute()) {
                    throw new Exception("Không thể cập nhật email!");
                }
                $user['email'] = $_POST['email'];
            }

            // Kiểm tra xem đã có record trong users_info chưa
            $check_sql = "SELECT id FROM users_info WHERE user_id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Update existing record
                $update_sql = "UPDATE users_info SET 
                              full_name = ?,
                              gender = ?,
                              date_of_birth = ?,
                              phone = ?,
                              updated_at = NOW()
                              WHERE user_id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param('ssssi', 
                    $_POST['full_name'],
                    $_POST['gender'],
                    $_POST['date_of_birth'],
                    $_POST['phone'],
                    $user_id
                );
            } else {
                // Insert new record
                $insert_sql = "INSERT INTO users_info 
                              (user_id, full_name, gender, date_of_birth, phone, created_at, updated_at)
                              VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param('issss',
                    $user_id,
                    $_POST['full_name'],
                    $_POST['gender'],
                    $_POST['date_of_birth'],
                    $_POST['phone']
                );
            }

            if ($stmt->execute()) {
                $success_message = "Cập nhật thông tin thành công!";
                // Refresh user_info
                $result = $conn->query("SELECT * FROM users_info WHERE user_id = $user_id");
                if ($result && $result->num_rows > 0) {
                    $user_info = $result->fetch_assoc();
                }
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            $error_message = "Lỗi cập nhật thông tin: " . $e->getMessage();
        }
    }

    // Thêm query lấy thông tin bác sĩ nếu là role doctor
    $doctor_info = null;
    $schedules = [];
    $off_days = [];

    if ($role && $role['role_name'] === 'doctor') {
        try {
            // Debug log
            error_log("Fetching doctor info for user_id: " . $user_id);
            
            // Lấy doctor_id từ bảng doctors
            $doctor_sql = "SELECT d.doctor_id, d.user_id, d.specialty_id, d.clinic_id,
                             s.name as specialty_name, 
                             c.name as clinic_name,
                             c.address as clinic_address
                      FROM doctors d
                      LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
                      LEFT JOIN clinics c ON d.clinic_id = c.clinic_id
                      WHERE d.user_id = ?";
        
            $stmt = $conn->prepare($doctor_sql);
            if (!$stmt) {
                error_log("Prepare failed: " . $conn->error);
                throw new Exception("Database prepare failed");
            }

            $stmt->bind_param('i', $user_id);
            if (!$stmt->execute()) {
                error_log("Execute failed: " . $stmt->error);
                throw new Exception("Database execute failed");
            }

            $result = $stmt->get_result();
            $doctor_info = $result->fetch_assoc();
            
            // Debug log
            error_log("Doctor info found: " . print_r($doctor_info, true));

            // Lấy lịch làm việc nếu có doctor_id
            if ($doctor_info && isset($doctor_info['doctor_id'])) {
                $schedule_sql = "SELECT * FROM doctor_schedules 
                               WHERE doctor_id = ? 
                               ORDER BY day_of_week, start_time";
                $stmt = $conn->prepare($schedule_sql);
                if (!$stmt) {
                    error_log("Prepare schedule query failed: " . $conn->error);
                    throw new Exception("Database prepare failed");
                }

                $stmt->bind_param('i', $doctor_info['doctor_id']);
                if (!$stmt->execute()) {
                    error_log("Execute schedule query failed: " . $stmt->error);
                    throw new Exception("Database execute failed");
                }

                $result = $stmt->get_result();
                $schedules = $result->fetch_all(MYSQLI_ASSOC);
                
                // Debug log
                error_log("Schedules found for doctor_id " . $doctor_info['doctor_id'] . ": " . count($schedules));
                error_log("Schedule data: " . print_r($schedules, true));

                // Lấy ngày nghỉ
                $off_days_sql = "SELECT * FROM doctor_off_days 
                               WHERE doctor_id = ? 
                               AND off_date >= CURDATE()
                               ORDER BY off_date";
                $stmt = $conn->prepare($off_days_sql);
                $stmt->bind_param('i', $doctor_info['doctor_id']);
                $stmt->execute();
                $off_days = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } else {
                error_log("No doctor_id found for user_id: " . $user_id);
            }
        } catch (Exception $e) {
            error_log("Error fetching doctor info: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
        }
    }

    // Xử lý cập nhật lịch làm việc
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_schedule'])) {
            try {
                // Xóa lịch cũ
                $delete_sql = "DELETE FROM doctor_schedules WHERE doctor_id = ?";
                $stmt = $conn->prepare($delete_sql);
                $stmt->bind_param('i', $doctor_info['doctor_id']);
                $stmt->execute();

                // Thêm lịch mới
                $insert_sql = "INSERT INTO doctor_schedules (doctor_id, clinic_id, day_of_week, start_time, end_time) 
                              VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_sql);

                foreach ($_POST['schedule'] as $schedule) {
                    if (!empty($schedule['start_time']) && !empty($schedule['end_time'])) {
                        $stmt->bind_param('iiiss', 
                            $doctor_info['doctor_id'],
                            $doctor_info['clinic_id'],
                            $schedule['day'],
                            $schedule['start_time'],
                            $schedule['end_time']
                        );
                        $stmt->execute();
                    }
                }
                $success_message = "Cập nhật lịch làm việc thành công!";
            } catch (Exception $e) {
                $error_message = "Lỗi cập nhật lịch làm việc: " . $e->getMessage();
            }
        }

        if (isset($_POST['add_off_day'])) {
            try {
                $off_date = $_POST['off_date'];
                $reason = $_POST['reason'];

                $insert_sql = "INSERT INTO doctor_off_days (doctor_id, off_date, reason) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param('iss', $doctor_info['doctor_id'], $off_date, $reason);
                
                if ($stmt->execute()) {
                    $success_message = "Thêm ngày nghỉ thành công!";
                } else {
                    throw new Exception($stmt->error);
                }
            } catch (Exception $e) {
                $error_message = "Lỗi thêm ngày nghỉ: " . $e->getMessage();
            }
        }

        if (isset($_POST['delete_off_day'])) {
            try {
                $off_day_id = (int)$_POST['off_day_id'];
                
                $delete_sql = "DELETE FROM doctor_off_days WHERE id = ? AND doctor_id = ?";
                $stmt = $conn->prepare($delete_sql);
                $stmt->bind_param('ii', $off_day_id, $doctor_info['doctor_id']);
                
                if ($stmt->execute()) {
                    $success_message = "Xóa ngày nghỉ thành công!";
                } else {
                    throw new Exception($stmt->error);
                }
            } catch (Exception $e) {
                $error_message = "Lỗi xóa ngày nghỉ: " . $e->getMessage();
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
        <title>Chi tiết người dùng - <?= htmlspecialchars($user['username']) ?> - MediBot Store Admin</title>
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
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user-edit me-2"></i>
                                    Thông tin cá nhân
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Họ và tên</label>
                                        <input type="text" name="full_name" class="form-control" 
                                               value="<?= htmlspecialchars($user_info['full_name'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Số điện thoại</label>
                                        <input type="tel" name="phone" class="form-control" 
                                               pattern="[0-9]{10,15}"
                                               value="<?= htmlspecialchars($user_info['phone'] ?? '') ?>"
                                               title="Số điện thoại từ 10-15 số">
                                            </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Giới tính</label>
                                        <select name="gender" class="form-select">
                                            <option value="">-- Chọn giới tính --</option>
                                            <option value="Nam" <?= ($user_info['gender'] ?? '') === 'Nam' ? 'selected' : '' ?>>Nam</option>
                                            <option value="Nữ" <?= ($user_info['gender'] ?? '') === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                                            <option value="Khác" <?= ($user_info['gender'] ?? '') === 'Khác' ? 'selected' : '' ?>>Khác</option>
                                        </select>
                                        </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Ngày sinh</label>
                                        <input type="date" name="date_of_birth" class="form-control"
                                               value="<?= $user_info['date_of_birth'] ?? '' ?>">
                                            </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Ngày tạo tài khoản</label>
                                        <input type="text" class="form-control" 
                                               value="<?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>" readonly>
                                        </div>
                                    
                                    <div class="col-12">
                                        <button type="submit" name="update_info" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>
                                            Cập nhật thông tin
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <?php if ($role && $role['role_name'] === 'doctor' && $doctor_info): ?>
                        <!-- Thông tin bác sĩ -->
                        <div class="card info-card mb-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user-md me-2"></i>
                                    Thông tin bác sĩ
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($doctor_info): ?>
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <p><strong>Chuyên khoa:</strong> <?= htmlspecialchars($doctor_info['specialty_name'] ?? 'Chưa phân chuyên khoa') ?></p>
                                        <p><strong>Phòng khám:</strong> <?= htmlspecialchars($doctor_info['clinic_name'] ?? 'Chưa phân phòng khám') ?></p>
                                    </div>
                                </div>
                                                <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Chưa có thông tin bác sĩ. Vui lòng liên hệ quản trị viên để cập nhật.
                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                        <!-- Lịch làm việc hiện tại -->
                        <div class="card info-card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-calendar-check me-2"></i>
                                    Lịch làm việc hiện tại
                                </h5>
                                        </div>
                            <div class="card-body">
                                <?php if (!empty($schedules)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Thứ</th>
                                                    <th>Giờ bắt đầu</th>
                                                    <th>Giờ kết thúc</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $days = [
                                                    1 => 'Thứ 2',
                                                    2 => 'Thứ 3',
                                                    3 => 'Thứ 4',
                                                    4 => 'Thứ 5',
                                                    5 => 'Thứ 6',
                                                    6 => 'Thứ 7',
                                                    7 => 'Chủ nhật'
                                                ];
                                                foreach ($schedules as $schedule): ?>
                                                    <tr>
                                                        <td><?= $days[$schedule['day_of_week']] ?></td>
                                                        <td><?= date('H:i', strtotime($schedule['start_time'])) ?></td>
                                                        <td><?= date('H:i', strtotime($schedule['end_time'])) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">Chưa có lịch làm việc nào được thiết lập</p>
                                <?php endif; ?>

                                <button type="button" class="btn btn-primary mt-3" data-bs-toggle="collapse" data-bs-target="#scheduleForm">
                                    <i class="fas fa-edit me-2"></i>Cập nhật lịch làm việc
                                </button>

                                <div id="scheduleForm" class="collapse mt-3">
                                    <form method="POST" class="border rounded p-3 bg-light">
                                        <h6 class="mb-3">Thiết lập lịch làm việc</h6>
                                        <?php foreach ($days as $day_num => $day_name):
                                            $day_schedule = array_filter($schedules ?? [], function($s) use ($day_num) {
                                                return $s['day_of_week'] == $day_num;
                                            });
                                            $day_schedule = reset($day_schedule); // Lấy phần tử đầu tiên hoặc false
                                        ?>
                                            <div class="row mb-3 align-items-center">
                                                <div class="col-md-3">
                                                    <label class="form-label fw-bold"><?= $day_name ?></label>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                                        <input type="time" name="schedule[<?= $day_num ?>][start_time]" 
                                                               class="form-control" 
                                                               value="<?= $day_schedule ? $day_schedule['start_time'] : '' ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                                        <input type="time" name="schedule[<?= $day_num ?>][end_time]" 
                                                               class="form-control"
                                                               value="<?= $day_schedule ? $day_schedule['end_time'] : '' ?>">
                                                    </div>
                                                </div>
                                                <input type="hidden" name="schedule[<?= $day_num ?>][day]" value="<?= $day_num ?>">
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <button type="submit" name="update_schedule" class="btn btn-success">
                                            <i class="fas fa-save me-2"></i>Lưu lịch làm việc
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Ngày nghỉ -->
                        <div class="card info-card mb-4">
                            <div class="card-header bg-warning">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-calendar-minus me-2"></i>
                                    Quản lý ngày nghỉ
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Form thêm ngày nghỉ -->
                                <form method="POST" class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label">Ngày nghỉ</label>
                                        <input type="date" name="off_date" class="form-control" 
                                               min="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Lý do nghỉ</label>
                                        <input type="text" name="reason" class="form-control" 
                                               placeholder="Nhập lý do nghỉ" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" name="add_off_day" class="btn btn-primary w-100">
                                            <i class="fas fa-plus me-2"></i>Thêm
                                        </button>
                                    </div>
                                </form>

                                <!-- Danh sách ngày nghỉ -->
                                <?php if (!empty($off_days)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Ngày nghỉ</th>
                                                    <th>Lý do</th>
                                                    <th>Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($off_days as $off_day): ?>
                                                    <tr>
                                                        <td><?= date('d/m/Y', strtotime($off_day['off_date'])) ?></td>
                                                        <td><?= htmlspecialchars($off_day['reason']) ?></td>
                                                        <td>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="off_day_id" value="<?= $off_day['id'] ?>">
                                                                <button type="submit" name="delete_off_day" 
                                                                        class="btn btn-danger btn-sm"
                                                                        onclick="return confirm('Bạn có chắc muốn xóa ngày nghỉ này?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">Chưa có ngày nghỉ nào được thiết lập</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

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
                        <div class="card info-card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-tools me-2"></i>
                                    Thao tác nhanh
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editInfoModal">
                                        <i class="fas fa-edit me-2"></i>
                                        Chỉnh sửa thông tin
                                    </button>
                                    
                                    <?php if (!empty($user['email'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($user['email']) ?>" class="btn btn-info text-white">
                                        <i class="fas fa-envelope me-2"></i>
                                        Gửi email
                                    </a>
                                    <?php endif; ?>

                                    <?php if (!empty($user_info['phone'])): ?>
                                    <a href="tel:<?= htmlspecialchars($user_info['phone']) ?>" class="btn btn-success">
                                        <i class="fas fa-phone me-2"></i>
                                        Gọi điện
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($role && $role['role_name'] === 'doctor'): ?>
                                    <button type="button" class="btn btn-warning" data-bs-toggle="collapse" data-bs-target="#scheduleForm">
                                        <i class="fas fa-calendar-alt me-2"></i>
                                        Quản lý lịch làm việc
                                    </button>
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