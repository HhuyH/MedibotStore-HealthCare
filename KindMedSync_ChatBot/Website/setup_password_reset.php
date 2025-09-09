<?php
require_once 'includes/db.php';

echo "=== Thiết lập bảng Password Reset Tokens ===\n";

try {
    // Tạo bảng password_reset_tokens
    $sql = "
    CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at TIMESTAMP NOT NULL,
        used BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        used_at TIMESTAMP NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        INDEX idx_token (token),
        INDEX idx_email (email),
        INDEX idx_expires (expires_at),
        INDEX idx_used (used)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ Bảng password_reset_tokens đã được tạo thành công!\n";
    } else {
        echo "❌ Lỗi tạo bảng: " . $conn->error . "\n";
    }
    
    // Tạo event để tự động dọn dẹp token cũ
    $event_sql = "
    CREATE EVENT IF NOT EXISTS cleanup_expired_password_tokens
    ON SCHEDULE EVERY 1 HOUR
    DO
    DELETE FROM password_reset_tokens 
    WHERE expires_at < NOW() OR (used = TRUE AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY))
    ";
    
    if ($conn->query($event_sql) === TRUE) {
        echo "✅ Event cleanup_expired_password_tokens đã được tạo thành công!\n";
    } else {
        echo "❌ Lỗi tạo event: " . $conn->error . "\n";
    }
    
    // Kiểm tra bảng đã tồn tại
    $check_sql = "SHOW TABLES LIKE 'password_reset_tokens'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows > 0) {
        echo "✅ Bảng password_reset_tokens đã tồn tại trong database!\n";
        
        // Hiển thị cấu trúc bảng
        $structure_sql = "DESCRIBE password_reset_tokens";
        $structure_result = $conn->query($structure_sql);
        
        echo "\n📋 Cấu trúc bảng password_reset_tokens:\n";
        echo "+-----------------+--------------+------+-----+---------+----------------+\n";
        echo "| Field           | Type         | Null | Key | Default | Extra          |\n";
        echo "+-----------------+--------------+------+-----+---------+----------------+\n";
        
        while ($row = $structure_result->fetch_assoc()) {
            printf("| %-15s | %-12s | %-4s | %-3s | %-7s | %-14s |\n",
                $row['Field'],
                $row['Type'],
                $row['Null'],
                $row['Key'],
                $row['Default'] ?? 'NULL',
                $row['Extra']
            );
        }
        echo "+-----------------+--------------+------+-----+---------+----------------+\n";
    }
    
    echo "\n✅ Thiết lập hoàn tất!\n";
    echo "🔧 Bây giờ bạn có thể sử dụng tính năng quên mật khẩu.\n";
    echo "📧 Hệ thống sẽ gửi email với link reset password có hiệu lực 24 giờ.\n";
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}

$conn->close();
?> 