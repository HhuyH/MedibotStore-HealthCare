<?php
session_start();
header('Content-Type: application/json');

// Xóa biến history trong session
unset($_SESSION['history']);

echo json_encode([
    "status" => "success",
    "message" => "Lịch sử hội thoại đã được reset."
]);
