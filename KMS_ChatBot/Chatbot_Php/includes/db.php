<?php
$host = 'localhost';
$db   = 'kms'; // Đổi tên DB nếu khác
$user = 'root';
$pass = ''; // XAMPP mặc định không có password

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Kết nối CSDL thất bại: ' . $conn->connect_error);
}
?> 