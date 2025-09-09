<?php
session_start();
require_once '../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../login.php');
    exit();
}

// Get selected doctor and day filter
$selected_doctor = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;
$selected_day = isset($_GET['day_filter']) ? (int)$_GET['day_filter'] : null;

// Get all doctors
try {
    $doctors_query = "SELECT d.doctor_id, u.username as doctor_name, s.name as specialty_name, c.name as clinic_name
                    FROM doctors d
                    JOIN users u ON d.user_id = u.user_id
                    LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
                    LEFT JOIN clinics c ON d.clinic_id = c.clinic_id
                    ORDER BY u.username";
    
    // Debug query
    echo "<!-- Doctors Query: " . htmlspecialchars($doctors_query) . " -->";
    
    $doctors_result = $conn->query($doctors_query);
    if ($doctors_result === false) {
        throw new Exception("Query failed: " . $conn->error);
    }
    $doctors = $doctors_result->fetch_all(MYSQLI_ASSOC);
    
    // Debug results
    echo "<!-- Found " . count($doctors) . " doctors -->";
    echo "<!-- Doctors data: " . htmlspecialchars(print_r($doctors, true)) . " -->";
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error fetching doctors: ' . htmlspecialchars($e->getMessage()) . '</div>';
    error_log("Error fetching doctors: " . $e->getMessage());
    $doctors = [];
}

// Get schedules
try {
    $schedule_query = "SELECT ds.*, u.username as doctor_name, s.name as specialty_name, c.name as clinic_name
                    FROM doctor_schedules ds
                    JOIN doctors d ON ds.doctor_id = d.doctor_id
                    JOIN users u ON d.user_id = u.user_id
                    LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
                    LEFT JOIN clinics c ON d.clinic_id = c.clinic_id
                    WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($selected_doctor) {
        $schedule_query .= " AND ds.doctor_id = ?";
        $params[] = $selected_doctor;
        $types .= "i";
    }
    
    if ($selected_day) {
        $schedule_query .= " AND ds.day_of_week = ?";
        $params[] = $selected_day;
        $types .= "i";
    }
    
    $schedule_query .= " ORDER BY u.username, ds.day_of_week, ds.start_time";

    $stmt = $conn->prepare($schedule_query);
    if ($stmt && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt) {
        $stmt->execute();
        $schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Failed to prepare statement");
    }
} catch (Exception $e) {
    error_log("Error fetching schedules: " . $e->getMessage());
    $schedules = [];
}

// Get off days
try {
    $off_days_query = "SELECT do.*, u.username as doctor_name
                    FROM doctor_off_days do
                    JOIN doctors d ON do.doctor_id = d.doctor_id
                    JOIN users u ON d.user_id = u.user_id
                    WHERE off_date >= CURDATE()";
    if ($selected_doctor) {
        $off_days_query .= " AND do.doctor_id = " . (int)$selected_doctor;
    }
    $off_days_query .= " ORDER BY off_date";

    // Debug query
    echo "<!-- Off Days Query: " . htmlspecialchars($off_days_query) . " -->";

    $off_days_result = $conn->query($off_days_query);
    if ($off_days_result === false) {
        throw new Exception("Query failed: " . $conn->error);
    }
    $off_days = $off_days_result->fetch_all(MYSQLI_ASSOC);

    // Debug results
    echo "<!-- Found " . count($off_days) . " off days -->";
    echo "<!-- Off days data: " . htmlspecialchars(print_r($off_days, true)) . " -->";

} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error fetching off days: ' . htmlspecialchars($e->getMessage()) . '</div>';
    error_log("Error fetching off days: " . $e->getMessage());
    $off_days = [];
}

