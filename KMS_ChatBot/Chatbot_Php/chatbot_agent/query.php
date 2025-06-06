<?php
header("Content-Type: application/json");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: 0");

require_once "../includes/db.php"; // Kết nối CSDL (mysqli)

if (!isset($_POST["sql"])) {
    echo json_encode(["error" => "Không có câu truy vấn được gửi."]);
    exit;
}

$sql = $_POST["sql"];
$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["error" => "Lỗi truy vấn: " . $conn->error]);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(["data" => $data]);
exit;
