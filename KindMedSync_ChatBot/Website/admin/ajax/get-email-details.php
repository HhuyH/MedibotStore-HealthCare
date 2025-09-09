<?php
header('Content-Type: application/json');
session_start();
include '../../includes/db.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID email không hợp lệ']);
    exit;
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM email_logs WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Email không tồn tại']);
    exit;
}

$email = $result->fetch_assoc();
echo json_encode(['success' => true, 'email' => $email]);
?> 