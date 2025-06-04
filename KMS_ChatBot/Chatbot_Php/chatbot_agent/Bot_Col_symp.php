<?php
session_start();

// Tạo mảng triệu chứng nếu chưa có
if (!isset($_SESSION['user_symptoms'])) {
    $_SESSION['user_symptoms'] = [];
}

// Kiểm tra có dữ liệu symptom_id và symptom_name trong POST không
if (isset($_POST['symptom_id']) && isset($_POST['symptom_name'])) {
    $symptom_id = $_POST['symptom_id'];
    $symptom_name = $_POST['symptom_name'];

    // Thêm triệu chứng mới vào session
    $_SESSION['user_symptoms'][] = [
        'symptom_id' => $symptom_id,
        'symptom_name' => $symptom_name
    ];
}

// Hiển thị lại danh sách triệu chứng hiện có
if (!empty($_SESSION['user_symptoms'])) {
    echo "Danh sách triệu chứng đã ghi nhận:<br>";
    foreach ($_SESSION['user_symptoms'] as $symptom) {
        echo "- " . htmlspecialchars($symptom['symptom_name']) . "<br>";
    }
} else {
    echo "Chưa có triệu chứng nào được ghi nhận.";
}
?>
