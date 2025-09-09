<?php
session_start();
require_once '../../includes/db.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập và quyền bác sĩ
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [2, 3])) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit();
}

try {
    // Lấy dữ liệu từ request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['appointment_id']) || !isset($data['status'])) {
        throw new Exception('Thiếu thông tin cần thiết');
    }

    $appointment_id = intval($data['appointment_id']);
    $status = $data['status'];

    // Validate status
    $allowed_statuses = ['confirmed', 'completed', 'cancelled'];
    if (!in_array($status, $allowed_statuses)) {
        throw new Exception('Trạng thái không hợp lệ');
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

    // Kiểm tra quyền cập nhật
    $check_query = "SELECT * FROM appointments 
                   WHERE appointment_id = ? AND doctor_id = ?";
    
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('ii', $appointment_id, $doctor['doctor_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Không tìm thấy lịch hẹn hoặc không có quyền cập nhật');
    }

    // Cập nhật trạng thái
    $update_query = "UPDATE appointments 
                    SET status = ?, updated_at = NOW() 
                    WHERE appointment_id = ? AND doctor_id = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('sii', $status, $appointment_id, $doctor['doctor_id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Lỗi cập nhật trạng thái: " . $stmt->error);
    }

    $status_text = [
        'confirmed' => 'xác nhận',
        'completed' => 'hoàn thành',
        'cancelled' => 'hủy'
    ];

    echo json_encode([
        'success' => true, 
        'message' => 'Đã ' . $status_text[$status] . ' lịch hẹn thành công'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 