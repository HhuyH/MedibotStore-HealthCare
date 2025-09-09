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
    
    if (!isset($data['day_of_week']) || !isset($data['is_available'])) {
        throw new Exception('Thiếu thông tin cần thiết');
    }

    $day_of_week = intval($data['day_of_week']);
    $is_available = intval($data['is_available']);

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

    // Cập nhật trạng thái
    $update_query = "UPDATE doctor_schedules 
                    SET is_available = ?
                    WHERE doctor_id = ? AND day_of_week = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('iii', $is_available, $doctor['doctor_id'], $day_of_week);
    
    if (!$stmt->execute()) {
        throw new Exception("Lỗi cập nhật trạng thái: " . $stmt->error);
    }

    echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 