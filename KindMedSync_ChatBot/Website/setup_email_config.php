<?php
// SETUP EMAIL CONFIG - CẬP NHẬT SETTINGS EMAIL

require_once 'includes/db.php';

echo "<h1>🔧 SETUP EMAIL CONFIG</h1>";
echo "<style>body{font-family:Arial;margin:20px;line-height:1.6;} .success{color:green;} .error{color:red;} .info{color:blue;} .box{background:#f8f9fa;padding:15px;margin:10px 0;border-radius:5px;}</style>";

if ($conn->connect_error) {
    die("<div class='error'>❌ Lỗi kết nối database: " . $conn->connect_error . "</div>");
}

echo "<div class='box'>";
echo "<h3>📧 Cập nhật cài đặt SMTP...</h3>";

// Array các cài đặt SMTP
$smtp_settings = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => '587',
    'smtp_username' => 'medisyncnoreplybot@gmail.com',
    'smtp_password' => 'zvgk wleu zgyd ljyr',
    'smtp_secure' => 'tls',
    'email_from_name' => 'MediSyncNoreply',
    'email_from_address' => 'medisyncnoreplybot@gmail.com',
    'email_notifications_enabled' => '1',
    'email_welcome_enabled' => '1',
    'email_appointment_enabled' => '1',
    'email_order_enabled' => '1'
];

$updated = 0;
$inserted = 0;

foreach ($smtp_settings as $key => $value) {
    // Kiểm tra xem setting đã tồn tại chưa
    $check = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $check->bind_param("s", $key);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing setting
        $update = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $update->bind_param("ss", $value, $key);
        if ($update->execute()) {
            echo "<span class='success'>✅ Cập nhật $key = $value</span><br>";
            $updated++;
        } else {
            echo "<span class='error'>❌ Lỗi cập nhật $key</span><br>";
        }
    } else {
        // Insert new setting
        $insert = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $insert->bind_param("ss", $key, $value);
        if ($insert->execute()) {
            echo "<span class='success'>✅ Thêm mới $key = $value</span><br>";
            $inserted++;
        } else {
            echo "<span class='error'>❌ Lỗi thêm mới $key</span><br>";
        }
    }
}

echo "<br><strong>Kết quả:</strong><br>";
echo "- Cập nhật: $updated settings<br>";
echo "- Thêm mới: $inserted settings<br>";
echo "</div>";

// Hiển thị tất cả cài đặt email hiện tại
echo "<div class='box'>";
echo "<h3>📋 Cài đặt email hiện tại:</h3>";
$current_settings = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%' OR setting_key LIKE 'email_%' ORDER BY setting_key");

if ($current_settings->num_rows > 0) {
    while ($row = $current_settings->fetch_assoc()) {
        $display_value = ($row['setting_key'] == 'smtp_password') ? '****' : $row['setting_value'];
        echo "<strong>{$row['setting_key']}:</strong> {$display_value}<br>";
    }
} else {
    echo "<span class='error'>❌ Không có cài đặt email nào</span>";
}
echo "</div>";

// Tạo bảng email_logs nếu chưa có
echo "<div class='box'>";
echo "<h3>📊 Tạo bảng email logs...</h3>";
$create_table = "CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    status ENUM('success', 'failed', 'error') NOT NULL,
    error_message TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($create_table)) {
    echo "<span class='success'>✅ Bảng email_logs đã sẵn sàng</span><br>";
} else {
    echo "<span class='error'>❌ Lỗi tạo bảng email_logs: " . $conn->error . "</span><br>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h3>🚀 Bước tiếp theo:</h3>";
echo "<ol>";
echo "<li>Chạy file: <strong>quick_email_test.php</strong></li>";
echo "<li>Hoặc chạy: <strong>test_medisync_email.php</strong></li>";
echo "<li>Nhập email của bạn để test</li>";
echo "<li>Kiểm tra email (có thể ở thư mục spam)</li>";
echo "</ol>";
echo "</div>";

$conn->close();
?> 