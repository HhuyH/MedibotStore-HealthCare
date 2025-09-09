<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

try {
    $conn->begin_transaction();
    
    $messages = [];
    
    // 1. Kiểm tra và tạo bảng user_addresses nếu chưa có hoặc sai cấu trúc
    $result = $conn->query("SHOW TABLES LIKE 'user_addresses'");
    if ($result->num_rows == 0) {
        // Tạo bảng user_addresses mới
        $sql = "
            CREATE TABLE user_addresses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                address_line VARCHAR(255) NOT NULL,
                ward VARCHAR(100),
                district VARCHAR(100),
                city VARCHAR(100),
                postal_code VARCHAR(20),
                country VARCHAR(100) DEFAULT 'Vietnam',
                is_default TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";
        
        if ($conn->query($sql)) {
            $messages[] = "Tạo bảng user_addresses thành công";
        } else {
            throw new Exception("Lỗi tạo bảng user_addresses: " . $conn->error);
        }
    } else {
        // Kiểm tra cấu trúc bảng hiện tại
        $result = $conn->query("DESCRIBE user_addresses");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        // Kiểm tra primary key
        $result = $conn->query("SHOW KEYS FROM user_addresses WHERE Key_name = 'PRIMARY'");
        $primary_key = $result->fetch_assoc()['Column_name'] ?? '';
        
        if ($primary_key === 'address_id') {
            // Cần đổi từ address_id sang id
            $messages[] = "Phát hiện primary key sai (address_id), đang sửa...";
            
            // Drop foreign keys trước
            $conn->query("ALTER TABLE orders DROP FOREIGN KEY IF EXISTS orders_ibfk_2");
            
            // Đổi tên cột
            $conn->query("ALTER TABLE user_addresses CHANGE address_id id INT AUTO_INCREMENT PRIMARY KEY");
            
            // Tạo lại foreign key
            $conn->query("ALTER TABLE orders ADD CONSTRAINT orders_ibfk_2 FOREIGN KEY (address_id) REFERENCES user_addresses(id) ON DELETE SET NULL");
            
            $messages[] = "Đã sửa primary key từ address_id thành id";
        }
        
        $messages[] = "Bảng user_addresses đã tồn tại với cấu trúc đúng";
    }
    
    // 2. Kiểm tra và sửa bảng orders
    $result = $conn->query("DESCRIBE orders");
    if (!$result) {
        throw new Exception("Bảng orders không tồn tại");
    }
    
    // Kiểm tra cột address_id trong orders
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = $row;
    }
    
    if (!isset($columns['address_id'])) {
        // Thêm cột address_id nếu chưa có
        $sql = "ALTER TABLE orders ADD COLUMN address_id INT NULL AFTER user_id";
        if ($conn->query($sql)) {
            $messages[] = "Thêm cột address_id vào bảng orders";
        }
        
        // Thêm foreign key constraint
        $sql = "ALTER TABLE orders ADD CONSTRAINT orders_ibfk_2 FOREIGN KEY (address_id) REFERENCES user_addresses(id) ON DELETE SET NULL";
        if ($conn->query($sql)) {
            $messages[] = "Thêm foreign key constraint cho address_id";
        }
    }
    
    // 3. Tạo dữ liệu mẫu cho user_addresses nếu chưa có
    $result = $conn->query("SELECT COUNT(*) as count FROM user_addresses");
    $count = $result->fetch_assoc()['count'];
    
    if ($count == 0) {
        // Thêm địa chỉ mẫu cho các user hiện có
        $users_result = $conn->query("SELECT user_id FROM users LIMIT 3");
        if ($users_result->num_rows > 0) {
            $sample_addresses = [
                ['address_line' => '123 Đường ABC', 'ward' => 'Phường 1', 'district' => 'Quận 1', 'city' => 'TP.HCM'],
                ['address_line' => '456 Đường XYZ', 'ward' => 'Phường 2', 'district' => 'Quận 3', 'city' => 'TP.HCM'],
                ['address_line' => '789 Đường DEF', 'ward' => 'Phường 3', 'district' => 'Quận 5', 'city' => 'TP.HCM']
            ];
            
            $i = 0;
            while ($user = $users_result->fetch_assoc() && $i < count($sample_addresses)) {
                $addr = $sample_addresses[$i];
                $sql = "INSERT INTO user_addresses (user_id, address_line, ward, district, city, is_default) VALUES (?, ?, ?, ?, ?, 1)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('issss', $user['user_id'], $addr['address_line'], $addr['ward'], $addr['district'], $addr['city']);
                $stmt->execute();
                $i++;
            }
            $messages[] = "Thêm địa chỉ mẫu cho users";
        }
    }
    
    // 4. Kiểm tra các foreign key constraints
    $sql = "
        SELECT 
            CONSTRAINT_NAME,
            TABLE_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND REFERENCED_TABLE_NAME IS NOT NULL
        AND TABLE_NAME IN ('orders', 'order_items', 'user_addresses')
    ";
    
    $result = $conn->query($sql);
    $constraints = [];
    while ($row = $result->fetch_assoc()) {
        $constraints[] = $row;
    }
    
    $messages[] = "Kiểm tra " . count($constraints) . " foreign key constraints";
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => implode('. ', $messages),
        'constraints' => $constraints
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 