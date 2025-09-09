<?php
session_start();
require_once '../includes/db.php';

// Kiểm tra đăng nhập và quyền bác sĩ (role_id = 2 hoặc 3)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [2, 3])) {
    header('Location: ../login.php');
    exit();
}

$appointment_id = (int)($_GET['id'] ?? 0);
if (!$appointment_id) {
    header('Location: ../appointments.php?error=invalid_id');
    exit();
}

// Debug: Thêm logging chi tiết
error_log("Doctor appointment-view.php - User ID: " . $_SESSION['user_id'] . ", Appointment ID: " . $appointment_id);

// Lấy thông tin doctor hiện tại
$current_doctor_id = null;
$stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $doctor_data = $result->fetch_assoc();
            $current_doctor_id = $doctor_data['doctor_id'];
        }
    } else {
        error_log("Error getting doctor info: " . $stmt->error);
    }
    $stmt->close();
} else {
    error_log("Error preparing doctor query: " . $conn->error);
}

// Kiểm tra user có phải doctor không
if (!$current_doctor_id) {
    die("
        <h2>❌ Không có quyền truy cập</h2>
        <p>User ID: {$_SESSION['user_id']} không phải là bác sĩ.</p>
        <p>Chỉ bác sĩ mới có thể xem chi tiết lịch hẹn.</p>
        <a href='../test_doctor_access.php'>🔍 Debug Page</a> |
        <a href='../login.php'>← Đăng nhập lại</a>
    ");
}

// Lấy thông tin lịch hẹn chi tiết (chỉ của bác sĩ hiện tại)
$appointment = null;

try {
    $sql = "SELECT a.*, 
                   DATE(a.appointment_time) as appointment_date,
                   TIME(a.appointment_time) as appointment_time_only,
                   u_patient.username as patient_username,
                   u_patient.email as patient_email,
                --    u_patient.phone_number as patient_phone,
                   ui_patient.full_name as patient_fullname,
                   ui_patient.date_of_birth as patient_dob,
                   ua_patient.address_line as patient_address,
                   ua_patient.ward as patient_ward,
                   ua_patient.district as patient_district,
                   ua_patient.city as patient_city,
                   gu.full_name as guest_fullname,
                   gu.email as guest_email,
                   gu.phone as guest_phone,
                   u_doctor.username as doctor_username,
                   u_doctor.email as doctor_email,
                   ui_doctor.full_name as doctor_fullname,
                   s.name as doctor_specialization,
                   c.name as clinic_name,
                   c.address as clinic_address,
                   c.phone as clinic_phone
            FROM appointments a
            LEFT JOIN users u_patient ON a.user_id = u_patient.user_id
            LEFT JOIN users_info ui_patient ON a.user_id = ui_patient.user_id
            LEFT JOIN user_addresses ua_patient ON a.user_id = ua_patient.user_id AND ua_patient.is_default = 1
            LEFT JOIN guest_users gu ON a.guest_id = gu.guest_id
            LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
            LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
            LEFT JOIN users u_doctor ON d.user_id = u_doctor.user_id
            LEFT JOIN users_info ui_doctor ON d.user_id = ui_doctor.user_id
            LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
            WHERE a.appointment_id = ? AND a.doctor_id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("SQL Error in doctor appointment-view.php: " . $conn->error);
        die("Database Error: " . htmlspecialchars($conn->error));
    }
    
    $stmt->bind_param("ii", $appointment_id, $current_doctor_id);
    if (!$stmt->execute()) {
        error_log("Query execution failed in doctor appointment-view.php: " . $stmt->error);
        die("Query Error: " . htmlspecialchars($stmt->error));
    }
    
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $appointment = $result->fetch_assoc();
    } else {
        error_log("Appointment not found or access denied - Appointment ID: $appointment_id, Doctor ID: $current_doctor_id");
        die("
            <h2>❌ Không tìm thấy lịch hẹn</h2>
            <p>Appointment ID: $appointment_id</p>
            <p>Doctor ID: $current_doctor_id</p>
            <p>Có thể lịch hẹn này không tồn tại hoặc không thuộc về bác sĩ hiện tại.</p>
            <a href='../test_doctor_access.php'>🔍 Debug Page</a> |
            <a href='../appointments.php'>← Quay lại danh sách</a>
        ");
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Exception in doctor appointment-view.php: " . $e->getMessage());
    die("
        <h2>❌ Lỗi hệ thống</h2>
        <p>Exception: " . htmlspecialchars($e->getMessage()) . "</p>
        <a href='../test_doctor_access.php'>🔍 Debug Page</a> |
        <a href='../appointments.php'>← Quay lại danh sách</a>
    ");
}

// Prepare display data
$patient_name = '';
$patient_email = '';
$patient_phone = '';
$patient_dob = '';
$patient_address = '';

if ($appointment['patient_fullname'] || $appointment['patient_username']) {
    $patient_name = $appointment['patient_fullname'] ?: $appointment['patient_username'];
    $patient_email = $appointment['patient_email'];
    // $patient_phone = $appointment['patient_phone'];
    $patient_dob = $appointment['patient_dob'];
    $patient_address = trim($appointment['patient_address'] . ', ' . 
                           $appointment['patient_ward'] . ', ' . 
                           $appointment['patient_district'] . ', ' . 
                           $appointment['patient_city'], ', ');
} elseif ($appointment['guest_fullname']) {
    $patient_name = $appointment['guest_fullname'];
    $patient_email = $appointment['guest_email'];
    $patient_phone = $appointment['guest_phone'];
}

// Format appointment date and time
$formatted_date = date('d/m/Y', strtotime($appointment['appointment_date']));
$formatted_time = date('H:i', strtotime($appointment['appointment_time_only']));

// Status mapping
$status_map = [
    'pending' => ['text' => 'Chờ xác nhận', 'class' => 'warning'],
    'confirmed' => ['text' => 'Đã xác nhận', 'class' => 'success'],
    'completed' => ['text' => 'Hoàn thành', 'class' => 'info'],
    'cancelled' => ['text' => 'Đã hủy', 'class' => 'danger'],
    'no_show' => ['text' => 'Không đến', 'class' => 'secondary']
];

$current_status = $status_map[$appointment['status']] ?? ['text' => 'Không xác định', 'class' => 'dark'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Lịch hẹn - Bác sĩ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .appointment-card { background: white; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .status-badge { font-size: 0.9rem; }
        .patient-avatar { width: 80px; height: 80px; background: #e9ecef; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #6c757d; }
        .action-btn { min-width: 120px; }
        .info-row { border-bottom: 1px solid #eee; padding: 12px 0; }
        .info-row:last-child { border-bottom: none; }
        .section-title { color: #495057; font-weight: 600; border-bottom: 2px solid #007bff; padding-bottom: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h3 mb-1">
                            <i class="fas fa-calendar-check text-primary me-2"></i>
                            Chi tiết Lịch hẹn #<?= $appointment['appointment_id'] ?>
                        </h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
                                <li class="breadcrumb-item"><a href="../appointments.php">Lịch hẹn</a></li>
                                <li class="breadcrumb-item active">Chi tiết</li>
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <a href="../appointments.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Appointment Info -->
            <div class="col-lg-8 mb-4">
                <div class="appointment-card p-4">
                    <div class="row">
                        <div class="col-md-2 text-center mb-3">
                            <div class="patient-avatar mx-auto">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <div class="col-md-10">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h4 class="mb-1"><?= htmlspecialchars($patient_name) ?></h4>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-calendar me-1"></i><?= $formatted_date ?>
                                        <i class="fas fa-clock ms-3 me-1"></i><?= $formatted_time ?>
                                    </p>
                                </div>
                                <span class="badge bg-<?= $current_status['class'] ?> status-badge">
                                    <?= $current_status['text'] ?>
                                </span>
                            </div>

                            <h5 class="section-title">Thông tin Bệnh nhân</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <strong>Họ tên:</strong> <?= htmlspecialchars($patient_name) ?>
                                    </div>
                                    <div class="info-row">
                                        <strong>Email:</strong> <?= htmlspecialchars($patient_email ?: 'Không có') ?>
                                    </div>
                                    <div class="info-row">
                                        <strong>Số điện thoại:</strong> <?= htmlspecialchars($patient_phone ?: 'Không có') ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <strong>Ngày sinh:</strong> 
                                        <?= $patient_dob ? date('d/m/Y', strtotime($patient_dob)) : 'Không có' ?>
                                    </div>
                                    <div class="info-row">
                                        <strong>Địa chỉ:</strong> 
                                        <?= htmlspecialchars($patient_address ?: 'Không có') ?>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($appointment['reason'])): ?>
                            <h5 class="section-title mt-4">Lý do khám</h5>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($appointment['reason'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div class="col-lg-4">
                <!-- Clinic Info -->
                <div class="appointment-card p-4 mb-4">
                    <h5 class="section-title">Thông tin Phòng khám</h5>
                    <div class="info-row">
                        <strong>Tên:</strong> <?= htmlspecialchars($appointment['clinic_name'] ?: 'Không có') ?>
                    </div>
                    <div class="info-row">
                        <strong>Địa chỉ:</strong> <?= htmlspecialchars($appointment['clinic_address'] ?: 'Không có') ?>
                    </div>
                    <div class="info-row">
                        <strong>Điện thoại:</strong> <?= htmlspecialchars($appointment['clinic_phone'] ?: 'Không có') ?>
                    </div>
                    <div class="info-row">
                        <strong>Chuyên khoa:</strong> <?= htmlspecialchars($appointment['doctor_specialization'] ?: 'Không có') ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="appointment-card p-4">
                    <h5 class="section-title">Thao tác</h5>
                    <div class="d-grid gap-2">
                        <?php if ($appointment['status'] === 'pending'): ?>
                        <button class="btn btn-success action-btn" onclick="updateStatus('confirmed')">
                            <i class="fas fa-check"></i> Xác nhận
                        </button>
                        <button class="btn btn-danger action-btn" onclick="updateStatus('cancelled')">
                            <i class="fas fa-times"></i> Từ chối
                        </button>
                        <?php elseif ($appointment['status'] === 'confirmed'): ?>
                        <button class="btn btn-info action-btn" onclick="updateStatus('completed')">
                            <i class="fas fa-check-circle"></i> Hoàn thành
                        </button>
                        <button class="btn btn-secondary action-btn" onclick="updateStatus('no_show')">
                            <i class="fas fa-user-times"></i> Không đến
                        </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-outline-primary action-btn" onclick="window.print()">
                            <i class="fas fa-print"></i> In phiếu
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(newStatus) {
            if (!confirm('Bạn có chắc chắn muốn thay đổi trạng thái lịch hẹn này?')) {
                return;
            }

            fetch('ajax/update-appointment-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    appointment_id: <?= $appointment_id ?>,
                    status: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cập nhật trạng thái thành công!');
                    location.reload();
                } else {
                    alert('Lỗi: ' + (data.message || 'Không thể cập nhật trạng thái'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi khi cập nhật trạng thái');
            });
        }
    </script>
</body>
</html> 