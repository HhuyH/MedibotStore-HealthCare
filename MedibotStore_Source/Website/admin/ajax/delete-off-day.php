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
$off_day_id = isset($data['off_day_id']) ? (int)$data['off_day_id'] : 0;

try {
    // Kiểm tra quyền truy cập
    $check_query = "SELECT d.doctor_id 
                   FROM doctor_off_days do
                   JOIN doctors d ON do.doctor_id = d.doctor_id
                   WHERE do.off_day_id = ? AND d.user_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('ii', $off_day_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Unauthorized access');
    }

    // Xóa ngày nghỉ
    $delete_query = "DELETE FROM doctor_off_days WHERE off_day_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param('i', $off_day_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Delete failed');
    }

} catch (Exception $e) {
    error_log("Error deleting off day: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 