* mở sql đã có lên và thêm vào !!


-- Bảng danh mục dịch vụ
CREATE TABLE service_categories (
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

-- Bảng dịch vụ chính
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    short_description VARCHAR(500),
    full_description TEXT,
    icon VARCHAR(50),
    image VARCHAR(255),
    price_from DECIMAL(12,2),
    price_to DECIMAL(12,2),
    is_featured BOOLEAN DEFAULT FALSE,
    is_emergency BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES service_categories(id)
);

-- Bảng tính năng của dịch vụ
CREATE TABLE service_features (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_id INT,
    feature_name VARCHAR(200) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- Bảng gói dịch vụ
CREATE TABLE service_packages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(12,2),
    duration VARCHAR(50), -- Thời gian của gói (vd: /lần, /tháng)
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng chi tiết tính năng của gói dịch vụ
CREATE TABLE package_features (
    id INT PRIMARY KEY AUTO_INCREMENT,
    package_id INT,
    feature_name VARCHAR(200) NOT NULL,
    description TEXT,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (package_id) REFERENCES service_packages(id)
);

-- Dữ liệu mẫu cho categories
INSERT INTO service_categories (name, slug, icon, description) VALUES
('Khám Tổng Quát', 'kham-tong-quat', 'fas fa-stethoscope', 'Dịch vụ khám sức khỏe tổng quát và tầm soát bệnh'),
('Tim Mạch', 'tim-mach', 'fas fa-heartbeat', 'Chẩn đoán và điều trị các bệnh lý tim mạch'),
('Tiêu Hóa', 'tieu-hoa', 'fas fa-prescription-bottle-alt', 'Điều trị các bệnh về đường tiêu hóa'),
('Thần Kinh', 'than-kinh', 'fas fa-brain', 'Điều trị các bệnh lý thần kinh'),
('Chấn Thương Chỉnh Hình', 'chan-thuong-chinh-hinh', 'fas fa-bone', 'Điều trị chấn thương và bệnh lý xương khớp'),
('Cấp Cứu', 'cap-cuu', 'fas fa-ambulance', 'Dịch vụ cấp cứu 24/7');

-- Dữ liệu mẫu cho services
INSERT INTO services (category_id, name, slug, short_description, price_from, price_to, is_featured, is_emergency) VALUES
(1, 'Khám Tổng Quát', 'kham-tong-quat', 'Khám sức khỏe định kỳ và tầm soát các bệnh lý thường gặp', 200000, 500000, FALSE, FALSE),
(2, 'Khám Tim Mạch', 'kham-tim-mach', 'Chẩn đoán và điều trị các bệnh lý tim mạch với trang thiết bị hiện đại', 300000, 2000000, TRUE, FALSE),
(3, 'Khám Tiêu Hóa', 'kham-tieu-hoa', 'Chẩn đoán và điều trị các bệnh lý về đường tiêu hóa, gan mật', 250000, 1500000, FALSE, FALSE),
(6, 'Dịch Vụ Cấp Cứu', 'dich-vu-cap-cuu', 'Dịch vụ cấp cứu 24/7 với đội ngũ y bác sĩ luôn sẵn sàng', NULL, NULL, FALSE, TRUE);

-- Dữ liệu mẫu cho service_features
INSERT INTO service_features (service_id, feature_name) VALUES
(1, 'Khám lâm sàng toàn diện'),
(1, 'Xét nghiệm máu cơ bản'),
(1, 'Đo huyết áp, nhịp tim'),
(1, 'Tư vấn dinh dưỡng'),
(2, 'Siêu âm tim'),
(2, 'Điện tim'),
(2, 'Holter 24h'),
(2, 'Thăm dò chức năng tim');

-- Dữ liệu mẫu cho service_packages
INSERT INTO service_packages (name, slug, description, price, duration, is_featured) VALUES
('Gói Cơ Bản', 'goi-co-ban', 'Gói khám sức khỏe cơ bản', 1500000, '/lần', FALSE),
('Gói Nâng Cao', 'goi-nang-cao', 'Gói khám sức khỏe nâng cao', 3500000, '/lần', TRUE),
('Gói Cao Cấp', 'goi-cao-cap', 'Gói khám sức khỏe cao cấp', 6500000, '/lần', FALSE);

-- Dữ liệu mẫu cho package_features
INSERT INTO package_features (package_id, feature_name) VALUES
(1, 'Khám lâm sàng tổng quát'),
(1, 'Xét nghiệm máu cơ bản'),
(1, 'Xét nghiệm nước tiểu'),
(1, 'X-quang phổi'),
(1, 'Điện tim'),
(1, 'Tư vấn kết quả'),
(2, 'Tất cả gói cơ bản'),
(2, 'Siêu âm bụng tổng quát'),
(2, 'Siêu âm tim');