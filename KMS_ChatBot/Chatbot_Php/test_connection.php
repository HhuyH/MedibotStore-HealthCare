<?php
$servername = "localhost";  // Địa chỉ máy chủ DB, thường là localhost
$username = "root"; // Tên đăng nhập DB
$password = ""; // Mật khẩu DB
$dbname = "kms";  // Tên cơ sở dữ liệu bạn muốn kết nối

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
} else {
    echo "Kết nối thành công đến cơ sở dữ liệu $dbname";
}

// Đóng kết nối khi không dùng nữa
$conn->close();
?>
