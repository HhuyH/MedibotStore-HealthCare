<?php
include '../includes/db.php';
include '../includes/email_system_simple.php';
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
    // Debug incoming data
    error_log("Incoming POST data: " . print_r($_POST, true));
    
    // Get form data
    $doctor_id = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
    $clinic_id = isset($_POST['clinic_id']) ? (int)$_POST['clinic_id'] : 0;
    $appointment_date = isset($_POST['appointment_date']) ? trim($_POST['appointment_date']) : '';
    $appointment_time = isset($_POST['appointment_time']) ? trim($_POST['appointment_time']) : '';
    
    // Debug processed data
    error_log("Processed appointment data:");
    error_log("Doctor ID: " . $doctor_id);
    error_log("Clinic ID: " . $clinic_id);
    error_log("Date: " . $appointment_date);
    error_log("Time: " . $appointment_time);
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
    
    // Validate date and time
    try {
        $selected_datetime = new DateTime($appointment_date . ' ' . $appointment_time);
        $now = new DateTime();
        
        error_log("Selected datetime: " . $selected_datetime->format('Y-m-d H:i:s'));
        error_log("Current datetime: " . $now->format('Y-m-d H:i:s'));
        
        if ($selected_datetime < $now) {
            echo json_encode(['success' => false, 'message' => 'Không thể đặt lịch trong quá khứ']);
            exit;
        }

        // Validate if time is within working hours (8:00 - 17:00)
        $hour = (int)$selected_datetime->format('H');
        if ($hour < 8 || $hour >= 17) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng chọn giờ khám từ 8:00 đến 17:00']);
            exit;
        }
    } catch (Exception $e) {
        error_log("Date validation error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Định dạng thời gian không hợp lệ']);
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
        
        // Get user details for email
        $user_stmt = $conn->prepare("
            SELECT u.email, ui.full_name 
            FROM users u 
            JOIN users_info ui ON u.user_id = ui.user_id 
            WHERE u.user_id = ?
        ");
        $user_stmt->bind_param('i', $user_id);
        $user_stmt->execute();
        $user_details = $user_stmt->get_result()->fetch_assoc();

        // Debug log
        error_log("User Email: " . ($user_details['email'] ?? 'Not found'));
        error_log("Appointment Details: " . print_r($appointment_details, true));
        
        // Format appointment data correctly
        $formatted_appointment = [
            'doctor_name' => $appointment_details['doctor_name'],
            'specialization' => $appointment_details['specialization'],
            'appointment_time' => $appointment_details['appointment_time'],
            'clinic_name' => $appointment_details['clinic_name'],
            'clinic_address' => $appointment_details['clinic_address'],
            'reason' => $appointment_details['reason']
        ];
        
        // Send confirmation email with error handling
        if ($user_details && $user_details['email']) {
            try {
                $email_sent = sendAppointmentEmail(
                    $user_details['email'],
                    $user_details['full_name'],
                    $formatted_appointment
                );
                error_log("Email sending attempt to {$user_details['email']}: " . ($email_sent ? 'Success' : 'Failed'));
            } catch (Exception $e) {
                error_log("Email sending error: " . $e->getMessage());
            }
        }

        $_SESSION['appointment_success'] = 'Đặt lịch thành công! Lịch hẹn của bạn đang chờ xác nhận từ phòng khám. Vui lòng kiểm tra email để xem chi tiết.';
        
        echo json_encode([
            'success' => true,
            'message' => 'Đặt lịch thành công! Lịch hẹn của bạn đang chờ xác nhận từ phòng khám. Vui lòng kiểm tra email để xem chi tiết.',
            'appointment' => $appointment_details,
            'redirect' => '/appointments.php'
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