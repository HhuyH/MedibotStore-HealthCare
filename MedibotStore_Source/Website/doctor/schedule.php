<?php
session_start();
require_once '../includes/db.php';

// Kiểm tra đăng nhập và quyền bác sĩ
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [2, 3])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$current_date = date('Y-m-d');

try {
    // Kiểm tra kết nối
    if (!$conn) {
        throw new Exception("Không thể kết nối đến cơ sở dữ liệu");
    }

    // Debug user_id
    error_log("User ID: " . $user_id);

    // Truy vấn đơn giản nhất để lấy doctor_id
    $doctor_query = "SELECT doctor_id FROM doctors WHERE user_id = ?";
    
    error_log("Doctor Query: " . str_replace('?', $user_id, $doctor_query));
    
    $stmt = $conn->prepare($doctor_query);
    if ($stmt === false) {
        throw new Exception("Lỗi chuẩn bị truy vấn doctor: " . $conn->error);
    }

    if (!$stmt->bind_param('i', $user_id)) {
        throw new Exception("Lỗi bind tham số doctor: " . $stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception("Lỗi thực thi truy vấn doctor: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Lỗi lấy kết quả doctor: " . $conn->error);
    }

    $doctor_info = $result->fetch_assoc();
    if (!$doctor_info) {
        throw new Exception("Không tìm thấy thông tin bác sĩ cho user_id: " . $user_id);
    }

    // Debug doctor info
    error_log("Doctor Info: " . print_r($doctor_info, true));

    // Truy vấn đơn giản nhất để lấy lịch làm việc
    $schedule_query = "SELECT * FROM doctor_schedules WHERE doctor_id = ?";
    
    error_log("Schedule Query: " . str_replace('?', $doctor_info['doctor_id'], $schedule_query));
    
    $stmt = $conn->prepare($schedule_query);
    if ($stmt === false) {
        throw new Exception("Lỗi chuẩn bị truy vấn lịch: " . $conn->error . "\nQuery: " . $schedule_query);
    }

    if (!$stmt->bind_param('i', $doctor_info['doctor_id'])) {
        throw new Exception("Lỗi bind tham số lịch: " . $stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception("Lỗi thực thi truy vấn lịch: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Lỗi lấy kết quả lịch: " . $conn->error);
    }

    $schedules = $result->fetch_all(MYSQLI_ASSOC);
    
    // Debug schedules
    error_log("Schedules found: " . count($schedules));
    error_log("First schedule: " . print_r($schedules[0] ?? 'No schedules', true));

    // Truy vấn đơn giản nhất để lấy ngày nghỉ
    $off_days_query = "SELECT * FROM doctor_off_days WHERE doctor_id = ? AND off_date >= CURDATE()";
    
    error_log("Off Days Query: " . str_replace('?', $doctor_info['doctor_id'], $off_days_query));
    
    $stmt = $conn->prepare($off_days_query);
    if ($stmt === false) {
        throw new Exception("Lỗi chuẩn bị truy vấn ngày nghỉ: " . $conn->error);
    }

    if (!$stmt->bind_param('i', $doctor_info['doctor_id'])) {
        throw new Exception("Lỗi bind tham số ngày nghỉ: " . $stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception("Lỗi thực thi truy vấn ngày nghỉ: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Lỗi lấy kết quả ngày nghỉ: " . $conn->error);
    }

    $off_days = $result->fetch_all(MYSQLI_ASSOC);
    
    // Debug off days
    error_log("Off days found: " . count($off_days));
    error_log("First off day: " . print_r($off_days[0] ?? 'No off days', true));

    // Sau khi có dữ liệu cơ bản, lấy thêm thông tin bổ sung
    if ($doctor_info['doctor_id']) {
        $doctor_details_query = "SELECT d.*, u.username as doctor_name, s.name as specialty_name, 
                                      c.name as clinic_name, c.address as clinic_address
                               FROM doctors d
                               JOIN users u ON d.user_id = u.user_id
                               LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
                               LEFT JOIN clinics c ON d.clinic_id = c.clinic_id
                               WHERE d.doctor_id = ?";
        
        $stmt = $conn->prepare($doctor_details_query);
        if ($stmt && $stmt->bind_param('i', $doctor_info['doctor_id']) && $stmt->execute()) {
            $doctor_info = $stmt->get_result()->fetch_assoc();
        }
    }

} catch (Exception $e) {
    error_log("Error in schedule.php: " . $e->getMessage());
    die("Có lỗi xảy ra: " . htmlspecialchars($e->getMessage()));
}

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
    <title>Lịch làm việc - <?= htmlspecialchars($doctor_info['doctor_name'] ?? 'N/A') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* Remove modal backdrop */
        .modal-backdrop {
            display: none !important;
        }
        .modal {
            background: rgba(0, 0, 0, 0.2) !important;
        }
        /* Modern Background */
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* Container styling */
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin: 20px auto;
            padding: 2rem;
            backdrop-filter: blur(10px);
        }

        /* Doctor Navigation Bar */
        .doctor-nav {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border-radius: 15px;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            color: white;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }

        .doctor-nav h2 {
            color: white;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
            transform: translateY(-2px);
        }

        .nav-btn.active {
            background: rgba(255, 255, 255, 0.9);
            color: #4f46e5;
            border-color: white;
        }

        /* Doctor Info Card */
        .doctor-info-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .avatar-lg {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin-right: 1.5rem;
        }

        .doctor-name {
            color: #1f2937;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .doctor-specialty {
            color: #4f46e5;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .doctor-clinic {
            color: #6b7280;
            font-weight: 500;
        }

        /* Tab styling */
        .nav-tabs {
            border: none;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 12px;
            padding: 0.5rem;
            margin-bottom: 2rem;
        }

        .nav-tabs .nav-link {
            color: #6b7280;
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            background: rgba(79, 70, 229, 0.1);
            color: #4f46e5;
        }

        .nav-tabs .nav-link.active {
            background: #4f46e5;
            color: white;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
        }

        /* Cards */
        .content-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.6);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-bottom: 2px solid rgba(79, 70, 229, 0.1);
            padding: 1.5rem 2rem;
        }

        .card-header h5 {
            color: #1f2937;
            font-weight: 700;
            margin: 0;
        }

        /* Table styling */
        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: rgba(79, 70, 229, 0.05);
            color: #4f46e5;
            font-weight: 700;
            border: none;
            padding: 1rem;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-color: rgba(0, 0, 0, 0.05);
        }

        .table tbody tr:hover {
            background: rgba(79, 70, 229, 0.03);
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(79, 70, 229, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        }

        .btn-outline-primary {
            color: #4f46e5;
            border-color: #4f46e5;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-outline-primary:hover {
            background: #4f46e5;
            border-color: #4f46e5;
            transform: translateY(-1px);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: 6px;
        }

        /* Badges */
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        /* Status badges */
        .bg-warning {
            background: linear-gradient(135deg, #f59e0b, #fbbf24) !important;
        }

        .bg-success {
            background: linear-gradient(135deg, #10b981, #34d399) !important;
        }

        .bg-primary {
            background: linear-gradient(135deg, #3b82f6, #60a5fa) !important;
        }

        .bg-danger {
            background: linear-gradient(135deg, #ef4444, #f87171) !important;
        }

        /* Form controls */
        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
            border-radius: 2rem;
        }

        .form-switch .form-check-input:checked {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }

        /* Modal styling */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: white;
            border-radius: 15px 15px 0 0;
            border-bottom: none;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                margin: 10px;
                padding: 1.5rem;
            }

            .doctor-nav {
                padding: 1.5rem;
            }

            .nav-buttons {
                justify-content: center;
            }

            .nav-btn {
                flex: 1;
                justify-content: center;
                min-width: 140px;
            }

            .doctor-info-card {
                padding: 1.5rem;
            }

            .avatar-lg {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }

            .table-responsive {
                font-size: 0.875rem;
            }
        }

        /* Animation */
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

        .content-card {
            animation: fadeInUp 0.5s ease-out;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-3">
        <div class="main-container">
            <!-- Doctor Navigation -->
            <div class="doctor-nav">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="mb-3 mb-md-0">
                        <h2 class="mb-1">
                            <i class="fas fa-user-md me-2"></i>
                            Panel Bác sĩ
                        </h2>
                        <p class="mb-0 opacity-75">Quản lý lịch làm việc và lịch hẹn</p>
                    </div>
                    <div class="nav-buttons">
                        <a href="schedule.php" class="nav-btn active">
                            <i class="fas fa-calendar-alt"></i>
                            Lịch làm việc
                        </a>
                        <!-- <a href="../appointments.php" class="nav-btn">
                            <i class="fas fa-calendar-check"></i>
                            Lịch hẹn của tôi
                        </a> -->
                        <a href="clinic-registration.php" class="nav-btn">
                            <i class="fas fa-hospital"></i>
                            Nơi làm việc
                        </a>
                        <!-- <a href="appointment-view.php?id=1" class="nav-btn">
                            <i class="fas fa-eye"></i>
                            Xem lịch hẹn
                        </a> -->
                    </div>
                </div>
            </div>

            <!-- Doctor Info Card -->
            <div class="doctor-info-card">
                <div class="d-flex align-items-center">
                    <div class="avatar-lg">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="doctor-name"><?= htmlspecialchars($doctor_info['doctor_name'] ?? 'N/A') ?></h4>
                        <p class="doctor-specialty">
                            <i class="fas fa-stethoscope me-2"></i>
                            <?= htmlspecialchars($doctor_info['specialty_name'] ?? 'Chưa có chuyên khoa') ?>
                        </p>
                        <p class="doctor-clinic mb-0">
                            <i class="fas fa-hospital me-2"></i>
                            <?= htmlspecialchars($doctor_info['clinic_name'] ?? 'Chưa có phòng khám') ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <div class="d-flex gap-2">
                            <div class="text-center p-3 bg-light rounded-3">
                                <h3 class="mb-1 text-primary"><?= count($schedules) ?></h3>
                                <small class="text-muted">Ca trực</small>
                            </div>
                            <div class="text-center p-3 bg-light rounded-3">
                                <h3 class="mb-1 text-success"><?= count($off_days) ?></h3>
                                <small class="text-muted">Ngày nghỉ</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#schedule">
                        <i class="fas fa-calendar-alt me-2"></i>Lịch làm việc
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#appointments">
                        <i class="fas fa-calendar-check me-2"></i>Lịch hẹn
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#off-days">
                        <i class="fas fa-calendar-minus me-2"></i>Ngày nghỉ
                    </a>
                </li>
            </ul>

            <!-- Tab content -->
            <div class="tab-content">
                <!-- Lịch làm việc -->
                <div class="tab-pane fade show active" id="schedule">
                    <div class="content-card">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Ca trực trong tuần</h5>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addShiftModal">
                                <i class="fas fa-plus me-2"></i>Thêm ca trực
                            </button>
                           
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Thứ</th>
                                        <th>Ca sáng</th>
                                        <th>Ca chiều</th>
                                        <th>Lịch hẹn</th>
                                        <th>Trạng thái</th>
                                        <th style="width: 100px">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($day = 1; $day <= 7; $day++): ?>
                                        <tr>
                                            <td class="fw-medium"><?= getDayName($day) ?></td>
                                            <td>
                                                <?php
                                                $morning_shifts = array_filter($schedules, function($s) use ($day) {
                                                    return $s['day_of_week'] == $day && 
                                                           strtotime($s['start_time']) < strtotime('12:00:00');
                                                });
                                                if ($morning_shifts) {
                                                    foreach ($morning_shifts as $shift) {
                                                        echo '<div class="mb-2">';
                                                        echo date('H:i', strtotime($shift['start_time'])) . ' - ' . 
                                                             date('H:i', strtotime($shift['end_time']));
                                                        if (isset($shift['appointment_count']) && $shift['appointment_count'] > 0) {
                                                            echo '<br><small class="text-muted">' . 
                                                                 $shift['appointment_count'] . ' lịch hẹn</small>';
                                                        }
                                                        echo '</div>';
                                                    }
                                                } else {
                                                    echo '<span class="text-muted">Không có ca trực</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $afternoon_shifts = array_filter($schedules, function($s) use ($day) {
                                                    return $s['day_of_week'] == $day && 
                                                           strtotime($s['start_time']) >= strtotime('12:00:00');
                                                });
                                                if ($afternoon_shifts) {
                                                    foreach ($afternoon_shifts as $shift) {
                                                        echo '<div class="mb-2">';
                                                        echo date('H:i', strtotime($shift['start_time'])) . ' - ' . 
                                                             date('H:i', strtotime($shift['end_time']));
                                                        if (isset($shift['appointment_count']) && $shift['appointment_count'] > 0) {
                                                            echo '<br><small class="text-muted">' . 
                                                                 $shift['appointment_count'] . ' lịch hẹn</small>';
                                                        }
                                                        echo '</div>';
                                                    }
                                                } else {
                                                    echo '<span class="text-muted">Không có ca trực</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $day_shifts = array_filter($schedules, function($s) use ($day) {
                                                    return $s['day_of_week'] == $day && 
                                                           isset($s['appointment_count']) && 
                                                           $s['appointment_count'] > 0;
                                                });
                                                foreach ($day_shifts as $shift) {
                                                    if (isset($shift['appointments'])) {
                                                        echo '<div class="small">';
                                                        echo nl2br(htmlspecialchars($shift['appointments']));
                                                        echo '</div>';
                                                    }
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $day_shifts = array_filter($schedules, function($s) use ($day) {
                                                    return $s['day_of_week'] == $day;
                                                });
                                                if ($day_shifts) {
                                                    $all_available = array_reduce($day_shifts, function($carry, $item) {
                                                        return $carry && ($item['is_available'] ?? false);
                                                    }, true);
                                                    ?>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="day_<?= $day ?>"
                                                               <?= $all_available ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="day_<?= $day ?>">
                                                            <?= $all_available ? 'Đang làm việc' : 'Tạm nghỉ' ?>
                                                        </label>
                                                    </div>
                                                    <?php
                                                } else {
                                                    echo '<span class="badge bg-secondary">Chưa có lịch</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-outline-primary btn-sm me-2" 
                                                        onclick="editShifts(<?= $day ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if (!empty($day_shifts)): ?>
                                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                                            onclick="deleteShifts(<?= $day ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

                <!-- Lịch hẹn -->
                <div class="tab-pane fade" id="appointments">
                    <div class="content-card">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Lịch hẹn sắp tới</h5>
                            <div>
                                <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
                                    <i class="fas fa-sync me-2"></i>Làm mới
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Ngày hẹn</th>
                                        <th>Giờ hẹn</th>
                                        <th>Bệnh nhân</th>
                                        <th>Phòng khám</th>
                                        <th>Lý do khám</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        // Lấy danh sách lịch hẹn
                                        $appointments_query = "SELECT a.*, 
                                                            COALESCE(ui_patient.full_name, u_patient.username, gu.full_name) as patient_name,
                                                            COALESCE(ui_patient.phone, gu.phone) as patient_phone,
                                                            c.name as clinic_name
                                                    FROM appointments a
                                                    LEFT JOIN users u_patient ON a.user_id = u_patient.user_id
                                                    LEFT JOIN users_info ui_patient ON a.user_id = ui_patient.user_id
                                                    LEFT JOIN guest_users gu ON a.guest_id = gu.guest_id
                                                    LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
                                                    WHERE a.doctor_id = ? 
                                                    AND DATE(a.appointment_time) >= CURDATE()
                                                    ORDER BY a.appointment_time ASC";

                                $stmt = $conn->prepare($appointments_query);
                                if ($stmt === false) {
                                    throw new Exception("Lỗi chuẩn bị truy vấn: " . $conn->error);
                                }

                                if (!$stmt->bind_param('i', $doctor_info['doctor_id'])) {
                                    throw new Exception("Lỗi bind tham số: " . $stmt->error);
                                }

                                if (!$stmt->execute()) {
                                    throw new Exception("Lỗi thực thi truy vấn: " . $stmt->error);
                                }

                                $result = $stmt->get_result();
                                if (!$result) {
                                    throw new Exception("Lỗi lấy kết quả: " . $conn->error);
                                }

                                $appointments = $result->fetch_all(MYSQLI_ASSOC);
                                
                                // Debug
                                error_log("Found " . count($appointments) . " appointments");
                                if (count($appointments) > 0) {
                                    error_log("First appointment: " . print_r($appointments[0], true));
                                }

                                if (count($appointments) > 0):
                                    foreach ($appointments as $appointment):
                                        $status_configs = [
                                            'pending' => ['class' => 'warning', 'text' => 'Chờ xác nhận'],
                                            'confirmed' => ['class' => 'success', 'text' => 'Đã xác nhận'],
                                            'completed' => ['class' => 'primary', 'text' => 'Hoàn thành'],
                                            'cancelled' => ['class' => 'danger', 'text' => 'Đã hủy']
                                        ];
                                        $status_config = $status_configs[$appointment['status']] ?? ['class' => 'secondary', 'text' => 'Không xác định'];
                                        ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($appointment['appointment_time'])) ?></td>
                                            <td><?= date('H:i', strtotime($appointment['appointment_time'])) ?></td>
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
                                                <h6 class="mb-0"><?= htmlspecialchars($appointment['clinic_name'] ?? 'Chưa chọn') ?></h6>
                                            </td>
                                            <td>
                                                <?php if ($appointment['reason']): ?>
                                                    <small><?= htmlspecialchars(substr($appointment['reason'], 0, 50)) ?><?= strlen($appointment['reason']) > 50 ? '...' : '' ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">Không có</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $status_config['class'] ?>"><?= $status_config['text'] ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <?php if ($appointment['status'] == 'confirmed'): ?>
                                                        <button type="button" class="btn btn-primary btn-sm" 
                                                                onclick="updateAppointmentStatus(<?= $appointment['appointment_id'] ?>, 'completed')"
                                                                title="Hoàn thành khám">
                                                            <i class="fas fa-check-double"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($appointment['status'] == 'pending'): ?>
                                                        <button type="button" class="btn btn-success btn-sm" 
                                                                onclick="updateAppointmentStatus(<?= $appointment['appointment_id'] ?>, 'confirmed')"
                                                                title="Xác nhận lịch hẹn">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-danger btn-sm"
                                                                onclick="updateAppointmentStatus(<?= $appointment['appointment_id'] ?>, 'cancelled')"
                                                                title="Hủy lịch hẹn">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <a href="appointment-view.php?id=<?= $appointment['appointment_id'] ?>" 
                                                       class="btn btn-info btn-sm"
                                                       title="Xem chi tiết">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php 
                                    endforeach;
                                else:
                                    ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-calendar-check mb-2 d-block"></i>
                                                Không có lịch hẹn nào sắp tới
                                            </div>
                                        </td>
                                    </tr>
                                <?php 
                                endif;
                            } catch (Exception $e) {
                                echo '<tr><td colspan="7" class="text-center text-danger py-4">';
                                echo '<i class="fas fa-exclamation-circle mb-2 d-block"></i>';
                                echo 'Có lỗi xảy ra: ' . htmlspecialchars($e->getMessage());
                                echo '</td></tr>';
                                error_log("Error in appointments section: " . $e->getMessage());
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

                <!-- Ngày nghỉ -->
                <div class="tab-pane fade" id="off-days">
                    <div class="content-card">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Ngày nghỉ sắp tới</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOffDayModal">
                        <i class="fas fa-plus me-2"></i>Thêm ngày nghỉ
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Ngày</th>
                                <th>Lý do</th>
                                <th style="width: 100px">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($off_days) > 0): ?>
                                <?php foreach ($off_days as $off_day): ?>
                                    <tr>
                                        <td class="fw-medium">
                                            <?= date('d/m/Y', strtotime($off_day['off_date'])) ?>
                                        </td>
                                        <td><?= htmlspecialchars($off_day['reason']) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-outline-danger btn-sm"
                                                    onclick="deleteOffDay(<?= $off_day['off_day_id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-calendar-check mb-2 d-block"></i>
                                            Không có ngày nghỉ nào sắp tới
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal thêm/sửa ca trực -->
    <div class="modal fade" id="addShiftModal" tabindex="-1" style="background: rgba(0,0,0,0.2);">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); background: #fff;">
                <div class="modal-header" style="background: linear-gradient(135deg, #4f46e5, #6366f1); color: white; border-radius: 20px 20px 0 0; border: none; padding: 1.5rem;">
                    <h5 class="modal-title" style="font-size: 1.25rem; font-weight: 600;">
                        <i class="fas fa-calendar-plus me-2"></i><span id="modalTitle">Thêm ca trực mới</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;">
                    <form id="addShiftForm">
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #1f2937;">Chọn thứ</label>
                            <select class="form-select" name="day_of_week" required style="border-radius: 10px; padding: 0.75rem; border-color: #e5e7eb;">
                                <?php for ($i = 1; $i <= 7; $i++): ?>
                                    <option value="<?= $i ?>"><?= getDayName($i) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #1f2937;">Ca sáng</label>
                            <div class="d-flex gap-2 mb-2">
                                <input type="time" class="form-control" name="morning_start" min="08:00" max="12:00">
                                <input type="time" class="form-control" name="morning_end" min="08:00" max="12:00">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #1f2937;">Ca chiều</label>
                            <div class="d-flex gap-2">
                                <input type="time" class="form-control" name="afternoon_start" min="13:30" max="17:30">
                                <input type="time" class="form-control" name="afternoon_end" min="13:30" max="17:30">
                            </div>
                        </div>
                        <input type="hidden" name="action" value="add">
                    </form>
                </div>
                <div class="modal-footer" style="border-top-color: #f3f4f6; padding: 1.5rem;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600;">
                        <i class="fas fa-times me-2"></i>Đóng
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveShift()" style="padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600; background: linear-gradient(135deg, #4f46e5, #6366f1); border: none;">
                        <i class="fas fa-save me-2"></i>Lưu
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal thêm ngày nghỉ -->
    <div class="modal fade" id="addOffDayModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm ngày nghỉ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addOffDayForm">
                        <div class="mb-3">
                            <label class="form-label">Ngày nghỉ</label>
                            <input type="date" class="form-control" name="off_date" required
                                   min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lý do</label>
                            <textarea class="form-control" name="reason" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" onclick="saveOffDay()">Lưu</button>
                </div>
            </div>
        </div>
    </div>

    



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Xử lý thay đổi trạng thái làm việc
    document.querySelectorAll('.form-check-input').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const day = this.id.split('_')[1];
            const isAvailable = this.checked ? 1 : 0;
            
            fetch('ajax/update-shift-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    day_of_week: day,
                    is_available: isAvailable
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Cập nhật label
                    const label = this.nextElementSibling;
                    label.textContent = isAvailable ? 'Đang làm việc' : 'Tạm nghỉ';
                } else {
                    alert('Có lỗi xảy ra: ' + data.message);
                    // Revert checkbox state
                    this.checked = !this.checked;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi cập nhật trạng thái');
                // Revert checkbox state
                this.checked = !this.checked;
            });
        });
    });

    // Xử lý thêm/sửa ca trực
    function saveShift() {
        const form = document.getElementById('addShiftForm');
        const formData = new FormData(form);
        const action = formData.get('action');
        
        fetch('ajax/add-shift.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: action === 'add' ? 'Thêm ca trực thành công!' : 'Cập nhật ca trực thành công!',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Có lỗi xảy ra',
                    text: data.message
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Có lỗi xảy ra',
                text: 'Không thể kết nối đến máy chủ'
            });
        });
    }

    // Xử lý thêm ngày nghỉ
    function saveOffDay() {
        const form = document.getElementById('addOffDayForm');
        const formData = new FormData(form);
        
        fetch('ajax/add-off-day.php', {
            method: 'POST',
            body: formData
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
            alert('Có lỗi xảy ra khi thêm ngày nghỉ');
        });
    }

    // Xử lý chỉnh sửa ca trực
    function editShifts(day) {
        // Lấy thông tin ca hiện tại
        const morningShiftDiv = document.querySelector(`tr:nth-child(${day}) td:nth-child(2)`);
        const afternoonShiftDiv = document.querySelector(`tr:nth-child(${day}) td:nth-child(3)`);
        
        // Parse thời gian từ nội dung
        let morningStart = '', morningEnd = '', afternoonStart = '', afternoonEnd = '';
        
        // Lấy thời gian ca sáng
        const morningText = morningShiftDiv.textContent.trim();
        if (morningText && morningText !== 'Không có ca trực') {
            const morningMatch = morningText.match(/(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})/);
            if (morningMatch) {
                morningStart = morningMatch[1];
                morningEnd = morningMatch[2];
            }
        }
        
        // Lấy thời gian ca chiều
        const afternoonText = afternoonShiftDiv.textContent.trim();
        if (afternoonText && afternoonText !== 'Không có ca trực') {
            const afternoonMatch = afternoonText.match(/(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})/);
            if (afternoonMatch) {
                afternoonStart = afternoonMatch[1];
                afternoonEnd = afternoonMatch[2];
            }
        }
        
        // Hiển thị modal chỉnh sửa
        const modal = new bootstrap.Modal(document.getElementById('addShiftModal'));
        const form = document.getElementById('addShiftForm');
        
        // Điền thông tin vào form
        form.querySelector('select[name="day_of_week"]').value = day;
        form.querySelector('input[name="morning_start"]').value = morningStart;
        form.querySelector('input[name="morning_end"]').value = morningEnd;
        form.querySelector('input[name="afternoon_start"]').value = afternoonStart;
        form.querySelector('input[name="afternoon_end"]').value = afternoonEnd;
        
        // Đổi action thành edit
        form.querySelector('input[name="action"]').value = 'edit';
        
        // Đổi tiêu đề modal
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa ca trực';
        
        modal.show();
    }

    // Xử lý xóa ca trực
    function deleteShifts(day) {
        Swal.fire({
            title: 'Xác nhận xóa?',
            text: 'Bạn có chắc chắn muốn xóa các ca trực của ngày này?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('ajax/delete-shifts.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        day_of_week: day
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Đã xóa thành công!',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Có lỗi xảy ra',
                            text: data.message
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Có lỗi xảy ra',
                        text: 'Không thể kết nối đến máy chủ'
                    });
                });
            }
        });
    }

    // Xử lý xóa ngày nghỉ
    function deleteOffDay(offDayId) {
        if (confirm('Bạn có chắc chắn muốn xóa ngày nghỉ này?')) {
            fetch('ajax/delete-off-day.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    off_day_id: offDayId
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
                alert('Có lỗi xảy ra khi xóa ngày nghỉ');
            });
        }
    }

    // Thêm hàm xử lý cập nhật trạng thái lịch hẹn
    function updateAppointmentStatus(appointmentId, status) {
        if (confirm('Bạn có chắc chắn muốn ' + (status === 'confirmed' ? 'xác nhận' : 'hủy') + ' lịch hẹn này?')) {
            fetch('ajax/update-appointment-status.php', {
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
    </script>
</body>
</html> 