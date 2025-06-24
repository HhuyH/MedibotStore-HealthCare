<?php
session_start();
require_once '../../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $notifications = [];
    
    // 1. Lấy lịch hẹn mới (trong 24h qua)
    $appointmentQuery = "
        SELECT 
            a.appointment_id,
            a.appointment_time,
            a.created_at,
            COALESCE(ui.full_name, gu.full_name, 'Khách hàng') as patient_name,
            a.status,
            'appointment' as type
        FROM appointments a
        LEFT JOIN users_info ui ON a.user_id = ui.user_id
        LEFT JOIN guest_users gu ON a.guest_id = gu.guest_id
        WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND a.status = 'pending'
        ORDER BY a.created_at DESC
        LIMIT 5
    ";
    
    $appointmentResult = $conn->query($appointmentQuery);
    if ($appointmentResult) {
        while ($row = $appointmentResult->fetch_assoc()) {
            $timeAgo = getTimeAgo($row['created_at']);
            $notifications[] = [
                'id' => 'apt_' . $row['appointment_id'],
                'type' => 'appointment',
                'icon' => 'fas fa-calendar-plus',
                'icon_color' => 'bg-primary',
                'title' => 'Lịch hẹn mới',
                'message' => $row['patient_name'] . ' đã đặt lịch khám',
                'time' => $timeAgo,
                'link' => 'appointment-view.php?id=' . $row['appointment_id'],
                'created_at' => $row['created_at']
            ];
        }
    }
    
    // 2. Lấy đơn hàng mới (trong 24h qua)
    $orderQuery = "
        SELECT 
            o.order_id,
            o.total,
            o.order_date,
            o.status,
            COALESCE(ui.full_name, 'Khách hàng') as customer_name,
            'order' as type
        FROM orders o
        LEFT JOIN users_info ui ON o.user_id = ui.user_id
        WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND o.status IN ('pending', 'confirmed')
        ORDER BY o.order_date DESC
        LIMIT 5
    ";
    
    $orderResult = $conn->query($orderQuery);
    if ($orderResult) {
        while ($row = $orderResult->fetch_assoc()) {
            $timeAgo = getTimeAgo($row['order_date']);
            $notifications[] = [
                'id' => 'order_' . $row['order_id'],
                'type' => 'order',
                'icon' => 'fas fa-shopping-cart',
                'icon_color' => 'bg-success',
                'title' => 'Đơn hàng mới',
                'message' => $row['customer_name'] . ' đã đặt hàng (' . number_format($row['total']) . 'đ)',
                'time' => $timeAgo,
                'link' => 'orders.php?id=' . $row['order_id'],
                'created_at' => $row['order_date']
            ];
        }
    }
    
    // 3. Lấy người dùng mới đăng ký (trong 24h qua)
    $userQuery = "
        SELECT 
            u.user_id,
            u.username,
            u.created_at,
            ui.full_name,
            r.role_name,
            'user' as type
        FROM users u
        LEFT JOIN users_info ui ON u.user_id = ui.user_id
        LEFT JOIN roles r ON u.role_id = r.role_id
        WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND u.role_id IN (2, 3) -- Chỉ bệnh nhân và bác sĩ
        ORDER BY u.created_at DESC
        LIMIT 3
    ";
    
    $userResult = $conn->query($userQuery);
    if ($userResult) {
        while ($row = $userResult->fetch_assoc()) {
            $timeAgo = getTimeAgo($row['created_at']);
            $roleText = $row['role_name'] == 'Patient' ? 'bệnh nhân' : 'bác sĩ';
            $notifications[] = [
                'id' => 'user_' . $row['user_id'],
                'type' => 'user',
                'icon' => 'fas fa-user-plus',
                'icon_color' => 'bg-info',
                'title' => 'Người dùng mới',
                'message' => ($row['full_name'] ?: $row['username']) . ' đăng ký làm ' . $roleText,
                'time' => $timeAgo,
                'link' => 'user-view.php?id=' . $row['user_id'],
                'created_at' => $row['created_at']
            ];
        }
    }
    
    // 4. Lấy các thông báo hệ thống (nếu có bảng notifications)
    $systemQuery = "
        SELECT COUNT(*) as has_table
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'notifications'
    ";
    
    $systemResult = $conn->query($systemQuery);
    $hasNotificationTable = $systemResult && $systemResult->fetch_assoc()['has_table'] > 0;
    
    if ($hasNotificationTable) {
        $notifQuery = "
            SELECT 
                notification_id,
                title,
                message,
                type,
                created_at,
                is_read
            FROM notifications
            WHERE target_role = 'admin' 
            OR target_user_id = {$_SESSION['user_id']}
            ORDER BY created_at DESC
            LIMIT 5
        ";
        
        $notifResult = $conn->query($notifQuery);
        if ($notifResult) {
            while ($row = $notifResult->fetch_assoc()) {
                $timeAgo = getTimeAgo($row['created_at']);
                $iconMap = [
                    'info' => ['fas fa-info-circle', 'bg-info'],
                    'warning' => ['fas fa-exclamation-triangle', 'bg-warning'],
                    'error' => ['fas fa-exclamation-circle', 'bg-danger'],
                    'success' => ['fas fa-check-circle', 'bg-success']
                ];
                
                $icon = $iconMap[$row['type']] ?? ['fas fa-bell', 'bg-secondary'];
                
                $notifications[] = [
                    'id' => 'notif_' . $row['notification_id'],
                    'type' => 'system',
                    'icon' => $icon[0],
                    'icon_color' => $icon[1],
                    'title' => $row['title'],
                    'message' => $row['message'],
                    'time' => $timeAgo,
                    'link' => '#',
                    'created_at' => $row['created_at'],
                    'is_read' => $row['is_read']
                ];
            }
        }
    }
    
    // Sắp xếp theo thời gian tạo
    usort($notifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Lấy 10 thông báo mới nhất
    $notifications = array_slice($notifications, 0, 10);
    
    // Đếm tổng số thông báo chưa đọc
    $unreadCount = 0;
    foreach ($notifications as $notif) {
        if (!isset($notif['is_read']) || !$notif['is_read']) {
            $unreadCount++;
        }
    }
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
        'total_count' => count($notifications)
    ]);
    
} catch (Exception $e) {
    error_log("Notification API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Có lỗi xảy ra khi lấy thông báo',
        'details' => $e->getMessage()
    ]);
}

function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Vừa xong';
    if ($time < 3600) return floor($time/60) . ' phút trước';
    if ($time < 86400) return floor($time/3600) . ' giờ trước';
    if ($time < 2592000) return floor($time/86400) . ' ngày trước';
    if ($time < 31104000) return floor($time/2592000) . ' tháng trước';
    return floor($time/31104000) . ' năm trước';
}
?> 