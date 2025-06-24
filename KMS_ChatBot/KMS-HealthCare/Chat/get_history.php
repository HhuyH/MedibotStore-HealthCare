<?php
session_start();

if (isset($_SESSION['user_id'])) {
    // User đã đăng nhập: trả về history lưu trong session hoặc DB
    echo json_encode($_SESSION['history'] ?? []);
} else {
    // Không xóa history cho user chưa đăng nhập,
    // để giữ cache session trên trình duyệt này
    echo json_encode($_SESSION['history'] ?? []);
}

?>