-- Bảng danh mục sản phẩm
CREATE TABLE IF NOT EXISTS product_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng sản phẩm
CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(16, 0) NOT NULL,
    stock INT DEFAULT 0,
    image_url TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(category_id)
);

-- Bảng thuốc (medicine là 1 loại sản phẩm)
CREATE TABLE IF NOT EXISTS medicines (
    medicine_id INT PRIMARY KEY, -- Trùng với product_id
    active_ingredient VARCHAR(255),
    dosage_form VARCHAR(100),
    unit VARCHAR(50),
    usage_instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- Bảng đơn thuốc (nếu chưa có)
CREATE TABLE IF NOT EXISTS prescriptions (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng sản phẩm trong đơn thuốc
CREATE TABLE IF NOT EXISTS prescription_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    product_id INT,
    quantity INT NOT NULL,
    dosage TEXT,
    usage_time TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- Bảng đánh giá sản phẩm (loại bỏ user_id)
CREATE TABLE IF NOT EXISTS product_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Dữ liệu mẫu danh mục
INSERT INTO product_categories (name, description) VALUES
('Thực phẩm chức năng', 'Vitamin, khoáng chất và các loại thực phẩm bổ sung sức khỏe'),
('Thuốc', 'Thuốc kê đơn và không kê đơn từ các nhà sản xuất uy tín'),
('Thiết bị y tế', 'Máy đo huyết áp, nhiệt kế và các thiết bị y tế gia đình'),
('Dược phẩm', 'Các sản phẩm dược phẩm chuyên dụng và thuốc đặc trị');

-- Dữ liệu mẫu sản phẩm
INSERT INTO products (category_id, name, description, price, stock, image_url, is_active) VALUES
-- Thực phẩm chức năng
(1, 'Vitamin C 1000mg', 'Bổ sung Vitamin C tăng cường đề kháng', 320000, 100, '/assets/images/products/vitamin-c.jpg', TRUE),
(1, 'Omega 3 Fish Oil', 'Dầu cá omega 3 hỗ trợ tim mạch', 580000, 50, '/assets/images/products/omega3.jpg', TRUE),
(1, 'Calcium D3', 'Bổ sung canxi và vitamin D3', 250000, 80, '/assets/images/products/calcium.jpg', TRUE),

-- Thuốc
(2, 'Paracetamol 500mg', 'Thuốc hạ sốt, giảm đau', 25000, 200, '/assets/images/products/paracetamol.jpg', TRUE),
(2, 'Amoxicillin 500mg', 'Kháng sinh điều trị nhiễm khuẩn', 45000, 150, '/assets/images/products/amoxicillin.jpg', TRUE),
(2, 'Thuốc cảm cúm Decolgen', 'Điều trị các triệu chứng cảm cúm như sốt, nghẹt mũi, đau họng', 35000, 180, '/assets/images/products/decolgen.jpg', TRUE),
(2, 'Thuốc cảm Bảo Thanh', 'Điều trị ho, cảm cúm từ thảo dược', 42000, 120, '/assets/images/products/baothanh.jpg', TRUE),
(2, 'Thuốc cảm Coldacmin Flu', 'Giảm các triệu chứng cảm cúm, sổ mũi', 38000, 150, '/assets/images/products/coldacmin.jpg', TRUE),

-- Thiết bị y tế
(3, 'Máy đo huyết áp Omron', 'Máy đo huyết áp tự động, độ chính xác cao', 1250000, 30, '/assets/images/products/blood-pressure.jpg', TRUE),
(3, 'Nhiệt kế điện tử', 'Nhiệt kế đo nhiệt độ nhanh chóng', 120000, 60, '/assets/images/products/thermometer.jpg', TRUE),

-- Dược phẩm
(4, 'Dung dịch sát khuẩn', 'Dung dịch sát khuẩn tay nhanh', 45000, 100, '/assets/images/products/sanitizer.jpg', TRUE),
(4, 'Băng gạc y tế', 'Băng gạc vô trùng cao cấp', 35000, 200, '/assets/images/products/bandage.jpg', TRUE);

-- Dữ liệu thuốc chi tiết
INSERT INTO medicines (medicine_id, active_ingredient, dosage_form, unit, usage_instructions) VALUES
(4, 'Paracetamol', 'Viên nén', 'Viên', 'Uống 1 viên/lần, 3-4 lần/ngày sau ăn'),
(5, 'Amoxicillin', 'Viên nang', 'Viên', 'Uống 1 viên/lần, 2 lần/ngày sau ăn');

-- Đánh giá sản phẩm mẫu (không cần user)
INSERT INTO product_reviews (product_id, rating, comment) VALUES
(1, 5, 'Sản phẩm rất tốt, uống thấy khỏe hơn hẳn'),
(1, 4, 'Giá hơi cao nhưng chất lượng tốt'),
(2, 5, 'Dầu cá chất lượng, không tanh'),
(3, 4, 'Viên nén dễ uống, giá cả hợp lý'),
(4, 5, 'Thuốc tốt, giảm đau nhanh'),
(5, 4, 'Hiệu quả trong điều trị'),
(6, 4, 'Máy đo chính xác, dễ sử dụng'),
(7, 5, 'Nhiệt kế đo nhanh và chính xác'),
(8, 4, 'Sát khuẩn tốt, thơm nhẹ'),
(9, 5, 'Băng gạc mềm, thấm hút tốt');
