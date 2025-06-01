<?php
session_start();
header('Content-Type: application/json');

// Nhận dữ liệu JSON gửi từ client
$data = json_decode(file_get_contents("php://input"), true);

// Đảm bảo có mảng history
if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = [];
}

// Cho dù có user_id hay không, vẫn lưu history
$_SESSION['history'][] = [
    "role" => $data['role'],
    "content" => $data['content']
];
