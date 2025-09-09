<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_GET['doctor_id']) || !isset($_GET['date'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit;
}

$doctor_id = (int)$_GET['doctor_id'];
$date = $_GET['date'];
$day_of_week = (int)date('N', strtotime($date)); // 1-7: Thứ 2 - Chủ nhật

try {
    // Kiểm tra ngày hiện tại
    $current_date = date('Y-m-d');
    $current_time = date('H:i');
    
    if ($date < $current_date) {
        echo json_encode([
            'success' => true,
            'slots' => [],
            'message' => 'Không thể đặt lịch cho ngày trong quá khứ'
        ]);
        exit;
    }

    // Kiểm tra ngày nghỉ
    $off_day_check = $conn->prepare("
        SELECT 1 FROM doctor_off_days 
        WHERE doctor_id = ? AND off_date = ?
    ");
    $off_day_check->bind_param('is', $doctor_id, $date);
    $off_day_check->execute();
    if ($off_day_check->get_result()->num_rows > 0) {
        echo json_encode([
            'success' => true,
            'slots' => [],
            'message' => 'Bác sĩ không làm việc vào ngày này'
        ]);
        exit;
    }

    // Lấy tất cả lịch làm việc của bác sĩ trong ngày
    $schedule_sql = "
        SELECT start_time, end_time 
        FROM doctor_schedules 
        WHERE doctor_id = ? 
        AND day_of_week = ?
        AND is_available = 1
        ORDER BY start_time
    ";
    $schedule_stmt = $conn->prepare($schedule_sql);
    $schedule_stmt->bind_param('ii', $doctor_id, $day_of_week);
    $schedule_stmt->execute();
    $schedules_result = $schedule_stmt->get_result();
    $schedules = [];
    
    while ($row = $schedules_result->fetch_assoc()) {
        $schedules[] = $row;
    }

    if (empty($schedules)) {
        echo json_encode([
            'success' => true,
            'slots' => [],
            'message' => 'Bác sĩ không có lịch làm việc vào ngày này'
        ]);
        exit;
    }

    // Lấy các lịch hẹn đã đặt
    $booked_sql = "
        SELECT appointment_time 
        FROM appointments 
        WHERE doctor_id = ? 
        AND DATE(appointment_time) = ? 
        AND status IN ('pending', 'confirmed')
    ";
    $booked_stmt = $conn->prepare($booked_sql);
    $booked_stmt->bind_param('is', $doctor_id, $date);
    $booked_stmt->execute();
    $booked_result = $booked_stmt->get_result();
    
    $booked_slots = [];
    while ($row = $booked_result->fetch_assoc()) {
        $booked_slots[] = date('H:i', strtotime($row['appointment_time']));
    }

    // Tạo các time slots từ tất cả các ca làm việc
    $slots = [];
    $interval = 20 * 60; // 30 phút

    foreach ($schedules as $schedule) {
        $start = strtotime($schedule['start_time']);
        $end = strtotime($schedule['end_time']);

        for ($time = $start; $time < $end; $time += $interval) {
            $slot_time = date('H:i', $time);
            
            // Kiểm tra nếu là ngày hiện tại và thời gian đã qua
            $is_past = false;
            if ($date === $current_date) {
                $is_past = strtotime($slot_time) <= strtotime($current_time);
            }

            // Chỉ thêm slot nếu chưa tồn tại và không phải thời gian đã qua
            if (!in_array($slot_time, array_column($slots, 'time')) && !$is_past) {
                $slots[] = [
                    'time' => $slot_time,
                    'booked' => in_array($slot_time, $booked_slots)
                ];
            }
        }
    }

    // Sắp xếp các slots theo thời gian
    usort($slots, function($a, $b) {
        return strtotime($a['time']) - strtotime($b['time']);
    });

    // Loại bỏ các slot trùng lặp
    $slots = array_values(array_unique($slots, SORT_REGULAR));
    
    echo json_encode([
        'success' => true,
        'slots' => $slots
    ]);
    
} catch (Exception $e) {
    error_log("Error in get-time-slots.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi lấy thông tin lịch khám: ' . $e->getMessage()
    ]);
}