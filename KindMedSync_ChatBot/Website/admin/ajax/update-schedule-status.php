<?php
session_start();
require_once '../../includes/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Lấy dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);
$schedule_id = isset($data['schedule_id']) ? (int)$data['schedule_id'] : 0;
$is_available = isset($data['is_available']) ? (int)$data['is_available'] : 0;

try {
    // Kiểm tra quyền truy cập
    $check_query = "SELECT d.doctor_id 
                   FROM doctor_schedules ds
                   JOIN doctors d ON ds.doctor_id = d.doctor_id
                   WHERE ds.schedule_id = ? AND d.user_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('ii', $schedule_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Unauthorized access');
    }

    // Cập nhật trạng thái
    $update_query = "UPDATE doctor_schedules 
                    SET is_available = ?, 
                        updated_at = CURRENT_TIMESTAMP
                    WHERE schedule_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('ii', $is_available, $schedule_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Update failed');
    }

} catch (Exception $e) {
    error_log("Error updating schedule status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 