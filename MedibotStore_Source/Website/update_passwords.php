<?php
/**
 * Script để chuyển từ hashed password về plain text
 * CHÚ Ý: Việc này chỉ có thể làm cho password mặc định đã biết trước
 */

include 'includes/db.php';

echo "<h2>Revert Password Script</h2>";
echo "<p>Đang chuyển đổi password về plain text...</p>";

try {
    // Danh sách password mặc định đã biết
    $known_passwords = [
        'admin' => 'admin123',
        // Thêm các tài khoản mặc định khác nếu có
    ];
    
    // Lấy tất cả users
    $stmt = $conn->prepare("SELECT user_id, username, password FROM users");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $updated_count = 0;
    $total_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $total_count++;
        $user_id = $row['user_id'];
        $username = $row['username'];
        $current_password = $row['password'];
        
        // Kiểm tra xem password có bị hash không
        $password_info = password_get_info($current_password);
        
        if ($password_info['algo'] !== null) {
            // Password đã bị hash, thử chuyển về plain text
            if (isset($known_passwords[$username])) {
                $plain_password = $known_passwords[$username];
                
                // Verify xem hash có đúng không
                if (password_verify($plain_password, $current_password)) {
                    // Update về plain text
                    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $update_stmt->bind_param('si', $plain_password, $user_id);
                    
                    if ($update_stmt->execute()) {
                        echo "<p style='color: green;'>✅ Reverted password for user: $username</p>";
                        $updated_count++;
                    } else {
                        echo "<p style='color: red;'>❌ Failed to revert password for user: $username</p>";
                    }
                } else {
                    echo "<p style='color: orange;'>⚠️ Hash verification failed for user: $username</p>";
                }
            } else {
                echo "<p style='color: orange;'>⚠️ Unknown password for user: $username - cannot revert</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ️ Password already plain text for user: $username</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Kết quả:</h3>";
    echo "<p>Tổng số users: $total_count</p>";
    echo "<p>Số password đã revert: $updated_count</p>";
    
    if ($updated_count > 0) {
        echo "<p style='color: green; font-weight: bold;'>✅ Hoàn thành! Password đã được chuyển về plain text.</p>";
        echo "<p style='color: orange;'>⚠️ Hãy xóa file này sau khi hoàn tất để đảm bảo bảo mật!</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ Không có password nào cần revert.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Lỗi: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='login.php'>Đăng nhập</a> | <a href='test_db.php'>Test Database</a></p>";
?> 