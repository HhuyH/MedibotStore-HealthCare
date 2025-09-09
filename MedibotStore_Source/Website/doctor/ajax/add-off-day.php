<?php
session_start();
require_once '../../includes/db.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập và quyền bác sĩ
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit();
}

try {
    // Validate input
    if (!isset($_POST['off_date']) || !isset($_POST['reason'])) {
        throw new Exception('Vui lòng nhập đầy đủ thông tin');
    }

    $off_date = $_POST['off_date'];
    $reason = trim($_POST['reason']);

    // Validate date
    if (strtotime($off_date) < strtotime(date('Y-m-d'))) {
        throw new Exception('Không thể chọn ngày trong quá khứ');
    }

    // Lấy doctor_id
    $stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
    if (!$stmt) {
        throw new Exception("Lỗi truy vấn: " . $conn->error);
    }

    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctor = $result->fetch_assoc();

    if (!$doctor) {
        throw new Exception('Không tìm thấy thông tin bác sĩ');
    }

    // Kiểm tra xem ngày đã được đăng ký nghỉ chưa
    $check_exists = "SELECT COUNT(*) as count FROM doctor_off_days 
                    WHERE doctor_id = ? AND off_date = ?";
    $stmt = $conn->prepare($check_exists);
    $stmt->bind_param('is', $doctor['doctor_id'], $off_date);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();

    if ($exists['count'] > 0) {
        throw new Exception('Ngày này đã được đăng ký nghỉ');
    }

    // Kiểm tra có lịch hẹn không
    $check_query = "SELECT COUNT(*) as count FROM appointments 
                   WHERE doctor_id = ? AND DATE(appointment_time) = ?
                   AND status != 'cancelled'";
    
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('is', $doctor['doctor_id'], $off_date);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result['count'] > 0) {
        throw new Exception('Không thể đăng ký nghỉ vì có lịch hẹn trong ngày này');
    }

    // Thêm ngày nghỉ
    $insert_query = "INSERT INTO doctor_off_days (doctor_id, off_date, reason) 
                    VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param('iss', $doctor['doctor_id'], $off_date, $reason);
    
    if (!$stmt->execute()) {
        throw new Exception("Lỗi thêm ngày nghỉ: " . $stmt->error);
    }

    echo json_encode(['success' => true, 'message' => 'Thêm ngày nghỉ thành công']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