// Helper function to convert day number to name
function getDayName($day) {
    $days = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'];
    return $days[$day - 1];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý lịch trực bác sĩ - MediSync Admin</title>
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
                    <h1 class="h3 mb-0 text-gray-800">Quản lý lịch trực bác sĩ</h1>
                    <p class="mb-0 text-muted">Quản lý lịch làm việc và ngày nghỉ của bác sĩ</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                        <i class="fas fa-plus me-2"></i>Thêm lịch trực
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Chọn bác sĩ</label>
                            <select name="doctor_id" class="form-select">
                                <option value="">Tất cả bác sĩ</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?= $doctor['doctor_id'] ?>" 
                                            <?= $selected_doctor == $doctor['doctor_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($doctor['doctor_name']) ?> 
                                        (<?= htmlspecialchars($doctor['specialty_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Thứ trong tuần</label>
                            <select name="day_filter" class="form-select">
                                <option value="">Tất cả các ngày</option>
                                <?php for ($i = 1; $i <= 7; $i++): ?>
                                    <option value="<?= $i ?>" <?= isset($_GET['day_filter']) && $_GET['day_filter'] == $i ? 'selected' : '' ?>>
                                        <?= getDayName($i) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Tìm kiếm
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <a href="doctor-schedules.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo me-2"></i>Đặt lại
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Schedules Table -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="m-0 font-weight-bold text-primary">
                                Lịch làm việc
                                <span class="badge bg-primary ms-2"><?= count($schedules) ?></span>
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
                                    <th style="width: 40px" class="text-center">
                                        <i class="fas fa-user-md"></i>
                                    </th>
                                    <th>Bác sĩ</th>
                                    <th>Chuyên khoa</th>
                                    <th>Phòng khám</th>
                                    <th>Thứ</th>
                                    <th>Giờ bắt đầu</th>
                                    <th>Giờ kết thúc</th>
                                    <th>Trạng thái</th>
                                    <th style="width: 100px">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($schedules) > 0): ?>
                                    <?php foreach ($schedules as $schedule): ?>
                                        <tr>
                                            <td class="text-center">
                                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" 
                                                     style="width: 32px; height: 32px; font-weight: 600;">
                                                    <?= strtoupper(substr($schedule['doctor_name'], 0, 1)) ?>
                                                </div>
                                            </td>
                                            <td class="align-middle"><?= htmlspecialchars($schedule['doctor_name']) ?></td>
                                            <td class="align-middle"><?= htmlspecialchars($schedule['specialty_name']) ?></td>
                                            <td class="align-middle"><?= htmlspecialchars($schedule['clinic_name']) ?></td>
                                            <td class="align-middle"><?= getDayName($schedule['day_of_week']) ?></td>
                                            <td class="align-middle"><?= date('H:i', strtotime($schedule['start_time'])) ?></td>
                                            <td class="align-middle"><?= date('H:i', strtotime($schedule['end_time'])) ?></td>
                                            <td class="align-middle">
                                                <span class="badge rounded-pill <?= $schedule['is_available'] ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= $schedule['is_available'] ? 'Đang làm việc' : 'Tạm nghỉ' ?>
                                                </span>
                                            </td>
                                            <td class="align-middle">
                                                <button type="button" class="btn btn-outline-primary btn-sm" 
                                                        style="width: 32px; height: 32px; padding: 0; line-height: 30px;">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm ms-1" 
                                                        style="width: 32px; height: 32px; padding: 0; line-height: 30px;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-calendar-times fa-2x mb-3"></i>
                                                <p class="mb-0">Không có lịch trực nào</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Off Days Table -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="m-0 font-weight-bold text-primary">
                                Ngày nghỉ sắp tới
                                <span class="badge bg-primary ms-2"><?= count($off_days) ?></span>
                            </h6>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addOffDayModal">
                                <i class="fas fa-plus me-2"></i>Thêm ngày nghỉ
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bác sĩ</th>
                                    <th>Ngày nghỉ</th>
                                    <th>Lý do</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($off_days) > 0): ?>
                                    <?php foreach ($off_days as $off_day): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-3">
                                                        <div class="avatar-title bg-soft-primary text-primary rounded-circle">
                                                            <?= strtoupper(substr($off_day['doctor_name'], 0, 1)) ?>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($off_day['doctor_name']) ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($off_day['off_date'])) ?></td>
                                            <td><?= htmlspecialchars($off_day['reason'] ?? 'Không có lý do') ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" title="Chỉnh sửa">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" title="Xóa">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <i class="fas fa-calendar-check fa-2x mb-3 d-block"></i>
                                            Không có ngày nghỉ nào
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <style>
    .main-content {
        margin-left: 250px;
        padding: 20px;
    }
    .avatar-sm {
        width: 36px;
        height: 36px;
    }
    .avatar-title {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 500;
    }
    .bg-soft-primary {
        background-color: rgba(67, 94, 190, 0.1) !important;
    }
    .table th {
        white-space: nowrap;
    }
    .table td {
        vertical-align: middle;
    }
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    .badge {
        font-weight: 500;
        padding: 0.5em 0.7em;
    }
    .table th {
        font-weight: 600;
    }
    .table td {
        vertical-align: middle;
    }
    .badge {
        font-weight: 500;
    }
    .btn-sm {
        font-size: 14px;
    }
    .rounded-pill {
        padding: 0.5em 1em;
    }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/notifications.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html> 