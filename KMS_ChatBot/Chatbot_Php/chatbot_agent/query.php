<?php
header("Content-Type: application/json");

require_once "../includes/db.php"; // Kết nối CSDL (mysqli)

// Kiểm tra nếu có tham số sql
if (!isset($_POST["sql"])) {
    echo json_encode(["error" => "Không có câu truy vấn được gửi."]);
    exit;
}

$sql = $_POST["sql"];

// Thực thi truy vấn
$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["error" => "Lỗi truy vấn: " . $conn->error]);
    exit;
}

// Gửi dữ liệu dạng JSON
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(["data" => $data]);
