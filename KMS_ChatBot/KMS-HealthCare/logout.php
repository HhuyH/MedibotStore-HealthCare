<?php
include 'includes/db.php';
session_start();

// Chỉ xử lý POST request từ AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Xóa remember token nếu có
    if (isset($_COOKIE['remember_token']) && isset($_SESSION['user_id'])) {
        $token = $_COOKIE['remember_token'];
        $user_id = $_SESSION['user_id'];
        
        // Xóa token khỏi database
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ? AND token = ?");
        $stmt->bind_param('is', $user_id, $token);
        $stmt->execute();
        
        // Xóa cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }

    // Hủy session
    session_destroy();
    
    // Trả về JSON response cho JavaScript
    echo json_encode(['status' => 'success', 'message' => 'Đăng xuất thành công']);
    exit;
}

// Nếu truy cập trực tiếp, redirect về trang chính
header('Location: index.php');
exit; 