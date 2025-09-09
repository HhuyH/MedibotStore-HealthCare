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

    // Lấy thông tin ca trực
    $day_of_week = intval($_POST['day_of_week']);
    
    // Validate input
    if ($day_of_week < 1 || $day_of_week > 7) {
        throw new Exception('Ngày không hợp lệ');
    }

    // Lấy thông tin thời gian từ form
    $morning_start = $_POST['morning_start'] ? $_POST['morning_start'] . ':00' : null;
    $morning_end = $_POST['morning_end'] ? $_POST['morning_end'] . ':00' : null;
    $afternoon_start = $_POST['afternoon_start'] ? $_POST['afternoon_start'] . ':00' : null;
    $afternoon_end = $_POST['afternoon_end'] ? $_POST['afternoon_end'] . ':00' : null;

    // Mảng lưu các ca làm việc cần thêm
    $shifts = [];

    // Thêm ca sáng nếu có
    if ($morning_start && $morning_end) {
        $shifts[] = [
            'start_time' => $morning_start,
            'end_time' => $morning_end
        ];
    }

    // Thêm ca chiều nếu có
    if ($afternoon_start && $afternoon_end) {
        $shifts[] = [
            'start_time' => $afternoon_start,
            'end_time' => $afternoon_end
        ];
    }

    if (empty($shifts)) {
        throw new Exception('Vui lòng chọn ít nhất một ca làm việc');
    }

    // Xóa các ca trực cũ của ngày này (nếu là edit)
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $delete_query = "DELETE FROM doctor_schedules WHERE doctor_id = ? AND day_of_week = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param('ii', $doctor['doctor_id'], $day_of_week);
        $stmt->execute();
    }

    // Thêm từng ca trực mới
    foreach ($shifts as $shift) {
        // Kiểm tra xem ca trực có bị trùng không
        $check_query = "SELECT schedule_id FROM doctor_schedules 
                       WHERE doctor_id = ? AND day_of_week = ? 
                       AND ((? BETWEEN start_time AND end_time) 
                       OR (? BETWEEN start_time AND end_time))";
        
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param('iiss', $doctor['doctor_id'], $day_of_week, $shift['start_time'], $shift['end_time']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Ca trực ' . substr($shift['start_time'], 0, 5) . ' - ' . substr($shift['end_time'], 0, 5) . ' đã tồn tại');
        }

        // Thêm ca trực mới
        $insert_query = "INSERT INTO doctor_schedules 
                        (doctor_id, day_of_week, start_time, end_time, is_available) 
                        VALUES (?, ?, ?, ?, 1)";
        
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param('iiss', $doctor['doctor_id'], $day_of_week, $shift['start_time'], $shift['end_time']);
        
        if (!$stmt->execute()) {
            throw new Exception("Lỗi thêm ca trực: " . $stmt->error);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Thêm ca trực thành công']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 