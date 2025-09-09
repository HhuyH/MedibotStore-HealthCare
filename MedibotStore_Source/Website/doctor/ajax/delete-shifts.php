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
    // Lấy dữ liệu từ request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['day_of_week'])) {
        throw new Exception('Thiếu thông tin ngày cần xóa');
    }

    $day_of_week = intval($data['day_of_week']);

    // Validate input
    if ($day_of_week < 1 || $day_of_week > 7) {
        throw new Exception('Ngày không hợp lệ');
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

    // Kiểm tra ca trực có tồn tại không
    $check_exists = "SELECT COUNT(*) as count FROM doctor_schedules 
                    WHERE doctor_id = ? AND day_of_week = ?";
    $stmt = $conn->prepare($check_exists);
    $stmt->bind_param('ii', $doctor['doctor_id'], $day_of_week);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();

    if ($exists['count'] === 0) {
        throw new Exception('Không tìm thấy ca trực nào để xóa');
    }

    // Kiểm tra có lịch hẹn không
    $check_query = "SELECT COUNT(*) as count FROM appointments a
                   JOIN doctor_schedules ds ON a.doctor_id = ds.doctor_id
                   WHERE ds.doctor_id = ? AND ds.day_of_week = ?
                   AND a.appointment_date >= CURDATE()
                   AND a.status != 'cancelled'";
    
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('ii', $doctor['doctor_id'], $day_of_week);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result['count'] > 0) {
        throw new Exception('Không thể xóa ca trực có lịch hẹn');
    }

    // Xóa ca trực
    $delete_query = "DELETE FROM doctor_schedules 
                    WHERE doctor_id = ? AND day_of_week = ?";
    
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param('ii', $doctor['doctor_id'], $day_of_week);
    
    if (!$stmt->execute()) {
        throw new Exception("Lỗi xóa ca trực: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception('Không tìm thấy ca trực để xóa');
    }

    echo json_encode(['success' => true, 'message' => 'Xóa ca trực thành công']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 