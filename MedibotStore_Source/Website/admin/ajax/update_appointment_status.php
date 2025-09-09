<?php
session_start();
require_once '../../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit();
}

// Kiểm tra method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method không được phép']);
    exit();
}

// Lấy dữ liệu JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['appointment_id']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit();
}

$appointment_id = (int)$input['appointment_id'];
$status = $input['status'];

// Validate status
$valid_statuses = ['pending', 'confirmed', 'completed', 'canceled'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
    exit();
}

try {
    // Kiểm tra appointment có tồn tại không
    $check_stmt = $conn->prepare("SELECT appointment_id FROM appointments WHERE appointment_id = ?");
    $check_stmt->bind_param("i", $appointment_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy lịch hẹn']);
        exit();
    }
    
    // Cập nhật trạng thái
    $update_stmt = $conn->prepare("UPDATE appointments SET status = ?, updated_at = NOW() WHERE appointment_id = ?");
    $update_stmt->bind_param("si", $status, $appointment_id);
    
    if ($update_stmt->execute()) {
        // Log activity (optional)
        $admin_id = $_SESSION['user_id'];
        $action = "Cập nhật trạng thái lịch hẹn #$appointment_id thành '$status'";
        
        // Có thể thêm log vào bảng activity_logs nếu có
        // $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, created_at) VALUES (?, ?, NOW())");
        // $log_stmt->bind_param("is", $admin_id, $action);
        // $log_stmt->execute();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Cập nhật trạng thái thành công',
            'new_status' => $status
        ]);
    } else {
        throw new Exception('Không thể cập nhật trạng thái');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}
?> 