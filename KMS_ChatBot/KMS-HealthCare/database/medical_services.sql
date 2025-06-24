-- Create database if not exists
CREATE DATABASE IF NOT EXISTS medical_services;
USE medical_services;

-- Create service categories table
CREATE TABLE IF NOT EXISTS service_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) NOT NULL,
    description TEXT,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create services table
CREATE TABLE IF NOT EXISTS services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    short_description TEXT,
    features JSON,
    price_range VARCHAR(100),
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES service_categories(id)
);

-- Insert sample data for categories
INSERT INTO service_categories (name, slug, icon, description, display_order) VALUES
('Khám Tổng Quát', 'kham-tong-quat', 'fas fa-stethoscope', 'Dịch vụ khám sức khỏe tổng quát', 1),
('Tim Mạch', 'tim-mach', 'fas fa-heartbeat', 'Chuyên khoa tim mạch', 2),
('Tiêu Hóa', 'tieu-hoa', 'fas fa-prescription-bottle-alt', 'Chuyên khoa tiêu hóa', 3),
('Thần Kinh', 'than-kinh', 'fas fa-brain', 'Chuyên khoa thần kinh', 4),
('Chấn Thương Chỉnh Hình', 'chan-thuong-chinh-hinh', 'fas fa-bone', 'Chuyên khoa chấn thương chỉnh hình', 5),
('Cấp Cứu', 'cap-cuu', 'fas fa-ambulance', 'Dịch vụ cấp cứu 24/7', 6);

-- Insert sample data for services
INSERT INTO services (category_id, name, slug, short_description, features, price_range, is_featured) VALUES
(1, 'Khám Tổng Quát', 'kham-tong-quat', 'Khám sức khỏe định kỳ và tầm soát các bệnh lý thường gặp với quy trình chuyên nghiệp.', 
'["Khám lâm sàng toàn diện", "Xét nghiệm máu cơ bản", "Đo huyết áp, nhịp tim", "Tư vấn dinh dưỡng"]', 
'200.000đ - 500.000đ', FALSE),

(2, 'Tim Mạch', 'tim-mach', 'Chẩn đoán và điều trị các bệnh lý tim mạch với trang thiết bị hiện đại nhất.', 
'["Siêu âm tim", "Điện tim", "Holter 24h", "Thăm dò chức năng tim"]', 
'300.000đ - 2.000.000đ', TRUE),

(3, 'Tiêu Hóa', 'tieu-hoa', 'Chẩn đoán và điều trị các bệnh lý về đường tiêu hóa, gan mật tụy.', 
'["Nội soi dạ dày", "Nội soi đại tràng", "Siêu âm bụng", "Xét nghiệm chức năng gan"]', 
'250.000đ - 1.500.000đ', FALSE),

(4, 'Thần Kinh', 'than-kinh', 'Điều trị các bệnh lý thần kinh từ cơ bản đến phức tạp với đội ngũ chuyên gia.', 
'["Điện não đồ", "MRI não", "Đo tốc độ dẫn truyền thần kinh", "Điều trị đau đầu, chóng mặt"]', 
'400.000đ - 3.000.000đ', FALSE),

(5, 'Chấn Thương Chỉnh Hình', 'chan-thuong-chinh-hinh', 'Điều trị chấn thương và các bệnh lý về xương khớp, cột sống.', 
'["X-quang, CT scan", "Điều trị gãy xương", "Phẫu thuật chỉnh hình", "Vật lý trị liệu"]', 
'300.000đ - 5.000.000đ', FALSE),

(6, 'Cấp Cứu', 'cap-cuu', 'Dịch vụ cấp cứu 24/7 với đội ngũ y bác sĩ luôn sẵn sàng.', 
'["Cấp cứu nội khoa", "Cấp cứu ngoại khoa", "Hồi sức tích cực", "Xe cứu thương"]', 
'Liên hệ', TRUE); 