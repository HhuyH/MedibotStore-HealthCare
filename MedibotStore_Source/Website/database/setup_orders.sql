-- Tạo database nếu chưa có
CREATE DATABASE IF NOT EXISTS qickmed CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE qickmed;

-- Bảng users (nếu chưa có)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    avatar VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng categories (nếu chưa có)
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng products (nếu chưa có)
CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(16, 0) NOT NULL,
    discount_price DECIMAL(16, 0),
    stock INT DEFAULT 0,
    category_id INT,
    image_url VARCHAR(255),
    images TEXT, -- JSON array of image URLs
    brand VARCHAR(100),
    sku VARCHAR(50) UNIQUE,
    weight DECIMAL(8, 2),
    dimensions VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    meta_title VARCHAR(200),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_price (price),
    INDEX idx_active (is_active),
    INDEX idx_featured (is_featured)
);

-- Bảng user_addresses: Địa chỉ giao hàng của người dùng
CREATE TABLE IF NOT EXISTS user_addresses (
    address_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address_line TEXT NOT NULL,
    ward VARCHAR(100),
    district VARCHAR(100),
    city VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20),
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_default (is_default)
);

-- Bảng orders: Đơn hàng của người dùng
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_id INT,
    shipping_address TEXT,
    total DECIMAL(16, 0),
    payment_method VARCHAR(50),
    payment_status VARCHAR(50) DEFAULT 'pending',
    status ENUM('cart', 'pending', 'processing', 'shipped', 'completed', 'cancelled') DEFAULT 'cart',
    order_note TEXT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (address_id) REFERENCES user_addresses(address_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_date (order_date)
);

-- Bảng order_items: Chi tiết từng sản phẩm trong đơn hàng
CREATE TABLE IF NOT EXISTS order_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(16, 0) NOT NULL,
    
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
);

-- Bảng payments: Thông tin thanh toán đơn hàng
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    order_id INT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(50) DEFAULT 'pending',
    amount DECIMAL(16, 0) NOT NULL,
    transaction_id VARCHAR(100),
    payment_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_order (order_id),
    INDEX idx_user (user_id),
    INDEX idx_status (payment_status)
);

-- Thêm dữ liệu mẫu cho categories
INSERT IGNORE INTO categories (category_id, name, description, image_url) VALUES
(1, 'Thuốc không kê đơn', 'Các loại thuốc có thể mua không cần đơn bác sĩ', '/assets/images/category-otc.jpg'),
(2, 'Thực phẩm chức năng', 'Vitamin, khoáng chất và các chất bổ sung', '/assets/images/category-supplement.jpg'),
(3, 'Dụng cụ y tế', 'Thiết bị và dụng cụ chăm sóc sức khỏe', '/assets/images/category-medical.jpg'),
(4, 'Chăm sóc cá nhân', 'Sản phẩm vệ sinh và chăm sóc cá nhân', '/assets/images/category-personal.jpg');

-- Thêm dữ liệu mẫu cho products
INSERT IGNORE INTO products (product_id, name, description, price, discount_price, stock, category_id, image_url, brand, sku) VALUES
(1, 'Paracetamol 500mg', 'Thuốc giảm đau, hạ sốt hiệu quả', 25000, 20000, 100, 1, '/assets/images/paracetamol.jpg', 'Traphaco', 'PAR500'),
(2, 'Vitamin C 1000mg', 'Tăng cường sức đề kháng, chống oxy hóa', 180000, 150000, 50, 2, '/assets/images/vitamin-c.jpg', 'DHG Pharma', 'VITC1000'),
(3, 'Nhiệt kế điện tử', 'Đo nhiệt độ cơ thể chính xác', 150000, NULL, 30, 3, '/assets/images/thermometer.jpg', 'Omron', 'THERM01'),
(4, 'Khẩu trang y tế 3 lớp', 'Bảo vệ hô hấp, kháng khuẩn', 45000, 35000, 200, 4, '/assets/images/mask.jpg', 'Kimberly Clark', 'MASK3L'),
(5, 'Omega 3 Fish Oil', 'Bổ sung DHA, EPA cho tim mạch', 580000, 520000, 25, 2, '/assets/images/omega3.jpg', 'Nature Made', 'OMEGA3'),
(6, 'Máy đo huyết áp', 'Theo dõi huyết áp tại nhà', 1200000, 1050000, 15, 3, '/assets/images/blood-pressure.jpg', 'Omron', 'BP7120');

-- Thêm user mẫu (password: 123456)
INSERT IGNORE INTO users (user_id, username, email, password, full_name, phone) VALUES
(1, 'testuser', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn Test', '0123456789');

-- Thêm địa chỉ mẫu
INSERT IGNORE INTO user_addresses (user_id, full_name, phone, address_line, ward, district, city, is_default) VALUES
(1, 'Nguyễn Văn Test', '0123456789', '123 Đường ABC, Phường 1', 'Phường 1', 'Quận 1', 'TP.HCM', TRUE),
(1, 'Nguyễn Văn Test', '0123456789', '456 Đường XYZ, Phường 2', 'Phường 2', 'Quận 3', 'TP.HCM', FALSE);

-- Tạo indexes để tối ưu hiệu suất
CREATE INDEX IF NOT EXISTS idx_orders_user_status ON orders(user_id, status);
CREATE INDEX IF NOT EXISTS idx_products_category_active ON products(category_id, is_active);
CREATE INDEX IF NOT EXISTS idx_order_items_order_product ON order_items(order_id, product_id);

-- Tạo view để dễ dàng truy vấn thông tin đơn hàng
CREATE OR REPLACE VIEW order_details AS
SELECT 
    o.order_id,
    o.user_id,
    u.full_name as customer_name,
    u.email as customer_email,
    u.phone as customer_phone,
    o.shipping_address,
    o.total,
    o.payment_method,
    o.payment_status,
    o.status,
    o.order_note,
    o.order_date,
    o.updated_at,
    COUNT(oi.item_id) as total_items,
    GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') as products_summary
FROM orders o
JOIN users u ON o.user_id = u.user_id
LEFT JOIN order_items oi ON o.order_id = oi.order_id
LEFT JOIN products p ON oi.product_id = p.product_id
WHERE o.status != 'cart'
GROUP BY o.order_id;

COMMIT; 