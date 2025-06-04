<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "Bạn chưa đăng nhập, không thể lưu triệu chứng.";
    exit;
}

$user_id = $_SESSION['user_id'];
$date = date('Y-m-d');

if (!empty($_SESSION['user_symptoms'])) {
    foreach ($_SESSION['user_symptoms'] as $symptom) {
        $symptom_id = intval($symptom['symptom_id']);

        $sql = "INSERT INTO user_symptom_history (user_id, symptom_id, record_date, notes) VALUES (?, ?, ?, '')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $user_id, $symptom_id, $date);
        $stmt->execute();
    }
    // Xóa triệu chứng tạm sau khi lưu
    unset($_SESSION['user_symptoms']);
    echo "Lưu triệu chứng thành công.";
} else {
    echo "Không có triệu chứng để lưu.";
}
?>
