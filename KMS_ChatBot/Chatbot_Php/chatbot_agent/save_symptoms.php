<?php
header("Content-Type: application/json");
session_start();
include_once __DIR__ . '/../includes/db.php';

$input = json_decode(file_get_contents("php://input"), true);
$user_id = intval($input['user_id'] ?? 0);
$symptoms = $input['symptoms'] ?? [];

if (!$user_id || empty($symptoms)) {
    echo json_encode(["status" => "error", "message" => "Thiếu user_id hoặc symptoms."]);
    exit;
}

$date = date("Y-m-d");
$stmt = $conn->prepare("INSERT INTO user_symptom_history (user_id, symptom_id, record_date) VALUES (?, ?, ?)");

foreach ($symptoms as $symptom) {
    $stmt->bind_param("iis", $user_id, $symptom['id'], $date);
    $stmt->execute();
}

echo json_encode(["status" => "success", "message" => "Đã lưu triệu chứng."]);
