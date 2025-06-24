<?php
include '../includes/db.php';
session_start();

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get form data
    $doctor_id = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
    $clinic_id = isset($_POST['clinic_id']) ? (int)$_POST['clinic_id'] : 0;
    $appointment_date = isset($_POST['appointment_date']) ? trim($_POST['appointment_date']) : '';
    $appointment_time = isset($_POST['appointment_time']) ? trim($_POST['appointment_time']) : '';
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    
    // Validation
    if (!$doctor_id) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn bác sĩ']);
        exit;
    }
    
    if (!$clinic_id) {
        echo json_encode(['success' => false, 'message' => 'Thông tin phòng khám không hợp lệ']);
        exit;
    }
    
    if (!$appointment_date) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn ngày khám']);
        exit;
    }
    
    if (!$appointment_time) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn giờ khám']);
        exit;
    }
    
    // Validate date is not in the past
    $selected_date = new DateTime($appointment_date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($selected_date < $today) {
        echo json_encode(['success' => false, 'message' => 'Không thể đặt lịch trong quá khứ']);
        exit;
    }
    
    // Combine date and time
    $appointment_datetime = $appointment_date . ' ' . $appointment_time . ':00';
    
    // Check if the time slot is still available
    $check_stmt = $conn->prepare("SELECT appointment_id FROM appointments WHERE doctor_id = ? AND appointment_time = ? AND status IN ('pending', 'confirmed')");
    $check_stmt->bind_param('is', $doctor_id, $appointment_datetime);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Thời gian này đã có lịch hẹn khác. Vui lòng chọn thời gian khác!']);
        exit;
    }
    
    // Check if user already has an appointment at the same time
    $user_check_stmt = $conn->prepare("SELECT appointment_id FROM appointments WHERE user_id = ? AND appointment_time = ? AND status IN ('pending', 'confirmed')");
    $user_check_stmt->bind_param('is', $user_id, $appointment_datetime);
    $user_check_stmt->execute();
    
    if ($user_check_stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Bạn đã có lịch hẹn vào thời gian này!']);
        exit;
    }
    
    // Insert new appointment
    $insert_stmt = $conn->prepare("INSERT INTO appointments (user_id, doctor_id, clinic_id, appointment_time, reason, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
    $insert_stmt->bind_param('iiiss', $user_id, $doctor_id, $clinic_id, $appointment_datetime, $reason);
    
    if ($insert_stmt->execute()) {
        $appointment_id = $conn->insert_id;
        
        // Get appointment details for response
        $detail_stmt = $conn->prepare("
            SELECT a.appointment_id, a.appointment_time, a.reason, a.status,
                   ui.full_name as doctor_name, s.name as specialization,
                   c.name as clinic_name, c.address as clinic_address
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.doctor_id
            JOIN users u ON d.user_id = u.user_id
            JOIN users_info ui ON u.user_id = ui.user_id
            JOIN specialties s ON d.specialty_id = s.specialty_id
            JOIN clinics c ON a.clinic_id = c.clinic_id
            WHERE a.appointment_id = ?
        ");
        $detail_stmt->bind_param('i', $appointment_id);
        $detail_stmt->execute();
        $appointment_details = $detail_stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Đặt lịch thành công! Lịch hẹn của bạn đang chờ xác nhận từ phòng khám.',
            'appointment' => $appointment_details
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi đặt lịch. Vui lòng thử lại!']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}
?> 