<?php
session_start();
require_once '../../includes/db.php';

// Kiểm tra đăng nhập và quyền bác sĩ
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [2, 3])) {
    die(json_encode([
        'success' => false,
        'message' => 'Không có quyền truy cập'
    ]));
}

try {
    // Lấy dữ liệu từ request
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['off_day_id'])) {
        throw new Exception('Thiếu thông tin ngày nghỉ');
    }

    $off_day_id = (int)$data['off_day_id'];
    $user_id = $_SESSION['user_id'];

    // Lấy doctor_id từ user_id
    $doctor_query = "SELECT doctor_id FROM doctors WHERE user_id = ?";
    $stmt = $conn->prepare($doctor_query);
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị truy vấn: " . $conn->error);
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctor = $result->fetch_assoc();

    if (!$doctor) {
        throw new Exception('Không tìm thấy thông tin bác sĩ');
    }

    // Kiểm tra ngày nghỉ có thuộc về bác sĩ này không
    $check_query = "SELECT off_day_id FROM doctor_off_days 
                   WHERE off_day_id = ? AND doctor_id = ?";
    $stmt = $conn->prepare($check_query);
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị truy vấn kiểm tra: " . $conn->error);
    }

    $stmt->bind_param('ii', $off_day_id, $doctor['doctor_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Không tìm thấy ngày nghỉ hoặc không có quyền xóa');
    }

    // Kiểm tra xem có lịch hẹn nào trong ngày nghỉ không
    $check_appointments = "SELECT a.appointment_id 
                         FROM appointments a 
                         JOIN doctor_off_days d ON DATE(a.appointment_time) = d.off_date 
                         WHERE d.off_day_id = ? AND a.status IN ('pending', 'confirmed')";
    $stmt = $conn->prepare($check_appointments);
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị truy vấn kiểm tra lịch hẹn: " . $conn->error);
    }

    $stmt->bind_param('i', $off_day_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        throw new Exception('Không thể xóa ngày nghỉ vì đã có lịch hẹn');
    }

    // Thực hiện xóa ngày nghỉ
    $delete_query = "DELETE FROM doctor_off_days WHERE off_day_id = ? AND doctor_id = ?";
    $stmt = $conn->prepare($delete_query);
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị truy vấn xóa: " . $conn->error);
    }

    $stmt->bind_param('ii', $off_day_id, $doctor['doctor_id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Lỗi khi xóa ngày nghỉ: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception('Không có ngày nghỉ nào được xóa');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa ngày nghỉ thành công'
    ]);

} catch (Exception $e) {
    error_log("Error in delete-off-day.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

